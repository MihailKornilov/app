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
require_once GLOBAL_DIR.'/modul/global/html.php';
require_once GLOBAL_DIR.'/modul/global/user.php';
require_once GLOBAL_DIR.'/modul/page/page.php';
require_once GLOBAL_DIR.'/modul/block/block.php';
require_once GLOBAL_DIR.'/modul/element/element.php';
require_once GLOBAL_DIR.'/modul/spisok/spisok.php';

define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));
define('YEAR_CUR', strftime('%Y'));

define('CODE', _txt(@$_COOKIE['code']));
define('DEBUG', @$_COOKIE['debug']);
define('MIN', DEBUG ? '' : '.min');
define('URL', APP_HTML.'/index.php?'.TIME);
define('AJAX', APP_HTML.'/ajax.php?'.TIME);

//session_name('apppp');
//session_start();

//����������� ��� xCache
//$_SERVER["PHP_AUTH_USER"] = "admin";
//$_SERVER["PHP_AUTH_PW"] = "6000030";

function _setting() {//��������� ��������-��������
	$key = 'SETTING';
	if(!$arr = _cache_get($key, 1)) {
		$sql = "SELECT `key`,`v`
				FROM `_setting`";
		$arr = query_ass($sql);

		if(empty($arr['SCRIPT'])) {
			$sql = "INSERT INTO `_setting` (
						`key`,
						`v`
					) VALUES (
						'SCRIPT',
						100
					)";
			query($sql);
			$arr['SCRIPT'] = 100;
		}

		_cache_set($key, $arr, 1);
	}

	//������ ��������
	define('SCRIPT', _num($arr['SCRIPT']));
}

function _table($id=false) {//������� � ���� � ���������������� ����������������
	$tab = array(
		 1 => '_app',
		 2 => '_block',
		 3 => '_dialog',
		 4 => '_dialog_group',
		 5 => '_element',
		 6 => '_element_func',
		 7 => '_history',
		 8 => '_image',
		 9 => '_image_server',
		10 => '_page',
		11 => '_spisok',
		12 => '_user',
//		13 => '_user_app',
		14 => '_user_auth',
		15 => '_user_spisok_filter',
		16 => '_note'
	);

	if($id === false)
		return $tab;
	if(!$id = _num($id))
		return '';
	if(empty($tab[$id]))
		return '';

	return $tab[$id];
}
function _tableFrom($dialog) {//����������� ������ ��� ������� �� ��������� ������ �� �������
	$key = 'TABLE_FROM_'.$dialog['id'];

	if(defined($key))
		return constant($key);

	if(!$dialog['table_1'])
		return '';

	$send = "`".$dialog['table_name_1']."` `t1` ";

	if($dialog['table_2'])
		$send .= "INNER JOIN `".$dialog['table_name_2']."` `t2`
				  ON `t1`.`id`=`t2`.`".$dialog['table_2_field']."`";

	define($key, $send);

	return $send;
}


