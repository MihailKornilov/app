<?php

/* [55] Сумма значений привязанного списка */
function _element55_struct($el) {
	/*
		для хранения сумм используется колонка sum_1, sum_2, ...
	*/
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, указывающего на привязанный список
		'txt_1'   => $el['txt_1'],      //фильтр
		'num_2'   => _num($el['num_2']),//id элемента значения (колонки) привязанного списка
		'num_3'   => _num($el['num_3']) //включение счётчика
	) + _elementStruct($el);
}
function _element55_print($el) {
	return $el['name'];
}
function _element55_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element55_template_docx($el, $u) {
	return _element55_print11($el, $u);
}

