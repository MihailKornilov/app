<?php

//условия, не требующие авторизации
switch(@$_POST['op']) {
	case 'login'://процесс авторизации пользователя
		if(!$code = _txt($_POST['code']))
			jsonError('Отсутствует код');

		$url = 'https://oauth.vk.com/access_token?client_id=6046182&client_secret=s4gT1VC1JKQpkG0JiifY&redirect_uri=https://nyandoma.ru/app&code='.$code;
		$res = file_get_contents($url);
		$res = json_decode($res, true);

		if(!$viewer_id = _num($res['user_id']))
			jsonError('Неудачное получение токена');

		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `viewer_id`=".$viewer_id."
				  AND `worker`
				ORDER BY `id`
				LIMIT 1";
		if(!$r = query_assoc($sql))
			jsonError('Пользователь не зарегистрирован');

		$sql = "UPDATE `_vkuser`
				SET `viewer_sid`='".$code."',
					`last_seen`=CURRENT_TIMESTAMP
				WHERE `id`=".$r['id'];
		query($sql);
		
		setcookie('viewer_sid', $code, time() + 2592000, '/');
		
		jsonSuccess();
		break;
}

if(!$sid = _txt(@$_COOKIE['viewer_sid']))
	jsonError('Пользователь не авторизирован');



