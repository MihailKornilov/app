<?php

/* [34] Суммы нескольких списков по месяцам */
function _element34_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//страница, на которую будет переход при нажатии на название месяца
		'txt_1'   => $el['txt_1']       //данные о списках
	) + _elementStruct($el);
}
function _element34_print($el) {
	$v1 = @$_GET['v1'];

	if(!$year = _num($v1)) {
		$ex = explode('-', $v1);
		if(!$year = _num($ex[0]))
			$year = YEAR_CUR;
	}

	$json = _decode($el['txt_1']);
	$mass = array();
	foreach($json as $n => $r) {
		$mass[$n] = array();

		if(!$DLG = _dialogQuery($r['dialog_id']))
			continue;

		//если не указан элемент-сумма, выводится количество
		$sum = "COUNT(`id`)";
		if($col = _elemCol($r['sum_id']))
			$sum = "SUM(`".$col."`)";

		//если v1 используется для другой колонки, dtime_add изменяется на неё
		$colD = 'dtime_add';
		if(!empty($r['cond']))
			foreach($r['cond'] as $cn => $cnr)
				if($cnr['unit_id'] == -31)
					if($coll = _elemCol($cnr['elem_id'])) {
						$colD = $coll;
						unset($r['cond'][$cn]);
						break;
					}

		$sql = "SELECT
					DISTINCT(SUBSTR(`".$colD."`,6,2)) AS `id`,
					".$sum."  `sum`
				FROM   "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
				  AND `".$colD."` LIKE '".$year."-%'
					"._40cond(array(), $r['cond'])."
				GROUP BY SUBSTR(`".$colD."`,6,2)";
		foreach(query_ass($sql) as $m => $s)
			$mass[$n][_num($m)] = $s;
	}



	$send = '<table class="_stab w100p">'.
				'<tr><th class="w125">Месяц'.
					 _elem34th($json);
	for($n = 1; $n <= 12; $n++) {
		$mon = _monthDef($n, 1).' '.$year;
		$bgCur = '';
		if($p = $el['num_1']) {
			$yearMon = $year.'-'._nol($n);
			$mon = '<a href="'.URL._elem34href($yearMon, $p).'">'.$mon.'</a>';
			if(preg_match(REGEXP_YEARMON, $v1)) {
				if($yearMon == $v1)
					$bgCur = ' bg11';
			} else
				if($yearMon == YEAR_MON)
					$bgCur = ' bg11';
		}
		$send .=
			'<tr class="over1'.$bgCur.'">'.
				'<td class="r clr9">'.$mon.
				 _elem34td($mass, $n, _elemWidth($el)-125);
	}
	$send .=
			'<tr class="bg6">'.
				'<td class="r b">Итог:'.
				 _elem34itog($mass).
		'</table>';

	return
	_elem34year($json, $year).
	$send;
}
function _elem34href($v1, $p=0) {//формирование ссылки
	$send = '';
	$v1set = false;
	foreach($_GET as $k => $v) {
		if(!strlen($v))
			continue;
		if($k == 'v1') {
			$v = $v1;
			$v1set = true;
		}
		if($k == 'p' && $p)
			$v = $p;
		$send .= '&'.$k.'='.$v;
	}

	if(!$v1set)
		$send .= '&v1='.$v1;

	return $send;
}
function _elem34year($json, $year) {//ссылки на все года
	$Y = array();
	foreach($json as $n => $r) {
		if(!$DLG = _dialogQuery($r['dialog_id']))
			continue;

		//если v1 используется для другой колонки, dtime_add изменяется на неё
		$colD = 'dtime_add';
		if(!empty($r['cond']))
			foreach($r['cond'] as $cn => $cnr)
				if($cnr['unit_id'] == -31)
					if($coll = _elemCol($cnr['elem_id'])) {
						$colD = $coll;
						unset($r['cond'][$cn]);
						break;
					}

		$sql = "SELECT
					DISTINCT(SUBSTR(`".$colD."`,1,4)) AS `id`,
					1
				FROM   "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
					"._40cond(array(), $r['cond'])."
				GROUP BY SUBSTR(`".$colD."`,1,4)
				ORDER BY `".$colD."`";
		$Y += query_ass($sql);
	}

	//определение минимального года
	$min = empty($Y) ? YEAR_CUR : 0;
	foreach($Y as $y => $i) {
		if(!$min)
			$min = $y;
		if($min > $y)
			$min = $y;
	}

	//определение максимального года
	$max = YEAR_CUR;
	foreach($Y as $y => $i)
		if($max < $y)
			$max = $y;

	//формирование ссылок
	$send = '';
	for($y = $min; $y <= $max; $y++) {
		$cur = $y == $year ? ' b u' : '';
		$emp = !isset($Y[$y]) ? ' clr2' : '';
		$mr = $y != $max ? ' mr10' : '';
		$send .= '<a href="'.URL.'?'._elem34href($y).'" class="fs14'.$mr.$cur.$emp.'">'.$y.'</a>';
	}

	return '<div class="pb5">'.$send.'</div>';
}
function _elem34th($json) {//печать заголовков
	if(empty($json))
		return '';

	$send = '';
	foreach($json as $r)
		$send .= '<th>'.$r['title'];

	return $send;
}
function _elem34td($mass, $mon, $width) {//печать значений
	$send = '';
	$w = round($width/count($mass));
	foreach($mass as $n => $r) {
		$width = $n ? ' style="width:'.$w.'px"' : '';
		$send .= '<td class="r"'.$width.'>';
		if(empty($r[$mon]))
			continue;
		if(!round($r[$mon], 2))
			continue;
		$send .= _sumSpace($r[$mon], 1);
	}
	return $send;
}
function _elem34itog($mass) {//печать итога
	$send = '';
	foreach($mass as $mon) {
		$itog = 0;
		foreach($mon as $r)
			$itog += $r;
		$send .= '<td class="r">'._sumSpace($itog, 1);
	}
	return $send;
}





/* [34] Настройка значений */
function PHP12_elem34($prm) {
	return '';
}
function PHP12_elem34_vvv($prm) {//данные для настроек
	//списки для выбора
	$send['sp'] = _dialogSelArray('spisok_only');

	if(empty( $prm['unit_edit']))
		return $send;
	if(!$EL = _elemOne($prm['unit_edit']['id']))
		return $send;

	$send['val'] = json_decode($EL['txt_1'], true);
	foreach($send['val'] as $n => $r) {
		if(!$el = _elemOne($r['sum_id']))
			continue;
		$send['val'][$n]['sum_title'] = $el['title'];
		if(!empty($r['cond'])) {
			$c = count($r['cond']);
			$send['val'][$n]['c'] = $c.' услови'._end($c, 'е', 'я', 'й');
			$send['val'][$n]['cond'] = json_encode($r['cond']);
		}
	}

	return $send;
}
function PHP12_elem34_save($cmp, $val, $unit) {//сохранение
	if(!$elem_id = _num(@$unit['id']))
		jsonError('Некорректный ID элемента');
	if(empty($val))
		jsonError('Нет данных для сохранения');
	if(!is_array($val))
		jsonError('Данные не являются массивом');

	foreach($val as $n => $r)
		if(!empty($r['cond']))
			$val[$n]['cond'] = json_decode($r['cond'], true);

	$json = json_encode($val);

	$sql = "UPDATE `_element`
			SET `txt_1`='".addslashes($json)."'
			WHERE `id`=".$elem_id;
	query($sql);

	_elemOne($elem_id, true);
}





