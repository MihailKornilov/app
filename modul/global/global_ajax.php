<?php
switch(@$_POST['op']) {
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


