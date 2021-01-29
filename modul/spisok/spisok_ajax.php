<?php
switch(@$_POST['op']) {
	case 'spisok_add'://внесение новой записи
		$send = _SUN();
		jsonSuccess($send);
		break;
	case 'spisok_save'://сохранение данных записи
		if(!$unit_id = _num($_POST['unit_id'], 1))
			jsonError('Некорректный id единицы списка');

		$send = _SUN($unit_id);

		jsonSuccess($send);
		break;
	case 'spisok_del'://удаление записи
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный id записи');

		$dialog = _spisokUnitDialog($unit_id);
		$dialog['act'] = 3;

		$send['action_id'] = _num($dialog['del_action_id']);
		$send['action_obj_id'] = _num($dialog['del_action_obj_id']);
		$send = _spisokAction3($dialog, $unit_id, $send);
		_dialogIUID($dialog);

		if(isset($dialog['field1']['deleted'])) {
			$unit = _spisokUnitQuery($dialog, $unit_id);

			$sql = "UPDATE "._queryFrom($dialog)."
					SET `deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE "._queryWhere($dialog)."
					  AND "._queryCol_id($dialog)."=".$unit_id;
			query($sql);

			PHP12_schetPayContent_del($unit);

			_userAppAccessDel($dialog, $unit_id);
			_historyInsert(3, $dialog, $unit_id);
			_counterGlobal($dialog['id'], $dialog);

			$unit['deleted'] = 1;
			_SUN_AFTER($dialog, $unit);
		} else {
			$elem = array();
			if(_table($dialog['table_1']) == '_element') {//если это элемент
				$elem = _elemOne($unit_id);
				//удаление значений
				$sql = "DELETE FROM `_element` WHERE `parent_id`=".$unit_id;
				query($sql);

				//удаление функций
				$sql = "DELETE FROM `_action` WHERE `element_id`=".$unit_id;
				query($sql);

				//удаление фильтров
				$sql = "DELETE FROM `_user_spisok_filter` WHERE `element_id_filter`=".$unit_id;
				query($sql);

				//установка позиции в блоке по умолчанию
				$sql = "UPDATE `_block` SET `pos`='top' WHERE `id`=".$elem['block_id'];
				query($sql);
			}

			$sql = "SELECT * FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			$unit = query_assoc($sql);

			$sql = "DELETE FROM `"._table($dialog['table_1'])."` WHERE `id`=".$unit_id;
			query($sql);

			//обновление кеша объекта, если это элемент
			if($elem) {
				_BE('block_clear');
				_BE('elem_clear');
				_filter('cache_clear');//сброс кеша фильтра, так как возможно был удалён фильтр
				$send['elem_del'] = $unit_id;
			}

			//обновление кеша объекта, если это страница
			if($dialog['table_name_1'] == '_page')
				_cache_clear('page');

			if($dialog['table_name_1'] == '_action')
				if(_elemOne($unit['element_id']))
					_BE('elem_clear');

			//удаление данных счётчика
			if($dialog['table_name_1'] == '_counter') {
				$sql = "DELETE FROM `_counter_v` WHERE `counter_id`=".$unit_id;
				query($sql);
			}
		}

		$send = _spisokAction1($send);
		$send = _spisokAction4($send);

		jsonSuccess($send);
		break;
	case 'spisok_filter_update'://обновление списка после применения фильтра
		if(!$elem_spisok = _num($_POST['elem_spisok']))
			jsonError('Некорректный ID элемента-списка');
		if(!$elSpisok = _elemOne($elem_spisok))
			jsonError('Элемента-списка id'.$elem_spisok.' не существует');
		switch($elSpisok['dialog_id']) {
			case 14:
			case 23:
			case 88: break;
			default:
				jsonError('Элемент id'.$elem_spisok.' не является списком');

		}
		if(!$elem_v = $_POST['elem_v'])
			jsonError('Отсутствуют значения фильтров');
		if(!is_array($elem_v))
			jsonError('Некорректные значения фильров');


		//видимость блоков до и после обновления фильтров - исходные значения (до обновления)
		$blkHiddenSouce = _filterUpdateBlkHidden($elSpisok['block_id']);

		/* значения, которые будут обновлены вместе с обновлением списка
				id - id элемента
				html - содержание для обновления
		*/
		$send['upd'] = array();
		foreach($elem_v as $elem_filter => $v) {
			if(!_num($elem_filter))
				continue;

			$v = _element77filterSet($elem_filter, $v);

			_filter('insert', array(
				'spisok' => $elem_spisok,
				'filter' => $elem_filter,
				'v' => $v
			));
		}

		$send = _element15filterUpd($send, $elem_spisok);
		$send = _element64filterUpd($send, $elem_spisok);
		$send = _element73filterUpd($send, $elem_spisok);
		$send = _element79filterUpd($send, $elem_spisok);
		$send = _filterHtml($send, $elem_spisok);

		//элемент "очистка фильтра", привязанный к списку
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=80
				  AND `num_1`=".$elem_spisok."
				LIMIT 1";
		if($elClear = query_assoc($sql)) {
			$send['clear_id'] = $elClear['id'];
			$send['clear_diff'] = _filter('diff', $elem_spisok);
		}

		$send['hint'] = _hintMass();
		$send['blk_hidden_upd'] = _filterUpdateBlkHidden($elSpisok['block_id'], $blkHiddenSouce);
		$send['ignore'] = _filterSpisokIgnore($elem_spisok);

		jsonSuccess($send);
		break;
	case 'spisok_filter_clear'://очистка фильтра
		if(!$spisok_id = _num($_POST['spisok_id']))
			jsonError('Некорректный ID элемента-списка');
		if(!$elSpisok = _elemOne($spisok_id))
			jsonError('Элемента-списка id'.$spisok_id.' не существует');

		//видимость блоков до и после обновления фильтров - исходные значения (до обновления)
		$blkHiddenSouce = _filterUpdateBlkHidden($elSpisok['block_id']);

		$sql = "UPDATE `_user_spisok_filter`
				SET `v`=`def`
				WHERE `user_id`=".USER_ID."
				  AND `element_id_spisok`=".$spisok_id;
		query($sql);

		_filter('cache_clear');

		$send['upd'] = array();
		$send = _element15filterUpd($send, $spisok_id);
		$send = _element64filterUpd($send, $spisok_id);
		$send = _element73filterUpd($send, $spisok_id);
		$send = _element79filterUpd($send, $spisok_id);
		$send = _filterHtml($send, $spisok_id);

		//значения по умолчанию для фильтров списка
		$send['def'] = array();
		foreach(_filter('spisok', $spisok_id) as $r) {
			$dialog_id = _num($r['elem']['dialog_id']);
			$dop = array();
			if($dialog_id == 77) {//фильтр-календарь
				$v = _filter('v', $r['elem']['id']);
				$v = _filterCalendarDef($v);
				$mon = substr($v, 0, 7);
				$dop = array(
					'mon' => $mon,
					'td_mon' => _filterCalendarMon($r['elem'], $mon, $v),
					'cnt' => _filterCalendarContent($r['elem'], $mon, $v)
				);
			}
			$send['def'][] = _arrNum(array(
				'dialog_id' => $dialog_id,
				'elem_id' => $r['elem']['id'],
				'dop' => $dop,
				'v' => $r['def']
			));
		}

		$send['filter'] = _filter('page_js');
		$send['hint'] = _hintMass();
		$send['blk_hidden_upd'] = _filterUpdateBlkHidden($elSpisok['block_id'], $blkHiddenSouce);
		$send['ignore'] = _filterSpisokIgnore($spisok_id);

		jsonSuccess($send);
		break;
	case 'spisok_14_next'://догрузка списка-шаблона
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 14)
			jsonError('Элемент не является списком-шаблоном');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока');

		$send['spisok'] = _element14_print($el, array(), $next);
		$send['hint'] = _hintMass();

		jsonSuccess($send);
		break;
	case 'spisok_23_next'://догрузка списка-таблицы
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 23)
			jsonError('Элемент не является списком-таблицей');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока списка');

		$send['spisok'] = _element23_print($el, array(), $next);
		$send['hint'] = _hintMass();

		jsonSuccess($send);
		break;
	case 'spisok_23_sort':
		if(!$el = _elemOne($_POST['elem_id']))
			jsonError('[23] sort: Элемента не существует');
		if($el['dialog_id'] != 23)
			jsonError('[23] sort: Элемент не является списком-таблицей');
		if(!$dialog = _dialogQuery($el['num_1']))
			jsonError('[23] sort: Диалога не существует');

		$arr = $_POST['arr'];
		if(empty($arr))
			jsonError('Отсутствуют значения для сортировки');
		if(!is_array($arr))
			jsonError('Значения не являются массивом');

		foreach($arr as $n => $r) {
			if(!$id = _num($r['id']))
				continue;

			$parent = '';
			if(isset($dialog['field1']['parent_id']))
				$parent .= ",`parent_id`="._num($r['parent_id']);
			elseif(isset($dialog['field1']['sort_pid']))
				$parent .= ",`sort_pid`="._num($r['parent_id']);

			$child = '';
			if(isset($dialog['field1']['child_lvl'])) {
				$lvl = 0;
				$pid = _num($r['parent_id']);
				while($pid) {
					$lvl++;
					$sql = "SELECT `parent_id`
							FROM `".$dialog['table_name_1']."`
							WHERE `id`=".$pid;
					$pid = _num(query_value($sql));
				}
				$child .= ",`child_lvl`=".$lvl;
			}

			$child_lvl = 0;


			$sql = "UPDATE `".$dialog['table_name_1']."`
					SET `sort`=".$n."
						".$parent."
						".$child."
					WHERE `id`=".$id;
			query($sql);
		}

		//обновление количеств, если присутствуют
		foreach($dialog['cmp'] as $r)
			_element54update($r);

		//очистка кеша страниц
		if($dialog['table_name_1'] == '_page')
			_cache_clear('page');

		jsonSuccess();
		break;
	case 'spisok_29_connect':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента диалога');

		$v = _txt($_POST['v']);

		$send['spisok'] = _29cnn($cmp_id, $v);
		jsonSuccess($send);
		break;
	case 'spisok_45_uns':
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID компонента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$id_new = _num($_POST['id_new']))
			jsonError('Некорректный ID выбранного значения');

		$v = _txt($_POST['v']);
		$v .= ($v ? ',' : '').$id_new.':1:-1';

		$send['html'] = _element45Uns($el, $v);
		jsonSuccess($send);
		break;
	case 'spisok_59_unit':
		if(!$cmp_id = _num($_POST['cmp_id']))
			jsonError('Некорректный ID компонента');
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Некорректный ID выбранного элемента');

		$send['html'] = _spisok59unit($cmp_id, $unit_id);
		$send['unit'] = _spisok59unit($cmp_id, $unit_id, 'assoc');
		jsonSuccess($send);
		break;
	case 'spisok_72_sum'://получение сумм для фильтра [72]
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента-фильтра');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$year = _num($_POST['year']))
			jsonError('Некорректный год');

		$send['spisok'] = _elem72Sum($el, $year);
		jsonSuccess($send);
		break;
	case 'spisok_92_sum'://Выбранные значения галочками - получение сумм
		if(!$el = _elemOne($_POST['elem_id']))
			jsonError('[92] Не получен элемент');
		if(!$ids = _ids($el['txt_1'], 1))
			jsonError('[92] Не указаны списки, в которых производится выбор значений');
		if(!is_array($DI = _elem92dlgIds($ids)))
			jsonError($DI);
		if(!$ids = _ids($_POST['ids']))
			jsonError('Не получены ID записей');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `dialog_id` IN ("._idsGet($DI, 'dlg_id').")
				  AND `id` IN (".$ids.")
				  AND !`deleted`";
		if(!$spisok = query_arr($sql))
			jsonError('Записей не существует');

		$data = array();
		foreach($DI as $r)
			$data[$r['dlg_id']] = array(
				'count' => 0,
				'sum_col' => _elemCol($r['sum_id']),
				'sum' => 0
			);

		foreach($spisok as $r) {
			$dlg_id = _num($r['dialog_id']);
			$data[$dlg_id]['count']++;
			if($col = $data[$dlg_id]['sum_col'])
				$data[$dlg_id]['sum'] += $r[$col];
		}

		$send['data'] = $data;

		jsonSuccess($send);
		break;
	case 'elem88next':
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Некорректный ID элемента станицы');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента id'.$elem_id.' не существует');
		if($el['dialog_id'] != 88)
			jsonError('Элемент не является списком-таблицей');
		if(!$next = _num($_POST['next']))
			jsonError('Некорректное значение очередного блока списка');

		$el['mon_cut'] = _txt(@$_POST['mon']);
		$send['spisok'] = _element88_print($el, array(), $next);
		jsonSuccess($send);
		break;
}

function _spisokUnitDialog($unit_id) {//получение данных о диалоге и проверка наличия единицы списка
	if(!$dialog_id = _num($_POST['dialog_id']))
		jsonError('Некорректный ID диалогового окна');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога '.$dialog_id.' не существует');
	if($dialog['sa'] && !SA)
		jsonError('Нет доступа');
	if(!$dialog['table_1'])
		return $dialog;

	//проверка наличия таблицы для внесения данных
	$sql = "SHOW TABLES LIKE '"._table($dialog['table_1'])."'";
	if(!mysqli_num_rows(query($sql)))
		jsonError('Таблицы не существует');

	//получение данных единицы списка, если редактируется
	if($unit_id) {
		if(!$r = _spisokUnitQuery($dialog, $unit_id))
			jsonError('Записи не существует');
		if(@$r['deleted'])
			jsonError('Запись была удалена');
	}

	return $dialog;
}
function _SUN($unit_id=0) {//SpisokUnitUpdate: внесение/редактирование записи
	$dialog = _spisokUnitDialog($unit_id);

	define('ACT', $unit_id ? 'edit' : 'insert');
	$dialog['act'] = ACT == 'insert' ? 1 : 2;  //для счётчиков
	define('IS_ELEM', $dialog['table_name_1'] == '_element');

	$unitOld = IS_ELEM ? _elemOne($unit_id) : _spisokUnitQuery($dialog, $unit_id);

	$CMP_ARR = _SUN_CMP_TEST($dialog, $unit_id);
	$POST_CMP = !empty($CMP_ARR[$dialog['id']]) ? $CMP_ARR[$dialog['id']] : array();
	unset($CMP_ARR[$dialog['id']]);

	_SUN_INTERCEPT($dialog, $POST_CMP);

	$unit_id = _SUN_INSERT($dialog, $unit_id);

	// ---=== СЕКЦИЯ ОБНОВЛЕНИЯ ДАННЫХ ===---

	_elementFocusClear($dialog, $POST_CMP, $unit_id);
	_pageDefClear($dialog, $POST_CMP);
	_filterDefSet($dialog, $unit_id);

	_SUN_CMP_UPDATE($dialog, $POST_CMP, $unit_id);

	_spisokUnitUpd42($dialog, $POST_CMP);
	_spisokUnitDelSetup($dialog, $unit_id);


	//внесение данных из других диалогов (если есть)
	_SUN_OTHER($CMP_ARR);

	//получение обновлённых данных записи
	$unit = IS_ELEM ? _elemOne($unit_id, true) : _spisokUnitQuery($dialog, $unit_id, true);

	if($dialog['id'] == 54)
		_element54update($unit_id);
	if($dialog['id'] == 55)
		_element55update($unit_id);

	_spisokUnitDependUpd($dialog, $unitOld, $unit);
	_historyInsertEdit($dialog, $unitOld, $unit);
	_elem29defSet($dialog, $unit);

	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		switch($cmp['dialog_id']) {
			//---=== ДЕЙСТВИЯ ПРИ НАСТРОЙКИ ЭЛЕМЕНТОВ ===---
			//конкретная функция
			case 12:
				$func = $cmp['txt_1'].'_save';
				if(!function_exists($func))
					break;
				$unit['func12'] = $func($cmp, @$_POST['vvv'][$cmp_id], $unit);
				break;
			//Применение загруженных изображений
			case 60: _image60_save($cmp, $unit); break;
		}
	}

	//todo повторное получение обновлённых данных записи
	$unit = IS_ELEM ? _elemOne($unit_id, true) : _spisokUnitQuery($dialog, $unit_id, true);

	_counterGlobal($dialog['id'], $dialog);

	_SUN_AFTER($dialog, $unit, $unitOld);

	//установка первого значения для счётчика при его создании. Либо обновление.
	if($dialog['table_name_1'] == '_counter')
		_counterGlobal($unit['spisok_id'], $dialog);

	if($dialog['table_name_1'] == '_page')
		_cache_clear('page');

	if($dialog['table_name_1'] == '_dialog')
		_BE('dialog_clear');

	//было назначено действие
	if($dialog['table_name_1'] == '_action')
		if($dialog['id'] == 229)
			_BE('hint_clear');
		else
			_BE('action_clear');

	//изменены данные пользователя
	if($dialog['table_name_1'] == '_user')
		_cache_clear('user'.$unit_id);
	else {
		$DLG = $dialog;
		while($dip = $DLG['dialog_id_parent']) {
			$DLG = _dialogQuery($dip);
			if($DLG['table_name_1'] == '_user')
				_cache_clear('user'.$unit_id);
		}
	}

	_app_create($dialog, $unit_id);
	_app_copy($dialog, $unit_id);

	$send = array(
		'unit' => _arrNum($unit),
		'action_id' => _num($dialog[ACT.'_action_id']),
		'action_obj_id' => _num($dialog[ACT.'_action_obj_id'])
	);

	$send = _spisokAction1($send);
	$send = _spisokAction3($dialog, $unit_id, $send);
	$send = _spisokAction4($send);

	if(IS_ELEM) {
		if($block_id = $unit['block_id']) {
			$bl = _blockOne($block_id, true);
			$send['js_upd'] = true;
			$send['jsblk'] = _BE('block_arr', $bl['obj_name'], $bl['obj_id']);
			$send['jselm'] = _elmJs($bl['obj_name'], $bl['obj_id']);
		}
	}

	return $send;
}
function _SUN_CMP_TEST($dialog, $unit_id) {//проверка корректности компонентов диалога
	$DLG = _dialogParent($dialog);

	if(!$DLG['table_1'])
		return array();

	$POST_CMP = @$_POST['cmp'];

	if($dialog['cmp_no_req'] && empty($POST_CMP))
		return array();
	if(empty($POST_CMP))
		jsonError('Отсутствуют данные для внесения записи');
	if(!is_array($POST_CMP))
		jsonError('Данные не являются массивом');

	//выбор значений, которые существуют в диалоговом окне, чтобы в дальнейшем выстроить по порядку в соответствии с блоками
	$CMP = array();
	foreach($POST_CMP as $cmp_id => $val) {
		if(!$cmp_id = _num($cmp_id))
			jsonError('Некорректный id компонента диалога');
		if(empty($dialog['cmp'][$cmp_id]))
			jsonError('Отсутствует компонент id'.$cmp_id.' в диалоге');
		$CMP[$cmp_id] = $val;
	}

	$send = array();
	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		if(!isset($CMP[$cmp_id]))
			continue;
		if(!$col = _elemCol($cmp))
			continue;
		//диалог, которому принадлежит колонка
		if(!$COL_DLG_ID = _elemColDlgId($cmp_id))
			continue;

		//является ли колонка элемент из текущего диалога
		$cur = $dialog['id'] == $COL_DLG_ID;

		$v = _txt($CMP[$cmp_id]);

		//данные для формирования и отправки ошибки
		$is_err = 0;
		$err_msg = !empty($cmp['req_msg']) ? $cmp['req_msg'] : 'Необходимо заполнить поле,<br>либо выбрать значение';

		switch($cmp['dialog_id']) {
			//многострочное текстовое поле
			case 5:
				if($cur && !empty($cmp['req']) && !strlen($v))
					$is_err = 1;

				$send[$COL_DLG_ID][$cmp_id] = $v;
				break;
			//текстовое поле
			case 8:
				if($cur && !empty($cmp['req']) && !strlen($v))
					$is_err = 1;
				//цифры и числа
				if($cmp['num_1'] == 33) {
					$v = str_replace(',', '.', $v);
					$v = round($v, $cmp['num_2']);
					//разрешение вностиь Ноль
					if($cur && !empty($cmp['req']) && !$v && !$cmp['num_4'])
						$is_err = 1;
					if($v < 0 && !$cmp['num_3']) {
						$is_err = 1;
						$err_msg = 'Значение не может быть отрицательным';
					}
				}
				//проверка, чтобы артикул не совпадал с другими артикулами
				if($v)
					if($cmp['num_1'] == 34) {
						$sql = "SELECT COUNT(*)
								FROM  "._queryFrom($DLG)."
								WHERE "._queryWhere($DLG)."
								  AND "._queryCol_id($DLG)."!=".$unit_id."
								  AND "._queryColReq($DLG, $col)."='".addslashes($v)."'";
						if(query_value($sql)) {
							$is_err = 1;
							$err_msg = 'Данное значение содержится в другой записи<br>и не может повторяться';
						}
					}

				$send[$COL_DLG_ID][$cmp_id] = $v;
				break;
			//поле-пароль
			case 9:
				if($cur && !empty($cmp['req']) && !strlen($v)) {
					$is_err = 1;
					break;
				}

				if($v && strlen($v) < $cmp['num_1']) {
					$is_err = 1;
					$err_msg = 'Минимальная длина '.$cmp['num_1'].' символ'._end($cmp['num_1'], '', 'а', 'ов');
					break;
				}

				if($v)
					$send[$COL_DLG_ID][$cmp_id] = _authPassMD5($v);
				break;
			//Select: выбор записи из другого списка
			case 29:
				$v = $cmp['num_11'] ? _ids($v) : _num($v);

				if($cmp['num_7'] && !$v)
					$v = _elem29ValAuto($cmp, $_POST['vvv'][$cmp_id]);

				if($cur && !empty($cmp['req']) && !$v)
					$is_err = 1;

				$send[$COL_DLG_ID][$cmp_id] = $v;
				break;
			//Select: выбор колонки таблицы
			case 37:
				$send[$COL_DLG_ID][$cmp_id] = $v;
				if(!$el = _elemOne($unit_id))
					break;
				if(!$v)
					break;
				if(empty($el['col']))
					break;
				if($el['col'] == $v)
					break;

				$old = explode('_', $el['col']);
				$new = explode('_', $v);
				if($old[0] != $new[0]) {
					$is_err = 1;
					$err_msg = 'Нельзя изменить тип колонки';
					break;
				}

				//старое и новое имена колонки сохраняются для последующего переноса данных
				define('ELEM37_CHANAGE_COL', $el['col'].'-'.$v);
				break;
			//страница ВК
			case 300:
				if(_elem300VkIdTest($DLG, $v, $unit_id)) {
					$is_err = 1;
					$err_msg = 'Учётная запись vk.com: '.$v.' закреплена'.
							   '<br>'.
							   'за другим пользователем в системе';
				}
				$send[$COL_DLG_ID][$cmp_id] = $v;
				break;
			default:
				$ex = explode('_', $col);
				if($ex[0] == 'num')
					$v = _num($v, 1);
				if($ex[0] == 'sum')
					$v = _cena($v, 1);

				if($cur && !empty($cmp['req']) && !$v)
					if(strlen($v) && $ex[0] != 'txt')
						$is_err = 1;

				$send[$COL_DLG_ID][$cmp_id] = $v;
		}

		if($is_err)
			jsonError(array(
				'attr_cmp' => _elemAttrCmp($cmp)._dialogParam($cmp['dialog_id'], 'element_afics'),
				'text' => $err_msg
			));
	}

	if($dialog['cmp_no_req'] && !$send)
		return array($dialog['id']=>array());

	if(!$send)
		jsonError('Нет данных для внесения');

	return $send;
}
function _SUN_INTERCEPT($dialog, $POST_CMP) {//перехват внесения данных
	//[98] регистрация нового пользователя
	_auth98($dialog, $POST_CMP);
	//[99] авторизация по логину и паролю
	_auth99($dialog, $POST_CMP);
	//[131] создание пин-кода
	_pin131($dialog, $POST_CMP);
	//[132] изменение или удаление пин-кода
	_pin132($dialog, $POST_CMP);
	//[133] ввод пин-кода, чтобы войти в приложение
	_pin133($dialog, $POST_CMP);
	//[11] элемент выбирает значение из диалога
	_elem11_choose_mysave($dialog, $POST_CMP);
	//[19] элемент выбирает блоки из диалога
	_elem19_block_choose($dialog);
	//[22] выбор дополнительной колонки
	_elem22_col_dop($dialog);
	//[41] сохранение условий для фильтра
	PHP12_spfl_save($dialog);
	//[67] сохранение настройки истории действий
	PHP12_history_setup_save($dialog);
	//[1000] сохранение выбранных элементов для правила
	PHP12_elem_all_rule_setup_save($dialog);
	//[119] очистка приложения
	_d119_app_clear($dialog);
	//[112] Закрытие/открытие доступа к приложению
	_d112_app_access($dialog, $POST_CMP);
	//[120] клонирование приложения
	_clone_go($dialog, $POST_CMP);
	//[109] принятие приглашения в приложение
	_user_invite_submit($dialog);
	//[95] быстрое наполнение списка
	_elem95_save($dialog, $POST_CMP, @$_POST['vvv']);

	//[129] КОМТЕКС - перенос
//	_elem129_comtex($dialog, $POST_CMP);

	//[129] КУПЕЦ - перенос
	_elem129_kupez($dialog, $POST_CMP);
}
function _SUN_INSERT($DLG, $unit_id=0) {//внесение новой записи, если отсутствует
	if($unit_id)
		return $unit_id;
	if(!$DLG['table_1'])
		jsonError('Не указана таблица для внесения записи');

	$dialog_id = $DLG['id'];
	$parent_id = 0;//группировка в таблице _element

	//если производится вставка в блок: проверка, чтобы в блок не попало 2 элемента
	$block_id = _num($_POST['block_id']);
	$element_id = _num($_POST['element_id']);
	if(IS_ELEM && $block_id) {
		if(!$block = _blockOne($block_id))
			jsonError('Блока не сущетвует');
		//если происходит вставка дочернего элемента, подмена блока на родителя
		if($elem = _elemOne($block['elem_id'])) {
			if($elem['dialog_id'] == 23//таблица
			|| $elem['dialog_id'] == 44//сборный текст
			|| $elem['dialog_id'] == 88//таблица из нескольких списков
			) {
				$block_id = 0;
				$parent_id = $elem['id'];
			} else
				jsonError('В блоке уже есть элемент');
		}
	}

	if($DLG['table_name_1'] == '_action')
		if(!$block_id && !$element_id)
			jsonError('Отсутствует исходный блок или элемент<br>для назначения действия');

	/*
		Если диалог является дочерним, проверяется, совпадают ли таблицы.
		Если таблицы разные, то у дочернего всегда `_spisok`
		Сначала вносится запись в родительскую таблицу, её ID становится основным и привязывается к cnn_id в дочерней таблице
		`dialog_id` вносится от родительского диалога
	*/

	$table_1 = _table($DLG['table_1']);
	$table_2 = '';

	if($dip = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($dip);
		if($PAR['table_1'] != $DLG['table_1']) {
			$table_1 = _table($PAR['table_1']);
			$table_2 = _table($DLG['table_1']);
		}
		$dialog_id = $dip;
	}

	$sql = "INSERT INTO `".$table_1."` (`id`) VALUES (0)";
	$uid[$table_1] = query_id($sql);

	if($table_2) {
		$sql = "INSERT INTO `".$table_2."` (`cnn_id`) VALUES (".$uid[$table_1].")";
		$uid[$table_2] = query_id($sql);

		//если родительская таблица=`_user`, внесение записи для `_user_access`
		if($table_1 == '_user')
			_userAppAccessCreate(APP_ID, $uid[$table_1]);
	}

	/* ---=== Обновление обязательных колонок для обеих таблиц ===--- */

	if($tab = _queryTN($DLG, 'app_id', 1))
		//если вносится страница SA, id приложения не присваивается
		if(!($tab == '_page' && $dialog_id == '101')) {
			$app_id = APP_ID;
			//получение app_id для элемента
			if(IS_ELEM) {
				$app_id = 0;
				if($block_id) {
					$sql = "SELECT `app_id`
							FROM `_block`
							WHERE `id`=".$block_id;
					$app_id = query_value($sql);
				}
				if($parent_id) {
					$sql = "SELECT `app_id`
							FROM `_element`
							WHERE `id`=".$parent_id;
					$app_id = query_value($sql);
				}
			}
			if($tab == '_hint') {
				if($element_id) {
					$sql = "SELECT `app_id`
							FROM `_element`
							WHERE `id`=".$element_id;
					$app_id = query_value($sql);
				}
				if($block_id) {
					$sql = "SELECT `app_id`
							FROM `_block`
							WHERE `id`=".$block_id;
					$app_id = query_value($sql);
				}
			}

			$sql = "UPDATE `".$tab."`
					SET `app_id`=".$app_id."
					WHERE `id`=".$uid[$tab];
			query($sql);
		}

	if($tab = _queryTN($DLG, 'dialog_id', 1)) {
		$sql = "UPDATE `".$tab."`
				SET `dialog_id`=".$dialog_id."
				WHERE `id`=".$uid[$tab];
		query($sql);
	}

	//установка порядкового номера
	if($tab = _queryTN($DLG, 'num', 1)) {
		$ngIds = $dialog_id;
		if($ng = $DLG['num_group']) {
			$sql = "SELECT `id`
					FROM `_dialog`
					WHERE `num_group`=".$ng;
			if($ids = query_ids($sql))
				$ngIds = $ids;
		}
		$sql = "SELECT IFNULL(MAX(`num`),0)+1
				FROM `".$tab."`
				WHERE `dialog_id` IN (".$ngIds.")";
		$num = query_value($sql);

		$sql = "UPDATE `".$tab."`
				SET `num`=".$num."
				WHERE `id`=".$uid[$tab];
		query($sql);
	}

	if($tab = _queryTN($DLG, 'page_id', 1))
		if($page_id = _num($_POST['page_id'])) {
			$sql = "UPDATE `".$tab."`
					SET `page_id`=".$page_id."
					WHERE `id`=".$uid[$tab];
			query($sql);
		}

	if($block_id)
		if($tab = _queryTN($DLG, 'block_id', 1)) {
			$sql = "UPDATE `".$tab."`
					SET `block_id`=".$block_id."
					WHERE `id`=".$uid[$tab];
			query($sql);
		}

	//присвоение родительского значения элементу
	if(IS_ELEM && $parent_id) {
		$sql = "UPDATE `_element`
				SET `parent_id`=".$parent_id."
				WHERE `id`=".$uid[$table_1];
		query($sql);
	}

	//установка начальной ширины элементу
	if(IS_ELEM && $w = _num($DLG['element_width'])) {
		if($bl = _blockOne($block_id))
			if($w > $bl['width'])
				$w = $bl['width'];

		$sql = "UPDATE `_element`
				SET `width`=".$w."
				WHERE `id`=".$uid[$table_1];
		query($sql);
	}

	if($tab = _queryTN($DLG, 'sort', 1)) {
		$sql = "SELECT IFNULL(MAX(`sort`)+1,1)
				FROM `".$tab."`
				WHERE `id`";
		$sort = query_value($sql);

		$sql = "UPDATE `".$tab."`
				SET `sort`=".$sort."
				WHERE `id`=".$uid[$tab];
		query($sql);
	}

	if($tab = _queryTN($DLG, 'user_id_add', 1)) {
		$sql = "UPDATE `".$tab."`
				SET `user_id_add`=".USER_ID."
				WHERE `id`=".$uid[$tab];
		query($sql);
	}

	if($tab = _queryTN($DLG, 'element_id', 1))
		if($tab == '_action')
			if($element_id) {
				$sql = "UPDATE `_action`
						SET `element_id`=".$element_id."
						WHERE `id`=".$uid[$tab];
				query($sql);
			}

	//установка `app_id` для `_action`
	if($table_1 == '_action') {
		$app_id = 0;
		if($block_id) {
			$sql = "SELECT `app_id`
					FROM `_block`
					WHERE `id`=".$block_id;
			$app_id = query_value($sql);
		}
		if($element_id) {
			$sql = "SELECT `app_id`
					FROM `_element`
					WHERE `id`=".$element_id;
			$app_id = query_value($sql);
		}
		$sql = "UPDATE `_action`
				SET `app_id`=".$app_id."
				WHERE `id`=".$uid[$tab];
		query($sql);
	}

	//удаление предыдущего действия (когда разрешено назначать только оно действие)
	if($table_1 == '_action')
		switch($DLG['id']) {
//			case 221:
			case 222:
			case 224:
			case 227:
				$sql = "SELECT *
						FROM `_action`
						WHERE `id`=".$uid[$table_1];
				if(!$r = query_assoc($sql))
					break;

				$sql = "DELETE
						FROM `_action`
						WHERE `block_id`=".$r['block_id']."
						  AND `element_id`=".$r['element_id']."
						  AND `dialog_id` IN (221,222,224,227)
						  AND `id`!=".$uid[$table_1];
				query($sql);
		}

	_dialogIUID($DLG, $uid[$table_1]);
	_historyInsert(1, $DLG, $uid[$table_1]);

	return $uid[$table_1];
}
function _elementFocusClear($dialog, $POST_CMP, $unit_id) {//предварительное снятие флага фокуса `focus` с элементов
	if(!IS_ELEM)
		return;
	if(empty($POST_CMP))
		return;
	if(!$unit_id)
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'focus')
			continue;
		if(!$v)
			return;
		if(!$el = _elemOne($unit_id))
			return;
		if(!$bl = _blockOne($el['block_id']))
			return;
		if(!$ids = _BE('elem_ids_arr', $bl['obj_name'], $bl['obj_id']))
			return;

		$sql = "UPDATE `_element`
				SET `focus`=0
				WHERE `id` IN (".implode(',', $ids).")";
		query($sql);

		return;
	}
}
function _pageDefClear($dialog, $POST_CMP) {//для таблицы _page: очистка `def`, если устанавливается новая страница по умолчанию
	if(_table($dialog['table_1']) != '_page')
		return;
	if(empty($POST_CMP))
		return;

	foreach($POST_CMP as $cmp_id => $v) {
		if($dialog['cmp'][$cmp_id]['col'] != 'def')
			continue;
		if(!$v)
			return;

		//снятие флага 'страница по умолчанию' со всех страниц приложения
		$sql = "UPDATE `_page`
				SET `def`=0
				WHERE `dialog_id`=".$dialog['id'].
			($dialog['id'] == 20 ? " AND `app_id`=".APP_ID : '');
		query($sql);

		return;
	}
}
function _filterDefSet($dialog, $elem_id) {//обновление значения фильтра
	switch($dialog['id']) {
		//Фильтр: галочка
		case 62:
		//Фильтр: год и месяц
		case 72:
		//Фильтр: календарь
		case 77:
		//Фильтр: меню
		case 78:
		//Фильтр - Выбор нескольких групп значений
		case 102:
			$sql = "DELETE FROM `_user_spisok_filter`
					WHERE `element_id_filter`=".$elem_id;
			query($sql);
			_filter('cache_clear');
			break;
	}
}
function _SUN_CMP_UPDATE($DLG, $POST_CMP, $unit_id) {//обновление компонентов единицы списка
	if(empty($POST_CMP))
		return;
/*
	$uid[$DLG['table_name_1']] = $unit_id;

	//при наличии двух таблиц главной первой становится родительская
	if($dip = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($dip);
		if($PAR['table_1'] != $DLG['table_1']) {
			$sql = "SELECT `id`
					FROM `".$DLG['table_name_1']."`
					WHERE `cnn_id`=".$unit_id."
					  AND `app_id`=".APP_ID."
					LIMIT 1";
			$id2 = query_value($sql);
			$uid[$PAR['table_name_1']] = $unit_id;
			$uid[$DLG['table_name_1']] = $id2;
		}
	}
*/
	$DLG = _dialogParent($DLG);

	foreach($POST_CMP as $cmp_id => $v) {
		if(!$col = _elemCol($cmp_id))
			continue;
		if(!$cmp = _elemOne($cmp_id))
			continue;
		//[29] при выборе нескольких значений по краям ставятся нули с зяпятыми для фильра в будущем
		if($cmp['dialog_id'] == 29 && $cmp['num_11'])
			if($v = _ids($v))
				$v = '0,'.$v.',0';

/*
		if(!$tab = _queryTN($DLG, $col, 1)) {
			//если родительская таблица=`_user`, сохранение её колонок, если есть
			if($dip && !empty($PAR))
				if($PAR['table_1'] == 12)
					if(!_elem103save($cmp_id, $unit_id, $v)) {
						$sql = "UPDATE `_user_access`
								SET `".$col."`='".addslashes($v)."'
								WHERE `user_id`=".$unit_id."
								  AND `app_id`=".APP_ID;
						query($sql);
					}
			continue;
		}
*/
		$sql = "UPDATE "._queryFrom($DLG)."
				SET "._queryColReq($DLG, $col)."='".addslashes($v)."'
				WHERE "._queryWhere($DLG)."
				  AND "._queryCol_id($DLG)."=".$unit_id;
		query($sql);

		_elem1def($cmp_id, $unit_id, $v);
		_elem37changeCol($cmp_id, $unit_id);
	}
}
function _SUN_OTHER($arr) {//внесение данных из других диалогов
	if(empty($arr))
		return;

	foreach($arr as $dlg_id => $val) {
		$insert = true;
		foreach($val as $cmp_id => $v) {
			$cmp = _elemOne($cmp_id);
			if(!empty($cmp['req']) && !$v)
				$insert = false;
		}
		if(!$insert)
			continue;

		$dialog = _dialogQuery($dlg_id);
		$dialog['act'] = 1;
		$unit_id = _SUN_INSERT($dialog);
		_SUN_CMP_UPDATE($dialog, $val, $unit_id);
		$unit = _spisokUnitQuery($dialog, $unit_id, true);
		_SUN_AFTER($dialog, $unit);
	}
}
function _spisokUnitUpd42($DLG, $cmp) {//обновление некоторых данных другой записи [42]
	if(!$elem_id = $DLG['insert_unit_change_elem_id'])
		return;
	if(!$ass = PHP12_insert_unit_change_ass($DLG['insert_unit_change_v']))
		return;
	if(!$el = _elemOne($elem_id))
		return;
	if(!_elemIsConnect($el))
		return;
	//диалог записи, которую нужно обновлять
	if(!$DST = _dialogQuery($el['num_1']))
		return;
	//id записи, данные которой будут изменены
	if(!$unit_id = _num($cmp[$elem_id]))
		return;
	if(!$unit = _spisokUnitQuery($DST, $unit_id))
		return;

	$upd = array();
	foreach($ass as $dst_id => $src_id) {
		if(!$el = _elemOne($dst_id))
			continue;
		if(!$col = $el['col'])
			continue;
		if(!isset($cmp[$src_id]))
			continue;
		$upd[] = "`".$col."`='".addslashes($cmp[$src_id])."'";
	}

	if(empty($upd))
		return;

	$sql = "UPDATE "._queryFrom($DST)."
			SET ".implode(',', $upd)."
			WHERE "._queryWhere($DST)."
			  AND "._queryCol_id($DST)."=".$unit_id;
	query($sql);
}
function _spisokUnitDelSetup($dialog, $unit_id) {//присвоение id диалога при создании условий удаления записи
	if($dialog['id'] != 58)
		return;
	if(!$dlg_id = _num($_POST['dss'])) {
		$sql = "DELETE FROM `_element` WHERE `id`=".$unit_id;
		query($sql);
		jsonError('Отсутствует исходный диалог');
	}

	$sql = "UPDATE `_element`
			SET `num_1`=".$dlg_id."
			WHERE `id`=".$unit_id;
	query($sql);
}
function _spisokUnitDependUpd($dialog, $unitOld, $unit) {//обновление зависимых привязанных списков
														 //В случае если изменилось значение в привязанном списке, будут изменены все зависимые идентификаторы,
														 //а также пересчитаны счётчики у старого и нового владельца
	//дочерние диалоги не затрагиваются
	if($dialog['dialog_id_parent'])
		return;
	//только диалог, управляющий данными
	if($dialog['table_name_1'] != '_spisok')
		return;

	//получение диалогов, в которые встроен текущий (редактируемый) диалог
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$dialog['id'];
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `obj_id`,1
			FROM `_block`
			WHERE `obj_name`='dialog'
			  AND `id` IN ("._idsGet($arr, 'block_id').")";
	if(!$DLG_CUR = query_ass($sql))
		return;


	$UPD = array();//собирание записей, которые участвовали в изменении (для дальнейшего обновления их счётчиков)
	foreach($dialog['cmp'] as $cmp) {
		if($cmp['dialog_id'] != 29)
			continue;
		if($cmp['num_11'])
			continue;
		if(!$col = $cmp['col'])
			continue;

		if(!isset($unitOld[$col]))
			continue;
		$old = $unitOld[$col];
		if(is_array($old))
			$old = $old['id'];

		if(!isset($unit[$col]))
			continue;
		$new = $unit[$col];
		if(is_array($new))
			$new = $new['id'];

		if($old == $new)
			continue;

		//получение диалогов, в которые встроен привязанный список
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=29
				  AND `num_1`=".$cmp['num_1'];
		if(!$arr = query_arr($sql))
			continue;

		$sql = "SELECT `obj_id`,1
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id` IN ("._idsGet($arr, 'block_id').")";
		if(!$DLG_SP = query_ass($sql))
			continue;



		//получение общих диалогов
		$DLG_COM = array();
		foreach($DLG_SP as $id => $i)
			if(isset($DLG_CUR[$id]))
				$DLG_COM[] = $id;

		if(empty($DLG_COM))
			continue;



		//изменение значения в связанных записях
		foreach($DLG_COM AS $id) {
			if(!$dlg = _dialogQuery($id))
				continue;
			if($dlg['dialog_id_parent'])
				continue;
			if(!$dlg['table_1'])
				continue;


			//получение колонок, по которым будет получена связанная запись
			$colCur = '';//колонка, значение которой является исходным диалогом
			$colIn = ''; //колонка, значение которой произошло изменение в исходной записи
			foreach($dlg['cmp'] as $comCmp) {
				if($comCmp['dialog_id'] != 29)
					continue;
				if($comCmp['num_1'] == $dialog['id'])
					$colCur = $comCmp['col'];
				if($comCmp['num_1'] == $cmp['num_1'])
					$colIn = $comCmp['col'];
			}


			if(!$colCur || !$colIn)
				continue;

			$sql = "SELECT `id`
					FROM `".$dlg['table_name_1']."`
					WHERE `dialog_id`=".$id."
					  AND `".$colCur."`=".$unit['id']."
					  AND `".$colIn."` IN (".$old.")";
			if(!$spIds = query_ids($sql))
				continue;

			$sql = "UPDATE `".$dlg['table_name_1']."`
					SET `".$colIn."`=".$new."
					WHERE `id` IN (".$spIds.")";
			query($sql);



			//сбор значений, у которых потребуется обновлять счётчики
			$UPD[$cmp['num_1']] = _ids($old.','.$new);
		}
	}

	//обновление счётчиков у изменённых значений
	foreach($UPD as $dlg_id => $ids) {
		if(!$dlg = _dialogQuery($dlg_id))
			continue;

		foreach($dlg['cmp'] as $id => $cmp) {
			if($cmp['dialog_id'] == 54)
				_element54update($id, $ids);
			if($cmp['dialog_id'] == 55)
				_element55update($id, $ids);
		}
	}
}
function _spisokAction1($send) {//обновление страницы посредством AJAX
	if($send['action_id'] != 1 && $send['action_id'] != 5)
		return $send;
	if(!$page_id = _num(@$_POST['page_id']))
		return $send;

	$send['content'] = _pageShow($page_id);

	return $send;
}
function _spisokAction3($dialog, $unit_id, $send) {//добавление значений для отправки, если действие 3 - обновление содержания блоков
	if($send['action_id'] != 3)
		return $send;
	//должно быть действие над элементом
	if($dialog['table_1'] != 5)
		return $send;
	if(!$el = _elemOne($unit_id))
		return $send;
	//была вставка доп-значения для элемента
	if(!empty($el['parent_id']))
		return $send;
	if(!$bl = _blockOne($el['block_id']))
		return $send;

	$send['obj_name'] = $bl['obj_name'];
	$send['level'] = _blockLevelChange($bl['obj_name'], $bl['obj_id']);

	return $send;
}
function _spisokAction4($send) {//действие 4 - обновление исходного диалога
	if($send['action_id'] != 4)
		return $send;
	if(!$dialog_id = _num(@$_POST['dss']))
		return $send;

	$send['dss4'] = $dialog_id;

	return $send;
}


