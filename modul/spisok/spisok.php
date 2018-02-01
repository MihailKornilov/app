<?php
function _spisokCountAll($pe) {//получение общего количества строк списка
	$key = 'SPISOK_COUNT_ALL'.$pe['id'];

	if(defined($key))
		return constant($key);

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($pe['num_1']);

	$sql = "SELECT COUNT(*)
			FROM `".$dialog['base_table']."`
			WHERE "._spisokCond($pe);
	$all = _num(query_value($sql));

	define($key, $all);

	return $all;
}
function _spisokElemCount($r) {//формирование элемента с содержанием количества списка для вывода на страницу
	if(!$elem_id = $r['num_1'])
		return 'Список не указан.';

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$elem_id;
	if(!$elem = query_assoc($sql))
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
function _spisokInclude($spisok, $CMP) {//вложенные списки
	foreach($CMP as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
		//должен является вложенным списком
		if($cmp['dialog_id'] != 29)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		//выборка будет производиться только по нужным строкам списка
		if(!$ids = _idsGet($spisok, $col))
			continue;

		//получение данных из вложенного списка
		$incDialog = _dialogQuery($cmp['num_1']);
		$sql = "SELECT *
				FROM `".$incDialog['base_table']."`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$cmp['num_1']."
				  AND `id` IN (".$ids.")";
		$arr = query_arr($sql);
		//идентификаторы будут заменены на массив с данными единицы списка
		foreach($spisok as $id => $r) {
			$connect_id = $r[$col];
			$spisok[$id][$col] = $arr[$connect_id];
		}
	}

	return $spisok;
}
function _spisokShow($ELEM, $next=0) {//список, выводимый на странице
	/*
	$ELEM:
		dialog_id = 14: ШАБЛОН
            num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
			num_2 - длина (лимит, количество строк, выводимых за один раз)
			txt_1 - сообщение пустого запроса

		dialog_id = 23: таблица
            num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
			num_2 - длина (лимит, количество строк, выводимых за один раз)
			txt_1 - сообщение пустого запроса
			num_3 - узкие строки таблицы
			num_4 - подсвечивать строку при наведении мыши
			num_5 - показывать имена колонок
			txt_2 - ids элементов через запятую. Сами элементы хранятся в таблице _element
		Значения вставляются диалогом 31.
		Параметры значений:
			num_1 - id элемента-значения из диалога
			txt_1 - имя заголовка TR
			width - ширина колонки
			font
			color
			txt_6 - pos (позиция)
			num_2 - является ссылкой
			sort
	*/
	if(!$dialog = _dialogQuery($ELEM['dialog_id']))
		return 'Несуществующий диалог id'.$ELEM['dialog_id'];

	$limit = PAS ? 3 : $ELEM['num_2'];//лимит

	//диалог, через который вносятся данные списка
	$dialog_id = $ELEM['num_1'];
	$spDialog = _dialogQuery($dialog_id);

	//элементы списка
	$CMP = $spDialog['cmp'];
	$spTable = $spDialog['base_table'];

	$all = _spisokCountAll($ELEM);

	//получение данных списка
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE "._spisokCond($ELEM)."
			ORDER BY `id` DESC
			LIMIT ".($limit * $next).",".$limit;
	if(!$spisok = query_arr($sql))
		return '<div class="_empty">'._br($ELEM['txt_1']).'</div>';

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok, $CMP);

	//выбор внешнего вида
	switch($ELEM['dialog_id']) {
		//таблица
		case 23://Таблица
			if(empty($ELEM['txt_2']))
				return '<div class="_empty"><span class="fs15 red">Таблица не настроена.</span></div>';
			if(!$ELEM['num_1'])
				return '<div class="_empty"><span class="fs15 red">Не указан список для вывода данных.</span></div>';
			if(!$tabDialog = _dialogQuery($ELEM['num_1']))
				return '<div class="_empty"><span class="fs15 red">Списка <b>'.$ELEM['num_1'].'</b> не существует.</span></div>';

			//получение настроек колонок таблицы
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ELEM['txt_2'].")
					  AND `block_id`=-".$ELEM['id']."
					ORDER BY `sort`";
			$tabCol = query_arr($sql);

			//получение используемых значений списка
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN ("._idsGet($tabCol, 'num_1').")";
			$tabElemUse = query_arr($sql);

			$html = !$next ? '<table class="_stab'._dn(!$ELEM['num_3'], 'small').'">' : '';

			//отображение названий колонок
			if(!$next && $ELEM['num_5']) {
				$html .= '<tr>';
				foreach($tabCol as $tr)
					$html .= '<th>'.$tr['txt_1'];
			}

			foreach($spisok as $sp) {
				$html .= '<tr'.($ELEM['num_4'] ? ' class="over1"' : '').'>';
				foreach($tabCol as $td) {
					$txt = '';
					$cls = array();
					switch($td['dialog_id']) {
						case 32://порядковый номер - num
							$txt = _spisokUnitNum($sp);
							$txt = _spisokUnitUrl($txt, $sp, $td['url']);
							break;
						case 33://дата
							$txt = _spisokUnitData($sp['dtime_add'], $td);
							break;
						case 34://иконки управления
							$txt = _spisokUnitIconEdit($dialog_id, $sp['id']);
							$cls[] = 'pad0';
							break;
						case 11://из диалога
							$elemUse = $tabElemUse[$td['num_1']];
							$el = $CMP[$elemUse['id']];

							//элементу не присвоена колонка
							if(!$col = $el['col'])
								break;

							//в списке не существует такой колонки
							if(!isset($sp[$col]))
								break;

							//значение из другого списка
							if($el['dialog_id'] == 29) {
								$txt = $sp[$col]['txt_1'];
								$txt = _spisokUnitUrl($txt, $sp[$col], $td['url']);
								break;
							}

							$txt = _br($sp[$col]);
							$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
							$txt = _spisokUnitUrl($txt, $sp, $td['url']);
						break;
					}
					$cls[] = $td['font'];
					$cls[] = $td['color'];
					$cls[] = $td['txt_6'];//pos - позиция
					$cls = array_diff($cls, array(''));
					$cls = implode(' ', $cls);
					$cls = $cls ? ' class="'.$cls.'"' : '';
					$html .= '<td'.$cls.' style="width:'.$td['width'].'px">'.$txt;
				}
			}

			if($limit * ($next + 1) < $all) {
				$count_next = $all - $limit * ($next + 1);
				if($count_next > $limit)
					$count_next = $limit;
				$html .=
					'<tr class="over5" onclick="_spisokNext($(this),'.$ELEM['id'].','.($next + 1).')">'.
						'<td colspan="20">'.
							'<tt class="db center curP fs14 blue pad8">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>';
			}

			$html .= !$next ? '</table>' : '';
			return $html;

		//шаблон
		case 14:
			if(!$BLK = _block('spisok', $ELEM['block_id'], 'block_arr'))
				return '<div class="_empty"><span class="fs15 red">Шаблон единицы списка не настроен.</span></div>';

			//получение элементов, расставленных находящихся в блоках
			$ELM = _block('spisok', $ELEM['block_id'], 'elem_arr');

			//получение самих элементов, расставленных по шаблону
			$ids = _idsGet($ELM, 'num_1');
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ids.")";
			$ELM_TMP = $ids ? query_arr($sql) : array();

			//ширина единицы списка с учётом отступов
			$ex = explode(' ', $ELEM['mar']);
			$width = floor(($ELEM['block']['width'] - $ex[1] - $ex[3]) / 10) * 10;

			$send = '';
			foreach($spisok as $sp) {
				$child = array();
				foreach($BLK as $id => $r) {
/*
					$r['elem'] = array();
					if($elem_id = $r['elem_id']) {//если элемент есть в блоке
						$txt = '';
						$el = $ELM[$elem_id];
						switch($el['num_1']) {
							default:
								$tmp = $ELM_TMP[$el['num_1']];
								switch($tmp['dialog_id']) {
									case 10: $txt = $tmp['txt_1']; break;//произвольный текст
									default://значение колонки
										if($col = $tmp['col'])
											$txt = $sp[$col];
										$txt = _spisokColSearchBg($txt, $ELEM, $el['num_1']);
								}
						}

						$el['tmp'] = 1;
						$el['block'] = $r;
						$el['txt_real'] = $txt;
						$r['elem'] = $el;
					}
*/
					$r['elem'] = $r['elem_id'] ? $ELM[$r['elem_id']] : array();
					$child[$r['parent_id']][$id] = $r;
				}

				$block = _blockArrChild($child);
				$send .= _blockLevel($block, $width, 0, 0, 1, $sp);
			}

			if($limit * ($next + 1) < $all) {
				$count_next = $all - $limit * ($next + 1);
				if($count_next > $limit)
					$count_next = $limit;
				$send .=
					'<div class="over5" onclick="_spisokNext($(this),'.$ELEM['id'].','.($next + 1).')">'.
						'<tt class="db center curP fs14 blue pad10">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>'.
					'</div>';
			}

			return $send;
	}

	return 'Неизвестный внешний вид списка: '.$ELEM['num_1'];
}

