<?php
define('TIME', microtime(true));

setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));
define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/modul/global/regexp.php';
require_once GLOBAL_DIR.'/modul/global/mysql.php';
require_once GLOBAL_DIR.'/modul/global/date.php';
require_once GLOBAL_DIR.'/modul/debug/debug.php';
require_once GLOBAL_DIR.'/modul/db/db.php';
require_once GLOBAL_DIR.'/modul/global/vkuser.php';
require_once GLOBAL_DIR.'/modul/page/page.php';
require_once GLOBAL_DIR.'/modul/block/block.php';
require_once GLOBAL_DIR.'/modul/element/element.php';
require_once GLOBAL_DIR.'/modul/spisok/spisok.php';

define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));
define('YEAR_CUR', strftime('%Y'));



define('DEBUG', @$_COOKIE['debug']);
define('MIN', DEBUG ? '' : '.min');

define('CODE', _txt(@$_COOKIE['code']));

define('VERSION', _num(@$_COOKIE['version']));

define('URL', APP_HTML.'/index.php?'.TIME);


//���������� ����������
$CACHE_ARR = array();




function _sa($viewer_id) {//�������� ������������ �� ������ SA
	//������ ������������� - SA
	$SA[982006] = true;//������ ��������

	return isset($SA[_num($viewer_id)]) ? 1 : 0;
}
function _saSet() {//��������� ����� �������������������
	if(!_authCache()) {
		define('SA', 0);
		return;
	}

//	define('SA', _sa(VIEWER_ID_SHOWER ? VIEWER_ID_SHOWER : VIEWER_ID));
	define('SA', _sa(VIEWER_ID));

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	}
}

