<?php
function _button($v=array()) {//кнопка из контакта
	$attr_id = empty($v['attr_id']) ? '' : ' id="'.$v['attr_id'].'"';
	$name = empty($v['name']) ? 'Кнопка' : $v['name'];
	$small = empty($v['small']) ? '' : ' small';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$cls = empty($v['class']) ? '' : ' '.$v['class'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';

	$width = '';
	if(isset($v['width']))
		switch($v['width']) {
			case 0: $width = ' style="width:100%"'; break;
			default: $width = ' style="width:'._num($v['width']).'px"';
		}

	return
	'<button class="vk'.$color.$small.$cls.'"'.$attr_id.$width.$click.$val.'>'.
		$name.
	'</button>';
}


function _iconEdit($v=array()) {//иконка редактирования записи в таблице
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	$v = array(
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : 'Изменить',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$click.$val.' class="icon icon-edit'.$cls._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDel($v=array()) {//иконка удаления записи в таблице
	if(!empty($v['nodel']))
		return '';

	//если указывается дата внесения записи и она не является сегодняшним днём, то удаление невозможно
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';
	$red = empty($v['red']) ? '' : '-red';
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	return '<div'.$click.$val.' class="icon icon-del'.$red.$cls._tooltip('Удалить', -42, 'r').'</div>';
}

function _colorJS() {//массив цветов для текста в формате JS, доступных элементам
	return '{'.
		'"":["#000","Чёрный"],'.
		'"color-555":["#555","Тёмно-серый"],'.
		'"grey":["#888","Серый"],'.
		'"pale":["#aaa","Бледный"],'.
		'"color-ccc":["#ccc","Совсем бледный"],'.
		'"blue":["#2B587A","Тёмно-синий"],'.
		'"color-acc":["#07a","Синий"],'.
		'"color-sal":["#770","Салатовый"],'.
		'"color-pay":["#090","Зелёный"],'.
		'"color-aea":["#aea","Ярко-зелёный"],'.
		'"red":["#e22","Красный"],'.
		'"color-ref":["#800","Тёмно-красный"],'.
		'"color-del":["#a66","Тёмно-бордовый"],'.
		'"color-vin":["#c88","Бордовый"]'.
	'}';
}

function _emptyMin($msg) {
	return '<div class="_empty min mar10">'.$msg.'</div>';
}

function _dialogTest() {//проверка id диалога, создание нового нового, если это кнопка
	//если dialog_id получен - отправка его
	if($dialog_id = _num(@$_POST['dialog_id']))
		return $dialog_id;
	if(!$block_id = _num(@$_POST['block_id']))
		return false;

	//получение элемента-кнопки для присвоения нового диалога
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id."
			  AND `dialog_id` IN (2,59)
			LIMIT 1";
	if(!$elem = query_assoc($sql))
		return false;

	//новый диалог кнопке уже был присвоен
	if($elem['num_4'])
		return $elem['num_4'];

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".USER_ID."
			)";
	query($sql);

	$sql = "SELECT IFNULL(MAX(`num`),0)+1
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$num = query_value($sql);

	$dialog_id = query_insert_id('_dialog');

	$sql = "UPDATE `_dialog`
			SET `num`=".$num.",
				`name`='Диалог ".$num."'
			WHERE `id`=".$dialog_id;
	query($sql);

	$sql = "UPDATE `_element`
			SET `num_4`=".$dialog_id."
			WHERE `id`=".$elem['id'];
	query($sql);

	_BE('block_clear');
	_BE('elem_clear');
	_BE('dialog_clear');

	return $dialog_id;
}
function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	if(!$dialog = _BE('dialog', $dialog_id))
		return array();

		//id заглавных элементов настройки шаблона истории действий
		foreach(array(1,2,3) as $n) {
			$dialog['history'][$n]['elem_id'] = 0;
			$dialog['history'][$n]['tmp'] = '';
			$dialog['history'][$n]['tmp_elm'] = array();
			continue;

			$sql = "SELECT `id`
					FROM `_element`
					WHERE `dialog_id`=67
					  AND `num_1`=".$n."
					  AND `num_2`=".$dialog_id."
					LIMIT 1";
			$elem_id = query_value($sql);
			$dialog['history'][$n]['elem_id'] = $elem_id;

			$tmp_txt = '';//текстовое содержание шаблона истории
			$tmp_elm = array();//элементы, участвующие в шаблоне истории
			if($elem_id) {
				$sql = "SELECT *
						FROM `_element`
						WHERE `block_id`=-".$elem_id."
						ORDER BY `sort`";
				if($elem = query_arr($sql)) {
					$sql = "SELECT `id`,`col`
							FROM `_element`
							WHERE `id` IN ("._idsGet($elem, 'num_1').")";
					$cols = query_ass($sql);
					foreach($elem as $r) {
						$num_1 = $r['num_1'] ? '[' . $r['num_1'] . ']' : '';
						$tmp_txt .= $r['txt_7'].$num_1.$r['txt_8'];
						switch($r['dialog_id']) {
							case 11: $col = @$cols[$r['num_1']]; break;
							case 32: $col = 'num'; break;
							case 33: $col = 'dtime_add'; break;
							default: $col = '';
						}
						$r['col'] = $col;
						$tmp_elm[] = $r;
					}
				}
			}
			$dialog['history'][$n]['tmp'] = trim($tmp_txt);
			$dialog['history'][$n]['tmp_elm'] = $tmp_elm;
		}

	$dialog['blk'] = _BE('block_arr', 'dialog', $dialog_id);
	$dialog['cmp'] = _BE('elem_arr', 'dialog', $dialog_id);

	return $dialog;
}
function _dialogParam($dialog_id, $param) {//получение конкретного параметра диалога
	$dialog = _dialogQuery($dialog_id);
	if(!isset($dialog[$param]))
		return 'Неизвестный параметр диалога: '.$param;

	$send = $dialog[$param];

	if(!is_array($send) && preg_match(REGEXP_NUMERIC, $send))
		return _num($send);

	return $send;
}
function _dialogSpisokOn($dialog_id, $block_id, $elem_id) {//получение массива диалогов, которые могут быть списками: spisok_on=1
	$cond = "`spisok_on`";
	$cond .= " AND `app_id` IN (0,".APP_ID.")";

/*
	//получение id диалога, который является списком, чтобы было нельзя его выбирать в самом себе (для связок)
	$dialog = _dialogQuery($dialog_id);
	if(_table($dialog['table_1']) == '_element') {
		//если редактирование - получение id блока из элемента
		if($elem_id) {
			$sql = "SELECT `block_id`
					FROM `_element`
					WHERE `id`=".$elem_id;
			$block_id = query_value($sql);
		}
		//если вставка элемента в блок
		$sql = "SELECT `obj_id`
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id`=".$block_id;
		if($dialog_id_skip = query_value($sql))
			$cond .= " AND `id`!=".$dialog_id_skip;
	}
*/
	$sql = "SELECT *
			FROM `_dialog`
			WHERE ".$cond."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	$saArr = array();
	foreach($arr as $r) {
		if($r['sa'] || !$r['app_id']) {
			$saArr[] = $r;
			continue;
		}
		$send[] = array(
			'id' => _num($r['id']),
			'title' => $r['name']
		);
	}


	//списки, доступные только SA
	if(SA) {
		$send[] = array(
			'info' => 1,
			'title' => 'SA-списки:'
		);
		foreach($saArr as $r)
			$send[] = array(
				'id' => _num($r['id']),
				'title' => $r['name'],
				'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.$r['name'].'</div>'
			);
	}


	return $send;
}
function _dialogSpisokOnPage($block_id) {//получение массива диалогов, которые могут быть списками: spisok_on=1
/*
	получены будут списки, размещёные в текущем объекте
	$elem_id - размещённый на странице или в диалоге, по которому определяется объект
	Идентификаторами результата являются id элементов (а не диалогов)
*/

	if(!$block = _blockOne($block_id))
		return array();

	//списки размещаются при помощи диалогов 14 и 23
	//идентификаторами результата являются id элементов (а не диалогов)

	if(!$elm = _BE('elem_arr', $block['obj_name'], $block['obj_id']))
		return array();

	$send = array();
	foreach($elm as $elem_id => $r) {
		if($r['dialog_id'] != 14 && $r['dialog_id'] != 23 && $r['dialog_id'] != 68)
			continue;

		if($r['dialog_id'] == 68)
			$spisokName = 'История действий';
		else
			$spisokName = _dialogParam($r['num_1'], 'name');
		$send[$elem_id] = $spisokName.' (в '.$block['obj_name'].'-блоке '.$r['block_id'].')';
	}

	return $send;
}
function _dialogSpisokOnConnect($block_id, $elem_id) {//получение диалогов-списков, которые привязаны к текущему (исходному) диалогу
/*
	$block_id - исходный блок, по которому определяется объект
	Привязка происходит через элемент [29], по нему будет производиться происк
	Идентификаторами результата являются id элементов (а не диалогов)
*/

	//получение исходного блока, если редактирование значения
	if($elem_id) {
		if(!$EL = _elemOne($elem_id))
			return array();
		$block_id = $EL['block_id'];
	}

	if(!$BL = _blockOne($block_id))
		return array();

	if($BL['obj_name'] != 'dialog')
		return array();

	$dialog_id = $BL['obj_id'];

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$dialog_id."
			ORDER BY `id`";
	if(!$elem = query_arr($sql))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			ORDER BY `obj_id`";
	if(!$block = query_arr($sql))
		return array();

	//количество связок для каждого диалога (connect count)
	$sql = "SELECT
				`obj_id`,
				COUNT(`id`)-1
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			GROUP BY `obj_id`";
	$cc = query_ass($sql);

	$send = array();
	foreach($elem as $elem_id => $r) {
		$BL = $block[$r['block_id']];
		$obj_id = _num($BL['obj_id']);
		$dialog = _dialogQuery($obj_id);
		$send[_num($elem_id)] = $dialog['name'].($cc[$obj_id] ? ' (в блоке '.$r['block_id'].')' : '');
	}

	return $send;
}
function _dialogSelArray($v=false) {//список диалогов для Select - отправка через AJAX
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ',0' : '').")
			  AND `sa` IN(0".(SA ? ',1' : '').")
			ORDER BY `app_id` DESC,`id`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	$sa_only = $v == 'sa_only';
	$saFlag = $sa_only;
	$skip = _num($v);//id диалога, который нужно пропустить
	foreach($arr as $r) {
		if($r['id'] == $skip)
			continue;
		if(!$saFlag && !$r['app_id']) {//вставка графы для SA
			$spisok[] = array(
				'info' => 1,
				'title' => 'SA-диалоги:'
			);
			$saFlag = 1;
		}
		if($sa_only && $r['app_id'])
			continue;
		$u = array(
			'id' => _num($r['id']),
			'title' => $r['name']
		);
		if(!$r['app_id'])
			$u['content'] = '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'"><b>'.$r['id'].'</b>. '.$r['name'].'</div>';
		$spisok[] = $u;
	}

	return $spisok;
}

