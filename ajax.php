<?php
require_once 'modul/global/global.php';
require_once 'modul/global/auth_ajax.php';

_face();
_auth();
_sa();
_user();
_pasDefine();


require_once 'modul/global/global_ajax.php';
require_once 'modul/block/block_ajax.php';
require_once 'modul/element/element_ajax.php';
require_once 'modul/spisok/spisok_ajax.php';
require_once 'modul/action/action_ajax.php';
require_once 'modul/debug/debug_ajax.php';





jsonError('Условие [op: '.@$_POST['op'].'] не найдено');




function jsonError($values=null) {
	$send['error'] = 1;
	if(empty($values))
		$send['text'] = 'Произошла неизвестная ошибка.';
	elseif(is_array($values))
		$send += $values;
	else
		$send['text'] = $values;
	die(json_encode($send + jsonDebugParam()));
}
function jsonSuccess($send=array()) {
	$send['success'] = 1;
	die(json_encode($send + jsonDebugParam()));
}
