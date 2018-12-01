<?php

_db_connect();

function _db_connect() {//подключение к базе данных
	global $SQL_CNN,    //соединение с базой
		   $SQL_TIME,   //общее время выполнения запросов
	       $SQL_QUERY,  //массив запросов
	       $SQL_QUERY_T;//массив времени выполнения по каждому запросу

	$SQL_TIME = 0;
	$SQL_QUERY = array();
	$SQL_QUERY_T = array();

	if(!$SQL_CNN = mysqli_connect(
		MYSQLI_HOST,
		MYSQLI_USER,
		MYSQLI_PASS,
		MYSQLI_DATABASE
	))
	    die('Can`t mysql connect: '.mysqli_connect_error());

	$sql = "SET NAMES '".MYSQLI_NAMES."'";
	mysqli_query($SQL_CNN, $sql);
}

function query($sql) {
	global $SQL_CNN, $SQL_TIME, $SQL_QUERY, $SQL_QUERY_T;

	$t = microtime(true);
	if(!$res = mysqli_query($SQL_CNN, $sql)) {
		$path = array();
		$DD = debug_backtrace();
		foreach($DD as $n => $r)
			$path[] = $r['function'].' - '.$r['file'].':'.$r['line'];
		$msg =  $sql."\n\n".
				mysqli_error($SQL_CNN)."\n".
				"---------------------------------\n".
				implode("\n", $path);

		$c = count($DD) - 1;
		if($DD[$c]['function'] == '_html')
			$msg = _br($msg);

		die($msg);
	};
	$t = microtime(true) - $t;

	$SQL_TIME += $t;
	$SQL_QUERY[] = $sql;
	$SQL_QUERY_T[] = round($t, 3);

	return $res;
}
function query_value($sql) {//запрос одного значения
	$q = query($sql);

	if(!$r = mysqli_fetch_row($q))
		return 0;
	if(preg_match(REGEXP_INTEGER, $r[0]))
		return $r[0] * 1;

	return $r[0];
}
function query_arr($sql, $key='id') {//массив по ключу
	$q = query($sql);

	$send = array();
	while($r = mysqli_fetch_assoc($q))
		$send[$r[$key]] = $r;

	return $send;
}
function query_array($sql) {//последовательный массив без ключей
	$q = query($sql);

	$send = array();
	while($r = mysqli_fetch_assoc($q))
		$send[] = $r;

	return $send;
}
function query_ass($sql) {//ассоциативный массив из двух значений: a => b
	$q = query($sql);

	$send = array();
	while($r = mysqli_fetch_row($q))
		$send[$r[0]] = preg_match(REGEXP_NUMERIC, $r[1]) ? $r[1] * 1 : $r[1];

	return $send;
}
function query_assoc($sql) {//ассоциативный массив одной записи
	$q = query($sql);
	if(!$r = mysqli_fetch_assoc($q))
		return array();
	return $r;
}
function query_ids($sql) {//идентификаторы через запятую
	$q = query($sql);

	$send = array();
	while($r = mysqli_fetch_row($q))
		$send[] = $r[0];

	return !$send ? 0 : implode(',', array_unique($send));
}
function query_id($sql) {//получение id внесённой записи
	global $SQL_CNN;

	query($sql);

	return _num(mysqli_insert_id($SQL_CNN));
}
function query_insert_id($tab) {//id последнего внесённого элемента
	$sql = "SELECT `id` FROM `".$tab."` ORDER BY `id` DESC LIMIT 1";
	return query_value($sql);
}



