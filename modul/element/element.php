<?php
function _button($v=array()) {//кнопка из контакта
	$attr_id = empty($v['attr_id']) ? '' : ' id="'.$v['attr_id'].'"';
	$name = empty($v['name']) ? 'Кнопка' : $v['name'];
	$small = empty($v['small']) ? '' : ' small';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$cls = empty($v['class']) ? '' : ' '.$v['class'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$val = empty($v['val']) ? '' : ' val="'.$v['val'].'"';

	return
	'<button class="vk'.$color.$small.$cls.'"'.$attr_id.$click.$val.'>'.
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
	$attr_id = empty($v['attr_id']) ? 'check'.rand(1, 10000) : $v['attr_id'];

	$cls = '_check ';
	$cls .= empty($v['block']) ?    '' : ' block';      //display:block, иначе inline-block
	$cls .= empty($v['disabled']) ? '' : ' disabled';//неактивное состояние
	$cls .= empty($v['light']) ?    '' : ' light';      //если галочка не стоит, текст бледный
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
	$attr_id = empty($v['attr_id']) ? 'radio'.rand(1, 10000) : $v['attr_id'];
	$title0 = @$v['title0'];
	$spisok = @$v['spisok'] ? $v['spisok'] : array();//содержание в виде id => title
	$value = _num(@$v['value']);
	$dis = _num(@$v['disabled']) ? ' disabled' : '';
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;

	//если список пуст и только нулевое значение, отступ снизу не делается
	$int = empty($spisok) ? 0 : $interval;
	$html = _radioUnit(0, $title0, $int, $value == 0);

	if(is_array($spisok) && !empty($spisok)) {
		end($spisok);
		$idEnd = key($spisok);
		foreach($spisok as $id => $title) {
			//отступ снизу после последнего значения не делается
			$int = $idEnd == $id ? 0 : $interval;
			$html .= _radioUnit($id, $title, $int, $value == $id);
		}
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />'.
	'<div id="'.$attr_id.'_radio" class="_radio php'.$block.$dis.$light.'">'.
		$html.
	'</div>';
}
function _radioUnit($id, $title, $interval, $on) {
	if(empty($title))
		return '';

	$on = $on ? ' class="on"' : '';
	$interval = $interval ? ' style="margin-bottom:'.$interval.'px"' : '';
	return
	'<div'.$on.' val="'.$id.'"'.$interval.'>'.
		$title.
	'</div>';
}

function _search($v=array()) {//элемент ПОИСК
	$attr_id = empty($v['attr_id']) ? '' : ' id="'.$v['attr_id'].'"';
	$v = array(
		'id' => @$v['id'],
		'width' => _num(@$v['width']) ? _num($v['width']) : 300,
		'hold' => @$v['hold'],
		'v' => @$v['v']
	);
	return
	'<div class="_search" style="width:'.$v['width'].'px"'.$attr_id.'>'.
		'<div class="icon icon-del fr'._dn($v['v']).'"></div>'.
		'<div class="_busy dib fr mr5 dn"></div>'.
		'<div class="hold'._dn(!$v['v']).'">'.$v['hold'].'</div>'.
		'<input type="text" style="width:'.($v['width'] - 87).'px" value="'.$v['v'].'" />'.
	'</div>';
}



function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	if($dialog = _cache())
		return $dialog;

	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			  AND `id`=".$dialog_id;
	if(!$dialog = query_assoc($sql))
		return array();

	//получение компонентов диалога, ответственных за внесение единицы списка
	$cmp = array();
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog'
			  AND `obj_id`=".$dialog_id."
			  AND `sa` IN (0,".SA.")";
	if($block = query_arr($sql))
		if($block = _blockChildClear($block)) {
			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id` IN ("._idsGet($block).")
					  AND LENGTH(`col`)";
			foreach(query_arr($sql) as $r) {
				$id = _num($r['id']);
				$cmp[$id] = array(
					'id' => _num($r['id']),
					'dialog_id' => _num($r['dialog_id']),
					'require' => _num($r['require']),
					'col' => $r['col'],
					'attr_id' => '#cmp_'.$id,
					'attr_pe' => '#pe_'.$id
				);
			}
		}


	$sql = "SELECT *
			FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	$component = query_arr($sql);
	foreach($component as $id => $r) {
		$component[$id]['type_name'] = _dialogEl($r['type_id'], 'name');
		$component[$id]['label_html'] = $r['label_name'] ?
										$r['label_name']
										:
										'<span class="grey i b">'.
											$component[$id]['type_name'].
										($r['type_id'] == 1 ? ': <i>'.$r['txt_1'].'</i>' : '').
										'</span>';
		$component[$id]['v_ass'] = array();
		$component[$id]['func'] = array();
		$component[$id]['func_action_ass'] = array();//ассоциативный массив действий для конкретного компонента
	}

	$sql = "SELECT `id`,`v`
			FROM `_dialog_component_v`
			WHERE `dialog_id`=".$dialog_id;
	$dialog['v_ass'] = query_ass($sql);

	$sql = "SELECT *
			FROM `_dialog_component_func`
			WHERE `component_id` IN ("._idsGet($component).")
			ORDER BY `component_id`,`id`";
	$func = array();
	foreach(query_arr($sql) as $r) {
		$component[$r['component_id']]['func'][] = array(
			'action_id' => _num($r['action_id']),
			'cond_id' => _num($r['cond_id']),
			'component_ids' => $r['component_ids']
		);
		$component[$r['component_id']]['func_action_ass'][$r['action_id']] = 1;

		if(!isset($func[$r['component_id']]))
			$func[$r['component_id']] = array();

		$func[$r['component_id']][] = array(
			'action_id' => _num($r['action_id']),
			'cond_id' => _num($r['cond_id']),
			'ids' => $r['component_ids']
		);
	}

	//получение списка колонок, присутствующих в таблице
	$col = array();
	$sql = "DESCRIBE `".$dialog['base_table']."`";
	foreach(query_array($sql) as $r)
		$col[$r['Field']] = 1;

	$dialog['cmp'] = $cmp;
	$dialog['component'] = $component;
	$dialog['func'] = $func;
	$dialog['field'] = $col;

	return _cache($dialog);
}
/*
function _dialogValToId($val='') {//получение id диалога на основании имени val
	//если такого имени нет, то внесение диалога в базу

	if(!$val = _txt($val))
		return 0;

//	_cache('clear', '_dialogValToIdbutton172');

	if($dialog_id = _cache())
		return $dialog_id;

	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `val`='".$val."'
			LIMIT 1";
	if($dialog_id = query_value($sql))
		return _cache($dialog_id);

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`val`,

				`head_insert`,
				`button_insert_submit`,
				`button_insert_cancel`,

				`head_edit`,
				`button_edit_submit`,
				`button_edit_cancel`
			) VALUES (
				".APP_ID.",
				'".$val."',

				'".$val." - внесение',
				'Внести',
				'Отмена',

				'".$val." - сохранение',
				'Сохранить',
				'Отмена'
			)";
	query($sql);

	$dialog_id = query_insert_id('_dialog');
	
	$sql = "UPDATE `_dialog`
			SET `spisok_name`='Список ".$dialog_id."'
			WHERE `id`=".$dialog_id;
	query($sql);
	
	return _cache($dialog_id);
}
*/
function _dialogSpisokOn() {//получение массива диалогов, которые могут быть списками: spisok_on=1
	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `sa` IN (0".(SA ? ",1" : '').")
			  AND `spisok_on`
			ORDER BY `id`";
	return query_selArray($sql);
}
function _dialogPageList() {//получение массива страниц приложения для select
	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id`=".APP_ID."
			  AND !`sa`
			ORDER BY `sort`";
	$arr = query_arr($sql);

	$sort = array();
	foreach($arr as $id => $r)
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		}

	return _dialogPageListSpisok($arr, $sort);
}
function _dialogPageListSpisok($arr, $sort, $level=0) {
	if(empty($arr))
		return '';

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'uid' => _num($r['id']),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))),
			'content' => '<div class="'.($level ? 'ml'.($level*10) : 'b').'">'.utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))).'</div>'
		);
		if(!empty($sort[$r['id']]))
			foreach(_dialogPageListSpisok($sort[$r['id']], $sort, $level+1) as $sub)
				$send[] = $sub;
	}

	return $send;
}
function _dialogSpisokOnPage($page_id) {//получение массива элементов страницы, которые являются списками 
	if(!$page_id)
		return array();
	
	$sql = "SELECT `id`,`num_3`
			FROM `_element`
			WHERE `page_id`=".$page_id."
			  AND `dialog_id`=14
			  AND `num_3`";
	if(!$res = query_arr($sql))
		return array();

	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `sa` IN (0".(SA ? ",1" : '').")
			  AND `id` IN ("._idsGet($res, 'num_3').")
			ORDER BY `id`";
	$ass = query_ass($sql);

	foreach($res as $id => $r)
		$res[$id] = $ass[$r['num_3']];

	return _selArray($res);
}
function _dialogSpisokGetPage($page_id) {//список объектов, которые поступают на страницу через GET
	if(!$page_id)
		return array();

	//определение, есть ли данные, поступающие на эту страницу
	$sql = "SELECT `id`,`spisok_name`
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			  AND `action_id`=2
			  AND `action_page_id`=".$page_id;
	if(!$send = query_ass($sql))
		return array();

	return _selArray($send);
}