function _spisokUnitNum($u) {//порядковый номер - значение единицы списка
	if(empty($u))
		return 'порядковый номер';
	if(empty($u['num']))
		return $u['id'];
	return $u['num'];
}
function _spisokUnitData($dtime, $el) {//дата и время - значение единицы списка [33]
	if(empty($dtime))
		return 'дата и время';
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
function _spisokUnitIconEdit($dialog_id, $unit_id) {//иконки управления - значение единицы списка [34]
	return
		_iconEdit(array(
			'class' => 'dialog-open pl',
			'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$unit_id
		)).
		_iconDel(array(
			'class' => 'dialog-open pl',
			'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$unit_id.',del:1'
		));
}

function _spisokUnitUrl($txt, $sp, $is_url) {//обёртка значения колонки в ссылку
	if(!$is_url)//если оборачивать не нужно
		return $txt;

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($sp['dialog_id']);

	//по умолчанию текущая страница
	$link = '&p='._page('cur');

	//если таблица является страницей, то ссылка перехода на страницу
	if($dialog['base_table'] == '_page')
		$link = '&p='.$sp['id'];

	//если список пользователей, то переход на страницу приложений пользователя от его имени todo временно
	if($dialog['base_table'] == '_vkuser')
		$link = '&viewer_id='.$sp['viewer_id'];

	//если есть страница, которая принимает значения списка
	if($page_id = _page('spisok_id', $dialog['id']))
		$link = '&p='.$page_id.'&id='.$sp['id'];

	return '<a href="'.URL.$link.'" class="inhr">'.$txt.'</a>';
}
function _spisokColSearchBg($txt, $el, $cmp_id) {//подсветка значения колонки при текстовом (быстром) поиске
	$val = _spisokCondSearchVal($el);
	if(!strlen($val))
		return $txt;

	//элемент поиска, который ищет по данному списку
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=7
			  AND `num_1`=".$el['id'];
	if(!$elemSearch = query_assoc($sql))
		return $txt;

	//ассоциативный массив колонок, по которым производится поиск
	$colIds = _idsAss($elemSearch['txt_2']);
	//если по данной колонке поиск разрешён, то выделение цветом найденные символы
	if(!isset($colIds[$cmp_id]))
		return $txt;

	$val = utf8($val);
	$txt = utf8($txt);
	$txt = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $txt, 1);
	$txt = win1251($txt);

	return $txt;
}

