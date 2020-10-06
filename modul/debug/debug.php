<?php
function _debug($i='') {
	if(!SA)
		return '';

	if($i == 'style')
		return '<link rel="stylesheet" type="text/css" href="modul/debug/debug'.MIN.'.css?'.SCRIPT.'" />';

	global $SQL_TIME, $SQL_QUERY, $SQL_QUERY_T;

	$goFace = SITE ? 'iframe' : 'site';
	$send =
		'<div id="debug-footer">'.
			'<a class="debug_toggle'.(DEBUG ? ' on' : '').'">'.(DEBUG ? 'От' : 'В').'ключить Debug</a> :: '.
			'<a id="cookie_clear">Очисить cookie</a> :: '.
			'<a id="count_update">Обновить суммы</a> :: '.
			'<a id="cache_clear">Очисить кэш ('.SCRIPT.')</a> :: '.
			'sql <b>'.count($SQL_QUERY).'</b> ('.round($SQL_TIME, 3).') :: '.
			'php '.round(microtime(true) - TIME, 3).' :: '.
			'js <em></em>'.
   (LOCAL ? ' :: <a onclick="_faceGo(\''.$goFace.'\')">go '.$goFace.'</a>' : '').
		'</div>'.
		'<script src="modul/debug/debug'.MIN.'.js?'.SCRIPT.'"></script>';

	if(DEBUG) {
		$get = '';
		ksort($_GET);
		foreach($_GET as $k => $v)
			$get .= '<b>'.$k.'</b>='.$v.'<br>';
		$get .= '<textarea>http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'</textarea>';



		$send .=
		'<div id="_debug" class="'._dn(empty($_COOKIE['debug_show']), 'show').'">'.
			'<h1>+</h1>'.
			'<h2><div class="dmenu">'.
					'<a>cache</a>'.
					'<a>sql</a>'.
					'<a>cookie</a>'.
					'<a>get</a>'.
					'<a>cons</a>'.
					'<a>ajax</a>'.
				'</div>'.
				'<div class="pg cache dn">'._debug_cache().'</div>'.
				'<div class="pg sql pad10 dn">'._debug_sql().'</div>'.
				'<div class="pg cookie dn">'.
					'<a onclick="debugCookieUpdate($(this))">Обновить</a>'.
					'<div class="mt10">'._debug_cookie().'</div>'.
				'</div>'.
				'<div class="pg get dn">'.$get.'</div>'.
				'<div class="pg cons pad5 dn">'.
					'<div class="cons-div mar5">&nbsp;</div>'.
				'</div>'.
				'<div class="pg ajax pad10 dn">&nbsp;</div>'.
			'</h2>'.
		'</div>';
	}

	return $send;
}

function _debug_cache() {//результат использования кеша
	if(!CACHE_USE)
		return '';

	$asi = apcu_sma_info();//информация о выделямой памяти
	$aci = apcu_cache_info();//информация о данных, сохранённых в кеше

	$size = round($asi['seg_size'] / 1024 / 1024, 2);
	$avail = round($asi['avail_mem'] / 1024 / 1024, 2);
	$busy = round($size - $avail, 2);

	$list = $aci['cache_list'];
	$cc = count($list);

	$send =
		'<table class="_stab small mar10">'.
			'<tr><td class="">Общий кеш:'.
				'<td class="r"><b>'.$size.'</b> mb'.
			'<tr><td class="r clr8">Занято:'.
				'<td class="clr8 r"><b>'.$busy.'</b> mb'.
				'<td class="clr1">'.($cc ? $cc.' запис'._end($cc, 'ь', 'и', 'ей') : 'записей нет').
			'<tr><td class="r clr11">Свободно:'.
				'<td class="clr11 r"><b>'.$avail.'</b> mb'.
		'</table>';



	if(!$cc)
		return $send;

	$ccGlobal = array();//глобальный кеш
	$ccApp = array();   //кеш по приложениям
	$ccOther = array();  //кеш из других приложений
	foreach($list as $n => $r) {
		if(preg_match('/^__GLOBAL[a-z0-9_]{1,50}$/i', $r['info'])) {
			$ccGlobal[] = $r;
			continue;
		}

		if(preg_match('/^__APP[a-z0-9_]{1,50}$/i', $r['info'])) {
			$ex = explode('__APP', $r['info']);
			$ex = explode('_', $ex[1]);
			$ccApp[$ex[0]][] = $r;
			continue;
		}

		$ccOther[] = $r;
	}


	$TR_E = '<tr><td><td colspan="10" class="h35 b bottom">';

	$send .= '<table class="_stab small mar10">'.
		'<tr>'.
			'<th>'.
			'<th>key'.
			'<th>size'.
			'<th>time';

	foreach($ccGlobal as $n => $r)
		$send .= _debug_cache_tr($r, $n);

	foreach($ccApp as $app_id => $app) {
		$send .= $TR_E.'APP'.$app_id;
		foreach($app as $n => $r)
			$send .= _debug_cache_tr($r, $n);
	}

	if($ccOther) {
		$send .= $TR_E.'Другие приложения:';
		foreach($ccOther as $n => $r)
			$send .= _debug_cache_tr($r, $n);
	}

	$send .= '</table>';

	return $send;
}
function _debug_cache_tr($r, $n) {
	$t = time() - $r['creation_time'];
	if($t < 60)
		$t .= ' s';
	else
		$t = '<b class="clr1">'.floor($t / 60).'</b> m';
	return '<tr>'.
		'<td class="r clr1">'.($n + 1).
		'<td><a class="fs12" onclick=_cacheContentOpen("'.$r['info'].'")>'.$r['info'].'</a>'.
		'<td class="r">'._sumSpace($r['mem_size']).
		'<td class="r clr2">'.$t;

}
function _debug_cache_clear() {//очистка кеша
	$sql = "UPDATE `_setting`
			SET `v`=`v`+1
			WHERE `key`='SCRIPT'";
	query($sql);

	_cache_clear('all');

	_attachLinkRepair();
	_userImageRepair();
}

