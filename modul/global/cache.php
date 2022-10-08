<?php

define('CACHE_USE', true); //включение кеша
define('CACHE_TTL', 86400);//время в секундах, которое хранит кеш

function _cache($v=array()) {
    /*
        action (действие):
            get - считывание данных из кеша (по умолчанию)
            set - занесение данных в кеш
            isset - проверка существования кеша
            clear - очистка кеша

        global:
            1 - глобальные значения
            0 - конкретное приложение

        key: ключ кеша
    */

    $key = '__'._cachePrefix($v).'_'._cacheKey($v);

    switch(_cacheAction($v)) {
        case 'get': return CACHE_USE ? apcu_fetch($key) : false;
        case 'set':
//			if(!isset($v['data']))
//				die('Отсутствуют данные для внесения в кеш. Key: '.$key);

            if(CACHE_USE)
                apcu_store($key, $v['data'], CACHE_TTL);

            return $v['data'];
        case 'isset': return CACHE_USE ? apcu_exists($key) : false;
        case 'clear':
            if(CACHE_USE)
                apcu_delete($key);
            return true;
        default: die('Неизвестное действие кеша.');
    }
}
function _cacheAction($v) {//получение действия кеша
    if(empty($v['action']))
        return 'get';
    return $v['action'];
}
function _cacheKey($v) {//получение ключа кеша
    if(empty($v['key']))
        die('Отсутствует ключ кеша.');
    if(is_array($v['key']))
        die('Ключ кеша не может быть массивом.');
    return $v['key'];
}
function _cachePrefix($v) {//получение префикса кеша
    if(!empty($v['global']))
        return 'GLOBAL';
    if(!defined('APP_ID'))
        return 'GLOBAL';
    if(empty(APP_ID))
        return 'GLOBAL';
    return 'APP'.APP_ID;
}
function _cache_get($key, $global=0) {//получение значений кеша
    return _cache(array(
        'action' => 'get',
        'key' => $key,
        'global' => $global
    ));
}
function _cache_set($key, $data, $global=0) {//запись значений в кеш
    return _cache(array(
        'action' => 'set',
        'key' => $key,
        'data' => $data,
        'global' => $global
    ));
}
function _cache_isset($key, $global=0) {//проверка, производилась ли запись в кеш
    return _cache(array(
        'action' => 'isset',
        'key' => $key,
        'global' => $global
    ));
}
function _cache_clear($key, $global=0) {//очистка кеша
    if($key == 'all') {
        if(CACHE_USE)
            apcu_clear_cache();
        return true;
    }

    return _cache(array(
        'action' => 'clear',
        'key' => $key,
        'global' => $global
    ));
}
function _cache_content() {//содержание кеша в диалоге [84] (подключаемая функция [12])
    if(!CACHE_USE)
        $send = 'Кеш отключен.';
    elseif(!$name = @$_COOKIE['cache_content_name'])
        $send = 'Отсутствует имя кеша.';
    else {
        if(!apcu_exists($name))
            $send = '<b>'.$name.'</b>: кеш не сохранён.';
        else {
            if(!$arr = apcu_fetch($name))
                $send = '<b>'.$name.'</b>: кеш пуст.';
            else
                $send =
                    '<div class="fs15 b mb10">'.$name.'</div>'.
                    _pr($arr);
        }
    }
    return
        '<div style="height:700px;width:560px;overflow-y:scroll;word-wrap:break-word" class="bg0 bor-e8 pad10">'.
        $send.
        '</div>';
}









//class CacheApcu {
//    function __construct() {
//    }
//
//    /**
//     * Получение данных из кеша
//     */
//    public function fetch($key) {
//        if(apcu_exists($key) === false)
//            return [];
//
//        return apcu_fetch($key);
//    }
//
//    /**
//     * Сохранение данных в кеш
//     */
//    public function store($key, $v) {
//        return apcu_store($key, $v);
//    }
//}
//
//echo '<br><br><br><br><br>';
//
//$cache = new CacheApcu();
//$cache->fetch('4124wwww');
////$cache->store('qqq1', '4124wwww');
//echo _pr($cache->fetch('qqq1'));


//echo '<br><br><br><br><br>';
//echo $c->CacheGet(22);