function _spisokCond($el) {//формирование строки с условиями поиска
	//$el - элемент, который размещает список. 14 или 23.
	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($el['num_1']);
	$field = $dialog['field'];

	$cond = "`id`";
	if(isset($field['deleted']))
		$cond = "!`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($field['dialog_id']))
		$cond .= " AND `dialog_id`=".$el['num_1'];

	$cond .= _spisokCondSearch($el);

	return $cond;
}
function _spisokCondSearch($pe) {//значения фильтра-поиска для списка
	//элемент поиска, который ищет по данному списку
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=7
			  AND `num_1`=".$pe['id'];
	if(!$elemSearch = query_assoc($sql))
		return '';

	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($elemSearch['txt_2'], 1))
		return '';

	$val = _spisokCondSearchVal($pe);
	if(!strlen($val))
		return '';

	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($pe['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		$arr[] = "`".$cmp[$cmp_id]['col']."` LIKE '%".addslashes($val)."%'";
	}

	if(!$arr)
		return '';

	return " AND (".implode($arr, ' OR ').")";
}
function _spisokCondSearchVal($pe) {//получение введённого значения в строку поиска, воздействующий на этот список
	$key = 'ELEM_SEARCH_VAL'.$pe['id'];

	if(defined($key))
		return constant($key);

	$sql = "SELECT `v`
			FROM `_element`
			WHERE `dialog_id`=7
			  AND `num_1`=".$pe['id'];
	$v = query_value($sql);

	define($key, $v);

	return $v;
}

