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
			WHERE `app_id`=".APP_ID."
			  and `user_id`=".USER_ID;
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
					`app_id`,
					`user_id`,
					`element_id_spisok`,
					`element_id_filter`,
					`v`,
					`def`
				) VALUES (
					".$id.",
					".APP_ID.",
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

function _spisokCountAll($el, $next=0) {//получение общего количества строк списка
	$key = 'SPISOK_COUNT_ALL'.$el['id'];

	if(defined($key))
		return constant($key);

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);

	$sql = "/* ".__FUNCTION__.":".__LINE__." Кол-во списка ".$dialog['name']." */
			SELECT COUNT(*)
			FROM "._tableFrom($dialog)."
			WHERE "._spisokCond($el);
	$all = _num(query_value($sql));

	//проверка, есть ли единица списка, которую нашли по номеру (num)
	if(!$next && _spisok7num(array(), $el))
		$all++;

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

	$send = '';
	foreach($fields as $col => $r)
		$send .= ',`t2`.`'.$col.'`';

	//вставка колонки `dialog_id` из второй таблицы, если отсутствует в первой
	if(!isset($dialog['field1']['dialog_id']))
		if(isset($dialog['field2']['dialog_id']))
			$send .= ',`t2`.`dialog_id`';

	return $send;
}

