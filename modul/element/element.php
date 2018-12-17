<?php
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
		return false;

	//получение элемента-кнопки для присвоения нового диалога
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=".$block_id."
			  AND `dialog_id` IN (2,59)
			LIMIT 1";
	if(!$elem = query_assoc($sql))
		return false;

	//новый диалог кнопке уже был присвоен
	if($elem['num_4'])
		return $elem['num_4'];

	$sql = "INSERT INTO `_dialog` (
				`app_id`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".USER_ID."
			)";
	$dialog_id = query_id($sql);

	$sql = "SELECT IFNULL(MAX(`num`),0)+1
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$num = query_value($sql);

	$sql = "UPDATE `_dialog`
			SET `num`=".$num.",
				`name`='Диалог ".$num."'
			WHERE `id`=".$dialog_id;
	query($sql);

	$sql = "UPDATE `_element`
			SET `num_4`=".$dialog_id."
			WHERE `id`=".$elem['id'];
	query($sql);

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

	//история действий - сбор id элементов-шаблонов
	foreach(_historyAct() as $act => $act_id) {
		$dialog[$act.'_history_tmp'] = '';          //текст шаблона для отображения в настройках
		$dialog[$act_id.'_history_elm'] = array();  //элементы, которые участвуют в шаблоне

		if(!$ids = $dialog[$act.'_history_elem'])
			continue;

		foreach(_ids($ids, 1) as $id) {
			$el = _elemOne($id);
			$title = '';
			if($el['dialog_id']) {
				$title = _elemTitle($el['id']);
				$cls = array('wsnw');
				if($el['font'])
					$cls[] = $el['font'];
				if($el['color'])
					$cls[] = $el['color'];
				$cls = implode(' ', $cls);
				$title = '<span class="'.$cls.'">'.$title.'</span>';
				$title = '['.$title.']';
			}

			$dialog[$act.'_history_tmp'] .= $el['txt_7'].$title.$el['txt_8'];
			$dialog[$act_id.'_history_elm'][] = $el;
		}
	}

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
/*
	//получение исходного блока, если редактирование значения
	if($elem_id) {
		if(!$EL = _elemOne($elem_id))
			return array();
		$block_id = $EL['block_id'];
	}
*/
	if(!$BL = _blockOne($block_id))
		return array();

	if($BL['obj_name'] != 'dialog')
		return array();

	$dialog_id = $BL['obj_id'];

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

	if(SA)
		return array_merge($dlg_base, $dlg_app_spisok, $dlg_app, $dlg_elem, $dlg_sa);

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
		if(!$col = $r['col'])
			continue;
		$send[$id] = $col.': '.$r['name'];
	}

	return $send;
}

function _dialogContentDelSetup($dialog_id) {//иконка настройки содежания удаления записи (единицы списка)
	$isSetup = _BE('block_obj', 'dialog_del', $dialog_id);
	$tooltip = _tooltip(($isSetup ? 'Изменить' : 'Настроить').' содержание', -67);
	return
	($isSetup ?'<span class="color-pay b">Настроено.</span> ' : '').
	'<div val="dialog_id:56,dss:'.$dialog_id.'"'.
		' class="icon icon-edit pl dialog-open'.$tooltip.
	'</div>';
}

