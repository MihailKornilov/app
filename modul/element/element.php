<?php
/*
	Каждый элемент должен пройти проверку:
		1. Визуальное отображение в блоке {print}
		2. Структура элемента {struct}
		3. Title элемента {struct_title}
		4. Дочерние элементы или значения {struct_vvv} - статическое формирование (сохранение в кеш)
		5. Дочерние элементы или значения {vvv} - динамическое формирование
		6. Настройка ширины (в PageSetup) ['width']
		7. Наличие флага обязательного заполнения ['req']
		8. Установка фокуса ['focus']
		9. Значения для JS
	   10. Действия
	   11. Подсказки
	   12. Элемент [11]: вывод значения {print11}
	   13. Форматирование
	   14. Правила
*/

function _elementType($type, $el=array(), $prm=array()) {//все возможные варианты манипуляций
	switch($type) {
		//вывод элемента на экран
		case 'print':
			if(empty($el['dialog_id']))
				return '';
			return DEBUG ? '<span class="fs11 red">['.$el['dialog_id'].']</span>' : '';

		//вывод значения на экран через [11]
		case 'print11':
			$PARAM = _blockParam();
			$PARAM['unit_get'] = $prm;
			return _element('print', $el, $PARAM);

		//печать значения истории действий
		case 'history': return $prm;

		//структура элемента: колонки, поля, подсказки, действия, форматирование
		case 'struct':       return _elementStruct($el);
		case 'struct_title':
			$el['title'] = '';
			if(!empty($el['name']))
				$el['title'] = $el['name'];
			return $el;
		//структура содержания: дочерние элементы или значения
		case 'struct_vvv': return array();

		//содержание элемента (ячейки таблицы, значения выпадающего списка, ...)
		case 'vvv':
			if(!empty($el['vvv']))
				return $el['vvv'];
			return array();

		//структура элемента для JS
		case 'js':
			if(empty($el))
				return array();
			return _elementJs($el);

		//получение названия элемента
		case 'title':
			if(!empty($el['title']))
				return $el['title'];
			if(!empty($el['name']))
				return $el['name'];
			if(empty($el['dialog_id']))
				return '';

			$el = _elementTitle($el);

			return $el['title'];

		//скрытие блока при нулевом значении
		case 'action231':
			//здесь $prm - сама запись
			if(empty($el['col']))
				return false;
			if(!empty($prm[$el['col']]))
				return false;
			return true;
	}

	return '';
}
function _elementStruct($el) {//структура элемента - базовые компоненты
	$send = array(
		'id'        => _num($el['id']),
		'app_id'    => _num($el['app_id']),
		'block_id'  => _num($el['block_id']),
		'dialog_id' => _num($el['dialog_id']),
		'mar'       =>      $el['mar'],

		'txt_10'    => $el['txt_10']     //для шаблонов документов todo временно
	);

	if(!empty($el['name']))
		$send['name'] = $el['name'];
	if(!empty($el['col']))
		$send['col'] = $el['col'];

	if(!empty($el['width_min']))
		$send['width_min'] = _num($el['width_min']);
	if(!empty($el['width_max']))
		$send['width_max'] = _num($el['width_max']);
	if($el['width'] || !empty($el['width_min']))
		$send['width'] = _num($el['width']);

	if(!empty($el['focus']))
		$send['focus'] = 1;

	if(!empty($el['hidden']))
		$send['hidden'] = 1;
	if(!empty($el['afics']))
		$send['afics'] = $el['afics'];

	//разрешать настройку стилей (правило 12)
	if(_elemRule($el['dialog_id'], 12)) {
		$send['stl'] = 1; //для JS
		$send['color'] = $el['color'];
		$send['font']  = $el['font'];
		$send['size']  = $el['size'] ? _num($el['size']) : 13;
	}

	//диалог для управления действиями
	if(!empty($el['eadi']))
		$send['eadi'] = _num($el['eadi']);

	return $send;
}
function _elementTitle($el) {//вставка title элемента (после сформированного кеша)
	if(empty($el['dialog_id']))
		return $el;

	global $G_DLG, $G_ELEM;

	if($el['dialog_id'] == 11)
		$el = _element11_struct_title($el, $G_ELEM, $G_DLG);
	else
		$el = _element('struct_title', $el, $G_DLG);

	return $el;
}
function _elementJs($el) {//структура элемента для JS
	$send = array(
		'dialog_id' => $el['dialog_id'],
		'block_id'  => $el['block_id'],
		'mar'       => $el['mar']
	);

	if(!empty($el['width']))
		$send['width'] = $el['width'];
	if(!empty($el['focus']))
		$send['focus'] = 1;
	if(!empty($el['afics']))
		$send['afics'] = $el['afics'];
	if(!empty($el['hint']))
		$send['hint'] = $el['hint'];

	//разрешать настройку стилей (правило 12)
	if(!empty($el['stl'])) {
		$send['stl'] = 1;
		$send['color'] = $el['color'];
		$send['font']  = $el['font'];
		$send['size']  = _num($el['size']);
	}

	//диалог для управления действиями
	if(!empty($el['eadi']))
		$send['eadi'] = $el['eadi'];

	//элемент является подключаемым списком
	if(_elemIsConnect($el))
		$send['issp'] = 1;

	//разрешать прикрепление подсказки
	if(_elemRule($el['dialog_id'], 15))
		$send['rule15'] = 1;

	//разрешать настройку перехода на страницу или открытие диалога
	if(_elemRule($el['dialog_id'], 13) || !empty($el['url_use']))
		$send['url_use'] = 1;

	//разрешать настройку условий отображения
	if(_elemRule($el['dialog_id'], 17))
		$send['eye'] = 1;

	if(!empty($el['action']))
		$send['action'] = $el['action'];

	if(!empty($el['immg']))
		$send['immg'] = 1;

	return $send;
}
function _element($type, $el, $prm=array()) {//все манипуляции, связанные с элементом
	if(empty($el))
		return _elementType($type, $el);

	if(!is_array($el))
		$el = _elemOne($el);

	if(!$dlg_id = _num(@$el['dialog_id']))
		return _elementType($type);

	//тип манипуляции добавляется в конце функции. Например: _element1_struct
	$fname = '_element'.$dlg_id.'_'.$type;
	if(function_exists($fname))
		return $fname($el, $prm);

	return _elementType($type, $el, $prm);
}

/* [1] Галочка */
function _element1_struct($el) {
	return array(
		'def'   => _num($el['def']),

		'txt_1' => $el['txt_1'] //текст для галочки
	) + _elementStruct($el);
}
function _element1_struct_title($el) {
	$el['title'] = '✓';
	return $el;
}
function _element1_print($el, $prm) {
	return _check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'title' => $el['txt_1'],
		'disabled' => $prm['blk_setup'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element1_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(empty($u[$col]))
		return '';

	return '<div class="icon icon-ok curD"></div>';
}
function _element1_history($el, $v) {
	return _daNet($v);
}

/* [2] Кнопка */
function _element2_struct($el) {
	return array(
		'parent_id' => _num($el['parent_id']),

		'txt_1' => $el['txt_1'],        //текст кнопки
		'num_1' => _num($el['num_1']),  //цвет
		'num_2' => _num($el['num_2']),  //маленькая кнопка
		'num_3' => _num($el['num_3']),  //передаёт данные записи
		'num_4' => _num($el['num_4'])   //dialog_id, который назначен на эту кнопку
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
	if(!empty($el['block']))
		if($el['block']['obj_name'] == 'dialog') {
			$ass['dss'] = $el['block']['obj_id'];
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
		$val .= _dialogOpenVal($dialog_id, $prm, $el['num_3']);

	return $val;
}

/* [3] Меню страниц */
function _element3_struct($el) {
	return array(
		'num_1' => _num($el['num_1']),// раздел (страница-родитель). В меню будут дочерние страницы
		'num_2' => _num($el['num_2']) /* внешний вид:
											1 - Основной вид - горизонтальное меню
											2 - С подчёркиванием (гориз.)
											3 - Синие маленькие кнопки (гориз.)
											4 - Боковое вертикальное меню
											5 - Боковое с иконками
									  */
	) + _elementStruct($el);
}
function _element3_print($el, $prm) {
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if(!_pageAccess($id))
			continue;
		//раздел
		if($el['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return 'Разделов нет.';

	$menu = _spisokImage($menu);
	$menu = _menuCount($menu);

	$razdel = '';
	foreach($menu as $page_id => $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';

		//фактическая страница, на которую будет переход
		$pid = $page_id;

		//если страница является ссылкой на другую страницу, при этом она недоступна, поиск первой вложенной доступной
		if($r['common_id'])
			foreach(_page('child', $r['id']) as $p) {
				if(_pageAccess($p['id'])) {
					$pid = $p['id'];
					break;
				}
				if($r['common_id'] == $p['id'])
					continue;
			}

		$href = $prm['blk_setup'] ? '' : ' href="'.URL.'&p='.$pid.'"';
		$curd = _dn(!$prm['blk_setup'], 'curD');

		if($el['num_2'] == 5)
			$r['name'] = _imageHtml($r['image_ids'], $r['image_width'], 0, false, false);

		$razdel .= '<a class="link'.$sel.$curd.'"'.$href.'>'.$r['name'].'</a>';
	}

	return '<div class="_menu'.$el['num_2'].'">'.$razdel.'</div>';
}
function _menuCount($menu) {//получение элемента-циферки, размещённого на выводимых страницах
	$sql = "SELECT
				`el`.`id`,
				`el`.`num_1`,
				`el`.`txt_1`,
				`bl`.`obj_id` `page_id`
			FROM `_element` `el`,
				 `_block` `bl`
			WHERE `el`.`block_id`=`bl`.`id`
			  AND `el`.`app_id`=".APP_ID."
			  AND `dialog_id`=87
			  AND `bl`.`obj_name`='page'";
	if(!$arr = query_arr($sql))
		return $menu;

	foreach($arr as $r) {
		$page = _page($r['page_id']);

		//страница, к которой будет добавлена циферка
		$pid = 0;
		if(isset($menu[$r['page_id']]))
			$pid = $r['page_id'];
		if($page['parent_id'] && isset($menu[$page['parent_id']]))
			$pid = $page['parent_id'];
		if(!$pid)
			continue;

		$DLG = _dialogQuery($r['num_1']);
		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG).
					_40cond(array(), $r['txt_1']);
		if(!$count = query_value($sql))
			continue;

		$menu[$pid]['name'] .= '<b class="ml5">+'.$count.'</b>';
	}

	return $menu;
}

/* [4] Заголовок */
function _element4_struct($el) {
	return array(
		'txt_1' => $el['txt_1'] //текст заголовка
	) + _elementStruct($el);
}
function _element4_print($el) {
	return '<div class="hd2">'.$el['txt_1'].'</div>';
}

/* [5] textarea (многострочное текстовое поле) */
function _element5_struct($el) {
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'] //текст для placeholder
	) + _elementStruct($el);
}
function _element5_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return
	'<textarea id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.'>'.
		_elemPrintV($el, $prm).
	'</textarea>';
}
function _element5_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	$txt = _spisokColSearchBg($el['elp'], $txt);
	return _br($txt);
}

/* [6] Select: выбор страницы */
function _element6_struct($el) {
	/*
		содержание: PAGE_LIST
	*/
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'] //текст, когда страница не выбрана
	) + _elementStruct($el);
}
function _element6_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element6_print($el, $prm) {
	return _select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}

/* [7] Фильтр: быстрый поиск */
function _element7_struct($el) {
	return array(
		'width' => _num($el['width']),

		'txt_1' => $el['txt_1'],      //текст поиска
		'num_1' => _num($el['num_1']),//id элемента, содержащего список, по которому происходит поиск
		'txt_2' => $el['txt_2']       //по каким полям производить поиск (id элементов через запятую диалога списка)
	) + _elementStruct($el);
}
function _element7_js($el) {
	return array(
		'num_1' => $el['num_1']
	) + _elementJs($el);
}
function _element7_print($el, $prm) {
	return _search(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'v' => _spisokFilter('vv', $el),
		'disabled' => $prm['blk_setup']
	));
}

/* [8] input:text (однострочное текстовое поле) */
function _element8_struct($el) {
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],      //текст для placeholder
		'txt_2' => $el['txt_2'],      //текст по умолчанию
		'num_1' => _num($el['num_1']),/* формат:
											32 - произвольный текст
											33 - цифры и числа
											34 - артикул
									  */
		'num_2' => _num($el['num_2']),//количество знаков после запятой (для 33)
		'num_3' => _num($el['num_3']),//разрешать отрицательные значения (для 33)
		'num_4' => _num($el['num_4']),//разрешать вносить 0 (для 33)

		'txt_3' => $el['txt_3']       //шаблон артикула (для 34)
	) + _elementStruct($el);
}
function _element8_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';


	$v = _elemPrintV($el, $prm, $el['txt_2']);

	switch($el['num_1']) {
		default:
		//произвольный текст
		case 32: break;
		//цифры и числа
		case 33:
			$v = round($v, $el['num_2']);
			$v = $v || $el['num_4'] ? $v : '';
			break;
		//артикул
		case 34:
			if($v)
				break;
			if(!$col = _elemCol($el))
				break;
			if(!$BL = $el['block'])
				break;
			if($BL['obj_name'] != 'dialog')
				break;
			if(!$DLG = _dialogQuery($BL['obj_id']))
				break;
			$sql = "SELECT MAX(`t1`.`".$col."`)+1
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND LENGTH(`t1`.`".$col."`)=".strlen($el['txt_3']);
			$v = query_value($sql);
			if(($diff = strlen($el['txt_3']) - strlen($v)) > 0)
				for($n = 0; $n < $diff; $n++)
					$v = '0'.$v;
			break;
	}

	return '<input type="text" id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.' value="'.$v.'" />';
}
function _element8_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	$txt = _spisokColSearchBg($el['elp'], $txt);

	return _br($txt);
}

/* [9] Поле-пароль */
function _element9_struct($el) {
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],     //текст для placeholder
		'num_1' => _num($el['num_1'])//минимальное количество знаков
	) + _elementStruct($el);
}
function _element9_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return '<input type="password" id="'._elemAttrId($el, $prm).'"'._elemStyleWidth($el).$placeholder.$disabled.' />';
}

/* [10] Произвольный текст */
function _element10_struct($el) {
	return array(
		'txt_1' => $el['txt_1']     //текст
	) + _elementStruct($el);
}
function _element10_struct_title($el) {
	$el['title'] = $el['txt_1'];
	return $el;
}
function _element10_print($el) {
	return _br($el['txt_1']);
}

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

		'txt_2'     => $el['txt_2'],    //id элемента, выбранного из диалога, который вносит данные списка
								        //возможна иерархия элементов через запятую: 256,1312,560
		'txt_7'     => $el['txt_7'],    //текст слева (для истории действий)
		'txt_8'     => $el['txt_8']     //текст справа (для истории действий)
	) + _elementStruct($el);

	if($last_id = _idsLast($el['txt_2']))
		if(isset($ELM[$last_id])) {
			$el11 = $ELM[$last_id];

			//разрешать настройку стилей (правило 11)
			if(_elemRule($el11['dialog_id'], 11)) {
				$send['stl'] = 1; //для JS
				$send['color'] = $el['color'];
				$send['font']  = $el['font'];
				$send['size']  = $el['size'] ? _num($el['size']) : 13;
			}

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
		if(!isset($ELM[$id]))
			return $el;

		$ell = $ELM[$id];

		//для изображения путь не пишется
		if($ell['dialog_id'] == 60) {
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
					'num_7' => $el['num_7'],//[60] ограничение высоты
					'num_8' => $el['num_8'] //[60] закруглённые углы
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

	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');

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

/* [12] Функция PHP (SA) */
function _element12_struct($el) {
	/*
		После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.
	*/
	return array(
		'req'       => _num($el['req']),
		'req_msg'   => $el['req_msg'],

		'txt_1' => $el['txt_1'],     //имя функции (начинается с PHP12)
		'txt_2' => $el['txt_2'],     //начальное значение
		'num_1' => _num($el['num_1'])//условие 1
	) + _elementStruct($el);
}
function _element12_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element12_print($el, $prm) {
	if(!$el['txt_1'])
		return _emptyMin('Отсутствует имя функции.');
	if(!function_exists($el['txt_1']))
		return _emptyMinRed('Фукнции <b>'.$el['txt_1'].'</b> не существует.');
	if($prm['blk_setup'])
		return _emptyMin('Функция '.$el['txt_1']);

	$prm['el12'] = $el;

	return
	(!empty($el['col']) ?
		'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm, $el['txt_2']).'" />'
	: '').
		$el['txt_1']($prm);
}
function _element12_vvv($el, $prm) {
	$func = $el['txt_1'].'_vvv';

	if(!function_exists($func))
		return array();

	$prm['el12'] = $el;

	return $func($prm);
}

/* [13] Выбор элемента из диалога или страницы */
function _element13_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1'],      //текст для placeholder
		'num_1'   => _num($el['num_1']),//ID диалога (список всех диалогов)
		'txt_2'   => $el['txt_2'],      //разрешать выбирать только некоторые типы элементов (иначе любые)
		'num_2'   => _num($el['num_2']),//ids диалогов разрешённых элементов
		'num_5'   => _num($el['num_5']),//выбор значений во вложенных списках
		'num_6'   => _num($el['num_6']) //выбор нескольких значений
	) + _elementStruct($el);
}
function _element13_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_5'   => _num($el['num_5']),
		'num_6'   => _num($el['num_6']),
		'txt_2'   => $el['txt_2']
	) + _elementJs($el);
}
function _element13_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	//в самом себе выбор элемента невозможен
	if($block_id = $prm['srce']['block_id'])//должен быть блок 2214
		if($BL = _blockOne($block_id))
			if($BL['obj_name'] == 'dialog' && $BL['obj_id'] == 13)
				$disabled = ' disabled';

	$v = _elemPrintV($el, $prm, !$el['num_5'] && !$el['num_6'] ? 0 : '');

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_selem dib prel bg-fff over1" id="'._elemAttrId($el, $prm).'_selem"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-star pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp curP w100p color-pay"'.$placeholder.$disabled.' value="'._elemIdsTitle($v).'" />'.
	'</div>';
}

/* [14] Список-шаблон */
function _element14_struct($el) {
	/*
		настройка шаблона через функцию PHP12_spisok14_setup
	*/
	return array(
		'num_1' => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2' => _num($el['num_2']),//длина (количество строк, выводимых за один раз)

		'txt_1' => $el['txt_1'],      //сообщение пустого запроса
		'txt_2' => $el['txt_2'],      //условия отображения, настраиваемые через [40]
		'num_3' => _num($el['num_3']),/* порядок:
											0 - автоматически
											2318 - по дате добавления
											2319 - сотрировка (на основании поля sort)
									  */
		'num_4' => _num($el['num_4']) //горизонтальное расположение списка
	) + _elementStruct($el);
}
function _element14_struct_title($el, $DLG) {
	if(!$dlg_id = $el['num_1'])
		return $el;
	if(empty($DLG[$dlg_id]))
		return $el;
	$el['title'] = $DLG[$dlg_id]['name'];
	return $el;
}
function _element14_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element14_print($el, $prm) {
	if(!$dialog_id = $el['num_1'])
		return _emptyRed('Не указан список для вывода данных.');
	if(!$DLG = _dialogQuery($dialog_id))
		return _emptyRed('Списка <b>'.$dialog_id.'</b> не существует.');
	if($prm['blk_setup'])
		return _emptyMin('Список-шаблон <b>'.$DLG['name'].'</b>');

	return _spisok14($el);
}

/* [15] Количество строк списка */
function _element15_struct($el) {
	return array(
		'num_1' => _num($el['num_1']),//id элемента, содержащего список, количество строк которого нужно выводить
		'txt_1' => $el['txt_1'],      //показана "1"
		'txt_2' => $el['txt_2'],      //запись   "1"
		'txt_3' => $el['txt_3'],      //показано "2"
		'txt_4' => $el['txt_4'],      //записи   "2"
		'txt_5' => $el['txt_5'],      //показано "5"
		'txt_6' => $el['txt_6'],      //записей  "5"
		'txt_7' => $el['txt_7']       //сообщение об отсутствии записей
	) + _elementStruct($el);
}
function _element15_print($el, $prm) {
	return _spisokElemCount($el, $prm);
}

/* [16] Radio: произвольные значения */
function _element16_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_1'   => _num($el['num_1']),//горизонтальное положение
		'num_2'   => _num($el['num_2']),/* значения:
											3876 - произвольные значения (настраиваются через PHP12_radio_setup)
											3877 - значения существующего элемента
										*/
		'num_3'   => _num($el['num_3']) //элемент, если выбрано num_2:3877
	) + _elementStruct($el);
}
function _element16_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1'],
		'def' => _num($cl['def']),
		'use' => 0
	);
}
function _element16_print($el, $prm) {
	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'light' => 1,
		'block' => !$el['num_1'],
		'interval' => 5,
		'value' => _elemPrintV($el, $prm, $el['def']),
		'title0' => $el['txt_1'],
		'spisok' => _element('vvv', $el),
		'disabled' => $prm['blk_setup']
	));
}
function _element16_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';
	if(empty($el['vvv']))
		return '';

	foreach($el['vvv'] as $vv)
		if($vv['id'] == $id)
			return $vv['title'];

	return '';
}
function _element16_vvv($el) {
	//значения из существующего (другого) элемента
	if($el['num_2'] == 3877) {
		if($elem_id = $el['num_3']) {
			$sql = "SELECT
			            `id`,
			            `txt_1` `title`
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			return query_arr($sql);
		}
		return array();
	}

	if(!empty($el['vvv']))
		return $el['vvv'];

	return array();

}
function _element16_history($el, $v) {
	foreach($el['vvv'] as $vv)
		if($vv['id'] == $v)
			return $vv['title'];

	return '';
}

/* [17] Select: произвольные значения */
function _element17_struct($el) {
	/*
		значения: PHP12_select_setup
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1']      //текст нулевого значения
	) + _elementStruct($el);
}
function _element17_struct_vvv($el, $cl) {
	$send = array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1']
	);

	if($cl['txt_2'])
		$send['content'] = $cl['txt_1'].'<div class="fs12 grey ml10 mt3">'.$cl['txt_2'].'</div>';

	return $send;
}
function _element17_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element17_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element17_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';
	if(empty($el['vvv']))
		return '';

	foreach($el['vvv'] as $vv)
		if($vv['id'] == $id)
			return $vv['title'];

	return '';
}

/* [18] Dropdown */
function _element18_struct($el) {
	/*
		значения через PHP12_radio_setup
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'def'     => _num($el['def']),

		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_1'   => _num($el['num_1']),//скрывать нулевое значение в меню выбора
		'num_2'   => _num($el['num_2']) //не изменять имя нулевого значения после выбора
	) + _elementStruct($el);
}
function _element18_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'title' => $cl['txt_1'],
		'def' => _num($cl['def'])
	);
}
function _element18_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'txt_1'   => $el['txt_1']
	) + _elementJs($el);
}
function _element18_print($el, $prm) {
	return
	_dropdown(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'value' => _elemPrintV($el, $prm, $el['def'])
	));
}
function _element18_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num($u[$col]))
		return '';
	if(empty($el['vvv']))
		return '';

	foreach($el['vvv'] as $vv)
		if($vv['id'] == $id)
			return $vv['title'];

	return '';
}

/* [21] Информационный блок */
function _element21_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],  //содержание
	) + _elementStruct($el);
}
function _element21_print($el) {
	return '<div class="_info">'._br($el['txt_1']).'</div>';
}

/* [23] Список-таблица */
function _element23_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2'   => _num($el['num_2']),//длина (количество строк, выводимых за один раз)
		'txt_1'   => $el['txt_1'],      //сообщение пустого запроса
		'txt_2'   => $el['txt_2'],      //сусловия отображения, настраиваемые через [40]
		'num_3'   => _num($el['num_3']),//узкие строки таблицы
		'num_4'   => _num($el['num_4']),//подсвечивать строку при наведении мыши
		'num_5'   => _num($el['num_5']),//показывать имена колонок
		'num_6'   => _num($el['num_6']),//обратный порядок
		'num_7'   => _num($el['num_7']),//уровни сортировки: 1,2,3 (при num_8=6161)
		'num_8'   => _num($el['num_8']),/* порядок вывода данных [18]
											6159 - по дате внесения
											6160 - по значению из диалога
											6161 - ручная сортировка (если выбрано, длина списка становится 1000)
										*/

		'num_9'   => _num($el['num_9']),//включение отображения сообщения пустого запроса
		'num_10'  => _num($el['num_10'])//выбранное значение для порядка (при num_8=6160)
	) + _elementStruct($el);
}
function _element23_struct_title($el, $DLG) {
	if(!$dlg_id = $el['num_1'])
		return $el;
	if(empty($DLG[$dlg_id]))
		return $el;
	$el['title'] = $DLG[$dlg_id]['name'];
	return $el;
}
function _element23_struct_vvv($el, $cl) {
	return array(
		'id'        => _num($cl['id']),
		'title'     => $cl['title'],
		'parent_id' => _num($cl['parent_id']),
		'dialog_id' => _num($cl['dialog_id']),
		'width'     => _num($cl['width']),
		'font'      => $cl['font'],
		'color'     => $cl['color'],
		'txt_7'     => $cl['txt_7'],//название колонки
		'txt_8'     => $cl['txt_8'],//pos: позиция

		'num_1'     => _num($cl['num_1']),
		'num_2'     => _num($cl['num_2']),
		'num_3'     => _num($cl['num_3']),
		'num_4'     => _num($cl['num_4']),
		'txt_1'     => $cl['txt_1'],//для [10]
		'txt_2'     => $cl['txt_2'],//для [11]
	);
}
function _element23_js($el) {
	return array(
		'num_7'   => _num($el['num_7']),
		'num_8'   => _num($el['num_8'])
	) + _elementJs($el);
}
function _element23_print($el, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('Список-таблица <b>'._dialogParam($el['num_1'], 'name').'</b>');

	return _spisok23($el, $prm);
}

/* [24] Select: выбор списка приложения */
function _element24_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'width'   => _num($el['width']),

		'txt_1'   => $el['txt_1'],     //текст, когда список не выбран
		'num_1'   => _num($el['num_1'])/* содержание селекта:
											0   - все списки приложения. Функция _dialogSpisokOn()
											960 - размещённые на текущем объекте
												  Списки размещаются диалогами 14(шаблон), 23(таблица), История действий
												  Идентификаторами результата являются id элементов (а не диалогов)
												  Функция _dialogSpisokOnPage()
											961 - привязанные к данному диалогу
												  Идентификаторами результата являются id элементов (а не диалогов)
												  Функция _dialogSpisokOnConnect()
									   */
	) + _elementStruct($el);
}
function _element24_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element24_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => _elemPrintV($el, $prm, 0)
   ));
}
function _element24_vvv($el, $prm) {
	$dialog_id = $prm['srce']['dialog_id'];
	$block_id = $prm['srce']['block_id'];
	switch($el['num_1']) {
		//диалоги, которые могут быть списками: spisok_on=1 и размещены на текущей странице
		case 960: return _dialogSpisokOnPage($block_id);
		//диалоги, которые привязаны к выбранному диалогу
		case 961: return _dialogSpisokOnConnect($block_id);
	}

	//все списки приложения
	return _dialogSpisokOn($dialog_id, $block_id, $el['id']);
}

/* [25] Кружок с цветом статуса */
function _element25_struct($el) {
	return array(
		'txt_1' => $el['txt_1'],     //путь к заливке
		'num_1' => _num($el['num_1'])//диаметр
	) + _elementStruct($el);
}
function _element25_struct_title($el) {
	$el['title'] = 'O';
	return $el;
}
function _element25_print($el, $prm) {
	$bg = $prm['blk_setup'] ? '#eee' : _elemUids($el['txt_1'], $prm['unit_get']);

	$css = 'width:'.$el['num_1'].'px;'.
		   'height:'.$el['num_1'].'px;'.
		   'border:#EAEBEC solid 1px;'.
		   'vertical-align:top;';

	if(!empty($bg))
		$css .= 'background-color:'.$bg.';';

	return '<div class="dib br1000" style="'.$css.'"></div>';
}