function _elem11_choose_mysave($dialog, $POST_CMP) {//выбор значения из диалога через [11]
	if(!IS_ELEM)
		return;
	//сохранение данных (выбор значения) должно происходить через [11]
	if($dialog['id'] != 11)
		return;
	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dialog['cmp']))
		jsonError('Пустой диалог 11');


	$elem_func_id = key($dialog['cmp']);

	if(empty($_POST['vvv'][$elem_func_id]['mysave']))
		return;

	if(!$v = $POST_CMP[$elem_func_id])
		jsonError('Значение не выбрано');

	$send = array(
		'v' => $v,
		'title' => _elemIdsTitle($v),
		'issp' => 0,
		'spisok' => array()
	);

	//получение значений привязанного списка
	if($elem_id = _num($v)) {
		if(_elemIsConnect($elem_id)) {
			$send['issp'] = 1;
			$spisok = _29cnn($elem_id);
			$send['spisok'] = PHP12_spfl_vvv_unshift($spisok);
		} else {
			$el = _elemOne($elem_id);
			if($el['dialog_id'] == 17) {
				$send['issp'] = 1;
				$send['spisok'] = _element('vvv', $el);
			}
		}
	}

	//определение, смотрит ли на изменения данного элемента элемент [85]
	if($el13_id = _num(@$_POST['vvv'][$elem_func_id]['is13'])) {
		$sql = "SELECT `id`
				FROM `_element`
				WHERE `dialog_id`=85
				  AND `num_1`=".$el13_id."
				LIMIT 1";
		if($el_id = query_value($sql))
			$send['spisok'] = _elem212ActionFormat($el_id, $v, $send['spisok']);
	}

	jsonSuccess($send);
}
function _elem19_block_choose($dialog) {//выбор блоков через [11]
	//выбор блоков должен происходить через [19]
	if($dialog['id'] != 19)
		return;

	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dialog['cmp']))
		jsonError('Пустой диалог 19');

	$vvv = @$_POST['vvv'];
	$elem_func_id = key($dialog['cmp']);

	$ids = _ids($vvv[$elem_func_id]);
	$count = _ids($ids, 'count');

	$send['ids'] = $ids;
	$send['title'] = $count ? $count.' блок'._end($count, '', 'а', 'ов') : '';

	jsonSuccess($send);
}
function _elem22_col_dop($DLG) {
	if($DLG['id'] != 22)
		return;
	if(!$CMP = @$_POST['cmp'])
		jsonError('Нет данных');
	if(!is_array($CMP))
		jsonError('Данные не являются массивом');


	$col_id = 0;
	foreach($CMP as $elem_id => $v) {
		if(!$el = _elemOne($elem_id))
			continue;
		if($el['dialog_id'] != 13)
			continue;
		$col_id = $v;
	}

	if(!$col_id)
		jsonError('Колонка не выбрана');
	if(!$el = _elemOne($col_id))
		jsonError('Элемента '.$col_id.' не существует');
	if(!$col = $el['col'])
		jsonError('Выбранный элемент не содержит колонку');
	if(!$bl = _blockOne($el['block_id']))
		jsonError('Блока '.$el['block_id'].' не существует');
	if(!$dlg = _dialogQuery($bl['obj_id']))
		jsonError('Диалога '.$bl['obj_id'].' не существует');

	$u = array(
		'id' => $col_id,
		'title' => $dlg['name'].': '.$el['name'],
		'content' => $dlg['name'].': '.$el['name'].' <b class="clr2">'.$col.'</b>'
	);

	jsonSuccess($u);
}

