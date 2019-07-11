<?php

/* [400] График: столбики */
function _element400_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //заголовок
		'num_1'   => _num($el['num_1'])//список (id диалога) [24]
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

	$days = array();//30 последних дней месяца - подписи
	$data = array();//данные
	$d30 = TODAY_UNIXTIME - 60*60*24*29;
	$dayLast = 100; //последний пройденный день (для вставки месяца)
	$w = date('w', $d30);//день недели
	while($d30 <= TODAY_UNIXTIME) {
		$day = _num(strftime('%d', $d30));
		$mon = $day < $dayLast ? _monthCut(strftime('%m', $d30)).' ' : '';
		$days[strftime('%Y-%m-%d', $d30)] = '"'.$mon.'<tspan'.(!$w || $w == 6 ? ' style=\"color:#d55\"' : '').'>'.$day.' '._week($w++).'</tspan>"';
		$data[strftime('%Y-%m-%d', $d30)] = '""';
		$d30 += 60*60*24;
		$dayLast = $day;
		if($w > 6)
			$w = 0;
	}

	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`,
				COUNT(`id`) `count`
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add`>DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY `day`
			ORDER BY `day`";
	foreach(query_ass($sql) as $d => $c)
		if(isset($data[$d]))
			$data[$d] = $c;

	return
	'<div id="chart_'.$el['id'].'"></div>'.
	'<script>'.
		'var WIDTH_'.$el['id'].'='._elemWidth($el).',
			 CAT_'.$el['id'].'=['.implode(',', $days).'],
			 SERIES_'.$el['id'].'=[{'.
				'name:"Все записи",'.
				'data:['.implode(',', $data).']'.
			'}];'.
	'</script>';
}