<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'id' => 0,
			'sa' => 0,
			'width' => 500,
			'width_auto' => 0,

			'head_insert' => 'Внесение новой записи',
			'button_insert_submit' => 'Внести',
			'button_insert_cancel' => 'Отмена',

			'head_edit' => 'Сохранение записи',
			'button_edit_submit' => 'Сохранить',
			'button_edit_cancel' => 'Отмена',

			'base_table' => '_spisok',

			'spisok_on' => 0,
			'spisok_name' => '',

			'action_id' => 1,
			'action_page_id' => 0,

			'menu_edit_last' => 1
		);

		if($dialog_id = _num($_POST['dialog_id']))
			if($ass = _dialogQuery($dialog_id))
				$dialog = $ass;
			else
				$dialog_id = 0;

		$menu = array(
			1 => 'Заголовок',
			2 => 'Компоненты',
  		    3 => 'Действие',
//  		4 => 'Функции',
//			5 => 'Отображение полей',
			9 => '<b class="red">SA</b>'
		);

		$action = array(
			3 => 'Обновить содержимое блоков',
			1 => 'Обновить страницу',
			2 => 'Перейти на страницу'
		);

		if(!SA)
			unset($menu[9]);

		if(!isset($menu[$dialog['menu_edit_last']]))
			$dialog['menu_edit_last'] = 1;

		define('BLOCK_EDIT', 1);

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<div class="pad10 center bg-gr3 line-b">'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1'._dn($dialog['menu_edit_last'] == 1).'">'.
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
				'<div class="bg-gr2 line-t1 pad10">'.
					'<div class="hd2">Служебное</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Может быть списком:'.
							'<td><input type="hidden" id="spisok_on" value="'.$dialog['spisok_on'].'" />'.
						'<tr id="tr_spisok_name" class="'.($dialog['spisok_on'] ? '' : 'dn').'">'.
							'<td class="label r">Имя списка:'.
							'<td><input type="text" id="spisok_name" class="w200" maxlength="100" value="'.$dialog['spisok_name'].'" />'.
					'</table>'.
				'</div>'.
			'</div>'.

			//Компоненты
			'<div class="dialog-menu-2'._dn($dialog['menu_edit_last'] == 2).'">'.
				'<div class="pad10 line-b bg-ffc">'.
					_blockLevelChange('dialog', $dialog_id, $dialog['width']).
				'</div>'.
				'<div class="block-content-dialog" style="width:'.$dialog['width'].'px">'.
					_blockHtml('dialog', $dialog_id, $dialog['width']).
				'</div>'.
			'</div>'.

			//Действие
			'<div class="dialog-menu-3 pb20'._dn($dialog['menu_edit_last'] == 3).'">'.
				'<div class="_info mar20">'.
					'Дальнейшее действие, которое происходит после внесения или сохранения записи.'.
				'</div>'.
				'<table class="bs10">'.
					'<tr><td class="label r w100">Действие:'.
						'<td><input type="hidden" id="action_id" value="'.$dialog['action_id'].'" />'.
					'<tr class="td-action-page'._dn($dialog['action_id'] == 2).'">'.
						'<td class="label r">Страница:'.
						'<td><input type="hidden" id="action_page_id" value="'.$dialog['action_page_id'].'" />'.
				'</table>'.
			'</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pt20 pb20'._dn($dialog['menu_edit_last'] == 9).'">'.
				'<table class="bs10">'.
					'<tr><td class="red w175 r">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="red r">Ширина:'.
		                '<td><div id="dialog-width" class="dib w50">'.$dialog['width'].'</div>'.
		                    '<input type="hidden" id="width_auto" value="'.$dialog['width_auto'].'" />'.
					'<tr><td class="red r">Таблица в базе:'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					//доступность диалога. На основании app_id. По умолчанию 0 - недоступен всем.
					'<tr><td class="red r">App any:'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'value' => $dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0
							   )).
					'<tr><td class="red r">SA only:'.
						'<td>'._check(array(
									'attr_id' => 'sa',
									'value' => $dialog['sa']
							   )).
				'</table>'.
			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['menu'] = _selArray($menu);
		$send['block_arr'] = _blockJsArr('dialog', $dialog_id);
		$send['action'] = _selArray($action);
		$send['cmp'] = $dialog['cmp_utf8'];
		$send['html'] = utf8($html);
		$send['sa'] = SA;
		jsonSuccess($send);
		break;
	case 'dialog_save'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		_dialogUpdate($dialog_id);

		$send = _dialogOpenLoad($dialog_id);

		jsonSuccess($send);
		break;
	case 'dialog_open_load'://получение данных диалога
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалога');

		$send = _dialogOpenLoad($dialog_id);

		jsonSuccess($send);
		break;

	case 'page_sort'://сортировка страниц
		$arr = $_POST['arr'];
		if(!is_array($arr))
			jsonError('Не является массивом');

		$update = array();
		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;
			$parent_id = _num($r['parent_id']);
			$update[] = "(".$id.",".$parent_id.",".$n.")";
		}

		if(empty($update))
			jsonError('Нет данных для обновления');

		$sql = "INSERT INTO `_page` (
					`id`,
					`parent_id`,
					`sort`
				) VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`parent_id`=VALUES(`parent_id`),
					`sort`=VALUES(`sort`)";
		query($sql);

		_cache('clear', '_pageCache');

		jsonSuccess();
		break;
}

