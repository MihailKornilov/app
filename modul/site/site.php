<?php
/*
	1. Если есть GET code -> переход на страницу авторизации
	2. Если нет code в Cookie -> переход на страницу авторизации
	3.
*/

function _auth() {//авторизация через сайт
	if($code = @$_GET['code'])
		_authLogin($code);
	if(!$code = _txt(@$_COOKIE['code']))
		_authLogin();

	if(!_authCache($code))
		_authLogin();

	if(isset($_GET['logout'])) {
		$sql = "DELETE FROM `_vkuser_auth` WHERE `code`='".addslashes($code)."'";
		query($sql);
		_cache($code, 'clear');
		_cache('viewer_'.VIEWER_ID, 'clear');
		header('Location:'.URL);
		exit;
	}

	_viewer();
}
function _authCache($code) {//получение данных авторизации из кеша и установка констант id пользователя и приложения
	if(!$r = _cache($code)) {
		$sql = "SELECT *
				FROM `_vkuser_auth`
				WHERE `code`='".addslashes($code)."'
				LIMIT 1";
		if(!$r = query_assoc($sql))
			return false;

		_cache($code, array(
			'viewer_id' => $r['viewer_id'],
			'app_id' => $r['app_id']
		));
	}


	define('VIEWER_ID', _num($r['viewer_id']));
	define('APP_ID', _num($r['app_id']));

	return true;
}
function _authLogin($code='') {//отображение ссылки для входа через ВКонтакте
	setcookie('code', '', time() - 1, '/');//сброс авторизации

	$href = 'https://oauth.vk.com/authorize?'.
					 'client_id='.AUTH_APP_ID.
					'&display=page'.
					'&redirect_uri=https://nyandoma.ru/app'.
					'&scope=0'.
					'&response_type=code'.
					'&v=5.64';
	$html =
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>Авторизация</title>'.
		_global_script().
	'</head>'.

	'<body>'.

		'<div class="center mt40">'.
			'<div class="w1000 pad30 dib mt40">'.
				'<button class="vk'.($code ? ' _busy' : '').'"'.($code ? '' : ' onclick="location.href=\''.$href.'\'"').'>Войти через VK</button>'.
			'</div>'.
		'</div>'.

	($code ?
		'<script>_authLogin("'.$code.'")</script>'
	: '').
	
	'</body>'.
	'</html>';

	die($html);
}





function _header() {
	return
		'<!DOCTYPE html>'.
		'<html lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
//			'<title>'.APP_NAME.'</title>'.
			_global_script().
//			_api_scripts().
		'</head>'.

		'<body>'.
			_header_hat();
}
function _header_hat() {//верхняя строка приложения-сайта
	return
	'<div id="hat">'.
		'<p>'.
			'Фабрика мебели'.
			'<a href="'.URL.'&logout" class="fr white mt5">Выход</a>'.
		'</p>'.
	'</div>';
}
function _footer() {
	return
	'</body></html>';
}


