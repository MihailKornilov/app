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


define('BR', '<br>');
define('MAIL_FROM', 'app@gim-system.ru');

//название запакованного файла
define('DUMP_NAME', MYSQLI_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql.gz');
//полный путь к файлу с названием
define('DUMP_FILE', APP_PATH.'/.tmp/'.DUMP_NAME);






//                          ---=== Создание дампа базы ===---
$cmd = '/usr/bin/mysqldump --password='.MYSQLI_PASS.
						 ' --user='.MYSQLI_USER.
						 ' --host='.MYSQLI_HOST.
						 ' '.MYSQLI_DATABASE.' | gzip -c > '.DUMP_FILE.' 2>&1';
exec($cmd, $out);
echo 'Archive name: '.DUMP_NAME.BR;



//                          ---=== Получение размера файла ===---
$file = fopen(DUMP_FILE, 'r');
$size = filesize(DUMP_FILE);
$text = fread($file, $size);
fclose($file);
echo 'Size: '._sumSpace($size).BR;


//                          ---=== Подключение PHPMailer ===---
require '../../inc/PHPMailer/src/PHPMailer.php';
require '../../inc/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;





//                          ---=== Отправка письма ===---
$mail = new PHPMailer(false);

try {
    $mail->setFrom(MAIL_FROM, 'global_n');
    $mail->addAddress(CRON_MAIL);   //адрес получателя

    $mail->addAttachment(DUMP_FILE);

    $mail->isHTML(false);
    $mail->Subject = MYSQLI_DATABASE.' - base dump '.strftime('%Y-%m-%d');  //заголовок
    $mail->Body    = DUMP_NAME."\n".
                    "Size: "._sumSpace($size)." bytes.\n".
                    "Time: ".round(microtime(true) - TIME, 3)."\n";

    $mail->send();

    unlink(DUMP_FILE);
    echo BR.'Sent OK.';
} catch (Exception $e) {
    mail(CRON_MAIL, 'BASE DUMP ERROR',
        "Mailer Error: {$mail->ErrorInfo}",
        "Content-type:text/html; Charset=utf-8\r\nFrom:".MAIL_FROM."\r\n");
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}


