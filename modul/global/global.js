var _post = function(send, func) {//отправка ajax-запроса методом POST
		$.post(AJAX, send, function(res) {
			console.log(res);
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
	_authLogin = function(code) {//авторизация пользователя по коду на сайте
		var send = {
			op:'login',
			code:code
		};
		_post(send, function(res) {
			location.href = 'https://nyandoma.ru/app';
		});
	};

$.fn.keyEnter = function(func) {
	$(this).keydown(function(e) {
		if(e.keyCode == 13)
			func();
	});
	return $(this);
};

$(document)
	.ajaxError(function(event, request, settings) {
//		_busy(0);
		if(!request.responseText)
			return;
		var d = _dialog({
			width:770,
			top:10,
			head:'Ошибка AJAX-запроса',
			content:'<textarea style="width:730px;background-color:#fdd">' + request.responseText + '</textarea>',
			butSubmit:'',
			butCancel:'Закрыть'
		});
		d.content.find('textarea').autosize();
	})

	.ready(function() {
		_faceTest();
	});
