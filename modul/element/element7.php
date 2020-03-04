<?php

/* [7] Фильтр: быстрый поиск */
function _element7_struct($el) {
	return array(
		'width' => _num($el['width']),

		'txt_1' => $el['txt_1'],      //текст поиска
		'num_1' => _num($el['num_1']),//id элемента, содержащего список, по которому происходит поиск
		'txt_2' => $el['txt_2']       //по каким полям производить поиск (id элементов через запятую диалога списка)
	) + _elementStruct($el);
}
function _element7_js($el) {
	return array(
		'num_1' => $el['num_1']
	) + _elementJs($el);
}
function _element7_print($el, $prm) {
	return _search(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'v' => _filter('vv', $el),
		'disabled' => $prm['blk_setup']
	));
}