function PHP12_dialog_sa($el, $unit) {//список диалоговых окон [12]
	$sql = "SELECT *
			FROM `_dialog`
			WHERE !`app_id`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	$send = '<table class="_stab small">'.
				'<tr>'.
					'<th>ID'.
					'<th>Таблица'.
					'<th>Имя диалога'.
					'<th>afics'.
					'<th>type'.
					'<th>col';
	foreach($arr as $r) {
		$send .= '<tr class="over1 curP dialog-open" val="dialog_id:'.$r['id'].'">'.
					'<td class="w35 r grey'.($r['sa'] ? ' bg-fee' : '').'">'.$r['id'].
					'<td class="'.(_table($r['table_1']) == '_element' ? 'b color-pay' : '').'">'.
						_table($r['table_1']).
						($r['table_2'] ? '<br>'._table($r['table_2']) : '').
					'<td>'.$r['name'].
					'<td>'.$r['element_afics'].
					'<td class="center">'._elemColType($r['element_type']).
					'<td class="grey">'.PHP12_dialog_col($r['id']);
	}
	$send .= '</table>';

	return $send;
}
function PHP12_dialog_app($el, $unit) {//список диалоговых окон для конкретного приложения [12]
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	$send = '<table class="_stab small">'.
				'<tr>'.
					'<th>ID'.
					'<th>Имя диалога'.
					'<th>Список'.
					'<th>Колонки';
	foreach($arr as $r) {
		$send .= '<tr class="over1 curP dialog-open" val="dialog_id:'.$r['id'].'">'.
					'<td class="w35 r grey">'.$r['id'].
					'<td>'.$r['name'].
					'<td class="center'.($r['spisok_on'] ? ' bg-dfd' : '').'">'.($r['spisok_on'] ? 'да' : '').
					'<td class="grey">'.PHP12_dialog_col($r['id']);
	}
	$send .= '</table>';

	return $send;
}
function PHP12_dialog_col($dialog_id) {//колонки, используемые в элементе
	$send = array();
	foreach(_BE('elem_arr', 'dialog', $dialog_id) as $el) {
		//поиск элементам, которым не назначена колонка таблицы
		if(!$col = $el['col'])
			foreach(_BE('elem_arr', 'dialog', $el['dialog_id']) as $ell)
				if($ell['col'] == 'col')
					if($el['dialog_id'] != 12) {
						$dlg = _dialogQuery($el['dialog_id']);
						$col = '<span class="bg-fee'._tooltip('Отсутствует имя колонки<br>'.$dlg['name'], 5, 'l', 1).'--- ['.$el['dialog_id'].']</span>';
						break;
					}

		if(!$col)
			continue;

		if(isset($send[$col])) {
			$send[$col.'dub'.rand(0, 10000)] = '<span class="bg-fcc">'.$col.' - повтор</span>';
			continue;
		}

		if($col == 'col')
			$send[$col] = '<span class="red b">'.$col.'</span>';
		elseif($col == 'name')
			$send[$col] = '<span class="color-pay b">'.$col.'</span>';
		elseif($col == 'req' || $col == 'req_msg')
			$send[$col] = '<span class="color-ref b">'.$col.'</span>';
		else
			$send[$col] = $col;
	}

	ksort($send);

	return implode('<br>', $send);
}

function PHP12_spisok_app($type_id, $msgEmpty, $appAll=0) {//вывод списков по условиям
	$arr = array();

	foreach(_BE('elem_all') as $el) {
		if($el['dialog_id'] != $type_id)
			continue;
		if(!$dlg = _dialogQuery($el['num_1']))
			continue;
		if($appAll && !$dlg['app_id'] || !$appAll && $dlg['app_id']) {
			$el['dlg'] = $dlg;
			$arr[] = $el;
		}
	}

	if(empty($arr))
		return $msgEmpty;

	$send = '<table class="_stab">'.
				'<tr>'.
					'<th class="w50">el-id'.
					'<th>Диалог, создающий список'.
					'<th>Местонахождение списка';
	foreach($arr as $r) {
		if(!$el = _elemOne($r['id'])) {
			$send .=
				'<tr><td colspan="10" class="red">'.
						'Элемента '.$r['id'].' нет в кеше.';
			continue;
		}

		$bl = _blockOne($r['block_id']);

		$link = '';
		//ссылка на страницу, в котором расположен список
		if($bl['obj_name'] == 'page') {
			$page = _page($bl['obj_id']);
			$link = '<a href="'.URL.'&p='.$bl['obj_id'].'" class="color-pay">Страница '.$bl['obj_id'].' - '.$page['name'].'</a>';
		}
		//диалог, в котором расположен список
		if($bl['obj_name'] == 'dialog') {
			$dlg = _dialogQuery($bl['obj_id']);
			$link = '<a class="dialog-open" val="dialog_id:'.$bl['obj_id'].'">Диалог '.$bl['obj_id'].' - '.$dlg['name'].'</a>';
		}

		$send .= '<tr>'.
					'<td class="r grey">'.$r['id'].
					'<td class="b over1 curP dialog-open" val="dialog_id:'.$r['dlg']['id'].'"">'.$r['dlg']['name'].
					'<td>'.$link;
	}
	$send .= '</table>';

	return $send;
}
function PHP12_spisok14_all() {//списки-шаблоны для всех приложений. Страница 126
	return PHP12_spisok_app(14, 'Списков-шаблонов нет.', 1);
}
function PHP12_spisok23_all() {//списки-таблицы для всех приложений. Страница 126
	return PHP12_spisok_app(23, 'Списков-таблиц нет.', 1);
}
function PHP12_spisok14_app() {//списки-шаблоны для текущего приложения. Страница 127
	return PHP12_spisok_app(14, 'Списков-шаблонов нет.');
}
function PHP12_spisok23_app() {//списки-таблицы для текущего приложения. Страница 127
	return PHP12_spisok_app(23, 'Списков-таблиц нет.');
}

function _elemOne($elem_id) {//запрос одного элемента
	return _BE('elem_one', $elem_id);
}
function _blockOne($block_id) {//запрос одного блока
	return _BE('block_one', $block_id);
}