/* [26] Select: выбор документа (SA) */
function _element26_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']  //нулевое значение
	) + _elementStruct($el);
}
function _element26_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element26_print($el, $prm) {
	return _select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}
function _element26_vvv() {
	$sql = "SELECT `id`,`name`
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id` DESC";
	return query_ass($sql);
}

/* [27] Cумма значений записи */
function _element27_struct($el) {
	/*
		настройка значений через PHP12_balans_setup
	*/
	return array(
		'num_3'   => _num($el['num_3'])//включение счётчика
	) + _elementStruct($el);
}
function _element27_print($el) {
	return $el['name'];
}
function _element27_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element27_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'minus' => _num($cl['num_8']), //вычитание=1, сложение=0
		'title' => $cl['title']
	);
}

/* [28] Загрузка файла */
function _element28_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']  //нулевое значение
	) + _elementStruct($el);
}
function _element28_print($el, $prm) {
	$v = _elemPrintV($el, $prm, 0);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_attach">'.
		'<div id="'._elemAttrId($el, $prm).'_atup" class="atup'._dn(!$v).'"'._elemStyleWidth($el).'>'.
			'<form method="post" action="'.AJAX.'" enctype="multipart/form-data" target="at-frame">'.
				'<input type="hidden" name="op" value="attach_upload" />'.
				'<input type="file" name="f1" class="at-file"'._elemStyleWidth($el).' />'.// accept="' + acceptMime() + '"
			'</form>'.
			'<button class="vk small grey w100p">'.$el['txt_1'].'</button>'.
			'<iframe name="at-frame"></iframe>'.
		'</div>'.
		'<table class="atv'._dn($v).'">'.
			'<tr><td class="top">'._attachLink($v).
				'<th class="top wsnw">'.
//					'<div class="icon icon-set mtm2 ml5 pl'._tooltip('Параметры файла', -56).'</div>'.
					'<div class="icon icon-del-red ml5 mtm2 pl'._tooltip('Отменить', -30).'</div>'.
		'</table>'.
	'</div>';
}

/* [29] Select: выбор записи из другого списка */
function _element29_struct($el) {
	/*
		Для связки одного списка с другим
		Список нельзя связывать самого с собой
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'width'   => _num($el['width']),

		'num_1'   => _num($el['num_1']),//id диалога, через который вносятся данные выбираемого списка [24]
		'txt_1'   => $el['txt_1'],      //текст, когда запись не выбрана
		'txt_3'   => $el['txt_3'],      //первый id элемента, составляющие содержание Select. Выбор через [13]
		'txt_4'   => $el['txt_4'],      //второй id элемента, составляющие содержание Select. Выбор через [13]
		'txt_5'   => $el['txt_5'],      //фильтр для отображения особых значений (зависит от num_1) [40]
		'num_2'   => _num($el['num_2']),//возможность добавления новых значений (через диалог)
		'num_3'   => _num($el['num_3']),//поиск значений вручную
		'num_4'   => _num($el['num_4']),//блокировать выбор
		'num_5'   => _num($el['num_5']),//учитывать уровни
		'num_6'   => _num($el['num_6'], 1),//значение по умолчанию
		'num_7'   => _num($el['num_7']) //автоматическое внесение записи, если отсутствует подобное текстовое значение
	) + _elementStruct($el);
}
function _element29_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_3'   => _num($el['num_3']),
		'num_4'   => _num($el['num_4']),
		'txt_1'   => $el['txt_1']
	) + _elementJs($el);
}
function _element29_print($el, $prm) {
	$v = _elemPrintV($el, $prm, $el['num_6']);
	$v = _elem29PageSel($el['num_1'], $v);
	$v = _elem29DialogSel($prm, $v);

	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => $v
	));
}
function _element29_vvv($el, $prm) {
	if($prm['unit_edit'])
		if(_elemColDlgId($el['id'], true))
			$prm['unit_edit'] = array();

	$sel_id = _elemPrintV($el, $prm, $el['num_6']);
	$sel_id = _elem29PageSel($el['num_1'], $sel_id);
	$sel_id = _elem29DialogSel($prm, $sel_id);
	return _29cnn($el['id'], '', $sel_id);
}
function _element29_history($el, $u) {
	if(empty($u))
		return '';

	foreach(_ids($el['txt_3'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return '';

		if($ell['dialog_id'] == 44) {
			$prm = _blockParam();
			$prm['unit_get'] = $u;
			return _element44_print($ell, $prm);
		}

		if(!$col = _elemCol($id))
			return '';
		if(empty($u[$col]))
			return '';

		$u = $u[$col];
	}

	if(is_array($u))
		return '';

	return $u;
}
function _elem29PageSel($dlg_cur, $sel_id) {//подмена id записи, если не совпадает со списком текущей страницы
	//id записи берётся с текущей страницы
	if($sel_id != -1)
		return $sel_id;
	if(!$sel_id = _num(@$_GET['id']))
		return 0;

	$page_id = _page('cur');
	$page = _page($page_id);
	if(!$dlg_id = $page['dialog_id_unit_get'])
		return $sel_id;

	//если страница принимает значения другого списка, нужно поменять id записи
	if($dlg_id == $dlg_cur)
		return $sel_id;
	if(!$DLG = _dialogQuery($dlg_id))
		return 0;
	if(!$u = _spisokUnitQuery($DLG, $sel_id))
		return 0;

	foreach($DLG['cmp'] as $cmp)
		if(_elemIsConnect($cmp))
			if($dlg_cur == $cmp['num_1'])
				if(!empty($u[$cmp['col']]))
					return $u[$cmp['col']]['id'];

	return 0;
}
function _elem29DialogSel($prm, $sel_id) {//подстановка id записи, которая приходит на диалоговое окно
	//id записи берётся с текущей страницы
	if($sel_id != -2)
		return $sel_id;
	//должен передаваться id записи
	if(!$get_id = $prm['unit_get_id'])
		return 0;
	if(!$block_id = $prm['srce']['block_id'])
		return 0;
	if(!$blk = _blockOne($block_id))
		return 0;
	//поиск id диалога: пока только получение из данных списка
	if($blk['obj_name'] != 'spisok')
		return 0;
	if(!$el = _elemOne($blk['obj_id']))
		return 0;
	if(!$DLG = _dialogQuery($el['num_1']))
		return 0;
	if(!$u = _spisokUnitQuery($DLG, $get_id))
		return 0;

	return $get_id;
}
function _elem29ValAuto($el, $txt) {//автоматическое внесение текста, введённого в выпадающем списке [29]
	if(!$txt = _txt($txt))
		return 0;
	//подключенный список, в который будет производиться внесение записи
	if(!$DLG = _dialogQuery($el['num_1']))
		return 0;
	//вносить можно пока только в "_spisok"
	if($DLG['table_name_1'] != '_spisok')
		return 0;
	//вносить можно пока только в родительский диалог
	if($DLG['dialog_id_parent'])
		return 0;
	if(!$last = _idsLast($el['txt_3']))
		return 0;
	if(!$ell = _elemOne($last))
		return 0;
	if(!$col = $ell['col'])
		return 0;

	//получение id записи, если такой текст уже был внесён ранее
	$sql = "SELECT `id`
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			  AND `".$col."`='".addslashes($txt)."'
			LIMIT 1";
	if($id = query_value($sql))
		return $id;

	$sql = "SELECT IFNULL(MAX(`num`),0)+1
			FROM `_spisok`
			WHERE `dialog_id`=".$DLG['id'];
	$num = query_value($sql);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,1)
			FROM `_spisok`
			WHERE `dialog_id`=".$DLG['id'];
	$sort = query_value($sql);

	$sql = "INSERT INTO `_spisok` (
				`app_id`,
				`dialog_id`,
				`num`,
				`".$col."`,
				`sort`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$DLG['id'].",
				".$num.",
				'".addslashes($txt)."',
				".$sort.",
				".USER_ID."
			)";
	return query_id($sql);
}

/* [30] Иконка удаления записи */
function _element30_struct($el) {
	return array(
		'num_1'   => _num($el['num_1'])//иконка красного цвета
	) + _elementStruct($el);
}
function _element30_struct_title($el) {
	$el['title'] = 'DEL';
	return $el;
}
function _element30_print($el, $prm) {
	if($prm['blk_setup'])
		return _iconDel(array(
			'red' => $el['num_1'],
			'class'=>'curD'
		));
	if(!$u = $prm['unit_get'])
		return _iconDel(array(
			'red' => $el['num_1'],
			'class'=>'curD'
		));

	if(!$dlg_id = _num(@$u['dialog_id_use']))
		return _msgRed('-dlg-id-yok-');
	if(!$dlg = _dialogQuery($dlg_id))
		return _msgRed('-dlg-yok-');
	//иконка не выводится, если удаление запрещено
	if(!$dlg['del_on'])
		return '';
	//иконка не выводится, если наступили другие сутки
	if($dlg['del_cond']['num_2']) {
		$day = explode(' ', $u['dtime_add']);
		if(TODAY != $day[0])
			return '';
	}

	return
	_iconDel(array(
		'red' => $el['num_1'],
		'class' => 'dialog-open pl',
		'val' => 'dialog_id:'.$dlg_id.',del_id:'.$u['id']
	));
}

/* [31] Выбор нескольких значений галочками */
function _element31_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога - список, из которого будут выбираться галочки
		'num_2'   => _num($el['num_2']) //id элемента - содержание
	) + _elementStruct($el);
}
function _element31_print($el, $prm) {
	$v = _elemPrintV($el, $prm);

	//получение данных списка
	$DLG = _dialogQuery($el['num_1']);
	$sql = "/* ".__FUNCTION__.":".__LINE__." Выбор галочками из ".$DLG['name']." */
			SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`";
	$spisok = query_arr($sql);

	$chk = '';
	$n = 0;
	$sel = _idsAss($v);
	foreach($spisok as $r) {
		$title = '<div class="fs10 red">содержание не настроено</div>';

		if($elem_id = $el['num_2']) {
			if($ell = _elemOne($elem_id)) {
				switch($ell['dialog_id']) {
					//сборный текст
					case 44:
						$prm44 = _blockParam();
						$prm44['unit_get'] = $r;
						$title = _element44_print($ell, $prm44);
						break;
					default:
						if($col = $ell['col'])
							if(isset($r[$col]))
								$title = $r[$col];
				}
			}
		} elseif($col = _elemCol($DLG['spisok_elem_id']))
				$title = $r[$col];

		$chk .=
			'<div class="'._dn(!$n++, 'mt5').'">'.
				_check(array(
					'attr_id' => 'chk31_'.$r['id'],
					'light' => 1,
					'title' => $title,
					'value' => _num(@$sel[$r['id']])
				)).
			'</div>';
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	$chk;
}
function _element31_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';

	return _val31($el, $txt);
}
function _element31_history($el, $v) {
	return _val31($el, $v);
}
function _element31_action231($el, $u) {
	if(!$col = @$el['col'])
		return true;
	if(!_idsAss(@$u[$col]))
		return true;

	return false;
}
function _val31($el, $txt) {//Выбор нескольких значений галочками [31] - вывод значения
	if(!$sel = _idsAss($txt))
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._queryWhere($DLG)."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '';


	$send = array();

	foreach($spisok as $r)
		if(!empty($sel[$r['id']]))
			$send[] = $r['txt_1'];

	return implode(', ', $send);
}

/* [32] Значение списка: порядковый номер */
function _element32_struct($el) {
	return _elementStruct($el);
}
function _element32_struct_title($el) {
	$el['title'] = 'NUM';
	return $el;
}
function _element32_print($el, $prm) {
	if(!$u = @$prm['unit_get'])
		return $el['title'];

	$num = empty($u['num']) ? $u['id'] : $u['num'];
	$num = _spisokColSearchBg($el, $num);
	return $num;
}

/* [33] Значение записи: дата */
function _element33_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),/* формат:
											29: 5 августа 2017
											30: 5 авг 2017
											31: 05/08/2017
										*/
		'num_2'   => _num($el['num_2']),//не показывать текущий год
		'num_3'   => _num($el['num_3']),/* имена у ближайших дней:
												вчера
												сегодня
												завтра
										*/
		'num_4'   => _num($el['num_4']) //показывать время в формате 12:45
	) + _elementStruct($el);
}
function _element33_struct_title($el) {
	$el['title'] = 'Дата';
	return $el;
}
function _element33_print($el, $prm) {
	if($prm['blk_setup'])
		return 'дата';
	if(!$u = $prm['unit_get'])
		return 'дата';

	return _elem33Data($el, $u);
}
function _elem33Data($el, $u) {//Значение записи: дата [33]
	if(empty($u['dtime_add']))
		return '';
	if(!preg_match(REGEXP_DATE, $u['dtime_add']))
		return 'некорректный формат даты';

	$ex = explode(' ', $u['dtime_add']);
	$d = explode('-', $ex[0]);

	//время
	$hh = '';
	if($el['num_4'] && !empty($ex[1])) {
		$h = explode(':', $ex[1]);
		$hh .= ' '.$h[0].':'.$h[1];
	}

	if($el['num_1'] == 31)
		return $d[2].'/'.$d[1].'/'.$d[0].$hh;

	$hh = $hh ? ' в'.$hh : '';

	if($el['num_3']) {
		$dCount = floor((strtotime($ex[0]) - TODAY_UNIXTIME) / 3600 / 24);
		switch($dCount) {
			case -1: return 'вчера'.$hh;
			case 0: return 'сегодня'.$hh;
			case 1: return 'завтра'.$hh;
		}
	}

	return
		_num($d[2]).                                                     //день
		' '.($el['num_1'] == 29 ? _monthFull($d[1]) : _monthCut($d[1])). //месяц
		($el['num_2'] && $d[0] == YEAR_CUR ? '' : ' '.$d[0]).            //год
		$hh;                                                             //время
}

/* [34] Иконка редактирования записи */
function _element34_struct($el) {
	return _elementStruct($el);
}
function _element34_struct_title($el) {
	$el['title'] = 'EDIT';
	return $el;
}
function _element34_print($el, $prm) {
	if($prm['blk_setup'])
		return _iconEdit(array('class'=>'curD'));
	if(!$u = $prm['unit_get'])
		return _iconEdit(array('class'=>'curD'));
	if(!$dlg_id = _num(@$u['dialog_id_use']))
		return _msgRed('-dlg-id-yok-');
	if(!$dlg = _dialogQuery($dlg_id))
		return _msgRed('-dlg-yok-');

	//иконка не выводится, если редактирование запрещено
	if(!$dlg['edit_on'])
		return '';

	return
	_iconEdit(array(
		'class' => 'dialog-open pl',
		'val' => 'dialog_id:'.$dlg_id.',edit_id:'.$u['id']
	));
}

/* [35] Count: количество */
function _element35_struct($el) {
	return array(
		'def'   => _num($el['def']),

		'num_1' => _num($el['num_1']),/* варианты значений:
												3681 - диапазон значений
												3682 - конкретные значения
										*/
		'num_2' => _num($el['num_2']),//разрешать минимум
		'num_3' => _num($el['num_3']),//минимум
		'num_4' => _num($el['num_4']),//минимум может быть отрицательным
		'num_5' => _num($el['num_5']),//разрешать максимум
		'num_6' => _num($el['num_6']),//максимум
		'num_7' => _num($el['num_7']),//шаг
		'num_8' => _num($el['num_8']),//разрешать переключение значений по кругу
		'txt_1' => $el['txt_1']       //конкретные значения, настраиваются через PHP12_count_value
	) + _elementStruct($el);
}
function _element35_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_3'   => _num($el['num_3']),
		'num_4'   => _num($el['num_4']),
		'num_5'   => _num($el['num_5']),
		'num_6'   => _num($el['num_6']),
		'num_7'   => _num($el['num_7']),
		'num_8'   => _num($el['num_8'])
	) + _elementJs($el);
}
function _element35_print($el, $prm) {
	return _count(array(
				'attr_id' => _elemAttrId($el, $prm),
				'width' => $el['width'],
				'value' => _elemPrintV($el, $prm, $el['def'])
		   ));
}
function _element35_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element35_vvv($el) {
	if($el['num_1'] != 3682)
		return array();

	return json_decode($el['txt_1']);
}

/* [36] Иконка */
function _element36_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id иконки
		'num_2'   => _num($el['num_2']),//изменять яркость при наведении мышкой
		'num_3'   => _num($el['num_3']) //курсор рука при наведении, иначе стрелочка
	) + _elementStruct($el);
}
function _element36_struct_title($el) {
	$el['title'] = 'ICON';
	return $el;
}
function _element36_print($el) {
	$type = PHP12_icon18_type($el['num_1']);
	$pl = _dn(!$el['num_2'], 'pl');
	$cur = $el['num_3'] ? ' curP' : ' curD';

	return '<div class="icon icon-'.$type.$pl.$cur.'"></div>';
}

/* [37] Select: выбор колонки таблицы (SA) */
function _element37_struct($el) {
	return _elementStruct($el);
}
function _element37_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'width' => $el['width'],
		'value' => _elemPrintV($el, $prm)
	));
}
function _element37_vvv($el, $prm) {
	if(!$block = _blockOne($prm['srce']['block_id']))
		return array();
	//список колонок может быть получен при условии, если элемент размещается в диалоге
	if($block['obj_name'] != 'dialog')
		return array();
	if(!$dlg = _dialogQuery($block['obj_id']))
		return array();

	//выбранная колонка, если редактирование записи
	$uCol = '';
	if($u = $prm['unit_edit'])
		$uCol = $u['col'];

	$field = _elemVvv37fieldDop($uCol);

	//если диалог родительский, получение колонок родителя
	if($parent_id = $dlg['dialog_id_parent']) {
		$field = _elemVvv37parent($parent_id, $field);
		$PAR = _dialogQuery($parent_id);
		//если таблицы одинаковые, отправка только родительских колонок
		if(!$dlg['table_1'] || $dlg['table_1'] == $PAR['table_1'])
			return $field;
	}

	$field = _elemVvv37field($dlg, $uCol, $field);

	return $field;
}
function _elemVvv37field($dlg, $uCol, $send=array()) {//колонки по каждой таблице
	if(!$dlg['table_1'])
		return $send;

	//получение используемых колонок
	$colUse = array();
	foreach($dlg['cmp'] as $r) {
		if(empty($r['col']))
			continue;
		$colUse[$r['col']] = !empty($r['name']) ? '<i class="color-555 ml10">('.$r['name'].')</i>' : '';
	}

	//колонки, которые не должны выбираться
	$fieldNo = array(
		'id' => 1,
		'id_old' => 1,
		'num' => 1,
		'app_id' => 1,
		'cnn_id' => 1,
		'parent_id' => 1,
		'user_id' => 1,
		'page_id' => 1,
		'block_id' => 1,
		'element_id' => 1,
		'dialog_id' => 1,
		'width' => 1,
		'color' => 1,
		'font' => 1,
		'size' => 1,
		'mar' => 1,
		'sort' => 1,
		'deleted' => 1,
		'user_id_add' => 1,
		'user_id_del' => 1,
		'dtime_add' => 1,
		'dtime_del' => 1,
		'dtime_create' => 1,
		'app_id_last' => 1
	);

	foreach($dlg['field1'] as $col => $k) {
		if(isset($fieldNo[$col]))
			continue;

		$color = '';
		$busy = 0;//занята ли колонка
		$name = '';
		if(isset($colUse[$col])) {
			$color = $uCol == $col ? 'b color-pay' : 'b red';
			$busy = 1;
			$name = $colUse[$col];
		}
		$u = array(
			'id' => $col,
			'title' => $col,
			'busy' => $busy,
			'content' =>
				'<div class="'.$color.'">'.
					'<span class="pale">'.$dlg['name'].'.</span>'.
					$col.
					$name.
				'</div>'

		);
		$send[] = $u;
	}

	return $send;
}
function _elemVvv37fieldDop($uCol) {//дополнительная колонка - из другого списка
	$send=array();

	if(!$col_id = _num($uCol))
		return $send;
	if(!$el = _elemOne($col_id))
		return $send;
	if(!$col = $el['col'])
		return $send;
	if(!$DLG = _dialogQuery($el['block']['obj_id']))
		return $send;

	$send[] = array(
		'id' => $col_id,
		'title' => $DLG['name'].': '.$el['name'],
		'content' => $DLG['name'].': '.$el['name'].' <b class="pale">'.$col.'</b>'
	);

	return $send;
}
function _elemVvv37parent($dlg_id, $send) {//колонки родительского диалога
	if(!$dlg = _dialogQuery($dlg_id))
		return $send;

	foreach($dlg['cmp'] as $id => $cmp) {
		if(empty($cmp['col']))
			continue;

		$send[] = array(
			'id' => $id,
			'title' => $dlg['name'].': '.$cmp['name'],
			'content' => $dlg['name'].': '.$cmp['name'].' <b class="pale">'.$cmp['col'].'</b>'
		);
	}

	return $send;
}

/* [38] Select: выбор диалогового окна (SA) */
function _element38_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']//нулевое значение
	) + _elementStruct($el);
}
function _element38_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element38_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => _num(@$el['width']),
		'value' => _elemPrintV($el, $prm, 0)
	));
}
function _element38_vvv() {
	return _dialogSelArray();
}

/* [39] Месяц и год */
function _element39_struct($el) {
	return _elementStruct($el);
}
function _element39_print($el, $prm) {
	$def = strftime('%Y-%m');
	if(!$v = _elemPrintV($el, $prm, $def))
		$v = $def;

	$ex = explode('-', $v);

	$attr_id = _elemAttrId($el, $prm);

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
	_count(array(
		'attr_id' => $attr_id.'_mon',
		'width' => 100,
		'class' => 'mr5',
		'value' => _num($ex[1])
	)).
	_count(array(
		'attr_id' => $attr_id.'_year',
		'width' => 70,
		'value' => $ex[0]
	));
}

/* [40] Фильтрование списка */
function _element40_struct($el) {
	/*
		Работает совместно с PHP12_spfl [41] - настройка значений
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'num_1'   => _num($el['num_1']),//id элемента - путь к списку [13]
		'txt_1'   => $el['txt_1']       //текст нулевого значения
	) + _elementStruct($el);
}
function _element40_print($el, $prm) {
	$attr_id = _elemAttrId($el, $prm);
	$placeholder = ' placeholder="'.$el['txt_1'].'"';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$title = '';
	if($v = _elemPrintV($el, $prm)) {
		$vv = htmlspecialchars_decode($v);
		$arr = json_decode($vv, true);
		$c = count($arr);
		$title = $c.' услови'._end($c, 'е', 'я', 'й');
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
	'<div class="_spfl dib w125 prel" id="'.$attr_id.'_spfl">'.
		'<div class="icon icon-filter pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp color-del b pl25 curP w100p over3"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}
function _element40_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element40_vvv($el, $prm) {
	if($el['num_1'])
		return 0;
	if(!$block_id = $prm['srce']['block_id'])
		return 0;
	if(!$BL = _blockOne($block_id))
		return 0;
	if(!$EL = $BL['elem'])
		return 0;
	if(!_elemIsConnect($EL))
		return 0;

	return _num($EL['num_1']);
}
function _elem40json($cond) {//перевод данных фильтра из JSON в array
	if(empty($cond))
		return array();

	$arr = htmlspecialchars_decode($cond);
	return json_decode($arr, true);
}

/* [44] Сборный текст */
function _element44_struct($el) {
	/*
		настройка значений через PHP12_44_setup
	*/
	return _elementStruct($el);
}
function _element44_struct_vvv($el, $cl) {
	return array(
		'id'        => _num($cl['id']),
		'title'     => $cl['title'],
		'dialog_id' => _num($cl['dialog_id']),
		'txt_1'     => $cl['txt_1'],      //для [10]
		'txt_2'     => $cl['txt_2'],      //ids из [11]
		'num_1'     => _num($cl['num_1']),
		'num_2'     => _num($cl['num_2']),
		'num_3'     => _num($cl['num_3']),
		'num_4'     => _num($cl['num_4']),
		'num_8'     => _num($cl['num_8']) //пробел справа
	);
}
function _element44_print($el, $prm) {
	if(empty($el['vvv']))
		return $el['name'];

	$send = '';
	foreach($el['vvv'] as $ell) {
		$send .= _element('print', $ell, $prm);
		if($ell['num_8'])
			$send .= ' ';
	}

	return $send;
}

/* [45] Выбор нескольких значений привязанного списка */
function _element45_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'num_1'   => _num($el['num_1']),//список (из которого будут выбираться значения)
		'txt_1'   => $el['txt_1'],      //имя кнопки
		'num_2'   => _num($el['num_2']),//вспомогательный диалог
		'txt_2'   => $el['txt_2'],      //путь к изображениям
		'num_3'   => _num($el['num_3']) //указывать количество выбранных значений
	) + _elementStruct($el);
}
function _element45_js($el) {
	return array(
		'num_2' => _num($el['num_2'])
	) + _elementJs($el);
}
function _element45_print($el, $prm) {
	$v = _elemPrintV($el, $prm);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="uns-html">'._element45Uns($el, $v).'</div>'.
	_button(array(
		'attr_id' => _elemAttrId($el, $prm).$el['afics'],
		'name' => $el['txt_1'],
		'color' => 'grey',
		'width' => $el['width'],
		'small' => 1,
		'class' => _dn(!$prm['blk_setup'], 'curD')
	));
}
function _element45_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return _element45Uns($el, @$u[$col], true);
}
function _element45Uns($el, $v, $is_show=false) {//выбранные значения при редактировании
	if(empty($v))
		return '';

	$UNS = array();
	foreach(explode(',', $v) as $ex) {
		$exx = explode(':', $ex);
		$UNS[] = array(
			'id' => $exx[0],
			'c' => $exx[1]
		);
	}

	if(empty($UNS))
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE `t1`.`id` IN ("._idsGet($UNS).")
			  AND "._queryWhere($DLG, true);
	if(!$arr = query_arr($sql))
		return '';

	$col = _elemCol($DLG['spisok_elem_id']);
	$send = '';
	$n = 1;
	foreach($UNS as $r) {
		if(!isset($arr[$r['id']]))
			continue;

		$name = '<span class="fs10 red">Отсутствует колонка для отображения названия</span>';

		$u = $arr[$r['id']];
		if($col)
			if(isset($u[$col]))
				$name = $u[$col];

		//вариант вывода значений для редактирования
		if(!$is_show) {
			$send .=
			'<tr><td class="w35">'.
					'<div class="fs14 grey r">'.$n++.'</div>'.
				'<td class="fs14">'.
					'<div class="fs14">'.$name.'</div>'.
				'<td class="w70 bg-ffd'._dn($el['num_3']).'">'.
					'<input type="text" class="uinp w100p r b" val="'.$r['id'].'" value="'.$r['c'].'">'.
				'<td class="pad0 w35 center">'.
					'<div class="icon icon-del'._tooltip('Отменить выбор', -94, 'r').'</div>';
			continue;
		}

		//вариант вывода значений для просмотра
		$send .=
		'<tr><td class="w35 grey r">'.$n++.
			'<td>'.$name;
		if($el['num_3'])
			$send .='<td class="w50 r b">'.$r['c'];
	}

	return
	'<table class="_stab w100p small '.($is_show ? '' : 'mb10').'">'.$send.'</table>';
}

/* [49] Выбор блоков из диалога или страницы */
function _element49_struct($el) {
	/*
		работает в паре с [19] - окно выбора блоков
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']//текст для placeholder
	) + _elementStruct($el);
}
function _element49_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$v = _elemPrintV($el, $prm);
	$ids = _ids($v);
	$count = _ids($ids, 'count');
	$title = $count ? $count.' блок'._end($count, '', 'а', 'ов') : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_sebl dib prel bg-fff over1" id="'._elemAttrId($el, $prm).'_sebl"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-cube pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp curP w100p color-ref"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}

/* [51] Календарь */
function _element51_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//разрешать выбор прошедших дней
		'num_2'   => _num($el['num_2']) //показывать время
	) + _elementStruct($el);
}
function _element51_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2'])
	) + _elementJs($el);
}
function _element51_print($el, $prm) {
	return
	_calendar(array(
		'attr_id' => _elemAttrId($el, $prm),
		'time' => $el['num_2'],
		'value' => _elemPrintV($el, $prm)
	));
}
function _element51_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$txt = @$u[$col])
		return '';
	if($txt == '0000-00-00')
		return '-';
	if($el['num_2'] && $txt == '0000-00-00 00:00:00')
		return '';

	$v = FullData($txt);
	if($el['num_2'])
		$v .= ' в '._num(substr($txt, 11, 2)).
				':'.substr($txt, 14, 2);

	return $v;
}
function _element51_history($el, $v) {
	return FullData($v);
}

