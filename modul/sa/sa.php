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

	//элементы страницы
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$page_id."
			ORDER BY `sort`";
	foreach(query_arr($sql) as $id => $r) {
		switch($r['table_id']) {
			case 5://menu
				$send .=
					'<div class="'.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
						_pageElementMenu($r['unit_id']).
					'</div>';
				break;
			default:
				switch($r['dialog_id']) {
					case 2://button
						$send .=
							'<div class="pad5 '.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
								_button(array(
									'name' => $r['txt_1'],
									'click' => '_dialogOpen('._dialogValToId('button'.$id).')',
									'color' => 'green'
								)).
							'</div>';
					break;
					case 4://head
						$send .=
							'<div class="'.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
								'<div class="hd2">'.$r['txt_1'].'</div>'.
							'</div>';
						break;
					case 7://search
						$send .=
							'<div class="'.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
								_search(array(
											'txt' => $r['txt_1'],
											'grey' => $r['num_1'],
											'width' => $r['num_2']
										)).
							'</div>';
						break;
					case 9://link
						$send .=
							'<div class="'.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
								'<a href="'.URL.'&p='.$r['num_1'].'">'.
										$r['txt_1'].
								 '</a>'.
							'</div>';
						break;
					case 14://_spisok
						$dialog = _dialogQuery($r['dialog_id']);
						$sql = "SELECT *
								FROM `_spisok`
								WHERE `app_id`=".APP_ID."
								  AND `dialog_id`=13
								ORDER BY `dtime_add` DESC
								LIMIT ".$dialog['v_ass'][$r['num_2']];
						$html = '';
						if($spisok = query_arr($sql)) {
							foreach($spisok as $sp) {
								$html .=
									'<div class="pad5">'.
										'<a>'.$sp['txt_1'].'</a>, '.$sp['txt_2'].
									'</div>';
							}
						}else
							$html = '<div class="_empty">'.$r['txt_1'].'</div>';
						$send .=
							'<div class="'.$r['cls']._pasClass($r).'"'._pasId($id).'>'.
								$html.
							'</div>';
						break;
				}
		}
	}


	return '<div class="pas_sort">'.$send.'</div>';
}
function _pasClass($r) {//стили для редактирования элемента при условии включенного флага PAS
	if(!PAS)
		return '';

	return ' over3 pas pas_'.$r['dialog_id'].'_'.$r['id'];
}
function _pasId($id) {//id элемента страницы для сотрировки
	if(!PAS)
		return '';

	return ' val="'.$id.'"';
}
function _pageElementMenu($id) {//элемент страницы: Меню
	$sql = "SELECT *
			FROM `_page_menu`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if(!$menu = query_assoc($sql))
		return 'Несуществующее меню.';

	$sql = "SELECT *
			FROM `_page_menu_razdel`
			WHERE `app_id`=".APP_ID."
			  AND `menu_id`=".$id;
	if(!$spisok = query_arr($sql))
		return 'Не разделов меню.';

	$razdel = '';
	foreach($spisok as $r) {
		$sel = PAGE_ID == $r['uid'] ? ' sel' : '';
		$href = $r['uid'] ? ' href="'.URL.'&p='.$r['uid'].'"' : '';
		$razdel .=
			'<a class="link'.$sel.'"'.$href.'>'.
				$r['name'].
			'</a>';
	}

	return '<div class="_menu0">'.$razdel.'</div>';
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



