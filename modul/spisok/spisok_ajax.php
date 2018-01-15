<?php
switch(@$_POST['op']) {
	case 'spisok_add'://внесение единицы списка из диалога
		$send = _spisokUnitUpdate();
		jsonSuccess($send);
		break;
	case 'spisok_save'://сохранение данных единицы списка для диалога
		if(!$unit_id = _num($_POST['unit_id']))
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

		if(isset($dialog['field']['deleted']))
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
		else
			$sql = "DELETE FROM `".$dialog['base_table']."` WHERE `id`=".$unit_id;
		query($sql);

		jsonSuccess($send);
		break;
	case 'spisok_next'://догрузка списка
		if(!$pe_id = _num($_POST['pe_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$pe_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$pe_id.' не существует');

		if($pe['dialog_id'] != 14 && $pe['dialog_id'] != 23)
			jsonError('Элемент не является списком');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$pe['block_id'];
		if(!$pe['block'] = query_assoc($sql))
			jsonError('Отсутствует блок списка');

		$send['is_table'] = $pe['dialog_id'] == 23;
		$send['spisok'] = utf8(_spisokShow($pe, $next));
		jsonSuccess($send);
		break;
	case 'spisok_search'://получение обновлённого списка по условиям
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');

		$v = _txt($_POST['v']);

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$elem_id.' не существует');

		//сохранение строки поиска
		$sql = "UPDATE `_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$elem_id;
		query($sql);

		//id диалога списка, на который происходит воздействие через поиск
		if(!$pe_id = _num($pe['num_1']))
			jsonError('Не указан список, по которому нужно производить поиск');

		//расположение списка на странице, на которой расположен поиск
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id` IN (14,23)
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('Нет нужного списка на странице');

		//получение данных блока, в котором расположен элемент-список
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$peSpisok['block_id'];
		if(!$peSpisok['block'] = query_assoc($sql))
			jsonError('Блока не существует');

		//элемент количества списка на странице, на которой расположен поиск
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `page_id`=".$pe['page_id']."
				  AND `num_1`=".$pe_id."
				LIMIT 1";
		if($peCount = query_assoc($sql)) {
			$send['count_attr'] = '#pe_'.$peCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($peCount));
		}

		$send['spisok_attr'] = '#pe_'.$peSpisok['id'];
		$send['spisok_html'] = utf8(_spisokShow($peSpisok));

		jsonSuccess($send);
		break;
	case 'spisok_connect_29':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента диалога');

		$v = _txt($_POST['v']);

		$send['spisok'] = _spisokConnect($cmp_id, $v);
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
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	//получение данных единицы списка, если редактируется
	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".$dialog['base_table']."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');
		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	return $dialog;
}
function _spisokUnitUpdate($unit_id=0) {//внесение/редактирование единицы списка
	$dialog = _spisokUnitDialog($unit_id);
	$dialog_id = $dialog['id'];

	$act = $unit_id ? 'edit' : 'insert';

	$page_id = _num($_POST['page_id']);
	$block_id = _num($_POST['block_id'], 1);

	//данные компонентов диалога
	if(!$postCmp = @$_POST['cmp'])
		jsonError('Нет данных для внесения');
	if(!is_array($postCmp))
		jsonError('Компоненты диалога не являются массивом');

	$cmpUpdate = array();
	$elementFocusClear = false;//если в таблице присутствует колонка `focus`, то предварительное снятие флага фокуса с других элементов объекта (для таблицы _element)
	$pageDefClear = false; //для таблицы _page: очистка `def`, если устанавливается новая страница по умолчанию
	foreach($postCmp as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('Некорректный id компонента диалога');
		if(!$cmp = @$dialog['cmp'][$cmp_id])
			jsonError('Отсутствует компонент id'.$cmp_id.' в диалоге');
		if(!$col = @$cmp['col'])
			jsonError('Отсутствует имя колонки в компоненте id'.$cmp_id);
		if(!isset($dialog['field'][$col]))
			jsonError('В таблице <b>'.$dialog['base_table'].'</b> нет колонки с именем "'.$col.'"');
		if($cmp['dialog_id'] == 19) {//наполнение для некоторых компонентов: radio, select, dropdown
			_dialogCmpValue($val, 'test');
			continue;
		}

		$v = _txt($val);

		if($cmp['req'] && !$v)
			jsonError('Требуется обязательно заполнить<br>поля, отмеченные звёздочкой');

		if($dialog['base_table'] == '_element' && $col == 'focus' && $v)
			$elementFocusClear = true;

		if($dialog['base_table'] == '_page' && $col == 'def' && $v)
			$pageDefClear = true;

		$cmpUpdate[] = "`".$col."`='".addslashes($v)."'";
	}

	if(!$unit_id) {
		//если производится вставка в блок: проверка, чтобы в блок не попало 2 элемента
		if($dialog['base_table'] == '_element' && $block_id > 0) {
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block_id;
			if(!$block = query_assoc($sql))
				jsonError('Блока не сущетвует');

			$sql = "SELECT COUNT(*)
					FROM `_element`
					WHERE `block_id`=".$block_id;
			if(query_value($sql))
				jsonError('В блоке уже есть элемент');
		}

		$sql = "INSERT INTO `".$dialog['base_table']."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($dialog['base_table']);

		//обновление некоторых колонок
		$sql = "DESCRIBE `".$dialog['base_table']."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".$dialog['base_table']."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'block_id' && $block_id && $dialog['base_table'] == '_element') {
				$sql = "UPDATE `_element`
						SET `block_id`=".$block_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'width' && $dialog['base_table'] == '_element') {
				$sql = "UPDATE `_element`
						SET `width`=".$dialog['element_width']."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'sort') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `sort`="._maxSql($dialog['base_table'])."
						WHERE `id`=".$unit_id;
				query($sql);
			}
		}
	}

	//снятие флага фокуса со всех элементов объекта
	if($elementFocusClear) {
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
		if($block_ids = query_ids($sql)) {
			$sql = "UPDATE `_element`
					SET `focus`=0
					WHERE `block_id` IN (".$block_ids.")";
			query($sql);
		}
	}

	//снятие флага 'страница по умолчанию' со всех страниц приложения
	if($pageDefClear) {
		$sql = "UPDATE `_page`
				SET `def`=0
				WHERE `app_id`=".APP_ID."
				  AND !`sa`";
		query($sql);
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $cmpUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//обновление значений компонентов
	foreach($postCmp as $cmp_id => $val) {
		$cmp = @$dialog['cmp'][$cmp_id];
		if($cmp['dialog_id'] == 19) {//наполнение для некоторых компонентов: radio, select, dropdown
			_dialogCmpValue($val, 'save', $dialog_id, $unit_id);
			continue;
		}
	}

	//получение данных единицы списка
	$sql = "SELECT *
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	$unit = query_assoc_utf8($sql);

	if($cmpv = @$_POST['cmpv'])
		foreach($dialog['cmp'] as $cmp_id => $cmp) {
			if(!isset($cmpv[$cmp_id]))
				continue;
			switch($cmp['dialog_id']) {
				//Настройка ТАБЛИЧНОГО содержания списка
				case 30: _spisokTableValueSave($cmp, $cmpv[$cmp_id], $unit); break;
			}
		}


	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');
	if($dialog['base_table'] == '_element')
		_cache('clear', '_dialogQuery'.$dialog_id);

	$send = array(
		'unit' => $unit,
		'action_id' => _num($dialog[$act.'_action_id']),
		'action_page_id' => _num($dialog[$act.'_action_page_id'])
	);

	$send = _spisokAction3($send, $dialog, $unit_id);

	return $send;
}
function _spisokAction3($send, $dialog, $unit_id) {//добавление значений для отправки, если действие 3 - обновление содержания блоков
	if($send['action_id'] != 3)
		return $send;
	if($dialog['base_table'] != '_element')
		return $send;

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$unit_id;
	$elem = query_assoc($sql);

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$elem['block_id'];
	$block = query_assoc($sql);

	$send['block_obj_name'] = $block['obj_name'];

	switch($block['obj_name']) {
		default:
		case 'page': $width = 1000; break;
		case 'spisok':
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block['obj_id'];
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
			_cache('clear', '_dialogQuery'.$block['obj_id']);
			$dlg = _dialogQuery($block['obj_id']);
			$width = $dlg['width'];
			break;
	}
	$send['level'] = utf8(_blockLevelChange($block['obj_name'], $block['obj_id'], $width));

	return $send;
}
function _spisokTableValueSave(//сохранение настройки ТАБЛИЧНОГО содержания списка (30)
	$cmp,//компонент из диалога, отвечающий за настройку таблицы
	$val,//значения, полученные для сохранения
	$unit//элемент, размещающий таблицу, для которой происходит настройка
) {
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
				SET `width`="._num($r['width']).",
					`txt_1`='".addslashes(_txt($r['tr']))."',
					`font`='".$r['font']."',
					`color`='".$r['color']."',
					`txt_6`='".$r['pos']."',
					`num_2`="._num($r['link']).",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}
}

