<?php

/* [68] Список истории действий */
function _element68_struct($el) {
	return array(
		'num_8'   => _num($el['num_8']) //показывать историю записи, которую принимает текущая страница или диалог
	) + _elementStruct($el);
}
function _element68_print($el, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('История действий');

	return _historySpisok($el, $prm);
}

