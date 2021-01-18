<?php

/* [51] Календарь */
function _element51_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[1] разрешать выбор прошедших дней
		'num_2'   => _num($el['num_2']),//[1] показывать время
		'num_3'   => _num($el['num_3']),//[18] начальная дата: 0 - текущая, 1 - указанная
		'num_4'   => _num($el['num_4']),//[1] устанавливать начальную дату в том числе и при редактировании
		'txt_1'   => $el['txt_1']       //[51] указанная дата (при num_3=1)
	) + _elementStruct($el);
}
function _element51_print($el, $prm) {
	$v = $el['num_3'] ? $el['txt_1'] : strftime('%Y-%m-%d %H:%M-%S');

	if(!$el['num_4'])
		$v = _elemPrintV($el, $prm, $v);

	return
	_calendar(array(
		'attr_id' => _elemAttrId($el, $prm),
		'time' => $el['num_2'],
		'value' => $v
	));
}
function _element51_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	$txt = _elemAction246($el['elp'], $txt);       //Формат даты

	if(!preg_match(REGEXP_DATE, $txt))
		return $txt;
	if($txt == '0000-00-00')
		return '';
	if($el['num_2'] && $txt == '0000-00-00 00:00:00')
		return '';

	$v = FullData($txt, 0, 1, 1);
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

