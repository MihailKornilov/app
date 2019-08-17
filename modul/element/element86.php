<?php

/* [86] Значение записи: количество дней */
function _element86_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//ID элемента, который указывает на дату
		'txt_1'   => $el['txt_1'],      //текст "Прошёл" 1
		'txt_2'   => $el['txt_2'],      //текст "Остался" 1
		'txt_3'   => $el['txt_3'],      //текст "День" 1
		'txt_4'   => $el['txt_4'],      //текст "Прошло" 2
		'txt_5'   => $el['txt_5'],      //текст "Осталось" 2
		'txt_6'   => $el['txt_6'],      //текст "Дня" 2
		'txt_7'   => $el['txt_7'],      //текст "Прошло" 5
		'txt_8'   => $el['txt_8'],      //текст "Осталось" 5
		'txt_9'   => $el['txt_9'],      //текст "Дней" 5
		'txt_10'  => $el['txt_10'],     //текст для "сегодня"

		'num_2'   => _num($el['num_2']),//показывать "вчера"
		'num_3'   => _num($el['num_3']) //показывать "завтра"
	) + _elementStruct($el);
}
function _element86_print($el, $prm) {
	if(!$u = $prm['unit_get'])
		return 'Кол-во дней';
	if(!$elem_id = $el['num_1'])
		return _msgRed('-no-elem-date');
	if(!$EL = _elemOne($elem_id))
		return _msgRed('-no-elem-'.$elem_id);
	if(!$col = $EL['col'])
		return _msgRed('-no-elem-col');
	if(!isset($u[$col]))
		return _msgRed('-no-unit-col');

	$date = substr($u[$col], 0, 10);

	if(!preg_match(REGEXP_DATE, $date))
		return _msgRed('-no-date-format');
	if($date == '0000-00-00')
		return '';

	$day = (strtotime($date) - TODAY_UNIXTIME) / 86400;

	$day_txt =
		($day > 0 ?
		_end($day, $el['txt_2'], $el['txt_5'], $el['txt_8'])
		:
		_end($day, $el['txt_1'], $el['txt_4'], $el['txt_7'])
		).
		' '.abs($day).' '.
		_end($day, $el['txt_3'], $el['txt_6'], $el['txt_9']);

	if($day == -1 && $el['num_2'])
		$day_txt = $el['txt_10'].' вчера';
	if(!$day)
		$day_txt = $el['txt_10'].' сегодня';
	if($day == 1 && $el['num_3'])
		$day_txt = $el['txt_10'].' завтра';

	return $day_txt;
}

