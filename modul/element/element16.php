<?php

/* [16] Radio */
function _element16_struct($el) {
	$send = array(
		'txt_1'   => $el['txt_1'],  //текст нулевого значения
		'txt_2'   => $el['txt_2'],  /* содержание списка в формате JSON
                                        id
                                        title
                                        def
                                    */
		'num_1'   => _num($el['num_1']),//горизонтальное положение
		'num_2'   => _num($el['num_2']),/* значения:
											3876 - произвольные (настраиваются через PHP12_radio_setup)
											3877 - существующий элемент
											3878 - список
										*/
		'num_3'   => _num($el['num_3']),//элемент, если выбрано num_2=3877
		'num_4'   => _num($el['num_4']),//список, если выбрано num_2=3878
		'num_5'   => _num($el['num_5']) //кружки справа
	) + _elementStruct($el);

	if($send['num_2'] == 3878 && $send['num_4'])
		$send['issp'] = $send['num_4'];

	return $send;
}
function _element16_print($el, $prm) {
	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'light' => 1,
		'right' => $el['num_5'],
		'block' => !$el['num_1'],
		'interval' => 5,
		'value' => _element16_v_get($el, $prm),
		'title0' => $el['txt_1'],
		'spisok' => _element16_vvv($el),
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
	//значения из списка
	if($el['num_2'] == 3878) {
		if(!$DLG = _dialogQuery($el['num_4']))
			return array();

		$sql = "SELECT "._queryCol($DLG)."
				FROM   "._queryFrom($DLG)."
				WHERE  "._queryWhere($DLG)."
				ORDER BY `sort`
				LIMIT 30";
		if(!$arr = DB1::arr($sql))
			return array();

		$send = array();
		foreach($arr as $id => $r) {
			$title = '- значение не настроено -';
			if($col = _elemCol($DLG['spisok_elem_id']))
				if(isset($r[$col]))
					$title = $r[$col];
			$send[] = array(
				'id' => $id,
				'title' => $title
			);
		}

		return $send;
	}

	//значения из существующего (другого) элемента
	if($el['num_2'] == 3877)
		if(!$el = _elemOne($el['num_3']))
			return array();

	if(!$send = _decode($el['txt_2']))
		return array();

	return _arrNum($send);
}
function _element16_v_get($el, $prm, $v=0) {
	if($v = _elemPrintV($el, $prm, $v))
		return $v;

	foreach(_element16_vvv($el) as $r)
		if(!empty($r['def']))
			return $r['id'];

	return $v;
}
function _element16_title_get($el, $id) {
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
function _element16_template_docx($el, $u) {
	return _element16_print11($el, $u);
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
	DB1::query($sql);

	_elemOne($unit['id'], true);
}
function PHP12_radio_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();
	if(!$send = _decode($el['txt_2']))
		return array();

	return _arrNum($send);
}


