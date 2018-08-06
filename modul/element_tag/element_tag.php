<?php
/* Элементы-теги */

function _element_tag_script() {//скрипты и стили элементов-тегов
	return
	'<link rel="stylesheet" type="text/css" href="modul/element_tag/element_tag'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/element_tag/element_tag'.MIN.'.js?'.SCRIPT.'"></script>';
}

function _check($v=array()) {//элемент ГАЛОЧКА
	$attr_id = empty($v['attr_id']) ? 'check'.rand(1, 100000) : $v['attr_id'];

	$cls = '_check php ';
	$cls .= empty($v['block']) ?    '' : ' block';       //display:block, иначе inline-block
	$cls .= empty($v['disabled']) ? '' : ' disabled';    //неактивное состояние
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
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;

	//если список пуст и только нулевое значение, отступ снизу не делается
	$int = empty($spisok) ? 0 : $interval;
	$html = _radioUnit(0, $block, $title0, $int, $value == 0);

	if(is_array($spisok) && !empty($spisok)) {
		end($spisok);
		$idEnd = key($spisok);
		foreach($spisok as $id => $title) {
			//отступ снизу после последнего значения не делается
			$int = $idEnd == $id ? 0 : $interval;
			$html .= _radioUnit($id, $block, $title, $int, $value == $id);
		}
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$light.'">'.
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

	$value = _num(@$v['value']);
	return
	'<div class="_count disabled" id="'.$attr_id.'_count"'.$width.'>'.
		'<input type="text" readonly id="'.$attr_id.'" value="'.$value.'" />'.
		'<div class="but"></div>'.
		'<div class="but but-b"></div>'.
	'</div>';
}
function _select($v=array()) {//выпадающее поле
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_select disabled dib" id="'.$attr_id.'_select"'.$width.'">'.
		'<table class="w100p">'.
			'<tr><td><input type="text" class="select-inp w100p"'.$placeholder.' readonly />'.
				'<td class="arrow">'.
		'</table>'.
	'</div>';
}
function _hint() {/* все действия через JS */}
function _tooltip($msg, $left=0, $ugolSide='', $x2=0) {//подсказка на чёрном фоне
	//x2: в две строки
	$x2 = $x2 ? ' x2' : '';
	return
		' _tooltip">'.
		'<div class="ttdiv'.$x2.'"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
			'<div class="ttmsg">'.$msg.'</div>'.
			'<div class="ttug'.($ugolSide ? ' '.$ugolSide : '').'"></div>'.
		'</div>';
}
function _calendar($v=array()) {//поле Календарь
	$attr_id = empty($v['attr_id']) ? 'calendar'.rand(1, 100000) : $v['attr_id'];

	$value = TODAY;
	if(!empty($v['value'])) {
		$ex = explode('-', $v['value']);
		if(count($ex) == 3)
			if(_num($ex[0]) && _num($ex[1]) && _num($ex[2]))
				$value = $v['value'];
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_calendar disabled" id="'.$attr_id.'_calendar">'.
		'<div class="icon icon-calendar"></div>'.
		'<input type="text" class="cal-inp" readonly value="'.FullData($value).'" />'.
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

	$dis = empty($v['disabled']) ? '' : ' disabled';
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
function _menu($unit, $is_edit) {//Меню страниц, dialog_id=3
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if(!$r['access'])
			continue;
		if($unit['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return 'Разделов нет.';

	$razdel = '';
	foreach($menu as $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';
		$page_id = $r['id'];
		if($r['common_id']) {//если страница является ссылкой на другую страницу, при этом она недоступна, поиск первой вложенной доступной
			$page_id = $r['common_id'];
			$p = _page($page_id);
			if(!$p['access']) {
				$page_id = 0;
				foreach(_page('child', $r['id']) as $p)
					if($p['access']) {
						$page_id = $p['id'];
						break;
					}
			}
		}

		if(!$page_id)
			continue;

		$href = $is_edit ? '' : ' href="'.URL.'&p='.$page_id.'"';

		$razdel .=
			'<a class="link'.$sel.'"'.$href.'>'.
				$r['name'].
			'</a>';
	}

	//Внешний вид меню
	$type = array(
		0 => 0,
		10 => 0,//Основной вид - горизонтальное меню
		11 => 1,//С подчёркиванием (гориз.)
		12 => 2,//Синие маленькие кнопки (гориз.)
		13 => 3 //Боковое вертикальное меню
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';
}
function _dropdown($v=array()) {//выпадающее поле - ссылка
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_dropdown">'.
		'<a class="dd-head grey">'.$v['placeholder'].'</a>'.
	'</div>';
}


