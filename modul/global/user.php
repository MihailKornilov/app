<?php
function _user($user_id=USER_ID, $i='ass') {//получение данных о пользователя
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_ADMIN', _userAdmin());//создатель приложения
		define('USER_NAME', $u['i'].' '.$u['f']);//Имя Фамилия

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
function _userAdmin() {//получение флага администратора приложения
	if(!APP_ID)
		return false;
	if(_app(APP_ID, 'user_id_add') == USER_ID)
		return true;
	if(!$ids = _idsAss(_app(APP_ID, 'user_admin')))
		return false;

	return isset($ids[USER_ID]);
}
function _userCache($user_id) {
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


function _userApp($app_id=APP_ID, $user_id=USER_ID) {//получение данных пользователя, связанного с конкретным приложением
/*
	num_1  - доступ в приложение
	num_8  - $_GET['p'] последняя посещённая страница
	num_9  - $_GET['id'] id записи последней страницы
	num_10 - скрытый пользователь
*/

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=111
			  AND `cnn_id`=".$user_id."
			LIMIT 1";
	return query_assoc($sql);
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
function _user_active_minute($data, $hour, $min) {//вставка данный по минутам
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
