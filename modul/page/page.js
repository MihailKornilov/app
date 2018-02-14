var _faceTest = function() {//определение, как загружена страница: iframe или сайт
		if(_cookie('local'))
			return;
		//если текущее значение не совпадает, то установка и перезагрузка страницы
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
	_authLogin = function(url) {//авторизация пользователя по коду на сайте
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
	_appEnter = function(app_id) {//вход в приложение из списка приложений
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, 'reload');
	},

	_pageAct = function(pas) {//активация элементов после вывода страницы
		_elemActivate(ELM, {}, pas);
	};

	$(document).ready(_faceTest);
