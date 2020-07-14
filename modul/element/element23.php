<?php

/* [23] Список-таблица */
function _element23_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2'   => _num($el['num_2']),//длина (количество строк, выводимых за один раз)
		'txt_1'   => $el['txt_1'],	  //сообщение пустого запроса
		'txt_2'   => $el['txt_2'],	  //условия отображения, настраиваемые через [40]
		'num_3'   => _num($el['num_3']),//узкие строки таблицы
		'num_4'   => _num($el['num_4']),//подсвечивать строку при наведении мыши
		'num_5'   => _num($el['num_5']),//показывать имена колонок
		'num_6'   => _num($el['num_6']),//обратный порядок
		'num_7'   => _num($el['num_7']),//уровни сортировки: 1,2,3 (при num_8=6161)
		'num_8'   => _num($el['num_8']),/* порядок вывода данных [18]
											6159 - по дате внесения
											6160 - по значению из диалога
											6161 - ручная сортировка (если выбрано, длина списка становится 1000)
										*/

		'num_9'   => _num($el['num_9']),//включение отображения сообщения пустого запроса
		'num_10'  => _num($el['num_10'])//выбранное значение для порядка (при num_8=6160)
	) + _elementStruct($el);
}
function _element23_struct_title($el, $DLG) {
	if(!$dlg_id = $el['num_1'])
		return $el;
	if(empty($DLG[$dlg_id]))
		return $el;
	$el['title'] = $DLG[$dlg_id]['name'];
	return $el;
}
function _element23_struct_vvv($el, $cl) {
	$send = array(
		'id'		=> _num($cl['id']),
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
		'txt_1'     => $cl['txt_1'],//для [10][44]
		'txt_2'     => $cl['txt_2'],//для [11]
		'vvv'       => array()      //для [44]
	);

	$send = _elem44vvv($send);

	return $send;
}
function _element23_js($el) {
	return array(
		'num_7'   => _num($el['num_7']),
		'num_8'   => _num($el['num_8'])
	) + _elementJs($el);
}
function _element23_print($ELEM, $prm=array(), $next=0) {//вывод списка в виде таблицы
	if(!empty($prm['blk_setup']))
		return _emptyMin('Список-таблица <b>'._dialogParam($ELEM['num_1'], 'name').'</b>');

	//диалог, через который вносятся данные списка
	if(!$dialog_id = $ELEM['num_1'])
		return _emptyRed('Не указан список для вывода данных.');
	if(!$DLG = _dialogQuery($dialog_id))
		return _emptyRed('Списка <b>'.$dialog_id.'</b> не существует.');
	if(!$all = _spisokCountAll($ELEM, $prm))
		return $ELEM['num_9'] ? _emptyMin(_br($ELEM['txt_1'])) : '';

	$limit = $ELEM['num_2'];
	$SC = $ELEM['num_6'] ? 'DESC' : 'ASC';
	$order = "`t1`.`id`";
	if($tab = _queryTN($DLG, 'dtime_add'))
		$order = "`".$tab."`.`dtime_add`";
	$IS_SORT = false;

	switch($ELEM['num_8']) {
		//по дате внесения
		default:
		case 6159: break;
		//по значению из диалога
		case 6160:
			if(!$col = _elemCol($ELEM['num_10']))
				break;
			if($tab = _queryTN($DLG, $col))
				$order = "`".$tab."`.`".$col."`";
			break;
		//ручная сортировка
		case 6161:
			$IS_SORT = true;
			$order = "`sort`";
			$limit = 1000;  //если включена сортировка, количество максимальное
			$SC = 'ASC';
			break;
	}

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._spisokWhere($ELEM, $prm)."
			ORDER BY ".$order." ".$SC."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);
	//вставка картинок
	$spisok = _spisokImage($spisok);

	if(empty($ELEM['vvv']))
		return _emptyRed('Таблица не настроена.');

	$MASS = array();
	foreach($spisok as $uid => $u) {
		$TR = '<tr class="tr-unit'.($ELEM['num_4'] ? ' over1' : '').'" val="'.$u['id'].'">';
		$prm = _blockParam(array('unit_get'=>$u));
		foreach($ELEM['vvv'] as $td) {
			$cls = array();
			$txt = '';

			//если элемент не скрыт
			if(!_elemAction244($td, $prm)) {
				$txt = _elemPrint($td, $prm);

				switch($td['dialog_id']) {
					case 11:
						if($ell = _elemOne($td['txt_2']))
							if($ell['dialog_id'] == 60)//картинка через [11]
								$cls[] = 'pad0';
						break;
					case 25: //кружок-статус
					case 36: //иконка
					case 71: //иконка сортировки
						$cls[] = 'pad0';

				}

				$cls[] = $td['font'];
				$cls[] = $td['txt_8'];//pos - позиция
				$cls[] = _elemAction242($td, $prm);//подмена цвета
				$cls[] = _elemHintOn($td);//наличие подсказки

				$txt = _elemFormat($td, $prm, $txt);//[23] форматирование для ячеек таблицы
			}

			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);
			$cls = $cls ? ' class="'.$cls.'"' : '';

			$TR .= '<td'.$cls._elemStyleWidth($td)._elemDivDataHint($td, $prm).'>'.$txt;
		}
		$MASS[$uid] = $TR;
	}

	//tr-догрузка списка
	if(!$IS_SORT && $limit * ($next + 1) < $all) {
		$count_next = $all - $limit * ($next + 1);
		if($count_next > $limit)
			$count_next = $limit;
		$MASS[] =
			'<tr class="over5 curP center blue" onclick="_spisok23next($(this),'.$ELEM['id'].','.($next + 1).')">'.
				'<td colspan="20">'.
					'<tt class="db '.($ELEM['num_3'] ? 'fs13 pt3 pb3' : 'fs14 pad5').'">'.
						'Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').
					'</tt>';
	}

	//открытие и закрытие таблицы
	$TABLE_BEGIN = '<table class="_stab'._dn(!$ELEM['num_3'], 'small').'">';
	$TABLE_END = '</table>';

	$BEGIN = !$next && !$IS_SORT ? $TABLE_BEGIN : '';
	$END = !$next && !$IS_SORT ? $TABLE_END : '';

	//включено условие сортировки
	if($IS_SORT) {
		if($ELEM['num_7'] > 1) {
			$child = array();
			foreach($spisok as $id => $r)
				$child[$r['parent_id']][$id] = $r;
			$TR = _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child);
		} else {
			$TR = '';
			foreach($MASS as $id => $sp)
				$TR .=
					'<li class="mt1" id="sp_'.$id.'">'.
						$TABLE_BEGIN.$sp.$TABLE_END.
					'</li>';
			$TR = '<ol>'.$TR.'</ol>';
		}
	} else
		$TR = implode('', $MASS);

	return
	$BEGIN.
	_spisok23th($ELEM, $next, $TABLE_BEGIN, $TABLE_END, $IS_SORT).
	$TR.
	$END;
}
function _spisok23th($ELEM, $next, $TABLE_BEGIN, $TABLE_END, $IS_SORT) {//отображение названий колонок
	if($next)
		return '';
	if(!$ELEM['num_5'])
		return '';

	$send = '';

	if($IS_SORT)
		$send = $TABLE_BEGIN;

	$send .= '<tr>';
	foreach($ELEM['vvv'] as $tr) {
		$txt = $tr['txt_7'];

		//выбор галочками
		if($tr['dialog_id'] == 91)
			$txt = _check(array(
				'attr_id' => 'sch'.$tr['id'].'_all',
				'value' => 0
			));

		$send .= '<th'._elemStyleWidth($tr).'>'.$txt;
	}

	if($IS_SORT)
		$send .= $TABLE_END;

	return $send;
}
function _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child, $parent_id=0) {//формирование табличного списка по уровням
	if(!$arr = @$child[$parent_id])
		return '';

	$send = '';
	foreach($arr as $id => $r)
		$send .=
			'<li class="mt1" id="sp_'.$id.'">'.
				$TABLE_BEGIN.$MASS[$id].$TABLE_END.
				(!empty($child[$id]) ? _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child, $id) : '').
			'</li>';
	return
		'<ol>'.$send.'</ol>';
}
function _element23_template_docx($ELEM, $u) {
	if(!$dialog_id = $ELEM['num_1'])
		return DEBUG ? '[23] Не указан список для вывода данных.' : '';
	if(!$DLG = _dialogQuery($dialog_id))
		return DEBUG ? '[23] Списка <b>'.$dialog_id.'</b> не существует.' : '';

	$limit = $ELEM['num_2'];
	$SC = $ELEM['num_6'] ? 'DESC' : 'ASC';
	$order = "`t1`.`id`";
	if($tab = _queryTN($DLG, 'dtime_add'))
		$order = "`".$tab."`.`dtime_add`";

	switch($ELEM['num_8']) {
		//по дате внесения
		default:
		case 6159: break;
		//по значению из диалога
		case 6160:
			if(!$col = _elemCol($ELEM['num_10']))
				break;
			if($tab = _queryTN($DLG, $col))
				$order = "`".$tab."`.`".$col."`";
			break;
		//ручная сортировка
		case 6161:
			$order = "`sort`";
			$limit = 1000;  //если включена сортировка, количество максимальное
			$SC = 'ASC';
			break;
	}

	$prm = array('unit_edit'=>$u);

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG).
					_40cond($ELEM, $ELEM['txt_2'], $prm)."
			ORDER BY ".$order." ".$SC."
			LIMIT ".$limit;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	//максимальная ширина таблицы. На основании её будет высчитываться ширина каждой колонки
	$w100 = 9900;


	$send =
	'<w:tbl>'.
		'<w:tblPr><w:tblW w:w="'.$w100.'" w:type="dxa"/>'.
			'<w:tblInd w:w="-34" w:type="dxa"/>'.
			'<w:tblBorders>'.
				'<w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
				'<w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
				'<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
				'<w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
				'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
				'<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>'.
			'</w:tblBorders>'.
			'<w:tblLayout w:type="fixed"/>'.
			'<w:tblLook w:val="01E0"/>'.
		'</w:tblPr>';
