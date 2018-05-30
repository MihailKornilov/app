<?php

if(SA)
switch(@$_POST['op']) {
	case 'count_update'://пересчёт количеств, сумм и балансов

		//пересчёт количеств [54]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=54";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd54($r);

		//пересчёт сумм [55]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=55";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd55($r);

		//пересчёт сумм [27]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=27";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd27($r);

		jsonSuccess();
		break;
	case 'cache_clear'://очистка xCache
		$sql = "UPDATE `_setting`
				SET `v`=`v`+1
				WHERE `key`='SCRIPT'";
		query($sql);

		_cache_clear('all');

		_userImageMove();
		_jsCache();

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
