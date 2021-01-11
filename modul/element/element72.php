<?php

/* [72] Фильтр: год и месяц */
function _element72_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента - список, на который происходит воздействие (num_3 = 1)
		'num_2'   => _num($el['num_2']),//id элемента - путь к сумме для подсчёта по каждому месяцу
		'num_3'   => _num($el['num_3']),/* [17] воздействие на:
											1 - список
											2 - страница
										*/
		'num_4'   => _num($el['num_4']),/* [17] период фильтрации:
											1 - по году
											2 - по году и месяцу
										*/
		'num_5'   => _num($el['num_5']),//[13] фильтр по значению
		'txt_1' => $el['txt_1']         //дополнительные условия для отображения сумм
	) + _elementStruct($el);
}
function _element72_print($el, $prm) {
	$isMon = $el['num_4'] == 2;
	$year = $isMon ? YEAR_MON : YEAR_CUR;
	switch($el['num_3']) {
		case 1://список
			if(!$el['num_1'])
				return _emptyMinRed('[72] Не выбран список');
			$year = _filter('vv', $el, $year);
			break;
		case 2://страница
			if(preg_match(REGEXP_YEARMON, @$_GET['v1']))
				$year = $_GET['v1'];
			break;
		default:
			return _emptyMinRed('[72] Не указан объект воздействия');
	}

	if($isMon) {
		$ex = explode('-', $year);
		$year = $ex[0];
		$mon  = $ex[1];
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$year.'" />'.
	_yearleaf(array(
		'attr_id' => _elemAttrId($el, $prm).'yl',
		'value' => $year
	)).
($isMon ?
	'<div class="mt5">'.
		_radio(array(
			'attr_id' => _elemAttrId($el, $prm).'rd',
			'width' => 0,
			'block' => 1,
			'light' => 1,
			'interval' => 5,
			'value' => $mon,
			'spisok' => _elem72Sum($el, $year),
			'disabled' => $prm['blk_setup']
		)).
	'</div>'
: '');
}
function _elem72Sum($EL, $year) {//получение сумм для фильтра [72]
	$spisok = _monthDef();

	if($EL['num_3'] != 1)
		return $spisok;
	if(!$el = _elemOne($EL['num_2']))
		return $spisok;
	if(!$col = _elemCol($el))
		return $spisok;
	if(!$bl = _blockOne($el['block_id']))
		return $spisok;
	if($bl['obj_name'] != 'dialog')
		return $spisok;
	if(!$DLG = _dialogQuery($bl['obj_id']))
		return $spisok;

	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%m')) AS `mon`,
				SUM(`".$col."`) `sum`
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
				  "._40cond(array(), $EL['txt_1'])."
			  AND `dtime_add` LIKE '".$year."-%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
	if(!$arr = query_array($sql))
		return $spisok;

	foreach($arr as $r) {
		$mon = _num($r['mon']);
		$txt = $spisok[$mon];
		$spisok[$mon] = $txt.
						'<span class="fr">'._sumSpace(round($r['sum'])).'</span>';
	}

	return $spisok;
}
function _elem72filter($el) {//фильтр: год и месяц
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 72) {
			$col = 'dtime_add';
			if($col5 = _elemCol($r['elem']['num_5']))
				$col = $col5;
			return " AND `t1`.`".$col."` LIKE '".$r['v']."-%'";
		}

	return '';
}

