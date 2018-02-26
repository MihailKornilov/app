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

	foreach($page as $id => $r) {
		$block_count = _num(@$block[$id]);
		$page[$id]['del_access'] = $block_count || $r['common_id'] ? 0 : 1;
	}

	return _cache($page);
}
function _page($i='all', $i1=0) {//получение данных страницы
	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//id текущей страницы
	if($i == 'cur') {
		if($page_id = _num(@$_GET['p'])) {
			if(!isset($page[$page_id]))
				return 0;
			if($page[$page_id]['common_id'])
				return $page[$page_id]['common_id'];
			return $page_id;
		}
		$i = 'def';
	}

	//id страницы по умолчанию
	if($i == 'def') {
		//список приложений, если пользователь не вошёл в приложение
		if(!APP_ID)
			return 98;

		//сначала поиск страницы приложения
		foreach($page as $p)
			if(!$p['sa'] && $p['def'])
				return $p['id'];

		//затем страницы SA
		if(SA)
			foreach($page as $p)
				if($p['sa'] && $p['def'])
					return $p['id'];

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

	//получение страницы, которая принимает значения списка
	//  $i1 - id диалога, который вносит данные этого списка
	if($i == 'spisok_id') {
		if(!$dialog_id = _num($i1))
			return 0;
		foreach($page as $id => $r) {
			if($r['spisok_id'] == $dialog_id)
				return $id;
		}
		return 0;
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

function _pasDefine() {//установка флага включения управления страницей PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//страница существует
		if($page = _page($page_id))//данные страницы получены
			if(!($page['sa'] && !SA))
				if(!(!$page['app_id'] && !SA))
					$pas = _bool(@$_COOKIE['page_setup']);

	define('PAS', APP_ID && $pas);
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
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>';
}
function _pageSetupAppPageSpisok($arr, $sort) {//список страниц приложения
	if(empty($arr))
		return '';
//mjs-nestedSortable-branch
	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1'.(!$r['parent_id'] ? ' pb10' : '').'" id="item_'.$r['id'].'">'.
			'<div>'.
				'<table class="_stab w100p bor-e8 bg-fff">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok fr curD'._tooltip('Страница по умолчанию', -76).'</div>' : '').
						'<td class="w35 wsnw">'.
							'<div class="icon icon-move pl"></div>'.
							'<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].'" class="icon icon-edit pl dialog-open'._tooltip('Изменить название', -58).'</div>'.
		($r['del_access'] ? '<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',del:1" class="icon icon-del-red dialog-open'._tooltip('Страница пустая, удалить', -79).'</div>'
						  : '<div class="icon icon-empty"></div>'
		).
				'</table>'.
			'</div>';
		if(!empty($sort[$r['id']]))
			$send .= '<ol>'._pageSetupAppPageSpisok($sort[$r['id']], $sort).'</ol>';
	}

	return $send;
}
function _pasMenu() {//строка меню управления страницей
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
//	_block('page', $page_id, 'block_js').
//	_pr(_block('page', $page_id, 'elem_arr')).
	_blockHtml('page', $page_id, 1000, 0, _pageSpisokUnit($page_id)).
//	_page_div().
	'<script>'.
		'var BLK='._block('page', $page_id, 'block_js').','.
			'ELM='._block('page', $page_id, 'elem_js').','.
			'PAGE_LIST='._page('for_select', 'js').','.
			'ELEM_COLOR={'._colorJS().'};'.
	'</script>'.
	'<script>_pageAct('.PAS.')</script>';
}
function _pageSpisokUnit($page_id, $obj_name='page') {//данные единицы списка, которая размещается на странице. Получение по $_GET['id']
	if($obj_name != 'page')
		return array();

	$page = _page($page_id);
	if(!$dialog_id = $page['spisok_id'])
		return array();

	$pageDef = '<br><br><a href="'.URL.'&p='._page('def').'">Перейти на страницу по умолчанию</a>';
	if(!$id = _num(@$_GET['id']))
		return _contentMsg('Некорректный идентификатор единицы списка.'.$pageDef);

	if(!$dialog = _dialogQuery($dialog_id))
		return _contentMsg('Отсутствует диалог, который вносит данные.'.$pageDef);

	$sql = "SELECT *
			FROM `".$dialog['base_table']."`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if(!$unit = query_assoc($sql))
		return _contentMsg('Единицы списка id'.$id.' не существует.'.$pageDef);

	if(isset($dialog['field']['deleted']) && $unit['deleted'])
		return _contentMsg('Единица списка id'.$id.' была удалена.'.$pageDef);

	return $unit;
}
function _elemDiv($el, $unit=array()) {//формирование div элемента
	if(!$el)
		return '';

	//если элемент списка шаблона, attr_id не ставится
	$attr_id = empty($el['tmp']) ? ' id="el_'.$el['id'].'"' : '';

	$cls = array();
//	$cls[] = 'dib';
	$cls[] = $el['color'];
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

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
function _elemUnit($el, $unit=array()) {//формирование элемента страницы
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//значение из списка
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']]: '';
	$is_edit = @BLOCK_EDIT || ELEM_WIDTH_CHANGE || !empty($unit['choose']);
	$attr_id = 'cmp_'.$el['id'].($is_edit ? '_edit' : '');
	$disabled = $is_edit ? ' disabled' : '';

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
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

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
					WHERE `block_id`=-".$el['id']."
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

		//Select - выбор списка приложения
		case 24:
			/*
                txt_1 - текст, когда список не выбран
				num_1 - содержание селекта:
						0   - все списки приложения. Функция _dialogSpisokOn()
						960 - размещённые на текущем объекте
							  Списки размещаются диалогами 14(шаблон) и 23(таблица)
							  Идентификаторами результата являются id элементов (а не диалогов)
							  Функция _dialogSpisokOnPage()
						961 - привязанные к данному диалогу
							  Идентификаторами результата являются id элементов (а не диалогов)
							  Функция _dialogSpisokOnConnect()
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
                txt_2 - два id элемента, составляющие содержание Select
				num_2 - возможность добавления новых значений
				num_3 - поиск значений вручную
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Выбор значений для содержания Select
		case 31:
			/*
				num_1 - id элемента, размещающее Select, для которого выбираются значения
				txt_1 - имя первого значения
				num_2 - использовать ли второе значение
				txt_2 - имя второго значения
			*/
			$ex = explode(',', $v);
			$v0 = _num(@$ex[0]) ? 'выбрано' : '';
			$v1 = _num(@$ex[1]) ? 'выбрано' : '';
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<input type="text" id="'.$attr_id.'_sv" class="sv w125 curP over1 color-pay" placeholder="'.$el['txt_1'].'" val="0" readonly'.$disabled.' value="'.$v0.'" />'.
			($el['num_2'] ?
				'<input type="text" class="sv w150 curP over1 color-pay ml5" placeholder="'.$el['txt_2'].'" val="1" readonly'.$disabled.' value="'.$v1.'" />'
			: '');

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

		//Календарь
		case 51:
			/*
				num_1 - разрешать выбор прошедших дней
				num_2 - показывать время
			*/
			return _calendar(array(
				'attr_id' => $attr_id,
				'value' => $v
			));

		//Связка списка при помощи кнопки
		case 59:
			/*
				txt_1 - текст кнопки
				num_4 - id диалогового окна
				num_1 - id списка
			*/

			$v = _num($v);
			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_button(array(
				'attr_id' => $attr_id.$el['afics'],
				'name' => $el['txt_1'],
				'color' => 'grey',
				'width' => $el['width'],
				'small' => 1,
				'class' => _dn(!$v)
			)).
			'<div class="'._dn($v).'">'.
				'<div class="icon icon-del-red pl fr'._tooltip('Отменить выбор', -53).'</div>'.
				'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
			'</div>';


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
			$dialog_source = !empty($el['block']) && $el['block']['obj_name'] == 'dialog' ? ',dialog_source:'.$el['block']['obj_id'] : '';

			//если новая кнопка, будет создаваться новый диалог для неё
			if(!$el['num_4'])
				$block = ',block_id:'.$el['block_id'];

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

			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$el['num_1'];
			if(!$elem = query_assoc($sql))
				return 'элемент отсутствует';

			switch($elem['dialog_id']) {
				//однострочное поле
				case 8:
					if(!$UNIT_ISSET)
						return 'text';
					$txt = $unit[$elem['col']];
//					$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
					$txt = _spisokUnitUrl($txt, $unit, $el['url']);
					$txt = _spisokUnitFormat($txt, $el);
					return $txt;
				//произвольный текст
				case 10: return $elem['txt_1'];
				case 29:
					if(!$UNIT_ISSET)
						return 'связка';
					if(!$connect_id = $unit[$elem['col']])
						return '';
					$dialog = _dialogQuery($unit['dialog_id']);
					$sql = "SELECT *
							FROM `".$dialog['base_table']."`
							WHERE `id`=".$connect_id;
					$sp = query_assoc($sql);
					$txt = $sp['txt_1'];
					$txt = _spisokUnitUrl($txt, $sp, $el['url']);
					return $txt;
				//сумма значений единицы списка
				case 27:
					if(!$UNIT_ISSET)
						return $elem['txt_1'];
					return $unit[$elem['col']];
				//количество связанного списка
				case 54:
					if(!$UNIT_ISSET)
						return $elem['txt_1'];
					return _num($unit[$elem['col']]);
				//сумма связанного списка
				case 55:
					if(!$UNIT_ISSET)
						return $elem['txt_1'];
					return $unit[$elem['col']];
				//Изображение
				case 60:
					if(!$UNIT_ISSET)
						return 'img';
					if(!$col = $elem['col'])
						return '';
//					if(empty($unit[$elem['col']]))//id картинки хранится в колонке
//						return '';
//					if(!$img_id = _num($unit[$elem['col']]))//получение id картинки, либо вывод её, если уже сформирована
//						return $unit[$elem['col']];

					$sql = "SELECT *
							FROM `_image`
							WHERE `obj_name`='elem_".$elem['id']."'
							  AND `obj_id`=".$unit['id']."
							  AND !`deleted`
							  AND !`sort`
							LIMIT 1";
					if(!$r = query_assoc($sql))
						return _imageNo();
					return _imageHtml($r);
			}
			return 'значение '.$elem['dialog_id'].' ещё не сделано';

		//SA: Функция PHP
		case 12:
			/*
				После размещения данных PHP-функции будет выполняться JS-функция с таким же именем, если существует.

                txt_1 - имя функции
			*/

			if(!$el['txt_1'])
				return '<div class="_empty min">Отсутствует имя функции.</div>';
			if(!function_exists($el['txt_1']))
				return '<div class="_empty min">Фукнции <u>'.$el['txt_1'].'</u> не существует.</div>';
			if($is_edit)
				return '<div class="_empty min">Функция '.$el['txt_1'].'</div>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				$el['txt_1']($el, $unit);

		//Содержание единицы списка - шаблон
		case 14:
			/*
                num_1 - id диалога, который вносит данные списка (шаблон которого будет настраиваться)
				num_2 - длина (количество строк, выводимых за один раз)
				txt_1 - сообщение пустого запроса

				настройка шаблона через вспомогательный элемент: dialig_id=25
			*/
			if($is_edit) {
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

			if(!$BL = _blockQuery($bs_id))
				return _emptyMin('Исходного блока id'.$bs_id.' не существует.');

			if($BL['obj_name'] != 'dialog')
				return _emptyMin('Действия можно назначать<br>только компонентам в диалоговых окнах.');

			$sql = "SELECT *
					FROM `_element_func`
					WHERE `block_id`=".$bs_id."
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				return _emptyMin('Действий не назначено.');

			//Названия действий
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'action_id').")";
			$act = query_ass($sql);

			//Названия условий
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'cond_id').")";
			$cond = query_ass($sql);

			//Конкретные значения
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'value_specific').")";
			$vs = query_ass($sql);

			//Названия эффектов
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'effect_id').")";
			$effect = query_ass($sql);
			$effect[0] = 'нет';

			$spisok = '';
			foreach($arr as $r) {
				$c = count(_ids($r['target'], 1));
				$spisok .=
					'<dd val="'.$r['id'].'">'.
					'<table class="bs5 bor1 bg-gr2 over2 mb5 curD">'.
						'<tr>'.
							'<td class="w25 top">'.
								'<div class="icon icon-move-y pl"></div>'.
							'<td class="w300">'.
								'<div class="fs15">'._dialogParam($r['dialog_id'], 'spisok_name').'</div>'.
								'<table class="bs3">'.
									'<tr><td class="fs12 grey top">Действие:'.
										'<td class="fs12">'.
											'<b class="fs12">'.$act[$r['action_id']].'</b>, если '.
				   (!$r['value_specific'] ? '<b class="fs12">'.$cond[$r['cond_id']].'</b>' : '').
					($r['value_specific'] ? 'выбрано: <b>'.$vs[$r['value_specific']].'</b>' : '').
					($r['action_reverse'] ? '<div class="fs11 color-555">(применяется обратное действие)</div>' : '').
									'<tr><td class="fs12 grey r">Эффект:'.
										'<td class="fs12 '.($r['effect_id'] ? 'color-pay' : 'pale').'">'.$effect[$r['effect_id']].
								'</table>'.
							'<td class="w70 b color-ref top center pt3">'.
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
				Используется в диалогах: 7,11,36,40

				num_2 - Что выбирать:
							40: любые элементы
							41: элементы, которые вносят данные
							42: элементы, по которым можно производить поиск
							43: блоки
				num_3 - выбор нескольких значений
			*/
			if($el['block']['obj_name'] != 'dialog')
				return _emptyMin('Элемент может располагаться только в блоке Диалога');

			if(!$bls_id = _num(@$US['block_id'], 1))
				return _emptyMin('Отсутствует ID исходного блока.');

			//блок является элементом
			if($bls_id < 0) {
				if(!$EL = _elemQuery(abs($bls_id)))
					return _emptyMin('Исходного элемента id'.$bls_id.' не существует.');
				$bls_id = $EL['block_id'];//обновление исходного блока
			}

			if(!$BLS = _blockQuery($bls_id))
				return _emptyMin('Исходный блок id'.$bls_id.' отсутствует.');

			if($el['num_2'] == 43 && $BLS['obj_name'] != 'dialog')
				return _emptyMin('Выбор блоков доступен только для диалогов.');

			$dialog_id = 0;
			//id диалога, в котором располагается выбор
			switch($el['block']['obj_id']) {
				case 7://поиск
					if(!$EL = $BLS['elem'])
						return _emptyMin('Содержание диалога будет доступно<br>после вставки элемента поиска в блок.');
					if(!$EL['num_1'])
						return _emptyMin('Содержание диалога будет доступно после выбора списка,<br>по которому будет производиться поиск.');
					if(!$sp = _elemQuery($EL['num_1']))
						return _emptyMin('Отсутствует элемент, размещающий список.');
					$dialog_id = $sp['num_1'];
					break;
				case 11://вставка значения...
					if($BLS['obj_name'] == 'spisok') {//...в блок шаблона [14]
						$bl = _blockQuery($BLS['obj_id']);
						if(!$bl['elem'])
							return _emptyMin('Содержание диалога будет доступно<br>после вставки элемента в блок.');
						if(!$dialog_id = $bl['elem']['num_1'])
							return _emptyMin('Содержание диалога будет доступно после выбора списка.');
						break;
					}
					if($BLS['obj_name'] == 'page') {
						if($BLS['elem'] && ($BLS['elem']['dialog_id'] == 14 || $BLS['elem']['dialog_id'] == 23)) {//списки [14,23]
							$dialog_id = $BLS['elem']['num_1'];
							break;
						}
						if(!$page = _page($BLS['obj_id']))
							return _emptyMin('Данные страницы '.$BLS['obj_id'].' не получены.');
						if(!$dialog_id = $page['spisok_id'])
							return _emptyMin('Страница не принимает значения единицы списка');
					}
					if($BLS['obj_name'] == 'dialog') {
						if($US['dialog_source']) {
							if(!$dialog_id = $US['dialog_source'])
								break;
							//отображение диалога происходит для элемента, который выбирает значения для списка
							//требуется уточнение, где искать id диалога
							if($BLS['elem']['dialog_id'] == 31) {
								if(!$el31_id = _num($BLS['elem']['num_1']))
									return _emptyMin('Отсутствует id элемента, размещающего select');
								if(!$el31 = _elemQuery($el31_id))
									return _emptyMin('Отсутствует элемент, размещающий select');
								if($el31['num_1']) {//$dialog_id - является элементом, размещающий выпадающий список-связку [29]
									if(!$ell = _elemQuery($dialog_id))
										return _emptyMin('...');
									$dialog_id = _num($ell['block']['obj_id']);
								}
							}
							break;
						}

						$dialog_id = $BLS['obj_id'];
						break;
					}
					break;
				case 31://выбор значения для Выпадающего поля
					if($BLS['obj_name'] != 'dialog')
						return _emptyMin('Выбор значения только для диалогов');
					$dialog_id = $BLS['obj_id'];
					break;
				case 36://показ-скрытие блоков для галочки
				case 40://показ-скрытие блоков для выпадающего поля
				default:
					if($el['num_2'] == 43) {
						$dialog_id = $BLS['obj_id'];
						break;
					}
					return _emptyMin('Ненастроенный диалог '.$el['block']['obj_id']);
			}

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
/*
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
*/

			$send = array(
				'choose' => 1,
				'choose_access' => $choose_access,
				'choose_sel' => _idsAss($v),       //ids ранее выбранных элементов или блоков
				'choose_deny' => $choose_deny      //ids элементов или блоков, которые выбирать нельзя (если они были выбраны другой фукцией того же элемента)
			);

			//выбирается только одно значение
			if(!$el['num_3'])
				$v = _num($v);

			return
			'<div class="fs14 pad10 pl15 bg-gr2 line-b">Диалоговое окно <b class="fs14">'.$dialog['spisok_name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send);

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

		//Значение списка: порядковый номер
		case 32: return _spisokUnitNum($unit);

		//Значение списка: дата
		case 33:
			/*
				num_1 - формат:
					29: 5 августа 2017
					30: 5 авг 2017
					31: 05/08/2017
				num_2 - не показывать текущий год
				num_3 - имена у ближайших дней:
					вчера
					сегодня
					завтра
				num_4 - показывать время в формате 12:45
			*/

			return _spisokUnitData($unit, $el);

		//Значение списка: иконки управления
		case 34:
			if(!$UNIT_ISSET)
				return 'edit';

			return _spisokUnitIconEdit($unit['dialog_id'], $unit['id']);

		//Иконка вопрос: Выплывающая подсказка
		case 42:
			/*
				txt_1 - текст подсказки
				num_1 - сторона всплытия
					741 - сверху
					742 - снизу
					743 - слева
					744 - справа
			*/
			return '<div class="icon icon-hint pl" id="'.$attr_id.'"></div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка суммы значений единицы списка (для [27])
		case 56:
			/*
				Все действия через JS.
				cmp_id получает ids используемых элементов в определённом порядке
			*/
			if($is_edit)
				return '<div class="_empty min">Настройка суммы значений единицы списка</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';

		//Меню переключения блоков
		case 57:
			/*
				num_1 - внешний вид меню:
						1158 - Маленькие синие кнопки
						1159 - С нижним подчёркиванием
			*/

			if(empty($el['vvv']))
				return '';

			$type = array(
				1158 => 2,
				1159 => 1
			);

			$razdel = '';
			foreach($el['vvv'] as $r)
				$razdel .= '<a class="link'._dn($el['def'] != $r['id'], 'sel').'">'.$r['title'].'</a>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$el['def'].'" />'.
				'<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка пунктов меню переключения блоков (для [57])
		case 58:
			/*
				Все действия через JS.
			*/
			if($is_edit)
				return '<div class="_empty min">Настройка пунктов меню переключения блоков</div>';

			return '';

		//Загрузка изображений
		case 60:
			/*
				num_1 - максимальное количество изображений, которое разрешено загрузить
			*/
			if($is_edit)
				return '<div class="_empty min">Изображения</div>';

			$v = _num($v);

			//отметка загруженных изображений как неиспользуемые, которые были не сохранены в предыдущий раз
			$sql = "UPDATE `_image`
					SET `obj_name`='elem_".$el['id']."',
						`deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `obj_name`='elem_".$el['id']."_".USER_ID."'";
			query($sql);

			$html = '';
			$del_count = 0;
			if($unit_id = _num(@$unit['id'])) {
				$sql = "SELECT *
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND !`deleted`
						ORDER BY `sort`";
				if($spisok = query_arr($sql))
					foreach($spisok as $r)
						$html .= _imageDD($r);

				$sql = "SELECT COUNT(*)
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND `deleted`";
				$del_count = query_value($sql);
			}
			return
			'<div class="_image">'.
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<dl>'.
					$html.
					'<dd class="dib">'.
						'<table class="_image-load">'.
							'<tr><td>'.
									'<div class="_image-add icon-image"></div>'.
									'<div class="icon-image spin"></div>'.
									'<div class="_image-prc"></div>'.
									'<div class="_image-dis"></div>'.
									'<table class="tab-load">'.
										'<tr><td class="icon-image ii1">'.//Выбрать из файлов
												'<form>'.
													'<input type="file" accept="image/jpeg,image/png,image/gif,image/tiff" />'.
												'</form>'.
											'<td class="icon-image ii2">'.      //Указать ссылку на изображение
										'<tr><td class="icon-image ii3">'.      //Фото с вебкамеры
											'<td class="icon-image ii4'._dn($del_count, 'empty').'" val="'.$del_count.'">'.//Достать из корзины
									'</table>'.

						'</table>'.
					'</dd>'.
				'</dl>'.
				'<div class="_image-link dn mt5">'.
					'<table class="w100p">'.
						'<tr><td>'.
								'<input type="text" class="w100p" placeholder="вставьте ссылку или скриншот и нажмите Enter" />'.
							'<td class="w50 center">'.
								'<div class="icon icon-ok"></div>'.
								'<div class="icon icon-del pl ml5"></div>'.
					'</table>'.
				'</div>'.
			'</div>';

		//Выбор цвета
		case 66:
			/*
			*/
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color" style="background-color:#000"></div>';





		//---=== ДЕЙСТВИЯ К ЭЛЕМЕНТАМ (ФУНКЦИИ) ===---
		//Список действий для Галочки [1]
		case 28: return 28;

		//Назначение действия для Галочки [1]: скрытие/показ блоков
		case 36:
			/*
				таблица _element_func
					action_id - действие для блоков
						726 - скрыть
						727 - показать
					cond_id - условие действия
						730 - галочка снята
						731 - галочка установлена
					action_reverse - применять обратное действие
					effect_id - эффекты
						44 - изчезновение/появление
						45 - сворачивание/разворачивание
					target - id блоков, на которые воздействует галочка
			*/
			return 36;

		//Список действий для Выпадающего поля [17]
		case 39: return 39;

		//Назначение действия для Выпадающего поля [17]: скрытие/показ блоков
		case 40:
			/*
				таблица _element_func
					action_id - действие для блоков
						709 - скрыть
						710 - показать
					cond_id - условие действия
						703 - значение не выбрано
						704 - значение выбрано
						705 - конкретное значение
					action_reverse - применять обратное действие (для выбрано/не выбрано)
					value_specific - конкртетное значение (при условии 705)
					effect_id - эффекты
						715 - изчезновение/появление
						716 - сворачивание/разворачивание
					target - id блоков, на которые воздействует галочка
			*/
			return 40;

		//Сборный текст
		case 44:
			/*
				txt_1 - ids элементов, наполняющих содержание
			*/

			if(!$el['txt_1'])
				return '';

			$txt = '';
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$el['txt_1'].")
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$txt .= _elemUnit($r, $unit);
				$txt .= $r['num_8'] ? ' ' : ''; //добавление пробела справа, если нужно
			}

			return $txt;

		//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка содержания сборного текста
		case 49:
			/*
				Все действия через JS.
				cmp_id получает ids используемых элементов в определённом порядке
			*/
			if($is_edit)
				return '<div class="_empty min">Содержание сборного текста</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';




		//---=== ФИЛЬТРЫ ===---
		//Быстрый поиск - фильтр
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
						'v' => _spisokFilter('v', $el['id']),
						'disabled' => $disabled
					));

		//отображение данных при условии связанного списка
		case 52:
			/*
				Значение берётся со страницы, которая принимает единицу списка

				num_1 - id элемента, который размещает список
			*/
			if($is_edit)
				return 'Фильтр-связка';

			return '';

		//порядок - не доделано
		case 53:
			/*
			*/
			return 'порядок';

		//Галочка - фильтр
		case 62:
			/*
				txt_1 - текст для галочки
				num_1 - условие применяется:
						1439 - галочка установлена
						1440 - галочка НЕ установлена
				num_2 - id элемента, размещающего список
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
				'value' => _num(_spisokFilter('v', $el['id']))
			));




		//---=== СВЯЗКИ ===---

		//Настройка суммы значений единицы списка
		case 27:
			/*
				txt_1 - имя суммы
				txt_2 - ids значений для подсчёта
			*/
			return $el['txt_1'];

		//количество значений привязанного списка
		case 54:
			/*
				txt_1 - имя значения (для отображения в блоке при выборе как значение)
				num_1 - привязанный список
			*/
			return $el['txt_1'];

		//сумма значений привязанного списка
		case 55:
			/*
				для хранения сумм используется колонка sum_1, sum_2, ...

				txt_1 - имя значения (для отображения в блоке при выборе как значение)
				num_1 - привязанный список
				num_2 - id элемента значения (колонки) привязанного списка
			*/
			return $el['txt_1'];


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
			'<a class="link'.$sel.'" href="'.URL.'&p='.($r['common_id'] ? $r['common_id'] : $r['id']).'">'.
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
		'<div class="icon icon-hint"></div>'.
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







