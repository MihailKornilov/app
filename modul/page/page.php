<?php
function _pageCache() {//получение массива страниц из кеша
	$key = 'page';
	if($arr = _cache_get($key))
		return $arr;

	$sql = "SELECT *
			FROM `_page`
			WHERE `app_id` IN (0,".APP_PARENT.")
			ORDER BY `sort`";
	if(!$page = DB1::arr($sql))
		return array();

	//получение количества блоков по каждой странице
	$sql = "SELECT
				`obj_id`,
				COUNT(*) `c`
			FROM `_block`
			WHERE `obj_name`='page'
			  AND `obj_id` IN ("._idsGet($page).")
			GROUP BY `obj_id`";
	$block = DB1::ass($sql);

	foreach($page as $id => $r) {
		unset($page[$id]['about']);
		unset($page[$id]['user_id_add']);
		unset($page[$id]['dtime_add']);
		$block_count = _num(@$block[$id]);
		$page[$id]['del_allow'] = $block_count || $r['common_id'] ? 0 : 1;
	}

	return _cache_set($key, $page);
}
function _pageSA($p) {//определение страницы, доступной только SA
	if(empty($p['acs']))
		return false;
	if($p['acs'] == 2)
		return true;
	return false;
}
function _pageAccess($page_id) {//доступ к конкретного странице для текущего пользователя
	if(SA)
		return true;

	//скрытая страница
	$page = _page($page_id);
	if($page['hidden'])
		return false;

	$u = _user();
	$ass = _idsAss($u['access_pages']);

	//разрешение страниц, видимых всем пользователям
	foreach(_page() as $id => $p) {
		if($p['dialog_id'] == 101) {
			if(!$p['acs']
			|| $p['acs'] == 1 && USER_ADMIN
			|| $p['acs'] == 3 && USER_ACCESS_MANUAL
			|| $p['acs'] == 4 && USER_ACCESS_TASK)
				$ass[$id] = 1;
			continue;
		}
		if(USER_ADMIN)
			$ass[$id] = 1;
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
			if(_pageSA($r))//только SA
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
			if(_pageSA($p))
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
				if(_pageSA($p) && $p['def'])
					return $p['id'];

		//иначе Администрирование
		if(USER_ADMIN)
			return 7;

		return 14;
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
		if(_pageSA($r))
			continue;
		if(!$r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => addslashes(htmlspecialchars_decode(trim($r['name']))),
			'content' => '<div class="fs'.(15-$level).' '.($level ? 'ml'.($level*20) : 'b').'">'.addslashes(htmlspecialchars_decode(trim($r['name']))).'</div>'
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
		if(!_pageSA($r) && $r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => addslashes(htmlspecialchars_decode(trim($r['name']))),
			'content' => '<div class="'.(_pageSA($r) ? 'clr8' : 'clr11').'">'.addslashes(htmlspecialchars_decode(trim($r['name']))).'</div>'
		);
		if(!empty($child[$r['id']]))
			foreach(_pageSaForSelect($child[$r['id']], $child) as $sub)
				$send[] = $sub;
	}

	return $send;

}

function _pageIframe() {//показ страницы, если приложение было запущено через фрейм в ВК
	if(empty($_GET['referrer']))
		return;

	die(_html('iframe', _blockHtml('page', 20)));
}
function _pageAuth() {//страница авторизации
	if(USER_ID)
		return;

	$content =
	'<div class="center mt40">'.
		'<div class="w1000 pad30 dib mt40">'.
			'<button class="vk w200" onclick="_authVk'.(LOCAL ? 'Local' : '').'(this)">Войти через VK</button>'.
			'<br>'.
			'<button class="vk w200 grey mt10 dialog-open" val="dialog_id:99">Войти по логину и паролю</button>'.
			'<br>'.
			'<button class="vk small green mt10 dialog-open" val="dialog_id:98">Регистрация</button>'.
		'</div>'.
	'</div>'.
	_pageScript(98).
(!LOCAL ?
	'<script src="https://vk.com/js/api/openapi.js?152"></script>'.
	'<script>VK.init({apiId:'.AUTH_APP_ID.'});</script>'
: '');

	die(_html('Авторизация', $content));
}
function _pageGlobalDeny() {//страница (19) с сообщением о тех-работах
	if(SA)
		return;
	if(APP_ACCESS)
		return;

	$CNT = '<div id="_content">'._blockHtml('page', 19).'</div>';

	die(_html('Тех-работы', $CNT));
}
function _pageAppUserAccess() {//страница (105): доступ пользователю в приложение запрещён
	if(SA)
		return;
	if(!APP_ID)
		return;
	if(USER_ACCESS)
		return;
	if(_page('cur') == 98)
		return;

	$app = _app(APP_ID);

	$CNT =
		_html_hat().
		'<div id="_content">'._blockHtml('page', 105).'</div>';

	die(_html($app['name'], $CNT));
}
function _pageContent() {//приложение в работе
	if(!USER_ID)
		return '';
	if(!APP_ID)
		return '';

	$page_id = _page('cur');
	_userActive($page_id);

	$app = _app(APP_ID);

	$CNT =
	_html_hat().
	_html_sa_access_msg().
	_pasMenu().
	'<div id="_content" class="block-content-page site">'.
		_elem97print($page_id).//независимая кнопка
		_pageShow($page_id).
	'</div>';

	die(_html($app['name'], $CNT));
}
function _page98() {//список приложений пользователя
	$CNT =
		_html_hat().
		'<div id="_content">'._blockHtml('page', 98).'</div>'.
		_pageScript(98);

	die(_html('Мои приложения', $CNT));
}

function _pasDefine() {//установка флага включения управления страницей PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//страница существует
		if($page = _page($page_id))//данные страницы получены
			if(!(_pageSA($page) && !SA))
				if(!(!$page['app_id'] && !SA))
					if($pas = _num(@$_COOKIE['page_setup']))
						if($pas != $page_id)
							$pas = 0;

	if(!$pas)
		_cookieDel('page_setup');

	define('PAS', APP_ID && $pas ? $page_id : 0);
//	define('PAS', 1);//для настройки страниц, которые доступны всем приложениям
}
function _pasMenu() {//строка меню управления страницей
//	if(IFRAME_AUTH_ERROR)
//		return '';
	if(!PAS)
		return '';

	return
	'<div id="pas">'.
		'<div class="mara pt5">'.
			'<div class="dib fs16 b">'.
				_page('name').
				_blockLevelPageEdit().
			'</div>'.
		'</div>'.
		'<div class="mara pt5">'.
			_blockLevelChange('page', _page('cur')).
		'</div>'.
	'</div>';
}
function _pageInfo() {//информация о странице
	if(!SA)
		return '';
//	if(IFRAME_AUTH_ERROR)
//		return '';
	if(!PAS)
		return '';

	$page_id = _page('cur');
	$page = _page($page_id);

	$blk = _BE('block_arr', 'page', $page_id);
	$elm = _BE('elem_arr', 'page', $page_id);

	return
	'<div class="bg14 line-b">'.
		'<div class="w1000 mara pad5">'.

			'<table class="w300">'.
				'<tr class="center">'.
					'<td>APP_ID: '.$page['app_id'].
					'<td class="'.(_pageSA($page) ? 'fs15 b clr8' : 'clr2').'">SA'.
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

	$user = _user($u['id']);

	//доступ в приложение
	if(!$user['access_enter'])
		return _emptyRed10('Вход в приложение запрещён.');

	//доступные страницы
	$ids = _idsAss($user['access_pages']);

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
				`ua`.`access_enter`
			FROM
				`_user` `u`,
				`_user_access` `ua`
			WHERE `ua`.`app_id`=".APP_ID."
			  AND `u`.`id`=`ua`.`user_id`
			ORDER BY `ua`.`id`";
	if(!$user = DB1::arr($sql))
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
						'value' => $r['access_enter']
					));

	$send .= '</table>';

	return $send;
}
function PHP12_app_enter_for_all_user_save($cmp, $val, $unit) {//сохранение доступа в приложение для всех пользователей
	$sql = "UPDATE `_user_access`
			SET `access_enter`=0
			WHERE `app_id`=".APP_ID;
	DB1::query($sql);

	if($ids = _ids($val)) {
		$sql = "UPDATE `_user_access`
				SET `access_enter`=1
				WHERE `app_id`=".APP_ID."
				  AND `user_id` IN (".$ids.")";
		DB1::query($sql);
	}

	$sql = "UPDATE `_user_auth`
			SET `app_id`=0
			WHERE `app_id`=".APP_ID."
			  AND `user_id` NOT IN (".$ids.")";
	DB1::query($sql);

	_cache_clear('AUTH _'.CODE, 1);
	_cache_clear('page');

	$sql = "SELECT *
			FROM `_user_access`
			WHERE `app_id`=".APP_ID;
	foreach(DB1::arr($sql) as $r)
		_cache_clear('user'.$r['user_id']);
}