/* [52] Заметки */
function _element52_struct($el) {
	return _elementStruct($el);
}
function _element52_print($el, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('Заметки');
	return _note($el);
}

/* [54] Количество значений привязанного списка */
function _element54_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, указывающего на привязанный список
		'num_3'   => _num($el['num_3']),//включение счётчика
		'txt_1'   => $el['txt_1']       //фильтр
	) + _elementStruct($el);
}
function _element54_print($el) {
	return $el['name'];
}
function _element54_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}

/* [55] Сумма значений привязанного списка */
function _element55_struct($el) {
	/*
		для хранения сумм используется колонка sum_1, sum_2, ...
	*/
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, указывающего на привязанный список
		'txt_1'   => $el['txt_1'],      //фильтр
		'num_2'   => _num($el['num_2']),//id элемента значения (колонки) привязанного списка
		'num_3'   => _num($el['num_3']) //включение счётчика
	) + _elementStruct($el);
}
function _element55_print($el) {
	return $el['name'];
}
function _element55_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}

/* [57] Меню переключения блоков */
function _element57_struct($el) {
	/*
		для настройки блоков используется функция PHP12_menu_block_setup
	*/
	return array(
		'def'     => _num($el['def']),

		'num_1'   => _num($el['num_1']),/* внешний вид меню:
											1158 - Маленькие синие кнопки
											1159 - С нижним подчёркиванием
										*/
		'txt_1'   => $el['txt_1']       //ids дочерних элементов
	) + _elementStruct($el);
}
function _element57_struct_vvv($el, $cl) {//пункты меню
	return array(
		'id' => _num($cl['id']),
		'title'   => $cl['txt_1'], //название пункта меню
		'blk'   => $cl['txt_2'],   //блоки
		'def'   => _num($cl['def'])//по умолчанию
	);
}
function _element57_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element57_print($el, $prm) {
	$v = _elemPrintV($el, $prm, $el['def']);

	$type = array(
		1158 => 2,
		1159 => 3
	);

	$razdel = '';
	if(!empty($el['vvv']))
		foreach($el['vvv'] as $r) {
			$sel = _dn($v != $r['id'], 'sel');
			$curd = _dn(!$prm['blk_setup'], 'curD');
			$razdel .= '<a class="link'.$sel.$curd.'">'.$r['title'].'</a>';
		}

	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
		   '<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';
}

/* [58] Условия удаления записи (пока не используется) */
function _element58_struct($el) {
	/*
		применяется при настройке диалога в удалении
	*/
	return array(
		'num_1'   => _num($el['num_1']),//id диалога
		'num_2'   => _num($el['num_2']),//запрещать удаление, если наступили новые сутки [1]
	) + _elementStruct($el);
}

/* [59] Связка списка при помощи кнопки */
function _element59_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст кнопки
		'txt_5'   => $el['txt_5'],      //НЕ ЗАНИМАТЬ (используется под фильтр в [29])
		'num_1'   => _num($el['num_1']),//id диалога, через который вносятся данные выбираемого списка
		'num_4'   => _num($el['num_4']) //id диалога, которое открывается при нажатии на кнопку
	) + _elementStruct($el);
}
function _element59_js($el) {
	return array(
		'num_1' => _num($el['num_1']),
		'num_4' => _num($el['num_4'])
	) + _elementJs($el);
}
function _element59_print($el, $prm) {
	$v = _elemPrintV($el, $prm, 0);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	_button(array(
		'attr_id' => _elemAttrId($el, $prm).$el['afics'],
		'name' => $el['txt_1'],
		'color' => 'grey',
		'width' => $el['width'],
		'small' => 1,
		'class' => _dn(!$v)._dn(!$prm['blk_setup'], 'curD')
	)).
	'<div class="prel'._dn($v).'">'.
		'<div style="position:absolute;top:2px;right:3px;z-index:100" class="icon icon-del-red pl'._tooltip('Отменить выбор', -52).'</div>'.
		'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
	'</div>';
}
function _element59_history($el, $u) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';
	if(!$elem_id = $DLG['spisok_elem_id'])
		return '';
	if(!$ell = _elemOne($elem_id))
		return '';
	if(empty($ell['col']))
		return '';

	$col = $ell['col'];

	if(empty($u[$col]))
		return '';
	if(is_array($u[$col]))
		return '';

	return $u[$col];
}
function _spisok59unit($elem_id, $unit_id) {//выбранное значение при связке списков через кнопку [59]
	if(!$unit_id)
		return '';
	if(!$el = _elemOne($elem_id))
		return '';
	if(!$dialog_id = _num($el['num_1']))
		return '';
	if(!$dlg = _dialogQuery($dialog_id))
		return '';
	if(!$prm['unit_get'] = _spisokUnitQuery($dlg, $unit_id))
		return '';

	return _blockHtml('spisok', $elem_id, $prm);
}

/* [60] Загрузка изображений */
function _element60_struct($el) {
	return array(
		'width' => _num($el['width']),//нужно для 'struct_title'

		'num_1' => _num($el['num_1']),//максимальное количество изображений, которое разрешено загрузить
		'num_7' => _num($el['num_7']),//ограничение высоты (настройка стилей)
		'num_8' => _num($el['num_8']) //закруглённые углы (настройка стилей)
	) + _elementStruct($el);
}
function _element60_struct_title($el) {
	$el['title'] = _imageNo($el['width'], $el['num_8']);
	return $el;
}
function _element60_print($el, $prm) {
	return _image($el, $prm);
}
function _element60_print11($el, $u) {
	$EL = $el['elp'];

	if(!$col = _elemCol($el))
		return _imageNo($EL['width'], $EL['num_8']);
	if(empty($u[$col]['id']))
		return _imageNo($EL['width'], $EL['num_8']);

	return _imageHtml($u[$col], $EL['width'], $EL['num_7'], $EL['num_8']);
}

/* [62] Фильтр: галочка */
function _element62_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст для галочки
		'txt_2'   => $el['txt_2'],      //фильтр настраивается через [40]
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'num_2'   => _num($el['num_2']),/* условие применяется:
											1439 - галочка установлена
											1440 - галочка НЕ установлена
										*/
		'num_3'   => _num($el['num_3']) //начальное значение для галочки
	) + _elementStruct($el);
}
function _element62_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element62_print($el, $prm) {
	return
	_check(array(
		'attr_id' => _elemAttrId($el, $prm),
		'title' => $el['txt_1'],
		'disabled' => $prm['blk_setup'],
		'value' => _spisokFilter('vv', $el, $el['num_3'])
	));
}

/* [66] Выбор цвета текста */
function _element66_struct($el) {
	return _elementStruct($el);
}
function _element66_print($el, $prm) {
	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'._elemPrintV($el, $prm).'" />'.
		   '<div class="_color" style="background-color:#000"></div>';
}

/* [68] Список истории действий */
function _element68_struct($el) {
	return array(
		'num_8'   => _num($el['num_8']) //показывать историю записи, которую принимает текущая страница или диалог
	) + _elementStruct($el);
}
function _element68_print($el, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('История действий');

	return _historySpisok($el, $prm);
}

/* [69] Значение записи: имя пользователя */
function _element69_struct($el) {
	return _elementStruct($el);
}
function _element69_print($el, $prm) {
	if(!$u = $prm['unit_get'])
		return $el['title'];
	if(empty($u['user_id_add']))
		return 'no user';

	return _user($u['user_id_add'], 'name');
}

/* [70] Выбор цвета фона */
function _element70_struct($el) {
	return _elementStruct($el);
}
function _element70_print($el, $prm) {
	$v = _elemPrintV($el, $prm, '#fff');

	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
		   '<div class="_color-bg" style="background-color:'.$v.'"></div>';
}
function _element70_vvv($el, $prm) {
	$color = array(
		'#fff',
		'#ffffe4',
		'#e4ffe4',
		'#dff',
		'#ffe8ff',

		'#f9f9f9',
		'#ffb',
		'#cfc',
		'#aff',
		'#fcf',

		'#f3f3f3',
		'#fec',
		'#F2F2B6',
		'#D7EBFF',
		'#ffe4e4',

		'#ededed',
		'#FFDA8F',
		'#E3E3AA',
		'#B2D9FF',
		'#fcc'
	);

	$sel = '#fff';//выбранное значение
	if($u = $prm['unit_edit']) {
		$col = $el['col'];
		$sel = $u[$col];
	}

	$spisok = '';
	for($n = 0; $n < count($color); $n++) {
		$cls = $sel == $color[$n] ? ' class="sel"' : '';
		$spisok .= '<div'.$cls.' style="background-color:'.$color[$n].'" val="'.$color[$n].'">'.
						'&#10004;'.
				   '</div>';
	}
	return '<div class="_color-bg-choose">'.$spisok.'</div>';
}

/* [71] Значение записи: иконка сортировки */
function _element71_struct($el) {
	return _elementStruct($el);
}
function _element71_struct_title($el) {
	$el['title'] = 'SORT';
	return $el;
}
function _element71_print($el, $prm) {
	return '<div class="icon icon-move '.($prm['unit_get'] ? 'pl' : 'curD').'"></div>';
}

/* [72] Фильтр: год и месяц */
function _element72_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента - список, на который происходит воздействие
		'num_2'   => _num($el['num_2']) //id элемента - путь к сумме для подсчёта по каждому месяцу
	) + _elementStruct($el);
}
function _element72_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element72_print($el, $prm) {
	$v = _spisokFilter('vv', $el, strftime('%Y-%m'));

	$ex = explode('-', $v);
	$year = $ex[0];
	$mon  = $ex[1];


	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	_yearleaf(array(
		'attr_id' => _elemAttrId($el, $prm).'yl',
		'value' => $ex[0]
	)).
	'<div class="mt5">'.
		_radio(array(
			'attr_id' => _elemAttrId($el, $prm).'rd',
			'width' => 0,
			'block' => 1,
			'light' => 1,
			'interval' => 5,
			'value' => $mon,
			'spisok' => _elem72Sum($el, $year),
			'disabled' => $prm['blk_setup']
		)).
	'</div>';
}
function _elem72Sum($el, $year) {//получение сумм для фильтра [72]
	$spisok = _monthDef();

	if(!$el = _elemOne($el['num_2']))
		return $spisok;
	if(!$col = $el['col'])
		return $spisok;
	if(!$bl = $el['block'])
		return $spisok;
	if($bl['obj_name'] != 'dialog')
		return $spisok;
	if(!$DLG = _dialogQuery($bl['obj_id']))
		return $spisok;

	$sql = "/* ".__FUNCTION__.":".__LINE__." Суммы для фильтра [72] */
			SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%m')) AS `mon`,
				SUM(`".$col."`) `sum`
			FROM   "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND `dtime_add` LIKE '".$year."-%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')";
	if(!$arr = query_array($sql))
		return $spisok;

	foreach($arr as $r) {
		$mon = _num($r['mon']);
		$txt = $spisok[$mon];
		$spisok[$mon] = $txt.
						'<span class="fr">'._sumSpace(round($r['sum'])).'</span>';
	}

	return $spisok;
}

/* [74] Фильтр: Radio */
function _element74_struct($el) {
	/*
		значения: PHP12_filter_radio_setup
	*/
	return array(
		'def'   => _num($el['def']),

		'num_1' => _num($el['num_1'])//id элемента-список, к которому применяется фильтр
	) + _elementStruct($el);
}
function _element74_struct_vvv($el, $cl) {
	$c = '';
	if($cl['txt_2']) {
		$vv = htmlspecialchars_decode($cl['txt_2']);
		$arr = json_decode($vv, true);
		$c = count($arr);
	}

	return array(
		'id'    => _num($cl['id']),
		'txt_1' => $cl['txt_1'],        //имя пунтка
		'def'   => _num($cl['def']),
		'c'     => $c,                  //количество условий в пункте
		'txt_2' => $cl['txt_2'],        //условия
		'num_1' => _num($cl['num_1'])   //отображать количество значений в пункте
	);
}
function _element74_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element74_print($el, $prm) {
	if(empty($el['vvv']))
		return _emptyMinRed('Значения фильтра не настроены');

	//получение количества значений по каждому пункту
	$EL = _elemOne($el['num_1']);
	$DLG = _dialogQuery($EL['num_1']);
	$spisok = array();
	foreach($el['vvv'] as $n => $r) {
		$spisok[$n] = array(
			'id' => $r['id'],
			'title' => $r['txt_1']
		);

		if(!$r['num_1'])
			continue;

		$sql = "SELECT COUNT(*)
				FROM  "._queryFrom($DLG)."
				WHERE "._queryWhere($DLG)."
					"._40cond($EL, $r['txt_2']);
		if($c = query_value($sql))
			$spisok[$n]['title'] .= '<span class="fr inhr">'.$c.'</span>';
	}

	return
	_radio(array(
		'attr_id' => _elemAttrId($el, $prm),
		'block' => 1,
		'width' => '100%',
		'interval' => 6,
		'light' => 1,
		'value' => _spisokFilter('vv', $el, $el['def']),
		'spisok' => $spisok,
		'disabled' => $prm['blk_setup']
	));
}

/* [77] Фильтр: календарь */
function _element77_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'num_2'   => _num($el['num_2']),/* значение по умолчанию:
											2819 - текущий день
											2820 - текущая неделя
											2821 - текущий месяц
										*/
		'num_3'   => _num($el['num_3']),/* фильтрация - по какой колонке производить фильтр
											6509 - по дате внесения
											6510 - по значению даты
                                        */
		'num_4'   => _num($el['num_4']),//значение даты (если выбрано 6510)
	) + _elementStruct($el);
}
function _element77_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element77_print($el) {
	return _filterCalendar($el);
}

/* [78] Фильтр: меню */
function _element78_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'txt_1'   => $el['txt_1'],      //id элемента (с учётом вложений), содержащего значения (названия), составляющие меню
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений), содержащего количество записей по каждому пункту
	) + _elementStruct($el);
}
function _element78_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element78_print($el) {
	if(!$ids = _ids($el['txt_1'], 1))
		return _emptyMin10('Фильтр-меню: отсутствуют ID значений.');

	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemOne($elem_id))
		return _emptyMin10('Фильтр-меню: значение отсутствует.');
	if(!$BL = $EL['block'])
		return _emptyMin10('Фильтр-меню: нет блока.');
	if($BL['obj_name'] != 'dialog')
		return _emptyMin10('Фильтр-меню: блок не из диалога.');
	if(!$dialog_id = $BL['obj_id'])
		return _emptyMin10('Фильтр-меню: нет ID диалога.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin10('Фильтр-меню: нет диалога.');

	$col = $EL['col'];//колонка текстового значения
	$colCount = '';//колонка значения количества
	if($ids = _ids($el['txt_2'], 1)) {
		$c = count($ids) - 1;
		$elem_id = $ids[$c];
		if($EL3 = _elemOne($elem_id))
			$colCount = $EL3['col'];
	}

	$cond = " `id`";
	if(isset($dialog['field1']['deleted']))
		$cond .= " AND !`deleted`";
	if(isset($dialog['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;
	$sql = "SELECT *
			FROM `"._table($dialog['table_1'])."`
			WHERE ".$cond."
			ORDER BY `sort`,`id`";
	if(!$arr = query_arr($sql))
		return _emptyMin10('Фильтр-меню: пустое меню.');

	$send = '';
	$v = _spisokFilter('vv', $el, 0);

	$spisok = array();
	foreach($arr as $r)
		$spisok[$r['parent_id']][] = $r;

	foreach($spisok[0] as $r) {
		$child = '';
		$child_sel = false;//список будет раскрыт, если в нём был выбранное значение
		if(!empty($spisok[$r['id']]))
			foreach($spisok[$r['id']] as $c) {
				$sel = $v == $c['id'] ? ' sel' : '';
				if($sel)
					$child_sel = true;
				$child .= '<div class="fm-unit'.$sel.'" val="'.$c['id'].'">'.
							$c[$col].
							($colCount ? '<span class="ml10 pale b">'.$c[$colCount].'</span>' : '').
						'</div>';
			}

		$sel = $v == $r['id'] ? ' sel' : '';
		$send .=
			'<table class="w100p">'.
				'<tr>'.
		  ($child ? '<td class="fm-plus">'.($child_sel ? '-' : '+') : '<td class="w25">').//—
					'<td><div class="fm-unit b fs14'.$sel.'" val="'.$r['id'].'">'.
							$r[$col].
							($colCount ? '<span class="ml10 pale b">'.$r[$colCount].'</span>' : '').
						'</div>'.
			'</table>'.
			($child ? '<div class="ml40'._dn($child_sel).'">'.$child.'</div>' : '');
	}

	return $send;
}

/* [80] Очистка фильтров */
function _element80_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //имя кнопки
		'num_1'   => _num($el['num_1'])//id элемента, размещающего список
	) + _elementStruct($el);
}
function _element80_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element80_print($el, $prm) {
	$diff = _spisokFilter('diff', $el['num_1']);
	return _button(array(
		'attr_id' => _elemAttrId($el, $prm),
		'name' => _br($el['txt_1']),
		'color' => 'red',
		'width' => $el['width'],
		'small' => 1,
		'class' => _dn($prm['blk_setup'] || $diff)._dn(!$prm['blk_setup'], 'curD')
	));
}

/* [83] Фильтр: Select - привязанный список */
function _element83_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента-списка, на который воздействует фильтр
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений) - привязанный список (через [13])
	) + _elementStruct($el);
}
function _element83_js($el) {
	return array(
		'txt_1' => $el['txt_1'],
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element83_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => $el['width'],
		'value' => _spisokFilter('vv', $el, 0)
	));
}
function _element83_vvv($el) {
	return _elem102CnnList($el['txt_2']);
}
function _elem102CnnList($ids, $return='select', $cond='') {//значения привязанного списка (пока для фильтра 102)
	if(!$last_id = _idsLast($ids))
		return array();
	if(!$el = _elemOne($last_id))
		return array();
	if(!$bl = $el['block'])
		return array();
	if($bl['obj_name'] != 'dialog')
		return array();
	if(!$dlg_id = _num($bl['obj_id']))
		return array();
	if(!$dlg = _dialogQuery($dlg_id))
		return array();
	if(!$col = @$el['col'])
		return array();

	//получение данных списка
	$sql = "SELECT "._queryCol($dlg)."
			FROM   "._queryFrom($dlg)."
			WHERE  "._queryWhere($dlg)."
				   ".$cond."
			ORDER BY `sort`,`id`
			LIMIT 200";
	if(!$spisok = query_arr($sql))
		return array();

	$select = array();
	$ass = array();
	foreach($spisok as $id => $r) {
		$select[] = array(
			'id' => $id,
			'title' => $r[$col]
		);
		$ass[$id] = $r[$col];
	}

	if($return == 'ass')
		return $ass;
	if($return == 'ids')
		return _idsGet($select);

	return $select;
}

/* [85] Select: выбор значения списка по умолчанию */
function _element85_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//ID элемента select, который содержит списки
		'txt_1'   => $el['txt_1'],      //текст нулевого значения
		'num_2'   => _num($el['num_2']),//разрешать выбор записи, данные которой принимает страница
		'num_3'   => _num($el['num_3']) //разрешать выбор записи, данные которой принимает диалог
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
		'width' => $el['width'],
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

	$send = _elem201init($el, $prm, $send);

	if(!$u = $prm['unit_edit'])
		return $send;

	//ID элемента, содержащее значение
	if(!$ell_id = _num($el['num_1']))
		return $send;
	if(!$ell = _elemOne($ell_id))
		return $send;
	//колонка, по которой будет получено ID диалога-списка
	if(!$col = $ell['col'])
		return $send;
	if(!$v = _num($u[$col]))
		return $send;

	$send = _elem85mass($ell_id, $v, $send);
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
			'title' => $sp
		);
	}

	return $send;
}

/* [86] Значение записи: количество дней */
function _element86_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//ID элемента, который указывает на дату
		'txt_1'   => $el['txt_1'],      //текст "Прошёл" 1
		'txt_2'   => $el['txt_2'],      //текст "Остался" 1
		'txt_3'   => $el['txt_3'],      //текст "День" 1
		'txt_4'   => $el['txt_4'],      //текст "Прошло" 2
		'txt_5'   => $el['txt_5'],      //текст "Осталось" 2
		'txt_6'   => $el['txt_6'],      //текст "Дня" 2
		'txt_7'   => $el['txt_7'],      //текст "Прошло" 5
		'txt_8'   => $el['txt_8'],      //текст "Осталось" 5
		'txt_9'   => $el['txt_9'],      //текст "Дней" 5
		'txt_10'  => $el['txt_10'],     //текст для "сегодня"

		'num_2'   => _num($el['num_2']),//показывать "вчера"
		'num_3'   => _num($el['num_3']) //показывать "завтра"
	) + _elementStruct($el);
}
function _element86_print($el, $prm) {
	if(!$u = $prm['unit_get'])
		return 'Кол-во дней';
	if(!$elem_id = $el['num_1'])
		return _msgRed('-no-elem-date');
	if(!$EL = _elemOne($elem_id))
		return _msgRed('-no-elem-'.$elem_id);
	if(!$col = $EL['col'])
		return _msgRed('-no-elem-col');
	if(!isset($u[$col]))
		return _msgRed('-no-unit-col');

	$date = substr($u[$col], 0, 10);

	if(!preg_match(REGEXP_DATE, $date))
		return _msgRed('-no-date-format');
	if($date == '0000-00-00')
		return '';

	$day = (strtotime($date) - TODAY_UNIXTIME) / 86400;

	$day_txt =
		($day > 0 ?
		_end($day, $el['txt_2'], $el['txt_5'], $el['txt_8'])
		:
		_end($day, $el['txt_1'], $el['txt_4'], $el['txt_7'])
		).
		' '.abs($day).' '.
		_end($day, $el['txt_3'], $el['txt_6'], $el['txt_9']);

	if($day == -1 && $el['num_2'])
		$day_txt = $el['txt_10'].' вчера';
	if(!$day)
		$day_txt = $el['txt_10'].' сегодня';
	if($day == 1 && $el['num_3'])
		$day_txt = $el['txt_10'].' завтра';

	return $day_txt;
}

/* [87] Циферка в меню страниц */
function _element87_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога: список
		'txt_1'   => $el['txt_1']       //условия [40]
	) + _elementStruct($el);
}
function _element87_print($el, $prm) {
	if(!$prm['blk_setup'])
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return _msgRed('Не получены данные диалога '.$el['num_1']);

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG).
				_40cond(array(), $el['txt_1']);
	$count = query_value($sql);

	return 'Кол-во "'.$DLG['name'].'" '.($count ? '+'.$count : '0');
}

/* [90] Изображение */
function _element90_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //ids изображений
		'num_1'   => _num($el['num_1']),//ширина
		'num_2'   => _num($el['num_2']),//разрешать высоту
		'num_3'   => _num($el['num_3']),//высота
		'num_4'   => _num($el['num_4']) //разрешать клик для увеличения
	) + _elementStruct($el);
}
function _element90_print($el) {
	//формирование ширины, если изображение отсутствует
	$w = $el['num_1'];
	if($el['num_2'])
		if($el['num_1'] > $el['num_3'])
			$w = $el['num_3'];

	if(!$image_id = _idsFirst($el['txt_1']))
		return _imageNo($w);

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	if(!$img = query_assoc($sql))
		return _imageNo($w);

	//если присутствует высота - подгонка картинки под размеры
	$w = $el['num_1'];
	$h = 0;
	if($el['num_2']) {
		$s = _imageResize($img['max_x'], $img['max_y'], $w, $el['num_3']);
		$w = $s['x'];
		$h = $s['y'];
	}

	return _imageHtml($img, $w, $h, false, $el['num_4']);
}

/* [96] Количество значений связанного списка с учётом категорий */
function _element96_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента: привязанный список
		'txt_1'   => $el['txt_1'],      //id элемента (с учётом вложений): путь к категориям
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений): путь к цветам
	) + _elementStruct($el);
}
function _element96_print($el, $prm) {
	if($prm['blk_setup'])
		return '<div class="el96-u bg-ffc mr3">8</div>'.
			   '<div class="el96-u bg-fcc">3</div>';

	if(!$u = $prm['unit_get'])
		return '';

	//ключ для конкретного элемента, по которому расположены данные в записи
	$key = 'el96_'.$el['id'];
	if(empty($u[$key]))
		return '';

	end($u[$key]);
	$end = key($u[$key]);

	$send = '';
	foreach($u[$key] as $id => $r) {
		$bg = $r['bg'] ? ' style="background-color:'.$r['bg'].'"' : '';
		$name = $r['name'] ? _tooltip($r['name'], -6, 'l') : '">';
		$mr = $id != $end ? ' mr3' : '';
		$send .= '<div'.$bg.' class="el96-u'.$mr.$name.$r['count'].'</div>';
	}

	return $send;
}

/* [102] Фильтр: Выбор нескольких групп значений */
function _element102_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента: список, на который воздействует фильтр [24]
		'txt_1'   => $el['txt_1'],      //нулевое значение
		'txt_2'   => $el['txt_2'],      //ids элементов: привязанный список (зависит от num_1) [13]
		'txt_3'   => $el['txt_3'],      //ids элементов: счётчик количеств  (зависит от num_1) [13]
		'txt_4'   => $el['txt_4'],      //ids элементов: путь к цветам (зависит от num_1) [13]
		'txt_5'   => $el['txt_5']       //значение по умолчанию: настраивается через [40]
	) + _elementStruct($el);
}
function _element102_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element102_print($el, $prm) {
	$v = _spisokFilter('v', $el['id']);
	if($v === false) {
		$cond = _40cond($el, $el['txt_5']);
		$v = _elem102CnnList($el['txt_2'], 'ids', $cond);
		_spisokFilter('insert', array(
			'spisok' => $el['num_1'],
			'filter' => $el['id'],
			'v' => $v
		));
	}

	$vAss = _idsAss($v);

	//ассоциативный массив с количествами
	$countAss = _elem102CnnList($el['txt_3'], 'ass');

	//ассоциативный массив с цветами
	$bgAss = _elem102CnnList($el['txt_4'], 'ass');

	$title = array();//ассоциативный массив с именами значений фильтра для JS
	$spisok = '';
	$sel = '';//выбранные значения
	if($arr = _elem102CnnList($el['txt_2'])) {
		$n = 0;
		$selOne = '';
		foreach($arr as $r) {
			$id = $r['id'];
			$bg = isset($bgAss[$id]) ? ' style="background-color:'.$bgAss[$id].'"' : '';
			$c = _hide0(@$countAss[$id]);
			$spisok .=
				'<tr class="over1" val="'.$r['id'].'">'.
					'<th class="w35 pad8 center"'.$bg.'>'.
						_check(array(
							'attr_id' => 'chk'.$id,
							'value' => isset($vAss[$id])
						)).
					'<td class="wsnw">'.$r['title'].
					'<td class="r fs12 grey b">'.$c;

			$title[$id] = $r['title'];

			if(isset($vAss[$id])) {
				$c = _num($c);
				$sel .= $c ? '<div'.$bg.' class="un'._tooltip($r['title'], -6, 'l').$c.'</div>' : '';
				$selOne = '<div class="un"'.$bg.'>'.$r['title'].'</div>';
				$n++;
			}
		}
		if($n == 1)
			$sel = $selOne;
	}


	return
	'<div class="_filter102"'._elemStyleWidth($el).' id="'._elemAttrId($el, $prm).'_filter102">'.
		'<div class="holder'._dn(!$sel).'">'.$el['txt_1'].'</div>'.
		'<table class="w100p">'.
			'<tr><td class="td-un">'.($sel ? $sel : '<div class="icon icon-empty"></div>').
				'<td class="w25 top r">'.
					'<div class="icon icon-del pl'._dn($sel, 'vh')._tooltip('Очистить фильтр', -53).'</div>'.
		'</table>'.
		'<div class="list">'.
			'<table>'.$spisok.'</table>'.
		'</div>'.
	'</div>'.
	'<script>'.
		'var EL'.$el['id'].'_F102_TITLE='._json($title).','.
			'EL'.$el['id'].'_F102_C='._json($countAss).','.
			'EL'.$el['id'].'_F102_BG='._json($bgAss).';'.
	'</script>';
}

