<?php

if(SITE)
switch(@$_POST['op']) {
	case 'app_enter':
		if(!$app_id = _num($_POST['app_id']))
			jsonError('Некорректный ID приложения');

		$sql = "SELECT *
				FROM `_vkuser_app`
				WHERE `viewer_id`=".VIEWER_ID."
				  AND `app_id`=".$app_id."
				LIMIT 1";
		if(!$va = query_assoc($sql))
			jsonError('Приложения не существует');

		if(!$va['worker'])
			jsonError('Нет доступа в приложение');

		$sql = "UPDATE `app_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);
		
		_cache($code, 'clear');

		jsonSuccess();
		break;
}


