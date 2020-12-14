<?php

/* [11] Вставка значения записи */
function _element11_struct($el) {
	/*
		Вставка элемента через функцию PHP12_v_choose
	*/

	return array(
		'parent_id' => _num($el['parent_id']),
		'width' => _num($el['width']),

		'font'      => $el['font'],
		'color'     => $el['color'],
		'size'      => $el['size'] ? _num($el['size']) : 13,

		'txt_2'     => $el['txt_2'],   //id элемента, выбранного из диалога, который вносит данные списка
								       //возможна иерархия элементов через запятую: 256,1312,560
		'num_7' => _num($el['num_7']),//ограничение высоты (настройка стилей для [60] изображения)
		'num_8' => _num($el['num_8']) //закруглённые углы (настройка стилей для [60] изображения)
	) + _elementStruct($el);
}
function _element11_title($el) {
	$send = '';
	foreach(_ids($el['txt_2'], 'arr') as $elem_id) {
		if(!$ell = _elemOne($elem_id))
			return '-[11:'.$elem_id.']-not-elem-';

		//элементы, для которых путь не пишется
		switch($ell['dialog_id']) {
			case 60: //изображение
			case 76: //видеоролик
				$ell['elp'] = $el;
				return _element('title', $ell);
		}

		//вложенное значение
		if(!empty($ell['issp'])) {
			switch($ell['dialog_id']) {
				case 16:
					$send .= _dialogParam($ell['num_4'], 'name').' » ';
					break;
				case 29:
				case 59:
					$send .= _dialogParam($ell['num_1'], 'name').' » ';
					break;
				case 300:
					$send .= 'VK » ';
					break;
			}
			continue;
		}

		$send .= _element('title', $ell);
	}
	return $send;
}
function _element11_print($el, $prm) {
	if(!$u = _unitGet($prm))
		return _element('title', $el);
	if(!$ids = _ids($el['txt_2'], 'arr'))
		return _msgRed('[11] нет ids элементов');

	$unit = $u;

	foreach($ids as $elem_id) {
		if($elem_id < 0) {
			switch($elem_id) {
				case -31://значение v1
					if(!$v1 = @$_GET['v1'])
						return '';
					if(preg_match(REGEXP_YEARMON, $v1)) {
						$ex = explode('-', $v1);
						return _monthDef($ex[1]).' '.$ex[0];
					}
					return $v1;
			}
			return _msgRed('[11:'.$elem_id.'] sub_sero');
		}
		if(!$ell = _elemOne($elem_id))
			return _msgRed('[11:'.$elem_id.'] not_exist');

		if(!empty($ell['issp'])) {
			if(!$col = _elemCol($ell))
				return _msgRed('[11:'.$elem_id.'] not_col');
			if(!isset($u[$col]))
				return _msgRed('[11:'.$elem_id.'] not_val');

			$u = $u[$col];

			if(!is_array($u)) {
				if(empty($u)) {
					if($ell['dialog_id'] == 29)
						return $ell['txt_1'];
					return '';
				}
				$ell['elp'] = $el;
				if($ell['dialog_id'] == 300)
					return _elem300p301($ell, $unit, _idsLast($el['txt_2']));
				return $u;
			}

			continue;
		}

		$ell['elp'] = $el;
		return _element('print11', $ell, $u);
	}

	return _msgRed('[11] ('.$el['txt_2'].') no_print');
}
function _element11_title_get() {
	return 'любое значение';
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
function _element11_vvv_count($el, $prm) {
	if(!$last_id = _idsLast($el['txt_2']))
		return 0;

	return _element('vvv_count', $last_id, $prm);
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
	$obj_id = PHP12_v_choose_dss($prm);

	//выбор элемента-значения через [13]
	$obj_id = PHP12_v_choose_13($prm, $obj_id);

	//ячейка таблицы
	$obj_id = PHP12_v_choose_23($prm, $obj_id);

	//сборный текст
	$obj_id = PHP12_v_choose_44($prm, $obj_id);

	//блок со страницы
	$obj_id = PHP12_v_choose_page($prm, $obj_id);

	//блок из диалога
	$obj_id = PHP12_v_choose_dialog($prm, $obj_id);

	//элемент записи
	$obj_id = PHP12_v_choose_spisok($prm, $obj_id);

	//шаблон записи
	$obj_id = PHP12_v_choose_43($prm, $obj_id);

	//блок из содержания удаления записи
	$obj_id = PHP12_v_choose_dialog_del($prm, $obj_id);

	//настройка баланса [27]
	$obj_id = PHP12_v_choose_27($prm, $obj_id);

	//выплывающая подсказка [act229]
	$obj_id = _hintDlgId($prm, $obj_id);

	if($obj_id === false)
		return _emptyMin10('[11] Не найдена схема поиска объекта.');
	if(!$obj_id)
		return _emptyMin10('[11] Объект не найден.');
	//сообщение об ошибке из одной из схем поиска
	if(!_num($obj_id))
		return _emptyMin10($obj_id);

	if(!defined('OBJ_NAME_CHOOSE'))
		define('OBJ_NAME_CHOOSE', 'dialog');

	$DLG_PARENT = '';
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
			$TITLE = 'Диалог';
			$NAME = $dialog['name'];

			if($pid = _num($dialog['dialog_id_parent']))
				if($DP = _dialogQuery($pid)) {
					$cond = array(
						'elm_choose' => 1,
						'elm_sel' => $prm['dop']['sel'],
						'elm_allow' => $prm['dop']['allow']
					);
					$DLG_PARENT = '<div class="fs14 pad10 pl15 bg-orange line-b">Диалог-родитель <b class="fs14">'.$DP['name'].'</b>:</div>'.
								  _blockHtml('dialog', $pid, $cond);
				}
			break;
		default:
			return _emptyMin10('Неизвестный объект <b>'.OBJ_NAME_CHOOSE.'</b>.');
	}

	$cond = array(
		'elm_choose' => 1,
		'elm_sel' => $prm['dop']['sel'],
		'elm_allow' => $prm['dop']['allow']
	);

	//для повторного (реального) вызова vvv, чтобы получить данные об блоках и элементах
	define('OBJ_ID_CHOOSE', $obj_id);

	return
($prm['dop']['first'] ?
	'<div class="bg4 pad10 pl5 line-b">'.
		'<input type="hidden" id="choose-menu" value="'.PHP12_v_choose_menuSel($prm).'" />'.
	'</div>'.

	'<div class="choose-menu-1">'.PHP12_v_choose_global($prm).'</div>'.
	'<div class="choose-menu-2 pad10">id, дата внесения, кто внёс</div>'.
	'<div class="choose-menu-3">'
: '').

		'<div class="fs14 pad10 pl15 bg-orange line-b">'.$TITLE.' <b class="fs14">'.$NAME.'</b>:</div>'.
		_blockHtml(OBJ_NAME_CHOOSE, $obj_id, $cond).

($prm['dop']['first'] ?
	'</div>'.

($DLG_PARENT ?
	'<div class="choose-menu-4">'.$DLG_PARENT.'</div>'
: '')

: '').
	'';
}
function PHP12_v_choose_menuSel($prm) {//выбранный пункт меню
	$sel = 3;

	if(!$v = _idsFirst($prm['dop']['sel']))
		return $sel;

	if($v == -21)
		return 1;
	if($v == -22)
		return 1;
	if($v == -23)
		return 1;
	if($v == -24)
		return 1;
	if($v == -31)
		return 1;

	return $sel;
}
function PHP12_v_choose_global($prm) {//глобальные значения для выбора
	$v = _idsFirst($prm['dop']['sel']);

	return
	'<div class="prel pad10">'.
		'<div class="elm-choose'.($v == -21 ? ' sel' : '').'" val="-21"></div>'.
		'<div class="fs17 b center pad10 clr9">Текущий пользователь</div>'.
	'</div>'.
	'<div class="prel pad10">'.
		'<div class="elm-choose'.($v == -24 ? ' sel' : '').'" val="-24"></div>'.
		'<div class="fs17 b center pad10 clr9">Текущая страница</div>'.
	'</div>'.
	'<div class="prel pad10">'.
		'<div class="elm-choose'.($v == -22 ? ' sel' : '').'" val="-22"></div>'.
		'<div class="fs17 b center pad10 clr9">Текущий диалог</div>'.
	'</div>'.
	'<div class="prel pad10">'.
		'<div class="elm-choose'.($v == -23 ? ' sel' : '').'" val="-23"></div>'.
		'<div class="fs17 b center pad10 clr9">Текущая запись</div>'.
		'<div class="center pad5 clr1">Если открыт диалог для редактирования данных</div>'.
	'</div>'.
	'<div class="prel pad10">'.
		'<div class="elm-choose'.($v == -31 ? ' sel' : '').'" val="-31"></div>'.
		'<div class="fs17 b center pad10 clr9">Значение v1</div>'.
	'</div>';
}
function PHP12_v_choose_vvv($prm) {
	$dop = array(
		'mysave' => 0,      //сохранение данных будет происходить через собственную функцию
		'is13' => 0,        //через элемент [13]
		'sev' => 0,         //выбор нескольких значений-элементов
		'nest' => 1,        //возможность выбора из вложенного списка
		'dlg24' => 0,       //выбранный диалог через select [24]
		'sel' => 0,         //выбранные значения
		'allow' => '',      //разрешённые значения
		'first' => 1,       //открытие первого диалога [11]. При этом создаются глобальные переменные в JS
		'jselm' => array()  //данные об выбираемых элементах
	);

	if(!empty($prm['unit_edit']))
		$dop['sel'] = $prm['unit_edit']['txt_2'];
	if(defined('OBJ_NAME_CHOOSE') && defined('OBJ_ID_CHOOSE')) {
		$dop['jselm'] = _elmJs(OBJ_NAME_CHOOSE, OBJ_ID_CHOOSE);
		//добавление элементов из родительского диалога, если есть
		if(OBJ_NAME_CHOOSE == 'dialog')
			if($dlg = _dialogQuery(OBJ_ID_CHOOSE))
				if($parent_id = $dlg['dialog_id_parent'])
					$dop['jselm'] += _elmJs('dialog', $parent_id);
	}

	return $prm['dop'] + $dop;
}
function PHP12_v_choose_dss($prm) {//ID диалога из dss
	if(!$dss = _num($prm['srce']['dss']))
		return false;
	if($dss == 200)
		return false;
	if($dss == 210)
		return false;
	if($dss == 230)
		return false;
	return $dss;
}
function PHP12_v_choose_BL($prm) {//получение данных исходного блока
	if(empty($prm['srce']))
		return array();
	if($BL = _blockOne($prm['srce']['block_id']))
		return $BL;
	if($EL = _elemOne($prm['srce']['element_id']))
		if($BL = _blockOne($EL['block_id']))
			return $BL;
	return array();
}
function PHP12_v_choose_13($prm, $dialog_id) {//клик по элементу [13]
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

	if(!$BL = PHP12_v_choose_BL($prm))
		return false;

	//если список, получение id диалога, размещающего список
	if($BL['obj_name'] == 'spisok') {
		//определение местоположения элемента [13]
		if($bl13 = _blockOne($el13['block_id']))
			if($bl13['obj_name'] == 'dialog') {
				if(!$DLG = _dialogQuery($bl13['obj_id']))
					return 'Диалога '.$bl13['obj_id'].' не существует, содержащего элемент [13].';
			}
		if(!$ell = _elemOne($BL['obj_id']))
			return 'Элемента, размещающего список, не существует.';

		return $ell['num_1'];
	}

	//также может происходить выбор со страницы
	if($BL['obj_name'] == 'page') {
		//запрос произошёл из ячейки таблицы, находящейся на странице
		if($dlg_id = PHP12_v_choose_23($prm, 0))
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
//		if($parent_id = $DLG['dialog_id_parent'])
//			return $parent_id;

		//выбор для ячейки диалога
		if($ell = _elemOne($BL['elem_id']))
			if($ell['dialog_id'] == 23)
				return $ell['num_1'];

		return $BL['obj_id'];
	}

	return '[13] неизвестно, где искать диалог';
}
function PHP12_v_choose_23($prm, $dialog_id) {//ячейка таблицы
	if($dialog_id)
		return $dialog_id;
	if($EL = _elemOne($prm['srce']['element_id'])) {
		if(!$EL23 = _elemOne($EL['parent_id']))
			return false;
	} else
		if($BL = PHP12_v_choose_BL($prm))
			if(!$EL23 = _elemOne($BL['elem_id']))
				return false;

	if(!isset($EL23))
		return false;

	if($EL23['dialog_id'] != 23)
		return false;

	return _num($EL23['num_1']);
}
function PHP12_v_choose_44($prm, $obj_id) {//сборный текст
	if($obj_id)
		return $obj_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if(!$EL = _elemOne($BL['elem_id']))
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
function PHP12_v_choose_page($prm, $dialog_id) {//блок со страницы
	if($dialog_id !== false)
		return $dialog_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if($BL['obj_name'] != 'page')
		return false;
	if(!$page = _page($BL['obj_id']))
		return 'Страницы '.$BL['obj_id'].' не существует.';
	if(!$dialog_id = $page['dialog_id_unit_get'])
		return 'Страница не принимает данные записи';

	return $dialog_id;
}
function PHP12_v_choose_dialog($prm, $dialog_id) {//блок из диалога
	if($dialog_id !== false)
		return $dialog_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if($BL['obj_name'] != 'dialog')
		return false;
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return 'Диалога '.$BL['obj_id'].' не существует.';
//	if($parent_id = $DLG['dialog_id_parent'])
//		return $parent_id;
//	if($get_id = $DLG['dialog_id_unit_get'])
//		return $get_id;
	return $BL['obj_id'];
}
function PHP12_v_choose_spisok($prm, $obj_id) {//элемент из записи
	if($obj_id)
		return $obj_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if($BL['obj_name'] != 'spisok')
		return false;
	if(!$el = _elemOne($BL['obj_id']))
		return '[11] Элемента-списка не существует';

	return $el['num_1'];
}
function PHP12_v_choose_43($prm, $obj_id) {//шаблон записи
	if($obj_id)
		return $obj_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if($BL['obj_name'] != 'tmp43')
		return false;
	if(!$el = _elemOne($BL['obj_id']))
		return 'Элемента-списка не существует';
	if(!$bl = _blockOne($el['block_id']))
		return '[43] Не получен блок, в котором размещается элемент-список';
	if($bl['obj_name'] != 'dialog')
		return '[43] Блок размещается не в диалоге '._pr($bl);

	return $bl['obj_id'];
}
function PHP12_v_choose_dialog_del($prm, $obj_id) {//блок из содержания удаления единицы списка
	if($obj_id)
		return $obj_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if($BL['obj_name'] != 'dialog_del')
		return false;

	return _num($BL['obj_id']);
}
function PHP12_v_choose_27($prm, $obj_id) {//[27] значение баланса
	if($obj_id)
		return $obj_id;
	if(!$BL = PHP12_v_choose_BL($prm))
		return false;
	if(!$EL = _elemOne($BL['elem_id']))
		return false;
	if($EL['dialog_id'] != 27)
		return false;

	return _num($BL['obj_id']);
}



