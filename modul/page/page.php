<?php
function _pageCache() {//получение массива страниц из кеша
	$key = 'page';
	if($arr = _cache_get($key))
		return $arr;

	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id` IN (0,".APP_PARENT.")
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
		unset($page[$id]['about']);
		unset($page[$id]['user_id_add']);
		unset($page[$id]['dtime_add']);
		unset($page[$id]['acs']);
		$block_count = _num(@$block[$id]);
		$page[$id]['del_allow'] = $block_count || $r['common_id'] ? 0 : 1;
		$page[$id]['sa'] = $r['acs'] == 2 ? 1 : 0;
		$page[$id]['creator'] = $r['acs'] == 1 ? 1 : 0;
	}

	return _cache_set($key, $page);
}
function _pageAccess($page_id) {//доступ к странице для конкретного пользователя
	if(SA)
		return true;
	if(USER_ADMIN)
		return true;

	$key = 'UserPageAccess'.USER_ID;

	if(!$ass = _cache_get($key)) {
		$sql = "SELECT `page_id`,1
				FROM `_user_page_access`
				WHERE `app_id`=".APP_ID."
				  AND `user_id`=".USER_ID;
		$ass = query_ass($sql);

		//разрешение страниц, видимых всем пользователям
		foreach(_page() as $id => $p)
			if($p['dialog_id'] == 101)
				if(!$p['sa'] && !$p['creator'])
					$ass[$id] = 1;

		_cache_set($key, $ass);
	}

	return !empty($ass[$page_id]);
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

		//сначала поиск стартовой страницы приложения
		$pageLost = array();
		foreach($page as $id => $p) {
			if($p['sa'])
				continue;
			if($p['creator'])
				continue;
			if($id == 9)//печать шаблона
				continue;
			if($id == 98)//список приложений
				continue;
			if($id == 13)//ввод пин-кода
				continue;
			if($p['dialog_id'] != 20)
				continue;
			if(!_pageAccess($id))
				continue;
			if($p['def'])
				return $id;
			$pageLost[] = $p;
		}

		//затем доступные из оставшихся
		foreach($pageLost as $r) {
			if($r['common_id']) {
				foreach(_page('child', $r['id']) as $p) {
					if(_pageAccess($p['id']))
						return $p['id'];
				}
				continue;
			}
			return $r['id'];
		}

		//затем страницы SA
		if(SA)
			foreach($page as $p)
				if($p['sa'] && $p['def'])
					return $p['id'];

		//иначе Администрирование
		if(USER_ADMIN)
			return 7;

		return 105;
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

	//получение страницы, которая принимает значения списка
	//  $i1 - id диалога, который вносит данные этого списка
	if($i == 'dialog_id_unit_get') {
		if(!$dialog_id = _num($i1))
			return 0;
		foreach($page as $id => $r)
			if($r['dialog_id_unit_get'] == $dialog_id)
				return $id;
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

	define('PAS', APP_ID && $pas ? 1 : 0);
//	define('PAS', 1);//для настройки страниц, которые доступны всем приложениям
}
function _pasMenu() {//строка меню управления страницей
	if(IFRAME_AUTH_ERROR)
		return '';
	if(!PAS)
		return '';

	return
	'<div id="pas">'.
		'<div class="mara pt5">'.
			'<div class="dib fs16 b">'._page('name').	'</div>'.
		'</div>'.
		'<div class="mara pt5">'.
			_blockLevelChange('page', _page('cur')).
		'</div>'.
	'</div>';
}
function _pageInfo() {//информация о странице
	if(!SA)
		return '';
	if(IFRAME_AUTH_ERROR)
		return '';
	if(!PAS)
		return '';

	$page_id = _page('cur');
	$page = _page($page_id);

	$blk = _BE('block_arr', 'page', $page_id);
	$elm = _BE('elem_arr', 'page', $page_id);

	return
	'<div class="bg-fee line-b">'.
		'<div class="w1000 mara pad5">'.

			'<table class="w300">'.
				'<tr class="center">'.
					'<td>APP_ID: '.$page['app_id'].
					'<td class="'.($page['sa'] ? 'fs15 b color-ref' : 'pale').'">SA'.
					'<td>BLK: <b>'.count($blk).'</b>'.
					'<td>ELM: <b>'.count($elm).'</b>'.
			'</table>'.

		'</div>'.
	'</div>';
}

function PHP12_page_access_for_user_view($prm) {//отображение страниц, доступных пользователю
	if(!$u = $prm['unit_get'])
		return _emptyMin10('Данные пользователя не получены.');

	if(_sa($u['id']))
		return _emptyMin('SA: Доступны все страницы.');
	if($u['id'] == _app(APP_ID, 'user_id_add'))
		return _emptyMin('Создатель приложения: доступны все страницы.');

	//доступ в приложение
	if(!$u['num_1'])
		return _emptyRed10('Вход в приложение запрещён.');

	//доступные страницы
	$sql = "SELECT `page_id`
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$u['id'];
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

	if(!$send = PHP12_page_access_for_user_view_spisok($child))
		return _emptyMin10('Нет доступных страниц.');

	return $send;
}
function PHP12_page_access_for_user_view_spisok($arr, $parent_id=0) {//список страниц для настройки доступа
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
			$send .= '<dl class="ml30">'.PHP12_page_access_for_user_view_spisok($arr, $r['id']).'</dl>';
	}

	return $send;
}

function PHP12_app_enter_for_all_user() {//настройка входа в приложение всем пользователям
	$sql = "SELECT
				`u`.*,
				`sp`.`num_1`
			FROM
				`_user` `u`,
				`_spisok` `sp`
			WHERE `sp`.`app_id`=".APP_ID."
			  AND `u`.`id`=`sp`.`cnn_id`
			  AND `sp`.`dialog_id`=111
			ORDER BY `sp`.`dtime_add`";
	if(!$user = query_arr($sql))
		return _emptyMin10('Сотрудников нет.');

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
function PHP12_app_enter_for_all_user_save($cmp, $val, $unit) {//сохранение доступа в приложение для всех пользователей
	$sql = "UPDATE `_spisok`
			SET `num_1`=0
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=111
			  AND `cnn_id`";
	query($sql);

	if($ids = _ids($val)) {
		$sql = "UPDATE `_spisok`
				SET `num_1`=1
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=111
				  AND `cnn_id` IN (".$ids.")";
		query($sql);
	}

	$sql = "UPDATE `_user_auth`
			SET `app_id`=0
			WHERE `app_id`=".APP_ID."
			  AND `user_id` NOT IN (".$ids.")";
	query($sql);

	_cache_clear('AUTH _'.CODE, 1);
	_cache_clear('page');

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=111
			  AND `cnn_id`";
	foreach(query_arr($sql) as $r)
		_cache_clear('user'.$r['cnn_id']);
}



function _pageShow($page_id) {
	define('PAGE_MSG_ERR', '<br><br><a href="'.URL.'&p='._page('def').'">Перейти на <b>стартовую страницу</b></a>');

	//нет доступа в приложение
	if(!SA && APP_ID && !APP_ACCESS)
		$page_id = 105;

	//требуется ввод пин-кода
	if(PIN_ENTER && $page_id != 98)
		$page_id = 13;
	else
		//если не требуется ввода пин-кода, обновление времени действия
		$_SESSION[PIN_KEY] = time() + PIN_DURATION;

	//если попали на страницу ввода пина, но вводить не нужно, переход на стартовую страницу
	if($page_id == 13 && !PIN_ENTER)
		$page_id = _page('def');

	//вывод документа на печать
	if($page_id == 9)
		return _document();

	//если доступ в приложение есть, но попали на страницу о недоступности, то переход на стартовую страницу
	if($page_id == 105 && APP_ID && APP_ACCESS)
		$page_id = _page('def');

	if(!$page = _page($page_id))
		return _empty20('Несуществующая страница.'.PAGE_MSG_ERR);
	if($page['sa'] && !SA)
		return _empty20('Нет доступа.'.PAGE_MSG_ERR);
	if(!SA && $page['creator'] && !USER_ADMIN)
		return _empty20('Страница недоступна.'.PAGE_MSG_ERR);
	if(!_pageAccess($page_id))
		return _empty20('Страница недоступна или не существует.'.PAGE_MSG_ERR);

	$prm = array();

	//страница принимает данные записи
	if($dialog_id = $page['dialog_id_unit_get']) {
		if(!$id = $page['unit_id'])
			if(!$id = _num(@$_GET['id']))
				return _empty20('Некорректный идентификатор записи.'.PAGE_MSG_ERR);
		if(!$dialog = _dialogQuery($dialog_id))
			return _empty20('Отсутствует диалог, который вносит данные записи.'.PAGE_MSG_ERR);
		if(!$prm['unit_get'] = _spisokUnitQuery($dialog, $id))
			return _empty20('Записи '.$id.' не существует.'.PAGE_MSG_ERR);
	}


	return
	_blockHtml('page', $page_id, $prm).
	_page_div().
	_pageShowScript($page_id, $prm);
}
function _pageShowScript($page_id, $prm) {
	if(PAS)
		return '';

	$prm = _blockParam($prm, 'page');

	//значения элементов страницы
	$vvv = array();
	foreach(_BE('elem_ids_arr', 'page', $page_id) as $elem_id)
		$vvv[$elem_id] = _element('vvv', $elem_id, $prm);

	return
	'<script>'.
	(APP_ID && USER_ID ?
		'var FILTER='._json(_spisokFilter('page_js'), 1).';'.
		_pageDlgOpenAuto()
	: '').
		'_ELM_ACT({vvv:'._json($vvv).',unit:[]});'.
	'</script>';
}
function _pageUnitGet($obj_name, $obj_id) {//получение данных записи, которые принимает страница (для отображения в настройке страницы)
	if($obj_name != 'page')
		return array();
	if(!$get_id = _num(@$_GET['id']))
		return array();
	if(!$page = _page($obj_id))
		return array();
	if(!$dialog_id = $page['dialog_id_unit_get'])
		return array();
	if(!$dialog = _dialogQuery($dialog_id))
		return array();

	return _spisokUnitQuery($dialog, $get_id);
}
function _pageDlgOpenAuto() {
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `open_auto`
			LIMIT 1";
	if(!$dlg = query_assoc($sql))
		return '';

	return '_dialogLoad({dialog_id:'.$dlg['id'].'});';
}


function _document() {//формирование документа для вывода на печать
	if(!APP_ID)
		return _empty20('Не выполнен вход в приложение'.PAGE_MSG_ERR);
	if(!$doc_id = _num(@$_GET['doc_id']))
		return _empty20('Некорректный id шаблона документа'.PAGE_MSG_ERR);

	//получение данных шаблона
	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$doc_id;
	if(!$doc = query_assoc($sql))
		return _empty20('Шаблона документа '.$doc_id.' не существует'.PAGE_MSG_ERR);

	//получение данных файла-шаблона
	if(!$attach_id = $doc['attach_id'])
		return _empty20('Не настроен файл-шаблон');

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$attach_id;
	if(!$att = query_assoc($sql))
		return _empty20('Файла-шаблона '.$attach_id.' не существует'.PAGE_MSG_ERR);

	if(!file_exists($att['path'].$att['fname']))
		return _empty20('Файл-шаблон отсутствует на сервере'.PAGE_MSG_ERR);

	//проверка корректности расширения файла-шаблона
	$ex = explode('.', $att['fname']);
	$c = count($ex) - 1;
	if($ex[$c] != 'docx')
		return _empty20('Некорректный файл-шаблон'.PAGE_MSG_ERR);

	//получение данных записи
	if(!$dlg_id = $doc['spisok_id'])
		return _empty20('Не указан список, из которого берутся данные'.PAGE_MSG_ERR);
	if(!$DLG = _dialogQuery($dlg_id))
		return _empty20('Диалога '.$dlg_id.' не существует'.PAGE_MSG_ERR);
	if(!$unit_id = _num(@$_GET['id']))
		return _empty20('Отсутствует id записи'.PAGE_MSG_ERR);
	if(!$unit = _spisokUnitQuery($DLG, $unit_id))
		return _empty20('Записи '.$unit_id.' не существует'.PAGE_MSG_ERR);

	require_once GLOBAL_DIR.'/inc/PhpWord/vendor/autoload.php';
	$document = new \PhpOffice\PhpWord\TemplateProcessor($att['path'].$att['fname']);

	//подстановка данных
	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN ("._ids($doc['param_ids']).")";
	foreach(query_arr($sql) as $el) {
		$v = _element('template_docx', $el, $unit);
		if(strpos($el['txt_10'], '_PROPIS}'))
			if($sum = round($v))
				$v = _numToWord($sum);
		$document->setValue($el['txt_10'], $v);
	}

	//формирование имени файла-шаблона для загрузки
	$fname = $att['fname'];
	if($doc['fname']) {
		$fname = $doc['fname'];
		$ex = explode('.', $fname);
		$c = count($ex) - 1;
		if($ex[$c] != 'docx')
			$fname .='.docx';
	}

	header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header('Content-Disposition: attachment; filename="'.$fname.'"');
	$document->saveAs('php://output');

	exit;
}

/* ----==== СПИСОК СТРАНИЦ (page12) ====---- */
function PHP12_page_list() {
	$send = '';
	foreach(_page('app') as $id => $r) {
		if($r['parent_id'])
			continue;

		$send .= PHP12_page_list_li($r, 0).
				 PHP12_page_list_child($id);
	}

	if(!$send)
		return
		'<div class="_empty">'.
			'Ещё не создано ни одной страницы.'.
			'<br>'.
			'<br>'.
			'<button class="vk green dialog-open" val="dialog_id:20">Создать страницу</button>'.
		'</div>';

	return '<ol class="page-sort">'.$send.'</ol>';
}
function PHP12_page_list_child($parent_id, $level=1) {//дочерний уровень страниц
	if(!$arr = _page('child', $parent_id))
		return '';

	$send = '';
	foreach($arr as $id => $r)
		$send .= PHP12_page_list_li($r, $level).
				 PHP12_page_list_child($id, $level+1);

	return '<ol>'.$send.'</ol>';
}
function PHP12_page_list_li($r, $level=0) {//данные одной страницы
	return
		'<li id="pg_'.$r['id'].'" class="'.(!$level ? 'mb30' : 'mb1').'">'.
			'<table class="_stab small w100p">'.
				'<tr>'.
					'<td class="w25"><div class="icon icon-move pl"></div>'.
					'<td>'.
						'<a href="'.URL.'&p='.$r['id'].'" class="pg-name'._dn($r['parent_id'], 'b fs14').'">'.$r['name'].'</a>'.
		   ($r['def'] ? '<div class="icon icon-ok curD ml10'._tooltip('Стартовая страница', -61).'</div>' : '').
					'<td class="w50">'.
						'<div val="dialog_id:20,edit_id:'.$r['id'].'" class="icon icon-edit pl dialog-open'._tooltip('Настроить страницу', -60).'</div>'.
	($r['del_allow'] ? '<div val="dialog_id:20,del_id:'.$r['id'].'" class="icon icon-del-red pl dialog-open'._tooltip('Удалить страницу', -54).'</div>' : '').
			'</table>';
}


/* Автоматическое открытие диалога для ввода Пин-кода, если был переход на страницу 13 */
function PHP12_pin_dialog_open() {
	//поиск кнопки на странице
	foreach(_BE('elem_arr', 'page', 13) as $cmp)
		if($cmp['dialog_id'] == 2)
			return
			'<script>'.
				'$("'._elemAttrCmp($cmp).'").trigger("click");'.
			'</script>';

	return '';
}













function _page_div() {//todo тест
	return '';
	$send = '';
	$sql = "select * from _spisok where dialog_id=1266 order by id";
	$arr = query_arr($sql);
	$n = 1;
	foreach($arr as $r)
		foreach(explode(',', $r['txt_3']) as $v) {
			$ex = explode(':', $v);
			$send .= "INSERT INTO `_spisok` (
						`app_id`,
						`num`,
						`dialog_id`,
						`num_1`,
						`num_2`,
						`sum_1`,
						`sum_2`,
						`user_id_add`,
						`dtime_add`
					) VALUES (
						".$r['app_id'].",
						".$n++.",
						1283,
						".$r['id'].",
						".$ex[0].",
						".$ex[1].",
						".round($ex[1]*$ex[2],2).",
						".$r['user_id_add'].",
						'".$r['dtime_add']."'
					);<br>";
		}

	return ' delete from _spisok where dialog_id=1283;<br>'.$send;

	for($n=0;$n<50;$n++) {
		$a1 = rand(2,9);
		$a2 = rand(2,9);
		$a3 = rand(2,9);
		$a4 = rand(2,50);
		$a5 = rand(2,50);

		$send .= ($a1*$a2).' : '.$a1.' + '.$a3.' = <br>';
		if($a1*$a2 > $a4)
			$send .= $a1.' * '.$a2.' - '.$a4.' = <br>';
		$send .= ($a1*$a2).(rand(0,1) ? ' - ' : ' + ').$a2.(rand(0,1) ? ' - ' : ' + ').$a3.' = <br>';
	}


	$key = 'APCU_KEY7';

		$arr = array();
		for($n = 0; $n < 100; $n++)
			$arr[$n] = array(
				'txt_1' => $n*231,
				'txt_2' => $n*105
			);
		apcu_store($key, $arr);


	if(apcu_exists($key)) {
		return $key.' = '._pr(apcu_fetch($key));
	}
	return '';

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





