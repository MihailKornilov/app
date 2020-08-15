<?php

/* [91] Выбор галочками */
function _element91_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'], //[8] Подсказка для галочки
		'num_1'   => $el['num_1']  //[13] Поле для подсчёта суммы
	) + _elementStruct($el);
}
function _element91_struct_title($el) {
	$el['title'] = '✓';
	return $el;
}
function _element91_print($el, $prm) {
	$u = $prm['unit_get'];

	return _check(array(
		'attr_id' => 'sch'.$el['id'].'_'.$u['id'],
		'value' => 0
	));
}

