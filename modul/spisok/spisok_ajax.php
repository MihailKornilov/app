<?php
switch(@$_POST['op']) {
	case 'spisok_add'://внесение единицы списка из диалога
		$send = _spisokUnitUpdate();
		jsonSuccess($send);
		break;
	case 'spisok_edit'://сохранение данных записи для диалога
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный id единицы списка');

		$send = _spisokUnitUpdate();

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
	case 'spisok_next'://догрузка списка
		if(!$pe_id = _num($_POST['pe_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$pe_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$pe_id.' не существует');

		if($pe['dialog_id'] != 14)
			jsonError('Элемент не является списком');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$pe['block_id'];
		if(!$pe['block'] = query_assoc($sql))
			jsonError('Отсутствует блок списка');

		$send['type'] = _num($pe['num_1']);
		$send['spisok'] = utf8(_spisokShow($pe, $next));
		jsonSuccess($send);
		break;
	case 'spisok_search'://получение обновлённого списка по условиям
		if(!$element_id = _num($_POST['element_id']))
			jsonError('Некорректный ID элемента станицы');

		$v = _txt($_POST['v']);

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$element_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$element_id.' не существует');

		//сохранение строки поиска
		$sql = "UPDATE `_element`
				SET `v`='".addslashes($v)."'
				WHERE `id`=".$element_id;
		query($sql);

		//id диалога списка, на который происходит воздействие через поиск
		if(!$pe_id = _num($pe['num_3']))
			jsonError('Нет воздействия на список');

		//расположение списка на странице, на которой расположен поиск
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('Нет нужного списка на странице');

		//получение данных блока, в котором расположен элемент-список
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$peSpisok['block_id'];
		if(!$peSpisok['block'] = query_assoc($sql))
			jsonError('Блока не существует');

		//элемент количества списка на странице, на которой расположен поиск
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=15
				  AND `page_id`=".$pe['page_id']."
				  AND `num_1`=".$pe_id."
				LIMIT 1";
		if($peCount = query_assoc($sql)) {
			$send['count_attr'] = '#pe_'.$peCount['id'];
			$send['count_html'] = utf8(_spisokElemCount($peCount));
		}

		$send['spisok_attr'] = '#pe_'.$peSpisok['id'];
		$send['spisok_html'] = utf8(_spisokShow($peSpisok));

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
					FROM `_element`
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
				'<tr><td class="label r top" style="width:125px">Колонк'.($f6 ? 'а' : 'и').':'.
					'<td>'.$col.
			'</table>'
		);

		jsonSuccess($send);
		break;
	case 'spisok_elem_list'://настройка колонок списка (для функции Действие 7)
		if(!$cmp_id = _num($_POST['component_id']))
			jsonError('Некорректный ID компонента диалога');
		if(!$elem_id = _num($_POST['elem_id'])) {
			$send['after'] = utf8('<div class="center fs14 i grey">Настройка колонок будет доступна<br />после добавления списка на страницу.</div>');
			jsonSuccess($send);
		}
		if(!$spisok_id = _num($_POST['spisok_id']))
			jsonError('Некорректный ID списка');
		if(!$dialog = _dialogQuery($spisok_id))
			jsonError('Диалога для создания списка не существует');

		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$elem = query_assoc($sql))
			jsonError('Элемента не существует');

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$elem['block_id'];
		if(!$block = query_assoc($sql))
			jsonError('Блока не существует');

		$CMP = $dialog['component'];

		$labelName = array();
		$labelName[] = array(
			'uid' => -1,
			'title' => utf8('Порядковый номер'),
			'content' => utf8('<div class="color-pay">Порядковый номер</div>'),
			'link_on' => 1
		);

		$arrDef = array();//массив колонок по умолчанию, если настройка списка производится впервые

		switch($elem['num_1']) {
			default:
			case 181://таблица
				$html =
					'<div class="hd2">Содержание списка:</div>'.
					'<div class="mar10">'.
						'<div class="ml30 mb10">'.
							_check(array(
								'id' => 'rowSmall',
								'title' => 'Узкие строки',
								'light' => 1,
								'value' => $elem['num_7']
							)).
							_check(array(
								'id' => 'colNameShow',
								'title' => 'Показывать имена колонок',
								'light' => 1,
								'value' => $elem['num_5'],
								'class' => 'ml30'
							)).
							_check(array(
								'id' => 'rowLight',
								'title' => 'Подсвечивать строки при наведении',
								'light' => 1,
								'value' => $elem['num_6'],
								'class' => 'ml30'
							)).
						'</div>'.
						'<dl></dl>'.
						'<div class="item-add center pad15 fs15 color-555 over1 curP">Добавить колонку</div>'.
					'</div>';
				break;
			case 182://шаблон
				setcookie('block_level_spisok', 1, time() + 2592000, '/');

				//корректировка ширины с учётом отступов
				$ex = explode(' ', $elem['mar']);
				$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;

				$html =
					'<div class="hd2 mt20">Настройка шаблона единицы списка:</div>'.
					'<div class="bg-ffc pad10 line-b">'._blockLevelChange('spisok', $block['id'], $width).'</div>'.
					'<div class="block-content-spisok mt10" style="width:'.$width.'px">'._blockHtml('spisok', $block['id'], $width).'</div>';

				$send['block_arr'] = _blockJsArr('spisok', $block['id']);
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
				'title' => utf8('Произвольный текст'),
				'content' => utf8('<div class="color-pay b">Произвольный текст</div>')
			);
		$labelName[] = array(
			'uid' => -2,
			'title' => utf8('Дата внесения'),
			'content' => utf8('<div class="color-pay">Дата внесения</div>')
		);
		if($elem['num_1'] == 181)
			$labelName[] = array(
				'uid' => -3,
				'title' => utf8('Иконки управления'),
				'content' => utf8('<div class="color-pay">Иконки управления</div>')
			);

		//массив настроенных колонок (для 181)
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

		$send['label_name_select'] = $labelName;//названия колонок для select
		$send['arr'] = empty($elem['txt_5']) || $spisok_id != $elem['num_3'] ? $arrDef : $arr;//колонки, которые были настроены
		$send['spisok_type'] = _num($elem['num_1']);
		$send['html'] = utf8($html);

		jsonSuccess($send);
		break;
	case 'spisok_tmp_elem_to_block'://вставка элемента в блок единицы списка
		if(!$block_id = _num($_POST['block_id']))
			jsonError('Некорректный ID блока');
		if(!$num_1 = _num($_POST['num_1'], true))
			jsonError('Колонка не выбрана');

		if($elem_id = _num($_POST['elem_id'])) {
			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$elem_id;
			if(!query_assoc($sql))
				jsonError('Редактируемого элемента id'.$elem_id.' не существует');
		}

		$num_2 = _num($_POST['num_2']);
		$num_7 = _num($_POST['num_7']);
		$txt_2 = $num_1 == -4 ? _txt($_POST['txt_2']) : '';

		if($num_1 > 0 && !$num_2)
			jsonError('Не выбран тип содержания');

		//получение данных блока
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		if(!$block = query_assoc($sql))
			jsonError('Блока id'.$block_id.' не существует');

		//проверка наличия в блоке других блоков
		$sql = "SELECT COUNT(`id`)
				FROM `_block`
				WHERE `parent_id`=".$block_id;
		if(query_value($sql))
			jsonError('Данный блок поделён на части,<br>вставка элемента невозможна');

		//проверка наличия элемента в блоке
		if(!$elem_id) {
			$sql = "SELECT COUNT(`id`)
					FROM `_element`
					WHERE `block_id`=".$block_id;
			if(query_value($sql))
				jsonError('В данном блоке уже присутствует элемент');
		}

		//получение элемента, в котором находятся данные списка
		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id`=".$block['obj_id'];
		if(!$elSpisok = query_assoc($sql))
			jsonError('Списка не существует');

		//диалог, через который вносятся единицы списка
		$dialog_id = $elSpisok['num_3'];

		$sql = "INSERT INTO `_element` (
					`id`,
					`page_id`,
					`block_id`,
					`num_1`,
					`num_2`,
					`num_3`,
					`num_7`,
					`txt_2`,
					`viewer_id_add`
				) VALUES (
					".$elem_id.",
					".$elSpisok['page_id'].",
					".$block_id.",
					".$num_1.",
					".$num_2.",
					".$dialog_id.",
					".$num_7.",
					'".addslashes($txt_2)."',
					".VIEWER_ID."
				) ON DUPLICATE KEY UPDATE
					`num_1`=VALUES(`num_1`),
					`num_2`=VALUES(`num_2`),
					`num_7`=VALUES(`num_7`),
					`txt_2`=VALUES(`txt_2`)";
		query($sql);

		//получение данных самого главного блока списка
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block['obj_id'];
		$iss = query_assoc($sql);

		//корректировка ширины с учётом отступов
		$ex = explode(' ', $elSpisok['mar']);
		$width = floor(($iss['width'] - $ex[1] - $ex[3]) / 10) * 10;

		$send['html'] = utf8(_blockHtml('spisok', $iss['id'], $width));
		$send['block_arr'] = _blockJsArr('spisok', $iss['id']);

		jsonSuccess($send);
		break;

	case 'spisok_select_get'://получение списка для селекта
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалога');
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента диалога');

		$v = _txt($_POST['v']);

		$send['spisok'] = _spisokList($dialog_id, $cmp_id, $v);

		jsonSuccess($send);
		break;
}

function _spisokUnitUpdate() {//внесение/редактирование единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');

	$page_id = _num($_POST['page_id']);
	$unit_id = _num($_POST['unit_id']);
	$block_id = _num($_POST['block_id']);

	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	//получение данных единицы списка, если редактируется
	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".$dialog['base_table']."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');
		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	//данные компонентов диалога
	if(!$cmp = @$_POST['cmp'])
		jsonError('Нет данных для внесения');
	if(!is_array($cmp))
		jsonError('Компоненты диалога не являтся массивом');

	$cmpUpdate = array();
	foreach($cmp as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('Некорректный id компонента диалога');
		if(!$col = @$dialog['cmp'][$cmp_id]['col'])
			jsonError('Отсутствует имя колонки списка');
		if(!isset($dialog['field'][$col]))
			jsonError('В списке нет колонки с именем "'.$col.'"');

		$v = _txt($cmp[$cmp_id]);

		if($dialog['cmp'][$cmp_id]['require'] && !$v)
			jsonError('Требуется обязательно заполнить<br>поля, помеченные звёздочкой');

		$cmpUpdate[] = "`".$col."`='".addslashes($v)."'";
	}

	if(!$unit_id) {
		//если производится вставка в блок: проверка, чтобы в блок не попало 2 элемента
		if($dialog['base_table'] == '_element' && $block_id) {
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$block_id;
			if(!$block = query_assoc($sql))
				jsonError('Блока не сущетвует');

			$sql = "SELECT COUNT(*)
					FROM `_element`
					WHERE `block_id`=".$block_id;
			if(query_value($sql))
				jsonError('В блоке уже есть элемент');
		}


		$sql = "INSERT INTO `".$dialog['base_table']."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($dialog['base_table']);

		//обновление некоторых колонок
		$sql = "DESCRIBE `".$dialog['base_table']."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".$dialog['base_table']."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'block_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `block_id`=".$block_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'sort') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `sort`="._maxSql($dialog['base_table'])."
						WHERE `id`=".$unit_id;
				query($sql);
			}
		}
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $cmpUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');


	$send = array(
		'unit_id' => $unit_id,
		'action_id' => _num($dialog['action_id']),
		'action_page_id' => _num($dialog['action_page_id']),
		'block_obj_name' => '',
		'level' => ''
	);


	//получение имени объекта, если действие производилось для блока
	if($dialog['action_id'] == 3 && $dialog['base_table'] == '_element') {
		$sql = "SELECT `block_id`
				FROM `_element`
				WHERE `id`=".$unit_id;
		$block_id = _num(query_value($sql));

		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		$block = query_assoc($sql);

		$send['block_obj_name'] = $block['obj_name'];

		switch($block['obj_name']) {
			default:
			case 'page': $width = 1000; break;
			case 'spisok':
				jsonError('Получение ширины для списка не доделано...');
				$width = 0;
				break;
			case 'dialog':
				$dlg = _dialogQuery($block['obj_id']);
				$width = $dlg['width'];
				break;
		}
		$send['level'] = utf8(_blockLevelChange($block['obj_name'], $block['obj_id'], $width));
	}


	return $send;
}