function _elemVvv($elem_id, $src=array()) {
	if(!$el = _elemOne($elem_id))
		return array();

	$block_id =  _num(@$src['block_id']);
	$dialog_id = _num(@$src['dialog_id']);
	$unit_id = _num(@$src['unit_id']);
	$unit = $unit_id ? $src['unit'] : array();

	switch($el['dialog_id']) {
		//подключаемая функция
		case 12:
			if(!$unit_id)
				break;

			$func = $el['txt_1'].'_vvv';
			if(!function_exists($func))
				break;

			return $func($unit_id);

		//Radio
		case 16:
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			return query_ass($sql);

		//Select
		case 17:
		//dropdown
		case 18:
			$send = array();
			$sql = "SELECT *
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r)
				$send[] = array(
					'id' => _num($r['id']),
					'title' => $r['txt_1'],
					'content' => $r['txt_2']
				);
			return $send;

		//select - выбор списка (все списки приложения)
		case 24:
			switch($el['num_1']) {
				case 960: return _dialogSpisokOnPage($block_id);
				case 961: return _dialogSpisokOnConnect($block_id, $unit_id);
			}
			return _dialogSpisokOn($dialog_id, $block_id, $elem_id);

		//SA: select - выбор имени колонки
		case 37:
			if(!$block = _blockOne($block_id))
				break;

			//выбор имени колонки может производиться, только если элемент размещается в диалоге
			if($block['obj_name'] != 'dialog')
				break;

			if(!$colDialog = _dialogQuery($block['obj_id']))
				break;

			//выбор колонок из родительского диалога
			if($parent_id = $colDialog['dialog_parent_id'])
				if(!$colDialog = _dialogQuery($parent_id))
					break;


			//получение используемых колонок
			$colUse = array();
			foreach($colDialog['cmp'] as $r) {
				if(!$col = $r['col'])
					continue;
				$colUse[$col] = 1;
			}

			//колонки, которые не должны выбираться
			$fieldNo = array(
				'id' => 1,
				'id_old' => 1,
				'num' => 1,
				'parent_id' => 1,
				'app_id' => 1,
				'user_id' => 1,
				'page_id' => 1,
				'block_id' => 1,
				'element_id' => 1,
				'dialog_id' => 1,
				'width' => 1,
				'color' => 1,
				'font' => 1,
				'size' => 1,
				'mar' => 1,
				'sort' => 1,
				'deleted' => 1,
				'user_id_add' => 1,
				'user_id_del' => 1,
				'dtime_add' => 1,
				'dtime_del' => 1,
				'dtime_last' => 1
			);

			$field = array();
			$n = 1;
			foreach($colDialog['field1'] as $col => $k) {
				if(isset($fieldNo[$col]))
					continue;

				$color = '';
				$busy = 0;//занята ли колонка
				if(isset($colUse[$col])) {
					$color = $unit_id && $unit['col'] == $col ? 'b color-pay' : 'b red';
					$busy = 1;
				}
				$u = array(
					'id' => $n++,
					'title' => $col,
					'busy' => $busy,
					'content' =>
						'<div class="'.$color.'">'.
							'<span class="pale">'._table($colDialog['table_1']).'.</span>'.
							$col.
						'</div>'

				);
				$field[] = $u;
			}

			foreach($colDialog['field2'] as $col => $k) {
				if(isset($fieldNo[$col]))
					continue;

				$color = '';
				if(isset($colUse[$col]))
					$color = $unit_id && $unit['col'] == $col ? 'b color-pay' : 'b red';
				$u = array(
					'id' => $n++,
					'title' => $col,
					'content' =>
						'<div class="'.$color.'">'.
							'<span class="pale">'._table($colDialog['table_2']).'.</span>'.
							$col.
						'</div>'

				);
				$field[] = $u;
			}

			return $field;

/*
		//select - выбор единицы из другого списка (для связки)
		case 29:
			$sel_id = 0;
			if($unit_id && $cmp['col']) {
				if(!empty($unit[$cmp['col']]))
					$sel_id = $unit[$cmp['col']]['id'];
			} else
				$sel_id = _spisokCmpConnectIdGet($cmp);
			$dialog['cmp'][$cmp_id]['vvv'] = _spisok29connect($cmp_id, $v='', $sel_id);
			break;
		//SA: Select - выбор диалогового окна
		case 38: $dialog['cmp'][$cmp_id]['vvv'] = _dialogSelArray(); break;
		//SA: Select - дублирование
		case 41:
			//Отсутствует ID исходного блока.
			if(!$block_id)
				break;

			$BL = _blockOne($block_id);

			//Исходный блок не является блоком из диалога
			if($BL['obj_name'] != 'dialog')
				break;

			//Отсутствует исходный элемент
			if(!$EL = $BL['elem'])
				break;

			//Исходный элемент не является выпадающим полем
			if($EL['dialog_id'] != 17 && $EL['dialog_id'] != 18)
				break;

			$dialog['cmp'][$cmp_id]['txt_1'] = $EL['txt_1'];
			$dialog['cmp'][$cmp_id]['vvv'] = _elemValue($EL['id']);
			break;
		//Настройка содержания Сборного текста
		case 49:
			if($unit_id <= 0)
				break;
			if(!$col = $cmp['col'])
				break;
			if(!$ids = $unit[$col])
				break;
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ids.")
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				break;

			$spisok = array();
			foreach($arr as $r) {
				$spisok[] = array(
					'id' => _num($r['id']),
					'dialog_id' => _num($r['dialog_id']),
					'title' => _elemTitle($r['id']),
					'spc' => _num($r['num_8']) //пробел справа
				);
			}
			$dialog['cmp'][$cmp_id]['vvv'] = $spisok;
			break;
		//Настройка суммы значений единицы списка
		case 56:
			if($unit_id <= 0)
				break;
			if(!$col = $cmp['col'])
				break;
			if(!$ids = $unit[$col])
				break;
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ids.")
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				break;

			$spisok = array();
			foreach($arr as $r) {
				$spisok[] = array(
					'id' => _num($r['id']),
					'dialog_id' => _num($r['dialog_id']),
					'minus' => _num($r['num_8']), //вычитание=1, сложение=0
					'title' => _elemUnit($r)
				);
			}
			$dialog['cmp'][$cmp_id]['vvv'] = $spisok;
			break;
		//Цвета для фона
		case 70:
			$color = array(
				'#fff',
				'#ffffe4',
				'#e4ffe4',
				'#dff',
				'#ffe8ff',

				'#f9f9f9',
				'#ffb',
				'#cfc',
				'#aff',
				'#fcf',

				'#f3f3f3',
				'#fec',
				'#F2F2B6',
				'#D7EBFF',
				'#ffe4e4',

				'#ededed',
				'#FFDA8F',
				'#E3E3AA',
				'#B2D9FF',
				'#fcc'
			);

			$sel = '#fff';//выбранное значение
			if($unit_id)
				$sel = $unit[$cmp['col']];

			$spisok = '';
			for($n = 0; $n < count($color); $n++) {
				$cls = $sel == $color[$n] ? ' class="sel"' : '';
				$spisok .= '<div'.$cls.' style="background-color:'.$color[$n].'" val="'.$color[$n].'">'.
								'&#10004;'.
						   '</div>';
			}
			$dialog['cmp'][$cmp_id]['vvv'] = '<div class="_color-bg-choose">'.$spisok.'</div>';
*/
	}

	return array();
}

function _elemValue($elem_id) {//дополнительне значения к элементу select, настроенные через [19]
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=-".$elem_id."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	foreach($arr as $id => $r)
		$spisok[] = array(
			'id' => _num($id),
			'title' => $r['txt_1'],
			'content' => $r['txt_1'].'<div class="fs11 grey">'._br($r['txt_2']).'</div>'
		);

	return $spisok;
}
function _elemTitle($elem_id, $el_parent=array()) {//имя элемента или его текст
	if(!$elem_id = _num($elem_id))
		return '';
	if(!$el = _elemOne($elem_id))
		return '';
	if(!$el_parent)
		$el_parent = $el;

	switch($el['dialog_id']) {
		case 10: return $el['txt_1']; //произвольный текст
		case 11: //значение диалога
			$title = '';
			foreach(_ids($el['txt_2'], 1) as $n => $id)
				$title .= ($n ? ' » ' : '')._elemTitle($id, $el_parent);
			return $title;
		case 29: //связки
		case 59: return _dialogParam($el['num_1'], 'name');
		case 32: return 'номер';
		case 33: return 'дата/время';
		case 30: return 'del';
		case 34: return 'edit';
		case 60: return _imageNo($el_parent['width']);
		case 62: return 'Фильтр-галочка';
		case 67://шаблон истории действий
//			_BE('dialog_clear');
			$dlg = _dialogQuery($el['num_2']);
			return $dlg['history'][$el['num_1']]['tmp'];
		case 71: return 'sort';
	}
	return $el['name'];
}
function _elem_11_dialog($el) {//получение массива диалога по элементу 11
	if($el['dialog_id'] != 11)
		return 0;
	if(!$el11 = _elemOne($el['txt_2']))
		return 0;
	if($el11['block']['obj_name'] != 'dialog')
		return 0;

	$dialog_id = _num($el11['block']['obj_id']);

	if(!$dlg = _dialogQuery($dialog_id))
		return 0;

	return $dlg;
}
function _elem_11_v($EL, $ell_id, $unit) {//получение значения из единицы списка
	if(!$ell = _elemOne($ell_id))
		return _msgRed('-no-el11-'.$ell_id.'-');

	switch($ell['dialog_id']) {
		//многострочное поле
		case 5:
		//однострочное поле
		case 8:
			//отсутствует имя колонки
			if(!$col = $ell['col'])
				return _msgRed('no-col');
			//имени колонки не существует в единице списка
			if(!isset($unit[$col]))
				return _msgRed('no-unit-col');

			$txt = $unit[$col];
			$txt = _spisokUnitUrl($EL, $unit, $txt);
			$txt = _spisokColSearchBg($EL, $txt);

			return _br($txt);
		//произвольный текст
		case 10: return _br($ell['txt_1']);
		//сумма значений единицы списка (баланс)
		case 27:
		//количество связанного списка
		case 54:
		//сумма связанного списка
		case 55: return $unit[$ell['col']];
		//Изображение
		case 60:
			if(!$col = $ell['col'])
				return _msgRed('no-col-60');
			if(empty($unit))
				return _imageNo($EL['width']);

			$sql = "SELECT *
					FROM `_image`
					WHERE `obj_name`='elem_".$ell['id']."'
					  AND `obj_id`=".$unit['id']."
					  AND !`deleted`
					  AND !`sort`
					LIMIT 1";
			if(!$r = query_assoc($sql))
				return _imageNo($EL['width']);

			return _imageHtml($r, $EL['width'], $EL['num_7']);

	}

	return _msgRed('-no-11-');
}

function _elemColType($id='all') {//тип данных, используемый элементом
	$col_type = array(
		1 => 'txt',
		2 => 'num',
		3 => 'connect',
		4 => 'count',
		5 => 'cena',
		6 => 'sum',
		7 => 'date',
		8 => 'image'
	);

	if($id == 'all')
		return $col_type;
	if(!isset($col_type[$id]))
		return '';

	return $col_type[$id];
}


function _filterCheckSetup() {//настройка условий фильтра для галочки (подключение через [12])
	return '';
}





