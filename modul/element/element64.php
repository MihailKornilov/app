<?php

/* [64] Сумма значений списка */
function _element64_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//список [13]
		'num_2'   => _num($el['num_2']),//значение суммы [13]
		'txt_1'   => $el['txt_1'],      //текст перед суммой
		'txt_2'   => $el['txt_2'],      //текст после суммой
		'txt_3'   => $el['txt_3']       //условия
	) + _elementStruct($el);
}
function _element64_print($el, $prm=array()) {
	if(!$elem_id = $el['num_1'])
		return '[64] Список не указан';
	if(!$ELEM = _elemOne($elem_id))
		return '[64] Элемента, содержащего список, не существует';

	switch($ELEM['dialog_id']) {
		case 14:
		case 23: break;
		default: return '[64] Элемент не является списком';
	}

	if(!$DLG = _dialogQuery($ELEM['num_1']))
		return '[64] Диалога-списка не существует';
	if(!$col = _elemCol($el['num_2']))
		return '[64] Не найдена колонка значения суммы';

	$sql = "SELECT SUM(`".$col."`)
			FROM  "._queryFrom($DLG)."
			WHERE "._spisokWhere($ELEM, $prm).
					_40cond($el, $el['txt_3'], $prm);
	$sum = round(DB1::value($sql), 10);

	return $el['txt_1'].' '._sumSpace($sum).' '.$el['txt_2'];
}
function _element64filterUpd($send, $elem_spisok) {//обновление значения после применения фильтра
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=64
			  AND `num_1`=".$elem_spisok;
	if(!$arr = DB1::arr($sql))
		return $send;

	foreach($arr as $el)
		$send['upd'][] = array(
			'id' => $el['id'],
			'html' => _element64_print($el)
		);

	return $send;
}





