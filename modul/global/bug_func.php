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
	'<div class="b fs14 color-555">Потерянные блоки со страниц:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех страниц:<td class="r b">'.$pageCount.
		'<tr><td class="grey">Кол-во страниц, заполненных блоками:<td class="r">'.$pageBlkDstCount.
		'<tr><td class="grey">Кол-во всех блоков на страницах:<td class="r">'.$pageBlkCount.
		'<tr><td class="grey">Кол-во удалённых страниц, от которых остались блоки:<td class="r red">'._ids($pageDelIds, 'count_empty').
		'<tr><td class="grey">Кол-во потерянных блоков:<td class="r b red">'._empty($blkLostCount).
	'</table>'.

($blkLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red'._dn(!@$_GET[$getv], '_busy').'" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\'">'.
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
	'<div class="b fs14 color-555">Потерянные блоки из диалоговых окон:</div>'.
	'<table class="_stab mt5">'.
		'<tr><td class="grey b">Кол-во всех диалогов:<td class="r b">'.$dlgCount.'<td>'.
		'<tr><td class="grey">Кол-во диалогов, заполненных блоками:'.
			'<td class="r">'.$dlgBlkDstCount.
			'<td class="r">'._empty($dlgDelBlkDstCount).
		'<tr><td class="grey">Кол-во блоков во всех диалогах:'.
			'<td class="r">'.$dlgBlkCount.
			'<td class="r">'._empty($dlgDelBlkCount).
		'<tr><td class="grey">Кол-во удалённых диалогов, от которых остались блоки:'.
			'<td class="r red">'._ids($dlgLostIds, 'count_empty').
			'<td class="r red">'._ids($dlgDelLostIds, 'count_empty').
		'<tr><td class="grey">Кол-во потерянных блоков:'.
			'<td class="r b red">'._empty($blkLostCount).
			'<td class="r b red">'._empty($blkDelLostCount).
	'</table>'.

($blkLostCount || $blkDelLostCount ?
	'<div class="center mt10">'.
		'<button class="vk small red'._dn(!@$_GET[$getv], '_busy').'" onclick="location.href=\''.URL.'&p='._page('cur').'&'.$getv.'=1\'">'.
			'Удалить потерянные блоки диалогов'.
		'</button>'.
	'</div>'
: '');
}



