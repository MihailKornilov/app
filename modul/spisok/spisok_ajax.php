<?php
switch(@$_POST['op']) {
	case 'spisok_add'://�������� ������ ������� � _spisok
		$page_id = _num($_POST['page_id']);
		$block_id = _num($_POST['block_id']);

		$v = _spisokUnitUpdate(0, $page_id, $block_id);

		$send['unit_id'] = $v['unit_id'];
		$send['action_id'] = _num($v['dialog']['action_id']);
		$send['page_id'] = _num($v['dialog']['action_page_id']);

		jsonSuccess($send);
		break;
/*
	case 'spisok_edit_load'://��������� ������ ������ ��� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ �������������');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		if($r['deleted'])
			jsonError('������ ���� �������');

		if(!$dialog = _dialogQuery($r['dialog_id']))
			jsonError('������� �� ����������');

		$html = '<table class="bs10">'._dialogComponentSpisok($r['dialog_id'], 'html', $r).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['component'] = _dialogComponentSpisok($r['dialog_id'], 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
*/
	case 'spisok_edit'://���������� ������ ������ ��� �������
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('������������ �������������');

		$v = _spisokUnitUpdate($unit_id);

		$send['unit_id'] = $v['unit_id'];
		$send['action_id'] = _num($v['dialog']['action_id']);
		$send['page_id'] = _num($v['dialog']['action_page_id']);

		jsonSuccess($send);
		break;
	case 'spisok_del'://�������� ������ �� _spisok
		if(!$id = _num($_POST['id']))
			jsonError('������������ �������������');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		if($r['deleted'])
			jsonError('������ ��� ���� �������');

		$sql = "UPDATE `_spisok`
				SET `deleted`=1
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'spisok_next'://�������� ������
		if(!$pe_id = _num($_POST['pe_id']))
			jsonError('������������ ID �������� �������');
		if(!$next = _num($_POST['next']))
			jsonError('������������ �������� ���������� �����');

		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `id`=".$pe_id;
		if(!$pe = query_assoc($sql))
			jsonError('�������� id'.$pe_id.' �� ����������');

		if($pe['dialog_id'] != 14)
			jsonError('������� �� �������� �������');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$pe['block_id'];
		if(!$pe['block'] = query_assoc($sql))
			jsonError('����������� ���� ������');

		$send['type'] = _num($pe['num_1']);
		$send['spisok'] = utf8(_spisokShow($pe, $next));
		jsonSuccess($send);
		break;
	case 'spisok_search'://��������� ����������� ������ �� ��������
		if(!$element_id = _num($_POST['element_id']))
			jsonError('������������ ID �������� �������');

		$v = _txt($_POST['v']);

		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `id`=".$element_id;
		if(!$pe = query_assoc($sql))
			jsonError('�������� id'.$element_id.' �� ����������');

		//���������� ������ ������
		$sql = "UPDATE `_page_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$element_id;
		query($sql);

		//id ������� ������, �� ������� ���������� ����������� ����� �����
		if(!$pe_id = _num($pe['num_3']))
			jsonError('��� ����������� �� ������');

		//������������ ������ �� ��������, �� ������� ���������� �����
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('��� ������� ������ �� ��������');

		//������� ���������� ������ �� ��������, �� ������� ���������� �����
		$sql = "SELECT *
				FROM `_page_element`
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
	case 'spisok_col_get'://��������� ������� ������ (��� ������� �������� 5 � 6)
		if(!$cmp_id = _num($_POST['component_id']))
			jsonError('������������ ID ���������� �������');
		if(!$page_id = _num($_POST['page_id']))
			jsonError('������������ ID ��������');
		if(!$vid = _num($_POST['vid']))
			jsonError('������������ �������� ��� ������ ������');

		$sql = "SELECT *
				FROM `_dialog_component`
				WHERE `id`=".$cmp_id;
		if(!$cmp = query_assoc($sql))
			jsonError('���������� �� ����������');

		//��������: ��������� ������ ���� ���������� ������� � ������������ ��������� ������
		if($cmp['type_id'] != 2 || !$cmp['num_4'])
			jsonError('��������� �� �������� �������� �������');
		if(!$cmpDialog = _dialogQuery($cmp['dialog_id']))
			jsonError('�������, � ������� ���������� ���������, �� ����������');

		$cmpFuncAss = $cmpDialog['component'][$cmp_id]['func_action_ass'];
		$f5 = !empty($cmpFuncAss[5]);
		$f6 = !empty($cmpFuncAss[6]);
		if(!$f5 && !$f6)
			jsonError('����������� ������� ��������� �������');

		//��������� dialog_id ������
		$dialog_id = $vid;  //���� �� ����������� ������� "������ � ������� ��������", �� ��� � ���� ��� ����� dialog_id
		$colIds = array();
		if($cmp['num_5']) {//��������� dialog_id �� �������� ��������
			$sql = "SELECT *
					FROM `_page_element`
					WHERE `page_id`=".$page_id."
					  AND `id`=".$vid;
			if(!$pe = query_assoc($sql))
				jsonError('�������� �� �������� �� ����������');
			if(!$dialog_id = _num($pe['num_3']))
				jsonError('��� ������������ ������ �� ��������');

			$colIds = _idsAss($pe['txt_3']);  //��������� �������� ������� �������� 5
		}

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');
		if(!$dialog['spisok_on'])
			jsonError('������ �� �������� �������');
		if(empty($dialog['component']))
			jsonError('� ������ ������ ������� ���');

		$col = '';
		$radioAss = array();
		foreach($dialog['component'] as $id => $r) {
			if($r['type_id'] == 7)
				continue;
			if($r['type_id'] == 8)
				continue;
			if($r['type_id'] == 9)
				continue;

			$title = $r['label_name'].' <span class="grey i">'._dialogEl($r['type_id'], 'name').'</span>';
			
			if($f6) {
				$radioAss[$id] = $title;
				continue;
			}

			if($r['type_id'] == 1)
				continue;
			if($r['type_id'] == 5)
				continue;
			if($r['type_id'] == 6)
				continue;
			$col .=
				'<div class="mb5">'.
					_check(array(
						'id' => 'col'.$id,
						'title' => $title,
						'value' => @$colIds[$id]
					)).
				'</div>';
		}

		if($f6)
			$col = _radio(array(
				'spisok' => $radioAss,
				'light' => 1,
				'interval' => 5
			));

		
		$send['html'] = utf8(
			'<table class="bs5 w100p">'.
				'<tr><td class="label r top" style="width:'.$cmpDialog['label_width'].'px">������'.($f6 ? '�' : '�').':'.
					'<td>'.$col.
			'</table>'
		);

		jsonSuccess($send);
		break;
	case 'spisok_elem_list'://��������� ������� ������ (��� ������� �������� 7)
		if(!$cmp_id = _num($_POST['component_id']))
			jsonError('������������ ID ���������� �������');
		if(!$elem_id = _num($_POST['elem_id'])) {
			$send['after'] = utf8('<div class="center fs14 i grey">��������� ������� ����� ��������<br />����� ���������� ������ �� ��������.</div>');
			jsonSuccess($send);
		}
		if(!$spisok_id = _num($_POST['spisok_id']))
			jsonError('������������ ID ������');
		if(!$dialog = _dialogQuery($spisok_id))
			jsonError('������� ��� �������� ������ �� ����������');

		$sql = "SELECT *
				FROM `_page_element`
				WHERE `id`=".$elem_id;
		if(!$elem = query_assoc($sql))
			jsonError('�������� �� ����������');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$elem['block_id'];
		if(!$block = query_assoc($sql))
			jsonError('����� �� ����������');

		$CMP = $dialog['component'];

		$labelName = array();
		$labelName[] = array(
			'uid' => -1,
			'title' => utf8('���������� �����'),
			'content' => utf8('<div class="color-pay">���������� �����</div>'),
			'link_on' => 1
		);

		$arrDef = array();//������ ������� �� ���������, ���� ��������� ������ ������������ �������

		switch($elem['num_1']) {
			default:
			case 181://�������
				$html =
					'<div class="hd2">���������� ������:</div>'.
					'<div class="mar10">'.
						'<div class="ml30 mb10">'.
							_check(array(
								'id' => 'rowSmall',
								'title' => '����� ������',
								'light' => 1,
								'value' => $elem['num_7']
							)).
							_check(array(
								'id' => 'colNameShow',
								'title' => '���������� ����� �������',
								'light' => 1,
								'value' => $elem['num_5'],
								'class' => 'ml30'
							)).
							_check(array(
								'id' => 'rowLight',
								'title' => '������������ ������ ��� ���������',
								'light' => 1,
								'value' => $elem['num_6'],
								'class' => 'ml30'
							)).
						'</div>'.
						'<dl></dl>'.
						'<div class="item-add center pad15 fs15 color-555 over1 curP">�������� �������</div>'.
					'</div>';
				break;
			case 182://������
				setcookie('block_level_spisok', 1, time() + 2592000, '/');
				$html =
					'<div class="hd2 mt20">��������� ������� ������� ������:</div>'.
					'<button id="but-spisok-tmp-grid" class="vk small grey mt10" onclick="_spisokTmpBlock($(this),'.$block['id'].')" val="��������� ��������� ������">�������� ��������� ������</button>'.
					'<div id="tmp-elem-list" class="mt10" style="width:'.$block['width'].'px">'._spisokUnitSetup($block['id'], $block['width']).'</div>';
				break;
		}

		foreach($CMP as $r) {
			if(!_dialogEl($r['type_id'], 'func'))
				continue;

			$labelName[] = array(
				'uid' => $r['id'],
				'title' => utf8($r['label_name'] ? $r['label_name'] : $r['type_name']),
				'content' => utf8($r['label_html']),
				'link_on' => 1
			);

			if(!$r['label_name'])
				continue;

			$arrDef[] = array(
				'id' => $r['id'],
				'tr' => utf8($r['label_name'])
			);
		}

		if($elem['num_1'] == 182)
			$labelName[] = array(
				'uid' => -4,
				'title' => utf8('������������ �����'),
				'content' => utf8('<div class="color-pay b">������������ �����</div>')
			);
		$labelName[] = array(
			'uid' => -2,
			'title' => utf8('���� ��������'),
			'content' => utf8('<div class="color-pay">���� ��������</div>')
		);
		if($elem['num_1'] == 181)
			$labelName[] = array(
				'uid' => -3,
				'title' => utf8('������ ����������'),
				'content' => utf8('<div class="color-pay">������ ����������</div>')
			);

		//������ ����������� ������� (��� 181)
		$arr = array();
		if(!empty($elem['txt_5']))
			foreach(explode(',', $elem['txt_5']) as $col) {
				$ex = explode('&', $col);
				$arr[] = array(
					'id' => $ex[0],
					'tr' => utf8($ex[1]),
					'link_on' => _num(@$ex[2]),
					'link' => _num(@$ex[3])
				);
			}

		$send['label_name_select'] = $labelName;//�������� ������� ��� select
		$send['arr'] = empty($elem['txt_5']) || $spisok_id != $elem['num_3'] ? $arrDef : $arr;//�������, ������� ���� ���������
		$send['spisok_type'] = _num($elem['num_1']);
		$send['html'] = utf8($html);

		jsonSuccess($send);
		break;
	case 'spisok_tmp_block_on'://��������� ������ ������� ������ ��� ��������� (grid)
		if(!$block_id = _num($_POST['block_id']))
			jsonError('������������ ID �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$block_id.' �� ����������');

		//��������� ������ ���������
		$sql = "SELECT *
				FROM `_block`
				WHERE `parent_id`=".$block_id."
				ORDER BY `y`,`x`";
		$arr = query_arr($sql);

		$send['html'] = utf8(_blockGrid($arr));
		$send['w'] = $block['w'];

		jsonSuccess($send);
		break;
	case 'spisok_tmp_block_off':
		if(!$block_id = _num($_POST['block_id']))
			jsonError('������������ ID �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		if(!$bl = query_assoc($sql))
			jsonError('����� id'.$block_id.' �� ����������');

		$send['html'] = utf8(_spisokUnitSetup($block_id, $bl['width']));

		jsonSuccess($send);
		break;
	case 'spisok_tmp_elem_to_block'://������� �������� � ���� ������� ������
		if(!$block_id = _num($_POST['block_id']))
			jsonError('������������ ID �����');
		if(!$num_1 = _num($_POST['num_1'], true))
			jsonError('������� �� �������');

		if($elem_id = _num($_POST['elem_id'])) {
			$sql = "SELECT *
					FROM `_page_element`
					WHERE `id`=".$elem_id;
			if(!query_assoc($sql))
				jsonError('�������������� �������� id'.$elem_id.' �� ����������');
		}

		$num_2 = _num($_POST['num_2']);
		$txt_2 = $num_1 == -4 ? _txt($_POST['txt_2']) : '';

		if($num_1 > 0 && !$num_2)
			jsonError('�� ������ ��� ����������');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_block`
				WHERE `is_spisok`
				  AND `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$block_id.' �� ����������');

		//�������� ������� �������� � �����
		if(!$elem_id) {
			$sql = "SELECT COUNT(`id`)
					FROM `_page_element`
					WHERE `block_id`=".$block_id;
			if(query_value($sql))
				jsonError('� ������ ����� ��� ������������ �������');
		}

		//��������� ��������, � ������� ��������� ������ ������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `block_id`=".$block['is_spisok'];
		if(!$elSpisok = query_assoc($sql))
			jsonError('������ �� ����������');

		//������, ����� ������� �������� ������� ������
		$dialog_id = $elSpisok['num_3'];

		$sql = "INSERT INTO `_page_element` (
					`id`,
					`page_id`,
					`block_id`,
					`num_1`,
					`num_2`,
					`num_3`,
					`txt_2`,
					`viewer_id_add`
				) VALUES (
					".$elem_id.",
					".$block['page_id'].",
					".$block_id.",
					".$num_1.",
					".$num_2.",
					".$dialog_id.",
					'".addslashes($txt_2)."',
					".VIEWER_ID."
				) ON DUPLICATE KEY UPDATE
					`num_1`=VALUES(`num_1`),
					`num_2`=VALUES(`num_2`),
					`txt_2`=VALUES(`txt_2`)";
		query($sql);

		jsonSuccess();
		break;

	case 'spisok_select_get'://��������� ������ ��� �������
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID �������');
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('������������ ID ���������� �������');

		$v = _txt($_POST['v']);

		$send['spisok'] = _spisokList($dialog_id, $cmp_id, $v);

		jsonSuccess($send);
		break;
}


