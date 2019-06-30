<?php

/* [70] Выбор цвета фона */
function _element70_struct($el) {
	return _elementStruct($el);
}
function _element70_print($el, $prm) {
	$v = _elemPrintV($el, $prm, '#fff');

	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
		   '<div class="_color-bg" style="background-color:'.$v.'"></div>';
}
function _element70_vvv($el, $prm) {
	$color = array(
		'#fff',
		'#ffffe4',
		'#e4ffe4',
		'#dff',
		'#ffe8ff',

		'#f9f9f9',
		'#ffb',
		'#cfc',
		'#aff',
		'#fcf',

		'#f3f3f3',
		'#fec',
		'#F2F2B6',
		'#D7EBFF',
		'#ffe4e4',

		'#ededed',
		'#FFDA8F',
		'#E3E3AA',
		'#B2D9FF',
		'#fcc'
	);

	$sel = '#fff';//выбранное значение
	if($u = $prm['unit_edit']) {
		$col = $el['col'];
		$sel = $u[$col];
	}

	$spisok = '';
	for($n = 0; $n < count($color); $n++) {
		$cls = $sel == $color[$n] ? ' class="sel"' : '';
		$spisok .= '<div'.$cls.' style="background-color:'.$color[$n].'" val="'.$color[$n].'">'.
						'&#10004;'.
				   '</div>';
	}
	return '<div class="_color-bg-choose">'.$spisok.'</div>';
}
function _element70_copy_field($el) {
	return array(
		'def'   => 0,
	);
}

