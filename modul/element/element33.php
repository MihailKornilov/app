<?php

/* [33] Значение записи: дата */
function _element33_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),/* формат:
											29: 5 августа 2017
											30: 5 авг 2017
											31: 05/08/2017
										*/
		'num_2'   => _num($el['num_2']),//не показывать текущий год
		'num_3'   => _num($el['num_3']),/* имена у ближайших дней:
												вчера
												сегодня
												завтра
										*/
		'num_4'   => _num($el['num_4']),//показывать время в формате 12:45
		'num_5'   => _num($el['num_5']) //показывать пользователя, внёсшего запись (при наведении на дату)
	) + _elementStruct($el);
}
function _element33_struct_title($el) {
	$el['title'] = 'Дата';
	return $el;
}
function _element33_print($el, $prm) {
	if($prm['blk_setup'])
		return 'дата';
	if(!$u = $prm['unit_get'])
		return 'дата';

	$data = _element33Data($el, $u);
	$data = _element33TT($el, $u, $data);

	return $data;
}
function _element33Data($el, $u) {//Значение записи: дата [33]
	if(empty($u['dtime_add']))
		return '';
	if(!preg_match(REGEXP_DATE, $u['dtime_add']))
		return 'некорректный формат даты';

	$ex = explode(' ', $u['dtime_add']);
	$d = explode('-', $ex[0]);

	//время
	$hh = '';
	if($el['num_4'] && !empty($ex[1])) {
		$h = explode(':', $ex[1]);
		$hh .= ' '.$h[0].':'.$h[1];
	}

	if($el['num_1'] == 31)
		return $d[2].'/'.$d[1].'/'.$d[0].$hh;

	$hh = $hh ? ' в'.$hh : '';

	if($el['num_3']) {
		$dCount = floor((strtotime($ex[0]) - TODAY_UNIXTIME) / 3600 / 24);
		switch($dCount) {
			case -1: return 'вчера'.$hh;
			case 0: return 'сегодня'.$hh;
			case 1: return 'завтра'.$hh;
		}
	}

	return
		_num($d[2]).                                                     //день
		' '.($el['num_1'] == 29 ? _monthFull($d[1]) : _monthCut($d[1])). //месяц
		($el['num_2'] && $d[0] == YEAR_CUR ? '' : ' '.$d[0]).            //год
		$hh;                                                             //время
}
function _element33TT($el, $u, $data) {//подсказка кто внёс запись
	if(!$el['num_5'])
		return $data;
	if(!$user_id = _num($u['user_id_add']))
		return $data;
	if(!$user = _user($user_id))
		return $data;

	$tt = 'Вн'.($user['pol'] == 1 ? 'есла' : 'ёс').' '.$user['i'].' '.$user['f'];

	return '<span class="inhr curD tool" data-tool="'.$tt.'">'.$data.'<span>';
}
function _element33_template_docx($el, $u) {
	return _element33Data($el, $u);
}

