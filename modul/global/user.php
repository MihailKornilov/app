<?php
function _user($user_id=USER_ID) {//получение данных о пользовате из контакта
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_WORKER', $u['worker']);
		define('USER_APP_ONE', $u['app_count'] < 2);
		define('USER_NAME', $u['i'].' '.$u['o']);//»м€ ‘амили€
	}

	return $u;
}
function _userCache($user_id) {
	if($u = _cache())
		return $u;

	$sql = "SELECT * FROM `_user` WHERE `id`=".$user_id;
	if(!$u = query_assoc($sql))
		return array();

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

	return _cache($u);
}
function _userVkUpdate($user_id) {//ќбновление пользовател€ из  онтакта
	if(LOCAL)
		_appError('Not load user from VK <b>'.$user_id.'</b> in LOCAL version.');

	$res = _vkapi('users.get', array(
		'user_ids' => $user_id,
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$user_id);

	$res = $res['response'][0];
	$u = array(
		'user_id' => $user_id,
		'first_name' => win1251($res['first_name']),
		'last_name' => win1251($res['last_name']),
		'sex' => $res['sex'],
		'photo' => $res['photo'],
		'country_id' => empty($res['country']) ? 0 : $res['country']['id'],
		'country_title' => empty($res['country']) ? '' : win1251($res['country']['title']),
		'city_id' => empty($res['city']) ? 0 : $res['city']['id'],
		'city_title' => empty($res['city']) ? '' : win1251($res['city']['title'])
	);

	$sql = "SELECT `id`
			FROM `_vkuser`
			WHERE `viewer_id`=".$user_id."
			LIMIT 1";
	$id = query_value($sql);

	$sql = "INSERT INTO `_vkuser` (
				`id`,
				`viewer_id`,
				`first_name`,
				`last_name`,
				`sex`,
				`photo`,
				`country_id`,
				`country_title`,
				`city_id`,
				`city_title`
			) VALUES (
				".$id.",
				".$user_id.",
				'".addslashes($u['first_name'])."',
				'".addslashes($u['last_name'])."',
				"._num($u['sex']).",
				'".addslashes($u['photo'])."',
				"._num($u['country_id']).",
				'".addslashes($u['country_title'])."',
				"._num($u['city_id']).",
				'".addslashes($u['city_title'])."'
			) ON DUPLICATE KEY UPDATE
				`first_name`=VALUES(`first_name`),
				`last_name`=VALUES(`last_name`),
				`sex`=VALUES(`sex`),
				`photo`=VALUES(`photo`),
				`country_id`=VALUES(`country_id`),
				`country_title`=VALUES(`country_title`),
				`city_id`=VALUES(`city_id`),
				`city_title`=VALUES(`city_title`)";
	query($sql);

	return _user($user_id);
}
