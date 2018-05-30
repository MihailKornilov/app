<?php
function _face() {//определение, как загружена страница: iframe или сайт
	$face = 'site';

	if(@$_COOKIE['face'] == 'iframe')
		$face = 'iframe';
	if(!empty($_GET['referrer']))
		$face = 'iframe';

	setcookie('face', $face, time() + 2592000, '/');

	define('FACE', $face);
	define('SITE', FACE == 'site' ? 'site' : '');
	define('IFRAME', FACE == 'iframe');
}
function _saDefine() {//установка флага суперпользователя SA
	//Список пользователей - SA
	$SA[1] = true;  //Михаил Корнилов
//	$SA[18] = true;
	$SA[53] = true;

	define('SA', isset($SA[USER_ID]) ? 1 : 0);

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	}
}

/* ---=== АВТОРИЗАЦИЯ ===--- */
function _auth() {//получение данных об авторизации из кеша
	if(!$r = _cache_get('AUTH', 1)) {
		$sql = "SELECT *
				FROM `_user_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if($r = query_assoc($sql)) {
			$sql = "SELECT `num_1`
					FROM `_spisok`
					WHERE `app_id`=".$r['app_id']."
					  AND `connect_1`=".$r['user_id'];
			$r['access'] = _num(query_value($sql));

			$data = array(
				'user_id' => $r['user_id'],
				'app_id' => $r['app_id'],
				'access' => $r['access']
			);

			_cache_set('AUTH', $data, 1);
		}
	};

	if(defined('USER_ID'))
		return;

	define('USER_ID', _num(@$r['user_id']));
	define('APP_ID', _num(@$r['app_id']));
	define('APP_ACCESS', _num(@$r['access']));

/*
	//SA: вход от имени другого пользователя
	if(SA && $user_id = _num(@$_GET['user_id'])) {
		$sql = "SELECT COUNT(*)
				FROM `_vkuser`
				WHERE `viewer_id`=".$user_id;
		if(query_value($sql)) {
			$sql = "UPDATE `_vkuser_auth`
					SET `viewer_id_show`=".$user_id.",
						`app_id`=0
					WHERE `code`='".CODE."'";
			query($sql);

			_cache_('clear', 'USER_'.USER_ID);
			_cache_('clear', 'AUTH');

			header('Location:'.URL);
			exit;
		}
	}
*/
}
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
		if(!$app_id = _num(query_value($sql)))
			return _authIframeError('Приложение не зарегистрировано.');

		if(!$viewer_id = _num(@$_GET['viewer_id']))
			return _authIframeError('Некорректный ID пользователя.');

		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=".$viewer_id."
				LIMIT 1";
		if(!$user_id = _num(query_value($sql)))
			return _authIframeError($sql.'Пользователя нет.');

		if($auth_key != md5($vk_app_id.'_'.$viewer_id.'_'._app($app_id, 'vk_secret')))
			return _authIframeError('Авторизация не пройдена.');

		_authSuccess($auth_key, $user_id, $app_id);
		header('Location:'.URL);
	}

	if(!CODE)
		return _authIframeError();

	return '';
}
function _authLoginSite() {//страница авторизации через сайт
	if(!defined('IFRAME_AUTH_ERROR'))
		define('IFRAME_AUTH_ERROR', 0);
	if(CODE)
		return '';
	if(!SITE)
		return '';

	return
	'<div class="center mt40">'.
		'<div class="w1000 pad30 dib mt40">'.
			'<button class="vk w200" onclick="_authVk'.(LOCAL ? 'Local' : '').'(this)">Войти через VK</button>'.
			'<br>'.
			'<button class="vk w200 grey mt10 dialog-open" val="dialog_id:99">Войти по логину и паролю</button>'.
		'</div>'.
	'</div>'.
(!LOCAL ?
	'<script src="https://vk.com/js/api/openapi.js?152"></script>'.
	'<script>VK.init({apiId:'.AUTH_APP_ID.'});</script>'
: '');
}
function _authSuccess($code, $user_id, $app_id=0) {//внесение записи об успешной авторизации
	$sql = "DELETE FROM `_user_auth` WHERE `code`='".addslashes($code)."'";
	query($sql);

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
	query($sql);

	//отметка даты последнего посещения пользователя
	$sql = "UPDATE `_user`
			SET `dtime_last`=CURRENT_TIMESTAMP
			WHERE `id`=".$user_id;
	query($sql);

	setcookie('code', $code, time() + 2592000, '/');

	_cache_clear( 'AUTH', 1);
	_cache_clear( 'page');
	_cache_clear( 'user'.$user_id);

	if(LOCAL)
		setcookie('local', 1, time() + 2592000, '/');
}
function _authLogout() {//выход из приложения, если требуется
	if(!isset($_GET['logout']) && @$_GET['p'] != 98)
		return;
	if(!CODE)
		return;

	_cache_clear( 'AUTH', 1);
	_cache_clear( 'page');
	_cache_clear( 'user'.USER_ID);

	//выход только из приложения и попадание в список приложений
	if(APP_ID) {
		$sql = "UPDATE `_user_auth`
				SET `app_id`=0
				WHERE `code`='".CODE."'";
		query($sql);
		header('Location:'.URL);
		exit;
	}

	$sql = "DELETE FROM `_user_auth` WHERE `code`='".addslashes(CODE)."'";
	query($sql);

	setcookie('code', '', time() - 1, '/');
	header('Location:'.URL);
	exit;
}
function _authPassMD5($pass) {
	return md5('655005005xX'.$pass);
}
function _auth98($dialog, $cmp) {//регистрация нового пользователя
	if($dialog['id'] != 98)
		return;

	$f = $cmp[2065];
	$i = $cmp[2066];
	$pol = array(
		0 => 0,
		2073 => 1749,//мужской
		2074 => 1750 //женский
	);
	$login = $cmp[2069];
	$pass = $cmp[2070];

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
				'"._authPassMD5($pass)."'
			)";
	query($sql);

	$user_id = query_insert_id('_user');

	$sig = md5($login.$pass.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
	_authSuccess($sig, $user_id);

	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _auth99($dialog, $cmp) {//авторизация по логину и паролю
	if($dialog['id'] != 99)
		return;
	if(empty($cmp[2058]))
		jsonError('Не указан логин');
	if(empty($cmp[2059]))
		jsonError('Не указан пароль');

	$login = $cmp[2058];
	$pass = $cmp[2059];

	$sql = "SELECT `id`
			FROM `_user`
			WHERE `login`='".addslashes($login)."'
			  AND `pass`='"._authPassMD5($pass)."'
			LIMIT 1";
	if(!$user_id = _num(query_value($sql)))
		jsonError('Неверный логин или пароль');

	$sig = md5($login.$pass.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
	_authSuccess($sig, $user_id);

	$send['action_id'] = 1;
	jsonSuccess($send);
}
function _authIframeError($msg='Вход в приложение недоступен.') {//сообщение об ошибке входа в приложение через VK iframe
	define('IFRAME_AUTH_ERROR', 1);
	return
	'<div class="bg-gr1 pad30">'.
		'<div class="fs14 center bor-e8 bg-fff pad30 grey">'.
			$msg.
		'</div>'.
	'</div>';
}



/* ---=== СОДЕРЖАНИЕ ===--- */
function _html() {
	return
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>'._html_title().'</title>'.
		'<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">'.
		_html_script().
	'</head>'.

	'<body class="'.SITE.'">'.
		(IFRAME ? '<iframe id="frame0" name="frame0"></iframe>' : '').

		_authLoginIframe().
		_authLoginSite().

		_html_hat().
		_pasMenu().
		_app_content().

		_debug().
	'</body></html>';
}
function _html_title() {
	if(!CODE)
		return 'Авторизация';
	if(!APP_ID)
		return 'Мои приложения';

	return _app(APP_ID, 'name');
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

(IFRAME && !LOCAL ?
	'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
	'<script>VK.init(function() {},function() {},"5.60");</script>'
: '').

	'<script>'.
		'var URL="'.URL.'",'.
			'AJAX="'.AJAX.$GET_ARR.'",'.
			'SA='.SA.','.
			'USER_ID='.USER_ID.','.
			'PAGE_ID='._page('cur').';'.
	'</script>'.

	'<script src="js/jquery-3.2.1.min.js?3"></script>'.
	'<script src="js/autosize.js?3"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/global/global'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/global/global'.MIN.'.js?'.SCRIPT.'"></script>'.

	'<script src="js_cache/app0.js?'.SCRIPT.'"></script>'.

(CODE ?
	'<link rel="stylesheet" type="text/css" href="css/jquery-ui'.MIN.'.css?3" />'.
	'<script src="js/jquery-ui.min.js?3"></script>'.

	'<script src="js/jquery.mjs.nestedSortable'.MIN.'.js?1"></script>'.

	'<script src="js/lodash.min.js"></script>'.
	'<link rel="stylesheet" href="css/gridstack'.MIN.'.css" />'.
	'<script src="js/gridstack'.MIN.'.js?"></script>'.
	'<script src="js/gridstack.jQueryUI'.MIN.'.js"></script>'
: '').

	'<script src="modul/page/page'.MIN.'.js?'.SCRIPT.'"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/element/element'.MIN.'.css?'.SCRIPT.'" />'.
	'<script src="modul/element/element'.MIN.'.js?'.SCRIPT.'"></script>'.

(CODE ?
	'<script src="modul/block/block'.MIN.'.js?'.SCRIPT.'"></script>'.

	'<script src="modul/spisok/spisok'.MIN.'.js?'.SCRIPT.'"></script>'
: '').

	_debug('style');
}
function _html_hat() {//верхняя строка приложения для сайта
	if(IFRAME_AUTH_ERROR)
		return '';
	if(!CODE)
		return '';
	if(!SITE)
		return '';

	return
	'<div id="hat">'.
		'<div class="w1000 mara pt3">'.
			'<div class="dib mt5 fs22">'._html_title().'</div>'.

			'<a href="'.URL.'&logout" class="fr white mt10">'.
				'<span class="dib mr20 pale">'.USER_NAME.'</span>'.
				'Выход'.
			'</a>'.

			'<div class="fr w300 mt8 r mr20">'.
				_hat_but_sa().
				_hat_but_page().
				_hat_but_pas().
			'</div>'.
		'</div>'.
	'</div>';
}
function _hat_but_sa() {//отображение кнопки списка страниц
	if(!SA)
		return '';
	if(!APP_ID)
		return '';

	if(_page('cur') == 1)
		return '';

	return '<button class="vk small red" onclick="location.href=\''.URL.'&p=1\'">SA</button>';
}
function _hat_but_page() {//отображение кнопки списка страниц
	if(!APP_ID)
		return '';
	if(!USER_CREATOR)
		return '';
	if(_page('cur') == 12)
		return '';

	return '<button class="vk small ml10" onclick="location.href=\''.URL.'&p=12\'">Cтраницы</button>';
}
function _hat_but_pas() {//отображение кнопки настройки страницы
	if(!APP_ID)
		return '';
	if(!USER_CREATOR)
		return '';
	if(!$page_id = _page('cur'))
		return '';
	if(!$page = _page($page_id))
		return '';
	if($page['sa'] && !SA)
		return '';
	if(!$page['app_id'] && !SA)
		return '';

	return '<button id="page_setup" class="vk small fr ml10 '.(PAS ? 'orange' : 'grey').'">Page setup</button>';
}

function _app_create($dialog, $app_id) {//привязка пользователя к приложению после его создания
	if($dialog['id'] != 100)
		return;
	if(!$app_id)//ID созданного приложения в таблице _app
		return;

	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=1011
			  AND `connect_1`=".USER_ID;
	if(query_value($sql))
		return;

	$sql = "INSERT INTO `_spisok` (
				`app_id`,
				`dialog_id`,
				`connect_1`,
				`num_1`
			) VALUES (
				".$app_id.",
				1011,
				".USER_ID.",
				1
			)";
	query($sql);

	$sql = "UPDATE `_user_auth`
			SET `app_id`=".$app_id."
			WHERE `code`='".CODE."'";
	query($sql);

	_cache_clear( 'AUTH', 1);
	_cache_clear( 'page');
	_cache_clear( 'user'.USER_ID);

	_auth();
}
function _app_list() {//список приложений, которые доступны пользователю
	if(!USER_ID)
		return '';
	if(APP_ID)
		return 'Здесь будет размещён список приложений.';

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `connect_1`=".USER_ID."
			  AND `dialog_id`=1011
			ORDER BY `dtime_add`";
	if(!$spisok = query_arr($sql))
		return
			'<div class="center pad30 color-555 fs15">'.
				'Доступных приложений нет.'.
				'<br>'.
				'<br>'.
				'<button class="vk green dialog-open" val="dialog_id:100">Создать приложение</div>'.
			'</div>';

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="pad10 bg-gr2 mb10 over2 curP" onclick="_appEnter('.$r['app_id'].')">'.
		  (SA ? '<span class="grey">'.$r['app_id'].'</span> ' : '').
				_app($r['app_id'], 'name').
				'<div class="fr grey">'.FullData($r['dtime_add']).'</div>'.
			'</div>';
	}

	return $send;
}
function _app_content() {//центральное содержание
	if(IFRAME_AUTH_ERROR)
		return '';
	if(!CODE)
		return '';
	if(!USER_ID)
		return '';

	return
	'<div id="_content" class="block-content-page '.SITE.'">'.
		_pageShow(_page('cur')).
	'</div>';
}

function _contentMsg($msg='') {
	if(!$msg) {
		$_GET['p'] = 0;
		$msg = 'Несуществующая страница<br><br><a href="'.URL.'&p='._page('cur').'">Перейти на страницу по умолчанию</a>';
	}
	return '<div class="_empty mar20">'.$msg.'</div>';
}






