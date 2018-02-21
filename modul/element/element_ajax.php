<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('������������ ID ����������� ����');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('������� �� ����������');

		$menu = array(
			1 => '���������',
			2 => '����������',
	  		4 => '���������',
			9 => '<b class="red">SA</b>'
		);
		$action = array(
			3 => '�������� ���������� ������',
			1 => '�������� ��������',
			2 => '������� �� ��������',
			4 => '�������� �������� ������'
		);

		if(!SA) {
			unset($menu[9]);
			unset($action[4]);
		}

		if(!isset($menu[$dialog['menu_edit_last']]))
			$dialog['menu_edit_last'] = 1;

		define('BLOCK_EDIT', 1);

		$tab_id = 0;
		$tables = array();
		$group = array();
		$menu_sa = array();
		if(SA) {
			//��������� ������ ������ ���� � ����������� ���������
			$sql = "SHOW TABLES";
			$arr = query_array($sql);
			$n = 1;
			foreach($arr as $ass)
				foreach($ass as $base => $tab) {
					if($dialog['base_table'] == $tab)
						$tab_id = $n;
					$tables[$n++] = $tab;
				}

			//������ ���������
			$sql = "SELECT *
					FROM `_dialog_group`
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$group[] = array(
					'id' => _num($r['id']),
					'title' => utf8(_br($r['name'], ' ')),
					'content' => '<div class="'._dn(!$r['sa'], 'red').'">'.utf8(_br($r['name'])).'</div>'
				);
			}

			$menu_sa = array(
				1 => '������',
				2 => '�������'
			);
		}

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
					'<tr><td class="grey r">��� ����������� ����:'.
						'<td><input type="text" id="spisok_name" class="w250" maxlength="100" value="'.$dialog['spisok_name'].'" />'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'spisok_on',
									'title' => '������ ������ ������ ��� ������',
									'value' => $dialog['spisok_on']
							   )).
				'</table>'.
			'</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pb20'._dn($dialog['menu_edit_last'] == 9).'">'.
				'<div class="mt5 mb10 ml20 mr20">'.
		            '<input type="hidden" id="menu_sa" value="1" />'.
				'</div>'.

				'<table class="menu_sa-1 bs10">'.
					'<tr><td class="red w125 r">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="red r">������:'.
		                '<td><div id="dialog-width" class="dib w50">'.$dialog['width'].'</div>'.
		                    '<input type="hidden" id="width_auto" value="'.$dialog['width_auto'].'" />'.
					'<tr><td class="red r">������� � ����:'.
						'<td><input type="hidden" id="base_table" value="'.$tab_id.'" />'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'cmp_no_req',
									'title' => '���������� � ���������� �� ���������',
									'value' => $dialog['cmp_no_req']
							   )).
					//����������� �������. �� ��������� app_id.
		            //0 - �������� ������ ����������� ����������
		            //1 - ���� �����������
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'title' => '�������� ���� �����������',
									'value' => $dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'sa',
									'title' => '�������� ������ SA',
									'value' => $dialog['sa']
							   )).
				'</table>'.

				'<table class="menu_sa-2 bs5">'.
					'<tr><td class="red w150 r">��� ��������:'.
		                '<td><input type="text" id="element_name" class="w230 b" maxlength="100" value="'.$dialog['element_name'].'" />'.
					'<tr><td class="red r">������:'.
		                '<td><input type="hidden" id="element_group_id" value="'.$dialog['element_group_id'].'" />'.
					'<tr><td class="red r">��������� ������:'.
						'<td><input type="hidden" id="element_width" value="'.$dialog['element_width'].'" />'.
					'<tr><td class="red r">����������� ������:'.
						'<td><input type="hidden" id="element_width_min" value="'.$dialog['element_width_min'].'" />'.
					'<tr><td class="red r">CMP-������:'.
						'<td><input type="text" id="element_afics" class="w150" value="'.$dialog['element_afics'].'" />'.
					'<tr><td class="red r">������ ��� �������:'.
						'<td><input type="hidden" id="element_dialog_func" value="'.$dialog['element_dialog_func'].'" />'.

					'<tr><td class="red r pt20">����������:'.
						'<td class="pt20">'.
		                        _check(array(
									'attr_id' => 'element_search_access',
									'title' => '��������� ������� ����� �� ��������',
									'value' => $dialog['element_search_access']
								)).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_style_access',
									'title' => '��������� ��������� ������',
									'value' => $dialog['element_style_access']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_url_access',
									'title' => '��������� ������ �������',
									'value' => $dialog['element_url_access']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_hint_access',
									'title' => '��������� ������������ ���������',
									'value' => $dialog['element_hint_access']
							   )).

					'<tr><td class="red r pt20">�������������:'.
						'<td class="pt20">'.
		                        _check(array(
									'attr_id' => 'element_is_insert',
									'title' => '������� ������ ������',
									'value' => $dialog['element_is_insert']
								)).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_is_spisok_unit',
									'title' => '�������� ��������� ������',
									'value' => $dialog['element_is_spisok_unit']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_hidden',
									'title' => '������� �������',
									'value' => $dialog['element_hidden']
							   )).

					'<tr><td colspan="2"><div class="hd2 ml20 mt20 mb5">������� ����������� � ������� ������ ��������:</div>'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_page_paste',
									'title' => '������� � ���� ��������',
									'value' => $dialog['element_page_paste']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_dialog_paste',
									'title' => '������� � ���� �������',
									'value' => $dialog['element_dialog_paste']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_spisok_paste',
									'title' => '������� � ���� ������� ������',
									'value' => $dialog['element_spisok_paste']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_44_access',
									'title' => '������� � ������� �����',
									'value' => $dialog['element_44_access']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'element_td_paste',
									'title' => '������� � ������ �������',
									'value' => $dialog['element_td_paste']
							   )).
				'</table>'.
			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['menu'] = _selArray($menu);
		$send['menu_sa'] = _selArray($menu_sa);
		$send['action'] = _selArray($action);
		$send['blk'] = $dialog['blk'];
		$send['cmp'] = $dialog['cmp_utf8'];
		$send['html'] = utf8($html);
		$send['sa'] = SA;
		$send['tables'] = $tables;
		$send['group'] = $group;
		$send['dialog_spisok'] = SA ? _dialogSelArray(true) : array() ;

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
		if(!$dialog_id = _dialogTest())
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

	case 'image_upload'://���������� �����������
		$obj_name = _txt(@$_POST['obj_name']);
		$obj_id = _num(@$_POST['obj_id']);


		print_r($_POST);

		exit;

		if(!$unit_name)
			jsonError();

		$f = $_FILES['f1'];
		$im = null;

		//������ ����������� �� ����� 15 ��.
		if($f['size'] > 15728640)
			_imageCookie(4);

		switch($f['type']) {
			case 'image/jpeg': $im = @imagecreatefromjpeg($f['tmp_name']); break;
			case 'image/png': $im = @imagecreatefrompng($f['tmp_name']); break;
			case 'image/gif': $im = @imagecreatefromgif($f['tmp_name']); break;
			case 'image/tiff':
				$tmp = IMAGE_PATH.'/'.VIEWER_ID.'.jpg';
				$image = NewMagickWand(); // magickwand.org
				MagickReadImage($image, $f['tmp_name']);
				MagickSetImageFormat($image, 'jpg');
				MagickWriteImage($image, $tmp); //���������� ����������
				ClearMagickWand($image); //�������� � �������� ����������� ����������� �� ������
				DestroyMagickWand($image);
				$im = @imagecreatefromjpeg($tmp);
				unlink($tmp);
				break;
		}


		if(!$im)
			_imageCookie(1);

		$x = imagesx($im);
		$y = imagesy($im);
		if($x < 100 || $y < 100)
			_imageCookie(2);

		$fileName = time().'-'._imageNameCreate();

		if(!is_dir(IMAGE_PATH))
			mkdir(IMAGE_PATH, 0777, true);

		$small = _imageImCreate($im, $x, $y, 80, 80, IMAGE_PATH.'/'.$fileName.'-s.jpg');
		$big = _imageImCreate($im, $x, $y, 625, 625, IMAGE_PATH.'/'.$fileName.'-b.jpg');

		$sql = "SELECT COUNT(`id`)
				FROM `_image`
				WHERE !`deleted`
				  AND `unit_name`='".$unit_name."'
				  AND `unit_id`=".$unit_id."
				LIMIT 1";
		$sort = query_value($sql);

		$sql = "INSERT INTO `_image` (
					`app_id`,
					`path`,

					`small_name`,
					`small_x`,
					`small_y`,
					`small_size`,

					`big_name`,
					`big_x`,
					`big_y`,
					`big_size`,

					`unit_name`,
					`unit_id`,

					`sort`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					'".addslashes('//'.DOMAIN.IMAGE_HTML.'/')."',

					'".$fileName."-s.jpg',
					".$small['x'].",
					".$small['y'].",
					".$small['size'].",

					'".$fileName."-b.jpg',
					".$big['x'].",
					".$big['y'].",
					".$big['size'].",

					'".$unit_name."',
					".$unit_id.",

					".$sort.",
					".VIEWER_ID."
			)";
		query($sql);

		_imageCookie(7);
		break;
	case 'image_view':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND `id`=".$id;
		if(!$im = query_assoc($sql))
			jsonError();

		$n = 0; //����������� ����������� ������ ���������������� �����������
		$send['img'] = array();
		foreach(_imageArr($id) as $r) {
			if($r['id'] == $im['id'])
				$send['n'] = $n;
			$send['img'][] = array(
				'id' => $r['id'],
				'link' => $r['path'].$r['big_name'],
				'x' => $r['big_x'],
				'y' => $r['big_y'],
				'dtime' => utf8(FullData($r['dtime_add'], 1)),
				'deleted' => 0
			);
			$n++;
		}
		jsonSuccess($send);
		break;
	case 'image_obj_get':
		$unit_name = _txt(@$_POST['unit_name']);
		$unit_id = _num(@$_POST['unit_id']);

		if(!$unit_name)
			jsonError();

		//�������� ������ �������� �� ����������
		if(_num(@$_POST['clear'])) {
			$sql = "UPDATE `_image`
					SET `deleted`=1
					WHERE !`deleted`
					  AND `unit_name`='".$unit_name."'
					  AND `unit_id`=".$unit_id;
			query($sql);
		}

		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND `unit_name`='".$unit_name."'
				  AND `unit_id`=".$unit_id."
				ORDER BY `id`";
		$arr = query_arr($sql);

		$send['img'] = '';

		foreach($arr as $r) {
			$send['img'] .=
			'<a class="_iview" val="'.$r['id'].'">'.
				'<div class="div-del"></div>'.
				'<div class="img_minidel'._tooltip(utf8('�������'), -29).'</div>'.
				'<img src="'.$r['path'].$r['small_name'].'">'.
			'</a>';
		}

		jsonSuccess($send);
		break;
	case 'image_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		if(!_imageQuery($id))
			jsonError();

		$sql = "UPDATE `_image`
				SET `deleted`=1
				WHERE `id`=".$id;
		query($sql);

		//���������� ����������
		$n = 0;
		foreach(_imageArr($id, 1) as $r) {
			if($r['deleted'])
				continue;
			$sql = "UPDATE `_image` SET `sort`=".$n." WHERE `id`=".$r['id'];
			query($sql);
			$n++;
		}

		jsonSuccess();
		break;
}

