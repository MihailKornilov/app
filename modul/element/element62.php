<?php

/* [62] Фильтр: галочка */
function _element62_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст для галочки
		'txt_2'   => $el['txt_2'],      //фильтр настраивается через [40]
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'num_2'   => _num($el['num_2']),/* условие применяется:
											1439 - галочка установлена
											1440 - галочка снята
										*/
		'num_3'   => _num($el['num_3']) //начальное значение для галочки
	) + _elementStruct($el);
}
function _element62_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element62_print($el, $prm) {
	return
	_check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'title' => $el['txt_1'],
		'disabled' => $prm['blk_setup'],
		'value' => _spisokFilter('vv', $el, $el['num_3'])
	));
}

