<?php

/* [17] Select: произвольные значения */
function _element17_struct($el) {
	/*
		значения: PHP12_select_setup
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1']      //текст нулевого значения
	) + _elementStruct($el);
}
function _element17_struct_vvv($el, $cl) {
	$send = array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1']
	);

	if($cl['txt_2'])
		$send['content'] = $cl['txt_1'].'<div class="fs12 grey ml10 mt3">'.$cl['txt_2'].'</div>';

	return $send;
}
function _element17_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element17_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element17_print11($el, $u) {
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

