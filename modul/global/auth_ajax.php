<?php

//�������, �� ��������� �����������
switch(@$_POST['op']) {
	case 'login'://������� ����������� ������������
		if(!$code = _txt($_POST['code']))
			jsonError('����������� ���');

		if(!LOCAL) {
			$url = 'https://oauth.vk.com/access_token?'.
						'client_id='.AUTH_APP_ID.
					   '&client_secret='.AUTH_APP_SECRET.
					   '&redirect_uri=https://nyandoma.ru/app'.
					   '&code='.$code;
			if(!$res = @file_get_contents($url))
				jsonError('���������� ������� ��������� ������');

			$res = json_decode($res, true);
		} else {
			//todo ��������� ������
			$res = array(
				'user_id' => 982006
			);
		}

		if(!$user_id = _num($res['user_id']))
			jsonError('������ ��� ��������� ������');

		if(!$u = _user($user_id))
			jsonError('������ ��������� ������ ������������');

		//��������� id ����������, � ������� � ��������� ��� ��� ������������
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
	jsonError('������������ �� �������������');



