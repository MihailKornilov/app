<?php
function _auth() {//авторизация через сайт
	if($code = @$_GET['code'])
		_authLoginProcess($code);

	if(!$sid = @$_COOKIE['viewer_sid'])
		_authLoginButton();

	$sql = "";
}
function _authLoginButton() {//отображение ссылки для входа через ВКонтакте
	$href = 'https://oauth.vk.com/authorize?'.
					 'client_id=6046182'.
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
				'<button class="vk" onclick="location.href=\''.$href.'\'">Войти через VK</button>'.
			'</div>'.
		'</div>'.

	'</body>'.
	'</html>';

	die($html);
}
function _authLoginProcess($code) {
	$html =
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>Процесс авторизации</title>'.
		_global_script().
	'</head>'.

	'<body>'.

		'<div class="center mt40">'.
			'<div class="w1000 pad30 dib mt40">'.
				'Ожидайте, сейчас Вы будете перенаправлены в приложение...'.
			'</div>'.
		'</div>'.

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
			_header_hat().


		'<br />token='.@$_GET['access_token'];
}
function _header_hat() {//верхняя строка приложения-сайта
	return
	'<div id="hat">'.
		'<p>Фабрика мебели</p>'.
	'</div>';
}
function _footer() {
	return
	'</body></html>';
}


