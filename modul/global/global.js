var FB,
	REGEXP_NUMERIC =       /^\d+$/,
	REGEXP_NUMERIC_MINUS = /^-?\d+$/,
	REGEXP_DROB =          /^[\d]+(.[\d]+)?(,[\d]+)?$/,
	REGEXP_CENA =          /^[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_CENA_MINUS =    /^-?[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_SIZE =          /^[\d]+(.[\d]{1})?(,[\d]{1})?$/,
	REGEXP_MS =            /^[\d]+(.[\d]{1,3})?(,[\d]{1,3})?$/,
	REGEXP_DATE =          /^(\d{4})-(\d{1,2})-(\d{1,2})$/,

	_post = function(send, func, funcErr) {//�������� ajax-������� ������� POST
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
		if(_cookie('local'))
			return;
		//���� ������� �������� �� ���������, �� ��������� � ������������ ��������
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
	_authLogin = function(code) {//����������� ������������ �� ���� �� �����
		var send = {
				op:'login',
				code:code
			},
			func = function() {
				location.href = URL;
			};

		_post(send, func, func);
	},
	_appEnter = function(app_id) {//���� � ���������� �� ������ ����������
		var send = {
			op:'app_enter',
			app_id:app_id
		};

		_post(send, 'reload');
	},

	sortable = function() {
		$('._sort').sortable({
			axis:'y',
			update:function () {
				if($(this).hasClass('no'))
					return true;
				var dds = $(this).find('dd'),
					arr = [];
				for(var n = 0; n < dds.length; n++) {
					var v = _num(dds.eq(n).attr('val'));
					if(v)
						arr.push(v);
				}
				var send = {
					op:'sort',
					table:$(this).attr('val'),
					ids:arr.join()
				};
				_post(send);
			}
		});
	},
	_toSpisok = function(s) {
		var a=[];
		for(k in s)
			a.push({uid:k,title:s[k]});
		return a
	},
	_toAss = function(s) {
		var a=[];
		for(var n = 0; n < s.length; n++)
			a[s[n].uid] = s[n].title;
		return a
	},
	_yearSpisok = function(yearFirst) {//���� ��� ����������� ������
		//��������� ���������� ����
		yearFirst = _num(yearFirst);
		if(!yearFirst)
			yearFirst = 2010;

		//����������� �������� ����
		var d = new Date(),
			cur = d.getFullYear(),
			arr = [];

		for(var y = yearFirst; y <= cur; y++)
			arr.push({uid:y,title:y + ''});

		return arr
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
	_bool = function(v) {
		return v == 1 ? 1 : 0;
	},
	_num = function(v, minus) {
		var val = minus ? REGEXP_NUMERIC_MINUS.test(v) : REGEXP_NUMERIC.test(v);
		return val ? v * 1 : 0;
	},
	_cena = function(v, minus) {//���� � ����: 100    16,34     0.5
		//����� ���� ������������� ���������
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(v == 0)
			return 0;
		if(minus && REGEXP_CENA_MINUS.test(v))
			return v * 1;
		if(!REGEXP_DROB.test(v))
			return 0;
		return Math.round(v * 100) / 100;
	},
	_size = function(v) {//������ � ����: 100    16,3    (������ ���������� �����, �� ����� ���� �������������)
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(v == 0)
			return 0;
		if(!REGEXP_SIZE.test(v))
			return 0;
		return v * 1;
	},
	_ms = function(v) {//������� ��������� � ������� 0.000
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(v == 0)
			return 0;
		if(!REGEXP_MS.test(v))
			return 0;
		return v * 1;
	},
	_fbhs = function() {
		var h;
		if(FOTO_HEIGHT > 0) {
			h = FOTO_HEIGHT;
			FB.height(h);
		} else {
			if(DIALOG_MAXHEIGHT == 0)
				FB.height('auto');
			h = FB.height();
			if(h < DIALOG_MAXHEIGHT) {
				h = DIALOG_MAXHEIGHT;
				FB.height(h);
			}
		}
		if(FB_HEIGHT == h)
			return;
		FB_HEIGHT = h;
		VK.callMethod('resizeWindow', 795, h);
	},
	_msg = function(txt, func) {//��������� � ���������� ����������� ��������
		if(!txt)
			txt = '���������';
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
	_err = function(msg) {
		dialog.bottom._hint({
			msg:msg,
			red:1,
			top:-48,
			left:126,
			indent:40,
			show:1,
			remove:1
		});
	},
	_wait = function(v) {//�������� ���������� ��������
		$('#_wait').remove();
		if(v === false) {
			_backfon(false);
			return;
		}
		$('body').append('<div id="_wait" class="_busy"></div>');
		_backfon($('#_wait'));
		$('#_wait')
			.css('top', $(this).scrollTop() + 200 + VK_SCROLL);
	},
	_br = function(v, back) {
		if(back)
			return v.replace(new RegExp("\n",'g'), '<br />');
		return v.replace(new RegExp('<br />','g'), "\n")
				.replace(new RegExp('<br>','g'), "\n");
	},
	_copySel = function(arr, id) {//����������� ������� ��� �������. ���� ������ id - ������������
		var send = [];
		for(var n = 0; n < arr.length; n++) {
			var sp = arr[n];
			if(sp.uid == id)
				continue;
			send.push(sp);
		}
		return send;
	},
	_dialogDel = function(o) {//����� ������� ��������
		o = $.extend({
			id:0,               //id, ������� ����� �������
			head:'������',      //��������� �������
			info:'',            //�������������� ���������� �� ��������
			op:'_del',          //���������� switch, �� ������� ����� ������������� ��������
			func:function() {}  //�������, ����������� ����� ��������� ��������
		}, o);
		var html = (o.info ? '<div class="_info">'+ o.info + '</div>' : '') +
				'<center class="red">����������� ��������<br /><b>' + o.head + '</b>.</center>',
			dialog = _dialog({
				head:'�������� ' + o.head,
				padding:30,
				content:html,
				butSubmit:'�������',
				submit:submit
			});
		function submit() {
			var send = {
				op:o.op,
				id:o.id
			};
			dialog.process();
			$.post(AJAX, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('�������');
					o.func(res);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_tooltip = function(msg, left, ugolSide) {
		return ' _tooltip">' +
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>';
	},
	_parent = function(t, tag) {//����� ������� ���� ������� parent()
		tag = tag || 'TR';
		var max = 10,
			e = 0,
			cls = tag[0] == '.';
		if(cls)
			tag = tag.substr(1);
		while(!e) {
			t = t.parent();
			e = cls ? t.hasClass(tag) : t[0].tagName == tag;
			if(!--max)
				e = 1;
		}
		return t;
	},
	_busy = function(v, obj) {//����������� ��������� ��������
		//��������� ����� ���������
		if(v == 'set') {
			window.BUSY_OBJ = obj;
			return;
		}

		if(!window.BUSY_OBJ)
			window.BUSY_OBJ = $('#_menu');

		var m = window.BUSY_OBJ;
		if(v === 0) {
			m.removeClass('_busy');
			return;
		}

		if(m.hasClass('_busy'))
			return true;

		m.addClass('_busy');
	},
	_forEq = function(arr, func) {//������������ ����������������� ������� jquery $(...)

		//������� ����� �������������� �� ��� ���, ���� �� ����� ��������� �������� false � �������
		for(var n = 0; n < arr.length; n++)
			if(func(arr.eq(n), n) === false)
				return false;
		return true;
	},
	_forN = function(arr, func) {//������������ ����������������� ������� js
		for(var n = 0; n < arr.length; n++)
			func(arr[n], n);
	},
	_dn = function(v) {//�������/����� ��������
		return v ? '' : ' dn';
	},

/*
	_pas = function() {
		$('#page-setup-page')._dropdown({
			head:'��������',
			nosel:1,
			spisok:[
				{uid:1,title:'<b>����� ��������</b>'},
				{uid:2,title:'������ �������'}
			],
			func:function(v) {
				if(v == 1)
					_dialogOpen(20);
				if(v == 2)
					location.href = URL + '&p=12';
			}
		});
	},
*/
	_pageShow = function() {//���������� ����� ������ ��������

		//���������� ������� � _search
		_forEq($('._search'), function(sp) {
			sp.find('input')._search({
				func:function(v) {
					var send = {
						op:'spisok_search',
						element_id:_parent(sp, '.pe').attr('id').split('_')[1],
						v:v
					};
					_post(send, function(res) {
						$(res.attr_id).html(res.spisok);
					});
				}
			});
		});
	};

$.fn.keyEnter = function(func) {
	$(this).keydown(function(e) {
		if(e.keyCode == 13)
			func();
	});
	return $(this);
};
$.fn._flash = function(o) {//������� � ��������� �������� � ������
	var t = $(this),
		w = t.width(),
		h = t.height(),
		mt = t.css('margin-top');

	o = $.extend({
		color:'orange' //orange, red
	}, o);

	t.before('<div id="unit-flash" class="' + o.color + '"><div></div></div>')
	 .prev().find('div')
	 .width(w)
	 .height(h)
	 .css('top', mt)
	 .animate({opacity:.7}, 100)
	 .animate({opacity:0}, 600, function() {
		$(this).parent().remove();
	 });

	return t;
};
$.fn._dn = function(v, cls) {//�������/����� ��������
	var t = $(this);
	t[(v ? 'remove' : 'add') + 'Class'](cls || 'dn');
	return t;
};

$(document)
	.ajaxSuccess(function(event, request) {
		_busy(0);
		var req = request.responseJSON;

		if(req.pin) {
			location.reload();
			return;
		}

		if(!$('#_debug').length)
			return;

		var html = '',
			post =
				'<div class="hd ' + (req.success ? 'res1' : '') + (req.error ? 'res0' : '') + '">' +
					'<b>post</b>' +
					'<a id="repeat">������</a>' +
	 (req.success ? '<b id="res-success">success</b>' : '') +
	   (req.error ? '<b id="res-error">error</b>' : '') +
				'</div>' +
				req.post,
			link = '<div class="hd"><b>link</b></div><textarea>' + req.link + '</textarea>',
			sql = '<div class="hd">sql <b>' + req.sql_count + '</b> (' + req.sql_time + ') :: php ' + req.php_time + '</div>';

		for(var i in req) {
			switch(i) {
				case 'success': break;
				case 'error': break;
				case 'php_time': break;
				case 'sql_count': break;
				case 'sql_time': break;
				case 'link': break;
				case 'post': break;
				case 'sql':
					sql += '<ul>' + req[i] + '</ul>';
					break;
				default:
					var len = req[i].length ? '<tt>' + req[i].length + '</tt>' : '';
					html += '<div class="hd"><b>' + i + '</b>' + len + '<em>' + typeof req[i] + '</em></div>';
					if(typeof req[i] == 'object') {
						html += obj(req[i]);
						break;
					}
					if(typeof req[i] == 'string')
						req[i] = req[i].replace(/<\/textarea>/g,'</ textarea>');
					html += '<textarea>' + req[i] + '</textarea>';
			}
		}
		$('#_debug .ajax').html(post + link + sql + html);
		$('#_debug .ajax textarea').autosize();
		$('#_debug #repeat').click(function() {
			var t = $(this).parent();
			if(t.hasClass('_busy'))
				return;
			t.addClass('_busy');
			$.post(req.link, req.post, function() {}, 'json');
		});
//		window.FBH = FB.height();
		debugHeight();
		function obj(v) {
			var send = '<table>',
				i;
			for(i in v)
				send += '<tr><td class="val"><b>' + i + '</b>: ' +
							'<td>' + (typeof v[i] == 'object' ? obj(v[i]) : v[i]);
			send += '</table>';
			return send;
		}
	})
	.ajaxError(function(event, request) {
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

	.on('click', '#cache_clear', function() {//�������� ����
		_cookie('version', _num(_cookie('version')) + 1);
		_msg();
		location.reload();
	})
	.on('click', '#page_setup', function() {//���������/���������� ���������� ���������
		_cookie('page_setup', _cookie('page_setup') == 1 ? 0 : 1);
		_msg();
		location.reload();
	})

	.ready(function() {
		_faceTest();
	});
