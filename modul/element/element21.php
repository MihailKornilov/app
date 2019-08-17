<?php

/* [21] Информационный блок */
function _element21_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],  //содержание
	) + _elementStruct($el);
}
function _element21_print($el) {
	return '<div class="_info">'._br($el['txt_1']).'</div>';
}

