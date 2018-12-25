/* Элементы-теги */

var _attrId = function(t) {//формирование аттрибута id
		var attr_id = t.attr('id');

		if(attr_id)
			return attr_id;

		attr_id = 'afics' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
		return attr_id;
	};

$(document)
	.on('click', '._check', function() {//установка/снятие галочки, если была выведена через PHP
		var t = $(this);
		if(!t.hasClass('php'))//если галочка выведена через JS, а не через PHP, то действия нет
			return;
		if(t.hasClass('disabled'))
			return;

		var p = t.prev(),
			v = _num(p.val()) ? 0 : 1;

		p.val(v);
		t._dn(!v, 'on');
	})
	.on('click', '._radio div', function() {//выбор значения radio, если был выведен через PHP
		var t = $(this),
			p = t.parent();
		if(!p.hasClass('php'))//если элемент был выведен через JS, а не через PHP, то действия нет
			return;
		if(p.hasClass('disabled'))
			return;

		var v = _num(t.attr('val'));

		p.prev().val(v);
		p.find('.on').removeClass('on');
		t.addClass('on');
	});

$.fn._check = function(o) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id');
	if(!attr_id) {
		attr_id = 'check' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_check',
		S = window[win];

	switch(typeof o) {
		case 'number':
			S.value(o ? 1 : 0);
			return t;
		case 'string':
			if(o == 'disable')
				S.dis();
			if(o == 'enable')
				S.enab();
			if(o == 'func')
				S.funcGo();
			return t;
	}

	checkPrint();

	var CHECK = $('#' + win);

	CHECK.click(function() {
		if(CHECK.hasClass('disabled'))
			return;

		var v = CHECK.hasClass('on') ? 0 : 1;
		setVal(v);
		o.func(v, t);
	});

	if(o.tooltip)
		CHECK._tooltip(o.tooltip);

	function checkPrint() {//вывод галочки
		var nx = t.next(),
			cls = '';//дополнительные стили, которые были вставлены через PHP
		if(nx.hasClass('_check')) {  //если галочка была выведена через PHP - обновление и применение функций
			o = $.extend({
				title:nx.html() == '&nbsp;' ? '' : nx.html(),
				disabled:nx.hasClass('disabled'),
				light:nx.hasClass('light'),
				block:nx.hasClass('block')
			}, o);
			nx.removeClass('_check title light block disabled on php');
			cls = ' ' + nx.attr('class');
			nx.remove();
		}

		o = $.extend({
			title:'',
			disabled:0,
			light:1,
			block:0,
			tooltip:'',
			func:function() {}
		}, o);

		var val = t.val() == 1 ? 1 : 0,
			on = val ? ' on' : '',
			title = o.title ? ' title' : '',
			light = o.light ? ' light' : '',
			block = o.block ? ' block' : '',
			dis = o.disabled ? ' disabled' : '',
			html =
				'<div id="' + win + '" class="_check' + on + title + light + block + dis + cls + '">' +
					(o.title ? o.title : '&nbsp;') +
				'</div>';

		t.val(val).after(html);
	}
	function setVal(v) {
		CHECK[(v ? 'add' : 'remove') + 'Class']('on');
		t.val(v);
	}

	t.value = setVal;
	t.funcGo = function() {//применение фукнции
		o.func(_num(t.val()), t);
	};
	t.dis = function() {//перевод галочки в неактивное состояние
		CHECK.addClass('disabled');
	};
	t.enab = function() {//перевод галочки в активное состояние
		CHECK.removeClass('disabled');
	};
	window[win] = t;
	return t;
};
$.fn._radio = function(o, oo) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win];

	if(S) {
		switch(typeof o) {
			case 'number':
				S.valSet(o);
				break;
			case 'string':
				if(o == 'spisok')
					S.spisok(oo);
				break;
			case 'function':
				S.func(o);
				break;
		}
		return S;
	}





	// ---=== АКТИВАЦИЯ RADIO ===---


	//если элемент был выведен через PHP, переключение его на JS
	var PHP = t.next('._radio.php'),
		PHP_O = o;  //сохранение для повторного вывода
	if(PHP.length) {
		if(typeof o != 'object')
			o = {};

		o.php = 1;  //флаг, чтобы не перерисовывать заново содержание, а только применить функции
		o.title0   = PHP.find('.title0').html();
		o.block    = PHP.hasClass('block');
		o.dis      = PHP.hasClass('disabled');
		o.light    = PHP.hasClass('light');
		o.interval = PHP.find('div:first').css('margin-bottom').split('px')[0] * 1;
		if(!o.interval)
			delete o.interval;

		PHP.removeClass('php');
	}


	o = $.extend({
		title0:'',  //текст нулевого значения
		spisok:[],  //список значений в виде id => title
		dis:0,      //выбор значений заблокирован
		light:0,    //невыбранные значения показываются бледным цветом
		block:1,    //вертикальное, либо горизонтальное отображение значений
		interval:7, //интервал между значениями
		func:function() {}
	}, o);

	
	//печать списка, если отсутствует
	var RD = t.next('._radio');
	if(!RD.length) {
		var html =
			'<div class="_radio' + 
						_dn(o.block, 'block') + 
						_dn(o.dis, 'disabled') + 
						_dn(o.light, 'light') + '"' +
				' id="' + attr_id + '_radio">'   +
			'</div>';
		t.after(html);
		RD = t.next();
		_spisok();
	}

	_active();

	function _spisok() {//печать списка
		var spisok = _copySel(o.spisok),
			val = _num(t.val(), 1),
			html = '';

		if(o.title0)
			spisok.unshift({id:0,title:o.title0});

		_forN(spisok, function(sp) {
			html += '<div class="' + _dn(val == sp.id, 'on') + '"' +
						' val="' + sp.id + '"' +
						' style="margin-bottom:' + o.interval + 'px">' +
						sp.title +
					'</div>';
		});

		RD.html(html);
	}
	function _active() {//активирование нажания
		RD.find('div').click(function() {
			if(RD.hasClass('disabled'))
				return;

			var v = _num($(this).attr('val'));
			_val(v);
			o.func(v, attr_id);
		});
	}
	function _val(v) {//установка значения
		RD.find('div.on').removeClass('on');
		_forEq(RD.find('div'), function(sp) {
			if(v == _num(sp.attr('val'))) {
				sp.addClass('on');
				return false;
			}
		});
		t.val(v);
	}








	t.func = function(func) {//установка новой функции
		o.func = func;
	};
	t.valSet = _val;
	t.spisok = function(spisok) {//вставка нового списка
		o.spisok = spisok;
		_spisok();
		_active();
	};

	window[win] = t;

	if(o.php) {
		o.php = 0;
		return t._radio(PHP_O, oo);
	}

	return t;
};
$.fn._count = function(o) {//input с количеством
	var t = $(this),
		S;

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		win = attr_id + 'win';

	o = $.extend({
		width:50,   //если 0 = 100%
		bold:0,
		disabled:0,
		tooltip:'',

		min:false,  //минимальное значение
		max:false,  //максимальное значение
		minus:0,    //может уходить в минус
		step:1,     //шаг. Либо массив вариантов: [1,5,10,20]
		time:0,     //значение является временем (добавление нуля спереди, если меньше 10)
		again:0,    //если переключение доходит до крайнего значения, продолжение с начала

		title:[],   //имена значений в виде текста. Перечислены через запятую согласно step

		func:function() {}
	}, o);

	if(o.min < 0)
		o.minus = 1;

	var val = _num(t.val(), 1);
	val = valCorrect();
	t.val(val);

	var PHP = t.next('._count.php'),
		width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		dis = o.disabled ? ' disabled' : '',
		STEP_COUNT = o.step.length || 0,//количество значений, если шаг-массив
		STEP_N = 0;//номер шага, если шаг-массив

	if(STEP_COUNT) {
		o.min = o.step[0];
		o.max = o.step[STEP_COUNT - 1];
		_forN(o.step, function(sp, n) {
			if(sp == val) {
				STEP_N = n;
				return false;
			}
		});
	}

	if(PHP.length) {
		 PHP.removeClass('php')
			._dn(!dis, 'disabled')
			.attr('id', attr_id + '_count')
			.width(o.width || '100%');
	} else {
		t.after('<div class="_count' + dis + '" id="' + attr_id + '_count" style="' + width + '">' +
					'<input type="text" readonly value="' + val + '" />' +
					'<div class="but"></div>' +
					'<div class="but but-b"></div>' +
				'</div>');
		PHP = t.next();
	}

	var INP = PHP.find('input');
	INP.val(val);

	PHP._dn(val || o.time, 'nol');

	if(o.bold)
		INP.addClass('b');

	if(o.tooltip)
		PHP._tooltip(o.tooltip, -15);

	PHP.find('.but').click(function() {
		if(dis)
			return;
		var znak = $(this).hasClass('but-b') ? -1 : 1;

		if(STEP_COUNT) {
			STEP_N += znak;
			if(znak > 0 && STEP_N > STEP_COUNT - 1)
				STEP_N = STEP_COUNT - 1;
			if(znak < 0 && STEP_N < 0)
				STEP_N = 0;
			val = o.step[STEP_N];
		} else
			val += o.step * znak;

		val = valCorrect();
		PHP._dn(val || o.time, 'nol');

		t.val(val);

		//установка текстового значения, если требуется
		var title = (o.time && val < 10 ? '0' : '') + val;
		if(o.title[STEP_N])
			title = o.title[STEP_N];
		INP.val(title);

		o.func(val, attr_id);
	});
	function valCorrect() {
		if(o.again)
			if(o.min !== false)
				if(o.max !== false) {
					if(val < o.min)
						return o.max;

					if(val > o.max)
						return o.min;
				}

		if(!o.minus && val < 0)
			return 0;

		if(o.min !== false && val < o.min)
			return o.min;

		if(o.max !== false && val > o.max)
			return o.max;

		return val;
	}

	window[win] = t;
	return t;
};
$.fn._select = function(o, o1) {//выпадающий список от 03.01.2018
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		VALUE = t.val(),
		win = attr_id + 'win',
		s = window[win];

	switch(typeof o) {
		default:
		case 'undefined': break;
		case 'object': break;
		case 'number':
		case 'string': return action();
	}

	o = $.extend({
		width:150,			// ширина. Если 0 = 100%
		disabled:0,         // нельзя выбирать, серые стили
		blocked:0,          // заблокировано. Нельзя выбирать, но выглядит ярко
		block:0,       	    // расположение селекта
		title0:'',			// поле с нулевым значением
		spisok:[],			// результаты в формате json
		write:0,            // возможность вводить значения
		write_save:0,       // сохранять текст, если даже не выбран элемент
		msg_empty:'Список пуст',
		multi:0,            // возможность выбирать несколько значений. Идентификаторы перечисляются через запятую
		func:function() {},	// функция, выполняемая при выборе значения
		funcWrite:funcWrite,// функция, выполняемая при вводе в INPUT в селекте. Нужна для вывода списка из вне, например, Ajax-запроса, либо из vk api.
		funcAdd:null	    // добавления новой единицы. Если указана, показывает плюсик.
	}, o);

	var dis = o.disabled ? ' disabled' : '',
		blocked = o.blocked ? ' blocked' : '',
		dib = o.block ? '' : ' dib',
		width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		readonly = o.write ? '' : ' readonly',
		placeholder = o.title0 ? ' placeholder="' + o.title0 + '"' : '',
		iconAddFlag = o.funcAdd && !dis && !blocked,
		html =
		'<div class="_select' + dis + blocked + dib + '" id="' + attr_id + '_select" style="' + width + '">' +
			'<table class="w100p">' +
				'<tr><td>' +
			 (o.multi ? '<dl>' : '') +
							'<input type="text" class="select-inp ' + (!o.multi ? 'w100p' : 'w50') + '"' + placeholder + readonly + ' />' +
			 (o.multi ? '</dl>' : '') +
					'<td class="w15' + _dn(o.write) + '"><div class="icon icon-del clear pl dn"></div>' +
					'<td class="w25 r' + _dn(iconAddFlag) + '"><div class="icon icon-add pl"></div>'+
					'<td class="arrow">' +
			'</table>' +
			'<div class="select-res"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	if(blocked)
		dis = 1;

	var SEL = t.next(),
		DL = SEL.find('dl'),
		DLW = o.multi ? Math.round(DL.width()) : 0,
		INP = SEL.find('.select-inp'),
		RES = SEL.find('.select-res'),
		ICON_DEL = SEL.find('.clear'),
		ICON_ADD = SEL.find('.icon-add'),
		MASS_ASS,//ассоциативный массив в виде {1:'text'}
		MASS_SEL,//массив в виде [{id:1,title:'text1'},{id:2,title:'text2'}]
		MASS_SEL_SAVE,//дублирование MASS_SEL
		TAG = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // поиск всех тегов
		BG_ASS;  //ассоциативный массив цветов фона

	massCreate();
	o.multi ? multiPrint() : spisokPrint();
	valueSet(o.multi ? '' : VALUE);

	INP.keydown(function() {
		setTimeout(function() {
			VALUE = 0;
			t.val(0);
			var v = INP.val();
			ICON_DEL._dn(v && !o.multi);
			o.funcWrite(v, t);
		}, 0);
	});
	SEL.click(function(e) {
		if(dis)
			return;

		var rs = SEL.hasClass('rs'),
			tagret = $(e.target);

		if(tagret.hasClass('select-unit info'))
			return;
		if(tagret.hasClass('select-unit')) {
			valueSet(tagret.attr('val'));
			o.func(VALUE);
		} else {
			var p = tagret.closest('.select-unit');
			if(p.hasClass('select-unit')) {
				valueSet(p.attr('val'));
				o.func(VALUE);
			}
		}

		if(rs && o.write && tagret.hasClass('select-inp'))
			return;
		if(tagret.hasClass('icon-add'))
			return;
		if(tagret.hasClass('icon-del'))
			return;
		if(tagret.hasClass('empty'))
			return;

		SEL._dn(rs, 'rs');
		RES._dn(RES.height() < 250, 'h250');

		//открытие списка
		if(!rs) {
			//выделение выбранного значения
			_forEq(RES.find('.select-unit'), function(sp) {
				if(VALUE == sp.attr('val')) {
					RES.find('.select-unit').removeClass('ov');
					if(sp.hasClass('info'))
						return false;
					sp.addClass('ov');
					//установка выбранного значения в области видимости
					sp = sp[0];
					var showTop = Math.round((250 - sp.offsetHeight) / 2);
					RES[0].scrollTop = sp.offsetTop - showTop;
					return false;
				}
			});

			//корректировка высоты фрейма VK, чтобы список не уходил за экран
			_fbhs(RES.offset().top + RES.height() + 20)
		} else
			_fbhs();
	});
	ICON_DEL.click(function() {
		valueSet(0);
		o.funcWrite('', t);
	});
	if(iconAddFlag)
		SEL.find('.icon-add').click(function() {
			o.funcAdd(t);
		});

	if(o.multi)
		DL.sortable({update:multiValueSet});

	$(document)
		.off('click._select')
		 .on('click._select', function(e) {
			var cur = $(e.target).parents('._select'),
				attr = '';

			//закрытие селектов, когда нажатие было в стороне
			if(cur.hasClass('_select'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._select' + attr).removeClass('rs');
		});

	function massCreate() {//создание массива для корректного вывода списка
		var unit;

		MASS_ASS = {};
		MASS_SEL = [];
		MASS_SEL_SAVE = [];
		BG_ASS = {};

		if(o.title0)
			MASS_ASS[0] = '';

		//исходный список является ассоциативным объектом
		if(!o.spisok.length) {
			_forIn(o.spisok, function(sp, id) {
				if(!id)
					return;
				MASS_ASS[id] = sp;
				unit = {
					id:id,
					title:sp,
					content:sp
				};
				MASS_SEL.push(unit);
				MASS_SEL_SAVE.push(_copyObj(unit));
			});
			return;
		}

		//исходный список является последовательным массивом
		_forN(o.spisok, function(sp, n) {
			var id,
				title,
				content;

			//проверка на одномерный последовательный массив
			if(typeof sp == 'number' || typeof sp == 'string') {
				id = n + 1;
				title = sp;
			} else {
				id = sp.uid;
				if(id === undefined)
					id = sp.id;
				if(sp.info)
					id = -9999999999;
				if(!id)
					return;
				title = sp.title;
				if(title === undefined)
					return;
				content = sp.content;
			}

			MASS_ASS[id] = title || ' ';
			title = title || '&nbsp;';
			if(!content)
				content = title;
			unit = {
				id:id,
				title:title,
				content:content,
				info:_num(sp.info),//флаг информационного значения. Значение нельзя выбрать.
				bg:sp.bg
			};
			MASS_SEL.push(unit);
			MASS_SEL_SAVE.push(_copyObj(unit));
			BG_ASS[id] = sp.bg;
		});
	}
	function spisokPrint() {//вставка списка в select
		RES.removeClass('h250');
		if(!MASS_SEL.length && !o.title0) {
			RES.html('<div class="empty">' + o.msg_empty + '</div>');
			return;
		}

		var html = '',
			is_sel = o.multi ? _idsAss(t.val()) : {}; //выбранные значения (нельзя выбрать повторно при multi)
		if(o.title0 && !o.write && !o.multi)
			html += '<div class="select-unit title0" val="0">' + o.title0 + '</div>';

		_forN(MASS_SEL, function(sp) {
			if(is_sel[sp.id])
				return;
			var info = sp.info ? ' info' : '',
				val = info ? '' : ' val="' + sp.id + '"',
				bg = sp.bg ? ' style="background-color:' + sp.bg + '"' : '';
			html += '<div class="select-unit' + info + '"' + bg + val + '>' + sp.content + '</div>';
		});

		RES.html(html);

		var h = RES.height();
		RES._dn(h < 250, 'h250');

		RES.find('.select-unit').mouseenter(function() {
			var sp = $(this);
			RES.find('.ov').removeClass('ov');
			if(sp.hasClass('info'))
				return;
			sp.addClass('ov');
		});
	}
	function funcWrite() {//выделение символов при поиске
		var v = $.trim(INP.val()),
			find = [],
			reg = new RegExp(v, 'i'); // для замены найденного значения
		_forN(MASS_SEL_SAVE, function(sp) {
			var un = _copyObj(sp),
				arr = un.content.split(TAG); // разбивка на массив согласно тегам
			_forN(arr, function(r, k) {
				if(!r.length)    //если строка пустая
					return;
				if(TAG.test(r))  //если это тег
					return;
				if(!reg.test(r)) //если нет совпадения
					return;

				arr[k] = r.replace(reg, '<em class="fndd">$&</em>'); // производится замена
				un.content = arr.join('');
				find.push(un);
				return false; // и сразу выход из массива
			});
		});
		MASS_SEL = find;
		spisokPrint();
	}
	function valueSet(v) {//установка значения
		if(o.multi)
			return multiValueSet(v);
		if(REGEXP_CENA_MINUS.test(v))
			v = _num(v, 1);
		VALUE = v;
		t.val(v);
		INP.val(MASS_ASS[v] ? MASS_ASS[v].replace(/&quot;/g,'"') : '');
		ICON_DEL._dn(v && o.write);
		if(BG_ASS[v]) {
			SEL.css('background-color', BG_ASS[v]);
			INP.css('background-color', BG_ASS[v]);
		}
	}
	function multiValueSet(v) {//обновление массива и ширины инпута после вставки значения, если мульти-выбор
		v = _num(v);
		multiBefore(v);

		var dd = DL.find('dd:last'),
			w = DLW - 10,
			vv = [];
		if(dd.length) {
			var ol = dd[0].offsetLeft,
				ow = dd[0].offsetWidth,
				inpW = DLW - ol - ow - 10;
			w = inpW < 30 ? w : inpW;
			_forEq(DL.find('dd'), function(sp) {
				vv.push(sp.attr('val'));
			});
		}
		INP.width(w);
		INP.attr('placeholder', dd.length ? '' : o.title0);
		t.val(vv.join(','));
		spisokPrint();
	}
	function multiPrint() {//вывод выбранных значений при мульти-выборе
		_forIn(_idsAss(t.val()), function(i, id) {
			multiBefore(id);
		});
	}
	function multiBefore(v) {//вставка значения, если мульти-выбор
		if(!v)
			return;
		if(!MASS_ASS[v])
			return;
		INP.before(
			'<dd class="multi" val="' + v + '">' +
				MASS_ASS[v] +
				'<div class="icon icon-del pl"></div>' +
			'</dd>'
		);
		INP.val('');
		DL.find('.icon:last').click(function() {
			$(this).parent().remove();
			multiValueSet();
		});
	}
	function action() {//выполнение действия в существующем селекте
		if(s === undefined)
			return t;

		if(typeof o == 'number') {
			s.value(o);
			return s;
		}

		switch(o) {
			case 'disable': s.disable(); break;
			case 'enable': s.enable(); break;
			case 'inp': return s.inp();
			case 'spisok': s.spisok(o1); break;
			case 'process': s.process(); break;
			case 'cancel': s.cancel(); break;
		}

		return s;
	}

	t.value = valueSet;
	t.icon_del = ICON_DEL;
	t.icon_add = ICON_ADD;
	t.inp = function() {//получение введённого значения
		return INP.val();
	};
	t.disable = function() {//делание неактивным
		SEL.addClass('disabled')
		   .removeClass('rs');
		INP.attr('readonly', true);
		SEL.find('.td-add')._dn();
		dis = true;
	};
	t.enable = function() {//делание активным
		SEL.removeClass('disabled');
		dis = false;
	};
	t.process = function() {//показ процесса ожидания
		if(!o.write)
			ICON_DEL.parent().removeClass('dn');
		ICON_DEL.addClass('spin');
	};
	t.isProcess = function() {//получение флага процесса ожидания
		return ICON_DEL.hasClass('spin');
	};
	t.cancel = function() {//отмена процесса ожидания
		if(!o.write)
			ICON_DEL.parent().addClass('dn');
		ICON_DEL.removeClass('spin');
	};
	t.spisok = function(spisok) {//вставка нового списка
		t.cancel();
		o.spisok = spisok;
		massCreate();
		spisokPrint();
	};
	t.unitUnshift = function(unit) {//вставка единицы в начало существующего списка
		o.spisok.unshift(unit);
		massCreate();
		spisokPrint();
	};

	window[win] = t;
	return t;
};
$.fn._select1 = function(o, o1, o2) {
	var t = $(this),
		n,
		s,
		id = t.attr('id'),
		val = t.val() || 0;

	if(!id)
		return;

	switch(typeof o) {
		default:
		case 'undefined': break;
		case 'object': break;
		case 'number':
		case 'string':
			s = window[id + '_select'];
			switch(o) {
				case 'process': s.process(); break;
				case 'is_process': return s.isProcess();
				case 'load'://загрузка нового списка
					s.process();
					_post1(o1, function(res) {
						if(res.success) {
							s.spisok(res.spisok);
							if(o2)
								o2(res);
						} else
							s.cancel();
					});
					break;
				case 'cancel': s.cancel(); break;
				case 'clear': s.clear(); break;//очищение inp, установка val=0
				case 'empty': s.empty(); break;//удаление списка, установка val=0
				case 'title0'://установка или получение title0
					if(o1) {
						s.title0(o1);
						return s;
					}
					return s.title0();
				case 'title': return s.title();
				case 'inp': return s.inp();
				case 'focus': s.focus(); break;
				case 'first': s.first(); break;//установка первого элемента в списке
				case 'disabled': s.disabled(); break;
				case 'remove':
					$('#' + id + '_select').remove();
					window[id + '_select'] = null;
					break;
				default:
					if(REGEXP_NUMERIC_MINUS.test(o)) {
						var write_save = s.o.write_save;
						s.o.write_save = 0;
						s.value(o);
						s.o.write_save = write_save;
					}
			}
			return t;
/*			//если это первый вход, то пропуск
			s = window[id + '_select'];
			if(!s)
				break;

			//вставка списка после загрузки
			if('length' in o) {
				s.spisok(o);
				return t;
			}
			if(!('spisok' in o))
				return t;
*/
	}

	o = $.extend({
		width:180,			// ширина
		disabled:0,
		block:0,       	    // расположение селекта
		title0:'',			// поле с нулевым значением
		spisok:[],			// результаты в формате json
		limit:0,
		write:0,            // возможность вводить значения
		write_save:0,       // сохранять текст, если даже не выбран элемент
		nofind:'Список пуст',
		multiselect:0,      // возможность выбирать несколько значений. Идентификаторы перечисляются через запятую
		func:function() {},	// функция, выполняемая при выборе элемента
		funcAdd:null,		// функция добавления нового значения. Если не пустая, то выводится плюсик. Функция передаёт список всех элементов, чтобы можно было добавить новый
		funcKeyup:funcKeyup	// функция, выполняемая при вводе в INPUT в селекте. Нужна для вывода списка из вне, например, Ajax-запроса, либо из vk api.
	}, o);

	if(o.multiselect || o.write_save)
		o.write = true;

	o.clear = o.write && !o.multiselect;

	var inpWidth = o.width - 17 - 5 - 4;
	if(o.funcAdd)
		inpWidth -= 18;
	if(o.clear) {
		inpWidth -= 24;
		val = _num(val);
	}
	var dis = o.disabled ? ' disabled' : '',
		dib = o.block ? '' : ' dib',
		html =
		'<div class="_select' + dis + dib + '" id="' + id + '_select" style="width:' + o.width + 'px">' +
//			'<div class="title0bg" style="width:' + inpWidth + 'px">' + o.title0 + '</div>' +
			'<table class="seltab">' +
				'<tr><td class="selsel">' +
						'<input type="text"' +
							  ' class="selinp"' +
							  ' placeholder="' + o.title0 + '"' +
							//  ' style="width:' + inpWidth + 'px' +
							//		(o.write && !o.disabled? '' : ';cursor:default') + '"' +
									(o.write && !o.disabled? '' : ' readonly') +
						' />' +
					(o.clear ? '<div class="icon icon-del mt5 fr' + _dn(val) + _tooltip('Очистить', -49, 'r') + '</div>' : '') +
	   (o.funcAdd ? '<td class="seladd">' : '') +
					'<td class="selug">' +
			'</table>' +
			'<div class="selres" style="width:' + o.width + 'px"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);
//return t;
	var select = t.next(),
		inp = select.find('.selinp'),
		inpClear = select.find('.icon-del'),
		sel = select.find('.selsel'),
		res = select.find('.selres'),
		resH, //Высота списка до обрезания
		title0bg = select.find('.title0bg'), //Нулевой title как background
		ass,            //Ассоциативный массив с названиями
		save = [],      //Сохранение исходного списка
		assHide = {},   //Ассоциативный массив с отображением в списке
		multiCount = 0, //Количество выбранных мульти-значений
		tag = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // поиск всех тегов
		keys = {38:1,40:1,13:1,27:1,9:1};

	assCreate();

	if(o.multiselect) {
		if(val != 0) {
			var arr = val.split(',');
			for(n = 0; n < arr.length; n++) {
				assHide[arr[n]] = true;
				inp.before('<div class="multi">' + ass[arr[n]] + '<span class="x" val="' + arr[n] + '"></span></div>');
			}
		}
		multiCorrect();
	}
	if(o.funcAdd && !o.disabled)
		select.find('.seladd').click(function() {
			o.funcAdd(id);
		});

	spisokPrint();
	setVal(val);

	var keyVal = inp.val();//Вводимое значение из inp

	if(!o.disabled) {
		$(document)
			.off('click', '#' + id + '_select .selug')
			.on('click', '#' + id + '_select .selug', hideOn)

			.off('click', '#' + id + '_select .selsel')
			.on('click', '#' + id + '_select .selsel', function() { inp.focus(); })

			.off('click', '#' + id + '_select .selun')
			.on('click', '#' + id + '_select .selun', function() { unitSel($(this)); })

			.off('mouseenter', '#' + id + '_select .selun')
			.on('mouseenter', '#' + id + '_select .selun', function() {
				res.find('.ov').removeClass('ov');
				$(this).addClass('ov');
			})

			.off('click', '#' + id + '_select .x')
			.on('click', '#' + id + '_select .x', function(e) {
				e.stopPropagation();
				var v = $(this).attr('val');
				$(this).parent().remove();
				multiCorrect(v, false);
				setVal(v);
				o.func(v, id);
			});

		inp	.focus(function() {
				hideOn();
				if(o.write)
					title0bg.css('color', '#ccc');
			})
			.blur(function() {
				if(o.write)
					title0bg.css('color', '#888');
			})
			.keyup(function(e) {
				if(keys[e.keyCode])
					return;
				title0bg[inp.val() || multiCount ? 'hide' : 'show']();
				inpClear._dn(inp.val());
				if(keyVal != inp.val()) {
					keyVal = inp.val();
					o.funcKeyup(keyVal, t);
					t.val(0);
					val = 0;
				}
			});

		inpClear.click(function(e) {
			e.stopPropagation();
			setVal(0);
			inp.val('');
			title0bg.show();
			inpClear._dn(0);
			o.func(0, id);
			o.funcKeyup('', t);
		});
	}

	function spisokPrint() {
		if(!o.spisok.length) {
			res.html('<div class="nofind">' + o.nofind + '</div>')
			   .removeClass('h250');
			return;
		}
		if(o.write)
			findEm();
		var spisok = o.title0 && !o.write ? '<div class="selun title0" val="0">' + o.title0 + '</div>' : '',
			len = o.spisok.length;
		if(o.limit && len > o.limit)
			len = o.limit;
		for(n = 0; n < len; n++) {
			var sp = o.spisok[n];
			if(assHide[sp.uid])
				continue;
			spisok += '<div class="selun" val="' + sp.uid + '">' + (sp.content || sp.title) + '</div>';
		}
		res.removeClass('h250')
		   .html(spisok)
		   .find('.selun:last').addClass('last');
		resH = res.height();
		if(resH > 250)
			res.addClass('h250');
	}
	function spisokMove(e) {
		if(!keys[e.keyCode])
			return;
		e.preventDefault();
		var u = res.find('.selun'),
			res0 = res[0],
			len = u.length,
			ov;
		for(n = 0; n < len; n++)
			if(u.eq(n).hasClass('ov'))
				break;
		switch(e.keyCode) {
			case 38: //вверх
				if(n == len)
					n = 1;
				if(n > 0) {
					if(len > 1) // если в списке больше одого элемента
						u.eq(n).removeClass('ov');
					ov = u.eq(n - 1);
				} else
					ov = u.eq(0);
				ov.addClass('ov');
				ov = ov[0];
				if(res0.scrollTop > ov.offsetTop)// если элемент ушёл вверх выше видимости, ставится в самый верх
					res0.scrollTop = ov.offsetTop;
				if(ov.offsetTop - 250 - res0.scrollTop + ov.offsetHeight > 0) // если ниже, то вниз
					res0.scrollTop = ov.offsetTop - 250 + ov.offsetHeight;
				break;
			case 40: //вниз
				if(n == len) {
					u.eq(0).addClass('ov');
					res0.scrollTop = 0;
				}
				if(n < len - 1) {
					u.eq(n).removeClass('ov');
					ov = u.eq(n+1);
					ov.addClass('ov');
					ov = ov[0];
					if(ov.offsetTop + ov.offsetHeight - res0.scrollTop > 250) // если элемент ниже видимости, ставится в нижнюю позицию
						res0.scrollTop = ov.offsetTop + ov.offsetHeight - 250;
					if(ov.offsetTop < res0.scrollTop) // если выше, то в верхнюю
						res0.scrollTop = ov.offsetTop;
				}
				break;
			case 13: //Enter
				if(n < len) {
					inp.blur();
					unitSel(u.eq(n));
					hideOff();
				}
				break;
			case 27: //ESC
			case 9: //Tab
				inp.blur();
				hideOff();
		}
	}
	function unitSelShow() {//выделение выбранного поля и выставление его в зоне видимости
		var u = res.find('.selun'),
			res0 = res[0];
		u.removeClass('ov');
		for(n = 0; n < u.length; n++) {
			var ov = u.eq(n);
			if(ov.attr('val') == val) {
				ov.addClass('ov');
				ov = ov[0];
				var top = ov.offsetTop + ov.offsetHeight;
				if(top > 170) {
					var resMax = 250;
					if(resH > top)
						resMax -= resH - top > 120 ? 120 : resH - top;
					res0.scrollTop = top - resMax;
				}
				break;
			}
		}
	}
	function assCreate() {//Создание ассоциативного массива
		ass = o.title0 ? {0:''} : {};
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			ass[sp.uid] = sp.title;
			if(!sp.content)
				sp.content = sp.title;
			save.push({
				uid:sp.uid,
				title:sp.title,
				content:sp.content
			});
		}
	}
	function funcKeyup() {
		o.spisok = [];
		for(n = 0; n < save.length; n++) {
			var sp = save[n];
			o.spisok.push({
				uid:sp.uid,
				title:sp.title,
				content:sp.content
			});
		}
		spisokPrint();
	}
	function setVal(v) {
		if(o.multiselect) {
			if(!multiCount) {
				t.val(0);
				return;
			}
			var x = sel.find('.x'),
				arr = [];
			for(n = 0; n < x.length; n++)
				arr.push(x.eq(n).attr('val'));
			t.val(arr.join());
			return;
		}
		val = v;
		t.val(v);
		if(v || !v && !o.write_save) {
			inp.val(ass[v] ? ass[v].replace(/&quot;/g,'"') : '');
			title0bg[v == 0 ? 'show' : 'hide']();
		}
	}
	function unitSel(t) {
		var v = parseInt(t.attr('val')),
			item = {};
		if(o.multiselect) {
			if(!o.title0 && !v || v > 0)
				inp.before('<div class="multi">' + ass[v] + '<span class="x" val="' + v + '"></span></div>');
			multiCorrect(v, true);
		}
		setVal(v);
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			if(sp.uid == v) {
				item = sp;
				break;
			}
		}
		o.func(v, id, item);
		keyVal = inp.val();
	}
	function multiCorrect(v, ch) {//Выравнивание значений списка multi
		var multi = sel.find('.multi'),
			w = 0;
		multiCount = multi.length;
		for(n = 0; n < multiCount; n++) {
			var mw = multi.eq(n).width();
			if(w + mw > inpWidth + 4)
				w = 0;
			w += mw + 5 + 2;
		}
		w = inpWidth - w;
		inp.width(w < 25 ? inpWidth : w);
		if(v !== undefined) {
			assHide[v] = ch;
			spisokPrint();
			if(!o.title0 && v == 0 || v > 0)
				inp.val('');
		}
		title0bg[multiCount ? 'hide' : 'show']();
	}
	function findEm() {
		var v = inp.val();
		if(v && v.length) {
			var find = [];
				reg = new RegExp(v, 'i'); // для замены найденного значения
			for(n = 0; n < o.spisok.length; n++) {
				var sp = o.spisok[n],
					arr = sp.content.split(tag); // разбивка на массив согласно тегам
				for(var k = 0; k < arr.length; k++) {
					var r = arr[k];
					if(r.length) // если строка не пустая
						if(!tag.test(r)) // если это не тег
							if(reg.test(r)) { // если есть совпадение
								arr[k] = r.replace(reg, '<em>$&</em>'); // производится замена
								sp.content = arr.join('');
								find.push(sp);
								break; // и сразу выход из массива
							}
				}
				if(o.limit && find.length == o.limit)
					break;
			}
			o.spisok = find;
		}
	}
	function hideOn() {
		if(!select.hasClass('rs')) {
			select.addClass('rs');

			if(res.height() > 250)
				res.addClass('h250');

			//увеличение области видимости, если список уходит вниз экрана
			var st = select.offset().top;//положение селекта сверху страницы
			if(window.FB && (st + 250) > FB.height())
				FB.height(st + 350);


			unitSelShow();
			$(document)
				.on('click.' + id + '_select', hideOff)
				.on('keydown.' + id + '_select', spisokMove);
		}
	}
	function hideOff() {
		if(!inp.is(':focus')) {
			select.removeClass('rs');
			if(o.write && !val) {
				if(inp.val() && !o.write_save) {
					inp.val('');
					o.funcKeyup('', t);
				}
				setVal(0);
				o.func(0, id);
			}
			$(document)
				.off('click.' + id + '_select')
				.off('keydown.' + id + '_select');
		}
	}

	t.o = o;
	t.value = setVal;
	t.process = function() {//Показ ожидания загрузки в selinp
		inp.addClass('_busy');
	};
	t.isProcess = function() {//проверка наличия ожидания загрузки в selinp
		return inp.hasClass('_busy');
	};
	t.cancel = function() {//Отмена ожидания загрузки в selinp
		inp.removeClass('_busy');
	};
	t.clear = function() {//очищение inp, установка val=0
		if(o.multiselect) {
			sel.find('.multi').remove();
			multiCorrect();
		}
		setVal(0);
		inp.val('');
		title0bg.show();
		o.func(0, id);
	};
	t.spisok = function(v) {
		t.cancel();
		o.spisok = v;
		assCreate();
		spisokPrint();
		setVal(val);
	};
	t.empty = function() {//удаление списка, установка val=0
		if(o.multiselect) {
			sel.find('.multi').remove();
			multiCorrect();
		}
		setVal(0);
		inp.val('');
		title0bg.show();
		o.spisok = [];
		assCreate();
		spisokPrint();
		o.func(0, id);
	};
	t.title0 = function(v) {//Получение|установка нулевого значения
		if(v) {
			title0bg.html(v);
			select.find('.title0').html(v);
			return;
		}
		return title0bg.html();
	};
	t.title = function() {//Получение содержимого установленного значения
		return ass[t.val()];
	};
	t.inp = function() {//Получение содержимого введённого значения
		return inp.val();
	};
	t.focus = function() {//установка фокуса на input
		inp.focus();
	};
	t.disabled = function() {//установка недоступности селекта
		select.addClass('disabled');
		res.remove();
		inp.remove();
	};
	t.first = function() {//установка первого элемента в списке
		if(!o.spisok.length)
			setVal(0);
		setVal(o.spisok[0].uid);
	};

	window[id + '_select'] = t;
	return t;
};
$.fn._hint = function(o) {//выплывающие подсказки
	var t = $(this);

	if(!t.length)
		return;

	//счётчик подсказок. Для удаления именно той подсказки, которая была добавлена
	if(!window.HINT_NUM)
		HINT_NUM = 1;

	//если с поля подсказки мышь возвращается на объект, то ничего не происходит
	if(o.show && t.hasClass('hnt' + HINT_NUM))
		return t;

	o = $.extend({
		msg:'Пусто',//сообщение подсказки
		color:'',   //цвет текста (стиль)
		width:0,    //фиксированная ширина. Если 0 - автоматически
		pad:1,      //внутренние отступы контента

		side:'auto',    //сторона, с которой подсказка показывается относительно элемента (auto, top, right, bottom, left)
		ugPos:'center', //позиция уголка на подсказке: top, bottom (для вертикали), left, right (для горизонтали). Либо число в пикселях слева либо сверху.
		objPos:'center',//позиция объекта, в которую будет указывать уголок.
						//top, bottom - для вертикали
	                    //left, right - для горизонтали
						//mouse - относительно положения мыши при первом касании объекта
	                    //число в пикселях слева либо сверху.

		show:0,	     //сразу показывать подсказку. После показа удаляется.

		event:'mouseenter', // событие, при котором происходит всплытие подсказки
		speed:200,//скорость появления, скрытия
		delayShow:0, // задержка перед всплытием
		delayHide:0, // задержка перед скрытием

		func:function() {},         //функция, которая выполняется после вставки контента
		funcBeforeHide:function() {}//функция, которая выполняется перед началом скрытия подсказки
	}, o);

	//корректировка минимальной ширины с учётом отступов
	if(o.width) {
		if(o.width < 12)
			o.width = 12;
		if(o.width - 2 - o.pad * 2 < 0)
			o.width = o.pad * 2 + 12;
	}

	var HN = ++HINT_NUM,
		body = $('body'),
		width = o.width ? ' style="width:' + o.width + 'px"' : '',
		pad = o.pad ? ' style="padding:' + o.pad + 'px"' : '',
		color = o.color ? ' ' + o.color : '',
		html =
			'<div class="_hint" id="hint' + HN + '"' + width + '>' +
				'<div class="prel"' + pad + '>' +
					'<div class="ug"><div></div></div>' +
					'<div class="hi-msg' + color + '">' + o.msg + '</div>' +
				'</div>' +
			'</div>';

	body.find('._hint').remove();
	body.append(html);

	var HINT = $('#hint' + HN),
		MSG = HINT.find('.hi-msg'),
		UG = HINT.find('.ug');

	o.func(MSG);

	HINT.css('z-index', ZINDEX + 6);

	//автоматический подбор ширины, если строка слишком длинная
	if(!o.width) {
		var msgW = Math.ceil(MSG.width()),
			msgH = Math.ceil(MSG.height()),
			k = 23;//коэффициэнт по высоте строки, который определяет минимальную ширину, от которой нужно начинать изменение
		if(msgW > msgH * k) {
			var del = msgW / (msgH * k);
			msgW = Math.ceil(msgW / del);
			MSG.width(msgW);
		}
	}

	var W = Math.ceil(HINT.css('width').split('px')[0]),      //полная ширина подсказки с учётом рамок
		H = Math.ceil(HINT.css('height').split('px')[0]),     //полная высота подсказки с учётом рамок

		TBS = t.css('box-sizing'),
		objW = hintObjW(),//ширина объекта
		objH = hintObjH(),//высота объекта

		slide = 20, //расстояние, на которое сдвигается подсказка при появлении
		SIDE = o.side,       //сторона, с которой будет выплывать подсказка
		topStart,
		topEnd,
		leftStart,
		leftEnd,

		// процессы всплытия подсказки:
		// - wait_to_showing - ожидает показа (мышь была наведена)
		// - showing - выплывает
		// - showed - показана
		// - wait_to_hidding - ожидает скрытия (мышь была отведена)
		// - hidding - скрывается
		// - hidden - скрыта
		process = 'hidden',
		timer = 0;

	//принудительное выставление ширины для того, чтобы подсказка оставалась нужного размера и не изменялась, если упирается в правую часть экрана
	if(!o.width)
		HINT.width(W - 2);

	t.on(o.event + '.hint' + HN, hintShow);
	t.on('mouseleave.hint' + HN, hintHide);
	HINT.on('mouseenter.hint' + HN, hintShow)
		.on('mouseleave.hint' + HN, hintHide);

	// автоматический показ подсказки, если нужно
	if(o.show) {
		t.addClass('hnt' + HN);
		t.addClass('hint-show');
		if(o.objPos != 'mouse')
			hintShow();
		t.on('mousemove.hint' + HN, hintShow);
	}

	function hintObjW() {//получение ширины объекта
		var w = Math.round(t.css('width').split('px')[0]);//внутренняя ширина
		w += Math.round(t.css('border-left-width').split('px')[0]);//рамка справа
		w += Math.round(t.css('border-right-width').split('px')[0]);//рамка слева
		if(TBS != 'border-box') {
			w += Math.round(t.css('padding-left').split('px')[0]);//отступ слева
			w += Math.round(t.css('padding-right').split('px')[0]);//отступ справа
		}
		return w;
	}
	function hintObjH() {//получение высоты объекта
		var h = Math.round(t.css('height').split('px')[0]);//внутренняя высота
		h += Math.round(t.css('border-top-width').split('px')[0]);//рамка сверху
		h += Math.round(t.css('border-bottom-width').split('px')[0]);//рамка снизу
		if(TBS != 'border-box') {
			h += Math.round(t.css('padding-top').split('px')[0]);//отступ сверху
			h += Math.round(t.css('padding-bottom').split('px')[0]);//отступ снизу
		}
		return h;
	}
	function hintSideAuto() {//автоматическое определение стороны появления подсказки
		if(o.side != 'auto')
			return o.side;

		var offset = t.offset(),
			screenW = $(window).width(), //ширина экрана видимой области
			screenH = $(window).height(),//высота экрана видимой области
			scrollTop = $(window).scrollTop(),//прокручено сверху
			scrollLeft = $(window).scrollLeft(),//прокручено слева
			diff = {//свободное пространство для отображения подсказки в порядке приоритета
				top:offset.top - scrollTop - H - 6,
				bottom:screenH + scrollTop - offset.top - objH - H - 6 - slide,
				left:offset.left - scrollLeft - W - 6,
				right:screenW + scrollLeft - offset.left - objW - W - 6
			},
			minMinus = -9999,//минимальный минус, если ни с одной стороны нет свободного пространства
			minMinusSide;    //сторона минимального минуса

		//выбор из наибольшей зоны видимости
		for(var sd in diff) {
			if(diff[sd] > 0) {
				SIDE = sd;
				break;
			}
			if(minMinus < diff[sd]) {
				minMinus = diff[sd];
				minMinusSide = sd;
			}
		}

		return sd;
	}
	function hintPosition(e) {//позиционирование подсказки перед показом
		var offset = t.offset();

		SIDE = hintSideAuto();

		UG.removeClass('ugb ugl ugt ugr');
		//позиционирование уголка
		var ugPos = 0;
		switch(SIDE) {
			case 'top':
			case 'bottom':
				ugPos = Math.floor(W / 2) - 6;
				switch(o.ugPos) {
					case 'center': break;
					case 'left':ugPos = ugPos < 15 ? ugPos : 15; break;
					case 'right': ugPos = W - 15 - 11 < ugPos ? ugPos : W - 15 - 11; break;
					default:
						ugPos = _num(o.ugPos);
						if(ugPos < 2)
							ugPos = 2;
						if(ugPos > W - 11 - 4)
							ugPos = W - 11 - 4;
				}
				UG.css({'padding-left':ugPos});
				break;
			case 'left':
			case 'right':
				ugPos = Math.floor(H / 2) - 6;
				switch(o.ugPos) {
					case 'center': break;
					case 'top': ugPos = ugPos < 15 ? ugPos : 15;  break;
					case 'bottom': ugPos = H - 15 - 11 < ugPos ? ugPos : H - 15 - 11; break;
					default:
						ugPos = _num(o.ugPos);
						if(ugPos < 2)
							ugPos = 2;
						if(ugPos > H - 11 - 4)
							ugPos = H - 11 - 4;
				}
				UG.css({'padding-top':ugPos});
		}

		//определение позиции объекта, на которое будет показываться уголок
		var objPos = 0;
		switch(SIDE) {
			case 'top':
			case 'bottom':
				objPos = Math.floor(objW / 2);
				switch(o.objPos) {
					case 'center': break;
					case 'left': objPos = objPos < 15 ? objPos : 15; break;
					case 'right': objPos = objPos < 15 ? objPos : objW - 15; break;
					case 'mouse': objPos = e.pageX - offset.left; break;
					default:
						objPos = _num(o.objPos);
						if(objPos > objW - 1)
							objPos = objW - 1;
				}
				break;
			case 'left':
			case 'right':
				objPos = Math.floor(objH / 2);
				switch(o.objPos) {
					case 'center': break;
					case 'top':
						if(objPos > ugPos + 6) {
							objPos = ugPos + 6;
							break;
						}
						objPos = objPos < 15 ? objPos : 15;
						break;
					case 'bottom':
						if(objH - objPos > H - ugPos) {
							objPos = objH - (H - ugPos - 6);
							break;
						}
						objPos = objPos < 15 ? objPos : objH - 15;
						break;
					case 'mouse': objPos = e.pageY - offset.top; break;
					default:
						objPos = _num(o.objPos);
						if(objPos > objH - 1)
							objPos = objH - 1;
				}
		}

		switch(SIDE) {
			case 'top':
				UG.addClass('ugb');
				topEnd = offset.top - H - 6;
				topStart = topEnd - slide;
				leftEnd = offset.left - ugPos + objPos - 6;
				leftStart = leftEnd;
				break;
			case 'bottom':
				UG.addClass('ugt');
				topEnd = offset.top + objH + 6;
				topStart = topEnd + slide;
				leftEnd = offset.left - ugPos + objPos - 6;
				leftStart = leftEnd;
				break;
			case 'left':
				UG.addClass('ugr');
				topEnd = offset.top - ugPos + objPos - 6;
				topStart = topEnd;
				leftEnd = offset.left - W - 6;
				leftStart = leftEnd - slide;
				break;
			case 'right':
				UG.addClass('ugl');
				topEnd = offset.top - ugPos + objPos - 6;
				topStart = topEnd;
				leftEnd = offset.left + objW + 6;
				leftStart = leftEnd + slide;
				break;
		}
	}
	function hintShow(e) {//процесс показа подсказки
		if(o.show)
			t.off('mousemove.hint' + HN);

		switch(process) {
			case 'wait_to_hidding':
				process = 'showed';
				clearTimeout(timer);
				break;
			case 'hidding':
				process = 'showing';
				HINT.stop()
					.animate({
							top:topEnd,
							left:leftEnd,
							opacity:1
						},
						o.speed,
						function() { process = 'showed' });
				break;
			case 'hidden':
				if(!o.delayShow) {
					action();
					break;
				}
				process = 'wait_to_showing';
				timer = setTimeout(action, o.delayShow);
				break;
		}
		//действие всплытия подсказки
		function action() {
			process = 'showing';
			hintPosition(e);
			HINT.css({top:topStart, left:leftStart, opacity:0})
				.animate({top:topEnd, left:leftEnd, opacity:1}, o.speed, function() { process = 'showed' });
		}
	}
	function hintHide() {//процесс скрытия подсказки
		switch(process) {
			case 'wait_to_showing':
				process = 'hidden';
				clearTimeout(timer);
				if(o.show)
					hidding();
				break;
			case 'showing':
				HINT.stop();
				hidding();
				break;
			case 'showed':
				if(!o.delayHide) {
					hidding();
					break;
				}
				process = 'wait_to_hidding';
				timer = setTimeout(hidding, o.delayHide);
				break;
		}
		function hidding() {
			process = 'hidding';
			o.funcBeforeHide();
			HINT.animate({opacity:0}, o.speed, function () {
				process = 'hidden';
				HINT.css({top:-9999, left:-9999});
				//подсказка, которая автоматически показывается, удаляется
				if(o.show) {
					HINT.remove();
					t.off(o.event + '.hint' + HN);
					t.off('mouseleave.hint' + HN);
					t.removeClass('hnt' + HN);
					t.removeClass('hint-show');
				}
			});
		}
	}

	return t;
};
$.fn._tooltip = function(msg, left, ugolSide) {
	var t = $(this);

	t.find('.ttdiv').remove();
	t.addClass('_tooltip');
	t.append(
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>'
	);
	//автопозиционирование подсказки
	if(!left) {
		var ttdiv = t.find('.ttdiv');
		left = Math.ceil(ttdiv.width() / 2) - 9;
		ttdiv.css('left', '-' + left + 'px');
	}

	return t;
};
$.fn._calendar = function(o) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id'),
		VALUE = t.val();

	if(!attr_id) {
		attr_id = 'calendar' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_calendar',
		S = window[win],
		n;

	o = $.extend({
		lost:1,                //если не 0, то можно выбрать прошедшие дни
		time:0,                //показывать время
		tomorrow:0,            //ссылка "завтра" для быстрой установки завтрашней даты
		func:function () {}    //исполняемая функция при выборе дня
	}, o);


	//удаление такого же календаря при повторном вызове
	t.next().remove('._calendar');
	t.after(
		'<div class="_calendar" id="' + win + '">' +
			'<div class="icon icon-calendar"></div>' +
			'<input type="text" class="cal-inp" readonly />' +

		(o.time ?
			'<div class="dib ml8">' +
				'<input type="hidden" id="' + attr_id + '_hour"/>' +
			'</div>' +
			'<div class="dib b ml3 mr3">:</div>' +
			'<input type="hidden" id="' + attr_id + '_min"/>'
		: '') +

			'<div class="cal-abs dn">' +
				'<table class="cal-head">'+
					'<tr><td class="cal-back">' +
						'<td class="cal-mon">' +
						'<td class="cal-next">' +
				'</table>' +
				'<table class="cal-week"><tr><td>Пн<td>Вт<td>Ср<td>Чт<td>Пт<td>Сб<td>Вс</table>' +
				'<table class="cal-day"></table>' +
			'</div>' +
		'</div>'
	);

	if(o.time) {
		$('#' + attr_id + '_hour')._count({
			min:0,
			max:23,
			again:1,
			time:1
		});
		$('#' + attr_id + '_min')._count({
			step:10,
			min:0,
			max:50,
			again:1,
			time:1
		});
	}

	var D = new Date(),
		CUR_YEAR = D.getFullYear(), //текущий год
		CUR_MON =  D.getMonth() + 1,//текущий месяц
		CUR_DAY =  D.getDate(),     //текущий день

		TAB_YEAR = CUR_YEAR,        //год, отображаемый в календаре
		TAB_MON =  CUR_MON,         //месяц, отображаемый в календаре

		VAL_YEAR = CUR_YEAR,    //выбранный год
		VAL_MON =  CUR_MON,     //выбранный месяц
		VAL_DAY =  CUR_DAY,     //выбранный день

		CAL = t.next(),
		INP = CAL.find('.cal-inp'),        //текстовое отображение выбранного дня
		CAL_ABS = CAL.find('.cal-abs'), //содержание календаря
		TD_MON = CAL.find('.cal-mon'),  //строка td с месяцем и годом
		TD_WEEK = CAL.find('.cal-week'),//таблица с названиями недель
		TAB_DAY = CAL.find('.cal-day'); //таблица с днями

	valTest();
	tdMonUpd();
	dayPrint();

	INP.click(function() {
		if(CAL.hasClass('disabled'))
			return;
		var on = CAL_ABS.hasClass('dn');
		TAB_YEAR = VAL_YEAR;
		TAB_MON = VAL_MON;
		tdMonUpd();
		dayPrint();
		TD_WEEK._dn(1);
		TAB_DAY._dn(1, 'mon');
		CAL_ABS._dn(on);
	});
	CAL.find('.cal-back').click(back);
	CAL.find('.cal-next').click(next);
	TD_MON.click(function() {
		TD_WEEK._dn();
		TAB_DAY._dn(0, 'mon');
		TD_MON.html(TAB_YEAR);
		monPrint();
	});


	$(document)
		.off('click._calendar')
		.on('click._calendar', function(e) {
			var cur = $(e.target).closest('._calendar,.cal-tb'),//текущий календарь
				attr = '';  //id текущего календаря

			//закрытие календарей, когда нажатие было в стороне
			//кроме текущего, если натажие было на нём
			if(cur.hasClass('_calendar'))
				attr = ':not(#' + cur.attr('id') + ')';

			if(cur.hasClass('cal-tb'))
				attr = ':not(#' + cur.attr('val') + ')';

			$('._calendar' + attr + ' .cal-abs')._dn();
		});

	function valTest() {//проверка текущего значения, установка, если некорректное
		if(!VALUE.length)
			return valUpd();
		if(!REGEXP_DATE.test(VALUE))
			return valUpd();

		var ex = VALUE.split('-');
		if(!_num(ex[0]) || !_num(ex[1]) || !_num(ex[2]))
			return valUpd();

		VAL_YEAR = _num(ex[0]);
		VAL_MON =  _num(ex[1]);
		VAL_DAY =  _num(ex[2]);
		TAB_YEAR = VAL_YEAR;
		TAB_MON = VAL_MON;
		valUpd();
	}
	function valUpd() {//обновление значения
		VALUE = TAB_YEAR + '-' + (TAB_MON < 10 ? '0' : '') + TAB_MON + '-' + (VAL_DAY < 10 ? '0' : '') + VAL_DAY;
		t.val(VALUE);
		INP.val(VAL_DAY + ' ' + MONTH_DAT[VAL_MON] + ' ' + VAL_YEAR);
	}
	function tdMonUpd() {
		TD_MON.html(MONTH_DEF[TAB_MON] + ' ' + TAB_YEAR);
	}
	function dayPrint() {//вывод списка дней
		var html = '<tr>',
			df = dayFirst(),
			dc = dayCount(TAB_YEAR, TAB_MON),
			cur = CUR_YEAR == TAB_YEAR && CUR_MON == TAB_MON,// выделение текущего дня, если показан текущий год и месяц
			st =  VAL_YEAR == TAB_YEAR && VAL_MON == TAB_MON;// выделение выбранного дня, если показан год и месяц выбранного дня

		//установка пустых ячеек
		if(df > 1)
			for(n = 0; n < df - 1; n++)
				html += '<td>';

		for(n = 1; n <= dc; n++) {
			var l = '';
			if(TAB_YEAR < CUR_YEAR) l = ' lost';
			else if(TAB_YEAR == CUR_YEAR && TAB_MON < CUR_MON) l = ' lost';
			else if(TAB_YEAR == CUR_YEAR && TAB_MON == CUR_MON && n < CUR_DAY) l = ' lost';
			var b = cur && n == CUR_DAY ? ' b' : '',
				set = st && n == VAL_DAY ? ' set' : '',
				sel = !l || l && o.lost ? ' sel' : '';
			html += '<td class="' + sel + set + b +	l + '">' + n;
			if(++df > 7 && n != dc) {
				html += "<tr>";
				df = 1;
			}
		}
		TAB_DAY
			.html('<tbody class="cal-tb" val="' + win + '">' + html + '</tbody>')
			.find('.sel').click(daySel);
	}
	function dayFirst() {//номер первой недели в месяце
		var first = new Date(TAB_YEAR, TAB_MON - 1, 1).getDay();
		return first || 7;
	}
	function dayCount(year, mon) {//количество дней в месяце
		mon--;
		if(!mon) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function daySel() {
		VAL_YEAR = TAB_YEAR;
		VAL_MON = TAB_MON;
		VAL_DAY = _num($(this).html());
		CAL_ABS._dn();
		valUpd();
		dayPrint();
		o.func();
	}
	function monPrint() {//отображение месяцев, когда пролистывание по году
		var html = '',
			cur = CUR_YEAR == TAB_YEAR,//выделение текущего месяца, если показан текущий год
			st =  VAL_YEAR == TAB_YEAR,//выделение выбранного месяца, если показан год выбанного месяца
			monn = {
				1:'янв',
				2:'фев',
				3:'мар',
				4:'апр',
				5:'май',
				6:'июн',
				7:'июл',
				8:'авг',
				9:'сен',
				10:'окт',
				11:'ноя',
				12:'дек'
			},
			tr = 3;

		for(n = 1; n <= 12; n++) {
			if(++tr > 3) {
				html += '<tr>';
				tr = 0;
			}
			var b = cur && n == CUR_MON ? ' b' : '',
				set = st && n == VAL_MON ? ' set' : '';
			html += '<td class="sel' + b + set + '" val="' + n + '">' + monn[n];
		}
		TAB_DAY
			.html('<tbody class="cal-tb" val="' + win + '">' + html + '</tbody>')
			.find('.sel').click(function() {
				TAB_MON = _num($(this).attr('val'));
				TAB_DAY._dn(1, 'mon');
				TD_WEEK._dn(1);
				tdMonUpd();
				dayPrint();
			});
	}
	function back() {//пролистывание календаря назад
		if(TD_WEEK.hasClass('dn')) {
			TAB_YEAR--;
			TD_MON.html(TAB_YEAR);
			monPrint();
			return;
		}
		if(!--TAB_MON) {
			TAB_MON = 12;
			TAB_YEAR--;
		}
		tdMonUpd();
		dayPrint();
	}
	function next() {//пролистывание календаря вперёд
		if(TD_WEEK.hasClass('dn')) {
			TAB_YEAR++;
			TD_MON.html(TAB_YEAR);
			monPrint();
			return;
		}
		if(++TAB_MON > 12) {
			TAB_MON = 1;
			TAB_YEAR++;
		}
		tdMonUpd();
		dayPrint();
	}


};
$.fn._search = function(o, v) {//поисковая строка
	/*
		Оборачивается input:text
		attr_id не обязателен
	*/
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id'),
		VALUE = $.trim(t.val());

	if(!attr_id) {
		attr_id = 'search' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_search',
		S = window[win];

	switch(typeof o) {
		case 'number':
		case 'string':
			if(!S)
				break;
			if(o == 'val') {
				if(v !== undefined) {
					S.inp(v);
					return S;
				}
				return S.inp();
			}
			if(o == 'process')
				S.process();
			if(o == 'is_process')
				return S.isProcess();
			if(o == 'cancel')
				S.cancel();
			if(o == 'clear')
				S.clear();
			return S;
	}

//	if(S && S.inp)
//		return S;

	o = $.extend({
		width:150,      //ширина. Если 0 = 100%
		placeholder:'', //текст-подсказка
		focus:0,        //сразу устанавливать фокус
		enter:0,        //применять введённый текст только после нажатия ентер
		func:function() {}
	}, o);

	//вывод поиска, если не был вставлен через PHP. Иначе только применение функций
	if(!t.closest('._search').length) {
		var width = ' style="width:' + (o.width ? o.width + 'px' : '100%') + '"',
			placeholder = o.placeholder ? ' placeholder="' + o.placeholder + '"' : '',
			html = '<div class="_search" id="' + attr_id + '_search"' + width + '>' +
				'<table class="w100p">' +
					'<tr><td class="w15 pl5">' +
							'<div class="icon icon-search curD"></div>' +
						'<td><input type="text" id="' + attr_id + '"' + placeholder + ' value="' + VALUE + '" />' +
						'<td class="w25 center">' +
							'<div class="icon icon-del pl' + _dn(VALUE) + '"></div>' +
				'</table>' +
			'</div>';

		t.after(html).remove();
	}

	var SEARCH = $('#' + win),
		INP = $('#' + attr_id),
		DEL = SEARCH.find('.icon-del');

	t = INP;

	if(o.focus)
		t.focus();

	INP.keydown(function(e) {
		setTimeout(function() {
			VALUE = $.trim(INP.val());
			DEL._dn(VALUE);
			if(o.enter && e.which != 13)
				return;
			o.func(VALUE, t);
		}, 0);
	});
	DEL.click(function() {
		if(DEL.hasClass('spin'))
			return;
		t.clear();
		o.func('', t);
		t.focus();
	});

	t.inp = function(v) {
		if(!v)
			return VALUE;
		VALUE = $.trim(v);
		INP.val(VALUE);
		DEL.removeClass('dn spin');
	};
	t.icon_del = DEL;
	t.clear = function() {//очищение содержимого
		INP.val('');
		DEL.addClass('dn');
	};
	t.process = function() {//показ процесса ожидания
		DEL.addClass('spin');
	};
	t.isProcess = function() {//определение, в процессе ли поиск
		return DEL.hasClass('spin');
	};
	t.cancel = function() {//отмена процесса ожидания
		DEL.removeClass('spin');
	};

	window[win] = t;

	return t;
};
$.fn._menu = function(o) {//меню
	var tMain = $(this),
		attr_id = tMain.attr('id'),
		val = _num(tMain.val()),
		win = attr_id + '_menu',
		n,
		S;

	if(!attr_id)
		return;

	S = window[win];

	switch(typeof o){
		case 'number':
		case 'string':
		case 'boolean':
			var v = _num(o);
			S.value(v);
			return tMain;
	}

	tMain.val(val);
	
	o = $.extend({
		type:1,
		spisok:[],
		func:function() {}
	}, o);

	_init();
	_pageCange(val);

	var mainDiv = tMain.next(),
		link = mainDiv.find('.link');

	link.click(_click);

	function _init() {
		var html = '<div class="_menu' + o.type + '" id="' + attr_id + '_menu">';

		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n],
				sel = val == sp.id ? ' sel' : '';
			html +=
				'<a class="link' + sel + '" val="' + sp.id + '">' +
					sp.title +
				'</a>';
		}

		html += '</div>';

		tMain.next().remove('._menu' + o.type);
		tMain.after(html);
	}
	function _click() {
		var t = $(this),
			v = _num(t.attr('val'));
		link.removeClass('sel');
		t.addClass('sel');
		tMain.val(v);
		_pageCange(v);
		o.func(v, S);
	}
	function _pageCange(v) {
		_forN(o.spisok, function(sp) {
			$('.' + attr_id + '-' + sp.id)._dn(v == sp.id);
		});
	}


	tMain.value = function(v) {
		link.removeClass('sel');
		for(n = 0; n < link.length; n++) {
			var sp = link.eq(n);
			if(_num(sp.attr('val')) == v) {
				sp.addClass('sel');
				break;
			}
		}
		tMain.val(v);
		_pageCange(v);
		o.func(v, attr_id);
	};

	window[win] = tMain;
	return tMain;
};
$.fn._dropdown = function(o) {//выпадающий список в виде ссылки
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id');
	if(!attr_id) {
		attr_id = 'dropdown' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_dropdown',
		S = window[win],
		VALUE = _num(t.val());

	o = $.extend({
		head:'',        //если указано, то ставится в название ссылки, а список из spisok
		title0:'',
		title0_grey:1,  //показывать серым, если значение не выбрано
		title0_hide:0,  //не показывать нулевое значение в меню выбора
		nosel:0,        //не изменять имя нулевого значения после выбора
		disabled:0,
		spisok:[],
		func:function() {}
	}, o);

	if(o.title0)
		o.head = o.title0;

	t.next().remove('._dropdown');

	var dis = o.disabled ? ' disabled' : '',
		html =  '<div class="_dropdown' + dis + '" id="' + win + '">' +
					'<a class="dd-head">' + o.head + '</a>' +
					'<div class="dd-list"></div>' +
				'</div>';
	t.after(html);

	var DDN = t.next(),
		HEAD = DDN.find('.dd-head'),
		LIST = DDN.find('.dd-list'),
		timer = 0,
		MASS = [],
		MASS_ASS = {};

	massCreate();
	spisokPrint();
	valueSet(VALUE);

	var DDU = DDN.find('.ddu');

	$(document)
		.off('click._dropdown')
		 .on('click._dropdown', function(e) {//закрытие всех списков при нажатии на любое место на экране
			var cur = $(e.target).parents('._dropdown'),
				attr = '';

			//закрытие селектов, когда нажатие было в стороне
			if(cur.hasClass('_dropdown'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._dropdown' + attr + ' .dd-list').hide();
		});

	HEAD.on('click mouseenter', function() {
		timerClear();
		_forEq(DDU, function(sp) {
			if(VALUE == sp.attr('val')) {
				sp.addClass('on');
				return false;
			}
		});
		LIST.show();
	});
	DDU.click(function() {
		timerClear();
		LIST.hide();
		var tt = $(this),
			v = _num(tt.attr('val'));
		valueSet(v);
		o.func(v);
	});
	DDU.mouseenter(function() {
		DDU.removeClass('on');
	});
	LIST.on({
		mouseleave:function () {
			timer = setTimeout(function() {
				LIST.fadeOut(200);
			}, 500);
		},
		mouseenter:timerClear
	});

	function massCreate() {//создание массива для корректного вывода списка
		var unit;

		if(o.title0)
			MASS_ASS[0] = o.title0;

		//исходный список является ассоциативным объектом
		if(!o.spisok.length) {
			_forIn(o.spisok, function(sp, id) {
				id = _num(id);
				if(!id)
					return;
				MASS_ASS[id] = sp;
				unit = {
					id:id,
					title:sp
				};
				MASS.push(unit);
			});
			return;
		}

		//исходный список является последовательным массивом
		_forN(o.spisok, function(sp, n) {
			var id,
				title;

			//проверка на одномерный последовательный массив
			if(typeof sp == 'number' || typeof sp == 'string') {
				id = n + 1;
				title = sp;
			} else {
				id = sp.uid;
				if(id === undefined)
					id = sp.id;
				if(id === undefined)
					return;
				id = _num(id);
				if(!id)
					return;
				title = sp.title;
			}

			MASS_ASS[id] = title || ' ';
			title = title || '&nbsp;';
			unit = {
				id:id,
				title:title
			};
			MASS.push(unit);
		});
	}
	function spisokPrint() {//вывод списка
		html = '<div class="dd-sel">' + o.head + '</div>';

		if(o.title0 && !o.title0_hide)
			html += '<div class="ddu title0" val="0">' + o.title0 + '</div>';

		_forN(MASS, function(sp) {
			var on = VALUE == sp.id ? ' on' : '';
			html += '<div class="ddu' + on + '" val="' + sp.id + '">' +
						sp.title +
					'</div>';
		});

		LIST.html(html);
	}
	function valueSet(v) {
		HEAD._dn(!(o.title0_grey && (!v || o.nosel)), 'grey');
		if(o.nosel)
			return;
		HEAD.html(MASS_ASS[v]);
		DDN.find('.dd-sel').html(MASS_ASS[v]);
		VALUE = v;
		t.val(v);
	}
	function timerClear() {
		if(!timer)
			return;
		clearTimeout(timer);
		timer = 0;
	}

	window[win] = t;
	return t;
};
$.fn._yearleaf = function(o) {//перелистывание годов
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win],
		VAL = _num(t.val()),
		YEAR_CUR = (new Date()).getFullYear(),
		YL = t.next();

/*
	if(typeof o == 'string') {
		if(o == 'cur')
			S.cur();
		return t;
	}
*/

	if(!YL.hasClass('_yearleaf'))
		return t;
	if(!YL.hasClass('php'))
		return S;

	YL.removeClass('php');

	o = $.extend({
		func:function() {}
	}, o);

/*
	if(!VAL) {
		VAL = YEAR_CUR;
		t.val(VAL);
	}
*/

	var YW = Math.round(YL.find('.ylc').width() / 2),//ширина центральной части, где год
		SPN = YL.find('span'),  //текст с годом
		MAL = YW + Math.floor(SPN.width() / 2), //расстояние, на которое разрешено продвинуться
		IS_MOVE = 0,            //происходит ли процесс изменения года
		timer;

	YL.find('.but').mousedown(function() {//перемещение года
		if(IS_MOVE)
			return;

		IS_MOVE = 1;

		var side = $(this).html() == '«' ? 1 : -1,
			mv = 0,
			speed = 1,  //ускорение
			half = 0;   //пройдена ли половина пути

		timer = setInterval(function() {
			mv += (speed += 3);

			if(half && (mv * -1 <= 0)) {
				clearInterval(timer);
				mv = 0;
				IS_MOVE = 0;
			}

			SPN.css({left:mv * side});

			//первая половина пути
			if(!half && mv >= MAL) {
				half = 1;
				mv *= -1;
				VAL -= side;
				SPN.html(VAL);
				t.val(VAL);
				o.func(VAL);
			}
		}, 25);
	});



	window[win] = t;
	return t;














	t.cur = function() {
		t.val(curYear);
		years.span.html(curYear);
	};

};



