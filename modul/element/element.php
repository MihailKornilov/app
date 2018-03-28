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

function _tooltip($msg, $left=0, $ugolSide='', $x2=0) {
	//x2: в две строки
	$x2 = $x2 ? ' x2' : '';
	return
		' _tooltip">'.
		'<div class="ttdiv'.$x2.'"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
			'<div class="ttmsg">'.$msg.'</div>'.
			'<div class="ttug'.($ugolSide ? ' '.$ugolSide : '').'"></div>'.
		'</div>';
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
	$cls = empty($v['class']) ? '' : ' '.$v['class'];

	return '<div'.$click.$val.' class="icon icon-del'.$cls._tooltip('Удалить', -42, 'r').'</div>';
}

function _check($v=array()) {//элемент ГАЛОЧКА
	$attr_id = empty($v['attr_id']) ? 'check'.rand(1, 100000) : $v['attr_id'];

	$cls = '_check ';
	$cls .= empty($v['block']) ?    '' : ' block';       //display:block, иначе inline-block
	$cls .= empty($v['disabled']) ? '' : ' disabled';    //неактивное состояние
	$cls .= isset($v['light']) && empty($v['light']) ?    '' : ' light';       //если галочка не стоит, текст бледный
	$cls .= empty($v['class']) ?    '' : ' '.$v['class'];//дополнительные классы

	$val = _bool(@$v['value']);
	$cls .= $val ? ' on' : '';      //галочка поставлена или нет

	$title = empty($v['title']) ? '&nbsp;' : $v['title'];
	$cls .= empty($v['title']) ? '' : ' title'; //отступ от галочки, если есть текст

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />'.
	'<div class="'.$cls.'" id="'.$attr_id.'_check">'.
		$title.
	'</div>';
}
function _radio($v=array()) {//элемент RADIO
	$attr_id = empty($v['attr_id']) ? 'radio'.rand(1, 100000) : $v['attr_id'];
	$title0 = @$v['title0'];
	$spisok = @$v['spisok'] ? $v['spisok'] : array();//содержание в виде id => title
	$value = _num(@$v['value']);
	$dis = empty($v['disabled']) ? '' : ' disabled';
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;

	//если список пуст и только нулевое значение, отступ снизу не делается
	$int = empty($spisok) ? 0 : $interval;
	$html = _radioUnit(0, $block, $title0, $int, $value == 0);

	if(is_array($spisok) && !empty($spisok)) {
		end($spisok);
		$idEnd = key($spisok);
		foreach($spisok as $id => $title) {
			//отступ снизу после последнего значения не делается
			$int = $idEnd == $id ? 0 : $interval;
			$html .= _radioUnit($id, $block, $title, $int, $value == $id);
		}
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$light.'">'.
		$html.
	'</div>';
}
function _radioUnit($id, $block, $title, $interval, $on) {
	if(empty($title))
		return '';

	$title0 = !$id ? 'title0' : '';
	$on = $on ? ' on' : '';
	$ms = $block ? 'bottom' : 'right';
	$interval = $block ? $interval : 12;
	$interval = $interval ? ' style="margin-'.$ms.':'.$interval.'px"' : '';
	return
	'<div class="'.$title0.$on.'" val="'.$id.'"'.$interval.'>'.
		$title.
	'</div>';
}
function _select($v=array()) {//выпадающее поле
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_select disabled dib" id="'.$attr_id.'_select"'.$width.'">'.
		'<table class="w100p">'.
			'<tr><td><input type="text" class="select-inp"'.$placeholder.' readonly />'.
				'<td class="arrow">'.
		'</table>'.
	'</div>';
}
function _dropdown($v=array()) {//выпадающее поле - ссылка
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$value = _num(@$v['value']);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_dropdown"></div>';
}
function _count($v=array()) {//поле количество
	$attr_id = empty($v['attr_id']) ? 'select'.rand(1, 100000) : $v['attr_id'];

	$width = '50px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$value = _num(@$v['value']);
	return
	'<div class="_count disabled" id="'.$attr_id.'_count"'.$width.'>'.
		'<input type="text" readonly id="'.$attr_id.'" value="'.$value.'" />'.
		'<div class="but"></div>'.
		'<div class="but but-b"></div>'.
	'</div>';
}
function _calendar($v=array()) {//поле Календарь
	$attr_id = empty($v['attr_id']) ? 'calendar'.rand(1, 100000) : $v['attr_id'];

	if(!$value = @$v['value'])
		$value = strftime('%Y-%m-%d');

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div class="_calendar disabled" id="'.$attr_id.'_calendar">'.
		'<div class="icon icon-calendar"></div>'.
		'<input type="text" class="cal-inp" readonly value="'.FullData($value).'" />'.
	'</div>';
}
function _search($v=array()) {//элемент ПОИСК
	$attr_id = empty($v['attr_id']) ? 'search'.rand(1, 100000) : $v['attr_id'];

	$width = '150px';
	if(isset($v['width']))
		if(!$width = _num($v['width']))
			$width = '100%';
		else
			$width .= 'px';
	$width = ' style="width:'.$width.'"';

	$dis = empty($v['disabled']) ? '' : ' disabled';
	$readonly = $dis ? ' readonly' : '';
	$placeholder = empty($v['placeholder']) ? '' : ' placeholder="'.trim($v['placeholder']).'"';
	$v = trim(@$v['v']);

	return
	'<div class="_search'.$dis.'"'.$width.' id="'.$attr_id.'_search">'.
		'<table class="w100p">'.
			'<tr><td class="w15 pl5">'.
					'<div class="icon icon-search curD"></div>'.
				'<td><input type="text" id="'.$attr_id.'"'.$placeholder.$readonly.' value="'.$v.'" />'.
				'<td class="w25 center">'.
					'<div class="icon icon-del pl'._dn($v).'"></div>'.
		'</table>'.
	'</div>';
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

function _baseTable($id=false) {//таблицы в базе с соответствующими идентификаторами
	$tab = array(
		 1 => '_app',
		 2 => '_block',
		 3 => '_dialog',
		 4 => '_dialog_group',
		 5 => '_element',
		 6 => '_element_func',
		 7 => '_history',
		 8 => '_image',
		 9 => '_image_server',
		10 => '_page',
		11 => '_spisok',
		12 => '_user',
//		13 => '_user_app',
		14 => '_user_auth',
		15 => '_user_spisok_filter'
	);

	if($id === false)
		return $tab;
	if(!$id = _num($id))
		return false;
	if(!isset($tab[$id]))
		return false;

	return $tab[$id];
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

	//обновление кеша объекта, в котором находится блок с кнопкой
	$bl = _blockQuery($block_id);
	_cache('clear', $bl['obj_name'].'_'.$bl['obj_id']);

	return $dialog_id;
}
function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	if(!$dialog_id = _num($dialog_id))
		return array();

	if($dialog = _cache())
		return $dialog;


	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			  AND `id`=".$dialog_id;
	if(!$dialog = query_assoc($sql))
		return array();


	//список колонок, присутствующих в таблице 1
	$field = array();
	if($dialog['table_1']) {
		$sql = "DESCRIBE `"._baseTable($dialog['table_1'])."`";
		foreach(query_array($sql) as $r)
			$field[$r['Field']] = 1;
	}
	$dialog['field1'] = $field;

	//список колонок, присутствующих в таблице 2
	$field = array();
	if($dialog['table_2']) {
		$sql = "DESCRIBE `"._baseTable($dialog['table_2'])."`";
		foreach(query_array($sql) as $r)
			$field[$r['Field']] = 1;
	}
	$dialog['field2'] = $field;

//	return _cache($dialog);

//	_cache('clear', 'dialog_'.$dialog_id);
	$dialog['blk'] = _block('dialog', $dialog_id, 'block_arr');
	$dialog['cmp'] = _block('dialog', $dialog_id, 'elem_arr');

	//id заглавных элементов настройки шаблона истории действий
	foreach(array(1,2,3) as $n) {
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
//echo $dialog_id;exit;

	return _cache($dialog);
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


	//получение id диалога, который является списком, чтобы было нельзя его выбирать в самом себе (для связок)
	$dialog = _dialogQuery($dialog_id);
	if(_baseTable($dialog['table_1']) == '_element') {
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
	$block_id - исходный блок, по которому определяется объект
*/

	if(!$block = _blockQuery($block_id))
		return array();

	//списки размещаются при помощи диалогов 14 и 23
	//идентификаторами результата являются id элементов (а не диалогов)

	if(!$elm = _block($block['obj_name'], $block['obj_id'], 'elem_arr'))
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
		if(!$EL = _elemQuery($elem_id))
			return array();
		$block_id = $EL['block_id'];
	}

	if(!$BL = _blockQuery($block_id))
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
function _dialogElemChoose($el, $unit) {//выбор элемента (подключение через [12]). Используется в [74] для [13]
	if(empty($unit['source']['dialog_source']))
		return _emptyMin('Отсутствует исходный диалог.');

/*
	$block_id = _num($unit['source']['block_id']);

	if(!$BL = _blockQuery($block_id))
		return _emptyMin('Исходного блока id'.$block_id.' не существует.');

	if($BL['obj_name'] != 'dialog')
		return _emptyMin('Не диалог.');

	$dialog_id = $BL['obj_id'];
*/

	if(!$dialog_id = _num($unit['source']['dialog_source']))
		return _emptyMin('Не найдено ID диалога, который вносит данные списка.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin('Диалога не существует, который вносит данные списка.');

	$sql = "SELECT `id`,1
			FROM `_dialog`
			WHERE !`app_id`
			  AND `element_is_insert`";
	$choose_access = query_ass($sql);

	$send = array(
		'choose' => 1,
		'choose_access' => $choose_access,
		'choose_sel' => array(),      //ids ранее выбранных элементов или блоков
		'choose_deny' => array()      //ids элементов или блоков, которые выбирать нельзя (если они были выбраны другой фукцией того же элемента)
	);

	$elemJS = array();
	foreach($dialog['cmp'] as $id => $r) {
		if(empty($choose_access[$r['dialog_id']]))
			continue;
		$elemJS[] = $id.':"'.addslashes(_elemTitle($id)).'"';
	}



	return
	'<div class="fs14 pad10 pl15 bg-orange line-b">Диалоговое окно <b class="fs14">'.$dialog['name'].'</b>:</div>'.
	_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send).
	'<script>ELM74={'.implode(',', $elemJS).'};</script>';
}

function _elemQuery($elem_id) {//запрос одного элемента
	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".abs($elem_id);
	if(!$elem = query_assoc($sql))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$elem['block_id'];
	$elem['block'] = query_assoc($sql);

	return $elem;
}
function _blockQuery($block_id) {//запрос одного блока
	if(empty($block_id))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `id`=".$block_id;
	if(!$block = query_assoc($sql))
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id;
	$block['elem'] = query_assoc($sql);

	return $block;
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
	if(!$el = _elemQuery($elem_id))
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
		case 60: return _imageNo($el_parent['width']);
		case 62: return 'Фильтр-галочка';
		case 67://шаблон истории действий
			_cache('clear', '_dialogQuery'.$el['num_2']);
			$dlg = _dialogQuery($el['num_2']);
			return $dlg['history'][$el['num_1']]['tmp'];
	}
	return $el['name'];
}

function _elementChoose($el, $unit) {//выбор элемента для вставки в блок
	$BL['obj_name'] = $unit['source']['unit_id'] == -115 ? 'spisok' : '';
	if($block_id = _num($unit['source']['block_id'], 1))
		if(!$BL = _blockQuery($block_id))
			return _emptyMin('Исходного блока id'.$block_id.' не существует.');

	define('BLOCK_PAGE',   $BL['obj_name'] == 'page');
	define('BLOCK_DIALOG', $BL['obj_name'] == 'dialog');
	define('BLOCK_SPISOK', $BL['obj_name'] == 'spisok');
	define('_44_ACCESS', $unit['source']['unit_id'] == -111);//сборный текст, шаблон истории действий
	define('TD_PASTE', $unit['source']['unit_id'] == -112 || $unit['source']['unit_id'] == -115); //ячейка таблицы

	//определение, принимает ли страница значения списка
	$spisok_exist = false;
	if(BLOCK_PAGE) {
		$page = _page($BL['obj_id']);
		$spisok_exist = $page['spisok_id'];
	}
	define('IS_SPISOK_UNIT', BLOCK_SPISOK || TD_PASTE || $spisok_exist);

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
		if(_44_ACCESS && !$r['element_44_access'])
			continue;
		if(TD_PASTE && !$r['element_td_paste'])
			continue;
//		if(IS_SPISOK_UNIT && !$r['element_is_spisok_unit'])
//			continue;

		$show = false;

		if(BLOCK_PAGE && $r['element_page_paste'])
			$show = true;
		if(BLOCK_DIALOG && $r['element_dialog_paste'])
			$show = true;
		if(BLOCK_SPISOK && $r['element_spisok_paste'])
			$show = true;
		if($r['element_is_spisok_unit'] && !IS_SPISOK_UNIT)
			$show = false;

		if($show)
			$group[$r['element_group_id']]['elem'][] = $r;
	}

	$debug =
		(DEBUG ?
			'<div class="line-t pad10 bg-ffe">'.
				'<div class="'.(BLOCK_PAGE ? 'color-pay b' : 'pale').'">BLOCK_PAGE</div>'.
				'<div class="'.(BLOCK_DIALOG ? 'color-pay b' : 'pale').'">BLOCK_DIALOG</div>'.
				'<div class="'.(BLOCK_SPISOK ? 'color-pay b' : 'pale').'">BLOCK_SPISOK</div>'.
				'<div class="'.($spisok_exist ? 'color-pay b' : 'pale').'">$spisok_exist</div>'.
				'<div class="'.(IS_SPISOK_UNIT ? 'color-pay b' : 'pale').'">IS_SPISOK_UNIT</div>'.
				'<div class="'.(_44_ACCESS ? 'color-pay b' : 'pale').'">_44_ACCESS</div>'.
				'<div class="'.(TD_PASTE ? 'color-pay b' : 'pale').'">TD_PASTE</div>'.
				_pr($unit).
//				_pr($BL).
			'</div>'
		: '');

	foreach($group as $id => $r)
		if(empty($r['elem']))
			unset($group[$id]);

	if(empty($group))
		return _emptyMin('Нет элементов для отображения.').$debug;

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
				  (SA ? '<div class="icon icon-move-y fr pl"></div><div class="icon icon-edit fr pl mr3"></div>' : '').
						'<div class="dib w25 fs12 r">'.$n++.'.</div> '.
						'<b>'.$el['name'].'</b>'.
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
		$debug;
}

function _filterCheckSetup() {//настройка условий фильтра для галочки (подключение через [12])
	return '';
}

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
			  "._historyCond52($el)."
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
function _historyCond52($el) {//отображение истории для конкретной единицы списка (связка)
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id`=52
			  AND `num_1`=".$el['id']."
			LIMIT 1";
	if(!query_value($sql))
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
	if($arr = _cache())
		return $arr;

	$sql = "SELECT `id`,`path` FROM `_image_server`";
	return _cache(query_ass($sql));
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

	_cache('clear', '_imageServerCache');

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
		$sql = "INSERT INTO `_user_spisok_filter` (
					`user_id`,
					`element_id_spisok`,
					`element_id_filter`,
					`v`
				) VALUES (
					".USER_ID.",
					".$el['num_1'].",
					".$el['id'].",
					'".$v."'
				)";
		query($sql);
		_spisokFilter('cache_clear');
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
	'<table class="w100p">'.
		'<tr class="week-name">'.
			'<th>&nbsp;<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс'.
		$send.
	'</table>';
}
function _filterCalendarDays($el, $mon) {//отметка дней в календаре, по которым есть записи
	if(!$elem = _elemQuery($el['num_1']))
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
			FROM `"._baseTable($dlg['table_1'])."`
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
	if(!$ell = _elemQuery($el['num_2']))
		return _emptyMin('Фильтр-меню: отсутствует элемент, содержащий значения.');
	if(!$ids = _ids($ell['txt_2'], 1))
		return _emptyMin('Фильтр-меню: отсутствуют ID значений.');

	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemQuery($elem_id))
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
	if($el3 = _elemQuery($el['num_3']))
		if($ids = _ids($el3['txt_2'], 1)) {
			$c = count($ids) - 1;
			$elem_id = $ids[$c];
			if($EL3 = _elemQuery($elem_id))
				$colCount = $EL3['col'];
		}

	$cond = " `id`";
	if(isset($dialog['field1']['deleted']))
		$cond .= " AND !`deleted`";
	if(isset($dialog['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;
	$sql = "SELECT *
			FROM `"._baseTable($dialog['table_1'])."`
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
/*
	foreach($spisok[0] as $r) {
		$b = $r['parent_id'] ? ' ml30' : ' b fs14';
		$bCount = $r['parent_id'] ? '' : ' b';
		$sel = $v == $r['id'] ? ' sel' : '';
		$send .=
			(!$r['parent_id'] ? '<div class="fl fs15 b pale mr5 mt3">+</div> ' : '').
			'<div class="fm-unit'.$b.$sel.'" val="'.$r['id'].'">'.
				$r[$col].
				($colCount ? '<span class="ml10 pale'.$bCount.'">'.$r[$colCount].'</span>' : '').
			'</div>';
	}
*/
	return $send;
}













