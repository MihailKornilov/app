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
											35 - номер телефона в формате +7 (***) ***-**-**
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
		'num_8' => _num($el['num_8']),//только для чтения
		'txt_4' => $el['txt_4'],      //начальный текст: указанное значение [13]
		'txt_3' => $el['txt_3']       //шаблон артикула (для 34)
	) + _elementStruct($el);
}
function _element8_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$readonly = $el['num_8'] ? ' readonly' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$cls = array();
	$cls[] = $el['num_6'] ? 'r' : '';//прижимать текст вправо
	$cls[] = $readonly ? 'readonly' : '';
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$v = _elemPrintV($el, $prm, $el['txt_2']);
	$v = _element8vFromEl($el, $prm, $v);

	switch($el['num_1']) {
		default:
		//произвольный текст
		case 32: break;
		//цифры и числа
		case 33:
			$v = round($v, $el['num_2']);
			if(empty($prm['unit_edit']) && $el['txt_2'] !== '0')
				$v = _hide0($v);
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

			$qCol = _queryColReq($DLG, $col);
			$sql = "SELECT MAX(".$qCol.")
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND LENGTH(".$qCol.")=".mb_strlen($el['txt_3']);
			if(!$v = DB1::value($sql))
				$v = $el['txt_3'];

			//полученный максимальный артикул из базы
			$art = preg_split("//u", $v , -1, PREG_SPLIT_NO_EMPTY);

			//выделение числовых символов из артикула и увеличение на единицу
			$txt = '';
			$sum = '';
			foreach($art as $r)
				if(!preg_match(REGEXP_NUMERIC, $r))
					$txt .= $r;
				else
					$sum .= $r;

			$sum *= 1;
			$sum++;

			$sum = preg_split("//u", $sum , -1, PREG_SPLIT_NO_EMPTY);

			//добавление нулей спереди к числовому значению
			$add0 = count($art) - mb_strlen($txt) - count($sum);
			if($add0 > 0)
				for($n = 0; $n < $add0; $n++)
					array_unshift($sum, 0);

			$v = $txt.implode('', $sum);
			break;
	}

	return
	//подключение маски ввода номера телефона
	($el['num_1'] == 35 ? '<script src="js/jquery.maskedinput.min.js?1"></script>' : '').
	'<input type="text" id="'._elemAttrId($el, $prm).'"'.$cls._elemStyleWidth($el).$placeholder.$readonly.$disabled.' value="'.$v.'" />';
}
function _element8_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	$txt = _spisokColSearchBg($el['elp'], $txt);

	//печать родительских значений, если есть
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

