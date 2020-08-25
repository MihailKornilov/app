<?php

/* [85] Select: выбор значения списка по умолчанию */
function _element85_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//ID элемента select, который содержит списки
		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_2'   => _num($el['num_2']),//разрешать выбор записи, данные которой принимает страница
		'num_3'   => _num($el['num_3']),//разрешать выбор записи, данные которой принимает диалог
		'num_4'   => _num($el['num_4']) //разрешать выбор текущего пользователя
	) + _elementStruct($el);
}
function _element85_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}
function _element85_vvv($el, $prm) {
	$send = array();

	if($el['num_2'])
		$send[] = array(
			'id' => -1,
			'title' => 'Совпадает с текущей страницей',
			'content' => '<div class="b clr11">Совпадает с текущей страницей</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет установлено значение записи, которую принимает текущая страница</div>'
		);

	if($el['num_3'])
		$send[] = array(
			'id' => -2,
			'title' => 'Совпадает с данными, приходящими на диалог',
			'content' => '<div class="b clr11">Совпадает с данными, приходящими на диалог</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет установлено значение записи, которая приходит на открываемое диалоговое окно</div>'
		);

	if($el['num_4'])
		$send[] = array(
			'id' => -21,
			'title' => 'Текущий пользователь',
			'content' => '<div class="b clr11">Текущий пользователь</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет выбран текущий пользователь</div>'
		);

	$send = _elem201init($el, $prm, $send);

	if(empty($prm['unit_edit']))
		return $send;

	$u = $prm['unit_edit'];

	//колонка, по которой будет получено ID диалога-списка
	if(!$col = _elemCol($el['num_1']))
		return $send;
	if(!$v = _num($u[$col]))
		return $send;

	$send = _elem85mass($el['num_1'], $v, $send);
	$send = _elem212ActionFormat($el['id'], $v, $send);

	return $send;
}
function _elem85mass($ell_id, $v, $send) {//получение значений для элемента [85]
	if(!$dlg_id = _dialogSel24($ell_id, $v))
		return $send;
	if(!$dlg = _dialogQuery($dlg_id))
		return $send;

	//получение данных списка
	$sql = "SELECT "._queryCol($dlg)."
			FROM   "._queryFrom($dlg)."
			WHERE  "._queryWhere($dlg)."
			ORDER BY `id`
			LIMIT 200";
	if(!$spisok = query_arr($sql))
		return $send;

	$spisok = _spisokInclude($spisok);

	//содержание выпадающего списка будет взято из настроек диалога
	$cols = array();
	while(true) {
		if(!$elem_id = $dlg['spisok_elem_id'])
			break;
		$ell = _elemOne($elem_id);
		$cols[] = $ell['col'];
		if(_elemIsConnect($elem_id)) {
			$dlg = _dialogQuery($ell['num_1']);
			continue;
		}
		break;
	}

	foreach($spisok as $id => $sp) {
		foreach($cols as $col) {
			if(empty($sp[$col])) {
				$sp = '- значение отсутствует -';
				break;
			}
			$sp = $sp[$col];
		}
		$send[] = array(
			'id' => $id,
			'title' => is_array($sp) ? $sp['txt_1'] : $sp
		);
	}

	return $send;
}

