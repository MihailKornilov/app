<?php

/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
function PHP12_action_list($prm) {
	//текущий диалог для обновления списка действий после редактирования
	$dss = $prm['el12']['block']['obj_id'];

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
		$spisok .=
			'<dd val="'.$id.'">'.
			'<table class="w100p bs5 bor1 bg-gr2 over2 mb5 curD">'.
				'<tr>'.
					'<td class="w25 top">'.
						'<div class="icon icon-move-y pl"></div>'.
					'<td><div class="fs15 color-555">'._dialogParam($r['dialog_id'], 'name').'</div>'.
						'<div class="mt3 ml10">'.
							PHP12_action_201($r).
							PHP12_action_205($r).
							PHP12_action_211($r).
							PHP12_action_212($r).
							PHP12_action_213($r).
							PHP12_action_214($r).
							PHP12_action_215($r).
							PHP12_action_216($r).
							PHP12_action_221($r).
							PHP12_action_222($r).
							PHP12_action_223($r).
							PHP12_action_224($r).
						'</div>'.
					'<td class="w50 r top">'.
						'<div val="dialog_id:'.$r['dialog_id'].',edit_id:'.$id.',block_id:'.$block_id.',dss:'.$dss.'" class="icon icon-edit pl dialog-open'._tooltip('Настроить действие', -60).'</div>'.
						_iconDel(array(
							'class' => 'pl ml5 dialog-open',
							'val' => 'dialog_id:'.$r['dialog_id'].',del_id:'.$id.',dss:'.$dss
						)).
			'</table>'.
			'</dd>';
	}

	return '<dl>'.$spisok.'</dl>';
}
function PHP12_action_201($r) {//ЭЛЕМЕНТ: скрытие/показ блоков
	if($r['dialog_id'] != 201)
		return '';

/*
	apply_id: Действие с блоками: скрыть|показать
	filter: Фильтр
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
	revers: Обратное действие
	target_ids: Блоки, на которые происходит воздействие
	effect_id: Эффект

*/


	//Названия действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);


	$c = count(_ids($r['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');

	$initial = '-';
	switch($r['initial_id']) {
		case -1: $initial = '<b class="color-ref">значение сброшено</b>'; break;
		case -2: $initial = '<b class="color-pay">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($r['element_id']))
				break;

			switch($el['dialog_id']) {
				case 29:
				case 59:
					if(!$DLG = _dialogQuery($el['num_1']))
						break;
					if(!$u = _spisokUnitQuery($DLG, $r['initial_id']))
						break;
					$initial = 'выбрано <b class="color-pay">'.$u['txt_1'].'</b>';
			}

	}


	$effect = '';
	if($r['effect_id']) {
		//Названия эффектов
		$sql = "SELECT `txt_1`
				FROM `_element`
				WHERE `id`=".$r['effect_id'];
		$name = query_value($sql);
		$effect =   '<div class="fs12 grey mt2">'.
						'Эффект: '.
						'<span class="fs12 color-sal">'.$name.'</span>'.
					'</div>';

	}

	$revers = $r['revers'] ? '<div class="fs11 i color-555 mt2">Применяется обратное действие</div>' : '';

	return
	'<div class="b">'.$apply.' '.$target.'</div>'.
	'<span class="grey">если</span> '.$initial.
	$effect.
	$revers;
}
function PHP12_action_205($r) {//ЭЛЕМЕНТ: открытие диалога
	if($r['dialog_id'] != 205)
		return '';

/*
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
					id: конкретное значение
	target_ids: id диалога
	apply_id:  элемент передаёт данные записи для отображения
	effect_id: элемент передаёт данные записи для редактирования

*/

	if(!$DLG = _dialogQuery($r['target_ids']))
		return '<div class="red">не получены данные диалога ['.$r['target_ids'].']</div>';

	$initial = '-';
	switch($r['initial_id']) {
		case -1: $initial = '<b class="color-ref">значение сброшено</b>'; break;
		case -2: $initial = '<b class="color-pay">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($r['element_id']))
				break;

			switch($el['dialog_id']) {
				case 18:
					foreach($el['vvv'] as $vv)
						if($vv['id'] == $r['initial_id'])
							$initial = 'выбрано <b class="color-sal">'.$vv['title'].'</b>';
					break;
				case 29:
				case 59:
					if(!$DLG = _dialogQuery($el['num_1']))
						break;
					if(!$u = _spisokUnitQuery($DLG, $r['initial_id']))
						break;
					$initial = 'выбрано <b class="color-pay">'.$u['txt_1'].'</b>';
			}

	}

	$get  = $r['apply_id']  ? '<div class="fs11 i color-ref mt2">Элемент передаёт данные записи для отображения</div>' : '';
	$edit = $r['effect_id'] ? '<div class="fs11 i color-ref mt2">Элемент передаёт данные записи для редактирования</div>' : '';

	return
	'<span class="grey">Диалог: </span> <b>'.$DLG['name'].'</b>'.
	'<br>'.
	'<span class="grey">если</span> '.$initial.
	$get.
	$edit;
}
function PHP12_action_211($r) {//БЛОК: скрытие/показ блоков
	if($r['dialog_id'] != 211)
		return '';

	//Название действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);


	$c = count(_ids($r['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');


	$effect = '';
	if($r['effect_id']) {
		//Названия эффектов
		$sql = "SELECT `txt_1`
				FROM `_element`
				WHERE `id`=".$r['effect_id'];
		$name = query_value($sql);
		$effect =   '<div class="fs12 grey mt2">'.
						'Эффект: '.
						'<span class="fs12 color-sal">'.$name.'</span>'.
					'</div>';

	}

	$revers = $r['revers'] ? '<div class="fs11 i color-555 mt2">Применяется обратное действие</div>' : '';

	return
	'<div class="b">'.$apply.' '.$target.'</div>'.
	$effect.
	$revers;
}
function PHP12_action_212($r) {//БЛОК: Установка значения элементу
	if($r['dialog_id'] != 212)
		return '';
	if(!$elem_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id элемента</div>';
	if(!$el = _elemOne($elem_id))
		return '<div class="red">Элемента не существует</div>';

	$send = '<div class="red">Неизвестный элемент ['.$el['dialog_id'].']</div>';

	switch($el['dialog_id']) {
		case 1:
		case 62:
			$send = '<div class="red">Неизвестное действие для галочки</div>';
			if($r['apply_id'] == -1)
				$send = '<b>Снять галочку</b>';
			if($r['apply_id'] == 1)
				$send = '<b>Установить галочку</b>';
			break;
	}


	return $send;
}
function PHP12_action_213($r) {//БЛОК: блокировка элементов
	if($r['dialog_id'] != 213)
		return '';
	if(!$ids = _ids($r['target_ids'], 'arr'))
		return '<div class="red">Отсутствует элементы для блокировки</div>';

	//Название действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);

	$elem = array();
	foreach($ids as $id)
		$elem[] = '<b>'._element('title', $id).'</b>';

	$target = implode(', ', $elem);

	return
	$apply.' '.
	'элемент'.(count($elem) > 1 ? 'ы' : '').' '.
	$target;
}
function PHP12_action_214($r) {//БЛОК: переход на страницу
	if($r['dialog_id'] != 214)
		return '';
	if(!$page_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="red">Страницы не существует</div>';

	return
	'<span class="grey">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>'.
	($r['apply_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи</div>' : '');
}
function PHP12_action_215($r) {//БЛОК: открытие диалога
	if($r['dialog_id'] != 215)
		return '';
	if(!$dlg_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id диалога</div>';
	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="red">Диалога не существует</div>';

	return
	'<span class="grey">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>'.
	($r['apply_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи для отображения</div>' : '').
	($r['effect_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи для редактирования</div>' : '');
}
function PHP12_action_216($r) {//БЛОК: Установка фокуса на элемент
	if($r['dialog_id'] != 216)
		return '';
	if(!$elem_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id элемента</div>';

	return '<span class="grey">Элемент:</span> <b>'._element('title', $elem_id).'</b>';
}
function PHP12_action_221($r) {//ЭЛЕМЕНТ: переход на страницу
	if($r['dialog_id'] != 221)
		return '';
	if(!$page_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="red">Страницы не существует</div>';

	return
	'<span class="grey">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>';
}
function PHP12_action_222($r) {//ЭЛЕМЕНТ: открытие диалога
	if($r['dialog_id'] != 222)
		return '';
	if(!$dlg_id = _num($r['target_ids'], 1))
		return '<div class="red">Отсутствует id диалога</div>';

	switch($dlg_id) {
		case -1: return '<div class="color-ref b">SA: всегда создавать новый диалог</div>';
		case -2: return '<div class="color-ref b">SA: открытие по ID</div>';
	}

	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="red">Диалога не существует</div>';

	return
	'<span class="grey">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>';
}
function PHP12_action_223($r) {//ЭЛЕМЕНТ: тёмная подсказка
	if($r['dialog_id'] != 223)
		return '';
	if(!$v = _ids($r['target_ids']))
		return '<div class="red">Отсутствует значение для подсказки</div>';

	return
	'<span class="grey">Значение:</span> '.
	'<span class="color-pay">'._elemIdsTitle($v).'</span>';
}
function PHP12_action_224($r) {//ЭЛЕМЕНТ: внешняя ссылка
	if($r['dialog_id'] != 224)
		return '';

	return
	'<span class="grey">Ссылка:</span> '.
	($r['target_ids'] ?
		'<span class="blue">'.$r['target_ids'].'</span>'
	: '<span class="grey">совпадает с содержанием элемента</span>');
}


function PHP12_action208_formula($prm) {//составление формулы для действия 208
	return '';
}
function PHP12_action208_formula_save($cmp, $val, $unit) {
	if(!$action_id = _num($unit['id']))
		return;

	$sql = "UPDATE `_action`
			SET `v1`='".$val."'
			WHERE `id`=".$action_id;
	query($sql);
}
function PHP12_action208_formula_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(empty($u['v1']))
		return array();

	$send = array();
	$ex = explode(',', $u['v1']);
	$count = (count($ex) - 1) / 2;

	for($n = 0; $n <= $count; $n++) {
		$el = _elemOne($ex[$n*2]);
		$send[] = array(
			'elem_id' => $ex[$n*2],
			'elem_name' => @$el['title'],
			'znak' => $n ? $ex[$n*2-1] : 0
		);
	}

	return _arrNum($send);
}








/* ---=== ВЫПЛЫВАЮЩИЕ ПОДСКАЗКИ ===--- */
function PHP12_action229_content($prm) {//содержание подсказки
	/*
		имя объекта: hint
		 id объекта: id действия из _action
	*/
	if(!$unit = $prm['unit_edit'])
		return
		'<div class="bg-ffe pad10">'.
			_emptyMin('Настройка содержания будет доступна после создания подсказки.').
		'</div>';

	//максимальная ширина подсказки: 500
	$width = 500;

	return
	'<div class="bg-ffc pad10 line-b">'.
		_blockLevelChange('hint', $unit['id']).
	'</div>'.
	'<div class="block-content-hint" style="width:'.$width.'px">'.
		_blockHtml('hint', $unit['id'], array('blk_setup' => 1)).
	'</div>';
}
function PHP12_hint_spisok($prm) {//список подсказок для управления
	//Используется в двух местах:
	//      в Администрировании (условие 1 = 1: конктертное приложение)
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
				$BL = $EL['block'];


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
		  (SA ? '<td class="r pale">'.$id : '').
				'<td>'.($r['block_id'] ? 'к блоку' : 'к элементу').
				'<td>'.$place.
				'<td>'._blockHtml('hint', $id, array('td_no_end'=>1)).
				'<td class="r grey">'.FullDataTime($r['dtime_add'], 1).
				'<td><div class="icon icon-edit dialog-open" val="dialog_id:229,edit_id:'.$id.'"></div>';
	}
	$send .= '</table>';
	return $send;
}
function _hintObj($obj_name, $obj_id) {//подсказки, размещённые на странице, в диалоге
	global $HINT_MASS;
	$send = $HINT_MASS;

	_BE('elem_hint_one');

	$sql = "SELECT `id`
			FROM `_block`
			WHERE `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	if(!$blkIds = query_ids($sql))
		return $send;


	//получение подсказок для блоков
	$sql = "SELECT *
			FROM `_action`
			WHERE `dialog_id`=229
			  AND `block_id` IN (".$blkIds.")
			ORDER BY `id`";
	if($hint = query_arr($sql))
		foreach($hint as $action_id => $r) {
			$block_id = $r['block_id'];
			$prm = array();

			if($BL = _blockOne($block_id)) {
				switch($BL['obj_name']) {
					case 'page':
						if(!$page = _page($BL['obj_id']))
							break;
						if(!$dialog_id = $page['dialog_id_unit_get'])
							break;
						if(!$dialog = _dialogQuery($dialog_id))
							break;
						if(!$prm['unit_get_id'] = _num(@$_GET['id']))
							break;
						$prm['unit_get'] = _spisokUnitQuery($dialog, $prm['unit_get_id']);
						break;
				}
			}

			$send['bl_'.$block_id] = _hintStruct($r, $action_id, $prm);
		}

/*
	//получение подсказок для элементов
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id` IN (".$blkIds.")";
	if(!$elmIds = query_ids($sql))
		return $send;

	$sql = "SELECT *
			FROM `_action`
			WHERE `dialog_id`=229
			  AND `element_id` IN (".$elmIds.")
			ORDER BY `id`";
	if(!$hint = query_arr($sql))
		return $send;

	foreach($hint as $action_id => $r) {
		$elem_id = $r['element_id'];
		$send['el_'.$elem_id] = _hintStruct($r, $action_id);
	}
*/
	return $send;
}
function _hintStruct($r, $id, $prm=array()) {//формирование данных подсказки
	unset($r['app_id']);
	unset($r['dialog_id']);
	unset($r['block_id']);
	unset($r['element_id']);
	unset($r['user_id_add']);
	unset($r['dtime_add']);

	unset($r['filter']);
	unset($r['sort']);

	if(isset($r['initial_id'])) {
		$r['side'] = $r['initial_id'];
		unset($r['initial_id']);
	}

	if(isset($r['apply_id'])) {
		$r['pos_h'] = $r['apply_id'];
		unset($r['apply_id']);
	}

	if(isset($r['effect_id'])) {
		$r['pos_v'] = $r['effect_id'];
		unset($r['effect_id']);
	}

	if(isset($r['target_ids'])) {
		$r['ug_h'] = $r['target_ids'];
		unset($r['target_ids']);
	}

	if(isset($r['revers'])) {
		$r['ug_v'] = $r['revers'];
		unset($r['revers']);
	}

	if(isset($r['v1'])) {
		$r['delay_show'] = $r['v1'];
		unset($r['v1']);
	}

	if(isset($r['v2'])) {
		$r['delay_hide'] = $r['v2'];
		unset($r['v2']);
	}

	$prm['td_no_end'] = 1;
	$r['msg'] = _blockHtml('hint', $id, $prm);

	return $r;
}
function _hintCacheClear($dlg) {//очистка кеша подсказок
	if($dlg['table_name_1'] != '_action')
		return;
	if($dlg['id'] != 229)
		return;

	global $BE_FLAG;

	_cache_clear('HINT');
	_cache_clear('HINT', 1);
	$BE_FLAG = 0;
}
function _hintDlgId($BL, $obj_id=0) {//поиск id диалога для подсказки
	if($obj_id)
		return $obj_id;

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
			default:
				return 'Не определено местоположение блока';
		}

	}

	if($dlg_id = _elemDlgId($hint['element_id']))
		return $dlg_id;

	return 'Не определено местоположение подсказки';
}


