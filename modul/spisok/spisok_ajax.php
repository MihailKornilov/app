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
		$send = _spisokAction3($send, $dialog, $unit_id, 1);

		if(isset($dialog['field1']['deleted'])) {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
			query($sql);
			_historyInsert(3, $dialog, $unit_id);
			_spisokUnitAfter($dialog, $unit_id);
		} else {
			$elem = array();
			if(_baseTable($dialog['table_1']) == '_element') {//если это элемент
				$elem = _elemQuery($unit_id);
				//удаление значений
				$sql = "DELETE FROM `_element` WHERE `block_id`=-".$unit_id;
				query($sql);
				//удаление функций
				$sql = "DELETE FROM `_element_func` WHERE `block_id`=".$elem['block_id'];
				query($sql);
				//удаление фильтров
				$sql = "DELETE FROM `_user_spisok_filter` WHERE `element_id_filter`=".$unit_id;
				query($sql);
				//установка позиции в блоке по умолчанию
				$sql = "UPDATE `_block` SET `pos`='top' WHERE `id`=".$elem['block_id'];
				query($sql);
			}

			$sql = "SELECT * FROM `"._baseTable($dialog['table_1'])."` WHERE `id`=".$unit_id;
			$unit = query_assoc($sql);

			$sql = "DELETE FROM `"._baseTable($dialog['table_1'])."` WHERE `id`=".$unit_id;
			query($sql);

			//обновление кеша объекта, если это элемент
			if($elem)
				_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);

			//обновление кеша объекта, если это страница
			if(_baseTable($dialog['table_1']) == '_page')
				_cache('clear', '_pageCache');

			if(_baseTable($dialog['table_1']) == '_element_func')
				if($BL = _blockQuery($unit['block_id']))
					_cache('clear', $BL['obj_name'].'_'.$BL['obj_id']);
		}

		$send = _spisokAction4($send);

		jsonSuccess($send);
		break;
	case 'spisok_filter_update'://обновление списка после применения фильтра
		if(!$elem_spisok = _num($_POST['elem_spisok']))
			jsonError('Некорректный ID элемента-списка');
		if(!$elSpisok = _elemQuery($elem_spisok))
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
			//получение  сохранённого фильтра для пользователя
			$sql = "SELECT *
					FROM `_user_spisok_filter`
					WHERE `user_id`=".USER_ID."
					  AND `element_id_spisok`=".$elem_spisok."
					  AND `element_id_filter`=".$elem_filter;
			$id = _num(query_value($sql));

			$sql = "INSERT INTO `_user_spisok_filter` (
						`id`,
						`user_id`,
						`element_id_spisok`,
						`element_id_filter`,
						`v`
					) VALUES (
						".$id.",
						".USER_ID.",
						".$elem_spisok.",
						".$elem_filter.",
						'".addslashes(_txt($v))."'
					) ON DUPLICATE KEY UPDATE
						`v`=VALUES(`v`)";
			query($sql);
		}


		_spisokFilter('cache_clear');

		//элемент количества, привязанный к списку
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elCount = query_assoc($sql)) {
			$send['count_attr'] = '#el_'.$elCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($elCount));
		}

		$send['spisok_attr'] = '#el_'.$elem_spisok;
		$send['spisok_html'] = utf8(_spisokShow($elSpisok));
		jsonSuccess($send);
		break;
	case 'spisok_next'://догрузка списка
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');
		//получение данных элемента поиска
		if(!$el = _elemQuery($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 14 && $el['dialog_id'] != 23)
			jsonError('Элемент не является списком');
		if(!$el['block'])
			jsonError('Отсутствует блок списка');

		$send['is_table'] = $el['dialog_id'] == 23;
		$send['spisok'] = utf8(_spisokShow($el, $next));
		jsonSuccess($send);
		break;
	case 'spisok_29_connect':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента диалога');

		$v = _txt($_POST['v']);

		$send['spisok'] = utf8(_spisok29connect($cmp_id, $v));
		jsonSuccess($send);
		break;
	case 'spisok_59_unit':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента');
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный ID выбранного элемента');

		$send['html'] = utf8(_spisok59unit($cmp_id, $unit_id));
		jsonSuccess($send);
		break;
}