/* [130] Пин-код */
function _element130_struct($el) {
	return _elementStruct($el);
}
function _element130_print($el, $prm) {
	$txt = 'Установить';
	$color = 'grey';
	$dlg_id = 131;
	if(_user(USER_ID, 'pin')) {
		$txt = 'Изменить';
		$color = '';
		$dlg_id = 132;
	}
	return
	_button(array(
		'name' => $txt.' пин-код',
		'color' => $color,
		'class' => $prm['blk_setup'] ? 'curD' : 'dialog-open',
		'val' => 'dialog_id:'.$dlg_id
	));
}

/* [300] Привязка пользователя к странице ВК */
function _element300_struct($el) {
	return _elementStruct($el);
}
function _element300_print($el, $prm) {
	$vkRes = '';
	if($user_id = _elemPrintV($el, $prm, 0)) {
		$res = _vkapi('users.get', array(
			'user_ids' => $user_id,
			'fields' => 'photo,'.
						'sex,'.
						'country,'.
						'city'
		));

		if(empty($res['response']))
			$vkRes = '<div class="red">Данные из VK не получены';
		else
			$vkRes = _elem300Sel($res['response'][0]);
	}

	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'"'.$disabled.' value="'.$user_id.'" />'.
	'<div id="'._elemAttrId($el, $prm).'_vk300" class="_vk300"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-vk curD'._dn(!$user_id).'"></div>'.
		'<input type="text" class="w100p'._dn(!$user_id).'"'.$disabled.' />'.
		'<div class="vk-res">'.$vkRes.'</div>'.
	'</div>';
}
function _elem300Place($res) {//страна и город пользователя ВК
	$place = array();
	if(!empty($res['country']))
		$place[] = $res['country']['title'];
	if(!empty($res['city']))
		$place[] = $res['city']['title'];

	return implode(', ', $place);
}
function _elem300Sel($res) {//выбранный пользователь ВК
	return
	'<table>'.
		'<tr><td class="pr5"><img src="'.$res['photo'].'" class="ava35">'.
			'<td><div class="icon icon-del-red pl fr ml20 mtm2'._tooltip('Отменить', -31).'</div>'.
				'<a href="//vk.com/id'.$res['id'].'" target="_blank">'.
					$res['first_name'].' '.$res['last_name'].
				'</a>'.
				'<div class="grey mt3">'._elem300Place($res).'</div>'.
	'</table>';
}
function _elem300VkIdTest($DLG, $v, $user_id) {//проверка, чтобы два одинаковый `vk_id` не попали в таблицу `_user`
	if(!$vk_id = _num($v))
		return false;

	//поиск таблицы `_user`
	$tab = $DLG['table_name_1'];

	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		$tab = $PAR['table_name_1'];
	}

	if($tab == '_user') {
		$sql = "SELECT COUNT(*)
				FROM `_user`
				WHERE `vk_id`=".$vk_id.
	($user_id ? " AND `id`!=".$user_id : '');
		return query_value($sql);
	}

	return false;
}

/* [400] График: столбики */
function _element400_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],     //заголовок
		'num_1'   => _num($el['num_1'])//список (id диалога) [24]
	) + _elementStruct($el);
}
function _element400_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element400_print($el, $prm) {
	return _elem400($el, $prm);
}
























function PHP12_elem_info($prm) {//информация об элементе [118]
	if(!$elem_id = $prm['unit_get_id'])
		return _emptyRed('Не получен id элемента.');

	$el = _elemOne($elem_id, true);

	$send = '<tr><td class="r w250">Элемент ID:<td class="b">'.$elem_id.
			'<tr><td class="r color-ref b">app_id:'.
				'<td>'.($el['app_id'] ? '<span class="b">'.$el['app_id'].'</span>' : '-');


	//Создан через диалог
	$dlgPaste = '-';
	if($dialog_id = $el['dialog_id']) {
		$dlgPaste = '['.$dialog_id.'] ';
		if(!$DLG = _dialogQuery($dialog_id))
			$dlgPaste .= '<span class="red">- диалога не существует</span>';
		else
			$dlgPaste .= ' '.$DLG['name'];
	}
	$send .='<tr><td class="r grey">Создан через диалог:<td>'.$dlgPaste;

	$send .='<tr><td class="r grey">Расположен в блоке:'.
				'<td>'.($el['block_id'] ? '<a class="dialog-open color-sal" val="dialog_id:117,get_id:'.$el['block_id'].'">'.$el['block_id'].'</a>' : '-').
			'<tr><td class="r grey">Элемент-родитель:<td>'.PHP12_elem_info_elemLink(@$el['parent_id']);

	//Дочерние элементы
	$td = '-';
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$elem_id."
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		$ids = array();
		foreach($arr as $id => $r)
			$ids[] = PHP12_elem_info_elemLink($id);
		$td = implode(', ', $ids);
	}
	$send .='<tr><td class="r grey top">Дочерние элементы:<td>'.$td;


	//Использование в элементе [11]
	$td = '-';
	$sql = "SELECT `id`,`txt_2`
			FROM `_element`
			WHERE `dialog_id`=11
			  AND `app_id` IN (0,".APP_ID.")
			  AND LENGTH(`txt_2`)";
	if($arr = query_arr($sql)) {
		$ids = array();
		foreach($arr as $id => $r) {
			$ass = _idsAss($r['txt_2']);
			if(isset($ass[$elem_id]))
				$ids[] = PHP12_elem_info_elemLink($id);
		}
		if(!empty($ids))
			$td = implode(', ', $ids);
	}
	$send .='<tr><td class="r grey top">Используется в элементе [11]:<td>'.$td;

	//Прикреплены функции
	$td = '-';
	$sql = "SELECT `id`
			FROM `_action`
			WHERE `element_id`=".$elem_id."
			ORDER BY `id`";
	if($ids = query_ids($sql))
		$td = $ids;
	$send .='<tr><td class="r grey">IDs прикрепленых функций:<td>'.$td;


	//Функции, которые воздействуют на этот элемент
	$td = '-';
	$sql = "SELECT *
			FROM `_action`
			WHERE `dialog_id` IN (202,203,206,212,213,216,223)
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		$ids = array();
		foreach($arr as $id => $r) {
			$ass = _idsAss($r['target_ids']);
			if(isset($ass[$elem_id]))
				$ids[] = $id;
		}
		if(!empty($ids))
			$td = implode(', ', $ids);
	}
	$send .='<tr><td class="r grey">Воздействующие функции:<td>'.$td;


	//Использование в содержании фильтра [40]
	$td = '-';
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=40
			  AND `app_id` IN (0,".APP_ID.")
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		$mass = array();
		foreach($arr as $r) {
			if(!$col = $r['col'])
				continue;
			if(!$BL = _blockOne($r['block_id']))
				continue;
			if($BL['obj_name'] != 'dialog')
				continue;
			if(!$DLG = _dialogQuery($BL['obj_id']))
				continue;

			$sql = "SELECT *
					FROM  "._queryFrom($DLG)."
					WHERE "._queryWhere($DLG)."
					  AND LENGTH(`".$col."`)".
						($DLG['table_name_1'] == '_element' ? " AND `dialog_id`=".$DLG['id'] : '');
			if(!$spisok = query_arr($sql))
				continue;

			foreach($spisok as $sp) {
				$filter = htmlspecialchars_decode($sp[$col]);
				if(!$filter = json_decode($filter, true))
					continue;

				foreach($filter as $f) {
					$ass = _idsAss($f['elem_id']);
					if(isset($ass[$elem_id]))
						$mass[] = '<a class="dialog-open" val="dialog_id:'.$DLG['id'].',edit_id:'.$sp['id'].'">'.
									'<span class="color-pay">`'.$DLG['table_name_1'].'`</span> '.
									'['.$DLG['id'].'] '.$DLG['name'].
									' - <b>id'.$sp['id'].'<b>'.
								  '</a>';
				}
			}
		}
		if(!empty($mass))
			$td = implode('<br>', $mass);
	}
	$send .='<tr><td class="r grey top">Используется в фильтре [40]:<td>'.$td;

	//Использование в фильтрах шаблона истории действий (а также в самой истории действий)
	$td = '-';
	$mass = array();
	$hist = array();
	$colDef = '-';
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (0,".APP_ID.")";
	foreach(query_arr($sql) as $r) {
		if(!$DLG = _dialogQuery($r['id']))
			continue;

		foreach(array('insert', 'edit', 'del') as $i)
			if($ids = $r[$i.'_history_elem']) {
				$mass[] = $ids;
				$ass = _idsAss($ids);
				if(isset($ass[$elem_id]))
					$hist[] = '['.$DLG['id'].'] '.$DLG['name'];
			}
		if($elem_id == $r['spisok_elem_id'])
			$colDef = '['.$DLG['id'].'] '.$DLG['name'];
	}

	if($mass) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id` IN (".implode(',', $mass).")
				  AND LENGTH(`txt_9`)
				ORDER BY `id`";
		if($arr = query_arr($sql)) {
			$mass = array();
			foreach($arr as $r) {
				$filter = htmlspecialchars_decode($r['txt_9']);
				if(!$filter = json_decode($filter, true))
					continue;

				foreach($filter as $f) {
					$ass = _idsAss($f['elem_id']);
					if(isset($ass[$elem_id]))
						$mass[] = PHP12_elem_info_elemLink($r['id']);
				}
			}
			if($mass)
				$td = implode(', ', $mass);
		}
	}
	$send .='<tr><td class="r grey top">В истории действий диалогов:<td>'.($hist ? implode('<br>', $hist) : '-');
	$send .='<tr><td class="r grey top">IDs элементов шаблонов истории действий, в фильтрах которых используется данный элемент:<td>'.$td;
	$send .='<tr><td class="r grey">Является колонкой по умолчанию в диалоге:<td>'.$colDef;


	//Является указателем на список в исходном диалоге в элементе [13]
	$td = '-';
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=13
			  AND `num_1`=".$elem_id."
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		foreach($arr as $r)
			$mass[] = PHP12_elem_info_elemLink($r['id']);
		$td = implode(', ', $mass);
	}
	$send .='<tr><td class="r grey">Является указателем на список в исходном диалоге в элементе [13]:<td>'.$td;

	$send .='<tr><td class="r grey">Используется в фильтре элемента [74]:<td>---';
	$send .='<tr><td class="r grey">Был выбран в элементе [13]:<td>---';
	$send .='<tr><td class="r grey">Является указателем на колонку родительского диалога:<td>---';

	return '<table class="bs10">'.$send.'</table>';
}
function PHP12_elem_info_elemLink($elem_id, $empty='-') {//формирование ссылки на элемент
	if(!$elem_id)
		return $empty;

	return '<a class="dialog-open" val="dialog_id:118,get_id:'.$elem_id.'">'.$elem_id.'</a>';
}

function _colorJS() {//массив цветов для текста в формате JS, доступных элементам
	return '{'.
		'"":["#000","Чёрный"],'.
		'"color-555":["#555","Тёмно-серый"],'.
		'"grey":["#888","Серый"],'.
		'"pale":["#aaa","Бледный"],'.
		'"color-ccc":["#ccc","Совсем бледный"],'.
		'"blue":["#2B587A","Тёмно-синий"],'.
		'"color-acc":["#07a","Синий"],'.
		'"color-sal":["#770","Салатовый"],'.
		'"color-pay":["#090","Зелёный"],'.
		'"color-aea":["#aea","Ярко-зелёный"],'.
		'"red":["#e22","Красный"],'.
		'"color-ref":["#800","Тёмно-красный"],'.
		'"color-del":["#a66","Тёмно-бордовый"],'.
		'"color-vin":["#c88","Бордовый"]'.
	'}';
}

function _dialogTest() {//проверка id диалога, создание нового нового, если это кнопка
	//если dialog_id получен - отправка его
	if($dialog_id = _num(@$_POST['dialog_id']))
		return $dialog_id;
	if(!$block_id = _num(@$_POST['block_id']))
		return 0;

	//получение элемента-кнопки для присвоения нового диалога
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id."
			  AND `dialog_id` IN (2,59)
			LIMIT 1";
	if(!$elem = query_assoc($sql))
		return false;

	//новый диалог кнопке уже был присвоен
	if($elem['num_4'] > 0)
		return $elem['num_4'];

	$sql = "SELECT IFNULL(MAX(`num`),0)+1
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$num = query_value($sql);

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`num`,
				`name`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$num.",
				'Диалог ".$num."',
				".USER_ID."
			)";
	$dialog_id = query_id($sql);

	//проверка, нужно ли по кнопке всегда создавать новый диалог
	if($elem['num_4'] != -1) {
		$sql = "UPDATE `_element`
				SET `num_4`=".$dialog_id."
				WHERE `id`=".$elem['id'];
		query($sql);
	}

	_BE('block_clear');
	_BE('elem_clear');
	_BE('dialog_clear');

	return $dialog_id;
}
function _dialogQuery($dialog_id) {//данные конкретного диалогового окна
	global $_DQ;

	if(isset($_DQ[$dialog_id]))
		return $_DQ[$dialog_id];

	if(!$dialog = _BE('dialog', $dialog_id))
		return array();

	$dialog['blk'] = _BE('block_arr', 'dialog', $dialog_id);
	$dialog['cmp'] = _BE('elem_arr', 'dialog', $dialog_id);

	$_DQ[$dialog_id] = $dialog;

	return $dialog;
}
function _dialogParam($dialog_id, $param) {//получение конкретного параметра диалога
	$dialog = _dialogQuery($dialog_id);
	if(!isset($dialog[$param]))
		return 'Неизвестный параметр диалога: '.$param;

	$send = $dialog[$param];

	if(!is_array($send) && preg_match(REGEXP_NUMERIC, $send))
		return _num($send);

	return $send;
}
function _dialogParent($dialog) {//получение диалога, отвечающего за внесение записи
	while($parent_id = $dialog['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);

		//диалог может быть родительским во всех приложениях
		//в таком случае диалогом списка становится его первый последователь
		if($PAR['parent_any'])
			break;

		$dialog = $PAR;
	}
	return $dialog;
}
function _dialogOpenVal($dialog_id, $prm, $unit_id_send) {//получение параметров открытия диалога для кнопки или блока
	if(!$dialog_id)
		return '';
	if(!$dlg = _dialogQuery($dialog_id))
		return '';
	if(!$prm['unit_get'])
		return '';

	$uid = $prm['unit_get']['id'];

	//если кнопка открывает дочерний диалог, проверяется, вносит ли он данные записи. Если да, то добавляется возможность редактирования
	//Будут переданы значения и для отображения, и для редактирования
	if($dlg['dialog_id_parent'])
		return ',get_id:'.$uid.',edit_id:'.$uid;
//				($dlg['spisok_on'] ? ',edit_id:'.$uid : '');

	//передаёт id записи. Берётся со страницы, либо с единицы списка
	if($unit_id_send)
		return ',get_id:'.$uid;

	return '';
}
function _dialogSpisokOn($dialog_id, $block_id, $elem_id) {//получение массива диалогов, которые могут быть списками: spisok_on=1
	$cond = "`spisok_on`";
	$cond .= " AND `app_id` IN (0,".APP_ID.")";

	//получение id диалога, который является списком, чтобы было нельзя его выбирать в самом себе (для связок)
	$dialog = _dialogQuery($dialog_id);
	if(_table($dialog['table_1']) == '_element') {
		//если редактирование - получение id блока из элемента
		if($elem_id) {
			$sql = "SELECT `block_id`
					FROM `_element`
					WHERE `id`=".$elem_id;
			$block_id = query_value($sql);
		}
		//если вставка элемента в блок
		$sql = "SELECT `obj_id`
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id`=".$block_id;
		if($dialog_id_skip = query_value($sql))
			$cond .= " AND `id`!=".$dialog_id_skip;
	}

	$sql = "SELECT *
			FROM `_dialog`
			WHERE ".$cond."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	$saArr = array();
	foreach($arr as $r) {
		if($r['sa'] || !$r['app_id']) {
			$saArr[] = $r;
			continue;
		}
		$send[] = array(
			'id' => _num($r['id']),
			'title' => $r['name']
		);
	}


	//списки, доступные только SA
	if(SA) {
		$send[] = array(
			'info' => 1,
			'title' => 'SA-списки:'
		);
		foreach($saArr as $r)
			$send[] = array(
				'id' => _num($r['id']),
				'title' => $r['name'],
				'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.$r['name'].'</div>'
			);
	}

	return $send;
}
function _dialogSpisokOnPage($block_id) {//получение массива диалогов, которые могут быть списками: spisok_on=1 (размещённые на странице)
/*
	получены будут списки, размещёные в текущем объекте
	$elem_id - размещённый на странице или в диалоге, по которому определяется объект
	Идентификаторами результата являются id элементов (а не диалогов)
*/

	if(!$block = _blockOne($block_id))
		return array();

	//списки размещаются при помощи диалогов 14 и 23
	//идентификаторами результата являются id элементов (а не диалогов)

	if(!$elm = _BE('elem_arr', $block['obj_name'], $block['obj_id']))
		return array();

	$send = array();
	foreach($elm as $elem_id => $r) {
		if($r['dialog_id'] != 14 && $r['dialog_id'] != 23 && $r['dialog_id'] != 68)
			continue;

		if($r['dialog_id'] == 68)
			$spisokName = 'История действий';
		else
			$spisokName = _dialogParam($r['num_1'], 'name');
		$send[$elem_id] = $spisokName.' (в '.$block['obj_name'].'-блоке '.$r['block_id'].')';
	}

	return $send;
}
function _dialogSpisokOnConnect($block_id) {//получение диалогов-списков, которые привязаны к текущему (исходному) диалогу
/*
	$block_id - исходный блок, по которому определяется объект
	Привязка происходит через элементы [29],[59], по нему будет производиться происк
	Идентификаторами результата являются id элементов (а не диалогов)
*/

	if(!$BL = _blockOne($block_id))
		return array();

	$dialog_id = 0;
	switch($BL['obj_name']) {
		case 'dialog': $dialog_id = $BL['obj_id']; break;
		case 'spisok':
			if(!$el = _elemOne($BL['obj_id']))
				break;
			if($el['dialog_id'] != 14)
				break;
			$dialog_id = $el['num_1'];
			break;
	}

	if(!$dialog_id)
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id` IN (29,59)
			  AND `num_1`=".$dialog_id."
			ORDER BY `id`";
	if(!$elem = query_arr($sql))
		return array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			ORDER BY `obj_id`";
	if(!$block = query_arr($sql))
		return array();

	//количество связок для каждого диалога (connect count)
	$sql = "SELECT
				`obj_id`,
				COUNT(`id`)-1
			FROM `_block`
			WHERE `obj_name`='dialog' 
			  AND `id` IN ("._idsGet($elem, 'block_id').")
			GROUP BY `obj_id`";
	$cc = query_ass($sql);

	$send = array();
	foreach($elem as $elem_id => $r) {
		$BL = $block[$r['block_id']];
		$obj_id = _num($BL['obj_id']);
		$dialog = _dialogQuery($obj_id);
		$send[_num($elem_id)] = $dialog['name'].($cc[$obj_id] ? ' (в блоке '.$r['block_id'].')' : '');
	}

	return $send;
}
function _dialogSel24($elem_id, $dlg_id) {//получение id диалога, который выбирается через элемент [24]
	if(!$el = _elemOne($elem_id))
		return 0;
	if(!$dlg_id)
		return 0;

	if($el['dialog_id'] == 24) {
		//список, размещённый на странице
		if($el['num_1'] == 960) {
			if(!$ell = _elemOne($dlg_id))
				return 0;
			return $ell['num_1'];
		}

		//привязанный диалог
		if($el['num_1'] == 961) {
			if(!$ell = _elemOne($dlg_id))
				return 0;
			return $ell['block']['obj_id'];
		}

		return $dlg_id;
	}

	if($el['dialog_id'] == 13) {
		if(!$ell = _elemOne($dlg_id))
			return 0;
		if(_elemIsConnect($ell))
			return $ell['num_1'];
		if($ell['dialog_id'] == 14 || $ell['dialog_id'] == 23)
			return $ell['num_1'];
	}

	return 0;
}

function _dialogSelArray($v='all', $skip=0) {//список диалогов для Select - отправка через AJAX
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id` IN (".APP_ID._dn(!SA, ',0').")
			  AND `sa` IN (0"._dn(!SA, ',1').")
			  OR `parent_any`
			ORDER BY `app_id` DESC,`id`";
	if(!$arr = query_arr($sql))
		return array();



	//Базовые диалоги
	$dlg_base = array();
	foreach($arr as $r) {
		if($r['element_group_id'])
			continue;
		if(!$r['parent_any'])
			continue;
		if($r['app_id'])
			continue;

		$dlg_base[] = _dialogSelArrayUnit($r);
	}
	if(!empty($dlg_base))
		array_unshift($dlg_base, array(
			'info' => 1,
			'title' => 'Базовые диалоги:'
		));




	//Списки приложения
	$dlg_app_spisok = array();
	foreach($arr as $r) {
		if($r['element_group_id'])
			continue;
		if($r['parent_any'])
			continue;
		if(!$r['app_id'])
			continue;
		if(!$r['spisok_on'])
			continue;
		if($r['id'] == $skip)
			continue;

		$dlg_app_spisok[] = _dialogSelArrayUnit($r);
	}
	if(!empty($dlg_app_spisok))
		array_unshift($dlg_app_spisok, array(
			'info' => 1,
			'title' => 'Диалоги-списки:'
		));




	//Не являются списками
	$dlg_app = array();
	foreach($arr as $r) {
		if($r['element_group_id'])
			continue;
		if($r['parent_any'])
			continue;
		if(!$r['app_id'])
			continue;
		if($r['spisok_on'])
			continue;

		$dlg_app[] = _dialogSelArrayUnit($r);
	}
	if(!empty($dlg_app))
		array_unshift($dlg_app, array(
			'info' => 1,
			'title' => 'Остальные:'
		));




	//диалоги-элементы
	$dlg_elem = array();
	foreach($arr as $r) {
		if(!$r['element_group_id'])
			continue;
		if($r['parent_any'])
			continue;
		if($r['app_id'])
			continue;

		$dlg_elem[] = _dialogSelArrayUnit($r, 1);
	}
	if(!empty($dlg_elem))
		array_unshift($dlg_elem, array(
			'info' => 1,
			'title' => 'Диалоги-элементы:'
		));


	//SA-диалоги
	$dlg_sa = array();
	foreach($arr as $r) {
		if($r['element_group_id'])
			continue;
		if($r['parent_any'])
			continue;
		if($r['app_id'])
			continue;

		$dlg_sa[] = _dialogSelArrayUnit($r, 1);
	}
	if(!empty($dlg_sa))
		array_unshift($dlg_sa, array(
			'info' => 1,
			'title' => 'SA-диалоги:'
		));



	if($v == 'dlg_func')
		return SA ? array_merge($dlg_sa) : array();

	if($v == 'spisok_only')
		return array_merge($dlg_base, $dlg_app_spisok);

	//диалоги, которые разрешено указывать для получения данных записи
	if($v == 'unit_get')
		return array_merge($dlg_app_spisok);

	if(SA) {
		$title = 'SA: всегда создавать новый диалог';
		array_unshift($dlg_base, array(
			'id' => -1,
			'title' => $title,
			'content' => '<div class="color-pay">'.$title.'</div>'
		));
		return array_merge($dlg_base, $dlg_app_spisok, $dlg_app, $dlg_elem, $dlg_sa);
	}

	return array_merge($dlg_app_spisok, $dlg_app);
}
function _dialogSelArrayUnit($r, $idShow=0) {//составление единицы значения селекта
	$u = array(
		'id' => _num($r['id']),
		'title' => $r['name']
	);

	$color = '';
	if(!$r['app_id'])
		$color = 'color-pay';
	if($r['spisok_on'])
		$color = 'color-sal'.(!$r['app_id'] ? ' b' : '');
	if($r['sa'])
		$color = 'color-ref';

	$u['content'] = '<div class="'.$color.'">'.
			 ($idShow ? '<b>'.$r['id'].'</b>. ' : '').
						$r['name'].
					'</div>';

	return $u;
}

function _dialogSpisokCmp($cmp) {//список колонок, используемых в диалоге (для выбора колонки по умолчанию)
	$send = array();

	foreach($cmp as $id => $r) {
		if(empty($r['col']))
			continue;
		$send[$id] = $r['col'].': '.@$r['name'];
	}

	return $send;
}

function _dialogContentDelSetup($dialog_id) {//иконка настройки содежания удаления записи (единицы списка)
	$isSetup = _BE('block_obj', 'dialog_del', $dialog_id);
	$tooltip = _tooltip(($isSetup ? 'Изменить' : 'Настроить').' содержание', -70);
	return
	($isSetup ?'<span class="color-pay b">Настроено.</span> ' : '').
	'<div val="dialog_id:56,dss:'.$dialog_id.'"'.
		' class="icon icon-set pl dialog-open'.$tooltip.
	'</div>';
}

