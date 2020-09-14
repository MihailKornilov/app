<?php

/* [39] Месяц и год */
function _element39_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']) //[18] начальное значение
										//     0 - текущий год и месяц
										//     1 - v1
	) + _elementStruct($el);
}
function _element39_print($el, $prm) {
	if(!$v = _elemPrintV($el, $prm)) {
		$cur = strftime('%Y-%m');
		if($el['num_1'] && $v = @$_GET['v1']) {
			if(!preg_match(REGEXP_YEARMON, $v))
				$v = $cur;
		} else
			$v = $cur;
	}

	$ex = explode('-', $v);

	$attr_id = _elemAttrId($el, $prm);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
	_count(array(
		'attr_id' => $attr_id.'_mon',
		'width' => 100,
		'class' => 'mr5',
		'value' => _num($ex[1])
	)).
	_count(array(
		'attr_id' => $attr_id.'_year',
		'width' => 70,
		'value' => $ex[0]
	));
}
function _element39_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$v = @$u[$col])
		return '';

	$ex = explode('-', $v);

	return _monthDef($ex[1]).' '.$ex[0];
}
function _element39_template_docx($el, $u) {
	return _element39_print11($el, $u);
}


