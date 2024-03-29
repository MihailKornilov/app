<?php

/* [95] Быстрое формирование списка
	Диалог, в который вставлен этот элемент, требует указания родительского диалога
*/

function _element95_struct($el) {
	return array(
		'num_2'   => _num($el['num_2']),//[1] показывать имена колонок
		'txt_1'   => $el['txt_1'],      //[8] текст кнопки добавления
		'txt_2'   => $el['txt_2']       //[12] данные колонок в формате JSON
	) + _elementStruct($el);
}
function _element95_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return _emptyMin(_debugPrint('[95] ').$el['name']);
	if(!_decode($el['txt_2']))
		return _emptyMinRed(_debugPrint('[95] ').'Не настроены колонки');

	return '';
}
function _element95_vvv($el, $prm) {

	$send = array(
		'cols' => array(),
		'mass' => _element95_mass($el, $prm)
	);

	if(!$cols = _decode($el['txt_2']))
		return $send;

	foreach($cols as $i => $r) {
		if($r['type'] != 3)
			continue;

		$cols[$i]['spisok'] = _elem95_spisok($r['v']);

		//сборка id, которые были внесены ранее (для редактирования)
		$ids = array();
		foreach($send['mass'] as $m)
			$ids[] = _num($m[$r['col']]);

		$ids = array_unique($ids);
		$ids = implode(',', $ids);
		if($ids) {
			$ids .= ','._idsGet($cols[$i]['spisok'], 'id');
			$cols[$i]['spisok'] = _elem95_spisok($r['v'], '', $ids);
		}
	}

	$send['cols'] = $cols;

	return $send;
}
function _element95_mass($el, $prm) {//данные для редактирования
//	if(!$DLG_INS = _dialogQuery($el['num_1']))
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
			$sel_id = _elem29DialogSel($cmp, $prm, $sel_id);
			$qDop = " AND `"._elemCol($cmp)."`=".$sel_id;
		}

	$sql = "SELECT *
			FROM `".$tab."`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$DLG_INS['id']."
			  ".$qDop."
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$arr = DB1::arr($sql))
		return array();

	//получение имён колонок элемента [95]
	$cols95 = array();
	if(!$json = _decode($el['txt_2']))
		return array();

	foreach($json as $c)
		if($c['col'])
			$cols95[] = $c['col'];

	$send = array();
	foreach($arr as $id => $r) {
		$v = array('id'=>$id);
		foreach($cols95 as $col) {
			if(preg_match(REGEXP_CENA_MINUS, $r[$col]))
				$r[$col] *= 1;
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
	if(!$col = _elemCol($DLG['spisok_elem_id']))
		return array();

	$sql = "SELECT *
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
	".($v ? " AND `".$col."` LIKE '%".addslashes($v)."%'" : '')."
  ".($ids ? " AND `id` IN (".$ids.")" : '')."			
			ORDER BY `id` DESC
			LIMIT ".($ids ? 100 : 50);
	if(!$arr = DB1::arr($sql))
		return array();

	$arr = _spisokInclude($arr);

	if(!$elT = _elemOne($elem_id))
		return array();

	$send = array();
	foreach($arr as $r) {
		$r['title'] = $r[$col];

		switch($elT['dialog_id']) {
			//шаблон записи
			case 43:
				$r['content'] = _element43_print11($elT, $r);
				break;

			//сборный текст
			case 44:
				$prm = _blockParam();
				$prm['unit_get'] = $r;
				$r['content'] = _element44_print($elT, $prm);
				break;
		}

		$send[] = $r;
	}

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
	if(!$dlg_id = _blockDlgId($el95['block_id']))
		jsonError(_debugPrint('[95] ').'Не получен id диалога, в котором расположен элемент');
	if(!$DLG = _dialogQuery($dlg_id))
		jsonError(_debugPrint('[95] ').'Не получены данные диалога '.$dlg_id);
	if(!$DLG_INS = _dialogQuery($DLG['dialog_id_parent']))
		jsonError(_debugPrint('[95] ').'Не получены данные родительского диалога ');
	if(!$tab = $DLG_INS['table_name_1'])
		jsonError(_debugPrint('[95] ').'Не получена таблица для внесения данных');

	if(empty($VVV[$el95_id])) {
//		_elem95_deleted($DLG, $DLG_INS);
		$send = array(
			'action_id' => _num($DLG[ACT.'_action_id']),
			'action_obj_id' => _num($DLG[ACT.'_action_obj_id']),
			'content' => _pageShow(_page('cur'))
		);
//		_count_update();
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
			$ex = explode('_', $cols95[$i]['col']);
			if($ex[0] == 'num')
				$v = _num($v, 1);
			if($ex[0] == 'sum')
				$v = _cena($v, 1);

			$val[] = "'".$v."'";
		}

		$values[] = "(".implode(',', $val).")";
	}

	//удаление удалённых строк
/*
	if($idsNoDel) {
		$ids = implode(',', $idsNoDel);
		_elem95_deleted($DLG, $DLG_INS, $ids);
	}
*/

	//составление колонок для обновления значений, если редактирование
	$upd = array();
	$upd[] = "`sort`=VALUES(`sort`)";
	foreach($cols95 as $r)
		$upd[] = "`".$r['col']."`=VALUES(`".$r['col']."`)";

	$sql = "INSERT INTO `".$tab."` (".implode(',', $cols).")
			VALUES ".implode(',', $values)."
			ON DUPLICATE KEY UPDATE ".implode(',', $upd);
	DB1::query($sql);

	_count_update();

	$send = array(
		'action_id' => _num($DLG[ACT.'_action_id']),
		'action_obj_id' => _num($DLG[ACT.'_action_obj_id']),
		'content' => _pageShow(_page('cur'))
	);

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
			$sel_id = _elem29DialogSel($cmp, $prm, $sel_id);
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
	DB1::query($sql);
}



function PHP12_elem95_setup($prm) {//настройка колонок списка
	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка колонок будет доступна после вставки элемента в блок.');
	if(!$BL = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('[95] Отсутствует исходный блок.');
	if(!$el = _elemOne($BL['elem_id']))
		return _emptyMin10('[95] Отсутствует элемент.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMin10('[95] Элемент вставлен не в диалог.');
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return _emptyMin10('[95] Диалога '.$BL['obj_id'].' не существует.');
	if(!$dialog_id = $DLG['dialog_id_parent'])
		return _emptyMinRed10('[95] Необходимо указать родительский диалог.');

	return '<div class="calc-div h25 line-b bg5" val="'.$dialog_id.'">'._elemWidth($el).'</div>';
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
	if(!$col = _elemCol($cmp))
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
	DB1::query($sql);

	_BE('elem_clear');
}
function PHP12_elem95_setup_vvv($prm) {
	if(!$u = @$prm['unit_edit'])
		return array();
	if(!$VAL = _decode($u['txt_2']))
		return array();

	foreach($VAL as $i => $r) {
		$VAL[$i]['title'] = '';
		if($r['type'] == 3)
			$VAL[$i]['title'] = _elemIdsTitle($r['v']);
	}

	return $VAL;
}











