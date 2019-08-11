<?php

/* [38] Select: выбор диалогового окна (SA) */
function _element38_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1'],      //нулевое значение
		'num_1'   => _num($el['num_1']) //начальное значение
	) + _elementStruct($el);
}
function _element38_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element38_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, $el['num_1'])
	));
}
function _element38_vvv() {
	return _dialogSelArray();
}

