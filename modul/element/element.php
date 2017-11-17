<?php
/*
	---=== Элементы, размещаемые на странице ===---

	5:  *** Меню *** (страница настройки: 4)
			txt_1 - Название меню
			num_1 - положение (горизонтальное, вертикальное)
			num_2 - тип меню

	4:  *** Заголовок ***

	7:  *** Поиск ***

	2:  *** Кнопка ***

	9:  *** Ссылка ***

	14: *** Список ***

	10: *** Произвольный список ***

	11: *** Данные объекта ***

*/
function _button($v=array()) {//кнопка из контакта
	$name = empty($v['name']) ? 'Кнопка' : $v['name'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$color = empty($v['color']) ? '' : ' '.$v['color'];
	$small = empty($v['small']) ? '' : ' small';

	return
	'<button class="vk'.$color.$small.'"'.$click.'>'.
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
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',       //id записи
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',      //дополнительный класс
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '', //скрипт по нажатию
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : 'Изменить',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$v['id'].$v['onclick'].' class="icon icon-edit'.$v['class']._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDel($v=array()) {//иконка удаления записи в таблице
	if(!empty($v['nodel']))
		return '';

	//если указывается дата внесения записи и она не является сегодняшним днём, то удаление невозможно
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id записи
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',//дополнительный класс
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '' //скрипт по нажатию
	);

	return '<div '.$v['id'].$v['onclick'].' class="icon icon-del'.$v['class']._tooltip('Удалить', -42, 'r').'</div>';
}



function _check($v=array()) {//элемент ГАЛОЧКА
	$v = array(
		'id' => @$v['id'],
		'title' => @$v['title'],
		'value' => _bool(@$v['value']),
		'on' => _bool(@$v['value']) ? ' on' : '',
		'light' => _bool(@$v['light']) ? ' light' : '',
		'disabled' => _bool(@$v['disabled']) ? ' disabled' : '',
		'block' => _bool(@$v['block']) ? ' block' : ''
	);
	$title = $v['title'] ? ' title' : '';
	return
	'<input type="hidden" id="'.$v['id'].'" value="'.$v['value'].'" />'.
	'<div class="_check '.$v['on'].$v['block'].$v['disabled'].$v['light'].$title.'" id="'.$v['id'].'_check">'.
		$v['title'].
	'</div>';
}
function _radio($v=array()) {//элемент RADIO
	$attr_id = @$v['attr_id'] ? ' id="'.$v['attr_id'].'_radio"' : '';
	$title0 = @$v['title0'];
	$spisok = @$v['spisok'] ? $v['spisok'] : array();
	$value = _num(@$v['value']);
	$dis = _num(@$v['disabled']) ? ' disabled' : '';
	$light = _num(@$v['light']) ? ' light' : '';
	$block = _bool(@$v['block']) ? ' block' : '';
	$interval = _num(@$v['interval']) ? _num(@$v['interval']) : 7;

	$html = '';
	if($title0)
		$html = _radioUnit(0, $title0, $interval, $value == 0);
	if(is_array($spisok))
		foreach($spisok as $id => $title)
			$html .= _radioUnit($id, $title, $interval, $value == $id);

	return
	'<input type="hidden" id="" value="'.$value.'" />'.
	'<div class="_radio php'.$block.$dis.$light.'"'.$attr_id.'>'.
		$html.
	'</div>';
}
function _radioUnit($id, $title, $interval, $on) {
	$on = $on ? ' class="on"' : '';
	return
	'<div'.$on.' val="'.$id.'" style="margin-bottom:'.$interval.'px">'.
		$title.
	'</div>';
}

function _search($v=array()) {//элемент ПОИСК
	$v = array(
		'id' => @$v['id'],
		'width' => _num(@$v['width']) ? _num($v['width']) : 300,
		'hold' => @$v['hold'],
		'v' => @$v['v']
	);
	return
	'<div class="_search" style="width:'.$v['width'].'px">'.
		'<div class="icon icon-del fr'._dn($v['v']).'"></div>'.
		'<div class="_busy dib fr mr5 dn"></div>'.
		'<div class="hold'._dn(!$v['v']).'">'.$v['hold'].'</div>'.
		'<input type="text" style="width:'.($v['width'] - 77).'px" value="'.$v['v'].'" />'.
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

	$sql = "SELECT *
			FROM `_dialog_component`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	$cmp = query_arr($sql);
	foreach($cmp as $id => $r) {
		$cmp[$id]['type_name'] = _dialogEl($r['type_id'], 'name');
		$cmp[$id]['label_html'] = $r['label_name'] ?
										$r['label_name']
										:
										'<span class="grey i b">'.
											$cmp[$id]['type_name'].
										($r['type_id'] == 1 ? ': <i>'.$r['txt_1'].'</i>' : '').
										'</span>';
		$cmp[$id]['v_ass'] = array();
		$cmp[$id]['func'] = array();
		$cmp[$id]['func_action_ass'] = array();//ассоциативный массив действий для конкретного компонента
	}

	$sql = "SELECT `id`,`v`
			FROM `_dialog_component_v`
			WHERE `dialog_id`=".$dialog_id;
	$dialog['v_ass'] = query_ass($sql);

	$sql = "SELECT *
			FROM `_dialog_component_func`
			WHERE `component_id` IN ("._idsGet($cmp).")
			ORDER BY `sort`";
	foreach(query_arr($sql) as $r) {
		$cmp[$r['component_id']]['func'][] = array(
			'action_id' => $r['action_id'],
			'cond_id' => $r['cond_id'],
			'component_ids' => $r['component_ids']
		);
		$cmp[$r['component_id']]['func_action_ass'][$r['action_id']] = 1;
	}

	$dialog['component'] = $cmp;

	return _cache($dialog);
}
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
			FROM `_page_element`
			WHERE `app_id` IN (".APP_ID.",0)
			  AND `page_id`=".$page_id."
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

function _dialogEl($type_id=0, $i='') {//данные всех элементов, используемых в диалоге
	if(!defined('EL_LABEL_W'))
		define('EL_LABEL_W', 'w175');//первоначальная ширина для всех label
	$sort = array(9,3,4,2,1,5,6,7,8);

	$name = array(
		1 => 'Галочка',
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
                txt_1  - текст нулевого значения
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

		4 => /* *** Многострочный текст ***
				txt_1 - текст для placeholder
             */
			_dialogElHtmlContent(1).
			'<table class="bs5 mt5">'.
				'<tr><td class="label r '.EL_LABEL_W.'">Подсказка в поле:'.
					'<td><input type="text" class="w300" id="txt_1" />'.
			'</table>'.
			_dialogElHtmlPrev('<textarea id="elem-attr-id" class="w250"></textarea>'),

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




