<?php
function _blockChildClear($arr) {//изъятие дочерних блоков, если отсутствует родитель
	$idsForDel = array();
	foreach($arr as $id => $r) {
		if(!$parent_id = $r['parent_id'])
			continue;
		$ids = array();
		$ids[$id] = $id;
		$DEL_FLAG = true;
		while(true) {
			if(!$p = @$arr[$parent_id])
				break;
			$ids[$p['id']] = $p['id'];
			if(!$parent_id = $p['parent_id']) {
				$DEL_FLAG = false;
				break;
			}
		}
		if($DEL_FLAG)
			$idsForDel += $ids;
	}
	foreach($idsForDel as $id)
		unset($arr[$id]);

	return $arr;
}
function _blockArrChild($child, $parent_id=0) {//расстановка дочерних блоков
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _blockArrChild($child, $id);

	return $send;
}
function _blockObj($name, $i='name') {//доступные варианты объектов для блоков
	$empty = array(
		'page' => '<div class="_empty mar20">Эта страница пустая и ещё не была настроена.</div>',

		'spisok' =>
			'<div class="bg-ffe pad10">'.
				'<div class="_empty min">'.
					'Шаблон пуст.'.
					'<div class="mt10 pale">Начните с настройки блоков.</div>'.
				'</div>'.
			'</div>',

		'dialog' => '<div class="pad10">'.
						'<div class="_empty min">'.
							'Пустое содержание диалога.'.
	   (_num(@BLOCK_EDIT) ? '<div class="mt10 pale">Начните с управления блоками.</div>' : '').
						'</div>'.
					'</div>'
	);

	if(!isset($empty[$name]))
		return 0;

	//сообщение отсутствия блоков
	if($i == 'empty')
		return $empty[$name];

	return $name;
}
function _blockHtml($obj_name, $obj_id, $width=1000, $grid_id=0, $unit=array()) {//вывод на экран всей структуры блоков
	if(!$blk = _block($obj_name, $obj_id, 'block_arr'))
		return _blockObj($obj_name, 'empty');
	if(!is_array($unit))
		return $unit;

	$elm = _block($obj_name, $obj_id, 'elem_arr');

	//расстановка элементов в блоки
	foreach($blk as $id => $r) {
		$arr[$id]['child'] = array();
		$blk[$id]['elem'] = array();
		if($r['elem_id']) {
			$el = $elm[$r['elem_id']];
			$el['block'] = $blk[$id];//а также предварительно прикрепление данных блока к элементу
			$blk[$id]['elem'] = $el;
		}
	}

	$child = array();
	foreach($blk as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$block = _blockArrChild($child);

	return _blockLevel($block, $width, $grid_id, 0,1, $unit);
}
function _blockLevel($arr, $WM, $grid_id=0, $hMax=0, $level=1, $unit=array()) {//формирование блоков по уровням
	if(empty($arr))
		return '';

	//условие изменения ширины элемента
	if(!defined('ELEM_WIDTH_CHANGE'))
		define('ELEM_WIDTH_CHANGE', 0);

	//условия для настройки блоков конкретного объекта
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

	//подстановка нижней линии, если блоки не доходят до низу
	$yEnd = 0;
	$hSum = 0;

	//составление структуры блоков по строкам
	$block = array();
	foreach($arr as $r) {
		if(!BLOCK_EDIT && empty($unit['choose']) && $r['elem_id'] && $r['elem']['hidden'])
			continue;
		$block[$r['y']][] = $r;
		$yEnd = $r['y'];
	}

	if(empty($block))
		return '';

	$send = '';
	$BT = BLOCK_EDIT ? ' bor-t-dash' : '';
	$BR = BLOCK_EDIT ? ' bor-r-dash' : '';
	$BB = BLOCK_EDIT ? ' bor-b-dash' : '';
	$br1px = BLOCK_EDIT ? 1 : 0;//показ красной разделительной линии справа

	foreach($block as $y => $str) {
		$widthMax = $WM;
		$r = $str[0];

		$bt = $y ? $BT : '';

		$hSum += $r['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? $BB : '';

		$send .=
			'<div class="bl-div">'.
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
			//$cls[] = BLOCK_EDIT ? 'prel' : '';
			$cls[] = 'prel';
			$cls[] = $r['bg'];
			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == $grid_id ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls[] = $r['link'] ? 'curP block-link pg-'.$r['link'] : '';
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + (BLOCK_EDIT ? 0 : $bor[1]);
			$width = $r['width'] - ($xEnd ? 0 : $br1px) - $borPx;

			//если блок списка шаблона, attr_id не ставится
			$attr_id = !BLOCK_EDIT && $r['obj_name'] == 'spisok' ? '' : ' id="bl_'.$r['id'].'"';

			$send .= '<td'.$attr_id.
						' class="'.$cls.'"'.
						' style="'._blockStyle($r, $width, $unit).'"'.
		  (BLOCK_EDIT ? ' val="'.$r['id'].'"' : '').
					 '>'.
							_blockSetka($r, $level, $grid_id, $unit).
							_blockChoose($r, $level, $unit).
							_blockElemChoose($r, $unit).
							_blockChildHtml($r, $level + 1, $width, $grid_id, $unit).
	    					_elemDiv($r['elem'], $unit).
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
		$send .= '</table>'.
				 '</div>';
	}

	return $send;
}
function _blockLevelChange($obj_name, $obj_id, $width=1000) {//кнопки для изменения уровня редактирования блоков
	$max = 1;
	$html = '';

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	if($arr = query_arr($sql)) {
		//определение количества уровней блоков
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

		//обновление текущего уровня настройки блоков, если у предыдущего объекта было больше уровней
		$selected = _blockLevelDefine($obj_name);
		if($selected > $max) {
			_blockLevelDefine($obj_name, 1);
			$selected = 1;
		}

		for($n = 1; $n <= $max; $n++) {
			$sel = $selected == $n ? 'orange' : 'cancel';
			$html .= '<button class="block-level-change vk small ml5 '.$sel.'">'.$n.'</button>';
		}

		//опделеление, есть ли элементы, у которых можно изменять ширину, чтобы выводить кнопку настройки
		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id` IN ("._idsGet($arr).")";
		foreach(query_arr($sql) as $r)
			if(_dialogParam($r['dialog_id'], 'element_width')) {
				$html .= '<button class="vk small grey ml30 elem-width-change">Настройка ширины элементов</button>';
				break;
			}
	}

	return
	'<div id="block-level-'.$obj_name.'" val="'.$obj_name.':'.$obj_id.':'.$width.'">'.
		'<button class="vk small grey block-grid-on">Управление блоками</button>'.
		$html.
		'<div class="dn fr">'.
			'<button class="vk small green mr5 block-choose-submit">Блоки выбраны</button>'.
			'<button class="vk small cancel block-choose-cancel">Вернуться к диалогу</button>'.
		'</div>'.
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
function _blockSetka($r, $level, $grid_id, $unit) {//отображение сетки для настраиваемого блока
	if(!BLOCK_EDIT)
		return '';
	//включенное изменение ширины элемента отключает настройку блоков
	if(ELEM_WIDTH_CHANGE)
		return '';
	if(!empty($unit['choose']))
		return '';
	if($r['id'] == $grid_id)
		return '';

	$bld = _blockLevelDefine($r['obj_name']);

	if($bld != $level)
		return '';

	$bld += $r['obj_name'] == 'page' ? 0 : 2;

	return '<div class="block-unit level'.$bld.' '.($grid_id ? ' grid' : '').'" val="'.$r['id'].'"></div>';
}
function _blockChoose($r, $level, $unit) {//подсветка блоков для выбора (к функциям)
	if(empty($unit['choose']))
		return '';
//	if($r['parent_id'])//выбирать можно только корневые блоки
//		return '';
	if($level != $_COOKIE['block_level_page'])//выбирать можно только блоки уровня элемента
		return '';
	if(!$ca = $unit['choose_access'])
		return '';
	if(!@$ca['block'])
		return '';

	//отметка выбранных полей
	$block_id = $r['id'];
	$sel = isset($unit['choose_sel'][$block_id]) ? ' sel' : '';
	$deny = isset($unit['choose_deny'][$block_id]) ? ' deny' : '';

	return '<div class="choose block-choose'.$sel.$deny.'" val="'.$block_id.'"></div>';
}
function _blockElemChoose($r, $unit) {//подсветка элементов для вставки в шаблон
	//условие выбора
	if(empty($unit['choose']))
		return '';
	if(empty($r['elem']))//блок не подсвечивается, если в нём нет элемента
		return '';
//	if($r['obj_name'] != 'dialog')//выбор элементов можно производить только у диалогов (пока)
//		return '';

	$dialog_id = $r['elem']['dialog_id'];

	//подсветка полей, которые разрешено выбирать
	if(!$ca = $unit['choose_access'])
		return '';

	if(@$ca['block'])
		return '';

	if(!@$ca['all'] && !isset($ca[$dialog_id]))
		return '';

	//отметка выбранных полей
	$elem_id = $r['elem']['id'];
	$sel = isset($unit['choose_sel'][$elem_id]) ? ' sel' : '';

	return '<div class="choose block-elem-choose'.$sel.'" val="'.$elem_id.'"></div>';
}
function _blockStyle($r, $width, $unit) {//стили css для блока
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

	$send[] = ($r['width_auto'] ? 'min-' : '').'width:'.$width.'px';

	//цвет фона из единицы списка
	if($r['bg'] == 'bg70')
		if(!empty($r['bg_col'])) {
			$col = $r['bg_col'];
			if(!empty($r['bg_connect']))
				$bg = @$unit[$r['bg_connect']][$col];
			else
				$bg = @$unit[$col];
			if($bg)
				$send[] = 'background-color:'.$bg;
		}

	return implode(';', $send);
}
function _blockChildHtml($block, $level, $width, $grid_id, $unit) {//деление блока на части
	if($block['id'] != $grid_id)
		return _blockLevel($block['child'], $width, $grid_id, $block['h'], $level, $unit);

	return _blockGrid($block['child']);
}
function _blockGrid($arr) {//режим деления на подблоки
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
		'<div id="grid-add" class="pad5 bg-gr2 bor-e8 fs14 center color-555 curP over5 mt1">Добавить блок</div> '.
		'<div class="pad5 center">'.
			'<button class="vk small orange" id="grid-save">Сохранить</button>'.
			'<button class="vk small cancel ml5" id="grid-cancel">Отмена</button>'.
		'</div>';
}

function _blockCache($obj_name, $obj_id) {
	$cacheKey = $obj_name.'_'.$obj_id;
	if($send = _cache('', $cacheKey))
		return $send;

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id."
			  AND `sa` IN (0,".SA.")
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return _cache(array(
			'block' => array(),
			'elem' => array()
		), $cacheKey);

	if(!$arr = _blockChildClear($arr))
		return _cache(array(
			'block' => array(),
			'elem' => array()
		), $cacheKey);

	//Отображение варианта цвета для динамической окраски блоков
	//Будет открываться диалог, который вносит данные списка, чтобы указать, откуда брать цвет для окраски
	//Иконка показывается, если:
	//      1. spisok-блоки. id диалога, который вносит значения списка
	//      2. dialog-блоки. id этого диалога
	$bg70 = 0;
	if($obj_name == 'spisok')
		if($bl = _blockQuery($obj_id))
			if($el = $bl['elem'])
				if($el['dialog_id'] == 14 || $el['dialog_id'] == 59)
					if($dlg_id = _num($el['num_1']))
						$bg70 = $dlg_id;
	if($obj_name == 'dialog')
		$bg70 = $obj_id;


	$block = array();
	$blockYStr = array();//выстраивание id блоков по порядку, чтобы потом по этому порядку выстроить элементы
	foreach($arr as $bl) {
		$id = _num($bl['id']);
		unset($bl['user_id_add']);
		unset($bl['dtime_add']);
		foreach($bl as $key => $v)
			if(preg_match(REGEXP_NUMERIC, $v))
				$bl[$key] = _num($v);
		$bl['elem_id'] = 0;
		$bl['attr_bl'] = '#bl_'.$id;
		$bl['bg70'] = $bg70;
		$block[$id] = $bl;
		$blockYStr[$bl['parent_id']][$bl['y']][] = $id;
	}

	//получение элементов
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($block).")";
	$elemArr = query_arr($sql);

	//наличие функций в элементах
	$sql = "SELECT `block_id`,1
			FROM `_element_func`
			WHERE `block_id` IN("._idsGet($elemArr, 'block_id').")
			GROUP BY `block_id`";
	$isFunc = query_ass($sql);

	//данные настроек из диалога
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `id` IN ("._idsGet($elemArr, 'dialog_id').")";
	$dialog = query_arr($sql);

	$elemPrev = array();//предварительная сборка элементов (без порядка по блокам)
	foreach($elemArr as $el) {
		$elem_id = _num($el['id']);
		$dlg = $dialog[$el['dialog_id']];
		$block[$el['block_id']]['elem_id'] = $elem_id;
		unset($el['sort']);
		unset($el['user_id_add']);
		unset($el['dtime_add']);
		unset($el['page_id']);

		if(!$el['hint_on']) {
			unset($el['hint_msg']);
			unset($el['hint_side']);
			unset($el['hint_obj_pos_h']);
			unset($el['hint_obj_pos_v']);
			unset($el['hint_delay_show']);
			unset($el['hint_delay_hide']);
		}

		foreach($el as $key => $v)
			if(preg_match(REGEXP_INTEGER, $v))
				$el[$key] = _num($v, 1);

		$el['attr_el'] = '#el_'.$elem_id;
		$el['attr_cmp'] = '#cmp_'.$elem_id;
		$el['size'] = $el['size'] ? _num($el['size']) : 13;
		$el['is_func'] = _num(@$isFunc[$el['block_id']]);
		$el['style_access'] = _num($dlg['element_style_access']);
		$el['url_access'] = _num($dlg['element_url_access']);
		$el['hint_access'] = _num($dlg['element_hint_access']);
		$el['dialog_func'] = _num($dlg['element_dialog_func']);
		$el['afics'] = $dlg['element_afics'];
		$el['hidden'] = _num($dlg['element_hidden']);
		$el['title'] = _elemTitle($el['id']);

		if($el['width_min'] = _num($dlg['element_width_min'])) {
			//определение максимальной ширины, на которую может растягиваться элемент
			$ex = explode(' ', $el['mar']);
			$width_max = $block[$el['block_id']]['width'] - $ex[1] - $ex[3];
			$el['width_max'] = floor($width_max / 10) * 10;
		}

		$el['func'] = array();
		$el['vvv'] = array();//значения для некоторых компонентов

		switch($el['dialog_id']) {
			//произвольные значения
			case 17://select - произвольные значения
				$el['vvv'] = _elemValue($elem_id);
				break;
			//Меню переключения блоков - список пунктов
			case 57:
				$sql = "SELECT *
						FROM `_element`
						WHERE `block_id`=-".$elem_id."
						ORDER BY `sort`";
				if(!$arr = query_arr($sql))
					break;

				$spisok = array();
				foreach($arr as $id => $r)
					$spisok[] = array(
						'id' => _num($id),
						'title' => $r['txt_1'],
						'blk' => $r['txt_2']
					);

				$el['vvv'] = $spisok;
				break;
		}

		$elemPrev[$elem_id] = $el;
	}

	//выстраивание элементов по порядку
	$block_ids = array();
	foreach($blockYStr as $parent)
		foreach($parent as $y)
			foreach($y as $block_id)
				$block_ids[] = $block_id;
	$elem = array();
	foreach($block_ids as $block_id) {
		if(!$elem_id = $block[$block_id]['elem_id'])
			continue;
		$elem[$elem_id] = $elemPrev[$elem_id];
	}

	$sql = "SELECT *
			FROM `_element_func`
			WHERE `block_id` IN ("._idsGet($block).")
			ORDER BY `sort`";
	foreach(query_arr($sql) as $r) {
		$elem_id = $block[$r['block_id']]['elem_id'];
		$elem[$elem_id]['func'][] = array(
			'dialog_id' => _num($r['dialog_id']),
			'action_id' => _num($r['action_id']),
			'cond_id' => _num($r['cond_id']),
			'action_reverse' => _num($r['action_reverse']),
			'value_specific' => _num($r['value_specific']),
			'effect_id' => _num($r['effect_id']),
			'target' => _idsAss($r['target'])
		);
	}

	return _cache(array(
		'block' => $block,
		'elem' => $elem
	), $cacheKey);
}
function _block($obj_name, $obj_id, $i='all') {
	$mass = _blockCache($obj_name, $obj_id);

	$BLK = $mass['block'];
	$ELM = $mass['elem'];

	if($i == 'block_js') {//массив блоков в формате JS
		if(empty($BLK))
			return '{}';
		$send = array();
		foreach($BLK as $id => $bl) {
			$u = array();
			foreach($bl as $k => $v) {
				if($k == 'xx_ids')
					continue;
				if(!preg_match(REGEXP_NUMERIC, $v))
					$v = '"'.addslashes(_br($v)).'"';
				$u[] = $k.':'.$v;
			}

			$send[] = $id.':{'.implode(',', $u).'}';
		}
		return '{'.implode(',', $send).'}';
	}

	if($i == 'elem_js') {//массив элементов в формате JS
		if(empty($ELM))
			return '{}';
		return _json($ELM);
/*
		$send = array();
		foreach($ELM as $id => $bl) {
			$u = array();
			foreach($bl as $k => $v) {
				if($k == 'focus' && !$v)
					continue;
				if($k == 'func') {
					$u[] = $k.':'._json($v);
					continue;
				}
				if(is_array($v)) {
					if(empty($v))
						continue;
					$u[] = $k.':'._json($v);
					continue;
				}
				if(!preg_match(REGEXP_NUMERIC, $v))
					$v = '"'.addslashes(_br($v)).'"';
				$u[] = $k.':'.$v;
			}

			$send[] = $id.':{'.implode(',', $u).'}';
		}
		return '{'.implode(',', $send).'}';
*/
	}

	if($i == 'block_arr') {
		//если присутствует элемент-цвет фона, получение колонок для цвета, если потребуется окраска блока
		foreach($BLK as $id => $bl) {
			$BLK[$id]['xx_ids'] = _idsAss($bl['xx_ids']);
			$BLK[$id]['bg_col'] = '';    //имя колонки, по которой будет выбираться цвет
			$BLK[$id]['bg_connect'] = '';//имя колонки, если это подключаемый список
			if($bl['bg'] == 'bg70')
				if($ids = _ids($bl['bg_ids'], 1))
					foreach($ids as $elem_id)
						if($el = _elemQuery($elem_id))
							switch($el['dialog_id']) {
								case 29:
								case 59:
									$BLK[$id]['bg_connect'] = $el['col'];
									break;
								case 70:
									$BLK[$id]['bg_col'] = $el['col'];
									break;
							}
		}
		return $BLK;
	}

	if($i == 'elem_arr')
		return $ELM;

	if($i == 'elem_utf8') {
		foreach($ELM as $id => $el)
			foreach($el as $k => $v)
				if(!is_array($v))
					if(!preg_match(REGEXP_NUMERIC, $v))
						$ELM[$id][$k] = utf8($v);
		return $ELM;
	}

	return $mass;
}


