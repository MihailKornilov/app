<?php
switch(@$_POST['op']) {
	case 'spisok_add'://внесение единицы списка из диалога
		$send = _spisokUnitUpdate();
		jsonSuccess($send);
		break;
	case 'spisok_save'://сохранение данных единицы списка для диалога
		if(!$unit_id = _num($_POST['unit_id'], 1))
			jsonError('Некорректный id единицы списка');

		$send = _spisokUnitUpdate($unit_id);

		jsonSuccess($send);
		break;
	case 'spisok_del'://удаление единицы списка
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный id единицы списка');

		$dialog = _spisokUnitDialog($unit_id);

		$send['action_id'] = _num($dialog['del_action_id']);
		$send['action_page_id'] = _num($dialog['del_action_page_id']);
		$send = _spisokAction3($send, $dialog, $unit_id);

		if(isset($dialog['field1']['deleted'])) {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
			query($sql);
			_historyInsert(3, $dialog, $unit_id);
			_spisokUnitAfter($dialog, $unit_id);
		} else {
			$elem = array();
			if(_table($dialog['table_1']) == '_element') {//если это элемент
				$elem = _elemOne($unit_id);
				//удаление значений
query("DELETE FROM `_element` WHERE `block_id`=-".$unit_id);//todo на удаление
				$sql = "DELETE FROM `_element` WHERE `parent_id`=".$unit_id;
				query($sql);
				//удаление функций
				$sql = "DELETE FROM `_element_func` WHERE `element_id`=".$unit_id;
				query($sql);
				//удаление фильтров
				$sql = "DELETE FROM `_user_spisok_filter` WHERE `element_id_filter`=".$unit_id;
				query($sql);
				//установка позиции в блоке по умолчанию
				$sql = "UPDATE `_block` SET `pos`='top' WHERE `id`=".$elem['block_id'];
				query($sql);
			}

			$sql = "SELECT * FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			$unit = query_assoc($sql);

			$sql = "DELETE FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			query($sql);

			//обновление кеша объекта, если это элемент
			if($elem) {
				_BE('block_clear');
				_BE('elem_clear');
				_spisokFilter('cache_clear');//сброс кеша фильтра, так как возможно был удалён фильтр
				_jsCache();
				$send['elem_del'] = 1;
			}

			//обновление кеша объекта, если это страница
			if($dialog['table_name_1'] == '_page')
				_cache_clear( 'page');

			if($dialog['table_name_1'] == '_element_func')
				if($BL = _blockOne($unit['block_id']))
					_BE('block_clear');
		}

		$send = _spisokAction4($send);

		jsonSuccess($send);
		break;
	case 'spisok_filter_update'://обновление списка после применения фильтра
		if(!$elem_spisok = _num($_POST['elem_spisok']))
			jsonError('Некорректный ID элемента-списка');
		if(!$elSpisok = _elemOne($elem_spisok))
			jsonError('Элемента-списка id'.$elem_spisok.' не существует');
		if($elSpisok['dialog_id'] != 14 && $elSpisok['dialog_id'] != 23)
			jsonError('Элемент id'.$elem_spisok.' не является списком');
		if(!$elem_v = $_POST['elem_v'])
			jsonError('Отсутствуют значения фильтров');
		if(!is_array($elem_v))
			jsonError('Некорректные значения фильров');

		foreach($elem_v as $elem_filter => $v) {
			if(!_num($elem_filter))
				continue;

			_spisokFilter('insert', array(
				'spisok' => $elem_spisok,
				'filter' => $elem_filter,
				'v' => $v
			));
		}

		//элемент количества, привязанный к списку
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elCount = query_assoc($sql)) {
			$send['count_attr'] = '#el_'.$elCount['id'];
			$send['count_html'] = _spisokElemCount($elCount);
		}

		//элемент "очистка фильтра", привязанный к списку
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=80
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elClear = query_assoc($sql)) {
			$send['clear_attr'] = '#cmp_'.$elClear['id'];
			$send['clear_diff'] = _spisokFilter('diff', $elem_spisok);
		}

		$send['spisok_attr'] = '#el_'.$elem_spisok;
		$spFunc = '_spisok'.$elSpisok['dialog_id'];
		$send['spisok_html'] = $spFunc($elSpisok);
		jsonSuccess($send);
		break;
	case 'spisok_filter_clear'://очистка фильтра
		if(!$spisok_id = _num($_POST['spisok_id']))
			jsonError('Некорректный ID элемента-списка');
		if(!$elSpisok = _elemOne($spisok_id))
			jsonError('Элемента-списка id'.$spisok_id.' не существует');

		$sql = "UPDATE `_user_spisok_filter`
				SET `v`=`def`
				WHERE `user_id`=".USER_ID."
				  AND `element_id_spisok`=".$spisok_id;
		query($sql);

		_spisokFilter('cache_clear');

		//элемент количества, привязанный к списку
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `num_1`=".$spisok_id."
				LIMIT 1";
		if($elCount = query_assoc($sql)) {
			$send['count_elem_id'] = $elCount['id'];
			$send['count_html'] = _spisokElemCount($elCount);
		}

		$send['spisok_elem_id'] = $spisok_id;
		$spFunc = '_spisok'.$elSpisok['dialog_id'];
		$send['spisok_html'] = $spFunc($elSpisok);

		//значения по умолчанию для фильтров списка
		$send['def'] = array();
		foreach(_spisokFilter('spisok', $spisok_id) as $r) {
			$dialog_id = _num($r['elem']['dialog_id']);
			$dop = array();
			if($dialog_id == 77) {
				$v = _spisokFilter('v', $r['elem']['id']);
				$mon = substr($v, 0, 7);
				$dop = array(
					'mon' => $mon,
					'td_mon' => _filterCalendarMon($mon),
					'cnt' => _filterCalendarContent($r['elem'], $mon, $v)
				);
			}
			$send['def'][] = array(
				'dialog_id' => $dialog_id,
				'elem_id' => $r['elem']['id'],
				'dop' => $dop,
				'v' => $r['def']
			);
		}

		$send['filter'] = _spisokFilter('page_js');

		jsonSuccess($send);
		break;
	case 'spisok_14_next'://догрузка списка-шаблона
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 14)
			jsonError('Элемент не является списком-шаблоном');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');

		$send['spisok'] = _spisok14($el, $next);
		jsonSuccess($send);
		break;
	case 'spisok_23_next'://догрузка списка-таблицы
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 23)
			jsonError('Элемент не является списком-таблицей');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока списка');

		$send['spisok'] = _spisok23($el, $next);
		jsonSuccess($send);
		break;
	case 'spisok_23_sort':
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 23)
			jsonError('Элемент не является списком-таблицей');
		if(!$dialog_id = _num($el['num_1']))
			jsonError('Отсутствует ID диалога');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');

		$arr = $_POST['arr'];
		if(empty($arr))
			jsonError('Отсутствуют значения для сортировки');
		if(!is_array($arr))
			jsonError('Значения не являются массивом');

		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;

			$upd = "`sort`=".$n;
			if(isset($dialog['field1']['parent_id']))
				$upd .= ",`parent_id`="._num($r['parent_id']);

			$sql = "UPDATE `".$dialog['table_name_1']."`
					SET ".$upd."
					WHERE `id`=".$id;
			query($sql);
		}

		//обновление количеств, если присутствуют
		foreach($dialog['cmp'] as $r)
			_spisokUnitUpd54($r);

		//очистка кеша страниц
		if($dialog['table_name_1'] == '_page')
			_cache_clear('page');

		jsonSuccess();
		break;
	case 'spisok_29_connect':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента диалога');

		$v = _txt($_POST['v']);

		$send['spisok'] = _29cnn($cmp_id, $v);
		jsonSuccess($send);
		break;
	case 'spisok_59_unit':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента');
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный ID выбранного элемента');

		$send['html'] = _spisok59unit($cmp_id, $unit_id);
		jsonSuccess($send);
		break;
}

