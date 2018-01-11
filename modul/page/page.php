<?php
function _pageCache() {//получение массива страниц из кеша
	if($arr = _cache())
		return $arr;

	$sql = "SELECT
				*,
				0 `block_count`,
				0 `elem_count`,
				1 `del_access`
			FROM `_page`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			ORDER BY `sort`";
	if(!$page = query_arr($sql))
		return array();

	//получение количества блоков по каждой странице
	$sql = "SELECT
				`obj_id`,
				COUNT(*) `c`
			FROM `_block`
			WHERE `obj_name`='page'
			  AND `obj_id` IN ("._idsGet($page).")
			GROUP BY `obj_id`";
	$block = query_ass($sql);

	//получение количества элементов по каждой странице
	$sql = "SELECT
				`page_id`,
				COUNT(*) `c`
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($block).")
			GROUP BY `page_id`";
	$elem = query_ass($sql);

	foreach($page as $id => $r) {
		$block_count = _num(@$block[$id]);
		$elem_count = _num(@$elem[$id]);
		$page[$id]['block_count'] = $block_count;
		$page[$id]['elem_count'] = $elem_count;
		$page[$id]['del_access'] = $block_count || $elem_count ? 0 : 1;
	}

	return _cache($page);
}
function _page($i='all', $i1=0) {//получение данных страницы
	if(!defined('APP_ID'))
		return 0;

	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//id страницы по умолчанию, либо из $_GET
	if($i == 'cur') {
		if($page_id = _num(@$_GET['p'])) {
			if(!isset($page[$page_id]))
				return 0;
			return $page_id;
		}

		foreach($page as $p) {
			if(!$p['def'])
				continue;

			if(!SA && $p['sa'])
				continue;

			return $p['id'];
		}

		//иначе на список страниц
		return 12;
	}

	//является ли страница родительской относительно текущей
	if($i == 'is_cur_parent') {
		if(!$page_id = _num($i1))
			return false;
		$cur = _page('cur');

		//проверяемая страница совпадает с текущей
		if($page_id == $cur)
			return true;

		//текущая страница сама является главной
		if(!$cur_parent = _num($page[$cur]['parent_id']))
			return false;

		//проверяемая страница является родителем текущей
		if($page_id == $cur_parent)
			return true;

		//проверяемая страница является про-родителем текущей
		if($page_id == $page[$cur_parent]['parent_id'])
			return true;

		return false;
	}

	//список страниц для select
	if($i == 'for_select') {
		$child = array();
		foreach($page as $id => $r) {
			if(!$r['parent_id'])
				continue;

			if(empty($child[$r['parent_id']]))
				$child[$r['parent_id']] = array();

			$child[$r['parent_id']][] = $r;
			unset($page[$id]);
		}
		$send = _pageChildArr($page, $child);
		if(SA) {
			$send[] = array(
				'title' => utf8('Страницы SA'),
				'info' => 1
			);
			foreach(_pageSaForSelect($page, $child) as $r)
				$send[] = $r;
		}

		if($i1 == 'js')
			return json_encode($send);

		return $send;
	}

	//данные конкретной страницы
	if($page_id = _num($i)) {
		if(!isset($page[$page_id]))
			return false;
		return $page[$page_id];
	}

	//значение текущей страницы
	if($page_id = _page('cur')) {
		if(!isset($page[$page_id]))
			return false;
		if(!isset($page[$page_id][$i]))
			return false;
		return $page[$page_id][$i];
	}

	return false;
}
function _pageChildArr($arr, $child, $level=0) {//перечисление иерархии страниц для select
	$send = array();
	foreach($arr as $r) {
		if($r['sa'])
			continue;
		if(!$r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))),
			'content' => '<div class="fs'.(14-$level).' '.($level ? 'ml'.($level*20) : 'b').'">'.utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))).'</div>'
		);
		if(!empty($child[$r['id']]))
			foreach(_pageChildArr($child[$r['id']], $child, $level+1) as $sub)
				$send[] = $sub;
	}

	return $send;
}
function _pageSaForSelect($arr, $child) {//страницы SA для select
	$send = array();
	foreach($arr as $r) {
		if(!$r['sa'] && $r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))),
			'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))).'</div>'
		);
		if(!empty($child[$r['id']]))
			foreach(_pageSaForSelect($child[$r['id']], $child) as $sub)
				$send[] = $sub;
	}

	return $send;

}

