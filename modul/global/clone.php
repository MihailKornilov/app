<?php
function _clone() {//клонирование приложения
	define('CLONE_ID_SRC', 1); //id приложения-источника
	define('CLONE_ID_DST', 9); //id приложения-получателя

	_clone_clear();

	if(_clone_page() === false)
		return;

	_clone_block();
	_clone_element();
	_clone_dialog();
	_clone_blk_upd();
	_clone_elm_upd();

	_debug_cache_clear();
}
function _clone_clear() {
	//удаление соответствий
	$sql = "DELETE FROM `_clone`
			WHERE `app_id_src`=".CLONE_ID_SRC."
			  AND `app_id_dst`=".CLONE_ID_DST;
	query($sql);

	//удаление страниц
	$sql = "DELETE FROM `_page`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление блоков
	$sql = "DELETE FROM `_block`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление элементов
	$sql = "DELETE FROM `_element`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление диалогов
	$sql = "DELETE FROM `_dialog`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);
}

function _clone_ass_save($tName, $src_id, $dst_id) {//сохранение соответствий идентификаторов
	$sql = "INSERT INTO `_clone` (
				`app_id_src`,
				`app_id_dst`,
				`table_id`,
				`src_id`,
				`dst_id`
			) VALUES (
				".CLONE_ID_SRC.",
				".CLONE_ID_DST.",
				'"._table($tName)."',
				".$src_id.",
				".$dst_id."
			)";
	query($sql);
}
function _clone_ass($table_id=0) {//получение соответствий идентификаторов
	$sql = "SELECT *
			FROM `_clone`
			WHERE `app_id_src`=".CLONE_ID_SRC."
			  AND `app_id_dst`=".CLONE_ID_DST;
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r)
		$send[$r['table_id']][$r['src_id']] = $r['dst_id'];

	if(!$table_id)
		return $send;

	if(empty($send[$table_id]))
		return array();

	return $send[$table_id];
}
function _clone_ids($ids, $ass, $send='') {//замена нескольких ID
	if(!$ids)
		return $send;

	$arr = array();
	foreach(explode(',', $ids) as $id)
		if(isset($ass[$id]))
			$arr[] = $ass[$id];

	if(empty($arr))
		return $send;

	return implode(',', $arr);
}
function _clone_json($vv, $assEL) {//замена элементов в json
	if(!$vv)
		return '';

	$json = htmlspecialchars_decode($vv);
	if(!$json = json_decode($json, true))
		return $vv;

	foreach($json as $n => $js) {
		$json[$n]['elem_id'] = _num(@$assEL[$js['elem_id']]);
	}

	return json_encode($json);
}