function _spisokUnitDialog($unit_id) {//получение данных о диалоге и проверка наличия единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога '.$dialog_id.' не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');

	if(!$dialog['table_1'])
		return $dialog;

	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '"._table($dialog['table_1'])."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	//получение данных единицы списка, если редактируется
	if($unit_id > 0) {
		if(!$r = _spisokUnitQuery($dialog, $unit_id))
			jsonError('Записи не существует');
		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	return $dialog;
}
function _spisokUnitUpdate($unit_id=0) {//внесение/редактирование единицы списка
	$dialog = _spisokUnitDialog($unit_id);
	$unitOld = _spisokUnitQuery($dialog, $unit_id);

	define('IS_ELEM', $dialog['table_1'] == 5);// '_element'

	$act = $unit_id ? 'edit' : 'insert';

	$block_id = _num($_POST['block_id'], 1);

	$POST_CMP = _spisokUnitCmpTest($dialog);

	_auth98($dialog, $POST_CMP);
	_auth99($dialog, $POST_CMP);

	//элемент выбирает значение из диалога - через [11] - перехват внесения данных
	_elem13_v_choose($block_id, $dialog, $POST_CMP);

	//элемент выбирает блоки из диалога - через [19] - перехват внесения данных
	_elem19_block_choose($dialog);

	//сохранение настройки истории действий - через [67] - перехват внесения данных
	PHP12_history_setup_save($dialog);

	//выбор цвета для динамической заливки - через [11] - перехват внесения данных
	_block_bg70($block_id, $dialog, $POST_CMP);

	$unit_id = _spisokUnitInsert($unit_id, $dialog, $block_id);

	if(IS_ELEM)
		_BE('elem_clear');

	// ---=== СЕКЦИЯ ОБНОВЛЕНИЯ ДАННЫХ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);

	_spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id);
	_spisokUnitBalansUpd($dialog, $POST_CMP);

	//получение обновлённых данных единицы списка
	$unit = _spisokUnitQuery($dialog, $unit_id);

	_historyInsertEdit($dialog, $unitOld, $unit);

	if(IS_ELEM) {
		_BE('block_clear');
		_BE('elem_clear');
		if($bl = _blockOne($unit['block_id']))
			if($bl['obj_name'] == 'dialog') {
				_BE('dialog_clear');
				$dlg = _dialogQuery($bl['obj_id']);
				$unit = $dlg['cmp'][$unit_id];
			}
	}

	$vvv = @$_POST['vvv'];
	foreach($dialog['cmp'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//---=== ДЕЙСТВИЯ ПРИ НАСТРОЙКИ ЭЛЕМЕНТОВ ===---
			//конкретная функция
			case 12:
				$func = $cmp['txt_1'].'_save';
				if(!function_exists($func))
					break;
				$func($cmp, $vvv[$cmp_id], $unit);
				break;
			//Применение загруженных изображений
			case 60: _cmpV60($cmp, $unit); break;
		}

	_spisokUnitUpd27($unit);
	_spisokUnitUpd54($unit);
	_spisokUnitUpd55($unit);
	_spisokUnitUpd72($dialog, $unit);

	_spisokUnitAfter($dialog, $unit_id, $unitOld);

	if(_table($dialog['table_1']) == '_page')
		_cache_clear( 'page');

	if(_table($dialog['table_1']) == '_element_func')
		if($BL = _blockOne($unit['block_id']))
			_BE('elem_clear');

	if(IS_ELEM) {
		_BE('elem_clear');
		$elem = _elemOne($unit_id);
		if(!empty($elem['block']))
			_BE('block_clear');
		$unit['title'] = _elemTitle($unit_id);
		_jsCache();
	}

	_app_create($dialog, $unit_id);

	$send = array(
		'unit' => _arrNum($unit),
		'action_id' => _num($dialog[$act.'_action_id']),
		'action_page_id' => _num($dialog[$act.'_action_page_id'])
	);

	$send = _spisokAction3($send, $dialog, $unit_id);
	$send = _spisokAction4($send);

	if(IS_ELEM)
		$send['elem_js'] = _jsCacheElemOne($unit_id);

	return $send;
}
function _spisokUnitCmpTest($dialog) {//проверка корректности компонентов диалога
	$dlgParent = $dialog;

	if($parent_id = $dialog['dialog_parent_id'])
		if(!$dlgParent = _dialogQuery($parent_id))
			return array();

	if(!$dlgParent['table_1'])
		return array();

	$POST_CMP = @$_POST['cmp'];

	if($dialog['cmp_no_req'] && empty($POST_CMP))
		return array();
	if(!is_array($POST_CMP))
		jsonError('Компоненты диалога не являются массивом');

	//выбор значений, которые существуют в диалоговом окне, чтобы в дальнейшем выстроить по порядку в соответствии с блоками
	$CMP = array();
	foreach($POST_CMP as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('Некорректный id компонента диалога');
		if(empty($dialog['cmp'][$cmp_id]))
			jsonError('Отсутствует компонент id'.$cmp_id.' в диалоге');
		$CMP[$cmp_id] = $val;
	}

	$send = array();
	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		if(!isset($CMP[$cmp_id]))
			continue;
		if(!$col = @$cmp['col'])
			continue;
/*			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => 'Отсутствует имя колонки в компоненте id'.$cmp_id
			));
*/
		if(!isset($dlgParent['field1'][$col]) && !isset($dlgParent['field2'][$col]))
			jsonError('В таблице отсутствует колонка с именем "'.$col.'"');

		$v = _txt($CMP[$cmp_id]);

		//массив для отправки ошибки
		$is_err = 0;
		$err_msg = $cmp['req_msg'] ? $cmp['req_msg'] : 'Необходимо заполнить поле,<br>либо выбрать значение';

		switch($cmp['dialog_id']) {
			case 8://текстовое поле
				if($cmp['req'] && !strlen($v))
					$is_err = 1;
				if($cmp['num_1'] == 32)//любой текст
					break;
				if($cmp['num_1'] != 33)//цифры и числа
					break;

				$v = round($v, $cmp['num_2']);
				if($cmp['req'] && !$v && !$cmp['num_4'])
					$is_err = 1;
				if($v < 0 && !$cmp['num_3']) {
					$is_err = 1;
					$err_msg = 'Значение не может быть отрицательным';
				}
				break;
			default:
				if($cmp['req'] && !$v)
					$is_err = 1;

				$ex = explode('_', $col);
				if($ex[0] == 'num')
					$v = _num($v, 1);
				if($ex[0] == 'count')
					$v = _num($v,1);
				if($ex[0] == 'cena')
					$v = _cena($v, 1);
		}

		if($is_err)
			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => $err_msg
			));

		$send[$cmp_id] = $v;
	}

	if($dialog['cmp_no_req'] && !$send)
		return array();

	if(!$send)
		jsonError('Нет данных для внесения');

	return $send;
}
function _spisokUnitInsert($unit_id, $dialog, $block_id) {//внесение новой единицы списка, если отсутствует
	if($unit_id > 0)
		return $unit_id;
	if(!$dialog['table_1'])
		return 0;

	$page_id = _num($_POST['page_id']);
	$parent_id = 0;//группировка в таблице _element

	//если производится вставка в блок: проверка, чтобы в блок не попало 2 элемента
	if(IS_ELEM && $block_id > 0 && !$unit_id) {
		if(!$block = _blockOne($block_id))
			jsonError('Блока не сущетвует');
		if($elem = $block['elem']) {
			if($elem['dialog_id'] == 23//таблица
			|| $elem['dialog_id'] == 27//баланс
			|| $elem['dialog_id'] == 44//сборный текст
			) {
				$block_id = 0;
				$parent_id = $elem['id'];
			} else
				jsonError('В блоке уже есть элемент');
		}
	}

	$sql = "INSERT INTO `"._table($dialog['table_1'])."` (`id`) VALUES (0)";
	query($sql);

	//подмена id блока отрицательным значением для группировки
//	if($unit_id < 0)
//		$block_id = $unit_id;

	$unit_id = query_insert_id(_table($dialog['table_1']));

	//обновление некоторых колонок таблицы 1
	foreach($dialog['field1'] as $field => $i) {
		if($field == 'app_id') {
			//если вносится страница SA, id приложения не присваивается
			if(_table($dialog['table_1']) == '_page' && $dialog['id'] == '101')
				continue;

			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `app_id`=".APP_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'dialog_id') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `dialog_id`=".$dialog['id']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'num') {//установка порядкового номера
			$sql = "SELECT IFNULL(MAX(`num`),0)+1
					FROM `"._table($dialog['table_1'])."`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog['id'];
			$num = query_value($sql);
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `num`=".$num."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'page_id') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `page_id`=".$page_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'block_id' && $block_id) {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `block_id`=".$block_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'parent_id' && $parent_id && _table($dialog['table_1']) == '_element') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `parent_id`=".$parent_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'width' && IS_ELEM && $dialog['element_width']) {
			$sql = "UPDATE `_element`
					SET `width`=".$dialog['element_width']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'sort') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `sort`="._maxSql(_table($dialog['table_1']))."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'user_id_add') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `user_id_add`=".USER_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
	}


	//внесение данных таблицы 2, если есть
	if($dialog['table_2']) {
		$sql = "INSERT INTO `"._table($dialog['table_2'])."` (`".$dialog['table_2_field']."`) VALUES (".$unit_id.")";
		query($sql);

		$unit_2 = query_insert_id(_table($dialog['table_2']));
		foreach($dialog['field2'] as $field => $i) {
			if($field == 'app_id') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'dialog_id') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `dialog_id`=".$dialog['id']."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `"._table($dialog['table_2'])."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog['id'];
				$num = query_value($sql);
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `num`=".$num."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'user_id_add') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `user_id_add`=".USER_ID."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
		}
	}

	_historyInsert(1, $dialog, $unit_id);

	return $unit_id;
}
function _elementFocusClear($dialog, $POST_CMP, $unit_id) {//если в таблице присутствует колонка `focus`, то предварительное снятие флага фокуса с других элементов объекта (для таблицы _element)
	if(!$dialog['table_1'])
		return;
	if(_table($dialog['table_1']) != '_element')
		return;
	if(empty($POST_CMP))
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'focus')
			continue;
		if(!$v)
			return;

		$sql = "SELECT `block_id`
				FROM `_element`
				WHERE `id`=".$unit_id;
		$block_id = _num(query_value($sql));

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		$block = query_assoc($sql);

		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='".$block['obj_name']."'
				  AND `obj_id`=".$block['obj_id'];
		if(!$block_ids = query_ids($sql))
			return;

		$sql = "UPDATE `_element`
				SET `focus`=0
				WHERE `block_id` IN (".$block_ids.")";
		query($sql);

		return;
	}
}
function _pageDefClear($dialog, $POST_CMP) {//для таблицы _page: очистка `def`, если устанавливается новая страница по умолчанию
	if(!$dialog['table_1'])
		return;
	if(_table($dialog['table_1']) != '_page')
		return;
	if(empty($POST_CMP))
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'def')
			continue;
		if(!$v)
			return;

		//снятие флага 'страница по умолчанию' со всех страниц приложения
		$sql = "UPDATE `_page`
				SET `def`=0
				WHERE `app_id`=".APP_ID."
				  AND !`sa`";
		query($sql);

		return;
	}
}
function _spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id) {//обновление компонентов единицы списка
	$dlgParent = $dialog;
	if($parent_id = $dialog['dialog_parent_id'])
		if(!$dlgParent = _dialogQuery($parent_id))
			return;

	if(!$dlgParent['table_1'])
		return;
	if(empty($POST_CMP))
		return;

	$update1 = array();
	$update2 = array();
	foreach($POST_CMP as $cmp_id => $v) {
		$cmp = $dialog['cmp'][$cmp_id];
		$col = $cmp['col'];

		if(IS_ELEM && $col == 'col') {//если элемент, установка номера таблицы, в которой содержится колонка
			$num = 0;

			if($v)
				if($el = _elemOne($unit_id))
					if($el['block']['obj_name'] == 'dialog')
						if($dlg = _dialogQuery($el['block']['obj_id'])) {

							if($parent_id = $dlg['dialog_parent_id'])
								$dlg = _dialogQuery($parent_id);

							if(isset($dlg['field1'][$v]))
								$num = 1;
							else
								if(isset($dlg['field2'][$v]))
									$num = 2;
								else
									$v = '';

						}

			$update1[] = "`table_num`=".$num;
		}

		if($cmp['table_num'] == 1)
			$update1[] = "`".$col."`='".addslashes($v)."'";
		if($cmp['table_num'] == 2)
			$update2[] = "`".$col."`='".addslashes($v)."'";
	}

	if(!empty($update1)) {
		$sql = "UPDATE `"._table($dlgParent['table_1'])."`
				SET ".implode(',', $update1)."
				WHERE `id`=".$unit_id;
		query($sql);
	}

	if(!empty($update2)) {
		$field2 = $dlgParent['field2'];

		$cond = "`".$dlgParent['table_2_field']."`=".$unit_id;
		if(isset($field2['app_id']))
			$cond .= " AND `app_id`=".APP_ID;
		if(isset($field2['dialog_id']))
			$cond .= " AND `dialog_id`=".$dlgParent['id'];

		$sql = "SELECT `id`
				FROM `"._table($dlgParent['table_2'])."`
				WHERE ".$cond."
				LIMIT 1";
		if($unit_2 = _num(query_value($sql))) {
			$sql = "UPDATE `"._table($dlgParent['table_2'])."`
					SET ".implode(',', $update2)."
					WHERE `id`=".$unit_2;
			query($sql);
		}
	}
}
function _spisokAction3($send, $dialog, $unit_id) {//добавление значений для отправки, если действие 3 - обновление содержания блоков
	if($dialog['table_1'] != 5)
		return $send;
	if($send['action_id'] != 3)
		return $send;
	if(!$elem = _elemOne($unit_id))
		return $send;
	if($elem['parent_id'])//была вставка доп-значения для элемета
		return $send;
	if($elem['block_id'] < 0)
		return $send;

	$send['obj_name'] = $elem['block']['obj_name'];
	$send['level'] = _blockLevelChange($elem['block']['obj_name'], $elem['block']['obj_id']);

	return $send;
}
function _spisokAction4($send) {//действие 4 - обновление исходного диалога
	if($send['action_id'] != 4)
		return $send;
	if(!$dialog_id = _num(@$_POST['dialog_source']))
		return $send;

	$_POST['unit_id'] = 0;
	$send['dialog_source'] = _dialogOpenLoad($dialog_id);

	return $send;
}
function _cmpV60($cmp, $unit) {//Применение загруженных изображений
	//поле, хранящее список id изображений
	if(!$col = $cmp['col'])
		return;

	//прикрепление изображений к единице списка
	$sql = "UPDATE `_image`
			SET `obj_name`='elem_".$cmp['id']."',
				`obj_id`=".$unit['id']."
			WHERE `obj_name`='elem_".$cmp['id']."_".USER_ID."'";
	query($sql);

	$sql = "UPDATE `_image`
			SET `deleted`=1,
				`user_id_del`=".USER_ID.",
				`dtime_del`=CURRENT_TIMESTAMP
			WHERE `obj_name`='elem_".$cmp['id']."'
			  AND `obj_id`=".$unit['id']."
			  AND `id` NOT IN ("._ids($unit[$col]).")";
	query($sql);

	//обновление сортировки
	$sort = 0;
	foreach(_ids($unit[$col], 1) as $id) {
		$sql = "UPDATE `_image`
				SET `sort`=".$sort++.",
					`deleted`=0,
					`user_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$id;
		query($sql);
	}
}
function _filterCheckSetup_save($cmp, $val, $unit) {//сохранение настройки фильтра для галочки. Подключаемая функция [12]
	/*
		-114
		$cmp  - компонент из диалога, отвечающий за настройку таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий таблицу, для которой происходит настройка
	*/

	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $id => $r) {
			if($id = _num($id))
				$idsNoDel .= ','.$id;
			if(!$num_8 = _num($r['num_8']))
				continue;
			$txt_8 = $num_8 > 2 ? _txt($r['txt_8']) : '';
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				".$num_8.",
				'".addslashes($txt_8)."'
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	if(!empty($update)) {
		$sql = "INSERT INTO `_element` (
					`id`,
					`block_id`,
					`num_8`,
					`txt_8`
				)
				VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`block_id`=VALUES(`block_id`),
					`num_8`=VALUES(`num_8`),
					`txt_8`=VALUES(`txt_8`)";
		query($sql);
	}


	//очистка неиспользованных элементов
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-114)";
	query($sql);
}
function _pageUserAccess_save($cmp, $val, $unit) {//сохранение доступа к страницам для конкретного пользователя
	if(!is_array($val))
		return;
	if(!$user_id = @$val['user_id'])
		return;

	$sql = "DELETE FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$user_id;
	query($sql);


	if($ids = _ids(@$val['ids'], 1)) {
		$upd = array();
		foreach($ids as $page_id)
			$upd[] = "(".APP_ID.",".$user_id.",".$page_id.")";

		$sql = "INSERT INTO `_user_page_access`
					(`app_id`,`user_id`,`page_id`)
				VALUES ".implode(',', $upd);
		query($sql);
	}

	//отключение входа в приложение, если нужно
	$sql = "SELECT `num_1`
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `connect_1`=".$user_id."
			LIMIT 1";
	if(!_num(query_value($sql))) {
		$sql = "UPDATE `_user_auth`
				SET `app_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `user_id`=".$user_id;
		query($sql);
	}

	_cache_clear( 'AUTH', 1);
	_cache_clear( 'page');
	_cache_clear( 'user'.$user_id);
}
function _pageUserAccessAll_save($cmp, $val, $unit) {//сохранение доступа в приложение для всех пользователей
	$sql = "UPDATE `_spisok`
			SET `num_1`=0
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011";
	query($sql);

	$ids = _ids($val);

	$sql = "UPDATE `_spisok`
			SET `num_1`=1
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011
			  AND `connect_1` IN (".$ids.")";
	query($sql);

	$sql = "UPDATE `_user_auth`
			SET `app_id`=0
			WHERE `app_id`=".APP_ID."
			  AND `user_id` NOT IN (".$ids.")";
	query($sql);

	_cache_clear( 'AUTH', 1);
	_cache_clear( 'page');

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011";
	foreach(query_arr($sql) as $r)
		_cache_clear('user'.$r['connect_1']);
}
function _spisokUnitBalansUpd($dialog, $POST_CMP) {//обновление значения стартовой суммы (для правильного подсчёта баланса)
	/*
		Стартовая сумма нужна для корректного отображения баланса (например, расчётного счёта)
		Если при расчёте баланса содержится значение, которое было изменено, этот баланс должен будет пересчитан
	*/
	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		//только для элементов-балансов
		if($cmp['dialog_id'] != 27)
			continue;

		//получение всех слагаемых баланса
		$sql = "SELECT *
				FROM `_element`
				WHERE `parent_id`=".$cmp_id;
		if(!$arr = query_arr($sql))
			continue;

		//поиск значения сохраняемого диалога, которое содержится в балансе
		foreach($arr as $id => $r) {
			if(!$elm_id = _num($r['txt_2']))
				continue;
			if(isset($POST_CMP[$elm_id]))
				_spisokUnitUpd27($cmp);
		}
	}
}
function _spisokUnitUpd27($unit) {//обновление сумм значений единицы списка (баланс)
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 27)
		return;
	//блок, в котором размещается "баланс"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockOne($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	//диалог, в котором размещаются значения (данные этого списка будут обновляться)
	if(!$DSrc = _dialogQuery($BL['obj_id']))
		return;

	$DSRC_COND = _spisokCondDef($BL['obj_id']);

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` ".$DSRC_COND;
	query($sql);

	if(!$ids = _ids($unit['txt_2']))
		return;

	//получение данных значений для подсчёта
	$sql = "SELECT `txt_2`,`num_8`
			FROM `_element`
			WHERE `id` IN (".$ids.")";
	if(!$elData = query_ass($sql))
		return;

	//получение самих значений для подсчёта
	$sql = "SELECT `id`,`col`
			FROM `_element`
			WHERE LENGTH(`col`)
			  AND `id` IN ("._idsGet($elData, 'key').")";
	if(!$elCol = query_ass($sql))
		return;


	$upd = '';
	foreach($elCol as $id => $col) {
		$znak = $elData[$id] ? '-' : '+';
		$upd .= $znak.'`'.$col.'`';
	}

	//процесс обновления
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=".$upd."
			WHERE `t1`.`id` ".$DSRC_COND;
	query($sql);
}
function _spisokUnitUpd54($unit) {//обновление количеств привязанного списка (при создании элемента)
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 54)
		return;

	//id компонента в диалоге, в котором размещается привязка (количество этих значений будет считаться)
	if(!$cmp_id = _num($unit['num_1']))
		return;
	if(!$cmp = _elemOne($cmp_id))
		return;

	//id диалога, в котором размещается привязка
	if(!$dialog_id = $cmp['block']['obj_id'])
		return;
	if(!$DConn = _dialogQuery($dialog_id))
		return;

	//блок, в котором размещается "количество"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockOne($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$DSrc = _dialogQuery($BL['obj_id']))//диалог, к которому привязан список (данные этого списка будут обновляться)
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` "._spisokCondDef($BL['obj_id']);
	query($sql);

	$sql = "SELECT
				`".$cmp['col']."`,
				COUNT(`id`)
			FROM `"._table($DConn['table_1'])."`
			WHERE `dialog_id`=".$dialog_id."
			  AND `".$cmp['col']."`
			  AND !`deleted`
			GROUP BY `".$cmp['col']."`";
	if(!$ass = query_ass($sql))//выход, если нечего обновлять
		return;

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $c) {
		$sql = "UPDATE "._tableFrom($DSrc)."
				SET `".$unit['col']."`=".$c."
				WHERE `t1`.`id`=".$id." "._spisokCondDef($BL['obj_id']);
		query($sql);
/*
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._table($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
*/
	}

	//обновление сумм родительских значений, если есть дочерние
	if(!isset($DSrc['field1']['parent_id']))
		return;

	$sql = "SELECT DISTINCT `parent_id`
			FROM `"._table($DSrc['table_1'])."`
			WHERE `dialog_id`=".$BL['obj_id']."
			  AND `parent_id`";
	if(!$ids = query_ids($sql))
		return;

	foreach(_ids($ids, 1) as $id) {
		$sql = "SELECT SUM(`".$unit['col']."`)
				FROM `"._table($DSrc['table_1'])."`
				WHERE `parent_id`=".$id;
		$count = query_value($sql);
		$count += empty($ass[$id]) ? 0 : $ass[$id];

		$sql = "UPDATE `"._table($DSrc['table_1'])."`
				SET `".$unit['col']."`=".$count."
				WHERE `id`=".$id;
		query($sql);
	}
}
function _spisokUnitUpd55($unit) {//обновление сумм привязанного списка
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 55)
		return;
	if(!$cmp_id = _num($unit['num_1']))//id компонента в диалоге, в котором размещается привязка (сумма этих значений будет считаться)
		return;
	if(!$cmp = _elemOne($cmp_id))
		return;
	if(!$dialog_id = $cmp['block']['obj_id'])//id диалога, в котором размещается привязка
		return;
	if(!$DConn = _dialogQuery($dialog_id))
		return;

	//диалог, к которому привязан список (данные этого списка будут обновляться)
	if(!$DSrc_id = _num($cmp['num_1']))
		return;
	if(!$DSrc = _dialogQuery($DSrc_id))
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` "._spisokCondDef($DSrc_id);
	query($sql);

	//получение элемента, который указывает на элемент, сумму значения которого нужно будет считать
	if(!$elem_id = _num($unit['num_2']))
		return;
	if(!$elForSum = _elemOne($elem_id))
		return;
	if(!$sum_col = $elForSum['col'])
		return;

	$sql = "SELECT
				`".$cmp['col']."`,
				SUM(`".$sum_col."`)
			FROM `"._table($DConn['table_1'])."`
			WHERE `dialog_id`=".$dialog_id."
			  AND `app_id`=".APP_ID."
			  AND `".$cmp['col']."`
			  AND !`deleted`
			GROUP BY `".$cmp['col']."`";
	if(!$ass = query_ass($sql))//выход, если нечего обновлять
		return;

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $c) {
		$sql = "UPDATE "._tableFrom($DSrc)."
				SET `".$unit['col']."`=".$c."
				WHERE `t1`.`id`=".$id." "._spisokCondDef($DSrc_id);
		query($sql);
/*
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._table($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
*/
	}
}
function _spisokUnitUpd72($dialog, $unit) {//обновление кеша после применения действия для блока
	if($dialog['id'] != 72)
		return;
	if(empty($unit['obj_name']))
		return;
	if(empty($unit['obj_id']))
		return;

	_BE('block_clear');
}

