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

	$sqlPath = '';
	if(DEBUG) {
		$DB = debug_backtrace();

		$n = substr($DB[1]['function'], 0, 5) == 'query' ? 1 : 0;

		$ex = explode('\\', $DB[$n]['file']);
		$file = $ex[count($ex) - 1];
		$sqlPath = '/* '.$file.':'.$DB[$n]['line'].' '.$DB[$n]['function'].' */'."\n";
	}

	$SQL_TIME += $t;
	$SQL_QUERY[] = $sqlPath.$sql;
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
	$key = 'TABLE';
	if(!$tab = _cache_get($key, 1)) {
		$sql = "SELECT `id`,`name`
				FROM `_table`
				ORDER BY `name`";
		$tab = query_ass($sql);

		//внесение таблиц, которых нет в таблице `_table`
		$ass = array();
		foreach($tab as $t)
			$ass[$t] = 1;
		$sql = "SHOW TABLES";
		foreach(query_array($sql) as $r) {
			$i = key($r);
			$t = $r[$i];
			if($t == '_table')
				continue;
			if(!isset($ass[$t])) {
				$sql = "INSERT INTO `_table` (`name`) VALUES ('".$t."')";
				$tab_id = query_id($sql);
				$tab[$tab_id] = $t;
			}
		}
		_cache_set($key, $tab, 1);
	}

	if($id === false)
		return $tab;
	//получение ID по имени таблицы
	if(!_num($id)) {
		if(empty($id))
			return '';
		foreach($tab as $tid => $name)
			if($id == $name)
				return $tid;
		return 0;
	}
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
	$field[] = _queryColReq($DLG, 'deleted');

	//id диалога, который использовался при создании записи
	$field[] = $DLG['id'].' `dialog_id_use`';

	foreach($DLG['cmp'] as $cmp) {
		$col = _elemCol($cmp);
		if($cmp['dialog_id'] == 9)
			$field[] = "IF(`".$col."`,1,'') `".$col."`";
		else
			$field[] = _queryColReq($DLG, $col);
	}

	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		foreach($PAR['cmp'] as $cmp) {
			$col = _elemCol($cmp);
			if($cmp['dialog_id'] == 9)
				$field[] = "IF(`".$col."`,1,'') `".$col."`";
			else
				$field[] = _queryColReq($DLG, $col);

			//если родительская таблица=`_user`, прикрепление колонок `_user_access`
			if($PAR['table_1'] == 12) {
				$field[] = "`t3`.`access_enter`";
				$field[] = "`t3`.`access_admin`";
				$field[] = "`t3`.`access_task`";
				$field[] = "`t3`.`access_manual`";
				$field[] = "`t3`.`access_pages`";
				$field[] = "`t3`.`user_hidden`";
			}
		}
	}

	$field = array_diff($field, array(''));
	$field = array_unique($field);

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
		if($PAR['table_1'] != $DLG['table_1']) {
			$send .= ",`".$DLG['table_name_1']."` `t2`";
			//если родительская таблица=`_user`, прикрепление таблицы `_user_access`
			if($PAR['table_1'] == 12)
				$send .= ",`_user_access` `t3`";
		}
	}

	define($key, $send);

	return $send;
}
function _queryWhere($DLG, $withDel=0) {//составление условий для запроса
	$key = 'QUERY_WHERE_'.$DLG['id'].$withDel;

	if(defined($key))
		return constant($key);

	$send = array();

	//если присутствует родительский диалог и разные таблицы, происходит связка через `cnn_id`
	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		if($PAR['table_1'] != $DLG['table_1']) {
			$send[] = "`t2`.`cnn_id`=`t1`.`id`";
			//если родительская таблица=`_user`, добавление условий для `_user_access`
			if($PAR['table_1'] == 12) {
				$send[] = "`t3`.`user_id`=`t1`.`id`";
				$send[] = "`t3`.`app_id`=".APP_ID;
			}

		}
	}

	if(!$withDel)
		if($tn = _queryTN($DLG, 'deleted'))
			$send[] = "!`".$tn."`.`deleted`";

	if($tn = _queryTN($DLG, 'app_id'))
		if(!$DLG['spisok_any'])
			switch($DLG['table_name_1']) {
				case '_dialog':
					$send[] = "!`".$tn."`.`app_id`";
					break;
				case '_element': break;
				case '_hint': break;
				case '_action':  break;
				case '_page':  break;
				default:
					$send[] = "`".$tn."`.`app_id`=".APP_ID;
			}


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