function _clone_page() {//клонирование: страницы
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return false;

	foreach($arr as $r) {
		/* прописываются позже:
			`parent_id`
			`common_id`
			`dialog_id_unit_get`
		*/

		$sql = "INSERT INTO `_page` (
					`app_id`,
					`acs`,
					`dialog_id`,
					`name`,
					`about`,
					`image_ids`,
					`def`,
					`sort`,
					`user_id_add`				
				) VALUES (
					".CLONE_ID_DST.",
					".$r['acs'].",
					".$r['dialog_id'].",
					'".addslashes($r['name'])."',
					'".addslashes($r['about'])."',
					'".addslashes($r['image_ids'])."',
					".$r['def'].",
					".$r['sort'].",
					".USER_ID."
				)";
		_clone_ass_save('_page', $r['id'], query_id($sql));
	}

	$ass = _clone_ass(_table('_page'));

	foreach($arr as $id => $r) {
		if(!$r['parent_id'] && !$r['common_id'])
			continue;

		$sql = "UPDATE `_page`
				SET `parent_id`="._num(@$ass[$r['parent_id']]).",
					`common_id`="._num(@$ass[$r['common_id']])."
				WHERE `id`=".$ass[$id];
		query($sql);
	}

	return true;
}
function _clone_block() {//клонирование: блоки
	$sql = "SELECT *
			FROM `_block`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $r) {
		/* прописываются позже:
			`parent_id`
			`xx_ids`
		*/

		$sql = "INSERT INTO `_block` (
					`app_id`,
					`child_count`,
					`sa`,
					`obj_name`,
					`obj_id`,
					`x`,
					`xx`,
					`y`,
					`w`,
					`h`,
					`width`,
					`width_auto`,
					`height`,
					`pos`,
					`bg`,
					`bor`,
					`hidden`,
					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",
					".$r['child_count'].",
					".$r['sa'].",
					'".$r['obj_name']."',
					".$r['obj_id'].",
					".$r['x'].",
					".$r['xx'].",
					".$r['y'].",
					".$r['w'].",
					".$r['h'].",
					".$r['width'].",
					".$r['width_auto'].",
					".$r['height'].",
					'".$r['pos']."',
					'".$r['bg']."',
					'".$r['bor']."',
					".$r['hidden'].",
					".USER_ID."
				)";
		_clone_ass_save('_block', $r['id'], query_id($sql));
	}

	$ass = _clone_ass();
	$assBlk = $ass[_table('_block')];

	foreach($arr as $id => $r) {
		if(!$r['parent_id'] && !$r['xx_ids'])
			continue;

		$sql = "UPDATE `_block`
				SET `parent_id`="._num(@$assBlk[$r['parent_id']]).",
					`xx_ids`='"._clone_ids($r['xx_ids'], $assBlk)."'
				WHERE `id`=".$assBlk[$id];
		query($sql);
	}
}
function _clone_element() {//клонирование: элементы
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass(_table('_block'));

	foreach($arr as $r) {
		/* прописываются позже:
			`parent_id`
		*/

		$sql = "INSERT INTO `_element` (
					`app_id`,
					`block_id`,
					`dialog_id`,
					`col`,
					`name`,
					`req`,
					`req_msg`,
					`nosel`,
					`focus`,
					`width`,
					`color`,
					`font`,
					`size`,
					`mar`,
					`txt_1`,
					`txt_2`,
					`txt_3`,
					`txt_4`,
					`txt_5`,
					`txt_6`,
					`txt_7`,
					`txt_8`,
					`txt_9`,
					`txt_10`,
					`num_1`,
					`num_2`,
					`num_3`,
					`num_4`,
					`num_5`,
					`num_6`,
					`num_7`,
					`num_8`,
					`num_9`,
					`num_10`,
					`def`,
					`sort`,
					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",
					"._num(@$ass[$r['block_id']]).",
					".$r['dialog_id'].",
					'".addslashes($r['col'])."',
					'".addslashes($r['name'])."',
					".$r['req'].",
					'".addslashes($r['req_msg'])."',
					".$r['nosel'].",
					".$r['focus'].",
					".$r['width'].",
					'".$r['color']."',
					'".$r['font']."',
					".$r['size'].",
					'".$r['mar']."',
					'".addslashes($r['txt_1'])."',
					'".addslashes($r['txt_2'])."',
					'".addslashes($r['txt_3'])."',
					'".addslashes($r['txt_4'])."',
					'".addslashes($r['txt_5'])."',
					'".addslashes($r['txt_6'])."',
					'".addslashes($r['txt_7'])."',
					'".addslashes($r['txt_8'])."',
					'".addslashes($r['txt_9'])."',
					'".addslashes($r['txt_10'])."',
					".$r['num_1'].",
					".$r['num_2'].",
					".$r['num_3'].",
					".$r['num_4'].",
					".$r['num_5'].",
					".$r['num_6'].",
					".$r['num_7'].",
					".$r['num_8'].",
					".$r['num_9'].",
					".$r['num_10'].",
					".$r['def'].",
					".$r['sort'].",
					".USER_ID."
				)";
		_clone_ass_save('_element', $r['id'], query_id($sql));
	}

	$ass = _clone_ass();
	$assElm = $ass[_table('_element')];

	foreach($arr as $id => $r) {
		if(!$r['parent_id'] && !_num($r['col']))
			continue;

		if(_num($r['col']))
			$r['col'] = _num(@$assElm[$r['col']]);

		$sql = "UPDATE `_element`
				SET `parent_id`="._num(@$assElm[$r['parent_id']]).",
					`col`='".$r['col']."'
				WHERE `id`=".$assElm[$id];
		query($sql);
	}
}
function _clone_dialog() {//клонирование: диалоги
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$assPG = _clone_ass(_table('_page'));
	$assEL = _clone_ass(_table('_element'));

	foreach($arr as $r) {
		/* прописываются позже:
				`dialog_id_parent`,
				`dialog_id_unit_get`,

			НЕ переносятся:
				`sa`,
				`parent_any`,
				`element_group_id`,
				`element_width`,
				`element_width_min`,
				`element_type`,
				`element_afics`,
				`element_action_dialog_id`,
				`element_hidden`,
				`cmp_no_req`,
		*/

		$sql = "INSERT INTO `_dialog` (
					`app_id`,
					`num`,
					`name`,
					`width`,
					`width_auto`,
					`insert_on`,
					`insert_head`,
					`insert_button_submit`,
					`insert_button_cancel`,
					`insert_action_id`,
					`insert_action_page_id`,
					`insert_history_elem`,
					`insert_unit_change_elem_id`,
					`insert_unit_change_v`,
					`edit_on`,
					`edit_head`,
					`edit_button_submit`,
					`edit_button_cancel`,
					`edit_action_id`,
					`edit_action_page_id`,
					`edit_history_elem`,
					`del_on`,
					`del_head`,
					`del_button_submit`,
					`del_button_cancel`,
					`del_action_id`,
					`del_action_page_id`,
					`del_history_elem`,
					`table_1`,
					`sort`,
					`spisok_on`,
					`spisok_elem_id`,
					`menu_edit_last`,
					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",
					".$r['num'].",
					'".addslashes($r['name'])."',
					".$r['width'].",
					".$r['width_auto'].",

					".$r['insert_on'].",
					'".addslashes($r['insert_head'])."',
					'".addslashes($r['insert_button_submit'])."',
					'".addslashes($r['insert_button_cancel'])."',
					".$r['insert_action_id'].",
					"._num(@$assPG[$r['insert_action_page_id']]).",
					'"._clone_ids($r['insert_history_elem'], $assEL)."',
					"._num(@$assEL[$r['insert_unit_change_elem_id']]).",
					'".$r['insert_unit_change_v']."',

					".$r['edit_on'].",
					'".addslashes($r['edit_head'])."',
					'".addslashes($r['edit_button_submit'])."',
					'".addslashes($r['edit_button_cancel'])."',
					".$r['edit_action_id'].",
					"._num(@$assPG[$r['edit_action_page_id']]).",
					'"._clone_ids($r['edit_history_elem'], $assEL)."',

					".$r['del_on'].",
					'".addslashes($r['del_head'])."',
					'".addslashes($r['del_button_submit'])."',
					'".addslashes($r['del_button_cancel'])."',
					".$r['del_action_id'].",
					"._num(@$assPG[$r['del_action_page_id']]).",
					'"._clone_ids($r['del_history_elem'], $assEL)."',

					".$r['table_1'].",
					".$r['sort'].",
					".$r['spisok_on'].",
					"._num(@$assEL[$r['spisok_elem_id']]).",
					".$r['menu_edit_last'].",

					".USER_ID."
				)";
		_clone_ass_save('_dialog', $r['id'], query_id($sql));
	}

	$ass = _clone_ass();
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $id => $r) {
		if(!$r['dialog_id_parent'] && !$r['dialog_id_unit_get'])
			continue;

		$sql = "UPDATE `_dialog`
				SET `dialog_id_parent`="._num(@$assDLG[$r['dialog_id_unit_get']]).",
					`dialog_id_unit_get`="._num(@$assDLG[$r['dialog_id_unit_get']])."
				WHERE `id`=".$assDLG[$id];
		query($sql);
	}
}