function _spisokUnitDialog($unit_id) {//получение данных о диалоге и проверка наличия единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');

	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '"._baseTable($dialog['table_1'])."'";
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

	define('IS_ELEM', _baseTable($dialog['table_1']) == '_element');

	$act = $unit_id ? 'edit' : 'insert';

	$block_id = _num($_POST['block_id'], 1);

	$POST_CMP = _spisokUnitCmpTest($dialog);

	$unit_id = _spisokUnitInsert($unit_id, $dialog, $block_id);


	// ---=== СЕКЦИЯ ОБНОВЛЕНИЯ ДАННЫХ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);

	_spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id);

	//получение обновлённых данных единицы списка
	$sql = "SELECT *
			FROM `"._baseTable($dialog['table_1'])."`
			WHERE `id`=".$unit_id;
	$unit = query_assoc($sql);
	if(IS_ELEM)
		if($bl = _blockQuery($unit['block_id']))
			if($bl['obj_name'] == 'dialog') {
				_cache('clear', '_dialogQuery'.$bl['obj_id']);
				$dlg = _dialogQuery($bl['obj_id']);
				$unit = $dlg['cmp'][$unit['id']];
			}

	$cmpv = @$_POST['cmpv'];
	foreach($dialog['cmp'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//---=== ДЕЙСТВИЯ ПРИ НАСТРОЙКИ ЭЛЕМЕНТОВ ===---
			//конкретная функция
			case 12:
				$funcSave = $cmp['txt_1'].'Save';
				if(!function_exists($funcSave))
					break;
				$funcSave($cmp, $cmpv[$cmp_id], $unit);
				break;
			//наполнение для некоторых компонентов: radio, select, dropdown
			case 19: _cmpV19($cmpv[$cmp_id], $unit); break;
			//Настройка ТАБЛИЧНОГО содержания списка
			case 30: _cmpV30($cmp, $cmpv[$cmp_id], $unit); break;
			case 49: _cmpV49($cmp, $cmpv[$cmp_id], $unit); break;
			//Настройка суммы значений единицы списка
			case 56: _cmpV56($cmp, $cmpv[$cmp_id], $unit); break;
			//количество значений связанного списка
			case 54: /* сделать пересчёт значения */ break;
			//Настройка пунктов меню переключения блоков
			case 58: _cmpV58($cmpv[$cmp_id], $unit); break;
			//Применение загруженных изображений
			case 60: _cmpV60($cmp, $unit); break;
		}

	_spisokUnitUpd27($unit);
	_spisokUnitUpd54($unit);
	_spisokUnitUpd55($unit);

	_spisokUnitAfter($dialog, $unit_id);

	if(_baseTable($dialog['table_1']) == '_page')
		_cache('clear', '_pageCache');

	if(_baseTable($dialog['table_1']) == '_element_func')
		if($BL = _blockQuery($unit['block_id']))
			_cache('clear', $BL['obj_name'].'_'.$BL['obj_id']);

	if(IS_ELEM) {
		$elem = _elemQuery($unit_id);
		if($elem['block'])
			_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);
	}

	if(IS_ELEM)
		$unit['title'] = _elemTitle($unit_id);

	$send = array(
		'unit' => utf8($unit),
		'action_id' => _num($dialog[$act.'_action_id']),
		'action_page_id' => _num($dialog[$act.'_action_page_id'])
	);

	$send = _spisokAction3($send, $dialog, $unit_id, $block_id);
	$send = _spisokAction4($send);

	return $send;
}
function _spisokUnitCmpTest($dialog) {//проверка корректности компонентов диалога
	$POST_CMP = @$_POST['cmp'];
	if($dialog['cmp_no_req'] && empty($POST_CMP))
		return array();
//	if(empty($POST_CMP))
//		jsonError('Нет данных для внесения');
	if(!is_array($POST_CMP))
		jsonError('Компоненты диалога не являются массивом');

	$send = array();
	foreach($POST_CMP as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('Некорректный id компонента диалога');
		if(!$cmp = @$dialog['cmp'][$cmp_id])
			jsonError('Отсутствует компонент id'.$cmp_id.' в диалоге');
		if(!$col = @$cmp['col'])
			continue;
/*			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8('Отсутствует имя колонки в компоненте id'.$cmp_id)
			));
*/
		if(!isset($dialog['field1'][$col]) && !isset($dialog['field2'][$col]))
			jsonError('В таблице отсутствует колонка с именем "'.$col.'"');

		$v = _txt($val);

		if($cmp['req'] && !$v)
			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8($cmp['req_msg'] ? $cmp['req_msg'] : 'Необходимо заполнить поле,<br>либо выбрать значение')
			));

		$ex = explode('_', $col);
		if($ex[0] == 'num')
			$v = _num($v);
		if($ex[0] == 'count')
			$v = _num($v);
		if($ex[0] == 'cena')
			$v = _cena($v);

		$send[$cmp_id] = $v;
	}

	if(!$send)
		jsonError('Нет данных для внесения');

	return $send;
}
function _spisokUnitInsert($unit_id, $dialog, $block_id) {//внесение новой единицы списка, если отсутствует
	if($unit_id > 0)
		return $unit_id;

	$page_id = _num($_POST['page_id']);

	//если производится вставка в блок: проверка, чтобы в блок не попало 2 элемента
	if(IS_ELEM && $block_id > 0 && !$unit_id) {
		if(!$block = _blockQuery($block_id))
			jsonError('Блока не сущетвует');
		if($block['elem'])
			jsonError('В блоке уже есть элемент');
	}

	$sql = "INSERT INTO `"._baseTable($dialog['table_1'])."` (`id`) VALUES (0)";
	query($sql);

	//подмена id блока отрицательным значением для группировки
	if($unit_id < 0)
		$block_id = $unit_id;

	$unit_id = query_insert_id(_baseTable($dialog['table_1']));

	//обновление некоторых колонок таблицы 1
	foreach($dialog['field1'] as $field => $i) {
		if($field == 'app_id') {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `app_id`=".APP_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'dialog_id') {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `dialog_id`=".$dialog['id']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'num') {//установка порядкового номера
			$sql = "SELECT IFNULL(MAX(`num`),0)+1
					FROM `"._baseTable($dialog['table_1'])."`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog['id'];
			$num = query_value($sql);
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `num`=".$num."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'page_id') {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `page_id`=".$page_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'block_id' && $block_id) {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `block_id`=".$block_id."
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
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `sort`="._maxSql(_baseTable($dialog['table_1']))."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'user_id_add') {
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `user_id_add`=".USER_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
	}


	//внесение данных таблицы 2, если есть
	if($dialog['table_2']) {
		$sql = "INSERT INTO `"._baseTable($dialog['table_2'])."` (`".$dialog['table_2_field']."`) VALUES (".$unit_id.")";
		query($sql);

		$unit_2 = query_insert_id(_baseTable($dialog['table_2']));
		foreach($dialog['field2'] as $field => $i) {
			if($field == 'app_id') {
				$sql = "UPDATE `"._baseTable($dialog['table_2'])."`
						SET `app_id`=".APP_ID."
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
	if(_baseTable($dialog['table_1']) != '_element')
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
	if(_baseTable($dialog['table_1']) != '_page')
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
				if($el = _elemQuery($unit_id))
					if($el['block']['obj_name'] == 'dialog')
						if($dlg = _dialogQuery($el['block']['obj_id'])) {

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
		$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
				SET ".implode(',', $update1)."
				WHERE `id`=".$unit_id;
		query($sql);
	}

	if(!empty($update2)) {
		$sql = "SELECT `id`
				FROM `"._baseTable($dialog['table_2'])."`
				WHERE `app_id`=".APP_ID."
				  AND `".$dialog['table_2_field']."`=".$unit_id."
				LIMIT 1";
		if($unit_2 = _num(query_value($sql))) {
			$sql = "UPDATE `"._baseTable($dialog['table_2'])."`
					SET ".implode(',', $update2)."
					WHERE `id`=".$unit_2;
			query($sql);
		}
	}
}
function _spisokAction3($send, $dialog, $unit_id, $block_id=0) {//добавление значений для отправки, если действие 3 - обновление содержания блоков
	if($send['action_id'] != 3)
		return $send;
	if(_baseTable($dialog['table_1']) != '_element')
		return $send;
	if($block_id <= 0)//была вставка доп-значения для элемета
		return $send;

	$elem = _elemQuery($unit_id);

	if($elem['block_id'] < 0)
		return $send;

	$send['block_obj_name'] = $elem['block']['obj_name'];

	switch($elem['block']['obj_name']) {
		default:
		case 'page': $width = 1000; break;
		case 'spisok':
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$elem['block']['obj_id'];
			$bl = query_assoc($sql);

			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=".$bl['id'];
			$el = query_assoc($sql);

			//корректировка ширины с учётом отступов
			$ex = explode(' ', $el['mar']);
			$width = floor(($bl['width'] - $ex[1] - $ex[3]) / 10) * 10;
			break;
		case 'dialog':
			_cache('clear', '_dialogQuery'.$elem['block']['obj_id']);
			$dlg = _dialogQuery($elem['block']['obj_id']);
			$width = $dlg['width'];
			break;
	}
	$send['level'] = utf8(_blockLevelChange($elem['block']['obj_name'], $elem['block']['obj_id'], $width));

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
function _cmpV19($val, $unit) {//наполнение для некоторых компонентов: radio, select, dropdown
	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$content = _txt($r['content']);
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($title)."',
				'".addslashes($content)."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`block_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id` FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
}
function _cmpV30($cmp, $val, $unit) {//сохранение настройки ТАБЛИЧНОГО содержания списка (30)
	/*
		-112
		$cmp  - компонент из диалога, отвечающий за настройку таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий таблицу, для которой происходит настройка
	*/
	if(empty($cmp['col']))
		return;

	//поле, хранящее список id элементов-значений
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `block_id`=-".$unit['id'].",
					`width`="._num($r['width']).",
					`txt_7`='".addslashes(_txt($r['tr']))."',
					`font`='".$r['font']."',
					`color`='".$r['color']."',
					`txt_8`='".$r['pos']."',
					`url`="._num($r['url']).",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//очистка неиспользованных элементов
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-112)";
	query($sql);
}
function _cmpV49($cmp, $val, $unit) {//Настройка содержания Сборного текста
	/*
		-111
		$cmp  - компонент из диалога, отвечающий за настройку таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий таблицу, для которой происходит настройка
	*/
	if(empty($cmp['col']))
		return;

	//поле, хранящее список id элементов-значений
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-".$unit['id'].")
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `block_id`=-".$unit['id'].",
					`num_8`=".$r['spc'].",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//очистка неиспользованных элементов
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-111)";
	query($sql);
}
function _cmpV56($cmp, $val, $unit) {//Настройка суммы значений единицы списка
	/*
		-113
		$cmp  - компонент из диалога, отвечающий за настройку таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий таблицу, для которой происходит настройка
	*/
	if(empty($cmp['col']))
		return;

	//поле, хранящее список id элементов-значений
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-".$unit['id'].")
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `block_id`=-".$unit['id'].",
					`num_8`=".$r['minus'].",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//очистка неиспользованных элементов
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-113)";
	query($sql);
}
function _cmpV58($val, $unit) {//Настройка пунктов меню переключения блоков
	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$blk = _ids($r['blk']);
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($title)."',
				'".$blk."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`block_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id` FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
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
function _filterCheckSetupSave($cmp, $val, $unit) {//сохранение настройки фильтра для галочки. Подключаемая функция [12]
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
function _historySetupSave($cmp, $val, $unit) {//сохранение настройки шаблона истории действий. Подключаемая функция [12]
	/*
		-115
		$cmp  - компонент из диалога, отвечающий за настройку таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий таблицу, для которой происходит настройка
	*/

	$update = array();
	$idsNoDel = '0';

	if(!$dlg_id = $val['dialog_id'])
		return;
	if(!$type_id = $val['type_id'])
		return;

	$val = @$val['val'];
	if(!empty($val) && is_array($val)) {
		$sort = 0;
		foreach($val as $r) {
			$num_1 = _num($r['num_1']);
			$txt_7 = _txt($r['txt_7'], 0, 1);
			$txt_8 = _txt($r['txt_8'], 0, 1);
			if(!$num_1 && !$txt_7 && !$txt_8)
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($txt_7)."',
				'".addslashes($txt_8)."',
				".$sort++.",
				".USER_ID."
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
					`txt_7`,
					`txt_8`,
					`sort`,
					`user_id_add`
				)
				VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`block_id`=VALUES(`block_id`),
					`txt_7`=VALUES(`txt_7`),
					`txt_8`=VALUES(`txt_8`),
					`sort`=VALUES(`sort`)";
		query($sql);
	}

	//очистка неиспользованных элементов
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-115)";
	query($sql);

	//обновление значений главного элемента шаблона
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			ORDER BY `sort`";
	$ids = query_ids($sql);

	$sql = "UPDATE `_element`
			SET `num_1`=".$type_id.",
				`num_2`=".$dlg_id.",
				`txt_1`='".($ids ? $ids : '')."'
			WHERE `id`=".$unit['id'];
	query($sql);

	//обновление активности в истории
	$sql = "UPDATE `_history`
			SET `active`=".($ids ? 1 : 0)."
			WHERE `type_id`=".$type_id."
			  AND `dialog_id`=".$dlg_id;
	query($sql);
}
function _pageUserAccessSave($cmp, $val, $unit) {
	if(!is_array($val))
		return;
	if(!$user_id = @$val['user_id'])
		return;

	$sql = "DELETE FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$user_id;
	query($sql);


	if(!$ids = _ids(@$val['ids'], 1))
		return;

	$upd = array();
	foreach($ids as $page_id)
		$upd[] = "(".APP_ID.",".$user_id.",".$page_id.")";

	$sql = "INSERT INTO `_user_page_access`
				(`app_id`,`user_id`,`page_id`)
			VALUES ".implode(',', $upd);
	query($sql);
}
function _spisokUnitUpd27($unit) {//обновление сумм значений единицы списка (баланс)
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 27)
		return;
	//блок, в котором размещается "баланс"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockQuery($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	//диалог, в котором размещаются значения (данные этого списка будут обновляться)
	if(!$DSrc = _dialogQuery($BL['obj_id']))
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE `"._baseTable($DSrc['table_1'])."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$BL['obj_id'];
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
	$sql = "UPDATE `"._baseTable($DSrc['table_1'])."`
			SET `".$unit['col']."`=".$upd."
			WHERE `dialog_id`=".$BL['obj_id'];
	query($sql);
}
function _spisokUnitUpd54($unit) {//обновление количеств привязанного списка
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 54)
		return;
	if(!$cmp_id = _num($unit['num_1']))//id компонента в диалоге, в котором размещается привязка (количество этих значений будет считаться)
		return;
	if(!$cmp = _elemQuery($cmp_id))
		return;
	if(!$dialog_id = $cmp['block']['obj_id'])//id диалога, в котором размещается привязка
		return;
	if(!$DConn = _dialogQuery($dialog_id))
		return;
	//блок, в котором размещается "количество"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockQuery($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$DSrc = _dialogQuery($BL['obj_id']))//диалог, к которому привязан список (данные этого списка будут обновляться)
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE `"._baseTable($DSrc['table_1'])."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$BL['obj_id'];
	query($sql);

	$sql = "SELECT
				`".$cmp['col']."`,
				COUNT(`id`)
			FROM `"._baseTable($DConn['table_1'])."`
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
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._baseTable($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
	}
}
function _spisokUnitUpd55($unit) {//обновление сумм привязанного списка
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 55)
		return;
	if(!$cmp_id = _num($unit['num_1']))//id компонента в диалоге, в котором размещается привязка (сумма этих значений будет считаться)
		return;
	if(!$cmp = _elemQuery($cmp_id))
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
	$sql = "UPDATE `"._baseTable($DSrc['table_1'])."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$DSrc_id;
	query($sql);

	//получение элемента, который указывает на элемент, сумму значения которого нужно будет считать
	if(!$elem_id = _num($unit['num_2']))
		return;
	if(!$elForSum = _elemQuery($elem_id))
		return;
	if(!$elForSum_id = _num($elForSum['txt_2']))
		return;
	if(!$cmpSum = @$DConn['cmp'][$elForSum_id])
		return;
	if(!$sum_col = $cmpSum['col'])
		return;

	$sql = "SELECT
				`".$cmp['col']."`,
				SUM(`".$sum_col."`)
			FROM `"._baseTable($DConn['table_1'])."`
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
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._baseTable($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
	}
}

