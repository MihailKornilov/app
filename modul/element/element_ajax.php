<?php
switch(@$_POST['op']) {
	case 'dialog_edit_load':
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

		$tab2field_id = 0;   //id колонка для связки с первой таблицей
		$tablesFields = array();//колонки по каждой таблице
		$group = array();
		$menu_sa = array();
		if(SA) {
			//колонки для всех таблиц
			foreach(_table() as $id => $tab)
				$tablesFields[$id] = _table2field($tab);

			if($dialog['table_2'])
				foreach(_table2field(_table($dialog['table_2'])) as $i => $field)
					if($dialog['table_2_field'] == $field) {
						$tab2field_id = $i;
						break;
					}

			//группы элементов
			$sql = "SELECT *
					FROM `_dialog_group`
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
				2 => 'Элемент'
			);
			if($dialog['element_group_id'])
				$menu_sa[3] = 'Использование';
		}

		$html =
			'<div id="dialog-w-change"></div>'.//правая вертикальная линия для изменения ширины диалога

			'<div class="pad10 center bg-gr3 line-b">'.
				'<input type="hidden" id="dialog-menu" value="'.$dialog['menu_edit_last'].'" />'.
			'</div>'.

			//Заголовок и кнопки
			'<div class="dialog-menu-1'._dn($dialog['menu_edit_last'] == 1).'">'.
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
					_blockHtml('dialog', $dialog_id, array('blk_edit' => 1)).
				'</div>'.
			'</div>'.

			//Служебное
			'<div class="dialog-menu-4 bg-gr2 pad20'._dn($dialog['menu_edit_last'] == 4).'">'.
				'<table class="bs10">'.
					'<tr><td class="grey r">Имя диалогового окна:'.
						'<td><input type="text" id="dialog_name" class="w250" maxlength="100" value="'.$dialog['name'].'" />'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'spisok_on',
									'title' => 'диалог вносит данные для списка',
									'value' => $dialog['spisok_on']
							   )).
					'<tr><td class="grey r">Родительский диалог:'.
						'<td><input type="hidden" id="dialog_parent_id" value="'.$dialog['dialog_parent_id'].'" />'.
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
					'<tr><td class="red r">Таблица 2:'.
						'<td><table>'.
								'<tr><td><input type="hidden" id="table_2" value="'.$dialog['table_2'].'" />'.
									'<td class="pl5'._dn($dialog['table_2']).'" id="td-bt2c">'.
										'<input type="hidden" id="table_2_field" value="'.$tab2field_id.'" />'.
		                    '</table>'.
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'cmp_no_req',
									'title' => 'компоненты в содержании не требуются',
									'value' => $dialog['cmp_no_req']
							   )).
					//доступность диалога. На основании app_id.
		            //0 - доступен только конкретному приложению
		            //1 - всем приложениям
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'app_any',
									'title' => 'доступно всем приложениям',
									'value' => $dialog['id'] ? ($dialog['app_id'] ? 0 : 1) : 0
							   )).
					'<tr><td>'.
						'<td>'._check(array(
									'attr_id' => 'sa',
									'title' => 'доступно только SA',
									'value' => $dialog['sa']
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
							'<td><input type="hidden" id="element_dialog_func" value="'.$dialog['element_dialog_func'].'" />'.

						'<tr><td class="red r pt20">Разрешения:'.
							'<td class="pt20">'.
			                        _check(array(
										'attr_id' => 'element_search_access',
										'title' => 'разрешать быстрый поиск по элементу',
										'value' => $dialog['element_search_access']
									)).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_style_access',
										'title' => 'разрешать настройку стилей',
										'value' => $dialog['element_style_access']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_url_access',
										'title' => 'разрешать делать ссылкой',
										'value' => $dialog['element_url_access']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_hint_access',
										'title' => 'разрешать прикрепление подсказки',
										'value' => $dialog['element_hint_access']
								   )).

						'<tr><td class="red r pt20">Дополнительно:'.
							'<td class="pt20">'.
			                        _check(array(
										'attr_id' => 'element_is_insert',
										'title' => 'элемент вносит данные',
										'value' => $dialog['element_is_insert']
									)).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_is_spisok_unit',
										'title' => 'является значением списка',
										'value' => $dialog['element_is_spisok_unit']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_hidden',
										'title' => 'скрытый элемент',
										'value' => $dialog['element_hidden']
								   )).

						'<tr><td colspan="2"><div class="hd2 ml20 mt20 mb5">Правила отображения в диалоге выбора элемента:</div>'.
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_paste_page',
										'title' => 'вставка в блок страницы',
										'value' => $dialog['element_paste_page']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_paste_dialog',
										'title' => 'вставка в блок диалога',
										'value' => $dialog['element_paste_dialog']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_paste_spisok',
										'title' => 'вставка в блок шаблона списка',
										'value' => $dialog['element_paste_spisok']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_paste_td',
										'title' => 'вставка в ячейку таблицы',
										'value' => $dialog['element_paste_td']
								   )).
						'<tr><td>'.
							'<td>'._check(array(
										'attr_id' => 'element_paste_44',
										'title' => 'вставка в сборный текст',
										'value' => $dialog['element_paste_44']
								   )).
					'</table>'.
					'</div>'.
				'</div>'.

				_dialogEditLoadUse($dialog).

			'</div>'
	  : '');

		$send['dialog_id'] = $dialog_id;
		$send['width'] = _num($dialog['width']);
		$send['menu'] = _selArray($menu);
		$send['menu_sa'] = _selArray($menu_sa);
		$send['action'] = _selArray($action);
		$send['col_type'] = _selArray(_elemColType());
		$send['blk'] = $dialog['blk'];
		$send['cmp'] = $dialog['cmp'];
		$send['html'] = $html;
		$send['sa'] = SA;
		$send['tables'] = SA ? _table() : array();
		$send['tablesFields'] = $tablesFields;
		$send['group'] = $group;
		$send['dialog_spisok'] = SA ? _dialogSelArray('sa_only') : array() ;
		$send['dialog_parent'] = _dialogSelArray($dialog_id);

		jsonSuccess($send);
		break;
	case 'dialog_save'://сохранение диалогового окна
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
}


