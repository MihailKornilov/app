<?php

/* [5] textarea (многострочное текстовое поле) */
function _element5_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],//текст для placeholder
		'num_1' => _num($el['num_1']),/* тип начального текста
                                            8051: произвольный текст
                                            8052: указанное значение
									  */
		'num_2' => _num($el['num_2']),//форматирование текста
		'txt_2' => $el['txt_2'],//начальный текст: произвольный
		'txt_3' => $el['txt_3'] //начальный текст: указанное значение [13]
	) + _elementStruct($el);
}
function _element5_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$v = _elemPrintV($el, $prm, $el['txt_2']);
	$v = _element5vFromEl($el, $prm, $v);

	return
	'<textarea id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.'>'.
		$v.
	'</textarea>';
}
function _element5_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!isset($u[$col]))
		return '';

	$txt = $u[$col];

	if(!$el['num_2']) {
		$txt = _spisokColSearchBg($el['elp'], $txt);
		return _br($txt);
	}

	return '<div class="ck-content">'.htmlspecialchars_decode($txt).'</div>';
}
function _element5vFromEl($el, $prm, $v) {//начальный текст из указанного значения
	if($el['num_1'] != 8052)
		return $v;
	//значение может быть подставлено только при внесении записи
	if($u = $prm['unit_edit'])
		return $v;

	$page_id = _page('cur');
	if(!$page = _page($page_id))
		return '';
	if(!$dlg_id = $page['dialog_id_unit_get'])
		return '';
	if(!$id = _num(@$_GET['id']))
		return '';
	if(!$dialog = _dialogQuery($dlg_id))
		return '';
	if(!$u = _spisokUnitQuery($dialog, $id))
		return '';

	return _elemUids($el['txt_3'], $u);
}
function _element5_template_docx($el, $u) {
	$col = $el['col'];
	return _br($u[$col], "<w:br/>");
}

