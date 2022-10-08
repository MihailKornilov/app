<?php

/* [94] Сумма новых записей за период */
function _element94_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//список
		'num_2'   => _num($el['num_2']),//элемент-сумма [13]
		'num_3'   => _num($el['num_3']),/*период:
												0 - всё время
											11172 - сегодня
											11173 - текущая неделя
											11174 - текущий месяц
											11175 - текущий год
											13694 - особые условия
										*/
		'txt_1' => $el['txt_1']         //условия
	) + _elementStruct($el);
}
function _element94_print($el) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return _msgRed('[94] не указан список');
	if(!$colSum = _elemCol($el['num_2']))
		return _msgRed('[94] не указан элемент-сумма');

	$sql = "SELECT SUM(`".$colSum."`)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG);

	switch($el['num_3']) {
		//сегодня
		case 11172:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')";
			break;
		//текущая неделя
		case 11173:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%u')=DATE_FORMAT(CURRENT_TIMESTAMP,'%u') AND `dtime_add`>DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)";
			break;
		//текущий месяц
		case 11174:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y-%m')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m')";
			break;
		//текущий год
		case 11175:
			$sql .= " AND DATE_FORMAT(`dtime_add`,'%Y')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y')";
			break;
		//особые условия
		case 13694:
			if(empty($el['txt_1'])) {
				$sql .= " AND !`id`";
				break;
			}
			$sql .= _40cond($el, $el['txt_1']);
			break;
	}

	return _sumSpace(DB1::value($sql));
}
