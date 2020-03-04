<?php


/* [9] Поле-пароль */
function _element9_struct($el) {
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],     //текст для placeholder
		'num_1' => _num($el['num_1'])//минимальное количество знаков
	) + _elementStruct($el);
}
function _element9_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return '<input type="password" id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.' />';
}
function _element9_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(empty($u[$col]))
		return '';

	return $u[$col];
}
