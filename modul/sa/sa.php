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

		if(!$r['sub']) {
			$send .=
				'<div id="pb_'.$r['id'].'" class="'.$cls.$mh.'"'.$val.'>'.
					_pagePasBlock($r, $blockShow).
					_pageElSpisok($elem[$block_id]).
				'</div>';
			continue;
		}

		$send .= '<div id="pb_'.$r['id'].'" class="pb"'.$val.'>'.
					'<table class="w100p'.$mh.'"><tr>';
		$cSub = count($r['sub']) - 1;
		foreach($r['sub'] as $n => $sub) {
			$w = $sub['w'] ? ' style="width:'.$sub['w'].'px"' : '';
			$send .= '<td class="prel"'.$w.'>'.
						_pagePasBlock($sub, $blockShow, $n != $cSub).
						_pageElSpisok($elem[$sub['id']]);
		}
		$send .=	'</table>'.
				'</div>';
	}

	return
//	_pr($block).
	'<div class="pbsort0 prel">'.
		$send.
	'</div>'.

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
/*
function _page_show($page_id) {//����������� ���������� ��������
	$send = '';

	//������� ��������� �������� �� ������
	$sql = "SELECT
				*
			FROM `_page_element`
			WHERE `page_id`=".$page_id."
			ORDER BY `parent_id`,`sort`";
	$arr = query_arr($sql);

	foreach($arr as $id => $r)
		if($r['parent_id']) {
			if(!isset($arr[$r['parent_id']]['sub_ids']))
				$arr[$r['parent_id']]['sub_ids'] = array();
			$arr[$r['parent_id']]['sub_ids'][] = $id;
		}

	foreach($arr as $r) {
		$cls = PAS ? ' class="prel"' : '';
		$val = PAS ? ' val="'.$r['id'].'"' : '';

		if(empty($r['sub_ids'])) {
			if($r['parent_id'])
				continue;
			$send .=
				'<div id="pe_'.$r['id'].'"'.$cls.$val.'>'.
					_pagePasBlock($r).
					_pageUnit($r).
				'</div>';
			continue;
		}

		$send .= '<div id="pe_'.$r['id'].'">'.
					'<table class="w100p"><tr>';
		foreach($r['sub_ids'] as $sub_id) {
			$send .= '<td class="prel">'.
						_pagePasBlock($arr[$sub_id]).
						_pageUnit($arr[$sub_id]);
		}
		$send .= '</table></div>';
	}


	return
	'<div class="pas_sort prel">'.
		$send.
	'</div>'.
	'<script>_pageShow()</script>';
}
*/
function _pageElSpisok($elem) {//������ ��������� ������� html ��� ����������� �����
	if(!$elem)
		return '';

	$send = '';
	foreach($elem as $r) {
		$send .= '<div>'._pageUnit($r).'</div>';
	}

	return $send;
}
function _pageUnit($unit) {//������������ �������� ��������
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
						'grey' => $unit['num_1'],
						'width' => $unit['num_2'],
						'v' => $unit['v']
					));
		case 9://link
			return '<a href="'.URL.'&p='.$unit['num_1'].'">'.
						$unit['txt_1'].
				   '</a>';
		case 14://_spisok
			return _pageSpisok($unit);
	}
	return '����������� �������='.$unit['dialog_id'];
}
function _pagePasBlock($r, $show=0, $resize=0) {//��������� ������ ��� ��������������
	if(!PAS)
		return '';

	$block_id = $r['id'];
	$dn = $show ? '' : ' dn';
	$resize = $resize ? ' resize' : '';

	return
	'<div class="pas-block'.$resize.$dn.'" val="'.$block_id.'">'.
		'<div class="fl">'.
			$block_id.
			'<span class="fs11 grey"> w'.$r['w'].'</span>'.
			'<span class="fs11 color-acc"> '.$r['sort'].'</span>'.
		'</div>'.
		'<div class="pas-icon'.($resize ? ' mr5' : '').'">'.
			'<div class="icon icon-del-red fr mt1'._tooltip('������� ����', -42).'</div>'.
		'</div>'.
	'</div>';

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








