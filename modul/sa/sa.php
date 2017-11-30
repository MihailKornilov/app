<?php


function _pageShow($page_id, $blockShow=0) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();


	_pageBlockTest($page_id);

	//��������� ������ ������
	$sql = "SELECT *
			FROM `_page_block`
			WHERE `page_id`=".$page_id."
			ORDER BY `parent_id`,`sort`";
	$arr = query_arr($sql);
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
	_pageSpisokUnit().

(PAS ?
	'<div id="page-block-add" class="center mt1 pad15 bg-gr1 bor-f0 over1 curP'._dn($blockShow).'">'.
		'<tt class="fs15 color-555">�������� ����� ����</tt>'.
	'</div>'.
	'<script>'.
		'var ELEM_ARR={'._pageElemArr($elem_arr).'},'.
			'ELEM_COLOR={'._pageElemColor().'};'.
	'</script>'
: '').
//	_pr($page).

	'<script>_pageShow()</script>';
}
function _pageBlockTest($page_id) {//�������� �������� �� ������� ���� �� ������ �����
	//���� ������ ���, �� �������� ������ � ���������� � ���� ���� ��������� �� ��������

	$sql = "SELECT `id`
			FROM `_page_block`
			WHERE `page_id`=".$page_id."
			LIMIT 1";
	if($block_id = query_value($sql))
		return;

	$sql = "INSERT INTO `_page_block` (
				`page_id`,
				`viewer_id_add`
			) VALUES (
				".$page_id.",
				".VIEWER_ID."
			)";
	query($sql);

	$block_id = query_insert_id('_page_block');

	$sql = "UPDATE `_page_element`
			SET `block_id`=".$block_id."
			WHERE `page_id`=".$page_id;
	query($sql);
}
function _pageBlockStyle($r) {//����� css ��� �����
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

	if($r['w'])
		$send[] = 'width:'.$r['w'].'px';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
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
		$send .=
		'<div class="pe prel '.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'" id="pe_'.$r['id'].'"'._pageElemStyle($r).'>'.
			_pageElemPas($r).
			_pageElemUnit($r).
		'</div>';
	}

	return $send;
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
			'type:"'.$r['type'].'",'.
			'pos:"'.$r['pos'].'",'.
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
	switch($unit['dialog_id']) {
		case 2://button
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
						'small' => $unit['num_2']
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
		case 14: return _spisokShow($unit); //_spisok
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

//	$send[] = 'box-sizing:padding-box';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _pageElemFontAllow($dialog_id) {//����������� � ���������� ������ ��� ���������� ��������� ��������
	$elem = array(
		10 => 1,
		11 => 1
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







function _pageSpisokUnit() {
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








