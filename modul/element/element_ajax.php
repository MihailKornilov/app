<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
		$dialog = array(
			'id' => 0,
			'sa' => 0,
			'width' => 500,

			'head_insert' => 'Внесение новой записи',
			'button_insert_submit' => 'Внести',
			'button_insert_cancel' => 'Отмена',

			'head_edit' => 'Сохранение записи',
			'button_edit_submit' => 'Сохранить',
			'button_edit_cancel' => 'Отмена',

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
			1 => 'Заголовок, кнопки',
			2 => 'Компоненты',
  		    3 => 'Действие',
//  		4 => 'Функции',
//			5 => 'Отображение полей',
			9 => '<b class="red">SA</b>'
		);

		$action = array(
			1 => 'Обновить страницу',
			2 => 'Перейти на страницу'
		);

		if(!SA) {
			unset($menu[9]);

			if($dialog['menu_edit_last'] == 9)
				$dialog['menu_edit_last'] = 2;
		}

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1">'.
				'<div class="pad10">'.
					'<div class="hd2">Внесение новой записи</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Заголовок:'.
							'<td><input type="text" id="head_insert" class="w250" maxlength="200" placeholder="название диалогового окна - новая запись" value="'.$dialog['head_insert'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>внесения</b>:<td><input type="text" id="button_insert_submit" class="w150" maxlength="100" value="'.$dialog['button_insert_submit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>отмены</b>:<td><input type="text" id="button_insert_cancel" class="w150" maxlength="100" value="'.$dialog['button_insert_cancel'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-ffd line-t1 pad10">'.
					'<div class="hd2">Редактирование записи</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Заголовок:'.
							'<td><input type="text" id="head_edit" class="w250" maxlength="200" placeholder="название диалогового окна - редактирование" value="'.$dialog['head_edit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>сохранения</b>:<td><input type="text" id="button_edit_submit" class="w150" maxlength="100" value="'.$dialog['button_edit_submit'].'" />'.
						'<tr><td class="label r">Текст кнопки <b>отмены</b>:<td><input type="text" id="button_edit_cancel" class="w150" maxlength="100" value="'.$dialog['button_edit_cancel'].'" />'.
					'</table>'.
				'</div>'.
				'<div class="bg-gr2 line-t1 pad10">'.
					'<div class="hd2">Служебное</div>'.
					'<table class="bs10">'.
						'<tr><td class="label w175 r">Может быть списком:'.
							'<td><input type="hidden" id="spisok_on" value="'.$dialog['spisok_on'].'" />'.
						'<tr id="tr_spisok_name" class="'.($dialog['spisok_on'] ? '' : 'dn').'">'.
							'<td class="label r">Имя списка:'.
							'<td><input type="text" id="spisok_name" class="w200" maxlength="100" value="'.$dialog['spisok_name'].'" />'.
					'</table>'.
				'</div>'.
			'</div>'.

			//Компоненты
			'<div class="dialog-menu-2">'.
				'<div class="pt10 mb10 ml10 mr10 prel">'.
					'<dl id="dialog-base" class="_sort pad5" val="_dialog_component">'.
						'<div id="label-w-change"></div>'.
						_dialogComponentSpisok($dialog_id, 'html_edit').
					'</dl>'.
				'</div>'.
				'<div class="pad20 center bg-ffd line-t1">'.
					'<button class="vk green" onclick="_dialogComponentEdit()">Новый компонент</button>'.
				'</div>'.
			'</div>'.

			//Действие
			'<div class="dialog-menu-3">'.
				'<div class="_info mar20">'.
					'Дальнейшее действие, которое происходит после внесения или сохранения записи.'.
				'</div>'.
				'<table class="bs10 mb20">'.
					'<tr><td class="label r w100">Действие:'.
						'<td><input type="hidden" id="action_id" value="'.$dialog['action_id'].'" />'.
					'<tr class="td-action-page'._dn($dialog['action_id'] == 2).'">'.
						'<td class="label r">Страница:'.
						'<td><input type="hidden" id="action_page_id" value="'.$dialog['action_page_id'].'" />'.
				'</table>'.
			'</div>'.

			//Связки
//			'<div class="dialog-menu-3">Связки в раздумке</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pt20 pb20">'.
				'<table class="bs10">'.
					'<tr><td class="label w175 r"><div class="red">ID диалога:</div>'.
						'<td>'.$dialog['id'].
					'<tr><td class="label r"><div class="red">Таблица в базе:</div>'.
						'<td><input type="text" id="base_table" class="w230" maxlength="30" value="'.$dialog['base_table'].'" />'.
					//доступность диалога. На основании app_id. По умолчанию 0 - недоступен всем.
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
	case 'dialog_add'://создание нового диалогового окна
		$send['dialog_id'] = _dialogUpdate(0);
		jsonSuccess($send);
		break;
	case 'dialog_edit'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		$send['dialog_id'] = _dialogUpdate($dialog_id);

		jsonSuccess($send);
		break;

	case 'dialog_spisok_on_col_load'://получение названий колонок конкретного списка из компонентов диалога
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалога');

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');

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

	case 'dialog_open_load'://получение данных для диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');

		$page_id = _num($_POST['page_id']);

		$data = array();
		if($unit_id = _num($_POST['unit_id'])) {
			$sql = "SELECT *
					FROM `".$dialog['base_table']."`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$unit_id;
			if(!$data = query_assoc($sql))
				jsonError('Записи не существует');
			if(@$data['sa'] && !SA)
				jsonError('Нет доступа');
			if(@$data['deleted'])
				jsonError('Запись была удалена');
		}

		//8:связка
		if($unit_id_dub = _num(@$_POST['unit_id_dub'])) {
			foreach($dialog['component'] as $r)
				if($r['type_id'] == 8)
					$data[$r['col_name']] = $unit_id_dub;
		}

		$html = '<div class="mt5 mb5">'._dialogComponentSpisok($dialog_id, 'html', $data, $page_id).'</div>';

		$send['iconEdit'] = SA || $dialog['app_id'] == APP_ID ? 'show' : 'hide';//права для редактирования диалога
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

	case 'page_sort'://сортировка страниц
		$arr = $_POST['arr'];
		if(!is_array($arr))
			jsonError('Не является массивом');

		$update = array();
		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;
			$parent_id = _num($r['parent_id']);
			$update[] = "(".$id.",".$parent_id.",".$n.")";
		}

		if(empty($update))
			jsonError('Нет данных для обновления');

		$sql = "INSERT INTO `_page` (
					`id`,
					`parent_id`,
					`sort`
				) VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`parent_id`=VALUES(`parent_id`),
					`sort`=VALUES(`sort`)";
		query($sql);

		jsonSuccess();
		break;

	case 'page_block_add'://добавление блока на страницу
		if(!$page_id = _num($_POST['page_id']))
			jsonError('Некорректный ID страницы');

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
	case 'page_block_div'://деление блока на две части
		if(!$block_id = _num($_POST['block_id']))
			jsonError('Некорректный ID блока');

		//получение данных блока
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$block_id.' не существует');


		//если блок является основным, то он становится дочерним, а над ним вносится блок, который будет родителем над текущим и над новым
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

		//изменение значений сортировки, чтобы не повторялись
		$sql = "UPDATE `_page_block`
				SET `sort`=`sort`+1
				WHERE `parent_id`=".$parent_id."
				  AND `sort`>".$block['sort'];
		query($sql);

		//внесение нового дочернего блока
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

		//определение блока, из которого нужно вычесть размер 100px
		if($block['w'] < 200) {
			$sql = "SELECT `id`
					FROM `_page_block`
					WHERE `parent_id`=".$parent_id."
					ORDER BY `w` DESC
					LIMIT 1";
			$block_id = query_value($sql);
		}

		//убавление размера разделяемого блока
		$sql = "UPDATE `_page_block`
				SET `w`=`w`-100
				WHERE `id`=".$block_id;
		query($sql);

		$send['html'] = utf8(_page_show($block['page_id'], 1));

		jsonSuccess($send);
		break;
	case 'page_block_resize'://изменение размера блока
		if(!$block_id = _num($_POST['block_id']))
			jsonError('Некорректный ID блока');
		if(!$w = _num($_POST['w']))
			jsonError('Некорректная длина блока');

		//получение данных блока
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$block_id.' не существует');

		//установка новой длины блока
		$sql = "UPDATE `_page_block`
				SET `w`=".$w."
				WHERE `id`=".$block_id;
		query($sql);

		//изменение длины следующего блока на уменьшенную разницу
		$sql = "UPDATE `_page_block`
				SET `w`=`w`+(".($block['w'] - $w).")
				WHERE `parent_id`=".$block['parent_id']."
				  AND `sort`>".$block['sort']."
				ORDER BY `sort`
				LIMIT 1";
		query($sql);

		$send['html'] = utf8(_page_show($block['page_id'], 1));
		
		jsonSuccess($send);
		break;
	case 'page_block_style_load'://получение стилей блока для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		//получение данных блока
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//отступы
		$ex = explode(' ', $block['pad']);
		$pad =
		'<div class="pas-block-pad mt10 center">'.

			'<div class="fs15 color-555 mb5">сверху</div>'.
			'<button class="vk small cancel mt1 mr3 minus">«</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[0] ? 'bg-dfd' : 'pale').'">'.$ex[0].'</div>'.
			'<button class="vk small cancel mt1 plus">»</button>'.

			'<table class="w100p ml10 mt30 mb30">'.
				'<tr><td class="w200">'.
						'<div class="dib fs15 color-555 mr5">слева</div>'.
						'<button class="vk small cancel mt1 mr3 minus">«</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[3] ? 'bg-dfd' : 'pale').'">'.$ex[3].'</div>'.
						'<button class="vk small cancel mt1 plus">»</button>'.
					'<td>'.
						'<button class="vk small cancel mt1 mr3 minus">«</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[1] ? 'bg-dfd' : 'pale').'">'.$ex[1].'</div>'.
						'<button class="vk small cancel mt1 plus">»</button>'.
						'<div class="dib fs15 color-555 ml5">справа</div>'.
			'</table>'.

			'<button class="vk small cancel mt1 mr3 minus">«</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[2] ? 'bg-dfd' : 'pale').'">'.$ex[2].'</div>'.
			'<button class="vk small cancel mt1 plus">»</button>'.
			'<div class="fs15 color-555 mt3">снизу</div>'.

		'</div>';

		//границы блока
		$ex = explode(' ', $block['bor']);
		$bor =
		'<div class="mar20 w250 center">'.
			_check(array(
				'id' => 'bor0',
				'title' => 'сверху',
				'value' => $ex[0],
				'light' => 1
			)).

			'<table class="w100p ml10 mt20 mb20">'.
				'<tr><td class="w100">'.
						_check(array(
							'id' => 'bor3',
							'title' => 'слева',
							'value' => $ex[3],
							'light' => 1
						)).
					'<td>'.
						_check(array(
							'id' => 'bor1',
							'title' => 'справа',
							'value' => $ex[1],
							'light' => 1
						)).
			'</table>'.

			_check(array(
				'id' => 'bor2',
				'title' => 'снизу',
				'value' => $ex[2],
				'light' => 1
			)).
		'</div>';


		$send['html'] = utf8(
			'<div class="ml10 mr10">'.
				'<div class="hd2">Внутренние отступы:</div>'.
				$pad.

				'<div class="hd2 mt20">Цвет заливки:</div>'.
				'<div class="pas-block-bg mt10">'.
					'<div class="'.($block['bg'] == 'bg-fff' ? 'sel' : '').' dib h50 w50 bor-e8 curP" val="bg-fff"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr1' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr3" val="bg-gr1"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr3' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr3" val="bg-gr3"></div>'.
					'<div class="'.($block['bg'] == 'bg-gr2' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-gr2" val="bg-gr2"></div>'.
					'<div class="'.($block['bg'] == 'bg-ffe' ? 'sel' : '').' dib h50 w50 bor-e8 curP ml10 bg-ffe" val="bg-ffe"></div>'.
				'</div>'.

				'<div class="hd2 mt20">Границы:</div>'.
				$bor.
			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'page_block_style_save'://применение стилей блока
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		$bg = _txt($_POST['bg']);

		//отступы
		$ex = explode(' ', $_POST['pad']);
		$pad =  _num($ex[0]).' './/сверху
				_num($ex[2]).' './/справа
				_num($ex[3]).' './/снизу
				_num($ex[1]);    //слева

		//границы
		$ex = explode(' ', $_POST['bor']);
		$bor =  _num($ex[0]).' './/сверху
				_num($ex[1]).' './/справа
				_num($ex[2]).' './/снизу
				_num($ex[3]);    //слева


		//получение данных блока
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//изменение стилей
		$sql = "UPDATE `_page_block`
				SET `bg`='".addslashes($bg)."',
				    `pad`='".$pad."',
				    `bor`='".$bor."'
				WHERE `id`=".$id;
		query($sql);

//		$send['html'] = utf8(_page_show($block['page_id'], 1));

		jsonSuccess();
		break;
	case 'page_block_del'://удаление блока
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID блока');

		//получение данных блока
		$sql = "SELECT *
				FROM `_page_block`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//если блок является дочерним
		if($block['parent_id']) {
			//подсчёт количества дочерних блоков, в котором состоит удаляемый блок
			$sql = "SELECT COUNT(*)
					FROM `_page_block`
					WHERE `parent_id`=".$block['parent_id'];
			$parentCount = query_value($sql);

			//если блок останется один, то установка его основным на странице
			if($parentCount < 3) {
				//получение данных блока-родителя
				$sql = "SELECT *
						FROM `_page_block`
						WHERE `id`=".$id;
				$blockParent = query_assoc($sql);

				//удаление блока-родителя
				$sql = "DELETE FROM `_page_block` WHERE `id`=".$block['parent_id'];
				query($sql);

				//оставшийся блок помещается на основной странице
				$sql = "UPDATE `_page_block`
						SET `parent_id`=0,
							`w`=0,
							`sort`=".$blockParent['sort']."
						WHERE `parent_id`=".$block['parent_id'];
				query($sql);
			} else {
				//увеличение длины первого блока в строке на длину удалённого блока
				$sql = "UPDATE `_page_block`
						SET `w`=`w`+".$block['w']."
						WHERE `parent_id`=".$block['parent_id']."
						  AND `id`!=".$id."
						ORDER BY `sort`
						LIMIT 1";
				query($sql);
			}
		}

		//удаление блока
		$sql = "DELETE FROM `_page_block` WHERE `id`=".$id;
		query($sql);

		//удаление элементов в блоке
		$sql = "DELETE FROM `_page_element` WHERE `block_id`=".$id;
		query($sql);

		$send['html'] = utf8(_page_show($block['page_id'], 1));

		jsonSuccess($send);
		break;

	case 'page_elem_style_load'://получение стилей элемента для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID элемента');

		//получение данных блока
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$elem = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//отступы
		$ex = explode(' ', $elem['mar']);
		$mar =
		'<div class="elem-pas-mar mt10 mb20 center">'.

			'<div class="fs15 color-555 mb5">сверху</div>'.
			'<button class="vk small cancel mt1 mr3 minus">«</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[0] ? 'bg-dfd' : 'pale').'">'.$ex[0].'</div>'.
			'<button class="vk small cancel mt1 plus">»</button>'.

			'<table class="w100p ml20 mt30 mb30">'.
				'<tr><td class="w200">'.
						'<div class="dib fs15 color-555 mr5">слева</div>'.
						'<button class="vk small cancel mt1 mr3 minus">«</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[3] ? 'bg-dfd' : 'pale').'">'.$ex[3].'</div>'.
						'<button class="vk small cancel mt1 plus">»</button>'.
					'<td>'.
						'<button class="vk small cancel mt1 mr3 minus">«</button>'.
						'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[1] ? 'bg-dfd' : 'pale').'">'.$ex[1].'</div>'.
						'<button class="vk small cancel mt1 plus">»</button>'.
						'<div class="dib fs15 color-555 ml5">справа</div>'.
			'</table>'.

			'<button class="vk small cancel mt1 mr3 minus">«</button>'.
			'<div class="dib bor-e8 fs14 b pad2-7 mr3 w15 '.($ex[2] ? 'bg-dfd' : 'pale').'">'.$ex[2].'</div>'.
			'<button class="vk small cancel mt1 plus">»</button>'.
			'<div class="fs15 color-555 mt3">снизу</div>'.

		'</div>';

		//цвет
		$color = array(
			'' => '',
			'color-555' => '',
			'grey' => '',
			'pale' => '',
			'color-ccc' => '',
			'blue' => '',
			'color-acc' => '',
			'color-sal' => '',
			'color-pay' => '',
			'color-aea' => '',
			'color-ref' => '',
			'red' => '',
			'color-del' => '',
			'color-vin' => ''
		);
		$color[$elem['color']] = ' bg-dfd';

		//выделение
		$font = array(
			'b' => '',
			'i' => '',
			'u' => ''
		);
		foreach(explode(' ', $elem['font']) as $r)
			if($r)
				$font[$r] = ' bg-dfd';

		//размер текста
		$size = '';
		for($n = 10; $n <= 18; $n++) {
			if(!$elem['size'])
				$elem['size'] = 'fs13';
			$sel = $elem['size'] == 'fs'.$n ? ' bg-dfd' : '';
			$size .= '<td class="fs'.$n.$sel.' center h50 w50 bor-e8 curP over1" val="fs'.$n.'">'.$n.'px';
		}

		$send['html'] = utf8(
			'<div class="ml10 mr10">'.
				'<div class="hd2">Внешние отступы элемента:</div>'.
				$mar.

			(_pageElemFontAllow($elem['dialog_id']) ?
				'<div class="hd2">Цвет текста:</div>'.
				'<table class="elem-pas-color ml10 mt10 collaps">'.
					'<tr class="h35">'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color[''].'" val="">чёрный'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-555'].' color-555" val="color-555">тёмно-серый'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['grey'].' grey" val="grey">серый'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['pale'].' pale" val="pale">бледный'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-ccc'].' color-ccc" val="color-ccc">совсем бледный'.
				'</table>'.
				'<table class="elem-pas-color ml10 mt3 collaps">'.
					'<tr class="h35">'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['blue'].' blue" val="blue">синий'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-acc'].' color-acc" val="color-acc">голубой'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-sal'].' color-sal" val="color-sal">салатовый'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-pay'].' color-pay" val="color-pay">зелёный'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-aea'].' color-aea" val="color-aea">ярко-зелёный'.
				'</table>'.
				'<table class="elem-pas-color ml10 mt3 mb20 collaps">'.
					'<tr class="h35">'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-ref'].' color-ref" val="color-ref">тёмно-красный'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['red'].' red" val="red">красный'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-del'].' color-del" val="color-del">тёмно-бордовый'.
						'<td class="b center pl10 pr10 bor-e8 curP'.$color['color-vin'].' color-vin" val="color-vin">бордовый'.
				'</table>'.

				'<div class="hd2">Выделение:</div>'.
				'<table class="elem-pas-font ml10 mt10 mb20 collaps">'.
					'<tr><td class="fs16 center h50 w50 bor-e8 curP'.$font['b'].' b" val="b">B'.
						'<td class="fs16 center h50 w50 bor-e8 curP'.$font['i'].' i" val="i">I'.
						'<td class="fs16 center h50 w50 bor-e8 curP'.$font['u'].' u" val="u">U'.
				'</table>'.

				'<div class="hd2">Размер текста:</div>'.
				'<table class="elem-pas-size ml10 mt10 mb20 collaps"><tr>'.$size.'</table>'
			: '').

			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'page_elem_style_save'://применение стилей элемента
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID элемента');

		//отступы
		$ex = explode(' ', $_POST['mar']);
		$mar =  _num($ex[0]).' './/сверху
				_num($ex[2]).' './/справа
				_num($ex[3]).' './/снизу
				_num($ex[1]);    //слева

		$color = _txt(@$_POST['color']);
		$font = _txt(@$_POST['font']);

		$size = _txt(@$_POST['size']);
		if($size == 'fs13')
			$size = '';

		//получение данных элемента
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$id;
		if(!$elem = query_assoc($sql))
			jsonError('Элемента id'.$id.' не существует');

		//изменение стилей
		$sql = "UPDATE `_page_element`
				SET `mar`='".$mar."',
					`color`='".$color."',
					`font`='".$font."',
					`size`='".$size."'
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'page_elem_del'://применение новых стилей к элементу страницы
		if(!$element_id = _num($_POST['id']))
			jsonError('Некорректный ID элемента');

		//получение данных элемента
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$element_id;
		if(!$elem = query_assoc($sql))
			jsonError('Блока id'.$id.' не существует');

		//удаление элемента
		$sql = "DELETE FROM `_page_element` WHERE `id`=".$element_id;
		query($sql);

		$send['html'] = utf8(_page_show($elem['page_id'], 1));

		jsonSuccess();
		break;

	case 'spisok_add'://внесение данных диалога в _spisok
		$page_id = _num($_POST['page_id']);
		$block_id = _num($_POST['block_id']);

		$v = _dialogSpisokUpdate(0, $page_id, $block_id);

		$send['unit_id'] = $v['unit_id'];
		$send['action_id'] = _num($v['dialog']['action_id']);
		$send['page_id'] = _num($v['dialog']['action_page_id']);

		jsonSuccess($send);
		break;
/*
	case 'spisok_edit_load'://получение данных записи для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись была удалена');

		if(!$dialog = _dialogQuery($r['dialog_id']))
			jsonError('Диалога не существует');

		$html = '<table class="bs10">'._dialogComponentSpisok($r['dialog_id'], 'html', $r).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['component'] = _dialogComponentSpisok($r['dialog_id'], 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
*/
	case 'spisok_edit'://сохранение данных записи для диалога
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный идентификатор');

		$v = _dialogSpisokUpdate($unit_id);

		$send['unit_id'] = $v['unit_id'];
		$send['action_id'] = _num($v['dialog']['action_id']);
		$send['page_id'] = _num($v['dialog']['action_page_id']);

		jsonSuccess($send);
		break;
	case 'spisok_del'://удаление записи из _spisok
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись уже была удалена');

		$sql = "UPDATE `_spisok`
				SET `deleted`=1
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'spisok_get'://получение обновлённого списка по условиям
		if(!$element_id = _num($_POST['element_id']))
			jsonError('Некорректный ID элемента станицы');

		$v = _txt($_POST['v']);
		
		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$element_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$element_id.' не существует');

		//сохранение строки поиска
		$sql = "UPDATE `_page_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$element_id;
		query($sql);

		//id диалога списка, на который происходит воздействие через поиск
		if(!$pe_id = _num($pe['num_3']))
			jsonError('Нет воздействия на список');

		//расположение списка на странице, на которой расположен поиск
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('Нет нужного списка на странице');

		$send['attr_id'] = '#pe_'.$peSpisok['id'];
		$send['spisok'] = utf8(_pageSpisok($peSpisok));

		jsonSuccess($send);
		break;
	case 'spisok_col_get'://получение колонок списка (для функции Действие 5 и 6)
		if(!$cmp_id = _num($_POST['component_id']))
			jsonError('Некорректный ID компонента диалога');
		if(!$page_id = _num($_POST['page_id']))
			jsonError('Некорректный ID страницы');
		if(!$vid = _num($_POST['vid']))
			jsonError('Некорректное значение для поиска списка');

		$sql = "SELECT *
				FROM `_dialog_component`
				WHERE `id`=".$cmp_id;
		if(!$cmp = query_assoc($sql))
			jsonError('Компонента не существует');

		//проверка: компонент должен быть выпадающим списком и одновременно содержать списки
		if($cmp['type_id'] != 2 || !$cmp['num_4'])
			jsonError('Компонент не является массивом списков');
		if(!$cmpDialog = _dialogQuery($cmp['dialog_id']))
			jsonError('Диалога, в котором содержится компонент, не существует');

		$cmpFuncAss = $cmpDialog['component'][$cmp_id]['func_action_ass'];
		$f5 = !empty($cmpFuncAss[5]);
		$f6 = !empty($cmpFuncAss[6]);
		if(!$f5 && !$f6)
			jsonError('Отсутствует функция получения колонок');

		//получение dialog_id списка
		$dialog_id = $vid;  //если не установлена галочка "только с текущей страницы", то это и есть тот самый dialog_id
		$colIds = array();
		if($cmp['num_5']) {//получение dialog_id из элемента страницы
			$sql = "SELECT *
					FROM `_page_element`
					WHERE `page_id`=".$page_id."
					  AND `id`=".$vid;
			if(!$pe = query_assoc($sql))
				jsonError('Элемента на странице не существует');
			if(!$dialog_id = _num($pe['num_3']))
				jsonError('Нет размещённого списка на странице');

			$colIds = _idsAss($pe['txt_3']);  //получение значения функции Действие 5
		}

		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');
		if(!$dialog['spisok_on'])
			jsonError('Диалог не является списком');
		if(empty($dialog['component']))
			jsonError('В данном списке колонок нет');

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
				'<tr><td class="label r top" style="width:'.$cmpDialog['label_width'].'px">Колонк'.($f6 ? 'а' : 'и').':'.
					'<td>'.$col.
			'</table>'
		);

		jsonSuccess($send);
		break;
}

