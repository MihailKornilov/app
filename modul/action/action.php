<?php

function _blockAction201($bl, $prm) {//установка исходного отображения блока на основании действия
	global $G_ACT;

	//расстановка выполнения действий в порядке добавления
	//первым будет выполняться действие, которое было добавлено первым
	$action = array();
	foreach($G_ACT['act'] as $id => $r) {
		if($r['dialog_id'] != 201)
			continue;

		if(!$ass = _idsAss($r['target_ids']))
			continue;
		if(!isset($ass[$bl['id']]))
			continue;
		if(!$el = _elemOne($r['element_id']))
			continue;

		$action[$id][] = $r['element_id'];
	}

	if(empty($action))
		return $bl;

	ksort($action);

	$hidden = $bl['hidden'];

	foreach($action as $id => $elm) {
		$r = $G_ACT['act'][$id];
		foreach($elm as $elem_id) {
			if(!$el = _elemOne($elem_id))
				continue;

			if($el['dialog_id'] != 1//галочка
			&& $el['dialog_id'] != 6//select страниц
			&& $el['dialog_id'] != 7//быстрый поиск
			&& $el['dialog_id'] != 16//radio
			&& $el['dialog_id'] != 17//select
			&& $el['dialog_id'] != 18//dropdown
			&& $el['dialog_id'] != 24//Выпадающее поле - выбор списка
			&& $el['dialog_id'] != 29//Выпадающее поле - выбор записи из другого списка
			&& $el['dialog_id'] != 59//Связка с другим списком через кнопку
			&& $el['dialog_id'] != 62//Фильтр: галочка
			&& $el['dialog_id'] != 75//Фильтр: фронтальное меню
			) continue;

			if(_filterIgnore($el)) {
				$bl['hidden'] = true;
				continue;
			}

			if(!$r['initial_id'])
				continue;

			switch($r['apply_id']) {
				default:
				//скрыть
				case 2783: $hidden = true; break;
				//показать
				case 2784: $hidden = false; break;
			}

			//получение выбранного значения при редактировании записи
			$v = isset($el['def']) ? $el['def'] : 0;
			if($u = $prm['unit_edit'])
				if($col = _elemCol($el))
					if(isset($u[$col])) {
						$v = $u[$col];
						if(is_array($v))
							$v = _num(@$v['id']);
					}

			//фильтры
			switch($el['dialog_id']) {
				case 7: $v = _filter('vv', $el); break;
				case 62: $v = _filter('vv', $el, $el['num_3']); break;
				case 75: $v = _filter('vv', $el, 0); break;
			}

			if($v) {//если галочка установлена
				if($r['initial_id'] != -2 && $r['initial_id'] != $v)//действие при установленной галочке
					if($r['revers'])
						$hidden = !$hidden;
					else
						continue;

			} else  //если галочка снята
				if($r['initial_id'] != -1)//действие при снятой галочке
					if($r['revers'])
						$hidden = !$hidden;
					else
						continue;
		}
	}

	$bl['hidden'] = $hidden;

	return $bl;
}
function _blockAction209($bl, $prm, $txt='') {//установка ранее выбранного значения в блок
	global $G_ACT;

	if($el = _elemOne($bl['elem_id']))
		if($el['dialog_id'] != 10)
			return $txt;

	foreach($G_ACT['act'] as $r) {
		if($r['dialog_id'] != 209)
			continue;

		$ass = _idsAss($r['target_ids']);
		if(!isset($ass[$bl['id']]))
			continue;

		if(!$el = _elemOne($r['element_id']))
			return $txt;

		//пока только для элемента [29]
		if($el['dialog_id'] != 29)
			return $txt;

		$v = _40condVcopy($el['num_6']);
		if(!$el['num_10'])
			$v = _elemPrintV($el, $prm, $v);
		$v = _elem29PageSel($el['num_1'], $v);
		$v = _elem29DialogSel($prm, $v);
		$v = _elem29UserSel($el, $prm, $v);

		if(!$v)
			return $txt;

		if(!$spisok = _element29_vvv($el, $prm))
			return $txt;

		foreach($spisok as $sp) {
			if($sp['id'] != $v)
				continue;
			if(!isset($sp['sp']))
				return $txt;

			return _elemUids(_idsLast($r['v1']), $sp['sp']);
		}

		return $txt;
	}


	return $txt;
}
function _blockAction211($bl) {//БЛОК: скрытие/показ блоков
	global $G_ACT;

	foreach($G_ACT['act'] as $r) {
		if($r['dialog_id'] != 211)
			continue;
		if(!$r['revers'])
			continue;

		$ass = _idsAss($r['target_ids']);
		if(!isset($ass[$bl['id']]))
			continue;

		if($r['v1']) {
			if(isset($_COOKIE['ACT211_'.$bl['id']])) {
				$bl['hidden'] = !_bool($_COOKIE['ACT211_'.$bl['id']]);
				return $bl;
			}
		}

		switch($r['apply_id']) {
			default:
			//скрыть
			case 3166: break;
			//показать
			case 3167: $bl['hidden'] = true; break;
		}
		return $bl;
	}

	return $bl;
}
function _blockAction231($bl, $prm) {//условия отображения блока: скрытие
	if($bl['hidden'])
		return $bl;
	if(!$u = $prm['unit_get'])
		return $bl;
	if(!$action =  _BE('block_one_action', $bl['id']))
		return $bl;

	foreach($action as $act) {
		if($act['dialog_id'] != 231)
			continue;
		if(!$F = _decode($act['filter']))
			return $bl;

		$bl['hidden'] = _elem40res($F, $u);
		return $bl;
	}

	return $bl;
}
function _blockAction232($bl, $prm, $bg='') {//условия отображения блока: подмена заливки
	if(!$u = $prm['unit_get'])
		return $bg;
	if(!$action =  _BE('block_one_action', $bl['id']))
		return $bg;

	foreach($action as $act) {
		if($act['dialog_id'] != 232)
			continue;
		if(!$F = _decode($act['filter']))
			continue;
		if(!_elem40res($F, $u))
			continue;

		return $act['v1'];
	}

	return $bg;
}

