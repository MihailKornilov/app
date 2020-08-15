<?php
/*
	Файл, содержащий фукнции отслеживания ошибок в приложении
	Страница 132
*/

function _bug_ids_count($arr, $OBJ, $col, $isNum=false) {//количество отсутствующих идентификаторов
	$c = 0;
	foreach($arr as $r)
		if($ids = _ids($r[$col], 'arr'))
			foreach($ids as $id)
				if(!isset($OBJ[$id]))
					$c++;

	return $isNum ? $c : _hide0($c);
}
function _bug_json_elm($arr, $OBJ, $col, $isNum=false) {//количество несуществующих идентификаторов в фильтре [40]
	$c = 0;
	foreach($arr as $r) {
		$json = htmlspecialchars_decode($r[$col]);
		if(!$json = json_decode($json, true))
			continue;

		foreach($json as $n => $js)
			if($ids = _ids($js['elem_id'], 'arr'))
				foreach($ids as $id)
					if(!isset($OBJ[$id]))
						$c++;
	}

	return $isNum ? $c : _hide0($c);
}
function _bug_elm_ass($arr, $OBJ, $col, $isNum=false) {//ассоциативных массив элементов в виде 1234:6789,4433:120
	$c = 0;
	foreach($arr as $r)
		if($r[$col]) {
			$ex = explode(',', $r[$col]);
			foreach($ex as $vv) {
				$iex = explode(':', $vv);
				if($id = _num($iex[0]))
					if(!isset($OBJ[$id]))
						$c++;
				if($id = _num($iex[0]))
					if(!isset($OBJ[$id]))
						$c++;
			}
		}

	return $isNum ? $c : _hide0($c);
}
function _bug_script($prm) {
	return '<script>_bug('.$prm['el12']['block_id'].')</script>';
}





/* ---=== ПОЛЬЗОВАТЕЛИ ===--- */
function PHP12_bug_user($prm) {
	$sql = "SELECT COUNT(*)
			FROM `_user_access`
			WHERE `app_id`=".APP_ID;
	$USER = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего зарегистрировано пользователей:<td class="w50 r b color-pay">'.$USER.
	'</table>'.
	_bug_script($prm);
}






