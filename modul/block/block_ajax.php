<?php
switch(@$_POST['op']) {
	case 'block_grid_on'://включение управления блоками
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');

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
	case 'block_grid_off'://выключение управления блоками
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$width = _num($_POST['width']))
			jsonError('Некорректная ширина');

		define('BLOCK_EDIT', 1);
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_change'://включение/выключение изменения ширины элементов
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$width = _num($_POST['width']))
			jsonError('Некорректная ширина');

		$on = _num($_POST['on']);

		define('ELEM_WIDTH_CHANGE', $on);
		define('BLOCK_EDIT', 1);

		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_save'://включение/выключение изменения ширины элементов
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');

		$width = _num($_POST['width']);

		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$elem = query_assoc($sql))
			jsonError('Элемента не существует');

		if(!_elemWidth($elem['dialog_id']))
			jsonError('У этого элемента не может настраиваться ширина');

		$sql = "UPDATE `_element`
				SET `width`=".$width."
				WHERE `id`=".$elem_id;
		query($sql);

		jsonSuccess();
		break;

	case 'block_grid_save'://сохранение данных блоков после редактирования
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$width = _num($_POST['width']))
			jsonError('Некорректная ширина');

		//проверка наличия родительского блока
		$parent = array();
		if($parent_id = _num(@$_POST['parent_id'])) {
			$sql = "SELECT *
					FROM `_block`
					WHERE `obj_name`='".$obj_name."'
					  AND `obj_id`=".$obj_id."
					  AND `id`=".$parent_id;
			if(!$parent = query_ass($sql))
				jsonError('Родительского блока не существует');

			switch($obj_name) {
				default:
				case 'page': $width = 1000; break;
				case 'spisok':
					$sql = "SELECT *
							FROM `_block`
							WHERE `id`=".$obj_id;
					if(!$block = query_assoc($sql))
						jsonError('Блока для элемента-списка не существует');

					$sql = "SELECT *
							FROM `_element`
							WHERE `block_id`=".$obj_id;
					if(!$elem = query_assoc($sql))
						jsonError('Элемента-списка не существует');

					//корректировка ширины с учётом отступов
					$ex = explode(' ', $elem['mar']);
					$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
					break;
				case 'dialog':
					$dialog = _dialogQuery($obj_id);
					$width = $dialog['width'];
					break;
			}
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

		//удаление удалённых блоков
		$sql = "DELETE FROM `_block`
				WHERE `obj_name`='".$obj_name."'
				  AND `obj_id`=".$obj_id."
				  AND `parent_id`=".$parent_id."
				  AND `id` NOT IN (".implode(',', $idsNotDel).")";
		query($sql);

		//удаление потомков удалённых блоков
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

		define('BLOCK_EDIT', 1);

		$send['level'] = utf8(_blockLevelChange($obj_name, $obj_id, $width));
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width));
		$send['block_arr'] = _blockJsArr($obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_unit_style_save'://применение стилей блока
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		$pos = _txt($_POST['pos']);
		$bg = _txt($_POST['bg']);
		//границы
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/сверху
				_num($ex[1]).' './/справа
				_num($ex[2]).' './/снизу
				_num($ex[3]);    //слева

		//получение данных блока
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//изменение стилей
		$sql = "UPDATE `_block`
				SET `pos`='".$pos."',
					`bg`='".$bg."',
					`bor`='".$bor."'
				WHERE `id`=".$id;
		query($sql);

		//сохранение стилей элемента в блоке
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
	case 'block_unit_gird'://деление блока на части
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		$width = 1000;

		//получение данных блока
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		if($block['obj_name'] == 'spisok') {//деление происходит для элемента списка
			//получение данных главного блока списка
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block['obj_id'];
			if(!$iss = query_assoc($sql))
				jsonError('Главного блока списка id'.$block['obj_id'].' не существует');

			//получение элемента, который содержит список (для корректировки ширины с отступами)
			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=".$iss['id']."
					LIMIT 1";
			if(!$elem = query_assoc($sql))
				jsonError('Элемента в блоке не существует');

			$ex = explode(' ', $elem['mar']);
			$width = floor(($iss['width'] - $ex[1] - $ex[3]) / 10) * 10;
		}

		if($block['obj_name'] == 'dialog') {//деление происходит для диалогового окна
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



