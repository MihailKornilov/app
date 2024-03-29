<?php
/* Условия, не требующие авторизации */

switch(@$_POST['op']) {
	case 'auth_vk'://авторизация пользователя по VK
		$session = @$_POST['session'];
		$valid_keys = array('expire', 'mid', 'secret', 'sid');

		$key = '';
		foreach($valid_keys as $k)
			$key .= $k.'='.@$session[$k];

		$sig = md5($key.AUTH_APP_SECRET);

		if($sig != $session['sig'])
			jsonError('Неуспешная авторизация');

		//проверка, есть ли пользователь в базе
		$vkUser_id = _num($session['mid']);
		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=".$vkUser_id."
				LIMIT 1";
		if(!$user_id = _num(DB1::value($sql))) {

			//если пользователя в базе нет - получение данных из VK
			if(!$user_id = _userVkUpdate($vkUser_id))
				jsonError('Не удалось получить данные пользователя из ВК');

			define('USER_ID', $user_id);
		}

		$sql = "SELECT `app_id_last`
				FROM `_user`
				WHERE `id`=".$user_id;
		$app_id_last = _num(DB1::value($sql));

		_authSuccess($sig, $user_id, $app_id_last);

		jsonSuccess();
		break;
	case 'auth_vk_local'://авторизация пользователя по VK - локальная версия
		$sql = "SELECT *
				FROM `_user`
				WHERE `vk_id`=982006
				LIMIT 1";
		if(!$user = DB1::assoc($sql))
			jsonError('Пользователь не найден');

		_authSuccess(md5('local'.$user['id']), $user['id'], $user['app_id_last']);

		jsonSuccess();
		break;
}

//if(!CODE)
//	jsonError('Пользователь не авторизирован');



