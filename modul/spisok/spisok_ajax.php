<?php
switch(@$_POST['op']) {
	case 'spisok_add'://внесение данных диалога в _spisok
		$page_id = _num($_POST['page_id']);
		$block_id = _num($_POST['block_id']);

		$v = _spisokUnitUpdate(0, $page_id, $block_id);

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

		$v = _spisokUnitUpdate($unit_id);

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
	case 'spisok_next'://догрузка списка
		if(!$pe_id = _num($_POST['pe_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `id`=".$pe_id;
		if(!$pe = query_assoc($sql))
			jsonError('Элемента id'.$pe_id.' не существует');

		if($pe['dialog_id'] != 14)
			jsonError('Элемент не является списком');

		$send['spisok_type'] = _num($pe['num_1']);
		$send['spisok'] = utf8(_spisokShow($pe, $next));
		jsonSuccess($send);
		break;
	case 'spisok_search'://получение обновлённого списка по условиям
		if(!$element_id = _num($_POST['element_id']))
			jsonError('Некорректный ID элемента станицы');

		$v = _txt($_POST['v']);

		//получение данных элемента поиска
		$sql = "SELECT *
				FROM `_page_element`
				WHERE `id`=".$element_id;
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
				WHERE `dialog_id`=14
				  AND `page_id`=".$pe['page_id']."
				  AND `id`=".$pe_id."
				LIMIT 1";
		if(!$peSpisok = query_assoc($sql))
			jsonError('Нет нужного списка на странице');

		$send['attr_id'] = '#pe_'.$peSpisok['id'];
		$send['spisok'] = utf8(_spisokShow($peSpisok));

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
				FROM `_page_element`
				WHERE `id`=".$elem_id;
		if(!$elem = query_assoc($sql))
			jsonError('Элемента не существует');

		$CMP = $dialog['component'];

		$labelName = array();
		$labelName[] = array(
			'uid' => -1,
			'title' => utf8('Порядковый номер'),
			'content' => utf8('<div class="color-pay">Порядковый номер</div>')
		);

		$arrDef = array();//массив колонок по умолчанию, если настройка списка производится впервые
		$arr182 = array();//массив элементов для шаблона

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
				$tmpHtml = '';
				//получение элементов шаблона
				$sql = "SELECT *
						FROM `_page_element`
						WHERE `parent_id`=".$elem_id."
						ORDER BY `sort`";
				if($tmp = query_arr($sql))
					foreach($tmp as $id => $r) {
						$txt = '';
						$txt_2 = '';
						switch($r['num_4']) {
							case -1://порядковый номер
								$txt = '123';
								break;
							case -2://дата внесения
								$txt = '21 мар 2017';
								break;
							case -4://произвольный текст
								$txt = _br($r['txt_2']);
								$txt_2 = $r['txt_2'];
								break;
							default:
								if($r['num_4'] <= 0)
									continue;
								$txt_2 = $r['txt_2'];
								switch($r['txt_2']) {
									case 1://имя колонки
										$txt = $CMP[$r['num_4']]['label_name'];
										break;
									case 2://значение колонки
										$txt = 'Значение "'.$CMP[$r['num_4']]['label_name'].'"';
										break;
									default: continue;
								}
						}

						$size = 13;
						if($r['size']) {
							$ex = explode('fs', $r['size']);
							$size = _num($ex[1]);
						}
						$arr182[] = array(
							'id' => _num($id),
							'tmp' => 1,
							'txt_2' => utf8($txt_2),
							'num_4' => _num($r['num_4'], 1),
							'fontAllow' => 1,
							'type' => $r['type'],
							'pos' => $r['pos'],
							'color' => $r['color'],
							'font' => $r['font'],
							'size' => $size,
							'pad' => $r['pad'],
						);
						$tmpHtml .=
							'<div id="pe_'.$id.'" class="pe prel '.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'"'._pageElemStyle($r).' val="'.$id.'">'.
								'<div class="elem-pas" val="'.$id.'"></div>'.
								$txt.
							'</div>';
					}

				$html =
					'<div class="hd2">Настройка шаблона единицы списка:</div>'.
					'<table class="mt10">'.
						'<tr><td class="top">'.
								'<input type="hidden" id="elem_type" />'.
							'<td class="top pl10 dn">'.
								'<input type="hidden" id="col_type" />'.
							'<td class="top dn">'.
								'<textarea id="tmp_elem_txt_2" class="min w250 ml10"></textarea>'.
							'<td class="top dn">'.
								'<button id="elem-add" class="vk green ml10">Добавить элемент</button>'.
					'</table>'.
					'<div id="tmp-elem-list" class="elem-sort mh50 mt10 bor-f0 bg-ffe">'.$tmpHtml.'</div>';
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

		//массив настроенных колонок
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
		$send['arr182'] = $arr182;
		$send['spisok_type'] = _num($elem['num_1']);
		$send['html'] = utf8($html);

		jsonSuccess($send);
		break;
}


function _spisokUnitUpdate($unit_id=0, $page_id=0, $block_id=0) {//внесение/редактирование записи списка
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
	define('BASE_TABLE', $dialog['base_table']);
	$sql = "SHOW TABLES LIKE '".BASE_TABLE."'";
	if(!mysql_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	if($unit_id) {
		$cond = "`id`=".$unit_id;
		if(isset($dialog['field']['app_id']))
			$cond .= " AND `app_id` IN (0,".APP_ID.")";
		$sql = "SELECT * FROM `".BASE_TABLE."` WHERE ".$cond;
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
				'text' => utf8('Не заполнено поле<br><b>'.$r['label_name'].'</b>')
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
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$unit_id = query_insert_id(BASE_TABLE);
		$send['unit_id'] = $unit_id;

		//обновление некоторых колонок
		$sql = "DESCRIBE `".BASE_TABLE."`";
		$desc = query_array($sql);
		foreach($desc as $r) {
			if($r['Field'] == 'app_id') {
				$sql = "UPDATE `".BASE_TABLE."`
						SET `app_id`=".APP_ID."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
			if($r['Field'] == 'num') {//установка порядкового номера
				$sql = "SELECT IFNULL(MAX(`num`),0)+1
						FROM `".BASE_TABLE."`
						WHERE `app_id`=".APP_ID."
						  AND `dialog_id`=".$dialog_id;
				$num = query_value($sql);
				$sql = "UPDATE `".BASE_TABLE."`
						SET `num`=".$num."
						WHERE `id`=".$unit_id;
				query($sql);
				continue;
			}
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
		_spisokUnitFuncValUpdate($dialog, $id, $unit_id);

	if(BASE_TABLE == '_page')
		_cache('clear', '_pageCache');

	return $send;
}
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

				$sql = "UPDATE `".BASE_TABLE."`
						SET `num_5`=".$num_5.",
							`num_6`=".$num_6.",
							`num_7`=".$num_7.",
							`txt_5`='".implode(',', $txt_5)."'
						WHERE `id`=".$unit_id;
				query($sql);

				return;
			case 1://[182] шаблон
				$insert = array();
				$sort = 0;
				$idsEdit = '0';//id элементов, которые редактировались, не для удаления
				foreach($v as $r) {
					$insert[] = "(
						".$r['id'].",
						".$unit_id.",
						'".$r['type']."',
						'".$r['pos']."',
						'".$r['color']."',
						'".$r['font']."',
						'fs".$r['size']."',
						'".$r['pad']."',
						'".addslashes(_txt($r['txt_2']))."',
						".$r['num_4'].",
						".$sort++."
					)";
					if($r['id'])
						$idsEdit .= ','.$r['id'];
				}

				$sql = "DELETE FROM `_page_element`
						WHERE `parent_id`=".$unit_id."
						  AND `id` NOT IN (".$idsEdit.")";
				query($sql);

				if(empty($insert))
					return;

				$sql = "INSERT INTO `_page_element`  (
							`id`,
							`parent_id`,
							`type`,
							`pos`,
							`color`,
							`font`,
							`size`,
							`pad`,
							`txt_2`,
							`num_4`,
							`sort`
						) VALUES ".implode(',', $insert)."
						ON DUPLICATE KEY UPDATE
							`type`=VALUES(`type`),
							`pos`=VALUES(`pos`),
							`color`=VALUES(`color`),
							`font`=VALUES(`font`),
							`size`=VALUES(`size`),
							`pad`=VALUES(`pad`),
							`txt_2`=VALUES(`txt_2`),
							`num_4`=VALUES(`num_4`),
							`sort`=VALUES(`sort`)
						";
				query($sql);
				break;
		}
	}
}