function _table($id=false) {//таблицы в базе с соответствующими идентификаторами
	$tab = array(
		 1 =>  '_app',
		 2 =>  '_block',
		 3 =>  '_dialog',
		 4 =>  '_element_group',
		 5 =>  '_element',
		 6 =>  '_element_func',
		 17 => '_element_format',
		 18 => '_element_hint',
		 19 => '_element_rule_name',
		 20 => '_element_rule_use',
		 7 =>  '_history',
		 8 =>  '_image',
		 9 =>  '_image_server',
		10 =>  '_page',
		11 =>  '_spisok',
		12 =>  '_user',
		14 =>  '_user_auth',
		15 =>  '_user_spisok_filter',
		16 =>  '_note'
	);

	if($id === false)
		return $tab;
	if(!$id = _num($id))
		return '';
	if(empty($tab[$id]))
		return '';

	return $tab[$id];
}
function _queryCol($DLG) {//получение колонок, для которых будет происходить запрос
/*
	Диалог предварительно должен быть проверен:
		* использует таблицу
        * содержит колонки, по которым будет получение данных
*/

	$key = 'QUERY_COL_'.$DLG['id'];

	if(defined($key))
		return constant($key);

	$field = array("`t1`.`id`");
	$field[] = _queryColReq($DLG, 'dialog_id');
	$field[] = _queryColReq($DLG, 'block_id');
	$field[] = _queryColReq($DLG, 'element_id');
	$field[] = _queryColReq($DLG, 'parent_id');
	$field[] = _queryColReq($DLG, 'num');
	$field[] = _queryColReq($DLG, 'dtime_add');
	$field[] = _queryColReq($DLG, 'user_id_add');

	//id диалога, который использовался при создании записи
	$field[] = $DLG['id'].' `dialog_id_use`';

	foreach($DLG['cmp'] as $cmp)
		$field[] = _queryColReq($DLG, _elemCol($cmp));

	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		foreach($PAR['cmp'] as $cmp)
			$field[] = _queryColReq($DLG, _elemCol($cmp));
	}

	$field = array_diff($field, array(''));

	define($key, implode(',', $field));

	return constant($key);
}
function _queryColReq($DLG, $col) {//добавление обязательных колонок
	//колонка не используется ни в одной таблице
	if(!$tn = _queryTN($DLG, $col))
		return '';

	//сначала проверяется использование в родительской таблице
	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		if(isset($PAR['field1'][$col]))
			return "`".$tn."`.`".$col."`";
	}

	if(isset($DLG['field1'][$col]))
		return "`".$tn."`.`".$col."`";

	return '';
}
function _queryFrom($DLG) {//составление таблиц для запроса
/*
	Диалог предварительно должен быть проверен и использовать таблицу
*/
	$key = 'QUERY_FROM_'.$DLG['id'];

	if(defined($key))
		return constant($key);

	$send = "`".$DLG['table_name_1']."` `t1`";

	//если присутствует родительский диалог, основной становится таблица родителя
	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		$send = "`".$PAR['table_name_1']."` `t1` /* Таблица-родитель */";
		if($PAR['table_1'] != $DLG['table_1'])
			$send .= ",`".$DLG['table_name_1']."` `t2`";
	}


	define($key, $send);

	return $send;
}
function _queryWhere($DLG) {//составление условий для запроса
	$key = 'QUERY_WHERE_'.$DLG['id'];

	if(defined($key))
		return constant($key);


	$send = array();

	//если присутствует родительский диалог и разные таблицы, происходит связка через `cnn_id`
	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		if($PAR['table_1'] != $DLG['table_1'])
			if(isset($PAR['field1']['cnn_id']))
				$send[] = "`t1`.`cnn_id`=`t2`.`id`";
			elseif(isset($DLG['field1']['cnn_id']))
				$send[] = "`t2`.`cnn_id`=`t1`.`id`";
	}

	if($tn = _queryTN($DLG, 'deleted'))
		$send[] = "!`".$tn."`.`deleted`";
	if($tn = _queryTN($DLG, 'app_id'))
		if($DLG['table_name_1'] != '_element')
			$send[] = "`".$tn."`.`app_id`=".APP_ID;

	$send[] = _queryWhereDialogId($DLG);

	$send = array_diff($send, array(''));

	if(!$send = implode(' AND ', $send))
		$send = "`t1`.`id`";

	define($key, $send);

	return $send;
}
function _queryTN($DLG, $name, $full=false) {//получение имени таблицы для определённой колонки
	// $full - возвращать полное название таблицы
	if(!$name)
		return '';

	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		if(isset($PAR['field1'][$name]))
			return $full ? $PAR['table_name_1'] : 't1';
		elseif(isset($DLG['field1'][$name]))
			return $full ? $DLG['table_name_1'] : 't2';
	}

	if(isset($DLG['field1'][$name]))
		return $full ? $DLG['table_name_1'] : 't1';

	return '';
}
function _queryWhereDialogId($DLG) {//получение условия по `dialog_id`
	if($DLG['table_name_1'] == '_element')
		return '';
	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		if($PAR['table_name_1'] == '_element')
			return '';
	}

	if(!$tn = _queryTN($DLG, 'dialog_id'))
		return '';

	$dialog_id = $parent_id ? $parent_id : $DLG['id'];
	return "`".$tn."`.`dialog_id`=".$dialog_id;
}





