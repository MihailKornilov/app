<?php

/* [95] Быстрое формирование списка */

/* Структура элемента */
function _element95_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[24] список
		'num_2'   => _num($el['num_2']),//[1] показывать имена колонок
		'txt_1'   => $el['txt_1'],      //[8] текст кнопки добавления
		'txt_2'   => $el['txt_2']       //[12] данные колонок в формате JSON
	) + _elementStruct($el);
}
function _element95_js($el) {
	return array(
		'txt_1'   => $el['txt_1'],
		'num_2'   => _num($el['num_2'])
	) + _elementJs($el);
}

/* Вывод содержимого элемента на экран */
function _element95_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return _emptyMin(_debugPrint('[95] ').$el['name']);

	$cols = json_decode($el['txt_2'], true);
	if(empty($cols))
		return _emptyMinRed(_debugPrint('[95] ').'Не настроены колонки');

	return '';
}

function _element95_vvv($el) {
	if(empty($el['txt_2']))
		return array();

	$cols = json_decode($el['txt_2'], true);
	foreach($cols as $i => $r) {
		if($r['type'] != 3)
			continue;
		$cols[$i]['spisok'] = _elem95_spisok($r['v']);
	}

	return array(
		'cols' => $cols
	);
}
function _elem95_spisok($elem_ids, $v='') {//получение данных для Select (type=3)
	$elem_id = _idsLast($elem_ids);
	if(!$dlg_id = _elemDlgId($elem_id))
		return array();
	if(!$DLG = _dialogQuery($dlg_id))
		return array();
	if(!$col = _elemCol($elem_id))
		return array();

	$sql = "SELECT `id`,".$col." `title`
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
	".($v ? " AND `".$col."` LIKE '%".addslashes($v)."%'" : '')."
			ORDER BY `id` DESC
			LIMIT 50";
	return query_ass($sql);
}






function PHP12_elem95_setup($prm) {//настройка колонок списка
	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка колонок будет доступна после вставки элемента в блок.');

	if(!$BL = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('[95] Отсутствует исходный блок.');

	$ex = explode(' ', $BL['elem']['mar']);
	$w = $BL['width'] - $ex[1] - $ex[3] - 60;

	return '<div class="calc-div h25 line-b bg-efe">'.$w.'</div>';
}
function PHP12_elem95_setup_save($cmp, $val, $unit) {//сохранение данных колонок
	/*
		сохранение в формате JSON в txt_2:
			w - ширина
			name - имя заголовка
			type - тип колонки
			col - имя колонки в таблице
			v - значение
	*/

	if(empty($unit['id']))
		return;
	if(!$col = $cmp['col'])
		return;

	$save = array();
	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$type = _num($r['type']))
					continue;

				if(!isset($r['v']))
					$r['v'] = '';
				if(!isset($r['col']))
					$r['col'] = '';

				$save[] = array(
					'w' => _num($r['w']),
					'name' => $r['name'],
					'type' => $type,
					'col' => $r['col'],
					'v' => $r['v']
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	query($sql);

	_BE('elem_clear');
}
function PHP12_elem95_setup_vvv($prm) {
	if(!$u = @$prm['unit_edit'])
		return array();

	$VAL = json_decode($u['txt_2'], true);

	foreach($VAL as $i => $r) {
		$VAL[$i]['title'] = '';
		if($r['type'] == 3)
			$VAL[$i]['title'] = _elemIdsTitle($r['v']);
	}

	return $VAL;
}











