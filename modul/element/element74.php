<?php

/* [74] Фильтр: Radio */
function _element74_struct($el) {
	/*
		значения: PHP12_filter_radio_setup
	*/
	return array(
		'num_1' => _num($el['num_1']),//id элемента-список, к которому применяется фильтр
		'txt_1' => $el['txt_1']       //[12] содержание фильтра
	) + _elementStruct($el);
}
function _element74_vvv($el) {
	if(!$send = _decode($el['txt_1']))
		return array();

	foreach($send as $i => $r) {
		$send[$i]['c'] = $r['cond'] ? count($r['cond']) : '';
		$send[$i]['cond'] = $r['cond'] && is_array($r['cond']) ? json_encode($r['cond']) : '';
	}

	return $send;
}
function _element74_print($el, $prm) {
	if(!$vvv = _element('vvv', $el))
		return _emptyMinRed('Значения фильтра не настроены');

	//получение количества значений по каждому пункту
	$EL = _elemOne($el['num_1']);
	$DLG = _dialogQuery($EL['num_1']);
	$spisok = array();
	$def = 0;
	foreach($vvv as $n => $r) {
		$spisok[$n] = array(
			'id' => $r['id'],
			'title' => $r['title']
		);

		if($r['def'])
			$def = $r['id'];

		if(!$r['eye'])
			continue;


		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
					"._40cond($EL, $r['cond']);
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
		'value' => _filter('vv', $el, $def),
		'spisok' => $spisok,
		'disabled' => $prm['blk_setup']
	));
}
function _elem74filter($el) {//применение фильтра к списку
	//поиск элемента-фильтра-радио
	foreach(_filter('spisok', $el['id']) as $F)
		if($F['elem']['dialog_id'] == 74) {
			if(!$v = _num($F['v']))
				return ' AND !`t1`.`id` /* [74] некорректное значение фильтра */';
			if(!empty($F['elem']['txt_1']))
				if($arr = _decode($F['elem']['txt_1']))
					foreach($arr as $r)
						if($v == $r['id']) {
							if(empty($r['cond']))
								return '';
							$cond = json_encode($r['cond']);
							return _40cond($el, $cond);
						}
			return ' AND !`t1`.`id` /* [74] отсутствует элемент '.$v.' пункта Радио */';
		}

	return '';
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

	if(!$unit['id'])
		return;
	if(!$col = @$cmp['col'])
		return;

	$save = array();

	$def = '';
	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$id = _num($r['id']))
					continue;
				if(!$title = _txt($r['title']))
					continue;

				if(_num($r['def']))
					$def = $id;

				$save[] = array(
					'id' => $id,
					'title' => $title,
					'cond' => _decode($r['cond'], ''),
					'def' =>_num($r['def']),
					'eye' =>_num($r['eye'])
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	query($sql);

	_filter('def_update', $unit['id'], $def);

	_elemOne($unit['id'], true);
}
function PHP12_filter_radio_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();

	return _arrNum(_element('vvv', $el));
}
