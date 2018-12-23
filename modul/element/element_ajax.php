<?php
switch(@$_POST['op']) {
	case 'dialog_setup_load'://получение данных диалога для его настройки
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');
		if(!$dialog = _dialogQuery($dialog_id))
			jsonError('Диалога не существует');

		$menu = array(
			1 => 'Диалог',
			2 => 'История',
			3 => 'Содержание',
	  		4 => 'Служебное',
			9 => '<b class=red>SA</b>'
		);
		$action = array(//действие, которое будет происходить после внесения или изменения единицы списка
			3 => 'Обновить содержимое блоков',
			1 => 'Обновить страницу',
			2 => 'Перейти на страницу',
			4 => 'Обновить исходный диалог'
		);

		if(!SA) {
			unset($menu[9]);
			unset($action[4]);
		}
		if(!isset($dialog['field1']['deleted']))
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
					'content' => '<div class="'._dn(!$r['sa'], 'red').'">'._br($r['name']).'</div>'
				);
			}

			$menu_sa = array(
				1 => 'Диалог',
				2 => 'Элемент',
				3 => 'Использование'
			);
			if(!$dialog['element_group_id'])
				unset($menu_sa[3]);
		}

		//условия удаления
		$sql = "SELECT `id`
				FROM `_element`
				WHERE `dialog_id`=58
				  AND `num_1`=".$dialog_id."
				LIMIT 1";
		$del58 = _num(query_value($sql));

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<div class="pad10 center bg-gr3 line-b">'.
				'<div class="fr mt5 b pale curD dw'._tooltip('Ширина диалога', -50).$dialog['width'].'</div>'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1'._dn($dialog['menu_edit_last'] == 1).'">'.

				'<div class="pad10 bg-gr2 line-b">'.
					'<table class="bs5 w100p">'.
						'<tr><td class="w150 r color-555">Имя диалогового окна:'.
							'<td><input type="text" id="dialog_name" class="w100p b" maxlength="100" value="'.$dialog['name'].'" />'.
					'</table>'.
				'</div>'.

				'<div class="pad10 bg-dfd">'.
					'<div class="hd2 mt5">'.
						'Внесение новой записи'.
						'<div class="fr">'.
							'<input type="hidden" id="insert_on" value="'.$dialog['insert_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['insert_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="grey w150 r">Заголовок:'.
								'<td><input type="text" id="insert_head" class="w100p" maxlength="200" placeholder="название диалогового окна - новая запись" value="'.$dialog['insert_head'].'" />'.
							'<tr><td class="grey r">Текст кнопок:'.
								'<td><input type="text" id="insert_button_submit" class="w150" maxlength="100" value="'.$dialog['insert_button_submit'].'" />'.
									'<input type="text" id="insert_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['insert_button_cancel'].'" />'.
							'<tr><td class="blue r">Дальнейшее действие:'.
								'<td><input type="hidden" id="insert_action_id" value="'.$dialog['insert_action_id'].'" />'.
							'<tr class="td-insert-action-page'._dn($dialog['insert_action_id'] == 2).'">'.
								'<td class="grey r">Страница:'.
								'<td><input type="hidden" id="insert_action_page_id" value="'.$dialog['insert_action_page_id'].'" />'.
						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="bg-ffd line-t1 pad10">'.
					'<div class="hd2 mt5">'.
						'Редактирование записи'.
						'<div class="fr">'.
							'<input type="hidden" id="edit_on" value="'.$dialog['edit_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['edit_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="grey w150 r">Заголовок:'.
								'<td><input type="text" id="edit_head" class="w100p" maxlength="200" placeholder="название диалогового окна - редактирование" value="'.$dialog['edit_head'].'" />'.
							'<tr><td class="grey r">Текст кнопок:'.
								'<td><input type="text" id="edit_button_submit" class="w150" maxlength="100" value="'.$dialog['edit_button_submit'].'" />'.
									'<input type="text" id="edit_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['edit_button_cancel'].'" />'.
							'<tr><td class="blue r">Дальнейшее действие:'.
								'<td><input type="hidden" id="edit_action_id" value="'.$dialog['edit_action_id'].'" />'.
							'<tr class="td-edit-action-page'._dn($dialog['edit_action_id'] == 2).'">'.
								'<td class="grey r">Страница:'.
								'<td><input type="hidden" id="edit_action_page_id" value="'.$dialog['edit_action_page_id'].'" />'.
						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="bg-fee line-t1 pad10">'.
					'<div class="hd2 mt5">'.
						'Удаление записи'.
						'<div class="fr">'.
							'<input type="hidden" id="del_on" value="'.$dialog['del_on'].'" />'.
						'</div>'.
					'</div>'.
					'<div class="'._dn($dialog['del_on']).'">'.
						'<table class="bs5 w100p">'.
							'<tr><td class="grey w150 r">Заголовок:'.
								'<td><input type="text" id="del_head" class="w100p" maxlength="200" placeholder="название диалогового окна - удаление" value="'.$dialog['del_head'].'" />'.
							'<tr><td class="grey r">Текст кнопок:'.
								'<td><input type="text" id="del_button_submit" class="w150" maxlength="100" value="'.$dialog['del_button_submit'].'" />'.
									'<input type="text" id="del_button_cancel" class="w125 ml5" maxlength="100" value="'.$dialog['del_button_cancel'].'" />'.
							'<tr><td class="grey r h35">Содержание удаления:'.
								'<td>'._dialogContentDelSetup($dialog_id).
							'<tr><td class="grey r">Условия удаления:'.
								'<td class="pale">'.
									($del58 ? '' : 'условий нет. ').
									'<div val="dialog_id:58,dss:'.$dialog_id.',edit_id:'.$del58.'" class="icon icon-set pl dialog-open'._tooltip('Настроить условия', -59).'</div>'.
							'<tr><td class="blue r">Дальнейшее действие:'.
								'<td><input type="hidden" id="del_action_id" value="'.$dialog['del_action_id'].'" />'.
							'<tr class="td-del-action-page'._dn($dialog['del_action_id'] == 2).'">'.
								'<td class="grey r">Страница:'.
								'<td><input type="hidden" id="del_action_page_id" value="'.$dialog['del_action_page_id'].'" />'.
						'</table>'.
					'</div>'.
				'</div>'.
			'</div>'.

			//История действий
			'<div class="dialog-menu-2'._dn($dialog['menu_edit_last'] == 2).'">'.
				'<div class="pad10 pb20 bg-dfd">'.
					'<div class="hd2 mt5">Внесение новой записи</div>'.
					'<div class="mt5 bg-fff bor-e8 over1 curP" id="history_insert">'.
						'<div class="mar10 pale'._dn(!$dialog['insert_history_tmp']).'">шаблон истории действий для внесения новой записи</div>'.
						'<div class="mar10 msg">'.$dialog['insert_history_tmp'].'</div>'.
					'</div>'.
				'</div>'.
				'<div class="pad10 pb20 bg-ffd line-t1">'.
					'<div class="hd2 mt5">Редактирование записи</div>'.
					'<div class="mt5 bg-fff bor-e8 over1 curP" id="history_edit">'.
						'<div class="mar10 pale'._dn(!$dialog['edit_history_tmp']).'">шаблон истории действий для редактирования записи</div>'.
						'<div class="mar10 msg">'.$dialog['edit_history_tmp'].'</div>'.
					'</div>'.
				'</div>'.
				'<div class="pad10 pb20 bg-fee line-t1">'.
					'<div class="hd2 mt5">Удаление записи</div>'.
					'<div class="mt5 bg-fff bor-e8 over1 curP" id="history_del">'.
						'<div class="mar10 pale'._dn(!$dialog['del_history_tmp']).'">шаблон истории действий для удаления записи</div>'.
						'<div class="mar10 msg">'.$dialog['del_history_tmp'].'</div>'.
					'</div>'.
				'</div>'.
			'</div>'.

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
			'<div class="dialog-menu-4 bg-gr2 pad10'._dn($dialog['menu_edit_last'] == 4).'">'.
				'<table class="bs10">'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'spisok_on',
									'title' => 'диалог вносит данные',
									'value' => $dialog['spisok_on']
							   )).
					'<tr class="tr-spisok-col'._dn($dialog['spisok_on']).'">'.
						'<td class="grey r">Колонка по умолчанию:'.
						'<td><input type="hidden" id="spisok_elem_id" value="'.$dialog['spisok_elem_id'].'" />'.
					'<tr><td class="grey r">Родительский диалог:'.
						'<td><input type="hidden" id="dialog_id_parent" value="'.$dialog['dialog_id_parent'].'" />'.

					'<tr><td colspan="2">&nbsp;'.
					'<tr><td colspan="2" class="line-t">&nbsp;'.
					'<tr><td class="grey r">Получает данные записи:'.
						'<td><input type="hidden" id="dialog_id_unit_get" value="'.$dialog['dialog_id_unit_get'].'" />'.
				'</table>'.
			'</div>'.

			//SA
	  (SA ? '<div class="dialog-menu-9 pb20'._dn($dialog['menu_edit_last'] == 9).'">'.
				'<div class="mt5 mb10 ml20 mr20">'.
		            '<input type="hidden" id="menu_sa" value="1" />'.
				'</div>'.

				'<table class="menu_sa-1 bs10">'.
					'<tr><td class="red r w80">ID:<td class="b">'.$dialog['id'].
					'<tr><td class="red r">Ширина:'.
		                '<td><div id="dialog-width" class="dib w50">'.$dialog['width'].'</div>'.
		                    '<input type="hidden" id="width_auto" value="'.$dialog['width_auto'].'" />'.
					'<tr><td class="red r">Таблица 1:'.
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
									'attr_id' => 'parent_any',
									'title' => 'может быть выбран родительским во всех приложениях',
									'value' => $dialog['parent_any']
							   )).
				'</table>'.


				'<div class="menu_sa-2">'.
					'<table class="bs5">'.
						'<tr><td class="red r w150">Группа элемента:'.
			                '<td><input type="hidden" id="element_group_id" value="'.$dialog['element_group_id'].'" />'.
					'</table>'.
					'<div class="elememt-setup'._dn($dialog['element_group_id']).'">'.
					'<table class="bs5">'.
						'<tr><td class="red r w150">Начальная ширина:'.
							'<td><input type="hidden" id="element_width" value="'.$dialog['element_width'].'" />'.
						'<tr><td class="red r">Минимальная ширина:'.
							'<td><input type="hidden" id="element_width_min" value="'.$dialog['element_width_min'].'" />'.
						'<tr><td class="red r">Тип данных:'.
							'<td><input type="hidden" id="element_type" value="'.$dialog['element_type'].'" />'.
						'<tr><td class="red r">CMP-аффикс:'.
							'<td><input type="text" id="element_afics" class="w150" value="'.$dialog['element_afics'].'" />'.
						'<tr><td class="red r">Диалог для функций:'.
							'<td><input type="hidden" id="element_action_dialog_id" value="'.$dialog['element_action_dialog_id'].'" />'.

						'<tr><td>'.
							'<td class="pt10">'.
			                       _check(array(
										'attr_id' => 'element_hidden',
										'title' => 'скрытый элемент',
										'value' => $dialog['element_hidden']
								   )).
					'</table>'.
		            _dialogSetupRule($dialog_id).
					'</div>'.
				'</div>'.

				_dialogEditLoadUse($dialog).

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
		$send['dlg_func'] = _dialogSelArray('dlg_func');
		$send['dlg_spisok_on'] = _dialogSelArray('spisok_only', $dialog_id);
		$send['spisok_cmp'] = _dialogSpisokCmp($dialog['cmp']);

		$dlgUnitGet = _dialogSelArray('unit_get', $dialog_id);
		array_unshift($dlgUnitGet, array(
			'id' => -1,
			'title' => 'Совпадает с текущей страницей',
			'content' => '<div class="b color-pay">Совпадает с текущей страницей</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Диалог будет принимать данные списка, которые принимает страница</div>'
		));
		$send['dlg_unit_get'] = $dlgUnitGet;

		jsonSuccess($send);
		break;
	case 'dialog_setup_save'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		_dialogSave($dialog_id);

		$send = _dialogOpenLoad($dialog_id);

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

		_cache_clear( 'page');
		_jsCache();

		jsonSuccess();
		break;

	case 'image_upload'://добавление изображения
		if(!$obj_name = _txt(@$_POST['obj_name']))
			jsonError('Отсутствует имя объекта');
		if(!$f = @$_FILES['f1'])
			jsonError('Файл отсутствует');
		if($f['size'] > 15728640)
			jsonError('Размер изображения не должен быть более 15 Мб');

		$obj_id = _num(@$_POST['obj_id']);

		$img = _imageSave($obj_name, $obj_id, $f['type'], $f['tmp_name']);

		$send['html'] = _imageDD($img);

		jsonSuccess($send);
		break;
	case 'image_link'://загрузка изображения по ссылке
		if(!$url = _txt(@$_POST['url']))
			jsonError('Отсутствует ссылка');
		if(!$obj_name = _txt(@$_POST['obj_name']))
			jsonError('Отсутствует имя объекта');

		$obj_id = _num(@$_POST['obj_id']);

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
		    CURLOPT_TIMEOUT => 60,//максимальное время работы cURL
		    CURLOPT_FOLLOWLOCATION => 1,//следовать перенаправлениям
		    CURLOPT_RETURNTRANSFER => 1,//результат писать в переменную
		    CURLOPT_NOPROGRESS => 0,//индикатор загрузки данных
		    CURLOPT_BUFFERSIZE => 1024,//размер буфера 1 Кбайт
		    //функцию для подсчёта скачанных данных. Подробнее: http://stackoverflow.com/a/17642638
		    CURLOPT_PROGRESSFUNCTION => function ($ch, $dwnldSize, $dwnld, $upldSize) {
		        if($dwnld > 1024 * 1024 * 15)//Когда будет скачано больше 15 Мбайт, cURL прервёт работу
		            return 1;
		        return 0;
		    },
		    CURLOPT_SSL_VERIFYPEER => 0//проверка сертификата
	//	    CURLOPT_SSL_VERIFYHOST => 2,//имя сертификата и его совпадение с указанным хостом
	//	    CURLOPT_CAINFO => __DIR__ . '/cacert.pem'//сертификат проверки. Скачать: https://curl.haxx.se/docs/caextract.html
		));

		//код последней ошибки
		if(curl_errno($ch))
			jsonError('При загрузке произошла ошибка');

		$raw   = curl_exec($ch);    //данные в переменную
		$info  = curl_getinfo($ch); //информация об операции
		curl_close($ch);//завершение сеанса cURL

		if(!is_dir(APP_PATH.'/.tmp'))
			mkdir(APP_PATH.'/.tmp', 0777, true);

		$file_tmp_name = APP_PATH.'/.tmp/'.USER_ID.'.tmp';
		$file = fopen($file_tmp_name,'w');
		fwrite($file, $raw);
		fclose($file);

		$img = _imageSave($obj_name, $obj_id, $info['content_type'], $file_tmp_name);
		unlink($file_tmp_name);

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

		if(!$img['deleted'])
			jsonError('Изображение не было удалено');

		$send['html'] = _imageDD($img);
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

		$send['mon'] = $mon;
		$send['td_mon'] = _filterCalendarMon($mon);
		$send['cnt'] = _filterCalendarContent($el, $mon, _spisokFilter('v', $el['id']));

		jsonSuccess($send);
		break;

	case 'note_add'://добавление заметки
		if(!$page_id = _num($_POST['page_id']))
			jsonError('Некорректный ID страницы');
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

		$send['html'] = _noteList($page_id, $obj_id);

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
		$comm = _noteCommentUnit(query_assoc($sql));

		$send['html'] = $comm;

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
		if(!$user_id = _num($_POST['val']))
			jsonError('Не получен ID пользователя');

		$res = _vkapi('users.get', array(
			'user_ids' => $user_id,
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
						'<a href="//vk.com/id'.$user_id.'" class="b" target="_blank">'.
							$res['first_name'].' '.$res['last_name'].
						'</a>'.
						'<div class="grey mt3">'._elem300Place($res).'</div>'.
						'<button class="vk small mt3">выбрать</button>'.
			'</table>';

		$send['sel'] = _elem300Sel($res);
		$send['user_id'] = $user_id;

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
				setcookie('_attached', 2, time() + 3600, '/');
				exit;
		}

		$ATTACH_PATH = APP_PATH.'/.attach/'.APP_ID;


		if(!is_dir($ATTACH_PATH))
			mkdir($ATTACH_PATH, 0777, true);

		$fname = time().'_'.translit(trim($f['name'])); //имя файла, сохраняемое на диск

		if(move_uploaded_file($f['tmp_name'], $ATTACH_PATH.'/'.$fname)) {
			$sql = "INSERT INTO `_attach` (
						`app_id`,
						`name`,
						`size`,
						`link`,
						`user_id_add`
					) VALUES (
						".APP_ID.",
						'".addslashes(trim($f['name']))."',
						".$f['size'].",
						'".addslashes($ATTACH_PATH.'/'.$fname)."',
						".USER_ID."
					)";
			$id = query_id($sql);

			//успешно
			setcookie('_attached', 1, time() + 3600, '/');
			setcookie('_attached_id', $id, time() + 3600, '/');
			exit;
		}

		//загрузить не удалось
		setcookie('_attached', 3, time() + 3600, '/');
		exit;
	case 'attach_get'://получение данных файла
		if(!$id = _num($_POST['id']))
			jsonError('Не получен id файла');

		$sql = "SELECT *
				FROM `_attach`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Файла '.$id.' не существует');

		jsonSuccess($r);
		break;
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


