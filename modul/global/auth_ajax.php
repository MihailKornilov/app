<?php
/* �������, �� ��������� ����������� */

switch(@$_POST['op']) {
	case 'auth_vk'://����������� ������������ �� VK
		$session = @$_POST['session'];
		$valid_keys = array('expire', 'mid', 'secret', 'sid');

		$key = '';
		foreach($valid_keys as $k)
			$key .= $k.'='.@$session[$k];

		$sig = md5($key.AUTH_APP_SECRET);

		if($sig != $session['sig'])
			jsonError('���������� �����������');

		//��������, ���� �� ������������ � ����
		$vkUser_id = _num($session['mid']);
		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=".$vkUser_id."
				LIMIT 1";
		if(!$user_id = _num(query_value($sql))) {
			$user_id = _userVkUpdate($vkUser_id);//���� ��� - ��������� ������ �� VK
			_userImageMove();
		}

		_authSuccess($sig, $user_id);

		jsonSuccess();
		break;
	case 'auth_vk_local'://����������� ������������ �� VK - ��������� ������
		$sql = "SELECT `id`
				FROM `_user`
				WHERE `vk_id`=982006
				LIMIT 1";
		if(!$user_id = _num(query_value($sql)))
			jsonError('������������ �� ������');

		_authSuccess('local'.$user_id, $user_id);

		jsonSuccess();
		break;
}

if(!CODE)
	jsonError('������������ �� �������������');



