<?php

/* ---=== Управление базой данных ===--- */
function _db() {//заглавная страница
	$sql = "SHOW TABLES";
	$arr = DB1::array($sql);

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
		if(!DB1::num_rows($sql))
			return 'Таблицы <b>'.$table.'</b> не существует';

	$sql = "DESCRIBE `".$table."`";
	$arr = DB1::array($sql);

	$send = '<table class="_stab small">'.
				'<tr>';
	foreach($arr as $r) {
		$send .= '<th>'.$r['Field'];
	}

	$sql = "SELECT * FROM `".$table."` LIMIT 100";
	$arr = DB1::array($sql);
	foreach($arr as $row)  {
		$send .= '<tr class="over1">';
		foreach($row as $r) {
			$send .= '<td>'.$r;
		}
	}

	$send .= '</table>';

	return $send;
}










