<?php

/* [93]  */
function _element93_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//список
		'num_2'   => _num($el['num_2']) /*период:
												0 - всё время
											11137 - сегодня
											11138 - текущая неделя
											11139 - текущий месяц
											11140 - текущий год
										*/
	) + _elementStruct($el);
}
function _element93_print($el) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return _msgRed('[93] не указан список');

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG);

	switch($el['num_2']) {
		//сегодня
		case 11137:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')";
			break;
		//текущая неделя
		case 11138:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%u')=DATE_FORMAT(CURRENT_TIMESTAMP,'%u') AND `dtime_add`>DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)";
			break;
		//текущий месяц
		case 11139:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y-%m')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m')";
			break;
		//текущий год
		case 11140:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y')";
			break;
	}

	return _num(query_value($sql));
}
