<?php

/* [2] Кнопка */
function _element2_struct($el) {
	return array(
		'parent_id' => _num($el['parent_id']),//если кнопка расположена в ячейке таблицы

		'txt_1' => $el['txt_1'],        //текст кнопки
		'num_1' => _num($el['num_1']),  //цвет
		'num_2' => _num($el['num_2']),  //маленькая кнопка
		'num_3' => _num($el['num_3']),  //передаёт данные записи для отображения
		'num_4' => _num($el['num_4']),  //dialog_id, который назначен на эту кнопку
		'num_5' => _num($el['num_5']),  //передаёт данные записи для редактирования
		'num_6' => _num($el['num_6'])   //передаёт данные записи для удаления
	) + _elementStruct($el);
}
function _element2_struct_title($el) {
	$el['title'] = $el['txt_1'];
	return $el;
}
function _element2_print($el, $prm) {
	$color = array(
		0 => '',      //Синий - по умолчанию
		1 => '',      //Синий
		2 => 'green', //Зелёный
		3 => 'red',   //Красный
		4 => 'grey',  //Серый
		5 => 'cancel',//Прозрачный
		6 => 'pink',  //Розовый
		7 => 'orange' //Оранжевый
	);

	//если кнопка расположена в ячейке таблицы, установка ширины = 100%. Ширина будет подстраиваться под ячейку.
	if($parent_id = $el['parent_id'])
		if($elp = _elemOne($parent_id))
			if($elp['dialog_id'] == 23)
				$el['width'] = 0;

	return
	_button(array(
		'attr_id' => _elemAttrId($el, $prm),
		'name' => _br($el['txt_1']),
		'color' => $color[$el['num_1']],
		'width' => _num(@$el['width']),
		'small' => $el['num_2'],
		'class' => $prm['blk_setup'] ? 'curD' : 'dialog-open',
		'val' => _element2printVal($el, $prm)
	));
}
function _element2printVal($el, $prm) {//значения аттрибута val для кнопки
	$ass['dialog_id'] = $el['num_4'];

	//Если кнопка новая, будет создаваться новый диалог для неё. На основании блока, в который она вставлена.
	if($el['num_4'] <= 0)
		$ass['block_id'] = $el['block_id'];

	//если кнопка расположена в диалоговом окне, то указывается id этого окна как исходное
	//а также вставка исходного блока для передачи как промежуточного значения, если кнопка расположена в диалоге
	//Нужно для назначения функций (пока)
	if($bl = _blockOne($el['block_id']))
		if($bl['obj_name'] == 'dialog') {
			$ass['dss'] = $bl['obj_id'];
			if($prm['srce']['block_id'])
				$ass['block_id'] = $prm['srce']['block_id'];
			if($prm['srce']['element_id'])
				$ass['element_id'] = $prm['srce']['element_id'];
		}

	$val = array();
	foreach($ass as $k => $v)
		$val[] = $k.':'.$v;

	$val = implode(',', $val);

	if($dialog_id = $el['num_4'])
		$val .= _dialogOpenVal($dialog_id, $prm, $el);

	return $val;
}

/* ---=== ВЫБОР ЦВЕТА КНОПКИ [2] ===--- */
function PHP12_button_color($prm) {
	$sel = _num($prm['el12']['txt_2']);
	if($col = $prm['el12']['col'])
		if($u = $prm['unit_edit'])
			$sel = $u[$col];

	$BUT[1] = array('Синий',        '');
	$BUT[2] = array('Зелёный',      'green');
	$BUT[3] = array('Красный',      'red');
	$BUT[4] = array('Серый',        'grey');
	$BUT[5] = array('Прозрачный',   'cancel');
	$BUT[6] = array('Розовый',      'pink');
	$BUT[7] = array('Оранжевый',    'orange');

	$send = '';
	foreach($BUT as $id => $r) {
		$send .=
		'<div class="vk-but-color over1'._dn($id != $sel, 'sel').'" val="'.$id.'">'.
			'<button class="vk w125 curD '.$r[1].'">'.$r[0].'</button>'.
		'</div>';
	}

	return $send;
}

