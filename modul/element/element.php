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

//подключение файлов-элементов
foreach(array(
			 1, 2, 3, 4, 5, 6, 7, 8, 9,10,
			11,12,   14,15,16,17,18,
			      23,         27,   29,30,
	        31,32,33,34,   36,37,   39,40,
			         44,45,
			51,52,   54,55,   57,   59,60,
			         64,            69,70,
			      73,   75,   77,78,79,80,
			      83,   85,      88,   90,
			   92,
			300
        ) as $id) {
	$file = GLOBAL_DIR.'/modul/element/element'.$id.'.php';
	if(file_exists($file))
		require_once $file;
}

//подключение элементов-графиков
foreach(array(400,401) as $id) {
	$file = GLOBAL_DIR.'/modul/element/chart/element'.$id.'.php';
	if(file_exists($file))
		require_once $file;
}



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
			if(!empty($prm['deleted']))
				return '<s>'._element('print', $el, $PARAM).'</s>';
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

		//получение колонок при копировании элемента при переносе блоков
		case 'copy_field': return array();
		//копирование содержания элемента
		case 'copy_vvv': return array();

		//формирование значения для шаблона WORD
		case 'template_docx': return DEBUG ? '[DLG-'.$el['dialog_id'].']' : '';
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


/* [13] Выбор элемента из диалога или страницы */
function _element13_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1'],      //текст для placeholder
		'num_1'   => _num($el['num_1']),//элемент в исходном диалоге [13] (если num_3=8059)
		'num_2'   => _num($el['num_2']),//разрешать выбирать только некоторые типы элементов (иначе любые)
		'txt_2'   => $el['txt_2'],      //ids диалогов разрешённых элементов [12]
		'num_3'   => _num($el['num_3']),/* где выбирать элементы:
											8058: в исходном диалоге
											8059: по элементу в исходном диалоге
											8060: на текущей странице
											8061: из диалога, данные которого получает страница
                                        */
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
function PHP12_elem_rule7($prm) {/* ---=== ЭЛЕМЕНТЫ, КОТОРЫЕ МОЖНО ВЫБИРАТЬ В НАСТРОЙКЕ ДИАЛОГА [13] ===--- */
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

/* [21] Информационный блок */
function _element21_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],  //содержание
	) + _elementStruct($el);
}
function _element21_print($el) {
	return '<div class="_info">'._br($el['txt_1']).'</div>';
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
		'width' => @$el['width'],
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
		'width' => @$el['width'],
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

	$width = 0;
	if($bl = @$el['block'])
		$width = $bl['width'];

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
			'<tr><td class="top">'._attachLink($v, $width).
				'<th class="top wsnw">'.
//					'<div class="icon icon-set mtm2 ml5 pl'._tooltip('Параметры файла', -56).'</div>'.
					'<div class="icon icon-del-red ml5 mtm2 pl'._tooltip('Отменить', -30).'</div>'.
		'</table>'.
	'</div>';
}
function _element28_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	$width = 0;
	if($bl = @$el['elp']['block'])
		$width = $bl['width'];

	return _attachLink(@$u[$col], $width);
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
		'txt_1' => $el['txt_1']       //конкретные значения, если num_1=3682 (настраиваются через PHP12_count_value)
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

	$v = _num(@$u[$col]);

	if($el['num_1'] == 3681)
		return $v;

	if(!$json = _elem40json($el['txt_1']))
		return '';

	foreach($json['ids'] as $n => $id)
		if($v == $id)
			return $json['title'][$n];

	return '';
}
function _element35_vvv($el) {
	if($el['num_1'] != 3682)
		return array();

	return json_decode($el['txt_1']);
}

/* [38] Select: выбор диалогового окна (SA) */
function _element38_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1'],      //нулевое значение
		'num_1'   => _num($el['num_1']) //начальное значение
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
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, $el['num_1'])
	));
}
function _element38_vvv() {
	return _dialogSelArray();
}

