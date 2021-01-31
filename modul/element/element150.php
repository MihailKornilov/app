<?php

/* [150] Выделение/снятие всех галочек в списке */
function _element150_struct($el) {
	return array(
		'num_1'   => _num($el['num_1'])//id элемента - список, в котором размещены галочки
	) + _elementStruct($el);
}
function _element150_print($el, $prm) {
	if(!$el['num_1'])
		return '';

	return _check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'disabled' => $prm['blk_setup']
	));
}
