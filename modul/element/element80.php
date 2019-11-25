<?php

/* [80] Очистка фильтров */
function _element80_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //имя кнопки
		'num_1'   => _num($el['num_1'])//id элемента, размещающего список
	) + _elementStruct($el);
}
function _element80_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element80_print($el, $prm) {
	$diff = _filter('diff', $el['num_1']);
	return _button(array(
		'attr_id' => _elemAttrId($el, $prm),
		'name' => _br($el['txt_1']),
		'color' => 'red',
		'width' => $el['width'],
		'small' => 1,
		'class' => _dn($prm['blk_setup'] || $diff)._dn(!$prm['blk_setup'], 'curD')
	));
}

