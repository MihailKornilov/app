<?php

/* [130] Пин-код */
function _element130_struct($el) {
	return _elementStruct($el);
}
function _element130_print($el, $prm) {
	$txt = 'Установить';
	$color = 'grey';
	$dlg_id = 131;
	if(_user(USER_ID, 'pin')) {
		$txt = 'Изменить';
		$color = '';
		$dlg_id = 132;
	}
	return
	_button(array(
		'name' => $txt.' пин-код',
		'color' => $color,
		'class' => $prm['blk_setup'] ? 'curD' : 'dialog-open',
		'val' => 'dialog_id:'.$dlg_id
	));
}


