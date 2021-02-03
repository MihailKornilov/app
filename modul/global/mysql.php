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

	_db1();

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
function _field($table_id=0, $fieldName='') {//колонки по каждой таблице, используемые в диалогах
	$key = 'FIELD';
	if(!$FLD = _cache_get($key, 1)) {
		$sql = "SELECT DISTINCT(`table_1`)
				FROM `_dialog`
				WHERE `table_1`
				ORDER BY `table_1`";
		$ids = _ids(query_ids($sql), 1);
		foreach($ids as $id) {
			$sql = "DESCRIBE `"._table($id)."`";
			foreach(query_array($sql) as $r)
				$FLD[$id][$r['Field']] = 1;
		}

		_cache_set($key, $FLD, 1);
	}

	if($table_id) {
		$tabFld = isset($FLD[$table_id]) ? $FLD[$table_id] : array();
		if($fieldName)
			return isset($tabFld[$fieldName]);
		return $tabFld;
	}

	return $FLD;
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

	$field[] = _queryCol_id($DLG);
	$field[] = _queryColReq($DLG, 'dialog_id');
	$field[] = _queryColReq($DLG, 'block_id');
	$field[] = _queryColReq($DLG, 'element_id');
	$field[] = _queryColReq($DLG, 'parent_id');
	$field[] = _queryColReq($DLG, 'num');
	$field[] = _queryColReq($DLG, 'sort_pid');
	$field[] = _queryColReq($DLG, 'dtime_add');
	$field[] = _queryColReq($DLG, 'user_id_add');
	$field[] = _queryColReq($DLG, 'deleted');

	$D = $DLG;
	while(true) {
		foreach($D['cmp'] as $cmp) {
			if(!$col = _elemCol($cmp))
				continue;
			if($cmp['dialog_id'] == 9) {
				$field[] = "IF(`".$col."`,1,'') `".$col."`";
				continue;
			}
			$field[] = _queryColReq($DLG, $col);
		}

		if(!$parent_id = $D['dialog_id_parent'])
			break;

		$D = _dialogQuery($parent_id);
	}

	//если присутствует таблица=`_user`, прикрепление колонок `_user_access`
	$tab = _queryTable($DLG);
	if(isset($tab[12])) {
		$field[] = _queryColReq($DLG, 'access_enter');
		$field[] = _queryColReq($DLG, 'access_admin');
		$field[] = _queryColReq($DLG, 'access_task');
		$field[] = _queryColReq($DLG, 'access_manual');
		$field[] = _queryColReq($DLG, 'access_pages');
		$field[] = _queryColReq($DLG, 'user_hidden');
	}

	$field = array_diff($field, array(''));
	$field = array_unique($field);

	define($key, implode(',', $field));

	return constant($key);
}
function _queryCol_id($DLG) {//основной идентификатор: всегда берётся у родительского диалога
	$tab = _queryTable($DLG);
	$D = $DLG;
	while(true) {
		if(!$parent_id = $D['dialog_id_parent'])
			break;

		$D = _dialogQuery($parent_id);
	}

	return "`".$tab[$D['table_1']]."`.`id`";
}
function _queryColReq($DLG, $col) {//добавление обязательных колонок
	//колонка не используется ни в одной таблице
	if($tn = _queryTN($DLG, $col))
		return "`".$tn."`.`".$col."`";

	return '';
}
function _queryTN($DLG, $col, $full=false) {//получение имени таблицы для определённой колонки
	// $full - возвращать полное название таблицы
	if(!$col)
		return '';

	foreach(_queryTable($DLG) as $id => $t)
		if(_field($id, $col))
			return $full ? _table($id) : $t;

	return '';
}
function _queryFrom($DLG) {//составление таблиц для запроса
/*
	Диалог предварительно должен быть проверен и должен использовать таблицу
*/
	$key = 'QUERY_FROM_'.$DLG['id'];

	if(defined($key))
		return constant($key);

	$send = array();
	foreach(_queryTable($DLG) as $id => $t)
		$send[] = '`'._table($id).'` `'.$t.'`';
	$send = implode(',', $send);

	define($key, $send);

	return $send;
}
function _queryTable($DLG) {//перечень таблиц, используемых в запросе
	global $QTAB;

	$key = 'QTAB'.$DLG['id'];

	if(isset($QTAB[$key]))
		return $QTAB[$key];

	$table[$DLG['table_1']] = 't1';

	$n = 2;
	while($parent_id = $DLG['dialog_id_parent']) {
		if(!$PAR = _dialogQuery($parent_id))
			break;

		$DLG = $PAR;

		if(!isset($table[$DLG['table_1']])) {
			$table[$DLG['table_1']] = 't'.($n++);
			//если таблица=`_user`, прикрепление таблицы `_user_access`(32)
			if($DLG['table_1'] == 12)
				$table[32] = 't'.($n++);
		}
	}

	$QTAB[$key] = $table;

	return $table;
}
function _queryWhere($DLG, $withDel=0) {//составление условий для запроса
	$key = 'QUERY_WHERE_'.$DLG['id'].'_'.$withDel;

	if(defined($key))
		return constant($key);

	$send[] = _queryWhere_dialog_id($DLG);

	$D = $DLG;
	while(true) {
		if(!$parent_id = $D['dialog_id_parent'])
			break;

		$PAR = _dialogQuery($parent_id);

		if($PAR['table_1'] != $D['table_1']) {
			$send[] = _queryColReq($DLG, 'cnn_id')."="._queryCol_id($DLG);
			//если присутствует таблица=`_user`, добавление условий для `_user_access`
			$tab = _queryTable($DLG);
			if(isset($tab[12])) {
				$send[] = "`".$tab[32]."`.`user_id`="._queryCol_id($DLG);
				$send[] = "`".$tab[32]."`.`app_id`=".APP_ID;

				$tn = _queryTN($DLG, 'app_id');
				$send[] = "`".$tn."`.`app_id`=".APP_ID;
			}
			break;
		}

		$D = $PAR;
	}

	if(!$withDel)
		if($col = _queryColReq($DLG, 'deleted'))
			$send[] = "!".$col;

	if($tn = _queryTN($DLG, 'app_id'))
		if(!$DLG['spisok_any'])
			switch($DLG['table_name_1']) {
				case '_dialog':
					if($DLG['id'] == 42)
						break;
					$send[] = "!`".$tn."`.`app_id`";
					break;
				case '_element': break;
				case '_action':  break;
				case '_page':    break;
				case '_spisok':  break;
				default:
					$send[] = "`".$tn."`.`app_id`=".APP_ID;
			}

	$send = array_diff($send, array(''));
	$send = array_unique($send);

	if(!$send = implode(' AND ', $send))
		$send = _queryCol_id($DLG);

	define($key, $send);

	return $send;
}
function _queryWhere_dialog_id($DLG) {//получение условия по `dialog_id`
	$tab = _queryTable($DLG);
	if(isset($tab[5])) //_element
		return '';
	if(!$tn = _queryTN($DLG, 'dialog_id'))
		return '';

	$parent_id = $DLG['dialog_id_parent'];
	$dialog_id = $parent_id ? $parent_id : $DLG['id'];

	return "`".$tn."`.`dialog_id`=".$dialog_id;
}










//ВТОРАЯ БАЗА
_db_connect2();

function _db_connect2() {//подключение ко второй базе данных
	global  $CNN2,   //соединение со второй базой
			$SQL_CNN,
	        $CNN1;   //хранение первого подключения к базе, чтобы всегда переключаться на него после каждого запроса ко второй

	$CNN1 = $SQL_CNN;

	if(!$CNN2 = mysqli_connect(
		MYSQLI_HOST,
		MYSQLI_USER2,
		MYSQLI_PASS2,
		MYSQLI_DATABASE2
	))
	    die('Can`t mysql connect BAZE2: '.mysqli_connect_error());

	$sql = "SET NAMES '".MYSQLI_NAMES."'";
	mysqli_query($CNN2, $sql);
}
function _db1() {//переключение на первую базу
	global $SQL_CNN, $CNN1;
	$SQL_CNN = $CNN1;
}
function _db2() {//переключение на вторую базу
	global $SQL_CNN, $CNN2;
	$SQL_CNN = $CNN2;
}