function _dialogUpdate($dialog_id) {//обновление диалога
	if(!$head_insert = _txt($_POST['head_insert']))
		jsonError('Не указан заголовок для внесения записи');
	if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
		jsonError('Не указан текст кнопки внесения');
	if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
		jsonError('Не указан текст кнопки отмены для новой записи');

	if(!$head_edit = _txt($_POST['head_edit']))
		jsonError('Не указан заголовок редактирования');
	if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
		jsonError('Не указан текст кнопки сохранения');
	if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
		jsonError('Не указан текст кнопки отмены редактирования');

	if(!$width = _num($_POST['width']))
		jsonError('Некорректное значение ширины диалога');
	if($width < 480 || $width > 980)
		jsonError('Установлена недопустимая ширина диалога');

	if(!$base_table = _txt($_POST['base_table']))
		$base_table = '_spisok';

	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_name = _txt($_POST['spisok_name']);
	if($spisok_on && !$spisok_name)
		jsonError('Укажите имя списка страницы');

	$width_auto = _num($_POST['width_auto']);
	$app_any = _num($_POST['app_any']);
	$action_id = _num($_POST['action_id']);
	$action_page_id = _num($_POST['action_page_id']);

	if(!$dialog_id) {
		$sql = "INSERT INTO `_dialog` (
					`app_id`
				) VALUES (
					".APP_ID."
				)";
		query($sql);
		$dialog_id = query_insert_id('_dialog');
	}

	if(!_dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`width`=".$width.",
				`width_auto`=".$width_auto.",

				`head_insert`='".addslashes($head_insert)."',
				`button_insert_submit`='".addslashes($button_insert_submit)."',
				`button_insert_cancel`='".addslashes($button_insert_cancel)."',

				`head_edit`='".addslashes($head_edit)."',
				`button_edit_submit`='".addslashes($button_edit_submit)."',
				`button_edit_cancel`='".addslashes($button_edit_cancel)."',

				`base_table`='".addslashes($base_table)."',
				`spisok_on`=".$spisok_on.",
				`spisok_name`='".addslashes($spisok_name)."',

				`action_id`=".$action_id.",
				`action_page_id`=".$action_page_id.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

//	_dialogComponentUpdate($dialog_id);
//	_dialogFuncUpdate($dialog_id);

	_cache('clear', '_dialogQuery'.$dialog_id);

	return $dialog_id;
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	//получение данных единицы списка
	$unit = array();
	if($unit_id = _num($_POST['unit_id'])) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT *
				FROM `".$dialog['base_table']."`
				WHERE ".$cond;
		if(!$unit = query_assoc($sql))
			jsonError('Записи не существует');
		if(@$unit['sa'] && !SA)
			jsonError('Нет доступа');
		if(@$unit['deleted'])
			jsonError('Запись была удалена');
	}

	$page_id = _num($_POST['page_id']);
	$send['dialog_id'] = $dialog_id;
	$send['unit_id'] = $unit_id;
	$send['block_id'] = _num($_POST['block_id']);

	//исходные данные, полученные для открытия диалога
	$unit['source'] = array(
		'block_id' => $send['block_id']//для какого блока был запрос
	);

	$send['edit_access'] = SA || $dialog['app_id'] == APP_ID ? 1 : 0;//права для редактирования диалога
	$send['width'] = $dialog['width_auto'] ? 0 : _num($dialog['width']);
	$send['head'] = utf8($dialog[!$unit_id ? 'head_insert' : 'head_edit']);
	$send['button_submit'] = utf8($dialog[!$unit_id ? 'button_insert_submit' : 'button_edit_submit']);
	$send['button_cancel'] = utf8($dialog[!$unit_id ? 'button_insert_cancel' : 'button_edit_cancel']);
	$send['html'] = utf8(_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $unit));

	//заполнение значениями некоторых компонентов
	foreach($dialog['cmp_utf8'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//select - выбор списка (все списки приложения)
			case 24:
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _dialogSpisokOn($dialog_id, $send['block_id'], $unit_id);
				break;
			//select - выбор списка, размещённого на текущей странице
			case 27:
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _dialogSpisokOnPage($page_id);
				break;
			//select - выбор единицы из другого списка (для связки)
			case 29:
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _spisokConnect($cmp_id);
				break;

	}

	$send['cmp'] = $dialog['cmp_utf8'];

	foreach($unit as $id => $r)
		$unit[$id] = utf8($r);

	//вставка наполнения для некоторых компонентов
	foreach($dialog['cmp'] as $cmp)
		switch($cmp['dialog_id']) {
			case 19:
				if(!$col = $cmp['col'])
					break;
				$unit[$col] = array();
				if($unit_id) {
					$sql = "SELECT *
							FROM `_element_value`
							WHERE `dialog_id`=".$dialog_id."
							  AND `element_id`=".$unit_id."
							ORDER BY `sort`";
					foreach(query_arr($sql) as $id => $r)
						$unit[$col][_num($id)] = array(
							'id' => _num($id),
							'title' => utf8($r['title']),
							'def' => _num($r['def']),
							'use' => 0  //количество использования значений, чтобы нельзя было удалять
						);

					//если нет значений
					if(empty($unit[$col]))
						break;

					if(empty($unit['col']))
						break;

					//объект, в котором находится блок с элементом
					$sql = "SELECT *
							FROM `_block`
							WHERE `id`=".$unit['block_id'];
					if(!$block = query_assoc($sql))
						break;

					//пока только для диалогов
					if($block['obj_name'] != 'dialog')
						break;

					//получение количества использования значений
					$sql = "SELECT
								`".$unit['col']."` `id`,
								COUNT(*) `use`
							FROM `_element`
							WHERE `dialog_id`=".$block['obj_id']."
							GROUP BY `".$unit['col']."`";
					foreach(query_ass($sql) as $id => $use)
						if(isset($unit[$col][$id]))
							$unit[$col][$id]['use'] = $use;
				}
		}

	$send['unit'] = $unit;

	//если производится удаление единицы списка
	if($send['to_del'] = _num(@$_POST['to_del'])) {
		if(!$unit_id)
			jsonError('Отсутствует единица списка для удаления');

		$html =
			'<div class="pad20">'.
				'<div class="_info b">Подтвердите удаление записи.</div>'.
			'</div>';

		$send['head'] = utf8('Удаление записи');
		$send['html'] = utf8($html);
		$send['button_submit'] = utf8('Удалить');
	}

	return $send;
}




