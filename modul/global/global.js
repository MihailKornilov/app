var _post = function(send, func) {//отправка ajax-запроса методом POST
		$.post(AJAX, send, function(res) {
			if(res.success) {
				if(func)
					func(res);
			} else
				console.log(res.text);
		}, 'json');
	},
	_cookie = function(name, value) {
		if(value !== undefined) {
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + 1);
			document.cookie = name + '=' + value + '; path=/; expires=' + exdate.toGMTString();
			return '';
		}
		var r = document.cookie.split('; ');
		for(var i = 0; i < r.length; i++) {
			var k = r[i].split('=');
			if(k[0] == name)
				return k[1];
		}
		return '';
	},
	_faceTest = function() {//определение, как загружена страница: iframe или сайт
		//если текущее значение не совпадает, то установка и перезагрузка страницы
		var face = window == window.top ? 'site' : 'iframe';
		if(_cookie('face') == face)
			return;
		_cookie('face', face);
		location.reload();
	},
	_authLogin = function(code) {
		var send = {
			op:'login',
			code:code
		};
		_post(send, function(res) {
			location.href = 'https://nyandoma.ru/app';
		});
	};

$(document)
	.ready(function() {
		_faceTest();
	});
