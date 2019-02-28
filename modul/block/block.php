<?php
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
	$first_id = key($BLK);
	$FIRST = $BLK[$first_id];

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
			$cls[] = !$PARAM['blk_setup'] && _blockActionIsClick($r, $PARAM) ? 'curP' : '';
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
	    					_elemDiv($r, $PARAM).
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
			if(!$next && $widthMax && empty($PARAM['td_no_end']))
				$send .= '<td class="'.$bt.$bb.'" style="width:'.$widthMax.'px">';
		}
		$send .= '</table>'.
				 '</div>';
	}

	return $send;
}
function _blockActionIsClick($r, $prm) {//проверка действий блока, если блок кликабельный - показ руки
	if($prm['blk_setup'])
		return false;
	if(empty($r['action']))
		return false;

	foreach($r['action'] as $act)
		switch($act['dialog_id']) {
			case 211:
			case 212:
			case 213:
			case 214:
			case 215:
			case 216:
			case 217:
			case 219:
				return true;
		}

	return false;
}
function _blockAction($r, $prm) {//действие при нажатии на блок
	if($prm['blk_setup'])
		return '';
	if(empty($r['action']))
		return '';
	if(!_blockActionIsClick($r, $prm))
		return '';

	$skip = array();//номера действия, которые нужно пропустить, если они не будут соответствовать условиям
	$uid = 0;
	if($u = $prm['unit_get']) {
		$uid = $u['id'];
		foreach($r['action'] as $n => $act)
			if($v = _blockActionFilter($u, $act['filter']))
				$skip[$act['id']] = 1;
	}

	return ' onclick="_blockActionJS(this,'.$r['id'].','.$uid.','._json($skip, 0, true).')"';
}
function _blockActionFilter($u, $filter) {//дополнительные условия для действий
	if(!$filter)
		return 0;

	$filter = htmlspecialchars_decode($filter);
	//не получен массив условий (ошибка 2)
	if(!$arr = json_decode($filter, true))
		return 2;

	foreach($arr as $r) {
		if(!$ell = _elemOne($r['elem_id']))
			return 3;//отсутствует элемент (ошибка 3)
		if(!$col = $ell['col'])
			return 4;//отсутствует имя колонки (ошибка 4)

		$connect_id = $u[$col];
		if(is_array($connect_id))
			$connect_id = $u[$col]['id'];

		switch($r['cond_id']) {
			//равно
			case 3:
				if($r['unit_id'] != $connect_id)
					return 1;
				break;
			default: return 5;//условие $r['cond_id'] не доделано  (ошибка 5)
		}
	}

	return 0;
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
	if(!$arr = _BE('block_arr', $obj_name, $obj_id))
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
	if(!$arr = _BE('elem_arr', $obj_name, $obj_id))
		return '';

	foreach($arr as $r)
		if(_dialogParam($r['dialog_id'], 'element_width'))
			return '<div class="icon icon-width ml40 elem-width-change'._tooltip('Настройка ширины элементов', -79).'</div>';

	return '';
}
function _blockChooseBut($obj_name, $obj_id) {//кнопка включения выбора блоков
//	if(!$arr = _BE('block_arr', $obj_name, $obj_id))
//		return '';

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
	$send[] = _blockStyleBG($bl, $prm);

	$send = array_diff($send, array(''));

	if(empty($send))
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _blockStyleBG($bl, $prm) {//цвет фона из записи
	if($prm['blk_setup'])
		return '';
	if(!$u = $prm['unit_get'])
		return '';
	if(!$ids = _ids($bl['bg'], 'arr'))
		return '';

	$bg = $u;
	foreach($ids as $id) {
		if(!$el = _elemOne($id))
			return '';
		if(!$col = $el['col'])
			return '';
		if(!isset($bg[$col]))
			return '';

		$bg = $bg[$col];
	}

	return 'background-color:'.$bg;
}
function _blockChildHtml($block, $prm, $grid_id, $level, $width) {//деление блока на части
	if($block['id'] == $grid_id)
		return _blockGrid($block['child'], $width);

	if(!is_array($prm = _blockUnitGet($block, $prm)))
		return $prm;

	return _blockLevel($block['child'], $prm, $grid_id, $level, $width);
}
function _blockUnitGet($bl, $prm, $is_elem=false) {//блок принимает данные записи
	if($bl['elem'] && !$is_elem)
		return $prm;
	if(!$bl['action'])
		return $prm;

	foreach($bl['action'] as $act)
		switch($act['dialog_id']) {
			case 218:
				if(!$id = _num(@$_GET['id']))
					if(!$id = _num($prm['unit_get_id']))
						return _emptyMin($act['filter']);
				if(!$dialog = _dialogQuery($act['initial_id']))
					return _emptyMin('Не существует диалога, который вносит данные записи.');
				if(!$prm['unit_get'] = _spisokUnitQuery($dialog, $id))
					return _emptyMin('Записи '.$id.' не существует.');
		}

	return $prm;
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
	if(empty($el['size']))
		return '';
	if($el['size'] == 13)
		return '';
	return 'fs'.$el['size'];
}
function _elemDiv($bl, $prm=array()) {//формирование div элемента
	if(!$el = $bl['elem'])
		return '';

	$attr_id = _elemDivAttrId($el, $prm);
	$style = _elemStyle($el, $prm);

	if(!is_array($prm = _blockUnitGet($bl, $prm, true)))
		return '<div'.$attr_id.$style.'>'.$prm.'</div>';

	$txt = _elemPrint($el, $prm);

	$cls = array();
	$cls[] = _elemFormatColorDate($el, $prm, $txt);
	$cls[] = @$el['font'];
	$cls[] = _elemDivSize($el);
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormatHide($el, $txt);
	$txt = _elemFormatDigital($el, $txt);
	$txt = _spisokUnitUrl($el, $prm, $txt);

	return '<div'.$attr_id.$cls.$style.'>'.$txt.'</div>';
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
	$el['color'] = empty($el['color']) ? '' : $el['color'];

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
	//если элемент вносит данные из другого диалога - удаление данных записи, чтобы не было подстановки данных
	if($prm['unit_edit'])
		if(_elemColDlgId($el['id'], true))
			$prm['unit_edit'] = array();

	switch($el['dialog_id']) {
		//Фильтр: Select - привязанный список
		case 83:
			/*
                num_1 - id элемента-списка, на который воздействует фильтр
				txt_1 - нулевое значение
                txt_2 - id элемента (с учётом вложений) - привязанный список (через [13])
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

		//Циферка в меню страниц
		case 87:
			/*
				num_1 - id диалога: список
				txt_1 - условия [40]
			*/

			if(!$prm['blk_setup'])
				return '';
			if(!$DLG = _dialogQuery($el['num_1']))
				return _msgRed('Не получены данные диалога '.$el['num_1']);

			$sql = "SELECT COUNT(*)
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG).
						_40cond(array(), $el['txt_1']);
			$count = query_value($sql);

			return 'Кол-во "'.$DLG['name'].'" '.($count ? '+'.$count : '0');

		//Изображение
		case 90:
			/*
				txt_1 - ids изображений
				num_1 - ширина
				num_2 - разрешать высоту
				num_3 - высота
				num_4 - разрешать клик для увеличения
			*/

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
			if(!$img = query_assoc($sql))
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

		//Количество значений связанного списка с учётом категорий
		case 96:
			/*
				num_1 - id элемента: привязанный список
				txt_1 - id элемента (с учётом вложений): путь к категориям
				txt_2 - id элемента (с учётом вложений): путь к цветам
			*/
			if($prm['blk_setup'])
				return '<div class="el96-u bg-ffc mr3">8</div>'.
					   '<div class="el96-u bg-fcc">3</div>';

			if(!$u = $prm['unit_get'])
				return '';

			//ключ для конкретного элемента, по которому расположены данные в записи
			$key = 'el96_'.$el['id'];
			if(empty($u[$key]))
				return '';

			end($u[$key]);
			$end = key($u[$key]);

			$send = '';
			foreach($u[$key] as $id => $r) {
				$bg = $r['bg'] ? ' style="background-color:'.$r['bg'].'"' : '';
				$name = $r['name'] ? _tooltip($r['name'], -6, 'l') : '">';
				$mr = $id != $end ? ' mr3' : '';
				$send .= '<div'.$bg.' class="el96-u'.$mr.$name.$r['count'].'</div>';
			}

			return $send;

		//Фильтр: Выбор нескольких групп значений
		case 102:
			/*
                num_1 - id элемента: список, на который воздействует фильтр [24]
				txt_1 - нулевое значение
                txt_2 - ids элементов: привязанный список (зависит от num_1) [13]
                txt_3 - ids элементов: счётчик количеств  (зависит от num_1) [13]
                txt_4 - ids элементов: путь к цветам (зависит от num_1) [13]
				txt_5 - значение по умолчанию: настраивается через [40]
			*/

			$v = _spisokFilter('v', $el['id']);
			if($v === false) {
				$cond = _40cond($el, $el['txt_5']);
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
						$c = _num($c);
						$sel .= $c ? '<div'.$bg.' class="un'._tooltip($r['title'], -6, 'l').$c.'</div>' : '';
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

		//Пин-код
		case 130:
			$txt = 'Установить';
			$color = 'grey';
			$dlg_id = 131;
			if(_user(USER_ID, 'pin')) {
				$txt = 'Изменить';
				$color = '';
				$dlg_id = 132;
			}
			return _button(array(
						'name' => $txt.' пин-код',
						'color' => $color,
						'class' => $prm['blk_setup'] ? 'curD' : 'dialog-open',
						'val' => 'dialog_id:'.$dlg_id
					));

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

		//График: столбики
		case 400:
			/*
				txt_1 - заголовок
				num_1 - список (id диалога) [24]
			*/
			return _elem400($el, $prm);
	}

	return _element('print', $el, $prm);
}
function _elemPrintV($el, $prm, $def='') {//значение записи при редактировании
	if(!$u = $prm['unit_edit'])
		return $def;
	//установлен флаг "Всегда по умолчанию"
	if(!empty($el['nosel']))
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

	if(is_array($v)) {
		//идентификаторы изображений
		if($ids = @$v['ids'])
			return $ids;
		if($id = _num(@$v['id']))
			return $id;
		return $def;
	}

	//если текстовое поле и не число, возврат просто значения
	if($el['dialog_id'] == 8 && $el['num_1'] != 33)
		return  $v;

	if(is_string($v) && preg_match(REGEXP_INTEGER, $v) && preg_match(REGEXP_INTEGER, $def))
		return $v * 1;

	return $v;
}











function _BE($i, $i1=0, $i2=0) {//кеширование элементов приложения
	global $BE_FLAG, $G_BLOCK, $G_ELEM, $G_DLG, $_DQ;

	_beDefine();

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
		_cache_clear('BLKK');
		_cache_clear('BLKK', 1);
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
		_cache_clear('ELMM');
		_cache_clear('ELMM', 1);
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
		_cache_clear('DIALOG');
		_cache_clear('DIALOG', 1);
		_cache_clear('dialog_del_cond');
		_cache_clear('dialog_del_cond', 1);
		$_DQ = array();
		$BE_FLAG = 0;
	}

	return false;
}
function _beDefine() {//получение блоков и элементов из кеша
	global  $BE_FLAG,//флаг заполненных глобальных элементов
			$G_DLG, $G_BLOCK, $G_ELEM;

	//если флаг установлен, значит кеш был обновлён, глобальные элементы заполнены
	if($BE_FLAG)
		return;

	//диалоги
	$G_DLG = _beDlg();
	if(APP_ID)
		$G_DLG += _beDlg(APP_ID);

	//блоки
	$G_BLOCK = _beBlock();
	if(APP_ID)
		$G_BLOCK += _beBlock(APP_ID);

	//элементы
	$G_ELEM = _beElem();
	if(APP_ID)
		$G_ELEM += _beElem(APP_ID);

	$BE_FLAG = 1;
}

