<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'id' => 0,
			'sa' => 0,
			'width' => 500,

			'head_insert' => '�������� ����� ������',
			'button_insert_submit' => '������',
			'button_insert_cancel' => '������',

			'head_edit' => '���������� ������',
			'button_edit_submit' => '���������',
			'button_edit_cancel' => '������',

			'base_table' => '_spisok',

			'spisok_on' => 0,
			'spisok_name' => '',

			'action_id' => 1,
			'action_page_id' => 0,

			'menu_edit_last' => 1
		);

		if($dialog_id = _num($_POST['dialog_id']))
			if($ass = _dialogQuery($dialog_id))
				$dialog = $ass;
			else
				$dialog_id = 0;

		$menu = array(
			1 => '���������',
			2 => '����������',
			4 => 'CMP new',
  		    3 => '��������',
//  		4 => '�������',
//			5 => '����������� �����',
			9 => '<b class="red">SA</b>'
		);

		$action = array(
			3 => '�������� ���������� ������',
			1 => '�������� ��������',
			2 => '������� �� ��������'
		);

		if(!SA) {
			unset($menu[9]);

			if($dialog['menu_edit_last'] == 9)
				$dialog['menu_edit_last'] = 2;
		}

		define('BLOCK_EDIT', 1);

		$html =
			'<div id="dialog-w-change"></div>'.//������ ������������ ����� ��� ��������� ������ �������

			'<div class="pad10 center bg-gr3 line-b">'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//��������� � ������
			'<div class="dialog-menu-1">'.
				'<div class="pad10">'.
					'<div class="hd2">�������� ����� ������</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">���������:'.
							'<td><input type="text" id="head_insert" class="w250" maxlength="200" placeholder="�������� ����������� ���� - ����� ������" value="'.$dialog['head_insert'].'" />'.
						'<tr><td class="label r">����� ������ <b>��������</b>:<td><input type="text" id="button_insert_submit" class="w150" maxlength="100" value="'.$dialog['button_insert_submit'].'" />'.
						'<tr><td class="label r">����� ������ <b>������</b>:<td><input type="text" id="button_insert_cancel" class="w150" maxlength="100" value="'.$dialog['button_insert_cancel'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-ffd line-t1 pad10">'.
					'<div class="hd2">�������������� ������</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">���������:'.
							'<td><input type="text" id="head_edit" class="w250" maxlength="200" placeholder="�������� ����������� ���� - ��������������" value="'.$dialog['head_edit'].'" />'.
						'<tr><td class="label r">����� ������ <b>����������</b>:<td><input type="text" id="button_edit_submit" class="w150" maxlength="100" value="'.$dialog['button_edit_submit'].'" />'.
						'<tr><td class="label r">����� ������ <b>������</b>:<td><input type="text" id="button_edit_cancel" class="w150" maxlength="100" value="'.$dialog['button_edit_cancel'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-gr2 line-t1 pad10">'.
					'<div class="hd2">���������</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">����� ���� �������:'.
							'<td><input type="hidden" id="spisok_on" value="'.$dialog['spisok_on'].'" />'.
						'<tr id="tr_spisok_name" class="'.($dialog['spisok_on'] ? '' : 'dn').'">'.
							'<td class="label r">��� ������:'.
							'<td><input type="text" id="spisok_name" class="w200" maxlength="100" value="'.$dialog['spisok_name'].'" />'.
					'</table>'.
				'</div>'.
			'</div>'.

			//����������
			'<div class="dialog-menu-2">'.
				'<div class="pt10 mb10 ml10 mr10 prel">'.
					'<dl id="dialog-base" class="_sort pad5" val="_dialog_component">'.
						_dialogComponentSpisok($dialog_id, 'html_edit').
					'</dl>'.
				'</div>'.
				'<div class="pad20 center bg-ffd line-t1">'.
					'<button class="vk green" onclick="_dialogCmpEdit()">����� ���������</button>'.
				'</div>'.
			'</div>'.

			//���������� - �����
			'<div class="dialog-menu-4">'.
				'<div class="pad10 line-b bg-ffc">'.
					_blockLevelChange('dialog', $dialog_id, $dialog['width']).
				'</div>'.
				'<div class="block-content-dialog" style="width:'.$dialog['width'].'px">'.
					_blockHtml('dialog', $dialog_id, $dialog['width']).
				'</div>'.
			'</div>'.

			//��������
			'<div class="dialog-menu-3 pb20">'.
				'<div class="_info mar20">'.
					'���������� ��������, ������� ���������� ����� �������� ��� ���������� ������.'.
				'</div>'.
				'<table class="bs10">'.
					'<tr><td class="label r w100">��������:'.
						'<td><input type="hidden" id="action_id" value="'.$dialog['action_id'].'" />'.
					'<tr class="td-action-page'._dn($dialog['action_id'] == 2).'">'.
						'<td class="label r">��������:'.
						'<td><input type="hidden" id="action_page_id" value="'.$dialog['action_page_id'].'" />'.
				'</table>'.
			'</div>'.

			//������
//			'<div class="dialog-menu-3">������ � ��������</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pt20 pb20">'.
				'<table class="bs10">'.
					'<tr><td class="red w175 r">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="red r">������:<td id="dialog-width">'.$dialog['width'].
					'<tr><td class="red r">������� � ����:'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					//����������� �������. �� ��������� app_id. �� ��������� 0 - ���������� ����.
					'<tr><td class="red r">App any:'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'value' => $dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0
							   )).
					'<tr><td class="red r">SA only:'.
						'<td>'._check(array(
									'attr_id' => 'sa',
									'value' => $dialog['sa']
							   )).
				'</table>'.
			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['menu'] = _selArray($menu);
		$send['block_arr'] = _blockJsArr('dialog', $dialog_id);
		$send['action'] = _selArray($action);
		$send['component'] = _dialogComponentSpisok($dialog_id, 'arr_edit');
		$send['cmp'] = $dialog['cmp_utf8'];
		$send['spisok_on'] = _dialogSpisokOn();
