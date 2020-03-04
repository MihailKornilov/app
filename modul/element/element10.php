<?php

/* [10] Произвольный текст */
function _element10_struct($el) {
	return array(
		'txt_1' => $el['txt_1']     //текст
	) + _elementStruct($el);
}
function _element10_struct_title($el) {
	$el['title'] = $el['txt_1'];
	return $el;
}
function _element10_print($el) {
	return _br($el['txt_1']);
}

