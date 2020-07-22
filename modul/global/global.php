<?php
define('TIME', microtime(true));
define('CRON_MAIL', 'mihan_k@mail.ru');

//setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_ALL, 'ru_RU.UTF-8');
//setlocale(LC_ALL, 'Russian_Russia.65001');
setlocale(LC_NUMERIC, 'en_US');
//iconv_set_encoding('output_encoding'  , 'UTF-8');

define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));
define('DOMAIN', $_SERVER['SERVER_NAME']);

define('CACHE_USE', true); //включение кеша
define('CACHE_TTL', 86400);//время в секундах, которое хранит кеш

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/modul/global/regexp.php';
require_once GLOBAL_DIR.'/modul/global/mysql.php';
require_once GLOBAL_DIR.'/modul/global/date.php';
require_once GLOBAL_DIR.'/modul/global/bug.php';
require_once GLOBAL_DIR.'/modul/debug/debug.php';
require_once GLOBAL_DIR.'/modul/db/db.php';
require_once GLOBAL_DIR.'/modul/global/clone.php';
require_once GLOBAL_DIR.'/modul/global/html.php';
require_once GLOBAL_DIR.'/modul/global/user.php';
require_once GLOBAL_DIR.'/modul/page/page.php';
require_once GLOBAL_DIR.'/modul/block/block.php';
require_once GLOBAL_DIR.'/modul/tag/tag.php';
require_once GLOBAL_DIR.'/modul/element/element.php';
require_once GLOBAL_DIR.'/modul/action/action.php';
require_once GLOBAL_DIR.'/modul/spisok/spisok.php';

define('YEAR_CUR', strftime('%Y'));
define('YEAR_MON', strftime('%Y-%m'));
define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));

define('CODE', _txt(@$_COOKIE['code']));
define('DEBUG', _num(@$_COOKIE['debug']));
define('MIN', DEBUG ? '' : '.min');

define('URL', APP_HTML.'/index.php?'.TIME);
define('AJAX', APP_HTML.'/ajax.php?'.TIME);

session_name('apppp');
session_start();

//авторизация для xCache
//$_SERVER["PHP_AUTH_USER"] = "admin";
//$_SERVER["PHP_AUTH_PW"] = "6000030";

//сборщик подсказок
$HINT_MASS = array();

function _setting() {//установка констант-настроек
	$key = 'SETTING';
	if(!$arr = _cache_get($key, 1)) {
		$sql = "SELECT `key`,`v` FROM `_setting`";
		$arr = query_ass($sql);

		$arr = _settingInsert($arr, 'SCRIPT', 100);
		$arr = _settingInsert($arr, 'JS_CACHE', 1);
		$arr = _settingInsert($arr, 'APP_ACCESS', 1);

		_cache_set($key, $arr, 1);
	}

	if(empty($arr)) {
		echo 'Global SETTING yok.';
		exit;
	}

	//версия скриптов
	define('SCRIPT', _num($arr['SCRIPT']).(LOCAL ? rand(1, 9999) : ''));
	//версия кеша JS - app0.js
	define('JS_CACHE', _num($arr['JS_CACHE']));
	//глобальный доступ к приложению
	define('APP_ACCESS', _bool($arr['APP_ACCESS']));
}
function _settingInsert($arr, $key, $v) {//проверка наличия всех ключей. Если отсутствуют, то внесение
	if(isset($arr[$key]))
		return $arr;

	$sql = "INSERT INTO `_setting` (
				`key`,
				`v`
			) VALUES (
				'".$key."',
				".$v."
			)";
	query($sql);

	$arr[$key] = $v;

	return $arr;
}