/*
		'<w:tblGrid>'.
			'<w:gridCol w:w="629"/>'.
			'<w:gridCol w:w="6743"/>'.
			'<w:gridCol w:w="654"/>'.
			'<w:gridCol w:w="905"/>'.
			'<w:gridCol w:w="992"/>'.
		'</w:tblGrid>';
*/
	//---=== ЗАГОЛОВКИ ===---

	$WNUM = 35;//ширина колонки для порядкового номера

	//получение общей ширины таблицы
	$WTR = $WNUM;
	foreach($ELEM['vvv'] as $tr)
		$WTR += $tr['width'];


	$send .=
		'<w:tr>'.
			'<w:trPr><w:trHeight w:val="295"/></w:trPr>';

	$w = round($WNUM/$WTR*$w100);
	$send .= elem23docxCell('№ п/п', $w, 'center', true);

	foreach($ELEM['vvv'] as $th) {
		$w = round($th['width']/$WTR*$w100);
		$send .= elem23docxCell($th['txt_7'], $w, 'center', true);

	}
	$send .= '</w:tr>';

	$num = 1;
	foreach($spisok as $u) {
		$send .=
			'<w:tr w:rsidR="00112243" w:rsidRPr="00FA01DF">'.
				'<w:trPr><w:trHeight w:val="295"/></w:trPr>';

		$w = round($WNUM/$WTR*$w100);
		$send .= elem23docxCell($num++, $w);

		$prm = _blockParam(array('unit_get'=>$u));

		foreach($ELEM['vvv'] as $td) {
			switch($td['txt_8']) {
				case 'center'; $align = 'center'; break;
				case 'r'; $align = 'right'; break;
				default: $align = 'left';
			}

			$txt = _elemPrint($td, $prm);
			$txt = _elemFormat($td, $prm, $txt);

			$w = round($td['width']/$WTR*$w100);
			$send .= elem23docxCell($txt, $w, $align);
		}

		$send .= '</w:tr>';
	}

	$send .= '</w:tbl>';

	return $send;
}
function elem23docxCell($txt, $w, $align='center', $th=false) {//формирование одной ячейки для таблицы формата DOCX
	return
	'<w:tc>'.
		'<w:tcPr>'.
			'<w:tcW w:w="'.$w.'" w:type="dxa"/>'.
			'<w:vAlign w:val="center"/>'.//позиция по вертикали
		'</w:tcPr>'.
		'<w:p>'.
			'<w:pPr>'.
				'<w:spacing w:after="0"/>'.
				'<w:jc w:val="'.$align.'"/>'.
				'<w:rPr>'.
					'<w:sz w:val="20"/>'.
					'<w:szCs w:val="20"/>'.
				'</w:rPr>'.
			'</w:pPr>'.
			'<w:r>'.
				'<w:rPr>'.
					'<w:sz w:val="20"/>'.
					'<w:szCs w:val="20"/>'.
			 ($th ? '<w:b/>' : '').
				'</w:rPr>'.
				'<w:t>'.strip_tags($txt).'</w:t>'.
			'</w:r>'.
		'</w:p>'.
	'</w:tc>';
}

