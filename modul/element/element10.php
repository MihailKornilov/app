<?php

/* [10] ������������ ����� */
function _element10_struct($el) {
	return array(
		'txt_1' => $el['txt_1']     //�����
	) + _elementStruct($el);
}
function _element10_struct_title($el) {
	$el['title'] = $el['txt_1'];
	return $el;
}
function _element10_print($el) {
	return _br($el['txt_1']);
}
function _element10_copy_field($el) {
	return array(
		'txt_1' => $el['txt_1']
	);
}

