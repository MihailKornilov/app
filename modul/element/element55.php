<?php

/* [55] Сумма значений привязанного списка */
function _element55_struct($el) {
	/*
		для хранения сумм используется колонка sum_1, sum_2, ...
	*/
	return array(
		'num_1'   => _num($el['num_1']),// [24] id элемента, указывающего на привязанный список
		'txt_1'   => $el['txt_1'],      // фильтр
		'num_2'   => _num($el['num_2']),// id элемента значения (колонки) привязанного списка
		'num_3'   => _num($el['num_3']) // включение счётчика
	) + _elementStruct($el);
}
function _element55_print($el) {
	return $el['name'];
}
function _element55_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element55_template_docx($el, $u) {
	return _element55_print11($el, $u);
}

function _element55update($elem_id, $unit_ids=0) {//обновление сумм
	if(!$el = _elemOne($elem_id))
		return;
	if($el['dialog_id'] != 55)
		return;
	if(!$col = _elemCol($el))
		return;

	//компонент в диалоге, в котором размещается привязка (сумма этих значений будет считаться)
	if(!$cmp = _elemOne($el['num_1']))
		return;
	if(!$cmpCol = _elemCol($cmp))
		return;
	if(!$bl = _blockOne($cmp['block_id']))
		return;
	if(!$DConn = _dialogQuery($bl['obj_id']))//диалог, в котором размещается привязка
		return;

	//диалог, к которому привязан список (данные этого списка будут обновляться)
	if(!$DSrc = _dialogQuery($cmp['num_1']))
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._queryFrom($DSrc)."
			SET `".$col."`=0
			WHERE "._queryWhere($DSrc).
($unit_ids ? " AND `t1`.`id` IN (".$unit_ids.")" : '');
	query($sql);

	//получение колонки, по которой нужно будет считать сумму значения
	if(!$sumCol = _elemCol($el['num_2']))
		return;

	$sql = "SELECT
				`".$cmpCol."`,
				SUM(`".$sumCol."`)
			FROM "._queryFrom($DConn)."
			WHERE "._queryWhere($DConn)."
			  AND `".$cmpCol."`".($unit_ids ? " IN (".$unit_ids.")" : '')."
			  "._40cond(array(), $el['txt_1'])."
			GROUP BY `".$cmpCol."`";
	if(!$ass = query_ass($sql)) {//выход, если нечего обновлять
		_element27childChange($elem_id, $unit_ids);
		return;
	}

	//если присутствует второй диалог, подмена ids
	if($DSrc['dialog_id_parent']) {
		//сохраниние исходного списка
		$saveAss = array();
		foreach($ass as $id => $r)
			$saveAss[$id] = $r;

		$sql = "SELECT `id`,`cnn_id`
				FROM `_spisok`
				WHERE `cnn_id` IN ("._idsGet($saveAss, 'key').")
				  AND `app_id`=".$DSrc['app_id']."
				  AND !`deleted`";
		if(!$ass = query_ass($sql))
			return;

		foreach($ass as $id => $cnn_id)
			$ass[$id] = $saveAss[$cnn_id];
	}

	$n = 1000;
	$upd = array();
	$cAss = count($ass);
	foreach($ass as $id => $sum) {
		$upd[] = "(".$id.",".$sum.")";
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
}
function _element55unitUpd($dlg, $unitNew, $unitOld) {//обновить сумму, если была внесена или удалена запись
	//поиск элементов-связок в диалоге
	$ids = array();
	foreach($dlg['cmp'] as $id => $r) {
		if(!_elemIsConnect($r))
			continue;
		if(!$col = _elemCol($r))
			continue;

		//новый и старый владельцы значения, указанные в редактируемом диалоге
		//если они различаются - будет обновление сумм
		$uidNew = _num(@$unitNew[$col]['id']);
		$uidOld = _num(@$unitOld[$col]['id']);

		if(!$uidNew && !$uidOld)
			continue;

		$ids[$id][] = $uidNew;
		$ids[$id][] = $uidOld;
	}

	if(empty($ids))
		return;

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=55
			  AND `num_1` IN ("._idsGet($ids, 'key').")";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $elem_id => $r) {
		if(!$colSum = _elemCol($r['num_2']))
			continue;

		$id = $r['num_1'];

		//если различаются новое и старое значения, указанные в редактируемом диалоге - будет обновление сумм
		$sumNew = round(@$unitNew[$colSum], 10);
		$sumOld = round(@$unitOld[$colSum], 10);

		if($ids[$id][0] == $ids[$id][1] && $sumNew == $sumOld)
			continue;

		$mass = array_unique($ids[$id]);

		_element55update($elem_id, _ids($mass));

		foreach($mass as $uid)
			_element55Counter($r, $uid, $dlg, $unitNew, $sumOld);
	}
}
function _element55Counter($el, $uid, $dlg, $unit, $sumOld) {//внесение данных в счётчик
	if(!$col = _elemCol($el))
		return;
	//блок, в котором расположен счётчик
	if(!$bl = _blockOne($el['block_id']))
		return;
	if($bl['obj_name'] != 'dialog')
		return;
	//диалог, в котором расположен счётчик
	if(!$dlg55 = _dialogQuery($bl['obj_id']))
		return;
	//запись, из которой будет взята сумма
	if(!$u55 = _spisokUnitQuery($dlg55, $uid))
		return;
	if(!isset($u55[$col]))
		return;

	$balans = round($u55[$col], 10);

	//колонка, по которому вносилось значение
	if(!$colV = _elemCol($el['num_2']))
		return;
	if(!isset($unit[$colV]))
		return;

	$sum = round($unit[$colV], 10);

	if($el['num_3'])
		_counterV($el['id'], $uid, $dlg, $unit['id'], $balans, $sum, $sumOld);

	_element27counter($el['id'], $uid, $dlg, $unit, $sum, $sumOld);
}