//		$send['func'] = (object)$dialog['func'];
		$send['html'] = utf8($html);
		$send['sa'] = SA;
		jsonSuccess($send);
		break;
/*	case 'dialog_add'://�������� ������ ����������� ����
		$send['dialog_id'] = _dialogUpdate(0);
		jsonSuccess($send);
		break;
*/
	case 'dialog_edit'://���������� ����������� ����
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');

		_dialogUpdate($dialog_id);

		$send = _dialogOpenLoad($dialog_id);

		jsonSuccess($send);
		break;

	case 'dialog_open_load'://��������� ������ �������
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID �������');

		$send = _dialogOpenLoad($dialog_id);

		jsonSuccess($send);
		break;

	case 'dialog_spisok_on_col_load'://��������� �������� ������� ����������� ������ �� ����������� �������
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID �������');

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');

		$sql = "SELECT
					`id`,
					`label_name`
				FROM `_dialog_component`
				WHERE `dialog_id`=".$dialog_id."
				  AND LENGTH(`label_name`)
				ORDER BY `sort`";
		$send['spisok'] = query_selArray($sql);

		jsonSuccess($send);
		break;

	case 'page_sort'://���������� �������
		$arr = $_POST['arr'];
		if(!is_array($arr))
			jsonError('�� �������� ��������');

		$update = array();
		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;
			$parent_id = _num($r['parent_id']);
			$update[] = "(".$id.",".$parent_id.",".$n.")";
		}

		if(empty($update))
			jsonError('��� ������ ��� ����������');

		$sql = "INSERT INTO `_page` (
					`id`,
					`parent_id`,
					`sort`
				) VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`parent_id`=VALUES(`parent_id`),
					`sort`=VALUES(`sort`)";
		query($sql);

		_cache('clear', '_pageCache');

		jsonSuccess();
		break;
}

