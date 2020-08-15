<?php

/* [95] Быстрое формирование списка */

/* Структура элемента */
function _element95_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[24] список
		'num_2'   => _num($el['num_2']),//[1] показывать имена колонок
		'txt_1'   => $el['txt_1'],      //[8] текст кнопки добавления
		'txt_2'   => $el['txt_2']       //[12] данные колонок в формате JSON
	) + _elementStruct($el);
}
function _element95_js($el) {
	return array(
		'txt_1'   => $el['txt_1'],
		'num_2'   => _num($el['num_2'])
	) + _elementJs($el);
}

/* Вывод содержимого элемента на экран */
function _element95_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return _emptyMin(_debugPrint('[95] ').$el['name']);

	$cols = json_decode($el['txt_2'], true);
	if(empty($cols))
		return _emptyMinRed(_debugPrint('[95] ').'Не настроены колонки');

	return '';
}

function _element95_vvv($el, $prm) {
	if(empty($el['txt_2']))
		return array();

	$mass = _element95_mass($el, $prm);

	$cols = json_decode($el['txt_2'], true);
	foreach($cols as $i => $r) {
		if($r['type'] != 3)
			continue;

		$cols[$i]['spisok'] = _elem95_spisok($r['v']);

		//сборка id, которые были внесены ранее (для редактирования)
		$ids = array();
		foreach($mass as $m)
			$ids[] = _num($m[$r['col']]);

		$ids = array_unique($ids);
		$ids = implode(',', $ids);
		if($ids) {
			$ids .= ','._idsGet($cols[$i]['spisok'], 'id');
			$cols[$i]['spisok'] = _elem95_spisok($r['v'], '', $ids);
		}
	}

	return array(
		'cols' => $cols,
		'mass' => $mass
	);
}
function _element95_mass($el, $prm) {//данные для редактирования
	if(!$DLG_INS = _dialogQuery($el['num_1']))
		return array();
	if(!$tab = $DLG_INS['table_name_1'])
		return array();

	//получение диалога, в котором расположен элемент [95]
	if(!$dlg_id = _elemDlgId($el['id']))
		return array();
	if(!$DLG = _dialogQuery($dlg_id))
		return array();

	//поиск элемента-связки для ещё более точной выборки
	$qDop = '';
	foreach($DLG['cmp'] as $cmp)
		if($cmp['dialog_id'] == 29) {
			$sel_id = _elemPrintV($cmp, $prm, $cmp['num_6']);
			$sel_id = _elem29PageSel($cmp['num_1'], $sel_id);
			$sel_id = _elem29DialogSel($prm, $sel_id);
			$qDop = " AND `"._elemCol($cmp)."`=".$sel_id;
		}

	$sql = "SELECT *
			FROM `".$tab."`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$DLG_INS['id']."
			  ".$qDop."
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	//получение имён колонок элемента [95]
	$cols95 = array();
	$json = json_decode($el['txt_2'], true);
	if(empty($json))
		return array();
	foreach($json as $c)
		if($c['col'])
			$cols95[] = $c['col'];

	$send = array();
	foreach($arr as $id => $r) {
		$v = array('id'=>$id);
		foreach($cols95 as $col) {
			if(preg_match(REGEXP_CENA_MINUS, $r[$col]))
				$r[$col] = $r[$col] * 1;
			$v[$col] = $r[$col];
		}

		$send[] = $v;
	}

	return $send;
}
function _elem95_spisok($elem_ids, $v='', $ids=0) {//получение данных для Select (type=3)
	$elem_id = _idsLast($elem_ids);
	if(!$dlg_id = _elemDlgId($elem_id))
		return array();
	if(!$DLG = _dialogQuery($dlg_id))
		return array();
	if(!$col = _elemCol($elem_id))
		return array();

	$sql = "SELECT *,".$col." `title`
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
	".($v ? " AND `".$col."` LIKE '%".addslashes($v)."%'" : '')."
  ".($ids ? " AND `id` IN (".$ids.")" : '')."			
			ORDER BY `id` DESC
			LIMIT ".($ids ? 100 : 50);
	if(!$arr = query_arr($sql))
		return array();

	$arr = _spisokInclude($arr);

	$send = array();
	foreach($arr as $r)
		$send[] = $r;

	return _arrNum($send);
}
function _elem95_save($DLG, $CMP, $VVV) {//сохранение данных
	//определение, присутствует ли элемент [95] в диалоге
	$el95_id = 0;
	foreach($DLG['cmp'] as $cmp_id => $r)
		if($r['dialog_id'] == 95)
			$el95_id = $cmp_id;
	if(!$el95_id)
		return;

	//получение id диалога, по которому будут вноситься данные
	if(!$el95 = _elemOne($el95_id))
		jsonError(_debugPrint('[95] ').'Не получены данные элемента');
	if(!$DLG_INS = _dialogQuery($el95['num_1']))
		jsonError(_debugPrint('[95] ').'Не получены данные диалога '.$el95['num_1']);
	if(!$tab = $DLG_INS['table_name_1'])
		jsonError(_debugPrint('[95] ').'Не получена таблица для внесения данных');

	if(empty($VVV[$el95_id])) {
		_elem95_deleted($DLG, $DLG_INS);
		$send = array(
			'action_id' => _num($DLG[ACT.'_action_id']),
			'action_page_id' => _num($DLG[ACT.'_action_page_id'])
		);
		_count_update();
		jsonSuccess($send);
	}

	$vvv = $VVV[$el95_id];

	//получение колонок из элемента [95], отвечающих за внесение данных
	$cols95 = array();
	foreach($DLG['cmp'] as $r)
		if($r['dialog_id'] == 95) {
			$json = json_decode($r['txt_2'], true);
			if(empty($json))
				jsonError('Элемент Быстрое формирование списка не настроен');
			foreach($json as $c)
				if($c['col'])
					$cols95[] = $c;
			break;
		}

	//составление колонок для внесения данных
	$cols = array();
	$cols[] = '`app_id`';
	$cols[] = '`dialog_id`';
	$cols[] = '`sort`';
	$cols[] = '`user_id_add`';
	foreach($DLG['cmp'] as $cmp_id => $r)
		if($col = _elemCol($cmp_id))
			$cols[] = '`'.$col.'`';

	$cols[] = '`id`';
	foreach($cols95 as $r)
		$cols[] = '`'.$r['col'].'`';

	$values = array();
	$idsNoDel = array();
	foreach($vvv as $n => $mass) {
		$idsNoDel[] = $mass[0];
		$val = array();
		$val[] = APP_ID;    //app_id
		$val[] = $DLG_INS['id'];//dialog_id
		$val[] = $n;        //sort
		$val[] = USER_ID;   //user_id_add

		foreach($DLG['cmp'] as $cmp_id => $r)
			if(_elemCol($cmp_id))
				$val[] = "'".addslashes($CMP[$cmp_id])."'";

		foreach($mass as $i => $v) {
			if(!$i) {
				$val[] = $v;//id
				continue;
			}

			$ex = explode('_', $cols95[$i-1]['col']);
			if($ex[0] == 'num')
				$v = _num($v, 1);
			if($ex[0] == 'sum')
				$v = _cena($v, 1);

			$val[] = "'".$v."'";
		}

		$values[] = "(".implode(',', $val).")";
	}

	//удаление удалённых строк
	if($idsNoDel) {
		$ids = implode(',', $idsNoDel);
		_elem95_deleted($DLG, $DLG_INS, $ids);
	}

	//составление колонок для обновления значений, если редактирование
	$upd = array();
	$upd[] = "`sort`=VALUES(`sort`)";
	foreach($cols95 as $r)
		$upd[] = "`".$r['col']."`=VALUES(`".$r['col']."`)";

	$sql = "INSERT INTO `".$tab."` (".implode(',', $cols).")
			VALUES ".implode(',', $values)."
			ON DUPLICATE KEY UPDATE ".implode(',', $upd);
	query($sql);

	$send = array(
		'action_id' => _num($DLG[ACT.'_action_id']),
		'action_page_id' => _num($DLG[ACT.'_action_page_id'])
	);

	_count_update();

	jsonSuccess($send);
}
function _elem95_deleted($DLG, $DLG_INS, $ids=0) {
	//поиск элемента-связки для ещё более точной выборки
	$qDop = '';
	foreach($DLG['cmp'] as $cmp)
		if($cmp['dialog_id'] == 29) {
			$prm = _blockParam();
			$sel_id = _elemPrintV($cmp, $prm, $cmp['num_6']);
			$sel_id = _elem29PageSel($cmp['num_1'], $sel_id);
			$sel_id = _elem29DialogSel($prm, $sel_id);
			$qDop = " AND `"._elemCol($cmp)."`=".$sel_id;
		}

	$sql = "UPDATE `".$DLG_INS['table_name_1']."`
			SET `deleted`=1,
				`user_id_del`=".USER_ID.",
				`dtime_del`=CURRENT_TIMESTAMP
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$DLG_INS['id']."
			  ".$qDop."
  ".($ids ? " AND `id` NOT IN (".$ids.")" : '');
	query($sql);
}



