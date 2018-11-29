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
}

function query($sql) {
	global $SQL_CNN, $SQL_TIME, $SQL_QUERY, $SQL_QUERY_T;

	$t = microtime(true);
	$res = mysqli_query($SQL_CNN, $sql);
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
