<?php
/*
	---=== ��������, ����������� �� �������� ===---

	*** ���� *** dialog_id=5 (�������� ���������: 4)
		txt_1 - �������� ����
		num_1 - ��������� (��������������, ������������)
		num_2 - ��� ����

	*** ��������� *** dialog_id=4

	*** ����� *** dialog_id=7

	*** ������ *** dialog_id=2

	*** ������ *** dialog_id=9

	*** ������ *** dialog_id=14

*/
function _button($v=array()) {//������ �� ��������
	$name = empty($v['name']) ? '������' : $v['name'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$small = empty($v['small']) ? '' : ' small';

	return
	'<button class="vk'.$color.$small.'"'.$click.'>'.
		$name.
	'</button>';
}

function _tooltip($msg, $left=0, $ugolSide='', $x2=0) {
	//x2: � ��� ������
	$x2 = $x2 ? ' x2' : '';
	return
		' _tooltip">'.
		'<div class="ttdiv'.$x2.'"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
			'<div class="ttmsg">'.$msg.'</div>'.
			'<div class="ttug'.($ugolSide ? ' '.$ugolSide : '').'"></div>'.
		'</div>';
}

function _iconEdit($v=array()) {//������ �������������� ������ � �������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',       //id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',      //�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '', //������ �� �������
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : '��������',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$v['id'].$v['onclick'].' class="icon icon-edit'.$v['class']._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDel($v=array()) {//������ �������� ������ � �������
	if(!empty($v['nodel']))
		return '';

	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ���, �� �������� ����������
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',//�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '' //������ �� �������
	);

	return '<div '.$v['id'].$v['onclick'].' class="icon icon-del'.$v['class']._tooltip('�������', -42, 'r').'</div>';
}



function _check($v=array()) {//������� �������
	$v = array(
		'id' => @$v['id'],
		'title' => @$v['title'],
		'value' => _bool(@$v['value']),
		'on' => _bool(@$v['value']) ? ' on' : '',
		'light' => _bool(@$v['light']) ? ' light' : '',
		'disabled' => _bool(@$v['disabled']) ? ' disabled' : '',
		'block' => _bool(@$v['block']) ? ' block' : ''
	);
	$title = $v['title'] ? ' title' : '';
	return
	'<input type="hidden" id="'.$v['id'].'" value="'.$v['value'].'" />'.
	'<div class="_check '.$v['on'].$v['block'].$v['disabled'].$v['light'].$title.'" id="'.$v['id'].'_check">'.
		$v['title'].
	'</div>';
}

function _search($v=array()) {//������� �����
	$v = array(
		'id' => @$v['id'],
		'width' => _num(@$v['width']) ? _num($v['width']) : 300,
		'hold' => @$v['hold'],
		'v' => @$v['v']
	);
	return
	'<div class="_search" style="width:'.$v['width'].'px">'.
		'<div class="icon icon-del fr'._dn($v['v']).'"></div>'.
		'<div class="_busy dib fr mr5 dn"></div>'.
		'<div class="hold'._dn(!$v['v']).'">'.$v['hold'].'</div>'.
		'<input type="text" style="width:'.($v['width'] - 77).'px" value="'.$v['v'].'" />'.
	'</div>';
}



function _dialogQuery($dialog_id) {//������ ����������� ����������� ����
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			  AND `id`=".$dialog_id;
	if(!$r = query_assoc($sql))
		return array();

	$sql = "SELECT *
			FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	$r['component'] = query_arr($sql);

	$sql = "SELECT `id`,`v`
			FROM `_dialog_component_v`
			WHERE `dialog_id`=".$dialog_id;
	$r['v_ass'] = query_ass($sql);

/* ���� ��������
	//���������� �������, ������������ � �������
	//������ �� `base_table`
	//��������: _page:razdel
	$ex = explode(':', $r['base_table']);
	$r['col'] = @$ex[1];
	$r['base_table'] = $ex[0];
*/
	return $r;
}
function _dialogValToId($val='') {//��������� id ������� �� ��������� ����� val
	//���� ������ ����� ���, �� �������� ������� � ����

	if(!$val = _txt($val))
		return 0;

	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `val`='".$val."'
			LIMIT 1";
	if($dialog_id = query_value($sql))
		return $dialog_id;

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`val`,

				`head_insert`,
				`button_insert_submit`,
				`button_insert_cancel`,

				`head_edit`,
				`button_edit_submit`,
				`button_edit_cancel`
			) VALUES (
				".APP_ID.",
				'".$val."',

				'".$val." - ��������',
				'������',
				'������',

				'".$val." - ����������',
				'���������',
				'������'
			)";
	query($sql);

	$dialog_id = query_insert_id('_dialog');
	
	$sql = "UPDATE `_dialog`
			SET `spisok_name`='������ ".$dialog_id."'
			WHERE `id`=".$dialog_id;
	query($sql);
	
	return $dialog_id;
}
function _dialogSpisokOn() {//��������� ������� ��������, ������� ����� ���� ��������: spisok_on=1
	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `sa` IN (0".(SA ? ",1" : '').")
			  AND `spisok_on`
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogPageList() {//��������� ������� ������� ����������
	$sql = "SELECT `id`,`name`
			FROM `_page`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogSpisokOnPage($page_id) {//��������� ������� ��������� ��������, ������� �������� �������� 
	if(!$page_id)
		return array();
	
	$sql = "SELECT `id`,`num_3`
			FROM `_page_element`
			WHERE `app_id` IN (".APP_ID.",0)
			  AND `page_id`=".$page_id."
			  AND `dialog_id`=14
			  AND `num_3`";
	if(!$res = query_arr($sql))
		return array();

	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `sa` IN (0".(SA ? ",1" : '').")
			  AND `id` IN ("._idsGet($res, 'num_3').")
			ORDER BY `id`";
	$ass = query_ass($sql);

	foreach($res as $id => $r)
		$res[$id] = $ass[$r['num_3']];

	return _selArray($res);
}





