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
						'</div>'.
					'</div>',

		'dialog_del' => '<div class="pad10">'.
							'<div class="_empty min">'.
								'Содержание удаления записи не настроено.'.
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
function _blockHtml($obj_name, $obj_id, $unit=array(), $grid_id=0) {//вывод структуры блоков для конкретного объекта
	if(!$block = _BE('block_obj', $obj_name, $obj_id))
		return _blockName($obj_name, 'empty');
	if(!is_array($unit))
		return $unit;

	$width = _blockObjWidth($obj_name, $obj_id);

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
						blk_edit: включение настройки блоков
						blk_choose: выбор блоков (только корневых)
						v_choose: выбор элемента
						elem_width_change: изменение ширины элементов
	*/
	if(empty($arr))
		return '';

	//условия для настройки блоков конкретного объекта
	if(!isset($unit['blk_edit'])) {
		$id = key($arr);
		switch($arr[$id]['obj_name']) {
			default:
			case 'page':        $v = PAS; break;
			case 'dialog':      $v = 0; break;
			case 'dialog_del':  $v = 0; break;
			case 'spisok':      $v = 0; break;
		}
		$unit['blk_edit'] = $v;
	}

	$BLK_EDIT = $unit['blk_edit'];
	if(!empty($unit['blk_choose']))
		$BLK_EDIT = 1;

	$MN = 10;//множитель
	$wMax = round($WM / $MN);

	//подстановка нижней линии, если блоки не доходят до низу
	$hSum = 0;

	//составление структуры блоков по строкам
	$block = array();
	foreach($arr as $r) {
		if(!$BLK_EDIT
		&& empty($unit['v_choose'])
		&& $r['elem_id']
		&& $r['elem']['hidden']
		) continue;

		$block[$r['y']][$r['x']] = $r;
	}

	if(empty($block))
		return '';

	ksort($block);
	end($block);
	$yEnd = key($block);

	$send = '';
	$BT = $BLK_EDIT ? ' bor-t-dash' : '';
	$BR = $BLK_EDIT ? ' bor-r-dash' : '';
	$BB = $BLK_EDIT ? ' bor-b-dash' : '';
	$br1px = $BLK_EDIT ? 1 : 0;//показ красной разделительной линии справа

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

		//скрытие всей строки, если все блоки в строке являются скрытыми
		$strHide = !$BLK_EDIT && empty($unit['v_choose']);
		if(!$BLK_EDIT && empty($unit['v_choose']))
			foreach($xStr as $n => $r)
				if(!$r['hidden']) {//если хотя бы один блок не скрыт, вся строка не будет скрыта
					$strHide = 0;
					break;
				}

		$send .=
			'<div class="bl-div'._dn(!$strHide).'">'.
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
			if($r['bg'] && !_ids($r['bg']))
				$cls[] = $r['bg'];
			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == $grid_id ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls[] = _dn(!(!$BLK_EDIT && empty($unit['v_choose']) && $r['hidden']));
			$cls[] = $r['click_action'] == 2081 && $r['click_page']   ? 'curP block-click-page pg-'.$r['click_page'] : '';
			$cls[] = !$BLK_EDIT && $r['click_action'] == 2082 && $r['click_dialog'] ? 'curP dialog-open' : '';
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + ($BLK_EDIT ? 0 : $bor[1]);
			$width = $r['width'] - ($xEnd ? 0 : $br1px) - $borPx;

			//если блок списка шаблона, attr_id не ставится
			$attr_id = !$BLK_EDIT && $r['obj_name'] == 'spisok' ? '' : ' id="bl_'.$r['id'].'"';

			$send .= '<td'.$attr_id.
						' class="'.$cls.'"'.
						' style="'._blockStyle($r, $width, $unit).'"'.
		   ($BLK_EDIT ? ' val="'.$r['id'].'"' : '').
		  (!$BLK_EDIT && $r['click_action'] == 2082 && $r['click_dialog'] ?
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
function _blockLevelChange($obj_name, $obj_id) {//кнопки для изменения уровня редактирования блоков
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
	'<div id="block-level-'.$obj_name.'" val="'.$obj_name.':'.$obj_id.'">'.
		'<button class="vk small grey block-grid-on">Управление блоками</button>'.
		$html.
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
	if(empty($unit['blk_edit']))
		return '';
	//выход, если включено изменение ширины элемента
	if(!empty($unit['elem_width_change']))
		return '';
	//выход, если выбор элемента
	if(!empty($unit['v_choose']))
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
	if(empty($unit['blk_choose']))
		return '';
	if($r['parent_id'])//выбирать можно только корневые блоки
		return '';

	//отметка выбранных полей
	$block_id = $r['id'];
	$sel = isset($unit['blk_sel'][$block_id]) ? ' sel' : '';
	$deny = isset($unit['blk_deny'][$block_id]) ? ' deny' : '';

	return '<div class="blk-choose'.$sel.$deny.'" val="'.$block_id.'"></div>';
}
function _blockElemChoose_old($r, $unit) {//подсветка для выбора элементов
	//условие выбора
	if(empty($unit['choose_old']))
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
	$sel = empty($unit['v_id_sel'][$elem_id]) ? '' : ' sel';

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
	if(!_elemUnitIsEdit($unit))
		if($ids = _ids($r['bg'], 1)) {
			$bg = $unit;
			foreach($ids as $id) {
				if($el = _elemOne($id)) {
					$bg = $bg[$el['col']];
				}
			}
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
function _blockObjWidth($obj_name, $obj_id=0) {//получение ширины объекта (страницы, диалога, списка)
	switch($obj_name) {
		case 'page': return 1000;
		case 'dialog': return _dialogParam($obj_id, 'width');
		case 'dialog_del': return 500;
		case 'spisok':
			//получение элемента, который содержит список (для корректировки ширины с отступами)
			if(!$elm14 = _elemOne($obj_id))
				return 0;
			$ex = explode(' ', $elm14['mar']);
			return floor(($elm14['block']['width'] - $ex[1] - $ex[3]) / 10) * 10;
	}
	return 0;
}

function _elemDiv($el, $unit=array()) {//формирование div элемента
	if(!$el)
		return '';

	$txt = _elemUnit($el, $unit);

	//если элемент списка шаблона, attr_id не ставится
	$attr_id = empty($unit['blk_edit']) && $el['block']['obj_name'] == 'spisok' ? '' : ' id="el_'.$el['id'].'"';

	$cls = array();
	$cls[] = _elemFormatColor($txt, $el, $el['color']);
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormat($txt, $el);
	$txt = _spisokUnitUrl($el, $unit, $txt);

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
function _elemUnitIsEdit($unit) {//определение в каком режиме находится блочная структура (рабочий или настройка)
	$edit = 0;

	if(!empty($unit['blk_edit']))
		$edit = 1;

	if(!empty($unit['blk_choose']))
		$edit = 1;

	if(!empty($unit['v_choose']))
		$edit = 1;

	if(!empty($unit['elem_width_change']))
		$edit = 1;

	return $edit;
}
function _elemUnit($el, $unit=array()) {//формирование элемента страницы
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//значение из списка
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']] : '';
	$is_edit = _elemUnitIsEdit($unit);
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
						'class' => $is_edit ? '' : 'dialog-open',
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
			return _menu($el, $is_edit);

		//Заголовок
		case 4:
			/*
                txt_1 - текст заголовка
			*/
			return '<div class="hd2">'.$el['txt_1'].'</div>';

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

		//Фильтр - быстрый поиск
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
				return _elem_11_v($el, $ids[0], $unit, $is_edit);

			$u = $unit;
			$ell_id = 0;//id элемента, который содержит элемент 11 (в txt_2)
			foreach($ids as $n => $elem_id) {
				$ell_id = $elem_id;
				if(!$ell = _elemOne($elem_id))
					return _msgRed('-no-el-'.$elem_id.'-');

				switch($ell['dialog_id']) {
					case 29:
					case 59:
						if(!$col = $ell['col'])
							return _msgRed('нет имени колонки');
						if(empty($u))
							return _msgRed('единица списка пуста. Шаг: '.$n);
						if(!isset($u[$col]))
							return _msgRed('вложенное значение отсутствует. Шаг: '.$n.'. col: '.$col);
						if(!is_array($u[$col]) && $u[$col] == 0)
							return $ell['txt_1'];
						$u = $u[$col];
						if(!is_array($u)) {
							$sql = "SELECT *
									FROM `_spisok`
									WHERE `id`=".$u;
							$u = query_assoc($sql);
						}
				}
			}

			return _elem_11_v($el, $ell_id, $u, $is_edit);

		//SA: Функция PHP
		case 12:
			/*
				После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.

                txt_1 - имя функции (начинается с PHP12)
			*/

			if(!$el['txt_1'])
				return '<div class="_empty min">Отсутствует имя функции.</div>';
			if(!function_exists($el['txt_1']))
				return '<div class="_empty min">Фукнции <b class="fs14">'.$el['txt_1'].'</b> не существует.</div>';
			if($is_edit)
				return '<div class="_empty min">Функция '.$el['txt_1'].'</div>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				$el['txt_1']($el, $unit);

		//Выбор элемента из диалога или страницы
		case 13:
			/*
				txt_1 - текст для placeholder
				num_1 - источник выбора
						2119 - текущая страница
						2120 - диалог
				num_2 - если источник выбора диалог: (вспомогательный диалог [11] - выводит содержание диалога)
						2123 - конкретный диалог (из списка диалогов)
						2124 - указать значение, где находится диалог
				num_3 - элемент-значение для указания местонахождения диалога
				num_4 - ID диалога (список всех диалогов)
				num_5 - выбор значений во вложенных списках
				num_6 - выбор нескольких значений
			*/

			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$title = _num($v) ? _elemTitle($v) : (!empty($v) ? $v : '');
			if(!$v && $title)
				$title = '-empty-';

			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			'<div class="_selem dib prel bg-fff over1" id="'.$attr_id.'_selem"'.$width.'>'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
				'<input type="text" readonly class="inp curP w100p color-pay"'.$placeholder.$disabled.' value="'.$title.'" />'.
			'</div>';

		//Содержание единицы списка - шаблон
		case 14:
			//диалог, через который вносятся данные списка
			if(!$dialog_id = $el['num_1'])
				return
				'<div class="_empty">'.
					'<span class="fs15 red">'.
						'Не указан список для вывода данных.'.
					'</span>'.
				'</div>';

			if(!$DLG = _dialogQuery($dialog_id))
				return
				'<div class="_empty">'.
					'<span class="fs15 red">'.
						'Списка <b class="fs15">'.$dialog_id.'</b> не существует.'.
					'</span>'.
				'</div>';

			if($is_edit)
				return
				'<div class="_empty">'.
					'Список-шаблон <b class="fs14">'.$DLG['name'].'</b>'.
				'</div>';

			return _spisok14($el);

		//Количество строк списка
		case 15:
			/*
                num_1 - id элемента, содержащего список, количество строк которого нужно выводить
				txt_1 "1" txt_2 - показана "1" запись
				txt_3 "2" txt_4 - показано "2" записи
				txt_5 "5" txt_6 - показано "5" записей
			*/
			return _spisokElemCount($el);

		//Radio
		case 16:
			/*
				txt_1 - текст нулевого значения
				num_1 - горизонтальное положение
				значения: PHP12_radio_setup
			*/

			return _radio(array(
				'attr_id' => $attr_id,
				'light' => 1,
				'block' => !$el['num_1'],
				'interval' => 5,
				'value' => _num($v) ? _num($v) : $el['def'],
				'title0' => $el['txt_1'],
				'spisok' => _elemVvv($el['id']),
				'disabled' => $disabled
			));

		//Select - произвольные значения
		case 17:
			/*
                txt_1 - текст нулевого значения
				значения: PHP12_select_setup
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
					WHERE `element_id`=".$BL['elem_id']."
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

		//Настройка суммы значений единицы списка
		case 27: return $el['name'];

		//Список действий для Галочки [1]
		case 28: return 28;

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
				num_6 - значение по умолчанию
			*/

			if(!$UNIT_ISSET)
				if(!$v = $el['num_6'])
					$v = _spisokCmpConnectIdGet($el);

			if(is_array($v))
				$v = $v['id'];

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

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
		case 32:
			if($is_edit)
				return _elemTitle($el['id']);
			return _spisokUnitNum($unit);

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
			if($is_edit)
				return _iconEdit(array('class'=>'curD'));

			$dialog = _dialogQuery($unit['dialog_id']);

			//если редактирование запрещено, иконка не выводится
			if(!$dialog['edit_on'])
				return '';

			return
			_iconEdit(array(
				'class' => 'dialog-open pl',
				'val' => 'dialog_id:'.$unit['dialog_id'].',unit_id:'.$unit['id']
			));

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

			if($EL['dialog_id'] != 17 && $EL['dialog_id'] != 18)
				return '<div class="red">Исходный элемент не является выпадающим полем.</div>';

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $EL['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $EL['def']
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

		//Сборный текст
		case 44:
			if(!$spisok = PHP12_44_setup_vvv($el['id']))
				return '<div class="fs11 red">сборный текст не настроен</div>';

			$txt = '';
			foreach($spisok as $r) {
				$elem = _elemOne($r['id']);
				$txt .= _elemUnit($elem, $unit);
				$txt .= $r['spc'] ? ' ' : ''; //добавление пробела справа, если нужно (num_8)
			}

			return $txt;

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

		//Заметки
		case 52:
			/*
			*/
			return _note($el);

		//порядок - не доделано
		case 53:
			/*
			*/
			return 'порядок';

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

		//Меню переключения блоков
		case 57:
			/*
				num_1 - внешний вид меню:
						1158 - Маленькие синие кнопки
						1159 - С нижним подчёркиванием

				для настройки блоков используется функция PHP12_menu_block_setup
			*/

			$type = array(
				1158 => 2,
				1159 => 1
			);

			//получение пунктов меню
			$vvv = PHP12_menu_block_setup_vvv($el['id']);

			$razdel = '';
			foreach($vvv as $r) {
				$sel = _dn($el['def'] != $r['id'], 'sel');
				$razdel .= '<a class="link'.$sel.'">'.$r['title'].'</a>';
			}

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$el['def'].'" />'.
				'<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';

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
				'<div class="icon icon-del-red pl fr'._tooltip('Отменить выбор', -52).'</div>'.
				'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
			'</div>';

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

		//Фильтр - галочка
		case 62:
			/*
				txt_1 - текст для галочки
				num_1 - условие применяется:
						1439 - галочка установлена
						1440 - галочка НЕ установлена
				num_2 - id элемента, размещающего список
				значения: PHP12_filter_check_setup
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

		//Выбор цвета текста
		case 66:
			/*
			*/
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color" style="background-color:#000"></div>';

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

		//Выбор цвета фона
		case 70:
			$v = empty($v) ? '#fff' : $v;
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color-bg" style="background-color:'.$v.'"></div>';

		//Значение списка: иконка сортировки
		case 71:
			if(!$UNIT_ISSET)
				return 'sort';

			return '<div class="icon icon-move pl"></div>';

		//Фильтр - Radio
		case 74:
			/*
				num_1 - список, к которому применяется фильтр
				значения: PHP12_filter_radio_setup
			*/

			return _radio(array(
				'attr_id' => $attr_id,
				'block' => 1,
				'light' => 1,
				'interval' => 5,
				'value' => _num($v) ? _num($v) : $el['def'],
				'spisok' => _elemVvv($el['id']),
				'disabled' => $disabled
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
				txt_1 - id элемента (с учётом вложений), содержащего значения (названия), составляющие меню
				txt_2 - id элемента (с учётом вложений), содержащего количество записей по каждому пункту
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
                num_1 - список, на который воздействует фильтр
				txt_1 - нулевое значение
                txt_2 - привязанный список
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
	}

	return '<div class="fs10 red">неизвестный элемент '.$el['dialog_id'].'</div>';
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

		$send = _beBlockBg($G_BLOCK[$i1]);
		$send['elem'] = $send['elem_id'] ? $G_ELEM[$send['elem_id']] : array();

		return _arrNum($send);
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

	//получение блоков для конкретного объекта (новая схема)
	if($i == 'block_arr1') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$send = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;

			$send[$id] = _jsCacheBlockOne($id);
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
		$send['block'] = $send['block_id'] > 0 ? $G_BLOCK[$send['block_id']] : array();

		return $send;
	}

	//получение элементов для конкретного объекта с последовательным расположением
	if($i == 'elem_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			$blk[$id] = array(
				'id' => $id,
				'parent_id' => $r['parent_id'],
				'x' => $r['x'],
				'y' => $r['y']
			);
		}

		$child = array();
		foreach($blk as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		$blk = _blockArrChild($child);
		$blk = _beBlockSort($blk);

		$send = array();
		foreach($blk as $block_id) {
			$bl = $G_BLOCK[$block_id];

			if(!$elem_id = $bl['elem_id'])
				continue;

			$send[$elem_id] = _beElemVvv($G_ELEM[$elem_id]);
		}

		return $send;
	}

	//получение элементов для конкретного объекта
	if($i == 'elem_arrr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			$blk[$id] = array(
				'id' => $id,
				'parent_id' => $r['parent_id'],
				'x' => $r['x'],
				'y' => $r['y']
			);
		}

		$child = array();
		foreach($blk as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		$blk = _blockArrChild($child);

		return _beBlockSort($blk);
	}

	//получение элементов для конкретного объекта (новая схема)
	if($i == 'elem_arr1') {
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

			$send[$elem_id] = _jsCacheElemOne($elem_id);
		}

		return $send;
	}

	//массив элементов в формате JS
	if($i == 'elem_js') {
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

	//массив ID элементов в формате JS
	if($i == 'elem_ids_arr') {
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

			$send[] = $elem_id;
		}

		return $send;
	}

	if($i == 'elem_ids_js') {//массив ID элементов в формате JS
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return '[]';

		$send = array();
		foreach($G_BLOCK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			if(!$elem_id = $r['elem_id'])
				continue;

			$send[] = $elem_id;
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
		_cache_clear('ELM_HISTORY', 1);
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
		_cache_clear('ELM_HISTORY', 1);
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
	//элементы истории действий
	_beElemHistory();

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
		$block_global += _beBlockDialogDel($type, $obj_ids);
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
		$block_app += _beBlockDialogDel($type, $obj_ids);
		$block_app = _beBlockForming($block_app);
		$block_app = _beElemIdSet($block_app);

		_cache_set($key, $block_app);
	}

	$G_BLOCK += $block_app;
	_beBlockSpisok($type, $block_app);
	_beBlockElem($type, $block_app);
}
function _beBlockDialogDel($type, $obj_ids) {//добавление блоков содержания удаления
	if($type != 'dialog')
		return array();
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog_del'
			  AND `obj_id` IN (".$obj_ids.")
			ORDER BY `parent_id`,`y`,`x`";
	return query_arr($sql);
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
				  AND `obj_id` IN ("._idsGet($block, 'elem_id').")";
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
			'sa' => _num($r['sa']),
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
			'bor' => $r['bor'],
			'hidden' => _num($r['hidden']),
			'elem_id' => 0
		);
	}

	return $data;
}
function _beBlockBg($r) {
	global $G_ELEM, $G_DLG;

	$r['xx_ids'] = _idsAss($r['xx_ids']);

	//Отображение варианта цвета для динамической окраски блоков
	//Будет открываться диалог, который вносит данные списка, чтобы указать, откуда брать цвет для окраски
	//Иконка показывается, если:
	//      1. spisok-блоки. id диалога, который вносит значения списка
	//      2. dialog-блоки. id этого диалога
	//      3. page-блоки.   id диалога, который вносит значения списка, страница которой получает значения списка
	$bg70 = 0;
	if($r['obj_name'] == 'spisok')
//		if($bl = $G_BLOCK[$r['obj_id']])
//			if($el = $G_ELEM[$bl['elem_id']])
			if($el = $G_ELEM[$r['obj_id']])
				if($el['dialog_id'] == 14 || $el['dialog_id'] == 59)
					$bg70 = _num($el['num_1']);
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
		$sql = "SELECT `element_id`,1
				FROM `_element_func`
				GROUP BY `element_id`";
		$isFunc = query_ass($sql);

		//переменная для сбора ID элементов-таблиц
		$elem23 = array();

		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id` IN ("._idsGet($BLK).")";
		foreach(query_arr($sql) as $elem_id => $el) {
			if($el['dialog_id'] == 16//значения radio
			|| $el['dialog_id'] == 17//значения select
			|| $el['dialog_id'] == 18//значения dropdown
			|| $el['dialog_id'] == 23//ячейки таблиц
			|| $el['dialog_id'] == 27//значения баланса
			|| $el['dialog_id'] == 44//ячейки сборного текста
			|| $el['dialog_id'] == 57//пункты меню переключения блоков
			)
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
			$el['is_func'] = _num(@$isFunc[$elem_id]);
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
				WHERE `element_id` IN ("._idsGet($ELM).")
				ORDER BY `sort`";
		foreach(query_arr($sql) as $r) {
			$ELM[$r['element_id']]['func'][] = array(
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
function _beElemHistory() {//элементы истории действий
	global $G_DLG, $G_ELEM;

	$ids = array();
	foreach($G_DLG as $r)
		foreach(_historyAct() as $act => $act_id)
			if($r[$act.'_history_elem'])
				$ids[] = $r[$act.'_history_elem'];

	if(!$ids = implode(',', $ids))
		return;

	$key = 'ELM_HISTORY';
	if(!$arr = _cache_get($key, 1)) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id` IN (".$ids.")";
		$arr = query_arr($sql);

		_cache_set($key, $arr, 1);
	}

	$G_ELEM += $arr;
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
function _beBlockSort($BLK, $RES=array()) {//выстраивание блоков по порядку
	//составление структуры блоков по строкам
	$block = array();
	foreach($BLK as $r)
		$block[$r['y']][$r['x']] = $r;

	//выстраивание блоков по Y
	ksort($block);

	foreach($block as $y => $xx) {
		//выстраивание блоков по X
		ksort($xx);

		foreach($xx as $r) {
			$RES[] = $r['id'];
			if(!empty($r['child']))
				$RES = _beBlockSort($r['child'], $RES);
		}
	}

	return $RES;
}

















