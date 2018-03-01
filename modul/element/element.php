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
function _calendar($v=array()) {//���� ���������
	$attr_id = empty($v['attr_id']) ? 'calendar'.rand(1, 100000) : $v['attr_id'];

	if(!$value = @$v['value'])
		$value = strftime('%Y-%m-%d');

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_calendar disabled" id="'.$attr_id.'_calendar">'.
		'<div class="icon icon-calendar"></div>'.
		'<input type="text" class="cal-inp" readonly value="'.FullData($value).'" />'.
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
function _colorJS() {//������ ������ ��� ������ � ������� JS, ��������� ���������
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
		'"red":["#e22","�������"],'.
		'"color-ref":["#800","Ҹ���-�������"],'.
		'"color-del":["#a66","Ҹ���-��������"],'.
		'"color-vin":["#c88","��������"]';
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
			  AND `dialog_id` IN (2,59)
			LIMIT 1";
	if(!$elem = query_assoc($sql))
		return false;

	//����� ������ ������ ��� ��� ��������
	if($elem['num_4'])
		return $elem['num_4'];

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".USER_ID."
			)";
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

	//���������� ���� �������, � ������� ��������� ���� � �������
	$bl = _blockQuery($block_id);
	_cache('clear', $bl['obj_name'].'_'.$bl['obj_id']);

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

	//������ �������, �������������� � �������
	$field = array();
	$sql = "DESCRIBE `".$dialog['base_table']."`";
	foreach(query_array($sql) as $r)
		$field[$r['Field']] = 1;

	_cache('clear', 'dialog_'.$dialog_id);
	$dialog['blk'] = _block('dialog', $dialog_id, 'block_arr');
	$dialog['cmp'] = _block('dialog', $dialog_id, 'elem_arr');
	$dialog['cmp_utf8'] = _block('dialog', $dialog_id, 'elem_utf8');
	$dialog['field'] = $field;

	//id ��������� ��������� ��������� ������� ������� ��������
	foreach(array(1,2,3) as $n) {
		$sql = "SELECT `id`
				FROM `_element`
				WHERE `dialog_id`=67
				  AND `num_1`=".$n."
				  AND `num_2`=".$dialog_id."
				LIMIT 1";
		$elem_id = query_value($sql);
		$dialog['history'][$n]['elem_id'] = $elem_id;

		$tmp = '';
		if($elem_id) {
			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=-".$elem_id."
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$num_1 = $r['num_1'] ? '['.$r['num_1'].'] ' : '';
				$tmp.= $r['txt_7'].' '.$num_1.$r['txt_8'].' ';
			}
		}
		$dialog['history'][$n]['tmp'] = trim($tmp);
	}

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
function _dialogSpisokOnConnect($block_id, $elem_id) {//��������� ��������-�������, ������� ��������� � �������� (���������) �������
/*
	$block_id - �������� ����, �� �������� ������������ ������
	�������� ���������� ����� ������� [29], �� ���� ����� ������������� ������
	���������������� ���������� �������� id ��������� (� �� ��������)
*/

	//��������� ��������� �����, ���� �������������� ��������
	if($elem_id) {
		if(!$EL = _elemQuery($elem_id))
			return array();
		$block_id = $EL['block_id'];
	}

	if(!$BL = _blockQuery($block_id))
		return array();

	if($BL['obj_name'] != 'dialog')
		return array();

	$dialog_id = $BL['obj_id'];

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$dialog_id."
			ORDER BY `id`";
	if(!$elem = query_arr($sql))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			ORDER BY `obj_id`";
	if(!$block = query_arr($sql))
		return array();

	//���������� ������ ��� ������� ������� (connect count)
	$sql = "SELECT
				`obj_id`,
				COUNT(`id`)-1
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			GROUP BY `obj_id`";
	$cc = query_ass($sql);

	$send = array();
	foreach($elem as $elem_id => $r) {
		$BL = $block[$r['block_id']];
		$obj_id = _num($BL['obj_id']);
		$dialog = _dialogQuery($obj_id);
		$send[_num($elem_id)] =
			utf8(
				$dialog['spisok_name'].
					($cc[$obj_id] ? ' (� ����� '.$r['block_id'].')' : '')
			);
	}

	return $send;
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
	if(!$elem = query_assoc($sql))
		return array();

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

function _elemTitle($elem_id) {//��� �������� ��� ��� �����
	if(!$el = _elemQuery($elem_id))
		return '';
	if($el['dialog_id'] != 67)
		return '';

	_cache('clear', '_dialogQuery'.$el['num_2']);
	$dlg = _dialogQuery($el['num_2']);
	return $dlg['history'][$el['num_1']]['tmp'];
}

function _elementChoose($el, $unit) {//����� ��������� ��� ������� � ����
	if(!$block_id = _num($unit['source']['block_id'], 1))
		return _emptyMin('����������� id ��������� �����.');
	if(!$BL = _blockQuery($block_id))
		return _emptyMin('��������� ����� id'.$block_id.' �� ����������.');

	define('BLOCK_PAGE',   $BL['obj_name'] == 'page');
	define('BLOCK_DIALOG', $BL['obj_name'] == 'dialog');
	define('BLOCK_SPISOK', $BL['obj_name'] == 'spisok');
	define('_44_ACCESS', $unit['source']['unit_id'] == -111);//������� �����
	define('TD_PASTE', $unit['source']['unit_id'] == -112); //������ �������

	//�����������, ��������� �� �������� �������� ������
	$spisok_exist = false;
	if(BLOCK_PAGE) {
		$page = _page($BL['obj_id']);
		$spisok_exist = $page['spisok_id'];
	}
	define('IS_SPISOK_UNIT', BLOCK_SPISOK || TD_PASTE || $spisok_exist);

	$head = '';
	$content = '';
	$sql = "SELECT *
			FROM `_dialog_group`
			WHERE `sa` IN (0,".SA.")
			ORDER BY `sort`";
	if(!$group = query_arr($sql))
		return _emptyMin('����������� ������ ���������.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `element_group_id` IN ("._idsGet($group).")
			  AND `sa` IN (0,".SA.")
			ORDER BY `sort`,`id`";
	if(!$elem = query_arr($sql))
		return _emptyMin('��� ��������� ��� �����������.');

	//����������� ��������� � ������ � ������ ������ �����������
	foreach($elem as $id => $r) {
		if(_44_ACCESS && !$r['element_44_access'])
			continue;
		if(TD_PASTE && !$r['element_td_paste'])
			continue;
//		if(IS_SPISOK_UNIT && !$r['element_is_spisok_unit'])
//			continue;

		$show = false;

		if(BLOCK_PAGE && $r['element_page_paste'])
			$show = true;
		if(BLOCK_DIALOG && $r['element_dialog_paste'])
			$show = true;
		if(BLOCK_SPISOK && $r['element_spisok_paste'])
			$show = true;
		if($r['element_is_spisok_unit'] && !IS_SPISOK_UNIT)
			$show = false;

		if($show)
			$group[$r['element_group_id']]['elem'][] = $r;
	}

	$debug =
		(DEBUG ?
			'<div class="line-t pad10 bg-ffe">'.
				'<div class="'.(BLOCK_PAGE ? 'color-pay b' : 'pale').'">BLOCK_PAGE</div>'.
				'<div class="'.(BLOCK_DIALOG ? 'color-pay b' : 'pale').'">BLOCK_DIALOG</div>'.
				'<div class="'.(BLOCK_SPISOK ? 'color-pay b' : 'pale').'">BLOCK_SPISOK</div>'.
				'<div class="'.($spisok_exist ? 'color-pay b' : 'pale').'">$spisok_exist</div>'.
				'<div class="'.(IS_SPISOK_UNIT ? 'color-pay b' : 'pale').'">IS_SPISOK_UNIT</div>'.
				'<div class="'.(_44_ACCESS ? 'color-pay b' : 'pale').'">_44_ACCESS</div>'.
				'<div class="'.(TD_PASTE ? 'color-pay b' : 'pale').'">TD_PASTE</div>'.
				_pr($unit).
//				_pr($BL).
			'</div>'
		: '');

	foreach($group as $id => $r)
		if(empty($r['elem']))
			unset($group[$id]);

	if(empty($group))
		return _emptyMin('��� ��������� ��� �����������.').$debug;

	reset($group);
	$firstId = key($group);
	foreach($group as $id => $r) {
		$sel = _dn($id != $firstId, 'sel');
		$first = _dn($id != $firstId, 'first');
		$head .=
			'<table class="el-group-head'.$first.$sel.'" val="'.$id.'">'.
				'<tr>'.
	   ($r['img'] ? '<td class="w50 center"><img src="img/'.$r['img'].'">' : '').
					'<td class="fs14 '.($r['sa'] ? 'red pl5' : 'blue').'">'.$r['name'].
			'</table>';

		$content .= '<dl id="cnt_'.$id.'" class="cnt'._dn($id == $firstId).'">';
		$n = 1;
		foreach($r['elem'] as $el)
				$content .=
					'<dd val="'.$el['id'].'">'.
					'<div class="elem-unit '.($el['sa'] ? 'red' : 'color-555').'" val="'.$el['id'].'">'.
				  (SA ? '<div class="icon icon-move-y fr pl"></div><div class="icon icon-edit fr pl mr3"></div>' : '').
						'<div class="dib w25 fs12 r">'.$n++.'.</div> '.
						'<b>'.$el['element_name'].'</b>'.
						'<div class="elem-img eli'.$el['id'].' mt5"></div>'.
					'</div>'.
					'</dd>';
		$content .=	'</dl>';
	}

	return
		'<table id="elem-group" class="w100p">'.
			'<tr><td class="w150 top prel">'.
					'<div id="head-back"></div>'.
					$head.
				'<td id="elem-group-content" class="top">'.
					'<div class="cnt-div">'.$content.'<div>'.
		'</table>'.
		$debug;
}

function _filterCheckSetup() {//��������� ������� ������� ��� ������� (����������� ����� [12])
	return '';
}

function _historySetup($el, $unit) {//��������� ������� ������� �������� (����������� ����� [12])
	/*
		��������� �������: -117
			num_1 - �������� (type_id):
		              1 - ������ �������
		              2 - ������ ��������
		              3 - ������ �������
			num_2 - id �������, �� �������� ������������� ������
			txt_1 - ������ id �������� ���������

		�������� ��������:
			txt_7 - ����� ����� �� ��������
			num_8 - �������� �� �������
			txt_8 - ����� ������ �� ��������
	*/
	return '<input type="hidden" id="type_id" />';
}
function _historySpisok($el) {//������ ������� �������� [68]
	$sql = "SELECT *
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			ORDER BY `dtime_add` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">������� ���.</div>';

	//������������� ������� �� ����
	$spisok = array();
	foreach($arr as $r) {
		$day = substr($r['dtime_add'], 0, 10);
		if(!isset($spisok[$day]))
			$spisok[$day] = array();
		$spisok[$day][] = $r;
	}

	$datFirst = key($spisok);
	$send = '';
	foreach($spisok as $day => $day_arr) {
		$send .= '<div class="history-day'._dn($day == $datFirst, 'pt20').'">'.FullData($day, 1, 0, 1).'</div>';

		$last = count($day_arr) - 1;
		$user_id =  $day_arr[0]['user_id_add'];
		$un = '';
		foreach($day_arr as $n => $r) {
			$un .= '<div class="history-un">'.
						'<div class="history-o o'.$r['type_id'].'"></div>'.
						'<span class="dib pale w35 mr5">'.substr($r['dtime_add'], 11, 5).'</span>'.
						'����� ����� ������. '.
					'</div>';

			$is_user = $user_id != $r['user_id_add'];//��������� ������������
			$is_last = $n == $last;//��������� ������

			if(!$is_user && !$is_last)
				continue;

			$send .=
				'<table class="mt5">'.
					'<tr><td class="top">'._user($r['user_id_add'], 'ava30').
						'<td class="top">'.
							'<div class="fs12 ml5 color-555">'._user($r['user_id_add'], 'name').'</div>'.
							$un.
				'</table>';

			$user_id = $r['user_id_add'];
			$un = '';
		}
	}

	return $send;
}





function _imageServerCache() {//����������� �������� �����������
	if($arr = _cache())
		return $arr;

	$sql = "SELECT `id`,`path` FROM `_image_server`";
	return _cache(query_ass($sql));
}
function _imageServer($v) {//��������� ������� (����) ��� ������������
/*
	���� $v - �����, ��������� ����� ����
	���� $v - �����, ��� ��� ���� � ��������� id ����. ���� ���, �� ��������
*/
	if(empty($v))
		return '';

	$SRV = _imageServerCache();

	//��������� id ����
	if($server_id = _num($v)) {
		if(empty($SRV[$server_id]))
			return '';

		return $SRV[$server_id];
	}

	foreach($SRV as $id => $path)
		if($v == $path)
			return $id;

	//�������� � ���� ������ ����
	$sql = "INSERT INTO `_image_server` (
				`path`,
				`user_id_add`
			) VALUES (
				'".addslashes($v)."',
				".USER_ID."
			)";
	query($sql);

	_cache('clear', '_imageServerCache');

	return query_insert_id('_image_server');
}
function _imageNo() {//��������, ���� ����������� ���
	return '<img src="'.APP_HTML.'/img/nofoto-s.gif" width="80" height= "80" />';
}
function _imageHtml($r) {//��������� �������� � html-�������
	return
		'<img src="'._imageServer($r['server_id']).$r['80_name'].'"'.
			' width="'.$r['80_x'].'" height= "'.$r['80_y'].'"'.
			' class="image-open"'.
			' val="'.$r['id'].'"'.
		' />';
}
function _imageNameCreate() {//������������ ����� ����� �� ��������� ��������
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//������ �����������
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//��������� ������� ����������� � ����������� ���������
	$x = $x_new;
	$y = $y_new;
	// ���� ������ ������ ��� ����� ������
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // ���� ����� ������ ������, ��� ��������, �� X ������� ��������
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// ���� ������ ������ ������
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // ���� ����� ������ ������, ��� ��������, �� Y ������� ��������
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}

function _imageSave($obj_name, $obj_id, $file_type, $file_tmp_name) {
	$im = null;
	$IMAGE_PATH = APP_PATH.'/.image/'.APP_ID;
	$server_id = _imageServer('//'.DOMAIN.APP_HTML.'/.image/'.APP_ID.'/');

	//�������� ����������, ���� �����������
	if(!is_dir($IMAGE_PATH))
		mkdir($IMAGE_PATH, 0777, true);

	switch($file_type) {
		case 'image/jpeg': $im = @imagecreatefromjpeg($file_tmp_name); break;
		case 'image/png': $im = @imagecreatefrompng($file_tmp_name); break;
		case 'image/gif': $im = @imagecreatefromgif($file_tmp_name); break;
		case 'image/tiff':
			$tmp = $IMAGE_PATH.'/'.USER_ID.'.jpg';
			$image = NewMagickWand(); // magickwand.org
			MagickReadImage($image, $file_tmp_name);
			MagickSetImageFormat($image, 'jpg');
			MagickWriteImage($image, $tmp); //���������� ����������
			ClearMagickWand($image); //�������� � �������� ����������� ����������� �� ������
			DestroyMagickWand($image);
			$im = @imagecreatefromjpeg($tmp);
			unlink($tmp);
			break;
	}


	if(!$im)
		jsonError('����������� ���� �� �������� ������������.<br>�������� JPG, PNG, GIF ��� TIFF ������.');

	$x = imagesx($im);
	$y = imagesy($im);
	if($x < 100 || $y < 100)
		jsonError('����������� ������� ���������.<br>����������� ������ �� ����� 100�100 px.');

	$fileName = time().'-'._imageNameCreate();
	$NAME_MAX = $fileName.'-900.jpg';
	$NAME_80 = $fileName.'-80.jpg';

	$max = _imageImCreate($im, $x, $y, 900, 900, $IMAGE_PATH.'/'.$NAME_MAX);
	$_80 = _imageImCreate($im, $x, $y, 80, 80, $IMAGE_PATH.'/'.$NAME_80);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,0)
			FROM `_image`
			WHERE !`deleted`
			  AND `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	$sort = query_value($sql);

	$sql = "INSERT INTO `_image` (
				`server_id`,

				`max_name`,
				`max_x`,
				`max_y`,
				`max_size`,

				`80_name`,
				`80_x`,
				`80_y`,
				`80_size`,

				`obj_name`,
				`obj_id`,

				`sort`,
				`user_id_add`
			) VALUES (
				".$server_id.",

				'".$NAME_MAX."',
				".$max['x'].",
				".$max['y'].",
				".$max['size'].",

				'".$NAME_80."',
				".$_80['x'].",
				".$_80['y'].",
				".$_80['size'].",

				'".$obj_name."',
				".$obj_id.",

				".$sort.",
				".USER_ID."
		)";
	query($sql);

	$image_id = query_insert_id('_image');

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	return query_assoc($sql);
}
function _imageDD($img) {//������� ����������� ��� ���������
	return
	'<dd class="dib mr3 curM" val="'.$img['id'].'">'.
		'<div class="icon icon-off'._tooltip('����������� � �������', -70).'</div>'.
		'<table class="_image-unit">'.
			'<tr><td>'.
				_imageHtml($img).
		'</table>'.
	'</dd>';
}

function _imageShow($el, $unit) {//�������� ����������� (����������� � ���� ����� [12])
	$image = '����������� �����������.';//�������� ��������, �� ������� ������. ��������� ������
	$spisok = '';//html-������ �������������� �����������
	$spisokJs = array();//js-������ ���� �����������
	$spisokIds = array();//id �������� �� �������
	if($image_id = _num(@$unit['id'])) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id`=".$image_id;
		if($im = query_assoc($sql)) {
			$image = '<img src="'._imageServer($im['server_id']).$im['max_name'].'"'.
						 ' width="'.$im['max_x'].'"'.
						 ' height="'.$im['max_y'].'"'.
						 ' />';

			$sql = "SELECT *
					FROM `_image`
					WHERE `obj_name`='".$im['obj_name']."'
					  AND `obj_id`=".$im['obj_id']."
					  AND `deleted`=".$im['deleted']."
					ORDER BY `".($im['deleted'] ? 'dtime_del' : 'sort')."`";
			$arr = query_arr($sql);
			if(count($arr) > 1) {
				$spisok = '<div class="line-t pad10 center bg-gr2">';
				foreach($arr as $r) {
					$sel = $r['id'] == $image_id ? ' sel' : '';
					$spisok .=
					'<div class="dib ml3 mr3">'.
						'<table class="iu'.$sel.'" val="'.$r['id'].'">'.
							'<tr><td><img src="'._imageServer($r['server_id']).$r['80_name'].'"'.
										' width="'.$r['80_x'].'"'.
										' height="'.$r['80_y'].'"'.
									' />'.
						'</table>'.
					'</div>';
					$spisokJs[] = $r['id'].':{'.
						'src:"'.addslashes(_imageServer($r['server_id']).$r['max_name']).'",'.
						'x:'.$r['max_x'].','.
						'y:'.$r['max_y'].','.
					'}';
					$spisokIds[] = $r['id'];
				}
				$spisok .= '</div>';
			}

		}
	}

	return
	'<div id="_image-show">'.
		'<table class="w100p">'.
			'<tr><td id="_image-main" val="'.$image_id.'">'.
					$image.
		'</table>'.
		$spisok.
	'</div>'.
	'<script>'.
		'var IMG_ASS={'.implode(',', $spisokJs).'},'.
			'IMG_IDS=['.implode(',', $spisokIds).'];'.
	'</script>';
}
function _imageDeleted($el, $unit) {//�������� ����������� (����������� � ���� ����� [12])
	if(!$unit_id = _num(@$unit['id']))
		return '<div class="_empty min">����������� ������� ������, � ������� ������������� �����������.</div>';
	if(!$block_id = _num($unit['source']['block_id'], 1))
		return '<div class="_empty min">����������� id �����.</div>';
	if($block_id > 0)
		return '<div class="_empty min">Id ����� �� ����� ���� �������������.</div>';

	$sql = "SELECT *
			FROM `_image`
			WHERE `obj_name`='elem_".abs($block_id)."'
			  AND `obj_id`=".$unit_id."
			  AND `deleted`
			ORDER BY `dtime_del`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">�������� ����������� ���.</div>';

	$html = '';
	foreach($arr as $r) {
		$html .=
		'<div class="prel dib ml3 mr3">'.
			'<div val="'.$r['id'].'" class="icon icon-recover'._tooltip('������������', -43).'</div>'.
			'<table class="_image-unit">'.
				'<tr><td>'.
					_imageHtml($r).
			'</table>'.
		'</div>';
	}

	return '<div class="_image">'.$html.'</div>';
}
function _imageWebcam($el) {//���-������ (����������� � ���� ����� [12])
	$width = $el['block']['width'];
	$mar = explode(' ', $el['mar']);
	$width = round($width - $mar[1] - $mar[3]);
	$height = round($width * 0.75);

	$flashvars =
		'width='.$width.
		'&height='.$height.
		'&dest_width='.$width.
		'&dest_height='.$height.
		'&image_format=jpeg'.
		'&jpeg_quality=100'.
		'&enable_flash=true'.
		'&force_flash=false'.
		'&flip_horiz=false'.
		'&fps=30'.
		'&upload_name=webcam'.
		'&constraints=null'.
		'&swfURL=""'.
		'&flashNotDetectedText=""'.
		'&noInterfaceFoundText=""'.
		'&unfreeze_snap=true'.
		'&iosPlaceholderText=""'.
		'&user_callback=null'.
		'&user_canvas=null';

	return
	'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"'.
			' type="application/x-shockwave-flash"'.
	        ' codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0"'.
	        ' width="'.$width.'"'.
	        ' height="'.$height.'"'.
	        ' align="middle">'.
	            '<param name="wmode" value="opaque" />'.
				'<param name="allowScriptAccess" value="always" />'.
				'<param name="allowFullScreen" value="false" />'.
				'<param name="movie" value="" />'.
				'<param name="loop" value="false" />'.
				'<param name="menu" value="false" />'.
				'<param name="quality" value="best" />'.
				'<param name="bgcolor" value="#ffffff" />'.
				'<param name="flashvars" value="'.$flashvars.'" />'.
				'<embed src="'.APP_HTML.'/modul/element/webcam.swf?2"'.
					  ' wmode="opaque" loop="false" menu="false" quality="best" bgcolor="#ffffff" width="'.$width.'" height="'.$height.'" name="webcam_movie_embed" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="'.$flashvars.'">'.
				'</embed>'.
	'</object>';
}






