function PHP12_dialog_sa() {//список диалоговых окон [12]
	$sql = "SELECT *
			FROM `_dialog`
			WHERE !`app_id`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	$send = '<table class="_stab small">'.
				'<tr>'.
					'<th>ID'.
					'<th>Таблица'.
					'<th>Имя диалога'.
					'<th>type'.
					'<th>afics'.
					'<th>col';
	foreach($arr as $r) {
		$color = '';
		if(_table($r['table_1']) == '_element')
			$color = 'b color-pay';
		if(_table($r['table_1']) == '_action')
			$color = 'red';
		$send .= '<tr>'.
					'<td class="w35 r grey'.($r['sa'] ? ' bg-fee' : '').'">'.$r['id'].
					'<td class="'.$color.'">'._table($r['table_1']).
					'<td class="over1 curP dialog-open" val="dialog_id:'.$r['id'].'">'.$r['name'].
					'<td class="center">'._elemColType($r['element_type']).
					'<td>'.$r['element_afics'].
					'<td class="grey">'.PHP12_dialog_col($r['id']);
	}
	$send .= '</table>';

	return $send;
}
function PHP12_dialog_app() {//список диалоговых окон для конкретного приложения [12]
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	$sql = "SELECT `obj_id`,1
			FROM `_block`
			WHERE `obj_name`='dialog_del'
			  AND `obj_id` IN ("._idsGet($arr).")";
	$contentDelAss = query_ass($sql);

	$send = '<table class="_stab small">'.
				'<tr>'.
					'<th class="w30">'.
					'<th class="w35">num'.
			  (SA ? '<th class="w50">ID' : '').
					'<th class="w200">Имя диалога'.
					'<th class="w30">'.
					'<th class="w70">Список'.
					'<th class="w100">Родитель'.
					'<th class="w70">Колонки'.
					'<th class="w30">h1'.
					'<th class="w30">h2'.
					'<th class="w30">h3'.
					'<th class="w100">content<br>del'.
			'</table>'.
			'<dl>';
	foreach($arr as $dialog_id => $r) {
		$parent = '';
		if($parent_id = $r['dialog_id_parent'])
			$parent = _dialogParam($parent_id, 'name');
		$send .= '<dd val="'.$dialog_id.'">'.
			'<table class="_stab small mt1">'.
				'<tr>'.
					'<td class="w30 r">'.
						'<div class="icon icon-move pl"></div>'.
					'<td class="w35 r grey">'.$r['num'].
			  (SA ? '<td class="w50 pale r">'.$dialog_id : '').
					'<td class="w200 over1 curP dialog-open" val="dialog_id:'.$dialog_id.'">'.$r['name'].
					'<td class="w30 r">'.
						'<div val="dialog_id:'.$dialog_id.'" class="icon icon-edit dialog-setup'._tooltip('Редактировать диалог', -66).'</div>'.
					'<td class="w70 center'.($r['spisok_on'] ? ' bg-dfd' : '').'">'.($r['spisok_on'] ? 'да' : '').
					'<td class="w100 color-sal'.($parent ? ' over1 curP dialog-open' : '').'" val="dialog_id:'.$parent_id.'">'.$parent.
					'<td class="w70 grey">'.PHP12_dialog_col($dialog_id).
					'<td class="w30">'.($r['insert_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td class="w30">'.($r['edit_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td class="w30">'.($r['del_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td class="w100 center'.(!empty($contentDelAss[$dialog_id]) ? ' bg-dfd' : '').'">'.
						_dialogContentDelSetup($dialog_id).
			'</table>';
	}

	$send .= '</dl>';

	return $send;
}
function PHP12_dialog_col($dialog_id) {//колонки, используемые в элементе
	$send = array();
	$dub = false;//флаг повторяющейся колонки
	foreach(_BE('elem_arr', 'dialog', $dialog_id) as $el) {
		//поиск элементам, которым не назначена колонка таблицы
		if(!$col = @$el['col'])
			foreach(_BE('elem_arr', 'dialog', $el['dialog_id']) as $ell)
				if(@$ell['col'] == 'col')
					if($el['dialog_id'] != 12) {
						$dlg = _dialogQuery($el['dialog_id']);
						$col = '<span class="bg-fee'._tooltip('Отсутствует имя колонки<br>'.$dlg['name'], 5, 'l', 1).'--- ['.$el['dialog_id'].']</span>';
						break;
					}

		if(!$col)
			continue;

		$colName = $col.': '.@$el['name'];

		if(isset($send[$col])) {
			$send[$col.'dub'.rand(0, 10000)] = $colName.' <span class="bg-fcc">повтор</span>';
			$dub = true;
			continue;
		}

		if($col == 'col')
			$send[$col] = '<span class="red b">'.$colName.'</span>';
		elseif($col == 'name')
			$send[$col] = '<span class="color-pay b">'.$colName.'</span>';
		elseif($col == 'req' || $col == 'req_msg')
			$send[$col] = '<span class="color-ref b">'.$colName.'</span>';
		else
			$send[$col] = $colName;
	}

	if(empty($send))
		return '';

	ksort($send);

	return
	'<div class="curP center'._dn(!$dub, 'bg-fcc').'" onclick="$(this).slideUp().next().slideDown()">'.count($send).'</div>'.
	'<div class="dn">'.implode('<br>', $send).'</div>';
}

function PHP12_spisok_app($type_id, $msgEmpty, $appAll=0) {//вывод списков по условиям
	$arr = array();

	foreach(_BE('elem_all') as $el) {
		if($el['dialog_id'] != $type_id)
			continue;
		if(!$dlg = _dialogQuery($el['num_1']))
			continue;
		if($appAll && !$dlg['app_id'] || !$appAll && $dlg['app_id']) {
			$el['dlg'] = $dlg;
			$arr[] = $el;
		}
	}

	if(empty($arr))
		return $msgEmpty;

	$send = '<table class="_stab">'.
				'<tr>'.
					'<th class="w50">el-id'.
					'<th>Диалог, создающий список'.
					'<th>Местонахождение списка';
	foreach($arr as $r) {
		if(!$el = _elemOne($r['id'])) {
			$send .=
				'<tr><td colspan="10" class="red">'.
						'Элемента '.$r['id'].' нет в кеше.';
			continue;
		}

		$bl = _blockOne($r['block_id']);

		$link = '';
		//ссылка на страницу, в котором расположен список
		if($bl['obj_name'] == 'page') {
			$page = _page($bl['obj_id']);
			$link = '<a href="'.URL.'&p='.$bl['obj_id'].'" class="color-pay">Страница '.$bl['obj_id'].' - '.$page['name'].'</a>';
		}
		//диалог, в котором расположен список
		if($bl['obj_name'] == 'dialog') {
			$dlg = _dialogQuery($bl['obj_id']);
			$link = '<a class="dialog-open" val="dialog_id:'.$bl['obj_id'].'">Диалог '.$bl['obj_id'].' - '.$dlg['name'].'</a>';
		}

		$send .= '<tr>'.
					'<td class="r grey">'.$r['id'].
					'<td class="b over1 curP dialog-open" val="dialog_id:'.$r['dlg']['id'].'"">'.$r['dlg']['name'].
					'<td>'.$link;
	}
	$send .= '</table>';

	return $send;
}
function PHP12_spisok14_all() {//списки-шаблоны для всех приложений. Страница 126
	return PHP12_spisok_app(14, 'Списков-шаблонов нет.', 1);
}
function PHP12_spisok23_all() {//списки-таблицы для всех приложений. Страница 126
	return PHP12_spisok_app(23, 'Списков-таблиц нет.', 1);
}
function PHP12_spisok14_app() {//списки-шаблоны для текущего приложения. Страница 127
	return PHP12_spisok_app(14, 'Списков-шаблонов нет.');
}
function PHP12_spisok23_app() {//списки-таблицы для текущего приложения. Страница 127
	return PHP12_spisok_app(23, 'Списков-таблиц нет.');
}

function _elemRule($i='all', $v=0) {//кеш правил для элементов
	global  $RULE_USE,//массив всех правил
			$DLG_ASS, //элемент содержит правило
		    $RULE_ASS;//правило содержит элемен

	if(!defined('RULE_USE')) {
		$key = 'RULE_USE';
		if(!$RULE_USE = _cache_get($key, 1)) {
			$sql = "SELECT *
					FROM `_element_rule_use`
					ORDER BY `dialog_id`,`rule_id`";
			$RULE_USE = query_arr($sql);
			_cache_set($key, $RULE_USE, 1);
		}

		$DLG_ASS = array();
		$RULE_ASS = array();
		foreach($RULE_USE as $r) {
			$did = _num($r['dialog_id']);
			$rid = _num($r['rule_id']);
			$DLG_ASS[$did][$rid] = 1;
			$RULE_ASS[$rid][$did] = 1;
		}

		define('RULE_USE', 1);
	}

	//содержит ли элемент правило
	if($dlg_id = _num($i))
		return isset($DLG_ASS[$dlg_id][$v]);

	return $RULE_USE;
}

function _elemOne($elem_id, $upd=false) {//запрос одного элемента
	global $BE_FLAG;

	//обновление данных элемента в кеше
	if($upd) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$elem_id;
		if(!$el = query_assoc($sql))
			return array();

		$key = 'ELMM';
		$global = $el['app_id'] ? 0 : 1;
		if(_cache_isset($key, $global)) {
			$ELM = _cache_get($key, $global);
			$el = _element('struct', $el);
			$el = _elementTitle($el);
			$el = _beElemDlg($el);

			//дочерние элементы
			$sql = "SELECT *
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			if($ELMCH = query_arr($sql)) {
				$ELMCH = _beElemAction($ELMCH);
				$el['vvv'] = array();
				foreach($ELMCH as $id => $ell) {
					$ell = _elementTitle($ell);
					$ell = _element('struct_vvv', $el, $ell);

					if(!empty($ELMCH[$id]['action']))
						$ell['action'] = $ELMCH[$id]['action'];

					$el['vvv'][] = $ell;
				}
			}

			$ELM[$elem_id] = $el;
			_cache_set($key, $ELM, $global);
			$BE_FLAG = 0;
		}
	}

	return _BE('elem_one', $elem_id);
}
function _blockOne($block_id, $upd=false) {//запрос одного блока
	global $BE_FLAG;

	//обновление данных блока в кеше
	if($upd) {
		$sql = "SELECT *
				FROM `_block`
				WHERE `id`=".$block_id;
		if(!$bl = query_assoc($sql))
			return array();

		$sql = "SELECT `id`
				FROM `_element`
				WHERE `block_id`=".$block_id."
				LIMIT 1";
		$bl['elem_id'] = _num(query_value($sql));

		$key = 'BLKK';
		$global = $bl['app_id'] ? 0 : 1;
		if(_cache_isset($key, $global)) {
			$BLK = _cache_get($key, $global);
			$bl = _beBlockStructure($bl);
			$BLK[$block_id] = $bl;
			_cache_set($key, $BLK, $global);
			$BE_FLAG = 0;
		}
	}

	return _BE('block_one', $block_id);
}


function _elemColType($id='all') {//тип данных, используемый элементом
	$col_type = array(
		1 => 'txt',
		2 => 'num',
		3 => 'connect',
		4 => 'count',
		5 => 'cena',
		6 => 'sum',
		7 => 'date',
		8 => 'image'
	);

	if($id == 'all')
		return $col_type;
	if(!isset($col_type[$id]))
		return '';

	return $col_type[$id];
}

function _elemIsConnect($el) {//определение, является ли элемент подключаемым списком
	if(empty($el))
		return false;

	if(!is_array($el))
		if(!$el = _elemOne(_num($el)))
			return false;

	if(!isset($el['dialog_id']))
		return false;

	switch($el['dialog_id']) {
		case 29:
		case 59: return true;
	}
	return false;
}
function _elemIsDate($el) {//определение, является ли элемент подключаемым списком
	if(empty($el))
		return false;

	if(!is_array($el))
		if(!$el = _elemOne(_num($el)))
			return false;

	if(!isset($el['dialog_id']))
		return false;

	switch($el['dialog_id']) {
		case 51: return true;
	}
	return false;
}

function _elem201init($el85, $prm, $send) {//получение данных элемента для настройки действия [201]
	$srce = $prm['srce'];

	if($srce['dialog_id'] != 201)
		if($srce['dialog_id'] == 202 && $el85['col'] != 'initial_id')
			if($srce['dialog_id'] != 206)//установка фокуса
				return $send;

	//получение настраиваемого элемента
	if(!$block_id = $srce['block_id'])
		return $send;
	if(!$BL = _blockOne($block_id))
		return $send;
	if(!$EL = $BL['elem'])
		return $send;

	switch($EL['dialog_id']) {
		case 1://галочка
		case 62://фильтр-галочка
			array_unshift($send, array(
				'id' => -2,
				'title' => 'галочка установлена',
				'content' => '<div class="color-pay b">галочка установлена</div>'.
							 '<div class="grey i ml20">Действие будет совершено при установленной галочке</div>'
			));
			array_unshift($send, array(
				'id' => -1,
				'title' => 'галочка НЕ установлена',
				'content' => '<div class="color-ref b">галочка НЕ установлена</div>'.
							 '<div class="grey i ml20">Действие будет совершено, если галочка снята</div>'
			));
			break;
		case 6: return _elem201initCnn($send, _jsCachePage());

		case 16:
			$vvv = _element('vvv', $EL);
			return _elem201initCnn($send, _sel($vvv));

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

	}

	return $send;
}
function _elem201initCnn($send, $vvv=array()) {
	foreach($vvv as $n => $r) {
		$r['content'] = '<span class="color-pay">выбрано</span> <b>'.$r['title'].'</b>';
		$r['title'] = 'выбрано "'.$r['title'].'"';
		array_push($send, $r);
	}

	array_unshift($send, array(
		'id' => -2,
		'title' => 'выбрано любое значение',
		'content' => '<div class="color-pay b">выбрано любое значение</div>'.
					 '<div class="grey i ml20">Действие с блоками будет совершено при выборе любого значения</div>'
	));
	array_unshift($send, array(
		'id' => -1,
		'title' => 'значение сброшено',
		'content' => '<div class="color-ref b">значение сброшено</div>'.
					 '<div class="grey i ml20">Действие с блоками будет совершено, если значение было сброшено</div>'
	));
	return $send;
}
function _elem212ActionFormat($el85_id, $elv_id, $send) {//преобразование данных для выбора в действиях [212]
	//СНАЧАЛА получение информации об элементе [85]
	if(!$el85 = _elemOne($el85_id))
		return $send;
	if($el85['dialog_id'] != 85)
		return $send;
	if(!$BL = $el85['block'])
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
		case 29://подключаемый список
			foreach($send as $n => $r) {
				if($r['id'] <= 0)
					continue;
				$send[$n]['title'] = 'установить "'.$r['title'].'"';
				$send[$n]['content'] = 'установить "<b>'.$r['title'].'</b>"';
			}
			array_unshift($send, array(
				'id' => -1,
				'title' => 'Сбросить значение',
				'content' => '<div class="color-ref">сбросить значение</div>'.
							 '<div class="grey i ml20">При нажатии на блок значение будет сброшено, либо поле очищено</div>'
			));
			break;
	}

	return $send;
}

function _elemCol($el) {//получение имени колонки
	if(!is_array($el))
		if(!$id = _num($el))
			return '';
		elseif(!$el = _elemOne($id))
			return '';

	if(empty($el))
		return '';
	if(empty($el['col']))
		return '';
	if(!$col = $el['col'])
		return '';
	if(!$id = _num($col))
		return $col;
	if(!$ell = _elemOne($id))
		return '';
	if(empty($ell['col']))
		return '';

	return $ell['col'];
}
function _elemColDlgId($elem_id, $oo=false) {//получение id диалога по имени колонки (для определения, вносит ли элемент данные из другого диалога)
/*
	$oo - OtherOnly: отправлять только флаг: елемент из своего диалога или нет
*/

	if(!$elem_id = _num($elem_id))
		return 0;
	if(!$el = _elemOne($elem_id))
		return 0;

	//определение диалога, в котором расположен элемент
	if(!$BL = $el['block'])
		return 0;
	if($BL['obj_name'] != 'dialog')
		return 0;
	if(!$DLG = _dialogQuery($BL['obj_id']))
		return 0;
	if(empty($el['col']))
		return $oo ? 0 : $DLG['id'];

	//если текстовое название колонки, значит она принадлежит текущему диалогу
	if(!$id = _num($el['col']))
		return $oo ? 0 : $DLG['id'];

	if(!$ell = _elemOne($id))
		return 0;
	if(!$BL = $ell['block'])
		return 0;
	if($BL['obj_name'] != 'dialog')
		return 0;
	//если текущий диалог не является дочерним - возврат диалога, в котором расположен элемент
	if(!$parent_id = $DLG['dialog_id_parent'])
		return $oo ? 0 : $BL['obj_id'];
	//если родитель является владельцем элемента - возврат текущего диалога
	if($parent_id == $BL['obj_id'])
		return $oo ? 0 : $DLG['id'];

	//сторонний диалог, от которого подключен элемент
	return $BL['obj_id'];
}

function _elemAttrCmp($el) {
	return '#cmp_'.$el['id'];
}
function _elemIdsTitle($v) {//получение имён по id элементов
	if(!$ids = _ids($v, 'arr'))
		return '';

	$send = '';
	$znak = _elemIsConnect($ids[0]) ? ' » ' : ', ';
	foreach($ids as $n => $id)
		$send .= ($n ? $znak : '')._element('title', $id);

	return $send;
}
function _elemUids($ids, $u) {//получение значения записи по идентификаторам элементов (в основном для [11])
	if(empty($u))
		return '';
	if(!$ids = _ids($ids, 'arr'))
		return '';

	foreach($ids as $k => $id) {
		if(!$el = _elemOne($id))
			return '';
		if(empty($el['col']))
			return '';

		$col = $el['col'];

		if(!isset($u[$col]))
			return '';
		if(!is_array($u[$col]))
			return $u[$col];
		$u = $u[$col];
	}

	return '';
}




function _elem_11_dialog($el) {//получение данных диалога по элементу 11
	if($el['dialog_id'] != 11)
		return 0;
	if(!$ell = _elemOne($el['txt_2']))
		return 0;
	if($ell['block']['obj_name'] != 'dialog')
		return 0;
	if(!$dialog_id = _num($ell['block']['obj_id']))
		return 0;
	if(!$dlg = _dialogQuery($dialog_id))
		return 0;

	return $dlg;
}





/* ---=== УКАЗАНИЕ ЭЛЕМЕНТОВ ПОД КОНКРЕТНОЕ ПРАВИЛО [1000] ===--- */
function PHP12_elem_all_rule_setup($prm) {
	if(!$rule_id = $prm['unit_get_id'])
		return _empty('Не получен id правила.');

	$sql = "SELECT *
			FROM `_element_rule_name`
			WHERE `id`=".$rule_id;
	if(!$rule = query_assoc($sql))
		return _empty('Правила '.$rule_id.' не существует.');

	//элементы, используемые в правиле
	$sql = "SELECT `dialog_id`,1
			FROM `_element_rule_use`
			WHERE `rule_id`=".$rule_id;
	$ass = query_ass($sql);

	$sql = "SELECT *
			FROM `_element_group`
			ORDER BY `sort`";
	if(!$group = query_arr($sql))
		return _empty('Отсутствуют группы элементов.');

	//получение всех элементов
	$sql = "SELECT
				*
			FROM `_dialog`
			WHERE `element_group_id` IN ("._idsGet($group).")
			ORDER BY `sort`,`id`";
	if(!$elem = query_arr($sql))
		return _empty('Нет элементов для отображения.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	//расстановка элементов в группы согласно правилу отображения
	foreach($elem as $id => $r)
		$group[$r['element_group_id']]['elem'][] = $r;

	$send = '';
	foreach($group as $r) {
		if(empty($r['elem']))
			continue;

		$send .= '<div class="fs15 mt20 mb5">'.$r['name'].':</div>';
		foreach($r['elem'] as $el) {
			$send .=
			'<div class="ml30 mt3">'.
				_check(array(
					'attr_id' => 'rule-el'.$el['id'],
					'title' => $el['name'].' <span class="pale">['.$el['id'].']</span>',
					'value' => _num(@$ass[$el['id']])
				)).
			'</div>';
		}
	}

	return
	'<div class="fs16 color-555">'.
		'Элементы, используемые в правиле'.
		'<br>'.
		'<b class="fs16">'.$rule['name'].'</b>:'.
	'</div>'.
	$send;
}
function PHP12_elem_all_rule_setup_save($dlg) {
	if($dlg['id'] != 1000)
		return;
	if(!SA)
		jsonError('Действие только для SA');

	//получение элемента-функции [12], отображающего диалог для выбора
	if(empty($dlg['cmp']))
		jsonError('Пустой диалог 1000');

	$elem_func_id = key($dlg['cmp']);

	if(!$vvv = $_POST['vvv'][$elem_func_id])
		jsonError('Нет данных');
	if(!$rule_id = _num($vvv['rule_id']))
		jsonError('Не получено id правила');
	$sql = "SELECT *
			FROM `_element_rule_name`
			WHERE `id`=".$rule_id;
	if(!$rule = query_assoc($sql))
		jsonError('Правила '.$rule_id.' не существует.');

	//Обновление элементов для правила
	$sql = "DELETE FROM `_element_rule_use` WHERE `rule_id`=".$rule_id;
	query($sql);

	if($ids = _ids($vvv['ids'], 'arr'))
		foreach($ids as $dialog_id) {
			$sql = "INSERT INTO `_element_rule_use`
						(`rule_id`,`dialog_id`)
					VALUES
						(".$rule_id.",".$dialog_id.")";
			query($sql);
		}

	_BE('dialog_clear');
	_cache_clear('RULE_USE', 1);

	jsonSuccess();
}



/* ---=== ЭЛЕМЕНТЫ, КОТОРЫЕ МОЖНО ВЫБИРАТЬ В НАСТРОЙКЕ ДИАЛОГА [13] ===--- */
function PHP12_elem_rule7($prm) {
	//элементы, используемые в правиле 7
	$sql = "SELECT `dialog_id`
			FROM `_element_rule_use`
			WHERE `rule_id`=7";
	if(!$ids = query_ids($sql))
		return _emptyMin('Нет элементов для выбора');

	//получение разрешённых элементов
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `id` IN (".$ids.")
			ORDER BY `sort`,`id`";
	if(!$elem = query_arr($sql))
		return _empty('Нет элементов для отображения.');

	$sql = "SELECT *
			FROM `_element_group`
			WHERE `id` IN ("._idsGet($elem, 'element_group_id').")
			ORDER BY `sort`";
	if(!$group = query_arr($sql))
		return _emptyMin('Отсутствуют группы элементов.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	//расстановка элементов в группы
	foreach($elem as $id => $r)
		$group[$r['element_group_id']]['elem'][] = $r;

	$ass = _idsAss(_elemPrintV($prm['el12'], $prm));

	$send = '';
	foreach($group as $r) {
		$send .= '<div class="fs15 mt15 mb5 color-555">'.$r['name'].':</div>';
		foreach($r['elem'] as $el) {
			$send .=
			'<div class="ml15 mt3">'.
				_check(array(
					'attr_id' => 'rule7-el'.$el['id'],
					'title' => $el['name'],
					'value' => _num(@$ass[$el['id']]) ? 1 : 0
				)).
			'</div>';
		}
	}

	return $send;
}



/* ---=== ВЫБОР ЭЛЕМЕНТА [50] ===--- */
function PHP12_elem_choose($prm) {//выбор элемента для вставки в блок. Диалог [50]
	$head = '';
	$content = '';
	$sql = "SELECT *
			FROM `_element_group`
			WHERE `sa` IN (0,".SA.")
			ORDER BY `sort`";
	if(!$group = query_arr($sql))
		return _emptyMin10('Отсутствуют группы элементов.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	//получение всех элементов
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `element_group_id` IN ("._idsGet($group).")
			  AND `sa` IN (0,".SA.")
			ORDER BY `sort`,`id`";
	if(!$elem = query_arr($sql))
		return _emptyMin10('Нет элементов для отображения.');

	//правила для каждого элемента
	$sql = "SELECT *
			FROM `_element_rule_use`
			WHERE `dialog_id` IN ("._idsGet($elem).")";
	foreach(query_arr($sql) as $r) {
		$dlg_id = _num($r['dialog_id']);
		$rid = _num($r['rule_id']);
		if(!isset($elem[$dlg_id]['rule']))
			$elem[$dlg_id]['rule'] = array();
		$elem[$dlg_id]['rule'][$rid] = 1;
	}

	if(!$rule_id = PHP12_elem_choose_rule($prm))
		return _emptyRed10(PHP12_elem_choose_rule($prm, 1));

	//расстановка элементов в группы согласно правилу отображения
	foreach($elem as $id => $r)
		if(isset($r['rule'][$rule_id]))
			$group[$r['element_group_id']]['elem'][] = $r;

	//скрытие разделов без элементов
	foreach($group as $id => $r)
		if(empty($r['elem']))
			unset($group[$id]);

	if(empty($group))
		return _emptyMin10('Нет элементов для отображения.').
			   PHP12_elem_choose_debug($prm);

	reset($group);
	$firstId = key($group);
	foreach($group as $id => $r) {
		$sel = _dn($id != $firstId, 'sel');
		$first = _dn($id != $firstId, 'first');
		$head .=
			'<table class="el-group-head'.$first.$sel.'" val="'.$id.'">'.
				'<tr>'.
	   ($r['img'] ? '<td class="w50 center"><img src="img/'.$r['img'].'">' : '').
					'<td class="fs14 '.($r['sa'] ? 'red pl5' : 'blue').'">'._br($r['name']).
			'</table>';

		$content .= '<dl id="cnt_'.$id.'" class="cnt'._dn($id == $firstId).'">';
		$n = 1;
		foreach($r['elem'] as $el)
				$content .=
					'<dd val="'.$el['id'].'">'.
						'<div class="elem-unit '.($el['sa'] ? 'red' : 'color-555').'" val="'.$el['id'].'">'.
							'<table class="w100p">'.
								'<tr><td class="num w25 r top pr5 grey">'.$n++.'.'.
									'<td class="b top">'.$el['name'].
							  (SA ? '<td class="w50 top">'.
										'<div class="icon icon-move-y fr pl"></div>'.
								        '<div class="icon icon-edit fr pl mr3 dialog-setup" val="dialog_id:'.$el['id'].'"></div>'
							  : '').
							'</table>'.
							'<div class="elem-img eli'.$el['id'].' mt5"></div>'.
						'</div>'.
					'</dd>';
		$content .=	'</dl>';
	}

	return
	'<table id="elem-group" class="w100p">'.
		'<tr><td class="w150 top prel">'.
				'<div id="head-back"></div>'.
				$head.
			'<td id="elem-group-content" class="top">'.
				'<div class="cnt-div">'.$content.'<div>'.
	'</table>'.
	PHP12_elem_choose_debug($prm);
}
function PHP12_elem_choose_rule($prm, $isMsg=0) {
	//прямое указание на правило
	if($rule_id = _num(@$prm['srce']['dop']['rule_id']))
		return !$isMsg ? $rule_id : 'Правило '.$rule_id.'.';

	if($block_id = $prm['srce']['block_id']) {
		if(!$BL = _blockOne($block_id))
			return !$isMsg ? 0 : 'Исходного блока '.$block_id.' не существует.';

		if($EL = $BL['elem'])
			switch($EL['dialog_id']) {
				case 23: return !$isMsg ? 5 : 'Ячейка таблицы.';
				case 44: return !$isMsg ? 4 : 'Сборный текст.';
			}

		switch($BL['obj_name']) {
			case 'page':
				if(!$page = _page($BL['obj_id']))
					return !$isMsg ? 0 : 'Несуществующая страница '.$BL['obj_id'].'.';
				if($page['dialog_id_unit_get'])
					return !$isMsg ? 9 : 'Блок страницы, принимающей данные записи.';
				return !$isMsg ? 1 : 'Блок со страницы.';
			case 'dialog':
				if(!$dlg = _dialogQuery($BL['obj_id']))
					return !$isMsg ? 0 : 'Несуществующий диалог '.$BL['obj_id'].'.';
				if($dlg['dialog_id_unit_get'])
					return !$isMsg ? 10 : 'Блок диалога, принимающего данные записи.';
				return !$isMsg ? 2 : 'Блок с диалога.';
			case 'dialog_del':  return !$isMsg ? 8 : 'Блок содержания удаления записи.';
			case 'spisok':      return !$isMsg ? 3 : 'Блок единицы списка.';
		}

		return !$isMsg ? 0 : 'Неизвестное местоположение.';
	}

	return !$isMsg ? 0 : 'Отсутствует исходный блок.';
}
function PHP12_elem_choose_debug($prm) {//информация о месте куда происходит вставка элемента
	if(!DEBUG)
		return '';

	$rule_setup = '';
	if($rule_id = PHP12_elem_choose_rule($prm))
		$rule_setup = '<a class="dialog-open ml10" val="dialog_id:1000,get_id:'.$rule_id.'">настроить правило</a>';

	return
	'<div class="pad10 line-t bg-ffc">'.
		PHP12_elem_choose_rule($prm, 1).
		$rule_setup.
	'</div>';
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
	'<div class="fs14 pad10 pl15 bg-orange line-b">'.$TITLE.' <b class="fs14">'.$NAME.'</b>:</div>'.
	_blockHtml(OBJ_NAME_CHOOSE, $obj_id, $cond).
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

	//поиск диалога в выпадающем списке [24]
	if($dlg_place = $el13['num_1']) {
		if(!_elemOne($dlg_place))
			return 'Элемента со списком диалогов не существует.';
		if(!$dlg24 = $prm['dop']['dlg24'])
			return 'Не выбран диалог в списке';
		return _dialogSel24($dlg_place, $dlg24);
	}

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





/* ---=== ВЫБОР ВНЕШНЕГО ВИДА МЕНЮ СТРАНИЦ [3] ===--- */
function PHP12_page_menu_type($prm) {
	$sel = _num($prm['el12']['txt_2']);
	if($col = $prm['el12']['col'])
		if($u = $prm['unit_edit'])
			$sel = $u[$col];


	$send = '';
	for($n = 1; $n <= 5; $n++)
		$send .= '<div class="page-menu type'.$n._dn($n != $sel, 'sel').'" val="'.$n.'"></div>';

	return $send;
}



/* ---=== ВЫБОР БЛОКОВ [19] ===--- */
function PHP12_block_choose($prm) {
	if(!$block_id = _num($prm['srce']['block_id']))
		return _emptyMin10('Отсутствует исходный блок.');
	if(!$BL = _blockOne($block_id))
		return _emptyMin10('Блока '.$block_id.' не существует.');

	$title = 'Страница';
	$obj_name = $BL['obj_name'];
	$obj_id = $BL['obj_id'];

	switch($obj_name) {
		case 'page':
			if(!$page = _page($obj_id))
				return _emptyMin10('Страницы '.$obj_id.' не существует.');
			$name = $page['name'];
			break;
		case 'dialog':
			$title = 'Диалог';
			if(!$dlg = _dialogQuery($obj_id))
				return _emptyMin10('Диалога '.$obj_id.' не существует.');
			$name = $dlg['name'];
			break;
		case 'spisok':
			if(!$el = _elemOne($obj_id))
				return _emptyMin10('Элемента '.$obj_id.' не существует.');

			$obj_name = $el['block']['obj_name'];
			$obj_id = $el['block']['obj_id'];
			if($obj_name == 'dialog') {
				$title = 'Диалог';
				if(!$dlg = _dialogQuery($el['num_1']))
					return _emptyMin10('Диалога-списка '.$obj_id.' не существует.');
				$name = $dlg['name'];
			} else {
				if(!$page = _page($obj_id))
					return _emptyMin10('Страницы '.$obj_id.' не существует.');
				$name = $page['name'];
			}
			break;
		default:
			return _emptyMin10('Выбор блоков возможен только на страницах и в диалоговых окнах.');
	}

	//доп.параметны, отправленные из JS
	$prm['dop'] += array(
		'level_deny' => 0,  //запрет изменения уровня блоков. Только верхний (первый) уровень
		'blk_deny' => 0,    //блоки, которые запрещено выбирать
		'sel' => 0          //выбранные блоки
	);

	$cond = array(
		'blk_choose' => 1,
		'blk_level' => $prm['dop']['level_deny'] ? 1 : _blockLevelDefine($obj_name),
		'blk_deny' => $prm['dop']['blk_deny'],
		'blk_sel' => $prm['dop']['sel']
	);

	return
	'<div class="fs14 pad10 pl15 bg-orange">'.$title.' <b class="fs14">'.$name.'</b>:</div>'.
	($prm['dop']['level_deny'] ? '' : PHP12_block_choose_but_level($obj_name, $obj_id)).
	'<div id="block-choose-div">'.
		_blockHtml($obj_name, $obj_id, $cond).
	'</div>';
}
function PHP12_block_choose_but_level($obj_name, $obj_id) {//кнопки уровня блоков
	$arr = _blockLevelButArr($obj_name, $obj_id);
	if(count($arr) < 2)
		return '';

	$html = '';
	foreach($arr as $n => $color)
		$html .= '<button class="block-choose-level-change vk small ml5 '.$color.'">'.$n.'</button>';

	return
	'<div class="bg-ffc">'.
		'<table class="bs5 ml10">'.
			'<tr><td class="color-sal">Уровни блоков:'.
				'<td>'.$html.
				'<td class="w50 level-hold">'.
		'</table>'.
	'</div>';
}




/* ---=== НАСТРОЙКА ВОЗДЕЙСТВИЯ НА ЗАПИСЬ ПОСЛЕ ВНЕСЕНИЯ ДАННЫХ [42] ===--- */
function PHP12_insert_unit_change($prm) {
	if(!$u = $prm['unit_edit'])
		return _emptyMin10('Не получены данные для настройки.');
	if(!$elem_id = $u['insert_unit_change_elem_id'])
		return _emptyMin10('Для настройки значений укажите путь к записи,<br>сохраните настройки и откройте снова.');
	if(!$el = _elemOne($elem_id))
		return _emptyMin10('Элемента '.$elem_id.' не существует.');
	if(!_elemIsConnect($el))
		return _emptyMin10('Элемент не является подключаемым списком.');

	return '';
}
function PHP12_insert_unit_change_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$elem_id = $u['insert_unit_change_elem_id'])
		return array();
	if(!$el = _elemOne($elem_id))
		return array();
	if(!_elemIsConnect($el))
		return array();
	if(!$DLG = _dialogQuery($el['num_1']))
		return array();

	$ass = PHP12_insert_unit_change_ass($u['insert_unit_change_v']);

	$send = array();
	foreach($DLG['cmp'] as $id => $r) {
		if(!$r['col'])
			continue;
		if($r['hidden'])
			continue;
		$src_id = _num(@$ass[$id]);
		$send[] = array(
			'dst_id' => $id,
			'dst_title' => _element('title', $id),
			'src_id' => $src_id,
			'src_title' => _element('title', $src_id)
		);
	}

	return $send;
}
function PHP12_insert_unit_change_ass($dst) {//ассоциативный массив id элементов: получатель <- исходный
	if(!$dst)
		return array();

	$ass = array();
	foreach(explode(',', $dst) as $r) {
		$ex = explode(':', $r);
		if(!$dst_id = _num(@$ex[0]))
			continue;
		$ass[$dst_id] = _num(@$ex[1]);
	}

	return $ass;
}


/* ---=== НАСТРОЙКА СОДЕРЖАНИЯ УДАЛЕНИЯ ЗАПИСИ [56] ===--- */
function PHP12_dialog_del_setup($prm) {
	if(!$dialog_id = $prm['srce']['dss'])
		return _emptyMin10('Не найден диалог.');
	if(!$dialog = _dialogQuery($dialog_id))
		return _emptyMin10('Диалога '.$dialog_id.' не существует.');

	$obj_name = 'dialog_del';

	return
	'<div class="fs14 pad10 pl15 bg-orange line-b">Настройка содержания удаления для диалога <b class="fs14">'.$dialog['name'].'</b>:</div>'.
	'<div class="bg-ffc pad10 line-b">'.
		_blockLevelChange($obj_name, $dialog_id).
	'</div>'.
	'<div class="block-content-'.$obj_name.'" style="width:500px">'.
		_blockHtml($obj_name, $dialog_id, array('blk_setup' => 1)).
	'</div>';
}







/* ---=== НАСТРОЙКА УСЛОВИЙ ДЛЯ СПИСКА [41] ===--- */
function PHP12_spfl($prm) {
	if(!$DS = $prm['srce']['dss'])
		return _emptyMin('Отсутствует id исходного диалога');

	return '';
}
function PHP12_spfl_save($DLG) {
	if($DLG['id'] != 41)
		return;

	//поиск id элемента, который является подключаемой PHP функцией
	$vvv_id = 0;

	foreach($DLG['cmp'] as $cmp)
		if($cmp['dialog_id'] == 12)
			if($cmp['txt_1'] == 'PHP12_spfl')
				$vvv_id = $cmp['id'];

	if(!$vvv_id)
		jsonError('Не найдена подключаемая функция');

	$send['v'] = '';
	$send['title'] = '';

	if(!empty($_POST['vvv']))
		if($arr = $_POST['vvv'][$vvv_id])
			if(is_array($arr))
				if(!empty($arr)) {
					$v = array();
					foreach($arr as $r) {
						if(!$r['elem_id'] = _ids($r['elem_id']))
							continue;
						if(!$r['cond_id'] = _num($r['cond_id']))
							continue;
						$r['unit_id'] = _num($r['unit_id'], 1);
						$v[] = $r;
					}
					$send['v'] = json_encode($v);
					$c = count($v);
					$send['c'] = $c;
					$send['title'] = $c.' услови'._end($c, 'е', 'я', 'й');
				}

	jsonSuccess($send);
}
function PHP12_spfl_vvv($prm) {//получение настроек для редактирования
	$send = array(
		'dss' => $prm['srce']['dss'],
		'vvv' => array(),
		'drop' => PHP12_spfl_drop()//стандартные значения выпадающего списка
	);

	//получение id диалога по элементу, через который был выбор
	if($elem_id = $prm['srce']['element_id'])
		$send['dss'] = _dialogSel24($elem_id, $prm['srce']['dss']);

	if(!$arr = $prm['srce']['dop'])
		return $send;

	$arr = htmlspecialchars_decode($arr);
	if(!$arr = json_decode($arr, true))
		return $send;

	foreach($arr as $n => $r) {
		$arr[$n]['elem_title'] = _elemIdsTitle($r['elem_id']);
		$arr[$n]['spisok'] = array();
		if($arr[$n]['elem_issp'] = _elemIsConnect($r['elem_id'])) {
			$spisok = _29cnn($r['elem_id']);
			$arr[$n]['spisok'] = PHP12_spfl_vvv_unshift($spisok);
		} else {
			$last = _idsLast($r['elem_id']);
			$el = _elemOne($last);
			if($el['dialog_id'] == 17) {
				$arr[$n]['elem_issp'] = 1;
				$arr[$n]['spisok'] = _element('vvv', $r);
			}
		}
	}

	$send['vvv'] = $arr;

	return $send;
}
function PHP12_spfl_drop() {
	return array(
		-11 => 'число текущего дня',
		-12 => 'число текущей недели',
		-13 => 'число текущего месяца',
		-14 => 'число текущего года',

		-21 => 'текущий пользователь'
	);
}
function PHP12_spfl_vvv_unshift($spisok) {//общие дополнительные значения
	array_unshift(
		$spisok,
		array(
			'id' => -3,
			'title' => 'Совпадает с данными, которые принимает блок',
			'content' => '<div class="b color-pay">Совпадает с данными, которые принимает блок</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет выбрана запись, которую принимает блок</div>'
		)
	);
	array_unshift(
		$spisok,
		array(
			'id' => -1,
			'title' => 'Совпадает с текущей страницей',
			'content' => '<div class="b color-pay">Совпадает с текущей страницей</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет выбрана запись, которую принимает текущая страница</div>'
		)
	);

	return $spisok;
}

/* ---=== ШАБЛОН ЕДИНИЦЫ СПИСКА [14] ===--- */
function PHP12_spisok14_setup($prm) {//настройка шаблона
	/*
		имя объекта: spisok
		 id объекта: id элемента, который размещает список
	*/
	if(!$unit = $prm['unit_edit'])
		return
		'<div class="bg-ffe pad10">'.
			_emptyMin('Настройка шаблона будет доступна после вставки списка в блок.').
		'</div>';

	//определение ширины шаблона
	if(!$block = _blockOne($unit['block_id']))
		return 'Блока, в котором находится список, не существует.';

	setcookie('block_level_spisok', 1, time() + 2592000, '/');
	$_COOKIE['block_level_spisok'] = 1;

	$width = _blockObjWidth('spisok', $unit['id']);

	return
	'<div class="bg-ffc pad10 line-b">'.
		_blockLevelChange('spisok', $unit['id']).
	'</div>'.
	'<div class="block-content-spisok" style="width:'.$width.'px">'.
		_blockHtml('spisok', $unit['id'], array('blk_setup' => 1)).
	'</div>';
}


/* ---=== НАСТРОЙКА ЯЧЕЕК ТАБЛИЦЫ [23] ===--- */
function PHP12_td_setup($prm) {//используется в диалоге [23]
	/*
		все действия через JS
	*/

	if(!$prm['unit_edit'])
		return _emptyMin('Настройка таблицы будет доступна после вставки списка в блок.');

	return '';
}
function PHP12_td_setup_save($cmp, $val, $unit) {//сохранение данных ячеек таблицы
	/*
		$cmp  - компонент из диалога, отвечающий за настройку ячеек таблицы
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается таблица

		Данные колонок таблицы записываются в _element
		parent_id = $unit['id'] (ID элемента-таблицы [23])

		num_8 - флаг активности ячейки. Если 1 - ячейка настроена и активна
	*/

	if(empty($unit['id']))
		return;

	//Сброс флага активности ячейки
	$sql = "UPDATE `_element`
			SET `num_8`=0
			WHERE `parent_id`=".$unit['id'];
	query($sql);

	if(!empty($val) && is_array($val))
		foreach($val as $sort => $r) {
			if(!$id = _num($r['id']))
				continue;

			$sql = "UPDATE `_element`
					SET `num_8`=1,
						`width`="._num($r['width']).",
						`font`='".$r['font']."',
						`color`='".$r['color']."',
						`txt_7`='".addslashes(_txt($r['txt_7']))."',
						`txt_8`='".$r['txt_8']."',
						`sort`=".$sort."
					WHERE `parent_id`=".$unit['id']."
					  AND `id`=".$id;
			query($sql);
		}

	//удаление значений, которые были удалены при настройке
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND !`num_8`";
	if($ids = query_ids($sql)) {
		$sql = "DELETE FROM `_element` WHERE `id` IN (".$ids.")";
		query($sql);

		$sql = "DELETE FROM `_action` WHERE `element_id` IN (".$ids.")";
		query($sql);
	}

	_BE('elem_clear');
}
function PHP12_td_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}


/* ---=== НАСТРОЙКА МЕНЮ ПЕРЕКЛЮЧЕНИЯ БЛОКОВ ===--- */
function PHP12_menu_block_setup() {//используется в диалоге [57]
	return '';
}
function PHP12_menu_block_setup_save($cmp, $val, $unit) {//сохранение данных о пунктах меню
	if(!$parent_id = _num($unit['id']))
		return;

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$parent_id;
	$app_id = query_value($sql);

	$ids = array();
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $sort => $r) {
			if($id = _num($r['id']))
				$ids[] = $id;
			if(!$title = _txt($r['title']))
				continue;
			$blk = _ids($r['blk']);
			$update[] = "(
				".$id.",
				".$app_id.",
				".$parent_id.",
				'".addslashes($title)."',
				'".($blk ? $blk : '')."',
				"._num($r['def']).",
				".$sort."
			)";
		}
	}

	$ids = implode(',', $ids);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (0".($ids ? ',' : '').$ids.")";
	query($sql);

	//ID элементов-значений, составляющих сборный текст
	$sql = "UPDATE `_element`
			SET `txt_2`='".$ids."'
			WHERE `id`=".$parent_id;
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$parent_id;
	query($sql);
}
function PHP12_menu_block_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}



/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ RADIO для [16][17] ===--- */
function PHP12_radio_setup() {
	return '';
}
function PHP12_radio_setup_save($cmp, $val, $unit) {//сохранение значений radio
	/*
		$cmp  - компонент из диалога, отвечающий за настройку значений radio
		$val  - значения, полученные для сохранения
		$unit - элемент, в котором размещается radio

		Данные колонок таблицы записываются в _element
		parent_id = $unit['id'] (ID элемента-radio [16])
	*/

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$unit['id'];
	$app_id = query_value($sql);

	$update = array();
	$idsNoDel = '0';

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if(!$title = _txt($r['title']))
				continue;
			if($id = _num($r['id']))
				$idsNoDel .= ','.$id;
			$content = _txt($r['content']);
			$update[] = "(
				".$id.",
				".$app_id.",
				".$unit['id'].",
				'".addslashes($title)."',
				'".addslashes($content)."',
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `id` NOT IN (".$idsNoDel.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$unit['id'];
	query($sql);

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`txt_2`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$unit['id'];
	query($sql);
}
function PHP12_radio_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}

/*
function PHP12_radio_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'id' => _num($r['id']),
			'title' => $r['txt_1'],
			'content' => $r['txt_2'],
			'def' => _num($r['def']),
			'use' => 0
		);
	}

	$send = PHP12_radio_setup_vvv_use($send, $u['id']);

	return $send;
}
function PHP12_radio_setup_vvv_use($send, $parent_id) {//использование значений radio (чтобы нельзя было удалять значения)
	$el = _elemOne($parent_id);

	if(empty($el['block']))
		return $send;

	//пока только для диалогов
	if($el['block']['obj_name'] != 'dialog')
		return $send;
	if(!$dlg = _dialogQuery($el['block']['obj_id']))
		return $send;
	if(!$col = $el['col'])
		return $send;
	//только для таблиц, в которых есть колонка dialog_id
	if(empty($dlg['field1']['dialog_id']))
		return $send;

	//получение количества использования значений
	$sql = "SELECT
				`".$col."` `id`,
				COUNT(*) `use`
			FROM `"._table($dlg['table_1'])."`
			WHERE `dialog_id`=".$el['block']['obj_id']."
			GROUP BY `".$col."`";
	if($ass = query_ass($sql))
		foreach($send as $n => $r) {
			if(empty($ass[$r['id']]))
				continue;
			$send[$n]['use'] = $ass[$r['id']];
		}

	return $send;
}
*/


/* ---=== НАСТРОЙКА КОНКРЕТНЫХ ЗНАЧЕНИЙ ЭЛЕМЕНТА COUNT [35] ===--- */
function PHP12_count_value($prm) {
	return '';
}
function PHP12_count_value_save($cmp, $val, $unit) {
	if(!$unit_id = _num($unit['id']))
		return;
	if($unit['num_1'] != 3682)//изменения возможны если выбран пункт "конкретные значения"
		return;
	if(!$col = $cmp['col'])
		return;

	$txt = '';
	$def = 0;

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$ids = array();
		$title = array();
		foreach($val as $r) {
			$id = _num($r['id'], 1);
			$ids[] = $id;
			$title[] = _txt($r['title']);
			if($r['def'])
				$def = $id;
		}
		$txt = json_encode(array(
			'ids' => $ids,
			'title' => $title
		));
	}

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($txt)."',
				`def`=".$def."
			WHERE `id`=".$unit_id;
	query($sql);
}
function PHP12_count_value_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$col = $prm['el12']['col'])
		return array();
	if(!$arr = $u[$col])
		return array();

	$arr = json_decode($arr, true);
	$ids = $arr['ids'];
	$title = $arr['title'];

	$send = array();
	foreach($ids as $n => $id) {
		$send[] = array(
			'id' => _num($id),
			'title' => $title[$n],
			'def' => $u['def'] == $id ? 1 : 0
		);
	}

	return $send;
}


