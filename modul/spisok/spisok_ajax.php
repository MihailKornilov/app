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

		if(isset($dialog['field1']['deleted'])) {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `id`=".$unit_id;
			query($sql);
			_historyInsert(3, $dialog, $unit_id);
			_spisokUnitAfter($dialog, $unit_id);
		} else {
			$elem = array();
			if(_table($dialog['table_1']) == '_element') {//���� ��� �������
				$elem = _elemQuery($unit_id);
				//�������� ��������
				$sql = "DELETE FROM `_element` WHERE `block_id`=-".$unit_id;
				query($sql);
				//�������� �������
				$sql = "DELETE FROM `_element_func` WHERE `block_id`=".$elem['block_id'];
				query($sql);
				//�������� ��������
				$sql = "DELETE FROM `_user_spisok_filter` WHERE `element_id_filter`=".$unit_id;
				query($sql);
				//��������� ������� � ����� �� ���������
				$sql = "UPDATE `_block` SET `pos`='top' WHERE `id`=".$elem['block_id'];
				query($sql);
			}

			$sql = "SELECT * FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			$unit = query_assoc($sql);

			$sql = "DELETE FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			query($sql);

			//���������� ���� �������, ���� ��� �������
			if($elem) {
				_cache('clear', '_elemQuery'.$elem['id']);
				_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);
				_spisokFilter('cache_clear');//����� ���� �������, ��� ��� �������� ��� ����� ������
			}

			//���������� ���� �������, ���� ��� ��������
			if($dialog['table_name_1'] == '_page')
				_cache('clear', '_pageCache');

			if($dialog['table_name_1'] == '_element_func')
				if($BL = _blockQuery($unit['block_id']))
					_cache('clear', $BL['obj_name'].'_'.$BL['obj_id']);
		}

		$send = _spisokAction4($send);

		jsonSuccess($send);
		break;
	case 'spisok_filter_update'://���������� ������ ����� ���������� �������
		if(!$elem_spisok = _num($_POST['elem_spisok']))
			jsonError('������������ ID ��������-������');
		if(!$elSpisok = _elemQuery($elem_spisok))
			jsonError('��������-������ id'.$elem_spisok.' �� ����������');
		if($elSpisok['dialog_id'] != 14 && $elSpisok['dialog_id'] != 23)
			jsonError('������� id'.$elem_spisok.' �� �������� �������');
		if(!$elem_v = $_POST['elem_v'])
			jsonError('����������� �������� ��������');
		if(!is_array($elem_v))
			jsonError('������������ �������� �������');

		foreach($elem_v as $elem_filter => $v) {
			if(!_num($elem_filter))
				continue;

			_spisokFilter('insert', array(
				'spisok' => $elem_spisok,
				'filter' => $elem_filter,
				'v' => $v
			));
		}

		//������� ����������, ����������� � ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elCount = query_assoc($sql)) {
			$send['count_attr'] = '#el_'.$elCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($elCount));
		}

		//������� "������� �������", ����������� � ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=80
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elClear = query_assoc($sql)) {
			$send['clear_attr'] = '#cmp_'.$elClear['id'];
			$send['clear_diff'] = _spisokFilter('diff', $elem_spisok);
		}

		$send['spisok_attr'] = '#el_'.$elem_spisok;
		$send['spisok_html'] = utf8(_spisokShow($elSpisok));
		jsonSuccess($send);
		break;
	case 'spisok_filter_clear'://������� �������
		if(!$spisok_id = _num($_POST['spisok_id']))
			jsonError('������������ ID ��������-������');
		if(!$elSpisok = _elemQuery($spisok_id))
			jsonError('��������-������ id'.$spisok_id.' �� ����������');

		$sql = "UPDATE `_user_spisok_filter`
				SET `v`=`def`
				WHERE `user_id`=".USER_ID."
				  AND `element_id_spisok`=".$spisok_id;
		query($sql);

		_spisokFilter('cache_clear');

		//������� ����������, ����������� � ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `num_1`=".$spisok_id."
				LIMIT 1";
		if($elCount = query_assoc($sql)) {
			$send['count_attr'] = '#el_'.$elCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($elCount));
		}

		$send['spisok_attr'] = '#el_'.$spisok_id;
		$send['spisok_html'] = utf8(_spisokShow($elSpisok));
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
	case 'spisok_29_connect':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('������������ ID ���������� �������');

		$v = _txt($_POST['v']);

		$send['spisok'] = utf8(_spisok29connect($cmp_id, $v));
		jsonSuccess($send);
		break;
	case 'spisok_59_unit':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('������������ ID ����������');
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('������������ ID ���������� ��������');

		$send['html'] = utf8(_spisok59unit($cmp_id, $unit_id));
		jsonSuccess($send);
		break;
	case 'spisok_23_sort':
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('������������ ID ��������');
		if(!$el = _elemQuery($elem_id))
			jsonError('�������� id'.$elem_id.' �� ����������');
		if($el['dialog_id'] != 23)
			jsonError('������� �� �������� �������-��������');
		if(!$dialog_id = _num($el['num_1']))
			jsonError('����������� ID �������');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');

		$arr = $_POST['arr'];
		if(empty($arr))
			jsonError('����������� �������� ��� ����������');
		if(!is_array($arr))
			jsonError('�������� �� �������� ��������');

		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;

			$parent_id = _num($r['parent_id']);

			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `parent_id`=".$parent_id.",
						`sort`=".$n."
					WHERE `id`=".$id;
			query($sql);
		}

		//���������� ���������, ���� ������������
		foreach($dialog['cmp'] as $r)
			_spisokUnitUpd54($r);

		jsonSuccess();
		break;
}