/* [46] Данные текущего пользователя */
function _element46_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//диалог пользователей, на основании которого будет выбираться значение [38]
		'txt_2'   => _num($el['txt_2']) //id значения [13]
	) + _element11_struct($el);
}
function _element46_js($el) {
	return _element11_js($el);
}
function _element46_print($el, $prm) {
	if(!APP_ID)
		return '';
	if(!$u = _user())
		return '';

	$u['dialog_id'] = 111;
	$spisok[USER_ID] = $u;
	$spisok = _spisokImage($spisok);

	$prm['unit_get'] = $spisok[USER_ID];

	return _element11_print($el, $prm);
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

/* [62] Фильтр: галочка */
function _element62_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст для галочки
		'txt_2'   => $el['txt_2'],      //фильтр настраивается через [40]
		'num_1'   => _num($el['num_1']),//id элемента, размещающего список
		'num_2'   => _num($el['num_2']),/* условие применяется:
											1439 - галочка установлена
											1440 - галочка снята
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

	$sql = "SELECT
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

/* [91] Выбор галочками */
function _element91_struct($el) {
	return array(
		'txt_1'   => $el['txt_1']  //подсказка для галочки
	) + _elementStruct($el);
}
function _element91_struct_title($el) {
	$el['title'] = '✓';
	return $el;
}
function _element91_print($el, $prm) {
	$u = $prm['unit_get'];

	return _check(array(
		'attr_id' => 'sch'.$el['id'].'_'.$u['id'],
		'value' => 0
	));
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
		'num_1'   => _num($el['num_1']),//id элемента: список, на который воздействует фильтр [13]
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

	$sql = "SELECT IFNULL(MAX(`sort`)+1,1)
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$sort = query_value($sql);

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`num`,
				`name`,
				`sort`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$num.",
				'Диалог ".$num."',
				".$sort.",
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
function _dialogOpenVal($dialog_id, $prm, $EL_BUT) {//получение параметров открытия диалога для кнопки или блока
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
	$send = '';
	if($EL_BUT['num_3'])
		$send .= ',get_id:'.$uid;
	if($EL_BUT['num_5'])
		$send .= ',edit_id:'.$uid;
	if($EL_BUT['num_6'])
		$send .= ',del_id:'.$uid;

	return $send;
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

	if($el['dialog_id'] == 38)
		return $dlg_id;

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

function _dialogSpisokCmp($dialog_id) {//список колонок, используемых в диалоге (для выбора колонки по умолчанию)
	if(!$DLG = _dialogQuery($dialog_id))
		return array();

	$send = array();

	foreach($DLG['cmp'] as $id => $r) {
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

function _dialogIUID($DLG, $unit_id=0) {//присвоение ID стороннего диалога (InsertUnitID)
	if(!$el = _elemOne($DLG['insert_unit_id_set_elem_id']))
		return;
	if(!$BL = $el['block'])
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	if(!$col = _elemCol($el))
		return;
	if(!$get_id = _num(@$_GET['id']))
		return;
	if(!$UDLG = _dialogQuery($BL['obj_id']))
		return;
	if(!$u = _spisokUnitQuery($UDLG, $get_id))
		return;
	if(!isset($u[$col]))
		return;

	$sql = "UPDATE "._queryFrom($UDLG)."
			SET `t1`.`".$col."`=".$unit_id."
			WHERE "._queryWhere($UDLG)."
			  AND `t1`.`id`=".$get_id;
	query($sql);
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
function PHP12_dialog_app() {//список диалоговых окон для конкретного приложения (страница 123)
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			ORDER BY `pid`,`sort`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	foreach($arr as $id => $r) {
		if(!isset($r['child']))
			$arr[$id]['child'] = array();
		if($pid = _num($r['pid']))
			if(isset($arr[$pid]))
				$arr[$pid]['child'][] = $r;
	}


	$sql = "SELECT `obj_id`,1
			FROM `_block`
			WHERE `obj_name`='dialog_del'
			  AND `obj_id` IN ("._idsGet($arr).")";
	$contentDelAss = query_ass($sql);

	return
	'<table class="_stab small w100p">'.
		'<tr>'.
			'<th class="w30">'.
//			'<th class="w35">num'.
	  (SA ? '<th class="w50">ID' : '').
			'<th>Имя диалога'.
			'<th class="w30">'.
			'<th class="w50">Список'.
			'<th class="w100">Родитель'.
			'<th class="w70">Колонки'.
			'<th class="w30">h1'.
			'<th class="w30">h2'.
			'<th class="w30">h3'.
			'<th class="w100">content<br>del'.
	'</table>'.
	PHP12_dialog_app_child($arr);
}
function PHP12_dialog_app_child($arr, $pid=0) {
	$send = '';
	foreach($arr as $id => $r) {
		if($r['pid'] != $pid)
			continue;
		$send .= PHP12_dialog_app_li($r);
		if(!empty($r['child']))
			$send .= PHP12_dialog_app_child($arr, $id);
	}

	$cls = $pid ? '' : ' class="dialog-sort"';

	return '<ol'.$cls.'>'.$send.'</ol>';
}
function PHP12_dialog_app_li($r) {
	$parent = '';
	if($parent_id = $r['dialog_id_parent'])
		$parent = _dialogParam($parent_id, 'name');
	return
	'<li id="dlg_'.$r['id'].'" class="mt1 '.(!$r['pid'] ? 'mb5' : 'mb1').'">'.
		'<table class="_stab small w100p">'.
			'<tr class="over1">'.
				'<td class="w30 r">'.
					'<div class="icon icon-move pl"></div>'.
//				'<td class="w35 r grey">'.$r['num'].
		  (SA ? '<td class="w50 pale r">'.$r['id'] : '').
				'<td class="d-name over5 curP dialog-open'._dn($r['pid'], 'b').'" val="dialog_id:'.$r['id'].'">'.$r['name'].
				'<td class="w30 r">'.
					'<div val="dialog_id:'.$r['id'].'" class="icon icon-edit pl dialog-setup'._tooltip('Редактировать диалог', -66).'</div>'.
				'<td class="w50 center">'.
					($r['spisok_on'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w100 color-sal'.($parent ? ' over1 curP dialog-open' : '').'" val="dialog_id:'.$parent_id.'">'.$parent.
				'<td class="w70 grey">'.PHP12_dialog_col($r['id']).
				'<td class="w30">'.($r['insert_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w30">'.($r['edit_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w30">'.($r['del_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w100 center'.(!empty($contentDelAss[$r['id']]) ? ' bg-dfd' : '').'">'.
					_dialogContentDelSetup($r['id']).
		'</table>';

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


function _elemColType($id='all') {//тип данных, используемый элементом _dialog:element_type
	$col_type = array(
		1 => 'txt',
		2 => 'num',
		3 => 'sum',
		4 => 'date'
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
				'title' => 'галочка снята',
				'content' => '<div class="color-ref b">галочка снята</div>'.
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

function _elemWidth($el) {//получение ширины поля, в котором расположен элемент
	if(!is_array($el))
		if($el = _num($el))
			$el = _elemOne($el);
	if(empty($el['block']))
		return 0;

	$BL = $el['block'];
	$width = $BL['width'];

	$mar = explode(' ', $el['mar']);
	$width -= $mar[1];
	$width -= $mar[3];

	$bor = explode(' ', $BL['bor']);
	$width -= $bor[1];
	$width -= $bor[3];

	return $width;
}











function PHP12_counter_v($prm) {
	if(!$u = $prm['unit_get'])
		return _empty('Отсутствуют данные записи');
	if(!$unit_id = _num($u['id']))
		return _empty('Не получен ID записи');


	$act = array(
		1 => 'Внесение',
		2 => 'Изменение',
		3 => 'Удаление'
	);

	$actColor = array(
		1 => 'bg-dfd',
		2 => 'bg-ffc',
		3 => 'bg-fcc'
	);

	$sql = "SELECT *
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID."
			  AND `unit_id`=".$unit_id."
			ORDER BY `id` DESC
			LIMIT 100";
	if(!$spisok = query_arr($sql))
		return _empty('Данных нет');


	$send = '<table class="_stab small">'.
				'<tr><th>Действие'.
					'<th>Диалог'.
					'<th>Сумма'.
					'<th>Остаток'.
					'<th>Дата внесения'.
					'<th>Менеджер'.
				'';

	foreach($spisok as $r) {
		$DLG = _dialogQuery($r['action_dialog_id']);
		$send .= '<tr>'.
					'<td class="'.$actColor[$r['action_type_id']].'">'.$act[$r['action_type_id']].
					'<td>'.$DLG['name'].
					'<td class="r">'.$r['sum'].
					'<td class="r">'.$r['balans'].
					'<td>'.$r['dtime_add'].
					'<td>'._user($r['user_id_add'], 'name').
					'';
	}

	$send .= '</table>';


	return $send;
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

	$sql = "SELECT *
			FROM `_image`
			WHERE `id` IN ("._idsGet($elem, 'element_image_id').")";
	if($img = query_arr($sql))
		foreach($elem as $id => $r) {
			$img_id = $r['element_image_id'];
			$elem[$id]['img'] = empty($img[$img_id]) ? array() : $img[$img_id];
		}

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
									'<td class="b top">'.
							  (SA ? 	'<div class="icon icon-move-y fr pl"></div>'.
								        '<div class="icon icon-edit fr pl mr3 dialog-setup" val="dialog_id:'.$el['id'].'"></div>'
							  : '').
										$el['name'].
						  ($el['img'] ? '<div class="mt5">'._imageHtml($el['img'], 300, 0, 0, 0).'</div>' : '').
							'</table>'.
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
		if(empty($r['col']))
			continue;
		if(!empty($r['hidden']))
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

		-21 => 'текущий пользователь',

		-31 => 'значение v1'
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
			'id' => -2,
			'title' => 'Совпадает с данными, которые принимает диалоговое окно',
			'content' => '<div class="b color-pay">Совпадает с данными, которые принимает диалоговое окно</div>'.
						 '<div class="fs12 grey ml10 mt3 i">Будет выбрана запись, которую принимает диалоговое окно</div>'
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





/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ RADIO для [16][17][18] ===--- */
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

	_elemOne($unit['id'], true);
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

	_BE('elem_clear');
}
function PHP12_balans_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
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
							PHP12_action_205($r).
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
function PHP12_action_205($r) {//ЭЛЕМЕНТ: открытие диалога
	if($r['dialog_id'] != 205)
		return '';

/*
	initial_id: Значение, при котором происходит действие
					-1: значение сброшено
					-2: выбрано любое значение
					id: конкретное значение
	target_ids: id диалога
	apply_id:  элемент передаёт данные записи для отображения
	effect_id: элемент передаёт данные записи для редактирования

*/

	if(!$DLG = _dialogQuery($r['target_ids']))
		return '<div class="red">не получены данные диалога ['.$r['target_ids'].']</div>';

	$initial = '-';
	switch($r['initial_id']) {
		case -1: $initial = '<b class="color-ref">значение сброшено</b>'; break;
		case -2: $initial = '<b class="color-pay">выбрано любое значение</b>'; break;
		default:
			if(!$el = _elemOne($r['element_id']))
				break;

			switch($el['dialog_id']) {
				case 18:
					foreach($el['vvv'] as $vv)
						if($vv['id'] == $r['initial_id'])
							$initial = 'выбрано <b class="color-sal">'.$vv['title'].'</b>';
					break;
				case 29:
				case 59:
					if(!$DLG = _dialogQuery($el['num_1']))
						break;
					if(!$u = _spisokUnitQuery($DLG, $r['initial_id']))
						break;
					$initial = 'выбрано <b class="color-pay">'.$u['txt_1'].'</b>';
			}

	}

	$get  = $r['apply_id']  ? '<div class="fs11 i color-ref mt2">Элемент передаёт данные записи для отображения</div>' : '';
	$edit = $r['effect_id'] ? '<div class="fs11 i color-ref mt2">Элемент передаёт данные записи для редактирования</div>' : '';

	return
	'<span class="grey">Диалог: </span> <b>'.$DLG['name'].'</b>'.
	'<br>'.
	'<span class="grey">если</span> '.$initial.
	$get.
	$edit;
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

	_BE('elem_clear');
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
	$keyAct = HISTORY_ACT.'_history_elem';
	if(!empty($dialog[$keyAct])) {
		$sql = "DELETE FROM `_element`
				WHERE `id` IN ("._idsGet($dialog[$keyAct]).")
				  AND `id` NOT IN ("._ids($ids).")";
		query($sql);
	}

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
		return _emptyMin('Истории нет.');

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
			$type = _historyAct($r['type_id']).'_history_elem';

			$msg = '';
			if(empty($dlg[$type])) {
				$msg = '<span class="red">['.$dlg['id'].'] история не настроена</span>';
			} else {
				$prm['unit_get'] = $unitArr[$r['unit_id']];
				$prm = _blockParam($prm);
				foreach($dlg[$type] as $hel)
					$msg .= _historyKit($hel, $prm);
			}

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

	$ids = '0';

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

		foreach($cond as $r) {
			$sql = "SELECT `id`
					FROM `_spisok`
					WHERE ".$r;
			if($res = query_ids($sql))
				$ids .= ','.$res;
		}
	}

	return " AND `unit_id` IN (".$unit_id.",".$ids.")";
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


function _attachLink($attach_id, $width=0) {//формирование ссылки на файл
	if(!$attach_id)
		return '';

	$sql = "SELECT *
			FROM `_attach`
			WHERE `id`=".$attach_id;
	if(!$r = query_assoc($sql))
		return 'Файл не найден';

	$sw = '';
	if($width)
		$sw = ' style="max-width:'.($width-25).'px"';

	return
	'<div class="_attach-link"'.$sw.'>'.
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











