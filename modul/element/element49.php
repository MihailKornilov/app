<?php

/* [49] Выбор блоков из диалога или страницы */
function _element49_struct($el) {
	/*
		работает в паре с [19] - окно выбора блоков
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']//текст для placeholder
	) + _elementStruct($el);
}
function _element49_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$v = _elemPrintV($el, $prm);
	$ids = _ids($v);
	$count = _ids($ids, 'count');
	$title = $count ? $count.' блок'._end($count, '', 'а', 'ов') : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_sebl dib prel bg-fff over1" id="'._elemAttrId($el, $prm).'_sebl"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-cube pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp curP w100p color-ref"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}

