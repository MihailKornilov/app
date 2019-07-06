<?php

/* [44] Сборный текст */
function _element44_struct($el) {
	/*
		настройка значений через PHP12_44_setup
	*/
	return _elementStruct($el);
}
function _element44_struct_vvv($el, $cl) {
	return array(
		'id'        => _num($cl['id']),
		'title'     => $cl['title'],
		'dialog_id' => _num($cl['dialog_id']),
		'txt_1'     => $cl['txt_1'],      //для [10]
		'txt_2'     => $cl['txt_2'],      //ids из [11]
		'num_1'     => _num($cl['num_1']),
		'num_2'     => _num($cl['num_2']),
		'num_3'     => _num($cl['num_3']),
		'num_4'     => _num($cl['num_4']),
		'num_5'     => _num($cl['num_5']),
		'num_8'     => _num($cl['num_8']) //пробел справа
	);
}
function _element44_print($el, $prm) {
	if(empty($el['vvv']))
		return $el['title'];

	$send = '';
	foreach($el['vvv'] as $ell) {
		$txt = _element('print', $ell, $prm);
		$txt = _elemFormat($ell, $prm, $txt);
		$txt = _spisokColSearchBg($el, $txt);
		$send .= $txt;
		if($ell['num_8'])
			$send .= ' ';
	}

	return $send;
}
function _element44_print11($el, $u) {
	$prm = _blockParam();
	$prm['unit_get'] = $u;

	return  _element44_print($el, $prm);
}




/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА для [44] ===--- */
function PHP12_44_setup($prm) {
	/*
		все действия через JS

		num_8 - пробел справа от значения
		txt_2 - ID элементов-значений, составляющих сборный текст
	*/

	if(!$prm['unit_edit'])
		return _emptyMin('Настройка сборного текста будет доступна<br>после вставки элемента в блок.');

	return '';
}
function PHP12_44_setup_save($cmp, $val, $unit) {//сохранение содержания Сборного текста
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений сборного текста
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает сборный текст
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	$ids = array();
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['id']))
				continue;
			$ids[] = $id;
			$spc = _num($r['spc']);
			$update[] = array(
				'id' => $id,
				'spc' => $spc
			);
		}
	}

	$ids = implode(',', $ids);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (0".($ids ? ',' : '').$ids.")";
	query($sql);

	if(empty($update))
		return;

	foreach($update as $sort => $r) {
		$sql = "UPDATE `_element`
				SET `parent_id`=".$parent_id.",
					`num_8`=".$r['spc'].",
					`sort`=".$sort."
				WHERE `id`=".$r['id'];
		query($sql);
	}

	_BE('elem_clear');
}
function PHP12_44_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}
