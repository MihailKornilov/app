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
			1 => '���������, ������',
			2 => '����������',
  		    3 => '��������',
//  		4 => '�������',
//			5 => '����������� �����',
			9 => '<b class="red">SA</b>'
		);

		$action = array(
			1 => '�������� ��������',
			2 => '������� �� ��������'
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
					'<button class="vk green" onclick="_dialogCmpEdit()">����� ���������</button>'.
				'</div>'.
			'</div>'.

			//��������
			'<div class="dialog-menu-3">'.
				'<div class="_info mar20">'.
					'���������� ��������, ������� ���������� ����� �������� ��� ���������� ������.'.
				'</div>'.
				'<table class="bs10 mb20">'.
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
		$send['action'] = _selArray($action);
		$send['element'] = _dialogEl();
		$send['cmp_name'] = _dialogEl(0, 'name');
		$send['component'] = _dialogComponentSpisok($dialog_id, 'arr_edit');
		$send['func'] = (object)_dialogFuncSpisok($dialog_id);
		$send['spisokOn'] = _dialogSpisokOn();
		$send['page_list'] = _dialogPageList();
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

		if($dialog['sa'] && !SA)
			jsonError('��� �������');

		$page_id = _num($_POST['page_id']);

		$data = array();
		if($unit_id = _num($_POST['unit_id'])) {
			$cond = "`id`=".$unit_id;
			if(isset($dialog['field']['app_id']))
				$cond .= " AND `app_id` IN (0,".APP_ID.")";
			$sql = "SELECT *
					FROM `".$dialog['base_table']."`
					WHERE ".$cond;
			if(!$data = query_assoc($sql))
				jsonError('������ �� ����������');
			if(@$data['sa'] && !SA)
				jsonError('��� �������');
			if(@$data['deleted'])
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
		$send['component'] = _dialogComponentSpisok($dialog_id, 'arr', array(), $page_id);
		$send['func'] = _dialogFuncSpisok($dialog_id);
		$send['html'] = utf8($html);
		$send['data'] = $data;
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

	case 'page_block_add'://���������� ����� �� ��������
		if(!$page_id = _num($_POST['page_id']))
			jsonError('������������ ID ��������');

		$sql = "INSERT INTO `_page_block` (
					`app_id`,
					`page_id`,
					`sort`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$page_id.",
					"._maxSql('_page_block').",
					".VIEWER_ID."
				)";
		query($sql);

		$block_id = query_insert_id('_page_block');

		$block = array(
			'id' => $block_id,
			'parent_id' => 0,
			'w' => 0,
			'elem_count' => 0,
			'sort' => 0
		);

		$send['id'] = $block_id;
		$send['html'] = utf8(
			'<div id="pb_'.$block_id.'" class="pb prel h50" val="'.$block_id.'">'.
				_pageBlockPas($block, 1).
			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'page_block_div'://������� ����� �� ��� �����
		if(!$block_id = _num($_POST['block_id']))
			jsonError('������������ ID �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$block_id.' �� ����������');


		//���� ���� �������� ��������, �� �� ���������� ��������, � ��� ��� �������� ����, ������� ����� ��������� ��� ������� � ��� �����
		if(!$parent_id = _num($block['parent_id'])) {
			$sql = "INSERT INTO `_page_block` (
						`app_id`,
						`page_id`,
						`sort`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".$block['page_id'].",
						".$block['sort'].",
						".VIEWER_ID."
					)";
			query($sql);

			$parent_id = query_insert_id('_page_block');

			$sql = "UPDATE `_page_block`
					SET `parent_id`=".$parent_id.",
						`w`=1000
					WHERE `id`=".$block_id;
			query($sql);

			$block['w'] = 1000;
		}

		//��������� �������� ����������, ����� �� �����������
		$sql = "UPDATE `_page_block`
				SET `sort`=`sort`+1
				WHERE `parent_id`=".$parent_id."
				  AND `sort`>".$block['sort'];
		query($sql);

		//�������� ������ ��������� �����
		$sql = "INSERT INTO `_page_block` (
					`app_id`,
					`page_id`,
					`parent_id`,
					`w`,
					`sort`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$block['page_id'].",
					".$parent_id.",
					100,
					".($block['sort'] + 1).",
					".VIEWER_ID."
				)";
		query($sql);

		//����������� �����, �� �������� ����� ������� ������ 100px
		if($block['w'] < 200) {
			$sql = "SELECT `id`
					FROM `_page_block`
					WHERE `parent_id`=".$parent_id."
					ORDER BY `w` DESC
					LIMIT 1";
			$block_id = query_value($sql);
		}

		//��������� ������� ������������ �����
		$sql = "UPDATE `_page_block`
				SET `w`=`w`-100
				WHERE `id`=".$block_id;
		query($sql);

		$send['html'] = utf8(_pageShow($block['page_id'], 1));

		jsonSuccess($send);
		break;
	case 'page_block_resize'://��������� ������� �����
		if(!$block_id = _num($_POST['block_id']))
			jsonError('������������ ID �����');
		if(!$w = _num($_POST['w']))
			jsonError('������������ ����� �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$block_id.' �� ����������');

		//��������� ����� ����� �����
		$sql = "UPDATE `_page_block`
				SET `w`=".$w."
				WHERE `id`=".$block_id;
		query($sql);

		//��������� ����� ���������� ����� �� ����������� �������
		$sql = "UPDATE `_page_block`
				SET `w`=`w`+(".($block['w'] - $w).")
				WHERE `parent_id`=".$block['parent_id']."
				  AND `sort`>".$block['sort']."
				ORDER BY `sort`
				LIMIT 1";
		query($sql);

		$send['html'] = utf8(_pageShow($block['page_id'], 1));
		
		jsonSuccess($send);
		break;
	case 'page_block_style_load'://��������� ������ ����� ��� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		//�������
		$ex = explode(' ', $block['pad']);
		$pad =
		'<div class="pas-block-pad mt10 center">'.

			'<div class="fs15 color-555 mb5">������</div>'.
			'<button class="vk small cancel mt1 mr3 minus">�</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[0] ? 'bg-dfd' : 'pale').'">'.$ex[0].'</div>'.
			'<button class="vk small cancel mt1 plus">�</button>'.

			'<table class="w100p ml10 mt30 mb30">'.
				'<tr><td class="w200">'.
						'<div class="dib fs15 color-555 mr5">�����</div>'.
						'<button class="vk small cancel mt1 mr3 minus">�</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[3] ? 'bg-dfd' : 'pale').'">'.$ex[3].'</div>'.
						'<button class="vk small cancel mt1 plus">�</button>'.
					'<td>'.
						'<button class="vk small cancel mt1 mr3 minus">�</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[1] ? 'bg-dfd' : 'pale').'">'.$ex[1].'</div>'.
						'<button class="vk small cancel mt1 plus">�</button>'.
						'<div class="dib fs15 color-555 ml5">������</div>'.
			'</table>'.

			'<button class="vk small cancel mt1 mr3 minus">�</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[2] ? 'bg-dfd' : 'pale').'">'.$ex[2].'</div>'.
			'<button class="vk small cancel mt1 plus">�</button>'.
			'<div class="fs15 color-555 mt3">�����</div>'.

		'</div>';

		//������� �����
		$ex = explode(' ', $block['bor']);
		$bor =
		'<div class="mar20 w250 center">'.
			_check(array(
				'id' => 'bor0',
				'title' => '������',
				'value' => $ex[0],
				'light' => 1
			)).

			'<table class="w100p ml10 mt20 mb20">'.
				'<tr><td class="w100">'.
						_check(array(
							'id' => 'bor3',
							'title' => '�����',
							'value' => $ex[3],
							'light' => 1
						)).
					'<td>'.
						_check(array(
							'id' => 'bor1',
							'title' => '������',
							'value' => $ex[1],
							'light' => 1
						)).
			'</table>'.

			_check(array(
				'id' => 'bor2',
				'title' => '�����',
				'value' => $ex[2],
				'light' => 1
			)).
		'</div>';


		$send['html'] = utf8(
			'<div class="ml10 mr10">'.
				'<div class="hd2">���������� �������:</div>'.
				$pad.

				'<div class="hd2 mt20">���� �������:</div>'.
				'<div class="pas-block-bg mt10">'.
					'<div class="'.($block['bg'] == 'bg-fff' ? 'sel' : '').' dib h50 w50 bor-e8 curP" val="bg-fff"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr1' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr3" val="bg-gr1"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr3' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr3" val="bg-gr3"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr2' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr2" val="bg-gr2"></div>'.
					'<div class="'.($block['bg'] == 'bg-ffe' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-ffe" val="bg-ffe"></div>'.
				'</div>'.

				'<div class="hd2 mt20">�������:</div>'.
				$bor.
			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'page_block_style_save'://���������� ������ �����
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID �����');

		$bg = _txt($_POST['bg']);

		//�������
		$ex = explode(' ', $_POST['pad']);
		$pad =  _num($ex[0]).' './/������
				_num($ex[2]).' './/������
				_num($ex[3]).' './/�����
				_num($ex[1]);    //�����

		//�������
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/������
				_num($ex[1]).' './/������
				_num($ex[2]).' './/�����
				_num($ex[3]);    //�����


		//��������� ������ �����
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		//��������� ������
		$sql = "UPDATE `_page_block`
				SET `bg`='".addslashes($bg)."',
				    `pad`='".$pad."',
				    `bor`='".$bor."'
				WHERE `id`=".$id;
		query($sql);

//		$send['html'] = utf8(_pageShow($block['page_id'], 1));

		jsonSuccess();
		break;
	case 'page_block_del'://�������� �����
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID �����');

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		//���� ���� �������� ��������
		if($block['parent_id']) {
			//������� ���������� �������� ������, � ������� ������� ��������� ����
			$sql = "SELECT COUNT(*)
					FROM `_page_block`
					WHERE `parent_id`=".$block['parent_id'];
			$parentCount = query_value($sql);

			//���� ���� ��������� ����, �� ��������� ��� �������� �� ��������
			if($parentCount < 3) {
				//��������� ������ �����-��������
				$sql = "SELECT *
						FROM `_page_block`
						WHERE `id`=".$id;
				$blockParent = query_assoc($sql);

				//�������� �����-��������
				$sql = "DELETE FROM `_page_block` WHERE `id`=".$block['parent_id'];
				query($sql);

				//���������� ���� ���������� �� �������� ��������
				$sql = "UPDATE `_page_block`
						SET `parent_id`=0,
							`w`=0,
							`sort`=".$blockParent['sort']."
						WHERE `parent_id`=".$block['parent_id'];
				query($sql);
			} else {
				//���������� ����� ������� ����� � ������ �� ����� ��������� �����
				$sql = "UPDATE `_page_block`
						SET `w`=`w`+".$block['w']."
						WHERE `parent_id`=".$block['parent_id']."
						  AND `id`!=".$id."
						ORDER BY `sort`
						LIMIT 1";
				query($sql);
			}
		}

		//�������� �����
		$sql = "DELETE FROM `_page_block` WHERE `id`=".$id;
		query($sql);

		//�������� ��������� � �����
		$sql = "DELETE FROM `_page_element` WHERE `block_id`=".$id;
		query($sql);

		$send['html'] = utf8(_pageShow($block['page_id'], 1));

		jsonSuccess($send);
		break;

	case 'page_elem_style_save'://���������� ������ ��������
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID ��������');

		//�������
		$pad = _txt(@$_POST['pad']);
		$type = _txt(@$_POST['type']);
		$pos = _txt(@$_POST['pos']);
		$color = _txt(@$_POST['color']);
		$font = _txt(@$_POST['font']);

		$size = _num(@$_POST['size']);
		$size = $size == 13 ? '' : 'fs'.$size;

		//��������� ������ ��������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$elem = query_assoc($sql))
			jsonError('�������� id'.$id.' �� ����������');

		//��������� ������
		$sql = "UPDATE `_page_element`
				SET `pad`='".$pad."',
					`type`='".$type."',
					`pos`='".$pos."',
					`color`='".$color."',
					`font`='".$font."',
					`size`='".$size."'
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'page_elem_del'://���������� ����� ������ � �������� ��������
		if(!$element_id = _num($_POST['id']))
			jsonError('������������ ID ��������');

		//��������� ������ ��������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$element_id;
		if(!$elem = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		//�������� ��������
		$sql = "DELETE FROM `_page_element` WHERE `id`=".$element_id;
		query($sql);

		$send['html'] = utf8(_pageShow($elem['page_id'], 1));

		jsonSuccess();
		break;

	case 'spisok_add'://�������� ������ ������� � _spisok
		$page_id = _num($_POST['page_id']);
		$block_id = _num($_POST['block_id']);

		$v = _dialogSpisokUpdate(0, $page_id, $block_id);

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

		$v = _dialogSpisokUpdate($unit_id);

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
	case 'spisok_get'://��������� ����������� ������ �� ��������
		if(!$element_id = _num($_POST['element_id']))
			jsonError('������������ ID �������� �������');

		$v = _txt($_POST['v']);
		
		//��������� ������ �������� ������
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$element_id;
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
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('��� ������� ������ �� ��������');

		$send['attr_id'] = '#pe_'.$peSpisok['id'];
		$send['spisok'] = utf8(_pageSpisok($peSpisok));

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

		$CMP = $dialog['component'];

		$labelName = array();
		$labelName[] = array(
			'uid' => -1,
			'title' => utf8('���������� �����'),
			'content' => utf8('<div class="color-pay">���������� �����</div>')
		);

		$arrDef = array();//������ ������� �� ���������, ���� ��������� ������ ������������ �������
		$arr182 = array();//������ ��������� ��� �������

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
				$tmpHtml = '';
				//��������� ��������� �������
				$sql = "SELECT *
						FROM `_page_element`
						WHERE `app_id` IN(0,".APP_ID.")
						  AND `parent_id`=".$elem_id."
						ORDER BY `sort`";
				if($tmp = query_arr($sql))
					foreach($tmp as $id => $r) {
						$txt = '';
						$txt_2 = '';
						switch($r['num_4']) {
							case -1://���������� �����
								$txt = '123';
								break;
							case -2://���� ��������
								$txt = '21 ��� 2017';
								break;
							case -4://������������ �����
								$txt = _br($r['txt_2']);
								$txt_2 = $r['txt_2'];
								break;
							default:
								if($r['num_4'] <= 0)
									continue;
								$txt_2 = $r['txt_2'];
								switch($r['txt_2']) {
									case 1://��� �������
										$txt = $CMP[$r['num_4']]['label_name'];
										break;
									case 2://�������� �������
										$txt = '�������� "'.$CMP[$r['num_4']]['label_name'].'"';
										break;
									default: continue;
								}
						}

						$size = 13;
						if($r['size']) {
							$ex = explode('fs', $r['size']);
							$size = _num($ex[1]);
						}
						$arr182[] = array(
							'id' => _num($id),
							'tmp' => 1,
							'txt_2' => utf8($txt_2),
							'num_4' => _num($r['num_4'], 1),
							'fontAllow' => 1,
							'type' => $r['type'],
							'pos' => $r['pos'],
							'color' => $r['color'],
							'font' => $r['font'],
							'size' => $size,
							'pad' => $r['pad'],
						);
						$tmpHtml .=
							'<div id="pe_'.$id.'" class="pe prel '.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'"'._pageElemStyle($r).' val="'.$id.'">'.
								'<div class="elem-pas" val="'.$id.'"></div>'.
								$txt.
							'</div>';
					}

				$html =
					'<div class="hd2">��������� ������� ������� ������:</div>'.
					'<table class="mt10">'.
						'<tr><td class="top">'.
								'<input type="hidden" id="elem_type" />'.
							'<td class="top pl10 dn">'.
								'<input type="hidden" id="col_type" />'.
							'<td class="top dn">'.
								'<textarea id="tmp_elem_txt_2" class="min w250 ml10"></textarea>'.
							'<td class="top dn">'.
								'<button id="elem-add" class="vk green ml10">�������� �������</button>'.
					'</table>'.
					'<div id="tmp-elem-list" class="elem-sort mh50 mt10 bor-f0 bg-ffe">'.$tmpHtml.'</div>';
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

		//������ ����������� �������
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
		$send['arr182'] = $arr182;
		$send['spisok_type'] = _num($elem['num_1']);
		$send['html'] = utf8($html);

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

				`action_id`=".$action_id.",
				`action_page_id`=".$action_page_id.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_dialogComponentUpdate($dialog_id);
	_dialogFuncUpdate($dialog_id);

	_cache('clear', '_dialogQuery'.$dialog_id);

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
		$require = _bool($r['require']);
		$hint = _txt($r['hint']);
		$width = _num($r['width']);

		$sql = "INSERT INTO `_dialog_component` (
					`id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`require`,
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
					".$require.",
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
					`require`=VALUES(`require`),
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
			$type_7 = $type_id == 7 || $type_id == 9;//info & head
			
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

			if($r['type_id'] == 2 && !$edit)
				switch($r['num_4']) {
					case 2://��� ������ ��� � ���������� ��������
						$arr[$n]['v'] = $r['num_5'] ? _dialogSpisokOnPage($page_id) : _dialogSpisokOn();
						break;
					case 3://��������� �������� ����������� �������
						$arr[$n]['v'] = _dialogSpisokList($r['num_1'], $r['num_2']);
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
function _dialogComponent_autoSelectPage($val, $r, $page_id) {//��������� �������� �� ���������, ���� ������ ����������� �� ��������, ������ ����� ������ �� ������ ��������
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

function _dialogSpisokList($dialog_id, $component_id) {//������ ������� (���� ������ ��� select)
	$dialog = _dialogQuery($dialog_id);

	$sql = "SELECT `col_name`
			FROM `_dialog_component`
			WHERE `id`=".$component_id;
	if(!$colName = query_value($sql))
		$colName = 'id';

	//����������� ������ ������� ����������� �������
	if($dialog['base_table'] == '_page')
		return _dialogPageList();

	$sql = "SELECT `id`,`".$colName."`
			FROM `".$dialog['base_table']."`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogSpisokUpdate($unit_id=0, $page_id=0, $block_id=0) {//��������/�������������� ������ ������
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
		$send['unit_id'] = $unit_id;

		//���������� ��������� �������
		$sql = "DESCRIBE `".BASE_TABLE."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
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
		_dialogSpisokFuncValUpdate($dialog, $id, $unit_id);

	if(BASE_TABLE == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
function _dialogSpisokFuncValUpdate($dialog, $cmp_id, $unit_id) {//���������� �������� ������� ����������� (���� ��������� �������� 5)
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
				$num_6 = _num($ex[1]);
				$num_7 = _num($ex[2]);

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
				$insert = array();
				$sort = 0;
				$idsEdit = '0';//id ���������, ������� ���������������, �� ��� ��������
				foreach($v as $r) {
					$insert[] = "(
						".$r['id'].",
						".APP_ID.",
						".$unit_id.",
						'".$r['type']."',
						'".$r['pos']."',
						'".$r['color']."',
						'".$r['font']."',
						'fs".$r['size']."',
						'".$r['pad']."',
						'".addslashes(_txt($r['txt_2']))."',
						".$r['num_4'].",
						".$sort++."
					)";
					if($r['id'])
						$idsEdit .= ','.$r['id'];
				}

				$sql = "DELETE FROM `".BASE_TABLE."`
						WHERE `parent_id`=".$unit_id."
						  AND `id` NOT IN (".$idsEdit.")";
				query($sql);

				if(empty($insert))
					return;

				$sql = "INSERT INTO `".BASE_TABLE."` (
							`id`,
							`app_id`,
							`parent_id`,
							`type`,
							`pos`,
							`color`,
							`font`,
							`size`,
							`pad`,
							`txt_2`,
							`num_4`,
							`sort`
						) VALUES ".implode(',', $insert)."
						ON DUPLICATE KEY UPDATE
							`type`=VALUES(`type`),
							`pos`=VALUES(`pos`),
							`color`=VALUES(`color`),
							`font`=VALUES(`font`),
							`size`=VALUES(`size`),
							`pad`=VALUES(`pad`),
							`txt_2`=VALUES(`txt_2`),
							`num_4`=VALUES(`num_4`),
							`sort`=VALUES(`sort`)
						";
				query($sql);
				break;
		}
	}
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