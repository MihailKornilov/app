<?php




function _pageShow($page_id, $blockShow=0) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();


	_pageBlockTest($page_id);

	//получение списка блоков
	$sql = "SELECT *
			FROM `_page_block`
			WHERE `page_id`=".$page_id."
			ORDER BY `parent_id`,`sort`";
	$arr = query_arr($sql);
	$block = array();
	$elem = array();
	foreach($arr as $id => $r) {
		$elem[$id] = array();
		$r['sub'] = array();
		if(!$r['parent_id']) {
			$block[$id] = $r;
			continue;
		}
		$block[$r['parent_id']]['sub'][] = $r;
	}

	//расстановка элементов в блоки
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$page_id."
			ORDER BY `sort`";
	$elem_arr = query_arr($sql);
	foreach($elem_arr as $r)
		$elem[$r['block_id']][] = $r;

	$send = '';
	foreach($block as $block_id => $r) {
		$cls = PAS ? ' pb prel' : '';
		$val = PAS ? ' val="'.$r['id'].'"' : '';
		$mh = $elem[$block_id] ? '': ' h50';//минимальная высота, если блок пустой
		$r['elem_count'] = count($elem[$block_id]);

		if(!$r['sub']) {
			$send .=
				'<div id="pb_'.$r['id'].'"'._pageBlockStyle($r).' class="elem-sort bspb '.$r['bg'].' '.$cls.$mh.'"'.$val.'>'.
					_pageBlockPas($r, $blockShow).
					_pageElemSpisok($elem[$block_id]).
				'</div>';
			continue;
		}

		$send .= '<div id="pb_'.$r['id'].'" class="pb"'.$val.'>'.
					'<table class="w100p prel'.$mh.'">'.
						'<tr>';
		$cSub = count($r['sub']) - 1;
		foreach($r['sub'] as $n => $sub) {
			$sub['elem_count'] = count($elem[$sub['id']]);
			$send .= '<td class="elem-sort bspb '.$sub['bg'].' prel top"'._pageBlockStyle($sub).'>'.
						_pageBlockPas($sub, $blockShow, $n != $cSub).
						_pageElemSpisok($elem[$sub['id']]);
		}
		$send .=	'</table>'.
				'</div>';
	}

	return
	'<div class="pbsort0 prel">'.
		$send.
	'</div>'.
	_pageSpisokUnit().

