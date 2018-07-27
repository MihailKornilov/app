<?php
function _pageCache() {//получение массива страниц из кеша
	$key = 'page';
	if($arr = _cache_get($key))
		return $arr;

	$sql = "SELECT
				*,
				0 `block_count`,
				0 `elem_count`,
				1 `del_access`,
				0 `access`
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
		//страница доступна создателю приложения, а также всем, если не SA и для всех приложений
		$page[$id]['access'] = USER_ID && USER_CREATOR || !$r['sa'] && !$r['app_id'];
	}

	$sql = "SELECT *
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".USER_ID;
	foreach(query_arr($sql) as $r)
		if(!empty($page[$r['page_id']]))
			$page[$r['page_id']]['access'] = 1;

	return _cache_set($key, $page);
}
function _page($i='all', $i1=0) {//получение данных страницы
	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//страницы приложения
	if($i == 'app') {
		$send = array();
		foreach($page as $id => $r) {
			if(!$r['app_id'])
				continue;
			if($r['sa'])
				continue;
			$send[$id] = $r;
		}
		return $send;
	}

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
			if(!$p['sa'] && $p['def'] && $p['access'])
				return $p['id'];

		//затем первую доступную страницу
		foreach($page as $p)
			if(!$p['sa'] && $p['access'])
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
				'title' => 'Страницы SA',
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

	//список дочерних страниц относительно родительской
	if($i == 'child') {
		if(!$parent_id = _num($i1))
			return array();
		$send = array();
		foreach($page as $id => $r) {
			if($r['parent_id'] == $parent_id)
				$send[$id] = $r;
		}
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
			'title' => addslashes(htmlspecialchars_decode(trim($r['name']))),
			'content' => '<div class="fs'.(14-$level).' '.($level ? 'ml'.($level*20) : 'b').'">'.addslashes(htmlspecialchars_decode(trim($r['name']))).'</div>'
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
			'title' => addslashes(htmlspecialchars_decode(trim($r['name']))),
			'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.addslashes(htmlspecialchars_decode(trim($r['name']))).'</div>'
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
//	define('PAS', 1);//для настройки страниц, которые доступны всем приложениям
}
/*
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
//		'ol{max-width:700px;padding-left:40px}'.
	'</style>'.
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>';
}
function _pageSetupAppPageSpisok($arr, $sort) {//список страниц приложения
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1'.(!$r['parent_id'] ? ' pb10' : '').'" id="item_'.$r['id'].'">'.
			'<div>'.
				'<table class="_stab w100p bg-fff">'.
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
*/
function _pasMenu() {//строка меню управления страницей
	if(IFRAME_AUTH_ERROR)
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

function _pageUserAccess($el, $unit) {//настройка доступа к страницам для пользователя (подключение через [12])
	if(empty($unit['id']))
		return _emptyMin('Отсутствует id пользователя.');

	//доступные страницы
	$sql = "SELECT `page_id`
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$unit['id'];
	$ids = _idsAss(query_ids($sql));

	$arr = _page('app');
	$sort = array();
	foreach($arr as $id => $r) {
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$r['access'] = _num(@$ids[$id]);
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		} else
			$arr[$id]['access'] = _num(@$ids[$id]);
	}


	return
	'<input type="hidden" id="access-user-id" value="'.$unit['id'].'" />'.
	'<dl>'._pageUserAccessSpisok($arr, $sort).'</dl>';
}
function _pageUserAccessSpisok($arr, $sort) {//список страниц для настройки доступа
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<dd class="'._dn($r['parent_id'], ' pb10').'">'.
				'<table class="bs3">'.
					'<tr><td>'._check(array(
									'attr_id' => 'pageAccess_'.$r['id'],
									'title' => $r['name'],
									'class' => !$r['parent_id'] ? 'b fs14' : '',
									'value' => $r['access']
								)).
				'</table>';
		if(!empty($sort[$r['id']]))
			$send .= '<dl class="ml40'._dn($r['access']).'">'._pageUserAccessSpisok($sort[$r['id']], $sort).'</dl>';
	}

	return $send;
}
function _pageUserShow($el, $unit) {//отображение страниц, доступных пользователю (подключение через [12])
	if(empty($unit['id']))
		return _emptyMin('Отсутствует id пользователя.');

	//доступ в преложние
	$sql = "SELECT `num_1`
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011
			  AND `connect_1`=".$unit['id']."
			LIMIT 1";
	if(!query_value($sql))
		return '<div class="_empty min mar10 red">Вход в приложение запрещён.</div>';

	//доступные страницы
	$sql = "SELECT `page_id`
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$unit['id'];
	$ids = _idsAss(query_ids($sql));

	$page = _page('app');
	foreach($page as $id => $r)
		$page[$id]['access'] = _num(@$ids[$id]);

	$child = array();
	foreach($page as $id => $r) {
		if(empty($child[$r['parent_id']]))
			$child[$r['parent_id']] = array();
		$child[$r['parent_id']][] = $r;
	}

	if(!$send = _pageUserShowSpisok($child))
		return _emptyMin('Нет доступных страниц.');

	return $send;
}
function _pageUserShowSpisok($arr, $parent_id=0) {//список страниц для настройки доступа
	if(empty($arr[$parent_id]))
		return '';

	$send = '';
	foreach($arr[$parent_id] as $id => $r) {
		if(!$r['access'])
			continue;
		$send .= '<dd class="'._dn($r['parent_id'], ' pb10').'">'.
				'<table class="bs3">'.
					'<tr><td class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].
				'</table>';
		if(!empty($arr[$r['id']]))
			$send .= '<dl class="ml30">'._pageUserShowSpisok($arr, $r['id']).'</dl>';
	}

	return $send;
}
function _pageUserAccessAll() {//настройка входа в приложение всем пользователям (подключение через [12])
	$sql = "SELECT
				`u`.*,
				`ua`.`num_1`
			FROM
				`_user` `u`,
				`_spisok` `ua`
			WHERE `ua`.`app_id`=".APP_ID."
			  AND `u`.`id`=`ua`.`connect_1`
			  AND `ua`.`dialog_id`=1011
			ORDER BY `ua`.`dtime_add`";
	if(!$user = query_arr($sql))
		return _emptyMin('Сотрудников нет.');

	$send = '<table class="">';
	foreach($user as $r)
		$send .=
			'<tr class="over1">'.
				'<td class="w200 pad5 pl20 curD">'.
					$r['f'].' '.$r['i'].
				'<td class="w35">'.
					_check(array(
						'attr_id' => 'allAcc_'.$r['id'],
						'value' => $r['num_1']
					));

	$send .= '</table>';

	return $send;
}