/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ФИЛЬТРА RADIO для [74] ===--- */
function PHP12_filter_radio_setup($prm) {
	if(!$prm['unit_edit'])
		return _emptyMin('Настройка значений фильтра будет доступна<br>после вставки элемента в блок.');
	return '';
}
function PHP12_filter_radio_setup_save($cmp, $val, $unit) {//сохранение значений фильтра radio
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений фильтра radio
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает сборный текст
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	//получение id приложения у родительского элемента
	$sql = "SELECT `app_id`
			FROM `_element`
			WHERE `id`=".$parent_id;
	$app_id = query_value($sql);

	$ids = '0';
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		$sort = 0;
		foreach($val as $r) {
			if($id = _num($r['id']))
				$ids .= ','.$id;
			if(!$txt_1 = _txt($r['txt_1']))
				continue;
			$update[] = "(
				".$id.",
				".$app_id.",
				".$parent_id.",
				'".addslashes($txt_1)."',
				'"._txt($r['txt_2'])."',
				"._num($r['num_1']).",
				"._num($r['def']).",
				".$sort++."
			)";
		}
	}

	//удаление удалённых значений
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	//сброс значения по умолчанию
	$sql = "UPDATE `_element`
			SET `def`=0
			WHERE `id`=".$parent_id;
	query($sql);

	if(empty($update)) {
		_elemOne($unit['id'], true);
		return;
	}

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`txt_2`,
				`num_1`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`txt_2`=VALUES(`txt_2`),
				`num_1`=VALUES(`num_1`),
				`def`=VALUES(`def`),
				`sort`=VALUES(`sort`)";
	query($sql);

	//установка нового значения по умолчанию
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `def`
			LIMIT 1";
	$def = _num(query_value($sql));

	$sql = "UPDATE `_element`
			SET `def`=".$def."
			WHERE `id`=".$parent_id;
	query($sql);

	_elemOne($unit['id'], true);
}
function PHP12_filter_radio_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$c = '';
		if($r['txt_2']) {
			$vv = htmlspecialchars_decode($r['txt_2']);
			$arr = json_decode($vv, true);
			$c = count($arr);
		}

		$send[] = array(
			'id' => _num($r['id']),
			'txt_1' => $r['txt_1'],
			'def' => _num($r['def']),
			'c' => $c,
			'txt_2' => $r['txt_2'],
			'num_1' => _num($r['num_1'])
		);
	}

	return $send;
}


/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА для [44] ===--- */
function PHP12_44_setup($prm) {
	/*
		все действия через JS

		num_8 - пробел справа от значения
		txt_2 - ID элементов-значений, составляющих сборный текст
	*/

	if(!$prm['unit_edit'])
		return _emptyMin('Настройка сборного текста будет доступна<br>после вставки элемента в блок.');

	return '';
}
function PHP12_44_setup_save($cmp, $val, $unit) {//сохранение содержания Сборного текста
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений сборного текста
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает сборный текст
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	$ids = array();
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['id']))
				continue;
			$ids[] = $id;
			$spc = _num($r['spc']);
			$update[] = array(
				'id' => $id,
				'spc' => $spc
			);
		}
	}

	$ids = implode(',', $ids);

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (0".($ids ? ',' : '').$ids.")";
	query($sql);

	if(empty($update))
		return;

	foreach($update as $sort => $r) {
		$sql = "UPDATE `_element`
				SET `parent_id`=".$parent_id.",
					`num_8`=".$r['spc'].",
					`sort`=".$sort."
				WHERE `id`=".$r['id'];
		query($sql);
	}
}
function PHP12_44_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}


/* ---=== НАСТРОЙКА БАЛАНСА - СУММ ЗНАЧЕНИЙ ЕДИНИЦЫ СПИСКА для [27] ===--- */
function PHP12_balans_setup($prm) {
	/*
		все действия через JS

		num_8: знак 1=вычитание, 0=сложение
	*/

	if(!$prm['unit_edit'])
		return _emptyMin('Настройка значений будет доступна<br>после вставки элемента в блок.');

	return '';
}
function PHP12_balans_setup_save($cmp, $val, $unit) {//сохранение содержания баланса
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений баланса
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает баланс
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	$ids = '0';
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['id']))
				continue;
			$ids .= ','.$id;
			$spc = _num($r['minus']);
			$update[] = array(
				'id' => $id,
				'minus' => $spc
			);
		}
	}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(empty($update))
		return;

	foreach($update as $sort => $r) {
		$sql = "UPDATE `_element`
				SET `parent_id`=".$parent_id.",
					`num_8`=".$r['minus'].",
					`sort`=".$sort."
				WHERE `id`=".$r['id'];
		query($sql);
	}
}
function PHP12_balans_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}




/* ---=== ВЫБОР ИКОНКИ [36] ===--- */
function PHP12_icon18_list($prm) {
	$sel = 0;
	if($col = $prm['el12']['col'])
		if($u = $prm['unit_edit'])
			$sel = $u[$col];

	$send = '';
	foreach(PHP12_icon18_type() as $id => $name) {
		$send .=
			'<div class="icu over1'._dn($id!=$sel, 'sel').'" val="'.$id.'">'.
				'<div class="icon icon-'.$name.' curP"></div>'.
			'</div>';
	}

	return
	'<div class="_icon-choose mt3">'.
		$send.
	'</div>';
}
function PHP12_icon18_type($id='all') {//доступные варианты иконок
	$icon = array(
		1 => 'hint',
		2 => 'print',
		3 => 'ok',
		4 => 'set',
		5 => 'set-b',
		6 => 'client',
		7 => 'worker',
		8 => 'vk',
		9 => 'rub',
		10 => 'usd',
		11 => 'stat',
		12 => 'set-dot',
		13 => 'info',
		14 => 'search',
		15 => 'star',
		16 => 'comment',
		17 => 'add',
		18 => 'edit',
		19 => 'del',
		20 => 'del-red',
		21 => 'doc-add',
		22 => 'order',
		23 => 'calendar',
		24 => 'eye',
		25 => 'clock'
	);

	if($id == 'all')
		return $icon;

	return isset($icon[$id]) ? $icon[$id] : 'empty';
}



