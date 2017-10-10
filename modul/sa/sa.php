<?php
function sa_page_spisok() {
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `dialog_id`=1
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th>Название'.
				'<th class="w70">App any'.
				'<th class="w70">SA only'.
				'<th class="w200">Функция'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$send .=
				'<tr><td class="r grey">'.$r['id'].
					'<td><a href="'.URL.'&p='.$r['id'].'">'.$r['name'].'</a>'.
					'<td class="'.($r['app_id'] ? '' : 'bg-dfd').'">'.
					'<td class="'.($r['sa'] ? 'bg-ccd' : '').'">'.
					'<td>'.$r['func'].
					'<td class="wsnw">'
						._iconEdit(array('onclick'=>'_dialogOpen('.$r['dialog_id'].','.$r['id'].')'))
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
					'<div class="'.$r['cls']._pasClass().'"'._pasId($r).'>'.
						_pageElementMenu($r['unit_id']).
					'</div>';
				break;
			default:
				switch($r['dialog_id']) {
					case 2://button
						$color = array(
							0 => '',        //Синий - по умолчанию
							321 => '',      //Синий
							322 => 'green', //Зелёный
							323 => 'red',   //Красный
							324 => 'grey',  //Серый
							325 => 'cancel',//Прозрачный
							326 => 'pink',  //Розовый
							327 => 'orange' //Оранжевый
						);
						$send .=
							'<div class="pad5 '.$r['cls']._pasClass().'"'._pasId($r).'>'.
								_button(array(
									'name' => $r['txt_1'],
									'click' => '_dialogOpen('._dialogValToId('button'.$id).')',
									'color' => $color[$r['num_1']],
									'small' => $r['num_2']
								)).
							'</div>';
					break;
					case 4://head
						$send .=
							'<div class="'.$r['cls']._pasClass().'"'._pasId($r).'>'.
								'<div class="hd2">'.$r['txt_1'].'</div>'.
							'</div>';
						break;
					case 7://search
						$send .=
							'<div class="'.$r['cls']._pasClass().'"'._pasId($r).'>'.
								_search(array(
											'hold' => $r['txt_1'],
											'grey' => $r['num_1'],
											'width' => $r['num_2'],
											'v' => $r['v']
										)).
							'</div>';
						break;
					case 9://link
						$send .=
							'<div class="'.$r['cls']._pasClass().'"'._pasId($r).'>'.
								'<a href="'.URL.'&p='.$r['num_1'].'">'.
										$r['txt_1'].
								 '</a>'.
							'</div>';
						break;
					case 14://_spisok
						$send .=
							'<div class="'.$r['cls']._pasClass().'"'._pasId($r).'>'.
								_pageSpisok($r).
							'</div>';
						break;
				}
		}
	}


	return
	'<div class="pas_sort">'.
		$send.
	'</div>'.
	'<script>_pageShow()</script>';
}
function _pasClass() {//стили для редактирования элемента при условии включенного флага PAS
	if(!PAS)
		return ' pe';

	return ' over3 pas pe';
}
function _pasId($r) {//id элемента страницы для сотрировки
	return ' id="pe_'.$r['dialog_id'].'_'.$r['id'].'"'.(PAS ? ' val="'.$r['id'].'"' : '');
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




function _pageSpisok($pe) {//список, выводимый на странице
	$page_id = $pe['page_id'];

	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	$spTypeId = $pe['num_1'];    //внешний вид списка: [181] => Таблица [182] => Шаблон
	$spLimit = $dv[$pe['num_2']];//лимит

	//диалог, через который вносятся данные списка
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);
	$spElement = $spDialog['component']; //элементы списка
	$spTable = $spDialog['base_table'];

	//получение данных списка
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `dialog_id`=".$dialog_id."
			  "._pageSpisokFilterSearch($pe, $spDialog)."
			ORDER BY `dtime_add` DESC
			LIMIT ".$spLimit;
	$spisok = query_arr($sql);

	$html = '';

	//выбор внешнего вида
	switch($spTypeId) {
		case 181://Таблица
			if(!$spisok) {
				$html = '<div class="_empty">'.$pe['txt_1'].'</div>';
				break;
			}
			$html = '<table class="_stab">'.
						'<tr>'.
							'<th class="w15">id';//ID
			foreach($spElement as $el) {
				if($el['type_id'] == 7)
					continue;
				$html .= '<th>'.$el['label_name'];
			}
			$html .= '<th class="w15">';//настройки
			foreach($spisok as $sp) {
				$html .= '<tr><td class="r grey">'.$sp['id'];
				foreach($spElement as $el) {
					if($el['type_id'] == 7)
						continue;
					if($el['col_name'] == 'app_any_spisok')
						$v = '';
					else {
						$v = $sp[$el['col_name']];
//						if(strlen($val) && $el['col_name'] == $spElement[$comp_id]['col_name'])
//							$v = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $v, 1);
					}
					$html .= '<td>'.$v;
				}
				$html .= '<td class="wsnw">'
							._iconEdit(array('onclick'=>'_dialogOpen('.$dialog_id.','.$sp['id'].')'));
							//._iconDel();
			}

			$html .= '</table>';
			break;
		case 182://Шаблон
			foreach($spElement as $el) {
				$html .= '<div>'.$el['label_name'].'</div>';
			}
			break;
		default:
			$html = 'Неизвестный внешний вид списка: '.$spTypeId;
	}

	return $html;
}
function _pageSpisokFilterSearch($pe, $spDialog) {//получение значений фильтра-поиска для списка
	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	//получение значения элемента поиска, содержащегося на странице, где находится список воздействующий на этот список
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_3`=".$pe['id'];
	if(!$search = query_assoc($sql))
		return '';

	$arr = array();
	foreach($colIds as $cmp_id)
		$arr[] = "`".$spDialog['component'][$cmp_id]['col_name']."` LIKE '%".addslashes($search['v'])."%'";

	return " AND (".implode($arr, ' OR ').")";
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


function _page_div() {//todo тест
	return
	'<div class="bg-ffc">123</div>';
}








