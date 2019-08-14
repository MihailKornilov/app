<?php

/* [75] Фильтр: фронтальное меню */
function _element75_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[13] список, который фильтруется
		'num_2'   => _num($el['num_2']),//[35] Кол-во колонок
		'txt_2'   => $el['txt_2'],      //[13] путь к названиям
		'txt_1'   => $el['txt_1'],      //[13] путь к иконкам
		'num_3'   => _num($el['num_3']),//[35] Размер иконок
	) + _elementStruct($el);
}
function _element75_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element75_print($el, $prm) {
	if(!$elem_id = _idsLast($el['txt_2']))
		return _emptyMinRed('[75] отсутствует путь к названиям.');
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

	$v = _spisokFilter('vv', $el, 0);

	//вставка картинок
	$arr = _spisokImage($arr);

	$spisok = array();
	foreach($arr as $r)
		$spisok[$r['parent_id']][] = $r;

	if(!$CC = $el['num_2'])
		return _emptyMinRed('[75] не указано количетсво колонок.');

	$CCcol = 1; //счётчик колонок
	$count = count($spisok[0]);//общее количество записей
	$CCcount = ceil($count / $CC);//максимальное количество записей в одной колонке
	$n = 0;

	$send = '<table class="tab75 w100p'._dn(!$v).'"><tr>';
	foreach($spisok[0] as $r) {
		if(!$n)
			$send .= '<td class="top'.($CCcol != $CC ? ' pr20' : '').'">';

		$n++;

		$clk = !empty($spisok[$r['id']]) ? ' onclick="$(this).next().slideToggle(250)"' : '';

		$send .=
		'<table class="w100p'._dn($n == $CCcount, 'mb20').'">'.
			'<tr><td class="w50 top">'._imageHtml($r['txt_2'], $el['num_3'], $el['num_3'], false, false).
				'<td class="top pt3"><a class="fs16 b"'.$clk.'>'.$r[$col].'</a>'.
					_element75child($spisok, $r['id'], $col).
		'</table>';

		if($n == $CCcount) {
			$n = 0;
			$CCcol++;
		}
	}
	$send .= '</table>';

	return
	_element75mp($v, $arr, $col, $DLG).
	$send;
}
function _element75child($spisok, $parent_id, $col) {
	if(empty($spisok[$parent_id]))
		return '';

	$send = '';
	foreach($spisok[$parent_id] as $i => $r)
		$send .=
			'<div class="'.($i ? 'mt5' : 'mt10').'">'.
				'<a class="u75 fs14" val="'.$r['id'].'">'.$r[$col].'</a>'.
			'</div>';

	return '<div class="pb20 dn">'.$send.'</div>';
}
function _element75mp($v, $arr, $col, $DLG) {//путь меню (Menu Path)
	$pname = '';
	if($v) {
		$pname = $arr[$v][$col];
		$pid = $arr[$v]['parent_id'];
		while($pid) {
			$sql = "SELECT "._queryCol($DLG)."
					FROM   "._queryFrom($DLG)."
					WHERE  "._queryWhere($DLG)."
					  AND `id`=".$pid;
			if($r = query_assoc($sql)) {
				$pname = $r[$col].' » '.$pname;
				$pid = $r['parent_id'];
			} else
				$pid = 0;
		}
	}


	return
	'<div class="mp75'._dn($v).'">'.
		'<div class="icon icon-del fr'._tooltip('Отменить выбор', -52).'</div>'.
		'<div class="pname75 fs17 b">'.$pname.'</div>'.
	'</div>';
}





