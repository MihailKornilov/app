<?php

/* [78] Фильтр: меню */
function _element78_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'txt_1'   => $el['txt_1'],      //id элемента (с учётом вложений), содержащего значения (названия), составляющие меню
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений), содержащего количество записей по каждому пункту
	) + _elementStruct($el);
}
function _element78_print($el) {
	if(!$ids = _ids($el['txt_1'], 1))
		return _emptyMin('Фильтр-меню: отсутствуют ID значений.');

	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemOne($elem_id))
		return _emptyMin('Фильтр-меню: значение отсутствует.');
	if(!$BL = _blockOne($EL['block_id']))
		return _emptyMin('Фильтр-меню: нет блока.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMin('Фильтр-меню: блок не из диалога.');
	if(!$dialog_id = $BL['obj_id'])
		return _emptyMin('Фильтр-меню: нет ID диалога.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin('Фильтр-меню: нет диалога.');

	$col = $EL['col'];//колонка текстового значения
	$colCount = '';//колонка значения количества
	if($ids = _ids($el['txt_2'], 1)) {
		$c = count($ids) - 1;
		$elem_id = $ids[$c];
		if($EL3 = _elemOne($elem_id))
			$colCount = $EL3['col'];
	}

	$cond = "`app_id`=".APP_ID;
	if(isset($dialog['field1']['deleted']))
		$cond .= " AND !`deleted`";
	if(isset($dialog['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;
	$sql = "SELECT *
			FROM `"._table($dialog['table_1'])."`
			WHERE ".$cond."
			ORDER BY `sort`,`id`";
	if(!$arr = DB1::arr($sql))
		return _emptyMin('Фильтр-меню: пустое меню.');

	$send = '';
	$v = _filter('vv', $el, 0);

	$spisok = array();
	foreach($arr as $r)
		$spisok[$r['parent_id']][] = $r;

	foreach($spisok[0] as $r) {
		$child = '';
		$child_sel = false;//список будет раскрыт, если в нём было выбранное значение
		if(!empty($spisok[$r['id']]))
			foreach($spisok[$r['id']] as $c) {
				$sel = $v == $c['id'] ? ' sel' : '';
				if($sel)
					$child_sel = true;
				$child .= '<div class="fm-unit'.$sel.'" val="'.$c['id'].'">'.
							$c[$col].
							($colCount ? '<span class="ml10 clr2 b">'.$c[$colCount].'</span>' : '').
						'</div>';
			}

		$sel = $v == $r['id'] ? ' sel' : '';
		$send .=
			'<table class="w100p">'.
				'<tr>'.
		  ($child ? '<td class="fm-plus">'.($child_sel ? '-' : '+') : '<td class="w25">').//—
					'<td><div class="fm-unit b fs14'.$sel.'" val="'.$r['id'].'">'.
							$r[$col].
							($colCount ? '<span class="ml10 clr2 b">'.$r[$colCount].'</span>' : '').
						'</div>'.
			'</table>'.
			($child ? '<div class="ml40'._dn($child_sel).'">'.$child.'</div>' : '');
	}

	return $send;
}
function _elem78filter($el) {//фильтр-меню
	$filter = false;
	$v = '';

	//поиск элемента-фильтра-меню
	foreach(_filter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 78) {
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
	if($EL['dialog_id'] == 29 && !$dialog_id = _num($EL['num_1']))
		return " AND !`id`";
	if(!$dialog = _dialogQuery($dialog_id))
		return " AND !`id`";

	if(isset($dialog['field1']['parent_id'])) {
		$sql = "SELECT `id`
				FROM `"._table($dialog['table_1'])."`
				WHERE `parent_id`=".$v;
		if($ids = DB1::ids($sql))
			$v .= ','.$ids;
	}

	return " AND `".$col."` IN (".$v.")";
}