function _spisokConnect($cmp_id, $v='', $sel_id=0) {//получение данных списка для связки (dialog_id:29)
	if(!$cmp_id)
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$cmp_id;
	if(!$cmp = query_assoc($sql))
		return array();

	if(!$cmp['num_1'])
		return array();

	if(!$dialog = _dialogQuery($cmp['num_1']))
		return array();

	//получение имён колонок для отображения содержания Select
	$ex = explode(',', $cmp['txt_2']);
	$col0 = _num($ex[0]);
	$col1 = _num(@$ex[1]);
	if($cmp['txt_2']) {
		$sql = "SELECT `id`,`num_1`
				FROM `_element`
				WHERE `id` IN ("._ids($cmp['txt_2']).")";
		if($ass = query_ass($sql)) {
			$sql = "SELECT `id`,`col`
					FROM `_element`
					WHERE `id` IN (".implode(',', $ass).")";
			if($cols = query_ass($sql)) {
				$col0 = $cols[$ass[$col0]];
				$col1 = @$cols[$ass[$col1]];
			}
		}
	}
	//проверка наличия колонок в таблице
	$col0 = isset($dialog['field'][$col0]) ? $col0 : '';
	$col1 = isset($dialog['field'][$col1]) ? $col1 : '';

	$cond = "`dialog_id`=".$cmp['num_1'];
	if($v) {
		$cols = array();
		if($col0)
			$cols[] = "`".$col0."` LIKE '%".$v."%'";
		if($col1)
			$cols[] = "`".$col1."` LIKE '%".$v."%'";
		$cond .= $cols ? " AND (".implode(' OR ', $cols).")" : " AND !`id`";
	}
	$sql = "SELECT *
			FROM `_spisok`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return array();

	//добавление единицы списка, которая была выбрана ранее
	if($sel_id && empty($arr[$sel_id])) {
		$sql = "SELECT *
				FROM `_spisok`
				WHERE `dialog_id`=".$cmp['num_1']."
				  AND `id`=".$sel_id;
		if($unit = query_assoc($sql))
			$arr[$sel_id] = $unit;
	}

	$send = array();
	foreach($arr as $r) {
		$u = array(
			'id' => _num($r['id']),
			'title' => utf8($col0 ? $r[$col0] : 'значение не настроено'),
			'content' => utf8($col0 ? $r[$col0] : '<div class="red">значение не настроено</div>')
		);
		if($col1 && $r[$col1])
			$u['content'] = utf8($r[$col0].'<div class="fs11 grey">'.$r[$col1].'</div>');
		if($v)
			$u['content'] = preg_replace(_regFilter(utf8($v)), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}





