<?php

/* [83] Фильтр: Select - привязанный список */
function _element83_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента-списка, на который воздействует фильтр
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2']       //[13] id элемента (с учётом вложений) - привязанный список
	) + _elementStruct($el);
}
function _element83_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _filter('vv', $el, 0)
	));
}
function _element83_vvv($el) {
	//---=== ШАГ 1: Получение количеств привязанных записей (если элемент является привязанным списком) ===---
	$CC = array();
	$DLG = array();
	$col = '';
	foreach(_ids($el['txt_2'], 'arr') as $n => $elem_id) {
		if(!$ell = _elemOne($elem_id))
			return array();
		
		//исходный диалог, к которому применяется фильтр. Количество записей именно этого диалога будет показано в фильтре
		if(!$DLG = _elemDlg($elem_id))
			return array();

		//колонка привязанной записи, по которой будет производиться группировка значений исходного диалога
		if(!$col = _elemCol($elem_id))
			return array();
		
		if(empty($ell['issp']))
			break;

		if(!$n) {
			$sql = "SELECT `".$col."`,COUNT(`id`) `cc`
					FROM   "._queryFrom($DLG)."
					WHERE  "._queryWhere($DLG)."
					  AND `".$col."`
					GROUP BY `".$col."` /* [83-] ".$n." */";
			$CC = query_ass($sql);
			continue;
		}

		if($n > 1)
			return array(
				'id' => -1,
				'title' => '[83] Далее 3-го уровня не реализовано'
			);

		$mass = array();
		$sql = "SELECT `id`,`".$col."`
				FROM   "._queryFrom($DLG)."
				WHERE  "._queryWhere($DLG)."
				  AND `id` IN ("._idsGet($CC, 'key').") /* [83-] ".$n." */";
		foreach(query_ass($sql) as $id => $ccid) {
			if(!isset($mass[$ccid]))
				$mass[$ccid] = 0;
			$mass[$ccid] += $CC[$id];
		}

		$CC = $mass;
	}



	//---=== ШАГ 2: получение имён для составления содержания фильтра ===---
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			  AND `id` IN ("._idsGet($CC, 'key').")
			ORDER BY ".(_queryColReq($DLG, 'sort') ? "`sort`,`id`" : '`id`');
	if(!$spisok = query_arr($sql))
		return array();

	$parentLost = array();
	foreach($spisok as $id => $r) {
		//присвоение количеств каждому значению
		if(!empty($CC[$id]))
			$spisok[$id]['cc'] = $CC[$id];

		//потерянные значения родителей
		if(!empty($r['parent_id'])) {
			$pid = $r['parent_id'];
			if(empty($spisok[$pid]))
				$parentLost[$pid] = $pid;
		}
	}

	if(!empty($parentLost)) {
		$sql = "SELECT "._queryCol($DLG)."
				FROM   "._queryFrom($DLG)."
				WHERE  "._queryWhere($DLG)."
				  AND `id` IN ("._idsGet($parentLost, 'key').")";
		foreach(query_arr($sql) as $id => $r)
			$spisok[$id] = $r;
	}


	//требуется ли вывод списка по уровням
	foreach($spisok as $r)
		if(!empty($r['parent_id'])) {
			$child = array();
			foreach($spisok as $id => $sp)
				$child[$sp['parent_id']][$id] = $sp;
			$child = _elem102CnnCount($child);
			return _elem102CnnChild($col, $child);
		}


	$send = array();
	foreach($spisok as $id => $r) {
		$cc = !empty($r['cc']) ? '<div class="fr clr2 b fs12">'.$r['cc'].'</div>' : '';
		$send[] = array(
			'id' => $id,
			'title' => $r[$col],
			'content' => $r[$col].$cc
		);
	}

	return $send;
}
function _elem83filter($el) {//фильтр-select
	$send = '';
	//поиск элемента-фильтра-select
	foreach(_filter('spisok', $el['id']) as $r) {
		if($r['elem']['dialog_id'] != 83)
			continue;
		if(!$filter = $r['elem'])
			continue;
		if(!$v = _num($r['v']))
			continue;

		$send .= _elem83filterWhere($filter, $v);
	}

	return $send;
}
function _elem83filterWhere($filter, $v) {
	if(!$ids = _ids($filter['txt_2'], 'arr'))
		return ' AND !`id` /* [83] err0 */';

	//если указано поле в самом диалоге списка, то фильтр показывает на значение этого же диалога
	if(count($ids) == 1)
		return " AND `id`=".$v." /* [83] myself */";

	if(count($ids) > 3)
		return ' AND !`id` /* [83] далее 3-го уровня не сделано */';

	if(!$col = _elemCol($ids[0]))
		return ' AND !`id` /* [83] err1 */';

	if(!$DLG = _elemDlg($ids[1]))
		return ' AND !`id` /* [83] err2 */';

	if(count($ids) == 2) {
		//проверка, нужно ли добавлять дочерние значения
		$PIDS = $v;
		$ass[$v] = 1;
		while($PIDS) {
			$sql = "SELECT `id`
					FROM   "._queryFrom($DLG)."
					WHERE  "._queryWhere($DLG)."
					  AND `parent_id` IN (".$PIDS.")";
			if($PIDS = query_ids($sql))
				$ass += _idsAss($PIDS);
		}
		return " AND `".$col."` IN ("._idsGet($ass, 'key').") /* [83] level 2 */";
	}


	//колонка второго уровня. По ней происходит сборка IDS второго уровня
	if(!$col1 = _elemCol($ids[1]))
		return ' AND !`id` /* [83] err3 */';

	//проверка, нужно ли добавлять дочерние значения
	if(!$DLG2 = _elemDlg($ids[2]))
		return ' AND !`id` /* [83] err4 */';

	$PIDS = $v;
	$ass[$v] = 1;
	while($PIDS) {
		$sql = "SELECT `id`
				FROM   "._queryFrom($DLG2)."
				WHERE  "._queryWhere($DLG2)."
				  AND `parent_id` IN (".$PIDS.")";
		if($PIDS = query_ids($sql))
			$ass += _idsAss($PIDS);
	}

	$sql = "SELECT `id`
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			  AND `".$col1."` IN ("._idsGet($ass, 'key').")";
	if(!$ids = query_ids($sql))
		return ' AND !`id` /* [83] level 3 empty */';

	return " AND `".$col."` IN (".$ids.") /* [83] level 3 */";
}

