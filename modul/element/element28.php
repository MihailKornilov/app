<?php

/* [28] Загрузка файла */
function _element28_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']  //нулевое значение
	) + _elementStruct($el);
}
function _element28_print($el, $prm) {
	$v = _elemPrintV($el, $prm, 0);

	$width = 0;
	if($bl = @$el['block'])
		$width = $bl['width'];

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_attach">'.
		'<div id="'._elemAttrId($el, $prm).'_atup" class="atup'._dn(!$v).'"'._elemStyleWidth($el).'>'.
			'<form method="post" action="'.AJAX.'" enctype="multipart/form-data" target="at-frame">'.
				'<input type="hidden" name="op" value="attach_upload" />'.
				'<input type="file" name="f1" class="at-file"'._elemStyleWidth($el).' />'.// accept="' + acceptMime() + '"
			'</form>'.
			'<button class="vk small grey w100p">'.$el['txt_1'].'</button>'.
			'<iframe name="at-frame"></iframe>'.
		'</div>'.
		'<table class="atv'._dn($v).'">'.
			'<tr><td class="top">'._attachLink($v, $width).
				'<th class="top wsnw">'.
//					'<div class="icon icon-set mtm2 ml5 pl'._tooltip('Параметры файла', -56).'</div>'.
					'<div class="icon icon-del-red ml5 mtm2 pl'._tooltip('Отменить', -30).'</div>'.
		'</table>'.
	'</div>';
}
function _element28_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	$width = 0;
	if($bl = @$el['elp']['block'])
		$width = $bl['width'];

	return _attachLink(@$u[$col], $width);
}