function _elem201init($el85, $prm, $send) {//получение данных элемента для настройки действия [201]
	if(empty($prm['srce']))
		return $send;

	$srce = $prm['srce'];

	//проверка, чтобы данные были получены только для действий
	if(!$DLG = _dialogQuery($srce['dialog_id']))
		return $send;
	if($DLG['table_name_1'] != '_action')
		return $send;

	if($srce['dialog_id'] != 201)
		if($srce['dialog_id'] == 202 && $el85['col'] != 'initial_id')
			if($srce['dialog_id'] != 206)//установка фокуса
				return $send;

	//получение настраиваемого элемента
	if(!$EL = _elemOne($srce['element_id'])) {
		if($BL = _blockOne($srce['block_id'])) {
			if(!$EL = _elemOne($BL['elem_id']))
				return $send;
		}
		if(!$act = _BE('action_one', 0))
			return $send;
	}

	switch($EL['dialog_id']) {
		//галочка
		case 1:
		//фильтр-галочка
		case 62:
			array_unshift($send, array(
				'id' => -2,
				'title' => 'галочка установлена',
				'content' => '<div class="clr11 b">галочка установлена</div>'.
							 '<div class="clr1 i ml20">Действие будет совершено при установленной галочке</div>'
			));
			array_unshift($send, array(
				'id' => -1,
				'title' => 'галочка снята',
				'content' => '<div class="clr8 b">галочка снята</div>'.
							 '<div class="clr1 i ml20">Действие будет совершено, если галочка снята</div>'
			));
			break;

		//быстрый поиск
		case 7:
			array_unshift($send, array(
				'id' => -2,
				'title' => 'введено любое значение'
			));
			array_unshift($send, array(
				'id' => -1,
				'title' => 'значение сброшено'
			));
			break;

		case 11:
			array_unshift($send, array(
				'id' => -2,
				'title' => 'выбрано любое значение',
				'content' => '<div class="clr11 b">выбрано любое значение</div>'.
							 '<div class="clr1 i ml20">Действие будет совершено при выборе любого значения</div>'
			));
			break;

		case 6:
		case 16:
		case 17:
		case 18: return _elem201initCnn($send, _element('vvv', $EL));

		case 24: return _elem201initCnn($send);

		case 29:
		case 59: return _elem201initCnn($send, _29cnn($EL['id']));

		case 51://календарь
			array_unshift($send, array(
				'id' => -2,
				'title' => 'выбран любой день'
			));
			break;

		case 75:
			array_unshift($send, array(
				'id' => -2,
				'title' => 'значение выбрано'
			));
			array_unshift($send, array(
				'id' => -1,
				'title' => 'значение НЕ выбрано'
			));
			break;
	}

	return $send;
}
function _elem201initCnn($send, $vvv=array()) {
	foreach($vvv as $n => $r) {
		$r['content'] = '<span class="clr11">выбрано</span> <b>'.$r['title'].'</b>';
		$r['title'] = 'выбрано "'.$r['title'].'"';
		array_push($send, $r);
	}

	array_unshift($send, array(
		'id' => -2,
		'title' => 'выбрано любое значение',
		'content' => '<div class="clr11 b">выбрано любое значение</div>'.
					 '<div class="clr1 i ml20">Действие с блоками будет совершено при выборе любого значения</div>'
	));
	array_unshift($send, array(
		'id' => -1,
		'title' => 'значение сброшено',
		'content' => '<div class="clr8 b">значение сброшено</div>'.
					 '<div class="clr1 i ml20">Действие с блоками будет совершено, если значение было сброшено</div>'
	));
	return $send;
}
function _elem212ActionFormat($el85_id, $elv_id, $send) {//преобразование данных для выбора в действиях [212]
	//СНАЧАЛА получение информации об элементе [85]
	if(!$el85 = _elemOne($el85_id))
		return $send;
	if($el85['dialog_id'] != 85)
		return $send;
	if(!$BL = _blockOne($el85['block_id']))
		return $send;
	if($BL['obj_name'] != 'dialog')
		return $send;
	//элемент [85] должен располагаться в диалоге [212]
	if($BL['obj_id'] != 212)
		if($BL['obj_id'] == 202)//либо в диалоге [202]
			if($el85['col'] != 'apply_id')//и обязательно должен использовать колонку `apply_id`
				return $send;

	//ЗАТЕМ получение информации о выбранном элементе, который выбран для воздействия
	if(!$elv = _elemOne($elv_id))
		return $send;

	switch($elv['dialog_id']) {
		case 1://галочка
		case 62://фильтр-галочка
			array_unshift($send, array(
				'id' => 1,
				'title' => 'установить галочку'
			));
			array_unshift($send, array(
				'id' => -1,
				'title' => 'снять галочку'
			));
			break;

		//выбор значений
		default:
		case 13:
		case 75:
			array_unshift($send, array(
				'id' => -1,
				'title' => 'Сбросить значение'
			));
			break;

		//подключаемый список
		case 29:
			foreach($send as $n => $r) {
				if($r['id'] <= 0)
					continue;
				$send[$n]['title'] = 'установить "'.$r['title'].'"';
				$send[$n]['content'] = 'установить "<b>'.$r['title'].'</b>"';
			}
			array_unshift($send, array(
				'id' => -1,
				'title' => 'Сбросить значение'
			));
			break;
	}

	return $send;
}