function PHP12_elem95_setup($prm) {//настройка колонок списка
	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка колонок будет доступна после вставки элемента в блок.');
	if(!$BL = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('[95] Отсутствует исходный блок.');
	if(!$el = _elemOne($BL['elem_id']))
		return _emptyMin10('[95] Отсутствует элемент.');

	return '<div class="calc-div h25 line-b bg-efe">'._elemWidth($el).'</div>';
}
function PHP12_elem95_setup_save($cmp, $val, $unit) {//сохранение данных колонок
	/*
		сохранение в формате JSON в txt_2:
			w - ширина
			name - имя заголовка
			type - тип колонки
			col - имя колонки в таблице
			v - значение
	*/

	if(empty($unit['id']))
		return;
	if(!$col = $cmp['col'])
		return;

	$save = array();
	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$type = _num($r['type']))
					continue;

				if(!isset($r['v']))
					$r['v'] = '';
				if(!isset($r['col']))
					$r['col'] = '';

				$save[] = array(
					'w' => _num($r['w']),
					'name' => $r['name'],
					'type' => $type,
					'col' => $r['col'],
					'v' => $r['v']
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	query($sql);

	_BE('elem_clear');
}
function PHP12_elem95_setup_vvv($prm) {
	if(!$u = @$prm['unit_edit'])
		return array();

	$VAL = json_decode($u['txt_2'], true);

	foreach($VAL as $i => $r) {
		$VAL[$i]['title'] = '';
		if($r['type'] == 3)
			$VAL[$i]['title'] = _elemIdsTitle($r['v']);
	}

	return $VAL;
}











