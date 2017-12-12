<?php
function _page($i='all', $id=0) {//��������� ������ ��������
	if(!defined('APP_ID'))
		return 0;

	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//id �������� �� ���������, ���� �� $_GET
	if($i == 'cur') {
		if($page_id = _num(@$_GET['p'])) {
			if(!isset($page[$page_id]))
				return 0;
			return $page_id;
		}

		foreach($page as $p) {
			if(!$p['def'])
				continue;

			if(!SA && $p['sa'])
				continue;

			return $p['id'];
		}

		//����� �� ������ �������
		return 12;
	}

	//�������� �� �������� ������������ ������������ �������
	if($i == 'is_cur_parent') {
		if(!$page_id = _num($id))
			return false;
		$cur = _page('cur');

		//����������� �������� ��������� � �������
		if($page_id == $cur)
			return true;

		//������� �������� ���� �������� �������
		if(!$cur_parent = _num($page[$cur]['parent_id']))
			return false;

		//����������� �������� �������� ��������� �������
		if($page_id == $cur_parent)
			return true;

		//����������� �������� �������� ���-��������� �������
		if($page_id == $page[$cur_parent]['parent_id'])
			return true;

		return false;
	}

	if($page_id = _num($i)) {
		if(!isset($page[$page_id]))
			return false;
		return $page[$page_id];
	}
	return false;
}
function _pageCache() {//��������� ������� ������� �� ����
	if($arr = _cache())
		return $arr;

	$sql = "SELECT
				*,
				0 `block_count`,
				0 `elem_count`,
				1 `del_access`
			FROM `_page`
			WHERE `app_id` IN (0,".APP_ID.")
			ORDER BY `sort`";
	if(!$page = query_arr($sql))
		return array();

	//��������� ���������� ������ �� ������ ��������
	$sql = "SELECT
				`page_id`,
				COUNT(*) `c`
			FROM `_page_block`
			WHERE `page_id` IN ("._idsGet($page).")
			GROUP BY `page_id`";
	$block = query_ass($sql);

	//��������� ���������� ��������� �� ������ ��������
	$sql = "SELECT
				`page_id`,
				COUNT(*) `c`
			FROM `_page_element`
			WHERE `page_id` IN ("._idsGet($page).")
			GROUP BY `page_id`";
	$elem = query_ass($sql);

	foreach($page as $id => $r) {
		$block_count = _num(@$block[$id]);
		$elem_count = _num(@$elem[$id]);
		$page[$id]['block_count'] = $block_count;
		$page[$id]['elem_count'] = $elem_count;
		$page[$id]['del_access'] = $block_count || $elem_count ? 0 : 1;
	}

	return _cache($page);
}

function _pageSetupAppPage() {//���������� ���������� ����������
	$arr = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		$arr[$id] = $r;
	}

	if(empty($arr))
		return
		'<div class="_empty">'.
			'��� �� ������� �� ����� ��������.'.
			'<div class="mt10 fs15 black">�������� ������!</div>'.
		'</div>';

	$sort = array();
	foreach($arr as $id => $r)
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		}

	return
	'<style>'.
		'.placeholder{outline:1px dashed #4183C4;margin-top:1px}'.
		'ol{list-style-type:none;max-width:700px;padding-left:40px}'.
	'</style>'.
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>'.
	'<script>_pageSetupAppPage()</script>';
}
function _pageSetupAppPageSpisok($arr, $sort) {
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1" id="item_'.$r['id'].'">'.
			'<div class="curM">'.
				'<table class="_stab  bor-e8 bg-fff over1">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok ml10 mbm5 curD'._tooltip('�������� �� ���������', -76).'</div>' : '').
						'<td class="w50 wsnw">'.
							'<div onclick="_dialogOpen('.$r['dialog_id'].','.$r['id'].')" class="icon icon-edit'._tooltip('�������� ��������', -58).'</div>'.
	   (!$r['del_access'] ? '<div class="icon icon-off'._tooltip('��������', -29).'</div>' : '').
		($r['del_access'] ? '<div onclick="_dialogOpen(6,'.$r['id'].')" class="icon icon-del-red'._tooltip('�������� ������, �������', -79).'</div>' : '').
				'</table>'.
			'</div>';
		if(!empty($sort[$r['id']]))
			$send .= '<ol>'._pageSetupAppPageSpisok($sort[$r['id']], $sort).'</ol>';
	}

	return $send;
}
function _pageSetupMenu() {//������ ���� ���������� ���������
	if(!PAS)
		return '';
	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	return
	'<div id="pas">'.
		'<div class="p pad5">'.
			'<div class="fr mtm3">'.
				'<div class="icon-page-tmp"></div>'.
			'</div>'.

			'<div class="dib fs16 b">'.
				$page['name'].
			'</div>'.


//			'<div onclick="_dialogOpen('.$page['dialog_id'].','.PAGE_ID.')" class="icon icon-edit mbm5 ml20'._tooltip('������������� ������� ��������', -102).'</div>'.
		'</div>'.
		'<div class="p pad5">'.
			(_page('cur') !=12 ? '<a href="'.URL.'&p=12"><< � ������ �������</a>' : '&nbsp;').
			_blockLevelChange(_page('cur')).
//			'<input type="hidden" id="page-setup-page" />'.
		'</div>'.
	'</div>';
//	'<script>_pas()</script>';
}