function _pageShow($page_id) {
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
	if($page_id == 105 && APP_ID && USER_ACCESS)
		$page_id = _page('def');

	if(!$page = _page($page_id))
		return _empty20('Несуществующая страница.'._pageUrlBack());
	if(!_pageAccess($page_id))
		return _empty20('Страница недоступна или не существует.'._pageUrlBack());


	$prm = array();

	//страница принимает данные записи
	if($dialog_id = $page['dialog_id_unit_get']) {
		if(!$id = $page['unit_id'])
			if(!$id = _num(@$_GET['id']))
				return _empty20('Некорректный идентификатор записи.'._pageUrlBack());
		if(!$dialog = _dialogQuery($dialog_id))
			return _empty20('Отсутствует диалог, который вносит данные записи.'._pageUrlBack());
		if(!$prm['unit_get'] = _spisokUnitQuery($dialog, $id))
			return _empty20('Записи '.$id.' не существует.'._pageUrlBack());
	}

	return
	_blockHtml('page', $page_id, $prm).
	_page_div().
	_pageScript($page_id, $prm);
}
function _pageScript($page_id, $prm=array()) {

	$send = 'var BLKK='._json(_BE('block_arr', 'page', $page_id)).';'.
			'var ELMM='._json(_elmJs('page', $page_id, $prm)).';';

	if(!PAS) {
		if(APP_ID && USER_ID)
			$send .= 'var FILTER='._json(_filter('page_js'), 1).';';
		$send .=
			'_ELM_JS(ELMM);'.
			_pageJsElmFocus($page_id).
			'var HINT='._json(_hintMass()).';'.
			_pageJsDlgOpenAuto().
			_userInviteDlgOpen().
			_blockFlash();
	}

	return '<script>'.$send.'</script>';
}
function _pageJsElmFocus($page_id) {//установка фокуса на указанный элемент
	if(!$elem_id = _elemJsFocus('page', $page_id))
		return '';
	return '_ELM_FOCUS('.$elem_id.');';
}
function _pageJsDlgOpenAuto() {//автоматическое открытие указанного диалога после печати страницы
	if(!APP_ID)
		return '';
	if(!USER_ID)
		return '';
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `open_auto`
			LIMIT 1";
	if(!$dlg = DB1::assoc($sql))
		return '';

	return '_dialogLoad({dialog_id:'.$dlg['id'].'});';
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
function _blockFlash() {//подсветка блока, если нужно на него указать
	if(!$block_id = _num(@$_GET['block_flash']))
		return '';

	return '$("#bl_'.$block_id.'")._flash({color:"red"});';
}
function _pageUrlBack() {//ссылка возврата на предыдущую страницу
	if(!$pfrom = _num(@$_GET['pfrom']))
		return '<br><br><a href="'.URL.'&p='._page('def').'">Перейти на <b>стартовую страницу</b></a>';

	$uid = '';
	if($id = _num(@$_GET['id']))
		$uid = '&id='.$id;
	
	return '<br><br><a href="'.URL.'&p='.$pfrom.$uid.'">Вернуться на предыдущую страницу</a>';
}

function _document() {//формирование документа для вывода на печать
	if(!APP_ID)
		return _empty20('Не выполнен вход в приложение'._pageUrlBack());
	if(!$doc_id = _num(@$_GET['doc_id']))
		return _empty20('Некорректный id шаблона документа'._pageUrlBack());

	//получение данных шаблона
	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$doc_id;
	if(!$TMP = DB1::assoc($sql))
		return _empty20('Шаблона документа '.$doc_id.' не существует'._pageUrlBack());

	//получение данных файла-шаблона
	if(!$attach_id = $TMP['attach_id'])
		return _empty20(
				'Не настроен файл-шаблон. '.
				'<a href="'.URL.'&p=8">Настроить</a>'.
				_pageUrlBack()
		);

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$attach_id;
	if(!$ATT = DB1::assoc($sql))
		return _empty20('Файла-шаблона '.$attach_id.' не существует'._pageUrlBack());

	if(!file_exists($ATT['path'].$ATT['fname']))
		return _empty20(
					'Файл-шаблон <b>'.$ATT['oname'].'</b> отсутствует на сервере. '.
					'<a href="'.URL.'&p=8">Настроить</a>'.
					_pageUrlBack()
			   );

	//получение данных записи
	if(!$dlg_id = $TMP['spisok_id'])
		return _empty20('Не указан список, из которого берутся данные'._pageUrlBack());
	if(!$DLG = _dialogQuery($dlg_id))
		return _empty20('Диалога '.$dlg_id.' не существует'._pageUrlBack());
	if(!$unit_id = _num(@$_GET['id']))
		return _empty20('Отсутствует id записи'._pageUrlBack());

	_kupez_ob($ATT, $TMP);//todo формирование объявлений Купец в формате WORD

	if(!$unit = _spisokUnitQuery($DLG, $unit_id))
		return _empty20('Записи '.$unit_id.' не существует'._pageUrlBack());

	switch(_document_ftype($ATT)) {
		case 'docx':
			require_once GLOBAL_DIR.'/inc/PhpWord/vendor/autoload.php';
			$document = new \PhpOffice\PhpWord\TemplateProcessor($ATT['path'].$ATT['fname']);

			//подстановка данных
			foreach(_document_values($TMP, $unit) as $i => $v)
				$document->setValue($i, $v);

			header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
			header('Content-Disposition: attachment; filename="'._document_fname($ATT, $TMP, 'docx').'"');
			$document->saveAs('php://output');
			exit;

		case 'xlsx':
			require_once GLOBAL_DIR.'/inc/PHPSpreadsheet/vendor/autoload.php';

			$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
			$spreadsheet = $reader->load($ATT['path'].$ATT['fname']);
			foreach($spreadsheet->getSheetNames() as $sheet_id => $name) {
				$spreadsheet->setActiveSheetIndex($sheet_id);
				$sheet = $spreadsheet->getActiveSheet();

				$ass = _document_values($TMP, $unit);

				foreach($sheet->getRowIterator() as $row) {
				    $cellIterator = $row->getCellIterator();
				    $cellIterator->setIterateOnlyExistingCells(FALSE);
				    foreach($cellIterator as $cell) {
				        $v = $cell->getValue();
				        if(strpos($v, '{') !== false)
				            foreach($ass as $i => $txt) {
				                if(is_array($txt)) {
							        if($v == $i) {
								        $ass[$i]['unit']['row'] = $cell->getRow();
								        $ass[$i]['unit']['document_ext'] = 'xslx';
								        $ass[$i]['unit']['sheet'] = $sheet;
							        }
							        continue;
						        }
						        $v = str_replace($i, $txt, $v);
						        $v = str_replace('&quot;', '"', $v);
						        $cell->setValue($v);
					        }
				    }
				}

				//вставка списков, если есть
				foreach($ass as $i => $v)
					if(is_array($v))
						_element('template_docx', $v['el'], $v['unit']);
			}

			$spreadsheet->setActiveSheetIndex(0);

			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
			header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="'._document_fname($ATT, $TMP, 'xlsx').'"');
			$writer->save('php://output');
			exit;

		default: return _empty20('Некорректный файл-шаблон'._pageUrlBack());
	}
}
function _document_ftype($ATT) {//получение расширения файла
	$ex = explode('.', $ATT['fname']);
	$c = count($ex) - 1;
	return $ex[$c];
}
function _document_fname($ATT, $TMP, $type) {//формирование имени файла-шаблона для загрузки
	$fname = $ATT['fname'];
	if($TMP['fname']) {
		$fname = $TMP['fname'];
		$ex = explode('.', $fname);
		$c = count($ex) - 1;
		if($ex[$c] != $type)
			$fname .='.'.$type;
	}
	return $fname;
}
function _document_values($TMP, $unit) {//получение значений для подмены (ассоциативный массив)
	$ass = array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN ("._ids($TMP['param_ids']).")";
	if(!$arr = DB1::arr($sql))
		return array();

	foreach($arr as $el) {
		$i = $el['txt_10'];

		if(strpos($i, '_COUNT}'))
			$v = _element('vvv_count', $el, $unit);
		else {
			$v = _element('template_docx', $el, $unit);

			//если получен массив, это означает, что будет вставляться список. Подготовка данных для вставки списка
			if(is_array($v)) {
				$v = array(
					'el' => $el,
					'unit' => $unit
				);
			} else {
				if(strpos($i, '_PROPIS}'))
					if($sum = round($v))
						$v = _numToWord($sum, true, true);
				$v = strip_tags($v);
			}
		}


		$ass[$i] = $v;
	}

	return $ass;
}
//todo Купец: формирование списка объявлений по номеру газеты
function _kupez_ob($ATT, $TMP) {
	if(APP_ID != 4)
		return false;
	if(!$elem_id = _num(@$_GET['elem_filter']))
		return false;

	require_once GLOBAL_DIR.'/inc/PhpWord/vendor/autoload.php';
	$word = new \PhpOffice\PhpWord\PhpWord();
	$fname = _document_fname($ATT, $TMP, 'docx');

	$section = $word->addSection(
		array(
			'orientation' => 'portrait',
			'marginLeft' => floor(10*56.7),
			'marginRight' => floor(10*56.7),
			'marginTop' => floor(5*56.7),
			'marginBottom' => floor(5*56.7)
         )
	);
	header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header('Content-Disposition: attachment; filename="'.$fname.'"');

	//стили для вывода ошибки
	$errStyle = array(
		'color' => 'EE0000',
		'size' => 11,
		'bold' => true
	);

	if(!$v = _filter('v', $elem_id)) {
		$section->addText('Не получено значение фильтра', $errStyle);
		$word->save('php://output');
		exit;
	}


	$ex = explode('-', $v);
	if(!$id = _num(@$ex[1])) {
		$section->addText('Не получен идентификатор номера газеты из фильтра', $errStyle);
		$word->save('php://output');
		exit;
	}

	//данные номера выпуска
	$sql = "SELECT *
			FROM `_spisok`
			WHERE `id`=".$id;
	if(!$gn = DB1::assoc($sql)) {
		$section->addText('Не получены данные номера газеты', $errStyle);
		$word->save('php://output');
		exit;
	}

	$txt = $section->addTextRun();
	$txt->addText('Список объявлений для номера ', array('size' => 12));
	$txt->addText($gn['num_2'], array('size' => 12, 'bold' => true));
	$txt->addText(':', array('size' => 12));

	//выходы по номеру выпуска
	$sql = "SELECT *
			FROM `_spisok`
			WHERE `num_5`=".$gn['id']."
			  AND `num_2`";
	if(!$gns = DB1::arr($sql)) {
		$section->addText('Выходов объявлений по номеру нет', $errStyle);
		$word->save('php://output');
		exit;
	}

	//объявления по номеру выпуска
	$sql = "SELECT
				*,
				'' `dop`
			FROM `_spisok`
			WHERE `id` IN ("._idsGet($gns, 'num_2').")
			  AND `dialog_id`=1477
			  AND `num_6`=1
			  AND !`deleted`
			ORDER BY `txt_1`";
	if(!$ob = DB1::arr($sql)) {
		$section->addText('Объявлений по номеру нет', $errStyle);
		$word->save('php://output');
		exit;
	}

	//доп. параметры объявлений
	$sql = "SELECT
				`id`,
				`txt_1`
			FROM `_spisok`
			WHERE `dialog_id`=1481
			  AND !`deleted`";
	$dop = DB1::ass($sql);

	foreach($gns as $r) {
		$obid = $r['num_2'];
		if(!isset($ob[$obid]))
			continue;
		if(!$r['num_6'])
			continue;
		if(!isset($dop[$r['num_6']]))
			continue;

		$ob[$obid]['dop'] = $dop[$r['num_6']];
	}

	//рубрики
	$sql = "SELECT
				`id`,
				`parent_id`,
				`txt_1`,
				0 `obc` /* количество объявлений в рубрике */
			FROM `_spisok`
			WHERE `dialog_id`=1478
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$rub = DB1::arr($sql)) {
		$section->addText('Не получены рубрики объявлений', $errStyle);
		$word->save('php://output');
		exit;
	}

	//расстановка объявлений по рубрикам
	foreach($ob as $r) {
		if(!$rub_id = _num($r['num_2'])) {
			$section->addText('У объявления '.$r['num'].' отсутствует рубрика', $errStyle);
			$word->save('php://output');
			exit;
		}

		if(!isset($rub[$rub_id]['ob']))
			$rub[$rub_id]['ob'] = array();

		$rub[$rub_id]['ob'][] = $r;
		$rub[$rub_id]['obc']++;
	}

	//вставка подрубрик в рубрики
	foreach($rub as $id => $r) {
		if(!$pid = _num($r['parent_id']))
			continue;

		if(!isset($rub[$pid]['sub']))
			$rub[$pid]['sub'] = array();

		$rub[$pid]['sub'][] = $r;

		if(!empty($r['ob']))
			$rub[$pid]['obc'] += count($r['ob']);
	}


	foreach($rub as $id => $r) {
		if($r['parent_id'])
			continue;
		if(!$r['obc'])
			continue;

		$section->addText(
			$r['txt_1'],
			array(
				'size' => 13.5,
				'bold' => true
			),
			array(
				'spaceBefore' => 300,
				'spaceAfter' => 0
			)
		);

		if(!empty($r['sub']))
			foreach($r['sub'] as $sub) {
				if(empty($sub['ob']))
					continue;

				//печать названия подрубрики
				$section->addText(
					'        '.$sub['txt_1'],
					array(
						'name' => 'Times New Roman',
						'size' => 12,
						'bold' => true
					),
					array(
						'spaceBefore' => 70,
						'spaceAfter' => 0
					)
				);

				foreach($sub['ob'] as $ob)
					_kupez_ob_txt($section, $ob);
			}


		if(empty($r['ob']))
			continue;

		foreach($r['ob'] as $ob)
			_kupez_ob_txt($section, $ob);
	}

	header('Content-Disposition: attachment; filename="'.str_replace('.', '-'.$gn['num_2'].'.', $fname).'"');
	$word->save('php://output');
	exit;
}
function _kupez_ob_txt($section, $ob) {
	$txt = $section->addTextRun(
		array(
			'size' => 10.5,
			'spaceBefore' => 0,
			'spaceAfter' => 0
		)
	);
	$txt->addText(
		$ob['txt_1'],
		array('name' => 'Times New Roman')
	);
	if($ob['txt_2'])
		$txt->addText(
			' Тел.: '.$ob['txt_2'],
			array(
				'name' => 'Times New Roman',
				'bold' => true
			)
		);
	if($ob['txt_3'])
		$txt->addText(
			' Адрес: '.$ob['txt_3'],
			array(
				'name' => 'Times New Roman',
				'bold' => true
			)
		);
	if($ob['dop'])
		$txt->addText(
			' ('.$ob['dop'].')',
			array(
				'name' => 'Times New Roman',
				'italic' => true
			)
		);
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
		   ($r['def'] ? '<div class="icon icon-ok curD ml10 tool" data-tool="Стартовая страница"></div>' : '').
					'<td class="w25">'.
		($r['hidden'] ? '<div class="icon icon-cancel tool" data-tool="Скрытая страница"></div>' : '').
					'<td class="w50">'.
						'<div val="dialog_id:20,edit_id:'.$r['id'].'" class="icon icon-edit pl dialog-open tool" data-tool="Редактировать страницу"></div>'.
	 ($r['del_allow'] ? '<div val="dialog_id:20,del_id:'.$r['id'].'" class="icon icon-del-red pl dialog-open tool" data-tool="Удалить страницу"></div>' : '').
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











function _page_div($issa=false) {//todo тест

	return '';


	if(@$_GET['set'])
		_cookie('AAA', 122);

	if(@$_GET['clear']) {
		_cookieDel('AAA');
		_cookieDel('debug');
	}



	return
	'debug='._cookie('debug').
	'<br>'.
	'<a href="'.URL.'&set=1">set</a>'.
	'<br>'.
	'<a onclick="_cookie(\'AAA\',300);alert(\'setted\')">JS set</a>'.
	'<br>'.
	'<a onclick="alert(_cookie(\'AAA\'))">JS get</a>'.
	'<br>'.
	'<br>'.
	'<a href="'.URL.'&clear=1">clear</a>'.
	'<br>'.
	'<a onclick="_cookieDel(\'AAA\');_cookieDel(\'debug\');alert(\'deleted\')">JS del</a>'.
	'<br>'.
	'<br>'.
	'<a href="'.URL.'" class="b">UPD</a>'.
	'<br>'.
	_pr($_COOKIE);

	return '';

	$sql = "SELECT *
			FROM `_dialog`
			WHERE !`dialog_id_parent`
			  AND !`insert_on`
			ORDER BY `id`";
	$arr = DB1::arr($sql);

	$sql = "SELECT *
			FROM `_dialog`
			ORDER BY `id`";
	$DLG = DB1::arr($sql);

	$send = '<div>Всего: '.count($arr).'</div>';

	$send .= '<table class="_stab small">'.
				'<tr><th>APP_ID'.
					'<th>DLG_ID'.
					'<th>Таблица'.
					'<th>Имя диалога'.
					'<th>dialog_id_parent';
//					'<th>Род.таблица'.
//					'<th>Родитель<br>в родителе';
	foreach($arr as $id => $r) {
//		$PAR = $DLG[$r['dialog_id_parent']];

		$send .=
			'<tr><td class="r">'.$r['app_id'].
				'<td class="r">'.$id.
				'<td>'._table($r['table_1']).
				'<td>'.$r['name'].
				'<td class="r">'.$r['dialog_id_parent'];
//				'<td>'._table($PAR['table_1']).
//				'<td class="r">'.$PAR['dialog_id_parent'];
	}
	$send .= '</table>';


	return $send;





	return '';
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





