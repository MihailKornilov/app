<?php
switch(@$_POST['op']) {
	case 'spisok_add'://�������� ������� ������ �� �������
		$send = _spisokUnitUpdate();
		jsonSuccess($send);
		break;
	case 'spisok_save'://���������� ������ ������� ������ ��� �������
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('������������ id ������� ������');

		$send = _spisokUnitUpdate($unit_id);

		jsonSuccess($send);
		break;
	case 'spisok_del'://�������� ������� ������
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('������������ id ������� ������');

		$dialog = _spisokUnitDialog($unit_id);

		$send['action_id'] = _num($dialog['del_action_id']);
		$send['action_page_id'] = _num($dialog['del_action_page_id']);
		$send = _spisokAction3($send, $dialog, $unit_id);

		if(isset($dialog['field']['deleted']))
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
		else
			$sql = "DELETE FROM `".$dialog['base_table']."` WHERE `id`=".$unit_id;
		query($sql);

		jsonSuccess($send);
		break;
	case 'spisok_next'://�������� ������
		if(!$pe_id = _num($_POST['pe_id']))
			jsonError('������������ ID �������� �������');
		if(!$next = _num($_POST['next']))
			jsonError('������������ �������� ���������� �����');

		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$pe_id;
		if(!$pe = query_assoc($sql))
			jsonError('�������� id'.$pe_id.' �� ����������');

		if($pe['dialog_id'] != 14 && $pe['dialog_id'] != 23)
			jsonError('������� �� �������� �������');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$pe['block_id'];
		if(!$pe['block'] = query_assoc($sql))
			jsonError('����������� ���� ������');

		$send['is_table'] = $pe['dialog_id'] == 23;
		$send['spisok'] = utf8(_spisokShow($pe, $next));
		jsonSuccess($send);
		break;
	case 'spisok_search'://��������� ����������� ������ �� ��������
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('������������ ID �������� �������');

		$v = _txt($_POST['v']);

		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$pe = query_assoc($sql))
			jsonError('�������� id'.$elem_id.' �� ����������');

		//���������� ������ ������
		$sql = "UPDATE `_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$elem_id;
		query($sql);

		//id ������� ������, �� ������� ���������� ����������� ����� �����
		if(!$pe_id = _num($pe['num_1']))
			jsonError('�� ������ ������, �� �������� ����� ����������� �����');

		//������������ ������ �� ��������, �� ������� ���������� �����
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id` IN (14,23)
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('��� ������� ������ �� ��������');

		//��������� ������ �����, � ������� ���������� �������-������
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$peSpisok['block_id'];
		if(!$peSpisok['block'] = query_assoc($sql))
			jsonError('����� �� ����������');

		//������� ���������� ������ �� ��������, �� ������� ���������� �����
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `page_id`=".$pe['page_id']."
				  AND `num_1`=".$pe_id."
				LIMIT 1";
		if($peCount = query_assoc($sql)) {
			$send['count_attr'] = '#pe_'.$peCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($peCount));
		}

		$send['spisok_attr'] = '#pe_'.$peSpisok['id'];
		$send['spisok_html'] = utf8(_spisokShow($peSpisok));

		jsonSuccess($send);
		break;
	case 'spisok_connect_29':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('������������ ID ���������� �������');

		$v = _txt($_POST['v']);

		$send['spisok'] = _spisokConnect($cmp_id, $v);
		jsonSuccess($send);
		break;
}

function _spisokUnitDialog($unit_id) {//��������� ������ � ������� � �������� ������� ������� ������
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('������������ ID ����������� ����');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');
	if($dialog['sa'] && !SA)
		jsonError('��� �������');

	//�������� ������� ������� ��� �������� ������
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('������� �� ����������');

	//��������� ������ ������� ������, ���� �������������
	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".$dialog['base_table']."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');
		if(@$r['deleted'])
			jsonError('������ ���� �������');
	}

	return $dialog;
}
function _spisokUnitUpdate($unit_id=0) {//��������/�������������� ������� ������
	$dialog = _spisokUnitDialog($unit_id);
	$dialog_id = $dialog['id'];

	$act = $unit_id ? 'edit' : 'insert';

	$block_id = _num($_POST['block_id'], 1);

	$POST_CMP = _spisokUnitCmpTest($dialog);
	$unit_id = _spisokUnitInsert($unit_id, $dialog, $block_id);


	// ---=== ������ ���������� ������ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);

	_spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id);

	//���������� �������� �����������
	if(!empty($_POST['cmp']))
		foreach($_POST['cmp'] as $cmp_id => $val) {
			$cmp = @$dialog['cmp'][$cmp_id];
			if($cmp['dialog_id'] == 19) {//���������� ��� ��������� �����������: radio, select, dropdown
				_dialogCmpValue($val, 'save', $dialog_id, $unit_id);
				continue;
			}
		}

	//��������� ���������� ������ ������� ������
	$sql = "SELECT *
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	$unit = query_assoc_utf8($sql);

	if($cmpv = @$_POST['cmpv'])
		foreach($dialog['cmp'] as $cmp_id => $cmp) {
			if(!isset($cmpv[$cmp_id]))
				continue;
			switch($cmp['dialog_id']) {
				//��������� ���������� ���������� ������
				case 30: _spisokTableValueSave($cmp, $cmpv[$cmp_id], $unit); break;
			}
		}

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');
	if($dialog['base_table'] == '_element')
		_cache('clear', '_dialogQuery'.$dialog_id);

	$send = array(
		'unit' => $unit,
		'action_id' => _num($dialog[$act.'_action_id']),
		'action_page_id' => _num($dialog[$act.'_action_page_id'])
	);

	$send = _spisokAction3($send, $dialog, $unit_id);

	return $send;
}
function _spisokUnitCmpTest($dialog) {//�������� ������������ ����������� �������
	$POST_CMP = @$_POST['cmp'];
	if($dialog['cmp_no_req'] && empty($POST_CMP))
		return array();
	if(empty($POST_CMP))
		jsonError('��� ������ ��� ��������');
	if(!is_array($POST_CMP))
		jsonError('���������� ������� �� �������� ��������');

	$send = array();
	foreach($POST_CMP as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('������������ id ���������� �������');
		if(!$cmp = @$dialog['cmp'][$cmp_id])
			jsonError('����������� ��������� id'.$cmp_id.' � �������');
		if(!$col = @$cmp['col'])
			jsonError('����������� ��� ������� � ���������� id'.$cmp_id);
		if(!isset($dialog['field'][$col]))
			jsonError('� ������� <b>'.$dialog['base_table'].'</b> ��� ������� � ������ "'.$col.'"');
		if($cmp['dialog_id'] == 19) {//���������� ��� ��������� �����������: radio, select, dropdown
			_dialogCmpValue($val, 'test');
			continue;
		}

		$v = _txt($val);

		if($cmp['req'] && !$v)
			jsonError('��������� ����������� ���������<br>����, ���������� ���������');

		$ex = explode('_', $col);
		if($ex[0] == 'num')
			$v = _num($v);

		$send[$cmp_id] = $v;
	}

	return $send;
}
function _spisokUnitInsert($unit_id, $dialog, $block_id) {//�������� ����� ������� ������, ���� �����������
	if($unit_id)
		return $unit_id;

	$page_id = _num($_POST['page_id']);

	//���� ������������ ������� � ����: ��������, ����� � ���� �� ������ 2 ��������
	if($dialog['base_table'] == '_element' && $block_id > 0) {
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('����� �� ���������');

		$sql = "SELECT COUNT(*)
				FROM `_element`
				WHERE `block_id`=".$block_id;
		if(query_value($sql))
			jsonError('� ����� ��� ���� �������');
	}

	$sql = "INSERT INTO `".$dialog['base_table']."` (
				`dialog_id`,
				`viewer_id_add`
			) VALUES (
				".$dialog['id'].",
				".VIEWER_ID."
			)";
	query($sql);

	$unit_id = query_insert_id($dialog['base_table']);

	//���������� ��������� �������
	$sql = "DESCRIBE `".$dialog['base_table']."`";
	$desc = query_array($sql);
	foreach($desc as $r) {
		if($r['Field'] == 'app_id') {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `app_id`=".APP_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($r['Field'] == 'num') {//��������� ����������� ������
			$sql = "SELECT IFNULL(MAX(`num`),0)+1
					FROM `".$dialog['base_table']."`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog['id'];
			$num = query_value($sql);
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `num`=".$num."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($r['Field'] == 'page_id') {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `page_id`=".$page_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($r['Field'] == 'block_id' && $block_id) {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `block_id`=".$block_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($r['Field'] == 'width' && $dialog['base_table'] == '_element') {
			$sql = "UPDATE `_element`
					SET `width`=".$dialog['element_width']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($r['Field'] == 'sort') {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `sort`="._maxSql($dialog['base_table'])."
					WHERE `id`=".$unit_id;
			query($sql);
		}
	}

	return $unit_id;
}
function _elementFocusClear($dialog, $POST_CMP, $unit_id) {//���� � ������� ������������ ������� `focus`, �� ��������������� ������ ����� ������ � ������ ��������� ������� (��� ������� _element)
	if($dialog['base_table'] != '_element')
		return;
	if(empty($POST_CMP))
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'focus')
			continue;
		if(!$v)
			return;

		$sql = "SELECT `block_id`
				FROM `_element`
				WHERE `id`=".$unit_id;
		$block_id = _num(query_value($sql));

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		$block = query_assoc($sql);

		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='".$block['obj_name']."'
				  AND `obj_id`=".$block['obj_id'];
		if(!$block_ids = query_ids($sql))
			return;

		$sql = "UPDATE `_element`
				SET `focus`=0
				WHERE `block_id` IN (".$block_ids.")";
		query($sql);

		return;
	}
}
function _pageDefClear($dialog, $POST_CMP) {//��� ������� _page: ������� `def`, ���� ��������������� ����� �������� �� ���������
	if($dialog['base_table'] != '_page')
		return;
	if(empty($POST_CMP))
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'def')
			continue;
		if(!$v)
			return;

		//������ ����� '�������� �� ���������' �� ���� ������� ����������
		$sql = "UPDATE `_page`
				SET `def`=0
				WHERE `app_id`=".APP_ID."
				  AND !`sa`";
		query($sql);

		return;
	}
}
function _spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id) {//���������� ����������� ������� ������
	if(empty($POST_CMP))
		return;

	$update = array();
	foreach($POST_CMP as $cmp_id => $v) {
		$col = $dialog['cmp'][$cmp_id]['col'];
		$update[] = "`".$col."`='".addslashes($v)."'";
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $update)."
			WHERE `id`=".$unit_id;
	query($sql);
}
function _spisokAction3($send, $dialog, $unit_id) {//���������� �������� ��� ��������, ���� �������� 3 - ���������� ���������� ������
	if($send['action_id'] != 3)
		return $send;
	if($dialog['base_table'] != '_element')
		return $send;

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$unit_id;
	$elem = query_assoc($sql);

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$elem['block_id'];
	$block = query_assoc($sql);

	$send['block_obj_name'] = $block['obj_name'];

	switch($block['obj_name']) {
		default:
		case 'page': $width = 1000; break;
		case 'spisok':
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block['obj_id'];
			$bl = query_assoc($sql);

			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=".$bl['id'];
			$el = query_assoc($sql);

			//������������� ������ � ������ ��������
			$ex = explode(' ', $el['mar']);
			$width = floor(($bl['width'] - $ex[1] - $ex[3]) / 10) * 10;
			break;
		case 'dialog':
			_cache('clear', '_dialogQuery'.$block['obj_id']);
			$dlg = _dialogQuery($block['obj_id']);
			$width = $dlg['width'];
			break;
	}
	$send['level'] = utf8(_blockLevelChange($block['obj_name'], $block['obj_id'], $width));

	return $send;
}
function _spisokTableValueSave(//���������� ��������� ���������� ���������� ������ (30)
	$cmp,//��������� �� �������, ���������� �� ��������� �������
	$val,//��������, ���������� ��� ����������
	$unit//�������, ����������� �������, ��� ������� ���������� ���������
) {
	if(empty($cmp['col']))
		return;

	//����, �������� ������ id ���������-��������
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//�������� ��������, ������� ���� ������� ��� ���������
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `width`="._num($r['width']).",
					`txt_1`='".addslashes(_txt($r['tr']))."',
					`font`='".$r['font']."',
					`color`='".$r['color']."',
					`txt_6`='".$r['pos']."',
					`num_2`="._num($r['link']).",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}
}

