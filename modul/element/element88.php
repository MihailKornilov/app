<?php

/* [88] Таблица из нескольких списков */
function _element88_struct($el) {
	return array(
		'num_1' => _num($el['num_1']),//Кол-во выводимых строк
		'num_2' => _num($el['num_2']),//Флаг текста, когда нет данных
		'num_3' => _num($el['num_3']),//узкие строки таблицы
		'num_4' => _num($el['num_4']),//подсвечивать строку при наведении мыши
		'num_5' => _num($el['num_5']),//показывать имена колонок
		'num_6' => _num($el['num_6']),//обратный порядок
		'txt_1' => $el['txt_1'],      //текст, если нет данных
		'txt_2' => $el['txt_2']       //содержание
	) + _elementStruct($el);
}
function _element88_vvv($el) {
	if(!$V = _decode($el['txt_2']))
		return array();

	$send = array();
	foreach($V['col'] as $r)
		foreach($r['elm'] as $id)
			if($ell = _elemOne($id))
				$send[$id] = $ell;

	return $send;
}
function _element88_print($EL, $prm=array(), $next=0) {
	if(!empty($prm['blk_setup']))
		return _emptyMin('Таблица из нескольких списков');

	$V = json_decode($EL['txt_2'], true);

	if(empty($V['spv']))
		return _emptyRed('Таблица из нескольких списков не настроена.');
	if(empty($V['col']))
		return _emptyRed('Таблица из нескольких списков не настроена');

	$LIMIT = $EL['num_1'];
	$SC = $EL['num_6'] ? 'DESC' : 'ASC';

	//составление колонок для запроса
	$SPV_IDS = array(); //ids диалогов
	$COL = array();
	foreach($V['spv'] as $spv) {
		if(!$id = $spv['dialog_id'])
			return _emptyRed('Не выбран один из диалогов');
		if(!$DLG = _dialogQuery($id))
			return _emptyRed('Диалога '.$id.' не существует');
		if($DLG['table_name_1'] != '_spisok')
			return _emptyRed('Диалог '.$id.' использует неверную таблицу');
		
		$SPV_IDS[] = $id;

		foreach(explode(',', _queryCol($DLG)) as $c)
			$COL[$c] = 1;
	}

	$COLL = array();
	foreach($COL as $c => $i) {
		if(strpos($c, 'dialog_id_use'))
			continue;
		$COLL[] = $c;
	}


	if(!$EL['all'] = _elem88countAll($EL))
		return _emptyMin($EL['txt_1']);

	//получение данных списка
	$sql = "SELECT ".implode(',', $COLL)."
			FROM   `_spisok` `t1`
			WHERE  !`deleted`
			  "._elem88cond($EL)."
			  "._elem77filter($EL)."
			ORDER BY `dtime_add` ".$SC."
			LIMIT ".($LIMIT * $next).",".$LIMIT;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков по каждому dialog_id
	$spInc = array();
	foreach($SPV_IDS as $id)
		$spInc[$id] = array();
	foreach($spisok as $uid => $r)
		$spInc[$r['dialog_id']][$uid] = $r;
	foreach($SPV_IDS as $id)
		$spInc[$id] = _spisokInclude($spInc[$id]);
	foreach($SPV_IDS as $id)
		foreach($spInc[$id] as $uid => $r)
			$spisok[$uid] = $r;

	$TR = '';
	foreach($spisok as $uid => $u) {
		foreach($SPV_IDS as $n => $dlg_id) {
			if($u['dialog_id'] != $dlg_id)
				continue;
			$TR .= '<tr'.($EL['num_4'] ? ' class="over1"' : '').'>';
			$prm = _blockParam(array('unit_get'=>$u));
			foreach($V['col'] as $col) {
				$cls = array();
				$txt = '';

				if($elm_id = $col['elm'][$n])
					if($ell = _elemOne($elm_id)) {

						//если элемент скрыт
						if(_elemAction244($ell, $prm)) {
							$TR .= '<td'._elemStyleWidth($col).'>';
							continue;
						}

						switch($ell['dialog_id']) {
							case 25: //кружок-статус
							case 36: //иконка
							case 71: //иконка сортировки
								$cls[] = 'pad0';
						}
						$cls[] = $ell['font'];
						$cls[] = $ell['txt_8'];//pos - позиция
						$cls[] = _elemAction242($ell, $prm);//подмена цвета
						$txt = _elemPrint($ell, $prm);
						$txt = _elemFormat($ell, $prm, $txt);//форматирование для ячеек таблицы
				}

				$cls = array_diff($cls, array(''));
				$cls = implode(' ', $cls);
				$cls = $cls ? ' class="'.$cls.'"' : '';

				$TR .= '<td'.$cls._elemStyleWidth($col).'>'.$txt;
			}
		}
	}

	//открытие и закрытие таблицы
	$TABLE_BEGIN = !$next ? '<table class="_stab'._dn(!$EL['num_3'], 'small').'">' : '';
	$TABLE_END   = !$next ? '</table>' : '';

	return
	$TABLE_BEGIN.
	_elem88th($EL, $next).
	$TR.
	_elem88next($EL, $next).
	$TABLE_END;
}
function _elem88cond($el) {//условия из настроек списка
	$V = _decode($el['txt_2']);

	$SPV_COND = array();//массив условий для каждого диалога
	foreach($V['spv'] as $spv) {
		if(!$id = $spv['dialog_id'])
			return " AND !`id`";
		if(!$DLG = _dialogQuery($id))
			return " AND !`id`";
		if($DLG['table_name_1'] != '_spisok')
			return " AND !`id`";

		$SPV_COND[$id] = $spv['cond'];
	}

	$cond = array();
	foreach($SPV_COND as $id => $r) {
		$c = "`dialog_id`=".$id;
		if($r)
			$c .= _40cond(array(), $r);
		$cond[] = $c;
	}

	return " AND (".implode(' OR ', $cond).")";
}
function _elem88th($el, $next) {//показ имён колонок
	if(!$el['num_5'])
		return '';
	if($next)
		return '';

	$V = json_decode($el['txt_2'], true);

	$send = '<tr>';
	foreach($V['col'] as $r) {
		$txt = $r['title'];
		foreach($r['elm'] as $id) {
			if(!$ell = _elemOne($id))
				continue;
			if($ell['dialog_id'] != 91)
				continue;

			$txt = _check(array(
				'attr_id' => 'sch'.$ell['id'].'_all',
				'value' => 0
			));
			break;
		}
		$send .= '<th>'.$txt;
	}

	return $send;
}
function _elem88countAll($el) {//общее количество строк списка
	$sql = "SELECT COUNT(*)
			FROM   `_spisok` `t1`
			WHERE  !`deleted`".
			  _elem88cond($el).
			  _elem77filter($el);
	return query_value($sql);
}
function _elem88next($EL, $next) {//tr-догрузка списка
	if($EL['num_1'] * ($next + 1) >= $EL['all'])
		return '';

	$count_next = $EL['all'] - $EL['num_1'] * ($next + 1);
	if($count_next > $EL['num_1'])
		$count_next = $EL['num_1'];

	return
	'<tr class="over5 curP center clr15" onclick="_elem88next($(this),'.$EL['id'].','.($next + 1).')">'.
		'<td colspan="20">'.
			'<tt class="db '.($EL['num_3'] ? 'fs13 pt3 pb3' : 'fs14 pad5').'">'.
				'Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').
			'</tt>';
}
function _elem88dlgId($elem_id) {//получение id диалога по элементу, если таблица содежит этот элемент
	if(!$el = _elemOne($elem_id))
		return 0;
	if(!$elp = _elemOne($el['parent_id']))
		return 0;
	if($elp['dialog_id'] != 88)
		return 0;
	if(!$V = _decode($elp['txt_2']))
		return 0;

	foreach($V['col'] as $r)
		foreach($r['elm'] as $n => $id)
			if($elem_id == $id)
				return _num($V['spv'][$n]['dialog_id']);

	return 0;
}