function _spisokElemCount($el) {//формирование элемента с содержанием количества списка для вывода на страницу
	if(!$elem_id = $el['num_1'])
		return 'Список не указан.';
	if(!$ELEM = _elemOne($elem_id))
		return 'Элемента, содержащего список, не существует.';

	//если результат нулевой, выводится сообщение из элемента, который размещает список
	if(!$all = _spisokCountAll($ELEM))
		return $el['txt_7'];

	return
	_end($all, $el['txt_1'], $el['txt_3'], $el['txt_5']).
	' '.
	$all.
	' '.
	_end($all, $el['txt_2'], $el['txt_4'], $el['txt_6']);
}
function _spisok7num($spisok, $el) {//добавление единицы списка, если был быстрый поиск по номеру
	/*
		Единица списка с найденным номером будет добавляться при двух условиях:
		  1. Если существует быстрый поиск по этому списку
		  2. Если в шаблоне списка вставлен номер

		Найденное значение будет перемещено или вставлено в начало списка
	*/

	//пока только для списков-шаблонов
	if($el['dialog_id'] != 14)
		return $spisok;

	$search = false;
	$num = 0;

	//1. Поиск элемента-фильтра-поиска
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$num = _num($r['v']);
			break;
		}

	if(!$search)
		return $spisok;
	if(!$num)
		return $spisok;

	//2. Определение, есть ли в шаблоне номер списка
	//получение элементов, находящихся в блоках
	if(!$ELM = _BE('elem_arr', 'spisok', $el['id']))
		return $spisok;

	$is_num = false;
	foreach($ELM as $r) {
		//сам порядковый номер
		if($r['dialog_id'] == 32)
			$is_num = true;

		//сборный текст
		if($r['dialog_id'] == 44)
			if($child = PHP12_44_setup_vvv($r['id']))
				foreach($child as $rr)
					if($rr['dialog_id'] == 32) {
						$is_num = true;
						break;
					}

		if($is_num)
			break;
	}

	//в шаблоне нет номера списка
	if(!$is_num)
		return $spisok;

	$DLG = _dialogQuery($el['num_1']);

	$sql = "SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE `t1`.`num`=".$num."
			  "._spisokCondDef($el['num_1'])."
			LIMIT 1";
	if(!$u = query_assoc($sql))
		return $spisok;

	array_unshift($spisok, $u);
	$spisok[0] = $u;

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
		if($dialog_id = _num(@$r['dialog_id']))
			$DLG_IDS[$dialog_id] = 1;
	
	if(empty($DLG_IDS))
		return $spisok;

	foreach($DLG_IDS as $dlg_id => $i) {
		$dlg = _dialogQuery($dlg_id);
		$CMP = $dlg['cmp'];
		foreach($CMP as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
			//должен является вложенным списком
			if(!_elemIsConnect($cmp))
				continue;

			//должно быть присвоено имя колонки
			if(!$col = $cmp['col'])
				continue;

			//выборка будет производиться только по нужным строкам списка
			if(!$ids = _idsGet($spisok, $col))
				continue;

			//получение данных из вложенного списка
			$incDialog = _dialogQuery($cmp['num_1']);

			$sql = "/* ".__FUNCTION__.":".__LINE__." Вложенный список ".$incDialog['name']." */
					SELECT `t1`.*"._spisokJoinField($incDialog)."
					FROM "._tableFrom($incDialog)."
					WHERE `t1`.`id` IN (".$ids.")".
						_spisokCondDef($incDialog['id']);
			if(!$arr = query_arr($sql))
				continue;

			//вложения во вложенных списках
			$arr = _spisokInclude($arr);
			$arr = _spisokImage($arr);

			//идентификаторы будут заменены на массив с данными единицы списка
			foreach($spisok as $id => $r)
				if($dlg_id == $r['dialog_id']) {
					$connect_id = $r[$col];
					if(is_array($connect_id))
						continue;
					if(empty($arr[$connect_id]))
						continue;
					$spisok[$id][$col] = $arr[$connect_id];
				}
		}
	}

	return $spisok;
}
function _spisokImage($spisok) {//вставка картинок
	if(empty($spisok))
		return array();

	//проверка наличия колонки dialog_id в содержании списка
	$key = key($spisok);
	$sp0 = $spisok[$key];
	if(!isset($sp0['dialog_id']))
		return $spisok;

	$DLG = _dialogQuery($sp0['dialog_id']);

	foreach($DLG['cmp'] as $cmp_id => $cmp) {//поиск компонента диалога с изображениями
		//должен является компонентом "загрузка изображений"
		if($cmp['dialog_id'] != 60)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		foreach($spisok as $id => $r)
			$spisok[$id][$col] = array();

		$sql = "/* ".__FUNCTION__.":".__LINE__." Картинки для списка ".$DLG['name']." */
				SELECT *
				FROM `_image`
				WHERE `obj_name`='elem_".$cmp_id."'
				  AND `obj_id` IN ("._idsGet($spisok).")
				  AND !`deleted`
				  AND !`sort`";
		if($arr = query_arr($sql))
			foreach($arr as$r)
				$spisok[$r['obj_id']][$col] = $r;
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
		num_8 - показывать только те значения, которые связаны с выбранным списком

		настройка шаблона через функцию PHP12_spisok14_setup
	*/

	if(!_BE('block_arr', 'spisok', $ELEM['id']))
		return _emptyRed('Шаблон единицы списка не настроен.');

	$DLG = _dialogQuery($ELEM['num_1']);

	$limit = $ELEM['num_2'];

	if(!$all = _spisokCountAll($ELEM, $next))
		return _emptyMin(_br($ELEM['txt_1']));

	$IS_SORT = _spisokIsSort($ELEM['id']);

	$order = "`t1`.`id` DESC";
	if($IS_SORT || $ELEM['num_3'] == 2319)
		$order = "`sort`";

	//получение данных списка
	$sql = "/* ".__FUNCTION__.":".__LINE__." Список-шаблон <u>".$DLG['name']."</u> */
			SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE "._spisokCond($ELEM)."
			ORDER BY ".$order."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//добавление единицы списка, если был быстрый поиск по номеру
	if(!$next)
		$spisok = _spisok7num($spisok, $ELEM);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	//вставка картинок
	$spisok = _spisokImage($spisok);

	$send = '';
	foreach($spisok as $id => $sp) {
		$block = _BE('block_obj', 'spisok', $ELEM['id']);
		$prm = array('unit_get'=>$sp);
		$send .= '<div class="sp-unit" val="'.$id.'">'.
					_blockLevel($block, $prm).
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
		num_8 - показывать только те значения, которые связаны с выбранным списком

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
		return _emptyRed('Не указан список для вывода данных.');
	if(!$DLG = _dialogQuery($dialog_id))
		return _emptyRed('Списка <b>'.$dialog_id.'</b> не существует.');

	$limit = $ELEM['num_2'];

	//если включена сортировка, количество максимальное
	if($ELEM['num_6'])
		$limit = 1000;

	if(!$all = _spisokCountAll($ELEM))
		return _emptyMin(_br($ELEM['txt_1']));

	$order = "`t1`.`id` DESC";
	if($ELEM['num_6'] || _spisokIsSort($ELEM['block_id']))
		$order = "`sort`";

	//получение данных списка
	$sql = "/* ".__FUNCTION__.":".__LINE__." Список-таблица <u>".$DLG['name']."</u> */
			SELECT `t1`.*"._spisokJoinField($DLG)."
			FROM "._tableFrom($DLG)."
			WHERE "._spisokCond($ELEM)."
			ORDER BY ".$order."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);
	//вставка картинок
	$spisok = _spisokImage($spisok);

	//получение настроек колонок таблицы
	$sql = "SELECT *
			FROM `_element`
			WHERE !`block_id`
			  AND `parent_id`=".$ELEM['id']."
			  AND `num_8`
			ORDER BY `sort`";
	if(!$tabCol = query_arr($sql))
		return _emptyRed('Таблица не настроена.');

	$MASS = array();
	foreach($spisok as $uid => $u) {
		$TR = '<tr'.($ELEM['num_4'] ? ' class="over1"' : '').'>';
		$prm = _blockParam(array('unit_get'=>$u));
		foreach($tabCol as $td) {
			$txt = _elemPrint($td, $prm);

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
			$cls[] = _elemFormatColorDate($td, $prm, $txt);
			$cls = array_diff($cls, array(''));
			$cls = implode(' ', $cls);
			$cls = $cls ? ' class="'.$cls.'"' : '';

			$txt = _elemFormatHide($td, $txt);
			$txt = _elemFormatDigital($td, $txt);
			$txt = _spisokUnitUrl($td, $prm, $txt);

			$TR .= '<td'.$cls._elemStyleWidth($td).'>'.$txt;
		}
		$MASS[$uid] = $TR;
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
	global $SUQ;

	$key = $dialog['id'].'_'.$unit_id;

	if(isset($SUQ[$key]))
		return $SUQ[$key];

	if(!$unit_id)
		return array();

	if($parent_id = $dialog['dialog_id_parent'])
		if(!$dialog = _dialogQuery($parent_id))
			return array();

	if(!$dialog['table_1'])
		return array();

	$sql = "/* ".__FUNCTION__.":".__LINE__." Данные записи */
			SELECT `t1`.*"._spisokJoinField($dialog)."
			FROM "._tableFrom($dialog)."
			WHERE `t1`.`id`=".$unit_id.
				  _spisokCondDef($dialog['id']);
	if(!$spisok[$unit_id] = query_assoc($sql))
		return array();

	$spisok = _spisokInclude($spisok);
	$spisok = _spisokImage($spisok);

	$SUQ[$key] = _arrNum($spisok[$unit_id]);
	
	return $SUQ[$key];
}
function _spisokUnitUrl($el, $prm, $txt) {//обёртка значения в ссылку
	//оборачивать не нужно
	if(!$el['url'])
		return $txt;
	//отсутствует запись
	if(!$u = $prm['unit_get'])
		return $txt;

	//указана конкретная страница
	if($el['url'] != 3) {
		$page = _page($el['url']);
		$uid = $u['id'];
		if($page['dialog_id_unit_get'] != $u['dialog_id'])
			if($el['dialog_id'] == 11) {
				if(!$ids = _ids($el['txt_2'], 1))
					return $txt;
				if(!$EL = _elemOne($ids[0]))
					return $txt;
				if(!$col = $EL['col'])
					return $txt;
				$uid = is_array($u[$col]) ? $u[$col]['id'] : $u[$col];
			}
		return '<a href="'.URL.'&p='.$el['url'].'&id='.$uid.'" class="inhr">'.$txt.'</a>';
	}

	if(!$dlg = _elem_11_dialog($el))
		return $txt;

	//ссылка на страницу, если это список страниц
	if(_table($dlg['table_1']) == '_page')
		return '<a href="'.URL.'&p='.$u['id'].'" class="inhr">'.$txt.'</a>';

	if(!$page_id = _page('dialog_id_unit_get', $dlg['id']))
		return $txt;

	return '<a href="'.URL.'&p='.$page_id.'&id='.$u['id'].'" class="inhr">'.$txt.'</a>';
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
		if($ell['dialog_id'] == 23)
			$element_id_spisok = $el['parent_id'];
		elseif($ell['dialog_id'] == 44)
				if(!empty($ell['block']))
					if($ell['block']['obj_name'] == 'spisok')
						$element_id_spisok = $ell['block']['obj_id'];
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

	//совпадение с номером единицы списка
	if($num = _num($v))
		if($num == $txt)
			return '<em class="fndd">'.$txt.'</em>';

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
	$cond .= _spisokCondBind($el);
	$cond .= _spisokCond7($el);
	$cond .= _spisokCond26($el);
	$cond .= _spisokCond62($el);
	$cond .= _spisokCond74($el);
	$cond .= _spisokCond77($el);
	$cond .= _spisokCond78($el);
	$cond .= _spisokCond83($el);
	$cond .= _spisokCond102($el);

	return $cond;
}
function _spisokCondBind($el) {//отображения значений записи привязанного списка
	//элемент, который указывает на привязанный список
	if(!$el_id_conn = $el['num_8'])
		return '';
	if(!$EL = _elemOne($el_id_conn))
		return '';
	//должен быть указан именно привязанный список
	if(!_elemIsConnect($EL))
		return '';
	//id диалога, единицу списка которого нужно будет отображать
	if(!$DLG_ID_CONN = $EL['num_1'])
		return '';
	//проверка, чтобы список был размещён именно на странице
	if($el['block']['obj_name'] != 'page')
		return ' AND !`t1`.`id`';
	if(!$page_id = $el['block']['obj_id'])
		return ' AND !`t1`.`id`';
	//страница, на которой размещён список
	if(!$page = _page($page_id))
		return ' AND !`t1`.`id`';
	//id диалога, данные единицы списка которого выводится на странице
	if(!$dlg_id = $page['dialog_id_unit_get'])
		return ' AND !`t1`.`id`';
	if(!$DLG = _dialogQuery($dlg_id))
		return ' AND !`t1`.`id`';
	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`t1`.`id`';
	//получение данных единицы списка, которое принимает страница
	if(!$unit = _spisokUnitQuery($DLG, $unit_id))
		return ' AND !`t1`.`id`';

	if(!$col = $EL['col'])
		return ' AND !`t1`.`id`';

	//выбранный привязанный список совпадает с принимаемым страницей
	if($DLG_ID_CONN == $dlg_id)
		return " AND `t1`.`".$col."`=".$unit_id;

	//поиск первого элемента, который содержит привязанный список выбранного значения для отображения
	$cmp = false;
	foreach($DLG['cmp'] as $r)
		if(_elemIsConnect($r))
			if($r['num_1'] == $DLG_ID_CONN) {
			$cmp = $r;
			break;
		}

/*
	echo 'указатель на связку='.$DLG_ID_CONN.' ('._dialogParam($DLG_ID_CONN, 'name').') col='.$col.'<br>';
	echo 'страница принимает='.$dlg_id.' ('._dialogParam($dlg_id, 'name').') единицу списка '.$unit_id.'<br>';
	echo 'найденная колонка из связки '.$cmp['col'].'<br>';
	echo 'получен id от указателя '.$unit[$cmp['col']]['id'].'<br>';
	echo 'выводится список='.$el['num_1'].' ('._dialogParam($el['num_1'], 'name').')<br>';
	echo '<br>';
*/

	if(!$cmp)
		return ' AND !`t1`.`id` /* no cmp */';

	return " AND `t1`.`".$col."`=".$unit[$cmp['col']]['id'];
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
function _spisokCond26($el) {//дополнительные условия - в отдельном элементе [26]
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=26
			  AND `num_1`=".$el['id']."
			LIMIT 1";
	if(!$elem26 = query_assoc($sql))
		return '';

	//элемент-колонка, по которому будет применяться фильтр
	if(!$elCol = _elemOne($elem26['num_2']))
		return '';
	if(!$col = $elCol['col'])
		return '';

	//если у основного значнеия есть дочерние, получение их ID, чтобы добавить к запросу
	if($v = _num($elem26['num_3'])) {
		$sql = "SELECT `id`
				FROM `_spisok`
				WHERE `parent_id`=".$v."
				  AND !`deleted`";
		if($ids = query_ids($sql))
			$v .= ','.$ids;
	}


	return " AND `".$col."` IN (".$v.")";
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

		$send .= _22cond($filter['id']);
	}

	return $send;
}
function _spisokCond74($el) {//фильтр-радио
	$filter = false;

	//поиск элемента-фильтра-радио
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 74) {
			$filter = true;
			if(!$v = _num($r['v']))
				return ' AND !`id` /* некорректное значение фильтра */';
			break;
		}

	if(!$filter)
		return '';

	return _22cond($v);
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

	$v = _filterCalendarDef($v);
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
			$v = _num($r['v']);
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return '';

	//элемент, указывающий на подключенный список
	if(!$elem_id = _ids($filter['txt_1'], 'first'))
		return " AND !`id`";
	if(!$EL = _elemOne($elem_id))
		return " AND !`id`";

	//колонка, по которой будет производиться фильтрование
	if(!$col = $EL['col'])
		return " AND !`id`";

	//получение диалога подключенного списка
	if($EL['dialog_id'] == 29 && !$dialog_id = _num($EL['num_1']))
		return " AND !`id`";
	if(!$dialog = _dialogQuery($dialog_id))
		return " AND !`id`";

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
function _spisokCond102($el) {//Фильтр - Выбор нескольких групп значений
	$filter = false;
	$v = 0;

	//поиск элемента-фильтра-select
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 102) {
			$filter = $r['elem'];
			$v = _ids($r['v']);
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

	return " AND `".$col."` IN (".$v.")";
}

function _22cond($parent_id) {//получение условий запроса из базы при помощи: Дополнительные условия к фильтру (вспомогательный элемент)
	//условия, формирующие фильтр
	$sql = "SELECT *
			FROM `_element`
			WHERE `parent_id`=".$parent_id;
	if(!$cond = query_arr($sql))
		return '';

	//получение id элементов, содержащих колонки
	$idsCol = array();
	foreach($cond as $r)
		if(_idsLast($r['txt_1']))
			$idsCol[] = _idsLast($r['txt_1']);

	if(empty($idsCol))
		return '';

	//колонки, по которым будет производиться фильтр
	$sql = "SELECT `id`,`col`
			FROM `_element`
			WHERE `id` IN (".implode(',', $idsCol).")";
	if(!$elCol = query_ass($sql))
		return '';

	$send = '';
	foreach($cond as $r) {
		//если вложение более чем одно - пока такое не отработано
		if(_ids($r['txt_1'], 'count') > 2)
			return " AND !(`t1`.`id`)";

		//если присутствует одно вложенное значение
		if(_ids($r['txt_1'], 'count') == 2) {
			if(!$EL = _elemOne(_ids($r['txt_1'], 'first')))
				return " AND !((`t1`.`id`))";
			if(!$col = $EL['col'])
				return " AND !(((`t1`.`id`)))";

			$dialog = _dialogQuery($EL['num_1']);
			$send .= " AND `".$col."` IN (
						SELECT `id`
						FROM "._tableFrom($dialog)."
						WHERE `id`"._spisokCondDef($dialog['id']).
							_22condV(
									$r['num_2'],
									$elCol[_idsLast($r['txt_1'])],
									$r['txt_2']
								 )."
						)";
			continue;
		}

		$val = _elemIsConnect($r['txt_1']) && $r['num_3'] ? $r['num_3'] : $r['txt_2'];

		//если элемент является датой, преобразование значения в дату, если это число.
		if(_elemIsDate($r['txt_1']))
			if(preg_match(REGEXP_INTEGER, $val)) {
				//число - это количество дней
				//нулевое значение = сегодня
				//положительное = дни в будущем
				//отрицательное = дни в прошлом
				$val = TODAY_UNIXTIME + $val * 86400;
				$val = strftime('%Y-%m-%d', $val);
			}

		$send .= _22condV(
					$r['num_2'],
					$elCol[$r['txt_1']],
					$val
				 );
	}

	return 	$send;
}
function _22condV($act, $col, $val) {//значение запроса по конкретному условию
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

	if(!$col)
		return '';

	$val = addslashes($val);
	if($act != 9 && $act != 10)
		$val = preg_match(REGEXP_INTEGER, $val) ? $val : "'".$val."'";
	switch($act) {
		case 1: return " AND !`t1`.`".$col."`";
		case 2: return " AND `t1`.`".$col."`";
		case 3: return " AND `t1`.`".$col."`=".$val;
		case 4: return " AND `t1`.`".$col."`!=".$val;
		case 5: return " AND `t1`.`".$col."`>".$val;
		case 6: return " AND `t1`.`".$col."`>=".$val;
		case 7: return " AND `t1`.`".$col."`<".$val;
		case 8: return " AND `t1`.`".$col."`<=".$val;
		case 9: return " AND `t1`.`".$col."` LIKE '%".$val."%'";
		case 10:return " AND `t1`.`".$col."` NOT LIKE '%".$val."%'";
	}

	return '';
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
	if(!$spisok = _29cnnSpisok($EL, $v))
		return array();

	//добавление единицы списка, которая была выбрана ранее
	if($sel_id && empty($spisok[$sel_id]))
		$spisok[$sel_id] = _spisokUnitQuery($DLG, $sel_id);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	//формирование списка по уровням
	if($EL['num_5'])
		return _29cnnLevel($EL, $spisok);

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
		if($content = _29cnnTitle($EL['txt_4'], $sp, 1))
			$u['content'] = $title.'<div class="grey fs12">'.$content.'</div>';
		if($v)
			$u['content'] = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}
function _29cnnLevel($EL, $spisok) {//вывод списка по уровням
	$send = array();

	$child = array();
	foreach($spisok as $id => $sp)
		if($sp['parent_id'])
			$child[$sp['parent_id']][] = $sp;

	foreach($spisok as $id => $sp) {
		if($sp['parent_id'])
			continue;

		$title = _29cnnTitle($EL['txt_3'], $sp);
		$send[] = array(
			'id' => $id,
			'title' => $title,
			'content' => '<b>'.$title.'</b>'
		);

		if(!empty($child[$id]))
			foreach($child[$id] as $r) {
				$ch = _29cnnTitle($EL['txt_3'], $r);
				$send[] = array(
					'id' => $r['id'],
					'title' => $title.' » '.$ch,
					'content' => '<div class="ml20">'.$ch.'</div>'
				);
			}
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
	if(!$ids = _ids($ids, 'arr'))
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
	if(!$prm['unit_get'] = _spisokUnitQuery($dlg, $unit_id))
		return '';

	return _blockHtml('spisok', $elem_id, $prm);
}

function _spisokCmpConnectIdGet($el, $sel_id=0) {//получение id привязонного списка, если рядом стоит родительский список (для страницы, принимающей значения списка)
	if($sel_id)
		return $sel_id;
	if(!_elemIsConnect($el))//только для связок
		return 0;


	return 0;



	if(!$get_id = _num(@$_GET['id']))
		return 0;
	if(!$page_id = _page('cur'))
		return 0;
	if(!$page = _page($page_id))
		return 0;
	if(!$page['dialog_id_unit_get'])//страница не принимает значения
		return 0;
	if($page['dialog_id_unit_get'] == $el['num_1'])//если список является страницей, принимающей значение, возврат $_GET['id']
		return $get_id;

	if(!$dlg = _dialogQuery($page['dialog_id_unit_get']))
		return 0;

	foreach($dlg['cmp'] as $cmp)
		if(_elemIsConnect($cmp) && $cmp['num_1'] == $el['num_1']) {
			$sql = "SELECT *
					FROM `_spisok`
					WHERE `id`=".$get_id;
			if($unit = query_assoc($sql))
				if($cmp['col'])
					return $unit[$cmp['col']];
		}

	return 0;
}