function _dialogUpdate($dialog_id) {//обновление диалога
	if(!$head_insert = _txt($_POST['head_insert']))
		jsonError('Не указано название диалога для новой записи');
	if(!$button_insert_submit = _txt($_POST['button_insert_submit']))
		jsonError('Не указан текст кнопки внесения');
	if(!$button_insert_cancel = _txt($_POST['button_insert_cancel']))
		jsonError('Не указан текст кнопки отмены для новой записи');

	if(!$head_edit = _txt($_POST['head_edit']))
		jsonError('Не указано название диалога редактирования');
	if(!$button_edit_submit = _txt($_POST['button_edit_submit']))
		jsonError('Не указан текст кнопки сохранения');
	if(!$button_edit_cancel = _txt($_POST['button_edit_cancel']))
		jsonError('Не указан текст кнопки отмены редактирования');

	if(!$width = _num($_POST['width']))
		jsonError('Некорректное значение ширины диалога');
	if($width < 480 || $width > 900)
		jsonError('Установлена недопустимая ширина диалога');
	if(!$label_width = _num($_POST['label_width']))
		jsonError('Некорректное значение ширины label');

	$base_table = _txt($_POST['base_table']);
	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_name = _txt($_POST['spisok_name']);
	if($spisok_on && !$spisok_name)
		jsonError('Укажите имя списка страницы');

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
		jsonError('Диалога не существует');

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

	_cacheNew('clear', '_dialogQuery'.$dialog_id);

	return $dialog_id;
}
function _dialogFuncUpdate($dialog_id) {//обновление функций компонентов диалога
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
function _dialogFuncSpisok($dialog_id) {//получение данных фукнциий компонентов диалога
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

function _dialogEl($type_id=0, $i='') {//данные всех элементов, используемых в диалоге
	if(!defined('EL_LABEL_W'))
		define('EL_LABEL_W', 'w175');//первоначальная ширина для всех label
	$sort = array(9,3,4,2,1,5,6,7,8);

	$name = array(
		1 => 'Галочка',
		2 => 'Выпадающий список',
		3 => 'Однострочный текст',
		4 => 'Многострочный текст',
		5 => 'Radio',
		6 => 'Календарь',
		7 => 'Информация',
		8 => 'Связка',
		9 => 'Заголовок'
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
	//может ли компонент содержать функцию (а также может ли участвовать в передаче данных)
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
		1 => /* *** галочка ***
				txt_1 - текст для галочки
            */
			_dialogElHtmlContent().
			'<table class="bs5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Текст для галочки:'.
					'<td><input type="text" class="w250" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		2 => /* *** выпадающий список ***
				num_3 - использовать или нет нулевое значение
                txt_1  - текст нулевого значения
				num_4:  0 - данных нет
						1 - произвольные значения
						2 - использование всех списков при выборе
							num_5 - выбор списков только с текущей страницы
						3 - выбор элемента списка (для связок)
						4 - список объектов, которые поступают на страницу через GET
 				num_1  - id списка по dialog_id
		        num_2  - id колонки по component_id
			*/
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20">Содержание выпадающего списка:</div>'.
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Нулевое значение:'.
					'<td><input type="hidden" id="num_3" value="1" />'.
						'<input type="text" class="w230 ml5" id="txt_1" value="не выбрано" />'.
			'</table>'.
			'<input type="hidden" id="num_4" />'.
			'<div id="elem-select-but" class="center pad10">'.
				'<p><button class="vk small green">Произвольные значения</button>'.
				'<p class="mt5"><button class="vk small">Все списки</button> '.
				'<button class="vk small">Выбрать из списков</button>'.
				'<p class="mt5"><button class="vk small">Списки объектов страницы</button>'.
			'</div>'.
			_dialogElHtmlPrev(),

		3 => /* *** Однострочный текст ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Подсказка в поле:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<input type="text" id="elem-attr-id" class="w250" />'),

		4 => /* *** Многострочный текст ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Подсказка в поле:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<textarea id="elem-attr-id" class="w250"></textarea>'),

		5 => /* *** Радио ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20" id="radio-cont">Содержание:</div>'.
			_dialogElHtmlPrev(),

		6 => /* *** Календарь ***
				num_3 - возможность выбирать прошедшие дни
                num_4 - показывать ссылку "завтра"
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Выбор прошедших дней:'.
					'<td><input type="hidden" id="num_3" />'.
				'<tr><td class="label r">Ссылка <u>завтра</u>:'.
					'<td><input type="hidden" id="num_4" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		7 => /* *** Информация ***
				txt_1 - текст информации
             */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r topi '.EL_LABEL_W.'">Текст:'.
					'<td><textarea id="txt_1" class="w300"></textarea>'.
			'</table>'.
			'<div id="prev-tab" class="mt20 pad20 pt10">'.
				'<div class="hd2">Предварительный просмотр:</div>'.
				'<div id="elem-attr-id" class="_info mt10"></div>'.
			'</div>',

		8 => /* *** Связка ***
 				num_1  - id списка по dialog_id
		        num_2  - id колонки по component_id
            */
			_dialogElHtmlContent().
			'<div id="connect-head"></div>'.
			_dialogElHtmlPrev('<div class="grey i">Текстовый результат</div>'),

		9 => /* *** Заголовок ***
 				num_1  - вид заголовка
 				txt_1  - текст заголовка
            */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Вид:'.
					'<td><input type="hidden" id="num_1" value="2" />'.
				'<tr><td class="label r">Текст:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			'<div class="b ml20 mt20 mb5">Предосмотр:</div>'.
			'<div id="prev-tab" class="pad20 pt10 bor-f0">'.
				'<div id="elem-attr-id" class="mt10 hd2"></div>'.
			'</div>',
	);

	//получение возможности настройки функции для компонента
	if($i == 'func')
		return $func[$type_id];

	//подготовка и отправка имён компонентов через AJAX
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
function _dialogElHtmlHead($name) {//заголовок элемента
	return '<div class="fs16 bg-gr1 pad20 line-b mb10">Компонент <b class="fs16">'.$name.'</b></div>';
}
function _dialogElHtmlSA() {//поле SA
	if(!SA)
		return '';
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' red r">SA: col_name:'.
			'<td><input type="text" id="col_name" class="w100" />'.
	'</table>';
}
function _dialogElHtmlContent($req=0) {//основное содержимое
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r b">Название поля:'.
			'<td><input type="text" id="label_name" class="w250" />'.
	'</table>'.

($req ? //отображение галочки "Требуется обязательное заполнение"
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.'">'.
			'<td><input type="hidden" id="label-require" />'.
	'</table>'
: '').

	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r topi">Текст выплывающей<br />подсказки:'.
			'<td><textarea id="label-hint" class="w300"></textarea>'.
	'</table>';
}
function _dialogElHtmlPrev($inp='<input type="hidden" id="elem-attr-id" />') {//предварительный просмотр
	return
	'<div id="prev-tab" class="mt20 pad20 pt10 bor-f0 bg-ffe">'.
		'<div class="hd2">Предварительный просмотр:</div>'.
		'<table class="bs5 w100p mt10">'.
			'<tr><td id="label-prev" class="label r '.EL_LABEL_W.'">'.
				'<td>'.$inp.
		'</table>'.
	'</div>';
}



function _dialogComponentUpdate($dialog_id=0) {//проверка/внесение элементов диалога
	if(!$arr = @$_POST['component'])
		jsonError('Отсутствуют компоненты диалога');
	if(!is_array($arr))
		jsonError('Некорректный массив компонентов диалога');

	foreach($arr as $r) {
		if(!$type_id = _num($r['type_id']))
			jsonError('Некорректный тип компонента');

		$component_id = _num($r['id']);

		//проверка на ошибки всех видов компонентов диалога
		switch($type_id) {
			case 1://check
				if(!_txt($r['label_name']) && !_txt($r['txt_1']))
					jsonError('Укажите название поля,<br />либо текст для галочки');
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
					jsonError('Отсутствуют значения элемента Radio');
				break;
			case 6://календарь
				break;
			case 7://info
				break;
			case 8://connect
				break;
			case 9://head
				break;
			default:
				jsonError('Несуществующий тип компонента');
		}
	}

	//первый запуск - тестирование
	if(!$dialog_id)
		return;

	//удаление удалённых компонентов
	$sql = "DELETE FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			  AND `id` NOT IN ("._idsGet($arr).")";
	query($sql);

	//удаление значений удалённых компонентов
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
			//формирование названия поля на основании типа элемента
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

		//удаление всех элементов, если были
		if(empty($r['v'])) {
			$sql = "DELETE FROM `_dialog_component_v`
					WHERE `component_id`=".$component_id;
			query($sql);
			continue;
		}

		//удаление удалённых значений элемента
		if($ids = _idsGet($r['v'])) {
			$sql = "DELETE FROM `_dialog_component_v`
					WHERE `component_id`=".$component_id."
					  AND `id` NOT IN (".$ids.")";
			query($sql);
		}

		//внесение дополнительных значений элемента
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
function _dialogComponentSpisok($dialog_id, $i, $data=array(), $page_id=0) {//список значений диалога в формате массива и html
/*
	Форматы возврата данных:
		arr
		arr_edit
		html
		html_edit

	Компоненты и их характеристики - порядок отображения колонки в таблице, например: txt_1, num_2
		1. check:    num
		2. select:   num
		3. input:    txt
		4. textarea: txt
		5. radio:    num
		6. calendar: date
		7. info:     txt
		8. связка:   num
		9. head:     num
*/

	$arr = array();
	$html = '';
	$edit = $i == 'html_edit' || $i == 'arr_edit';//редактирование + сортировка значений

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

			//установка значения при редактировании данных диалога
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

					//получение названия таблицы для связки из диалога
					$sql = "SELECT `base_table`
							FROM `_dialog`
							WHERE `id`=".$r['num_1'];
					$baseTable = query_value($sql);

					//получение названия колонки для связки
					$sql = "SELECT `col_name`
							FROM `_dialog_component`
							WHERE `id`=".$r['num_2'];
					$colName = query_value($sql);

					//получение значения колонки
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
						'<div class="component-del icon icon-del'._tooltip('Удалить компонент', -59).'</div>'.
						'<div class="component-edit icon icon-edit'._tooltip('Настроить компонент', -66).'</div>'.
					(_dialogEl($type_id, 'func') ?
						'<div class="component-func'.(empty($func[$r['id']]) ? '' : ' on').' icon icon-zp'._tooltip('Настроить функции', -61).'</div>'
					: '')
				: '').
						'<div id="delem'.$r['id'].'">'.
							'<table class="bs5 w100p">'.
								'<tr><td class="label '.($type_7 ? '' : 'r').($edit ? ' label-width pr5' : '').'" '.($type_7 ? 'colspan="2"' : 'style="width:'.$label_width.'px"').'>'.
										($r['label_name'] ? $r['label_name'].':' : '').
										($r['require'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
										($r['hint'] ? ' <div class="icon icon-hint dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'"></div>' : '').
					//если информация, то показ на всю ширину
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

				'func_flag' => _dialogEl($type_id, 'func'), //может ли содержать функцию

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
					case 2://все списки или с конкретной страницы
						$arr[$n]['v'] = $r['num_5'] ? _dialogSpisokOnPage($page_id) : _dialogSpisokOn();
						break;
					case 3://получение значений конкретного объекта
						$arr[$n]['v'] = _dialogSpisokList($r['num_1'], $r['num_2']);
						break;
					case 4://список объектов, которые поступают на страницу через GET
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
function _dialogComponent_autoSelectPage($val, $r, $page_id) {//установка страницы по умолчанию, если список добавляется на страницу, кнопка этого списка на данной странице
	//выбранное значениe не меняется
	if($val)
		return $val;

	//нет страницы - нет выбора
	if(!$page_id)
		return '';

	if($r['dialog_id'] == 3)//меню
		return '';

	$spisokOther = $r['type_id'] == 2 && $r['num_1'];

	//выбор конкретного элемента - умолчания нет
	if(!$spisokOther)
		return '';

	//определение, есть ли на странице элементы-кнопки: dialog_id=2
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
function _dialogComponent_defSet($val, $r, $isEdit) {//установка значения по умолчанию в select
	//выбранное значениe не меняется
	if($val)
		return $val;

	//если редактирование диалога, то не устанавливается
	if($isEdit)
		return $val;


	//все элементы списка - умолчания нет
	if($r['type_id'] == 2 && $r['num_4'])
		return $val;

	//выбор конкретного элемента - умолчания нет
	if($r['type_id'] == 2 && $r['num_1'])
		return $val;

	//либо select, либо radio
	if($r['type_id'] != 2 && $r['type_id'] != 5)
		return $val;

	$sql = "SELECT *
			FROM `_dialog_component_v`
			WHERE `component_id`=".$r['id']."
			  AND `def`
			LIMIT 1";

	return query_value($sql);
}

function _dialogSpisokList($dialog_id, $component_id) {//массив списков (пока только для select)
	$dialog = _dialogQuery($dialog_id);

	$sql = "SELECT `col_name`
			FROM `_dialog_component`
			WHERE `id`=".$component_id;
	if(!$colName = query_value($sql))
		$colName = 'id';

	//отображение списка страниц определённым образом
	if($dialog['base_table'] == '_page')
		return _dialogPageList();

	$sql = "SELECT `id`,`".$colName."`
			FROM `".$dialog['base_table']."`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogSpisokUpdate($unit_id=0, $page_id=0, $block_id=0) {//внесение/редактирование записи списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	$send = array(
		'unit_id' => $unit_id,
		'dialog' => $dialog
	);
	
	//проверка наличия таблицы для внесения данных
	define('BASE_TABLE', $dialog['base_table']);
	$sql = "SHOW TABLES LIKE '".BASE_TABLE."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	if($unit_id) {
		$sql = "SELECT *
				FROM `".BASE_TABLE."`
				WHERE `app_id` IN (0,".APP_ID.")
				  AND `id`=".$unit_id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	//удаление элемента со страницы
	if($dialog_id == 6) {
		$sql = "DELETE FROM `_page_element` WHERE `id`=".$unit_id;
		query($sql);
		return $send;
	}

	//проверка на корректность данных компонентах диалога
	if(!$elem = @$_POST['elem'])
		jsonError('Нет данных для внесения');
	if(!is_array($elem))
		jsonError('Некорректный формат данных');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('Некорректный идентификатор поля');

	$elemUpdate = array();
	foreach($dialog['component'] as $id => $r) {
		if(!_dialogEl($r['type_id'], 'func'))
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError(array(
				'delem_id' => $id,
				'text' => utf8('Не заполнено поле <b>'.$r['label_name'].'</b>')
			));

		//если это выпадающий список, выбирающий связку и вносит в список элементов
//		if($r['type_id'] == 2 && $dialog['base_table'] == '_page_element' && $r['num_1'])
//			$elemUpdate[] = "`num_id`=".$r['num_1'];

		//служебная переменная app_any_spisok. Если равна 1, то устанавливает app_id=0 (все приложения), либо = id приложения
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
			default://остальные текстовые значения
				if(preg_match('/^num_/', $r['col_name'])) {//если текстовое значение должно быть только числом
					if($v && !preg_match(REGEXP_NUMERIC, $v))
						jsonError(array(
							'delem_id' => $id,
							'text' => utf8('Некорректно заполнено поле <b>'.$r['label_name'].'</b>')
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

		//обновление page_id, если есть
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

	//обновление функций компонентов
	foreach($dialog['component'] as $id => $r)
		_dialogSpisokFuncValUpdate($dialog, $id, $unit_id);

	return $send;
}
function _dialogSpisokFuncValUpdate($dialog, $cmp_id, $unit_id) {//обновление значений функций компонентов (пока конкретно Действие 5)
	if(!$unit_id)
		return;
	if(!isset($_POST['func'][$cmp_id]))
		return;

	$cmp = $dialog['component'][$cmp_id];
	$v = $_POST['func'][$cmp_id];

	//проверка наличия функции у компонента
	$f5 = !empty($cmp['func_action_ass'][5]);
	$f6 = !empty($cmp['func_action_ass'][6]);
	if(!$f5 && !$f6)
		return;

	//если компонент не содержит список
	if(!$cmp['num_4'])
		return;

	if($f5) {
		//если список не со страницы
		if(!$cmp['num_5'])
			return;

		//получение id элемента страницы, у которой будет изменяться значение
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
}

























/*
function _dialogSpisokColUpdate($dialog, $unit_id, $elem, $de) {//внесение/редактирование данных конкретного поля таблицы
	$elemUpdate = array();
	foreach($de as $id => $r) {
		if($r['type_id'] == 7)//info
			continue;

		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('Не заполнено поле <b>'.$r['label_name'].'</b>');

		$elemUpdate[$r['col_name']] = utf8($v);
	}

	$id = 1;

	$sql = "SELECT `".$dialog['col']."`
			FROM `".$dialog['base_table']."`
			WHERE `id`=".$unit_id;
	if($col = query_value($sql)) {
		$col = json_decode(utf8($col), true);

		//получение максимального ID
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