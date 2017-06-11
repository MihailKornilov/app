/* Все элементы визуального отображения, используемые в приложении */
var VK_SCROLL = 0,
	ZINDEX = 0,
	BC = 0,

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
		var body = $('body');
		if(add) {
			ZINDEX += 10;
			if(!BC) {
				body.find('._backfon').remove().end()
					.append('<div class="_backfon"></div>');
			}
			var backfon = body.find('._backfon');
			backfon.css({'z-index':ZINDEX});
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
			var backfon = body.find('._backfon');
			if(!BC)
				backfon.remove();
			else
				backfon.css({'z-index':ZINDEX});
		}
	},

	_dialog = function(o) {//диалоговое окно
		o = $.extend({
			top:100,
			width:380,
			mb:0,       //margin-bottom: отступ снизу от диалога (для календаря или выпадающих списков)
			padding:10, //отступ для content
			dialog_id:0,//ID диалога, загруженного из базы
			edit:0,     //диалог редактируется
			id:'',      //идентификатор диалога: для определения открытого такого же, чтобы предварительно закрывать его
			head:'head: Название заголовка',
			load:0, // Показ процесса ожидания загрузки в центре диалога
			class:'',//дополнительный класс для content
			content:'content: содержимое центрального поля',
			submit:function() {},
			cancel:function() {},
			butSubmit:'Внести',
			butCancel:'Отмена'
		}, o);

		var frameNum = $('.dFrame').length;

		//закрытие диалога с тем же идентификатором
		if(o.id && $('#' + o.id + '_dialog').length) {
			$('#' + o.id + '_dialog').remove();
			_backfon(false);
			if(frameNum == 0)
				DIALOG_MAXHEIGHT = 0;
//			_fbhs();
		}

		if(o.load)
			o.content =
				'<div class="load _busy">' +
					'<div class="ms center red">В процессе загрузки произошла ошибка.</div>' +
				'</div>';

		var html =
			'<div class="_dialog"' + (o.id ? ' id="' + o.id + '_dialog"' : '') + '>' +
				'<div class="head' + (o.edit ? ' edit' : '') + '">' +
					'<div class="close fr curP"><a class="icon icon-del-white"></a></div>' +

	 (o.dialog_id && !o.edit ?
		            '<div class="edit fr curP"><a class="icon icon-edit-white"></a></div>'
	 : '') +

		(o.edit ?	'<div class="inp"><input type="text" placeholder="название диалогового окна" /></div>'
					:
					'<div class="fs14 white">' + o.head + '</div>'
		) +

				'</div>' +
				'<div class="dcntr">' +
					'<iframe class="dFrame" name="dFrame' + frameNum + '"></iframe>' +
					'<div class="content bg-fff' + (o.class ? ' ' + o.class + '_dialog' : '') + '"' + (o.padding ? ' style="padding:' + o.padding + 'px"' : '') + '>' +
						o.content +
					'</div>' +
				'</div>' +
				'<div class="bottom">' +
					'<button class="vk submit mr10' + (o.butSubmit ? '' : ' dn') + (o.edit ? ' edit' : '') + '">' + o.butSubmit + '</button>' +
					'<button class="vk cancel' + (o.butCancel ? '' : ' dn') + '">' + o.butCancel + '</button>' +
				'</div>' +
			'</div>';

		// Если открывается первый диалог на странице, запоминается стартовая максимальная высота диалогов
		if(frameNum == 0)
			DIALOG_MAXHEIGHT = 0;

		var dialog = $('body').append(html).find('._dialog:last'),
			content = dialog.find('.content'),
			bottom = dialog.find('.bottom'),
			butSubmit = bottom.find('.submit'),
			butCancel = bottom.find('.cancel'),
			submitFunc = function() {
				if(butSubmit.hasClass('_busy'))
					return;
				o.submit();
			},
			w2 = Math.round(o.width / 2); // ширина/2. Для определения положения по центру
		dialog.find('.close').click(dialogClose);
		butSubmit.click(submitFunc);
		butCancel.click(function(e) {
			e.stopPropagation();
			dialogClose();
			o.cancel();
			if(o.dialog_id && o.edit)
				_dialogOpen(o.dialog_id);
		});

		//для всех input при нажатии enter применяется submit
		content.find('input').keyEnter(submitFunc);

		_backfon();

		dialog.css({
			width:o.width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		dialog.find('.head input').width(o.width - 80);//установка ширины INPUT заголовка при редактировании диалога
		dialog.find('.head .edit').click(function() {
			dialogClose();
			_dialogEdit(o.dialog_id);
		});


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

		function dialogClose() {
			dialog.remove();
			_backfon(false);
			if(frameNum == 0)
				DIALOG_MAXHEIGHT = 0;
//			_fbhs();
		}
		function dialogErr(msg) {
			butSubmit._hint({
				msg:msg,
				red:1,
				show:1,
				remove:1
			});
		}
		function loadError(msg) {//ошибка загрузки данных для диалога
			dialog.find('.load').removeClass('_busy');
			if(msg)
				dialog.find('.ms').append('<br /><br /><b>' + msg + '</b>');
		}

		return {
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
					}
				}, 'json');
			},
			head:function(v) {//установка текста заголовка
				dialog.find('.head .white').html(v);
			},
			editHead:function(v) {//получение/установка текста заголовка в INPUT
				var inp = dialog.find('.head input');
				if(v) {
					inp.val(v).focus();
					return;
				}
				return inp.val();
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
	},
	_dialogEdit = function(dialog_id) {//создание диалогового окна
		dialog_id = _num(dialog_id);
		var dialog = _dialog({
				edit:1,
				dialog_id:dialog_id,
				width:500,
				top:20,
				padding:0,
				head:'Диалог',
				load:1,
				submit:submit
			}),
			send = {
				op:'dialog_edit_load',
				dialog_id:dialog_id
			},
			DIALOG_WIDTH;

		window.LABEL_WIDTH = 125;
		window.DIALOG_ELEMENT = [];

		dialog.load(send, loaded);

		function loaded(res) {
			dialog_id = res.dialog_id;
			dialog.width(res.width);
			dialog.editHead(res.head);
			dialog.content.html(res.html);
			dialog.butSubmit((dialog_id ? 'Сохранить' : 'Создать') + ' диалоговое окно');
			DIALOG_ELEMENT = res.element;
			_dialogScript(res.element, 1);
			sortable();
			elementEdit();
			elementDel();

			_dialogHeightCorrect();

			//установка линии для настройки ширины диалога
			DIALOG_WIDTH = res.width;
			$('#dialog-w-change')
				.css('left', (DIALOG_WIDTH + 8) + 'px')
				.draggable({
					axis:'x',
					grid:[10,0],
					drag:function(event, ui) {
						DIALOG_WIDTH = ui.position.left - 8;
						dialog.width(DIALOG_WIDTH);
					}
				});

			//установка линии для настройки ширины полей с названиями
			LABEL_WIDTH = res.label_width;
			$('#label-w-change')
				.css('left', (LABEL_WIDTH + 12) + 'px')
				.draggable({
					axis:'x',
					grid:[10,0],
					containment:'parent',
					drag:function(event, ui) {
						LABEL_WIDTH = ui.position.left - 12;
						$('.label-width').width(LABEL_WIDTH);
					}
				});
		}
		function elementEdit() {//редактирование элемента
			$(document).off('click', '.element-edit');
			$(document).on('click', '.element-edit', function() {
				var p = $(this).parent(),
					id = _num(p.attr('val')),
					sp = {};
				for(var n = 0; n < DIALOG_ELEMENT.length; n++) {
					sp = DIALOG_ELEMENT[n];
					if(sp.id == id)
						break;
				}
				_dialogEditElement(sp);
			});
		}
		function elementDel() {//удаление элемента
			$(document).off('click', '.element-del');
			$(document).on('click', '.element-del', function() {
				var p = $(this).parent(),
					id = _num(p.attr('val'));
				p.remove();
				for(var n = 0; n < DIALOG_ELEMENT.length; n++) {
					var sp = DIALOG_ELEMENT[n];
					if(sp.id == id) {
						DIALOG_ELEMENT.splice(n, 1);
						break;
					}
				}
				_dialogHeightCorrect();
			});
		}
		function submit() {
			send = {
				op:'dialog_' + (dialog_id ? 'edit' : 'add'),
				dialog_id:dialog_id,
				head:dialog.editHead(),
				width:DIALOG_WIDTH,
				label_width:LABEL_WIDTH,
				button_submit:$('#button_submit').val(),
				button_cancel:$('#button_cancel').val(),
				element:DIALOG_ELEMENT
			};
			dialog.post(send, function(res) {
				_dialogOpen(res.dialog_id);
			});
		}
	},
	_dialogHeightCorrect = function() {//установка высоты линий для настройки ширины диалога и ширины полей с названиями
		var h = $('#dialog-w-change').parent().height();
		$('#dialog-w-change').height(h);
		h = $('#dialog-base').height();
		$('#label-w-change').height(h);
	},
	_dialogEditElement = function(EL) {//добавление нового элемента
		EL = $.extend({
			id:0,
			type_id:0,
			label_name:'',
			require:0,
			hint:'',
			width:0,
			param_txt_1:'',
			param_bool_1:0,
			param_bool_2:0,
			v:[]
		}, EL);
		var html =
				'<div id="element-unit">' +
					'<div val="3" class="over1 line-b element element-input">Однострочный текст</div>' +
					'<div val="4" class="over1 line-b element element-textaera">Многострочный текст</div>' +
					'<div val="2" class="over1 line-b element element-select">Выпадающий список</div>' +
					'<div val="1" class="over1 line-b element">Галочка</div>' +
					'<div val="5" class="over1 line-b element element-radio">Radio</div>' +
					'<div val="6" class="over1 element element-calendar">Календарь</div>' +
				'</div>' +
				'<div id="element-sel" class="dn">' +
					'<div class="fs16">Элемент <b id="element-name" class="fs16"></b></div>' +
					'<table id="element-main" class="bs10 mt10">' +
						'<tr><td class="label r b">Название поля:' +
							'<td><input type="text" id="label-name" class="w250" value="' + EL.label_name + '" />' +
						'<tr id="tr-require" class="dn">' +
							'<td>' +
							'<td><input type="hidden" id="label-require" value="' + EL.require + '" />' +
						'<tr><td class="label r topi">Текст подсказки:' +
							'<td><textarea id="label-hint" class="w250">' + EL.hint + '</textarea>' +
					'</table>' +

					'<div class="line-b mt10"></div>' +

					'<div class="fs15 color-555 mt30">Предварительный просмотр:</div>' +
					'<div class="pad10 mt10 bor-f0 bg-ffe">' +
						'<table class="bs10">' +
							'<tr><td id="prev-label" class="label r w150">' +
								'<td id="prev-element">' +
						'</table>' +
					'</div>' +

				'</div>',
			dialog = _dialog({
				width:520,
				top:30,
				padding:20,
				head:(EL.id ? 'Редактирование' : 'Добавление') + ' элемента диалога',
				content:html,
				butSubmit:'',
				submit:submit
			}),
			TYPE_ID = EL.type_id,//выбранный элемент
			RADIO_ASS = [];

		$('#element-unit div').click(function() {
			var t = $(this);
			if(EL.id) {
				t.parent().hide();
				$('#element-sel').removeClass('dn');
				$('#label-name').focus();
				dialog.butSubmit('Сохранить изменения');
				elementHtml();
				elementScript();
				return;
			}

			TYPE_ID = _num(t.attr('val'));
			t.parent().slideUp(200, function() {
				$('#element-sel').slideDown(200, function() {
					$('#label-name').focus();
				});
				dialog.butSubmit('Добавить элемент');
			});
			elementHtml();
			elementScript();
		});
		$('#label-name').keyup(function() {
			var txt = $.trim($(this).val()),
				require = _bool($('#label-require').val()),
				hint = $.trim($('#label-hint').val());
			txt =
				txt +
				(txt ? ':' : '') +
				(require ? '<div class="dib red fs15 mtm2">*</div>' : '') +
				(hint ?' <div class="icon icon-hint dialog-hint-edit"></div>' : '');
			$('#prev-label').html(txt);
		});
		$('#label-hint').keyup(function() {
			$('#label-name').trigger('keyup');
		}).autosize();

		//вывод текста подсказки при наведении
		$(document).off('mouseenter', '.dialog-hint-edit');
		$(document).on('mouseenter', '.dialog-hint-edit', function() {
			$('#label-hint').focus().blur();
			var msg = _br($('#label-hint').val(), 1),
				brAdd = (msg.split('<br />').length - 1) * 15;

			$(this)._hint({
				msg:msg,
				show:1,
				delayHide:100,
				remove:1
			});
		});


		if(TYPE_ID) {
			$('#element-unit div').eq(TYPE_ID - 1).trigger('click');
			$('#label-name').trigger('keyup');
		}

		function elementHtml() {//вставка основных данных в таблицу для конкретного элемента
			var name = '',
				main = '',
				prev = '';
			switch(TYPE_ID) {
				case 1:
					name = 'Галочка';
					main =
						'<tr><td class="label r">Текст для галочки:' +
							'<td><input type="text" class="w250" id="param_txt_1" value="' + EL.param_txt_1 + '" />';
					prev = '<input type="hidden" id="elem-attr-id" />';
					break;
				case 2:
					name = 'Выпадающий список';
					main =
						'<tr><td class="label r">Текст по умолчанию:' +
							'<td><input type="text" class="w250" id="param_txt_1" value="не выбрано" />';
					prev = '<input type="hidden" id="elem-attr-id" />';
					break;
				case 3:
					name = 'Однострочный текст';
					main =
						'<tr><td class="label r">Комментарий:' +
							'<td><input type="text" class="w250" id="param_txt_1" value="' + EL.param_txt_1 + '" />';
					prev = '<input type="text" id="elem-attr-id" class="w250" />';
					break;
				case 4:
					name = 'Многострочный текст';
					main =
						'<tr><td class="label r">Комментарий:' +
							'<td><input type="text" class="w250" id="param_txt_1" value="' + EL.param_txt_1 + '" />';
					prev = '<textarea id="elem-attr-id" class="w250"></textarea>';
					break;
				case 5:
					name = 'Радио';
					prev = '<input type="hidden" id="elem-attr-id" />';
					break;
				case 6:
					name = 'Календарь';
					main =
						'<tr><td class="label r">Выбор прошедших дней:' +
							'<td><input type="hidden" id="param_bool_1" />' +
						'<tr><td class="label r">Ссылка <u>завтра</u>:' +
							'<td><input type="hidden" id="param_bool_2" />';
					prev = '<input type="hidden" id="elem-attr-id" />';
					break;
			}
			$('#element-main').append(main);
			$('#prev-element').html(prev);
			$('#element-name').html(name);
		}
		function elementRequire() {
			$('#tr-require').removeClass('dn');
			$('#label-require')._check({
				title:'требовать обязательное заполнение',
				light:1,
				func:function() {
					$('#label-name').trigger('keyup');
				}
			});
		}
		function elementScript() {//применение скриптов для конкретного элемента
			switch(TYPE_ID) {
				case 1://check
					elementActionSel = function() {
						$('#elem-attr-id')._check({
							title:$.trim($('#param_txt_1').val()),
							light:1
						});
					};
					$('#param_txt_1').keyup(elementActionSel);
					elementActionSel();
					break;

				case 2://select
					elementRequire();
					elementActionSel = function() {
						$('#elem-attr-id')._select({
							width:200,
							title0:$.trim($('#param_txt_1').val()),
							spisok:[]
						});
					};
					$('#param_txt_1').keyup(elementActionSel);
					elementActionSel();
					break;

				case 3://text
					elementRequire();
					elementActionSel = function() {
						var txt = $.trim($('#param_txt_1').val());
						$('#elem-attr-id').attr('placeholder', txt);
					};
					$('#param_txt_1').keyup(elementActionSel);
					elementActionSel();
					break;

				case 4://textarea
					elementRequire();
					$('#prev-label').addClass('topi');
					elementActionSel = function() {
						var txt = $.trim($('#param_txt_1').val());
						$('#elem-attr-id').attr('placeholder', txt);
					};
					$('#param_txt_1').keyup(elementActionSel);
					elementActionSel();
					$('#elem-attr-id').autosize();
					break;
				case 5://radio
					elementRequire();
					$('#prev-label').addClass('topi');
					var RADIO_N = 1,
						radioValAdd = function(v) {
							v = $.extend({
								id:0,
								uid:RADIO_N,
								title:'имя значения ' + RADIO_N
							}, v);
							$('#element-main').append(
								'<tr><td class="label r">Значение ' + RADIO_N + ':' +
									'<td><input type="text" class="w250" id="radio-val-' + v.uid + '" val="' + v.uid + '" value="' + v.title + '" />' +
						 (RADIO_N > 1 ? '<div class="icon icon-del ml5 prel top5' + _tooltip('Удалить значение', -55) + '</div>' : '')
							);
							$('#radio-val-' + v.uid).keyup(function() {
								var t = $(this),
									uid = _num(t.attr('val'));
								for(var n = 0; n < RADIO_ASS.length; n++) {
									var sp = RADIO_ASS[n];
									if(sp.uid == uid) {
										sp.title = t.val();
										break;
									}
								}
								radioPrint();
							}).select();
							$('#element-main .icon-del:last').click(function() {
								var t = $(this),
									uid = _num(t.prev().attr('val')),
									p = _parent(t);
								for(var n = 0; n < RADIO_ASS.length; n++) {
									var sp = RADIO_ASS[n];
									if(sp.uid == uid) {
										RADIO_ASS.splice(n, 1);
										break;
									}
								}
								p.remove();
								radioPrint();
							});
							RADIO_ASS.push({
								id:v.id,
								uid:v.uid,
								title:v.title
							});
							RADIO_N++;
							radioPrint();
						},
						radioPrint = function() {
							$('#elem-attr-id')._radio({
								light:1,
								spisok:RADIO_ASS
							});
						};
					//кнопка добавления нового значения radio
					$('#element-main').after(
						'<div class="color-555 b center pad10 over1 curP">' +
							'Добавить значение' +
						'</div>'
					).next().click(radioValAdd);

					if(EL.id){
						for(var n = 0; n < EL.v.length; n++)
							radioValAdd(EL.v[n]);
					} else
						radioValAdd();
					break;
				case 6://календарь
					elementActionSel = function() {
						$('#elem-attr-id')._calendar({
							lost:_bool($('#param_bool_1').val()),
							tomorrow:_bool($('#param_bool_2').val())
						});
					};
					$('#param_bool_1')._check({
						func:elementActionSel
					});
					$('#param_bool_2')._check({
						func:elementActionSel
					});
					elementActionSel();
					break;
			}
		}
		function elementActionSel() {}//действие, которое применяется к выбранному элементу в предварительном просмотре
		function submit() {
			var elem = {
					id:EL.id,
					type_id:TYPE_ID,
					label_name:$.trim($('#label-name').val()),
					require:_bool($('#label-require').val()),
					hint:$.trim($('#label-hint').val()),
					width:250,
					param_txt_1:$.trim($('#param_txt_1').val()),
					param_bool_1:_bool($('#param_bool_1').val()),
					param_bool_2:_bool($('#param_bool_2').val()),
					v:RADIO_ASS
				},
				rand = EL.id || Math.round(Math.random() * 10000),//случайное число для создани ID элемента
				attr_id = 'elem' + rand,
				DD =
					'<dd class="over1 curM prel" val="' + (EL.id || 0) + '">' +
						'<div class="element-del icon icon-del' + _tooltip('Удалить элемент', -53) + '</div>' +
						'<div class="element-edit icon icon-edit' + _tooltip('Изменить', -29) + '</div>' +
						'<table class="bs5">' +
							'<tr><td class="label label-width r pr5" style="width:' + LABEL_WIDTH + 'px">' +
									elem.label_name +
									(elem.label_name ? ':' : '') +
									(elem.require ? '<div class="dib red fs15 mtm2">*</div>' : '') +
									(elem.hint ? ' <div class="icon icon-hint dialog-hint" val="' + _br(elem.hint, 1) + '"></div>' : '') +
								'<td>',
				inp = '<input type="hidden" id="' + attr_id + '" />';

			//формирование содержания
			switch(TYPE_ID) {
				case 1://check
					if(!elem.label_name && !elem.param_txt_1) {
						dialog.err('Укажите название поля, либо текст для галочки');
						$('#label-name').focus();
						return;
					}
					break;
				case 2://select
					break;
				case 3://text
					inp = '<input type="text" id="' + attr_id + '" class="w250" placeholder="' + elem.param_txt_1 +'" />';
					break;
				case 4://textarea
					inp = '<textarea id="' + attr_id + '" class="w250" placeholder="' + elem.param_txt_1 + '"></textarea>';
					break;
				case 5://radio
					break;
				case 6://календарь
					break;
			}

			//вставка содержания
			if(EL.id) {
				var dd = $('#dialog-base DD');
				for(var n = 0; n < dd.length; n++) {
					var sp = dd.eq(n),
						id = _num(sp.attr('val'));
					if(EL.id == id) {
						sp.after(DD + inp + '</dd></table>')
						  .remove();
						break;
					}
				}
				for(n = 0; n < DIALOG_ELEMENT.length; n++) {
					var sp = DIALOG_ELEMENT[n];
					if(EL.id == sp.id) {
						DIALOG_ELEMENT[n] = elem;
						break;
					}
				}
			} else {
				$('#dialog-base').append(DD + inp + '</dd></table>');
				DIALOG_ELEMENT.push(elem);
			}

			//применение скриптов
			switch(TYPE_ID) {
				case 1://check
					$('#' + attr_id)._check({
						title:elem.param_txt_1,
						light:1
					});
					break;
				case 2://select
					$('#' + attr_id)._select({
						width:200,
						title0:elem.param_txt_1,
						spisok:[]
					});
					break;
				case 3://text
					break;
				case 4://textarea
					$('#' + attr_id)
						.autosize()
						.parent().parent()
						.find('.label').addClass('topi');
					break;
				case 5://radio
					$('#' + attr_id)
						._radio({
							light:1,
							spisok:RADIO_ASS
						})
						.parent().parent()
						.find('.label').addClass('topi');
					break;
				case 6://календарь
					$('#' + attr_id)._calendar({
						lost:elem.param_bool_1,
						tomorrow:elem.param_bool_2
					});
					break;
			}

			dialog.close();
			_dialogHeightCorrect();
		}
	},
	_dialogOpen = function(dialog_id) {//создание диалогового окна
		dialog_id = _num(dialog_id);
		var dialog = _dialog({
				dialog_id:dialog_id,
				width:500,
				top:20,
				head:'Диалог',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'dialog_open_load',
				dialog_id:dialog_id
			};

		dialog.load(send, loaded);

		function loaded(res) {
			dialog.width(res.width);
			dialog.head(res.head);
			dialog.content.html(res.html);
			dialog.butSubmit(res.button_submit);
			dialog.butCancel(res.button_cancel);
			_dialogScript(res.element);
			dialog.submit(function() {
				submit(res.element);
			});
		}
		function submit(elem) {
			send = {
				op:'spisok_add',
				dialog_id:dialog_id,
				elem:{}
			};

			for(var n = 0; n < elem.length; n++) {
				var sp = elem[n];
				send.elem[sp.id] = $(sp.attr_id).val();
			}

			dialog.post(send, 'reload');
		}
	},
	_dialogScript = function(element, isEdit) {//применение скриптов после загрузки данных диалога
		for(var n = 0; n < element.length; n++) {
			var ch = element[n];
			if(ch.type_id == 1) {//check
				$(ch.attr_id)._check({
					title:ch.param_txt_1,
					light:1
				});
				continue;
			}
			if(ch.type_id == 2) {//select
				$(ch.attr_id)._select({
					title0:ch.param_txt_1,
					spisok:[]
				});
				continue;
			}
			if(ch.type_id == 3) {//input
				if(isEdit)
					$(ch.attr_id)
						.attr('readonly', true)
						.resizable({
							minWidth:50,
							maxWidth:350,
							grid:10,
							handles:'e',
							stop:function(event, ui) {
								var id = _num(ui.originalElement[0].id.split('elem')[1]);
								for(var n = 0; n < DIALOG_ELEMENT.length; n++) {
									var sp = DIALOG_ELEMENT[n];
									if(sp.id == id) {
										sp.width = ui.size.width - 18;
										break;
									}
								}
							}
						});
				continue;
			}
			if(ch.type_id == 4) {//textarea
				$(ch.attr_id)
					.parent().parent()
					.find('.label').addClass('topi');
				if(isEdit)
					$(ch.attr_id)
						.attr('readonly', true)
						.resizable({
							minWidth:50,
							maxWidth:350,
							grid:10,
							handles:'e',
							stop:function(event, ui) {
								var id = _num(ui.originalElement[0].id.split('elem')[1]);
								for(var n = 0; n < DIALOG_ELEMENT.length; n++) {
									var sp = DIALOG_ELEMENT[n];
									if(sp.id == id) {
										sp.width = ui.size.width - 18;
										break;
									}
								}
							}
						});
				else
					$(ch.attr_id).autosize();
				continue;
			}
			if(ch.type_id == 5) {//radio
				$(ch.attr_id)
					._radio({
						light:1,
						spisok:ch.v
					})
					.parent().parent()
					.find('.label').addClass('topi');
				continue;
			}
			if(ch.type_id == 6) {//календарь
				$(ch.attr_id)._calendar({
					lost:ch.param_bool_1,
					tomorrow:ch.param_bool_2
				});
				continue;
			}
		}
	};

$(document)
	.on('mouseenter', '.dialog-hint', function() {//отображение подсказки при наведении на вопрос в диалоге
		var t = $(this),
			msg = t.attr('val');

		if(!msg)
			return;

		t._hint({
			msg:msg,
			show:1,
			delayHide:100,
			remove:1
		});
});


$.fn._check = function(o) {
	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_check';

	if(!attr_id)
		return;

	t.next().remove('._check');

	o = $.extend({
		title:'',
		disabled:0,
		light:0,
		block:0,
		func:function() {}
	}, o);

	var val = t.val() == 1 ? 1 : 0,
		on = val ? ' on' : '',
		title = o.title ? ' title' : '',
		light = o.light ? ' light' : '',
		block = o.block ? ' block' : '',
		dis = o.disabled ? ' disabled' : '',
		html =
			'<div class="_check' + on + title + light + block + dis + '" id="' + win + '">' +
				(o.title ? o.title : '&nbsp;') +
			'</div>';

	t.val(val).after(html);

	if(!o.disabled)
		$('#' + win).click(function() {
			var tt = $(this),
				v = tt.hasClass('on') ? 0 : 1;
			tt[(v ? 'add' : 'remove') + 'Class']('on');
			t.val(v);
			o.func(v);
		});

/*
	_click(o.func);

	function _click(func) {
		if($('#' + win).hasClass('disabled'))
			return;
		$(document).on('click', '#' + win, function() {
			func(parseInt(t.val()), attr_id);
		});
	}

*/

	return t;
};
$.fn._radio = function(o, o1) {
	var t = $(this),
		n,
		attr_id = t.attr('id'),
		win = attr_id + '_radio';

	if(!attr_id)
		return;

	t.next().remove('._radio');

	o = $.extend({
		title0:'',
		spisok:[],
		light:0,
		block:1,
		top:7,      //отступ сверху всего блока
		interval:7, //интервал между значениями
		func:function() {}
	}, o);

	var val = _num(t.val(), 1);

	t.after(
		'<div class="_radio' + (o.block ? ' block' : '') + '" id="' + win + '" style="margin-top:' + o.top + 'px">' +
			_print(_copySel(o.spisok)) +
		'</div>'
	);

	$('#' + win + ' div').click(function() {
		var div = $(this),
			p = _parent(div, '._radio'),
			v = div.attr('val');
		p.find('div.on').removeClass('on');
		div.addClass('on');
		t.val(v);
	});

	_click(o.func);

	function _print(spisok) {
		var list = '';
		if(o.title0)
			spisok.unshift({uid:0,title:o.title0});
		for(n = 0; n < spisok.length; n++) {
			var sp = spisok[n],
				on = val == sp.uid ? 'on' : '',
				l = o.light ? ' l' : '';
			list += '<div class="' + on + l + '" val="' + sp.uid + '" style="margin-bottom:' + o.interval + 'px">' +
						sp.title +
					'</div>';
		}
		return list;
	}
	function _click(func) {
		$(document).off('click', '#' + win + ' div');
		$(document).on('click', '#' + win + ' div', function() {
			func(_num(t.val()), attr_id);
		});
	}

	window[win] = t;
	return t;
};
$.fn._select = function(o) {
	var t = $(this),
		n,
		s,
		id = t.attr('id'),
		val = t.val() || 0;

	if(!id)
		return;

	switch(typeof o) {
		default:
		case 'number':
		case 'string':
			s = window[id + '_select'];
			switch(o) {
				case 'process': s.process(); break;
				case 'cancel': s.cancel(); break;
				case 'clear': s.clear(); break;//очищение inp, установка val=0
				case 'title': return s.title();
				case 'inp': return s.inp();
				case 'focus': s.focus(); break;
				case 'first': s.first(); break;//установка первого элемента в списке
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
		case 'object':
			//если это первый вход, то пропуск
			if(o.width || o.func)
				break;

			//вставка списка после загрузки
			if('length' in o) {
				s = window[id + '_select'];
				s.spisok(o);
				return t;
			}
			if(!('spisok' in o))
				return t;
	}

	o = $.extend({
		width:180,			// ширина
		disabled:0,
		block:false,       	// расположение селекта
		bottom:0,           // отступ снизу
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

	o.clear = o.write && !o.multiselect;

	if(o.multiselect || o.write_save)
		o.write = true;

	var inpWidth = o.width - 17 - 5 - 4;
	if(o.funcAdd)
		inpWidth -= 18;
	if(o.clear) {
		inpWidth -= 18;
		val = _num(val);
	}
	var html =
		'<div class="_select' + (o.disabled ? ' disabled' : '') + '" ' +
			 'id="' + id + '_select" ' +
			 'style="width:' + o.width + 'px' +
				(o.block ? ';display:block' : '') +
				(o.bottom ? ';margin-bottom:' + o.bottom + 'px' : '') +
		'">' +
			'<div class="title0bg" style="width:' + inpWidth + 'px">' + o.title0 + '</div>' +
			'<table class="seltab">' +
				'<tr><td class="selsel">' +
						'<input type="text" ' +
							   'class="selinp" ' +
							   'style="width:' + inpWidth + 'px' +
									(o.write && !o.disabled? '' : ';cursor:default') + '"' +
									(o.write && !o.disabled? '' : ' readonly') + ' />' +
					(o.clear ? '<div' + (val ? '' : ' style="display:none"') + ' class="clear' + _tooltip('Очистить', -51, 'r') + '</div>' : '') +
	   (o.funcAdd ? '<td class="seladd">' : '') +
					'<td class="selug">' +
			'</table>' +
			'<div class="selres" style="width:' + o.width + 'px"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	var select = t.next(),
		inp = select.find('.selinp'),
		inpClear = select.find('.clear'),
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
				inpClear[inp.val() ? 'show' : 'hide']();
				if(keyVal != inp.val()) {
					keyVal = inp.val();
					o.funcKeyup(keyVal);
					t.val(0);
					val = 0;
				}
			});

		inpClear.click(function(e) {
			e.stopPropagation();
			setVal(0);
			inp.val('');
			title0bg.show();
			o.func(0, id);
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
		inpClear[v ? 'show' : 'hide']();
		if(v || !v && !o.write_save) {
			inp.val(ass[v] ? ass[v].replace(/&quot;/g,'"') : '');
			title0bg[v == 0 ? 'show' : 'hide']();
		}
	}
	function unitSel(t) {
		var v = parseInt(t.attr('val')),
			item;
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
					o.funcKeyup('');
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
	t.title = function() {//Получение содержимого установленного значения
		return ass[t.val()];
	};
	t.inp = function() {//Получение содержимого введённого значения
		return inp.val();
	};
	t.focus = function() {//установка фокуса на input
		inp.focus();
	};
	t.first = function() {//установка первого элемента в списке
		if(!o.spisok.length)
			setVal(0);
		setVal(o.spisok[0].uid);
	};

	window[id + '_select'] = t;
	return t;
};
$.fn._hint = function(o) {
	var t = $(this);

	//счётчик подсказок. Для удаления именно той подсказки, которая была добавлена
	if(!window.HINT_COUNT)
		HINT_COUNT = 1;

	o = $.extend({
		msg:'Сообщение подсказки',
		red:0,      //окрашивание текста в красный цвет
		width:0,
		event:'mouseenter', // событие, при котором происходит всплытие подсказки
		ugol:'bottom',
		indent:'center',    //отступ уголка: top, bottom (для вертикали), left, right (для горизонтали). Либо число в пикселях слева либо сверху.
		top:0,
		left:0,
		show:0,	     // выводить ли подсказку после загрузки страницы
		delayShow:0, // задержка перед всплытием
		delayHide:0, // задержка перед скрытием
		correct:0,   // настройка top и left
		correctCoordHide:0,// скрывать координаты при настройке top и left
		correctFunc:function() {},// функция, выполняемая при настройки top и left
		remove:0	 // удалить подсказку после показа
	}, o);

	var	HC = HINT_COUNT++,
		top = o.top, // установка конечного положения подсказки после движения
		left = o.left,
		html =
			hintCorrect() +
			'<table class="_hint-tab3 bg-fff curD"' + (o.width ? ' style="width:' + o.width + 'px"' : '') + '>' +
				hintUgolTop() +
				'<tr>' +
					hintUgolLeft() +
					'<td class="pad10' + (o.red ? ' red' : '') + '">' + o.msg +
					hintUgolRight() +
				hintUgolBottom() +
			'</table>';

	html =
		'<table>' +
			'<tr><td class="side012">' +
				'<td>' + html +
				'<td class="side012">' +
			'<tr><td class="b012" colspan="3">' +
		'</table>';

	html =
		'<table class="_hint-tab1 prel">' +
			'<tr><td class="side005">' +
				'<td>' + html +
				'<td class="side005">' +
			'<tr><td class="b005" colspan="3">' +
		'</table>';

	html = '<div class="_hint hint' + HC + '">' + html + '</div>';

//	t.prev().remove('._hint'); // удаление предыдущей такой же подсказки
//	t.before(html); // вставка перед элементом

	var hi = $('body').append(html).find('.hint' + HC), //поле absolute для подсказки
//		hi = t.prev(), //поле absolute для подсказки
		hintTable = hi.find('._hint-tab1'), // сама подсказка
		hintW = hintTable.width(),
		hintH = hintTable.height(),
		tW = t.width() + _num(t.css('padding-left').split('px')[0]) + _num(t.css('padding-right').split('px')[0]),//ширина объекта
		tH = t.height() + _num(t.css('padding-top').split('px')[0]) + _num(t.css('padding-bottom').split('px')[0]),//высота объекта
		diff = Math.round((hintW - 26) / (hintH - 24));

	//корректировка ширины, если слишком длинный текст в одну строку
	if(diff > 15) {
		var x = hintW - 26,
			y = hintH - 24;//коэфициент отношения стороны экрана 16*9
		hintW = Math.round(Math.sqrt(x * y) * 1.3) + 26;
		hintTable.width(hintW);
		hintH = hintTable.height();
	}

	hintUgolPos();

	hi.addClass('dn');

	// отключение событий от предыдущей такой же подсказки
//	t.off(o.event + '.hint');
//	t.off('mouseleave.hint');

	// установка событий
	t.on(o.event + '.hint' + HC, hintShow);
	t.on('mouseleave.hint' + HC, hintHide);
	hintTable.on('mouseenter.hint' + HC, hintShow);
	hintTable.on('mouseleave.hint' + HC, hintHide);



	// процессы всплытия подсказки:
	// - wait_to_showind - ожидает показа (мышь была наведена)
	// - showing - выплывает
	// - show - показана
	// - wait_to_hidding - ожидает скрытия (мышь была отведена)
	// - hidding - скрывается
	// - hidden - скрыта
	var process = 'hidden',
		timer = 0;

	// автоматический показ подсказки, если нужно
	if(o.show)
		hintShow();

	function hintUgolTop() {//рисование уголка сверху
		if(o.ugol != 'top')
			return '';

		top = o.top - 15;

		return '<tr><td class="prel"><div class="ug ugt"></div>';
	}
	function hintUgolBottom() {//рисование уголка снизу
		if(o.ugol != 'bottom')
			return '';

		top = o.top + 15;

		return '<tr><td class="prel"><div class="ug ugb"></div>';
	}
	function hintUgolLeft() {//рисование уголка слева
		if(o.ugol != 'left')
			return '';
		
		left = o.left - 25;

		return '<td class="prel"><div class="ug ugl"></div>';
	}
	function hintUgolRight() {//рисование уголка справа
		if(o.ugol != 'right')
			return '';

		left = o.left + 25;

		return '<td class="prel"><div class="ug ugr"></div>';
	}
	function hintUgolPos() {//позиционирование уголка после вывода вставки подсказки
		var pos = 10;
		switch(o.ugol) {
			case 'top':
			case 'bottom':
				switch(o.indent) {
					case 'center': pos = Math.round(hintW / 2) - 8;	break;
					case 'left': break;
					case 'right': pos = hintW - 27; break;
					default:
						pos = _num(o.indent);
						if(pos > hintW - 28)
							pos = hintW - 28;
				}
				if(pos < 10)
					pos = 10;
				hintTable.find('.ug').css('left', pos + 'px');
				break;
			case 'left':
			case 'right':
				switch(o.indent) {
					case 'center': pos = Math.round(hintH / 2) - 8;	break;
					case 'top': break;
					case 'bottom': pos = hintH - 27; break;
					default:
						pos = _num(o.indent);
						if(pos > hintH - 25)
							pos = hintH - 25;
				}
				if(pos < 10)
					pos = 10;
				hintTable.find('.ug').css('top', pos + 'px');
		}
	}
	function hintAutoPos() {//автоматическое позиционирование подсказки
		var offset = t.offset(),
			x, y;

		switch(o.ugol) {
			case 'top':
				x = Math.round(offset.left - hintW / 2 + tW / 2) - 2;
				y = offset.top + tH + 24;
				break;
			case 'bottom':
				x = Math.round(offset.left - hintW / 2 + tW / 2) - 2;
				y = offset.top - hintH - 21;
				break;
			case 'left':
				x = offset.left + tW + 32;
				y = Math.round(offset.top - hintH / 2 + tH / 2 );
				break;
			case 'right':
				x = offset.left - hintW - 31;
				y = Math.round(offset.top - hintH / 2 + tH / 2 );
				break;
		}

		hi.css({
			top:y + 'px',
			left:x + 'px'
		});
	}
	function hintShow() {//всплытие подсказки
		hi.removeClass('dn');
		hintAutoPos();

		if(o.correct)
			$(document).off('keydown.hint');

		switch(process) {
			case 'wait_to_hidding':
				clearTimeout(timer);
				process = 'show';
				break;
			case 'hidding':
				process = 'showing';
				hintTable
					.stop()
					.animate({top:top, left:left, opacity:1}, 200, showed);
				break;
			case 'hidden':
				if(o.delayShow) {
					process = 'wait_to_showing';
					timer = setTimeout(action, o.delayShow);
				} else
					action();
				break;
		}
		// действие всплытия подсказки
		function action() {
			process = 'showing';
			hintTable
				.css({top:o.top, left:o.left})
				.animate({top:top, left:left, opacity:1}, 200, showed);
		}
		// действие по завершению всплытия
		function showed() {
			process = 'show';
			if(o.correct) {
				$(document).on('keydown.hint', function(e) {
					e.preventDefault();
					switch(e.keyCode) {
						case 38: o.top--; top--; break; // вверх
						case 40: o.top++; top++; break; // вниз
						case 37: o.left--; left--; break; // влево
						case 39: o.left++; left++; break; // вправо
					}
					hintTable.css({top:top, left:left});
					hintTable.find('.crt-top').html(o.top);
					hintTable.find('.crt-left').html(o.left);
					o.correctFunc(o.top, o.left);
				});
			}
		}
	}
	function hintHide() {//скрытие подсказки
		if(o.correct)
			$(document).off('keydown.hint');
		if(process == 'wait_to_showing') {
			clearTimeout(timer);
			process = 'hidden';
		}
		if(process == 'showing') {
			hintTable.stop();
			action();
		}
		if(process == 'show') {
			if(o.delayHide) {
				process = 'wait_to_hidding';
				timer = setTimeout(action, o.delayHide);
			} else
				action();
		}
		function action() {
			process = 'hidding';
			hintTable.animate({opacity:0}, 200, function () {
				process = 'hidden';
				hi.addClass('dn');
				if(o.remove) {
					hi.remove();
					t.off(o.event + '.hint' + HC);
					t.off('mouseleave.hint' + HC);
				}
			});
		}
	}
	function hintCorrect() {//вставка информации о корректировке положения подсказки
		if(!o.correct)
			return '';

		return '<div class="_hint-crt' + (o.correctCoordHide ? ' dn' : '') + '">' +
					 'top: <span class="crt-top mr10">' + o.top + '</span> ' +
					'left: <span class="crt-left mr10">' + o.left + '</span>' +
				'</div>';
	}
};
$.fn._tooltip = function(msg, left, ugolSide) {
	var t = $(this);

	t.addClass('_tooltip');
	t.append(
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>'
	);
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
