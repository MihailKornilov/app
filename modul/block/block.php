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
function _blockName($name, $i='name') {//доступные варианты объектов для блоков
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
function _blockHtml($obj_name, $obj_id, $width=1000, $grid_id=0, $unit=array()) {//вывод структуры блоков для конкретного объекта
	if(!$block = _BE('block_obj', $obj_name, $obj_id))
		return _blockName($obj_name, 'empty');
	if(!is_array($unit))
		return $unit;

	return _blockLevel($block, $width, $grid_id, 0,1, $unit);
}
function _blockLevel($arr, $WM, $grid_id=0, $hMax=0, $level=1, $unit=array()) {//формирование блоков по уровням
	/*
		$arr:       список блоков
		$WM:        width max, максимальная ширина группы блоков
		$grid_id:   ID блока, который делится на части в конкретный момент
		$hMax:      максимальная высота блока (для отображения нижней разделительной полосы при редактировании)
		$level:     уровень блоков
		$unit:      данные единицы списка.
					А также дополнительные настройки:
						elem_width_change: изменение ширины элементов
						elem_choose: выбор элемента
	*/
	if(empty($arr))
		return '';

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
	$hSum = 0;

	//составление структуры блоков по строкам
	$block = array();
	foreach($arr as $r) {
		if(!BLOCK_EDIT && empty($unit['v_choose']) && empty($unit['choose']) && $r['elem_id'] && $r['elem']['hidden'])
			continue;
		$block[$r['y']][$r['x']] = $r;
	}

	if(empty($block))
		return '';

	ksort($block);
	end($block);
	$yEnd = key($block);

	$send = '';
	$BT = BLOCK_EDIT ? ' bor-t-dash' : '';
	$BR = BLOCK_EDIT ? ' bor-r-dash' : '';
	$BB = BLOCK_EDIT ? ' bor-b-dash' : '';
	$br1px = BLOCK_EDIT ? 1 : 0;//показ красной разделительной линии справа

	foreach($block as $y => $str) {
		$widthMax = $WM;

		ksort($str);//выстраивание блоков по X

		$xStr = array();
		foreach($str as $r)
			$xStr[] = $r;

		$r = $xStr[0];

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

		foreach($xStr as $n => $r) {
			$next = @$xStr[$n + 1];

			if($r['width'] > $widthMax)
				$r['width'] = $widthMax;

			$xEnd = !($wMax - $r['x'] - $r['w']);

			$cls = array('bl-td');
			$cls[] = 'prel';
			$cls[] = $r['bg'];
			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == $grid_id ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls[] = $r['click_action'] == 2081 && $r['click_page']   ? 'curP block-click-page pg-'.$r['click_page'] : '';
			$cls[] = $r['click_action'] == 2082 && $r['click_dialog'] ? 'curP dialog-open' : '';
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
		  (!BLOCK_EDIT && $r['click_action'] == 2082 && $r['click_dialog'] ?
			            ' val="dialog_id:'.$r['click_dialog'].',unit_id:'.$unit['id'].'"'
		  : '').
					 '>'.
							_blockSetka($r, $level, $grid_id, $unit).
							_blockChoose($r, $level, $unit).
							_block_v_choose($r, $unit).
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
	//выход, если включено изменение ширины элемента
	if(!empty($unit['elem_width_change']))
		return '';
	//выход, если выбор элемента
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
	if($level != @$_COOKIE['block_level_'.$r['obj_name']])//выбирать можно только блоки установленного уровня (на уровне, котором расположен элемент)
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
function _blockElemChoose_old($r, $unit) {//подсветка для выбора элементов
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
function _block_v_choose($r, $unit) {//подсветка элементов для выбора значения
	//(не)разрешён выбор значения
	if(empty($unit['v_choose']))
		return '';

	//блок не подсвечивается, если в нём нет элемента
	if(empty($r['elem']))
		return '';

	//отметка выбранных полей
	$elem_id = $r['elem']['id'];
	$sel = $unit['v_id_sel'] == $elem_id ? ' sel' : '';

	return '<div class="v-choose'.$sel.'" val="'.$elem_id.'"></div>';
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


function _elemDiv($el, $unit=array()) {//формирование div элемента
	if(!$el)
		return '';

	$txt = _elemUnit($el, $unit);

	//если элемент списка шаблона, attr_id не ставится
	$attr_id = !BLOCK_EDIT && $el['block']['obj_name'] == 'spisok' ? '' : ' id="el_'.$el['id'].'"';

	$cls = array();
	$cls[] = _elemFormatColor($txt, $el, $el['color']);
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormat($txt, $el);

	return '<div'.$attr_id.$cls._elemStyle($el, $unit).'>'.$txt.'</div>';
}
function _elemFormat($txt, $el) {//дополнительное форматирование для чисел
	if($el['format_hide'] && empty($txt))
		return '';
	if(!preg_match(REGEXP_CENA_MINUS, $txt))
		return $txt;
	if($el['format_hide'] && !_cena($txt, 1))
		return '';

	if($el['format_space'])
		$txt = _sumSpace($txt, $el['format_fract_0_show'], $el['format_fract_char']);
	else {
		if(!$el['format_fract_0_show'])
			$txt = round($txt, 2);
		$txt = str_replace('.', $el['format_fract_char'], $txt);
	}

	return $txt;
}
function _elemFormatColor($txt, $el, $color) {//подмена цвета при дополнительном форматировании для чисел
	if(!preg_match(REGEXP_CENA_MINUS, $txt))
		return $color;

	switch($el['format_color_cond']) {
		case 1457:
			if($txt == 0)
				return $el['format_color'];
			break;
		case 1458:
			if($txt < 0)
				return $el['format_color'];
			break;
		case 1459:
			if($txt > 0)
				return $el['format_color'];
			break;
	}

	return $color;
}
function _elemStyle($el, $unit) {//стили css для элемента
	$send = array();

	//отступы
	$ex = explode(' ', $el['mar']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'margin:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	//когда включена настройка ширины элементов,
	//те элементы, которые могут настраиваться, остаются, остальные скрываются
	if(!empty($unit['elem_width_change']) && !_dialogParam($el['dialog_id'], 'element_width'))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _elemUnit($el, $unit=array()) {//формирование элемента страницы
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//значение из списка
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']] : '';
	$is_edit = @BLOCK_EDIT || !empty($unit['elem_width_change']) || !empty($unit['choose']);
	$attr_id = 'cmp_'.$el['id'].($is_edit ? '_edit' : '');
	$disabled = $is_edit ? ' disabled' : '';

	switch($el['width']) {
		case 0: $width = ' style="width:100%"'; break;
//		case -1: $width = ' style="width:100%"'; break;
		default: $width = ' style="width:'.$el['width'].'px"';
	}

	switch($el['dialog_id']) {
		//---=== КОМПОНЕНТЫ ДЛЯ ВНЕСЕНИЯ ДАННЫХ ===--- (используется $unit)
		//галочка
		case 1:
			/*
				txt_1 - текст для галочки
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
				'value' => _num($v)
			));

		//textarea (многострочное текстовое поле)
		case 5:
			/*
				txt_1 - текст для placeholder
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			return
			'<textarea id="'.$attr_id.'"'.$width.$placeholder.$disabled.'>'.
				$v.
			'</textarea>';

		//Select - выбор страницы
		case 6:
			/*
                txt_1 - текст, когда страница не выбрана
				функция _page('for_select', 'js')
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//input:text (однострочное текстовое поле)
		case 8:
			/*
				txt_1 - текст для placeholder
				txt_2 - текст по умолчанию
				num_1 - формат:
					32 - любой текст
					33 - цифры и числа
				num_2 - количество знаков после запятой
				num_3 - разрешать отрицательные значения
				num_4 - разрешать вносить 0
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';

			$value = $el['txt_2'];

			if($UNIT_ISSET)
				switch($el['num_1']) {
					default:
					case 32://любой текст
						$value = $v;
						break;
					case 33://цифры и числа
						$value = round($v, $el['num_2']);
						$value = $value || $el['num_4'] ? $value : '';
						break;
				}

			return// _pr($el).
				'<input type="text" id="'.$attr_id.'"'.$width.$placeholder.$disabled.' value="'.$value.'" />';

		//Выбор элемента из диалога или страницы
		case 13:
			/*
				txt_1 - текст для placeholder
				num_1 - источник выбора
						2119 - текущая страница
						2120 - диалог
				num_2 - если источник выбора диалог: (вспомогательный диалог [74] - выводит содержание диалога)
						2123 - конкретный диалог (из списка диалогов)
						2124 - указать значение, где находится диалог
				num_3 - значение для указания местонахождения диалога
				num_4 - ID диалога (список всех диалогов)
				num_5 - разрешать выбор значений во вложенных списках
			*/

			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$name = _num($v) ? _elemTitle($v) : (!empty($v) ? $v : '');
			if(!$v && $name)
				$name = '-empty-';

			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			'<div class="_selem dib prel bg-fff over1" id="'.$attr_id.'_selem"'.$width.'>'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
				'<input type="text" readonly class="inp curP w100p color-pay"'.$placeholder.$disabled.' value="'.$name.'" />'.
			'</div>';

		//Radio
		case 16:
			/*
				txt_1 - текст нулевого значения
				num_1 - горизонтальное положение
				значения из _element через dialog_id:19
			*/
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `block_id`=-".$el['id']."
					ORDER BY `sort`";
			$spisok = query_ass($sql);

			return _radio(array(
				'attr_id' => $attr_id,
				'light' => 1,
				'block' => !$el['num_1'],
				'interval' => 5,
				'value' => _num($v) ? _num($v) : $el['def'],
				'title0' => $el['txt_1'],
				'spisok' => $spisok,
				'disabled' => $disabled
			));

		//Select - произвольные значения
		case 17:
			/*
                txt_1 - текст нулевого значения
				значения из _element через dialog_id:19
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $el['def']
				   ));

		//Dropdown
		case 18:
			/*
                txt_1 - текст нулевого значения
				значения из _element через dialog_id:19
			*/
			return _dropdown(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $el['def']
				   ));

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: наполнение для некоторых компонентов: radio, select, dropdown
		case 19:
			/*
				Все действия через JS.
				Данные хранятся в _element. В block_id пишется отрицательный id главного элемента.

				num_1 - использовать Описания значений

				Значения:
					id
					txt_1 - title
					txt_2 - content
					def
					sort
			*/

			return '<div class="_empty min">Наполнение компонента</div>';

		//Select - выбор списка приложения
		case 24:
			/*
                txt_1 - текст, когда список не выбран
				num_1 - содержание селекта:
						0   - все списки приложения. Функция _dialogSpisokOn()
						960 - размещённые на текущем объекте
							  Списки размещаются диалогами 14(шаблон), 23(таблица), История действий
							  Идентификаторами результата являются id элементов (а не диалогов)
							  Функция _dialogSpisokOnPage()
						961 - привязанные к данному диалогу
							  Идентификаторами результата являются id элементов (а не диалогов)
							  Функция _dialogSpisokOnConnect()
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Select - выбор единицы из другого списка
		case 29:
			/*
				Для связки одного списка с другим
				Список нельзя связывать самого с собой

                num_1 - id диалога, через который вносятся данные выбираемого списка
                txt_1 - текст, когда единица не выбрана
                txt_3 - первый id элемента, составляющие содержание Select
                txt_4 - второй id элемента, составляющие содержание Select
				num_2 - возможность добавления новых значений
				num_3 - поиск значений вручную
				num_4 - блокировать выбор
				num_5 - учитывать уровни
			*/

			if(!$UNIT_ISSET)
				$v = _spisokCmpConnectIdGet($el);

			if(is_array($v))
				$v = $v['id'];

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Выбор значений для содержания Select
		case 31:
			/*
				num_1 - id элемента, размещающее Select, для которого выбираются значения
				txt_1 - имя первого значения
				num_2 - использовать ли второе значение
				txt_2 - имя второго значения
			*/
			$ex = explode(',', $v);
			$v0 = _num(@$ex[0]) ? _elemTitle($ex[0]) : '';
			$v1 = _num(@$ex[1]) ? _elemTitle($ex[1]) : '';
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<input type="text" id="'.$attr_id.'_sv" class="sv w125 curP over1 color-pay" placeholder="'.$el['txt_1'].'" val="0" readonly'.$disabled.' value="'.$v0.'" />'.
			($el['num_2'] ?
				'<input type="text" class="sv w150 curP over1 color-pay ml5" placeholder="'.$el['txt_2'].'" val="1" readonly'.$disabled.' value="'.$v1.'" />'
			: '');

		//Count - количество
		case 35:
			/*
                num_1 - минимальное значение
                num_2 - максимальное значение
                num_3 - шаг
                num_4 - может быть отрицательным (галочка)
			*/
			return _count(array(
						'attr_id' => $attr_id,
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - выбор колонки таблицы
		case 37:
			return _select(array(
						'attr_id' => $attr_id,
						'width' => $el['width']
				   ));

		//SA: Select - выбор диалогового окна
		case 38:
			/*
                txt_1 - нулевое значение
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - значения из существующего селекта
		case 41:
			/*

			*/

			if(!$bs_id = _num(@$US['block_id']))
				return '<div class="red">Отсутствует ID исходного блока.</div>';

			$BL = _blockOne($bs_id);
			if($BL['obj_name'] != 'dialog')
				return '<div class="red">Исходный блок не является блоком из диалога.</div>';

			if(!$EL = $BL['elem'])
				return '<div class="red">Отсутствует исходный элемент.</div>';

			if($EL['dialog_id'] != 17)
				return '<div class="red">Исходный элемент не является выпадающим полем.</div>';

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $EL['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $EL['def']
				   ));

		//Календарь
		case 51:
			/*
				num_1 - разрешать выбор прошедших дней
				num_2 - показывать время
			*/
			return _calendar(array(
				'attr_id' => $attr_id,
				'value' => $v
			));

		//Связка списка при помощи кнопки
		case 59:
			/*
				txt_1 - текст кнопки
                num_1 - id диалога, через который вносятся данные выбираемого списка
				num_4 - id диалога, которое открывается при нажатии на кнопку
			*/

			$v = is_array($v) ? _num($v['id']) : _num($v);

			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_button(array(
				'attr_id' => $attr_id.$el['afics'],
				'name' => $el['txt_1'],
				'color' => 'grey',
				'width' => $el['width'],
				'small' => 1,
				'class' => _dn(!$v)
			)).
			'<div class="'._dn($v).'">'.
				'<div class="icon icon-del-red pl fr'._tooltip('Отменить выбор', -53).'</div>'.
				'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
			'</div>';

		//Выбор цвета текста
		case 66:
			/*
			*/
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color" style="background-color:#000"></div>';

		//Выбор цвета фона
		case 70:
			$v = empty($v) ? '#fff' : $v;
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color-bg" style="background-color:'.$v.'"></div>';

		//Select - выбор значения списка
		case 85:
			/*
                num_1 - ID элемента select, который содержит списки
                txt_1 - текст нулевого значения
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));




		//---=== ЭЛЕМЕНТЫ ОТОБРАЖЕНИЯ ===---
		//button
		case 2:
			/*
				txt_1 - текст кнопки
				num_1 - цвет
				num_2 - маленькая кнопка
				num_3 - принимает значения списка, которое принимает страница
				num_4 - dialog_id, который назначен на эту кнопку
			*/
			$color = array(
				0 => '',      //Синий - по умолчанию
				1 => '',      //Синий
				2 => 'green', //Зелёный
				3 => 'red',   //Красный
				4 => 'grey',  //Серый
				5 => 'cancel',//Прозрачный
				6 => 'pink',  //Розовый
				7 => 'orange' //Оранжевый
			);

			//вставка исходного блока для передачи как промежуточного значения, если кнопка расположена в диалоге
			$block = _num(@$US['block_id']) ? ',block_id:'.$US['block_id'] : '';
			//если кнопка расположена в диалоговом окне, то указывается id этого окна как исходное
			$dialog_source = !empty($el['block']) && $el['block']['obj_name'] == 'dialog' ? ',dialog_source:'.$el['block']['obj_id'] : '';

			//кнопка принимает значения списка, которое принимает страница
			if($el['num_3'] && $UNIT_ISSET)
				$block = ',unit_id:'.$unit['id'];

			//если новая кнопка, будет создаваться новый диалог для неё
			if(!$el['num_4'])
				$block = ',block_id:'.$el['block_id'];

			return _button(array(
						'attr_id' => $attr_id,
						'name' => _br($el['txt_1']),
						'color' => $color[$el['num_1']],
						'width' => $el['width'],
						'small' => $el['num_2'],
						'class' => 'dialog-open',
						'val' => 'dialog_id:'.$el['num_4'].$block.$dialog_source
					));

		//Меню страниц
		case 3:
			/*
				num_1 - раздел (страница-родитель). В меню будут дочерние страницы
				num_2 - внешний вид:
						10 - Основной вид - горизонтальное меню
						11 - С подчёркиванием (гориз.)
						12 - Синие маленькие кнопки (гориз.)
						13 - Боковое вертикальное меню
			*/
			return _menu($el);

		//Заголовок
		case 4:
			/*
                txt_1 - текст заголовка
			*/
			return '<div class="hd2">'.$el['txt_1'].'</div>';

		//Ссылка на страницу
		case 9:
			/*
                txt_1 - текст ссылки
				num_1 - id страницы
			*/
			if(!$txt = $el['txt_1']) {
				$page = _page($el['num_1']);
				$txt = $page['name'];
			}
			return '<a class="inhr" href="'.URL.'&p='.$el['num_1'].'">'.
						$txt.
				   '</a>';

		//произвольный текст
		case 10:
			/*
                txt_1 - текст
			*/
			return _br($el['txt_1']);

		//Выбор значения для шаблона (выводится окно для выбора)
		case 11:
			/*
				Вставка элемента через функцию PHP12_v_choose

				txt_2 - id элемента, выбранного из диалога, который вносит данные списка
						возможна иерархия элементов через запятую 256,1312,560
			*/

			if(!$UNIT_ISSET)
				return _elemTitle($el['id']);

			if(!$ids = _ids($el['txt_2'], 1))
				return _msgRed('-el-yok-');//id элементов отсутствуют

			//получение значения, если нет вложенных списков
			if(count($ids) == 1)
				return _elem_11_v($ids[0], $unit);

			$u = $unit;
			$eid = 0;
			foreach($ids as $n => $elem_id) {
				$eid = $elem_id;
				if(!$ell = _elemOne($elem_id))
					return _msgRed('-no-el-'.$elem_id.'-');

				switch($ell['dialog_id']) {
					case 29:
					case 59:
						if(!$col = $ell['col'])
							return 'нет имени колонки';
						if(empty($u))
							return _msgRed('единица списка пуста. Шаг: '.$n);
						if(!$u = $u[$col])
							return _msgRed('вложенное значение отсутствует. Шаг: '.$n.'. col: '.$col);
						if(!is_array($u)) {
							$sql = "SELECT *
									FROM `_spisok`
									WHERE `id`=".$u;
							$u = query_assoc($sql);
						}
				}
			}

			return _elem_11_v($eid, $u);



			foreach($ids as $n => $elem_id) {
				if(!$elem = _elemOne($elem_id))
					return '-удалено-';
				switch($elem['dialog_id']) {
					//однострочное поле
					case 8:
						if(empty($unit))
							return '';
						if(empty($unit[$elem['col']]))
							return '';
						$txt = $unit[$elem['col']];
						if($n) {
							$el0 = _elemOne($ids[0]);
							if($el0['dialog_id'] == 29)
								if($el0['num_5']) {//вывод значения по уровням

									if($parent_id = $unit['parent_id'])
										while($parent_id) {
											$sql = "SELECT *
													FROM `_spisok`
													WHERE `id`=".$parent_id;
											if(!$u = query_assoc($sql))
												break;
											$txt = $u[$elem['col']].' » '.$txt;
											$parent_id = $u['parent_id'];
										}

								}
						}
						$send .= _br($txt);
						break;
					//связки
					case 29:
					case 59:
						if(!$sp = $unit[$elem['col']])
							break;
						if(!is_array($sp)) {
							$dialog = _dialogQuery($unit['dialog_id']);
							$sql = "SELECT *
									FROM `"._table($dialog['table_1'])."`
									WHERE `id`=".$sp;
							$unit = query_assoc($sql);
							break;
						}
						$unit = $sp;
						break;
					//Изображение
					case 60:
						if(!$col = $elem['col']) {
							$send .= '';
							break;
						}
						if(empty($unit)) {
							$send .= _imageNo($el['width']);
							break;
						}

	//					if(empty($unit[$elem['col']]))//id картинки хранится в колонке
	//						$send .= '';
	//					if(!$img_id = _num($unit[$elem['col']]))//получение id картинки, либо вывод её, если уже сформирована
	//						$send .= $unit[$elem['col']];

						$sql = "SELECT *
								FROM `_image`
								WHERE `obj_name`='elem_".$elem['id']."'
								  AND `obj_id`=".$unit['id']."
								  AND !`deleted`
								  AND !`sort`
								LIMIT 1";
						if(!$r = query_assoc($sql)) {
							$send .= _imageNo($el['width']);
							break;
						}
						$send .= _imageHtml($r, $el['width'], $el['num_7']);
						break;
				}
			}
			return $send;

		//SA: Функция PHP
		case 12:
			/*
				После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.

                txt_1 - имя функции (начинается с PHP12)
			*/

			if(!$el['txt_1'])
				return '<div class="_empty min">Отсутствует имя функции.</div>';
			if(!function_exists($el['txt_1']))
				return '<div class="_empty min">Фукнции <u>'.$el['txt_1'].'</u> не существует.</div>';
			if($is_edit)
				return '<div class="_empty min">Функция '.$el['txt_1'].'</div>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				$el['txt_1']($el, $unit);

		//Содержание единицы списка - шаблон
		case 14:
			/*
                num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
				num_2 - длина (количество строк, выводимых за один раз)
				txt_1 - сообщение пустого запроса

				настройка шаблона через вспомогательный элемент: dialig_id=25
			*/
			if($is_edit)
				return '<div class="_empty">Список <b class="fs14">'._dialogParam($el['num_1'],'name').'</b></div>';

			return
				_spisokShow($el).
				(_spisokIsSort($el['block_id']) ?
					'<script>_spisokSort("'.$el['attr_el'].'")</script>'
				: '');

		//Количество строк списка
		case 15:
			/*
                num_1 - id элемента, содержащего список, количество строк которого нужно выводить
				txt_1 "1" txt_2 - показана "1" запись
				txt_3 "2" txt_4 - показано "2" записи
				txt_5 "5" txt_6 - показано "5" записей
			*/
			return _spisokElemCount($el);

		//Информационный блок
		case 21:
			/*
                txt_1 - содержание
			*/
			return '<div class="_info">'._br($el['txt_1']).'</div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Список действий, привязанных к элементу
		case 22:
			if(!$bs_id = _num(@$US['block_id']))
				return _emptyMin('Отсутствует ID исходного блока.');

			if(!$BL = _blockOne($bs_id))
				return _emptyMin('Исходного блока id'.$bs_id.' не существует.');

			if($BL['obj_name'] != 'page' && $BL['obj_name'] != 'dialog')
				return _emptyMin('Действия можно назначать<br>только компонентам на страницах и диалоговых окнах.');

			$sql = "SELECT *
					FROM `_element_func`
					WHERE `block_id`=".$bs_id."
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				return _emptyMin('Действий не назначено.');

			//Названия действий
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'action_id').")";
			$act = query_ass($sql);

			//Названия условий
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'cond_id').")";
			$cond = query_ass($sql);

			//Конкретные значения
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'value_specific').")";
			$vs = query_ass($sql);

			//Названия эффектов
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'effect_id').")";
			$effect = query_ass($sql);
			$effect[0] = 'нет';

			$spisok = '';
			foreach($arr as $r) {
				$c = count(_ids($r['target'], 1));
				$targetName = 'блок'._end($c, '', 'а', 'ов');
				$targetColor = 'color-ref';
				if($r['dialog_id'] == 73) {
					$targetName = 'элем.';
					$targetColor = 'color-pay';
				}
				$spisok .=
					'<dd val="'.$r['id'].'">'.
					'<table class="bs5 bor1 bg-gr2 over2 mb5 curD">'.
						'<tr>'.
							'<td class="w25 top">'.
								'<div class="icon icon-move-y pl"></div>'.
							'<td class="w300">'.
								'<div class="fs15">'._dialogParam($r['dialog_id'], 'name').'</div>'.
								'<table class="bs3">'.
									'<tr><td class="fs12 grey top">Действие:'.
										'<td class="fs12">'.
											'<b class="fs12">'.$act[$r['action_id']].'</b>, если '.
				   (!$r['value_specific'] ? '<b class="fs12">'.$cond[$r['cond_id']].'</b>' : '').
					($r['value_specific'] ? 'выбрано: <b>'.$vs[$r['value_specific']].'</b>' : '').
					($r['action_reverse'] ? '<div class="fs11 color-555">(применяется обратное действие)</div>' : '').
					 ($r['effect_id'] ? '<tr><td class="fs12 grey r">Эффект:<td class="fs12 color-pay">'.$effect[$r['effect_id']] : '').
								'</table>'.
							'<td class="w70 b '.$targetColor.' top center pt3">'.
								$c.' '.$targetName.
							'<td class="w50 r top">'.
								'<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',dialog_source:'.$el['block']['obj_id'].'" class="icon icon-edit pl dialog-open'._tooltip('Настроить действие', -60).'</div>'.
								_iconDel(array(
									'class' => 'pl ml5 dialog-open',
									'val' => 'dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',del:1,dialog_source:'.$el['block']['obj_id']
								)).
					'</table>'.
					'</dd>';
			}

			return '<dl class="mar10">'.$spisok.'</dl>';

		//Содержание единицы списка - таблица
		case 23: return _spisok23($el);

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка ШАБЛОНА единицы списка
		case 25:
			/*
				имя объекта: spisok
				 id объекта: block_id, в котором размещается список
			*/
			if(!$UNIT_ISSET)
				return
				'<div class="bg-ffe pad10">'.
					'<div class="_empty min">'.
						'Настройка шаблона будет доступна после вставки списка в блок.'.
					'</div>'.
				'</div>';

			//определение ширины шаблона
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$unit['block_id'];
			if(!$block = query_assoc($sql))
				return 'Блока, в котором находится список, не существует.';

			setcookie('block_level_spisok', 1, time() + 2592000, '/');
			$_COOKIE['block_level_spisok'] = 1;

			//корректировка ширины с учётом отступов
			$ex = explode(' ', $unit['mar']);
			$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
			$line_r = $width < 980 ? ' line-r' : '';

			return
				'<div class="bg-ffc pad10 line-b">'.
					_blockLevelChange('spisok', $unit['block_id'], $width).
				'</div>'.
				'<div class="block-content-spisok'.$line_r.'" style="width:'.$width.'px">'._blockHtml('spisok', $unit['block_id'], $width).'</div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Содержание диалога для выбора значения
		case 26:
			/*
				Используется в диалогах: 7,11,36,40

				num_2 - Что выбирать:
							40: любые элементы
							41: элементы, которые вносят данные
							42: элементы, по которым можно производить поиск
							43: блоки
				num_3 - выбор нескольких значений
			*/
			if($el['block']['obj_name'] != 'dialog')
				return _emptyMin('Элемент может располагаться только в блоке Диалога');

			$dialog_id = _num(@$US['dialog_source']);

			//исходный блок
			if($bls_id = _num(@$US['block_id'], 1)) {
				//блок является элементом
				if($bls_id < 0) {
					if(!$EL = _elemOne(abs($bls_id)))
						return _emptyMin('Исходного элемента id'.$bls_id.' не существует.');
					$bls_id = $EL['block_id'];//обновление исходного блока
				}

				//$history = $el['dialog_id']

				if(!$BLS = _blockOne($bls_id))
					return _emptyMin('Исходный блок id'.$bls_id.' отсутствует.');

				if($el['num_2'] == 43 && $BLS['obj_name'] != 'dialog')
					return _emptyMin('Выбор блоков доступен только для диалогов.');

				//id диалога, в котором располагается выбор
				switch($el['block']['obj_id']) {
					case 7://поиск
						if(!$EL = $BLS['elem'])
							return _emptyMin('Содержание диалога будет доступно<br>после вставки элемента поиска в блок.');
						if(!$EL['num_1'])
							return _emptyMin('Содержание диалога будет доступно после выбора списка,<br>по которому будет производиться поиск.');
						if(!$sp = _elemOne($EL['num_1']))
							return _emptyMin('Отсутствует элемент, размещающий список.');
						$dialog_id = $sp['num_1'];
						break;
					case 11://вставка значения...
						if($BLS['obj_name'] == 'spisok') {//...в блок шаблона [14]
							$bl = _blockOne($BLS['obj_id']);
							if(!$bl['elem'])
								return _emptyMin('Содержание диалога будет доступно<br>после вставки элемента в блок.');
							if(!$dialog_id = $bl['elem']['num_1'])
								return _emptyMin('Содержание диалога будет доступно после выбора списка.');
							break;
						}
						if($BLS['obj_name'] == 'page') {
							if($BLS['elem'] && ($BLS['elem']['dialog_id'] == 14 || $BLS['elem']['dialog_id'] == 23)) {//списки [14,23]
								$dialog_id = $BLS['elem']['num_1'];
								break;
							}
							if(!$page = _page($BLS['obj_id']))
								return _emptyMin('Данные страницы '.$BLS['obj_id'].' не получены.');
							if(!$dialog_id = $page['spisok_id'])
								return _emptyMin('Страница не принимает значения единицы списка');
						}
						if($BLS['obj_name'] == 'dialog') {
							if($dialog_id = $US['dialog_source']) {
								//отображение диалога происходит для элемента, который выбирает значения для списка
								//требуется уточнение, где искать id диалога
								if($BLS['elem']['dialog_id'] == 31) {
									if(!$el31_id = _num($BLS['elem']['num_1']))
										return _emptyMin('Отсутствует id элемента, размещающего select');
									if(!$el31 = _elemOne($el31_id))
										return _emptyMin('Отсутствует элемент, размещающий select');
									if($el31['dialog_id'] == 24 && $el31['num_1']) {//$dialog_id - является элементом, размещающий выпадающий список-связку [29]
										if(!$ell = _elemOne($dialog_id))
											return _emptyMin('...');
										$dialog_id = _num($ell['block']['obj_id']);
									}
									if($el31['dialog_id'] != 24 && $el31['num_1']) {//$dialog_id - является элементом, размещающий выпадающий список-выбор списка [24]
										if(!$ell = _elemOne($dialog_id))
											return _emptyMin('....');
										$dialog_id = _num($ell['num_1']);
									}
								}
								break;
							}

							if(!$dialog_id)
								if($page = _page($US['page_id']))
									$dialog_id = $page['spisok_id'];

							if(!$dialog_id)
								$dialog_id = $BLS['obj_id'];
							break;
						}
						break;
					case 31://выбор значения для Выпадающего поля
						if($BLS['obj_name'] != 'dialog')
							return _emptyMin('Выбор значения только для диалогов');
						$dialog_id = $BLS['obj_id'];
						break;
					case 36://показ-скрытие блоков для галочки
					case 40://показ-скрытие блоков для выпадающего поля
					default:
						if($el['num_2'] == 43) {
							$dialog_id = $BLS['obj_id'];
							break;
						}
						return _emptyMin('Ненастроенный диалог '.$el['block']['obj_id']);
				}
			}

			if(!$dialog_id)
				return _emptyMin('Не найдено ID диалога, который вносит данные списка.');

			if(!$dialog = _dialogQuery($dialog_id))
				return _emptyMin('Диалога не существует, который вносит данные списка.');

			//поля, которые можно подсвечивать
			$choose_access = array();
			switch($el['num_2']) {
				case 40://любые элементы
					$choose_access = array('all'=>1);
					break;
				case 41: //элементы, которые вносят данные
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_is_insert`";
						$choose_access = query_ass($sql);
					break;
				case 42: //элементы, по которым можно производить поиск
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_search_access`";
						$choose_access = query_ass($sql);
					break;
				case 43: //блоки
					$choose_access = array('block'=>1);
					break;
			}

			//выделение уже выбранных полей, чтобы нельзя было их выбрать (для функций)
			$choose_deny = array();
/*
			$dialogCur = _dialogQuery($el['block']['obj_id']);
			if($dialogCur['base_tabl'] == '_element_func') {
				$id = $UNIT_ISSET ? _num($unit['id']) : 0;
				$sql = "SELECT *
						FROM `_element_func`
						WHERE `block_id`=".$bs_id."
						  AND `id`!=".$id;
				if($arr = query_arr($sql))
					foreach($arr as $r)
						foreach(_ids($r['target'], 1) as $t)
							$choose_deny[$t] = 1;
			}
*/

			$send = array(
				'choose' => 1,
				'choose_access' => $choose_access,
				'choose_sel' => _idsAss($v),       //ids ранее выбранных элементов или блоков
				'choose_deny' => $choose_deny      //ids элементов или блоков, которые выбирать нельзя (если они были выбраны другой фукцией того же элемента)
			);

			return
			'<div class="fs14 pad10 pl15 bg-gr2 line-b">Диалоговое окно <b class="fs14">'.$dialog['name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send).
			'<input type="hidden" class="dlg26" value="'.$dialog_id.'" />'.
			'<script>ELM'.$dialog_id.'='._BE('elem_js', 'dialog', $dialog_id).';</script>';

		//Значение списка: иконка удаления
		case 30:
			/*
				num_1 - иконка красного цвета
			*/

			if(!$UNIT_ISSET)
				return 'del';

			$dialog = _dialogQuery($unit['dialog_id']);

			//если редактирование запрещено, иконка не выводится
			if(!$dialog['del_on'])
				return '';

			return
			_iconDel(array(
				'red' => $el['num_1'],
				'class' => 'dialog-open pl',
				'val' => 'dialog_id:'.$unit['dialog_id'].',unit_id:'.$unit['id'].',del:1'
			));

		//Значение списка: порядковый номер
		case 32: return _spisokUnitNum($el, $unit);

		//Значение списка: дата
		case 33:
			/*
				num_1 - формат:
					29: 5 августа 2017
					30: 5 авг 2017
					31: 05/08/2017
				num_2 - не показывать текущий год
				num_3 - имена у ближайших дней:
					вчера
					сегодня
					завтра
				num_4 - показывать время в формате 12:45
			*/

			return _spisokUnitData($el, $unit);

		//Значение списка: иконка редактирования
		case 34:
			if(!$UNIT_ISSET)
				return 'edit';

			$dialog = _dialogQuery($unit['dialog_id']);

			//если редактирование запрещено, иконка не выводится
			if(!$dialog['edit_on'])
				return '';

			return
			_iconEdit(array(
				'class' => 'dialog-open pl',
				'val' => 'dialog_id:'.$unit['dialog_id'].',unit_id:'.$unit['id']
			));

		//Иконка вопрос: Выплывающая подсказка
		case 42:
			/*
				txt_1 - текст подсказки
				num_1 - сторона всплытия
					741 - сверху
					742 - снизу
					743 - слева
					744 - справа
			*/
			return '<div class="icon icon-hint pl" id="'.$attr_id.'"></div>';

		//Заметки
		case 52:
			/*
			*/
			return _note($el);

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка суммы значений единицы списка (для [27])
		case 56:
			/*
				Все действия через JS.
				cmp_id получает ids используемых элементов в определённом порядке
			*/
			if($is_edit)
				return '<div class="_empty min">Настройка суммы значений единицы списка</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';

		//Меню переключения блоков
		case 57:
			/*
				num_1 - внешний вид меню:
						1158 - Маленькие синие кнопки
						1159 - С нижним подчёркиванием
			*/

			if(empty($el['vvv']))
				return '';

			$type = array(
				1158 => 2,
				1159 => 1
			);

			$razdel = '';
			foreach($el['vvv'] as $r)
				$razdel .= '<a class="link'._dn($el['def'] != $r['id'], 'sel').'">'.$r['title'].'</a>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$el['def'].'" />'.
				'<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка пунктов меню переключения блоков (для [57])
		case 58:
			/*
				Все действия через JS.
			*/
			if($is_edit)
				return '<div class="_empty min">Настройка пунктов меню переключения блоков</div>';

			return '';

		//Загрузка изображений
		case 60:
			/*
				num_7 - ограничение высоты (настройка стилей)

				num_1 - максимальное количество изображений, которое разрешено загрузить
			*/
			if($is_edit)
				return '<div class="_empty min">Изображения</div>';

			$v = _num($v);

			//отметка загруженных изображений как неиспользуемые, которые были не сохранены в предыдущий раз
			$sql = "UPDATE `_image`
					SET `obj_name`='elem_".$el['id']."',
						`deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `obj_name`='elem_".$el['id']."_".USER_ID."'";
			query($sql);

			$html = '';
			$del_count = 0;
			if($unit_id = _num(@$unit['id'])) {
				$sql = "SELECT *
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND !`deleted`
						ORDER BY `sort`";
				if($spisok = query_arr($sql))
					foreach($spisok as $r)
						$html .= _imageDD($r);

				$sql = "SELECT COUNT(*)
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND `deleted`";
				$del_count = query_value($sql);
			}
			return
			'<div class="_image">'.
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
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
											'<td class="icon-image ii2">'.      //Указать ссылку на изображение
										'<tr><td class="icon-image ii3">'.      //Фото с вебкамеры
											'<td class="icon-image ii4'._dn($del_count, 'empty').'" val="'.$del_count.'">'.//Достать из корзины
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

		//Список истории действий
		case 68:
			if($is_edit)
				return '<div class="_empty min">История действий.</div>';

			/*
				num_8 - показывать только записи единицы списка, которые принимает текущая страница
			*/

			return _historySpisok($el);

		//Значение списка: имя пользователя
		case 69: return _spisokUnitUser($el, $unit);

		//Значение списка: иконка сортировки
		case 71:
			if(!$UNIT_ISSET)
				return 'sort';

			return '<div class="icon icon-move pl"></div>';




		//---=== ДЕЙСТВИЯ К ЭЛЕМЕНТАМ (ФУНКЦИИ) ===---
		//Список действий для Галочки [1]
		case 28: return 28;

		//Назначение действия для Галочки [1]: скрытие/показ блоков
		case 36:
			/*
				таблица _element_func
					action_id - действие для блоков
						726 - скрыть
						727 - показать
					cond_id - условие действия
						730 - галочка снята
						731 - галочка установлена
					action_reverse - применять обратное действие
					effect_id - эффекты
						44 - изчезновение/появление
						45 - сворачивание/разворачивание
					target - id блоков, на которые воздействует галочка
			*/
			return 36;

		//Список действий для Выпадающего поля [17]
		case 39: return 39;

		//Назначение действия для Выпадающего поля [17]: скрытие/показ блоков
		case 40:
			/*
				таблица _element_func
					action_id - действие для блоков
						709 - скрыть
						710 - показать
					cond_id - условие действия
						703 - значение не выбрано
						704 - значение выбрано
						705 - конкретное значение
					action_reverse - применять обратное действие (для выбрано/не выбрано)
					value_specific - конкртетное значение (при условии 705)
					effect_id - эффекты
						715 - изчезновение/появление
						716 - сворачивание/разворачивание
					target - id блоков, на которые воздействует галочка
			*/
			return 40;

		//Сборный текст
		case 44:
			/*
				txt_1 - ids элементов, наполняющих содержание
			*/

			if(!$el['txt_1'])
				return '';

			$txt = '';
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$el['txt_1'].")
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$txt .= _elemUnit($r, $unit);
				$txt .= $r['num_8'] ? ' ' : ''; //добавление пробела справа, если нужно
			}

			$txt = _spisokUnitUrl($el, $unit, $txt);

			return $txt;

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка содержания сборного текста
		case 49:
			/*
				Все действия через JS.
				cmp_id получает ids используемых элементов в определённом порядке
			*/
			if($is_edit)
				return '<div class="_empty min">Содержание сборного текста</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';




		//---=== ФИЛЬТРЫ ===---
		//Быстрый поиск - фильтр
		case 7:
			/*
                txt_1 - текст поиска
				num_1 - id элемента, содержащего список, по которому происходит поиск
				txt_2 - по каким полям производить поиск (id элементов через запятую диалога списка)
			*/

			$v = _spisokFilter('v', $el['id']);
			if($v === false) {
				$v = '';
				_spisokFilter('insert', array(
					'spisok' => $el['num_1'],
					'filter' => $el['id'],
					'v' => $v
				));
			}

			return _search(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'v' => $v,
						'disabled' => $disabled
					));

		//порядок - не доделано
		case 53:
			/*
			*/
			return 'порядок';

		//Галочка - фильтр
		case 62:
			/*
				txt_1 - текст для галочки
				num_1 - условие применяется:
						1439 - галочка установлена
						1440 - галочка НЕ установлена
				num_2 - id элемента, размещающего список
			*/

			$v = _spisokFilter('v', $el['id']);
			if($v === false) {
				$v = 0;
				_spisokFilter('insert', array(
					'spisok' => $el['num_2'],
					'filter' => $el['id'],
					'v' => $v
				));
			}

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
				'value' => $v
			));

		//Календарь
		case 77:
			/*
				num_1 - id элемента, размещающего список
			*/
			return _filterCalendar($el);

		//Меню
		case 78:
			/*
				num_1 - id элемента, размещающего список
				num_2 - id элемента, содержащего значения, составляющие меню
			*/
			return _filterMenu($el);

		//Очистка фильтра
		case 80:
			/*
				txt_1 - имя кнопки
				num_1 - id элемента, размещающего список
			*/

			$diff = _spisokFilter('diff', $el['num_1']);
			return _button(array(
						'attr_id' => $attr_id,
						'name' => _br($el['txt_1']),
						'color' => 'red',
						'width' => $el['width'],
						'small' => 1,
						'class' => _dn($is_edit || $diff)
					));

		//Фильтр-select: привязанный список
		case 83:
			/*
                num_1 - воздействие на список
                num_2 - привязанный список
			*/

			$v = _spisokFilter('v', $el['id']);
			if($v === false) {
				$v = 0;
				_spisokFilter('insert', array(
					'spisok' => $el['num_1'],
					'filter' => $el['id'],
					'v' => $v
				));
			}


			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => $v
				   ));



		//---=== СВЯЗКИ ===---
		//Настройка суммы значений единицы списка
		case 27:
			/*
				txt_2 - ids значений для подсчёта
			*/
			return $el['name'];

		//количество значений привязанного списка
		case 54:
			/*
				num_1 - привязанный список
			*/
			return $el['name'];

		//сумма значений привязанного списка
		case 55:
			/*
				для хранения сумм используется колонка sum_1, sum_2, ...

				num_1 - привязанный список
				num_2 - id элемента значения (колонки) привязанного списка
			*/
			return $el['name'];
	}

	return'неизвестный элемент='.$el['dialog_id'];
}


function _BE($i, $i1=0, $i2=0) {//кеширование элементов приложения
	global $BE_FLAG, $G_BLOCK, $G_ELEM, $G_DLG;

	_beDefine();

	//получение данных всех блоков
	if($i == 'block_all')
		return $G_BLOCK;

	//получение данных одного блока
	if($i == 'block_one') {
		//ID блока
		if(!$i1)
			return array();
		if(!isset($G_BLOCK[$i1]))
			return array();

		$send = $G_BLOCK[$i1];
		$send['elem'] = $send['elem_id'] ? $G_ELEM[$send['elem_id']] : array();

		return $send;
	}

	//получение блоков для конкретного объекта
	if($i == 'block_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$send = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;

			$send[$id] = _beBlockBg($r);
		}

		return $send;
	}

	//получение блоков для конкретного объекта c учётом иерархии
	if($i == 'block_obj') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;

			$r['child'] = array();
			$r['elem'] = array();

			//вставка элемента в блок
			if($r['elem_id'] && isset($G_ELEM[$r['elem_id']])) {
				$el = $G_ELEM[$r['elem_id']];
				$el['block'] = $G_BLOCK[$id];//предварительно прикрепление данных блока к элементу
				$r['elem'] = _beElemVvv($el);
			}

			$blk[$id] = _beBlockBg($r);
		}

		$child = array();
		foreach($blk as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		return _blockArrChild($child);
	}

	//массив блоков в формате JS для конкретного объекта
	if($i == 'block_js') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return '{}';

		$send = array();
		foreach($G_BLOCK as $id => $bl) {
			if($bl['obj_name'] != $obj_name)
				continue;
			if($bl['obj_id'] != $obj_id)
				continue;

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

	//очистка кеша блоков
	if($i == 'block_clear') {
		_cache_clear('BLK_page');
		_cache_clear('BLK_page', 1);
		_cache_clear('BLK_dialog');
		_cache_clear('BLK_dialog', 1);
		_cache_clear('BLK_SPISOK_page');
		_cache_clear('BLK_SPISOK_page', 1);
		_cache_clear('BLK_SPISOK_dialog');
		_cache_clear('BLK_SPISOK_dialog', 1);
		$BE_FLAG = 0;
	}

	//получение данных всех элементов
	if($i == 'elem_all')
		return $G_ELEM;

	//получение данных одного элемента
	if($i == 'elem_one') {
		//ID элемента
		if(!$i1)
			return array();
		if(!isset($G_ELEM[$i1]))
			return array();

		$send = $G_ELEM[$i1];
		$send['block'] = $send['block_id'] ? $G_BLOCK[$send['block_id']] : array();

		return $send;
	}

	//получение элементов для конкретного объекта
	if($i == 'elem_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$send = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			if(!$elem_id = $r['elem_id'])
				continue;

			$send[$elem_id] = _beElemVvv($G_ELEM[$elem_id]);
		}

		return $send;
	}

	if($i == 'elem_js') {//массив элементов в формате JS
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return '{}';

		$send = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			if(!$elem_id = $r['elem_id'])
				continue;

			$send[$elem_id] = _beElemVvv($G_ELEM[$elem_id]);
		}

		return _json($send);
	}

	//очистка кеша элементов
	if($i == 'elem_clear') {
		_cache_clear('ELM_page');
		_cache_clear('ELM_page', 1);
		_cache_clear('ELM_dialog');
		_cache_clear('ELM_dialog', 1);
		_cache_clear('ELM_SPISOK_page');
		_cache_clear('ELM_SPISOK_page', 1);
		_cache_clear('ELM_SPISOK_dialog');
		_cache_clear('ELM_SPISOK_dialog', 1);
		$BE_FLAG = 0;
	}

	//получение данных одного диалога
	if($i == 'dialog') {
		//ID диалога
		if(!$dialog_id = _num($i1))
			return array();
		if(!isset($G_DLG[$dialog_id]))
			return array();

		$send = $G_DLG[$dialog_id];

		return $send;
	}

	//очистка кеша диалогов
	if($i == 'dialog_clear') {
		_cache_clear('dialog');
		_cache_clear('dialog', 1);
		$BE_FLAG = 0;
	}

	return false;
}
function _beDefine() {//получение блоков и элементов из кеша
	global  $BE_FLAG,//флаг заполненных глобальных элементов
			$G_BLOCK, $G_ELEM, $G_DLG;

	//если флаг установлен, значит кеш был обновлён, глобальные элементы заполнены
	if($BE_FLAG)
		return;

	$G_BLOCK = array();
	$G_ELEM = array();

	//диалоги
	$G_DLG = _beDlg();

	//блоки страниц
	_beBlockType('page');
	//блоки диалогов
	_beBlockType('dialog');

	$BE_FLAG = 1;
}
function _beBlockType($type) {//получение данных о блоках по типу
	global $G_BLOCK;

	$key = 'BLK_'.$type;

	//глобальные
	if(!$block_global = _cache_get($key, 1)) {
		$sql = "SELECT `id`
				FROM `_".$type."`
				WHERE !`app_id`";
		$obj_ids = query_ids($sql);

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$type."'
				  AND `obj_id` IN (".$obj_ids.")
				ORDER BY `parent_id`,`y`,`x`";
		$block_global = query_arr($sql);
		$block_global = _beBlockForming($block_global);
		$block_global = _beElemIdSet($block_global);

		_cache_set($key, $block_global, 1);
	}

	$G_BLOCK += $block_global;
	_beBlockSpisok($type, $block_global, 1);
	_beBlockElem($type, $block_global, 1);

	if(!APP_ID)
		return;

	//для конкретного приложения
	if(!$block_app = _cache_get($key)) {
		$sql = "SELECT `id`
				FROM `_".$type."`
				WHERE `app_id`=".APP_ID;
		$obj_ids = query_ids($sql);

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$type."'
				  AND `obj_id` IN (".$obj_ids.")";
		$block_app = query_arr($sql);
		$block_app = _beBlockForming($block_app);
		$block_app = _beElemIdSet($block_app);

		_cache_set($key, $block_app);
	}

	$G_BLOCK += $block_app;
	_beBlockSpisok($type, $block_app);
	_beBlockElem($type, $block_app);
}
function _beBlockSpisok($type, $block, $global=0) {//получение данных о блоках-списках
	global $G_BLOCK;

	if(empty($block))
		return;

	$key = 'BLK_SPISOK_'.$type;
	if(!$arr = _cache_get($key, $global)) {
		//если получен пустой массив и при этом запись в кеше была, запрос из базы не производится
		if(_cache_isset($key, $global))
			return;

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='spisok'
				  AND `obj_id` IN ("._idsGet($block).")";
		$arr = query_arr($sql);
		$arr = _beBlockForming($arr);
		$arr = _beElemIdSet($arr);

		_cache_set($key, $arr, $global);
	}

	$G_BLOCK += $arr;
	_beBlockElem('SPISOK_'.$type, $arr, $global);
}
function _beBlockForming($arr) {//формирование массива блоков для кеша
	$data = array();
	foreach($arr as $r) {
		$id = _num($r['id']);
		$data[$id] = array(
			'id' => _num($r['id']),
			'parent_id' => _num($r['parent_id']),
			'child_count' => _num($r['child_count']),
			'sa' => _num($r['parent_id']),
			'obj_name' => $r['obj_name'],
			'obj_id' => _num($r['obj_id']),
			'click_action' => _num($r['click_action']),
			'click_page' => _num($r['click_page']),
			'click_dialog' => _num($r['click_dialog']),
			'x' => _num($r['x']),
			'xx' => _num($r['xx']),
			'xx_ids' => $r['xx_ids'],
			'y' => _num($r['y']),
			'w' => _num($r['w']),
			'h' => _num($r['h']),
			'width' => _num($r['width']),
			'width_auto' => _num($r['width_auto']),
			'height' => _num($r['height']),
			'pos' => $r['pos'],
			'bg' => $r['bg'],
			'bg_ids' => $r['bg_ids'],
			'bor' => $r['bor'],
//			user_id_add: 1
//			dtime_add: 2017-10-23 00:59:48

			'attr_bl' => '#bl_'.$id,
			'elem_id' => 0
		);
	}

	return $data;
}
function _beBlockBg($r) {
	global $G_BLOCK, $G_ELEM, $G_DLG;

	//если присутствует элемент-цвет фона, получение колонок для цвета, если потребуется окраска блока
	$r['xx_ids'] = _idsAss($r['xx_ids']);
	$r['bg_col'] = '';    //имя колонки, по которой будет выбираться цвет
	$r['bg_connect'] = '';//имя колонки, если это подключаемый список
	if($r['bg'] == 'bg70')
		if($ids = _ids($r['bg_ids'], 1))
			foreach($ids as $elem_id)
				if($el = $G_ELEM[$elem_id])
					switch($el['dialog_id']) {
						case 29:
						case 59:
							$r['bg_connect'] = $el['col'];
							break;
						case 70:
							$r['bg_col'] = $el['col'];
							break;
					}


	//Отображение варианта цвета для динамической окраски блоков
	//Будет открываться диалог, который вносит данные списка, чтобы указать, откуда брать цвет для окраски
	//Иконка показывается, если:
	//      1. spisok-блоки. id диалога, который вносит значения списка
	//      2. dialog-блоки. id этого диалога
	//      3. page-блоки.   id диалога, который вносит значения списка, страница которой получает значения списка
	$bg70 = 0;
	if($r['obj_name'] == 'spisok')
		if($bl = $G_BLOCK[$r['obj_id']])
			if($el = $G_ELEM[$bl['elem_id']])
				if($el['dialog_id'] == 14 || $el['dialog_id'] == 59)
					if($dlg_id = _num($el['num_1']))
						$bg70 = $dlg_id;
	if($r['obj_name'] == 'dialog') {
		$dialog_parent_id = _num($G_DLG[$r['obj_id']]['dialog_parent_id']);
		$bg70 = $dialog_parent_id ? $dialog_parent_id : $r['obj_id'];
	}
	if($r['obj_name'] == 'page')
		if($page = _page($r['obj_id']))
			$bg70 = $page['spisok_id'];

	$r['bg70'] = $bg70;

	return $r;
}
function _beBlockElem($type, $BLK, $global=0) {//элементы, которые расположены в блоках
	global $G_ELEM, $G_DLG;

	if(empty($BLK))
		return;

	$key = 'ELM_'.$type;
	if(!$ELM = _cache_get($key, $global)) {
		if(_cache_isset($key, $global))
			return;

		$ELM = array();

		//наличие функций в элементах
		$sql = "SELECT `block_id`,1
				FROM `_element_func`
				WHERE `block_id` IN("._idsGet($BLK).")
				GROUP BY `block_id`";
		$isFunc = query_ass($sql);

		//переменная для сбора ID элементов-таблиц
		$elem23 = array();

		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id` IN ("._idsGet($BLK).")";
		foreach(query_arr($sql) as $elem_id => $el) {
			if($el['dialog_id'] == 23)
				$elem23[] = $elem_id;

			$el['hidden'] = 0;

			unset($el['sort']);
			unset($el['user_id_add']);
			unset($el['dtime_add']);

			//подсказка для элемента
			if(!$el['hint_on']) {
				unset($el['hint_msg']);
				unset($el['hint_side']);
				unset($el['hint_obj_pos_h']);
				unset($el['hint_obj_pos_v']);
				unset($el['hint_delay_show']);
				unset($el['hint_delay_hide']);
			}

			//переделка значений элемента в INT, если есть
			foreach($el as $k => $v)
				if(preg_match(REGEXP_INTEGER, $v))
					$el[$k] = _num($v, 1);

			$dlg = $G_DLG[$el['dialog_id']];

			$el['attr_el'] = '#el_'.$elem_id;
			$el['attr_cmp'] = '#cmp_'.$elem_id;
			$el['size'] = $el['size'] ? _num($el['size']) : 13;
			$el['is_img'] = 0;
			$el['is_func'] = _num(@$isFunc[$el['block_id']]);
			$el['style_access'] = _num($dlg['element_style_access']);
			$el['url_access'] = _num($dlg['element_url_access']);
			$el['hint_access'] = _num($dlg['element_hint_access']);
			$el['dialog_func'] = _num($dlg['element_dialog_func']);
			$el['afics'] = $dlg['element_afics'];
			$el['hidden'] = _num($dlg['element_hidden']);

			if($el['width_min'] = _num($dlg['element_width_min'])) {
				//определение максимальной ширины, на которую может растягиваться элемент
				$ex = explode(' ', $el['mar']);
				$width_max = $BLK[$el['block_id']]['width'] - $ex[1] - $ex[3];
				$el['width_max'] = floor($width_max / 10) * 10;
			}

			$el['func'] = array();
			$el['vvv'] = array();//значения для некоторых компонентов

			$ELM[$elem_id] = $el;
		}

		//элементы-ячейки таблиц
		if(!empty($elem23)) {
			$sql = "SELECT *
					FROM `_element`
					WHERE !`block_id`
					  AND `parent_id` IN (".implode(',', array_unique($elem23)).")";
			foreach(query_arr($sql) as $elem_id => $el) {
				unset($el['sort']);
				unset($el['user_id_add']);
				unset($el['dtime_add']);
				unset($el['hint_msg']);
				unset($el['hint_side']);
				unset($el['hint_obj_pos_h']);
				unset($el['hint_obj_pos_v']);
				unset($el['hint_delay_show']);
				unset($el['hint_delay_hide']);
				$ELM[$elem_id] = $el;
			}
		}

		$sql = "SELECT *
				FROM `_element_func`
				WHERE `block_id` IN ("._idsGet($BLK).")
				ORDER BY `sort`";
		foreach(query_arr($sql) as $r) {
			$elem_id = $BLK[$r['block_id']]['elem_id'];
			$ELM[$elem_id]['func'][] = array(
				'dialog_id' => _num($r['dialog_id']),
				'action_id' => _num($r['action_id']),
				'cond_id' => _num($r['cond_id']),
				'action_reverse' => _num($r['action_reverse']),
				'value_specific' => _num($r['value_specific']),
				'effect_id' => _num($r['effect_id']),
				'target' => _idsAss($r['target'])
			);
		}

		_cache_set($key, $ELM, $global);
	}

	$G_ELEM += $ELM;
}
function _beElemIdSet($arr) {//добавление id элемента к блоку
	if(empty($arr))
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	$elem = query_arr($sql);

	foreach($elem as $r) {
		$arr[$r['block_id']]['elem_id'] = _num($r['id']);
	}

	return $arr;
}
function _beElemVvv($el) {//вставка дополнительных значений в элемент
	global $G_ELEM, $G_DLG;

	switch($el['dialog_id']) {
		//значение, выбранное из диалога - переустановка некоторых настроек
		case 11:
			if(!$ids = _ids($el['txt_2'], 1))
				break;
			$c = count($ids) - 1;
			$last_id = $ids[$c];
			if(empty($G_ELEM[$last_id]))
				break;
			$el11 = $G_ELEM[$last_id];
			if(!$dlg11 = $G_DLG[$el11['dialog_id']])
				break;

			switch($el11['dialog_id']) {
				case 60://image
					$el['style_access'] = _num($dlg11['element_style_access']);
					$el['url_access'] = _num($dlg11['element_url_access']);
					$el['hint_access'] = _num($dlg11['element_hint_access']);
					$el['dialog_func'] = _num($dlg11['element_dialog_func']);
					$el['afics'] = $dlg11['element_afics'];
					$el['is_img'] = 1;
					break;
			}
			break;
		//select - произвольные значения
		case 17:
		//dropdown
		case 18: $el['vvv'] = _elemValue($el['id']); break;
		//Меню переключения блоков - список пунктов
		case 57:
			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=-".$el['id']."
					ORDER BY `sort`";
			if(!$elArr = query_arr($sql))
				break;

			$spisok = array();
			foreach($elArr as $idd => $rr)
				$spisok[] = array(
					'id' => _num($idd),
					'title' => $rr['txt_1'],
					'blk' => $rr['txt_2']
				);

			$el['vvv'] = $spisok;
			break;
		//фильтр-select
		case 83:
			if(!$dialog_id = $el['num_2'])
				break;
			if(!$dlg = $G_DLG[$dialog_id])
				break;

			$field = $dlg['field1'];

			$cond = "`t1`.`id`";
			if(isset($field['deleted']))
				$cond .= " AND !`t1`.`deleted`";
			if(isset($field['app_id']))
				$cond .= " AND `t1`.`app_id`=".APP_ID;
			if(isset($field['dialog_id']))
				$cond .= " AND `t1`.`dialog_id`=".$dialog_id;

			$sql = "SELECT `t1`.*"._spisokJoinField($dlg)."
					FROM "._tableFrom($dlg)."
					WHERE ".$cond."
					ORDER BY `sort` DESC
					LIMIT 50";
			if(!$spisok = query_arr($sql))
				break;

			$vvv = array();

			foreach($spisok as $rr)
				$vvv[] = array(
					'id' => $rr['id'],
					'title' => $rr['txt_1']
				);

			$el['vvv'] = $vvv;
			break;
	}

	return $el;
}
function _beDlg() {//получение данных диалогов из кеша
	$key = 'dialog';
	//глобальные диалоги
	if(!$global = _cache_get($key, 1)) {
		$sql = "SELECT *
				FROM `_dialog`
				WHERE !`app_id`";
		$global = query_arr($sql);

		_cache_set($key, $global, 1);
	}

	$global = _beDlgField($global);

	if(!APP_ID)
		return $global;

	//диалоги конкретного приложения
	if(!$local = _cache_get($key)) {
		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID;
		$local = query_arr($sql);

		_cache_set($key, $local);
	}

	$local = _beDlgField($local);

	return $global + $local;
}
function _beDlgField($dialog) {//вставка колонок таблиц в диалоги
	//колонки по каждой таблице, используемые в диалогах
	$key = 'field';
	if(!$field = _cache_get($key, 1)) {
		$sql = "SELECT DISTINCT(`table_1`)
				FROM `_dialog`
				WHERE `table_1`";
		$ids = _ids(query_ids($sql), 1);
		foreach($ids as $table_id) {
			$sql = "DESCRIBE `"._table($table_id)."`";
			foreach(query_array($sql) as $r)
				$field[$table_id][$r['Field']] = 1;
		}

		_cache_set($key, $field, 1);
	}

	//список колонок, присутствующих в таблицах 1 и 2
	foreach($dialog as $dlg_id => $r)
		foreach(array(1,2) as $id) {
			$dialog[$dlg_id]['field'.$id] = array();
			$table_id = $r['table_'.$id];
			if($dialog[$dlg_id]['table_name_'.$id] = _table($table_id))
				$dialog[$dlg_id]['field'.$id] = $field[$table_id];
		}

	return $dialog;
}

