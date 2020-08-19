<?php


/* [6] Select: выбор страницы */
function _element6_struct($el) {
	return array(
		'txt_1' => $el['txt_1'] //текст, когда страница не выбрана
	) + _elementStruct($el);
}
function _element6_print($el, $prm) {
	return _select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}
function _element6_vvv() {
	$page = _pageCache();
	$child = array();
	foreach($page as $id => $r) {
		if(!$r['parent_id'])
			continue;

		if(empty($child[$r['parent_id']]))
			$child[$r['parent_id']] = array();

		$child[$r['parent_id']][] = $r;
		unset($page[$id]);
	}
	$send = _pageChildArr($page, $child);

	if(SA) {
		$child = array();
		$send[] = array(
			'title' => 'Страницы SA',
			'info' => 1
		);
		foreach(_pageSaForSelect($page, $child) as $r)
			$send[] = $r;
	}

	return $send;
}

