<?php
/*
	✓

	Каждый элемент должен пройти проверку:
		1. Визуальное отображение в блоке {print}
		2. Структура элемента {struct}
		5. Дочерние элементы или значения {vvv} - динамическое формирование
		6. Настройка ширины (в PageSetup) ['width']
		7. Наличие флага обязательного заполнения ['req']
		8. Установка фокуса ['focus']
	   10. Действия
	   11. Подсказки
	   12. Элемент [11]: вывод значения {print11}
	   13. Форматирование
	   14. Правила
*/

//подключение файлов-элементов
foreach(array(
			 1, 2, 3, 4, 5, 6, 7, 8, 9,10,
			11,12,13,14,15,16,17,18,
			21,   23,24,25,26,27,28,29,30,
	        31,32,33,34,35,36,37,38,39,40,
			      43,44,45,46,      49,
			51,52,   54,55,   57,58,59,60,
			   62,   64,   66,   68,69,70,
			71,72,73,74,75,76,77,78,79,80,
			      83,   85,86,87,88,   90,
			91,92,93,94,95,96,97,
			102,103,116,130,300
        ) as $id) {
	$file = GLOBAL_DIR.'/modul/element/element'.$id.'.php';
	if(file_exists($file))
		require_once $file;
}

//подключение элементов-графиков
foreach(array(400,401,402) as $id) {
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
			return _msgRed('['.$el['dialog_id'].'] print');

		//вывод значения на экран через [11]
		case 'print11': return _msgRed('['.$el['dialog_id'].'] print11');
			$PARAM = _blockParam();
			$PARAM['unit_get'] = $prm;
			if(!empty($prm['deleted']))
				return '<s>'._element('print', $el, $PARAM).'</s>';
			return _element('print', $el, $PARAM);

		//печать значения истории действий
		case 'history': return $prm;

		//структура элемента: колонки, поля, подсказки, действия, форматирование
		case 'struct':  return _elementStruct($el);

		//содержание элемента (ячейки таблицы, значения выпадающего списка, ...)
		case 'vvv': return array();

		//получение имени значения по id
		case 'v_get': return _msgRed('['.$el['dialog_id'].'] не настроено');

		//получение названия элемента
		case 'title':
			if(empty($el))
				return '';
			if(!empty($el['name']))
				return $el['name'];
			if(!$el['dialog_id'])
				return '';
			return '-['.$el['dialog_id'].']-type-title-no-';

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
		'parent_id' => _num($el['parent_id']),
		'block_id'  => _num($el['block_id']),
		'dialog_id' => _num($el['dialog_id']),
		'mar'       =>      $el['mar'],
		'font'      =>      $el['font'],
		'color'      =>     $el['color'],
		'size'      =>      $el['size'] ? _num($el['size']) : 13,

		'txt_1'     => $el['txt_1'],     //для истории действий: для [10]
		'txt_2'     => $el['txt_2'],     //для истории действий: ids из [11]
		'txt_7'     => $el['txt_7'],     //для истории действий: текст слева
		'txt_8'     => $el['txt_8'],     //для истории действий: текст справа
		'txt_9'     => $el['txt_9'],     //для истории действий: условия [40]
		'txt_10'    => $el['txt_10']     //todo временно: для шаблонов документов
	);

	if(!empty($el['req']))
		$send['req'] = 1;
	if(!empty($send['req']) && !empty($el['req_msg']))
		$send['req_msg'] = $el['req_msg'];

	if(!empty($el['name']))
		$send['name'] = $el['name'];
	if(!empty($el['col']))
		$send['col'] = $el['col'];

	if(!empty($el['width']))
		$send['width'] = _num($el['width']);
	if(!empty($el['width_min']))
		$send['width_min'] = _num($el['width_min']);
	if(!empty($el['width_max']))
		$send['width_max'] = _num($el['width_max']);
	if(empty($send['width']) && !empty($send['width_min']))
		$send['width'] = 0;

	if(!empty($el['noedit']))
		$send['noedit'] = 1;
	if(!empty($el['focus']))
		$send['focus'] = 1;

	if(!empty($el['hidden']))
		$send['hidden'] = 1;
	if(!empty($el['afics']))
		$send['afics'] = $el['afics'];

	//разрешать настройку стилей
	if(_elemRule($el['dialog_id'], 12))
		$send['stl'] = 1;

	//разрешать настройку условий отображения
	if(_elemRule($el['dialog_id'], 17))
		$send['eye'] = 1;

	return $send;
}
function _element($type, $el, $prm=array()) {//все манипуляции, связанные с элементом
	if(empty($el))
		return _elementType($type, $el);

	if(!is_array($el))
		$el = _elemOne($el);

	if(!$dlg_id = _num(@$el['dialog_id']))
		return _elementType($type, $el, $prm);

	//тип манипуляции добавляется в конце функции. Например: _element1_struct
	$fname = '_element'.$dlg_id.'_'.$type;
	if(function_exists($fname))
		return $fname($el, $prm);

	return _elementType($type, $el, $prm);
}


























