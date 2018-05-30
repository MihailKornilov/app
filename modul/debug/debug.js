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
	.on('click', '.debug_toggle', function() {
		_cookie('debug', _cookie('debug') == 1 ? 0 : 1);
		_msg();
		location.reload();
	})
	.on('click', '#_debug .sql-un', function() {
		var t = $(this),
			txt = '<div class="sql-hd">' +
					'time: ' + t.next().html() +
					'<a>Обновить</a>' +
					'<a>NOCACHE</a>' +
					'<a>EXPLAIN</a>' +
					'<h3></h3>' +
				  '</div>' +
				  '<textarea>' + t.html() + '</textarea>' +
				  '<div class="exp"></div>';
		t.parent()
		 .html(txt)
		 .find('textarea').select().autosize();
	})
	.on('click', '#_debug .sql-hd a', function() {
		var t = $(this),
			p = t.parent(),
			h3 = p.find('h3'),
			send = {
				op:'debug_sql',
				query:p.next().val(),
				nocache:t.html() == 'NOCACHE' ? 1 : 0,
				explain:t.html() == 'EXPLAIN' ? 1 : 0
			};
		if(p.hasClass('_busy'))
			return;
		h3.html('');
		p.addClass('_busy');
		$.post(AJAX, send, function(res) {
			p.removeClass('_busy');
			if(res.success) {
				h3.html(res.html);
				if(res.exp)
					p.next().next().html(res.exp);
			}
		}, 'json');
	})
	.on('click', '#cookie_clear', function() {
		$.post(AJAX, {'op':'cookie_clear'}, function(res) {
			if(res.success) {
				_msg('Cookie очищены');
				location.reload();
			}
		}, 'json');
	})

	.ready(function() {
		if(!_cookie('debug_pg'))
			_cookie('debug_pg', 'sql');

		$('.pg.' + _cookie('debug_pg'))._dn(1);
		_forEq($('#_debug .dmenu a'), function(sp) {
			if(sp.html() == _cookie('debug_pg'))
				sp.addClass('sel');
		});

/*
		if(_cookie('face') == 'iframe')
			frame0.onresize = function() {
				var inn = $(window).innerWidth(),
					out = $(window).outerWidth(),
					body = $('body');
				if(out < 1003) {
					body.removeClass('scrl');
					return;
				}
				body._dn(!(out - inn), 'scrl');
			};
*/
		//		$(window).resize();

		$('#_debug h1').click(function() {
			var t = $(this).parent(),
				s = t.hasClass('show');
			t[(s ? 'remove' : 'add') + 'Class']('show');
			$(this).html(s ? '+' : '—');
			_cookie('debug_show', s ? 0 : 1);
		});
		$('#_debug .dmenu a').click(function() {
			var t = $(this),
				sel = t.html();
			$('.pg')._dn();
			$('.pg.' + sel)._dn(1);
			t.parent().find('.sel').removeClass('sel');
			t.addClass('sel');
			_cookie('debug_pg', sel);
		});

		if($('#admin').length)
			$('#admin em').html(((new Date().getTime()) - TIME) / 1000);
	});
