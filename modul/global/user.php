<?php
function _user($user_id=USER_ID) {//получение данных о пользовате из контакта
	if(!_num($user_id))
		return array();

	if(!$u = _userCache($user_id))
		return array();

	if(!defined('USER_NAME')) {
		define('USER_WORKER', $u['worker']);
		define('USER_APP_ONE', $u['app_count'] < 2);
		define('USER_NAME', $u['i'].' '.$u['f']);//»м€ ‘амили€
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
function _userVkUpdate($vk_id) {//ќбновление пользовател€ из  онтакта
	if(LOCAL)
		_appError('Not load user from VK <b>'.$vk_id.'</b> in LOCAL version.');

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
				"._num($u['sex']).",
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