function _spisokUnitDialog($unit_id) {//��������� ������ � ������� � �������� ������� ������� ������
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('������������ ID ����������� ����');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');
	if($dialog['sa'] && !SA)
		jsonError('��� �������');

	if(!$dialog['table_1'])
		return $dialog;

	//�������� ������� ������� ��� �������� ������
	$sql = "SHOW TABLES LIKE '"._table($dialog['table_1'])."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('������� �� ����������');

	//��������� ������ ������� ������, ���� �������������
	if($unit_id > 0) {
		if(!$r = _spisokUnitQuery($dialog, $unit_id))
			jsonError('������ �� ����������');
		if(@$r['deleted'])
			jsonError('������ ���� �������');
	}

	return $dialog;
}
function _spisokUnitUpdate($unit_id=0) {//��������/�������������� ������� ������
	$dialog = _spisokUnitDialog($unit_id);
	$unitOld = _spisokUnitQuery($dialog, $unit_id);

	define('IS_ELEM', $dialog['table_1'] == 5);// '_element'

	$act = $unit_id ? 'edit' : 'insert';

	$block_id = _num($_POST['block_id'], 1);

	$POST_CMP = _spisokUnitCmpTest($dialog);

	$unit_id = _spisokUnitInsert($unit_id, $dialog, $block_id);


	// ---=== ������ ���������� ������ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);

	_spisokUnitCmpUpdate($dialog, $POST_CMP, $unit_id);

	//��������� ���������� ������ ������� ������
	$unit = _spisokUnitQuery($dialog, $unit_id);

	if(IS_ELEM)
		if($bl = _blockQuery($unit['block_id']))
			if($bl['obj_name'] == 'dialog') {
				_cache('clear', '_dialogQuery'.$bl['obj_id']);
				$dlg = _dialogQuery($bl['obj_id']);
				$unit = $dlg['cmp'][$unit['id']];
			}

	$cmpv = @$_POST['cmpv'];
	foreach($dialog['cmp'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//---=== �������� ��� ��������� ��������� ===---
			//���������� �������
			case 12:
				$funcSave = $cmp['txt_1'].'Save';
				if(!function_exists($funcSave))
					break;
				$funcSave($cmp, $cmpv[$cmp_id], $unit);
				break;
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
			//���������� ����������� �����������
			case 60: _cmpV60($cmp, $unit); break;
		}

	_spisokUnitUpd27($unit);
	_spisokUnitUpd54($unit);
	_spisokUnitUpd55($unit);

	_spisokUnitAfter($dialog, $unit_id, $unitOld);

	if(_table($dialog['table_1']) == '_page')
		_cache('clear', '_pageCache');

	if(_table($dialog['table_1']) == '_element_func')
		if($BL = _blockQuery($unit['block_id']))
			_cache('clear', $BL['obj_name'].'_'.$BL['obj_id']);

	if(IS_ELEM) {
		$elem = _elemQuery($unit_id);
		if($elem['block'])
			_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);
	}

	if(IS_ELEM)
		$unit['title'] = _elemTitle($unit_id);

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
	$dlgParent = $dialog;

	if($parent_id = $dialog['dialog_parent_id'])
		if(!$dlgParent = _dialogQuery($parent_id))
			return array();

	if(!$dlgParent['table_1'])
		return array();

	$POST_CMP = @$_POST['cmp'];
	if($dialog['cmp_no_req'] && empty($POST_CMP))
		return array();
//	if(empty($POST_CMP))
//		jsonError('��� ������ ��� ��������');
	if(!is_array($POST_CMP))
		jsonError('���������� ������� �� �������� ��������');

	$send = array();
	foreach($POST_CMP as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('������������ id ���������� �������');
		if(!$cmp = @$dialog['cmp'][$cmp_id])
			jsonError('����������� ��������� id'.$cmp_id.' � �������');
		if(!$col = @$cmp['col'])
			continue;
/*			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8('����������� ��� ������� � ���������� id'.$cmp_id)
			));
*/
		if(!isset($dlgParent['field1'][$col]) && !isset($dlgParent['field2'][$col]))
			jsonError('� ������� ����������� ������� � ������ "'.$col.'"');

		$v = _txt($val);

		//������ ��� �������� ������
		$is_err = 0;
		$err_msg = $cmp['req_msg'] ? $cmp['req_msg'] : '���������� ��������� ����,<br>���� ������� ��������';

		switch($cmp['dialog_id']) {
			case 8://��������� ����
				if($cmp['req'] && !strlen($v))
					$is_err = 1;
				if($cmp['num_1'] == 32)//����� �����
					break;
				if($cmp['num_1'] != 33)//����� � �����
					break;

				$v = round($v, $cmp['num_2']);
				if($cmp['req'] && !$v && !$cmp['num_4'])
					$is_err = 1;
				if($v < 0 && !$cmp['num_3']) {
					$is_err = 1;
					$err_msg = '�������� �� ����� ���� �������������';
				}
				break;
			default:
				if($cmp['req'] && !$v)
					$is_err = 1;

				$ex = explode('_', $col);
				if($ex[0] == 'num')
					$v = _num($v, 1);
				if($ex[0] == 'count')
					$v = _num($v,1);
				if($ex[0] == 'cena')
					$v = _cena($v, 1);
		}

		if($is_err)
			jsonError(array(
				'attr_cmp' => $cmp['attr_cmp']._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => utf8($err_msg)
			));

		$send[$cmp_id] = $v;
	}

	if(!$send)
		jsonError('��� ������ ��� ��������');

	return $send;
}
function _spisokUnitInsert($unit_id, $dialog, $block_id) {//�������� ����� ������� ������, ���� �����������
	if($unit_id > 0)
		return $unit_id;
	if(!$dialog['table_1'])
		return 0;

	$page_id = _num($_POST['page_id']);

	//���� ������������ ������� � ����: ��������, ����� � ���� �� ������ 2 ��������
	if(IS_ELEM && $block_id > 0 && !$unit_id) {
		_cache('clear', '_blockQuery'.$block_id);
		if(!$block = _blockQuery($block_id))
			jsonError('����� �� ���������');
		if($block['elem'])
			jsonError('� ����� ��� ���� �������');
	}

	$sql = "INSERT INTO `"._table($dialog['table_1'])."` (`id`) VALUES (0)";
	query($sql);

	//������� id ����� ������������� ��������� ��� �����������
	if($unit_id < 0)
		$block_id = $unit_id;

	$unit_id = query_insert_id(_table($dialog['table_1']));

	//���������� ��������� ������� ������� 1
	foreach($dialog['field1'] as $field => $i) {
		if($field == 'app_id') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `app_id`=".APP_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'dialog_id') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `dialog_id`=".$dialog['id']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'num') {//��������� ����������� ������
			$sql = "SELECT IFNULL(MAX(`num`),0)+1
					FROM `"._table($dialog['table_1'])."`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog['id'];
			$num = query_value($sql);
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `num`=".$num."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'page_id') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `page_id`=".$page_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'block_id' && $block_id) {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `block_id`=".$block_id."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'width' && IS_ELEM && $dialog['element_width']) {
			$sql = "UPDATE `_element`
					SET `width`=".$dialog['element_width']."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'sort') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `sort`="._maxSql(_table($dialog['table_1']))."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
		if($field == 'user_id_add') {
			$sql = "UPDATE `"._table($dialog['table_1'])."`
					SET `user_id_add`=".USER_ID."
					WHERE `id`=".$unit_id;
			query($sql);
			continue;
		}
	}


	//�������� ������ ������� 2, ���� ����
	if($dialog['table_2']) {
		$sql = "INSERT INTO `"._table($dialog['table_2'])."` (`".$dialog['table_2_field']."`) VALUES (".$unit_id.")";
		query($sql);

		$unit_2 = query_insert_id(_table($dialog['table_2']));
		foreach($dialog['field2'] as $field => $i) {
			if($field == 'app_id') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'dialog_id') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `dialog_id`=".$dialog['id']."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'num') {//��������� ����������� ������
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `"._table($dialog['table_2'])."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog['id'];
				$num = query_value($sql);
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `num`=".$num."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
			if($field == 'user_id_add') {
				$sql = "UPDATE `"._table($dialog['table_2'])."`
						SET `user_id_add`=".USER_ID."
						WHERE `id`=".$unit_2;
				query($sql);
				continue;
			}
		}
	}

	_historyInsert(1, $dialog, $unit_id);

	return $unit_id;
}
function _elementFocusClear($dialog, $POST_CMP, $unit_id) {//���� � ������� ������������ ������� `focus`, �� ��������������� ������ ����� ������ � ������ ��������� ������� (��� ������� _element)
	if(!$dialog['table_1'])
		return;
	if(_table($dialog['table_1']) != '_element')
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
	if(!$dialog['table_1'])
		return;
	if(_table($dialog['table_1']) != '_page')
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
	$dlgParent = $dialog;
	if($parent_id = $dialog['dialog_parent_id'])
		if(!$dlgParent = _dialogQuery($parent_id))
			return;

	if(!$dlgParent['table_1'])
		return;
	if(empty($POST_CMP))
		return;

	$update1 = array();
	$update2 = array();
	foreach($POST_CMP as $cmp_id => $v) {
		$cmp = $dialog['cmp'][$cmp_id];
		$col = $cmp['col'];

		if(IS_ELEM && $col == 'col') {//���� �������, ��������� ������ �������, � ������� ���������� �������
			$num = 0;

			if($v)
				if($el = _elemQuery($unit_id))
					if($el['block']['obj_name'] == 'dialog')
						if($dlg = _dialogQuery($el['block']['obj_id'])) {

							if($parent_id = $dlg['dialog_parent_id'])
								$dlg = _dialogQuery($parent_id);

							if(isset($dlg['field1'][$v]))
								$num = 1;
							else
								if(isset($dlg['field2'][$v]))
									$num = 2;
								else
									$v = '';

						}

			$update1[] = "`table_num`=".$num;
		}

		if($cmp['table_num'] == 1)
			$update1[] = "`".$col."`='".addslashes($v)."'";
		if($cmp['table_num'] == 2)
			$update2[] = "`".$col."`='".addslashes($v)."'";
	}

	if(!empty($update1)) {
		$sql = "UPDATE `"._table($dlgParent['table_1'])."`
				SET ".implode(',', $update1)."
				WHERE `id`=".$unit_id;
		query($sql);
	}

	if(!empty($update2)) {
		$field2 = $dlgParent['field2'];

		$cond = "`".$dlgParent['table_2_field']."`=".$unit_id;
		if(isset($field2['app_id']))
			$cond .= " AND `app_id`=".APP_ID;
		if(isset($field2['dialog_id']))
			$cond .= " AND `dialog_id`=".$dlgParent['id'];

		$sql = "SELECT `id`
				FROM `"._table($dlgParent['table_2'])."`
				WHERE ".$cond."
				LIMIT 1";
		if($unit_2 = _num(query_value($sql))) {
			$sql = "UPDATE `"._table($dlgParent['table_2'])."`
					SET ".implode(',', $update2)."
					WHERE `id`=".$unit_2;
			query($sql);
		}
	}
}
function _spisokAction3($send, $dialog, $unit_id, $block_id=0) {//���������� �������� ��� ��������, ���� �������� 3 - ���������� ���������� ������
	if($send['action_id'] != 3)
		return $send;
	if(_table($dialog['table_1']) != '_element')
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
		-112
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
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-112)";
	query($sql);
}
function _cmpV49($cmp, $val, $unit) {//��������� ���������� �������� ������
	/*
		-111
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
			WHERE `user_id_add`=".USER_ID."
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
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-111)";
	query($sql);
}
function _cmpV56($cmp, $val, $unit) {//��������� ����� �������� ������� ������
	/*
		-113
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
			WHERE `user_id_add`=".USER_ID."
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
			WHERE `user_id_add`=".USER_ID."
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
function _cmpV60($cmp, $unit) {//���������� ����������� �����������
	//����, �������� ������ id �����������
	if(!$col = $cmp['col'])
		return;

	//������������ ����������� � ������� ������
	$sql = "UPDATE `_image`
			SET `obj_name`='elem_".$cmp['id']."',
				`obj_id`=".$unit['id']."
			WHERE `obj_name`='elem_".$cmp['id']."_".USER_ID."'";
	query($sql);

	$sql = "UPDATE `_image`
			SET `deleted`=1,
				`user_id_del`=".USER_ID.",
				`dtime_del`=CURRENT_TIMESTAMP
			WHERE `obj_name`='elem_".$cmp['id']."'
			  AND `obj_id`=".$unit['id']."
			  AND `id` NOT IN ("._ids($unit[$col]).")";
	query($sql);

	//���������� ����������
	$sort = 0;
	foreach(_ids($unit[$col], 1) as $id) {
		$sql = "UPDATE `_image`
				SET `sort`=".$sort++.",
					`deleted`=0,
					`user_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$id;
		query($sql);
	}
}
function _filterCheckSetupSave($cmp, $val, $unit) {//���������� ��������� ������� ��� �������. ������������ ������� [12]
	/*
		-114
		$cmp  - ��������� �� �������, ���������� �� ��������� �������
		$val  - ��������, ���������� ��� ����������
		$unit - �������, ����������� �������, ��� ������� ���������� ���������
	*/

	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $id => $r) {
			if($id = _num($id))
				$idsNoDel .= ','.$id;
			if(!$num_8 = _num($r['num_8']))
				continue;
			$txt_8 = $num_8 > 2 ? _txt($r['txt_8']) : '';
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				".$num_8.",
				'".addslashes($txt_8)."'
			)";
		}
	}

	//�������� �������� ��������
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	if(!empty($update)) {
		$sql = "INSERT INTO `_element` (
					`id`,
					`block_id`,
					`num_8`,
					`txt_8`
				)
				VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`block_id`=VALUES(`block_id`),
					`num_8`=VALUES(`num_8`),
					`txt_8`=VALUES(`txt_8`)";
		query($sql);
	}


	//������� ���������������� ���������
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-114)";
	query($sql);
}
function _historySetupSave($cmp, $val, $unit) {//���������� ��������� ������� ������� ��������. ������������ ������� [12]
	/*
		-115
		$cmp  - ��������� �� �������, ���������� �� ��������� �������
		$val  - ��������, ���������� ��� ����������
		$unit - �������, ����������� �������, ��� ������� ���������� ���������
	*/

	$update = array();
	$idsNoDel = '0';

	if(!$dlg_id = $val['dialog_id'])
		return;
	if(!$type_id = $val['type_id'])
		return;

	$val = @$val['val'];
	if(!empty($val) && is_array($val)) {
		$sort = 0;
		foreach($val as $r) {
			$num_1 = _num($r['num_1']);
			$txt_7 = _txt($r['txt_7'], 0, 1);
			$txt_8 = _txt($r['txt_8'], 0, 1);
			if(!$num_1 && !$txt_7 && !$txt_8)
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$update[] = "(
				".$id.",
				-".$unit['id'].",
				'".addslashes($txt_7)."',
				'".addslashes($txt_8)."',
				".$sort++.",
				".USER_ID."
			)";
		}
	}

	//�������� �������� ��������
	$sql = "DELETE FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	if(!empty($update)) {
		$sql = "INSERT INTO `_element` (
					`id`,
					`block_id`,
					`txt_7`,
					`txt_8`,
					`sort`,
					`user_id_add`
				)
				VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`block_id`=VALUES(`block_id`),
					`txt_7`=VALUES(`txt_7`),
					`txt_8`=VALUES(`txt_8`),
					`sort`=VALUES(`sort`)";
		query($sql);
	}

	//������� ���������������� ���������
	$sql = "DELETE FROM `_element`
			WHERE `user_id_add`=".USER_ID."
			  AND `block_id` IN (0,-115)";
	query($sql);

	//���������� �������� �������� �������� �������
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id`=-".$unit['id']."
			ORDER BY `sort`";
	$ids = query_ids($sql);

	$sql = "UPDATE `_element`
			SET `num_1`=".$type_id.",
				`num_2`=".$dlg_id.",
				`txt_1`='".($ids ? $ids : '')."'
			WHERE `id`=".$unit['id'];
	query($sql);

	//���������� ���������� � �������
	$sql = "UPDATE `_history`
			SET `active`=".($ids ? 1 : 0)."
			WHERE `type_id`=".$type_id."
			  AND `dialog_id`=".$dlg_id;
	query($sql);
}
function _pageUserAccessSave($cmp, $val, $unit) {//���������� ������� � ��������� ��� ����������� ������������
	if(!is_array($val))
		return;
	if(!$user_id = @$val['user_id'])
		return;

	$sql = "DELETE FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$user_id;
	query($sql);


	if($ids = _ids(@$val['ids'], 1)) {
		$upd = array();
		foreach($ids as $page_id)
			$upd[] = "(".APP_ID.",".$user_id.",".$page_id.")";

		$sql = "INSERT INTO `_user_page_access`
					(`app_id`,`user_id`,`page_id`)
				VALUES ".implode(',', $upd);
		query($sql);
	}

	//���������� ����� � ����������, ���� �����
	$sql = "SELECT `num_1`
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `connect_1`=".$user_id."
			LIMIT 1";
	if(!_num(query_value($sql))) {
		$sql = "UPDATE `_user_auth`
				SET `app_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `user_id`=".$user_id;
		query($sql);
	}

	_cache('clear', '_auth');
	_cache('clear', '_pageCache');
	_cache('clear', '_userCache'.$user_id);
}
function _pageUserAccessAllSave($cmp, $val, $unit) {//���������� ������� � ���������� ��� ���� �������������
	$sql = "UPDATE `_spisok`
			SET `num_1`=0
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011";
	query($sql);

	$ids = _ids($val);

	$sql = "UPDATE `_spisok`
			SET `num_1`=1
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011
			  AND `connect_1` IN (".$ids.")";
	query($sql);

	$sql = "UPDATE `_user_auth`
			SET `app_id`=0
			WHERE `app_id`=".APP_ID."
			  AND `user_id` NOT IN (".$ids.")";
	query($sql);

	_cache('clear', '_auth');
	_cache('clear', '_pageCache');

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011";
	foreach(query_arr($sql) as $r)
		_cache('clear', '_userCache'.$r['connect_1']);
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

	$DSRC_COND = _spisokCondDef($BL['obj_id']);

	//��������������� ��������� �������� ����� �����������
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` ".$DSRC_COND;
	query($sql);

	if(!$ids = _ids($unit['txt_2']))
		return;

	//��������� ������ �������� ��� ��������
	$sql = "SELECT `txt_2`,`num_8`
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
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=".$upd."
			WHERE `t1`.`id` ".$DSRC_COND;
	query($sql);
}
function _spisokUnitUpd54($unit) {//���������� ��������� ������������ ������ (��� �������� ��������)
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
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` "._spisokCondDef($BL['obj_id']);
	query($sql);

	$sql = "SELECT
				`".$cmp['col']."`,
				COUNT(`id`)
			FROM `"._table($DConn['table_1'])."`
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
		$sql = "UPDATE "._tableFrom($DSrc)."
				SET `".$unit['col']."`=".$c."
				WHERE `t1`.`id`=".$id." "._spisokCondDef($BL['obj_id']);
		query($sql);
/*
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._table($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
*/
	}

	//���������� ���� ������������ ��������, ���� ���� ��������
	if(!isset($DSrc['field1']['parent_id']))
		return;

	$sql = "SELECT DISTINCT `parent_id`
			FROM `"._table($DSrc['table_1'])."`
			WHERE `dialog_id`=".$BL['obj_id']."
			  AND `parent_id`";
	if(!$ids = query_ids($sql))
		return;

	foreach(_ids($ids, 1) as $id) {
		$sql = "SELECT SUM(`".$unit['col']."`)
				FROM `"._table($DSrc['table_1'])."`
				WHERE `parent_id`=".$id;
		$count = query_value($sql);
		$count += empty($ass[$id]) ? 0 : $ass[$id];

		$sql = "UPDATE `"._table($DSrc['table_1'])."`
				SET `".$unit['col']."`=".$count."
				WHERE `id`=".$id;
		query($sql);
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
	$sql = "UPDATE "._tableFrom($DSrc)."
			SET `".$unit['col']."`=0
			WHERE `t1`.`id` "._spisokCondDef($DSrc_id);
	query($sql);

	//��������� ��������, ������� ��������� �� �������, ����� �������� �������� ����� ����� �������
	if(!$elem_id = _num($unit['num_2']))
		return;
	if(!$elForSum = _elemQuery($elem_id))
		return;
	if(!$elForSum_id = _num($elForSum['txt_2']))
		return;
	if(!$cmpSum = @$DConn['cmp'][$elForSum_id])
		return;
	if(!$sum_col = $cmpSum['col'])
		return;

	$sql = "SELECT
				`".$cmp['col']."`,
				SUM(`".$sum_col."`)
			FROM `"._table($DConn['table_1'])."`
			WHERE `dialog_id`=".$dialog_id."
			  AND `app_id`=".APP_ID."
			  AND `".$cmp['col']."`
			  AND !`deleted`
			GROUP BY `".$cmp['col']."`";
	if(!$ass = query_ass($sql))//�����, ���� ������ ���������
		return;

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $c) {
		$sql = "UPDATE "._tableFrom($DSrc)."
				SET `".$unit['col']."`=".$c."
				WHERE `t1`.`id`=".$id." "._spisokCondDef($DSrc_id);
		query($sql);
/*
		$upd[] = "(".$id.",".$c.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._table($DSrc['table_1'])."`
						(`id`,`".$unit['col']."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$unit['col']."`=VALUES(`".$unit['col']."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
*/
	}
}