/* ---=== НАСТРОЙКА ЯЧЕЕК ТАБЛИЦЫ [23] ===--- */
function PHP12_td_setup($prm) {//используется в диалоге [23]
	/*
		все действия через JS
	*/

	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка таблицы будет доступна после вставки списка в блок.');
	if(!$BL = _blockOne($prm['srce']['block_id']))
		return _emptyMin10('[23] Отсутствует исходный блок.');

	$ex = explode(' ', $BL['elem']['mar']);
	$w = $BL['width'] - $ex[1] - $ex[3];


	return '<div class="calc-div h25 line-b bg-efe">'.$w.'</div>';
}
function PHP12_td_setup_save($cmp, $val, $unit) {//сохранение данных ячеек таблицы
	/*
		$cmp  - компонент из диалога, отвечающий за настройку ячеек таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается таблица

		Данные колонок таблицы записываются в _element
		parent_id = $unit['id'] (ID элемента-таблицы [23])

		num_8 - флаг активности ячейки. Если 1 - ячейка настроена и активна
	*/

	if(empty($unit['id']))
		return;

	//Сброс флага активности ячейки
	$sql = "UPDATE `_element`
			SET `num_8`=0
			WHERE `parent_id`=".$unit['id'];
	query($sql);

	if(!empty($val) && is_array($val))
		foreach($val as $sort => $r) {
			if(!$id = _num($r['id']))
				continue;

			$sql = "UPDATE `_element`
					SET `num_8`=1,
						`width`="._num($r['width']).",
						`font`='".$r['font']."',
						`color`='".$r['color']."',
						`txt_7`='".addslashes(_txt($r['txt_7']))."',
						`txt_8`='".$r['txt_8']."',
						`sort`=".$sort."
					WHERE `parent_id`=".$unit['id']."
					  AND `id`=".$id;
			query($sql);
		}

	//удаление значений, которые были удалены при настройке
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND !`num_8`";
	if($ids = query_ids($sql)) {
		$sql = "DELETE FROM `_element` WHERE `id` IN (".$ids.")";
		query($sql);

		$sql = "DELETE FROM `_action` WHERE `element_id` IN (".$ids.")";
		query($sql);
	}

	_BE('elem_clear');
}
function PHP12_td_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}

