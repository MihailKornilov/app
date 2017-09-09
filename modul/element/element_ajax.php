<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'button_submit' => 'Внести',
			'button_cancel' => 'Отмена',
			'width' => 500
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
			'<div id="dialog-w-change"></div>'.
			'<div class="pt10 mb10 ml10 mr10 prel">'.
				'<dl id="dialog-base" class="_sort pad5" val="_dialog_element">'.
					'<div id="label-w-change"></div>'.
					_dialogElementSpisok($dialog_id, 'html_edit').
				'</dl>'.
			'</div>'.

			'<div id="dialog-but" class="pad20 center bg-ffd line-t1">'.
				'<button class="vk green" onclick="_dialogEditElement()">Добавить элемент</button>'.
				'<table class="bs5 mt20">'.
					'<tr><td class="label w175 r">Текст кнопки применения:<td><input type="text" id="button_submit" class="w230" maxlength="100" value="'.$dialog['button_submit'].'" />'.
					'<tr><td class="label r">Текст кнопки отмены:<td><input type="text" id="button_cancel" class="w230" maxlength="100" value="'.$dialog['button_cancel'].'" />'.
				'</table>'.

				'<table class="bs5 mt10'.(SA ? '' : ' dn').'">'.
					'<tr><td class="label w175 r"><div class="red">SA: Таблица в базе:</div>'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					'<tr><td class="label r"><div class="red">SA: App any:</div>'.
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
		$send['head'] = utf8(@$dialog['head']);
		$send['button_submit'] = utf8($dialog['button_submit']);
		$send['button_cancel'] = utf8($dialog['button_cancel']);
		$send['element'] = _dialogElementSpisok($dialog_id, 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'dialog_add'://создание нового диалогового окна
		if(!$head = _txt($_POST['head']))
			jsonError('Не указано название диалога');
		if(!$button_cancel = _txt($_POST['button_cancel']))
			jsonError('Не указан текст кнопки отмены');

		$button_submit = _txt($_POST['button_submit']);
		
		
		_dialogElementUpdate();

		$sql = "INSERT INTO `_dialog` (
					`app_id`,
					`head`,
					`button_submit`,
					`button_cancel`
				) VALUES (
					".APP_ID.",
					'".addslashes($head)."',
					'".addslashes($button_submit)."',
					'".addslashes($button_cancel)."'
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
		if(!$head = _txt($_POST['head']))
			jsonError('Не указано название диалога');
		if(!$width = _num($_POST['width']))
			jsonError('Некорректное значение ширины диалога');
		if($width < 480 || $width > 780)
			jsonError('Установлена недопустимая ширина диалога');
		if(!$label_width = _num($_POST['label_width']))
			jsonError('Некорректное значение ширины label');
		if(!$button_cancel = _txt($_POST['button_cancel']))
			jsonError('Не указан текст кнопки отмены');

		$button_submit = _txt($_POST['button_submit']);
		$base_table = _txt($_POST['base_table']);

		_dialogElementUpdate();

		$sql = "SELECT COUNT(*)
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!query_value($sql))
			jsonError('Диалога не существует');

		$sql = "UPDATE `_dialog`
				SET `head`='".addslashes($head)."',
					`width`=".$width.",
					`label_width`=".$label_width.",
					`button_submit`='".addslashes($button_submit)."',
					`button_cancel`='".addslashes($button_cancel)."',
					`base_table`='".addslashes($base_table)."'
				WHERE `id`=".$dialog_id;
		query($sql);

		_dialogElementUpdate($dialog_id);

		$send['dialog_id'] = $dialog_id;
		jsonSuccess($send);
		break;

	case 'dialog_open_load'://получение данных для диалогового окна
		$dialog_id = _num($_POST['dialog_id']);

		$data = array();
		if($unit_id = _num($_POST['unit_id'])) {
			$sql = "SELECT *
					FROM `_page`
					WHERE `id`=".$unit_id;
			if(!$data = query_assoc($sql))
				jsonError('Записи не существует');
			if($data['deleted'])
				jsonError('Запись была удалена');
			$dialog_id = _num($data['dialog_id']);
		}
		
		if(!$dialog_id)
			jsonError('Некорректный ID диалогового окна');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		$html = '<table class="bs10">'._dialogElementSpisok($dialog_id, 'html', $data).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['head'] = utf8($unit_id ? 'Редактирование записи' : $dialog['head']);
		$send['button_submit'] = utf8($unit_id ? 'Сохранить' : $dialog['button_submit']);
		$send['button_cancel'] = utf8($dialog['button_cancel']);
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
		if(!$id = _num($_POST['unit_id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_page`
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

		_dialogSpisokUpdate($r['dialog_id'], $id);

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
				6 => 'date'
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

		$sql = "INSERT INTO `_dialog_element` (
					`id`,
					`app_id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`require`,
					`hint`,
					`width`,
					`param_txt_1`,
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
					'".addslashes($param_txt_1)."',
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
					`param_txt_1`=VALUES(`param_txt_1`),
					`param_bool_1`=VALUES(`param_bool_1`),
					`param_bool_2`=VALUES(`param_bool_2`),
					`col_name`=VALUES(`col_name`)";
		query($sql);

		if(!$element_id)
			$element_id = query_insert_id('_dialog_element');

		if(empty($r['v']))
			continue;

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

	Элементы и их характеристики
		1: check - bool
		2: select - num
		3: input - text
		4: textarea - text
		5: radio - num
		6: calendar - date
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
			$val = '';

			//установка значения при редактировании данных диалога
			if(!empty($data))
				$val = $data[$r['col_name']];

			$attr_id = 'elem'.$r['id'];
			$width = $r['width'] ? _num($r['width']) : 250;
			$inp = '<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />';

			$html .=
				($edit ?
					'<dd class="over1 curM prel" val="'.$r['id'].'">'.
						'<div class="element-del icon icon-del'._tooltip('Удалить элемент', -53).'</div>'.
						'<div class="element-edit icon icon-edit'._tooltip('Изменить', -29).'</div>'.
						'<table class="bs5">'
				: '').
				'<tr><td class="label r'.($edit ? ' label-width pr5' : '').'" style="width:'.$label_width.'px">'.
						($r['label_name'] ? $r['label_name'].':' : '').
						($r['require'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
						($r['hint'] ? ' <div class="icon icon-hint dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'"></div>' : '').
					'<td>';

			switch($r['type_id']) {
				case 1://check
				case 2://select
				default: break;
				case 3://input
					$inp = '<input type="text" id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px" value="'.$val.'" />';
					break;
				case 4://textarea
					$inp = '<textarea id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px">'.$val.'</textarea>';
					break;
			}

			$html .= $inp.($edit ? '</table></dd>' : '');

			$arr[] = array(
				'id' => _num($r['id']),
				'type_id' => _num($r['type_id']),
				'label_name' => utf8($r['label_name']),
				'require' => _bool($r['require']),
				'hint' => utf8(htmlspecialchars_decode(htmlspecialchars_decode($r['hint']))),
				'width' => $width,
				'param_txt_1' => utf8($r['param_txt_1']),
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
		
		foreach($arr as $n => $r)
			if(isset($element_v[$r['id']]))
				$arr[$n]['v'] = $element_v[$r['id']];
	}

	if($i == 'arr')
		return $arr;

	return $html;
}

function _dialogSpisokUpdate($dialog_id, $spisok_id=0, $page_id=0) {//внесение/редактирование записи списка
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$dialog_id;
	if(!$dialog = query_assoc($sql))
		jsonError('Диалога не существует');

	//установка таблицы для внесения данных
	$baseTable = '_spisok';
	if(!empty($dialog['base_table'])) {
		$baseTable = $dialog['base_table'];
		$sql = "SHOW TABLES LIKE '".$baseTable."'";
		if(!mysql_num_rows(query($sql)))
			jsonError('Таблицы не существует');
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
		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('Не заполнено поле <b>'.$r['label_name'].'</b>');

		$elemUpdate[] = "`".$r['col_name']."`='".addslashes($v)."'";
	}

	if(!$spisok_id) {
		$sql = "INSERT INTO `".$baseTable."` (
					`app_id`,
					`dialog_id`,
					`page_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$dialog_id.",
					".$page_id.",
					".VIEWER_ID."
				)";
		query($sql);
		$spisok_id = query_insert_id($baseTable);
	}

	$sql = "UPDATE `".$baseTable."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$spisok_id;
	query($sql);

	return $spisok_id;
}

