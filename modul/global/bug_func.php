<?php
/*
	Файл, содержащий фукнции отслеживания ошибок в приложении
	Страница 132
*/


function PHP12_BUG_block_page_lost() {//потерянные блоки от несуществующих (удалённых) страниц
	$getv = 'page-block-lost-del';//переменная для GET

	$sql = "SELECT COUNT(*) FROM `_page`";
	$pageCount = query_value($sql);

	$sql = "SELECT COUNT(DISTINCT `obj_id`)
			FROM `_block`
			WHERE `obj_name`='page'";
	$pageBlkDstCount = query_value($sql);

	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `obj_name`='page'";
	$pageBlkCount = query_value($sql);


	$blkLostCount = 0;//количество потерянных блоков

	$sql = "SELECT id FROM (
				SELECT
					DISTINCT b.obj_id `id`,
					COUNT(p.id) `c`
				FROM _block b
					LEFT JOIN _page p
					ON b.obj_id=p.id
				WHERE b.obj_name='page'
				GROUP BY b.obj_id
				ORDER BY b.obj_id
			) t
			WHERE !`c`";
	if($pageDelIds = query_ids($sql)) {

		//удаление потерянных блоков
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_block`
					WHERE `obj_name`='page'
					  AND `obj_id` IN (".$pageDelIds.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}

		$sql = "SELECT COUNT(*)
				FROM `_block`
				WHERE `obj_name`='page'
				  AND `obj_id` IN (".$pageDelIds.")";
		$blkLostCount = query_value($sql);
	}

	return
	'<div class="b fs14 color-555">Блоки со страниц:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех страниц:<td class="r b">'.$pageCount.
		'<tr><td class="grey">Кол-во страниц, заполненных блоками:<td class="r">'.$pageBlkDstCount.
		'<tr><td class="grey">Кол-во всех блоков на страницах:<td class="r">'.$pageBlkCount.
($blkLostCount ?
		'<tr><td class="color-ref">Кол-во удалённых страниц, от которых остались блоки:<td class="r red">'._ids($pageDelIds, 'count_empty').
		'<tr><td class="color-ref">Кол-во потерянных блоков:<td class="r b red">'._empty($blkLostCount)
: '').
	'</table>'.

($blkLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
			'Удалить потерянные блоки страниц'.
		'</button>'.
	'</div>'
: '');
}

function PHP12_BUG_block_dialog_lost() {//потерянные блоки от несуществующих (удалённых) диалоговых окон
	$getv = 'dlg-block-lost-del';

	$sql = "SELECT COUNT(*) FROM `_dialog`";
	$dlgCount = query_value($sql);

	//Кол-во диалогов, заполненных блоками - содержание
	$sql = "SELECT COUNT(DISTINCT `obj_id`)
			FROM `_block`
			WHERE `obj_name`='dialog'";
	$dlgBlkDstCount = query_value($sql);

	//Кол-во диалогов, заполненных блоками - содержание удаления
	$sql = "SELECT COUNT(DISTINCT `obj_id`)
			FROM `_block`
			WHERE `obj_name`='dialog_del'";
	$dlgDelBlkDstCount = query_value($sql);

	//Кол-во блоков во всех диалогах
	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `obj_name`='dialog'";
	$dlgBlkCount = query_value($sql);

	//Кол-во блоков во всех диалогах
	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `obj_name`='dialog_del'";
	$dlgDelBlkCount = query_value($sql);

	$reload = 0;//флаг перезагрузки страницы, если блоки удалялись

	//потерянные блоки - содержание
	$blkLostCount = 0;//количество потерянных блоков
	$sql = "SELECT id FROM (
				SELECT
					DISTINCT b.obj_id `id`,
					COUNT(dlg.id) `c`
				FROM _block b
					LEFT JOIN _dialog dlg
					ON b.obj_id=dlg.id
				WHERE b.obj_name='dialog'
				GROUP BY b.obj_id
				ORDER BY b.obj_id
			) t
			WHERE !`c`";
	if($dlgLostIds = query_ids($sql)) {
		//удаление потерянных блоков
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_block`
					WHERE `obj_name`='dialog'
					  AND `obj_id` IN (".$dlgLostIds.")";
			query($sql);
			$reload = 1;
		}

		$sql = "SELECT COUNT(*)
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `obj_id` IN (".$dlgLostIds.")";
		$blkLostCount = query_value($sql);
	}

	//потерянные блоки - содержание удаления
	$blkDelLostCount = 0;//количество потерянных блоков
	$sql = "SELECT id FROM (
				SELECT
					DISTINCT b.obj_id `id`,
					COUNT(dlg.id) `c`
				FROM _block b
					LEFT JOIN _dialog dlg
					ON b.obj_id=dlg.id
				WHERE b.obj_name='dialog_del'
				GROUP BY b.obj_id
				ORDER BY b.obj_id
			) t
			WHERE !`c`";
	if($dlgDelLostIds = query_ids($sql)) {
		//удаление потерянных блоков
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_block`
					WHERE `obj_name`='dialog_del'
					  AND `obj_id` IN (".$dlgDelLostIds.")";
			query($sql);
			$reload = 1;
		}

		$sql = "SELECT COUNT(*)
				FROM `_block`
				WHERE `obj_name`='dialog_del'
				  AND `obj_id` IN (".$dlgDelLostIds.")";
		$blkDelLostCount = query_value($sql);
	}

	if($reload) {
		_debug_cache_clear();
		header('Location:'.URL.'&p='._page('cur'));
	}

	return
	'<div class="b fs14 color-555">Блоки из диалоговых окон:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех диалогов:<td class="r b">'.$dlgCount.'<td>'.
		'<tr><td class="grey">Кол-во диалогов, заполненных блоками:'.
			'<td class="r">'.$dlgBlkDstCount.
			'<td class="r w35'._tooltip('Содержание удаления', -60)._empty($dlgDelBlkDstCount).
		'<tr><td class="grey">Кол-во блоков во всех диалогах:'.
			'<td class="r">'.$dlgBlkCount.
			'<td class="r'._tooltip('Содержание удаления', -60)._empty($dlgDelBlkCount).
($blkLostCount || $blkDelLostCount ?
		'<tr><td class="color-ref">Кол-во удалённых диалогов, от которых остались блоки:'.
			'<td class="r red">'._ids($dlgLostIds, 'count_empty').
			'<td class="r red">'._ids($dlgDelLostIds, 'count_empty').
		'<tr><td class="color-ref">Кол-во потерянных блоков:'.
			'<td class="r b red">'._empty($blkLostCount).
			'<td class="r b red">'._empty($blkDelLostCount)
: '').
	'</table>'.

($blkLostCount || $blkDelLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
			'Удалить потерянные блоки диалогов'.
		'</button>'.
	'</div>'
: '');
}

function PHP12_BUG_block_spisok_lost() {//потерянные блоки от несуществующих (удалённых) списков
	$getv = 'spisok-block-lost-del';//переменная для GET

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id` IN (14,59)";
	$spisokCount = query_value($sql);

	//Кол-во списков, заполненных блоками
	$sql = "SELECT COUNT(DISTINCT `obj_id`)
			FROM `_block`
			WHERE `obj_name`='spisok'";
	$spisokBlkDstCount = query_value($sql);

	//Кол-во блоков во всех списках
	$sql = "SELECT COUNT(*)
			FROM `_block`
			WHERE `obj_name`='spisok'";
	$spisokBlkCount = query_value($sql);

	$blkLostCount = 0;//количество потерянных блоков

	$sql = "SELECT id FROM (
				SELECT
					DISTINCT b.obj_id `id`,
					COUNT(el.id) `c`
				FROM _block b
					LEFT JOIN _element el
					ON b.obj_id=el.id
				   AND `el`.`dialog_id` IN (14,59)
				WHERE b.obj_name='spisok'
				GROUP BY b.obj_id
				ORDER BY b.obj_id
			) t
			WHERE !`c`";
	if($spisokDelIds = query_ids($sql)) {

		//удаление потерянных блоков
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_block`
					WHERE `obj_name`='spisok'
					  AND `obj_id` IN (".$spisokDelIds.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}

		$sql = "SELECT COUNT(*)
				FROM `_block`
				WHERE `obj_name`='spisok'
				  AND `obj_id` IN (".$spisokDelIds.")";
		$blkLostCount = query_value($sql);
	}

	return
	'<div class="b fs14 color-555">Блоки из списков:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех списков:<td class="r b">'.$spisokCount.
		'<tr><td class="grey">Кол-во списков, заполненных блоками:<td class="r">'.$spisokBlkDstCount.
		'<tr><td class="grey">Кол-во блоков во всех списках:<td class="r">'.$spisokBlkCount.
($blkLostCount ?
		'<tr><td class="color-ref">Кол-во удалённых списков, от которых остались блоки:<td class="r red">'._ids($spisokDelIds, 'count_empty').
		'<tr><td class="color-ref">Кол-во потерянных блоков:<td class="r b red">'._empty($blkLostCount)
: '').
	'</table>'.

($blkLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
			'Удалить потерянные блоки списков'.
		'</button>'.
	'</div>'
: '');
}

function PHP12_BUG_elem_in_block_lost() {//элементы, оставшиеся без блоков
	$getv = 'elem-lost';//переменная для GET

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `block_id`>0";
	$elmCount = query_value($sql);

	$sql = "SELECT `id`
			FROM (
				SELECT
					el.`id`,
					IFNULL(bl.id,0) `blid`
				FROM _element el
					LEFT JOIN _block bl
					ON bl.id=el.block_id
				WHERE el.block_id>0
				ORDER BY el.id
			) t
			WHERE !`blid`";
	if($elmLost = query_ids($sql)) {
		//удаление потерянных элементов
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_element`
					WHERE `id` IN (".$elmLost.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}
	}

	return
	'<div class="b fs14 color-555">Элементы, размещаемые в блоках:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех элементов с блоками:<td class="r b">'.$elmCount.
($elmLost ?
		'<tr><td class="color-ref">Кол-во элементов с несуществующими блоками:<td class="r red">'._ids($elmLost, 'count_empty')
: '').
	'</table>'.

($elmLost ?
	'<div class="center mt10">'.
		'<button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
			'Удалить элементы без блоков'.
		'</button>'.
	'</div>'
: '');
}