function _dialogUpdate($dialog_id) {//���������� �������
	if(!$head_insert = _txt($_POST['head_insert']))
		jsonError('�� ������ ��������� ��� �������� ������');
	if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
		jsonError('�� ������ ����� ������ ��������');
	if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
		jsonError('�� ������ ����� ������ ������ ��� ����� ������');

	if(!$head_edit = _txt($_POST['head_edit']))
		jsonError('�� ������ ��������� ��������������');
	if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
		jsonError('�� ������ ����� ������ ����������');
	if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
		jsonError('�� ������ ����� ������ ������ ��������������');

	if(!$width = _num($_POST['width']))
		jsonError('������������ �������� ������ �������');
	if($width < 480 || $width > 980)
		jsonError('����������� ������������ ������ �������');

	if(!$base_table = _txt($_POST['base_table']))
		$base_table = '_spisok';

	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_name = _txt($_POST['spisok_name']);
	if($spisok_on && !$spisok_name)
		jsonError('������� ��� ������ ��������');

	$app_any = _num($_POST['app_any']);
	$action_id = _num($_POST['action_id']);
	$action_page_id = _num($_POST['action_page_id']);

	if(!$dialog_id) {
		$sql = "INSERT INTO `_dialog` (
					`app_id`
				) VALUES (
					".APP_ID."
				)";
		query($sql);
		$dialog_id = query_insert_id('_dialog');
	}

	if(!_dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`width`=".$width.",

				`head_insert`='".addslashes($head_insert)."',
				`button_insert_submit`='".addslashes($button_insert_submit)."',
				`button_insert_cancel`='".addslashes($button_insert_cancel)."',

				`head_edit`='".addslashes($head_edit)."',
				`button_edit_submit`='".addslashes($button_edit_submit)."',
				`button_edit_cancel`='".addslashes($button_edit_cancel)."',

				`base_table`='".addslashes($base_table)."',
				`spisok_on`=".$spisok_on.",
				`spisok_name`='".addslashes($spisok_name)."',

				`action_id`=".$action_id.",
				`action_page_id`=".$action_page_id.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

//	_dialogComponentUpdate($dialog_id);
//	_dialogFuncUpdate($dialog_id);

	_cache('clear', '_dialogQuery'.$dialog_id);

	return $dialog_id;
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	//��������� ������ ������� ������
	$unit = array();
	if($unit_id = _num($_POST['unit_id'])) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT *
				FROM `".$dialog['base_table']."`
				WHERE ".$cond;
		if(!$unit = query_assoc($sql))
			jsonError('������ �� ����������');
		if(@$unit['sa'] && !SA)
			jsonError('��� �������');
		if(@$unit['deleted'])
			jsonError('������ ���� �������');
	}

	$send['dialog_id'] = $dialog_id;
	$send['unit_id'] = $unit_id;
	$send['block_id'] = _num($_POST['block_id']);

	//�������� ������, ���������� ��� �������� �������
	$unit['source'] = array(
		'block_id' => $send['block_id']//��� ������ ����� ��� ������
	);

	$send['edit_access'] = SA || $dialog['app_id'] == APP_ID ? 1 : 0;//����� ��� �������������� �������
	$send['width'] = _num($dialog['width']);
	$send['head'] = utf8($dialog[!$unit_id ? 'head_insert' : 'head_edit']);
	$send['button_submit'] = utf8($dialog[!$unit_id ? 'button_insert_submit' : 'button_edit_submit']);
	$send['button_cancel'] = utf8($dialog[!$unit_id ? 'button_insert_cancel' : 'button_edit_cancel']);
	$send['html'] = utf8(_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $unit));
	$send['cmp'] = $dialog['cmp_utf8'];
	$send['spisok_on'] = _dialogSpisokOn();

	foreach($unit as $id => $r)
		$unit[$id] = utf8($r);

	//������� ���������� ��� ��������� �����������
	foreach($dialog['cmp'] as $cmp)
		switch($cmp['dialog_id']) {
			case 19:
				if(!$col = $cmp['col'])
					break;
				$unit[$col] = array();
				if($unit_id) {
					$sql = "SELECT *
							FROM `_element_value`
							WHERE `dialog_id`=".$dialog_id."
							  AND `element_id`=".$unit_id."
							ORDER BY `sort`";
					foreach(query_arr($sql) as $id => $r)
						$unit[$col][_num($id)] = array(
							'id' => _num($id),
							'title' => utf8($r['title']),
							'def' => _num($r['def']),
							'use' => 0  //���������� ������������� ��������, ����� ������ ���� �������
						);

					//���� ��� ��������
					if(empty($unit[$col]))
						break;

					if(empty($unit['col']))
						break;

					//������, � ������� ��������� ���� � ���������
					$sql = "SELECT *
							FROM `_block`
							WHERE `id`=".$unit['block_id'];
					if(!$block = query_assoc($sql))
						break;

					//���� ������ ��� ��������
					if($block['obj_name'] != 'dialog')
						break;

					//��������� ���������� ������������� ��������
					$sql = "SELECT
								`".$unit['col']."` `id`,
								COUNT(*) `use`
							FROM `_element`
							WHERE `dialog_id`=".$block['obj_id']."
							GROUP BY `".$unit['col']."`";
					foreach(query_ass($sql) as $id => $use)
						if(isset($unit[$col][$id]))
							$unit[$col][$id]['use'] = $use;
				}
		}

	$send['unit'] = $unit;

	//���� ������������ �������� ������� ������
	if($send['to_del'] = _num(@$_POST['to_del'])) {
		if(!$unit_id)
			jsonError('����������� ������� ������ ��� ��������');

		$html =
			'<div class="pad20">'.
				'<div class="_info b">����������� �������� ������.</div>'.
			'</div>';

		$send['head'] = utf8('�������� ������');
		$send['html'] = utf8($html);
		$send['button_submit'] = utf8('�������');
	}

	return $send;
}