function _spisokUnitAfter($dialog, $unit_id) {//выполнение действий после обновления единицы списка
	$sql = "SELECT *
			FROM `"._baseTable($dialog['table_1'])."`
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
						  AND `id` IN (".$cmp['txt_2'].")";
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

				_spisokUnitBalans29($send);
				break;
			//привязанные списки
			case 29:
				_spisokUnitCount29($cmp, $dialog, $unit);        //пересчёт количеств привязаного списка [54]
				$elUpd = _spisokUnitSum29($cmp, $dialog, $unit); //пересчёт cумм привязаного списка [55]
				_spisokUnitBalans29($elUpd);                     //подсчёт балансов после обновления сумм [27]
				break;
		}
}
function _spisokUnitCount29($cmp, $dialog, $unit) {//пересчёт количеств привязаного списка
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

	//получение нового количества для обновления
	$sql = "SELECT COUNT(*)
			FROM `"._baseTable($dialog['table_1'])."`
			WHERE `dialog_id`=".$dialog['id']."
			  AND !`deleted`
			  AND `".$col."`=".$connect_id;
	$count = _num(query_value($sql));

	foreach($arr as $r) {
		if(!$col = $r['col'])
			continue;
		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);
		$sql = "UPDATE `"._baseTable($dlg['table_1'])."`
				SET `".$col."`=".$count."
				WHERE `id`=".$connect_id;
		query($sql);
	}
}
function _spisokUnitSum29($cmp, $dialog, $unit) {//пересчёт сумм привязаного списка после внесения/удаления данных
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
		if(!$el = _elemQuery($r['num_2']))
			continue;
		if($el['dialog_id'] != 11)//ссылка элемент с колонкой суммы указывался через [11]
			continue;
		if(!$el = _elemQuery($el['txt_2']))
			continue;
		if(!$colSumGet = $el['col'])
			continue;

		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		//получение нового количества для обновления
		$sql = "SELECT IFNULL(SUM(`".$colSumGet."`),0)
				FROM `"._baseTable($dialog['table_1'])."`
				WHERE `dialog_id`=".$dialog['id']."
				  AND !`deleted`
				  AND `".$col."`=".$connect_id;
		$sum = query_value($sql);

		$sql = "UPDATE `"._baseTable($dlg['table_1'])."`
				SET `".$colSumSet."`=".$sum."
				WHERE `id`=".$connect_id;
		query($sql);

		$send[] = array(
			'id' => $elem_id,
			'block_id' => $r['block_id'],
			'connect_id' => $connect_id     //id единицы списка, баланс которой будет пересчитан
		);
	}

	return $send;
}
function _spisokUnitBalans29($elUpd) {
	if(empty($elUpd))
		return;

	foreach($elUpd as $el) {
		if(!$bl = _blockQuery($el['block_id']))
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
			$sql = "UPDATE `"._baseTable($dialog['table_1'])."`
					SET `".$cmp['col']."`=".$upd."
					WHERE `id`=".$el['connect_id'];
			query($sql);
		}
	}
}





