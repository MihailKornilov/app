<?php
function _viewer($viewer_id=VIEWER_ID, $i='') {//получение данных о пользовате из контакта
	if(!_num($viewer_id))
		return false;

	if(!$u = _cache()) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `viewer_id`=".$viewer_id."
				LIMIT 1";
		if(!$u = query_assoc($sql))
			$u = _viewerVkUpdate($viewer_id);

		$u['app_count'] = _viewerAppCount($viewer_id);

		$app = _viewerAppVar($viewer_id);
		$u['worker'] = _bool(@$app['worker']);
		$u['admin'] = _bool(@$app['admin']);
		
		_cache($u);
	}

	_viewerDefine($u);

	return $u;
}
function _viewerVkUpdate($viewer_id) {//Обновление пользователя из Контакта
	if(LOCAL)
		_appError('Not load user from VK <b>'.$viewer_id.'</b> in LOCAL version.');

	$res = _vkapi('users.get', array(
		'user_ids' => $viewer_id,
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$viewer_id);

	$res = $res['response'][0];
	$u = array(
		'viewer_id' => $viewer_id,
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
			WHERE `viewer_id`=".$viewer_id."
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
				".$viewer_id.",
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

	return _viewer($viewer_id);
}
function _viewerDefine($u) {//установка констант для пользователя
	if(defined('VIEWER_DEFINED'))
		return;

	define('VIEWER_WORKER', $u['worker']);
	define('VIEWER_ADMIN', $u['admin']);
	define('VIEWER_APP_ONE', $u['app_count'] < 2);
	define('VIEWER_APP_COUNT', $u['app_count']);
	define('VIEWER_APP_NAME', $u['first_name'].' '.$u['last_name']);//Имя Фамилия

	define('VIEWER_DEFINED', true);

	return;
}
function _viewerAppCount($viewer_id) {//получение количества приложений, в которых участвует пользователь
	$sql = "SELECT COUNT(*)
			FROM `_vkuser_app`
			WHERE `viewer_id`=".$viewer_id."
			  AND `worker`";
	return _num(query_value($sql));
}
function _viewerAppVar($viewer_id) {//получение настроек в приложении для пользователя
	if(!defined('APP_ID'))
		return array();

	$sql = "SELECT *
			FROM `_vkuser_app`
			WHERE `viewer_id`=".$viewer_id."
			  AND `app_id`=".APP_ID;
	return query_assoc($sql);
}
