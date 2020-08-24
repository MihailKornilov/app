<?php

/* [102] Фильтр: Выбор нескольких групп значений */
function _element102_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента: список, на который воздействует фильтр [13]
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2'],      //ids элементов: привязанный список (зависит от num_1) [13]
		'txt_3'   => $el['txt_3'],      //ids элементов: счётчик количеств  (зависит от num_1) [13]
		'txt_4'   => $el['txt_4'],      //ids элементов: путь к цветам (зависит от num_1) [13]
		'txt_5'   => $el['txt_5']       //значение по умолчанию: настраивается через [40]
	) + _elementStruct($el);
}
function _element102_print($el, $prm) {
	$v = _filter('v', $el['id']);
	if($v === false) {
		$cond = _40cond($el, $el['txt_5']);
		$v = _elem102CnnList($el['txt_2'], 'ids', $cond);
		_filter('insert', array(
			'spisok' => $el['num_1'],
			'filter' => $el['id'],
			'v' => $v
		));
	}

	$vAss = _idsAss($v);

	//ассоциативный массив с количествами
	$countAss = _elem102CnnList($el['txt_3'], 'ass');

	//ассоциативный массив с цветами
	$bgAss = _elem102CnnList($el['txt_4'], 'ass');

	$title = array();//ассоциативный массив с именами значений фильтра для JS
	$spisok = '';
	$sel = '';//выбранные значения
	if($arr = _elem102CnnList($el['txt_2'])) {
		$n = 0;
		$selOne = '';
		foreach($arr as $r) {
			$id = $r['id'];
			$bg = isset($bgAss[$id]) ? ' style="background-color:'.$bgAss[$id].'"' : '';
			$c = _hide0(@$countAss[$id]);
			$spisok .=
				'<tr class="over1" val="'.$r['id'].'">'.
					'<th class="w35 pad8 center"'.$bg.'>'.
						_check(array(
							'attr_id' => 'chk'.$id,
							'value' => isset($vAss[$id])
						)).
					'<td class="wsnw">'.$r['title'].
					'<td class="r fs12 clr1 b">'.$c;

			$title[$id] = $r['title'];

			if(isset($vAss[$id])) {
				$c = _num($c);
				$sel .= $c ? '<div'.$bg.' class="un tool" data-tool="'.$r['title'].'">'.$c.'</div>' : '';
				$selOne = '<div class="un"'.$bg.'>'.$r['title'].'</div>';
				$n++;
			}
		}
		if($n == 1)
			$sel = $selOne;
	}

	return
	'<div class="_filter102"'._elemStyleWidth($el).' id="'._elemAttrId($el, $prm).'_filter102">'.
		'<div class="holder'._dn(!$sel).'">'.$el['txt_1'].'</div>'.
		'<table class="w100p">'.
			'<tr><td class="td-un">'.($sel ? $sel : '<div class="icon icon-empty"></div>').
				'<td class="w25 top r">'.
					'<div class="icon icon-del pl tool'._dn($sel, 'vh').'" data-tool="Очистить фильтр"></div>'.
		'</table>'.
		'<div class="list">'.
			'<table>'.$spisok.'</table>'.
		'</div>'.
	'</div>'.
	'<script>'.
		'var EL'.$el['id'].'_F102_TITLE='._json($title).','.
			'EL'.$el['id'].'_F102_C='._json($countAss).','.
			'EL'.$el['id'].'_F102_BG='._json($bgAss).';'.
	'</script>';
}
function _elem102CnnList($ids, $return='select', $cond='') {//значения привязанного списка (пока для фильтра 102)
	/*
		$return - варианты возврата:
			select - по умолчанию
			ass
			ids
	*/


	if(!$last_id = _idsLast($ids))
		return array();
	if(!$dlg = _elemDlg($last_id))
		return array();
	if(!$col = _elemCol($last_id))
		return array();

	//получение данных списка
	$sql = "SELECT "._queryCol($dlg)."
			FROM   "._queryFrom($dlg)."
			WHERE  "._queryWhere($dlg)."
				   ".$cond."
			ORDER BY ".(_queryColReq($dlg, 'sort') ? "`sort`,`id`" : '`id`');
	if(!$spisok = query_arr($sql))
		return array();

	//требуется ли вывод списка по уровням
	if($return == 'select')
		foreach($spisok as $r)
			if($r['parent_id']) {
				$child = array();
				foreach($spisok as $id => $sp)
					$child[$sp['parent_id']][$id] = $sp;
				return _elem102CnnChild($col, $child);
			}

	$select = array();
	$ass = array();
	foreach($spisok as $id => $r) {
		$select[] = array(
			'id' => $id,
			'title' => $r[$col]
		);
		$ass[$id] = $r[$col];
	}

	if($return == 'ass')
		return $ass;
	if($return == 'ids')
		return _idsGet($select);

	return $select;
}
function _elem102CnnChild($col, $child, $pid=0, $spisok=array(), $path='', $level=0) {//расстановка дочерних значений
	if(!$send = @$child[$pid])
		return $spisok;

	foreach($send as $id => $sp) {
		$content = $sp[$col];
		$u = array(
			'id' => $id,
			'title' => $path.$sp[$col],
			'content' => '<b>'.$content.'</b>'
		);
		if($level)
			$u['content'] = '<div class="ml'.($level*20).'">'.$content.'</div>';
		$spisok[] = $u;
		$spisok = _elem102CnnChild($col, $child, $id, $spisok, $path.$sp[$col].' » ', $level+1);
	}

	return $spisok;
}
function _elem102filter($el) {//Фильтр - Выбор нескольких групп значений
	$filter = false;
	$v = 0;

	//поиск элемента-фильтра-select
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 102) {
			if(!$filter = $r['elem'])
				return '';
			if(!$v = _num($r['v']))
				return '';
			break;
		}

	if(empty($filter))
		return '';
	if(!$elem_id = _idsFirst($filter['txt_2']))
		return '';
	if(!$ell = _elemOne($elem_id))
		return '';
	if(!$col = _elemCol($ell))
		return '';

	return " AND `".$col."` IN (".$v.")";
}

