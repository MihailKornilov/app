/* Элементы-теги */



$.fn._select = function(o, o1) {//выпадающий список от 24.08.2020
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
					'<td class="w15' + _dn(!blocked && o.write) + '"><div class="icon icon-del clear pl dn tool" data-tool="Очистить"></div>' +
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
		if(dis)
			return;

		SEL.addClass('rs');

		setTimeout(function() {
			if(!o.multi) {
				VALUE = 0;
				t.val(0);
			}
			var v = INP.val();
			ICON_DEL._dn(v && !o.multi);
			o.funcWrite(v, t);
			o.func(0);
		}, 0);
	});

	SEL.click(function(e) {
		if(dis)
			return;

		var rs = SEL.hasClass('rs'),
			TRG = $(e.target),
			su = TRG;

		if(!su.hasClass('select-unit'))
			su = TRG.closest('.select-unit');

		if(su.hasClass('select-unit')) {
			if(su.hasClass('info'))
				return;
			if(su.hasClass('busy'))
				return;

			valueSet(su.attr('val'));
			if(o.multi) {
				su._dn();
				if(o.write)
					INP.focus();
				return;
			}
		}

		if(rs && o.write && TRG.hasClass('select-inp'))
			return;
		if(TRG.hasClass('icon-add'))
			return;
		if(TRG.hasClass('icon-del'))
			return;
		if(TRG.hasClass('empty'))
			return;

		SEL._dn(rs, 'rs');
		RES._dn(RES.height() < 250, 'h250');

		//открытие списка
		if(!rs) {
			spisokPrint();
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
		}
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
		DL.sortable({
			items:'.mu',
			update:multiValueSet
		});

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

		//исходный список является ассоциативным объектом {1:'title1',2:'title2'}
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
				MASS_SEL_SAVE.push(_objCopy(unit));
			});
			return;
		}

		//исходный список является последовательным массивом [{id:1,title:'name1'},{id:2,title:'name2'}]
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
				busy:_num(sp.busy),//значение нельзя выбрать.
				bg:sp.bg
			};
			MASS_SEL.push(unit);
			MASS_SEL_SAVE.push(_objCopy(unit));
			BG_ASS[id] = sp.bg;
		});
	}
	function spisokPrint() {//вставка списка в select
		RES.removeClass('h250');
		if(!MASS_SEL.length && !o.title0)
			return RES.html('<div class="empty">' + o.msg_empty + '</div>');

		var html = '',
			is_sel = o.multi ? _idsAss(t.val()) : {}; //выбранные значения (нельзя выбрать повторно при multi)

		if(o.title0 && !o.write && !o.multi)
			html += '<div class="select-unit title0" val="0">' + o.title0 + '</div>';

		_forN(MASS_SEL, function(sp) {
			if(is_sel[sp.id])
				return;
			var info = sp.info ? ' info' : '',
				busy = sp.busy ? ' busy' : '',
				val = info || busy ? '' : ' val="' + sp.id + '"',
				bg = sp.bg ? ' style="background-color:' + sp.bg + '"' : '';
			html += '<div class="select-unit' + info + busy + '"' + bg + val + '>' + sp.content + '</div>';
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
			var un = _objCopy(sp),
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
		o.func(v);

		INP.val(MASS_ASS[v] ? String(MASS_ASS[v]).replace(/&quot;/g,'"') : '');

		if(v && !MASS_ASS[v])
			INP.val('Несуществующее значение ' + v)
			   .addClass('clr5');
		else
			INP.removeClass('clr5');

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
		o.func(vv.join(','));
		MASS_SEL = MASS_SEL_SAVE;
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
			'<dd class="mu" val="' + v + '">' +
				MASS_ASS[v] +
				'<div class="icon icon-del pl"></div>' +
			'</dd>'
		);
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
			case 'focus': s.focus(); break;
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
	t.focus = function() {//установка фокуса на input
		if(o.write)
			INP.focus();
	};

	window[win] = t;
	return t;
};

$(document).on('click', '._check', function() {//установка/снятие галочки, если была выведена через PHP
	var t = $(this);
	if(!t.hasClass('php'))//если галочка выведена через JS, а не через PHP, то действия нет
		return;
	if(t.hasClass('disabled'))
		return;
	if(t.hasClass('ignore'))
		return;

	var p = t.prev(),
		v = _num(p.val()) ? 0 : 1;

	p.val(v);
	t._dn(!v, 'on');
});
$.fn._check = function(o, oo) {
	var t = $(this);

	if(!t.length)
		return;

	if(o === undefined)
		o = {};

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win];

	if(S && t.next('._check:not(.php)').length) {
		switch(typeof o) {
			case 'number': S.setV(o ? 1 : 0); break;
			case 'string':
				if(o == 'disable')
					S.dis();
				if(o == 'ignore')
					S.ignore(oo);
				if(o == 'enable')
					S.enab();
				if(o == 'func')
					S.funcGo();
				break;
		}
		return S;
	}

	checkPrint();

	var CHECK = $('#' + attr_id + '_check');

	CHECK.click(function() {
		if(CHECK.hasClass('disabled'))
			return;
		if(CHECK.hasClass('ignore'))
			return;

		var v = CHECK.hasClass('on') ? 0 : 1;
		setVal(v);
		o.func(v, t);
	});


	if(o.tooltip)
		CHECK._tool(o.tooltip);

	function checkPrint() {//вывод галочки
		if(t.next().hasClass('_check'))  //если галочка была выведена через PHP - обновление и применение функций
			return t.next().removeClass('php');

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
				'<div id="' + attr_id + '_check" class="_check' + on + title + light + block + dis + '">' +
					(o.title ? o.title : '&nbsp;') +
				'</div>';

		t.val(val).after(html);
	}
	function setVal(v) {
		CHECK[(v ? 'add' : 'remove') + 'Class']('on');
		t.val(v);
	}

	t.setV = setVal;
	t.funcGo = function() {//применение фукнции
		o.func(_num(t.val()), t);
	};
	t.dis = function() {//перевод галочки в неактивное состояние
		CHECK.addClass('disabled');
	};
	t.ignore = function(i) {//перевод галочки в неактивное состояние
		CHECK[(i === false ? 'remove' : 'add') + 'Class']('ignore');
	};
	t.enab = function() {//перевод галочки в активное состояние
		CHECK.removeClass('disabled');
	};
	window[win] = t;
	return t;
};

