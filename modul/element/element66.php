<?php

/* [66] Выбор цвета текста */
function _element66_struct($el) {
	return _elementStruct($el);
}
function _element66_print($el, $prm) {
	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm).'" />'.
		   '<div class="_color" style="background-color:#000"></div>';
}