function _spisokUnitUpdate($unit_id=0, $page_id=0, $block_id=0) {//��������/�������������� ������ ������
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
	define('BASE_TABLE', $dialog['base_table']);
	$sql = "SHOW TABLES LIKE '".BASE_TABLE."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('������� �� ����������');

	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".BASE_TABLE."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		if(@$r['deleted'])
			jsonError('������ ���� �������');
	}

	//�������� �������� �� ��������
	if($dialog_id == 6) {
		$sql = "DELETE FROM `_page_element` WHERE `id`=".$unit_id;
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

		if($r['require'] && empty($v))
			jsonError(array(
				'delem_id' => $id,
				'text' => utf8('�� ��������� ����<br><b>'.$r['label_name'].'</b>')
			));

		//���� ��� ���������� ������, ���������� ������ � ������ � ������ ���������
//		if($r['type_id'] == 2 && $dialog['base_table'] == '_page_element' && $r['num_1'])
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
		$sql = "INSERT INTO `".BASE_TABLE."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id(BASE_TABLE);
		$send['unit_id'] = $unit_id;

		//���������� ��������� �������
		$sql = "DESCRIBE `".BASE_TABLE."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//��������� ����������� ������
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".BASE_TABLE."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".BASE_TABLE."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'block_id') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `block_id`=".$block_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'sort') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `sort`="._maxSql(BASE_TABLE)."
						WHERE `id`=".$unit_id;
				query($sql);
			}			
		}
	}

	$sql = "UPDATE `".BASE_TABLE."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//���������� ������� �����������
	foreach($dialog['component'] as $id => $r)
		_spisokUnitFuncValUpdate($dialog, $id, $unit_id);

	if(BASE_TABLE == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
function _spisokUnitFuncValUpdate($dialog, $cmp_id, $unit_id) {//���������� �������� ������� ����������� (���� ��������� �������� 5)
	if(!$unit_id)
		return;
	if(!isset($_POST['func'][$cmp_id]))
		return;

	$cmp = $dialog['component'][$cmp_id];
	$v = $_POST['func'][$cmp_id];

	//�������� ������� ������� � ����������
	$f5 = !empty($cmp['func_action_ass'][5]);
	$f6 = !empty($cmp['func_action_ass'][6]);
	$f7 = !empty($cmp['func_action_ass'][7]);
	if(!$f5 && !$f6 && !$f7)
		return;

	//���� ��������� �� �������� ������
	if(!$cmp['num_4'])
		return;

	if($f5) {
		//���� ������ �� �� ��������
		if(!$cmp['num_5'])
			return;

		//��������� id �������� ��������, � ������� ����� ���������� ��������
		$sql = "SELECT `num_3`
				FROM `".BASE_TABLE."`
				WHERE `id`=".$unit_id;
		if(!$pe_id = query_value($sql))
			return;

		$sql = "UPDATE `".BASE_TABLE."`
				SET `txt_3`='".addslashes($v)."'
				WHERE `id`=".$pe_id;
		query($sql);
		return;
	}

	if($f6) {
		$sql = "UPDATE `".BASE_TABLE."`
				SET `num_3`='".addslashes($v)."'
				WHERE `id`=".$unit_id;
		query($sql);
		return;
	}

	if($f7) {
		switch(is_array($v)) {
			default://[181] �������
				$ex = explode(',', $v);
				$num_5 = _num($ex[0]);
				$num_6 = _num(@$ex[1]);
				$num_7 = _num(@$ex[2]);

				$txt_5 = array();
				foreach($ex as $k => $r) {
					if($k < 3)
						continue;

					$rex = explode('&', $r);
					if(!$id = _num($rex[0], 1))
						continue;
					$tr = _txt(@$rex[1]);
					$link_on = _num(@$rex[2]);
					$link = _num(@$rex[3]);

					$txt_5[] = $id.'&'.$tr.'&'.$link_on.'&'.$link;
				}

				$sql = "UPDATE `".BASE_TABLE."`
						SET `num_5`=".$num_5.",
							`num_6`=".$num_6.",
							`num_7`=".$num_7.",
							`txt_5`='".implode(',', $txt_5)."'
						WHERE `id`=".$unit_id;
				query($sql);

				return;
			case 1://[182] ������

				break;
		}
	}
}
