<?php
switch(@$_POST['op']) {
	case 'app_enter'://���� � ���������� �� ������ ����������
		if(!SITE)
			jsonError();
		if(!$app_id = _num($_POST['app_id']))
			jsonError('������������ ID ����������');

		$sql = "SELECT *
				FROM `_user_app`
				WHERE `user_id`=".USER_ID."
				  AND `app_id`=".$app_id."
				LIMIT 1";
		if(!$ua = query_assoc($sql))
			jsonError('���������� �� ����������');

//		if(!$ua['access'])
//			jsonError('��� ������� � ����������');

		$sql = "UPDATE `_user_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);

		//������� ���� ���������� ��������� ����������
		$sql = "UPDATE `_user_app`
				SET `dtime_last`=CURRENT_TIMESTAMP
				WHERE `id`=".$ua['id'];
		query($sql);

		_cache('clear', '_auth');
		_cache('clear', '_pageCache');
		_cache('clear', '_userCache'.USER_ID);

		jsonSuccess();
		break;
	case 'sort':
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['table']))
			jsonError();

		$table = htmlspecialchars(trim($_POST['table']));
		$conn = 0;

		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysql_num_rows(query($sql)))
			if(mysql_num_rows(query($sql)))
				$conn = GLOBAL_MYSQL_CONNECT;
			else
				jsonError('������� �� ����������');

		$sort = explode(',', $_POST['ids']);
		if(empty($sort))
			jsonError('����������� �������� ��� ����������');

		for($n = 0; $n < count($sort); $n++)
			if(!preg_match(REGEXP_NUMERIC, $sort[$n]))
				jsonError('������������ ������������� ������ �� ���������');

		for($n = 0; $n < count($sort); $n++) {
			$sql = "UPDATE `".$table."` SET `sort`=".$n." WHERE `id`=".intval($sort[$n]);
			query($sql, $conn);
		}

//		_globalCacheClear();
//		_appJsValues();

		jsonSuccess();
		break;
}


