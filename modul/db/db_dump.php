<?php
/*
	Автоматическое сохранение базы
	http://nyandoma/app/modul/db/db_dump.php

	Команда:
	/usr/bin/wget -O /dev/null -q http://nyandoma.ru/app/modul/db/db_dump.php?key=jern32n32Md93J83hs
*/


if(@$_GET['key'] != 'jern32n32Md93J83hs')
	exit;


set_time_limit(300);

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once '../../modul/global/global.php';

//название запакованного файла
define('DUMP_NAME', MYSQLI_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql.gz');
//полный путь к файлу с названием
define('DUMP_FILE', APP_PATH.'/.tmp/'.DUMP_NAME);



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
$subject = MYSQLI_DATABASE.' - дамп базы '.FullData(curTime()); //Тема писма
$boundary = '---'; //Разделитель

$headers = "From: $from\nReply-To: $from\n".
		   'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
$body =
	"--$boundary\n".
	"Content-type: text/html; charset='windows-1251'\n".
	"Content-Transfer-Encoding: quoted-printablenn".
	"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME)."?=\n\n".

	//текст сообщения
	"Size: "._sumSpace($size)." bytes.\n".
	"Time: ".round(microtime(true) - TIME, 3)."\n".

	"--$boundary\n".
	"Content-Type: application/octet-stream;name==?windows-1251?B?".base64_encode(DUMP_NAME)."?=\n".
	"Content-Transfer-Encoding: base64\n".
	"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME)."?=\n\n".
	chunk_split(base64_encode($text))."\n".
	'--'.$boundary ."--\n";

if(mail(CRON_MAIL, $subject, $body, $headers))
	unlink(DUMP_FILE);



