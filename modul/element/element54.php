<?php

/* [54] Количество значений привязанного списка */
function _element54_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),// [24] id элемента-связки, указывающего на привязанный список
		'num_3'   => _num($el['num_3']),// [1] включение счётчика
		'txt_1'   => $el['txt_1']       // фильтр
	) + _elementStruct($el);
}
function _element54_print($el) {
	return $el['name'];
}
function _element54_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element54_template_docx($el, $u) {
	return _element54_print11($el, $u);
}

function _element54update($elem_id, $unit_ids=0) {//обновление количеств, если был отредактирован счётчик
	if(!$el = _elemOne($elem_id))
		return;
	if($el['dialog_id'] != 54)
		return;
	if(!$col = _elemCol($el))
		return;

	//компонент в диалоге, в котором размещается привязка (количество этих значений будет считаться)
	if(!$cmp = _elemOne($el['num_1']))
		return;
	if(!$cmpCol = _elemCol($cmp))
		return;
	if(!$bl = _blockOne($cmp['block_id']))
		return;

	//диалог, в котором размещается привязка
	if(!$DConn = _dialogQuery($bl['obj_id']))
		return;

	//блок, в котором размещается "количество"
	if(!$BL = _blockOne($el['block_id']))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$DSrc = _dialogQuery($BL['obj_id']))//диалог, к которому привязан список (данные этого списка будут обновляться)
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._queryFrom($DSrc)."
			SET `".$col."`=0
			WHERE "._queryWhere($DSrc).
($unit_ids ? " AND "._queryCol_id($DSrc)." IN (".$unit_ids.")" : '');
	query($sql);

	$sql = "SELECT
				`".$cmpCol."`,
				COUNT(`id`)
			FROM "._queryFrom($DConn)."
			WHERE "._queryWhere($DConn)." 
			  AND `".$cmpCol."`".($unit_ids ? " IN (".$unit_ids.")" : '')."
			  "._40cond($cmp, $el['txt_1'])."
			GROUP BY `".$cmpCol."`";
	if(!$ass = query_ass($sql)) {//выход, если нечего обновлять
		if($unit_ids)
			_element27childChange($elem_id, $unit_ids);
		return;
	}

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $count) {
		$upd[] = "(".$id.",".$count.")";
		if(!--$cAss || !--$n) {
			$sql = "INSERT INTO `"._table($DSrc['table_1'])."`
						(`id`,`".$col."`)
						VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`".$col."`=VALUES(`".$col."`)";
			query($sql);
			$n = 1000;
			$upd = array();
		}
	}

	_element27childChange($elem_id, $unit_ids);

	//обновление сумм родительских значений, если есть дочерние
	if(!isset($DSrc['field1']['parent_id']))
		return;
	if(!isset($DSrc['field1']['child_lvl']))
		return;

	$sql = "SELECT MAX(`child_lvl`)
			FROM `"._table($DSrc['table_1'])."`
			WHERE `dialog_id`=".$BL['obj_id'];
	if(!$lvl = _num(query_value($sql)))
		return;

	for($n = $lvl; $n > 0; $n--) {
		$sql = "SELECT
					DISTINCT `parent_id`,
					SUM(`".$col."`)
				FROM `"._table($DSrc['table_1'])."`
				WHERE `dialog_id`=".$BL['obj_id']."
				  AND `child_lvl`=".$n."
				GROUP BY `parent_id`";
		if($ass = query_ass($sql))
			foreach($ass as $id => $count) {
				$sql = "UPDATE `"._table($DSrc['table_1'])."`
						SET `".$col."`=`".$col."`+".$count."
						WHERE `id`=".$id;
				query($sql);
			}
	}
}
function _element54unitUpd($dlg, $unit, $unitOld) {//обновить количество, если была внесена или удалена запись
	$dlg = _dialogParent($dlg);

	//поиск элементов-связок в диалоге
	$ids = array();
	foreach($dlg['cmp'] as $id => $r) {
		if(!_elemIsConnect($r))
			continue;
		if(!$col = _elemCol($r))
			continue;

		//новое и старое значения, указанные в редактируемом диалоге
		//если они различаются, то будет обновление количеств
		$uid = 0;
		$uidOld = 0;

		if(!empty($unit[$col]))
			if(isset($unit[$col]['id']))
				$uid = $unit[$col]['id'];
		if(!empty($unitOld[$col]))
			if(isset($unitOld[$col]['id']))
				$uidOld = $unitOld[$col]['id'];

		if($uid != $uidOld) {
			if($uid)
				$ids[$id][] = $uid;
			if($uidOld)
				$ids[$id][] = $uidOld;
		}
	}

	if(empty($ids))
		return;

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=54
			  AND `num_1` IN ("._idsGet($ids, 'key').")";

	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $elem_id => $r) {
		$id = $r['num_1'];
		_element54update($elem_id, _ids($ids[$id]));
		foreach($ids[$id] as $uid)
			_element54Counter($r, $uid, $dlg, $unit);
	}
}
function _element54Counter($el, $uid, $dlg, $unit) {//внесение данных в счётчик
	_element27counter($el['id'], $uid, $dlg, $unit);

	if(!$el['num_3'])
		return;
	if(!$col = _elemCol($el))
		return;
	//блок, в котором расположен счётчик
	if(!$bl = _blockOne($el['block_id']))
		return;
	if($bl['obj_name'] != 'dialog')
		return;
	//диалог, в котором расположен счётчик
	if(!$dlg54 = _dialogQuery($bl['obj_id']))
		return;
	//запись, из которой будет взято количество
	if(!$u54 = _spisokUnitQuery($dlg54, $uid))
		return;
	if(!isset($u54[$col]))
		return;

	_counterV($el['id'], $uid, $dlg, $unit['id'], _num($u54[$col]));
}
