<?php

/* [74] Фильтр: Radio */
function _element74_struct($el) {
	/*
		значения: PHP12_filter_radio_setup
	*/
	return array(
		'def'   => _num($el['def']),

		'num_1' => _num($el['num_1'])//id элемента-список, к которому применяется фильтр
	) + _elementStruct($el);
}
function _element74_struct_vvv($el, $cl) {
	$c = '';
	if($cl['txt_2']) {
		$vv = htmlspecialchars_decode($cl['txt_2']);
		$arr = json_decode($vv, true);
		$c = count($arr);
	}

	return array(
		'id'    => _num($cl['id']),
		'txt_1' => $cl['txt_1'],        //имя пунтка
		'def'   => _num($cl['def']),
		'c'     => $c,                  //количество условий в пункте
		'txt_2' => $cl['txt_2'],        //условия
		'num_1' => _num($cl['num_1'])   //отображать количество значений в пункте
	);
}
function _element74_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element74_print($el, $prm) {
	if(empty($el['vvv']))
		return _emptyMinRed('Значения фильтра не настроены');

	//получение количества значений по каждому пункту
	$EL = _elemOne($el['num_1']);
	$DLG = _dialogQuery($EL['num_1']);
	$spisok = array();
	foreach($el['vvv'] as $n => $r) {
		$spisok[$n] = array(
			'id' => $r['id'],
			'title' => $r['txt_1']
		);

		if(!$r['num_1'])
			continue;

		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
					"._40cond($EL, $r['txt_2']);
		if($c = query_value($sql))
			$spisok[$n]['title'] .= '<span class="fr inhr">'.$c.'</span>';
	}

	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'block' => 1,
		'width' => '100%',
		'interval' => 6,
		'light' => 1,
		'value' => _filter('vv', $el, $el['def']),
		'spisok' => $spisok,
		'disabled' => $prm['blk_setup']
	));
}

/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ФИЛЬТРА ===--- */
function PHP12_filter_radio_setup($prm) {
	if(!$prm['unit_edit'])
		return _emptyMin('Настройка значений фильтра будет доступна<br>после вставки элемента в блок.');
	return '';
}
function PHP12_filter_radio_setup_save($cmp, $val, $unit) {//сохранение значений фильтра radio
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений фильтра radio
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает сборный текст
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$parent_id;
	$app_id = query_value($sql);

	$ids = '0';
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if($id = _num($r['id']))
				$ids .= ','.$id;
			if(!$txt_1 = _txt($r['txt_1']))
				continue;
			$update[] = "(
				".$id.",
				".$app_id.",
				".$parent_id.",
				'".addslashes($txt_1)."',
				'"._txt($r['txt_2'])."',
				"._num($r['num_1']).",
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$parent_id;
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
				`num_1`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`num_1`=VALUES(`num_1`),
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

	_elemOne($unit['id'], true);
}
function PHP12_filter_radio_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$c = '';
		if($r['txt_2']) {
			$vv = htmlspecialchars_decode($r['txt_2']);
			$arr = json_decode($vv, true);
			$c = count($arr);
		}

		$send[] = array(
			'id' => _num($r['id']),
			'txt_1' => $r['txt_1'],
			'def' => _num($r['def']),
			'c' => $c,
			'txt_2' => $r['txt_2'],
			'num_1' => _num($r['num_1'])
		);
	}

	return $send;
}
