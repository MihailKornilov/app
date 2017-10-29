<?php
function sa_page_spisok() {
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `dialog_id`=1
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th>��������'.
				'<th class="w70">App any'.
				'<th class="w70">SA only'.
				'<th class="w200">�������'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$send .=
				'<tr><td class="r grey">'.$r['id'].
					'<td><a href="'.URL.'&p='.$r['id'].'">'.$r['name'].'</a>'.
					'<td class="'.($r['app_id'] ? '' : 'bg-dfd').'">'.
					'<td class="'.($r['sa'] ? 'bg-ccd' : '').'">'.
					'<td>'.$r['func'].
					'<td class="wsnw">'
						._iconEdit(array('onclick'=>'_dialogOpen('.$r['dialog_id'].','.$r['id'].')'))
						._iconDel();
	}

	$send .= '</table>';

	return $send;
}




function _page_show($page_id, $blockShow=0) {
//	$dialog = _dialogQuery(2);
	
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
	$arr = query_arr($sql);
	foreach($arr as $r)
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
	'</div>'
: '').

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
				`app_id`,
				`page_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
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
		'<div class="pe prel '.$r['color'].' '.$r['font'].' '.$r['size'].'" id="pe_'.$r['id'].'"'._pageElemStyle($r).'>'.
			_pageElemPas($r).
			_pageElemUnit($r).
		'</div>';
	}

	return $send;
}
function _pageElemPas($r) {
	if(!PAS)
		return '';

	return
	'<div class="elem-pas" val="'.$r['id'].'">'.
		'<div class="elem-icon">'.
			'<div class="icon icon-sort curM mr3'._tooltip('�������� �������<br />������ �����', -57, '', 1).'</div>'.
			'<div class="icon icon-setup mr3'._tooltip('����� ��������', -50).'</div>'.
			'<div onclick="_dialogOpen('.$r['dialog_id'].','.$r['id'].')" class="icon icon-edit mr3'._tooltip('��������� �������', -58).'</div>'.
			'<div onclick="_dialogOpen(6,'.$r['id'].')" class="icon icon-del-red'._tooltip('������� �������', -53).'</div>'.
		'</div>'.
	'</div>';
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
			return _pageElementMenu($unit['num_1']);
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
			return $unit['txt_1'];
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
		case 14://_spisok
			return _pageSpisok($unit);
	}
	return '����������� �������='.$unit['dialog_id'];
}
function _pageElemStyle($r) {//����� css ��� ��������
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
function _pageElemFontAllow($dialog_id) {//����������� � ���������� ������ ��� ���������� ��������� ��������
	$elem = array(
		10 => 1,
		11 => 1
	);
	return isset($elem[$dialog_id]);
}

function _pageElementMenu($menu_id) {//������� ��������: ����
	$sql = "SELECT *
			FROM `_page_menu`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$menu_id;
	if(!$menu = query_assoc($sql))
		return '�������������� ����.';

	$sql = "SELECT *
			FROM `_page_menu_razdel`
			WHERE `app_id`=".APP_ID."
			  AND `menu_id`=".$menu_id;
	if(!$spisok = query_arr($sql))
		return '�� �������� ����.';

	$razdel = '';
	foreach($spisok as $r) {
		$sel = PAGE_ID == $r['uid'] ? ' sel' : '';
		$href = $r['uid'] ? ' href="'.URL.'&p='.$r['uid'].'"' : '';
		$razdel .=
			'<a class="link'.$sel.'"'.$href.'>'.
				$r['name'].
			'</a>';
	}

	return '<div class="_menu0">'.$razdel.'</div>';
}




function _pageSpisok($pe) {//������, ��������� �� ��������
	$page_id = $pe['page_id'];

	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	$spTypeId = $pe['num_1'];    //������� ��� ������: [181] => ������� [182] => ������
	$spLimit = $dv[$pe['num_2']];//�����

	//������, ����� ������� �������� ������ ������
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);
	$spElement = $spDialog['component']; //�������� ������
	$spTable = $spDialog['base_table'];

	//��������� ������ ������
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `dialog_id`=".$dialog_id."
			  "._pageSpisokFilterSearch($pe, $spDialog)."
			ORDER BY `dtime_add` DESC
			LIMIT ".$spLimit;
	$spisok = query_arr($sql);

	$html = '';

	//����� �������� ����
	switch($spTypeId) {
		case 181://�������
			if(!$spisok) {
				$html = '<div class="_empty">'.$pe['txt_1'].'</div>';
				break;
			}
			$html = '<table class="_stab">'.
						'<tr>'.
							'<th class="w15">id';//ID
			foreach($spElement as $el) {
				if($el['type_id'] == 7)
					continue;
				$html .= '<th>'.$el['label_name'];
			}
			$html .= '<th class="w15">';//���������
			foreach($spisok as $sp) {
				$html .= '<tr><td class="r grey">'.$sp['id'];
				foreach($spElement as $el) {
					if($el['type_id'] == 7)
						continue;
					if($el['col_name'] == 'app_any_spisok')
						$v = '';
					else {
						$v = $sp[$el['col_name']];
//						if(strlen($val) && $el['col_name'] == $spElement[$comp_id]['col_name'])
//							$v = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $v, 1);
					}
					$html .= '<td>'.$v;
				}
				$html .= '<td class="wsnw">'
							._iconEdit(array('onclick'=>'_dialogOpen('.$dialog_id.','.$sp['id'].')'));
							//._iconDel();
			}

			$html .= '</table>';
			break;
		case 182://������
			foreach($spElement as $el) {
				$html .= '<div>'.$el['label_name'].'</div>';
			}
			break;
		default:
			$html = '����������� ������� ��� ������: '.$spTypeId;
	}

	return $html;
}
function _pageSpisokFilterSearch($pe, $spDialog) {//��������� �������� �������-������ ��� ������
	//���� ����� �� ������������ �� �� ����� ��������, �� �����
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	//��������� �������� �������� ������, ������������� �� ��������, ��� ��������� ������ �������������� �� ���� ������
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_3`=".$pe['id'];
	if(!$search = query_assoc($sql))
		return '';

	$arr = array();
	foreach($colIds as $cmp_id)
		$arr[] = "`".$spDialog['component'][$cmp_id]['col_name']."` LIKE '%".addslashes($search['v'])."%'";

	return " AND (".implode($arr, ' OR ').")";
}

