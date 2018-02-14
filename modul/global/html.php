<?php
/*
	������� �����������:
		1. ���� ���� $_GET['code'] -> ������� �� �������� �����������
		2. ���� ��� code � Cookie -> ������� �� �������� �����������
*/



function _face() {//�����������, ��� ��������� ��������: iframe ��� ����
//	print_r(debug_backtrace(0));
	$face = 'site';

	if(@$_COOKIE['face'] == 'iframe')
		$face = 'iframe';
	if(!empty($_GET['referrer']))
		$face = 'iframe';

	setcookie('face', $face, time() + 2592000, '/');

	define('FACE', $face);
	define('SITE', FACE == 'site');
	define('IFRAME', FACE == 'iframe');
//	echo FACE;
}

/* ---=== ����������� ===--- */
function _auth() {//����������� ����� ����
	if(!CODE)
		_authLogin();

	if(!_authCache())
		_authLogin();

	_authLogout();

/*	//SA: ���� �� ����� ������� ������������
	if(SA && $user_id = _num(@$_GET['user_id'])) {
		$sql = "SELECT COUNT(*)
				FROM `_vkuser`
				WHERE `viewer_id`=".$user_id;
		if(query_value($sql)) {
			$sql = "UPDATE `_vkuser_auth`
					SET `viewer_id_show`=".$user_id.",
						`app_id`=0
					WHERE `code`='".CODE."'";
			query($sql);

			_cache('clear', '_userCache'.USER_ID);
			_cache('clear', '_viewer'.$user_id);
			_cache('clear', '_authCache');

			header('Location:'.URL);
			exit;
		}
	}
*/

//	if(!_user())
//		_authLogin();
}
function _authCache() {//��������� ������ ����������� �� ���� � ��������� �������� id ������������ � ����������
	if(!CODE)
		return false;
	if(defined('USER_ID'))
		return true;

	if(!$r = _cache()) {
		$sql = "SELECT *
				FROM `_user_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if(!$r = query_assoc($sql))
			return false;

		_cache(array(
			'user_id' => $r['user_id'],
			'app_id' => $r['app_id']
		));
	}

	//���� ���������������, ���� SA ������������� �� ����� ������� ������������
//	define('VIEWER_ID_SHOWER', $r['viewer_id_show'] && _sa($r['user_id']) ? _num($r['user_id']) : 0);//id ������������, ������� �������
//	define('USER_ID', _num($r['user_id'.(VIEWER_ID_SHOWER ? '_show' : '')]));
	define('USER_ID', _num($r['user_id']));
	define('APP_ID', _num($r['app_id']));

//	if(!_user())
//		return false;

	return true;
}
function _authLogin() {//����������� ������ ��� ����� ����� ���������
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
				'<button class="vk" onclick="_authVk(this)">����� ����� VK</button>'.
				'<br>'.
				'<br>'.
				'<button class="vk" onclick="VK.Auth.getLoginStatus(function(res){console.log(res)})">getLoginStatus</button>'.
				'<br>'.
				'<br>'.
				'<button class="vk" onclick="VK.Auth.logout(function(res){console.log(res)})">logout</button>'.
			'</div>'.
		'</div>'.

	'<script src="https://vk.com/js/api/openapi.js?152"></script>'.
	'<script>VK.init({apiId:'.AUTH_APP_ID.'})</script>'.

	'</body>'.
	'</html>';

	die($html);
}


function _authSuccess($code, $user_id, $app_id) {//�������� ������ �� �������� �����������
	$ip = $_SERVER['REMOTE_ADDR'];
	$browser = _txt($_SERVER['HTTP_USER_AGENT']);
	$sql = "INSERT INTO `_user_auth` (
				`user_id`,
				`app_id`,
				`code`,
				`ip`,
				`browser`
			) VALUES (
				".$user_id.",
				".$app_id.",
				'".$code."',
				'".$ip."',
				'".addslashes($browser)."'
			)";
	query($sql);

	//������� ���� ���������� ��������� ������������
	$sql = "UPDATE `_user`
			SET `dtime_last`=CURRENT_TIMESTAMP
			WHERE `id`=".$user_id;
	query($sql);
/*
	//������� ���� ���������� ��������� ����������. ���� ������������ ������� ������ � ����������, �� �������� ���������� ��� ����
	if($app_id) {
		$sql = "SELECT `id`
		        FROM `_vkuser_app`
				WHERE `app_id`=".$app_id."
				  AND `viewer_id`=".$user_id;
		$id = _num(query_value($sql));

		$sql = "INSERT INTO `_vkuser_app` (
					`id`,
					`viewer_id`,
					`app_id`,
					`last_seen`
				) VALUES (
					".$id.",
					".$user_id.",
					".$app_id.",
					CURRENT_TIMESTAMP
				) ON DUPLICATE KEY UPDATE
					`last_seen`=CURRENT_TIMESTAMP";
		query($sql);
	}
*/
	setcookie('code', $code, time() + 2592000, '/');

	if(LOCAL)
		setcookie('local', 1, time() + 2592000, '/');
}
function _authLogout() {//����� �� ������ ����������
	if(!isset($_GET['logout']))
		return;
	if(!CODE)
		return;
	if(!USER_ID)
		return;

	_cache('clear', '_authCache');
	_cache('clear', '_pageCache');
	_cache('clear', '_userCache'.USER_ID);

	if(APP_ID) {
		$sql = "UPDATE `_user_auth`
				SET `app_id`=0
				WHERE `code`='".CODE."'";
		query($sql);
		header('Location:'.URL);
		exit;
	}


	$sql = "DELETE FROM `_user_auth` WHERE `code`='".addslashes(CODE)."'";
	query($sql);


	setcookie('code', '', time() - 1, '/');
}


function i_auth() {//����������� ����� iframe
	if($auth_key = @$_GET['auth_key']) {
		if(!$app_id = _num(@$_GET['api_id']))
			_appError('������ �����������.'.(SA ? ' ����������� ID ����������.' : ''));
		define('APP_ID', $app_id);

		if(!$user_id = _num(@$_GET['user_id']))
			_appError('������ �����������.'.(SA ? ' ����������� ID ������������.' : ''));
		define('USER_ID', $user_id);

		if($auth_key != md5(APP_ID.'_'.$user_id.'_'._app(APP_ID, 'secret')))
			_appError('����������� �� ��������.');

		_authSuccess($auth_key, $user_id, $app_id);

		if(!_user())
			_appError('������ ������������ �� ��������.');

		if(!USER_WORKER)
			_appError('��� ������� � ����������.');

		return;
	}

	if(!CODE)
		_appError('����������� �� ��������.'.(SA ? ' ������ code.' : ''));
	if(!_authCache())
		_appError('����������� �� ��������.'.(SA ? ' �� �������� ������ �� code.' : ''));
	if(!USER_WORKER)
		_appError('��� ������� � ����������.');
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


function i_header() {
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

		'<body class="site">'.
			'<iframe id="frame0" name="frame0"></iframe>'.
			_header_hat();
}
function _header_hat() {//������� ������ ����������-�����
	return
	'<div id="hat"'.(VIEWER_ID_SHOWER ? ' class="show"' : '').'>'.
		'<div class="w1000 mara pt3">'.
			'<div class="dib mt5 fs22">'.(APP_ID ? _app(APP_ID, 'app_name') : '��� ����������').'</div>'.

			'<a href="'.URL.'&logout'.(APP_ID && !USER_APP_ONE ? '&app' : '').'" class="fr white mt10">'.
				'<span class="dib mr20 pale">'.USER_NAME.'</span>'.
				'�����'.
			'</a>'.

			'<div class="fr w300 mt8 r mr20">'.
				_header_but_sa().
				_header_but_page().
				_header_but_pas().
			'</div>'.
		'</div>'.
	'</div>';
}
function _header_but_sa() {//����������� ������ ������ �������
	if(!SA)
		return '';
	if(!APP_ID)
		return '';

	if(_page('cur') == 1)
		return '';

	return '<button class="vk small red" onclick="location.href=\''.URL.'&p=1\'">SA</button>';
}
function _header_but_page() {//����������� ������ ������ �������
	if(!APP_ID)
		return '';

	if(_page('cur') == 12)
		return '';

	return '<button class="vk small ml10" onclick="location.href=\''.URL.'&p=12\'">C�������</button>';
}
function _header_but_pas() {//����������� ������ ��������� ��������
	if(!APP_ID)
		return '';

	if(!$page_id = _page('cur'))
		return '';

	if(!$page = _page($page_id))
		return '';

	if($page['sa'] && !SA)
		return '';

	if(!$page['app_id'] && !SA)
		return '';

	return '<button id="page_setup" class="vk small fr ml10 '.(PAS ? 'orange' : 'grey').'">Page setup</button>';
}


function _appSpisok() {//������ ����������, ������� �������� ������������
	if(APP_ID)
		return '';

	$sql = "SELECT `app`.*
			FROM
				`_app` `app`,
				`_vkuser_app` `va`
			WHERE `app`.`id`=`va`.`app_id`
			  AND `viewer_id`=".USER_ID."
			  AND `worker`
			ORDER BY `va`.`dtime_add`";
	if(!$spisok = query_arr($sql))
		return _appCreate();

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
function _appCreate() {//�������������� �������� ����������, ���� ������������ ������� ����� �� ����
	/*
		���������� �������� ������ � �����.
		id ���������� �� 1001 � �� ����� ���� ������ 2.000.000, ��� ��� �������� ���������� VK ���� �� 2���
	*/

	$sql = "SELECT MAX(`id`)+1
			FROM `_app`
			WHERE `id`<2000000";
	if(!$app_id = query_value($sql))
		$app_id = 1001;

	$sql = "INSERT INTO `_app` (
				`id`,
				`app_name`,
				`viewer_id_add`
			) VALUES (
				".$app_id.",
				'���������� ".$app_id."',
				".USER_ID."
			)";
	query($sql);

	$sql = "INSERT INTO `_vkuser_app` (
				`viewer_id`,
				`app_id`,
				`last_seen`,
				`admin`,
				`worker`
			) VALUES (
				".USER_ID.",
				".$app_id.",
				CURRENT_TIMESTAMP,
				1,
				1
			)";
	query($sql);

	$sql = "UPDATE `_vkuser_auth`
			SET `app_id`=".$app_id."
			WHERE `code`='".CODE."'
			  AND `viewer_id`=".USER_ID;
	query($sql);

	_cache('clear', '_authCache');
	header('Location:'.URL);

	return '<div class="_empty mt20">���������� ���.</div>';
}







function _sa($user_id) {//�������� ������������ �� ������ SA
	//������ ������������� - SA
	$SA[982006] = true;  //������ ��������
//	$SA[20912036] = true;//�����

	return isset($SA[_num($user_id)]) ? 1 : 0;
}
function _saSet() {//��������� ����� �������������������
	define('SA', 0); return;


	if(!_authCache()) {
		define('SA', 0);
		return;
	}

//	define('SA', _sa(VIEWER_ID_SHOWER ? VIEWER_ID_SHOWER : USER_ID));
	define('SA', _sa(USER_ID));

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	}
}

function _global_script() {//������� � �����
	//���������� ������ ��� �������� �������� ajax
	$GET_ARR = '';
	foreach($_GET as $i => $v)
		if($v)
			$GET_ARR .= '&'.$i.'='.$v;

	return
	//������������ ������ � ��������
	(SA ? '<script src="js/errors.js"></script>' : '').

	'<script>'.
		'var URL="'.URL.'",'.
			'AJAX="'.APP_HTML.'/ajax.php?'.TIME.$GET_ARR.'",'.
			'SA='.SA.','.
			'PAGE_ID='._page('cur').';'.
	'</script>'.

	'<script src="js/jquery-3.2.1.min.js?3"></script>'.
	'<link rel="stylesheet" type="text/css" href="css/jquery-ui'.MIN.'.css?3" />'.
	'<script src="js/jquery-ui.min.js?3"></script>'.
	'<script src="js/autosize.js?3"></script>'.
	'<script src="js/jquery.mjs.nestedSortable'.MIN.'.js?1"></script>'.

	'<script src="js/lodash.min.js"></script>'.
	'<link rel="stylesheet" href="css/gridstack'.MIN.'.css" />'.
	'<script src="js/gridstack'.MIN.'.js?"></script>'.
	'<script src="js/gridstack.jQueryUI'.MIN.'.js"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/global/global'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/global/global'.MIN.'.js?'.VERSION.TIME.'"></script>'.

	'<link rel="stylesheet" type="text/css" href="modul/element/element'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/element/element'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/page/page'.MIN.'.js?'.VERSION.TIME.'"></script>'.

	'<script src="modul/block/block'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/spisok/spisok'.MIN.'.js?'.VERSION.'"></script>'.

	_debug('style');
}




function _content() {//����������� ����������
	return
	'<div id="_content" class="block-content-page'.(SITE ? ' site' : '').'">'.
		(APP_ID ? _pageShow(_page('cur')) : _appSpisok()).
	'</div>';
}
function _contentMsg($msg='') {
	if(!$msg) {
		$_GET['p'] = 0;
		$msg = '�������������� ��������<br><br><a href="'.URL.'&p='._page('cur').'">������� �� �������� �� ���������</a>';
	}
	return '<div class="_empty mar20">'.$msg.'</div>';
}
function _footer() {
	return '</body></html>';
}






