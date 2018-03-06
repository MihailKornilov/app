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
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width, 0, _pageSpisokUnit($obj_id, $obj_name)));
		$send['blk'] = _block($obj_name, $obj_id, 'block_arr');
		$send['elm'] = _block($obj_name, $obj_id, 'elem_utf8');

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

		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width, 0, _pageSpisokUnit($obj_id, $obj_name)));
		$send['elm'] = _block($obj_name, $obj_id, 'elem_utf8');

		jsonSuccess($send);
		break;
	case 'block_elem_width_save'://сохранение ширины элемента
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');

		$width = _num($_POST['width']);

		if(!$elem = _elemQuery($elem_id))
			jsonError('Элемента не существует');

		if(!_dialogParam($elem['dialog_id'], 'element_width'))
			jsonError('У этого элемента не может настраиваться ширина');

		$sql = "UPDATE `_element`
				SET `width`=".$width."
				WHERE `id`=".$elem_id;
		query($sql);

		_cache('clear', $elem['block']['obj_name'].'_'.$elem['block']['obj_id']);

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

		define('BLOCK_EDIT', 1);

		_cache('clear', $obj_name.'_'.$obj_id);
		$send['level'] = utf8(_blockLevelChange($obj_name, $obj_id, $width));
		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width,0, _pageSpisokUnit($obj_id, $obj_name)));
		$send['blk'] = _block($obj_name, $obj_id, 'block_arr');
		$send['elm'] = _block($obj_name, $obj_id, 'elem_utf8');

		jsonSuccess($send);
		break;
	case 'block_unit_style_save'://применение стилей блока
		if(!$block_id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		$sa = _num($_POST['sa']);
		$width_auto = _num($_POST['width_auto']);
		$pos = _txt($_POST['pos']);
		$bg = _txt($_POST['bg']);
		if(!$bg_ids = _ids($_POST['bg_ids'])) {
			if($bg == 'bg70')
				$bg = '';
			$bg_ids = '';
		}

		//границы
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/сверху
				_num($ex[1]).' './/справа
				_num($ex[2]).' './/снизу
				_num($ex[3]);    //слева

		//получение данных блока
		if(!$block = _blockQuery($block_id))
			jsonError('Блока id'.$block_id.' не существует');

		//изменение стилей
		$sql = "UPDATE `_block`
				SET `sa`='".$sa."',
					`width_auto`='".$width_auto."',
					`pos`='".$pos."',
					`bg`='".$bg."',
					`bg_ids`='".$bg_ids."',
					`bor`='".$bor."'
				WHERE `id`=".$block_id;
		query($sql);

		//сохранение стилей элемента в блоке
		if($elem_id = _num($_POST['elem_id']))
			if(_elemQuery($elem_id)) {
				$EL = $_POST['elem'];
				$mar = _txt($EL['mar']);
				$font = _txt($EL['font']);
				$color = _txt($EL['color']);
				$size = _num($EL['size']);
				if($size == 13)
					$size = 0;
				$url = _num($EL['url']);
				$sql = "UPDATE `_element`
						SET `mar`='".$mar."',
							`font`='".$font."',
							`color`='".$color."',
							`size`=".$size.",
							`url`=".$url."
						WHERE `id`=".$elem_id;
				query($sql);
			}

		_cache('clear', $block['obj_name'].'_'.$block['obj_id']);

		jsonSuccess();
		break;
	case 'block_unit_gird'://включение деления блока на подблоки
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		$width = 1000;

		//получение данных блока
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		foreach($block as $key => $v)
			if(preg_match(REGEXP_INTEGER, $v))
				$block[$key] = _num($v, 1);

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

		if($block['obj_name'] == 'dialog')//деление происходит для диалогового окна
			$width = _dialogParam($block['obj_id'], 'width');

		define('BLOCK_EDIT', 1);
		$send['block'] = $block;
		$send['html'] = utf8(_blockHtml($block['obj_name'], $block['obj_id'], $width, $id, _pageSpisokUnit($block['obj_id'], $block['obj_name'])));

		jsonSuccess($send);
		break;
	case 'block_choose_page'://выбор блоков на странице
		if(!$obj_name = _blockObj($_POST['obj_name']))
			jsonError('Несуществующее имя объекта');
		if(!$obj_id = _num($_POST['obj_id']))
			jsonError('Некорректный ID объекта');
		if(!$width = _num($_POST['width']))
			jsonError('Некорректная ширина');

		$sel = _idsAss($_POST['sel']);
		$deny = @$_POST['deny'];

		$unit = _pageSpisokUnit($obj_id, $obj_name);
		$unit += array(
			'choose' => 1,
			'choose_access' => array('block'=>1),
			'choose_sel' => $sel,       //ids ранее выбранных блоков
			'choose_deny' => empty($deny) ? array() : $deny
		);

		$send['html'] = utf8(_blockHtml($obj_name, $obj_id, $width, 0, $unit));

		jsonSuccess($send);
		break;
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
function _blockChildCountAllUpdate() {//обновление количества дочерних блоков у всех объектов (разовая функция)
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