function _app($app_id=APP_ID, $i='all') {//��������� ������ � ����������
	$key = 'app'.$app_id;
	if(!$arr = _cache_get($key)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".$app_id;
		if(!$arr = query_assoc($sql))
			die('���������� �������� ������ ����������.');

		_cache_set($key, $arr);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return '_app: ����������� ����';

	return $arr[$i];
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
function _sumSpace($sum, $oo=0, $znak=',') {//���������� ����� � �������� ���� � ���������
	$minus = $sum < 0 ? -1 : 1;
	$sum *= $minus;
	$send = '';
	$floor = floor($sum);
	$drob = round($sum - $floor, 2) * 100;
	while($floor > 0) {
		$del = $floor % 1000;
		$floor = floor($floor / 1000);
		if(!$del) $send = ' 000'.$send;
		elseif($del < 10) $send = ($floor ? ' 00' : '').$del.$send;
		elseif($del < 100) $send = ($floor ? ' 0' : '').$del.$send;
		else $send = ' '.$del.$send;
	}
	$send = $send ? trim($send) : 0;
	$send = $drob ? $send.$znak.($drob < 10 ? 0 : '').$drob : $send;
	$send = $oo && !$drob ? $send.$znak.'00' : $send;
	return ($minus < 0 ? '-' : '').$send;
}
function _txt($v, $utf8=0, $no_trim=0) {
	$v = htmlspecialchars($v);
	if(!$no_trim)
		$v = trim($v);
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
			return $return_arr ? array() : 0;
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
	if(!is_array($val))
		return iconv('WINDOWS-1251', 'UTF-8', $val);

	foreach($val as $k => $v) {
		if(is_array($v)) {
			$val[$k] = utf8($v);
			continue;
		}
		$val[$k] = preg_match(REGEXP_INTEGER, $v) ? _num($v, 1) : utf8($v);
	}

	return $val;
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
			'id' => _num($uid),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($title))))
		);
	}
	return $send;
}
function _assJson($arr) {//������������� ������ ������ ��������
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
function _json($arr) {//������� ������� � JS
	if(empty($arr))
		return '[]';

	//�����������, ������������� ������ ��� ����������������
	$is_ass = range(0,count($arr) - 1) !== array_keys($arr);

	$send = array();
	foreach($arr as $k => $v) {
		if(is_array($v))
			$v = _json($v);
		else
			$v = preg_match(REGEXP_NUMERIC, $v) ? $v : '"'.addslashes(_br($v)).'"';
		if($is_ass)
			$v = $k.':'.$v;
		$send[] = $v;
	}
	return
		($is_ass ? '{' : '[').
		implode(',', $send).
		($is_ass ? '}' : ']');
}

function _vkapi($method, $param=array()) {//��������� ������ �� api ���������
	$param += array(
		'v' => 5.64,
		'lang' => 'ru',
		'access_token' => 'be6861a8be6861a8be6861a82cbe519716bbe68be6861a8e74e64410e898fe15cfbac8e'
	);

	$url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($param);
	$res = file_get_contents($url);
	$res = json_decode($res, true);
//	if(DEBUG)
//		$res['url'] = $url;
	return $res;
}


function _jsCache() {//������������ ����� JS � ������� (��������, �����)
	$ELM = array();
	$BLK = array();

	$block = _BE('block_all');

	foreach($block as $block_id => $r)
		$BLK[] = $block_id.':{elem_id:'.$r['elem_id'].'}';

	foreach(_BE('elem_all') as $elem_id => $r) {
		$block_id = $r['block_id'];

		$val = array();
		$val[] = 'name:"'.addslashes($r['name']).'"';
		$val[] = 'block_id:'.$block_id;

		//�������� ������ (dialog source)
		if($block[$block_id]['obj_name'] == 'dialog')
			$val[] = 'ds:'.$block[$block_id]['obj_id'];

		//������� �������� ������������ �������
		if($r['dialog_id'] == 29 || $r['dialog_id'] == 59)
			$val[] = 'issp:1';

		for($n = 1; $n <= 8; $n++) {
			$num = 'num_'.$n;
			if($r[$num])
				$val[] = $num.':'.$r[$num];
			$txt = 'txt_'.$n;
			if(!empty($r[$txt]))
				$val[] = $txt.':"'.addslashes(_br($r[$txt])).'"';
		}

		$ELM[] = $elem_id.':{'.implode(',', $val).'}';
	}

	$save = 'var ELMM={'.implode(",\n", $ELM).'},'.
				"\n\n".
				'BLKK={'.implode(",\n", $BLK).'};';
	$fp = fopen(APP_PATH.'/js_cache/app0.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

}

function _cache($v=array()) {
	if(!defined('CACHE_DEFINE')) {
		define('CACHE_TTL', 86400);//����� � ��������, ������� ������ ���
		define('CACHE_DEFINE', true);
	}

	//��������:
	//	get - ���������� ������ �� ���� (�� ���������)
	//	set - ��������� ������ � ���
	//	clear - ������� ����
	$action = empty($v['action']) ? 'get' : $v['action'];

	//���������� ��������: �������� ��� ���� ����������
	//���� ����������, �� � ����� ����� ������������ �������
	$global = !empty($v['global']);

	if(empty($v['key']))
		die('����������� ���� ����.');

	$key = $v['key'];

	if(is_array($key))
		die('���� ���� �� ����� ���� ��������.');

	$key = '__'.($global ? 'GLOBAL' : 'APP'.APP_ID).'_'.$key;

	switch($action) {
		case 'set':
			if(!isset($v['data']))
				die('����������� ������ ��� �������� � ���.');

			xcache_set($key, $v['data'], CACHE_TTL);

			return $v['data'];
		case 'get': return xcache_get($key);
		case 'clear':
			xcache_unset($key);
			return true;
		default: die('����������� �������� ����.');
	}
}
function _cache_get($key, $global=0) {//��������� �������� ����
	return _cache(array(
		'action' => 'get',
		'key' => $key,
		'global' => $global
	));
}
function _cache_set($key, $data, $global=0) {//������ �������� � ���
	return _cache(array(
		'action' => 'set',
		'key' => $key,
		'data' => $data,
		'global' => $global
	));
}
function _cache_clear($key, $global=0) {//������� ����
	if($key == 'all') {
		xcache_clear_cache(1);
		return true;
	}

	return _cache(array(
		'action' => 'clear',
		'key' => $key,
		'global' => $global
	));
}
