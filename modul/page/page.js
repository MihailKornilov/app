var _faceTest = function() {//�����������, ��� ��������� ��������: iframe ��� ����
		if(_cookie('local'))
			return;
		//���� ������� �������� �� ���������, �� ��������� � ������������ ��������
		var face = window == window.top ? 'site' : 'iframe';
		if(_cookie('face') == face)
			return;
		_cookie('face', face);
		location.reload();
	},
	_faceGo = function(face) {
		_cookie('face', face);
		location.reload();
	},
	_authLogin = function(url) {//����������� ������������ �� ���� �� �����
		var send = {
			op:'login',
			code:code
		};
		_post(url, function(res) {
			//location.href = URL;
			console.log(res);
		});
	},
	_loginVk = function(but) {
		but = $(but);
		but.addClass('_busy');

		VK.Auth.getLoginStatus(function(res){
			console.log(res)
		});
		return;

		VK.Auth.login(function(res) {
			console.log(res)
		});
	},
	_appEnter = function(app_id) {//���� � ���������� �� ������ ����������
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, 'reload');
	},

	_pageAct = function(pas) {//��������� ��������� ����� ������ ��������
		_elemActivate(ELM, {}, pas);
	};

	$(document).ready(_faceTest);
