<?php

/* [11] Вставка значения записи */
function _element11_struct($el, $ELM=array()) {
	/*
		Вставка элемента через функцию PHP12_v_choose
	*/

	global $G_ELEM;
	if(empty($ELM))
		$ELM = $G_ELEM;

	$send = array(
		'parent_id' => _num($el['parent_id']),

		'font'      => $el['font'],
		'color'     => $el['color'],
		'size'      => $el['size'] ? _num($el['size']) : 13,

		'txt_2'     => $el['txt_2'],    //id элемента, выбранного из диалога, который вносит данные списка
								        //возможна иерархия элементов через запятую: 256,1312,560
		'txt_7'     => $el['txt_7'],    //текст слева (для истории действий)
		'txt_8'     => $el['txt_8']     //текст справа (для истории действий)
	) + _elementStruct($el);

	if($last_id = _idsLast($el['txt_2']))
		if(isset($ELM[$last_id])) {
			$el11 = $ELM[$last_id];

			//разрешать настройку стилей (правило 11)
			if(_elemRule($el11['dialog_id'], 11))
				$send['stl'] = 1; //для JS

			//разрешать настройку перехода на страницу или открытие диалога
			if(_elemRule($el11['dialog_id'], 16))
				$send['url_use'] = 1;

			//является изображением
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
		if(!isset($ELM[$id])) {
			$el['title'] = '-title-no-find-';
			return $el;
		}

		$ell = $ELM[$id];

		//для изображения путь не пишется
		if($ell['dialog_id'] == 60) {
			if($pid = _num($el['parent_id']))
				$el['title'] = 'IMG';
			else
				$el['title'] = _imageNo($el['width'], $el['num_8']);
			return $el;
		}

		//вложенное значение
		if(_elemIsConnect($ell)) {
			$dlg = $DLGS[$ell['num_1']];
			$el['title'] .= $dlg['name'].' » ';
			continue;
		}

		$el['title'] .= _element('title', $ell);
	}
	return $el;
}
function _element11_js($el) {
	$send = _elementJs($el);

	//дополнительные значения для изображений
	if($last = _idsLast($el['txt_2']))
		if($ell = _elemOne($last)) {
			if($ell['dialog_id'] == 60)
				$send += array(
					'num_7' => _num(@$el['num_7']),//[60] ограничение высоты
					'num_8' => _num(@$el['num_8']) //[60] закруглённые углы
				);
			//разрешать настройку условий отображения
			if(_elemRule($ell['dialog_id'], 14))
				$send['eye'] = 1;
			}

	return $send;
}
function _element11_print($el, $prm) {
	if(!$u = @$prm['unit_get'])
		return $el['title'];
	if(!$ids = _ids($el['txt_2'], 'arr'))
		return _msgRed('[11] нет ids элементов');

	foreach($ids as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');

		if(_elemIsConnect($ell))
			return _element29_print11($el, $u);

		if(!_elemIsConnect($ell)) {
			$ell['elp'] = $el;//вставка родительского элемента (для подсветки при быстром поиске)
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
function _element11_template_docx($el, $u) {
	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return '';
		//вложенное значение становится записью
		if(_elemIsConnect($ell)) {
			if(!$col = $ell['col'])
				return '';
			if(!$u = $u[$col])
				return '';
			continue;
		}
		return _element('template_docx', $ell, $u);
	}
	return '';
}

/* ---=== ВЫБОР ЗНАЧЕНИЯ ИЗ ДИАЛОГА [11] ===--- */
function PHP12_v_choose($prm) {
/*
	Исходные данные через PHP12_v_choose_vvv

	OBJ_NAME_CHOOSE - по умолчанию выводится диалог. Будет меняться, если требуется
*/

	$prm['dop'] = PHP12_v_choose_vvv($prm);

	//Изначально obj_id = false. По этому флагу будет определяться, в какой именно функции будет производиться поиск объекта
	//В начале всегда проверяется прямое указание на диалог
	if(!$obj_id = PHP12_v_choose_dss($prm)) {
		if(!$block_id = _num($prm['srce']['block_id']))
			return _emptyMin10('Отсутствует исходный блок.');
		if(!$BL = _blockOne($block_id))
			return _emptyMin10('Блока '.$block_id.' не существует.');

		//выбор элемента-значения через [13]
		$obj_id = PHP12_v_choose_13($BL, $prm, $obj_id);

		//ячейка таблицы
		$obj_id = PHP12_v_choose_23($BL, $obj_id);

		//сборный текст
		$obj_id = PHP12_v_choose_44($BL, $obj_id);

		//блок со страницы
		$obj_id = PHP12_v_choose_page($BL, $obj_id);

		//блок из диалога
		$obj_id = PHP12_v_choose_dialog($BL, $obj_id);

		//элемент записи
		$obj_id = PHP12_v_choose_spisok($BL, $obj_id);

		//блок из содержания удаления записи
		$obj_id = PHP12_v_choose_dialog_del($BL, $obj_id);

		//настройка баланса [27]
		$obj_id = PHP12_v_choose_27balans($BL, $obj_id);
	}

	if($obj_id === false)
		return _emptyMin10('Не найдена схема поиска объекта.');
	if(!$obj_id)
		return _emptyMin10('Объект не найден.');
	//сообщение об ошибке из одной из схем поиска
	if(!_num($obj_id))
		return _emptyMin10($obj_id);

	if(!defined('OBJ_NAME_CHOOSE'))
		define('OBJ_NAME_CHOOSE', 'dialog');

	switch(OBJ_NAME_CHOOSE) {
		case 'page':
			if(!$page = _page($obj_id))
				return _emptyMin10('Страницы '.$obj_id.' не существует.');
			$TITLE = 'Страница';
			$NAME = $page['name'];
			break;
		case 'dialog':
			if(!$dialog = _dialogQuery($obj_id))
				return _emptyMin10('Диалога '.$obj_id.' не существует.');
			$TITLE = 'Диалоговое окно';
			$NAME = $dialog['name'];
			break;
		default:
			return _emptyMin10('Неизвестный объект <b>'.OBJ_NAME_CHOOSE.'</b>.');
	}


	$cond = array(
		'elm_choose' => 1,
		'elm_sel' => $prm['dop']['sel'],
		'elm_allow' => $prm['dop']['allow']
	);

	return
($prm['dop']['first'] ?
	'<div class="bg-gr2 pad10 pl5 line-b">'.
		'<input type="hidden" id="choose-menu" value="2" />'.
	'</div>'.

	'<div class="choose-menu-1">'.
		'<div class="prel pad10">'.
			'<div class="elm-choose" val="-21"></div>'.
			'<input type="text" class="over1 color-555 b w100p curP" readonly value="Текущий пользователь">'.
		'</div>'.
	'</div>'
: '').

	'<div class="choose-menu-2">'.
		'<div class="fs14 pad10 pl15 bg-orange line-b">'.$TITLE.' <b class="fs14">'.$NAME.'</b>:</div>'.
		_blockHtml(OBJ_NAME_CHOOSE, $obj_id, $cond).
	'</div>'.

($prm['dop']['first'] ?
	'<div class="choose-menu-3">'.
		'Стандартные значения'.
	'</div>'
: '').
	'';
}
function PHP12_v_choose_vvv($prm) {
	$dop = array(
		'mysave' => 0,  //сохранение данных будет происходить через собственную функцию
		'is13' => 0,    //через элемент [13]
		'sev' => 0,     //выбор нескольких значений-элементов
		'nest' => 1,    //возможность выбора из вложенного списка
		'dlg24' => 0,   //выбранный диалог через select [24]
		'sel' => 0,     //выбранные значения
		'allow' => '',  //разрешённые значения
		'first' => 1    //открытие первого диалога [11]. При этом создаются глобальные переменные в JS
	);

	if($u = $prm['unit_edit'])
		$dop['sel'] = $u['txt_2'];

	return $prm['dop'] + $dop;
}
function PHP12_v_choose_dss($prm) {//ID диалога из dss
	if(!$dss = _num($prm['srce']['dss']))
		return false;
	if($dss == 200)
		return false;
	if($dss == 210)
		return false;
	if($dss == 220)
		return false;
	if($dss == 230)
		return false;
	return $dss;
}
function PHP12_v_choose_13($BL, $prm, $dialog_id) {//клик по элементу [13]
	if($dialog_id !== false)
		return $dialog_id;
	//передаёт id элемента, который размещает [13]
	if(!$el13_id = $prm['dop']['is13'])
		return false;
	if(!$el13 = _elemOne($el13_id))
		return 'Элемента '.$el13_id.' не существует.';


	switch($el13['num_3']) {
		//в исходном диалоге
		case 8058: break;

		//по элементу в исходном диалоге
		case 8059:
			if(!_elemOne($el13['num_1']))
				return '[13] Элемента со диалогами не существует';
			if(!$dlg24 = $prm['dop']['dlg24'])
				return '[13] Не выбран диалог в списке';
			return _dialogSel24($el13['num_1'], $dlg24);

		//на текущей странице
		case 8060:
			define('OBJ_NAME_CHOOSE', 'page');
			return _page('cur');

		//из диалога, данные которого получает страница
		case 8061:
			$page_id = _page('cur');
			if(!$page = _page($page_id))
				return '[13] Не получены данные страницы '.$page_id;
			if(!$dlg_id = $page['dialog_id_unit_get'])
				return '[13] Страница не принимает данные записи';
			return $dlg_id;
	}



//	return '[13] Не доделано';



	//если список, получение id диалога, размещающего список
	if($BL['obj_name'] == 'spisok') {
		//определение местоположения элемента [13]
		if($el13['block']['obj_name'] == 'dialog') {
			if(!$DLG = _dialogQuery($el13['block']['obj_id']))
				return 'Диалога '.$el13['block']['obj_id'].' не существует, содержащего элемент [13].';
		}
		if(!$ell = _elemOne($BL['obj_id']))
			return 'Элемента, размещающего список, не существует.';

		return $ell['num_1'];
	}

	//также может происходить выбор со страницы
	if($BL['obj_name'] == 'page') {
		//запрос произошёл из ячейки таблицы, находящейся на странице
		if($dlg_id = PHP12_v_choose_23($BL, 0))
			return $dlg_id;
		if(!$page = _page($BL['obj_id']))
			return 'Страницы '.$BL['obj_id'].' не существует.';
		if($page['dialog_id_unit_get'])
			return $page['dialog_id_unit_get'];
		define('OBJ_NAME_CHOOSE', 'page');
		return $BL['obj_id'];
	}

	//если указан диалог, проверка, чтобы был отправлен id родительского диалога
	if($BL['obj_name'] == 'dialog') {
		if(!$DLG = _dialogQuery($BL['obj_id']))
			return 'Диалога '.$BL['obj_id'].' не существует.';
		if($parent_id = $DLG['dialog_id_parent'])
			return $parent_id;

		//выбор для ячейки диалога
		if(!empty($BL['elem'])) {
			$ell = $BL['elem'];
			if($ell['dialog_id'] == 23)
				return $ell['num_1'];
		}

		return $BL['obj_id'];
	}

	return '[13] неизвестно, где искать диалог';
}
function PHP12_v_choose_23($BL, $dialog_id) {//ячейка таблицы
	if($dialog_id)
		return $dialog_id;
	if(!$EL = $BL['elem'])
		return false;
	if($EL['dialog_id'] != 23)
		return false;

	return _num($EL['num_1']);
}
function PHP12_v_choose_44($BL, $obj_id) {//сборный текст
	if($obj_id)
		return $obj_id;
	if(!$EL = $BL['elem'])
		return false;
	if($EL['dialog_id'] != 44)
		return false;

	switch($BL['obj_name']) {
		case 'page':   return false; //диалог будет найден в PHP12_v_choose_page
		case 'dialog': return _num($BL['obj_id']);
		case 'spisok': return false; //диалог будет найден в PHP12_v_choose_spisok
	}
	return 0;
}
function PHP12_v_choose_page($BL, $dialog_id) {//блок со страницы
	if($dialog_id !== false)
		return $dialog_id;
	if($BL['obj_name'] != 'page')
		return false;
	if(!$page = _page($BL['obj_id']))
		return 'Страницы '.$BL['obj_id'].' не существует.';
	if(!$dialog_id = $page['dialog_id_unit_get'])
		return 'Страница не принимает данные записи';

	return $dialog_id;
}
function PHP12_v_choose_dialog($BL, $dialog_id) {//блок из диалога
	if($dialog_id !== false)
		return $dialog_id;
	if($BL['obj_name'] != 'dialog')
		return false;
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return 'Диалога '.$BL['obj_id'].' не существует.';
//	if($parent_id = $DLG['dialog_id_parent'])
//		return $parent_id;
	if($get_id = $DLG['dialog_id_unit_get'])
		return $get_id;
	return $BL['obj_id'];
}
function PHP12_v_choose_spisok($BL, $obj_id) {//элемент из записи
	if($obj_id)
		return $obj_id;
	if($BL['obj_name'] != 'spisok')
		return false;
	if(!$el = _elemOne($BL['obj_id']))
		return 'Элемента-списка не существует.';

	return $el['num_1'];
}
function PHP12_v_choose_dialog_del($BL, $obj_id) {//блок из содержания удаления единицы списка
	if($obj_id)
		return $obj_id;
	if($BL['obj_name'] != 'dialog_del')
		return false;

	return _num($BL['obj_id']);
}
function PHP12_v_choose_27balans($BL, $dialog_id) {//ячейка таблицы
	if($dialog_id)
		return $dialog_id;
	if(!$EL = $BL['elem'])
		return false;
	if($EL['dialog_id'] != 27)
		return false;

	return _num($BL['obj_id']);
}



