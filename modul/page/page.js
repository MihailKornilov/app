var VK_BODY,       //фрейм VK для изменения высоты $('body')
	VK_BODY_H = 0, //текущая высота фрейма VK
	VK_SCROLL = 0,

	_faceTest = function() {//определение, как загружена страница: iframe или сайт
		if(_cookie('local'))
			return;

		//если текущее значение не совпадает, то установка и перезагрузка страницы
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

	_fbhs = function(h) {//коррекция высоты окна в VK
		if(_cookie('local'))
			return;
		if(_cookie('face') != 'iframe')
			return;

		if(!VK_BODY) {
			VK_BODY = $('body');
			window.frame0.onresize = _fbhs;

			//установка прокрутки окна в верхнее положение
			VK.callMethod('scrollWindow', 0);

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
	};

	$(document).ready(_faceTest);
