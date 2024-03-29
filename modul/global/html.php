<?php
function _face() {//определение, как загружена страница: iframe или сайт
	$face = 'site';

	if(_cookie('face') == 'iframe')
		$face = 'iframe';
	if(!empty($_GET['referrer']))
		$face = 'iframe';

	_cookie('face', $face);

	define('FACE', $face);
	define('SITE', FACE == 'site' ? 'site' : '');
	define('IFRAME', FACE == 'iframe');
}
function _sa($user_id=USER_ID) {
	//Список пользователей - SA
	$SA[1] = true;  //Михаил Корнилов


	$issa = isset($SA[$user_id]) ? 1 : 0;

	if(defined('SA'))
		return $issa;

	//установка флага суперпользователя SA при первом запуске
	define('SA', $issa);

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	} elseif(DEBUG)
		_cookie('debug', 1);

	return $issa;
}
function _isMobile() {//проверка: мобильная версия сайта или настольная
	//https://xdan.ru/avtomaticheskoe-opredelenie-mobilnih-brauzerov.html
	require_once GLOBAL_DIR.'/inc/MobileDetect/Mobile_Detect.php';

	$detect = new Mobile_Detect();

	if($detect->isMobile() or $detect->isTablet())
		return true;

	return false;
}

/* ---=== АВТОРИЗАЦИЯ ===--- */
function _auth() {//получение данных об авторизации из кеша
	$key = 'AUTH_'.CODE;

	$r = _cache_get($key, 1);

	if(!$r) {
		$sql = "SELECT *
				FROM `_user_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if($r = DB1::assoc($sql)) {
			$UAA = _userAppAccessGet($r['user_id'], $r['app_id']);
			$r['access_enter'] = _num(@$UAA['access_enter']);

			$data = array(
				'user_id' => $r['user_id'],
				'app_id' => $r['app_id'],
				'access_enter' => $r['access_enter']
			);

			_cache_set($key, $data, 1);
		}
	};


	if(defined('USER_ID'))
		return;

	define('USER_ID', _num(@$r['user_id']));
	define('APP_ID', _num(@$r['app_id']));

	if($PID = APP_ID)
		if($pid = _app(APP_ID, 'pid'))
			$PID = $pid;

	define('APP_PARENT', $PID);
	define('APP_IS_PID', APP_ID && APP_ID != APP_PARENT);//приложение наследует родителя
	define('USER_ACCESS', _num(@$r['access_enter']));
}
/*
function _authLoginIframe() {//проверка авторизации через iframe
	if(!IFRAME)
		return '';

	if($auth_key = @$_GET['auth_key']) {
		if(!$vk_app_id = _num(@$_GET['api_id']))
			return _authIframeError('Некорректный ID приложения.');

		$sql = "SELECT `id`
				FROM `_app`
				WHERE `vk_app_id`=".$vk_app_id."
				LIMIT 1";
		if(!$app_id = _num(DB1::value($sql)))
			return _authIframeError('Приложение не зарегистрировано.');

		if(!$viewer_id = _num(@$_GET['viewer_id']))
			return _authIframeError('Некорректный ID пользователя.');

		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=".$viewer_id."
				LIMIT 1";
		if(!$user_id = _num(DB1::value($sql)))
			return _authIframeError('Пользователя нет.');

		if($auth_key != md5($vk_app_id.'_'.$viewer_id.'_'._app($app_id, 'vk_secret')))
			return _authIframeError('Авторизация не пройдена.');

		_authSuccess($auth_key, $user_id, $app_id);
		_cookieDel('page_setup');
		header('Location:'.URL);
	}

	if(!CODE)
		return _authIframeError();

	return '';
}
*/


function _authSuccess($code, $user_id, $app_id=0) {//внесение записи об успешной авторизации
	$sql = "DELETE FROM `_user_auth` WHERE `code`='".addslashes($code)."'";
	DB1::query($sql);

	$ip = $_SERVER['REMOTE_ADDR'];
	$browser = _txt($_SERVER['HTTP_USER_AGENT']);
	$sql = "INSERT INTO `_user_auth` (
				`user_id`,
				`app_id`,
				`code`,
				`ip`,
				`browser`
			) VALUES (
				".$user_id.",
				".$app_id.",
				'".$code."',
				'".$ip."',
				'".addslashes($browser)."'
			)";
	DB1::query($sql);

	_cookie('code', $code);

	_cache_clear('AUTH_'.$code, 1);
	_cache_clear('page');
	_cache_clear('user'.$user_id);

	if(LOCAL)
		_cookie('local', 1);
}
function _authLogout() {//выход из приложения, если требуется
	if(!isset($_GET['logout']))
		return;
	if(!CODE)
		return;

	_cache_clear('AUTH_'.CODE, 1);
	_cache_clear('page');
	_cache_clear('user'.USER_ID);
	_cookieDel('page_setup');

	$sql = "DELETE FROM `_user_auth` WHERE `code`='".addslashes(CODE)."'";
	DB1::query($sql);

	_cookieDel('code');
	header('Location:'.URL);
	exit;
}
function _authPassMD5($pass) {
	return md5('655005005xX'.$pass);
}
function _authCmp($dialog, $cmp, $name) {//получение значения по имени колонки
	foreach($dialog['cmp'] as $cmp_id => $r)
		if(!empty($r['col']))
			if($r['col'] == $name)
				if(isset($cmp[$cmp_id]))
					return $cmp[$cmp_id];
	return '';
}
function _auth98($dialog, $cmp) {//регистрация нового пользователя
	if($dialog['id'] != 98)
		return;

	if(!$f = _authCmp($dialog, $cmp, 'f'))
		jsonError('Не найдена фамилия');
	if(!$i = _authCmp($dialog, $cmp, 'i'))
		jsonError('Не найдено имя');

	$pol = array(
		0 => 0,
		2073 => 2,//мужской
		2074 => 1 //женский
	);

	if(!$login = _authCmp($dialog, $cmp, 'login'))
		jsonError('Не найден логин');
	if(!$pass = _authCmp($dialog, $cmp, 'pass'))
		jsonError('Не найден пароль');

	$sql = "INSERT INTO `_user` (
				`f`,
				`i`,
				`pol`,
				`login`,
				`pass`
			) VALUES (
				'".addslashes($f)."',
				'".addslashes($i)."',
				".$pol[$cmp[2072]].",
				'".addslashes($login)."',
				'".$pass."'
			)";
	$user_id = DB1::insert_id($sql);

	$sig = md5($login.$pass.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
	_authSuccess($sig, $user_id);

	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _auth99($dialog, $cmp) {//авторизация по логину и паролю
	if($dialog['id'] != 99)
		return;

	if(!$login = _authCmp($dialog, $cmp, 'login'))
		jsonError('Не найден логин');
	if(!$pass = _authCmp($dialog, $cmp, 'pass'))
		jsonError('Не найден пароль');


	$sql = "SELECT `id`
			FROM `_user`
			WHERE `login`='".addslashes($login)."'
			  AND `pass`='".$pass."'
			LIMIT 1";
	if(!$user_id = DB1::value($sql))
		jsonError('Неверный логин или пароль');

	$sig = md5($login.$pass.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
	_authSuccess($sig, $user_id);

	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _authIframeError($msg='Вход в приложение недоступен.') {//сообщение об ошибке входа в приложение через VK iframe
	define('IFRAME_AUTH_ERROR', 1);
	return
	'<div class="bg6 pad30">'.
		'<div class="fs14 center bor-e8 bg0 pad30 clr1">'.
			$msg.
		'</div>'.
	'</div>';
}

function _pin131($dialog, $cmp) {//пользователь устанавливает свой пин-код
	if($dialog['id'] != 131)
		return;
	if(_user(USER_ID, 'pin'))
		jsonError('Пин-код уже установлен');
	if(!$pin = _authCmp($dialog, $cmp, 'pin'))
		jsonError('Не найден пин');

	$sql = "UPDATE `_user`
			SET `pin`='".$pin."'
			WHERE `id`=".USER_ID;
	DB1::query($sql);

	unset($_SESSION[PIN_KEY]);
	_cache_clear('user'.USER_ID);
	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _pin132($dialog, $cmp) {//пользователь изменяет или удаляет свой пин-код
	if($dialog['id'] != 132)
		return;
	if(!$cur = _user(USER_ID, 'pin'))
		jsonError('Пин-код не был установлен');
	if(!$pin = _authCmp($dialog, $cmp, 'txt_1'))
		jsonError('Не найден старый пин');
	if($cur != $pin)
		jsonError('Неверный текущий пин-код');

	$new = '';
	if(!_authCmp($dialog, $cmp, 'num_1'))
		if(!$new = _authCmp($dialog, $cmp, 'txt_2'))
			jsonError('Укажите новый пин-код');

	$sql = "UPDATE `_user`
			SET `pin`='".$new."'
			WHERE `id`=".USER_ID;
	DB1::query($sql);

	unset($_SESSION[PIN_KEY]);
	_cache_clear('user'.USER_ID);
	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _pin133($dialog, $cmp) {//пользователь вводит пин-код для входа в приложение
	if($dialog['id'] != 133)
		return;
	if(!$cur = _user(USER_ID, 'pin'))
		jsonError('Пин-код не был установлен');
	if(!$pin = _authCmp($dialog, $cmp, 'txt_1'))
		jsonError('Не найден пин');
	if($cur != $pin)
		jsonError('Неверный пин-код');

	$_SESSION[PIN_KEY] = time() + PIN_DURATION;

	$send['action_id'] = 1;
	jsonSuccess($send);
}


/* ---=== СОДЕРЖАНИЕ ===--- */
function _html($title, $content) {
	return
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=utf-8" />'.
		'<title>'.$title.(LOCAL ? ' - LOCAL' : '').'</title>'.
		'<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">'.
		_html_script().
	'</head>'.

	'<body class="site">'.//'.SITE.'
//		(IFRAME ? '<iframe id="frame0" name="frame0"></iframe>' : '').

	$content.

//		_pageInfo().

		_debug().
	'</body></html>';
}
function _html_script() {//скрипты и стили
	//глобальная ссылка для отправки запросов ajax
	$GET_ARR = '';
	foreach($_GET as $i => $v)
		if($v)
			$GET_ARR .= '&'.$i.'='.$v;

	return
	//Отслеживание ошибок в скриптах
(SA ? '<script src="js/errors.js"></script>' : '').

/*
(IFRAME && !LOCAL ?
	'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
	'<script>VK.init(function() {},function() {},"5.60");</script>'
: '').
*/

	'<script>'.
		'var URL="'.URL.'",'.
			'AJAX="'.AJAX.$GET_ARR.'",'.
			'SA='.SA.','.
			'USER_ID='.USER_ID.','.
			'PAGE_ID='._page('cur').','.
   (LOCAL ? 'LOCAL=true,' : '').
			'GET_ID='._num(@$_GET['id']).';'.
	'</script>'.

	'<script src="js/jquery-3.2.1.min.js?3"></script>'.
	'<script src="js/autosize.min.js?5"></script>'.

	//Установка начального значения таймера JS
	(SA ? '<script>var TIME=(new Date()).getTime();</script>' : '').

	'<link rel="stylesheet" type="text/css" href="modul/global/global'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/global/global'.MIN.'.js?'.SCRIPT.'"></script>'.

(!_isMobile() ?
	'<link rel="stylesheet" type="text/css" href="modul/global/nomobile'.MIN.'.css?'.SCRIPT.'" />'
: '').

(CODE ?
	'<link rel="stylesheet" type="text/css" href="css/jquery-ui'.MIN.'.css?3" />'.
	'<script src="js/jquery-ui.min.js?3"></script>'.

	'<script src="js/highcharts.js"></script>'.

	'<script src="js/jquery.mjs.nestedSortable'.MIN.'.js?2"></script>'.

	'<script src="js/lodash.min.js"></script>'.
	'<link rel="stylesheet" href="css/gridstack'.MIN.'.css?9" />'.//'.SCRIPT.'
	'<script src="js/gridstack'.MIN.'.js?2"></script>'.//'.SCRIPT.'
	'<script src="js/gridstack.jQueryUI'.MIN.'.js"></script>'.
	'<script src="js/ckeditor5.js?1"></script>'
: '').

	'<script src="modul/page/page'.MIN.'.js?'.SCRIPT.'"></script>'.

	_tag_script().

	'<link rel="stylesheet" type="text/css" href="modul/element/element'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/element/element'.MIN.'.js?'.SCRIPT.'"></script>'.

(CODE ?
	'<script src="modul/block/block'.MIN.'.js?'.SCRIPT.'"></script>'.

	'<script src="modul/spisok/spisok'.MIN.'.js?'.SCRIPT.'"></script>'
: '').

	_debug('style');
}
function _html_hat() {//верхняя строка приложения для сайта
	if(!defined('USER_NAME_FAM')) {
		header('Location:'.URL.'&logout');
		exit;
	}

	$local = LOCAL || !SA && !APP_ACCESS ? ' class="local"' : '';

	$title = 'Мои приложения';
	if(APP_ID)
		$title =
			'<span class="mr5">'.
				_imageHtml(_app(APP_ID, 'img'), 26, 26, false, false).
			'</span>'.
			_app(APP_ID, 'name');

	return
	'<div id="hat-prel">'.

		'<div id="hat"'.$local.'>'.
			'<div id="hat-center">'.
				'<a href="'.URL.'" class="hat-title">'.$title.'</a>'.

				'<div id="hat-user" class="'._dn(!PAS, 'ispas').'">'.
					'<div class="uname">'.USER_NAME.'</div>'.
					'<dl>'.
						'<dd onclick="location.href=\''.URL.'&p=14\'">Мои настройки'.
						'<dd id="hat-sub">'.
							'<span onclick="location.href=\''.URL.'&p=98\'">Мои приложения</span>'.
							_html_hat_MyApp().
						_hat_link_admin().
						_hat_link_task().
						_hat_link_manial().
				  (SA ? '<dd onclick="location.href=\''.URL.'&p=1\'" class="sa b">SA' : '').
				  (SA ? '<dd onclick="window.open(\'http://'.(LOCAL ? 'nyandoma/' : '').'gim-system.ru\', \'_blank\')" class="sa">GIM-system.ru' : '').
						'<dd onclick="location.href=\''.URL.'&logout\'">'.
							'Выход'.
							'<div class="icon icon-exit wh ml5 mbm3"></div>'.
					'</dl>'.
				'</div>'.

				'<div id="hat-but">'.
//					_hat_but_alert().
					_hat_but_pas().
				'</div>'.

			'</div>'.
		'</div>'.

	'</div>'.

	//шапка в зафиксированном состоянии. При изменеии ширины страницы шапка центрируется
	'<script>'.
		'function hatW(){var w=$(window).width();$("#hat").width(w<1000?1000:w)}'.
		'$(window).resize(hatW);'.
		'hatW();'.
	'</script>';
}
function _html_hat_MyApp() {
	if(!$arr = PHP12_app_list('arr'))
		return '';

	$send = '<div>'.
				'<table class="w100p mt10 mb10">';

	foreach($arr as $r) {
		$on = $r['app_id'] == APP_ID;
		$send .=
			'<tr onclick="_appEnter('.$r['app_id'].')" class="'.($on ? 'on' : '').'">'.
				'<td class="w20 pad5">'._imageHtml(_app($r['app_id'], 'img'), 25, 25, false, false).
				'<td class="l fs12 pl5'.($on ? ' b' : '').'">'._app($r['app_id'], 'name');
	}

	$send .= '</table></div>';

	return $send;
}
function _html_sa_access_msg() {//сообщение о закрытом доступе приложения для SA
	if(!SA)
		return '';
	if(APP_ACCESS)
		return '';
	return
	'<div id="sa-access-msg" class="center pad10 line-b b fs16 clr5 bg-fcc">'.
		'ВХОД В ПРИЛОЖЕНИЕ ЗАКРЫТ'.
	'</div>';
}
function _hat_link_admin() {//кнопки Администрирование
	if(PAS)
		return '';
	if(APP_IS_PID)
		return '';
	if(!SA && !USER_ADMIN)
		return '';
	if(!APP_ID)
		return '';

	$ass = array(
		'Страницы' => 12,
		'Диалоговые окна' => 123,
		'Подсказки' => 66,
		'Пользователи' => 4,
		'Шаблоны документов' => 8,
		'' => 0,
		'Счётчики' => 11,
		'Планировщик' => 10,
		'Изображения' => 17,
		'Файлы' => 18
	);

	$send = '<div style="width:160px;left:-160px">'.
				'<table class="w100p mt10 mb10">';

	foreach($ass as $name => $page_id) {
		if(!$name) {
			$send .= '<tr><td>&nbsp;';
			continue;
		}
		$send .=
			'<tr onclick="location.href=\''.URL.'&p='.$page_id.'\'">'.
				'<td class="l fs12 pl15 pt5 pb5">'.$name;
	}

	$send .= '</table></div>';


	return
	'<dd id="hat-sub">'.
		'<span onclick="location.href=\''.URL.'&p=7\'">Администрирование</span>'.
		$send;
}
function _hat_link_task() {//ссылка Задачи
	if(PAS)
		return '';
	if(APP_IS_PID)
		return '';
	if(!SA && !USER_ACCESS_TASK)
		return '';
	if(!APP_ID)
		return '';

	return '<dd onclick="location.href=\''.URL.'&p=385\'" class="b fs14">Задачи';
}
function _hat_link_manial() {//ссылка Руководство пользователя
	if(PAS)
		return '';
	if(APP_IS_PID)
		return '';
	if(!SA && !USER_ACCESS_MANUAL)
		return '';
	if(!APP_ID)
		return '';

	return '<dd onclick="location.href=\''.URL.'&p=15\'">Руководство<br>пользователя';
}
function _hat_but_alert() {//отображение кнопки оповещения об ошибках
	if(!APP_ID)
		return '';
	if(APP_IS_PID)
		return '';
	if(!SA && !USER_ADMIN)
		return '';

	return '<button id="app-error"></button>';
}
function _hat_but_pas() {//отображение кнопки настройки страницы
	if(!APP_ID)
		return '';
	if(APP_IS_PID)
		return '';
	if(!SA && !APP_ID)
		return '';
	if(!SA && !USER_ADMIN)
		return '';
	if(!$page_id = _page('cur'))
		return '';
	if(!$page = _page($page_id))
		return '';
	if(!SA && _pageSA($page))
		return '';
	if(!SA && !$page['app_id'])
		return '';

	return '<button id="page_setup" class="'._dn(!PAS, 'ispas').'"></button>';
}

function _app($app_id, $i='all') {//Получение данных о приложении
	$key = 'app'.$app_id;
	if(!$arr = _cache_get($key, 1)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".$app_id;
		if(!$arr = DB1::assoc($sql))
			die('Невозможно получить данные приложения. Кеш: '.$key);

		$arr['img'] = array();
		if($image_id = _idsFirst($arr['image_ids'])) {
			$sql = "SELECT *
					FROM `_image`
					WHERE `id`=".$image_id;
			$arr['img'] = DB1::assoc($sql);
		}

		_cache_set($key, $arr, 1);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return '_app: неизвестный ключ';

	return $arr[$i];
}
function _app_create($dialog, $app_id) {//привязка пользователя к приложению после его создания
	if($dialog['id'] != 100)
		return;
	//ID созданного приложения в таблице _app
	if(!$app_id)
		return;

	if(!$access_id = _userAppAccessCreate($app_id)) {
		_cache_clear('app'.$app_id, 1);
		return;
	}

	//изменение текущего приложения на новое
	$sql = "UPDATE `_user_auth`
			SET `app_id`=".$app_id."
			WHERE `code`='".CODE."'";
	DB1::query($sql);

	//применение прав пользователю, создавшему приложение
	$sql = "UPDATE `_user_access`
			SET `access_admin`=1,
				`access_task`=1,
				`access_manual`=1
			WHERE `id`=".$access_id;
	DB1::query($sql);

	_cache_clear('AUTH_'.CODE, 1);
	_cache_clear('page');
	_cache_clear('user'.USER_ID);

	_auth();
}
function _app_copy($dialog, $app_id) {//копирование приложения
	if($dialog['id'] != 140)
		return;
	if(!$app_id)
		return;
	if(!$access_id = _userAppAccessCreate($app_id)) {
		_cache_clear('app'.$app_id, 1);
		return;
	}

	//изменение текущего приложения на новое
	$sql = "UPDATE `_user_auth`
			SET `app_id`=".$app_id."
			WHERE `code`='".CODE."'";
	DB1::query($sql);

	//применение прав пользователю, создавшему приложение
	$sql = "UPDATE `_user_access`
			SET `access_admin`=1,
				`access_task`=1,
				`access_manual`=1
			WHERE `id`=".$access_id;
	DB1::query($sql);

	_app_copy_spisok($app_id);

	_cache_clear('AUTH_'.CODE, 1);
	_cache_clear('page');
	_cache_clear('user'.USER_ID);

	_auth();
}
function _app_copy_spisok($app_id_dst) {//копирование разрешённых данных
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=".APP_ID."
			  AND `clone_on`
			  AND `table_1`=11
			ORDER BY `name`";
	if(!$dlg = DB1::arr($sql))
		return;

	foreach($dlg as $dlg_id => $d) {
		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$dlg_id."
				  AND !`deleted`
				ORDER BY `id`";
		if(!$spisok = DB1::arr($sql))
			continue;

		$pAss = array();//ассоциации parent_id
		foreach($spisok as $id_old => $r) {
			$sql = "INSERT INTO `_spisok` (
						`id_old`,
						`cnn_id`,
						`app_id`,
						`dialog_id`,
						`num`,
	
						`parent_id`,
						`child_lvl`,
	
						`txt_1`,`txt_2`,`txt_3`,`txt_4`,`txt_5`,`txt_6`,`txt_7`,`txt_8`,`txt_9`,`txt_10`,
						`txt_11`,`txt_12`,`txt_13`,`txt_14`,`txt_15`,`txt_16`,`txt_17`,`txt_18`,`txt_19`,`txt_20`,
						`txt_21`,`txt_22`,
	
						`num_1`,`num_2`,`num_3`,`num_4`,`num_5`,`num_6`,`num_7`,`num_8`,`num_9`,`num_10`,
						`num_11`,`num_12`,`num_13`,`num_14`,`num_15`,`num_16`,`num_17`,`num_18`,`num_19`,`num_20`,
	
						`sum_1`,`sum_2`,`sum_3`,`sum_4`,`sum_5`,`sum_6`,`sum_7`,`sum_8`,`sum_9`,`sum_10`,
						`sum_11`,`sum_12`,`sum_13`,`sum_14`,`sum_15`,
	
						`date_1`,`date_2`,`date_3`,`date_4`,`date_5`,
	
						`sort`,
						`user_id_add`
					) SELECT
						".$id_old.",
						`cnn_id`,
						".$app_id_dst.",
						`dialog_id`,
						`num`,
	
						`parent_id`,
						`child_lvl`,
	
						`txt_1`,`txt_2`,`txt_3`,`txt_4`,`txt_5`,`txt_6`,`txt_7`,`txt_8`,`txt_9`,`txt_10`,
						`txt_11`,`txt_12`,`txt_13`,`txt_14`,`txt_15`,`txt_16`,`txt_17`,`txt_18`,`txt_19`,`txt_20`,
						`txt_21`,`txt_22`,
	
						`num_1`,`num_2`,`num_3`,`num_4`,`num_5`,`num_6`,`num_7`,`num_8`,`num_9`,`num_10`,
						`num_11`,`num_12`,`num_13`,`num_14`,`num_15`,`num_16`,`num_17`,`num_18`,`num_19`,`num_20`,
	
						`sum_1`,`sum_2`,`sum_3`,`sum_4`,`sum_5`,`sum_6`,`sum_7`,`sum_8`,`sum_9`,`sum_10`,
						`sum_11`,`sum_12`,`sum_13`,`sum_14`,`sum_15`,
	
						`date_1`,`date_2`,`date_3`,`date_4`,`date_5`,
	
						`sort`,
						".USER_ID."
					  FROM `_spisok`
					  WHERE `id`=".$id_old;
			$unit_id = DB1::insert_id($sql);

			$pAss[$id_old] = $unit_id;
		}

		//расстановка новых parent_id
		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".$app_id_dst."
				  AND `dialog_id`=".$dlg_id."
				  AND `parent_id`
				ORDER BY `id`";
		if($spisok = DB1::arr($sql))
			foreach($spisok as $id => $r) {
				$pid = _num(@$pAss[$r['parent_id']]);
				$sql = "UPDATE `_spisok`
						SET `parent_id`=".$pid."
						WHERE `id`=".$id;
				DB1::query($sql);
			}
	}

}
function PHP12_app_list($return='html') {//список приложений, которые доступны пользователю
	if(!USER_ID)
		return '';

	unset($_SESSION[PIN_KEY]);

	$sql = "SELECT *
			FROM `_user_access`
			WHERE `user_id`=".USER_ID."
			  AND !`app_archive`
			ORDER BY `uasort`";
	if(!$spisok = DB1::arr($sql))
		return $return == 'arr' ? array() :
			'<div class="center pad30 clr9 fs15">'.
				'Доступных приложений нет.'.
			'</div>';

	if($return == 'arr')
		return $spisok;


	$sql = "SELECT
				`app_id`,
				COUNT(*)
			FROM `_user_access`
			WHERE `app_id` IN ("._idsGet($spisok, 'app_id').")
			GROUP BY `app_id`";
	$userC = DB1::ass($sql);

	$send = '';
	foreach($spisok as $id => $r) {
		$bgCur = $r['app_id'] == APP_ID ? ' bg11' : '';
		$uc = _num($userC[$r['app_id']]);
		$send .=
		'<div class="line-b over1 over-parent'.$bgCur.'" val="'.$id.'">'.
			'<table class="bs10 w100p">'.
				'<tr><td class="w35">'.
						_imageHtml(_app($r['app_id'], 'img'), 40).
					'<td class="w500 top">'.
						'<a class="dib mt3 fs16 clr15" onclick="_appEnter('.$r['app_id'].')">'._app($r['app_id'], 'name').'</a>'.
						'<div class="mt5 fs12 clr2">'.$uc.' пользовател'._end($uc, 'ь', 'я', 'ей').'</div>'.
			  (SA ? '<td class="top center w50"><span class="clr2 fs16 ml30">'.$r['app_id'].'</span>' : '').
					'<td class="top r">'.
						'<a class="clr6 over-child dialog-open" val="dialog_id:107,edit_id:'.$id.'">Отправить в архив</a>'.
					'<td class="w35 top r">'.
			($r['access_admin'] ?
						'<div onclick="_appEnter('.$r['app_id'].',7)" class="icon icon-admin tool" data-tool="Администрирование"></div>'
			: '').
					'<td class="w35 top r">'.
						'<div class="icon icon-move pl over-child"></div>'.
			'</table>'.
		'</div>';
	}

	return $send;
}
function PHP12_app_archive() {//список приложений, отправленные в архив
	if(!USER_ID)
		return '';

	$sql = "SELECT *
			FROM `_user_access`
			WHERE `user_id`=".USER_ID."
			  AND `app_archive`
			ORDER BY `uasort`";
	if(!$spisok = DB1::arr($sql))
		return
			'<div class="center pad30 clr9 fs15">'.
				'Архивных приложений нет.'.
			'</div>';

	$send = '';
	foreach($spisok as $id => $r) {
		$bgCur = $r['app_id'] == APP_ID ? ' bg11' : '';
		$send .=
		'<div class="line-b over1 over-parent'.$bgCur.'" val="'.$id.'">'.
			'<table class="bs10 w100p">'.
				'<tr><td class="w500 top">'.
						'<a class="dib mt3 fs16 clr15" onclick="_appEnter('.$r['app_id'].')">'._app($r['app_id'], 'name').'</a>'.
			  (SA ? '<td class="top center w50"><span class="clr2 fs16 ml30">'.$r['app_id'].'</span>' : '').
					'<td class="w300 top r">'.
						'<a class="clr11 over-child dialog-open" val="dialog_id:106,edit_id:'.$id.'">Восстановить из архива</a>'.
			'</table>'.
		'</div>';
	}

	return $send;
}

function _contentMsg($msg='') {
	if(!$msg) {
		$_GET['p'] = 0;
		$msg = 'Несуществующая страница<br><br><a href="'.URL.'&p='._page('cur').'">Перейти на <b>стартовую страницу</b></a>';
	}
	return '<div class="_empty mar20">'.$msg.'</div>';
}