function _elemAction223($el, $prm, $txt) {//подсказка на тёмном фоне
	if(!$action = _BE('elem_one_action', $el['id']))
		return $txt;

	foreach($action as $act) {
		if($act['dialog_id'] != 223)
			continue;
		if(preg_match('/data-tool/', $txt))
			return $txt;

		if(!$tool = _txt($act['v1'])) {
			if(!$u = $prm['unit_get'])
				return $txt;
			if(!$tool = _elemUids($act['target_ids'], $u))
				return $txt;
		}

		if(preg_match('/class="/', $txt))
			return preg_replace('/class="/', 'data-tool="'._br($tool).'" class="tool ', $txt, 1);

		return '<span class="inhr tool" data-tool="'._br($tool).'">'.$txt.'</a>';
	}

	return $txt;
}
function _elemAction229Hint($el, $prm, $txt) {//выплывающая подсказка
	if(!empty($prm['blk_setup']))
		return $txt;
	if(!$hint = _BE('hint_elem_one', $el['id']))
		return $txt;
	if(preg_match('/data-hint-id/', $txt))
		return $txt;

	$prm['td_no_end'] = 1;
	$hint['msg'] = _blockHtml('hint', $hint['id'], $prm);

	if(preg_match('/class="/', $txt))
		return preg_replace('/class="/', 'data-hint-id="'._hintMassPush($hint).'" class="hint-on ', $txt, 1);

	return '<span class="inhr hint-on" data-hint-id="'._hintMassPush($hint).'">'.$txt.'</span>';
}
function _elemAction241($el, $prm, $txt) {//подмена текста
	if(!$action = _BE('elem_one_action', $el['id']))
		return $txt;
	if(!$u = $prm['unit_get'])
		return $txt;

	foreach($action as $act) {
		if($act['dialog_id'] != 241)
				continue;
		if(!$F = _decode($act['filter']))
			return $txt;
		if(!_elem40res($F, $u))
			return $txt;

		return $act['v1'];
	}

	return $txt;
}
function _elemAction242($el, $prm) {//подмена цвета
	$color = empty($el['color']) ? '' : $el['color'];

	if(empty($el['id']))
		return $color;
	if(!$action = _BE('elem_one_action', $el['id']))
		return $color;
	if(!$u = $prm['unit_get'])
		return $color;

	foreach($action as $act) {
		if($act['dialog_id'] != 242)
			continue;
		if(!$F = _decode($act['filter']))
			continue;
		if(!_elem40res($F, $u))
			continue;

		return $act['v1'];
	}

	return $color;
}
function _elemAction243($el, $txt) {//Формат для чисел
	if($el['dialog_id'] == 44)
		return $txt;
	if(is_string($txt) && !preg_match(REGEXP_CENA_MINUS, $txt))
		return $txt;
	if(!$action = _BE('elem_one_action', $el['id'])) {
		if($el['dialog_id'] == 11)
			if(!$el = _elemOne(_idsLast($el['txt_2'])))
				return $txt;

		switch($el['dialog_id']) {
			case 8:
				if($el['num_1'] == 33)//цифры и числа
					return round($txt, 10);
				break;
			case 27:
			case 54:
			case 55:
				return round($txt, 10);
		}

		return $txt;
	}

	foreach($action as $act) {
		if($act['dialog_id'] != 243)
			continue;

		//не показывать при нуле
		if($act['apply_id'] && !round($txt, 10))
			return '';

		//пробелы в больших числах
		if($act['effect_id'])
			$txt = _sumSpace($txt, $act['revers'], $act['v1']);
		else {
			//не показывать нули в дробной части
			if(!$act['revers'])
				$txt = round($txt, 10);
			$txt = str_replace('.', $act['v1'], $txt);
		}

		return $txt;
	}

	return round($txt, 10);
}
function _elemAction244($el, $prm) {//скрытие элемента
	if(!$action = _BE('elem_one_action', $el['id']))
		return false;
	if(!$u = $prm['unit_get'])
		return false;

	$send = false;

	foreach($action as $act) {
		if($act['dialog_id'] != 244)
			continue;
		if(!$F = _decode($act['filter']))
			continue;

		if(_elem40res($F, $u))
			$send = true;
	}

	return $send;
}
function _elemAction245($el, $txt, $skip224=false) {//Формат для текста
	if(!$action = _BE('elem_one_action', $el['id']))
		return $txt;

	//если присутствует внешняя ссылка, выход. Обрезка будет далее в ссылке
	if($skip224)
		foreach($action as $act)
			if($act['dialog_id'] == 224)
				return $txt;

	foreach($action as $act) {
		if($act['dialog_id'] != 245)
			continue;
		if(!$len = $act['apply_id'])
			continue;
		if($len >= mb_strlen($txt))
			continue;

		$txt = mb_substr($txt, 0, $len).'...';
	}

	return $txt;
}