function _table2field($tab) {//список колонок для таблицы 2
	if(empty($tab))
		return array();

	$sql = "DESCRIBE `".$tab."`";
	if(!$arr = query_array($sql))
		return array();

	$send = array();
	$n = 1;
	foreach($arr as $r) {
		if($r['Field'] == 'id')
			continue;
		if(!preg_match('/^int+/', $r['Type']))//только INT
			continue;
		$send[$n++] = $r['Field'];
	}

	return $send;
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

	$insert_on = _bool($_POST['insert_on']);
	if(!$insert_head = _txt($_POST['insert_head']))
		jsonError('Не указан заголовок для внесения записи');
	$insert_button_submit = _txt($_POST['insert_button_submit']);
	if(!$insert_button_cancel = _txt($_POST['insert_button_cancel']))
		jsonError('Не указан текст кнопки отмены для новой записи');
	$insert_action_id = _num($_POST['insert_action_id']);
	$insert_action_page_id = _num($_POST['insert_action_page_id']);

	$edit_on = _bool($_POST['edit_on']);
	if(!$edit_head = _txt($_POST['edit_head']))
		jsonError('Не указан заголовок редактирования');
	$edit_button_submit = _txt($_POST['edit_button_submit']);
	if(!$edit_button_cancel = _txt($_POST['edit_button_cancel']))
		jsonError('Не указан текст кнопки отмены редактирования');
	$edit_action_id = _num($_POST['edit_action_id']);
	$edit_action_page_id = _num($_POST['edit_action_page_id']);

	$del_on = _bool($_POST['del_on']);
	if(!$del_head = _txt($_POST['del_head']))
		jsonError('Не указан заголовок удаления');
	if(!$del_button_submit = _txt($_POST['del_button_submit']))
		jsonError('Не указан текст кнопки удаления');
	if(!$del_button_cancel = _txt($_POST['del_button_cancel']))
		jsonError('Не указан текст кнопки отмены удаления');
	$del_action_id = _num($_POST['del_action_id']);
	$del_action_page_id = _num($_POST['del_action_page_id']);

	if(!$width = _num($_POST['width']))
		jsonError('Некорректное значение ширины диалога');
	if($width < 480 || $width > 980)
		jsonError('Установлена недопустимая ширина диалога');

	if($table_1 = _num($_POST['table_1'])) {
		if(!$table = _table($table_1))
			jsonError('Указана несуществующая таблица 1');
		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!query_array($sql))
			jsonError('Указана несуществующая таблица 1: "'.$table.'"');
	}

	$table_2_field = '';
	if($table_2 = _num($_POST['table_2'])) {
		if(!$table = _table($table_2))
			jsonError('Указана несуществующая таблица 2');
		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!query_array($sql))
			jsonError('Указана несуществующая таблица 2: "'.$table.'"');
		if($table_1 == $table_2)
			jsonError('Таблицы не могут совпадать');
		if(!$table_2_field = _txt($_POST['table_2_field']))
			jsonError('Не указана колонка для связки');
	}

	$menu_edit_last = _num($_POST['menu_edit_last']);
	$sa = _bool($_POST['sa']);

	$name = _txt($_POST['name']);
	$spisok_on = _bool($_POST['spisok_on']);
	if($spisok_on && !$name)
		jsonError('Укажите имя диалогового окна');

	$dialog_parent_id = _num($_POST['dialog_parent_id']);
	if($dialog_parent_id == $dialog_id)
		jsonError('Диалог не может быть родительским для себя');

	$width_auto = _num($_POST['width_auto']);
	$cmp_no_req = _num($_POST['cmp_no_req']);
	$app_any = _num($_POST['app_any']);

	$element_group_id = _num($_POST['element_group_id']);
	$element_width = _num($_POST['element_width']);
	$element_width_min = _num($_POST['element_width_min']);
	$element_type = _num($_POST['element_type']);
	$element_search_access = _num($_POST['element_search_access']);
	$element_is_insert = _num($_POST['element_is_insert']);
	$element_style_access = _num($_POST['element_style_access']);
	$element_url_access = _num($_POST['element_url_access']);
	$element_hint_access = _num($_POST['element_hint_access']);
	$element_dialog_func = _num($_POST['element_dialog_func']);
	$element_afics = _txt($_POST['element_afics']);

	$element_hidden = _num($_POST['element_hidden']);
	$element_is_spisok_unit = _num($_POST['element_is_spisok_unit']);

	$element_paste_page =   _num($_POST['element_paste_page']);
	$element_paste_dialog = _num($_POST['element_paste_dialog']);
	$element_paste_spisok = _num($_POST['element_paste_spisok']);
	$element_paste_td = _num($_POST['element_paste_td']);
	$element_paste_44 = _num($_POST['element_paste_44']);

	$sql = "UPDATE `_dialog`
			SET `app_id`=".($app_any ? 0 : APP_ID).",
				`sa`=".$sa.",
				`name`='".addslashes($name)."',
				`width`=".$width.",
				`width_auto`=".$width_auto.",
				`cmp_no_req`=".$cmp_no_req.",

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

				`dialog_parent_id`=".$dialog_parent_id.",
				`table_1`=".$table_1.",
				`table_2`=".$table_2.",
				`table_2_field`='".addslashes($table_2_field)."',
				`spisok_on`=".$spisok_on.",

				`element_group_id`=".$element_group_id.",
				`element_width`=".$element_width.",
				`element_width_min`=".$element_width_min.",
				`element_type`=".$element_type.",
				`element_search_access`=".$element_search_access.",
				`element_is_insert`=".$element_is_insert.",
				`element_style_access`=".$element_style_access.",
				`element_url_access`=".$element_url_access.",
				`element_hint_access`=".$element_hint_access.",
				`element_dialog_func`=".$element_dialog_func.",
				`element_afics`='".addslashes($element_afics)."',

				`element_hidden`=".$element_hidden.",
				`element_is_spisok_unit`=".$element_is_spisok_unit.",

				`element_paste_page`=".$element_paste_page.",
				`element_paste_dialog`=".$element_paste_dialog.",
				`element_paste_spisok`=".$element_paste_spisok.",
				`element_paste_td`=".$element_paste_td.",
				`element_paste_44`=".$element_paste_44.",

				`menu_edit_last`=".$menu_edit_last."
			WHERE `id`=".$dialog_id;
	query($sql);

	_BE('dialog_clear');

	return $dialog_id;
}
function _dialogOpenLoad($dialog_id) {
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');

	$block_id = _num(@$_POST['block_id'], 1);

	//получение данных единицы списка
	$unit = array();
	$unit_id = _num(@$_POST['unit_id'], 1);
	if($unit_id > 0) {
		$dlg = $dialog;
		if($parent_id = $dialog['dialog_parent_id'])
			$dlg = _dialogQuery($parent_id);

		$cond = "`t1`.`id`=".$unit_id;
//		$cond .= _spisokCondDef($dialog_id);
		if(isset($dlg['field1']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";

		if(isset($dlg['field2']['app_id']))
			$cond .= " AND `t2`.`app_id` IN (0,".APP_ID.")";
		if(isset($dlg['field2']['dialog_id']))
			$cond .= " AND `t2`.`dialog_id`=".$dlg['id'];

		$sql = "SELECT `t1`.*"._spisokJoinField($dlg)."
				FROM "._tableFrom($dlg)."
				WHERE ".$cond;
		if(!$unit = query_assoc($sql))
			jsonError('Записи не существует');
		if(@$unit['sa'] && !SA)
			jsonError('Нет доступа');
		if(@$unit['deleted'])
			jsonError('Запись была удалена');

		if(!$block_id && isset($dlg['field1']['block_id']))
			$block_id = _num($unit['block_id']);

		foreach($dlg['cmp'] as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
			//должен является вложенным списком
			if($cmp['dialog_id'] != 29 && $cmp['dialog_id'] != 59)
				continue;

			//должно быть присвоено имя колонки
			if(!$col = $cmp['col'])
				continue;

			//получение данных из вложенного списка
			$incDialog = _dialogQuery($cmp['num_1']);

			$cond = "`t1`.`id`=".$unit[$col];

			$sql = "SELECT `t1`.*"._spisokJoinField($incDialog)."
					FROM "._tableFrom($incDialog)."
					WHERE ".$cond;
			if(!$inc = query_assoc($sql))
				continue;

			//идентификаторы будут заменены на массив с данными единицы списка
			$unit[$col] = $inc;
		}
	}

	$act = $unit_id > 0 ? 'edit' : 'insert';
	if(_num(@$_POST['del']))
		$act = 'del';

	$send['page_id'] = _num(@$_POST['page_id']);;
	$send['dialog_id'] = $dialog_id;
	$send['block_id'] = $block_id;
	$send['unit_id'] = $unit_id;
	$send['dialog_source'] = _num(@$_POST['dialog_source']);

	//исходные данные, полученные для открытия диалога
	$unit['source'] = $send;

	//дополнительные параметры
	$unit['source']['prm'] = isset($_POST['prm']) ? _arrNum($_POST['prm']) : array();

	$send['act'] = $act;
	$send['edit_access'] = _num(@SA) || $dialog['app_id'] && $dialog['app_id'] == APP_ID ? 1 : 0;//права для редактирования диалога
	$send['width'] = $dialog['width_auto'] ? 0 : _num($dialog['width']);
	$send['col_type'] = _elemColType($dialog['element_type']);
	$send['head'] = $dialog[$act.'_head'];
	$send['button_submit'] = $dialog[$act.'_button_submit'];
	$send['button_cancel'] = $dialog[$act.'_button_cancel'];
	$send['html'] = _blockHtml('dialog', $dialog_id, $unit);

	$send['elm_ids'] = _BE('elem_ids_arr', 'dialog', $dialog_id);

	$send['unit'] = _arrNum($unit);

	//заполнение значениями некоторых компонентов
	$send['vvv'] = array();
	foreach($send['elm_ids'] as $elem_id)
		$send['vvv'][$elem_id] = _elemVvv($elem_id, $send);


	//проверка доступа внесения новой записи
	if($act == 'insert' && !$dialog['insert_on']) {
		$send['html'] =
			'<div class="pad10">'.
				'<div class="_empty">Внесение новой записи запрещено.</div>'.
			'</div>';
		$send['button_submit'] = '';
		$send['button_cancel'] = 'Закрыть';
	}

	//проверка доступа редактирования записи
	if($act == 'edit' && !$dialog['edit_on']) {
		$send['html'] =
			'<div class="pad10">'.
				'<div class="_empty">Редактирование запрещено.</div>'.
			'</div>';
		$send['button_submit'] = '';
		$send['button_cancel'] = 'Закрыть';
	}


	//если производится удаление единицы списка
	if($act == 'del') {
		if(!$unit_id)
			jsonError('Отсутствует единица списка для удаления');

		$html =
			'<div class="pad20">'.
				'<div class="_info">'.
					'<div class="fs15 center color-ref pad30">'.
						'Подтвердите удаление.'.
					'</div>'.
				'</div>'.
			'</div>';

		if(!$dialog['del_on']) {
			$html =
				'<div class="pad10">'.
					'<div class="_empty">Удаление записи недоступно.</div>'.
				'</div>';
			$send['button_submit'] = '';
			$send['button_cancel'] = 'Закрыть';
		}

		$send['width'] = 480;
		$send['html'] = $html;
	}

	return $send;
}



