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

		if(!$u = _viewer($viewer_id))
			jsonError('������ ��������� ������ ������������');

		//������� ���� ���������� ��������� ������������
		$sql = "UPDATE `_vkuser`
				SET `last_seen`=CURRENT_TIMESTAMP
				WHERE `id`=".$u['id'];
		query($sql);

		//��������� id ����������, � ������� � ��������� ��� ��� ������������
		$sql = "SELECT `app_id`
				FROM `_vkuser_auth`
				WHERE `viewer_id`=".$viewer_id."
				ORDER BY `id` DESC
				LIMIT 1";
		if(!$app_id = _num(query_value($sql))) {
			//���� ������ ��� �� ����, ������ ��������� id ����������, ��� �������, ���� ������������ ���������� ����� ���� ����������, ����� 0
			$sql = "SELECT `app_id`,1
					FROM `_vkuser_app`
					WHERE `viewer_id`=".$viewer_id."
					  AND `worker`
					ORDER BY `last_seen` DESC";
			if($app = query_ass($sql))
				if(count($app) == 1)
					$app_id = _num(key($app));
		}

		$sql = "INSERT INTO `_vkuser_auth` (
					`viewer_id`,
					`app_id`,
					`code`
				) VALUES (
					".$viewer_id.",
					".$app_id.",
					'".$code."'
				)";
		query($sql);

		setcookie('code', $code, time() + 2592000, '/');
		
		jsonSuccess();
		break;
}

if(!$code = _txt(@$_COOKIE['code']))
	jsonError('������������ �� �������������');



