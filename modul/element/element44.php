<?php

/* [44] Сборный текст */
function _element44_struct($el) {
	/*
		настройка значений через PHP12_elem34_setup
	*/
	return array(
		'txt_1'   => $el['txt_1']//содержание (из настройки значений)
	) +_elementStruct($el);
}
function _element44_struct_vvv($el, $cl) {
	return array(
		'id'        => _num($cl['id']),
		'title'     => $cl['title'],
		'dialog_id' => _num($cl['dialog_id']),
		'font'      => $cl['font'],
		'color'     => $cl['color'],
		'txt_1'     => $cl['txt_1'],      //для [10]
		'txt_2'     => $cl['txt_2'],      //ids из [11]
		'num_1'     => _num($cl['num_1']),
		'num_2'     => _num($cl['num_2']),
		'num_3'     => _num($cl['num_3']),
		'num_4'     => _num($cl['num_4']),
		'num_5'     => _num($cl['num_5'])
	);
}
function _element44_print($el, $prm) {
	if(empty($el['txt_1']))
		return $el['title'];

	$json = json_decode($el['txt_1'], true);
	$send = '';
	foreach($json as $r)
		switch($r['type']) {
			case 'txt':
				$r['txt'] = _elem44css($r, $r['txt']);
				$send .= _br($r['txt']);
				break;
			case 'el':
				if(!$ell = _elemOne($r['id']))
					break;

				$txt = _element('print', $ell, $prm);
				$txt = _elemFormat($ell, $prm, $txt);
				$txt = _elem44css($ell, $txt, $prm);
				$txt = _spisokColSearchBg($el, $txt);
				$send .= $txt;
			break;
	}

	return $send;
}
function _element44_template_docx($el, $u) {
	$prm = _blockParam();
	$prm['unit_get'] = $u;
	return _element44_print($el, $prm);
}
function _elem44css($ell, $txt, $prm=array()) {//применение стилей к значению
	$cls = array();
	$cls[] = $ell['font'];
	$cls[] = $ell['color'];
	$cls[] = _elemHintOn($ell, $prm);

	if(!$cls = array_diff($cls, array('')))
		return $txt;

	return '<span class="'.implode(' ', $cls).'" style="font-size:inherit"'._elemDivDataHint($ell, $prm).'>'.$txt.'</span>';
}







/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА для [44] ===--- */
function PHP12_elem44_setup($prm) {
	/*
		все действия через JS
	*/
	if(!$prm['unit_edit'])
		return _emptyMin('Настройка сборного текста будет доступна<br>после вставки элемента в блок.');

	return '';
}
function PHP12_elem44_setup_save($cmp, $val, $unit) {//сохранение содержания Сборного текста
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

					$sql = "UPDATE `_element`
							SET `font`='".$r['font']."',
								`color`='".$r['color']."'
							WHERE `id`=".$id;
					query($sql);

					unset($r['font']);
					unset($r['color']);

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

	//применение родительского элемента (для таблиц)
	$sql = "UPDATE `_element`
			SET `parent_id`=".$parent_id."
			WHERE `id` IN (".$ids.")";
	query($sql);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	_BE('elem_clear');
}
function PHP12_elem44_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id'], true))
		return array();
	if(!$json = $el['txt_1'])
		return array();

	$json = json_decode($json, true);

	foreach($json as $n => $r)
		if($r['type'] == 'el')
			if($ell = _elemOne($r['id'])) {
				$json[$n]['dialog_id'] = $ell['dialog_id'];
				$json[$n]['title'] = $ell['title'];
				$json[$n]['font'] = $ell['font'];
				$json[$n]['color'] = $ell['color'];
			}

	return $json;
}




