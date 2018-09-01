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
			//функция, которая выполняется при отмене или закрытии диалога
			closeFunc = function() {},
			w2 = Math.round(width / 2), // ширина/2. Для определения положения по центру
			vkScroll = VK_SCROLL > 110 ? VK_SCROLL - 110 : 0;//корректировка скролла VK

		dialog.find('.close').click(dialogClose);
		butSubmit.click(submitFunc);
		butCancel.click(function() {
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
			closeFunc();
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
			submit:function(func) {//изменение функции сохранения данных диалога
				o.submit = func;
			},
			go:function() {//нажатие на кнопку сохранения данных диалога
				butSubmit.trigger('click');
			},
			closeFunc:function(func) {
				closeFunc = func;
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
			DLG = dialog.D;

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
		_forN(['insert', 'edit', 'del'], function(act) {
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
				t.find('div')._dn(0, 'vh');
				_dialogLoad({
					dialog_id:67,
					dialog_source:o.dialog_id,
					unit_id:-1,
					prm:{
						act:act
					},
					busy_obj:t,
					func_open:function() {
						t.find('div')._dn(1, 'vh');
					},
					func_save:function(res) {
						t.find('.pale')._dn(!res.tmp);
						t.find('.msg').html(res.tmp);
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
					delayShow:750
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
			spisok:o.group,
			func:function(v) {
				DLG('.elememt-setup')['slide' + (v ? 'Down' : 'Up')]();
			}
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
		DLG('#spisok_on')._check({
			func:function(v) {
				DLG('.tr-spisok-col')._dn(v);
			}
		});
		DLG('#spisok_elem_id')._select({
			width:250,
			title0:'не указана',
			spisok:o.spisok_cmp
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
				spisok_elem_id:DLG('#spisok_elem_id').val(),

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
				element_hidden:DLG('#element_hidden').val(),
				element_is_spisok_unit:DLG('#element_is_spisok_unit').val(),

				element_paste_page:  DLG('#element_paste_page').val(),
				element_paste_dialog:DLG('#element_paste_dialog').val(),
				element_paste_spisok:DLG('#element_paste_spisok').val(),
				element_paste_td:    DLG('#element_paste_td').val(),
				element_paste_44:    DLG('#element_paste_44').val(),

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
			for(var i in o.vvv)
				VVV[i] = o.vvv[i];
			_ELM_ACT(o.elm_ids, o.unit);
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
				prm:o.unit.source.prm,
				cmp:{},
				vvv:{}
			};

			if(o.unit_id) {
				send.op = 'spisok_save';
				if(o.act == 'del')
					send.op = 'spisok_del';
			}

			//получение значений компонентов
			if(o.act != 'del')
				_forN(o.elm_ids, function(id) {
					var sp = ELMM[id],
						ATR_CMP = _attr_cmp(id);

					switch(sp.dialog_id) {
						case 12://подключаемая функция
							if(window[sp.txt_1])
								send.vvv[id] = window[sp.txt_1](sp, 'get');
							if(ATR_CMP)
								send.cmp[id] = ATR_CMP.val();
							return;
						case 37://SA: Select - выбор имени колонки
							send.cmp[id] = ATR_CMP._select('inp');
							return;
					}

					if(ATR_CMP)
						send.cmp[id] = ATR_CMP.val();
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
						var bln = '#block-level-' + res.obj_name;
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

				//обновление значения JS-кеша, если элемент вносился или изменялся
				if(res.elem_js) {
					ELMM[res.unit.id] = res.elem_js;
					if(res.unit.block_id > 0)
						BLKK[res.unit.block_id].elem_id = res.unit.id;
				}

				//обновление значения JS-кеша, если элемент удалён
				if(res.elem_del) {
					var el = ELMM[o.unit.id];
					BLKK[el.block_id].elem_id = 0;
					delete ELMM[o.unit.id];
				}
			});
		}
	},

	ATTR_EL = function(id) {
		return '#el_' + id;
	},
	_attr_el = function(id) {//аттрибут элемента
		var send = $(ATTR_EL(id));

		if(!send.length)
			return false;

		return send;
	},
	ATTR_CMP = function(id) {
		return '#cmp_' + id;
	},
	_attr_cmp = function(id, afics) {//аттрибут компонента
		var el = ELMM[id],
			_afics = afics && el.afics ? el.afics : '',
			send = $(ATTR_CMP(id) + _afics);

		if(!send.length)
			return false;

		return send;
	},
	ATTR_BL = function(id) {
		return '#bl_' + id;
	},
	_attr_bl = function(id) {//аттрибут блока
		var send = $(ATTR_BL(id));

		if(!send.length)
			return false;

		return send;
	},

	_ELM_ACT = function(elm_ids, unit) {//активирование элементов
		var attr_focus = false;//элемент, на который будет поставлен фокус

		_forN(elm_ids, function(elm_id) {
			var el = ELMM[elm_id];

			if(!el)
				alert('несуществующий элемент ' + elem_id);

			var ATR_CMP = _attr_cmp(elm_id),
				ATTR_CMP_AFICS = _attr_cmp(elm_id, 1),
				ATTR_EL =  _attr_el(elm_id);

			el.id = elm_id;

			if(el.focus)
				attr_focus = ATR_CMP;

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
				ATTR_EL.mouseenter(function() {
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
					ATTR_EL._hint(oo);
				});
			}

			switch(el.dialog_id) {
				case 1://галочка
					if(el.func) {
						_elemFunc(el, _num(unit[el.col] || 0), 1);
						ATR_CMP._check({
							func:function(v) {
								_elemFunc(el, v);
							}
						});
					}
					return;
				//textarea
				case 5:	ATR_CMP.autosize(); return;
				//select - выбор страницы
				case 6:
					_elemFunc(el, _num(unit[el.col]), 1);
					var spisok = _copySel(PAGE_LIST);

					//если выбирается страница для ссылки, то добавляется вариант: 3 => Автоматически
					if(elm_id == 1959)
						spisok.unshift({
							id:3,
							title:'Автоматически',
							content:'<b class="color-pay">Автоматически</b>'
						});

					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:spisok,
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
					ATR_CMP._search({
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
								FILTER[el.num_1][elm_id] = v;
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
					var P = ATR_CMP.next(),
						inp = P.find('.inp'),
						sel = ATR_CMP.val(),
						del = P.find('.icon-del'),
						D = DIALOG_OPEN.D;

					P.click(function() {
						_dialogLoad({
							dialog_id:11,
							block_id:el.block_id,
							prm:{
								src:unit.source,
								num_3:_num(D(ATTR_CMP(el.num_3)).val()),
								nest:_num(el.num_5),//выбор значений во вложенных списках
								sev:_num(el.num_6), //выбор нескольких значений
								sel:sel
							},
							busy_obj:inp,
							busy_cls:'hold',
							func_open:function(res, dlg) {
								dlg.D(ATTR_CMP(res.elm_ids[0])).val(sel);
							},
							func_save:function(res) {
								sel = res.v;
								ATR_CMP.val(sel);
								inp.val(res.title);
								del._dn(1);
							}
						});
					});
					del.click(function(e) {
						sel = 0;
						e.stopPropagation();
						ATR_CMP.val(0);
						inp.val('');
						del._dn();
					});
					return;
				//select - произвольные значения
				case 17:
					_elemFunc(el, _num(unit[el.col] || el.def), 1);
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:VVV[el.id],
						func:function(v) {
							_elemFunc(el, v);
						}
					});
					return;
				//dropdown
				case 18:
					_elemFunc(el, _num(unit[el.col] || el.def), 1);
					ATR_CMP._dropdown({
						title0:el.txt_1,
						spisok:VVV[el.id],
						func:function(v) {
							_elemFunc(el, v);
						}
					});
					return;
				//ВСПОМОГАТЕЛЬНЫЙ ЭЛЕМЕНТ: Список действий, привязанных к элементу
				case 22:
					ATTR_EL.find('DL')._sort({table:'_element_func'});
					return;
				//Список - ТАБЛИЦА
				case 23:
					if(!el.num_6)
						return;

					ATTR_EL.find('ol:first').nestedSortable({
						forcePlaceholderSize:true,//сохранять размер места, откуда был взят элемент
						placeholder:'nested-placeholder', //класс, применяемый для подсветки места, откуда взялся элемент
						listType:'ol',
						items:'li',
						handle:'.icon-move',
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
								elem_id:elm_id,
								arr:$(this).nestedSortable('toArray'),
								busy_obj:ATTR_EL,
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
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:VVV[el.id],
						func:function(v) {
							_elemFunc(el, v);
						}
					});
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
							elm = window['ELM_OLD' + dlg_id][id];
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
						spisok:VVV[el.id],
						blocked:el.num_4,
						funcWrite:function(v, t) {
							var send = {
								op:'spisok_29_connect',
								cmp_id:elm_id,
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
					ATR_CMP._select(o);
					return;
				//count - количество
				case 35:
					ATR_CMP._count({
						width:el.width,
						min:el.num_1,
						max:el.num_2,
						step:el.num_3,
						minus:el.num_4
					});
					return;
				//SA: Select - выбор имени колонки
				case 37:
					ATR_CMP._select({
						width:el.width,
						title0:'не выбрано',
						msg_empty:'колонок нет',
						spisok:VVV[el.id]
					});
					_forN(VVV[el.id], function(u) {
						if(unit.col == u.title) {
							ATR_CMP._select(u.id);
							return false;
						}
						if(unit.col)
							return;
						if(u.busy)
							return;
						if(u.title.split('_')[0] == DIALOG_OPEN.col_type) {
							ATR_CMP._select(u.id);
							return false;
						}
					});
					return;
				//SA: Select - выбор диалогового окна
				case 38:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						msg_empty:'диалоги ещё не были созданы',
						spisok:VVV[el.id]
					});
					return;
				//SA: Select - дублирование
				case 41:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:VVV[el.id]
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
					ATR_CMP.mouseenter(function() {
						ATR_CMP._hint({
							msg:_br(el.txt_1, 1),
							pad:10,
							side:side[el.num_1],
							show:1
						});
					});
					return;
				//Календарь
				case 51:
					ATR_CMP._calendar({
						lost:_num(el.num_1),
						time:el.num_2
					});
					return;
				//Заметки
				case 52:
					if(!ATTR_EL.length)
						return;
					var timer = 0,
						NOTE = ATTR_EL.find('._note'),
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
				//Меню переключения блоков
				case 57:
					var type = {
							1158:2,
							1159:1
						},
						toggle = function(id) {
							_forN(VVV[el.id], function(sp) {
								_forN(_elemFuncBlockObj(_idsAss(sp.blk)), function(oo) {
									if(!oo.obj.length)
										return;
									oo.obj[sp.id == id ? 'show' : 'hide']();
								});
							});
						};
					toggle(el.def);
					ATR_CMP._menu({
						type:type[el.num_1],
						spisok:VVV[el.id],
						func:toggle
					});
					return;
				//Связка списка при помощи кнопки
				case 59:
					var div = ATTR_CMP_AFICS.next(),
						unitSel = function(id) {//действие после выбора значения
							var send = {
								op:'spisok_59_unit',
								cmp_id:elm_id,
								unit_id:id,
								busy_obj:ATTR_CMP_AFICS
							};
							_post(send, function(res) {
								div.find('.un-html').html(res.html);
								div._dn(1);
								ATTR_CMP_AFICS._dn();
							});
						};
					//нажатие на кнопку для открытыя диалога
					ATTR_CMP_AFICS.click(function() {
						_dialogLoad({
							block_id:el.block_id,
							dialog_id:el.num_4,
							busy_obj:ATTR_CMP_AFICS,
							func_open:function(res, dlg) {
								//выбор значения списка
								dlg.content.click(function(e) {
									var un = $(e.target).parents('.sp-unit');
									if(!un.length)
										return;

									var id = _num(un.attr('val'));
									if(!id)
										return;

									ATR_CMP.val(id);
									dlg.close();
									unitSel(id);
								});
							}
						});
					});
					//отмена выбора
					div.find('.icon').click(function() {
						ATTR_CMP_AFICS._dn(1);
						div._dn();
						ATR_CMP.val(0);
					});
					return;
				//Загрузка изображений
				case 60:
					var AEL = ATTR_EL,
						load = AEL.find('._image-load'),
						prc = AEL.find('._image-prc'), //div для отображения процентов
						ids_upd = function() {//обновление id загруженных изображений
							var ids = [];
							_forEq(AEL.find('dd.curM'), function(sp) {
								ids.push(sp.attr('val'));
							});
							ATR_CMP.val(ids.join(','));

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
						    data.append('obj_name', 'elem_' + elm_id + '_' + USER_ID);
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
								obj_name:'elem_' + elm_id + '_' + USER_ID,
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
							block_id:elm_id * -1,
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
					ATR_CMP._check({
						func:function(v) {
							_elemFunc(el, v);
							FILTER[el.num_2][elm_id] = v;
							_spisokUpdate(el.num_2);
						}
					});
					return;
				//Выбор цвета текста
				case 66:
					var func = function(v) {
							ATR_CMP.val(v);
						},
						html = _color(ATR_CMP.val(), func);
					ATR_CMP.next().remove('._color');
					ATR_CMP.after(html);
					return;
				//Выбор цвета фона
				case 70:
					ATR_CMP.next()._hint({
						msg:VVV[el.id],
						pad:3,
						side:'right',
						func:function(h) {
							var div = h.find('._color-bg-choose div');
							div.click(function() {
								var t = $(this),
									c = t.attr('val');
								div.removeClass('sel');
								t.addClass('sel');
								ATR_CMP
									.val(c)
									.next().css('background-color', c);
							});
						}
					});
					return;
				//Фильтр-календарь
				case 77:
					var CAL = ATTR_EL.find('._filter-calendar'),
						CNT = CAL.find('.fc-cnt');
					CAL.find('.laquo').click(function() {
						var send = {
							op:'filter_calendar_mon_change',
							elem_id:elm_id,
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
							FILTER[el.num_1][elm_id] = t.attr('val');
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Фильтр-меню
				case 78:
					var FM = ATTR_EL.find('.fm-unit');
					ATTR_EL.find('.fm-plus').click(function() {
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
							FILTER[el.num_1][elm_id] = sel ? 0 : t.attr('val');
							_spisokUpdate(el.num_1);
					});
					return;
				//Очистка фильтра
				case 80:
					ATR_CMP.click(function() {
						var t = $(this),
							send = {
								op:'spisok_filter_clear',
								spisok_id:el.num_1,
								busy_obj:t
							};
						_post(send, function(res) {
							//скрытие кнопки
							t._dn();

							//обновление количества
							if(res.count_id)
								_attr_el(res.count_id).html(res.count_html);

							_attr_el(res.spisok_id).html(res.spisok_html);

							_forIn(res.def, function(sp) {
								switch(sp.dialog_id) {
									//быстрый поиск
									case 7:  _attr_cmp(sp.elem_id)._search('clear'); break;
									//фильтр-галочка
									case 62: _attr_cmp(sp.elem_id)._check(0); break;
									//фильтр-календарь
									case 77:
										var CAL = _attr_el(sp.elem_id).find('._filter-calendar');
										CAL.find('.mon-cur').val(sp.dop.mon);
										CAL.find('.td-mon').html(sp.dop.td_mon);
										CAL.find('.fc-cnt').html(sp.dop.cnt);
										break;
									//фильтр-меню
									case 78: _attr_el(sp.elem_id).find('.sel').removeClass('sel'); break;
									//фильтр-select
									case 83: _attr_cmp(sp.elem_id)._select(0); break;
								}
							});
							FILTER = res.filter;
						});
					});
					return;
				//Select - фильтр
				case 83:
					if(!FILTER[el.num_1])
						FILTER[el.num_1] = {};
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
//						spisok:VVV[el.id],
						func:function(v) {
							FILTER[el.num_1][elm_id] = v;
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Select - выбор значения списка
				case 85:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:VVV[el.id],
						msg_empty:el.num_1 ? 'Список пуст' : 'Не указан список',
						func:function(v) {
//							_elemFunc(el, v);
						}
					});
					return;
			}
		});

		if(attr_focus)
			attr_focus.focus();
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
						var EL = ELMM[elem_id];
						//свои способы действия на каждый элемент
						switch(EL.dialog_id) {
							case 1: //галочка
							case 62://фильтр-галочка
								_attr_cmp(elem_id)._check(is_set);
								FILTER[el.num_2][EL.id] = is_set;
								break;
						}
					});
			}
		});
	},
	_elemFuncBlockObj = function(blk_ass) {//получение $(obj) блоков
		var arr = [],
			TRG = _copyObj(blk_ass),
			D = $;

//		if(window.DIALOG_OPEN)
//			D = DIALOG_OPEN.D;

		_forIn(TRG, function(n, block_id) {
			if(!n)
				return;

			var BL = BLKK[block_id],
				ATR_BL = D(ATTR_BL(block_id));

			if(BL.xx == 1) {//если блок в ряду один, фукнция применится ко всей таблице
				arr.push({
					obj:_parent(ATR_BL, '.bl-div'),
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
					obj:_parent(ATR_BL, '.bl-div'),
					slide:1
				});
				return;
			}

			//функция будет применена к конкретному блоку
			arr.push({
				obj:ATR_BL,
				slide:0
			});
		});

		return arr;
	},

	/* ---=== ВЫБОР ЗНАЧЕНИЯ ИЗ ДИАЛОГА [11] ===--- */
	PHP12_v_choose = function(el, unit) {
		if(unit == 'get')
			return '';

		var DLG = DIALOG_OPEN;
		if(!DLG)
			return;

		var D = DLG.D,
			VC = D(ATTR_EL(el.id)).find('.v-choose'),//элементы в открытом диалоге для выбора
			sev = unit.source.prm.sev,               //выбор нескольких значений
			nest = !sev && unit.source.prm.nest != undefined ? 1 : 0;     //выбор во вложенных списках

		//описание глобальных переменных при открытии исходного (первого, невложенного) диалога
		if(unit.source.block_id) {
			V11_CMP = D(ATTR_CMP(el.id));   //переменная в исходном диалоге для хранения значений
			V11_DLG = [];                   //массив диалогов, открывающиеся последовательно
			V11_V = sev ? _idsAss(unit.source.prm.sel) : []; //массив выбранных значений
			V11_COUNT = 0;                  //счётчик открытых диалогов
		}

		//выбор одного из элеметов
		VC.click(function() {
			var t = $(this),
				v = _num(t.attr('val'));

			if(sev) {
				var sel = !t.hasClass('sel');
				t[(sel ? 'add' : 'remove') + 'Class']('sel');
				if(sel)
					V11_V[v] = 1;
				else
					delete V11_V[v];
				var vvv = [];
				for(var k in V11_V)
					vvv.push(k);
				V11_CMP.val(vvv.join());
			} else {
				VC.removeClass('sel');
				t.addClass('sel');

				V11_V.length = V11_COUNT;
				V11_V[V11_COUNT] = v;
				V11_DLG.length = V11_COUNT;
				V11_DLG[V11_COUNT] = DLG;

				V11_CMP.val(V11_V.join());
			}

			//нажатие по обычному элементу (не список)
			if(!nest || !ELMM[v].issp)
				return;

			V11_COUNT++;

			_dialogLoad({
				dialog_id:11,
				dialog_source:ELMM[v].num_1,
				prm:unit.source.prm,
				func_open:function(res, dlg) {
					dlg.submit(function() {
						var sel = dlg.content.find('.v-choose.sel');
						if(!sel.length) {
							dlg.err('Значение не выбрано');
							return;
						}

						//проверка чтобы невозможно было выбрать элемент-список
						var sel_v = sel.attr('val');
						if(ELMM[sel_v].issp)
							dlg.err('Не выбрано конечное значение ' + sel_v);

						//закрытие всех открытых диалогов кроме последнего
						_forIn(V11_DLG, function(sp, n) {
							if(!_num(n))
								return;
							sp.close();
						});

						//запуск первого (исходного) диалога
						V11_DLG[0].go();
					});
					dlg.closeFunc(function() {
						V11_COUNT--;
					});
				}
			});
		});
	},

	/* ---=== ВЫБОР БЛОКОВ [19] ===--- */
	PHP12_block_choose = function(el, unit) {
		var DLG = DIALOG_OPEN;
		if(!DLG)
			return;

		var D = DLG.D,
			BC = D(ATTR_EL(el.id)).find('.blk-choose');//блоки в открытом диалоге для выбора

		if(unit == 'get') {
			var send = [];
			_forEq(BC, function(sp) {
				if(!sp.hasClass('sel'))
					return;
				send.push(sp.attr('val'));
			});
			return send.join();
		}

		//подсветка блока при выборе
		BC.click(function() {
			var t = $(this),
				v = t.attr('val'),
				sel = t.hasClass('sel');

			t[(sel ? 'remove' : 'add') + 'Class']('sel');
		});
	},

	/* ---=== НАСТРОЙКА МЕНЮ ПЕРЕКЛЮЧЕНИЯ БЛОКОВ ===--- */
	PHP12_menu_block_setup = function(el, unit) {//используется в диалоге [57]

		//получение данных для сохранения
		if(unit == 'get')
			return PHP12_menu_block_get(el);

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Новый пункт меню</div>',
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1,
			blkTitle = function(ids) {//текст с количеством блоков
				if(!ids)
					return '';
				var c = ids.split(',').length;
				if(c == 1 && !_num(ids))
					return '';
				return c + ' блок' + _end(c, ['', 'а', 'ов']);

			};

		BUT_ADD.click(valueAdd);

		if(!VVV[el.id].length)
			valueAdd();
		else
			_forIn(VVV[el.id], valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,                  //id элемента
				num:NUM,
				title:'Имя пункта ' + NUM++, //имя пункта меню
				blk:'',
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
									  ' value="' + blkTitle(v.blk) + '"' +
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
				_forEq(ATR_EL.find('dd'), function(sp) {
					if(_num(sp.attr('data-num')) == v.num)
						return;
					_forN(sp.find('.pk-block').attr('val').split(','), function(id) {
						deny.push(id);
					});
				});

				_dialogLoad({
					dialog_id:19,
					dialog_source:0,
					block_id:unit.source.block_id,
					prm:{
						sel:BLOCK.attr('val'),
						deny:deny
					},
					busy_obj:BLOCK,
					busy_cls:'hold',
					func_save:function(res) {
						BLOCK.attr('val', res.ids)
							 .val(blkTitle(res.ids))
							 ._flash();
					}
				});
			});

			//галочка по-умолчанию для блока
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

			//сортировка пунктов меню
			DL.sortable({axis:'y',handle:'.icon-move-y'});

			//удаление пункта меню
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
		}
	},
	PHP12_menu_block_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				id:sp.attr('val'),
				title:sp.find('.pk-title').val(),
				blk:sp.find('.pk-block').attr('val'),
				def:sp.find('.def').val()
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА ЯЧЕЕК ТАБЛИЦЫ ===--- */
	PHP12_spisok_td_setting = function(el, unit) {//настройка ячеек таблицы
		if(unit == 'get')
			return PHP12_spisok_td_get(el);

		if(!unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить колонку</div>',
			DL = _attr_el(el.id).append(html).find('dl'),
			NUM = 1;

		//кнопка добавления новой ячейки
		_attr_el(el.id).find('div:last').click(tdAdd);

		//показ-скрытие настройки TH-заголовков
		$('#cmp_531')._check({
			func:function(v) {
				unit.num_5 = v;
				DL.find('.div-th-name')['slide' + (v ? 'Down' : 'Up')]();
			}
		});

		_forIn(VVV[el.id], tdAdd);

		//добавление новой колонки в таблицу
		function tdAdd(v) {
			v = $.extend({
				attr_el:'#inp_' + NUM,//требуется для настройки стилей в выплывающем окне
				attr_bl:'#inp_' + NUM,//требуется для настройки позиции в выплывающем окне

				id:0,           //id элемента
				dialog_id:50,   //id диалога, через который был вставлен этот элемент
				name:'',        //имя значения
				width:150,      //ширина колонки
				font:'',        //выделение: b, i, u
				color:'',       //цвет текста
				url_access:1,   //отображение иконки для настройки ссылки
				url:0,          //текст в колонке является ссылкой
				txt_7:'',       //TH-заголовок колонки
				pos:''          //txt_8: позиция по горизонтали (l, center, r)
			}, v.id ? v : {});

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w80 grey r topi">Колонка ' + NUM + ':' +
							'<td><div style="width:' + v.width + 'px">' +
									'<div class="div-th-name' + _dn(unit.num_5) + '">' +
										'<input type="text"' +
											  ' class="th-name w100p bg-gr2 center fs14 blue mb1"' +
											  ' placeholder="имя колонки"' +
											  ' value="' + v.txt_7 + '"' +
										' />' +
									'</div>' +
									'<input type="text"' +
										  ' id="inp_' + NUM + '"' +
										  ' class="inp w100p curP ' + v.font + ' ' + v.color + ' ' + v.pos + '"' +
										  ' readonly' +
										  ' placeholder="значение не выбрано"' +
										  ' value="' + v.name + '"' +
									' />' +
								'</div>' +
							'<td class="w50 r top pt5">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить колонку', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			tdResize(DD);

			//открытие диалога для выбора элемента или его редактирования
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:unit.source.block_id,  //блок, в котором размещена таблица
					unit_id:v.id,                   //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.title);
						tdResize(DD);
					}
				});
			});

			//отображение выплывающего окна настройки стилей
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
								'<td class="pt3">' + _elemUnitEye(v) +
								'<td class="pt3 pl10" id="elem-pos">' + _elemUnitPlaceMiddle(v) +
						'</table>' +
						'',
					side:'right',
					show:1,
					delayShow:700,
					delayHide:300
				});
			});

			//сортировка колонок
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
			});

			//удаление элемента
			DL.find('.icon-del:last').click(function() {
				_parent($(this), 'DD').remove();
			});

			NUM++;
		}

		//включение изменения ширины, если присутствует значение
		function tdResize(dd) {
			if(!_num(dd.attr('val')))
				return;
			var res = dd.find('.div-th-name').parent();
			if(res.hasClass('ui-resizable'))
				return;
			res.resizable({
				minWidth:30,
				maxWidth:400,
				grid:10,
				handles:'e'
			});
		}
	},
	PHP12_spisok_td_get = function(el) {//сохранение ячеек таблицы
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var v = {},
				inp = sp.find('.inp');

			v.id = _num(sp.attr('val'));

			if(!v.id)
				return;

			//ширина
			v.width = _num(sp.find('.div-th-name').parent().width());

			//TH-заголовок
			v.txt_7 = sp.find('.th-name').val();

			//выделение: b, i, u
			var arr = ['b', 'i', 'u'],
				font = [];
			for(var k in arr)
				if(inp.hasClass(arr[k]))
					font.push(arr[k]);
			v.font = font.join(' ');

			//позиция txt_8
			arr = ['center', 'r'];
			v.pos = '';
			for(k in arr)
				if(inp.hasClass(arr[k]))
					v.pos = arr[k];

			//цвет текста
			v.color = '';
			for(k in ELEM_COLOR)
				if(inp.hasClass(k))
					v.color = k;

			/*
				url:sp.url
			*/
			send.push(v);
		});

		return send;
	},

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ RADIO ===--- */
	PHP12_radio_setup = function(el, unit) {//для [16]
		if(unit == 'get')
			return PHP12_radio_get(el);

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATTR_EL = _attr_el(el.id),
			DL = ATTR_EL.append(html).find('dl'),
			BUT_ADD = ATTR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		_forIn(VVV[el.id], valueAdd);

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
								'<textarea class="w100p min mtm1' + _dn(el.ds != 16) + '" placeholder="описание значения">' + v.content + '</textarea>' +
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
	PHP12_radio_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				id:_num(sp.attr('val')),
				title:sp.find('.title').val(),
				content:sp.find('textarea').val(),
				def:_num(sp.find('.def').val())
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ФИЛЬТРА RADIO ===--- */
	PHP12_filter_radio_setup = function(el, unit) {//для [74]
		if(unit == 'get')
			return PHP12_filter_radio_get(el);

		if(!unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			ATR_SP = $('#cmp_2585'),
			NUM = 1;

		ATR_SP._select('disable');
		BUT_ADD.click(valueAdd);

		if(!VVV[el.id].length)
			valueAdd();
		else
			_forIn(VVV[el.id], valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента из диалога, по которому будет выполняться условие фильтра
				title:'имя значения ' + NUM++,
				num_8:0,  //id условия из выпадающего списка [num_8]
				txt_8:''  //значеие условия                  [txt_8]
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="title w150"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить условие', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({axis:'y',handle:'.icon-move-y'});

			var DD = DL.find('dd:last'),
				TITLE = DD.find('.title');
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
		}
	},
	PHP12_filter_radio_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var id = _num(sp.attr('val'));
			if(!id)
				return;
			send.push({
				id:id,
				num_8:sp.find('.cond-id').val(),
				txt_8:sp.find('.cond-val').val()
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ФИЛЬТРА ГАЛОЧКИ ===--- */
	PHP12_filter_check_setup = function(el, unit) {//для [62]
		if(unit == 'get')
			return PHP12_filter_check_get(el);

		if(!unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			ATR_SP = $('#cmp_1443');

		ATR_SP._select('disable');
		BUT_ADD.click(valueAdd);

		if(!VVV[el.id].length)
			valueAdd();
		else
			_forIn(VVV[el.id], valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента из диалога, по которому будет выполняться условие фильтра
				title:'', //имя элемента
				num_8:0,  //id условия из выпадающего списка [num_8]
				txt_8:''  //значеие условия                  [txt_8]
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 r color-sal">Если:' +
							'<td><input type="text"' +
									  ' readonly' +
									  ' class="title w150 curP over4"' +
									  ' placeholder="выберите значение..."' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td><input type="hidden" class="cond-id" value="' + v.num_8 + '" />' +
							'<td class="w100p">' +
								'<input type="text"' +
									  ' class="cond-val w125' + _dn(v.num_8 > 2) + '"' +
									  ' value="' + v.txt_8 + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить условие', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				TITLE = DD.find('.title'),
				COND_ID = DD.find('.cond-id');
			TITLE.click(function() {
				_dialogLoad({
					dialog_id:11,
					dialog_source:ELMM[ATR_SP.val()].num_1,
					block_id:unit.source.block_id,
					unit_id:v.id,
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						COND_ID._select('enable');
						if(!v.id)
							COND_ID._select(1);
						v.id = res.unit.id;
						DD.attr('val', v.id);
						TITLE.val(res.unit.title);
					}
				});
			});
			COND_ID._select({//условие
				width:150,
				title0:'не выбрано',
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
					DD.find('.cond-val')
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
	PHP12_filter_check_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var id = _num(sp.attr('val'));
			if(!id)
				return;
			send.push({
				id:id,
				num_8:sp.find('.cond-id').val(),
				txt_8:sp.find('.cond-val').val()
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА ===--- */
	PHP12_44_setup = function(el, unit) {//для [44]
		if(unit == 'get')
			return PHP12_44_get(el);

		if(!unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить элемент</div>',
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		//вывод двух первых элементов, если начало настройки
		if(!VVV[el.id].length) {
			valueAdd();
			valueAdd({spc:0});
		} else
			_forIn(VVV[el.id], valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,           //id элемента
				dialog_id:50,   //id диалога, через который был вставлен этот элемент
				title:'',       //имя элемента
				spc:1           //пробел справа
			}, v || {});

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="элемент не выбран"' +
									  ' value="' + (v.title || v.id || '') + '"' +
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
					unit_id:v.id,           //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.title);
					}
				});
			});
			DD.find('.spc')._check({tooltip:'Пробел справа'});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
			});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
				v.id = 0;
			});
			NUM++;
		}
	},
	PHP12_44_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var id = _num(sp.attr('val'));
			if(!id)
				return;
			send.push({
				id:id,
				spc:sp.find('.spc').val()
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА БАЛАНСА - СУММ ЗНАЧЕНИЙ ЕДИНИЦЫ СПИСКА ===--- */
	PHP12_balans_setup = function(el, unit) {
		if(unit == 'get')
			return PHP12_balans_get(el);

		if(!unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last');

		BUT_ADD.click(valueAdd);

		for(var i in VVV[el.id])
			valueAdd(VVV[el.id][i])

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
				INP = DD.find('.inp'),
				DS = BLKK[unit.source.block_id].obj_id;
			INP.click(function() {
				_dialogLoad({
					dialog_id:11,
					dialog_source:DS,
					block_id:unit.source.block_id,
					unit_id:v.id,      //id выбранного элемента (при редактировании)
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.unit.id);
						v.id = ia.unit.id;
						INP.val(ia.unit.title);
					}
				});
			});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
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
				v.id = 0;
			});
		}
	},
	PHP12_balans_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var id = _num(sp.attr('val'));
			if(!id)
				return;
			send.push({
				id:id,
				minus:sp.find('button').hasClass('green') ? 0 : 1
			});
		});
		return send;
	},

	/* ---=== НАСТРЙОКА ШАБЛОНА ИСТОРИИ ДЕЙСТВИЙ ===--- */
	PHP12_history_setup = function(el, unit) {
		if(unit == 'get')
			return PHP12_history_get(el);

		var html = '<input type="hidden" class="act" value="' + unit.source.prm.act + '" />' +  //действие: insert, edit, del
				   '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить сборку</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		if(!VVV[el.id].length)
			valueAdd();
		else
			_forIn(VVV[el.id], valueAdd);

		DL.sortable({
			axis:'y',
			handle:'.icon-move-y'
		});

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента-сборки
				dialog_id:50,  //id диалога, вносившего элемента-значения
				title:'', //имя элемента-значения
				txt_7:'', //текст слева
				txt_8:'', //текст справа

				attr_el:'#inp_' + NUM,//требуется для настройки стилей в выплывающем окне
				font:'',  //выделение: b, i, u
				color:''  //цвет текста
			}, v || {});

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w35 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w250">' +
								'<input type="text"' +
									  ' class="txt_7 w100p"' +
									  ' placeholder="текст слева"' +
									  ' value="' + v.txt_7 + '"' +
								' />' +
							'<td class="w200">' +
								'<input type="text"' +
									  ' readonly' +
									  ' id="inp_' + NUM++ + '"' +
									  ' class="title w100p curP over4 ' + v.font + ' ' + v.color + '"' +
									  ' placeholder="значение из диалога"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w250">' +
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
				_dialogLoad({
					dialog_id:v.dialog_id || 50,
					dialog_source:unit.source.dialog_source,
					block_id:-1,
					unit_id:v.id,
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						v.id = res.unit.id;
						DD.attr('val', v.id);
						TITLE.val(res.unit.title);
						DD.find('.txt_8').focus();
					}
				});
			});

			//отображение выплывающего окна настройки стилей
			TITLE.mouseenter(function() {
				if(!v.id)
					return;
				if(TITLE.hasClass('hold'))
					return;
				TITLE._hint({
					msg:'<table class="bs5">' +
							'<tr><td class="pt3">' + _elemUnitFont(v) +
								'<td class="pt3">' + _elemUnitColor(v) +
								'<td class="pt3">' + _elemUnitEye(v) +
						'</table>' +
						'',
					side:'top',
					ugPos:'left',
					objPos:20,
					show:1,
					delayShow:500,
					delayHide:300
				});
			});


			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
			});
			DD.find('.txt_7').focus();
		}
	},
	PHP12_history_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			//выделение: b, i, u
			var arr = ['b', 'i', 'u'],
				font = [],
				title = sp.find('.title'),
				u = {
					id:_num(sp.attr('val')),
					txt_7:sp.find('.txt_7').val(),
					txt_8:sp.find('.txt_8').val(),
					color:''
				};

			for(var k in arr)
				if(title.hasClass(arr[k]))
					font.push(arr[k]);
			u.font = font.join(' ');

			//цвет текста
			for(k in ELEM_COLOR)
				if(title.hasClass(k)) {
					u.color = k;
					break;
				}

			send.push(u);
		});
		return {
			act:_attr_el(el.id).find('.act').val(),
			v:send
		};
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

			prm:o.prm || [],                    //дополнительные параметры

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

			_forEq(_attr_el(el.id).find('._check'), function(sp) {
				var ch = sp.prev(),
					id = _num(ch.attr('id').split('_')[1]),
					v = _num(ch.val());
				if(v)
					ids.push(id);
			});
			send.ids = ids.join(',');
			return send;
		}

		_forEq(_attr_el(el.id).find('._check'), function(sp) {
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
			_forEq(_attr_el(el.id).find('._check'), function(sp) {
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
	.on('click', '.dialog-edit', function() {//нажатие на кнопку, иконку для открытия редактирования диалога
		var t = $(this),
			val = t.attr('val'),
			send = {
				op:'dialog_edit_load',
				busy_obj:t,
				busy_cls:t.hasClass('icon') ? 'spin' : '_busy'
			};

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1], 1);
		});

		_post(send, _dialogEdit);
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



