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
function _blockName($name, $i='name', $obj_id=0) {//доступные варианты объектов для блоков
	$empty = array(
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

	if($name == 'page') {
		$pName = '';
		if($page = _page($obj_id))
			$pName = $page['name'];
		$empty['page'] = '<div class="_empty mar20">'.
							'Cтраница <b class="fs14 color-555">'.$pName.'</b> пустая и ещё не была настроена.'.
						 '</div>';
	}

	if(!isset($empty[$name]))
		return 0;

	//сообщение отсутствия блоков
	if($i == 'empty')
		return $empty[$name];

	return $name;
}
function _blockHtml($obj_name, $obj_id, $PARAM=array(), $grid_id=0) {//вывод структуры блоков для конкретного объекта
	if(!$block = _BE('block_obj', $obj_name, $obj_id))
		return _blockName($obj_name, 'empty', $obj_id);

	return _blockLevel($block, $PARAM, $grid_id);
}
function _blockParam($PARAM, $obj_name='') {//значения-параметры, формирующие настройки блоков
	//если параметры получены, настройка не нужна
	if(isset($arr['param_flag']))
		return $PARAM;

	$setup = array(
		'param_flag' => true,       //флаг полученных параметров

		'blk_setup' => 0,           //включена настройка блоков
		'blk_level' => 1,           //уровень выбираемых блоков
		'blk_choose' => 0,          //выбор блоков
		'elm_choose' => 0,          //выбор элемента
		'elm_width_change' => 0,    //изменение ширины элементов

		'unit_get_id' => 0,         //id записи для просмотра
		'unit_get' => array(),      //данные записи для просмотра
		'unit_edit' => array(),     //данные записи для редактирования

		'dop' => array()            //дополнительные параметны для некоторых диалогов
	);

	//условия для настройки блоков конкретного объекта
	if(!isset($PARAM['blk_setup']))
		if($obj_name == 'page')
			$PARAM['blk_setup'] = PAS;

	if(!empty($PARAM['blk_choose']))
		$PARAM['blk_setup'] = 1;
	if(!empty($PARAM['elm_choose']))
		$PARAM['blk_setup'] = 1;
	if(!empty($PARAM['elm_width_change']))
		$PARAM['blk_setup'] = 1;

	//исходные данные, полученные при открытии диалога
	if(!isset($PARAM['srce']))
		$PARAM['srce'] = array();

	$PARAM['srce'] += array(
		'dialog_id' => 0,
		'dss' => 0,
		'page_id' => 0,
		'block_id' => 0,
		'element_id' => 0
	);

	return $PARAM + $setup;
}
function _blockLevel($BLK, $PARAM=array(), $grid_id=0, $level=1, $WM=0) {//формирование блоков по уровням
	/*
		$BLK:       список блоков
		$PARAM:     параметры блоков. Все параметры в _blockParam
		$grid_id:   ID блока, который делится на части в конкретный момент
		$level:     уровень блоков
		$WM:        width max - максимальная ширина группы блоков
	*/
	if(empty($BLK))
		return '';

	//данные первого блока в массиве
	$firt_id = key($BLK);
	$FIRST = $BLK[$firt_id];

	$PARAM = _blockParam($PARAM, $FIRST['obj_name']);

	//если первый уровень, получение максимальной ширины всей структуры блоков
	if($level == 1)
		$WM = _blockObjWidth($FIRST['obj_name'], $FIRST['obj_id']);
	$MN = 10;//множитель
	$wMax = round($WM / $MN);

	//если дочерний уровень, получение высоты родительского блока
	$hMax = 0;  //высота родительского блока (для отображения нижней разделительной полосы при редактировании)
	if($level > 1) {
		$parent_id = $FIRST['parent_id'];
		$parent = _blockOne($parent_id);
		$hMax = $parent['h'];
	}

	//подстановка нижней линии, если блоки не доходят до низу
	$hSum = 0;

	//составление структуры блоков по строкам
	$block = array();
	foreach($BLK as $r) {
		if(!$PARAM['blk_setup']
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
	$BT = $PARAM['blk_setup'] && !$PARAM['elm_choose'] ? ' bor-t-dash' : '';
	$BR = $PARAM['blk_setup'] && !$PARAM['elm_choose'] ? ' bor-r-dash' : '';
	$BB = $PARAM['blk_setup'] && !$PARAM['elm_choose'] ? ' bor-b-dash' : '';
	$br1px = $PARAM['blk_setup'];//место в 1px для показа красной разделительной линии справа

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
		$strHide = !$PARAM['blk_setup'] && !$PARAM['elm_choose'];
		if(!$PARAM['blk_setup'] && !$PARAM['elm_choose'])
			foreach($xStr as $n => $rr)
				if(!$rr['hidden']) {//если хотя бы один блок не скрыт, вся строка не будет скрыта
					$strHide = 0;
					break;
				}

		//если блок в строке один и для него выбрана автоматическая ширина - таблица будет максимальной ширины
		$table_w100p = count($xStr) == 1 && $r['width_auto'] ? 'w100p' : '';

		//если скрывается вся строка, состоящая из одного блока, то этот блок становится как открытый TD внутри скрытого DIV
		if($strHide && count($xStr) == 1 && $r['hidden'])
			$xStr[0]['hidden'] = 0;

		$send .=
			'<div class="bl-div'._dn(!$strHide).'">'.
			'<table class="'.$table_w100p.'" style="height:'.$r['height'].'px">'.
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
			$cls[] = _dn(!(!$PARAM['blk_setup'] && !$PARAM['elm_choose'] && $r['hidden']));
			$cls[] = !$PARAM['blk_setup'] && !empty($r['action']) ? 'curP' : '';
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + ($PARAM['blk_setup'] ? 0 : $bor[1]);
			$width = $r['width'] - ($xEnd ? 0 : $br1px) - $borPx;

			//если блок списка шаблона, attr_id не ставится
			$attr_id = !$PARAM['blk_setup'] && $r['obj_name'] == 'spisok' ? '' : ' id="bl_'.$r['id'].'"';

			$send .= '<td'.$attr_id.
						' class="'.$cls.'"'.
						_blockStyle($r, $PARAM, $width).
						_blockAction($r, $PARAM).
					 '>'.
							_blockSetka($r, $PARAM, $grid_id, $level).
							_blockChoose($r, $PARAM, $level).
							_blockElemChoose($r, $PARAM).
							_blockChildHtml($r, $PARAM, $grid_id, $level + 1, $width).
	    					_elemDiv($r['elem'], $PARAM).
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
function _blockAction($r, $prm) {//действие при нажатии на блок
	if($prm['blk_setup'])
		return '';
	if(empty($r['action']))
		return '';

	$uid = 0;
	if($prm['unit_get'])
		$uid = $prm['unit_get']['id'];

	return ' onclick="_blockActionJS(this,'.$r['id'].','.$uid.')"';
}
function _blockLevelChange($obj_name, $obj_id) {//кнопки изменения уровня редактирования блоков
	$html = '';

	$arr = _blockLevelButArr($obj_name, $obj_id);
	foreach($arr as $n => $color)
		$html .= '<button class="block-level-change vk small ml5 '.$color.'">'.$n.'</button>';

	return
	'<div id="block-level-'.$obj_name.'" val="'.$obj_name.':'.$obj_id.'">'.
		'<button class="vk small grey block-grid-on">Управление блоками</button>'.
		$html.
		_blockWidthChange($obj_name, $obj_id).
		_blockChooseBut($obj_name, $obj_id).
	'</div>';
}
function _blockLevelButArr($obj_name, $obj_id) {//кнопки для изменения уровня редактирования блоков в виде массива
	if(!$arr = _BE('block_arr1', $obj_name, $obj_id))
		return array();

	$max = 1;
	$send = array();

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

	for($n = 1; $n <= $max; $n++)
		$send[$n] = $selected == $n ? 'orange' : 'cancel';

	return $send;
}
function _blockWidthChange($obj_name, $obj_id) {//кнопка изменения ширины элементов
	if(!$arr = _BE('elem_arr1', $obj_name, $obj_id))
		return '';

	foreach($arr as $r)
		if(_dialogParam($r['dialog_id'], 'element_width'))
			return '<div class="icon icon-width ml40 elem-width-change'._tooltip('Настройка ширины элементов', -79).'</div>';

	return '';
}
function _blockChooseBut($obj_name, $obj_id) {//кнопка включения выбора блоков
	if(!$arr = _BE('block_arr1', $obj_name, $obj_id))
		return '';

	return
	'<button class="vk small grey ml30 block-choose-on">'.
		'выбор блоков'.
		'<b class="ml5 fs12">0</b>'.
	'</button>';
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
function _blockSetka($bl, $prm, $grid_id, $level) {//отображение сетки для настраиваемого блока
	if(!$prm['blk_setup'])
		return '';
	//выход, если включено изменение ширины элемента
	if($prm['elm_width_change'])
		return '';
	//выход, если выбор блоков
	if($prm['blk_choose'])
		return '';
	//выход, если выбор элемента
	if($prm['elm_choose'])
		return '';
	//выход, если происходит настройка подблоков
	if($bl['id'] == $grid_id)
		return '';

	$BLD = _blockLevelDefine($bl['obj_name']);

	if($BLD != $level)
		return '';

	$BLD += $bl['obj_name'] == 'page' ? 0 : 2;

	return '<div class="block-unit level'.$BLD.' '.($grid_id ? ' grid' : '').'" val="'.$bl['id'].'"></div>';
}
function _blockChoose($bl, $prm, $level) {//подсветка блоков для выбора
	if(!$prm['blk_choose'])
		return '';
	if($prm['blk_level'] != $level)
		return '';

	$id = $bl['id'];

	//подсветка блоков, которые запрещено выбирать
	$ass = _idsAss(@$prm['blk_deny']);
	$deny = isset($ass[$id]) ? ' deny' : '';

	//подсветка выбранный блоков
	$ass = _idsAss(@$prm['blk_sel']);
	$sel = isset($ass[$id]) && !$deny ? ' sel' : '';

	return '<div class="blk-choose'.$sel.$deny.'" val="'.$id.'"></div>';
}
function _blockElemChoose($bl, $prm) {//подсветка элементов для выбора значения
	//(не)разрешён выбор значения
	if(!$prm['elm_choose'])
		return '';
	//блок не подсвечивается, если в нём нет элемента
	if(!$el = $bl['elem'])
		return '';
	//если указано какие элементы можно выбирать, проверка разрешения
	if($allow = _idsAss($prm['elm_allow']))
		if(!isset($allow[$el['dialog_id']]))
			return '';


	//отметка выбранных полей
	$id = $el['id'];
	$ass = _idsAss($prm['elm_sel']);
	$sel = isset($ass[$id]) ? ' sel' : '';

	return '<div class="elm-choose'.$sel.'" val="'.$id.'"></div>';
}
function _blockStyle($bl, $prm, $width) {//стили css для блока
	$send = array();

	//границы
	$bor = explode(' ', $bl['bor']);
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

	$send[] = ($bl['width_auto'] ? 'min-' : '').'width:'.$width.'px';

	//цвет фона из записи
	if(!$prm['blk_setup'])
		if($u = $prm['unit_get'])
			if($ids = _ids($bl['bg'], 'arr')) {
				$bg = $u;
				foreach($ids as $id) {
					if($el = _elemOne($id))
						if($col = $el['col'])
							$bg = $bg[$col];
				}
				$send[] = 'background-color:'.$bg;
			}

	$send = array_diff($send, array(''));

	if(empty($send))
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _blockChildHtml($block, $unit, $grid_id, $level, $width) {//деление блока на части
	if($block['id'] != $grid_id)
		return _blockLevel($block['child'], $unit, $grid_id, $level, $width);

	return _blockGrid($block['child'], $width);
}
function _blockGrid($arr, $width) {//режим деления на подблоки
	$spisok = '';
	foreach($arr as $r) {
		$spisok .=
		    '<div id="pb_'.$r['id'].'" class="grid-item" data-gs-x="'.$r['x'].'" data-gs-y="'.$r['y'].'" data-gs-width="'.$r['w'].'" data-gs-height="'.$r['h'].'">'.
		        '<div class="grid-info">'.$r['width'].'</div>'.
		        '<div class="grid-edge"></div>'.
		        '<div class="grid-edge er"></div>'.
				'<div class="grid-content"></div>'.
				'<div class="grid-del">x</div>'.
		    '</div>';
	}

	return
	'<div id="grid-stack" class="prel">'.
		'<div id="grid-line" style="width:'.($width-1).'px">'.
			'<span>'.$width.'</span>'.
		'</div>'.
		$spisok.
	'</div>'.
	'<div id="grid-add">Добавить блок</div> '.
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

function _elemDivAttrId($el, $prm) {//аттрибут id для DIV элемента
	//attr_id не ставится в элементе шаблона в рабочей версии
	if(!$prm['blk_setup'] && $el['block']['obj_name'] == 'spisok')
		return '';

	return ' id="el_'.$el['id'].'"';
}
function _elemDivSize($el) {//класс - размер шрифта
	if(!$el['size'])
		return '';
	if($el['size'] == 13)
		return '';
	return 'fs'.$el['size'];
}
function _elemDiv($el, $prm=array()) {//формирование div элемента
	if(!$el)
		return '';

	$txt = _elemPrint($el, $prm);

	$cls = array();
	$cls[] = _elemFormatColorDate($el, $prm, $txt);
	$cls[] = $el['font'];
	$cls[] = _elemDivSize($el);
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormatHide($el, $txt);
	$txt = _elemFormatDigital($el, $txt);
	$txt = _spisokUnitUrl($el, $prm, $txt);// шаблон

	return '<div'._elemDivAttrId($el, $prm).$cls._elemStyle($el, $prm).'>'.$txt.'</div>';
}
function _elemFormatHide($el, $txt) {//Дополнительное форматирование: скрытие при нулевом значении
	if(empty($el['format']))
		return $txt;
	if($el['format']['hide'] && empty($txt))
		return '';
	if(is_string($txt) && !preg_match(REGEXP_CENA_MINUS, $txt))
		return $txt;
	if($el['format']['hide'] && !_cena($txt, 1))
		return '';

	return $txt;
}
function _elemFormatDigital($el, $txt) {//Дополнительное форматирование для чисел
	if(is_string($txt) && !preg_match(REGEXP_CENA_MINUS, $txt))
		return $txt;
	if(empty($el['format'])) {
		if(is_string($txt))
			return $txt;
		return round($txt, 2);
	}
	if($el['format']['space'])
		$txt = _sumSpace($txt, $el['format']['fract_0_show'], $el['format']['fract_char']);
	else {
		if(!$el['format']['fract_0_show'])
			$txt = round($txt, 2);
		$txt = str_replace('.', $el['format']['fract_char'], $txt);
	}

	return $txt;
}
function _elemFormatColor($el, $txt) {//подмена цвета при дополнительном форматировании для чисел
	if(is_string($txt) && !preg_match(REGEXP_CENA_MINUS, $txt))
		return $el['color'];
	if(empty($el['format']))
		return $el['color'];

	switch($el['format']['color_cond']) {
		case 1457:
			if($txt == 0)
				return $el['format']['color_alt'];
			break;
		case 1458:
			if($txt < 0)
				return $el['format']['color_alt'];
			break;
		case 1459:
			if($txt > 0)
				return $el['format']['color_alt'];
			break;
	}

	return $el['color'];
}
function _elemFormatColorDate($el, $prm, $txt) {//подмена цвета для даты todo тестовая версия
	if($prm['blk_setup'])
		return _elemFormatColor($el, $txt);
	if($el['dialog_id'] != 86)
		return _elemFormatColor($el, $txt);
	if(!$elem_id = $el['num_1'])
		return '';
	if(!$EL = _elemOne($elem_id))
		return '';
	if(!$col = $EL['col'])
		return '';
	if(!$u = $prm['unit_get'])
		return '';
	if(!isset($u[$col]))
		return '';

	$date = substr($u[$col], 0, 10);

	if(!preg_match(REGEXP_DATE, $date))
		return '';
	if($date == '0000-00-00')
		return '';

	$day = (strtotime($date) - TODAY_UNIXTIME) / 86400;

	return _elemFormatColor($el, $day);
}
function _elemStyle($el, $prm) {//стили css для элемента
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
	if($prm['elm_width_change'] && !_dialogParam($el['dialog_id'], 'element_width'))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}

function _elemAttrId($el, $prm) {//аттрибут id для DIV элемента
	$attr_id = 'cmp_'.$el['id'];

	if($prm['blk_setup'])
		$attr_id .= '_edit';

	return $attr_id;
}
function _elemStyleWidth($el) {//ширина элемента
	if(!isset($el['width']))
		return '';
	if(!$width = _num($el['width']))
		return ' style="width:100%"';

	return ' style="width:'.$width.'px"';
}
function _elemPrint($el, $prm) {//формирование и отображение элемента
	switch($el['dialog_id']) {
		//галочка
		case 1:
			/*
				txt_1 - текст для галочки
			*/
			return _check(array(
				'attr_id' => _elemAttrId($el, $prm),
				'title' => $el['txt_1'],
				'disabled' => $prm['blk_setup'],
				'value' => _elemPrintV($el, $prm, $el['def'])
			));

		//button
		case 2: return _elemButton($el, $prm);

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
			return _menu($el, $prm['blk_setup']);

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
			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			return
			'<textarea id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.'>'.
				_elemPrintV($el, $prm).
			'</textarea>';

		//Select - выбор страницы
		case 6:
			/*
                txt_1 - текст, когда страница не выбрана
				содержание: PAGE_LIST
			*/
			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, 0)
				   ));

		//Фильтр - быстрый поиск
		case 7:
			/*
                txt_1 - текст поиска
				num_1 - id элемента, содержащего список, по которому происходит поиск
				txt_2 - по каким полям производить поиск (id элементов через запятую диалога списка)
			*/

			return _search(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'v' => _spisokFilter('vv', $el),
						'disabled' => $prm['blk_setup']
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
			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			$v = _elemPrintV($el, $prm, $el['txt_2']);

			switch($el['num_1']) {
				default:
				//любой текст
				case 32: break;
				//цифры и числа
				case 33:
					$v = round($v, $el['num_2']);
					$v = $v || $el['num_4'] ? $v : '';
					break;
			}

			return '<input type="text" id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.' value="'.$v.'" />';

		//Поле-пароль
		case 9:
			/*
				txt_1 - текст для placeholder
				num_1 - минимальное количество знаков
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			return '<input type="password" id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.' />';

		//произвольный текст
		case 10:
			/*
                txt_1 - текст
			*/
			return _br($el['txt_1']);

		//Выбор элемента из диалога (выводится окно для выбора)
		case 11:
			/*
				Вставка элемента через функцию PHP12_v_choose

				txt_2 - id элемента, выбранного из диалога, который вносит данные списка
						возможна иерархия элементов через запятую 256,1312,560
			*/

			return _elem11($el, $prm);

		//SA: Функция PHP
		case 12:
			/*
				После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.

                txt_1 - имя функции (начинается с PHP12)
			*/

			if(!$el['txt_1'])
				return _emptyMin('Отсутствует имя функции.');
			if(!function_exists($el['txt_1']))
				return _emptyRed('Фукнции <b>'.$el['txt_1'].'</b> не существует.');
			if($prm['blk_setup'])
				return _emptyMin('Функция '.$el['txt_1']);

			$prm['el12'] = $el;

			return
			($el['col'] ?
				'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm).'" />'
			: '').
				$el['txt_1']($prm);

		//Выбор элемента из диалога или страницы
		case 13:
			/*
				txt_1 - текст для placeholder
				num_1 - ID диалога (список всех диалогов)
				num_2 - разрешать выбирать только некоторые типы элементов (иначе любые)
				txt_2 - ids диалогов разрешённых элементов
				num_5 - выбор значений во вложенных списках
				num_6 - выбор нескольких значений
			*/

			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			//в самом себе выбор элемента невозможен
			if($block_id = $prm['srce']['block_id'])//должен быть блок 2214
				if($BL = _blockOne($block_id))
					if($BL['obj_name'] == 'dialog' && $BL['obj_id'] == 13)
						$disabled = ' disabled';

			$v = _elemPrintV($el, $prm);

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
			'<div class="_selem dib prel bg-fff over1" id="'._elemAttrId($el, $prm).'_selem"'._elemStyleWidth($el).'>'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
				'<input type="text" readonly class="inp curP w100p color-pay"'.$placeholder.$disabled.' value="'._elemIdsTitle($v).'" />'.
			'</div>';

		//Содержание единицы списка - шаблон
		case 14:
			if(!$dialog_id = $el['num_1'])
				return _emptyRed('Не указан список для вывода данных.');
			if(!$DLG = _dialogQuery($dialog_id))
				return _emptyRed('Списка <b>'.$dialog_id.'</b> не существует.');
			if($prm['blk_setup'])
				return _emptyMin('Список-шаблон <b>'.$DLG['name'].'</b>');

			return _spisok14($el);

		//Количество строк списка
		case 15:
			/*
                num_1 - id элемента, содержащего список, количество строк которого нужно выводить
				txt_1 "1" txt_2 - показана "1" запись
				txt_3 "2" txt_4 - показано "2" записи
				txt_5 "5" txt_6 - показано "5" записей
				txt_7 - сообщение об отсутствии записей
			*/
			return _spisokElemCount($el);

		//Radio - произвольные значения
		case 16:
			/*
				txt_1 - текст нулевого значения
				num_1 - горизонтальное положение
				значения: PHP12_radio_setup
			*/
			return _radio(array(
				'attr_id' => _elemAttrId($el, $prm),
				'light' => 1,
				'block' => !$el['num_1'],
				'interval' => 5,
				'value' => _elemPrintV($el, $prm, $el['def']),
				'title0' => $el['txt_1'],
				'spisok' => _elemVvv($el['id'], $prm),
				'disabled' => $prm['blk_setup']
			));

		//Select - произвольные значения
		case 17:
			/*
                txt_1 - текст нулевого значения
				значения: PHP12_select_setup
			*/
			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, $el['def'])
				   ));

		//Dropdown
		case 18:
			/*
                txt_1 - текст нулевого значения
				num_1 - скрывать нулевое значение в меню выбора
				num_2 - не изменять имя нулевого значения после выбора
				значения из _element через dialog_id:19
			*/
			return _dropdown(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, $el['def'])
				   ));

		//Информационный блок
		case 21:
			/*
                txt_1 - содержание
			*/
			return '<div class="_info">'._br($el['txt_1']).'</div>';

		//Дополнительные условия к фильтру (вспомогательный элемент)
		case 22:
			/*
                num_1 - id элемента, в котором нужно искать список
			*/
			return PHP12_elem22_paste($el, $prm);

		//Содержание единицы списка - таблица
		case 23:
			if($prm['blk_setup'])
				return _emptyMin('Список-таблица <b>'._dialogParam($el['num_1'], 'name').'</b>');

			return _spisok23($el);

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
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, 0)
				   ));

		//SA: Select - выбор документа
		case 26:
			/*
                txt_1 - нулевое значение
			*/
			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, 0)
				   ));

		//Настройка суммы значений единицы списка
		case 27: return $el['name'];

		//Загрузка файла
		case 28:
			/*
                txt_1 - нулевое значение
			*/

			$v = _elemPrintV($el, $prm, 0);

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
			'<div class="_attach">'.
				'<div id="'._elemAttrId($el, $prm).'_atup" class="atup'._dn(!$v).'"'._elemStyleWidth($el).'>'.
					'<form method="post" action="'.AJAX.'" enctype="multipart/form-data" target="at-frame">'.
						'<input type="hidden" name="op" value="attach_upload" />'.
						'<input type="file" name="f1" class="at-file"'._elemStyleWidth($el).' />'.// accept="' + acceptMime() + '"
					'</form>'.
					'<button class="vk small grey w100p">'.$el['txt_1'].'</button>'.
					'<iframe name="at-frame"></iframe>'.
				'</div>'.
				'<table class="atv'._dn($v).'">'.
					'<tr><td class="top">'._attachLink($v).
						'<th class="top wsnw">'.