function _elem13_v_choose($block_id, $dialog, $POST_CMP) {//выбор значения из диалога [13] через [11]
	if(!IS_ELEM)
		return;

	//блок, который размещает элемент [13]
	if(!$block = _blockOne($block_id))
		return;
	//сам элемент [13]
	if(!$el = $block['elem'])
		return;
	if($el['dialog_id'] != 13)
		return;

	//сохранение данных (выбор значения) должно происходить через [11]
	if($dialog['id'] != 11)
		return;

	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dialog['cmp']))
		jsonError('Пустой диалог 11');

	$elem_func_id = key($dialog['cmp']);

	if(!$v = $POST_CMP[$elem_func_id])
		jsonError('Значение не выбрано');

	$title = _num($v) ? _elemTitle($v) : $v;

	$send = array(
		'v' => $v,
		'title' => $title
	);

	jsonSuccess($send);
}
function _elem19_block_choose($dialog) {//выбор блоков через [11]
	//выбор блоков должен происходить через [19]
	if($dialog['id'] != 19)
		return;

	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dialog['cmp']))
		jsonError('Пустой диалог 19');

	$vvv = @$_POST['vvv'];
	$elem_func_id = key($dialog['cmp']);

	$send['ids'] = _ids($vvv[$elem_func_id]);

	jsonSuccess($send);
}

