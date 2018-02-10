<?php
switch(@$_POST['op']) {
	case 'spisok_add'://�������� ������� ������ �� �������
		$send = _spisokUnitUpdate();
		jsonSuccess($send);
		break;
	case 'spisok_save'://���������� ������ ������� ������ ��� �������
		if(!$unit_id = _num($_POST['unit_id'], 1))
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
		$send = _spisokAction3($send, $dialog, $unit_id, 1);

		if(isset($dialog['field']['deleted'])) {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
			query($sql);
		} else {
			$elem = array();
			if($dialog['base_table'] == '_element') {//���� ��� �������
				$elem = _elemQuery($unit_id);
				//�������� ��������
				$sql = "DELETE FROM `_element` WHERE `block_id`=-".$unit_id;
				query($sql);
				//�������� �������
				$sql = "DELETE FROM `_element_func` WHERE `block_id`=".$elem['block_id'];
				query($sql);
				//��������� ������� � ����� �� ���������
				$sql = "UPDATE `_block` SET `pos`='top' WHERE `id`=".$elem['block_id'];
				query($sql);
			}

			$sql = "DELETE FROM `".$dialog['base_table']."` WHERE `id`=".$unit_id;
			query($sql);

			//���������� ���� �������, ���� ��� �������
			if($elem)
				_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);
		}

		$send = _spisokAction4($send);

		jsonSuccess($send);
		break;
	case 'spisok_next'://�������� ������
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('������������ ID �������� �������');
		if(!$next = _num($_POST['next']))
			jsonError('������������ �������� ���������� �����');
		//��������� ������ �������� ������
		if(!$el = _elemQuery($elem_id))
			jsonError('�������� id'.$elem_id.' �� ����������');
		if($el['dialog_id'] != 14 && $el['dialog_id'] != 23)
			jsonError('������� �� �������� �������');
		if(!$el['block'])
			jsonError('����������� ���� ������');

		$send['is_table'] = $el['dialog_id'] == 23;
		$send['spisok'] = utf8(_spisokShow($el, $next));
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
				  AND `num_1`=".$pe_id."
				LIMIT 1";
		if($peCount = query_assoc($sql)) {
			$send['count_attr'] = '#el_'.$peCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($peCount));
		}

		$send['spisok_attr'] = '#el_'.$peSpisok['id'];
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
	if($unit_id > 0) {
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

	define('IS_ELEM', $dialog['base_table'] == '_element');

	$act = $unit_id ? 'edit' : 'insert';

	$block_id = _num($_POST['block_id'], 1);

	$POST_CMP = _spisokUnitCmpTest($dialog);
	$unit_id = _spisokUnitInsert($unit_id, $dialog, $block_id);


	// ---=== ������ ���������� ������ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);

	_spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id);

	//��������� ���������� ������ ������� ������
	$sql = "SELECT *
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	$unit = query_assoc($sql);
	$unit['title'] = IS_ELEM ? _elemUnit($unit) : '';



	$cmpv = @$_POST['cmpv'];
	foreach($dialog['cmp'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//---=== �������� ��� ��������� ��������� ===---
			//���������� ��� ��������� �����������: radio, select, dropdown
			case 19: _cmpV19($cmpv[$cmp_id], $unit); break;
			//��������� ���������� ���������� ������
			case 30: _cmpV30($cmp, $cmpv[$cmp_id], $unit); break;
			case 49: _cmpV49($cmp, $cmpv[$cmp_id], $unit); break;
			//��������� ����� �������� ������� ������
			case 56: _cmpV56($cmp, $cmpv[$cmp_id], $unit); break;
			//���������� �������� ���������� ������
			case 54: /* ������� �������� �������� */ break;
			//��������� ������� ���� ������������ ������
			case 58: _cmpV58($cmpv[$cmp_id], $unit); break;

			//---=== �������� ����� �������� ������ ===---
			case 29:
				_spisokUnitCount29($cmp, $dialog, $unit); //�������� ��������� ����������� ������
				_spisokUnitSum29($cmp, $dialog, $unit);   //�������� c��� ����������� ������
				break;
		}

	_spisokUnitUpd27($unit);
	_spisokUnitUpd54($unit);
	_spisokUnitUpd55($unit);

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');
	if(IS_ELEM) {
		$elem = _elemQuery($unit_id);
		if($elem['block'])
			_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);
	}

	$send = array(
		'unit' => utf8($unit),
		'action_id' => _num($dialog[$act.'_action_id']),
		'action_page_id' => _num($dialog[$act.'_action_page_id'])
	);

	$send = _spisokAction3($send, $dialog, $unit_id, $block_id);
	$send = _spisokAction4($send);

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
			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8('����������� ��� ������� � ���������� id'.$cmp_id)
			));
		if(!isset($dialog['field'][$col]))
			jsonError('� ������� <b>'.$dialog['base_table'].'</b> ��� ������� � ������ "'.$col.'"');

		$v = _txt($val);

		if($cmp['req'] && !$v)
			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8($cmp['req_msg'] ? $cmp['req_msg'] : '���������� ��������� ����,<br>���� ������� ��������')
			));

		$ex = explode('_', $col);
		if($ex[0] == 'num')
			$v = _num($v);

		$send[$cmp_id] = $v;
	}

	return $send;
}
function _spisokUnitInsert($unit_id, $dialog, $block_id) {//�������� ����� ������� ������, ���� �����������
	if($unit_id > 0)
		return $unit_id;

	$page_id = _num($_POST['page_id']);

	//���� ������������ ������� � ����: ��������, ����� � ���� �� ������ 2 ��������
	if(IS_ELEM && $block_id > 0 && !$unit_id) {
		if(!$block = _blockQuery($block_id))
			jsonError('����� �� ���������');
		if($block['elem'])
			jsonError('� ����� ��� ���� �������');
	}

	$sql = "INSERT INTO `".$dialog['base_table']."` (`id`) VALUES (0)";
	query($sql);

	//������� id ����� ������������� ��������� ��� �����������
	if($unit_id < 0)
		$block_id = $unit_id;

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
		if($r['Field'] == 'dialog_id') {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `dialog_id`=".$dialog['id']."
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
		if($r['Field'] == 'width' && IS_ELEM && $dialog['element_width']) {
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
			continue;
		}
		if($r['Field'] == 'viewer_id_add') {
			$sql = "UPDATE `".$dialog['base_table']."`
					SET `viewer_id_add`=".VIEWER_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
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
function _spisokAction3($send, $dialog, $unit_id, $block_id=0) {//���������� �������� ��� ��������, ���� �������� 3 - ���������� ���������� ������
	if($send['action_id'] != 3)
		return $send;
	if($dialog['base_table'] != '_element')
		return $send;
	if($block_id <= 0)//���� ������� ���-�������� ��� �������
		return $send;

	$elem = _elemQuery($unit_id);

	if($elem['block_id'] < 0)
		return $send;

	$send['block_obj_name'] = $elem['block']['obj_name'];

	switch($elem['block']['obj_name']) {
		default:
		case 'page': $width = 1000; break;
		case 'spisok':
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$elem['block']['obj_id'];
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
			_cache('clear', '_dialogQuery'.$elem['block']['obj_id']);
			$dlg = _dialogQuery($elem['block']['obj_id']);
			$width = $dlg['width'];
			break;
	}
	$send['level'] = utf8(_blockLevelChange($elem['block']['obj_name'], $elem['block']['obj_id'], $width));

	return $send;
}
function _spisokAction4($send) {//�������� 4 - ���������� ��������� �������
	if($send['action_id'] != 4)
		return $send;
	if(!$dialog_id = _num(@$_POST['dialog_source']))
		return $send;

	$_POST['unit_id'] = 0;
	$send['dialog_source'] = _dialogOpenLoad($dialog_id);

	return $send;
}
function _cmpV19($val, $unit) {//���������� ��� ��������� �����������: radio, select, dropdown
	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$content = _txt($r['content']);
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($title)."',
				'".addslashes($content)."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//�������� �������� ��������
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//����� �������� �� ���������
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`block_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//��������� ������ �������� �� ���������
	$sql = "SELECT `id` FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
}
function _cmpV30($cmp, $val, $unit) {//���������� ��������� ���������� ���������� ������ (30)
	/*
		$cmp  - ��������� �� �������, ���������� �� ��������� �������
		$val  - ��������, ���������� ��� ����������
		$unit - �������, ����������� �������, ��� ������� ���������� ���������
	*/
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
				SET `block_id`=-".$unit['id'].",
					`width`="._num($r['width']).",
					`txt_7`='".addslashes(_txt($r['tr']))."',
					`font`='".$r['font']."',
					`color`='".$r['color']."',
					`txt_8`='".$r['pos']."',
					`url`="._num($r['url']).",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//������� ���������������� ���������
	$sql = "DELETE FROM `_element`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND `block_id` IN (0,-112)";
	query($sql);
}
function _cmpV49($cmp, $val, $unit) {//��������� ���������� �������� ������
	/*
		$cmp  - ��������� �� �������, ���������� �� ��������� �������
		$val  - ��������, ���������� ��� ����������
		$unit -�������, ����������� �������, ��� ������� ���������� ���������
	*/
	if(empty($cmp['col']))
		return;

	//����, �������� ������ id ���������-��������
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//�������� ��������, ������� ���� ������� ��� ���������
	$sql = "DELETE FROM `_element`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND `block_id` IN (0,-".$unit['id'].")
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `block_id`=-".$unit['id'].",
					`num_8`=".$r['spc'].",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//������� ���������������� ���������
	$sql = "DELETE FROM `_element`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND `block_id` IN (0,-111)";
	query($sql);
}
function _cmpV56($cmp, $val, $unit) {//��������� ����� �������� ������� ������
	/*
		$cmp  - ��������� �� �������, ���������� �� ��������� �������
		$val  - ��������, ���������� ��� ����������
		$unit -�������, ����������� �������, ��� ������� ���������� ���������
	*/
	if(empty($cmp['col']))
		return;

	//����, �������� ������ id ���������-��������
	$col = $cmp['col'];
	$ids = $unit[$col] ? $unit[$col] : 0;

	//�������� ��������, ������� ���� ������� ��� ���������
	$sql = "DELETE FROM `_element`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND `block_id` IN (0,-".$unit['id'].")
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(!$ids)
		return;

	$sort = 0;
	foreach(_ids($ids, 1) as $id) {
		$r = $val[$id];
		$sql = "UPDATE `_element`
				SET `block_id`=-".$unit['id'].",
					`num_8`=".$r['minus'].",
					`sort`=".$sort++."
				WHERE `id`=".$id;
		query($sql);
	}

	//������� ���������������� ���������
	$sql = "DELETE FROM `_element`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND `block_id` IN (0,-113)";
	query($sql);
}
function _cmpV58($val, $unit) {//��������� ������� ���� ������������ ������
	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$blk = _ids($r['blk']);
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($title)."',
				'".$blk."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//�������� �������� ��������
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//����� �������� �� ���������
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`block_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//��������� ������ �������� �� ���������
	$sql = "SELECT `id` FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
}
function _spisokUnitUpd27($unit) {//���������� ���� �������� ������� ������ (������)
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 27)
		return;
	//����, � ������� ����������� "������"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockQuery($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	//������, � ������� ����������� �������� (������ ����� ������ ����� �����������)
	if(!$DSrc = _dialogQuery($BL['obj_id']))
		return;

	//��������������� ��������� �������� ����� �����������
	$sql = "UPDATE `".$DSrc['base_table']."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$BL['obj_id'];
	query($sql);

	if(!$ids = _ids($unit['txt_2']))
		return;

	//��������� ������ �������� ��� ��������
	$sql = "SELECT `num_1`,`num_8`
			FROM `_element`
			WHERE `id` IN (".$ids.")";
	if(!$elData = query_ass($sql))
		return;

	//��������� ����� �������� ��� ��������
	$sql = "SELECT `id`,`col`
			FROM `_element`
			WHERE LENGTH(`col`)
			  AND `id` IN ("._idsGet($elData, 'key').")";
	if(!$elCol = query_ass($sql))
		return;


	$upd = '';
	foreach($elCol as $id => $col) {
		$znak = $elData[$id] ? '-' : '+';
		$upd .= $znak.'`'.$col.'`';
	}

	//������� ����������
	$sql = "UPDATE `".$DSrc['base_table']."`
			SET `".$unit['col']."`=".$upd."
			WHERE `dialog_id`=".$BL['obj_id'];
	query($sql);
}
function _spisokUnitUpd54($unit) {//���������� ��������� ������������ ������
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 54)
		return;
	if(!$cmp_id = _num($unit['num_1']))//id ���������� � �������, � ������� ����������� �������� (���������� ���� �������� ����� ���������)
		return;
	if(!$cmp = _elemQuery($cmp_id))
		return;
	if(!$dialog_id = $cmp['block']['obj_id'])//id �������, � ������� ����������� ��������
		return;
	if(!$DConn = _dialogQuery($dialog_id))
		return;
	//����, � ������� ����������� "����������"
	if(!$block_id = _num($unit['block_id']))
		return;
	if(!$BL = _blockQuery($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$DSrc = _dialogQuery($BL['obj_id']))//������, � �������� �������� ������ (������ ����� ������ ����� �����������)
		return;

	//��������������� ��������� �������� ����� �����������
	$sql = "UPDATE `".$DSrc['base_table']."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$BL['obj_id'];
	query($sql);

	$sql = "SELECT
				`".$cmp['col']."`,
				COUNT(`id`)
			FROM `".$DConn['base_table']."`
			WHERE `dialog_id`=".$dialog_id."
			  AND `".$cmp['col']."`
			  AND !`deleted`
			GROUP BY `".$cmp['col']."`";
	if(!$ass = query_ass($sql))//�����, ���� ������ ���������
		return;

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $c) {
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `".$DSrc['base_table']."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
	}
}
function _spisokUnitUpd55($unit) {//���������� ���� ������������ ������
	if(!isset($unit['dialog_id']))
		return;
	if($unit['dialog_id'] != 55)
		return;
	if(!$cmp_id = _num($unit['num_1']))//id ���������� � �������, � ������� ����������� �������� (����� ���� �������� ����� ���������)
		return;
	if(!$cmp = _elemQuery($cmp_id))
		return;
	if(!$dialog_id = $cmp['block']['obj_id'])//id �������, � ������� ����������� ��������
		return;
	if(!$DConn = _dialogQuery($dialog_id))
		return;

	//������, � �������� �������� ������ (������ ����� ������ ����� �����������)
	if(!$DSrc_id = _num($cmp['num_1']))
		return;
	if(!$DSrc = _dialogQuery($DSrc_id))
		return;

	//��������������� ��������� �������� ����� �����������
	$sql = "UPDATE `".$DSrc['base_table']."`
			SET `".$unit['col']."`=0
			WHERE `dialog_id`=".$DSrc_id;
	query($sql);

	//��������� ��������, ������� ��������� �� �������, ����� �������� �������� ����� ����� �������
	if(!$elem_id = _num($unit['num_2']))
		return;
	if(!$elForSum = _elemQuery($elem_id))
		return;
	if(!$elForSum_id = _num($elForSum['num_1']))
		return;
	if(!$cmpSum = @$DConn['cmp_utf8'][$elForSum_id])
		return;
	if(!$sum_col = $cmpSum['col'])
		return;

	$sql = "SELECT
				`".$cmp['col']."`,
				SUM(`".$sum_col."`)
			FROM `".$DConn['base_table']."`
			WHERE `dialog_id`=".$dialog_id."
			  AND `".$cmp['col']."`
			  AND !`deleted`
			GROUP BY `".$cmp['col']."`";
	if(!$ass = query_ass($sql))//�����, ���� ������ ���������
		return;

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $c) {
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `".$DSrc['base_table']."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
	}
}

function _spisokUnitCount29($cmp, $dialog, $unit) {//�������� ��������� ����������� ������
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=54
			  AND `num_1`=".$cmp['id'];
	if(!$arr = query_arr($sql))
		return;
	if(!$col = $cmp['col'])//��� �������, �� ������� �������� ������
		return;
	if(!$connect_id = _num($unit[$col]))//��������, id ������� ������������ ������.
		return;

	//��������� ������ ���������� ��� ����������
	$sql = "SELECT COUNT(*)
			FROM `".$dialog['base_table']."`
			WHERE `dialog_id`=".$dialog['id']."
			  AND `".$col."`=".$connect_id;
	$count = _num(query_value($sql));

	foreach($arr as $r) {
		if(!$col = $r['col'])
			continue;
		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);
		$sql = "UPDATE `".$dlg['base_table']."`
				SET `".$col."`=".$count."
				WHERE `id`=".$connect_id;
		query($sql);
	}
}
function _spisokUnitSum29($cmp, $dialog, $unit) {//�������� ���� ����������� ������
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=55
			  AND `num_1`=".$cmp['id'];
	if(!$arr = query_arr($sql))
		return;
	if(!$col = $cmp['col'])//��� �������, �� ������� �������� ������
		return;
	if(!$connect_id = _num($unit[$col]))//��������, id ������� ������������ ������.
		return;

	foreach($arr as $r) {
		if(!$colSumSet = $r['col'])
			continue;
		//����� �������, �� ������� ����� ������������ ������� �����
		if(!$el = _elemQuery($r['num_2']))
			continue;
		if($el['dialog_id'] != 11)//������ ������� � �������� ����� ���������� ����� [11]
			continue;
		if(!$el = _elemQuery($el['num_1']))
			continue;
		if(!$colSumGet = $el['col'])
			continue;

		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		//��������� ������ ���������� ��� ����������
		$sql = "SELECT SUM(`".$colSumGet."`)
				FROM `".$dialog['base_table']."`
				WHERE `dialog_id`=".$dialog['id']."
				  AND `".$col."`=".$connect_id;
		$sum = query_value($sql);

		$sql = "UPDATE `".$dlg['base_table']."`
				SET `".$colSumSet."`=".$sum."
				WHERE `id`=".$connect_id;
		query($sql);
	}
}