function PHP12_app_export() {//экспорт / импорт текущего приложения todo не доделано
	if(!APP_ID)
		return _emptyMin10('Приложение не выбрано.');

	$app = _app();


	//количество пользователей
	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=111";
	$userCount = query_value($sql);

	//ids страниц
	$sql = "SELECT `id`
			FROM `_page`
			WHERE `app_id`=".APP_ID;
	$pageIds = query_ids($sql);

	//ids диалогов
	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	$dlgIds = query_ids($sql);

	//ids блоков в страницах
	$sql = "SELECT `id`
			FROM `_block`
			WHERE `obj_name`='page'
			  AND `obj_id` IN (".$pageIds.")";
	$blkPageIds = query_ids($sql);

	//ids блоков в диалогах
	$sql = "SELECT `id`
			FROM `_block`
			WHERE `obj_name`='dialog'
			  AND `obj_id` IN (".$dlgIds.")";
	$blkDlgIds = query_ids($sql);

	//ids блоков в диалогах-удаления
	$sql = "SELECT `id`
			FROM `_block`
			WHERE `obj_name`='dialog_del'
			  AND `obj_id` IN (".$dlgIds.")";
	$blkDlgDelIds = query_ids($sql);


	//блоки со страниц, диалогов и диалогов-удаления
	$blkIds = $blkPageIds.",".$blkDlgIds.",".$blkDlgDelIds;

	//элементы
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id`
			  AND `block_id` IN (".$blkIds.")";
	$elmIds = query_ids($sql);

	//элементы-списки
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id`
			  AND `block_id` IN (".$blkIds.")
			  AND `dialog_id` IN (14,59)";
	$elmSpisokIds = query_ids($sql);

	//количество блоков в списках
	$sql = "SELECT `id`
			FROM `_block`
			WHERE `obj_name`='spisok'
			  AND `obj_id` IN (".$elmSpisokIds.")";
	$blkSpisokIds = query_ids($sql);

	$blkCount = _ids($blkPageIds, 'count')
			  + _ids($blkDlgIds, 'count')
			  + _ids($blkDlgDelIds, 'count')
			  + _ids($blkSpisokIds, 'count');

	//количество элементов на страницах
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `block_id` IN (".$blkPageIds.")";
	$elmPageCount = $blkPageIds ? query_value($sql) : 0;

	//количество элементов в диалогах
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `block_id` IN (".$blkDlgIds.")";
	$elmDlgCount = $blkDlgIds ? query_value($sql) : 0;

	//количество элементов в диалогах-удаления
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `block_id` IN (".$blkDlgDelIds.")";
	$elmDlgDelCount = $blkDlgDelIds ? query_value($sql) : 0;

	//ids элементов в списках
	$sql = "SELECT `id`
			FROM `_element`
			WHERE `block_id` IN (".$blkSpisokIds.")";
	$elmSpIds = $blkSpisokIds ? query_ids($sql) : 0;

	$emlCount = _ids($elmIds, 'count')
			  + _ids($elmSpIds, 'count');





	//ids элементов истории действий в диалогах
	$dlgHist = array();
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID;
	foreach(query_arr($sql) as $r) {
		$dlgHist[] = $r['insert_history_elem'];
		$dlgHist[] = $r['edit_history_elem'];
		$dlgHist[] = $r['del_history_elem'];
	}
	$dlgHist = array_diff($dlgHist, array(''));
	$dlgHist = implode(',', $dlgHist);





	//элементы-значения
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `parent_id`";
	$elmValCount = query_value($sql) - _ids($dlgHist, 'count');





	//количество функций
	$sql = "SELECT COUNT(*)
			FROM `_action`
			WHERE `element_id` IN (".$elmIds.")";
	$elmFunc = query_value($sql);





	//данные в списках
	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id` IN (".$dlgIds.")";
	$spCount = query_value($sql);

	//история действий
	$sql = "SELECT COUNT(*)
			FROM `_history`
			WHERE `app_id`=".APP_ID;
	$histCount = query_value($sql);

	//изображения
	$sql = "SELECT COUNT(*)
			FROM `_image`";
	$imgCount = query_value($sql);

	//заметки
	$sql = "SELECT COUNT(*)
			FROM `_note`
			WHERE `app_id`=".APP_ID;
	$noteCount = query_value($sql);


	return
	'<div class="fs18">Приложение: <b class="fs18">'.$app['name'].'</b></div>'.

	'<div class="mt15 fs14">Структура:</div>'.

	'<table class="bs10">'.
		'<tr><td>'.
				'<table class="_stab small">'.
					'<tr><td class="grey r w200">Пользователи:<td class="center w70">'.$userCount.
					'<tr><td class="grey r">Страницы:<td class="center">'._ids($pageIds, 'count_empty').
					'<tr><td class="grey r">Диалоги:<td class="center">'._ids($dlgIds, 'count_empty').
					'<tr><td class="grey r">Вспомогательные элементы:<td class="center">'._hide0($elmValCount).
					'<tr><td class="grey r">Элементы истории действий:<td class="center">'._ids($dlgHist, 'count_empty').
					'<tr><td class="grey r">Функции:<td class="center">'._hide0($elmFunc).
				'</table>'.
			'<td class="top pl10">'.
				'<table class="_stab small">'.
					'<tr class="center">'.
						'<td>'.
						'<td class="b">все'.
						'<td>страницы'.
						'<td>диалоги'.
						'<td>диалоги<br>del'.
						'<td>списки'.
					'<tr class="center">'.
						'<td class="grey r">Блоки:'.
						'<td class="b">'._hide0($blkCount).
						'<td>'._ids($blkPageIds, 'count_empty').
						'<td>'._ids($blkDlgIds, 'count_empty').
						'<td>'._ids($blkDlgDelIds, 'count_empty').
						'<td>'._ids($blkSpisokIds, 'count_empty').
					'<tr class="center">'.
						'<td class="grey r">Элементы:'.
						'<td class="b">'._hide0($emlCount).
						'<td>'._hide0($elmPageCount).
						'<td>'._hide0($elmDlgCount).
						'<td>'._hide0($elmDlgDelCount).
						'<td>'._ids($elmSpIds, 'count_empty').
				'</table>'.
	'</table>'.


	'<div class="mt15 fs14">Данные:</div>'.
	'<table class="_stab small mt5 ml10">'.
		'<tr><td class="grey r w150">В списках:<td class="center w70">'._hide0($spCount).
		'<tr><td class="grey r">История действий:<td class="center">'._hide0($histCount).
		'<tr><td class="grey r">Изображения:<td class="center">'._hide0($imgCount).
		'<tr><td class="grey r">Заметки:<td class="center">'._hide0($noteCount).
	'</table>'.
	'';
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
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return 'Диалоговых окон нет.';

	$sql = "SELECT `obj_id`,1
			FROM `_block`
			WHERE `obj_name`='dialog_del'
			  AND `obj_id` IN ("._idsGet($arr).")";
	$contentDelAss = query_ass($sql);

	$send = '<table class="_stab small">'.
				'<tr>'.
					'<th>ID'.
					'<th>Имя диалога'.
					'<th>Список'.
					'<th>Родитель'.
					'<th>Колонки'.
					'<th>h1'.
					'<th>h2'.
					'<th>h3'.
					'<th>content<br>del';
	foreach($arr as $dialog_id => $r) {
		$parent = '';
		if($parent_id = $r['dialog_id_parent'])
			$parent = _dialogParam($parent_id, 'name');
		$send .= '<tr>'.
					'<td class="w35 r grey">'.$dialog_id.
					'<td class="over1 curP dialog-open" val="dialog_id:'.$r['id'].'">'.$r['name'].
					'<td class="center'.($r['spisok_on'] ? ' bg-dfd' : '').'">'.($r['spisok_on'] ? 'да' : '').
					'<td class="color-sal'.($parent ? ' over1 curP dialog-open' : '').'" val="dialog_id:'.$parent_id.'">'.$parent.
					'<td class="grey">'.PHP12_dialog_col($r['id']).
					'<td>'.($r['insert_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td>'.($r['edit_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td>'.($r['del_history_elem'] ? '<div class="icon icon-ok curD"></div>' : '').
					'<td class="center'.(!empty($contentDelAss[$dialog_id]) ? ' bg-dfd' : '').'">'.
						_dialogContentDelSetup($dialog_id);
	}
	$send .= '</table>';

	return $send;
}
function PHP12_dialog_col($dialog_id) {//колонки, используемые в элементе
	$send = array();
	$dub = false;//флаг повторяющейся колонки
	foreach(_BE('elem_arr', 'dialog', $dialog_id) as $el) {
		//поиск элементам, которым не назначена колонка таблицы
		if(!$col = $el['col'])
			foreach(_BE('elem_arr', 'dialog', $el['dialog_id']) as $ell)
				if($ell['col'] == 'col')
					if($el['dialog_id'] != 12) {
						$dlg = _dialogQuery($el['dialog_id']);
						$col = '<span class="bg-fee'._tooltip('Отсутствует имя колонки<br>'.$dlg['name'], 5, 'l', 1).'--- ['.$el['dialog_id'].']</span>';
						break;
					}

		if(!$col)
			continue;

		$colName = $col.': '.$el['name'];

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

function _elemOne($elem_id) {//запрос одного элемента
	return _BE('elem_one', $elem_id);
}
function _blockOne($block_id) {//запрос одного блока
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

function _elemVvv($elem_id, $prm) {//дополнительные значения, привязанные к элементу. Для рабочей версии.
	if(!$el = _elemOne($elem_id))
		return array();

	switch($el['dialog_id']) {
		//подключаемая функция
		case 12:
			$func = $el['txt_1'].'_vvv';

			if(!function_exists($func))
				return array();

			return $func($prm);

		//Radio
		case 16:
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			return query_ass($sql);

		//Select - произвольные значения
		case 17:
		//dropdown
		case 18: return _elemVvv17($elem_id);

		//Дополнительные условия к фильтру
		case 22: return PHP12_elem22_vvv($prm);

		//select - выбор списка
		case 24:
			$dialog_id = $prm['srce']['dialog_id'];
			$block_id = $prm['srce']['block_id'];
			switch($el['num_1']) {
				//диалоги, которые могут быть списками: spisok_on=1 и размещены на текущей странице
				case 960: return _dialogSpisokOnPage($block_id);
				case 961: return _dialogSpisokOnConnect($block_id);
			}
			return _dialogSpisokOn($dialog_id, $block_id, $elem_id);//все списки приложения

		//Select - выбор записи из другого списка (для связки)
		case 29:
			$sel_id = _elemPrintV($el, $prm, $el['num_6']);
			$sel_id = _elem29PageSel($el['num_1'], $sel_id);
			$sel_id = _elem29DialogSel($prm, $sel_id);
			return _29cnn($elem_id, '', $sel_id);

		//SA: select - выбор имени колонки
		case 37: return _elemVvv37($prm);

		//SA: Select - выбор диалогового окна
		case 38: return _dialogSelArray();

		//Меню переключения блоков - список пунктов для управления в рабочей версии
		case 57: return PHP12_menu_block_arr($elem_id);

		//Цвета для фона
		case 70:
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

		//Фильтр radio
		case 74:
			$sql = "/* ".__FUNCTION__.":".__LINE__." VVV ".$el['dialog_id']." */
					SELECT *
					FROM `_element`
					WHERE `parent_id`=".$elem_id."
					ORDER BY `sort`";
			return query_arr($sql);

		//Фильтр: Select - привязанный список
		case 83: return _elem102CnnList($el['txt_2']);

		//Select - выбор значения списка. Для значений по умолчанию.
		case 85:
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
			$send = _elem212ActionFormat($elem_id, $v, $send);

			return $send;
	}

	return array();
}
function _elemVvv17($elem_id) {
	$send = array();
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$elem_id."
			ORDER BY `sort`";
	foreach(query_arr($sql) as $r) {
		$u = array(
			'id' => _num($r['id']),
			'title' => $r['txt_1']
		);
		if($r['txt_2'])
			$u['content'] = $r['txt_1'].'<div class="fs12 grey ml10 mt3">'.$r['txt_2'].'</div>';
		$send[] = $u;
	}
	return $send;
}
function _elemVvv37($prm) {//select - выбор имени колонки [37]
	if(!$block = _blockOne($prm['srce']['block_id']))
		return array();
	//может производиться, только если элемент размещается в диалоге
	if($block['obj_name'] != 'dialog')
		return array();
	if(!$dlg = _dialogQuery($block['obj_id']))
		return array();

	$field = array();

	//если диалог родительский, получение колонок родителя
	if($parent_id = $dlg['dialog_id_parent']) {
		$field = _elemVvv37parent($parent_id);
		$PAR = _dialogQuery($parent_id);
		//если таблицы одинаковые, отправка только родительских колонок
		if(!$dlg['table_1'] || $dlg['table_1'] == $PAR['table_1'])
			return $field;
	}

	//выбранная колонка, если редактирование записи
	$uCol = '';
	if($u = $prm['unit_edit'])
		$uCol = $u['col'];

	$field = _elemVvv37field($dlg, 1, $uCol, $field);
//	$field = _elemVvv37field($dlg, 2, $uCol, $field);

	return $field;
}
function _elemVvv37field($dlg, $num, $uCol, $send=array()) {//колонки по каждой таблице
	if(!$dlg['table_'.$num])
		return $send;

	//получение используемых колонок
	$colUse = array();
	foreach($dlg['cmp'] as $r) {
		if(!$col = $r['col'])
			continue;
		$colUse[$col] = $r['name'] ? '<i class="color-555 ml10">('.$r['name'].')</i>' : '';
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
		'dtime_last' => 1
	);

	foreach($dlg['field'.$num] as $col => $k) {
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
function _elemVvv37parent($dlg_id) {//колонки родительского диалога
	if(!$dlg = _dialogQuery($dlg_id))
		return array();

	$send = array();
	foreach($dlg['cmp'] as $id => $cmp) {
		if(!$col = $cmp['col'])
			continue;

/*
		//выбирать можно только колонки элементов, которые вносят данные
		if($cmp['dialog_id'] != 1
		&& $cmp['dialog_id'] != 8
		&& $cmp['dialog_id'] != 10
		&& $cmp['dialog_id'] != 16
		&& $cmp['dialog_id'] != 17
		&& $cmp['dialog_id'] != 29
		&& $cmp['dialog_id'] != 59
		&& $cmp['dialog_id'] != 31
		&& $cmp['dialog_id'] != 51
		&& $cmp['dialog_id'] != 300
		) continue;
*/
		$send[] = array(
			'id' => $id,
			'title' => $dlg['name'].': '.$cmp['name'],
			'content' => $dlg['name'].': '.$cmp['name'].' <b class="pale">'.$col.'</b>',
		);
	}

	return $send;
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
	if(!$col = $el['col'])
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

function _elemButton($el, $prm) {//кнопка [2]
	/*
		txt_1 - текст кнопки
		num_1 - цвет
		num_2 - маленькая кнопка
		num_3 - передаёт данные записи
		num_4 - dialog_id, который назначен на эту кнопку
	*/
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

	return _button(array(
				'attr_id' => _elemAttrId($el, $prm),
				'name' => _br($el['txt_1']),
				'color' => $color[$el['num_1']],
				'width' => $el['width'],
				'small' => $el['num_2'],
				'class' => $prm['blk_setup'] ? 'curD' : 'dialog-open',
				'val' => _elemButtonVal($el, $prm)
			));
}
function _elemButtonVal($el, $prm) {//значения аттрибута val для кнопки
	$val = 'dialog_id:'.$el['num_4'].
			//Если кнопка новая, будет создаваться новый диалог для неё. На основании блока, в который она вставлена.
		  ',block_id:'.$el['block_id'];

	//если кнопка расположена в диалоговом окне, то указывается id этого окна как исходное
	//а также вставка исходного блока для передачи как промежуточного значения, если кнопка расположена в диалоге
	//Нужно для назначения функций (пока)
	if(!empty($el['block']))
		if($el['block']['obj_name'] == 'dialog')
			$val .= ',dss:'.$el['block']['obj_id'].
					',block_id:'.$prm['srce']['block_id'];

	if($dialog_id = $el['num_4'])
		$val .= _dialogOpenVal($dialog_id, $prm, $el['num_3']);

	return $val;
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

		case 17:
		case 18: return _elem201initCnn($send, _elemVvv17($EL['id']));

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
	if(!isset($el['col']))
		return '';
	if(!$col = $el['col'])
		return '';
	if(!$id = _num($col))
		return $col;
	if(!$ell = _elemOne($id))
		return '';

	return $ell['col'];
}

function _elemTitle($elem_id) {//имя элемента или его текст
	if(!$elem_id = _num($elem_id))
		return '';
	if(!$el = _elemOne($elem_id))
		return '';

	switch($el['dialog_id']) {
		case 2:  return $el['txt_1']; //кнопка
		case 10: return $el['txt_1']; //произвольный текст
		case 11: return _elem11title($el);
		case 32: return 'номер';
		case 33: return 'дата';
		case 30: return 'del';
		case 34: return 'edit';
		case 60: return _imageNo($el['width']);
		case 62: return 'Фильтр-галочка';
		case 67://шаблон истории действий
			$dlg = _dialogQuery($el['num_2']);
			return $dlg['history'][$el['num_1']]['tmp'];
		case 71: return 'sort';
	}

	if(_elemIsConnect($el))
		return _dialogParam($el['num_1'], 'name');

	return $el['name'];
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

function _elem11($el, $prm) {//отображение элемента, вставленного через диалог [11]
	if(!$unit = $prm['unit_get'])
		return _elem11title($el);

	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');
		//вложенное значение становится записью
		if(_elemIsConnect($ell)) {
			if(!$col = $ell['col'])
				return _msgRed('-cnn-col-yok-');
			$unit = $unit[$col];
			if(!is_array($unit)) {
				if($ell['dialog_id'] == 29)
					return $ell['txt_1'];
				return _msgRed('значение отсутствует');
			}
			continue;
		}
		return _elem11one($el, $ell, $unit);
	}

	return _msgRed('-11-yok-');
}
function _elem11one($EL, $ell, $unit) {//прямая ссылка на элемент через [11]
	switch($ell['dialog_id']) {
		//произвольный текст
		case 10: return _br($ell['txt_1']);
		//сборный текст
		case 44: return PHP12_44_print($ell, $unit);
	}

	/* --- Вывод из данных записи по колонке --- */

	//не присвоена колонка элементу 11
	if(!$col = $ell['col'])
		return _msgRed('no-11-col.'.$ell['dialog_id']);

	//получение имени колонки из элемента родительского диалога
	if($elem_id = _num($col)) {
		if(!$el = _elemOne($elem_id))
			return _msgRed('no-elp.'.$elem_id);
		if(!$col = $el['col'])
			return _msgRed('no-elp-col.'.$ell['dialog_id']);
	}

	//колонки в записи не существует
	if(!isset($unit[$col]))
		return _msgRed('no-u-val.'.$ell['dialog_id']);

	$txt = $unit[$col];

	switch($ell['dialog_id']) {
		//textarea (многострочное текстовое поле)
		case 5:

		//input:text (однострочное текстовое поле)
		case 8:
			$txt = _spisokColSearchBg($EL, $txt);
			return _br($txt);

		//Radio - произвольные значения
		case 16:
			if(!$id = _num($txt))
				return _msgRed('11.radio.empty');
			if(!$dop = _elemOne($id))
				return _msgRed('no-16-dop');

			return $dop['txt_1'];

		//сумма значений единицы списка (баланс)
		case 27:
		//количество связанного списка
		case 54:
		//сумма связанного списка
		case 55: return $txt;

		//Выбор нескольких значений галочками
		case 31:
			if(!$sel = _idsAss($txt))
				return '';

			//получение данных списка
			$DLG = _dialogQuery($ell['num_1']);
			$sql = "SELECT "._queryCol($DLG)."
					FROM   "._queryFrom($DLG)."
					WHERE  "._queryWhere($DLG)."
					ORDER BY `sort`";
			$spisok = query_arr($sql);

			$send = array();

			foreach($spisok as $r)
				if(!empty($sel[$r['num']]))
					$send[] = $r['txt_1'];

			return implode(', ', $send);

		//Календарь
		case 51:
			if($txt == '0000-00-00')
				return '-';

			return FullData($txt);

		//Изображение
		case 60:
			if(empty($txt))
				return _imageNo($EL['width']);

			return _imageHtml($txt, $EL['width'], $EL['num_7']);
	}


	return '11.'.$ell['dialog_id'].'.one';
}
function _elem11title($EL) {//имя элемента, если нет записи
	$title = '';
	foreach(_ids($EL['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');

		//вложенное значение
		if(_elemIsConnect($ell)) {
			$dlg = _dialogQuery($ell['num_1']);
			$title .= $dlg['name'].' » ';
			continue;
		}

		$title .= $ell['name'];

		switch($ell['dialog_id']) {
			//произвольный текст
			case 10: return $ell['txt_1'];
			//Изображение
			case 60: return _imageNo($EL['width']);
		}
	}

	if(!$title)
		return '11.title';

	return $title;
}

function _elemIdsTitle($v) {//получение имён по id элементов
	if(!$ids = _ids($v, 'arr'))
		return '';

	$send = '';
	$znak = _elemIsConnect($ids[0]) ? ' » ' : ', ';
	foreach($ids as $n => $id)
		$send .= ($n ? $znak : '') . _elemTitle($id);

	return $send;
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
	$sql = "SELECT
				*,
				'' `rule`
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
			if($DLG['table_name_1'] == '_action')
				return 'Выбор элементов для назначения действий'.
					   '<br>'.
					   'возможен только для страниц и диалоговых окон.'.
					   '<br>'.
					   'Вы пытаетесь назначить действие для <b>записи</b>.';
		}
		if(!$ell = _elemOne($BL['obj_id']))
			return 'Элемента, размещающего список, не существует.';

		return $ell['num_1'];
	}

	//также может происходить выбор со страницы
	if($BL['obj_name'] == 'page')
		define('OBJ_NAME_CHOOSE', 'page');

	return $BL['obj_id'];
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
	if(!$dialog_id = $DLG['dialog_id_unit_get'])
		return 'Диалог не принимает данные записи';

	return $dialog_id;
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



/* ---=== ВЫБОР БЛОКОВ [19] ===--- */
function PHP12_block_choose($prm) {
	if(!$block_id = _num($prm['srce']['block_id']))
		return _emptyMin10('Отсутствует исходный блок.');
	if(!$BL = _blockOne($block_id))
		return _emptyMin10('Блока '.$block_id.' не существует.');

	$obj_name = $BL['obj_name'];
	$obj_id = $BL['obj_id'];

	switch($obj_name) {
		case 'page':
			$title = 'Страница';
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





/* ---=== УСЛОВИЯ ДЛЯ ФИЛЬТРОВ [22] ===--- */
function PHP12_elem22($prm) {
	/*
		Используется в виде элемента, а также как подключаемая функция
		PHP12_elem22_save - сохранение значений после редактирования
		PHP12_elem22_vvv - получение значений для редактирования
		_22cond - применение в фильтре
	*/

	$src = $prm['srce'];

	if(!$dialog_id = $src['dss'])
		return _emptyMin('Отсутствует id исходного диалога');

	return
	'<script>'.
		'var EL'.$prm['el12']['id'].'_DS='.$dialog_id.';'.
	'</script>';
}
function PHP12_elem22_paste($el, $prm) {//условия были вставлены как элемент [22]
	if($prm['blk_setup'])
		return _emptyMin('Дополнительные условия к фильтру');
	if(!$elem_id = $el['num_1'])
		return _emptyMin('Не выбран элемент, указывающий на список');
	if(!$EL = _elemOne($el['num_1']))
		return _emptyMin('Элемента '.$elem_id.' не существует');
	if(!$col = $EL['col'])
		return _emptyMin('Не назначена колонка');
	if(!$u = $prm['unit_edit'])
		return _emptyMin('Настройки фильтра будут доступны после вставки элемента в блок.');
	if(!isset($u[$col]))
		return _emptyMin('Колонка в записи отсутствует');
	if(!$id = _ids($u[$col], 'first'))
		return _emptyMin('Значение в элементе ещё не выбрано');
	if(!$dialog_id = _dialogSel24($el['num_1'], $id))
		return _emptyMin('Диалог не найден');

	return
	'<script>'.
		'var EL'.$el['id'].'_DS='.$dialog_id.';'.
	'</script>';
}
function PHP12_elem22_save($cmp, $val, $unit) {//Дополнительные условия к фильтру - сохранение настроек
	/*
		$cmp  - компонент из диалога, отвечающий за настройку значений фильтра
		$val  - значения, полученные для сохранения
		$unit - элемент, размещающий фильтр, для которого происходит настройка
	*/

	if(!$parent_id = _num($unit['id']))
		return 0;

	//ID элементов, которые не должны будут удалены
	$ids = array(0);
	$update = array();

	if(is_array($val))
		foreach($val as $r) {
			if($id = _num($r['id']))
				$ids[] = $id;
			//выбранный элемент
			if(!$txt_1 = _ids($r['txt_1']))
				continue;
			//выбранное условие
			if(!$num_2 = _num($r['num_2']))
				continue;

			$txt_2 = _txt($r['txt_2']);//произвольное текстовое значение
			$num_3 = _num($r['num_3']);//значение из select
			$update[] = "(
				".$id.",
				".APP_ID.",
				".$parent_id.",
				'".$txt_1."',
				".$num_2.",
				'".addslashes($txt_2)."',
				".$num_3."
			)";
		}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".implode(',', $ids).")";
	query($sql);

	if(empty($update))
		return 0;

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`num_2`,
				`txt_2`,
				`num_3`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
				`num_2`=VALUES(`num_2`),
				`txt_2`=VALUES(`txt_2`),
				`num_3`=VALUES(`num_3`)";
	query($sql);

	return count($update);
}
function PHP12_elem22_vvv($prm) {//Дополнительные условия к фильтру - получение настроек
	if(!$u = $prm['unit_edit'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r)
		$send[] = array(
			'id' => _num($r['id']),
			'title' => _elemIdsTitle($r['txt_1']),
			'txt_1' => _ids($r['txt_1']),
			'num_2' => _num($r['num_2']),
			'txt_2' => $r['txt_2'],
			'issp' => _elemIsConnect($r['txt_1']),
			'spisok' => _29cnn($r['txt_1']),
			'num_3' => _num($r['num_3'])
		);

	return $send;
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
		return _emptyMin10('Настройка таблицы будет доступна после вставки списка в блок.');

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
/*
					`url`="._num($r['url']).",
*/
			$sql = "UPDATE `_element`
					SET `num_8`=1,
						`width`="._num($r['width']).",
						`font`='".$r['font']."',
						`color`='".$r['color']."',
						`txt_7`='".addslashes(_txt($r['txt_7']))."',
						`txt_8`='".$r['pos']."',
						`sort`=".$sort."
					WHERE `parent_id`=".$unit['id']."
					  AND `id`=".$id;
			query($sql);
		}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$unit['id']."
			  AND !`num_8`";
	query($sql);
}
function PHP12_td_setup_vvv($prm) {//получение данных ячеек таблицы
	if(!$u = $prm['unit_edit'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			  AND `num_8`
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'id' => _num($r['id']),
			'dialog_id' => _num($r['dialog_id']),
			'name' => _elemTitle($r['id']),
			'width' => _num($r['width']),
			'font' => $r['font'],
			'color' => $r['color'],
			'url' => _num($r['url']),
			'txt_7' => $r['txt_7'],
			'pos' => $r['txt_8']
		);
	}

	return $send;
}


/* ---=== НАСТРОЙКА МЕНЮ ПЕРЕКЛЮЧЕНИЯ БЛОКОВ ===--- */
function PHP12_menu_block_setup() {//используется в диалоге [57]
	return '';
}
function PHP12_menu_block_setup_save($cmp, $val, $unit) {//сохранение данных о пунктах меню
	if(!$parent_id = _num($unit['id']))
		return;

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
				".$unit['app_id'].",
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
function PHP12_menu_block_setup_vvv($prm) {//получение данных о пунктах меню для настройки
	if(!$u = $prm['unit_edit'])
		return array();

	return PHP12_menu_block_arr($u['id']);
}
function PHP12_menu_block_arr($parent_id) {//получение данных о пунктах меню
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$parent_id."
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$spisok = array();
	foreach($arr as $id => $r) {
		$spisok[] = array(
			'id' => _num($id),
			'title' => $r['txt_1'],//название пункта меню
			'blk' => $r['txt_2'],  //блоки
			'def' => _num($r['def'])
		);
	}

	return $spisok;
}



/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ RADIO для [16] ===--- */
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
			if(!$title = _txt($r['title']))
				continue;
			$update[] = "(
				".$id.",
				".$app_id.",
				".$parent_id.",
				'".addslashes($title)."',
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

	if(empty($update))
		return;

	$sql = "INSERT INTO `_element` (
				`id`,
				`app_id`,
				`parent_id`,
				`txt_1`,
				`num_1`,
				`def`,
				`sort`
			)
			VALUES ".implode(',', $update)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`),
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

	//количество настроек условий фильтра по каждому значению
	$sql = "SELECT
				`parent_id`,
				COUNT(*)
			FROM `_element`
			WHERE `parent_id` IN ("._idsGet($arr).")
			GROUP BY `parent_id`";
	$ass = query_ass($sql);

	$send = array();
	foreach($arr as $r)
		$send[] = array(
			'id' => _num($r['id']),
			'title' => $r['txt_1'],
			'def' => _num($r['def']),
			'c' => _num(@$ass[$r['id']]),
			'num_1' => _num($r['num_1'])
		);

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

	//ID элементов-значений, составляющих сборный текст
	$sql = "UPDATE `_element`
			SET `txt_2`='".$ids."'
			WHERE `id`=".$parent_id;
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

	$send = array();
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$u['id']."
			ORDER BY `sort`";
	foreach(query_arr($sql) as $r)
		$send[] = array(
			'id' => _num($r['id']),
			'dialog_id' => _num($r['dialog_id']),
			'title' => _elemTitle($r['id']),
			'spc' => _num($r['num_8'])
		);

	return $send;
}
function PHP12_44_print($el, $u) {//печать сборного текста
	if(!$ids = _ids($el['txt_2'], 'arr'))
		return $el['name'];

	$send = '';
	foreach($ids as $id) {
		$ell = _elemOne($id);
		$send .= _elemPrint($ell, _blockParam(array('unit_get'=>$u)));
		if($ell['num_8'])
			$send .= ' ';
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

	$ids = array();
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['id']))
				continue;
			$ids[] = $id;
			$spc = _num($r['minus']);
			$update[] = array(
				'id' => $id,
				'minus' => $spc
			);
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
			'minus' => _num($r['num_8']), //вычитание=1, сложение=0
			'title' => _elemTitle($r['id'])
		);
	}
	return $send;
}


