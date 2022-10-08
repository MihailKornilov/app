<?php

/*
 * Подключение к базе производится автоматически при создании первого запроса
 * */
class DB1 {
//    todo: указание типа данных начинается с PHP 7.4
//    static protected object $Q;           //объект соединения с базой
//    static protected string $host = MYSQLI_HOST;
//    static protected string $user = MYSQLI_USER;
//    static protected string $pass = MYSQLI_PASS;
//    static protected string $base = MYSQLI_DATABASE;

    static protected $Q;           //объект соединения с базой
    static protected $host = MYSQLI_HOST;
    static protected $user = MYSQLI_USER;
    static protected $pass = MYSQLI_PASS;
    static protected $base = MYSQLI_DATABASE;

    /**
     * Подключение к базе
     * */
    static protected function Connect() {
        if(isset(static::$Q))
            return;

        static::$Q = new mysqlClass(
            static::$host,
            static::$user,
            static::$pass,
            static::$base
        );
    }

    static public function query($sql) {
        static::Connect();
        return static::$Q->query($sql);
    }



    static public function value($sql) {
        static::Connect();
        return static::$Q->value($sql);
    }
    static public function value_cache($sql) {
        static::Connect();
        return static::$Q->value_cache($sql);
    }



    static public function arr($sql, $key='id') {
        static::Connect();
        return static::$Q->arr($sql, $key);
    }
    static public function arr_cache($sql, $key='id') {
        static::Connect();
        return static::$Q->arr_cache($sql, $key);
    }



    static public function array($sql) {
        static::Connect();
        return static::$Q->array($sql);
    }
    static public function array_cache($sql) {
        static::Connect();
        return static::$Q->array_cache($sql);
    }



    static public function assoc($sql) {
        static::Connect();
        return static::$Q->assoc($sql);
    }
    static public function assoc_cache($sql) {
        static::Connect();
        return static::$Q->assoc_cache($sql);
    }



    static public function ass($sql) {
        static::Connect();
        return static::$Q->ass($sql);
    }
    static public function ass_cache($sql) {
        static::Connect();
        return static::$Q->ass_cache($sql);
    }



    static public function ids($sql) {
        static::Connect();
        return static::$Q->ids($sql);
    }
    static public function ids_cache($sql) {
        static::Connect();
        return static::$Q->ids_cache($sql);
    }



    //Количество записей в запросе
    static public function num_rows($sql):int {
        static::Connect();
        return static::$Q->num_rows($sql);
    }


    //id внесённой записи
    static public function insert_id($sql) {
        static::Connect();
        return static::$Q->insert_id($sql);
    }


    //количество сделанных запросов
    static public function QueryCount() {
        static::Connect();
        return static::$Q->QueryCount();
    }

    //общее время всех запросов
    static public function QueryDur() {
        static::Connect();
        return static::$Q->QueryDur();
    }

    //массив с поднобностями о запросах
    static public function QueryMass() {
        static::Connect();
        return static::$Q->QueryMass();
    }
}


/*
 * Последующие подключения к другим базам данных наследуются от класса DB1
 * Обязательно нужно указать новое имя базы данных: $base
 * */
//class DB2 extends DB1 {
//    static protected $Q;           //объект соединения с базой
//    static protected $base = 'english';
//}






class mysqlClass {
//    todo указание типа данных с версии PHP 7.4
//    private object $cnn;       //идентификатор подключения к базе
//    private int $count = 0;    //количество сделанных запросов
//    private float $dur = 0;    //общая продолжительность выполнения запросов

    private $cnn;       //идентификатор подключения к базе
    private $count = 0; //количество сделанных запросов
    private $dur = 0;   //общая продолжительность выполнения запросов
    private $mass = []; //список запросов
    private const CACHE_TTL = 600;

    function __construct($host, $user, $pass, $base) {
        if(!$this->cnn = mysqli_connect($host, $user, $pass, $base))
            die('Can`t mysql connect: '.mysqli_connect_error());

        mysqli_query($this->cnn, "SET NAMES 'utf8'");
    }


    /**
     * Базовый запрос. От него происходят остальные запросы.
     * Подсчёт количества запросов
     * Подсчёт времени выполнения запроса
     * */
    public function query($sql) {
        $t = microtime(true);

        if(!$res = mysqli_query($this->cnn, $sql)) {
            $msg =  $sql."\n\n".mysqli_error($this->cnn);
            die($msg);
        }
        $t = microtime(true) - $t;


        $this->count++;
        $this->dur += $t;

        $DB = debug_backtrace();

        $n = 0;
        while($n < 5) {
            $n++;
            if(empty($DB[$n]['class']))
                break;
            if($DB[$n]['class'] == 'mysqlClass')
                continue;
        }

        $ex = explode('\\', $DB[$n-1]['file']);
        $file = $ex[count($ex) - 1];
        $funcSql = $DB[$n-1]['function'];//SQL-функция, через которую выполнялся запрос
        $func = $DB[$n]['function']; //PHP-функция, из которой был вызван SQL-запрос


        $this->mass[] = array(
            'sql' => $sql,
            't' => round($t, 3),
            'path' => $file.':'.$DB[$n-1]['line'].
                      ' <b class="ml10">'.$func.'</b>:'.
                      ' <span class="clr13">'.$funcSql.'</span>'
        );

        return $res;
    }