/*
function _spisokUnitUpdate($unit_id=0, $page_id=0, $block_id=0) {//внесение/редактирование единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');

	$send = array(
		'unit_id' => $unit_id,
		'dialog' => $dialog
	);
	
	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".$dialog['base_table']."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	//удаление элемента со страницы
	if($dialog_id == 6) {
		$sql = "DELETE FROM `_element` WHERE `id`=".$unit_id;
		query($sql);
		return $send;
	}

	//проверка на корректность данных компонентах диалога
	if(!$elem = @$_POST['elem'])
		jsonError('Нет данных для внесения');
	if(!is_array($elem))
		jsonError('Некорректный формат данных');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('Некорректный идентификатор поля');

	$elemUpdate = array();
	foreach($dialog['component'] as $id => $r) {
		if(!_dialogEl($r['type_id'], 'func'))
			continue;

		$v = _txt($elem[$id]);

		if($r['req'] && empty($v))
			jsonError(array(
				'delem_id' => $id,
				'text' => utf8('Не заполнено поле<br><b>'.$r['label_name'].'</b>')
			));

		//если это выпадающий список, выбирающий связку и вносит в список элементов
//		if($r['type_id'] == 2 && $dialog['base_table'] == '_element' && $r['num_1'])
//			$elemUpdate[] = "`num_id`=".$r['num_1'];

		//служебная переменная app_any_spisok. Если равна 1, то устанавливает app_id=0 (все приложения), либо = id приложения
		if($r['col_name'] == 'app_any_spisok') {
			$elemUpdate[] = "`app_id`=".($v ? 0 : APP_ID);
			continue;
		}

		$upd = "`".$r['col_name']."`=";
		switch($r['type_id']) {
			case 1: //check
			case 2: //select
			case 5: //radio
				$upd .= _num($v);
				break;
			default://остальные текстовые значения
				if(preg_match('/^num_/', $r['col_name'])) {//если текстовое значение должно быть только числом
					if($v && !preg_match(REGEXP_NUMERIC, $v))
						jsonError(array(
							'delem_id' => $id,
							'text' => utf8('Некорректно заполнено поле <b>'.$r['label_name'].'</b>')
						));
					$upd .= _num($v);
					break;
				}
				$upd .= "'".addslashes($v)."'";
		}
		$elemUpdate[] = $upd;
	}

	if(!$unit_id) {
		$sql = "INSERT INTO `".$dialog['base_table']."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($dialog['base_table']);
		$send['unit_id'] = $unit_id;

		//обновление некоторых колонок
		$sql = "DESCRIBE `".$dialog['base_table']."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".$dialog['base_table']."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'block_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `block_id`=".$block_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'sort') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `sort`="._maxSql($dialog['base_table'])."
						WHERE `id`=".$unit_id;
				query($sql);
			}			
		}
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//обновление функций компонентов
	foreach($dialog['component'] as $id => $r)
		_spisokUnitFuncValUpdate($dialog, $id, $unit_id);

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
*/
