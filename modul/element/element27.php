<?php

/* [27] Cумма значений записи */
function _element27_struct($el) {
	/*
		настройка значений через PHP12_balans_setup
	*/
	return array(
		'num_3'   => _num($el['num_3'])//включение счётчика
	) + _elementStruct($el);
}
function _element27_print($el) {
	return $el['name'];
}
function _element27_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element27_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'minus' => _num($cl['num_8']), //вычитание=1, сложение=0
		'title' => $cl['title']
	);
}
function _element27_template_docx($el, $u) {
	return _element27_print11($el, $u);
}