function _d112_app_access($DLG, $POST_CMP) {//Закрытие / открытие доступа к приложению
	if($DLG['id'] != 112)
		return;
	if(!SA)
		jsonError('Нет прав');
	if(empty($POST_CMP))
		jsonError('Нет данных');

	$key = key($POST_CMP);

	$v = _bool($POST_CMP[$key] - 12745);

	$sql = "UPDATE `_setting`
			SET `v`=".$v."
			WHERE `key`='APP_ACCESS'";
	query($sql);

	_cache_clear('SETTING', 1);

	$send = array(
		'action_id' => 1 //обновить страницу
	);

	jsonSuccess($send);
}

function _filterUpdateBlkHidden($block_id, $source=array()) {//получение блоков, которые нужно скрыть или показать после обновления фильтра через AJAX
	if(!$bl = _blockOne($block_id))
		return array();

	$prm = _blockParam(array(), $bl['obj_name']);

	$send = array();
	foreach(_BE('block_arr', $bl['obj_name'], $bl['obj_id']) as $id => $r) {
		$r = _blockAction201($r, $prm);
		$send[$id] = $r['hidden'];
	}

	if(empty($source))
		return $send;

	$upd = array();
	foreach($source as $id => $v)
		if($send[$id] != $v)
			$upd[$id] = $v;

	return $upd;
}
function _filterSpisokIgnore($spisok_id) {//игнорирование одних фильтров другими
	$send = array();
	foreach(_filter('spisok', $spisok_id) as $F) {
		$el = $F['elem'];
		$send[] = _arrNum(array(
			'id' => $el['id'],
			'dialog_id' => $el['dialog_id'],
			'ignore' => _filterIgnore($el)
		));
	}

	return $send;
}




