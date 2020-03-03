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

/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ для [16][17][18] ===--- */
function PHP12_radio_setup() {
	return '';
}
function PHP12_radio_setup_save($cmp, $val, $unit) {//сохранение значений radio
	/*
		$cmp  - компонент из диалога, отвечающий за настройку значений radio
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается radio

		Данные колонок таблицы записываются в _element
		parent_id = $unit['id'] (ID элемента-radio [16])
	*/

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$unit['id'];
	$app_id = query_value($sql);

	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$content = _txt($r['content']);
			$update[] = "(
				".$id.",
				".$app_id.",
				".$unit['id'].",
				'".addslashes($title)."',
				'".addslashes($content)."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update)) {
		_elemOne($unit['id'], true);
		return;
	}

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);

	_elemOne($unit['id'], true);
}
function PHP12_radio_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}


