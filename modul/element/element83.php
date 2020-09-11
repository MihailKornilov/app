<?php

/* [83] Фильтр: Select - привязанный список */
function _element83_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента-списка, на который воздействует фильтр
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2']       //[13] id элемента (с учётом вложений) - привязанный список
	) + _elementStruct($el);
}
function _element83_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _filter('vv', $el, 0)
	));
}
function _element83_vvv($el) {
	return _elem102CnnList($el['txt_2']);
}
function _elem83filter($el) {//фильтр-select
	$send = '';
	//поиск элемента-фильтра-select
	foreach(_filter('spisok', $el['id']) as $r) {
		if($r['elem']['dialog_id'] != 83)
			continue;
		if(!$filter = $r['elem'])
			continue;
		if(!$v = _num($r['v']))
			continue;

		$send .= _elem83filterWhere($filter, $v);
	}

	return $send;
}
function _elem83filterWhere($filter, $v) {
	//проверка, нужно ли добавлять дочерние значения
	if(!$last_id = _idsLast($filter['txt_2']))
		return '';
	if(!$dlg = _elemDlg($last_id))
		return '';

	$parent_ids = $v;
	$ids[$v] = 1;
	while($parent_ids) {
		$sql = "SELECT `id`
				FROM   "._queryFrom($dlg)."
				WHERE  "._queryWhere($dlg)."
				  AND `parent_id` IN (".$parent_ids.")";
		if($parent_ids = query_ids($sql))
			$ids += _idsAss($parent_ids);
	}

	//получение колонки, по которой выборка
	if(!$elem_id = _idsFirst($filter['txt_2']))
		return '';
	if(!$col = _elemCol($elem_id))
		return '';

	return " AND `".$col."` IN ("._idsGet($ids, 'key').")";
}

