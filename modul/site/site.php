<?php
/*
	Процесс авторизации:
		1. Если есть $_GET['code'] -> переход на страницу авторизации
		2. Если нет code в Cookie -> переход на страницу авторизации
*/

function _auth() {//авторизация через сайт
	if($code = @$_GET['code'])
		_authLogin($code);

	if(!CODE)
		_authLogin();

	if(!_authCache())
		_authLogin();

	if(isset($_GET['logout'])) {
		if(isset($_GET['app']))
			_authLogoutApp();
		else
			_authLogout(CODE, VIEWER_ID);
		header('Location:'.URL);
		exit;
	}

	//SA: вход от имени другого пользователя
	if(SA && $viewer_id = _num(@$_GET['viewer_id'])) {
		$sql = "SELECT COUNT(*)
				FROM `_vkuser`
				WHERE `viewer_id`=".$viewer_id;
		if(query_value($sql)) {
			$sql = "UPDATE `_vkuser_auth`
					SET `viewer_id_show`=".$viewer_id.",
						`app_id`=0
					WHERE `code`='".CODE."'";
			query($sql);

			_cache('clear', '_viewerCache'.VIEWER_ID);
			_cache('clear', '_viewer'.$viewer_id);
			_cache('clear', '_authCache');

			header('Location:'.URL);
			exit;
		}
	}

	_viewer();
}
function _authLogin($code='') {//отображение ссылки для входа через ВКонтакте
	setcookie('code', '', time() - 1, '/');//сброс авторизации

	$href = LOCAL ? URL.'&code='.md5(TIME)
			:
			'https://oauth.vk.com/authorize?'.
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
			'<title>'.(APP_ID ? _app(APP_ID, 'app_name') : 'Мои приложения').'</title>'.
			_global_script().
//			_api_scripts().
		'</head>'.

		'<body class="site">'.
			_header_hat();
}
function _header_hat() {//верхняя строка приложения-сайта
	return
	'<div id="hat"'.(VIEWER_ID_SHOWER ? ' class="show"' : '').'>'.
		'<div class="w1000 mara pt3">'.
			'<div class="dib mt5 fs22">'.(APP_ID ? _app(APP_ID, 'app_name') : 'Мои приложения').'</div>'.

			'<a href="'.URL.'&logout'.(APP_ID && !VIEWER_APP_ONE ? '&app' : '').'" class="fr white mt10">'.
				'<span class="dib mr20 pale">'.VIEWER_NAME.'</span>'.
				'Выход'.
			'</a>'.

			'<div class="fr w200 mt8">'.
				_header_but_page().
				_header_but_pas().
			'</div>'.
		'</div>'.
	'</div>';
}
function _header_but_page() {//отображение кнопки списка страниц
	if(!APP_ID)
		return '';

	if(_page('cur') == 12)
		return '';

	return '<button class="vk small" onclick="location.href=\''.URL.'&p=12\'">Cтраницы</button>';
}
function _header_but_pas() {//отображение кнопки настройки страницы
	if(!APP_ID)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	return '<button id="page_setup" class="vk small fr mr30 '.(PAS ? 'orange' : 'grey').'">Page setup</button>';
}


function _appSpisok() {//список приложений, которые доступны пользователю
	if(APP_ID)
		return '';

	$sql = "SELECT `app`.*
			FROM
				`_app` `app`,
				`_vkuser_app` `va`
			WHERE `app`.`id`=`va`.`app_id`
			  AND `viewer_id`=".VIEWER_ID."
			  AND `worker`
			ORDER BY `va`.`dtime_add`";
	if(!$spisok = query_arr($sql))
		return _appCreate();

	$send = '<div class="">';
	foreach($spisok as $r) {
		$send .=
			'<div class="pad10 bg-gr2 mar10 over2 curP" onclick="_appEnter('.$r['id'].')">'.
				'<span class="grey">'.$r['id'].'</span> '.
				$r['app_name'].
				'<div class="fr grey">'.FullData($r['dtime_add']).'</div>'.
			'</div>';
	}
	$send .= '</div>';

	return $send;
}
function _appCreate() {//автоматическое создание приложения, если пользователь впервые зашёл на сайт
	/*
		Приложение создаётся только с сайта.
		id начинается от 1001 и не может быть больше 2.000.000, так как реальные приложения VK идут от 2млн
	*/

	$sql = "SELECT MAX(`id`)+1
			FROM `_app`
			WHERE `id`<2000000";
	if(!$app_id = query_value($sql))
		$app_id = 1001;

	$sql = "INSERT INTO `_app` (
				`id`,
				`app_name`,
				`viewer_id_add`
			) VALUES (
				".$app_id.",
				'Приложение ".$app_id."',
				".VIEWER_ID."
			)";
	query($sql);

	$sql = "INSERT INTO `_vkuser_app` (
				`viewer_id`,
				`app_id`,
				`last_seen`,
				`admin`,
				`worker`
			) VALUES (
				".VIEWER_ID.",
				".$app_id.",
				CURRENT_TIMESTAMP,
				1,
				1
			)";
	query($sql);

	$sql = "UPDATE `_vkuser_auth`
			SET `app_id`=".$app_id."
			WHERE `code`='".CODE."'
			  AND `viewer_id`=".VIEWER_ID;
	query($sql);

	_cache('clear', '_authCache');
	header('Location:'.URL);

	return '<div class="_empty mt20">Приложений нет.</div>';
}








