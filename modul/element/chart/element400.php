<?php

/* [400] График: столбики */
function _element400_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //[8] заголовок
		'num_1'   => _num($el['num_1'])//[24] список
	) + _elementStruct($el);
}
function _element400_print($el, $prm) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return _emptyMinRed('График-столбики: диалога '.$el['num_1'].' не существует.');
	if($prm['blk_setup'])
		return _emptyMin('График-столбики <b>'.$DLG['name'].'</b>');

	$bl = _blockOne($el['block_id']);
	$data = _elem400_monData($DLG);
	$cat = _elem400_monCat();

	return
	'<div class="pad10 bg6 line-b">'.
		'<input type="hidden" id="hcYear'.$el['id'].'" value="'.YEAR_CUR.'">'.
		'<span id="hcMonDiv'.$el['id'].'" class="ml5">'.
			'&raquo; <input type="hidden" id="hcMon'.$el['id'].'">'.
		'</span>'.
		'<div class="w35 fr" id="busy'.$el['id'].'">&nbsp;</div>'.
	'</div>'.
	'<div id="chart_'.$el['id'].'"></div>'.
	'<script>'.
		'var YEAR_SPISOK_'.$el['id'].'='._elem400_yearSpisok($DLG).',
			WIDTH_'.$el['id'].'='._elemWidth($el).',
			HEIGHT_'.$el['id'].'='.($bl['height'] < 200 ? 200 : $bl['height']).',
			HEAD_'.$el['id'].'="'._elem400_monHead($el).'",
			DATA_'.$el['id'].'='._json($data).',
			CAT_'.$el['id'].'='._json($cat).';'.
	'</script>';
}

function _elem400_yearHead($el, $year=YEAR_CUR) {
	return '<b>'.$el['txt_1'].'</b><br>за весь период';
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

	$arr = array();
	foreach($send as $r)
		$arr[] = $r ? $r : '';

	return $arr;
}
function _elem400_yearCat($DLG) {//подписи для годов
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

	return $send;
}
function _elem400_yearSpisok($DLG) {//получение списка годов для JS, в которых есть записи
	$send = array();
	$send = _selArray($send);


	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			ORDER BY `year`
			LIMIT 1";
	if(!$first = query_value($sql)) {
		$send[YEAR_CUR] = YEAR_CUR;
		$send = _selArray($send);
		return _json($send);
	}

	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			ORDER BY `year` DESC
			LIMIT 1";
	$last = query_value($sql);

	if($first == $last) {
		$send[$first] = $first;
		$send = _selArray($send);
		return _json($send);
	}

	$send = array();
	for($n = $first; $n <= $last; $n++)
		$send[$n] = $n;

	$send = _selArray($send);
	return _json($send);
}

function _elem400_monHead($el, $year=YEAR_CUR) {
	return '<b>'.$el['txt_1'].'</b><br>за '.$year.' год';
}
function _elem400_monData($DLG, $year=YEAR_CUR) {//данные для месяцев
	$mon = array();
	for($n = 1; $n <= 12; $n++)
		$mon[$n] = 0;

	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%m') AS `mon`,
				COUNT(`id`) `count`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '".$year."-%'
			GROUP BY `mon`
			ORDER BY `mon`";
	foreach(query_ass($sql) as $d => $c)
		$mon[_num($d)] = $c;

	$send = array();
	foreach($mon as $m)
		$send[] = $m ? $m : '';

	return $send;
}
function _elem400_monCat() {//подписи для месяцев
	$send = array();
	for($n = 1; $n <= 12; $n++)
		$send[] = _monthCut($n);
	return $send;
}

function _elem400_dayHead($el, $mon) {
	$ex = explode('-', $mon);
	return '<b>'.$el['txt_1'].'</b><br>за '._monthDef($ex[1]).' '.$ex[0];
}
function _elem400_dayData($DLG, $mon=YEAR_MON) {
	$send = array();

	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);   //Количество дней в месяце
	for($n = 1; $n <= $dayCount; $n++)
		$send[$n] = '';

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

	$arr = array();
	foreach($send as $r)
		$arr[] = $r ? $r : '';

	return $arr;
}
function _elem400_dayCat($mon=YEAR_MON) {
	$send = array();
	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);//Количество дней в месяце
	$w = date('w', $unix);       //Номер первого дня недели
	if(!$w)
		$w = 7;

	$first = _monthCut(strftime('%m', $unix));

	for($n = 1; $n <= $dayCount; $n++) {
		$cur = $mon.'-'._nol($n);
		$send[] = '<tspan style="'.
					(!$w || $w == 6 ? 'color:#d55;' : '').
					($cur == TODAY ? 'font-weight:bold;text-decoration:underline;' : '').
					(strtotime($cur) > TODAY_UNIXTIME ? 'opacity:.3;' : '').
				  '">'.
			($n == 1 ? $first.' ' : '').
			_week($w++).' '.
			$n.
			'</tspan>';
		if($w > 6)
			$w = 0;
	}

	return $send;
}
