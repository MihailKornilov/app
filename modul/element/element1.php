<?php

/* [1] Галочка */
function _element1_struct($el) {
	return array(
		'def'   => _num($el['def']),

		'txt_1' => $el['txt_1'] //текст для галочки
	) + _elementStruct($el);
}
function _element1_struct_title($el) {
	$el['title'] = '? '.$el['txt_1'];
	return $el;
}
function _element1_print($el, $prm) {
	return _check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'title' => $el['txt_1'],
		'disabled' => $prm['blk_setup'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element1_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(empty($u[$col]))
		return '';

	return '<div class="icon icon-ok curD"></div>';
}
function _element1_history($el, $v) {
	return _daNet($v);
}
function _element1_copy_field($el) {
	return array(
		'def'   => _num($el['def']),
		'txt_1' => $el['txt_1']
	);
}