/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
function PHP12_action_list($prm) {
	//текущий диалог для обновления списка действий после редактирования
	$dss = $prm['el12']['block']['obj_id'];

	switch($dss) {
		//действия для элемента
		case 200:
		case 220:
		case 240:
			if($block_id = _num($prm['srce']['block_id'])) {
				if(!$BL = _blockOne($block_id))
					return _emptyMin('Блока id'.$block_id.' не существует.');
				$elem_id = $BL['elem_id'];
			} elseif(!$elem_id = _num($prm['srce']['element_id']))
				return _emptyMin('Отсутствует ID элемента.');
			$where = "`element_id`=".$elem_id;
			break;
		//действия для блока
		case 210:
		case 230:
			if(!$block_id = _num($prm['srce']['block_id']))
				return _emptyMin('Отсутствует ID исходного блока.');
			if(!$BL = _blockOne($block_id))
				return _emptyMin('Блока id'.$block_id.' не существует.');
			$where = "`block_id`=".$block_id;
			break;
		default: return _emptyMin('Неизвестный диалог для настройки действий.');
	}

	$sql = "SELECT *
			FROM `_action`
			WHERE ".$where."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return _emptyMin('Действий не назначено.');

	$spisok = '';
	foreach($arr as $id => $r) {
		$spisok .=
			'<dd val="'.$id.'">'.
			'<table class="w100p bs5 bor1 bg-gr2 over2 mb5 curD">'.
				'<tr>'.
					'<td class="w25 top">'.
						'<div class="icon icon-move-y pl"></div>'.
					'<td><div class="fs15 color-555">'._dialogParam($r['dialog_id'], 'name').'</div>'.
						'<div class="mt3 ml10">'.
							PHP12_action_201($r).
							PHP12_action_211($r).
							PHP12_action_212($r).
							PHP12_action_213($r).
							PHP12_action_214($r).
							PHP12_action_215($r).
							PHP12_action_216($r).
							PHP12_action_221($r).
							PHP12_action_222($r).
							PHP12_action_223($r).
							PHP12_action_224($r).
						'</div>'.
					'<td class="w50 r top">'.
						'<div val="dialog_id:'.$r['dialog_id'].',edit_id:'.$id.',dss:'.$dss.'" class="icon icon-edit pl dialog-open'._tooltip('Настроить действие', -60).'</div>'.
						_iconDel(array(
							'class' => 'pl ml5 dialog-open',
							'val' => 'dialog_id:'.$r['dialog_id'].',del_id:'.$id.',dss:'.$dss
						)).
			'</table>'.
			'</dd>';
	}

	return '<dl>'.$spisok.'</dl>';
}
function PHP12_action_201($r) {//ЭЛЕМЕНТ: скрытие/показ блоков
	if($r['dialog_id'] != 201)
		return '';

/*
	apply_id: Действие с блоками: скрыть|показать
	filter: Фильтр
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
	revers: Обратное действие
	target_ids: Блоки, на которые происходит воздействие
	effect_id: Эффект

*/


	//Названия действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);


	$c = count(_ids($r['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');

	$initial = '-';
	switch($r['initial_id']) {
		case -1: $initial = '<b class="color-ref">значение сброшено</b>'; break;
		case -2: $initial = '<b class="color-pay">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($r['element_id']))
				break;

			switch($el['dialog_id']) {
				case 29:
				case 59:
					if(!$DLG = _dialogQuery($el['num_1']))
						break;
					if(!$u = _spisokUnitQuery($DLG, $r['initial_id']))
						break;
					$initial = 'выбрано <b class="color-pay">'.$u['txt_1'].'</b>';
			}

	}


	$effect = '';
	if($r['effect_id']) {
		//Названия эффектов
		$sql = "SELECT `txt_1`
				FROM `_element`
				WHERE `id`=".$r['effect_id'];
		$name = query_value($sql);
		$effect =   '<div class="fs12 grey mt2">'.
						'Эффект: '.
						'<span class="fs12 color-sal">'.$name.'</span>'.
					'</div>';

	}

	$revers = $r['revers'] ? '<div class="fs11 i color-555 mt2">Применяется обратное действие</div>' : '';

	return
	'<div class="b">'.$apply.' '.$target.'</div>'.
	'<span class="grey">если</span> '.$initial.
	$effect.
	$revers;
}
function PHP12_action_211($r) {//БЛОК: скрытие/показ блоков
	if($r['dialog_id'] != 211)
		return '';

	//Название действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);


	$c = count(_ids($r['target_ids'], 1));
	$target = $c.' блок'._end($c, '', 'а', 'ов');


	$effect = '';
	if($r['effect_id']) {
		//Названия эффектов
		$sql = "SELECT `txt_1`
				FROM `_element`
				WHERE `id`=".$r['effect_id'];
		$name = query_value($sql);
		$effect =   '<div class="fs12 grey mt2">'.
						'Эффект: '.
						'<span class="fs12 color-sal">'.$name.'</span>'.
					'</div>';

	}

	$revers = $r['revers'] ? '<div class="fs11 i color-555 mt2">Применяется обратное действие</div>' : '';

	return
	'<div class="b">'.$apply.' '.$target.'</div>'.
	$effect.
	$revers;
}
function PHP12_action_212($r) {//БЛОК: Установка значения элементу
	if($r['dialog_id'] != 212)
		return '';
	if(!$elem_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id элемента</div>';
	if(!$el = _elemOne($elem_id))
		return '<div class="red">Элемента не существует</div>';

	$send = '<div class="red">Неизвестный элемент ['.$el['dialog_id'].']</div>';

	switch($el['dialog_id']) {
		case 1:
		case 62:
			$send = '<div class="red">Неизвестное действие для галочки</div>';
			if($r['apply_id'] == -1)
				$send = '<b>Снять галочку</b>';
			if($r['apply_id'] == 1)
				$send = '<b>Установить галочку</b>';
			break;
	}


	return $send;
}
function PHP12_action_213($r) {//БЛОК: блокировка элементов
	if($r['dialog_id'] != 213)
		return '';
	if(!$ids = _ids($r['target_ids'], 'arr'))
		return '<div class="red">Отсутствует элементы для блокировки</div>';

	//Название действия
	$sql = "SELECT `txt_1`
			FROM `_element`
			WHERE `id`=".$r['apply_id'];
	$apply = query_value($sql);

	$elem = array();
	foreach($ids as $id)
		$elem[] = '<b>'._element('title', $id).'</b>';

	$target = implode(', ', $elem);

	return
	$apply.' '.
	'элемент'.(count($elem) > 1 ? 'ы' : '').' '.
	$target;
}
function PHP12_action_214($r) {//БЛОК: переход на страницу
	if($r['dialog_id'] != 214)
		return '';
	if(!$page_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="red">Страницы не существует</div>';

	return
	'<span class="grey">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>'.
	($r['apply_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи</div>' : '');
}
function PHP12_action_215($r) {//БЛОК: открытие диалога
	if($r['dialog_id'] != 215)
		return '';
	if(!$dlg_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id диалога</div>';
	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="red">Диалога не существует</div>';

	return
	'<span class="grey">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>'.
	($r['apply_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи для отображения</div>' : '').
	($r['effect_id'] ? '<div class="color-555 i fs12 mt3">Блок передаёт данные записи для редактирования</div>' : '');
}
function PHP12_action_216($r) {//БЛОК: Установка фокуса на элемент
	if($r['dialog_id'] != 216)
		return '';
	if(!$elem_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id элемента</div>';

	return '<span class="grey">Элемент:</span> <b>'._element('title', $elem_id).'</b>';
}
function PHP12_action_221($r) {//ЭЛЕМЕНТ: переход на страницу
	if($r['dialog_id'] != 221)
		return '';
	if(!$page_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id страницы</div>';
	if(!$page = _page($page_id))
		return '<div class="red">Страницы не существует</div>';

	return
	'<span class="grey">Cтраница:</span> '.
	'<b>'.$page['name'].'</b>';
}
function PHP12_action_222($r) {//ЭЛЕМЕНТ: открытие диалога
	if($r['dialog_id'] != 222)
		return '';
	if(!$dlg_id = _num($r['target_ids']))
		return '<div class="red">Отсутствует id диалога</div>';
	if(!$DLG = _dialogQuery($dlg_id))
		return '<div class="red">Диалога не существует</div>';

	return
	'<span class="grey">Диалог:</span> '.
	'<b>'.$DLG['name'].'</b>';
}
function PHP12_action_223($r) {//ЭЛЕМЕНТ: тёмная подсказка
	if($r['dialog_id'] != 223)
		return '';
	if(!$v = _ids($r['target_ids']))
		return '<div class="red">Отсутствует значение для подсказки</div>';

	return
	'<span class="grey">Значение:</span> '.
	'<span class="color-pay">'._elemIdsTitle($v).'</span>';
}
function PHP12_action_224($r) {//ЭЛЕМЕНТ: внешняя ссылка
	if($r['dialog_id'] != 224)
		return '';

	return
	'<span class="grey">Ссылка:</span> '.
	($r['target_ids'] ?
		'<span class="blue">'.$r['target_ids'].'</span>'
	: '<span class="grey">совпадает с содержанием элемента</span>');
}





/* ---=== НАСТРОЙКА ПАРАМЕТРОВ ШАБЛОНА ДЛЯ ДОКУМЕНТОВ [114] ===--- */
function PHP12_template_param($prm) {
	if(!$prm['unit_edit'])
		return _emptyMin('Настройка значений будет доступна<br>после создания шаблона.');

	return '';
}
function PHP12_template_param_save($cmp, $val, $unit) {
	if(!$template_id = _num($unit['id']))
		return;

	//получение id приложения у шаблона
	$sql = "SELECT `app_id`
			FROM `_template`
			WHERE `id`=".$template_id;
	$app_id = query_value($sql);

	$ids = '0';         //сбор id элементов, которые не будут удалены
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['elem_id']))
				continue;

			$ids .= ','.$id;

			if(!$txt_10 = _txt($r['txt_10']))
				jsonError(array(
					'attr_cmp' => '#dd'.$id.' .txt_10',
					'text' => 'Не указан код значения'
				));

			$update[] = array(
				'id' => $id,
				'txt_10' => $txt_10
			);
		}
	}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `id` IN ("._ids($unit['param_ids']).")
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	//ID элементов-значений, применяемых в шаблоне
	$sql = "UPDATE `_template`
			SET `param_ids`='"._ids($ids)."'
			WHERE `id`=".$template_id;
	query($sql);

	if(empty($update))
		return;

	foreach($update as $sort => $r) {
		$sql = "UPDATE `_element`
				SET `app_id`=".$app_id.",
					`txt_10`='".$r['txt_10']."',
					`sort`=".$sort."
				WHERE `id`=".$r['id'];
		query($sql);
	}
}
function PHP12_template_param_vvv($prm) {//получение значений для настройки истории действий
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$ids = _ids($u['param_ids']))
		return array();

	$send = array();
	foreach(_ids($ids, 'arr') as $id) {
		if(!$el = _elemOne($id))
			continue;
		$send[] = array(
			'id' => _num($id),
			'txt_10' => $el['txt_10'],
			'dialog_id' => _num($el['dialog_id']),
			'title' => $el['title']
		);
	}

	return $send;
}




/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ДЛЯ ПЛАНИРОВЩИКА [115] ===--- */
function PHP12_cron_dst_prm($prm) {
	if(!$u = $prm['unit_edit'])
		return _emptyMin('Настройка данных будет доступна<br>после выбора списка для внесения данных.');
	if(!$u['dst_spisok'])
		return _emptyMin('Выберите список для внесения данных,<br>сохраните окно и откройте снова.');

	return '';
}
function PHP12_cron_dst_prm_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$dlg_id = $u['dst_spisok'])
		return array();
	if(!$dlg = _dialogQuery($dlg_id))
		return array();

	$ass = PHP12_cron_dst_prm_ass($u['dst_prm']);

	$send = array();
	foreach($dlg['cmp'] as $id => $r) {
		if(empty($r['col']))
			continue;
		if(!empty($r['hidden']))
			continue;
		$src_id = _num(@$ass[$id]);
		$send[] = array(
			'dst_id' => $id,
			'dst_title' => _element('title', $r),
			'src_id' => $src_id,
			'src_title' => _element('title', $src_id)
		);
	}

	return $send;
}
function PHP12_cron_dst_prm_ass($dst) {//ассоциативный массив id элементов: получатель <- исходный
	if(!$dst)
		return array();

	$ass = array();
	foreach(explode(',', $dst) as $r) {
		$ex = explode(':', $r);
		if(!$dst_id = _num(@$ex[0]))
			continue;
		$ass[$dst_id] = _num(@$ex[1]);
	}

	return $ass;
}







/* ---=== НАСТРОЙКА ШАБЛОНА ИСТОРИИ ДЕЙСТВИЙ [67] ===--- */
function PHP12_history_setup() {
	/*
		действие (type_id):
			1 - запись внесена
			2 - запись изменена
			3 - запись удалена

		Дочерние элементы:
			txt_7 - текст слева от значения
			num_8 - значение из диалога
			txt_8 - текст справа от значения
			txt_9 - условия отображения сборки
	*/
	return '';
}
function PHP12_history_setup_save($dlg) {//сохранение настройки шаблона истории действий
	/*
		одна сборка = один элемент
		HISTORY_ACT - действие: insert, edit, del
		HISTORY_KEY - ключ, по которому будут определяться вносимые элементы (временное хранение в `col`)
	*/

	if($dlg['id'] != 67)
		return;
	if(empty($_POST['vvv']))
		jsonError('Отсутствуют данные для сохранения');
	if(!$dialog_id = _num($_POST['dss']))
		jsonError('Отсутствует исходный диалог');
	if(!$dialog = _dialogQuery($dialog_id))
		jsonError('Диалога '.$dialog_id.' не существует');
	if(!$i = _num(key($_POST['vvv'])))
		jsonError('Отсутствует ключ, по которому находятся данные vvv');

	$v = $_POST['vvv'][$i];
	$vvv = empty($v['v']) ? array() : $v['v'];

	if(!is_array($vvv))
		jsonError('Данные не являются массивом');
	if(!$type_id = _historyAct($v['act']))
		jsonError('Неизвестное действие');

	define('HISTORY_ACT', $v['act']);
	define('HISTORY_KEY', '67_'.$dialog_id.'_'.HISTORY_ACT);

	//ID ранее внесённых элементов, которые не будут удалены
	$ids = array();
	$update = array();

	foreach($vvv as $sort => $r) {
		$font = _txt($r['font']);
		$color = _txt($r['color']);
		$txt_7 = _txt($r['txt_7'], 1);
		$txt_8 = _txt($r['txt_8'], 1);
		$txt_9 = _txt($r['txt_9']);
		if(!$txt_7 && !$txt_8)
			continue;
		if($id = _num($r['id']))
			$ids[] = $id;
		$update[] = "(
			".$id.",
			".$dialog['app_id'].",
			'".HISTORY_KEY."',
			'".$font."',
			'".$color."',
			'".addslashes($txt_7)."',
			'".addslashes($txt_8)."',
			'".$txt_9."',
			".$sort.",
			".USER_ID."
		)";
	}

	$ids = implode(',', $ids);

	//удаление элементов, которые были удалены
	$sql = "DELETE FROM `_element`
			WHERE `id` IN ("._idsGet($dialog[HISTORY_ACT.'_history_elem']).")
			  AND `id` NOT IN ("._ids($ids).")";
	query($sql);

	if(!empty($update)) {
		$sql = "INSERT INTO `_element` (
					`id`,
					`app_id`,
					`col`,
					`font`,
					`color`,
					`txt_7`,
					`txt_8`,
					`txt_9`,
					`sort`,
					`user_id_add`
				)
				VALUES ".implode(',', $update)."
				ON DUPLICATE KEY UPDATE
					`app_id`=VALUES(`app_id`),
					`col`=VALUES(`col`),
					`font`=VALUES(`font`),
					`color`=VALUES(`color`),
					`txt_7`=VALUES(`txt_7`),
					`txt_8`=VALUES(`txt_8`),
					`txt_9`=VALUES(`txt_9`),
					`sort`=VALUES(`sort`)";
		query($sql);
	}

	//получение ID элементов, которые были сохранены
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `col`='".HISTORY_KEY."'
			  AND `user_id_add`=".USER_ID."
			ORDER BY `sort`";
	$ids = query_ids($sql);
	$ids = $ids ? $ids : '';

	//сохранение ID элементов в диалоге
	$sql = "UPDATE `_dialog`
			SET `".HISTORY_ACT."_history_elem`='".$ids."'
			WHERE `id`=".$dialog_id;
	query($sql);

	//очистка временного ключа
	$sql = "UPDATE `_element`
			SET `col`=''
			WHERE `col`='".HISTORY_KEY."'";
	query($sql);

	//обновление активности в истории
	$sql = "UPDATE `_history`
			SET `active`=".($ids ? 1 : 0)."
			WHERE `type_id`=".$type_id."
			  AND `dialog_id`=".$dialog_id;
	query($sql);

	_BE('dialog_clear');
	_BE('elem_clear');

	$dialog = _dialogQuery($dialog_id);
	$send['tmp'] = _dialogSetupHistoryTmp($dialog[HISTORY_ACT.'_history_elem']);

	jsonSuccess($send);
}
function PHP12_history_setup_vvv($prm) {//получение значений для настройки истории действий
	if(!$dialog_id = _num($prm['srce']['dss']))
		return array();
	if(!$DLG = _dialogQuery($dialog_id))
		return array();

	$act = $prm['dop']['act'];
	if(!$arr = $DLG[$act.'_history_elem'])
		return array();

	foreach($arr as $id => $r) {
		$c = 0;
		if($r['txt_9']) {
			$vv = htmlspecialchars_decode($r['txt_9']);
			$vv = json_decode($vv, true);
			$c = count($vv);
		}
		$arr[$id]['c'] = $c;
		$arr[$id]['title'] = _element('title', $r);
		unset($arr[$id]['action']);
	}
	return ($arr);
}

function _historyAct($i='all') {//действия истории - ассоциативный массив
	$action =  array(
		'insert' => 1,
		'edit' => 2,
		'del' => 3
	);

	$idName =  array(
		1 => 'insert',
		2 => 'edit',
		3 => 'del'
	);

	if($i == 'all')
		return $action;

	if($id = _num($i)) {
		if(isset($idName[$id]))
			return $idName[$id];
		return '';
	}



	if(!isset($action[$i]))
		return false;

	return $action[$i];
}
function _historyInsert($type_id, $dialog, $unit_id) {//внесение истории действий
	//история не вносится, если запись физически может удаляться из базы
	if(!isset($dialog['field1']['deleted']))
		return 0;

	$active = empty($dialog[_historyAct($type_id).'_history_elem']) ? 0 : 1;

	$sql = "INSERT INTO `_history` (
				`app_id`,
				`type_id`,
				`dialog_id`,
				`unit_id`,
				`active`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$type_id.",
				".$dialog['id'].",
				".$unit_id.",
				".$active.",
				".USER_ID."
			)";
	return query_id($sql);
}
function _historyInsertEdit($dialog, $unitOld, $unit) {//внесение истории действий при редактировании
	if(empty($unitOld))
		return;
	if($parent_id = $dialog['dialog_id_parent'])
		if(!$dialog = _dialogQuery($parent_id))
			return;
	if(!isset($dialog['field1']['deleted']))
		return;


	$edited = array();
	foreach($unitOld as $i => $v) {
		if($unit[$i] == $v)
			continue;

		$hidden = false;//скрытые элементы в историю не попадают
		$el = array();
		$name = '';
		foreach($dialog['cmp'] as $cmp_id => $cmp) {
			if(empty($cmp['col']))
				continue;
			if($i != $cmp['col'])
				continue;

			//картинки в историю не попадают
			if($cmp['dialog_id'] == 60) {
				$hidden = true;
				break;
			}
			if(!empty($cmp['hidden'])) {
				$hidden = true;
				break;
			}
			$el = $cmp;
			$name = $cmp['name'];
			break;
		}

		if($hidden)
			continue;

		$edited[] = array(
			'name' => $name,
			'old' => _element('history', $el, $v),
			'new' => _element('history', $el, $unit[$i])
		);
	}

	if(!$edited)
		return;

	$history_id = _historyInsert(2, $dialog, $unit['id']);

	$insert = array();
	foreach($edited as $r)
		$insert[] = "(
			".APP_ID.",
			".$history_id.",
			'".$r['name']."',
			'".addslashes($r['old'])."',
			'".addslashes($r['new'])."'
		)";

	$sql = "INSERT INTO `_history_edited` (
				`app_id`,
				`history_id`,
				`name`,
				`old`,
				`new`
			) VALUES ".implode(',', $insert);
	query($sql);
}
function _historySpisok($EL, $prm) {//список истории действий [68]
	$sql = "SELECT *
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `active`
			  "._historyUnitCond($EL, $prm)."
			  AND `user_id_add`
			  AND `dtime_add`
			ORDER BY `dtime_add` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return _emptyMin10('Истории нет.');

	foreach($arr as $id => $r)
		$arr[$id]['edited'] = array();

	//история - редактирование
	$sql = "SELECT *
			FROM `_history_edited`
			WHERE `history_id` IN ("._idsGet($arr).")
			ORDER BY `id`";
	foreach(query_arr($sql) as $r)
		$arr[$r['history_id']]['edited'][] = $r;

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `id` IN ("._idsGet($arr, 'unit_id').")";
	$unitArr = query_arr($sql);

	//вставка значений из вложенных списков
	$unitArr = _spisokInclude($unitArr);

	//распределение истории по дням
	$spisok = array();
	foreach($arr as $r) {
		$day = substr($r['dtime_add'], 0, 10);
		if(!isset($spisok[$day]))
			$spisok[$day] = array();
		$spisok[$day][] = $r;
	}

	$datFirst = key($spisok);
	$send = '';
	foreach($spisok as $day => $day_arr) {
		$send .= '<div class="history-day'._dn($day == $datFirst, 'pt20').'">'.FullData($day, 1, 0, 1).'</div>';

		$last = count($day_arr) - 1;
		$user_id =  $day_arr[0]['user_id_add'];
		$un = '';
		foreach($day_arr as $n => $r) {
			$dlg = _dialogQuery($r['dialog_id']);
			$msg = '';
			$prm['unit_get'] = $unitArr[$r['unit_id']];
			$prm = _blockParam($prm);
			foreach($dlg[_historyAct($r['type_id']).'_history_elem'] as $hel)
				$msg .= _historyKit($hel, $prm);

			$is_last = $n == $last;//последняя запись

			//изменился пользователь
			if($user_id != $r['user_id_add']) {
				$send .= _historySpisokU($user_id, $un);
				$user_id = $r['user_id_add'];
				$un = '';
			}

			$un .= '<table class="history-un'._dn($is_last, 'mb5').'">'.
						'<tr><td class="top tdo">'.
								'<div class="history-o o'.$r['type_id'].'"></div>'.
								'<span class="dib pale w35 mr5">'.substr($r['dtime_add'], 11, 5).'</span>'.
							'<td>'.
				   (SA && DEBUG ? '<div val="dialog_id:'.$r['dialog_id'].',menu:2" class="icon icon-edit fr pl dialog-setup'._tooltip('Настроить историю', -60).'</div>' : '').
								$msg.
								_historySpisokEdited($r).
					'</table>';

			if($is_last) {
				$send .= _historySpisokU($user_id, $un);
				$un = '';
			}
		}
	}

	return $send;
}
function _historyKit($el, $prm) {//составление одной сборки
	if(!$u = $prm['unit_get'])
		return _msgRed('отсутствует запись');

	//показ сборки по условиям, если есть
	if($cond = $el['txt_9']) {
		$arr = htmlspecialchars_decode($cond);
		if(!$arr = json_decode($arr, true))
			return _msgRed('не получен массив условий');

		foreach($arr as $r) {
			if(!$ell = _elemOne($r['elem_id']))
				return _msgRed('отсутствует элемент '.$r['elem_id']);
			if(!$col = $ell['col'])
				return _msgRed('отсутствует имя колонки');


			$connect_id = $u[$col];
			if(is_array($connect_id))
				$connect_id = $u[$col]['id'];

			switch($r['cond_id']) {
				//равно
				case 3:
					if($r['unit_id'] != $connect_id)
						return '';
					break;
				default: return _msgRed('условие '.$r['cond_id'].' не доделано');
			}
		}
	}

	if(!$el['dialog_id'])
		return $el['txt_7'].$el['txt_8'];
	if(!$txt = _element('print', $el, $prm))
		return '';

	$cls = array();
	$cls[] = $el['font'];
	$cls[] = $el['color'];
	$cls = array_diff($cls, array(''));
	$cls = implode(' ', $cls);

	$txt = _elemFormat($el, $prm, $txt);//[67] форматирование для истории действий

	$txt = '<span class="'.$cls.'">'.$txt.'</span>';
	return $el['txt_7'].$txt.$el['txt_8'];
}
function _historySpisokU($user_id, $un) {//вывод пользователя для отдельной группы истории
	return
	'<table class="mt5">'.
		'<tr><td class="top">'._user($user_id, 'ava30').
			'<td class="top">'.
				'<div class="fs12 ml5 color-555">'._user($user_id, 'name').'</div>'.
				$un.
	'</table>';
}
function _historySpisokEdited($hist) {//история при редактировании
	if($hist['edited_old'])
		return
		'<div class="history-old ">'.
			$hist['edited_old'].
		'</div>';

	if(empty($hist['edited']))
		return '';

	$send = '<table class="_stab hist">';
	foreach($hist['edited'] as $r) {
		$send .=
			'<tr><td class="grey r b">'.$r['name'].
				'<td class="grey">'.$r['old'].
				'<td class="grey">»'.
				'<td class="grey">'.$r['new'];
	}

	$send .= '</table>';

	return $send;
}
function _historyUnitCond($el, $prm) {//отображение истории для конкретной записи, которую принимает страница
	if(!$el['num_8'])
		return '';

	//история может быть размещёна либо на странице, либо в диалоге
	switch($el['block']['obj_name']) {
		case 'page':
			$page_id = $el['block']['obj_id'];
			if(!$page = _page($page_id))
				return " AND !`id` /* страницы ".$page_id." не существует */";
			if(!$dialog_id = $page['dialog_id_unit_get'])
				return " AND !`id` /* страница не принимает данные записи */";
			if(!$unit_id = _num(@$_GET['id']))
				return " AND !`id` /* идентификатор записи не получен */";
			break;
		case 'dialog':
			$dlg_id = $el['block']['obj_id'];
			if(!$DLG = _dialogQuery($dlg_id))
				return " AND !`id` /* диалога ".$dlg_id." не существует */";
			if(!$dialog_id = $DLG['dialog_id_unit_get'])
				return " AND !`id` /* диалог не принимает данные записи */";
			if(!$unit_id = $prm['unit_get_id'])
				return " AND !`id` /* id записи не получен */";
			break;
		default: return " AND !`id` /* не страница и не диалог */";
	}

	$ids = 0;

	//получение id записей, которые были связаны с текущей записью
	$sql = "SELECT `block_id`,`col`
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$dialog_id."
			  AND LENGTH(`col`)";
	if($cols = query_ass($sql)) {
		$cond = array();
		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id` IN ("._idsGet($cols, 'key').")";
		foreach(query_arr($sql) as $r) {
			$col = $cols[$r['id']];
			if(_num($col))
				$col = _elemCol($col);
			$cond[] = "`dialog_id`=".$r['obj_id']." AND `".$col."`=".$unit_id;
		}

		if(!empty($cond)) {
			$sql = "SELECT `id`
					FROM `_spisok`
					WHERE ".implode(' OR ', $cond);
			$ids = query_ids($sql);
		}
	}

	return " AND `unit_id` IN (".$unit_id.",".$ids.")";
}







function _image($el, $prm) {//элемент - загрузка изображений [60]
/*
	Загрузка изображений производится тремя способами:
		1. Выбор файла
		2. Вставка прямой ссылки, либо скриншок
		3. Вебкамера

	Данные об изображениях хранятся в таблице `_image`.
	В объекте указываются id прикреплённых изображений в текстовой колонке.
	Если id изображения со знаком минус - это изображение было удалено и находится с корзине объекта.

	Просмотр изображения производится диалогом [65].
	Класс '.image-open' отвечает за открытие изображения.
	Диалогу [65] передаются все идентифитаторы изображений, прикреплённые объекту.

	Функция _spisokImage переводит ПЕРВЫЙ id изображения в данные для каждого объекта.
	Идентификаторы помещаются в переменную 'ids'.
	Если изображений нет в объекте, создаётся пустой массив array('ids'=>'').
*/

	if($prm['blk_setup'])
		return _emptyMin('Изображения');

	$html = '';
	if($v = _elemPrintV($el, $prm)) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id` IN ("._ids($v).")
				ORDER BY `sort`";
		foreach(query_arr($sql) as $r)
			$html .= _imageDD($r);
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_image">'.
		'<dl>'.
			$html.
			'<dd class="dib">'.
				'<table class="_image-load">'.
					'<tr><td>'.
							'<div class="_image-add icon-image"></div>'.
							'<div class="icon-image spin"></div>'.
							'<div class="_image-prc"></div>'.
							'<div class="_image-dis"></div>'.
							'<table class="tab-load">'.
								'<tr><td class="icon-image ii1">'.//Выбрать из файлов
										'<form>'.
											'<input type="file" accept="image/jpeg,image/png,image/gif,image/tiff" />'.
										'</form>'.
									'<td class="icon-image ii2">'.//Указать ссылку на изображение
								'<tr><td class="icon-image ii3">'.//Фото с вебкамеры
									'<td class="icon-image ii4 empty">'.//Достать из корзины
							'</table>'.

				'</table>'.
			'</dd>'.
		'</dl>'.
		'<div class="_image-link dn mt5">'.
			'<table class="w100p">'.
				'<tr><td>'.
						'<input type="text" class="w100p" placeholder="вставьте ссылку или скриншот и нажмите Enter" />'.
					'<td class="w50 center">'.
						'<div class="icon icon-ok"></div>'.
						'<div class="icon icon-del pl ml5"></div>'.
			'</table>'.
		'</div>'.
	'</div>';
}
function _imageServerCache() {//кеширование серверов изображений
	$key = 'IMG_SERVER';
	if($arr = _cache_get($key, 1))
		return $arr;

	$sql = "SELECT `id`,`path` FROM `_image_server`";
	return _cache_set($key, query_ass($sql), 1);
}
function _imageServer($v) {//получение сервера (пути) для изображнения
/*
	если $v - число, получение имени пути
	если $v - текст, это сам путь и получение id пути. Если нет, то создание
*/
	if(empty($v))
		return '';

	$SRV = _imageServerCache();

	//получение id пути
	if($server_id = _num($v)) {
		if(empty($SRV[$server_id]))
			return '';

		return $SRV[$server_id];
	}

	foreach($SRV as $id => $path)
		if($v == $path)
			return $id;

	//внесение в базу нового пути
	$sql = "INSERT INTO `_image_server` (
				`path`,
				`user_id_add`
			) VALUES (
				'".addslashes($v)."',
				"._num(@USER_ID)."
			)";
	$insert_id = query_id($sql);

	_cache_clear('IMG_SERVER', 1);

	return $insert_id;
}
function _imageNo($width=80, $cr=false) {//картинка, если изображнеия нет
	return
	'<img src="'.APP_HTML.'/img/nofoto-s.gif"'.
		' width="'.$width.'"'.
 ($cr ? ' class="br1000"' : '').//круглое фото
	' />';
}
function _imageHtml($r, $width=80, $h=0, $cr=false, $click=true) {//получение картинки в html-формате
	if(empty($r))
		return _imageNo($width, $cr);
	if(!is_array($r))
		return _imageNo($width, $cr);
	if(empty($r['id']))
		return _imageNo($width, $cr);

	$width = $width ? $width : 80;

	$st = $width > 80 ? 'max' : 80;
	$width = $width > $r['max_x'] ? $r['max_x'] : $width;
	if($h) {
		$s = _imageResize($r['max_x'], $r['max_y'], $width, $width);
		$width = $s['x'];
		$h = $s['y'];
	}

	$cls = array();
	if($click)
		$cls[] = 'image-open';
	if($cr)
		$cls[] = 'br1000';

	return
		'<img src="'._imageServer($r['server_id']).$r[$st.'_name'].'"'.
			' width="'.$width.'"'.
	  ($h ? ' height= "'.$h.'"' : '').
	($cls ? ' class="'.implode(' ', $cls).'"'.
			' val="'.(empty($r['ids']) ? $r['id'] : $r['ids']).'"'
  : '').

		' />';
}
function _imageNameCreate() {//формирование имени файла из случайных символов
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name, $exp) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagealphablending($im_new, false);//устанавливает режим смешивания
	imagesavealpha($im_new, true);//сохранять информацию о прозрачности
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	switch($exp) {
		case 'png': imagepng($im_new, $name); break;
		case 'gif': imagegif($im_new, $name); break;
		default: imagejpeg($im_new, $name, 79);
	}
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//изменение размера изображения с сохранением пропорций
	$x = $x_new;
	$y = $y_new;
	// если ширина больше или равна высоте
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // если новая ширина больше, чем исходная, то X остаётся исходным
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // если новая высота в итоге осталась меньше исходной, то подравнивание по Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// если высота больше ширины
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // если новая высота больше, чем исходная, то Y остаётся исходным
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // если новая ширина в итоге осталась меньше исходной, то подравнивание по X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}

