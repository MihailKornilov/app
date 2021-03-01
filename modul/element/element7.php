<?php

/* [7] Фильтр: быстрый поиск */
function _element7_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],      //[8] текст поиска
		'num_1' => _num($el['num_1']),//[13] id элемента, содержащего список, по которому происходит поиск
		'txt_2' => $el['txt_2'],      //[13] по каким полям производить поиск (id элементов через запятую диалога списка)
		'num_3' => _num($el['num_3']),//[35] начинать поиск после ввода количества символов
		'num_2' => _num($el['num_2']),//[1] игнорировать другие фильтры
		'txt_3' => $el['txt_3']       //[13] фильтры, которые игнорируются
	) + _elementStruct($el);
}
function _element7_print($el, $prm) {
	return _search(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'v' => _filter('vv', $el),
		'disabled' => $prm['blk_setup']
	));
}
function _elem7filter($el) {//значения фильтра-поиска для списка
	//поиск элемента-фильтра-поиска
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			if(!$search = $r['elem'])
				return '';
			if(!$v = $r['v'])
				return '';
			break;
		}

	if(!isset($search))
		return '';

	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($search['txt_2'], 1))
		return '';

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		if(!$col = _elemCol($cmp_id))
			continue;

		if(_elemIsConnect($cmp[$cmp_id])) {
			if(!$DLG = _dialogQuery($cmp[$cmp_id]['num_1']))
				continue;
			if(!$colDef = _elemCol($DLG['spisok_elem_id']))
				continue;
			$sql = "SELECT `id`
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND `".$colDef."` LIKE '%".addslashes($v)."%'";
			$arr[] = "`t1`.`".$col."` IN (".$sql.")";
			continue;
		}

		$arr[] = "`t1`.`".$col."` LIKE '%".addslashes($v)."%'";
	}

	if(!$arr)
		return '';

	return " AND (".implode($arr, ' OR ').")";
}
function _elem7num14($el, $spisok=array()) {//добавление записи для списка-шаблона, если был быстрый поиск по номеру
	/*
		Запись с найденным номером будет добавляться при двух условиях:
		  1. Если существует быстрый поиск по этому списку
		  2. Если в шаблоне списка вставлен номер

		Найденное значение будет перемещено или вставлено в начало списка
	*/

	if($el['dialog_id'] != 14)
		return $spisok;

	//1. Поиск элемента-фильтра-поиска
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			if(!$search = $r['elem'])
				return $spisok;
			if(!$num = _num($r['v']))
				return $spisok;
			break;
		}

	if(!isset($search))
		return $spisok;

	//2. Определение, есть ли в шаблоне номер списка
	//получение элементов, находящихся в блоках
	if(!$ELM = _BE('elem_arr', 'spisok', $el['id']))
		return $spisok;
	if(!$col = _elem7numCol($ELM))
		return $spisok;

	$DLG = _dialogQuery($el['num_1']);

	if($col == 'num')
		if(!$tab = _queryTN($DLG, 'num', 1))
			$col = 'id';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  "._40cond($el, $el['txt_2'])."
			  AND `t1`.`".$col."`=".$num."
			LIMIT 1";
	if(!$u = query_assoc($sql))
		return $spisok;

	array_unshift($spisok, $u);
	$spisok[0] = $u;

	return $spisok;
}
function _elem7num23($el, $spisok=array()) {//добавление записи для списка-таблицы, если был быстрый поиск по номеру
	if($el['dialog_id'] != 23)
		return $spisok;

	//1. Поиск элемента-фильтра-поиска
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			if(!$search = $r['elem'])
				return $spisok;
			if(!$num = _num($r['v']))
				return $spisok;
			break;
		}

	if(!isset($search))
		return $spisok;

	//2. Определение, есть ли в таблице номер списка
	if(!$vvv = _element('vvv', $el))
		return $spisok;
	if(!$col = _elem7numCol($vvv))
		return $spisok;

	$DLG = _dialogQuery($el['num_1']);

	if($col == 'num')
		if(!$tab = _queryTN($DLG, 'num', 1))
			$col = 'id';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  "._40cond($el, $el['txt_2'])."
			  AND `t1`.`".$col."`=".$num."
			LIMIT 1";
	if(!$u = query_assoc($sql))
		return $spisok;

	array_unshift($spisok, $u);
	$spisok[0] = $u;

	return $spisok;
}
function _elem7numCol($vvv) {//получение колонки, по которой искать порядковый номер
	foreach($vvv as $r) {
		//сам порядковый номер
		if($r['dialog_id'] == 32)
			return $r['num_1'] == 1 ? 'id' : 'num';

		//сборный текст
		if($r['dialog_id'] == 44)
			foreach(_element('vvv', $r) as $v) {
				if($v['type'] != 'el')
					continue;
				if(!$ell = _elemOne($v['id']))
					continue;
				if($ell['dialog_id'] == 32)
					return $ell['num_1'] == 1 ? 'id' : 'num';
			}

		if($r['dialog_id'] == 11) {
			if(!$last_id = _idsLast($r['txt_2']))
				continue;
			if(!$ell = _elemOne($last_id))
				continue;
			if(!$col = _elem7numCol(array($ell)))
				continue;
			return $col;
		}
	}

	return false;
}
