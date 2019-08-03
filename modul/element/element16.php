<?php

/* [16] Radio: произвольные значения */
function _element16_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_1'   => _num($el['num_1']),//горизонтальное положение
		'num_2'   => _num($el['num_2']),/* значения:
											3876 - произвольные значения (настраиваются через PHP12_radio_setup)
											3877 - значения существующего элемента
										*/
		'num_3'   => _num($el['num_3']) //элемент, если выбрано num_2:3877
	) + _elementStruct($el);
}
function _element16_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1'],
		'def' => _num($cl['def']),
		'use' => 0
	);
}
function _element16_print($el, $prm) {
	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'light' => 1,
		'block' => !$el['num_1'],
		'interval' => 5,
		'value' => _elemPrintV($el, $prm, $el['def']),
		'title0' => $el['txt_1'],
		'spisok' => _element('vvv', $el),
		'disabled' => $prm['blk_setup']
	));
}
function _element16_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';
	if(empty($el['vvv']))
		return '';

	foreach($el['vvv'] as $vv)
		if($vv['id'] == $id)
			return $vv['title'];

	return '';
}
function _element16_vvv($el) {
	//значения из существующего (другого) элемента
	if($el['num_2'] == 3877) {
		if($elem_id = $el['num_3']) {
			$sql = "SELECT
			            `id`,
			            `txt_1` `title`
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			return query_arr($sql);
		}
		return array();
	}

	if(!empty($el['vvv']))
		return $el['vvv'];

	return array();

}
function _element16_history($el, $v) {
	foreach($el['vvv'] as $vv)
		if($vv['id'] == $v)
			return $vv['title'];

	return '';
}