function _face() {//�����������, ��� ��������� ��������: iframe ��� ����
	$face = 'site';
	switch(@$_COOKIE['face']) {
		case 'site': $face = 'site'; break;
		case 'iframe': $face = 'iframe'; break;
	}

	if(!empty($_GET['referrer']))
		$face = 'iframe';

	setcookie('face', $face, time() + 2592000, '/');

	define('FACE', $face);
	define('SITE', FACE == 'site');
	define('IFRAME', FACE == 'iframe');

	require_once GLOBAL_DIR.'/modul/'.FACE.'/'.FACE.'.php';
	require_once GLOBAL_DIR.'/modul/global/func_require.php';
}
function _ajax_url() {//���������� ������ ��� �������� �������� ajax
		$get = '';
		foreach($_GET as $i => $v) {
			if(!$v)
				continue;
			$get .= '&'.$i.'='.$v;
		}
	return APP_HTML.'/ajax.php?'.TIME.$get;
}
function _global_script() {//������� � �����
	return
	//������������ ������ � ��������
	(SA ? '<script src="js/errors.js"></script>' : '').

	'<script>'.
		'var URL="'.URL.'",'.
			'AJAX="'._ajax_url().'",'.
			'SA='.SA.','.
			'PAGE_ID='._page('cur').';'.
	'</script>'.

	'<script src="js/jquery-3.2.1.min.js?3"></script>'.
	'<link rel="stylesheet" type="text/css" href="css/jquery-ui'.MIN.'.css?3" />'.
	'<script src="js/jquery-ui.min.js?3"></script>'.
	'<script src="js/autosize.js?3"></script>'.
	'<script src="js/jquery.mjs.nestedSortable'.MIN.'.js?1"></script>'.

	'<script src="js/lodash.min.js"></script>'.
	'<link rel="stylesheet" href="css/gridstack'.MIN.'.css" />'.
	'<script src="js/gridstack'.MIN.'.js?"></script>'.
	'<script src="js/gridstack.jQueryUI'.MIN.'.js"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/global/global'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/global/global'.MIN.'.js?'.VERSION.'"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/element/element'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/element/element'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/page/page'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/block/block'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/spisok/spisok'.MIN.'.js?'.VERSION.'"></script>'.

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

	_cache('clear', '_authCache');
	_cache('clear', '_pageCache');
	_cache('clear', '_viewerCache'.VIEWER_ID);
}
function _authLogout($code, $viewer_id) {//����� �� ������ ����������
	$sql = "DELETE FROM `_vkuser_auth` WHERE `code`='".addslashes($code)."'";
	query($sql);

	_cache('clear', '_authCache');
	_cache('clear', '_pageCache');
	_cache('clear', '_viewer'.$viewer_id);

	setcookie('code', '', time() - 1, '/');
}
function _authCache() {//��������� ������ ����������� �� ���� � ��������� �������� id ������������ � ����������
	if(!CODE)
		return false;
	if(defined('VIEWER_ID'))
		return true;

	if(!$r = _cache()) {
		$sql = "SELECT *
				FROM `_vkuser_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if(!$r = query_assoc($sql))
			return false;

		_cache(array(
			'viewer_id' => $r['viewer_id'],
			'app_id' => $r['app_id'],
			'viewer_id_show' => $r['viewer_id_show']
		));
	}

	//���� ���������������, ���� SA ������������� �� ����� ������� ������������
	define('VIEWER_ID_SHOWER', $r['viewer_id_show'] && _sa($r['viewer_id']) ? _num($r['viewer_id']) : 0);//id ������������, ������� �������
	define('VIEWER_ID', _num($r['viewer_id'.(VIEWER_ID_SHOWER ? '_show' : '')]));
	define('APP_ID', _num($r['app_id']));

	_viewer();

	return true;
}

function _app($app_id=APP_ID, $i='all') {//��������� ������ � ����������
	if(!$arr = _cache()) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".$app_id;
		if(!$arr = query_assoc($sql))
			_appError('���������� ��������� ������ ���������� ��� ����.');

		_cache($arr);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return _cacheErr('_app: ����������� ����', $i);

	return $arr[$i];
}


function _content() {//����������� ����������
	return
	'<div id="_content" class="block-content-page'.(SITE ? ' site' : '').'">'.
		(APP_ID ? _pageShow(_page('cur')) : _appSpisok()).
	'</div>';
}
function _contentMsg($msg='') {
	if(!$msg) {
		$_GET['p'] = 0;
		$msg = '�������������� ��������<br><br><a href="'.URL.'&p='._page('cur').'">������� �� �������� �� ���������</a>';
	}
	return '<div class="_empty mar20">'.$msg.'</div>';
}
function _footer() {
	return '</body></html>';
}





function _regFilter($v) {//�������� ����������� ��������� �� ������������ �������
	$reg = '/(\[)/'; // ������ [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}


function _end($count, $o1, $o2, $o5=false) {
	if($o5 === false) $o5 = $o2;
	if($count / 10 % 10 == 1)
		return $o5;
	else
		switch($count % 10) {
			case 1: return $o1;
			case 2: return $o2;
			case 3: return $o2;
			case 4: return $o2;
		}
	return $o5;
}
function _dn($v, $cls='dn') {//�����/������� ����� �� ��������� �������
	if(empty($v))
		return ' '.$cls;
	return '';
}

function _num($v, $minus=0) {
	if(empty($v) || is_array($v))
		return 0;

	if($minus && !preg_match(REGEXP_INTEGER, $v))
		return 0;

	if(!$minus && !preg_match(REGEXP_NUMERIC, $v))
		return 0;

	return intval($v);
}
function _bool($v) {//�������� �� ������ �����
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return 1;
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
function _br($v, $replace='<br />') {//������� br � ����� ��� ���������� enter
	return str_replace("\n", $replace, $v);
}
function _daNet($v) {//$v: 1 -> ��, 0 -> ���
	return $v ? '��' : '���';
}

function _ids($ids, $return_arr=0) {//�������� ������������ ������ id, ������������ ����� �������
	$arr = array();
	foreach(explode(',', $ids) as $i => $id) {
		if(!preg_match(REGEXP_NUMERIC, $id))
			return 0;
		$arr[$i] = _num($id);
	}
	return $return_arr ? $arr : implode(',', $arr);
}
function _idsGet($arr, $i='id') {//����������� �� ������� ������ id ����� �������
/*
	key: ������ id �� �����
*/
	$ids = array();
	foreach($arr as $id => $r) {
		if($i == 'key') {
			$ids[] = $id;
			continue;
		}
		if(!empty($r[$i]))
			$ids[] = $r[$i];
	}
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _idsAss($v) {//��������� ������ id ����: $v[25] = 1; - ��������� ������
	$send = array();

	if(empty($v))
		return $send;

	$arr = is_array($v) ? $v : _ids($v, 1);

	foreach($arr as $id)
		$send[$id] = 1;

	return $send;
}


function win1251($txt) { return iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $txt); }
function utf8($val) {
	if(is_array($val)) {
		foreach($val as $k => $v)
			$val[$k] = preg_match(REGEXP_INTEGER, $v) ? _num($v, 1) : utf8($v);
		return $val;
	}
	return iconv('WINDOWS-1251', 'UTF-8', $val);
}
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



function _pr($arr) {//������ ������� print_r
	if(empty($arr))
		return _prMsg('������ ����');

	if(!is_array($arr))
		return _prMsg('�� �������� ��������');

	return
	'<div class="dib pad5 bor-e8">'.
		_prFor($arr).
	'</div>';
}
function _prMsg($msg) {
	return '<div class="dib grey i pad5 bor-e8">'.$msg.'</div>';
}
function _prFor($arr, $sub=0) {//������� �������
	$send = '';
	foreach($arr as $id => $r) {
		$send .=
			'<div class="'.($sub ? 'ml20' : '').(is_array($r) ? '' : ' mtm2').'">'.
				'<span class="'.($sub ? 'fs11 color-acc' : 'fs12 black').(is_array($r) ? ' b u curP' : '').'"'.(is_array($r) ? ' onclick="$(this).next().slideToggle(300)"' : '').'>'.
					$id.':'.
				'</span> '.
				'<span class="grey fs11">'.
					(is_array($r) ? _prFor($r, 1) : $r).
				'</span>'.
			'</div>';
	}
	return $send;
}


function _arr($arr, $i=false) {//���������������� ������
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? _cena($v) : utf8(htmlspecialchars_decode($v));
	}
	return $send;
}
function _sel($arr) {
	$send = array();
	foreach($arr as $uid => $title) {
		$send[] = array(
			'uid' => $uid,
			'title' => utf8(trim($title))
		);
	}
	return $send;
}
function _selJson($arr) {
	$send = array();
	foreach($arr as $uid => $title) {
		$content = '';
		if(is_array($title)) {
			$r = $title;
			$title = $r['title'];
			$content = isset($r['content']) ? $r['content'] : '';
		}
		$send[] = '{'.
			'uid:'.$uid.','.
			'title:"'.addslashes($title).'"'.
			($content ? ',content:"'.addslashes($content).'"' : '').
		'}';
	}
	return '['.implode(',',$send).']';
}
function _selArray($arr) {//������ ��� _select ��� �������� ����� ajax
	$send = array();
	foreach($arr as $uid => $title) {
		$send[] = array(
			'uid' => _num($uid),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($title))))
		);
	}
	return $send;
}
function _assJson($arr) {//������������� ������
	$send = array();
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_NUMERIC, $v) ? $v : '"'.$v.'"');
	return '{'.implode(',', $send).'}';
}
function _arrJson($arr, $i=false) {//���������������� ������
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? $v : '"'.addslashes(htmlspecialchars_decode($v)).'"';
	}
	return '['.implode(',', $send).']';
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

function _cache($data='', $key='') {//����������� ������
	/*
		$data - ������, ����������� � ���. ���� �������� ������, �� ������� �������� ������
		$key  - ������������� ���������� �� ������� �������� ������� debug_backtrace
				��� ����������� ����� + �������� ������� ��������� (���� ����)
	*/
	global $CACHE_ARR;

	
	if(!$key) {
		$DBT = debug_backtrace(0);
		$DBT = $DBT[1];
		$ARG = empty($DBT['args'][0]) ? '' : $DBT['args'][0];
		$key = $DBT['function'].$ARG;
	}

	$cKey = md5(CODE).$key;
	if($data == 'clear') {
		xcache_unset($cKey);
		$CACHE_ARR[] = array(
			'act' => 'clear',
			'key' => $key,
			'dbt' => debug_backtrace(0)
		);
		return true;
	}

	//��������� ������ � ���
	if($data) {
		xcache_set($cKey, $data, 86400);
		$CACHE_ARR[] = array(
			'act' => 'set',
			'key' => $key,
			'dbt' => debug_backtrace(0)
		);
		return $data;
	}

	if(!xcache_isset($cKey)) {
		$CACHE_ARR[] = array(
			'act' => 'empty',
			'key' => $key,
			'dbt' => debug_backtrace(0)
		);
		return false;
	}

	$CACHE_ARR[] = array(
		'act' => 'get',
		'key' => $key,
		'dbt' => debug_backtrace(0)
	);
	return xcache_get($cKey);
}
function _cacheErr($txt='����������� ��������', $i='') {//
	if($i != '')
		$i = ': <b>'.$i.'</b>';
	return '<span class="red">'.$txt.$i.'.</span>';
}