function _dialogCmpValue($val, $i='test', $dialog_id=0, $cmp_id=0) {//проверка на корректность заполнения значений для некоторых компонентов
	if(empty($val))
		return;
	if(!is_array($val))
		jsonError('Значения компонента не являются массивом');

	$update = array();
	$idsNoDel = '0';
	$sort = 0;
	foreach($val as $r) {
		if(!$title = _txt($r['title']))
			jsonError('Не заполнено одно из значений');
		if($id = _num($r['id']))
			$idsNoDel .= ','.$id;
		$update[] = "(
			".$id.",
			".$dialog_id.",
			".$cmp_id.",
			'".addslashes($title)."',
			"._num($r['def']).",
			".$sort++."
		)";
	}

	if($i == 'test')
		return;

	//$i == 'save': процесс сохранения

	//удаление удалённых значений
	$sql = "DELETE FROM `_element_value`
			WHERE `element_id`=".$cmp_id."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element_value` (
				`id`,
				`dialog_id`,
				`element_id`,
				`title`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`title`=VALUES(`title`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);
}

function _dialogEl($type_id=0, $i='') {//данные всех элементов, используемых в диалоге
	if(!defined('EL_LABEL_W'))
		define('EL_LABEL_W', 'w175');//первоначальная ширина для всех label
	$sort = array(9,3,4,2,1,5,6,7,8);

	$name = array(
		1 => 'Галочка', //стало dialog_id=8
		2 => 'Выпадающий список',
		3 => 'Однострочный текст',
		4 => 'Многострочный текст',
		5 => 'Radio',
		6 => 'Календарь',
		7 => 'Информация',

		8 => 'Связка',
		9 => 'Заголовок'
	);
	$css = array(
		1 => '',
		2 => 'element-select',
		3 => 'element-input',
		4 => 'element-textaera',
		5 => 'element-radio',
		6 => 'element-calendar',
		7 => 'element-info',
		8 => 'element-connect',
		9 => 'element-head'
	);
	//может ли компонент содержать функцию (а также может ли участвовать в передаче данных)
	$func = array(
		1 => 1,
		2 => 1,
		3 => 1,
		4 => 1,
		5 => 1,
		6 => 1,
		7 => 0,
		8 => 0,
		9 => 0
	);
	$html = array(
		1 => /* *** галочка ***
				txt_1 - текст для галочки
            */
			_dialogElHtmlContent().
			'<table class="bs5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Текст для галочки:'.
					'<td><input type="text" class="w250" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		2 => /* *** выпадающий список ***
				num_3 - использовать или нет нулевое значение
                txt_1 - текст нулевого значения
				num_4:  0 - данных нет
						1 - произвольные значения
						2 - использование всех списков при выборе
							num_5 - выбор списков только с текущей страницы
						3 - выбор элемента списка (для связок)
						4 - список объектов, которые поступают на страницу через GET
 				num_1  - id списка по dialog_id
		        num_2  - id колонки по component_id
			*/
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20">Содержание выпадающего списка:</div>'.
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Нулевое значение:'.
					'<td><input type="hidden" id="num_3" value="1" />'.
						'<input type="text" class="w230 ml5" id="txt_1" value="не выбрано" />'.
			'</table>'.
			'<input type="hidden" id="num_4" />'.
			'<div id="elem-select-but" class="center pad10">'.
				'<p><button class="vk small green">Произвольные значения</button>'.
				'<p class="mt5"><button class="vk small">Все списки</button> '.
				'<button class="vk small">Выбрать из списков</button>'.
				'<p class="mt5"><button class="vk small">Списки объектов страницы</button>'.
			'</div>'.
			_dialogElHtmlPrev(),

		3 => /* *** Однострочный текст ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Подсказка в поле:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<input type="text" id="elem-attr-id" class="w250" />'),

		5 => /* *** Радио ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<div class="hd2 ml20 mr20" id="radio-cont">Содержание:</div>'.
			_dialogElHtmlPrev(),

		6 => /* *** Календарь ***
				num_3 - возможность выбирать прошедшие дни
                num_4 - показывать ссылку "завтра"
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Выбор прошедших дней:'.
					'<td><input type="hidden" id="num_3" />'.
				'<tr><td class="label r">Ссылка <u>завтра</u>:'.
					'<td><input type="hidden" id="num_4" />'.
			'</table>'.
			_dialogElHtmlPrev(),

		7 => /* *** Информация ***
				txt_1 - текст информации
             */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r topi '.EL_LABEL_W.'">Текст:'.
					'<td><textarea id="txt_1" class="w300"></textarea>'.
			'</table>'.
			'<div id="prev-tab" class="mt20 pad20 pt10">'.
				'<div class="hd2">Предварительный просмотр:</div>'.
				'<div id="elem-attr-id" class="_info mt10"></div>'.
			'</div>',

		8 => /* *** Связка ***
 				num_1  - id списка по dialog_id
		        num_2  - id колонки по component_id
            */
			_dialogElHtmlContent().
			'<div id="connect-head"></div>'.
			_dialogElHtmlPrev('<div class="grey i">Текстовый результат</div>'),

		9 => /* *** Заголовок ***
 				num_1  - вид заголовка
 				txt_1  - текст заголовка
            */
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Вид:'.
					'<td><input type="hidden" id="num_1" value="2" />'.
				'<tr><td class="label r">Текст:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			'<div class="b ml20 mt20 mb5">Предосмотр:</div>'.
			'<div id="prev-tab" class="pad20 pt10 bor-f0">'.
				'<div id="elem-attr-id" class="mt10 hd2"></div>'.
			'</div>',
	);

	//получение возможности настройки функции для компонента
	if($i == 'func')
		return $func[$type_id];

	//подготовка и отправка имён компонентов через AJAX
	if($i == 'name') {
		if($type_id)
			return $name[$type_id];
		foreach($name as $id => $r)
			 $name[$id] = utf8($r);
		return $name;
	}


	$send = array();
	foreach($sort as $id) {
		$send[$id] = array(
			'name' => utf8($name[$id]),
			'css' => $css[$id],
			'html' => utf8(
						_dialogElHtmlHead($name[$id]).
						_dialogElHtmlSA().
						$html[$id]
					)
		);
	}
	return $send;
}
function _dialogElHtmlHead($name) {//заголовок элемента
	return '<div class="fs16 bg-gr1 pad20 line-b mb10">Компонент <b class="fs16">'.$name.'</b></div>';
}
function _dialogElHtmlSA() {//поле SA
	if(!SA)
		return '';
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' red r">SA: col_name:'.
			'<td><input type="text" id="col_name" class="w100" />'.
	'</table>';
}
function _dialogElHtmlContent($req=0) {//основное содержимое
	return
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r b">Название поля:'.
			'<td><input type="text" id="label_name" class="w250" />'.
	'</table>'.

($req ? //отображение галочки "Требуется обязательное заполнение"
	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.'">'.
			'<td><input type="hidden" id="label-require" />'.
	'</table>'
: '').

	'<table class="bs5">'.
		'<tr><td class="'.EL_LABEL_W.' label r topi">Текст выплывающей<br />подсказки:'.
			'<td><textarea id="label-hint" class="w300"></textarea>'.
	'</table>';
}
function _dialogElHtmlPrev($inp='<input type="hidden" id="elem-attr-id" />') {//предварительный просмотр
	return
	'<div id="prev-tab" class="mt20 pad20 pt10 bor-f0 bg-ffe">'.
		'<div class="hd2">Предварительный просмотр:</div>'.
		'<table class="bs5 w100p mt10">'.
			'<tr><td id="label-prev" class="label r '.EL_LABEL_W.'">'.
				'<td>'.$inp.
		'</table>'.
	'</div>';
}



