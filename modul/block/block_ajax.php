<?php
switch(@$_POST['op']) {
	case 'block_grid_on'://��������� ���������� �������
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('�������������� ��� �������');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('������������ ID �������');

		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id."
				  AND !`parent_id`
				ORDER BY `y`,`x`";
		$arr = query_arr($sql);

		$send['html'] = utf8(_blockGrid($arr));

		jsonSuccess($send);
		break;
	case 'block_grid_off'://���������� ���������� �������
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('�������������� ��� �������');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('������������ ID �������');
		if(!$width = _num($_POST['width']))
			jsonError('������������ ������');

		define('BLOCK_EDIT', 1);
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_change'://���������/���������� ��������� ������ ���������
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('�������������� ��� �������');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('������������ ID �������');
		if(!$width = _num($_POST['width']))
			jsonError('������������ ������');

		$on = _num($_POST['on']);

		define('ELEM_WIDTH_CHANGE', $on);
		define('BLOCK_EDIT', 1);

		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_save'://���������� ������ ��������
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('������������ ID ��������');

		$width = _num($_POST['width']);

		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$elem = query_assoc($sql))
			jsonError('�������� �� ����������');

		if(!_dialogParam($elem['dialog_id'], 'element_width'))
			jsonError('� ����� �������� �� ����� ������������� ������');

		$sql = "UPDATE `_element`
				SET `width`=".$width."
				WHERE `id`=".$elem_id;
		query($sql);

		jsonSuccess();
		break;
	case 'block_grid_save'://���������� ������ ������ ����� ��������������
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('�������������� ��� �������');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('������������ ID �������');
		if(!$width = _num($_POST['width']))
			jsonError('������������ ������');

		//�������� ������� ������������� �����
		$parent = array();
		if($parent_id = _num(@$_POST['parent_id'])) {
			$sql = "SELECT *
					FROM `_block`
					WHERE `obj_name`='".$obj_name."'
					  AND `obj_id`=".$obj_id."
					  AND `id`=".$parent_id;
			if(!$parent = query_ass($sql))
				jsonError('������������� ����� �� ����������');

			switch($obj_name) {
				default:
				case 'page': $width = 1000; break;
				case 'spisok':
					$sql = "SELECT *
							FROM `_block`
							WHERE `id`=".$obj_id;
					if(!$block = query_assoc($sql))
						jsonError('����� ��� ��������-������ �� ����������');

					$sql = "SELECT *
							FROM `_element`
							WHERE `block_id`=".$obj_id;
					if(!$elem = query_assoc($sql))
						jsonError('��������-������ �� ����������');

					//������������� ������ � ������ ��������
					$ex = explode(' ', $elem['mar']);
					$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
					break;
				case 'dialog':
					$dialog = _dialogQuery($obj_id);
					$width = $dialog['width'];
					break;
			}
		}

		//��������� id ���������, ������������ � ������ (��� ������������ �� �������� � �������� ������)
		$elemIdsPrev = 0;
		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id;
		if($block_ids = query_ids($sql)) {
			$sql = "SELECT `id`
					FROM `_element`
					WHERE `block_id` IN (".$block_ids.")";
			$elemIdsPrev = query_ids($sql);
		}

		$idsNotDel = array(0);
		$insert = array();

		if($arr = @$_POST['arr']) {
			foreach($arr as $r) {
				$ex = explode(',', $r);
				$id = _num($ex[0]);
				$x = _num($ex[1]);
				$y = _num($ex[2]);
				if(!$w = _num($ex[3]))
					continue;
				if(!$h = _num($ex[4]))
					continue;
				$insert[] = "(".
					$id.",".
					$parent_id.",".
					"'".$obj_name."',".
					$obj_id.",".
					$x.",".
					$y.",".
					$w.",".
					$h.",".
					($w * 10).",".
					($h * 10).",".
					VIEWER_ID.
				")";
				if($id)
					$idsNotDel[$id] = $id;
			}
		}

		//�������� �������� ������
		$sql = "DELETE FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id."
				  AND `parent_id`=".$parent_id."
				  AND `id` NOT IN (".implode(',', $idsNotDel).")";
		query($sql);

		//�������� �������� �������� ������
		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id;
		if($arr = query_arr($sql)) {
			$idsForDel = array();
			foreach($arr as $id => $r) {
				if(!$parent_id = $r['parent_id'])
					continue;
				$ids = array();
				$ids[$id] = $id;
				$DEL_FLAG = true;
				while(true) {
					if(!$p = @$arr[$parent_id])
						break;
					$ids[$p['id']] = $p['id'];
					if(!$parent_id = $p['parent_id']) {
						$DEL_FLAG = false;
						break;
					}
				}
				if($DEL_FLAG)
					$idsForDel += $ids;
			}

			if($idsForDel) {
				$sql = "DELETE FROM `_block` WHERE `id` IN (".implode(',', $idsForDel).")";
				query($sql);
			}
		}


		//�������� ��������� � �������� ������
		$elemIdsNotDel = 0;
		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id;
		if($block_ids = query_ids($sql)) {
			$sql = "SELECT `id`
					FROM `_element`
					WHERE `block_id` IN (".$block_ids.")";
			$elemIdsNotDel = query_ids($sql);
		}
		$sql = "DELETE FROM `_element`
				WHERE `id` IN (".$elemIdsPrev.")
				  AND `id` NOT IN (".$elemIdsNotDel.")";
		query($sql);


		if($insert) {
			$sql = "INSERT INTO `_block` (
						`id`,
						`parent_id`,
						`obj_name`,
						`obj_id`,
						`x`,
						`y`,
						`w`,
						`h`,
						`width`,
						`height`,
						`viewer_id_add`
					) VALUES ".implode(',', $insert)."
					ON DUPLICATE KEY UPDATE
						`x`=VALUES(`x`),
						`y`=VALUES(`y`),
						`w`=VALUES(`w`),
						`h`=VALUES(`h`),
						`width`=VALUES(`width`),
						`height`=VALUES(`height`)";
			query($sql);
		}

		_blockChildCountSet($obj_name, $obj_id);

		define('BLOCK_EDIT', 1);

		$send['level'] = utf8(_blockLevelChange($obj_name, $obj_id, $width));
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_unit_style_save'://���������� ������ �����
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID �����');

		$sa = _num($_POST['sa']);
		$width_auto = _num($_POST['width_auto']);
		$pos = _txt($_POST['pos']);
		$bg = _txt($_POST['bg']);
		//�������
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/������
				_num($ex[1]).' './/������
				_num($ex[2]).' './/�����
				_num($ex[3]);    //�����

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		//��������� ������
		$sql = "UPDATE `_block`
				SET `sa`='".$sa."',
					`width_auto`='".$width_auto."',
					`pos`='".$pos."',
					`bg`='".$bg."',
					`bor`='".$bor."'
				WHERE `id`=".$id;
		query($sql);

		//���������� ������ �������� � �����
		if($elem_id = _num(@$_POST['elem_id'])) {
			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$elem_id;
			if($elem = query_ass($sql)) {
				$mar = _txt($_POST['mar']);
				$font = _txt($_POST['font']);
				$color = _txt($_POST['color']);
				$size = _num($_POST['size']);
				if($size == 13)
					$size = 0;
				$sql = "UPDATE `_element`
						SET `mar`='".$mar."',
							`font`='".$font."',
							`color`='".$color."',
							`size`=".$size."
						WHERE `id`=".$elem_id;
				query($sql);
			}
		}

		jsonSuccess();
		break;
	case 'block_unit_gird'://������� ����� �� �����
		if(!$id = _num($_POST['id']))
			jsonError('������������ ID �����');

		$width = 1000;

		//��������� ������ �����
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('����� id'.$id.' �� ����������');

		if($block['obj_name'] == 'spisok') {//������� ���������� ��� �������� ������
			//��������� ������ �������� ����� ������
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block['obj_id'];
			if(!$iss = query_assoc($sql))
				jsonError('�������� ����� ������ id'.$block['obj_id'].' �� ����������');

			//��������� ��������, ������� �������� ������ (��� ������������� ������ � ���������)
			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=".$iss['id']."
					LIMIT 1";
			if(!$elem = query_assoc($sql))
				jsonError('�������� � ����� �� ����������');

			$ex = explode(' ', $elem['mar']);
			$width = floor(($iss['width'] - $ex[1] - $ex[3]) / 10) * 10;
		}

		if($block['obj_name'] == 'dialog') {//������� ���������� ��� ����������� ����
			$dialog = _dialogQuery($block['obj_id']);
			$width = $dialog['width'];
		}

		define('BLOCK_EDIT', 1);
		$send['html'] = utf8(_blockHtml($block['obj_name'], $block['obj_id'], $width, $id));
		$send['block'] = $block;
		$send['block_arr'] = _blockJsArr($block['obj_name'], $block['obj_id']);

		jsonSuccess($send);
		break;
}




