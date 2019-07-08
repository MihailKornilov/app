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
function _element88_struct_vvv($el, $cl) {
	return array(
		'id'        => _num($cl['id']),
		'title'     => $cl['title'],
		'parent_id' => _num($cl['parent_id']),
		'dialog_id' => _num($cl['dialog_id']),
		'width'     => _num($cl['width']),
		'font'      => $cl['font'],
		'color'     => $cl['color'],
		'txt_7'     => $cl['txt_7'],//название колонки
		'txt_8'     => $cl['txt_8'],//pos: позиция

		'num_1'     => _num($cl['num_1']),
		'num_2'     => _num($cl['num_2']),
		'num_3'     => _num($cl['num_3']),
		'num_4'     => _num($cl['num_4']),
		'num_5'     => _num($cl['num_5']),
		'txt_1'     => $cl['txt_1'],//для [10]
		'txt_2'     => $cl['txt_2'],//для [11]
	);
}
function _element88_print($EL, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('Таблица из нескольких списков');

	$V = json_decode($EL['txt_2'], true);

	if(!$spv = _ids($V['spv']))
		return _emptyRed('Таблица из нескольких списков не настроена.');
	if(empty($V['col']))
		return _emptyRed('Таблица из нескольких списков не настроена');

	$ELM = array();
	if(!empty($EL['vvv']))
		foreach($EL['vvv'] as $ell)
			$ELM[$ell['id']] = $ell;

	$LIMIT = $EL['num_1'];
	$SC = $EL['num_6'] ? 'DESC' : 'ASC';


	//составление колонок для запроса
	$COL = array();
	foreach(_ids($spv, 'arr') as $id) {
		if(!$DLG = _dialogQuery($id))
			return _emptyRed('Диалога '.$id.' не существует');
		if($DLG['table_name_1'] != '_spisok')
			return _emptyRed('Диалог '.$id.' использует неверную таблицу');

		foreach(explode(',', _queryCol($DLG)) as $r)
			$COL[$r] = 1;
	}

	$COLL = array();
	foreach($COL as $c => $i) {
		if(strpos($c, 'dialog_id_use'))
			continue;
		$COLL[] = $c;
	}


	//получение данных списка
	$sql = "SELECT ".implode(',', $COLL)."
			FROM   `_spisok` `t1`
			WHERE  !`deleted`
			  AND  `dialog_id` in (".$spv.")
			ORDER BY `dtime_add` ".$SC."
			LIMIT ".$LIMIT;
	if(!$spisok = query_arr($sql))
		return _emptyMin($EL['txt_1']);

	//вставка значений из вложенных списков по каждому dialog_id
	$spInc = array();
	foreach(_ids($spv, 'arr') as $id)
		$spInc[$id] = array();
	foreach($spisok as $uid => $r)
		$spInc[$r['dialog_id']][$uid] = $r;
	foreach(_ids($spv, 'arr') as $id)
		$spInc[$id] = _spisokInclude($spInc[$id]);
	foreach(_ids($spv, 'arr') as $id)
		foreach($spInc[$id] as $uid => $r)
			$spisok[$uid] = $r;

	$TR = '';
	foreach($spisok as $uid => $u) {
		foreach(_ids($spv, 1) as $n => $dlg_id) {
			if($u['dialog_id'] != $dlg_id)
				continue;
			$TR .= '<tr'.($EL['num_4'] ? ' class="over1"' : '').'>';
			$prm = _blockParam(array('unit_get'=>$u));
			foreach($V['col'] as $col) {
				$cls = array();
				$txt = '';

				if($elm_id = $col['elm'][$n])
					if($ell = @$ELM[$elm_id]) {
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
	$TABLE_BEGIN = '<table class="_stab'._dn(!$EL['num_3'], 'small').'">';
	$TABLE_END = '</table>';

	return
	$TABLE_BEGIN.
	_element88_th($EL).
	$TR.
	$TABLE_END;
}
function _element88_th($el) {//показ имён колонок
	if(!$el['num_5'])
		return '';

	$V = json_decode($el['txt_2'], true);

	$eltd = array();
	if(!empty($el['vvv']))
		foreach($el['vvv'] as $r)
			$eltd[$r['id']] = $r;

	$send = '<tr>';
	foreach($V['col'] as $r) {
		$txt = $r['title'];
		foreach($r['elm'] as $id) {
			if(!$ell = @$eltd[$id])
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
function PHP12_elem88($prm) {//Настройка ячеек таблицы
	if(!$u = $prm['unit_edit'])
		return _emptyMin10('Настройка таблицы будет доступна после вставки списка в блок.');
	if(!$BL = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('Отсутствует исходный блок.');

	$ex = explode(' ', $BL['elem']['mar']);
	$w = $BL['width'] - $ex[1] - $ex[3];

	return
	'<div class="fs16 b color-555 bg-gr1 pl20 pt5 pb5 line-t line-b">Списки:</div>'.
	'<dl id="sp88" class="mt5 ml40"></dl>'.
	'<div class="fs15 color-555 pad10 center over1 curP">Добавить список</div>'.

	'<div class="fs16 color-555 bg-gr1 pl20 pt5 pb5 mt10 line-t">Колонки:</div>'.
	'<div class="calc-div h25 line-t line-b bg-efe">'.$w.'</div>'.
	'<dl id="col88" class="mt5"></dl>'.
	'<div class="fs15 color-555 pad10 center over1 curP">Добавить колонку</div>';
}
function PHP12_elem88_vvv($prm) {//данные для настроек
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$EL = _elemOne($u['id']))
		return array();

	//передача блока при выборе элемента для конкретном списке
	$send['block_id'] = $prm['srce']['block_id'];
	$send['element_id'] = $EL['id'];

	//списки для выбора
	$send['sp'] = _dialogSelArray('spisok_only');

	$val = json_decode($u['txt_2'], true);

	$send['spv'] = _ids($val['spv'], 1);
	$send['col'] = empty($val['col']) ? array() : $val['col'];

/*
	$send['spv'] = array(1192,1193);
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
		jsonError('Отсутствует содержание');
	if(!is_array($val))
		jsonError('Содержание не является массивом');
//	if(!$val['spv'] = _ids($val['spv']))
//		jsonError('Не выбраны списки');

	$val = json_encode($val);

	$sql = "UPDATE `_element`
			SET `txt_2`='".addslashes($val)."'
			WHERE `id`=".$elem_id;
	query($sql);

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
//	_pr($prm).
	'';
}
function PHP12_elem89_vvv($prm) {//данные колонок для конкретной таблицы
	$send['block_id'] = $prm['srce']['block_id'];
	$send['dss'] = $prm['srce']['dss'];
	$send['i'] = _num($prm['dop']);//порядковый номер списка
	$send['elm'] = array();//все элементы всех списков, использующиеся в таблице

	if(!$bl = _blockOne($send['block_id']))
		return $send;
	if(!$EL = _elemOne($bl['elem_id']))
		return $send;

	$send['elm'] = empty($EL['vvv']) ? array() : _arrKey($EL['vvv']);

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