function _pageSetupDefine() {//установка флага включения управления страницей PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//страница существует
		if($page = _page($page_id))//данные страницы получены
			if(!($page['sa'] && !SA))
				if(!(!$page['app_id'] && !SA))
					$pas = _bool(@$_COOKIE['page_setup']);

	define('PAS', $pas);
}
function _pageSetupAppPage() {//управление страницами приложения
	$arr = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		$arr[$id] = $r;
	}

	if(empty($arr))
		return
		'<div class="_empty">'.
			'Ещё не создано ни одной страницы.'.
			'<div class="mt10 fs15 black">Создайте первую!</div>'.
		'</div>';

	$sort = array();
	foreach($arr as $id => $r)
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		}

	return
	'<style>'.
		'.placeholder{outline:1px dashed #4183C4;margin-top:1px}'.
		'ol{list-style-type:none;max-width:700px;padding-left:40px}'.
	'</style>'.
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>'.
	'<script>_pageSetupAppPage()</script>';
}
function _pageSetupAppPageSpisok($arr, $sort) {//список страниц приложения
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1" id="item_'.$r['id'].'">'.
			'<div class="curM">'.
				'<table class="_stab  bor-e8 bg-fff over1">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok fr curD'._tooltip('Страница по умолчанию', -76).'</div>' : '').
						'<td class="w35 wsnw">'.
							'<div val="dialog_id:20,unit_id:'.$r['id'].'" class="icon icon-edit dialog-open'._tooltip('Изменить название', -58).'</div>'.
	   (!$r['del_access'] ? '<div class="icon icon-off'._tooltip('Очистить', -29).'</div>' : '').
		($r['del_access'] ? '<div onclick="_dialogOpen(6,'.$r['id'].')" class="icon icon-del-red'._tooltip('Страница пустая, удалить', -79).'</div>' : '').
				'</table>'.
			'</div>';
		if(!empty($sort[$r['id']]))
			$send .= '<ol>'._pageSetupAppPageSpisok($sort[$r['id']], $sort).'</ol>';
	}

	return $send;
}
function _pageSetupMenu() {//строка меню управления страницей
	if(!APP_ID)
		return '';
	if(!PAS)
		return '';

	return
	'<div id="pas">'.
		'<div class="w1000 mara pad5">'.
			'<div class="dib fs16 b">'._page('name').	'</div>'.
		'</div>'.
		'<div class="w1000 mara pad5">'.
			_blockLevelChange('page', _page('cur')).
		'</div>'.
	'</div>';
}

function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	return
	_blockHtml('page', $page_id).
