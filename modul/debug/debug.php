<?php
function _debug($i='') {
	if(!SA)
		return '';

	if($i == 'style')
		return '<link rel="stylesheet" type="text/css" href="modul/debug/debug'.MIN.'.css?'.TIME.'" />';

	global $sqlQuery, $sqlTime;

	$goFace = SITE ? 'iframe' : 'site';
	$send =
		'<div id="debug-footer"'.(SITE ? ' style="bottom:0;position:fixed;width:100%"' : '').'>'.
			'<a class="debug_toggle'.(DEBUG ? ' on' : '').'">'.(DEBUG ? 'От' : 'В').'ключить Debug</a> :: '.
			'<a id="cookie_clear">Очисить cookie</a> :: '.
			'<a id="cache_clear">Очисить кэш ('.VERSION.')</a> :: '.
			'sql <b>'.count($sqlQuery).'</b> ('.round($sqlTime, 3).') :: '.
			'php '.round(microtime(true) - TIME, 3).' :: '.
			'js <em></em>'.
   (LOCAL ? ' :: <a onclick="_faceGo(\''.$goFace.'\')">go '.$goFace.'</a>' : '').
		'</div>'.
		'<script src="modul/debug/debug.js?'.TIME.'"></script>';

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
				'<div class="pg cache dn">'._debugCache().'</div>'.
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

function _debugCache() {//результат использования кеша
	global $CACHE_ARR;

	$send = '<table class="mar10 bg-fff collaps">'.
			'<tr>'.
				'<td class="bor-f0">'.
				'<td class="bor-f0 pad5 center b">act'.
				'<td class="bor-f0 pad5 center b">key'.
				'<td class="bor-f0 pad5 center b">'.
				'<td class="bor-f0 pad5 center b">func';
	foreach($CACHE_ARR as $n => $r) {
		$dbtCount = count($r['dbt']);
		$send .= '<tr>'.
			'<td class="grey r bor-f0 pad5">'.($n + 1).
			'<td class="bor-f0 pad5'._dn($r['act'] != 'set', 'bg-fcc').'">'.$r['act'].
			'<td class="bor-f0 pad5">'.$r['key'].
			'<td class="bor-f0 pad5 center">'.$dbtCount.
			'<td class="bor-f0 pad5">'.$r['dbt'][$dbtCount - 1]['function'];
	}

	$send .= '</table>';

	return $send;
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
		'sql' => utf8(implode('', $sqlQuery)),
		'php_file' => $d[1]['file'],
		'php_line' => $d[1]['line']
	);
}
