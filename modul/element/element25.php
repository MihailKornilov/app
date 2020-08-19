<?php

/* [25] Кружок с цветом статуса */
function _element25_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],     //путь к заливке
		'num_1' => _num($el['num_1'])//диаметр
	) + _elementStruct($el);
}
function _element25_print($el, $prm) {
	$bg = $prm['blk_setup'] ? '#eee' : _elemUids($el['txt_1'], $prm['unit_get']);

	$css = 'width:'.$el['num_1'].'px;'.
		   'height:'.$el['num_1'].'px;'.
		   'border:#EAEBEC solid 1px;'.
		   'vertical-align:top;';

	if(!empty($bg))
		$css .= 'background-color:'.$bg.';';

	return '<div class="dib br1000" style="'.$css.'"></div>';
}

