<?php
switch(@$_POST['op']) {
	case 'app_enter'://���� � ���������� �� ������ ����������
		if(!SITE)
			jsonError();
		if(!$app_id = _num($_POST['app_id']))
			jsonError('������������ ID ����������');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".$app_id."
				  AND `dialog_id`=1011
				  AND `connect_1`=".USER_ID."
				LIMIT 1";
		if(!$ua = query_assoc($sql))
			jsonError('���������� �� ����������');

//		if(!$ua['access'])
//			jsonError('��� ������� � ����������');

		$sql = "UPDATE `_user_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);

		_cache('clear', '_auth');
		_cache('clear', '_pageCache');
		_cache('clear', '_userCache'.USER_ID);

		_auth();

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


