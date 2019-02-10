<?php
switch(@$_POST['op']) {
	case 'block_grid_on'://включение управления блоками
		if(!$obj_name = _blockName($_POST['obj_name']))
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

		$send['obj_name'] = $obj_name;
		$send['obj_id'] = $obj_id;
		$width = _blockObjWidth($obj_name, $obj_id);
		$send['width'] = $width;
		$send['html'] = _blockGrid($arr, $width);

		jsonSuccess($send);
		break;
	case 'block_grid_off'://выключение управления блоками
		if(!$obj_name = _blockName($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$level = _num($_POST['level']))
			jsonError('Некорректный уровень блоков');

		$blk_choose = _bool($_POST['blk_choose']);

		$prm = array(
			'blk_setup' => 1,
			'blk_choose' => $blk_choose,
			'blk_sel' => _ids(@$_POST['blk_sel']),
			'blk_level' => $level,
			'unit_get' => _pageUnitGet($obj_name, $obj_id)
		);

		$send['html'] = _blockHtml($obj_name, $obj_id,  $prm);
		$send['blk'] = _BE('block_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_change'://включение/выключение изменения ширины элементов
		if(!$obj_name = _blockName($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');

		$on = _num($_POST['on']);

		$prm = array(
			'blk_setup' => 1,
			'elm_width_change' => $on,
			'unit_get' => _pageUnitGet($obj_name, $obj_id)
		);
		$send['html'] = _blockHtml($obj_name, $obj_id,  $prm);
		$send['elm'] = _BE('elem_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_elem_width_save'://сохранение ширины элемента
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');

		$width = _num($_POST['width']);

		if(!$elem = _elemOne($elem_id))
			jsonError('Элемента не существует');

		if(!_dialogParam($elem['dialog_id'], 'element_width'))
			jsonError('У этого элемента не может настраиваться ширина');

		$sql = "UPDATE `_element`
				SET `width`=".$width."
				WHERE `id`=".$elem_id;
		query($sql);

		_BE('elem_clear');

		jsonSuccess();
		break;
	case 'block_grid_save'://сохранение данных блоков после редактирования
		if(!$obj_name = _blockName($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');

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
		}

		//получение id элементов, содержащихся в блоках (для последующего их удаления в удалённых блоках)
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
					USER_ID.
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

		//удаление элементов в удалённых блоках
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
						`user_id_add`
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
		_blockAppIdUpdate($obj_name, $obj_id);

		_BE('block_clear');
		_BE('elem_clear');
		_jsCache();

		$prm = array(
			'blk_setup' => 1,
			'unit_get' => _pageUnitGet($obj_name, $obj_id)
		);
		$send['level'] = _blockLevelChange($obj_name, $obj_id);
		$send['html'] = _blockHtml($obj_name, $obj_id, $prm);
		$send['blk'] = _BE('block_arr', $obj_name, $obj_id);
		$send['elm'] = _BE('elem_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_unit_style_save'://применение стилей блока
		if(!$block_id = _num($_POST['id']))
			jsonError('Некорректный ID блока');
		if(!$block = _blockOne($block_id))
			jsonError('Блока id'.$block_id.' не существует');

		$sa = _bool($_POST['sa']);
		$width_auto = _num($_POST['width_auto']);
		$pos = _txt($_POST['pos']);
		$bg = _txt($_POST['bg']);
		$hidden = _num($_POST['hidden']);

		//границы
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/сверху
				_num($ex[1]).' './/справа
				_num($ex[2]).' './/снизу
				_num($ex[3]);    //слева


		//сохранение данных блока
		$sql = "UPDATE `_block`
				SET `sa`='".$sa."',
					`width_auto`='".$width_auto."',
					`pos`='".$pos."',
					`bg`='".$bg."',
					`bor`='".$bor."',
					`hidden`=".$hidden."
				WHERE `id`=".$block_id;
		query($sql);

		_BE('block_clear');

		$send = array();

		//сохранение стилей элемента в блоке
		if($elem_id = _num($_POST['elem_id']))
			if(_elemOne($elem_id)) {
				$EL = $_POST['elem'];
				$mar = _txt($EL['mar']);
				$font = _txt($EL['font']);
				$color = _txt($EL['color']);
				$size = _num($EL['size']);
				if($size == 13)
					$size = 0;
				$width = _num($EL['width']);
				$num_7 = _num($EL['num_7']);//ограничение высоты фото [60]
				$num_8 = _num($EL['num_8']);//закруглённые углы [60]
				$sql = "UPDATE `_element`
						SET `mar`='".$mar."',
							`font`='".$font."',
							`color`='".$color."',
							`size`=".$size.",
							`width`=".$width.",
							`num_7`=".$num_7.",
							`num_8`=".$num_8."
						WHERE `id`=".$elem_id;
				query($sql);

				_BE('elem_clear');

				$send['elem_js'] = _jsCacheElemOne($elem_id);
			}

		_jsCache();

		jsonSuccess($send);
		break;
	case 'block_unit_gird'://включение деления блока на подблоки
		if(!$block_id = _num($_POST['id']))
			jsonError('Некорректный ID блока');
		if(!$block = _blockOne($block_id))
			jsonError('Блока id'.$block_id.' не существует');

		$prm = array(
			'blk_setup' => 1,
			'unit_get' => _pageUnitGet($block['obj_name'], $block['obj_id'])
		);

		$send['obj_name'] = $block['obj_name'];
		$send['obj_id'] = $block['obj_id'];
		$send['width'] = $block['width'];
		$send['html'] =
			_blockHtml(
				$block['obj_name'],
				$block['obj_id'],
				$prm,
				$block_id
			);

		jsonSuccess($send);
		break;

	case 'block_choose_level_change'://переключение уровней во время выбора блоков
		if(!$block_id = _num($_POST['block_id']))
			jsonError('Некорректный ID блока');
		if(!$level = _num($_POST['level']))
			jsonError('Некорректный уровень блоков');
		if(!$BL = _blockOne($block_id))
			jsonError('Блока id'.$block_id.' не существует');

		$prm = array(
			'blk_choose' => 1,
			'blk_level' => $level,
			'unit_get' => _pageUnitGet($BL['obj_name'], $BL['obj_id'])
		);

		$send['html'] = _blockHtml($BL['obj_name'], $BL['obj_id'], $prm);

		jsonSuccess($send);
		break;
	case 'block_choose_paste_0_copy'://вставка выбранных блоков на нулевой уровень - копирование
		if(!$obj_name = _blockName($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id` IN (".$ids.")
				ORDER BY `parent_id`,`y`,`x`";
		if(!$BLK = query_arr($sql))
			jsonError('Выбранные блоки не существуют');


		//определение координаты Y текущего объекта, с которой нужно начинать вставку блоков
		$Y_START = 0;
		foreach(_BE('block_arr', $obj_name, $obj_id) as $r) {
			if($r['parent_id'])
				continue;
			if($Y_START < ($r['y'] + $r['h']))
				$Y_START = $r['y'] + $r['h'];
		}


		//определение сдвига у переносимых блоков по X
		$cX = 1000;
		$cY = 1000;
		foreach($BLK as $id => $r) {
			if($cX > $r['x'])
				$cX = $r['x'];
			if($cY > $r['y'])
				$cY = $r['y'];
		}


		//получение всех дочерних блоков
		$PASTE = _blockChildGet($BLK);


		$pasteIds = array();
		foreach($PASTE as $id => $r) {
			//коррекция координат, если это верхний уровень
			if(isset($BLK[$id])) {
				$r['x'] -= $cX;
				$r['y'] = $r['y'] - $cY + $Y_START;
			}
			$sql = "INSERT INTO `_block` (
						`obj_name`,
						`obj_id`,
						`x`,
						`y`,
						`w`,
						`h`,
						`width`,
						`width_auto`,
						`height`,
						`pos`,
						`bg`,
						`bor`,
						`hidden`,
						`user_id_add`
					) VALUES (
						'".$obj_name."',
						".$obj_id.",
						".$r['x'].",
						".$r['y'].",
						".$r['w'].",
						".$r['h'].",
						".$r['width'].",
						".$r['width_auto'].",
						".$r['height'].",
						'".$r['pos']."',
						'".$r['bg']."',
						'".$r['bor']."',
						'".$r['hidden']."',
						".USER_ID."
					)";
			$pasteIds[$id] = query_id($sql);
		}

		//восстановление id родителей
		foreach($PASTE as $id => $r) {
			if(isset($BLK[$id]))
				continue;

			$sql = "UPDATE `_block`
					SET `parent_id`=".$pasteIds[$r['parent_id']]."
					WHERE `id`=".$pasteIds[$id];
			query($sql);
		}

		_blockChildCountSet($obj_name, $obj_id);
		_blockAppIdUpdate($obj_name, $obj_id);
		_blockElementCopy($PASTE, $pasteIds);

		_BE('elem_clear');
		_BE('block_clear');
		_BE('dialog_clear');
		_jsCache();

		$send['blk'] = _BE('block_arr', $obj_name, $obj_id);
		$send['elm'] = _BE('elem_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_choose_paste_0_move'://вставка выбранных блоков на нулевой уровень - перенос
		if(!$obj_name = _blockName($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id` IN (".$ids.")
				ORDER BY `parent_id`,`y`,`x`";
		if(!$BLK = query_arr($sql))
			jsonError('Выбранные блоки не существуют');


		//определение координаты Y текущего объекта, с которой нужно начинать вставку блоков
		$Y_START = 0;
		foreach(_BE('block_arr', $obj_name, $obj_id) as $r) {
			if($r['parent_id'])
				continue;
			if($Y_START < ($r['y'] + $r['h']))
				$Y_START = $r['y'] + $r['h'];
		}


		//определение сдвига у переносимых блоков по X
		$cX = 1000;
		$cY = 1000;
		foreach($BLK as $id => $r) {
			if($cX > $r['x'])
				$cX = $r['x'];
			if($cY > $r['y'])
				$cY = $r['y'];
		}


		//получение всех дочерних блоков
		$PASTE = _blockChildGet($BLK);


		foreach($BLK as $id => $r) {
			//коррекция координат
			$r['x'] -= $cX;
			$r['y'] = $r['y'] - $cY + $Y_START;
			$sql = "UPDATE `_block`
					SET `x`=".$r['x'].",
						`y`=".$r['y']."
					WHERE `id`=".$id;
			query($sql);
		}


		//изменение объекта у всех перенесённых блоков
		$sql = "UPDATE `_block`
				SET `obj_name`='".$obj_name."',
					`obj_id`=".$obj_id."
				WHERE `id` IN ("._idsGet($PASTE).")";
		query($sql);


		_blockChildCountSet($obj_name, $obj_id);
		_blockAppIdUpdate($obj_name, $obj_id);

		//изменение приложения у всех перенесённых элементов
		$sql = "UPDATE `_element` `el`
				SET `app_id`=(
					SELECT `app_id`
					FROM `_block`
					WHERE `id`=`el`.`block_id`
				)
				WHERE `block_id` IN ("._idsGet($PASTE).")";
		query($sql);


		_BE('elem_clear');
		_BE('block_clear');
		_BE('dialog_clear');
		_jsCache();

		$send['blk'] = _BE('block_arr', $obj_name, $obj_id);
		$send['elm'] = _BE('elem_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_choose_copy'://вставка блоков в выбранный блок - копирование
		if(!$parent_id = _num($_POST['parent_id']))
			jsonError('Не корректный ID блока, в который нужно делать перенос');
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');
		if(!$BL = _blockOne($parent_id))
			jsonError('Блок не существует, в который нужно делать перенос');
		if($BL['elem_id'])
			jsonError('Блок не должен содержать элемент');

		$obj_name = $BL['obj_name'];
		$obj_id = $BL['obj_id'];

		foreach(_ids($ids, 'arr') as $id) {
			if($id == $parent_id)
				jsonError('Невозможно вставить блок в самого себя');

			$ass = _BE('block_child_ids', $id);
			if(isset($ass[$parent_id]))
				jsonError('Невозможна вставка в дочерний блок одного из переносимых блоков');
			if(!$bl = _blockOne($id))
				jsonError('Переносимого блока '.$id.' не существует');
			if($bl['x'] + $bl['w'] > $BL['w'])
				jsonError('Общая ширина переносимых блоков не может превышать ширину выбранного блока');
		}

		$sql = "SELECT *
				FROM `_block`
				WHERE `id` IN (".$ids.")
				ORDER BY `parent_id`,`y`,`x`";
		if(!$BLK = query_arr($sql))
			jsonError('Выбранные блоки не существуют');


		//получение всех дочерних блоков
		$PASTE = _blockChildGet($BLK);

		$pasteIds = array();
		foreach($PASTE as $id => $r) {
			$sql = "INSERT INTO `_block` (
						`obj_name`,
						`obj_id`,
						`x`,
						`y`,
						`w`,
						`h`,
						`width`,
						`width_auto`,
						`height`,
						`pos`,
						`bg`,
						`bor`,
						`hidden`,
						`user_id_add`
					) VALUES (
						'".$obj_name."',
						".$obj_id.",
						".$r['x'].",
						".$r['y'].",
						".$r['w'].",
						".$r['h'].",
						".$r['width'].",
						".$r['width_auto'].",
						".$r['height'].",
						'".$r['pos']."',
						'".$r['bg']."',
						'".$r['bor']."',
						'".$r['hidden']."',
						".USER_ID."
					)";
			$pasteIds[$id] = query_id($sql);
		}

		//восстановление id родителей
		foreach($PASTE as $id => $r) {
			$pid = isset($BLK[$id]) ? $parent_id : $pasteIds[$r['parent_id']];
			$sql = "UPDATE `_block`
					SET `parent_id`=".$pid."
					WHERE `id`=".$pasteIds[$id];
			query($sql);
		}

		_blockChildCountSet($obj_name, $obj_id);
		_blockAppIdUpdate($obj_name, $obj_id);
		_blockElementCopy($PASTE, $pasteIds);

		_BE('elem_clear');
		_BE('block_clear');
		_BE('dialog_clear');
		_jsCache();

		$send['blk'] = _BE('block_arr', $obj_name, $obj_id);
		$send['elm'] = _BE('elem_arr', $obj_name, $obj_id);

		jsonSuccess($send);
		break;
	case 'block_choose_move'://вставка блоков в выбранный блок - перенос
		if(!$parent_id = _num($_POST['parent_id']))
			jsonError('Не корректный ID блока, в который нужно делать перенос');
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');
		if(!$BL = _blockOne($parent_id))
			jsonError('Блок не существует, в который нужно делать перенос');
		if($BL['elem_id'])
			jsonError('Блок не должен содержать элемент');

		foreach(_ids($ids, 'arr') as $id) {
			if($id == $parent_id)
				jsonError('Невозможно вставить блок в самого себя');

			$ass = _BE('block_child_ids', $id);
			if(isset($ass[$parent_id]))
				jsonError('Невозможна вставка в дочерний блок одного из переносимых блоков');
			if(!$bl = _blockOne($id))
				jsonError('Переносимого блока '.$id.' не существует');
			if($bl['x'] + $bl['w'] > $BL['w'])
				jsonError('Ширина переносимого блока не может превышать ширину выбранного блока');
		}

		$sql = "UPDATE `_block`
				SET `parent_id`=".$parent_id.",
					`obj_name`='".$BL['obj_name']."',
					`obj_id`=".$BL['obj_id']."
				WHERE `id` IN (".$ids.")";
		query($sql);

		_blockChildCountSet($BL['obj_name'], $BL['obj_id']);
		_blockAppIdUpdate($BL['obj_name'], $BL['obj_id']);
		_BE('elem_clear');
		_BE('block_clear');
		_BE('dialog_clear');
		_jsCache();

		jsonSuccess();
		break;

/*
	case 'block_choose_clone'://клонирование блоков
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id` IN (".$ids.")
				ORDER BY `parent_id`,`y`,`x`";
		if(!$arr = query_arr($sql))
			jsonError('Выбранные блоки не существуют');

		$key0 = key($arr);
		$obj_name = $arr[$key0]['obj_name'];
		$obj_id = $arr[$key0]['obj_id'];




		$yCur = array();
		$hCur = array();//текущая высота во время вставки блоков. Высота изменяется, если появляется блок из новой строки
		$cloneIds = array();
		foreach($arr as $id => $r) {
			$pid = $r['parent_id'];
			if(!isset($yCur[$pid])) {
				$yCur[$pid] = $r['y'];
				$hCur[$pid] = $r['h'];
			}
			if($yCur[$pid] != $r['y']) {
				$yCur[$pid] = $r['y'];
				$hMax[$pid] += $hCur[$pid];
				$hCur[$pid] = $r['h'];
			}
			$sql = "INSERT INTO `_block` (
						`app_id`,
						`parent_id`,
						`obj_name`,
						`obj_id`,
						`x`,
						`y`,
						`w`,
						`h`,
						`width`,
						`width_auto`,
						`height`,
						`bg`,
						`bor`,
						`hidden`,
						`user_id_add`
					) VALUES (
						".APP_ID.",
						".$pid.",
						'".$r['obj_name']."',
						".$r['obj_id'].",
						".$r['x'].",
						".$hMax[$pid].",
						".$r['w'].",
						".$r['h'].",
						".$r['width'].",
						".$r['width_auto'].",
						".$r['height'].",
						'".$r['bg']."',
						'".$r['bor']."',
						'".$r['hidden']."',
						".USER_ID."
					)";
			$cloneIds[$id] = query_id($sql);
		}

		//внесение дочерних блоков
		foreach($cloneIds as $id_old => $id_new)
			_blockChildClone($id_old, $id_new);

		_blockChildCountSet($obj_name, $obj_id);
		_BE('block_clear');
		_jsCache();

		jsonSuccess();
		break;
*/
/*
	case 'block_choose_del'://удаление выбранных блоков
		if(!$ids = _ids($_POST['ids']))
			jsonError('Блоки не выбраны');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id` IN (".$ids.")
				ORDER BY `parent_id`,`y`,`x`";
		if(!$arr = query_arr($sql))
			jsonError('Выбранные блоки не существуют');

		$key0 = key($arr);
		$obj_name = $arr[$key0]['obj_name'];
		$obj_id = $arr[$key0]['obj_id'];

		//получение всех дочерних блоков у выбранных
		$ass = array();
		foreach(_ids($ids, 'arr') as $id)
			$ass += _BE('block_child_ids', $id);

		$ass += _idsAss($ids);
		$blkIds = _idsGet($ass, 'key');

		//все элементы, содержащиеся в блоках
		$sql = "SELECT `id`
				FROM `_element`
				WHERE `block_id` IN (".$blkIds.")";
		if($elmIds = query_ids($sql)) {
			//удаление дочерних элементов
			$sql = "DELETE FROM `_element`
					WHERE `parent_id` IN (".$elmIds.")";
			query($sql);

			//удаление элементов, являющихся списками-шаблонами
			$sql = "SELECT `id`
					FROM `_block`
					WHERE `obj_name`='spisok'
					  AND `obj_id` IN (".$elmIds.")";
			if($spisokIds = query_ids($sql)) {
				$sql = "DELETE FROM `_element`
						WHERE `block_id` IN (".$spisokIds.")";
				query($sql);

				$sql = "DELETE FROM `_block`
						WHERE `id` IN (".$spisokIds.")";
				query($sql);
			}

			$sql = "DELETE FROM `_element`
					WHERE `parent_id` IN (".$elmIds.")";
			query($sql);

			//удаление функций у элементов
			$sql = "DELETE FROM `_action`
					WHERE `element_id` IN (".$elmIds.")";
			query($sql);

			//удаление фильтров
			$sql = "DELETE FROM `_user_spisok_filter`
					WHERE `element_id_filter` IN (".$elmIds.")";
			query($sql);
		}

		//удаление самих элементов
		$sql = "DELETE FROM `_element`
				WHERE `id` IN (".$elmIds.")";
		query($sql);

		//удаление блоков
		$sql = "DELETE FROM `_block`
				WHERE `id` IN (".$blkIds.")";
		query($sql);

		_blockChildCountSet($obj_name, $obj_id);
		_BE('elem_clear');
		_BE('block_clear');
		_jsCache();

		jsonSuccess();
		break;
*/
}

function _blockChildCountSet($obj_name, $obj_id) {//обновление количества дочерних блоков
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return;

	//предварительное обнуление количества дочерних блоков
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

	//подсчёт количества рядом стоящих Х-блоков в каждой Y-строке
	$stroka = array();
	foreach($child as $block_id => $bl)
		foreach($bl as $r)
			$stroka[$block_id][$r['y']][] = $r['id'];

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

function _blockChildGet($BLK) {//получение всех дочерних блоков в выбранных блоках
	if(empty($BLK))
		return array();

	foreach($BLK as $bl) {
		if(!$bl['child_count'])
			continue;

		$sql = "SELECT *
				FROM `_block`
				WHERE `parent_id`=".$bl['id'];
		if(!$arr = query_arr($sql))
			continue;

		$arr = _blockChildGet($arr);

		$BLK += $arr;
	}

	return $BLK;
}
function _blockLevelGet($BLK, $level=0) {//определение верхнего уровня todo пока не пригодилась
	$ids = array();
	foreach($BLK as $id => $bl) {
		if(!$bl['parent_id'])
			return $level;
		$ids[] = $bl['parent_id'];
	}

	$ids = array_unique($ids);

	$sql = "SELECT *
			FROM `_block`
			WHERE `id` IN (".implode(',', $ids).")";
	if(!$arr = query_arr($sql))
		return $level;

	return _blockLevelGet($arr, $level+1);
}

/*
function _blockChildCountAllUpdate() {//обновление количества дочерних блоков у всех объектов (разовая функция)
	$sql = " SELECT DISTINCT `obj_name` FROM `_block`";
	foreach(query_array($sql) as $arr)
		foreach($arr as $name) {
			$sql = "SELECT DISTINCT `obj_id`
					FROM `_block`
					WHERE `obj_name`='".$name."'";
			foreach(_ids(query_ids($sql), 'arr') as $id)
				_blockChildCountSet($name, $id);
		}
}
*/
function _blockAppIdUpdate($obj_name, $obj_id) {//обновление id приложения в блоках конкретного объекта
	$app_id = 0;
	switch($obj_name) {
		case 'page':
			$sql = "SELECT *
					FROM `_page`
					WHERE `id`=".$obj_id;
			if($page = query_assoc($sql))
				$app_id = $page['app_id'];
			break;
		case 'dialog':
		case 'dialog_del':
			$sql = "SELECT *
					FROM `_dialog`
					WHERE `id`=".$obj_id;
			if($dlg = query_assoc($sql))
				$app_id = $dlg['app_id'];
			break;
		case 'spisok':
			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$obj_id;
			if(!$elm = query_assoc($sql))
				jsonError('Несуществующий элемент '.$obj_id.', размещающий список.');
			if(!$block_id = $elm['block_id'])
				jsonError('Отсутствует блок, размещающий элемент со списком.');
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block_id;
			if(!$blk = query_assoc($sql))
				jsonError('Несуществующий блок '.$block_id.', размещающий элемент со списком.');

			switch($blk['obj_name']) {
				case 'page':
					$sql = "SELECT *
							FROM `_page`
							WHERE `id`=".$blk['obj_id'];
					if($page = query_assoc($sql))
						$app_id = $page['app_id'];
					break;
				case 'dialog':
				case 'dialog_del':
					$sql = "SELECT *
							FROM `_dialog`
							WHERE `id`=".$blk['obj_id'];
					if($dlg = query_assoc($sql))
						$app_id = $dlg['app_id'];
					break;
			}
			break;
	}

	$sql = "UPDATE `_block`
			SET `app_id`=".$app_id."
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	query($sql);
}
function _blockChildClone($id_old, $id_new) {//внесение дочерних блоков в скопированные
	$sql = "SELECT *
			FROM `_block`
			WHERE `parent_id`=".$id_old."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $r) {
		$sql = "INSERT INTO `_block` (
					`app_id`,
					`parent_id`,
					`obj_name`,
					`obj_id`,
					`x`,
					`y`,
					`w`,
					`h`,
					`width`,
					`width_auto`,
					`height`,
					`bg`,
					`bor`,
					`hidden`,
					`user_id_add`
				) VALUES (
					".APP_ID.",
					".$id_new.",
					'".$r['obj_name']."',
					".$r['obj_id'].",
					".$r['x'].",
					".$r['y'].",
					".$r['w'].",
					".$r['h'].",
					".$r['width'].",
					".$r['width_auto'].",
					".$r['height'].",
					'".$r['bg']."',
					'".$r['bor']."',
					'".$r['hidden']."',
					".USER_ID."
				)";
		$block_id = query_id($sql);

		if($r['child_count'])
			_blockChildClone($r['id'], $block_id);
	}
}
function _blockElementCopy($PASTE, $pasteIds) {
	//копирование элементов
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($PASTE).")";
	foreach(query_arr($sql) as $r) {
		$block_id = $pasteIds[$r['block_id']];

		//копировать можно пока не все элементы
		switch($r['dialog_id']) {
			case 1:
			case 3:
			case 5:
			case 8:
			case 9:
			case 10:
			case 21:
			case 35:
			case 57://меню переключения блоков
			case 60:
			case 300:
				$sql = "INSERT INTO `_element` (
							`app_id`,
							`block_id`,
							`dialog_id`,
							`col`,
							`name`,
							`req`,
							`req_msg`,
							`nosel`,
							`focus`,
							`width`,
							`color`,
							`font`,
							`size`,
							`mar`,
							`txt_1`,
							`txt_2`,
							`txt_3`,
							`txt_4`,
							`txt_5`,
							`txt_6`,
							`txt_7`,
							`txt_8`,
							`txt_9`,
							`txt_10`,
							`num_1`,
							`num_2`,
							`num_3`,
							`num_4`,
							`num_5`,
							`num_6`,
							`num_7`,
							`num_8`,
							`num_9`,
							`num_10`,
							`def`,
							`sort`,
							`user_id_add`
						) VALUES (
							(SELECT `app_id` FROM `_block` WHERE `id`=".$block_id."),
							".$block_id.",
							".$r['dialog_id'].",
							'".$r['col']."',
							'".$r['name']."',
							".$r['req'].",
							'".$r['req_msg']."',
							".$r['nosel'].",
							".$r['focus'].",
							".$r['width'].",
							'".$r['color']."',
							'".$r['font']."',
							".$r['size'].",
							'".$r['mar']."',
							'".addslashes($r['txt_1'])."',
							'".addslashes($r['txt_2'])."',
							'".addslashes($r['txt_3'])."',
							'".addslashes($r['txt_4'])."',
							'".addslashes($r['txt_5'])."',
							'".addslashes($r['txt_6'])."',
							'".addslashes($r['txt_7'])."',
							'".addslashes($r['txt_8'])."',
							'".addslashes($r['txt_9'])."',
							'".addslashes($r['txt_10'])."',
							".$r['num_1'].",
							".$r['num_2'].",
							".$r['num_3'].",
							".$r['num_4'].",
							".$r['num_5'].",
							".$r['num_6'].",
							".$r['num_7'].",
							".$r['num_8'].",
							".$r['num_9'].",
							".$r['num_10'].",
							".$r['def'].",
							".$r['sort'].",
							".USER_ID."
						)";
				$parent_id = query_id($sql);
		}

		//копирование дочерних элементов
		switch($r['dialog_id']) {
			case 57://меню переключения блоков
				$sql = "SELECT *
						FROM `_element`
						WHERE `parent_id`=".$r['id']."
						ORDER BY `sort`";
				foreach(query_arr($sql) as $el) {
					$blk = array();
					foreach(_ids($el['txt_2'], 'arr') as $bid) {
						if(empty($pasteIds[$bid]))
							continue;
						$blk[] = $pasteIds[$bid];
					}
					$sql = "INSERT INTO `_element` (
								`app_id`,
								`parent_id`,
								`txt_1`,
								`txt_2`,
								`def`,
								`sort`,
								`user_id_add`
							) VALUES (
								(SELECT `app_id` FROM `_block` WHERE `id`=".$block_id."),
								".$parent_id.",
								'".addslashes($el['txt_1'])."',
								'".implode(',', $blk)."',
								".$el['def'].",
								".$el['sort'].",
								".USER_ID."
							)";
					query($sql);
				}
				//установка нового значения по умолчанию
				$sql = "SELECT `id`
						FROM `_element`
						WHERE `parent_id`=".$parent_id."
						  AND `def`
						LIMIT 1";
				$def = _num(query_value($sql));

				$sql = "UPDATE `_element`
						SET `def`=".$def."
						WHERE `id`=".$parent_id;
				query($sql);
				break;
		}

	}
}


