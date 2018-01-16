<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'id' => 0,
			'sa' => 0,
			'width' => 500,
			'width_auto' => 0,

			'insert_head' => '�������� ����� ������',
			'insert_button_submit' => '������',
			'insert_button_cancel' => '������',

			'edit_head' => '���������� ������',
			'edit_button_submit' => '���������',
			'edit_button_cancel' => '������',

			'base_table' => '_spisok',

			'spisok_on' => 0,
			'spisok_name' => '',

			'insert_action_id' => 1,
			'insert_action_page_id' => 0,

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
	  		4 => '���������',
			9 => '<b class="red">SA</b>'
		);

		$action = array(
			3 => '�������� ���������� ������',
			1 => '�������� ��������',
			2 => '������� �� ��������'
		);

		if(!SA)
			unset($menu[9]);

		if(!isset($menu[$dialog['menu_edit_last']]))
			$dialog['menu_edit_last'] = 1;

		define('BLOCK_EDIT', 1);

		$html =
			'<div id="dialog-w-change"></div>'.//������ ������������ ����� ��� ��������� ������ �������

			'<div class="pad10 center bg-gr3 line-b">'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//��������� � ������
			'<div class="dialog-menu-1'._dn($dialog['menu_edit_last'] == 1).'">'.
				'<div class="pad10 bg-dfd">'.
					'<div class="hd2 mt5">�������� ����� ������</div>'.
					'<table class="bs5 w100p">'.
						'<tr><td class="grey w175 r">���������:'.
							'<td><input type="text" id="insert_head" class="w100p" maxlength="200" placeholder="�������� ����������� ���� - ����� ������" value="'.$dialog['insert_head'].'" />'.
						'<tr><td class="grey r">����� ������ <b>��������</b>:'.
							'<td><input type="text" id="insert_button_submit" class="w200" maxlength="100" value="'.$dialog['insert_button_submit'].'" />'.
						'<tr><td class="grey r">����� ������ <b>������</b>:'.
							'<td><input type="text" id="insert_button_cancel" class="w200" maxlength="100" value="'.$dialog['insert_button_cancel'].'" />'.
						'<tr><td class="blue r">���������� ��������:'.
							'<td><input type="hidden" id="insert_action_id" value="'.$dialog['insert_action_id'].'" />'.
						'<tr class="td-insert-action-page'._dn($dialog['insert_action_id'] == 2).'">'.
							'<td class="grey r">��������:'.
							'<td><input type="hidden" id="insert_action_page_id" value="'.$dialog['insert_action_page_id'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-ffd line-t1 pad10">'.
					'<div class="hd2 mt5">�������������� ������</div>'.
					'<table class="bs5 w100p">'.
						'<tr><td class="grey w175 r">���������:'.
							'<td><input type="text" id="edit_head" class="w100p" maxlength="200" placeholder="�������� ����������� ���� - ��������������" value="'.$dialog['edit_head'].'" />'.
						'<tr><td class="grey r">����� ������ <b>����������</b>:'.
							'<td><input type="text" id="edit_button_submit" class="w200" maxlength="100" value="'.$dialog['edit_button_submit'].'" />'.
						'<tr><td class="grey r">����� ������ <b>������</b>:'.
							'<td><input type="text" id="edit_button_cancel" class="w200" maxlength="100" value="'.$dialog['edit_button_cancel'].'" />'.
						'<tr><td class="blue r">���������� ��������:'.
							'<td><input type="hidden" id="edit_action_id" value="'.$dialog['edit_action_id'].'" />'.
						'<tr class="td-edit-action-page'._dn($dialog['edit_action_id'] == 2).'">'.
							'<td class="grey r">��������:'.
							'<td><input type="hidden" id="edit_action_page_id" value="'.$dialog['edit_action_page_id'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-fee line-t1 pad10">'.
					'<div class="hd2 mt5">�������� ������</div>'.
					'<table class="bs5 w100p">'.
						'<tr><td class="grey w175 r">���������:'.
							'<td><input type="text" id="del_head" class="w100p" maxlength="200" placeholder="�������� ����������� ���� - ��������" value="'.$dialog['del_head'].'" />'.
						'<tr><td class="grey r">����� ������ <b>��������</b>:'.
							'<td><input type="text" id="del_button_submit" class="w200" maxlength="100" value="'.$dialog['del_button_submit'].'" />'.
						'<tr><td class="grey r">����� ������ <b>������</b>:'.
							'<td><input type="text" id="del_button_cancel" class="w200" maxlength="100" value="'.$dialog['del_button_cancel'].'" />'.
						'<tr><td class="blue r">���������� ��������:'.
							'<td><input type="hidden" id="del_action_id" value="'.$dialog['del_action_id'].'" />'.
						'<tr class="td-del-action-page'._dn($dialog['del_action_id'] == 2).'">'.
							'<td class="grey r">��������:'.
							'<td><input type="hidden" id="del_action_page_id" value="'.$dialog['del_action_page_id'].'" />'.
					'</table>'.
				'</div>'.
			'</div>'.

			//����������
			'<div class="dialog-menu-2'._dn($dialog['menu_edit_last'] == 2).'">'.
				'<div class="pad10 line-b bg-ffc">'.
					_blockLevelChange('dialog', $dialog_id, $dialog['width']).
				'</div>'.
				'<div class="block-content-dialog" style="width:'.$dialog['width'].'px">'.
					_blockHtml('dialog', $dialog_id, $dialog['width']).
				'</div>'.
			'</div>'.

			//���������
			'<div class="dialog-menu-4 bg-gr2 pad20'._dn($dialog['menu_edit_last'] == 4).'">'.
				'<table class="bs10">'.
					'<tr><td class="grey w150 r">����� ���� �������:'.
						'<td><input type="hidden" id="spisok_on" value="'.$dialog['spisok_on'].'" />'.
					'<tr id="tr_spisok_name" class="'.($dialog['spisok_on'] ? '' : 'dn').'">'.
						'<td class="grey r">��� ������:'.
						'<td><input type="text" id="spisok_name" class="w200" maxlength="100" value="'.$dialog['spisok_name'].'" />'.
				'</table>'.
			'</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pad20'._dn($dialog['menu_edit_last'] == 9).'">'.
		        '<div class="hd2">���������� � ���������� ����:</div>'.
				'<table class="bs10">'.
					'<tr><td class="red w175 r">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="red r">������:'.
		                '<td><div id="dialog-width" class="dib w50">'.$dialog['width'].'</div>'.
		                    '<input type="hidden" id="width_auto" value="'.$dialog['width_auto'].'" />'.
					'<tr><td class="red r">������� � ����:'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					//����������� �������. �� ��������� app_id. �� ��������� 0 - ���������� ����.
					'<tr><td class="red r">������ ���� �����������:'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'value' => $dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0
							   )).
					'<tr><td class="red r">������ ������ SA:'.
						'<td>'._check(array(
									'attr_id' => 'sa',
									'value' => $dialog['sa']
							   )).
				'</table>'.

		        '<div class="hd2 mt20">��������� ��� ��������:</div>'.
				'<table class="bs10">'.
					'<tr><td class="red w175 r">��� ��������:'.
		                '<td><input type="text" id="element_name" class="w230" maxlength="100" value="'.$dialog['element_name'].'" />'.
					'<tr><td class="red r">��������� ������:'.
						'<td><input type="hidden" id="element_width" value="'.$dialog['element_width'].'" />'.
					'<tr><td class="red r">����������� ������:'.
						'<td><input type="hidden" id="element_width_min" value="'.$dialog['element_width_min'].'" />'.
					'<tr><td class="red r">����������� �����:'.
						'<td>'._check(array(
									'attr_id' => 'element_style_access',
									'value' => $dialog['element_style_access']
							   )).
				'</table>'.
			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['menu'] = _selArray($menu);
		$send['block_arr'] = _blockJsArr('dialog', $dialog_id);
		$send['action'] = _selArray($action);
		$send['cmp'] = $dialog['cmp_utf8'];
		$send['html'] = utf8($html);
		$send['sa'] = SA;
		jsonSuccess($send);
		break;
	case 'dialog_save'://���������� ����������� ����
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
	if(!_dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	if(!$insert_head = _txt($_POST['insert_head']))
		jsonError('�� ������ ��������� ��� �������� ������');
	if(!$insert_button_submit = _txt($_POST['insert_button_submit']))
		jsonError('�� ������ ����� ������ ��������');
	if(!$insert_button_cancel = _txt($_POST['insert_button_cancel']))
		jsonError('�� ������ ����� ������ ������ ��� ����� ������');
	$insert_action_id = _num($_POST['insert_action_id']);
	$insert_action_page_id = _num($_POST['insert_action_page_id']);

	if(!$edit_head = _txt($_POST['edit_head']))
		jsonError('�� ������ ��������� ��������������');
	if(!$edit_button_submit = _txt($_POST['edit_button_submit']))
		jsonError('�� ������ ����� ������ ����������');
	if(!$edit_button_cancel = _txt($_POST['edit_button_cancel']))
		jsonError('�� ������ ����� ������ ������ ��������������');
	$edit_action_id = _num($_POST['edit_action_id']);
	$edit_action_page_id = _num($_POST['edit_action_page_id']);

	if(!$del_head = _txt($_POST['del_head']))
		jsonError('�� ������ ��������� ��������');
	if(!$del_button_submit = _txt($_POST['del_button_submit']))
		jsonError('�� ������ ����� ������ ��������');
	if(!$del_button_cancel = _txt($_POST['del_button_cancel']))
		jsonError('�� ������ ����� ������ ������ ��������');
	$del_action_id = _num($_POST['del_action_id']);
	$del_action_page_id = _num($_POST['del_action_page_id']);

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

	$width_auto = _num($_POST['width_auto']);
	$app_any = _num($_POST['app_any']);

	$element_name = _txt($_POST['element_name']);
	$element_width = _num($_POST['element_width']);
	$element_width_min = _num($_POST['element_width_min']);
	$element_style_access = _num($_POST['element_style_access']);

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`width`=".$width.",
				`width_auto`=".$width_auto.",

				`insert_head`='".addslashes($insert_head)."',
				`insert_button_submit`='".addslashes($insert_button_submit)."',
				`insert_button_cancel`='".addslashes($insert_button_cancel)."',
				`insert_action_id`=".$insert_action_id.",
				`insert_action_page_id`=".$insert_action_page_id.",

				`edit_head`='".addslashes($edit_head)."',
				`edit_button_submit`='".addslashes($edit_button_submit)."',
				`edit_button_cancel`='".addslashes($edit_button_cancel)."',
				`edit_action_id`=".$edit_action_id.",
				`edit_action_page_id`=".$edit_action_page_id.",

				`del_head`='".addslashes($del_head)."',
				`del_button_submit`='".addslashes($del_button_submit)."',
				`del_button_cancel`='".addslashes($del_button_cancel)."',
				`del_action_id`=".$del_action_id.",
				`del_action_page_id`=".$del_action_page_id.",

				`base_table`='".addslashes($base_table)."',
				`spisok_on`=".$spisok_on.",
				`spisok_name`='".addslashes($spisok_name)."',


				`element_name`='".addslashes($element_name)."',
				`element_width`=".$element_width.",
				`element_width_min`=".$element_width_min.",
				`element_style_access`=".$element_style_access.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_cache('clear', '_dialogQuery'.$dialog_id);

	return $dialog_id;
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	//��������� ������ ������� ������
	$unit = array();
	if($unit_id = _num(@$_POST['unit_id'])) {
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

	$page_id = _num($_POST['page_id']);

	$act = $unit_id ? 'edit' : 'insert';
	if(_num(@$_POST['del']))
		$act = 'del';

	$send['dialog_id'] = $dialog_id;
	$send['unit_id'] = $unit_id;
	$send['block_id'] = _num(@$_POST['block_id']);
	$send['act'] = $act;

	//�������� ������, ���������� ��� �������� �������
	$unit['source'] = array(
		'block_id' => $send['block_id']//��� ������ ����� ��� ������
	);

	$send['edit_access'] = SA || $dialog['app_id'] == APP_ID ? 1 : 0;//����� ��� �������������� �������
	$send['width'] = $dialog['width_auto'] ? 0 : _num($dialog['width']);
	$send['head'] = utf8($dialog[$act.'_head']);
	$send['button_submit'] = utf8($dialog[$act.'_button_submit']);
	$send['button_cancel'] = utf8($dialog[$act.'_button_cancel']);
	$send['html'] = utf8(_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $unit));

	//���������� ���������� ��������� �����������
	foreach($dialog['cmp_utf8'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//select - ����� ������ (��� ������ ����������)
			case 24:
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _dialogSpisokOn($dialog_id, $send['block_id'], $unit_id);
				break;
			//select - ����� ������, ������������ �� ������� ��������
			case 27:
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _dialogSpisokOnPage($page_id);
				break;
			//select - ����� ������� �� ������� ������ (��� ������)
			case 29:
				$sel_id = 0;//��������� ��������
				if($unit_id)
					$sel_id = $unit[$cmp['col']];
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = _spisokConnect($cmp_id, $v='', $sel_id);
				break;
			//��������� ���������� ���������� ������
			case 30:
				if(!$unit_id)
					break;
				if(!$col = $cmp['col'])
					break;
				if(!$ids = $unit[$col])
					break;
				$sql = "SELECT *
						FROM `_element`
						WHERE `id` IN (".$ids.")
						ORDER BY `sort`";
				if(!$arr = query_arr($sql))
					break;

				$sql = "SELECT `id`,`dialog_id`
						FROM `_element`
						WHERE `id` IN ("._idsGet($arr, 'num_1').")";
				$elem = query_ass($sql);

				$spisok = array();
				foreach($arr as $r) {
					$elDialog = _dialogQuery($r['dialog_id'] == 31 ? $elem[$r['num_1']] : $r['dialog_id']);
					$spisok[] = array(
						'id' => _num($r['id']),
						'dialog_id' => _num($r['dialog_id']),
						'width' => _num($r['width']),
						'tr' => utf8($r['txt_1']),
						'title' => utf8($elDialog['element_name']),
						'font' => $r['font'],
						'color' => $r['color'],
						'pos' => $r['txt_6'],
						'link' => _num($r['num_2']),
					);
				}
				$dialog['cmp_utf8'][$cmp_id]['elv_spisok'] = $spisok;
				break;
	}

	$send['cmp'] = $dialog['cmp_utf8'];

	foreach($unit as $id => $r) {
		$r = !is_array($r) && preg_match(REGEXP_NUMERIC, $r) ? intval($r) : utf8($r);
		$unit[$id] = $r;
	}

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
	if($act == 'del') {
		if(!$unit_id)
			jsonError('����������� ������� ������ ��� ��������');

		$html =
			'<div class="pad20">'.
				'<div class="_info b">����������� �������� ������.</div>'.
			'</div>';

		$send['width'] = 480;
		$send['html'] = utf8($html);
	}

	return $send;
}




