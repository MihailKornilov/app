<?php

/* [60] Загрузка изображений */
function _element60_struct($el) {
	return array(
		'num_1' => _num($el['num_1'])//максимальное количество изображений, которое разрешено загрузить
	) + _elementStruct($el);
}
function _element60_title($el) {
	if(empty($el['elp']))
		return _imageNo();

	$elp = $el['elp'];

	return _imageNo($elp['width'], $elp['num_8']);
}
function _element60_print($el, $prm) {
	return _image($el, $prm);
}
function _element60_print11($el, $u) {
	$ELP = $el['elp'];

	//ячейка таблицы
	if($pid = _num($ELP['parent_id'])) {
		if($ell = _elemOne($pid))
			if($ell['dialog_id'] == 23) {
				if(!$col = _elemCol($el))
					return '';//_imageNo(20);
				if(empty($u[$col]['id']))
					return '';//_imageNo(20);
				return _imageHtml($u[$col], 28, 28, false, true);
			}
		return $el['title'];
	}


	if(!$col = _elemCol($el))
		return _imageNo($ELP['width'], $ELP['num_8']);
	if(empty($u[$col]['id']))
		return _imageNo($ELP['width'], $ELP['num_8']);

	return _imageHtml($u[$col], $ELP['width'], $ELP['num_7'], $ELP['num_8']);
}






