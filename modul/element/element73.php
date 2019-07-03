<?php

/* [73] Динамический путь даты */
function _element73_struct($el) {
	return array(
		'num_1'   => _num($el['num_1'])//фильтр-календарь [13]
	) + _elementStruct($el);
}
function _element73_print($el) {
	$F = _spisokFilter();
	if(empty($F['filter'][$el['num_1']]))
		return _msgRed('Отсутствует фильтр-календарь');

	$ex = explode(':', $F['filter'][$el['num_1']]['v']);

	$S = explode('-', $ex[0]);
	$send =  $S[0].' » '._monthDef($S[1]);

	if(isset($S[2]))
		$send .= ' » '._num($S[2]);

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
function _element73_copy_field($el) {
	return array(
		'num_1'   => _num($el['num_1'])
	);
}
function _element73filterUpd($send, $elem_spisok) {//обновление значения после применения фильтра
	//поиск фильтра-календаря, привязанного к списку
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=77
			  AND `num_1`=".$elem_spisok."
			LIMIT 1";
	if(!$el = query_assoc($sql))
		return $send;

	//поиск элемента-пути, привязанного к календарю
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=73
			  AND `num_1`=".$el['id']."
			LIMIT 1";
	if(!$elp = query_assoc($sql))
		return $send;

	$send['upd'][] = array(
		'id' => $elp['id'],
		'html' => _element73_print($elp)
	);

	return $send;
}







