<?php

/* [79] Краткая сводка по списку */
function _element79_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, указывающего на список, по которому будет сводка [13]
		'num_2'   => _num($el['num_2']),//id элемента, указывающего на сумму [13]
		'txt_1'   => $el['txt_1'],      //текст, когда данных нет
		'txt_2'   => $el['txt_2'],      //id элемента (с учётом вложений), указывающий на имена группировки [13]
	) + _elementStruct($el);
}
function _element79_print($el) {
	if(!$SPEL = _elemOne($el['num_1']))
		return _emptyMinRed('[79] Не найден список');
	if(!$DLG = _dialogQuery($SPEL['num_1']))
		return _emptyMinRed('[79] Списка <b>'.$SPEL['num_1'].'</b> не существует.');

	if(!$GROUP_EL = _elemOne(_idsFirst($el['txt_2'])))
		return _emptyMinRed('[79] Отсутствует элемент для группировки');
	if(!$GROUP_DLG = _dialogQuery($GROUP_EL['num_1']))
		return _emptyMinRed('[79] Диалога <b>'.$GROUP_EL['num_1'].'</b> не существует.');
	if(!$GROUP_COL_NAME = _elemCol(_idsLast($el['txt_2'])))
		return _emptyMinRed('[79] Отсутствует колонка для имени груп');

	if(!$GROUP_COL = _elemCol(_idsFirst($el['txt_2'])))
		return _emptyMinRed('[79] Отсутствует колонка для группировки');

	//колонка для суммы
	$SUM_COL = _elemCol($el['num_2']);

	$sql = "SELECT
				`".$GROUP_COL."` `gid`,
				COUNT(*) `c`
				".($SUM_COL ? ",SUM(`".$SUM_COL."`) `sum`" : '')."
			FROM   "._queryFrom($DLG)."
			WHERE  "._spisokWhere($SPEL)."
			GROUP BY `".$GROUP_COL."`";
	if(!$arr = query_array($sql))
		return $el['txt_1'];

	//получение имён для групп
	$sql = "SELECT
				`t1`.`id`,
				`".$GROUP_COL_NAME."`
			FROM   "._queryFrom($GROUP_DLG)."
			WHERE  "._queryWhere($GROUP_DLG)."
			  AND `t1`.`id` IN ("._idsGet($arr, 'gid').")";
	$ass = query_ass($sql);

	$spisok = '';
	$cAll = 0;
	$sumAll = 0;
	foreach($arr as $r) {
		$spisok .=
		'<tr><td>'.@$ass[$r['gid']].
			'<td class="w70 center b color-555">'.$r['c'];
		$cAll += $r['c'];
	if($SUM_COL) {
			$spisok .= '<td class="w90 r color-555">'._sumSpace($r['sum']);
			$sumAll += $r['sum'];
		}
	}

	return
	'<table class="_stab small w100p">'.
		'<tr><th>'.
			'<th>Кол-во'.
($SUM_COL ? '<th>Сумма' : '').

		$spisok.

		'<tr><td class="r b">Всего:'.
			'<td class="center b">'.$cAll.
($SUM_COL ? '<td class="r b">'._sumSpace($sumAll) : '').
	'</table>';
}
function _element79filterUpd($send, $elem_spisok) {//обновление значения после применения фильтра
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=79
			  AND `num_1`=".$elem_spisok."
			LIMIT 1";
	if(!$el = query_assoc($sql))
		return $send;

	$send['upd'][] = array(
		'id' => $el['id'],
		'html' => _element79_print($el)
	);

	return $send;
}

function PHP12_elem79_group_setup($prm) {//настройка группировки
	if(!$u = $prm['unit_edit'])
		return _emptyMin('Настройка группировки будет доступна<br>после вставки элемента в блок.');
	if(!$elem_id = _num($u['num_1']))
		return _emptyMinRed('Не указан список');
	if(!$el = _elemOne($elem_id))
		return _emptyMinRed('Не получены данные списка');

	$val = array();
	$col = $prm['el12']['col'];
	if(!empty($u[$col])) {
		$val = htmlspecialchars_decode($u[$col]);
		$val = json_decode($val, true);
	}

	$groupTR = '';
	switch($el['dialog_id']) {
		case 14:
		case 23:
			$groupTR = _elem79_group_tr($el['num_1'], @$val[0][0], @$val[0][1]);
			break;
		case 88:
			$V = json_decode($el['txt_2'], true);

			if(empty($V['spv']))
				return _emptyRed('Таблица из нескольких списков не настроена.');

			foreach($V['spv'] as $n => $spv)
				$groupTR .= _elem79_group_tr($spv['dialog_id'], @$val[$n][0], @$val[$n][1]);
			break;
		default:
			return _emptyMinRed('Указанный элемент не является списком');
	}

	return '<table class="w100p">'.$groupTR.'</table>';
}
function _elem79_group_tr($dlg_id, $group_ids=0, $sum_ids=0) {
	if(!$DLG = _dialogQuery($dlg_id))
		return '';

	$group_name = $group_ids ?  _elemIdsTitle($group_ids) : '';
	$sum_name = $sum_ids ?  _elemIdsTitle($sum_ids) : '';

	return
	'<tr class="over1" data-dlg="'.$dlg_id.'">'.
		'<td class="w150 pad5 fs14 b color-555">'.$DLG['name'].':'.
		'<td class="r w175 pad5">'.
			'<div class="_selem dib prel w150 bg-fff over1">'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del-red pl pabs'._dn($group_ids).'"></div>'.
				'<input type="text" readonly class="inp79 curP w100p color-pay" placeholder="не указана" val="'.$group_ids.'" value="'.$group_name.'" />'.
			'</div>'.
		'<td class="r pad5">'.
			'<div class="_selem dib prel w150 bg-fff over1">'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del-red pl pabs'._dn($sum_ids).'"></div>'.
				'<input type="text" readonly class="inp79 curP w100p color-pay" placeholder="путь не указан" val="'.$sum_ids.'" value="'.$sum_name.'" />'.
			'</div>';
}




