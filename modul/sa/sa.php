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






function _page_show($page_id) {//����������� ���������� ��������
	$send = '';

	//�������� ��������
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$page_id."
			ORDER BY `sort`";
	foreach(query_arr($sql) as $id => $r) {
		switch($r['dialog_id']) {
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
				$r['cls'] .= ' pad5 ';
				$send .= _pageEl($r, _button(array(
							'name' => $r['txt_1'],
							'click' => '_dialogOpen('._dialogValToId('button'.$id).')',
							'color' => $color[$r['num_1']],
							'small' => $r['num_2']
						)));
				break;
			case 3://menu
				$send .= _pageEl($r, _pageElementMenu($r['num_1']));
				break;
			case 4://head
				$send .= _pageEl($r, '<div class="hd2">'.$r['txt_1'].'</div>');
				break;
			case 7://search
				$send .= _pageEl($r, _search(array(
							'hold' => $r['txt_1'],
							'grey' => $r['num_1'],
							'width' => $r['num_2'],
							'v' => $r['v']
						)));
				break;
			case 9://link
				$send .= _pageEl($r, '<a href="'.URL.'&p='.$r['num_1'].'">'.
										$r['txt_1'].
						             '</a>'
								);
				break;
			case 14://_spisok
				$send .= _pageEl($r, _pageSpisok($r));
				break;
		}
	}


	return
	'<div class="pas_sort prel">'.
		$send.

		'<div class="pe pas prel" id="pe_2_120" val="120">'.
			'<table class="w100p">'.
				'<tr><td class="prel top w300">'.
						'<div class="pas-block dn"></div>'.
						'<button class="vk green">������</button>'.
					'<td class="prel top">'.
						'<div class="pas-block dn"></div>'.
						'123'.
			'</table>'.
		'</div>'.

	'</div>'.
	'<script>_pageShow()</script>';
}
function _pageEl($r, $cont) {//����� �������� ������� � ������ ���������
	$attr_id = ' id="pe_'.$r['dialog_id'].'_'.$r['id'].'"';
	$valId = PAS ? ' val="'.$r['id'].'"' : '';
	$cls = PAS ? 'pas prel' : '';
	return
	'<div class="pe '.$r['cls'].$cls.'"'.$attr_id.$valId.'>'.
		(PAS ? '<div class="pas-block dn"></div>' : '').
		$cont.
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








