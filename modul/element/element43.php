<?php

/* [43] Шаблон данных записи */

function _element43_struct($el) {
	return array(
	) + _elementStruct($el);
}

function _element43_print($el) {
	return
	'<div class="center pad10 bg9 bor-dash br3">'.
		'<div class="fs14 clr1">Шаблон записи:</div>'.
		'<div class="fs14 b">'._element('title', $el).'</div>'.
	'</div>';
}
function _element43_print11($el, $u) {
	if(empty($u))
		return _emptyMinRed('Не получены данные записи');

	$prm = _blockParam();
	$prm['unit_get'] = $u;

	return _blockHtml('tmp43', $el['id'], $prm);
}