function _blockChildCountSet($obj_name, $obj_id) {//���������� ���������� �������� ������
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return;

	//��������������� ��������� ���������� �������� ������
	$sql = "UPDATE `_block`
			SET `child_count`=0,
				`xx`=1,
				`xx_ids`=''
			WHERE `id` IN ("._idsGet($arr).")";
	query($sql);

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$countUpdate = array();
	foreach($arr as $id => $r) {
		if(!$r['parent_id'])
			continue;
		$countUpdate[] = '('.$r['parent_id'].','.count($child[$r['parent_id']]).')';
	}

	if($countUpdate) {
		$sql = "INSERT INTO `_block` (
					`id`,
					`child_count`
				) VALUES ".implode(',', $countUpdate)."
				ON DUPLICATE KEY UPDATE
					`child_count`=VALUES(`child_count`)";
		query($sql);
	}

	//������� ���������� ����� ������� �-������ � ������ Y-������
	$stroka = array();
	foreach($child as $block_id => $bl)
		foreach($bl as $r) {
			$stroka[$block_id][$r['y']][] = $r['id'];
		}

	$xxUpdate = array();
	foreach($stroka as $str)
		foreach($str as $y) {
			$c = count($y);
			if($c < 2)
				continue;
			foreach($y as $block_id) {
				$xx_ids = _idsAss($y);
				unset($xx_ids[$block_id]);
				$xxUpdate[] = "(".$block_id.",".$c.",'"._idsGet($xx_ids, 'key')."')";
			}
		}

	if($xxUpdate) {
		$sql = "INSERT INTO `_block` (
					`id`,
					`xx`,
					`xx_ids`
				) VALUES ".implode(',', $xxUpdate)."
				ON DUPLICATE KEY UPDATE
					`xx`=VALUES(`xx`),
					`xx_ids`=VALUES(`xx_ids`)";
		query($sql);
	}
}
function _blockChildCountAllUpdate() {//���������� ���������� �������� ������ � ���� �������� (������� �������)
	$sql = " SELECT DISTINCT `obj_name` FROM `_block`";
	foreach(query_array($sql) as $arr)
		foreach($arr as $name) {
			$sql = "SELECT DISTINCT `obj_id`
					FROM `_block`
					WHERE `obj_name`='".$name."'";
			foreach(_ids(query_ids($sql), 1) as $id)
				_blockChildCountSet($name, $id);
		}
}
