<?php

/* [7] Фильтр: быстрый поиск */
function _element7_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],      //[8] текст поиска
		'num_1' => _num($el['num_1']),//[13] id элемента, содержащего список, по которому происходит поиск
		'txt_2' => $el['txt_2'],      //[13] по каким полям производить поиск (id элементов через запятую диалога списка)
		'num_2' => _num($el['num_2']),//[1] игнорировать другие фильтры
		'txt_3' => $el['txt_3']       //[13] фильтры, которые игнорируются
	) + _elementStruct($el);
}
function _element7_print($el, $prm) {
	return _search(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'v' => _filter('vv', $el),
		'disabled' => $prm['blk_setup']
	));
}
function _elem7filter($el) {//значения фильтра-поиска для списка
	$search = false;
	$v = '';

	//поиск элемента-фильтра-поиска
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
			break;
		}

	if(!$search)
		return '';
	if(!$v)
		return '';

	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($search['txt_2'], 1))
		return '';

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		if(!$col = _elemCol($cmp_id))
			continue;

		if(_elemIsConnect($cmp[$cmp_id])) {
			if(!$DLG = _dialogQuery($cmp[$cmp_id]['num_1']))
				continue;
			if(!$colDef = _elemCol($DLG['spisok_elem_id']))
				continue;
			$sql = "SELECT `id`
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND `".$colDef."` LIKE '%".addslashes($v)."%'";
			$arr[] = "`t1`.`".$col."` IN (".$sql.")";
			continue;
		}

		$arr[] = "`t1`.`".$col."` LIKE '%".addslashes($v)."%'";
	}

	if(!$arr)
		return '';

	return " AND (".implode($arr, ' OR ').")";
}

