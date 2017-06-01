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
		_authLogout($code, VIEWER_ID);
		header('Location:'.URL);
		exit;
	}

	_viewer();
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


