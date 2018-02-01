<?php
function _button($v=array()) {//������ �� ��������
	$attr_id = empty($v['attr_id']) ? '' : ' id="'.$v['attr_id'].'"';
	$name = empty($v['name']) ? '������' : $v['name'];
	$small = empty($v['small']) ? '' : ' small';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$cls = empty($v['class']) ? '' : ' '.$v['class'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';

	$width = '';
	if(isset($v['width']))
		switch($v['width']) {
			case 0: $width = ' style="width:100%"'; break;
			default: $width = ' style="width:'._num($v['width']).'px"';
		}

	return
	'<button class="vk'.$color.$small.$cls.'"'.$attr_id.$width.$click.$val.'>'.
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
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	$v = array(
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : '��������',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$click.$val.' class="icon icon-edit'.$cls._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDel($v=array()) {//������ �������� ������ � �������
	if(!empty($v['nodel']))
		return '';

	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ���, �� �������� ����������
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	return '<div'.$click.$val.' class="icon icon-del'.$cls._tooltip('�������', -42, 'r').'</div>';
}

function _check($v=array()) {//������� �������
	$attr_id = empty($v['attr_id']) ? 'check'.rand(1, 100000) : $v['attr_id'];

	$cls = '_check ';
	$cls .= empty($v['block']) ?    '' : ' block';       //display:block, ����� inline-block
	$cls .= empty($v['disabled']) ? '' : ' disabled';    //���������� ���������
	$cls .= isset($v['light']) && empty($v['light']) ?    '' : ' light';       //���� ������� �� �����, ����� �������
	$cls .= empty($v['class']) ?    '' : ' '.$v['class'];//�������������� ������

	$val = _bool(@$v['value']);
	$cls .= $val ? ' on' : '';      //������� ���������� ��� ���

	$title = empty($v['title']) ? '&nbsp;' : $v['title'];
	$cls .= empty($v['title']) ? '' : ' title'; //������ �� �������, ���� ���� �����

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />'.
	'<div class="'.$cls.'" id="'.$attr_id.'_check">'.
		$title.
	'</div>';
}
function _radio($v=array()) {//������� RADIO
	$attr_id = empty($v['attr_id']) ? 'radio'.rand(1, 100000) : $v['attr_id'];
	$title0 = @$v['title0'];
	$spisok = @$v['spisok'] ? $v['spisok'] : array();//���������� � ���� id => title
	$value = _num(@$v['value']);
	$dis = empty($v['disabled']) ? '' : ' disabled';
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;

	//���� ������ ���� � ������ ������� ��������, ������ ����� �� ��������
	$int = empty($spisok) ? 0 : $interval;
	$html = _radioUnit(0, $block, $title0, $int, $value == 0);

	if(is_array($spisok) && !empty($spisok)) {
		end($spisok);
		$idEnd = key($spisok);
		foreach($spisok as $id => $title) {
			//������ ����� ����� ���������� �������� �� ��������
			$int = $idEnd == $id ? 0 : $interval;
			$html .= _radioUnit($id, $block, $title, $int, $value == $id);
		}
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$light.'">'.
		$html.
	'</div>';
}
function _radioUnit($id, $block, $title, $interval, $on) {
	if(empty($title))
		return '';

	$title0 = !$id ? 'title0' : '';
	$on = $on ? ' on' : '';
	$ms = $block ? 'bottom' : 'right';
	$interval = $block ? $interval : 12;
	$interval = $interval ? ' style="margin-'.$ms.':'.$interval.'px"' : '';
	return
	'<div class="'.$title0.$on.'" val="'.$id.'"'.$interval.'>'.
		$title.
	'</div>';
}
function _select($v=array()) {//���������� ����
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_select disabled dib" id="'.$attr_id.'_select"'.$width.'">'.
		'<table class="w100p">'.
			'<tr><td><input type="text" class="select-inp"'.$placeholder.' readonly />'.
				'<td class="arrow">'.
		'</table>'.
	'</div>';
}
function _count($v=array()) {//���� ����������
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '50px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$value = _num(@$v['value']);
	return
	'<div class="_count disabled" id="'.$attr_id.'_count"'.$width.'>'.
		'<input type="text" readonly id="'.$attr_id.'" value="'.$value.'" />'.
		'<div class="but"></div>'.
		'<div class="but but-b"></div>'.
	'</div>';
}
function _search($v=array()) {//������� �����
	$attr_id = empty($v['attr_id']) ? 'search'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$dis = empty($v['disabled']) ? '' : ' disabled';
	$readonly = $dis ? ' readonly' : '';
	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$v = trim(@$v['v']);

	return
	'<div class="_search'.$dis.'"'.$width.' id="'.$attr_id.'_search">'.
		'<table class="w100p">'.
			'<tr><td class="w15 pl5">'.
					'<div class="icon icon-search curD"></div>'.
				'<td><input type="text" id="'.$attr_id.'"'.$placeholder.$readonly.' value="'.$v.'" />'.
				'<td class="w25 center">'.
					'<div class="icon icon-del pl'._dn($v).'"></div>'.
		'</table>'.
	'</div>';
}

function _emptyMin($msg) {
	return '<div class="_empty min mar10">'.$msg.'</div>';
}

function _dialogTest() {//�������� id �������, �������� ������ ������, ���� ��� ������
	//���� dialog_id ������� - �������� ���
	if($dialog_id = _num(@$_POST['dialog_id']))
		return $dialog_id;
	if(!$block_id = _num(@$_POST['block_id']))
		return false;

	//��������� ��������-������ ��� ���������� ������ �������
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id."
			  AND `dialog_id`=2
			LIMIT 1";
	if(!$elem = query_assoc($sql))
		return false;

	//����� ������ ������ ��� ��� ��������
	if($elem['num_4'])
		return $elem['num_4'];

	$sql = "INSERT INTO `_dialog` (`app_id`) VALUES (".APP_ID.")";
	query($sql);

	$sql = "SELECT IFNULL(MAX(`num`),0)+1
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$num = query_value($sql);

	$dialog_id = query_insert_id('_dialog');

	$sql = "UPDATE `_dialog`
			SET `num`=".$num.",
				`element_name`='������� ".$dialog_id."',
				`spisok_name`='������ ".$num."'
			WHERE `id`=".$dialog_id;
	query($sql);

	$sql = "UPDATE `_element`
			SET `num_4`=".$dialog_id."
			WHERE `id`=".$elem['id'];
	query($sql);

	return $dialog_id;
}
function _dialogQuery($dialog_id) {//������ ����������� ����������� ����
	if($dialog = _cache())
		return $dialog;

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			  AND `id`=".$dialog_id;
	if(!$dialog = query_assoc($sql))
		return array();

	//��������� ������ �������, �������������� � �������
	$col = array();
	$sql = "DESCRIBE `".$dialog['base_table']."`";
	foreach(query_array($sql) as $r)
		$col[$r['Field']] = 1;

	_cache('clear', 'dialog_'.$dialog_id);
	$dialog['blk'] = _block('dialog', $dialog_id, 'block_arr');
	$dialog['cmp'] = _block('dialog', $dialog_id, 'elem_arr');
	$dialog['cmp_utf8'] = _block('dialog', $dialog_id, 'elem_utf8');
	$dialog['field'] = $col;

	return _cache($dialog);
}
function _dialogParam($dialog_id, $param) {//��������� ����������� ��������� �������
	$dialog = _dialogQuery($dialog_id);
	if(!isset($dialog[$param]))
		return '����������� �������� �������: '.$param;

	$send = $dialog[$param];

	if(!is_array($send) && preg_match(REGEXP_NUMERIC, $send))
		return _num($send);

	return $send;
}
function _dialogSpisokOn($dialog_id, $block_id, $elem_id) {//��������� ������� ��������, ������� ����� ���� ��������: spisok_on=1
	$cond = "`spisok_on`";
	$cond .= " AND `app_id` IN (0,".APP_ID.")";


	//��������� id �������, ������� �������� �������, ����� ���� ������ ��� �������� � ����� ���� (��� ������)
	$dialog = _dialogQuery($dialog_id);
	if($dialog['base_table'] == '_element') {
		//���� �������������� - ��������� id ����� �� ��������
		if($elem_id) {
			$sql = "SELECT `block_id`
					FROM `_element`
					WHERE `id`=".$elem_id;
			$block_id = query_value($sql);
		}
		//���� ������� �������� � ����
		$sql = "SELECT `obj_id`
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id`=".$block_id;
		if($dialog_id_skip = query_value($sql))
			$cond .= " AND `id`!=".$dialog_id_skip;
	}

	$sql = "SELECT *
			FROM `_dialog`
			WHERE ".$cond."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	$saArr = array();
	foreach($arr as $r) {
		if($r['sa'] || !$r['app_id']) {
			$saArr[] = $r;
			continue;
		}
		$send[] = array(
			'id' => _num($r['id']),
			'title' => utf8($r['spisok_name'])
		);
	}


	//������, ��������� ������ SA
	if(SA) {
		$send[] = array(
			'info' => 1,
			'title' => utf8('SA-������:')
		);
		foreach($saArr as $r)
			$send[] = array(
				'id' => _num($r['id']),
				'title' => utf8($r['spisok_name']),
				'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.utf8($r['spisok_name']).'</div>'
			);
	}


	return $send;
}
function _dialogSpisokOnPage($block_id) {//��������� ������� ��������, ������� ����� ���� ��������: spisok_on=1
/*
	 �������� ����� ������, ���������� � ������� �������
	$block_id - �������� ����, �� �������� ������������ ������

*/

	if(!$block = _blockQuery($block_id))
		return array();

	//������ ����������� ��� ������ �������� 14 � 23
	//���������������� ���������� �������� id ��������� (� �� ��������)

	if(!$elm = _block($block['obj_name'], $block['obj_id'], 'elem_arr'))
		return array();

	$send = array();
	foreach($elm as $elem_id => $r) {
		if($r['dialog_id'] != 14 && $r['dialog_id'] != 23)
			continue;
		$dialog = _dialogQuery($r['num_1']);
		$send[$elem_id] = utf8($dialog['spisok_name'].' (� '.$block['obj_name'].'-����� '.$r['block_id'].')');
	}

	return $send;
}
function _dialogSpisokGetPage($page_id) {//������ ��������, ������� ��������� �� �������� ����� GET
	if(!$page_id)
		return array();

	//�����������, ���� �� ������, ����������� �� ��� ��������
	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `insert_action_id`=2
			  AND `insert_action_page_id`=".$page_id;
	if(!$send = query_ass($sql))
		return array();

	return _selArray($send);
}
function _dialogSelArray($sa_only=0) {//������ �������� ��� Select - �������� ����� AJAX
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ',0' : '').")
			  AND `sa` IN(0".(SA ? ',1' : '').")
			ORDER BY `app_id` DESC,`id`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	$saFlag = $sa_only;
	foreach($arr as $r) {
		if(!$saFlag && !$r['app_id']) {//������� ����� ��� SA
			$spisok[] = array(
				'info' => 1,
				'title' => utf8('SA-�������:')
			);
			$saFlag = 1;
		}
		if($sa_only && $r['app_id'])
			continue;
		$u = array(
			'id' => _num($r['id']),
			'title' => utf8($r['insert_head'])
		);
		if(!$r['app_id'])
			$u['content'] = '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'"><b>'.$r['id'].'</b>. '.utf8($r['insert_head']).'</div>';
		$spisok[] = $u;
	}

	return $spisok;
}

function _elemQuery($elem_id) {//������ ������ ��������
	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".abs($elem_id);
	$elem = query_assoc($sql);

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$elem['block_id'];
	$elem['block'] = query_assoc($sql);

	return $elem;
}
function _blockQuery($block_id) {//������ ������ �����
	if(empty($block_id))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$block_id;
	if(!$block = query_assoc($sql))
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id;
	$block['elem'] = query_assoc($sql);

	return $block;
}

function _elementChoose($unit) {
	if(empty($unit['source']))
		return _emptyMin('������� _elementChoose');
	if(!$block_id = _num($unit['source']['block_id']))
		return _emptyMin('����������� id ��������� �����.');
	if(!$BL = _blockQuery($block_id))
		return _emptyMin('��������� ����� id'.$block_id.' �� ����������.');

	$head = '';
	$content = '';
	$sql = "SELECT *
			FROM `_dialog_group`
			ORDER BY `sort`";
	$group = query_arr($sql);

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `element_group_id`
			ORDER BY `id`";
	$elem = query_arr($sql);

	$c = count($group);
	foreach($group as $id => $r) {
		$sel = _dn($id != 1, 'sel');
		$first = _dn($id != 1, 'first');
		$last = _dn(--$c, 'last');
		$head .=
			'<table class="el-group-head w100p bs5 curP over1'.$sel.$first.$last.'" val="'.$id.'">'.
				'<tr>'.
	   ($r['img'] ? '<td class="w50 center"><img src="img/'.$r['img'].'">' : '').
					'<td class="fs14 '.($r['sa'] ? 'red pl5' : 'blue').'">'.$r['name'].
			'</table>';

		$content .= '<div id="cnt_'.$id.'" class="cnt'._dn($id == 1).'">';
		$n = 1;
		foreach($elem as $el)
			if($el['element_group_id'] == $id) {
				$content .=
					'<div class="dialog-open '.($el['sa'] ? 'red' : 'color-555').'" val="dialog_id:'.$el['id'].',block_id:'.$block_id.'">'.
						'<div class="dib w25 fs12 r">'.$n++.'.</div> '.
						'<b>'.$el['element_name'].'</b>'.
						'<div class="elem-img eli'.$el['id'].' mt5"></div>'.
					'</div>';
			}
		$content .=	'</div>';
	}

	return
		'<table id="elem-group" class="w100p">'.
			'<tr><td class="w150 top">'.$head.
				'<td id="elem-group-content" class="top">'.
					'<div class="cnt-div">'.$content.'<div>'.
		'</table>'.
		'<script>_elemGroup()</script>';
}

