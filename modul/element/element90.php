<?php

/* [90] Изображение */
function _element90_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //ids изображений
		'num_1'   => _num($el['num_1']),//ширина
		'num_2'   => _num($el['num_2']),//разрешать высоту
		'num_3'   => _num($el['num_3']),//высота
		'num_4'   => _num($el['num_4']) //разрешать клик для увеличения
	) + _elementStruct($el);
}
function _element90_print($el) {
	//формирование ширины, если изображение отсутствует
	$w = $el['num_1'];
	if($el['num_2'])
		if($el['num_1'] > $el['num_3'])
			$w = $el['num_3'];

	if(!$image_id = _idsFirst($el['txt_1']))
		return _imageNo($w);

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	if(!$img = DB1::assoc($sql))
		return _imageNo($w);

	//если присутствует высота - подгонка картинки под размеры
	$w = $el['num_1'];
	$h = 0;
	if($el['num_2']) {
		$s = _imageResize($img['max_x'], $img['max_y'], $w, $el['num_3']);
		$w = $s['x'];
		$h = $s['y'];
	}

	return _imageHtml($img, $w, $h, false, $el['num_4']);
}