/* ---=== ВЫБОР ЗНАЧЕНИЯ ИЗ ДИАЛОГА [11] ===--- */
function PHP12_v_choose($el, $unit) {
	$SRC = $unit['source'];

	//изначально проверяется исходный диалог
	if(!$dialog_id = $SRC['dialog_source']) {
		if(!$SRC['block_id'])
			return _emptyMin('Отсутствует исходный блок.');

		//данные исходного блока
		$BL = _blockOne($SRC['block_id']);
		$EL = $BL['elem'];

		//ячейка таблицы
		if($EL && $EL['dialog_id'] == 23)
			$dialog_id = _num($EL['num_1']);
		//блок со страницы
		elseif($BL['obj_name'] == 'page') {
			$page = _page($BL['obj_id']);
			$dialog_id = $page['spisok_id'];
		}
		//элемент единицы списка
		elseif($BL['obj_name'] == 'spisok') {
			$el_spisok = _elemOne($BL['obj_id']);
			$dialog_id = $el_spisok['num_1'];
		}
	}

	//принимает ли страница значения списка
	if(!$dialog_id) {
		$page = _page($SRC['page_id']);
		$dialog_id = $page['spisok_id'];
	}

	if(!$dialog_id)
		return _emptyMin('Не найден диалог, который вносит данные списка.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin('Диалога не существует, который вносит данные списка.');

	$sel = 0;
	if(!empty($unit['txt_2'])) {
		$ex = explode(',', $unit['txt_2']);
		$sel = _num($ex[0]);
	}

	$cond = array(
		'v_choose' => 1,
		'v_id_sel' => $sel
	);

	return
	'<div class="fs14 pad10 pl15 bg-orange line-b">Диалоговое окно <b class="fs14">'.$dialog['name'].'</b>:</div>'.
	_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $cond).
//	_pr($unit).
	'';
}


/* ---=== ШАБЛОН ЕДИНИЦЫ СПИСКА [14] ===--- */
function PHP12_spisok14_setup($el, $unit) {//настройка шаблона
	/*
		имя объекта: spisok
		 id объекта: id элемента, который размещает список
	*/
	if(empty($unit['id']))
		return
		'<div class="bg-ffe pad10">'.
			'<div class="_empty min">'.
				'Настройка шаблона будет доступна после вставки списка в блок.'.
			'</div>'.
		'</div>';

	//определение ширины шаблона
	if(!$block = _blockOne($unit['block_id']))
		return 'Блока, в котором находится список, не существует.';

	setcookie('block_level_spisok', 1, time() + 2592000, '/');
	$_COOKIE['block_level_spisok'] = 1;

	//корректировка ширины с учётом отступов
	$ex = explode(' ', $unit['mar']);
	$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
	$line_r = $width < 980 ? ' line-r' : '';

	return
	'<div class="bg-ffc pad10 line-b">'.
		_blockLevelChange('spisok', $unit['id'], $width).
	'</div>'.
	'<div class="block-content-spisok'.$line_r.'" style="width:'.$width.'px">'.
		_blockHtml('spisok', $unit['id'], $width, 0, array('blk_edit' => 1)).
	'</div>';
}

/* ---=== ВЫБОР ЭЛЕМЕНТА [50] ===--- */
function PHP12_elem_choose($el, $unit) {//выбор элемента для вставки в блок. Диалог [50]
	if(empty($unit['source']))
		return _emptyMin('Отсутствуют исходные данные.');

	$SRC = $unit['source'];

	//данные исходного блока
	$BL = _blockOne($SRC['block_id']);
	$EL = $BL['elem'];

	define('OBJ_ID', _num($BL['obj_id']));

	//ячейка таблицы
	define('TD_UNIT', $EL && $EL['dialog_id'] == 23);

	//блок со страницы
	define('BLOCK_PAGE', !TD_UNIT && $BL['obj_name'] == 'page');

	//блок из диалога
	define('BLOCK_DIALOG', $BL['obj_name'] == 'dialog');

	//блок единицы списка
	define('BLOCK_SPISOK', $BL['obj_name'] == 'spisok');

	//сборный текст
	define('_44_UNIT', 0);

	//принимает ли страница значения единицы списка
	$spisok_id = 0;
	if(BLOCK_PAGE) {
		$page = _page(OBJ_ID);
		$spisok_id = $page['spisok_id'];
	}
	define('PAGE_SPISOK_UNIT', $spisok_id);

	//принимает ли диалог значения единицы списка
	define('DIALOG_SPISOK_UNIT', 0);

/*
	$BL['obj_name'] = $unit['source']['unit_id'] == -115 ? 'spisok' : '';
	if($block_id = _num($unit['source']['block_id'], 1))
		if(!$BL = _blockOne($block_id))
			return _emptyMin('Исходного блока id'.$block_id.' не существует.');

	define('_44_ACCESS', $unit['source']['unit_id'] == -111);//сборный текст, шаблон истории действий

	if(BLOCK_DIALOG) {
		$page = _page($unit['source']['page_id']);
		$spisok_exist = $page['spisok_id'];
	}
	define('IS_SPISOK_UNIT', BLOCK_SPISOK || TD_PASTE || $spisok_exist);
*/

	$head = '';
	$content = '';
	$sql = "SELECT *
			FROM `_dialog_group`
			WHERE `sa` IN (0,".SA.")
			ORDER BY `sort`";
	if(!$group = query_arr($sql))
		return _emptyMin('Отсутствуют группы элементов.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `element_group_id` IN ("._idsGet($group).")
			  AND `sa` IN (0,".SA.")
			ORDER BY `sort`,`id`";
	if(!$elem = query_arr($sql))
		return _emptyMin('Нет элементов для отображения.');

	//расстановка элементов в группы с учётом правил отображения
	foreach($elem as $id => $r) {
/*
		if(_44_ACCESS && !$r['element_paste_44'])
			continue;
//		if(IS_SPISOK_UNIT && !$r['element_is_spisok_unit'])
//			continue;
*/

		$show = false;

		if(BLOCK_PAGE && $r['element_paste_page']
		|| BLOCK_DIALOG && $r['element_paste_dialog']
		|| BLOCK_SPISOK && $r['element_paste_spisok']
		|| TD_UNIT && $r['element_paste_td']
		) $show = true;

//		if($r['element_is_spisok_unit'] && !IS_SPISOK_UNIT)
//			$show = false;

		if($show)
			$group[$r['element_group_id']]['elem'][] = $r;
	}

	foreach($group as $id => $r)
		if(empty($r['elem']))
			unset($group[$id]);

	if(empty($group))
		return _emptyMin('Нет элементов для отображения.');

	reset($group);
	$firstId = key($group);
	foreach($group as $id => $r) {
		$sel = _dn($id != $firstId, 'sel');
		$first = _dn($id != $firstId, 'first');
		$head .=
			'<table class="el-group-head'.$first.$sel.'" val="'.$id.'">'.
				'<tr>'.
	   ($r['img'] ? '<td class="w50 center"><img src="img/'.$r['img'].'">' : '').
					'<td class="fs14 '.($r['sa'] ? 'red pl5' : 'blue').'">'.$r['name'].
			'</table>';

		$content .= '<dl id="cnt_'.$id.'" class="cnt'._dn($id == $firstId).'">';
		$n = 1;
		foreach($r['elem'] as $el)
				$content .=
					'<dd val="'.$el['id'].'">'.
						'<div class="elem-unit '.($el['sa'] ? 'red' : 'color-555').'" val="'.$el['id'].'">'.
							'<table class="w100p">'.
								'<tr><td class="num w25 r top pr5 grey">'.$n++.'.'.
									'<td class="b top">'.$el['name'].
							  (SA ? '<td class="w50 top">'.
										'<div class="icon icon-move-y fr pl"></div>'.
								        '<div class="icon icon-edit fr pl mr3"></div>'
							  : '').
							'</table>'.
							'<div class="elem-img eli'.$el['id'].' mt5"></div>'.
						'</div>'.
					'</dd>';
		$content .=	'</dl>';
	}

	return
		'<table id="elem-group" class="w100p">'.
			'<tr><td class="w150 top prel">'.
					'<div id="head-back"></div>'.
					$head.
				'<td id="elem-group-content" class="top">'.
					'<div class="cnt-div">'.$content.'<div>'.
		'</table>'.
		PHP12_elem_choose_gebug($el, $unit).
		'';
}
function PHP12_elem_choose_gebug($el, $unit) {//выбор элемента - группы
	if(!DEBUG)
		return '';

	$SRC = $unit['source'];
	$block_id = $SRC['block_id'];
	$BL = _blockOne($SRC['block_id']);

	return
//	_pr($unit).
//	_pr($BL).
	'<div class="line-t pad10 bg-ffe">'.
		'<div class="'.(BLOCK_PAGE ? 'color-pay b' : 'pale').'">'.
			'Блок '.(BLOCK_PAGE ? $block_id : '').' на странице '.
			(BLOCK_PAGE ? OBJ_ID : '').
		'</div>'.

		'<div class="'.(PAGE_SPISOK_UNIT ? 'color-pay b' : 'pale').'">'.
			'Страница '.(PAGE_SPISOK_UNIT ? '' : 'не ').'принимает значения списка'.
			(PAGE_SPISOK_UNIT ? ' из диалога '.PAGE_SPISOK_UNIT : '').
		'</div>'.

		'<div class="mt10 '.(BLOCK_DIALOG ? 'color-pay b' : 'pale').'">'.
			'Блок '.(BLOCK_DIALOG ? $block_id : '').' в диалоге '.
			(BLOCK_DIALOG ? OBJ_ID : '').
		'</div>'.

		'<div class="'.(DIALOG_SPISOK_UNIT ? 'color-pay b' : 'pale').'">'.
			'Диалог '.(DIALOG_SPISOK_UNIT ? '' : 'не ').'принимает значения списка'.
		'</div>'.

		'<div class="mt10 '.(BLOCK_SPISOK ? 'color-pay b' : 'pale').'">'.
			'Блок '.(BLOCK_SPISOK ? $block_id : '').' из единицы списка'.
			(BLOCK_SPISOK ? '. Список размещён в блоке '.OBJ_ID : '').
		'</div>'.

		'<div class="'.(TD_UNIT ? 'color-pay b' : 'pale').'">'.
			'Ячейка таблицы'.
			(TD_UNIT ? '. Элемент(таблица) '.$BL['elem_id'].' размещён в блоке '.$block_id : '').
		'</div>'.

		'<div class="'.(_44_UNIT ? 'color-pay b' : 'pale').'">Сборный текст</div>'.
	'</div>';
}


/* ---=== НАСТРОЙКА ЯЧЕЕК ТАБЛИЦЫ ===--- */
function PHP12_spisok_td_setting($el, $unit) {//используется в диалоге [23]
	/*
		все действия через JS

		имя объекта: spisok
		 id объекта: block_id, в котором размещается список
	*/

//	return _pr($unit);

	if(empty($unit['id']))
		return '<div class="_empty min">Настройка таблицы будет доступна после вставки списка в блок.</div>';

	return '';
}
function PHP12_spisok_td_setting_save($cmp, $val, $unit) {//сохранение данных ячеек таблицы
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
/*
					`url`="._num($r['url']).",
*/
			$sql = "UPDATE `_element`
					SET `num_8`=1,
						`width`="._num($r['width']).",
						`font`='".$r['font']."',
						`color`='".$r['color']."',
						`txt_7`='".addslashes(_txt($r['txt_7']))."',
						`txt_8`='".$r['pos']."',
						`sort`=".$sort."
					WHERE `parent_id`=".$unit['id']."
					  AND `id`=".$id;
			query($sql);
		}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND !`num_8`";
	query($sql);
}
function PHP12_spisok_td_setting_vvv($parent_id) {//получение данных ячеек таблицы
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `num_8`
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'id' => _num($r['id']),
			'dialog_id' => _num($r['dialog_id']),
			'name' => _elemTitle($r['id']),
			'width' => _num($r['width']),
			'font' => $r['font'],
			'color' => $r['color'],
			'url' => _num($r['url']),
			'txt_7' => $r['txt_7'],
			'pos' => $r['txt_8']
		);
	}

	return $send;
}


