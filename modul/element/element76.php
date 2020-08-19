<?php

/* [76] Видеоролик */

/* Структура элемента */
function _element76_struct($el) {
	return array(
		'width' => empty($el['width']) ? 150 : _num($el['width'])
	) + _elementStruct($el);
}
function _element76_title($el) {
	return _elem76novideo($el['width']);
}
function _element76_print($el, $prm) {
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$v = _elemPrintV($el, $prm);

	$frame = $prm['blk_setup'] ? '' :
	'<div class="_video-cont pad5 bg4 line-l line-b line-r'._dn($v).'">'.
		_elem76iframe($el, $v).
	'</div>';

	return
	'<div id="'._elemAttrId($el, $prm).'_video" class="_video prel"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-video pabs l5 top5"></div>'.
		'<div class="icon icon-del pl pabs r5 top5'._dn($v).'"></div>'.
		'<input type="text" id="'._elemAttrId($el, $prm).'" class="w100p b blue pl25"'.$disabled.' value="'.$v.'" />'.
		$frame.
	'</div>';
}
function _element76_print11($el, $u) {
	$ELP = $el['elp'];

	if(!$col = _elemCol($el))
		return _elem76novideo($el['width']);
	if(!$frame = _elem76iframe($ELP, $u[$col]))
		return _elem76novideo($el['width']);

	return $frame;
}
function _elem76iframe($el, $url) {//получение фрейма для вставки ролика
	$code = '';
	if(strpos($url, 'youtu.be')) {
		$ex = explode('youtu.be/', $url);
		if(!empty($ex[1]))
			$code = $ex[1];
	} elseif(strpos($url, 'watch')) {
		$ex = explode('watch?v=', $url);
		if(!empty($ex[1])) {
			$code = $ex[1];
			if(strpos($code, '&')) {
				$ex = explode('&', $code);
				$code = $ex[0];
			}
		}
	}

	if(!$code)
		return '';

	//делитель соотношения сторон
	$del = 0.5625;
	$w = !empty($el['width']) ? $el['width'] : _elemWidth($el);
	$w -= 12;
	$h = round($w * $del);

	return
	'<iframe'.
		' width="'.$w.'"'.
		' height="'.$h.'"'.
		' src="https://www.youtube.com/embed/'.$code.'"'.
		' frameborder="0"'.
		' allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"'.
		' allowfullscreen'.
	'></iframe>';
}
function _elem76novideo($width) {//отсутствующий видеоролик
	return '<img src="'.APP_HTML.'/img/novideo.png" width="'.$width.'" />';
}





