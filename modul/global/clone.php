<?php
function PHP12_clone_on() {//получение диалогов, данные которых разрешены для переноса
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `clone_on`
			  AND `table_1`=11
			ORDER BY `name`";
	if(!$arr = query_arr($sql))
		return _emptyMin('Данные переноситься не будут. Только структура приложения.');

	$sql = "SELECT
				`dialog_id`,
				COUNT(*)
			FROM `_spisok`
			WHERE `dialog_id` IN ("._idsGet($arr).")
			GROUP BY `dialog_id`";
	$ass = query_ass($sql);

	$send = '';
	foreach($arr as $id => $r)
		$send .= '<div class="mt3">'.
					'&bull; '.$r['name'].
					'<span class="pale ml10">'.
						'(<b>'._num(@$ass[$id]).'</b>)'.
					'</span>'.
				 '</div>';

	return '<div class="ml20">'.$send.'</div>';
}

function _clone_go($DLG, $CMP) {
	if($DLG['id'] != 120)
		return;
	if(!SA)
		jsonError('Нет прав');


	$name = '';
	foreach($DLG['cmp'] as $cmp_id => $r)
		if($r['dialog_id'] == 8)
			if(isset($CMP[$cmp_id]))
				$name = $CMP[$cmp_id];

	if(!$name)
		jsonError('Не указано имя приложения');

	//внесение приложения
	$sql = "INSERT INTO `_app` (
				`name`,
				`user_id_add`
			) VALUES (
				'".addslashes($name)."',
				".USER_ID."
			)";
	$app_id = query_id($sql);

	_clone($app_id);
	_app_user_access($app_id);

	$send = array(
		'action_id' => 2,
		'action_page_id' => 0
	);

	jsonSuccess($send);
}
function _clone($appDst) {//клонирование приложения
	define('CLONE_ID_SRC', APP_ID); //id приложения-источника
	define('CLONE_ID_DST', $appDst);//id приложения-получателя

	_clone_clear();
	_clone_base();

	if(_clone_page() === false)
		return;

	_clone_block();
	_clone_element();
	_clone_dialog();
	_clone_blk_upd();
	_clone_elm_upd();
	_clone_template();
	_clone_action();
	_clone_counter();
	_clone_cron();
	_clone_element_hint();
	_clone_spisok();

	_debug_cache_clear();
}
function _clone_clear() {
	//удаление соответствий
	$sql = "DELETE FROM `_clone`
			WHERE `app_id_src`=".CLONE_ID_SRC."
			  AND `app_id_dst`=".CLONE_ID_DST;
	query($sql);

	//удаление данных
	$sql = "DELETE FROM `_spisok`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление значений фильтров пользователей
	$sql = "DELETE FROM `_user_spisok_filter`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление истории действий
	$sql = "DELETE FROM `_history`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);
	$sql = "DELETE FROM `_history_edited`
			WHERE `app_id`=".CLONE_ID_DST;
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

	//удаление действий
	$sql = "DELETE FROM `_action`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление счётчиков
	$sql = "DELETE FROM `_counter`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление планировщиков
	$sql = "DELETE FROM `_cron`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление подсказок
	$sql = "DELETE FROM `_element_hint`
			WHERE `app_id`=".CLONE_ID_DST;
	query($sql);

	//удаление шаблонов документов
	$sql = "DELETE FROM `_template`
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

	$json = json_encode($json);

	return htmlspecialchars($json);
}
function _clone_base() {//сохранение соответствий базовых диалогов
	$sql = "SELECT *
			FROM `_dialog`
			WHERE !`app_id`
			  AND `parent_any`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $id => $r)
		_clone_ass_save('_dialog', $id, $id);

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog'
			  AND `obj_id` IN ("._idsGet($arr).")";
	if(!$BLK = query_arr($sql))
		return;

	foreach($BLK as $id => $r)
		_clone_ass_save('_block', $id, $id);

	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($BLK).")";
	if(!$ELM = query_arr($sql))
		return;

	foreach($ELM as $id => $r)
		_clone_ass_save('_element', $id, $id);
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
		*/

		$sql = "INSERT INTO `_page` (
					`app_id`,
					`acs`,
					`dialog_id`,
					`dialog_id_unit_get`,
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
					".$r['dialog_id_unit_get'].",
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

	//страницы, принимающие значения записи
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id`=".CLONE_ID_DST."
			  AND `dialog_id_unit_get`
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$sql = "UPDATE `_page`
				SET `dialog_id_unit_get`="._num(@$assDLG[$r['dialog_id_unit_get']])."
				WHERE `id`=".$r['id'];
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

		//замена элементов-указателей цвета
		if($ids = _ids($r['bg']))
			$r['bg'] = _clone_ids($ids, $assEL);

		$sql = "UPDATE `_block`
				SET `obj_id`=".$r['obj_id'].",
					`bg`='".$r['bg']."'
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
	$assBL = $ass[_table('_block')];
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

			//Сборный текст
			case 44:
				$sql = "UPDATE `_element`
						SET `txt_2`='"._clone_ids($r['txt_2'], $assEL)."'
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

			//Меню переключения блоков
			case 57:
				$sql = "UPDATE `_element`
						SET `txt_2`='"._clone_ids($r['txt_2'], $assEL)."',
							`def`="._num(@$assEL[$r['def']])."
						WHERE `id`=".$elem_id;
				query($sql);

				$sql = "SELECT *
						FROM `_element`
						WHERE `parent_id`=".$elem_id;
				foreach(query_arr($sql) as $ell) {
					$sql = "UPDATE `_element`
							SET `txt_2`='"._clone_ids($ell['txt_2'], $assBL)."'
							WHERE `id`=".$ell['id'];
					query($sql);
				}
				break;

			//Связка списка при помощи кнопки
			case 59:
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
						SET `num_1`="._num(@$assEL[$r['num_1']]).",
							`def`="._num(@$assEL[$r['def']])."
						WHERE `id`=".$elem_id;
				query($sql);

				$sql = "SELECT *
						FROM `_element`
						WHERE `parent_id`=".$elem_id;
				foreach(query_arr($sql) as $ell) {
					$sql = "UPDATE `_element`
							SET `txt_2`='".addslashes(_clone_json($ell['txt_2'], $assEL))."'
							WHERE `id`=".$ell['id'];
					query($sql);
				}
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
						SET `num_1`="._num(@$assDLG[$r['num_1']]).",
							`txt_1`='".addslashes(_clone_json($r['txt_1'], $assEL))."'
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

function _clone_template() {
	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assEL = $ass[_table('_element')];
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $r) {
		$sql = "INSERT INTO `_template` (
					`app_id`,

					`name`,
					`spisok_id`,
					`attach_id`,
					`param_ids`,
					`fname`,

					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",

					'".addslashes($r['name'])."',
					"._num(@$assDLG[$r['spisok_id']]).",
					".$r['attach_id'].",
					'"._clone_ids($r['param_ids'], $assEL)."',
					'".$r['fname']."',

					".USER_ID."
				)";
		_clone_ass_save('_template', $r['id'], query_id($sql));
	}
}
function _clone_action() {
	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assPG = $ass[_table('_page')];
	$assEL = $ass[_table('_element')];
	$assBL = $ass[_table('_block')];
	$assDLG = $ass[_table('_dialog')];
	$assTMP = $ass[_table('_template')];

	foreach($arr as $r) {
		$filter = $r['filter'];
		$initial_id = $r['initial_id'];
		$target_ids = $r['target_ids'];
		$apply_id = $r['apply_id'];
		$effect_id = $r['effect_id'];
		$revers = $r['revers'];

		switch($r['dialog_id']) {
			//действие для элемента: скрытие/показ блоков
			case 201:
				$filter = _clone_json($filter, $assEL);
				if($initial_id > 0) {

				}
				$target_ids = _clone_ids($target_ids, $assBL);
				break;

			//действие для элемента: Установка значения элементу
			case 202:
				$target_ids = _clone_ids($target_ids, $assEL);
				break;

			//действие для элемента: Открытие диалога
			case 205:
				$initial_id = _clone_ids($initial_id, $assEL);
				$target_ids = _clone_ids($target_ids, $assDLG);
				break;

			//действие для элемента: Установка фокуса
			case 206:
				$target_ids = _clone_ids($target_ids, $assEL);
				break;

			//действие для элемента: Открытие документа на печать
			case 207:
				$initial_id = _clone_ids($initial_id, $assEL);
				$target_ids = _clone_ids($target_ids, $assTMP);
				break;

			//действие для блока: скрытие/показ блоков
			case 211:
				$target_ids = _clone_ids($target_ids, $assBL);
				break;

			//действие для блока: изменение значения у элемента
			case 212:
				$target_ids = _clone_ids($target_ids, $assEL);
				//$apply_id
				break;

			//действие для блока: переход на страницу
			case 214:
				$target_ids = _clone_ids($target_ids, $assPG);
				//$apply_id
				break;

			//действие для блока: открытие диалога
			case 215:
				$filter = _clone_json($filter, $assEL);
				$target_ids = _clone_ids($target_ids, $assDLG);
				break;

			//действие для блока: установка фокуса на элемент
			case 216:
				$target_ids = _clone_ids($target_ids, $assEL);
				break;

			//действие для блока: открытие документа на печать
			case 217:
				$target_ids = _clone_ids($target_ids, $assTMP);
				break;

			//действие для элемента: переход на страницу
			case 221:
				$target_ids = _clone_ids($target_ids, $assPG);
				break;

			//действие для элемента: открытие диалога
			case 222:
				$target_ids = _clone_ids($target_ids, $assDLG);
				break;

			//действие для элемента: тёмная подсказка при наведении
			case 223:
				$target_ids = _clone_ids($target_ids, $assEL);
				break;

			//действие для элемента: внешняя ссылка
			case 224: break;
		}

		$sql = "INSERT INTO `_action` (
					`app_id`,

					`dialog_id`,
					`block_id`,
					`element_id`,

					`filter`,
					`initial_id`,
					`target_ids`,
					`apply_id`,
					`effect_id`,
					`revers`,

					`sort`,
					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",

					".$r['dialog_id'].",
					"._num(@$assBL[$r['block_id']]).",
					"._num(@$assEL[$r['element_id']]).",

					'".addslashes($filter)."',
					".$initial_id.",
					'".$target_ids."',
					".$apply_id.",
					".$effect_id.",
					".$revers.",

					".$r['sort'].",
					".USER_ID."
				)";
		query($sql);
	}
}
function _clone_counter() {
	$sql = "SELECT *
			FROM `_counter`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assEL = $ass[_table('_element')];
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $r) {
		$sql = "INSERT INTO `_counter` (
					`app_id`,

					`name`,
					`about`,
					`spisok_id`,
					`filter`,
					`type_id`,
					`sum_elem_id`,

					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",

					'".$r['name']."',
					'".$r['about']."',
					"._num(@$assDLG[$r['spisok_id']]).",
					'".addslashes( _clone_json($r['filter'], $assEL))."',
					".$r['type_id'].",
					"._num(@$assEL[$r['sum_elem_id']]).",

					".USER_ID."
				)";
		query($sql);
	}
}
function _clone_cron() {
	$sql = "SELECT *
			FROM `_cron`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assEL = $ass[_table('_element')];
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $r) {
		if($r['dst_prm']) {
			$dst_prm = array();
			foreach(explode(',', $r['dst_prm']) as $ex) {
				$exx = explode(':', $ex);
				$e1 = _num(@$assEL[$exx[0]]);
				$e2 = _num(@$assEL[$exx[1]]);
				$dst_prm[] = $e1.':'.$e2;
			}
			$r['dst_prm'] = implode(',', $dst_prm);
		}

		$sql = "INSERT INTO `_cron` (
					`app_id`,

					`name`,
					`time_min`,
					`time_hour`,
					`time_day`,
					`time_week`,
					`time_mon`,
					`src_spisok`,
					`src_prm`,
					`dst_spisok`,
					`dst_prm`,

					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",

					'".$r['name']."',
					".$r['time_min'].",
					".$r['time_hour'].",
					".$r['time_day'].",
					".$r['time_week'].",
					".$r['time_mon'].",
					"._num(@$assDLG[$r['src_spisok']]).",
					'".addslashes( _clone_json($r['src_prm'], $assEL))."',
					"._num(@$assDLG[$r['dst_spisok']]).",
					'".$r['dst_prm']."',

					".USER_ID."
				)";
		query($sql);
	}
}
function _clone_element_hint() {
	$sql = "SELECT *
			FROM `_element_hint`
			WHERE `app_id`=".CLONE_ID_SRC."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assEL = $ass[_table('_element')];

	foreach($arr as $r) {
		$sql = "INSERT INTO `_element_hint` (
					`app_id`,

					`element_id`,
					`on`,
					`msg`,
					`side`,
					`pos_h`,
					`pos_v`,
					`delay_show`,
					`delay_hide`,

					`user_id_add`
				) VALUES (
					".CLONE_ID_DST.",

					"._num(@$assEL[$r['element_id']]).",
					".$r['on'].",
					'".addslashes($r['msg'])."',
					".$r['side'].",
					".$r['pos_h'].",
					".$r['pos_v'].",
					".$r['delay_show'].",
					".$r['delay_hide'].",

					".USER_ID."
				)";
		query($sql);
	}
}
function _clone_spisok() {
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".CLONE_ID_SRC."
			  AND `clone_on`
			  AND `table_1`=11
			ORDER BY `name`";
	if(!$dlg = query_arr($sql))
		return;

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `dialog_id` IN ("._idsGet($dlg).")
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$ass = _clone_ass();
	$assDLG = $ass[_table('_dialog')];

	foreach($arr as $id => $r) {
		$sql = "INSERT INTO `_spisok` (
					`cnn_id`,
					`app_id`,
					`dialog_id`,
					`num`,
					`txt_1`,`txt_2`,`txt_3`,`txt_4`,`txt_5`,`txt_6`,`txt_7`,`txt_8`,`txt_9`,`txt_10`,
					`num_1`,`num_2`,`num_3`,`num_4`,`num_5`,`num_6`,`num_7`,`num_8`,`num_9`,`num_10`,
					`connect_1`,`connect_2`,`connect_3`,`connect_4`,`connect_5`,
					`count_1`,`count_2`,`count_3`,`count_4`,`count_5`,
					`cena_1`,`cena_2`,`cena_3`,`cena_4`,`cena_5`,
					`sum_1`,`sum_2`,`sum_3`,`sum_4`,`sum_5`,`sum_6`,`sum_7`,`sum_8`,`sum_9`,`sum_10`,
					`date_1`,`date_2`,`date_3`,`date_4`,`date_5`,
					`image_1`,`image_2`,
					`sort`,
					`user_id_add`
				) SELECT
					`cnn_id`,
					".CLONE_ID_DST.",
					"._num(@$assDLG[$r['dialog_id']]).",
					`num`,
					`txt_1`,`txt_2`,`txt_3`,`txt_4`,`txt_5`,`txt_6`,`txt_7`,`txt_8`,`txt_9`,`txt_10`,
					`num_1`,`num_2`,`num_3`,`num_4`,`num_5`,`num_6`,`num_7`,`num_8`,`num_9`,`num_10`,
					`connect_1`,`connect_2`,`connect_3`,`connect_4`,`connect_5`,
					`count_1`,`count_2`,`count_3`,`count_4`,`count_5`,
					`cena_1`,`cena_2`,`cena_3`,`cena_4`,`cena_5`,
					`sum_1`,`sum_2`,`sum_3`,`sum_4`,`sum_5`,`sum_6`,`sum_7`,`sum_8`,`sum_9`,`sum_10`,
					`date_1`,`date_2`,`date_3`,`date_4`,`date_5`,
					`image_1`,`image_2`,
					`sort`,
					".USER_ID."
				  FROM `_spisok`
				  WHERE `id`=".$id;
		_clone_ass_save('_spisok', $id, query_id($sql));
	}

	$ass = _clone_ass();
	$assSP = $ass[_table('_spisok')];

	foreach($arr as $id => $r) {
		$sql = "UPDATE `_spisok`
				SET `parent_id`="._num(@$assSP[$r['parent_id']])."
				WHERE `id`="._num($assSP[$id]);
		query($sql);
	}
}

