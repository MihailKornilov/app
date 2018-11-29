<?php
switch(@$_POST['op']) {
	case 'app_enter'://вход в приложение из списка приложений
		if(!SITE)
			jsonError('Вход может осуществляться только на сайт');
		if(!$app_id = _num($_POST['app_id']))
			jsonError('Некорректный ID приложения');
		if(!$ua = _userApp($app_id))
			jsonError('Пользователя не существует');

		$sql = "UPDATE `_user_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);

		$sql = "UPDATE `_user`
				SET `app_id_last`=".$app_id."
				WHERE `id`=".USER_ID;
		query($sql);

		_cache_clear( 'AUTH_'.CODE, 1);
		_cache_clear( 'page');

		_auth();

		jsonSuccess();
		break;
	case 'sort':
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['table']))
			jsonError();

		$table = htmlspecialchars(trim($_POST['table']));
		$conn = 0;

		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysqli_num_rows(query($sql)))
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

		jsonSuccess();
		break;
}


