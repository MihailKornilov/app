<?php
switch(@$_POST['op']) {
	case 'app_enter'://вход в приложение из списка приложений
		if(!SITE)
			jsonError();
		if(!$app_id = _num($_POST['app_id']))
			jsonError('Некорректный ID приложения');

		$sql = "SELECT *
				FROM `_vkuser_app`
				WHERE `viewer_id`=".USER_ID."
				  AND `app_id`=".$app_id."
				LIMIT 1";
		if(!$va = query_assoc($sql))
			jsonError('Приложения не существует');

		if(!$va['worker'])
			jsonError('Нет доступа в приложение');

		$sql = "UPDATE `_vkuser_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);

		//отметка даты последнего посещения приложения
		$sql = "UPDATE `_vkuser_app`
				SET `last_seen`=CURRENT_TIMESTAMP
				WHERE `id`=".$va['id'];
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
				jsonError('Таблицы не существует');

		$sort = explode(',', $_POST['ids']);
		if(empty($sort))
			jsonError('Отсутствуют элементы для сортировки');

		for($n = 0; $n < count($sort); $n++)
			if(!preg_match(REGEXP_NUMERIC, $sort[$n]))
				jsonError('Некорректный идентификатор одного из элементов');

		for($n = 0; $n < count($sort); $n++) {
			$sql = "UPDATE `".$table."` SET `sort`=".$n." WHERE `id`=".intval($sort[$n]);
			query($sql, $conn);
		}

//		_globalCacheClear();
//		_appJsValues();

		jsonSuccess();
		break;
}


