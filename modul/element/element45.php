<?php

/* [45] Выбор нескольких значений привязанного списка */
function _element45_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'num_1'   => _num($el['num_1']),//список (из которого будут выбираться значения)
		'txt_1'   => $el['txt_1'],      //имя кнопки
		'num_2'   => _num($el['num_2']),//вспомогательный диалог
		'txt_2'   => $el['txt_2'],      //элемент-изображение [13]
		'num_3'   => _num($el['num_3']),//указывать количество выбранных значений
		'num_4'   => _num($el['num_4']),//элемент-цена [13]
		'num_5'   => _num($el['num_5']) //элемент-итог [13]
	) + _elementStruct($el);
}
function _element45_js($el) {
	return array(
		'num_2' => _num($el['num_2']),
		'num_4' => _num($el['num_4']),
		'num_5' => _num($el['num_5'])
	) + _elementJs($el);
}
function _element45_print($el, $prm) {
	$v = _elemPrintV($el, $prm);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div>'._element45Uns($el, $v).'</div>'.
	'<table class="w100p">'.
		'<tr><td>'.
				_button(array(
					'attr_id' => _elemAttrId($el, $prm).$el['afics'],
					'name' => $el['txt_1'],
					'color' => 'grey',
					'width' => $el['width'],
					'small' => 1,
					'class' => _dn(!$prm['blk_setup'], 'curD')
				)).
			'<td class="uns-itog r fs14 pr40">'.
	'</table>';
}
function _element45_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return _element45Uns($el, @$u[$col], true);
}
function _element45Uns($el, $v, $is_show=false) {//выбранные значения при редактировании
	if(empty($v))
		return '';

	$UNS = array();
	foreach(explode(',', $v) as $ex) {
		$exx = explode(':', $ex);
		$UNS[] = array(
			'id' => $exx[0],
			'c' => $exx[1]
		);
	}

	if(empty($UNS))
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE `t1`.`id` IN ("._idsGet($UNS).")
			  AND "._queryWhere($DLG, 1);
	if(!$arr = query_arr($sql))
		return '';

	$col = _elemCol($DLG['spisok_elem_id']);
	$cenaCol = _elemCol($el['num_4']);
	$send = '';
	$n = 1;
	foreach($UNS as $r) {
		if(!isset($arr[$r['id']]))
			continue;

		$name = '<span class="fs10 red">Отсутствует колонка для отображения названия</span>';

		$u = $arr[$r['id']];
		if($col)
			if(isset($u[$col]))
				$name = $u[$col];

		$cena = 0;
		if($cenaCol)
			if(isset($u[$cenaCol]))
				$cena = $u[$cenaCol];

		//вариант вывода значений для редактирования
		if(!$is_show) {
			$send .=
			'<tr><td class="w35">'.
					'<div class="fs14 grey r">'.$n++.'</div>'.
				'<td class="fs14">'.
					'<div class="fs14">'.$name.'</div>'.
				'<td class="w70 bg-ffd'._dn($el['num_3']).'">'.
					'<input type="text" class="uinp w100p r b" val="'.$r['id'].'" value="'.$r['c'].'">'.

			($cenaCol ?
				'<td class="w100 r'._dn($el['num_4']).'">'.
					'<b class="ucena">'.$cena.'</b> руб.'
			: '').

				'<td class="pad0 w35 center">'.
					'<div class="icon icon-del'._tooltip('Отменить выбор', -94, 'r').'</div>';
			continue;
		}

		//вариант вывода значений для просмотра
		$send .=
		'<tr><td class="w35 grey r">'.$n++.
			'<td>'.$name;
		if($el['num_3'])
			$send .='<td class="w50 r b">'.$r['c'];
	}

	return
	'<table class="_stab w100p small'._dn($is_show, 'mb5').'">'.$send.'</table>';
}