function _dialogEditLoadUse($dialog) {//использование как элемента в других диалогах
	if(!$dialog['element_group_id'])
		return '';

	$use_dialog = '';
	$use_page = '';
	$sql = "SELECT `block_id`
			FROM `_element`
			WHERE `dialog_id`=".$dialog['id'];
	if($block_ids = query_ids($sql)) {
		$sql = "SELECT
					DISTINCT `obj_id`,
					`obj_name`,
					COUNT(`id`) `c`
				FROM `_block`
				WHERE `id` IN (".$block_ids.")
				GROUP BY `obj_name`,`obj_id`
				ORDER BY `obj_name`,`obj_id`";
		foreach(query_array($sql) as $r) {
			$count = $r['c'] > 1 ? ' <span class="grey">('.$r['c'].'x)</span>' : '';
			switch($r['obj_name']) {
				case 'dialog':
					$use_dialog .=
						'<div>'.
							'<div class="dib w35 mr5">'.$r['obj_id'].':</div>'.
							'<a class="dialog-open" val="dialog_id:'.$r['obj_id'].'">'._dialogParam($r['obj_id'], 'name').'</a>'.
							$count.
						'</div>';
					break;
				case 'page':
					if(!$p = _page($r['obj_id']))
						break;
					$use_page .=
						'<div>'.
							'<div class="dib w35 mr5">'.$r['obj_id'].':</div>'.
							'<a class="'._dn(!$p['sa'], 'color-ref').'" href="'.URL.'&p='.$r['obj_id'].'">'.$p['name'].'</a>'.
							$count.
						'</div>';
					break;
			}
		}
	}

	return
	'<table class="menu_sa-3 bs10">'.
		'<tr><td class="w125 r color-pay top">В диалогах:'.
			'<td>'.($use_dialog ? $use_dialog : '-').
		'<tr><td class="r color-pay top">На страницах:'.
			'<td>'.($use_page ? $use_page : '-').
	'</table>';
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
	$insert_action_page_id = _num($_POST['insert_action_page_id']);


	/* ---=== Настройки редактирования данных ===--- */
	$edit_on = _bool($_POST['edit_on']);
	if(!$edit_head = _txt($_POST['edit_head']))
		jsonError('Не указан заголовок редактирования');
	$edit_button_submit = _txt($_POST['edit_button_submit']);
	if(!$edit_button_cancel = _txt($_POST['edit_button_cancel']))
		jsonError('Не указан текст кнопки отмены редактирования');
	$edit_action_id = _num($_POST['edit_action_id']);
	$edit_action_page_id = _num($_POST['edit_action_page_id']);


	/* ---=== Настройки удаления данных ===--- */
	$del_on = _bool($_POST['del_on']);
	if(!$del_head = _txt($_POST['del_head']))
		jsonError('Не указан заголовок удаления');
	if(!$del_button_submit = _txt($_POST['del_button_submit']))
		jsonError('Не указан текст кнопки удаления');
	if(!$del_button_cancel = _txt($_POST['del_button_cancel']))
		jsonError('Не указан текст кнопки отмены удаления');
	$del_action_id = _num($_POST['del_action_id']);
	$del_action_page_id = _num($_POST['del_action_page_id']);


	/* ---=== Настройки ширины ===--- */
	if(!$width = _num($_POST['width']))
		jsonError('Некорректное значение ширины диалога');
	if($width < 480 || $width > 980)
		jsonError('Установлена недопустимая ширина диалога');

	if(!$name = _txt($_POST['name']))
		jsonError('Укажите имя диалогового окна');

	$spisok_on = _bool($_POST['spisok_on']);
	$spisok_elem_id = $spisok_on ? _num($_POST['spisok_elem_id']) : 0;

	$dialog_id_parent =   _num($_POST['dialog_id_parent']);
	if($dialog_id_parent == $dialog_id)
		jsonError('Диалог не может родительским для самого себя');

	$dialog_id_unit_get = _num($_POST['dialog_id_unit_get'], 1);
	if($dialog_id_unit_get == $dialog_id)
		jsonError('Диалог не может принимать значения самого себя');

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
				`insert_action_page_id`=".$insert_action_page_id.",

				`edit_on`=".$edit_on.",
				`edit_head`='".addslashes($edit_head)."',
				`edit_button_submit`='".addslashes($edit_button_submit)."',
				`edit_button_cancel`='".addslashes($edit_button_cancel)."',
				`edit_action_id`=".$edit_action_id.",
				`edit_action_page_id`=".$edit_action_page_id.",

				`del_on`=".$del_on.",
				`del_head`='".addslashes($del_head)."',
				`del_button_submit`='".addslashes($del_button_submit)."',
				`del_button_cancel`='".addslashes($del_button_cancel)."',
				`del_action_id`=".$del_action_id.",
				`del_action_page_id`=".$del_action_page_id.",

				`spisok_on`=".$spisok_on.",
				`spisok_elem_id`=".$spisok_elem_id.",

				`dialog_id_unit_get`=".$dialog_id_unit_get.",

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

	$sa = _bool($_POST['sa']);
	$parent_any = _bool($_POST['parent_any']);
	$width_auto = _num($_POST['width_auto']);
	$cmp_no_req = _num($_POST['cmp_no_req']);
	$app_any = _num($_POST['app_any']);

	if($table_1 = _num($_POST['table_1'])) {
		if(!$table = _table($table_1))
			jsonError('Указана несуществующая таблица 1');
		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!query_array($sql))
			jsonError('Указана несуществующая таблица 1: "'.$table.'"');
	}

	$element_group_id = _num($_POST['element_group_id']);
	$element_width = _num($_POST['element_width']);
	$element_width_min = _num($_POST['element_width_min']);
	$element_type = _num($_POST['element_type']);
	$element_afics = _txt($_POST['element_afics']);
	$element_hidden = _num($_POST['element_hidden']);
	$element_action_dialog_id = _num($_POST['element_action_dialog_id']);

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`parent_any`=".$parent_any.",
				`width_auto`=".$width_auto.",
				`cmp_no_req`=".$cmp_no_req.",

				`table_1`=".$table_1.",

				`element_group_id`=".$element_group_id.",
				`element_width`=".$element_width.",
				`element_width_min`=".$element_width_min.",
				`element_type`=".$element_type.",
				`element_afics`='".addslashes($element_afics)."',
				`element_hidden`=".$element_hidden.",
				`element_action_dialog_id`=".$element_action_dialog_id."
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

	$ELM_IDS = _BE('elem_ids_arr', 'dialog', $dialog_id);

	$get_id = _num(@$_POST['get_id']);

	if($dialog['dialog_id_unit_get'] && !$get_id)
		return _dialogOpenErr($send, 'Не получены данные записи');

	//если получен id записи
	if($get_id) {
		$send['get_id'] = $get_id;
		$prm['unit_get_id'] = $get_id;
		//в диалоге должна быть настройка, какого списка принимать данные записи
		if($dlgGetId = $dialog['dialog_id_unit_get']) {
			//диалог принимает данные записи, которые принимает страница
			if($dlgGetId == -1) {
				$page = _page($page_id);
				if(!$dlgGetId = $page['dialog_id_unit_get'])
					return _dialogOpenErr($send, 'Текущая страница не принимает данные записи');
			}
			$DLG_GET = _dialogQuery($dlgGetId);
			$prm['unit_get'] = _spisokUnitQuery($DLG_GET, $get_id);
		}
	}

	_dialogOpenPreLoad($dialog_id);

	/* --- Редактирование записи --- */
	if($edit_id = _num(@$_POST['edit_id'])) {
		$send['head'] = $dialog['edit_head'];

		if(!$dialog['edit_on'])
			return _dialogOpenErr($send, 'Редактирование записи запрещено.');
		if(!$prm['unit_edit'] = _spisokUnitQuery($dialog, $edit_id))
			return _dialogOpenErr($send, 'Записи '.$edit_id.' не существует.');

		$send['edit_id'] = $edit_id;
		$send['unit'] = $prm['unit_edit'];
		$prm['srce']['block_id'] = _dialogOpenBlockIdUpd($dialog, $prm);

		foreach($ELM_IDS as $elem_id)
			$send['vvv'][$elem_id] = _elemVvv($elem_id, $prm);

		$send['html'] = _blockHtml('dialog', $dialog['id'], $prm);
		$send['button_submit'] = $dialog['edit_button_submit'];
		$send['button_cancel'] = $dialog['edit_button_cancel'];

		$prm = _blockParam($prm);
		$send['srce'] = $prm['srce'];

		return $send;
	}







	/* --- Внесение новой записи --- */
	if(!$dialog['insert_on'])
		return _dialogOpenErr($send, 'Внесение новой записи запрещено.');

	foreach($ELM_IDS as $elem_id)
		$send['vvv'][$elem_id] = _elemVvv($elem_id, _blockParam($prm));





	$send['html'] = _blockHtml('dialog', $dialog_id, $prm);
	$send['button_submit'] = $dialog['insert_button_submit'];
	$send['button_cancel'] = $dialog['insert_button_cancel'];



	$prm = _blockParam($prm);
	$send['srce'] = $prm['srce'];

	return $send;
}
function _dialogOpenPreLoad($dialog_id) {//предварительное внесение элемента, который использует доп.параметры. Нужно для возможности сразу настраивать доп.параметры
	return;

	//удаление элементов, которые в итоге не были вставлены после использования предварительной вставки
	$sql = "DELETE FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$dialog_id."
			  AND `user_id_add`=-".USER_ID;
	query($sql);

	if($_POST['edit_id'])
		return;

	//важное условие - только при вставке в блок
	if(!$block_id = _num($_POST['block_id']))
		return;

	//только для некоторых элементов
	switch($dialog_id) {
		case 23: //список-таблица
		case 44: //сборный текст
			break;
		//--- пока нет возможности
		case 14: //список-шаблон
		case 62: //фильтр: галочка
		case 74: //фильтр: радио
		case 102://фильтр: выбор нескольких групп значений
		default: return;
	}

	//получение значения по умолчанию
	$name = '';
	$DLG = _dialogQuery($dialog_id);
	foreach($DLG['cmp'] as $cmp)
		if($cmp['col'] == 'name') {
			$name = $cmp['txt_2'];
			break;
		}

	//предварительная вставка определяется по отрицательному значению id пользователя, который вносит элемент
	$sql = "INSERT INTO `_element` (
				`app_id`,
				`dialog_id`,
				`name`,
				`block_id`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$dialog_id.",
				'".$name."',
				".$block_id.",
				-".USER_ID."
			)";
	$_POST['edit_id'] = query_id($sql);
}
function _dialogOpenUnitDelHtml($dialog, $unit) {//содержание диалога при удалении единицы списка
	if(!$block = _BE('block_obj', 'dialog_del', $dialog['id']))
		return
		'<div class="pad20">'.
			'<div class="_info">'.
				'<div class="fs15 center color-ref pad30">'.
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
	if(_num(@SA))
		return 1;
	if(!$dlg['app_id'])
		return 0;
	if($dlg['app_id'] == APP_ID)
		return 1;
	return 0;
}