function PHP12_BUG_elm_child_without_parent() {//дочерние элементы без родителя
	$getv = 'elem-parent-lost';//переменная для GET

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `parent_id`";
	$elmCount = query_value($sql);

	//элементы-родители
	$sql = "SELECT DISTINCT `parent_id`
			FROM `_element`
			WHERE `parent_id`";
	$elmParentIds = query_ids($sql);

	$lost = array();

	$sql = "SELECT `id`
			FROM `_element`
			WHERE `id` IN (".$elmParentIds.")";
	$ass = _idsAss(query_ids($sql));
	foreach(_ids($elmParentIds, 'arr') as $id)
		if(!isset($ass[$id]))
			$lost[] = $id;

	$lost = implode(',', $lost);

	$childLostCount = 0;

	if($lost) {
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_element`
					WHERE `parent_id` IN (".$lost.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}
		$sql = "SELECT COUNT(*)
				FROM `_element`
				WHERE `parent_id` IN (".$lost.")";
		$childLostCount = query_value($sql);
	}

	return
	'<div class="b fs14 color-555">Дочерние элементы:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех дочерних элементов:<td class="r b">'.$elmCount.
($childLostCount ?
		'<tr><td class="color-ref">Кол-во элементов с несуществующими родителями:<td class="r red">'._empty($childLostCount)
: '').
	'</table>'.

($childLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
			'Удалить элементы без родителей'.
		'</button>'.
	'</div>'
: '');
}

function PHP12_BUG_elm_dialog_history_lost() {//элементы истории действий
	$getv = 'elem-dlg-hist-lost';//переменная для GET

	$dlgHist = array();
	$sql = "SELECT * FROM `_dialog`";
	foreach(query_arr($sql) as $r) {
		$dlgHist[] = $r['insert_history_elem'];
		$dlgHist[] = $r['edit_history_elem'];
		$dlgHist[] = $r['del_history_elem'];
	}
	$dlgHist = array_diff($dlgHist, array(''));
	$dlgHist = implode(',', $dlgHist);

	define('ELM_DLG_HIST', $dlgHist);

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `id` IN (".$dlgHist.")";
	$histExist = query_value($sql);

	return
	'<div class="b fs14 color-555">Элементы для настройки истории действий:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey">Кол-во ID элементов истории в таблице _dialog:<td class="r b">'._ids($dlgHist, 'count').
		'<tr><td class="grey">Существующие элементы истории:<td class="r">'.$histExist.
	'</table>';
}

function PHP12_BUG_elm_unit_del_setup() {//элементы, используемые отдельно для дополнительных настроек
	$getv = 'elem-unit-del-setup';//переменная для GET

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id`=58";
	$elm58Count = query_value($sql);

	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id`=26";
	$elm26Count = query_value($sql);

	$sql = "SELECT `id`
			FROM `_element`
			WHERE `id` NOT IN (".ELM_DLG_HIST.")
			  AND `block_id`<=0
			  AND !`parent_id`
			  AND `dialog_id`!=58
			  AND `dialog_id`!=26";
	if($lost = query_ids($sql)) {
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_element`
					WHERE `id` IN (".$lost.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}
	}

	return
	'<div class="b fs14 color-555">Элементы для настройки удаления записи:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey">Кол-во всех элементов настройки удаления записи:<td class="r b">'.$elm58Count.
		'<tr><td class="grey">Кол-во всех элементов настройки дополнительных условий отображения списка:<td class="r b">'.$elm26Count.
	'</table>'.

($lost ?
	'<table class="_stab mt5">'.
		'<tr><td class="color-ref">Потерянные элементы:'.
			'<td class="r red">'._ids($lost, 'count').
			'<td><button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
					'Удалить'.
				'</button>'.
	'</table>'
: '');
}

function PHP12_BUG_elm_func_lost() {//Функции, привязанные к элементам
	$getv = 'elem-func-lost';//переменная для GET

	$sql = "SELECT COUNT(*) FROM `_element_func`";
	$funcCount = query_value($sql);

	$sql = "SELECT `id`
			FROM (
				SELECT
					f.`id`,
					IFNULL(el.id,0) `elid`
				FROM _element_func f
					LEFT JOIN _element el
					ON el.id=f.element_id
				ORDER BY el.id
			) t
			WHERE !`elid`";
	if($funcLost = query_ids($sql)) {
		if(SA && @$_GET[$getv]) {
			$sql = "DELETE
					FROM `_element_func`
					WHERE `id` IN (".$funcLost.")";
			query($sql);
			_debug_cache_clear();
			header('Location:'.URL.'&p='._page('cur'));
		}
	}

	return
	'<div class="b fs14 color-555">Функции, привязанные к элементам:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех функций:<td class="r b">'.$funcCount.
($funcLost ?
		'<tr><td class="grey color-ref">Функции без элементов:'.
			'<td class="r red">'._ids($funcLost, 'count').
			'<td><button class="vk small red" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\';$(this).addClass(\'_busy\')">'.
					'Удалить'.
				'</button>'
: '').
	'</table>';
}



