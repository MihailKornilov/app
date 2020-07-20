<?php

/* [400] График: столбики */
function _element400_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //[8] заголовок
		'num_1'   => _num($el['num_1'])//[24] список
	) + _elementStruct($el);
}
function _element400_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element400_print($el, $prm) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return _emptyMinRed('График-столбики: диалога '.$el['num_1'].' не существует.');
	if($prm['blk_setup'])
		return _emptyMin('График-столбики <b>'.$DLG['name'].'</b>');


//	$data = _elem400_yearData($DLG);
//	$title = _elem400_yearTitle($DLG);

//	$data = _elem400_monData($DLG);
//	$title = _elem400_monTitle();

	$data = _elem400_dayData($DLG);
	$title = _elem400_dayTitle();

	return
	'<div id="chart_'.$el['id'].'"></div>'.
	'<script>'.
		'var WIDTH_'.$el['id'].'='._elemWidth($el).',
			 SERIES_'.$el['id'].'=[{'.
				'name:"Все записи",'.
				'data:['.$data.']'.
			'}],
			 CAT_'.$el['id'].'=['.$title.'];'.
	'</script>';
}

function _elem400_yearData($DLG) {//данные для годов
	$send = array();

	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%y') AS `year`,
				COUNT(`id`) `count`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			GROUP BY `year`
			ORDER BY `year`";
	$Y = 0;
	foreach(query_ass($sql) as $year => $c) {
		if(!$Y)
			$Y = $year;

		//заполнение пустых годов нулями
		while($Y != $year)
			$send[$Y++] = 0;

		$send[$year] = $c;

		$Y++;
	}

	return implode(',', $send);
}
function _elem400_yearTitle($DLG) {//подписи для годов
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			ORDER BY `year`
			LIMIT 1";
	if(!$first = query_value($sql))
		return '';

	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			ORDER BY `year` DESC
			LIMIT 1";
	$last = query_value($sql);

	if($first == $last)
		return $first;

	$send = array();
	for($n = $first; $n <= $last; $n++)
		$send[] = $n;

	return implode(',', $send);
}

function _elem400_monData($DLG) {//данные для месяцев
	$mon = array();
	for($n = 1; $n <= 12; $n++)
		$mon[$n] = 0;

	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%m') AS `mon`,
				COUNT(`id`) `count`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '2020-%'
			GROUP BY `mon`
			ORDER BY `mon`";
	foreach(query_ass($sql) as $d => $c)
		$mon[_num($d)] = $c;

	return implode(',', $mon);
}
function _elem400_monTitle() {//подписи для месяцев
	$send = array();
	for($n = 1; $n <= 12; $n++)
		$send[] = '"'._monthCut($n).'"';
	return implode(',', $send);
}

function _elem400_dayData($DLG, $mon=YEAR_MON) {
	$send = array();

	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);   //Количество дней в месяце
	for($n = 1; $n <= $dayCount; $n++)
		$send[$n] = '""';

	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				COUNT(`id`) `count`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '".$mon."-%'
			GROUP BY `day`
			ORDER BY `day`";
	foreach(query_ass($sql) as $d => $c) {
		$d = _num($d);
		if(isset($send[$d]))
			$send[$d] = $c;
	}

	return implode(',', $send);
}
function _elem400_dayTitle($mon=YEAR_MON) {
	$send = array();
	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);   //Количество дней в месяце
	$w = date('w', $unix);       //Номер первого дня недели
	if(!$w)
		$w = 7;

	$mon = _monthCut(strftime('%m', $unix));

	for($n = 1; $n <= $dayCount; $n++) {
		$send[] = '"<tspan'.(!$w || $w == 6 ? ' style=\"color:#d55\"' : '').'>'.
			($n == 1 ? '<b>'.$mon.'</b> ' : '').
			_week($w++).' '.
			$n.
			'</tspan>"';
		if($w > 6)
			$w = 0;
	}

	return implode(',', $send);
}