function _spisokUnitAfter($dialog, $unit_id, $unitOld=array()) {//���������� �������� ����� ���������� ������� ������
	if(!$dialog['table_1'])
		return;
	$sql = "SELECT *
			FROM `"._table($dialog['table_1'])."`
			WHERE `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return;

	//��������� ����������� �������, ������� �������� �� �������� ������
	//����� ��������, ���� �� �����-�� ���������, ������� ��������� � �������� �������
	$cmpInsertIds = array();
	foreach($dialog['cmp'] as $cmp) {
		if(!$cmp['col'])
			continue;
		if($cmp['dialog_id'] == 27)
			continue;
		if($cmp['dialog_id'] == 54)
			continue;
		if($cmp['dialog_id'] == 55)
			continue;
		$cmpInsertIds[] = $cmp['id'];
	}


	foreach($dialog['cmp'] as $cmp)
		switch($cmp['dialog_id']) {
			//���������� �����, ���� �����-�� ������� ������ ������� ��������� � �������� (��� ��������� ����)
			case 27:
				if(empty($cmpInsertIds))
					break;
				if(empty($cmp['txt_2']))
					break;

				$sql = "SELECT *
						FROM `_element`
						WHERE `num_1` IN (".implode(',', $cmpInsertIds).")
						  AND `id` IN (".$cmp['txt_2'].")";
				if(!$arr = query_arr($sql))
					break;

				$send = array();
				foreach($arr as $r) {
					$el = $dialog['cmp'][$r['num_1']];
					$send[] = array(
						'id' => $el['id'],
						'block_id' => $el['block_id'],
						'connect_id' => $unit_id     //id ������� ������, ������ ������� ����� ����������
					);
				}

				_spisokUnitAfter27($send);
				break;
			//����������� ������
			case 29:
				_spisokUnitAfter54($cmp, $dialog, $unit, $unitOld); //�������� ��������� ����������� ������ [54]
				$elUpd = _spisokUnitAfter55($cmp, $dialog, $unit);    //�������� c��� ����������� ������ [55]
				_spisokUnitAfter27($elUpd);                        //������� �������� ����� ���������� ���� [27]
				break;
		}
}
function _spisokUnitAfter54($cmp, $dialog, $unit, $unitOld) {//�������� ��������� ����������� ������
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

	$connect_old = 0;
	$count_old = 0;
	if(!empty($unitOld))
		if($connect_old = _num($unitOld[$col])) {
			//��������� ������� ���������� ��� ����������
			$sql = "SELECT COUNT(*)
					FROM "._tableFrom($dialog)."
					WHERE `".$col."`=".$connect_old." "._spisokCondDef($dialog['id']);
			$count_old = _num(query_value($sql));
		}

	//��������� ������ ���������� ��� ����������
	$sql = "SELECT COUNT(*)
			FROM "._tableFrom($dialog)."
			WHERE `".$col."`=".$connect_id." "._spisokCondDef($dialog['id']);
	$count = _num(query_value($sql));

	foreach($arr as $r) {
		if(!$col = $r['col'])
			continue;

		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		if($connect_old) {
			$sql = "UPDATE "._tableFrom($dlg)."
					SET `".$col."`=".$count_old."
					WHERE `t1`.`id`=".$connect_old." "._spisokCondDef($dlg['id']);
			query($sql);
		}

		$sql = "UPDATE "._tableFrom($dlg)."
				SET `".$col."`=".$count."
				WHERE `t1`.`id`=".$connect_id." "._spisokCondDef($dlg['id']);
		query($sql);
	}
}
function _spisokUnitAfter55($cmp, $dialog, $unit) {//�������� ���� ����������� ������ ����� ��������/�������� ������
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=55
			  AND `num_1`=".$cmp['id'];
	if(!$arr = query_arr($sql))
		return array();
	if(!$col = $cmp['col'])//��� �������, �� ������� �������� ������
		return array();
	if(!$connect_id = _num($unit[$col]))//��������, id ������� ������������ ������.
		return array();

	$send = array();//��������, ������� ���� �����������. �� ��� ����� ����� �������� ������, ���� �����������.
	foreach($arr as $elem_id => $r) {
		if(!$colSumSet = $r['col'])
			continue;
		//����� �������, �� ������� ����� ������������� ������� �����
		if(!$el = _elemQuery($r['num_2']))
			continue;
		if($el['dialog_id'] != 11)//������ ������� � �������� ����� ���������� ����� [11]
			continue;
		if(!$el = _elemQuery($el['txt_2']))
			continue;
		if(!$colSumGet = $el['col'])
			continue;

		$bl = _blockQuery($r['block_id']);
		$dlg = _dialogQuery($bl['obj_id']);

		//��������� ������ ���������� ��� ����������
		$sql = "SELECT IFNULL(SUM(`".$colSumGet."`),0)
				FROM "._tableFrom($dialog)."
				WHERE `".$col."`=".$connect_id." "._spisokCondDef($dialog['id']);
		$sum = query_value($sql);

		$sql = "UPDATE "._tableFrom($dlg)."
				SET `".$colSumSet."`=".$sum."
				WHERE `t1`.`id`=".$connect_id." "._spisokCondDef($dlg['id']);
		query($sql);

		$send[] = array(
			'id' => $elem_id,
			'block_id' => $r['block_id'],
			'connect_id' => $connect_id     //id ������� ������, ������ ������� ����� ����������
		);
	}

	return $send;
}
function _spisokUnitAfter27($elUpd) {
	if(empty($elUpd))
		return;

	foreach($elUpd as $el) {
		if(!$bl = _blockQuery($el['block_id']))
			continue;
		if($bl['obj_name'] != 'dialog')
			continue;
		if(!$dialog = _dialogQuery($bl['obj_id']))
			continue;

		foreach($dialog['cmp'] as $cmp) {
			if($cmp['dialog_id'] != 27)
				continue;
			if(empty($cmp['col']))//��� �������, ���������� ��������
				continue;
			if(empty($cmp['txt_2']))//������ id ���������, ������������ �����
				continue;

			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$cmp['txt_2'].")";
			if(!$arr = query_arr($sql))
				continue;

			$upd_flag = 0;//���� ���������� �������. ����� ����������, ���� ������������ �������, ����������� � ����������.
			foreach($arr as $r)
				if($r['txt_2'] == $el['id']) {
					$upd_flag = 1;
					break;
				}
			if(!$upd_flag)
				continue;

			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'txt_2').")";
			if(!$dlgElUpd = query_arr($sql))
				continue;

			$upd = '';
			foreach($arr as $r) {
				if(!$elUpd = $dlgElUpd[$r['txt_2']])
					continue;
				if(!$col = $elUpd['col'])
					continue;

				$znak = $r['num_8'] ? '-' : '+';
				$upd .= $znak."`".$col."`";
			}

			//������� ����������
			$sql = "UPDATE "._tableFrom($dialog)."
					SET `".$cmp['col']."`=".$upd."
					WHERE `t1`.`id`=".$el['connect_id']." "._spisokCondDef($dialog['id']);
			query($sql);
		}
	}
}




