<?php

/* [43] Шаблон данных записи */

function _element43_struct($el) {
	return array(
	) + _elementStruct($el);
}

function _element43_print($el) {
	return
	'<div class="center pad10">'.
		'Шаблон записи'.
		'<br>'.
		'<b>'.$el['title'].'</b>'.
	'</div>';
}





