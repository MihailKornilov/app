<?php

/* [34] Сборный текст */
function _element34_struct($el) {
	/*
		настройка значений через PHP12_elem34_setup
	*/
	return array(
		'txt_1'   => $el['txt_1']//содержание (из настройки значений)
	) +_elementStruct($el);
}
function _element34_struct_vvv($el, $cl) {
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
		'num_5'     => _num($cl['num_5'])
	);
}
function _element34_print($el, $prm) {
	if(empty($el['txt_1']))
		return $el['title'];

	$vvv = array();
	if(!empty($el['vvv']))
		foreach($el['vvv'] as $r)
			$vvv[$r['id']] = $r;

	$json = json_decode($el['txt_1'], true);
	$send = '';
	foreach($json as $r)
		switch($r['type']) {
			case 'txt':
				$send .= _br($r['txt']);
				break;
			case 'el':
				if(!$ell = $vvv[$r['id']])
					break;

				$txt = _element('print', $ell, $prm);
//				$txt = _elemFormat($ell, $prm, $txt);
//				$txt = _spisokColSearchBg($el, $txt);
				$send .= $txt;
			break;
	}

	return $send;
}









/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА для [34] ===--- */
function PHP12_elem34_setup($prm) {
	/*
		все действия через JS
	*/
	return '';
}
function PHP12_elem34_setup_save($cmp, $val, $unit) {//сохранение содержания Сборного текста
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений сборного текста
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает сборный текст
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	//колонка, по которой сохраняется содержание
	if(!$col = $cmp['col'])
		return;

	//содержание в виде JSON, которое будет сохранено
	$json = array();

	//идентификаторы, которые удалять не нужно
	$ids = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(empty($r['type']))
				continue;
			switch($r['type']) {
				case 'txt':
					if(!empty($r['txt']))
						$json[] = $r;
					break;
				case 'el':
					if(!$id = _num($r['id']))
						break;
					$json[] = $r;
					$ids .= ','.$id;
					break;
			}
		}
	}

	$json = empty($json) ? '' : json_encode($json);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($json)."'
			WHERE `id`=".$parent_id;
	query($sql);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	_BE('elem_clear');
}
function PHP12_elem34_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();
	if(!$json = $el['txt_1'])
		return array();

	$json = json_decode($json, true);

	$vvv = array();
	if(!empty($el['vvv']))
		foreach($el['vvv'] as $r)
			$vvv[$r['id']] = $r;

	foreach($json as $n => $r)
		if($r['type'] == 'el') {
			$ell = $vvv[$r['id']];
			$json[$n]['dialog_id'] = $ell['dialog_id'];
			$json[$n]['title'] = $ell['title'];
		}

	return $json;
}









