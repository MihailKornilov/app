<?php
function _user($user_id=USER_ID, $i='') {//получение данных о пользовате из контакта
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_CREATOR', APP_ID && _app(APP_ID, 'user_id_add') == USER_ID);//создатель приложени€
		define('USER_WORKER', $u['worker']);
//		define('USER_APP_ONE', $u['app_count'] < 2);
		define('USER_NAME', $u['i'].' '.$u['f']);//»м€ ‘амили€
	}

	if($i == 'name')
		return $u['i'].' '.$u['f'];

	if($i == 'ava')
		return '<img src="'.$u['src'].'" />';

	if($i == 'ava30')
		return '<img src="'.$u['src'].'" width="30" />';

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
			WHERE `obj_name`='elem_1778'
			  AND `obj_id`=".$user_id."
			  AND !`sort`
			LIMIT 1";
	if($img = query_assoc($sql))
		$u['src'] = _imageServer($img['server_id']).$img['80_name'];


//		$u = _userVkUpdate($user_id);

/*
	//количества приложений, в которых участвует пользователь
	$sql = "SELECT COUNT(*)
			FROM `_vkuser_app`
			WHERE `viewer_id`=".$user_id."
			  AND `worker`";
	$u['app_count'] = _num(query_value($sql));

	if(!defined('APP_ID'))
	$sql = "SELECT *
			FROM `_vkuser_app`
			WHERE `viewer_id`=".$user_id."
			  AND `app_id`=".APP_ID;
	return query_assoc($sql);
*/


	$u['worker'] = 0;//_bool(@$app['worker']);

	return _cache_set($key, $u);
}
function _userVkUpdate($vk_id) {//ќбновление пользовател€ из  онтакта
	if(LOCAL)
		die('ƒанные пользовател€ VK не были получены <b>'.$vk_id.'</b> в LOCAL версии.');

	$res = _vkapi('users.get', array(
		'user_ids' => $vk_id,
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$vk_id);

	$res = $res['response'][0];
	$u = array(
		'user_id' => $vk_id,
		'first_name' => win1251($res['first_name']),
		'last_name' => win1251($res['last_name']),
		'sex' => $res['sex'],
		'photo' => $res['photo']
	);

	$sql = "SELECT `id`
			FROM `_user`
			WHERE `vk_id`=".$vk_id."
			LIMIT 1";
	$user_id = _num(query_value($sql));

	$pol = array(
		0 => 0,
		1 => 1750,//женский
		2 => 1749 //мужской
	);

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
				'".addslashes($u['last_name'])."',
				'".addslashes($u['first_name'])."',
				"._num(@$pol[$u['sex']]).",
				'".addslashes($u['photo'])."'
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
function _userImageMove() {//перенос аватарок пользователей в изображени€
	_cache_clear('IMG_SERVER');

	$sql = "SELECT *
			FROM `_user`
			WHERE LENGTH(`ava`)";
	foreach(query_arr($sql) as $r) {
		$ex = explode('/', $r['ava']);
		$c = count($ex) - 1;
		$server = '';
		foreach($ex as $n => $v) {
			if($n == $c)
				continue;
			$server .= $v.'/';
		}
		$name = $ex[$c];

		$sql = "INSERT INTO `_image` (
					`server_id`,
	
					`max_name`,
					`max_x`,
					`max_y`,
	
					`80_name`,
					`80_x`,
					`80_y`,
	
					`obj_name`,
					`obj_id`,
	
					`user_id_add`
				) VALUES (
					"._imageServer($server).",
	
					'".$name."',
					50,
					50,
	
					'".$name."',
					50,
					50,
	
					'elem_1778',
					".$r['id'].",
	
					".USER_ID."
			)";
		query($sql);

		$image_id = query_insert_id('_image');

		$sql = "UPDATE `_user`
				SET `ava`=''
				WHERE `id`=".$r['id'];
		query($sql);

		$sql = "SELECT `id`
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=1011
				  AND `connect_1`=".$r['id'];
		if($spisok_id = query_value($sql)) {
			$sql = "UPDATE `_spisok`
					SET `image_1`=".$image_id."
					WHERE `id`=".$spisok_id;
			query($sql);
		}
	}
}
