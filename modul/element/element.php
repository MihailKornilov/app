<?php
/*
	---=== Элементы, размещаемые на странице ===---

	*** Меню *** dialog_id=5 (страница настройки: 4)
		txt_1 - Название меню
		num_1 - положение (горизонтальное, вертикальное)
		num_2 - тип меню

	*** Заголовок *** dialog_id=4

	*** Поиск *** dialog_id=7

	*** Кнопка *** dialog_id=2

	*** Ссыдка *** dialog_id=9

	*** Список *** dialog_id=14

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
	if($dialog = _cacheNew())
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

	return _cacheNew($dialog);
}
function _dialogValToId($val='') {//получение id диалога на основании имени val
	//если такого имени нет, то внесение диалога в базу

	if(!$val = _txt($val))
		return 0;

	if($dialog_id = _cacheNew())
		return $dialog_id;

	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `val`='".$val."'
			LIMIT 1";
	if($dialog_id = query_value($sql))
		return _cacheNew($dialog_id);

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
	
	return _cacheNew($dialog_id);
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
function _dialogPageList() {//получение массива страниц приложения
	$sql = "SELECT `id`,`name`
			FROM `_page`
			WHERE `app_id` IN (".APP_ID.(SA ? ",0" : '').")
			ORDER BY `id`";
	return query_selArray($sql);
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





