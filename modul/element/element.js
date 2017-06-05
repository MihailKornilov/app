/* Все элементы визуального отображения, используемые в приложении */
var VK_SCROLL = 0,
	ZINDEX = 0,
	BC = 0,
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
			bottom._hint({
				msg:'<span class="red">' + msg + '</span>',
				top:-48,
				left:w2 - 90,
				indent:40,
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
				butSubmit:(dialog_id ? 'Сохранить' : 'Создать') + ' диалоговое окно',
				submit:submit
			}),
			send = {
				op:'dialog_edit_load',
				dialog_id:dialog_id
			};

		window.DIALOG_ELEMENT = [];

		dialog.load(send, loaded);

		function loaded(res) {
			dialog.width(res.width);
			dialog.editHead(res.head);
			dialog.content.html(res.html);
			DIALOG_ELEMENT = res.element;
			_dialogScript(res.element);
			sortable();
			widthOk();
			widthCancel();
			elementEdit();
			elementDel();
		}
		function widthOk() {//применение ширины окна
			$('#dialog-width-ok').click(function() {
				var	t = $(this),
					send = {
						op:'dialog_width_set',
						dialog_id:dialog_id,
						width:$('#dialog-width-inp').val()
					};

				if(t.hasClass('_busy'))
					return;

				t.addClass('_busy');
				$.post(AJAX, send, function(res) {
					t.removeClass('_busy');
					if(res.success) {
						$('#dialog-but').removeClass('dn');
						$('#dialog-width').addClass('dn');
						dialog.width(send.width);
					}
				}, 'json');
			});
		}
		function widthCancel() {//отмена ширины окна
			$('#dialog-width-cancel').click(function() {
				$('#dialog-but').removeClass('dn');
				$('#dialog-width').addClass('dn');
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
			});
		}
		function submit() {
			send = {
				op:'dialog_' + (dialog_id ? 'edit' : 'add'),
				dialog_id:dialog_id,
				head:dialog.editHead(),
				button_submit:$('#button_submit').val(),
				button_cancel:$('#button_cancel').val(),
				element:DIALOG_ELEMENT
			};
			dialog.post(send, function(res) {
				_dialogOpen(res.dialog_id);
			});
		}
	},
	_dialogEditElement = function(EL) {//добавление нового элемента
		EL = $.extend({
			id:0,
			type_id:0,
			label_name:'',
			require:0,
			hint:'',
			hint_top:0,
			hint_left:0,
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
							'<tr><td id="prev-label" class="label r w125">' +
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

			if(!EL.hint_top) {
				EL.hint_top = -77 - brAdd;
				EL.hint_left = 55;
			}

			$(this)._hint({
				msg:msg,
//				top:EL.hint_top,
//				left:EL.hint_left,
//				ugol:'right',
//				indent:'auto',
				show:1,
//				correct:1,
//				correctCoordHide:1,
//				correctFunc:function(top, left) {
//					EL.hint_top = top;
//					EL.hint_left = left;
//				},
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
						$('#elem-attr-id')._calendarNew({
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
					hint_top:EL.hint_top,
					hint_left:EL.hint_left,
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
							'<tr><td class="label r w125 pr5">' +
									elem.label_name +
									(elem.label_name ? ':' : '') +
									(elem.require ? '<div class="dib red fs15 mtm2">*</div>' : '') +
									(elem.hint ? ' <div class="icon icon-hint dialog-hint" val="' + _br(elem.hint, 1) + '###' + elem.hint_top + '###' + elem.hint_left + '"></div>' : '') +
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
					$('#' + attr_id)._calendarNew({
						lost:elem.param_bool_1,
						tomorrow:elem.param_bool_2
					});
					break;
			}


			dialog.close();
		}
	},
	_dialogEditWidth = function() {//изменение ширины окна
		$('#dialog-but').addClass('dn');
		$('#dialog-width').removeClass('dn');
		$('#dialog-width-inp').select();
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
	_dialogScript = function(element) {//применение скриптов после загрузки данных диалога
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
			if(ch.type_id == 4) {//textarea
				$(ch.attr_id)
					.autosize()
					.parent().parent()
					.find('.label').addClass('topi');
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
				$(ch.attr_id)._calendarNew({
					lost:ch.param_bool_1,
					tomorrow:ch.param_bool_2
				});
				continue;
			}
		}
	};

$(document)
	.on('mouseenter', '.dialog-hint', function() {
		var t = $(this),
			msg = t.attr('val');

		if(!msg)
			return;

		var v = msg.split('###');

		t._hint({
			msg:v[0],
//			top:v[1] * 1,
//			left:v[2] * 1,
//			indent:50,
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
				o.title +
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
$.fn._hint = function(o) {
	var t = $(this);

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

	var	top = o.top, // установка конечного положения подсказки после движения
		left = o.left,
		html =
			hintCorrect() +
			'<table class="_hint-tab3 bg-fff curD"' + (o.width ? ' style="width:' + o.width + 'px"' : '') + '>' +
				hintUgolTop() +
				'<tr>' +
					hintUgolLeft() +
					'<td class="cont pad10' + (o.red ? ' red' : '') + '">' + o.msg +
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

	html = '<div class="_hint">' + html + '</div>';

//	t.prev().remove('._hint'); // удаление предыдущей такой же подсказки
//	t.before(html); // вставка перед элементом

	$('body')
		.remove('._hint')
		.append(html);

	var hi = $('body').find('._hint:first'), //поле absolute для подсказки
//		hi = t.prev(), //поле absolute для подсказки
		hintTable = hi.find('._hint-tab1'), // сама подсказка
		hintW = hintTable.width(),
		hintH = hintTable.height(),
		diff = Math.round((hintW - 26) / (hintH - 24));

	//корректировка ширины, если слишком длинный текст в одну строку
	if(diff > 15) {
		var x = hintW - 26,
			y = hintH - 24;
		hintW = Math.round(Math.sqrt(x * y)) + 26 + 70;
		hintTable.width(hintW);
		hintH = hintTable.height();
	}

	hintUgolPos();
	hintAutoPos();

	// отключение событий от предыдущей такой же подсказки
	t.off(o.event + '.hint');
	t.off('mouseleave.hint');

	// установка событий
	t.on(o.event + '.hint', hintShow);
	t.on('mouseleave.hint', hintHide);
	hintTable.on('mouseenter.hint', hintShow);
	hintTable.on('mouseleave.hint', hintHide);



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
			x = offset.left - Math.round(hintW / 2) + 8,
			y = offset.top - hintH - 21;

		hi.css({
			top:y + 'px',
			left:x + 'px'
		});
	}
	function hintShow() {//всплытие подсказки
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
				if(o.remove) {
					hi.remove();
					t.off(o.event + '.hint');
					t.off('mouseleave.hint');
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