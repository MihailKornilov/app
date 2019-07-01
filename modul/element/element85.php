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
function _element85_js($el) {
	return array(
		'txt_1' => $el['txt_1'],
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
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
			'content' => '<div class="b color-pay">Совпадает с текущей страницей</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет установлено значение записи, которую принимает текущая страница</div>'
		);

	if($el['num_3'])
		$send[] = array(
			'id' => -2,
			'title' => 'Совпадает с данными, приходящими на диалог',
			'content' => '<div class="b color-pay">Совпадает с данными, приходящими на диалог</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет установлено значение записи, которая приходит на открываемое диалоговое окно</div>'
		);

	if($el['num_4'])
		$send[] = array(
			'id' => -21,
			'title' => 'Текущий пользователь',
			'content' => '<div class="b color-pay">Текущий пользователь</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет выбран текущий пользователь</div>'
		);

	$send = _elem201init($el, $prm, $send);

	if(!$u = $prm['unit_edit'])
		return $send;

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
			'title' => is_array($sp) ? _pr($sp) : $sp
		);
	}

	return $send;
}



