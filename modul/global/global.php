<?php
define('TIME', microtime(true));

//setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_ALL, 'ru_RU.UTF-8');
//setlocale(LC_ALL, 'Russian_Russia.65001');
setlocale(LC_NUMERIC, 'en_US');
//iconv_set_encoding('output_encoding'  , 'UTF-8');

define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));
define('DOMAIN', $_SERVER['SERVER_NAME']);

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/modul/global/regexp.php';
require_once GLOBAL_DIR.'/modul/global/mysql.php';
require_once GLOBAL_DIR.'/modul/global/date.php';
require_once GLOBAL_DIR.'/modul/global/bug_func.php';
require_once GLOBAL_DIR.'/modul/debug/debug.php';
require_once GLOBAL_DIR.'/modul/db/db.php';
require_once GLOBAL_DIR.'/modul/global/html.php';
require_once GLOBAL_DIR.'/modul/global/user.php';
require_once GLOBAL_DIR.'/modul/page/page.php';
require_once GLOBAL_DIR.'/modul/block/block.php';
require_once GLOBAL_DIR.'/modul/element_tag/element_tag.php';
require_once GLOBAL_DIR.'/modul/element/element.php';
require_once GLOBAL_DIR.'/modul/spisok/spisok.php';

define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));
define('YEAR_CUR', strftime('%Y'));

define('CODE', _txt(@$_COOKIE['code']));
define('DEBUG', @$_COOKIE['debug']);

define('MIN', DEBUG ? '' : '.min');
//define('MIN', '');

define('URL', APP_HTML.'/index.php?'.TIME);
define('AJAX', APP_HTML.'/ajax.php?'.TIME);

//session_name('apppp');
//session_start();

//авторизация для xCache
//$_SERVER["PHP_AUTH_USER"] = "admin";
//$_SERVER["PHP_AUTH_PW"] = "6000030";

function _setting() {//установка констант-настроек
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

	//версия скриптов
	define('SCRIPT', _num($arr['SCRIPT']));
	//версия кеша JS - app0.js
	define('JS_CACHE', _num($arr['JS_CACHE']));
}

