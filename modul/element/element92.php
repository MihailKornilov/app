<?php

/* [92] Выбранные значения галочками */
function _element92_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

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
					'<th>Кол-во</br>записей'.
					'<th>Сумма';
	foreach($dlgIds as $r) {
		if(!$DLG = _dialogQuery($r['dlg_id']))
			continue;
		$send .= '<tr class="color-555">'.
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
	foreach($ell['vvv'] as $vv)
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
function _elem92dlg88($ell, $send) {//значения для таблицы их нескольких списков
	if($ell['dialog_id'] != 88)
		return $send;

	$V = json_decode($ell['txt_2'], true);
	$vvv = _arrKey($ell['vvv']);

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
function _elem92_cnn($dialog, $cmp, $unit) {//присвоение (или удаление) связанного списка у выбранных значений
	if(!$col = _elemCol($cmp))
		return;
	if(!$ids = _ids($unit[$col]))
		return;

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `id` IN (".$ids.")
			  AND !`deleted`";
	if(!$spisok = query_arr($sql))
		return;

	foreach($spisok as $r) {
		if(!$DLG = _dialogQuery($r['dialog_id']))
			continue;
		foreach($DLG['cmp'] as $cmpp)
			if(_elemIsConnect($cmpp))
				if($cmpp['num_1'] == $dialog['id'])
					if($coll = _elemCol($cmpp)) {
						$sql = "UPDATE `_spisok`
								SET `".$coll."`=".($unit['deleted'] ? 0 : $unit['id'])."
								WHERE `id`=".$r['id'];
						query($sql);
					}
	}
}
