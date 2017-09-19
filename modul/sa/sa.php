<?php
function sa_page_spisok() {
	$sql = "SELECT *
			FROM `_page`
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th>Название'.
				'<th class="w200">Функция'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$send .=
				'<tr><td class="r grey">'.$r['id'].
					'<td><a href="'.URL.'&p='.$r['id'].'">'.$r['name'].'</a>'.
					'<td>'.$r['func'].
					'<td class="wsnw">'
						._iconEdit(array('onclick'=>'_dialogOpen(1,'.$r['id'].')'))
//						._iconEdit(array('class'=>'spisok-edit') + $r)
						._iconDel();
	}

	$send .= '</table>';

	return $send;
}






function _page_show($page_id) {//отображение содержания страницы
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

	//ссылки
	$sql = "SELECT *
			FROM `_page_link`
			WHERE `app_id`=".APP_ID."
			  AND `page_id`=".$page_id."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$send .= '<a href="'.URL.'&p='.$r['link_id'].'" class="db ml20">'.
					$r['name'].
				 '</a>';
	}

	return
	'<div class="">'.$send.'</div>';
}

function _page_menu_spisok() {//список меню
	$sql = "SELECT
				*,
				'' `razdel`
			FROM `_page_menu`
			ORDER BY `id`";
	$spisok = query_arr($sql);

	$sql = "SELECT
				*,
				'' `razdel`
			FROM `_page_menu_razdel`
			ORDER BY `sort`";
	$razdel = query_arr($sql);
	foreach($razdel as $r) {
		$spisok[$r['menu_id']]['razdel'][] = $r;
	}

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th class="w200">Название'.
				'<th>Разделы'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$razdel = '';
		if($r['razdel']) {
			foreach($r['razdel'] as $rz) {
				$razdel .=
					'<div>'.
						'<a onclick="_dialogOpen('._dialogValToId('page_menu_razdel').','.$rz['id'].')">'.
							$rz['name'].
						'<a>'.
					'</div>';
			}
		}
		$send .=
				'<tr><td class="r grey topi">'.$r['id'].
					'<td class="b topi">'.$r['name'].
					'<td>'.$razdel.
					'<td class="wsnw">'.
						'<div onclick="_dialogOpen('._dialogValToId('page_menu_razdel').',0,'.$r['id'].')" class="icon icon-avai'._tooltip('Добавить раздел', -94, 'r').'</div>'.
						_iconEdit(array('onclick'=>'_dialogOpen('.$r['dialog_id'].','.$r['id'].')')).
						_iconDel();
	}

	$send .= '</table>';

	return $send;
}



