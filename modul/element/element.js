/* Все элементы визуального отображения, используемые в приложении */
var DIALOG = {},//массив диалоговых окон для управления другими элементами

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

	_color = function(v, func) {
		$(document)
			.off('mouseenter', '._color td')
			.on('mouseenter', '._color td', function() {//показ цветов при наведении
				var td = $(this),
					v = td.attr('val');
				td._tooltip(ELEM_COLOR[v][1]);
			})
			.off('click', '._color td')
			.on('click', '._color td', function() {//установка цвета при выборе
				var td = $(this),
					v = td.attr('val'),
					COL = td.parents('._color');

				COL.find('td').css('color', 'transparent');
				td.css('color', '#fff');
				COL.css('background-color', ELEM_COLOR[v][0]);

				if(func)
					func(v);
			});


		var td = '',
			n = 0;
		_forIn(ELEM_COLOR, function(sp, i) {
			var bg = sp[0],
				sel = i == v ? '#fff' : 'transparent';
			if(!n || n == 7)
				td += '<tr>';
			td += '<td class="pad5 center" style="background-color:' + bg + ';color:' + sel + '" val="' + i + '">&#10004;';
			n++;
		});

		return '<div class="_color" style="background-color:' + ELEM_COLOR[v][0] + '">' +
			       '<table class="w200 bg-eee curP pabs">' + td + '</table>' +
			   '</div>';
	},

	_dialog = function(o) {//диалоговое окно
		o = $.extend({
			top:100,
			width:0,    //ширина диалога. Если 0 = автоматически
			mb:0,       //margin-bottom: отступ снизу от диалога (для календаря или выпадающих списков)
			pad:0,      //отступ для content

			dialog_id:0,//id диалога, загруженного из базы
			unit_id:0,  //id единицы списка, который вносится при помощи данного диалога (только для передачи при редактировании диалога)
			block_id:0, //id блока, в который вставляется элемент (только для передачи при редактировании диалога)

			edit_access:0,//показ иконки редактирования диалога

			color:'',   //цвет диалога - заголовка и кнопки
			head:'head: Название заголовка',
			content:'<div class="pad30 pale">content: содержимое центрального поля</div>',

			butSubmit:'Внести',
			butCancel:'Отмена',
			submit:function() {},
			cancel:dialogClose
		}, o);

		var DIALOG_NUM = $('._dialog').length,
			html =
			'<div class="_dialog-back"></div>' +
			'<div class="_dialog">' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh pl"></a></div>' +
		            '<div class="edit fr curP' + _dn(!DIALOG_NUM && o.edit_access && _cookie('face') == 'site') + '"><a class="icon icon-edit wh pl"></a></div>' +
					'<div class="fs14 white">' + o.head + '</div>' +
				'</div>' +
				'<div class="content bg-fff"' + (o.pad ? ' style="padding:' + o.pad + 'px"' : '') + '>' +
					o.content +
				'</div>' +
				'<div class="btm">' +
					'<button class="vk submit mr10 ' + o.color + (o.butSubmit ? '' : ' dn') + '">' + o.butSubmit + '</button>' +
					'<button class="vk cancel' + (o.butCancel ? '' : ' dn') + '">' + o.butCancel + '</button>' +
				'</div>' +
			'</div>',

			dialog = $('body').append(html).find('._dialog:last'),
			DBACK = dialog.prev(),
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
			w2 = Math.round(width / 2), // ширина/2. Для определения положения по центру
			vkScroll = VK_SCROLL > 110 ? VK_SCROLL - 110 : 0;//корректировка скролла VK

		dialog.find('.close').click(dialogClose);
		butSubmit.click(submitFunc);
		butCancel.click(function() {
//			e.stopPropagation();
//			dialogClose();
			if(butCancel.hasClass('_busy'))
				return;
			o.cancel();
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

		$('._hint').remove();
		DBACK.css({
				'z-index':ZINDEX + 3,
				height:$(document).height()
			 })
			 .click(dialogClose);

		dialog.css({
			width:width + 'px',
			top:$(window).scrollTop() + vkScroll + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		ZINDEX += 10;

		_fbhs();

		function dialogClose() {
			DBACK.remove();
			dialog.remove();
			ZINDEX -= 10;
			if(o.dialog_id)
				delete DIALOG[o.dialog_id];
			_fbhs();
		}
		function dialogErr(msg) {
			butSubmit._hint({
				msg:msg,
				color:'red',
				pad:10,
				show:1
			});
		}

		var DLG = {
			id:o.dialog_id,
			D:function(attr) {//получение значений по аттрибутам конкретно из этого диалога
				return content.find(attr);
			},
			close:dialogClose,
			hide:function() {
				DBACK.hide();
				dialog.hide();
			},
			show:function() {
				DBACK.show();
				dialog.show();
			},
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
				top:20,
				color:'orange',
				width:o.width,
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
			DIALOG_WIDTH = o.width,
			DLG = function(attr) {//получение элемента данного диалога по атрибуту (для устранения конфликта с другими диалогами)
				return dialog.content.find(attr);
			};

		_blockUpd(o.blk);
		_elemUpd(o.cmp);

		DLG('#dialog-menu')._menu({
			type:2,
			spisok:o.menu,
			func:function() {
				_dialogHeightCorrect(DLG);
			}
		});
		DLG('#menu_sa')._menu({
			type:1,
			spisok:o.menu_sa
		});
		DLG('#width_auto')._check({
			title:'автоматическая ширина'
		});

		var ACT_NAME = {
			insert:'внесения',
			edit:'редактирования',
			del:'удаления'
		};
		_forN(['insert', 'edit', 'del'], function(act, n) {
			DLG('#' + act + '_action_id')._select({
				width:270,
				title0:'действия нет, закрыть окно',
				spisok:o.action,
				func:function(v) {
					DLG('.td-' + act + '-action-page')._dn(v == 2);
					DLG('#' + act + '_action_page_id')._select(0);
				}
			});
			DLG('#' + act + '_action_page_id')._select({
				width:270,
				title0:'не выбрана',
				spisok:PAGE_LIST
			});
			DLG('#history_' + act).click(function() {
				var t = $(this);
				_dialogLoad({
					dialog_id:67,
					dialog_source:o.dialog_id,
					unit_id:_num(t.attr('val')) || -117,
					busy_obj:$(this),
					busy_cls:'hold',
					func_open:function(res, dlg) {
						dlg.content.find('#type_id').val(n + 1);
					},
					func_save:function(res) {
						t.val(res.unit.title);
						t._flash();
					}
				});
			});
			DLG('#' + act + '_on')._check({
				func:function(v, t) {
					t.parent().parent().next()['slide' + (v ? 'Down' : 'Up')]();
				}
			});
			DLG('#' + act + '_on_check').mouseenter(function() {
				$(this)._hint({
					pad:10,
					msg:'Включение ' + ACT_NAME[act] + ' записи',
					side:'left',
					show:1,
					delayShow:1500
				});
			});
		});

		DLG('#table_1')._select({
			width:170,
			title0:'не выбрана',
			spisok:o.tables
		});
		DLG('#table_2')._select({
			width:170,
			title0:'не выбрана',
			spisok:o.tables,
			func:function(v) {
				$('#td-bt2c')._dn(v);
				DLG('#table_2_field')._select(0);
				if(v)
					DLG('#table_2_field')._select('spisok', o.tablesFields[v]);
			}
		});
		DLG('#table_2_field')._select({
			width:170,
			title0:'колонка для связки',
			spisok:o.tablesFields[DLG('#table_2').val()]
		});
		DLG('#element_group_id')._select({
			title0:'нет',
			width:230,
			spisok:o.group
		});
		DLG('#element_width')._count({width:60,step:10});
		DLG('#element_width_min')._count({width:60,step:10});
		DLG('#element_type')._select({
			title0:'не указан',
			width:100,
			spisok:o.col_type
		});
		DLG('#element_dialog_func')._select({
			width:280,
			title0:'не указан',
			spisok:o.dialog_spisok
		});
		DLG('#dialog_parent_id')._select({
			width:250,
			title0:'нет',
			spisok:o.dialog_parent
		});

		_dialogHeightCorrect(DLG);

		//установка линии для настройки ширины диалога
		DLG('#dialog-w-change')
			.css('left', (DIALOG_WIDTH + 8) + 'px')
			.draggable({
				axis:'x',
				grid:[10,0],
				drag:function(event, ui) {
					DLG('#width_auto')._check(0);
					var w = ui.position.left - 8;
					if(w < 480 || w > 980)
						return false;
					DIALOG_WIDTH = w;
					dialog.width(w);
					DLG('#dialog-width').html(w);
				}
			});

		function submit() {
			var send = {
				op:'dialog_save',

				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				unit_id:o.unit_id,
				block_id:o.block_id,

				name:DLG('#dialog_name').val(),

				width:DIALOG_WIDTH,
				width_auto:DLG('#width_auto').val(),
				cmp_no_req:DLG('#cmp_no_req').val(),

				insert_on:DLG('#insert_on').val(),
				insert_head:DLG('#insert_head').val(),
				insert_button_submit:DLG('#insert_button_submit').val(),
				insert_button_cancel:DLG('#insert_button_cancel').val(),
				insert_action_id:DLG('#insert_action_id').val(),
				insert_action_page_id:DLG('#insert_action_page_id').val(),

				edit_on:DLG('#edit_on').val(),
				edit_head:DLG('#edit_head').val(),
				edit_button_submit:DLG('#edit_button_submit').val(),
				edit_button_cancel:DLG('#edit_button_cancel').val(),
				edit_action_id:DLG('#edit_action_id').val(),
				edit_action_page_id:DLG('#edit_action_page_id').val(),

				del_on:DLG('#del_on').val(),
				del_head:DLG('#del_head').val(),
				del_button_submit:DLG('#del_button_submit').val(),
				del_button_cancel:DLG('#del_button_cancel').val(),
				del_action_id:DLG('#del_action_id').val(),
				del_action_page_id:DLG('#del_action_page_id').val(),

				dialog_parent_id:DLG('#dialog_parent_id').val(),
				spisok_on:DLG('#spisok_on').val(),

				table_1:DLG('#table_1').val(),
				table_2:DLG('#table_2').val(),
				table_2_field:DLG('#table_2_field')._select('inp'),
				app_any:DLG('#app_any').val(),
				sa:DLG('#sa').val(),

				element_group_id:DLG('#element_group_id').val(),
				element_width:DLG('#element_width').val(),
				element_width_min:DLG('#element_width_min').val(),
				element_type:DLG('#element_type').val(),
				element_search_access:DLG('#element_search_access').val(),
				element_is_insert:DLG('#element_is_insert').val(),
				element_style_access:DLG('#element_style_access').val(),
				element_url_access:DLG('#element_url_access').val(),
				element_hint_access:DLG('#element_hint_access').val(),
				element_dialog_func:DLG('#element_dialog_func').val(),
				element_afics:DLG('#element_afics').val(),
				element_page_paste:DLG('#element_page_paste').val(),
				element_dialog_paste:DLG('#element_dialog_paste').val(),
				element_spisok_paste:DLG('#element_spisok_paste').val(),
				element_is_spisok_unit:DLG('#element_is_spisok_unit').val(),
				element_44_access:DLG('#element_44_access').val(),
				element_td_paste:DLG('#element_td_paste').val(),
				element_hidden:DLG('#element_hidden').val(),

				menu_edit_last:DLG('#dialog-menu').val()
			};
			dialog.post(send, _dialogOpen);
		}
	},
	_dialogHeightCorrect = function(DLG) {//установка высоты линий для настройки ширины диалога и ширины полей с названиями
		var h = DLG('#dialog-w-change').parent().height();
		DLG('#dialog-w-change').height(h);
	},

	_dialogOpen = function(o) {//открытие диалогового окна
		var dialog = _dialog({
			dialog_id:o.dialog_id,
			block_id:o.block_id,  //для передачи значений, если будет требоваться редактирование диалога
			unit_id:o.unit_id,    //id также для передачи

			top:20,
			width:o.width,
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
			DIALOG_OPEN.col_type = o.col_type;
			_blockUpd(o.blk);
			_elemUpd(o.cmp);
			_elemActivate(o.cmp, o.unit);
		}

		return dialog;

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
						case 12://подключаемая функция
							if(!window[sp.txt_1])
								return;
							send.cmpv[id] = window[sp.txt_1](sp, 'get');
							return;
						case 19://наполнение для некоторых компонентов
							send.cmpv[id] = _cmpV19(sp, 1);
							return;
						case 30://Настройка ТАБЛИЧНОГО содержания списка
							send.cmpv[id] = _cmpV30(sp, 'get');
							break;
						case 37://SA: Select - выбор имени колонки
							send.cmp[id] = $(sp.attr_cmp)._select('inp');
							return;
						case 49://Настройка содержания Сборного текста
							send.cmpv[id] = _cmpV49(sp, 'get');
							break;
						case 56://Настройка суммы значений единицы списка
							send.cmpv[id] = _cmpV56(sp, 'get');
							break;
						case 58://наполнение для некоторых компонентов
							send.cmpv[id] = _cmpV58(sp, 1);
							return;
					}
					send.cmp[id] = $(sp.attr_cmp).val();
				});

			dialog.post(send, function(res) {
				//закрытие диалога 50 - выбор элемента, если вызов был из него
				if(o.d50close)
					o.d50close();

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
	_elemActivate = function(elem, unit) {//активирование элементов
		var attr_focus = false;//элемент, на который будет поставлен фокус

		_forIn(elem, function(el) {
			if(el.focus)
				attr_focus = el.attr_cmp;

			if(el.hint_on) {
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
						msg:_br(el.hint_msg, 1),
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
					if(el.func.length) {
						_elemFunc(el, _num(unit[el.col] || 0), 1);
						$(el.attr_cmp)._check({
							func:function(v) {
								_elemFunc(el, v);
							}
						});
					}
					return;
				//textarea
				case 5:	$(el.attr_cmp).autosize(); return;
				//select - выбор страницы
				case 6:
					_elemFunc(el, _num(unit[el.col]), 1);
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:PAGE_LIST,
						func:function(v) {
							_elemFunc(el, v);
						}
					});
					return;
				//search
				case 7:
					var timer,
						started = 0,
						v_last;
					$(el.attr_cmp)._search({
						func:function(v) {
							if(started)
								return;
							if(timer)
								clearInterval(timer);
							if(v_last == v)
								return;
							timer = setInterval(function() {
								started = 1;
								v_last = v;
								if(!FILTER[el.num_1])
									FILTER[el.num_1] = {};
								FILTER[el.num_1][el.id] = v;
								_spisokUpdate(el.num_1, function() {
									started = 0;
									clearInterval(timer);
									timer = 0;
								});
							}, 700);
						}
					});
					return;
				//Функция
				case 12:
					if(!window[el.txt_1])
						return;
					window[el.txt_1](el, unit);
					return;
				//Выбор элемента из диалога или страницы
				case 13:
					var P = $(el.attr_cmp).next(),
						inp = P.find('.inp'),
						del = P.find('.icon-del'),
						err = function(msg) {
							P._hint({
								msg:msg,
								color:'red',
								pad:10,
								show:1
							});
						},
						choosed = function(bec) {//выделение выбранного значения
							_forEq(bec, function(sp) {
								if(sp.attr('val') == $(el.attr_cmp).val()) {
									sp.addClass('sel');
									return false;
								}
							});
						};
					P.click(function() {
						switch(el.num_1) {
							case 2119://страница
								alert('страница');
								return;
							case 2120://диалог
								switch(el.num_2) {
									case 2123: alert('конкретный диалог'); return;
									case 2124://элемент для поиска диалога
										var dlg_id = 0;
										if(el.num_3) {//выбор по указанному значению
											var dlg = $('#cmp_' + el.num_3);
											if(!dlg.length) {
												err('Отсутствует элемент со списком диалогов.');
												return;
											}
											dlg_id = _num(dlg.val());
										} else {
											var block_id = unit.source.block_id;
											if(!block_id) {
												err('Отсутствует исходный блок.');
												return;
											}
											var elem_id = BLKK[block_id].elem_id;
											if(!elem_id) {
												err('Отсутствует исходный элемент.');
												return;
											}
											dlg_id = ELMM[elem_id].ds;
										}
										if(!dlg_id) {
											err('Не выбран диалог');
											return;
										}
										_dialogLoad({
											dialog_id:74,
											dialog_source:dlg_id,
											busy_obj:inp,
											busy_cls:'hold',
											func_open:function(res, dlg) {
												var bec = dlg.D('.choose');
												choosed(bec);
												bec.click(function() {
													var t = $(this),
														id = t.attr('val');
													if(el.num_5 && ELMM[id].issp) {
														_dialogLoad({
															dialog_id:74,
															dialog_source:ELMM[id].num_1,
															busy_obj:t,
															func_open:function(res, dlg2) {
																var bec = dlg2.D('.choose');
																choosed(bec);
																bec.click(function() {
																	var t = $(this),
																		id2 = t.attr('val');
																	$(el.attr_cmp).val(id + ',' + id2);
																	inp.val(ELMM[id].name + ' » ' + ELMM[id2].name);
																	del._dn(1);
																	dlg.close();
																	dlg2.close();
																	P._flash();
																});
															}
														});
														return;
													}
													$(el.attr_cmp).val(id);
													inp.val(ELMM[id].name);
													del._dn(1);
													dlg.close();
													P._flash();
												});
											}
										});
								}
						}
					});
					del.click(function(e) {
						e.stopPropagation();
						$(el.attr_cmp).val(0);
						inp.val('');
						del._dn();
					});
					return;
				//select - произвольные значения
				case 17:
					_elemFunc(el, _num(unit[el.col] || el.def), 1);
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv,
						func:function(v) {
							_elemFunc(el, v);
						}
					});
					return;
				//dropdown
				case 18:
					$(el.attr_cmp)._dropdown({
						title0:el.txt_1,
						spisok:el.vvv
					});
					return;
				//наполнение для некоторых компонентов
				case 19: _cmpV19(el); return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Список действий, привязанных к элементу
				case 22:
					$(el.attr_el).find('DL')._sort({table:'_element_func'});
					return;
				//Список - ТАБЛИЦА
				case 23:
					if(!el.num_6)
						return;

					$(el.attr_el).find('ol:first').nestedSortable({
						forcePlaceholderSize:true,//сохранять размер места, откуда был взят элемент
						placeholder:'nested-placeholder', //класс, применяемый для подсветки места, откуда взялся элемент
						listType:'ol',
						items:'li',
						isTree:el.num_7 > 1,
						maxLevels:el.num_7,
						tabSize:30, //расстояние, на которое надо сместить элемент, чтобы он перешёл на другой уровень
						revert:200, //плавное возвращение (полёт) элемента на своё место. Цифра - скорость в миллисекундах.

						start:function(e, t) {//установка ширины placeholder
							var w = $(t.item).find('._stab:first').width();
							$(t.placeholder).width(w);
						},
						update:function(e, t) {
//							var pos = t.item.parent().attr('id');
//							t.item.find('a')._dn(!pos, 'b fs14');

							var send = {
								op:'spisok_23_sort',
								elem_id:el.id,
								arr:$(this).nestedSortable('toArray'),
								busy_obj:$(el.attr_el),
								busy_cls:'spisok-busy'
							};
							_post(send);
						},

						expandedClass:'pb10',//раскрытый список
						errorClass:el.num_7 > 1 ? 'bg-fcc' : ''  //ошибка, если попытка переместить элемент на недоступный уровень
					});

					return;
				//select - выбор списка (все списки приложения)
				case 24:
					_elemFunc(el, _num(unit[el.col] || el.def), 1);
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv,
						func:function(v) {
							_elemFunc(el, v);
						}
					});
					return;
				//настройка шаблона единицы списка
				case 25:
					$('#block-level-spisok')
						.find('.block-grid-on')
						.removeClass('grey')
						.trigger('click');
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Содержание диалога для выбора значения
				case 26:
					if(!window.DIALOG_OPEN)
						return;
					var DLG = DIALOG_OPEN,
						D = function(attr) {
							return DLG.content.find(attr);
						},
						bec = D('.choose'),
						dlg_id =  _num(D('.dlg26').val());
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
						D(el.attr_cmp).val(ids.join(','));

						if(el.num_3)
							return;

						//выбор подзначения из вложенного списка
						var id = _num(D(el.attr_cmp).val()),
							elm = window['ELM' + dlg_id][id];
						if(elm.dialog_id != 29 && elm.dialog_id != 59)
							return;

						_dialogLoad({
							dialog_id:11,
							dialog_source:elm.num_1,
							func_open:function(res, dlg) {
								dlg.submit(function() {
									var sel = dlg.content.find('.choose.sel').attr('val');
									if(!sel)
										return;
									id = id + ',' + sel;
									D(el.attr_cmp).val(id);
									dlg.close();
								});
							}
						});
					});
					return;
				//select - выбор единицы из другого списка (для связки)
				case 29:
					var o = {
						width:el.width,
						title0:el.txt_1,
						write:el.num_1 && el.num_3,
						msg_empty:'Не найдено',
						spisok:el.vvv,
						blocked:el.num_4,
						funcWrite:function(v, t) {
							var send = {
								op:'spisok_29_connect',
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
				case 30: _cmpV30(el, unit); return;
				//Выбор значений для содержания Select
				case 31:
					var sv = $(el.attr_el).find('.sv'),
						ex = $(el.attr_cmp).val().split(','),
						v = [];

					v.push(_num(ex[0]));
					if(el.num_2 && _num(ex[1]))
						v.push(_num(ex[1]));
					$(el.attr_cmp).val(v.join(','));

					sv.click(function() {
						var t = $(this),
							n = _num(t.attr('val')),
							attr_cmp = ELM[el.num_1].attr_cmp;
						_dialogLoad({
							dialog_id:11,
							block_id:el.id * -1,
							dialog_source:$(attr_cmp).val(),
							unit_id:v[n],
							busy_obj:t,
							busy_cls:'hold',
							func_save:function(ia) {
								v[n] = ia.unit.id;
								t.val(ia.unit.title);
								$(el.attr_cmp).val(v.join(','));
							}
						});
					});
					return;
				//count - количество
				case 35:
					$(el.attr_cmp)._count({
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
						width:el.width,
						title0:'не выбрано',
						msg_empty:'колонок нет',
						spisok:el.vvv
					});
					_forN(el.vvv, function(u) {
						if(unit.col == u.title) {
							$(el.attr_cmp)._select(u.id);
							return false;
						}
						if(unit.col)
							return;
						if(u.busy)
							return;
						if(u.title.split('_')[0] == DIALOG_OPEN.col_type) {
							$(el.attr_cmp)._select(u.id);
							return false;
						}
					});
					return;
				//SA: Select - выбор диалогового окна
				case 38:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						msg_empty:'диалоги ещё не были созданы',
						spisok:el.vvv
					});
					return;
				//SA: Select - дублирование
				case 41:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv
					});
					return;
				//Иконка вопрос: Выплывающая подсказка
				case 42:
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
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Наполнение для некоторых компонентов
				case 49: _cmpV49(el, unit); return;
				//Календарь
				case 51:
					$(el.attr_cmp)._calendar({
						lost:el.num_1,
						time:el.num_2
					});
					return;
				//Заметки
				case 52:
					if(!$(el.attr_el).length)
						return;
					var timer = 0,
						NOTE = $(el.attr_el).find('._note'),
						ex = NOTE.attr('val').split(':'),
						page_id = _num(ex[0]),
						obj_id = _num(ex[1]),
						NOTE_TXT = NOTE.find('._note-txt'),
						NOTE_AREA = NOTE_TXT.find('textarea'),
						NOTE_TXT_W = NOTE_TXT.width(),
						noteAfterPrint = function() {
							NOTE.find('textarea').autosize();
							NOTE.find('.comment-ok').click(function() {//внесение комментария
								var t = $(this),
									comm = t.parents('._note-comment'),
									note = t.parents('._note-u'),
									area = comm.find('textarea'),
									txt = $.trim(area.val());
								if(!txt) {
									area.focus();
									return;
								}
								var send = {
									op:'note_comment_add',
									note_id:note.attr('val'),
									txt:txt,
									busy_cls:'busy',
									busy_obj:comm
								};
								_post(send, function(res) {
									_parent(t, 'TABLE').before(res.html);
									area.val('');
									area.trigger('autosize');
								});
							});
							NOTE.find('._note-to-cmnt').click(function() {//раскрытие комментариев
								var t = $(this);
								t.next().show();
								t.parents('._note-u').find('textarea').focus();
								t.remove();
							});
							NOTE.find('.note-del').click(function() {//удаление заметки
								var t = $(this),
									note = t.parents('._note-u'),
									send = {
										op:'note_del',
										note_id:note.attr('val'),
										busy_cls:'spin',
										busy_obj:t
									};
								_post(send, function() {
									note.addClass('deleted');
								});
							});
							NOTE.find('.note-rest').click(function() {//восстановление заметки
								var t = $(this),
									note = t.parents('._note-u'),
									send = {
										op:'note_rest',
										note_id:note.attr('val'),
										busy_obj:t
									};
								_post(send, function() {
									note.removeClass('deleted');
								});
							});
						};
					NOTE_AREA.keyup(function() {
						var v = $.trim(NOTE_AREA.val());
						if(timer)
							clearInterval(timer);
						timer = setInterval(function() {
							NOTE_TXT
								.stop()
								.animate({width:NOTE_TXT_W - (v.length ? 33 : 0)}, 150);
							clearInterval(timer);
							timer = 0;
						}, 300);
					});
					NOTE.find('.note-ok').click(function() {
						var txt = $.trim(NOTE_AREA.val());
						if(!txt)
							return;
						var send = {
							op:'note_add',
							page_id:page_id,
							obj_id:obj_id,
							txt:txt,
							busy_cls:'busy',
							busy_obj:NOTE
						};
						_post(send, function(res) {
							NOTE_AREA.val('').trigger('autosize');
							NOTE_TXT.width(NOTE_TXT_W);
							NOTE.find('._note-list').html(res.html);
							noteAfterPrint();
						});
					});
					noteAfterPrint();
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка суммы значений единицы списка (для [27])
				case 56: _cmpV56(el, unit); return;
				//Меню переключения блоков
				case 57:
					var type = {
							1158:2,
							1159:1
						},
						toggle = function(id) {
						_forN(el.vvv, function(sp) {
							_forN(_elemFuncBlockObj(_idsAss(sp.blk)), function(oo) {
								if(!oo.obj.length)
									return;
								oo.obj[sp.id == id ? 'show' : 'hide']();
//								oo.obj[sp.id == id ? 'slideDown' : 'slideUp'](300);
							});
						});
					};
					toggle(el.def);
					$(el.attr_cmp)._menu({
						type:type[el.num_1],
						spisok:el.vvv,
						func:toggle
					});
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Настройка пунктов меню переключения блоков (для [57])
				case 58: _cmpV58(el); return;
				//Связка списка при помощи кнопки
				case 59:
					var but = $(el.attr_cmp + el.afics),
						div = but.next(),
						unitSel = function(id) {//действие после выбора значения
							var send = {
								op:'spisok_59_unit',
								cmp_id:el.id,
								unit_id:id,
								busy_obj:but
							};
							_post(send, function(res) {
								div.find('.un-html').html(res.html);
								div._dn(1);
								but._dn();
							});
						};
					but.click(function() {
						_dialogLoad({
							block_id:el.block_id,
							dialog_id:el.num_4,
							busy_obj:but,
							func_open:function(res, dlg) {
								dlg.content.click(function(e) {
									var un = $(e.target).parents('.sp-unit');
									if(!un.length)
										return;

									var id = _num(un.attr('val'));
									if(!id)
										return;

									$(el.attr_cmp).val(id);
									dlg.close();
									unitSel(id);
								});
							}
						});
					});
					//отмена выбора
					div.find('.icon').click(function() {
						but._dn(1);
						div._dn();
						$(el.attr_cmp).val(0);
					});
					return;
				//Загрузка изображений
				case 60:
					var AEL = $(el.attr_el),
						load = AEL.find('._image-load'),
						prc = AEL.find('._image-prc'), //div для отображения процентов
						ids_upd = function() {//обновление id загруженных изображений
							var ids = [];
							_forEq(AEL.find('dd.curM'), function(sp) {
								ids.push(sp.attr('val'));
							});
							$(el.attr_cmp).val(ids.join(','));

							//установка действия для удаления изображения
							AEL.find('.icon-off').off('click');
							AEL.find('.icon-off').on('click', function(e) {
								e.stopPropagation();
								var dd = $(this).parent();
								$(this).remove();
								dd.animate({width:0}, 300, function() {
									dd.remove();
									ids_upd();
								});
							});
						},
						xhr_upload = function(file) {//отправка выбранного файла или скрина на сервер
						    var xhr = new XMLHttpRequest();

						    (xhr.upload || xhr).addEventListener('progress', function(e) {
						        var done = e.position || e.loaded,
						            total = e.totalSize || e.total,
						            itog = Math.round(done/total*100);

						        if(itog == 100) {
									load.removeClass('progress');
									return;
						        }

						        prc.html(itog + '%');
						        load.addClass('progress');
						    });
						    xhr.addEventListener('load', function() {
						        load.removeClass('busy');
						        var res = JSON.parse(xhr.responseText);
								if(!res.success) {
									load._hint({
										msg:res.text,
										pad:10,
										color:'red',
										show:1
									});
									return;
								}
								load.parent().before(res.html);
								ids_upd();
						    });
						    xhr.open('post', AJAX, true);

						    var data = new FormData;
						    data.append('f1', file);
						    data.append('op', 'image_upload');
						    data.append('obj_name', 'elem_' + el.id + '_' + USER_ID);
						    data.append('obj_id', _num(unit.id));
						    xhr.send(data);
						};

					//Загрузка изображения из файла
					ids_upd();
					AEL.find('dl').sortable({
						items:'.curM',
						placeholder:'ui-hold',
						update:ids_upd
					});
					AEL.find('.tab-load td').mouseenter(function() {
						var t = $(this),
							msg = 'Выбрать картинку из файлов.' +
								  '<br>' +
								  'Размер не менее 100х100 пикс.' +
								  '<br>' +
								  'Размер файла не более 15 мб.';
						if(t.hasClass('ii2'))
							msg = 'Указать ссылку на изображение';
						if(t.hasClass('ii3'))
							msg = 'Сделать фото с вебкамеры';
						if(t.hasClass('ii4')) {
							if(t.hasClass('empty'))
								msg = 'Удалённых изображений нет';
							else {
								var c = _num(t.attr('val'));
								msg = 'Удалено ' + c + ' изображени' + _end(c, ['е', 'я', 'й']) + '.' +
									  '<br>' +
									  'Нажмите для просмотра.';
							}
						}
						t._hint({
							msg:msg,
							pad:10,
							show:1,
							delayShow:1000
						});
					});
					AEL.find('form input').change(function() {
						load.addClass('busy');
						xhr_upload(this.files[0]);
					});

					//Загрузка изображения по ссылке
					var linkDiv = AEL.find('._image-link'), //поле с ссылкой на изображение
						linkInp = linkDiv.find('input'),
						iconOk = linkDiv.find('.icon-ok'),
						linkOkFunc = function() {
							var send = {
								op:'image_link',
								obj_name:'elem_' + el.id + '_' + USER_ID,
								obj_id:_num(unit.id),
								url:$.trim(linkInp.val()),
								busy_obj:iconOk,
								busy_cls:'spin'
							};
							if(!send.url.length) {
								linkInp.focus();
								return;
							}
							_post(send, function(res) {
								linkInp.val('');
								load.parent().before(res.html);
								ids_upd();
								linkInp.focus();
							});
						};
					AEL.find('.ii2').click(function() {
						load.addClass('dis');
						linkDiv.slideDown(200);
						linkInp.val('').focus();
					});
					linkDiv.find('.icon-del').click(function() {
						load.removeClass('dis');
						linkDiv.slideUp(200);
					});
					linkInp._enter(linkOkFunc);
					iconOk.click(linkOkFunc);

					//загрузка скриншота
					linkInp[0].addEventListener('paste', function(e) {
						if(!e.clipboardData)
							return;

						var blob;
						_forN(e.clipboardData.items, function(sp) {
							if(sp.type.substr(0, 5) == 'image') {
								blob = sp.getAsFile();
								return false;
							}
						});

						if(!blob)
							return;

						load.removeClass('dis');
						load.addClass('busy');
						linkDiv.slideUp(200);
						xhr_upload(blob);
					});

					//изображение с Веб-камеры
					var b64ToUint6 = function(nChr) {//convert base64 encoded character to 6-bit integer
							return nChr > 64 && nChr < 91 ? nChr - 65
								 : nChr > 96 && nChr < 123 ? nChr - 71
								 : nChr > 47 && nChr < 58 ? nChr + 4
								 : nChr === 43 ? 62 : nChr === 47 ? 63 : 0;
						},
						base64DecToArr = function(sBase64, nBlocksSize) {// convert base64 encoded string to Uintarray
							var sB64Enc = sBase64.replace(/[^A-Za-z0-9\+\/]/g, ""),
								nInLen = sB64Enc.length,
								nOutLen = nBlocksSize ? Math.ceil((nInLen * 3 + 1 >> 2) / nBlocksSize) * nBlocksSize : nInLen * 3 + 1 >> 2,
								taBytes = new Uint8Array(nOutLen);

							for(var nMod3, nMod4, nUint24 = 0, nOutIdx = 0, nInIdx = 0; nInIdx < nInLen; nInIdx++) {
								nMod4 = nInIdx & 3;
								nUint24 |= b64ToUint6(sB64Enc.charCodeAt(nInIdx)) << 18 - 6 * nMod4;
								if(nMod4 === 3 || nInLen - nInIdx === 1) {
									for(nMod3 = 0; nMod3 < 3 && nOutIdx < nOutLen; nMod3++, nOutIdx++) {
										taBytes[nOutIdx] = nUint24 >>> (16 >>> nMod3 & 24) & 255;
									}
									nUint24 = 0;
								}
							}
							return taBytes;
						};
					AEL.find('.ii3').click(function() {
						_dialogLoad({
							dialog_id:61,
							busy_obj:load,
							busy_cls:'busy',
							func_open:function(res, dlg) {
								var webcam = dlg.content.find('embed')[0];
								dlg.submit(function() {
									var foto = base64DecToArr(webcam._snap()),
										blob = new Blob([foto], {type:'image/jpeg'});
									xhr_upload(blob);
									load.addClass('busy');
									dlg.close();
								});
							}
						});
					});

					//Удалённые изображения
					AEL.find('.ii4').click(function() {
						var t = $(this);
						if(t.hasClass('empty'))
							return;

						_dialogLoad({
							dialog_id:63,
							block_id:el.id * -1,
							unit_id:_num(unit.id),
							busy_obj:load,
							busy_cls:'busy',
							func_open:function(res, dlg) {
								dlg.content.find('.icon-recover').click(function() {
									var t = $(this),
										send = {
											op:'image_recover',
											id:t.attr('val'),
											busy_obj:t,
											busy_cls:'spin'
										};
									_post(send, function(res) {
										t.parent().remove();
										load.parent().before(res.html);
										ids_upd();
									});
								});
							}
						});
					});
					return;
				//Фильтр-галочка
				case 62:
					$(el.attr_cmp)._check({
						func:function(v) {
							_elemFunc(el, v);
							FILTER[el.num_2][el.id] = v;
							_spisokUpdate(el.num_2);
						}
					});
					return;
				//Выбор цвета текста
				case 66:
					var func = function(v) {
							$(el.attr_cmp).val(v);
						},
						html = _color($(el.attr_cmp).val(), func);
					$(el.attr_cmp).next().remove('._color');
					$(el.attr_cmp).after(html);
					return;
				//Выбор цвета фона
				case 70:
					$(el.attr_cmp).next()._hint({
						msg:el.vvv,
						pad:3,
						side:'right',
						func:function(h) {
							var div = h.find('._color-bg-choose div');
							div.click(function() {
								var t = $(this),
									c = t.attr('val');
								div.removeClass('sel');
								t.addClass('sel');
								$(el.attr_cmp)
									.val(c)
									.next().css('background-color', c);
							});
						}
					});
					return;
				//Фильтр-календарь
				case 77:
					var CAL = $(el.attr_el).find('._filter-calendar'),
						CNT = CAL.find('.fc-cnt');
					CAL.find('.laquo').click(function() {
						var send = {
							op:'filter_calendar_mon_change',
							elem_id:el.id,
							mon:CAL.find('.mon-cur').val(),
							side:$(this).attr('val'),
							busy_cls:'busy',
							busy_obj:CAL
						};
						_post(send, function(res) {
							CAL.find('.mon-cur').val(res.mon);
							CAL.find('.td-mon').html(res.td_mon);
							CNT.html(res.cnt);
						});
					});
					CNT.click(function(e) {
						var t = $(e.target),
							on = t.hasClass('on'),
							week = t.hasClass('week-num'),
							td = on ? t : t.parent();
						if(on || week) {
							CNT.find('.sel').removeClass('sel');
							td.addClass('sel');
							if(!FILTER[el.num_1])
								FILTER[el.num_1] = {};
							FILTER[el.num_1][el.id] = t.attr('val');
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Фильтр-меню
				case 78:
					var FM = $(el.attr_el).find('.fm-unit');
					$(el.attr_el).find('.fm-plus').click(function() {
						var t = $(this),
							plus = t.html() == '+',
							div = _parent(t, 'TABLE').next();
						div['slide' + (plus ? 'Down' : 'Up')](200);
						t.html(plus ? '-' : '+');
					});
					FM.click(function() {
						var t = $(this),
							sel = t.hasClass('sel');
						FM.removeClass('sel');
						if(!sel)
							t.addClass('sel');
							if(!FILTER[el.num_1])
								FILTER[el.num_1] = {};
							FILTER[el.num_1][el.id] = sel ? 0 : t.attr('val')
							_spisokUpdate(el.num_1);
					});
					return;
				//Очистка фильтра
				case 80:
					$(el.attr_cmp).click(function() {
						var t = $(this),
							send = {
								op:'spisok_filter_clear',
								spisok_id:el.num_1,
								busy_obj:t
							};
						_post(send, function(res) {
							_forIn(res.def, function(sp) {
								switch(sp.dialog_id) {
									//быстрый поиск
									case 7:  $(sp.attr_cmp)._search('clear'); break;
									//фильтр-галочка
									case 62: $(sp.attr_cmp)._check(0); break;
									//фильтр-календарь
									case 77:
										var CAL = $(sp.attr_el).find('._filter-calendar');
										CAL.find('.mon-cur').val(sp.dop.mon);
										CAL.find('.td-mon').html(sp.dop.td_mon);
										CAL.find('.fc-cnt').html(sp.dop.cnt);
										break;
									//фильтр-меню
									case 78: $(sp.attr_el).find('.sel').removeClass('sel'); break;
									//фильтр-select
									case 83: $(sp.attr_cmp)._select(0); break;
								}
							});
							$(res.count_attr).html(res.count_html);
							$(res.spisok_attr).html(res.spisok_html);
							t._dn();
							FILTER = res.filter;
						});
					});
					return;
				//Select - фильтр
				case 83:
					if(!FILTER[el.num_1])
						FILTER[el.num_1] = {};
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv,
						func:function(v) {
							FILTER[el.num_1][el.id] = v;
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Select - выбор значения списка
				case 85:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
//						spisok:el.vvv,
						msg_empty:'Не указан список',
						func:function(v) {
//							_elemFunc(el, v);
						}
					});
					return;
			}
		});

		if(attr_focus)
			$(attr_focus).focus();
	},
	_cmpV19 = function(o, get) {//наполнение для некоторых компонентов. dialog_id=19
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
	_cmpV30 = function(o, unit) {//Настройка ТАБЛИЧНОГО содержания списка. dialog_id=30
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
		BUT_ADD.click(function() {
			valueAdd();
		});

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id элемента
				dialog_id:50,//id диалога, через который был вставлен этот элемент
				attr_el:'#inp_' + NUM,
				attr_bl:'#inp_' + NUM,
				attr_tr:'#tr_' + NUM,
				width:150,  //ширина колонки
				tr:'',      //имя колонки txt_7
				title:'',   //имя значения
				font:'',
				color:'',
				pos:'',      //txt_8
				url_access:1,//колонке разрешено быть ссылкой
				url:0        //колонка является ссылкой
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
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
									' />' +
								'</div>' +
							'<td class="w50 r top pt5">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('Удалить колонку', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var INP = $(v.attr_el),
				DD = DL.find('dd:last');
			valueResize(v);
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:unit.source.block_id,  //блок, в котором размещена таблица
					unit_id:v.id || -112,           //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						cmpUpdate();
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.title);
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
				var id = _num(sp.attr('val'));
				if(!id)
					return;
				val.push(id);
			});
			cmp.val(val);
		}
	},
	_cmpV49 = function(o, unit) {//Настройка содержания Сборного текста [44]
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(unit == 'get') {
			var send = {};
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.attr('val'));
				if(!id)
					return;
				send[id] = {
					spc:sp.find('.spc').val()
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
				dialog_id:50,
				title:'',
				spc:1     //пробел справа
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
									  ' value="' + _br(v.title) + '"' +
								' />' +
							'<td class="w25">' +
								'<input type="hidden" class="spc" value="' + v.spc + '" />' +
							'<td class="w50 r">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('Удалить элемент', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:unit.source.block_id,
					unit_id:v.id || -111,           //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						cmpUpdate();
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(_br(ia.unit.title));
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
	_cmpV56 = function(o, unit) {//Настройка суммы значений единицы списка
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(unit == 'get') {
			var send = {};
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.attr('val'));
				if(!id)
					return;
				send[id] = {
					minus:sp.find('button').hasClass('green') ? 0 : 1
				};
			});
			return send;
		}

		var cmp = $(o.attr_cmp),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last');

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id элемента
				minus:0,    //минусовое значение
				title:''
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 pl5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w25 r">' +
								'<button class="vk short ' + (v.minus ? 'red' : 'green') + ' w35">' + (v.minus ? '—' : '+') + '</button>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="значение не выбрано"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w50 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить значение', -54) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:11,
					block_id:unit.source.block_id,
					unit_id:v.id || -113,      //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						cmpUpdate();
						v.id = ia.unit.id;
						INP.val(ia.unit.title);
					}
				});
			});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y',
				stop:cmpUpdate
			});
			DD.find('button')
				._tooltip('Прибавление', -33)
				.click(function() {
					var t = $(this),
						plus = t.hasClass('green');
					t.html(plus ? '—' : '+');
					t._dn(plus, 'green');
					t._dn(!plus, 'red');
					t._tooltip(plus ? 'Вычитание' : 'Прибавление', plus ? -26 : -33);
				});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
				cmpUpdate();
				v.id = 0;
			});
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
	_cmpV58 = function(o, get) {//Настройка пунктов меню переключения блоков
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(get) {
			var send = [];
			_forEq(el.find('dd'), function(sp) {
				send.push({
					id:sp.attr('val'),
					title:sp.find('.pk-title').val(),
					blk:sp.find('.pk-block').attr('val'),
					def:sp.find('.def').val()
				});
			});
			return send;
		}

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Новый пункт меню</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1,
			BCS = $('.block-choose-submit');//кнопка сохранения выбора блоков

		//отмена выбора блоков на странице
		$('.block-choose-cancel').click(dlgShow);

		BUT_ADD.click(valueAdd);

		if(!o.vvv.length)
			valueAdd();

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,                  //id элемента
				num:NUM,
				title:'Имя пункта ' + NUM++, //имя пункта меню
				blk:'',
				blk_title:'',
				def:0
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '" data-num="' + v.num + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w35 pl5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w15">' +
								'<input type="hidden" class="def" value="' + v.def + '" />' +
							'<td><input type="text"' +
									  ' class="pk-title w100p"' +
									  ' placeholder="имя не указано"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w125">' +
								'<input type="text"' +
									  ' class="pk-block w100p curP color-ref over1"' +
									  ' readonly' +
									  ' placeholder="выбрать блоки"' +
									  ' value="' + v.blk_title + '"' +
									  ' val="' + v.blk + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить пункт', -44) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				NAME = DD.find('.pk-title'),
				BLOCK = DD.find('.pk-block');
			NAME.focus();
			BLOCK.click(function() {
				var deny = [];
				_forEq(el.find('dd'), function(sp) {
					var num = _num(sp.attr('data-num')),
						blk = sp.find('.pk-block').attr('val');
					if(num == v.num)
						return;
					if(!blk)
						return;
					deny.push(blk);
				});
				var spl = BCS.parent().parent().attr('val').split(':'),
					send = {
						op:'block_choose_page',
						obj_name:spl[0],
						obj_id:spl[1],
						width:spl[2],
						sel:BLOCK.attr('val'),
						deny:_idsAss(deny.join(',')),
						busy_obj:BLOCK,
						busy_cls:'hold'
					};
				_post(send, function(res) {
					$('#_content').html(res.html);
					$('.block-grid-on').hide();
					$('.block-level-change').hide();
					$('.elem-width-change').hide();
					BCS.parent().show();
					DIALOG_OPEN.hide();
					var bec = $('#_content').find('.choose');
					bec.click(function() {
						var t = $(this),
							sel = t.hasClass('sel');
						t._dn(sel, 'sel');
					});
					BCS.click(function() {
						var ids = [];
						_forEq(bec, function(el) {
							if(el.hasClass('sel'))
								ids.push(_num(el.attr('val')));
						});
						BLOCK.attr('val', ids.join(','));
						BLOCK.val(ids.length ? ids.length + ' блок' + _end(ids.length, ['', 'а', 'ов']) : '');
						dlgShow();
					});
				});
			});
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
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
		}
		function dlgShow() {
			$('.block-grid-on')
				.show()
				.removeClass('grey')
				.trigger('click');
			$('.block-level-change').show();
			$('.elem-width-change').show();
			BCS.parent().hide();
			DIALOG_OPEN.show();
		}
	},
	_elemFunc = function(el, v, is_open) {//применение функций, привязанных к элементам
		/*
			is_open - окно открылось, эффектов нет, только применение функций
		*/

		if(!el.func)
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
					_forN(_elemFuncBlockObj(sp.target), function(oo) {
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
				//установка/снятие значений
				case 73://фильтр-галочка[62]
					var is_set = 0;//по умолчанию: сбросить значение

					//ДЕЙСТВИЕ
					switch(sp.action_id) {
						//сбросить значение
						case 1718:
						default: break;
						//установить значение
						case 1719:
							is_set = 1;
							break;
					}

					//УСЛОВИЕ
					switch(sp.cond_id) {
						case 1715://галочка снята
							if(v && sp.action_reverse) {
								is_set = is_set ? 0 : 1;
								break;
							}
							if(v)
								return;
							break;
						case 1716://галочка установлена
							if(!v && sp.action_reverse) {
								is_set = is_set ? 0 : 1;
								break;
							}
							if(!v)
								return;
							break;
						default: return;
					}

					_forIn(sp.target, function(tar, elem_id) {
						var EL = ELM[elem_id];
						//свои способы действия на каждый элемент
						switch(EL.dialog_id) {
							case 1: //галочка
							case 62://фильтр-галочка
								$(EL.attr_cmp)._check(is_set);
								FILTER[el.num_2][EL.id] = is_set;
								break;
						}
					});
			}
		});
	},
	_elemFuncBlockObj = function(blk_ass) {//получение $(obj) блоков
		var arr = [],
			TRG = _copyObj(blk_ass);

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

	_elemGroup = function(v, dlg) {//функция, которая выполняется после открытия окна выбора элемента
		var D = dlg.D;
		D('.el-group-head').click(function() {//переключение меню
			var t = $(this),
				id = t.attr('val');
			D('.el-group-head').removeClass('sel');
			t.addClass('sel');

			D('#elem-group .cnt')._dn(0);
			D('#cnt_' + id)._dn(1);
		});
		D('.elem-unit').click(function() {//открытие диалога
			var t = $(this);
			v.dialog_id = t.attr('val');
			_dialogLoad(v);
		});

		if(!SA)
			return;
		D('#elem-group .icon-edit').click(function(e) {//редактирование диалога
			e.stopPropagation();
			var t = $(this),
				send = {
					op:'dialog_edit_load',
					dialog_id:t.parents('.elem-unit').attr('val'),
					busy_obj:t,
					busy_cls:'spin'
				};
			_post(send, _dialogEdit);
		});
		_forEq(D('#elem-group .cnt'), function(sp) {
			sp._sort({table:'_dialog'});
		});
	},
	_dialogLoad = function(o) {//загрузка диалога
		/*
			o.func_open - функция, выполняемая после открытия диалога
			o.func_save - функция, выполняемая после успешного выполнения диалога (после нажатия кнопки submit)
		*/
		var send = {
			op:'dialog_open_load',
			page_id:PAGE_ID,

			dialog_id:_num(o.dialog_id),        //диалог, который вносит элемент
			dialog_source:_num(o.dialog_source),//исходный диалог, либо настраиваемый
			block_id:_num(o.block_id, 1),       //блок (или отрицательный id: элемент-группировка), в который вставляется элемент
			unit_id:_num(o.unit_id, 1),         //id единицы списка (элемент или функция)

			del:_num(o.del),                    //удаление элемента

			busy_obj:o.busy_obj,
			busy_cls:o.busy_cls
		};

		_post(send, function(res) {
			if(res.dialog_id == 44) {
				if(o.d50close)
					o.d50close();
			} else
				res.d50close = o.d50close;
			//функция, выполняемая после успешной вставки элемента
			res.func = o.func_save;
			var dialog = _dialogOpen(res);
			if(res.dialog_id == 50) {
				send.func_save = o.func_save;
				send.d50close = function() {
					dialog.close();
				};
				_elemGroup(send, dialog);
			}
			if(o.func_open)
				o.func_open(res, dialog);
		});
	},

	_noteCDel = function(t, id) {//удаление комментария
		var send = {
			op:'note_comment_del',
			note_id:id,
			busy_cls:'spin',
			busy_obj:$(t)
		};
		_post(send, function() {
			$(t).parents('._comment-u').addClass('deleted');
		});
	},
	_noteCRest = function(t, id) {//восстановление комментария
		var send = {
			op:'note_comment_rest',
			note_id:id,
			busy_obj:$(t)
		};
		_post(send, function() {
			$(t).parents('._comment-u').removeClass('deleted');
		});

	},

	_pageUserAccess = function(el, i) {
		if(i == 'get') {
			var user_id = _num($('#access-user-id').val()),
				send = {},
				ids = [];

			if(!user_id)
				return '';

			send.user_id = user_id;

			_forEq($(el.attr_el).find('._check'), function(sp) {
				var ch = sp.prev(),
					id = _num(ch.attr('id').split('_')[1]),
					v = _num(ch.val());
				if(v)
					ids.push(id);
			});
			send.ids = ids.join(',');
			return send;
		}

		_forEq($(el.attr_el).find('._check'), function(sp) {
			var prev = sp.prev();
			prev._check({
				func:function(v) {
					prev.parents('table').next()[v ? 'slideDown' : 'slideUp'](200);
				}
			});
		});
	},
	_pageUserAccessAll = function(el, i) {//настройка входа для всех сотрудников
		if(i == 'get') {
			var send = [];
			_forEq($(el.attr_el).find('._check'), function(sp) {
				var ch = sp.prev(),
					id = _num(ch.attr('id').split('_')[1]),
					v = _num(ch.val());
				if(v)
					send.push(id);
			});
			return send.join(',');
		}
	},
	_imageShow = function() {//просмотр изображений. Подключается функцией [12]
		var IMS = $('#_image-show'),
			IU = IMS.find('.iu'),
			IMAIN = $('#_image-main'),
			imNext = function(next_id) {//установка следующего изображения
				var im = IMG_ASS[next_id];
				IU.removeClass('sel');
				IMAIN.html('<img src="' + im.src + '" width="' + im.x + '" height="' + im.y + '" />');
				IMAIN.attr('val', next_id);
				_forEq(IU, function(sp) {
					var id = _num(sp.attr('val'));
					if(id == next_id) {
						sp.addClass('sel');
						return false;
					}
				});
			};
		IMAIN.click(function() {//нажатие на основное изображение
			if(!IMG_IDS.length) {//если изображение всего одно, то закрытие диалога
				DIALOG[65].close();
				return;
			}
			var sel_id = _num(IMAIN.attr('val'));
			_forN(IMG_IDS, function(id, n) {
				if(id == sel_id) {
					if(++n == IMG_IDS.length)
						n = 0;
					imNext(IMG_IDS[n]);
					return false;
				}
			});
		});
		IU.click(function() {//нажатие на дополнительные изображения
			var t = $(this),
				id = _num(t.attr('val'));
			imNext(id);
		});
	},
	_filterCheckSetup = function(o, i) {//настройка условий фильтра для галочки (подключение через [12])
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(i == 'get') {
			var send = {};
			_forEq(el.find('dd'), function(sp) {
				var id = _num(sp.find('.title').attr('val'));
				if(!id)
					return;
				send[id] = {
					num_8:sp.find('.cond_id').val(),
					txt_8:sp.find('.cond_val').val()
				};
			});
			return send;
		}
		
		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить условие</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last');

		BUT_ADD.click(valueAdd);

		if(!o.vvv.length)
			valueAdd();
		else {
			$('#cmp_1443')._select('disable');
			_forIn(o.vvv, valueAdd);
		}

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id элемента из диалога, по которому будет выполняться условие фильтра
				title:'',   //имя элемента
				num_8:0,  //id условия из выпадающего списка [num_8]
				txt_8:''  //значеие условия                  [txt_8]
			}, v);

			DL.append(
				'<dd class="over3">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 r color-sal">Если:' +
							'<td><input type="text"' +
									  ' readonly' +
									  ' class="title w150 curP over4"' +
									  ' placeholder="выберите значение..."' +
									  ' value="' + v.title + '"' +
									  ' val="' + v.id + '"' +
								' />' +
							'<td><input type="hidden" class="cond_id" value="' + v.num_8 + '" />' +
							'<td class="w100p">' +
								'<input type="text"' +
									  ' class="cond_val w125' + _dn(v.num_8 > 2) + '"' +
									  ' value="' + v.txt_8 + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить условие', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				COND_ID = DD.find('.cond_id'),
				TITLE = DD.find('.title');
			TITLE.click(function() {
				_dialogLoad({
					dialog_id:11,
					block_id:_num($('#cmp_1443').val()) * -1,
					unit_id:v.id || -114,           //id выбранного элемента (при редактировании)
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						COND_ID._select('enable');
						$('#cmp_1443')._select('disable');
						if(!v.id)
							COND_ID._select(1);
						v.id = res.unit.id;
						TITLE.val(res.unit.title);
						TITLE.attr('val', v.id);
					}
				});
			});
			COND_ID._select({//условие
				width:150,
				disabled:!v.id,
				spisok:[
					{id:1,title:'отсутствует'},
					{id:2,title:'присутствует'},
					{id:3,title:'равно'},
					{id:4,title:'не равно'},
					{id:5,title:'больше'},
					{id:6,title:'больше или равно'},
					{id:7,title:'меньше'},
					{id:8,title:'меньше или равно'},
					{id:9,title:'содержит'},
					{id:10,title:'не содержит'}
				],
				func:function(v) {
					DD.find('.cond_val')
						._dn(v > 2)
						.focus();
				}
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
		}
	},
	_historySetup = function(o, i) {//настройка шаблона истории действий (подключение через [12])
		var el = $(o.attr_el);

		//получение данных для сохранения
		if(i == 'get') {
			var send = [];
			_forEq(el.find('dd'), function(sp) {
				send.push({
					id:_num(sp.attr('val')),
					num_1:sp.find('.title').attr('val'),
					txt_7:sp.find('.txt_7').val(),
					txt_8:sp.find('.txt_8').val()
				});
			});
			return {
				type_id:$(o.attr_cmp).next().val(),
				dialog_id:$(o.attr_cmp).val(),
				val:send
			};
		}

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить сборку</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last');

		$(o.attr_cmp).val(i.source.dialog_source);

		BUT_ADD.click(valueAdd);

		if(!o.vvv.length)
			valueAdd();
		else {
			$('#cmp_1443')._select('disable');
			_forIn(o.vvv, valueAdd);
		}

		DL.sortable({
			axis:'y',
			handle:'.icon-move-y'
		});

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента-сборки
				dialog_id:50,  //id диалога, вносившего элемента-значения
				num_1:0,  //id элемента-значения
				title:'', //имя элемента-значения
				txt_7:'', //текст слева
				txt_8:''  //текст справа
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w35 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w200">' +
								'<input type="text"' +
									  ' class="txt_7 w100p"' +
									  ' placeholder="текст слева"' +
									  ' value="' + v.txt_7 + '"' +
								' />' +
							'<td class="w150">' +
								'<input type="text"' +
									  ' readonly' +
									  ' class="title w100p curP over4"' +
									  ' placeholder="значение из диалога"' +
									  ' value="' + v.title + '"' +
									  ' val="' + v.num_1 + '"' +
								' />' +
							'<td class="w200">' +
								'<input type="text"' +
									  ' class="txt_8 w100p"' +
									  ' placeholder="текст справа"' +
									  ' value="' + v.txt_8 + '"' +
								' />' +
							'<td class="r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить сборку', -48) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				TITLE = DD.find('.title');
			TITLE.click(function() {
				if(!v.dialog_id)
					v.dialog_id = 50;
				_dialogLoad({
					dialog_id:v.dialog_id,
					dialog_source:i.source.dialog_source,
					unit_id:v.dialog_id != 50 ? v.id || -115 : -115,
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						v.id = res.unit.id;
						DD.attr('val', v.id);
						TITLE.attr('val', res.unit.num_1);
						TITLE.val(v.id);
						DD.find('.txt_8').focus();
					}
				});
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
			DD.find('.txt_7').focus();
		}
	};

$(document)
	.on('click', '.dialog-open', function() {//нажатие на кнопку, иконку для открытия диалога
		var t = $(this),
			val = t.attr('val'),
			send = {
				busy_obj:t,
				busy_cls:t.hasClass('icon') ? 'spin' : '_busy'
			};

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1], 1);
		});

		_dialogLoad(send);
	})
	.on('click', '.image-open', function() {//открытие изображения при нажатии на миниатюру
		var t = $(this),
			id = t.attr('val');
		_dialogLoad({
			dialog_id:65,
			unit_id:id,
			busy_obj:t.parent()
		});
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



