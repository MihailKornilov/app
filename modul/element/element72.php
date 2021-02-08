<?php

/* [72] Фильтр: год и месяц */
function _element72_struct($el) {
	return array(
		'txt_2' => $el['txt_2'],      // [13] фильтр по значению (если не указано, то по дате внесения)
		'num_3' => _num($el['num_3']),/* [17] воздействие на:
											1 - список
											2 - страница
									  */
		'num_1' => _num($el['num_1']),// [13] список, на который происходит воздействие (при num_3 == 1)

		'num_5' => _num($el['num_5']),// [1] дополнительные параметры (для отображения нижней части фильтра. Иначе выводится только год)
		'txt_3' => $el['txt_3'],      // [13] содержание (если не указано, выводятся названия месяцев)
		'num_6' => _num($el['num_6']),/* [18] использование счётчика:
											0 - нет
											1 - количество
											2 - сумма
									  */
		'num_2' => _num($el['num_2']),// [13] путь к сумме для подсчёта по каждому месяцу (при num_6 == 2)
		'txt_1' => $el['txt_1']       // [40] дополнительные условия для отображения количество или сумм
	) + _elementStruct($el);
}
function _element72_title() {
	return 'Фильтр: год и месяц';
}
function _element72_print($el, $prm) {
	if(!$el['num_3'] || $el['num_3'] > 2)
		return _emptyMinRed('[72] Не указан объект воздействия');
	if($el['num_3'] == 1 && !$el['num_1'])
		return _emptyMinRed('[72] Не выбран список');

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elem72v($el).'" />'.
	_yearleaf(array(
		'attr_id' => _elemAttrId($el, $prm).'yl',
		'value' => _elem72year($el)
	)).
	_elem72radio($el, $prm);
}
function _elem72v($el) {//выбранное значение
	$def = YEAR_CUR;

	if($el['num_6'])
		$def = YEAR_MON;

	return _filter('vv', $el, $def);
}
function _elem72year($el, $year=false) {//значение-год
	if($year)
		return $year;

	$v = _filter('vv', $el, YEAR_CUR);
	$ex = explode('-', $v);
	return $ex[0];
}
function _elem72radio($el, $prm) {//вывод месяцев (или значений) в радио-списке
	if(!$el['num_5'])
		return '';

	$spisok = _elem72radioSpisok($el);
	$spisok = _elem72Calc($el, $spisok);

	return
	'<div class="mt5">'.
		_radio(array(
			'attr_id' => _elemAttrId($el, $prm).'rd',
			'width' => 0,
			'block' => 1,
			'light' => 1,
			'interval' => 5,
			'value' => _elem72radioValue($el),
			'spisok' => $spisok,
			'disabled' => $prm['blk_setup']
		)).
	'</div>';
}
function _elem72radioValue($el, $year=false) {//выбранное значение для Радио
	if($year) {
		if(!$spisok = _elem72radioSpisok($el, $year))
			return 0;
		return _num(key($spisok));
	}

	if($v = _elem72v($el)) {
		$ex = explode('-', $v);
		return _num(@$ex[1]);
	}

	return 0;
}
function _elem72radioSpisok($el, $year=false) {//получение списка для Радио
	//элемент, указывающий на содержание
	if(!$ids = _ids($el['txt_3']))
		return _monthDef();
	//вложения в содержании пока не поддерживаются
	if(count(_ids($ids, 'arr')) > 1)
		return array();
	if(!$el3 = _elemOne($ids))
		return array();
	//выборка списка осуществляется на основании фильтра по значению. Он обязательно должен быть указан
	if(!$el2 = _idsLast($el['txt_2']))
		return array();
	if(!$col2 = _elemCol($el2))
		return array();
	if(!$DLG = _elemDlg($ids))
		return array();

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			  AND `".$col2."` LIKE '"._elem72year($el, $year)."%'";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $id => $r)
		$send[$id] = _element('print11', $el3, $r);

	return $send;
}
function _elem72Calc($el, $spisok, $year=false) {//добавление счётчиков к списку
	switch($el['num_6']) {
		//количество
		case 1: break;
		//сумма
		case 2:	break;

		default:return $spisok;
	}

	if(!$idsArr = _ids($el['txt_2'], 'arr'))//отсутствует путь к значению (данные по дате внесения)
		return _elem72CalcData($el, $spisok, $year);

	//не указан путь к сумме
	if($el['num_6'] == 2 && !$el['num_2'])
		return $spisok;

	if(count($idsArr) > 3)//вложенность более чем 3
		return $spisok;

	if(!$el2 = _elemOne($idsArr[count($idsArr) - 2]))//не получен элемент вложенного списка
		return $spisok;
	if(!$col2 = _elemCol($el2))//не получена колонка вложенного списка
		return $spisok;

	if(count($idsArr) == 2)
		return $spisok;

	if(!$elSp = _elemOne($el['num_1']))//не получены данные элемента-списка
		return $spisok;
	if(!$DLG2 = _elemDlg($el2['id']))//не получены данные диалога 2
		return $spisok;

	$col3 = '';
	foreach($DLG2['cmp'] as $cmp) {
		if($cmp['dialog_id'] != 29)
			continue;
		if($cmp['num_1'] != $elSp['num_1'])
			continue;
		if(!$col3 = _elemCol($cmp))//не получена колонка
			return $spisok;
		break;
	}

	$sql = "SELECT
				DISTINCT `".$col2."`,
				COUNT(*)
			FROM   "._queryFrom($DLG2)."
			WHERE  "._queryWhere($DLG2)."
			  AND `".$col2."` IN ("._idsGet($spisok, 'key').")
			  AND `".$col3."`
			GROUP BY `".$col2."`";
	if(!$ass = query_ass($sql))//нет значений во вложенном списке 3-го уровня
		return $spisok;

	foreach($ass as $id => $c)
		$spisok[$id] .= '<span class="fr">'._sumSpace(round($c)).'</span>';

	return $spisok;
}
function _elem72CalcData($el, $spisok, $year=false) {//получение количеств или сумм по дате внесения
	if(!$DLG = _elemDlg($el['num_1']))
		return $spisok;

	$field2 = "COUNT(*)";

	if($el['num_6'] == 2) {
		if(!$col = _elemCol($el['num_2']))
			return $spisok;
		$field2 = "SUM(`".$col."`)";
	}

	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%m')) * 1,
				".$field2."
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
				  "._40cond(array(), $el['txt_1'])."
			  AND `dtime_add` LIKE '"._elem72year($el, $year)."-%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
	if(!$ass = query_ass($sql))
		return $spisok;

	foreach($ass as $id => $c)
		$spisok[$id] .= '<span class="fr">'._sumSpace(round($c)).'</span>';

	return $spisok;
}



