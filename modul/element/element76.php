<?php

/* [76] Видеоролик */

/* Структура элемента */
function _element76_struct($el) {
	return array(
	) + _elementStruct($el);
}


/* Вывод содержимого элемента на экран */
function _element76_print($el, $prm) {
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	//делитель соотношения сторон
	$del = 0.5625;
	$w = $el['width'] ? $el['width'] : _elemWidth($el);
	$w -= 12;
	$h = round($w * $del);


	$frame = $prm['blk_setup'] ? '' :
	'<div class="_video-cont pad5 bg4 line-l line-b line-r">'.
		'<iframe'.
			' width="'.$w.'"'.
			' height="'.$h.'"'.
			' src="https://www.youtube.com/embed/8KuUA73AYZE"'.
			' frameborder="0"'.
			' allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"'.
			' allowfullscreen'.
		'></iframe>'.
	'</div>';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'"'.$disabled.' value="" />'.
	'<div id="'._elemAttrId($el, $prm).'_video" class="_video prel"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-video pabs l5 top5"></div>'.
		'<input type="text" class="w100p pl25"'.$disabled.' />'.
		$frame.
	'</div>';
}

