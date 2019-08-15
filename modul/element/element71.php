<?php

/* [71] Значение записи: иконка сортировки */
function _element71_struct($el) {
	return _elementStruct($el);
}
function _element71_struct_title($el) {
	$el['title'] = 'SORT';
	return $el;
}
function _element71_print($el, $prm) {
	return '<div class="icon icon-move '.($prm['unit_get'] ? 'pl' : 'curD').'"></div>';
}

