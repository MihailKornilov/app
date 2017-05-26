<?php
function _auth() {//авторизация через iframe
	if($auth_key = @$_GET['auth_key']) {
		$viewer_id = _num(@$_GET['viewer_id']);
		$app_id = _num(@$_GET['api_id']);
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".$app_id."
				  AND `viewer_id`=".$viewer_id;
		if(!$r = query_assoc($sql))
			_appError();

	}
		
}
function _appError($msg='Приложение не было загружено.') {//вывод сообщения об ошибке приложения и выход
	if(!defined('VERSION')) {
		define('VERSION', 141);
		define('MIN', defined('DEBUG') ? '' : '.min');
	}
	$html =
		'<!DOCTYPE html>'.
		'<html lang="ru">'.
			'<head>'.
				'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
				'<title>Error</title>'.

				'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
				'<script>VK.init(function() {},function() {},"5.60");</script>'.

				_global_script().

			'</head>'.
			'<body>'.
				'<div id="frameBody">'.
					'<iframe id="frameHidden" name="frameHidden"></iframe>'.
					_noauth($msg).
				'</div>'.
			'</body>'.
		'</html>';
	die($html);
}
function _noauth($msg='Не удалось выполнить вход в приложение.') {
	return
	'<div class="noauth pad30 bg-gr1">'.
		'<div class="center grey bg-fff">'.$msg.'</div>'.
	'</div>';
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

		'<body>';
}
function _footer() {
	return
	'</body></html>';
}