function _beDlg($app_id=0) {//получение данных диалогов из кеша
	$key = 'DIALOG';

	$global = $app_id ? 0 : 1;

	//глобальные диалоги
	if(!$DLG = _cache_get($key, $global)) {
		$sql = "/* CACHE DIALOG APP".$app_id." */
				SELECT *
				FROM `_dialog`
				WHERE `app_id`=".$app_id;
		if(!$DLG = query_arr($sql))
			return array();

		_cache_set($key, $DLG, $global);
	}

	$DLG = _beDlgField($DLG);
	$DLG = _beDlgDelCond($DLG, $global);

	return $DLG;
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
function _beDlgDelCond($dlg, $global) {//дополнительные условия удаления записи
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

function _beBlock($app_id=0) {//кеш блоков
	$key = 'BLKK';

	$global = $app_id ? 0 : 1;

	if(!$BLK = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return array();

		$sql = "/* CACHE BLKK APP".$app_id." */
				SELECT
					IFNULL(`el`.`id`,0) `elem_id`,
					`bl`.*
				FROM `_block` `bl`
					
					LEFT JOIN `_element` `el`
					ON `bl`.`id`=`el`.`block_id`
				   AND `el`.`block_id`
					
				WHERE `bl`.`app_id`=".$app_id."
				ORDER BY `bl`.`parent_id`,`y`,`x`";
		if(!$arr = query_arr($sql))
			return array();

		$BLK = array();
		foreach($arr as $bl) {
			$block_id = _num($bl['id']);
			$bl = _beBlockStructure($bl);
			$BLK[$block_id] = $bl;
		}

		$BLK = _beBlockAction($BLK, $app_id);

		_cache_set($key, $BLK, $global);
	}

	return $BLK;
}
function _beBlockStructure($bl) {//формирование массива блоков для кеша
	return array(
		'id' => _num($bl['id']),
		'app_id' => _num($bl['app_id']),
		'parent_id' => _num($bl['parent_id']),
		'child_count' => _num($bl['child_count']),
		'sa' => _num($bl['sa']),
		'obj_name' => $bl['obj_name'],
		'obj_id' => _num($bl['obj_id']),
		'x' => _num($bl['x']),
		'xx' => _num($bl['xx']),
		'xx_ids' => _idsAss($bl['xx_ids']),
		'y' => _num($bl['y']),
		'w' => _num($bl['w']),
		'h' => _num($bl['h']),
		'width' => _num($bl['width']),
		'width_auto' => _num($bl['width_auto']),
		'height' => _num($bl['height']),
		'pos' => $bl['pos'],
		'bg' => $bl['bg'],
		'bor' => $bl['bor'],
		'hidden' => _num($bl['hidden']),
		'action' => array(),
		'elem_id' => _num($bl['elem_id']),
		'elem' => array()
	);
}
function _beBlockAction($blk, $app_id) {//вставка действий для блоков
	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".$app_id."
			  AND `block_id`
			ORDER BY `block_id`,`sort`";
	if(!$action = query_arr($sql))
		return $blk;

	foreach($action as $r) {
		$block_id = $r['block_id'];

		if(!isset($blk[$block_id]))
			continue;

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

function _beElem($app_id=0) {
	$key = 'ELMM';

	$global = $app_id ? 0 : 1;

	if(!$ELM = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return array();

		$sql = "SELECT *
				FROM `_element`
				WHERE `app_id`=".$app_id."
				ORDER BY `id`";
		if(!$arr = query_arr($sql))
			return array();

		$ELM = array();
		foreach($arr as $el) {
			$elem_id = _num($el['id']);
			$el = _element('struct', $el);
			$el = _beElemDlg($el);

			$ELM[$elem_id] = $el;
		}

		$ELM = _beElemFormat($ELM, $app_id);
		$ELM = _beElemHint($ELM, $app_id);
		$ELM = _beElemAction($ELM, $app_id);

		_cache_set($key, $ELM, $global);
	}

	return $ELM;
}
function _beElemDlg($el) {//настройки элемента из диалогов
	global $G_DLG, $G_BLOCK;

	if(!$dialog_id = _num($el['dialog_id']))
		return $el;
	if(!$DLG = @$G_DLG[$dialog_id])
		return $el;

	$el['hidden'] = _num($DLG['element_hidden']);
	$el['afics'] = $DLG['element_afics'];

	//определение максимальной ширины, на которую может растягиваться элемент
	if($el['width_min'] = _num($DLG['element_width_min']))
		if($block_id = _num($el['block_id']))
			if($bl = @$G_BLOCK[$block_id]) {
				$ex = explode(' ', $el['mar']);
				$width_max = $bl['width'] - $ex[1] - $ex[3];
				$el['width_max'] = floor($width_max / 10) * 10;
			}

	return $el;
}
function _beElemFormat($ELM, $app_id) {//дополнительное форматирование элемента
	$sql = "SELECT *
			FROM `_element_format`
			WHERE `app_id`=".$app_id;
	foreach(query_arr($sql) as $r) {
		$elem_id = $r['element_id'];
		if(!isset($ELM[$elem_id]))
			continue;
		unset($r['id']);
		unset($r['app_id']);
		unset($r['element_id']);
		unset($r['user_id_add']);
		unset($r['dtime_add']);
		$ELM[$elem_id]['format'] = _arrNum($r);
	}

	return $ELM;
}
function _beElemHint($ELM, $app_id) {//подсказки, назначенные элементам
	$sql = "SELECT *
			FROM `_element_hint`
			WHERE `app_id`=".$app_id."
			  AND `on`
			  AND LENGTH(`msg`)";
	foreach(query_arr($sql) as $r) {
		$elem_id = $r['element_id'];
		if(!isset($ELM[$elem_id]))
			continue;
		unset($r['app_id']);
		unset($r['element_id']);
		unset($r['user_id_add']);
		unset($r['dtime_add']);
		$ELM[$elem_id]['hint'] = _arrNum($r);
	}

	return $ELM;
}
function _beElemAction($ELM, $app_id) {//действия, назначенные элементам
	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".$app_id."
			  AND `element_id`";
	foreach(query_arr($sql) as $r) {
		$elem_id = $r['element_id'];
		if(!isset($ELM[$elem_id]))
			continue;
		unset($r['id']);
		unset($r['app_id']);
		unset($r['block_id']);
		unset($r['element_id']);
		unset($r['sort']);
		unset($r['user_id_add']);
		unset($r['dtime_add']);
		$ELM[$elem_id]['action'][] = _arrNum($r);
	}

	return $ELM;
}


/*
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


		_cache_set($key, $ELM, $global);
	}

	$G_ELEM += $ELM;
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
*/












