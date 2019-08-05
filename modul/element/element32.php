<?php

/* [32] Значение списка: порядковый номер */
function _element32_struct($el) {
	return _elementStruct($el);
}
function _element32_struct_title($el) {
	$el['title'] = 'NUM';
	return $el;
}
function _element32_print($el, $prm) {
	if(empty($prm['unit_get']))
		return $el['title'];

	$u = $prm['unit_get'];

	if(!$num = _num($u))
		if(is_array($u))
			$num = empty($u['num']) ? $u['id'] : $u['num'];

	if(!$num)
		return $el['title'];

	$num = _spisokColSearchBg($el, $num);
	return $num;
}
function _element32_template_docx($el, $u) {
	return empty($u['num']) ? $u['id'] : $u['num'];
}