    /**
     * Получение одного значения
     * */
    public function value($sql) {
        $res = $this->query($sql);

        if(!$r = mysqli_fetch_row($res))
            return 0;
        if(preg_match(REGEXP_INTEGER, $r[0]))
            return (int)$r[0];

        return $r[0];
    }
    public function value_cache($sql) {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey)) {
//            echo "<br><br><br><br><br>".$key.' '.$sql;
            return apcu_fetch($cacheKey);
        }

        $v = $this->value($sql);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }


    /**
     * Массив по ключу
     * */
    public function arr($sql, $key):array {
        $q = $this->query($sql);

        $send = array();
        while($r = mysqli_fetch_assoc($q))
            $send[$r[$key]] = $r;

        return $send;
    }
    public function arr_cache($sql, $key):array {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey))
            return apcu_fetch($cacheKey);

        $v = $this->arr($sql, $key);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }




    /**
     * Последовательный массив без ключей
     * */
    public function array($sql):array {
        $q = $this->query($sql);

        $send = array();
        while($r = mysqli_fetch_assoc($q))
            $send[] = $r;

        return $send;
    }
    public function array_cache($sql):array {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey))
            return apcu_fetch($cacheKey);

        $v = $this->array($sql);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }




    /**
     * Ассоциативный массив одной записи
     * */
    public function assoc($sql):array {
        $q = $this->query($sql);

        if(!$r = mysqli_fetch_assoc($q))
            return array();

        return $r;
    }
    public function assoc_cache($sql):array {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey))
            return apcu_fetch($cacheKey);

        $v = $this->assoc($sql);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }




    /**
     * Ассоциативный массив из двух значений: a => b
     * */
    public function ass($sql):array {
        $q = $this->query($sql);

        $send = array();
        while($r = mysqli_fetch_row($q))
            $send[$r[0]] = preg_match(REGEXP_NUMERIC, $r[1]) ? (int)$r[1] : $r[1];

        return $send;
    }
    public function ass_cache($sql):array {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey))
            return apcu_fetch($cacheKey);

        $v = $this->ass($sql);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }




    /**
     * Идентификаторы через запятую
     * */
    public function ids($sql) {
        $q = $this->query($sql);

        $send = array();
        while($r = mysqli_fetch_row($q))
            $send[] = $r[0];

        return !$send ? 0 : implode(',', array_unique($send));
    }
    public function ids_cache($sql) {
        $cacheKey = md5($sql);

        if(apcu_exists($cacheKey))
            return apcu_fetch($cacheKey);

        $v = $this->ids($sql);
        apcu_store($cacheKey, $v, CACHE_TTL);

        return $v;
    }




    /**
     * Количество записей в запросе
     * */
    public function num_rows($sql):int {
        return mysqli_num_rows($this->query($sql));
    }




    /**
     * ID внесённой записи
     * */
    public function insert_id($sql):int {
        $this->query($sql);
        return (int)mysqli_insert_id($this->cnn);
    }






    /**
     * Количество сделанных запросов
     * */
    public function QueryCount():int {
        return $this->count;
    }

    /**
     * Общее время выполнения запросов
     * */
    public function QueryDur():float {
        return round($this->dur, 3);
    }

    /**
     * Массив с поднобностями о запросах
     * */
    public function QueryMass():array {
        return $this->mass;
    }
}









function _table($id=false) {//таблицы в базе с соответствующими идентификаторами
	$key = 'TABLE';
	if(!$tab = _cache_get($key, 1)) {
		$sql = "SELECT `id`,`name`
				FROM `_table`
				ORDER BY `name`";
		$tab = DB1::ass($sql);

		//внесение таблиц, которых нет в таблице `_table`
		$ass = array();
		foreach($tab as $t)
			$ass[$t] = 1;
		$sql = "SHOW TABLES";
		foreach(DB1::array($sql) as $r) {
			$i = key($r);
			$t = $r[$i];
			if($t == '_table')
				continue;
			if(!isset($ass[$t])) {
				$sql = "INSERT INTO `_table` (`name`) VALUES ('".$t."')";
				$tab_id = DB1::insert_id($sql);
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
		$ids = _ids(DB1::ids($sql), 1);
		foreach($ids as $id) {
			$sql = "DESCRIBE `"._table($id)."`";
			foreach(DB1::array($sql) as $r)
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









