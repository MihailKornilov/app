<?php


function _spisokShow($pe, $next=0) {//список, выводимый на странице
	/*
	$pe:
		dialog_id = 14
		txt_1 - сообщение пустого запроса
		txt_2[child] - для [182]: произвольное содержание
		txt_5 - текстовый массив настроенных колонок
			-1: num порядковый номер
			-2: dtime_add
			-3: иконки управления
			-4: произвольный текст
		num_1 - внешний вид списка: [181] => Таблица [182] => Шаблон
		num_2 - лимит, количество строк списка, показываемого за один раз
		num_3 - id диалога, через который вносятся данные списка
		num_4[child] - id колонки. Если отрицательное - см.txt_5
		num_5 - галочка "Показывать имена колонок"
		num_6 - галочка "Подсвечивать строки при наведении"
		num_7 - галочка "Узкие строки"

	$next: очередной блок списка, ограниченная $pe['num_2']
	*/
	$page_id = $pe['page_id'];

	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	define('SPISOK_LIMIT', $dv[$pe['num_2']]);//лимит

	//диалог, через который вносятся данные списка
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);

	$CMP = $spDialog['component']; //элементы списка
	$spTable = $spDialog['base_table'];

	$cond = "`id`";
	if(isset($spDialog['field']['deleted']))
		$cond = "!`deleted`";
	if(isset($spDialog['field']['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($spDialog['field']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;

	//получение общего количества строк
	$sql = "SELECT COUNT(*)
			FROM `".$spTable."`
			WHERE ".$cond."
			  "._spisokFilterSearch($pe, $spDialog);
	$all = query_value($sql);

	//получение данных списка
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE ".$cond."
			  "._spisokFilterSearch($pe, $spDialog)."
			ORDER BY `dtime_add` DESC
			LIMIT ".(SPISOK_LIMIT * $next).",".SPISOK_LIMIT;
	$spisok = query_arr($sql);



	$html = '<div class="_empty">'._br($pe['txt_1']).'</div>';

	foreach($spisok as $id => $sp)
		if(empty($sp['num']))
			$spisok[$id]['num'] = $sp['id'];


	//выбор внешнего вида
	if($spisok)
		switch($pe['num_1']) {
			case 181://Таблица
				if(empty($pe['txt_5'])) {
					$html = '<div class="_empty"><span class="fs15 red">Таблица не настроена.</span></div>';
					break;
				}

				$colArr = explode(',', $pe['txt_5']);

				$html = !$next ? '<table class="_stab'._dn(!$pe['num_7'], 'small').'">' : '';
				//отображение названий колонок
				if($pe['num_5'] && !$next) {
					$html .= '<tr>';
					foreach($colArr as $col) {
						$ex = explode('&', $col);
						$html .= '<th>'.$ex[1];
					}
				}

				foreach($spisok as $sp) {
					$html .= '<tr'.($pe['num_6'] ? ' class="over1"' : '').'>';
					foreach($colArr as $col) {
						$ex = explode('&', $col);
						switch($ex[0]) {
							case -1://num
								$html .= '<td class="w15 grey r">'.$sp['num'];
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
													'onclick'=>'_dialogOpen('.$dialog_id.','.$sp['id'].')',
													'class' => 'ml5 mr5'
												));
								//._iconDel();
								break;
							default:
								$el = $CMP[$ex[0]];
								if($el['col_name'] == 'app_any_spisok')
									$v = $sp['app_id'] ? 0 : 1;
								else
									$v = $sp[$el['col_name']];

								$cls = array();
								if($el['type_id'] == 1) {//галочка
									$cls[] = 'center';
									$v = $v ? '<div class="icon icon-ok curD"></div>' : '';
								}
								if(@$ex[3]) {//ссылка
									//по умолчанию текущая страница
									$link = '&p='.$page_id;

									//если таблица является страницей, то ссылка перехода на страницу
									if($spTable == '_page')
										$link = '&p='.$sp['id'];

									//если список пользователей, то переход на страницу приложений пользователя от его имени todo временно
									if($spTable == '_vkuser')
										$link = '&viewer_id='.$sp['viewer_id'];

									//если указана страница перехода после создания элемента списка
									if($spDialog['action_id'] == 2)
										$link = '&p='.$spDialog['action_page_id'].'&id='.$sp['id'];

									$v = '<a href="'.URL.$link.'"'._dn(!$pe['num_7'], 'class="fs12"').'>'.$v.'</a>';
								}
								$html .= '<td class="'.implode(' ', $cls).'">'.$v;
						}

	//						if(strlen($val) && $el['col_name'] == $CMP[$comp_id]['col_name'])
	//							$v = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $v, 1);
					}
				}

				if(SPISOK_LIMIT * ($next + 1) < $all) {
					$count_next = $all - SPISOK_LIMIT * ($next + 1);
					if($count_next > SPISOK_LIMIT)
						$count_next = SPISOK_LIMIT;
					$html .=
						'<tr class="over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
							'<td colspan="20">'.
								'<tt class="db center curP fs14 blue pad8">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>';
				}


				$html .= !$next ? '</table>' : '';
				break;
			case 182://Шаблон
				//получение элементов шаблона
				$sql = "SELECT *
						FROM `_page_element`
						WHERE `parent_id`=".$pe['id']."
						ORDER BY `sort`";
				if(!$tmp = query_arr($sql)) {
					$html = '<div class="_empty"><span class="fs15 red">Шаблон единицы списка не настроен.</span></div>';
					break;
				}

				$html = '';
				foreach($spisok as $sp) {
					$html .= '<div>';
					foreach($tmp as $r) {
						$txt = '';
						switch($r['num_4']) {
							case -1://порядковый номер
								$txt = $sp['num'];
								break;
							case -2://дата внесения
								$txt = FullData($sp['dtime_add'], 0, 1);
								break;
							case -4://произвольный текст
								$txt = _br($r['txt_2']);
								break;
							default:
								if($r['num_4'] <= 0)
									continue;
								switch($r['txt_2']) {
									case 1://имя колонки
										$txt = $CMP[$r['num_4']]['label_name'];
										break;
									case 2://значение колонки
										$txt = $sp[$CMP[$r['num_4']]['col_name']];
										break;
									default: continue;
								}
						}
						$html .=
						'<div class="'.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'"'._pageElemStyle($r).'>'.
							$txt.
						'</div>';
					}
					$html .= '</div>';
				}

				if(SPISOK_LIMIT * ($next + 1) < $all) {
					$count_next = $all - SPISOK_LIMIT * ($next + 1);
					if($count_next > SPISOK_LIMIT)
						$count_next = SPISOK_LIMIT;
					$html .=
						'<div class="over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
							'<tt class="db center curP fs14 blue pad10">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>'.
						'</div>';
				}
				break;
			default:
				$html = 'Неизвестный внешний вид списка: '.$pe['num_1'];
		}

	return $html;
}
function _spisokFilterSearch($pe, $spDialog) {//получение значений фильтра-поиска для списка
	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	//получение значения элемента поиска, содержащегося на странице, где находится список воздействующий на этот список
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_3`=".$pe['id'];
	if(!$search = query_assoc($sql))
		return '';

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($spDialog['component'][$cmp_id]))
			continue;
		$arr[] = "`".$spDialog['component'][$cmp_id]['col_name']."` LIKE '%".addslashes($search['v'])."%'";
	}

	if(empty($arr))
		return '';

	return " AND (".implode($arr, ' OR ').")";
}


