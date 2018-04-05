var VK_FRAME,       //фрейм для изменения высоты контакта $('#frame0')
	VK_FRAME_H = 0,
	VK_SCROLL = 0,

	_faceTest = function() {//определение, как загружена страница: iframe или сайт
		if(_cookie('local'))
			return;

		//если текущее значение не совпадает, то установка и перезагрузка страницы
		var face = window == window.top ? 'site' : 'iframe';

		if(_cookie('face') != face) {
			_cookie('face', face);
		//	location.reload();
			return;
		}
		if(face == 'iframe') {
			VK_FRAME = $('#frame0');
			_fbhs();
			window.frame0.onresize = _fbhs;
		}
	},
	_faceGo = function(face) {
		_cookie('face', face);
		location.reload();
	},

	_fbhs = function() {//коррекция высоты окна в VK
		if(_cookie('local'))
			return;
		if(_cookie('face') != 'iframe')
			return;

		var h;

		VK_FRAME.height('auto');
		h = VK_FRAME.height();

		if(VK_FRAME_H == h)
			return;

		VK_FRAME_H = h;

		VK.callMethod('resizeWindow', 1000, h);
	},

	_authVk = function(but) {//авторазация через VK
		but = $(but);
		but.addClass('_busy');

		VK.Auth.login(function(res) {//проверка статуса авторизации
			but.removeClass('_busy');
			if(res.status != 'connected')
				return;

			//вход на сайт
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
	_authVkLocal = function(but) {//авторазация через VK - локальная версия
		$(but).addClass('_busy');
		_post({op:'auth_vk_local'}, function(res) {
			if(res.success) {
				location.href = URL;
				return;
			}
			$(but).removeClass('_busy');
		});
	},
	_appEnter = function(app_id) {//вход в приложение из списка приложений
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, 'reload');
	},

	_pageAct = function(pas) {//активация элементов после вывода страницы
		if(pas)
			return;
		_elemActivate(ELM, {});
	};

	$(document).ready(_faceTest);
