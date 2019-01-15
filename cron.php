<?php
define('CRON_KEY', @$_GET['cron_key']);
if(CRON_KEY != 'jern32n32Md93J83hs')
	exit;

set_time_limit(300);

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'modul/global/global.php';

define('BR', '<br>');
//define('BR', "\n");
define('USER_ID', 0);
define('CRON_MAIL', 'mihan_k@mail.ru');
define('CRON_TIME_CUR', strftime('%Y-%m-%d %H:%M'));




_cronStart();
_cronTask();




function _cronStart() {//просмотр и выполнение всех задач - глобальная функция
	if(!empty($_GET['task_id']))
		return;

	$sql = "SELECT *
			FROM `_cron`
			ORDER BY `app_id`,`id`";
	if(!$arr = query_arr($sql))
		exit;


	$send = '<table style="border-spacing:10px">';

	foreach($arr as $r) {
		if(!_cronTime($r))
			continue;

		$link = 'http://'.DOMAIN.APP_HTML.'/cron.php?cron_key='.CRON_KEY.'&task_id='.$r['id'];
		if(!$content = file_get_contents($link))
			die('cnt yok');

		$send .= '<tr>'.
			'<td>id'.$r['id'].
			'<td>src_id='.$r['src_spisok'].
			'<td>'.$link.
			'<td>'.$content.
			'<td>';
	}

	$send .= '</table>';


	echo
	$send.
	'time='.round(microtime(true) - TIME, 3);
}
function _cronTask() {//выполнение конкретной задачи
	if(!$task_id = _num(@$_GET['task_id']))
		return;

	$sql = "SELECT *
			FROM `_cron`
			WHERE `id`=".$task_id;
	if(!$task = query_assoc($sql))
		die('task id'.$task_id.' not exists');

	define('APP_ID', $task['app_id']);

	//id исходного списка, из которого будут браться данные
	if(!$dlg_id = $task['src_spisok'])
		die('src_id not exists');
	if(!$SRC = _dialogQuery($dlg_id))
		die('SRC dialog_id='.$dlg_id.' not exists');

	//исходные данные для внесения
	$sql = "SELECT "._queryCol($SRC)."
			FROM  "._queryFrom($SRC)."
			WHERE "._queryWhere($SRC).
				_40cond(array(), $task['src_prm']);
	if(!$SRC_ARR = query_arr($sql))
		die('no data for insert');
		
	//id списка-получателя
	if(!$dlg_id = $task['dst_spisok'])
		die('dst_id not exists');
	if(!$DST = _dialogQuery($dlg_id))
		die('DST dialog_id='.$dlg_id.' not exists');
	if(!$dstTab = $DST['table_name_1'])
		die('DST table not exists');

	$ass = PHP12_cron_dst_prm_ass($task['dst_prm']);
	
	foreach($SRC_ARR as $r) {
		$cols = array();
		$data = array();
		foreach($ass as $dst_id => $src_id) {
			//элемент получатель
			if(!$dstEl = @$DST['cmp'][$dst_id])
				continue;
			if(!$dstCol = $dstEl['col'])
				continue;

			//исходный элемент
			if($src_id) {
				if(!$srcEl = @$SRC['cmp'][$src_id])
					continue;
				if(!$col = $srcEl['col'])
					continue;
				if(!isset($r[$col]))
					continue;
				$v = $r[$col];
			} else {
				//значения по умолчанию, которые не были назначены элементу-получателю
				$is_def = true;
				switch($dstEl['dialog_id']) {
					//Месяц и год
					case 39: $v = strftime('%Y-%m'); break;
					default: $is_def = false;
				}
				if(!$is_def)
					continue;
			}

			$cols[] = '`'.$dstCol.'`';
			$data[] = "'".addslashes($v)."'";
		}

		//внесение записи
		$sql = "INSERT INTO `".$dstTab."` (
					`app_id`,
					`dialog_id`,
					".implode(',', $cols)."
				) VALUES (
					".APP_ID.",
					".$DST['id'].",
					".implode(',', $data)."
				)";
		$unit_id = query_id($sql);
		$unit = _spisokUnitQuery($DST, $unit_id);

		//обновление счётчиков
		$DST['act'] = 1;
		_SUN_AFTER($DST, $unit);
	}
	
	
	echo
	'all='.count($SRC_ARR).
	BR.
	'time='.round(microtime(true) - TIME, 3);
}
function _cronTime($r) {//время, установленное в задании. Будет разрешено выполнение, если время подходящее
	//месяц
	if($mon = $r['time_mon'])
		if($mon != _num(strftime('%m')))
			return false;

	//день недели
	if($week = $r['time_week']) {
		if(!$w = strftime('%w'))
			$w = 7;
		if($week != $w)
			return false;

	}

	//число месяца
	if($day = $r['time_day'])
		if($day != _num(strftime('%d')))
			return false;

	//час
	if(($hour = $r['time_hour']) < 24)
		if($hour != _num(strftime('%H')))
			return false;

	//минута
	if(($min = $r['time_min']) < 60)
		if($min != _num(strftime('%M')))
			return false;

	return true;
}








