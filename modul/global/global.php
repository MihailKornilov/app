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

		'<body>';
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




function _global_script() {//������� � �����
	return
		//����� Global
//		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/global/global'.MIN.'.css?'.VERSION.'" />'.
//		'<script src="'.API_HTML.'/modul/global/global'.MIN.'.js?'.VERSION.'"></script>';
	'<link rel="stylesheet" type="text/css" href="modul/global/global.css" />';
//	'<script src="'.API_HTML.'/modul/global/global'.MIN.'.js?'.VERSION.'"></script>';
}






function _content() {//����������� ����������
	return
	'<div id="_content">'.
		'123'.
	'</div>';
}