function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	if(!SA && !$page['access'])
		return _contentMsg();

	if(APP_ID && !APP_ACCESS)
		$page_id = 105;

	if($page_id == 105 && APP_ID && APP_ACCESS)
		$page_id = _page('def');

	return
	_blockHtml('page', $page_id, 1000, 0, _pageSpisokUnit($page_id)).
	_page_div().
	'<script>'.
		'var BLK='._BE('block_js','page', $page_id).','.
			"\n\n".
//			'ELM='._BE('elem_js', 'page', $page_id).','.
//			"\n\n".
			'ELM='._BE('elem_ids_js', 'page', $page_id).','.
			"\n\n".
			'PAGE_LIST='._page('for_select', 'js').','.
			"\n\n".
			'ELEM_COLOR='._colorJS().','.
			"\n\n".
			'FILTER='._json(_spisokFilter('page_js')).';'.
			"\n\n".
		'_pageAct('.PAS.');'.
	'</script>';
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

	if(!$unit = _spisokUnitQuery($dialog, $id))
		return _contentMsg('Единицы списка id'.$id.' не существует.'.$pageDef);

	if(isset($dialog['field1']['deleted']) && $unit['deleted'])
		return _contentMsg('Единица списка id'.$id.' была удалена.'.$pageDef);

	foreach($dialog['cmp'] as $cmp_id => $cmp) {//поиск компонента диалога с вложенным списком
		//должен является вложенным списком
		if($cmp['dialog_id'] != 29 && $cmp['dialog_id'] != 59)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		//получение данных из вложенного списка
		$incDialog = _dialogQuery($cmp['num_1']);

		$cond = "`t1`.`id`=".$unit[$col];

		$sql = "SELECT `t1`.*"._spisokJoinField($incDialog)."
				FROM "._tableFrom($incDialog)."
				WHERE ".$cond;
		if(!$inc = query_assoc($sql))
			continue;

		//идентификаторы будут заменены на массив с данными единицы списка
		$unit[$col] = $inc;
	}

	return $unit;
}






function _page_div() {//todo тест
	return '';//count(_BE('block_all'));

	return
	'<div>'.
		'USER_ID='.USER_ID.
		'<br>'.
		'PAGE_ID='._page('cur').
		'<br>'.
		'APP_ID='.APP_ID.
		'<br>'.
		'APP_ACCESS='.APP_ACCESS.
	'</div>';



	$dlg = _dialogQuery(1003);

	return
	_pr($dlg).
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
	'<div class="w200 pad20">'.
		'<input type="hidden" id="aaa" value="2" />'.
	'</div>'.

	'<div class="w500 mt10">'.
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

	'<div class="mar20">'.
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





