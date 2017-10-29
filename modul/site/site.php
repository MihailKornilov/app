<?php
/*
	������� �����������:
		1. ���� ���� GET code -> ������� �� �������� �����������
		2. ���� ��� code � Cookie -> ������� �� �������� �����������
*/

function _auth() {//����������� ����� ����
	if($code = @$_GET['code'])
		_authLogin($code);

	if(!CODE)
		_authLogin();

	if(!_authCache())
		_authLogin();

	if(isset($_GET['logout'])) {
		if(isset($_GET['app']))
			_authLogoutApp();
		else
			_authLogout(CODE, VIEWER_ID);
		header('Location:'.URL);
		exit;
	}

	_viewer();
}
function _authLogin($code='') {//����������� ������ ��� ����� ����� ���������
	setcookie('code', '', time() - 1, '/');//����� �����������

	$href = LOCAL ? URL.'&code='.md5(TIME)
			:
			'https://oauth.vk.com/authorize?'.
					 'client_id='.AUTH_APP_ID.
					'&display=page'.
					'&redirect_uri=https://nyandoma.ru/app'.
					'&scope=0'.
					'&response_type=code'.
					'&v=5.64';
	$html =
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>�����������</title>'.
		_global_script().
	'</head>'.

	'<body>'.

		'<div class="center mt40">'.
			'<div class="w1000 pad30 dib mt40">'.
				'<button class="vk'.($code ? ' _busy' : '').'"'.($code ? '' : ' onclick="location.href=\''.$href.'\'"').'>����� ����� VK</button>'.
				_localDopUserAuth($code).
			'</div>'.
		'</div>'.

	($code ?
		'<script>_authLogin("'.$code.'",'._num(@$_GET['user_id']).')</script>'
	: '').
	
	'</body>'.
	'</html>';

	die($html);
}
function _localDopUserAuth($code) {//������ ����� ������ ������������� ��� ������������ todo �� ��������
	if(!LOCAL)
		return '';

	$busy = $code ? ' _busy' : '';

	return
		'<br />'.
		'<br />'.
		'<button class="vk'.$busy.'"'.($code ? '' : ' onclick="location.href=\''.URL.'&code='.md5(TIME.'1382858').'&user_id=1382858\'"').'>����� ��� <b>������ ������������</b>: id=1382858</button>'.
		'<br />'.
		'<br />'.
		'<button class="vk'.$busy.'"'.($code ? '' : ' onclick="location.href=\''.URL.'&code='.md5(TIME.'5809794').'&user_id=5809794\'"').'>����� ��� <b>������ �����</b>: id=5809794</button>';
}




function _header() {
	return
		'<!DOCTYPE html>'.
		'<html lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
			'<title>'.(APP_ID ? _app(APP_ID, 'app_name') : '��� ����������').'</title>'.
			_global_script().
//			_api_scripts().
		'</head>'.

		'<body>'.
			_header_hat();
}
function _header_hat() {//������� ������ ����������-�����
	return
	'<div id="hat">'.
		'<p>'.
			(APP_ID ? _app(APP_ID, 'app_name') : '��� ����������').
			'<a href="'.URL.'&logout'.(APP_ID && !VIEWER_APP_ONE ? '&app' : '').'" class="fr white mt5">'.
				'<span class="dib mr20 pale">'.VIEWER_APP_NAME.'</span>'.
				'�����'.
	(PAGE_ID && !SA ? '<a id="page_setup" class="fr mt5 mr20'.(PAS ? ' color-aea' : '').'">Page setup</a>' : '').//todo ��������� ������
			'</a>'.
		'</p>'.
	'</div>';
}



function _appSpisok() {//������ ����������, ������� �������� ������������
	if(APP_ID)
		return '';

	$sql = "SELECT `app`.*
			FROM
				`_app` `app`,
				`_vkuser_app` `va`
			WHERE `app`.`id`=`va`.`app_id`
			  AND `viewer_id`=".VIEWER_ID."
			  AND `worker`
			ORDER BY `va`.`dtime_add`";
	if(!$spisok = query_arr($sql))
		return '���������� ���.';

	$send = '<div class="">';
	foreach($spisok as $r) {
		$send .=
			'<div class="pad10 bg-gr2 mar10 over2 curP" onclick="_appEnter('.$r['id'].')">'.
				'<span class="grey">'.$r['id'].'</span> '.
				$r['app_name'].
				'<div class="fr grey">'.FullData($r['dtime_add']).'</div>'.
			'</div>';
	}
	$send .= '</div>';

	return $send;
}
