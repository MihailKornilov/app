<?php
switch(@$_POST['op']) {
	case 'dialog_setup_load'://получение данных диалога для его настройки
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');
		if(!SA && !USER_ADMIN)
			jsonError('Нет доступа');

		$menu = array(
			1 => 'Диалог',
			2 => 'История',
			3 => 'Содержание',
	  		4 => 'Служебное',
			9 => '<b class=clr5>SA</b>'
		);
		$action = array(//действие, которое будет происходить после внесения или изменения единицы списка
			3 => 'Обновить содержимое блоков',
			1 => 'Обновить страницу',
			2 => 'Перейти на страницу',
			4 => 'Обновить исходный диалог',
			5 => 'Открыть диалог'
		);

		if(!SA) {
			unset($menu[9]);
			unset($action[4]);
		}

		//история вносится, если запись не удаляется физически
		if(!isset($dialog['field1']['deleted']))
			unset($menu[2]);
		//история вносится только у родительских диалогов
		if($dialog['dialog_id_parent'])
			unset($menu[2]);

		if(!isset($menu[$dialog['menu_edit_last']]))
			$dialog['menu_edit_last'] = 1;
		//установка раздела меню по запросу
		if($menu_id = _num(@$_POST['menu']))
			$dialog['menu_edit_last'] = $menu_id;

		$group = array();
		$menu_sa = array();
		if(SA) {
			//группы элементов
			$sql = "SELECT *
					FROM `_element_group`
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$group[] = array(
					'id' => _num($r['id']),
					'title' => _br($r['name'], ' '),
					'content' => '<div class="'._dn(!$r['sa'], 'clr5').'">'._br($r['name']).'</div>'
				);
			}

			$menu_sa = array(
				1 => 'Диалог',
				2 => 'Элемент',
				3 => 'Использование'
			);
			if(empty($dialog['element_group_id']))
				unset($menu_sa[3]);
		}

		//условия удаления
		$sql = "SELECT `id`
				FROM `_element`
				WHERE `dialog_id`=58
				  AND `num_1`=".$dialog_id."
				LIMIT 1";
		$del58 = _num(query_value($sql));


		//присвоение ID стороннего диалога
		$IUID_dlg_id = 0;
		$IUID_cols = array();
		if($el = _elemOne($dialog['insert_unit_id_set_elem_id']))
			if($BL = _blockOne($el['block_id']))
				if($BL['obj_name'] == 'dialog') {
					$IUID_dlg_id = $BL['obj_id'];
					$IUID_cols = _dialogSpisokCmp($BL['obj_id']);
				}

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<div class="pad10 center bg6 line-b">'.
				'<div class="fr mt5 b clr2 curD dw tool" data-tool="Ширина диалога">'.$dialog['width'].'</div>'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1'._dn($dialog['menu_edit_last'] == 1).'">'.

				'<div class="pad10 bg4 line-b">'.
					'<table class="bs5 w100p">'.
						'<tr><td class="w150 r clr9">Имя диалогового окна:'.
							'<td><input type="text" id="dialog_name" class="w100p b" maxlength="100" value="'.$dialog['name'].'" />'.
					'</table>'.
				'</div>'.

				'<div class="pad10 bg11">'.
					'<div class="hd2 mt5">'.
						'Внесение новой записи'.
						'<div class="fr">'.
							'<input type="hidden" id="insert_on" value="'.$dialog['insert_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['insert_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="clr1 w150 r">Заголовок:'.
								'<td><input type="text" id="insert_head" class="w100p" maxlength="200" placeholder="название диалогового окна - новая запись" value="'.$dialog['insert_head'].'" />'.
							'<tr><td class="clr1 r">Текст кнопок:'.
								'<td><input type="text" id="insert_button_submit" class="w150" maxlength="100" value="'.$dialog['insert_button_submit'].'" />'.
									'<input type="text" id="insert_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['insert_button_cancel'].'" />'.
							'<tr><td class="clr15 r">Дальнейшее действие:'.
								'<td><input type="hidden" id="insert_action_id" value="'.$dialog['insert_action_id'].'" />'.
							'<tr class="td-insert-action-obj'._dn($dialog['insert_action_id'] == 2 || $dialog['insert_action_id'] == 5).'">'.
								'<td class="clr1 r td-insert-action-title">'.
								'<td><input type="hidden" id="insert_action_obj_id" value="'.$dialog['insert_action_obj_id'].'" />'.

							'<tr><td class="clr15 r h35">Воздействие на запись:'.
								'<td><a id="insert_unit_change" class="'.($dialog['insert_unit_change_elem_id'] ? 'clr11 b">' : 'clr1">не ').'настроено</a>'.

							'<tr><td class="clr15 r">Присвоение ID:'.
								'<td><input type="hidden" id="IUID_dlg_id" value="'.$IUID_dlg_id.'" />'.
							'<tr class="tr-iuid'._dn($dialog['insert_unit_id_set_elem_id']).'">'.
								'<td>'.
								'<td><input type="hidden" id="insert_unit_id_set_elem_id" value="'.$dialog['insert_unit_id_set_elem_id'].'" />'.

						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="bg7 line-t1 pad10">'.
					'<div class="hd2 mt5">'.
						'Редактирование записи'.
						'<div class="fr">'.
							'<input type="hidden" id="edit_on" value="'.$dialog['edit_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['edit_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="clr1 w150 r">Заголовок:'.
								'<td><input type="text" id="edit_head" class="w100p" maxlength="200" placeholder="название диалогового окна - редактирование" value="'.$dialog['edit_head'].'" />'.
							'<tr><td class="clr1 r">Текст кнопок:'.
								'<td><input type="text" id="edit_button_submit" class="w150" maxlength="100" value="'.$dialog['edit_button_submit'].'" />'.
									'<input type="text" id="edit_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['edit_button_cancel'].'" />'.
							'<tr><td class="clr15 r">Дальнейшее действие:'.
								'<td><input type="hidden" id="edit_action_id" value="'.$dialog['edit_action_id'].'" />'.
							'<tr class="td-edit-action-obj'._dn($dialog['edit_action_id'] == 2 || $dialog['edit_action_id'] == 5).'">'.
								'<td class="clr1 r td-edit-action-title">'.
								'<td><input type="hidden" id="edit_action_obj_id" value="'.$dialog['edit_action_obj_id'].'" />'.
						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="bg14 line-t1 pad10">'.
					'<div class="hd2 mt5">'.
						'Удаление записи'.
						'<div class="fr">'.
							'<input type="hidden" id="del_on" value="'.$dialog['del_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['del_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="clr1 w150 r">Заголовок:'.
								'<td><input type="text" id="del_head" class="w100p" maxlength="200" placeholder="название диалогового окна - удаление" value="'.$dialog['del_head'].'" />'.
							'<tr><td class="clr1 r">Текст кнопок:'.
								'<td><input type="text" id="del_button_submit" class="w150" maxlength="100" value="'.$dialog['del_button_submit'].'" />'.
									'<input type="text" id="del_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['del_button_cancel'].'" />'.
							'<tr><td class="clr1 r h35">Содержание удаления:'.
								'<td>'._dialogContentDelSetup($dialog_id).
							'<tr><td class="clr1 r">Условия удаления:'.
								'<td class="clr2">'.
									($del58 ? '' : 'условий нет. ').
									'<div val="dialog_id:58,dss:'.$dialog_id.',edit_id:'.$del58.'"'.
										' class="icon icon-set pl dialog-open tool"'.
										' data-tool="Настроить условия">'.
									'</div>'.
							'<tr><td class="clr15 r">Дальнейшее действие:'.
								'<td><input type="hidden" id="del_action_id" value="'.$dialog['del_action_id'].'" />'.
							'<tr class="td-del-action-obj'._dn($dialog['del_action_id'] == 2 || $dialog['del_action_id'] == 5).'">'.
								'<td class="clr1 r td-del-action-title">'.
								'<td><input type="hidden" id="del_action_obj_id" value="'.$dialog['del_action_obj_id'].'" />'.
						'</table>'.
					'</div>'.
				'</div>'.
			'</div>'.

			//История действий
			_dialogSetupHistory($dialog).

			//Содержание
			'<div class="dialog-menu-3'._dn($dialog['menu_edit_last'] == 3).'">'.
				'<div class="pad10 line-b bg-ffc">'.
					_blockLevelChange('dialog', $dialog_id).
				'</div>'.
				'<div class="block-content-dialog" style="width:'.$dialog['width'].'px">'.
					_blockHtml('dialog', $dialog_id, array('blk_setup' => 1)).
				'</div>'.
			'</div>'.

			//Служебное
			_dialogSetupService($dialog).

			//SA
	  (SA ? '<div class="dialog-menu-9 pb20'._dn($dialog['menu_edit_last'] == 9).'">'.
				'<div class="mt5 mb10 ml20 mr20">'.
		            '<input type="hidden" id="menu_sa" value="1" />'.
				'</div>'.

				'<table class="menu_sa-1 bs10">'.
					'<tr><td class="clr5 r w80">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="clr5 r">Ширина:'.
		                '<td><div id="dialog-width" class="dib w50">'.$dialog['width'].'</div>'.
		                    '<input type="hidden" id="width_auto" value="'.$dialog['width_auto'].'" />'.
					'<tr><td class="clr5 r">Таблица 1:'.
						'<td><input type="hidden" id="table_1"   value="'.$dialog['table_1'].'" />'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'cmp_no_req',
									'title' => 'компоненты в содержании не требуются',
									'value' => $dialog['cmp_no_req']
							   )).

					'<tr><td><td>'.

					'<tr><td>'.
						'<td>'.
		                        _check(array(
									'attr_id' => 'sa',
									'title' => 'SA only',
									'value' => $dialog['sa']
								)).
					//доступность диалога. На основании app_id.
		            //0 - доступен только конкретному приложению
		            //1 - всем приложениям
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'title' => 'доступен во всех приложениях',
									'value' => $dialog['app_id'] ? 0 : 1
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'spisok_any',
									'title' => 'данные, которые вносит диалог,<br>доступны во всех приложениях',
									'value' => $dialog['spisok_any']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'parent_any',
									'title' => 'может быть выбран родительским во всех приложениях',
									'value' => $dialog['parent_any']
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'clone_on',
									'title' => 'данные диалога участвуют в клонировании и копировании',
									'value' => $dialog['clone_on']
							   )).
				'</table>'.

				_dialogSetupSa2($dialog).
				_dialogSetupUse($dialog).

			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['width_min'] = _dialogWidthMin($dialog['blk']);
		$send['menu'] = _selArray($menu);
		$send['menu_sa'] = _selArray($menu_sa);
		$send['action'] = _selArray($action);
		$send['col_type'] = _selArray(_elemColType());
		$send['blk'] = $dialog['blk'];
		$send['cmp'] = $dialog['cmp'];
		$send['html'] = $html;
		$send['sa'] = SA;
		$send['tables'] = SA ? _table() : array();
		$send['group'] = $group;
		$send['dlg_all'] = _dialogSelArray();
		$send['dlg_func'] = _dialogSelArray('dlg_func');
		$send['dlg_spisok_on'] = _dialogSelArray('spisok_only', $dialog_id);
		$send['spisok_cmp'] = _dialogSpisokCmp($dialog_id);
		$send['iuid_cols'] = $IUID_cols;
		$send['page_list'] = _element6_vvv();

		jsonSuccess($send);
		break;
	case 'dialog_setup_cols'://получение колонок конктетного диалога
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		$send['spisok'] = _dialogSpisokCmp($dialog_id);

		jsonSuccess($send);
		break;
	case 'dialog_setup_save'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');
		if(!SA && !USER_ADMIN)
			jsonError('Нет доступа');

		_dialogSave($dialog_id);

		$send = _dialogOpenLoad($dialog_id);

		//если на странице диалогов, при сохранении диалога обновляется страница
		if($_POST['page_id'] == 123)
			$send['content'] = _pageShow(123);

		jsonSuccess($send);
		break;
	case 'dialog_open_load'://получение данных диалога
		if(!$dialog_id = _dialogTest())
			jsonError('Некорректный ID диалога');

		$send = _dialogOpenLoad($dialog_id);

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

		_cache_clear('page');

		jsonSuccess();
		break;
	case 'dialog_sort'://сортировка диалогов
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

		$sql = "INSERT INTO `_dialog` (
					`id`,
					`pid`,
					`sort`
				) VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`pid`=VALUES(`pid`),
					`sort`=VALUES(`sort`)";
		query($sql);

		_cache_clear('DIALOG');

		jsonSuccess();
		break;

	case 'image_upload'://добавление изображения
		if(!$f = @$_FILES['f1'])
			jsonError('Файл отсутствует');
		if($f['size'] > 15728640)
			jsonError('Размер изображения не должен быть более 15 Мб');

		$img = _imageSave($f['type'], $f['tmp_name']);

		$send['html'] = _imageDD($img);

		jsonSuccess($send);
		break;
	case 'image_link'://загрузка изображения по ссылке
		if(!$url = _txt(@$_POST['url']))
			jsonError('Отсутствует ссылка');

		$img = _imageLink($url);
		$send['html'] = _imageDD($img);

		jsonSuccess($send);
		break;
	case 'image_recover'://восстановелние изображения из корзины
		if(!$image_id = _num($_POST['id']))
			jsonError('Некорректный id изображения');

		$sql = "SELECT *
				FROM `_image`
				WHERE `id`=".$image_id;
		if(!$img = query_assoc($sql))
			jsonError('Изображения не существует');

		$send['html'] = _imageDD($img);
		jsonSuccess($send);
		break;

	case 'element_image_upload'://загрузка изображения для элемента
		if(!$f = @$_FILES['f1'])
			jsonError('Файл отсутствует');

		$img = _imageSave($f['type'], $f['tmp_name']);

		$send['img_id'] = $img['id'];
		$send['html'] = _imageFromId($img['id']);

		jsonSuccess($send);
		break;

	case 'filter_calendar_mon_change'://перелистывание фильтра-календаря
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента не существует');
		if($el['dialog_id'] != 77)
			jsonError('Элемент не является фильтром-календарём');
		if(!preg_match(REGEXP_YEARMON, $_POST['mon']))
			jsonError('Некорректные месяц и год');

		$side = _num($_POST['side']);//направление, в котором пролистывается календарь

		$ex = explode('-', $_POST['mon']);
		$YEAR = _num($ex[0]);
		$MON = _num($ex[1]);

		$next = $MON + 1 * ($side ? 1 : -1);

		if($side)
			$mon = $next > 12 ? ($YEAR + 1).'-01' : $YEAR.'-'.($next < 10 ? 0 : '').$next;
		else
			$mon = !$next ? ($YEAR - 1).'-12' : $YEAR.'-'.($next < 10 ? 0 : '').$next;

		$v = _filter('v', $el['id']);
		$send['mon'] = $mon;
		$send['td_mon'] = _filterCalendarMon($el, $mon, $v);
		$send['cnt'] = _filterCalendarContent($el, $mon, $v);

		jsonSuccess($send);
		break;

	case 'note_add'://добавление заметки
		if(!$page_id = _num($_POST['page_id']))
			jsonError('Некорректный ID страницы');
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$txt = _txt(@$_POST['txt']))
			jsonError('Отсутствует текст заметки');

		$obj_id = _num($_POST['obj_id']);

		$sql = "INSERT INTO `_note` (
					`app_id`,
					`page_id`,
					`obj_id`,
					`txt`,
					`user_id_add`
				) VALUES (
					".APP_ID.",
					".$page_id.",
					".$obj_id.",
					'".addslashes($txt)."',
					".USER_ID."
				)";
		query($sql);

		$send['html'] = _noteList($page_id, $obj_id, $el);

		jsonSuccess($send);
		break;
	case 'note_del'://удаление заметки
		if(!$note_id = _num($_POST['note_id']))
			jsonError('Некорректный ID заметки');

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND !`parent_id`
				  AND `id`=".$note_id;
		if(!$note = query_assoc($sql))
			jsonError('Заметки не существует');

		if($note['deleted'])
			jsonError('Заметка была удалена');

		$sql = "UPDATE `_note`
				SET `deleted`=1,
					`user_id_del`=".USER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$note_id;
		query($sql);

		jsonSuccess();
		break;
	case 'note_rest'://восстановление заметки
		if(!$note_id = _num($_POST['note_id']))
			jsonError('Некорректный ID заметки');

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND !`parent_id`
				  AND `id`=".$note_id;
		if(!$note = query_assoc($sql))
			jsonError('Заметки не существует');

		if(!$note['deleted'])
			jsonError('Заметка не была удалена');

		$sql = "UPDATE `_note`
				SET `deleted`=0,
					`user_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$note_id;
		query($sql);

		jsonSuccess();
		break;
	case 'note_comment_add'://добавление комментария
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$note_id = _num($_POST['note_id']))
			jsonError('Некорректный ID заметки');
		if(!$txt = _txt(@$_POST['txt']))
			jsonError('Отсутствует текст комментария');

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND !`parent_id`
				  AND `id`=".$note_id;
		if(!$note = query_assoc($sql))
			jsonError('Заметки не существует');

		$sql = "INSERT INTO `_note` (
					`app_id`,
					`parent_id`,
					`txt`,
					`user_id_add`
				) VALUES (
					".APP_ID.",
					".$note_id.",
					'".addslashes($txt)."',
					".USER_ID."
				)";
		query($sql);

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND `parent_id`=".$note_id."
				ORDER BY `id` DESC
				LIMIT 1";
		$send['html'] = _noteCommentUnit($el, query_assoc($sql));

		jsonSuccess($send);
		break;
	case 'note_comment_del'://удаление комментария
		if(!$note_id = _num($_POST['note_id']))
			jsonError('Некорректный ID комментария');

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND `parent_id`
				  AND `id`=".$note_id;
		if(!$note = query_assoc($sql))
			jsonError('Комментария не существует');

		if($note['deleted'])
			jsonError('Комментарий был удалён');

		$sql = "UPDATE `_note`
				SET `deleted`=1,
					`user_id_del`=".USER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$note_id;
		query($sql);

		jsonSuccess();
		break;
	case 'note_comment_rest'://восстановление комментария
		if(!$note_id = _num($_POST['note_id']))
			jsonError('Некорректный ID комментария');

		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND `parent_id`
				  AND `id`=".$note_id;
		if(!$note = query_assoc($sql))
			jsonError('Комментарий не существует');

		if(!$note['deleted'])
			jsonError('Комментарий не был удалён');

		$sql = "UPDATE `_note`
				SET `deleted`=0,
					`user_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$note_id;
		query($sql);

		jsonSuccess();
		break;

	case 'vk_user_get'://получение данных пользователя VK [300]
		if(empty($val = $_POST['val']))
			jsonError('Не получен ссылка на страницу пользователя');

		if(preg_match('/vk.com\//', $val)) {
			$ex = explode('vk.com/', $val);
			$val = $ex[1];
		}

		$res = _vkapi('users.get', array(
			'user_ids' => $val,
			'fields' => 'photo,'.
						'sex,'.
						'country,'.
						'city'
		));

		if(empty($res['response']))
			jsonError('Не получены данные из VK');

		$res = $res['response'][0];

		$send['html'] =
			'<table class="mt5">'.
				'<tr><td class="top pr5"><img src="'.$res['photo'].'" class="ava50">'.
					'<td class="top">'.
						'<a href="//vk.com/id'.$res['id'].'" class="b" target="_blank">'.
							$res['first_name'].' '.$res['last_name'].
						'</a>'.
						'<div class="clr1 mt3">'._elem300Place($res).'</div>'.
						'<button class="vk small mt3">выбрать</button>'.
			'</table>';

		$send['sel'] = _elem300Sel($res);
		$send['user_id'] = $res['id'];

		jsonSuccess($send);
		break;

	case 'attach_upload'://загрузка файла [28]
		/*
			Прикрепление файлов
			1 - успешно
			2 - неверный формат
			3 - загрузить не удалось
		*/

		$f = $_FILES['f1'];
		switch($f['type']) {
			case 'application/vnd.ms-excel':    //xls
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':       //xlsx
			case 'application/msword':          //doc
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': //docx
			case 'application/rtf':             //rtf
			case 'application/pdf':             //pdf
			case 'image/jpeg':
			case 'image/png':
			case 'application/octet-stream':    //pro100    .sto
				break;
			default:
				//неверный формат
				_cookie('_attached', 2, 3600);
				exit;
		}

		$ATTACH_PATH = APP_PATH.'/.attach/'.APP_ID.'/';
		$ATTACH_LINK = '//'.DOMAIN.APP_HTML.'/.attach/'.APP_ID.'/';


		if(!is_dir($ATTACH_PATH))
			mkdir($ATTACH_PATH, 0777, true);

		$fname = time().'_'.translit(trim($f['name'])); //имя файла, сохраняемое на диск

		if(move_uploaded_file($f['tmp_name'], $ATTACH_PATH.'/'.$fname)) {
			$sql = "INSERT INTO `_attach` (
						`app_id`,
						`path`,
						`link`,
						`oname`,
						`fname`,
						`size`,
						`user_id_add`
					) VALUES (
						".APP_ID.",
						'".addslashes($ATTACH_PATH)."',
						'".addslashes($ATTACH_LINK)."',
						'".addslashes(trim($f['name']))."',
						'".addslashes($fname)."',
						".$f['size'].",
						".USER_ID."
					)";
			$id = query_id($sql);

			//успешно
			_cookie('_attached', 1, 3600);
			_cookie('_attached_id', $id, 3600);
			exit;
		}

		//загрузить не удалось
		_cookie('_attached', 3, 3600);
		exit;
	case 'attach_get'://получение данных файла
		if(!$id = _num($_POST['id']))
			jsonError('Не получен id файла');

		$send['html'] = _attachLink($id);

		jsonSuccess($send);
		break;

	case 'el76_video'://получение готового фрейма для ролика Ютуб
		if(!$el = _elemOne($_POST['elem_id']))
			jsonError('Не получен id элемента');
		if(!$url = _txt(@$_POST['url']))
			jsonError('Отсутствует ссылка');
		if(!$send['iframe'] = _elem76iframe($el, $url))
			jsonError('Не получен код видео.');

		jsonSuccess($send);
		break;
	case 'el95_spisok':
		if(!$elem_id = _idsLast($_POST['elem_id']))
			jsonError('Некорректный ID элемента');

		$v = _txt($_POST['v']);

		$send['spisok'] = _elem95_spisok($elem_id, $v);
		jsonSuccess($send);
		break;
	case 'el97_move_save'://сохранение координат независимой кнопки
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if($el['dialog_id'] != 97)
			jsonError('Элемент не является независимой кнопкой');

		$x = _num(@$_POST['x'], 1);
		$y = _num(@$_POST['y'], 1);

		$sql = "UPDATE `_element`
				SET `num_3`=".$x.",
					`num_2`=".$y."
				WHERE `id`=".$elem_id;
		query($sql);

		_elemOne($elem_id, true);

		jsonSuccess();
		break;

	case 'el400_chart':
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$DLG = _dialogQuery($el['num_1']))
			return _emptyMinRed('График-столбики: диалога '.$el['num_1'].' не существует.');

		if(!$year = _num(@$_POST['year'])) {
			$send = array(
				'head' => _elem400_yearHead($el),
				'data' => _elem400_yearData($DLG),
				'cat' => _elem400_yearCat($DLG)
			);
			jsonSuccess($send);
		}

		if(!$mon = _num(@$_POST['mon'])) {
			$send = array(
				'head' => _elem400_monHead($el, $year),
				'data' => _elem400_monData($DLG, $year),
				'cat' => _elem400_monCat()
			);
			jsonSuccess($send);
		}

		$mon = $year.'-'._nol($mon);
		$send = array(
			'head' => _elem400_dayHead($el, $mon),
			'data' => _elem400_dayData($DLG, $mon),
			'cat' => _elem400_dayCat($mon)
		);

		jsonSuccess($send);
		break;


	case 'act228_block_upd'://действие 228: обновление содержимого блока
		if(!$block_id = _num($_POST['block_id']))
			jsonError('Некорректный id блока');

		$send['html'] = 'Ok.';

		jsonSuccess($send);
		break;
}

