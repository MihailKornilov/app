<?php

/* [72] Фильтр: год и месяц */
function _element72_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента - список, на который происходит воздействие
		'num_2'   => _num($el['num_2']) //id элемента - путь к сумме для подсчёта по каждому месяцу
	) + _elementStruct($el);
}
function _element72_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element72_print($el, $prm) {
	$v = _spisokFilter('vv', $el, strftime('%Y-%m'));

	$ex = explode('-', $v);
	$year = $ex[0];
	$mon  = $ex[1];


	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	_yearleaf(array(
		'attr_id' => _elemAttrId($el, $prm).'yl',
		'value' => $ex[0]
	)).
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
	'</div>';
}
function _elem72Sum($el, $year) {//получение сумм для фильтра [72]
	$spisok = _monthDef();

	if(!$el = _elemOne($el['num_2']))
		return $spisok;
	if(!$col = $el['col'])
		return $spisok;
	if(!$bl = $el['block'])
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

