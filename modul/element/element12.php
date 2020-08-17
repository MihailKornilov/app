<?php

/* [12] Функция PHP (SA) */
function _element12_struct($el) {
	/*
		После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.
	*/
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],     //имя функции (начинается с PHP12)
		'txt_2' => $el['txt_2'],     //значение 1
		'txt_3' => $el['txt_3'],     //значение 2
		'txt_4' => $el['txt_4'],     //значение 3
		'num_1' => _num($el['num_1'])//условие 1
	) + _elementStruct($el);
}
function _element12_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element12_print($el, $prm) {
	if(!$el['txt_1'])
		return _emptyMin('Отсутствует имя функции.');
	if(!function_exists($el['txt_1']))
		return _emptyMinRed('Фукнции <b>'.$el['txt_1'].'</b> не существует.');
	if($prm['blk_setup'])
		return _emptyMin('Функция '.$el['txt_1']);

	$prm['el12'] = $el;

	return
	(!empty($el['col']) ?
		'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm, $el['txt_2']).'" />'
	: '').
		$el['txt_1']($prm);
}
function _element12_vvv($el, $prm) {
	$func = $el['txt_1'].'_vvv';

	if(!function_exists($func))
		return array();

	$prm['el12'] = $el;

	return $func($prm);
}
