<?php
/*
	Файл, содержащий фукнции отслеживания ошибок в приложении
	Страница 132
*/

function _bug_ids_count($arr, $OBJ, $col) {//количество отсутствующих идентификаторов
	$c = 0;
	foreach($arr as $r)
		if($ids = _ids($r[$col], 'arr'))
			foreach($ids as $id)
				if(!isset($OBJ[$id]))
					$c++;
	return _hide0($c);
}

function PHP12_bug_page() {//страницы
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
	'</table>';
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

	$sql = "SELECT COUNT(*)
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `page_id` NOT IN ("._idsGet($PG).")";
	return _hide0(query_value($sql));
}






function PHP12_bug_dialog() {//Диалоги
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.",0)";
	$DLG = query_arr($sql);

	$c = 0;
	$parentC = 0;
	$uGetC = 0;
	$actC = 0;
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

		if($actId = $r['element_action_dialog_id'])
			if(!isset($DLG[$actId]))
				$actC++;
	}

	return
	'<table class="_stab w100p">'.
		'<tr><td class="grey b">Всего диалогов:<td class="w50 r b color-pay">'._hide0($c).
		'<tr><td class="color-del">Некорректный ID родителя `dialog_id_parent`:<td class="r b red">'._hide0($parentC).
		'<tr><td class="color-del">Некорректный `dialog_id_unit_get` (принимает данные записи):<td class="r b red">'._hide0($uGetC).
//		'<tr><td class="color-del">Некорректный `element_action_dialog_id` (действие для элемента):<td class="r b red">'._hide0($actC).
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
		'<tr><td class="color-del">222 - открытие диалога (клик по элементу):<td class="r b red">'.PHP12_bug_dialog_222act($DLG).
	'</table>';
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






function PHP12_bug_block() {
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
		'<tr><td class="color-555 b" colspan="2">Действия:'.
		'<tr><td class="color-del">Действия для блоков:<td class="w50 r b red">'.PHP12_bug_block_action().
		'<tr><td class="color-del">201 - скрытие/показ блоков (для элемента):<td class="r b red">'.PHP12_bug_block_act201($BLK).
		'<tr><td class="color-del">211 - скрытие/показ блоков (для блоков):<td class="r b red">'.PHP12_bug_block_act211($BLK).
	'</table>';
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
function PHP12_bug_block_action() {
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






function PHP12_bug_element() {
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
	'</table>';
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
















