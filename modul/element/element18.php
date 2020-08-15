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
		'txt_2'   => $el['txt_2'],      /* содержание списка в формате JSON
                                            id
	                                        title
                                            def
                                        */
		'num_1'   => _num($el['num_1']),//скрывать нулевое значение в меню выбора
		'num_2'   => _num($el['num_2']) //не изменять имя нулевого значения после выбора
	) + _elementStruct($el);
}
function _element18_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'txt_1'   => $el['txt_1']
	) + _elementJs($el);
}
function _element18_vvv($el) {
	if(!$el['txt_2'])
		return array();
	if(!$send = json_decode($el['txt_2'], true))
		return array();

	return _arrNum($send);
}
function _element18_v_get($el, $id) {
	foreach(_element18_vvv($el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element18_print($el, $prm) {
	$def = 0;
	foreach(_element('vvv', $el) as $r)
		if($r['def']) {
			$def = $r['id'];
			break;
		}
	return
	_dropdown(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'value' => _elemPrintV($el, $prm, $def)
	));
}
function _element18_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';

	foreach(_element('vvv', $el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}