//	_page_div().
	'<script>'.
		'var PAGE_LIST='._page('for_select', 'js').','.
			'BLOCK_ARR='._blockJS('page', $page_id).','.
			'ELEM_COLOR={'._elemColor().'};'.
	'</script>'.
	'<script>_pageShow('.PAS.')</script>';
}
function _elemDiv($el, $unit=array()) {//формирование div элемента
	if(!$el)
		return '';

	$tmp = !empty($el['tmp']);//элемент списка шаблона
	$attr_id = $tmp ? '' : ' id="pe_'.$el['id'].'"';

	$cls = array();
	$cls[] = $el['color'];
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = implode(' ', $cls);
	$cls = $cls ? ' class="'.$cls.'"' : '';


	return
	'<div'.$attr_id.$cls._elemStyle($el).'>'.
		_elemUnit($el, $unit).
	'</div>';

}
function _elemStyle($el) {//стили css для элемента
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
	if(ELEM_WIDTH_CHANGE && !_elemWidth($el['dialog_id']))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _elemWidth($dialog_id, $i='access') {//получение информации о ширине элементов
	/*
		   def: ширина по умолчанию (для внесения нового элемента)
		   min: минимальная ширина, которую можно установить элементу
		access: определение элементов, у которых может настраиваться ширина
	*/

	if($i == 'def')
		switch($dialog_id) {
			case 5:  return 150;//textarea
			case 6:  return 150;//select - выбор страницы
			case 7:  return 150;//search
			case 8:  return 150;//input:text
			case 17: return 150;//select - произвольные значения
			case 24: return 150;//select - выбор списка
			case 27: return 150;//select - выбор списка, размещённого на текущей странице
			case 29: return 150;//select - выбор единицы из другого списка (для связки)
			default: return 0;
		}

	if($i == 'min')
		switch($dialog_id) {
			case 2:  return 30;//button
			case 5:  return 30;//textarea
			case 6:  return 50;//select - выбор страницы
			case 7:  return 70;//search
			case 8:  return 30;//input:text
			case 17: return 50;//select - произвольные значения
			case 24: return 70;//select - выбор списка
			case 27: return 70;//select - выбор списка, размещённого на текущей странице
			case 29: return 70;//select - выбор единицы из другого списка (для связки)
			default: return 0;
		}

	//$i == 'access'
	switch($dialog_id) {
		case 2:  //кнопка
		case 5:  //textarea
		case 6:  //select - выбор страницы
		case 7:  //search
		case 8:  //input:text
		case 17: //select - произвольные значения
		case 24: //select - выбор списка
		case 27: //select - выбор списка, размещённого на текущей странице
		case 29: //select - выбор единицы из другого списка (для связки)
		case 0: return 1;
		default: return 0;
	}
}
function _elemUnit($el, $unit=array()) {//формирование элемента страницы
	$unitExist = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//значение из списка
	$v = $unitExist && $el['col'] ? $unit[$el['col']]: '';
	$attr_id = 'cmp_'.$el['id'];
	$disabled = ELEM_WIDTH_CHANGE ? ' disabled' : '';

	switch($el['width']) {
		case 0: $width = ' style="width:100%"'; break;
//		case -1: $width = ' style="width:100%"'; break;
		default: $width = ' style="width:'.$el['width'].'px"';
	}

	switch($el['dialog_id']) {
		//---=== КОМПОНЕНТ ДЛЯ ВНЕСЕНИЯ ДАННЫХ ===--- (используется $unit)
		//галочка
		case 1:
			/*
				txt_1 - текст для галочки
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'value' => _num($v)
			));

		//textarea (многострочное текстовое поле)
		case 5:
			/*
				txt_1 - текст для placeholder
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			return
			'<textarea id="'.$attr_id.'"'.$width.$placeholder.$disabled.'>'.
				$v.
			'</textarea>';

		//Select - выбор страницы
		case 6:
			/*
                txt_1 - текст, когда страница не выбрана
				функция _page('for_select', 'js')
			*/
			return '<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />';

		//input:text (однострочное текстовое поле)
		case 8:
			/*
				txt_1 - текст для placeholder
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			return '<input type="text" id="'.$attr_id.'"'.$width.$placeholder.$disabled.' value="'.$v.'" />';

		//Radio
		case 16:
			/*
				txt_1 - текст нулевого значения
				v - наполнение из таблицы _element_value через dialog:19
			*/
			$value = _num($v);
			$spisok = array();
			$sql = "SELECT *
					FROM `_element_value`
					WHERE `dialog_id`=".$el['dialog_id']."
					  AND `element_id`=".$el['id']."
					ORDER BY `sort`";
			foreach(query_arr($sql) as $id => $r) {
				$spisok[$id] = $r['title'];
				if(!$value && $r['def'])
					$value = $r['id'];
			}
			return _radio(array(
				'attr_id' => $attr_id,
				'light' => 1,
				'interval' => 5,
				'value' => $value,
				'title0' => $el['txt_1'],
				'spisok' => $spisok
			));

		//Select - произвольные значения
		case 17:
			/*
                txt_1 - текст нулевого значения
				v - наполнение из таблицы _element_value через dialog:19
			*/
			if(!$value = _num($v)) {
				$block = $el['block'];
				if($block['obj_name'] == 'dialog') {
					$dialog = _dialogQuery($block['obj_id']);
					$value = $dialog['cmp'][$el['id']]['elv_def'];
				} else {
					$sql = "SELECT *
							FROM `_element_value`
							WHERE `dialog_id`=".$el['dialog_id']."
							  AND `element_id`=".$el['id']."
							ORDER BY `sort`";
					foreach(query_arr($sql) as $id => $r) {
						if(!$value && $r['def'])
							$value = $r['id'];
					}
				}
			}
			return '<input type="hidden" id="'.$attr_id.'" value="'.$value.'" />';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: наполнение для некоторых компонентов: radio, select, dropdown
		case 19: return '<div class="_empty min">Наполнение компонента</div>'; //все действия через JS

		//Select - выбор списка, которые есть в приложении
		case 24:
			/*
                txt_1 - текст, когда список не выбран
				функция _dialogSpisokOn()
			*/
			return '<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />';

		//Select - выбор списка из размещённых текущей странице
		case 27:
			/*
				списки размещаются диалогами 14(шаблон) и 23(таблица)
				идентификаторами результата являются id элементов (а не диалогов)

                txt_1 - текст, когда список не выбран
				функция _dialogSpisokOnPage()
			*/
			return '<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />';

		//Select - выбор единицы из другого списка
		case 29:
			/*
				Для связки одного списка с другим
				Список нельзя связывать самого с собой

                num_1 - id диалога, через который вносятся данные выбираемого списка
                txt_1 - текст, когда единица не выбрана
			*/
			return '<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />';




		//---=== ЭЛЕМЕНТЫ ДЛЯ ОТОБРАЖЕНИЯ ===---
		//button
		case 2:
			/*
				txt_1 - текст кнопки
				num_1 - цвет
				num_2 - маленькая кнопка
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
			return _button(array(
						'attr_id' => $attr_id,
						'name' => _br($el['txt_1']),
						'color' => $color[$el['num_1']],
						'width' => $el['width'],
						'small' => $el['num_2'],
						'class' => 'dialog-open'.($el['num_3'] ? ' w100p' : ''),
						'val' => 'dialog_id:'.$el['num_4']
					));

		//Меню страниц
		case 3:
			/*
				num_1 - раздел (страница-родитель). В меню будут дочерние страницы
				num_2 - внешний вид:
						16 - Основной вид - горизонтальное меню
						17 - С подчёркиванием (гориз.)
						18 - Синие маленькие кнопки (гориз.)
						19 - Боковое вертикальное меню
			*/
			return _pageElemMenu($el);

		//Заголовок
		case 4:
			/*
                txt_1 - текст заголовка
			*/
			return '<div class="hd2">'.$el['txt_1'].'</div>';

		//Поиск
		case 7:
			/*
                txt_1 - текст поиска
				num_1 - id элемента, содержащего список, по которому происходит поиск
				txt_2 - по каким полям производить поиск (id элементов через запятую диалога списка)
			*/
			return _search(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'v' => $el['v']
					));

		//Ссылка на страницу
		case 9:
			/*
                txt_1 - текст ссылки
				num_1 - id страницы
			*/
			if(!$txt = $el['txt_1']) {
				$page = _page($el['num_1']);
				$txt = $page['name'];
			}
			return '<a class="inhr" href="'.URL.'&p='.$el['num_1'].'">'.
						$txt.
				   '</a>';

		//произвольный текст
		case 10:
			/*
                txt_1 - текст
			*/
			return _br($el['txt_1']);

		//Выбор значения для шаблона (выводится окно для выбора)
		case 11:
			/*
				num_1 - id элемента, выбранного из диалога, который вносит данные списка (через dialog_id=26)
				num_2 - является ссылкой
			*/

			if(isset($el['txt_real']))
				return $el['txt_real'];

			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$el['num_1'];
			if(!$elem = query_assoc($sql))
				return 'элемент отсутствует</div>';

			switch($elem['dialog_id']) {
				case 8: return 'текстовое значение';
				case 10: return $elem['txt_1'];
			}

			return 'значение не доделано';

		//Функция PHP
		case 12:
			/*
                txt_1 - имя функции
			*/
			if(!$el['txt_1'])
				return 'пустое значение фукнции';
			if(!function_exists($el['txt_1']))
				return 'фукнции не существует';
			return $el['txt_1']();

		//Содержание единицы списка - шаблон
		case 14:
			/*
                num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
				num_2 - длина (количество строк, выводимых за один раз)
				txt_1 - сообщение пустого запроса

				настройка шаблона через вспомогательный элемент: dialig_id=25
			*/
			if(PAS) {
				$dialog = _dialogQuery($el['num_1']);
				return '<div class="_empty">Список <b class="fs14">'.$dialog['spisok_name'].'</b></div>';
			}

			return _spisokShow($el);

		//Количество строк списка
		case 15:
			/*
                num_1 - id элемента, содержащего список, количество строк которого нужно выводить
				txt_1 "1" txt_2 - показана "1" запись
				txt_3 "2" txt_4 - показано "2" записи
				txt_5 "5" txt_6 - показано "5" записей
			*/
			return _spisokElemCount($el);

		//Информационный блок
		case 21:
			/*
                txt_1 - содержание
			*/
			return '<div class="_info">'._br($el['txt_1']).'</div>';

		//Содержание единицы списка - таблица
		case 23:
			/*
                num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
				num_2 - длина (количество строк, выводимых за один раз)
				txt_1 - сообщение пустого запроса
				num_3 - узкие строки таблицы
				num_4 - подсвечивать строку при наведении мыши

				настройка шаблона через вспомогательный элемент: dialig_id=30
			*/
			if(PAS) {
				$dialog = _dialogQuery($el['num_1']);
				return '<div class="_empty">Список-таблица <b class="fs14">'.$dialog['spisok_name'].'</b></div>';
			}

			return _spisokShow($el);

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка ШАБЛОНА единицы списка
		case 25:
			/*
				имя объекта: spisok
				 id объекта: block_id, в котором размещается список
			*/
			if(!$unitExist)
				return
				'<div class="bg-ffe pad10">'.
					'<div class="_empty min">'.
						'Настройка шаблона будет доступна после вставки списка в блок.'.
					'</div>'.
				'</div>';

			//определение ширины шаблона
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$unit['block_id'];
			if(!$block = query_assoc($sql))
				return 'Блока, в котором находится список, не существует.';

			setcookie('block_level_spisok', 1, time() + 2592000, '/');
			$_COOKIE['block_level_spisok'] = 1;

			//корректировка ширины с учётом отступов
			$ex = explode(' ', $unit['mar']);
			$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
			$line_r = $width < 980 ? ' line-r' : '';

			return
				'<div class="bg-ffc pad10 line-b">'.
					_blockLevelChange('spisok', $unit['block_id'], $width).
				'</div>'.
				'<div class="block-content-spisok'.$line_r.'" style="width:'.$width.'px">'._blockHtml('spisok', $unit['block_id'], $width).'</div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Содержание диалога для выбора значения
		case 26:
			if($unitExist) {
				//получение исходного блока, если элемент был вставлен ранее
				$sql = "SELECT *
						FROM `_element`
						WHERE `id`=".$unit['id'];
				if(!$elemSource = query_assoc($sql))
					return '<div class="_empty min mar10">Отсутствует исходный элемент.</div>';

				$US['block_id'] = $elemSource['block_id'];
			}
			if(empty($US['block_id']))
				return '<div class="_empty min mar10">Отсутствует исходный блок.</div>';

			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$US['block_id'];
			if(!$blockSource = query_assoc($sql))
				return '<div class="_empty min mar10">Исходного блока id'.$US['block_id'].' не существует.</div>';

			if($blockSource['obj_name'] != 'spisok')
				return '<div class="_empty min mar10">Исходный блок не является блоком шаблона для списка.</div>';

			$sql = "SELECT *
					FROM `_element`
					WHERE `block_id`=".$blockSource['obj_id']."
					  AND `dialog_id`=14";
			if(!$elem = query_assoc($sql))
				return '<div class="_empty min mar10">Элемента не существует, который размещает список.</div>';

			if(!$dialog = _dialogQuery($elem['num_1']))
				return '<div class="_empty min mar10">Диалога не существует, который вносит данные списка.</div>';

			$send = array(
				'choose' => 1,
				'choose_sel' => _num($v)
			);

			return
			'<div class="hd2 ml10 mr10">Диалоговое окно <b class="fs16">'.$dialog['spisok_name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />'.
			_blockHtml('dialog', $elem['num_1'], $dialog['width'], 0, $send);

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Содержание диалога для указания значений, по которым будет производиться поиск
		case 28:
			if(!$unitExist || !$unit['num_1'])
				return '<div class="_empty min mar10">'.
							'Выбор полей, по которым производится поиск,'.
							'<br>'.
							'будет доступен после выбора списка'.
							'<br>'.
							'и вставки элемента поиска в блок.'.
						'</div>';

			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$unit['num_1'];
			if(!$elemSource = query_assoc($sql))
				return '<div class="_empty min mar10">Отсутствует элемент, который содержит список.</div>';

			if(!$dialog_id = $elemSource['num_1'])
				return '<div class="_empty min mar10">Нужный список ещё не был вставлен в блок.</div>';

			if(!$dialog = _dialogQuery($dialog_id))
				return '<div class="_empty min mar10">Диалога не существует, который вносит данные списка.</div>';


			$send = array(
				'choose' => 1,                      //флаг подсветки элементов
				'choose_search' => 1,               //флаг выбора полей поиска
				'choose_access' => _idsAss('5,8'),  //поля, которые можно подсвечивать
				'choose_sel' => _idsAss($v)            //id выбранных элементов
			);

			return
			'<div class="hd1">Диалоговое окно <b class="fs16">'.$dialog['spisok_name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send);

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка ТАБЛИЧНОГО содержания списка
		case 30:
			/*
				имя объекта: spisok
				 id объекта: block_id, в котором размещается список
			*/
			if(!$unitExist)
				return '<div class="_empty min">Настройка таблицы будет доступна после вставки списка в блок.</div>';

			 //все действия через JS
			return '<div class="_empty min">Подключение настройки таблицы...</div>';
	}
/*
	//элементы списка шаблона (для настройки)
	if($el['block']['obj_name'] == 'spisok') {
		if(isset($el['real_txt']))
			return $el['real_txt'];
		switch($el['num_1']) {
			case -1: return '{NUM}';//порядковый номер
			case -2: return FullData(curTime(), 0, 1);//дата внесения
			case -4: return _br($el['txt_2']);//произвольный текст
			default:
				if(!$dialog = _dialogQuery($el['num_3']))
					return 'неизвестный id диалога списка: '.$el['num_3'];
				$cmp = $dialog['component'];
				$label_name = $cmp[$el['num_1']]['label_name'];
				switch($el['num_2']) {
					case 1: return $label_name;//название колонки
					case 2: return 'Значение "'.$label_name.'"';//значение колонки
					default: return 'неизвестный тип содержания колонки';
				}
		}
	}
*/
	return'неизвестный элемент='.$el['dialog_id'];
}
function _elemFontAllow($dialog_id) {//отображение в настройках стилей для конкретных элементов страницы
	$elem = array(
		0 => 1,
		9 => 1,
		10 => 1,
		11 => 1,
		15 => 1
	);
	return _num(@$elem[$dialog_id]);
}
function _elemColor() {//массив цветов для текста в формате JS, доступных элементам
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

function _pageElemMenu($unit) {//элемент dialog_id=3: Меню страниц
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
		16 => 0,//Основной вид - горизонтальное меню
		17 => 1,//С подчёркиванием (гориз.)
		18 => 2,//Синие маленькие кнопки (гориз.)
		19 => 3 //Боковое вертикальное меню
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';
}
















function _pageSpisokUnit() {//todo для тестов
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
	return
	'<div class="mar20 bor-e8 pad20" id="for-hint">'.
		'Передний текст '.
		'<div class="icon icon-edit"></div>'.
		'<div class="icon spin pl wh"></div>'.
		'<div class="icon icon-del"></div>'.
		'<div class="icon icon-del-red"></div>'.
		'<div class="icon icon-add"></div>'.
		'<div class="icon icon-ok"></div>'.
		'<div class="icon icon-set"></div>'.
		'<div class="icon icon-set-b"></div>'.
		'<div class="icon icon-off"></div>'.
		'<div class="icon icon-offf"></div>'.
		'<div class="icon icon-doc-add"></div>'.
		'<div class="icon icon-order"></div>'.
		'<div class="icon icon-client"></div>'.
		'<div class="icon icon-worker"></div>'.
		'<div class="icon icon-vk"></div>'.
		'<div class="icon icon-rub"></div>'.
		'<div class="icon icon-usd"></div>'.
		'<div class="icon icon-stat"></div>'.
		'<div class="icon icon-print"></div>'.
		'<div class="icon icon-out"></div>'.
		'<div class="icon icon-chain"></div>'.
		'<div class="icon icon-set-dot"></div>'.
		'<div class="icon icon-move"></div>'.
		'<div class="icon icon-move-x"></div>'.
		'<div class="icon icon-move-y"></div>'.
		'<div class="icon icon-sub"></div>'.
		'<div class="icon icon-join"></div>'.
		'<div class="icon icon-info"></div>'.
		'<div class="icon icon-search"></div>'.
		'<div class="icon icon-hint"></div>'.
		' Попутный текст'.
	'</div>'.

	_pr(_page('all', 'js')).

	'<button class="vk mar20" id="bbb">Кнопка для сохранения</button>'.

	'<br>'.
	'<br>'.
	'<br>'.
	'<div class="bg-fcc w200">'.
		'<input type="hidden" class="aaa" />'.
	'</div>'.

	'<div class="bg-ddf w500 mt10">'.
		'<div class="_select dib bg-ffc w200 prel">'.
			'<table class="w100p">'.
				'<tr><td>456'.
			'</table>'.
		'</div>'.
		'<div class="_select  bg-ffc w200 prel">'.
			'<table>'.
				'<tr><td>456'.
			'</table>'.
		'</div>'.
	'</div>'.
//	'<div><input type="text" /></div>'.

	'<div class="mar20 bg-ccd">'.
		'<div class="icon icon-edit wh"></div>'.
		'<div class="icon icon-del wh"></div>'.
	'</div>';
}
function gridStackStyleGen() {//генерирование стилей для gridstack
	$step = 50;    //шаг сетки по горизонтали
	$send = '';
	$w = round(100 / $step, 10);//ширина шага в процентах
	$next = $w;
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-stack-item[data-gs-width='".$n."'] {width:".$next."%}<br>".
			".grid-stack-item[data-gs-x='".$n."']     {left:".$next."%}<br>".
			".grid-stack-item[data-gs-min-width='".$n."'] {min-width:".$next."%}<br>".
			".grid-stack-item[data-gs-max-width='".$n."'] {max-width:".$next."%}<br>".
			"<br>";
		$next = round($next + $w, 10);
	}

	return $send;
}
function gridStackStylePx() {//генерирование стилей для grid-child
	$step = 100;    //шаг сетки по горизонтали
	$send = '';
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-child-item[data-gs-width='".$n."']{width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-x='".$n."']{left:".($n*10)."px}<br>".
			".grid-child-item[data-gs-min-width='".$n."']{min-width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-max-width='".$n."']{max-width:".($n*10)."px}<br>".
			"<br>";
	}

	return $send;
}