/* ---=== [88] Настройка ячеек таблицы ===--- */
function PHP12_elem88($prm) {
	if(empty($prm['unit_edit']))
		return _emptyMin10('Настройка таблицы будет доступна после вставки списка в блок.');
	if(!$bl = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('[88] Отсутствует исходный блок.');
	if(!$el = _elemOne($bl['elem_id']))
		return _emptyMin10('[88] Отсутствует элемент.');

	return
	'<div class="fs16 b clr9 bg-gr1 pl15 pt5 pb5 line-t line-b">Списки:</div>'.
	'<dl id="sp88" class="mt5 ml10"></dl>'.
	'<div class="fs15 clr9 pad10 center over1 curP">Добавить список</div>'.

	'<div class="fs16 clr9 bg-gr1 pl15 pt5 pb5 mt10 line-t">Колонки:</div>'.
	'<div class="calc-div h25 line-t line-b bg-efe">'._elemWidth($el).'</div>'.
	'<dl id="col88" class="mt5"></dl>'.
	'<div class="fs15 clr9 pad10 center over1 curP">Добавить колонку</div>';
}
function PHP12_elem88_vvv($prm) {//данные для настроек
	if(empty($prm['unit_edit']))
		return array();
	if(!$EL = _elemOne($prm['unit_edit']['id']))
		return array();

	//передача блока при выборе элемента для конкретном списке
	$send['block_id'] = $prm['srce']['block_id'];
	$send['element_id'] = $EL['id'];

	//списки для выбора
	$send['sp'] = _dialogSelArray('spisok_only');

	$val = json_decode($prm['unit_edit']['txt_2'], true);

	if(!empty($val['spv'])) {
		foreach($val['spv'] as $n => $r) {
			$val['spv'][$n]['dialog_id'] = _num($r['dialog_id']);
			$c = $r['cond'] ? count($r['cond']) : '';
			$val['spv'][$n]['c'] = $c ? $c.' услови'._end($c, 'е', 'я', 'й') : '';
			if(!empty($r['cond']))
				$val['spv'][$n]['cond'] = json_encode($r['cond']);
		}
	}

	$send['spv'] = empty($val['spv']) ? array() : $val['spv'];
	$send['col'] = empty($val['col']) ? array() : $val['col'];

/*
	$send['spv'] = array(
		array(
			dialog_id:1192
			cond:array()
		),
		array(
			dialog_id:1193
			cond:array()
		)
	);
	$send['col'] = array(
		array(
			'width' => 200,
			'title' => 'Название',
			'elm' => array(0,0)
		),
		array(
			'width' => 100,
			'title' => 'Дата',
			'elm' => array(0,0)
		),
		array(
			'width' => 250,
			'title' => 'Описание',
			'elm' => array(0,0)
		)
	);
*/
	return $send;
}
function PHP12_elem88_save($cmp, $val, $unit) {//сохранение
	if(!$elem_id = _num(@$unit['id']))
		jsonError('Некорректный ID элемента');
	if(empty($val))
		return;
//		jsonError('Отсутствует содержание');
	if(!is_array($val))
		jsonError('Содержание не является массивом');
	if(empty($val['spv']))
		jsonError('Не выбрано ни одного списка');

	$rpt = array();
	foreach($val['spv'] as $n => $r) {
		if(!empty($r['cond']))
			$val['spv'][$n]['cond'] = json_decode($r['cond'], true);

		$id = $r['dialog_id'];
		if(!isset($rpt[$id]))
			$rpt[$id] = 1;
		else
			jsonError('Нельзя выбирать один и тот же список повторно');
	}

	$json = json_encode($val);

	$sql = "UPDATE `_element`
			SET `txt_2`='".addslashes($json)."'
			WHERE `id`=".$elem_id;
	query($sql);

	//удаление удалённых элементов
	$elm = array();
	foreach($val['col'] as $r)
		foreach($r['elm'] as $id)
			if($id = _num($id))
				$elm[] = $id;
	if(!empty($elm)) {
		$sql = "DELETE FROM `_element`
				WHERE `parent_id`=".$elem_id."
				  AND `id` NOT IN (".implode(',', $elm).")";
		query($sql);
	}

	_elemOne($elem_id, true);
}
function PHP12_elem89($prm) {//настройка колонок для конкретной таблицы
	if(!$dss = $prm['srce']['dss'])
		return _emptyMin10('Не получен ID списка');
	if(!$DLG = _dialogQuery($dss))
		return _emptyMin10('Диалога '.$dss.' не существует');

	return
	'<div class="line-b bg-gr1 pad10 fs15">'.
		'Колонки списка <b class="fs15">'.$DLG['name'].'</b>:'.
	'</div>'.
	'<div id="col89" class="pad10"></div>'.
	'';
}
function PHP12_elem89_vvv($prm) {//данные колонок для конкретной таблицы
	$send['block_id'] = $prm['srce']['block_id'];
	$send['dss'] = $prm['srce']['dss'];
	$send['i'] = _num($prm['dop']);//порядковый номер списка
	$send['elm'] = array();//все элементы всех списков, использующиеся в таблице

	if(!$bl = _blockOne($send['block_id']))
		return $send;
	if(!$el = _elemOne($bl['elem_id']))
		return $send;

	$send['elm'] = _element('vvv', $el);

	return $send;
}
function PHP12_elem89_save($cmp, $val, $unit) {//сохранение
	if(!$elem_id = _num(@$unit['id']))
		jsonError('Некорректный ID элемента');
	if(empty($val))
		return;
	if(!is_array($val))
		return;

	foreach($val as $r) {
		if(!$id = _num($r['id']))
			continue;

		$sql = "UPDATE `_element`
				SET `font`='".$r['font']."',
					`color`='".$r['color']."',
					`txt_8`='".$r['txt_8']."'
				WHERE `id`=".$id;
		query($sql);
	}

	_elemOne($elem_id, true);
}




