<?php
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
			_header_hat().

		'<a href="https://oauth.vk.com/authorize?client_id=6046182&display=page&redirect_uri=https://nyandoma.ru/app&scope=0&response_type=token&v=5.64">�����������</a>';
}
function _header_hat() {//������� ������ ����������-�����
	return
	'<div id="hat">'.
		'<p>������� ������</p>'.
	'</div>';
}
function _footer() {
	return
	'</body></html>';
}