function PHP12_elem_info($prm) {//информация об элементе [118]
	if(!$elem_id = $prm['unit_get_id'])
		return _emptyRed('Не получен id элемента.');

	$EL_VAR = _elemInfoVar($elem_id);

	$el = _elemOne($elem_id, true);

	$send = '<tr><td class="r w250">Элемент ID:<td class="b">'.$elem_id.
			'<tr><td class="r clr8 b">app_id:'.
				'<td>'.($el['app_id'] ? '<span class="b">'.$el['app_id'].'</span>' : '-');


	//Создан через диалог
	$dlgPaste = '-';
	if($dialog_id = $el['dialog_id']) {
		$dlgPaste = '['.$dialog_id.'] ';
		if(!$DLG = _dialogQuery($dialog_id))
			$dlgPaste .= '<span class="clr5">- диалога не существует</span>';
		else
			$dlgPaste .= ' '.$DLG['name'];
	}
	$send .='<tr><td class="r clr1">Создан через диалог:<td>'.$dlgPaste;

	$send .='<tr><td class="r clr1">Расположен в блоке:'.
				'<td>'.($el['block_id'] ? '<a class="dialog-open clr13" val="dialog_id:117,get_id:'.$el['block_id'].'">'.$el['block_id'].'</a>' : '-').
			'<tr><td class="r clr1">Элемент-родитель:<td>'._elemInfoLink(@$el['parent_id']);

	//Дочерние элементы
	$td = '-';
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$elem_id."
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		$ids = array();
		foreach($arr as $id => $r)
			$ids[] = _elemInfoLink($id);
		$td = implode(', ', $ids);
	}
	$send .='<tr><td class="r clr1 top">Дочерние элементы:<td>'.$td;


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
				$ids[] = _elemInfoLink($id);
		}
		if(!empty($ids))
			$td = implode(', ', $ids);
	}
	$send .='<tr><td class="r clr1 top">Используется в элементе [11]:<td>'.$td;

	//Прикреплены функции
	$td = '-';
	$sql = "SELECT `id`
			FROM `_action`
			WHERE `element_id`=".$elem_id."
			ORDER BY `id`";
	if($ids = query_ids($sql))
		$td = $ids;
	$send .='<tr><td class="r clr1">IDs прикрепленых функций:<td>'.$td;


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
	$send .='<tr><td class="r clr1">Воздействующие функции:<td>'.$td;


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
				if(!is_array($filter))
					continue;

				foreach($filter as $f) {
					$ass = _idsAss($f['elem_id']);
					if(isset($ass[$elem_id]))
						$mass[] = '<a class="dialog-open" val="dialog_id:'.$DLG['id'].',edit_id:'.$sp['id'].'">'.
									'<span class="clr11">`'.$DLG['table_name_1'].'`</span> '.
									'['.$DLG['id'].'] '.$DLG['name'].
									' - <b>id'.$sp['id'].'<b>'.
								  '</a>';
				}
			}
		}
		if(!empty($mass))
			$td = implode('<br>', $mass);
	}
	$send .='<tr><td class="r clr1 top">Используется в фильтре [40]:<td>'.$td;

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
						$mass[] = _elemInfoLink($r['id']);
				}
			}
			if($mass)
				$td = implode(', ', $mass);
		}
	}
	$send .='<tr><td class="r clr1 top">В истории действий диалогов:<td>'.($hist ? implode('<br>', $hist) : '-');
	$send .='<tr><td class="r clr1 top">IDs элементов шаблонов истории действий, в фильтрах которых используется данный элемент:<td>'.$td;
	$send .='<tr><td class="r clr1">Является колонкой по умолчанию в диалоге:<td>'.$colDef;


	//Является указателем на список в исходном диалоге в элементе [13]
	$td = '-';
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=13
			  AND `num_1`=".$elem_id."
			ORDER BY `id`";
	if($arr = query_arr($sql)) {
		foreach($arr as $r)
			$mass[] = _elemInfoLink($r['id']);
		$td = implode(', ', $mass);
	}
	$send .='<tr><td class="r clr1">Является указателем на список в исходном диалоге в элементе [13]:<td>'.$td;

	$send .='<tr><td class="r clr1">Используется в фильтре элемента [74]:<td>---';
	$send .='<tr><td class="r clr1">Был выбран в элементе [13]:<td>---';
	$send .='<tr><td class="r clr1">Является указателем на колонку родительского диалога:<td>---';

	return
	$EL_VAR.
	'<table class="bs10">'.$send.'</table>';
}
function PHP12_elem_info_vvv($prm) {
	return _num($prm['unit_get_id']);
}
function _elemInfoLink($elem_id, $empty='-') {//формирование ссылки на элемент
	if(!$elem_id)
		return $empty;

	return '<a class="dialog-open" val="dialog_id:118,get_id:'.$elem_id.'">'.$elem_id.'</a>';
}
function _elemInfoVar($elem_id) {
	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$elem_id;
	$elBase = query_assoc($sql);
	$elCashe = _elemOne($elem_id);
	$elCasheUpd = _elemOne($elem_id, true);

	return
	'<table class="_stab">'.
		'<tr><th>Base'.
			'<th>Cache'.
			'<th>Cache-upd'.
			'<th>JS'.
		'<tr><td class="top">'._pr($elBase).
			'<td class="top">'._pr($elCashe).
			'<td class="top">'._pr($elCasheUpd).
			'<td class="top js">'.
	'</table>';
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

	if(!$dialog_id = _num($dialog_id))
		return array();
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
	$act = _num(@$dialog['act']);
	while($parent_id = $dialog['dialog_id_parent']) {
		if(!$PAR = _dialogQuery($parent_id))
			break;

		//диалог может быть родительским во всех приложениях
		//в таком случае диалогом списка становится его первый последователь
		if($PAR['parent_any'])
			break;

		$dialog = $PAR;
	}
	$dialog['act'] = $act;
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
function _dialogSpisokOn($dialog_id, $block_id, $elem_id) {//диалоги, которые являются списками: insert_on=1, не дочерние
	//диалоги, которые могут быть родительскими во всех приложениях. Они не учитываются как родители
	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `parent_any`";
	$ids = query_ids($sql);

	$cond = "`insert_on`";
	$cond .= " AND `dialog_id_parent` IN (0,".$ids.")";
	$cond .= " AND !`element_group_id`";
	$cond .= " AND `table_1` NOT IN (6)";
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
				'content' => '<div class="'.($r['sa'] ? 'clr8' : 'clr11').'">['.$r['id'].'] '.$r['name'].'</div>'
			);
	}

	return $send;
}
function _dialogSpisokOnPage($block_id) {//получение массива диалогов, которые могут быть списками: insert_on=1 (размещённые на странице)
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
		if($dialog['dialog_id_parent'])
			continue;
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
			if(!$bl = _blockOne($ell['block_id']))
				return 0;
			return $bl['obj_id'];
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


	$PA = array();//диалоги, которые могут быть родительскими во всех приложениях

	//Базовые диалоги
	$dlg_base = array();
	foreach($arr as $id => $r) {
		if($r['element_group_id'])
			continue;
		if(!$r['parent_any'])
			continue;
		else
			$PA[$id] = true;
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
		if(!$r['insert_on'])
			continue;
		if($r['dialog_id_parent'] && !isset($PA[$r['dialog_id_parent']]))
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
		if($r['insert_on'])
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
		$title = 'SA: открывать по ID';
		array_unshift($dlg_base, array(
			'id' => -2,
			'title' => $title,
			'content' => '<div class="clr8 b">'.$title.'</div>'.
						 '<div class="clr1 fs12">Будет открываться сам же диалог, если выведен список диалогов</div>'
		));
		$title = 'SA: всегда создавать новый диалог';
		array_unshift($dlg_base, array(
			'id' => -1,
			'title' => $title,
			'content' => '<div class="clr8 b">'.$title.'</div>'
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
		$color = 'clr11';
	if($r['insert_on'])
		$color = 'clr13'.(!$r['app_id'] ? ' b' : '');
	if($r['sa'])
		$color = 'clr8';

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
	return
	($isSetup ?'<span class="clr11 b">Настроено.</span> ' : '').
	'<div val="dialog_id:56,dss:'.$dialog_id.'"'.
		' class="icon icon-set pl dialog-open tool"'.
		' data-tool="'.($isSetup ? 'Изменить' : 'Настроить').' содержание">'.
	'</div>';
}

function _dialogIUID($DLG, $unit_id=0) {//присвоение ID стороннего диалога (InsertUnitID)
	if(!$el = _elemOne($DLG['insert_unit_id_set_elem_id']))
		return;
	if(!$BL = _blockOne($el['block_id']))
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
			SET "._queryColReq($DLG, $col)."=".$unit_id."
			WHERE "._queryWhere($UDLG)."
			  AND "._queryCol_id($UDLG)."=".$get_id;
	query($sql);
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
   (DEBUG ? '<th class="w50">ID' : '').
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

	//в истории действий дочених диалогов галочки не ставятся
	$bgh = $parent ? ' bg6' : '';

	return
	'<li id="dlg_'.$r['id'].'" class="mt1 '.(!$r['pid'] ? 'mb5' : 'mb1').'">'.
		'<table class="_stab small w100p">'.
			'<tr class="over1">'.
				'<td class="w30 r">'.
					'<div class="icon icon-move pl"></div>'.
	   (DEBUG ? '<td class="w50 clr2 r">'.$r['id'] : '').
				'<td class="d-name over5 curP dialog-open'._dn($r['pid'], 'b').'" val="dialog_id:'.$r['id'].'">'.$r['name'].
				'<td class="w30 r">'.
					'<div val="dialog_id:'.$r['id'].'" class="icon icon-edit pl dialog-setup tool" data-tool="Редактировать диалог"></div>'.
				'<td class="w50 center">'.
					($r['insert_on'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w100 clr13'.($parent ? ' over1 curP dialog-open' : '').'" val="dialog_id:'.$parent_id.'">'.$parent.
				'<td class="w70 clr1">'.PHP12_dialog_col($r['id']).
				'<td class="w30'.$bgh.'">'.($r['insert_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w30'.$bgh.'">'.($r['edit_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
				'<td class="w30'.$bgh.'">'.($r['del_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
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
						$col = '<span class="bg-fee tool" data-tool="Отсутствует имя колонки<br>'.$dlg['name'].'">--- ['.$el['dialog_id'].']</span>';
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
			$send[$col] = '<span class="clr5 b">'.$colName.'</span>';
		elseif($col == 'name')
			$send[$col] = '<span class="clr11 b">'.$colName.'</span>';
		elseif($col == 'req' || $col == 'req_msg')
			$send[$col] = '<span class="clr8 b">'.$colName.'</span>';
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
				'<tr><td colspan="10" class="clr5">'.
						'Элемента '.$r['id'].' нет в кеше.';
			continue;
		}

		$bl = _blockOne($r['block_id']);

		$link = '';
		//ссылка на страницу, в котором расположен список
		if($bl['obj_name'] == 'page') {
			$page = _page($bl['obj_id']);
			$link = '<a href="'.URL.'&p='.$bl['obj_id'].'" class="clr11">Страница '.$bl['obj_id'].' - '.$page['name'].'</a>';
		}
		//диалог, в котором расположен список
		if($bl['obj_name'] == 'dialog') {
			$dlg = _dialogQuery($bl['obj_id']);
			$link = '<a class="dialog-open" val="dialog_id:'.$bl['obj_id'].'">Диалог '.$bl['obj_id'].' - '.$dlg['name'].'</a>';
		}

		$send .= '<tr>'.
					'<td class="r clr1">'.$r['id'].
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
		    $RULE_ASS;//правило содержит элемент

	if(!_defined('RULE_USE')) {
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
	}

	//содержит ли элемент правило
	if($dlg_id = _num($i))
		return isset($DLG_ASS[$dlg_id][$v]);

	return $RULE_USE;
}

function _elemOne($elem_id, $upd=false) {//запрос одного элемента
	if(!$elem_id = _num($elem_id))
		return array();
	if(!$upd)
		return _BE('elem_one', $elem_id);

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$elem_id;
	if(!$el = query_assoc($sql))
		return array();

	//обновление данных элемента в кеше
	$key = 'GELM';
	$global = $el['app_id'] ? 0 : 1;
	if(!_cache_isset($key, $global))
		return array();

	global $G_ELM;

	$ELM = _cache_get($key, $global);
	$el = _beElmDlg($el);
	$el = _element('struct', $el);
	$ELM[$elem_id] = $el;

	_cache_set($key, $ELM, $global);

	$el = _beElmStruct11($el);

	$G_ELM[$elem_id] = $el;

	if($el['dialog_id'])
		$el['title'] = _element('title', $el);

	return $el;
}
function _blockOne($block_id, $upd=false) {//запрос одного блока
	if(!$upd)
		return _BE('block_one', $block_id);

	//обновление данных блока в кеше
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

	$key = 'GBLK';
	$global = $bl['app_id'] ? 0 : 1;
	if(!_cache_isset($key, $global))
		return array();

	global $G_BLK;

	$BLK = _cache_get($key, $global);
	$bl = _beBlkStruct($bl);
	$BLK[$block_id] = $bl;
	_cache_set($key, $BLK, $global);

	$G_BLK[$block_id] = $bl;

	return $bl;
}
function _blockCh($block_id, $param) {//получение конкретного параметра блока
	if(!$bl = _BE('block_one', $block_id))
		return '';
	if(!isset($bl[$param]))
		return '';
	return $bl[$param];
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
function _elemColDlgId($elem_id, $oo=false) {//получение id диалога по имени колонки (для определения, вносит ли элемент данные из другого диалога)
/*
	$oo - OtherOnly: отправлять только флаг: елемент из своего диалога или нет
*/

	if(!$el = _elemOne($elem_id))
		return 0;

	//определение диалога, в котором расположен элемент
	if(!$BL = _blockOne($el['block_id']))
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
	if(!$BL = _blockOne($ell['block_id']))
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

function _elemId($dlg_id, $unit_id) {//получение id элемента на основании записи
	if(!$DLG = _dialogQuery($dlg_id))
		return 0;
	if(!$unit_id = _num($unit_id))
		return 0;

	switch($DLG['table_name_1']) {
		case '_action':
			if(!$act = _BE('action_one', $unit_id))
				return 0;
			return _num($act['element_id']);
	}

	return 0;
}
function _elemDlg($elem_id) {//получение данных диалога на основании элемента
	if(!$dlg_id = _elemDlgId($elem_id))
		return array();
	return _dialogQuery($dlg_id);
}
function _elemDlgId($elem_id_src) {//получение id диалога на основании элемента
	$elem_id = $elem_id_src;
	while($EL = _elemDlgIdEL($elem_id)) {
		switch($EL['dialog_id']) {
			case 14:
			case 23: return $EL['num_1'];
			case 88:
				$V = json_decode($EL['txt_2'], true);

				//распределение диалогов по порядковым номерам
				$spv = array();
				foreach($V['spv'] as $n => $r)
					$spv[$n] = $r['dialog_id'];

				//распределение элементов по порядковым номерам
				$elmN = array();
				foreach($V['col'] as $col)
					foreach($col['elm'] as $n => $elm_id) {
						$elmN[$elm_id] = $n;

						//если элемент является прямым дочерним элементом списка, сразу возврат его диалога
						if($elm_id == $elem_id_src)
							return  $spv[$n];
					}

				$EL_SRC = _elemDlgIdEL($elem_id_src);

				//вложенный элемент [44]
				if(!$pid = $EL_SRC['parent_id'])
					return 0;
				if(!$dlgN = _num(@$elmN[$pid]))
					return 0;
				if(empty($spv[$dlgN]))
					return 0;
				return $spv[$dlgN];
			default:
				if($block_id = $EL['block_id'])
					return _blockDlgId($block_id);
				break;
		}

		if(!$elem_id = _num(@$EL['parent_id']))
			return 0;
	}

	return 0;
}
function _elemDlgIdEL($elem_id) {//получение данных элемента
	if($EL = _elemOne($elem_id))
		return $EL;

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$elem_id;
	return query_assoc($sql);
}

function _elemAttrCmp($el) {
	return '#cmp_'.$el['id'];
}
function _elemIdsTitle($v) {//получение имён по id элементов
	if(!$ids = _ids($v, 'arr'))
		return '';

	$send = '';
	$znak = _elemIdsTitleZnak($ids[0]);
	foreach($ids as $n => $id) {
		switch($id) {
			case -21: $send = 'Текущий пользователь'; break;
			case -22: $send = 'Текущий диалог'; break;
			case -23: $send = 'Текущая запись'; break;
			default: $send .= ($n ? $znak : '')._element('title', $id);
		}
	}

	return $send;
}
function _elemIdsTitleZnak($v) {
	if(_elemIsConnect($v))//является списком
		return ' » ';
	if($v == -21)//текущий пользователь
		return ' » ';
	return ', ';
}
function _elemUids($ids, $u) {//получение значения записи по идентификаторам элементов (в основном для [11])
	if(empty($u))
		return '';
	if(!$ids = _ids($ids, 'arr'))
		return '';

	foreach($ids as $k => $id) {
		if(!$col = _elemCol($id))
			return '';
		if(!isset($u[$col]))
			return '';

		$u = $u[$col];
	}

	return is_array($u) ? $u['id'] : $u;
}
function _elemArr($ids) {//получение массива элементов по id
	if(!$ids = _ids($ids, 'arr'))
		return array();

	$send = array();
	foreach($ids as $elem_id) {
		if(!$el = _elemOne($elem_id))
			continue;
		$send[] = $el;
	}

	return $send;
}
function _elmJs($obj_name, $obj_id, $prm=array()) {//список элементов, которым требуется выполнение JS после печати
	if(!$ELM = _BE('elem_arr', $obj_name, $obj_id))
		return array();

	$send = array();
	$elmDop = array();//поиск элементов сторонних объектов (в действиях), которые потребуются
	foreach($ELM as $elem_id => $el) {
		$el['vvv'] = _element('vvv', $el, $prm);

		foreach($el['action'] as $act)
			if($act['dialog_id'] == 209)//вставка значения в блок
				$elmDop += _idsAss($act['v1']);

		$send[$elem_id] = $el;
	}

	foreach($elmDop as $elem_id => $n) {
		if(isset($send[$elem_id]))
			continue;
		$send[$elem_id] = _elemOne($elem_id);
	}

	return $send;
}
function _elemJsFocus($obj_name, $obj_id) {//id элемента, на который будует установлен фокус
	if(!$ELM = _BE('elem_arr', $obj_name, $obj_id))
		return 0;

	foreach($ELM as $el)
		if(!empty($el['focus']))
			return $el['id'];

	//если фокус установлен не был, но присутствует [7] быстрый поиск, установка фокуса на него
	foreach($ELM as $el)
		if($el['dialog_id'] == 7)
			return $el['id'];

	return 0;
}

function _elemWidth($el) {//получение ширины поля, в котором расположен элемент
	if(!is_array($el))
		if($el = _num($el))
			$el = _elemOne($el);
	if(!$BL = _blockOne($el['block_id']))
		return 0;

	$width = $BL['width'];

	$mar = explode(' ', $el['mar']);
	$width -= $mar[1];
	$width -= $mar[3];

	$bor = explode(' ', $BL['bor']);
	$width -= $bor[1];
	$width -= $bor[3];

	return $width;
}






function _elemDivAttrId($el, $prm) {//аттрибут id для DIV элемента
	if(!$bl = _blockOne($el['block_id']))
		return '';
	//attr_id не ставится в элементе шаблона в рабочей версии
	if(!$prm['blk_setup'] && $bl['obj_name'] == 'spisok')
		return '';

	return ' id="el_'.$el['id'].'"';
}
function _elemDivSize($el) {//класс - размер шрифта
	if(empty($el['size']))
		return '';
	if($el['size'] == 13)
		return '';
	return 'fs'.$el['size'];
}
function _elemDiv($elem_id, $prm=array()) {//формирование div элемента
	if(!$el = _elemOne($elem_id))
		return '';
	if(_elemAction244($el, $prm))
		return '';

	$attr_id = _elemDivAttrId($el, $prm);
	$style = _elemStyle($el, $prm);

	//блок принимает данные записи
	$bl = _blockOne($el['block_id']);
	if(!is_array($prm = _blockUnitGet($bl, $prm, true)))
		return '<div'.$attr_id.$style.'>'.$prm.'</div>';

	$txt = _elemPrint($el, $prm);

	$cls = array();
	$cls[] = _elemAction242($el, $prm);
	$cls[] = @$el['font'];
	$cls[] = _elemDivSize($el);
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormat($el, $prm, $txt);

	return
	_elemDivCol($el, $prm).
	'<div'.$attr_id.$cls.$style.'>'.$txt.'</div>';
}
function _elemDivCol($el, $prm) {
	if(empty($el['col']))
		return '';
	if(!$prm['blk_setup'])
		return '';

	return '<div class="elem-col">'.$el['col'].'</div>';
}
function _elemFormat($el, $prm, $txt) {//формат значения элемента
	$txt = _elemAction241($el, $prm, $txt); //подмена текста
	$txt = _elemAction243($el, $txt);       //Формат для чисел
	$txt = _elemAction245($el, $txt, 1);    //Формат для текста
	$txt = _spisokUnitUrl($el, $prm, $txt);
	$txt = _elemAction229Hint($el, $prm, $txt);//выплывающая подсказка
	$txt = _elemAction223($el, $prm, $txt);
	$txt = _elemLink($el, $txt);
	return $txt;
}
function _elemLink($el, $txt) {//нахождение ссылок и преобразование
	switch($el['dialog_id']) {
		case 11:
			if(!$last_id = _idsLast($el['txt_2']))
				break;
			if(!$el11 = _elemOne($last_id))
				break;

			//для видеороликов ссылка не делается
			if($el11['dialog_id'] == 76)
				break;
			if($el11['dialog_id'] == 5 && $el11['num_2'])
				break;

			return _noteLink($txt);
	}

	return $txt;
}
function _elemStyle($el, $prm) {//стили css для элемента
	$send = array();

	//отступы
	$ex = explode(' ', $el['mar']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'margin:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	//когда включена настройка ширины элементов,
	//те элементы, которые могут настраиваться, остаются, остальные скрываются
	if($prm['elm_width_change'] && !_dialogParam($el['dialog_id'], 'element_width'))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}

function _elemAttrId($el, $prm) {//аттрибут id для DIV элемента
	$attr_id = 'cmp_'.$el['id'];

	if($prm['blk_setup'])
		$attr_id .= '_edit';

	return $attr_id;
}
function _elemStyleWidth($el) {//ширина элемента
	if(!isset($el['width']))
		return '';
	if(!$width = _num($el['width']))
		return ' style="width:100%"';

	return ' style="width:'.$width.'px"';
}
function _elemPrint($el, $prm) {//формирование и отображение элемента
	//если элемент вносит данные из другого диалога - удаление данных записи, чтобы не было подстановки данных
	if($prm['unit_edit'])
		if(_elemColDlgId($el['id'], true))
			$prm['unit_edit'] = array();

	return _element('print', $el, $prm);
}
function _elemPrintV($el, $prm, $def='') {//значение записи при редактировании
	if(empty($prm['unit_edit']))
		return $def;
	if(empty($el['col']))
		return $def;
	if(!$col = _elemCol($el))
		return $def;

	//имя колонки является id элемента из родительского диалога
	if($id = _num($col)) {
		if(!$elp = _elemOne($id))
			return $def;
		if(!$col = $elp['col'])
			return $def;
	}

	$v = $prm['unit_edit'][$col];

	if(is_array($v)) {
		//идентификаторы изображений
		if($ids = @$v['ids'])
			return $ids;
		if($id = _num(@$v['id']))
			return $id;
		return $def;
	}

	//если текстовое поле и не число, возврат просто значения
	if($el['dialog_id'] == 8 && $el['num_1'] != 33)
		return  $v;

	if(is_string($v) && preg_match(REGEXP_INTEGER, $v) && preg_match(REGEXP_INTEGER, $def))
		return $v * 1;

	return $v;
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
					'title' => '<span class="dib w30 r clr2 mr5">['.$el['id'].']</span>'.$el['name'],
					'value' => _num(@$ass[$el['id']])
				)).
			'</div>';
		}
	}

	return
	'<div class="fs16 clr9">'.
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
					'<td class="fs14 '.($r['sa'] ? 'clr5 pl5' : 'clr15').'">'._br($r['name']).
			'</table>';

		$content .= '<dl id="cnt_'.$id.'" class="cnt'._dn($id == $firstId).'">';
		$n = 1;
		foreach($r['elem'] as $el)
				$content .=
					'<dd val="'.$el['id'].'">'.
						'<div class="elem-unit '.($el['sa'] ? 'clr5' : 'clr9').'" val="'.$el['id'].'">'.
							'<table class="w100p">'.
								'<tr><td class="num w25 r top pr5 clr1">'.$n++.'.'.
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

		if($EL = _elemOne($BL['elem_id']))
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
				if($dlg['is_unit_get'])
					return !$isMsg ? 10 : 'Блок диалога, принимающего данные записи.';
				return !$isMsg ? 2 : 'Блок с диалога.';
			case 'dialog_del':  return !$isMsg ? 8 : 'Блок содержания удаления записи.';
			case 'tmp43':       return !$isMsg ? 3 : 'Блок шаблона записи.';
			case 'spisok':      return !$isMsg ? 3 : 'Блок записи.';
			case 'hint':        return !$isMsg ? 18 : 'Блок подсказки.';
		}

		return !$isMsg ? 0 : '[50] Неизвестное местоположение.';
	}

	return !$isMsg ? 0 : '[50] Отсутствует исходный блок.';
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
	if(empty($prm['unit_edit']))
		return array();
	if(!$ids = _ids($prm['unit_edit']['param_ids']))
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
	if(empty($prm['unit_edit']))
		return _emptyMin('Настройка данных будет доступна<br>после выбора списка для внесения данных.');
	if(!$prm['unit_edit']['dst_spisok'])
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





// Настройка шаблона данных записи
function PHP12_tmp_setup($prm) {
	/*
		txt_2: имя объекта
		       id объекта = идентификатор, который размещает шаблон
		txt_3: максимальная ширина шаблона. Если равно нулю - получение ширины блока, в котором размещён элемент
		txt_4: сообщение о том, что настройка будет доступна после создания элемента
	*/
	$el12 = $prm['el12'];
	if(!$unit = $prm['unit_edit'])
		return '<div class="bg-ffe pad10">'._emptyMin($el12['txt_4']).'</div>';
	if(!$obj_name = _txt($el12['txt_2']))
		return _emptyRed10('Отсутствует имя объекта');

	$obj_id = $unit['id'];

	setcookie('block_level_'.$obj_name, 1, time() + 2592000, '/');
	$_COOKIE['block_level_'.$obj_name] = 1;

	//определение ширины шаблона
	if(!$width = _num($el12['txt_3']))
		$width = _blockObjWidth($obj_name, $obj_id);

	return
	'<div class="bg-ffc pad10 line-b">'.
		_blockLevelChange($obj_name, $obj_id).
	'</div>'.
	'<div class="block-content-'.$obj_name.'" style="width:'.$width.'px">'.
		_blockHtml($obj_name, $obj_id, array('blk_setup' => 1)).
	'</div>';
}
function PHP12_tmp_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$obj_name = $prm['el12']['txt_2'])
		return array();

	$obj_id = $prm['unit_edit']['id'];

	$send['jsblk'] = _BE('block_arr', $obj_name, $obj_id);
	$send['jselm'] = _elmJs($obj_name, $obj_id, $prm);

	return $send;
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
				WHERE `id` IN ("._ids($dialog[$keyAct]).")
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
	if(!$ids = $DLG[$act.'_history_elem'])
		return array();

	$send = array();
	foreach(_ids($ids, 'arr') as $elem_id) {
		if(!$el = _elemOne($elem_id))
			continue;
		$c = 0;
		if($el['txt_9']) {
			$vv = htmlspecialchars_decode($el['txt_9']);
			$vv = json_decode($vv, true);
			$c = count($vv);
		}
		$el['c'] = $c;
		$el['title'] = _element('title', $el);
		$send[] = $el;
	}
	return $send;
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
				$msg = '<span class="clr5">['.$dlg['id'].'] история не настроена</span>';
			} else {
				$prm['unit_get'] = $unitArr[$r['unit_id']];
				$prm = _blockParam($prm);
				foreach(_ids($dlg[$type], 'arr') as $elem_id)
					$msg .= _historyKit($elem_id, $prm);
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
								'<span class="dib clr2 w35 mr5">'.substr($r['dtime_add'], 11, 5).'</span>'.
							'<td>'.
				   (SA && DEBUG ? '<div val="dialog_id:'.$r['dialog_id'].',menu:2" class="icon icon-edit fr pl dialog-setup tool" data-tool="Настроить историю"></div>' : '').
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
function _historyKit($elem_id, $prm) {//составление одной сборки
	if(!$u = $prm['unit_get'])
		return _msgRed('отсутствует запись');
	if(!$el = _elemOne($elem_id))
		return _msgRed('элемента '.$elem_id.' не существует');

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
				//не равно
				case 4:
					if($r['unit_id'] == $connect_id)
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
				'<div class="fs12 ml5 clr9">'._user($user_id, 'name').'</div>'.
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
			'<tr><td class="clr1 r b">'.$r['name'].
				'<td class="clr1">'.$r['old'].
				'<td class="clr1">»'.
				'<td class="clr1">'.$r['new'];
	}

	$send .= '</table>';

	return $send;
}
function _historyUnitCond($el, $prm) {//отображение истории для конкретной записи, которую принимает страница
	if(!$el['num_8'])
		return '';
	if(!$bl = _blockOne($el['block_id']))
		return '';

	//история может быть размещёна либо на странице, либо в диалоге
	switch($bl['obj_name']) {
		case 'page':
			if(!$page = _page($bl['obj_id']))
				return " AND !`id` /* страницы ".$bl['obj_id']." не существует */";
			if(!$dialog_id = $page['dialog_id_unit_get'])
				return " AND !`id` /* страница не принимает данные записи */";
			if(!$unit_id = _num(@$_GET['id']))
				return " AND !`id` /* идентификатор записи не получен */";
			break;
		case 'dialog':
			if(!$DLG = _dialogQuery($bl['obj_id']))
				return " AND !`id` /* диалога ".$bl['obj_id']." не существует */";
			if(!$DLG['is_unit_get'])
				return " AND !`id` /* диалог не принимает данные записи */";
			if(!$unit_id = $prm['unit_get_id'])
				return " AND !`id` /* id записи не получен */";

			$DLG = _dialogParent($DLG);
			$dialog_id = $DLG['id'];
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






