<?php

/* ---=== ���������� ����� ������ ===--- */
function _db() {//��������� ��������
	$sql = "SHOW TABLES";
	$arr = query_array($sql);

	$send = '';
	foreach($arr as $r)
		$send .=
			'<div>'.
				'<a href="'.URL.'&p=6&table='.$r['Tables_in__global_n'].'">'.$r['Tables_in__global_n'].'</a>'.
			'</div>';

	return $send;
}
function _db_table() {//���������� �������
	if(empty($_GET['table']) || !preg_match(REGEXP_MYSQLTABLE, $_GET['table']))
		return '������� �� ����������';
	
	$table = $_GET['table'];

	$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysql_num_rows(query($sql)))
			return '������� <b>'.$table.'</b> �� ����������';

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










