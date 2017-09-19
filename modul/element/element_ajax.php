<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'width' => 500,

			'head_insert' => 'Внесение новой записи',
			'button_insert_submit' => 'Внести',
			'button_insert_cancel' => 'Отмена',

			'head_edit' => 'Сохранение записи',
			'button_edit_submit' => 'Сохранить',
			'button_edit_cancel' => 'Отмена',

			'menu_edit_last' => 1
		);

		if($dialog_id = _num($_POST['dialog_id'])) {
			$sql = "SELECT *
					FROM `_dialog`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$dialog_id;
			if($ass = query_assoc($sql))
				$dialog = $ass;
			else
				$dialog_id = 0;
		}

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1">'.
				'<div class="pad10">'.
					'<div class="hd2">Внесение новой записи</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Заголовок:'.
							'<td><input type="text" id="head_insert" class="w250" maxlength="200" placeholder="название диалогового окна - новая запись" value="'.$dialog['head_insert'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>внесения</b>:<td><input type="text" id="button_insert_submit" class="w150" maxlength="100" value="'.$dialog['button_insert_submit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>отмены</b>:<td><input type="text" id="button_insert_cancel" class="w150" maxlength="100" value="'.$dialog['button_insert_cancel'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-ffd line-t1 pad10">'.
					'<div class="hd2">Редактирование записи</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Заголовок:'.
							'<td><input type="text" id="head_edit" class="w250" maxlength="200" placeholder="название диалогового окна - редактирование" value="'.$dialog['head_edit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>сохранения</b>:<td><input type="text" id="button_edit_submit" class="w150" maxlength="100" value="'.$dialog['button_edit_submit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>отмены</b>:<td><input type="text" id="button_edit_cancel" class="w150" maxlength="100" value="'.$dialog['button_edit_cancel'].'" />'.
					'</table>'.
				'</div>'.
			'</div>'.

			//Элементы
			'<div class="dialog-menu-2">'.
				'<div class="pt10 mb10 ml10 mr10 prel">'.
					'<dl id="dialog-base" class="_sort pad5" val="_dialog_element">'.
						'<div id="label-w-change"></div>'.
						_dialogElementSpisok($dialog_id, 'html_edit').
					'</dl>'.
				'</div>'.
				'<div class="pad20 center bg-ffd line-t1">'.
					'<button class="vk green" onclick="_dialogEditElement()">Добавить элемент</button>'.
				'</div>'.
			'</div>'.

			//Связки
//			'<div class="dialog-menu-3">Связки в раздумке</div>'.

			//SA
			'<div class="dialog-menu-9 pt20 pb20">'.
				'<table class="bs10">'.
					'<tr><td class="label w175 r"><div class="red">Таблица в базе:</div>'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					'<tr><td class="label r"><div class="red">App any:</div>'.
						'<td>'._check(array(
								'id' => 'app_any',
								'block' => 1,
								'value' => _bool($dialog['app_any'])
							   )).
				'</table>'.
			'</div>';

		$send['dialog_id'] = $dialog_id;
		$send['width'] = $dialog_id ? _num($dialog['width']) : 500;
		$send['label_width'] = $dialog_id ? _num($dialog['label_width']) : 125;
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['element'] = _dialogElementSpisok($dialog_id, 'arr');
		$send['table'] = _selArray(_globalTable());
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'dialog_add'://создание нового диалогового окна
		if(!$head_insert = _txt($_POST['head_insert']))
			jsonError('Не указано название диалога для новой записи');
		if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
			jsonError('Не указан текст кнопки внесения');
		if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
			jsonError('Не указан текст кнопки отмены для новой записи');

		if(!$head_edit = _txt($_POST['head_edit']))
			jsonError('Не указано название диалога редактирования');
		if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
			jsonError('Не указан текст кнопки сохранения');
		if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
			jsonError('Не указан текст кнопки отмены редактирования');

		$menu_edit_last = _num($_POST['menu_edit_last']);

		_dialogElementUpdate();

		$sql = "INSERT INTO `_dialog` (
					`app_id`,
					`head_insert`,
					`button_insert_submit`,
					`button_insert_cancel`,
					`head_edit`,
					`button_edit_submit`,
					`button_edit_cancel`,
					`menu_edit_last`
				) VALUES (
					".APP_ID.",
					'".addslashes($head_insert)."',
					'".addslashes($button_insert_submit)."',
					'".addslashes($button_insert_cancel)."',
					'".addslashes($head_edit)."',
					'".addslashes($button_edit_submit)."',
					'".addslashes($button_edit_cancel)."',
					".$menu_edit_last."
				)";
		query($sql);

		$dialog_id = query_insert_id('_dialog');

		_dialogElementUpdate($dialog_id);

		$send['dialog_id'] = $dialog_id;
		jsonSuccess($send);
		break;
	case 'dialog_edit'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		if(!$head_insert = _txt($_POST['head_insert']))
			jsonError('Не указано название диалога для новой записи');
		if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
			jsonError('Не указан текст кнопки внесения');
		if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
			jsonError('Не указан текст кнопки отмены для новой записи');

		if(!$head_edit = _txt($_POST['head_edit']))
			jsonError('Не указано название диалога редактирования');
		if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
			jsonError('Не указан текст кнопки сохранения');
		if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
			jsonError('Не указан текст кнопки отмены редактирования');

		if(!$width = _num($_POST['width']))
			jsonError('Некорректное значение ширины диалога');
		if($width < 480 || $width > 780)
			jsonError('Установлена недопустимая ширина диалога');
		if(!$label_width = _num($_POST['label_width']))
			jsonError('Некорректное значение ширины label');

		$base_table = _txt($_POST['base_table']);
		$menu_edit_last = _num($_POST['menu_edit_last']);

		_dialogElementUpdate();

		$sql = "SELECT COUNT(*)
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!query_value($sql))
			jsonError('Диалога не существует');

		$sql = "UPDATE `_dialog`
				SET `width`=".$width.",
					`label_width`=".$label_width.",
					`head_insert`='".addslashes($head_insert)."',
					`button_insert_submit`='".addslashes($button_insert_submit)."',
					`button_insert_cancel`='".addslashes($button_insert_cancel)."',
					`head_edit`='".addslashes($head_edit)."',
					`button_edit_submit`='".addslashes($button_edit_submit)."',
					`button_edit_cancel`='".addslashes($button_edit_cancel)."',
					`base_table`='".addslashes($base_table)."',
					`menu_edit_last`=".$menu_edit_last."
				WHERE `id`=".$dialog_id;
		query($sql);

		_dialogElementUpdate($dialog_id);

		$send['dialog_id'] = $dialog_id;
		jsonSuccess($send);
		break;

	case 'dialog_table_col_load'://получение списка колонок конкретной таблицы
		if(!$table_id = _num($_POST['table_id']))
			jsonError('Некорректный ID таблицы');

		$tab = _globalTable();
		if(!isset($tab[$table_id]))
			jsonError('Таблицы не существует');

		$sql = "SELECT
					`id`,
					`label_name`
				FROM `_dialog_element`
				WHERE `dialog_id`=".$table_id."
				ORDER BY `sort`";
		$send['spisok'] = query_selArray($sql);

		jsonSuccess($send);
		break;

	case 'dialog_open_load'://получение данных для диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');

		$data = array();
		if(($unit_id = _num($_POST['unit_id'])) && $dialog['base_table']) {
			$sql = "SELECT *
					FROM `".$dialog['base_table']."`
					WHERE `id`=".$unit_id;
			if(!$data = query_assoc($sql))
				jsonError('Записи не существует');
			if($data['deleted'])
				jsonError('Запись была удалена');
		}

		//8:связка
		if($unit_id_dub = _num(@$_POST['unit_id_dub'])) {
			foreach($dialog['element'] as $r)
				if($r['type_id'] == 8)
					$data[$r['col_name']] = $unit_id_dub;
		}

		$html = '<table class="bs10 w100p">'._dialogElementSpisok($dialog_id, 'html', $data).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['element'] = _dialogElementSpisok($dialog_id, 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;

	case 'spisok_add'://внесение данных диалога в _spisok
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		$page_id = _num($_POST['page_id']);
		
		_dialogSpisokUpdate($dialog_id, 0, $page_id);

		jsonSuccess();
		break;
	case 'spisok_edit_load'://получение данных записи для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись была удалена');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$r['dialog_id'];
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		$html = '<table class="bs10">'._dialogElementSpisok($r['dialog_id'], 'html', $r).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['element'] = _dialogElementSpisok($r['dialog_id'], 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'spisok_edit'://сохранение данных записи для диалога
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный идентификатор');

		_dialogSpisokUpdate($dialog_id, $unit_id);

		jsonSuccess();
		break;
	case 'spisok_del'://удаление записи из _spisok
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись уже была удалена');

		$sql = "UPDATE `_spisok`
				SET `deleted`=1
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
}



function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$dialog_id;
	$r = query_assoc($sql);

	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `dialog_id`=".$dialog_id;
	$r['element'] = query_arr($sql);

/* пока отменено
	//конкретная колонка, используемая в таблице
	//берётся из `base_table`
	//например: _page:razdel
	$ex = explode(':', $r['base_table']);
	$r['col'] = @$ex[1];
	$r['base_table'] = $ex[0];
*/
	return $r;
}
function _dialogElementUpdate($dialog_id=0) {//проверка/внесение элементов диалога
	if(!$arr = @$_POST['element'])
		jsonError('Отсутствуют элементы диалога');
	if(!is_array($arr))
		jsonError('Некорректный массив элементов диалога');

	foreach($arr as $r) {
		if(!$type_id = _num($r['type_id']))
			jsonError('Некорректный тип элемента');
		if($type_id == 5 && empty($r['v']))
			jsonError('Отсутствуют значения элемента Radio');
	}

	//первый запуск - тестирование
	if(!$dialog_id)
		return;

	//удаление удалённых элементов
	$sql = "DELETE FROM `_dialog_element`
			WHERE `dialog_id`=".$dialog_id."
			  AND `id` NOT IN ("._idsGet($arr).")";
	query($sql);

	//удаление значений удалённых элементов
	$sql = "DELETE FROM `_dialog_element_v`
			WHERE `dialog_id`=".$dialog_id."
			  AND `element_id` NOT IN ("._idsGet($arr).")";
	query($sql);

	$sort = 0;
	foreach($arr as $r) {
		$element_id = _num($r['id']);
		$type_id = _num($r['type_id']);
		$col_name = _txt($r['col_name']);

		if(!$element_id && !$col_name) {
			//формирование названия поля на основании типа элемента
			$pole = array(
				1 => 'bool',
				2 => 'num',
				3 => 'txt',
				4 => 'txt',
				5 => 'num',
				6 => 'date',
				7 => 'txt',
				8 => 'num'
			);
			$n = 1;
			$sql = "SELECT `col_name`,1
					FROM `_dialog_element`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog_id."
					  AND `col_name` LIKE '".$pole[$type_id]."_%'";
			$ass = query_ass($sql);
			for($n = 1; $n <= 5; $n++) {
				$col_name = $pole[$type_id].'_'.$n;
				if(!isset($ass[$col_name]))
					break;
			}
		}

		$label_name = _txt($r['label_name']);
		$require = _bool($r['require']);
		$hint = _txt($r['hint']);
		$width = _num($r['width']);
		$param_txt_1 = _txt($r['param_txt_1']);
		$param_txt_2 = _txt($r['param_txt_2']);

		$sql = "INSERT INTO `_dialog_element` (
					`id`,
					`app_id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`require`,
					`hint`,
					`width`,
					`param_num_1`,
					`param_num_2`,
					`param_txt_1`,
					`param_txt_2`,
					`param_bool_1`,
					`param_bool_2`,
					`col_name`,
					`sort`
				) VALUES (
					".$element_id.",
					".APP_ID.",
					".$dialog_id.",
					".$type_id.",
					'".addslashes($label_name)."',
					".$require.",
					'".addslashes($hint)."',
					".$width.",
					"._num($r['param_num_1']).",
					"._num($r['param_num_2']).",
					'".addslashes($param_txt_1)."',
					'".addslashes($param_txt_2)."',
					"._bool($r['param_bool_1']).",
					"._bool($r['param_bool_2']).",
					'".$col_name."',
					".($sort++)."
				)
				ON DUPLICATE KEY UPDATE
					`label_name`=VALUES(`label_name`),
					`require`=VALUES(`require`),
					`hint`=VALUES(`hint`),
					`width`=VALUES(`width`),
					`param_num_1`=VALUES(`param_num_1`),
					`param_num_2`=VALUES(`param_num_2`),
					`param_txt_1`=VALUES(`param_txt_1`),
					`param_txt_2`=VALUES(`param_txt_2`),
					`param_bool_1`=VALUES(`param_bool_1`),
					`param_bool_2`=VALUES(`param_bool_2`),
					`col_name`=VALUES(`col_name`)";
		query($sql);

		if(!$element_id)
			$element_id = query_insert_id('_dialog_element');

		//удаление всех элементов, если были
		if(empty($r['v'])) {
			$sql = "DELETE FROM `_dialog_element_v`
					WHERE `element_id`=".$element_id;
			query($sql);
			continue;
		}

		//удаление удалённых значений элемента
		if($ids = _idsGet($r['v'])) {
			$sql = "DELETE FROM `_dialog_element_v`
					WHERE `element_id`=".$element_id."
					  AND `id` NOT IN (".$ids.")";
			query($sql);
		}

		//внесение дополнительных значений элемента
		$sort_v = 0;
		foreach($r['v'] as $v) {
			$sql = "INSERT INTO `_dialog_element_v` (
						`id`,
						`app_id`,
						`dialog_id`,
						`element_id`,
						`v`,
						`sort`
					) VALUES (
						"._num(@$v['id']).",
						".APP_ID.",
						".$dialog_id.",
						".$element_id.",
						'".addslashes(_txt($v['title']))."',
						".($sort_v++)."
					)
					ON DUPLICATE KEY UPDATE
						`v`=VALUES(`v`)";
			query($sql);
		}
	}
}
function _dialogElementSpisok($dialog_id, $i, $data=array()) {//список элементов диалога в формате массива и html
/*
	Форматы возврата данных:
		arr
		html
		html_edit

	Элементы и их характеристики - порядок отображения колонки в таблице, например: txt_1, num_2
		1. check:    bool
		2. select:   num
		3. input:    txt
		4. textarea: txt
		5. radio:    num
		6. calendar: date
		7. info:     txt
		8. info:     num
*/

	$arr = array();
	$html = '';
	$edit = $i == 'html_edit';//редактирование + сортировка элементов

	$sql = "SELECT `label_width`
			FROM `_dialog`
			WHERE `id`=".$dialog_id;
	$label_width = _num(query_value($sql));

	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	if($spisok = query_arr($sql)) {
		foreach($spisok as $r) {
			$type_id = _num($r['type_id']);
			
			if($type_id == 7)
				continue;

			$val = '';

			//установка значения при редактировании данных диалога
			if(!empty($data))
				$val = @$data[$r['col_name']];

			$attr_id = 'elem'.$r['id'];
			$width = $r['width'] ? _num($r['width']) : 250;
			$inp = '<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />';

			$html .=
				($edit ?
					'<dd class="over1 curM prel" val="'.$r['id'].'">'.
						'<div class="element-del icon icon-del'._tooltip('Удалить элемент', -53).'</div>'.
						'<div class="element-edit icon icon-edit'._tooltip('Изменить', -29).'</div>'.
						'<table class="bs5 w100p">'
				: '').
				'<tr><td class="label r'.($edit ? ' label-width pr5' : '').'" '.($type_id == 7 ? 'colspan="2"' : 'style="width:'.$label_width.'px"').'>'.
						($r['label_name'] ? $r['label_name'].':' : '').
						($r['require'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
						($r['hint'] ? ' <div class="icon icon-hint dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'"></div>' : '').
				($type_id != 7 ?//если информация, то показ на всю шишину
					'<td>'
				: '');

			switch($type_id) {
				case 1://check
				case 2://select
				default: break;
				case 3://input
					$inp = '<input type="text" id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px" value="'.$val.'" />';
					break;
				case 4://textarea
					$inp = '<textarea id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px">'.$val.'</textarea>';
					break;
				case 7://info
					$inp = '<div id="'.$attr_id.'" class="_info">'._br(htmlspecialchars_decode($r['param_txt_1'])).'</div>';
					break;
				case 8://connect
//					$baseTable = _globalTable('table', $r['param_num_1']);

					//получение названия таблицы для связки из диалога
					$sql = "SELECT `base_table`
							FROM `_dialog`
							WHERE `id`=".$r['param_num_1'];
					$baseTable = query_value($sql);

					//получение названия колонки для связки
					$sql = "SELECT `col_name`
							FROM `_dialog_element`
							WHERE `id`=".$r['param_num_2'];
					$colName = query_value($sql);

					//получение значения колонки
					$sql = "SELECT `".$colName."`
							FROM `".$baseTable."`
							WHERE `id`=1";
					$colVal = query_value($sql);

					$inp .= '<b>'.$colVal.'</b>';
					break;
			}

			$html .= $inp.($edit ? '</table></dd>' : '');

			$arr[] = array(
				'id' => _num($r['id']),
				'type_id' => _num($type_id),
				'label_name' => utf8($r['label_name']),
				'require' => _bool($r['require']),
				'hint' => utf8(htmlspecialchars_decode(htmlspecialchars_decode($r['hint']))),
				'width' => $width,
				'param_num_1' => _num($r['param_num_1']),
				'param_num_2' => _num($r['param_num_2']),
				'param_txt_1' => utf8($r['param_txt_1']),
				'param_txt_2' => utf8($r['param_txt_2']),
				'param_bool_1' => _bool($r['param_bool_1']),
				'param_bool_2' => _bool($r['param_bool_2']),

				'col_name' => $r['col_name'],

				'attr_id' => '#'.$attr_id,

				'v' => array()
			);
		}

		$sql = "SELECT *
				FROM `_dialog_element_v`
				WHERE `element_id` IN ("._idsGet($spisok).")
				ORDER BY `sort`";
		$element_v = array();
		if($spisok = query_arr($sql)) {
			foreach($spisok as $r) {
				$element_v[$r['element_id']][] = array(
					'id' => _num($r['id']),
					'uid' => _num($r['id']),
					'title' => utf8($r['v'])
				);
			}
		}
		
		foreach($arr as $n => $r) {
			if(isset($element_v[$r['id']]))
				$arr[$n]['v'] = $element_v[$r['id']];
			if($r['type_id'] == 2 && $r['param_txt_2'] == 1)
				$arr[$n]['v'] = _dialogSpisokList();
		}
	}

	if($i == 'arr')
		return $arr;

	return $html;
}

function _dialogSpisokList() {//массив списков (пока только для select) (пока только список страниц)
	$sql = "SELECT `id`,`name`
			FROM `_page`
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogSpisokUpdate($dialog_id, $unit_id=0, $page_id=0) {//внесение/редактирование записи списка
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	//установка таблицы для внесения данных
	$baseTable = '_spisok';
	if(!empty($dialog['base_table'])) {
		$baseTable = $dialog['base_table'];
		$sql = "SHOW TABLES LIKE '".$baseTable."'";
		if(!mysql_num_rows(query($sql)))
			jsonError('Таблицы не существует');
	}

	if($unit_id) {
		$sql = "SELECT *
				FROM `".$baseTable."`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$unit_id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись была удалена');
	}

	//проверка на корректность данных элементов диалога
	$elem = $_POST['elem'];
	if(!is_array($elem))
		jsonError('Некорректный формат данных');
	if(empty($elem))
		jsonError('Нет данных для внесения');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('Некорректный идентификатор поля');

	//получение информации об элементах и составление списка для внесения в таблицу
	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$dialog_id;
	$de = query_arr($sql);

	$elemUpdate = array();
	foreach($de as $id => $r) {
		if($r['type_id'] == 7)//info
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('Не заполнено поле <b>'.$r['label_name'].'</b>');

		$elemUpdate[] = "`".$r['col_name']."`='".addslashes($v)."'";
	}

	if(!$unit_id) {
		$sql = "INSERT INTO `".$baseTable."` (
					`app_id`,
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($baseTable);

		//обновление page_id, если есть
		$sql = "DESCRIBE `".$baseTable."`";
		$desc = query_array($sql);
		foreach($desc as $r)
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".$baseTable."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				break;
			}
	}

	$sql = "UPDATE `".$baseTable."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	return $unit_id;
}



























/*
function _dialogSpisokColUpdate($dialog, $unit_id, $elem, $de) {//внесение/редактирование данных конкретного поля таблицы
	$elemUpdate = array();
	foreach($de as $id => $r) {
		if($r['type_id'] == 7)//info
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('Не заполнено поле <b>'.$r['label_name'].'</b>');

		$elemUpdate[$r['col_name']] = utf8($v);
	}

	$id = 1;

	$sql = "SELECT `".$dialog['col']."`
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	if($col = query_value($sql)) {
		$col = json_decode(utf8($col), true);

		//получение максимального ID
		foreach($col as $r)
			if($id <= $r['id'])
				$id = ++$r['id'];
	}

	$elemUpdate['id'] = $id;

	$col[] = $elemUpdate;

	$sql = "UPDATE `".$dialog['base_table']."`
			SET `".$dialog['col']."`='".addslashes(win1251(json_encode($col)))."'
			WHERE `id`=".$unit_id;
	query($sql);

	return $unit_id;
}
*/