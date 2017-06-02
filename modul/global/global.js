var _post = function(send, func, funcErr) {//�������� ajax-������� ������� POST
		$.post(AJAX, send, function(res) {
			if(res.success) {
				if(!func)
					return;
				if(func == 'reload') {
					location.reload();
					return;
				}
				func(res);
			} else
				if(funcErr)
					funcErr(res);
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
	_faceTest = function() {//�����������, ��� ��������� ��������: iframe ��� ����
		//���� ������� �������� �� ���������, �� ��������� � ������������ ��������
		var face = window == window.top ? 'site' : 'iframe';
		if(_cookie('face') == face)
			return;
		_cookie('face', face);
		location.reload();
	},
	_authLogin = function(code) {//����������� ������������ �� ���� �� �����
		var send = {
				op:'login',
				code:code
			},
			func = function() {
				location.href = 'https://nyandoma.ru/app';
			};

		_post(send, func, func);
	},
	_appEnter = function(app_id) {//���� � ���������� �� ������ ����������
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, 'reload');
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
			head:'������ AJAX-�������',
			content:'<textarea style="width:730px;background-color:#fdd">' + request.responseText + '</textarea>',
			butSubmit:'',
			butCancel:'�������'
		});
		d.content.find('textarea').autosize();
	})

	.ready(function() {
		_faceTest();
	});
