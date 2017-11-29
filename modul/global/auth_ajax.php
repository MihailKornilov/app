<?php

//условия, не требующие авторизации
switch(@$_POST['op']) {
	case 'login'://процесс авторизации пользователя
		if(!$code = _txt($_POST['code']))
			jsonError('Отсутствует код');

		if(!LOCAL) {
			$url = 'https://oauth.vk.com/access_token?'.
						'client_id='.AUTH_APP_ID.
					   '&client_secret='.AUTH_APP_SECRET.
					   '&redirect_uri=https://nyandoma.ru/app'.
					   '&code='.$code;
			if(!$res = @file_get_contents($url))
				jsonError('Неуспешная попытка получения токена');

			$res = json_decode($res, true);
		} else {
			//todo локальная версия
			$user_id = _num($_POST['user_id']);
			$res = array(
				'user_id' => $user_id ? $user_id : 982006
			);
		}

		if(!$viewer_id = _num($res['user_id']))
			jsonError('Ошибка при получении токена');

		if(!$u = _viewer($viewer_id))
			jsonError('Ошибка получения данных пользователя');

		//получение id приложения, в котором в последний раз был пользователь
		$sql = "SELECT `app_id`
				FROM `_vkuser_app`
				WHERE `viewer_id`=".$viewer_id."
				  AND `worker`
				ORDER BY `last_seen` DESC
				LIMIT 1";
		$app_id = _num(query_value($sql));

		_authSuccess($code, $viewer_id, $app_id);
		
		jsonSuccess();
		break;
}

if(!CODE)
	jsonError('Пользователь не авторизирован');



