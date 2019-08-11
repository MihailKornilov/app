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
		'value' => _spisokFilter('vv', $el, $el['def']),
		'spisok' => $spisok,
		'disabled' => $prm['blk_setup']
	));
}

