<?php

/* [12] ������� PHP (SA) */
function _element12_struct($el) {
	/*
		����� ���������� ������ PHP-������� ����� ����������� JS-������� � ����� �� ������, ���� ����������.
	*/
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],     //��� ������� (���������� � PHP12)
		'txt_2' => $el['txt_2'],     //��������� ��������
		'num_1' => _num($el['num_1'])//������� 1
	) + _elementStruct($el);
}
function _element12_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element12_print($el, $prm) {
	if(!$el['txt_1'])
		return _emptyMin('����������� ��� �������.');
	if(!function_exists($el['txt_1']))
		return _emptyMinRed('������� <b>'.$el['txt_1'].'</b> �� ����������.');
	if($prm['blk_setup'])
		return _emptyMin('������� '.$el['txt_1']);

	$prm['el12'] = $el;

	return
	(!empty($el['col']) ?
		'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm, $el['txt_2']).'" />'
	: '').
		$el['txt_1']($prm);
}
function _element12_vvv($el, $prm) {
	$func = $el['txt_1'].'_vvv';

	if(!function_exists($func))
		return array();

	$prm['el12'] = $el;

	return $func($prm);
}
function _element12_copy_field($el) {
	return array(
		'txt_1' => $el['txt_1'],
		'txt_2' => $el['txt_2'],
		'num_1' => _num($el['num_1'])
	);
}

