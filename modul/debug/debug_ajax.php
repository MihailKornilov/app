<?php

if(SA)
switch(@$_POST['op']) {
	case 'count_update'://пересчёт количеств, сумм и балансов
		_count_update();
		jsonSuccess();
		break;
	case 'cache_clear'://очистка xCache
		_debug_cache_clear();
		jsonSuccess();
		break;
	case 'cookie_clear':
		if(!empty($_COOKIE))
			foreach($_COOKIE as $key => $val)
				_cookieDel($key);
		jsonSuccess();
		break;

	case 'debug_sql':
		$nocache = _bool($_POST['nocache']);
		$explain = _bool($_POST['explain']);

		$sql = ($explain ? 'EXPLAIN ' : '').trim($_POST['query']);
		$q = DB1::query($sql);

		if($nocache)
			$sql = preg_replace('/SELECT/', 'SELECT NO_SQL_CACHE', $sql);

		if($explain) {
			$exp = '<table>';
			$n = 1;
			while($r = mysqli_fetch_assoc($q)) {
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
