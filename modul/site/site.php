<?php
function _auth() {//авторизация через сайт
	if($code = @$_GET['code'])
		_authLogin($code);

	if(!$sid = _txt(@$_COOKIE['viewer_sid']))
		_authLogin();

	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `viewer_sid`='".addslashes($sid)."'
			LIMIT 1";
	if(!$r = query_assoc($sql))
		_authLogin();

	
}
function _authLogin($code='') {//отображение ссылки для входа через ВКонтакте
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


