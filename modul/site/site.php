<?php
function _auth() {//����������� ����� ����
	if($code = @$_GET['code'])
		_authLogin($code);
	if(!_viewerConst())
		_authLogin();
}
function _authLogin($code='') {//����������� ������ ��� ����� ����� ���������
	$href = 'https://oauth.vk.com/authorize?'.
					 'client_id=6046182'.
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
			'</div>'.
		'</div>'.

	($code ?
		'<script>_authLogin("'.$code.'")</script>'
	: '').
	
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

		'<body>'.
			_header_hat();
}
function _header_hat() {//������� ������ ����������-�����
	return
	'<div id="hat">'.
		'<p>'.
			'������� ������'.
			'<a href="'.URL.'&logout" class="fr white mt5">�����</a>'.
		'</p>'.
	'</div>';
}
function _footer() {
	return
	'</body></html>';
}


