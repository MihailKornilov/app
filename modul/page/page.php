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
				'<table class="_stab w100p bor-e8 bg-fff over1">'.
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
	if(ELEM_WIDTH_CHANGE && !_dialogParam($el['dialog_id'], 'element_width'))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
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
function _elemUnit($el, $unit=array()) {//формирование элемента страницы
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//значение из списка
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']]: '';
	$attr_id = 'cmp_'.$el['id'];
	$disabled = BLOCK_EDIT || ELEM_WIDTH_CHANGE || !empty($unit['choose']) ? ' disabled' : '';

	switch($el['width']) {
		case 0: $width = ' style="width:100%"'; break;
//		case -1: $width = ' style="width:100%"'; break;
		default: $width = ' style="width:'.$el['width'].'px"';
	}

	switch($el['dialog_id']) {
		//---=== КОМПОНЕНТЫ ДЛЯ ВНЕСЕНИЯ ДАННЫХ ===--- (используется $unit)
		//галочка
		case 1:
			/*
				txt_1 - текст для галочки
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
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
				txt_2 - текст по умолчанию
				num_1 - формат:
					38 - любой текст
					39 - цифры и числа
				num_2 - количество знаков после запятой
				num_3 - разрешать отрицательные значения
				num_4 - разрешать вносить 0
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$v = empty($v) ? $el['txt_2'] : $v;
			return '<input type="text" id="'.$attr_id.'"'.$width.$placeholder.$disabled.' value="'.$v.'" />';

		//Radio
		case 16:
			/*
				txt_1 - текст нулевого значения
				num_1 - горизонтальное положение
				значения из _element через dialog_id:19
			*/
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `dialog_id`=19
					  AND `block_id`=-".$el['id']."
					ORDER BY `sort`";
			$spisok = query_ass($sql);

			return _radio(array(
				'attr_id' => $attr_id,
				'light' => 1,
				'block' => !$el['num_1'],
				'interval' => 5,
				'value' => _num($v) ? _num($v) : $el['def'],
				'title0' => $el['txt_1'],
				'spisok' => $spisok,
				'disabled' => $disabled
			));

		//Select - произвольные значения
		case 17:
			/*
                txt_1 - текст нулевого значения
				значения из _element через dialog_id:19
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $el['def']
				   ));

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: наполнение для некоторых компонентов: radio, select, dropdown
		case 19:
			/*
				Все действия через JS.
				Данные хранятся в _element. В block_id пишется отрицательный id главного элемента.

				num_1 - использовать Описания значений

				Значения:
					id
					txt_1 - title
					txt_2 - content
					def
					sort
			*/

			return '<div class="_empty min">Наполнение компонента</div>';

		//Select - выбор списка, которые есть в приложении
		case 24:
			/*
                txt_1 - текст, когда список не выбран
				функция _dialogSpisokOn()
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Select - выбор списка из размещённых текущей странице
		case 27:
			/*
				списки размещаются диалогами 14(шаблон) и 23(таблица)
				идентификаторами результата являются id элементов (а не диалогов)

                txt_1 - текст, когда список не выбран
				функция _dialogSpisokOnPage()
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Select - выбор единицы из другого списка
		case 29:
			/*
				Для связки одного списка с другим
				Список нельзя связывать самого с собой

                num_1 - id диалога, через который вносятся данные выбираемого списка
                txt_1 - текст, когда единица не выбрана
				num_2 - возможность добавления новых значений
				num_3 - поиск значений вручную
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Count - количество
		case 35:
			/*
                num_1 - минимальное значение
                num_2 - максимальное значение
                num_3 - шаг
                num_4 - может быть отрицательным (галочка)
			*/
			return _count(array(
						'attr_id' => $attr_id,
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - выбор колонки таблицы
		case 37:
			/*
                num_1 - показывать имя таблицы перед выбором
			*/
			if($el['num_1'] && !empty($US)) {
				if($block = _blockQuery($US['block_id']))
					if($block['obj_name'] == 'dialog') //выбор имени колонки может производиться, только если элемент размещается в диалоге
						return
							'<table>'.
								'<tr><td class="pr3 b color-555">'._dialogParam($block['obj_id'], 'base_table').'.'.
									'<td>'._select(array(
												'attr_id' => $attr_id,
												'width' => $el['width']
										   )).
							'</table>';
			}

			return _select(array(
						'attr_id' => $attr_id,
						'width' => $el['width']
				   ));

		//SA: Select - выбор диалогового окна
		case 38:
			/*
                txt_1 - нулевое значение
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - значения из существующего селекта
		case 41:
			/*

			*/

			if(!$bs_id = _num(@$US['block_id']))
				return '<div class="red">Отсутствует ID исходного блока.</div>';

			$BL = _blockQuery($bs_id);
			if($BL['obj_name'] != 'dialog')
				return '<div class="red">Исходный блок не является блоком из диалога.</div>';

			if(!$EL = $BL['elem'])
				return '<div class="red">Отсутствует исходный элемент.</div>';

			if($EL['dialog_id'] != 17)
				return '<div class="red">Исходный элемент не является выпадающим полем.</div>';

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $EL['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $EL['def']
				   ));




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

			//вставка исходного блока для передачи как промежуточного значения, если кнопка расположена в диалоге
			$block = _num(@$US['block_id']) ? ',block_id:'.$US['block_id'] : '';
			//если кнопка расположена в диалоговом окне, то указывается id этого окна как исходное
			$dialog_source = $el['block']['obj_name'] == 'dialog' ? ',dialog_source:'.$el['block']['obj_id'] : '';
			return _button(array(
						'attr_id' => $attr_id,
						'name' => _br($el['txt_1']),
						'color' => $color[$el['num_1']],
						'width' => $el['width'],
						'small' => $el['num_2'],
						'class' => 'dialog-open',
						'val' => 'dialog_id:'.$el['num_4'].$block.$dialog_source
					));

		//Меню страниц
		case 3:
			/*
				num_1 - раздел (страница-родитель). В меню будут дочерние страницы
				num_2 - внешний вид:
						10 - Основной вид - горизонтальное меню
						11 - С подчёркиванием (гориз.)
						12 - Синие маленькие кнопки (гориз.)
						13 - Боковое вертикальное меню
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
						'v' => $el['v'],
						'disabled' => $disabled
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

		//SA: Функция PHP
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

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Список действий, привязанных к элементу
		case 22:
			if(!$bs_id = _num(@$US['block_id']))
				return _emptyMin('Отсутствует ID исходного блока.');

			$BL = _blockQuery($bs_id);

			if($BL['obj_name'] != 'dialog')
				return _emptyMin('Действия можно назначать<br>только компонентам в диалоговых окнах.');

			$sql = "SELECT *
					FROM `_element_func`
					WHERE `block_id`=".$bs_id."
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				return _emptyMin('Действий не назначено.');

			$spisok = '';
			foreach($arr as $r) {
				$c = count(_ids($r['target'], 1));
				$spisok .=
					'<dd val="'.$r['id'].'">'.
					'<table class="bs5 ml10 bor1 bg-gr2 over2 mb5 curD">'.
						'<tr>'.
							'<td class="w35 top">'.
								'<div class="icon icon-move-y pl"></div>'.
							'<td class="w230">'.
								'<div class="fs15">Показ-скрытие блоков</div>'.
								'<div class="fs12 ml20 mt3">1 - показать, 0 - скрыть</div>'.
								'<div class="fs12 ml20 pale">эффекта нет</div>'.
							'<td class="w100 b color-ref top center pt3">'.
								$c.' блок'._end($c, '', 'а', 'ов').
							'<td class="w50 r top">'.
								'<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',dialog_source:'.$el['block']['obj_id'].'" class="icon icon-edit pl dialog-open'._tooltip('Настроить действие', -60).'</div>'.
								_iconDel(array(
									'class' => 'pl ml5 dialog-open',
									'val' => 'dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',del:1,dialog_source:'.$el['block']['obj_id']
								)).
					'</table>'.
					'</dd>';
			}

			return '<dl class="mar10">'.$spisok.'</dl>';

		//Содержание единицы списка - таблица
		case 23:
			/*
                num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
				num_2 - длина (количество строк, выводимых за один раз)
				txt_1 - сообщение пустого запроса
				num_3 - узкие строки таблицы
				num_4 - подсвечивать строку при наведении мыши
				num_5 - показывать имена колонок
				txt_2 - ids элементов через запятую. Сами элементы хранятся в таблице _element

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
			if(!$UNIT_ISSET)
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
			/*
				num_2 - Что выбирать:
							40: любые элементы
							41: элементы, которые вносят данные
							42: элементы, по которым можно производить поиск
							43: блоки
				num_3 - выбор нескольких значений
			*/

			if(!$bs_id = _num(@$US['block_id']))
				return _emptyMin('Отсутствует ID исходного блока.');

			$BL = _blockQuery($bs_id);

			//вставка значения в единицу списка
			if($BL['obj_name'] == 'spisok')
				$BL = _blockQuery($BL['obj_id']);

			if(!$EL = $BL['elem'])
				return _emptyMin('Содержание диалога будет доступно<br>после вставки элемента в блок.');

			if($el['num_2'] == 43 && $BL['obj_name'] != 'dialog')
				return _emptyMin('Выбор блоков доступен только для диалогов.');

			//поиск id диалога, который следует выводить
			$dialog_id = 0;
			switch($EL['dialog_id']) {
				case 7://поиск
					if(!$EL['num_1'])
						return _emptyMin('Содержание диалога будет доступно после выбора списка,<br>по которому будет производиться поиск.');
					$sp = _elemQuery($EL['num_1']);
					$dialog_id = $sp['num_1'];
					break;
				case 14://список-ШАБЛОН
				case 23://список-ТАБЛИЦА
					if(!$dialog_id = $EL['num_1'])
						return _emptyMin('Содержание диалога будет доступно после выбора списка.');
					break;
			}

			if($el['num_2'] == 43)
				$dialog_id = $BL['obj_id'];

			if(!$dialog_id)
				return _emptyMin('Не найдено ID диалога, который вносит данные списка.');

			if(!$dialog = _dialogQuery($dialog_id))
				return _emptyMin('Диалога не существует, который вносит данные списка.');


			//поля, которые можно подсвечивать
			$choose_access = array();
			switch($el['num_2']) {
				case 40://любые элементы
					$choose_access = array('all'=>1);
					break;
				case 41: //элементы, которые вносят данные
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_is_insert`";
						$choose_access = query_ass($sql);
					break;
				case 42: //элементы, по которым можно производить поиск
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_search_access`";
						$choose_access = query_ass($sql);
					break;
				case 43: //блоки
					$choose_access = array('block'=>1);
					break;
			}

			//выделение уже выбранных полей, чтобы нельзя было их выбрать (для функций)
			$choose_deny = array();
			$dialogCur = _dialogQuery($el['block']['obj_id']);
			if($dialogCur['base_table'] == '_element_func') {
				$id = $UNIT_ISSET ? _num($unit['id']) : 0;
				$sql = "SELECT *
						FROM `_element_func`
						WHERE `block_id`=".$bs_id."
						  AND `id`!=".$id;
				if($arr = query_arr($sql))
					foreach($arr as $r)
						foreach(_ids($r['target'], 1) as $t)
							$choose_deny[$t] = 1;
			}


			$send = array(
				'choose' => 1,
				'choose_access' => $choose_access,
				'choose_sel' => _idsAss($v),       //ids ранее выбранных элементов или блоков
				'choose_deny' => $choose_deny      //ids элементов или блоков, которые выбирать нельзя (если они были выбраны другой фукцией того же элемента)
			);

			return
			'<div class="fs14 pad10 pl15 bg-gr2 line-b">Диалоговое окно <b class="fs14">'.$dialog['spisok_name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send);

		//Список действий для галочки
		case 28: return 28;

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка ТАБЛИЧНОГО содержания списка
		case 30:
			/*
				имя объекта: spisok
				 id объекта: block_id, в котором размещается список
			*/
			if(!$UNIT_ISSET)
				return '<div class="_empty min">Настройка таблицы будет доступна после вставки списка в блок.</div>';

			//все действия через JS
			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';

		//Выбор значения для ТАБЛИЦЫ (выводится окно для выбора)
		case 31:
			/*
				num_1 - id элемента, выбранного из диалога, который вносит данные списка (через dialog_id=26)
			*/
			return '31';

		//Значение списка: порядковый номер
		case 32: return 'Порядковый номер используется только в списках';

		//Значение списка: дата
		case 33:
			/*
				num_1 - формат:
					35: 5 августа 2017
					36: 5 авг 2017
					37: 05/08/2017
				num_2 - не показывать текущий год
				num_3 - имена у ближайших дней:
					позавчера
					вчера
					сегодня
					завтра
					послезавтра
			*/
			return 'Дата используется только в списках';

		//Назначение действия для галочки: скрытие/показ блоков
		case 36:
			/*
				таблица _element_func
					action_id - действие
					effect_id - эффекты
						44 - изчезновение/появление
						45 - сворачивание/разворачивание
					target - id блоков, на которые воздействует галочка
			*/
			return 36;
	}

	return'неизвестный элемент='.$el['dialog_id'];
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
		10 => 0,//Основной вид - горизонтальное меню
		11 => 1,//С подчёркиванием (гориз.)
		12 => 2,//Синие маленькие кнопки (гориз.)
		13 => 3 //Боковое вертикальное меню
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';
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

	'<button class="vk mar20" id="bbb">Кнопка для сохранения</button>'.

	'<br>'.
	'<br>'.
	'<br>'.
	'<div class="w200">'.
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







