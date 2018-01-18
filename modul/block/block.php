<?php
function _blockChildClear($arr) {//������� �������� ������, ���� ����������� ��������
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
function _blockArr($obj_name, $obj_id, $return='block') {//��������� ��������� ������ � ���������� ��� ���������� ��������
	/*
		$return:
			block - �������� ������
			arr - ������������������ ������ (��� ������� JS)
	*/
	$sql = "SELECT
				*,
				'' `elem`
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id."
			  AND `sa` IN (0,".SA.")
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return array();

	$arr = _blockChildClear($arr);

	//��������� ���������
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	if($elem = query_arr($sql)) {
		foreach($elem as $id => $r) {
			$elem[$id]['elv_ass'] = array();
			$elem[$id]['elv_spisok'] = array();
			$elem[$id]['elv_def'] = 0;
		}

		//�������� ���������-�������
		$sql = "SELECT *
				FROM `_element_value`
				WHERE `element_id` IN("._idsGet($elem).")
				ORDER BY `element_id`,`sort`";
		foreach(query_arr($sql) as $r) {
			$id = _num($r['id']);
			$elem_id = _num($r['element_id']);
			$elem[$elem_id]['elv_ass'][$id] = $r['title'];
			$elem[$elem_id]['elv_spisok'][] = array(
				'uid' => $id,
				'title' => $r['title']
			);
			if($r['def'])
				$elem[$elem_id]['elv_def'] = $id;
		}

		//����������� ��������� � �����
		foreach($elem as $r) {
			unset($arr[$r['block_id']]['elem']);
			$r['block'] = $arr[$r['block_id']];
			$arr[$r['block_id']]['elem'] = $r;
		}
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

	//���������� �������� ������ ��� ������� �����
	foreach($arr as $id => $r) {
		if(!$r['parent_id'])
			continue;
		$arr[$r['parent_id']]['child_count'] = count($child[$r['parent_id']]);
	}

	return $arr;
}
function _blockArrChild($child, $parent_id=0) {//����������� �������� ������
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r) {
		$send[$id]['child'] = _blockArrChild($child, $id);
		$send[$id]['child_count'] = count($send[$id]['child']);
	}

	return $send;
}
function _blockObj($name, $i='name') {//��������� �������� �������� ��� ������
	$empty = array(
		'page' => '<div class="_empty mar20">��� �������� ������ � ��� �� ���� ���������.</div>',

		'spisok' =>
			'<div class="bg-ffe pad10">'.
				'<div class="_empty min">'.
					'������ ����.'.
					'<div class="mt10 pale">������� � ��������� ������.</div>'.
				'</div>'.
			'</div>',

		'dialog' => '<div class="pad10">'.
						'<div class="_empty min">'.
							'������ ���������� �������.'.
	   (_num(@BLOCK_EDIT) ? '<div class="mt10 pale">������� � ���������� �������.</div>' : '').
						'</div>'.
					'</div>'
	);

	if(!isset($empty[$name]))
		return 0;

	//��������� ���������� ������
	if($i == 'empty')
		return $empty[$name];

	return $name;
}
function _blockHtml($obj_name, $obj_id, $width=1000, $grid_id=0, $unit=array()) {//����� �� ����� ���� ��������� ������
	if(!$block = _blockArr($obj_name, $obj_id))
		return _blockObj($obj_name, 'empty');

	return _blockLevel($block, $width, $grid_id, 0,1, $unit);
}
function _blockLevel($arr, $WM, $grid_id=0, $hMax=0, $level=1, $unit=array()) {//������������ ������ �� �������
	if(empty($arr))
		return '';

	//������� ��������� ������ ��������
	if(!defined('ELEM_WIDTH_CHANGE'))
		define('ELEM_WIDTH_CHANGE', 0);

	//������� ��� ��������� ������ ����������� �������
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

	$MN = 10;//���������
	$wMax = round($WM / $MN);

	//����������� ������ �����, ���� ����� �� ������� �� ����
	$yEnd = 0;
	$hSum = 0;

	//����������� ��������� ������ �� �������
	$block = array();
	foreach($arr as $r) {
		$block[$r['y']][] = $r;
		$yEnd = $r['y'];
	}

	$send = '';
	$BT = BLOCK_EDIT ? ' bor-t-dash' : '';
	$BR = BLOCK_EDIT ? ' bor-r-dash' : '';
	$BB = BLOCK_EDIT ? ' bor-b-dash' : '';
	$br1px = BLOCK_EDIT ? 1 : 0;//����� ������� �������������� ����� ������

	foreach($block as $y => $str) {
		$widthMax = $WM;
		$r = $str[0];

		$bt = $y ? $BT : '';

		$hSum += $r['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? $BB : '';

		$send .=
			'<div class="bl-div y'.$y.'">'.
			'<table class="bl-tab" style="height:'.$r['height'].'px">'.
				'<tr>';
		//������� � ������
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
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + (BLOCK_EDIT ? 0 : $bor[1]);
			$width = $r['width'] - ($xEnd ? 0 : $br1px) - $borPx;

			$send .= '<td id="bl_'.$r['id'].'"'.
						' class="'.$cls.'"'.
						' style="'._blockStyle($r, $width).'"'.
				 (BLOCK_EDIT ? ' val="'.$r['id'].'"' : '').
					 '>'.
							_blockSetka($r, $level, $grid_id).
							_blockChoose($r, $unit).
							_blockElemChoose($r, $unit).
							_blockChildHtml($r, $level + 1, $width, $grid_id, $unit).
	    					_elemDiv($r['elem'], $unit).
					'';

			$widthMax -= $r['width'];

			//������� � ��������
			if($next)
				if($next['x'] > $r['x'] + $r['w']) {
					$w = $next['x'] - $r['x'] - $r['w'];
					$width = $w * $MN - $br1px;
					$send .= '<td class="'.$BR.$bt.$bb.'" style="width:'.$width.'px">';
					$widthMax -= $width;
				}

			//������� � �����
			if(!$next && $widthMax)
				$send .= '<td class="'.$bt.$bb.'" style="width:'.$widthMax.'px">';
		}
		$send .= '</table>'.
				 '</div>';
	}

	return $send;
}
function _blockLevelChange($obj_name, $obj_id, $width=1000) {//������ ��� ��������� ������ �������������� ������
	$max = 1;
	$html = '';

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	if($arr = query_arr($sql)) {
		//����������� ���������� ������� ������
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

		//���������� �������� ������ ��������� ������, ���� � ����������� ������� ���� ������ �������
		$selected = _blockLevelDefine($obj_name);
		if($selected > $max) {
			_blockLevelDefine($obj_name, 1);
			$selected = 1;
		}

		for($n = 1; $n <= $max; $n++) {
			$sel = $selected == $n ? 'orange' : 'cancel';
			$html .= '<button class="block-level-change vk small ml5 '.$sel.'">'.$n.'</button>';
		}

		//�����������, ���� �� ��������, � ������� ����� �������� ������, ����� �������� ������ ���������
		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id` IN ("._idsGet($arr).")";
		foreach(query_arr($sql) as $r)
			if(_dialogParam($r['dialog_id'], 'element_width')) {
				$html .= '<button class="vk small grey ml30 elem-width-change">��������� ������ ���������</button>';
				break;
			}
	}

	return
	'<div id="block-level-'.$obj_name.'" class="dib" val="'.$obj_name.':'.$obj_id.':'.$width.'">'.
		'<button class="vk small grey block-grid-on">���������� �������</button>'.
		$html.
	'</div>';
}
function _blockLevelDefine($obj_name, $v = 0) {//������� ������������� ������
	$key = 'block_level_'.$obj_name;
	if($v) {
		$_COOKIE[$key] = $v;
		setcookie($key, $v, time() + 2592000, '/');
		return $v;
	}
	return empty($_COOKIE[$key]) ? 1 : _num($_COOKIE[$key]);
}
function _blockSetka($r, $level, $grid_id) {//����������� ����� ��� �������������� �����
	if(!BLOCK_EDIT)
		return '';
	//���������� ��������� ������ �������� ��������� ��������� ������
	if(ELEM_WIDTH_CHANGE)
		return '';
	if($r['id'] == $grid_id)
		return '';

	$bld = _blockLevelDefine($r['obj_name']);

	if($bld != $level)
		return '';

	$bld += $r['obj_name'] == 'page' ? 0 : 2;

	return '<div class="block-unit level'.$bld.' '.($grid_id ? ' grid' : '').'" val="'.$r['id'].'"></div>';
}
function _blockChoose($r, $unit) {//��������� ������ ��� ������ (� ��������)
	if(empty($unit['choose']))
		return '';
	//�������� ����� ������ �������� �����
	if($r['parent_id'])
		return '';
	if(!$ca = $unit['choose_access'])
		return '';
	if(!@$ca['block'])
		return '';

	//������� ��������� �����
	$block_id = $r['id'];
	$sel = isset($unit['choose_sel'][$block_id]) ? ' sel' : '';

	return '<div class="choose block-choose'.$sel.'" val="'.$block_id.'"></div>';
}
function _blockElemChoose($r, $unit) {//��������� ��������� ��� ������� � ������
	//������� ������
	if(empty($unit['choose']))
		return '';
	if(empty($r['elem']))//���� �� ��������������, ���� � ��� ��� ��������
		return '';
	if($r['obj_name'] != 'dialog')//����� ��������� ����� ����������� ������ � �������� (����)
		return '';

	$dialog_id = $r['elem']['dialog_id'];

	//��������� �����, ������� ��������� ��������
	if(!$ca = $unit['choose_access'])
		return '';

	if(@$ca['block'])
		return '';

	if(!@$ca['all'] && !isset($ca[$dialog_id]))
		return '';

	//������� ��������� �����
	$elem_id = $r['elem']['id'];
	$sel = isset($unit['choose_sel'][$elem_id]) ? ' sel' : '';

	return '<div class="choose block-elem-choose'.$sel.'" val="'.$elem_id.'"></div>';
}
function _blockStyle($r, $width) {//����� css ��� �����
	$send = array();

	//�������
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

	return implode(';', $send);
}
function _blockJS($obj_name, $obj_id) {//������ �������� ������ � ������� JS
	if(!$arr = _blockArr($obj_name, $obj_id, 'arr'))
		return '{}';

	$send = array();
	foreach($arr as $id => $r) {
		$v = array();
		$v[] = 'id:'.$id;
		$v[] = 'attr_bl:"#bl_'.$id.'"';
		$v[] = 'sa:"'.$r['sa'].'"';
		$v[] = 'width_auto:"'.$r['width_auto'].'"';
		$v[] = 'pos:"'.$r['pos'].'"';
		$v[] = 'bg:"'.$r['bg'].'"';
		$v[] = 'bor:"'.$r['bor'].'"';
		$v[] = 'obj_name:"'.$r['obj_name'].'"';
		$v[] = 'obj_id:'.$r['obj_id'];
		$v[] = 'child:'.$r['child_count'];

		if($el = $r['elem']) {
			$v[] = 'elem_id:'._num($el['id']);
			$v[] = 'dialog_id:'._num($el['dialog_id']);

			$v[] = 'style_access:'._dialogParam($el['dialog_id'], 'element_style_access');
			$v[] = 'width:'.$el['width'];
			$v[] = 'focus:'.$el['focus'];
			$v[] = 'color:"'.$el['color'].'"';
			$v[] = 'font:"'.$el['font'].'"';
			$v[] = 'size:'.($el['size'] ? _num($el['size']) : 13);
			$v[] = 'mar:"'.$el['mar'].'"';

			$v[] = 'attr_id:"#cmp_'.$el['id'].'"';
			$v[] = 'attr_cmp:"#cmp_'.$el['id'].'"';
			$v[] = 'attr_el:"#pe_'.$el['id'].'"';

			$v[] = 'num_1:'._num($el['num_1'], true);
			$v[] = 'num_2:'._num($el['num_2']);
			$v[] = 'num_3:'._num($el['num_3']);
			$v[] = 'num_4:'._num($el['num_4']);
			$v[] = 'num_7:'._num($el['num_7']);
			$v[] = 'txt_1:"'._br($el['txt_1']).'"';
			$v[] = 'txt_2:"'._br($el['txt_2']).'"';

			$v[] = 'elv_spisok:'._selJson($el['elv_ass']);
			$v[] = 'elv_def:'.$el['elv_def'];
		}

		$send[] = $id.':{'.implode(',', $v).'}';
	}
	return '{'.implode(',', $send).'}';
}
function _blockJsArr($obj_name, $obj_id) {//������ �������� ������ � ������� ��� �������� ����� JSON ��� BLOCK_ARR
	if(!$arr = _blockArr($obj_name, $obj_id, 'arr'))
		return array();

	$send = array();
	foreach($arr as $id => $r) {
		$v = array(
			'id' => _num($id),
			'attr_bl' => '#bl_'.$id,
			'sa' => _num($r['sa']),
			'width_auto' => _num($r['width_auto']),
			'pos' => $r['pos'],
			'bg' => $r['bg'],
			'bor' => $r['bor'],
			'obj_name' => $r['obj_name'],
			'obj_id' => _num($r['obj_id']),
			'child' => _num($r['child_count'])
		);

		if($el = $r['elem']) {
			//����������� ������������ ������, �� ������� ����� ������������� �������
			$ex = explode(' ', $el['mar']);
			$width_max = $el['block']['width'] - $ex[1] - $ex[3];
			$width_max = floor($width_max / 10) * 10;

			$v['elem_id'] = _num($el['id']);
			$v['dialog_id'] = _num($el['dialog_id']);
			$v['style_access'] = _dialogParam($el['dialog_id'], 'element_style_access');

			$v['width'] = _num($el['width']);
			$v['width_min'] = _dialogParam($el['dialog_id'], 'element_width_min');
			$v['width_max'] = $width_max;

			$v['focus'] = _num($el['focus']);

			$v['color'] = $el['color'];
			$v['font'] = $el['font'];
			$v['size'] = $el['size'] ? _num($el['size']) : 13;
			$v['mar'] = $el['mar'];

			$v['attr_id'] = '#cmp_'.$el['id'];
			$v['attr_cmp'] = '#cmp_'.$el['id'];
			$v['attr_el'] = '#pe_'.$el['id'];

			$v['num_1'] = _num($el['num_1'], true);
			$v['num_2'] = _num($el['num_2']);
			$v['num_3'] = _num($el['num_3']);
			$v['num_4'] = _num($el['num_4']);
			$v['num_7'] = _num($el['num_7']);
			$v['txt_1'] = utf8(_br($el['txt_1']));
			$v['txt_2'] = utf8(_br($el['txt_2']));

			$v['elv_spisok'] = _selArray($el['elv_ass']);
			$v['elv_def'] = $el['elv_def'];
		}

		$send[_num($id)] = $v;
	}
	return $send;
}
function _blockChildHtml($block, $level, $width, $grid_id, $unit) {//������� ����� �� �����
	if($block['id'] != $grid_id)
		return _blockLevel($block['child'], $width, $grid_id, $block['h'], $level, $unit);

	return _blockGrid($block['child']);
}
function _blockGrid($arr) {//����� ������� �� ��������
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
		'<div id="grid-add" class="pad5 bg-gr2 bor-e8 fs14 center color-555 curP over5 mt1">�������� ����</div> '.
		'<div class="pad5 center">'.
			'<button class="vk small orange" id="grid-save">���������</button>'.
			'<button class="vk small cancel ml5" id="grid-cancel">������</button>'.
		'</div>';
}
