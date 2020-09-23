<?php

/* [8] input:text (однострочное текстовое поле) */
function _element8_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],      //текст для placeholder
		'txt_2' => $el['txt_2'],      //текст по умолчанию
		'num_1' => _num($el['num_1']),/* формат:
											32 - произвольный текст
											33 - цифры и числа
											34 - артикул
									  */
		'num_2' => _num($el['num_2']),//количество знаков после запятой (для 33)
		'num_3' => _num($el['num_3']),//разрешать отрицательные значения (для 33)
		'num_4' => _num($el['num_4']),//разрешать вносить 0 (для 33)
		'num_5' => _num($el['num_5']),/* тип начального текста
                                            8063: произвольный текст
                                            8064: указанное значение
									  */
		'num_6' => _num($el['num_6']),//прижимать текст вправо
		'num_7' => _num($el['num_7']),//применять диалоговое окно по нажатию Enter
		'txt_4' => $el['txt_4'],      //начальный текст: указанное значение [13]
		'txt_3' => $el['txt_3']       //шаблон артикула (для 34)
	) + _elementStruct($el);
}
function _element8_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';


	$v = _elemPrintV($el, $prm, $el['txt_2']);
	$v = _element8vFromEl($el, $prm, $v);

	switch($el['num_1']) {
		default:
		//произвольный текст
		case 32: break;
		//цифры и числа
		case 33:
			$v = round($v, $el['num_2']);
			$v = $v || $el['num_4'] ? $v : '';
			break;
		//артикул
		case 34:
			if($v)
				break;
			if(!$col = _elemCol($el))
				break;
			if(!$BL = _blockOne($el['block_id']))
				break;
			if($BL['obj_name'] != 'dialog')
				break;
			if(!$DLG = _dialogQuery($BL['obj_id']))
				break;
			$sql = "SELECT MAX("._queryColReq($DLG, $col).")+1
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND LENGTH("._queryColReq($DLG, $col).")=".strlen($el['txt_3']);
			$v = query_value($sql);
			if(($diff = strlen($el['txt_3']) - strlen($v)) > 0)
				for($n = 0; $n < $diff; $n++)
					$v = '0'.$v;
			break;
	}

	//прижимать текст вправо
	$right = $el['num_6'] ? ' class="r"' : '';

	return '<input type="text" id="'._elemAttrId($el, $prm).'"'.$right._elemStyleWidth($el).$placeholder.$disabled.' value="'.$v.'" />';
}
function _element8_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	$txt = _spisokColSearchBg($el['elp'], $txt);

	if(!empty($u['parent_id']))
		if($DLG = _dialogQuery($u['dialog_id']))
			while($pid = $u['parent_id']) {
				if(!$u = _spisokUnitQuery($DLG, $pid))
					break;
				if(!empty($u[$col]))
					$txt = $u[$col].' &raquo; '.$txt;
			}

	return _br($txt);
}
function _element8vFromEl($el, $prm, $v) {//начальный текст из указанного значения
	if($el['num_5'] != 8064)
		return $v;
	//значение может быть подставлено только при внесении записи
	if($u = $prm['unit_edit'])
		return $v;

	$page_id = _page('cur');
	if(!$page = _page($page_id))
		return '';
	if(!$dlg_id = $page['dialog_id_unit_get'])
		return '';
	if(!$id = _num(@$_GET['id']))
		return '';
	if(!$dialog = _dialogQuery($dlg_id))
		return '';
	if(!$u = _spisokUnitQuery($dialog, $id))
		return '';

	return _elemUids($el['txt_4'], $u);
}
function _element8_template_docx($el, $u) {
	$col = $el['col'];
	return $u[$col];
}

