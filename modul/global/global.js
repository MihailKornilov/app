var ZINDEX = 1000,
	REGEXP_NUMERIC =       /^\d+$/,
	REGEXP_NUMERIC_MINUS = /^-?\d+$/,
	REGEXP_DROB =          /^[\d]+(.[\d]+)?(,[\d]+)?$/,
	REGEXP_CENA =          /^[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_CENA_MINUS =    /^-?[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_SIZE =          /^[\d]+(.[\d]{1})?(,[\d]{1})?$/,
	REGEXP_MS =            /^[\d]+(.[\d]{1,3})?(,[\d]{1,3})?$/,
	REGEXP_DATE =          /^(\d{4})-(\d{1,2})-(\d{1,2})( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/,

	POST_SEND,              //отправленные данные в _post
	POST_BUSY_OBJ = null,   //объект, к которому применяется класс ожидания. Будет сброшен при условии возникновения ошибки
	POST_BUSY_CLS,          //класс, показвыающий процесс ожидания

	_post = function(send, func) {//отправка ajax-запроса методом POST
		var v = $.extend({
			busy_obj:null,   //объект, к которому применяется процесс ожидания
			busy_cls:'_busy',//класс, показвыающий процесс ожидания
			func_err:function(res) {//функция в случае ошибки, если не получен success
				if(v.busy_obj)
					$(v.busy_obj)._hint({
						msg:res.text,
						color:'red',
						pad:10,
						show:1
					});
			}
		}, send);

		if(v.busy_obj) {
			if($(v.busy_obj).hasClass(v.busy_cls))
				return;
			$(v.busy_obj).addClass(v.busy_cls);
		}

		POST_BUSY_OBJ = send.busy_obj;
		POST_BUSY_CLS = send.busy_cls;

		send = _postSend(send);

		POST_SEND = send;

		$.post(AJAX, send, function(res) {
			if(v.busy_obj)
				$(v.busy_obj).removeClass(v.busy_cls);

			if(res.success) {
				if(!func)
					return;
				if(func == 'reload') {
					location.reload();
					return;
				}
				func(res);
			} else
				v.func_err(res);

		}, 'json');
	},
	_postSend = function(send) {//очистка данных от лишних значений перед отправкой POST
		delete send.busy_obj;
		delete send.busy_cls;
		delete send.func_open_before;
		delete send.func_open;
		delete send.func_save;
		delete send.func_err;

		for(var i in send) {
			if(send[i] instanceof jQuery) {
				delete send[i];
				continue;
			}

			switch(typeof send[i]) {
				case 'number':
				case 'string':
				case 'object': continue;
			}
			delete send[i];
		}

		return send;
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
	_toSpisok = function(s) {
		var a=[];
		for(var k in s)
			a.push({id:k,title:s[k]});
		return a
	},
	_end = function(count, arr) {
		if(arr.length == 2)
			arr.push(arr[1]);
		var send = arr[2];
		if(Math.floor(count / 10 % 10) != 1)
			switch(count % 10) {
				case 1: send = arr[0]; break;
				case 2: send = arr[1]; break;
				case 3: send = arr[1]; break;
				case 4: send = arr[1]; break;
			}
		return send;
	},
	_num = function(v, minus) {
		var val = minus ? REGEXP_NUMERIC_MINUS.test(v) : REGEXP_NUMERIC.test(v);
		return val ? v * 1 : 0;
	},
	_cena = function(v, minus) {//цена в виде: 100    16,34     0.5
		//Может быть отрицательным значением
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(!REGEXP_DROB.test(v))
			return 0;
		if(v == 0)
			return 0;
		if(minus && REGEXP_CENA_MINUS.test(v))
			return v * 1;
		return Math.round(v * 100) / 100;
	},
	_nol = function(v) {//вставка нуля перед цифрой - для времени
		v = _num(v);
		return (v > 9 ? '' : '0') + v;
	},
	_msg = function(txt, func) {//Сообщение о результате выполненных действий
		if(!txt)
			txt = 'Выполнено';
		$('#_msg').remove();
		$('body').append('<div id="_msg">' + txt + '</div>');
		$('#_msg')
			.css('top', $(this).scrollTop() + 200 + VK_SCROLL)
			.css('left', $(document).width() / 2 - 200)
			.delay(1200)
			.fadeOut(400, function() {
				$(this).remove();
				if(typeof func == 'function')
					func();
			});
	},
	_br = function(v, back) {
		if(!v)
			return '';
		if(back)
			return v.replace(new RegExp("\n",'g'), '<br>');
		return v.replace(new RegExp('<br>','g'), "\n");
	},
	_copySel = function(arr, id) {//копирование массива для селекта. Если указан id - игнорируется
		var send = [];
		_forN(arr, function(sp) {
			if(!sp.info && sp.id == id)
				return;
			send.push(sp);
		});
		return send;
	},
	_copyObj = function(arr) {//копирование ассоциативного массива
		var send = {};
		_forIn(arr, function(v, i) {
			send[i] = v;
		});
		return send;
	},
	_tooltip = function(msg, left, ugolSide, x2) {
		left = left ? ' style="left:' + left + 'px"' : '';
		x2 = x2 ? ' x2' : '';
		ugolSide = ugolSide ? ' ' + ugolSide : '';
		return ' _tooltip">' +
		'<div class="ttdiv' + x2 + '"' + left +'>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + ugolSide + '"></div>' +
		'</div>';
	},
	_forEq = function(arr, func) {//перечисление последовательного массива jQuery $(...)

		//перебор будет осуществляться до тех пор, пока не будет встречено значение false в функции
		for(var n = 0; n < arr.length; n++)
			if(func(arr.eq(n), n) === false)
				return false;
		return true;
	},
	_forN = function(arr, func) {//перечисление последовательного массива js
		if(!arr)
			return false;
		for(var n = 0; n < arr.length; n++)
			if(func(arr[n], n) === false)
				return false;
		return true;
	},
	_forIn = function(arr, func) {//перечисление ассоциативного массива или объекта
		for(var n in arr)
			if(func(arr[n], n) === false)
				return false;
		return true;
	},
	_dn = function(v, cls) {//скрытие/показ элемента
		cls = cls || 'dn';
		v = cls == 'dn' ? v : !v;
		return v ? '' : ' ' + cls;
	},
	_idsAss = function(ids) {
		var send = {};

		if(!ids)
			return send;

		if(typeof ids == 'number') {
			send[ids] = 1;
			return send;
		}

		_forN(ids.split(','), function(id) {
			id = _num(id);
			if(!id)
				return;
			send[id] = 1;
		});
		return send;
	},
	_idsFirst = function(ids) {//первый id в списке
		if(!ids)
			return 0;

		if(typeof ids == 'number')
			return ids;

		return _num(ids.split(',')[0]);
	},
	_pr = function(v) {//представление массива в виде таблицы
		if(v instanceof jQuery)
			return '<div class="fs11 red">jQuery</div>';
		var send = '<div class="dib bor1 pad5 mt2 bg-gr1">' +
				   '<table>',
			i;
		for(i in v) {
			var txt = v[i];
			if(typeof v[i] == 'object')
				txt = _pr(v[i]);
			if(typeof v[i] == 'function')
				txt = '<div class="fs11 color-acc">Function</div>';
			if(typeof v[i] == 'undefined')
				txt = '<div class="fs11 pale">undefined</div>';
			send += '<tr><td class="r top b pr3">' + i + ': ' +
						'<td>' + txt;
		}
		send += '</table></div>';
		return send;
	},
	_cons = function(v) {//вывод значения как в console.log
		if(!$('#_debug').length)
			return;
		var cons = $('#_debug .cons'),
			tpf = typeof v,
			log = '<div class="fs14 mb5 grey">' + tpf + '</div>' +
				  (tpf == 'object' ? _pr(v) : v) +
				  '<div class="line-t mt10">&nbsp;</div>';
		cons.find('.cons-div').prepend(log);
		if(!cons.hasClass('dn'))
			cons._flash();
	},
	_bug = function(block_id) {//вставка количества ошибок по каждому виду структуры приложения (страница 132)
		var BL = _attr_bl(block_id),
			c = 0;

		_forEq(BL.find('.red'), function(sp) {
			c += _num(sp.html());
		});

		if(!c)
			return;

		BL.closest('.bl-div')
		  .prev()
		  .find('.bg-fee')
		  .addClass('red b center fs16')
		  .css('vertical-align', 'middle')
		  .html(c ? c : '');
	};

$.fn._enter = function(func) {
	$(this).keydown(function(e) {
		if(e.keyCode == 13)
			func();
	});
	return $(this);
};
$.fn._flash = function(o) {//вспышка и затухание элемента в списке
	var t = $(this);

	if(!t.length)
		return t;

	var w = t.css('width'),
		h = t.css('height'),
		mt = t.css('margin-top');

	o = $.extend({
		color:'orange' //orange, red
	}, o);

	t.before('<div id="unit-flash" class="' + o.color + '"><div></div></div>')
	 .prev().find('div')
	 .css('width', w)
	 .css('height', h)
	 .css('top', mt)
	 .css('opacity', .9)
	 .animate({opacity:0}, 800, function() {
		$(this).parent().remove();
	 });

	return t;
};
$.fn._dn = function(v, cls) {//скрытие/показ элемента
	var t = $(this);
	t[(v ? 'remove' : 'add') + 'Class'](cls || 'dn');
	return t;
};
$.fn._vh = function(v) {//скрытие/показ элемента visibility:hidden
	var t = $(this);
	t[(v ? 'remove' : 'add') + 'Class']('vh');
	return t;
};
$.fn._busy = function(v) {//проверка/установка/снятие процесса ожидания для элемента - класс _busy
	var t = $(this);

	if(v === undefined) {
		v = t.hasClass('_busy');
		//если процесса не было, то установка
		if(!v)
			t.addClass('_busy');
		return v;
	}

	t[(v ? 'add' : 'remove') + 'Class']('_busy');

	return t;
};
$.fn._sort = function(o) {//сортировка
	var t = $(this);

	o = $.extend({
		items:'dd',
		axis:'',
		handle:'.icon-move,.icon-move-y',
		elem_id:0
	}, o);

	t.sortable({
		axis:o.axis,
		handle:o.handle,
		update:function() {
			if(!o.elem_id)
				return;
			var dds = $(this).find(o.items),
				arr = [];
			for(var n = 0; n < dds.length; n++) {
				var v = _num(dds.eq(n).attr('val'));
				if(v)
					arr.push(v);
			}
			var send = {
				op:'sort',
				elem_id:o.elem_id,
				ids:arr.join()
			};
			t.addClass('prel');
			t.append('<div class="elm-sort-hold _busy"></div>');
			_post(send, function() {
				t.find('.elm-sort-hold').remove();
			});
		}
	});
};
$.fn._autosize = function(v) {//автоматическое изменение поля textarea
	var t = $(this);

	if(v == 'update') {
		autosize.update(t);
		return t;
	}

	autosize(t);

	return t;
};

$(document)
	.ajaxSuccess(function(event, request) {
		var req = request.responseJSON;

		if(!req)
			return;
		if(!$('#_debug').length)
			return;

		var html = '',
			post =
				'<div class="pad5 ' + _dn(req.success, 'bg-dfd') + _dn(req.error, 'bg-fcc') + '">' +
					'<b>post</b>' +
					'<a id="repeat">повтор</a>' +
	 (req.success ? '<b class="color-pay fr">success</b>' : '') +
	   (req.error ? '<b class="color-ref fr">error</b>' : '') +
				'</div>' +
				'<div class="mt3">' + _pr(req.post) + '</div>',
			link =  '<div class="bg-gr1 bor1 pad5 mt10">' +
						'<b>link:</b> ' +
						'<span class="color-acc">' + req.link + '</span>' +
					'</div>',
			file =  '<div class="bg-gr1 bor1 pad5 mt3">' +
						'<b>file:</b> ' +
						req.file +
					'</div>',
			sql = '';

		for(var i in req) {
			switch(i) {
				case 'success':
				case 'error':
				case 'link':
				case 'file':
				case 'post': break;
				case 'sql':
					sql = '<div class="mt20">' + req[i] + '</div>';
					break;
				default:
					var len = req[i] && req[i].length ? '<b class="pale ml10">' + req[i].length + '</b>' : '';
					html += '<div class="bg-eee bor1 pad5 mt20 curP over1" onclick="$(this).next().slideToggle()">' +
								'<b>' + i + '</b>' +
								len +
								'<em class="fr">' + typeof req[i] + '</em>' +
							'</div>';
					if(typeof req[i] == 'object') {
						html += _pr(req[i]);
						break;
					}
					if(typeof req[i] === 'string')
						req[i] = req[i].replace(/textarea/g, 'text_area');
					html += '<div>' +
								'<textarea class="w100p mt3 h20">' + req[i] + '</textarea>' +
							'</div>';
			}
		}
		$('#_debug .ajax').html(post + link + file + sql + html);
		$('#_debug textarea')._autosize();
		$('#_debug #repeat').click(function() {
			var t = $(this).parent();
			if(t.hasClass('_busy'))
				return;
			t.addClass('_busy');
			$.post(req.link, req.post, function() {}, 'json');
		});
	})
	.ajaxError(function(event, request) {
		var txt = request.responseText;

		if(!txt)
			return;
		if(txt.substr(0, 15) == '<!DOCTYPE html>')
			return location.reload();

		txt = txt.replace(new RegExp('<br />', 'g'), '');
		txt = txt.replace(new RegExp('<b>', 'g'), '');
		txt = txt.replace(new RegExp('</b>', 'g'), '');
		txt = '<textarea class="w100p bg-fcc">' + txt + '</textarea>';

		if(POST_BUSY_OBJ) {
			$(POST_BUSY_OBJ).removeClass(POST_BUSY_CLS);
			POST_BUSY_OBJ = null;
		}

		//открытие диалога для показа ошибки, если не включен DEBUG (для обычных пользователей)
		if(!$('#_debug').length) {
			txt = '<div class="fs18 b center pad10">' +
					  'Обязательно отправьте мне скрин с этой ошибкой!' +
					  '<br>' +
				      '<a href="//vk.com/im?sel=982006" target="_blank">vk.com/mihan_k</a>' +
				  '</div>'+ txt;
			var dlg = _dialog({
				top:20,
				color:'red',
				width:800,
				head:'Ошибка',
				content:txt,
				butSubmit:'',
				butCancel:'Закрыть'
			});
			dlg.content.find('textarea')
				.css('max-height', 500)
				.css('border', 0)
				._autosize();
			return;
		}

		$('#_debug').addClass('show');
		$('#_debug .ajax').html(txt);
		$('#_debug .ajax textarea')._autosize().before(_pr(POST_SEND));
		$('#_debug .dmenu a:last').trigger('click');
	})

	.on('click', '#count_update', function() {//обновление количеств и сумм
		var send = {
			op:'count_update',
			busy_obj:$(this)
		};
		_post(send, function() {
			_msg('Суммы обновлены.');
			location.reload();
		});
	})
	.on('click', '#cache_clear', function() {//очищение кеша
		var send = {
			op:'cache_clear',
			busy_obj:$(this)
		};
		_post(send, function() {
			_msg('Кеш очищен');
			location.reload();
		});
	})
	.on('click', '#page_setup', function() {//включение/выключение управления страницей
		_cookie('page_setup', _cookie('page_setup') == 1 ? 0 : 1);
		$(this).addClass('_busy');
		location.reload();
	})
	.ready(function() {
		$('#app-admin')._hintOver({msg:'<div class="center">Администрирование<br>приложения</div>',pad:15});
		$('#page_setup:not(.ispas)')._hintOver({msg:'Включить настройку страницы',pad:15});
		$("#debug-footer em").html(((new Date().getTime()) - TIME) / 1000);
	});

