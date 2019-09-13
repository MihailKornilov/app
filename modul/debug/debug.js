var debugCookieUpdate = function(t) {//обновление COOKIE
		var send = {
			op:'debug_cookie',
			busy_obj:t
		};
		_post(send, function(res) {
			t.next().html(res.html);
		});
	},
	_cacheContentOpen = function(name) {//открытие диалога для просмотра содержания кеша
		_cookie('cache_content_name', name);
		if(DIALOG[84])
			DIALOG[84].close();
		_dialogLoad({
			dialog_id:84
		});
	};

$(document)
	.on('click', '.debug_toggle', function() {//включение-выключение debug (нажатие на строку снизу)
		_cookie('debug', _cookie('debug') == 1 ? 0 : 1);
		_msg();
		location.reload();
	})
	.on('click', '#_debug h1', function() {//нажатие на плюсик - открытие поля debug
		var t = $(this),
			p = t.parent(),
			s = p.hasClass('show');
		p._dn(s, 'show');
		t.html(s ? '+' : '—');
		_cookie('debug_show', s ? 0 : 1);
		$('#_debug textarea')._autosize('update');
	})
	.on('click', '#cookie_clear', function() {//очистка cookies
		var send = {
			op:'cookie_clear',
			busy_obj:$(this)
		};
		_post(send, function() {
			_msg('Cookie очищены');
			location.reload();
		});
	})

	.ready(function() {
		if(!_cookie('debug_pg'))
			_cookie('debug_pg', 'sql');

		$('.pg.' + _cookie('debug_pg'))._dn(1);
		_forEq($('#_debug .dmenu a'), function(sp) {
			if(sp.html() == _cookie('debug_pg'))
				sp.addClass('sel');
		});

		$('#_debug .dmenu a').click(function() {
			var t = $(this),
				sel = t.html();
			$('.pg')._dn();
			$('.pg.' + sel)._dn(1);
			t.parent().find('.sel').removeClass('sel');
			t.addClass('sel');
			_cookie('debug_pg', sel);
			$('#_debug textarea')._autosize('update');
		});
		$('#_debug textarea')._autosize();
	});