/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
function PHP12_action_list($prm) {
	//текущий диалог для обновления списка действий после редактирования
	$bl = _blockOne($prm['el12']['block_id']);
	$dss = $bl['obj_id'];

	switch($dss) {
		//действия для элемента
		case 200:
		case 220:
		case 240:
			if($block_id = _num($prm['srce']['block_id'])) {
				if(!$BL = _blockOne($block_id))
					return _emptyMin('Блока id'.$block_id.' не существует.');
				$elem_id = $BL['elem_id'];
			} elseif(!$elem_id = _num($prm['srce']['element_id']))
				return _emptyMin('Отсутствует ID элемента.');
			$where = "`element_id`=".$elem_id;
			break;
		//действия для блока
		case 210:
		case 230:
			if(!$block_id = _num($prm['srce']['block_id']))
				return _emptyMin('Отсутствует ID исходного блока.');
			if(!$BL = _blockOne($block_id))
				return _emptyMin('Блока id'.$block_id.' не существует.');
			$where = "`block_id`=".$block_id;
			break;
		default: return _emptyMin('Неизвестный диалог для настройки действий.');
	}

	$sql = "SELECT *
			FROM `_action`
			WHERE ".$where."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return _emptyMin('Действий не назначено.');

	$spisok = '';
	foreach($arr as $id => $r) {
		if(!$block_id = $r['block_id']) {
			$el = _elemOne($r['element_id']);
			$block_id = _num(@$el['block_id']);
		}
		$val = array();
		$val[] = 'dialog_id:'.$r['dialog_id'];
		$val[] = 'edit_id:'.$id;
		if($block_id)
			$val[] = 'block_id:'.$block_id;
		if($r['element_id'])
			$val[] = 'element_id:'.$r['element_id'];
		$val[] = 'dss:'.$dss;
		$spisok .=
			'<dd val="'.$id.'">'.
			'<table class="w100p bs5 bor1 bg4 over2 mb5 curD">'.
				'<tr>'.
					'<td class="w25 top">'.
						'<div class="icon icon-move-y pl"></div>'.
					'<td><div class="fs15 clr9">'.
							_dialogParam($r['dialog_id'], 'name').
					 (SA ? '<span class="fs15 clr2 ml10">['.$r['dialog_id'].']</span>' : '').
						'</div>'.
						'<div class="mt3 ml10">'.
							_action201info($r).
							_action202info($r).
							_action205info($r).
							_action206info($r).
							_action207info($r).
							_action208info($r).
							_action209info($r).

							_action211info($r).
							_action212info($r).
							_action213info($r).
							_action214info($r).
							_action215info($r).
							_action216info($r).
							_action217info($r).
							_action218info($r).
							_action219info($r).

							PHP12_action_221($r).
							PHP12_action_222($r).
							PHP12_action_223($r).
							PHP12_action_224($r).
						'</div>'.
					'<td class="w50 r top">'.
						'<div val="'.implode(',', $val).'"'.
							' class="icon icon-edit pl dialog-open tool"'.
							' data-tool="Настроить действие">'.
						'</div>'.
						'<div class="icon icon-del pl ml5 dialog-open tool" data-tool="Удалить" val="dialog_id:'.$r['dialog_id'].',del_id:'.$id.',dss:'.$dss.'"></div>'.
			'</table>'.
			'</dd>';
	}

	return '<dl>'.$spisok.'</dl>';
}
function _action201info($act) {//ЭЛЕМЕНТ: скрытие/показ блоков
/*
	apply_id: Действие с блоками: скрыть|показать
	filter: Фильтр
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
	revers: Обратное действие
	v1: запоминать состояние
	target_ids: Блоки, на которые происходит воздействие
	effect_id: Эффект
*/

	if($act['dialog_id'] != 201)
		return '';

	$c = count(_ids($act['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = 'выбрано <b class="clr13">'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	$effect = '';
	if($act['effect_id'])
		$effect =
			'<div class="fs12 clr1 mt2">'.
				'Эффект: '.
				'<span class="fs12 clr13">'._element('v_get', 2788, $act['effect_id']).'</span>'.
			'</div>';

	$revers = $act['revers'] ? '<div class="fs11 i clr9 mt2">Применяется обратное действие</div>' : '';

	return
	'<span class="clr1">'._element('v_get', 2782, $act['apply_id']).'</span> '.
	'<b>'.$target.'</b>'.
	'<br>'.
	'<span class="clr1">если</span> '.$initial.
	$effect.
	$revers;
}
function _action202info($act) {//ЭЛЕМЕНТ: установка значения элементу
/*
	initial_id: [85] Условие для совершения действия:
	target_ids: элемент-получатель, на которое происходит воздействие
	apply_id: применяемое действие (устанавливаемое значение)
	v1: устанавливаемое значение вручную
	revers: Обратное действие
*/

	if($act['dialog_id'] != 202)
		return '';

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = '<span class="clr11">выбрано</span> '.
					   '<b>'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	$apply = '-';
	switch($act['apply_id']) {
		case -1: $apply = '<b class="clr8">сбросить значение</b>'; break;
		default:
			if(!$el = _elemOne($act['target_ids']))
				break;

			$apply = '<span class="clr11">установить</span> ';
			if($act['apply_id'])
				$apply .= '<b>'._element('v_get', $el, $act['apply_id']).'</b>';
			elseif(strlen($act['v1']))
				$apply .= '<b>'.$act['v1'].'</b>';
			else
				$apply = _msgRed('Устанавливаемое значение не настроено');
	}


	$revers = $act['revers'] ? '<div class="fs11 i clr9 mt2">Применяется обратное действие</div>' : '';

	return
	'<span class="clr1">Если</span> '.$initial.
	'<div>'.
		'<span class="clr1">то элементу</span> '.
		'<u>'._elemIdsTitle($act['target_ids']).'</u> '.
		$apply.
	'<div>'.
	$revers;
}
function _action205info($act) {//ЭЛЕМЕНТ: открытие диалога
/*
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
					id: конкретное значение
	target_ids: id диалога
	apply_id:  элемент передаёт данные записи для отображения
	effect_id: элемент передаёт данные записи для редактирования
*/

	if($act['dialog_id'] != 205)
		return '';

	if(!$DLG = _dialogQuery($act['target_ids']))
		return '<div class="clr5">не получены данные диалога ['.$act['target_ids'].']</div>';

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = 'выбрано <b class="clr13">'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	$get  = $act['apply_id']  ? '<div class="fs11 i clr8 mt2">Элемент передаёт данные записи для отображения</div>' : '';
	$edit = $act['effect_id'] ? '<div class="fs11 i clr8 mt2">Элемент передаёт данные записи для редактирования</div>' : '';

	return
	'<span class="clr1">Если</span> '.$initial.
	'<br>'.
	'<span class="clr1">открыть диалог </span> <b>'.$DLG['name'].'</b>'.
	$get.
	$edit;
}
function _action206info($act) {//ЭЛЕМЕНТ: установка фокуса на элемент
/*
	initial_id: [85] Условие для совершения действия:
					-1: значение сброшено
					-2: выбрано любое значение
					id: конкретное значение
	target_ids: [13] элемент, на который устанавливается фокус
*/

	if($act['dialog_id'] != 206)
		return '';

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = 'выбрано <b class="clr13">'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	return
	'<span class="clr1">Если</span> '.$initial.
	'<br>'.
	'<span class="clr1">установить фокус на элемент</span> <u>'._elemIdsTitle($act['target_ids']).'</u>';
}
function _action207info($act) {//ЭЛЕМЕНТ: открытие документа
/*
	initial_id: [85] Условие для совершения действия:
	target_ids: [26] документ
*/

	if($act['dialog_id'] != 207)
		return '';

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = 'выбрано <b class="clr13">'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	$docName = _msgRed('не получено имя документа');
	if(!$doc_id = _num($act['target_ids']))
		$docName = '<span class="clr5">документ не указан<span>';
	elseif($el = _elemOne(3547))
		$docName = _element('v_get', $el, $doc_id);

	return
	'<span class="clr1">Если</span> '.$initial.
	'<br>'.
	'<span class="clr1">открыть документ</span> <b>'.$docName.'</b>';
}
function _action208info($act) {//ЭЛЕМЕНТ: формула
/*
	v1: [12] функция
			znak
			elem_id
			v
	apply_id: [13] элемент-получатель
	effect_id: [35] округление результата 0-3 знаков
*/

	if($act['dialog_id'] != 208)
		return '';

	$F = array();
	foreach(_decode($act['v1']) as $n => $r) {
		if($n || !$n && $r['znak'] == '-')
			$F[] = $r['znak'];
		$F[] = $r['elem_id'] ? _elemIdsTitle($r['elem_id']) : $r['v'];
	}

	return
	'<span class="clr1">Применить формулу </span> <span class="clr13">'.implode(' ', $F).'</span>'.
	'<br>'.
	'<span class="clr1">к элементу</span> <b>'._elemIdsTitle($act['apply_id']).'</b>';
}
function _action209info($act) {//ЭЛЕМЕНТ: вставка значения в блок
/*
	initial_id: [85] Условие для совершения действия:
	v1: [13] вставляемое значение
	target_ids: [49] блок-получатель
*/

	if($act['dialog_id'] != 209)
		return '';

	$initial = '-';
	switch($act['initial_id']) {
		case -1: $initial = '<b class="clr8">значение сброшено</b>'; break;
		case -2: $initial = '<b class="clr11">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($act['element_id']))
				break;

			$initial = 'выбрано <b class="clr13">'._element('v_get', $el, $act['initial_id']).'</b>';
	}

	return
	'<span class="clr1">Если</span> '.$initial.
	'<br>'.
	'<span class="clr1">вставить</span> <b>'._elemIdsTitle($act['v1']).'</b>';
}

function _action211info($act) {//БЛОК: скрытие/показ блоков
/*
	apply_id: Действие с блоками: скрыть|показать
	revers: Обратное действие
	target_ids: [49] Блоки, на которые происходит воздействие
	effect_id: Эффект
*/

	if($act['dialog_id'] != 211)
		return '';

	$c = count(_ids($act['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');

	$effect = '';
	if($act['effect_id'])
		$effect =
			'<div class="fs12 clr1 mt2">'.
				'Эффект: '.
				'<span class="fs12 clr13">'._element('v_get', 3170, $act['effect_id']).'</span>'.
			'</div>';

	$revers = $act['revers'] ? '<div class="fs11 i clr9 mt2">Применяется обратное действие</div>' : '';
	$v1 = $act['v1'] ? '<div class="fs11 i clr9 mt2">Запоминать состояние</div>' : '';

	return
	'<div class="b">'._element('v_get', 3165, $act['apply_id']).' '.$target.'</div>'.
	$effect.
	$revers.
	$v1;
}
function _action212info($act) {//БЛОК: Установка значения элементу
/*
	target_ids: [13] элемент-получатель, на которое происходит воздействие
	apply_id: [85] применяемое действие (устанавливаемое значение)
*/

	if($act['dialog_id'] != 212)
		return '';
	if(!$elem_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id элемента</div>';
	if(!$el = _elemOne($elem_id))
		return '<div class="clr5">Элемента не существует</div>';

	return
	'<span class="clr1">Элементу</span> <b>'._elemIdsTitle($elem_id).'</b>'.
	'<br>'.
	'<span class="clr1">применить:</span> <b class="clr13">'._element('v_get', $el, $act['apply_id']).'</b>';
}
function _action213info($act) {//БЛОК: блокировка элементов
/*
	apply_id: [16] применяемое действие
	target_ids: [13] элементы-получатели, на которые происходит воздействие
*/

	if($act['dialog_id'] != 213)
		return '';
	if(!$ids = _ids($act['target_ids'], 'arr'))
		return '<div class="clr5">Отсутствует элементы для блокировки</div>';

	$elem = array();
	foreach($ids as $id)
		$elem[] = '<b>'._elemIdsTitle($id).'</b>';

	return
	'<span class="clr1">'.
		_element('v_get', 3364, $act['apply_id']).' '.
		'элемент'.(count($elem) > 1 ? 'ы' : '').
	'</span> '.
	implode(', ', $elem);
}
function _action214info($act) {//БЛОК: переход на страницу
/*
	target_ids: [6] страница
	apply_id: [1] Блок передаёт данные записи
*/
	if($act['dialog_id'] != 214)
		return '';
	if(!$page_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="clr5">Страницы не существует</div>';

	return
	'<span class="clr1">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>'.
	($act['apply_id'] ? '<div class="clr9 i fs12 mt3">Блок передаёт данные записи</div>' : '');
}
function _action215info($act) {//БЛОК: открытие диалога
/*
	filter: дополнительные условия
	target_ids: [38] диалог
	apply_id: [1] Блок передаёт данные записи для отображения
	effect_id: [1] Блок передаёт данные записи для редактирования
	revers: [1] Блок передаёт данные записи для удаления
*/
	if($act['dialog_id'] != 215)
		return '';
	if(!$dlg_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id диалога</div>';
	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="clr5">Диалога не существует</div>';

	return
	'<span class="clr1">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>'.
	($act['apply_id'] ? '<div class="clr9 i fs12 mt3">Блок передаёт данные записи для отображения</div>' : '').
	($act['effect_id'] ? '<div class="clr9 i fs12 mt3">Блок передаёт данные записи для редактирования</div>' : '').
	($act['revers'] ? '<div class="clr9 i fs12 mt3">Блок передаёт данные записи для удаления</div>' : '');
}
function _action216info($act) {//БЛОК: Установка фокуса на элемент
/*
	target_ids: элемент, на который устанавливается фокус
*/

	if($act['dialog_id'] != 216)
		return '';
	if(!$elem_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id элемента</div>';

	return '<span class="clr1">Элемент:</span> <b>'._element('title', $elem_id).'</b>';
}
function _action217info($act) {//БЛОК: открытие документа
/*
	target_ids: [26] шаблон документа
*/

	if($act['dialog_id'] != 217)
		return '';
	if(!$elem_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id элемента</div>';

	$docName = _msgRed('не получено имя документа');
	if(!$doc_id = _num($act['target_ids']))
		$docName = '<span class="clr5">документ не указан<span>';
	elseif($el = _elemOne(3737))
		$docName = _element('v_get', $el, $doc_id);

	return '<span class="clr1">Документ:</span> <b>'.$docName.'</b>';
}
function _action218info($act) {//БЛОК: принимает данные записи
/*
	initial_id: [24] список
	v1: [5] сообщение, если данные не получены
*/

	if($act['dialog_id'] != 218)
		return '';

	return
	'<span class="clr1">Список: </span> '.
	'<b>'._dialogParam($act['initial_id'], 'name').'</b>'.
	'<br>'.
	'<span class="clr1">Сообщение: </span> '.
	'<u>'.$act['v1'].'</u>';
}
function _action219info($act) {//БЛОК: обновление содержимого блоков
/*
	target_ids: [49] блоки, содержимое который обновляется
	v1: [70] Цвет, в который будет окрашен блок после нажатия на него.
*/

	if($act['dialog_id'] != 219)
		return '';

	return '';
}

function PHP12_action_221($act) {//ЭЛЕМЕНТ: переход на страницу
	if($act['dialog_id'] != 221)
		return '';
	if(!$page_id = _num($act['target_ids']))
		return '<div class="clr5">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="clr5">Страницы не существует</div>';

	return
	'<span class="clr1">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>';
}
function PHP12_action_222($act) {//ЭЛЕМЕНТ: открытие диалога
	if($act['dialog_id'] != 222)
		return '';
	if(!$dlg_id = _num($act['target_ids'], 1))
		return '<div class="clr5">Отсутствует id диалога</div>';

	switch($dlg_id) {
		case -1: return '<div class="clr8 b">SA: всегда создавать новый диалог</div>';
		case -2: return '<div class="clr8 b">SA: открытие по ID</div>';
	}

	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="clr5">Диалога не существует</div>';

	return
	'<span class="clr1">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>';
}
function PHP12_action_223($act) {//ЭЛЕМЕНТ: тёмная подсказка
	if($act['dialog_id'] != 223)
		return '';

	if($v = $act['v1'])
		return '<span class="clr1">Значение:</span> <b>'.$v.'</b>';

	if($ids = _ids($act['target_ids']))
		return '<span class="clr1">Значение:</span> '.
			   '<span class="clr11">'._elemIdsTitle($ids).'</span>';

	return '<div class="clr5">Отсутствует значение для подсказки</div>';
}
function PHP12_action_224($act) {//ЭЛЕМЕНТ: внешняя ссылка
	if($act['dialog_id'] != 224)
		return '';

	return
	'<span class="clr1">Ссылка:</span> '.
	($act['target_ids'] ?
		'<span class="clr15">'.$act['target_ids'].'</span>'
	: '<span class="clr1">совпадает с содержанием элемента</span>');
}


function PHP12_action208_formula() {//составление формулы для действия 208
	return '';
}
function PHP12_action208_formula_save($cmp, $val, $unit) {
	if(!$action_id = _num($unit['id']))
		return;

	$ZN = array(
		'+' => 1,
		'-' => 1,
		'*' => 1,
		'/' => 1
	);

	$save = array();

	if(!empty($val))
		if(is_array($val))
			foreach($val as $n => $r) {
				if(!isset($ZN[$r['znak']]))
					continue;

				$znak = $r['znak'];
				if(!$n && ($znak == '*' || $znak == '/'))
					$znak = '+';

				$elem_id = _num($r['elem_id']);
				$v = _cena($r['v']);

				if(!$elem_id && !$v)
					continue;

				$save[] = array(
					'znak' => $znak,
					'elem_id' => $elem_id,
					'v' => $v
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_action`
			SET `v1`='".addslashes($save)."'
			WHERE `id`=".$action_id;
	query($sql);
}
function PHP12_action208_formula_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(empty($prm['unit_edit']['v1']))
		return array();

	$send = _decode($prm['unit_edit']['v1']);
	foreach($send as $n => $r) {
		$send[$n]['title'] = '';
		if($el = _elemOne($r['elem_id']))
			$send[$n]['title'] = _element('title', $el);
	}

	return _arrNum($send);
}








/* ---=== ВЫПЛЫВАЮЩИЕ ПОДСКАЗКИ ===--- */
function PHP12_hint_spisok($prm) {//список подсказок для управления
	//Используется в двух местах:
	//      в Администрировании (условие 1 = 1: конкретное приложение)
	//      в SA (условие 1 = 0: конструктор)

	$sql = "SELECT *
			FROM `_action`
			WHERE `app_id`=".($prm['el12']['num_1'] ? APP_ID : 0)."
			  AND `dialog_id`=229
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return _empty('Подсказки не создавались');

	$send = '<table class="_stab">'.
				'<tr>'.
			  (SA ? '<th>id' : '').
					'<th>Привязана'.
					'<th>Размещена'.
					'<th>Содержание'.
					'<th class="w150">Создана'.
					'<th>';
	foreach($arr as $id => $r) {
		$place = '';

		$BL = array();
		if($r['block_id'])
			$BL = _blockOne($r['block_id']);
		if($r['element_id'])
			if($EL = _elemOne($r['element_id']))
				$BL = _blockOne($EL['block_id']);

		if(!empty($BL))
			switch($BL['obj_name']) {
				case 'page':
					$page = _page($BL['obj_id']);

					//если страница получает данные списка, получение первого id значения этого списка
					$unit = '';
					if($did = $page['dialog_id_unit_get']) {
						$sql = "SELECT `id`
								FROM `_spisok`
								WHERE `dialog_id`=".$did."
								ORDER BY `id`
								LIMIT 1";
						$unit = '&id='.query_value($sql);
					}
					$place = '<a href="'.URL.'&p='.$page['id'].$unit.'&block_flash='.$BL['id'].'">На странице <b>'.$page['name'].'</b></a>';
					break;
				case 'dialog':
					$dlg = _dialogQuery($BL['obj_id']);
					$place = '<a class="dialog-open" val="dialog_id:'.$dlg['id'].',block_flash:'.$BL['id'].'">В диалоге <b>'.$dlg['name'].'</b></a>';
					break;
				case 'dialog_del':
					$place = 'В содержании удаления';
					break;
				case 'spisok':
					$place = 'В списке';
					break;
			}

		$send .=
			'<tr class="over1">'.
		  (SA ? '<td class="r clr2">'.$id : '').
				'<td>'.($r['block_id'] ? 'к блоку' : 'к элементу').
				'<td>'.$place.
				'<td>'._blockHtml('hint', $id, array('td_no_end'=>1)).
				'<td class="r clr1">'.FullDataTime($r['dtime_add'], 1).
				'<td><div class="icon icon-edit dialog-open" val="dialog_id:229,edit_id:'.$id.'"></div>';
	}
	$send .= '</table>';
	return $send;
}
function _hintMass() {//получение массива собранных подсказок
	global $HINT_MASS;
	return _arrNum($HINT_MASS);
}
function _hintMassPush($ht) {//добавление данных подсказки в общий массив
	global $HINT_MASS;

	//сборщик подсказок
	if(!isset($HINT_MASS))
		$HINT_MASS = array();

	$key = false;
	while(!$key) {
		$key = rand(100000, 999999);
		if(isset($HINT_MASS[$key]))
			$key = false;
	}

	unset($ht['id']);
	$HINT_MASS[$key] = $ht;

	return $key;
}
function _hintDlgId($prm, $obj_id=0) {//поиск id диалога для подсказки
	if($obj_id)
		return $obj_id;

	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	//проверка, что подсказка именно из блока
	if($BL['obj_name'] != 'hint')
		return false;

	//получение данных о подсказке
	if(!$hint = _BE('hint_one', $BL['obj_id']))
		return 'Не получены данные подсказки id:'.$BL['obj_id'];

	//получение данных блока, к которому прикреплена подсказка
	if($block_id = $hint['block_id']) {
		if(!$BBL = _blockOne($block_id))
			return 'Не получены данные блока, к которому прикреплена подсказка';

		switch($BBL['obj_name']) {
			case 'page':
				if(!$page = _page($BBL['obj_id']))
					return '[11] Не получены данные страницы';
				if(!$dialog_id = $page['dialog_id_unit_get'])
					return '[11] Страница не принимает данные записи';
				return $dialog_id;
			case 'spisok':
				if(!$dlg_id = _elemDlgId($BBL['obj_id']))
					return 0;
				return $dlg_id;
			default:
				return 'Не определено местоположение блока';
		}

	}

	if($dlg_id = _elemDlgId($hint['element_id']))
		return $dlg_id;

	return 'Не определено местоположение подсказки';
}


