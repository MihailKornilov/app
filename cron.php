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
	'curTime: '.strftime('%Y-%m-%d %H:%M:%S').
	'<br>'.
	'duration: '.round(microtime(true) - TIME, 3);
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
	define('APP_PARENT', APP_ID);

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

	//дата и время последнего выполнения задачи
	$sql = "UPDATE `_cron`
			SET `exec_time_last`=CURRENT_TIMESTAMP
			WHERE `id`=".$task_id;
	query($sql);

	$duration = round(microtime(true) - TIME, 3);

	//внесение истории о выполненной задаче
	$sql = "INSERT INTO `_cron_log` (
				`app_id`,
				`cron_id`,
				`duration`
			) VALUES (
				".APP_ID.",
				".$task_id.",
				".$duration."
			)";
	query($sql);

	echo
	'all='.count($SRC_ARR).
	BR.
	'duration='.$duration;
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




