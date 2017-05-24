<?php
define('TIME', microtime(true));
define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));

setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/modul/global/mysql.php';
_dbConnect('GLOBAL_');

require_once GLOBAL_DIR.'/modul/global/regexp.php';

define('FACE', _face());
require_once GLOBAL_DIR.'/modul/'.FACE.'/'.FACE.'.php';
define('SITE', FACE == 'site');
define('IFRAME', FACE == 'iframe');




function _face() {//определение, как загружена страница: iframe или сайт
	switch(@$_COOKIE['face']) {
		case 'site': return 'site';
		case 'iframe': return 'iframe';
	}

	if(!empty($_GET['referrer'])) {
		setcookie('face', 'iframe', time() + 2592000, '/');
		return 'iframe';
	}

	setcookie('face', 'site', time() + 2592000, '/');
	return 'site';
}
function _global_script() {//скрипты и стили
	return
		//стили Global
//		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/global/global'.MIN.'.css?'.VERSION.'" />'.
//		'<script src="'.API_HTML.'/modul/global/global'.MIN.'.js?'.VERSION.'"></script>';
	'<script src="js/jquery-3.2.1.slim.min.js"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/global/global.css" />'.
	'<script src="modul/global/global.js?1006"></script>';
}

function _content() {//центральное содержание
	$sql = "SELECT COUNT(*) FROM `_vkuser`";

	return
	'<div id="_content">'.
		FACE.
		'<br />'.
		'количество пользователей: '.query_value($sql).
		'<br />'.
		'<a href="https://nyandoma.ru/app" target="blank">nyandoma.ru/app</a>'.
		'<br />'.
		'<a href="https://vk.com/app4872135" target="blank">vk.com/app4872135</a>'.
	'</div>';
}