function _dialogSetupHistory($DLG) {//раздел История действий
	if(!isset($DLG['field1']['deleted']))
		return '';

	return
	'<div class="dialog-menu-2'._dn($DLG['menu_edit_last'] == 2).'">'.
		'<div class="pad10 pb20 bg11">'.
			'<div class="hd2 mt5">Внесение новой записи</div>'.
			'<div class="mt5 bg0 bor-e8 over1 curP" id="history_insert">'.
				'<div class="mar10 clr2'._dn(!$DLG['insert_history_elem']).'">шаблон истории действий для внесения новой записи</div>'.
				'<div class="mar10 msg">'._dialogSetupHistoryTmp($DLG['insert_history_elem']).'</div>'.
			'</div>'.
		'</div>'.
		'<div class="pad10 pb20 bg7 line-t1">'.
			'<div class="hd2 mt5">Редактирование записи</div>'.
			'<div class="mt5 bg0 bor-e8 over1 curP" id="history_edit">'.
				'<div class="mar10 clr2'._dn(!$DLG['edit_history_elem']).'">шаблон истории действий для редактирования записи</div>'.
				'<div class="mar10 msg">'._dialogSetupHistoryTmp($DLG['edit_history_elem']).'</div>'.
			'</div>'.
		'</div>'.
		'<div class="pad10 pb20 bg14 line-t1">'.
			'<div class="hd2 mt5">Удаление записи</div>'.
			'<div class="mt5 bg0 bor-e8 over1 curP" id="history_del">'.
				'<div class="mar10 clr2'._dn(!$DLG['del_history_elem']).'">шаблон истории действий для удаления записи</div>'.
				'<div class="mar10 msg">'._dialogSetupHistoryTmp($DLG['del_history_elem']).'</div>'.
			'</div>'.
		'</div>'.
	'</div>';
}
function _dialogSetupHistoryTmp($arr) {
	if(empty($arr))
		return '';

	$send = '';
	foreach(_ids($arr, 'arr') as $elem_id) {
		if(!$el = _elemOne($elem_id))
			continue;
		$title = '';
		if($el['dialog_id']) {
			$title = _element('title', $el);
			$cls = array('wsnw');
			$cls[] = $el['font'];
			$cls[] = $el['color'];
			$cls = implode(' ', $cls);
			$title = '<span class="'.$cls.'">'.$title.'</span>';
			$title = '['.$title.']';
		}
		$send .= $el['txt_7'].$title.$el['txt_8'];
	}

	return $send;
}

