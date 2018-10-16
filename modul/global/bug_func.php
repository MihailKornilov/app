<?php
/*
	Файл, содержащий фукнции отслеживания ошибок в приложении
	Страница 132
*/


function PHP12_BUG_block_page_lost() {//потерянные блоки от несуществующих (удалённых) страниц
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
		if(SA && @$_GET['block-lost-del']) {
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
		'<button class="vk small red'._dn(!@$_GET['block-lost-del'], '_busy').'" onclick="location.href=\''.URL.'&p='._page('cur').'&block-lost-del=1\'">'.
			'Удалить потерянные блоки страниц'.
		'</button>'.
	'</div>'
: '');
}



