<?php

//�������, �� ��������� �����������
switch(@$_POST['op']) {
	case 'login'://������� ����������� ������������
		if(!$code = _txt($_POST['code']))
			jsonError('����������� ���');

		$url = 'https://oauth.vk.com/access_token?'.
					'client_id='.AUTH_APP_ID.
				   '&client_secret='.AUTH_APP_SECRET.
				   '&redirect_uri=https://nyandoma.ru/app'.
				   '&code='.$code;
		if(!$res = @file_get_contents($url))
			jsonError('���������� ������� ��������� ������');

		$res = json_decode($res, true);

		if(!$viewer_id = _num($res['user_id']))
			jsonError('������ ��� ��������� ������');

		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `viewer_id`=".$viewer_id."
				LIMIT 1";
		if(!$r = query_assoc($sql))
			jsonError('������������ �� ���������������');

		$sql = "UPDATE `_vkuser`
				SET `code`='".$code."',
					`last_seen`=CURRENT_TIMESTAMP
				WHERE `id`=".$r['id'];
		query($sql);
		
		setcookie('code', $code, time() + 2592000, '/');
		
		jsonSuccess();
		break;
}

if(!$code = _txt(@$_COOKIE['code']))
	jsonError('������������ �� �������������');