/*
function _spisokUnitUpdate($unit_id=0, $page_id=0, $block_id=0) {//внесение/редактирование единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');

	$send = array(
		'unit_id' => $unit_id,
		'dialog' => $dialog
	);
	
	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '".$dialog['base_table']."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".$dialog['base_table']."` WHERE ".$cond;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	//удаление элемента со страницы
	if($dialog_id == 6) {
		$sql = "DELETE FROM `_element` WHERE `id`=".$unit_id;
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
				'text' => utf8('Не заполнено поле<br><b>'.$r['label_name'].'</b>')
			));

		//если это выпадающий список, выбирающий связку и вносит в список элементов
//		if($r['type_id'] == 2 && $dialog['base_table'] == '_element' && $r['num_1'])
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
		$sql = "INSERT INTO `".$dialog['base_table']."` (
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id($dialog['base_table']);
		$send['unit_id'] = $unit_id;

		//обновление некоторых колонок
		$sql = "DESCRIBE `".$dialog['base_table']."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".$dialog['base_table']."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'page_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `page_id`=".$page_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'block_id') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `block_id`=".$block_id."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'sort') {
				$sql = "UPDATE `".$dialog['base_table']."`
						SET `sort`="._maxSql($dialog['base_table'])."
						WHERE `id`=".$unit_id;
				query($sql);
			}			
		}
	}

	$sql = "UPDATE `".$dialog['base_table']."`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$unit_id;
	query($sql);

	//обновление функций компонентов
	foreach($dialog['component'] as $id => $r)
		_spisokUnitFuncValUpdate($dialog, $id, $unit_id);

	if($dialog['base_table'] == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
*/
function _spisokUnitFuncValUpdate($dialog, $cmp_id, $unit_id) {//обновление значений функций компонентов (пока конкретно Действие 5)
	if(!$unit_id)
		return;
	if(!isset($_POST['func'][$cmp_id]))
		return;

	$cmp = $dialog['component'][$cmp_id];
	$v = $_POST['func'][$cmp_id];

	//проверка наличия функции у компонента
	$f5 = !empty($cmp['func_action_ass'][5]);
	$f6 = !empty($cmp['func_action_ass'][6]);
	$f7 = !empty($cmp['func_action_ass'][7]);
	if(!$f5 && !$f6 && !$f7)
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
				FROM `".$dialog['base_table']."`
				WHERE `id`=".$unit_id;
		if(!$pe_id = query_value($sql))
			return;

		$sql = "UPDATE `".$dialog['base_table']."`
				SET `txt_3`='".addslashes($v)."'
				WHERE `id`=".$pe_id;
		query($sql);
		return;
	}

	if($f6) {
		$sql = "UPDATE `".$dialog['base_table']."`
				SET `num_3`='".addslashes($v)."'
				WHERE `id`=".$unit_id;
		query($sql);
		return;
	}

	if($f7) {
		switch(is_array($v)) {
			default://[181] таблица
				$ex = explode(',', $v);
				$num_5 = _num($ex[0]);
				$num_6 = _num(@$ex[1]);
				$num_7 = _num(@$ex[2]);

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

				$sql = "UPDATE `".$dialog['base_table']."`
						SET `num_5`=".$num_5.",
							`num_6`=".$num_6.",
							`num_7`=".$num_7.",
							`txt_5`='".implode(',', $txt_5)."'
						WHERE `id`=".$unit_id;
				query($sql);

				return;
			case 1://[182] шаблон

				break;
		}
	}
}
