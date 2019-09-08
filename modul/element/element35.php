<?php

/* [35] Count: количество */
function _element35_struct($el) {
	return array(
		'def'   => _num($el['def']),

		'num_1' => _num($el['num_1']),/* варианты значений:
												3681 - диапазон значений
												3682 - конкретные значения
										*/
		'num_2' => _num($el['num_2']),//разрешать минимум
		'num_3' => _num($el['num_3']),//минимум
		'num_4' => _num($el['num_4']),//минимум может быть отрицательным
		'num_5' => _num($el['num_5']),//разрешать максимум
		'num_6' => _num($el['num_6']),//максимум
		'num_7' => _num($el['num_7']),//шаг
		'num_8' => _num($el['num_8']),//разрешать переключение значений по кругу
		'txt_1' => $el['txt_1']       //конкретные значения, если num_1=3682 (настраиваются через PHP12_count_value)
	) + _elementStruct($el);
}
function _element35_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_3'   => _num($el['num_3']),
		'num_4'   => _num($el['num_4']),
		'num_5'   => _num($el['num_5']),
		'num_6'   => _num($el['num_6']),
		'num_7'   => _num($el['num_7']),
		'num_8'   => _num($el['num_8'])
	) + _elementJs($el);
}
function _element35_print($el, $prm) {
	return _count(array(
				'attr_id' => _elemAttrId($el, $prm),
				'width' => $el['width'],
				'value' => _elemPrintV($el, $prm, $el['def'])
		   ));
}
function _element35_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	$v = _num(@$u[$col]);

	if($el['num_1'] == 3681)
		return $v;

	if(!$json = _elem40json($el['txt_1']))
		return '';

	foreach($json['ids'] as $n => $id)
		if($v == $id)
			return $json['title'][$n];

	return '';
}
function _element35_vvv($el) {
	if($el['num_1'] != 3682)
		return array();

	return json_decode($el['txt_1']);
}