function _dbDump() {
	define('INSERT_COUNT_MAX', 500); //записей в одном INSERT
	define('DUMP_NAME', GLOBAL_MYSQL_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql');//только название файла
	define('DUMP_FILE', APP_PATH.'/'.DUMP_NAME); //полный путь с названием
	define('DUMP_FILE_ZIP', DUMP_FILE.'.zip');   //полный путь запакованного файла с названием
	define('DUMP_NAME_ZIP', DUMP_NAME.'.zip');   //название запакованного файла

	$spisok = array();
	$sql = "SHOW TABLES";
	$q = query($sql);
	while($r = mysqli_fetch_row($q))
		$spisok[] = $r[0];

	if(empty($spisok))
		return false;

	$fp = fopen(DUMP_FILE, 'w+');
	fwrite($fp, "                                                  \n\n");
	fwrite($fp, "SET NAMES `cp1251`;\n\n");

	foreach($spisok as $r)
		_dbDumpTable($fp, $r);

	fclose($fp);

	_dbDumpTime();
	_dbDumpZip();
	_dbDumpMail();

	unlink(DUMP_FILE);

	return true;
}
function _dbDumpTable($fp, $table) {
	fwrite($fp, "DROP TABLE IF EXISTS `".$table."`;\n");

	$sql = "SHOW CREATE TABLE `".$table."`";
	$q = query($sql);
	$r = mysqli_fetch_row($q);
	fwrite($fp, $r[1].";\n");

	//получение форматов столбцов
	$sql = "DESCRIBE `".$table."`";
	$q = query($sql);
	$desc = array();
	while($r = mysqli_fetch_assoc($q))
		array_push($desc, $r['Type']);

	$values = array();
	$sql = "SELECT * FROM `".$table."`";
	$q = query($sql);
	$count = 0;
	while($row = mysqli_fetch_row($q)) {
		$count++;

		$cols = array();
		foreach($row as $n => $col)
			switch($desc[$n]) {
				case 'tinyint(3) unsigned': $cols[] = intval($col); break;
				case 'smallint(5) unsigned': $cols[] = intval($col); break;
				case 'int(10) unsigned': $cols[] = intval($col); break;
				case 'decimal(11,2)': $cols[] = round($col, 2); break;
				case 'decimal(11,2) unsigned': $cols[] = round($col, 2); break;
				default: $cols[] = '\'' . addslashes($col) . '\'';
			}

		$values[] = '('.implode(',', $cols).')';

		if($count >= INSERT_COUNT_MAX) {
			$count = _dbDumpInsert($fp, $table, $values);
			$values = array();
		}
	}
	_dbDumpInsert($fp, $table, $values);
	fwrite($fp, "\n\n\n");
}
function _dbDumpInsert($fp, $table, $values) {//внесение блока INSERT в файл
	if(empty($values))
		return 0;

	$insert = "INSERT INTO `".$table."` VALUES \n".implode(",\n", $values).";\n";
	fwrite($fp, $insert);
	return 0;
}
function _dbDumpTime() {//вставка даты и времени выполнения в начало дампа
	$fp = fopen(DUMP_FILE, 'r+');
	fwrite($fp, "#Dump created ".curTime()."\n");
	fwrite($fp, "#Time: ".round(microtime(true) - TIME, 3)."\n\n");
	fclose($fp);
	return true;
}
function _dbDumpZip() {//создание архива базы
	$zip = new ZipArchive();
	if($zip->open(DUMP_FILE_ZIP, ZIPARCHIVE::CREATE) !== true) {
	    echo 'Error while creating archive file';
	    return false;
	}
	$zip->addFile(DUMP_FILE, DUMP_NAME);
	$zip->close();

	return true;
}
function _dbDumpMail() {//отправка архива на почту
	//чтение содержания архива
	$file = fopen(DUMP_FILE_ZIP, 'r');
	$size = filesize(DUMP_FILE_ZIP);//получение размера файла
	$text = fread($file, $size);
	fclose($file);

	$from = 'global@dump';
	$subject = GLOBAL_MYSQL_DATABASE.' dump'; //Тема
	$boundary = '---'; //Разделитель

	$headers = "From: $from\nReply-To: $from\n".
			   'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
	$body =
		"--$boundary\n".
		"Content-type: text/html; charset='windows-1251'\n".
		"Content-Transfer-Encoding: quoted-printablenn".
		"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n\n".

		//текст сообщения
		"Size: "._sumSpace($size)." bytes.\n".
		"Time: ".round(microtime(true) - TIME, 3)."\n".

		"--$boundary\n".
		"Content-Type: application/octet-stream;name==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n".
		"Content-Transfer-Encoding: base64\n".
		"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n\n".
		chunk_split(base64_encode($text))."\n".
		'--'.$boundary ."--\n";
	if(mail(CRON_MAIL, $subject, $body, $headers))
		unlink(DUMP_FILE_ZIP);
}
