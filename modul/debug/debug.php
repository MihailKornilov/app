<?php
function _debug($i='') {
	if(!SA)
		return '';

	if($i == 'style')
		return '<link rel="stylesheet" type="text/css" href="modul/debug/debug'.MIN.'.css?'.SCRIPT.'" />';

	global $sqlQuery, $sqlTime;

	$goFace = SITE ? 'iframe' : 'site';
	$send =
		'<div id="debug-footer"'.(SITE ? ' style="bottom:0;position:fixed;width:100%"' : '').'>'.
			'<div class="w1000 mara center">'.
				'<a class="debug_toggle'.(DEBUG ? ' on' : '').'">'.(DEBUG ? 'От' : 'В').'ключить Debug</a> :: '.
				'<a id="cookie_clear">Очисить cookie</a> :: '.
				'<a id="count_update">Обновить суммы</a> :: '.
				'<a id="cache_clear">Очисить кэш ('.SCRIPT.')</a> :: '.
				'sql <b>'.count($sqlQuery).'</b> ('.round($sqlTime, 3).') :: '.
				'php '.round(microtime(true) - TIME, 3).' :: '.
				'js <em></em>'.
	   (LOCAL ? ' :: <a onclick="_faceGo(\''.$goFace.'\')">go '.$goFace.'</a>' : '').
			'</div>'.
		'</div>'.
		'<script src="modul/debug/debug'.MIN.'.js?'.SCRIPT.'"></script>';

	if(DEBUG) {
		$get = '';
		ksort($_GET);
		foreach($_GET as $k => $v)
			$get .= '<b>'.$k.'</b>='.$v.'<br />';
		$get .= '<textarea>http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'</textarea>';

		$send .=
		'<div id="_debug" class="'._dn(empty($_COOKIE['debug_show']), 'show').'">'.
			'<h1>+</h1>'.
			'<h2><div class="dmenu">'.
					'<a>cache</a>'.
					'<a>sql</a>'.// <b>'.count($sqlQuery).'</b> ('.round($sqlTime, 3).')
					'<a>cookie</a>'.
					'<a>get</a>'.
					'<a>ajax</a>'.
				'</div>'.
				'<div class="pg cache dn">'._debug_cache().'</div>'.
				'<ul class="pg sql dn">'.implode('', $sqlQuery).'</ul>'.
				'<div class="pg cookie dn">'.
					'<a onclick="debugCookieUpdate($(this))">Обновить</a>'.
					'<div class="mt10">'._debug_cookie().'</div>'.
				'</div>'.
				'<div class="pg get dn">'.$get.'</div>'.
				'<div class="pg ajax dn">&nbsp;</div>'.
			'</h2>'.
		'</div>';
	}

	return $send;
}

function _debug_cache() {//результат использования кеша
	$xi = xcache_info(XC_TYPE_VAR, 0);

	$size = round($xi['size'] / 1024 / 1024, 2);
	$avail = round($xi['avail'] / 1024 / 1024, 2);
	$busy = round($size - $avail, 2);

	$list = xcache_list(XC_TYPE_VAR, 0);
	$cc = count($list['cache_list']);

	$send =
		'<table class="_stab small mar10">'.
			'<tr><td class="">Общий кеш:'.
				'<td class="r"><b>'.$size.'</b> mb'.
			'<tr><td class="r color-ref">Занято:'.
				'<td class="color-ref r"><b>'.$busy.'</b> mb'.
				'<td class="grey">'.($cc ? $cc.' запис'._end($cc, 'ь', 'и', 'ей') : 'записей нет').
			'<tr><td class="r color-pay">Свободно:'.
				'<td class="color-pay r"><b>'.$avail.'</b> mb'.
		'</table>';

	if(!$cc)
		return $send;

	$ccGlobal = array();//глобальный кеш
	$ccApp = array();   //кеш по приложениям
	$ccOther = array();  //кеш из других приложений
	foreach($list['cache_list'] as $n => $r) {
		if(preg_match('/^__GLOBAL[a-z0-9_]{1,30}$/i', $r['name'])) {
			$ccGlobal[] = $r;
			continue;
		}

		if(preg_match('/^__APP[a-z0-9_]{1,30}$/i', $r['name'])) {
			$ex = explode('__APP', $r['name']);
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

	$send .= $TR_E.'Другие приложения:';
	foreach($ccOther as $n => $r)
		$send .= _debug_cache_tr($r, $n);

	$send .= '</table>';

	return $send;
}
function _debug_cache_tr($r, $n) {
	$t = time() - $r['ctime'];
	if($t < 60)
		$t .= ' s';
	else
		$t = '<b class="grey">'.floor($t / 60).'</b> m';
	return '<tr>'.
		'<td class="r grey">'.($n + 1).
		'<td><a class="fs12" onclick=_cacheContentOpen("'.$r['name'].'")>'.$r['name'].'</a>'.
		'<td class="r">'._sumSpace($r['size']).
		'<td class="r pale">'.$t;

}
function _debug_cache_clear() {//очистка кеша
	$sql = "UPDATE `_setting`
			SET `v`=`v`+1
			WHERE `key`='SCRIPT'";
	query($sql);

	_cache_clear('all');

	_userImageMove();
	_jsCache();
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


function jsonDebugParam() {//возвращение дополнительных параметров json, если включен debug
	if(!@DEBUG)
		return array();

	global $sqlQuery, $sqlTime;
	$d = debug_backtrace();
	return array(
		'post' => _pr($_POST),
		'link' => 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
		'php_time' => round(microtime(true) - TIME, 3),
		'sql_count' => count($sqlQuery),
		'sql_time' => round($sqlTime, 3),
		'sql' => implode('', $sqlQuery),
		'php_file' => $d[1]['file'],
		'php_line' => $d[1]['line']
	);
}