//							'<div class="icon icon-set mtm2 ml5 pl'._tooltip('Параметры файла', -56).'</div>'.
							'<div class="icon icon-del-red ml5 mtm2 pl'._tooltip('Отменить', -30).'</div>'.
				'</table>'.
			'</div>';

		//Select - выбор единицы из другого списка
		case 29:
			/*
				Для связки одного списка с другим
				Список нельзя связывать самого с собой

                num_1 - id диалога, через который вносятся данные выбираемого списка
                txt_1 - текст, когда единица не выбрана
                txt_3 - первый id элемента, составляющие содержание Select. Выбор через [13]
                txt_4 - второй id элемента, составляющие содержание Select. Выбор через [13]
				num_2 - возможность добавления новых значений
				num_3 - поиск значений вручную
				num_4 - блокировать выбор
				num_5 - учитывать уровни
				num_6 - значение по умолчанию
			*/


			$v = _elemPrintV($el, $prm, $el['num_6']);
			$v = _elem29PageSel($el['num_1'], $v);
			$v = _elem29DialogSel($prm, $v);

			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => $v
				   ));

		//Иконка удаления записи
		case 30:
			/*
				num_1 - иконка красного цвета
			*/

			if($prm['blk_setup'])
				return _iconDel(array(
					'red' => $el['num_1'],
					'class'=>'curD'
				));
			if(!$u = $prm['unit_get'])
				return _iconDel(array(
					'red' => $el['num_1'],
					'class'=>'curD'
				));

			if(!$dlg_id = _num(@$u['dialog_id_use']))
				return _msgRed('-dlg-id-yok-');
			if(!$dlg = _dialogQuery($dlg_id))
				return _msgRed('-dlg-yok-');
			//иконка не выводится, если удаление запрещено
			if(!$dlg['del_on'])
				return '';
			//иконка не выводится, если наступили другие сутки
			if($dlg['del_cond']['num_2']) {
				$day = explode(' ', $u['dtime_add']);
				if(TODAY != $day[0])
					return '';
			}

			return
			_iconDel(array(
				'red' => $el['num_1'],
				'class' => 'dialog-open pl',
				'val' => 'dialog_id:'.$dlg_id.',del_id:'.$u['id']
			));

		//Выбор нескольких значений галочками
		case 31:
			/*
				num_1 - список, из которого будут выбираться галочки
			*/

			$v = _elemPrintV($el, $prm);

			//получение данных списка
			$DLG = _dialogQuery($el['num_1']);
			$sql = "/* ".__FUNCTION__.":".__LINE__." Выбор галочками из ".$DLG['name']." */
					SELECT "._queryCol($DLG)."
					FROM   "._queryFrom($DLG)."
					WHERE  "._queryWhere($DLG)."
					ORDER BY `sort`";
			$spisok = query_arr($sql);

			$chk = '';
			$n = 0;
			$sel = _idsAss($v);
			foreach($spisok as $r) {
				$chk .=
					'<div class="'._dn(!$n++, 'mt5').'">'.
						_check(array(
							'attr_id' => 'chk31_'.$r['num'],
							'light' => 1,
							'title' => $r['txt_1'],
							'value' => _num(@$sel[$r['num']])
						)).
					'</div>';
			}

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
			$chk;

		//Значение списка: порядковый номер
		case 32:
			if(!$u = $prm['unit_get'])
				return 'num';

			$num = empty($u['num']) ? $u['id'] : $u['num'];
			$num = _spisokColSearchBg($el, $num);
			return $num;

		//Значение записи: дата
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
			if($prm['blk_setup'])
				return 'дата';
			if(!$u = $prm['unit_get'])
				return 'дата';

			return _elem33Data($el, $u);

		//Иконка редактирования записи
		case 34:
			if($prm['blk_setup'])
				return _iconEdit(array('class'=>'curD'));
			if(!$u = $prm['unit_get'])
				return _iconEdit(array('class'=>'curD'));
			if(!$dlg_id = _num(@$u['dialog_id_use']))
				return _msgRed('-dlg-id-yok-');
			if(!$dlg = _dialogQuery($dlg_id))
				return _msgRed('-dlg-yok-');

			//иконка не выводится, если редактирование запрещено
			if(!$dlg['edit_on'])
				return '';

			return
			_iconEdit(array(
				'class' => 'dialog-open pl',
				'val' => 'dialog_id:'.$dlg_id.',edit_id:'.$u['id']
			));

		//Count - количество
		case 35:
			/*
                num_1 - варианты значений:
							3681 - диапазон значений
							3682 - конкретные значения
				num_2 - разрешать минимум
				num_3 - минимум
                num_4 - минимум может быть отрицательным
				num_5 - разрешать максимум
				num_6 - максимум
                num_7 - шаг
			*/
			return _count(array(
						'attr_id' => _elemAttrId($el, $prm),
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, $el['def'])
				   ));

		//Иконка
		case 36:
			/*
                num_1 - id иконки
                num_2 - изменять яркость при наведении мышкой
                num_3 - курсор рука при наведении, иначе стрелочка
			*/

			$type = PHP12_icon18_type($el['num_1']);
			$pl = _dn(!$el['num_2'], 'pl');
			$cur = $el['num_3'] ? ' curP' : ' curD';

			return '<div class="icon icon-'.$type.$pl.$cur.'"></div>';

		//SA: Select - выбор колонки таблицы
		case 37:
			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm)
				   ));

		//SA: Select - выбор диалогового окна
		case 38:
			/*
                txt_1 - нулевое значение
			*/
			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, 0)
				   ));

		//Сборный текст
		case 44: return PHP12_44_print($el, $prm['unit_get']);

		//Выбор блоков из диалога или страницы
		case 49:
			/*
				txt_1 - текст для placeholder

				работает в паре с [19] - окно выбора блоков
			*/

			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			$v = _elemPrintV($el, $prm);
			$ids = _ids($v);
			$count = _ids($ids, 'count');
			$title = $count ? $count.' блок'._end($count, '', 'а', 'ов') : '';

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
			'<div class="_sebl dib prel bg-fff over1" id="'._elemAttrId($el, $prm).'_sebl"'._elemStyleWidth($el).'>'.
				'<div class="icon icon-cube pabs"></div>'.
				'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
				'<input type="text" readonly class="inp curP w100p color-ref"'.$placeholder.$disabled.' value="'.$title.'" />'.
			'</div>';

		//Календарь
		case 51:
			/*
				num_1 - разрешать выбор прошедших дней
				num_2 - показывать время
			*/
			return _calendar(array(
				'attr_id' => _elemAttrId($el, $prm),
				'value' => _elemPrintV($el, $prm)
			));

		//Заметки
		case 52:
			/*
			*/
			return _note($el);

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

			$v = _elemPrintV($el, $prm, $el['def']);

			$type = array(
				1158 => 2,
				1159 => 1
			);

			$razdel = '';
			foreach(PHP12_menu_block_arr($el['id']) as $r) {
				$sel = _dn($v != $r['id'], 'sel');
				$curd = _dn(!$prm['blk_setup'], 'curD');
				$razdel .= '<a class="link'.$sel.$curd.'">'.$r['title'].'</a>';
			}

			return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
				   '<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';

		//Связка списка при помощи кнопки
		case 59:
			/*
				txt_1 - текст кнопки
                num_1 - id диалога, через который вносятся данные выбираемого списка
				num_4 - id диалога, которое открывается при нажатии на кнопку
			*/

			$v = _elemPrintV($el, $prm, 0);

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
			_button(array(
				'attr_id' => _elemAttrId($el, $prm).$el['afics'],
				'name' => $el['txt_1'],
				'color' => 'grey',
				'width' => $el['width'],
				'small' => 1,
				'class' => _dn(!$v)._dn(!$prm['blk_setup'], 'curD')
			)).
			'<div class="prel'._dn($v).'">'.
				'<div style="position:absolute;top:2px;right:3px;z-index:100" class="icon icon-del-red pl'._tooltip('Отменить выбор', -52).'</div>'.
				'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
			'</div>';

		//Загрузка изображений
		case 60:
			/*
				num_1 - максимальное количество изображений, которое разрешено загрузить
				num_7 - ограничение высоты (настройка стилей)
			*/
			if($prm['blk_setup'])
				return _emptyMin('Изображения');

			//отметка загруженных изображений как неиспользуемые, которые были не сохранены в предыдущий раз
			$sql = "UPDATE `_image`
					SET `obj_name`='elem_".$el['id']."',
						`deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `obj_name`='elem_".$el['id']."_".USER_ID."'";
			query($sql);

			$v = 0;
			$html = '';
			$del_count = 0;
			if($u = $prm['unit_edit']) {
				$sql = "SELECT *
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$u['id']."
						  AND !`deleted`
						ORDER BY `sort`";
				if($spisok = query_arr($sql))
					foreach($spisok as $r)
						$html .= _imageDD($r);

				$sql = "SELECT COUNT(*)
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$u['id']."
						  AND `deleted`";
				$del_count = query_value($sql);

				$col = $el['col'];
				$v = _num($u[$col]);
			}
			return
			'<div class="_image">'.
				'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
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

		//Фильтр: галочка
		case 62:
			/*
				txt_1 - текст для галочки
				num_1 - id элемента, размещающего список
				num_2 - условие применяется:
						1439 - галочка установлена
						1440 - галочка НЕ установлена
				num_3 - значение по умолчанию
				значения: элемент [22]
			*/

			return
			_check(array(
				'attr_id' => _elemAttrId($el, $prm),
				'title' => $el['txt_1'],
				'disabled' => $prm['blk_setup'],
				'value' => _spisokFilter('vv', $el, $el['num_3'])
			));

		//Выбор цвета текста
		case 66:
			/*
			*/
			return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm).'" />'.
				   '<div class="_color" style="background-color:#000"></div>';

		//Список истории действий
		case 68:
			/*
				num_8 - показывать только записи единицы списка, которые принимает текущая страница
			*/

			if($prm['blk_setup'])
				return _emptyMin('История действий');

			return _historySpisok($el);

		//Значение записи: имя пользователя
		case 69:
			if(!$u = $prm['unit_get'])
				return $el['name'];
			if(empty($u['user_id_add']))
				return 'no user';

			return _user($u['user_id_add'], 'name');

		//Выбор цвета фона
		case 70:
			$v = _elemPrintV($el, $prm, '#fff');

			return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
				   '<div class="_color-bg" style="background-color:'.$v.'"></div>';

		//Значение записи: иконка сортировки
		case 71: return '<div class="icon icon-move '.($prm['unit_get'] ? 'pl' : 'curD').'"></div>';

		//Фильтр: год и месяц
		case 72:
			/*
				num_1 - список, на который происходит воздействие
				num_2 - путь к сумме для подсчёта по каждому месяцу
			*/
			return _elem72Radio($el, $prm);

		//Фильтр - Radio
		case 74:
			/*
				num_1 - список, к которому применяется фильтр
				значения: PHP12_filter_radio_setup
			*/

			//получение количества значений по каждому пункту
			$EL = _elemOne($el['num_1']);
			$DLG = _dialogQuery($EL['num_1']);
			$spisok = array();
			foreach(_elemVvv($el['id'], $prm) as $id => $r) {
				$spisok[$id] = $r['txt_1'];

				if(!$r['num_1'])
					continue;

				$sql = "SELECT COUNT(*)
						FROM  "._queryFrom($DLG)."
						WHERE "._queryWhere($DLG)."
							"._22cond($id);
				if($c = query_value($sql))
					$spisok[$id] .= '<span class="fr inhr">'.$c.'</span>';
			}

			return _radio(array(
				'attr_id' => _elemAttrId($el, $prm),
				'block' => 1,
				'width' => '100%',
				'interval' => 6,
				'light' => 1,
				'value' => _spisokFilter('vv', $el, $el['def']),
				'spisok' => $spisok,
				'disabled' => $prm['blk_setup']
			));

		//Фильтр: календарь
		case 77:
			/*
				num_1 - id элемента, размещающего список
				num_2 - значение по умолчанию:
							2819 - текущий день
							2820 - текущая неделя
							2821 - текущий месяц
			*/
			return _filterCalendar($el);

		//Фильтр: меню
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
						'attr_id' => _elemAttrId($el, $prm),
						'name' => _br($el['txt_1']),
						'color' => 'red',
						'width' => $el['width'],
						'small' => 1,
						'class' => _dn($prm['blk_setup'] || $diff)._dn(!$prm['blk_setup'], 'curD')
					));

		//Фильтр: Select - привязанный список
		case 83:
			/*
                num_1 - список, на который воздействует фильтр
				txt_1 - нулевое значение
                txt_2 - привязанный список
			*/

			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _spisokFilter('vv', $el, 0)
				   ));

		//Select - выбор значения списка по умолчанию
		case 85:
			/*
                num_1 - ID элемента select, который содержит списки
                txt_1 - текст нулевого значения
				num_2 - разрешать выбор записи, данные которой принимает страница
				num_3 - разрешать выбор записи, данные которой принимает диалог
			*/

			return _select(array(
						'attr_id' => _elemAttrId($el, $prm),
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _elemPrintV($el, $prm, 0)
				   ));

		//Значение записи: количество дней
		case 86:
			/*
                num_1 - ID элемента, который указывает на дату
                txt_1 - текст "Прошёл" 1
                txt_2 - текст "Остался" 1
                txt_3 - текст "День" 1
                txt_4 - текст "Прошло" 2
                txt_5 - текст "Осталось" 2
                txt_6 - текст "Дня" 2
                txt_7 - текст "Прошло" 5
                txt_8 - текст "Осталось" 5
                txt_9 - текст "Дней" 5
                txt_10 - текст для "сегодня"
				num_2 - показывать "вчера"
				num_3 - показывать "завтра"
			*/
			if(!$u = $prm['unit_get'])
				return 'Кол-во дней';
			if(!$elem_id = $el['num_1'])
				return _msgRed('-no-elem-date');
			if(!$EL = _elemOne($elem_id))
				return _msgRed('-no-elem-'.$elem_id);
			if(!$col = $EL['col'])
				return _msgRed('-no-elem-col');
			if(!isset($u[$col]))
				return _msgRed('-no-unit-col');

			$date = substr($u[$col], 0, 10);

			if(!preg_match(REGEXP_DATE, $date))
				return _msgRed('-no-date-format');
			if($date == '0000-00-00')
				return '';

			$day = (strtotime($date) - TODAY_UNIXTIME) / 86400;

			$day_txt =
				($day > 0 ?
				_end($day, $el['txt_2'], $el['txt_5'], $el['txt_8'])
				:
				_end($day, $el['txt_1'], $el['txt_4'], $el['txt_7'])
				).
				' '.abs($day).' '.
				_end($day, $el['txt_3'], $el['txt_6'], $el['txt_9']);

			if($day == -1 && $el['num_2'])
				$day_txt = $el['txt_10'].' вчера';
			if(!$day)
				$day_txt = $el['txt_10'].' сегодня';
			if($day == 1 && $el['num_3'])
				$day_txt = $el['txt_10'].' завтра';

			return $day_txt;

		//Фильтр - Выбор нескольких групп значений
		case 102:
			/*
                num_1 - список, на который воздействует фильтр
				txt_1 - нулевое значение
                txt_2 - привязанный список (зависит от num_1)
                txt_3 - счётчик количеств  (зависит от num_1)
                txt_4 - путь к цветам (зависит от num_1)
			*/

			$v = _spisokFilter('v', $el['id']);
			if($v === false) {
				$cond = _22cond($el['id']);
				$v = _elem102CnnList($el['txt_2'], 'ids', $cond);
				_spisokFilter('insert', array(
					'spisok' => $el['num_1'],
					'filter' => $el['id'],
					'v' => $v
				));
			}

			$vAss = _idsAss($v);

			//ассоциативный массив с количествами
			$countAss = _elem102CnnList($el['txt_3'], 'ass');

			//ассоциативный массив с цветами
			$bgAss = _elem102CnnList($el['txt_4'], 'ass');

			$title = array();//ассоциативный массив с именами значений фильтра для JS
			$spisok = '';
			$sel = '';//выбранные значения
			if($arr = _elem102CnnList($el['txt_2'])) {
				$n = 0;
				$selOne = '';
				foreach($arr as $r) {
					$id = $r['id'];
					$bg = isset($bgAss[$id]) ? ' style="background-color:'.$bgAss[$id].'"' : '';
					$c = _hide0(@$countAss[$id]);
					$spisok .=
						'<tr class="over1" val="'.$r['id'].'">'.
							'<th class="w35 pad8 center"'.$bg.'>'.
								_check(array(
									'attr_id' => 'chk'.$id,
									'value' => isset($vAss[$id])
								)).
							'<td class="wsnw">'.$r['title'].
							'<td class="r fs12 grey b">'.$c;

					$title[$id] = $r['title'];

					if(isset($vAss[$id])) {
						$sel .= '<div class="un"'.$bg.'>'._num($c).'</div>';
						$selOne = '<div class="un"'.$bg.'>'.$r['title'].'</div>';
						$n++;
					}
				}
				if($n == 1)
					$sel = $selOne;
			}


			return
			'<div class="_filter102"'._elemStyleWidth($el).' id="'._elemAttrId($el, $prm).'_filter102">'.
				'<div class="holder'._dn(!$sel).'">'.$el['txt_1'].'</div>'.
				'<table class="w100p">'.
					'<tr><td class="td-un">'.($sel ? $sel : '<div class="icon icon-empty"></div>').
						'<td class="w25 top r">'.
							'<div class="icon icon-del pl'._dn($sel, 'vh')._tooltip('Очистить фильтр', -53).'</div>'.
				'</table>'.
				'<div class="list">'.
					'<table>'.$spisok.'</table>'.
				'</div>'.
			'</div>'.
			'<script>'.
				'var EL'.$el['id'].'_F102_TITLE='._json($title).','.
					'EL'.$el['id'].'_F102_C='._json($countAss).','.
					'EL'.$el['id'].'_F102_BG='._json($bgAss).';'.
			'</script>';

		//Привязка пользователя к странице ВК
		case 300:
			$vkRes = '';
			if($user_id = _elemPrintV($el, $prm, 0)) {
				$res = _vkapi('users.get', array(
					'user_ids' => $user_id,
					'fields' => 'photo,'.
								'sex,'.
								'country,'.
								'city'
				));

				if(empty($res['response']))
					$vkRes = '<div class="red">Данные из VK не получены';
				else
					$vkRes = _elem300Sel($res['response'][0]);
			}

			$disabled = $prm['blk_setup'] ? ' disabled' : '';

			return
			'<input type="hidden" id="'._elemAttrId($el, $prm).'"'.$disabled.' value="'.$user_id.'" />'.
			'<div id="'._elemAttrId($el, $prm).'_vk300" class="_vk300"'._elemStyleWidth($el).'>'.
				'<div class="icon icon-vk curD'._dn(!$user_id).'"></div>'.
				'<input type="text" class="w100p'._dn(!$user_id).'"'.$disabled.' />'.
				'<div class="vk-res">'.$vkRes.'</div>'.
			'</div>';
	}

	return _msgRed('dlg-'.$el['dialog_id']);
}
function _elemPrintV($el, $prm, $def='') {//значение записи при редактировании
	if(!$u = $prm['unit_edit'])
		return $def;
	//установлен флаг "Всегда по умолчанию"
	if($el['nosel'])
		return $def;
	if(!$col = $el['col'])
		return $def;

	//имя колонки является id элемента из родительского диалога
	if($id = _num($col)) {
		if(!$elp = _elemOne($id))
			return $def;
		if(!$col = $elp['col'])
			return $def;
	}

	$v = $u[$col];

	if(is_array($v))
		return _num($v['id']);
	if(is_string($v) && preg_match(REGEXP_INTEGER, $v))
		return $v * 1;

	return $v;
}

function _BE($i, $i1=0, $i2=0) {//кеширование элементов приложения
	global $BE_FLAG, $G_BLOCK, $G_ELEM, $G_DLG, $_DQ;

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

			$send[$id] = $r;
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

			$send[$id] = _jsCacheBlkOne($id);
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
				$r['elem'] = $el;
			}

			$blk[$id] = $r;
		}

		$child = array();
		foreach($blk as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		return _blockArrChild($child);
	}

	//получение id дочерних блоков (с учётом вложенных) для конкретного блока. Возврат: ассоциативный массив
	if($i == 'block_child_ids') {
		if(!$parent_id = _num($i1))
			return array('1');
		if(empty($G_BLOCK[$parent_id]))
			return array('2');

		$send[$parent_id] = 1;
		$to_find = true;//флаг продолжения поиска дочерних блоков
		while($to_find) {
			$to_find = false;
			foreach($send as $pid => $i)//перечисление всех найденных дочерних блоков
				foreach($G_BLOCK as $id => $r)
					if($r['parent_id'] == $pid)//блок является дочерним одного из найденных
						if(!isset($send[$id])) {
							$send[$id] = 1;
							$to_find = true;
						}
		}

		unset($send[$parent_id]);

		return $send;
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

			$send[$elem_id] = $G_ELEM[$elem_id];
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

			$send[$elem_id] = $G_ELEM[$elem_id];
		}

		return _json($send);
	}

	//массив ID элементов для конкретного объекта
	if($i == 'elem_ids_arr') {
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
		_cache_clear('dialog_del_cond');
		_cache_clear('dialog_del_cond', 1);
		$_DQ = array();
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
		$block_global = _beBlockAction($block_global);
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
		$block_app = _beBlockAction($block_app, APP_ID);
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
		$arr = _beBlockAction($arr, $global ? 0 : APP_ID);
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
			'x' => _num($r['x']),
			'xx' => _num($r['xx']),
			'xx_ids' => _idsAss($r['xx_ids']),
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
function _beBlockAction($blk, $app_id=0) {//вставка действий для блоков
	$sql = "SELECT *
			FROM `_action`
			WHERE `block_id`
			  AND `app_id`=".$app_id."
			ORDER BY `block_id`,`sort`";
	if(!$action = query_arr($sql))
		return $blk;

	foreach($action as $r) {
		$block_id = $r['block_id'];

		if(!isset($blk[$block_id]))
			continue;

		unset($r['id']);
		unset($r['app_id']);
		unset($r['block_id']);
		unset($r['element_id']);
		unset($r['sort']);
		unset($r['user_id_add']);
		unset($r['dtime_add']);

		$blk[$block_id]['action'][] = _arrNum($r);
	}

	return $blk;
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
			) $elem23[] = $elem_id;

			$dlg = $G_DLG[$el['dialog_id']];

			$el['attr_el'] = '#el_'.$elem_id;
			$el['attr_cmp'] = '#cmp_'.$elem_id;
			$el['size'] = $el['size'] ? _num($el['size']) : 13;
			$el['afics'] = $dlg['element_afics'];
			$el['hidden'] = _num($dlg['element_hidden']);

			if($el['width_min'] = _num($dlg['element_width_min'])) {
				//определение максимальной ширины, на которую может растягиваться элемент
				$ex = explode(' ', $el['mar']);
				$width_max = $BLK[$el['block_id']]['width'] - $ex[1] - $ex[3];
				$el['width_max'] = floor($width_max / 10) * 10;
			}

			$el['format'] = array();//дополнительное форматирование
			$el['func'] = array();  //привязанные функции
			$el['hint'] = array();  //выплывающая подсказка
			$el['vvv'] = array();   //значения для некоторых компонентов

			unset($el['sort']);
			unset($el['user_id_add']);
			unset($el['dtime_add']);

			$ELM[$elem_id] = _arrNum($el);
		}

		//элементы-ячейки таблиц
		if(!empty($elem23)) {
			$sql = "SELECT *
					FROM `_element`
					WHERE !`block_id`
					  AND `parent_id` IN (".implode(',', array_unique($elem23)).")";
			foreach(query_arr($sql) as $elem_id => $el) {
				unset($el['app_id']);
				unset($el['sort']);
				unset($el['user_id_add']);
				unset($el['dtime_add']);
				$ELM[$elem_id] = _arrNum($el);
			}
		}

		//дополнительное форматирование
		$sql = "SELECT *
				FROM `_element_format`
				WHERE `element_id` IN ("._idsGet($ELM).")";
		foreach(query_arr($sql) as $r) {
			$format = _arrNum($r);
			unset($format['app_id']);
			unset($format['element_id']);
			unset($format['user_id_add']);
			unset($format['dtime_add']);
			$ELM[$r['element_id']]['format'] = $format;
		}

		$sql = "SELECT *
				FROM `_action`
				WHERE `element_id` IN ("._idsGet($ELM).")
				ORDER BY `sort`";
		foreach(query_arr($sql) as $r) {
			$elid = $r['element_id'];
			unset($r['id']);
			unset($r['app_id']);
			unset($r['block_id']);
			unset($r['element_id']);
			unset($r['sort']);
			unset($r['user_id_add']);
			unset($r['dtime_add']);
			$ELM[$elid]['func'][] = _arrNum($r);
		}

		//выплывающая подсказка
		$sql = "SELECT *
				FROM `_element_hint`
				WHERE `element_id` IN ("._idsGet($ELM).")
				  AND `on`
				  AND LENGTH(`msg`)";
		foreach(query_arr($sql) as $r) {
			$hint = _arrNum($r);
			unset($hint['app_id']);
			unset($hint['element_id']);
			unset($hint['user_id_add']);
			unset($hint['dtime_add']);
			$ELM[$r['element_id']]['hint'] = $hint;
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
	$global = _beDlgDelCond($global, 1);

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
	$local = _beDlgDelCond($local);

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
		foreach(array(1) as $id) {
			$dialog[$dlg_id]['field'.$id] = array();
			$table_id = $r['table_'.$id];
			if($dialog[$dlg_id]['table_name_'.$id] = _table($table_id))
				$dialog[$dlg_id]['field'.$id] = $field[$table_id];
		}

	return $dialog;
}
function _beDlgDelCond($dlg, $global=0) {//дополнительные условия удаления записи
	if(empty($dlg))
		return array();

	foreach($dlg as $id => $r)
		$dlg[$id]['del_cond']['num_2'] = 0;

	$key = 'dialog_del_cond';
	if(!_cache_isset($key, $global)) {
		$sql = "/* ".__FUNCTION__.":".__LINE__." */
				SELECT *
				FROM `_element`
				WHERE `dialog_id`=58
				  AND `num_1` IN ("._idsGet($dlg).")
				  AND `num_2`";
		$arr = query_arr($sql);
		_cache_set($key, $arr, $global);
	} else
		$arr = _cache_get($key, $global);

	foreach($arr as $r) {
		$dlg_id = $r['num_1'];
		$dlg[$dlg_id]['del_cond']['num_2'] = _num($r['num_2']);
	}


	return $dlg;
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

