/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
function PHP12_action_list($prm) {
	if(!$bs_id = _num($prm['srce']['block_id']))
		return _emptyMin('Отсутствует ID исходного блока.');
	if(!$BL = _blockOne($bs_id))
		return _emptyMin('Исходного блока id'.$bs_id.' не существует.');

	//текущий диалог для обновления списка действий после редактирования
	$dss = $prm['el12']['block']['obj_id'];

	switch($dss) {
		//действия для элемента
		case 200:
		case 220:
			$where = "`element_id`=".$BL['elem_id']; break;
		//действия для блока
		case 210: $where = "`block_id`=".$bs_id; break;
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

	//Названия действия
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
						'<span class="fs12 color-pay">'.$name.'</span>'.
					'</div>';

	}

	$revers = $r['revers'] ? '<div class="fs11 i color-555 mt2">Применяется обратное действие</div>' : '';

	return
	'<div class="b">'.$apply.' '.$target.'</div>'.
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
						'<span class="fs12 color-pay">'.$name.'</span>'.
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
		$elem[] = '<b>'._elemTitle($id).'</b>';

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

	return '<span class="grey">Элемент:</span> <b>'._elemTitle($elem_id).'</b>';
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
			".$sort.",
			".USER_ID."
		)";
	}

	$ids = implode(',', $ids);

	//удаление элементов, которые были удалены
	$sql = "DELETE FROM `_element`
			WHERE `id` IN ("._ids($dialog[HISTORY_ACT.'_history_elem']).")
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
	$dialog = _dialogQuery($dialog_id);

	$send['tmp'] = $dialog[HISTORY_ACT.'_history_tmp'];
	jsonSuccess($send);
}
function PHP12_history_setup_vvv($prm) {//получение значений для настройки истории действий
	if(!$dialog_id = _num($prm['srce']['dss']))
		return array();
	if(!$DLG = _dialogQuery($dialog_id))
		return array();
	if(!$ids = $DLG[$prm['dop']['act'].'_history_elem'])
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN (".$ids.")
			ORDER BY `sort`";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$send[] = array(
			'id' => $r['id'],
			'dialog_id' => $r['dialog_id'],
			'font' => $r['font'],
			'color' => $r['color'],
			'title' => _elemTitle($r['id']),
			'txt_7' => $r['txt_7'],
			'txt_8' => $r['txt_8']
		);
	}
	return _arrNum($send);
}