function _clone_blk_upd() {//обновление объектов для диалогов и списков
	$sql = "SELECT *
			FROM `_block`
			WHERE `app_id`=".CLONE_ID_DST."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assPG = $ass[_table('_page')];
	$assEL = $ass[_table('_element')];
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $block_id => $r) {
		switch($r['obj_name']) {
			case 'page':
				$r['obj_id'] = _num(@$assPG[$r['obj_id']]);
				break;
			case 'dialog':
			case 'dialog_del':
				$r['obj_id'] = _num(@$assDLG[$r['obj_id']]);
				break;
			case 'spisok':
				$r['obj_id'] = _num(@$assEL[$r['obj_id']]);
				break;
		}

		$sql = "UPDATE `_block`
				SET `obj_id`=".$r['obj_id']."
				WHERE `id`=".$block_id;
		query($sql);
	}

}
function _clone_elm_upd() {//клонирование: обновление значений в некоторых элементах
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".CLONE_ID_DST."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assPG = $ass[_table('_page')];
	$assEL = $ass[_table('_element')];
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $elem_id => $r)
		switch($r['dialog_id']) {
			//Кнопка
			case 2:
				$sql = "UPDATE `_element`
						SET `num_4`="._num(@$assDLG[$r['num_4']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Меню страниц
			case 3:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assPG[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр - быстрый поиск
			case 7:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Вставка значения записи
			case 11:
				$sql = "UPDATE `_element`
						SET `txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Выбор элемента из диалога или страницы
			case 13:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Список-шаблон
			case 14:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`txt_2`='".addslashes(_clone_json($r['txt_2'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Количество строк списка
			case 15:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Radio - произвольные значения
			case 16:
				if(!$r['num_3'])
					break;
				$sql = "UPDATE `_element`
						SET `num_3`="._num(@$assEL[$r['num_3']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Список-таблица
			case 23:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`txt_2`='".addslashes(_clone_json($r['txt_2'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Select: выбор записи из другого списка
			case 29:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`txt_3`='"._clone_ids($r['txt_3'], $assEL)."',
							`txt_4`='"._clone_ids($r['txt_4'], $assEL)."',
							`txt_5`='".addslashes(_clone_json($r['txt_5'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Выбор нескольких значений галочками
			case 31:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`num_2`="._num(@$assEL[$r['num_2']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтрование списка
			case 40:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//количество значений привязанного списка
			case 54:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_1`='".addslashes(_clone_json($r['txt_1'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//сумма значений привязанного списка
			case 55:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_1`='".addslashes(_clone_json($r['txt_1'], $assEL))."',
							`num_2`="._num(@$assEL[$r['num_2']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Связка списка при помощи кнопки
			case 56:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`num_4`="._num(@$assDLG[$r['num_4']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр: галочка
			case 62:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_2`='".addslashes(_clone_json($r['txt_2'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр: год и месяц
			case 72:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`num_2`="._num(@$assEL[$r['num_2']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр - Radio
			case 74:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр: календарь
			case 77:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр: меню
			case 78:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_1`='"._clone_ids($r['txt_1'], $assEL)."',
							`txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Очистка фильтра
			case 80:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр: Select - привязанный список
			case 83:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Select - выбор значения списка по умолчанию
			case 85:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Значение записи: количество дней
			case 86:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Циферка в меню страниц
			case 87:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assDLG[$r['num_1']])."
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Количество значений связанного списка с учётом категорий
			case 96:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_1`='"._clone_ids($r['txt_1'], $assEL)."',
							`txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;

			//Фильтр - Выбор нескольких групп значений
			case 102:
				$sql = "UPDATE `_element`
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`txt_2`='"._clone_ids($r['txt_2'], $assEL)."',
							`txt_3`='"._clone_ids($r['txt_3'], $assEL)."',
							`txt_4`='"._clone_ids($r['txt_4'], $assEL)."',
							`txt_5`='".addslashes(_clone_json($r['txt_5'], $assEL))."'
						WHERE `id`=".$elem_id;
				query($sql);
				break;
		}
}




