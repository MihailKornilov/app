<?php

/* [73] Динамический путь даты */
function _element73_struct($el) {
	return array(
		'num_1'   => _num($el['num_1'])//фильтр-календарь [13]
	) + _elementStruct($el);
}
function _element73_print($el) {
	$F = _spisokFilter();
	if(empty($F['filter'][$el['num_1']]))
		return _msgRed('Отсутствует фильтр-календарь');

	$el77 = $F['filter'][$el['num_1']];

	return $el77['v'];

	return
	'2009'.
	'<span class="pale"> » </span>'.
	'июль'.
	'<span class="pale"> » </span>'.
	'03';
}
function _element73_copy_field($el) {
	return array(
		'num_1'   => _num($el['num_1'])
	);
}

