<?php

/* [402] Диаграмма Ганта */

/* Структура элемента */
function _element402_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//список (id диалога) [24]
		'num_2'   => _num($el['num_2']),//элемент Дата начала [13]
		'num_3'   => _num($el['num_3']) //элемент Дата завершения [13]
	) + _elementStruct($el);
}

/* Вывод содержимого элемента на экран */
function _element402_print($EL, $prm=array()) {
	//диалог, через который вносятся данные списка
	if(!$DLG = _dialogQuery($EL['num_1']))
		return _emptyRed('Списка <b>'.$EL['num_1'].'</b> не существует.');

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `dtime_add`";
	if(!$spisok = query_arr($sql))
		return _empty('Данных нет.');

	$col = _elemCol($DLG['spisok_elem_id']);
	$send = '<table class="_stab">';
	$n = 1;
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="r clr1">'.$n++.
				'<td>'.$r[$col];
	}
	$send .= '</table>';

	return $send;
}