/* ---=== НАСТРОЙКА МЕНЮ ПЕРЕКЛЮЧЕНИЯ БЛОКОВ ===--- */
function PHP12_menu_block_setup($el, $unit) {//используется в диалоге [57]
	if(_elemUnitIsEdit($unit))
		return '<div class="_empty min">Настройка пунктов меню переключения блоков</div>';
	return '';
}
function PHP12_menu_block_setup_vvv($parent_id) {//получение данных о пунктах меню
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	foreach($arr as $id => $r) {
		$c = count(_ids($r['txt_2'], 1));
		$blk_title = $r['txt_2'] ? $c.' блок'._end($c, '', 'а', 'ов') : '';
		$spisok[] = array(
			'id' => _num($id),
			'title' => $r['txt_1'],//название пункта меню
			'blk' => $r['txt_2'],//блоки
			'blk_title' => $blk_title,
			'def' => _num($r['def'])
		);
	}

	return $spisok;
}


/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ RADIO ===--- */
function PHP12_radio_setup($el, $unit) {//используется в диалоге [16]
	return '';
}
function PHP12_radio_setup_save($cmp, $val, $unit) {//сохранение значений radio
	/*
		$cmp  - компонент из диалога, отвечающий за настройку значений radio
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается radio

		Данные колонок таблицы записываются в _element
		parent_id = $unit['id'] (ID элемента-radio [16])
	*/

	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$content = _txt($r['content']);
			$update[] = "(
				".$id.",
				".$unit['id'].",
				'".addslashes($title)."',
				'".addslashes($content)."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`parent_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
}
function PHP12_radio_setup_vvv($parent_id) {
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'id' => _num($r['id']),
			'title' => $r['txt_1'],
			'content' => $r['txt_2'],
			'def' => _num($r['def']),
			'use' => 0
		);
	}

	$send = PHP12_radio_setup_vvv_use($send, $parent_id);

	return $send;
}
function PHP12_radio_setup_vvv_use($send, $parent_id) {//использование значений radio (чтобы нельзя было удалять значения)
	$el = _elemOne($parent_id);

	if(empty($el['block']))
		return $send;

	//пока только для диалогов
	if($el['block']['obj_name'] != 'dialog')
		return $send;
	if(!$dlg = _dialogQuery($el['block']['obj_id']))
		return $send;
	if(!$col = $el['col'])
		return $send;
	//только для таблиц, в которых есть колонка dialog_id
	if(empty($dlg['field1']['dialog_id']))
		return $send;

	//получение количества использования значений
	$sql = "SELECT
				`".$col."` `id`,
				COUNT(*) `use`
			FROM `"._table($dlg['table_1'])."`
			WHERE `dialog_id`=".$el['block']['obj_id']."
			GROUP BY `".$col."`";
	if($ass = query_ass($sql))
		foreach($send as $n => $r) {
			if(empty($ass[$r['id']]))
				continue;
			$send[$n]['use'] = $ass[$r['id']];
		}

	return $send;
}