function _pageSpisokUnit() {
	if(!$unit_id = _num(@$_GET['id']))
		return '';

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return '������ �� ����������';

	return _pr($unit);
}


function _page_menu_spisok() {//������ ����
	$sql = "SELECT
				*,
				'' `razdel`
			FROM `_page_menu`
			ORDER BY `id`";
	$spisok = query_arr($sql);

	$sql = "SELECT
				*,
				'' `razdel`
			FROM `_page_menu_razdel`
			ORDER BY `sort`";
	$razdel = query_arr($sql);
	foreach($razdel as $r) {
		$spisok[$r['menu_id']]['razdel'][] = $r;
	}

	$send =
		'<table class="_stab">'.
			'<tr><th class="w15">id'.
				'<th class="w200">��������'.
				'<th>�������'.
				'<th class="w35">';
	foreach($spisok as $r) {
		$razdel = '';
		if($r['razdel']) {
			foreach($r['razdel'] as $rz) {
				$razdel .=
					'<div>'.
						'<a onclick="_dialogOpen('._dialogValToId('page_menu_razdel').','.$rz['id'].')">'.
							$rz['name'].
						'<a>'.
					'</div>';
			}
		}
		$send .=
				'<tr><td class="r grey topi">'.$r['id'].
					'<td class="b topi">'.$r['name'].
					'<td>'.$razdel.
					'<td class="wsnw">'.
						'<div onclick="_dialogOpen('._dialogValToId('page_menu_razdel').',0,'.$r['id'].')" class="icon icon-avai'._tooltip('�������� ������', -94, 'r').'</div>'.
						_iconEdit(array('onclick'=>'_dialogOpen('.$r['dialog_id'].','.$r['id'].')')).
						_iconDel();
	}

	$send .= '</table>';

	return $send;
}


function _page_div() {//todo ����
	return
	'<style>'.
		'.t-block{background-color:#aee;border:transparent solid 1px;position:absolute;left:0;right:0;top:0;bottom:0;opacity:.5}'.
		'.t-block:hover{background-color:#aff;border:#f00 solid 1px}'.
		'.bg-gr3:not(:hover) .t-block{display:none}'.
	'</style>'.
	'<div class="bg-dfd">'.
		'<div class="bg-gr3 w200 dib curP prel" style="height:100%">'.
			'<div class="t-block"></div>'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'<a>2354</a><br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
			'1<br />'.
		'</div>'.
		'<div class="bg-ddf w400 dib" style="height:inherit">124<br />456</div>'.
//		'<div class="bg-eee fl w500">124</div>'.
	'</div>';
}








