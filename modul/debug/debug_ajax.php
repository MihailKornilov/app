<?php

if(SA)
switch(@$_POST['op']) {
	case 'cache_clear'://очистка xCache
		_cache('clear', '_auth');             //авторизация
		_cache('clear', '_userCache'.USER_ID);//текущий пользователь
		_cache('clear', '_pageCache');        //страницы
		_cache('clear', '_imageServerCache'); //серверы изображений

		//диалоговые окна
		$sql = "SELECT `id`
				FROM `_dialog`
				WHERE `app_id` IN(0,".APP_ID.")";
		foreach(query_arr($sql) as $r)
			_cache('clear', '_dialogQuery'.$r['id']);

		//страницы
		$sql = "SELECT `id`
				FROM `_page`
				WHERE `app_id` IN(0,".APP_ID.")";
		foreach(query_arr($sql) as $r)
			_cache('clear', 'page_'.$r['id']);

		jsonSuccess();
		break;
	case 'cookie_clear':
		if(!empty($_COOKIE))
			foreach($_COOKIE as $key => $val)
				setcookie($key, '', time() - 3600, '/');
		jsonSuccess();
		break;

	case 'debug_sql':
		$nocache = _bool($_POST['nocache']);
		$explain = _bool($_POST['explain']);

		$sql = ($explain ? 'EXPLAIN ' : '').trim($_POST['query']);
		$q = query($sql);

		if($nocache)
			$sql = preg_replace('/SELECT/', 'SELECT NO_SQL_CACHE', $sql);

		if($explain) {
			$exp = '<table>';
			$n = 1;
			while($r = mysql_fetch_assoc($q)) {
				$exp .= '<tr>';
				if($n++ == 1) {
					foreach($r as $i => $v)
						$exp .= '<th>'.$i;
					$exp .= '<tr>';
				}
				foreach($r as $v)
					$exp .= '<td>'.$v;
			}
			$exp .= '<table>';
			$send['exp'] = $exp;
		}

		$send['query'] = $sql;
		$send['html'] =
			//'rows: <b>'.$q['rows'].'</b>, '.
			'time: '.$q['time'];
		jsonSuccess($send);
		break;
	case 'debug_cookie':
		$send['html'] = _debug_cookie();
		jsonSuccess($send);
		break;
}
