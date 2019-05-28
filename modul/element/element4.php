<?php

/* [4] Заголовок */
function _element4_struct($el) {
	return array(
		'txt_1' => $el['txt_1'] //текст заголовка
	) + _elementStruct($el);
}
function _element4_print($el) {
	return '<div class="hd2">'.$el['txt_1'].'</div>';
}
function _element4_copy_field($el) {
	return array(
		'txt_1' => $el['txt_1']
	);
}

