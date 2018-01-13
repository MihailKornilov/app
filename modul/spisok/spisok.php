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
function _spisokInc($dialog, $spisok) {//вложенные списки
	$send = array();

	//поиск компонента диалога с вложенным списком
	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		//если не селект
		if($cmp['type_id'] != 2)
			continue;

		//если не является вложенным списком
		if($cmp['num_4'] != 3)
			continue;

		//выборка будет производиться только по нужным строкам списка
		if(!$ids = _idsGet($spisok, $cmp['col_name']))
			continue;

		//получение данных из вложенного списка
		$incDialog = _dialogQuery($cmp['num_1']);
		$sql = "SELECT *
				FROM `".$incDialog['base_table']."`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$cmp['num_1']."
				  AND `id` IN (".$ids.")";
		$send[$cmp['num_1']] = query_arr($sql);
	}

	return $send;
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
			txt_1 - имя заголовка TR
			width - ширина колонки
			font
			color
			txt_6 - pos (позиция)
			sort


		--- старое ---
		txt_5 - текстовый массив настроенных колонок
			-1: num порядковый номер
			-2: dtime_add
			-3: иконки управления
			-4: произвольный текст
	*/
	if(!$dialog = _dialogQuery($ELEM['dialog_id']))
		return 'Несуществующий диалог id'.$ELEM['dialog_id'];

	$dv = $dialog['v_ass'];

	$limit = PAS ? 3 : $dv[$ELEM['num_2']];//лимит

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
			ORDER BY `dtime_add` DESC
			LIMIT ".($limit * $next).",".$limit;
	if(!$spisok = query_arr($sql))
		return '<div class="_empty">'._br($ELEM['txt_1']).'</div>';

	//	$inc = _spisokInc($spDialog, $spisok);

	foreach($spisok as $id => $sp)
		if(empty($sp['num']))
			$spisok[$id]['num'] = $sp['id'];

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
					switch($td['dialog_id']) {
						case 32://порядковый номер - num
							$txt = $sp['num'];
							break;
						case -2://дата
							$tooltip = '">';
							if(isset($sp['viewer_id_add'])) {
								$u = _viewer($sp['viewer_id_add']);
								$msg = 'Вн'.($u['sex'] == 2 ? 'ёс ' : 'есла ').$u['first_name'].' '.$u['last_name'];
								$tooltip = _tooltip($msg, -40);
							}
							$html .= '<td class="w50 wsnw r grey fs12 curD'.$tooltip.FullData($sp['dtime_add'], 0, 1);
							break;
						case -3://иконки управления
							$html .= '<td class="pad0 w15 wsnw">'.
										_iconEdit(array(
											'class' => 'dialog-open ml3',
											'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$sp['id']
										)).
										_iconDel(array(
											'class' => 'dialog-open mr3',
											'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$sp['id'].',to_del:1'
										));
							break;
						case 31://из диалога
							$elemUse = $tabElemUse[$td['num_1']];
							$el = $CMP[$elemUse['id']];

							$txt = $el['col'] ? $sp[$el['col']] : '';
							$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
/*
//							if($el['col_name'] == 'app_any_spisok')
//								$v = $sp['app_id'] ? 0 : 1;
//							else
							//элемент другого списка
							if($el['type_id'] == 2)
								if($el['num_4'] == 3) {
									$incDialog = _dialogQuery($el['num_1']);
									$col_name = $incDialog['component'][$el['num_2']]['col_name'];

									$unit_id = $v;
									$v = $inc[$el['num_1']][$v][$col_name];

									if($incDialog['action_id'] == 2)
										$v = '<a href="'.URL.'&p='.$incDialog['action_page_id'].'&id='.$unit_id.'">'.$v.'</a>';

							}
*/
						break;
					}
					$cls = array();
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
			if(!$arr = _blockArr('spisok', $ELEM['block_id'], 'arr'))
				return '<div class="_empty"><span class="fs15 red">Шаблон единицы списка не настроен.</span></div>';

			//получение ids элементов, которые расставлены по шаблону
			$ids = 0;
			foreach($arr as $r)
				if($r['elem'])
					$ids .= ','.$r['elem']['num_1'];

			//получение самих элементов, расставленных по шаблону
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ids.")";
			$tmpElemArr = $ids ? query_arr($sql) : array();

			//ширина единицы списка с учётом отступов
			$ex = explode(' ', $ELEM['mar']);
			$width = floor(($ELEM['block']['width'] - $ex[1] - $ex[3]) / 10) * 10;

			$send = '';
			foreach($spisok as $sp) {
				$child = array();
				foreach($arr as $id => $r) {
					if($tmpElem = $r['elem']) {//если элемент есть в блоке
						$txt = '';
						switch($tmpElem['num_1']) {
							case -1: $txt = $sp['num']; break;//порядковый номер
							case -2: $txt = FullData($sp['dtime_add'], 0, 1); break; //дата внесения
							case -4: $txt = _br($tmpElem['txt_2']); break;//произвольный текст
							default:
								$tmp = $tmpElemArr[$tmpElem['num_1']];
								switch($tmp['dialog_id']) {
									case 10: $txt = $tmp['txt_1']; break;//произвольный текст
									default://значение колонки
										if($col = $tmp['col'])
											$txt = $sp[$col];
										$txt = _spisokColSearchBg($txt, $ELEM, $tmpElem['num_1']);
								}
						}
						//обёртка в ссылку
						if($tmpElem['num_2'])
							$txt = _spisokColLink($txt, $ELEM, $sp);

						$r['elem']['txt_real'] = $txt;
					}
					$child[$r['parent_id']][$id] = $r;
				}

				$block = _blockArrChild($child);
				$send .= _blockLevel($block, $width);
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
function _spisokColLink($txt, $pe, $sp) {//обёртка значения колонки в ссылку
	//диалог, через который вносятся данные списка
	$dialog = _dialogQuery($pe['num_1']);

	//по умолчанию текущая страница
	$link = '&p='.$pe['page_id'];

	//если таблица является страницей, то ссылка перехода на страницу
	if($dialog['base_table'] == '_page')
		$link = '&p='.$sp['id'];

	//если список пользователей, то переход на страницу приложений пользователя от его имени todo временно
	if($dialog['base_table'] == '_vkuser')
		$link = '&viewer_id='.$sp['viewer_id'];

	//если указана страница перехода после создания элемента списка
	if($dialog['action_id'] == 2)
		$link = '&p='.$dialog['action_page_id'].'&id='.$sp['id'];

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
			WHERE `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_1`=".$pe['id'];
	$v = query_value($sql);

	define($key, $v);

	return $v;
}

function _spisokConnect($cmp_id, $v='') {//получение данных списка для связки (dialog_id:29)
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


	$cond = "`dialog_id`=".$cmp['num_1'];
	if($v)
		$cond .= " AND (`txt_1` LIKE '%".$v."%' OR `txt_2` LIKE '%".$v."%')";
	$sql = "SELECT *
			FROM `_spisok`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return array();

	$send = array();
	foreach($arr as $r) {
		$u = array(
			'id' => _num($r['id']),
			'title' => utf8($r['txt_1']),
			'content' => utf8($r['txt_1'])
		);
		if($r['txt_2'])
			$u['content'] = utf8($r['txt_1'].'<div class="fs11 grey">'.$r['txt_2'].'</div>');

		if($v)
			$u['content'] = preg_replace(_regFilter(utf8($v)), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}





