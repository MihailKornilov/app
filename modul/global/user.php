<?php
/* ---=== ПОЛЬЗОВАТЕЛИ ===---
	`_user`        - данные всех зарегистрированных пользователей
	`_user_access` - права и доступ к страницам
	`_spisok`      - дополнительные параметры пользователя для конкретного приложения
		Связка: `cnn_id`=_user.id
				`dialog_id`=111 - базовый диалог Пользователи
		Испльзование в функциях:
			_userAppAccessCreate
			_element46_print
			_SUN_INSERT
*/

function _user($user_id=USER_ID, $i='ass') {//получение данных о пользователя
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_ADMIN', $u['access_admin']);    //администратор приложения
		define('USER_ACCESS_TASK', $u['access_task']);//доступ к задачам
		define('USER_ACCESS_MANUAL', $u['access_manual']);//доступ к задачам
		define('USER_NAME', $u['i']);//Имя
		define('USER_NAME_FAM', $u['i'].' '.$u['f']);//Имя Фамилия

		define('PIN', !empty($u['pin']));       //установлен ли у пользователя пин-код
		define('PIN_KEY', USER_ID.'-pin-key');  //ключ для сессии
		define('PIN_DURATION', 3600);           //интервал действия пин-кода в секундах
		define('PIN_TIME', empty($_SESSION[PIN_KEY]) ? 0 : $_SESSION[PIN_KEY]);
		define('PIN_ENTER', PIN && (PIN_TIME - time() < 0));//требуется ли ввод пин-кода
	}

	switch($i) {
		case 'ass': return $u;
		case 'name': return $u['i'].' '.$u['f'];
		case 'ava': return '<img src="'.$u['src'].'" />';
		case 'ava30': return '<img class="ava30" src="'.$u['src'].'" />';
	}

	if(isset($u[$i]))
		return $u[$i];

	return '';
}
function _userCache($user_id) {//кеширование данных пользователя для конктетного приложения
	$key = 'user'.$user_id;

	if($u = _cache_get($key))
		return $u;

	$sql = "SELECT * FROM `_user` WHERE `id`=".$user_id;
	if(!$u = query_assoc($sql))
		return array();

	$u['src'] = 'https://vk.com/images/camera_50.png';
	if($image_id = _idsFirst($u['ava'])) {
		$sql = "SELECT * FROM `_image` WHERE `id`=".$image_id;
		if($img = query_assoc($sql))
			$u['src'] = _imageServer($img['server_id']).$img['80_name'];
	}

	$u['access_id'] = 0;    //идентификатор строки прав
	$u['access_enter'] = 0; //вход в приложение
	$u['access_admin'] = 0; //администрирование приложения
	$u['access_task'] = 0;  //доступ к задачам
	$u['access_manual'] = 0;//доступ к задачам
	$u['access_pages'] = '';//доступные страницы в приложении
	$u['user_hidden'] = 0;  //скрытый пользователь
	$u['invite_hash'] = ''; //код для приглашения

	$u = _userAppAccessGet($user_id) + $u;

	//обновление активности в приложении
	if($user_id == USER_ID) {
		$sql = "UPDATE `_user`
				SET `dtime_last`=CURRENT_TIMESTAMP
				WHERE `id`=".USER_ID;
		query($sql);
		$u['dtime_last'] = TODAY.strftime(' %H:%M:%S');
	}

	return _cache_set($key, $u);
}
function _userAppAccessCreate($app_id, $user_id=USER_ID, $invite_id=0) {//создание записей для прав доступа для пользователя к приложению. Если отсутствуют, то создание
	//флаг создания прав для пользователя. Если права уже были созданы ранее, возвращается ноль
	$UA_CREATED = 0;

	//права доступа пользователя к приложению
	if(!_userAppAccessGet($user_id, $app_id)) {
		$sql = "INSERT INTO `_user_access` (
					`app_id`,
					`user_id`,
					`access_enter`,
					`invite_user_id`
				) VALUES (
					".$app_id.",
					".$user_id.",
					1,
					".$invite_id."
				)";
		$UA_CREATED = query_id($sql);
	}

	//дополнительное параметры в приложении
	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=111
			  AND `cnn_id`=".$user_id."
			  AND !`deleted`";
	if(!query_value($sql)) {
		$sql = "INSERT INTO `_spisok` (
					`app_id`,
					`dialog_id`,
					`cnn_id`
				) VALUES (
					".$app_id.",
					111,
					".$user_id."
				)";
		query($sql);
	}

	return $UA_CREATED;
}
function _userAppAccessGet($user_id, $app_id=APP_ID) {//права пользователя в приложении
	if(!$app_id)
		return array();

	$sql = "SELECT
				`id` `access_id`,
				`access_enter`,
				`access_admin`,
				`access_task`,
				`access_manual`,
				`access_pages`,
				`user_hidden`,
				`invite_hash`
			FROM `_user_access`
			WHERE `app_id`=".$app_id."
			  AND `user_id`=".$user_id."
			LIMIT 1";
	return _arrNum(query_assoc($sql));
}
function _userAppAccessDel($DLG, $user_id) {//удаление прав пользователя из текущего приложения
	if(!$pid = $DLG['dialog_id_parent'])
		return;
	if(!$PAR = _dialogQuery($pid))
		return;
	if($PAR['table_name_1'] != '_user')
		return;

	$sql = "DELETE FROM `_user_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$user_id;
	query($sql);

//	_cache_clear('page');
	_cache_clear('user'.$user_id);
}
function _userVkUpdate($vk_id) {//Обновление пользователя из Контакта
	if(LOCAL)
		die('Данные пользователя VK не были получены <b>'.$vk_id.'</b> в LOCAL версии.');

	$res = _vkapi('users.get', array(
		'user_ids' => $vk_id,
		'fields' => 'photo_200,'.
					'photo_max,'.
					'sex'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$vk_id);

	$res = $res['response'][0];

	$photo = '';
	if(!empty($res['photo_200']))
		$photo = $res['photo_200'];
	if(!$photo && !empty($res['photo_max']))
		$photo = $res['photo_max'];
	if(preg_match('/deactivated/', $photo))
		$photo = '';
	if(preg_match('/camera/', $photo))
		$photo = '';

	$image_id = $photo ? _imageLink($photo, 'id') : '';

	$sql = "SELECT `id`
			FROM `_user`
			WHERE `vk_id`=".$vk_id."
			LIMIT 1";
	$user_id = _num(query_value($sql));

	$sql = "INSERT INTO `_user` (
				`id`,
				`vk_id`,
				`f`,
				`i`,
				`pol`,
				`ava`
			) VALUES (
				".$user_id.",
				".$vk_id.",
				'".addslashes($res['last_name'])."',
				'".addslashes($res['first_name'])."',
				"._num($res['sex']).",
				'".$image_id."'
			) ON DUPLICATE KEY UPDATE
				`f`=VALUES(`f`),
				`i`=VALUES(`i`),
				`pol`=VALUES(`pol`),
				`ava`=VALUES(`ava`)";
	query($sql);

	if(!$user_id)
		$user_id = query_insert_id('_user');

	return $user_id;
}
function _userImageRepair() {//восстановление аватарок пользователей
	if(LOCAL)
		return;

	$sql = "SELECT *
			FROM `_user`
			WHERE `vk_id`
			  AND `vk_id`<2147000000
			  AND !LENGTH(`ava`)
			ORDER BY `id`";
	foreach(query_arr($sql) as $r)
		_userVkUpdate($r['vk_id']);
}




