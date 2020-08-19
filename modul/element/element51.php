<?php

/* [51] Календарь */
function _element51_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//разрешать выбор прошедших дней
		'num_2'   => _num($el['num_2']) //показывать время
	) + _elementStruct($el);
}
function _element51_print($el, $prm) {
	return
	_calendar(array(
		'attr_id' => _elemAttrId($el, $prm),
		'time' => $el['num_2'],
		'value' => _elemPrintV($el, $prm)
	));
}
function _element51_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';
	if($txt == '0000-00-00')
		return '-';
	if($el['num_2'] && $txt == '0000-00-00 00:00:00')
		return '';

	$v = FullData($txt);
	if($el['num_2'])
		$v .= ' в '._num(substr($txt, 11, 2)).
				':'.substr($txt, 14, 2);

	return $v;
}
function _element51_history($el, $v) {
	return FullData($v);
}
function _element51_template_docx($el, $u) {
	$col = $el['col'];
	$ex = explode('-', $u[$col]);
	return $ex[2].'/'.$ex[1].'/'.$ex[0];}

