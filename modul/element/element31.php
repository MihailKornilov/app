<?php

/* [31] Выбор нескольких значений галочками */
function _element31_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога - список, из которого будут выбираться галочки
		'num_2'   => _num($el['num_2']) //id элемента - содержание
	) + _elementStruct($el);
}
function _element31_print($el, $prm) {
	$v = _elemPrintV($el, $prm);

	//получение данных списка
	$DLG = _dialogQuery($el['num_1']);
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	//вставка картинок
	$spisok = _spisokImage($spisok);

	$chk = '';
	$n = 0;
	$sel = _idsAss($v);
	foreach($spisok as $r) {
		$title = _msgRed('содержание не настроено');

		if($elem_id = $el['num_2']) {
			if($ell = _elemOne($elem_id)) {
				switch($ell['dialog_id']) {
					//сборный текст
					case 44:
						$prm44 = _blockParam();
						$prm44['unit_get'] = $r;
						$title = _element44_print($ell, $prm44);
						break;
					case 60:
						if(!$col = _elemCol($ell))
							break;
						if(!isset($r[$col]))
							break;
						if(!is_array($r[$col]))
							break;
						$title = _imageHtml($r[$col], 0, 0, false, false);
						break;
					default:
						if($col = $ell['col'])
							if(isset($r[$col]))
								$title = $r[$col];
				}
			}
		} elseif($col = _elemCol($DLG['spisok_elem_id']))
				$title = $r[$col];

		$chk .=
			'<div class="'._dn(!$n++, 'mt5').'">'.
				_check(array(
					'attr_id' => 'chk31_'.$r['id'],
					'light' => 1,
					'title' => $title,
					'value' => _num(@$sel[$r['id']])
				)).
			'</div>';
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	$chk;
}
function _element31_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	return _val31($el, $txt);
}
function _element31_history($el, $v) {
	return _val31($el, $v);
}
function _element31_action231($el, $u) {
	if(!$col = @$el['col'])
		return true;
	if(!_idsAss(@$u[$col]))
		return true;

	return false;
}
function _element31_template_docx($el, $u) {
	$col = $el['col'];
	return _val31($el, $u[$col]);
}
function _val31($el, $txt) {//Выбор нескольких значений галочками [31] - вывод значения
	if(!$sel = _idsAss($txt))
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '';

	//вставка картинок
	$spisok = _spisokImage($spisok);

	$send = array();

	foreach($spisok as $r) {
		if(empty($sel[$r['id']]))
			continue;
		if(is_array($r['txt_1'])) {
			$send[] = _imageHtml($r['txt_1'], 0, 0, false, false);
			continue;
		}
		$send[] = $r['txt_1'];
	}

	return implode(', ', $send);
}

