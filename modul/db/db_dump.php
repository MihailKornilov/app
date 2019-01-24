<?php
/*
	Автоматическое сохранение базы
	http://nyandoma/app/modul/db/db_dump.php
*/


set_time_limit(300);

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once '../../modul/global/global.php';



//название запакованного файла
define('DUMP_NAME', MYSQLI_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql.gz');
//полный путь к файлу с названием
define('DUMP_FILE', APP_PATH.'/'.DUMP_NAME);



$cmd = '/usr/bin/mysqldump --password='.MYSQLI_PASS.
						 ' --user='.MYSQLI_USER.
						 ' --host='.MYSQLI_HOST.
						 ' '.MYSQLI_DATABASE.' | gzip -c > '.DUMP_FILE.' 2>&1';

exec($cmd, $out);



//чтение содержания архива
$file = fopen(DUMP_FILE, 'r');
$size = filesize(DUMP_FILE);//получение размера файла
$text = fread($file, $size);
fclose($file);

$from = 'global_n@dump';
$subject = MYSQLI_DATABASE.' dump'; //Тема писма
$boundary = '---'; //Разделитель

$headers = "From: $from\nReply-To: $from\n".
		   'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
$body =
	"--$boundary\n".
	"Content-type: text/html; charset='windows-1251'\n".
	"Content-Transfer-Encoding: quoted-printablenn".
	"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_FILE)."?=\n\n".

	//текст сообщения
	"Size: "._sumSpace($size)." bytes.\n".
	"Time: ".round(microtime(true) - TIME, 3)."\n".

	"--$boundary\n".
	"Content-Type: application/octet-stream;name==?windows-1251?B?".base64_encode(DUMP_FILE)."?=\n".
	"Content-Transfer-Encoding: base64\n".
	"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_FILE)."?=\n\n".
	chunk_split(base64_encode($text))."\n".
	'--'.$boundary ."--\n";

if(mail(CRON_MAIL, $subject, $body, $headers))
	unlink(DUMP_FILE);










function _dbDump() {
	define('INSERT_COUNT_MAX', 500); //записей в одном INSERT
	define('DUMP_NAME', MYSQLI_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql');//только название файла
	define('DUMP_FILE', APP_PATH.'/'.DUMP_NAME); //полный путь с названием
	define('DUMP_FILE_ZIP', DUMP_FILE.'.zip');   //полный путь запакованного файла с названием
	define('DUMP_NAME_ZIP', DUMP_NAME.'.zip');   //название запакованного файла

	//получение всех таблиц
	$spisok = array();
	$sql = "SHOW TABLES";
	$q = query($sql);
	while($r = mysqli_fetch_row($q))
		$spisok[] = $r[0];

	if(empty($spisok))
		return false;

	$fp = fopen(DUMP_FILE, 'w+');
	fwrite($fp, "                                                  \n\n");
	fwrite($fp, "SET NAMES `".MYSQLI_NAMES."`;\n\n");

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