function _dialogFuncUpdate($dialog_id) {//���������� ������� ����������� �������
	$sql = "DELETE FROM `_dialog_component_func`
			WHERE `dialog_id`=".$dialog_id;
	query($sql);

	if(!$func = @$_POST['func'])
		return;

	$insert = array();
	foreach($func as $component_id => $arr)
		foreach($arr as $k => $r) {
			$insert[] = "(
				".$dialog_id.",
				".$component_id.",
				".$r['action_id'].",
				".$r['cond_id'].",
				'".addslashes($r['ids'])."'
			)";
		}

	$sql = "INSERT INTO `_dialog_component_func` (
				`dialog_id`,
				`component_id`,
				`action_id`,
				`cond_id`,
				`component_ids`
			) VALUES ".implode(',', $insert);
	query($sql);
}

function _dialogComponentUpdate($dialog_id=0) {//��������/�������� ��������� �������
	if(!$arr = @$_POST['component'])
		jsonError('����������� ���������� �������');
	if(!is_array($arr))
		jsonError('������������ ������ ����������� �������');

	foreach($arr as $r) {
		if(!$type_id = _num($r['type_id']))
			jsonError('������������ ��� ����������');

		$component_id = _num($r['id']);

		//�������� �� ������ ���� ����� ����������� �������
		switch($type_id) {
			case 1://check
				if(!_txt($r['label_name']) && !_txt($r['txt_1']))
					jsonError('������� �������� ����,<br />���� ����� ��� �������');
				break;
			case 2://select
				if($dialog_id)
					if(_num($r['num_4']) || _num($r['num_1'])) {
						$sql = "DELETE FROM `_dialog_component_v`
								WHERE `component_id`=".$component_id;
						query($sql);
						$r['v'] = array();
					}
				break;
			case 3://text
				break;
			case 4://textarea
				break;
			case 5://radio
				if(empty($r['v']))
					jsonError('����������� �������� �������� Radio');
				break;
			case 6://���������
				break;
			case 7://info
				break;
			case 8://connect
				break;
			case 9://head
				break;
			default:
				jsonError('�������������� ��� ����������');
		}
	}

	//������ ������ - ������������
	if(!$dialog_id)
		return;

	//�������� �������� �����������
	$sql = "DELETE FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			  AND `id` NOT IN ("._idsGet($arr).")";
	query($sql);

	//�������� �������� �������� �����������
	$sql = "DELETE FROM `_dialog_component_v`
			WHERE `dialog_id`=".$dialog_id."
			  AND `component_id` NOT IN ("._idsGet($arr).")";
	query($sql);

	$sort = 0;
	foreach($arr as $r) {
		$component_id = _num($r['id']);
		$type_id = _num($r['type_id']);
		$col_name = _txt($r['col_name']);

		if(!$component_id && !$col_name) {
			//������������ �������� ���� �� ��������� ���� ��������
			$pole = array(
				1 => 'num',
				2 => 'num',
				3 => 'txt',
				4 => 'txt',
				5 => 'num',
				6 => 'date',
				7 => 'txt',
				8 => 'num',
				9 => 'txt'
			);
			$sql = "SELECT `col_name`,1
					FROM `_dialog_component`
					WHERE `dialog_id`=".$dialog_id."
					  AND `col_name` LIKE '".$pole[$type_id]."_%'
					ORDER BY `col_name`";
			$ass = query_ass($sql);
			for($n = 1; $n <= 5; $n++) {
				$col_name = $pole[$type_id].'_'.$n;
				if(!isset($ass[$col_name]))
					break;
			}
		}

		$label_name = _txt($r['label_name']);
		$req = _bool($r['req']);
		$hint = _txt($r['hint']);
		$width = _num($r['width']);

		$sql = "INSERT INTO `_dialog_component` (
					`id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`req`,
					`hint`,
					`width`,
					`txt_1`,
					`txt_2`,
					`txt_3`,
					`num_1`,
					`num_2`,
					`num_3`,
					`num_4`,
					`num_5`,
					`col_name`,
					`sort`
				) VALUES (
					".$component_id.",
					".$dialog_id.",
					".$type_id.",
					'".addslashes($label_name)."',
					".$req.",
					'".addslashes($hint)."',
					".$width.",
					'".addslashes(_txt($r['txt_1']))."',
					'".addslashes(_txt($r['txt_2']))."',
					'".addslashes(_txt($r['txt_3']))."',
					"._num($r['num_1']).",
					"._num($r['num_2']).",
					"._num($r['num_3']).",
					"._num($r['num_4']).",
					"._num($r['num_5']).",
					'".$col_name."',
					".($sort++)."
				)
				ON DUPLICATE KEY UPDATE
					`label_name`=VALUES(`label_name`),
					`req`=VALUES(`req`),
					`hint`=VALUES(`hint`),
					`width`=VALUES(`width`),
					`txt_1`=VALUES(`txt_1`),
					`txt_2`=VALUES(`txt_2`),
					`txt_3`=VALUES(`txt_3`),
					`num_1`=VALUES(`num_1`),
					`num_2`=VALUES(`num_2`),
					`num_3`=VALUES(`num_3`),
					`num_4`=VALUES(`num_4`),
					`num_5`=VALUES(`num_5`),
					`col_name`=VALUES(`col_name`)";
		query($sql);

		if(!$component_id)
			$component_id = query_insert_id('_dialog_component');

		//�������� ���� ���������, ���� ����
		if(empty($r['v'])) {
			$sql = "DELETE FROM `_dialog_component_v`
					WHERE `component_id`=".$component_id;
			query($sql);
			continue;
		}

		//�������� �������� �������� ��������
		if($ids = _idsGet($r['v'])) {
			$sql = "DELETE FROM `_dialog_component_v`
					WHERE `component_id`=".$component_id."
					  AND `id` NOT IN (".$ids.")";
			query($sql);
		}

		//�������� �������������� �������� ��������
		$sort_v = 0;
		foreach($r['v'] as $v) {
			$sql = "INSERT INTO `_dialog_component_v` (
						`id`,
						`dialog_id`,
						`component_id`,
						`v`,
						`def`,
						`sort`
					) VALUES (
						"._num(@$v['id']).",
						".$dialog_id.",
						".$component_id.",
						'".addslashes(_txt($v['title']))."',
						"._bool($v['def']).",
						".($sort_v++)."
					)
					ON DUPLICATE KEY UPDATE
						`v`=VALUES(`v`),
						`def`=VALUES(`def`)";
			query($sql);
		}
	}
}
function _dialogComponentSpisok($dialog_id, $i, $data=array(), $page_id=0) {//������ �������� ������� � ������� ������� � html
/*
	������� �������� ������:
		arr
		arr_edit
		html
		html_edit

	���������� � �� �������������� - ������� ����������� ������� � �������, ��������: txt_1, num_2
		1. check:    num
		2. select:   num
		3. input:    txt
		4. textarea: txt
		5. radio:    num
		6. calendar: date
		7. info:     txt
		8. ������:   num
		9. head:     num
*/

	$arr = array();
	$html = '';
	$edit = $i == 'html_edit' || $i == 'arr_edit';//�������������� + ���������� ��������

	$dialog = _dialogQuery($dialog_id);

	if($cmp = $dialog['component']) {
		foreach($cmp as $r) {
			$type_id = _num($r['type_id']);
			$type_7 = $type_id == 7 || $type_id == 9;//info & head
			
			$val = '';

			//��������� �������� ��� �������������� ������ �������
			if(!empty($data)) {
				if($r['col_name'] == 'app_any_spisok') {
					$val = $data['app_id'] ? 0 : 1;
				} else
					$val = @$data[$r['col_name']];
			}

//			$val = _dialogComponent_autoSelectPage($val, $r, $page_id);
			$val = _dialogComponent_defSet($val, $r, $i != 'html' || isset($data['id']));

			$attr_id = 'elem'.$r['id'];
			$width = $r['width'] ? _num($r['width']) : 250;
			$inp = '<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />';

			switch($type_id) {
				case 1://check
				case 2://select
				default: break;
				case 3://input
					$inp = '<input type="text" id="'.$attr_id.'" placeholder="'.$r['txt_1'].'" style="width:'.$width.'px" value="'.$val.'" />';
					break;
				case 4://textarea
					$inp = '<textarea id="'.$attr_id.'" placeholder="'.$r['txt_1'].'" style="width:'.$width.'px">'.$val.'</textarea>';
					break;
				case 7://info
					$inp = '<div id="'.$attr_id.'" class="_info">'._br(htmlspecialchars_decode($r['txt_1'])).'</div>';
					break;
				case 8://connect
					if($i != 'html')
						break;
					if(!_num($val))
						break;

					//��������� �������� ������� ��� ������ �� �������
					$sql = "SELECT `base_table`
							FROM `_dialog`
							WHERE `id`=".$r['num_1'];
					$baseTable = query_value($sql);

					//��������� �������� ������� ��� ������
					$sql = "SELECT `col_name`
							FROM `_dialog_component`
							WHERE `id`=".$r['num_2'];
					$colName = query_value($sql);

					//��������� �������� �������
					$sql = "SELECT `".$colName."`
							FROM `".$baseTable."`
							WHERE `id`=".$val;
					$colVal = query_value($sql);

					$inp .= '<b>'.$colVal.'</b>';
					break;
				case 9://head
					$inp = '<div class="hd'.$r['num_1'].'">'.$r['txt_1'].'</div>';
					break;
			}

			$html .=
				($edit ?
					'<dd class="over1 curM prel" val="'.$r['id'].'">'.
						'<div class="cmp-set">'.
						(_dialogEl($type_id, 'func') ?
							'<div class="icon icon-usd mr3'._dn($r['func'], 'on')._tooltip('��������� �������', -61).'</div>'
						: '').
							'<div class="icon icon-edit mr3'._tooltip('��������� ���������', -66).'</div>'.
							'<div class="icon icon-del-red'._tooltip('������� ���������', -59).'</div>'.
						'</div>'
				: '').
						'<div id="delem'.$r['id'].'">'.
							'<table class="bs5 w100p">'.
								'<tr><td class="label '.($type_7 ? '' : 'r').($edit ? ' label-width pr5' : '').'" '.($type_7 ? 'colspan="2"' : 'style="width:125px"').'>'.
										($r['label_name'] ? $r['label_name'].':' : '').
										($r['req'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
										($r['hint'] ? ' <div class="icon icon-info pl dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'"></div>' : '').
					//���� ����������, �� ����� �� ��� ������
				   (!$type_7 ? '<td>' : '').
										$inp.
							'</table>'.
						'</div>'.
		   ($edit ? '</dd>' : '');

			$arr[] = array(
				'id' => _num($r['id']),
				'type_id' => $type_id,
				'label_name' => utf8($r['label_name']),
				'req' => _bool($r['req']),
				'hint' => utf8(htmlspecialchars_decode(htmlspecialchars_decode($r['hint']))),
				'width' => $width,
				'txt_1' => utf8($r['txt_1']),
				'txt_2' => utf8($r['txt_2']),
				'txt_3' => utf8($r['txt_3']),
				'num_1' => _num($r['num_1']),
				'num_2' => _num($r['num_2']),
				'num_3' => _num($r['num_3']),
				'num_4' => _num($r['num_4']),
				'num_5' => _num($r['num_5']),

				'func_flag' => _dialogEl($type_id, 'func'), //����� �� ��������� �������

				'col_name' => $r['col_name'],
				'val' => $val,//��������� ��������

				'attr_id' => '#'.$attr_id,

				'v' => array()
			);
		}

		$sql = "SELECT *
				FROM `_dialog_component_v`
				WHERE `component_id` IN ("._idsGet($cmp).")
				ORDER BY `sort`";
		$element_v = array();
		if($spisok = query_arr($sql)) {
			foreach($spisok as $r) {
				$element_v[$r['component_id']][] = array(
					'id' => _num($r['id']),
					'uid' => _num($r['id']),
					'title' => utf8($r['v']),
					'def' => _bool($r['def'])
				);
			}
		}
		
		foreach($arr as $n => $r) {
			if(isset($element_v[$r['id']]))
				$arr[$n]['v'] = $element_v[$r['id']];

			if($r['type_id'] == 2 && !$edit)
				switch($r['num_4']) {
					case 2://��� ������ ��� � ���������� ��������
						$arr[$n]['v'] = $r['num_5'] ? _dialogSpisokOnPage($page_id) : _dialogSpisokOn();
						break;
					case 3://��������� ������ �� ��������� ����������� �������
						$arr[$n]['v'] = _spisokList($r['num_1'], $r['num_2'], '', $r['val']);
						break;
					case 4://������ ��������, ������� ��������� �� �������� ����� GET
						$arr[$n]['v'] = _dialogSpisokGetPage($page_id);
						break;
				}
		}
	}

	if($i == 'arr')
		return $arr;

	if($i == 'arr_edit')
		return $arr;

	return $html;
}
function _dialogComponent_autoSelectPage($val, $r, $page_id) {//��������� �������� �� ���������, ���� ������ ����������� �� ��������, ������ ����� ������ �� ������ �������� todo ���� ��������
	//��������� �������e �� ��������
	if($val)
		return $val;

	//��� �������� - ��� ������
	if(!$page_id)
		return '';

	if($r['dialog_id'] == 3)//����
		return '';

	$spisokOther = $r['type_id'] == 2 && $r['num_1'];

	//����� ����������� �������� - ��������� ���
	if(!$spisokOther)
		return '';

	//�����������, ���� �� �� �������� ��������-������: dialog_id=2
	$sql = "SELECT CONCAT('\'button',id,'\'')
			FROM `_element`
			WHERE `dialog_id`=2
			  AND `page_id`=".$page_id;
	if(!$but = query_ids($sql))
		return '';

	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `val` IN (".$but.")
			LIMIT 1";
	return query_value($sql);
}
function _dialogComponent_defSet($val, $r, $isEdit) {//��������� �������� �� ��������� � select
	//��������� �������e �� ��������
	if($val)
		return $val;

	//���� �������������� �������, �� �� ���������������
	if($isEdit)
		return $val;


	if(!$val) {
		$sql = "SELECT *
				FROM `_dialog_component_v`
				WHERE `component_id`=".$r['id']."
				  AND `def`
				LIMIT 1";
		if($def = query_value($sql))
			$val = $def;
	}

	//��� �������� ������ - ��������� ���
	if($r['type_id'] == 2 && $r['num_4'])
		return $val;

	//����� ����������� �������� - ��������� ���
	if($r['type_id'] == 2 && $r['num_1'])
		return $val;

	//���� select, ���� radio
	if($r['type_id'] != 2 && $r['type_id'] != 5)
		return $val;

	return $val;
}








