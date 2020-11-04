<?php

/* [17] Select: произвольные значения */
function _element17_struct($el) {
	/*
		значения: PHP12_select_setup
	*/
	return array(
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
			$send[$id]['content'] = $r['title'].'<div class="fs12 clr1 ml10 mt3">'.$r['content'].'</div>';

	return _arrNum($send);
}
function _element17_v_get($el, $id) {
	foreach(_element17_vvv($el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
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

function _elem17inc($spisok) {
	foreach($spisok as $id => $sp) {
		if(!$dlg_id = _num(@$sp['dialog_id']))
			continue;
		if(!$DLG = _dialogQuery($dlg_id))
			continue;

		foreach($DLG['cmp'] as $el) {
			if($el['dialog_id'] != 17)
				continue;
			if(!$col = _elemCol($el))
				continue;

			$spisok[$id][$col.'_title'] = _element17_print11($el, $sp);
		}
	}

	return $spisok;
}





