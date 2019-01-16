<?php
function _user($user_id=USER_ID, $i='') {//получение данных о пользовате из контакта
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_CREATOR', APP_ID && _app(APP_ID, 'user_id_add') == USER_ID);//создатель приложения
		define('USER_NAME', $u['i'].' '.$u['f']);//Имя Фамилия
	}

	if($i == 'name')
		return $u['i'].' '.$u['f'];

	if($i == 'ava')
		return '<img src="'.$u['src'].'" />';

	if($i == 'ava30')
		return '<img class="ava30" src="'.$u['src'].'" />';

	if($i == 'src')
		return $u['src'];

	return $u;
}
function _userCache($user_id) {
	$key = 'user'.$user_id;

	if($u = _cache_get($key))
		return $u;

	$sql = "SELECT * FROM `_user` WHERE `id`=".$user_id;
	if(!$u = query_assoc($sql))
		return array();

	$u['src'] = 'https://vk.com/images/camera_50.png';
	$sql = "SELECT *
			FROM `_image`
			WHERE !`sort`
			  AND !`id`
			LIMIT 1";
	if($img = query_assoc($sql))
		$u['src'] = _imageServer($img['server_id']).$img['80_name'];

	return _cache_set($key, $u);
}
function _userVkUpdate($vk_id) {//Обновление пользователя из Контакта
	if(LOCAL)
		die('Данные пользователя VK не были получены <b>'.$vk_id.'</b> в LOCAL версии.');

	$res = _vkapi('users.get', array(
		'user_ids' => $vk_id,
		'fields' => 'photo_400_orig,'.
					'photo_max,'.
					'sex'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$vk_id);

	$res = $res['response'][0];

	$photo = '';
	if(!empty($res['photo_400_orig']))
		$photo = $res['photo_400_orig'];
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
			  AND !LENGTH(`ava`)
			ORDER BY `id`";
	foreach(query_arr($sql) as $r)
		_userVkUpdate($r['vk_id']);
}


function _userApp($app_id, $user_id=USER_ID) {//получение данных пользователя, связанного с конкретным приложением
	$sql = "SELECT *
			FROM `_spisok`
			WHERE `app_id`=".$app_id."
			  AND `dialog_id`=111
			  AND `cnn_id`=".$user_id."
			LIMIT 1";
	return query_assoc($sql);
}

