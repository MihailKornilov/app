<?php

/* [34] Суммы нескольких списков по месяцам */
function _element34_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//страница, на которую будет переход при нажатии на название месяца
		'txt_1'   => $el['txt_1']       //данные о списках
	) + _elementStruct($el);
}
function _element34_print($el, $prm) {
	if(!$year = _num(@$_GET['v1']))
		$year = YEAR_CUR;

	$json = json_decode($el['txt_1'], true);
	$mass = array();
	foreach($json as $n => $r) {
		$mass[$n] = array();
		if(!$DLG = _dialogQuery($r['dialog_id']))
			continue;
		$sum = "COUNT(`id`)";
		if($col = _elemCol($r['sum_id']))
			$sum = "SUM(`".$col."`)";
		$sql = "SELECT
					DISTINCT(DATE_FORMAT(`dtime_add`,'%c')) AS `id`,
					".$sum."  `sum`
				FROM   "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
				  AND `dtime_add` LIKE '".$year."-%'
				GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
		$mass[$n] = query_ass($sql);
	}

	$send = '<table class="_stab w100p">'.
				'<tr><th class="w125">Месяц'.
					 _elem34th($json);
	for($n = 1; $n <= 12; $n++) {
		$mon = _monthDef($n, 1).' '.$year;
		if($p = $el['num_1']) {
			$v1 = $year.'-'.($n < 10 ? '0' : '').$n;
			$mon = '<a href="'.URL._elem34href($v1, $p).'">'.$mon.'</a>';
		}
		$send .=
			'<tr class="over1">'.
				'<td class="r color-555">'.$mon.
				 _elem34td($mass, $n);
	}
	$send .=
			'<tr class="bg-gr1">'.
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
		$sql = "SELECT
					DISTINCT(DATE_FORMAT(`dtime_add`,'%Y')) AS `id`,
					1
				FROM   "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
				GROUP BY DATE_FORMAT(`dtime_add`,'%Y')
				ORDER BY `dtime_add`";
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
	for($y = $min; $y <= $max; $y++)
		$send .= '<a href="'.URL.'?'._elem34href($y).'" class="fs14 mr10'.($y == $year ? ' b u' : '').'">'.$y.'</a>';

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
function _elem34td($mass, $mon) {//печать значений
	$send = '';
	foreach($mass as $r) {
		$send .= '<td class="r">';
		if(empty($r[$mon]))
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

	if(!$u = $prm['unit_edit'])
		return $send;
	if(!$EL = _elemOne($u['id']))
		return $send;

	$send['val'] = json_decode($EL['txt_1'], true);
	foreach($send['val'] as $n => $r) {
		if(!$el = _elemOne($r['sum_id']))
			continue;
		$send['val'][$n]['sum_title'] = $el['title'];
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

	$json = json_encode($val);

	$sql = "UPDATE `_element`
			SET `txt_1`='".addslashes($json)."'
			WHERE `id`=".$elem_id;
	query($sql);

	_elemOne($elem_id, true);
}