/* ---=== ИСТОРИЯ ДЕЙСТВИЙ ===--- */
function _historySetup($el, $unit) {//настройка шаблона истории действий (подключение через [12])
	/*
		Заглавный элемент: -117
			num_1 - действие (type_id):
		              1 - запись внесена
		              2 - запись изменена
		              3 - запись удалена
			num_2 - id диалога, по которому настраивается шаблон
			txt_1 - список id дочерних элементов

		Дочерние элементы:
			txt_7 - текст слева от значения
			num_8 - значение из диалога
			txt_8 - текст справа от значения
	*/
	return '<input type="hidden" id="type_id" />';
}
function _historyInsert($type_id, $dialog, $unit_id) {//внесение истории действий
	//история не вносится, если единица списка удаляется физически из базы
	if(!isset($dialog['field1']['deleted']))
		return;

	$active = empty($dialog['history'][$type_id]['tmp_elm']) ? 0 : 1;

	$sql = "INSERT INTO `_history` (
				`app_id`,
				`type_id`,
				`dialog_id`,
				`unit_id`,
				`active`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$type_id.",
				".$dialog['id'].",
				".$unit_id.",
				".$active.",
				".USER_ID."
			)";
	query($sql);
}
function _historySpisok($el) {//список истории действий [68]
	$sql = "SELECT *
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `active`
			  "._historyCondPageUnit($el)."
			ORDER BY `dtime_add` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">Истории нет.</div>';

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `id` IN ("._idsGet($arr, 'unit_id').")";
	$spUnit = query_arr($sql);

	//распределение истории по дням
	$spisok = array();
	foreach($arr as $r) {
		$day = substr($r['dtime_add'], 0, 10);
		if(!isset($spisok[$day]))
			$spisok[$day] = array();
		$spisok[$day][] = $r;
	}

	$datFirst = key($spisok);
	$send = '';
	foreach($spisok as $day => $day_arr) {
		$send .= '<div class="history-day'._dn($day == $datFirst, 'pt20').'">'.FullData($day, 1, 0, 1).'</div>';

		$last = count($day_arr) - 1;
		$user_id =  $day_arr[0]['user_id_add'];
		$un = '';
		foreach($day_arr as $n => $r) {
			$dlg = _dialogQuery($r['dialog_id']);
			$msg = '';
			$unit = $spUnit[$r['unit_id']];
			foreach($dlg['history'][$r['type_id']]['tmp_elm'] as $el) {
				$colVal = '';
				if($col = $el['col']) {
					if(!isset($unit[$col]))
						continue;
					if(!$colVal = $unit[$col])
						continue;
					if($col == 'dtime_add')
						$colVal = _spisokUnitData($el, $unit);
				}
				$msg .= $el['txt_7'].$colVal.$el['txt_8'];
			}
			$un .= '<div class="history-un">'.
						'<div class="history-o o'.$r['type_id'].'"></div>'.
						'<span class="dib pale w35 mr5">'.substr($r['dtime_add'], 11, 5).'</span>'.
						$msg.
					'</div>';

			$is_user = $user_id != $r['user_id_add'];//изменился пользователь
			$is_last = $n == $last;//последняя запись

			if(!$is_user && !$is_last)
				continue;

			$send .=
				'<table class="mt5">'.
					'<tr><td class="top">'._user($r['user_id_add'], 'ava30').
						'<td class="top">'.
							'<div class="fs12 ml5 color-555">'._user($r['user_id_add'], 'name').'</div>'.
							$un.
				'</table>';

			$user_id = $r['user_id_add'];
			$un = '';
		}
	}

	return $send;
}
function _historyCondPageUnit($el) {//отображение истории для конкретной единицы списка, которую принимает страница (связка)
	if(!$el['num_8'])
		return '';

	//проверка, чтобы список был размещён именно на странице
	if($el['block']['obj_name'] != 'page')
		return ' AND !`id`';

	//страница, на которой размещён список
	if(!$page = _page($el['block']['obj_id']))
		return ' AND !`id`';

	//id диалога, единица списка которого размещается на странице
	if(!$spisok_id = $page['spisok_id'])
		return ' AND !`id`';

	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`id`';

	$ids = 0;

	//получение id единиц списка, которые были связаны с текущей единицей
	$sql = "SELECT `block_id`,`col`
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$spisok_id."
			  AND LENGTH(`col`)";
	if($cols = query_ass($sql)) {
		$cond = array();
		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id` IN ("._idsGet($cols, 'key').")";
		foreach(query_arr($sql) as $r)
			$cond[] = "`dialog_id`=".$r['obj_id']." AND `".$cols[$r['id']]."`=".$unit_id;

		if(!empty($cond)) {
			$sql = "SELECT `id`
					FROM `_spisok`
					WHERE ".implode(' OR ', $cond);
			$ids = query_ids($sql);
		}
	}

	return " AND `unit_id` IN (".$unit_id.",".$ids.")";
}








function _imageServerCache() {//кеширование серверов изображений
	$key = 'IMG_SERVER';
	if($arr = _cache_get($key, 1))
		return $arr;

	$sql = "SELECT `id`,`path` FROM `_image_server`";
	return _cache_set($key, query_ass($sql), 1);
}
function _imageServer($v) {//получение сервера (пути) для изображнения
/*
	если $v - число, получение имени пути
	если $v - текст, это сам путь и получение id пути. Если нет, то создание
*/
	if(empty($v))
		return '';

	$SRV = _imageServerCache();

	//получение id пути
	if($server_id = _num($v)) {
		if(empty($SRV[$server_id]))
			return '';

		return $SRV[$server_id];
	}

	foreach($SRV as $id => $path)
		if($v == $path)
			return $id;

	//внесение в базу нового пути
	$sql = "INSERT INTO `_image_server` (
				`path`,
				`user_id_add`
			) VALUES (
				'".addslashes($v)."',
				".USER_ID."
			)";
	query($sql);

	_cache_clear( 'IMG_SERVER', 1);

	return query_insert_id('_image_server');
}
function _imageNo($width=80) {//картинка, если изображнеия нет
	return '<img src="'.APP_HTML.'/img/nofoto-s.gif" width="'.$width.'" />';
}
function _imageHtml($r, $width=80, $h=0) {//получение картинки в html-формате
	$width = $width ? $width : 80;

	$st = $width > 80 ? 'max' : 80;
	$width = $width > $r['max_x'] ? $r['max_x'] : $width;
	if($h) {
		$s = _imageResize($r['max_x'], $r['max_y'], $width, $width);
		$width = $s['x'];
		$h = $s['y'];
	}

	return
		'<img src="'._imageServer($r['server_id']).$r[$st.'_name'].'"'.
			' width="'.$width.'"'.
	  ($h ? ' height= "'.$h.'"' : '').
			' class="image-open"'.
			' val="'.$r['id'].'"'.
		' />';
}
function _imageNameCreate() {//формирование имени файла из случайных символов
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//изменение размера изображения с сохранением пропорций
	$x = $x_new;
	$y = $y_new;
	// если ширина больше или равна высоте
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // если новая ширина больше, чем исходная, то X остаётся исходным
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // если новая высота в итоге осталась меньше исходной, то подравнивание по Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// если высота больше ширины
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // если новая высота больше, чем исходная, то Y остаётся исходным
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // если новая ширина в итоге осталась меньше исходной, то подравнивание по X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}

function _imageSave($obj_name, $obj_id, $file_type, $file_tmp_name) {
	$im = null;
	$IMAGE_PATH = APP_PATH.'/.image/'.APP_ID;
	$server_id = _imageServer('//'.DOMAIN.APP_HTML.'/.image/'.APP_ID.'/');

	//создание директории, если отсутствует
	if(!is_dir($IMAGE_PATH))
		mkdir($IMAGE_PATH, 0777, true);

	switch($file_type) {
		case 'image/jpeg': $im = @imagecreatefromjpeg($file_tmp_name); break;
		case 'image/png': $im = @imagecreatefrompng($file_tmp_name); break;
		case 'image/gif': $im = @imagecreatefromgif($file_tmp_name); break;
		case 'image/tiff':
			$tmp = $IMAGE_PATH.'/'.USER_ID.'.jpg';
			$image = NewMagickWand(); // magickwand.org
			MagickReadImage($image, $file_tmp_name);
			MagickSetImageFormat($image, 'jpg');
			MagickWriteImage($image, $tmp); //сохранение результата
			ClearMagickWand($image); //удаление и выгрузка полученного изображения из памяти
			DestroyMagickWand($image);
			$im = @imagecreatefromjpeg($tmp);
			unlink($tmp);
			break;
	}


	if(!$im)
		jsonError('Загруженный файл не является изображением.<br>Выберите JPG, PNG, GIF или TIFF формат.');

	$x = imagesx($im);
	$y = imagesy($im);
	if($x < 100 || $y < 100)
		jsonError('Изображение слишком маленькое.<br>Используйте размер не менее 100х100 px.');

	$fileName = time().'-'._imageNameCreate();
	$NAME_MAX = $fileName.'-900.jpg';
	$NAME_80 = $fileName.'-80.jpg';

	$max = _imageImCreate($im, $x, $y, 900, 900, $IMAGE_PATH.'/'.$NAME_MAX);
	$_80 = _imageImCreate($im, $x, $y, 80, 80, $IMAGE_PATH.'/'.$NAME_80);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,0)
			FROM `_image`
			WHERE !`deleted`
			  AND `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	$sort = query_value($sql);

	$sql = "INSERT INTO `_image` (
				`server_id`,

				`max_name`,
				`max_x`,
				`max_y`,
				`max_size`,

				`80_name`,
				`80_x`,
				`80_y`,
				`80_size`,

				`obj_name`,
				`obj_id`,

				`sort`,
				`user_id_add`
			) VALUES (
				".$server_id.",

				'".$NAME_MAX."',
				".$max['x'].",
				".$max['y'].",
				".$max['size'].",

				'".$NAME_80."',
				".$_80['x'].",
				".$_80['y'].",
				".$_80['size'].",

				'".$obj_name."',
				".$obj_id.",

				".$sort.",
				".USER_ID."
		)";
	query($sql);

	$image_id = query_insert_id('_image');

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	return query_assoc($sql);
}
function _imageDD($img) {//единица изображения для настройки
	return
	'<dd class="dib mr3 curM" val="'.$img['id'].'">'.
		'<div class="icon icon-off'._tooltip('Переместить в корзину', -70).'</div>'.
		'<table class="_image-unit">'.
			'<tr><td>'.
				_imageHtml($img, 80, 1).
		'</table>'.
	'</dd>';
}

