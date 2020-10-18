var FB,          //фрейм VK для изменения высоты $('body')
	FBH_CUR = 0, //текущая высота врейма, установленная в последний раз
	VK_SCROLL = 0,

	_faceTest = function() {//определение, как загружена страница: iframe или сайт
		if(_cookie('local'))
			return;

		//если текущее значение не совпадает, то установка и перезагрузка страницы
		var face = window == window.top ? 'site' : 'iframe';

		if(_cookie('face') != face) {
			_cookie('face', face);
//			location.reload();
			return;
		}

		if(face != 'iframe')
			return;

		//инициализация фрейма
		FB = $('body');

		window.frame0.onresize = _fbhs;

		//установка прокрутки окна в верхнее положение
		VK.callMethod('scrollWindow', 0);

		VK.callMethod('scrollSubscribe');
		VK.addCallback('onScroll', function(top) {
			VK_SCROLL = top;
		});

		_fbhs();

		//обновление высоты фрейма происходит всегда в конце каждого скрипта
		$(window).on('click', function() {
			setTimeout(_fbhs, 0)
		});
	},
	_faceGo = function(face) {
		_cookie('face', face);
		location.reload();
	},

	_fbhs = function() {//FrameBodyHeightSet - установка высоты фрейма в ВК
		if(_cookie('local'))
			return;
		if(_cookie('face') != 'iframe')
			return;

		var h = FB.height();

		//проверка, чтобы диалоговое окно не уходило за фрейм
		_forEq($('._dialog'), function(sp) {
			var top = sp.offset().top,
				dH = top + Math.round(sp.height()) + 50;
			if(h < dH)
				h = dH;
		});

		//проверка, чтобы содержание выпадающего списка не выходило за фрейм
		_forEq($('._select.rs .select-res'), function(sp) {
			var dH = sp.offset().top + 250 + 50;
			if(h < dH)
				h = dH;
		});

		//проверка, чтобы содержание _dropdown не выходило за фрейм
		_forEq($('._dropdown.rs .dd-list'), function(sp) {
			var dH = sp.offset().top + Math.round(sp.height()) + 50;
			if(h < dH)
				h = dH;
		});

		//проверка, чтобы календарь не выходил за фрейм
		_forEq($('.cal-abs'), function(sp) {
			var dH = sp.offset().top + 235 + 50;
			if(h < dH)
				h = dH;
		});

		//проверка, чтобы Фильтр102 не выходил за фрейм
		_forEq($('._filter102.rs .list'), function(sp) {
			var dH = sp.offset().top + Math.round(sp.height()) + 50;
			if(h < dH)
				h = dH;
		});

		if(FBH_CUR == h)
			return;

		FBH_CUR = h;
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
	_appEnter = function(app_id, page_id) {//вход в приложение из списка приложений
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, function() {
			location.href = URL + (page_id ? '&p=' + page_id : '');
		});
	};

	$(document).ready(_faceTest);
