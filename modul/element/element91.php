<?php

/* [91] Выбор галочками */
function _element91_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'], //[8] Подсказка для галочки
		'num_1'   => $el['num_1']  //[13] Поле для подсчёта суммы
	) + _elementStruct($el);
}
function _element91_print($el, $prm) {
	if($prm['blk_setup'])
		return _check(array('disabled'=>1));
	if(!$u = _unitGet($prm))
		return _msgRed('[91]-no-unit-get');

	return _check(array(
		'attr_id' => 'sch'.$el['id'].'_'.$u['id'],
		'value' => 0
	));
}
function _element91_title() {
	return '[91] Выбор галочками';
}
