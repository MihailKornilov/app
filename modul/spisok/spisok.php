<?php
function _spisokFilterCache() {//кеширование фильтров списка
	$key = 'filter_user'.USER_ID;
	if($send = _cache_get($key))
		return $send;

	$send = array(
		'spisok' => array(),//все списки с фильтрами
		'filter' => array() //ассоциативный список элемент-фильтр -> значение
	);

	$sql = "SELECT *
			FROM `_user_spisok_filter`
			WHERE `user_id`=".USER_ID;
	if($arr = query_arr($sql)) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id` IN ("._idsGet($arr,'element_id_filter').")";
		$elFilter = query_arr($sql);
		foreach($arr as $r) {
			$filter_id = $r['element_id_filter'];
			$spisok_id = $r['element_id_spisok'];
			if(empty($elFilter[$filter_id]))
				continue;
			$v = array(
				'elem' => $elFilter[$filter_id],
				'v' => $r['v'],
				'def' => $r['def']
			);
			$send['spisok'][$spisok_id][$filter_id] = $v;
			$send['filter'][$filter_id] = $v;
		}
	}

	return _cache_set($key, $send);
}
function _spisokFilter($i='all', $v=0) {//получение значений фильтров списка
	if($i == 'cache_clear')
		return _cache_clear('filter_user'.USER_ID);

	$F = _spisokFilterCache();

	//значение конкретного элемента-фильтра
	if($i == 'v') {
		if(!$v)
			return false;
		if(!isset($F['filter'][$v]))
			return false;
		return $F['filter'][$v]['v'];
	}

	//список элементов-фильтров для конкретного списка
	if($i == 'spisok') {
		if(!$v)
			return array();
		if(!isset($F['spisok'][$v]))
			return array();
		return $F['spisok'][$v];
	}

	if($i == 'page_js') {//значения фильтров в формате JS по каждому списку во всём приложении
		$send = array();
		foreach($F['spisok'] as $id => $arr)
			foreach($arr as $elid => $el)
				$send[$id][$elid] = $el['v'];
		return $send;
	}

	//внесение значение фильтра, если отсутствует
	if($i == 'insert') {
		if(!is_array($v))
			return '';
		if(empty($v))
			return '';
		if(!$spisok = _num(@$v['spisok']))
			return '';
		if(!$filter = _num(@$v['filter']))
			return '';
		$v = @$v['v'];

		$sql = "SELECT *
				FROM `_user_spisok_filter`
				WHERE `user_id`=".USER_ID."
				  AND `element_id_spisok`=".$spisok."
				  AND `element_id_filter`=".$filter;
		$id = _num(query_value($sql));

		$sql = "INSERT INTO `_user_spisok_filter` (
					`id`,
					`user_id`,
					`element_id_spisok`,
					`element_id_filter`,
					`v`,
					`def`
				) VALUES (
					".$id.",
					".USER_ID.",
					".$spisok.",
					".$filter.",
					'".addslashes(_txt($v))."',
					'".addslashes(_txt($v))."'
				) ON DUPLICATE KEY UPDATE
					`v`=VALUES(`v`)";
		query($sql);

		_spisokFilter('cache_clear');
	}

	//определение отличия значений от условий по умолчанию
	if($i == 'diff') {
		if(!$v)
			return 0;
		if(empty($F['spisok'][$v]))
			return 0;
		foreach($F['spisok'][$v] as $r)
			if($r['v'] != $r['def'])
				return 1;
		return 0;
	}

	return $F;
}

function _spisokIsSort($elem_id) {//определение, нужно ли производить сортировку этого списка (поиск элемента 71)
	if(!$spisok_el = _BE('elem_arr', 'spisok', $elem_id))
		return 0;

	foreach($spisok_el as $elem)
		if($elem['dialog_id'] == 71)
			return 1;

	return 0;
}

function _spisokCountAll($el) {//получение общего количества строк списка
	$key = 'SPISOK_COUNT_ALL'.$el['id'];

	if(defined($key))
		return constant($key);

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);

	$sql = "SELECT COUNT(*)
			FROM "._tableFrom($dialog)."
			WHERE "._spisokCond($el);
	$all = _num(query_value($sql));

	define($key, $all);

	return $all;
}
function _spisokJoinField($dialog) {//подключение колонок второго списка
	if(!$dialog['table_2'])
		return '';

	$fields = array();
	foreach($dialog['cmp'] as $cmp) {
		if($cmp['table_num'] != 2)
			continue;
		if(empty($cmp['col']))
			continue;
		$fields[$cmp['col']] = 1;
	}

	//используемые колонки из дочерних диалогов
	$sql = "SELECT `id`
			FROM `_dialog`
			WHERE `dialog_parent_id`=".$dialog['id'];
	if($ids = query_ids($sql))
		foreach(_ids($ids, 1) as $id) {
			$dlg_child = _dialogQuery($id);
			foreach($dlg_child['cmp'] as $cmp) {
				if($cmp['table_num'] != 2)
					continue;
				if(empty($cmp['col']))
					continue;
				$fields[$cmp['col']] = 1;
			}
		}

	$send = '';
	foreach($fields as $col => $r)
		$send .= ',`t2`.`'.$col.'`';

	//вставка колонки `dialog_id` из второй таблицы, если отсутствует в первой
	if(!isset($dialog['field1']['dialog_id']))
		if(isset($dialog['field2']['dialog_id']))
			$send .= ',`t2`.`dialog_id`';

	return $send;
}

function _spisokElemCount($r) {//формирование элемента с содержанием количества списка для вывода на страницу
	if(!$elem_id = $r['num_1'])
		return 'Список не указан.';
	if(!$elem = _elemOne($elem_id))
		return 'Элемента, содержащего список, не существует.';

	//если результат нулевой, выводится сообщение из элемента, который размещает список
	if(!$all = _spisokCountAll($elem))
		return $elem['txt_1'];

	return
	_end($all, $r['txt_1'], $r['txt_3'], $r['txt_5']).
	' '.
	$all.
	' '.
	_end($all, $r['txt_2'], $r['txt_4'], $r['txt_6']);
}
function _spisokInclude_($spisok, $CMP) {//вложенные списки
	foreach($CMP as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
		//должен является вложенным списком
		if($cmp['dialog_id'] != 29 && $cmp['dialog_id'] != 59)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		//выборка будет производиться только по нужным строкам списка
		if(!$ids = _idsGet($spisok, $col))
			continue;

		//получение данных из вложенного списка
		$incDialog = _dialogQuery($cmp['num_1']);

		$cond = "`t1`.`id` IN (".$ids.")";
/*
		if(isset($field['deleted']))
			$cond .= " AND !`t1`.`deleted`";
		if(isset($field['app_id']))
			$cond .= " AND `t1`.`app_id`=".APP_ID;
		if(isset($field['dialog_id']))
			$cond .= " AND `t1`.`dialog_id`=".$cmp['num_1'];
*/
		$sql = "SELECT `t1`.*"._spisokJoinField($incDialog)."
				FROM "._tableFrom($incDialog)."
				WHERE ".$cond;
		if(!$arr = query_arr($sql))
			continue;

		//идентификаторы будут заменены на массив с данными единицы списка
		foreach($spisok as $id => $r) {
			$connect_id = $r[$col];
			if(empty($arr[$connect_id]))
				continue;
			$spisok[$id][$col] = $arr[$connect_id];
		}
	}

	return $spisok;
}
function _spisokInclude($spisok) {//вложенные списки
	if(empty($spisok))
		return array();
	
	//проверка наличия колонки dialog_id в содержании списка
	$key = key($spisok);
	$sp0 = $spisok[$key];
	if(!isset($sp0['dialog_id']))
		return $spisok;
	
	//сбор ID диалогов
	$DLG_IDS = array();
	foreach($spisok as $r)
		if($r['dialog_id'])
			$DLG_IDS[$r['dialog_id']] = 1;
	
	if(empty($DLG_IDS))
		return $spisok;

	foreach($DLG_IDS as $dlg_id => $i) {
		$dlg = _dialogQuery($dlg_id);
		$CMP = $dlg['cmp'];
		foreach($CMP as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
			//должен является вложенным списком
			if($cmp['dialog_id'] != 29 && $cmp['dialog_id'] != 59)
				continue;

			//должно быть присвоено имя колонки
			if(!$col = $cmp['col'])
				continue;

			//выборка будет производиться только по нужным строкам списка
			if(!$ids = _idsGet($spisok, $col))
				continue;

			//получение данных из вложенного списка
			$incDialog = _dialogQuery($cmp['num_1']);

			$cond = "`t1`.`id` IN (".$ids.")";
			$sql = "SELECT `t1`.*"._spisokJoinField($incDialog)."
					FROM "._tableFrom($incDialog)."
					WHERE ".$cond;
			if(!$arr = query_arr($sql))
				continue;

			//идентификаторы будут заменены на массив с данными единицы списка
			foreach($spisok as $id => $r)
				if($dlg_id == $r['dialog_id']) {
					$connect_id = $r[$col];
					if(empty($arr[$connect_id]))
						continue;
					$spisok[$id][$col] = $arr[$connect_id];
				}
		}
	}

	return $spisok;
}
function _spisokImage($spisok, $CMP) {//вставка картинок
	foreach($CMP as $cmp_id => $cmp) {//поиск компонента диалога с изображениями
		//должен является компонентом "загрузка изображений"
		if($cmp['dialog_id'] != 60)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		foreach($spisok as $id => $r)
			$spisok[$id][$col] = 'no img';

		$sql = "SELECT *
				FROM `_image`
				WHERE `obj_name`='_spisok'
				  AND `obj_id` IN ("._idsGet($spisok).")
				  AND !`sort`";
		if($arr = query_arr($sql))
			foreach($arr as$r)
				$spisok[$r['obj_id']][$col] = _imageHtml($r, 80, 1);
	}

	return $spisok;
}
function _spisok14($ELEM, $next=0) {//список-шаблон
	/*
        num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		num_2 - длина (количество строк, выводимых за один раз)
		txt_1 - сообщение пустого запроса
		num_3 - порядок:
					0 - автоматически
					2318 - по дате добавления
					2319 - сотрировка (на основании поля sort)
		num_8 - показывать только те значения, которые принимает текущая страница

		настройка шаблона через функцию PHP12_spisok14_setup
	*/

	$DLG = _dialogQuery($ELEM['num_1']);

	$limit = $ELEM['num_2'];

	if(!$all = _spisokCountAll($ELEM))
		return '<div class="_empty min">'._br($ELEM['txt_1']).'</div>';

	$IS_SORT = _spisokIsSort($ELEM['id']);

	$order = "`t1`.`id` DESC";
	if($IS_SORT || $ELEM['num_3'] == 2319)
		$order = "`sort`";

	//получение данных списка
	$sql = "SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE "._spisokCond($ELEM)."
			ORDER BY ".$order."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);
	//вставка картинок
	$spisok = _spisokImage($spisok, $DLG['cmp']);

	if(!$BLK = _BE('block_arr', 'spisok', $ELEM['id']))
		return '<div class="_empty"><span class="fs15 red">Шаблон единицы списка не настроен.</span></div>';

	//получение элементов, расставленных находящихся в блоках
	$ELM = _BE('elem_arr', 'spisok', $ELEM['id']);

	//ширина единицы списка с учётом отступов
	$ex = explode(' ', $ELEM['mar']);
	$width = floor(($ELEM['block']['width'] - $ex[1] - $ex[3]) / 10) * 10;

	$send = '';
	foreach($spisok as $sp) {
		$child = array();
		foreach($BLK as $id => $r) {
			$r['elem'] = array();
			if($r['elem_id']) {
				$elem = $ELM[$r['elem_id']];
				$elem['block'] = $r;
				$r['elem'] = $elem;
			}

			$child[$r['parent_id']][$id] = $r;
		}

		$block = _blockArrChild($child);
		$send .=
			'<div class="sp-unit" val="'.$sp['id'].'">'.
				_blockLevel($block, $width, 0, 0, 1, $sp).
			'</div>';
	}

	if($limit * ($next + 1) < $all) {
		$count_next = $all - $limit * ($next + 1);
		if($count_next > $limit)
			$count_next = $limit;
		$send .=
			'<div class="over5" onclick="_spisok14Next($(this),'.$ELEM['id'].','.($next + 1).')">'.
				'<tt class="db center curP fs14 blue pad10">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>'.
			'</div>';
	}

	if($IS_SORT)
		$send .= '<script>_spisokSort("'.$ELEM['attr_el'].'")</script>';

	return $send;
}
function _spisok23($ELEM, $next=0) {//вывод списка в виде таблицы
	/*
        num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		num_2 - длина (количество строк, выводимых за один раз)
		txt_1 - сообщение пустого запроса
		num_3 - узкие строки таблицы
		num_4 - подсвечивать строку при наведении мыши
		num_5 - показывать имена колонок
		num_6 - возможность сортировки строк таблицы (если установлена, длина списка становится 1000)
		num_7 - уровни сортировки (1,2,3)
		num_8 - показывать только те значения, которые принимает текущая страница

		настройка шаблона через функцию PHP12_spisok_td_setting

		Свойства ячеек:
			num_8:      ячейка активна
			width:      ширина колонки
			font:       выделение: b, i, u
			color:      цвет текста
			url_access: отображение иконки для настройки ссылки
			url:        текст в колонке является ссылкой
			txt_7:      TH-заголовок колонки
			pos:        txt_8: позиция по горизонтали (l, center, r)
	*/

	//диалог, через который вносятся данные списка
	if(!$dialog_id = $ELEM['num_1'])
		return '<div class="_empty"><span class="fs15 red">Не указан список для вывода данных.</span></div>';
	if(!$DLG = _dialogQuery($dialog_id))
		return '<div class="_empty"><span class="fs15 red">Списка <b>'.$dialog_id.'</b> не существует.</span></div>';

	if(PAS)
		return
		'<div class="_empty">'.
			'Список-таблица <b class="fs14">'.$DLG['name'].'</b>'.
		'</div>';

	$limit = $ELEM['num_2'];

	//если включена сортировка, количество максимальное
	if($ELEM['num_6'])
		$limit = 1000;

	if(!$all = _spisokCountAll($ELEM))
		return '<div class="_empty min">'._br($ELEM['txt_1']).'</div>';

	$order = "`t1`.`id` DESC";
	if($ELEM['num_6'] || _spisokIsSort($ELEM['block_id']))
		$order = "`sort`";

	//получение данных списка
	$sql = "SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE "._spisokCond($ELEM)."
			ORDER BY ".$order."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);
	//вставка картинок
	$spisok = _spisokImage($spisok, $DLG['cmp']);

	//получение настроек колонок таблицы
	$sql = "SELECT *
			FROM `_element`
			WHERE !`block_id`
			  AND `parent_id`=".$ELEM['id']."
			  AND `num_8`
			ORDER BY `sort`";
	if(!$tabCol = query_arr($sql))
		return '<div class="_empty"><span class="fs15 red">Таблица не настроена.</span></div>';

	$MASS = array();
	foreach($spisok as $sp) {
		$TR = '<tr'.($ELEM['num_4'] ? ' class="over1"' : '').'>';
		foreach($tabCol as $td) {
			$txt = _elemUnit($td, $sp);

			$cls = array();
			switch($td['dialog_id']) {
				case 30: //иконка удаления
				case 34: //иконка редактирования
				case 71: //иконка сортировки
					$cls[] = 'pad0';
			}

			$cls[] = $td['font'];
			$cls[] = $td['color'];
			$cls[] = $td['txt_8'];//pos - позиция
			$cls[] = _elemFormatColor($txt, $td, $td['color']);
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);
			$cls = $cls ? ' class="'.$cls.'"' : '';

//			$txt = _spisokColSearchBg($txt, $el, $cmp_id);
			$txt = _spisokUnitUrl($td, $sp, $txt);
			$txt = _elemFormat($txt, $td);

			$TR .= '<td'.$cls.' style="width:'.$td['width'].'px">'.$txt;
		}
		$MASS[$sp['id']] = $TR;
	}

	//tr догрузки списка
	if(!$ELEM['num_6'] && $limit * ($next + 1) < $all) {
		$count_next = $all - $limit * ($next + 1);
		if($count_next > $limit)
			$count_next = $limit;
		$MASS[] =
			'<tr class="over5 curP center blue" onclick="_spisok23next($(this),'.$ELEM['id'].','.($next + 1).')">'.
				'<td colspan="20">'.
					'<tt class="db '.($ELEM['num_3'] ? 'fs13 pt3 pb3' : 'fs14 pad5').'">'.
						'Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').
					'</tt>';
	}

	//открытие и закрытие таблицы
	$TABLE_BEGIN = '<table class="_stab'._dn(!$ELEM['num_3'], 'small').'">';
	$TABLE_END = '</table>';

	$BEGIN = !$next && !$ELEM['num_6'] ? $TABLE_BEGIN : '';
	$END = !$next && !$ELEM['num_6'] ? $TABLE_END : '';

	//включено условие сортировки
	if($ELEM['num_6']) {
		if($ELEM['num_7'] > 1) {
			$child = array();
			foreach($spisok as $id => $r)
				$child[$r['parent_id']][$id] = $r;
			$TR = _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child);
		} else {
			$TR = '';
			foreach($MASS as $id => $sp)
				$TR .=
					'<li class="mt1" id="sp_'.$id.'">'.
						$TABLE_BEGIN.$sp.$TABLE_END.
					'</li>';
			$TR = '<ol>'.$TR.'</ol>';
		}
	} else {
		//отображение названий колонок
		$TH = '';
		if(!$next && $ELEM['num_5']) {
			$TH .= '<tr>';
			foreach($tabCol as $tr)
				$TH .= '<th>'.$tr['txt_7'];
		}
		$TR = $TH.implode('', $MASS);
	}

	return $BEGIN.$TR.$END;
}
function _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child, $parent_id=0) {//формирование табличного списка по уровням
	if(!$arr = @$child[$parent_id])
		return '';

	$send = '';
	foreach($arr as $id => $r)
		$send .=
			'<li class="mt1" id="sp_'.$id.'">'.
				$TABLE_BEGIN.$MASS[$id].$TABLE_END.
				(!empty($child[$id]) ? _spisok23Child($TABLE_BEGIN, $TABLE_END, $MASS, $child, $id) : '').
			'</li>';
	return
		'<ol>'.$send.'</ol>';
}

function _spisokUnitQuery($dialog, $unit_id) {//получение данных единицы списка
	if(!$unit_id)
		return array();

	if($parent_id = $dialog['dialog_parent_id'])
		if(!$dialog = _dialogQuery($parent_id))
			return array();

	if(!$dialog['table_1'])
		return array();

	$cond = "`t1`.`id`=".$unit_id;
	$cond .= _spisokCondDef($dialog['id']);
	$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
			FROM "._tableFrom($dialog)."
			WHERE ".$cond;
	return query_assoc($sql);
}
function _spisokUnitNum($u) {//порядковый номер - значение единицы списка
	if(empty($u['id']))
		return 'номер';

	if(empty($u['num']))
		return $u['id'];

	return $u['num'];
}
function _spisokUnitData($el, $unit) {//дата и время - значение единицы списка [33]
	if(empty($unit) || empty($unit['dtime_add']))
		return _elemTitle($el['id']);

	$dtime = $unit['dtime_add'];

	if(!preg_match(REGEXP_DATE, $dtime))
		return 'некорректный формат даты';

	$ex = explode(' ', $dtime);
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
function _spisokUnitUser($el, $u) {//значение единицы списка - имя пользователя
	if(empty($u))
		return $el['name'];

	if(empty($u['user_id_add']))
		return 'no user';

	return _user($u['user_id_add'], 'name');
}

function _spisokUnitUrl($el, $unit, $txt) {//обёртка значения в ссылку
	if(!$el['url'])//оборачивать не нужно
		return $txt;
	if(empty($unit['id']))//отсутствует единица списка
		return $txt;

	if($el['url'] != 3) {//указана конкретная страница
		$unit_id = $unit['id'];
		$page = _page($el['url']);
		if($page['spisok_id'] != $unit['dialog_id'])
			if($el['dialog_id'] == 11) {
				if(!$ids = _ids($el['txt_2'], 1))
					return $txt;
				if(!$EL = _elemOne($ids[0]))
					return $txt;
				if(!$col = $EL['col'])
					return $txt;
				$unit_id = is_array($unit[$col]) ? $unit[$col]['id'] : $unit[$col];
			}
		return '<a href="'.URL.'&p='.$el['url'].'&id='.$unit_id.'" class="inhr">'.$txt.'</a>';
	}

	if(!$dlg = _elem_11_dialog($el))
		return $txt;

	//ссылка на страницу, если это список страниц
	if(_table($dlg['table_1']) == '_page')
		return '<a href="'.URL.'&p='.$unit['id'].'" class="inhr">'.$txt.'</a>';

	if(!$page_id = _page('spisok_id', $dlg['id']))
		return $txt;

	return '<a href="'.URL.'&p='.$page_id.'&id='.$unit['id'].'" class="inhr">'.$txt.'</a>';
}
function _spisokColSearchBg($el, $txt) {//подсветка значения колонки при текстовом (быстром) поиске
	$element_id_spisok = 0;

	//список-шаблон
	if(!empty($el['block']))
		if($el['block']['obj_name'] == 'spisok')
			$element_id_spisok = $el['block']['obj_id'];

	//список-таблица
	if($el['parent_id']) {
		if(!$ell = _elemOne($el['parent_id']))
			return $txt;
		if($ell['dialog_id'] != 23)
			return $txt;
		$element_id_spisok = $el['parent_id'];
	}

	if(!$element_id_spisok)
		return $txt;

	$search = false;
	$v = '';

	//поиск элемента-фильтра-поиска
	foreach(_spisokFilter('spisok', $element_id_spisok) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
		}

	if(!$search)
		return $txt;
	if(!$v)
		return $txt;
	if(!$cmp_id = _num($el['txt_2']))
		return $txt;

	//ассоциативный массив колонок, по которым производится поиск
	$colIds = _idsAss($search['txt_2']);
	//если по данной колонке поиск разрешён, то выделение цветом найденные символы
	if(!isset($colIds[$cmp_id]))
		return $txt;

	$txt = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $txt, 1);

	return $txt;
}

function _spisokCondDef($dialog_id) {//условия по умолчанию
	$key = 'TABLE_COND_'.$dialog_id;

	if(defined($key))
		return constant($key);

	$dialog = _dialogQuery($dialog_id);
	$field1 = $dialog['field1'];
	$field2 = $dialog['field2'];

	$cond = '';
	if(isset($field1['deleted']))
		$cond .= " AND !`t1`.`deleted`";
	if(isset($field1['app_id']))
		$cond .= " AND `t1`.`app_id` IN (0,".APP_ID.")";
	if(isset($field1['dialog_id']) && $dialog['table_name_1'] != '_element')
		$cond .= " AND `t1`.`dialog_id`=".$dialog_id;

	if(isset($field2['deleted']))
		$cond .= " AND !`t2`.`deleted`";
	if(isset($field2['app_id']))
		$cond .= " AND `t2`.`app_id` IN (0,".APP_ID.")";
	if(isset($field2['dialog_id']))
		$cond .= " AND `t2`.`dialog_id`=".$dialog_id;

	define($key, $cond);

	return $cond;
}
function _spisokCond($el) {//формирование строки с условиями поиска
	//$el - элемент, который размещает список. 14 или 23.
	//диалог, через который вносятся данные списка

	$cond = "`t1`.`id`";
	$cond .= _spisokCondDef($el['num_1']);
	$cond .= _spisokCondPageUnit($el);
	$cond .= _spisokCond7($el);
	$cond .= _spisokCond62($el);
	$cond .= _spisokCond77($el);
	$cond .= _spisokCond78($el);
	$cond .= _spisokCond83($el);

	return $cond;
}
function _spisokCond7($el) {//значения фильтра-поиска для списка
	$search = false;
	$v = '';

	//поиск элемента-фильтра-поиска
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
			break;
		}

	if(!$search)
		return '';
	if(!$v)
		return '';

	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($search['txt_2'], 1))
		return '';

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		$arr[] = "`t1`.`".$cmp[$cmp_id]['col']."` LIKE '%".addslashes($v)."%'";
	}

	if(!$arr)
		return '';

	return " AND (".implode($arr, ' OR ').")";
}
function _spisokCondPageUnit($el) {//отображения значений, которые принимает текущая страница
	if(!$el['num_8'])//настройки нет
		return '';
	if($el['block']['obj_name'] != 'page')//проверка, чтобы список был размещён именно на странице
		return ' AND !`t1`.`id`';
	if(!$page = _page($el['block']['obj_id']))//страница, на которой размещён список
		return ' AND !`t1`.`id`';
	if(!$spisok_id = $page['spisok_id'])//id диалога, единица списка которого размещается на странице
		return ' AND !`t1`.`id`';

	$cmp = false;
	foreach(_dialogParam($el['num_1'], 'cmp') as $r) {
		if($r['dialog_id'] != 29)
			continue;
		if($r['num_1'] != $spisok_id)
			continue;
		$cmp = $r;
	}

	if(!$cmp)
		return ' AND !`t1`.`id`';

	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`t1`.`id`';

	return " AND `t1`.`".$cmp['col']."`=".$unit_id;
}
function _spisokCond62($el) {//фильтр-галочка
	$send = '';

	//поиск элемента-фильтра-галочки
	foreach(_spisokFilter('spisok', $el['id']) as $F) {
		$filter = $F['elem'];

		if($filter['dialog_id'] != 62)
			continue;

		$v = $F['v'];

		//условие срабатывает, если 1439: установлена, 1440 - НЕ установлена
		if($filter['num_1'] == 1439 && !$v)
			continue;
		if($filter['num_1'] == 1440 && $v)
			continue;

		//условия, формирующие фильтр
		$sql = "SELECT *
				FROM `_element`
				WHERE `parent_id`=".$filter['id'];
		if(!$cond = query_arr($sql))
			continue;

		//колонки, по которым будет производиться фильтр
		$sql = "SELECT `id`,`col`
				FROM `_element`
				WHERE `id` IN ("._idsGet($cond, 'txt_2').")";
		if(!$elCol = query_ass($sql))
			continue;

		/*
			 1: отсутствует
			 2: присутствует
			 3: равно
			 4: не равно
			 5: больше
			 6: больше или равно
			 7: меньше
			 8: меньше или равно
			 9: содержит
			10: не содержит
		*/

		foreach($cond as $r) {
			if(!$col = $elCol[$r['txt_2']])
				continue;
			$val = addslashes($r['txt_8']);
			switch($r['num_8']) {
				case 1: $send.= " AND !`t1`.`".$col."`"; break;
				case 2: $send.= " AND `t1`.`".$col."`"; break;
				case 3: $send.= " AND `t1`.`".$col."`='".$val."'"; break;
				case 4: $send.= " AND `t1`.`".$col."`!='".$val."'"; break;
				case 5: $send.= " AND `t1`.`".$col."`>'".$val."'"; break;
				case 6: $send.= " AND `t1`.`".$col."`>='".$val."'"; break;
				case 7: $send.= " AND `t1`.`".$col."`<'".$val."'"; break;
				case 8: $send.= " AND `t1`.`".$col."`<='".$val."'"; break;
				case 9: $send.= " AND `t1`.`".$col."` LIKE '%".$val."%'"; break;
				case 10:$send.= " AND `t1`.`".$col."` NOT LIKE '%".$val."%'"; break;
			}
		}
	}

	return $send;
}
function _spisokCond77($el) {//фильтр-календарь
	$filter = false;
	$v = '';

	//поиск элемента-фильтра-календаря
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 77) {
			$filter = true;
			$v = $r['v'];
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return ' AND !`id`';

	$ex = explode(':', $v);

	if(empty($ex[1]))
		return " AND `dtime_add` LIKE '".$v."%'";

	return " AND `dtime_add`>='".$ex[0]." 00:00:00' AND `dtime_add`<='".$ex[1]." 23:59:59'";
}
function _spisokCond78($el) {//фильтр-меню
	$filter = false;
	$v = '';

	//поиск элемента-фильтра-меню
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 78) {
			$filter = $r['elem'];
			$v = $r['v'];
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return '';

	if(!$elem_id = $filter['num_2'])//id элемента, содержащего значения
		return '';
	if(!$ell = _elemOne($elem_id))//элемент, размещающий список
		return '';
	if(!$ids = _ids($ell['txt_2'], 1))//значения, составляющие содержание фильтра
		return '';
	if(!$el0_id = $ids[0])//id элемента, на который указывает значение
		return '';
	if(!$el0 = _elemOne($el0_id))//сам элемент
		return '';
	if(!$col = $el0['col'])//колонка, которая участвует в фильтре
		return '';

	//если значение родительское, добавление дочерних ids
	$c = count($ids) - 1;
	$elem_id = $ids[$c];

	if(!$EL = _elemOne($elem_id))//значение отсутствует
		return '';
	if(!$BL = $EL['block'])//нет блока
		return '';
	if($BL['obj_name'] != 'dialog')//блок не из диалога
		return '';
	if(!$dialog_id = $BL['obj_id'])//нет ID диалога
		return '';
	if(!$dialog = _dialogQuery($dialog_id))//нет диалога
		return '';

	if(isset($dialog['field1']['parent_id'])) {
		$sql = "SELECT `id`
				FROM `"._table($dialog['table_1'])."`
				WHERE `parent_id`=".$v;
		if($ids = query_ids($sql))
			$v .= ','.$ids;
	}

	return " AND `".$col."` IN (".$v.")";
}
function _spisokCond83($el) {//фильтр-select
	$filter = false;
	$v = 0;

	//поиск элемента-фильтра-select
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 83) {
			$filter = $r['elem'];
			$v = _num($r['v']);
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return '';
	if(!$elem_ids = _ids($filter['txt_2'], 1))
		return '';

	$elem_id = $elem_ids[0];

	if(!$ell = _elemOne($elem_id))
		return '';
	if(!$col = $ell['col'])
		return '';

	return " AND `".$col."`=".$v;
}

function _29cnn($elem_id, $v='', $sel_id=0) {//содержание Select подключённого списка
	/*
		Три варианта вывода значений:
			1. Прямое значение
			2. Значения из вложенного списка
			3. Сборный текст
		$v - быстрый поиск
		$sel_id - единица списка, которая была выбрана ранее
	*/
	if(!$EL = _elemOne($elem_id))
		return array();
	//диалог привязанного списка
	if(!$DLG = _dialogQuery($EL['num_1']))
		return array();
	//значения списка, которые будут выводится
	if(!$spisok = _29cnnSpisok($EL, $v))
		return array();

	//добавление единицы списка, которая была выбрана ранее
	if($sel_id && empty($spisok[$sel_id]))
		$spisok[$sel_id] = _spisokUnitQuery($DLG, $sel_id);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	$send = array();

	foreach($spisok as $sid => $sp) {
		$title = _29cnnTitle($EL['txt_3'], $sp);
		$u = array(
			'id' => $sid,
			'title' => $title,
			'content' => $title
		);
		if($content = _29cnnTitle($EL['txt_4'], $sp, 1))
			$u['content'] = $title.'<div class="grey fs12">'.$content.'</div>';
		if($v)
			$u['content'] = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}
function _29cnnSpisok($el, $v) {//значения списка для формирования содержания
	$DLG = _dialogQuery($el['num_1']);

	//учитываются уровни (отключается лимит списка)
	$sort = $el['num_5'];
	$field = $DLG['field1'];

	$cond = "`t1`.`id`";
	$cond .= _spisokCondDef($DLG['id']);

	$C = array();
	$C[] = _29cnnCond($el['txt_3'], $v);
	$C[] = _29cnnCond($el['txt_4'], $v);
	$C = array_diff($C, array(''));
	if(!empty($C))
		$cond .= " AND (".implode(' OR ', $C).")";

	$sql = "SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE ".$cond."
			ORDER BY ".(isset($field['sort']) ? "`sort`," : '')."`id` DESC
			"._dn($sort, "LIMIT 50");
	return query_arr($sql);
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
	if(!$ids = _ids($ids, 1))
		return $content ? '' : '- значение не настроено -';

	//последний элемент для отображения
	$last = _idsLast($ids);

	if(!$el = _elemOne($last))
		return $content ? '' : '- несуществующий элемент '.$last.' -';

	switch($el['dialog_id']) {
		//текстовое поле
		case 8:
			$title = $sp;
			foreach($ids as $id) {
				if(!$el = _elemOne($id))
					return $content ? '' : '- несуществующий элемент: '.$id.' -';
				$title = $title[$el['col']];
			}
			return $title;
		//сборный текст
		case 44: return PHP12_44_print($el['id'], $sp);
	}

	return $content ? '' : '- незвестный тип: '.$el['dialog_id'].' -';
}
/*
function _spisok29connect($cmp_id, $v='', $sel_id=0) {//получение данных списка для связки (dialog_id:29)
	if(!$cmp_id)
		return array();
	if(!$cmp = _elemOne($cmp_id))
		return array();
	if(!$cmp['num_1'])
		return array();
	if(!$dialog = _dialogQuery($cmp['num_1']))
		return array();

	$S = array();//данные с результатами для содержания select

	//элементы, содержащие id элементов, настраивающих содержание select
	$S[] = _spisok29connectGet($cmp['txt_3'], $v);
	$S[] = _spisok29connectGet($cmp['txt_4'], $v);

	$cond = array();
	foreach($S as $n => $r)
		$cond[] = $r['cond'];
	$cond = array_diff($cond, array(''));
	$cond = $cond ? " AND (".implode(' OR ', $cond).")" : '';

	$field = $dialog['field1'];

	$cond = "`t1`.`id`".$cond;
	$cond .= _spisokCondDef($dialog['id']);

	$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
			FROM "._tableFrom($dialog)."
			WHERE ".$cond."
			ORDER BY ".(isset($field['sort']) ? "`sort`," : '')."`id` DESC
			".($cmp['num_5'] ? '' : "LIMIT 50");//если включён учёт списка по уровням
	if(!$spisok = query_arr($sql))
		return array();

	//добавление единицы списка, которая была выбрана ранее
	if($sel_id && empty($arr[$sel_id])) {
		$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
				FROM "._tableFrom($dialog)."
				WHERE `t1`.`id`=".$sel_id;
		if($unit = query_assoc($sql))
			$spisok[$sel_id] = $unit;
	}

	foreach($S as $n => $r)
		if($r['cnn']) {
			$sql = "SELECT `id`,`".$r['col1']."`
					FROM `_spisok`
					WHERE `id` IN ("._idsGet($spisok, $r['col0']).")";
			$S[$n]['cnnAss'] = query_ass($sql);
		}

	//предварительное формирование списка
	$mass = array();
	foreach($spisok as $id => $r) {
		$title = 'значение не настроено';
		if($S[0]['col0']) {
			$title = $r[$S[0]['col0']];
			if($S[0]['cnn'])
				$title = $title ? $S[0]['cnnAss'][$title] : '';
		}

		$content = '';
		if(isset($S[1]) && $S[1]['col0']) {
			$content = $r[$S[1]['col0']];
			if($S[1]['cnn'])
				$content = $content ? $S[1]['cnnAss'][$content] : '';
		}
		if($content)
			$content = '<div class="fs11 grey">'.$content.'</div>';

		$u = array(
			'parent_id' => isset($field['parent_id']) ? $r['parent_id'] : 0,
			'title' => $title,
			'content' => $content
		);

		$mass[$id] = $u;
	}

	$send = array();
	foreach($mass as $id => $r) {
		$title = $r['title'];
		$content = $r['content'];

		if($cmp['num_5']) {
			if($parent_id = $r['parent_id']) {
				$level = 0;
				while($parent_id) {
					if(empty($mass[$parent_id]))
						break;
					$title = $mass[$parent_id]['title'].' » '.$title;
					$parent_id = $mass[$parent_id]['parent_id'];
					$level++;
				}
				$content = '<div style="margin-left:'.($level * 25).'px">'.$r['title'].'</div>'.$content;
			} else
				$content = '<div class="fs14 b">'.$r['title'].'</div>'.$content;
		} else
			$content = $title.$content;

		$u = array(
			'id' => _num($id),
			'title' => $title,
			'content' => $content
		);
		if($v)
			$u['content'] = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}
function _spisok29connectGet($ids, $v) {
	$send = array(
		'dlg0' => 0,        //id диалога элемента
		'col0' => '',       //имя колонки основого списка
		'col1' => '',       //имя колонки привязанного списка
		'cnn' => 0,         //был ли привязанный список
		'cnnAss' => array(),//ассоциативный массив привязанного списка
		'cond' => ''
	);

	if(empty($ids))
		return $send;

	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN (".$ids.")";
	if(!$arr = query_arr($sql))
		return $send;

	$ids = _ids($ids, 1);
	$id0 = $ids[0];

	if(!isset($arr[$id0]))
		return $send;

	$send['dlg0'] = $arr[$id0]['dialog_id'];
	$send['col0'] = $arr[$id0]['col'];
	if(count($ids) == 1 && $v)
		$send['cond'] = "`".$send['col0']."` LIKE '%".addslashes($v)."%'";
	if(count($ids) == 2) {
		$send['cnn'] = 1;
		$id1 = $ids[1];
		$dlg_id = _num($arr[$id0]['num_1']);
		$send['col1'] = $arr[$id1]['col'];

		if($v) {
			$sql = "SELECT `id`,`".$send['col1']."`
					FROM `_spisok`
					WHERE `dialog_id`=".$dlg_id."
					  AND `".$send['col1']."` LIKE '%".addslashes($v)."%'
					LIMIT 1000";
			if($cnnAss = query_ass($sql)) {
				$send['cond'] = "`".$send['col0']."` IN ("._idsGet($cnnAss, 'key').")";
				return $send;
			}
			$send['cond'] = "!`id`";
		}
	}

	return $send;
}
*/
function _spisok59unit($elem_id, $unit_id) {//выбранное значение при связке списков через кнопку [59]
	if(!$unit_id)
		return '';
	if(!$el = _elemOne($elem_id))
		return '';
	if(!$dialog_id = _num($el['num_1']))
		return '';
	if(!$dlg = _dialogQuery($dialog_id))
		return '';

	$sql = "SELECT *
			FROM `"._table($dlg['table_1'])."`
			WHERE `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return '';

	return _blockHtml('spisok', $elem_id, $unit);
}

function _spisokCmpConnectIdGet($el) {//получение id привязонного списка, если рядом стоит родительский список (для страницы, принимающей значения списка)
	if($el['dialog_id'] != 29)//только для связок
		return 0;
	if(!$get_id = _num(@$_GET['id']))
		return 0;
	if(!$page_id = _page('cur'))
		return 0;
	if(!$page = _page($page_id))
		return 0;
	if(!$page['spisok_id'])//страница не принмает значения
		return 0;
	if($page['spisok_id'] == $el['num_1'])//если список является страницей, принимающей значение, возврат $_GET['id']
		return $get_id;

	if(!$dlg = _dialogQuery($page['spisok_id']))
		return 0;

	foreach($dlg['cmp'] as $cmp)
		if($cmp['dialog_id'] == 29 && $cmp['num_1'] == $el['num_1']) {
			$sql = "SELECT *
					FROM `_spisok`
					WHERE `id`=".$get_id;
			if($unit = query_assoc($sql))
				return $unit[$cmp['col']];
		}

	return 0;
}