function _historyAct($i='all') {//действия истории - ассоциативный массив
	$action =  array(
		'insert' => 1,
		'edit' => 2,
		'del' => 3
	);

	if($i == 'all')
		return $action;

	if(!isset($action[$i]))
		return false;

	return $action[$i];
}
function _historyInsert($type_id, $dialog, $unit_id) {//внесение истории действий
	//история не вносится, если запись физически может удаляться из базы
	if(!isset($dialog['field1']['deleted']))
		return 0;

	$active = empty($dialog[$type_id.'_history_elm']) ? 0 : 1;

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

	$edited = array();
	foreach($unitOld as $i => $v) {
		if($unit[$i] == $v)
			continue;

		$hidden = false;//скрытые элементы в историю не попадают
		$name = '';
		foreach($dialog['cmp'] as $cmp_id => $cmp)
			if($i == $cmp['col']) {
				if($cmp['hidden']) {
					$hidden = true;
					break;
				}
				if(_elemIsConnect($cmp)) {
					$name = $cmp['name'];
					break;
				}
				$name = _elemTitle($cmp_id);
				break;
			}

		if($hidden)
			continue;

		$edited[] = array(
			'name' => $name,
			'old' => $v,
			'new' => $unit[$i]
		);
	}

	if(!$edited)
		return;

	$history_id = _historyInsert(2, $dialog, $unit['id']);

	$insert = array();
	foreach($edited as $r)
		$insert[] = "(
			".$history_id.",
			'".$r['name']."',
			'".addslashes($r['old'])."',
			'".addslashes($r['new'])."'
		)";

	$sql = "INSERT INTO `_history_edited` (
				`history_id`,
				`name`,
				`old`,
				`new`
			) VALUES ".implode(',', $insert);
	query($sql);
}
function _historySpisok($el) {//список истории действий [68]
	$sql = "SELECT *
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `active`
			  "._historyCondPageUnit($el)."
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
			foreach($dlg[$r['type_id'].'_history_elm'] as $el) {
				if($el['dialog_id']) {
					if($txt = _elemPrint($el, $prm)) {
						$cls = array('wsnw');
						if($el['font'])
							$cls[] = $el['font'];
						if($el['color'])
							$cls[] = $el['color'];
						$cls = implode(' ', $cls);
						$txt = _elemFormatHide($el, $txt);
						$txt = _elemFormatDigital($el, $txt);
						$txt = _spisokUnitUrl($el, $prm, $txt);//история действий
						$txt = '<span class="'.$cls.'">'.$txt.'</span>';
						$msg .= $el['txt_7'].$txt.$el['txt_8'];
					}
				} else
					$msg .= $el['txt_7'].$el['txt_8'];
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
function _historyCondPageUnit($el) {//отображение истории для конкретной единицы списка, которую принимает страница (связка)
	if(!$el['num_8'])
		return '';

	//проверка, чтобы список был размещён именно на странице
	if($el['block']['obj_name'] != 'page')
		return ' AND !`id`';

	//страница, на которой размещён список
	if(!$page = _page($el['block']['obj_id']))
		return ' AND !`id`';

	//id диалога, единица списка которого размещается на странице
	if(!$dialog_id_unit_get = $page['dialog_id_unit_get'])
		return ' AND !`id`';

	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`id`';

	$ids = 0;

	//получение id единиц списка, которые были связаны с текущей единицей
	$sql = "SELECT `block_id`,`col`
			FROM `_element`
			WHERE `dialog_id`=29
			  AND `num_1`=".$dialog_id_unit_get."
			  AND LENGTH(`col`)";
	if($cols = query_ass($sql)) {
		$cond = array();
		$sql = "SELECT *
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `id` IN ("._idsGet($cols, 'key').")";
		foreach(query_arr($sql) as $r)
			$cond[] = "`dialog_id`=".$r['obj_id']." AND `".$cols[$r['id']]."`=".$unit_id;

		if(!empty($cond)) {
			$sql = "SELECT `id`
					FROM `_spisok`
					WHERE ".implode(' OR ', $cond);
			$ids = query_ids($sql);
		}
	}

	return " AND `unit_id` IN (".$unit_id.",".$ids.")";
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
				".USER_ID."
			)";
	$insert_id = query_id($sql);

	_cache_clear( 'IMG_SERVER', 1);

	return $insert_id;
}
function _imageNo($width=80) {//картинка, если изображнеия нет
	return '<img src="'.APP_HTML.'/img/nofoto-s.gif" width="'.$width.'" />';
}
function _imageHtml($r, $width=80, $h=0) {//получение картинки в html-формате
	$width = $width ? $width : 80;

	$st = $width > 80 ? 'max' : 80;
	$width = $width > $r['max_x'] ? $r['max_x'] : $width;
	if($h) {
		$s = _imageResize($r['max_x'], $r['max_y'], $width, $width);
		$width = $s['x'];
		$h = $s['y'];
	}

	return
		'<img src="'._imageServer($r['server_id']).$r[$st.'_name'].'"'.
			' width="'.$width.'"'.
	  ($h ? ' height= "'.$h.'"' : '').
			' class="image-open"'.
			' val="'.$r['id'].'"'.
		' />';
}
function _imageNameCreate() {//формирование имени файла из случайных символов
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
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

function _imageSave($obj_name, $obj_id, $file_type, $file_tmp_name) {
	$im = null;
	$IMAGE_PATH = APP_PATH.'/.image/'.APP_ID;
	$server_id = _imageServer('//'.DOMAIN.APP_HTML.'/.image/'.APP_ID.'/');

	//создание директории, если отсутствует
	if(!is_dir($IMAGE_PATH))
		mkdir($IMAGE_PATH, 0777, true);

	switch($file_type) {
		case 'image/jpeg': $im = @imagecreatefromjpeg($file_tmp_name); break;
		case 'image/png': $im = @imagecreatefrompng($file_tmp_name); break;
		case 'image/gif': $im = @imagecreatefromgif($file_tmp_name); break;
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
	if($x < 100 || $y < 100)
		jsonError('Изображение слишком маленькое.<br>Используйте размер не менее 100х100 px.');

	$fileName = time().'-'._imageNameCreate();
	$NAME_MAX = $fileName.'-900.jpg';
	$NAME_80 = $fileName.'-80.jpg';

	$max = _imageImCreate($im, $x, $y, 900, 900, $IMAGE_PATH.'/'.$NAME_MAX);
	$_80 = _imageImCreate($im, $x, $y, 80, 80, $IMAGE_PATH.'/'.$NAME_80);

	$sql = "SELECT IFNULL(MAX(`sort`)+1,0)
			FROM `_image`
			WHERE !`deleted`
			  AND `obj_name`='".$obj_name."'
			  AND `obj_id`=".$obj_id;
	$sort = query_value($sql);

	$sql = "INSERT INTO `_image` (
				`server_id`,

				`max_name`,
				`max_x`,
				`max_y`,
				`max_size`,

				`80_name`,
				`80_x`,
				`80_y`,
				`80_size`,

				`obj_name`,
				`obj_id`,

				`sort`,
				`user_id_add`
			) VALUES (
				".$server_id.",

				'".$NAME_MAX."',
				".$max['x'].",
				".$max['y'].",
				".$max['size'].",

				'".$NAME_80."',
				".$_80['x'].",
				".$_80['y'].",
				".$_80['size'].",

				'".$obj_name."',
				".$obj_id.",

				".$sort.",
				".USER_ID."
		)";
	$image_id = query_id($sql);

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

function _imageShow($prm) {//просмотр изображений (вставляется в блок через [12])
	$image = 'Изображение отсутствует.';//основная картинка, на которую нажали. Выводится первой
	$spisok = '';//html-список дополнительных изображений
	$spisokJs = array();//js-список всех изображений
	$spisokIds = array();//id картинок по порядку
	if($image_id = $prm['unit_get_id']) {
		$sql = "SELECT *
				FROM `_image`
				WHERE `id`=".$image_id;
		if($im = query_assoc($sql)) {
			$image = '<img src="'._imageServer($im['server_id']).$im['max_name'].'"'.
						 ' width="'.$im['max_x'].'"'.
						 ' height="'.$im['max_y'].'"'.
						 ' />';

			$sql = "SELECT *
					FROM `_image`
					WHERE `obj_name`='".$im['obj_name']."'
					  AND `obj_id`=".$im['obj_id']."
					  AND `deleted`=".$im['deleted']."
					ORDER BY `".($im['deleted'] ? 'dtime_del' : 'sort')."`";
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
function _imageDeleted($prm) {//удалённые изображения (вставляется в блок через [12])
	return 'Требуется переделать функцию.';
	if(!$obj_id = $prm['unit_get_id'])
		return _emptyMin('Отсутствует запись, к которой прикрепляются изображения.');
	if(!$elem_id = _num($SRC['prm']['elem_id']))
		return _emptyMin('Отсутствует id элемента.');

	$sql = "SELECT *
			FROM `_image`
			WHERE `obj_name`='elem_".$elem_id."'
			  AND `obj_id`=".$obj_id."
			  AND `deleted`
			ORDER BY `dtime_del`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">Удалённых изображений нет.</div>';

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
function _imageWebcam($el) {//Веб-камера (вставляется в блок через [12])
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








function _filterCalendar($el) {//Фильтр-календарь
	if(!$v = _spisokFilter('v', $el['id'])) {
		$v = $el['num_2'];
		_spisokFilter('insert', array(
			'spisok' => $el['num_1'],
			'filter' => $el['id'],
			'v' => $v
		));
	}

	$v = _filterCalendarDef($v);
	$mon = substr($v, 0, 7);

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

	$cond = "`dtime_add` LIKE ('".$mon."%')";
	if(isset($dlg['field1']['app_id']))
		$cond .= " AND `app_id`=".APP_ID;
	if(isset($dlg['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dlg['id'];
	if(isset($dlg['field1']['deleted']))
		$cond .= " AND !`deleted`";

	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d'),1
			FROM `"._table($dlg['table_1'])."`
			WHERE ".$cond."
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
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







function _filterMenu($el) {//фильтр-меню []
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
	$v = _spisokFilter('v', $el['id']);
	if($v === false) {
		$v = 0;
		_spisokFilter('insert', array(
			'spisok' => $el['num_1'],
			'filter' => $el['id'],
			'v' => $v
		));
	}

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
							'<td colspan="2" class="fs14">'._br($r['txt']).
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
				'<td colspan="2">'._br($c['txt']).
		'</table>'.
		'<div class="_comment-is-del">'.
			'Комментарий удалён.'.
			'<a class="comment-rest ml10" onclick="_noteCRest(this,'.$c['id'].')">Восстановить</a>'.
		'</div>'.
	'</div>';
}





