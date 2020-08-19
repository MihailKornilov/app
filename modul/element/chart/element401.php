<?php

/* [401] График по периодам */
function _element401_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //заголовок
		'num_1'   => _num($el['num_1']),//список (id диалога) [24]
		'num_2'   => _num($el['num_2']) //элемент суммы [13]
	) + _elementStruct($el);
}
function _element401_print($el, $prm) {
	if(!$year = _num(@$_GET['v1']))
		$year = YEAR_CUR;
	if(!$DLG = _dialogQuery($el['num_1']))
		return _emptyMinRed('[401] Диалога '.$el['num_1'].' не существует.');
	if($prm['blk_setup'])
		return _emptyMin('График по месяцам <b>'.$DLG['name'].'</b>');
	if(!$col = _elemCol($el['num_2']))
		return _emptyMinRed('Не получено значение суммы');

	$data = array();//данные
	for($n = 1; $n <= 12; $n++)
		$data[$n] = 0;

	$mon = array();
	foreach(_monthCut() as $m)
		$mon[] = '"'.$m.'"';

	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%c')) AS `id`,
				SUM(`".$col."`) `sum`
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '".$year."-%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
	foreach(query_ass($sql) as $n => $sum)
		$data[$n] = $sum;

	return
	'<div id="chart_'.$el['id'].'"></div>'.
	'<script>'.
		'var WIDTH_'.$el['id'].'='._elemWidth($el).',
			 CAT_'.$el['id'].'=['.implode(',', $mon).'],
			 SERIES_'.$el['id'].'=[{'.
				'name:"Все записи",'.
				'data:['.implode(',', $data).']'.
			'}];'.
	'</script>';
}
