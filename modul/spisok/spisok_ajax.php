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

	$page_id = _num($_POST['page_id']);
	$block_id = _num($_POST['block_id'], 1);

	//������ ����������� �������
	if(!$postCmp = @$_POST['cmp'])
		jsonError('��� ������ ��� ��������');
	if(!is_array($postCmp))
		jsonError('���������� ������� �� �������� ��������');

	$cmpUpdate = array();
	$elementFocusClear = false;//���� � ������� ������������ ������� `focus`, �� ��������������� ������ ����� ������ � ������ ��������� ������� (��� ������� _element)
	$pageDefClear = false; //��� ������� _page: ������� `def`, ���� ��������������� ����� �������� �� ���������
	foreach($postCmp as $cmp_id => $val) {
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

		if($dialog['base_table'] == '_element' && $col == 'focus' && $v)
			$elementFocusClear = true;

		if($dialog['base_table'] == '_page' && $col == 'def' && $v)
			$pageDefClear = true;

		$cmpUpdate[] = "`".$col."`='".addslashes($v)."'";
	}

	if(!$unit_id) {
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
					".$dialog_id.",
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
						  AND `dialog_id`=".$dialog_id;
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
			if($r['Field'] == 'block_id' && $block_id && $dialog['base_table'] == '_element') {
				$sql = "UPDATE `_element`
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
	}

	//������ ����� ������ �� ���� ��������� �������
	if($elementFocusClear) {
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
		if($block_ids = query_ids($sql)) {
			$sql = "UPDATE `_element`
					SET `focus`=0
					WHERE `block_id` IN (".$block_ids.")";
			query($sql);
		}
	}

	//������ ����� '�������� �� ���������' �� ���� ������� ����������
	if($pageDefClear) {
		$sql = "UPDATE `_page`
				SET `def`=0
				WHERE `app_id`=".APP_ID."
				  AND !`sa`";
		query($sql);
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $cmpUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//���������� �������� �����������
	foreach($postCmp as $cmp_id => $val) {
		$cmp = @$dialog['cmp'][$cmp_id];
		if($cmp['dialog_id'] == 19) {//���������� ��� ��������� �����������: radio, select, dropdown
			_dialogCmpValue($val, 'save', $dialog_id, $unit_id);
			continue;
		}
	}

	//��������� ������ ������� ������
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

/*
function _spisokUnitUpdate($unit_id=0, $page_id=0, $block_id=0) {//��������/�������������� ������� ������
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('������������ ID ����������� ����');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');
	if($dialog['sa'] && !SA)
		jsonError('��� �������');

	$send = array(
		'unit_id' => $unit_id,
		'dialog' => $dialog
	);
	
	//�������� ������� ������� ��� �������� ������
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('������� �� ����������');

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

	//�������� �������� �� ��������
	if($dialog_id == 6) {
		$sql = "DELETE FROM `_element` WHERE `id`=".$unit_id;
		query($sql);
		return $send;
	}

	//�������� �� ������������ ������ ����������� �������
	if(!$elem = @$_POST['elem'])
		jsonError('��� ������ ��� ��������');
	if(!is_array($elem))
		jsonError('������������ ������ ������');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('������������ ������������� ����');

	$elemUpdate = array();
	foreach($dialog['component'] as $id => $r) {
		if(!_dialogEl($r['type_id'], 'func'))
			continue;

		$v = _txt($elem[$id]);

		if($r['req'] && empty($v))
			jsonError(array(
				'delem_id' => $id,
				'text' => utf8('�� ��������� ����<br><b>'.$r['label_name'].'</b>')
			));

		//���� ��� ���������� ������, ���������� ������ � ������ � ������ ���������
//		if($r['type_id'] == 2 && $dialog['base_table'] == '_element' && $r['num_1'])
//			$elemUpdate[] = "`num_id`=".$r['num_1'];

		//��������� ���������� app_any_spisok. ���� ����� 1, �� ������������� app_id=0 (��� ����������), ���� = id ����������
		if($r['col_name'] == 'app_any_spisok') {
			$elemUpdate[] = "`app_id`=".($v ? 0 : APP_ID);
			continue;
		}

		$upd = "`".$r['col_name']."`=";
		switch($r['type_id']) {
			case 1: //check
			case 2: //select
			case 5: //radio
				$upd .= _num($v);
				break;
			default://��������� ��������� ��������
				if(preg_match('/^num_/', $r['col_name'])) {//���� ��������� �������� ������ ���� ������ ������
					if($v && !preg_match(REGEXP_NUMERIC, $v))
						jsonError(array(
							'delem_id' => $id,
							'text' => utf8('����������� ��������� ���� <b>'.$r['label_name'].'</b>')
						));
					$upd .= _num($v);
					break;
				}
				$upd .= "'".addslashes($v)."'";
		}
		$elemUpdate[] = $upd;
	}

	if(!$unit_id) {
		$sql = "INSERT INTO `".$dialog['base_table']."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($dialog['base_table']);
		$send['unit_id'] = $unit_id;

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
						  AND `dialog_id`=".$dialog_id;
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
			if($r['Field'] == 'block_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `block_id`=".$block_id."
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
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//���������� ������� �����������
	foreach($dialog['component'] as $id => $r)
		_spisokUnitFuncValUpdate($dialog, $id, $unit_id);

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
*/
