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

		'txt_1'   => $el['txt_1'],  //текст нулевого значения
		'txt_2'   => $el['txt_2']   /* содержание списка в формате JSON
                                        id
                                        title
                                        content
                                        def
                                    */
	) + _elementStruct($el);
}
function _element17_vvv($el) {
	if(!$el['txt_2'])
		return array();
	if(!$send = json_decode($el['txt_2'], true))
		return array();

	foreach($send as $id => $r)
		if($r['content'])
			$send[$id]['content'] = $r['content'].'<div class="fs12 grey ml10 mt3">'.$r['content'].'</div>';

	return _arrNum($send);
}
function _element17_v_get($el, $id) {
	foreach(_element17_vvv($el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element17_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element17_print($el, $prm) {
	$def = 0;
	foreach(_element('vvv', $el) as $r)
		if($r['def']) {
			$def = $r['id'];
			break;
		}
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, $def)
	));
}
function _element17_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';

	foreach(_element('vvv', $el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}






