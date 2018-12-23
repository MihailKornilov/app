/* Все элементы визуального отображения, используемые в приложении */
var DIALOG = {},    //массив диалоговых окон для управления другими элементами
	ELM_RELOAD = {},//массив элементов, ожидающих перезагрузки. В виде: [id кто перезагружает] = id кого перезагружают
					//пезагрузка происходит при помощи фукнции _elemReload

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

			dialog_id:0,//id диалога, загруженного из базы
			setup_access:0,//показ иконки настройки диалога

			color:'',   //цвет диалога: заголовка и кнопки
			head:'head: Название заголовка',
			content:'<div class="pad30 pale">content: содержимое центрального поля</div>',

			butSubmit:'Внести',
			butCancel:'Отмена',
			submit:function() {},
			cancel:dialogClose,

			send:{} //исходные данные, полученные при открытии предыдущего диалога. Для кнопки редактирования.
		}, o);

		var DIALOG_NUM = $('._dialog').length,
			editShow = _dn(!DIALOG_NUM && o.setup_access && _cookie('face') == 'site'),
			html =
			'<div class="_dialog-back"></div>' +
			'<div class="_dialog">' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh pl"></a></div>' +
		            '<div class="edit fr curP' + editShow + '"><a class="icon icon-edit wh pl"></a></div>' +
					'<div class="fs14 white">' + o.head + '</div>' +
				'</div>' +
				'<div class="content bg-fff">' +
					o.content +
				'</div>' +
				'<div class="btm">' +
					'<button class="vk submit mr10 ' + o.color + _dn(o.butSubmit) + '">' + o.butSubmit + '</button>' +
					'<button class="vk cancel' + _dn(o.butCancel) + '">' + o.butCancel + '</button>' +
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
				op:'dialog_setup_load',
				dialog_id:o.dialog_id,
				busy_obj:iconEdit.find('.icon'),
				busy_cls:'spin'
			};
			_post(send, function(res) {
				dialogClose();
				res.send = o.send;
				_dialogSetup(res);
			});
		});

		$('._hint').remove();
		DBACK.css({
				'z-index':ZINDEX + 3,
				height:$(document).height()
			 });
