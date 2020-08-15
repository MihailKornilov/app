<?php

/* [83] Фильтр: Select - привязанный список */
function _element83_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента-списка, на который воздействует фильтр
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений) - привязанный список (через [13])
	) + _elementStruct($el);
}
function _element83_js($el) {
	return array(
		'txt_1' => $el['txt_1'],
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
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
function _elem102CnnList($ids, $return='select', $cond='') {//значения привязанного списка (пока для фильтра 102)
	if(!$last_id = _idsLast($ids))
		return array();
	if(!$el = _elemOne($last_id))
		return array();
	if(!$bl = _blockOne($el['block_id']))
		return array();
	if($bl['obj_name'] != 'dialog')
		return array();
	if(!$dlg_id = _num($bl['obj_id']))
		return array();
	if(!$dlg = _dialogQuery($dlg_id))
		return array();
	if(!$col = @$el['col'])
		return array();

	//получение данных списка
	$sql = "SELECT "._queryCol($dlg)."
			FROM   "._queryFrom($dlg)."
			WHERE  "._queryWhere($dlg)."
				   ".$cond."
			ORDER BY ".(_queryColReq($dlg, 'sort') ? "`sort`,`id`" : '`id`')."
			LIMIT 200";
	if(!$spisok = query_arr($sql))
		return array();

	$select = array();
	$ass = array();
	foreach($spisok as $id => $r) {
		$select[] = array(
			'id' => $id,
			'title' => $r[$col]
		);
		$ass[$id] = $r[$col];
	}

	if($return == 'ass')
		return $ass;
	if($return == 'ids')
		return _idsGet($select);

	return $select;
}
function _elem83filter($el) {//фильтр-select
	$filter = false;
	$v = 0;

	//поиск элемента-фильтра-select
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 83) {
			$filter = $r['elem'];
			$v = _num($r['v']);
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return '';
	if(!$elem_ids = _ids($filter['txt_2'], 1))
		return '';

	$elem_id = $elem_ids[0];

	if(!$ell = _elemOne($elem_id))
		return '';
	if(!$col = $ell['col'])
		return '';

	return " AND `".$col."`=".$v;
}

