<?php

/* [18] Dropdown */
function _element18_struct($el) {
	$send = array(
		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'txt_2'   => $el['txt_2'],      /* содержание списка в формате JSON
                                            id
	                                        title
                                            def
                                        */
		'num_1'   => _num($el['num_1']),//скрывать нулевое значение в меню выбора
		'num_2'   => _num($el['num_2']),//не изменять имя нулевого значения после выбора
		'num_3'   => _num($el['num_3']),/* значения:
											1 - произвольные (настраиваются через PHP12_radio_setup)
											2 - список
										*/
		'num_4'   => _num($el['num_4']) //список, если выбрано num_3=2
	) + _elementStruct($el);

	if($send['num_3'] == 2 && $send['num_4'])
		$send['issp'] = $send['num_4'];

	return $send;
}
function _element18_vvv($el) {
	//значения из списка
	if($el['num_3'] == 2) {
		if(!$DLG = _dialogQuery($el['num_4']))
			return array();

		$sql = "SELECT "._queryCol($DLG)."
				FROM   "._queryFrom($DLG)."
				WHERE  "._queryWhere($DLG)."
				ORDER BY `sort`
				LIMIT 30";
		if(!$arr = query_arr($sql))
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

	if(!$send = _decode($el['txt_2']))
		return array();

	return _arrNum($send);
}
function _element18_v_get($el, $id) {
	foreach(_element18_vvv($el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element18_print($el, $prm) {
	$def = 0;
	foreach(_element('vvv', $el) as $r)
		if(!empty($r['def'])) {
			$def = $r['id'];
			break;
		}
	return
	_dropdown(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'value' => _elemPrintV($el, $prm, $def)
	));
}
function _element18_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';

	foreach(_element('vvv', $el) as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}

