<?php

/* [11] ¬ставка значени€ записи */
function _element11_struct($el, $ELM=array()) {
	/*
		¬ставка элемента через функцию PHP12_v_choose
	*/

	global $G_ELEM;
	if(empty($ELM))
		$ELM = $G_ELEM;

	$send = array(
		'parent_id' => _num($el['parent_id']),

		'txt_2'     => $el['txt_2'],    //id элемента, выбранного из диалога, который вносит данные списка
								        //возможна иерархи€ элементов через зап€тую: 256,1312,560
		'txt_7'     => $el['txt_7'],    //текст слева (дл€ истории действий)
		'txt_8'     => $el['txt_8']     //текст справа (дл€ истории действий)
	) + _elementStruct($el);

	if($last_id = _idsLast($el['txt_2']))
		if(isset($ELM[$last_id])) {
			$el11 = $ELM[$last_id];

			//разрешать настройку стилей (правило 11)
			if(_elemRule($el11['dialog_id'], 11)) {
				$send['stl'] = 1; //дл€ JS
				$send['color'] = $el['color'];
				$send['font']  = $el['font'];
				$send['size']  = $el['size'] ? _num($el['size']) : 13;
			}

			//разрешать настройку перехода на страницу или открытие диалога
			if(_elemRule($el11['dialog_id'], 16))
				$send['url_use'] = 1;

			//€вл€етс€ изображением
			if($el11['dialog_id'] == 60) {
				$send['width'] = empty($el['width']) ? 30 : _num($el['width']);
				$send['num_7'] = _num($el['num_7']);
				$send['num_8'] = _num($el['num_8']);
				$send['immg'] = 1;
			}
	}

	return $send;
}
function _element11_struct_title($el, $ELM, $DLGS=array()) {
	$el['title'] = '';
	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!isset($ELM[$id]))
			return $el;

		$ell = $ELM[$id];

		//дл€ изображени€ путь не пишетс€
		if($ell['dialog_id'] == 60) {
			$el['title'] = _imageNo($el['width'], $el['num_8']);
			return $el;
		}

		//вложенное значение
		if(_elemIsConnect($ell)) {
			$dlg = $DLGS[$ell['num_1']];
			$el['title'] .= $dlg['name'].' ї ';
			continue;
		}

		$el['title'] .= _element('title', $ell);
	}
	return $el;
}
function _element11_js($el) {
	$send = _elementJs($el);

	//дополнительные значени€ дл€ изображений
	if($last = _idsLast($el['txt_2']))
		if($ell = _elemOne($last)) {
			if($ell['dialog_id'] == 60)
				$send += array(
					'num_7' => $el['num_7'],//[60] ограничение высоты
					'num_8' => $el['num_8'] //[60] закруглЄнные углы
				);
			//разрешать настройку условий отображени€
			if(_elemRule($ell['dialog_id'], 14))
				$send['eye'] = 1;
			}

	return $send;
}
function _element11_print($el, $prm) {
	if(!$u = @$prm['unit_get'])
		return $el['title'];
	if(empty($el['txt_2']))
		return _msgRed('[11] нет ids элементов');

	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');

		if(_elemIsConnect($ell))
			return _element29_print11($el, $u);

		if(!_elemIsConnect($ell)) {
			$ell['elp'] = $el;//вставка родительского элемента (дл€ подсветки при быстром поиске)
			return _element('print11', $ell, $u);
		}

		if(empty($ell['col']))
			return _msgRed('-cnn-col-yok-');

		$col = $ell['col'];
		$u = $u[$col];

		if(is_array($u))
			continue;

		if($ell['dialog_id'] == 29)
			return $ell['txt_1'];

		return _msgRed('значение отсутствует');
	}

	return _msgRed('-11-yok-');
}