//			 .click(dialogClose);

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
			send:o.send,
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
				send.busy_obj = butSubmit;
				send.busy_cls = '_busy';
				send.func_err = function(res) {
					dialogErr(res.text);
					$(res.attr_cmp)
						._flash({color:'red'})
						.focus();
				};

				_post(send, function(res) {
					dialogClose();
					_msg();
					if(success == 'reload')
						location.reload();
					if(typeof success == 'function')
						success(res);
				});
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
	_dialogSetup = function(o) {//настройка диалогового окна
		var dialog = _dialog({
				dialog_id:o.dialog_id,
				top:20,
				color:'orange',
				width:o.width,
				head:'Настройка диалогового окна #' + o.dialog_id,
				content:o.html,
				butSubmit:'Сохранить диалоговое окно',
				submit:submit,
				cancel:function() {
					if(!o.send)
						return dialog.close();

					o.send.busy_obj = dialog.bottom.find('.cancel');
					o.send.busy_cls = '_busy';
					o.send.func_open_before = function() {
						dialog.close();
					};
					_dialogLoad(o.send);
				},
				send:o.send
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
				t.find('div')._vh();
				_dialogLoad({
					dialog_id:67,
					dss:o.dialog_id,
					dop:{
						act:act
					},
					busy_obj:t,
					func_open:function() {
						t.find('div')._vh(1);
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
					msg:'Разрешение ' + ACT_NAME[act] + ' записи',
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
		DLG('#element_action_dialog_id')._select({
			width:280,
			title0:'не указан',
			spisok:o.dlg_func
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
		DLG('#dialog_id_parent')._select({
			width:250,
			title0:'нет',
			spisok:o.dlg_spisok_on
		});
		DLG('#dialog_id_unit_get')._select({
			width:250,
			title0:'нет',
			spisok:o.dlg_unit_get
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
					if(w < o.width_min || w > 980)
						return false;
					DIALOG_WIDTH = w;
					dialog.width(w);
					DLG('#dialog-width').html(w);
					DLG('.dw').html(w);
				}
			});

		function submit() {
			if(o.send)
				delete o.send.op;
			var send = $.extend({
				op:'dialog_setup_save',

				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				dialog_id_parent:DLG('#dialog_id_parent').val(),
				dialog_id_unit_get:DLG('#dialog_id_unit_get').val(),

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

				spisok_on:DLG('#spisok_on').val(),
				spisok_elem_id:DLG('#spisok_elem_id').val(),

				table_1:DLG('#table_1').val(),
				app_any:DLG('#app_any').val(),
				sa:DLG('#sa').val(),
				parent_any:DLG('#parent_any').val(),

				element_group_id:DLG('#element_group_id').val(),
				element_width:DLG('#element_width').val(),
				element_width_min:DLG('#element_width_min').val(),
				element_type:DLG('#element_type').val(),
				element_afics:DLG('#element_afics').val(),
				element_hidden:DLG('#element_hidden').val(),
				element_action_dialog_id:DLG('#element_action_dialog_id').val(),

				menu_edit_last:DLG('#dialog-menu').val()
			}, o.send || {});

			//выбранные правила для элемента
			var rule = [];
			_forEq(DLG('#element-rule input'), function(sp) {
				var id = _num(sp.attr('id').split('element_rule_')[1]),
					v = _num(sp.val());
				if(!id)
					return;
				if(!v)
					return;
				rule.push(id);
			});
			send.element_rule = rule.join();

			dialog.post(send, function(res) {
				if(!o.send)
					return;
				res.send = o.send;
				_dialogOpen(res);
			});
		}
	},
	_dialogHeightCorrect = function(DLG) {//установка высоты линий для настройки ширины диалога и ширины полей с названиями
		var h = DLG('#dialog-w-change').parent().height();
		DLG('#dialog-w-change').height(h);
	},

	_dialogLoad = function(o) {//загрузка диалога
		var send = $.extend({
			op:'dialog_open_load',
			page_id:PAGE_ID,

			dialog_id:0,     //диалог, который вносит элемент
			dss:0,           //id исходного диалога, либо настраиваемого
			block_id:0,      //id блока в который вставляется элемент
			element_id:0,    //id элемента

			get_id:0,        //id записи, содержание которой будет размещаться в диалоге
			edit_id:0,       //id записи при редактировании
			del_id:0,        //id записи при удалении

			dop:'',          //дополнительные параметры для некоторых элементов

			busy_obj:null,   //объект, к которому применяется процесс ожидания
			busy_cls:'_busy',//класс, показвыающий процесс ожидания

			func_open_before:function() {},//функция, выполняемая перед открытием диалога
			func_open:function() {},//функция, выполняемая после открытия диалога
			func_save:null          //функция, применяется после успешного выполнения диалога (после нажатия кнопки submit)
		}, o);

		o = _copyObj(send);

		_post(send, function(res) {
			res.send = o;
			o.func_open_before(res, dialog);
			var dialog = _dialogOpen(res);
			o.func_open(res, dialog);
		});
	},
	_dialogOpen = function(o) {//открытие диалогового окна
		if(o.del_id)
			return _dialogOpenDel(o);

		o.send.dialog_id = o.dialog_id;

		var dialog = _dialog({
			dialog_id:o.dialog_id,
			top:20,
			width:o.width,
			setup_access:o.setup_access,
			head:o.head,
			content:o.html,
			butSubmit:o.button_submit,
			butCancel:o.button_cancel,
			submit:submit,
			send:o.send
		});

		o.dlg = dialog;
		_ELM_ACT(o);

		return dialog;

		function submit() {
			var send = {
				op:!o.edit_id ? 'spisok_add' : 'spisok_save',
				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				dss:o.srce.dss,
				block_id:o.srce.block_id,
				element_id:o.srce.element_id,
				unit_id:o.edit_id,
				cmp:{},
				vvv:{}
			};

			//получение значений компонентов
			_forIn(o.vvv, function(vvv, id) {
				var sp = ELMM[id],
					ATR_CMP = _attr_cmp(id);

				switch(sp.dialog_id) {
					case 12://подключаемая функция
						var func = sp.txt_1 + '_get';
						if(window[func])
							send.vvv[id] = window[func](sp, o);
						send.cmp[id] = ATR_CMP.val();
						return;
					case 22://Дополнительные условия к фильтру
						send.vvv[id] = PHP12_elem22_get(sp);
						return;
				}

				if(ATR_CMP)
					send.cmp[id] = ATR_CMP.val();
			});

			dialog.post(send, function(res) {
				//если присутствует функция, выполняется она. Для того, чтобы продолжить выполнение после функции, нужно чтобы она вернула любой результат
				if(o.send.func_save)
					if(!o.send.func_save(res))
						return;
//return;
				_dialogOpenSubmitAction(res);

				//обновление значения JS-кеша, если элемент вносился или изменялся
				if(res.elem_js) {
					ELMM[res.unit.id] = res.elem_js;
					if(res.unit.block_id > 0)
						BLKK[res.unit.block_id].elem_id = res.unit.id;
				}

				//присвоение id дополнительного форматирования
				if(o.dialog_id == 64)
					ELMM[res.unit.element_id].format = res.unit.id;

				//присвоение id выплвыающей подсказке
				if(o.dialog_id == 43) {
					ELMM[res.unit.element_id].hint = {
						id:res.unit.id,
						on:res.unit.on
					};
				}
			});
		}
	},
	_dialogOpenDel = function(o) {//открытие диалога удаления записи
		var dialog = _dialog({
			dialog_id:o.dialog_id,
			top:20,
			width:o.width,
			setup_access:o.setup_access,
			color:'red',
			head:o.head,
			content:o.html,
			butSubmit:o.button_submit,
			butCancel:o.button_cancel,
			submit:function() {
				var send = {
					op:'spisok_del',
					dialog_id:o.dialog_id,
					dss:o.send.dss,
					unit_id:o.del_id
				};
				dialog.post(send, _dialogOpenSubmitAction);
			},
			send:o.send
		});
		return dialog;
	},
	_dialogOpenSubmitAction = function(res) {//применение действий после выполнения диалога
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
				var id = _num(res.dss4);
				if(!id)
					break;
				if(!DIALOG[id])
					break;

				var send = DIALOG[id].send;

				send.func_open_before = function() {
					DIALOG[id].close();
				};

				_dialogLoad(send);
				break;
		}

		//обновление значения JS-кеша, если был удалён элемент
		if(res.elem_del) {
			var el = ELMM[res.elem_del];
			BLKK[el.block_id].elem_id = 0;
			delete ELMM[res.elem_del];
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

	_ELM_ACT = function(OBJ) {//активирование элементов
		if(OBJ.dlgerr)
			return;

		var unit = OBJ.unit;

		_forIn(OBJ.vvv, function(vvv, elm_id) {
			var el = ELMM[elm_id];

			if(!el) {
				alert('несуществующий элемент ' + elm_id);
				return;
			}

			el.id = elm_id;

			var ATR_CMP = _attr_cmp(elm_id),
				ATR_CMP_AFICS = _attr_cmp(elm_id, 1),
				ATR_EL =  _attr_el(elm_id);

			_elemHint(el);

			switch(el.dialog_id) {
				case 1://галочка
					if(el.func) {
						_elemAction(el, _num(ATR_CMP.val()), 1);
						ATR_CMP._check({
							func:function(v) {
								_elemAction(el, v);
							}
						});
					}
					return;
				//textarea
				case 5:	ATR_CMP._autosize(); return;
				//select - выбор страницы
				case 6:
					_elemAction(el, _num(ATR_CMP.val()), 1);
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
							_elemAction(el, v);
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
					window[el.txt_1](el, vvv, OBJ);
					return;
				//Выбор элемента из диалога или страницы
				case 13:
					var P = ATR_CMP.next(),
						INP = P.find('.inp'),
						DEL = P.find('.icon-del');

					if(INP.attr('disabled'))
						return;

					P.click(function() {
						var dlg24 = 0;
						if(el.num_1) {
							dlg24 = _num(OBJ.dlg.D(ATTR_CMP(el.num_1)).val());
							if(!dlg24) {
								_attr_cmp(el.num_1, 1)._flash({color:'red'});
								_attr_cmp(elm_id, 1)._hint({
									msg:'Не выбрано значение',
									color:'red',
									pad:10,
									show:1
								});
								return;
							}
						}

						_dialogLoad({
							dialog_id:11,
							block_id:OBJ.srce.block_id,

							dop:{
								mysave:1,
								is13:elm_id,
								nest:_num(el.num_5),            //выбор значений во вложенных списках
								sev:_num(el.num_6),             //выбор нескольких значений
								dlg24:dlg24,                    //выбранный диалог в селекте, на который указывает num_1
								sel:ATR_CMP.val(),              //выбранные элементы
								allow:el.num_2 ? el.txt_2 : ''  //id типов элементов, которые разрешено выбирать
							},

							busy_obj:INP,
							busy_cls:'hold',
							func_save:function(res) {
								ATR_CMP.val(res.v);
								INP.val(res.title);
								DEL._dn(1);
								_elemReload(el, res);
							}
						});
					});
					DEL.click(function(e) {
						e.stopPropagation();
						ATR_CMP.val(0);
						INP.val('');
						DEL._dn();
						_elemReload(el);
					});
					return;
				//select - произвольные значения
				case 17:
					_elemAction(el, _num(ATR_CMP.val()), 1);
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:vvv,
						func:function(v) {
							_elemAction(el, v);
						}
					});
					return;
				//dropdown
				case 18:
					_elemAction(el, _num(ATR_CMP.val()), 1);
					ATR_CMP._dropdown({
						title0:el.txt_1,
						title0_hide:el.num_1,
						nosel:el.num_2,
						spisok:vvv,
						func:function(v) {
							_elemAction(el, v);
						}
					});
					return;
				//Дополнительные условия к фильтру (вспомогательный элемент)
				case 22: PHP12_elem22(el, vvv, OBJ); return;
				//Список - ТАБЛИЦА
				case 23:
					if(!el.num_6)
						return;

					ATR_EL.find('ol:first').nestedSortable({
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
								busy_obj:ATR_EL,
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
					_elemAction(el, _num(ATR_CMP.val()), 1);
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:vvv,
						func:function(v) {
							_elemAction(el, v);
						}
					});
					return;
				//SA: Select - выбор документа
				case 26:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:vvv
					});
					return;
				//Загрузка файла
				case 28:
					var AT = ATR_EL.find('._attach'),
						ATUP = AT.find('.atup'),    //div для загрузки
						BUT = AT.find('.vk'),
						FORM = AT.find('form'),
						FILE = AT.find('.at-file'), //input file
						ATV = AT.find('.atv'),      //загруженный файл
						atmr;

					AT.find('.icon-del-red').click(function() {
						ATUP._dn(1);
						ATV._dn();
						ATR_CMP.val(0);
					});
					FILE.change(function() {
						FILE._vh();
						BUT.addClass('_busy');
						_cookie('_attached', 0);
						_cookie('_attached_id', 0);
						atmr = setInterval(upload_start, 500);
						FORM.submit();
					});
					function upload_start() {
						switch(_num(_cookie('_attached'))) {
							case 1:
								var attach_id = _cookie('_attached_id');
								ATUP._dn();
								ATV._dn(1).find('td').html('&nbsp;');
								ATR_CMP.val(attach_id);
								var send = {
									op:'attach_get',
									id:attach_id,
									busy_obj:ATV
								};
								_post(send, function(res) {
									ATV.find('td').html(res.name);
								});
							case 2:
							case 3:
								FILE._vh(1);
								BUT.removeClass('_busy');
								clearInterval(atmr);
						}
					}
					return;
				//select - выбор единицы из другого списка (для связки)
				case 29:
					_elemAction(el, _num(ATR_CMP.val()), 1);
					var o = {
						width:el.width,
						title0:el.txt_1,
						write:el.num_1 && el.num_3,
						msg_empty:'Не найдено',
						spisok:vvv,
						blocked:el.num_4,
						func:function(v) {
							_elemAction(el, v);
						},
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
					if(el.num_2)
						o.funcAdd = function(t) {
							_dialogLoad({
								dialog_id:el.num_1,
								busy_obj:t.icon_add,
								busy_cls:'spin',
								func_save:function(ia) {
									t.unitUnshift({
										id:ia.unit.id,
										title:ia.unit.txt_1
									});
									t.value(ia.unit.id);
								}
							});
						};
					ATR_CMP._select(o);
					return;
				//Выбор нескольких значений галочками
				case 31:
					$(document).on('click', ATTR_EL(elm_id) + ' ._check', function() {
						var cmpv = [];
						_forEq(ATR_EL.find('._check.on'), function(sp) {
							cmpv.push(sp.prev().attr('id').split('_')[1]);
						});
						ATR_CMP.val(cmpv.join())
					});
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
						spisok:vvv
					});
					return;
				//SA: Select - выбор диалогового окна
				case 38:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						msg_empty:'диалоги ещё не были созданы',
						spisok:vvv
					});
					return;
				//Выбор блоков из диалога или страницы
				case 49:
					var P = ATR_CMP.next(),
						INP = P.find('.inp'),
						DEL = P.find('.icon-del');

					P.click(function() {
						_dialogLoad({
							dialog_id:19,
							block_id:OBJ.srce.block_id,
							dop:{
								sel:ATR_CMP.val()
							},
							busy_obj:INP,
							busy_cls:'hold',
							func_save:function(res) {
								ATR_CMP.val(res.ids);
								INP.val(res.title);
								DEL._dn(res.ids);
							}
						});
					});
					DEL.click(function(e) {
						e.stopPropagation();
						ATR_CMP.val(0);
						INP.val('');
						DEL._dn();
					});
					return;
				//Календарь
				case 51:
					ATR_CMP._calendar({
						lost:_num(el.num_1),
						time:el.num_2,
						func:function() {
							_elemAction(el, 1);
						}
					});
					return;
				//Заметки
				case 52:
					if(!ATR_EL.length)
						return;
					var timer = 0,
						NOTE = ATR_EL.find('._note'),
						ex = NOTE.attr('val').split(':'),
						page_id = _num(ex[0]),
						obj_id = _num(ex[1]),
						NOTE_TXT = NOTE.find('._note-txt'),
						NOTE_AREA = NOTE_TXT.find('textarea'),
						NOTE_TXT_W = NOTE_TXT.width(),
						noteAfterPrint = function() {
							NOTE.find('textarea')._autosize();
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
									t.closest('TABLE').before(res.html);
									area.val('');
									area.trigger('_autosize');
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
							NOTE_AREA.val('').trigger('_autosize');
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
							_forN(vvv, function(sp) {
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
						spisok:vvv,
						func:toggle
					});
					return;
				//Связка списка при помощи кнопки
				case 59:
					//выполнение действия
					_elemAction(el, _num(ATR_CMP.val()), 1);

					var div = ATR_CMP_AFICS.next(),
						unitSel = function(id) {//действие после выбора значения
							var send = {
								op:'spisok_59_unit',
								cmp_id:elm_id,
								unit_id:id,
								busy_obj:ATR_CMP_AFICS
							};
							_post(send, function(res) {
								div.find('.un-html').html(res.html);
								div._dn(1);
								ATR_CMP_AFICS._dn();
								_elemAction(el, id);
							});
						};
					//нажатие на кнопку для открытыя диалога
					ATR_CMP_AFICS.click(function() {
						_dialogLoad({
							block_id:el.block_id,
							dialog_id:el.num_4,
							busy_obj:ATR_CMP_AFICS,
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
						ATR_CMP_AFICS._dn(1);
						div._dn();
						ATR_CMP.val(0);
						_elemAction(el, 0);
					});
					return;
				//Загрузка изображений
				case 60:
					var AEL = ATR_EL,
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
							get_id:_num(unit.id),
							prm:{elem_id:elm_id},
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
							_elemAction(el, v);
							FILTER[el.num_1][elm_id] = v;
							_spisokUpdate(el.num_1);
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
						msg:vvv,
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
				//Фильтр: год и месяц
				case 72:
					var YL = $(ATTR_CMP(elm_id) + 'yl'),
						RD = $(ATTR_CMP(elm_id) + 'rd'),
						YEAR_CUR = YL.val(),
						CMP_SET = function() {
							var mon = _num(RD.val()),
								data = YL.val() + '-' + (mon > 9 ? '' : '0') + mon;
							ATR_CMP.val(data);
							FILTER[el.num_1][elm_id] = data;
							_spisokUpdate(el.num_1);
						};
					YL._yearleaf({
						func:function(v) {
							RD._radio(YEAR_CUR < v ? 1 : 12);
							CMP_SET();
							YEAR_CUR = v;
							var send = {
								op:'spisok_72_sum',
								elem_id:elm_id,
								year:v
							};
							_post(send, function(res) {
								RD._radio('spisok', _toSpisok(res.spisok));
							});
						}
					});
					RD._radio(CMP_SET);
					return;
				//Фильтр-галочка
				case 74:
					ATR_CMP._radio({
						func:function(v) {
							FILTER[el.num_1][elm_id] = v;
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Фильтр-календарь
				case 77:
					var CAL = ATR_EL.find('._filter-calendar'),
						CNT = CAL.find('.fc-cnt');

					//перемотка месяцев
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

					//нажатие на неделю или на день
					CNT.click(function(e) {
						var t = $(e.target),
							on = t.hasClass('on'),
							week = t.hasClass('week-num'),
							td = on ? t : t.parent();
						if(on || week) {
							CNT.find('.sel').removeClass('sel');
							td.addClass('sel');
							FILTER[el.num_1][elm_id] = t.attr('val');
							_spisokUpdate(el.num_1);
						}
					});
					return;
				//Фильтр-меню
				case 78:
					var FM = ATR_EL.find('.fm-unit');
					ATR_EL.find('.fm-plus').click(function() {
						var t = $(this),
							plus = t.html() == '+',
							div = t.closest('TABLE').next();
						div['slide' + (plus ? 'Down' : 'Up')](200);
						t.html(plus ? '-' : '+');
					});
					FM.click(function() {
						var t = $(this),
							sel = t.hasClass('sel');
						FM.removeClass('sel');
						if(!sel)
							t.addClass('sel');
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
									case 7:  _attr_cmp(sp.elem_id)._search('clear'); return;
									//фильтр-галочка
									case 62: _attr_cmp(sp.elem_id)._check(sp.v); return;
									//фильтр-календарь
									case 77:
										var CAL = _attr_el(sp.elem_id).find('._filter-calendar');
										CAL.find('.mon-cur').val(sp.dop.mon);
										CAL.find('.td-mon').html(sp.dop.td_mon);
										CAL.find('.fc-cnt').html(sp.dop.cnt);
										return;
									//фильтр-меню
									case 78: _attr_el(sp.elem_id).find('.sel').removeClass('sel'); return;
									//фильтр-select
									case 83: _attr_cmp(sp.elem_id)._select(0); return;
									//Фильтр - Выбор нескольких групп значений
									case 102:
										_attr_el(sp.elem_id).find('.holder')._dn(1);
										_attr_el(sp.elem_id).find('.td-un').html('<div class="icon icon-empty"></div>');
										_attr_el(sp.elem_id).find('.icon-del')._vh();
										return;
								}
							});
							FILTER = res.filter;
						});
					});
					return;
				//Select - фильтр
				case 83:
					ATR_CMP._select({
						width:el.width,
						title0:el.txt_1,
						spisok:vvv,
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
						spisok:vvv,
						msg_empty:el.num_1 ? 'Список пуст' : 'Не указан список',
						func:function(v) {
//							_elemAction(el, v);
						}
					});
					ELM_RELOAD[el.num_1] = el.id;
					return;
				//Фильтр - Выбор нескольких групп значений
				case 102:
					var HLD = ATR_EL.find('.holder'),//текст пустого значения
						TDUN = ATR_EL.find('.td-un'),//выбранные значения
						DEL = ATR_EL.find('.icon-del'),//иконка удаления
						ICON_EMPTY = '<div class="icon icon-empty"></div>',
						TITLE = window['EL' + el.id + '_F102_TITLE'],
						COUNT = window['EL' + el.id + '_F102_C'],
						BG = window['EL' + el.id + '_F102_BG'],
						un = function(id, tl) {//формирование значения для вставки
							var bg = BG[id] ? ' style="background-color:' + BG[id] + '"' : '',
								title = tl ? TITLE[id] : _num(COUNT[id]);
							return '<div class="un"' + bg + '>' + title +'</div>';
						},
						sevSet = function() {//обновление выбранных значений
							var sel = '',
								ids = [];
							_forEq(ATR_EL.find('._check'), function(sp) {
								var p = sp.prev(),
									v = _num(p.val()),
									id = p.parent().parent().attr('val');

								if(v) {
									sel += un(id);
									ids.push(id);
								}
							});

							if(ids.length == 1)
								sel = un(ids[0], 1);

							HLD._dn(!sel);
							TDUN.html(sel || ICON_EMPTY);
							DEL._vh(sel);
							FILTER[el.num_1][elm_id] = ids.join();
							_spisokUpdate(el.num_1);
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
							FILTER[el.num_1][elm_id] = 0;
							_spisokUpdate(el.num_1);
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
								FILTER[el.num_1][elm_id] = id;
								_spisokUpdate(el.num_1);
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
					return;
				//Привязка пользователя к странице ВК
				case 300:
					var VK300 = ATR_CMP.next(),
						INP = VK300.find('input'),
						VK_ICON = VK300.find('.icon-vk'),
						VK_RES = INP.next(),
						VK_SEL,                 //выбранный пользователь в виде html
						VK_ID = ATR_CMP.val();  //id выбранного пользователя ВК
					INP.keyup(function() {
						var t = $(this),
							val = $.trim(t.val());

						if(!val) {
							VK_RES.html('');
							return;
						}

						var send = {
							op:'vk_user_get',
							val:val,
							busy_obj:VK_ICON,
							busy_cls:'spin'
						};
						_post(send, function(res) {
							VK_RES.html(res.html);
							VK_SEL = res.sel;
							VK_ID = res.user_id
						});
					});

					$(document)
						//выбор пользователя
					   .off('click', ATTR_EL(el.id) + ' button')
						.on('click', ATTR_EL(el.id) + ' button', function() {
							VK_RES.html(VK_SEL);
							INP._dn();
							VK_ICON._dn();
							ATR_CMP.val(VK_ID);
						})
						//отмена выбранного пользователя
					   .off('click', ATTR_EL(el.id) + ' .icon-del-red')
						.on('click', ATTR_EL(el.id) + ' .icon-del-red', function() {
							VK_RES.html('');
							VK_ICON._dn(1);
							ATR_CMP.val(0);
							INP.val('')._dn(1).focus();
						});
					return;
			}
		});

		//установка фокуса на первый указанный элемент
		_forIn(OBJ.vvv, function(vvv, id) {
			if(ELMM[id].focus) {
				_attr_cmp(id).focus();
				return false;
			}
		});
	},
	_elemHint = function(el) {//подключение подсказки к элементу
		if(!el.hint)
			return;

		var hint = el.hint,
			side = {
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
		_attr_el(el.id).mouseenter(function() {
			var o = {
				msg:_br(hint.msg, 1),
				pad:10,
				side:side[hint.side],
				show:1,
				delayShow:hint.delay_show,
				delayHide:hint.delay_hide
			};
			if(hint.side)
				o.objPos = objPos[hint['pos_' + sideObj[hint.side]]];
			$(this)._hint(o);
		});
	},
	_elemAction = function(el, v, is_open) {//применение функций, привязанных к элементам
		/*
			is_open - окно открылось - применение функций без эффектов
		*/

		if(!el.func)
			return;

		_forN(el.func, function(sp) {
			switch(sp.dialog_id) {
				//показ/скрытие блоков
				case 201:
					var is_show = 0;//по умолчанию скрывать.

					//ДЕЙСТВИЕ
					switch(sp.apply_id) {
						//скрыть
						case 2783:
						default: break;
						//показать
						case 2784:
							is_show = 1;
							break;
					}

					if(!sp.initial_id)
						return;

					//значение установлено
					if(v) {
						//любое значение
						if(sp.initial_id != -2 && sp.initial_id != v) {
							if(sp.revers)
								is_show = is_show ? 0 : 1;
							else
								return;
						}
					}

					//значение НЕ установлено
					if(!v) {
						if(sp.initial_id != -1) {
							if(sp.revers)
								is_show = is_show ? 0 : 1;
							else
								return;
						}
					}


					//ПРОЦЕСС
					_forN(_elemFuncBlockObj(sp.target_ids), function(oo) {
						if(!oo.obj.length)
							return;

						switch(sp.effect_id) {
							//исчезновение/появление
							case 44:
							case 715:
							case 2789:
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
							case 2790:
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
				case 202:
					var is_set = 0;//по умолчанию: сбросить значение

					//ТИП СОБЫТИЯ - что должно быть на исходном элементе
					switch(sp.initial_id) {
						//значение было сброшено
						case -1:
						default: break;
						//установлено какое-то значение
						case -2:
							is_set = 1;
							break;
					}
/*
					switch(sp.cond_id) {
						case 1715://галочка снята
							if(v && sp.revers) {
								is_set = is_set ? 0 : 1;
								break;
							}
							if(v)
								return;
							break;
						case 1716://галочка установлена
							if(!v && sp.revers) {
								is_set = is_set ? 0 : 1;
								break;
							}
							if(!v)
								return;
							break;
						default: return;
					}
*/

					_forIn(_idsAss(sp.target_ids), function(ex, id) {
						var EL = ELMM[id];
						//свои способы действия на каждый элемент
						switch(EL.dialog_id) {
							//галочка
							case 1:
								_attr_cmp(id)._check(is_set);
								break;
							//фильтр-галочка
							case 62:
								_attr_cmp(id)._check(is_set);
								FILTER[el.num_1][id] = is_set;
								break;
						}
					});
				//открытие диалога
				case 205:
					if(is_open)
						break;
					if(v != sp.initial_id)
						break;
					var dlg_id = _num(sp.target_ids);
					if(!dlg_id)
						break;
					var send = {
						dialog_id:dlg_id,
						get_id:GET_ID,
						edit_id:GET_ID,
						busy_obj:_attr_bl(ELMM[el.id].block_id)
					};

					_dialogLoad(send);
					break;
				//установка фокуса
				case 206:
					var is_set = 0;//по умолчанию: сбросить значение

					//ТИП СОБЫТИЯ - что должно быть на исходном элементе
					switch(sp.initial_id) {
						//значение было сброшено
						case -1:
						default: break;
						//установлено какое-то значение
						case -2:
							is_set = 1;
							break;
					}

					var elem_id = _num(sp.target_ids);
					if(!elem_id)
						return;
					_attr_cmp(elem_id).focus();
				//открытие документа
				case 207:
					if(is_open)
						break;
					if(v != sp.initial_id)
						break;
					var doc_id = _num(sp.target_ids);
					if(!doc_id)
						break;

					location.href = URL + '&p=9&doc_id=' + doc_id + (GET_ID ? '&id=' + GET_ID : '');
					break;
			}
		});
	},
	_elemFuncBlockObj = function(blk_ass) {//получение $(obj) блоков
		if(typeof blk_ass != 'object')
			blk_ass = _idsAss(blk_ass);

		var arr = [],
			TRG = _copyObj(blk_ass),
			D = $;

		_forIn(TRG, function(n, block_id) {
			if(!n)
				return;

			var BL = BLKK[block_id];

			//блок мог быть удалён, но в функциях id остался
			if(!BL)
				return;

			var ATR_BL = D(ATTR_BL(block_id));

			if(BL.xx == 1) {//если блок в ряду один, фукнция применится ко всей таблице
				arr.push({
					obj:ATR_BL.closest('.bl-div'),
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
					obj:ATR_BL.closest('.bl-div'),
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

	_elemReload = function(el, res) {//перезагрузка элемента
		//выход, если нечего перезагружать
		if(!ELM_RELOAD[el.id])
			return;

		var reload_id = ELM_RELOAD[el.id];

		//пока с элементом [85]
		_attr_cmp(reload_id)
			._select(0)
			._select('spisok', res ? res.spisok : []);
	},

	_blockActionJS = function(bo, block_id, unit_id) {//выполнение действия при нажатии на блок
		if(!BLKK[block_id])
			return;

		var BL = BLKK[block_id];

		if(!BL.action)
			return;

		_forN(BL.action, function(sp) {
			switch(sp.dialog_id) {
				//показ/скрытие блоков
				case 211://По умолчанию - для остальных элементов
					var is_show = 0;//скрывать или показывать блоки. По умолчанию скрывать.

					//ДЕЙСТВИЕ
					switch(sp.apply_id) {
						//скрыть
						case 3166:
						default: break;
						//показать
						case 3167:
							is_show = 1;
							break;
					}

					if(sp.revers) {
						if(sp.on)
							is_show = is_show ? 0 : 1;
						sp.on = sp.on ? 0 : 1;
					}


					//ПРОЦЕСС
					_forN(_elemFuncBlockObj(sp.target_ids), function(oo) {
						if(!oo.obj.length)
							return;

						switch(sp.effect_id) {
							//исчезновение/появление
							case 3171:
								oo.obj._dn(1, 'vh');
								oo.obj.animate({opacity:is_show}, 300, function() {
									oo.obj._dn(is_show, 'vh');
								});
								return;
							//сворачивание/разворачивание
							case 3172:
								if(!oo.slide) {
									oo.obj._dn(is_show, 'vh');
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
				//установка значения элементу
				case 212:
					var target_id = _num(sp.target_ids),
						ATR_CMP = _attr_cmp(target_id),
						EL = ELMM[target_id],
						v = sp.apply_id;

					if(!EL)
						return;
					if(!v)
						return;

					//-1 - означает сброс значения
					if(v == -1)
						v = 0;

					switch(EL.dialog_id) {
						case 1:
							//активирование галочки, если требуется
							if(ATR_CMP.next().hasClass('php'))
								ATR_CMP._check();
							ATR_CMP._check(v);
							return;
						case 29: ATR_CMP._select(v); return;
					}
					break;
				//блокировка элементов
				case 213:
					/*
						3365 - заблокировать
						3366 - разблокировать
					*/
					var lock = sp.apply_id == 3365;

					_forIn(_idsAss(sp.target_ids), function(n, id) {
						var EL = ELMM[id];
						if(!EL)
							return;
						switch(EL.dialog_id) {
							case 5:
							case 8: _attr_cmp(id).attr('disabled', lock); break;
						}
					});
					break;
				//переход на страницу
				case 214:
					var page_id = _num(sp.target_ids);
					if(!page_id)
						break;

					var link = '&p=' + page_id;

					if(sp.apply_id)
						link += '&id=' + unit_id;

					$(bo).addClass('_busy');
					location.href = URL + link;

					return false;
				//открытие диалога
				case 215:
					var dlg_id = _num(sp.target_ids);
					if(!dlg_id)
						break;
					var send = {
						dialog_id:dlg_id,
						block_id:block_id,
						busy_obj:$(bo)
					};

					//блок передаёт id записи для отображения
					if(sp.apply_id)
						send.get_id = unit_id;
					//блок передаёт id записи для редактирования
					if(sp.effect_id)
						send.edit_id = unit_id;

					_dialogLoad(send);
					break;
				//установка фокуса на элемент
				case 216:
					var elem_id = _num(sp.target_ids);
					if(!elem_id)
						break;
					_attr_cmp(elem_id).focus();
					break;
			}
		});
	},

	/* ---=== ВЫБОР ЭЛЕМЕНТА [50] ===--- */
	PHP12_elem_choose = function(el, vvv, obj) {
		var D = obj.dlg.D;
		D('.el-group-head').click(function() {//переключение меню
			var t = $(this),
				id = t.attr('val');
			D('.el-group-head').removeClass('sel');
			t.addClass('sel');

			D('#elem-group .cnt')._dn(0);
			D('#cnt_' + id)._dn(1);
		});
		D('.elem-unit').click(function(e) {//открытие диалога
			if($(e.target).hasClass('dialog-setup'))
				return;
			var t = $(this);
			_dialogLoad({
				dialog_id:t.attr('val'),
				block_id:obj.send.block_id,
				dss:obj.send.dss,
				busy_obj:t,
				busy_cls:'_busy',
				func_save:function(res) {
					obj.dlg.close();
					if(obj.send.func_save)
						return obj.send.func_save(res);
					return true;
				}
			});
		});

		if(!SA)
			return;

		_forEq(D('#elem-group .cnt'), function(sp) {
			sp._sort({table:'_dialog'});
		});
	},

	/* ---=== УСЛОВИЯ ДЛЯ ФИЛЬТРОВ [22] ===--- */
	PHP12_elem22 = function(el, vvv, obj) {//Дополнительные условия к фильтру
		//ID диалога, значения которого будут настраиваться
		var DS = window['EL' + el.id + '_DS'];
		if(!DS)
			return;
		if(!obj.unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить условие</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last');

		BUT_ADD.click(valueAdd);

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента, хранящего настройки
				title:'', //имя выбранного элемента
				txt_1:0,  //id выбранного элемента из диалога, по которому будет выполняться условие фильтра
				num_2:0,  //id условия из выпадающего списка
				txt_2:'', //текстовое значение
				issp:0,   //можно выбирать значения из списка. Только при условиях: [3:равно], [4:не равно]
				spisok:[],//содержание выпадающего списка
				num_3:''  //значение выпадающего списка
			}, v);

			var issp34 = v.issp && (v.num_2 == 3 || v.num_2 == 4);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 r color-sal">Если:' +
							'<td><input type="text"' +
									  ' readonly' +
									  ' class="title w175 curP over4 color-pay"' +
									  ' placeholder="выберите значение..."' +
									  ' value="' + v.title + '"' +
									  ' val="' + v.txt_1 + '"' +
								' />' +
							'<td class="cond-td' + _dn(v.txt_1) + '">' +
								'<input type="hidden" class="cond-id" value="' + v.num_2 + '" />' +
							'<td class="w100p pr20">' +
								'<input type="text"' +
									  ' class="cond-val w100' + _dn(!issp34 && v.num_2 > 2) + '"' +
									  ' value="' + v.txt_2 + '"' +
								' />' +
								'<div class="div-cond-sel' + _dn(issp34) + '">' +
									'<input type="hidden"' +
										  ' class="cond-sel"' +
										  ' value="' + v.num_3 + '"' +
									' />' +
								'</div>' +
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
					dss:DS,
					block_id:obj.srce.block_id,
					dop:{
						mysave:1,
						sel:v.txt_1,
						nest:1
					},
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						DD.find('.cond-td')._dn(1);
						COND_ID._select(2);
						v.txt_1 = res.v;
						TITLE.attr('val', v.txt_1);
						TITLE.val(res.title);
						DD.find('.cond-val')._dn().val('');

						//если выбран подключаемый список, то выбор значений этого списка
						v.issp = res.issp;
						DD.find('.div-cond-sel')._dn();
						DD.find('.cond-sel')
							._select('spisok', res.spisok)
							._select(0);
					}
				});
			});
			COND_ID._select({//условие
				width:150,
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
				func:function(vv) {
					var issp = v.issp && (vv == 3 || vv == 4);
					DD.find('.cond-val')
						._dn(!issp && vv > 2)
						.focus();
					DD.find('.div-cond-sel')._dn(issp);
				}
			});
			DD.find('.cond-sel')._select({
				width:0,
				title0:'не выбрано',
				spisok:v.spisok
			});
			DD.find('.icon-del').click(function() {
				$(this).closest('DD').remove();
			});
		}
	},
	PHP12_elem22_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				id:_num(sp.attr('val')),
				txt_1:sp.find('.title').attr('val'),
				num_2:sp.find('.cond-id').val(),
				txt_2:sp.find('.cond-val').val(),
				num_3:sp.find('.cond-sel').val()
			});
		});
		return send;
	},

	/* ----==== СПИСОК СТРАНИЦ (page12) ====---- */
	PHP12_page_list = function(el) {
		_attr_el(el.id).find('ol.page-sort').nestedSortable({
			forcePlaceholderSize:true,//сохранять размер места, откуда был взят элемент
			placeholder:'page-sort-hold', //класс, применяемый для подсветки места, откуда взялся элемент
			listType:'ol',
			items:'li',
			handle:'.icon-move',
			isTree:1,
			maxLevels:3,
			tabSize:20, //расстояние, на которое надо сместить элемент, чтобы он перешёл на другой уровень
			revert:200, //плавное возвращение (полёт) элемента на своё место. Цифра - скорость в миллисекундах.

			start:function(e, t) {//установка ширины placeholder
				if($(t.placeholder).prev().hasClass('mb30'))
					$(t.placeholder).addClass('mb30');
				if($(t.placeholder).prev().hasClass('mb1'))
					$(t.placeholder).addClass('mb1');
			},
			update:function(e, t) {
				var send = {
					op:'page_sort',
					arr:$(this).nestedSortable('toArray'),
					busy_obj:_attr_el(el.id),
					busy_cls:'spisok-busy'
				};
				_post(send, function() {
					var item = $(t.item),
						p = item.parent(),
						prn = p.hasClass('page-sort');

					item.removeClass(prn ? 'mb1' : 'mb30');
					item.addClass(!prn ? 'mb1' : 'mb30');
					item.find('.pg-name:first')[(prn ? 'add' : 'remove') + 'Class']('b fs14');
				});
			},

			errorClass:'bg-fcc'  //ошибка, если попытка переместить элемент на недоступный уровень
		});
	},

	/* ---=== ЭЛЕМЕНТЫ, КОТОРЫЕ МОЖНО ВЫБИРАТЬ В НАСТРОЙКЕ ДИАЛОГА [13] ===--- */
	PHP12_elem_rule7_get = function(el) {
		var ids = [];
		_forEq(_attr_el(el.id).find('input'), function(sp) {
			var id = _num(sp.attr('id').split('rule7-el')[1]),
				v = _num(sp.val());
			if(!id)
				return;
			if(!v)
				return;
			ids.push(id);
		});
		_attr_cmp(el.id).val(ids.join());
	},

	/* ---=== ВЫБОР ЗНАЧЕНИЯ ИЗ ДИАЛОГА [11] ===--- */
	PHP12_v_choose = function(el, vvv, obj) {
		var D = obj.dlg.D,
			VC = D(ATTR_EL(el.id)).find('.elm-choose');//элементы в открытом диалоге для выбора

		//описание глобальных переменных при открытии исходного (первого, невложенного) диалога
		if(vvv.first) {
			V11_CMP = D(ATTR_CMP(el.id));   //переменная в исходном диалоге для хранения значений
			V11_DLG = [];                   //массив диалогов, открывающиеся последовательно
			V11_V = vvv.sev ? _idsAss(vvv.sel) : []; //массив выбранных значений
			V11_COUNT = 0;                  //счётчик открытых диалогов

			if(vvv.sel)
				V11_CMP.val(vvv.sel);

			vvv.first = 0;
		}

		//выбор одного из элеметов
		VC.click(function() {
			var t = $(this),
				v = _num(t.attr('val'));

			if(vvv.sev) {
				var sel = !t.hasClass('sel');
				t[(sel ? 'add' : 'remove') + 'Class']('sel');
				if(sel)
					V11_V[v] = 1;
				else
					delete V11_V[v];
				var v11 = [];
				for(var k in V11_V)
					v11.push(k);
				V11_CMP.val(v11.join());
			} else {
				VC.removeClass('sel');
				t.addClass('sel');

				V11_V.length = V11_COUNT;
				V11_V[V11_COUNT] = v;
				V11_DLG.length = V11_COUNT;
				V11_DLG[V11_COUNT] = obj.dlg;

				V11_CMP.val(V11_V.join());
			}

			//нажатие по обычному элементу (не список)
			if(vvv.sev || !vvv.nest || !ELMM[v].issp)
				return;

			V11_COUNT++;

			_dialogLoad({
				dialog_id:11,
				block_id:obj.srce.block_id,
				dss:ELMM[v].num_1,
				dop:vvv,
				func_open:PHP12_v_choose_submit
			});
		});
	},
	PHP12_v_choose_submit = function(res, dlg) {//действие после выбора значений. Используется для всех элементов, которые открывают диалог [11]
		dlg.submit(function() {
			var sel = dlg.content.find('.elm-choose.sel');
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
	},
	PHP12_v_choose_get = function(el, obj) {//отправка значений настройки для определения типа сохранения данных. Конкретно по "mysave"
		return obj.vvv[el.id];
	},

	/* ---=== ВЫБОР БЛОКОВ [19] ===--- */
	PHP12_block_choose = function(el, vvv, obj) {
		var D = obj.dlg.D,
			ATR_EL = D(ATTR_EL(el.id)),
			LEVEL = ATR_EL.find('.block-choose-level-change');

		//переключение уровней блоков
		LEVEL.click(function() {
			var t = $(this),
				send = {
					op:'block_choose_level_change',
					block_id:obj.srce.block_id,
					level:_num(t.html()),
					busy_obj:ATR_EL.find('.level-hold')
				};
			if(t.hasClass('orange'))
				return;

			_post(send, function(res) {
				ATR_EL.find('#block-choose-div').html(res.html);
				LEVEL.removeClass('orange')
					.addClass('cancel');
				t.removeClass('cancel').addClass('orange');
				PHP12_block_choose_bc(el, obj);
			});
		});

		PHP12_block_choose_bc(el, obj);
	},
	PHP12_block_choose_bc = function(el, obj) {//получение блоков в открытом диалоге для выбора, а также обновление кликов по ним
		var D = obj.dlg.D,
			ATR_EL = D(ATTR_EL(el.id)),
			bc = ATR_EL.find('.blk-choose');

		//подсветка блока при выборе
		bc.click(function() {
			var t = $(this),
				v = t.attr('val'),
				sel = t.hasClass('sel');

			t[(sel ? 'remove' : 'add') + 'Class']('sel');
		});

		return bc;
	},
	PHP12_block_choose_get = function(el, obj) {
		var send = [];
		_forEq(PHP12_block_choose_bc(el, obj), function(sp) {
			if(sp.hasClass('sel'))
				send.push(sp.attr('val'));
		});
		return send.join();
	},

	/* ---=== НАСТРОЙКА МЕНЮ ПЕРЕКЛЮЧЕНИЯ БЛОКОВ ===--- */
	PHP12_menu_block_setup = function(el, vvv, obj) {//используется в диалоге [57]
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

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

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
						if(id)
							deny.push(id);
					});
				});

				_dialogLoad({
					dialog_id:19,
					block_id:obj.srce.block_id,
					dop:{
						level_deny:1,
						blk_deny:deny.join(),
						sel:BLOCK.attr('val')
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
				$(this).closest('DD').remove();
			});
		}
	},
	PHP12_menu_block_setup_get = function(el) {
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
	PHP12_td_setup = function(el, vvv, obj) {//настройка ячеек таблицы
		if(!obj.unit.id)
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
				obj.unit.num_5 = v;
				DL.find('.div-th-name')['slide' + (v ? 'Down' : 'Up')]();
			}
		});

		_forIn(vvv, tdAdd);

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
				url_action_id:0,//текст в колонке является ссылкой
				txt_7:'',       //TH-заголовок колонки
				pos:''          //txt_8: позиция по горизонтали (l, center, r)
			}, v.id ? v : {});

			DL.append(
				'<dd class="over3" val="' + v.id + '" data-url="' + v.url_action_id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w80 grey r topi">Колонка ' + NUM + ':' +
							'<td><div style="width:' + v.width + 'px">' +
									'<div class="div-th-name' + _dn(obj.unit.num_5) + '">' +
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
					block_id:obj.srce.block_id,  //блок, в котором размещена таблица
					edit_id:v.id,                //id выбранного элемента (при редактировании)
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
								'<td class="pt3">' +
									'<div class="icon-wiki iw12 ml3' + _dn(v.url_action_id, 'on') + _tooltip('Ссылка', -22) + '</div>' +
								'<td class="pt3 pl10" id="elem-pos">' + _elemUnitPlaceMiddle(v) +
						'</table>' +
						'',
					side:'right',
					show:1,
					delayShow:700,
					delayHide:300,
					func:function(o) {
						o.find('.iw12').click(function() {
							var url = $(this);

							//снятие ссылки
							if(url.hasClass('on')) {
								url.removeClass('on');
								DD.attr('data-url', 0);
								return false;
							}

							_dialogLoad({
								dialog_id:221,
								element_id:v.id,
								edit_id:v.url_action_id,
								busy_obj:$(this),
								func_save:function(res) {
									DD.attr('data-url', res.unit.id);
									v.url_action_id = res.unit.id;
								}
							});
						});
					}
				});
			});

			//сортировка колонок
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
			});

			//удаление элемента
			DL.find('.icon-del:last').click(function() {
				$(this).closest('DD').remove();
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
	PHP12_td_setup_get = function(el) {//сохранение ячеек таблицы
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

			//ссылка (действие [221])
			v.url = _num(sp.attr('data-url'));

			send.push(v);
		});

		return send;
	},

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ для [16][17][18] ===--- */
	PHP12_radio_setup = function(el, vvv, obj) {
		/*
			num_1 - использовать описание значений
		*/
		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATTR_EL = _attr_el(el.id),
			DL = ATTR_EL.append(html).find('dl'),
			BUT_ADD = ATTR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		if(!obj.unit.id) {
			valueAdd();
			valueAdd();
		} else
			_forIn(vvv, valueAdd);

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
								'<textarea class="w100p min mtm1' + _dn(el.num_1) + '" placeholder="описание значения">' + v.content + '</textarea>' +
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
			DD.find('textarea')._autosize();
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
				$(this).closest('DD').remove();
			});
			if(!v.id)
				DD.find('.title').select();
			NUM++;
		}
	},
	PHP12_radio_setup_get = function(el) {
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

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ФИЛЬТРА RADIO для [74] ===--- */
	PHP12_filter_radio_setup = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			ATR_SP = $('#cmp_2585'),
			NUM = 1,
			_CS = function(id, count) {//отобажение иконки настройки условий
				if(count)
					return '<span class="cond-setup ml20 curP' + _tooltip('Настроить', -10) + count + ' услови' + _end(count, ['е', 'я', 'ий']) + '<span>';

				if(id)
					return '<div class="icon icon-add cond-setup pl ml15' + _tooltip('Добавить условия', -57) + '</div>';

				return '<div class="icon icon-hint ml15"></div>';
			};

		_attr_cmp(2585)._select('disable');
		BUT_ADD.click(valueAdd);

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента из диалога, по которому будет выполняться условие фильтра
				title:'Значение ' + NUM++,
				def:0,
				c:0,        //количество настроек условий фильтра
				num_1:1     //отображть количество для пункта фильтра
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="title w200 mr10"' +
									  ' placeholder="имя значения"' +
									  ' value="' + v.title + '"' +
								' />' +
								'<input type="hidden" class="def" value="' + v.def + '" />' +
								'<span class="span-cs grey">' + _CS(v.id, v.c) + '</span>' +
							'<td class="w100">' +
								'<div class="icon icon-eye' + _dn(!v.num_1, 'over3-show pl') + _tooltip('Отображать<br>количество', -38, '', 1) + '</div>' +
							'<td class="w35 r">' +
								'<div class="icon icon-del-red pl' + _tooltip('Удалить значение', -54) + '</div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({axis:'y',handle:'.icon-move-y'});

			var DD = DL.find('dd:last');

			//установка галочки по умолчанию
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

			if(!v.id)
				DD.find('.icon-hint').mouseenter(function() {
					$(this)._hint({
						width:200,
						pad:10,
						msg:'Для настройки условий этого значения сохраните фильтр и откройте его снова.',
						show:1
					})
				});

			//добавление условия к значению
			DD.find('.span-cs').click(function() {
				var cs = $(this).find('.cond-setup');
				if(!cs.length)
					return;
				_dialogLoad({
					dialog_id:25,
					dss:ELMM[ATR_SP.val()].num_1,
					block_id:obj.srce.block_id,
					edit_id:v.id,
					busy_obj:cs,
					busy_cls:v.c ? '_busy' : 'spin',
					func_save:function(ia) {
						DD.find('.span-cs').html(_CS(1, ia.unit.func12));
					}
				});
			});

			//включение/выключение отображения количества для каждого пункта фильтра
			DD.find('.icon-eye').click(function() {
				var t = $(this),
					show = !t.hasClass('over3-show');
				t._dn(!show, 'over3-show pl');
			});

			//удаление значения radio вместе с условиями
			DD.find('.icon-del-red').click(function() {
				$(this).closest('DD').remove();
			});
		}
	},
	PHP12_filter_radio_setup_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				id:_num(sp.attr('val')),
				title:sp.find('.title').val(),
				def:sp.find('.def').val(),
				num_1:sp.find('.icon-eye').hasClass('pl') ? 0 : 1
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА СБОРНОГО ТЕКСТА ===--- */
	PHP12_44_setup = function(el, vvv, obj) {//для [44]
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить элемент</div>',
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last');

		BUT_ADD.click(valueAdd);

		//вывод двух первых элементов, если начало настройки
		if(!vvv.length) {
			valueAdd();
			valueAdd({spc:0});
		} else
			_forIn(vvv, valueAdd);

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
								'<div class="icon icon-del pl' + _tooltip('Удалить элемент', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:obj.srce.block_id,
					edit_id:v.id,           //id выбранного элемента (при редактировании)
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
				$(this).closest('DD').remove();
				v.id = 0;
			});
		}
	},
	PHP12_44_setup_get = function(el) {
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

	/* ---=== НАСТРОЙКА БАЛАНСА - СУММ ЗНАЧЕНИЙ ЕДИНИЦЫ СПИСКА для [27] ===--- */
	PHP12_balans_setup = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last');

		BUT_ADD.click(valueAdd);

		_forIn(vvv, valueAdd);

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
					block_id:obj.srce.block_id,
					edit_id:v.id,      //id выбранного элемента (при редактировании)
					dop:{
						nest:0,
						allow:'8,27,54,55'
					},
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
				$(this).closest('DD').remove();
				v.id = 0;
			});
		}
	},
	PHP12_balans_setup_get = function(el) {
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

	/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
	PHP12_action_list = function(el) {
		_attr_el(el.id).find('DL')._sort({table:'_action'});
	},

	/* ---=== НАСТРОЙКА ПАРАМЕТРОВ ШАБЛОНА ДЛЯ ДОКУМЕНТОВ [114] ===--- */
	PHP12_template_param = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over5 curP">Добавить параметр</div>',
			DL = ATR_EL.append(html).find('dl'),
			ATR_SP = _attr_cmp(3528),
			DLG_ID = _num(ATR_SP.val()),//список, из которого будут выбираться значения
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		ATR_SP._select('disable');
		BUT_ADD.click(valueAdd);

		//показ одного значения, если начало настройки
		if(!vvv.length) {
			valueAdd();
		} else
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:0,           //id значения
				txt_10:'{00000' + NUM++ + '}',//код, по которому будет производиться подмена данных в шаблоне
				dialog_id:50,   //id диалога, через который был вставлен этот элемент
				title:''        //имя элемента
			}, v || {});

			DL.append(
				'<dd id="dd' + v.id + '" class="over5">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="w200 b txt_10"' +
									  ' placeholder="код значения"' +
									  ' maxlength="20"' +
									  ' value="' + v.txt_10 + '"' +
								' />' +
							'<td class="w100p">' +
								'<input type="text"' +
									  ' class="inp w250 curP color-pay"' +
									  ' readonly' +
									  ' placeholder="значение не выбрано"' +
									  ' value="' + (v.title || v.id || '') + '"' +
								' />' +
							'<td class="w50 r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить параметр', -55) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					dss:DLG_ID,
					edit_id:v.id,           //id выбранного элемента (при редактировании)
					dop:{
						nest:1,
						rule_id:6
					},
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('id', 'dd' + ia.unit.id);
						v.id = ia.unit.id;
						v.dialog_id = ia.unit.dialog_id;
						INP.val(ia.unit.title || ia.unit.id);
					}
				});
			});
			DL.sortable({handle:'.icon-move-y'});
			DD.find('.icon-del').click(function() {
				$(this).closest('DD').remove();
				v.id = 0;
			});
			DD.find('.code').focus();
		}
	},
	PHP12_template_param_get = function(el, obj) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				elem_id:_num(sp.attr('id').split('dd')[1]),
				txt_10:sp.find('.txt_10').val()
			});
		});
		return send;
	},

	/* ---=== НАСТРОЙКА ШАБЛОНА ИСТОРИИ ДЕЙСТВИЙ [67] ===--- */
	PHP12_history_setup = function(el, vvv, obj) {
		var html = '<dl></dl>' +
				   '<div class="fs15 color-555 pad10 center over1 curP">Добавить сборку</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

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
					dss:obj.srce.dss,
					edit_id:v.id,
					dop:{rule_id:6},
					busy_obj:TITLE,
					busy_cls:'hold',
					func_save:function(res) {
						v.id = res.unit.id;
						v.dialog_id = res.unit.dialog_id;
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
								'<td class="pt3">' + _elemUnitFormat(v) +
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
				$(this).closest('DD').remove();
			});
			DD.find('.txt_7').focus();
		}
	},
	PHP12_history_setup_get = function(el, obj) {
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
			act:obj.srce.dop.act,
			v:send
		};
	},

	/* ---=== УКАЗАНИЕ ЭЛЕМЕНТОВ ПОД КОНКРЕТНОЕ ПРАВИЛО [1000] ===--- */
	PHP12_elem_all_rule_setup_get = function(el, obj) {
		var send = [];
		_forEq(_attr_el(el.id).find('input'), function(sp) {
			var id = _num(sp.attr('id').split('rule-el')[1]),
				v = _num(sp.val());
			if(!id)
				return;
			if(!v)
				return;
			send.push(id);
		});
		return {
			rule_id:obj.get_id,
			ids:send.join()
		};
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

	//настройка доступа к страницам для пользователя
	PHP12_page_access_for_user_setup = function(el, vvv, obj) {
		_forEq(_attr_el(el.id).find('._check'), function(sp) {
			var prev = sp.prev();
			prev._check({
				func:function(v) {
					prev.parents('table').next()[v ? 'slideDown' : 'slideUp'](200);
				}
			});
		});
	},
	PHP12_page_access_for_user_setup_get = function(el) {
		var ids = [];

		_forEq(_attr_el(el.id).find('._check'), function(sp) {
			var ch = sp.prev(),
				id = _num(ch.attr('id').split('_')[1]),
				v = _num(ch.val());
			if(v)
				ids.push(id);
		});

		return ids.join(',');
	},

	//настройка входа в приложение для всех сотрудников
	PHP12_app_enter_for_all_user_get = function(el) {
			var send = [];
			_forEq(_attr_el(el.id).find('._check'), function(sp) {
				var ch = sp.prev(),
					id = _num(ch.attr('id').split('_')[1]),
					v = _num(ch.val());
				if(v)
					send.push(id);
			});
			return send.join(',');
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

		if(!val)
			return;

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1], 1);
		});

		_dialogLoad(send);
	})
	.on('click', '.dialog-setup', function() {//нажатие на кнопку, иконку для открытия редактирования диалога
		var t = $(this),
			val = t.attr('val'),
			send = {
				op:'dialog_setup_load',
				busy_obj:t,
				busy_cls:t.hasClass('icon') ? 'spin' : '_busy'
			};

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1], 1);
		});

		_post(send, _dialogSetup);
	})
	.on('click', '.image-open', function() {//открытие изображения при нажатии на миниатюру
		var t = $(this),
			id = t.attr('val');
		_dialogLoad({
			dialog_id:65,
			get_id:id,
			busy_obj:t.parent()
		});
	});



