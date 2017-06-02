<?php
function _auth() {//����������� ����� iframe
	if($auth_key = @$_GET['auth_key']) {
		if(!$app_id = _num(@$_GET['api_id']))
			_appError('������ �����������.'.(SA ? ' ����������� ID ����������.' : ''));
		define('APP_ID', $app_id);

		if(!$viewer_id = _num(@$_GET['viewer_id']))
			_appError('������ �����������.'.(SA ? ' ����������� ID ������������.' : ''));
		define('VIEWER_ID', $viewer_id);

		if($auth_key != md5(APP_ID.'_'.$viewer_id.'_'._app('secret')))
			_appError('����������� �� ��������.');

		_authSuccess($auth_key, $viewer_id, $app_id);

		return;
	}

	if(!$code = _txt(@$_COOKIE['code']))
		_appError('����������� �� ��������.'.(SA ? ' ������ code.' : ''));
	if(!_authCache($code))
		_appError('����������� �� ��������.'.(SA ? ' �� �������� ������ �� code.' : ''));
}
function _appError($msg='���������� �� ���� ���������.') {//����� ��������� �� ������ ���������� � �����
	$html =
		'<!DOCTYPE html>'.
		'<html lang="ru">'.
			'<head>'.
				'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
				'<title>Error</title>'.

				'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
				'<script>VK.init(function() {},function() {},"5.60");</script>'.

				_global_script().

			'</head>'.
			'<body>'.
				'<div id="frameBody">'.
					'<iframe id="frameHidden" name="frameHidden"></iframe>'.

					'<div class="pad30 bg-gr1">'.
						'<div class="bg-fff pad30 bor-e8">'.
							'<div class="center grey mt40 mb40">'.
								$msg.
							'</div>'.
						'</div>'.
					'</div>'.

				'</div>'.
			'</body>'.
		'</html>';
	die($html);
}


function _header() {
	return
		'<!DOCTYPE html>'.
		'<html lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
//			'<title>'.APP_NAME.'</title>'.
			_global_script().
//			_api_scripts().
		'</head>'.

		'<body>';
}
