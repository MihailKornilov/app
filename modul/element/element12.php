<?php

/* [12] Функция PHP (SA) */
function _element12_struct($el) {
	/*
		После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.
	*/
	return array(
		'txt_1' => $el['txt_1'],      //имя функции (начинается с PHP12)
		'num_7' => _num($el['num_7']),//список (для указания связки)
		'txt_2' => $el['txt_2'],      //значение 1
		'txt_3' => $el['txt_3'],      //значение 2
		'txt_4' => $el['txt_4'],      //значение 3
		'num_1' => _num($el['num_1']),//условие 1
		'num_2' => _num($el['num_2']),//элемент 1 (диалог)
		'num_4' => _num($el['num_4']),//элемент 2 (диалог)
		'num_5' => _num($el['num_5']),//элемент 3 (диалог)
		'num_6' => _num($el['num_6']),//элемент 4 (диалог)
		'num_3' => _num($el['num_3']),//элемент 5 (страницa)

		'issp' => _num($el['num_7'])
	) + _elementStruct($el);
}
function _element12_title($el) {
	if(empty($el['name']))
		return '[12] Функция '.$el['txt_1'];
	return _emptyMin('Функция '.$el['txt_1'].'<br><b>'.$el['name'].'</b>');
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
function _element12_print11($el, $u) {//вывод содержания на основании функции
	$func = $el['txt_1'].'_print';

	if(!function_exists($func))
		return _msgRed('Отсутствует функция '.$func);

	return $func($el, $u);
}
function _element12_vvv($el, $prm) {
	$func = $el['txt_1'].'_vvv';

	if(!function_exists($func))
		return array();

	$prm['el12'] = $el;

	return $func($prm);
}
function _element12_vvv_count($el, $prm) {
	$func_vvv = $el['txt_1'].'_vvv';

	if(!function_exists($func_vvv))
		return 0;

	$prm['el12'] = $el;

	$func_vvv_count = $el['txt_1'].'_vvv_count';

	if(function_exists($func_vvv_count))
		return $func_vvv_count($prm);

	$vvv = $func_vvv($prm);

	return count($vvv);
}
function _element12_template_docx($el, $prm) {
	if(!$el['txt_1'])
		return '';

	$func = $el['txt_1'].'_template_docx';

	if(!function_exists($func))
		return '';

	$prm['el12'] = $el;

	return $func($prm);
}

