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
function _blockArrChild($child, $parent_id=0) {//����������� �������� ������
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _blockArrChild($child, $id);

	return $send;
}
function _blockName($name, $i='name') {//��������� �������� �������� ��� ������
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
	if(!$block = _BE('block_obj', $obj_name, $obj_id))
		return _blockName($obj_name, 'empty');
	if(!is_array($unit))
		return $unit;

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
		if(!BLOCK_EDIT && empty($unit['choose']) && $r['elem_id'] && $r['elem']['hidden'])
			continue;
		$block[$r['y']][$r['x']] = $r;
	}

	ksort($block);
	end($block);
	$yEnd = key($block);

	if(empty($block))
		return '';

	$send = '';
	$BT = BLOCK_EDIT ? ' bor-t-dash' : '';
	$BR = BLOCK_EDIT ? ' bor-r-dash' : '';
	$BB = BLOCK_EDIT ? ' bor-b-dash' : '';
	$br1px = BLOCK_EDIT ? 1 : 0;//����� ������� �������������� ����� ������

	foreach($block as $y => $str) {
		$widthMax = $WM;

		ksort($str);//������������ ������ �� X

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
		//������� � ������
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
			//$cls[] = BLOCK_EDIT ? 'prel' : '';
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

			//���� ���� ������ �������, attr_id �� ��������
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
	'<div id="block-level-'.$obj_name.'" val="'.$obj_name.':'.$obj_id.':'.$width.'">'.
		'<button class="vk small grey block-grid-on">���������� �������</button>'.
		$html.
		'<div class="dn fr">'.
			'<button class="vk small green mr5 block-choose-submit">����� �������</button>'.
			'<button class="vk small cancel block-choose-cancel">��������� � �������</button>'.
		'</div>'.
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
function _blockSetka($r, $level, $grid_id, $unit) {//����������� ����� ��� �������������� �����
	if(!BLOCK_EDIT)
		return '';
	//���������� ��������� ������ �������� ��������� ��������� ������
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
function _blockChoose($r, $level, $unit) {//��������� ������ ��� ������ (� ��������)
	if(empty($unit['choose']))
		return '';
//	if($r['parent_id'])//�������� ����� ������ �������� �����
//		return '';
	if($level != @$_COOKIE['block_level_'.$r['obj_name']])//�������� ����� ������ ����� �������������� ������ (�� ������, ������� ���������� �������)
		return '';
	if(!$ca = $unit['choose_access'])
		return '';
	if(!@$ca['block'])
		return '';

	//������� ��������� �����
	$block_id = $r['id'];
	$sel = isset($unit['choose_sel'][$block_id]) ? ' sel' : '';
	$deny = isset($unit['choose_deny'][$block_id]) ? ' deny' : '';

	return '<div class="choose block-choose'.$sel.$deny.'" val="'.$block_id.'"></div>';
}
function _blockElemChoose($r, $unit) {//��������� ��������� ��� ������� � ������
	//������� ������
	if(empty($unit['choose']))
		return '';
	if(empty($r['elem']))//���� �� ��������������, ���� � �� ��� ��������
		return '';
//	if($r['obj_name'] != 'dialog')//����� ��������� ����� ����������� ������ � �������� (����)
//		return '';

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
function _blockStyle($r, $width, $unit) {//����� css ��� �����
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

	//���� ���� �� ������� ������
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

function _BE($i, $i1=0, $i2=0) {//����������� ��������� ����������
	global $BE_FLAG, $G_BLOCK, $G_ELEM, $G_DLG;

	_beDefine();

	//��������� ������ ���� ������
	if($i == 'block_all')
		return $G_BLOCK;

	//��������� ������ ������ �����
	if($i == 'block_one') {
		//ID �����
		if(!$i1)
			return array();
		if(!isset($G_BLOCK[$i1]))
			return array();

		$send = $G_BLOCK[$i1];
		$send['elem'] = $send['elem_id'] ? $G_ELEM[$send['elem_id']] : array();

		return $send;
	}

	//��������� ������ ��� ����������� �������
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

	//��������� ������ ��� ����������� ������� c ������ ��������
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

			//������� �������� � ����
			if($r['elem_id'] && isset($G_ELEM[$r['elem_id']])) {
				$el = $G_ELEM[$r['elem_id']];
				$el['block'] = $G_BLOCK[$id];//�������������� ������������ ������ ����� � ��������
				$r['elem'] = _beElemVvv($el);
			}

			$blk[$id] = _beBlockBg($r);
		}

		$child = array();
		foreach($blk as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		return _blockArrChild($child);
	}

	//������ ������ � ������� JS ��� ����������� �������
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

	//������� ���� ������
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

	//��������� ������ ���� ���������
	if($i == 'elem_all')
		return $G_ELEM;

	//��������� ������ ������ ��������
	if($i == 'elem_one') {
		//ID ��������
		if(!$i1)
			return array();
		if(!isset($G_ELEM[$i1]))
			return array();

		$send = $G_ELEM[$i1];
		$send['block'] = $G_BLOCK[$send['block_id']];

		return $send;
	}

	//��������� ��������� ��� ����������� �������
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

	if($i == 'elem_js') {//������ ��������� � ������� JS
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

	//������� ���� ���������
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

	//��������� ������ ������ �������
	if($i == 'dialog') {
		//ID �������
		if(!$dialog_id = _num($i1))
			return array();
		if(!isset($G_DLG[$dialog_id]))
			return array();

		$send = $G_DLG[$dialog_id];

		return $send;
	}

	//������� ���� ��������
	if($i == 'dialog_clear') {
		_cache_clear('dialog');
		_cache_clear('dialog', 1);
		$BE_FLAG = 0;
	}

	return false;
}
function _beDefine() {//��������� ������ � ��������� �� ����
	global  $BE_FLAG,//���� ����������� ���������� ���������
			$G_BLOCK, $G_ELEM, $G_DLG;

	//���� ���� ����������, ������ ��� ��� �������, ���������� �������� ���������
	if($BE_FLAG)
		return;

	$G_BLOCK = array();
	$G_ELEM = array();

	//�������
	$G_DLG = _beDlg();

	//����� �������
	_beBlockType('page');
	//����� ��������
	_beBlockType('dialog');

	$BE_FLAG = 1;
}
function _beBlockType($type) {//��������� ������ � ������ �� ����
	global $G_BLOCK;

	$key = 'BLK_'.$type;

	//����������
	if(!$block_global = _cache_get($key, 1)) {
		$sql = "SELECT `id`
				FROM `_".$type."`
				WHERE !`app_id`";
		$page_ids = query_ids($sql);

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$type."'
				  AND `obj_id` IN (".$page_ids.")
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

	//��� ����������� ����������
	if(!$block_app = _cache_get($key)) {
		$sql = "SELECT `id`
				FROM `_".$type."`
				WHERE `app_id`=".APP_ID;
		$page_ids = query_ids($sql);

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$type."'
				  AND `obj_id` IN (".$page_ids.")";
		$block_app = query_arr($sql);
		$block_app = _beBlockForming($block_app);
		$block_app = _beElemIdSet($block_app);

		_cache_set($key, $block_app);
	}

	$G_BLOCK += $block_app;
	_beBlockSpisok($type, $block_app);
	_beBlockElem($type, $block_app);
}
function _beBlockSpisok($type, $block, $global=0) {//��������� ������ � ������-�������
	global $G_BLOCK;

	if(empty($block))
		return;

	$key = 'BLK_SPISOK_'.$type;
	if(!$arr = _cache_get($key, $global)) {
		//���� ������� ������ ������ � ��� ���� ������ � ���� ����, ������ �� ���� �� ������������
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
function _beBlockForming($arr) {//������������ ������� ������ ��� ����
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

	//���� ������������ �������-���� ����, ��������� ������� ��� �����, ���� ����������� ������� �����
	$r['xx_ids'] = _idsAss($r['xx_ids']);
	$r['bg_col'] = '';    //��� �������, �� ������� ����� ���������� ����
	$r['bg_connect'] = '';//��� �������, ���� ��� ������������ ������
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


	//����������� �������� ����� ��� ������������ ������� ������
	//����� ����������� ������, ������� ������ ������ ������, ����� �������, ������ ����� ���� ��� �������
	//������ ������������, ����:
	//      1. spisok-�����. id �������, ������� ������ �������� ������
	//      2. dialog-�����. id ����� �������
	//      3. page-�����.   id �������, ������� ������ �������� ������, �������� ������� �������� �������� ������
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
function _beBlockElem($type, $BLK, $global=0) {//��������, ������� ����������� � ������
	global $G_ELEM, $G_DLG;

	if(empty($BLK))
		return;

	$key = 'ELM_'.$type;
	if(!$ELM = _cache_get($key, $global)) {
		if(_cache_isset($key, $global))
			return;

		$ELM = array();

		//������� ������� � ���������
		$sql = "SELECT `block_id`,1
				FROM `_element_func`
				WHERE `block_id` IN("._idsGet($BLK).")
				GROUP BY `block_id`";
		$isFunc = query_ass($sql);

		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id` IN ("._idsGet($BLK).")";
		foreach(query_arr($sql) as $elem_id => $el) {
			$el['hidden'] = 0;

			unset($el['sort']);
			unset($el['user_id_add']);
			unset($el['dtime_add']);

			//��������� ��� ��������
			if(!$el['hint_on']) {
				unset($el['hint_msg']);
				unset($el['hint_side']);
				unset($el['hint_obj_pos_h']);
				unset($el['hint_obj_pos_v']);
				unset($el['hint_delay_show']);
				unset($el['hint_delay_hide']);
			}

			//��������� �������� �������� � INT, ���� ����
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
				//����������� ������������ ������, �� ������� ����� ������������� �������
				$ex = explode(' ', $el['mar']);
				$width_max = $BLK[$el['block_id']]['width'] - $ex[1] - $ex[3];
				$el['width_max'] = floor($width_max / 10) * 10;
			}

			$el['func'] = array();
			$el['vvv'] = array();//�������� ��� ��������� �����������

			$ELM[$elem_id] = $el;
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
function _beElemIdSet($arr) {//���������� id �������� � �����
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
function _beElemVvv($el) {//������� �������������� �������� � �������
	global $G_ELEM, $G_DLG;

	switch($el['dialog_id']) {
		//��������, ��������� �� ������� - ������������� ��������� ��������
		case 11:
			if(!$ids = _ids($el['txt_2'], 1))
				break;
			$c = count($ids) - 1;
			$last_id = $ids[$c];
			if(!$el11 = $G_ELEM[$last_id])
				break;
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
		//select - ������������ ��������
		case 17:
		//dropdown
		case 18: $el['vvv'] = _elemValue($el['id']); break;
		//���� ������������ ������ - ������ �������
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
		//������-select
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
function _beDlg() {//��������� ������ �������� �� ����
	$key = 'dialog';
	//���������� �������
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

	//������� ����������� ����������
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
function _beDlgField($dialog) {//������� ������� ������ � �������
	//������� �� ������ �������, ������������ � ��������
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

	//������ �������, �������������� � �������� 1 � 2
	foreach($dialog as $dlg_id => $r)
		foreach(array(1,2) as $id) {
			$dialog[$dlg_id]['field'.$id] = array();
			$table_id = $r['table_'.$id];
			if($dialog[$dlg_id]['table_name_'.$id] = _table($table_id))
				$dialog[$dlg_id]['field'.$id] = $field[$table_id];
		}

	return $dialog;
}

