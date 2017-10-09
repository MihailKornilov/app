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

			'menu_edit_last' => 1
		);

		if($dialog_id = _num($_POST['dialog_id']))
			if($ass = _dialogQuery($dialog_id))
				$dialog = $ass;
			else
				$dialog_id = 0;

		$menu = array(
			1 => '���������, ������',
			2 => '����������',
//  		3 => '�������',
//			4 => '����������� �����',
			9 => '<b class="red">SA</b>'
		);

		if(!SA) {
			unset($menu[9]);

			if($dialog['menu_edit_last'] == 9)
				$dialog['menu_edit_last'] = 2;
		}

		$html =
			'<div id="dialog-w-change"></div>'.//������ ������������ ����� ��� ��������� ������ �������

			'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.

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
						'<div id="label-w-change"></div>'.
						_dialogComponentSpisok($dialog_id, 'html_edit').
					'</dl>'.
				'</div>'.
				'<div class="pad20 center bg-ffd line-t1">'.
					'<button class="vk green" onclick="_dialogComponentEdit()">����� ���������</button>'.
				'</div>'.
			'</div>'.

			//������
//			'<div class="dialog-menu-3">������ � ��������</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pt20 pb20">'.
				'<table class="bs10">'.
					'<tr><td class="label w175 r"><div class="red">ID �������:</div>'.
						'<td>'.$dialog['id'].
					'<tr><td class="label r"><div class="red">������� � ����:</div>'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					//����������� �������. �� ��������� app_id. �� ��������� 0 - ���������� ����.
					'<tr><td class="label r"><div class="red">App any:</div>'.
						'<td><input type="hidden" id="app_any" value="'.($dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0).'" />'.
					'<tr><td class="label r"><div class="red">SA only:</div>'.
						'<td><input type="hidden" id="sa" value="'.$dialog['sa'].'" />'.
				'</table>'.
			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = $dialog_id ? _num($dialog['width']) : 500;
		$send['label_width'] = $dialog_id ? _num($dialog['label_width']) : 125;
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['menu'] = _selArray($menu);
		$send['element'] = _dialogEl();
		$send['cmp_name'] = _dialogEl(0, 'name');
		$send['component'] = _dialogComponentSpisok($dialog_id, 'arr_edit');
		$send['func'] = (object)_dialogFuncSpisok($dialog_id);
		$send['spisokOn'] = _dialogSpisokOn();
		$send['html'] = utf8($html);
		$send['sa'] = SA;
		jsonSuccess($send);
		break;
	case 'dialog_add'://�������� ������ ����������� ����
		$send['dialog_id'] = _dialogUpdate(0);
		jsonSuccess($send);
		break;
	case 'dialog_edit'://���������� ����������� ����
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');

		$send['dialog_id'] = _dialogUpdate($dialog_id);
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

	case 'dialog_open_load'://��������� ������ ��� ����������� ����
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');

		$page_id = _num($_POST['page_id']);

		$data = array();
		if($unit_id = _num($_POST['unit_id'])) {
			$sql = "SELECT *
					FROM `".$dialog['base_table']."`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$unit_id;
			if(!$data = query_assoc($sql))
				jsonError('������ �� ����������');
			if(@$data['sa'] && !SA)
				jsonError('��� �������');
			if($data['deleted'])
				jsonError('������ ���� �������');
		}

		//8:������
		if($unit_id_dub = _num(@$_POST['unit_id_dub'])) {
			foreach($dialog['component'] as $r)
				if($r['type_id'] == 8)
					$data[$r['col_name']] = $unit_id_dub;
		}

		$html = '<div class="mt5 mb5">'._dialogComponentSpisok($dialog_id, 'html', $data, $page_id).'</div>';

		$send['iconEdit'] = SA || $dialog['app_id'] == APP_ID ? 'show' : 'hide';//����� ��� �������������� �������
		$send['width'] = _num($dialog['width']);
		$send['head_insert'] = utf8($dialog['head_insert']);
		$send['button_insert_submit'] = utf8($dialog['button_insert_submit']);
		$send['button_insert_cancel'] = utf8($dialog['button_insert_cancel']);
		$send['head_edit'] = utf8($dialog['head_edit']);
		$send['button_edit_submit'] = utf8($dialog['button_edit_submit']);
		$send['button_edit_cancel'] = utf8($dialog['button_edit_cancel']);
		$send['component'] = _dialogComponentSpisok($dialog_id, 'arr');
		$send['func'] = _dialogFuncSpisok($dialog_id);
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;

	case 'page_element_class_set'://���������� ����� ������ � �������� ��������
		if(!$element_id = _num($_POST['element_id']))
			jsonError('������������ ID ��������');

		$cls = _txt($_POST['cls']);

		$sql = "UPDATE `_page_element`
				SET `cls`='".addslashes($cls)."'
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$element_id;
		query($sql);

		jsonSuccess();
		break;

	case 'spisok_add'://�������� ������ ������� � _spisok
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');

		$page_id = _num($_POST['page_id']);
		
		_dialogSpisokUpdate($dialog_id, 0, $page_id);

		jsonSuccess();
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
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');

		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('������������ �������������');

		_dialogSpisokUpdate($dialog_id, $unit_id);

		jsonSuccess();
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
	case 'spisok_get'://���������� ������ �� ��������
		if(!$element_id = _num($_POST['element_id']))
			jsonError('������������ ID �������� �������');

		$v = _txt($_POST['v']);
		
		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN(0,".APP_ID.")
				  AND `id`=".$element_id;
		if(!$pe = query_assoc($sql))
			jsonError('�������� id'.$element_id.' �� ����������');

		//���������� ������ ������
		$sql = "UPDATE `_page_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$element_id;
		query($sql);

		//id ������� ������, �� ������� ���������� ����������� ����� �����
		if(!$spisok_id = _num($pe['num_3']))
			jsonError('��� ����������� �� ������');

		//������������ ������ �� ��������, �� ������� ���������� �����
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN(0,".APP_ID.")
				  AND `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `num_3`=".$spisok_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('��� ������� ������ �� ��������');

		$send['attr_id'] = '#pe_'.$peSpisok['dialog_id'].'_'.$peSpisok['id'];
		$send['spisok'] = utf8(_pageSpisok($peSpisok));

		jsonSuccess($send);
		break;
	case 'spisok_col_get'://��������� ������� ������ (��� ������� �������� 5)
		if(!$component_id = _num($_POST['component_id']))
			jsonError('������������ ID ���������� �������');
		if(!$page_id = _num($_POST['page_id']))
			jsonError('������������ ID ��������');

		$sql = "SELECT *
				FROM `_dialog_component`
				WHERE `id`=".$component_id;
		if(!$cmp = query_assoc($sql))
			jsonError('���������� �� ����������');

		//��������: ��������� ������ ���� ���������� ������� � ������������ ��������� ������
		if($cmp['type_id'] != 2 || !$cmp['param_bool_2'])
			jsonError('��������� �� �������� �������� �������');
		if(!$cmpDialog = _dialogQuery($cmp['dialog_id']))
			jsonError('�������, � ������� ���������� ���������, �� ����������');

		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID �������');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');
		if(!$dialog['spisok_on'])
			jsonError('������ �� �������� �������');
		if(empty($dialog['component']))
			jsonError('� ������ ������ ������� ���');

		//��������� �������� ������� �������� 5
		$sql = "SELECT `txt_3`
				FROM `_page_element`
				WHERE `page_id`=".$page_id."
				  AND `dialog_id`=7";
		$ids = _idsAss(query_value($sql));

		$col = '';
		foreach($dialog['component'] as $id => $r) {
			if($r['type_id'] == 1)
				continue;
			if($r['type_id'] == 5)
				continue;
			if($r['type_id'] == 6)
				continue;
			if($r['type_id'] == 7)
				continue;
			if($r['type_id'] == 8)
				continue;
			if($r['type_id'] == 9)
				continue;
			$col .=
				'<div class="mb5">'.
					_check(array(
						'id' => 'col'.$id,
						'title' => $r['label_name'].' <span class="grey i">'._dialogEl($r['type_id'], 'name').'</span>',
						'value' => @$ids[$id]
					)).
				'</div>';
		}

		$send['html'] = utf8(
			'<table class="bs5 w100p">'.
				'<tr><td class="label r top" style="width:'.$cmpDialog['label_width'].'px">�������:'.
					'<td>'.$col.
			'</table>'
		);

		jsonSuccess($send);
		break;
}

function _dialogUpdate($dialog_id) {//���������� �������
	if(!$head_insert = _txt($_POST['head_insert']))
		jsonError('�� ������� �������� ������� ��� ����� ������');
	if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
		jsonError('�� ������ ����� ������ ��������');
	if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
		jsonError('�� ������ ����� ������ ������ ��� ����� ������');

	if(!$head_edit = _txt($_POST['head_edit']))
		jsonError('�� ������� �������� ������� ��������������');
	if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
		jsonError('�� ������ ����� ������ ����������');
	if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
		jsonError('�� ������ ����� ������ ������ ��������������');

	if(!$width = _num($_POST['width']))
		jsonError('������������ �������� ������ �������');
	if($width < 480 || $width > 900)
		jsonError('����������� ������������ ������ �������');
	if(!$label_width = _num($_POST['label_width']))
		jsonError('������������ �������� ������ label');

	$base_table = _txt($_POST['base_table']);
	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_name = _txt($_POST['spisok_name']);
	if($spisok_on && !$spisok_name)
		jsonError('������� ��� ������ ��������');

	$app_any = _num($_POST['app_any']);

	_dialogComponentUpdate();

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
				`label_width`=".$label_width.",

				`head_insert`='".addslashes($head_insert)."',
				`button_insert_submit`='".addslashes($button_insert_submit)."',
				`button_insert_cancel`='".addslashes($button_insert_cancel)."',

				`head_edit`='".addslashes($head_edit)."',
				`button_edit_submit`='".addslashes($button_edit_submit)."',
				`button_edit_cancel`='".addslashes($button_edit_cancel)."',

				`base_table`='".addslashes($base_table)."',
				`spisok_on`=".$spisok_on.",
				`spisok_name`='".addslashes($spisok_name)."',
				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_dialogComponentUpdate($dialog_id);
	_dialogFuncUpdate($dialog_id);

	return $dialog_id;
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
function _dialogFuncSpisok($dialog_id) {//��������� ������ �������� ����������� �������
	if(!$dialog_id)
		return array();

	$sql = "SELECT *
			FROM `_dialog_component_func`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `component_id`,`id`";
	if(!$arr = query_arr($sql))
		return array();

	$func = array();
	foreach($arr as $r) {
		if(!isset($func[$r['component_id']]))
			$func[$r['component_id']] = array();

		$func[$r['component_id']][] = array(
			'action_id' => _num($r['action_id']),
			'cond_id' => _num($r['cond_id']),
			'ids' => $r['component_ids']
		);
	}

	return $func;
}

function _dialogEl($type_id=0, $i='') {//������ ���� ���������, ������������ � �������
	if(!defined('EL_LABEL_W'))
		define('EL_LABEL_W', 'w175');//������ ��� ���� label
	$sort = array(9,3,4,2,1,5,6,7,8);

	$name = array(
		1 => '�������',
		2 => '���������� ������',
		3 => '������������ �����',
		4 => '������������� �����',
		5 => 'Radio',
		6 => '���������',
		7 => '����������',
		8 => '������',
		9 => '���������'
	);
	$css = array(
		1 => '',
		2 => 'element-select',
		3 => 'element-input',
		4 => 'element-textaera',
		5 => 'element-radio',
		6 => 'element-calendar',
		7 => 'element-info',
		8 => 'element-connect',
		9 => 'element-head'
	);
	//����� �� ��������� ��������� ������� (� ����� ����� �� ����������� � �������� ������)
	$func = array(
		1 => 1,
		2 => 1,
		3 => 1,
		4 => 1,
		5 => 1,
		6 => 1,
		7 => 0,
		8 => 0,
		9 => 0
	);
	$html = array(
		1 => /* *** ������� ***
				param_txt_1 - ����� ��� �������
            */
			_dialogElHtmlContent().
			'<table class="bs5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">����� ��� �������:'.
					'<td><input type="text" class="w250" id="param_txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		2 => /* *** ���������� ������ ***
				param_bool_1 - ������������ ��� ��� ������� ��������
                param_txt_1  - ����� �������� ��������
                param_bool_2 - ������������� ���� ������� ��� ������
				param_num_1  - id ������ �� dialog_id
		        param_num_2  - id ������� �� component_id
			*/
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20">���������� ����������� ������:</div>'.
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">������� ��������:'.
					'<td><input type="hidden" id="param_bool_1" value="1" />'.
						'<input type="text" class="w230 ml5" id="param_txt_1" value="�� �������" />'.
			'</table>'.
			'<div id="elem-select-but" class="center pad10">'.
				'<button class="vk small green">������������ ��������</button> '.
				'<button class="vk small">��� ������</button> '.
				'<button class="vk small">������� �� �������</button>'.
			'</div>'.
			_dialogElHtmlPrev(),

		3 => /* *** ������������ ����� ***
				param_txt_1 - ����� ��� placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">��������� � ����:'.
					'<td><input type="text" class="w300" id="param_txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<input type="text" id="elem-attr-id" class="w250" />'),

		4 => /* *** ������������� ����� ***
				param_txt_1 - ����� ��� placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">��������� � ����:'.
					'<td><input type="text" class="w300" id="param_txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<textarea id="elem-attr-id" class="w250"></textarea>'),

		5 => /* *** ����� ***
				param_txt_1 - ����� ��� placeholder
             */
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20" id="radio-cont">����������:</div>'.
			_dialogElHtmlPrev(),

		6 => /* *** ��������� ***
				param_bool_1 - ����������� �������� ��������� ���
                param_bool_2 - ���������� ������ "������"
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">����� ��������� ����:'.
					'<td><input type="hidden" id="param_bool_1" />'.
				'<tr><td class="label r">������ <u>������</u>:'.
					'<td><input type="hidden" id="param_bool_2" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		7 => /* *** ���������� ***
				param_txt_1 - ����� ����������
             */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r topi '.EL_LABEL_W.'">�����:'.
					'<td><textarea id="param_txt_1" class="w300"></textarea>'.
			'</table>'.
			'<div id="prev-tab" class="mt20 pad20 pt10">'.
				'<div class="hd2">��������������� ��������:</div>'.
				'<div id="elem-attr-id" class="_info mt10"></div>'.
			'</div>',

		8 => /* *** ������ ***
 				param_num_1  - id ������ �� dialog_id
		        param_num_2  - id ������� �� component_id
            */
			_dialogElHtmlContent().
			'<div id="connect-head"></div>'.
			_dialogElHtmlPrev('<div class="grey i">��������� ���������</div>'),

		9 => /* *** ��������� ***
 				param_num_1  - ��� ���������
 				param_txt_1  - ����� ���������
            */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">���:'.
					'<td><input type="hidden" id="param_num_1" value="2" />'.
				'<tr><td class="label r">�����:'.
					'<td><input type="text" class="w300" id="param_txt_1" />'.
			'</table>'.
			'<div class="b ml20 mt20 mb5">����������:</div>'.
			'<div id="prev-tab" class="pad20 pt10 bor-f0">'.
				'<div id="elem-attr-id" class="mt10 hd2"></div>'.
			'</div>',
	);

	//��������� ����������� ��������� ������� ��� ����������
	if($i == 'func')
		return $func[$type_id];

	//���������� � �������� ��� ����������� ����� AJAX
	if($i == 'name') {
		if($type_id)
			return $name[$type_id];
		foreach($name as $id => $r)
			 $name[$id] = utf8($r);
		return $name;
	}


	$send = array();
	foreach($sort as $id) {
		$send[$id] = array(
			'name' => utf8($name[$id]),
			'css' => $css[$id],
			'html' => utf8(
						_dialogElHtmlHead($name[$id]).
						_dialogElHtmlSA().
						$html[$id]
					)
		);
	}
	return $send;
}
function _dialogElHtmlHead($name) {//��������� ��������
	return '<div class="fs16 bg-gr1 pad20 line-b mb10">��������� <b class="fs16">'.$name.'</b></div>';
}
function _dialogElHtmlSA() {//���� SA
	if(!SA)
		return '';
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' red r">SA: col_name:'.
			'<td><input type="text" id="col_name" class="w100" />'.
	'</table>';
}
function _dialogElHtmlContent($req=0) {//�������� ����������
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r b">�������� ����:'.
			'<td><input type="text" id="label_name" class="w250" />'.
		'</table>'.

($req ? //����������� ������� "��������� ������������ ����������"
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.'">'.
			'<td><input type="hidden" id="label-require" />'.
	'</table>'
: '').

	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r topi">����� �����������<br />���������:'.
			'<td><textarea id="label-hint" class="w300"></textarea>'.
	'</table>';
}
function _dialogElHtmlPrev($inp='<input type="hidden" id="elem-attr-id" />') {//��������������� ��������
	return
	'<div id="prev-tab" class="mt20 pad20 pt10 bor-f0 bg-ffe">'.
		'<div class="hd2">��������������� ��������:</div>'.
		'<table class="bs5 w100p mt10">'.
			'<tr><td id="label-prev" class="label r '.EL_LABEL_W.'">'.
				'<td>'.$inp.
		'</table>'.
	'</div>';
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
				if(!_txt($r['label_name']) && !_txt($r['param_txt_1']))
					jsonError('������� �������� ����,<br />���� ����� ��� �������');
				break;
			case 2://select
				if($dialog_id)
					if(_num($r['param_bool_2']) || _num($r['param_num_1'])) {
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
				1 => 'bool',
				2 => 'num',
				3 => 'txt',
				4 => 'txt',
				5 => 'num',
				6 => 'date',
				7 => 'txt',
				8 => 'num',
				9 => 'txt'
			);
			$n = 1;
			$sql = "SELECT `col_name`,1
					FROM `_dialog_component`
					WHERE `dialog_id`=".$dialog_id."
					  AND `col_name` LIKE '".$pole[$type_id]."_%'";
			$ass = query_ass($sql);
			for($n = 1; $n <= 5; $n++) {
				$col_name = $pole[$type_id].'_'.$n;
				if(!isset($ass[$col_name]))
					break;
			}
		}

		$label_name = _txt($r['label_name']);
		$require = _bool($r['require']);
		$hint = _txt($r['hint']);
		$width = _num($r['width']);
		$param_txt_1 = _txt($r['param_txt_1']);
		$param_txt_2 = _txt($r['param_txt_2']);

		$sql = "INSERT INTO `_dialog_component` (
					`id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`require`,
					`hint`,
					`width`,
					`param_num_1`,
					`param_num_2`,
					`param_txt_1`,
					`param_txt_2`,
					`param_bool_1`,
					`param_bool_2`,
					`col_name`,
					`sort`
				) VALUES (
					".$component_id.",
					".$dialog_id.",
					".$type_id.",
					'".addslashes($label_name)."',
					".$require.",
					'".addslashes($hint)."',
					".$width.",
					"._num($r['param_num_1']).",
					"._num($r['param_num_2']).",
					'".addslashes($param_txt_1)."',
					'".addslashes($param_txt_2)."',
					"._bool($r['param_bool_1']).",
					"._bool($r['param_bool_2']).",
					'".$col_name."',
					".($sort++)."
				)
				ON DUPLICATE KEY UPDATE
					`label_name`=VALUES(`label_name`),
					`require`=VALUES(`require`),
					`hint`=VALUES(`hint`),
					`width`=VALUES(`width`),
					`param_num_1`=VALUES(`param_num_1`),
					`param_num_2`=VALUES(`param_num_2`),
					`param_txt_1`=VALUES(`param_txt_1`),
					`param_txt_2`=VALUES(`param_txt_2`),
					`param_bool_1`=VALUES(`param_bool_1`),
					`param_bool_2`=VALUES(`param_bool_2`),
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
		html
		html_edit

	���������� � �� �������������� - ������� ����������� ������� � �������, ��������: txt_1, num_2
		1. check:    bool
		2. select:   num
		3. input:    txt
		4. textarea: txt
		5. radio:    num
		6. calendar: date
		7. info:     txt
		8. info:     num