function _block_bg70($block_id, $dialog, $POST_CMP) {//выбор пути к динамической заливке блока через [11]
	if(empty($_POST['prm']['bg70_choose']))
		return;
	//блок, для которого применяется заливка
	if(!$block = _blockOne($block_id))
		return;
	//выбор пути должно происходить через [11]
	if($dialog['id'] != 11)
		return;

	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dialog['cmp']))
		jsonError('Пустой диалог 11');

	$elem_func_id = key($dialog['cmp']);

	if(!$send['bg'] = _ids($POST_CMP[$elem_func_id]))
		jsonError('Значение не выбрано');

	jsonSuccess($send);
}

function _spisokUnitAfter($dialog, $unit_id, $unitOld=array()) {//выполнение действий после обновления единицы списка
	if(!$dialog['table_1'])
		return;
	$sql = "SELECT *
			FROM `"._table($dialog['table_1'])."`
			WHERE `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return;

	//получение компонентов диалога, которые отвечают за внесение данных
	//будет проверка, есть ли какой-то компонент, который участвует в подсчёте баланса
	$cmpInsertIds = array();
	foreach($dialog['cmp'] as $cmp) {
		if(!$cmp['col'])
			continue;
		if($cmp['dialog_id'] == 27)
			continue;
		if($cmp['dialog_id'] == 54)
			continue;
		if($cmp['dialog_id'] == 55)
			continue;
		$cmpInsertIds[] = $cmp['id'];
	}


	foreach($dialog['cmp'] as $cmp)
		switch($cmp['dialog_id']) {
			//обновление суммы, если какой-то элемент самого диалога участвует в подсчёте (для стартовых сумм)
			case 27:
				if(empty($cmpInsertIds))
					break;
				if(empty($cmp['txt_2']))
					break;

				$sql = "SELECT *
						FROM `_element`
						WHERE `num_1` IN (".implode(',', $cmpInsertIds).")
						  AND `id` IN (".$cmp['txt_2'].")
						ORDER BY `id` DESC";
				if(!$arr = query_arr($sql))
					break;

				$send = array();
				foreach($arr as $r) {
					$el = $dialog['cmp'][$r['num_1']];
					$send[] = array(
						'id' => $el['id'],
						'block_id' => $el['block_id'],
						'connect_id' => $unit_id     //id единицы списка, баланс которой будет пересчитан
					);
				}

				_spisokUnitAfter27($send);
				break;
			//привязанные списки
			case 29:
				_spisokUnitAfter54($cmp, $dialog, $unit, $unitOld); //пересчёт количеств привязаного списка [54]
				$elUpd = _spisokUnitAfter55($cmp, $dialog, $unit);  //пересчёт cумм привязаного списка [55]
				_spisokUnitAfter27($elUpd);                         //подсчёт балансов после обновления сумм [27]
				break;
		}


}
function _spisokUnitAfter54($cmp, $dialog, $unit, $unitOld) {//пересчёт количеств привязаного списка
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=54
			  AND `num_1`=".$cmp['id'];
	if(!$arr = query_arr($sql))
		return;
	if(!$col = $cmp['col'])//имя колонки, по которой привязан список
		return;
	if(!$connect_id = _num($unit[$col]))//значение, id единицы привязанного списка.
		return;

	$connect_old = 0;
	$count_old = 0;
	if(!empty($unitOld))
		if($connect_old = _num($unitOld[$col])) {
			//получение старого количества для обновления
			$sql = "SELECT COUNT(*)
					FROM "._tableFrom($dialog)."
					WHERE `".$col."`=".$connect_old." "._spisokCondDef($dialog['id']);
			$count_old = _num(query_value($sql));
		}

	//получение нового количества для обновления
	$sql = "SELECT COUNT(*)
			FROM "._tableFrom($dialog)."
			WHERE `".$col."`=".$connect_id." "._spisokCondDef($dialog['id']);
	$count = _num(query_value($sql));

	foreach($arr as $r) {
		if(!$col = $r['col'])
			continue;

		$bl = _blockOne($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		if($connect_old) {
			$sql = "UPDATE "._tableFrom($dlg)."
					SET `".$col."`=".$count_old."
					WHERE `t1`.`id`=".$connect_old." "._spisokCondDef($dlg['id']);
			query($sql);
		}

		$sql = "UPDATE "._tableFrom($dlg)."
				SET `".$col."`=".$count."
				WHERE `t1`.`id`=".$connect_id." "._spisokCondDef($dlg['id']);
		query($sql);
	}
}
function _spisokUnitAfter55($cmp, $dialog, $unit) {//пересчёт сумм привязаного списка после внесения/удаления данных
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=55
			  AND `num_1`=".$cmp['id'];
	if(!$arr = query_arr($sql))
		return array();
	if(!$col = $cmp['col'])//имя колонки, по которой привязан список
		return array();
	if(!$connect_id = _num($unit[$col]))//значение, id единицы привязанного списка.
		return array();

	$send = array();//значения, которые были пересчитаны. По ним будет потом посчитан баланс, если потребуется.
	foreach($arr as $elem_id => $r) {
		if(!$colSumSet = $r['col'])
			continue;
		//поиск колонки, по которой будет производиться подсчёт суммы
		if(!$el = _elemOne($r['num_2']))
			continue;
		if(!$colSum = $el['col'])
			continue;

		$bl = _blockOne($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		//получение нового количества для обновления
		$sql = "SELECT IFNULL(SUM(`".$colSum."`),0)
				FROM "._tableFrom($dialog)."
				WHERE `".$col."`=".$connect_id." "._spisokCondDef($dialog['id']);
		$sum = query_value($sql);

		$sql = "UPDATE "._tableFrom($dlg)."
				SET `".$colSumSet."`=".$sum."
				WHERE `t1`.`id`=".$connect_id." "._spisokCondDef($dlg['id']);
		query($sql);

		$send[] = array(
			'id' => $elem_id,
			'block_id' => $r['block_id'],
			'connect_id' => $connect_id     //id единицы списка, баланс которой будет пересчитан
		);
	}

	return $send;
}
function _spisokUnitAfter27($elUpd) {
	if(empty($elUpd))
		return;

	foreach($elUpd as $el) {
		if(!$bl = _blockOne($el['block_id']))
			continue;
		if($bl['obj_name'] != 'dialog')
			continue;
		if(!$dialog = _dialogQuery($bl['obj_id']))
			continue;

		foreach($dialog['cmp'] as $cmp) {
			if($cmp['dialog_id'] != 27)
				continue;
			if(empty($cmp['col']))//имя колонки, являющаяся балансом
				continue;
			if(empty($cmp['txt_2']))//список id элементов, составляющих сумму
				continue;

			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$cmp['txt_2'].")";
			if(!$arr = query_arr($sql))
				continue;

			$upd_flag = 0;//флаг обновления баланса. Будет установлен, если присутствует элемент, участвующий в обновлении.
			foreach($arr as $r)
				if($r['txt_2'] == $el['id']) {
					$upd_flag = 1;
					break;
				}
			if(!$upd_flag)
				continue;

			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'txt_2').")";
			if(!$dlgElUpd = query_arr($sql))
				continue;

			$upd = '';
			foreach($arr as $r) {
				if(!$elUpd = $dlgElUpd[$r['txt_2']])
					continue;
				if(!$col = $elUpd['col'])
					continue;

				$znak = $r['num_8'] ? '-' : '+';
				$upd .= $znak."`".$col."`";
			}

			//процесс обновления
			$sql = "UPDATE "._tableFrom($dialog)."
					SET `".$cmp['col']."`=".$upd."
					WHERE `t1`.`id`=".$el['connect_id']." "._spisokCondDef($dialog['id']);
			query($sql);
		}
	}
}