function _userActive($page_id) {//сохранение активности пользователя
	if(PAS)
		return;

	$active_id = 0;
	$data = array();

	$sql = "SELECT *
			FROM `_user_active`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".USER_ID."
			  AND DATE_FORMAT(`dtime_begin`,'%Y-%m-%d %H%')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d %H%')
			LIMIT 1";
	if($r = query_assoc($sql)) {
		$active_id = $r['id'];
		$data = json_decode($r['data'], true);
	}


	$v = $page_id;
	if($id = _num(@$_GET['id']))
		$v .= ':'.$id;

	$m = strftime('%M') * 1;
	$data[$m][] = $v;
	$data = json_encode($data);

	$sql = "INSERT INTO `_user_active` (
				`id`,
				`app_id`,
				`user_id`,
				`data`,
				`dtime_end`
			) VALUES (
				".$active_id.",
				".APP_ID.",
				".USER_ID.",
				'".$data."',
				CURRENT_TIMESTAMP
			) ON DUPLICATE KEY UPDATE
				`data`=VALUES(`data`),
				`dtime_end`=VALUES(`dtime_end`)";
	query($sql);
}

function PHP12_user_active() {//общая картина использования приложений за сутки
	define('USER_SKIP', " AND `user_id` NOT IN (1) ");

	$data = array();
	$sql = "SELECT
				`id`,
				DATE_FORMAT(`dtime_begin`,'%k') `h`,
				`data`
			FROM `_user_active`
			WHERE DATE_FORMAT(`dtime_begin`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')
			".USER_SKIP."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$h = $r['h'];
		if(!isset($data[$h]))
			$data[$h] = array();

		foreach(json_decode($r['data'], true) as $min => $v)
			$data[$h][$min] = 1;
	}

	$m10show = 0;
	$m10step = 5;
	$send = '<table>';
	for($hour = 0; $hour < 24; $hour++) {
		$send .= '<tr class="over3">'.
					'<td class="h25 bg-fff fs16 r color-555 bottom pr5">'.$hour;
		for($min = 0; $min < 6; $min++) {
			$send .= '<td class="tdd bottom">'.
						'<div class="m10'._dn($min, 'll').' prel">'.
							_user_active_minute($data, $hour, $min).
	((!$m10show || $hour == 23) && $min < 5 ? '<div class="m10num pabs pale">'.(($min+1)*10).'</div>' : '').
						'</div>';
		}
		if($m10show++ >= $m10step)
			$m10show = 0;
	}
	$send .= '</table>';

	return
	'<div id="user-active">'.$send.'</div>'.
	_user_active_itog();
}
function _user_active_minute($data, $hour, $min) {//вставка данных по минутам
	if(empty($data[$hour]))
		return '';

	$send = '';
	foreach($data[$hour] as $k => $v) {
		$m = floor($k / 10);
		if($m != $min)
			continue;

		$cls = '';
		if($l = $k%10)
			$cls = ' l'.$l;

		$send .= '<div class="mu'.$cls.'"></div>';
	}

	return $send;
}
function _user_active_itog() {//общий итог использования приложений
	$sql = "SELECT COUNT(*)
			FROM `_user_active`
			WHERE DATE_FORMAT(`dtime_begin`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')".USER_SKIP;
	$c_unit = query_value($sql);

	$sql = "SELECT COUNT(DISTINCT `app_id`)
			FROM `_user_active`
			WHERE DATE_FORMAT(`dtime_begin`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')".USER_SKIP;
	$c_app = query_value($sql);

	$sql = "SELECT COUNT(DISTINCT `user_id`)
			FROM `_user_active`
			WHERE DATE_FORMAT(`dtime_begin`,'%Y-%m-%d')=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-%d')".USER_SKIP;
	$c_user = query_value($sql);

	return
	'<table class="_stab mt20">'.
		'<tr><td>Всего записей:<td>'.$c_unit.
		'<tr><td>Приложения:<td>'.$c_app.
		'<tr><td>Пользователи:<td>'.$c_user.
	'</table>';
}



/* ---=== ПРИГЛАШЕНИЕ ПОЛЬЗОВАТЕЛЯ ПО ССЫЛКЕ ===---
	1. PHP12_user_invite - отображение ссылки для приглашения
	        Формируется уникальный хеш, проверяется совпадение на существование в базе.
			Хеш хранится в таблице `_user_access` для каждого пользователя.
			Если совпадение есть, вместо ссылки выводится сообщение о перезагрузке страницы.
	2. Пользователь-отправитель копирует ссылку от своего имени и отправляет другому пользователю.
	3. Пользователь-получатель заходит по ссылке.
			Функция _userInviteCookieSave - сохранение кеша в куку 'invite_hash', если не авторизирован. После регистрации или регистрации этот хеш будет получен.
			После входа в приложение функция _userInviteDlgOpen проверяет присутствие пользователя в приглашаемом приложении, и если нет, то открывает окно для приглашения.
			Очищается кука 'invite_hash', кеш переносится с куку 'invite_submit' и в течение 5-и минут ожидает принятие приглашение пользователем-получателем.
*/
function PHP12_user_invite() {//ссылка на приглашение для пользователя
	$u = _user();
	if(!$hash = $u['invite_hash']) {
		$code = rand(0, 1000000).'hash'.microtime(true);
		$hash = substr(md5($code), 0, 12);
		$sql = "SELECT COUNT(*)
				FROM `_user_access`
				WHERE `invite_hash`='".$hash."'";
		if(query_value($sql))
			return
			'<input type="text"'.
			  ' class="w100p color-vin bg14"'.
			  ' readonly'.
			  ' value="Для получения ссылки приглашения перезагрузите страницу"'.
			'/>';

		$sql = "UPDATE `_user_access`
				SET `invite_hash`='".$hash."'
				WHERE `id`=".$u['access_id'];
		query($sql);
		_cache_clear('user'.USER_ID);
	}

	return
	'<div class="prel">'.
		'<div class="icon icon-copy pabs r5 top5"></div>'.
		'<input type="text"'.
			  ' class="w100p blue b bg4 pr30"'.
			  ' readonly'.
			  ' value="http'.(LOCAL ? '://nyandoma' : 's://fast-bpm.ru').'/app/index.php?invite='.$hash.'"'.
		'/>'.
	'</div>';
}
function _userInviteCookieSave() {//сохранение кода приглашения в куки, если пользователь не был авторизирован
	if(!$hash = _txt(@$_GET['invite']))
		return;

	$sql = "SELECT COUNT(*)
			FROM `_user_access`
			WHERE `invite_hash`='".$hash."'";
	if(!query_value($sql))
		return;

	if(USER_ID)
		return;

	setcookie('invite_hash', $hash, time() + 3600, '/');
}
function _userInviteDlgOpen() {//автоматическое открытие диалога для приглашения
	if(!USER_ID)
		return '';
	if(!$hash = _txt(@$_GET['invite']))
		if(!$hash = _txt(@$_COOKIE['invite_hash']))
			return '';

	//очистка куки приглашения
	setcookie('invite_hash', '', time()-1, '/');

	//получение данных приглашения по хешу
	$sql = "SELECT *
			FROM `_user_access`
			WHERE `invite_hash`='".$hash."'";
	if(!$r = query_assoc($sql))
		return '';

	//проверка, существует ли пользователь приложении
	if(_userAppAccessGet(USER_ID, $r['app_id']))
		return '';

	//сохранение флага принятия приглашения
	setcookie('invite_submit', $hash, time()+600, '/');

	return '_dialogLoad({dialog_id:109});';
}
function PHP12_user_invite_msg() {//сообщение о приглашении (используется в диалоге 109)
	if(!$hash = _txt(@$_COOKIE['invite_submit']))
		return _emptyRed('Приглашения не существует.');

	$sql = "SELECT *
			FROM `_user_access`
			WHERE `invite_hash`='".$hash."'";
	if(!$r = query_assoc($sql))
		return _emptyRed('Приглашение отсутствует.');

	return
	'<div class="fs17">Пользователь <b class="fs17">'._user($r['user_id'], 'name').'</b></div>'.
	'<div class="fs17 mt5">приглашает вас в приложение</div>'.
	'<div class="b fs22 mt5">'._app($r['app_id'], 'name').'</div>';
}
function _user_invite_submit($DLG) {//принятие приглашения
	if($DLG['id'] != 109)
		return;
	if(!$hash = _txt(@$_COOKIE['invite_submit']))
		jsonError('Время для принятия приглашения вышло.<br>Пройдите снова по ссылке приглашения.');

	//получение данных приглашения по хешу
	$sql = "SELECT *
			FROM `_user_access`
			WHERE `invite_hash`='".$hash."'";
	if(!$r = query_assoc($sql))
		jsonError('Этого приглашения не существует.');

	//проверка, существует ли пользователь приложении
	if(!_userAppAccessCreate($r['app_id'], USER_ID, $r['user_id']))
		jsonError('У вас уже есть доступ к приложению.');

	setcookie('invite_submit', $hash, time()-1, '/');

	$sql = "UPDATE `_user_auth`
			SET `app_id`=".$r['app_id']."
			WHERE `code`='".CODE."'";
	query($sql);

	$sql = "UPDATE `_user`
			SET `app_id_last`=".$r['app_id']."
			WHERE `id`=".USER_ID;
	query($sql);

	_cache_clear('AUTH_'.CODE, 1);
	_cache_clear('page');

	_auth();
}










