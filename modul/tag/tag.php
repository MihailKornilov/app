<?php
/* Элементы-теги */

require_once GLOBAL_DIR.'/modul/tag/tag_select/tag_select.php';


function _tag_script() {//скрипты и стили элементов-тегов
	return
	//общие скрипты
	'<link rel="stylesheet" type="text/css" href="modul/tag/tag'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/tag/tag'.MIN.'.js?'.SCRIPT.'"></script>'.

	//_select
	'<link rel="stylesheet" type="text/css" href="modul/tag/tag_select/tag_select'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/tag/tag_select/tag_select'.MIN.'.js?'.SCRIPT.'"></script>';
}

function _check($v=array()) {//элемент ГАЛОЧКА
	$attr_id = empty($v['attr_id']) ? 'check'.rand(1, 100000) : $v['attr_id'];

	$cls = '_check php ';
	$cls .= empty($v['block']) ?    '' : ' block';       //display:block, иначе inline-block
	$cls .= empty($v['disabled']) ? '' : ' disabled';    //неактивное состояние
	$cls .= empty($v['ignore']) ? '' : ' ignore';        //игнорирование (неактивное, слабо видно)
	$cls .= isset($v['light']) && empty($v['light']) ?    '' : ' light';       //если галочка не стоит, текст бледный
	$cls .= empty($v['class']) ?    '' : ' '.$v['class'];//дополнительные классы

	$val = _bool(@$v['value']);
	$cls .= $val ? ' on' : '';      //галочка поставлена или нет

	$title = empty($v['title']) ? '&nbsp;' : $v['title'];
	$cls .= empty($v['title']) ? '' : ' title'; //отступ от галочки, если есть текст

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />'.
	'<div class="'.$cls.'" id="'.$attr_id.'_check">'.
		$title.
	'</div>';
}
function _radio($v=array()) {//элемент RADIO
	$attr_id = empty($v['attr_id']) ? 'radio'.rand(1, 100000) : $v['attr_id'];
	$title0 = @$v['title0'];
	$spisok = @$v['spisok'] ? $v['spisok'] : array();//содержание в виде id => title
	$value = _num(@$v['value']);
	$dis = empty($v['disabled']) ? '' : ' disabled';
	$ignore = empty($v['ignore']) ? '' : ' ignore';        //игнорирование (неактивное, слабо видно)
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;
	$width = _elemStyleWidth($v);

	//если список пуст и только нулевое значение, отступ снизу не делается
	$int = empty($spisok) ? 0 : $interval;
	$html = _radioUnit(0, $block, $title0, $int, $value == 0);

	if(is_array($spisok) && !empty($spisok)) {
		end($spisok);
		$idEnd = key($spisok);

		if(!is_array($spisok[$idEnd]))
			$spisok = _sel($spisok);

		foreach($spisok as $i => $r) {
			//отступ снизу после последнего значения не делается
			$int = $idEnd == $i ? 0 : $interval;
			$html .= _radioUnit($r['id'], $block, $r['title'], $int, $value == $r['id']);
		}
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$ignore.$light.'"'.$width.'>'.
		$html.
	'</div>';
}
function _radioUnit($id, $block, $title, $interval, $on) {
	if(empty($title))
		return '';

	$title0 = !$id ? 'title0' : '';
	$on = $on ? ' on' : '';
	$ms = $block ? 'bottom' : 'right';
	$interval = $block ? $interval : 12;
	$interval = $interval ? ' style="margin-'.$ms.':'.$interval.'px"' : '';
	return
	'<div class="'.$title0.$on.'" val="'.$id.'"'.$interval.'>'.
		$title.
	'</div>';
}
function _count($v=array()) {//поле количество
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '50px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$cls = empty($v['class']) ?    '' : ' '.$v['class'];//дополнительные классы


	$value = _num(@$v['value'], 1);
	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_count disabled php'.$cls.'" id="'.$attr_id.'_count"'.$width.'>'.
		'<input type="text" readonly value="'.$value.'" />'.
		'<div class="but"></div>'.
		'<div class="but but-b"></div>'.
	'</div>';
}
function _hint() {/* все действия через JS */}
function _calendar($v=array()) {//поле Календарь
	$attr_id = empty($v['attr_id']) ? 'calendar'.rand(1, 100000) : $v['attr_id'];
	$time = _num($v['time']); //показывать время

	if(!preg_match(REGEXP_DATE, @$v['value']) || $v['value'] == '0000-00-00' || $v['value'] == '0000-00-00 00:00:00')
		$v['value'] = TODAY.($time ? strftime(' %H:%M:00') : '');


	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v['value'].'" />'.
	'<div class="_calendar disabled" id="'.$attr_id.'_calendar">'.
		'<div class="icon icon-calendar"></div>'.
		'<input type="text" class="cal-inp" readonly value="'.FullData($v['value']).'" />'.

	($time ?
		'<div class="dib ml8">'.
			_count(array(
				'attr_id' => $attr_id.'_hour',
				'value' => substr($v['value'], 11, 2)
			)).
		'</div>'.
		'<div class="dib b ml3 mr3">:</div>'.
			_count(array(
				'attr_id' => $attr_id.'_min',
				'value' => substr($v['value'], 14, 2)
			))
	: '').

	'</div>';
}
function _search($v=array()) {//поле ПОИСК
	$attr_id = empty($v['attr_id']) ? 'search'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$dis = $v['disabled'] ? ' disabled' : '';
	$readonly = $dis ? ' readonly' : '';
	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$v = trim(@$v['v']);

	return
	'<div class="_search'.$dis.'"'.$width.' id="'.$attr_id.'_search">'.
		'<table class="w100p">'.
			'<tr><td class="w15 pl5">'.
					'<div class="icon icon-search curD"></div>'.
				'<td><input type="text" id="'.$attr_id.'"'.$placeholder.$readonly.' value="'.$v.'" />'.
				'<td class="w25 center">'.
					'<div class="icon icon-del pl'._dn($v).'"></div>'.
		'</table>'.
	'</div>';
}
function _dropdown($v=array()) {//выпадающее поле - ссылка
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_dropdown">'.
		'<a class="dd-head clr1">'.$v['placeholder'].'</a>'.
	'</div>';
}
function _yearleaf($v=array()) {//перелистывание годов
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$value = empty($v['value']) ? YEAR_CUR : $v['value'];

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_yearleaf php" id="'.$attr_id.'_yearleaf">'.
		'<table>'.
			'<tr><td class="but">&laquo;'.
				'<td class="ylc"><span>'.$value.'</span>'.
				'<td class="but">&raquo;'.
		'</table>'.
	'</div>';
}

function _button($v=array()) {//кнопка из контакта
	$attr_id = empty($v['attr_id']) ? '' : ' id="'.$v['attr_id'].'"';
	$name = empty($v['name']) ? 'Кнопка' : $v['name'];
	$small = empty($v['small']) ? '' : ' small';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$cls = empty($v['class']) ? '' : ' '.$v['class'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';

	$width = '';
	if(isset($v['width']))
		switch($v['width']) {
			case 0: $width = ' style="width:100%"'; break;
			default: $width = ' style="width:'._num($v['width']).'px"';
		}

	return
	'<button class="vk'.$color.$small.$cls.'"'.$attr_id.$width.$click.$val.'>'.
		$name.
	'</button>';
}