/*
function _pageShow($page_id, $blockShow=0) {

	//��������� ������ ������
	$sql = "SELECT *
			FROM `_page_block`
			WHERE `page_id`=".$page_id."
			ORDER BY `parent_id`,`sort`";
	$arr = query_arr($sql);

	if(empty($arr) && !PAS)
		return '<div class="_empty mar20">��� �������� ������ � ��� �� ���� ���������.</div>';

	$block = array();
	$elem = array();
	foreach($arr as $id => $r) {
		$elem[$id] = array();
		$r['sub'] = array();
		if(!$r['parent_id']) {
			$block[$id] = $r;
			continue;
		}
		$block[$r['parent_id']]['sub'][] = $r;
	}

	//����������� ��������� � �����
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$page_id."
			ORDER BY `sort`";
	$elem_arr = query_arr($sql);
	foreach($elem_arr as $r)
		$elem[$r['block_id']][] = $r;

	$send = '';
	foreach($block as $block_id => $r) {
		$cls = PAS ? ' pb prel' : '';
		$val = PAS ? ' val="'.$r['id'].'"' : '';
		$mh = $elem[$block_id] ? '': ' h50';//����������� ������, ���� ���� ������
		$r['elem_count'] = count($elem[$block_id]);

		if(!$r['sub']) {
			$send .=
				'<div id="pb_'.$r['id'].'"'._pageBlockStyle($r).' class="elem-sort bspb '.$r['bg'].' '.$cls.$mh.'"'.$val.'>'.
					_pageBlockPas($r, $blockShow).
					_pageElemSpisok($elem[$block_id]).
				'</div>';
			continue;
		}

		$send .= '<div id="pb_'.$r['id'].'" class="pb"'.$val.'>'.
					'<table class="w100p prel'.$mh.'">'.
						'<tr>';
		$cSub = count($r['sub']) - 1;
		foreach($r['sub'] as $n => $sub) {
			$sub['elem_count'] = count($elem[$sub['id']]);
			$send .= '<td class="elem-sort bspb '.$sub['bg'].' prel top"'._pageBlockStyle($sub).'>'.
						_pageBlockPas($sub, $blockShow, $n != $cSub).
						_pageElemSpisok($elem[$sub['id']]);
		}
		$send .=	'</table>'.
				'</div>';
	}

	return
	'<div class="pbsort0 prel">'.
		$send.
	'</div>'.
//	_pageSpisokUnit().

(PAS ?
	'<div id="page-block-add" class="center mt1 pad15 bg-gr1 bor-f0 over1 curP'._dn($blockShow).'">'.
		'<tt class="fs15 color-555">�������� ����� ����</tt>'.
	'</div>'.
	'<script>'.
		'var ELEM_ARR={'._pageElemArr($elem_arr).'},'.
			'ELEM_COLOR={'._pageElemColor().'};'.
	'</script>'
: '').
//	_pr(_page()).

	'<script>_pageShow()</script>';
}
*/

