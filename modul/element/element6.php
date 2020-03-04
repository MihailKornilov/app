<?php


/* [6] Select: выбор страницы */
function _element6_struct($el) {
	/*
		содержание: PAGE_LIST
	*/
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'] //текст, когда страница не выбрана
	) + _elementStruct($el);
}
function _element6_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element6_print($el, $prm) {
	return _select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}

