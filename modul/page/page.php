<?php
function _page($i='all', $id=0) {//получение данных страницы
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
		if(!$page_id = _num($id))
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
			FROM `_page_element`
			WHERE `page_id` IN ("._idsGet($page).")
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
function _pageSetupAppPageSpisok($arr, $sort) {
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1" id="item_'.$r['id'].'">'.
			'<div class="curM">'.
				'<table class="_stab  bor-e8 bg-fff over1">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok ml10 mbm5 curD'._tooltip('Страница по умолчанию', -76).'</div>' : '').
						'<td class="w50 wsnw">'.
							'<div onclick="_dialogOpen('.$r['dialog_id'].','.$r['id'].')" class="icon icon-edit'._tooltip('Изменить название', -58).'</div>'.
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
	'<script>'.
		'var BLOCK_ARR={'._blockJS('page', $page_id).'},'.
			'ELEM_COLOR={'._elemColor().'};'.
	'</script>'.
	(!PAS ? '<script>_pageShow()</script>' : '');
}
function _elemDiv($el) {//формирование div элемента
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
		_elemUnit($el).
	'</div>';

}
function _elemStyle($r) {//стили css для элемента
	$send = array();

	//отступы
	$ex = explode(' ', $r['mar']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'margin:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _elemUnit($el) {//формирование элемента страницы
	if(!$el)
		return '';
	switch($el['dialog_id']) {
		case 2://button
			/*
				txt_1 - текст кнопки
				num_1 - цвет
				num_2 - маленькая кнопка
				num_3 - максимальная ширина
			*/
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
						'name' => $el['txt_1'],
						'click' => '_dialogOpen('._dialogValToId('button'.$el['id']).')',
						'color' => $color[$el['num_1']],
						'small' => $el['num_2'],
						'class' => $el['num_3'] ? 'w100p' : ''
					));
		case 3: return _pageElemMenu($el); //menu
		case 4: return '<div class="hd2">'.$el['txt_1'].'</div>'; //head
		case 5://textarea
			/*
				num_1 - ширина
			*/
			return '<textarea style="width:'.$el['num_1'].'px"></textarea>';
		case 7://search
			return _search(array(
						'hold' => $el['txt_1'],
						'width' => $el['num_2'],
						'v' => $el['v']
					));
		case 9://link
			return '<a href="'.URL.'&p='.$el['num_1'].'">'.
						$el['txt_1'].
				   '</a>';
		case 10: return _br($el['txt_1']);//произвольный текст
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

			if(!$el['num_3'])
				return 'нулевое значение компонента';

			$sql = "SELECT *
					FROM `_spisok`
					WHERE `app_id` IN (0,".APP_ID.")
					  AND `id`=".$spisok_id;
			if(!$sp = query_assoc($sql))
				return 'объекта не существует';

			$dialog = _dialogQuery($el['num_1']);
			$cmp = $dialog['component'][$el['num_3']];

			if($el['num_2'] == 331)
				return $cmp['label_name'];

			if($el['num_2'] == 332)
				return $sp[$cmp['col_name']];

			return 'spisok_id='.$spisok_id.' '.$el['num_3']._pr($cmp);
		case 12://из функции напрямую
			if(!$el['txt_1'])
				return 'пустое значение фукнции';
			if(!function_exists($el['txt_1']))
				return 'фукнции не существует';
			return $el['txt_1']();
		case 14: return _spisokShow($el); //содержание списка
		case 15: return _spisokElemCount($el);//текст с количеством строк списка
	}

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
	return'неизвестный элемент='.$el['dialog_id'];
}
function _elemFontAllow($dialog_id) {//отображение в настройках стилей для конкретных элементов страницы
	$elem = array(
		0 => 1,
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
	'<div class="mar20 bor-e8 w200 pad20" id="for-hint">'.
		'Передний текст '.
		'<div class="icon icon-edit"></div>'.
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
		'<div class="icon icon-hint"></div>'.
		' Попутный текст'.
	'</div>'.

	'<button class="vk mar20" id="bbb">Кнопка для сохранения</button>'.

	'<div id="aaa">0</div>'.
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







