<?php

/* [23] Список-таблица */
function _element23_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2'   => _num($el['num_2']),//длина (количество строк, выводимых за один раз)
		'txt_1'   => $el['txt_1'],	    //сообщение пустого запроса
		'txt_2'   => $el['txt_2'],	    //[40] условия отображения
		'txt_3'   => $el['txt_3'],	    //[12] содержание - ids элементов
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

		'num_9'   => _num($el['num_9']), //включение отображения сообщения пустого запроса
		'num_10'  => _num($el['num_10']),//выбранное значение для порядка (при num_8=6160)
		'txt_4'  => $el['txt_4']         //[13] динамическая окраска строки
	) + _elementStruct($el);
}
function _element23_vvv($el) {
	if(!$ids = _ids($el['txt_3'], 'arr'))
		return array();

	$send = array();
	foreach($ids as $elem_id)
		if($ell = _elemOne($elem_id))
			$send[] = $ell;

	return $send;
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
	$order = _queryCol_id($DLG);
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
	$spisok = DB1::arr_cache($sql);

	//добавление записи, если был быстрый поиск по номеру
	if(!$next)
		$spisok = _elem7num23($ELEM, $spisok);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);
	//вставка картинок
	$spisok = _spisokImage($spisok);

	if(!$vvv = _element('vvv', $ELEM))
		return _emptyRed('Таблица не настроена.');

	$MASS0 = array();
	$ov = $ELEM['num_4'] ? ' over1' : '';
	foreach($spisok as $uid => $u) {
		$bg = _elem23bg($ELEM, $u);
		$prm = _blockParam(array('unit_get'=>$u));
		$mu = array(
			'pid' => 0,
			'tr' => '<tr class="tr-unit'.$ov.'"'.$bg.' val="'.$uid.'">',
			'td' => array()
		);
		if(isset($u['parent_id']))
			$mu['pid'] = $u['parent_id'];
		elseif(isset($u['sort_pid']))
			$mu['pid'] = $u['sort_pid'];
		foreach($vvv as $td) {
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

				$txt = _elemFormat($td, $prm, $txt);//[23] форматирование для ячеек таблицы
			}

			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);
			$cls = $cls ? ' class="'.$cls.'"' : '';

			$mu['td'][] = array(
				'cls' => $cls,
				'w' => _elemStyleWidth($td, true),
				'txt' => $txt
			);
		}
		$MASS0[$uid] = $mu;
	}

	//открытие и закрытие таблицы
	$TABLE_BEGIN = '<table class="_stab'._dn(!$ELEM['num_3'], 'small').'">';
	$TABLE_END = '</table>';

	$BEGIN = !$next && !$IS_SORT ? $TABLE_BEGIN : '';
	$END = !$next && !$IS_SORT ? $TABLE_END : '';

	return
	$BEGIN.
	_spisok23th($ELEM, $next, $TABLE_BEGIN, $TABLE_END, $IS_SORT).
	_spisok23tr($ELEM, $TABLE_BEGIN, $TABLE_END, $MASS0, $IS_SORT).
	_spisok23next($ELEM, $IS_SORT, $limit, $next, $all).
	$END;
}
function _spisok23th($ELEM, $next, $TABLE_BEGIN, $TABLE_END, $IS_SORT) {//отображение названий колонок
	if($next)
		return '';
	if(!$ELEM['num_5'])
		return '';

	$send = '<tr>';
	foreach(_element('vvv', $ELEM) as $tr)
		$send .= '<th'._elemStyleWidth($tr).'>'._spisok23thCHK($tr, $tr['txt_7']);

	if(!$IS_SORT)
		return $send;

	return $TABLE_BEGIN.$send.$TABLE_END;
}
function _spisok23thCHK($el, $txt) {//вставка галочки в заголовок, которая будет выбирать все галочки
	if($el['dialog_id'] == 91)
		return _check(array(
			'attr_id' => 'sch'.$el['id'].'_all',
			'value' => 0
		));

	if($el['dialog_id'] == 44) {
		if(!$vvv = _element('vvv', $el))
			return $txt;

		foreach($vvv as $r) {
			if($r['type'] != 'el')
				continue;
			if(!$ell = _elemOne($r['id']))
				continue;
			if($ell['dialog_id'] != 91)
				continue;

			return _check(array(
				'attr_id' => 'sch'.$r['id'].'_all',
				'value' => 0
			));
		}
	}

	return $txt;
}
function _spisok23tr($ELEM, $TABLE_BEGIN, $TABLE_END, $MASS0, $IS_SORT) {//вывод содержания таблицы
	if(!$IS_SORT) {
		$send = '';
		foreach($MASS0 as $r)
			$send .= _spisok23tru($r);
		return $send;
	}

	if($ELEM['num_7'] > 1) {
		$child = array();
		foreach($MASS0 as $id => $r) {
			$pid = $r['pid'];
			$child[$pid][$id] = $r;
		}
		return _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS0, $child);
	}

	$send = '';
	foreach($MASS0 as $id => $sp)
		$send .=
			'<li class="mt1" id="sp_'.$id.'">'.
				$TABLE_BEGIN._spisok23tru($sp).$TABLE_END.
			'</li>';
	return '<ol>'.$send.'</ol>';
}
function _spisok23tru($r, $level=0) {//вывод одной строки содержания
	$send = $r['tr'];
	foreach($r['td'] as $n => $td) {
		if($n == 1)
			$td['w'] -= $level * 30;
		$send .= '<td'.$td['cls'].' style="width:'.$td['w'].'px"'.'>'.$td['txt'];
	}

	return $send;
}
function _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS0, $child, $parent_id=0, $level=0) {//формирование табличного списка по уровням
	if(!$arr = @$child[$parent_id])
		return '';

	$send = '';
	foreach($arr as $id => $r)
		$send .=
		'<li class="mt1" id="sp_'.$id.'">'.
			$TABLE_BEGIN._spisok23tru($r, $level).$TABLE_END.
			(!empty($child[$id]) ? _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS0, $child, $id, $level+1) : '').
		'</li>';

	return '<ol>'.$send.'</ol>';
}
function _spisok23next($ELEM, $IS_SORT, $limit, $next, $all) {//кнопка догрузки списка
	if($IS_SORT)
		return '';
	if($limit * ($next + 1) >= $all)
		return '';

	$count_next = $all - $limit * ($next + 1);
	if($count_next > $limit)
		$count_next = $limit;

	return
	'<tr class="over5 curP center clr15" onclick="_spisok23next($(this),'.$ELEM['id'].','.($next + 1).')">'.
		'<td colspan="20">'.
			'<tt class="db '.($ELEM['num_3'] ? 'fs13 pt3 pb3' : 'fs14 pad5').'">'.
				'Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').
			'</tt>';
}
function _elem23bg($el, $u) {//динамическая окраска строки
	if(!$ids = _ids($el['txt_4'], 'arr'))
		return '';

	$bg = $u;
	foreach($ids as $id) {
		if(!$col = _elemCol($id))
			return '';
		if(!isset($bg[$col]))
			return '';

		$bg = $bg[$col];
	}

	return ' style="background-color:'.$bg.'"';
}
function _element23_template_docx($ELEM, $u) {
	if(!$dialog_id = $ELEM['num_1'])
		return DEBUG ? '[23] Не указан список для вывода данных.' : '';
	if(!$DLG = _dialogQuery($dialog_id))
		return DEBUG ? '[23] Списка <b>'.$dialog_id.'</b> не существует.' : '';

	$limit = $ELEM['num_2'];
	$SC = $ELEM['num_6'] ? 'DESC' : 'ASC';
	$order = _queryCol_id($DLG);
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
	$spisok = DB1::arr($sql);

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

	$vvv = _element('vvv', $ELEM);

	//получение общей ширины таблицы
	$WTR = $WNUM;
	foreach($vvv as $tr)
		$WTR += $tr['width'];


	$send .=
		'<w:tr>'.
			'<w:trPr><w:trHeight w:val="295"/></w:trPr>';

	$w = round($WNUM/$WTR*$w100);
	$send .= elem23docxCell('№ п/п', $w, 'center', true);

	foreach($vvv as $th) {
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

		foreach($vvv as $td) {
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
function _element23_title($el) {
	return 'Список-таблица '._dialogParam($el['num_1'], 'name');
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
	if(!$el = _elemOne($BL['elem_id']))
		return _emptyMin10('[23] Отсутствует элемент.');

	return '<div class="calc-div h25 line-b bg5">'._elemWidth($el).'</div>';
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
	if(!$col = $cmp['col'])
		return;

	//идентификаторы, которые удалять не нужно
	$ids = 0;

	if(!empty($val) && is_array($val))
		foreach($val as $sort => $r) {
			if(!$id = _num($r['id']))
				continue;

			$sql = "UPDATE `_element`
					SET `width`="._num($r['width']).",
						`font`='".$r['font']."',
						`color`='".$r['color']."',
						`txt_7`='".addslashes(_txt($r['txt_7']))."',
						`txt_8`='".$r['txt_8']."',
						`sort`=".$sort."
					WHERE `parent_id`=".$unit['id']."
					  AND `id`=".$id;
			DB1::query($sql);

			$ids .= ','.$id;
		}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `id` NOT IN (".$ids.")";
	DB1::query($sql);

	//прописывание идентификаторов ячеек
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			ORDER BY `sort`";
	$ids = DB1::ids($sql);

	$sql = "UPDATE `_element`
			SET `".$col."`='".($ids ? $ids : '')."'
			WHERE `id`=".$unit['id'];
	DB1::query($sql);

	_BE('elem_clear');
}
function PHP12_td_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();

	return _element('vvv', $el);
}

