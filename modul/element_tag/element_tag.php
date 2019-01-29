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
	$width = _elemStyleWidth($v);

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
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$light.'"'.$width.'>'.
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

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.@$v['value'].'" />'.
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
function _menu($el, $is_edit) {//Меню страниц [3]
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if(!_pageAccess($id))
			continue;
		//раздел
		if($el['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return 'Разделов нет.';

	$menu = _spisokImage($menu);
	$menu = _menuCount($menu);

	$razdel = '';
	foreach($menu as $page_id => $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';

		//фактическая страница, на которую будет переход
		$pid = $page_id;

		//если страница является ссылкой на другую страницу, при этом она недоступна, поиск первой вложенной доступной
		if($r['common_id'])
			foreach(_page('child', $r['id']) as $p) {
				if(_pageAccess($p['id'])) {
					$pid = $p['id'];
					break;
				}
				if($r['common_id'] == $p['id'])
					continue;
			}

		$href = $is_edit ? '' : ' href="'.URL.'&p='.$pid.'"';
		$curd = _dn(!$is_edit, 'curD');

		if($el['num_2'] == 5)
			$r['name'] = _imageHtml($r['image_ids'], 24, 24, false, false);

		$razdel .= '<a class="link'.$sel.$curd.'"'.$href.'>'.$r['name'].'</a>';
	}

	return '<div class="_menu'.$el['num_2'].'">'.$razdel.'</div>';
}
function _menuCount($menu) {//получение элемента-циферки, размещённого на выводимых страницах
	$sql = "SELECT
				`el`.`id`,
				`el`.`num_1`,
				`el`.`txt_1`,
				`bl`.`obj_id` `page_id`
			FROM `_element` `el`,
				 `_block` `bl`
			WHERE `el`.`block_id`=`bl`.`id`
			  AND `el`.`app_id`=".APP_ID."
			  AND `dialog_id`=87
			  AND `bl`.`obj_name`='page'";
	if(!$arr = query_arr($sql))
		return $menu;

	foreach($arr as $r) {
		$page = _page($r['page_id']);

		//страница, к которой будет добавлена циферка
		$pid = 0;
		if(isset($menu[$r['page_id']]))
			$pid = $r['page_id'];
		if($page['parent_id'] && isset($menu[$page['parent_id']]))
			$pid = $page['parent_id'];
		if(!$pid)
			continue;

		$DLG = _dialogQuery($r['num_1']);
		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG).
					_40cond(array(), $r['txt_1']);
		if(!$count = query_value($sql))
			continue;

		$menu[$pid]['name'] .= '<b class="ml5">+'.$count.'</b>';
	}

	return $menu;
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

function _iconEdit($v=array()) {//иконка редактирования записи в таблице
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	$v = array(
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : 'Изменить',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$click.$val.' class="icon icon-edit'.$cls._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDel($v=array()) {//иконка удаления записи в таблице
	if(!empty($v['nodel']))
		return '';

	//если указывается дата внесения записи и она не является сегодняшним днём, то удаление невозможно
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$red = empty($v['red']) ? '' : '-red';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	return '<div'.$click.$val.' class="icon icon-del'.$red.$cls._tooltip('Удалить', -42, 'r').'</div>';
}

