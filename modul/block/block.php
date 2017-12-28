<?php
function _blockArr($obj_name, $obj_id, $return='block') {//получение структуры блоков с элементами дл€ конкретной страницы
	/*
		$return:
			block - иерархи€ блоков
			arr - последовательность блоков (дл€ формата JS)
	*/
	$sql = "SELECT
				*,
				'' `elem`
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return array();

	//расстановка элементов в блоки
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	foreach(query_arr($sql) as $r) {
		unset($arr[$r['block_id']]['elem']);
		$r['block'] = $arr[$r['block_id']];
		$arr[$r['block_id']]['elem'] = $r;
	}

	foreach($arr as $id => $r) {
		$arr[$id]['child'] = array();
		$arr[$id]['child_count'] = 0;
	}

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$block = _blockArrChild($child);

	if($return == 'block')
		return $block;

	foreach($arr as $id => $r) {
		if(!$r['parent_id'])
			continue;
		$arr[$r['parent_id']]['child_count'] = count($child[$r['parent_id']]);
	}

	return $arr;
}
function _blockArrChild($child, $parent_id=0) {//расстановка дочерних блоков
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _blockArrChild($child, $id);

	return $send;
}
function _blockObj($name, $i='name') {//доступные варианты объектов дл€ блоков
	$empty = array(
		'page' => '<div class="_empty mar20">Ёта страница пуста€ и ещЄ не была настроена.</div>',
		'spisok' => '<div class="_empty min">Ўаблон пуст.<div class="mt10 pale">Ќачните с настройки блоков.</div></div>',
		'dialog' => '<div class="pad10">'.
						'<div class="_empty min">'.
							'ѕустое содержание диалога.'.
	   (_num(@BLOCK_EDIT) ? '<div class="mt10 pale">Ќачните с управлени€ блоками.</div>' : '').
						'</div>'.
					'</div>'
	);

	if(!isset($empty[$name]))
		return 0;

	//сообщение отсутстви€ блоков
	if($i == 'empty')
		return $empty[$name];

	return $name;
}
function _blockHtml($obj_name, $obj_id, $width=1000, $grid_id=0) {//вывод на экран всей структуры блоков
	if(!$block = _blockArr($obj_name, $obj_id))
		return _blockObj($obj_name, 'empty');

	return _blockLevel($block, $width, $grid_id);
}
function _blockLevel($arr, $WM, $grid_id=0, $hMax=0, $level=1) {//формирование блоков по уровн€м
	if(empty($arr))
		return '';

	//услови€ дл€ настройки блоков конкретного объекта
	if(!defined('BLOCK_EDIT')) {
		$id = key($arr);
		switch($arr[$id]['obj_name']) {
			default:
			case 'page': $v = PAS; break;
			case 'spisok': $v = 0; break;
			case 'dialog': $v = 0; break;
		}
		define('BLOCK_EDIT', $v);
	}


	$MN = 10;//множитель
	$wMax = round($WM / $MN);

	//подстановка нижней линии, если блоки не доход€т до низу
	$yEnd = 0;
	$hSum = 0;

	//составление структуры блоков по строкам
	$block = array();
	foreach($arr as $r) {
		$block[$r['y']][] = $r;
		$yEnd = $r['y'];
	}

	$send = '';
	$BT = BLOCK_EDIT ? ' bor-t-dash' : '';
	$BR = BLOCK_EDIT ? ' bor-r-dash' : '';
	$BB = BLOCK_EDIT ? ' bor-b-dash' : '';
	$br1px = BLOCK_EDIT ? 1 : 0;


	foreach($block as $y => $str) {
		$widthMax = $WM;
		$r = $str[0];

		$bt = $y ? $BT : '';

		$hSum += $r['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? $BB : '';

		$send .=
			'<table class="bl-tab" style="height:'.$r['height'].'px">'.
				'<tr>';
		//пустота в начале
		if($r['x']) {
			$width = $r['x'] * $MN - $br1px;
			$send .= '<td class="'.$BR.$bt.$bb.'" style="width:'.$width.'px">';
			$widthMax -= $width;
		}

		foreach($str as $n => $r) {
			$next = @$str[$n + 1];

			if($r['width'] > $widthMax)
				$r['width'] = $widthMax;

			$xEnd = !($wMax - $r['x'] - $r['w']);

			$cls = array('bl-td');
			$cls[] = BLOCK_EDIT ? 'prel' : '';
			$cls[] = $r['bg'];
			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == $grid_id ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + (BLOCK_EDIT ? 0 : $bor[1]);
			$width = $r['width'] - $br1px - $borPx;

			$send .= '<td id="bl_'.$r['id'].'"'.
						' class="'.$cls.'"'.
						' style="'._blockStyle($r, $width).'"'.
				 (BLOCK_EDIT ? ' val="'.$r['id'].'"' : '').
					 '>'.
							_blockSetka($r, $level, $r['obj_name'], $grid_id).
							_blockChildHtml($r, $level + 1, $width, $grid_id).
	    					_elemDiv($r['elem']).
					'';

			$widthMax -= $r['width'];

			//пустота в середине
			if($next)
				if($next['x'] > $r['x'] + $r['w']) {
					$w = $next['x'] - $r['x'] - $r['w'];
					$width = $w * $MN - $br1px;
					$send .= '<td class="'.$BR.$bt.$bb.'" style="width:'.$width.'px">';
					$widthMax -= $width;
				}

			//пустота в конце
			if(!$next && $widthMax)
				$send .= '<td class="'.$bt.$bb.'" style="width:'.$widthMax.'px">';
		}
		$send .= '</table>';
	}

	return $send;
}
function _blockLevelChange($obj_name, $obj_id, $width=1000) {//кнопки дл€ изменени€ уровн€ редактировани€ блоков
	$max = 1;
	$send = '';

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	if($arr = query_arr($sql)) {
		foreach($arr as $r) {
			if(!$parent_id = $r['parent_id'])
				continue;

			$level = 2;

			while($parent_id)
				if($parent_id = $arr[$parent_id]['parent_id'])
					$level++;

			if($max < $level)
				$max = $level;
		}

		//обновление текущего уровн€ настройки блоков, если у предыдущего объекта было больше уровней
		$selected = _blockLevelDefine($obj_name);
		if($selected > $max) {
			_blockLevelDefine($obj_name, 1);
			$selected = 1;
		}

		for($n = 1; $n <= $max; $n++) {
			$sel = $selected == $n ? 'orange' : 'cancel';
			$send .= '<button class="block-level-change vk small ml5 '.$sel.'">'.$n.'</button>';
		}
	}

	return
	'<div id="block-level-'.$obj_name.'" class="dib">'.
		'<button class="vk small grey block-grid-on" val="'.$obj_name.':'.$obj_id.':'.$width.'">”правление блоками</button>'.
		$send.
	'</div>';
}
function _blockLevelDefine($obj_name, $v = 0) {//уровень редактируемых блоков
	$key = 'block_level_'.$obj_name;
	if($v) {
		$_COOKIE[$key] = $v;
		setcookie($key, $v, time() + 2592000, '/');
		return $v;
	}
	return empty($_COOKIE[$key]) ? 1 : _num($_COOKIE[$key]);
}
function _blockSetka($r, $level, $obj_name, $grid_id) {//отображение сетки дл€ настраиваемого блока
	if(!BLOCK_EDIT)
		return '';
	if($r['id'] == $grid_id)
		return '';

	$bld = _blockLevelDefine($obj_name);

	if($bld != $level)
		return '';

	$bld += $obj_name == 'page' ? 0 : 2;

	return '<div class="block-unit level'.$bld.' '.($grid_id ? ' grid' : '').'" val="'.$r['id'].'"></div>';
}
function _blockStyle($r, $width) {//стили css дл€ блока
	$send = array();

	//границы
	$bor = explode(' ', $r['bor']);
	foreach($bor as $i => $b) {
		if(!$b)
			continue;
		switch($i) {
			case 0: $send[] = 'border-top:#DEE3EF solid 1px'; break;
			case 1: $send[] = 'border-right:#DEE3EF solid 1px'; break;
			case 2: $send[] = 'border-bottom:#DEE3EF solid 1px'; break;
			case 3: $send[] = 'border-left:#DEE3EF solid 1px'; break;
		}
	}

	$send[] = 'width:'.$width.'px';

	return implode(';', $send);
}
function _blockJS($obj_name, $obj_id) {//массив настроек блоков в формате JS
	if(!$arr = _blockArr($obj_name, $obj_id, 'arr'))
		return '';

	$send = array();
	foreach($arr as $id => $r) {
		$v = array();
		$v[] = 'id:'.$id;
		$v[] = 'pos:"'.$r['pos'].'"';
		$v[] = 'bg:"'.$r['bg'].'"';
		$v[] = 'bor:"'.$r['bor'].'"';
		$v[] = 'obj_name:"'.$r['obj_name'].'"';
		$v[] = 'obj_id:'.$r['obj_id'];
		$v[] = 'child:'.$r['child_count'];

		if($el = $r['elem']) {
			if(!$size = _num($el['size']))
				$size = 13;
			$v[] = 'elem_id:'._num($el['id']);
			$v[] = 'dialog_id:'._num($el['dialog_id']);
			$v[] = 'fontAllow:'._elemFontAllow($el['dialog_id']);
			$v[] = 'color:"'.$el['color'].'"';
			$v[] = 'font:"'.$el['font'].'"';
			$v[] = 'size:'.$size;
			$v[] = 'mar:"'.$el['mar'].'"';

			$v[] = 'num_1:'._num($el['num_1'], true);
			$v[] = 'num_2:'._num($el['num_2']);
			$v[] = 'num_7:'._num($el['num_7']);
			$v[] = 'txt_2:"'._br($el['txt_2']).'"';
		}

		$send[] = $id.':{'.implode(',', $v).'}';
	}
	return implode(',', $send);
}
function _blockJsArr($obj_name, $obj_id) {//массив настроек блоков в формате дл€ отправки через JSON дл€ BLOCK_ARR
	if(!$arr = _blockArr($obj_name, $obj_id, 'arr'))
		return array();

	$send = array();
	foreach($arr as $id => $r) {
		$v = array(
			'id' => _num($id),
			'pos' => $r['pos'],
			'bg' => $r['bg'],
			'bor' => $r['bor'],
			'obj_name' => $r['obj_name'],
			'obj_id' => _num($r['obj_id']),
			'child' => _num($r['child_count'])
		);

		if($el = $r['elem']) {
			if(!$size = _num($el['size']))
				$size = 13;
			$v['elem_id'] = _num($el['id']);
			$v['dialog_id'] = _num($el['dialog_id']);
			$v['fontAllow'] = _elemFontAllow($el['dialog_id']);
			$v['color'] = $el['color'];
			$v['font'] = $el['font'];
			$v['size'] = $size;
			$v['mar'] = $el['mar'];

			$v['num_1'] = _num($el['num_1'], true);
			$v['num_2'] = _num($el['num_2']);
			$v['num_7'] = _num($el['num_7']);
			$v['txt_2'] = utf8(_br($el['txt_2']));
		}

		$send[_num($id)] = $v;
	}
	return $send;
}
function _blockChildHtml($block, $level, $width, $grid_id) {//деление блока на части
	if($block['id'] != $grid_id)
		return _blockLevel($block['child'], $width, $grid_id, $block['h'], $level);

	return _blockGrid($block['child']);
}
function _blockGrid($arr) {//режим делени€ на подблоки
	$spisok = '';
	foreach($arr as $r) {
		$spisok .=
		    '<div id="pb_'.$r['id'].'" class="grid-item" data-gs-x="'.$r['x'].'" data-gs-y="'.$r['y'].'" data-gs-width="'.$r['w'].'" data-gs-height="'.$r['h'].'">'.
				'<div class="grid-content"></div>'.
				'<div class="grid-del">x</div>'.
		    '</div>';
	}

	return
		'<div id="grid-stack" class="prel">'.$spisok.'</div>'.
		'<div id="grid-add" class="pad5 bg-gr2 bor-e8 fs14 center color-555 curP over5 mt1">ƒобавить блок</div> '.
		'<div class="pad5 center">'.
			'<button class="vk small orange" id="grid-save">—охранить</button>'.
			'<button class="vk small cancel ml5" id="grid-cancel">ќтмена</button>'.
		'</div>';
}