function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	return _blockTab($page_id);
}
function _blockTab($page_id, $grid_id=0) {
	define('GRID_ID', $grid_id);

	$sql = "SELECT
				*,
				'' `elem`
			FROM `_block`
			WHERE `page_id`=".$page_id."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return '';

	//����������� ��������� � �����
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `block_id` IN ("._idsGet($arr).")
			ORDER BY `sort`";
	foreach(query_arr($sql) as $r)
		$arr[$r['block_id']]['elem'] = $r;

	foreach($arr as $id => $r)
		$arr[$id]['child'] = array();

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$block = _blockChildArr($child);

	return _blockLevel($block, 100).
			'<script>'.
				'var BLOCK_ARR={'._blockArr($arr, $child).'};'.
			'</script>';
}
function _blockChildArr($child, $parent_id=0) {//����������� �������� ������
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _blockChildArr($child, $id);

	return $send;
}
function _blockLevel($arr, $wMax, $hMax=0, $level=1) {//������������ ������ �� �������
	if(empty($arr))
		return '';

	$MN = 10;//���������

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
	$BT = PAS ? ' bor-t-dash' : '';
	$BR = PAS ? ' bor-r-dash' : '';
	$BB = PAS ? ' bor-b-dash' : '';
	$br1px = PAS ? 1 : 0;

	$CLS = 'bl prel ';

	foreach($block as $y => $str) {
		$r = $str[0];

		$cls = $CLS.($r['id'] == GRID_ID ? ' block-unit-grid ' : '');

		$bt = $y ? $BT : '';
		$h = 'min-height:'.$r['height'].'px';

		$hSum += $r['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? ' '.$BB : '';

		if(count($str) == 1 && !$r['x'] && $r['w'] == $wMax) {
			$send .= '<div id="bl_'.$r['id'].'" class="'.$r['bg'].' '.$cls.$bt.$bb.'" style="'._blockStyle($r).$h.'" val="'.$r['id'].'">'.
						_blockSetka($r, $level).
						_blockChildHtml($r, $level + 1).
						_pageElemUnit($r['elem']).
					 '</div>';
			continue;
		}


		$send .=
			'<table style="table-layout:fixed;width:'.($wMax * $MN).'px;'.$h.'">'.
				'<tr>';
		//������� � ������
		if($r['x'])
			$send .= '<td class="'.$BR.$bt.$bb.'" style="width:'.($r['x'] * $MN - $br1px).'px">';
		foreach($str as $n => $r) {
			$next = @$str[$n + 1];

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + (PAS ? 0 : $bor[1]);

			$cls = $CLS.($r['id'] == GRID_ID ? ' block-unit-grid ' : '');
			$xEnd = !($wMax - $r['x'] - $r['w']);
			$send .= '<td id="bl_'.$r['id'].'"'.
						' class="top '.$r['bg'].' '.$cls.$bt.$bb.(!$xEnd ? $BR : '').'"'.
						' style="'._blockStyle($r).'width:'.($r['width'] - $br1px - $borPx).'px"'.
						' val="'.$r['id'].'">'.
							_blockSetka($r, $level).
							_blockChildHtml($r, $level + 1).
	    					_pageElemUnit($r['elem']).
					'';

			//������� � ��������
			if($next)
				if($next['x'] > $r['x'] + $r['w']) {
					$w = $next['x'] - $r['x'] - $r['w'];
					$send .= '<td class="'.$BR.$bt.$bb.'" style="width:'.($w * $MN - $br1px).'px">';
				}

			//������� � �����
			if(!$next && $r['x'] + $r['w'] < $wMax)
				$send .= '<td class="'.$bt.$bb.'" style="width:'.(($wMax - $r['x'] - $r['w']) * $MN).'px">';
		}
		$send .= '</table>';
	}

	return $send;
}
function _blockLevelChange($page_id) {//������ ��� ��������� ������ �������������� ������
	$sql = "SELECT *
			FROM `_block`
			WHERE `page_id`=".$page_id;
	if(!$arr = query_arr($sql))
		return '';

	$max = 1;

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


	$send = '';
	for($n = 1; $n <= $max; $n++) {
		$sel = BLOCK_LEVEL == $n ? '' : ' cancel';
		$send .= '<button class="block-level-change vk small ml5'.$sel.'">'.$n.'</button>';
	}

	return '<div id="block-level" class="dib ml20">'.$send.'</div>';
}
function _blockSetka($r, $level) {//����������� ����� ��� �������������� �����
	if(!PAS)
		return '';
	if(GRID_ID == $r['id'])
		return '';
	if(BLOCK_LEVEL != $level)
		return '';

	return '<div class="block-unit level'.BLOCK_LEVEL.' '.(GRID_ID ? ' grid' : '').'" val="'.$r['id'].'"></div>';
}
function _blockStyle($r) {//����� css ��� �����
	$send = array();

	//�������
	$ex = explode(' ', $r['bor']);
	foreach($ex as $i => $b) {
		if(!$b)
			continue;
		switch($i) {
			case 0: $send[] = 'border-top:#DEE3EF solid 1px'; break;
			case 1: $send[] = 'border-right:#DEE3EF solid 1px'; break;
			case 2: $send[] = 'border-bottom:#DEE3EF solid 1px'; break;
			case 3: $send[] = 'border-left:#DEE3EF solid 1px'; break;
		}
	}

	if(!$send)
		return '';

	return implode(';', $send).';';
}
function _blockArr($arr, $child) {//������ �������� ������ � ������� JS
	if(empty($arr))
		return '';

	$send = array();
	foreach($arr as $id => $r) {
		$send[] = $id.':{'.
			'id:'.$id.','.
			'bg:"'.$r['bg'].'",'.
			'bor:"'.$r['bor'].'",'.
			'elem:'.(empty($r['elem']) ? 0 : 1).','.
			'child:'.(empty($child[$id]) ? 0 : 1).
		'}';
	}
	return implode(',', $send);
}
function _blockGrid($arr) {//����� ������� �������� �� �����
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
		'<div id="grid-add" class="pad5 bg-fff bor-e8 fs14 center color-555 curP over1 mt1">�������� ����</div> '.
		'<div class="pad5 center">'.
			'<button class="vk small orange" id="grid-save">���������</button>'.
			'<button class="vk small cancel ml5" id="grid-cancel">������</button>'.
		'</div>';
}
function _blockChildHtml($block, $level) {//������� ����� �� �����
	if(GRID_ID != $block['id'])
		return _blockLevel($block['child'], $block['w'], $block['h'], $level);

	return _blockGrid($block['child']);
}

















function _pageBlockPas($r, $show=0, $resize=0) {//��������� ������ ��� ��������������
	if(!PAS)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	$block_id = $r['id'];
	$dn = $show ? '' : ' dn';
	$resize = $resize ? ' resize' : '';
	$empty = $r['elem_count'] ? '' : ' empty';


	return
	'<div class="pas-block'.$empty.$resize.$dn.'" val="'.$block_id.'">'.
		'<div class="fl">'.
			$block_id.
			'<span class="fs11 grey"> w'.$r['w'].'</span>'.
			'<span class="fs11 color-acc"> '.$r['sort'].'</span>'.
		'</div>'.
		'<div class="pas-icon">'.
			'<div class="icon icon-add mr3'._tooltip('�������� �������', -57).'</div>'.
			'<div class="icon icon-setup mr3'._tooltip('����� �����', -39).'</div>'.
	($r['parent_id'] ? '<div class="icon icon-move mr3 curM center'._tooltip('�������� �������<br />�� �����������', -56, '', 1).'</div>' : '').
			'<div class="icon icon-div mr3'._tooltip('��������� ���� �������', -76).'</div>'.
			'<div class="icon icon-del-red'._tooltip('������� ����', -42).'</div>'.
		'</div>'.
	'</div>';
}
function _pageElemSpisok($elem) {//������ ��������� ������� html ��� ����������� �����
	if(!$elem)
		return '';

	$send = '';
	foreach($elem as $r) {
		$txt = _pageElemUnit($r);
		$pas = _pageElemPas($r);
		$send .= _pageElem($r, $txt, $pas);
	}

	return $send;
}
function _pageElem($r, $txt, $pas='') {//������������ div ��������
	$tmp = !empty($r['tmp']);//������� ������ �������

	$cls = array();
	$cls[] = $tmp ? '' : 'pe prel';
	$cls[] = $r['display'];
	$cls[] = $r['pos'];
	$cls[] = $r['color'];
	$cls[] = $r['font'];
	$cls[] = $r['size'];
	$cls = array_diff($cls, array(''));
	$cls = implode(' ', $cls);

	$attr_id = $tmp ? '' : ' id="pe_'.$r['id'].'"';

	return
	'<div class="'.$cls.'"'.$attr_id._pageElemStyle($r).'>'.
		$pas.
		$txt.
	'</div>';

}
function _pageElemArr($elem) {//������ �������� ��������� � ������� JS
	if(empty($elem))
		return '';

	$send = array();
	foreach($elem as $r) {
		$size = 13;
		if($r['size']) {
			$ex = explode('fs', $r['size']);
			$size = _num($ex[1]);
		}
		$send[] = $r['id'].':{'.
			'id:'.$r['id'].','.
			'dialog_id:'.$r['dialog_id'].','.
			'fontAllow:'._pageElemFontAllow($r['dialog_id']).','.
			'display:"'.$r['display'].'",'.
			'pos:"'.$r['pos'].'",'.
			'w:'.$r['w'].','.
			'color:"'.$r['color'].'",'.
			'font:"'.$r['font'].'",'.
			'size:'.$size.','.
			'pad:"'.$r['pad'].'"'.
		'}';
	}
	return implode(',', $send);
}
function _pageElemColor() {//������ ������ ��� ������ � ������� JS, ��������� ���������
	return
		'"":["#000","׸����"],'.
		'"color-555":["#555","Ҹ���-�����"],'.
		'"grey":["#888","�����"],'.
		'"pale":["#aaa","�������"],'.
		'"color-ccc":["#ccc","������ �������"],'.
		'"blue":["#2B587A","Ҹ���-�����"],'.
		'"color-acc":["#07a","�����"],'.
		'"color-sal":["#770","���������"],'.
		'"color-pay":["#090","������"],'.
		'"color-aea":["#aea","����-������"],'.
		'"color-ref":["#800","Ҹ���-�������"],'.
		'"red":["#e22","�������"],'.
		'"color-del":["#a66","Ҹ���-��������"],'.
		'"color-vin":["#c88","��������"]';
}
function _pageElemPas($r) {
	if(!PAS)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	return '<div class="elem-pas" val="'.$r['id'].'"></div>';
}
function _pageElemUnit($unit) {//������������ �������� ��������
	if(!$unit)
		return '';
	switch($unit['dialog_id']) {
		case 2://button
			/*
				txt_1 - ����� ������
				num_1 - ����
				num_2 - ��������� ������
				num_3 - ������������ ������
			*/
			$color = array(
				0 => '',        //����� - �� ���������
				321 => '',      //�����
				322 => 'green', //������
				323 => 'red',   //�������
				324 => 'grey',  //�����
				325 => 'cancel',//����������
				326 => 'pink',  //�������
				327 => 'orange' //���������
			);
			return _button(array(
						'name' => $unit['txt_1'],
						'click' => '_dialogOpen('._dialogValToId('button'.$unit['id']).')',
						'color' => $color[$unit['num_1']],
						'small' => $unit['num_2'],
						'class' => $unit['num_3'] ? 'w100p' : ''
					));
		case 3://menu
			return _pageElemMenu($unit);
		case 4://head
			return '<div class="hd2">'.$unit['txt_1'].'</div>';
		case 7://search
			return _search(array(
						'hold' => $unit['txt_1'],
						'width' => $unit['num_2'],
						'v' => $unit['v']
					));
		case 9://link
			return '<a href="'.URL.'&p='.$unit['num_1'].'">'.
						$unit['txt_1'].
				   '</a>';
		case 10://������������ �����
			return _br($unit['txt_1']);
		case 11://��� ������� ��� �������� �� �������
			/*
				num_1 - dialog_id ������
				num_2 - ��� ���������� �������:
							331: ��������
							332: ��������
				num_3 - id ���������� �������
				$_GET['id'] - id ������ ��� ������
			*/
			if(!$spisok_id = _num(@$_GET['id']))
				return '������������ id �������';

			if(!$unit['num_3'])
				return '������� �������� ����������';

			$sql = "SELECT *
					FROM `_spisok`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$spisok_id;
			if(!$sp = query_assoc($sql))
				return '������� �� ����������';

			$dialog = _dialogQuery($unit['num_1']);
			$cmp = $dialog['component'][$unit['num_3']];

			if($unit['num_2'] == 331)
				return $cmp['label_name'];

			if($unit['num_2'] == 332)
				return $sp[$cmp['col_name']];

			return 'spisok_id='.$spisok_id.' '.$unit['num_3']._pr($cmp);
		case 12://�� ������� ��������
			if(!$unit['txt_1'])
				return '������ �������� �������';
			if(!function_exists($unit['txt_1']))
				return '������� �� ����������';
			return $unit['txt_1']();
		case 14: return _spisokShow($unit); //���������� ������
		case 15: return _spisokElemCount($unit);//����� � ����������� ����� ������
	}
	return '����������� �������='.$unit['dialog_id'];
}
function _pageElemStyle($r) {//����� css ��� ��������
	$send = array();

	//�������
	$ex = explode(' ', $r['pad']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'padding:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	if($r['w'])
		$send[] = 'width:'.$r['w'].'px';
//	$send[] = 'box-sizing:padding-box';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _pageElemFontAllow($dialog_id) {//����������� � ���������� ������ ��� ���������� ��������� ��������
	$elem = array(
		10 => 1,
		11 => 1,
		15 => 1
	);
	return _num(@$elem[$dialog_id]);
}

function _pageElemMenu($unit) {//������� ��������: ����
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if($unit['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return '�������� ���.';

	$razdel = '';
	foreach($menu as $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';
		$razdel .=
			'<a class="link'.$sel.'" href="'.URL.'&p='.$r['id'].'">'.
				$r['name'].
			'</a>';
	}

	//������� ��� ����
	$type = array(
		0 => 0,
		335 => 0, //�������� - �������������� ����
		336 => 1, //� �������������� (�����.)
		337 => 2, //�������������� �� ����� ���� (�����.)
		339 => 3, //�������������� �� ����� ���� (�����.)
		338 => 4  //���. - ������������
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';//._pr(_page());
}







function _pageSpisokUnit() {//todo ��� ������
	if(!$unit_id = _num(@$_GET['id']))
		return '';

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return '<div class="fs10 pale">������ �� ����������</div>';

	return _pr($unit);
}



function _page_div() {//todo ����
	return 'test';
}
function gridStackStyleGen() {//������������� ������ ��� gridstack
	$step = 50;    //��� ����� �� �����������
	$send = '';
	$w = round(100 / $step, 10);//������ ���� � ���������
	$next = $w;
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-stack-item[data-gs-width='".$n."'] {width:".$next."%}<br>".
			".grid-stack-item[data-gs-x='".$n."']     {left:".$next."%}<br>".
			".grid-stack-item[data-gs-min-width='".$n."'] {min-width:".$next."%}<br>".
			".grid-stack-item[data-gs-max-width='".$n."'] {max-width:".$next."%}<br>".
			"<br>";
		$next = round($next + $w, 10);
	}

	return $send;
}
function gridStackStylePx() {//������������� ������ ��� grid-child
	$step = 100;    //��� ����� �� �����������
	$send = '';
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-child-item[data-gs-width='".$n."']{width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-x='".$n."']{left:".($n*10)."px}<br>".
			".grid-child-item[data-gs-min-width='".$n."']{min-width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-max-width='".$n."']{max-width:".($n*10)."px}<br>".
			"<br>";
	}

	return $send;
}







