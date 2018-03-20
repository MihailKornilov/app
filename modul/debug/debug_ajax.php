<?php

if(SA)
switch(@$_POST['op']) {
	case 'count_update'://�������� ���������, ���� � ��������

		//�������� ��������� [54]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=54";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd54($r);

		//�������� ���� [55]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=55";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd55($r);

		//�������� ���� [27]
		$sql = "SELECT *
				FROM `_element`
				WHERE `dialog_id`=27";
		foreach(query_arr($sql) as $r)
			_spisokUnitUpd27($r);

		jsonSuccess();
		break;
	case 'cache_clear'://������� xCache
		_cache('clear', '_auth');             //�����������
		_cache('clear', '_userCache'.USER_ID);//������� ������������
		_cache('clear', '_pageCache');        //��������
		_cache('clear', '_imageServerCache'); //������� �����������
		_spisokFilter('cache_clear');                 //������� ��������

		//���������� ����
		$sql = "SELECT `id`
				FROM `_dialog`
				WHERE `app_id` IN(0,".APP_ID.")";
		$dialog_ids = query_ids($sql);
		foreach(_ids($dialog_ids, 1) as $id)
			_cache('clear', '_dialogQuery'.$id);

		//�����, ������� ������������ �� � ��������
		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='dialog'
				  AND `obj_id` IN (".$dialog_ids.")";
		$block_ids = query_ids($sql);

		//������, ������� ����������� � ��������
		$sql = "SELECT `block_id`
				FROM `_element`
				WHERE `dialog_id` IN (14,59)
				  AND `block_id` IN (".$block_ids.")";
		if($spisok_ids = query_ids($sql))
			foreach(_ids($spisok_ids, 1) as $id)
				_cache('clear', 'spisok_'.$id);

		//��������
		$sql = "SELECT `id`
				FROM `_page`
				WHERE `app_id` IN(0,".APP_ID.")";
		$page_ids = query_ids($sql);
		foreach(_ids($page_ids, 1) as $id)
			_cache('clear', 'page_'.$id);

		//�����, ������� ������������ �� ���������
		$sql = "SELECT `id`
				FROM `_block`
				WHERE `obj_name`='page'
				  AND `obj_id` IN (".$page_ids.")";
		$block_ids = query_ids($sql);

		//������, ������� ����������� �� ���������
		$sql = "SELECT `block_id`
				FROM `_element`
				WHERE `dialog_id`=14
				  AND `block_id` IN (".$block_ids.")";
		if($spisok_ids = query_ids($sql))
			foreach(_ids($spisok_ids, 1) as $id)
				_cache('clear', 'spisok_'.$id);

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
