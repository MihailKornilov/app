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
	_authVk = function(but) {//����������� ����� VK
		but = $(but);
		but.addClass('_busy');

		VK.Auth.login(function(res) {//�������� ������� �����������
			console.log(res);
			but.removeClass('_busy');
			if(res.status != 'connected')
				return;

			//���� �� ����
			var send = {
				op:'auth_vk',
				session:res.session
			};
			but.addClass('_busy');
			_post(send, function(res) {
				if(res.success) {
					location.href = URL;
					return;
				}
				but.removeClass('_busy');
				console.log(res);
			});
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
