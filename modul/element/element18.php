<?php

/* [18] Dropdown */
function _element18_struct($el) {
	/*
		значения через PHP12_radio_setup
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_1'   => _num($el['num_1']),//скрывать нулевое значение в меню выбора
		'num_2'   => _num($el['num_2']) //не изменять имя нулевого значения после выбора
	) + _elementStruct($el);
}
function _element18_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1'],
		'def' => _num($cl['def'])
	);
}
function _element18_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'txt_1'   => $el['txt_1']
	) + _elementJs($el);
}
function _element18_print($el, $prm) {
	return
	_dropdown(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element18_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';
	if(empty($el['vvv']))
		return '';

	foreach($el['vvv'] as $vv)
		if($vv['id'] == $id)
			return $vv['title'];

	return '';
}