function _elem72filter($el) {//фильтр: год и месяц
	foreach(_filter('spisok', $el['id']) as $r) {
		if($r['elem']['dialog_id'] != 72)
			continue;
		if(_filterIgnore($r['elem']))
			continue;

		//фильтрация по дате внесения, если не указано значение
		if(!$ids = _ids($r['elem']['txt_2']))
			return " AND `t1`.`dtime_add` LIKE '".$r['v']."-%'";

		//значение указано в текущем диалоге
		if($ell = _elemOne($ids)) {
			if($col = _elemCol($ell))
				return " AND `t1`.`".$col."` LIKE '".$r['v']."-%'";
			return " AND !`t1`.`id` /* [72] не получена колонка */";
		}

		return _elem72filterCnn($r['elem'], $r['v']);
	}

	return '';
}
function _elem72filterCnn($el, $filterV) {//значение указано в привязанном диалоге
	if(!$idsArr = _ids($el['txt_2'], 'arr'))
		return " AND !`t1`.`id` /* [72] не получен путь к значению */";
	if(count($idsArr) > 3)
		return " AND !`t1`.`id` /* [72] вложенность более чем 3 */";
	if(!$ell = _elemOne(_idsLast($el['txt_2'])))
		return " AND !`t1`.`id` /* [72] не получен элемент */";
	if(!$DLG = _elemDlg($ell['id']))
		return " AND !`t1`.`id` /* [72] не получены данные диалога */";
	if(!$col = _elemCol($ell))
		return " AND !`t1`.`id` /* [72] не получена колонка */";

	$ex = explode('-', $filterV);
	$year = $ex[0];

	$sql = "SELECT `id`
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			  AND `".$col."` LIKE '".$year."%'";

	if($idV = _num(@$ex[1]))
		$sql .= " AND `id`=".$idV;

	if(!$idsLast = query_ids($sql))
		return " AND !`t1`.`id` /* [72] нет значений во вложенном списке */";


	if(!$el2 = _elemOne($idsArr[count($idsArr) - 2]))
		return " AND !`t1`.`id` /* [72] не получен элемент вложенного списка */";
	if(!$col2 = _elemCol($el2))
		return " AND !`t1`.`id` /* [72] не получена колонка вложенного списка */";

	if(count($idsArr) == 2)
		return " AND `".$col2."` IN (".$idsLast.")";

	if(!$elSp = _elemOne($el['num_1']))
		return " AND !`t1`.`id` /* [72] не получены данные элемента-списка */";
	if(!$DLG2 = _elemDlg($el2['id']))
		return " AND !`t1`.`id` /* [72] не получены данные диалога 2 */";

	$col3 = '';
	foreach($DLG2['cmp'] as $cmp) {
		if($cmp['dialog_id'] != 29)
			continue;
		if($cmp['num_1'] != $elSp['num_1'])
			continue;
		if(!$col3 = _elemCol($cmp))
			return " AND !`t1`.`id` /* [72] не получена колонка */";
		break;
	}

	$sql = "SELECT DISTINCT `".$col3."`
			FROM   "._queryFrom($DLG2)."
			WHERE  "._queryWhere($DLG2)."
			  AND `".$col2."` IN (".$idsLast.")";
	if(!$ids3 = query_ids($sql))
		return " AND !`t1`.`id` /* [72] нет значений во вложенном списке 3-го уровня */";

	return " AND `id` IN (".$ids3.")";
}



