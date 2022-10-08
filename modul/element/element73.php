<?php

/* [73] Динамический путь даты */
function _element73_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//фильтр-календарь [13]
		'num_2'   => _num($el['num_2']),//страница для годов
		'num_3'   => _num($el['num_3']),//страница для месяцев
		'num_4'   => _num($el['num_4']) //страница для дней
	) + _elementStruct($el);
}
function _element73_print($el) {
	$F = _filter();
	if(!$fl = @$F['filter'][$el['num_1']])
		return _msgRed('Отсутствует фильтр-календарь');

	$v = $fl['v'];

	switch($v) {
		case 2819:
			$v = TODAY;
			break;
		case 2820:
			$v = _calendarWeek();
			break;
		case 2821:
			$v = YEAR_MON;
			break;
	}

	$ex = explode(':', $v);
	$S = explode('-', $ex[0]);
	$send =
		($el['num_2'] ? '<a href="'.URL.'&p='.$el['num_2'].'" class="inhr">'.$S[0].'</a>' : $S[0]).
		' » '.
		($el['num_3'] ? '<a href="'.URL.'&p='.$el['num_3'].'&v1='.$S[0].'" class="inhr">'._monthDef($S[1]).'</a>' : _monthDef($S[1]));

	if(isset($S[2]))
		$send .= ' » '.($el['num_4'] ? '<a href="'.URL.'&p='.$el['num_4'].'&v1='.'&v1='.$S[0].'-'.$S[1].'" class="inhr">'._num($S[2]).'</a>' : _num($S[2]));

	if(isset($ex[1])) {
		$E = explode('-', $ex[1]);
		$send .= ' - '._num($E[2]);
		if($E[1] != $S[1])
			$send .= ' '._monthFull($E[1]);
		if($E[0] != $S[0])
			$send .= ' '.$E[0];
	}

	return $send;
}
function _element73filterUpd($send, $elem_spisok) {//обновление значения после применения фильтра
	//поиск фильтра-календаря, привязанного к списку
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=77
			  AND `num_1`=".$elem_spisok."
			LIMIT 1";
	if(!$el = DB1::assoc($sql))
		return $send;

	//поиск элемента-пути, привязанного к календарю
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=73
			  AND `num_1`=".$el['id']."
			LIMIT 1";
	if(!$elp = DB1::assoc($sql))
		return $send;

	$send['upd'][] = array(
		'id' => $elp['id'],
		'html' => _element73_print($elp)
	);

	return $send;
}







