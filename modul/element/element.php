<?php
function _button($v=array()) {//кнопка из контакта
	$name = empty($v['name']) ? 'Кнопка' : $v['name'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$color = empty($v['color']) ? '' : ' '.$v['color'];

	return
	'<button class="vk'.$color.'"'.$click.'>'.
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
		'txt' => @$v['txt'],
		'value' => _bool(@$v['value']),
		'on' => _bool(@$v['value']) ? ' on' : '',
		'light' => _bool(@$v['light']) ? ' l' : '',
		'disabled' => _bool(@$v['disabled']) ? ' disabled' : '',
		'block' => _bool(@$v['block']) ? ' block' : ''
	);
	return
	'<div class="_check '.$v['on'].$v['block'].$v['disabled'].$v['light'].($v['txt'] ? '' : ' e').'" id="'.$v['id'].'_check">'.
		'<input type="hidden" id="'.$v['id'].'" value="'.$v['value'].'" />'.
		$v['txt'].
	'</div>';
}

function _search($v=array()) {//элемент ПОИСК
	$v = array(
		'id' => @$v['id'],
		'width' => _num(@$v['width']) ? _num($v['width']) : 300,
		'txt' => @$v['txt'],
		'grey' => _num(@$v['grey'])
	);
	return
	($v['grey'] ? '<div class="pad10 bg-gr3 line-b">' : '').
		'<div class="_search" style="width:'.$v['width'].'px">'.
				'<div class="img_del dn"></div>'.
				'<div class="_busy dib fr mr5 dn"></div>'.
				'<div class="hold">'.$v['txt'].'</div>'.
				'<input type="text" style="width:'.($v['width'] - 77).'px" />'.
		'</div>'.
	($v['grey'] ? '</div>' : '');
}



function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			  AND `id`=".$dialog_id;
	if(!$r = query_assoc($sql))
		return array();

	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `dialog_id`=".$dialog_id;
	$r['element'] = query_arr($sql);

	$sql = "SELECT `id`,`v`
			FROM `_dialog_element_v`
			WHERE `dialog_id`=".$dialog_id;
	$r['v_ass'] = query_ass($sql);

/* пока отменено
	//конкретная колонка, используемая в таблице
	//берётся из `base_table`
	//например: _page:razdel
	$ex = explode(':', $r['base_table']);
	$r['col'] = @$ex[1];
	$r['base_table'] = $ex[0];
*/
	return $r;
}
function _dialogValToId($val='') {//получение id диалога на основании имени val
	//если такого имени нет, то внесение диалога в базу

	if(!$val = _txt($val))
		return 0;

	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `val`='".$val."'
			LIMIT 1";
	if($dialog_id = query_value($sql))
		return $dialog_id;

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

	return query_insert_id('_dialog');
}