*/

	$arr = array();
	$html = '';
	$edit = $i == 'html_edit' || $i == 'arr_edit';//�������������� + ���������� ��������

	$sql = "SELECT `label_width`
			FROM `_dialog`
			WHERE `id`=".$dialog_id;
	$label_width = _num(query_value($sql));

	$func = _dialogFuncSpisok($dialog_id);

	$sql = "SELECT *
			FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	if($spisok = query_arr($sql)) {
		foreach($spisok as $r) {
			$type_id = _num($r['type_id']);
			$type_7 = $type_id == 7 || $type_id == 9;
			
//			if($type_id == 7)
//				continue;

			$val = '';

			//��������� �������� ��� �������������� ������ �������
			if(!empty($data)) {
				if($r['col_name'] == 'app_any_spisok') {
					$val = $data['app_id'] ? 0 : 1;
				} else
					$val = @$data[$r['col_name']];
			}

			$val = _dialogComponent_autoSelectPage($val, $r, $page_id);
			$val = _dialogComponent_defSet($val, $r, $i != 'html' || isset($data['id']));

			$attr_id = 'elem'.$r['id'];
			$width = $r['width'] ? _num($r['width']) : 250;
			$inp = '<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />';

			switch($type_id) {
				case 1://check
				case 2://select
				default: break;
				case 3://input
					$inp = '<input type="text" id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px" value="'.$val.'" />';
					break;
				case 4://textarea
					$inp = '<textarea id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" style="width:'.$width.'px">'.$val.'</textarea>';
					break;
				case 7://info
					$inp = '<div id="'.$attr_id.'" class="_info">'._br(htmlspecialchars_decode($r['param_txt_1'])).'</div>';
					break;
				case 8://connect
					if($i != 'html')
						break;
					if(!_num($val))
						break;

					//��������� �������� ������� ��� ������ �� �������
					$sql = "SELECT `base_table`
							FROM `_dialog`
							WHERE `id`=".$r['param_num_1'];
					$baseTable = query_value($sql);

					//��������� �������� ������� ��� ������
					$sql = "SELECT `col_name`
							FROM `_dialog_component`
							WHERE `id`=".$r['param_num_2'];
					$colName = query_value($sql);

					//��������� �������� �������
					$sql = "SELECT `".$colName."`
							FROM `".$baseTable."`
							WHERE `id`=".$val;
					$colVal = query_value($sql);

					$inp .= '<b>'.$colVal.'</b>';
					break;
				case 9://head
					$inp = '<div class="hd'.$r['param_num_1'].'">'.$r['param_txt_1'].'</div>';
					break;
			}

			$html .=
				($edit ?
					'<dd class="over1 curM prel" val="'.$r['id'].'">'.
						'<div class="component-del icon icon-del'._tooltip('������� ���������', -59).'</div>'.
						'<div class="component-edit icon icon-edit'._tooltip('��������� ���������', -66).'</div>'.
					(_dialogEl($type_id, 'func') ?
						'<div class="component-func'.(empty($func[$r['id']]) ? '' : ' on').' icon icon-zp'._tooltip('��������� �������', -61).'</div>'
					: '')
				: '').
						'<div id="delem'.$r['id'].'">'.
							'<table class="bs5 w100p">'.
								'<tr><td class="label '.($type_7 ? '' : 'r').($edit ? ' label-width pr5' : '').'" '.($type_7 ? 'colspan="2"' : 'style="width:'.$label_width.'px"').'>'.
										($r['label_name'] ? $r['label_name'].':' : '').
										($r['require'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
										($r['hint'] ? ' <div class="icon icon-hint dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'"></div>' : '').
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
				'require' => _bool($r['require']),
				'hint' => utf8(htmlspecialchars_decode(htmlspecialchars_decode($r['hint']))),
				'width' => $width,
				'param_num_1' => _num($r['param_num_1']),
				'param_num_2' => _num($r['param_num_2']),
				'param_txt_1' => utf8($r['param_txt_1']),
				'param_txt_2' => utf8($r['param_txt_2']),
				'param_bool_1' => _bool($r['param_bool_1']),
				'param_bool_2' => _bool($r['param_bool_2']),

				'func_flag' => _dialogEl($type_id, 'func'), //����� �� ��������� �������

				'col_name' => $r['col_name'],

				'attr_id' => '#'.$attr_id,

				'v' => array()
			);
		}

		$sql = "SELECT *
				FROM `_dialog_component_v`
				WHERE `component_id` IN ("._idsGet($spisok).")
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
			//��������� �������� ����������� �������
			if($r['type_id'] == 2 && $r['param_num_1'] && !$edit)
				$arr[$n]['v'] = _dialogSpisokList($r['param_num_1'], $r['param_num_2']);
			//������ ��������, ������� ����� ���� ��������
			if($r['type_id'] == 2 && $r['param_bool_2'] && !$edit)
				$arr[$n]['v'] = _dialogSpisokOn();
		}
	}

	if($i == 'arr')
		return $arr;

	if($i == 'arr_edit')
		return $arr;

	return $html;
}
function _dialogComponent_autoSelectPage($val, $r, $page_id) {//��������� �������� �� ���������, ���� ������ ����������� �� ��������, ������ ����� ������ �� ������ ��������
	//��������� �������e �� ��������
	if($val)
		return $val;

	//��� �������� - ��� ������
	if(!$page_id)
		return '';

	$spisokOther = $r['type_id'] == 2 && $r['param_num_1'];

	//����� ����������� �������� - ��������� ���
	if(!$spisokOther)
		return '';

	//�����������, ���� �� �� �������� ��������-������: dialog_id=2
	$sql = "SELECT CONCAT('\'button',id,'\'')
			FROM `_page_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=2
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


	//��� �������� ������ - ��������� ���
	if($r['type_id'] == 2 && $r['param_bool_2'])
		return $val;

	//����� ����������� �������� - ��������� ���
	if($r['type_id'] == 2 && $r['param_num_1'])
		return $val;

	//���� select, ���� radio
	if($r['type_id'] != 2 && $r['type_id'] != 5)
		return $val;

	$sql = "SELECT *
			FROM `_dialog_component_v`
			WHERE `component_id`=".$r['id']."
			  AND `def`
			LIMIT 1";

	return query_value($sql);
}

function _dialogSpisokList($dialog_id, $component_id) {//������ ������� (���� ������ ��� select)
	$dialog = _dialogQuery($dialog_id);

	$sql = "SELECT `col_name`
			FROM `_dialog_component`
			WHERE `id`=".$component_id;
	if(!$colName = query_value($sql))
		$colName = 'id';

	$sql = "SELECT `id`,`".$colName."`
			FROM `".$dialog['base_table']."`
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogSpisokUpdate($dialog_id, $unit_id=0, $page_id=0) {//��������/�������������� ������ ������
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	//�������� ������� ������� ��� �������� ������
	define('BASE_TABLE', $dialog['base_table']);
	$sql = "SHOW TABLES LIKE '".BASE_TABLE."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('������� �� ����������');

	if($unit_id) {
		$sql = "SELECT *
				FROM `".BASE_TABLE."`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$unit_id;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		if($r['deleted'])
			jsonError('������ ���� �������');
	}

	//�������� �������� �� ��������
	if($dialog_id == 6)
		return _pageElementDel($unit_id);

	//�������� �� ������������ ������ ����������� �������
	if(!$elem = @$_POST['elem'])
		jsonError('��� ������ ��� ��������');
	if(!is_array($elem))
		jsonError('������������ ������ ������');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('������������ ������������� ����');

	//��������� ���������� �� ����������� ������� � ����������� ������ ��� �������� � �������
	$sql = "SELECT *
			FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	$de = query_arr($sql);

	$elemUpdate = array();
	foreach($de as $id => $r) {
		if(!_dialogEl($r['type_id'], 'func'))
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError(array(
				'delem_id' => $id,
				'text' => utf8('�� ��������� ���� <b>'.$r['label_name'].'</b>')
			));

		//���� ��� ���������� ������, ���������� ������ � ������ � ������ ���������
		if($r['type_id'] == 2 && $dialog['base_table'] == '_page_element' && $r['param_num_1'])
			$elemUpdate[] = "`table_id`=".$r['param_num_1'];

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

		_dialogSpisokFuncValUpdate($r['id']);
	}

	if(!$unit_id) {
		$sql = "INSERT INTO `".BASE_TABLE."` (
					`app_id`,
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id(BASE_TABLE);

		//���������� page_id, ���� ����
		$sql = "DESCRIBE `".BASE_TABLE."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `page_id`=".$page_id."
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

	return $unit_id;
}
function _dialogSpisokFuncValUpdate($cmp_id) {//���������� �������� ������� ����������� (���� ��������� �������� 5)
	if(!isset($_POST['func'][$cmp_id]))
		return;

	$v = $_POST['func'][$cmp_id];

	$sql = "SELECT COUNT(`id`)
			FROM `_dialog_component_func`
			WHERE `action_id`=5
			  AND `component_id`=".$cmp_id;
	if(!query_value($sql))
		return;
/*
	$sql = "UPDATE `_page_element`
			SET `txt_3`='".addslashes($v)."'
			WHERE `action_id`=5
			  AND `component_id`=".$cmp_id;
	query($sql);
*/
}
function _pageElementDel($unit_id) {//�������� �������� ��������
	$sql = "DELETE FROM `_page_element`
			WHERE `id`=".$unit_id;
	query($sql);

	return true;
}


























/*
function _dialogSpisokColUpdate($dialog, $unit_id, $elem, $de) {//��������/�������������� ������ ����������� ���� �������
	$elemUpdate = array();
	foreach($de as $id => $r) {
		if($r['type_id'] == 7)//info
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('�� ��������� ���� <b>'.$r['label_name'].'</b>');

		$elemUpdate[$r['col_name']] = utf8($v);
	}

	$id = 1;

	$sql = "SELECT `".$dialog['col']."`
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	if($col = query_value($sql)) {
		$col = json_decode(utf8($col), true);

		//��������� ������������� ID
		foreach($col as $r)
			if($id <= $r['id'])
				$id = ++$r['id'];
	}

	$elemUpdate['id'] = $id;

	$col[] = $elemUpdate;

	$sql = "UPDATE `".$dialog['base_table']."`
			SET `".$dialog['col']."`='".addslashes(win1251(json_encode($col)))."'
			WHERE `id`=".$unit_id;
	query($sql);

	return $unit_id;
}
*/