/*
function zp_accrual() {//начисление ставки сотрудникам
	$year = strftime('%Y');
	$mon = strftime('%m');
	$day = intval(strftime('%d'));
	$w = date('w', time()); //день недели
	$week = !$w ? 7 : $w;   //если день недели 0 - это воскресенье
	$about = 'Ставка за '._monthDef($mon).' '.$year;

	$send = '';

	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `salary_rate_sum`>0";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$insert = 0;
		switch($r['salary_rate_period']) {
			case 1:	$insert = $r['salary_rate_day'] == $day; break;
			case 2:	$insert = $r['salary_rate_day'] == $week; break;
			case 3: $insert = 1; break;
		}
		if(!$insert)
			continue;
		$sql = "INSERT INTO `_salary_accrual` (
					`app_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				) VALUES (
					".APP_ID.",
					".$r['viewer_id'].",
					".$r['salary_rate_sum'].",
					'".$about."',
					".$year.",
					".$mon."
				)";
		query($sql);

		_balans(array(
			'action_id' => 19,
			'worker_id' => $r['viewer_id'],
			'sum' => $r['salary_rate_sum'],
			'about' => $about
		));

		_history(array(
			'type_id' => 46,
			'worker_id' => $r['viewer_id'],
			'v1' => _cena($r['salary_rate_sum']),
			'v2' => $about
		));

		$send .= 'Начисление ставки для сотрудника '._viewer($r['viewer_id'], 'viewer_name').' в сумме '._cena($r['salary_rate_sum']).' руб.'.BR;
	}
	return $send;
}
function zp_image_attach($worker_id) {//начисление зп сотруднику за загруженные картинки (пока только Даше)
	if(APP_ID != 2031819)
		return '';

	$year = strftime('%Y');
	$mon = strftime('%m');

	//позавчера
	$beforeYesterday = strftime('%Y-%m-%d', time() - 3600 * 48);

	//вчера
	$yesterday = strftime('%Y-%m-%d', time() - 3600 * 24);

	$sql = "SELECT COUNT(DISTINCT `unit_id`)
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id_add`=".$worker_id."
			  AND !`deleted`
			  AND `unit_name`='tovar'
			  AND `dtime_add` LIKE '".$yesterday." %'";
	if(!$count = query_value($sql))
		return '';

	$sql = "SELECT COUNT(DISTINCT `unit_id`)
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id_add`=".$worker_id."
			  AND !`deleted`
			  AND `unit_name`='tovar'
			  AND `dtime_add` LIKE '".$beforeYesterday." %'";
	$countBYD = query_value($sql);//количество изображений за позавчера

	$about = 'Прикреплены изображения к '.$count.' товар'._end($count, 'у', 'ам');
	$sum = round($count * ($countBYD >= 100 ? 1.5 : 1)); //1-1.5 рубль за один товар

	$worker_id = $worker_id == 418627813 ? 139400639 : $worker_id;//старая страница Даши

	$sql = "INSERT INTO `_salary_accrual` (
				`app_id`,
				`worker_id`,
				`sum`,
				`about`,
				`year`,
				`mon`
			) VALUES (
				".APP_ID.",
				".$worker_id.",
				".$sum.",
				'".$about."',
				".$year.",
				".$mon."
			)";
	query($sql);

	_balans(array(
		'action_id' => 19,
		'worker_id' => $worker_id,
		'sum' => $sum,
		'about' => $about
	));

	return BR.$about.BR;
}
function smena_zp_accrual() {//начисление ставки сотрудникам за смены
	if(APP_ID != 3978722)//пока только для евроокон
		return '';

	$day = _num(strftime('%d'));
	if($day != 1)//начисление только 1 числа каждого месяца
		return '';

	$year = _num(strftime('%Y'));
	$mon = _num(strftime('%m'));
	if(!--$mon) {
		$year--;
		$mon = 12;
	}

	$YM = $year.'-'.($mon < 10 ? 0 : '').$mon;

	//текущий бюджет за месяц по сменам
	$sql = "SELECT `value`
			FROM `_setup_global`
			WHERE `app_id`=".APP_ID."
			  AND `key`='SMENA_MON_BUDGET'
			LIMIT 1";
	$budget = _num(query_value($sql));

	$sql = "SELECT COUNT(`id`)
			FROM `_smena`
			WHERE `app_id`=".APP_ID."
			  AND `started`
			  AND `dtime_add` LIKE '".$YM."%'";
	if(!$smena_count = _num(query_value($sql)))
		return '';

	//сумма за одну смену
	$smena_cena = $budget / $smena_count;

	//сотрудники и количество смен по каждому
	$sql = "SELECT
				`worker_id`,
				COUNT(`id`)
			FROM `_smena`
			WHERE `app_id`=".APP_ID."
			  AND `started`
			  AND `dtime_add` LIKE '".$YM."%'
			GROUP BY `worker_id`";
	$ass = query_ass($sql);

	$send = '';
	foreach($ass as $worker_id => $c) {
		$sum = round($smena_cena * $c);
		$about = 'ставка: '.$c.' смен'._end($c, 'а', 'ы', '').' за '._monthDef($mon).' '.$year;
		$sql = "INSERT INTO `_salary_accrual` (
					`app_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				) VALUES (
					".APP_ID.",
					".$worker_id.",
					".$sum.",
					'".$about."',
					".$year.",
					".$mon."
				)";
		query($sql);

		_balans(array(
			'action_id' => 19,
			'worker_id' => $worker_id,
			'sum' => $sum,
			'about' => $about
		));

		_history(array(
			'type_id' => 46,
			'worker_id' => $worker_id,
			'v1' => $sum,
			'v2' => $about
		));

		$send .= 'Начисление ставки за смены сотруднику '._viewer($worker_id, 'viewer_name').' в сумме '.$sum.' руб.'.BR;
	}

	return $send;
}

function _cronMailSend() {
	if($content = ob_get_contents()) {
		$content .=
			BR.BR.'----'.BR.
			'Время выполнения: '.round(microtime(true) - TIME, 3);
		mail(CRON_MAIL, 'Cron', $content);
	}
}
function _cronAppParse() {//прохождение по всем приложениям
	if(!empty($_GET['api_id']))
		return false;

	ob_start();
	register_shutdown_function('_cronMailSend');

	$sql = "SELECT
				*
			FROM `_app`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		die('Приложений нет.');

	$send = '';
	foreach($spisok as $r) {
		if($content = file_get_contents('http://'.DOMAIN.API_HTML.'/cron.php?cron_key='.CRON_KEY.'&api_id='.$r['id']))
			$send .= $r['id'].' - '.$r['title'].BR.$content;
	}

	_dbDump();

	echo  $send;
	exit;
}
function _cronSubmit() {//выполнение задач
	define('APP_ID', _num(@$_GET['api_id']));

	if(!APP_ID)
		return false;

	$sql = "SELECT *
			FROM `_app`
			WHERE `id`=".APP_ID;
	if(!$app = query_assoc($sql))
		die('Приложение '.APP_ID.' не зарегистрировано.');

	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

	echo
		zp_accrual().
		zp_image_attach(418627813).//Даша новая
		zp_image_attach(228890122).//Татьяна
		zp_image_attach(163178453).//Олеся
		smena_zp_accrual();
}
*/