function _dialogUpdate($dialog_id) {//���������� �������
	if(!_dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	if(!$insert_head = _txt($_POST['insert_head']))
		jsonError('�� ������ ��������� ��� �������� ������');
	$insert_button_submit = _txt($_POST['insert_button_submit']);
//		jsonError('�� ������ ����� ������ ��������');
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
	$sql = "SHOW TABLES LIKE '".$base_table."'";
	if(!query_array($sql))
		jsonError('������� �������������� �������');

	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_name = _txt($_POST['spisok_name']);
	if($spisok_on && !$spisok_name)
		jsonError('������� ��� ������ ��������');

	$width_auto = _num($_POST['width_auto']);
	$cmp_no_req = _num($_POST['cmp_no_req']);
	$app_any = _num($_POST['app_any']);

	$element_group_id = _num($_POST['element_group_id']);
	$element_name = _txt($_POST['element_name']);
	$element_width = _num($_POST['element_width']);
	$element_width_min = _num($_POST['element_width_min']);
	$element_search_access = _num($_POST['element_search_access']);
	$element_is_insert = _num($_POST['element_is_insert']);
	$element_style_access = _num($_POST['element_style_access']);
	$element_url_access = _num($_POST['element_url_access']);
	$element_hint_access = _num($_POST['element_hint_access']);
	$element_dialog_func = _num($_POST['element_dialog_func']);
	$element_afics = _txt($_POST['element_afics']);
	$element_page_paste = _num($_POST['element_page_paste']);
	$element_dialog_paste = _num($_POST['element_dialog_paste']);
	$element_spisok_paste = _num($_POST['element_spisok_paste']);
	$element_is_spisok_unit = _num($_POST['element_is_spisok_unit']);
	$element_44_access = _num($_POST['element_44_access']);
	$element_td_paste = _num($_POST['element_td_paste']);
	$element_hidden = _num($_POST['element_hidden']);

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`width`=".$width.",
				`width_auto`=".$width_auto.",
				`cmp_no_req`=".$cmp_no_req.",

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

				`element_group_id`=".$element_group_id.",
				`element_name`='".addslashes($element_name)."',
				`element_width`=".$element_width.",
				`element_width_min`=".$element_width_min.",
				`element_search_access`=".$element_search_access.",
				`element_is_insert`=".$element_is_insert.",
				`element_style_access`=".$element_style_access.",
				`element_url_access`=".$element_url_access.",
				`element_hint_access`=".$element_hint_access.",
				`element_dialog_func`=".$element_dialog_func.",
				`element_afics`='".addslashes($element_afics)."',
				`element_page_paste`=".$element_page_paste.",
				`element_dialog_paste`=".$element_dialog_paste.",
				`element_spisok_paste`=".$element_spisok_paste.",
				`element_is_spisok_unit`=".$element_is_spisok_unit.",
				`element_44_access`=".$element_44_access.",
				`element_td_paste`=".$element_td_paste.",
				`element_hidden`=".$element_hidden.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_cache('clear', '_dialogQuery'.$dialog_id);

	return $dialog_id;
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('������� �� ����������');

	$block_id = _num(@$_POST['block_id'], 1);

	//��������� ������ ������� ������
	$unit = array();
	$unit_id = _num(@$_POST['unit_id'], 1);
	if($unit_id > 0) {
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

		if(!$block_id && isset($dialog['field']['block_id']))
			$block_id = _num($unit['block_id']);
	}

	$act = $unit_id > 0 ? 'edit' : 'insert';
	if(_num(@$_POST['del']))
		$act = 'del';

	$send['page_id'] = _num(@$_POST['page_id']);;
	$send['dialog_id'] = $dialog_id;
	$send['block_id'] = $block_id;
	$send['unit_id'] = $unit_id;
	$send['dialog_source'] = _num(@$_POST['dialog_source']);

	//�������� ������, ���������� ��� �������� �������
	$unit['source'] = $send;

	$send['act'] = $act;
	$send['edit_access'] = SA || $dialog['app_id'] == APP_ID ? 1 : 0;//����� ��� �������������� �������
	$send['width'] = $dialog['width_auto'] ? 0 : _num($dialog['width']);
	$send['head'] = utf8($dialog[$act.'_head']);
	$send['button_submit'] = utf8($dialog[$act.'_button_submit']);
	$send['button_cancel'] = utf8($dialog[$act.'_button_cancel']);
	$send['html'] = utf8(_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $unit));

	//���������� ���������� ��������� �����������
	foreach($dialog['cmp_utf8'] as $cmp_id => $cmp)
		switch($cmp['dialog_id']) {
			//������������ ��������
			case 17://select - ������������ ��������
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = _elemValue($cmp_id);
				break;
			//�������� ��� select, radio, dropdown
			case 19:
				if(!$unit_id)
					break;

				$sql = "SELECT *
						FROM `_element`
						WHERE `block_id`=-".$unit_id."
						ORDER BY `sort`";
				if(!$arr = query_arr($sql))
					break;

				$spisok = array();
				foreach($arr as $id => $r)
					$spisok[] = array(
						'id' => _num($id),
						'title' => utf8($r['txt_1']),
						'content' => utf8($r['txt_2']),
						'def' => _num($r['def']),
						'use' => 0  //���������� ������������� ��������, ����� ������ ���� �������
					);

				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;

				//���� ������� ���� �� �����������
				if(empty($unit['col']))
					break;

				//������, � ������� ��������� ���� � ���������
				if(!$block = _blockQuery($unit['block_id']))
					break;

				//���� ������ ��� ��������
				if($block['obj_name'] != 'dialog')
					break;

				$dlg = _dialogQuery($block['obj_id']);

				//��������� ���������� ������������� ��������
				$sql = "SELECT
							`".$unit['col']."` `id`,
							COUNT(*) `use`
						FROM `".$dlg['base_table']."`
						WHERE `dialog_id`=".$block['obj_id']."
						GROUP BY `".$unit['col']."`";
				if($ass = query_ass($sql))
					foreach($spisok as $n => $r) {
						if(empty($ass[$r['id']]))
							continue;
						$spisok[$n]['use'] = $ass[$r['id']];
					}

				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;
				break;
			//select - ����� ������ (��� ������ ����������)
			case 24:
				switch($cmp['num_1']) {
					case 960: $vvv = _dialogSpisokOnPage($block_id); break;
					case 961: $vvv = _dialogSpisokOnConnect($block_id, $unit_id); break;
					default:  $vvv = _dialogSpisokOn($dialog_id, $block_id, $unit_id); break;
				}
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $vvv;
				break;
			//select - ����� ������� �� ������� ������ (��� ������)
			case 29:
				$sel_id = 0;//��������� ��������
				if($unit_id)
					$sel_id = $unit[$cmp['col']];
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = _spisokConnect($cmp_id, $v='', $sel_id);
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

				$spisok = array();
				foreach($arr as $r) {
					$spisok[] = array(
						'id' => _num($r['id']),
						'dialog_id' => _num($r['dialog_id']),
						'width' => _num($r['width']),
						'tr' => utf8($r['txt_7']),
						'title' => utf8(_elemUnit($r)),
						'font' => $r['font'],
						'color' => $r['color'],
						'pos' => $r['txt_8'],
						'url' => _num($r['url']),
					);
				}
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;
				break;
			//SA: select - ����� ����� �������
			case 37:
				if(!$block = _blockQuery($block_id))
					break;

				//����� ����� ������� ����� �������������, ������ ���� ������� ����������� � �������
				if($block['obj_name'] != 'dialog')
					break;

				if(!$colDialog = _dialogQuery($block['obj_id']))
					break;

				//��������� ������������ �������
				$colUse = array();
				foreach($colDialog['cmp'] as $r) {
					if(!$col = $r['col'])
						continue;
					$colUse[$col] = 1;
				}

				$field = array();
				$n = 1;
				foreach($colDialog['field'] as $col => $k)
					switch($col) {
						case 'id':
						case 'id_old':
						case 'num':
						case 'app_id':
						case 'page_id':
						case 'block_id':
						case 'element_id':
						case 'dialog_id':
						case 'width':
						case 'color':
						case 'font':
						case 'size':
						case 'mar':
						case 'sort':
						case 'deleted':
						case 'user_id_add':
						case 'user_id_del':
						case 'dtime_add':
						case 'dtime_del':
						case '': break;
						default:
							$u = array(
								'id' => $n++,
								'title' => $col
							);
							if(isset($colUse[$col])) {
								$color = $unit_id && $unit['col'] == $col ? 'color-pay' : 'red';
								$u['content'] = '<div class="'.$color.' b">'.$col.'</div>';
							}
							$field[] = $u;
					}

				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $field;
				break;
			//SA: Select - ����� ����������� ����
			case 38:
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = _dialogSelArray();
				break;
			//SA: Select - ������������
			case 41:
				//����������� ID ��������� �����.
				if(!$block_id)
					break;

				$BL = _blockQuery($block_id);

				//�������� ���� �� �������� ������ �� �������
				if($BL['obj_name'] != 'dialog')
					break;

				//����������� �������� �������
				if(!$EL = $BL['elem'])
					break;

				//�������� ������� �� �������� ���������� �����
				if($EL['dialog_id'] != 17)
					break;

				$dialog['cmp_utf8'][$cmp_id]['txt_1'] = utf8($EL['txt_1']);
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = _elemValue($EL['id']);
				break;
			//��������� ���������� �������� ������
			case 49:
				if($unit_id <= 0)
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

				$spisok = array();
				foreach($arr as $r) {
					$spisok[] = array(
						'id' => _num($r['id']),
						'dialog_id' => _num($r['dialog_id']),
						'title' => utf8(_elemUnit($r)),
						'spc' => _num($r['num_8']) //������ ������
					);
				}
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;
				break;
			//��������� ����� �������� ������� ������
			case 56:
				if($unit_id <= 0)
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

				$spisok = array();
				foreach($arr as $r) {
					$spisok[] = array(
						'id' => _num($r['id']),
						'dialog_id' => _num($r['dialog_id']),
						'minus' => _num($r['num_8']), //���������=1, ��������=0
						'title' => utf8(_elemUnit($r))
					);
				}
				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;
				break;
			//��������� ������� ���� ������������ ������
			case 58:
				if(!$unit_id)
					break;

				$sql = "SELECT *
						FROM `_element`
						WHERE `block_id`=-".$unit_id."
						ORDER BY `sort`";
				if(!$arr = query_arr($sql))
					break;

				$spisok = array();
				foreach($arr as $id => $r) {
					$c = count(_ids($r['txt_2'], 1));
					$blk_title = $r['txt_2'] ? $c.' ����'._end($c, '', '�', '��') : '';
					$spisok[] = array(
						'id' => _num($id),
						'title' => utf8($r['txt_1']),
						'blk' => $r['txt_2'],
						'blk_title' => utf8($blk_title),
						'def' => _num($r['def'])
					);
				}

				$dialog['cmp_utf8'][$cmp_id]['vvv'] = $spisok;
				break;
		}

	$send['blk'] = $dialog['blk'];
	$send['cmp'] = $dialog['cmp_utf8'];
	$send['unit'] = utf8($unit);

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
function _elemValue($elem_id) {//������������� �������� � �������� select, ����������� ����� [19]
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=-".$elem_id."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	foreach($arr as $id => $r)
		$spisok[] = array(
			'id' => _num($id),
			'title' => utf8($r['txt_1']),
			'content' => utf8($r['txt_1'].'<div class="fs11 grey">'._br($r['txt_2']).'</div>')
		);

	return $spisok;
}



