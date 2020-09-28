<?php

/* [3] Меню страниц */
function _element3_struct($el) {
	return array(
		'num_1' => _num($el['num_1']),// раздел (страница-родитель). В меню будут дочерние страницы
		'num_2' => _num($el['num_2']) /* внешний вид:
											1 - Основной вид - горизонтальное меню
											2 - С подчёркиванием (гориз.)
											3 - Синие маленькие кнопки (гориз.)
											4 - Боковое вертикальное меню
											5 - Боковое с иконками
									  */
	) + _elementStruct($el);
}
function _element3_print($el, $prm) {
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if(_pageSA($r))
			continue;
		if(!_pageAccess($id))
			continue;
		//раздел
		if($el['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return 'Разделов нет.';

	$menu = _spisokImage($menu);
	$menu = _menuCount($menu);

	$pfrom = _num(@$_GET['pfrom']);

	$razdel = '';
	foreach($menu as $page_id => $r) {
		$sel = _page('is_cur_parent', $page_id) ? ' sel' : '';

		//фактическая страница, на которую будет переход
		$pid = $page_id;

		//если страница является ссылкой на другую страницу, при этом она недоступна, поиск первой вложенной доступной
		if($r['common_id'])
			foreach(_page('child', $page_id) as $p)
				if(_pageAccess($p['id'])) {
					$pid = $p['id'];
					break;
				}

		//если страница пришла из второго уровня, то при нажатии на первый уровень будет переход на предыдущую страницу второго уровня
		if($sel)
			foreach(_page('child', $page_id) as $p)
				if($pfrom == $p['id']) {
					$pid = $p['id'];
					break;
				}

		$href = $prm['blk_setup'] ? '' : ' href="'.URL.'&p='.$pid.'"';
		$curd = _dn(!$prm['blk_setup'], 'curD');

		if($el['num_2'] == 5)
			$r['name'] = _imageHtml($r['image_ids'], $r['image_width'], 0, false, false);

		$razdel .= '<a class="link'.$sel.$curd.'"'.$href.'>'.$r['name'].'</a>';
	}

	return '<div class="_menu'.$el['num_2'].'">'.$razdel.'</div>';
}
function _element3_title() {
	return 'Меню страниц';
}
function _menuCount($menu) {//получение элемента-циферки, размещённого на выводимых страницах
	$sql = "SELECT
				`el`.`id`,
				`el`.`num_1`,
				`el`.`txt_1`,
				`bl`.`obj_id` `page_id`
			FROM `_element` `el`,
				 `_block` `bl`
			WHERE `el`.`block_id`=`bl`.`id`
			  AND `el`.`app_id`=".APP_ID."
			  AND `dialog_id`=87
			  AND `bl`.`obj_name`='page'";
	if(!$arr = query_arr($sql))
		return $menu;

	foreach($arr as $r) {
		$page = _page($r['page_id']);

		//страница, к которой будет добавлена циферка
		$pid = 0;
		if(isset($menu[$r['page_id']]))
			$pid = $r['page_id'];
		if($page['parent_id'] && isset($menu[$page['parent_id']]))
			$pid = $page['parent_id'];
		if(!$pid)
			continue;

		$DLG = _dialogQuery($r['num_1']);
		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG).
					_40cond(array(), $r['txt_1']);
		if(!$count = query_value($sql))
			continue;

		$menu[$pid]['name'] .= '<b class="ml5">+'.$count.'</b>';
	}

	return $menu;
}

/* ---=== ВЫБОР ВНЕШНЕГО ВИДА МЕНЮ СТРАНИЦ [3] ===--- */
function PHP12_page_menu_type($prm) {
	$sel = _num($prm['el12']['txt_2']);
	if($col = $prm['el12']['col'])
		if($u = $prm['unit_edit'])
			$sel = $u[$col];


	$send = '';
	for($n = 1; $n <= 5; $n++)
		$send .= '<div class="page-menu type'.$n._dn($n != $sel, 'sel').'" val="'.$n.'"></div>';

	return $send;
}