function _imageShow($el, $unit) {//просмотр изображений (вставляется в блок через [12])
	$image = 'Изображение отсутствует.';//основная картинка, на которую нажали. Выводится первой
	$spisok = '';//html-список дополнительных изображений
	$spisokJs = array();//js-список всех изображений
	$spisokIds = array();//id картинок по порядку
	if($image_id = _num(@$unit['id'])) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id`=".$image_id;
		if($im = query_assoc($sql)) {
			$image = '<img src="'._imageServer($im['server_id']).$im['max_name'].'"'.
						 ' width="'.$im['max_x'].'"'.
						 ' height="'.$im['max_y'].'"'.
						 ' />';

			$sql = "SELECT *
					FROM `_image`
					WHERE `obj_name`='".$im['obj_name']."'
					  AND `obj_id`=".$im['obj_id']."
					  AND `deleted`=".$im['deleted']."
					ORDER BY `".($im['deleted'] ? 'dtime_del' : 'sort')."`";
			$arr = query_arr($sql);
			if(count($arr) > 1) {
				$spisok = '<div class="line-t pad10 center bg-gr2">';
				foreach($arr as $r) {
					$sel = $r['id'] == $image_id ? ' sel' : '';
					$spisok .=
					'<div class="dib ml3 mr3">'.
						'<table class="iu'.$sel.'" val="'.$r['id'].'">'.
							'<tr><td><img src="'._imageServer($r['server_id']).$r['80_name'].'"'.
										' width="'.$r['80_x'].'"'.
										' height="'.$r['80_y'].'"'.
									' />'.
						'</table>'.
					'</div>';
					$spisokJs[] = $r['id'].':{'.
						'src:"'.addslashes(_imageServer($r['server_id']).$r['max_name']).'",'.
						'x:'.$r['max_x'].','.
						'y:'.$r['max_y'].','.
					'}';
					$spisokIds[] = $r['id'];
				}
				$spisok .= '</div>';
			}

		}
	}

	return
	'<div id="_image-show">'.
		'<table class="w100p">'.
			'<tr><td id="_image-main" val="'.$image_id.'">'.
					$image.
		'</table>'.
		$spisok.
	'</div>'.
	'<script>'.
		'var IMG_ASS={'.implode(',', $spisokJs).'},'.
			'IMG_IDS=['.implode(',', $spisokIds).'];'.
	'</script>';
}
function _imageDeleted($el, $unit) {//удалённые изображения (вставляется в блок через [12])
	if(!$unit_id = _num(@$unit['id']))
		return '<div class="_empty min">Отсутствует единица списка, к которой прикрепляются изображения.</div>';
	if(!$block_id = _num($unit['source']['block_id'], 1))
		return '<div class="_empty min">Отсутствует id блока.</div>';
	if($block_id > 0)
		return '<div class="_empty min">Id блока не может быть положительным.</div>';

	$sql = "SELECT *
			FROM `_image`
			WHERE `obj_name`='elem_".abs($block_id)."'
			  AND `obj_id`=".$unit_id."
			  AND `deleted`
			ORDER BY `dtime_del`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">Удалённых изображений нет.</div>';

	$html = '';
	foreach($arr as $r) {
		$html .=
		'<div class="prel dib ml3 mr3">'.
			'<div val="'.$r['id'].'" class="icon icon-recover'._tooltip('Восстановить', -43).'</div>'.
			'<table class="_image-unit">'.
				'<tr><td>'.
					_imageHtml($r, 80, 1).
			'</table>'.
		'</div>';
	}

	return '<div class="_image">'.$html.'</div>';
}
function _imageWebcam($el) {//Веб-камера (вставляется в блок через [12])
	$width = $el['block']['width'];
	$mar = explode(' ', $el['mar']);
	$width = round($width - $mar[1] - $mar[3]);
	$height = round($width * 0.75);

	$flashvars =
		'width='.$width.
		'&height='.$height.
		'&dest_width='.$width.
		'&dest_height='.$height.
		'&image_format=jpeg'.
		'&jpeg_quality=100'.
		'&enable_flash=true'.
		'&force_flash=false'.
		'&flip_horiz=false'.
		'&fps=30'.
		'&upload_name=webcam'.
		'&constraints=null'.
		'&swfURL=""'.
		'&flashNotDetectedText=""'.
		'&noInterfaceFoundText=""'.
		'&unfreeze_snap=true'.
		'&iosPlaceholderText=""'.
		'&user_callback=null'.
		'&user_canvas=null';

	return
	'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"'.
			' type="application/x-shockwave-flash"'.
	        ' codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0"'.
	        ' width="'.$width.'"'.
	        ' height="'.$height.'"'.
	        ' align="middle">'.
	            '<param name="wmode" value="opaque" />'.
				'<param name="allowScriptAccess" value="always" />'.
				'<param name="allowFullScreen" value="false" />'.
				'<param name="movie" value="" />'.
				'<param name="loop" value="false" />'.
				'<param name="menu" value="false" />'.
				'<param name="quality" value="best" />'.
				'<param name="bgcolor" value="#ffffff" />'.
				'<param name="flashvars" value="'.$flashvars.'" />'.
				'<embed src="'.APP_HTML.'/modul/element/webcam.swf?2"'.
					  ' wmode="opaque" loop="false" menu="false" quality="best" bgcolor="#ffffff" width="'.$width.'" height="'.$height.'" name="webcam_movie_embed" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="'.$flashvars.'">'.
				'</embed>'.
	'</object>';
}








function _filterCalendar($el) {//Фильтр-календарь
	if(!$v = _spisokFilter('v', $el['id'])) {
		$v = _calendarWeek();
		_spisokFilter('insert', array(
			'spisok' => $el['num_1'],
			'filter' => $el['id'],
			'v' => $v
		));
	}

	$mon = substr($v, 0, 7);

	return
	'<div class="_filter-calendar">'.
		'<div class="_busy"></div>'.
		'<input type="hidden" class="mon-cur" value="'.$mon.'" />'.

		'<table class="w100p">'.
			'<tr><td class="laquo" val="0">&laquo;'.
				'<td class="td-mon">'._filterCalendarMon($mon).
				'<td class="laquo" val="1">&raquo;'.
		'</table>'.

		'<div class="fc-cnt">'._filterCalendarContent($el, $mon, $v).'</div>'.
	'</div>';
}
function _filterCalendarMon($mon) {//имя месяца и год
	$ex = explode('-', $mon);
	return _monthDef($ex[1]).' '.$ex[0];
}
function _filterCalendarContent($el, $mon, $v) {
	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);   //Количество дней в месяце
	$week = date('w', $unix);       //Номер первого дня недели
	if(!$week)
		$week = 7;

	$days = _filterCalendarDays($el, $mon);

	$weekNum = intval(date('W', $unix));    // Номер недели с начала месяца

	$range = _calendarWeek($mon.'-01');
	$send = '<tr'.($range == $v ? ' class="sel"' : '').'>'.
				'<td class="week-num" val="'.$range.'">'.$weekNum;

	//Вставка пустых полей, если первый день недели не понедельник
	for($n = $week; $n > 1; $n--)
		$send .= '<td>';

	for($n = 1; $n <= $dayCount; $n++) {
		$day = $mon.'-'.($n < 10 ? '0' : '').$n;
		$cur = TODAY == $day ? ' b' : '';
		$on = empty($days[$day]) ? '' : ' on';
		$old = $unix + $n * 86400 <= TODAY_UNIXTIME ? ' grey' : '';
		$sel = $day == $v ? ' sel' : '';
		$val = $on ? ' val="'.$day.'"' : '';
		$send .= '<td class="d '.$cur.$on.$old.$sel.'"'.$val.'>'.$n;
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount) {
			$range = _calendarWeek($mon.'-'.($n + 1 < 10 ? 0 : '').($n + 1));
			$send .= '<tr'.($range == $v ? ' class="sel"' : '').'>'.
						'<td class="week-num" val="'.$range.'">'.(++$weekNum);
		}
	}

	//Вставка пустых полей, если последняя неделя месяца заканчивается не воскресеньем
	if($week > 1)
		for($n = $week; $n <= 7; $n++)
			$send .= '<td>';

	return
	'<table class="w100p ">'.
		'<tr class="week-name">'.
			'<th>&nbsp;<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс'.
		$send.
	'</table>';
}
function _filterCalendarDays($el, $mon) {//отметка дней в календаре, по которым есть записи
	if(!$elem = _elemOne($el['num_1']))
		return array();
	if(!$dlg = _dialogQuery($elem['num_1']))
		return array();

	$cond = "`dtime_add` LIKE ('".$mon."%')";
	if(isset($dlg['field1']['app_id']))
		$cond .= " AND `app_id`=".APP_ID;
	if(isset($dlg['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dlg['id'];
	if(isset($dlg['field1']['deleted']))
		$cond .= " AND !`deleted`";

	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d'),1
			FROM `"._table($dlg['table_1'])."`
			WHERE ".$cond."
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
	return query_ass($sql);
}

function _calendarFilter($data=array()) {
	$data = array(
		'upd' => empty($data['upd']), // Обновлять существующий календать? (при перемотке масяцев)
		'month' => empty($data['month']) ? strftime('%Y-%m') : $data['month'],
		'sel' => empty($data['sel']) ? '' : $data['sel'],
		'days' => empty($data['days']) ? array() : $data['days'],
		'func' => empty($data['func']) ? '' : $data['func'],
		'noweek' => empty($data['noweek']) ? 0 : 1,
		'norewind' => !empty($data['norewind'])
	);
	$ex = explode('-', $data['month']);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];
	$days = $data['days'];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$send =
	($data['upd'] ?
		'<div class="_calendarFilter">'.
			'<input type="hidden" class="func" value="'.$data['func'].'" />'.
			'<input type="hidden" class="noweek" value="'.$data['noweek'].'" />'.
			'<input type="hidden" class="selected" value="'.$data['sel'].'" />'.
		'<div class="content">'
	: '').
		'<table class="data">'.
			'<tr>'.($data['norewind'] ? '' : '<td class="ch" val="'.$back.'">&laquo;').
				'<td><a val="'.$data['month'].'"'.($data['month'] == $data['sel'] ? ' class="sel"' : '').'>'._monthDef($SHOW_MON).'</a> '.
					($data['norewind'] ? '' :
						'<a val="'.$SHOW_YEAR.'"'.($SHOW_YEAR == $data['sel'] ? ' class="sel"' : '').'>'.$SHOW_YEAR.'</a>'.
					'<td class="ch" val="'.$next.'">&raquo;').
		'</table>'.
		'<table class="month">'.
			'<tr class="week-name">'.
				($data['noweek'] ? '' :'<th>&nbsp;').
				'<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс';

	$unix = strtotime($data['month'].'-01');
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);       // Номер первого дня недели
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // Текущий день для выделения прошедших дней
	$weekNum = intval(date('W', $unix));    // Номер недели с начала месяца

	$range = _calendarWeek($data['month'].'-01');
	$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
		($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.$weekNum);
	for($n = $week; $n > 1; $n--, $send .= '<td>'); // Вставка пустых полей, если первый день недели не понедельник
	for($n = 1; $n <= $dayCount; $n++) {
		$day = $data['month'].'-'.($n < 10 ? '0' : '').$n;
		$cur = $curDay == $day ? ' cur' : '';
		$on = empty($days[$day]) ? '' : ' on';
		$old = $unix + $n * 86400 <= $curUnix ? ' old' : '';
		$sel = $day == $data['sel'] ? ' sel' : '';
		$val = $on ? ' val="'.$day.'"' : '';
		$send .= '<td class="d '.$cur.$on.$old.$sel.'"'.$val.'>'.$n;
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount) {
			$range = _calendarWeek($data['month'].'-'.($n + 1 < 10 ? 0 : '').($n + 1));
			$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
				($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.(++$weekNum));
		}
	}
	if($week > 1)
		for($n = $week; $n <= 7; $n++, $send .= '<td>'); // Вставка пустых полей, если день заканчивается не воскресеньем
	$send .= '</table>'.($data['upd'] ? '</div></div>' : '');

	return $send;
}
function _calendarDataCheck($data) {
	if(empty($data))
		return false;
	if(preg_match(REGEXP_DATE, $data) || preg_match(REGEXP_YEARMON, $data) || preg_match(REGEXP_YEAR, $data))
		return true;
	$ex = explode(':', $data);
	if(preg_match(REGEXP_DATE, $ex[0]) && preg_match(REGEXP_DATE, @$ex[1]))
		return true;
	return false;
}
function _calendarPeriod($data) {// Формирование периода для элементов массива запросившего фильтра
	$send = array(
		'period' => $data,
		'day' => '',
		'from' => '',
		'to' => ''
	);
	if(!_calendarDataCheck($data))
		return $send;
	$ex = explode(':', $data);
	if(empty($ex[1]))
		return array('day'=>$ex[0]) + $send;
	return array(
		'from' => $ex[0],
		'to' => $ex[1]
	) + $send;
}
function _calendarWeek($day=TODAY) {// Формирование периода за неделю недели
	$d = explode('-', $day);
	$month = $d[0].'-'.$d[1];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);
	if(!$week)
		$week = 7;

	$dayStart = $d[2] - $week + 1; // Номер первого дня недели
	if($dayStart < 1) {
		$back = $d[1] - 1;
		$back = !$back ? ($d[0] - 1).'-12' : $d[0].'-'.($back < 10 ? 0 : '').$back;
		$start = $back.'-'.(date('t', strtotime($back.'-01')) + $dayStart);
	} else
		$start = $month.'-'.($dayStart < 10 ? 0 : '').$dayStart;

	$dayEnd = 7 - $week + $d[2]; // Номер последнего дня недели
	if($dayEnd > $dayCount) {
		$next = $d[1] + 1;
		$next = $next > 12 ? ($d[0] + 1).'-01' : $d[0].'-'.($next < 10 ? 0 : '').$next;
		$end = $next.'-0'.($dayEnd - $dayCount);
	} else
		$end = $month.'-'.($dayEnd < 10 ? 0 : '').$dayEnd;

	return $start.':'.$end;
}
function _period($v=0, $action='get') {// Формирование периода для элементов массива запросившего фильтра
	/*
		$i: get, sql
	*/

	if(empty($v))
		$v = _calendarWeek();

	switch($action) {
		case 'get': return $v;
		case 'sql':
			$ex = explode(':', $v);
			if(empty($ex[1]))
				return " AND `dtime_add` LIKE '".$v."%'";
			return " AND `dtime_add`>='".$ex[0]." 00:00:00' AND `dtime_add`<='".$ex[1]." 23:59:59'";
		default: return '';
	}
}







function _filterMenu($el) {//фильтр-меню
	if(!$el['num_2'])
		return _emptyMin('Фильтр-меню: отсутствует ID элемента, содержащий значения.');
	if(!$ell = _elemOne($el['num_2']))
		return _emptyMin('Фильтр-меню: отсутствует элемент, содержащий значения.');
	if(!$ids = _ids($ell['txt_2'], 1))
		return _emptyMin('Фильтр-меню: отсутствуют ID значений.');

	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemOne($elem_id))
		return _emptyMin('Фильтр-меню: значение отсутствует.');
	if(!$BL = $EL['block'])
		return _emptyMin('Фильтр-меню: нет блока.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMin('Фильтр-меню: блок не из диалога.');
	if(!$dialog_id = $BL['obj_id'])
		return _emptyMin('Фильтр-меню: нет ID диалога.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin('Фильтр-меню: нет диалога.');

	$col = $EL['col'];//колонка текстового значения
	$colCount = '';//колонка значения количества
	if($el3 = _elemOne($el['num_3']))
		if($ids = _ids($el3['txt_2'], 1)) {
			$c = count($ids) - 1;
			$elem_id = $ids[$c];
			if($EL3 = _elemOne($elem_id))
				$colCount = $EL3['col'];
		}

	$cond = " `id`";
	if(isset($dialog['field1']['deleted']))
		$cond .= " AND !`deleted`";
	if(isset($dialog['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;
	$sql = "SELECT *
			FROM `"._table($dialog['table_1'])."`
			WHERE ".$cond."
			ORDER BY `sort`,`id`";
	if(!$arr = query_arr($sql))
		return _emptyMin('Фильтр-меню: пустое меню.');

	$send = '';
	$v = _spisokFilter('v', $el['id']);

	$spisok = array();
	foreach($arr as $r)
		$spisok[$r['parent_id']][] = $r;

	foreach($spisok[0] as $r) {
		$child = '';
		$child_sel = false;//список будет раскрыт, если в нём был выбранное значение
		if(!empty($spisok[$r['id']]))
			foreach($spisok[$r['id']] as $c) {
				$sel = $v == $c['id'] ? ' sel' : '';
				if($sel)
					$child_sel = true;
				$child .= '<div class="fm-unit'.$sel.'" val="'.$c['id'].'">'.
							$c[$col].
							($colCount ? '<span class="ml10 pale b">'.$c[$colCount].'</span>' : '').
						'</div>';
			}

		$sel = $v == $r['id'] ? ' sel' : '';
		$send .=
			'<table class="w100p">'.
				'<tr>'.
		  ($child ? '<td class="fm-plus">'.($child_sel ? '-' : '+') : '<td class="w25">').//—
					'<td><div class="fm-unit b fs14'.$sel.'" val="'.$r['id'].'">'.
							$r[$col].
							($colCount ? '<span class="ml10 pale b">'.$r[$colCount].'</span>' : '').
						'</div>'.
			'</table>'.
			($child ? '<div class="ml40'._dn($child_sel).'">'.$child.'</div>' : '');
	}

	return $send;
}








function _note($el) {//заметки
	$page_id = _page('cur');
	$obj_id = _num(@$_GET['id']);
	return
	'<div class="_note" val="'.$page_id.':'.$obj_id.'">'.
		'<div class="prel">'.
			'<div class="note-ok"></div>'.
			'<div class="icon icon-ok spin"></div>'.
			'<div class="_note-txt">'.
				'<textarea placeholder="напишите заметку..." /></textarea>'.
			'</div>'.
		'</div>'.
		'<div class="_note-list">'._noteList($page_id, $obj_id).'</div>'.
	'</div>';
}
function _noteList($page_id, $obj_id) {
	$sql = "SELECT *
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`
			  AND !`deleted`
			  AND `page_id`=".$page_id."
			  AND `obj_id`=".$obj_id."
			ORDER BY `id` DESC";
	if(!$arr = query_arr($sql))
		return '';

	foreach($arr as $id => $r)
		$arr[$id]['comment'] = array();

	$sql = "SELECT *
			FROM `_note`
			WHERE `parent_id` IN ("._idsGet($arr).")
			  AND !`deleted`
			ORDER BY `id`";
	foreach(query_arr($sql) as $r)
		$arr[$r['parent_id']]['comment'][] = $r;

	$send = '';
	$n = 0;
	foreach($arr as $r) {
		$cmnt = $r['comment'] ? 'Комментарии '.count($r['comment']) : 'Комментировать';
		$comment = '';
		foreach($r['comment'] as $c)
			$comment .= _noteCommentUnit($c);
		$send .=
			'<div class="_note-u'._dn(!$n, 'line-t').'" val="'.$r['id'].'">'.
				'<div class="_note-is-show">'.
					'<table class="bs10 w100p">'.
						'<tr><td class="w35">'.
								'<img class="ava40" src="'._user($r['user_id_add'], 'src').'">'.
							'<td>'.
								'<div class="note-del icon icon-del pl fr'._tooltip('Удалить заметку', -91, 'r').'</div>'.
								'<div val="dialog_id:81,unit_id:'.$r['id'].'" class="dialog-open icon icon-edit pl fr'._tooltip('Изменить заметку', -98, 'r').'</div>'.
								'<a class="b">'._user($r['user_id_add'], 'name').'</a>'.
								'<div class="pale mt3">'.FullDataTime($r['dtime_add'], 1).'</div>'.
						'<tr>'.
							'<td colspan="2" class="fs14">'._br($r['txt']).
					'</table>'.
					'<div class="_note-to-cmnt dib b over1'._dn($n).'">'.
						'<div class="icon icon-comment"></div>'.
						$cmnt.
					'</div>'.
					'<div class="_note-comment'._dn(!$n).'">'.
						$comment.
						'<table class="w100p">'.
							'<tr><td><div class="_comment-txt">'.
										'<textarea placeholder="комментировать.." /></textarea>'.
									'</div>'.
								'<td class="w35 bottom">'.
									'<div class="icon icon-empty spin ml5 mb5"></div>'.
									'<div class="comment-ok"></div>'.
						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="_note-is-del">'.
					'Заметка удалена.'.
					'<a class="note-rest ml10">Восстановить</a>'.
				'</div>'.
			'</div>';
		$n++;
	}

	return $send;
}
function _noteCommentUnit($c) {//html одного комментария
	return
	'<div class="_comment-u">'.
		'<table class="_comment-is-show bs5 w100p">'.
			'<tr><td class="w35">'.
					'<img class="ava30" src="'._user($c['user_id_add'], 'src').'">'.
				'<td>'.
					'<div class="_note-icon fr mr5">'.
						'<div val="dialog_id:82,unit_id:'.$c['id'].'" class="dialog-open icon icon-edit pl"></div>'.
						'<div class="comment-del icon icon-del pl" onclick="_noteCDel(this,'.$c['id'].')"></div>'.
					'</div>'.
					'<a class="fs12">'._user($c['user_id_add'], 'name').'</a>'.
					'<div class="fs12 pale mt2">'.FullDataTime($c['dtime_add'], 1).'</div>'.
			'<tr>'.
				'<td colspan="2">'._br($c['txt']).
		'</table>'.
		'<div class="_comment-is-del">'.
			'Комментарий удалён.'.
			'<a class="comment-rest ml10" onclick="_noteCRest(this,'.$c['id'].')">Восстановить</a>'.
		'</div>'.
	'</div>';
}





