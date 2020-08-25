<?php

/* [75] Фильтр: фронтальное меню */
function _element75_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[13] список, который фильтруется
		'num_2'   => _num($el['num_2']),//[35] Кол-во колонок
		'txt_2'   => $el['txt_2'],      //[13] путь к названиям
		'txt_3'   => $el['txt_3'],      //[13] путь к количествам
		'txt_1'   => $el['txt_1'],      //[13] путь к иконкам
		'num_3'   => _num($el['num_3']) //[35] Размер иконок
	) + _elementStruct($el);
}
function _element75_print($el) {
	if(!$elem_id = _idsLast($el['txt_2']))
		return _emptyMinRed('[75] отсутствует путь к названиям.');
	if(!$EL = _elemOne($elem_id))
		return _emptyMinRed('[75] отсутствует элемент-название.');
	if(!$BL = _blockOne($EL['block_id']))
		return _emptyMinRed('[75] блок не найден.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMinRed('[75] блок не из диалога.');
	if(!$dialog_id = $BL['obj_id'])
		return _emptyMinRed('Фильтр-меню: нет ID диалога.');
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return _emptyMinRed('[75] диалога не существует.');

	if(!$col = _elemCol($EL))
		return _emptyMinRed('[75] отсутствует колонка элемента-названия.');

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`,`id`";
	if(!$arr = query_arr($sql))
		return _emptyMin('[75] пустое меню.');

	$v = _filter('vv', $el, 0);

	//вставка картинок
	$arr = _spisokImage($arr);

	//получение имени колонки для количеств
	$colCount = '';
	if($id = _idsLast($el['txt_3']))
		$colCount = _elemCol($id);

	$spisok = array();
	foreach($arr as $r) {
		$r['count'] = $colCount && isset($r[$colCount]) ? '<span class="inhr"> ('.$r[$colCount].')</span>' : '';
		$spisok[$r['parent_id']][] = $r;
	}

	if(!$CC = $el['num_2'])
		return _emptyMinRed('[75] не указано количество колонок.');

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

		$u75 = '';
		if(!$child = _elem75child($spisok, $r['id'], $col))
			$u75 = 'u75 ';

		$send .=
		'<table class="w100p'._dn($n == $CCcount, 'mb20').'">'.
			'<tr><td class="w50 top">'._imageHtml($r['txt_2'], $el['num_3'], $el['num_3'], false, false).
				'<td class="top pt3">'.
					'<a class="'.$u75.'fs16 b"'.$clk.' val="'.$r['id'].'">'.$r[$col].$r['count'].'</a>'.
					$child.
		'</table>';

		if($n == $CCcount) {
			$n = 0;
			$CCcol++;
		}
	}
	$send .= '</table>';

	return
	_elem75mp($v, $arr, $col, $DLG).
	$send;
}
function _element75_title() {
	return 'Фронтальное меню';
}
function _elem75child($spisok, $parent_id, $col, $level=0) {
	if(empty($spisok[$parent_id]))
		return '';

	$send = '';
	foreach($spisok[$parent_id] as $i => $r) {
		$fs = 'fs'.(14-$level);
		$clk = !empty($spisok[$r['id']]) ? ' onclick="$(this).parent().next().slideToggle(250)"' : '';

		$u75 = '';
		if(!$child = _elem75child($spisok, $r['id'], $col, $level+2))
			$u75 = 'u75 ';

		$send .=
			'<div class="'.($i ? 'mt5' : 'mt10').'">'.
				'<a class="'.$u75.$fs.'" val="'.$r['id'].'"'.$clk.'>'.$r[$col].$r['count'].'</a>'.
			'</div>'.
			$child;
	}

	$ml = $level ? ' ml'.$level/2*10 : '';

	return '<div class="pb20 dn'.$ml.'">'.$send.'</div>';
}
function _elem75mp($v, $arr, $col, $DLG) {//путь меню (Menu Path)
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
		'<div class="icon icon-del fr tool" data-tool="Отменить выбор"></div>'.
		'<div class="pname75 fs17 b">'.$pname.'</div>'.
	'</div>';
}
function _elem75filter($el) {//Фильтр: фронтальное меню
	$filter = false;
	$v = '';

	//поиск элемента-фильтра-меню
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 75) {
			$filter = $r['elem'];
			$v = _num($r['v']);
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return '';

	//элемент, указывающий на подключенный список
	if(!$elem_id = _ids($filter['txt_1'], 'first'))
		return " AND !`id`";
	if(!$EL = _elemOne($elem_id))
		return " AND !`id`";

	//колонка, по которой будет производиться фильтрование
	if(!$col = $EL['col'])
		return " AND !`id`";

	//получение диалога подключенного списка
	if($EL['dialog_id'] != 29)
		return " AND !`id`";
	if(!$dialog_id = _num($EL['num_1']))
		return " AND !`id`";
	if(!$dialog = _dialogQuery($dialog_id))
		return " AND !`id`";

	if(isset($dialog['field1']['parent_id'])) {
		$sql = "SELECT `id`
				FROM `"._table($dialog['table_1'])."`
				WHERE `parent_id`=".$v;
		if($ids = query_ids($sql))
			$v .= ','.$ids;
	}

	return " AND `".$col."` IN (".$v.")";
}





