<?php

/* [1] Галочка */
function _element1_struct($el) {
	return array(
		'def'   => _num($el['def']),

		'txt_1' => $el['txt_1'],      //текст для галочки
		'num_1' => _num($el['num_1']) //уникальное значение (для "по умолчанию")
	) + _elementStruct($el);
}
function _element1_title($el) {
	return '✓ '.$el['txt_1'];
}
function _element1_print($el, $prm) {
	return _check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'title' => _br($el['txt_1']),
		'disabled' => $prm['blk_setup'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element1_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(empty($u[$col]))
		return '';

	if(empty($el['txt_1']))
		return '<div class="icon icon-ok curD"></div>';

	return '<div class="icon icon-ok curD tool" data-tool="'._br($el['txt_1']).'"></div>';
}
function _element1_history($el, $v) {
	return _daNet($v);
}
function _element1_v_get($el, $v) {
	switch($v) {
		case 1: return 'установить галочку';
		case 0:
		case -1: return 'снять галочку';
	}
	return _msgRed('[1] неизвестное значение');
}
function _elem1def($cmp_id, $unit_id, $v) {//сброс значений у других галочек, если стоит флаг num_1
	if(!$cmp = _elemOne($cmp_id))
		return;
	if($cmp['dialog_id'] != 1)
		return;
	if(!$cmp['num_1'])
		return;
	if(!$col = $cmp['col'])
		return;

	if(!$unit_id = _num($unit_id))
		return;
	if(!$v)
		return;

	if(!$BL = _blockOne($cmp['block_id']))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return;

	$sql = "UPDATE "._queryFrom($DLG)."
			SET `".$col."`=0
			WHERE "._queryWhere($DLG)."
			  AND `id`!=".$unit_id;
	query($sql);
}