function _imageLink($url, $return='arr') {//сохранение изображения по прямой ссылке
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
	    CURLOPT_TIMEOUT => 60,//максимальное время работы cURL
	    CURLOPT_FOLLOWLOCATION => 1,//следовать перенаправлениям
	    CURLOPT_RETURNTRANSFER => 1,//результат писать в переменную
	    CURLOPT_NOPROGRESS => 0,//индикатор загрузки данных
	    CURLOPT_BUFFERSIZE => 1024,//размер буфера 1 Кбайт
	    //функцию для подсчёта скачанных данных. Подробнее: http://stackoverflow.com/a/17642638
	    CURLOPT_PROGRESSFUNCTION => function ($ch, $dwnldSize, $dwnld, $upldSize) {
	        if($dwnld > 1024 * 1024 * 15)//Когда будет скачано больше 15 Мбайт, cURL прервёт работу
	            return 1;
	        return 0;
	    },
	    CURLOPT_SSL_VERIFYPEER => 0//проверка сертификата
//	    CURLOPT_SSL_VERIFYHOST => 2,//имя сертификата и его совпадение с указанным хостом
//	    CURLOPT_CAINFO => __DIR__ . '/cacert.pem'//сертификат проверки. Скачать: https://curl.haxx.se/docs/caextract.html
	));

	//код последней ошибки
	if(curl_errno($ch)) {
		_debugLog('Ошибка при загрузке изображения: '.$url);
		if($return == 'id')
			return 0;
		else
			jsonError('При загрузке произошла ошибка');
	}

	$raw   = curl_exec($ch);    //данные в переменную
	$info  = curl_getinfo($ch); //информация об операции
	curl_close($ch);//завершение сеанса cURL

	if(!is_dir(APP_PATH.'/.tmp'))
		mkdir(APP_PATH.'/.tmp', 0777, true);

	$file_tmp_name = APP_PATH.'/.tmp/'.rand(0, 99999999).'_'.TODAY_UNIXTIME.'.tmp';
	$file = fopen($file_tmp_name,'w');
	fwrite($file, $raw);
	fclose($file);

	$send = _imageSave($info['content_type'], $file_tmp_name, $return);
	unlink($file_tmp_name);

	return $send;
}
function _imageSave($file_type, $file_tmp_name, $return='arr') {//сохранение полученного изображения
	$im = null;
	if(!defined('APP_ID'))
		define('APP_ID', 0);
	$IMAGE_PATH = APP_PATH.'/.image/'.APP_ID;
	$server_id = _imageServer('//'.DOMAIN.APP_HTML.'/.image/'.APP_ID.'/');

	//создание директории, если отсутствует
	if(!is_dir($IMAGE_PATH))
		mkdir($IMAGE_PATH, 0777, true);

	$exp = 'jpg';
	switch($file_type) {
		case 'image/jpeg': $im = @imagecreatefromjpeg($file_tmp_name); break;
		case 'image/png': $im = @imagecreatefrompng($file_tmp_name); $exp = 'png'; break;
		case 'image/gif': $im = @imagecreatefromgif($file_tmp_name); $exp = 'gif'; break;
		case 'image/tiff':
			$tmp = $IMAGE_PATH.'/'.USER_ID.'.jpg';
			$image = NewMagickWand(); // magickwand.org
			MagickReadImage($image, $file_tmp_name);
			MagickSetImageFormat($image, 'jpg');
			MagickWriteImage($image, $tmp); //сохранение результата
			ClearMagickWand($image); //удаление и выгрузка полученного изображения из памяти
			DestroyMagickWand($image);
			$im = @imagecreatefromjpeg($tmp);
			unlink($tmp);
			break;
	}


	if(!$im)
		jsonError('Загруженный файл не является изображением.<br>Выберите JPG, PNG, GIF или TIFF формат.');

	$x = imagesx($im);
	$y = imagesy($im);
	if($x < 10 || $y < 10)
		jsonError('Изображение слишком маленькое.<br>Используйте размер не менее 10x10 px.');

	$fileName = time().'-'._imageNameCreate();
	$NAME_MAX = $fileName.'-900.'.$exp;
	$NAME_80 = $fileName.'-80.'.$exp;

	$max = _imageImCreate($im, $x, $y, 900, 900, $IMAGE_PATH.'/'.$NAME_MAX, $exp);
	$_80 = _imageImCreate($im, $x, $y, 80, 80, $IMAGE_PATH.'/'.$NAME_80, $exp);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,0) FROM `_image`";
	$sort = query_value($sql);

	$sql = "INSERT INTO `_image` (
				`app_id`,
				`server_id`,

				`max_name`,
				`max_x`,
				`max_y`,
				`max_size`,

				`80_name`,
				`80_x`,
				`80_y`,
				`80_size`,

				`sort`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$server_id.",

				'".$NAME_MAX."',
				".$max['x'].",
				".$max['y'].",
				".$max['size'].",

				'".$NAME_80."',
				".$_80['x'].",
				".$_80['y'].",
				".$_80['size'].",

				".$sort.",
				"._num(@USER_ID)."
		)";
	$image_id = query_id($sql);

	if($return == 'id')
		return $image_id;

	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$image_id;
	return query_assoc($sql);
}
function _imageDD($img) {//единица изображения для настройки
	return
	'<dd class="dib mr3 curM" val="'.$img['id'].'">'.
		'<div class="icon icon-off'._tooltip('Переместить в корзину', -70).'</div>'.
		'<table class="_image-unit">'.
			'<tr><td>'.
				_imageHtml($img, 80, 1).
		'</table>'.
	'</dd>';
}

function _image60_save($cmp, $unit) {//Применение загруженных изображений после сохранения
	//поле, хранящее список id изображений
	if(!$col = _elemCol($cmp))
		return;
	if(!$img = $unit[$col])
		return;
	if(!$ids = @$img['ids'])
		return;

	foreach(explode(',', $ids) as $n => $id) {
		if($id < 0)
			continue;
		$sql = "UPDATE `_image`
				SET `sort`=".$n."
				WHERE `id`=".$id;
		query($sql);
	}
}


function PHP12_image_show($prm) {//просмотр изображений
	$image = 'Изображение отсутствует.';//основная картинка, на которую нажали. Выводится первой
	$spisok = '';//html-список дополнительных изображений
	$spisokJs = array();//js-список всех изображений
	$spisokIds = array();//id картинок по порядку
	$image_id = 0;

	if($ids = $prm['dop']) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id`="._idsFirst($ids);
		if($im = query_assoc($sql)) {
			$image_id = $im['id'];
			$image = '<img src="'._imageServer($im['server_id']).$im['max_name'].'"'.
						 ' width="'.$im['max_x'].'"'.
						 ' height="'.$im['max_y'].'"'.
						 ' />';

			$sql = "SELECT *
					FROM `_image`
					WHERE `id` IN (".$ids.")
					ORDER BY `sort`";
			$arr = query_arr($sql);
			if(count($arr) > 1) {
				$spisok = '<div class="line-t pad10 center bg-gr2">';
				foreach($arr as $r) {
					$sel = $r['id'] == $image_id ? ' sel' : '';
					$spisok .=
					'<div class="dib ml3 mr3">'.
						'<table class="iu'.$sel.'" val="'.$r['id'].'">'.
							'<tr><td><img src="'._imageServer($r['server_id']).$r['80_name'].'"'.
										' width="'.$r['80_x'].'"'.
										' height="'.$r['80_y'].'"'.
									' />'.
						'</table>'.
					'</div>';
					$spisokJs[] = $r['id'].':{'.
						'src:"'.addslashes(_imageServer($r['server_id']).$r['max_name']).'",'.
						'x:'.$r['max_x'].','.
						'y:'.$r['max_y'].','.
					'}';
					$spisokIds[] = $r['id'];
				}
				$spisok .= '</div>';
			}
		}
	}

	return
	'<div id="_image-show">'.
		'<table class="w100p">'.
			'<tr><td id="_image-main" val="'.$image_id.'">'.
					$image.
		'</table>'.
		$spisok.
	'</div>'.
	'<script>'.
		'var IMG_ASS={'.implode(',', $spisokJs).'},'.
			'IMG_IDS=['.implode(',', $spisokIds).'];'.
	'</script>';
}
function PHP12_image_deleted($prm) {//удалённые изображения [63]
	if(!$dop = $prm['dop'])
		return '<div class="_empty min">Удалённых изображений нет</div>';

	$ids = array();
	foreach(explode(',', $dop) as $r) {
		if(!$id = _num($r, 1))
			continue;
		if($id > 0)
			continue;
		$ids[] = abs($id);
	}

	if(empty($ids))
		return '<div class="_empty min">Удалённые изображения отсутствуют</div>';

	$sql = "SELECT *
			FROM `_image`
			WHERE `id` IN (".implode(',', $ids).")
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">Удалённые изображения не найдены</div>';

	$html = '';
	foreach($arr as $r) {
		$html .=
		'<div class="prel dib ml3 mr3">'.
			'<div val="'.$r['id'].'" class="icon icon-recover'._tooltip('Восстановить', -43).'</div>'.
			'<table class="_image-unit">'.
				'<tr><td>'.
					_imageHtml($r, 80, 1).
			'</table>'.
		'</div>';
	}

	return '<div class="_image">'.$html.'</div>';
}
function PHP12_image_webcam($prm) {//Веб-камера [61]
	$el = $prm['el12'];
	$width = $el['block']['width'];
	$mar = explode(' ', $el['mar']);
	$width = round($width - $mar[1] - $mar[3]);
	$height = round($width * 0.75);

	$flashvars =
		'width='.$width.
		'&height='.$height.
		'&dest_width='.$width.
		'&dest_height='.$height.
		'&image_format=jpeg'.
		'&jpeg_quality=100'.
		'&enable_flash=true'.
		'&force_flash=false'.
		'&flip_horiz=false'.
		'&fps=30'.
		'&upload_name=webcam'.
		'&constraints=null'.
		'&swfURL=""'.
		'&flashNotDetectedText=""'.
		'&noInterfaceFoundText=""'.
		'&unfreeze_snap=true'.
		'&iosPlaceholderText=""'.
		'&user_callback=null'.
		'&user_canvas=null';

	return
	'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"'.
			' type="application/x-shockwave-flash"'.
	        ' codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0"'.
	        ' width="'.$width.'"'.
	        ' height="'.$height.'"'.
	        ' align="middle">'.
	            '<param name="wmode" value="opaque" />'.
				'<param name="allowScriptAccess" value="always" />'.
				'<param name="allowFullScreen" value="false" />'.
				'<param name="movie" value="" />'.
				'<param name="loop" value="false" />'.
				'<param name="menu" value="false" />'.
				'<param name="quality" value="best" />'.
				'<param name="bgcolor" value="#ffffff" />'.
				'<param name="flashvars" value="'.$flashvars.'" />'.
				'<embed src="'.APP_HTML.'/modul/element/webcam.swf?2"'.
					  ' wmode="opaque" loop="false" menu="false" quality="best" bgcolor="#ffffff" width="'.$width.'" height="'.$height.'" name="webcam_movie_embed" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="'.$flashvars.'">'.
				'</embed>'.
	'</object>';
}




function _attachLinkRepair() {//временная фукнция для переделки ссылок на файлы todo на удаление
	$sql = "SELECT *
			FROM `_attach`
			WHERE LENGTH(`link_old`)
			LIMIT 1000";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $id => $r) {
		$ex = explode('/', $r['link_old']);

		$c = count($ex) - 1;
		$fname = $ex[$c];
		unset($ex[$c]);

		$path = '/home/httpd/vhosts/nyandoma.ru/httpdocs'.implode('/', $ex).'/';
		$link = '//nyandoma.ru'.implode('/', $ex).'/';

		$sql = "UPDATE `_attach`
				SET `fname`='".addslashes($fname)."',
					`path`='".addslashes($path)."',
					`link`='".addslashes($link)."',
					`link_old`=''
				WHERE `id`=".$id;
		query($sql);
	}
}


function _attachLink($attach_id) {//формирование ссылки на файл
	$sql = "SELECT *
			FROM `_attach`
			WHERE `id`=".$attach_id;
	if(!$r = query_assoc($sql))
		return 'Файл не найден';

	return
	'<div class="_attach-link">'.
		'<a href="'.$r['link'].$r['fname'].'" target="_blank">'.
			$r['oname'].
		'</a>'.
		'<span>'._attachSize($r['size']).'</span>'.
	'</div>';
}
function _attachSize($v) {//оформление размера файла в байтах, Кб, Мб
	if($v < 1000)
		return $v.'b';

	$v = round($v / 1024);
	if($v < 1000)
		return $v.'K';

	$v = round($v / 1024);
	if($v < 1000)
		return $v.'M';

	$v = round($v / 1024);
	return $v.'G';
}





function _filterCalendar($el) {//Фильтр-календарь
	$v = _spisokFilter('vv', $el, $el['num_2']);
	$v = _filterCalendarDef($v);
	$mon = substr($v, 0, 7);

	if($el['num_3'] == 6510 && !$el['num_4'])
		return _emptyMinRed('Не указано значение даты');

	return
	'<div class="_filter-calendar">'.
		'<div class="_busy"></div>'.
		'<input type="hidden" class="mon-cur" value="'.$mon.'" />'.

		'<table class="w100p">'.
			'<tr><td class="laquo" val="0">&laquo;'.
				'<td class="td-mon">'._filterCalendarMon($mon).
				'<td class="laquo" val="1">&raquo;'.
		'</table>'.

		'<div class="fc-cnt">'._filterCalendarContent($el, $mon, $v).'</div>'.
	'</div>';
}
function _filterCalendarDef($v) {//получение значения по умолчанию
	switch($v) {
		//текущий день
		case 2819: return TODAY;
		//текущая неделя
		case 2820: return _calendarWeek();
		//текущий месяц
		case 2821: return substr(TODAY, 0, 7);
	}
	return $v;
}
function _filterCalendarMon($mon) {//имя месяца и год
	$ex = explode('-', $mon);
	return _monthDef($ex[1]).' '.$ex[0];
}
function _filterCalendarContent($el, $mon, $v) {
	$unix = strtotime($mon.'-01');
	$dayCount = date('t', $unix);   //Количество дней в месяце
	$week = date('w', $unix);       //Номер первого дня недели
	if(!$week)
		$week = 7;

	$days = _filterCalendarDays($el, $mon);

	$weekNum = intval(date('W', $unix));    // Номер недели с начала месяца

	$range = _calendarWeek($mon.'-01');
	$send = '<tr'.($range == $v ? ' class="sel"' : '').'>'.
				'<td class="week-num" val="'.$range.'">'.$weekNum;

	//Вставка пустых полей, если первый день недели не понедельник
	for($n = $week; $n > 1; $n--)
		$send .= '<td>';

	for($n = 1; $n <= $dayCount; $n++) {
		$day = $mon.'-'.($n < 10 ? '0' : '').$n;
		$cur = TODAY == $day ? ' b' : '';
		$on = empty($days[$day]) ? '' : ' on';
		$old = $unix + $n * 86400 <= TODAY_UNIXTIME ? ' grey' : '';
		$sel = $day == $v ? ' sel' : '';
		$val = $on ? ' val="'.$day.'"' : '';
		$send .= '<td class="d '.$cur.$on.$old.$sel.'"'.$val.'>'.$n;
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount) {
			$range = _calendarWeek($mon.'-'.($n + 1 < 10 ? 0 : '').($n + 1));
			$send .= '<tr'.($range == $v ? ' class="sel"' : '').'>'.
						'<td class="week-num" val="'.$range.'">'.(++$weekNum);
		}
	}

	//Вставка пустых полей, если последняя неделя месяца заканчивается не воскресеньем
	if($week > 1)
		for($n = $week; $n <= 7; $n++)
			$send .= '<td>';

	return
	'<table class="w100p ">'.
		'<tr class="week-name">'.
			'<th>&nbsp;<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс'.
		$send.
	'</table>';
}
function _filterCalendarDays($el, $mon) {//отметка дней в календаре, по которым есть записи
	if(!$elem = _elemOne($el['num_1']))
		return array();
	if(!$dlg = _dialogQuery($elem['num_1']))
		return array();

	$col = 'dtime_add';

	if($el['num_3'] == 6510) {
		if(!$ELD = _elemOne($el['num_4']))
			return array();
		if(!$col = _elemCol($ELD))
			return array();
	}

	$cond = "`".$col."` LIKE ('".$mon."%')";
	if(isset($dlg['field1']['app_id']))
		$cond .= " AND `app_id`=".APP_ID;
	if(isset($dlg['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dlg['id'];
	if(isset($dlg['field1']['deleted']))
		$cond .= " AND !`deleted`";

	$sql = "SELECT DATE_FORMAT(`".$col."`,'%Y-%m-%d'),1
			FROM `"._table($dlg['table_1'])."`
			WHERE ".$cond."
			GROUP BY DATE_FORMAT(`".$col."`,'%d')";
	return query_ass($sql);
}

function _calendarFilter($data=array()) {
	$data = array(
		'upd' => empty($data['upd']), // Обновлять существующий календать? (при перемотке масяцев)
		'month' => empty($data['month']) ? strftime('%Y-%m') : $data['month'],
		'sel' => empty($data['sel']) ? '' : $data['sel'],
		'days' => empty($data['days']) ? array() : $data['days'],
		'func' => empty($data['func']) ? '' : $data['func'],
		'noweek' => empty($data['noweek']) ? 0 : 1,
		'norewind' => !empty($data['norewind'])
	);
	$ex = explode('-', $data['month']);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];
	$days = $data['days'];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$send =
	($data['upd'] ?
		'<div class="_calendarFilter">'.
			'<input type="hidden" class="func" value="'.$data['func'].'" />'.
			'<input type="hidden" class="noweek" value="'.$data['noweek'].'" />'.
			'<input type="hidden" class="selected" value="'.$data['sel'].'" />'.
		'<div class="content">'
	: '').
		'<table class="data">'.
			'<tr>'.($data['norewind'] ? '' : '<td class="ch" val="'.$back.'">&laquo;').
				'<td><a val="'.$data['month'].'"'.($data['month'] == $data['sel'] ? ' class="sel"' : '').'>'._monthDef($SHOW_MON).'</a> '.
					($data['norewind'] ? '' :
						'<a val="'.$SHOW_YEAR.'"'.($SHOW_YEAR == $data['sel'] ? ' class="sel"' : '').'>'.$SHOW_YEAR.'</a>'.
					'<td class="ch" val="'.$next.'">&raquo;').
		'</table>'.
		'<table class="month">'.
			'<tr class="week-name">'.
				($data['noweek'] ? '' :'<th>&nbsp;').
				'<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс';

	$unix = strtotime($data['month'].'-01');
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);       // Номер первого дня недели
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // Текущий день для выделения прошедших дней
	$weekNum = intval(date('W', $unix));    // Номер недели с начала месяца

	$range = _calendarWeek($data['month'].'-01');
	$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
		($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.$weekNum);
	for($n = $week; $n > 1; $n--, $send .= '<td>'); // Вставка пустых полей, если первый день недели не понедельник
	for($n = 1; $n <= $dayCount; $n++) {
		$day = $data['month'].'-'.($n < 10 ? '0' : '').$n;
		$cur = $curDay == $day ? ' cur' : '';
		$on = empty($days[$day]) ? '' : ' on';
		$old = $unix + $n * 86400 <= $curUnix ? ' old' : '';
		$sel = $day == $data['sel'] ? ' sel' : '';
		$val = $on ? ' val="'.$day.'"' : '';
		$send .= '<td class="d '.$cur.$on.$old.$sel.'"'.$val.'>'.$n;
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount) {
			$range = _calendarWeek($data['month'].'-'.($n + 1 < 10 ? 0 : '').($n + 1));
			$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
				($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.(++$weekNum));
		}
	}
	if($week > 1)
		for($n = $week; $n <= 7; $n++, $send .= '<td>'); // Вставка пустых полей, если день заканчивается не воскресеньем
	$send .= '</table>'.($data['upd'] ? '</div></div>' : '');

	return $send;
}
function _calendarDataCheck($data) {
	if(empty($data))
		return false;
	if(preg_match(REGEXP_DATE, $data) || preg_match(REGEXP_YEARMON, $data) || preg_match(REGEXP_YEAR, $data))
		return true;
	$ex = explode(':', $data);
	if(preg_match(REGEXP_DATE, $ex[0]) && preg_match(REGEXP_DATE, @$ex[1]))
		return true;
	return false;
}
function _calendarPeriod($data) {// Формирование периода для элементов массива запросившего фильтра
	$send = array(
		'period' => $data,
		'day' => '',
		'from' => '',
		'to' => ''
	);
	if(!_calendarDataCheck($data))
		return $send;
	$ex = explode(':', $data);
	if(empty($ex[1]))
		return array('day'=>$ex[0]) + $send;
	return array(
		'from' => $ex[0],
		'to' => $ex[1]
	) + $send;
}
function _calendarWeek($day=TODAY) {// Формирование периода за неделю недели
	$d = explode('-', $day);
	$month = $d[0].'-'.$d[1];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);
	if(!$week)
		$week = 7;

	$dayStart = $d[2] - $week + 1; // Номер первого дня недели
	if($dayStart < 1) {
		$back = $d[1] - 1;
		$back = !$back ? ($d[0] - 1).'-12' : $d[0].'-'.($back < 10 ? 0 : '').$back;
		$start = $back.'-'.(date('t', strtotime($back.'-01')) + $dayStart);
	} else
		$start = $month.'-'.($dayStart < 10 ? 0 : '').$dayStart;

	$dayEnd = 7 - $week + $d[2]; // Номер последнего дня недели
	if($dayEnd > $dayCount) {
		$next = $d[1] + 1;
		$next = $next > 12 ? ($d[0] + 1).'-01' : $d[0].'-'.($next < 10 ? 0 : '').$next;
		$end = $next.'-0'.($dayEnd - $dayCount);
	} else
		$end = $month.'-'.($dayEnd < 10 ? 0 : '').$dayEnd;

	return $start.':'.$end;
}
function _period($v=0, $action='get') {// Формирование периода для элементов массива запросившего фильтра
	/*
		$i: get, sql
	*/

	if(empty($v))
		$v = _calendarWeek();

	switch($action) {
		case 'get': return $v;
		case 'sql':
			$ex = explode(':', $v);
			if(empty($ex[1]))
				return " AND `dtime_add` LIKE '".$v."%'";
			return " AND `dtime_add`>='".$ex[0]." 00:00:00' AND `dtime_add`<='".$ex[1]." 23:59:59'";
		default: return '';
	}
}














function _note($el) {//заметки
	$page_id = _page('cur');
	$obj_id = _num(@$_GET['id']);
	return
	'<div class="_note" val="'.$page_id.':'.$obj_id.'">'.
		'<div class="prel">'.
			'<div class="note-ok"></div>'.
			'<div class="icon icon-ok spin"></div>'.
			'<div class="_note-txt">'.
				'<textarea placeholder="напишите заметку..." /></textarea>'.
			'</div>'.
		'</div>'.
		'<div class="_note-list">'._noteList($page_id, $obj_id).'</div>'.
	'</div>';
}
function _noteList($page_id, $obj_id) {
	$sql = "SELECT *
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`
			  AND !`deleted`
			  AND `page_id`=".$page_id."
			  AND `obj_id`=".$obj_id."
			ORDER BY `id` DESC";
	if(!$arr = query_arr($sql))
		return '';

	foreach($arr as $id => $r)
		$arr[$id]['comment'] = array();

	$sql = "SELECT *
			FROM `_note`
			WHERE `parent_id` IN ("._idsGet($arr).")
			  AND !`deleted`
			ORDER BY `id`";
	foreach(query_arr($sql) as $r)
		$arr[$r['parent_id']]['comment'][] = $r;

	$send = '';
	$n = 0;
	foreach($arr as $r) {
		$cmnt = $r['comment'] ? 'Комментарии '.count($r['comment']) : 'Комментировать';
		$comment = '';
		foreach($r['comment'] as $c)
			$comment .= _noteCommentUnit($c);
		$send .=
			'<div class="_note-u'._dn(!$n, 'line-t').'" val="'.$r['id'].'">'.
				'<div class="_note-is-show">'.
					'<table class="bs10 w100p">'.
						'<tr><td class="w35">'.
								'<img class="ava40" src="'._user($r['user_id_add'], 'src').'">'.
							'<td>'.
								'<div class="note-del icon icon-del pl fr'._tooltip('Удалить заметку', -91, 'r').'</div>'.
								'<div val="dialog_id:81,edit_id:'.$r['id'].'" class="dialog-open icon icon-edit pl fr'._tooltip('Изменить заметку', -98, 'r').'</div>'.
								'<a class="b">'._user($r['user_id_add'], 'name').'</a>'.
								'<div class="pale mt3">'.FullDataTime($r['dtime_add'], 1).'</div>'.
						'<tr>'.
							'<td colspan="2" class="fs14">'.
								'<div style="word-wrap:break-word;width:650px;">'.
									_noteLink($r['txt']).
								'</div>'.
					'</table>'.
					'<div class="_note-to-cmnt dib b over1'._dn($n).'">'.
						'<div class="icon icon-comment"></div>'.
						$cmnt.
					'</div>'.
					'<div class="_note-comment'._dn(!$n).'">'.
						$comment.
						'<table class="w100p">'.
							'<tr><td><div class="_comment-txt">'.
										'<textarea placeholder="комментировать.." /></textarea>'.
									'</div>'.
								'<td class="w35 bottom">'.
									'<div class="icon icon-empty spin ml5 mb5"></div>'.
									'<div class="comment-ok"></div>'.
						'</table>'.
					'</div>'.
				'</div>'.
				'<div class="_note-is-del">'.
					'Заметка удалена.'.
					'<a class="note-rest ml10">Восстановить</a>'.
				'</div>'.
			'</div>';
		$n++;
	}

	return $send;
}
function _noteCommentUnit($c) {//html одного комментария
	return
	'<div class="_comment-u">'.
		'<table class="_comment-is-show bs5 w100p">'.
			'<tr><td class="w35">'.
					'<img class="ava30" src="'._user($c['user_id_add'], 'src').'">'.
				'<td>'.
					'<div class="_note-icon fr mr5">'.
						'<div val="dialog_id:82,edit_id:'.$c['id'].'" class="dialog-open icon icon-edit pl"></div>'.
						'<div class="comment-del icon icon-del pl" onclick="_noteCDel(this,'.$c['id'].')"></div>'.
					'</div>'.
					'<a class="fs12">'._user($c['user_id_add'], 'name').'</a>'.
					'<div class="fs12 pale mt2">'.FullDataTime($c['dtime_add'], 1).'</div>'.
			'<tr>'.
				'<td colspan="2">'.
					'<div style="word-wrap:break-word;width:600px;">'.
						_noteLink($c['txt']).
					'</div>'.
		'</table>'.
		'<div class="_comment-is-del">'.
			'Комментарий удалён.'.
			'<a class="comment-rest ml10" onclick="_noteCRest(this,'.$c['id'].')">Восстановить</a>'.
		'</div>'.
	'</div>';
}
function _noteLink($txt) {//поиск в тексте ссылок и обёртка
	$preg_autolinks = array(
	    'pattern' => array(
	        "'[\w\+]+://[A-z0-9\.\?\+\-/_=&%#:;,]+[\w/=]+'si",
	        "'([^/])(www\.[A-z0-9\.\?\+\-/_=&%#:;,]+[\w/=]+)'si",
	    ),
	    'replacement' => array(
	        '<a href="$0" target="_blank" rel="nofollow">$0</a>',
	        '$1<a href="http://$2" target="_blank" rel="nofollow">$2</a>',
	    ));
	$search = $preg_autolinks['pattern'];
	$replace = $preg_autolinks['replacement'];

	$txt = preg_replace($search, $replace, $txt);
	return _br($txt);

}




