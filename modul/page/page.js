var VK_BODY,       //����� VK ��� ��������� ������ $('body')
	VK_BODY_H = 0, //������� ������ ������ VK
	VK_SCROLL = 0,

	_faceTest = function() {//�����������, ��� ��������� ��������: iframe ��� ����
		if(_cookie('local'))
			return;

		//���� ������� �������� �� ���������, �� ��������� � ������������ ��������
		var face = window == window.top ? 'site' : 'iframe';

		if(_cookie('face') != face) {
			_cookie('face', face);
			location.reload();
			return;
		}
		_fbhs();
	},
	_faceGo = function(face) {
		_cookie('face', face);
		location.reload();
	},

	_fbhs = function(h) {//��������� ������ ���� � VK
		if(_cookie('local'))
			return;
		if(_cookie('face') != 'iframe')
			return;

		if(!VK_BODY) {
			VK_BODY = $('body');
			window.frame0.onresize = _fbhs;

//			VK.callMethod('scrollWindow', _scroll());

			VK.callMethod('scrollSubscribe');
			VK.addCallback('onScroll', function(top) {
				VK_SCROLL = top;
			});
		}

		if(typeof h != 'number') {
			VK_BODY.height('auto');
			h = VK_BODY.height();
		}

		_forEq($('._dialog'), function(sp) {
			var top = _num(sp.css('top').split('px')[0]),
				dH = sp.height() + top + 20;
			if(h < dH)
				h = dH;
		});

		if(VK_BODY_H == h)
			return;

		VK_BODY_H = h;

		VK.callMethod('resizeWindow', 1000, h);
	},

	_authVk = function(but) {//����������� ����� VK
		but = $(but);
		but.addClass('_busy');

		VK.Auth.login(function(res) {//�������� ������� �����������
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
			});
		});
	},
	_authVkLocal = function(but) {//����������� ����� VK - ��������� ������
		$(but).addClass('_busy');
		_post({op:'auth_vk_local'}, function(res) {
			if(res.success) {
				location.href = URL;
				return;
			}
			$(but).removeClass('_busy');
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
		if(pas)
			return;
		_elemActivate(ELM, {});
	};

	$(document).ready(_faceTest);
