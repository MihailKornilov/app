<?php
define('TIME', microtime(true));
define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));
define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));

define('VERSION', 0);
define('DEBUG', true);
define('MIN', DEBUG ? '' : '.min');

define('SA', true);
if(SA) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
}

setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/modul/global/mysql.php';
_dbConnect('GLOBAL_');

require_once GLOBAL_DIR.'/modul/global/regexp.php';
require_once GLOBAL_DIR.'/modul/global/date.php';
require_once GLOBAL_DIR.'/modul/global/vkuser.php';

define('FACE', _face());
define('SITE', FACE == 'site');
define('IFRAME', FACE == 'iframe');
require_once GLOBAL_DIR.'/modul/'.FACE.'/'.FACE.'.php';
require_once GLOBAL_DIR.'/modul/global/func_require.php';

require_once GLOBAL_DIR.'/modul/debug/debug.php';

define('URL', APP_HTML.'/index.php?'.TIME);
define('URL_AJAX', APP_HTML.'/ajax.php?'.TIME);

define('CODE', _txt(@$_COOKIE['code']));
define('CACHE_PREFIX', md5(CODE));




function _face() {//�����������, ��� ��������� ��������: iframe ��� ����
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
function _global_script() {//������� � �����
	return
	//������������ ������ � ��������
	(SA ? '<script src="js/errors.js"></script>' : '').

	'<script>'.
		'var URL="'.URL.'",'.
			'AJAX="'.URL_AJAX.'";'.
	'</script>'.

	'<script src="js/jquery-3.2.1.min.js?1"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/global/global.css?'.TIME.'" />'.
	'<script src="modul/global/global.js?'.TIME.'"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/element/element.css?'.TIME.'" />'.
	'<script src="modul/element/element.js?'.TIME.'"></script>'.
	
	_debug('style');
}

function _authSuccess($code, $viewer_id, $app_id) {//�������� ������ �� �������� �����������
	_authLogout($code, $viewer_id);//��������������� �������� ������ �����������

	$ip = $_SERVER['REMOTE_ADDR'];
	$browser = _txt($_SERVER['HTTP_USER_AGENT']);
	$browser_md5 = md5($browser);
	$sql = "INSERT INTO `_vkuser_auth` (
				`viewer_id`,
				`app_id`,
				`code`,
				`ip`,
				`browser`,
				`browser_md5`
			) VALUES (
				".$viewer_id.",
				".$app_id.",
				'".$code."',
				'".$ip."',
				'".addslashes($browser)."',
				'".$browser_md5."'
			)";
	query($sql);

	//������� ���� ���������� ��������� ������������
	$sql = "UPDATE `_vkuser`
			SET `last_seen`=CURRENT_TIMESTAMP
			WHERE `id`=".$viewer_id;
	query($sql);

	//������� ���� ���������� ��������� ����������. ���� ������������ ������� ������ � ����������, �� �������� ���������� ��� ����
	if($app_id) {
		$sql = "SELECT `id`
		        FROM `_vkuser_app`
				WHERE `app_id`=".$app_id."
				  AND `viewer_id`=".$viewer_id;
		$id = _num(query_value($sql));

		$sql = "INSERT INTO `_vkuser_app` (
					`id`,
					`viewer_id`,
					`app_id`,
					`last_seen`
				) VALUES (
					".$id.",
					".$viewer_id.",
					".$app_id.",
					CURRENT_TIMESTAMP
				) ON DUPLICATE KEY UPDATE
					`last_seen`=CURRENT_TIMESTAMP";
		query($sql);
	}

	setcookie('code', $code, time() + 2592000, '/');

	if(LOCAL)
		setcookie('local', 1, time() + 2592000, '/');
}
function _authLogoutApp() {//����� �� ���������� � ��������� � ������ ����������
	$sql = "UPDATE `_vkuser_auth`
			SET `app_id`=0
			WHERE `code`='".CODE."'";
	query($sql);

	_cache(CODE, 'clear');
	_cache('viewer_'.VIEWER_ID, 'clear');
}
function _authLogout($code, $viewer_id) {
	$sql = "DELETE FROM `_vkuser_auth` WHERE `code`='".addslashes($code)."'";
	query($sql);
	_cache($code, 'clear');
	_cache('viewer_'.$viewer_id, 'clear');
}
function _authCache() {//��������� ������ ����������� �� ���� � ��������� �������� id ������������ � ����������
	if(!CODE)
		return false;

	if(!$r = _cache(CODE)) {
		$sql = "SELECT *
				FROM `_vkuser_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if(!$r = query_assoc($sql))
			return false;

		_cache(CODE, array(
			'viewer_id' => $r['viewer_id'],
			'app_id' => $r['app_id']
		));
	}

	define('VIEWER_ID', _num($r['viewer_id']));
	define('APP_ID', _num($r['app_id']));

	_viewer();

	return true;
}

function _app($i='all') {//��������� ������ � ����������
	if(!$arr = _cache('app'.APP_ID)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".APP_ID;
		if(!$arr = query_assoc($sql))
			_appError('���������� ��������� ������ ���������� ��� ����.');

		_cache('app'.APP_ID, $arr);
	}

	if($i == 'all') {
		_debugLoad('�������� ������ ����������');
		return $arr;
	}

	if(!isset($arr[$i]))
		return _cacheErr('_app: ����������� ����', $i);

	return $arr[$i];
}


function _content() {//����������� ����������
	return
	'<div id="_content">'.
		FACE.
		'<br />'.
		'<span class="grey">code:</span> '.CODE.
		'<br />'.
		'<span class="grey">viewer_id:</span> '.VIEWER_ID.
		'<br />'.
		'<span class="grey">app_id:</span> '.APP_ID.
		'<br />'.
		'<a href="'.URL.'">link</a>'.
		'<br />'.
		'VIEWER_WORKER='.VIEWER_WORKER.
		'<br />'.
		_appSpisok().
	'</div>';
}
function _footer() {
	return '</body></html>';
}



function _num($v) {
	if(empty($v) || is_array($v) || !preg_match(REGEXP_NUMERIC, $v))
		return 0;

	return intval($v);
}
function _bool($v) {//�������� �� ������ �����
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return intval($v);
}
function _cena($v, $minus=0, $kop=0, $del='.') {//�������� �� ����.
	/*
		$minus - ����� �� ���� ���� ���������.
		$kop - ���������� � ���������, ���� ���� 00
		$del - ���� ����� �������
	*/
	if(empty($v) || is_array($v) || !preg_match($minus ? REGEXP_CENA_MINUS : REGEXP_CENA, $v))
		return 0;

	$v = str_replace(',', '.', $v);
	$v = round($v, 2);

	if(!$kop)
		return $v;

	if(!$ost = round($v - floor($v), 2))
		$v .= '.00';
	else
		if(!(($ost * 100) % 10))
			$v .= 0;

	if($del == ',')
		$v = str_replace('.', ',', $v);

	return $v;
}
function _ms($v, $del='.') {//�������� �� ������� ��������� � ������� 0.000
	/*
		$del - ���� ����� �������
	*/
	if(empty($v) || is_array($v) || !preg_match(REGEXP_MS, $v))
		return 0;

	$v = str_replace(',', '.', $v);
	$v = round($v, 3);

	$v = str_replace(',', $del, $v);
	$v = str_replace('.', $del, $v);

	return $v;
}
function _txt($v, $utf8=0) {
	$v = htmlspecialchars(trim($v));
	return $utf8 ? $v : win1251($v);
}
function _br($v) {//������� br � ����� ��� ���������� enter
	return str_replace("\n", '<br />', $v);
}
function _daNet($v) {//$v: 1 -> ��, 0 -> ���
	return $v ? '��' : '���';
}



function win1251($txt) { return iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $txt); }
function utf8($txt) { return iconv('WINDOWS-1251', 'UTF-8', $txt); }
function mb_ucfirst($txt) {//������� ��������� ������ ����� ������
	mb_internal_encoding('UTF-8');
	$txt = utf8($txt);
	$txt = mb_strtoupper(mb_substr($txt, 0, 1)).mb_substr($txt, 1);
	return win1251($txt);
}
function unescape($str){
	$escape_chars = '0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 '.
		'0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D '.
		'041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 '.
		'0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F';
	$russian_chars = '� � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � �';
	$e = explode(' ', $escape_chars);
	$r = explode(' ', $russian_chars);
	$rus_array = explode('%u', $str);
	$new_word = str_replace($e, $r, $rus_array);
	$new_word = str_replace('%20', ' ', $new_word);
	return implode($new_word);
}
function translit($str) {
	$list = array(
		'�' => 'A',
		'�' => 'B',
		'�' => 'V',
		'�' => 'G',
		'�' => 'D',
		'�' => 'E',
		'�' => 'E',
		'�' => 'J',
		'�' => 'Z',
		'�' => 'I',
		'�' => 'Y',
		'�' => 'K',
		'�' => 'L',
		'�' => 'M',
		'�' => 'N',
		'�' => 'O',
		'�' => 'P',
		'�' => 'R',
		'�' => 'S',
		'�' => 'T',
		'�' => 'U',
		'�' => 'F',
		'�' => 'H',
		'�' => 'TS',
		'�' => 'CH',
		'�' => 'SH',
		'�' => 'SCH',
		'�' => '',
		'�' => 'YI',
		'�' => '',
		'�' => 'E',
		'�' => 'YU',
		'�' => 'YA',
		'�' => 'a',
		'�' => 'b',
		'�' => 'v',
		'�' => 'g',
		'�' => 'd',
		'�' => 'e',
		'�' => 'e',
		'�' => 'j',
		'�' => 'z',
		'�' => 'i',
		'�' => 'y',
		'�' => 'k',
		'�' => 'l',
		'�' => 'm',
		'�' => 'n',
		'�' => 'o',
		'�' => 'p',
		'�' => 'r',
		'�' => 's',
		'�' => 't',
		'�' => 'u',
		'�' => 'f',
		'�' => 'h',
		'�' => 'ts',
		'�' => 'ch',
		'�' => 'sh',
		'�' => 'sch',
		'�' => 'y',
		'�' => 'yi',
		'�' => '',
		'�' => 'e',
		'�' => 'yu',
		'�' => 'ya',
		' ' => '_',
		'�' => 'N',
		'�' => ''
	);
	return strtr($str, $list);
}





function _vkapi($method, $param=array()) {//��������� ������ �� api ���������
	$param += array(
		'v' => 5.64,
		'lang' => 'ru'
	);

	$url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($param);
	$res = file_get_contents($url);
	$res = json_decode($res, true);
//	if(DEBUG)
//		$res['url'] = $url;
	return $res;
}

function _cache($key, $v='') {//����������� ������
	if(empty($key))
		die('����������� ���� ��� �����������.');

	/*
		code - ������������ ���
		viewer_ + id
		app
	*/

	$key = CACHE_PREFIX.$key;

	if($v == 'clear') {
		xcache_unset($key);
		return true;
	}

	//��������� ������ � ���
	if($v) {
		xcache_set($key, $v, 86400);
		return true;
	}

	if(!xcache_isset($key))
		return false;

	return xcache_get($key);
}
function _cacheErr($txt='����������� ��������', $i='') {//
	if($i != '')
		$i = ': <b>'.$i.'</b>';
	return '<span class="red">'.$txt.$i.'.</span>';
}





