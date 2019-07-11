<?php

/* [34] Суммы нескольких списков по месяцам */
function _element34_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'] //данные о списках
	) + _elementStruct($el);
}
function _element34_print($el, $prm) {
	if(!$year = _num(@$_GET['v1']))
		return _emptyMinRed('Не получен номер года для отображения списка');

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
		$send .=
			'<tr class="over1">'.
				'<td class="r color-555">'._monthDef($n, 1).' '.$year.
				 _elem34td($mass, $n);
	}
	$send .=
			'<tr class="bg-gr1">'.
				'<td class="r b">Итог:'.
				 _elem34itog($mass).
		'</table>';

	return $send;
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





