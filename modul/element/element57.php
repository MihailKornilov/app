<?php

/* [57] Меню переключения блоков */
function _element57_struct($el) {
	/*
		для настройки блоков используется функция PHP12_menu_block_setup
	*/
	return array(
		'def'     => _num($el['def']),

		'num_1'   => _num($el['num_1']),/* внешний вид меню:
											1158 - Маленькие синие кнопки
											1159 - С нижним подчёркиванием
										*/
		'txt_1'   => $el['txt_1']       //ids дочерних элементов
	) + _elementStruct($el);
}
function _element57_struct_vvv($el, $cl) {//пункты меню
	return array(
		'id' => _num($cl['id']),
		'title'   => $cl['txt_1'], //название пункта меню
		'blk'   => $cl['txt_2'],   //блоки
		'def'   => _num($cl['def'])//по умолчанию
	);
}
function _element57_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element57_print($el, $prm) {
	$EL_COO = '57_'.$el['id'];
	if($def = _num(@$_COOKIE[$EL_COO]))
		$el['def'] = $def;
	$v = _elemPrintV($el, $prm, $el['def']);

	$type = array(
		1158 => 2,
		1159 => 3
	);

	$razdel = '';
	if(!empty($el['vvv']))
		foreach($el['vvv'] as $r) {
			$sel = _dn($v != $r['id'], 'sel');
			$curd = _dn(!$prm['blk_setup'], 'curD');
			$razdel .= '<a class="link'.$sel.$curd.'">'.$r['title'].'</a>';
		}

	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
		   '<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';
}
function PHP12_menu_block_setup() {//используется в диалоге [57]
	return '';
}
function PHP12_menu_block_setup_save($cmp, $val, $unit) {//сохранение данных о пунктах меню
	if(!$parent_id = _num($unit['id']))
		return;

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$parent_id;
	$app_id = query_value($sql);

	$ids = array();
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $sort => $r) {
			if($id = _num($r['id']))
				$ids[] = $id;
			if(!$title = _txt($r['title']))
				continue;
			$blk = _ids($r['blk']);
			$update[] = "(
				".$id.",
				".$app_id.",
				".$parent_id.",
				'".addslashes($title)."',
				'".($blk ? $blk : '')."',
				"._num($r['def']).",
				".$sort."
			)";
		}
	}

	$ids = implode(',', $ids);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (0".($ids ? ',' : '').$ids.")";
	query($sql);

	//ID элементов-значений, составляющих сборный текст
	$sql = "UPDATE `_element`
			SET `txt_2`='".$ids."'
			WHERE `id`=".$parent_id;
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update)) {
		_BE('elem_clear');
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
			WHERE `parent_id`=".$parent_id."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$parent_id;
	query($sql);

	_BE('elem_clear');
}
function PHP12_menu_block_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}