function _dialogSetupService($DLG) {
	return
	'<div class="dialog-menu-4 bg4'._dn($DLG['menu_edit_last'] == 4).'">'.

		'<div class="mt5 bg0">'.
            '<input type="hidden" id="menu_service" value="1" />'.
		'</div>'.

		'<div class="menu_service-1 pad10">'.
			'<table class="bs10">'.
				'<tr><td class="clr1 r">Колонка по умолчанию:'.
					'<td><input type="hidden" id="spisok_elem_id" value="'.$DLG['spisok_elem_id'].'" />'.
				'<tr><td class="clr1 r">Родительский диалог:'.
					'<td><input type="hidden" id="dialog_id_parent" value="'.$DLG['dialog_id_parent'].'" />'.
				'<tr><td>'.
					'<td>'._check(array(
								'attr_id' => 'is_unit_get',
								'title' => 'диалог получает данные записи',
								'value' => $DLG['is_unit_get']
						   )).

				'<tr><td colspan="2" class="line-t">&nbsp;'.
				'<tr><td>'.
					'<td>'._check(array(
								'attr_id' => 'open_auto',
								'title' => 'автоматическое открытие диалога',
								'value' => $DLG['open_auto']
						   )).
				'<tr id="tr-open-auto" class="'._dn($DLG['open_auto']).'">'.
					'<td class="r clr1">При условиях:'.
					'<td><div class="_spfl dib w125 prel">'.
							'<div class="icon icon-filter pabs"></div>'.
							'<div class="icon icon-del pl pabs'._dn($DLG['open_auto']).'"></div>'.
							'<input type="text"'.
								  ' id="open_filter"'.
								  ' readonly'.
								  ' class="inp clr7 b pl25 curP w100p over3"'.
								  ' placeholder="условий нет"'.
								  ' value=""'.
							' />'.
						'</div>'.
			'</table>'.
		'</div>'.

		'<div class="menu_service-2 pad10">'.
			'<table class="bs10">'.
				'<tr><td class="clr1 r">Записи:'.
					'<td>'._dialogSetupServiceCount($DLG).
				'<tr><td class="clr1 r top curD tool" data-tool="Размещён в других диалогах">Привязан:'.
					'<td>'._dialogSetupServiceCnnOut($DLG).
				'<tr><td class="clr1 r top curD tool" data-tool="Диалоги размещены в этом">Привязки:'.
					'<td>'._dialogSetupServiceCnnIn($DLG).
				'<tr><td class="clr1 r top tool" data-tool="Используется в кнопках">Кнопки:'.
					'<td>'._dialogSetupServiceButton($DLG).
			'</table>'.
		'</div>'.

	'</div>';
}
function _dialogSetupServiceCount($DLG) {//количество записей, внесённых диалогом
	if(!$DLG['table_1'])
		return '<span class="clr2">нет</span>';

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG, true);
	if(!$all = _num(query_value($sql)))
		return '<span class="clr2">нет</span>';

	$send = '<b>'.$all.'</b>';

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG);
	$noDel = _num(query_value($sql));

	if($del = $all - $noDel)
		$send .= '<span class="clr1">'.
					', из них: '.
					'<b class="clr11">'.$noDel.'</b> - активные, '.
					'<b class="clr8">'.$del.'</b> - удалены'.
				'</span>';
	else
		$send .= '<span class="clr2"> (удалённых нет)</span>';

	return $send;//' <a>очистить</a>'
}
function _dialogSetupServiceCnnOut($DLG) {//диалоги, к которым привязан текущий диалог
	//получение элементов-связок
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id` IN (29,59)
			  AND `num_1`=".$DLG['id']."
			ORDER BY `id`";
	if(!$ELM = query_arr($sql))
		return '<span class="clr2">нет</span>';

	//блоки, в которых размещаются элементы-связки. По ним будут определятся ID диалогов
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($ELM, 'block_id').")
			ORDER BY `obj_id`";
	if(!$BLK = query_arr($sql))
		return '<span class="clr2">нет</span>';

	foreach($ELM as $el) {
		if(!$block_id = $el['block_id'])
			continue;
		$BLK[$block_id]['col'] = _elemCol($el);
	}

	$send = '<table class="_stab small bg0">';
	$n = 1;
	foreach($BLK as $bl) {
		$send .= '<tr class="over2">'.
					'<td class="r clr2">'.$n++;

		if(!$dlg = _dialogQuery($bl['obj_id'])) {
			$send .= '<td class="clr8"><b>'.$bl['obj_id'].'</b> - диалог не найден<td>';
			continue;
		}

		if(empty($bl['col'])) {
			$send .= '<td class="clr8">Отсутствует колонка<td>';
			continue;
		}

		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($dlg)."
				WHERE "._queryWhere($dlg, true)."
				  AND `".$bl['col']."`";
		$c = _num(query_value($sql));

		$send .= '<td class="w230">'.
					$dlg['name'].
					($dlg['dialog_id_parent'] ? '<br><span class="clr13 fs11 b curD tool" data-tool="Родительский диалог">'._dialogParam($dlg['dialog_id_parent'], 'name').'</span>' : '').
				'<td class="r">'._hide0($c);
	}
	$send .= '</table>';

	return $send;
}
function _dialogSetupServiceCnnIn($DLG) {//диалоги, которые размещены в этом диалоге
	$ELM = array();

	foreach($DLG['cmp'] as $r)
		if($r['dialog_id'] == 29 || $r['dialog_id'] == 59)
			$ELM[] = $r;

	if(empty($ELM))
		return '<span class="clr2">нет</span>';

	$send = '<table class="_stab small bg0">';
	$n = 1;
	foreach($ELM as $el) {
		$send .= '<tr class="over2">'.
					'<td class="r clr2">'.$n++;

		if(!$dlg = _dialogQuery($el['num_1'])) {
			$send .= '<td class="clr8"><b>'.$el['num_1'].'</b> - диалог не найден<td>';
			continue;
		}

		if(!$col = _elemCol($el)) {
			$send .= '<td class="clr8">Отсутствует колонка<td>';
			continue;
		}

		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG, true)."
				  AND `".$col."`";
		$c = _num(query_value($sql));

		$send .=
			'<td class="w230">'.
					'<div class="clr1 b fs13">'.
						$el['name'].
						(!empty($el['req']) ? '<span class="clr5 fs16">*</span>' : '').
					'</div>'.
					$dlg['name'].
					($dlg['dialog_id_parent'] ? '<br><span class="clr13 fs11 b curD tool" data-tool="Родительский диалог">'._dialogParam($dlg['dialog_id_parent'], 'name').'</span>' : '').
				'<td class="r">'._hide0($c);
	}
	$send .= '</table>';

	return $send;
}
function _dialogSetupServiceButton($DLG) {//диалог используется в кнопках
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=2
			  AND `num_4`=".$DLG['id']."
			ORDER BY `id`";
	if(!$ELM = query_arr($sql))
		return '<span class="clr2">нет</span>';

	$send = '<table class="_stab small bg0">';
	$n = 1;
	foreach($ELM as $el) {
		$send .= '<tr class="over2">'.
					'<td class="r clr2">'.$n++;

		if($parent_id = $el['parent_id']) {
			$send .= '<td class="clr8">Не в блоке';
			continue;
		}

		if(!$el = _elemOne($el['id'])) {
			$send .= '<td class="clr8">Элемент не найден';
			continue;
		}

		if(!$bl = _blockOne($el['block_id'])) {
			$send .= '<td class="clr8">Элемент без блока';
			continue;
		}

		$objName = '---';
		switch($bl['obj_name']) {
			case 'page':
				$page = _page($bl['obj_id']);
				$objName = 'Страница <b>'.$page['name'].'<b>';
				break;
			case 'dialog':
				$objName = 'Диалог <b>'._dialogParam($bl['obj_id'], 'name').'<b>';
				break;
			case 'spisok':
				$objName = 'Список ';
				break;
		}

		$send .= '<td>'.$objName;

	}
	$send .= '</table>';

	return $send;
}

function _dialogSetupRule($dialog_id) {//Правила для элемета
	$sql = "SELECT `rule_id`,1
			FROM `_element_rule_use`
			WHERE `dialog_id`=".$dialog_id;
	$ass = query_ass($sql);

	$send = '';
	$sql = "SELECT *
			FROM `_element_rule_name`
			ORDER BY `sort`";
	foreach(query_arr($sql) as $id => $r) {
		$send .=
		'<tr><td class="w150">'.
			'<td>'._check(array(
						'attr_id' => 'element_rule_'.$id,
						'title' => $r['name'],
						'value' => _num(@$ass[$id])
				   ));
	}

	return
	'<div class="hd2 mar20 mb5">Правила для элемента:</div>'.
	'<table id="element-rule" class="bs5">'.$send.'</table>';
}

function _dialogSetupSa2($dialog) {//пункт меню настройки как элемента
	$group_id = _num(@$dialog['element_group_id']);
	return
	'<div class="menu_sa-2">'.
		'<table class="bs5">'.
			'<tr><td class="clr5 r w150">Группа элемента:'.
                '<td><input type="hidden" id="element_group_id" value="'.$group_id.'" />'.
		'</table>'.
		'<div class="elememt-setup'._dn($group_id).'">'.
		'<table class="bs5">'.
			'<tr><td class="clr5 r w150 topi">Изображение:'.
				'<td>'._dialogSetupElemImg($dialog).
			'<tr><td class="clr5 r">Начальная ширина:'.
				'<td><input type="hidden" id="element_width" value="'._num(@$dialog['element_width']).'" />'.
			'<tr><td class="clr5 r">Минимальная ширина:'.
				'<td><input type="hidden" id="element_width_min" value="'._num(@$dialog['element_width_min']).'" />'.
			'<tr><td class="clr5 r">Тип данных:'.
				'<td><input type="hidden" id="element_type" value="'._num(@$dialog['element_type']).'" />'.
			'<tr><td class="clr5 r">CMP-аффикс:'.
				'<td><input type="text" id="element_afics" class="w150" value="'.@$dialog['element_afics'].'" />'.

			'<tr><td>'.
				'<td class="pt10">'.
                       _check(array(
							'attr_id' => 'element_hidden',
							'title' => 'скрытый элемент',
							'value' => _num(@$dialog['element_hidden'])
					   )).
		'</table>'.
        _dialogSetupRule($dialog['id']).
		'</div>'.
	'</div>';
}
function _dialogSetupElemImg($dialog) {
	$img_id = _num(@$dialog['element_image_id']);

	return
	'<input type="hidden" id="element_image_id" value="'.$img_id.'" />'.
	'<div class="el-img'._dn(!$img_id, 'loaded').'">'.
		'<form>'.
			'<input type="file" accept="image/jpeg,image/png,image/gif,image/tiff" />'.
		'</form>'.
		'<div class="eimg'._dn($img_id, 'loaded').'">'.
			'<div class="icon icon-del-red eimg-del pabs r3 pl"></div>'.
			_imageFromId($img_id).
		'</div>'.
	'</div>';
}
function _dialogSetupUse($dialog) {//использование как элемента в других диалогах
	if(empty($dialog['element_group_id']))
		return '';

	return
	'<div class="menu_sa-3 ml20 mr20">'.
		_dialogSetupUseCount($dialog).
		_dialogSetupUseDialog($dialog).
		_dialogSetupUsePage($dialog).
	'</div>';
}
function _dialogSetupUseCount($dialog) {//количество использования элемента
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id`=".$dialog['id'];
	if($count = query_value($sql)) {
		$sql = "SELECT COUNT(DISTINCT `app_id`)
				FROM `_element`
				WHERE `dialog_id`=".$dialog['id'];
		$appC = query_value($sql);
		return
			'<div class="fs14">'.
				'Использование '.
				$count.' раз'._end($count, '', 'а', '').
				' в '.$appC.' приложени'._end($appC, 'ии', 'ях').'.'.
			'</div>';
	}
	return '';
}
function _dialogSetupUseDialog($dialog) {//использование элемента в диалогах
	$sql = "SELECT `block_id`
			FROM `_element`
			WHERE `dialog_id`=".$dialog['id'];
	if(!$block_ids = query_ids($sql))
		return '';

	$sql = "SELECT `id`
			FROM `_block`
			WHERE `id` IN (".$block_ids.")
			  AND `obj_name`='dialog'";
	if(!$block_ids = query_ids($sql))
		return '';

	//имена приложений
	$sql = "SELECT `id`,`name`
			FROM `_app`
			WHERE `id` IN (
				SELECT DISTINCT `app_id`
				FROM `_block`
				WHERE `id` IN (".$block_ids.")
			)";
	$appAss = query_ass($sql);

	//имена диалогов
	$sql = "SELECT `id`,`name`
			FROM `_dialog`
			WHERE `id` IN (
				SELECT DISTINCT `obj_id`
				FROM `_block`
				WHERE `id` IN (".$block_ids.")
			)";
	$dlgAss = query_ass($sql);

	$send = '';
	$app_id = -1;
	$sql = "SELECT
				DISTINCT `obj_id`,
				`app_id`,
				COUNT(`id`) `c`
			FROM `_block`
			WHERE `id` IN (".$block_ids.")
			GROUP BY `obj_id`
			ORDER BY `app_id`,`obj_id`";
	foreach(query_array($sql) as $r) {
		if($app_id != $r['app_id']) {
			$app_id = $r['app_id'];
			$send .= '<div class="b mt10 ml20 mb5">'.
						'<b class="fs14 clr9">app'.$app_id.'</b>'.
		($r['app_id'] ? '<span class="fs14 clr1 ml5">'.$appAss[$r['app_id']].'</span>' : '').
					 '</div>';
		}
		$count = $r['c'] > 1 ? ' <span class="clr1">('.$r['c'].'x)</span>' : '';
		$send .=
			'<div class="ml30">'.
				'<div class="dib w35 r mr5">'.$r['obj_id'].':</div>'.
				'<a class="dialog-open" val="dialog_id:'.$r['obj_id'].'">'.$dlgAss[$r['obj_id']].'</a>'.
				$count.
			'</div>';
	}

	return
	'<div class="fs14 clr11 mt15 bg17 pad5">В диалогах:</div>'.
	$send;
}
function _dialogSetupUsePage($dialog) {//использование элемента на страницах
	$sql = "SELECT `block_id`
			FROM `_element`
			WHERE `dialog_id`=".$dialog['id'];
	if(!$block_ids = query_ids($sql))
		return '';

	$sql = "SELECT `id`
			FROM `_block`
			WHERE `id` IN (".$block_ids.")
			  AND `obj_name`='page'";
	if(!$block_ids = query_ids($sql))
		return '';

	//имена приложений
	$sql = "SELECT `id`,`name`
			FROM `_app`
			WHERE `id` IN (
				SELECT DISTINCT `app_id`
				FROM `_block`
				WHERE `id` IN (".$block_ids.")
			)";
	$appAss = query_ass($sql);

	//имена страниц
	$sql = "SELECT `id`,`name`
			FROM `_page`
			WHERE `id` IN (
				SELECT DISTINCT `obj_id`
				FROM `_block`
				WHERE `id` IN (".$block_ids.")
			)";
	$pageAss = query_ass($sql);

	$send = '';
	$app_id = -1;
	$sql = "SELECT
				DISTINCT `obj_id`,
				`app_id`,
				COUNT(`id`) `c`
			FROM `_block`
			WHERE `id` IN (".$block_ids.")
			GROUP BY `obj_id`
			ORDER BY `app_id`,`obj_id`";
	foreach(query_array($sql) as $r) {
		if($app_id != $r['app_id']) {
			$app_id = $r['app_id'];
			$send .= '<div class="b mt10 ml20 mb5">'.
						'<b class="fs14 clr9">app'.$app_id.'</b>'.
		($r['app_id'] ? '<span class="fs14 clr1 ml5">'.$appAss[$r['app_id']].'</span>' : '').
					 '</div>';
		}
		$count = $r['c'] > 1 ? ' <span class="clr1">('.$r['c'].'x)</span>' : '';
		$send .=
			'<div class="ml30">'.
				'<div class="dib w35 r mr5">'.$r['obj_id'].':</div>'.
				'<a class="" href="'.URL.'&p='.$r['obj_id'].'">'.$pageAss[$r['obj_id']].'</a>'.
				$count.
			'</div>';
	}


	return
	'<div class="fs14 clr11 mt15 bg17 pad5">На страницах:</div>'.
	$send;
}
function _dialogSave($dialog_id) {//сохранение диалога
	if(!_dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	/* ---=== Настройки внесения данных ===--- */
	$insert_on = _bool($_POST['insert_on']);
	if(!$insert_head = _txt($_POST['insert_head']))
		jsonError('Не указан заголовок для внесения записи');
	$insert_button_submit = _txt($_POST['insert_button_submit']);
	if(!$insert_button_cancel = _txt($_POST['insert_button_cancel']))
		jsonError('Не указан текст кнопки отмены для новой записи');
	$insert_action_id = _num($_POST['insert_action_id']);
	$insert_action_obj_id = _num($_POST['insert_action_obj_id']);
	$insert_unit_id_set_elem_id = _num($_POST['insert_unit_id_set_elem_id']);


	/* ---=== Настройки редактирования данных ===--- */
	$edit_on = _bool($_POST['edit_on']);
	if(!$edit_head = _txt($_POST['edit_head']))
		jsonError('Не указан заголовок редактирования');
	$edit_button_submit = _txt($_POST['edit_button_submit']);
	if(!$edit_button_cancel = _txt($_POST['edit_button_cancel']))
		jsonError('Не указан текст кнопки отмены редактирования');
	$edit_action_id = _num($_POST['edit_action_id']);
	$edit_action_obj_id = _num($_POST['edit_action_obj_id']);


	/* ---=== Настройки удаления данных ===--- */
	$del_on = _bool($_POST['del_on']);
	if(!$del_head = _txt($_POST['del_head']))
		jsonError('Не указан заголовок удаления');
	if(!$del_button_submit = _txt($_POST['del_button_submit']))
		jsonError('Не указан текст кнопки удаления');
	if(!$del_button_cancel = _txt($_POST['del_button_cancel']))
		jsonError('Не указан текст кнопки отмены удаления');
	$del_action_id = _num($_POST['del_action_id']);
	$del_action_obj_id = _num($_POST['del_action_obj_id']);


	/* ---=== Настройки ширины ===--- */
	if(!$width = _num($_POST['width']))
		jsonError('Некорректное значение ширины диалога');
	if($width < 480 || $width > 980)
		jsonError('Установлена недопустимая ширина диалога');

	if(!$name = _txt($_POST['name']))
		jsonError('Укажите имя диалогового окна');

	$spisok_elem_id = _num($_POST['spisok_elem_id']);
	$open_auto = _bool($_POST['open_auto']);

	$dialog_id_parent =   _num($_POST['dialog_id_parent']);
	if($dialog_id_parent == $dialog_id)
		jsonError('Диалог не может родительским для самого себя');

	$is_unit_get = _bool($_POST['is_unit_get']);

	$menu_edit_last = _num($_POST['menu_edit_last']);

	$sql = "UPDATE `_dialog`
			SET `dialog_id_parent`=".$dialog_id_parent.",
				`name`='".addslashes($name)."',
				`width`=".$width.",

				`insert_on`=".$insert_on.",
				`insert_head`='".addslashes($insert_head)."',
				`insert_button_submit`='".addslashes($insert_button_submit)."',
				`insert_button_cancel`='".addslashes($insert_button_cancel)."',
				`insert_action_id`=".$insert_action_id.",
				`insert_action_obj_id`=".$insert_action_obj_id.",
				`insert_unit_id_set_elem_id`=".$insert_unit_id_set_elem_id.",

				`edit_on`=".$edit_on.",
				`edit_head`='".addslashes($edit_head)."',
				`edit_button_submit`='".addslashes($edit_button_submit)."',
				`edit_button_cancel`='".addslashes($edit_button_cancel)."',
				`edit_action_id`=".$edit_action_id.",
				`edit_action_obj_id`=".$edit_action_obj_id.",

				`del_on`=".$del_on.",
				`del_head`='".addslashes($del_head)."',
				`del_button_submit`='".addslashes($del_button_submit)."',
				`del_button_cancel`='".addslashes($del_button_cancel)."',
				`del_action_id`=".$del_action_id.",
				`del_action_obj_id`=".$del_action_obj_id.",

				`spisok_elem_id`=".$spisok_elem_id.",
				`open_auto`=".$open_auto.",

				`is_unit_get`=".$is_unit_get.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_dialogSaveSA($dialog_id);

	_BE('dialog_clear');
	_cache_clear('RULE_USE', 1);

	return $dialog_id;
}
function _dialogSaveSA($dialog_id) {//сохрание настроек диалога SA
	if(!SA)
		return;

	$spisok_any = _bool($_POST['spisok_any']);
	$sa = _bool($_POST['sa']);
	$parent_any = _bool($_POST['parent_any']);
	$width_auto = _num($_POST['width_auto']);
	$cmp_no_req = _num($_POST['cmp_no_req']);
	$app_any = _num($_POST['app_any']);
	$clone_on = _num($_POST['clone_on']);

	if($table_1 = _num($_POST['table_1'])) {
		if(!$table = _table($table_1))
			jsonError('Указана несуществующая таблица 1');
		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!query_array($sql))
			jsonError('Указана несуществующая таблица 1: "'.$table.'"');
	}

	$element_group_id = _num($_POST['element_group_id']);
	$element_image_id = _num($_POST['element_image_id']);
	$element_width = _num($_POST['element_width']);
	$element_width_min = _num($_POST['element_width_min']);
	$element_type = _num($_POST['element_type']);
	$element_afics = _txt($_POST['element_afics']);
	$element_hidden = _num($_POST['element_hidden']);

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`spisok_any`=".$spisok_any.",
				`sa`=".$sa.",
				`parent_any`=".$parent_any.",
				`width_auto`=".$width_auto.",
				`cmp_no_req`=".$cmp_no_req.",

				`table_1`=".$table_1.",
				`clone_on`=".$clone_on.",

				`element_group_id`=".$element_group_id.",
				`element_image_id`=".$element_image_id.",
				`element_width`=".$element_width.",
				`element_width_min`=".$element_width_min.",
				`element_type`=".$element_type.",
				`element_afics`='".addslashes($element_afics)."',
				`element_hidden`=".$element_hidden."
			WHERE `id`=".$dialog_id;
	query($sql);

	//Обновление правил элемента
	$sql = "DELETE FROM `_element_rule_use` WHERE `dialog_id`=".$dialog_id;
	query($sql);
	if($element_group_id)
		if($ids = _ids($_POST['element_rule'], 'arr'))
			foreach($ids as $id) {
				$sql = "INSERT INTO `_element_rule_use`
							(`dialog_id`,`rule_id`)
						VALUES
							(".$dialog_id.",".$id.")";
				query($sql);
			}
}