function _table($id=false) {//таблицы в базе с соответствующими идентификаторами
	$tab = array(
		 1 =>  '_app',
		 2 =>  '_block',
		 3 =>  '_dialog',
		 4 =>  '_dialog_group',
		 5 =>  '_element',
		 6 =>  '_element_func',
		 17 => '_element_format',
		 18 => '_element_hint',
		 7 =>  '_history',
		 8 =>  '_image',
		 9 =>  '_image_server',
		10 =>  '_page',
		11 =>  '_spisok',
		12 =>  '_user',
		14 =>  '_user_auth',
		15 =>  '_user_spisok_filter',
		16 =>  '_note'
	);

	if($id === false)
		return $tab;
	if(!$id = _num($id))
		return '';
	if(empty($tab[$id]))
		return '';

	return $tab[$id];
}
function _tableFrom($dialog) {//составление таблиц для запроса на основании данных из диалога
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

function _app($app_id=APP_ID, $i='all') {//Получение данных о приложении
	$key = 'app'.$app_id;
	if(!$arr = _cache_get($key)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".$app_id;
		if(!$arr = query_assoc($sql))
			die('Невозможно получить данные приложения. Кеш: '.$key);

		_cache_set($key, $arr);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return '_app: неизвестный ключ';

	return $arr[$i];
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
	if(empty($v) || is_array($v))
		return 0;

	if($minus && !preg_match(REGEXP_INTEGER, $v))
		return 0;

	if(!$minus && !preg_match(REGEXP_NUMERIC, $v))
		return 0;

	return intval($v);
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
function _txt($v, $notrim=false) {
	if(!isset($v))
		return '';
	$v = htmlspecialchars($v);
	if(!$notrim)
		$v = trim($v);
	return $v;
}
function _br($v, $replace='<br>') {//вставка br в текст при нахождении enter
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

	foreach(explode(',', $ids) as $i => $id) {
		if(!preg_match(REGEXP_NUMERIC, $id))
			return _idsReturn(0, $return);
		$arr[$i] = _num($id);
	}

	return _idsReturn(implode(',', $arr), $return);
}
function _idsReturn($v, $return) {//для _ids - формат возвращаемого результата
	switch($return) {
		default:
		case 'first'://первое значение
			$v = explode(',', $v);
			return _num($v[0]);
		case 'ids': return $v;
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
		if(is_array($r[$i]))
			continue;
		if(!empty($r[$i]))
			$ids[] = $r[$i];
	}
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _idsAss($v) {//получение списка id вида: $v[25] = 1; - выбранный список
	$send = array();

	if(empty($v))
		return $send;

	$arr = is_array($v) ? $v : _ids($v, 1);

	foreach($arr as $id)
		$send[$id] = 1;

	return $send;
}
function _idsLast($v) {//последнее значение последовательного массива (или идентификаторов через запятую)
	if(empty($v))
		return 0;

	if(!is_array($v))
		$v = _ids($v, 1);

	$c = count($v);

	return _num($v[$c - 1]);
}

function mb_ucfirst($txt) {//делание заклавной первую букву текста
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

function _pr($arr) {//аналог функции print_r
	if(empty($arr))
		return _prMsg('массив пуст');

	if(!is_array($arr))
		return _prMsg('не является массивом');

	return
	'<div class="dib pad5 bor-e8">'.
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
function _sel($arr) {
	$send = array();
	foreach($arr as $uid => $title) {
		$send[] = array(
			'uid' => $uid,
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
	foreach($arr as $uid => $title) {
		$send[] = array(
			'id' => _num($uid),
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
function _json($arr, $n=0) {//перевод массива в JS
	if(empty($arr))
		return '[]';

	//определение, ассоциативный массив или последовательный
	$is_ass = range(0,count($arr) - 1) !== array_keys($arr);

	$send = array();
	foreach($arr as $k => $v) {
		if(is_array($v))
			$v = _json($v, 1);
		else
			$v = preg_match(REGEXP_NUMERIC, $v) ? $v : '"'.addslashes(_br($v)).'"';
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
	foreach($arr as $k => $v) {
		if(is_array($v)) {
			$arr[$k] = _arrNum($v);
			continue;
		}
		if(preg_match(REGEXP_INTEGER, $v))
			$arr[$k] = _num($v, 1);
	}

	return $arr;
}

function _empty($msg) {//Информация на сером фоне
	return '<div class="_empty mar20">'.$msg.'</div>';
}
function _emptyMin($msg, $mar=10) {
	return '<div class="_empty min'.($mar ? ' mar'.$mar : '').'">'.$msg.'</div>';
}


function _vkapi($method, $param=array()) {//получение данных из api вконтакте
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
		'ELMM='._jsCacheElm().','.
		"\n\n".
		'VVV={};';

	$fp = fopen(APP_PATH.'/js_cache/app0.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	if(APP_ID) {
		$save =
			'PAGE_LIST='._jsCachePage().';'.
			"\n".'if(SA)for(i in PLSA)PAGE_LIST.push(PLSA[i]);'.
			"\n\n".
		'var TMP='._jsCacheBlk(APP_ID).';'."\n".'for(i in TMP)BLKK[i]=TMP[i];'.
			"\n\n".
			'TMP='._jsCacheElm(APP_ID).';'."\n".'for(i in TMP)ELMM[i]=TMP[i];'.
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
	return _json($send);
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
function _jsCacheElm($app_id=0) {
	$ELM = array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".$app_id."
			ORDER BY `id`";
	$arr = query_arr($sql);
	foreach($arr as $elem_id => $r) {
		if(!$el = _jsCacheElemOne($elem_id))
			continue;
		$ELM[$elem_id] = $el;
	}

	return _json($ELM);
}
function _jsCacheBlkOne($block_id) {
	if(!$r = _blockOne($block_id))
		return array();

	$val = array();
	$val['parent_id'] = $r['parent_id'];
	$val['obj_name'] = $r['obj_name'];
	$val['obj_id'] = $r['obj_id'];
	$val['elem_id'] = $r['elem_id'];
	$val['sa'] = $r['sa'];
	$val['width_auto'] = $r['width_auto'];
	$val['bor'] = $r['bor'];
	$val['pos'] = $r['pos'];
	$val['bg'] = $r['bg'];
	$val['bg70'] = $r['bg70'];
	$val['child_count'] = $r['child_count'];
	$val['hidden'] = $r['hidden'];

	//скрытие/показ блоков - действие для элементов
	$val['xx'] = $r['xx'];
	$val['xx_ids'] = $r['xx_ids'];

	return $val;
}
function _jsCacheElemOne($elem_id) {
	if(!$r = _elemOne($elem_id))
		return array();
	if(!$block_id = $r['block_id'])
		return array();
	if($block_id < 0)
		return array();

	$val = array();

	$val['dialog_id'] = $r['dialog_id'];
	$val['col'] = $r['col'];
	$val['name'] = $r['name'];
	$val['block_id'] = $block_id;

	$val['mar'] = $r['mar'];
	$val['font'] = $r['font'];
	$val['color'] = $r['color'];
	$val['size'] = $r['size'];
	$val['url'] = $r['url'];

	if($r['func'])
		$val['func'] = $r['func'];

	if(!empty($r['format']))
		$val['format'] = $r['format']['id'];

	if($r['hint'])
		$val['hint'] = $r['hint'];

	if($r['focus'])
		$val['focus'] = 1;

	if($dlg = _BE('dialog', $r['dialog_id'])) {
		if($dlg['element_style_access'])
			$val['style_access'] = $dlg['element_style_access'];
		if($dlg['element_afics'])
			$val['afics'] = $dlg['element_afics'];
		if($dlg['element_dialog_func'])
			$val['dialog_func'] = $dlg['element_dialog_func'];
		if($dlg['element_hint_access'])
			$val['hint_access'] = 1;
	}

	$val['width'] = $r['width'];

//	if($r['is_img'])
		$val['is_img'] = 1;

	//исходный диалог (dialog source)
	if($r['block']['obj_name'] == 'dialog')
		$val['ds'] = $r['block']['obj_id'];

	//элемент является подключаемым списком
	if($r['dialog_id'] == 29 || $r['dialog_id'] == 59)
		$val['issp'] = 1;

	//элемент-меню переключения блоков
	if($r['dialog_id'] == 57)
		$val['def'] = $r['def'];

	for($n = 1; $n <= 8; $n++) {
		$num = 'num_'.$n;
		if($r[$num])
			$val[$num] = $r[$num];
		elseif($r['dialog_id'] == 60 && $n == 7)//ограничение высоты фото [60] - обязательный num_7
			$val[$num] = 0;


		$txt = 'txt_'.$n;
		if(!empty($r[$txt]))
			$val[$txt] = $r[$txt];
	}

	return $val;
}
function _jsCacheVvv($elem_id) {//значения для элементов (только статические значения)
	$el = _elemOne($elem_id);

	switch($el['dialog_id']) {
		//Меню переключения блоков - список пунктов
		case 57:
			return _elemVvv($elem_id);
	}

	return array();
}

function _cache($v=array()) {
	if(!defined('CACHE_DEFINE')) {
		define('CACHE_USE', true);//включение кеша
		define('CACHE_TTL', 86400);//время в секундах, которое хранит кеш
		define('CACHE_DEFINE', true);
	}

	//действие:
	//	get - считывание данных из кеша (по умолчанию)
	//	set - занесение данных в кеш
	//	clear - очистка кеша
	$action = empty($v['action']) ? 'get' : $v['action'];

	//глобальное значение: доступно для всех приложений
	//если внутреннее, то к ключу будет прибавляться префикс
	$global = !empty($v['global']);

	if(empty($v['key']))
		die('Отсутствует ключ кеша.');

	$key = $v['key'];

	if(is_array($key))
		die('Ключ кеша не может быть массивом.');

	$key = '__'.($global || !_num(@APP_ID) ? 'GLOBAL' : 'APP'.APP_ID).'_'.$key;

	switch($action) {
		case 'get': return CACHE_USE ? xcache_get($key) : false;
		case 'set':
//			if(!isset($v['data']))
//				die('Отсутствуют данные для внесения в кеш. Key: '.$key);

			if(CACHE_USE)
				xcache_set($key, $v['data'], CACHE_TTL);

			return $v['data'];
		case 'isset': return CACHE_USE ? xcache_isset($key) : false;
		case 'clear':
			if(CACHE_USE)
				xcache_unset($key);
			return true;
		default: die('Неизвестное действие кеша.');
	}
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
			xcache_clear_cache(1);
		return true;
	}

	return _cache(array(
		'action' => 'clear',
		'key' => $key,
		'global' => $global
	));
}
function _cache_content($el, $unit) {//содержание кеша в диалоге [84] (подключаемая функция [12])
	if(!CACHE_USE)
		$send = 'Кеш отключен.';
	elseif(!$name = @$_COOKIE['cache_content_name'])
			$send = 'Отсутствует имя кеша.';
		else {
			if(!xcache_isset($name))
				$send = '<b>'.$name.'</b>: кеш не сохранён.';
			else {
				if(!$arr = xcache_get($name))
					$send = '<b>'.$name.'</b>: кеш пуст.';
				else
					$send =
						'<div class="fs15 b mb10">'.$name.'</div>'.
						_pr($arr);
			}
		}
	return
	'<div style="height:700px;overflow-y:scroll" class="bg-fff bor-e8 pad10">'.
		$send.
	'</div>';
}
