<?php
function _user($user_id=USER_ID, $i='ass') {//получение данных о пользователя
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_CREATOR', APP_ID && _app(APP_ID, 'user_id_add') == USER_ID);//создатель приложения
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

