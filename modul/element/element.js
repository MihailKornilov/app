/* Все элементы визуального отображения, используемые в приложении */
var DIALOG = {},    //массив диалоговых окон для управления другими элементами
	ELM_RELOAD = {},//массив элементов, ожидающих перезагрузки. В виде: [id кто перезагружает] = id кого перезагружают
					//пезагрузка происходит при помощи фукнции _elemReload

	ELEM_COLOR = {
		"":["#000", "Чёрный"],
		"clr9":["#555", "Тёмно-серый"],
		"clr1":["#888", "Серый"],
		"clr2":["#aaa", "Бледный"],
		"clr3":["#ccc", "Совсем бледный"],
		"clr15":["#2B587A", "Тёмно-синий"],
		"clr14":["#07a", "Синий"],
		"clr13":["#770", "Салатовый"],
		"clr11":["#090", "Зелёный"],
		"clr10":["#aea", "Ярко-зелёный"],
		"clr5":["#e22", "Красный"],
		"clr8":["#800", "Тёмно-красный"],
		"clr7":["#a66", "Тёмно-бордовый"],
		"clr6":["#c88", "Бордовый"]
	},

	_color = function(v, func) {
		$(document)
			.off('mouseenter', '._color td')
			.on('mouseenter', '._color td', function() {//показ цветов при наведении
				var td = $(this),
					v = td.attr('val');
				td._tool(ELEM_COLOR[v][1]);
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
	_hintPaste = function(o) {//вставка полученных подсказок в массив
		if(!o.hint)
			return;

		if(!window.HINT)
			HINT = {};

		_forIn(o.hint, function(v, i) {
			HINT[i] = v;
		});
	},

	_dialog = function(o) {//диалоговое окно
		o = $.extend({
			top:100,
			width:0,    //ширина диалога. Если 0 = автоматически

			dialog_id:0,//id диалога, загруженного из базы
			setup_access:0,//показ иконки настройки диалога

			color:'',   //цвет диалога: заголовка и кнопки
			head:'head: Название заголовка',
			content:'<div class="pad30 clr2">content: содержимое центрального поля</div>',

			butSubmit:'Внести',
			butCancel:'Отмена',
			submit:function() {},
			cancel:dialogClose,

			send:{} //исходные данные, полученные при открытии предыдущего диалога. Для кнопки редактирования.
		}, o);

		var DIALOG_NUM = $('._dialog').length,
			editShow = !DIALOG_NUM && o.setup_access && _cookie('face') == 'site',
			html =
			'<div class="_dialog-back"></div>' +
			'<div class="_dialog">' +
				'<iframe class="frameD" name="frameD' + DIALOG_NUM + '"></iframe>' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh pl"></a></div>' +
					'<div class="submit fr curP' + _dn(o.butSubmit) + '"><a class="icon icon-ok wh pl"></a></div>' +
					'<div class="edit fr curP' + _dn(editShow) + '"><a class="icon icon-edit wh pl"></a></div>' +
					'<div class="fs14 clr4">' + o.head + '</div>' +
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
			iconSubmit = dialog.find('.head .submit'),
			content = dialog.find('.content'),
			width = o.width || Math.round(content.width()),
			bottom = dialog.find('.btm'),
			butSubmit = bottom.find('.submit'),
			butCancel = bottom.find('.cancel'),
			//функция, которая выполняется при отмене или закрытии диалога
			closeFunc = function() {},
			w2 = Math.round(width / 2), //ширина/2. Для определения положения по центру
			vkScroll = VK_SCROLL > 110 ? VK_SCROLL - 110 : 0,//корректировка скролла VK
			BUSY_OBJ,
			BUSY_CLS;

		dialog.find('.submit').click(function() {
			if(butSubmit.hasClass('_busy'))
				return;
			if(iconSubmit.hasClass('spin'))
				return;

			switch($(this)[0].tagName) {
				default:
				case 'BUTTON':
					BUSY_OBJ = $(this);
					BUSY_CLS = '_busy';
					break;
				case 'DIV':
					BUSY_OBJ = $(this).find('.icon');
					BUSY_CLS = 'spin';
					break;
			}
			o.submit();
			if(o.dialog_id)
				delete DIALOG[o.dialog_id];
		});
		dialog.find('.close').click(dialogClose);
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
		iconSubmit.mouseover(function() {
			$(this)._hint({
				msg:o.butSubmit,
				pad:10,
				side:'left',
				delayShow:500,
				show:1
			});
		});

		$('._hint').remove();
		DBACK.css({
				'z-index':ZINDEX + 3,
				height:$(document).height()
			 });

		dialog.css({
			width:width + 'px',
			top:$(window).scrollTop() + vkScroll + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});

		ZINDEX += 10;

		//корректное закрытие окна клавишей ESC
		var esci = ZINDEX;
		$(document)
			.off('keyup.esc' + esci)
			.on('keyup.esc' + esci, function(e) {
				if(e.which != 27)
					return;
				if(esci != ZINDEX)
					return;
				dialogClose();
			});


		_fbhs();
		window['frameD' + DIALOG_NUM].onresize = _fbhs;

		function dialogClose() {
			DBACK.remove();
			dialog.remove();
			$(document).off('keyup.esc' + esci);
			ZINDEX -= 10;
			if(o.dialog_id)
				delete DIALOG[o.dialog_id];
			closeFunc();
		}
		function dialogErr(msg) {
			BUSY_OBJ._hint({
				msg:msg,
				color:'clr5',
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
				send.busy_obj = BUSY_OBJ;
				send.busy_cls = BUSY_CLS;
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
				dialog.find('.head .clr4').html(v);
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
			spisok:o.menu,
			func:function() {
				_dialogHeightCorrect(DLG);
			}
		});
		DLG('#menu_sa')._menu({
			type:3,
			spisok:o.menu_sa
		});
		DLG('#menu_service')._menu({
			type:3,
			spisok:[
				{id:1,title:'Настройка'},
				{id:2,title:'Статистика'}
			]
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
				spisok:o.page_list
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
						t.find('.clr2')._dn(!res.tmp);
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

		DLG('#insert_unit_change').click(function() {
			_dialogLoad({
				dialog_id:42,
				dss:o.dialog_id,
				edit_id:o.dialog_id,
				busy_obj:$(this)
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
		DLG('#spisok_on')._check({
			func:function(v) {
				DLG('.tr-spisok-col')._dn(v);
			}
		});

		DLG('#open_auto')._check({
			func:function(v) {
				DLG('#tr-open-auto')._dn(v);
			}
		});
		DLG('#open_filter').click(function() {
			_dialogLoad({
				dialog_id:41,
//				block_id:OBJ.srce.block_id,
				dss:o.dialog_id,
//				dop:ATR_CMP.val(),
				busy_obj:$(this),
				busy_cls:'hold',
				func_save:function(res) {
				}
			});
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

		DLG('#IUID_dlg_id')._select({
			width:250,
			title0:'диалог не выбран',
			spisok:o.dlg_spisok_on,
			func:function(v) {
				DLG('.tr-iuid')._dn(v);
				DLG('#insert_unit_id_set_elem_id')._select(0);

				if(!v)
					return

				DLG('#insert_unit_id_set_elem_id')._select('process');
				var send = {
					op:'dialog_setup_cols',
					dialog_id:v
				};
				_post(send, function(res) {
					DLG('#insert_unit_id_set_elem_id')._select('spisok', res.spisok);
				});
			}
		});
		DLG('#insert_unit_id_set_elem_id')._select({
			width:250,
			title0:'колонка не выбрана',
			spisok:o.iuid_cols
		});

		//Загрузка изображения из файла
		DLG('form input').change(function() {
			var elimg = DLG('#element_image_id'),
				load = elimg.next(),
				xhr = new XMLHttpRequest(),
				data = new FormData;

			load.addClass('_busy');
		    xhr.open('post', AJAX, true);

		    data.append('f1', this.files[0]);
		    data.append('op', 'element_image_upload');
		    xhr.send(data);

		    xhr.addEventListener('load', function() {
		        load.removeClass('_busy');
		        var res = JSON.parse(xhr.responseText);
				if(!res.success) {
					load._hint({
						msg:res.text,
						pad:10,
						color:'clr5',
						show:1
					});
					return;
				}

				elimg.val(res.img_id);
				load.addClass('loaded')
					.find('img').remove();
				load.find('.eimg').append(res.html);
		    });
		});
		DLG('.eimg-del').click(function() {
			DLG('.el-img')
				.removeClass('loaded')
				.prev().val(0);
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

		BLKK = _objUpd(BLKK, o.blk);
		ELMM = _objUpd(ELMM, o.cmp);

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
				insert_unit_id_set_elem_id:DLG('#insert_unit_id_set_elem_id').val(),

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
				open_auto:DLG('#open_auto').val(),

				table_1:DLG('#table_1').val(),
				app_any:DLG('#app_any').val(),
				spisok_any:DLG('#spisok_any').val(),
				sa:DLG('#sa').val(),
				parent_any:DLG('#parent_any').val(),
				clone_on:DLG('#clone_on').val(),

				element_group_id:DLG('#element_group_id').val(),
				element_image_id:DLG('#element_image_id').val(),
				element_width:DLG('#element_width').val(),
				element_width_min:DLG('#element_width_min').val(),
				element_type:DLG('#element_type').val(),
				element_afics:DLG('#element_afics').val(),
				element_hidden:DLG('#element_hidden').val(),

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
			busy_cls:'_busy',/* класс, показвыающий процесс ожидания
							    _busy: для блоков
							    hold:  для input
							    spin:  для иконок
							 */

			func_open_before:function() {},//функция, выполняемая перед открытием диалога
			func_open:function() {},//функция, выполняемая после открытия диалога
			func_save:null          //функция, применяется после успешного выполнения диалога (после нажатия кнопки submit)
		}, o);

		if(o.dop)
			delete o.dop.jselm;

		o = _objCopy(send);

		_post(send, function(res) {
			res.send = o;
			o.func_open_before(res);
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

		BLKK = _objUpd(BLKK, o.jsblk);
		ELMM = _objUpd(ELMM, o.jselm);

		_ELM_JS(o.jselm, o);
		_ELM_FOCUS(o.focus);

		//подсветка блока, если нужно на него указать
		if(o.send.block_flash)
			_attr_bl(o.send.block_flash)._flash({color:'red'});

		//вставка подсказок, прикреплённых к блоками или элементам
		_hintPaste(o);

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
			_forIn(o.jselm, function(el) {
				var ATR_CMP = _attr_cmp(el.id);

				switch(el.dialog_id) {
					//многострочный текст
					case 5:
						if(!el.num_2)
							break;
						if(!ATR_CMP)
							return;
						send.cmp[el.id] = _attr_el(el.id).find('.ck-content').html();
						return;
					//подключаемая функция
					case 12:
						var func = el.txt_1 + '_get';
						if(window[func])
							send.vvv[el.id] = window[func](el, o);
						break;
					//Select: выбор записи из другого списка (передача текста условии флага num_7)
					case 29:
						if(!el.num_7)
							break;
						send.vvv[el.id] = ATR_CMP._select('inp');
						break;
					//быстрое формирование списка
					case 95:
						send.vvv[el.id] = _EL95_GET(el);
						return;
				}

				if(ATR_CMP)
					send.cmp[el.id] = ATR_CMP.val();
			});

			dialog.post(send, function(res) {
				//если присутствует функция, выполняется она. Для того, чтобы продолжить выполнение после функции, нужно чтобы она вернула любой результат
				if(o.send.func_save)
					if(!o.send.func_save(res))
						return;
//return;
				_dialogOpenSubmitAction(res);

				//обновление JS блоков и элементов, если элемент вносился или изменялся
				if(res.js_upd) {
					BLKK = _objUpd(BLKK, res.jsblk);
					ELMM = _objUpd(ELMM, res.jselm);
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
					page_id:PAGE_ID,
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
			//обновление страницы
			case 1:
				console.log(DIALOG)
				_forIn(DIALOG, function() {

				});
				$('#_content').html(res.content);
				break;
			//переход на страницу
			case 2:
				var url = URL + '&p=' + res.action_page_id;
				if(res.unit)
					url += '&id=' + res.unit.id;
				location.href = url;
				break;
			//обновление содержимого блоков
			case 3:
				var bln = '#block-level-' + res.obj_name;
				$(bln).after(res.level).remove();
				$(bln)
					.find('.block-grid-on')
					.removeClass('grey')
					.trigger('click');
				break;
			//обновление исходного диалога
			case 4:
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

	ATTR_EL = function(id, nohash) {
		return (nohash ? '' : '#') + 'el_' + id;
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
	_attr_bl = function(block_id) {//аттрибут блока
		block_id = _num(block_id);
		if(!block_id)
			return false;

		var send = $(ATTR_BL(block_id));

		if(!send.length)
			return false;

		return send;
	},

	//применение функций после печати элементов
	_ELM_JS = function(ELM, OBJ) {
		_forIn(ELM, function(el) {
			var func = window['_EL' + el.dialog_id];
			if(!func)
				return;
			if(!OBJ)
				OBJ = {};
			func(el, OBJ);
		});
	},

	//установка фокуса на указанный элемент
	_ELM_FOCUS = function(elem_id) {
		if(!elem_id)
			return;
		var ATTR = _attr_cmp(elem_id);
		if(!ATTR)
			return;
		var l = ATTR.val().length;
		ATTR.focus();
		ATTR[0].setSelectionRange(l, l);
	},

	//применение действий, привязанных к элементам
	_ELM_ACT = function(el, v, period) {
		/*
			period - если указано, то некоторые действия применяются только до либо после AJAX
				before
				after
		*/
		if(!el.action)
			return;

		_forN(el.action, function(act) {
			var func = window['_ACT' + act.dialog_id];
			if(!func)
				return;

			act.el = el;

			func(act, v, period);
		});
	},

	//показ/скрытие блоков
	_ACT201 = function(act, v, period) {
		if(period == 'before')
			return;

		var is_show = 0;//по умолчанию скрывать

		//ДЕЙСТВИЕ
		switch(act.apply_id) {
			//скрыть
			case 2783:
			default:
				break;
			//показать
			case 2784:
				is_show = 1;
				break;
		}

		if(!act.initial_id)
			return;

		//значение установлено
		if(v) {
			//любое значение
			if(act.initial_id != -2 && act.initial_id != v) {
				if(act.revers)
					is_show = is_show ? 0 : 1;
				else
					return;
			}
		}

		//значение НЕ установлено
		if(!v) {
			if(act.initial_id != -1) {
				if(act.revers)
					is_show = is_show ? 0 : 1;
				else
					return;
			}
		}


		//ПРОЦЕСС
		_forN(_blockObj(act.target_ids), function(oo) {
			if(!oo.obj.length)
				return;

			switch(act.effect_id) {
				//исчезновение/появление
				case 44:
				case 715:
				case 2789:
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
					oo.obj['slide' + (is_show ? 'Down' : 'Up')](300);
					return;
				default:
					if(!oo.slide) {
						oo.obj._dn(is_show, 'vh');
						return;
					}
					oo.obj._dn(is_show);
			}
		});
	},

	//установка/снятие значений
	_ACT202 = function(act, v, period) {
		if(period == 'after')
			return;

		var is_set = act.apply_id == -1 ? 0 : 1;//начальное действие, которое будет установлено элементу-получателю

		//условие для совершения действия
		switch(act.initial_id) {
			//значение было сброшено
			case -1:
				if(v && act.revers) {
					is_set = is_set ? 0 : 1;
					break;
				}
				if(v)
					return;
				break;
			//установлено какое-то значение
			case -2:
				if(!v && act.revers) {
					is_set = is_set ? 0 : 1;
					break;
				}
				if(!v)
					return;
				break;
			default:
				if(!v)
					return;
				if(v != act.initial_id)
					return;
				v = act.apply_id;
				break;
		}

		_forIn(_idsAss(act.target_ids), function(i, id) {
			var EL = ELMM[id];
			//свои способы действия на каждый элемент
			switch(EL.dialog_id) {
				//галочка
				case 1:
					_attr_cmp(id)
						._check(is_set)
						._check(is_set);
					break;
				//выбор значений
				case 13:
					_attr_cmp(id)
						.val(0)
						.next().find('.inp').val('')
						.prev()._dn();
					break;
				//select - привязанный список
				case 29:
					_attr_cmp(id)._select(v);
					break;
				//фильтр-галочка
				case 62:
					_attr_cmp(id)._check(is_set);
					FILTER[EL.num_1][id] = is_set;
					break;
			}
		});
	},

	//открытие диалога
	_ACT205 = function(act, v) {
		if(v != act.initial_id)
			return;

		var dlg_id = _num(act.target_ids);
		if(!dlg_id)
			return;

		var send = {
			dialog_id:dlg_id,
			get_id:act.apply_id ? GET_ID : 0,
			edit_id:act.effect_id ? GET_ID : 0,
			del_id:act.revers ? GET_ID : 0,
			busy_obj:_attr_bl(act.el.block_id)
		};

		_dialogLoad(send);
	},

	//установка фокуса
	_ACT206 = function(act) {
		var elem_id = _num(act.target_ids);
		if(!elem_id)
			return;

		if(ELMM[elem_id].dialog_id == 29)
			return _attr_cmp(elem_id)._select('focus');

		_attr_cmp(elem_id).select();
	},

	//открытие документа
	_ACT207 = function(act, v) {
		if(v != act.initial_id)
			return;

		var doc_id = _num(act.target_ids);
		if(!doc_id)
			return;

		location.href = URL +
			'&p=9' +
			'&doc_id=' + doc_id +
			(GET_ID ? '&id=' + GET_ID : '') +
			'&pfrom=' + PAGE_ID;
	},

	//формула
	_ACT208 = function(act) {
		var V = 0,
			v1 = act.v1.split(','),
			cc = (v1.length - 1) / 2;
		for(var n = 0; n <= cc; n++) {
			var elid = _num(v1[n*2]);
			if(!elid)
				continue;

			var VN = 0,
				EL = _attr_cmp(elid);
			if(EL)
				VN = _cena(EL.val(), true);
			else {
				EL = _attr_el(elid);
				if(EL)
					VN = _cena(EL.html(), true);
			}

			if(!EL)
				continue;

			if(!n)
				V = VN;
			else
				switch(_num(v1[n*2-1])) {
					//сложение
					case 1: V = V + VN; break;
					//вычитание
					case 2: V = V - VN; break;
					//умножение
					case 3: V = V * VN; break;
					//деление
					case 4: V = V / VN; break;
				}
		}

		V = Math.round(V*100)/100;

		var EA = _attr_cmp(act.apply_id);
		if(EA)
			return EA.val(V);

		_attr_el(act.apply_id).html(V);
	},
	// [208] Настройка формулы
	PHP12_action208_formula = function(el, vvv, obj) {
		var ATR_EL = _attr_el(el.id),
			V1 = vvv.length ? vvv[0] : {},
			ACT208_V = function(n, v_id, v_name) {//печать значения
				return '<input type="hidden" class="v208" val="' + n + '" value="' + (v_id || 0) + '" />' +
					'<div class="_selem dib prel bg-fff over3 mb10">' +
						'<div class="icon icon-star pabs"></div>' +
						'<div class="icon icon-del pl pabs' + _dn(v_id) + '"></div>' +
						'<input type="text" readonly class="w125 curP clr11" placeholder="Значение ' + n + '" value="' + (v_name || '') + '" />' +
					'</div>';
			},
			ACT208_VSEL = function() {//выбор значения
				ATR_EL.find('._selem:last').click(function() {
					var t = $(this),
						cmp = t.prev(),
						inp = t.find('input'),
						del = t.find('.icon-del');

					_dialogLoad({
						dialog_id:11,
						block_id:obj.send.block_id,
						element_id:obj.send.element_id,

						dop:{
							mysave:1,
							allow:'8,10,11,29,59,54,55,27',
							sel:cmp.val()
						},

						busy_obj:inp,
						busy_cls:'hold',
						func_save:function(res) {
							cmp.val(res.v);
							inp.val(res.title);
							del._dn(1);
						}
					});
				});
				//очистка выбранного значения
				ATR_EL.find('._selem:last .icon-del').click(function(e) {
					e.stopPropagation();
					var t = $(this);
					t._dn();
					t.next().val('');
					t.parents('._selem').prev().val(0);
				});
			},
			html =
				ACT208_V(1, V1.elem_id, V1.elem_name) +
				'<div class="icon icon-add ml15 pl tool" data-tool="Добавить значение"></div>' +
				'<div class="icon icon-del-red ml3 pl tool" data-tool="Удалить последнее значение"></div>',
			ICON_ADD = ATR_EL.html(html).find('.icon-add'),
			ICON_DEL = ICON_ADD.next(),
			NUM = 2;

		ACT208_VSEL();
		ICON_ADD.click(_ADD);
		ICON_DEL.click(function() {
			var v208 = ATR_EL.find('.v208:last'),
				n = _num(v208.attr('val'));

			if(n == 1)
				return;

			v208.prev().remove();
			v208.next().remove();
			v208.remove();
			NUM--;

			if(n == 3)
				ICON_DEL._dn();
		});

		if(!vvv.length)
			_ADD();
		else
			_forN(vvv, function(v, n) {
				if(n)
					_ADD(v);
			});

		function _ADD(v) {
			v = $.extend({
				elem_id:0,
				elem_name:'',
				znak:1
			}, v);
			html =
				'<div class="dib w15 ml10">' +
					'<input type="hidden" id="znak' + NUM + '" value="' + v.znak + '" />' +
				'</div>' +
				ACT208_V(NUM, v.elem_id, v.elem_name);

			ICON_ADD.before(html);

			$('#znak' + NUM)._dropdown({
				title0:'выбор знака',
				spisok:[
					{id:1,title:'<b class="fs17">+</b>'},
					{id:2,title:'<b class="fs17">-</b>'},
					{id:3,title:'<b class="fs17">*</b>'},
					{id:4,title:'<b class="fs17">/</b>'}
				]
			});

			ACT208_VSEL();

			NUM++;

			ICON_DEL._dn(NUM > 3);
		}
	},
	PHP12_action208_formula_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('.v208'), function(sp) {
			var elem_id = _num(sp.val()),
				n = _num(sp.attr('val')) + 1;

			send.push(elem_id);

			if(!$('#znak' + n).length)
				return;

			send.push(_num($('#znak' + n).val()));
		});
		return send.join();
	},


	//вставка значения в блок
	_ACT209 = function(act, v) {
		//условие для совершения действия
		switch(act.initial_id) {
			//значение было сброшено
			case -1:
				if(v)
					return;
				break;
			//установлено какое-то значение
			case -2:
				if(!v)
					return;
				break;
			case 0: return;
			default:
				if(!v)
					return;
				if(v != act.initial_id)
					return;
				break;
		}

		var BL = BLKK[_num(act.target_ids)];
		if(!BL)
			return;

		var V = _elemUidsJS(act.v1, AG.unit);

		//вставка в элемент, если есть
		if(BL.elem_id) {
			var ATTR = _attr_cmp(BL.elem_id);
			if(ATTR.length)
				return ATTR.val(V);

			return _attr_el(BL.elem_id).html(V);
		}

		_attr_bl(BL.id).html(V);
	},

	//обновление содержимого блока
	_ACT228 = function(act, v) {
		var block_id = _num(act.target_ids),
			TRG = _attr_bl(block_id),
			send = {
				op:'act228_block_upd',
				block_id:block_id,
				busy_obj:TRG
			};

		if(!TRG)
			return;

		_post(send, function(res) {
			TRG.html(res.html);
		});
	},


	//---- ДЕЙСТВИЯ ДЛЯ БЛОКОВ
	_BLK_ACT = function(bo, block_id, unit_id, skip) {//выполнение действия при нажатии на блок
		var bl = BLKK[block_id]
		if(!bl)
			return;

		_forN(bl.action, function(act) {
			//пропуск действий, которые были отмечены через PHP фильтрами
			if(skip[act.id])
				return;

			var func = window['_ACT' + act.dialog_id];
			if(!func)
				return;

			act.bo = $(bo); //block object - this
			act.bl = bl;
			act.unit_id = unit_id;

			func(act);
		});
	},

	//показ/скрытие блоков
	_ACT211 = function(act) {
		var to_show = 0;//скрывать или показывать блоки. По умолчанию скрывать.

		//ДЕЙСТВИЕ
		switch(act.apply_id) {
			//скрыть
			case 3166:
			default:
				break;
			//показать
			case 3167:
				to_show = 1;
				break;
		}

		if(act.revers) {
			if(act.on)
				to_show = !to_show;

			act.on = act.on ? 0 : 1;

			//получить и запомнить позицию отображения блока
			if(act.v1) {
				var ass = _idsAss(act.target_ids);
				_forIn(ass, function(i, id) {
					var on = _cookie('ACT211_' + id) * 1;
					if(on !== undefined) {
						to_show = !on;
						act.on = on ? 0 : 1;
					}
					return false;
				});

				_forIn(ass, function(i, id) {
					_cookie('ACT211_' + id, act.on);
				});
			}
		}


		//ПРОЦЕСС
		_forN(_blockObj(act.target_ids), function(oo) {
			if(!oo.obj.length)
				return;

			switch(act.effect_id) {
				//исчезновение/появление
				case 3171:
					oo.obj._dn(1, 'vh');
					oo.obj.animate({opacity:to_show}, 300, function() {
						oo.obj._dn(to_show, 'vh');
					});
					return;
				//сворачивание/разворачивание
				case 3172:
					if(!oo.slide) {
						oo.obj._dn(to_show, 'vh');
						return;
					}
					oo.obj['slide' + (to_show ? 'Down' : 'Up')](300);
					return;
				default:
					if(!oo.slide) {
						oo.obj._dn(to_show, 'vh');
						return;
					}
					oo.obj[to_show ? 'show' : 'hide']();
			}
		});
	},

	//установка значения элементу
	_ACT212 = function(act) {
		var target_id = _num(act.target_ids),
			ATR_CMP = _attr_cmp(target_id),
			EL = ELMM[target_id],
			v = act.apply_id;

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
			case 29:
				ATR_CMP._select(v);
				return;
		}
	},

	//блокировка элементов
	_ACT213 = function(act) {
		/*
			3365 - заблокировать
			3366 - разблокировать
		*/
		var lock = act.apply_id == 3365;

		_forIn(_idsAss(act.target_ids), function(n, id) {
			var EL = ELMM[id];
			if(!EL)
				return;
			switch(EL.dialog_id) {
				case 5:
				case 8:
					_attr_cmp(id).attr('disabled', lock);
					break;
			}
		});
	},

	//переход на страницу
	_ACT214 = function(act) {
		var page_id = _num(act.target_ids);
		if(!page_id)
			return;

		var link = '&p=' + page_id + '&pfrom=' + PAGE_ID;

		if(act.apply_id)
			link += '&id=' + act.unit_id;

		act.bo.addClass('_busy');
		location.href = URL + link;
	},

	//открытие диалога
	_ACT215 = function(act) {
		var dlg_id = _num(act.target_ids);
		if(!dlg_id)
			return;
		var send = {
			dialog_id:dlg_id,
			block_id:act.bl.id,
			busy_obj:act.bo
		};

		//блок передаёт id записи для отображения
		if(act.apply_id)
			send.get_id = act.unit_id;
		//блок передаёт id записи для редактирования
		if(act.effect_id)
			send.edit_id = act.unit_id;
		//блок передаёт id записи для удаления
		if(act.revers)
			send.del_id = act.unit_id;

		_dialogLoad(send);
	},

	//установка фокуса на элемент
	_ACT216 = function(act) {
		var elem_id = _num(act.target_ids);
		if(!elem_id)
			return;

		if(ELMM[elem_id].dialog_id == 29)
			return _attr_cmp(elem_id)._select('focus');

		_attr_cmp(elem_id).focus();
	},

	//открытие документа
	_ACT217 = function(act) {
		var doc_id = _num(act.target_ids);
		if(!doc_id)
			return;

		location.href = URL +
			'&p=9' +
			'&doc_id=' + doc_id +
			(act.unit_id ? '&id=' + act.unit_id : '') +
			'&pfrom=' + PAGE_ID;
	},

	//обновление содержимого блоков
	_ACT219 = function(act) {
		//сохранение исходных заливок блоков, чтобы потом их восстанавливать
		var actName = 'ACT_' + act.id,
			attrBgId = 'dbg' + act.unit_id,
			bg;
		if(!window[actName])
			window[actName] = {};

		if(!$('#' + attrBgId).length) {
			bg = act.bo.css('background-color');
			act.bo
				.attr('id', attrBgId)
				.attr('data-bg', bg);
			window[actName][act.unit_id] = true;
		}

		act.bo.attr('data-bg', act.bo.css('background-color'));

		var ids = _ids(act.target_ids);
		if(!ids)
			return;

		var send = {
			op:'act219_block_upd',
			src_id:act.bl.id,
			action_id:act.id,
			ids:ids,
			unit_id:act.unit_id,
			busy_obj:act.bo
		};
		_post(send, function(res) {
			_forIn(res.blk, function(sp, id) {
				var PL = _attr_bl(id).closest('.bl-div');
				PL.css('background-color', '#999990');
//				PL.after(sp).remove();
			});
			//восстановление окраски других блоков
			_forIn(window[actName], function(sp, uid) {
				if(act.unit_id == uid)
					return;

				bg = $('#dbg' + uid).attr('data-bg');
				$('#dbg' + uid).css('background-color', bg);
			});
			if(res.bg)
				act.bo.css('background-color', res.bg);
		});
	},

	_blockObj = function(blk) {//получение $(obj) блоков для показа или скрытия
		if(typeof blk != 'object')
			blk = _idsAss(blk);

		var arr = [],
			TRG = _objCopy(blk),
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
	_elemUidsJS = function(ids, unit) {//получение значения записи по идентификаторам элементов (аналог _elemUids в PHP)
		var txt = '';
		_forN(ids.split(','), function(id, n) {
			//todo временно
			if(!n)
				return;

			var EL = ELMM[id];
			if(!EL)
				return;

			//если имя колонки не получено, поиск останавливается
			var col = EL.col;
			if(!col)
				return false;

			//если значение не получено, поиск останавливается
			if(unit[col] === undefined)
				return false;

			unit = unit[col];

			if(typeof unit == 'object')
				return;

			txt = unit;
		});

		return txt;
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

	//[1] галочка
	_EL1 = function(el) {
		if(!el.action)
			return;
		$(ATTR_CMP(el.id))._check({
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
	},

	//[5] textarea
	_EL5 = function(el) {
		if(!el.num_2)
			return $(ATTR_CMP(el.id))._autosize();

		//num_2: форматирование текста
		ClassicEditor.create(document.querySelector(ATTR_CMP(el.id)), {
			toolbar:[
				'heading',
				'|',
				'bold', 'italic', 'Underline',
				'|',
				'bulletedList', 'numberedList', 'blockQuote',
				'|',
				'link',
				'|',
				'undo', 'redo'
			],
			heading:{
				options:[
					{model:'paragraph', title:'Параграф', class:'ck-heading_paragraph'},
					{model:'heading1', view:'h1', title:'Заголовок 1', class:'ck-heading_heading1'},
					{model:'heading2', view:'h2', title:'Заголовок 2', class:'ck-heading_heading2'},
					{model:'heading3', view:'h3', title:'Заголовок 3', class:'ck-heading_heading3'}
				]
			}
		});
	},

	//[6] select - выбор страницы
	_EL6 = function(el) {
		//если выбирается страница для ссылки, то добавляется вариант: 3 => Автоматически
		if(el.id == 1959)
			el.vvv.unshift({
				id:3,
				title:'Автоматически',
				content:'<b class="clr11">Автоматически</b>'
			});

		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			spisok:el.vvv,
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
	},

	//[7] Быстрый поиск
	_EL7 = function(el) {
		var process = 0,
			v_last = FILTER[el.num_1] ? FILTER[el.num_1][el.id] : '',
			upd = function(v) {
				process = 1;
				FILTER[el.num_1][el.id] = v;
				_spisokUpdate(el.num_1, function() {
					process = 0;
					if(v_last != v)
						upd(v_last);
				});
			};
		$(ATTR_CMP(el.id))._search({
			func:function(v) {
				if(v_last == v)
					return;

				v_last = v;

				if(process)
					return;

				upd(v);
			}
		});
	},

	//[8] однострочное текстовое поле
	_EL8 = function(el) {
		if(!el.action && !el.num_7)
			return;

		$(ATTR_CMP(el.id)).keyup(function(e) {
			if(el.action)
				_ELM_ACT(el, $(ATTR_CMP(el.id)).val());

			//нажатие на Enter
			if(el.num_7)
				if(e.which == 13)
					OBJ.dlg.go();
		});
	},

	// [11] Выбор значения из диалога
	PHP12_v_choose = function(el, vvv, obj) {
		ELMM = _objUpd(ELMM, vvv.jselm);

		var D = obj.dlg.D,
			VC = D(ATTR_EL(el.id)).find('.elm-choose'),//элементы в открытом диалоге для выбора
			DSS = 0,
			_nest = function(v, dbl) {//разрешение прохода по списку (открытие второго диалога)
				if(v < 0)
					return v;
				if(vvv.sev)
					return false;
				if(!ELMM[v].issp)
					return false;
				if(vvv.nest)
					return true;
				if(!dbl)
					return false;
				return true;
			};

		//описание глобальных переменных при открытии исходного (первого, невложенного) диалога
		if(vvv.first) {
			var spisok = [
				{id:1,title:'Глобальные значения'},
				{id:2,title:'Стандартные значения'},
				{id:3,title:'Исходный диалог'},
				{id:4,title:'Родительский диалог'}
			];

			if(!D(ATTR_EL(el.id)).find('.choose-menu-4').length)
				spisok.splice(3, 1);

			D(ATTR_EL(el.id)).find('#choose-menu')._menu({
				spisok:spisok
			});

			V11_CMP = D(ATTR_CMP(el.id));   //переменная в исходном диалоге для хранения значений
			V11_DLG = [];                   //массив диалогов, открывающиеся последовательно
			V11_V = vvv.sev ? _idsAss(vvv.sel) : []; //массив выбранных значений
			V11_COUNT = 0;                  //счётчик открытых диалогов

			if(vvv.sel)
				V11_CMP.val(vvv.sel);

			vvv.first = 0;
		}

		//выбор одного из элеметов
		VC.on('click dblclick', function(e) {
			var t = $(this),
				v = _num(t.attr('val'), true);

			if(vvv.sev) {//выбор нескольких значений
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
			switch(_nest(v, e.type == 'dblclick')) {
				default:
					if(v == -21)
						DSS = 111;
					else if(v < 0)
						return;
					break;
				case true:
					DSS = ELMM[v].num_1;
					break;
				case false:
					if(e.type == 'dblclick')
						obj.dlg.go();
					return;
			}

			V11_COUNT++;

			_dialogLoad({
				dialog_id:11,
				block_id:obj.srce.block_id,
				dss:DSS,
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
	PHP12_v_choose_get = function(el) {//отправка значений настройки для определения типа сохранения данных. Конкретно по "mysave"
		delete el.vvv.jselm;
		return el.vvv;
	},

	//[12] Функция
	_EL12 = function(el, OBJ) {
		if(!window[el.txt_1])
			return;
		window[el.txt_1](el, el.vvv, OBJ);
	},

	//[13] Выбор элемента из диалога или страницы
	_EL13 = function(el, OBJ) {
		var P = $(ATTR_CMP(el.id)).next(),
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
					_attr_cmp(el.id, 1)._hint({
						msg:'Не выбрано значение',
						color:'clr5',
						pad:10,
						show:1
					});
					return;
				}
			}

			_dialogLoad({
				dialog_id:11,
				block_id:OBJ.srce.block_id,
				element_id:OBJ.srce.element_id,
				dss:OBJ.srce.dss,

				dop:{
					mysave:1,
					is13:el.id,
					nest:_num(el.num_5),            //выбор значений во вложенных списках
					sev:_num(el.num_6),             //выбор нескольких значений
					dlg24:dlg24,                    //выбранный диалог в селекте, на который указывает num_1
					sel:$(ATTR_CMP(el.id)).val(),              //выбранные элементы
					allow:el.num_2 ? el.txt_2 : ''  //id типов элементов, которые разрешено выбирать
				},

				busy_obj:INP,
				busy_cls:'hold',
				func_save:function(res) {
					$(ATTR_CMP(el.id)).val(res.v);
					INP.val(res.title);
					DEL._dn(1);
					_elemReload(el, res);
				}
			});
		});
		DEL.click(function(e) {
			e.stopPropagation();
			$(ATTR_CMP(el.id)).val(0);
			INP.val('');
			DEL._dn();
			_elemReload(el);
		});
	},

	//[16] Радио
	_EL16 = function(el) {
		if(!el.action)
			return;
		$(ATTR_CMP(el.id))._radio(function(v) {
			_ELM_ACT(el, v);
		});
	},
	//[16][17][18] Настройка значений
	PHP12_radio_setup = function(el, vvv, obj) {
		/*
			num_1 - использовать описание значений
		*/
		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить значение</div>',
			ATTR_EL = _attr_el(el.id),
			DL = ATTR_EL.append(html).find('dl'),
			BUT_ADD = ATTR_EL.find('div:last'),
			ID_NEXT = 1,//следующий идентификатор с учётом существующих
			NUM = 1;    //порядковый номер для визуального отображения

		BUT_ADD.click(valueAdd);

		if(!obj.unit.id) {
			valueAdd();
			valueAdd();
		} else {
			_forIn(vvv, function(v) {
				if(ID_NEXT <= v.id)
					ID_NEXT = v.id + 1;
			});
			_forIn(vvv, valueAdd);
		}

		function valueAdd(v) {
			v = $.extend({
				id:0,
				title:'имя значения ' + NUM,
				content:'',
				def:0,
				use:0
			}, v);

			if(!v.id)
				v.id = ID_NEXT++;

			DL.append(
				'<dd class="over1" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w90 clr1 r topi">Значение ' + NUM + ':' +
							'<td><input type="text" class="title w100p b" value="' + v.title + '" />' +
								'<textarea class="w100p min mtm1' + _dn(el.num_1) + '" placeholder="описание значения">' + v.content + '</textarea>' +
							'<td class="w15 topi">' +
								'<input type="hidden" class="def" id="el-def-' + NUM + '" value="' + v.def + '" />' +
							'<td class="w50 r top pt5">' +
					   (v.use ? '<div class="dib fs11 clr3 mr3 curD tool" data-tool="Использование">' + v.use + '</div>'
								:
								'<div class="icon icon-del pl tool" data-tool="Удалить значение"></div>'
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

	//[17] select - произвольные значения
	_EL17 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			spisok:el.vvv,
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
	},

	//[18] dropdown
	_EL18 = function(el) {
		$(ATTR_CMP(el.id))._dropdown({
			title0:el.txt_1,
			title0_hide:el.num_1,
			nosel:el.num_2,
			spisok:el.vvv,
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
	},

	//[23] Список ТАБЛИЦА
	_EL23 = function(el) {
		//сортировка значений
		if(el.num_8 == 6161)
			$(ATTR_EL(el.id)).find('ol:first').nestedSortable({
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
						elem_id:el.id,
						arr:$(this).nestedSortable('toArray'),
						busy_obj:$(ATTR_EL(el.id)),
						busy_cls:'spisok-busy'
					};
					_post(send);
				},

				expandedClass:'pb10',//раскрытый список
				errorClass:el.num_7 > 1 ? 'bg-fcc' : ''  //ошибка, если попытка переместить элемент на недоступный уровень
			});

		//выбор значений галочками
		_forN(el.vvv, function(sp) {
			if(sp.dialog_id != 91)
				return;

			_forEq($(ATTR_EL(el.id)).find('._check'), function(eq) {
				//получение id записи
				var tdid = eq.attr('id').split('_')[1];

				//выбор/снятие всех галочек
				if(tdid == 'all') {
					$('#sch' + sp.id + '_all')._check({
						func:function(v) {
							_forEq($(ATTR_EL(el.id)).find('._check'), function(eqAll) {
								var ch = eqAll.prev();
								if(ch.attr('id').split('_')[1] == 'all')
									return;
								ch._check(v);
							});
						}
					});
					return;
				}

				eq.prev()._check();
			});
			return false;
		});
	},
	PHP12_td_setup = function(el, vvv, obj) {//настройка ячеек таблицы [23]
		if(!obj.unit.id)
			return;

		var html = '<dl class="mt10"></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить колонку</div>',
			DL = _attr_el(el.id).append(html).find('dl'),
			CALC_DIV = _attr_el(el.id).find('.calc-div'),//div, в котором располагается визуальный подсчёт ячеек
			CALC_W = _num(CALC_DIV.html()),//изначальная ширина блока, в котором размещена таблица
			NUM = 1;

		//кнопка добавления новой ячейки
		_attr_el(el.id).find('div:last').click(tdAdd);

		//показ-скрытие настройки TH-заголовков
		_attr_cmp(531)._check({
			func:function(v) {
				obj.unit.num_5 = v;
				DL.find('.div-th-name')['slide' + (v ? 'Down' : 'Up')]();
			}
		});

		_forIn(vvv, tdAdd);
		tdCalc();

		//добавление новой колонки в таблицу
		function tdAdd(v) {
			v = $.extend({
				id:0,        //id элемента
				dialog_id:50,//id диалога, через который был вставлен этот элемент
				title:'',    //имя значения
				width:150,   //ширина колонки
				font:'',     //выделение: b, i, u
				color:'',    //цвет текста
				txt_7:'',    //TH-заголовок колонки
				txt_8:''     //позиция по горизонтали (l, center, r)
			}, v.id ? v : {});

			v.pos = v.txt_8;

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w25 r topi">' +
								'<b class="bnum fs15 clr9">' + NUM + '</b>:' +
							'<td><div style="width:' + v.width + 'px">' +
									'<div class="div-th-name' + _dn(_num(obj.unit.num_5)) + '">' +
										'<input type="text"' +
											  ' class="th-name w100p bg-gr2 center fs14 clr15 mb1"' +
											  ' placeholder="имя колонки"' +
											  ' value="' + v.txt_7 + '"' +
										' />' +
									'</div>' +
									'<input type="text"' +
										  ' id="' + ATTR_EL(v.id, true) + '"' +
										  ' class="inp w100p curP ' + v.font + ' ' + v.color + ' ' + v.txt_8 + '"' +
										  ' readonly' +
										  ' placeholder="значение не выбрано"' +
										  ' value="' + v.title + '"' +
									' />' +
								'</div>' +
							'<td class="w25 r top pt5">' +
								'<div class="icon icon-del pl tool-l" data-tool="Удалить колонку"></div>' +
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
						INP.attr('id', ATTR_EL(v.id, true));
						tdResize(DD);
						tdCalc();
					}
				});
			});

			INP.mouseenter(_tdCss);

			//сортировка колонок
			DL.sortable({
				handle:'.icon-move-y',
				update:tdCalc
			});

			//удаление элемента
			DL.find('.icon-del:last').click(function() {
				$(this).closest('DD').remove();
				tdCalc();
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
				maxWidth:500,
				grid:10,
				handles:'e',
				stop:tdCalc
			});
		}

		//пересчёт визуального отображения ячеек по диагонали
		function tdCalc() {
			var html = '',
				DIV_W = 600,
				i = DIV_W / CALC_W,
				FULL_W = 0,
				TDS = [],//массив ширин по каждой ячейке
				ALL_W = 0;//сумма ширины всех ячеек, кроме последней

			_forEq(DL.find('DD'), function(sp) {
				if(!_num(sp.attr('val')))
					return;
				var n = _num(sp.find('.bnum').html()),
					w = sp.find('.div-th-name').parent().width();
				TDS.push({
					n:n,
					w:Math.round(w*i)-1
				});
				FULL_W += w;
			});

			_forN(TDS, function(o, n) {
				var bg = 'ffc',
					line = ' line-r';
				if(FULL_W > CALC_W) {
					bg = 'fcc';
					i = CALC_W / FULL_W;
					o.w = Math.round(o.w*i);
				}
				if(FULL_W >= CALC_W) {
					ALL_W += (o.w+1);
					if(n == (TDS.length-1)) {
						line = '';
						o.w = DIV_W - ALL_W + o.w + 1;
					}
				}

				html += '<div class="h25 dib center bg-' + bg + line + '" style="width:' + o.w + 'px">' +
							'<div class="fs15 b clr9 pt5">' + o.n + '</div>' +
						'</div>';
			});
			CALC_DIV.html(html);
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
			v.font = _tdCssFontGet(inp);
			v.color = _tdCssColorGet(inp);

			//позиция
			var arr = ['center', 'r'];
			v.txt_8 = '';
			for(k in arr)
				if(inp.hasClass(arr[k]))
					v.txt_8 = arr[k];


			send.push(v);
		});

		return send;
	},

	//[24] select - выбор списка (все списки приложения)
	_EL24 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			blocked:el.noedit && unit.id,
			spisok:el.vvv,
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
	},

	//[26] SA: Select - выбор документа
	_EL26 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			spisok:el.vvv
		});
	},

	// [27] Баланс: сумма значений записи
	PHP12_balans_setup = function(el, vvv, obj) {
		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),

			znak = {
				'+':'+',
				'-':'-',
				'*':'<div class="fs11">&#9913;</div>',
				'/':'/'
			},
			znak_n = ['+','-','*','/'],
			znak_tool = {
				'+':'Прибавить',
				'-':'Вычесть',
				'*':'Умножить',
				'/':'Делить'
			},
			znak_color = {
				'+':'green',
				'-':'red',
				'*':'orange',
				'/':'blue'
			};

		BUT_ADD.click(valueAdd);

		if(!vvv.length) {
			valueAdd();
			valueAdd();
		} else
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				znak:'+',
				elem_id:0,
				title:''
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.elem_id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 pl5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w25 r">' +
								'<button class="vk short ' + znak_color[v.znak] +' w35 tool" data-tool="' + znak_tool[v.znak] + '">' + znak[v.znak] +'</button>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="значение не выбрано"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w50 r">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить значение"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp'),
				BUT = DD.find('button');
			INP.click(function() {
				_dialogLoad({
					dialog_id:11,
					block_id:obj.srce.block_id,
					dop:{
						mysave:1,
						nest:0,
						allow:'8,27,54,55',
						sel:DD.attr('val')   //id выбранного элемента (при редактировании)
					},
					busy_obj:INP,
					busy_cls:'hold',
					func_save:function(ia) {
						DD.attr('val', ia.v);
						INP.val(ia.title);
					}
				});
			});
			DL.sortable({
				axis:'y',
				handle:'.icon-move-y'
			});
			BUT.click(function() {
				var t = $(this),
					n = 0;

				if(t.hasClass('red'))
					n = 1;
				if(t.hasClass('orange'))
					n = 2;
				if(t.hasClass('blue'))
					n = 3;

				n++;
				if(n > 3)
					n = 0;

				t.html(znak[znak_n[n]]);
				t.removeClass('green red orange blue');
				t.addClass(znak_color[znak_n[n]]);
				t._tool(znak_tool[znak_n[n]]);
			});
			DD.find('.icon-del').click(function() {
				$(this).closest('DD').remove();
			});
		}
	},
	PHP12_balans_setup_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var elem_id = _num(sp.attr('val')),
				but = sp.find('button'),
				znak = '+';

			if(!elem_id)
				return;

			if(but.hasClass('red'))
				znak = '-';
			if(but.hasClass('orange'))
				znak = '*';
			if(but.hasClass('blue'))
				znak = '/';

			send.push({
				znak:znak,
				elem_id:elem_id
			});
		});
		return send;
	},

	//[28] Загрузка файла
	_EL28 = function(el) {
		var AT = _attr_el(el.id).find('._attach'),
			ATUP = AT.find('.atup'),    //div для загрузки
			BUT = AT.find('.vk'),
			FORM = AT.find('form'),
			FILE = AT.find('.at-file'), //input file
			ATV = AT.find('.atv'),      //загруженный файл
			atmr;

		AT.find('.icon-del-red').click(function() {
			ATUP._dn(1);
			ATV._dn();
			$(ATTR_CMP(el.id)).val(0);
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
					$(ATTR_CMP(el.id)).val(attach_id);
					var send = {
						op:'attach_get',
						id:attach_id,
						busy_obj:ATV
					};
					_post(send, function(res) {
						ATV.find('td').html(res.html);
					});
				case 2:
				case 3:
					FILE._vh(1);
					BUT.removeClass('_busy');
					clearInterval(atmr);
			}
		}
	},

	//[29] select: выбор единицы из другого списка (для связки)
	_EL29 = function(el) {
		var o = {
			width:_num(el.width),
			title0:el.txt_1,
			write:el.num_1 && el.num_3,
			msg_empty:'Не найдено',
			spisok:el.vvv,
			blocked:el.num_4,
			func:function(v) {
				_ELM_ACT(el, v);
			},
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
		//добавление записи через диалог
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
		$(ATTR_CMP(el.id))._select(o);
	},

	//[31] Выбор нескольких значений галочками
	_EL31 = function(el) {
		$(document).on('click', ATTR_EL(el.id) + ' ._check', function() {
			var cmpv = [];
			_forEq(_attr_el(el.id).find('._check.on'), function(sp) {
				cmpv.push(sp.prev().attr('id').split('_')[1]);
			});
			if(cmpv.length) {
				cmpv.unshift(0);
				cmpv.push(0);
			}
			$(ATTR_CMP(el.id)).val(cmpv.join())
		});
	},

	//[35] Количество
	_EL35 = function(el) {
		var obj = {
			width:_num(el.width),
			again:el.num_8
		};
		if(el.num_1 == 3681) {
			obj.min = el.num_2 ? _num(el.num_3) : false;
			obj.max = el.num_5 ? _num(el.num_6) : false;
			obj.step = _num(el.num_7);
			obj.minus = _num(el.num_4);
		}
		if(el.num_1 == 3682 && el.vvv) {
			obj.step = el.vvv.ids;
			obj.title = el.vvv.title;
		}
		$(ATTR_CMP(el.id))._count(obj);
	},
	PHP12_count_value = function(el, vvv, obj) {//настройка конктерных значений [35]
		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить значение</div>',
			ATTR_EL = _attr_el(el.id),
			DL = ATTR_EL.append(html).find('dl'),
			BUT_ADD = ATTR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		if(obj.unit.id)
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				id:NUM++,
				title:'',
				def:0
			}, v);

			DL.append(
				'<dd class="over1">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w35 center">' +
								'<input type="hidden" class="def" value="' + v.def + '" />' +
							'<td class="w50">' +
								'<input type="text" class="vid w50 r" value="' + v.id + '" />' +
							'<td><input type="text" class="title w100p" placeholder="имя значения не обязательно" value="' + v.title + '" />' +
							'<td class="w50 r top pt5">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить значение"></div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({handle:'.icon-move-y'});
			var DD = DL.find('dd:last');
			DD.find('.def')._check({
				tooltip:'Начальное значение',
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
			DD.find('.vid').select();
		}
	},
	PHP12_count_value_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				id:_num(sp.find('.vid').val(), 1),
				title:sp.find('.title').val(),
				def:_num(sp.find('.def').val())
			});
		});
		return send;
	},

	//[37] SA: Select - выбор имени колонки
	_EL37 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:'не выбрано',
			msg_empty:'колонок нет',
			spisok:el.vvv,
			funcAdd:function(t) {
				_dialogLoad({
					dialog_id:22,
					block_id:el.block_id,
					busy_obj:t.icon_add,
					busy_cls:'spin',
					func_save:function(res) {
						t.unitUnshift(res);
						t.value(res.id);
					}
				});
			}
		});
	},

	//[38] SA: Select - выбор диалогового окна
	_EL38 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			msg_empty:'диалоги ещё не были созданы',
			spisok:el.vvv
		});
	},

	//[39] Месяц и год
	_EL39 = function(el) {
		var ATR_MON = $(ATTR_CMP(el.id) + '_mon'),
			ATR_YEAR = $(ATTR_CMP(el.id) + '_year'),
			CMP_SET = function() {
				var mon = _num(ATR_MON.val());
				$(ATTR_CMP(el.id)).val(ATR_YEAR.val() + '-' + (mon > 9 ? '' : '0') + mon);
			};
		ATR_MON._count({
			width:100,
			step:[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
			title:['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'],
			again:1,
			func:CMP_SET
		});
		ATR_YEAR._count({
			width:70,
			min:1970,
			max:2100,
			func:CMP_SET
		});
	},

	//[40] Фильтрование списка
	_EL40 = function(el, OBJ) {
		var P = $(ATTR_CMP(el.id)).next(),
			INP = P.find('.inp'),
			DEL = P.find('.icon-del'),
			dssGet = function() {//получение id диалога из указанного элемента
				if(!el.num_1)
					return el.vvv;

				var send = _idsFirst(OBJ.dlg.D(ATTR_CMP(el.num_1)).val());
				if(send)
					return send;

				_attr_cmp(el.num_1, 1)
					._flash({color:'red'})
					._hint({
						msg:'Не выбрано значение',
						color:'clr5',
						pad:10,
						side:'left',
						show:1
					});

				return -1;
			};


		if(INP.attr('disabled'))
			return;

		P.click(function() {
			var dss = dssGet();

			if(dss == -1)
				return;
			if(!dss)
				return $(this)._hint({
					msg:'Не получен диалог<br>для настройки фильтра',
					color:'clr5',
					pad:10,
					show:1
				});

			_dialogLoad({
				dialog_id:41,
				block_id:OBJ.srce.block_id,
				element_id:el.num_1,//id элемента, к которому привязан фильтр (по нему будет определяться id диалога)
				dss:dss,
				dop:$(ATTR_CMP(el.id)).val(),
				busy_obj:INP,
				busy_cls:'hold',
				func_save:function(res) {
					$(ATTR_CMP(el.id)).val(res.v);
					INP.val(res.title);
					DEL._dn(1);
				}
			});
		});
		DEL.click(function(e) {
			e.stopPropagation();
			$(ATTR_CMP(el.id)).val('');
			INP.val('');
			DEL._dn();
		});
	},
	PHP12_spfl = function(el, vvv, obj) {//[41] настройка условий
		var DS = vvv.dss;
		if(!DS)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over5 curP">Добавить условие</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			DROP = vvv.drop,
			DS_4UNIT = 0,//id диалога, в котором размещён элемент, которому настраиваются условия (для значения unit_id=-4)
			BL = BLKK[obj.send.block_id];

		if(BL && BL.obj_name == 'dialog')
			DS_4UNIT = BL.obj_id;

		BUT_ADD.click(valueAdd);

		vvv = vvv.vvv;

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				elem_id:0,      //id выбранного элемента из диалога, по которому будет выполняться условие фильтра
				elem_title:'',  //имя выбранного элемента
				cond_id:0,      //id условия из выпадающего списка
				txt:'',         //текстовое значение, либо указанное значение из диалога (при unit_id=-4)
				elem_issp:0,    //можно выбирать значения из списка. Только при условиях: [3:равно], [4:не равно]
				spisok:[],      //содержание выпадающего списка
				unit_id:'',     //значение выпадающего списка
				unit4title:''   //имя выбранного значения (при unit_id=-4)
			}, v);

			var issp34 = v.elem_issp && (v.cond_id == 3 || v.cond_id == 4);

			DL.append(
				'<dd class="over5">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="topi w50 r clr13">Если:' +
							'<td class="top"><input type="text"' +
									  ' readonly' +
									  ' class="title w175 curP clr11"' +
									  ' placeholder="выберите значение..."' +
									  ' value="' + v.elem_title + '"' +
									  ' val="' + v.elem_id + '"' +
								' />' +
							'<td class="td-cond top' + _dn(v.elem_id) + '">' +
								'<input type="hidden" class="cond-id" value="' + v.cond_id + '" />' +
							'<td class="w100p">' +
								'<input type="text"' +
									  ' class="cond-val w100p' + _dn(!issp34 && v.cond_id > 2) + '"' +
					 (v.unit_id < 0 ? ' readonly' : '') +
									  ' value="' + v.txt + '"' +
								' />' +
								'<div class="div-cond-sel' + _dn(issp34) + '">' +
									'<input type="hidden"' +
										  ' class="cond-sel"' +
										  ' value="' + v.unit_id + '"' +
									' />' +

									'<div class="_selem dib prel bg-fff over1 mt3 w100p' + _dn(v.unit_id == -4) + '">' +
										'<div class="icon icon-star pabs"></div>' +
										'<div class="icon icon-del pl pabs' + _dn(v.txt) + '"></div>' +
										'<input type="text" readonly class="curP w100p clr11" placeholder="значение не указано" value="' + v.unit4title + '" />' +
									'</div>' +

								'</div>' +
							'<td class="td-drop' + _dn(!issp34 && v.cond_id > 2) + '">' +
								'<input type="hidden" class="cond-drop" />' +
							'<td class="top pl15">' +
								'<div class="mt5 icon icon-off pl tool" data-tool="Удалить условие"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				TITLE = DD.find('.title'),
				COND_ID = DD.find('.cond-id'),
				SELEM = DD.find('._selem');
			TITLE.click(function() {
				_dialogLoad({
					dialog_id:11,
					dss:DS,
					dop:{
						mysave:1,
						sel:v.elem_id,
						nest:0,
						sev:0
					},
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						DD.find('.td-cond')._dn(1);
						COND_ID._select(2);
						v.elem_id = res.v;
						TITLE.attr('val', v.elem_id);
						TITLE.val(res.title);
						DD.find('.cond-val')._dn().val('');

						//если выбран подключаемый список, то выбор значений этого списка
						v.elem_issp = res.issp;
						DD.find('.div-cond-sel')._dn();
						DD.find('.cond-sel')
							._select('spisok', res.spisok)
							._select(0);
					}
				});
			});
			COND_ID._select({//условие
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
					var issp = v.elem_issp && (vv == 3 || vv == 4);
					DD.find('.cond-val')
						._dn(!issp && vv > 2)
						.val('')
						.attr('readonly', false)
						.focus();
					DD.find('.cond-sel')._select(0);
					DD.find('.div-cond-sel')._dn(issp);
					DD.find('.td-drop')._dn(!issp && vv > 2);
					SELEM._dn().find('input').val('');
				}
			});
			DD.find('.cond-sel')._select({
				width:0,
				title0:'не выбрано',
				spisok:v.spisok,
				func:function(id) {
					SELEM._dn(id == -4);
				}
			});
			DD.find('.cond-drop')._dropdown({
				width:30,
				title0:'<div class="icon icon-set-dot"></div>',
				title0_hide:1,
				nosel:1,
				spisok:DROP,
				func:function(id, oo) {
					DD.find('.cond-sel').val(id);
					DD.find('.cond-val')
					  .val(oo.ass[id])
					  .attr('readonly', true);
				}
			});
			SELEM.click(function() {
				_dialogLoad({
					dialog_id:11,
					dss:DS_4UNIT,
					dop:{
						mysave:1,
						allow:'29',
						sel:v.txt,
						nest:0,
						sev:0
					},
					busy_obj:$(this).find('input'),
					busy_cls:'hold',
					func_save:function(res) {
						v.txt = res.v;
						DD.find('.cond-val').val(res.v);
						SELEM.find('input').val(res.title);
						SELEM.find('.icon-del')._dn(true);
					}
				});
			});
			SELEM.find('.icon-del').click(function(e) {
				e.stopPropagation();
				DD.find('.cond-val').val('');
				SELEM.find('input').val('');
				$(this)._dn();
			});
			DD.find('.icon-off').click(function() {
				$(this).closest('DD').remove();
			});
		}
	},
	PHP12_spfl_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push({
				elem_id:_ids(sp.find('.title').attr('val')),
				cond_id:_num(sp.find('.cond-id').val()),
				txt:sp.find('.cond-val').val(),
				unit_id:_num(sp.find('.cond-sel').val(), 1)
			});
		});
		return send;
	},

	/* [44] Настройка сборного текста */
	PHP12_elem44_setup = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<table class="w100p"><tr>' +
				        '<td><div class="fs15 clr9 pad10 center over1 curP add34-txt">Добавить текст</div>' +
				        '<td><div class="fs15 clr9 pad10 center over1 curP add34-el">Добавить элемент</div>' +
				   '</table>',
			DL = ATR_EL.append(html).find('dl'),
			NUM = 1;

		ATR_EL.find('.add34-txt').click(addTxt);
		ATR_EL.find('.add34-el').click(addEl);

		//вывод двух первых элементов, если начало настройки
		if(!vvv.length) {
			addTxt();
			addEl();
		} else
			_forN(vvv, function(v) {
				switch(v.type) {
					case 'txt': addTxt(v); break;
					case 'el': addEl(v); break;
				}
			});

		//добавление текстового поля
		function addTxt(v) {
			v = $.extend({
				txt:'',      //содержание текста
				font:'',
				color:''
			}, v || {});

			DL.append(
				'<dd class="over3" data-type="txt">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><textarea class="w100p h25 ' + v.font + ' ' + v.color + '"' +
										 ' style="background-color:#fff"' +
										 ' id="' + ATTR_EL('area' + NUM++, true) + '"' +
										 ' data-use="font color">' + v.txt +
								'</textarea>' +
							'<td class="w50 r">' +
								'<div class="icon icon-del-red pl tool" data-tool="Удалить"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				AREA = DD.find('textarea');

			//отображение выплывающего окна настройки стилей
			AREA.mouseenter(_tdCss);

			DL.sortable({handle:'.icon-move-y'});

			DD.find('.icon-del-red').click(function() {
				$(this).closest('DD').remove();
			});
			DD.find('textarea')._autosize().focus();
		}

		//добавление поля для выбора элемента
		function addEl(v) {
			v = $.extend({
				id:0,         //id элемента
				dialog_id:50, //id диалога, через который был вставлен этот элемент
				title:'',     //имя элемента
				font:'',
				color:''
			}, v || {});

			DL.append(
				'<dd class="over3" data-type="el" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="prel">' +
								'<input type="text"' +
									  ' id="' + ATTR_EL(v.id, true) + '"' +
									  ' class="inp w100p curP bg-gr2 ' + v.font + ' ' + v.color + '"' +
									  ' readonly' +
									  ' placeholder="элемент не выбран"' +
									  ' value="' + v.title + '"' +
									  ' data-use="font color link eye"' +
								' />' +
								'<div class="icon icon-star pabs top6 r5"></div>' +
							'<td class="w50 r">' +
								'<div class="icon icon-del-red pl tool" data-tool="Удалить"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');

			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:obj.srce.block_id,
					dss:obj.srce.dss,
					edit_id:v.id,           //id выбранного элемента (при редактировании)
					dop:{
						rule_id:4,
						mysave:1
					},
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

			//отображение выплывающего окна настройки стилей
			INP.mouseenter(_tdCss);

			DL.sortable({handle:'.icon-move-y'});

			DD.find('.icon-del-red').click(function() {
				$(this).closest('DD').remove();
				v.id = 0;
			});
		}
	},
	PHP12_elem44_setup_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			switch(sp.attr('data-type')) {
				case 'txt':
					var area = sp.find('textarea'),
						txt = area.val();
					if(!txt)
						return;
					send.push({
						type:'txt',
						txt:txt,
						font:_tdCssFontGet(area),
						color:_tdCssColorGet(area)
					});
					return;
				case 'el':
					var inp = sp.find('.inp'),
						id = _num(sp.attr('val'));
					if(!id)
						return;
					send.push({
						type:'el',
						id:id,
						font:_tdCssFontGet(inp),
						color:_tdCssColorGet(inp)
					});
					return;
			}
		});
		return send;
	},

	//[45] Выбор нескольких значений привязанного списка
	_EL45 = function(el) {
		var UNS = $(ATTR_CMP(el.id)).next(),//размещение выбранных значений
			CMP_UPD = function() {//сохранение значения
				var vv = [],
					pos = 0,//общее количество
					sum = 0,//общая сумма
					itog = '';

				_forEq(UNS.find('.uinp'), function(sp) {
					var id = _num(sp.attr('val'));
					if(!id)
						return;

					var count = _cena(sp.val()),
						cena = 0;//стоимость

					pos += count;

					if(el.num_4) {
						cena = _cena(sp.closest('tr').find('.ucena').html());
						sum += cena * count;
					}

					vv.push(id + ':' + count + ':' + cena);
				});

				$(ATTR_CMP(el.id)).val(vv.join());

				//обновление итога
				if(vv.length) {
					var summ = _cena(sum);
					itog = 'Всего <b class="fsin">' + _cena(pos) + '</b> позици' + _end(pos, ['я', 'и', 'й']) +
						(el.num_4 ? ' на сумму <b class="fsin">' + summ + '</b> руб.' : '');
					//вставка итоговой суммы у указанное поле
					if(el.num_5)
						_attr_cmp(el.num_5).val(summ);
				}
				_attr_el(el.id).find('.uns-itog').html(itog);
			},
			UNS_DEL = function() {//удаление значения
				UNS.find('.icon').click(function() {
					$(this).closest('tr').remove();
					if(!UNS.find('tr').length)
						UNS.html('');
					CMP_UPD();
				});
				UNS.find('.uinp').keyup(CMP_UPD);
			};
		UNS_DEL();
		CMP_UPD();
		//нажатие на кнопку для открытыя диалога
		_attr_cmp(el.id, 1).click(function() {
			if(!el.num_2)
				return;
			_dialogLoad({
				block_id:el.block_id,
				dialog_id:el.num_2,
				busy_obj:_attr_cmp(el.id, 1),
				func_open:function(res, dlg) {
					//выбор значения списка
					dlg.content.click(function(e) {
						var un = $(e.target).parents('.sp-unit');
						if(!un.length) {
							un = $(e.target).parents('.tr-unit');
							if(!un.length)
								return;
						}

						var id_new = _num(un.attr('val'));
						if(!id_new)
							return;

						dlg.close();

						//действие после выбора значения
						var send = {
							op:'spisok_45_uns',
							elem_id:el.id,
							id_new:id_new,
							v:$(ATTR_CMP(el.id)).val(),
							busy_obj:_attr_cmp(el.id, 1)
						};
						_post(send, function(res) {
							UNS.html(res.html);
							UNS_DEL();
							CMP_UPD();
							UNS.find('.uinp:last').select();
						});
					});
				}
			});
		});
	},

	//[49] Выбор блоков из диалога или страницы
	_EL49 = function(el, OBJ) {
		var P = $(ATTR_CMP(el.id)).next(),
			INP = P.find('.inp'),
			DEL = P.find('.icon-del');

		P.click(function() {
			_dialogLoad({
				dialog_id:19,
				block_id:OBJ.srce.block_id,
				element_id:OBJ.srce.element_id,
				dop:{
					sel:$(ATTR_CMP(el.id)).val()
				},
				busy_obj:INP,
				busy_cls:'hold',
				func_save:function(res) {
					$(ATTR_CMP(el.id)).val(res.ids);
					INP.val(res.title);
					DEL._dn(res.ids);
				}
			});
		});
		DEL.click(function(e) {
			e.stopPropagation();
			$(ATTR_CMP(el.id)).val(0);
			INP.val('');
			DEL._dn();
		});
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
					element_id:obj.srce.element_id,
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

	//[51] Календарь
	_EL51 = function(el) {
		$(ATTR_CMP(el.id))._calendar({
			lost:el.num_1,
			time:el.num_2,
			func:function() {
				_ELM_ACT(el, 1);
			}
		});
	},

	//[52] Заметки
	_EL52 = function(el) {
		if(!_attr_el(el.id).length)
			return;
		var timer = 0,
			NOTE = _attr_el(el.id).find('._note'),
			ex = NOTE.attr('val').split(':'),
			page_id = _num(ex[0]),
			obj_id = _num(ex[1]),
			NOTE_TXT = NOTE.find('._note-txt'),
			NOTE_AREA = NOTE_TXT.find('textarea'),
			NOTE_TXT_W = NOTE_TXT.width(),
			noteAfterPrint = function() {
				NOTE.find('._note-list textarea').keyup(function(e) {
					if(e.keyCode != 13)
						return;

					switch(el.num_2) {
						case 9644://отправка сообщения по Enter
							if(event.ctrlKey) {
								this.value += "\n";
								$(this)._autosize('update');
								return;
							}
							break;
						case 9645://отправка сообщения по CTRL+Enter
							if(!event.ctrlKey)
								return;
							break;
						default:
							return;
					}
					$(this).parents('._note-comment').find('.comment-ok').trigger('click');
				});
				NOTE.find('.comment-ok').click(function() {//внесение комментария
					var t = $(this),
						comm = t.parents('._note-comment'),
						note = t.parents('._note-u'),
						area = comm.find('textarea'),
						txt = $.trim(area.val());
					if(!txt)
						return area.focus();
					var send = {
						op:'note_comment_add',
						elem_id:el.id,
						note_id:note.attr('val'),
						txt:txt,
						busy_cls:'busy',
						busy_obj:comm
					};
					_post(send, function(res) {
						t.closest('TABLE').before(res.html);
						area.val('');
						area._autosize('update');
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
		NOTE.find('textarea')._autosize();
		NOTE_AREA.keyup(function(e) {
			if(e.keyCode == 13)
				switch(el.num_2) {
					case 9644://отправка сообщения по Enter
						if(event.ctrlKey) {
							this.value += "\n";
							$(this)._autosize('update');
							return;
						}
						return NOTE.find('.note-ok').trigger('click');
					case 9645://отправка сообщения по CTRL+Enter
						if(!event.ctrlKey)
							break;
						return NOTE.find('.note-ok').trigger('click');
				}

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
				elem_id:el.id,
				page_id:page_id,
				obj_id:obj_id,
				txt:txt,
				busy_cls:'busy',
				busy_obj:NOTE
			};
			_post(send, function(res) {
				NOTE_AREA.val('')._autosize('update');
				NOTE_TXT.width(NOTE_TXT_W);
				NOTE.find('._note-list').html(res.html)
					.find('textarea')._autosize();
				noteAfterPrint();
			});
		});
		noteAfterPrint();
	},

	//[57] Меню переключения блоков
	_EL57 = function(el) {
		var type = {
				1158:2,
				1159:3,
				13534:4
			},
			EL_COO = '57_' + el.id;//сохранение последней позиции
		$(ATTR_CMP(el.id))._menu({
			type:type[el.num_1],
			spisok:el.vvv,
			func:function(id) {
				_forN(el.vvv, function(sp) {
					_forN(_blockObj(_idsAss(sp.blk)), function(oo) {
						if(!oo.obj.length)
							return;
						oo.obj._dn(sp.id == id);
					});
				});
				if(el.num_2)
					_cookie(EL_COO, id);
			}
		});
	},
	PHP12_menu_block_setup = function(el, vvv, obj) {//[57] настройка пунктов меню
		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over5 curP">Новый пункт меню</div>',
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			ID_NEXT = 1,//следующий идентификатор с учётом существующих
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
		else {
			_forIn(vvv, function(v) {
				if(ID_NEXT < v.id)
					ID_NEXT = v.id + 1;
			});
			_forIn(vvv, valueAdd);
		}

		function valueAdd(v) {
			v = $.extend({
				id:0,                  //id элемента
				num:NUM,
				title:'Имя пункта ' + NUM++, //имя пункта меню
				blk:'',
				def:0
			}, v);

			if(!v.id)
				v.id = ID_NEXT++;

			DL.append(
				'<dd class="over5" val="' + v.id + '" data-num="' + v.num + '">' +
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
									  ' class="pk-block w100p curP clr8 over1"' +
									  ' readonly' +
									  ' placeholder="выбрать блоки"' +
									  ' value="' + blkTitle(v.blk) + '"' +
									  ' val="' + v.blk + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить пункт"></div>' +
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
						level_deny:0,
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

	//[59] Связка списка при помощи кнопки
	_EL59 = function(el) {
		var div = _attr_cmp(el.id, 1).next(),
			unitSel = function(id) {//действие после выбора значения
				var send = {
					op:'spisok_59_unit',
					cmp_id:el.id,
					unit_id:id,
					busy_obj:_attr_cmp(el.id, 1)
				};
				_post(send, function(res) {
					AG.unit = res.unit;
					div.find('.un-html').html(res.html);
					div._dn(1);
					_attr_cmp(el.id, 1)._dn();
					_ELM_ACT(el, id);
				});
			};

		//запрет перевыбора значения
		if(el.num_3)
			return;

		//нажатие на кнопку для открытия диалога
		_attr_cmp(el.id, 1).click(function() {
			_dialogLoad({
				block_id:el.block_id,
				dialog_id:el.num_4,
				busy_obj:_attr_cmp(el.id, 1),
				func_open:function(res, dlg) {
					//выбор значения списка
					dlg.content.click(function(e) {
						var un = $(e.target).parents('.sp-unit');
						if(!un.length) {
							un = $(e.target).parents('.tr-unit');
							if(!un.length)
								return;
						}

						var id = _num(un.attr('val'));
						if(!id)
							return;

						$(ATTR_CMP(el.id)).val(id);
						dlg.close();
						unitSel(id);
					});
				}
			});
		});
		//отмена выбора
		div.find('.icon').click(function() {
			_attr_cmp(el.id, 1)._dn(1);
			div._dn();
			$(ATTR_CMP(el.id)).val(0);
			_ELM_ACT(el, 0);
		});
	},

	//[60] Загрузка изображений
	_EL60 = function(el) {
		var load = _attr_el(el.id).find('._image-load'),
			prc = _attr_el(el.id).find('._image-prc'), //div для отображения процентов
			II4 = _attr_el(el.id).find('.ii4'),//корзина
			ids_upd = function() {//обновление id загруженных изображений
				var idsSave = $(ATTR_CMP(el.id)).val().split(','),//предварительное сохранение всех идентификаторов
					ids = [],
					ii4Empty = true;//статус для корзины

				//приведение всех id в статус: удалён
				_forN(idsSave, function(id, n) {
					if(id > 0)
						idsSave[n] = id * -1;
				});

				_forEq(_attr_el(el.id).find('dd.curM'), function(sp) {
					var idd = _num(sp.attr('val'));
					ids.push(idd);
					//если картинка существует, то убираем из удалённых
					_forN(idsSave, function(id, n) {
						if(idd == Math.abs(id)) {
							delete idsSave[n];
							return false;
						}
					});
				});

				//дополнение идентификаторов удалёнными
				_forN(idsSave, function(id) {
					if(id) {
						ids.push(id);
						ii4Empty = false;
					}
				});
				//изменение статуса корзины, если есть удалённые изображения
				II4._dn(!ii4Empty, 'empty');

				$(ATTR_CMP(el.id)).val(ids.join(','));

				//установка действия для удаления изображения
				_attr_el(el.id).find('.icon-off').off('click');
				_attr_el(el.id).find('.icon-off').on('click', function(e) {
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
						itog = Math.round(done / total * 100);

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
							color:'clr5',
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
				xhr.send(data);
			};

		ids_upd();
		_attr_el(el.id).find('dl').sortable({
			items:'.curM',
			placeholder:'ui-hold',
			update:ids_upd
		});

		//Выбор способа загрузки
		_attr_el(el.id).find('.tab-load td').mouseenter(function() {
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

		//Загрузка изображения из файла
		_attr_el(el.id).find('form input').change(function() {
			load.addClass('busy');
			xhr_upload(this.files[0]);
		});

		//Загрузка изображения по ссылке
		var linkDiv = _attr_el(el.id).find('._image-link'), //поле с ссылкой на изображение
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
		_attr_el(el.id).find('.ii2').click(function() {
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
		_attr_el(el.id).find('.ii3').click(function() {
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
		II4.click(function() {
			if($(this).hasClass('empty'))
				return;

			_dialogLoad({
				dialog_id:63,
				dop:$(ATTR_CMP(el.id)).val(),
				busy_obj:load,
				busy_cls:'busy',
				func_open:function(res, dlg) {
					dlg.content.find('.icon-recover').click(function() {
						var tt = $(this),
							send = {
								op:'image_recover',
								id:tt.attr('val'),
								busy_obj:tt,
								busy_cls:'spin'
							};
						_post(send, function(res) {
							tt.parent().remove();
							load.parent().before(res.html);
							ids_upd();
						});
					});
				}
			});
		});
	},

	//[62] Фильтр-галочка
	_EL62 = function(el) {
		$(ATTR_CMP(el.id))._check({
			func:function(v) {
				_ELM_ACT(el, v, 'before');
				FILTER[el.num_1][el.id] = v;
				_spisokUpdate(el.num_1, function() {
					_ELM_ACT(el, v, 'after');
				});
			}
		});
	},

	//[66] Выбор цвета текста
	_EL66 = function(el) {
		var func = function(v) {
				$(ATTR_CMP(el.id)).val(v);
			},
			html = _color($(ATTR_CMP(el.id)).val(), func);
		$(ATTR_CMP(el.id)).next().remove('._color');
		$(ATTR_CMP(el.id)).after(html);
	},

	//[70] Выбор цвета фона
	_EL70 = function(el) {
		$(ATTR_CMP(el.id)).next()._hint({
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
					$(ATTR_CMP(el.id))
						.val(c)
						.next().css('background-color', c);
				});
			}
		});
	},

	//[72] Фильтр: год и месяц
	_EL72 = function(el) {
		var YL = $(ATTR_CMP(el.id) + 'yl'),
			RD = $(ATTR_CMP(el.id) + 'rd'),
			YEAR_CUR = YL.val(),
			CMP_SET = function() {
				var mon = _num(RD.val()),
					data = YL.val() + '-' + (mon > 9 ? '' : '0') + mon;
				$(ATTR_CMP(el.id)).val(data);
				FILTER[el.num_1][el.id] = data;
				_spisokUpdate(el.num_1);
			};
		YL._yearleaf({
			func:function(v) {
				RD._radio(YEAR_CUR < v ? 1 : 12);
				CMP_SET();
				YEAR_CUR = v;
				var send = {
					op:'spisok_72_sum',
					elem_id:el.id,
					year:v
				};
				_post(send, function(res) {
					RD._radio('spisok', _toSpisok(res.spisok));
				});
			}
		});
		RD._radio(CMP_SET);
	},

	//[74] Фильтр-радио
	_EL74 = function(el) {
		$(ATTR_CMP(el.id))._radio({
			func:function(v) {
				FILTER[el.num_1][el.id] = v;
				_spisokUpdate(el.num_1);
			}
		});
	},
	PHP12_filter_radio_setup = function(el, vvv, obj) {//[74] настройка значений
		if(!obj.unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить значение</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			ATR_SP = _attr_cmp(2585),
			ID_NEXT = 1,//следующий идентификатор с учётом существующих
			NUM = 1,
			_CS = function(count) {//отобажение иконки настройки условий
				if(count)
					return '<span class="cond-setup ml20 curP tool" data-tool="Настроить">' + count  + ' услови' + _end(count, ['е', 'я', 'й']) + '</span>';

				return '<div class="icon icon-add cond-setup pl ml15 tool" data-tool="Добавить условия"></div>';
			};

		BUT_ADD.click(valueAdd);

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, function(v) {
				if(ID_NEXT < v.id)
					ID_NEXT = v.id + 1;
				valueAdd(v);
			});

		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента из диалога, по которому будет выполняться условие фильтра
				title:'Значение ' + NUM++,
				def:0,
				c:0,       //количество условий в пункте
				cond:'',   //сами условия
				eye:1      //отображть количество для пункта фильтра
			}, v);

			if(!v.id)
				v.id = ID_NEXT++;

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
								'<span class="span-cs clr1">' + _CS(v.c) + '</span>' +
								'<input type="hidden" class="cond" />' +
							'<td class="w100">' +
								'<div class="icon icon-eye tool' + _dn(!v.eye, 'over3-show pl') + '" data-tool="Отображать<br>количество"></div>' +
							'<td class="w35 r">' +
								'<div class="icon icon-del-red pl tool" data-tool="Удалить значение"></div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({handle:'.icon-move-y'});

			var DD = DL.find('dd:last');

			DD.find('.cond').val(v.cond);

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

			//добавление условия к значению
			DD.find('.span-cs').click(function() {
				_dialogLoad({
					dialog_id:41,
					dss:ATR_SP.val(),
					element_id:2585,
					dop:v.cond,
					busy_obj:$(this).find('.cond-setup'),
					busy_cls:v.c ? '_busy' : 'spin',
					func_save:function(res) {
						v.cond = res.v;
						DD.find('.cond').val(res.v);
						v.c = res.c;
						DD.find('.span-cs').html(_CS(res.c));
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
				cond:sp.find('.cond').val(),
				def:sp.find('.def').val(),
				eye:sp.find('.icon-eye').hasClass('pl') ? 0 : 1
			});
		});
		return send;
	},

	//[75] Фильтр: фронтальное меню
	_EL75 = function(el) {
		var UN = _attr_el(el.id).find('.u75');
		UN.click(function() {
			var t = $(this),
				pname = t.closest('td').find('a').html(),
				v = t.attr('val');

			if(!t.hasClass('fs16'))
				pname += ' » ' + t.html();

			_attr_el(el.id).find('.pname75').html(pname);
			_attr_el(el.id).find('.mp75')._dn(true);
			_attr_el(el.id).find('.tab75')._dn();

			_ELM_ACT(el, v);
			FILTER[el.num_1][el.id] = v;
			_spisokUpdate(el.num_1);
		});
		//отмена выбора
		_attr_el(el.id).find('.icon-del').click(function() {
			_attr_el(el.id).find('.mp75')._dn();
			_attr_el(el.id).find('.tab75')._dn(true);
			_ELM_ACT(el, 0);
			FILTER[el.num_1][el.id] = 0;
			_spisokUpdate(el.num_1);
		});
	},

	//[76] Видеоролик
	_EL76 = function(el) {
		var CNT = _attr_el(el.id).find('._video-cont'),
			DEL = _attr_el(el.id).find('.icon-del');
		$(ATTR_CMP(el.id)).keyup(function() {
			var url = $(this).val();
			if(!url) {
				DEL._dn();
				return;
			}

			DEL._dn(true).addClass('spin');

			var send = {
				op:'el76_video',
				elem_id:el.id,
				url:url,
				func_err:function(res) {
					var msg = '<div class="center clr5">' + res.text + '</div>';
					CNT.html(msg)._dn(true);
					DEL.removeClass('spin');
				}
			};
			_post(send, function(res) {
				CNT.html(res.iframe)._dn(true);
				DEL.removeClass('spin');
			});
		});
		DEL.click(function() {
			CNT.slideUp(200, function() {
				CNT.html('').show()._dn();
			});
			DEL._dn();
			$(ATTR_CMP(el.id)).val('').focus();
		});
	},

	//[77] Фильтр-календарь
	_EL77 = function(el) {
		var CAL = _attr_el(el.id).find('._filter-calendar'),
			CNT = CAL.find('.fc-cnt');

		//перемотка месяцев
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

		//нажатие на месяц
		CAL.click(function(e) {
			var t = $(e.target);
			if(!t.hasClass('monn'))
				return;
			CNT.find('.sel').removeClass('sel');
			t.addClass('sel');
			FILTER[el.num_1][el.id] = t.attr('val');
			_spisokUpdate(el.num_1);
		});

		//нажатие на неделю или на день
		CNT.click(function(e) {
			var t = $(e.target),
				on = t.hasClass('on'),
				week = t.hasClass('week-num'),
				td = on ? t : t.parent();
			if(on || week) {
				CAL.find('.monn.sel').removeClass('sel');
				CNT.find('.sel').removeClass('sel');
				td.addClass('sel');
				FILTER[el.num_1][el.id] = t.attr('val');
				_spisokUpdate(el.num_1);
			}
		});
	},

	//[78] Фильтр-меню
	_EL78 = function(el) {
		var FM = _attr_el(el.id).find('.fm-unit');
		_attr_el(el.id).find('.fm-plus').click(function() {
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
			FILTER[el.num_1][el.id] = sel ? 0 : t.attr('val');
			_spisokUpdate(el.num_1);
		});
	},

	//[80] Очистка фильтров
	_EL80 = function(el) {
		$(ATTR_CMP(el.id)).click(function() {
			var t = $(this),
				send = {
					op:'spisok_filter_clear',
					spisok_id:el.num_1,
					busy_obj:t
				};
			_post(send, function(res) {
				//скрытие кнопки
				t._dn();

				_forIn(res.def, function(sp) {
					switch(sp.dialog_id) {
						//быстрый поиск
						case 7:
							_attr_cmp(sp.elem_id)._search('clear');
							return;
						//фильтр-галочка
						case 62:
							_attr_cmp(sp.elem_id)._check(sp.v);
							return;
						//фильтр-радио
						case 74:
							_attr_cmp(sp.elem_id)._radio(sp.v);
							return;
						case 75:
							_attr_el(sp.elem_id).find('.mp75')._dn();
							_attr_el(sp.elem_id).find('.tab75')._dn(true);
							return;
						//фильтр-календарь
						case 77:
							var CAL = _attr_el(sp.elem_id).find('._filter-calendar');
							CAL.find('.mon-cur').val(sp.dop.mon);
							CAL.find('.td-mon').html(sp.dop.td_mon);
							CAL.find('.fc-cnt').html(sp.dop.cnt);
							return;
						//фильтр-меню
						case 78:
							_attr_el(sp.elem_id).find('.sel').removeClass('sel');
							return;
						//фильтр-select
						case 83:
							_attr_cmp(sp.elem_id)._select(0);
							return;
						//Фильтр - Выбор нескольких групп значений
						case 102:
							//_attr_el(sp.elem_id)._filter102();
							_attr_el(sp.elem_id).find('.holder')._dn(1);
							_attr_el(sp.elem_id).find('.td-un').html('<div class="icon icon-empty"></div>');
							_attr_el(sp.elem_id).find('.icon-del')._vh();
							return;
					}
				});
				FILTER = res.filter;
				_filterAfter(res);
			});
		});
	},

	//[83] Select - фильтр
	_EL83 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			spisok:el.vvv,
			func:function(v) {
				FILTER[el.num_1][el.id] = v;
				_spisokUpdate(el.num_1);
			}
		});
	},

	//[85] Select - выбор значения списка
	_EL85 = function(el) {
		$(ATTR_CMP(el.id))._select({
			width:_num(el.width),
			title0:el.txt_1,
			spisok:el.vvv,
			msg_empty:el.num_1 ? 'Список пуст' : 'Не указан список',
			func:function(v) {
				_ELM_ACT(el, v);
			}
		});
		ELM_RELOAD[el.num_1] = el.id;
	},

	//[88] Таблица из нескольких списков
	_EL88 = function(el) {
		//выбор значений галочками
		_forIn(el.vvv, function(sp) {
			if(sp.dialog_id != 91)
				return;

			_forEq($(ATTR_EL(el.id)).find('._check'), function(eq) {
				//получение id записи
				var tdid = eq.attr('id').split('_')[1];

				//выбор/снятие всех галочек
				if(tdid == 'all') {
					$('#sch' + sp.id + '_all')._check({
						func:function(v) {
							_forEq($(ATTR_EL(el.id)).find('._check'), function(eqAll) {
								var ch = eqAll.prev();
								if(ch.attr('id').split('_')[1] == 'all')
									return;
								ch._check(v);
							});
						}
					});
					return;
				}

				eq.prev()._check();
			});
			return false;
		});
	},
	PHP12_elem88 = function(el, vvv, obj) {//[88] настройка таблицы
		if(!obj.unit.id)
			return;

		window.EL88 = _attr_el(el.id);
		window.COL88 = vvv.col;

		PHP12_elem88_sp(el, vvv);
		PHP12_elem88_td(el, vvv);
	},
	PHP12_elem88_sp = function(el, vvv) {//списки
		var DL = _attr_el(el.id).find('#sp88'),
			NUM = 0;

		//кнопка добавления нового списка
		DL.next().click(function() {
			spAdd();
			PHP12_elem88_upd('spadd');
		});

		if(vvv.spv.length)
			_forN(vvv.spv, spAdd);
		else {
			spAdd();
			PHP12_elem88_upd('spadd');
		}

		//добавление нового списка
		function spAdd(v) {
			v = $.extend({
				dialog_id:0,
				cond:'',
				c:''
			}, v);

			DL.append(
				'<dd>' +
					'<table class="bs5 spv-tab">' +
						'<tr><td><input type="hidden" class="cond" />' +
								'<div class="_spfl dib w125 prel">' +
									'<div class="icon icon-filter pabs"></div>' +
									'<div class="icon icon-del pl pabs' + _dn(v.cond) + '"></div>' +
									'<input type="text" readonly class="clr7 b pl25 curP w100p over3" placeholder="условий нет" value="' + v.c + '" />' +
								'</div>' +
							'<td><input type="hidden" class="spv" value="' + v.dialog_id + '" />' +
							'<td class="w25">' +
								'<div class="icon icon-set-b pl tool" data-tool="Настроить колонки"></div>' +
							'<td class="w25">' +
								'<div val="' + (NUM++) + '" class="icon icon-del-red pl tool" data-tool="Удалить список"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last');

			//вставка данных фильтра (прямая вставка в valut - не корректна)
			DD.find('.cond').val(v.cond);

			//настройка условий
			DD.find('._spfl').click(function() {
				var t = $(this),
					inp = t.find('input');
				_dialogLoad({
					dialog_id:41,
					dss:DD.find('.spv').val(),
					dop:t.prev().val(),
					busy_obj:inp,
					busy_cls:'hold',
					func_save:function(res) {
						t.prev().val(res.v);
						inp.val(res.title);
						t.find('.icon-del')._dn(1);
					}
				});
			});

			//вывод списков
			DD.find('.spv')._select({
				width:390,
				title0:'список не выбран',
				spisok:vvv.sp
			});

			//настройка колонок
			DL.find('.icon-set-b:last').click(function() {
				var t = $(this),
					dlg_id = _num(DD.find('.spv').val()),
					i = spn(DD.find('.icon-del-red').attr('val'));
				if(!dlg_id)
					return;
				if(i === undefined)
					return;

				_dialogLoad({
					dialog_id:89,
					dss:dlg_id,
					block_id:vvv.block_id,//передача блока для выбора элемента
					edit_id:vvv.element_id,//элемент-родитель для сохранения данных дочерних элементов
					dop:i,
					busy_obj:t,
					busy_cls:'spin'
				});
			});

			//удаление списка
			DL.find('.icon-del-red:last').click(function() {
				var t = $(this),
					vd = spn(t.attr('val'));

				if(vd === undefined)
					return;

				t.closest('DD').remove();
				PHP12_elem88_upd('spdel', vd);
			});

			function spn(val) {//получение индекса списка по порядковому номеру
				var vd;
				_forEq(DL.find('.icon-del-red'), function(sp, n) {
					if(val == sp.attr('val'))
						vd = n;
				});
				return vd;
			}
		}
	},
	PHP12_elem88_td = function(el, vvv) {//ячейки
		var DL = _attr_el(el.id).find('#col88'),
			CALC_DIV = _attr_el(el.id).find('.calc-div'),//div, в котором располагается визуальный подсчёт ячеек
			CALC_W = _num(CALC_DIV.html()),//изначальная ширина блока, в котором размещена таблица
			NUM = 1;

		//кнопка добавления новой ячейки
		DL.next().click(function() {
			tdAdd();
			PHP12_elem88_upd('tdadd');
		});

		if(vvv.col.length)
			_forIn(vvv.col, tdAdd);
		else {
			tdAdd();
			PHP12_elem88_upd('tdadd');
		}

		//добавление новой колонки в таблицу
		function tdAdd(v) {
			v = $.extend({
				width:150,   //ширина колонки
				title:''     //имя значения
			}, v);

			DL.append(
				'<dd class="over3">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w25 r topi">' +
								'<b class="bnum fs15 clr9">' + NUM + '</b>:' +
							'<td><div style="width:' + v.width + 'px">' +
									'<input type="text"' +
										  ' class="th-name w100p bg-gr2 fs14 clr15"' +
										  ' placeholder="имя колонки"' +
										  ' value="' + v.title + '"' +
									' />' +
								'</div>' +
							'<td class="w25 r top pt5">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить колонку"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last');
			tdResize(DD);

			//сортировка колонок
			DL.sortable({
				handle:'.icon-move-y',
				update:function() {
					PHP12_elem88_upd('tdsort');
					tdCalc();
				}
			});

			//удаление элемента
			DL.find('.icon-del:last').click(function() {
				var dd = $(this).closest('DD'),
					v = _num(dd.find('.bnum').html()) - 1;
				dd.remove();
				tdCalc();
				PHP12_elem88_upd('tddel', v);
			});

			DD.find('.th-name').focus();

			NUM++;
			tdCalc();
		}

		//включение изменения ширины, если присутствует значение
		function tdResize(dd) {
			var res = dd.find('.th-name').parent();
			if(res.hasClass('ui-resizable'))
				return;
			res.resizable({
				minWidth:30,
				maxWidth:500,
				grid:10,
				handles:'e',
				stop:tdCalc
			});
		}

		//пересчёт визуального отображения ячеек по диагонали
		function tdCalc() {
			var html = '',
				DIV_W = 600,
				i = DIV_W / CALC_W,
				FULL_W = 0,
				TDS = [],//массив ширин по каждой ячейке
				ALL_W = 0;//сумма ширины всех ячеек, кроме последней

			_forEq(DL.find('DD'), function(sp) {
				var n = _num(sp.find('.bnum').html()),
					w = sp.find('.th-name').parent().width();
				TDS.push({
					n:n,
					w:Math.round(w*i)-1
				});
				FULL_W += w;
			});

			_forN(TDS, function(o, n) {
				var bg = 'ffc',
					line = ' line-r';
				if(FULL_W > CALC_W) {
					bg = 'fcc';
					i = CALC_W / FULL_W;
					o.w = Math.round(o.w*i);
				}
				if(FULL_W >= CALC_W) {
					ALL_W += (o.w+1);
					if(n == (TDS.length-1)) {
						line = '';
						o.w = DIV_W - ALL_W + o.w + 1;
					}
				}

				html += '<div class="h25 dib center bg-' + bg + line + '" style="width:' + o.w + 'px">' +
							'<div class="fs15 b clr9 pt5">' + o.n + '</div>' +
						'</div>';
			});
			CALC_DIV.html(html);
		}
	},
	PHP12_elem88_upd = function(cmd, i) {//обновление итоговых значений
		/*
			COL88: массив данных
			cmd:   команда, на основании которой изменяется массив
			i:     указатель изменения/удаления
		*/

		if(!window.EL88)
			return;

		switch(cmd) {
			//список добавлен: добавляется нулевой элемент к каждому списку
			case 'spadd':
				_forN(COL88, function(sp, n) {
					COL88[n].elm.push(0);
				});
				break;
			//список удалён: удаляется элемент у всех списков
			case 'spdel':
				_forN(COL88, function(sp, n) {
					COL88[n].elm.splice(i, 1);
				});
				break;
			//колонка добавлена
			case 'tdadd':
				var o = {
					width:150,
					title:'',
					elm:[]
				};
				_forN(EL88.find('.spv'), function() {
					o.elm.push(0);
				});
				COL88.push(o);
				break;
			//колонка удалена
			case 'tddel':
				COL88.splice(i, 1);
				break;
			//колонки отсортированы
			case 'tdsort':
				var col88save = [];
				_forEq(EL88.find('.bnum'), function(sp, n) {
					var num = _num(sp.html()) - 1;
					col88save.push(COL88[num]);
					sp.html(n+1);
				});
				COL88 = col88save;
				break;
		}

		_forEq(EL88.find('.th-name'), function(sp) {
			var n = _num(sp.closest('DD').find('.bnum').html()) - 1;
			COL88[n].width = sp.parent().width();
			COL88[n].title = sp.val();
		});
	},
	PHP12_elem88_get = function(el) {
		var spv = [];//списки

		PHP12_elem88_upd();

		_forEq(_attr_el(el.id).find('.spv-tab'), function(sp) {
			var id = _num(sp.find('.spv').val());
			spv.push({
				dialog_id:id,
				cond:sp.find('.cond').val()
			});
		});

		return {
			spv:spv,
			col:window.COL88 ? COL88 : {}
		};
	},
	PHP12_elem89 = function(el, vvv) {//настройка ячеек конкретной таблицы
		var html = '';
		_forIn(COL88, function(sp, n) {
			var elm_id = _num(sp.elm[vvv.i]),
				elm = $.extend({
					id:0,
					dialog_id:50,
					title:'',
					font:''
				}, vvv.elm[elm_id]);
			html +=
			'<div class="fs14 clr1">' + sp.title + '</div>' +
			'<div class="prel" style="width:' + sp.width + 'px">' +
				'<div class="icon icon-del-red pl pabs r3 top5' + _dn(elm.id) + '"></div>' +
				'<input type="text"' +
					  ' id="' + ATTR_EL(elm_id, 1) + '"' +
					  ' class="w100p curP over1 mb10 pr20 ' + elm.font + ' ' + elm.color + ' ' + elm.txt_8 + '"' +
					  ' placeholder="элемент не указан"' +
					  ' readonly' +
					  ' data-n="' + n + '"' +
					  ' data-did="' + elm.dialog_id + '"' +
					  ' val="' + elm.id + '"' +
					  ' value="' + elm.title + '"' +
				'>' +
			'</div>';
		});
		$('#col89')
			.html(html)
			.find('input').click(function() {
				var t = $(this);
				_dialogLoad({
					dialog_id:_num(t.attr('data-did')),
					dss:vvv.dss,
					block_id:vvv.block_id,
					edit_id:_num(t.attr('val')),
					dop:{
						rule_id:5,
						mysave:1
					},
					busy_obj:t,
					busy_cls:'hold',
					func_save:function(ia) {
						console.log(ia);
						t.val(ia.unit.title);
						t.attr('data-did', ia.unit.dialog_id);
						t.attr('val', ia.unit.id);
						t.attr('id', ATTR_EL(ia.unit.id, 1));
						t.prev()._dn(1);
						COL88[_num(t.attr('data-n'))].elm[vvv.i] = ia.unit.id;
						vvv.elm[ia.unit.id] = ia.unit;
					}
				});
			})
			//отображение выплывающего окна настройки стилей
			.mouseenter(_tdCss)
			.end()
			.find('.icon').click(function() {
				var inp = $(this)._dn().next();
				inp.val('')
				   .attr('data-did', 50)
				   .attr('val', 0);
				COL88[_num(inp.attr('data-n'))].elm[vvv.i] = 0;
			});
	},
	PHP12_elem89_get = function(el) {
		var send = [];//списки

		_forEq(_attr_el(el.id).find('input'), function(sp) {
			var v = {};

			v.id = _num(sp.attr('val'));

			if(!v.id)
				return;

			v.font = _tdCssFontGet(sp);

			//позиция
			arr = ['center', 'r'];
			v.txt_8 = '';
			for(k in arr)
				if(sp.hasClass(arr[k]))
					v.txt_8 = arr[k];

			//цвет текста
			v.color = '';
			for(k in ELEM_COLOR)
				if(sp.hasClass(k))
					v.color = k;

			send.push(v);
		});

		return send;
	},
	_elem88next = function(t, elem_id, next) {//догрузка списка
		var send = {
			op:'elem88next',
			elem_id:elem_id,
			next:next,
			busy_obj:t
		};
		_post(send, function(res) {
			t.closest('._stab').append(res.spisok);
			t.remove();
		});
	},

	//[92] Выбранные значения галочками
	_EL92 = function(el, OBJ) {
		if(OBJ.unit.id)
			return;

		var ids = [],
			elmIds = {};//id элементов-списков, в которых выбираются значения
		_forIn(el.vvv, function(sp) {
			if(elmIds[sp.elm_id])
				return;

			elmIds[sp.elm_id] = 1;

			_forEq(_attr_el(sp.elm_id).find('._check'), function(eqAll) {
				var ch = eqAll.prev(),
					spid = ch.attr('id').split('_')[1];
				if(spid == 'all')
					return;
				if(!_num(ch.val()))
					return;
				ids.push(spid);
			});
		});

		$(ATTR_CMP(el.id)).val(ids.join());

		//запрос сумм
		var send = {
			op:'spisok_92_sum',
			elem_id:el.id,
			ids:ids.join(),
			func_err:function() {
				_forIn(el.vvv, function(sp) {
					$('#el92_' + sp.dlg_id)
						.parent().find('.sum92')
						.html('');
				});
			}
		};
		_post(send, function(res) {
			var itogC = 0,
				itogSum = 0;
			_forIn(res.data, function(sp, dlg_id) {
				var ob = $('#el92_' + dlg_id);
				ob.html(sp.count ? sp.count : '').parent().find('.sum92').html(sp.sum ? sp.sum : '');
				itogC += sp.count;
				itogSum += sp.sum;
			});
			_attr_el(el.id).find('.itog-c').html(itogC);
			_attr_el(el.id).find('.itog-sum').html(itogSum);
		});
	},

	//[95] Быстрое формирование списка
	_EL95 = function(el, vvv) {
		if(!vvv.cols.length)
			return;

		var colName = function() {//вывод названий колонок
				if(!el.num_2)
					return '';

				var send =  '<table class="bs5 w100p">' +
							'<tr><td class="w25">';

				_forN(vvv.cols, function(col) {
					send += '<td class="fs14 clr9" style="width:' + (col.w-10) + 'px">' + col.name;
				});

				send += '<td></table>';
				return send;
			},
			html =  colName() +
					'<dl></dl>' +
					'<div class="fs15 clr9 pad10 center over5 curP">' + el.txt_1 + '</div>';

		var ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last');

		BUT_ADD.click(vAdd);

		if(vvv.mass.length)
			_forIn(vvv.mass, vAdd);
		else
			vAdd({});

		function vAdd(v) {
			v.id = _num(v.id);
			_forN(vvv.cols, function(sp) {
				if(v[sp.col] === undefined)
					v[sp.col] = '';
			});
			html = 	'<dd class="over1" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move pl"></div>';

			_forN(vvv.cols, function(sp) {
				html += '<td style="width:' + (sp.w-10) + 'px">';
				//если колонка не указана, значение для сохранения данных выбираться не будет
				var clsInp = sp.col ? 'el95inp ' : '';
				switch(sp.type) {
					case 1: html += sp.v; break;
					case 2: html += '<input type="text" class="' + clsInp + 'w100p" value="' + v[sp.col] + '">'; break;
					case 3: html += '<input type="hidden" class="' + clsInp + 'el95sel" value="' + _num(v[sp.col]) + '">'; break;
				}
			});

			html += 		'<td class="top pl15">' +
								'<div class="mt5 icon icon-off pl tool" data-tool="Удалить"></div>' +
						'</table>' +
					'</dd>';
			DL.append(html);
			DL._sort({handle:'.icon-move'});

			var DD = DL.find('dd:last');

			//todo только для мебельщиков
			DD.find('.el95inp').eq(1).keyup(function() {
				var count = $(this).val(),
					cena = DD.find('.el95inp').eq(2).val();
				count = count.replace(',', '.');
				DD.find('.el95inp').eq(3).val(_cena(count*cena));
			});
			DD.find('.el95inp').eq(2).keyup(function() {
				var count = DD.find('.el95inp').eq(1).val(),
					cena = $(this).val();
				cena = cena.replace(',', '.');
				DD.find('.el95inp').eq(3).val(_cena(count*cena));
			});

			_forN(vvv.cols, function(col) {
				if(col.type != 3)
					return;

				var spisok = col.spisok;

				DD.find('.el95sel')._select({
					width:0,
					title0:'не выбрано',
					write:1,
					spisok:spisok,
					funcWrite:function(v, t) {
						var send = {
							op:'el95_spisok',
							elem_id:col.v,
							v:v,
							busy_obj:t.icon_del,
							busy_cls:'spin'
						};
						_post(send, function(res) {
							spisok = res.spisok;
							t.spisok(spisok);
						});
					},
					func:function(id) {//todo пока только для мебельщиков
						var o = {};
						_forIn(spisok, function(sp) {
							if(sp.id == id) {
								o = sp;
								return false;
							}
						});

						DD.find('.el95inp').eq(1).val(1);
						DD.find('.el95inp').eq(2).val(o.sum_12);
						DD.find('.el95inp').eq(3).val(o.sum_12);
					}
				});
			});

			DD.find('.icon-off').click(function() {
				DD.remove();
			});

		}
	},
	_EL95_GET = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(dd) {

			var inp = [];
			inp.push(dd.attr('val'));
			_forEq(dd.find('.el95inp'), function(sp) {
				inp.push(sp.val());
			});

			send.push(inp);
		});

		return send;
	},
	PHP12_elem95_setup = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over5 curP">Добавить колонку</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			CALC_DIV = ATR_EL.find('.calc-div'),//div, в котором располагается визуальный подсчёт ячеек
			CALC_W = _num(CALC_DIV.html()),//изначальная ширина блока, в котором размещена таблица
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(function() {
			vAdd();
		});

		//показ-скрытие настройки имён колонок
		_attr_cmp(15415)._check({
			func:function(v) {
				obj.unit.num_2 = v;
				DL.find('.el95colname')['slide' + (v ? 'Down' : 'Up')]();
			}
		});

		if(!vvv)
			vAdd();
		else
			_forIn(vvv, vAdd);

		tdCalc();

		function vAdd(v) {
			v = $.extend({
				w:170,  //ширина
				name:'',//заголовок
				type:0, //тип значения: 1 - текст, 2 - поле, 3 - выпадающий список
				col:'', //имя колонки в базе
				v:'',   //дополнительное значение
				title:''//имя значения
			}, v);

			DL.append(
				'<dd class="ov7 pt10 pb5 line-b1">' +
					'<div class="el95w bg4" style="width:' + v.w + 'px;min-height:10px;margin-left:35px">' +
						'<div class="el95colname' + _dn(obj.unit.num_2) + '">' +
							'<input type="text"' +
								  ' class="colname w100p bg4 center fs14 clr15"' +
								  ' placeholder="имя колонки"' +
								  ' value="' + v.name + '"' +
							' />' +
						'</div>' +
					'</div>' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 r topi">' +
								'<b class="bnum fs15 clr9">' + NUM++ + '</b>:' +
							'<td class="w175">' +
								'<input type="hidden" class="el95type" value="' + v.type + '">' +
							'<td class="w50 el95tdcol">' +
							'<td class="w250 el95cnt">' +
							'<td class="r">' +
								'<div class="icon icon-move pl"></div>' +
							'<td class="w25 pr5 r">' +
								'<div class="icon icon-off el95del pl tool" data-tool="Удалить"></div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({
				handle:'.icon-move',
				update:tdCalc
			});

			var DD = DL.find('dd:last'),
				CNT = DD.find('.el95cnt'),
				colChange = function() {
					//выбор имени колонки из базы для колонки
					if(v.type > 1) {
						html = '<div class="el95col' + _dn(v.col, 'on') + '" val="' + v.col + '">' + (v.col ? v.col : '-') + '</div>';
						DD.find('.el95tdcol').html(html);
						DD.find('.el95col').click(function() {
							var t = $(this);
							_dialogLoad({
								dialog_id:30,
								dss:obj.unit.num_1,
								busy_obj:t,
								func_open:function(res, D) {
									D.content.find('.el37u').click(function() {
										var col = $(this).find('.el37col').html();
										t.html(col).addClass('on').attr('val', col);
										v.col = col;
										D.close();
									});
								}
							});
						});
					}

					switch(v.type) {
						case 1:
							CNT.html('<input type="text" class="el95v w250" placeholder="напишите текст" value="' + v.v + '">')
								.find('input').focus();
							break;
						case 2:
							CNT.html('');
							break;
						case 3:
							CNT.html('<input type="hidden" class="el95v" value="' + v.v + '">')
								.find('input')
								._selem({
									width:250,
									placeholder:'выберите содержание списка',
									dss:obj.unit.num_1,
									title:v.title
								});
							break;
					}
				};

			DD.find('.el95type')._select({
				width:170,
				title0:'выберите тип колонки',
				spisok:[
					{id:1,title:'Текст',content:'Текст<div class="fs12 clr2">Указать текст, который будет отображаться в колонке</div>'},
					{id:2,title:'Текстовое поле',content:'Текстовое поле<div class="fs12 clr2">Возможность внесения данных вручную</div>'},
					{id:3,title:'Выпадающий список',content:'Выпадающий список<div class="fs12 clr2">Выбор значений из выпадающего списка</div>'}
				],
				func:function(id) {
					v.type = id;
					colChange();
				}
			});

			colChange();
			resize95(DD.find('.el95w'));
			if(!v.type)
				tdCalc();

			DD.find('.el95del').click(function() {
				DD.remove();
				tdCalc();
			});
		}

		//включение изменения ширины
		function resize95(div) {
			if(div.hasClass('ui-resizable'))
				return;

			div.resizable({
				minWidth:20,
				maxWidth:530,
				grid:10,
				handles:'e',
				stop:tdCalc
			});
		}

		//пересчёт визуального отображения ячеек по диагонали
		function tdCalc() {
			var html = '',
				DIV_W = 600,
				i = DIV_W / CALC_W,
				FULL_W = 0,
				TDS = [],//массив ширин по каждой ячейке
				ALL_W = 0;//сумма ширины всех ячеек, кроме последней

			_forEq(DL.find('DD'), function(sp) {
				var n = _num(sp.find('.bnum').html()),
					w = sp.find('.el95w').width();
				TDS.push({
					n:n,
					w:Math.round(w*i)-1
				});
				FULL_W += w;
			});

			_forN(TDS, function(o, n) {
				var bg = 'ffc',
					line = ' line-r';
				if(FULL_W > CALC_W) {
					bg = 'fcc';
					i = CALC_W / FULL_W;
					o.w = Math.round(o.w*i);
				}
				if(FULL_W >= CALC_W) {
					ALL_W += (o.w+1);
					if(n == (TDS.length-1)) {
						line = '';
						o.w = DIV_W - ALL_W + o.w + 1;
					}
				}

				html += '<div class="h25 dib center bg-' + bg + line + '" style="width:' + o.w + 'px">' +
							'<div class="fs15 b clr9 pt5">' + o.n + '</div>' +
						'</div>';
			});
			CALC_DIV.html(html);
		}
	},
	PHP12_elem95_setup_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var type = _num(sp.find('.el95type').val()),
				v = sp.find('.el95v').val();

			if(!type)
				return;

			send.push({
				w:sp.find('.el95w').width(),
				name:sp.find('.colname').val(),
				col:sp.find('.el95col').attr('val'),
				type:type,
				v:v
			});
		});
		return send;
	},

	//[102] Фильтр - Выбор нескольких групп значений
	_EL102 = function(el) {
		_attr_el(el.id)._filter102();
	},

	//[103] настройка доступа к страницам для пользователя
	_EL103 = function(el) {
		if(!_attr_el(el.id).find('._check').length)
			return;
		var idsSet = function() {//вставка идентификаторов страниц для отправки данных
			var ids = [];
			_forEq(_attr_el(el.id).find('._check'), function(sp) {
				var ch = sp.prev(),
					id = _num(ch.attr('id').split('_')[1]),
					v = _num(ch.val());
				if(v)
					ids.push(id);
			});

			$(ATTR_CMP(el.id)).val(ids.join());
		};
		_forEq(_attr_el(el.id).find('._check'), function(sp) {
			var prev = sp.prev();
			prev._check({
				func:function(v) {
					prev.parents('table').next()[v ? 'slideDown' : 'slideUp'](200);
					idsSet();
				}
			});
		});
		idsSet();
	},

	//[300] Привязка пользователя к странице ВК
	_EL300 = function(el) {
		var VK300 = $(ATTR_CMP(el.id)).next(),
			INP = VK300.find('input'),
			VK_ICON = VK300.find('.icon-vk'),
			VK_RES = INP.next(),
			VK_SEL,                 //выбранный пользователь в виде html
			VK_ID = $(ATTR_CMP(el.id)).val();  //id выбранного пользователя ВК
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
				busy_cls:'spin',
				func_err:function(res) {
					VK_RES.html('<div class="mt10 clr5">' + res.text + '</div>');
				}
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
				$(ATTR_CMP(el.id)).val(VK_ID);
			})
			//отмена выбранного пользователя
			.off('click', ATTR_EL(el.id) + ' .icon-del-red')
			.on('click', ATTR_EL(el.id) + ' .icon-del-red', function() {
				VK_RES.html('');
				VK_ICON._dn(1);
				$(ATTR_CMP(el.id)).val(0);
				INP.val('')._dn(1).focus();
			});
	},

	//график Столбики
	_EL400 = function(el) {
		var CHART = {},
			Y = (new Date).getFullYear(),//номер выбранного года
			HCY = $('#hcYear' + el.id),
			HCM = $('#hcMon' + el.id),
			chartPrint = function(hc) {
				if(CHART.chartHeight)
					CHART.destroy();
				CHART = Highcharts.chart('chart_' + el.id, {
						chart:{
							type:'column'
						},
						title:{
							text:window['HEAD_' + el.id]
						},
						xAxis:{
							categories:hc.cat,
							labels:{
								style:{
									color:'#333'
								}
							}
						},
						yAxis:{
							title:{
								text:'Количество'
							},
							stackLabels:{
								enabled:true,
								style:{
									fontWeight:'bold',
									color:'black'
								}
							}
						},
						plotOptions:{
					        series:{
						        cursor:_num(HCM.val()) ? 'default' :'pointer'
					        },
							column:{
								stacking:'normal',
								dataLabels:{
									enabled:false
								},
					            events:{
						            click:function(e) {
						            	//если по дням, действия нет
						            	if(_num(HCM.val()))
						            		return;

							            var i = e.point.index;

						            	//нажатие на месяц
							            if(_num(HCY.val())) {
							            	HCM._dropdown(i+1);
							            } else {//нажатие на год
								            HCY._dropdown(e.point.series.xAxis.categories[i]);
								            $('#hcMonDiv' + el.id)._dn(true);
							            }
										chartUpd();
						            }
					            }
				            }
						},
						series:[{
							name:'Все записи',
							data:hc.data
						}]
					});
			},
			chartUpd = function() {
				var send = {
					op:'el400_chart',
					elem_id:el.id,
					year:HCY.val(),
					mon:HCM.val(),
					busy_obj:$('#busy' + el.id)
				};
				_post(send, function(res) {
					window['HEAD_' + el.id] = res.head;
					chartPrint(res);
				});
			};
		HCY._dropdown({
			title0:'Всё время',
			spisok:window['YEAR_SPISOK_' + el.id],
			func:function(y) {
				Y = y;
				$('#hcMonDiv' + el.id)._dn(y);
				HCM._dropdown(0);
				chartUpd();
			}
		});
		HCM._dropdown({
			title0:'месяц',
			spisok:[
				{id:1,title:'январь'},
				{id:2,title:'февраль'},
				{id:3,title:'март'},
				{id:4,title:'апрель'},
				{id:5,title:'май'},
				{id:6,title:'июнь'},
				{id:7,title:'июль'},
				{id:8,title:'август'},
				{id:9,title:'сентябрь'},
				{id:10,title:'октябрь'},
				{id:11,title:'ноябрь'},
				{id:12,title:'декабрь'}
			],
			func:chartUpd
		});
		$('#chart_' + el.id)
			.html('')
			.width(window['WIDTH_' + el.id])
			.height(window['HEIGHT_' + el.id]);

		chartPrint({
			cat:window['CAT_' + el.id],
			data:window['DATA_' + el.id]
		});
	},

	//[401] График по месяцам
	_EL401 = function(el) {
		$('#chart_' + el.id)
			.height(300)
			.width(window['WIDTH_' + el.id])
			.highcharts({
				chart:{
					type:'line'
				},
				title:{
					text:el.txt_1
				},
				xAxis:{
					categories:window['CAT_' + el.id],
					labels:{
						style:{
							color:'#333'
						}
					}
				},
				yAxis:{
					title:{
						text:'Сумма'
					},
					stackLabels:{
						enabled:true,
						style:{
							fontWeight:'bold',
							color:'black'
						}
					}
				},
				plotOptions:{
					column:{
						stacking:'normal',
						dataLabels:{
							enabled:false
						}
					}
				},
				series:window['SERIES_' + el.id]
			});
	},

	_tdCss = function() {//настройка стилей в выплывающем окошке для ячейки таблицы
		var t = $(this),
			v = {
				id:t.attr('id').split('_')[1],//если используется элемент не из базы, можно ставить id="el_sp14"
				use:t.attr('data-use') || 'font color eye link place',//использование вариантов настроек
				font:'',
				color:'',
				pos:''
			};

		if(!v.id)
			return;

		_forIn(t.attr('class').split(' '), function(sp) {
			if(sp == 'b' || sp == 'i' || sp == 'u')
				v.font += ' ' + sp;
			if(sp == 'center' || sp == 'r')
				v.pos = sp;
			if(ELEM_COLOR[sp])
				v.color = sp;
		});

		//преобразование вариантов настроек в ассоциативный массив
		var use = {};
		_forIn(v.use.split(' '), function(sp) {
			use[sp] = 1;
		});

		var msg = '<table class="bs5"><tr>';
		if(use.font)
			msg += '<td class="pt3">' + _elemUnitFont(v);
		if(use.color)
			msg += '<td class="pt3">' + _elemUnitColor(v);
		if(use.eye)
			msg += '<td class="pt3 pl10">' +
					  '<div class="icon icon-eye pl tool" data-tool="Условия отображения"></div>';
		if(use.link)
			msg += '<td class="pl3">' +
					  '<div class="icon icon-usd pl tool" data-tool="Настроить действия"></div>';
		if(use.place)
			msg += '<td class="pt3 pl10" id="elem-pos">' + _elemUnitPlaceMiddle(v, true);

		msg += '</table>';

		t._hint({
			msg:msg,
			side:'right',
			show:1,
			delayShow:700,
			delayHide:300,
			func:function(o) {
				o.find('.icon-usd').click(function() {
					_dialogLoad({
						dialog_id:200,
						element_id:v.id,
						busy_obj:$(this),
						busy_cls:'spin'
					});
				});
				o.find('.icon-eye').click(function() {
					_dialogLoad({
						dialog_id:240,
						element_id:v.id,
						busy_obj:$(this),
						busy_cls:'spin'
					});
				});
			}
		});
	},
	_tdCssFontGet= function(sp) {//получение стилей для сохранения (выделение: b, i, u)
		var arr = ['b', 'i', 'u'],
			font = [];
		for(var k in arr)
			if(sp.hasClass(arr[k]))
				font.push(arr[k]);
		return font.join(' ');
	},
	_tdCssColorGet= function(sp) {//получение стилей для сохранения (цвет текста)
		for(var k in ELEM_COLOR)
			if(sp.hasClass(k))
				return k;
		return '';
	},

	//Перетаскивание независимой кнопки
	_elem97move = function(elem_id) {
		var startSet = false,
			startX = 0,
			startY = 0;
		$('#but97_' + elem_id).draggable({
			grid:[5,5],
			start:function(event, ui) {
				if(startSet)
					return;
				startX = ui.position.left;
				startY = ui.position.top;
				startSet = true;
			},
			stop:function(event, ui) {
				var COORD = $(this).attr('data-coord').split(':'),
					x = COORD[0] * 1,
					y = COORD[1] * 1,
					send = {
						op:'el97_move_save',
						elem_id:elem_id,
						x:ui.position.left + x - startX,
						y:ui.position.top + y - startY
					};
				_post(send);
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
			sp._sort({elem_id:el.id});
		});
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
			maxLevels:6,
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

	/* ---=== ВЫБОР ЦВЕТА КНОПКИ [2] ===--- */
	PHP12_button_color = function(el) {
		var DIV = _attr_el(el.id).find('div');
		DIV.click(function() {
			var t = $(this),
				id = _num(t.attr('val'));

			DIV.removeClass('sel');
			t.addClass('sel');

			_attr_cmp(el.id).val(id);
		});
	},

	/* ---=== ВЫБОР ВНЕШНЕГО ВИДА МЕНЮ СТРАНИЦ [3] ===--- */
	PHP12_page_menu_type = function(el) {
		var DIV = _attr_el(el.id).find('div');
		DIV.click(function() {
			var t = $(this),
				id = _num(t.attr('val'));

			DIV.removeClass('sel');
			t.addClass('sel');

			_attr_cmp(el.id).val(id);
		});
	},

	/* ---=== НАСТРОЙКА ВОЗДЕЙСТВИЯ НА ЗАПИСЬ ПОСЛЕ ВНЕСЕНИЯ ДАННЫХ [42] ===--- */
	PHP12_insert_unit_change = function(el, vvv, obj) {
		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>',
			DL = ATR_EL.append(html).find('dl');

		_forN(vvv, function(sp) {
			DL.append(
				'<dd class="mt5">' +
					'<table>' +
						'<tr><td><input type="text"' +
									  ' class="inp-dst w200 clr8 curD"' +
									  ' readonly' +
									  ' val="' + sp.dst_id + '"' +
									  ' value="' + sp.dst_title + '"' +
								' />' +
							'<td class="w25 center fs17 clr1"> &laquo; ' +
							'<td><input type="text"' +
									  ' class="inp-src w200 clr11 curP over1"' +
									  ' readonly' +
									  ' val="' + sp.src_id + '"' +
									  ' value="' + sp.src_title + '"' +
									  ' placeholder="не изменять"' +
								' />' +
							'<td><div class="icon icon-del pl' + _dn(sp.src_id) + ' tool" data-tool="Отменить выбор"></div>' +
					'</table>' +
				'</dd>'
			);
			var DD = DL.find('dd:last'),
				SRC = DD.find('.inp-src'),
				DEL = DD.find('.icon-del');
			SRC.click(function() {
				_dialogLoad({
					dialog_id:11,
					dss:obj.unit.id,
					dop:{
						mysave:1,
						sel:_num($(this).attr('val')),
						nest:0
					},
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						SRC.attr('val', res.v);
						SRC.val(res.title);
						DEL._dn(1);
					}
				});
			});
			DEL.click(function() {
				SRC.attr('val', 0);
				SRC.val('');
				DEL._dn();
			});
		});
	},
	PHP12_insert_unit_change_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var src_id = _num(sp.find('.inp-src').attr('val'));
			if(!src_id)
				return;
			send.push(
				_num(sp.find('.inp-dst').attr('val')) +
				':' +
				src_id
			);
		});
		_attr_cmp(el.id).val(send.join());
	},

	/* [34] СУММЫ СПИСКОВ ПО МЕСЯЦАМ - НАСТРОЙКА */
	PHP12_elem34 = function(el, vvv, obj) {
		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over5 curP">Добавить список</div>',
			ATTR_EL = _attr_el(el.id),
			DL = ATTR_EL.append(html).find('dl');

		ATTR_EL.find('div:last').click(valueAdd);

		if(!obj.unit.id) {
			valueAdd();
		} else
			_forIn(vvv.val, valueAdd);

		function valueAdd(v) {
			v = $.extend({
				title:'',    //заголовок
				dialog_id:'',//id списка
				sum_id:0,    //id элемента-суммы
				sum_title:'',//имя элемента-суммы
				cond:'',     //условия
				c:''         //количество условий
			}, v);

			DL.append(
				'<dd class="over5">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text" class="title w100p" placeholder="Заголовок не указан" value="' + v.title + '">' +
							'<td><input type="hidden" class="dlg34" value="' + v.dialog_id + '" />' +
							'<td><input type="hidden" class="sum34" value="' + v.sum_id + '" />' +
								'<div class="_selem dib prel bg-fff over3">' +
									'<div class="icon icon-star pabs"></div>' +
									'<div class="icon icon-del pl pabs' + _dn(v.sum_id) + '"></div>' +
									'<input type="text" readonly class="w175 curP clr11" placeholder="сумма не выбрана" value="' + v.sum_title + '" />' +
								'</div>' +
							'<td><input type="hidden" class="cond" />' +
								'<div class="_spfl dib w125 prel">' +
									'<div class="icon icon-filter pabs"></div>' +
									'<div class="icon icon-del pl pabs' + _dn(v.cond) + '"></div>' +
									'<input type="text" readonly class="filter clr7 b pl25 curP w100p over3" placeholder="условий нет" value="' + v.c + '" />' +
								'</div>' +
							'<td class="w25 r top pt5">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить список"></div>' +
					'</table>' +
				'</dd>'
			);

			DL.sortable({handle:'.icon-move-y'});

			var DD = DL.find('dd:last');

			DD.find('.dlg34')._select({
				width:260,
				title0:'список не выбран',
				spisok:vvv.sp
			});

			DD.find('.cond').val(v.cond);

			//выбор значения суммы
			DD.find('._selem').click(function() {
				var t = $(this),
					cmp = t.prev(),
					inp = t.find('input'),
					del = t.find('.icon-del'),
					dss = _num(DD.find('.dlg34').val());

				if(!dss)
					return false;

				_dialogLoad({
					dialog_id:11,
					dss:dss,

					dop:{
						mysave:1,
						sel:cmp.val()
					},

					busy_obj:inp,
					busy_cls:'hold',
					func_save:function(res) {
						cmp.val(res.v);
						inp.val(res.title);
						del._dn(1);
					}
				});
			});

			DD.find('._spfl').click(function() {
				var t = $(this),
					inp = t.find('input'),
					dss = _num(DD.find('.dlg34').val());

				if(!dss)
					return false;

				_dialogLoad({
					dialog_id:41,
					dss:dss,
					dop:t.prev().val(),
					busy_obj:inp,
					busy_cls:'hold',
					func_save:function(res) {
						t.prev().val(res.v);
						inp.val(res.title);
						t.find('.icon-del')._dn(1);
					}
				});
			});

			DD.find('.icon-del').click(function() {
				$(this).closest('DD').remove();
			});
			//установка фокуса на новодобавленного значения
			if(!v.id)
				DD.find('.title').focus();
		}
	},
	PHP12_elem34_get = function(el) {
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			var dlg_id = _num(sp.find('.dlg34').val());
			if(!dlg_id)
				return;
			send.push({
				title:sp.find('.title').val(),
				dialog_id:dlg_id,
				sum_id:_num(sp.find('.sum34').val()),
				cond:sp.find('.cond').val()
			});
		});
		return send;
	},

	/* ---=== КРАТКАЯ СВОДКА ПО СПИСКУ: НАСТРОЙКА ГРУППИРОВКИ [79] ===--- */
	PHP12_elem79_group_setup = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			_vSave = function() {
				var arr = [];
				_forEq(_attr_el(el.id).find('TR'), function(sp) {
					var group_id = sp.find('.inp79').eq(0).attr('val'),
						sum_id = sp.find('.inp79').eq(1).attr('val');
					arr.push('["' + group_id + '","' + sum_id + '"]');
				});
				_attr_cmp(el.id).val('[' + arr.join() + ']');
			};

		ATR_EL.find('.inp79').click(function() {
			var INP = $(this);

			_dialogLoad({
				dialog_id:11,
				dss:INP.parents('TR').attr('data-dlg'),
				dop:{
					mysave:1,
					nest:1,
					allow:'5,8,29,59',
					sel:INP.attr('val')
				},
				busy_obj:INP,
				busy_cls:'hold',
				func_save:function(ia) {
					INP.attr('val', ia.v)
					   .val(ia.title)
					   .prev()._dn(true);
					_vSave();
				}
			});
		});
		ATR_EL.find('.icon-del-red').click(function() {
			var t = $(this);
			t._dn().next().val('').attr('val', 0);
			_vSave();
		});
	},

	/* ---=== ВЫБОР ИКОНКИ [36] ===--- */
	PHP12_icon18_list = function(el) {
		var ICU = _attr_el(el.id).find('.icu');
		ICU.click(function() {
			var t = $(this),
				id = _num(t.attr('val'));

			ICU.removeClass('sel');
			t.addClass('sel');

			_attr_cmp(el.id).val(id);
		});
	},

	/* ---=== СПИСОК ДЕЙСТВИЙ, НАЗНАЧЕННЫЕ ЭЛЕМЕНТУ ИЛИ БЛОКУ ===--- */
	PHP12_action_list = function(el) {
		_attr_el(el.id).find('DL')._sort({table:'_action',elem_id:el.id});
	},

	/* ---=== НАСТРОЙКА ПАРАМЕТРОВ ШАБЛОНА ДЛЯ ДОКУМЕНТОВ [114] ===--- */
	PHP12_template_param = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over5 curP">Добавить параметр</div>',
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
									  ' class="inp w250 curP clr11"' +
									  ' readonly' +
									  ' placeholder="значение не выбрано"' +
									  ' value="' + (v.title || v.id || '') + '"' +
								' />' +
							'<td class="w50 r">' +
								'<div class="icon icon-del pl tool" data-tool="Удалить параметр"></div>' +
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

	/* ---=== НАСТРОЙКА ЗНАЧЕНИЙ ДЛЯ ПЛАНИРОВЩИКА [115] ===--- */
	PHP12_cron_dst_prm = function(el, vvv, obj) {
		if(!obj.unit.id)
			return;

		var ATR_EL = _attr_el(el.id),
			html = '<dl></dl>',
			DL = ATR_EL.append(html).find('dl');

		_forN(vvv, function(sp, n) {
			DL.append(
				'<dd class="mt5">' +
					'<table>' +
						'<tr><td><input type="text"' +
									  ' class="inp-dst w200 clr8 curD"' +
									  ' readonly' +
									  ' val="' + sp.dst_id + '"' +
									  ' value="' + sp.dst_title + '"' +
								' />' +
							'<td class="w25 center fs17 clr1"> &laquo; ' +
							'<td><input type="text"' +
									  ' class="inp-src w200 clr11 curP over1"' +
									  ' readonly' +
									  ' val="' + sp.src_id + '"' +
									  ' value="' + sp.src_title + '"' +
									  ' placeholder="авто"' +
								' />' +
							'<td><div class="icon icon-del pl' + _dn(sp.src_id) + ' tool" data-tool="Отменить выбор"></div>' +
					'</table>' +
				'</dd>'
			);
			var DD = DL.find('dd:last'),
				SRC = DD.find('.inp-src'),
				DEL = DD.find('.icon-del');
			SRC.click(function() {
				_dialogLoad({
					dialog_id:11,
					dss:obj.unit.src_spisok,
					dop:{
						mysave:1,
						sel:_num($(this).attr('val')),
						nest:0
					},
					busy_obj:$(this),
					busy_cls:'hold',
					func_save:function(res) {
						SRC.attr('val', res.v);
						SRC.val(res.title);
						DEL._dn(1);
					}
				});
			});
			DEL.click(function() {
				SRC.attr('val', 0);
				SRC.val('');
				DEL._dn();
			});
		});
	},
	PHP12_cron_dst_prm_get = function(el) {//получение данных для сохранения
		var send = [];
		_forEq(_attr_el(el.id).find('dd'), function(sp) {
			send.push(
				_num(sp.find('.inp-dst').attr('val')) +
				':' +
				_num(sp.find('.inp-src').attr('val'))
			);
		});
		_attr_cmp(el.id).val(send.join());
	},

	/* ---=== НАСТРОЙКА ШАБЛОНА ИСТОРИИ ДЕЙСТВИЙ [67] ===--- */
	PHP12_history_setup = function(el, vvv, obj) {
		var html = '<dl></dl>' +
				   '<div class="fs15 clr9 pad10 center over1 curP">Добавить сборку</div>',
			ATR_EL = _attr_el(el.id),
			DL = ATR_EL.append(html).find('dl'),
			BUT_ADD = ATR_EL.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		if(!vvv.length)
			valueAdd();
		else
			_forIn(vvv, valueAdd);

		DL.sortable({handle:'.icon-move-y'});

		function _cc(count) {//количество условий в текстовом виде
			if(!count)
				return 'без условий';
			return count  + ' услови' + _end(count, ['е', 'я', 'й']);
		}
		function valueAdd(v) {
			v = $.extend({
				id:0,     //id элемента-сборки
				dialog_id:50,  //id диалога, вносившего элемента-значения
				title:'', //имя элемента-значения
				txt_7:'', //текст слева
				txt_8:'', //текст справа
				c:0,      //количество условий
				txt_9:'', //условия отображения сборки
				font:'',  //выделение: b, i, u
				color:''  //цвет текста
			}, v || {});

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w100 center">' +
								'<a class="cs ' + (v.c ? 'clr8 b' : 'clr2') + ' tool" data-tool="Настроить условия<br>показа сборки">' + _cc(v.c) + '</a>' +
								'<input type="hidden" class="txt_9" value="' + v.txt_9 + '" />' +
							'<td class="w250">' +
								'<input type="text"' +
									  ' class="txt_7 w100p"' +
									  ' placeholder="текст слева"' +
									  ' value="' + v.txt_7 + '"' +
								' />' +
							'<td class="w250">' +
								'<input type="text"' +
									  ' readonly' +
									  ' id="' + ATTR_EL(v.id, true) + '"' +
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
								'<div class="icon icon-del pl tool" data-tool="Удалить сборку"></div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				TITLE = DD.find('.title');

			//настройка условий для отображения сборки
			DD.find('.cs').click(function() {
				_dialogLoad({
					dialog_id:41,
					dss:obj.srce.dss,
					dop:DD.find('.txt_9').val(),
					busy_obj:$(this),
					func_save:function(res) {
						DD.find('.txt_9').val(res.v);
						DD.find('.cs')
							.html(_cc(res.c))
							._dn(res.c, 'clr2')
							._dn(!res.c, 'clr8 b');
					}
				});
			});

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
						TITLE.attr('id', ATTR_EL(v.id, true))
							 .val(res.unit.title);
						DD.find('.txt_8').focus();
					}
				});
			});

			//отображение выплывающего окна настройки стилей
			TITLE.mouseenter(function() {
				if(!v.dialog_id)
					return;
				if(TITLE.hasClass('hold'))
					return;
				TITLE._hint({
					msg:'<table class="bs5">' +
							'<tr><td class="pt3">' + _elemUnitFont(v) +
								'<td class="pt3">' + _elemUnitColor(v) +
								'<td class="pt3">' + _elemUnitFormat(v) +
								'<td class="pt3">' +
									'<div class="icon icon-usd ml3 tool" data-tool="Настроить действия"></div>' +
						'</table>',
					side:'top',
					ugPos:'left',
					objPos:20,
					show:1,
					delayShow:500,
					delayHide:300,
					func:function(o) {
						o.find('.icon-usd').click(function() {
							var url = $(this);

							//снятие ссылки
							if(url.hasClass('on')) {
								url.removeClass('on');
								DD.attr('data-url', 0);
								return false;
							}

							_dialogLoad({
								dialog_id:200,
								element_id:v.id,
								busy_obj:$(this),
								func_save:function(res) {
								}
							});
						});
					}
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
					txt_9:sp.find('.txt_9').val(),
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

	//Шаблон данных записи
	PHP12_tmp_setup = function(el, vvv) {
		BLKK = _objUpd(BLKK, vvv.jsblk);
		ELMM = _objUpd(ELMM, vvv.jselm);
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

	//ссылка на приглашение для пользователя
	PHP12_user_invite = function(el) {
		var INP = _attr_el(el.id).find('input');
		if(!INP.hasClass('clr15'))
			return;
		_attr_el(el.id).find('input,.icon').click(function() {
			INP.select();
			document.execCommand("copy");
			_msg('Скопировано в буфер обмена');
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

	//просмотр изображений [65]
	PHP12_image_show = function() {
		var IMS = $('#_image-show'),
			IU = IMS.find('.iu'),
			IMAIN = $('#_image-main'),
			IMAIN_H = IMAIN.height(),
			imNext = function(next_id) {//установка следующего изображения
				var im = IMG_ASS[next_id];
				IU.removeClass('sel');
				IMAIN.html('<img src="' + im.src + '" width="' + im.x + '" height="' + im.y + '" />');
				IMAIN.attr('val', next_id);
				if(IMAIN_H < IMAIN.height()) {
					IMAIN_H = IMAIN.height();
					IMAIN.height(IMAIN_H);
				}
				_forEq(IU, function(sp) {
					var id = _num(sp.attr('val'));
					if(id == next_id) {
						sp.addClass('sel');
						return false;
					}
				});
			};
		IMAIN.height(IMAIN_H);
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

	//список приложений
	PHP12_app_list = function(el) {
		_attr_el(el.id)._sort({
			items:'.line-b',
			elem_id:el.id
		});
	},

	//список диалоговых окон для конкретного приложения
	PHP12_dialog_app = function(el) {
		_attr_el(el.id).find('ol.dialog-sort').nestedSortable({
			forcePlaceholderSize:true,//сохранять размер места, откуда был взят элемент
			placeholder:'page-sort-hold', //класс, применяемый для подсветки места, откуда взялся элемент
			listType:'ol',
			items:'li',
			handle:'.icon-move',
			isTree:1,
			maxLevels:3,
			tabSize:20, //расстояние, на которое надо сместить элемент, чтобы он перешёл на другой уровень
			revert:200, //плавное возвращение (полёт) элемента на своё место. Цифра - скорость в миллисекундах.

			start:function(e, t) {//установка отступа снизу
				if($(t.placeholder).prev().hasClass('mb5'))
					$(t.placeholder).addClass('mb5');
				if($(t.placeholder).prev().hasClass('mb1'))
					$(t.placeholder).addClass('mb1');
			},
			update:function(e, t) {
				var send = {
					op:'dialog_sort',
					arr:$(this).nestedSortable('toArray'),
					busy_obj:_attr_el(el.id),
					busy_cls:'spisok-busy'
				};
				_post(send, function() {
					var item = $(t.item),
						p = item.parent(),
						prn = p.hasClass('dialog-sort');

					item.removeClass(prn ? 'mb1' : 'mb5');
					item.addClass(!prn ? 'mb1' : 'mb5');
					item.find('.d-name:first')._dn(!prn, 'b');
				});
			},

			errorClass:'bg-fcc'  //ошибка, если попытка переместить элемент на недоступный уровень
		});
	},

	//[118] Информация об элементе
	PHP12_elem_info = function(el) {
		if(!el.vvv)
			return;
		if(!ELMM[el.vvv])
			return;
		_attr_el(el.id).find('.js').html(_pr(ELMM[el.vvv]));
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
			if(k == 'dialog_close') {
				send.func_open_before = function() {
					DIALOG[_num(spl[1])].close();
				};
				return;
			}
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
			dop:id,
			busy_obj:t.parent()
		});
	});



