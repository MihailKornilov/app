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

	//������ ���������� ��������
	if($page_id = _num($i)) {
		if(!isset($page[$page_id]))
			return false;
		return $page[$page_id];
	}

	//�������� ������� ��������
	if($page_id = _page('cur')) {
		if(!isset($page[$page_id]))
			return false;
		if(!isset($page[$page_id][$i]))
			return false;
		return $page[$page_id][$i];
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
			FROM `_block`
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

function _pageSetupDefine() {//��������� ����� ��������� ���������� ��������� PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//�������� ����������
		if($page = _page($page_id))//������ �������� ��������
			if(!($page['sa'] && !SA))
				if(!(!$page['app_id'] && !SA))
					$pas = _bool(@$_COOKIE['page_setup']);

	define('PAS', $pas);
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

	return
	'<div id="pas">'.
		'<div class="p pad5">'.
			'<div class="fr mtm3">'.
				'<div class="icon-page-tmp"></div>'.
			'</div>'.

			'<div class="dib fs16 b">'.
				_page('name').
			'</div>'.


//			'<div onclick="_dialogOpen('.$page['dialog_id'].','.PAGE_ID.')" class="icon icon-edit mbm5 ml20'._tooltip('������������� ������� ��������', -102).'</div>'.
		'</div>'.
		'<div class="p pad5">'.
			(_page('cur') !=12 ? '<a href="'.URL.'&p=12"><< � ������ �������</a>' : '&nbsp;').
			_blockLevelChange(_page('cur')).
		'</div>'.
	'</div>';
}

function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	return
	_blockTab($page_id).
	(!PAS ? '<script>_pageShow()</script>' : '');
}
function _pageBlockArr($page_id, $is_spisok=0) {//��������� ��������� ������ � ���������� ��� ���������� ��������
	$cond = "`page_id`=".$page_id;
	//����� ������ ��� ������� ������
	if($is_spisok)
		$cond = "`is_spisok`=".$is_spisok;

	$sql = "SELECT
				*,
				'' `elem`
			FROM `_block`
			WHERE ".$cond."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return array();

	//����������� ��������� � �����
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	foreach(query_arr($sql) as $r) {
		unset($arr[$r['block_id']]['elem']);
		$r['block'] = $arr[$r['block_id']];
		$arr[$r['block_id']]['elem'] = $r;
	}

	foreach($arr as $id => $r)
		$arr[$id]['child'] = array();

	return $arr;
}
function _blockTab($page_id, $grid_id=0) {
	define('GRID_ID', $grid_id);

	if(!$arr = _pageBlockArr($page_id))
		return PAS ? '' : '<div class="_empty mar20">��� �������� ������ � ��� �� ���� ���������.</div>';

	$child = array();
	foreach($arr as $id => $r) {
		if($r['is_spisok'])
			continue;
		$child[$r['parent_id']][$id] = $r;
	}

	$block = _blockChildArr($child);

	return
	_blockLevel($block, 1000).
	'<script>'.
		'var BLOCK_ARR={'._blockJS($page_id).'},'.
			'ELEM_COLOR={'._elemColor().'};'.
	'</script>';
}
function _blockChildArr($child, $parent_id=0) {//����������� �������� ������
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _blockChildArr($child, $id);

	return $send;
}
function _blockLevel($arr, $WM, $hMax=0, $level=1) {//������������ ������ �� �������
	if(empty($arr))
		return '';

	if(!defined('GRID_ID'))
		define('GRID_ID', 0);

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
	$BT = PAS ? ' bor-t-dash' : '';
	$BR = PAS ? ' bor-r-dash' : '';
	$BB = PAS ? ' bor-b-dash' : '';
	$br1px = PAS ? 1 : 0;


	foreach($block as $y => $str) {
		$widthMax = $WM;
		$r = $str[0];

		$bt = $y ? $BT : '';

		$hSum += $r['h'];
		$bb = $y == $yEnd && $hMax > $hSum ? $BB : '';

		$send .=
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
			$cls[] = PAS ? 'prel' : '';
			$cls[] = $r['bg'];
			$cls[] = trim($bt);
			$cls[] = trim($bb);
			$cls[] = !$xEnd ? trim($BR) : '';
			$cls[] = $r['id'] == GRID_ID ? 'block-unit-grid' : '';
			$cls[] = $r['pos'];
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);

			$bor = explode(' ', $r['bor']);
			$borPx = $bor[3] + (PAS ? 0 : $bor[1]);
			$width = $r['width'] - $br1px - $borPx;

			$send .= '<td id="bl_'.$r['id'].'"'.
						' class="'.$cls.'"'.
						' style="'._blockStyle($r, $width).'"'.
				 (PAS ? ' val="'.$r['id'].'"' : '').
					 '>'.
							_blockSetka($r, $level, $r['is_spisok']).
							_blockChildHtml($r, $level + 1, $width).
	    					_elemDiv($r['elem']).
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
		$send .= '</table>';
	}

	return $send;
}
function _blockLevelChange($page_id) {//������ ��� ��������� ������ �������������� ������
	$sql = "SELECT *
			FROM `_block`
			WHERE `page_id`=".$page_id."
			  AND !`is_spisok`";
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
		$sel = _blockLevelDefine() == $n ? '' : ' cancel';
		$send .= '<button class="block-level-change vk small ml5'.$sel.'">'.$n.'</button>';
	}

	return '<div id="block-level" class="dib ml20">'.$send.'</div>';
}
function _blockLevelDefine($is_spisok=0) {//������� ������������� ������
	if($is_spisok)
		return empty($_COOKIE['block_level_spisok']) ? 1 : _num($_COOKIE['block_level_spisok']);

	return empty($_COOKIE['block_level']) ? 1 : _num($_COOKIE['block_level']);
}
function _blockSetka($r, $level, $is_spisok) {//����������� ����� ��� �������������� �����
	if(!PAS)
		return '';
	if(GRID_ID == $r['id'])
		return '';

	$bld = _blockLevelDefine($is_spisok);

	if($bld != $level)
		return '';

	$bld += $is_spisok ? 2 : 0;

	return '<div class="block-unit level'.$bld.' '.(GRID_ID ? ' grid' : '').'" val="'.$r['id'].'"></div>';
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

	$send[] = 'width:'.$width.'px';

	return implode(';', $send);
}
function _blockJS($page_id) {//������ �������� ������ � ������� JS
	if(!$arr = _pageBlockArr($page_id))
		return '';

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$send = array();
	foreach($arr as $id => $r) {
		$v = array();
		$v[] = 'id:'.$id;
		$v[] = 'pos:"'.$r['pos'].'"';
		$v[] = 'bg:"'.$r['bg'].'"';
		$v[] = 'bor:"'.$r['bor'].'"';
		$v[] = 'is_spisok:'._num($r['is_spisok']);
		$v[] = 'child:'.(!empty($child[$id]) ? 1 : 0);

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
function _blockArr($page_id) {//������ �������� ������ � ������� ��� �������� ����� JSON ��� BLOCK_ARR
	if(!$arr = _pageBlockArr($page_id))
		return '';

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$send = array();
	foreach($arr as $id => $r) {
		$v = array(
			'id' => _num($id),
			'pos' => $r['pos'],
			'bg' => $r['bg'],
			'bor' => $r['bor'],
			'is_spisok' => _num($r['is_spisok']),
			'child' => !empty($child[$id]) ? 1 : 0
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
function _blockChildHtml($block, $level, $width) {//������� ����� �� �����
	if(GRID_ID != $block['id'])
		return _blockLevel($block['child'], $width, $block['h'], $level);

	return _blockGrid($block['child']);
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
		'<div id="grid-add" class="pad5 bg-gr2 bor-e8 fs14 center color-555 curP over5 mt1">�������� ����</div> '.
		'<div class="pad5 center">'.
			'<button class="vk small orange" id="grid-save">���������</button>'.
			'<button class="vk small cancel ml5" id="grid-cancel">������</button>'.
		'</div>';
}


function _elemDiv($el) {//������������ div ��������
	if(!$el)
		return '';

	$tmp = !empty($el['tmp']);//������� ������ �������
	$attr_id = $tmp ? '' : ' id="pe_'.$el['id'].'"';

	$cls = array();
	$cls[] = $el['color'];
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = implode(' ', $cls);
	$cls = $cls ? ' class="'.$cls.'"' : '';


	return
	'<div'.$attr_id.$cls._elemStyle($el).'>'.
		_elemUnit($el).
	'</div>';

}
function _elemStyle($r) {//����� css ��� ��������
	$send = array();

	//�������
	$ex = explode(' ', $r['mar']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'margin:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _elemUnit($el) {//������������ �������� ��������
	if(!$el)
		return '';
	switch($el['dialog_id']) {
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
						'name' => $el['txt_1'],
						'click' => '_dialogOpen('._dialogValToId('button'.$el['id']).')',
						'color' => $color[$el['num_1']],
						'small' => $el['num_2'],
						'class' => $el['num_3'] ? 'w100p' : ''
					));
		case 3://menu
			return _pageElemMenu($el);
		case 4://head
			return '<div class="hd2">'.$el['txt_1'].'</div>';
		case 7://search
			return _search(array(
						'hold' => $el['txt_1'],
						'width' => $el['num_2'],
						'v' => $el['v']
					));
		case 9://link
			return '<a href="'.URL.'&p='.$el['num_1'].'">'.
						$el['txt_1'].
				   '</a>';
		case 10://������������ �����
			return _br($el['txt_1']);
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

			if(!$el['num_3'])
				return '������� �������� ����������';

			$sql = "SELECT *
					FROM `_spisok`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$spisok_id;
			if(!$sp = query_assoc($sql))
				return '������� �� ����������';

			$dialog = _dialogQuery($el['num_1']);
			$cmp = $dialog['component'][$el['num_3']];

			if($el['num_2'] == 331)
				return $cmp['label_name'];

			if($el['num_2'] == 332)
				return $sp[$cmp['col_name']];

			return 'spisok_id='.$spisok_id.' '.$el['num_3']._pr($cmp);
		case 12://�� ������� ��������
			if(!$el['txt_1'])
				return '������ �������� �������';
			if(!function_exists($el['txt_1']))
				return '������� �� ����������';
			return $el['txt_1']();
		case 14: return _spisokShow($el); //���������� ������
		case 15: return _spisokElemCount($el);//����� � ����������� ����� ������
	}
	//�������� ������ ������� (��� ���������)
	if($el['block']['is_spisok']) {
		if(isset($el['real_txt']))
			return $el['real_txt'];
		switch($el['num_1']) {
			case -1: return '{NUM}';//���������� �����
			case -2: return FullData(curTime(), 0, 1);//���� ��������
			case -4: return _br($el['txt_2']);//������������ �����
			default:
				if(!$dialog = _dialogQuery($el['num_3']))
					return '����������� id ������� ������: '.$el['num_3'];
				$cmp = $dialog['component'];
				$label_name = $cmp[$el['num_1']]['label_name'];
				switch($el['num_2']) {
					case 1: return $label_name;//�������� �������
					case 2: return '�������� "'.$label_name.'"';//�������� �������
					default: return '����������� ��� ���������� �������';
				}
		}
	}
	return'����������� �������='.$el['dialog_id'];
}
function _elemFontAllow($dialog_id) {//����������� � ���������� ������ ��� ���������� ��������� ��������
	$elem = array(
		0 => 1,
		10 => 1,
		11 => 1,
		15 => 1
	);
	return _num(@$elem[$dialog_id]);
}
function _elemColor() {//������ ������ ��� ������ � ������� JS, ��������� ���������
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
	return
	'<div class="mar20 bor-e8 w200 pad20" id="for-hint">'.
		'�������� ����� '.
		'<div class="icon icon-edit"></div>'.
		'<div class="icon icon-del"></div>'.
		'<div class="icon icon-del-red"></div>'.
		'<div class="icon icon-add"></div>'.
		'<div class="icon icon-ok"></div>'.
		'<div class="icon icon-set"></div>'.
		'<div class="icon icon-set-b"></div>'.
		'<div class="icon icon-off"></div>'.
		'<div class="icon icon-offf"></div>'.
		'<div class="icon icon-doc-add"></div>'.
		'<div class="icon icon-order"></div>'.
		'<div class="icon icon-client"></div>'.
		'<div class="icon icon-worker"></div>'.
		'<div class="icon icon-vk"></div>'.
		'<div class="icon icon-rub"></div>'.
		'<div class="icon icon-usd"></div>'.
		'<div class="icon icon-stat"></div>'.
		'<div class="icon icon-print"></div>'.
		'<div class="icon icon-out"></div>'.
		'<div class="icon icon-chain"></div>'.
		'<div class="icon icon-set-dot"></div>'.
		'<div class="icon icon-move"></div>'.
		'<div class="icon icon-move-x"></div>'.
		'<div class="icon icon-move-y"></div>'.
		'<div class="icon icon-sub"></div>'.
		'<div class="icon icon-join"></div>'.
		'<div class="icon icon-info"></div>'.
		'<div class="icon icon-hint"></div>'.
		' �������� �����'.
	'</div>'.

	'<button class="vk mar20" id="bbb">������ ��� ����������</button>'.

	'<div id="aaa">0</div>'.
	'<div class="mar20 bg-ccd">'.
		'<div class="icon icon-edit wh"></div>'.
		'<div class="icon icon-del wh"></div>'.
	'</div>';
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