function _image($el, $prm) {//элемент - загрузка изображений [60]
/*
	Загрузка изображений производится тремя способами:
		1. Выбор файла
		2. Вставка прямой ссылки, либо скриншок
		3. Вебкамера

	Данные об изображениях хранятся в таблице `_image`.
	В объекте указываются id прикреплённых изображений в текстовой колонке.
	Если id изображения со знаком минус - это изображение было удалено и находится с корзине объекта.

	Просмотр изображения производится диалогом [65].
	Класс '.image-open' отвечает за открытие изображения.
	Диалогу [65] передаются все идентифитаторы изображений, прикреплённые объекту.

	Функция _spisokImage переводит ПЕРВЫЙ id изображения в данные для каждого объекта.
	Идентификаторы помещаются в переменную 'ids'.
	Если изображений нет в объекте, создаётся пустой массив array('ids'=>'').
*/

	if($prm['blk_setup'])
		return _emptyMin('Изображения');

	$html = '';
	if($v = _elemPrintV($el, $prm)) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id` IN ("._ids($v).")
				ORDER BY `sort`";
		foreach(query_arr($sql) as $r)
			$html .= _imageDD($r);
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_image">'.
		'<dl>'.
			$html.
			'<dd class="dib">'.
				'<table class="_image-load">'.
					'<tr><td>'.
							'<div class="_image-add icon-image"></div>'.
							'<div class="icon-image spin"></div>'.
							'<div class="_image-prc"></div>'.
							'<div class="_image-dis"></div>'.
							'<table class="tab-load">'.
								'<tr><td class="icon-image ii1">'.//Выбрать из файлов
										'<form>'.
											'<input type="file" accept="image/jpeg,image/png,image/gif,image/tiff" />'.
										'</form>'.
									'<td class="icon-image ii2">'.//Указать ссылку на изображение
								'<tr><td class="icon-image ii3">'.//Фото с вебкамеры
									'<td class="icon-image ii4 empty">'.//Достать из корзины
							'</table>'.

				'</table>'.
			'</dd>'.
		'</dl>'.
		'<div class="_image-link dn mt5">'.
			'<table class="w100p">'.
				'<tr><td>'.
						'<input type="text" class="w100p" placeholder="вставьте ссылку или скриншот и нажмите Enter" />'.
					'<td class="w50 center">'.
						'<div class="icon icon-ok"></div>'.
						'<div class="icon icon-del pl ml5"></div>'.
			'</table>'.
		'</div>'.
	'</div>';
}
function _imageServerCache() {//кеширование серверов изображений
	$key = 'IMG_SERVER';
	if($arr = _cache_get($key, 1))
		return $arr;

	$sql = "SELECT `id`,`path` FROM `_image_server`";
	return _cache_set($key, query_ass($sql), 1);
}
function _imageServer($v) {//получение сервера (пути) для изображнения
/*
	если $v - число, получение имени пути
	если $v - текст, это сам путь и получение id пути. Если нет, то создание
*/
	if(empty($v))
		return '';

	$SRV = _imageServerCache();

	//получение id пути
	if($server_id = _num($v)) {
		if(empty($SRV[$server_id]))
			return '';

		return $SRV[$server_id];
	}

	foreach($SRV as $id => $path)
		if($v == $path)
			return $id;

	//внесение в базу нового пути
	$sql = "INSERT INTO `_image_server` (
				`path`,
				`user_id_add`
			) VALUES (
				'".addslashes($v)."',
				"._num(@USER_ID)."
			)";
	$insert_id = query_id($sql);

	_cache_clear('IMG_SERVER', 1);

	return $insert_id;
}
function _imageNo($width=80, $cr=false) {//картинка, если изображнеия нет
	return
	'<img src="'.APP_HTML.'/img/nofoto-s.gif"'.
		' width="'.$width.'"'.
 ($cr ? ' class="br1000"' : '').//круглое фото
	' />';
}
function _imageHtml($r, $width=80, $h=0, $cr=false, $click=true) {//получение картинки в html-формате
	if(empty($r))
		return _imageNo($width, $cr);
	if(!is_array($r))
		return _imageNo($width, $cr);
	if(empty($r['id']))
		return _imageNo($width, $cr);

	$width = $width ? $width : 80;

	$st = $width > 80 ? 'max' : 80;
	$width = $width > $r['max_x'] ? $r['max_x'] : $width;
	if($h) {
		$s = _imageResize($r['max_x'], $r['max_y'], $width, $width);
		$width = $s['x'];
		$h = $s['y'];
	}

	$cls = array();
	if($click)
		$cls[] = 'image-open';
	if($cr)
		$cls[] = 'br1000';

	return
		'<img src="'._imageServer($r['server_id']).$r[$st.'_name'].'"'.
			' width="'.$width.'"'.
	  ($h ? ' height= "'.$h.'"' : '').
	($cls ? ' class="'.implode(' ', $cls).'"'.
			' val="'.(empty($r['ids']) ? $r['id'] : $r['ids']).'"'
  : '').

		' />';
}
function _imageNameCreate() {//формирование имени файла из случайных символов
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name, $exp) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagealphablending($im_new, false);//устанавливает режим смешивания
	imagesavealpha($im_new, true);//сохранять информацию о прозрачности
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	switch($exp) {
		case 'png': imagepng($im_new, $name); break;
		case 'gif': imagegif($im_new, $name); break;
		default: imagejpeg($im_new, $name, 79);
	}
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//изменение размера изображения с сохранением пропорций
	$x = $x_new;
	$y = $y_new;
	// если ширина больше или равна высоте
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // если новая ширина больше, чем исходная, то X остаётся исходным
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // если новая высота в итоге осталась меньше исходной, то подравнивание по Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// если высота больше ширины
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // если новая высота больше, чем исходная, то Y остаётся исходным
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // если новая ширина в итоге осталась меньше исходной, то подравнивание по X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}

function _imageLink($url, $return='arr') {//сохранение изображения по прямой ссылке
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
	    CURLOPT_TIMEOUT => 60,//максимальное время работы cURL
	    CURLOPT_FOLLOWLOCATION => 1,//следовать перенаправлениям
	    CURLOPT_RETURNTRANSFER => 1,//результат писать в переменную
	    CURLOPT_NOPROGRESS => 0,//индикатор загрузки данных
	    CURLOPT_BUFFERSIZE => 1024,//размер буфера 1 Кбайт
	    //функцию для подсчёта скачанных данных. Подробнее: http://stackoverflow.com/a/17642638
	    CURLOPT_PROGRESSFUNCTION => function ($ch, $dwnldSize, $dwnld, $upldSize) {
	        if($dwnld > 1024 * 1024 * 15)//Когда будет скачано больше 15 Мбайт, cURL прервёт работу
	            return 1;
	        return 0;
	    },
	    CURLOPT_SSL_VERIFYPEER => 0//проверка сертификата
//	    CURLOPT_SSL_VERIFYHOST => 2,//имя сертификата и его совпадение с указанным хостом
//	    CURLOPT_CAINFO => __DIR__ . '/cacert.pem'//сертификат проверки. Скачать: https://curl.haxx.se/docs/caextract.html
	));

	//код последней ошибки
	if(curl_errno($ch)) {
		_debugLog('Ошибка при загрузке изображения: '.$url);
		if($return == 'id')
			return 0;
		else
			jsonError('При загрузке произошла ошибка');
	}

	$raw   = curl_exec($ch);    //данные в переменную
	$info  = curl_getinfo($ch); //информация об операции
	curl_close($ch);//завершение сеанса cURL

	if(!is_dir(APP_PATH.'/.tmp'))
		mkdir(APP_PATH.'/.tmp', 0777, true);

	$file_tmp_name = APP_PATH.'/.tmp/'.rand(0, 99999999).'_'.TODAY_UNIXTIME.'.tmp';
	$file = fopen($file_tmp_name,'w');
	fwrite($file, $raw);
	fclose($file);

	$send = _imageSave($info['content_type'], $file_tmp_name, $return);
	unlink($file_tmp_name);

	return $send;
}
function _imageSave($file_type, $file_tmp_name, $return='arr') {//сохранение полученного изображения
	$im = null;
	if(!defined('APP_ID'))
		define('APP_ID', 0);
	$IMAGE_PATH = APP_PATH.'/.image/'.APP_ID;
	$server_id = _imageServer('//'.DOMAIN.APP_HTML.'/.image/'.APP_ID.'/');

	//создание директории, если отсутствует
	if(!is_dir($IMAGE_PATH))
		mkdir($IMAGE_PATH, 0777, true);

	$exp = 'jpg';
	switch($file_type) {
		case 'image/jpeg': $im = @imagecreatefromjpeg($file_tmp_name); break;
		case 'image/png': $im = @imagecreatefrompng($file_tmp_name); $exp = 'png'; break;
		case 'image/gif': $im = @imagecreatefromgif($file_tmp_name); $exp = 'gif'; break;
		case 'image/tiff':
			$tmp = $IMAGE_PATH.'/'.USER_ID.'.jpg';
			$image = NewMagickWand(); // magickwand.org
			MagickReadImage($image, $file_tmp_name);
			MagickSetImageFormat($image, 'jpg');
			MagickWriteImage($image, $tmp); //сохранение результата
			ClearMagickWand($image); //удаление и выгрузка полученного изображения из памяти
			DestroyMagickWand($image);
			$im = @imagecreatefromjpeg($tmp);
			unlink($tmp);
			break;
	}


	if(!$im)
		jsonError('Загруженный файл не является изображением.<br>Выберите JPG, PNG, GIF или TIFF формат.');

	$x = imagesx($im);
	$y = imagesy($im);
	if($x < 10 || $y < 10)
		jsonError('Изображение слишком маленькое.<br>Используйте размер не менее 10x10 px.');

	$fileName = time().'-'._imageNameCreate();
	$NAME_MAX = $fileName.'-900.'.$exp;
	$NAME_80 = $fileName.'-80.'.$exp;

	$max = _imageImCreate($im, $x, $y, 900, 900, $IMAGE_PATH.'/'.$NAME_MAX, $exp);
	$_80 = _imageImCreate($im, $x, $y, 80, 80, $IMAGE_PATH.'/'.$NAME_80, $exp);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,0) FROM `_image`";
	$sort = query_value($sql);

	$sql = "INSERT INTO `_image` (
				`app_id`,
				`server_id`,

				`max_name`,
				`max_x`,
				`max_y`,
				`max_size`,

				`80_name`,
				`80_x`,
				`80_y`,
				`80_size`,

				`sort`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$server_id.",

				'".$NAME_MAX."',
				".$max['x'].",
				".$max['y'].",
				".$max['size'].",

				'".$NAME_80."',
				".$_80['x'].",
				".$_80['y'].",
				".$_80['size'].",

				".$sort.",
				"._num(@USER_ID)."
		)";
	$image_id = query_id($sql);

	if($return == 'id')
		return $image_id;

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	return query_assoc($sql);
}
function _imageDD($img) {//единица изображения для настройки
	return
	'<dd class="dib mr3 curM" val="'.$img['id'].'">'.
		'<div class="icon icon-off tool" data-tool="Переместить в корзину"></div>'.
		'<table class="_image-unit">'.
			'<tr><td>'.
				_imageHtml($img, 80, 1).
		'</table>'.
	'</dd>';
}

function _image60_save($cmp, $unit) {//Применение загруженных изображений после сохранения
	//поле, хранящее список id изображений
	if(!$col = _elemCol($cmp))
		return;
	if(!$img = $unit[$col])
		return;
	if(!$ids = @$img['ids'])
		return;

	foreach(explode(',', $ids) as $n => $id) {
		if($id < 0)
			continue;
		$sql = "UPDATE `_image`
				SET `sort`=".$n."
				WHERE `id`=".$id;
		query($sql);
	}
}


function PHP12_image_show($prm) {//просмотр изображений
	$image = 'Изображение отсутствует.';//основная картинка, на которую нажали. Выводится первой
	$spisok = '';//html-список дополнительных изображений
	$spisokJs = array();//js-список всех изображений
	$spisokIds = array();//id картинок по порядку
	$image_id = 0;

	if($ids = $prm['dop']) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id`="._idsFirst($ids);
		if($im = query_assoc($sql)) {
			$image_id = $im['id'];
			$image = '<img src="'._imageServer($im['server_id']).$im['max_name'].'"'.
						 ' width="'.$im['max_x'].'"'.
						 ' height="'.$im['max_y'].'"'.
						 ' />';

			$sql = "SELECT *
					FROM `_image`
					WHERE `id` IN (".$ids.")
					ORDER BY `sort`";
			$arr = query_arr($sql);
			if(count($arr) > 1) {
				$spisok = '<div class="line-t pad10 center bg-gr2">';
				foreach($arr as $r) {
					$sel = $r['id'] == $image_id ? ' sel' : '';
					$spisok .=
					'<div class="dib ml3 mr3">'.
						'<table class="iu'.$sel.'" val="'.$r['id'].'">'.
							'<tr><td><img src="'._imageServer($r['server_id']).$r['80_name'].'"'.
										' width="'.$r['80_x'].'"'.
										' height="'.$r['80_y'].'"'.
									' />'.
						'</table>'.
					'</div>';
					$spisokJs[] = $r['id'].':{'.
						'src:"'.addslashes(_imageServer($r['server_id']).$r['max_name']).'",'.
						'x:'.$r['max_x'].','.
						'y:'.$r['max_y'].','.
					'}';
					$spisokIds[] = $r['id'];
				}
				$spisok .= '</div>';
			}
		}
	}

	return
	'<div id="_image-show">'.
		'<table class="w100p">'.
			'<tr><td id="_image-main" val="'.$image_id.'">'.
					$image.
		'</table>'.
		$spisok.
	'</div>'.
	'<script>'.
		'var IMG_ASS={'.implode(',', $spisokJs).'},'.
			'IMG_IDS=['.implode(',', $spisokIds).'];'.
	'</script>';
}
function PHP12_image_deleted($prm) {//удалённые изображения [63]
	if(!$dop = $prm['dop'])
		return '<div class="_empty min">Удалённых изображений нет</div>';

	$ids = array();
	foreach(explode(',', $dop) as $r) {
		if(!$id = _num($r, 1))
			continue;
		if($id > 0)
			continue;
		$ids[] = abs($id);
	}

	if(empty($ids))
		return '<div class="_empty min">Удалённые изображения отсутствуют</div>';

	$sql = "SELECT *
			FROM `_image`
			WHERE `id` IN (".implode(',', $ids).")
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">Удалённые изображения не найдены</div>';

	$html = '';
	foreach($arr as $r) {
		$html .=
		'<div class="prel dib ml3 mr3">'.
			'<div val="'.$r['id'].'" class="icon icon-recover tool" data-tool="Восстановить"></div>'.
			'<table class="_image-unit">'.
				'<tr><td>'.
					_imageHtml($r, 80, 1).
			'</table>'.
		'</div>';
	}

	return '<div class="_image">'.$html.'</div>';
}
function PHP12_image_webcam($prm) {//Веб-камера [61]
	$el = $prm['el12'];
	$width = _blockCh($el['block_id'], 'width');
	$mar = explode(' ', $el['mar']);
	$width = round($width - $mar[1] - $mar[3]);
	$height = round($width * 0.75);

	$flashvars =
		'width='.$width.
		'&height='.$height.
		'&dest_width='.$width.
		'&dest_height='.$height.
		'&image_format=jpeg'.
		'&jpeg_quality=100'.
		'&enable_flash=true'.
		'&force_flash=false'.
		'&flip_horiz=false'.
		'&fps=30'.
		'&upload_name=webcam'.
		'&constraints=null'.
		'&swfURL=""'.
		'&flashNotDetectedText=""'.
		'&noInterfaceFoundText=""'.
		'&unfreeze_snap=true'.
		'&iosPlaceholderText=""'.
		'&user_callback=null'.
		'&user_canvas=null';

	return
	'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"'.
			' type="application/x-shockwave-flash"'.
	        ' codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0"'.
	        ' width="'.$width.'"'.
	        ' height="'.$height.'"'.
	        ' align="middle">'.
	            '<param name="wmode" value="opaque" />'.
				'<param name="allowScriptAccess" value="always" />'.
				'<param name="allowFullScreen" value="false" />'.
				'<param name="movie" value="" />'.
				'<param name="loop" value="false" />'.
				'<param name="menu" value="false" />'.
				'<param name="quality" value="best" />'.
				'<param name="bgcolor" value="#ffffff" />'.
				'<param name="flashvars" value="'.$flashvars.'" />'.
				'<embed src="'.APP_HTML.'/modul/element/webcam.swf?2"'.
					  ' wmode="opaque" loop="false" menu="false" quality="best" bgcolor="#ffffff" width="'.$width.'" height="'.$height.'" name="webcam_movie_embed" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="'.$flashvars.'">'.
				'</embed>'.
	'</object>';
}
