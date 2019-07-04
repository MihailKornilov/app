<?php


/* [30] Суммы списка по месяцам */
function _element30_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//список
		'num_2'   => _num($el['num_2']) //значение суммы
	) + _elementStruct($el);
}
function _element30_print($el, $prm) {
	if(!$year = _num($_GET['v1']))
		return _emptyMinRed('Не получен номер года для отображения списка');
	if(!$spisok_id = $el['num_1'])
		return _emptyMinRed('Не указан список');
	if(!$DLG = _dialogQuery($spisok_id))
		return _emptyMinRed('Не получены данные диалога '.$spisok_id);
	if(!$col = _elemCol($el['num_2']))
		return _emptyMinRed('Не получено значение суммы');
		
	
	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%c')) AS `id`,
				SUM(`".$col."`) `sum`
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '".$year."-%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
	$mon = query_ass($sql);

	$itog = 0;
	$send = '<table class="_stab">'.
				'<tr><th>Месяц'.
					'<th>Сумма';
	for($n = 1; $n <= 12; $n++) {
		$sum = '';
		if(isset($mon[$n])) {
			$sum = _sumSpace($mon[$n], 1);
			$itog += $mon[$n];
		}
		$send .=
			'<tr><td class="r color-555">'._monthDef($n, 1).' '.$year.
				'<td class="r">'.$sum;
	}
	$send .=
			'<tr class="bg-gr1">'.
				'<td class="r b">Итог:'.
				'<td class="r">'._sumSpace($itog, 1).
		'</table>';

	return $send;
}
