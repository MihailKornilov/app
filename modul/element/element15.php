<?php

/* [15] Количество строк списка */
function _element15_struct($el) {
	return array(
		'num_1' => _num($el['num_1']),//id элемента, содержащего список, количество строк которого нужно выводить
		'txt_1' => $el['txt_1'],      //показана "1"
		'txt_2' => $el['txt_2'],      //запись   "1"
		'txt_3' => $el['txt_3'],      //показано "2"
		'txt_4' => $el['txt_4'],      //записи   "2"
		'txt_5' => $el['txt_5'],      //показано "5"
		'txt_6' => $el['txt_6'],      //записей  "5"
		'txt_7' => $el['txt_7']       //сообщение об отсутствии записей
	) + _elementStruct($el);
}
function _element15_title() {
	return 'Количество строк списка';
}
function _element15_print($el, $prm=array()) {
	if(!$elem_id = $el['num_1'])
		return 'Список не указан.';
	if(!$ELEM = _elemOne($elem_id))
		return 'Элемента, содержащего список, не существует.';

	//если результат нулевой, выводится сообщение из элемента, который размещает список
	switch($ELEM['dialog_id']) {
		case 14://список-шаблон
		case 23://список-таблица
			if(!$all = _spisokCountAll($ELEM, $prm))
				return $el['txt_7'];
			break;
		case 88://таблица из нескольких списков
			if(!$all = _elem88countAll($ELEM))
				return $el['txt_7'];
			break;
		default: return array();
	}

	return
	_end($all, $el['txt_1'], $el['txt_3'], $el['txt_5']).
	' '.
	$all.
	' '.
	_end($all, $el['txt_2'], $el['txt_4'], $el['txt_6']);
}
function _element15filterUpd($send, $elem_spisok) {//обновление значения после применения фильтра
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=15
			  AND `num_1`=".$elem_spisok."
			LIMIT 1";
	if(!$el = query_assoc($sql))
		return $send;

	$send['upd'][] = array(
		'id' => $el['id'],
		'html' => _element15_print($el)
	);

	return $send;
}


