<?php
function sa_page() {//страницы приложения
	return
	'<div class="mar10">'.
		'<div class="hd2">Страницы приложения'.
			'<button class="vk small green fr" onclick="_dialogOpen(1)">Добавить страницу</button>'.
		'</div>'.
		'<div id="spisok">'.sa_page_spisok().'</div>'.
	'</div>';
}
function sa_page_spisok() {
	$sql = "SELECT *
			FROM `_page`
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th>Название'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$send .=
				'<tr><td class="r grey">'.$r['id'].
					'<td><a href="'.URL.'&p='.$r['id'].'">'.$r['name'].'</a>'.
					'<td class="wsnw">'
//						._iconEdit(array('onclick'=>'_dialogOpen(1,'.$r['id'].')'))
						._iconEdit(array('class'=>'spisok-edit') + $r)
						._iconDel();
	}

	$send .= '</table>';

	return $send;
}


function _page_show($page_id) {
	$send = '';

	//заголовки
	$sql = "SELECT *
			FROM `_page_head`
			WHERE `app_id`=".APP_ID."
			  AND `page_id`=".$page_id."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$send .=
			'<div class="hd2">'.$r['name'].'</div>';
	}

	//поиск
	$sql = "SELECT *
			FROM `_page_search`
			WHERE `app_id`=".APP_ID."
			  AND `page_id`=".$page_id."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$send .=
			_search(array(
				'txt' => $r['txt']
			));
	}

	//кнопки
	$sql = "SELECT *
			FROM `_page_button`
			WHERE `app_id`=".APP_ID."
			  AND `page_id`=".$page_id."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$send .=
			'<div class="pad5">'.
				_button(array(
					'name' => $r['name'],
					'click' => '_dialogOpen('._dialogValToId('button'.$r['id']).')',
					'color' => 'green'
				)).
			'</div>';
	}

	return
	'<div class="">'.$send.'</div>';
}