(PAS ?
	'<div id="page-block-add" class="center mt1 pad15 bg-gr1 bor-f0 over1 curP'._dn($blockShow).'">'.
		'<tt class="fs15 color-555">Добавить новый блок</tt>'.
	'</div>'.
	'<script>'.
		'var ELEM_ARR={'._pageElemArr($elem_arr).'},'.
			'ELEM_COLOR={'._pageElemColor().'};'.
	'</script>'
: '').
//	_pr($elem_arr).

	'<script>_pageShow()</script>';
}
function _pageBlockTest($page_id) {//проверка страницы на наличие хотя бы одного блока
	//если блоков нет, то внесение одного и применение к нему всех элементов на странице

	$sql = "SELECT `id`
			FROM `_page_block`
			WHERE `page_id`=".$page_id."
			LIMIT 1";
	if($block_id = query_value($sql))
		return;

	$sql = "INSERT INTO `_page_block` (
				`app_id`,
				`page_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$page_id.",
				".VIEWER_ID."
			)";
	query($sql);

	$block_id = query_insert_id('_page_block');

	$sql = "UPDATE `_page_element`
			SET `block_id`=".$block_id."
			WHERE `page_id`=".$page_id;
	query($sql);
}
function _pageBlockStyle($r) {//стили css для блока
	$send = array();

	//отступы
	$ex = explode(' ', $r['pad']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'padding:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	//границы
	$ex = explode(' ', $r['bor']);
	foreach($ex as $i => $b) {
		if(!$b)
			continue;
		switch($i) {
			case 0: $send[] = 'border-top:#DEE3EF solid 1px'; break;
			case 1: $send[] = 'border-right:#DEE3EF solid 1px'; break;
			case 2: $send[] = 'border-bottom:#DEE3EF solid 1px'; break;
			case 3: $send[] = 'border-left:#DEE3EF solid 1px'; break;
		}
	}

	if($r['w'])
		$send[] = 'width:'.$r['w'].'px';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _pageBlockPas($r, $show=0, $resize=0) {//подсветка блоков при редактировании
	if(!PAS)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	$block_id = $r['id'];
	$dn = $show ? '' : ' dn';
	$resize = $resize ? ' resize' : '';
	$empty = $r['elem_count'] ? '' : ' empty';


	return
	'<div class="pas-block'.$empty.$resize.$dn.'" val="'.$block_id.'">'.
		'<div class="fl">'.
			$block_id.
			'<span class="fs11 grey"> w'.$r['w'].'</span>'.
			'<span class="fs11 color-acc"> '.$r['sort'].'</span>'.
		'</div>'.
		'<div class="pas-icon">'.
			'<div class="icon icon-add mr3'._tooltip('Добавить элемент', -57).'</div>'.
			'<div class="icon icon-setup mr3'._tooltip('Стили блока', -39).'</div>'.
	($r['parent_id'] ? '<div class="icon icon-move mr3 curM center'._tooltip('Изменить порядок<br />по горизонтали', -56, '', 1).'</div>' : '').
			'<div class="icon icon-div mr3'._tooltip('Разделить блок пополам', -76).'</div>'.
			'<div class="icon icon-del-red'._tooltip('Удалить блок', -42).'</div>'.
		'</div>'.
	'</div>';
}
function _pageElemSpisok($elem) {//список элементов формате html для конкретного блока
	if(!$elem)
		return '';

	$send = '';
	foreach($elem as $r) {
		$send .=
		'<div class="pe prel '.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'" id="pe_'.$r['id'].'"'._pageElemStyle($r).'>'.
			_pageElemPas($r).
			_pageElemUnit($r).
		'</div>';
	}

	return $send;
}
function _pageElemArr($elem) {//массив настроек элементов в формате JS
	if(empty($elem))
		return '';

	$send = array();
	foreach($elem as $r) {
		$size = 13;
		if($r['size']) {
			$ex = explode('fs', $r['size']);
			$size = _num($ex[1]);
		}
		$send[] = $r['id'].':{'.
			'id:'.$r['id'].','.
			'dialog_id:'.$r['dialog_id'].','.
			'fontAllow:'._pageElemFontAllow($r['dialog_id']).','.
			'type:"'.$r['type'].'",'.
			'pos:"'.$r['pos'].'",'.
			'color:"'.$r['color'].'",'.
			'font:"'.$r['font'].'",'.
			'size:'.$size.','.
			'pad:"'.$r['pad'].'"'.
		'}';
	}
	return implode(',', $send);
}
function _pageElemColor() {//массив цветов для текста в формате JS, доступных элементам
	return
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
		'"color-ref":["#800","Тёмно-красный"],'.
		'"red":["#e22","Красный"],'.
		'"color-del":["#a66","Тёмно-бордовый"],'.
		'"color-vin":["#c88","Бордовый"]';
}
function _pageElemPas($r) {
	if(!PAS)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	return '<div class="elem-pas" val="'.$r['id'].'"></div>';
}
function _pageElemUnit($unit) {//формирование элемента страницы
	switch($unit['dialog_id']) {
		case 2://button
			$color = array(
				0 => '',        //Синий - по умолчанию
				321 => '',      //Синий
				322 => 'green', //Зелёный
				323 => 'red',   //Красный
				324 => 'grey',  //Серый
				325 => 'cancel',//Прозрачный
				326 => 'pink',  //Розовый
				327 => 'orange' //Оранжевый
			);
			return _button(array(
						'name' => $unit['txt_1'],
						'click' => '_dialogOpen('._dialogValToId('button'.$unit['id']).')',
						'color' => $color[$unit['num_1']],
						'small' => $unit['num_2']
					));
		case 3://menu
			return _pageElemMenu($unit);
		case 4://head
			return '<div class="hd2">'.$unit['txt_1'].'</div>';
		case 7://search
			return _search(array(
						'hold' => $unit['txt_1'],
						'width' => $unit['num_2'],
						'v' => $unit['v']
					));
		case 9://link
			return '<a href="'.URL.'&p='.$unit['num_1'].'">'.
						$unit['txt_1'].
				   '</a>';
		case 10://произвольный текст
			return _br($unit['txt_1']);
		case 11://имя колонки или значение из диалога
			/*
				num_1 - dialog_id списка
				num_2 - тип содержания колонки:
							331: название
							332: значение
				num_3 - id компонента диалога
				$_GET['id'] - id списка при выводе
			*/
			if(!$spisok_id = _num(@$_GET['id']))
				return 'некорректный id объекта';

			if(!$unit['num_3'])
				return 'нулевое значение компонента';

			$sql = "SELECT *
					FROM `_spisok`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$spisok_id;
			if(!$sp = query_assoc($sql))
				return 'объекта не существует';

			$dialog = _dialogQuery($unit['num_1']);
			$cmp = $dialog['component'][$unit['num_3']];

			if($unit['num_2'] == 331)
				return $cmp['label_name'];

			if($unit['num_2'] == 332)
				return $sp[$cmp['col_name']];

			return 'spisok_id='.$spisok_id.' '.$unit['num_3']._pr($cmp);
		case 12://из функции напрямую
			if(!$unit['txt_1'])
				return 'пустое значение фукнции';
			if(!function_exists($unit['txt_1']))
				return 'фукнции не существует';
			return $unit['txt_1']();
		case 14: return _pageSpisok($unit); //_spisok
	}
	return 'неизвестный элемент='.$unit['dialog_id'];
}
function _pageElemStyle($r) {//стили css для элемента
	$send = array();

	//отступы
	$ex = explode(' ', $r['pad']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'padding:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

//	$send[] = 'box-sizing:padding-box';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _pageElemFontAllow($dialog_id) {//отображение в настройках стилей для конкретных элементов страницы
	$elem = array(
		10 => 1,
		11 => 1
	);
	return _num(@$elem[$dialog_id]);
}

function _pageElemMenu($unit) {//элемент страницы: Меню
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if($unit['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return 'Разделов нет.';

	$razdel = '';
	foreach($menu as $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';
		$razdel .=
			'<a class="link'.$sel.'" href="'.URL.'&p='.$r['id'].'">'.
				$r['name'].
			'</a>';
	}

	//Внешний вид меню
	$type = array(
		0 => 0,
		335 => 0, //Основной - горизонтальное меню
		336 => 1, //С подчёркиванием (гориз.)
		337 => 2, //Дополнительное на белом фоне (гориз.)
		339 => 3, //Дополнительное на сером фоне (гориз.)
		338 => 4  //Доп. - вертикальное
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';//._pr(_page());
}




function _pageSpisok($pe) {//список, выводимый на странице
	/*  dialog_id = 14
		txt_1 - сообщение пустого запроса
		txt_2[child] - для [182]: произвольное содержание
		txt_5 - текстовый массив настроенных колонок
			-1: num порядковый номер
			-2: dtime_add
			-3: иконки управления
			-4: произвольный текст
		num_1 - внешний вид списка: [181] => Таблица [182] => Шаблон
		num_2 - лимит, длина списка, показываемого за один раз
		num_3 - id диалога, через который вносятся данные списка
		num_4[child] - id колонки. Если отрицательное - см.txt_5
		num_5 - галочка "Показывать имена колонок"
		num_6 - галочка "Подсвечивать строки при наведении"
	*/
	$page_id = $pe['page_id'];

	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	$spLimit = $dv[$pe['num_2']];//лимит

	//диалог, через который вносятся данные списка
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);
	$CMP = $spDialog['component']; //элементы списка
	$spTable = $spDialog['base_table'];

	$cond = "!`deleted`";
	if(isset($spDialog['field']['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($spDialog['field']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;

	//получение данных списка
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE ".$cond."
			  "._pageSpisokFilterSearch($pe, $spDialog)."
			ORDER BY `dtime_add` DESC
			LIMIT ".$spLimit;
	$spisok = query_arr($sql);

	$html = '<div class="_empty">'._br($pe['txt_1']).'</div>';

	foreach($spisok as $id => $sp)
		if(empty($sp['num']))
			$spisok[$id]['num'] = $sp['id'];


	//выбор внешнего вида
	if($spisok)
		switch($pe['num_1']) {
			case 181://Таблица
				$html = '<table class="_stab">';

				$colArr = explode(',', $pe['txt_5']);
				//отображение названий колонок
				if($pe['num_5']) {
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
								$u = _viewer($sp['viewer_id_add']);
								$msg = 'Вн'.($u['sex'] == 2 ? 'ёс ' : 'есла ').$u['first_name'].' '.$u['last_name'];
								$html .= '<td class="w50 wsnw r grey fs12 curD'._tooltip($msg, -40).FullData($sp['dtime_add'], 0, 1);
								break;
							case -3://иконки управления
								$html .= '<td class="w15 wsnw">'.
											_iconEdit(array('onclick'=>'_dialogOpen('.$dialog_id.','.$sp['id'].')'));
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

									//если указана страница перехода после создания элемента списка
									if($spDialog['action_id'] == 2)
										$link = '&p='.$spDialog['action_page_id'].'&id='.$sp['id'];

									$v = '<a href="'.URL.$link.'">'.$v.'</a>';
								}
								$html .= '<td class="'.implode(' ', $cls).'">'.$v;
						}

	//						if(strlen($val) && $el['col_name'] == $CMP[$comp_id]['col_name'])
	//							$v = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $v, 1);
					}
				}

				$html .= '</table>';
				break;
			case 182://Шаблон
				//получение элементов шаблона
				$sql = "SELECT *
						FROM `_page_element`
						WHERE `app_id` IN(0,".APP_ID.")
						  AND `parent_id`=".$pe['id']."
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
				break;
			default:
				$html = 'Неизвестный внешний вид списка: '.$pe['num_1'];
		}

	return $html;
}
function _pageSpisokFilterSearch($pe, $spDialog) {//получение значений фильтра-поиска для списка
	//если поиск не производится ни по каким колонкам, то выход
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	//получение значения элемента поиска, содержащегося на странице, где находится список воздействующий на этот список
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `app_id` IN(0,".APP_ID.")
			  AND `page_id`=".$pe['page_id']."
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

function _pageSpisokUnit() {
	if(!$unit_id = _num(@$_GET['id']))
		return '';

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `id`=".$unit_id;
	if(!$unit = query_assoc($sql))
		return '<div class="fs10 pale">записи не существует</div>';

	return _pr($unit);
}



function _page_div() {//todo тест
	return 'test';
}








