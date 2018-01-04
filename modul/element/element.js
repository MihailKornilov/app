/* Все элементы визуального отображения, используемые в приложении */
var VK_SCROLL = 0,
	ZINDEX = 1000,
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
			width:380,
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
			bottom = dialog.find('.btm'),
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
			width:o.width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		iconEdit.click(function() {//нажатие на иконку редактирования
			if(!o.dialog_id)
				return;

			var icon = iconEdit.find('.icon'),
				send = {
					op:'dialog_edit_load',
					dialog_id:o.dialog_id
				};

			if(icon.hasClass('spin'))
				return;

			iconEdit.removeClass('curP');
			icon.addClass('spin');

			_post(send, function(res) {
				dialogClose();
				res.unit_id = o.unit_id;
				res.block_id = o.block_id;
				_dialogEdit(res);
			}, function() {
				iconEdit.addClass('curP');
				icon.removeClass('spin');
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

		return {
			close:dialogClose,
			process:function() {
				butSubmit.addClass('_busy');
			},
			processCancel:function() {
				butCancel.addClass('_busy');
			},
			abort:function(msg) {
				butSubmit.removeClass('_busy');
				if(msg)
					dialogErr(msg);
			},
			abortCancel:function() {
				butCancel.removeClass('_busy');
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
						if(res.delem_id)
							$('#delem' + res.delem_id)._flash({color:'red'});
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
						dialog_id:o.dialog_id,
						unit_id:o.unit_id,
						block_id:o.block_id
					};
					dialog.processCancel();
					_post(send, function(res) {
						dialog.close();
						_dialogOpen(res);
					}, function() {
						dialog.abortCancel();
					});
				}
			}),
			DIALOG_WIDTH = o.width;

		_elemActivate(o.cmp, {}, 1);

		window.CMP_NAME = o.cmp_name;
		window.DIALOG_ELEMENT = o.element;
		window.DIALOG_COMPONENT = o.component;
		window.COMPONENT_FUNC = o.func;
		window.SPISOK_ON = o.spisokOn;

		_forIn(o.block_arr, function(sp, k) {
			BLOCK_ARR[k] = sp;
		});

		_dialogScript(o.component, 1);

		$('#spisok_on')._check({
			func:function(v) {
				$('#tr_spisok_name')._dn(v);
			}
		});

		$('#app_any')._check();
		$('#sa')._check();

		$('#dialog-menu')._menu({
			type:4,
			spisok:o.menu,
			func:_dialogHeightCorrect
		});
		$('#action_id')._select({
			width:260,
			title0:'действия нет, закрыть окно',
			spisok:o.action,
			func:function(v) {
				$('.td-action-page')._dn(v == 2);
				$('#action_page_id')._select(0);
			}
		});
		$('#action_page_id')._select({
			width:250,
			title0:'не выбрана',
			spisok:o.page_list
		});

		_dialogHeightCorrect();

		//установка линии для настройки ширины диалога
		$('#dialog-w-change')
			.css('left', (DIALOG_WIDTH + 8) + 'px')
			.draggable({
				axis:'x',
				grid:[10,0],
				drag:function(event, ui) {
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
				op:'dialog_edit',// + (o.dialog_id ? 'edit' : 'add'),

				dialog_id:o.dialog_id,
				unit_id:o.unit_id,
				block_id:o.block_id,

				width:DIALOG_WIDTH,

				head_insert:$('#head_insert').val(),
				button_insert_submit:$('#button_insert_submit').val(),
				button_insert_cancel:$('#button_insert_cancel').val(),
				head_edit:$('#head_edit').val(),
				button_edit_submit:$('#button_edit_submit').val(),
				button_edit_cancel:$('#button_edit_cancel').val(),

//				component:DIALOG_COMPONENT,
//				func:COMPONENT_FUNC,
				spisok_on:$('#spisok_on').val(),
				spisok_name:$('#spisok_name').val(),
				action_id:$('#action_id').val(),
				action_page_id:$('#action_page_id').val(),

				base_table:$('#base_table').val(),
				app_any:$('#app_any').val(),
				sa:$('#sa').val(),

				menu_edit_last:$('#dialog-menu').val()
			};
			dialog.post(send, _dialogOpen);
		}
	},




	_dialogHeightCorrect = function() {//установка высоты линий для настройки ширины диалога и ширины полей с названиями
		var h = $('#dialog-w-change').parent().height();
		$('#dialog-w-change').height(h);
	},
	_dialogCmpEdit = function(CMP) {//добавление|редактирование компонента диалога
		CMP = $.extend({
			id:0,
			type_id:0,
			col_name:'',    //имя колонки (для SA)
			label_name:'',  //название поля
			req:0,      //флаг "требовать обязательное заполнение"
			hint:'',        //текст подсказки
			width:0,        //длина элемента (для input, textarea, select)
			num_1:0,
			num_2:0,
			num_3:0,
			num_4:0,
			num_5:0,
			txt_1:'',
			txt_2:'',
			v:[]
		}, CMP);

		var TYPE_ID = CMP.type_id,//выбранный элемент
			dialog = _dialog({
				width:550,
				top:30,
				padding:0,
				color:'orange',
				head:'Настройка компонента диалога',
				content:elContentHtml(),
				butSubmit:'',
				submit:submit
			}),
			EL_VAL_ASS = [];

		elSelScript();
		elEditScript();

		//вывод текста подсказки при наведении
		$(document).off('mouseenter', '.dialog-hint-edit');
		$(document).on('mouseenter', '.dialog-hint-edit', function() {
			$('#label-hint').focus().blur();
			var msg = _br($('#label-hint').val(), 1);

			$(this)._hint({
				msg:msg,
				pad:10,
				show:1,
				delayShow:500,
				delayHide:300
			});
		});

		function elContentHtml() {//вывод списка элементов для выбора компонента диалога
			if(TYPE_ID)
				return '';
			var html = '';
			for(var k in DIALOG_ELEMENT) {
				var sp = DIALOG_ELEMENT[k];
				html += '<div val="' + k + '" class="over1 line-b element ' + sp.css + '">' + sp.name + '</div>';
			}
			return html;
		}
		function elSelScript() {//выбор НОВОГО элемента из окна списка
			if(TYPE_ID)
				return;
			dialog.content.find('.element').click(function() {
				TYPE_ID = _num($(this).attr('val'));
				dialog.content.slideUp(200, function() {
					$(this).html(DIALOG_ELEMENT[TYPE_ID].html)
						   .slideDown(200, elScript);
					dialog.butSubmit('Добавить компонент');
				});
			});
		}
		function elEditScript() {//отображение окна редактирования элемента
			if(!TYPE_ID)
				return;

			dialog.content.html(DIALOG_ELEMENT[TYPE_ID].html);
			$('#col_name').val(CMP.col_name);
			$('#label_name').val(CMP.label_name);
			$('#label-req').val(CMP.req);
			$('#label-hint').val(CMP.hint);
			$('#txt_1').val(CMP.txt_1);

			elScript();
			dialog.butSubmit('Сохранить изменения');
		}
		function elScript() {//применение скриптов для конкретного элемента
			var labelPrevUpdate = function() {//обновление предварительного просмотра label
					var txt = $.trim($('#label_name').val()),
						req = _bool($('#label-req').val()),
						hint = $.trim($('#label-hint').val());
					txt =
						(txt ? txt + ':' : '') +
						(req ? '<div class="dib red fs15 mtm2">*</div>' : '') +
						(hint ? ' <div class="icon icon-info pl dialog-hint-edit"></div>' : '');
					$('#label-prev').html(txt);
				},
				elPrevAction = function() {};//действие, которое применяется к выбранному элементу в предварительном просмотре

			$('#label_name').keyup(labelPrevUpdate).focus();

			$('#label-req')._check({
				title:'требовать обязательное заполнение',
				light:1,
				func:labelPrevUpdate
			});

			$('#label-hint').keyup(labelPrevUpdate).autosize();

			switch(TYPE_ID) {
				case 1: /* check */ {
					elPrevAction = function() {
						$('#elem-attr-id')._check({
							title:$.trim($('#txt_1').val()),
							light:1
						});
					};
					$('#txt_1').keyup(elPrevAction);
					break;
				}
				case 2: /* select */ {
					CMP.width = CMP.width || 228;
					elPrevAction = function() {
						$('#elem-attr-id')
							._select({
								width:228,
								title0:_num($('#num_3').val()) ? $.trim($('#txt_1').val()) : '',
								spisok:EL_VAL_ASS
							})
							._select(0);

						_forN(EL_VAL_ASS, function(sp) {
							if(sp.def) {
								$('#elem-attr-id')._select(sp.uid);
								return;
							}
						});
					};
					$('#num_3')._check({
						tooltip:'Использовать нулевое значение',
						func:function(v) {
							$('#txt_1').attr('disabled', !v);
							elPrevAction();
						}
					});
					if(CMP.id)
						$('#num_3')
							._check(CMP.num_3)
							._check('func');
					$('#txt_1').keyup(elPrevAction);

					var em = $('#elem-select-but'),
						but1 = em.find('button:first'),//произвольные значения
						but2 = em.find('button:eq(1)'),//все списки
						but3 = em.find('button:eq(2)'),//выбор элемента объекта
						but4 = em.find('button:eq(3)');//списки объектов страницы
					$.fn.selCancel = function() {//отмена выбора
						em.next().find('.icon-del').click(function() {
							em.next().remove();
							em.removeClass('dn');
							$('#num_4').val(0);
						});
					};
					but1.click(function() {
						$('#num_3')._check('enable');
						$('#num_4').val(1);
						em.addClass('dn');
						elVal(elPrevAction, em);
					});
					but2.click(function() {
						CMP.v = [];
						$('#num_3')
							._check(1)
							._check('func')
							._check('disable');
						$('#num_4').val(2);
						em.addClass('dn');
						em.after(
							'<table class="bs5 w100p">' +
								'<tr><td colspan="3" class="center">' +
									'<div class="_info w400 dib">Будут выбираться объекты, которые являются списками.</div>' +
								'<tr><td class="label r w150">Содержание:' +
									'<td class="i b w150">все объекты списков' +
									'<td><div class="icon icon-del mbm5' + _tooltip('Отменить выбор', -52) + '</div>' +
								'<tr><td>' +
									'<td colspan="2"><input type="hidden" id="num_5" value="' + CMP.num_5 + '" />' +
							'</table>'
						);
						em.selCancel();
						$('#num_5')._check({
							light:1,
							title:'отображать списки только с текущей страницы'
						});
					});
					but3.click(function() {
						CMP.v = [];
						$('#num_3')
							._check(1)
							._check('func')
							._check('disable');
						$('#num_4').val(3);
						em.addClass('dn');
						em.after(elObjSelect(CMP, 'Выбор элемента конкретного объекта-списка.' +
												  '<br />' +
												  'Для настройки выберите объект, затем колонку, ' +
												  'по которой будет производиться отображение содержания.',
											1)
								);
						em.selCancel();
						elObjSelect(CMP);
					});
					but4.click(function() {
						CMP.v = [];
						$('#num_3')
							._check(1)
							._check('func')
							._check('disable');
						$('#num_4').val(4);
						em.addClass('dn');
						em.after(
							'<table class="bs5 w100p">' +
								'<tr><td colspan="3" class="center">' +
									'<div class="_info w400 dib">Выбор из списков, объекты которых размещаются на странице.</div>' +
								'<tr><td class="label r w150">Содержание:' +
									'<td class="i b w200">списки объектов страницы' +
									'<td><div class="icon icon-del mbm5' + _tooltip('Отменить выбор', -52) + '</div>' +
							'</table>'
						);
						em.selCancel();
					});

					switch(CMP.num_4) {
						case 1: but1.trigger('click'); break;
						case 2: but2.trigger('click'); break;
						case 3: but3.trigger('click'); elPrevAction(); break;
						case 4: but4.trigger('click');
					}
					break;
				}
				case 3: /* text */ {
					if(!CMP.id)
						CMP.width = 250;
					elPrevAction = function() {
						var txt = $.trim($('#txt_1').val());
						$('#elem-attr-id').attr('placeholder', txt);
					};
					$('#txt_1').keyup(elPrevAction);
					elPrevAction();
					break;
				}
				case 4: /* textarea */ {
					if(!CMP.id)
						CMP.width = 250;
					$('#label-prev').addClass('topi');
					elPrevAction = function() {
						var txt = $.trim($('#txt_1').val());
						$('#elem-attr-id').attr('placeholder', txt);
					};
					$('#txt_1').keyup(elPrevAction);
					elPrevAction();
					$('#elem-attr-id').autosize();
					break;
				}
				case 5: /* radio */ {
					$('#label-prev').addClass('top');
					elPrevAction = function() {
						$('#elem-attr-id')._radio({
							light:1,
							spisok:EL_VAL_ASS
						});
						_forN(EL_VAL_ASS, function(sp) {
							if(sp.def) {
								$('#elem-attr-id')._radio(sp.uid);
								return;
							}
						});
					};
					elVal(elPrevAction, $('#radio-cont'), 1);
					break;
				}
				case 6: /* календарь */ {
					elPrevAction = function() {
						$('#elem-attr-id')._calendar({
							lost:_num($('#num_3').val()),
							tomorrow:_num($('#num_4').val())
						});
					};
					$('#num_3')._check({
						func:elPrevAction
					});
					$('#num_4')._check({
						func:elPrevAction
					});
					if(CMP.id) {
						$('#num_3')._check(CMP.num_3);
						$('#num_4')._check(CMP.num_4);
					}

					break;
				}
				case 7: /* info */ {
					elPrevAction = function(v) {
						var txt = _br($.trim($('#txt_1').val()), 1);
						$('#elem-attr-id').html(txt);
					};
					$('#txt_1')
						.autosize()
						.keyup(elPrevAction);
					break;
				}
				case 8: /* connect */ {
					var html = elObjSelect(CMP, 'Привязка элемента к объекту');
					$('#connect-head').after(html);
					elObjSelect(CMP);
					break;
				}
				case 9: /* Заголовок */ {
					if(CMP.id)
						$('#num_1').val(CMP.num_1);

					elPrevAction = function() {
						var v = _num($('#num_1').val()),
							txt = _br($.trim($('#txt_1').val()), 1);

						$('#elem-attr-id')
							.html(txt)
							.removeClass('hd1 hd2 hd3')
							.addClass('hd' + v);
					};
					$('#num_1')._select({
						width:250,
						spisok:[
							{uid:1,title:'На сером фоне'},
							{uid:2,title:'С нижним подчёркиванием'}
						],
						func:elPrevAction
					});
					$('#txt_1').keyup(elPrevAction);
					break;
				}
			}

			labelPrevUpdate();
			elPrevAction();
		}
		function elObjSelect(CMP, html, del) {//выбор объекта для связки при помощи _select
			/*
				CMP - данные элемента
				html - возвращать в виде html, либо применять скрипт. Содержит заголовок
			*/
			if(html)
				return  '<table class="bs5 w100p">' +
							'<tr><td colspan="3" class="center">' +
								'<div class="_info w400 dib">' + html + '</div>' +
							'<tr><td class="label r topi w175">Объект:' +
								'<td class="w230"><input type="hidden" id="num_1" value="' + CMP.num_1 + '" />' +
								'<td>' +
							 (del ? '<div class="icon icon-del mbm5' + _tooltip('Отменить выбор', -52) + '</div>' : '') +
							'<tr><td class="label r topi">Имя колонки:' +
								'<td colspan="2">' +
									'<input type="hidden" id="num_2" value="' + CMP.num_2 + '" />' +
						'</table>';

			//применение скриптов
			var menuColSet = function(v) {
				if(!v) {
					$('#num_2')
						._select('title0', 'сначала выберите объект')
						._select('empty');
					return;
				}

				$('#num_2')._select(0);
				var send = {
					op:'dialog_spisok_on_col_load',
					dialog_id:v
				};
				$('#num_2')._select('load', send, function() {
					$('#num_2')._select('title0', 'не выбрано')
				});
			};
			$('#num_1')._select({
				width:220,
				title0:'не выбран',
				spisok:SPISOK_ON,
				func:menuColSet
			});
			$('#num_2')._select({
				width:220,
				title0:'сначала выберите объект'
			});
			menuColSet(CMP.num_1);
			$('#num_2')._select(CMP.num_2);

		}
		function submit() {
			var rand = CMP.id || Math.round(Math.random() * 10000),//случайное число для создания ID элемента
				attr_id = 'elem' + rand,
				elem = {
					attr_id:'#' + attr_id,
					id:CMP.id || rand * -1,
					type_id:TYPE_ID,
					col_name:$.trim($('#col_name').val()),
					label_name:$.trim($('#label_name').val()),
					req:_bool($('#label-req').val()),
					hint:$.trim($('#label-hint').val()),
					width:CMP.width,
					txt_1:$.trim($('#txt_1').val()),
					txt_2:$.trim($('#txt_2').val()),
					txt_3:$.trim($('#txt_3').val()),
					num_1:_num($('#num_1').val()),
					num_2:_num($('#num_2').val()),
					num_3:_num($('#num_3').val()),
					num_4:_num($('#num_4').val()),
					num_5:_num($('#num_5').val()),
					v:EL_VAL_ASS
				},
				TYPE_7 = TYPE_ID == 7 || TYPE_ID == 9,
				inp = '<input type="hidden" id="' + attr_id + '" />';

			//формирование содержания и проверка на ошибки
			switch(TYPE_ID) {
				case 1://check
					if(!elem.label_name && !elem.txt_1) {
						dialog.err('Укажите название поля,<br />либо текст для галочки');
						$('#label_name').focus();
						return;
					}
					break;
				case 2://select
					if(elem.num_3 && !elem.txt_1) {
						dialog.err('Не указан текст нулевого значения');
						$('#txt_1').focus();
						return;
					}
					break;
				case 3://text
					inp = '<input type="text" id="' + attr_id + '" style="width:' + elem.width + 'px" placeholder="' + elem.txt_1 +'" />';
					break;
				case 4://textarea
					inp = '<textarea id="' + attr_id + '" style="width:' + elem.width + 'px" placeholder="' + elem.txt_1 + '"></textarea>';
					break;
				case 5://radio
					break;
				case 6://календарь
					break;
				case 7://info
					if(!elem.txt_1) {
						dialog.err('Напишите текст информации');
						$('#txt_1').focus();
						return;
					}
					inp = '<div class="_info">' + _br(elem.txt_1, 1) + '</div>';
					break;
				case 8://connect
					inp = '<div class="grey i">Текстовый результат</div>';
					break;
				case 9://Заголовок
					if(!elem.txt_1) {
						dialog.err('Напишите текст заголовка');
						$('#txt_1').focus();
						return;
					}
					inp = '<div class="hd' + elem.num_1 + '">' + elem.txt_1 + '</div>';
					break;
			}

			var DD =
					'<dd class="over1 curM prel" val="' + elem.id + '">' +
						'<div class="cmp-set">' +
							'<div class="icon icon-edit mr3' + _tooltip('Настроить компонент', -66) + '</div>' +
							'<div class="icon icon-del-red' + _tooltip('Удалить компонент', -59) + '</div>' +
						'</div>' +
						'<table class="bs5 w100p">' +
							'<tr><td class="label label-width ' + (TYPE_7 ? '' : 'r') +' pr5" ' + (TYPE_7 ? 'colspan="2"' : 'style="width:125px"') + '>' +
									(elem.label_name ? elem.label_name + ':' : '') +
									(elem.req ? '<div class="dib red fs15 mtm2">*</div>' : '') +
									(elem.hint ? ' <div class="icon icon-info pl dialog-hint" val="' + _br(elem.hint, 1) + '"></div>' : '') +
				(!TYPE_7 ? '<td>' : '') +
								inp +
						'</table>' +
					'</dd>',
				DD_ED;//новый или изменённый элемент (для _flash)



			//вставка содержания
			if(CMP.id) {
				var dd = $('#dialog-base DD');
				for(var n = 0; n < dd.length; n++) {
					var sp = dd.eq(n),
						id = _num(sp.attr('val'), 1);
					if(CMP.id == id) {
						DD_ED = sp.after(DD).next();
						sp.remove();
						break;
					}
				}
				for(n = 0; n < DIALOG_COMPONENT.length; n++) {
					var sp = DIALOG_COMPONENT[n];
					if(CMP.id == sp.id) {
						DIALOG_COMPONENT[n] = elem;
						break;
					}
				}
			} else {
				DD_ED = $('#dialog-base').append(DD).find('dd:last');
				DIALOG_COMPONENT.push(elem);
			}

			_dialogCmpScript(elem, 1);
			dialog.close();
			DD_ED._flash();
			_dialogHeightCorrect();
		}
	},
	_dialogCmpEditFunc = function(CMP) {//настройка функций компонента
		var dialog = _dialog({
				width:500,
				top:30,
				padding:0,
				color:'orange',
				head:'Функции компонента диалога',
				content:
					'<div class="hd1">Компонент <b class="fs15">' + CMP_NAME[CMP.type_id] + '</b> <u>' + CMP.label_name + '</u></div>' +
					'<div id="cmp-func-add" class="center over1 mar20 pad10 curP b">Новая функция</div>',
				butSubmit:'Сохранить',
				submit:submit
			}),
			FC = COMPONENT_FUNC[CMP.id],
			NUM = 1,
			COND_SHOW = {//показывать или нет условие на основании действия
				1:1,
				2:1
			},
			CMP_SHOW = {//показывать или нет компоненты на основании действия
				1:1,
				2:1,
				3:1,
				4:1
			};

		cmpFuncHtml();
		$('#cmp-func-add').click(cmpFuncUnit);

		function cmpFuncHtml() {//вывод списка функций компонента
			if(!FC)
				return '';

			var html = '';
			_forN(FC, function(sp) {
				html += cmpFuncUnit(sp);
			});
			return html;
		}
		function cmpFuncUnit(v) {//формирование одной фукнции
			v = $.extend({
				action_id:0,
				cond_id:0,
				ids:0
			}, v);
			var html =
				'<div class="cmp-func bor-e8 bg-gr2 mar20 pad10 pt1" val="' + NUM + '">' +
					'<div class="hd2 mt5">' +
						'Функция ' + NUM + ':' +
						'<div class="icon icon-del fr" id="cmp-func-del' + NUM + '"></div>' +
					'</div>' +
					'<table class="bs5 mt10 w100p" id="act-tab' + NUM + '">' +
						'<tr><td class="label r w100">Действие:' +
							'<td><input type="hidden" id="cmp-func-act' + NUM + '" value="' + v.action_id + '" />' +
					'</table>' +
					'<table class="bs5' + _dn(COND_SHOW[v.action_id]) + '" id="cond-tab' + NUM + '">' +
						'<tr><td class="label r w100 topi">Условие:' +
							'<td><input type="hidden" id="cmp-func-cond' + NUM + '" value="' + v.cond_id + '" />' +
					'</table>' +
					'<table class="bs5 w100p' + _dn(CMP_SHOW[v.action_id]) + '" id="cmp-tab' + NUM + '">' +
						'<tr><td class="label r w100 topi">Компоненты:' +
							'<td><input type="hidden" id="cmp-func-ids' + NUM + '" value="' + v.ids + '" />' +
					'</table>' +
				'</div>';
			$('#cmp-func-add').before(html);

			$('#cmp-func-del' + NUM)
				._tooltip('Удалить функцию ' + NUM)
				.click(function() {
					$(this).parent().parent().remove();
				});

			$('#cmp-func-act' + NUM)._select({
				width:250,
				title0:'не выбрано',
				spisok:[
					{uid:1,title:'Скрыть'},
					{uid:2,title:'Показать'},
					{uid:3,title:'Скрыть=0 / Показать=1',
						content:'Скрыть=0 / Показать=1' +
								'<div class="grey fs12">Скрывать при нулевом значении</div>' +
								'<div class="grey fs12">Показывать при ненулевом</div>'
					},
					{uid:4,title:'Скрыть=1 / Показать=0',
						content:'Скрыть=1 / Показать=0' +
								'<div class="grey fs12">Скрывать при ненулевом значении</div>' +
								'<div class="grey fs12">Показывать при нулевом</div>'
					},
					{uid:5,title:'Выбор нескольких колонок списка',
						content:'Выбор нескольких колонок списка' +
								'<div class="grey fs12">Отобразятся все колонки в виде галочек, можно выбирать несколько или все.</div>'
					},
					{uid:6,title:'Выбор одной из колонок списка',
						content:'Выбор одной из колонок списка' +
								'<div class="grey fs12">Отобразятся все колонки в виде Radio, можно выбрать только одну.</div>'
					},
					{uid:7,title:'Составление таблицы списка',
						content:'Составление таблицы списка' +
								'<div class="grey fs12">Возможность настраивать таблицу из списка.</div>'
					}
				],
				func:function(v, attr_id) {
					var num = attr_id.split('act')[1];
					$('#cond-tab' + num)._dn(COND_SHOW[v]);
					$('#cmp-tab' + num)._dn(CMP_SHOW[v]);
				}
			});

			$('#cmp-func-cond' + NUM)._radio({
				title0:'без условий',
				light:1,
				spisok:[
					{uid:1,title:'при нулевом значении'},
					{uid:2,title:'при ненулевом значении'}
				]
			});

			var spisok = [];
			_forN(DIALOG_COMPONENT, function(sp) {
				var name = sp.label_name;
				if(sp.id == CMP.id)
					return;
				if(sp.type_id == 9)
					name = sp.txt_1;
				spisok.push({
					uid:sp.id,
					title:CMP_NAME[sp.type_id] + (name ? ': ' + name : '')
				});
			});

			$('#cmp-func-ids' + NUM)._select({
				width:300,
				title0:'не выбраны',
				multiselect:1,
				spisok:spisok
			});

			NUM++;
		}
		function submit() {
			var arr = [];

			if(!_forEq($('.cmp-func'), function(sp) {
				var	num = sp.attr('val'),
					f = {
						action_id:_num($('#cmp-func-act' + num).val()),
						cond_id:$('#cmp-func-cond' + num).val(),
						ids:$('#cmp-func-ids' + num).val()
					};

				if(!f.action_id) {
					$('#act-tab' + num)._flash({color:'red'});
					dialog.err('Не выбрано действие в функции ' + num);
					return false;
				}
				if(CMP_SHOW[f.action_id] && f.ids == 0) {
					$('#cmp-tab' + num)._flash({color:'red'});
					dialog.err('Не выбраны компоненты в функции ' + num);
					return false;
				}

				arr.push(f);
			}))
				return;

			COMPONENT_FUNC[CMP.id] = arr;
			dialog.close();
		}
	},

/*
	_dialogOpen = function(dialog_id, unit_id, unit_id_dub, funcAfterPost) {//открытие диалогового окна
		dialog_id = _num(dialog_id);
		unit_id = _num(unit_id);
		var dialog = _dialog({
				dialog_id:dialog_id,
				width:500,
				top:20,
				padding:0,
				head:'Диалог',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'dialog_open_load',
				page_id:PAGE_ID,
				dialog_id:dialog_id,
				unit_id:unit_id,
				unit_id_dub:unit_id_dub
			};

		dialog.load(send, loaded);

		function loaded(res) {
			dialog.iconEdit(res.iconEdit);
			dialog.width(res.width);
			dialog.head(unit_id ? res.head_edit : res.head_insert);
			dialog.butSubmit(res['button_' + (unit_id ? 'edit' : 'insert') + '_submit']);
			dialog.butCancel(unit_id ? res.button_edit_cancel : res.button_insert_cancel);
			window.COMPONENT_FUNC = res.func;
			window.DATA = res.data;
			window.UNIT_ID = unit_id;
			_dialogScript(res.component);
			dialog.submit(function() {
				submit(res.component);
			});
		}
		function submit(cmp) {
			send = {
				op:'spisok_' + (unit_id ? 'edit' : 'add'),
				unit_id:unit_id,
				dialog_id:dialog_id,
				elem:{},
				func:{},//значения функций, если требуется сохранять. Конкретно пока для действия 5,6
				page_id:PAGE_ID,
				block_id:window.BLOCK_ID || 0
			};

			_forN(cmp, function(sp) {
				if(!sp.func_flag)
					return;

				send.elem[sp.id] = $(sp.attr_id).val();

				//сохранение значений функций
				funcVal(sp.id, send.func);
			});

			dialog.post(send, function(res) {
				if(funcAfterPost)
					return funcAfterPost(res);

				switch(res.action_id) {
					case 1: location.reload(); break;
					case 2: location.href = URL + '&p=' + res.page_id + '&id=' + res.unit_id; break;
				}
			});
		}
		function funcVal(id, sf) {//получение значений функций
			var func = COMPONENT_FUNC,
				v = [], //переменная для сбора значений
				join = 1;//производить ли объединение элементов после сбора

			if(!func[id])
				return;

			_forN(func[id], function(sp) {
				var delem = $('#delem' + id);
				if(sp.action_id == 5)
					_forEq(delem.find('.spisok-col ._check').prev(), function(i) {
						if(_num(i.val()))
							v.push(_num(i.attr('id').split('col')[1]));
					});
				if(sp.action_id == 6)
					v.push(_num(delem.find('.spisok-col ._radio .on').attr('val')));
				if(sp.action_id == 7) {
					if($('#colNameShow').length) {//181 таблица
						v.push(_num($('#colNameShow').val()));
						v.push(_num($('#rowLight').val()));
						v.push(_num($('#rowSmall').val()));
						_forEq(delem.find('dd'), function(eq) {
							var col_id = _num(eq.find('input:first').val(), 1),
								tr = eq.find('.tr').val(),
								link_on = eq.find('.td-link-on').hasClass('dn') ? 0 : 1,
								link = _num(eq.find('.elem-link').val());
							if(!col_id)
								return;
							v.push(col_id + '&' + tr + '&' + link_on + '&' + link);
						});
					}
				}
			});
			sf[id] = join ? v.join(',') : v;
		}
	},
*/
	_dialogOpen = function(o) {//открытие диалогового окна
		var dialog = _dialog({
			dialog_id:o.dialog_id,
			block_id:o.block_id,  //для передачи значений, если будет требоваться редактирование диалога
			unit_id:o.unit_id,    //также для передачи

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
		if(o.to_del)
			dialog.bottom.find('.submit').addClass('red');
		else
			_elemActivate(o.cmp, o.unit);

		function submit() {
			var send = {
				op:'spisok_add',
				page_id:PAGE_ID,
				dialog_id:o.dialog_id,
				block_id:o.block_id,
				unit_id:o.unit_id,
				cmp:{}
			};

			if(o.unit_id) {
				send.op = 'spisok_edit';
				if(o.to_del)
					send.op = 'spisok_del';
			}

			//получение значений компонентов
			_forIn(o.cmp, function(sp, id) {
				switch(sp.dialog_id) {
					case 19://наполнение для некоторых компонентов
						send.cmp[id] = _dialogCmpValue(sp, 'get');
						return;
				}
				send.cmp[id] = $(sp.attr_id).val();
			});

			dialog.post(send, function(res) {
//				return;
				switch(res.action_id) {
					case 1: location.reload(); break;
					case 2: location.href = URL + '&p=' + res.action_page_id + '&id=' + res.unit_id; break;
					case 3://обновление содержимого блоков
						var bln = '#block-level-' + res.block_obj_name;
						$(bln).after(res.level).remove();
						$(bln)
							.find('.block-grid-on')
							.removeClass('grey')
							.trigger('click');
						break;
				}
			});
		}
	},
	_dialogCmpValue = function(o, val) {//наполнение для некоторых компонентов
		var el = $(o.attr_pe);

		//получение данных для сохранения
		if(val == 'get') {
			var send = [];
			_forEq(el.find('dd'), function(sp) {
				send.push({
					id:_num(sp.attr('val')),
					title:sp.find('.title').val(),
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

		for(var i in val)
			valueAdd(val[i])

		function valueAdd(v) {
				v = $.extend({
					id:0,
					uid:NUM,
					title:'имя значения ' + NUM,
					def:0,
					use:0
				}, v);

				DL.append(
					'<dd class="over1" val="' + v.id + '">' +
						'<table class="bs5 w100p">' +
							'<tr><td class="w25 center"><div class="icon icon-move-y pl curM"></div>' +
								'<td class="w90 grey r">Значение ' + NUM + ':' +
								'<td><input type="text" class="title w100p" id="el-val-' + v.uid + '" value="' + v.title + '" />' +
								'<td class="w15">' +
									'<input type="hidden" class="def" id="el-def-' + v.uid + '" value="' + v.def + '" />' +
								'<td class="w50 r">' +
						   (v.use ? '<div class="dib fs11 color-ccc mr3 curD' + _tooltip('Использование', -53) + v.use + '</div>'
									:
									'<div val="' + v.uid + '" class="icon icon-del pl' + _tooltip('Удалить значение', -55) + '</div>'
						   )+
						'</table>' +
					'</dd>'
				);

				DL.sortable({axis:'y',handle:'.icon-move-y'});

				$('#el-def-' + v.uid)._check({
					tooltip:'По умолчанию',
					func:function(v, attr_id) {
						if(!v)
							return;
						//снятие галочек с остальных значений
						_forEq(DL.find('.def'), function(sp) {
							if(sp.attr('id') == attr_id)
								return;
							sp._check(0);
						});
					}
				});

				DL.find('.icon-del:last').click(function() {
					var t = $(this),
						p = _parent(t, 'DD');
					p.remove();
				});
				NUM++;
			}
	},

	_elemActivate = function(elem, unit, is_edit) {//применение функций (активирование) к элементам
		var attr_focus = false;//элемент, на который будет поставлен фокус

		_forIn(elem, function(sp) {
			if(sp.focus)
				attr_focus = sp.attr_id;
			switch(sp.dialog_id) {
				//textarea
				case 5:	$(sp.attr_id).autosize(); return;
				//select
				case 17:
					$(sp.attr_id)._select({
						disabled:is_edit,
			//			write:1,
			//			block:1,
						width:sp.width,
						title0:sp.txt_1,
						spisok:sp.elv_spisok
					});
					return;
				//наполнение для некоторых компонентов
				case 19:
					if(is_edit)
						return;
					_dialogCmpValue(sp, unit[sp.col]);
					return;
			}
		});

		if(attr_focus)
			$(attr_focus).focus();
	},

	_dialogScript = function(component, isEdit) {//применение скриптов после загрузки данных диалога

		//рисование компонентов
		_forN(component, function(ch) {
			_dialogCmpScript(ch, isEdit);
		});

		//применение функций, привязанных к компонентам
		_forN(component, function(ch) {
//			_dialogCmpFunc(ch.id, _num($(ch.attr_id).val()), isEdit, 1);
		})
	},
	_dialogCmpScript = function(ch, isEdit) {//применение скриптов для конкретного компонента диалога
		switch(ch.type_id) {
			case 1: /* check */ {
				$(ch.attr_id)._check({
					title:ch.txt_1,
					light:1,
					disabled:isEdit
				});
				break;
			}
			case 2: /* select */ {
				funcKeyup = ch.num_4 != 3 ? undefined : function(v, t) {
					if(t.isProcess())
						return;
					t.process();
					var send = {
						op:'spisok_select_get',
						dialog_id:ch.num_1,
						cmp_id:ch.num_2,
						v:v
					};
					_post(send, function(res) {
									t.spisok(res.spisok);
								},
								function() { t.cancel() })
				};
				$(ch.attr_id)._select({
					width:ch.width,
					title0:ch.num_3 ? ch.txt_1 : '',
					write_save:ch.num_4 == 3,
					spisok:ch.v,
					func:function(v) {
						_dialogCmpFunc(ch.id, v, isEdit);
					},
					funcKeyup:funcKeyup
				});
				if(isEdit) {
					$(ch.attr_id)._select('disable');
				}
				break;
			}
			case 3: /* input */ {
				if(isEdit)
					$(ch.attr_id)
						.attr('disabled', true)
						.resizable({
							minWidth:50,
							maxWidth:350,
							grid:10,
							handles:'e',
							stop:function(event, ui) {
								var id = _num(ui.originalElement[0].id.split('elem')[1]);
								for(var n = 0; n < DIALOG_COMPONENT.length; n++) {
									var sp = DIALOG_COMPONENT[n];
									if(sp.id == id) {
										sp.width = ui.size.width - 18;
										break;
									}
								}
							}
						});
				break;
			}
			case 5: /* radio */ {
				$(ch.attr_id)
					._radio({
						light:1,
						spisok:ch.v
					})
					.parent().parent()
					.find('.label').addClass('top');
				if(isEdit)
					$(ch.attr_id)._radio('disable');
				break;
			}
			case 6: /* Календарь */ {
				$(ch.attr_id)._calendar({
					lost:ch.num_3,
					tomorrow:ch.num_4
				});
				break;
			}
			case 7: /* Информация */ {
				break;
			}
			case 8: /* Связка */ {
				break;
			}
			case 9: /* Заголовок */ {
				break;
			}
		}
	},
	_dialogCmpFunc = function(cmp_id, v, isEdit, first) {//функция, исполняемая в компонентах диалога
		if(isEdit)
			return;

		var farr = COMPONENT_FUNC[cmp_id];
		if(!farr)
			return;

		//первое предварительное действие быстрое, не плавное
		var UP = first ? 'hide' : 'slideUp',
			DOWN = first ? 'show' : 'slideDown',
			speed = first ? 0 : 200;

		for(var n = 0; n < farr.length; n++) {
			var func = farr[n],
				hide = func.action_id == 1,
				show = func.action_id == 2,
				h0s1 = func.action_id == 3,//Скрыть=0 / Показать=1
				h1s0 = func.action_id == 4,//Скрыть=1 / Показать=0
				act = hide ? UP : DOWN,

				ifNo = func.cond_id == 1,   //если нет значeния
				ifYes = func.cond_id == 2,  //если есть значение

				ids = func.ids.split(',');

			if(func.action_id == 5 || func.action_id == 6) {//вывод элементов списка
				var delem = $('#delem' + cmp_id);
				delem.find('.spisok-col').remove();

				if(!v)
					continue;

				delem.append('<div class="spisok-col _busy">&nbsp;</div>');
				var spc = delem.find('.spisok-col'),
					send = {
						op:'spisok_col_get',
						page_id:PAGE_ID,
						component_id:cmp_id,
						vid:v //либо dialog_id, либо pe_id, если стоит условие - список только с текущей страницы
					};
				_post(send, function(res) {
					spc.html(res.html).removeClass('_busy');
					if(func.action_id == 6) {
						spc.find('input')._radio()._radio(_num(DATA.num_3));
					}
				}, function(res) {
					spc.html(res.text)
						.removeClass('_busy')
						.addClass('center red');
				});

				continue;
			}

			_dialogCmpFuncElemList(cmp_id, func, v);

			if(ifNo && v)
				return;

			if(ifYes && !v)
				return;

			if(h0s1)
				act = !v ? UP : DOWN;

			if(h1s0)
				act = v ? UP : DOWN;

			for(var i in ids)
				$('#delem' + ids[i])[act](speed);
		}
	},
	_dialogCmpFuncElemList = function(cmp_id, o, v) {//настройка столбцов списка
		if(o.action_id != 7)
			return;

		var delem = $('#delem' + cmp_id);
		delem.find('#elem-list').remove();
		
		if(!v)
			return;

		delem.append('<div id="elem-list" class="_busy">&nbsp;</div>');
		var spc = delem.find('#elem-list'),
			send = {
				op:'spisok_elem_list',
				elem_id:UNIT_ID,
				component_id:cmp_id,
				spisok_id:v
			},
			DL,
			NUM = 0;
		window.RES = [];
		_post(send, listShow, function(res) {
			spc.html(res.text)
				.removeClass('_busy')
				.addClass('center red');
		});

		function listShow(res) {
			spc.removeClass('_busy');

			//настройка колонок будет доступна позже
			if(res.after)
				return spc.html(res.after);

			spc.html(res.html);

			RES = res;

			switch(res.spisok_type) {
				default:
				case 181:/* таблица */ {
					DL = spc.find('dl');

					_forN(res.arr, itemAdd);

					DL.sortable({
						axis:'y',
						handle:'.icon-move-y'
					});

					spc.find('.item-add').click(itemAdd);

					break;
				}
				case 182:/* шаблон */ {
					for(var k in res.block_arr)
						BLOCK_ARR[k] = res.block_arr[k];
					//настройка через фукнцию _blockSpisokUnitElAdd
					break;
				}
			}
		}
		function itemAdd(sp) {
			sp = $.extend({
				id:0,
				tr:'',
				link_on:0,
				link:0
			}, sp);
			DL.append(
				'<dd class="over1">' +
					'<table class="bs5 w100p">' +
						'<tr>' +
							'<td class="topi w15">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w175">' +
								'<input type="hidden" id="elem-col' + NUM + '" value="' + sp.id + '" />' +
							'<td class="w175 top">' +
								'<input type="text" class="tr w175 b" placeholder="имя колонки" value="' + sp.tr + '" />' +
							'<td class="td-link-on">' +
								'<input type="hidden" class="elem-link" id="elem-link' + NUM + '" value="' + sp.link + '" />' +
							'<td class="topi r">' +
								'<div class="icon icon-del pl' + _tooltip('Удалить колонку', -51) + '</div>' +
					'</table>'
			);

			var DD = DL.find('dd:last');

			colSel($('#elem-col' + NUM));
			$('#elem-link' + NUM)._check({
					tooltip:'Является ссылкой'
				});
			DD.find('.td-link-on')._dn(sp.link_on);
			DD.find('.icon-del').click(function() {
				DD.remove();
			});

			NUM++;
		}
		function colSel(sp) {
			sp._select({
				title0:'колонка не выбрана',
				spisok:RES.label_name_select,
				func:function(v, id, item) {
					_parent($('#' + id)).find('.td-link-on')._dn(item.link_on);
				}
			});
		}
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

		var p = t.prev(),
			v = _num(p.val()) ? 0 : 1;

		p.val(v);
		t[(v ? 'add' : 'remove') + 'Class']('on');
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
				dialog_id:0,    //id диалогового окна
				unit_id:0,      //id единицы списка
				block_id:0      //id блока, если элемент вставляется в блок
			},
			busy = t.hasClass('icon') ? 'spin' : '_busy';

		if(t.hasClass(busy))
			return;

		_forN(val.split(','), function(sp) {
			var spl = sp.split(':'),
				k = spl[0];
			send[k] = _num(spl[1]);
		});

		t.addClass(busy);
		_post(send, function(res) {
			t.removeClass(busy);
			_dialogOpen(res);
		}, function(res) {
			t.removeClass(busy);
			t._hint({
				msg:res.text,
				color:'red',
				pad:10,
				show:1
			});
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

$.fn._check = function(o) {
	var t = $(this),
		attr_id = t.attr('id'),
		div_id = attr_id + '_check',
		win = attr_id + '_check_win',
		S = window[win];

	if(!attr_id)
		return;

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
		o.func(v, attr_id);
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
		o.func(_num(t.val()), attr_id);
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
		attr_id = 'radio' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_count';

	o = $.extend({
		width:50,
		bold:0,
		min:false,  //минимальное значение
		max:false,  //максимальное значение
		minus:0,    //может уходить в минус
		step:1,     //шаг
		tooltip:'',
		func:function() {}
	}, o);

	if(o.min < 0)
		o.minus = 1;

	var val = _num(t.val());
	valCorrect();
	t.val(val)
	 .width(o.width - 18)
	 .attr('type', 'text')
	 .attr('readonly', true);

	if(o.bold)
		t.addClass('b');

	t.wrap('<div class="_count" id="' + win + '">');

	var el = $('#' + win);
	el._dn(val, 'nol');
	el.append(
		'<div class="but"></div>' +
		'<div class="but but-b"></div>'
	);

	if(o.tooltip)
		el._tooltip(o.tooltip, -15);

	el.find('.but').click(function() {
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
$.fn._select = function(o, o1, o2) {//выпадающий список от 03.01.2018
	var t = $(this),
		attr_id = t.attr('id'),
		VALUE = t.val();

	if(!attr_id) {
		attr_id = 'select' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_select',
		s = window[win];
	console.log(s);
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
//		limit:0,
		write:0,            // возможность вводить значения
		write_save:0,       // сохранять текст, если даже не выбран элемент
		msg_empty:'Список пуст',
		multiselect:0,      // возможность выбирать несколько значений. Идентификаторы перечисляются через запятую
		func:function() {},	// функция, выполняемая при выборе элемента
//		funcAdd:null,		// функция добавления нового значения. Если не пустая, то выводится плюсик. Функция передаёт список всех элементов, чтобы можно было добавить новый
//		funcKeyup:funcKeyup	// функция, выполняемая при вводе в INPUT в селекте. Нужна для вывода списка из вне, например, Ajax-запроса, либо из vk api.
		end:0//удалить
	}, o);

	var dis = o.disabled ? ' disabled' : '',
		dib = o.block ? '' : ' dib',
		width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		html =
		'<div class="_select' + dis + dib + '" id="' + win + '" style="' + width + '">' +
			'<table class="w100p">' +
				'<tr><td><input type="text" class="select-inp" placeholder="' + o.title0 + '" readonly />' +
//					'<td class="w15 clear"><div class="icon icon-del pl' + _tooltip('Очистить', -29) + '</div>' +
//			(!dis ? '<td class="w25 r"><div class="icon icon-add pl"></div>' : '') +
					'<td class="arrow">' +
			'</table>' +
			'<div class="select-res"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	var SEL = t.next(),
		INP = SEL.find('.select-inp'),
		RES = SEL.find('.select-res'),
		MASS_ASS = {},//ассоциативный массив в виде {1:'text'}
		MASS_SEL = [],//массив в виде [{id:1,title:'text1'},{id:2,title:'text2'}]
		MASS_SEL_SAVE = [],//дублирование MASS_SEL

		end;//удалить

	massCreate();
	spisokPrint();
	valueSet(VALUE);

	SEL.click(function(e) {
		if(dis)
			return;

		var rs = SEL.hasClass('rs'),
			tagret = $(e.target);

		if(tagret.hasClass('select-unit'))
			valueSet(tagret.attr('val'));

		if(rs && o.write && tagret.hasClass('select-inp'))
			return;
		if(tagret.hasClass('icon-add'))
			return;
		if(tagret.hasClass('icon-del'))
			return;
		if(tagret.hasClass('empty'))
			return;

		SEL._dn(rs, 'rs');

		if(!rs)
			_forEq(RES.find('.select-unit'), function(sp) {
				if(VALUE == sp.attr('val')) {
					RES.find('.select-unit').removeClass('ov');
					sp.addClass('ov');
					return false;
				}
			});
	});
	RES.find('.select-unit').mouseenter(function() {
		RES.find('.ov').removeClass('ov');
		$(this).addClass('ov');
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
				content = sp;
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
				if(title === undefined)
					return;
				content = sp.content;
				if(content === undefined)
					content = title;
			}

			MASS_ASS[id] = title;
			unit = {
				id:id,
				title:title,
				content:content
			};
			MASS_SEL.push(unit);
			MASS_SEL_SAVE.push(unit);
		});
	}
	function spisokPrint() {//вставка списка в select
		if(!MASS_SEL.length) {
			RES.html('<div class="empty">' + o.msg_empty + '</div>');
			return;
		}

		var html = '';
		if(o.title0)
			html += '<div class="select-unit title0" val="0">' + o.title0 + '</div>';

		_forN(MASS_SEL, function(sp) {
			html += '<div class="select-unit" val="' + sp.id + '">' + sp.content + '</div>';
		});
		RES.html(html);
	}
	function valueSet(v) {//установка значения
		v = _num(v);
		VALUE = v;
		t.val(v);
		INP.val(MASS_ASS[v]);
	}
	function action() {//выполнение действия в существующем селекте
		if(s === undefined)
			return t;

		switch(o) {
			case 'disable': s.disable(); break;
		}

		return s;
	}

	t.disable = function() {//делание неактивным
		SEL.addClass('disabled')
		   .removeClass('rs');
		dis = true;
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
					_post(o1, function(res) {
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
		pad:0,      //внутренние отступы контента

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
		attr_id = t.attr('id');

	if(!attr_id) {
		attr_id = 'sr' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_search',
		S = window[win];

	switch(typeof o) {
		case 'number':
		case 'string':
			if(o == 'val') {
				if(v) {
					S.inp(v);
					return;
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
			return t;
	}

	if(S)
		return;

	o = $.extend({
		ex:0,
		width:126,
		focus:0,//сразу устанавливать фокус
		hold:'', //текст-подсказка placeholder
		func:function() {},
		enter:0,//применять введённый текст только после нажатия ентер
		v:''    //введённое значение
	}, o);

	//проверка такой же поисковой строки, которая была выведена через PHP. Если уже есть, то применение функций.
	var p = t.parent();
	if(p.hasClass('_search')) {
		o.ex = 1;    //флаг существующей поисковой строки
		o.width = p.width();
	}

	if(!o.ex) {
		t.width(o.width - 87);
		t.wrap('<div class="_search" style="width:' + o.width + 'px">');
		t.before(
			'<div class="icon icon-del fr dn "></div>' +
			'<div class="_busy dib fr mr5 dn"></div>' +
			'<div class="hold">' + o.hold + '</div>'
		);
	}

	var _s = t.parent(),
		inp = t,
		busy = _s.find('._busy'),
		hold = _s.find('.hold'),
		del = _s.find('.icon-del');

	if(o.focus) {
		inp.focus();
		holdFocus()
	}

	inp .focus(holdFocus)
		.blur(holdBlur)
		.keydown(function(e) {
			setTimeout(function() {
				var v = inp.val();
				hold._dn(!v);
				del._dn(v);
				if(o.enter && e.which != 13)
					return;
				o.func(v, attr_id);
			}, 0);
		});

	t.clear = function() {
		inp.val('');
		del.addClass('dn');
		hold.removeClass('dn');
	};

	del.click(function() {
		t.clear();
		o.func('', attr_id);
	});

	_s.click(function() {
		inp.focus();
		holdFocus();
	});

	t.inp = function(v) {
		if(!v)
			return $.trim(inp.val());
		inp.val(v);
		del.removeClass('dn');
		hold.addClass('dn');
		return $(this);
	};
	t.process = function() {//показ процесса ожидания с правой стороны
		busy.removeClass('dn');
	};
	t.isProcess = function() {//определение, в процессе ли поиск
		return !busy.hasClass('dn');
	};
	t.cancel = function() {//скрытие процесса ожидания с правой стороны
		busy.addClass('dn');
	};
	t.clear = function() {
		inp.val('');
		del.addClass('dn');
		hold.removeClass('dn');
	};
	window[win] = t;

	t.inp(o.v);

	return t;

	function holdFocus() { hold.css('color', '#ccc'); }
	function holdBlur() { hold.css('color', '#777'); }
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