function _debug_sql() {//получение всех запросов
	global $SQL_TIME, $SQL_QUERY, $SQL_QUERY_T;

	$txt = '<table class="_stab small w100p mt5">';
	foreach($SQL_QUERY as $n => $r) {
		$t = $SQL_QUERY_T[$n];
		$txt .=
			'<tr class="over5">'.
				'<td class="w25 clr2 r top">'.($n+1).
				'<td class="top '.($t > 0.05 ? 'bg-fcc' : 'bg11').'">'.
					'<textarea class="w100p h20 bg0 fs12">'.$r.';</textarea>'.
				'<td class="w35 r top '.($t > 0.05 ? 'b clr5' : 'clr1').'">'._hide0($t);
	}
	$txt .= '</table>';

	return
	'<div class="bg-eee bor1 pad5 curP over1" onclick="$(this).next().slideToggle()">'.
		'sql <b>'.count($SQL_QUERY).'</b> '.
		'('.round($SQL_TIME, 3).')'.
		' :: '.
		'php '.round(microtime(true) - TIME, 3).
	'</div>'.
	'<div>'.$txt.'</div>';
}

function _debug_cookie_count() {
	$count = 0;
	if(!empty($_COOKIE))
		foreach($_COOKIE as $key => $val)
			if(strpos($key, 'debug') !== 0)
				$count++;
	return $count ? $count : '';
}
function _debug_cookie() {
//	<b>'._debug_cookie_count().'</b>
	$cookie = '';
	if(!empty($_COOKIE))
		foreach($_COOKIE as $key => $val)
			if(strpos($key, 'debug') !== 0)
				$cookie .= '<p><b>'.$key.'</b> '.$val;
	return $cookie;
}

function _debugLog($txt) {//запись лога в файл
	if(!DEBUG)
		return;

	$save = '';

	//Вставка даты и времени при первом запуске лога в одном выполнении скрипта
	if(!defined('DEBUG_LOG')) {
		$save .= "\n".strftime('%Y-%m-%d %H:%M-%S')."\n";
		define('DEBUG_LOG', true);
	}

	$db = debug_backtrace();
	$ex = explode('\\', $db[1]['file']);
	$file = $ex[count($ex) - 1];
	$save .= $file.':'.$db[1]['line'].'		'.$db[1]['function'];

	$save .= '		'.$txt."\n";

	$fp = fopen(APP_PATH.'/!/log.txt', 'a+');
	fwrite($fp, $save);
	fclose($fp);
}
function _debugPrint($msg) {//вывод текста, если debug
	if(!DEBUG)
		return '';

	return $msg;
}

function _count_update($app_id=APP_ID) {//обновление счётчиков
	//установка родительского диалога, если нужно
	$app = _app($app_id);
	if($app['pid'])
		$app_id = $app['pid'];

	//пересчёт количеств [54]
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=54";
	foreach(query_arr($sql) as $r)
		_element54update($r['id']);

	//пересчёт сумм [55]
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=55";
	foreach(query_arr($sql) as $r)
		_element55update($r['id']);

	//пересчёт сумм [27]
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=27";
	foreach(query_arr($sql) as $r)
		_element27update($r['id']);
}

function jsonDebugParam() {//возвращение дополнительных параметров json, если включен debug
	if(!@DEBUG)
		return array();

	$d = debug_backtrace();

	return array(
		'post' => $_POST,
		'link' => 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
		'file' => $d[1]['file'].':<b>'.$d[1]['line'].'</b>',
		'sql' => _debug_sql(),
	);
}

