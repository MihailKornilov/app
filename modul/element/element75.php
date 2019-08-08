<?php

/* [75] Фильтр: фронтальное меню */
function _element75_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[13] список, который фильтруется
		'txt_1'   => $el['txt_1'],      //[13] путь к иконкам
		'txt_2'   => $el['txt_2']       //[13] путь к названиям
	) + _elementStruct($el);
}
function _element75_print($el, $prm) {
	if(!$ids = _ids($el['txt_2'], 1))
		return _emptyMinRed('[75] отсутствует путь к названиям.');

	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemOne($elem_id))
		return _emptyMinRed('[75] отсутствует элемент-название.');
	if(!$BL = $EL['block'])
		return _emptyMinRed('[75] блок не найден.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMinRed('[75] блок не из диалога.');
	if(!$dialog_id = $BL['obj_id'])
		return _emptyMinRed('Фильтр-меню: нет ID диалога.');
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return _emptyMinRed('[75] диалога не существует.');

	if(!$col = $EL['col'])
		return _emptyMinRed('[75] отсутствует колонка элемента-названия.');

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`,`id`";
	if(!$arr = query_arr($sql))
		return _emptyMin('[75] пустое меню.');

	//вставка картинок
	$arr = _spisokImage($arr);

	$spisok = array();
	foreach($arr as $r)
		$spisok[$r['parent_id']][] = $r;

	$CC = 3;    //количество колонок
	$CCcol = 1; //счётчик колонок
	$count = count($spisok[0]);//общее количество записей
	$CCcount = ceil($count / $CC);//максимальное количество записей в одной колонке
	$n = 0;

	$send = '<table class="w100p"><tr>';
	foreach($spisok[0] as $r) {
		if(!$n)
			$send .= '<td class="top'.($CCcol != $CC ? ' pr20' : '').'">';

		$clk = !empty($spisok[$r['id']]) ? ' onclick="$(this).next().slideToggle(250)"' : '';

		$send .=
		'<table class="w100p mb20">'.
			'<tr><td class="w50 top">'._imageHtml($r['txt_2'], 40, 40, false, false).
				'<td class="top pt3"><a class="fs16 b"'.$clk.'>'.$r[$col].'</a>'.
					_element75child($spisok, $r['id'], $col).
		'</table>';


		$n++;

		if($n == $CCcount) {
			$n = 0;
			$CCcol++;
		}
	}
	$send .= '</table>';

	return $send;
}
function _element75child($spisok, $parent_id, $col) {
	if(empty($spisok[$parent_id]))
		return '';

	$send = '';
	foreach($spisok[$parent_id] as $i => $r)
		$send .=
			'<div class="'.($i ? 'mt5' : 'mt10').'">'.
				'<a class="fs14">'.$r[$col].'</a>'.
			'</div>';

	return '<div class="pb20 dn">'.$send.'</div>';
}



