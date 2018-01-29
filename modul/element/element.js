/* Все элементы визуального отображения, используемые в приложении */
var VK_SCROLL = 0,
	ZINDEX = 1000,
	BC = 0,
	DIALOG = {},//массив диалоговых окон для управления другими элементами

	MONTH_DEF = {
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
	WEEK_NAME = {
		0:'вс',
		1:'пн',
		2:'вт',
		3:'ср',
		4:'чт',
		5:'пт',
		6:'сб',
		7:'вс'
	},

	_backfon = function(add) {//Задний фон при открытии поверхностных окон
		if(add === undefined)
			add = true;
		var body = $('body'),
			h = $(document).height();
		if(add) {
			ZINDEX += 10;
			if(!BC) {
				body.find('._backfon').remove().end()
					.append('<div class="_backfon"></div>');
			}
			var backfon = body.find('._backfon');
			backfon.css({
				'z-index':ZINDEX,
				height:h
			});
			if(typeof add == 'object')
				backfon.click(function() {
					del();
					add.remove();
				});
			BC++;
		} else
			del();

		function del() {
			BC--;
			ZINDEX -= 10;
			if($('._dialog').length)
				ZINDEX = $('._dialog:last').css('z-index') - 5;

			var backfon = body.find('._backfon');
			if(!BC)
				backfon.remove();
			else {
				backfon.css({'z-index':ZINDEX});
				ZINDEX += 10;
			}
		}
	},

	_dialog = function(o) {//диалоговое окно
		o = $.extend({
			top:100,
			width:0,    //ширина диалога. Если 0 = автоматически
			mb:0,       //margin-bottom: отступ снизу от диалога (для календаря или выпадающих списков)
			padding:10, //отступ для content

			dialog_id:0,//id диалога, загруженного из базы
			unit_id:0,  //id единицы списка, который вносится при помощи данного диалога (только для передачи при редактировании диалога)
			block_id:0, //id блока, в который вставляется элемент (только для передачи при редактировании диалога)

			edit_access:0,//показ иконки редактирования диалога

			color:'',   //цвет диалога - заголовка и кнопки
			attr_id:'', //идентификатор диалога: для определения открытого такого же, чтобы предварительно закрывать его
			head:'head: Название заголовка',
			load:0,     // Показ процесса ожидания загрузки в центре диалога
			content:'<div class="pad30 pale">content: содержимое центрального поля</div>',

			butSubmit:'Внести',
			butCancel:'Отмена',
			submit:function() {},
			cancel:dialogClose
		}, o);

		var frameNum = $('._dialog').length;

		//запрет редактирования диалогового окна, которое открыто поверх другого
		if(frameNum)
			o.edit_access = 0;

		//закрытие диалога с тем же идентификатором
		if(o.attr_id && $('#' + o.attr_id + '_dialog').length) {
			$('#' + o.attr_id + '_dialog').remove();
			_backfon(false);
			if(!frameNum)
				DIALOG_MAXHEIGHT = 0;
//			_fbhs();
		}

		if(o.load)
			o.content =
				'<div class="load _busy">' +
					'<tt class="red">В процессе загрузки произошла ошибка.</tt>' +
				'</div>';

		var html =
			'<div class="_dialog"' + (o.attr_id ? ' id="' + o.attr_id + '_dialog"' : '') + '>' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh pl"></a></div>' +
		            '<div class="edit fr curP' + _dn(o.edit_access) + '"><a class="icon icon-edit wh pl"></a></div>' +
					'<div class="fs14 white">' + o.head + '</div>' +
				'</div>' +
//				'<div>' +
//					'<iframe class="dFrame" name="dFrame' + frameNum + '"></iframe>' +
					'<div class="content bg-fff"' + (o.padding ? ' style="padding:' + o.padding + 'px"' : '') + '>' +
						o.content +
					'</div>' +
//				'</div>' +
				'<div class="btm">' +
					'<button class="vk submit mr10 ' + o.color + (o.butSubmit ? '' : ' dn') + '">' + o.butSubmit + '</button>' +
					'<button class="vk cancel' + (o.butCancel ? '' : ' dn') + '">' + o.butCancel + '</button>' +
				'</div>' +
			'</div>';

		// Если открывается первый диалог на странице, запоминается стартовая максимальная высота диалогов
		if(!frameNum)
			DIALOG_MAXHEIGHT = 0;

		var dialog = $('body').append(html).find('._dialog:last'),
			iconEdit = dialog.find('.head .edit'),
			content = dialog.find('.content'),
			width = o.width || Math.round(content.width()),
			bottom = dialog.find('.btm'),
			butSubmit = bottom.find('.submit'),
			butCancel = bottom.find('.cancel'),
			submitFunc = function() {
				if(butSubmit.hasClass('_busy'))
					return;
				o.submit();
				if(o.dialog_id)
					delete DIALOG[o.dialog_id];
			},
			w2 = Math.round(width / 2); // ширина/2. Для определения положения по центру
		dialog.find('.close').click(dialogClose);
		butSubmit.click(submitFunc);
		butCancel.click(function() {
//			e.stopPropagation();
//			dialogClose();
			if(butCancel.hasClass('_busy'))
				return;
			o.cancel();
		});

		//для всех input при нажатии enter применяется submit
		content.find('input').keyEnter(submitFunc);

		_backfon();

		dialog.css({
			width:width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		iconEdit.click(function() {//нажатие на иконку редактирования
			if(!o.dialog_id)
				return;

			var send = {
				op:'dialog_edit_load',
				dialog_id:o.dialog_id,
				busy_obj:iconEdit.find('.icon'),
				busy_cls:'spin'
			};
			_post(send, function(res) {
				dialogClose();
				res.unit_id = o.unit_id;
				res.block_id = o.block_id;
				_dialogEdit(res);
			});
		});

/*
		window['dFrame' + frameNum].onresize = function() {
			var fr = $('.dFrame'),
				max = 0;
			for(var n = 0; n < fr.length; n++) {
				var h = fr.eq(n).height();
				if(h > max)
					max = h;
			}
			var dh = max + VK_SCROLL + 180 + o.mb;
			if(DIALOG_MAXHEIGHT != dh) {
				DIALOG_MAXHEIGHT = dh;
//				_fbhs();
			}
		};
*/
		function dialogClose() {
			dialog.remove();
			_backfon(false);
			if(o.dialog_id)
				delete DIALOG[o.dialog_id];
			if(!frameNum)
				DIALOG_MAXHEIGHT = 0;
//			_fbhs();
		}
		function dialogErr(msg) {
			butSubmit._hint({
				msg:msg,
				color:'red',
				pad:10,
				show:1
			});
		}
		function loadError(msg) {//ошибка загрузки данных для диалога
			dialog.find('.load').removeClass('_busy');
			if(msg)
				dialog.find('.load tt').append('<br /><br /><b>' + msg + '</b>');
		}

		var DLG = {
			close:dialogClose,
			process:function() {
				butSubmit.addClass('_busy');
			},
			abort:function(msg) {
				butSubmit.removeClass('_busy');
				if(msg)
					dialogErr(msg);
			},
			bottom:(function() {
				return bottom;
			})(),
			content:(function() {
				return content;
			})(),
			err:dialogErr,
			loadError:loadError,
			butSubmit:function(name) {//изменение текста кнопки применения
				butSubmit[(name ? 'remove' : 'add') + 'Class']('dn');
				butSubmit.html(name);
			},
			butCancel:function(name) {//изменение текста кнопки отмены
				butCancel[(name ? 'remove' : 'add') + 'Class']('dn');
				butCancel.html(name);
			},
			submit:function(func) {
				o.submit = func;
			},
			load:function(send, func) {//загрузка контенка и вставка при получении в диалоговое окно. Если ошибка - вывод сообщения
				$.post(AJAX, send, function(res) {
					if(res.success) {
						content.html(res.html);
						if(typeof func == 'function')
							func(res);
					} else
						loadError(res.text);
				}, 'json');
			},
			post:function(send, success) {//отправка формы
				butSubmit.addClass('_busy');
				$.post(AJAX, send, function(res) {
					if(res.success) {
						dialogClose();
						_msg();
						if(success == 'reload')
							location.reload();
						if(typeof success == 'function')
							success(res);
					} else {
						butSubmit.removeClass('_busy');
						dialogErr(res.text);
						$(res.attr_cmp)
							._flash({color:'red'})
							.focus();
					}
				}, 'json');
			},
			head:function(v) {//установка текста заголовка
				dialog.find('.head .white').html(v);
			},
			width:function(v) {//установка ширины окна
				w2 = Math.round(v / 2);
				dialog.css({
					width:v + 'px',
					left:$(document).width() / 2 - w2 + 'px'
				});
				dialog.find('.head input').width(v - 80);
			}
		};

		if(o.dialog_id)
			DIALOG[o.dialog_id] = DLG;

		return DLG;
	},
	_dialogEdit = function(o) {//создание|редактирование диалогового окна
		var dialog = _dialog({
				dialog_id:o.dialog_id,
				color:'orange',
				width:o.width,
				top:20,
				padding:0,
				head:'Настройка диалогового окна',
				content:o.html,
				butSubmit:'Сохранить диалоговое окно',
				submit:submit,
				cancel:function() {
					var send = {
						op:'dialog_open_load',
						page_id:PAGE_ID,
						dialog_id:o.dialog_id,
						unit_id:o.unit_id,
						block_id:o.block_id,
						busy_obj:dialog.bottom.find('.cancel')
					};
					_post(send, function(res) {
						dialog.close();
						_dialogOpen(res);
					});
				}
			}),
			DIALOG_WIDTH = o.width;

		_blockUpd(o.blk);
		_elemUpd(o.cmp);
		_elemActivate(o.cmp, {}, 1);

		$('#dialog-menu')._menu({
			type:2,
			spisok:o.menu,
			func:_dialogHeightCorrect
		});
		$('#width_auto')._check({
			title:'автоматическая ширина'
		});

		_forN(['insert', 'edit', 'del'], function(act) {
			$('#' + act + '_action_id')._select({
				width:270,
				title0:'действия нет, закрыть окно',
				spisok:o.action,
				func:function(v) {
					$('.td-' + act + '-action-page')._dn(v == 2);
					$('#' + act + '_action_page_id')._select(0);
				}
			});
			$('#' + act + '_action_page_id')._select({
				width:270,
				title0:'не выбрана',
				spisok:PAGE_LIST
			});
		});

		$('#base_table')._select({
			width:230,
			write:1,
			spisok:o.tables
		});
		$('#element_width')._count({width:60,step:10});
		$('#element_width_min')._count({width:60,step:10});
		$('#element_dialog_func')._select({
			width:280,
			title0:'не указан',
			spisok:o.dialog_spisok
		});

		_dialogHeightCorrect();

		//установка линии для настройки ширины диалога
		$('#dialog-w-change')
			.css('left', (DIALOG_WIDTH + 8) + 'px')
			.draggable({
				axis:'x',
				grid:[10,0],
				drag:function(event, ui) {
					$('#width_auto')._check(0);
					var w = ui.position.left - 8;
					if(w < 480 || w > 980)
						return false;
					DIALOG_WIDTH = w;
					dialog.width(w);
					$('#dialog-width').html(w);
				}
			});

		function submit() {
			var send = {
				op:'dialog_save',

				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				unit_id:o.unit_id,
				block_id:o.block_id,

				width:DIALOG_WIDTH,
				width_auto:$('#width_auto').val(),
				cmp_no_req:$('#cmp_no_req').val(),

				insert_head:$('#insert_head').val(),
				insert_button_submit:$('#insert_button_submit').val(),
				insert_button_cancel:$('#insert_button_cancel').val(),
				insert_action_id:$('#insert_action_id').val(),
				insert_action_page_id:$('#insert_action_page_id').val(),

				edit_head:$('#edit_head').val(),
				edit_button_submit:$('#edit_button_submit').val(),
				edit_button_cancel:$('#edit_button_cancel').val(),
				edit_action_id:$('#edit_action_id').val(),
				edit_action_page_id:$('#edit_action_page_id').val(),

				del_head:$('#del_head').val(),
				del_button_submit:$('#del_button_submit').val(),
				del_button_cancel:$('#del_button_cancel').val(),
				del_action_id:$('#del_action_id').val(),
				del_action_page_id:$('#del_action_page_id').val(),

				spisok_on:$('#spisok_on').val(),
				spisok_name:$('#spisok_name').val(),

				base_table:$('#base_table')._select('inp'),
				app_any:$('#app_any').val(),
				sa:$('#sa').val(),

				element_name:$('#element_name').val(),
				element_width:$('#element_width').val(),
				element_width_min:$('#element_width_min').val(),
				element_search_access:$('#element_search_access').val(),
				element_is_insert:$('#element_is_insert').val(),
				element_style_access:$('#element_style_access').val(),
				element_url_access:$('#element_url_access').val(),
				element_hint_access:$('#element_hint_access').val(),
				element_dialog_func:$('#element_dialog_func').val(),
				element_afics:$('#element_afics').val(),

				menu_edit_last:$('#dialog-menu').val()
			};
			dialog.post(send, _dialogOpen);
		}
	},
	_dialogHeightCorrect = function() {//установка высоты линий для настройки ширины диалога и ширины полей с названиями
		var h = $('#dialog-w-change').parent().height();
		$('#dialog-w-change').height(h);
	},

	_dialogOpen = function(o) {//открытие диалогового окна
		var dialog = _dialog({
			dialog_id:o.dialog_id,
			block_id:o.block_id,  //для передачи значений, если будет требоваться редактирование диалога
			unit_id:o.unit_id,    //id также для передачи

			top:20,
			width:o.width,
			padding:0,
			edit_access:o.edit_access,

			head:o.head,
			content:o.html,
			butSubmit:o.button_submit,
			butCancel:o.button_cancel,
			submit:submit
		});

		//если удаление единицы списка, то кнопка красная
		if(o.act == 'del')
			dialog.bottom.find('.submit').addClass('red');
		else {
			window.DIALOG_OPEN = dialog;
			_blockUpd(o.blk);
			_elemUpd(o.cmp);
			_elemActivate(o.cmp, o.unit);
		}

		function submit() {
			var send = {
				op:'spisok_add',
				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				block_id:o.block_id,
				unit_id:o.unit_id,
				dialog_source:o.dialog_source,//id исходного диалогового окна
				cmp:{},
				cmpv:{}
			};

			if(o.unit_id) {
				send.op = 'spisok_save';
				if(o.act == 'del')
					send.op = 'spisok_del';
			}

			//получение значений компонентов
			if(o.act != 'del')
				_forIn(o.cmp, function(sp, id) {
					switch(sp.dialog_id) {
						case 19://наполнение для некоторых компонентов
							send.cmpv[id] = _dialogCmpV19(sp, 1);
							return;
						case 30://Настройка ТАБЛИЧНОГО содержания списка
							send.cmpv[id] = _dialogCmpV30(sp, 'get');
							break;
						case 37://SA: Select - выбор имени колонки
							send.cmp[id] = $(sp.attr_cmp)._select('inp');
							return;
						case 49://Настройка содержания Сборного текста
							send.cmpv[id] = _dialogCmpV49(sp, 'get');
							break;
					}
					send.cmp[id] = $(sp.attr_cmp).val();
				});

			dialog.post(send, function(res) {
				//если присутствует функция, выполняется она
				if(o.func)
					return o.func(res);

//return;

				switch(res.action_id) {
					case 1: location.reload(); break;
					case 2:
						var url = URL + '&p=' + res.action_page_id;
						if(res.unit)
							url += '&id=' + res.unit.id;
						location.href = url;
						break;
					case 3://обновление содержимого блоков
						var bln = '#block-level-' + res.block_obj_name;
						$(bln).after(res.level).remove();
						$(bln)
							.find('.block-grid-on')
							.removeClass('grey')
							.trigger('click');
						break;
					case 4://обновление исходного диалога
						var id = _num(o.dialog_source);
						if(!id)
							break;
						if(!DIALOG[id])
							break;
						DIALOG[id].close();
						if(!res.dialog_source)
							break;
						_dialogOpen(res.dialog_source);
						break;
				}
			});
		}
	},
	_dialogCmpV19 = function(o, get) {//наполнение для некоторых компонентов. dialog_id=19
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(get) {
			var send = [];
			_forEq(el.find('dd'), function(sp) {
				send.push({
					id:_num(sp.attr('val')),
					title:sp.find('.title').val(),
					content:sp.find('textarea').val(),
					def:_num(sp.find('.def').val())
				});
			});
			return send;
		}

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			DL = el.html(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,
				title:'имя значения ' + NUM,
				content:'',
				def:0,
				use:0
			}, v);

			DL.append(
				'<dd class="over1" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w90 grey r topi">Значение ' + NUM + ':' +
							'<td><input type="text" class="title w100p b" value="' + v.title + '" />' +
								'<textarea class="w100p min mtm1' + _dn(o.num_1) + '" placeholder="описание значения">' + v.content + '</textarea>' +
							'<td class="w15 topi">' +
								'<input type="hidden" class="def" id="el-def-' + NUM + '" value="' + v.def + '" />' +
							'<td class="w50 r top pt5">' +
					   (v.use ? '<div class="dib fs11 color-ccc mr3 curD' + _tooltip('Использование', -53) + v.use + '</div>'
								:
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('Удалить значение', -55) + '</div>'
					   ) +
					'</table>' +
				'</dd>'
			);

			DL.sortable({axis:'y',handle:'.icon-move-y'});
			var DD = DL.find('dd:last');
			DD.find('textarea').autosize();
			DD.find('.def')._check({
				tooltip:'По умолчанию',
				func:function(v, ch) {
					if(!v)
						return;
					//снятие галочек с остальных значений
					_forEq(DL.find('.def'), function(sp) {
						if(sp.attr('id') == ch.attr('id'))
							return;
						sp._check(0);
					});
				}
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
			if(!v.id)
				DD.find('.title').select();
			NUM++;
		}
	},
	_dialogCmpV30 = function(o, unit) {//Настройка ТАБЛИЧНОГО содержания списка. dialog_id=30
		if(unit == 'get') {//получение данных для сохранения
			var send = {};
			_forN(TABLE30, function(sp) {
				if(!sp.id)
					return;
				send[sp.id] = {
					width:sp.width,
					tr:$(sp.attr_tr).val(),
					font:sp.font,
					color:sp.color,
					pos:sp.pos,
					url:sp.url
				};
			});
			return send;
		}

		window.TABLE30 = [];

		if(!unit.block_id)
			return {};

		var el = $(o.attr_el),
			cmp = $(o.attr_cmp),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить колонку</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		$('#cmp_531')._check({//показ-скрытие настройки заголовков
			func:function(v) {
				unit.num_5 = v;
				DL.find('.div-inp-tr')['slide' + (v ? 'Down' : 'Up')]();
			}
		});
		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id элемента
				dialog_id:0,//id диалога, через который был вставлен этот элемент
				attr_el:'#inp_' + NUM,
				attr_bl:'#inp_' + NUM,
				attr_tr:'#tr_' + NUM,
				width:150,  //ширина колонки
				tr:'',      //имя колонки txt_1
				title:'',   //тип значения
				font:'',
				color:'',
				pos:'',      //txt_6
				url_access:1,//колонке разрешено быть ссылкой
				url:0        //колонка является ссылкой
			}, v);

			DL.append(
				'<dd class="over3">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w80 grey r topi">Колонка ' + NUM + ':' +
							'<td><div style="width:' + v.width + 'px">' +
									'<div class="div-inp-tr' + _dn(unit.num_5) + '">' +
										'<input type="text"' +
											  ' id="tr_' + NUM + '"' +
											  ' class="inp-tr bg-gr2 w100p center fs14 blue mb1"' +
											  ' placeholder="имя колонки"' +
											  ' value="' + v.tr + '"' +
										' />' +
									'</div>' +
									'<input type="text"' +
										  ' id="inp_' + NUM + '"' +
										  ' class="inp w100p curP ' + v.font + ' ' + v.color + ' ' + v.pos + '"' +
										  ' readonly' +
										  ' placeholder="значение не выбрано"' +
										  ' value="' + v.title + '"' +
										  ' val="' + v.id + '"' +
									' />' +
								'</div>' +
							'<td class="w50 r top pt5">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('Удалить колонку', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var INP = $(v.attr_el);
			valueResize(v);
			INP.click(function() {
				_elemChoose({
					type:'table',
					dialog_id:v.dialog_id,
					block_id:unit.block_id, //блок, в котором размещена таблица
					unit_id:v.id,           //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_open:function(res) {
						res.block_id = _num('-' + unit.id, 1);
					},
					func_save:function(ia) {
						if(!v.id) {
							INP.attr('val', ia.unit.id);
							cmpUpdate();
						}
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.num_1);
						valueResize(v);
					}
				});
			});
			INP.mouseenter(function() {
				if(INP.hasClass('_busy'))
					return;
				if(!INP.parent().hasClass('ui-resizable'))
					return;
				if(INP.parent().hasClass('ui-resizable-resizing'))
					return;
				INP._hint({
					msg:'<table class="bs5">' +
							'<tr><td class="pt3">' + _elemUnitFont(v) +
								'<td class="pt3">' + _elemUnitColor(v) +
								'<td class="pt3 pl10" id="elem-pos">' + _elemUnitPlaceMiddle(v) +
						'</table>' +
						'',
					side:'right',
					show:1,
					delayShow:700,
					delayHide:300
				});
			});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y',
				stop:cmpUpdate
			});
			DL.find('.icon-del:last').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
				cmpUpdate();
				v.id = 0;
			});
			NUM++;
			TABLE30.push(v);
		}
		function valueResize(v) {//включение изменения ширины, если есть значение
			if(!v.id)
				return;
			if($(v.attr_el).parent().hasClass('ui-resizable'))
				return;
			$(v.attr_el).parent().resizable({
				minWidth:40,
				maxWidth:400,
				grid:10,
				handles:'e',
				stop:function(e, ui) {
					v.width = ui.size.width;
				}
			});
		}
		function cmpUpdate() {//обновление значения компонента
			var val = [];
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.find('.inp').attr('val'));
				if(!id)
					return;
				val.push(id);
			});
			cmp.val(val);
		}
	},
	_dialogCmpV49 = function(o, unit) {//Настройка содержания Сборного текста
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(unit == 'get') {
			var send = {};
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.attr('val'));
				if(!id)
					return;
				send[id] = {
					num_1:sp.find('.spc').val()
				};
			});
			return send;
		}

		var cmp = $(o.attr_cmp),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить элемент</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id элемента
				dialog_id:0,
				num_1:1     //пробел справа
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="элемент не выбран"' +
									  ' value=""' +
								' />' +
							'<td class="w25">' +
								'<input type="hidden" class="spc" value="' + v.num_1 + '" />' +
							'<td class="w50 r">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('Удалить элемент', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_elemChoose({
					dialog_id:v.dialog_id,
					unit_id:v.id,           //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_open:function(res) {
						res.block_id = _num('-' + unit.id, 1);
					},
					func_save:function(ia) {
						if(!v.id) {
							DD.attr('val', ia.unit.id);
							cmpUpdate();
						}
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.num_1);
					}
				});
			});
			DD.find('.spc')._check({tooltip:'Пробел справа'});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y',
				stop:cmpUpdate
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
				cmpUpdate();
				v.id = 0;
			});
			NUM++;
		}
		function cmpUpdate() {//обновление значения компонента
			var val = [];
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.attr('val'));
				if(!id)
					return;
				val.push(id);
			});
			cmp.val(val);
		}
	},

	_elemActivate = function(elem, unit, is_edit) {//активирование элементов
		var attr_focus = false;//элемент, на который будет поставлен фокус

		_forIn(elem, function(el) {
			if(el.focus)
				attr_focus = el.attr_cmp;

			if(!is_edit && el.hint_on) {
				var side = {
						0:'auto',
						755:'top',
						756:'bottom',
						757:'left',
						758:'right'
					},
					sideObj = {
						755:'h',
						756:'h',
						757:'v',
						758:'v'
					},
					objPos = {
						767:'center',
						768:'left',
						769:'right',

						772:'center',
						773:'top',
						774:'bottom'
					};
				$(el.attr_el).mouseenter(function() {
					var oo = {
						msg:el.hint_msg,
						pad:10,
						side:side[el.hint_side],
						show:1,
						delayShow:el.hint_delay_show,
						delayHide:el.hint_delay_hide
					};
					if(el.hint_side)
						oo.objPos = objPos[el['hint_obj_pos_' + sideObj[el.hint_side]]];
					$(el.attr_el)._hint(oo);
				});
			}

			switch(el.dialog_id) {
				case 1://галочка
					_elemFunc(el, _num(unit[el.col] || 0), is_edit, 1);
					$(el.attr_cmp)._check({
						func:function(v) {
							_elemFunc(el, v, is_edit);
						}
					});
					return;
				//textarea
				case 5:	$(el.attr_cmp).autosize(); return;
				//select - выбор страницы
				case 6:
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						spisok:PAGE_LIST
					});
					return;
				//search
				case 7:
					$(el.attr_cmp)._search({
						func:function(v, obj) {
							var send = {
								op:'spisok_search',
								elem_id:el.id,
								v:v,
								busy_obj:obj.icon_del,
								busy_cls:'spin'
							};
							_post(send, function(res) {
								$(res.spisok_attr).html(res.spisok_html);
								$(res.count_attr).html(res.count_html);
							});
						}
					});
					return;
				//select - произвольные значения
				case 17:
					_elemFunc(el, _num(unit[el.col] || el.def), is_edit, 1);
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv,
						func:function(v) {
							_elemFunc(el, v, is_edit);
						}
					});
					return;
				//наполнение для некоторых компонентов
				case 19:
					if(is_edit)
						return;
					_dialogCmpV19(el);
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Список действий, привязанных к элементу
				case 22:
					if(is_edit)
						return;
					$(el.attr_el).find('DL')._sort({table:'_element_func'});
					return;
				//select - выбор списка (все списки приложения)
				case 24:
					_elemFunc(el, _num(unit[el.col] || el.def), is_edit, 1);
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv,
						func:function(v) {
							_elemFunc(el, v, is_edit);
						}
					});
					return;
				//настройка шаблона единицы списка
				case 25:
					if(is_edit)
						return;
					$('#block-level-spisok')
						.find('.block-grid-on')
						.removeClass('grey')
						.trigger('click');
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Содержание диалога для выбора значения
				case 26:
					if(is_edit)
						return;
					if(!window.DIALOG_OPEN)
						return;
					var bec = DIALOG_OPEN.content.find('.choose');
					bec.click(function() {
						var t = $(this),
							ids = [];
						if(t.hasClass('deny'))
							return;
						if(el.num_3) {
							var sel = t.hasClass('sel');
							t._dn(sel, 'sel');
						} else {
							bec.removeClass('sel');
							t.addClass('sel');
						}
						_forEq(bec, function(el) {
							if(el.hasClass('sel'))
								ids.push(_num(el.attr('val')));
						});
						$(el.attr_cmp).val(ids.join(','));
					});
					return;
				//select - выбор списка, размещённого на текущей странице
				case 27:
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv
					});
					return;
				//select - выбор единицы из другого списка (для связки)
				case 29:
					var o = {
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						write:el.num_1 && el.num_3,
						msg_empty:'Не найдено',
						spisok:el.vvv,
						funcWrite:function(v, t) {
							var send = {
								op:'spisok_connect_29',
								cmp_id:el.id,
								v:v,
								busy_obj:t.icon_del,
								busy_cls:'spin'
							};
							_post(send, function(res) {
								t.spisok(res.spisok);
							});
						}
					};
					if(!el.num_1)
						o.msg_empty = 'Список пока не привязан';
					if(el.num_1 && el.num_2)
						o.funcAdd = function(t) {
							var send = {
								op:'dialog_open_load',
								page_id:PAGE_ID,
								dialog_id:el.num_1,
								busy_obj:t.icon_add,
								busy_cls:'spin'
							};
							_post(send, function(res) {
								res.func = function(ia) {
									t.unitUnshift({
										id:ia.unit.id,
										title:ia.unit.txt_1
									});
									t.value(ia.unit.id);
								};
								_dialogOpen(res);
							});
						};
					$(el.attr_cmp)._select(o);
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка ТАБЛИЧНОГО содержания списка
				case 30:
					if(is_edit)
						return;
					_dialogCmpV30(el, unit);
					return;
				//Выбор значений для содержания Select
				case 31:
					if(is_edit)
						return;
					var sv = $(el.attr_el).find('.sv'),
						ex = $(el.attr_cmp).val().split(','),
						v = [_num(ex[0]),_num(ex[1])];
					$(el.attr_cmp).val(v.join(','));
					sv.click(function() {
						var t = $(this),
							n = _num(t.attr('val')),
							attr_cmp = ELM[el.num_1].attr_cmp;
						_elemChoose({
							dialog_id:11,
							block_id:el.block_id,
							dialog_source:$(attr_cmp).val(),
							unit_id:v[n],
							busy_obj:t,
							busy_cls:'hold',
							func_open:function(res) {
								res.block_id = el.id * -1;
							},
							func_save:function(ia) {
								v[n] = ia.unit.id;
								t.val('выбрано');
								$(el.attr_cmp).val(v.join(','));
							}
						});
					});
					return;
				//count - количество
				case 35:
					$(el.attr_cmp)._count({
						disabled:is_edit,
						width:el.width,
						min:el.num_1,
						max:el.num_2,
						step:el.num_3,
						minus:el.num_4
					});
					return;
				//SA: Select - выбор имени колонки
				case 37:
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:'не выбрано',
						msg_empty:'колонок нет',
						spisok:el.vvv
					});
					_forN(el.vvv, function(u) {
						if(u.title == unit.col) {
							$(el.attr_cmp)._select(u.id);
							return false;
						}
					});
					return;
				//SA: Select - выбор диалогового окна
				case 38:
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						msg_empty:'диалоги ещё не были созданы',
						spisok:el.vvv
					});
					return;
				//SA: Select - дублирование
				case 41:
					$(el.attr_cmp)._select({
						disabled:is_edit,
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv
					});
					return;
				//Иконка вопрос: Выплывающая подсказка
				case 42:
					if(is_edit)
						return;
					var side = {
						0:'auto',
						741:'top',
						742:'bottom',
						743:'left',
						744:'right'
					};

					$(el.attr_cmp).mouseenter(function() {
						$(el.attr_cmp)._hint({
							msg:_br(el.txt_1, 1),
							pad:10,
							side:side[el.num_1],
							show:1
						});
					});
					return;
				//наполнение для некоторых компонентов
				case 49:
					if(is_edit)
						return;
					_dialogCmpV49(el, unit);
					return;
			}
		});

		if(!is_edit && attr_focus)
			$(attr_focus).focus();
	},
	_elemFunc = function(el, v, is_edit, is_open) {//применение функций, привязанных к элементам
		/*
			is_open - окно открылось, эффектов нет, только применение функций
		*/
		if(is_edit)
			return;

		_forN(el.func, function(sp) {
			switch(sp.dialog_id) {
				//показ/скрытие блоков
				case 36://Галочка[1]:
				case 40://Выпадающее поле[17]:
					var is_show = 0;//скрывать или показывать блоки. По умолчанию скрывать.

					//ДЕЙСТВИЕ
					switch(sp.action_id) {
						//скрыть
						case 709:
						case 726:
						default: break;
						//показать
						case 710:
						case 727:
							is_show = 1;
							break;
					}

					//УСЛОВИЕ
					switch(sp.cond_id) {
						case 703://значение не выбрано
						case 730://галочка снята
							if(v && sp.action_reverse) {
								is_show = is_show ? 0 : 1;
								break;
							}
							if(v)
								return;
							break;
						case 704://значение выбрано
						case 731://галочка установлена
							if(!v && sp.action_reverse) {
								is_show = is_show ? 0 : 1;
								break;
							}
							if(!v)
								return;
							break;
						case 705://конкретное значение
							if(v != sp.value_specific) {
								if(sp.action_reverse) {
									is_show = is_show ? 0 : 1;
									break;
								}
								return;
							}
							break;
						default: return;
					}

					//ПРОЦЕСС
					_forN(_elemFuncBlockObj(sp), function(oo) {
						if(!oo.obj.length)
							return;

						switch(sp.effect_id) {
							//изчезновение/появление
							case 44:
							case 715:
								if(is_open) {
									oo.obj._dn(is_show, 'vh');
									oo.obj.css({opacity:is_show});
									return;
								}
								oo.obj._dn(1, 'vh');
								oo.obj.animate({opacity:is_show}, 300, function() {
									oo.obj._dn(is_show, 'vh');
								});
								return;
							//сворачивание/разворачивание
							case 45:
							case 716:
								if(!oo.slide) {
									oo.obj._dn(is_show, 'vh');
									return;
								}
								if(is_open) {
									oo.obj[is_show ? 'show' : 'hide']();
									return;
								}
								oo.obj['slide' + (is_show ? 'Down' : 'Up')](300);
								return;
							default:
								if(!oo.slide) {
									oo.obj._dn(is_show, 'vh');
									return;
								}
								oo.obj[is_show ? 'show' : 'hide']();
						}
					});
					break;
			}
		});
	},
	_elemFuncBlockObj = function(sp) {//получение $(obj) блоков
		var TRG = _copyObj(sp.target),
			arr = [];
		_forIn(TRG, function(n, block_id) {
			if(!n)
				return;
			var BL = BLK[block_id];
			if(BL.xx == 1) {//если блок в ряду один, фукнция применится ко всей таблице
				arr.push({
					obj:_parent($(BL.attr_bl), '.bl-div'),
					slide:1
				});
				return;
			}

			//проверка, поставлена та же функция на остальные блоки в том же ряду
			var all = 1;
			_forIn(BL.xx_ids, function(i, id) {
				if(!TRG[id]) {//выход, если не на всех
					all = 0;
					return false;
				}
			});

			if(all) {
				_forIn(BL.xx_ids, function(i, id) {
					TRG[id] = 0;//блоки в том же ряду отмечаются, чтобы к ним функция не применялась
				});
				arr.push({
					obj:_parent($(BL.attr_bl), '.bl-div'),
					slide:1
				});
				return;
			}

			//функция будет применена к конкретному блоку
			arr.push({
				obj:$(BL.attr_bl),
				slide:0
			});
		});

		return arr;
	},

	_elemChoose = function(v) {//ВЫБОР элемента для вставки
		v = $.extend({
			type:'all',
			dialog_id:0,//диалог, который вносит элемент
			dialog_source:0,//исходный диалог, либо настраиваемый
			block_id:0, //блок (или отрицательный id элемента - группировка), в который вставляется элемент
			unit_id:0,  //id единицы списка (элемент или функция)

			busy_obj:null,
			busy_cls:'_busy',

			func_open:function() {},    //функция, выполняемая перед открытием диалога, после его успешной загрузки
			func_save:null              //функция, выполняемая после успешной вставки элемента
		}, v);

		if(v.dialog_id)
			return dialogGet();

		var html;
		switch(v.type) {
			case 'table':  html = _elemChooseTable(); break;
			case 'func_1': html = _elemChooseFunc1(); break;
			default:html = _elemChooseAll();
		}

		$('._hint').remove();
		var dialog = _dialog({
				width:500,
				top:20,
				head:'Выбор элемента для вставки',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		dialog.content.find('button')
			.click(function() {
				v.busy_obj = $(this);
				v.busy_cls = '_busy';
				v.dialog_id = v.busy_obj.attr('val');
				var func = v.func_open;
				v.func_open = function(res) {
					func(res);
					dialog.close();
				};
				$('._hint').remove();
				dialogGet();
			})
			.mouseenter(function() {
				var t = $(this),
					msg = t.attr('data-hint');
				if(!msg)
					return;
				t._hint({
					msg:'<div class="blue">' + msg + '</div>',
					pad:10,
					show:1
				});
			});

		function dialogGet() {
			var send = {
				op:'dialog_open_load',
				page_id:PAGE_ID,
				dialog_id:v.dialog_id,
				dialog_source:v.dialog_source,
				block_id:v.block_id,
				unit_id:v.unit_id,
				busy_obj:v.busy_obj,
				busy_cls:v.busy_cls
			};
			_post(send, function(res) {
				v.func_open(res);
				res.func = v.func_save;
				_dialogOpen(res);
			});
		}
	},
	_elemChooseAll = function() {//все варианты элементов
		return '<div class="center mt5">' +
			'<div class="hd2 mb5">Компоненты для внесения данных</div>' +
				'<button val="8"  class="vk grey" data-hint="Однострочное поле">8</button>' +
				'<button val="5"  class="vk grey ml5" data-hint="Многострочное поле">5</button>' +
				'<button val="1"  class="vk ml5" data-hint="Галочка">1</button>' +
				'<button val="16" class="vk ml5" data-hint="Radio">16</button>' +
				'<button val="-6" class="vk ml5" data-hint="Календарь">-</button>' +
				'<button val="35" class="vk ml5" data-hint="Количество">35</button>' +
			'<p class="mt10">' +
				'<div class="dib fs15 mt5">Select:</div>' +
				'<button val="17" class="vk ml5" data-hint="Select - произвольные значения">17</button>' +
				'<button val="6"  class="vk ml5" data-hint="Select - страницы">6</button>' +
				'<button val="24" class="vk ml5" data-hint="Select - списки приложения">24</button>' +
				'<button val="27" class="vk ml5" data-hint="Select - списки на текущей странице">27</button>' +
				'<button val="29" class="vk ml5" data-hint="Select - выбор единицы из другого списка (связка)">29</button>' +
			'<p class="mt10">' +
				'<button val="31" class="vk orange" data-hint="Значения для содержания Select">31</button>' +
				'<button val="38" class="vk red ml5" data-hint="Select - выбор диалогового окна">38</button>' +
				'<button val="41" class="vk red ml5" data-hint="Select - значения из существующего селекта">41</button>' +
				'<button val="37" class="vk red ml5" data-hint="Select - выбор имени колонки">37</button>' +

			'<div class="hd2 mt20 mb5">Вспомогательные компоненты</div>' +
				'<button val="19" class="vk orange" data-hint="Содержание для некоторых компонентов">19</button>' +
				'<button val="25" class="vk orange ml5" data-hint="Настройка содержания списка-шаблона">25</button>' +
				'<button val="30" class="vk orange ml5" data-hint="Настройка содержания списка-таблицы">30</button>' +
				'<button val="26" class="vk orange ml5" data-hint="Содержание диалога для выбора значения">26</button>' +
				'<button val="43" class="vk pink ml5" data-hint="Прикрепление подсказки к элементу">43</button>' +
				'<button val="49" class="vk orange ml5" data-hint="Настройка содержания Сборного текста">49</button>' +

			'<div class="hd2 mt20 mb5">Функции</div>' +
				'<button val="28" class="vk" data-hint="Действия для галочки">28</button>' +
				'<button val="36" class="vk cancel ml5" data-hint="Действие для галочки: скрытие-показ блоков">36</button>' +
			'<p class="mt5">' +
				'<button val="39" class="vk" data-hint="Действия для выпадающего поля">39</button>' +
				'<button val="40" class="vk cancel ml5" data-hint="Действие для выпадающего поля: скрытие-показ блоков">40</button>' +
			'<p class="mt5">' +
				'<button val="22" class="vk orange" data-hint="Список действий у элементов">22</button>' +

			'<div class="hd2 mt20 mb5">Элементы для наполнения содержания</div>' +
				'<button val="3"  class="vk" data-hint="Меню страниц">3</button>' +
				'<button val="10" class="vk grey ml5" data-hint="Произвольный текст">10</button>' +
				'<button val="44" class="vk grey ml5" data-hint="Сборный текст">44</button>' +
				'<button val="2"  class="vk green ml5" data-hint="Кнопка">2</button>' +
				'<button val="4"  class="vk ml5" data-hint="Заголовок">4</button>' +
				'<button val="21" class="vk ml5" data-hint="Информация">21</button>' +
				'<button val="9"  class="vk ml5" data-hint="Ссылка на страницу">9</button>' +
				'<button val="42" class="vk ml5" data-hint="Иконка с вопросом: Выплывающая подсказка">42</button>' +

			'<div class="hd2 mt20 mb5">Элементы для списков</div>' +
				'<button val="7" class="vk">7 - search</button>' +
				'<button val="15" class="vk ml5" data-hint="Количество строк">15</button>' +
				'<button val="14" class="vk ml5">14 - Шаблон</button>' +
				'<button val="23" class="vk ml5">23 - Таблица</button>' +
			'<p class="mt10">' +
				'<button val="11" class="vk cancel" data-hint="Значение: из единицы списка">11</button>' +
				'<button val="32" class="vk cancel ml5" data-hint="Значение: Порядковый номер">32</button>' +
				'<button val="33" class="vk cancel ml5" data-hint="Значение: Дата">33</button>' +
				'<button val="34" class="vk cancel ml5" data-hint="Значение: Иконки управления">34</button>' +

	        '<div class="hd2 mt20 mb5">Элементы для SA</div>' +
				'<button val="12" class="vk red" data-hint="PHP-функция">12</button>' +
		'</div>';
	},
	_elemChooseTable = function() {
		return '<div class="hd2 mt10">Варианты выбора для ячейки таблицы:</div>' +
			'<button val="11" class="vk cancel mt5">Значение из диалога</button>' +
			'<button val="32" class="vk cancel mt5">Значение: Порядковый номер</button>' +
			'<button val="33" class="vk cancel mt5">Значение: Дата</button>' +
			'<button val="34" class="vk cancel mt5">Значение: Иконки управления</button>' +
		'';
	},

	_pageSetupAppPage = function() {//сортировка страниц приложения с учётом уровней
		$('#page-sort').nestedSortable({
			forcePlaceholderSize: true,//сохранять размер места, откуда был взят элемент
			placeholder:'placeholder',//класс, применяемый для подсветки места, откуда взялся элемент
			handle:'div',
//			helper:	'clone',
			listType:'ol',
			items:'li',
//			tolerance:'pointer',
			toleranceElement:'> div',
			isTree:true,
			maxLevels:3,
//			startCollapsed: false,
			tabSize:30,//расстояние, на которое надо сместить элемент, чтобы он перешёл на другой уровень
//			expandOnHover:700,
//			opacity:1,
			revert:200, //плавное возвращение (полёт) элемента на своё место. Цифра - скорость в миллисекундах.

			update:function() {
				var send = {
					op:'page_sort',
					arr:$(this).nestedSortable('toArray')
				};
				_post(send);
			},

			expandedClass:'pb10',//раскрытый список
			errorClass:'bg-fcc' //ошибка, если попытка переместить элемент на недоступный уровень
		});
	};

$(document)
	.on('click', '._check', function() {//установка/снятие галочки, если была выведена через PHP
		var t = $(this);
		if(t.hasClass('noon'))//если галочка выведена через JS, а не через PHP, то действия нет
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
	})

	.on('click', '.dialog-open', function() {//нажатие на кнопку, иконку для открытия диалога
		var t = $(this),
			val = t.attr('val'),
			send = {
				op:'dialog_open_load',
				page_id:PAGE_ID,//id текущей страницы
				dialog_id:0,    //id диалогового окна
				block_id:0,     //id блока, если элемент вставляется в блок
				unit_id:0,      //id единицы списка, если редактируется

				dialog_source:0,//id исходного диалогового окна

				busy_obj:t,
				busy_cls:t.hasClass('icon') ? 'spin' : '_busy'
			};

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1]);
		});

		_post(send, _dialogOpen);
	})

	.on('mouseenter', '.dialog-hint', function() {//отображение подсказки при наведении на вопрос в диалоге
		var t = $(this),
			msg = t.attr('val');

		if(!msg)
			return;

		t._hint({
			msg:msg,
			pad:10,
			show:1,
			delayShow:500,
			delayHide:300
		});
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

	var div_id = attr_id + '_check',
		win = attr_id + '_check_win',
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

	var CHECK = $('#' + div_id);

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
		var nx = t.next();
		if(nx.hasClass('_check'))   //если галочка была выведена через PHP - обновление и применение функций
			o = $.extend({
				title:nx.html(),
				disabled:nx.hasClass('disabled'),
				light:nx.hasClass('light'),
				block:nx.hasClass('block')
			}, o);

		t.next().remove('._check');
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
				'<div id="' + div_id + '" class="_check noon' + on + title + light + block + dis + '">' +
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
$.fn._radio = function(o) {
	var t = $(this),
		n,
		attr_id = t.attr('id'),
		s;

	if(!attr_id) {
		attr_id = 'radio' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_radio';

	switch(typeof o) {
		case 'number':
			s = window[win];
			s.value(o);
			return t;
		case 'string':
			s = window[win];
			if(o == 'disable')
				s.dis();
			if(o == 'enable')
				s.enab();
			if(o == 'func')
				s.funcGo();
			return t;
	}

	var cnt = t.next();
	if(cnt.hasClass('_radio'))
		if(cnt.hasClass('php')) {
			cnt.removeClass('php')
			   .attr('id', win);
		} else
			cnt.remove();

	o = $.extend({
		title0:'',
		spisok:[],
		disabled:0,
		light:0,
		block:1,
		interval:7, //интервал между значениями
		func:function() {}
	}, o);

	_print();

	var RADIO = $('#' + win),
		RDIV = RADIO.find('div');


	RDIV.click(function() {
		if(RADIO.hasClass('disabled'))
			return;

		var div = $(this),
			v = _num(div.attr('val'));
		setVal(v);
		o.func(v, attr_id);
	});

	function _print() {
		if(t.next().hasClass('_radio'))
			return;
		var spisok = _copySel(o.spisok),
			val = _num(t.val(), 1),
			block = o.block ? ' block' : '',
			light = o.light ? ' light' : '',
			dis = o.disabled ? ' disabled' : '',
			html = '<div class="_radio' + block + dis + light + '" id="' + win + '">';

		if(o.title0)
			spisok.unshift({uid:0,title:o.title0});

		_forN(spisok, function(sp) {
			var on = val == sp.uid ? 'on' : '';
			html += '<div class="' + on + '" val="' + sp.uid + '" style="margin-bottom:' + o.interval + 'px">' +
						sp.title +
					'</div>';
		});

		html += '</div>';

		t.after(html);
	}

	function setVal(v) {
		RADIO.find('div.on').removeClass('on');
		for(n = 0; n < RDIV.length; n++) {
			var sp = RDIV.eq(n),
				vv = _num(sp.attr('val'));
			if(vv == v) {
				sp.addClass('on');
				break;
			}
		}
		t.val(v);
	}


	t.value = setVal;
	t.funcGo = function() {//применение фукнции
		o.func(_num(t.val()), attr_id);
	};
	t.dis = function() {//перевод галочки в неактивное состояние
		RADIO.addClass('disabled');
	};
	t.enab = function() {//перевод галочки в активное состояние
		RADIO.removeClass('disabled');
	};
	window[win] = t;
	return t;
};
$.fn._count = function(o) {//input с количеством
	var t = $(this),
		n,
		attr_id = t.attr('id'),
		s;

	if(!attr_id) {
		attr_id = 'count' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_count';

	o = $.extend({
		width:50,   //если 0 = 100%
		bold:0,
		min:false,  //минимальное значение
		max:false,  //максимальное значение
		minus:0,    //может уходить в минус
		step:1,     //шаг
		tooltip:'',
		disabled:0,
		func:function() {}
	}, o);

	if(o.min < 0)
		o.minus = 1;

	var val = _num(t.val());
	valCorrect();
	t.val(val)
	 .attr('type', 'text')
	 .attr('readonly', true);

	var width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		dis = o.disabled ? ' disabled' : '';

	if(t.parent().hasClass('_count')) {
		t.parent()
			._dn(dis == '', 'disabled')
			.attr('id', win)
			.width(o.width || '100%')
			.find('.but').remove();
	} else {
		t.wrap('<div class="_count' + dis + '" id="' + win + '" style="' + width + '">');
	}

	var el = $('#' + win);
	el._dn(val, 'nol');
	el.append(
		'<div class="but"></div>' +
		'<div class="but but-b"></div>'
	);

	if(o.bold)
		t.addClass('b');

	if(o.tooltip)
		el._tooltip(o.tooltip, -15);

	el.find('.but').click(function() {
		if(dis)
			return;
		var znak = $(this).hasClass('but-b') ? -1 : 1;
		val += o.step * znak;

		valCorrect();

		el._dn(val, 'nol');
		t.val(val);
		o.func(val, attr_id);
	});
	function valCorrect() {
		if(!o.minus && val < 0)
			val = 0;

		if(o.max !== false && val > o.max)
			val = o.max;

		if(o.min !== false && val < o.min)
			val = o.min;
	}

	window[win] = t;
	return t;
};
$.fn._select = function(o) {//выпадающий список от 03.01.2018
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id'),
		VALUE = t.val();

	if(!attr_id) {
		attr_id = 'select' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_select',
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
		disabled:0,
		block:0,       	    // расположение селекта
		title0:'',			// поле с нулевым значением
		spisok:[],			// результаты в формате json
		write:0,            // возможность вводить значения
		write_save:0,       // сохранять текст, если даже не выбран элемент
		msg_empty:'Список пуст',
		multiselect:0,      // возможность выбирать несколько значений. Идентификаторы перечисляются через запятую
		func:function() {},	// функция, выполняемая при выборе элемента
		funcWrite:function() {},// функция, выполняемая при вводе в INPUT в селекте. Нужна для вывода списка из вне, например, Ajax-запроса, либо из vk api.
		funcAdd:null	    // добавления новой единицы. Если указана, показывает плюсик.
	}, o);

	var dis = o.disabled ? ' disabled' : '',
		dib = o.block ? '' : ' dib',
		width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		readonly = o.write ? '' : ' readonly',
		placeholder = o.title0 ? ' placeholder="' + o.title0 + '"' : '',
		iconAddFlag = o.funcAdd && !dis,
		html =
		'<div class="_select' + dis + dib + '" id="' + win + '" style="' + width + '">' +
			'<table class="w100p">' +
				'<tr><td><input type="text" class="select-inp"' + placeholder + readonly + ' />' +
					'<td class="w15' + _dn(o.write) + '"><div class="icon icon-del pl dn"></div>' +
					'<td class="w25 r' + _dn(iconAddFlag) + '"><div class="icon icon-add pl"></div>'+
					'<td class="arrow">' +
			'</table>' +
			'<div class="select-res"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	var SEL = t.next(),
		INP = SEL.find('.select-inp'),
		RES = SEL.find('.select-res'),
		ICON_DEL = SEL.find('.icon-del'),
		ICON_ADD = SEL.find('.icon-add'),
		MASS_ASS,//ассоциативный массив в виде {1:'text'}
		MASS_SEL,//массив в виде [{id:1,title:'text1'},{id:2,title:'text2'}]
		MASS_SEL_SAVE;//дублирование MASS_SEL

	massCreate();
	spisokPrint();
	valueSet(VALUE);

	INP.keydown(function(e) {
		setTimeout(function() {
			VALUE = 0;
			t.val(0);
			var v = INP.val();
			ICON_DEL._dn(v);
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
			var p = _parent(tagret, '.select-unit');
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
		var h = RES.height();
		RES._dn(h < 250, 'h250');

		if(!rs)
			_forEq(RES.find('.select-unit'), function(sp) {
				if(VALUE == sp.attr('val')) {
					RES.find('.select-unit').removeClass('ov');
					if(sp.hasClass('info'))
						return false;
					sp.addClass('ov');
					return false;
				}
			});
	});
	ICON_DEL.click(function() {
		valueSet(0);
		o.funcWrite('', t);
	});
	if(iconAddFlag)
		SEL.find('.icon-add').click(function() {
			o.funcAdd(t);
		});

	$(document)
		.off('click._select')
		.on('click._select', function(e) {
			var target = $(e.target),
				cur,
				attr = '';

			if(target.hasClass('_select'))
				cur = target;
			else
				cur = _parent(target, '._select')

			if(cur.hasClass('_select'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._select' + attr).removeClass('rs');
		});

	function massCreate() {//создание массива для корректного вывода списка
		var unit;

		MASS_ASS = {};
		MASS_SEL = [];
		MASS_SEL_SAVE = [];

		if(o.title0)
			MASS_ASS[0] = '';

		//исходный список является ассоциативным объектом
		if(!o.spisok.length) {
			_forIn(o.spisok, function(sp, id) {
				id = _num(id);
				if(!id)
					return;
				MASS_ASS[id] = sp;
				unit = {
					id:id,
					title:sp,
					content:sp
				};
				MASS_SEL.push(unit);
				MASS_SEL_SAVE.push(unit);
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
					id = -1;
				if(id === undefined)
					return;
				id = _num(id, 1);
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
				info:_num(sp.info)//флаг информационного значения. Значение нельзя выбрать.
			};
			MASS_SEL.push(unit);
			MASS_SEL_SAVE.push(unit);
		});
	}
	function spisokPrint() {//вставка списка в select
		RES.removeClass('h250');
		if(!MASS_SEL.length) {
			RES.html('<div class="empty">' + o.msg_empty + '</div>');
			return;
		}

		var html = '';
		if(o.title0 && !o.write)
			html += '<div class="select-unit title0" val="0">' + o.title0 + '</div>';

		_forN(MASS_SEL, function(sp) {
			var info = sp.info ? ' info' : '',
				val = info ? '' : ' val="' + sp.id + '"';
			html += '<div class="select-unit' + info + '"' + val + '>' + sp.content + '</div>';
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
	function valueSet(v) {//установка значения
		v = _num(v);
		VALUE = v;
		t.val(v);
		INP.val(MASS_ASS[v]);
		ICON_DEL._dn(v && o.write)
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
			case 'inp': return s.inp();
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
	t.process = function() {//показ процесса ожидания
		ICON_DEL.addClass('spin');
	};
	t.isProcess = function() {//получение флага процесса ожидания
		return ICON_DEL.hasClass('spin');
	};
	t.cancel = function() {//отмена процесса ожидания
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
		inpClear,_dn(v);
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

	o.func();

	var HINT = $('#hint' + HN),
		MSG = HINT.find('.hi-msg'),
		UG = HINT.find('.ug');

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

	if(t.hasClass('_tooltip'))
		return t;

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
	var t = $(this),
		id = t.attr('id'),
		val = t.val(),
		d = new Date();

	o = $.extend({
		year:d.getFullYear(),	// если год не указан, то текущий год
		mon:d.getMonth() + 1,   // если месяц не указан, то текущий месяц
		day:d.getDate(),		// то же с днём
		lost:0,                 // если не 0, то можно выбрать прошедшие дни
		func:function () {},    // исполняемая функция при выборе дня
		place:'right',          // расположение календаря относительно выбора
		tomorrow:0              // ссылка "завтра" для быстрой установки завтрашней даты
	}, o);

	// если input hidden содежит дату, применение её
	if(REGEXP_DATE.test(val) && val != '0000-00-00') {
		var r = val.split('-');
		o.year = r[0];
		o.mon = Math.abs(r[1]);
		o.day = Math.abs(r[2]);
	}

	//удаление такого же календаря при повторном вызове
	t.next().remove('._calendar');

	t.after(
		'<div class="_calendar" id="' + id + '_calendar">' +
			'<div class="calinp">' + o.day + ' ' + MONTH_DAT[o.mon] + ' ' + o.year + '</div>' +
			'<div class="calabs"></div>' +
		'</div>'
	);

	var	curYear = o.year,//дата,
		curMon = o.mon,  //установленная
		curDay = o.day,  //в input hidden
		inp = t.next().find('.calinp'),
		calabs = inp.next(),//место для календаря
		calmon,             //место для месяца и года
		caldays;            //место для дней

	if(o.tomorrow) {
		inp
			.after('<a class="dib ml10 grey">завтра</a>')
			.next().click(function() {
				var tmr = new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
				o.year = tmr.getFullYear();
				o.mon = tmr.getMonth() + 1;
				daySel(tmr.getDate());
			});
	}

	t.val(dataForm());
	inp.click(calPrint);

	function calPrint(e) {
		if(!calabs.html()) {
			e.stopPropagation();

			// если были открыты другие календари, то закрываются, кроме текущего
			var cals = $('.calabs');
			for(var n = 0; n < cals.length; n++) {
				var sp = cals.eq(n);
				if(sp.parent().attr('id').split('_calendar')[0] == id)
					continue;
				sp.html('');
			}

			// закрытие текущего календаря при нажатии на любое место экрана
			$(document).on('click.calendar' + id, function () {
				calabs.html('');
				$(document).off('click.calendar' + id);
			});

			o.year = curYear;
			o.mon = curMon;
			o.day = curDay;

			var html =
				'<div class="calcal" style="left:' + (o.place == 'right' ? 0 : -64) + 'px">' +
					'<table class="calhead">'+
						'<tr><td class="calback">' +
							'<td class="calmon">' + MONTH_DEF[curMon] + ' ' + curYear +
							'<td class="calnext">' +
					'</table>' +
					'<table class="calweeks"><tr><td>Пн<td>Вт<td>Ср<td>Чт<td>Пт<td>Сб<td>Вс</table>' +
					'<table class="caldays"></table>' +
				'</div>';
			calabs.html(html);
			calabs.find('.calback').click(back);
			calabs.find('.calnext').click(next);
			calmon = calabs.find('.calmon');
			caldays = calabs.find('.caldays');
			daysPrint();
		}
	}
	function daysPrint() {//вывод списка дней
		var n,
			html = '<tr>',
			year = d.getFullYear(),
			mon = d.getMonth() + 1,
			today = d.getDate(),
			df = dayFirst(o.year, o.mon),
			cur = year == o.year && mon == o.mon,// выделение текущего дня, если показан текущий год и месяц
			lost = o.lost == 0, // затемнение прошедших дней
			st = o.year == curYear && o.mon == curMon, // выделение выбранного дня
			dc = dayCount(o.year, o.mon);

		//установка пустых ячеек
		if(df > 1)
			for(n = 0; n < df - 1; n++)
				html += '<td>';

		for(n = 1; n <= dc; n++) {
			var l = '';
			if(o.year < year) l = ' lost';
			else if(o.year == year && o.mon < mon) l = ' lost';
			else if(o.year == year && o.mon == mon && n < today) l = ' lost';
			html +=
				'<td class="' + (!l || l && !lost ? ' sel' : '') +
								(cur && n == today ? ' b' : '') +
								(st && n == curDay ? ' set' : '') +
								l + '"' +
							(!l || l && !lost ? ' val="' + n + '"' : '') +
					'>' + n;
			df++;
			if(df == 8 && n != dc) {
				html += "<tr>";
				df = 1;
			}
		}
		caldays
			.html(html)
			.find('.sel').click(function() {
				daySel($(this).attr('val'));
			})
	}
	function daySel(v) {
		curYear = o.year;
		curMon = o.mon;
		curDay = v;
		inp.html(curDay + ' ' + MONTH_DAT[curMon] + ' ' + curYear);
		t.val(dataForm());
		o.func(dataForm());
	}
	function dataForm() {//формирование даты в виде 2012-12-03
		return curYear +
			'-' + (curMon < 10 ? '0' : '') + curMon +
			'-' + (curDay < 10 ? '0' : '') + curDay;
	}
	function dayFirst(year, mon) {//номер первой недели в месяце
		var first = new Date(year, mon - 1, 1).getDay();
		return first == 0 ? 7 : first;
	}
	function dayCount(year, mon) {//количество дней в месяце
		mon--;
		if(mon == 0) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function back(e) {//пролистывание календаря назад
		e.stopPropagation();
		o.mon--;
		if(o.mon == 0) {
			o.mon = 12;
			o.year--;
		}

		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
	function next(e) {//пролистывание календаря вперёд
		e.stopPropagation();
		o.mon++;
		if(o.mon == 13) {
			o.mon = 1;
			o.year++;
		}
		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
};
$.fn._search = function(o, v) {//поисковая строка
	/*
		Оборачивается input:text
		attr_id не обязателен
	*/
	var t = $(this),
		attr_id = t.attr('id'),
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
				if(v) {
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

	if(S && S.inp)
		return S;

	o = $.extend({
		width:150,      //ширина. Если 0 = 100%
		placeholder:'', //текст-подсказка
		focus:0,        //сразу устанавливать фокус
		enter:0,        //применять введённый текст только после нажатия ентер
		func:function() {}
	}, o);

	//вывод поиска, если не был вставлен через PHP. Иначе только применение функций
	if(!_parent(t, '._search').hasClass('_search')) {
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
		s;

	if(!attr_id)
		return;

	switch(typeof o){
		case 'number':
		case 'string':
		case 'boolean':
			s = window[win];
			var v = _num(o);
			s.value(v);
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

	var mainDiv = $('#' + attr_id + '_menu'),
		link = mainDiv.find('.link');

	link.click(_click);

	function _init() {
		var html = '<div class="_menu' + o.type + '" id="' + attr_id + '_menu">';

		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n],
				sel = val == sp.uid ? ' sel' : '';
			html +=
				'<a class="link' + sel + '" val="' + sp.uid + '">' +
					sp.title +
				'</a>';
		}

		html += '</div>';

		tMain.after(html);
	}
	function _click() {
		var t = $(this),
			v = _num(t.attr('val'));
		link.removeClass('sel');
		t.addClass('sel');
		tMain.val(v);
		_pageCange(v);
		o.func(v, attr_id);
	}
	function _pageCange(v) {
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			$('.' + attr_id + '-' + sp.uid)[(v == sp.uid ? 'remove' : 'add') + 'Class']('dn');
		}
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
	var t = $(this),
		id = t.attr('id');

	if(typeof o == 'number' || typeof o == 'string') {
		switch(o) {
			case 'remove':t.next().remove('._dropdown'); break;
			default: window[id + '_dropdown'].value(o);
		}
		return t;
	}

	o = $.extend({
		head:'',    // если указано, то ставится в название ссылки, а список из spisok
		headgrey:0,
		disabled:0,
		title0:'',
		spisok:[],
		func:function() {},
		nosel:0 // не вставлять название при выборе значения
	}, o);
	var n,
		val = t.val() * 1 || 0,
		ass = assCreate(),
		head = o.head || o.title0,
		len = o.spisok.length,
		spisok = o.title0 && !o.disabled ? '<a class="ddu grey' + (!len ? ' last' : '') + (!val ? ' seld' : '') + '" val="0">' + o.title0 + '</a>' : '',
		delay = 0;
	t.val(val);
	for(n = 0; n < len; n++) {
		var sp = o.spisok[n];
		spisok += '<a class="ddu' + (n == len - 1 ? ' last' : '') + (val == sp.uid ? ' seld' : '') + '" val="' + sp.uid + '">' + sp.title + '</a>';
		if(val == sp.uid)
			head = sp.title;
	}
	t.next().remove('._dropdown');
	t.after(
		'<div class="_dropdown' + (o.disabled ? ' disabled' : '') + '" id="' + id + '_dropdown">' +
			(o.disabled ?
				'<span>' + head + '</span>'
				:
				'<a class="ddhead' + (!val && (o.headgrey || o.title0) ? ' grey' : '') + '">' + head + '</a>'
			) +
			'<div class="ddlist">' +
				'<div class="ddsel">' + head + '</div>' +
				spisok +
			'</div>' +
		'</div>');

	if(!o.disabled) {
		var dropdown = t.next(),
			aHead = dropdown.find('.ddhead'),
			list = dropdown.find('.ddlist'),
			ddsel = list.find('.ddsel'),
			ddu = list.find('.ddu');
		aHead.mouseover(function(e) {
			e.stopPropagation();
			delayClear();
			list.show();
		});
		ddsel.click(function(e) {
			e.stopPropagation();
			delayClear();
			list.hide();
		});
		ddu.click(function(e) {
			e.stopPropagation();
			var th = $(this),
				v = parseInt(th.attr('val'));
			setVal(v);
			if(!o.nosel)
				th.addClass('seld');
			list.hide();
			o.func(v, id);
		})
		   .mouseenter(function() {
				ddu.removeClass('seld');
		   });
		list.on({
			mouseleave:function () {
				delay = setTimeout(function() {
					list.fadeOut(200);
				}, 500);
			},
			mouseenter:delayClear
		});
	}

	function assCreate() {//Создание ассоциативного массива
		var arr = o.title0 ? {0:o.title0} : {};
		for (var n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			arr[sp.uid] = sp.title;
		}
		return arr;
	}
	function setVal(v) {
		delayClear();
		if(!o.nosel) {
			t.val(v);
			aHead.html(ass[v])[(o.title0 && !v ? 'add' : 'remove') + 'Class']('grey');
			ddsel.html(ass[v]);
		}
	}
	function delayClear() {
		if(delay) {
			clearTimeout(delay);
			delay = 0;
		}
	}

	t.value = function(v) {
		setVal(v);
		list.find('.seld').removeClass('seld');
		for(n = 0; n < ddu.length; n++) {
			var eq = ddu.eq(n);
			if(eq.attr('val') == v) {
				eq.addClass('seld');
				break;
			}
		}
	};
	window[id + '_dropdown'] = t;
	return t;
};



