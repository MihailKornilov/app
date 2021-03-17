<?php
function _blockName($name, $i='name', $obj_id=0) {//доступные варианты объектов для блоков
	$empty = array(
		'dialog' => '<div class="pad10">'.
						'<div class="_empty min">'.
							'Пустое содержание диалога.'.
						'</div>'.
					'</div>',

		'dialog_del' => '<div class="pad10">'.
							'<div class="_empty min">'.
								'Содержание удаления записи не настроено.'.
							'</div>'.
						'</div>',

		'spisok' =>
			'<div class="bg1 pad10">'.
				'<div class="_empty min">'.
					'Шаблон пуст.'.
					'<div class="mt10 clr2">Начните с управления блоками.</div>'.
				'</div>'.
			'</div>',

		'hint' =>
			'<div class="_empty min">'.
				'Cодержание подсказки не настроено.'.
			'</div>',

		'tmp43' =>
			'<div class="_empty min">'.
				'Cодержание шаблона записи не настроено.'.
			'</div>'
	);

	if($name == 'page') {
		$pName = '';
		if($page = _page($obj_id))
			$pName = $page['name'];
		$empty['page'] = '<div class="_empty mar20 fs17 clr8 bg14">'.
							'Cтраница <b>'.$pName.'</b> пустая и ещё не была настроена.'.
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
function _blockParam($PARAM=array(), $obj_name='') {//значения-параметры, формирующие настройки блоков
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

		'dop' => array(),           //дополнительные параметны для некоторых диалогов
		'td_no_end' => 0            //не добавлять справа пустой блок
	);

	//условия для настройки блоков конкретного объекта
	if(!isset($PARAM['blk_setup']))
		if($obj_name == 'page')
			$PARAM['blk_setup'] = PAS ? 1 : 0;

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
	$wMax = round((int)$WM / $MN);

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
	foreach($BLK as $bl) {
		if(!$PARAM['blk_setup'])
			if($el = _elemOne($bl['elem_id']))
				if(!empty($el['hidden']))
					continue;

		$bl = _blockAction201($bl, $PARAM);
		$bl = _blockAction211($bl);
		$bl = _blockAction231($bl, $PARAM);
		$bl = _blockDlgShow($bl, $PARAM);
		$bl = _element57punkt($bl, $PARAM);
		$block[$bl['y']][$bl['x']] = $bl;
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
	$br1px = $PARAM['blk_setup'] ? 1 : 0;//место в 1px для показа красной разделительной линии справа

	foreach($block as $y => $str) {
		$widthMax = $WM;

		ksort($str);//выстраивание блоков по X

		$xStr = array();
		foreach($str as $r)
			$xStr[] = $r;

		$bl0 = $xStr[0];//первый блок в строке

		$bt = $y ? $BT : '';

		$hSum += $bl0['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? $BB : '';

		//скрытие всей строки, если все блоки в строке являются скрытыми
		if($strHide = (!$PARAM['blk_setup'] && !$PARAM['elm_choose']))
			foreach($xStr as $bl)
				if(!$bl['hidden'])//если хотя бы один блок не скрыт, вся строка не будет скрыта
					$strHide = 0;

		//если скрывается вся строка, то все блоки в строке становятся как открытые TD внутри скрытого DIV
		if($strHide)
			foreach($xStr as $n => $bl)
				$xStr[$n]['hidden'] = 0;

		//если блок в строке один и для него выбрана автоматическая ширина - таблица будет максимальной ширины
		$table_w100p = count($xStr) == 1 && $bl0['width_auto'] ? 'w100p' : '';

		$send .=
			'<div class="bl-div'._dn(!$strHide).'">'.
			'<table class="'.$table_w100p.'" style="height:'.$bl0['height'].'px">'.
				'<tr>';
		//пустота в начале
		if($bl0['x']) {
			$width = $bl0['x'] * $MN - $br1px;
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

			if(!$PARAM['blk_setup'] && $ov = $r['ov'])
				$cls[] = str_replace('bg', 'ov', $ov);

			if(!$PARAM['blk_setup'] && !$PARAM['elm_choose'] && $r['hidden'])
				$cls[] = 'vh';

			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == $grid_id ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls[] = _blockActionIsClick($r, $PARAM);
			$cls[] = _blockHintOn($r, $PARAM);//применение подсказки
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + ($PARAM['blk_setup'] ? 0 : $bor[1]);
			$width = (int)$r['width'] - ($xEnd ? 0 : $br1px) - $borPx;

			//если блок списка шаблона, attr_id не ставится
			$attr_id = !$PARAM['blk_setup'] && $r['obj_name'] == 'spisok' ? '' : ' id="bl_'.$r['id'].'"';

			$send .= '<td'.$attr_id.
						' class="'.$cls.'"'.
						_blockStyle($r, $PARAM, $width).
						_blockAction($r, $PARAM).
						_blockDataHint($r, $PARAM).
					 '>'.
							_blockSetka($r, $PARAM, $grid_id, $level).
							_blockChoose($r, $PARAM, $level).
							_blockElemChoose($r, $PARAM).
							_blockChildHtml($r, $PARAM, $grid_id, $level + 1, $width).
		  (!$r['elem_id'] ? _blockAction209($r, $PARAM) : '').
	    					_elemDiv($r['elem_id'], $PARAM).
					'';

			$widthMax -= (int)$r['width'];

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
function _blockActionIsClick($bl, $prm) {//проверка действий блока, если блок кликабельный - показ руки
	if($prm['blk_setup'])
		return '';
	if(!$action =  _BE('block_one_action', $bl['id']))
		return '';

	foreach($action as $act)
		switch($act['dialog_id']) {
			case 211:
			case 212:
			case 213:
			case 214:
			case 215:
			case 216:
			case 217:
			case 219:
				return 'curP';
		}

	return '';
}
function _blockAction($bl, $prm) {//действие при нажатии на блок
	if(!_blockActionIsClick($bl, $prm))
		return '';
	if(!$action =  _BE('block_one_action', $bl['id']))
		return '';

	$skip = array();//номера действий, которые нужно пропустить, если они не будут соответствовать условиям
	$uid = 0;
	if($u = $prm['unit_get']) {
		$uid = $u['id'];
		foreach($action as $n => $act) {
			if($v = _blockActionFilter($u, $act['filter']))
				$skip[$act['id']] = $v;

			//открытие документа на печать - подмена ID, соответствующего диалогу
			if($act['dialog_id'] == 217) {
				$sql = "SELECT *
						FROM `_template`
						WHERE `app_id`=".APP_ID."
						  AND `id`="._num($act['target_ids']);
				if($doc = query_assoc($sql))
					$uid = _unitUrlId($u, $doc['spisok_id']);
			}

			//переход на страницу - подмена ID, соответствующего диалогу
			if($act['dialog_id'] == 214)
				if($page = _page($act['target_ids']))
					if($dlg_id = $page['dialog_id_unit_get'])
						$uid = _unitUrlId($u, $dlg_id);
		}
	}

	return ' onclick="_BLK_ACT(this,'.$bl['id'].','.$uid.','._json($skip, 0, true).')"';
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

		$unit_id = $u[$col];
		if(is_array($unit_id))
			$unit_id = $u[$col]['id'];

		switch($r['cond_id']) {
			//равно
			case 3:
				if($r['unit_id'] != $unit_id)
					return 1;
				break;
			default: return 5;//условие $r['cond_id'] не доделано  (ошибка 5)
		}
	}

	return 0;
}
function _blockDataHint($bl, $prm) {//аттрибут data-подсказка для блока
	if($prm['blk_setup'])
		return '';
	if(empty($bl['id']))
		return '';
	if(!$hint = _BE('hint_block_one', $bl['id']))
		return '';
	if($F = _decode($hint['filter'])) {
		if(!$u = _unitGet($prm))
			return '';
		if(!_elem40res($F, $u))
			return '';
	}

	$prm['td_no_end'] = 1;
	$hint['msg'] = _blockHtml('hint', $hint['id'], $prm);

	return ' data-hint-id="'._hintMassPush($hint).'"';
}
function _blockHintOn($bl, $prm) {
	if($prm['blk_setup'])
		return '';
	if(!$hint = _BE('hint_block_one', $bl['id']))
		return '';
	if($F = _decode($hint['filter'])) {
		if(!$u = _unitGet($prm))
			return '';
		if(!_elem40res($F, $u))
			return '';
	}

	return 'hint-on';
}
function _blockDlgShow($bl, $prm) {//отображение блока при создании или изменении диалога
	if($bl['hidden'])
		return $bl;
	if($bl['obj_name'] != 'dialog')
		return $bl;

	if(empty($prm['unit_edit']) && !$bl['show_create'])
		$bl['hidden'] = 1;
	if(!empty($prm['unit_edit']) && !$bl['show_edit'])
		$bl['hidden'] = 1;

	return $bl;
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
		'<div class="icon icon-width ml40 elem-width-change tool '._blockWidthChangeOn($obj_name, $obj_id).'" data-tool="Настройка ширины элементов"></div>'.
		_blockChooseBut($obj_name, $obj_id).
	'</div>';
}
function _blockLevelPageEdit() {//отображение иконки редактирования страницы
	if(!$page_id = _page('cur'))
		return '';
	if(!$page = _page($page_id))
		return '';

	return '<div val="dialog_id:'.$page['dialog_id'].',edit_id:'.$page_id.'" class="icon icon-edit ml10 dialog-open tool-l" data-tool="Редактировать страницу"></div>';
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
function _blockWidthChangeOn($obj_name, $obj_id) {//отображение кнопки изменения ширины элементов
	if(!$arr = _BE('elem_arr', $obj_name, $obj_id))
		return 'dn';

	foreach($arr as $r)
		if(_dialogParam($r['dialog_id'], 'element_width'))
			return '';

	return 'dn';
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
function _blockLevelDefine($obj_name, $v=0) {//уровень редактируемых блоков
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
	if(!$el = _elemOne($bl['elem_id']))
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

	$send[] = ($bl['width_auto'] ? 'min-' : '').'width:'.$width.'px';

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

	$bg = '';
	if($ids = _ids($bl['bg'], 'arr')) {
		$bg = $u;
		foreach($ids as $id) {
			if(!$col = _elemCol($id))
				return '';
			if(!isset($bg[$col]))
				return '';

			$bg = $bg[$col];
		}
	}

	if(!$bg = _blockAction232($bl, $prm, $bg))
		return '';

	return 'background-color:'.$bg;
}
function _blockChildHtml($block, $prm, $grid_id, $level, $width) {//деление блока на части
	if($block['id'] == $grid_id) {
		$block['child'] = _blockGridIn($block['child']);
		return _blockGrid($block['child'], $width);
	}

	if(!$prm['blk_setup'])
		if(!is_array($prm = _blockUnitGet($block, $prm)))
			return $prm;

	return _blockLevel($block['child'], $prm, $grid_id, $level, $width);
}
function _blockUnitGet($bl, $prm, $is_elem=false) {//блок принимает данные записи
	if($bl['elem_id'] && !$is_elem)
		return $prm;
	if(!$action = _BE('block_one_action', $bl['id']))
		return $prm;

	foreach($action as $act)
		switch($act['dialog_id']) {
			case 218:
				if(!$id = _num(@$_GET['id']))
					if(!$id = _num($prm['unit_get_id']))
						return _emptyMin($act['v1']);
				if(!$dialog = _dialogQuery($act['initial_id']))
					return _emptyMin('Не существует диалога, который вносит данные записи.');
				if(!$prm['unit_get'] = _spisokUnitQuery($dialog, $id))
					return _emptyMin('Записи '.$id.' не существует.');
		}

	return $prm;
}
function _blockGridIn($arr) {//подстановка флагов наличия внутри блока элемента или дочерних блоков
	if(empty($arr))
		return array();

	foreach($arr as $id => $r) {
		$arr[$id]['blin'] = 0;  //в блоке присутствуют дочерние блоки
		$arr[$id]['blwmin'] = 0;//ограничивать минимальную ширину блока (по ширине дочерних блоков)
		$arr[$id]['elin'] = '';  //в блоке присутствует элемент
	}

	$sql = "SELECT *
			FROM `_block`
			WHERE `parent_id` IN ("._idsGet($arr).")";
	foreach(query_arr($sql) as $r) {
		$id = $r['parent_id'];
		$arr[$id]['blin'] = 1;
		$w = $r['x'] + $r['w'];
		if($arr[$id]['blwmin'] < $w)
			$arr[$id]['blwmin'] = $w;
	}

	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	foreach(query_arr($sql) as $r) {
		$id = $r['block_id'];
		$title = _element('title', $r);
		$DLG = _dialogQuery($r['dialog_id']);
		$arr[$id]['elin'] =
			(DEBUG ? '['.$r['dialog_id'].'] ' : '').
			$DLG['name'].
			($title ? '<br><b>'.$title.'</b>' : '');
	}

	return $arr;
}
function _blockGrid($arr, $width) {//режим деления на подблоки
	$spisok = '';
	foreach($arr as $r) {
		$blIn = $r['blin'] ? ' blin' : '';
		$elIn = $r['elin'] ? ' elin' : '';
		$prc = floor($r['height'] / 100) * 10;//разница 1% от grid-высоты
		$height = $r['height'] + $prc;
		$spisok .=
		    '<div id="pb_'.$r['id'].'"'.
		        ' class="grid-item"'.
		        ' data-gs-x="'.$r['x'].'"'.
		        ' data-gs-y="'.$r['y'].'"'.
		        ' data-gs-width="'.$r['w'].'"'.
		        ' data-gs-height="'.$r['h'].'"'.
($r['blwmin'] ? ' data-gs-min-width="'.$r['blwmin'].'"' : '').
		    '>'.
			        '<div class="grid-size-x">'.$r['width'].'</div>'.
			        '<div class="grid-size-y">'.$height.'</div>'.
			        '<div class="grid-edge"></div>'.
			        '<div class="grid-edge er"></div>'.
					'<div class="grid-content'.$blIn.$elIn.'">'.
		      ($elIn ? '<table>'.
		                    '<tr><td>'.$r['elin'].
		                '</table>'
			  : '').
		            '</div>'.
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
		case 'tmp43':
		case 'spisok':
			//получение элемента, который содержит список (для корректировки ширины с отступами)
			if(!$elm14 = _elemOne($obj_id))
				return 0;
			if(!$bl = _blockOne($elm14['block_id']))
				return 0;
			$ex = explode(' ', $elm14['mar']);
			return floor(($bl['width'] - $ex[1] - $ex[3]) / 10) * 10;
		case 'hint': return 500;
	}
	return 0;
}
function _blockDlgId($block_id, $obj_name='') {//получение id диалога по блоку
	if(!$BL = _BE('block_one', $block_id))
		return 0;
	if($obj_name && $obj_name != $BL['obj_name'])
		return 0;

	switch($BL['obj_name']) {
		case 'page':
			if(!$p = _page($BL['obj_id']))
				break;
			return $p['dialog_id_unit_get'];
		case 'dialog': return $BL['obj_id'];
		case 'spisok': return _elemDlgId($BL['obj_id']);
		case 'hint': return _hintDlgId($BL);
	}

	return 0;
}
function _blockSort($BLK, $RES=array()) {//выстраивание блоков по порядку
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
				$RES = _blockSort($r['child'], $RES);
		}
	}

	return $RES;
}











function _BE($i, $i1=0, $i2=0) {//кеширование элементов приложения
	global $G_DLG, $G_BLK, $G_ELM, $G_ACT, $G_HINT,
	       $_DQ;//хранение собранных диалогов из _dialogQuery

	_beDefine();

	//получение данных одного блока
	if($i == 'block_one') {
		//ID блока
		if(!$i1)
			return array();
		if(!isset($G_BLK[$i1]))
			return array();

		return _arrNum($G_BLK[$i1]);
	}

	//действия, прикреплённые к блоку
	if($i == 'block_one_action') {
		if(empty($G_ACT['bl'][$i1]))
			return array();
		return $G_ACT['bl'][$i1];
	}

	//получение блоков для конкретного объекта
	if($i == 'block_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$send = array();
		foreach($G_BLK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;

			$r['action'] = isset($G_ACT['bl'][$id]) ? $G_ACT['bl'][$id] : array();
			$send[$id] = _arrNum($r);
			$send = _BE('block_spisok_14', $r, $send);
			$send = _BE('block_hint', $r, $send);
		}

		return $send;
	}

	//получение данных блоков списка-шаблона, если этот список вставлен блок
	if($i == 'block_spisok_14') {
		$bl = $i1;
		$send = $i2;

		if(!$elem_id = $bl['elem_id'])
			return $send;
		if(!isset($G_ELM[$elem_id]))
			return $send;
		if($G_ELM[$elem_id]['dialog_id'] != 14)
			return $send;

		foreach($G_BLK as $id => $r) {
			if($r['obj_name'] != 'spisok')
				continue;
			if($r['obj_id'] != $elem_id)
				continue;

			$r['action'] = isset($G_ACT['bl'][$id]) ? $G_ACT['bl'][$id] : array();
			$send[$id] = _arrNum($r);
		}

		return $send;
	}

	//получение данных блоков подсказок, если к данному блоку или элементу в нём прикреплена подсказка
	if($i == 'block_hint') {
		$bl = $i1;
		$send = $i2;
		$block_id = $bl['id'];

		//подсказки к блокам
		if(!empty($G_HINT['bl'][$block_id])) {
			$hint = $G_HINT['bl'][$block_id];

			foreach($G_BLK as $id => $r) {
				if($r['obj_name'] != 'hint')
					continue;
				if($r['obj_id'] != $hint['id'])
					continue;

				$r['action'] = isset($G_ACT['bl'][$id]) ? $G_ACT['bl'][$id] : array();
				$send[$id] = _arrNum($r);
			}
		}


		//подсказки к элементам
		if($elem_id = $bl['elem_id'])
			if(!empty($G_HINT['el'][$elem_id])) {
				$hint = $G_HINT['el'][$elem_id];

				foreach($G_BLK as $id => $r) {
					if($r['obj_name'] != 'hint')
						continue;
					if($r['obj_id'] != $hint['id'])
						continue;

					$r['action'] = isset($G_ACT['bl'][$id]) ? $G_ACT['bl'][$id] : array();
					$send[$id] = _arrNum($r);
				}
			}

		return $send;
	}

	//получение блоков для конкретного объекта c учётом иерархии
	if($i == 'block_obj') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLK as $id => $r) {
			if($r['obj_name'] != $obj_name)
				continue;
			if($r['obj_id'] != $obj_id)
				continue;
			$blk[$id] = $r;
		}

		return _arrChild($blk);
	}

	//получение id дочерних блоков (с учётом вложенных) для конкретного блока. Возврат: ассоциативный массив
	if($i == 'block_child_ids') {
		if(!$parent_id = _num($i1))
			return array('1');
		if(empty($G_BLK[$parent_id]))
			return array('2');

		$send[$parent_id] = 1;
		$to_find = true;//флаг продолжения поиска дочерних блоков
		while($to_find) {
			$to_find = false;
			foreach($send as $pid => $i)//перечисление всех найденных дочерних блоков
				foreach($G_BLK as $id => $r)
					if($r['parent_id'] == $pid)//блок является дочерним одного из найденных
						if(!isset($send[$id])) {
							$send[$id] = 1;
							$to_find = true;
						}
		}

		unset($send[$parent_id]);

		return $send;
	}

	if($i == 'block_level') {//получение уровня, на котором находится блок
		$level = 0;
		while($bl = @$G_BLK[$i1]) {
			$i1 = $bl['parent_id'];
			$level++;
		}
		return $level;
	}

	//очистка кеша блоков
	if($i == 'block_clear') {
		_cache_clear('GBLK');
		_cache_clear('GBLK', 1);
		_beBlkCache();
	}





	//получение данных всех элементов
	if($i == 'elem_all')
		return $G_ELM;

	//получение данных одного элемента
	if($i == 'elem_one') {
		//ID элемента
		if(!$i1)
			return array();
		if(!isset($G_ELM[$i1]))
			return array();
		if($G_ELM[$i1]['dialog_id'])
			$G_ELM[$i1]['title'] = _element('title', $G_ELM[$i1]);
		return $G_ELM[$i1];
	}

	//действия, прикреплённые к элементу
	if($i == 'elem_one_action') {
		if(empty($G_ACT['el'][$i1]))
			return array();
		return $G_ACT['el'][$i1];
	}

	//получение элементов для конкретного объекта с последовательным расположением
	if($i == 'elem_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLK as $id => $r) {
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

		$blk = _arrChild($blk);
		$blk = _blockSort($blk);

		$send = array();
		foreach($blk as $block_id) {
			$bl = $G_BLK[$block_id];

			if(!$elem_id = $bl['elem_id'])
				continue;
			if(!isset($G_ELM[$elem_id]))
				continue;

			$el = $G_ELM[$elem_id];
			$el['action'] = isset($G_ACT['el'][$elem_id]) ? $G_ACT['el'][$elem_id] : array();
			$send[$elem_id] = $el;
		}

		return $send;
	}

	//массив ID элементов для конкретного объекта с последовательным расположением
	if($i == 'elem_ids_arr') {
		$obj_name = $i1;
		if(!$obj_id = _num($i2))
			return array();

		$blk = array();
		foreach($G_BLK as $id => $r) {
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

		$blk = _arrChild($blk);
		$blk = _blockSort($blk);

		$send = array();
		foreach($blk as $block_id) {
			$bl = $G_BLK[$block_id];

			if(!$elem_id = $bl['elem_id'])
				continue;

			$send[] = $elem_id;
		}

		return $send;
	}

	//очистка кеша элементов
	if($i == 'elem_clear') {
		_cache_clear('GELM');
		_cache_clear('GELM', 1);
		_beElmCache();
	}




	//получение данных об одной подсказке по id
	if($i == 'hint_one') {
		if(empty($G_HINT['ht'][$i1]))
			return array();
		return $G_HINT['ht'][$i1];
	}

	//получение данных о подсказке для элемента
	if($i == 'hint_elem_one') {
		if(empty($G_HINT['el'][$i1]))
			return array();
		return $G_HINT['el'][$i1];
	}

	//получение данных о подсказке для блока
	if($i == 'hint_block_one') {
		if(empty($G_HINT['bl'][$i1]))
			return array();
		return $G_HINT['bl'][$i1];
	}

	//очистка кеша действий
	if($i == 'hint_clear') {
		_cache_clear('HINT');
		_cache_clear('HINT', 1);
		_beHintCache();
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
		$_DQ = array();
		_beDlgCache();
	}




	//получение данных об одном действии по id
	if($i == 'action_one') {
		if(empty($G_ACT['act'][$i1]))
			return array();
		return $G_ACT['act'][$i1];
	}

	//очистка кеша действий
	if($i == 'action_clear') {
		_cache_clear('ACTION');
		_cache_clear('ACTION', 1);
		_beActCache();
	}

	return false;
}
function _beDefine() {//получение блоков и элементов из кеша
	//если флаг установлен, значит кеш был обновлён, глобальные элементы заполнены
	if(_flag('BE'))
		return;

	_beDlgCache();
	_beBlkCache();
	_beElmCache();
	_beActCache();
	_beHintCache();
}

function _beDlgCache() {//кеширование диалогов
	global $G_DLG;

	$G_DLG = array();

	//если функция вызывается повторно, очищаются флаги
	_flag('DLG_APP0', true);
	_flag('DLG_APP'.APP_PARENT, true);

	_beDlg();
	_beDlg(APP_PARENT);
}
function _beDlg($app_id=0) {//получение данных диалогов из кеша
	if(_flag('DLG_APP'.$app_id))
		return;

	global $G_DLG;

	$key = 'DIALOG';
	$global = $app_id ? 0 : 1;

	if(!$DLG = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return;

		$sql = "/* CACHE DIALOG APP".$app_id." */
				SELECT *
				FROM `_dialog`
				WHERE `app_id`=".$app_id;
		if(!$DLG = query_arr($sql))
			return;

		foreach($DLG as $id => $r) {
			unset($DLG[$id]['user_id_add']);
			unset($DLG[$id]['dtime_add']);
		}

		$DLG = _beDlgDelCond($DLG);
		_cache_set($key, $DLG, $global);
	}

	$DLG = _beDlgField($DLG);

	$G_DLG += $DLG;
}
function _beDlgField($DLG) {//вставка колонок таблиц в диалоги
	//список колонок, присутствующих в таблицах
	foreach($DLG as $id => $r) {
		$DLG[$id]['field1'] = array();
		$table_id = $r['table_1'];
		if($DLG[$id]['table_name_1'] = _table($table_id))
			$DLG[$id]['field1'] = _field($table_id);
	}

	return $DLG;
}
function _beDlgDelCond($DLG) {//дополнительные условия удаления записи
	if(empty($DLG))
		return array();

	foreach($DLG as $id => $r)
		$DLG[$id]['del_cond']['num_2'] = 0;

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=58
			  AND `num_1` IN ("._idsGet($DLG).")
			  AND `num_2`";
	if(!$arr = query_arr($sql))
		return $DLG;

	foreach($arr as $r) {
		if(!$dlg_id = $r['num_1'])
			continue;
		if(empty($DLG[$dlg_id]))
			continue;

		$DLG[$dlg_id]['del_cond']['num_2'] = 1;
	}

	return $DLG;
}

function _beBlkCache() {//кеш блоков
	global $G_BLK;

	$G_BLK = array();

	//если функция вызывается повторно, очищаются флаги
	_flag('BLK_APP0', true);
	_flag('BLK_APP'.APP_PARENT, true);

	_beBlk();
	_beBlk(APP_PARENT);
}
function _beBlk($app_id=0) {//кеш блоков
	if(_flag('BLK_APP'.$app_id))
		return;

	global $G_BLK;

	$key = 'GBLK';
	$global = $app_id ? 0 : 1;

	if(!$BLK = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return;

		$sql = "/* CACHE BLK APP".$app_id." */
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
			return;

		$BLK = array();
		foreach($arr as $bl)
			$BLK[$bl['id']] = _beBlkStruct($bl);

		_cache_set($key, $BLK, $global);
	}

	$G_BLK += $BLK;
}
function _beBlkStruct($bl) {//формирование массива блоков для кеша
	return array(
		'id' => _num($bl['id']),
		'app_id' => _num($bl['app_id']),
		'parent_id' => _num($bl['parent_id']),
		'child_count' => _num($bl['child_count']),
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
		'ov' => $bl['ov'],
		'bor' => $bl['bor'],
		'hidden' => _num($bl['hidden']),
		'show_create' => _num($bl['show_create']),
		'show_edit' => _num($bl['show_edit']),
		'elem_id' => _num($bl['elem_id'])
	);
}

function _beElmCache() {//кеш элементов
	global $G_ELM;

	$G_ELM = array();

	//если функция вызывается повторно, очищаются флаги
	_flag('ELM_APP0', true);
	_flag('ELM_APP'.APP_PARENT, true);

	_beElm();
	_beElm(APP_PARENT);

	foreach($G_ELM as $elem_id => $el)
		$G_ELM[$elem_id] = _beElmStruct11($el);
}
function _beElm($app_id=0) {
	if(_flag('ELM_APP'.$app_id))
		return;

	global $G_ELM;

	$key = 'GELM';
	$global = $app_id ? 0 : 1;

	if(!$ELM = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return;

		//получение всех элементов
		$sql = "SELECT *
				FROM `_element`
				WHERE `app_id`=".$app_id."
				ORDER BY `id`";
		if(!$arr = query_arr($sql))
			return;

		$ELM = array();
		$elm11 = array();
		foreach($arr as $elem_id => $el) {
			switch($el['dialog_id']) {
				case 0:
					unset($el['user_id_add']);
					unset($el['dtime_add']);
					break;
				default:
					$el = _beElmDlg($el);
					$el = _element('struct', $el);
					if($el['dialog_id'] == 11)
						$elm11[$elem_id] = $el;
			}
			$ELM[$elem_id] = $el;
		}

		_cache_set($key, $ELM, $global);
	}

	$G_ELM += $ELM;
}
function _beElmDlg($el) {//настройки элемента из диалогов
	global $G_DLG, $G_BLK;

	if(!$dialog_id = _num($el['dialog_id']))
		return $el;
	if($dialog_id == 11)
		return $el;
	if(!$DLG = @$G_DLG[$dialog_id])
		return $el;
	if(empty($DLG['element_group_id']))
		return $el;

	$el['hidden'] = _num($DLG['element_hidden']);
	$el['afics'] = $DLG['element_afics'];

	//определение максимальной ширины, на которую может растягиваться элемент
	if($el['width_min'] = _num(@$DLG['element_width_min']))
		if($block_id = _num($el['block_id']))
			if($bl = @$G_BLK[$block_id])
				$el['width_max'] = floor(_elemWidth($el) / 10) * 10;

	return $el;
}
function _beElmStruct11($el11) {
	global $G_ELM;

	if($el11['dialog_id'] != 11)
		return $el11;
	if(!$last_id = _idsLast($el11['txt_2']))
		return $el11;
	if(empty($G_ELM[$last_id])) {
		if($last_id < 0)
			$el11['stl'] = 1;
		return $el11;
	}

	$el = $G_ELM[$last_id];

	//разрешать настройку стилей, если элемент вставлен через [11]
	if(_elemRule($el['dialog_id'], 11))
		$el11['stl'] = 1; //для JS

	//разрешать настройку условий отображения, если элемент вставлен через [11]
	if(_elemRule($el['dialog_id'], 14))
		$el11['eye'] = 1;

	//является изоображением, вставленным через [11]
	if($el['dialog_id'] == 60)
		$el11['immg'] = 1;

	return $el11;
}

function _beActCache() {//кеш действий
	global $G_ACT;

	$G_ACT = array(
		'bl' => array(),//действия, прикреплённые к блокам
		'el' => array(),//действия, прикреплённые к элементам
		'act' => array()//все действия
	);

	//если функция вызывается повторно, очищаются флаги
	_flag('ACT_APP0', true);
	_flag('ACT_APP'.APP_PARENT, true);

	_beAct();
	_beAct(APP_PARENT);
}
function _beAct($app_id=0) {//кеш действий
	if(_flag('ACT_APP'.$app_id))
		return;

	global $G_ACT;

	$key = 'ACTION';
	$global = $app_id ? 0 : 1;

	if(!$ACT = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return;

		$ACT = array();
		$sql = "SELECT *
				FROM `_action`
				WHERE `app_id`=".$app_id."
				  AND `dialog_id` NOT IN (229) /* За исключением подсказок */
				ORDER BY `sort`";
		if($arr = query_arr($sql))
			foreach($arr as $id => $r) {
				unset($r['app_id']);
				unset($r['sort']);
				unset($r['user_id_add']);
				unset($r['dtime_add']);
				$ACT[$id] = _arrNum($r);
			}

		_cache_set($key, $ACT, $global);
	}

	foreach($ACT as $id => $r) {
		$block_id = _num($r['block_id']);
		$elem_id = _num($r['element_id']);

		$G_ACT['act'][$id] = $r;

		unset($r['block_id']);
		unset($r['element_id']);

		if($block_id)
			$G_ACT['bl'][$block_id][] = $r;
		if($elem_id)
			$G_ACT['el'][$elem_id][] = $r;
	}

//	$action = _beBlockAction212($app_id, $action);
//	$action = _beBlockAction215($app_id, $action);
}

function _beHintCache() {//кеш действий
	global $G_HINT;

	$G_HINT = array(
		'el' => array(),//подсказки, прикреплённые к элементам
		'bl' => array(),//подсказки, прикреплённые к блокам
		'ht' => array() //все подсказки
	);

	//если функция вызывается повторно, очищаются флаги
	_flag('HINT_APP0', true);
	_flag('HINT_APP'.APP_PARENT, true);

	_beHint();
	_beHint(APP_PARENT);
}
function _beHint($app_id=0) {//подсказки
	if(_flag('HINT_APP'.$app_id))
		return;

	global $G_HINT;

	$key = 'HINT';
	$global = $app_id ? 0 : 1;

	if(!$HINT = _cache_get($key, $global)) {

		if(_cache_isset($key, $global))
			return;

		$HINT = array();
		$sql = "/* CACHE HINT APP".$app_id." */
				SELECT *
				FROM `_action`
				WHERE `app_id`=".$app_id."
				  AND `dialog_id`=229";
		if($arr = query_arr($sql))
			foreach($arr as $id => $r) {
				unset($r['app_id']);
				unset($r['sort']);
				unset($r['user_id_add']);
				unset($r['dtime_add']);
				$HINT[$id] = $r;
			}

		_cache_set($key, $HINT, $global);
	}

	foreach($HINT as $id => $r) {
		$H = array(
			'id' => $id,
			'side' => _num($r['initial_id']),
			'pos_h' => _num($r['apply_id']),
			'pos_v' => _num($r['effect_id']),
			'ug_h' => _num($r['target_ids']),
			'ug_v' => _num($r['revers']),
			'delay_show' => _num($r['v1']),
			'delay_hide' => _num($r['v2']),
			'filter' => $r['filter']
		);

		if($block_id = _num($r['block_id']))
			$G_HINT['bl'][$block_id] = $H;
		if($elem_id = _num($r['element_id']))
			$G_HINT['el'][$elem_id] = $H;

		$H['block_id'] = $block_id;
		$H['element_id'] = $elem_id;
		$G_HINT['ht'][$id] = $H;
	}
}




















/*
function _beBlockAction212($app_id, $action) {//действие 212: подмена значений для приложений-копий
	if(!$app_id)
		return $action;
	if(!_app(APP_ID, 'pid'))
		return $action;

	$ids = array();
	foreach($action as $r)
		if($r['dialog_id'] == 212)
			$ids[] = $r['apply_id'];

	if(empty($ids))
		return $action;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `id_old` IN (".implode(',', $ids).")";
	if(!$ass = query_ass($sql))
		return $action;

	foreach($action as $id => $r)
		if($r['dialog_id'] == 212) {
			$apply_id = $r['apply_id'];
			if(isset($ass[$apply_id]))
				$action[$id]['apply_id'] = $ass[$apply_id];
		}

	return $action;
}
function _beBlockAction215($app_id, $action) {//действие 215: подмена значений для приложений-копий
	if(!$app_id)
		return $action;
	if(!_app(APP_ID, 'pid'))
		return $action;

	$ids = array();
	foreach($action as $r) {
		if($r['dialog_id'] != 215)
			continue;
		if(!$r['filter'])
			continue;

		$filter = htmlspecialchars_decode($r['filter']);
		if(!$F = json_decode($filter, true))
			continue;

		foreach($F as $ff) {
			if(!$unit_id = _num($ff['unit_id']))
				continue;
			$ids[$unit_id] = 1;
		}
	}

	if(empty($ids))
		return $action;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `id_old` IN ("._idsGet($ids, 'key').")";
	if(!$ass = query_ass($sql))
		return $action;

	foreach($action as $id => $r) {
		if($r['dialog_id'] != 215)
			continue;
		if(!$r['filter'])
			continue;

		$filter = htmlspecialchars_decode($r['filter']);
		if(!$F = json_decode($filter, true))
			continue;

		foreach($F as $fid => $ff) {
			if(!$unit_id = _num($ff['unit_id']))
				continue;
			if(!isset($ass[$unit_id]))
				continue;
			$F[$fid]['unit_id'] = $ass[$unit_id];
		}

		$action[$id]['filter'] = json_encode($F);
	}

	return $action;
}
*/





function PHP12_block_info($prm) {//информация о блоке (диалог 117)
	if(!$block_id = _num($prm['unit_get_id']))
		return _emptyRed('Данные блока не получены');

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$block_id;
	if(!$BL = query_assoc($sql))
		return _emptyRed('Блока id'.$block_id.' нет в базе');

	$BLCH = _blockOne($block_id);

	$send =
		'<table class="bs5">'.
			'<tr><td class="clr1">ID блока:'.
				'<td><input type="text" class="w100" value="'.$block_id.'">'.
		'</table>'.

		'<table class="_stab small mt10">'.
			'<tr><th>Параметр'.
				'<th>База'.
				'<th>Кеш'.

			_blockInfoTr('app_id', $BL, $BLCH).
			_blockInfoTr('parent_id', $BL, $BLCH).
			_blockInfoTr('child_count', $BL, $BLCH).
			_blockInfoTr('obj_name', $BL, $BLCH).
			_blockInfoTr('obj_id', $BL, $BLCH).
			_blockInfoTr('x', $BL, $BLCH).
			_blockInfoTr('xx', $BL, $BLCH).
			_blockInfoTr('xx_ids', $BL, $BLCH).
			_blockInfoTr('y', $BL, $BLCH).
			_blockInfoTr('w', $BL, $BLCH).
			_blockInfoTr('h', $BL, $BLCH).
			_blockInfoTr('width', $BL, $BLCH).
			_blockInfoTr('width_auto', $BL, $BLCH).
			_blockInfoTr('height', $BL, $BLCH).
			_blockInfoTr('pos', $BL, $BLCH).
			_blockInfoTr('bg', $BL, $BLCH).
			_blockInfoTr('ov', $BL, $BLCH).
			_blockInfoTr('bor', $BL, $BLCH).
			_blockInfoTr('hidden', $BL, $BLCH).
			_blockInfoTr('show_create', $BL, $BLCH).
			_blockInfoTr('show_edit', $BL, $BLCH).
			_blockInfoTr('user_id_add', $BL, $BLCH).
			_blockInfoTr('dtime_add', $BL, $BLCH).

			_blockInfoElem($prm, $block_id, $BLCH).

		'</table>';

	return $send;//._pr($prm);
}
function _blockInfoTr($param, $BL, $BLCH) {
	$blCache = isset($BLCH[$param]) ? $BLCH[$param] : '';

	$color = '';
	if(is_array($blCache))
		$blCache = _pr($blCache, true);
	elseif($BL[$param] != $blCache)
		$color = 'clr5';

	return
	'<tr class="over1">'.
		'<td class="clr1">'.$param.
		'<td>'.$BL[$param].
		'<td class="'.$color.'">'.$blCache;
}
function _blockInfoElem($prm, $block_id, $BLCH) {
	$elem_html = '';
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id`=".$block_id;
	if($elem_id = query_value($sql))
		$elem_html = '<a class="dialog-open" val="dialog_close:'.$prm['srce']['dialog_id'].',dialog_id:118,get_id:'.$elem_id.'">'.$elem_id.'<a>';

	$elem_id_cache = $BLCH['elem_id'];
	return
	'<tr class="over1">'.
		'<td class="clr1 b">Элемент'.
		'<td>'.$elem_html.
		'<td>'.$elem_id_cache;
}