function _regFilter($v) {//проверка регулярного выражения на недопустимые символы
	$reg = '/(\[)/'; // скобка [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}

function _end($count, $o1, $o2, $o5=false) {
	$count = abs($count);
	if($o5 === false)
		$o5 = $o2;
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
function _dn($v, $cls='dn') {//показ/скрытие блока на основании условия
	if(empty($v))
		return ' '.$cls;
	return '';
}

function _num($v, $minus=0) {
	if(empty($v))
		return 0;
	if(is_array($v))
		return 0;
	if(is_string($v) && $minus && !preg_match(REGEXP_INTEGER, $v))
		return 0;
	if(is_string($v) && !$minus && !preg_match(REGEXP_NUMERIC, $v))
		return 0;

	return $v * 1;
}
function _bool($v) {//проверка на булево число
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return 1;
}
function _cena($v, $minus=0, $kop=0, $del='.') {//проверка на цену.
	/*
		$minus - может ли цена быть минусовой.
		$kop - возвращать с копейками, даже если 00
		$del - знак после запятой
	*/
	if(empty($v))
		return 0;
	if(is_array($v))
		return 0;
	if(is_string($v) && !preg_match($minus ? REGEXP_CENA_MINUS : REGEXP_CENA, $v))
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
function _ms($v, $del='.') {//проверка на единицу измерения с дробями 0.000
	/*
		$del - знак после запятой
	*/
	if(empty($v) || is_array($v) || !preg_match(REGEXP_MS, $v))
		return 0;

	$v = str_replace(',', '.', $v);
	$v = round($v, 3);

	$v = str_replace(',', $del, $v);
	$v = str_replace('.', $del, $v);

	return $v;
}
function _sumSpace($sum, $oo=0, $znak=',') {//Приведение суммы к удобному виду с пробелами
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
function _numToWord($num, $firstSymbolUp=false) {
	$num = intval($num);
	$one = array(
		0 => 'ноль',
		1 => 'один',
		2 => 'два',
		3 => 'три',
		4 => 'четыре',
		5 => 'пять',
		6 => 'шесть',
		7 => 'семь',
		8 => 'восемь',
		9 => 'девять',
		10 => 'деcять',
		11 => 'одиннадцать',
		12 => 'двенадцать',
		13 => 'тринадцать',
		14 => 'четырнадцать',
		15 => 'пятнадцать',
		16 => 'шестнадцать',
		17 => 'семнадцать',
		18 => 'восемнадцать',
		19 => 'девятнадцать'
	);
	$ten = array(
		2 => 'двадцать',
		3 => 'тридцать',
		4 => 'сорок',
		5 => 'пятьдесят',
		6 => 'шестьдесят',
		7 => 'семьдесят',
		8 => 'восемьдесят',
		9 => 'девяносто'
	);
	$hundred = array(
		1 => 'сто',
		2 => 'двести',
		3 => 'триста',
		4 => 'четыреста',
		5 => 'пятьсот',
		6 => 'шестьсот',
		7 => 'семьсот',
		8 => 'восемьсот',
		9 => 'девятьсот'
	);

	if($num < 20)
		return $one[$num];

	$word = '';
	if($num % 100 > 0)
		if($num % 100 < 20)
			$word = $one[$num % 100];
		else
			$word = $ten[floor($num / 10) % 10].($num % 10 > 0 ? ' '.$one[$num % 10] : '');

	if($num % 1000 >= 100)
		$word = $hundred[floor($num / 100) % 10].' '.$word;

	if($num >= 1000) {
		$t = floor($num / 1000) % 1000;
		$word = ' тысяч'._end($t, 'а', 'и', '').' '.$word;
		if($t % 100 > 2 && $t % 100 < 20)
			$word = $one[$t % 100].$word;
		else {
			if($t % 10 == 1)
				$word = 'одна'.$word;
			elseif($t % 10 == 2)
				$word = 'две'.$word;
			elseif($t % 10 != 0)
				$word = $one[$t % 10].' '.$word;
			if($t % 100 >= 20)
				$word = $ten[floor($t / 10) % 10].' '.$word;
		}
		if($t >= 100)
			$word = $hundred[floor($t / 100) % 10].' '.$word;
	}
	if($firstSymbolUp)
		$word[0] = strtoupper($word[0]);
	return trim($word);
}
function _txt($v, $notrim=false) {
	if(!isset($v))
		return '';
	$v = htmlspecialchars($v);
	if(!$notrim)
		$v = trim($v);
	return $v;
}
function _br($v, $replace='<br>') {//вставка br в текст при нахождении enter
	if(!is_string($v))
		return $v;
	return str_replace("\n", $replace, $v);
}
function _daNet($v) {//$v: 1 -> да, 0 -> нет
	return $v ? 'да' : 'нет';
}
function _msgRed($msg) {//сообщение об ошибке красного цвета
	if(!DEBUG)
		return '';
	return '<div class="fs10 red">'.$msg.'</div>';
}
function _hide0($v) {//возвращает пустоту, если значение 0 или негатив
	return $v ? $v : '';
}
function _0($num) {//добавление нуля к числу, если меньше 10
	return ($num < 10 ? '0' : '').$num;
}

function _ids($ids, $return='ids') {//проверка корректности списка id, составленные через запятую
	/*
		$return - формат возвращаемого значения
				ids: id через запятую (по умолчанию)
				arr: массив (также если 1)
			  count: количество
		count_empty: количество, если = 0, то пустота
	*/
	if(!$ids)
		return _idsReturn(0, $return);

	$arr = array();

	foreach(explode(',', $ids) as $id) {
		if(!preg_match(REGEXP_INTEGER, $id))
			return _idsReturn(0, $return);
		if(!_num($id, 1))
			continue;
		$arr[] = _num($id, 1);
	}

	return _idsReturn(implode(',', $arr), $return);
}
function _idsReturn($v, $return) {//для _ids - формат возвращаемого результата
	switch($return) {
		default:
		case 'first'://первое значение
			$v = explode(',', $v);
			return _num($v[0]);
		case 'ids': return $v ? $v : 0;
		case 1:
		case 'arr': return $v ? explode(',', $v) : array();
		case 'count':return $v ? count(explode(',', $v)) : 0;
		case 'count_empty': return $v ? count(explode(',', $v)) : '';
	}
}
function _idsGet($arr, $i='id') {//возвращение из массива списка id через запятую
/*
	key: сборка id по ключу
*/
	$ids = array();
	foreach($arr as $id => $r) {
		if($i == 'key') {
			$ids[] = $id;
			continue;
		}
		if(empty($r[$i]))
			continue;
		if(is_array($r[$i]))
			continue;
		$ids[] = $r[$i];
	}
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _idsAss($v) {//получение списка id вида: $v[25] = 1; - выбранный список
	$send = array();

	if(empty($v))
		return $send;

	$arr = is_array($v) ? $v : _ids($v, 'arr');

	foreach($arr as $id)
		$send[$id] = 1;

	return $send;
}
function _idsFirst($v) {//первое значение последовательного массива (или идентификаторов через запятую)
	if(empty($v))
		return 0;
	if(!is_array($v))
		$v = _ids($v, 'arr');
	if(!isset($v[0]))
		return 0;

	return _num($v[0], 1);
}
function _idsLast($v) {//последнее значение последовательного массива (или идентификаторов через запятую)
	if(empty($v))
		return 0;

	if(!is_array($v))
		$v = _ids($v, 1);

	$c = count($v);

	return _num($v[$c - 1]);
}

function mb_ucfirst($txt) {//делание заглавной первую букву текста
//	mb_internal_encoding('UTF-8');
	$txt = mb_strtoupper(mb_substr($txt, 0, 1)).mb_substr($txt, 1);
	return $txt;
}
function unescape($str){
	$escape_chars = '0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 '.
		'0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D '.
		'041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 '.
		'0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F';
	$russian_chars = 'А а Б б В в Г г Ґ ґ Д д Е е Ё ё Є є Ж ж З з И и І і Й й К к Л л М м Н н О о П п Р р С с Т т У у Ф ф Х х Ц ц Ч ч Ш ш Щ щ Ъ ъ Ы ы Ь ь Э э Ю ю Я я';
	$e = explode(' ', $escape_chars);
	$r = explode(' ', $russian_chars);
	$rus_array = explode('%u', $str);
	$new_word = str_replace($e, $r, $rus_array);
	$new_word = str_replace('%20', ' ', $new_word);
	return implode($new_word);
}
function translit($str) {
	$list = array(
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ё' => 'E',
		'Ж' => 'J',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'H',
		'Ц' => 'TS',
		'Ч' => 'CH',
		'Ш' => 'SH',
		'Щ' => 'SCH',
		'Ъ' => '',
		'Ы' => 'YI',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'YU',
		'Я' => 'YA',
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ё' => 'e',
		'ж' => 'j',
		'з' => 'z',
		'и' => 'i',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'h',
		'ц' => 'ts',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'sch',
		'ъ' => 'y',
		'ы' => 'yi',
		'ь' => '',
		'э' => 'e',
		'ю' => 'yu',
		'я' => 'ya',
		' ' => '_',
		'№' => 'N',
		'¦' => ''
	);
	return strtr($str, $list);
}

function _pr($arr, $emptyReturn=false) {//аналог функции print_r
	if(empty($arr))
		return $emptyReturn ? '' : _prMsg('массив пуст');

	if(!is_array($arr))
		return $arr;

	return
	'<div class="dib pad5 bor-e8 l">'.
		_prFor($arr).
	'</div>';
}
function _prMsg($msg) {
	return '<div class="dib grey i pad5 bor-e8">'.$msg.'</div>';
}
function _prFor($arr, $sub=0) {//перебор массива
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

function _arr($arr, $i=false) {//Последовательный массив
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? _cena($v) : htmlspecialchars_decode($v);
	}
	return $send;
}
function _arrKey($arr, $key='id') {//расстановка значений массива согласно ключу
	if(empty($arr))
		return array();
	if(!is_array($arr))
		return array();

	$send = array();
	foreach($arr as $r) {
		if(!isset($r[$key]))
			continue;
		$send[$r[$key]] = $r;
	}

	return $send;
}
function _sel($arr) {
	$send = array();
	foreach($arr as $id => $title) {
		$send[] = array(
			'id' => $id,
			'title' => trim($title)
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
function _selArray($arr) {//список для _select при отправке через ajax
	$send = array();
	foreach($arr as $id => $title) {
		$send[] = array(
			'id' => _num($id),
			'title' => addslashes(htmlspecialchars_decode(trim($title)))
		);
	}
	return $send;
}
function _assJson($arr) {//Ассоциативный массив одного элемента
	$send = array();
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_NUMERIC, $v) ? $v : '"'.$v.'"');
	return '{'.implode(',', $send).'}';
}
function _arrJson($arr, $i=false) {//Последовательный массив
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? $v : '"'.addslashes(htmlspecialchars_decode($v)).'"';
	}
	return '['.implode(',', $send).']';
}
function _json($arr, $n=0, $ass_empty=false) {//перевод массива в JS
	if(empty($arr))
		return $ass_empty ? '{}' : '[]';

	//определение, ассоциативный массив или последовательный
	$is_ass = range(0,count($arr) - 1) !== array_keys($arr);

	$send = array();
	foreach($arr as $k => $v) {
		if(is_array($v))
			$v = _json($v, 1);
		else
			$v = preg_match(REGEXP_CENA_MINUS_POINT_ONLY, $v) ? $v : '"'.addslashes(_br($v)).'"';
		if($is_ass)
			$v = $k.':'.$v;
		$send[] = $v;
	}
	return
		($is_ass ? '{' : '[').
		implode(','.(!$n ? "\n" : ''), $send).
		($is_ass ? '}' : ']');
}
function _arrNum($arr) {//переделка значений массива в INT, если есть
	if(empty($arr))
		return array();
	if(!is_array($arr))
		return $arr;
	foreach($arr as $k => $v) {
		if(is_array($v)) {
			$arr[$k] = _arrNum($v);
			continue;
		}
		if(is_string($v) && preg_match(REGEXP_CENA_MINUS_POINT_ONLY, $v))
			$arr[$k] = $v * 1;
	}
	return $arr;
}

function _arrChild($arr) {//формирование массива с дочерними значеними по `parent_id`
	$send = array();
	foreach($arr as $id => $r)
		$send[$r['parent_id']][$id] = $r;
	return _arrChildOne($send);
}
function _arrChildOne($child, $parent_id=0) {//расстановка дочерних значений
	if(!$send = @$child[$parent_id])
		return array();

	foreach($send as $id => $r)
		$send[$id]['child'] = _arrChildOne($child, $id);

	return $send;
}

function _empty($msg) {//Информация на сером фоне без отступов
	return '<div class="_empty">'.$msg.'</div>';
}
function _empty20($msg) {//Информация на сером фоне c отступами 20px
	return '<div class="_empty mar20">'.$msg.'</div>';
}
function _emptyMin($msg) {
	return '<div class="_empty min">'.$msg.'</div>';
}
function _emptyMin10($msg) {
	return '<div class="_empty min mar10">'.$msg.'</div>';
}
function _emptyRed($msg) {
	return '<div class="_empty red">'.$msg.'</div>';
}
function _emptyMinRed($msg) {
	return '<div class="_empty min red">'.$msg.'</div>';
}
function _emptyRed10($msg) {
	return '<div class="_empty red mar10">'.$msg.'</div>';
}


function _vkapi($method, $param=array()) {//получение данных из api вконтакте
	if(LOCAL)
		return array();

	$param += array(
		'v' => 5.64,
		'lang' => 'ru',
		'access_token' => 'be6861a8be6861a8be6861a82cbe519716bbe68be6861a8e74e64410e898fe15cfbac8e'
	);

	$url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($param);
	$res = file_get_contents($url);
	$res = json_decode($res, true);

	return $res;
}

/*
function appUpdate() {//применение app_id к блокам и элементам - разовая функция
	$sql = "select * from _block group by obj_name,obj_id";
	foreach(query_arr($sql) as $r) {
		_blockAppIdUpdate($r['obj_name'], $r['obj_id']);
	}

	$sql = "update _element e set app_id=IFNULL((select app_id from _block where e.block_id=id),0)";
	query($sql);

	$sql = "SELECT
				distinct `parent_id` id,
				(select app_id from _element where id=e.parent_id) app_id
			FROM _element e
			WHERE parent_id";
	foreach(query_arr($sql) as $r) {
		$sql = "UPDATE _element
				SET app_id=".$r['app_id']."
				WHERE parent_id=".$r['id'];
		query($sql);
	}

	//ids элементов истории действий в диалогах
	$dlgHist = array();
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `app_id`=1";
	foreach(query_arr($sql) as $r) {
		$dlgHist[] = $r['insert_history_elem'];
		$dlgHist[] = $r['edit_history_elem'];
		$dlgHist[] = $r['del_history_elem'];
	}
	$dlgHist = array_diff($dlgHist, array(''));
	$dlgHist = implode(',', $dlgHist);

	$sql = "UPDATE `_element`
			SET `app_id`=1
			WHERE `id` IN (".$dlgHist.")";
	query($sql);
}
*/

function _jsCache() {//файл JS с блоками и элементами
	$save =
	'var PAGE_LIST=[],'.
		"\n".
		'PLSA='._jsCachePageSa().','.//страницы SA
		"\n\n".
		'ELEM_COLOR='._colorJS().','.
		"\n\n".
		'BLKK='._jsCacheBlk().','.
		"\n\n".
		'ELMM='._jsCacheElm().';';

	$fp = fopen(APP_PATH.'/js_cache/app0.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	if(APP_ID) {
		$save =
			'PAGE_LIST='._json(_jsCachePage()).';'.
			"\n".'if(SA)for(i in PLSA)PAGE_LIST.push(PLSA[i]);'.
			"\n\n".
		'var TMP='._jsCacheBlk(APP_PARENT).';'."\n".'for(i in TMP)BLKK[i]=TMP[i];'.
			"\n\n".
			'TMP='._jsCacheElm(APP_PARENT).';'."\n".'for(i in TMP)ELMM[i]=TMP[i];'.
			"\n\n";

		$fp = fopen(APP_PATH.'/js_cache/app'.APP_ID.'.js', 'w+');
		fwrite($fp, $save);
		fclose($fp);
	}

	$sql = "UPDATE `_setting`
			SET `v`=`v`+1
			WHERE `key`='JS_CACHE'";
	query($sql);

	_cache_clear('SETTING', 1);
}
function _jsCacheAppControl() {//проверка наличия файла JS для текущего приложения
	if(!APP_ID)
		return;
	if(file_exists(APP_PATH.'/js_cache/app'.APP_ID.'.js'))
		return;
	_jsCache();
	_count_update();//обновление счётчиков, если приложение клонировано
}
function _jsCachePageSa() {//страницы SA для select
	$page = _pageCache();
	$child = array();
	$send[] = array(
		'title' => 'Страницы SA',
		'info' => 1
	);
	foreach(_pageSaForSelect($page, $child) as $r)
		$send[] = $r;
	return _json($send);
}
function _jsCachePage() {//страницы APP для select
	$page = _pageCache();
	$child = array();
	foreach($page as $id => $r) {
		if(!$r['parent_id'])
			continue;

		if(empty($child[$r['parent_id']]))
			$child[$r['parent_id']] = array();

		$child[$r['parent_id']][] = $r;
		unset($page[$id]);
	}
	$send = _pageChildArr($page, $child);
	return $send;
}
function _jsCacheBlk($app_id=0) {
	$BLK = array();

	$sql = "SELECT *
			FROM `_block`
			WHERE `app_id`=".$app_id."
			ORDER BY `id`";
	$arr = query_arr($sql);
	foreach($arr as $block_id => $r)
		$BLK[$block_id] = _jsCacheBlkOne($block_id);

	return _json($BLK);
}
function _jsCacheBlkOne($block_id) {
	if(!$r = _blockOne($block_id))
		return array();

	$val = array();
	$val['parent_id'] = $r['parent_id'];
	$val['obj_name'] = $r['obj_name'];
	$val['obj_id'] = $r['obj_id'];
	$val['elem_id'] = $r['elem_id'];
	$val['width_auto'] = $r['width_auto'];
	$val['bor'] = $r['bor'];
	$val['pos'] = $r['pos'];
	$val['bg'] = $r['bg'];
	$val['ov'] = $r['ov'];
	$val['child_count'] = $r['child_count'];
	$val['hidden'] = $r['hidden'];

	//скрытие/показ блоков - действие для элементов
	$val['xx'] = $r['xx'];
	$val['xx_ids'] = $r['xx_ids'];

	// action229
	if(!empty($r['hint']))
		$val['hint'] = $r['hint'];
	if($r['obj_name'] == 'dialog') {
		$val['show_create'] = $r['show_create'];
		$val['show_edit'] = $r['show_edit'];
	}

	if(!empty($r['action'])) {
		//удаление фильтров, ибо в JS они не требуются
		foreach($r['action'] as $act) {
			unset($act['filter']);
			$val['action'][] = $act;
		}
	}

	return $val;
}
function _jsCacheElm($app_id=0) {
	$ELM = array();

	foreach(_BE('elem_all') as $elem_id => $r) {
		if($r['app_id'] != $app_id)
			continue;
		if(!$el = _element('js', $r))
			continue;
		$ELM[$elem_id] = $el;
	}

	return _json($ELM);
}







function _cache($v=array()) {
	/*
		action (действие):
			get - считывание данных из кеша (по умолчанию)
			set - занесение данных в кеш
			isset - проверка существования кеша
			clear - очистка кеша

		global:
			1 - глобальные значения
			0 - конкретное приложение

		key: ключ кеша
	*/

	$key = '__'._cachePrefix($v).'_'._cacheKey($v);

	switch(_cacheAction($v)) {
		case 'get': return CACHE_USE ? apcu_fetch($key) : false;
		case 'set':
//			if(!isset($v['data']))
//				die('Отсутствуют данные для внесения в кеш. Key: '.$key);

			if(CACHE_USE)
				apcu_store($key, $v['data'], CACHE_TTL);

			return $v['data'];
		case 'isset': return CACHE_USE ? apcu_exists($key) : false;
		case 'clear':
			if(CACHE_USE)
				apcu_delete($key);
			return true;
		default: die('Неизвестное действие кеша.');
	}
}
function _cacheAction($v) {//получение действия кеша
	if(empty($v['action']))
		return 'get';
	return $v['action'];
}
function _cacheKey($v) {//получение ключа кеша
	if(empty($v['key']))
		die('Отсутствует ключ кеша.');
	if(is_array($v['key']))
		die('Ключ кеша не может быть массивом.');
	return $v['key'];
}
function _cachePrefix($v) {//получение префикса кеша
	if(!empty($v['global']))
		return 'GLOBAL';
	if(!defined('APP_ID'))
		return 'GLOBAL';
	if(empty(APP_ID))
		return 'GLOBAL';
	return 'APP'.APP_ID;
}
function _cache_get($key, $global=0) {//получение значений кеша
	return _cache(array(
		'action' => 'get',
		'key' => $key,
		'global' => $global
	));
}
function _cache_set($key, $data, $global=0) {//запись значений в кеш
	return _cache(array(
		'action' => 'set',
		'key' => $key,
		'data' => $data,
		'global' => $global
	));
}
function _cache_isset($key, $global=0) {//проверка, производилась ли запись в кеш
	return _cache(array(
		'action' => 'isset',
		'key' => $key,
		'global' => $global
	));
}
function _cache_clear($key, $global=0) {//очистка кеша
	if($key == 'all') {
		if(CACHE_USE)
			apcu_clear_cache();
		return true;
	}

	return _cache(array(
		'action' => 'clear',
		'key' => $key,
		'global' => $global
	));
}
function _cache_content() {//содержание кеша в диалоге [84] (подключаемая функция [12])
	if(!CACHE_USE)
		$send = 'Кеш отключен.';
	elseif(!$name = @$_COOKIE['cache_content_name'])
			$send = 'Отсутствует имя кеша.';
		else {
			if(!apcu_exists($name))
				$send = '<b>'.$name.'</b>: кеш не сохранён.';
			else {
				if(!$arr = apcu_fetch($name))
					$send = '<b>'.$name.'</b>: кеш пуст.';
				else
					$send =
						'<div class="fs15 b mb10">'.$name.'</div>'.
						_pr($arr);
			}
		}
	return
	'<div style="height:700px;width:560px;overflow-y:scroll;word-wrap:break-word" class="bg-fff bor-e8 pad10">'.
		$send.
	'</div>';
}
