<?php

/* [38] Select: выбор диалогового окна (SA) */
function _element38_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'num_1'   => _num($el['num_1']) //начальное значение
	) + _elementStruct($el);
}
function _element38_print($el, $prm) {
	if(!$v = _elemPrintV($el, $prm, $el['num_1']))
		if($elem_id = _num(@$prm['srce']['element_id']))
			$v = _elemDlgId($elem_id);

	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => $v
	));
}
function _element38_vvv() {
	return _dialogSelArray();
}
