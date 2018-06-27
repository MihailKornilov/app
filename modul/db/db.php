<?php

/* ---=== Управление базой данных ===--- */
function _db() {//заглавная страница
	$sql = "SHOW TABLES";
	$arr = query_array($sql);

	$send = '';
	foreach($arr as $r) {
		$key = key($r);
		$table = $r[$key];
		$send .=
			'<div>'.
				'<a href="'.URL.'&p=6&table='.$table.'">'.$table.'</a>'.
			'</div>';
	}

	return $send;
}
function _db_table() {//содержание таблицы
	if(empty($_GET['table']) || !preg_match(REGEXP_MYSQLTABLE, $_GET['table']))
		return 'Таблицы не существует';
	
	$table = $_GET['table'];

	$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysql_num_rows(query($sql)))
			return 'Таблицы <b>'.$table.'</b> не существует';

	$sql = "DESCRIBE `".$table."`";
	$arr = query_array($sql);

	$send = '<table class="_stab small">'.
				'<tr>';
	foreach($arr as $r) {
		$send .= '<th>'.$r['Field'];
	}

	$sql = "SELECT * FROM `".$table."` LIMIT 100";
	$arr = query_array($sql);
//	print_r($arr[0]);
	foreach($arr as $row)  {
		$send .= '<tr class="over1">';
		foreach($row as $r) {
			$send .= '<td>'.$r;
		}
	}

	$send .= '</table>';

	return $send;
}










