<?php
function _face() {//�����������, ��� ��������� ��������: iframe ��� ����
	$face = 'site';

	if(@$_COOKIE['face'] == 'iframe')
		$face = 'iframe';
	if(!empty($_GET['referrer']))
		$face = 'iframe';

	setcookie('face', $face, time() + 2592000, '/');

	define('FACE', $face);
	define('SITE', FACE == 'site' ? 'site' : '');
	define('IFRAME', FACE == 'iframe');
}
function _saDefine() {//��������� ����� ����������������� SA
	//������ ������������� - SA
	$SA[1] = true;  //������ ��������
//	$SA[20912036] = true;//�����

	define('SA', isset($SA[USER_ID]) ? 1 : 0);

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	}
}

/* ---=== ����������� ===--- */
function _auth() {//����������� ����� ����
	if(!$r = _cache()) {
		$sql = "SELECT *
				FROM `_user_auth`
				WHERE `code`='".addslashes(CODE)."'
				LIMIT 1";
		if($r = query_assoc($sql))
			_cache(array(
				'user_id' => $r['user_id'],
				'app_id' => $r['app_id']
			));
	}

	define('USER_ID', _num(@$r['user_id']));
	define('APP_ID', _num(@$r['app_id']));

/*
	//SA: ���� �� ����� ������� ������������
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
			_cache('clear', '_auth');

			header('Location:'.URL);
			exit;
		}
	}
*/
}
function _authLogin() {//����������� ������ ��� ����� ����� ���������
	if(CODE)
		return '';

	return
	'<div class="center mt40">'.
		'<div class="w1000 pad30 dib mt40">'.
			'<button class="vk" onclick="_authVk'.(LOCAL ? 'Local' : '').'(this)">����� ����� VK</button>'.
		'</div>'.
	'</div>'.
(!LOCAL ?
	'<script src="https://vk.com/js/api/openapi.js?152"></script>'.
	'<script>VK.init({apiId:'.AUTH_APP_ID.'})</script>'
: '');
}
function _authSuccess($code, $user_id, $app_id=0) {//�������� ������ �� �������� �����������
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
function _authLogout() {//����� �� ����������, ���� ���������
	if(!isset($_GET['logout']))
		return;
	if(!CODE)
		return;

	_cache('clear', '_auth');
	_cache('clear', '_pageCache');
	_cache('clear', '_userCache'.USER_ID);

	//����� ������ �� ���������� � ��������� � ������ ����������
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
	header('Location:'.URL);
	exit;
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
	if(!_authCache__())
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

				_html_script().

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





/* ---=== ���������� ===--- */
function _html() {
	return
	'<!DOCTYPE html>'.
	'<html lang="ru">'.

	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>'._html_title().'</title>'.
		_html_script().
	'</head>'.

	'<body class="'.(CODE && FACE ? 'site' : '').'">'.
		(IFRAME ? '<iframe id="frame0" name="frame0"></iframe>' : '').

		_authLogin().

		_html_hat().
		_pasMenu().
		_app_content().

		_debug().
	'</body></html>';
}
function _html_title() {
	if(!CODE)
		return '�����������';
	if(!APP_ID)
		return '��� ����������';

	return _app(APP_ID, 'name');
}
function _html_script() {//������� � �����
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
	'<script src="js/autosize.js?3"></script>'.

(CODE ?
	'<link rel="stylesheet" type="text/css" href="css/jquery-ui'.MIN.'.css?3" />'.
	'<script src="js/jquery-ui.min.js?3"></script>'.

	'<script src="js/jquery.mjs.nestedSortable'.MIN.'.js?1"></script>'.

	'<script src="js/lodash.min.js"></script>'.
	'<link rel="stylesheet" href="css/gridstack'.MIN.'.css" />'.
	'<script src="js/gridstack'.MIN.'.js?"></script>'.
	'<script src="js/gridstack.jQueryUI'.MIN.'.js"></script>'
: '').

	'<link rel="stylesheet" type="text/css" href="modul/global/global'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/global/global'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/page/page'.MIN.'.js?'.VERSION.'"></script>'.

(CODE ?
	'<link rel="stylesheet" type="text/css" href="modul/element/element'.MIN.'.css?'.VERSION.'" />'.
	'<script src="modul/element/element'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/block/block'.MIN.'.js?'.VERSION.'"></script>'.

	'<script src="modul/spisok/spisok'.MIN.'.js?'.VERSION.'"></script>'
: '').

	_debug('style');
}
function _html_hat() {//������� ������ ���������� ��� �����
	if(!CODE)
		return '';
	if(!SITE)
		return '';

	return
	'<div id="hat">'.
		'<div class="w1000 mara pt3">'.
			'<div class="dib mt5 fs22">'._html_title().'</div>'.

			'<a href="'.URL.'&logout" class="fr white mt10">'.
				'<span class="dib mr20 pale">'.USER_NAME.'</span>'.
				'�����'.
			'</a>'.

			'<div class="fr w300 mt8 r mr20">'.
				_hat_but_sa().
				_hat_but_page().
				_hat_but_pas().
			'</div>'.
		'</div>'.
	'</div>';
}
function _hat_but_sa() {//����������� ������ ������ �������
	if(!SA)
		return '';
	if(!APP_ID)
		return '';

	if(_page('cur') == 1)
		return '';

	return '<button class="vk small red" onclick="location.href=\''.URL.'&p=1\'">SA</button>';
}
function _hat_but_page() {//����������� ������ ������ �������
	if(!APP_ID)
		return '';

	if(_page('cur') == 12)
		return '';

	return '<button class="vk small ml10" onclick="location.href=\''.URL.'&p=12\'">C�������</button>';
}
function _hat_but_pas() {//����������� ������ ��������� ��������
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

function _app_list() {//������ ����������, ������� �������� ������������
	if(!USER_ID)
		return '';
	if(APP_ID)
		return '����� ����� �������� ������ ����������.';

	$sql = "SELECT *
			FROM `_user_app`
			WHERE `user_id`=".USER_ID."
			ORDER BY `dtime_add`";
	if(!$spisok = query_arr($sql))
		return '���������� �� �������.';

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="pad10 bg-gr2 mb10 over2 curP" onclick="_appEnter('.$r['id'].')">'.
				'<span class="grey">'.$r['id'].'</span> '.
				_app($r['app_id'], 'name').
				'<div class="fr grey">'.FullData($r['dtime_add']).'</div>'.
			'</div>';
	}

	return $send;
}
function _app_create() {//�������������� �������� ����������, ���� ������������ ������� ����� �� ����
	/*
		���������� �������� ������ � �����.
		id ���������� �� 1001 � �� ����� ���� ������ 2.000.000, ��� ��� �������� ���������� VK ���� �� 2���
	*/

	if(APP_ID)
		return '';


	return '';

	$sql = "SELECT MAX(`id`)+1
			FROM `_app`
			WHERE `id`<2000000";
	if(!$app_id = query_value($sql))
		$app_id = 1001;

	$sql = "INSERT INTO `_app` (
				`id`,
				`name`,
				`user_id_add`
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

	_cache('clear', '_auth');
	header('Location:'.URL);

	return '<div class="_empty mt20">���������� ���.</div>';
}
function _app_content() {//����������� ����������
	if(!CODE)
		return '';
	if(!USER_ID)
		return '';

	return
	'<div id="_content" class="block-content-page '.SITE.'">'.
		_pageShow(_page('cur')).
	'</div>';
}

function _contentMsg($msg='') {
	if(!$msg) {
		$_GET['p'] = 0;
		$msg = '�������������� ��������<br><br><a href="'.URL.'&p='._page('cur').'">������� �� �������� �� ���������</a>';
	}
	return '<div class="_empty mar20">'.$msg.'</div>';
}