/* ---=== СТРАНИЦЫ ===--- */
function PHP12_bug_page($prm) {//страницы
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id`=".APP_ID;
	$PG = query_arr($sql);

	$parentC = 0;
	$commonC = 0;
	foreach($PG as $r) {
		if($pid = $r['parent_id'])
			if(!isset($PG[$pid]))
				$parentC++;

		if($cid = $r['common_id'])
			if(!isset($PG[$cid]))
				$commonC++;
	}

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего страниц:<td class="w50 r b color-pay">'.count($PG).
		'<tr><td class="color-del">Некорректный ID родителя `parent_id`:<td class="r b red">'._hide0($parentC).
		'<tr><td class="color-del">Некорректный ID связки `common_id`:<td class="r b red">'._hide0($commonC).
		'<tr><td class="color-del">Блоки от потерянных или удалённых страниц:<td class="r b red">'.PHP12_bug_page_blk($PG).
		'<tr><td class="color-del">Заметки от потерянных страниц:<td class="r b red">'.PHP12_bug_page_note($PG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Диалоги:'.
		'<tr><td class="color-del">Переход на страницу после внесения:<td class="w50 r b red">'.PHP12_bug_page_dlg_insert($PG).
		'<tr><td class="color-del">Переход на страницу после редактирования:<td class="r b red">'.PHP12_bug_page_dlg_edit($PG).
		'<tr><td class="color-del">Переход на страницу после удаления:<td class="r b red">'.PHP12_bug_page_dlg_del($PG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Элементы:'.
		'<tr><td class="color-del">[3] Меню страниц - некорректное указание страницы:<td class="w50 r b red">'.PHP12_bug_page_3menu($PG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Действия:'.
		'<tr><td class="color-del">204 - переход на страницу (элемент):<td class="w50 r b red">'.PHP12_bug_page_204act($PG).
		'<tr><td class="color-del">214 - переход на страницу (блок):<td class="r b red">'.PHP12_bug_page_214act($PG).
		'<tr><td class="color-del">221 - переход на страницу (клик по элементу):<td class="r b red">'.PHP12_bug_page_221act($PG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-del">Доступ к страницам для пользователей:<td class="w50 r b red">'.PHP12_bug_page_user_access($PG).
	'</table>'.
	_bug_script($prm);
}
function PHP12_bug_page_blk($PG) {//Блоки от потерянных страниц
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `app_id`=".APP_ID."
			  AND `obj_name`='page'
			  AND `obj_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_note($PG) {//Заметки
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`
			  AND `page_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_3menu($PG) {//[3] Меню страниц
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=3
			  AND `num_1`
			  AND `num_1` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_dlg_insert($PG) {//Переход на страницу после внесения
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `insert_action_page_id`
			  AND `insert_action_page_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_dlg_edit($PG) {//Переход на страницу после редактирования
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `edit_action_page_id`
			  AND `edit_action_page_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_dlg_del($PG) {//Переход на страницу после удаления
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `del_action_page_id`
			  AND `del_action_page_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_204act($PG) {//204 - переход на страницу (элемент)
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=204
			  AND `target_ids` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_214act($PG) {//214 - переход на страницу (блок)
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=214
			  AND `target_ids` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_221act($PG) {//221 - переход на страницу (клик по элементу)
	if(empty($PG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=221
			  AND `target_ids` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_page_user_access($PG) {//Доступ к страницам для пользователей
	if(empty($PG))
		return '';

	return '';
}






/* ---=== ДИАЛОГИ ===--- */
function PHP12_bug_dialog($prm) {
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.",0)";
	$DLG = query_arr($sql);

	$c = 0;
	$parentC = 0;
	$uGetC = 0;
	foreach($DLG as $r) {
		if($r['app_id'])
			$c++;

		if($pid = $r['dialog_id_parent'])
			if(!isset($DLG[$pid]))
				$parentC++;

		if($uid = $r['dialog_id_unit_get'])
			if($uid > 0)
				if(!isset($DLG[$uid]))
					$uGetC++;
	}

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего диалогов:<td class="w50 r b color-pay">'._hide0($c).
		'<tr><td class="color-del">Некорректный ID родителя `dialog_id_parent`:<td class="r b red">'._hide0($parentC).
		'<tr><td class="color-del">Некорректный `dialog_id_unit_get` (принимает данные записи):<td class="r b red">'._hide0($uGetC).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-del">Страница принимает данные записи:<td class="w50 r b red">'.PHP12_bug_dialog_page_get($DLG).
		'<tr><td class="color-del">Блоки потерянных диалогов:<td class="r b red">'.PHP12_bug_dialog_blk($DLG).
		'<tr><td class="color-del">История действий:<td class="r b red">'.PHP12_bug_dialog_history($DLG).
		'<tr><td class="color-del">Шаблоны документов:<td class="r b red">'.PHP12_bug_dialog_template($DLG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Элементы:'.
		'<tr><td class="color-del">[2] Кнопка:<td class="w50 r b red">'.PHP12_bug_dialog_elem2($DLG).
		'<tr><td class="color-del">[14] Список-шаблон:<td class="r b red">'.PHP12_bug_dialog_elem14($DLG).
		'<tr><td class="color-del">[23] Список-таблица:<td class="r b red">'.PHP12_bug_dialog_elem23($DLG).
		'<tr><td class="color-del">[29] Select-связка:<td class="r b red">'.PHP12_bug_dialog_elem29($DLG).
		'<tr><td class="color-del">[31] Выбор галочками:<td class="r b red">'.PHP12_bug_dialog_elem31($DLG).
		'<tr><td class="color-del">[59] Кнопка-связка:<td class="r b red">'.PHP12_bug_dialog_elem59($DLG).
		'<tr><td class="color-del">[87] Циферка в меню:<td class="r b red">'.PHP12_bug_dialog_elem87($DLG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Планировщик:'.
		'<tr><td class="color-del">Исходный список:<td class="w50 r b red">'.PHP12_bug_dialog_cron_src($DLG).
		'<tr><td class="color-del">Список-получатель:<td class="r b red">'.PHP12_bug_cron_dst($DLG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Глобальные счётчики:'.
		'<tr><td class="color-del">ID диалога в настройках:<td class="w50 r b red">'.PHP12_bug_dialog_counter($DLG).
		'<tr><td class="color-del">ID диалога в содержании:<td class="r b red">'.PHP12_bug_dialog_counter_v($DLG).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Действия:'.
		'<tr><td class="color-del">205 - открытие диалога (элемент):<td class="w50 r b red">'.PHP12_bug_dialog_205act($DLG).
		'<tr><td class="color-del">215 - открытие диалога (блок):<td class="r b red">'.PHP12_bug_dialog_215act($DLG).
		'<tr><td class="color-del">218 - Блок принимает данные записи:<td class="r b red">'.PHP12_bug_dialog_218act($DLG).
		'<tr><td class="color-del">222 - открытие диалога (клик по элементу):<td class="r b red">'.PHP12_bug_dialog_222act($DLG).
	'</table>'.
	_bug_script($prm);
}
function PHP12_bug_dialog_page_get($DLG) {//ID диалога, которого страница принимает данные
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_page`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id_unit_get`
			  AND `dialog_id_unit_get` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_blk($DLG) {//Блоки от потерянных диалогов
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `app_id`=".APP_ID."
			  AND `obj_name` IN ('dialog','dialog_del')
			  AND `obj_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_history($DLG) {//История действий
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_template($DLG) {//Шаблоны документов
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `spisok_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}

function PHP12_bug_dialog_elem2($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=2
			  AND `num_4` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem14($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=14
			  AND `num_1` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem23($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=23
			  AND `num_1` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem29($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=29
			  AND `num_1` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem31($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=31
			  AND `num_1` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem59($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=59
			  AND (`num_1` NOT IN ("._idsGet($DLG).")
			    OR `num_4` NOT IN ("._idsGet($DLG).")
			   )";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_elem87($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=87
			  AND `num_1` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}

function PHP12_bug_dialog_cron_src($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_cron`
			WHERE `app_id`=".APP_ID."
			  AND `src_spisok` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_cron_dst($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_cron`
			WHERE `app_id`=".APP_ID."
			  AND `dst_spisok` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}

function PHP12_bug_dialog_counter($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_counter`
			WHERE `app_id`=".APP_ID."
			  AND `spisok_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_counter_v($DLG) {
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID."
			  AND `action_dialog_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}

function PHP12_bug_dialog_205act($DLG) {//действие 205 - открытие диалога (элемент)
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=205
			  AND `target_ids` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_215act($DLG) {//действие 215 - открытие диалога (блок)
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=215
			  AND `target_ids` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_218act($DLG) {//действие 218 - Блок принимает данные записи
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=218
			  AND `initial_id` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}
function PHP12_bug_dialog_222act($DLG) {//действие 215 - открытие диалога (блок)
	if(empty($DLG))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=222
			  AND `target_ids` NOT IN ("._idsGet($DLG).")";
	return _hide0(query_value($sql));
}






/* ---=== БЛОКИ ===--- */
function PHP12_bug_block($prm) {
	$sql = "SELECT *
			FROM `_block`
			WHERE `app_id`=".APP_ID;
	$BLK = query_arr($sql);

	$parentC = 0;
	$xxC = 0;
	foreach($BLK as $r) {
		if($pid = $r['parent_id'])
			if(!isset($BLK[$pid]))
				$parentC++;

		if($ids = _ids($r['xx_ids'], 'arr'))
			foreach($ids as $id)
				if(!isset($BLK[$id]))
					$xxC++;
	}

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего блоков:<td class="w50 r b color-pay">'.count($BLK).
		'<tr><td class="color-del">Некорректный ID родителя `parent_id`:<td class="r b red">'._hide0($parentC).
		'<tr><td class="color-del">Рядом стоящие блоки `xx_ids`:<td class="r b red">'._hide0($xxC).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Элементы:'.
		'<tr><td class="color-del">Размещение в блоках:<td class="w50 r b red">'.PHP12_bug_block_elem_parent().
		'<tr><td class="color-del">[57] Меню переключения блоков:<td class="r b red">'.PHP12_bug_block_elem57($BLK).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Действия - потерянные блоки:'.
		'<tr><td class="color-del">201 - скрытие/показ блоков (для элемента):<td class="w50 r b red">'.PHP12_bug_block_act201($BLK).
		'<tr><td class="color-del">211 - скрытие/показ блоков (для блоков):<td class="r b red">'.PHP12_bug_block_act211($BLK).
	'</table>'.
	_bug_script($prm);
}
function PHP12_bug_block_elem_parent() {
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `block_id`
			  AND `block_id` NOT IN (
				SELECT `id`
				FROM `_block`
				WHERE `app_id`=".APP_ID."
			  )";
	return _hide0(query_value($sql));
}
function PHP12_bug_block_elem57($BLK) {//Меню переключения блоков
	if(empty($BLK))
		return '';

	$sql = "SELECT `id`,`txt_2`
			FROM `_element`
			WHERE `parent_id` IN (

			    SELECT `id`
				FROM `_element`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=57

			)";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $BLK, 'txt_2');
}
function PHP12_bug_block_act201($BLK) {//201 - скрытие/показ блоков
	if(empty($BLK))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=201";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $BLK, 'target_ids');
}
function PHP12_bug_block_act211($BLK) {//211 - скрытие/показ блоков (для блоков)
	if(empty($BLK))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=211";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $BLK, 'target_ids');
}






/* ---=== ЭЛЕМЕНТЫ ===--- */
function PHP12_bug_element($prm) {
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID;
	$ELM = query_arr($sql);

	$c = count($ELM);

	//элементы из базовых диалогов
	$dlgIds = "SELECT `id` FROM `_dialog` WHERE `parent_any`";
	$bldIds = "SELECT `id`
			   FROM `_block`
			   WHERE `obj_name`='dialog'
			     AND `obj_id` IN (".$dlgIds.")";
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN (".$bldIds.")";
	$ELM += query_arr($sql);


	$parentC = 0;
	$colC = 0;  //id элементов, используемых в колонках
	foreach($ELM as $r) {
		if($pid = $r['parent_id'])
			if(!isset($ELM[$pid]))
				$parentC++;

		if($cid = _num($r['col']))
			if(!isset($ELM[$cid]))
				$colC++;
	}

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего элементов:<td class="w50 r b color-pay">'.$c.
		'<tr><td class="color-del">Некорректный ID родителя `parent_id`:<td class="r b red">'._hide0($parentC).
		'<tr><td class="color-del">Некорректный ID элемента-колонки:<td class="r b red">'._hide0($colC).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-del">Динамическая заливка в блоке:<td class="w50 r b red">'.PHP12_bug_element_block_bg($ELM).
		'<tr><td class="color-del">Параметры в шаблонах документов:<td class="r b red">'.PHP12_bug_element_template_prm($ELM).
		'<tr><td class="color-del">Данные фильтров:<td class="r b red">'.PHP12_bug_element_filter($ELM).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Диалоги:'.
		'<tr><td class="color-del">История действий:<td class="w50 r b red">'.PHP12_bug_element_dlg_history($ELM).
		'<tr><td class="color-del">Подмена значений через [42]:<td class="r b red">'.PHP12_bug_element_dlg_42($ELM).
		'<tr><td class="color-del">Колонка по умолчанию:<td class="r b red">'.PHP12_bug_element_dlg_col_def($ELM).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Действия:'.
		'<tr><td class="color-del">202 - Установка значения элементу (для элемента):<td class="w50 r b red">'.PHP12_bug_element_act202($ELM).
		'<tr><td class="color-del">203 - Блокировка элементов (для элемента):<td class="r b red">'.PHP12_bug_element_act203($ELM).
		'<tr><td class="color-del">206 - Установка фокуса (для элемента):<td class="r b red">'.PHP12_bug_element_act206($ELM).
		'<tr><td class="color-del">212 - Установка значения элементу (для блока):<td class="r b red">'.PHP12_bug_element_act212($ELM).
		'<tr><td class="color-del">213 - Блокировка элементов (для блока):<td class="r b red">'.PHP12_bug_element_act213($ELM).
		'<tr><td class="color-del">216 - Установка фокуса (для блока):<td class="r b red">'.PHP12_bug_element_act216($ELM).
		'<tr><td class="color-del">223 - Тёмная подсказка при наведении (для элемента):<td class="r b red">'.PHP12_bug_element_act223($ELM).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Глобальные счётчики:'.
		'<tr><td class="color-del">Настройка счётчика:<td class="w50 r b red">'.PHP12_bug_element_counter($ELM).
		'<tr><td class="color-del">Данные счётчика:<td class="r b red">'.PHP12_bug_element_counter_v($ELM).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Планировщик:'.
		'<tr><td class="color-del">Условия [40]:<td class="w50 r b red">'.PHP12_bug_element_cron_src($ELM).
		'<tr><td class="color-del">Значения для внесения:<td class="r b red">'.PHP12_bug_element_cron_dst($ELM).
	'</table>'.

	'<table class="_stab w100p mt10">'.
		'<tr><td class="color-555 b" colspan="2">Элементы в элементах:'.
		'<tr><td class="color-del">[7] Фильтр-поиск:                            <td class="w50 r b red">'.PHP12_bug_element_elem7($ELM).
		'<tr><td class="color-del">[11] Вставка значения:                       <td class="r b red">'.PHP12_bug_element_elem11($ELM).
		'<tr><td class="color-del">[14] Список-шаблон:                          <td class="r b red">'.PHP12_bug_element_elem14($ELM).
		'<tr><td class="color-del">[15] Количество строк списка:                <td class="r b red">'.PHP12_bug_element_elem15($ELM).
		'<tr><td class="color-del">[16] Radio - произвольные значения:          <td class="r b red">'.PHP12_bug_element_elem16($ELM).
		'<tr><td class="color-del">[23] Список-таблица:                         <td class="r b red">'.PHP12_bug_element_elem23($ELM).
		'<tr><td class="color-del">[29] Select: выбор записи из другого списка: <td class="r b red">'.PHP12_bug_element_elem29($ELM).
		'<tr><td class="color-del">[31] Выбор нескольких значений галочками:    <td class="r b red">'.PHP12_bug_element_elem31($ELM).
		'<tr><td class="color-del">[40] Фильтрование списка:                    <td class="r b red">'.PHP12_bug_element_elem40($ELM).
		'<tr><td class="color-del">[44] Сборный текст:                          <td class="r b red">'.PHP12_bug_element_elem44($ELM).
		'<tr><td class="color-del">[54] Количество значений привязанного списка:<td class="r b red">'.PHP12_bug_element_elem54($ELM).
		'<tr><td class="color-del">[55] Сумма значений привязанного списка:     <td class="r b red">'.PHP12_bug_element_elem55($ELM).
		'<tr><td class="color-del">[57] Меню переключения блоков:               <td class="r b red">'.PHP12_bug_element_elem57($ELM).
		'<tr><td class="color-del">[62] Фильтр: галочка:                        <td class="r b red">'.PHP12_bug_element_elem62($ELM).
		'<tr><td class="color-del">[72] Фильтр: год и месяц:                    <td class="r b red">'.PHP12_bug_element_elem72($ELM).
		'<tr><td class="color-del">[74] Фильтр: Radio:                          <td class="r b red">'.PHP12_bug_element_elem74($ELM).
		'<tr><td class="color-del">[77] Фильтр: календарь:                      <td class="r b red">'.PHP12_bug_element_elem77($ELM).
		'<tr><td class="color-del">[78] Фильтр: меню:                           <td class="r b red">'.PHP12_bug_element_elem78($ELM).
		'<tr><td class="color-del">[80] Очистка фильтра:                        <td class="r b red">'.PHP12_bug_element_elem80($ELM).
		'<tr><td class="color-del">[83] Фильтр: Select - привязанный список:    <td class="r b red">'.PHP12_bug_element_elem83($ELM).
		'<tr><td class="color-del">[85] Select - выбор значения списка по умолчанию:<td class="r b red">'.PHP12_bug_element_elem85($ELM).
		'<tr><td class="color-del">[86] Значение записи: количество дней:       <td class="r b red">'.PHP12_bug_element_elem86($ELM).
		'<tr><td class="color-del">[87] Циферка в меню страниц:                 <td class="r b red">'.PHP12_bug_element_elem87($ELM).
		'<tr><td class="color-del">[96] Количество значений связанного списка с учётом категорий:<td class="r b red">'.PHP12_bug_element_elem96($ELM).
		'<tr><td class="color-del">[102] Фильтр: Выбор нескольких групп значений:<td class="r b red">'.PHP12_bug_element_elem102($ELM).
	'</table>'.
	_bug_script($prm);
}

function PHP12_bug_element_block_bg($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT `id`,`bg`
			FROM `_block`
			WHERE `app_id`=".APP_ID."
			  AND LENGTH(`bg`)";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'bg');
}
function PHP12_bug_element_template_prm($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT `id`,`param_ids`
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `param_ids`";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'param_ids');
}
function PHP12_bug_element_filter($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_user_spisok_filter`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'element_id_spisok', true);
	$c += _bug_ids_count($arr, $ELM, 'element_id_filter', true);

	return _hide0($c);
}

function PHP12_bug_element_dlg_history($ELM) {//Диалоги - история действий
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'insert_history_elem', true);
	$c += _bug_ids_count($arr, $ELM, 'edit_history_elem', true);
	$c += _bug_ids_count($arr, $ELM, 'del_history_elem', true);

	return _hide0($c);
}
function PHP12_bug_element_dlg_42($ELM) {//Диалоги - Подмена значений через [42]
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `insert_unit_change_elem_id`";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'insert_unit_change_elem_id', true);
	$c += _bug_elm_ass($arr, $ELM, 'insert_unit_change_v', true);

	return _hide0($c);
}
function PHP12_bug_element_dlg_col_def($ELM) {//Диалоги - Колонка по умолчанию
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `spisok_elem_id`";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'spisok_elem_id');
}

function PHP12_bug_element_act202($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=202";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act203($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=203";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act206($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=206";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act212($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=212";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act213($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=213";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act216($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=216";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}
function PHP12_bug_element_act223($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=223";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'target_ids');
}

function PHP12_bug_element_counter($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_counter`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'sum_elem_id', true);
	$c += _bug_json_elm($arr, $ELM, 'filter', true);

	return _hide0($c);
}
function PHP12_bug_element_counter_v($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'element_id');
}

function PHP12_bug_element_cron_src($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_cron`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	return _bug_json_elm($arr, $ELM, 'src_prm');
}
function PHP12_bug_element_cron_dst($ELM) {
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_cron`
			WHERE `app_id`=".APP_ID;
	if(!$arr = query_arr($sql))
		return '';

	return _bug_elm_ass($arr, $ELM, 'dst_prm');
}

function PHP12_bug_element_elem7($ELM) {//Фильтр-поиск
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=7";
	if(!$arr = query_arr($sql))
		return '';

	$c =  _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem11($ELM) {//Вставка значения
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=11";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'txt_2');
}
function PHP12_bug_element_elem14($ELM) {//Список-шаблон
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=14
			  AND LENGTH(`txt_2`)";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_json_elm($arr, $ELM, 'txt_2');
}
function PHP12_bug_element_elem15($ELM) {//Количество строк списка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=15";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem16($ELM) {//Radio - произвольные значения
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=16
			  AND `num_3`";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_3');
}
function PHP12_bug_element_elem23($ELM) {//Список-таблица
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=23
			  AND LENGTH(`txt_2`)";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_10', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem29($ELM) {//Select: выбор записи из другого списка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=29";
	if(!$arr = query_arr($sql))
		return '';

	$c = _bug_ids_count($arr, $ELM, 'txt_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_5', true);

	return _hide0($c);
}
function PHP12_bug_element_elem31($ELM) {//Выбор нескольких значений галочками
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=31
			  AND `num_2`";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_2');
}
function PHP12_bug_element_elem40($ELM) {//Фильтрование списка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=40
			  AND `num_1`";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem44($ELM) {//Сборный текст
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=44
			  AND LENGTH(`txt_2`)";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'txt_2');
}
function PHP12_bug_element_elem54($ELM) {//количество значений привязанного списка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=54";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_1', true);

	return _hide0($c);
}
function PHP12_bug_element_elem55($ELM) {//Сумма значений привязанного списка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=55";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'num_2', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_1', true);

	return _hide0($c);
}
function PHP12_bug_element_elem57($ELM) {//Меню переключения блоков
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=57
			  AND LENGTH(`txt_2`)";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'txt_2');
}
function PHP12_bug_element_elem62($ELM) {//Фильтр: галочка
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=62";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem72($ELM) {//Фильтр: год и месяц
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=72";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'num_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem74($ELM) {//Фильтр: Radio
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=74";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem77($ELM) {//Фильтр: календарь
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=77";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem78($ELM) {//Фильтр: меню
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=78";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem80($ELM) {//Очистка фильтра
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=80";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem83($ELM) {//Фильтр: Select - привязанный список
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=83";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem85($ELM) {//Select - выбор значения списка по умолчанию
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=85";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem86($ELM) {//Значение записи: количество дней
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=86";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_ids_count($arr, $ELM, 'num_1');
}
function PHP12_bug_element_elem87($ELM) {//Циферка в меню страниц
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=87";
	if(!$arr = query_arr($sql))
		return '';

	return _bug_json_elm($arr, $ELM, 'txt_1');
}
function PHP12_bug_element_elem96($ELM) {//Количество значений связанного списка с учётом категорий
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=96";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);

	return _hide0($c);
}
function PHP12_bug_element_elem102($ELM) {//Фильтр: Выбор нескольких групп значений
	if(empty($ELM))
		return '';

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=102";
	if(!$arr = query_arr($sql))
		return '';

	$c  = _bug_ids_count($arr, $ELM, 'num_1', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_2', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_3', true);
	$c += _bug_ids_count($arr, $ELM, 'txt_4', true);
	$c += _bug_json_elm($arr, $ELM, 'txt_5', true);

	return _hide0($c);
}

function PHP12_bug_element_hint() {
	return '';
	$sql = "SELECT *
			FROM `__hint`
			WHERE `app_id`=".APP_ID;
	$HINT = query_arr($sql);

	$sql = "SELECT `id`,1
			FROM `_element`
			WHERE `app_id`=".APP_ID;
	$ass = query_arr($sql);

	$lost = 0;
	foreach($HINT as $r)
		if(empty($ass[$r['element_id']]))
			$lost++;

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего подсказок:<td class="w50 r b color-pay">'._hide0(count($HINT)).
		'<tr><td class="color-del">Подсказки от потерянных элементов:<td class="w50 r b red">'._hide0($lost).
	'</table>';
}





/* ---=== ДЕЙСТВИЯ ===--- */
function PHP12_bug_action($prm) {
	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".APP_ID;
	$ACT = query_arr($sql);

	$cnnC = 0;
	foreach($ACT as $r)
		if(!$r['block_id'] && !$r['element_id'])
			$cnnC++;

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего действий:<td class="w50 r b color-pay">'.count($ACT).
		'<tr><td class="color-del">Нет привязки к элементу или блоку:<td class="r b red">'._hide0($cnnC).
		'<tr><td class="color-del">Привязка к потерянным блокам:<td class="r b red">'.PHP12_bug_action_blk().
		'<tr><td class="color-del">Привязка к потерянным элементам:<td class="r b red">'.PHP12_bug_action_elm().
	'</table>'.
	_bug_script($prm);
}
function PHP12_bug_action_blk() {
	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `block_id`
			  AND `block_id` NOT IN (
				SELECT `id`
				FROM `_block`
				WHERE `app_id`=".APP_ID."
			  )";
	return _hide0(query_value($sql));
}
function PHP12_bug_action_elm() {
	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `app_id`=".APP_ID."
			  AND `element_id`
			  AND `element_id` NOT IN (
				SELECT `id`
				FROM `_element`
				WHERE `app_id`=".APP_ID."
			  )";
	return _hide0(query_value($sql));
}





/* ---=== ГЛОБАЛЬНЫЕ СЧЁТЧИКИ ===--- */
function PHP12_bug_counter($prm) {
	$sql = "SELECT *
			FROM `_counter`
			WHERE `app_id`=".APP_ID;
	$COUNTER = query_arr($sql);

	$sql = "SELECT COUNT(*)
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID;
	$COUNTER_V = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего счётчиков:<td class="w50 r b color-pay">'.count($COUNTER).
		'<tr><td class="grey">Кол-во записей по счётчикам:<td class="r pale">'._hide0($COUNTER_V).
		'<tr><td class="color-del">Кол-во записей от удалённых счётчиков:<td class="r b red">'.PHP12_bug_counter_v_lost().
	'</table>'.
	_bug_script($prm);
}
function PHP12_bug_counter_v_lost() {
	$sql = "SELECT COUNT(*)
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID."
			  AND `counter_id`
			  AND `counter_id` NOT IN (
				SELECT `id`
				FROM `_counter`
				WHERE `app_id`=".APP_ID."
			  )";
	return _hide0(query_value($sql));
}





/* ---=== ПЛАНИРОВЩИК ===--- */
function PHP12_bug_cron($prm) {
	$sql = "SELECT *
			FROM `_cron`
			WHERE `app_id`=".APP_ID;
	$CRON = query_arr($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего заданий:<td class="w50 r b color-pay">'.count($CRON).
	'</table>'.
	_bug_script($prm);
}





/* ---=== ИЗОБРАЖЕНИЯ ===--- */
function PHP12_bug_image($prm) {
	$sql = "SELECT COUNT(*)
			FROM `_image`
			WHERE `app_id`=".APP_ID;
	$IMG = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего изображений:<td class="w70 r b color-pay">'.$IMG.
	'</table>'.
	_bug_script($prm);
}





/* ---=== ФАЙЛЫ ===--- */
function PHP12_bug_attach($prm) {
	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID;
	$ATTACH = query_arr($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего файлов:<td class="w50 r b color-pay">'.count($ATTACH).
	'</table>'.
	_bug_script($prm);
}





/* ---=== ШАБЛОНЫ ДОКУМЕНТОВ ===--- */
function PHP12_bug_template($prm) {
	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID;
	$TMP = query_arr($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего шаблонов:<td class="w50 r b color-pay">'.count($TMP).
	'</table>'.
	_bug_script($prm);
}





/* ---=== ЗАМЕТКИ ===--- */
function PHP12_bug_note($prm) {
	$sql = "SELECT COUNT(*)
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`";
	$NOTE = query_value($sql);

	$sql = "SELECT COUNT(*)
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND `parent_id`";
	$COMM = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего заметок:<td class="w70 r b color-pay">'.$NOTE.
		'<tr><td class="grey b">Комментарии к заметкам:<td class="w70 r b color-pay">'.$COMM.
	'</table>'.
	_bug_script($prm);
}





/* ---=== ИСТОРИЯ ДЕЙСТВИЙ ===--- */
function PHP12_bug_history($prm) {
	$sql = "SELECT COUNT(*)
			FROM `_history`
			WHERE `app_id`=".APP_ID;
	$HIST = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего записей истории:<td class="w70 r b color-pay">'.$HIST.
	'</table>'.
	_bug_script($prm);
}





/* ---=== ДАННЫЕ ===--- */
function PHP12_bug_spisok($prm) {
	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `app_id`=".APP_ID;
	$SPISOK = query_value($sql);

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего записей:<td class="w70 r b color-pay">'._sumSpace($SPISOK).
	'</table>'.
	_bug_script($prm);
}
























