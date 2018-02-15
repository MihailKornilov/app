<?php
/* ”слови€, не требующие авторизации */

switch(@$_POST['op']) {
	case 'auth_vk'://авторизаци€ пользовател€ по VK
		$session = @$_POST['session'];
		$valid_keys = array('expire', 'mid', 'secret', 'sid');

		$key = '';
		foreach($valid_keys as $k)
			$key .= $k.'='.@$session[$k];

		$sig = md5($key.AUTH_APP_SECRET);

		if($sig != $session['sig'])
			jsonError('Ќеуспешна€ авторизаци€');

		//проверка, есть ли пользователь в базе
		$vkUser_id = _num($session['mid']);
		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=".$vkUser_id."
				LIMIT 1";
		if(!$user_id = _num(query_value($sql)))
			$user_id = _userVkUpdate($vkUser_id);//если нет - получение данных из VK

		_authSuccess($sig, $user_id);

		jsonSuccess();
		break;
	case 'login'://процесс авторизации пользовател€
		if(!$code = _txt($_POST['code']))
			jsonError('ќтсутствует код');

		if(!LOCAL) {
			$url = 'https://oauth.vk.com/access_token?'.
						'client_id='.AUTH_APP_ID.
					   '&client_secret='.AUTH_APP_SECRET.
					   '&redirect_uri=https://nyandoma.ru/app'.
					   '&code='.$code;
			if(!$res = @file_get_contents($url))
				jsonError('Ќеуспешна€ попытка получени€ токена');

			$res = json_decode($res, true);
		} else {
			//todo локальна€ верси€
			$res = array(
				'user_id' => 982006
			);
		}

		if(!$user_id = _num($res['user_id']))
			jsonError('ќшибка при получении токена');

		if(!$u = _user($user_id))
			jsonError('ќшибка получени€ данных пользовател€');

		//получение id приложени€, в котором в последний раз был пользователь
		$sql = "SELECT `app_id`
				FROM `_vkuser_app`
				WHERE `viewer_id`=".$user_id."
				  AND `worker`
				ORDER BY `last_seen` DESC
				LIMIT 1";
		$app_id = _num(query_value($sql));

		_authSuccess($code, $user_id, $app_id);
		
		jsonSuccess();
		break;
}

if(!CODE)
	jsonError('ѕользователь не авторизирован');



