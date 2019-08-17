<?php

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
		'num_7'   => _num($el['num_7']),//автоматическое внесение записи, если отсутствует подобное текстовое значение
		'num_8'   => _num($el['num_8']),/* порядок вывода:
                                                10086 - по дате добавления
                                                10087 - ручная сортировка
                                        */
		'num_9'   => _num($el['num_9']),//обратный порядок
		'num_10'  => _num($el['num_10'])//всегда устанавливать значение по умолчанию
	) + _elementStruct($el);
}
function _element29_js($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_3'   => _num($el['num_3']),
		'num_4'   => _num($el['num_4']),
		'num_7'   => _num($el['num_7']),
		'txt_1'   => $el['txt_1']
	) + _elementJs($el);
}
function _element29_print($el, $prm) {
	$v = $el['num_6'];
	if(!$el['num_10'])
		$v = _elemPrintV($el, $prm, $el['num_6']);
	$v = _elem29PageSel($el['num_1'], $v);
	$v = _elem29DialogSel($prm, $v);
	$v = _elem29UserSel($el, $prm, $v);

	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => $v
	));
}
function _element29_print11($el, $u) {

	$parent = '';
	$deleted = 0;

	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return '';

		if($ell['dialog_id'] == 44) {
			$ell['elp'] = $el;
			return _element('print11', $ell, $u);
		}

		if(!$col = _elemCol($ell))
			return '---';
		if(empty($u[$col]))
			return '';

		if($ell['dialog_id'] == 60) {
			$ell['elp'] = $el;
			return _element('print11', $ell, $u);
		}

		if(!is_array($u[$col]))
			if($pid = @$u['parent_id'])
				if($DLG = _dialogQuery($u['dialog_id']))
					if($unit = _spisokUnitQuery($DLG, $pid))
						if(!empty($unit[$col]))
							$parent = $unit[$col].' » ';


		if(!empty($u['deleted']))
			$deleted = 1;
		$u = $u[$col];
	}

	if($deleted)
		return '<s>'.$parent.$u.'</s>';

	return $parent.$u;
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
function _elem29UserSel($el, $prm, $v) {//возвращение ID текущего пользователя
	if($v != -21)
		return $v;
	if(!$arr = _element29_vvv($el, $prm))
		return 0;

	foreach($arr as $r)
		if($r['id'] == USER_ID)
			return USER_ID;

	return 0;
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
function _elem29defSet($dlg, $u) {//установка значения по умолчанию существующим значениям в списке
/*
	Значения будут изменены при трёх условиях:
		1. Требуется обязательный выбор значения
		2. Указано значение по умолчанию num_6
		3. Значения были нулевые
*/
	if($dlg['id'] != 29)
		return;
	if(!$u['req'])
		return;
	if(!$u['num_6'])
		return;
	if(!$col = $u['col'])
		return;

	//диалог, в котором размещается элемент
	if($u['block']['obj_name'] != 'dialog')
		return;
	$did = $u['block']['obj_id'];
	if(!$DLG = _dialogQuery($did))
		return;

	$sql = "SELECT COUNT(*)
			FROM "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG)."
			  AND !`".$col."`";
	if(!query_value($sql))
		return;

	$sql = "UPDATE "._queryFrom($DLG)."
			SET `".$col."`=".$u['num_6']."
			WHERE "._queryWhere($DLG)."
			  AND !`".$col."`";
	query($sql);
}

function _29cnn($elem_id, $v='', $sel_id=0) {//содержание Select подключённого списка
	/*
		Три варианта вывода значений:
			1. Прямое значение
			2. Значения из вложенного списка
			3. Сборный текст
		$v - быстрый поиск
		$sel_id - ID записи, которая была выбрана ранее
	*/
	if(!$EL = _elemOne($elem_id))
		return array();
	//диалог привязанного списка
	if(!$DLG = _dialogQuery($EL['num_1']))
		return array();

	//значения списка, которые будут выводится
	$spisok = _29cnnSpisok($EL, $v);

	//добавление единицы списка, которая была выбрана ранее
	if($sel_id && empty($spisok[$sel_id]))
		if($sel = _spisokUnitQuery($DLG, $sel_id))
			$spisok[$sel_id] = $sel;

	if(empty($spisok))
		return array();

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	//формирование списка по уровням
	if(!empty($EL['num_5'])) {
		$child = array();
		foreach($spisok as $id => $r)
			$child[$r['parent_id']][$id] = $r;

		$EL['v'] = $v;
		return _29cnnChild($EL, $child);
	}

	//если значения не были настроены, берётся значение по умолчанию, настроенное в диалоге
	if(empty($EL['txt_3']))
		$EL['txt_3'] = $DLG['spisok_elem_id'];

	$send = array();
	foreach($spisok as $sid => $sp) {
		$title = _29cnnTitle($EL['txt_3'], $sp);
		$u = array(
			'id' => $sid,
			'title' => $title,
			'content' => $title
		);

		if($v)
			$u['content'] = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $u['content'], 1);

		if($content = _29cnnTitle(@$EL['txt_4'], $sp, 1)) {
			if($v)
				$content = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $content, 1);
			$u['content'] = $u['content'].'<div class="grey fs12">'.$content.'</div>';
		}


		$send[] = $u;
	}

	return $send;
}
function _29cnnChild($EL, $child, $pid=0, $spisok=array(), $path='', $level=0) {//расстановка дочерних значений
	if(!$send = @$child[$pid])
		return $spisok;

	foreach($send as $id => $sp) {
		$title = _29cnnTitle($EL['txt_3'], $sp);
		$content = $title;
		if($EL['v'])
			$content = preg_replace(_regFilter($EL['v']), '<em class="fndd">\\1</em>', $content, 1);
		$u = array(
			'id' => $id,
			'title' => $path.$title,
			'content' => '<b>'.$content.'</b>'
		);
		if($level)
			$u['content'] = '<div class="ml'.($level*20).'">'.$content.'</div>';
		$spisok[] = $u;
		$spisok = _29cnnChild($EL, $child, $id, $spisok, $path.$title.' » ', $level+1);
	}

	return $spisok;
}
function _29cnnSpisok($el, $v) {//значения списка для формирования содержания
	$DLG = _dialogQuery($el['num_1']);

	//если учитываются уровни, отключается лимит списка
	$LIMIT = @$el['num_5'] ? '' : "LIMIT 50";

	$cond = _queryWhere($DLG);

	$C = array();
	if($el['dialog_id'] == 29) {
		$C[] = _29cnnCond($el['txt_3'], $v);
		$C[] = _29cnnCond($el['txt_4'], $v);
	}
	$C = array_diff($C, array(''));
	if(!empty($C))
		$cond .= " AND (".implode(' OR ', $C).")";

	$cond .= _40cond($el, $el['txt_5']);

	$DESC = $el['num_9'] ? ' DESC' : '';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE ".$cond."
			ORDER BY `"._29cnnOrder($el, $DLG)."` ".$DESC."
			".$LIMIT;

	if(!$send = query_arr($sql))
		return array();

	if($v) {
		$arr = $send;
		while($ids = _idsGet($arr, 'parent_id')) {
			$sql = "SELECT "._queryCol($DLG)."
					FROM   "._queryFrom($DLG)."
					WHERE !`t1`.`deleted`
					  AND `id` IN (".$ids.")
					ORDER BY `"._29cnnOrder($el, $DLG)."` ".$DESC."
					".$LIMIT;
			$arr = query_arr($sql);
			$send += $arr;
		}
	}

	return $send;
}
function _29cnnOrder($el, $DLG) {//порядок вывода
	switch($el['num_8']) {
		case 10086: return 'dtime_add';
		case 10087:
			if(isset($DLG['field1']['sort']))
				return 'sort';
	}
	return 'id';
}
function _29cnnCond($ids, $v) {//получение условия при быстром поиске
	if(empty($v))
		return '';
	if(!$ids = _ids($ids, 1))
		return '';
	if(count($ids) != 1)//пока только для прямых значений (без вложенных списков)
		return '';

	$last = _idsLast($ids);

	if(!$el = _elemOne($last))
		return '';
	if($el['dialog_id'] != 8)//пока только для текстового поля
		return '';
	if(!$col = $el['col'])
		return '';

	return "`".$col."` LIKE '%".addslashes($v)."%'";
}
function _29cnnTitle($ids, $sp, $content=false) {//формирование содержания для одной единицы списка
	//элементы для отображения
	if(!$ids = _ids($ids, 'arr'))
		return $content ? '' : '- значение не настроено -';

	//последний элемент для отображения
	$last = _idsLast($ids);

	if(!$EL = _elemOne($last))
		return $content ? '' : '- несуществующий элемент '.$last.' -';

	switch($EL['dialog_id']) {
		//текстовое поле
		case 8:
			$title = $sp;
			foreach($ids as $id) {
				if(!$ell = _elemOne($id))
					return $content ? '' : '- несуществующий элемент: '.$id.' -';
				$title = $title[$ell['col']];
			}
			return $title;
		//сборный текст
		case 44:
			$prm = _blockParam();
			$prm['unit_get'] = $sp;
			return _element44_print($EL, $prm);
	}

	return $content ? '' : '- незвестный тип: '.$EL['dialog_id'].' -';
}




