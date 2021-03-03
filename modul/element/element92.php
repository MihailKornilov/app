<?php

/* [92] Выбранные значения галочками */
function _element92_struct($el) {
	return array(
		'txt_1'   => $el['txt_1']   //списки
	) + _elementStruct($el);
}
function _element92_print($el, $prm) {
	if(!$ids = _ids($el['txt_1'], 1))
		return _emptyMinRed('Не указаны списки, в которых производится выбор значений');
	if(!is_array($dlgIds = _elem92dlgIds($ids)))
		return $dlgIds;

	$send = '<table class="_stab">'.
				'<tr><th>Список'.
					'<th>Кол-во<br>записей'.
					'<th>Сумма';
	foreach($dlgIds as $r) {
		if(!$DLG = _dialogQuery($r['dlg_id']))
			continue;
		$send .= '<tr class="clr9">'.
					'<td>'.$DLG['name'].
					'<td class="center" id="el92_'.$r['dlg_id'].'">'.
					'<td class="sum92 r">'.
						'<div class="icon spin"></div>';
	}
	$send .= '<tr>'.
				'<td class="r b">Итог:'.
				'<td class="itog-c center b">'.
				'<td class="itog-sum r b">';
	$send .= '</table>';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm).'" />'.
	$send;
}
function _element92_vvv($el) {
	if(!$ids = _ids($el['txt_1'], 1))
		return array();
	return _elem92dlgIds($ids);
}
function _elem92dlgIds($ids) {
	$send = array();
	foreach($ids as $id) {
		//элемент-список
		if(!$ell = _elemOne($id))
			continue;
		$send[] = _elem92dlg23($ell);
		$send = _elem92dlg88($ell, $send);
	}

	foreach($send as $n => $r)
		if(empty($r))
			unset($send[$n]);

	return $send;
}
function _elem92dlg23($ell) {//значения для обычной таблицы
	if($ell['dialog_id'] != 23)
		return array();
	if(!$ell['num_1'])
		return array();

	//элемент-сумма для подсчёта, на который указывает галочка
	$sumId = 0;
	foreach(_element('vvv', $ell) as $vv)
		if($vv['dialog_id'] == 91) {
			$sumId = _num($vv['num_1']);
			break;
		}

	return array(
		'elm_id' => $ell['id'],
		'dlg_id' => $ell['num_1'],
		'sum_id' => $sumId
	);
}
function _elem92dlg88($ell, $send) {//значения для таблицы из нескольких списков
	if($ell['dialog_id'] != 88)
		return $send;

	$V = json_decode($ell['txt_2'], true);
	$vvv = _element('vvv', $ell);

	if(empty($V['spv']))
		return $send;

	foreach($V['spv'] as $n => $r) {
		//элемент-сумма для подсчёта, на который указывает галочка
		$sumId = 0;
		foreach($V['col'] as $col) {
			$elm_id = $col['elm'][$n];
			if(empty($vvv[$elm_id]))
				continue;
			$elv = $vvv[$elm_id];
			if($elv['dialog_id'] == 91) {
				$sumId = _num($elv['num_1']);
				break;
			}
		}

		$send[] = array(
			'elm_id' => $ell['id'],
			'dlg_id' => _num($r['dialog_id']),
			'sum_id' => $sumId
		);
	}
	return $send;
}
function _element92unitUpd($dlg, $unit) {//присвоение (или удаление) связанного списка у выбранных значений
	$countUpdate = false;
	foreach($dlg['cmp'] as $r) {
		if($r['dialog_id'] != 92)
			continue;
		if(!$col = _elemCol($r))
			continue;
		if(empty($unit[$col]))
			continue;

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `id` IN (".$unit[$col].")
				  AND !`deleted`";
		if(!$spisok = query_arr($sql))
			continue;

		$dlgAss = array();
		foreach($spisok as $sp)
			$dlgAss[$sp['dialog_id']][] = $sp['id'];

		$unit_id = $unit['deleted'] ? 0 : $unit['id'];

		foreach($dlgAss as $dialog_id => $ids) {
			if(!$DLG = _dialogQuery($dialog_id))
				continue;

			foreach($DLG['cmp'] as $cmpp) {
				if(!_elemIsConnect($cmpp))
					continue;
				if($cmpp['num_1'] != $dlg['id'])
					continue;
				if(!$coll = _elemCol($cmpp))
					continue;

				$sql = "UPDATE `_spisok`
						SET `".$coll."`=".$unit_id."
						WHERE `id` IN ("._ids($ids).")";
				query($sql);

				$countUpdate = true;
			}
		}
	}

	if(!$countUpdate)
		return;

	//обновление счётчиков
	foreach($dlg['cmp'] as $elem_id => $r) {
		if($r['dialog_id'] == 54)
			_element54update($elem_id, $unit['id']);
		if($r['dialog_id'] == 55)
			_element55update($elem_id, $unit['id']);
	}
}
