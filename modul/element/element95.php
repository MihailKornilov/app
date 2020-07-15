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

/* Вывод содержимого элемента на экран */
function _element95_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return _emptyMin('[95] Быстрое формирование списка');

	$cols = json_decode($el['txt_2'], true);
	if(empty($cols))
		return _emptyMinRed('[95] Не настроены колонки');

	return '';
}

function _element95_vvv($el) {
	if(empty($el['txt_2']))
		return array();

	$cols = json_decode($el['txt_2'], true);
	foreach($cols as $i => $r) {
		if($r['type'] != 3)
			continue;

		$cols[$i]['spisok'] = array();

		if(!$dlg_id = _elemDlgId($r['v']))
			continue;
		if(!$DLG = _dialogQuery($dlg_id))
			continue;
		if(!$col = _elemCol($r['v']))
			continue;

		$sql = "SELECT `id`,".$col." `title`
				FROM   "._queryFrom($DLG)."
				WHERE  "._queryWhere($DLG)."
				ORDER BY `id` DESC
				LIMIT 50";
		$cols[$i]['spisok'] = query_ass($sql);
	}

	return array(
		'cols' => $cols
	);
}






function PHP12_elem95_setup($prm) {//настройка колонок списка
	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка колонок будет доступна после вставки элемента в блок.');
	return '';
}
function PHP12_elem95_setup_save($cmp, $val, $unit) {//сохранение данных колонок
	/*
		сохранение в формате JSON в txt_2:
			type - тип колонки
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

				$v = $r['v'];

				if($type == 3 && !$v = _num($v))
					continue;

				$save[] = array(
					'type' => $type,
					'v' => $v
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

	return array(
		'dss' => $u['num_1'],
		'val' => $VAL
	);
}