function _dialogOpenParam($dlg) {//все возможные параметны для диалогового окна
	return array(
		'dialog_id' => $dlg['id'],
		'width' => 0,               //ширина диалога
		'setup_access' => _dialogSetupAccess($dlg), //права на редактирование диалога

		'head' => $dlg['insert_head'],
		'html' => '',               //содержание диалога
		'button_submit' => '',
		'button_cancel' => 'Закрыть',


		'get_id' => 0,              //id просматриваемой записи
		'edit_id' => 0,             //id редактируемой записи
		'del_id' => 0,              //id записи для удаления

		'unit' => array(),          //содержание записи (редактируемой или просматриваемой)

		'dlgerr' => 0,              //флаг ошибки
		'vvv' => array()            //содержание для элементов
	);
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет прав');

	$send = _dialogOpenParam($dialog);

	$page_id = _num($_POST['page_id']);

	$prm['srce']['dialog_id'] = $dialog_id;
	$prm['srce']['page_id'] = $page_id;
	$prm['srce']['block_id'] = _num(@$_POST['block_id']);
	$prm['srce']['element_id'] = _num(@$_POST['element_id']);

	/* --- Удаление записи --- */
	if($del_id = _num(@$_POST['del_id'])) {
		$send['del_id'] = $del_id;
		$send['width'] = _blockObjWidth('dialog_del');
		$send['head'] = $dialog['del_head'];

		if(!$dialog['del_on'])
			return _dialogOpenErr($send, 'Удаление записи запрещено.');
		if(!$unit = _spisokUnitQuery($dialog, $del_id))
			return _dialogOpenErr($send, 'Записи '.$del_id.' не существует.');
		if($dialog['del_cond']['num_2']) {
			$day = explode(' ', $unit['dtime_add']);
			if(TODAY != $day[0])
				return _dialogOpenErr($send, 'Время удаления записи истекло.');
		}

		$send['html'] = _dialogOpenUnitDelHtml($dialog, $unit);
		$send['button_submit'] = $dialog['del_button_submit'];
		$send['button_cancel'] = $dialog['del_button_cancel'];

		return $send;
	}

	$send['width'] = $dialog['width_auto'] ? 0 : _num($dialog['width']);

	$prm['srce']['dss'] = _num(@$_POST['dss']);

	$prm['dop'] = _arrNum(@$_POST['dop']);
	$prm['srce']['dop'] = $prm['dop'];

	//выбранные значения галочками (глобальная переменная CHK)
	$prm['srce']['chk'] = _arrNum(@$_POST['chk']);

	$get_id = _num(@$_POST['get_id']);

	if($dialog['is_unit_get'] && !$get_id)
		return _dialogOpenErr($send, 'Не получены данные записи');

	//если получен id записи
	if($get_id) {
		$send['get_id'] = $get_id;
		$prm['unit_get_id'] = $get_id;
		if($dialog['is_unit_get'])
			$prm['unit_get'] = _spisokUnitQuery($dialog, $get_id);
	}

	/* --- Редактирование записи --- */
	if($edit_id = _num(@$_POST['edit_id'])) {
		$send['head'] = $dialog['edit_head'];

		if(!$dialog['edit_on'])
			return _dialogOpenErr($send, 'Редактирование записи запрещено.');
		if(!$prm['unit_edit'] = _spisokUnitQuery($dialog, $edit_id))
			return _dialogOpenErr($send, 'Записи '.$dialog['id'].':'.$edit_id.' не существует.');

		$send['edit_id'] = $edit_id;

		$send['unit'] = $prm['unit_edit'];
		$prm['srce']['block_id'] = _dialogOpenBlockIdUpd($dialog, $prm);

		$send['html'] = _blockHtml('dialog', $dialog['id'], $prm);
		$send['button_submit'] = $dialog['edit_button_submit'];
		$send['button_cancel'] = $dialog['edit_button_cancel'];

		$send['jsblk'] = _BE('block_arr', 'dialog', $dialog_id);
		$send['jselm'] = _elmJs('dialog', $dialog_id, $prm);
		$send['focus'] = _elemJsFocus('dialog', $dialog_id);
		$send['hint'] = _hintMass();

		$prm = _blockParam($prm);
		$send['srce'] = $prm['srce'];

		return $send;
	}







	/* --- Внесение новой записи --- */
	if(!$dialog['insert_on'])
		return _dialogOpenErr($send, 'Внесение новой записи запрещено.');

	$send['html'] = _blockHtml('dialog', $dialog_id, $prm);
	$send['button_submit'] = $dialog['insert_button_submit'];
	$send['button_cancel'] = $dialog['insert_button_cancel'];

	$send['jsblk'] = _BE('block_arr', 'dialog', $dialog_id);
	$send['jselm'] = _elmJs('dialog', $dialog_id, $prm);
	$send['focus'] = _elemJsFocus('dialog', $dialog_id);
	$send['hint'] = _hintMass();

	$prm = _blockParam($prm);
	$send['srce'] = $prm['srce'];

	return $send;
}
function _dialogOpenUnitDelHtml($dialog, $unit) {//содержание диалога при удалении единицы списка
	if(!$block = _BE('block_obj', 'dialog_del', $dialog['id']))
		return
		'<div class="pad20">'.
			'<div class="_info">'.
				'<div class="fs15 center clr8 pad30">'.
					'Подтвердите удаление.'.
				'</div>'.
			'</div>'.
		'</div>';

	$prm['unit_get'] = $unit;

	return _blockLevel($block, $prm);
}
function _dialogOpenErr($send, $msg) {//формирование сообщения об ошибке при открытии диалога
	$send['dlgerr'] = 1;
	$send['html'] = '<div class="pad10">'._empty($msg).'</div>';
	return $send;
}
function _dialogOpenBlockIdUpd($dlg, $prm) {//обновление ID блока, если требуется
	$block_id = $prm['srce']['block_id'];

	if($block_id < 0)
		echo 'block_id = MINUS!!!';

	$u = $prm['unit_edit'];

	if(!$block_id && isset($dlg['field1']['block_id']))
		$block_id = _num($u['block_id']);

	if(!$block_id && isset($dlg['field1']['element_id']))
		if($EL = _elemOne($u['element_id']))
			$block_id = _num($EL['block_id']);

	return $block_id;
}

function _dialogWidthMin($blk) {//получение минимальной ширины диалога на основании корневых блоков
	$width = 480;
	if(empty($blk))
		return $width;

	foreach($blk as $r) {
		if($r['parent_id'])
			continue;
		$w = $r['x'] * 10 + $r['width'];
		if($width < $w)
			$width = $w;
	}

	return $width;
}
function _dialogSetupAccess($dlg) {//права для настройки диалога
	if(APP_IS_PID)
		return 0;
	if(SA)
		return 1;
	if(!$dlg['app_id'])
		return 0;
	if($dlg['app_id'] == APP_ID && USER_ADMIN)
		return 1;
	return 0;
}



