<?php

/* [43] Шаблон данных записи */

function _element43_struct($el) {
	return array(
	) + _elementStruct($el);
}

function _element43_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return
		'<div class="center pad10">'.
			'Шаблон записи'.
			'<br>'.
			'<b>'.$el['title'].'</b>'.
		'</div>';

	if(empty($prm['unit_get']))
		return $el['title'].': данные записи не получены.';

	return _blockHtml('tmp43', $el['id'], $prm);
}





