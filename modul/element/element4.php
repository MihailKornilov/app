<?php

/* [4] Заголовок */
function _element4_struct($el) {
	return array(
		'txt_1' => $el['txt_1'] //текст заголовка
	) + _elementStruct($el);
}
function _element4_title($el) {
	return $el['txt_1'];
}
function _element4_print($el) {
	return '<div class="hd2">'.$el['txt_1'].'</div>';
}

