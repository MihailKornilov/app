<?php

if(SITE)
switch(@$_POST['op']) {
	case 'app_enter':
		if(!$app_id = _num($_POST['app_id']))
			jsonError('������������ ID ����������');

		$sql = "SELECT *
				FROM `_vkuser_app`
				WHERE `viewer_id`=".VIEWER_ID."
				  AND `app_id`=".$app_id."
				LIMIT 1";
		if(!$va = query_assoc($sql))
			jsonError('���������� �� ����������');

		if(!$va['worker'])
			jsonError('��� ������� � ����������');

		$sql = "UPDATE `app_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);
		
		_cache($code, 'clear');

		jsonSuccess();
		break;
}


