<?php

/* [16] Radio: произвольные значения */
function _element16_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'txt_2'   => $el['txt_2'],  /* содержание списка в формате JSON
                                        id
                                        title
                                        def
                                    */
		'num_1'   => _num($el['num_1']),//горизонтальное положение
		'num_2'   => _num($el['num_2']),/* значения:
											3876 - произвольные значения (настраиваются через PHP12_radio_setup)
											3877 - значения существующего элемента
										*/
		'num_3'   => _num($el['num_3']) //элемент, если выбрано num_2:3877
	) + _elementStruct($el);
}
function _element16_print($el, $prm) {
	$vvv = _element('vvv', $el);
	$def = 0;
	foreach($vvv as $r)
		if($r['def']) {
			$def = $r['id'];
			break;
		}
	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'light' => 1,
		'block' => !$el['num_1'],
		'interval' => 5,
		'value' => _elemPrintV($el, $prm, $def),
		'title0' => $el['txt_1'],
		'spisok' => $vvv,
		'disabled' => $prm['blk_setup']
	));
}
function _element16_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';

	foreach(_element('vvv', $el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element16_vvv($el) {
	//значения из существующего (другого) элемента
	if($el['num_2'] == 3877)
		if(!$el = _elemOne($el['num_3']))
			return array();

	if(!$el['txt_2'])
		return array();
	if(!$send = _decode($el['txt_2']))
		return array();

	return _arrNum($send);
}
function _element16_v_get($el, $id) {
	foreach(_element16_vvv($el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element16_history($el, $v) {
	foreach(_element('vvv', $el) as $r)
		if($r['id'] == $v)
			return $r['title'];

	return '';
}

/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ для [16][17][18] ===--- */
function PHP12_radio_setup() {
	return '';
}
function PHP12_radio_setup_save($cmp, $val, $unit) {//сохранение значений radio
	/*
		$cmp  - компонент из диалога, отвечающий за настройку значений radio
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается radio
	*/

	if(empty($unit['id']))
		return;
	if(!$col = $cmp['col'])
		return;

	$save = array();

	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$id = _num($r['id']))
					continue;
				if(!$title = _txt($r['title']))
					continue;
				$save[] = array(
					'id' => $id,
					'title' => $title,
					'content' => _txt($r['content']),
					'def' =>_num($r['def'])
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	query($sql);

	_elemOne($unit['id'], true);
}
function PHP12_radio_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();

	return _decode($el['txt_2']);
}


