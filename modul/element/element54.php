<?php

/* [54] Количество значений привязанного списка */
function _element54_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, указывающего на привязанный список
		'num_3'   => _num($el['num_3']),//включение счётчика
		'txt_1'   => $el['txt_1']       //фильтр
	) + _elementStruct($el);
}
function _element54_print($el) {
	return $el['name'];
}
function _element54_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element54_template_docx($el, $u) {
	return _element54_print11($el, $u);
}