$(document).on('click', '._radio div', function() {//выбор значения radio, если был выведен через PHP
	var t = $(this),
		p = t.parent();
	if(!p.hasClass('php'))//если элемент был выведен через JS, а не через PHP, то действия нет
		return;
	if(p.hasClass('disabled'))
		return;
	if(p.hasClass('ignore'))
		return;

	var v = _num(t.attr('val'));

	p.prev().val(v);
	p.find('.on').removeClass('on');
	t.addClass('on');
});
$.fn._radio = function(o, oo) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win];

	//если элемент был выведен через PHP, переключение его на JS
	var PHP = t.next('._radio.php');
	if(PHP.length && S)
		S = false;

	if(S) {
		switch(typeof o) {
			case 'number': S.valSet(o);	break;
			case 'string':
				if(o == 'spisok')
					S.spisok(oo);
				if(o == 'ignore')
					S.ignore(oo);
				break;
			case 'function': S.func(o);	break;
		}
		return S;
	}





	// ---=== АКТИВАЦИЯ RADIO ===---


	var PHP_O = o;  //сохранение для повторного вывода
	if(PHP.length) {
		if(typeof o != 'object')
			o = {};

		o.php = 1;  //флаг, чтобы не перерисовывать заново содержание, а только применить функции
		o.title0   = PHP.find('.title0').html();
		o.block    = PHP.hasClass('block');
		o.dis      = PHP.hasClass('disabled');
		o.ignore   = PHP.hasClass('ignore');
		o.light    = PHP.hasClass('light');
		o.interval = PHP.find('div:first').length ? PHP.find('div:first').css('margin-bottom').split('px')[0] * 1 : 0;
		if(!o.interval)
			delete o.interval;

		PHP.removeClass('php');
	}


	o = $.extend({
		title0:'',  //текст нулевого значения
		spisok:[],  //список значений в виде id => title
		dis:0,      //серое состояние, выбор значений заблокирован
		ignore:0,   //бледное состояние, выбор значений заблокирован
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
						_dn(o.ignore, 'ignore') +
						_dn(o.light, 'light') + '"' +
				' id="' + attr_id + '_radio">'   +
			'</div>';
		t.after(html);
		RD = t.next();
		_spisok();
	}

	_active();

	function _spisok() {//печать списка
		var spisok = _selCopy(o.spisok),
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
			if(RD.hasClass('ignore'))
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
	t.ignore = function(i) {//перевод в неактивное состояние
		RD[(i === false ? 'remove' : 'add') + 'Class']('ignore');
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

	var V = _num(t.val(), 1),
		PHP = _print(),
		INP = PHP.find('input'),
		STEP_COUNT = o.step.length || 0,//количество значений, если шаг-массив
		STEP_N = 0;//номер шага, если шаг-массив

	if(STEP_COUNT) {
		o.min = 0;
		o.max = STEP_COUNT - 1;
		_forN(o.step, function(sp, n) {
			if(sp == V) {
				STEP_N = n;
				return false;
			}
		});
	} else
		if(o.min < 0)
			o.minus = 1;

	INP.val(valTitle());

	if(o.tooltip)
		PHP._tool(o.tooltip);

	PHP.find('.but').click(function() {
		if(PHP.hasClass('disabled'))
			return;

		var znak = $(this).hasClass('but-b') ? -1 : 1;

		if(STEP_COUNT) {
			STEP_N += znak;
			if(znak > 0 && STEP_N > o.max)
				STEP_N = o.again ? 0 : o.max;
			if(znak < 0 && STEP_N < 0)
				STEP_N = o.again ? o.max : 0;
			V = o.step[STEP_N];
			INP.val(valTitle());
		} else {
			V += o.step * znak;
			V = valCorrect();
			INP.val(o.time ? _nol(V) : V);
		}

		t.val(V);
		o.func(V, attr_id);
	});

	function _print() {//вывод счётчика, либо активация
		var div = t.next('._count.php'),
			width = 'width:' + (o.width ? o.width + 'px' : '100%'),
			dis = o.disabled ? ' disabled' : '';

		if(div.length) {
			 div.removeClass('php')
				._dn(!dis, 'disabled')
				.attr('id', attr_id + '_count')
				.width(o.width || '100%');
			 return div
		}

		t.after('<div class="_count' + dis + '" id="' + attr_id + '_count" style="' + width + '">' +
					'<input type="text" readonly />' +
					'<div class="but"></div>' +
					'<div class="but but-b"></div>' +
				'</div>');
		return t.next();
	}
	function valCorrect() {
		if(o.again)
			if(o.min !== false)
				if(o.max !== false) {
					if(V < o.min)
						return o.max;

					if(V > o.max)
						return o.min;
				}

		if(!STEP_COUNT && !o.minus && V < 0)
			return 0;

		if(o.min !== false && V < o.min)
			return o.min;

		if(o.max !== false && V > o.max)
			return o.max;

		return V;
	}
	function valTitle() {//установка текстового значения
		var title = o.time ? _nol(V) : V;
		if(o.title[STEP_N])
			title = o.title[STEP_N];
		return title;
	}

	window[win] = t;
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
		msg:'--- Содержание подсказки отсутствует---',//содержание подсказки
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
$.fn._hintOver = function(o) {//выплывающая подсказка от наведения
							  //временная функция, которая позволяет добавлять независимые подсказки нескольким элементам на одной странице
	var obj = $(this);

	if(!obj.length)
		return;

	obj.mouseenter(function() {
		o = $.extend({
			show:1,
			delayShow:500
		}, o);
		obj._hint(o);
	});
};

$(document).on('mouseenter', '.tool,.tool-l,.tool-r', function() {//показ сообщения на тёмном фоне
	var t = $(this),
		msg = t.attr('data-tool'),
		side = '';

	if(t.find('.tool-div').length)
		return;
	if(!msg)
		return t.removeClass('tool');

	//подсказка смещена влево
	if(t.hasClass('tool-l'))
		side = 'l';
	//подсказка смещена вправо
	if(t.hasClass('tool-r'))
		side = 'r';


	var html = '<div class="tool-div">' +
		'<div class="tool-msg">' + msg + '</div>' +
		'<div class="tool-ug ' + side + '"></div>' +
		'</div>';
	t.removeAttr('data-tool');
	t.append(html)._toolCss(side);
});
$.fn._tool = function(msg, side) {//подсказка на тёмном фоне
	var t = $(this);

	switch(side) {
		case 'l':
		case 'r': break;
		default: side = '';
	}

	t.find('.tool-div').remove();
	t.addClass('tool' + (side ? '-' + side : ''));
	var html = '<div class="tool-div">' +
				'<div class="tool-msg">' + msg + '</div>' +
				'<div class="tool-ug ' + side + '"></div>' +
			  '</div>';
	t.append(html)._toolCss(side);
	return t;
};
$.fn._toolCss = function(side) {//выставление подсказки по ширине и высоте
	var t = $(this),
		toolW = t.width(),//ширина элемента, на который навели
		div = t.find('.tool-div'),//ширина подсказки
		left = 0,
		top = Math.round(div.height() + 3) * -1;

	switch(side) {
		case 'r': left = Math.round(toolW/2 + 1) * -1; break;
		case 'l': left = Math.round(div.width() + toolW/2 - 36) * -1; break;
		default:  left = Math.round(div.width()/2 - toolW/2) * -1
	}

	div.css({
		left:left,
		top:top
	});
};

$.fn._calendar = function(o) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win],
		VALUE = t.val(),
		n;

	o = $.extend({
		lost:1,                //если не 0, то можно выбрать прошедшие дни
		time:0,                //показывать время
		tomorrow:0,            //ссылка "завтра" для быстрой установки завтрашней даты
		func:function () {}    //исполняемая функция при выборе дня
	}, o);

	var	MONTH_DEF = {
			1:'Январь',
			2:'Февраль',
			3:'Март',
			4:'Апрель',
			5:'Май',
			6:'Июнь',
			7:'Июль',
			8:'Август',
			9:'Сентябрь',
			10:'Октябрь',
			11:'Ноябрь',
			12:'Декабрь'
		},
		MONTH_DAT = {
			1:'января',
			2:'февраля',
			3:'марта',
			4:'апреля',
			5:'мая',
			6:'июня',
			7:'июля',
			8:'августа',
			9:'сентября',
			10:'октября',
			11:'ноября',
			12:'декабря'
		},

		D = new Date(),
		CUR_YEAR = D.getFullYear(), //текущий год
		CUR_MON =  D.getMonth() + 1,//текущий месяц
		CUR_DAY =  D.getDate(),     //текущий день
		CUR_H =    D.getHours(),    //текущий час
		CUR_M =    D.getMinutes(),  //текущая минута

		TAB_YEAR = CUR_YEAR,        //год, отображаемый в календаре
		TAB_MON =  CUR_MON,         //месяц, отображаемый в календаре

		VAL_YEAR = CUR_YEAR,  //выбранный год
		VAL_MON =  CUR_MON,   //выбранный месяц
		VAL_DAY =  CUR_DAY,   //выбранный день
		VAL_H =    CUR_H,     //выбранный час
		VAL_M =    CUR_M;     //выбранная минута

	valTest();

	//удаление такого же календаря при повторном вызове
	t.next().remove('._calendar');
	t.after(
		'<div class="_calendar" id="' + attr_id + '_calendar">' +
			'<div class="icon icon-calendar"></div>' +
			'<input type="text" class="cal-inp" readonly />' +

		(o.time ?
			'<div class="dib ml8">' +
				'<input type="hidden" id="' + attr_id + '_hour" value="' + VAL_H + '" />' +
			'</div>' +
			'<div class="dib b ml3 mr3">:</div>' +
			'<input type="hidden" id="' + attr_id + '_min" value="' + VAL_M + '" />'
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

	//отображение часов и минут
	if(o.time) {
		$('#' + attr_id + '_hour')._count({
			min:0,
			max:23,
			again:1,
			time:1,
			func:function(v) {
				VAL_H = v;
				valUpd();
			}
		});
		$('#' + attr_id + '_min')._count({
			step:10,
			min:0,
			max:50,
			again:1,
			time:1,
			func:function(v) {
				VAL_M = v;
				valUpd();
			}
		});
	}

	var	CAL = t.next(),
		INP = CAL.find('.cal-inp'),     //текстовое отображение выбранного дня
		CAL_ABS = CAL.find('.cal-abs'), //содержание календаря
		TD_MON = CAL.find('.cal-mon'),  //строка td с месяцем и годом
		TD_WEEK = CAL.find('.cal-week'),//таблица с названиями недель
		TAB_DAY = CAL.find('.cal-day'); //таблица с днями


	valUpd();
	tdMonUpd();
	dayPrint();

	INP.click(calShow);
	CAL.find('.icon-calendar').click(calShow);
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


	function calShow() {
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
	}
	function valTest() {//проверка текущего значения, установка, если некорректное
		var cur = CUR_YEAR + '-' + _nol(CUR_MON) + '-' + _nol(CUR_DAY);
		if(o.time)
			cur += ' ' + _nol(CUR_H) + ':' + _nol(CUR_M) + ':00';

		if(!VALUE.length)
			VALUE = cur;
		if(!REGEXP_DATE.test(VALUE))
			VALUE = cur;

		var day = VALUE.split('-');
		if(o.time) {
			day = VALUE.split(' ');
			var tm = day[1].split(':');
			day = day[0].split('-');
			VAL_H = _num(tm[0]);
			VAL_M = _num(tm[1]);
		}

		if(!_num(day[0]) || !_num(day[1]) || !_num(day[2])) {
			VALUE = cur;
			valTest();
			return;
		}

		VAL_YEAR = _num(day[0]);
		VAL_MON =  _num(day[1]);
		VAL_DAY =  _num(day[2]);
		TAB_YEAR = VAL_YEAR;
		TAB_MON = VAL_MON;
	}
	function valUpd() {//обновление значения
		VALUE = TAB_YEAR + '-' + _nol(TAB_MON) + '-' + _nol(VAL_DAY);
		if(o.time)
			VALUE += ' ' + _nol(VAL_H) + ':' + _nol(VAL_M) + ':00';
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
			.html('<tbody class="cal-tb" val="' + attr_id + '_calendar">' + html + '</tbody>')
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
			.html('<tbody class="cal-tb" val="' + attr_id + '_calendar">' + html + '</tbody>')
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

	return t;
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

$.fn._menu = function(o) {//меню переключения
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
		type:2,
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

	var attr_id = _attrId(t),
		win = attr_id + 'win',
		S = window[win],
		VALUE = _num(t.val());

	switch(typeof o) {
		default:
		case 'undefined': break;
		case 'object': break;
		case 'number':
		case 'string':
			S.value(o);
			return S;
	}

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
		html =  '<div class="_dropdown' + dis + '" id="' + attr_id + '_dropdown">' +
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

			$('._dropdown' + attr).removeClass('rs');
		});

	HEAD.on('click mouseenter', function() {
		timerClear();
		_forEq(DDU, function(sp) {
			if(VALUE == sp.attr('val')) {
				sp.addClass('on');
				return false;
			}
		});
		DDN.addClass('rs');
		LIST.css('opacity', 1);
		_fbhs();
	});
	DDU.click(function() {
		timerClear();
		DDN.removeClass('rs');
		var tt = $(this),
			v = _num(tt.attr('val'), 1);
		valueSet(v);
		o.func(v, t);
	});
	DDU.mouseenter(function() {
		DDU.removeClass('on');
	});
	LIST.on({
		mouseleave:function () {
			timer = setTimeout(function() {

				LIST.animate({opacity:0}, 200, function() {
					DDN.removeClass('rs');
					_fbhs();
				});

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
				id = _num(id, 1);
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
				id = _num(id, 1);
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
		HEAD._dn(!(o.title0_grey && (!v || o.nosel)), 'clr1');
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
		LIST.stop().css('opacity', 1);
	}

	t.ass = MASS_ASS;
	t.value = valueSet;

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

$.fn._selem = function(o) {//выбор элемента - звезда
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = _attrId(t),
		V = t.val();

	o = $.extend({
		width:175,
		placeholder:'не выбрано',
		title:'',
		dss:0,      //ID диалога, из которого выбирается значение
		nest:0,     //выбор во вложенных списках
		sev:0       //выбор нескольких значений
	}, o);

	var html =
		'<div class="_selem dib prel bg-fff over3" style="width:' + o.width + 'px">' +
			'<div class="icon icon-star pabs"></div>' +
			'<div class="icon icon-del pl pabs' + _dn(V) + '"></div>' +
			'<input type="text" readonly class="w100p curP clr11" placeholder="' + o.placeholder + '" value="' + o.title + '" />' +
		'</div>';
	t.after(html);

	var SELEM = t.next(),
		INP = SELEM.find('input');

	SELEM.click(function() {
		if(!o.dss) {
			SELEM._hint({
				msg:'Не указан диалог для выбора значения',
				color:'clr5',
				pad:10,
				show:1
			});
			return;
		}
		_dialogLoad({
			dialog_id:11,
			dss:o.dss,
			dop:{
				mysave:1,
				sel:V,
				nest:o.nest,
				sev:o.sev
			},
			busy_obj:INP,
			busy_cls:'hold',
			func_save:function(res) {
				t.val(res.v);
				V = res.v;
				INP.val(res.title);
				SELEM.find('.icon-del')._dn(true);
			}
		});
	});

	SELEM.find('.icon-del').click(function(e) {
		e.stopPropagation();
		INP.val('');
		V = 0;
		$(this)._dn();
	});

};

$.fn._filter102 = function() {//Фильтр - Выбор нескольких групп значений
	var ATR_EL = $(this);

	if(!ATR_EL.length)
		return;

	var elem_id = _num(ATR_EL.attr('id').split('_')[1]);
	if(!elem_id)
		return;

	//проверка, активирован ли элемент
	var actv = 'ACTIVE' + elem_id;
	if(window[actv])
		return;

	var EL = ELMM[elem_id];
	if(!EL)
		return;

	var ATR_CMP_AFICS = _attr_cmp(elem_id, 1),
		HLD = ATR_EL.find('.holder'),//текст пустого значения
		TDUN = ATR_EL.find('.td-un'),//выбранные значения
		DEL = ATR_EL.find('.icon-del'),//иконка удаления
		ICON_EMPTY = '<div class="icon icon-empty"></div>',
		TITLE = window['EL' + elem_id + '_F102_TITLE'],
		COUNT = window['EL' + elem_id + '_F102_C'],
		BG = window['EL' + elem_id + '_F102_BG'],
		un = function(id, not_count) {//формирование значения для вставки
			var bg = BG[id] ? ' style="background-color:' + BG[id] + '"' : '',
				title = not_count ? TITLE[id] : _num(COUNT[id]);
			if(!title)
				return '';
			return '<div' + bg + ' class="un tool" data-tool="' + (not_count ? '' : TITLE[id]) + '">' + title +'</div>';
		},
		sevSet = function() {//обновление выбранных значений
			var sel = '',
				ids = [];
			_forEq(ATR_EL.find('._check'), function(sp) {
				var p = sp.prev(),
					v = _num(p.val()),
					id = p.parent().parent().attr('val');

				if(!v)
					return;

				sel += un(id);
				ids.push(id);
			});

			if(ids.length == 1)
				sel = un(ids[0], 1);

			HLD._dn(!sel);
			TDUN.html(sel || ICON_EMPTY);
			DEL._vh(sel);
			FILTER[EL.num_1][elem_id] = ids.join();
			_spisokUpdate(EL.num_1);
		},
		chkUpd = function(id) {//обновление галочек
			_forEq(ATR_EL.find('._check'), function(sp) {
				var p = sp.prev(),
					chk_id = p.parent().parent().attr('val');
				p._check(chk_id == id ? 1 : 0);
			});
		};

	_forEq(ATR_EL.find('._check'), function(sp) {
		sp.prev()._check({func:sevSet});
	});

	ATR_CMP_AFICS.click(function(e) {
		var tar = $(e.target);

		//очистка фильтра
		if(tar.hasClass('icon-del')) {
			HLD._dn(1);
			TDUN.html(ICON_EMPTY);
			DEL._vh();
			ATR_CMP_AFICS.removeClass('rs');
			chkUpd();
			FILTER[EL.num_1][elem_id] = 0;
			_spisokUpdate(EL.num_1);
			return;
		}

		if(tar.parents('.list').hasClass('list')) {
			//выбор одного значения
			if(tar[0].tagName == 'TD') {
				var id = tar.parent().attr('val');
				HLD._dn();
				TDUN.html(un(id, 1));
				DEL._vh(1);
				ATR_CMP_AFICS.removeClass('rs');
				chkUpd(id);
				FILTER[EL.num_1][elem_id] = id;
				_spisokUpdate(EL.num_1);
			}
			return;
		}

		ATR_CMP_AFICS._dn(ATR_CMP_AFICS.hasClass('rs'), 'rs');
	});

	$(document)
		.off('click._filter102')
		 .on('click._filter102', function(e) {
			var cur = $(e.target).parents('._filter102'),
				attr = '';

			//закрытие фильтров-102, когда нажатие было в стороне
			if(cur.hasClass('_filter102'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._filter102' + attr).removeClass('rs');
		});

	window[actv] = true;
	return ATR_EL;
};

