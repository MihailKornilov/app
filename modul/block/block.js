var _ids = function(v, count) {
		if(!v)
			return 0;
		if(typeof v == 'number')
			return count ? 1 : v;
		if(typeof v == 'string') {
			var send = [];
			_forN(v.split(','), function(id) {
				id = _num(id);
				if(!id)
					return;
				send.push(id);
			});
			return count ? send.length : send.join();
		}
		return 0;
	},
	_blockUnitSetup = function() {//настройка стилей блока в выплывающем окне

		//если производится процесс деления блока на части, настройка стилей не выводится
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLKK[block_id],
			borSave = function() {//нажатие на галочку для установки/снятия бордюра
				BL.bor = $('#block-unit-bor0').val() + ' ' +
						 $('#block-unit-bor1').val() + ' ' +
						 $('#block-unit-bor2').val() + ' ' +
						 $('#block-unit-bor3').val();
				BL.save = 1;
			};
		BL.id = block_id;
		BL.attr_bl = ATTR_BL(block_id);

		//идёт процес сохранения
		if(BL.save || $(BL.attr_bl).hasClass('_busy'))
			return;

		t._hint({
			msg:'<div class="pad5">' +
					'<div class="line-b">' +
						'<span class="fs16 blue' + (SA ? ' curD' + _tooltip('#' + BL.id, -8)  : '">') + 'Блок</span>' +
						'<div val="dialog_id:210,block_id:' + BL.id + '" class="icon icon-usd pl fr dialog-open ml3' + _tooltip('Настроить действия', -62) + '</div>' +
						'<div val="dialog_id:230,block_id:' + BL.id + '" class="icon icon-eye pl fr dialog-open' + _tooltip('Условия отображения', -67) + '</div>' +
					'</div>' +
					_blockUnitBg(BL) +
					_blockUnitBor(BL) +
					_blockUnitBut(BL) +
				'</div>' +
				_elemUnit(BL),
			width:228,
			objPos:'mouse',
			show:1,
			delayShow:500,
			delayHide:300,
			func:function() {
				$('#block-unit-bor0')._check({
					tooltip:'сверху',
					func:function(v) {
						$(BL.attr_bl).css('border-top', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor1')._check({
					tooltip:'справа',
					func:function(v) {
						$(BL.attr_bl).css('border-right', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor2')._check({
					tooltip:'снизу',
					func:function(v) {
						$(BL.attr_bl).css('border-bottom', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor3')._check({
					tooltip:'слева',
					func:function(v) {
						$(BL.attr_bl).css('border-left', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-width-auto')._check({
					title:'<div class="fs11">width auto</div>',
					func:function(v) {
						BL.width_auto = v;
						BL.save = 1;
					}
				});
				$('#block-sa-view')._check({
					title:'<div class="fs12">SA only</div>',
					func:function(v) {
						BL.sa = v;
						BL.save = 1;
					}
				});
				$('#block-hidden')._check({
					title:'<div class="fs12">Скрыт</div>',
					func:function(v) {
						BL.hidden = v;
						BL.save = 1;
					}
				});

				if(BL.elem_id) {
					var EL = ELMM[BL.elem_id],
						tMar = {
							0:'сверху',
							1:'справа',
							2:'снизу',
							3:'слева'
						};
					for(var n = 0; n < 4; n++)
						$('#el-mar' + n)._count({
							step:5,
							max:50,
							tooltip:'Отступ ' + tMar[n],
							func:function(v, id) {
								var pos = _num(id.split('el-mar')[1]),
									top =    pos == 0 ? v : _num($('#el-mar0').val()),
									right =  pos == 1 ? v : _num($('#el-mar1').val()),
									bottom = pos == 2 ? v : _num($('#el-mar2').val()),
									left =   pos == 3 ? v : _num($('#el-mar3').val());
								_attr_el(BL.elem_id)
									.css({margin:
										top + (top ? 'px' : '') + ' ' +
										right + (right ? 'px' : '') + ' ' +
										bottom + (bottom ? 'px' : '') + ' ' +
										left + (left ? 'px' : '')
									});

								EL.mar = top + ' ' + right + ' ' + bottom + ' ' + left;
								BL.save = 1;
							}
						});
					$('#elem-size')._count({
						min:10,
						max:18,
						func:function(v) {
							_attr_el(BL.elem_id)
								.removeClass('fs' + EL.size)
								.addClass('fs' + v);
							EL.size = v;
							BL.save = 1;
						}
					});
					$('#elem-img-width')._count({
						width:60,
						step:[30,50,80,100,150,200,250],
						func:function(v) {
							_attr_el(BL.elem_id)
								.find('img')
								.width(v)
								.height('auto');
							EL.width = v;
							BL.save = 1;
						}
					});
					$('#elem-img-height')._check({
						tooltip:'Также ограничивать высоту',
						func:function(v) {
							EL.num_7 = v;
							BL.save = 1;
						}
					});
					$('#elem-img-circle')._check({
						func:function(v) {
							EL.num_8 = v;
							BL.save = 1;
						}
					});
				}
			},
			funcBeforeHide:function() {
				_blockUnitSave(BL);
			}
		});
	},
	_blockUnitBg = function(BL) {//заливка блока
		var BGS = 'bg-fff bg-gr3 bg-ffe bg-efe bg-gr2 bg-fee',
			div = '';

		$(document)
			.off('click', '#block-set-bg div.curP')
			.on('click', '#block-set-bg div.curP', function() {
				var unit = $(this),
					bg = unit.attr('val'),
					sel = unit.hasClass('sel');

				unit.parent().find('.sel').removeClass('sel');
				_attr_bl(BL.id).removeClass(BGS);
				$('#block-set-bg .bg70 .galka')._dn();

				if(!sel) {
					unit.addClass('sel');
					_attr_bl(BL.id).addClass(bg);
				}

				BL.bg = sel ? '' : bg;
				BL.save = 1;
			});

		_forN(BGS.split(' '), function(sp, n) {
			var sel = BL.bg == sp ? ' sel' : '',
				ml3 = n ? ' ml3' : '';
			div += '<div class="dib center w25 h25 bor-e8 curP fs17 grey ' + sp + ml3 + sel + '" val="' + sp + '">&#10004;</div>';
		});

		return '<div class="color-555 fs14 mt5">Заливка:</div>' +
			   '<div id="block-set-bg" class="mt3">' + div + _blockUnitBg70(BL, BGS) + '</div>';
	},
	_blockUnitBg70 = function(BL, BGS) {//динабическая заливка блока
		$(document)
			.off('click', '#block-set-bg .bg70')
			.on('click', '#block-set-bg .bg70', function() {
				var t = $(this),
					galka = t.find('.galka');

				if(!galka.hasClass('dn')) {
					galka._dn();
					BL.bg = '';
					BL.save = 1;
					return;
				}

				_dialogLoad({
					dialog_id:11,
					block_id:BL.id,
					dop:{
						nest:1,
						mysave:1,
						allow:'29,59,70'
					},
					busy_obj:$(this),
					busy_cls:'busy',
					func_save:function(res) {
						BL.bg = res.v;
						BL.save = 1;
						_attr_bl(BL.id).removeClass(BGS);
						_blockUnitSave(BL);
					}
				});
			});

		return '<div class="bg70 prel dib center w25 bor-e8 grey ml3' +
						_tooltip('Окраска блока согласно<br>цвету фона записи', -70, '', 1) +
					'<div class="galka pabs fs17 pl5' + _dn(_ids(BL.bg)) + '">&#10004;</div>' +
					'<div class="pabs icon spin"></div>' +
					'<table class="w100p curP">' +
						'<tr><td class="bg-efe" style="width:24px;height:8px">' +
						'<tr><td class="bg-ffe" style="width:24px;height:9px">' +
						'<tr><td class="bg-fee" style="height:8px">' +
					'</table>' +
			   '</div>';
	},
	_blockUnitBor = function(BL) {//границы блока
		var bor = BL.bor.split(' ');
		return '<table class="mt10">' +
				'<tr><td class="color-555 fs14">Границы:' +
					'<td class="pr3">' +
						'<div class="ml20 pl5"><input type="hidden" id="block-unit-bor0" value="' + bor[0] + '"></div>' +
						'<table class="bs5">' +
							'<tr>' +
								'<td><input type="hidden" id="block-unit-bor3" value="' + bor[3] + '">' +
								'<td class="pl20"><input type="hidden" id="block-unit-bor1" value="' + bor[1] + '">' +
						'</table>' +
						'<div class="ml20 pl5"><input type="hidden" id="block-unit-bor2" value="' + bor[2] + '"></div>' +
					_blockUnitSa(BL) +
				'</table>';
	},
	_blockUnitSa = function(BL) {//настройка блока для SA
		if(!SA)
			return '';

		return '<td class="bg-ffc bor-f0 pl5 pr3">' +
			'<input type="hidden" id="block-width-auto" value="' + BL.width_auto + '" />' +
			'<input type="hidden" id="block-sa-view" value="' + BL.sa + '" />' +
			'<input type="hidden" id="block-hidden" value="' + BL.hidden + '" />' +
			'';
	},
	_blockUnitBut = function(BL) {//кнопки
		if(BL.elem_id)
			return '';

		return '<div class="mt20 center">' +
					'<button class="vk small orange mb5" onclick="_blockUnitGrid($(this),' + BL.id + ')">Настроить подблоки</button>' +
 (!BL.child_count ? '<button class="dialog-open vk small green" id="elem-hint-add" val="dialog_id:50,block_id:' + BL.id + '">Вставить элемент</button>' : '') +
				'</div>';
	},
	_blockUnitGrid = function(obj, block_id) {
		var send = {
			op:'block_unit_gird',
			id:block_id,
			busy_obj:obj
		};
		_post(send, function(res) {
			$('._hint').remove();
			$('.block-content-' + res.obj_name).html(res.html);
			$('#grid-stack')._grid({
				parent_id:block_id,
				obj_name:res.obj_name,
				obj_id:res.obj_id,
				width:res.width
			});
		});
	},
	_blockUnitSave = function(BL) {
		if(!BL.save)
			return;

		BL.op = 'block_unit_style_save';

		if(BL.elem_id)
			BL.elem = $.extend({
				mar:'0 0 0 0',
	            font:'',
				color:'',
				size:13,
				width:0,
				num_7:0,//ограничение высоты фото [60]
				num_8:0 //закруглённые углы фото [60]
			}, ELMM[BL.elem_id]);

		BL.busy_obj = _attr_bl(BL.id);

		_post(BL, function(res) {
			BL.save = 0;
			if(res.elem_js)
				ELMM[BL.elem_id] = res.elem_js;
		});
	},

	_elemUnit = function(BL) {//настройки элемента в выплывающем окне
		if(!BL.elem_id)
			return '';

		var EL = ELMM[BL.elem_id];
		EL.id = BL.elem_id;
		EL.attr_el = ATTR_EL(EL.id);

		return '<div class="mar5 pad5 bor-e8 bg-gr1" id="elem-hint-' + EL.id + '">' +
			'<div class="line-b">' +
				'<a val="dialog_id:118,get_id:' + EL.id + '" class="fs16 blue dialog-open' + _tooltip('Info #' + EL.id, -5) + 'Элемент</a>' +
				'<div class="fr mtm3">' +
					_elemUnitUrl(EL) +
					_elemUnitFormat(EL) +
					_elemUnitHint(EL) +
					_elemUnitAction(EL) +
					'<div val="dialog_id:' + EL.dialog_id + ',edit_id:' + EL.id + '" class="icon icon-edit dialog-open ml3' + _tooltip('Редактировать элемент', -134, 'r') + '</div>' +
					'<div val="dialog_id:' + EL.dialog_id + ',del_id:' + EL.id + '" class="icon icon-del-red dialog-open ml3' + _tooltip('Удалить элемент', -94, 'r') + '</div>' +
				'</div>' +
			'</div>' +

			'<table class="w100p mt5">' +
				'<tr><td>' + _elemUnitMar(EL) +
					'<td>' + _elemUnitPlace(BL) +
			'</table>' +

			_elemUnitStyle(EL) +
			_elemUnitImg(EL) +
		'</div>';
	},
	_elemUnitUrl = function(EL) {//иконка для настройки ссылки
		if(!EL.url_use)
			return '';
		return '<div val="dialog_id:220,block_id:' + EL.block_id + '" class="icon icon-link ml3 pl dialog-open' + _tooltip('Настроить ссылку', -56) + '</div>'
	},
	_elemUnitFormat = function(EL) {//иконка с дополнительными условиями отображения
		if(!EL.rule14)
			return '';
		return '<div val="dialog_id:240,block_id:' + EL.block_id + '" class="icon icon-eye ml3 dialog-open pl' + _tooltip('Условия отображения', -67) + '</div>';
	},
	_elemUnitHint = function(EL) {//иконка для настройки выплывающей подсказки
		if(!EL.rule15)
			return '';
		var hint_id = EL.hint ? EL.hint.id : 0,
			pl = EL.hint && EL.hint.on;
		return '<div val="dialog_id:43,block_id:' + EL.block_id + ',edit_id:' + hint_id + '"' +
				   ' class="icon icon-hint ml3 curP dialog-open' + _dn(!pl, 'pl') + _tooltip('Настроить подсказку', -65) +
			   '</div>';
	},
	_elemUnitAction = function(EL) {//иконка для настройки действий
		if(!EL.eadi)
			return '';
		return '<div val="dialog_id:' + EL.eadi + ',block_id:' + EL.block_id + '" class="icon icon-usd ml3 dialog-open' + _tooltip('Настроить действия', -62) + '</div>';
	},
	_elemUnitMar = function(EL) {
		var mar = EL.mar.split(' ');
		return  '<div class="ml30 pl3">' +
					'<input type="hidden" id="el-mar0" value="' + mar[0] + '" />' +
				'</div>' +
				'<table class="mt5">' +
					'<tr>' +
						'<td class=""><input type="hidden" id="el-mar3" value="' + mar[3] + '" />' +
						'<td class="pl10"><input type="hidden" id="el-mar1" value="' + mar[1] + '" />' +
				'</table>' +
				'<div class="mt5 ml30 pl3">' +
					'<input type="hidden" id="el-mar2" value="' + mar[2] + '" />' +
				'</div>';
	},
	_elemUnitPlace = function(BL) {//позиция элемента
		return  '<table id="elem-pos">' +
			'<tr><td class="fs14 color-555 pb3 center">Позиция' +
			'<tr><td><div val="top" class="icon-wiki iw6 mr3' + _dn(BL.pos == 'top','on') + _tooltip('Вверх-влево', -37) + '</div>' +
					'<div val="top center" class="icon-wiki iw7 mr3' + _dn(BL.pos == 'top center','on') + _tooltip('Вверх-центр', -35) + '</div>' +
					'<div val="top r" class="icon-wiki iw8' + _dn(BL.pos == 'top r','on') + _tooltip('Вверх-вправо', -73, 'r') + '</div>' +
			'<tr><td>' + _elemUnitPlaceMiddle(BL) +
			'<tr><td><div val="bottom" class="icon-wiki iw9 mr3' + _dn(BL.pos == 'bottom','on') + _tooltip('Вниз-влево', -33) + '</div>' +
					'<div val="bottom center" class="icon-wiki iw10 mr3' + _dn(BL.pos == 'bottom center','on') + _tooltip('Вниз-центр', -32) + '</div>' +
					'<div val="bottom r" class="icon-wiki iw11' + _dn(BL.pos == 'bottom r','on') + _tooltip('Вниз-вправо', -65, 'r') + '</div>' +
		'</table>';
	},
	_elemUnitPlaceMiddle = function(BL) {//центральная часть позиции
		$(document)
			.off('click', '#elem-pos div')
			.on('click', '#elem-pos div', function() {
				var unit = $(this),
					v = unit.attr('val');

				unit.parents('#elem-pos').find('.on').removeClass('on');
				unit.addClass('on');

				$(BL.attr_bl).removeClass('top r center bottom');
				if(v)
					$(BL.attr_bl).addClass(v);

				BL.pos = v;
				BL.save = 1;
			});
		return  '<div val="" class="icon-wiki iw3 mr3' + _dn(!BL.pos,'on') + _tooltip('Влево', -15) + '</div>' +
				'<div val="center" class="icon-wiki iw4 mr3' + _dn(BL.pos == 'center','on') + _tooltip('По центру', -28) + '</div>' +
				'<div val="r" class="icon-wiki iw5' + _dn(BL.pos == 'r','on') + _tooltip('Вправо', -34, 'r') + '</div>';
	},
	_elemUnitStyle = function(EL) {
		if(!EL.stl)
			return '';

		return '<table class="w100p mt10">' +
					'<tr><td>' + _elemUnitFont(EL) +
						'<td>' + _elemUnitColor(EL) +
						'<td class="r w75">' +
							'<input type="hidden" id="elem-size" class="w15" value="' + EL.size + '" />' +
				'</table>'
	},
	_elemUnitFont = function(EL) {//стили элемента: жирность, наклон, подчёркивание
		var font = {
			b:'',
			i:'',
			u:''
		};
		_forN(EL.font.split(' '), function(v) {
			if(!v)
				return;
			font[v] = ' on';
		});

		$(document)
			.off('click', '#elem-font div')
			.on('click', '#elem-font div', function() {
				var td = $(this),
					cls = td.hasClass('on'),
					v = td.attr('val'),
					font = [];
				td._dn(cls, 'on');

				$(EL.attr_el)._dn(cls, v);

				_forEq($('#elem-font .on'), function(eq) {
					font.push(eq.attr('val'));
				});
				EL.font = font.join(' ');

				//блок может отсутствовать у ячеек таблицы
				if(!EL.block_id)
					return;

				BLKK[EL.block_id].save = 1;
			});

		return '<div id="elem-font" class="dib">' +
			'<div val="b" class="icon-wiki ml3' + font.b + _tooltip('Жирный', -23) + '</div>' +
			'<div val="i" class="icon-wiki iw1 ml3' + font.i + _tooltip('Наклонный', -31) + '</div>' +
			'<div val="u" class="icon-wiki iw2 ml3' + font.u + _tooltip('Подчёкнутый', -39) + '</div>' +
		'</div>';
	},
	_elemUnitColor = function(EL) {//стили элемента: цвет текста
		var func = function(v) {
			$(EL.attr_el)
				.removeClass(EL.color)
				.addClass(v);

			EL.color = v;

			//блок может отсутствовать у ячеек таблицы
			if(!EL.block_id)
				return;

			BLKK[EL.block_id].save = 1;
		};

		return _color(EL.color, func);
	},
	_elemUnitImg = function(EL) {
		if(!EL.immg)
			return '';

		return '<table class="bs5">' +
			'<tr><td class="color-555 fs14">Ширина фото:' +
				'<td><input type="hidden" id="elem-img-width" class="w15" value="' + EL.width + '" />' +
				'<td class="pl5">' +
					'<input type="hidden" id="elem-img-height" value="' + EL.num_7 + '" />' +
			'<tr><td class="color-555 r" colspan="3">' +
					'Закруглённые углы: ' +
					'<input type="hidden" id="elem-img-circle" value="' + EL.num_8 + '" />' +
		'</table>'
	};

$(document)
	.on('click', '.block-grid-on', function() {//включение/выключение управления блоками
		var t = $(this),
			p = t.parent(),
			v = t.hasClass('grey'),
			spl = p.attr('val').split(':'),
			CONTENT = $('.block-content-' + spl[0]),
			BCO = p.find('.block-choose-on'),
			BCO_on = BCO.hasClass('orange') ? 1 : 0,
			send = {
				op:'block_grid_' + (v ? 'on' : 'off'),
				obj_name:spl[0],
				obj_id:spl[1],
				blk_choose:BCO_on,
				blk_sel:_cookie('block_ids_motion'),
				level:p.find('.block-level-change.orange').html() || 1,
				busy_obj:t
			};

		_post(send, function(res) {
			var BIM = _cookie('block_ids_motion');

			//удаление подсказки по переносу блоков, если блоки не были выбраны
			if(!BCO_on)
				$('._hint').remove();

			t._dn(v, 'grey');
			t._dn(!v, 'orange');
			p.find('.block-level-change')._dn(!v);
			p.find('.elem-width-change')._dn(!v);
			BCO._dn(!v).removeClass('_busy');

			CONTENT.html(res.html);

			//включена настройка корневых блоков
			if(v)
				$('#grid-stack')._grid({
					obj_name:res.obj_name,
					obj_id:res.obj_id,
					width:res.width
				});
			else
				for(var i in res.blk)
					BLKK[i] = res.blk[i];


			BCO.find('b').html(_ids(BIM, 1));
			BCO.attr('val', BIM);

			//если выбор блоков не включен, выход
			if(!BCO_on)
				return;

			//включен выбор блоков
			var bc = CONTENT.find('.blk-choose');

			//подсветка блока при выборе
			bc.click(function() {
				var tt = $(this),
					v = tt.attr('val'),
					sel = tt.hasClass('sel');


				//процесс переноса блоков
				if(BIM) {
					if(bc.hasClass('_busy'))
						return;

				    var op = 'block_choose_copy';
				    if(_num(_cookie('block_is_move')))
				        op = 'block_choose_move';

					var send = {
						op:op,
						parent_id:v,
						ids:BIM,
						busy_obj:tt
					};
					_post(send, function() {
						_cookie('block_ids_motion', '');
						t.removeClass('grey').trigger('click');
						t.removeClass('_busy');
						_msg();
					});
					return;
				}



				tt[(sel ? 'remove' : 'add') + 'Class']('sel');

				var seld = [];
				_forEq(bc, function(sp) {
					if(sp.hasClass('sel'))
						seld.push(sp.attr('val'));
				});
				BCO.find('b').html(seld.length);
				BCO.attr('val', seld.join());
			});
		});
	})
	.on('click', '.elem-width-change', function() {//включение/выключение изменения ширины элементов
		var t = $(this),
			p = t.parent(),
			on = t.hasClass('on') ? 0 : 1,
			spl = p.attr('val').split(':'),
			send = {
				op:'block_elem_width_change',
				obj_name:spl[0],
				obj_id:spl[1],
				on:on,
				busy_obj:t
			};

		_post(send, function(res) {
			$('._hint').remove();
			t._dn(!on, 'on');
			p.find('.block-grid-on')._dn(!on, 'vh');
			p.find('.block-level-change')._dn(!on, 'vh');
			p.find('.block-choose-on')._dn(!on, 'vh');
			$('.block-content-' + spl[0]).html(res.html);
			_forIn(res.elm, function(sp, k) {
				if(!on || !sp.width_min)
					return;
				$(ATTR_CMP(k) + '_edit' + (sp.afics ? sp.afics : '')).css('width', '100%');
				_attr_el(k)
					.addClass('ewc')
					.css('width', sp.width ? sp.width + 'px' : 'auto')
					.resizable({
						minWidth:sp.width_min,
						maxWidth:sp.width_max,
						grid:10,
						handles:'e',
						start:function() {
							$('._hint').remove();
						},
						stop:function(event, ui) {
							var el = ui.originalElement,
								p = el.parent(),
								send = {
									op:'block_elem_width_save',
									elem_id:k,
									width:ui.size.width,
									busy_obj:p
								};
							_post(send,	function() {
								ELMM[k].width = ui.size.width;
							});
						}
					});
			});
		});
	})
	.on('mouseenter', '.ewc .ui-resizable-e', function() {//подсказка с возможностью установить ширину 100% для элемента
		var t = $(this),
			div = t.parent(),
			block = div.parent(),
			block_id = _num(block.attr('id').split('_')[1]),
			BL = BLKK[block_id],
			EL = ELMM[BL.elem_id],
			val = EL.width ? 0 : 1,
			save = 0,
			save_v;

		if(div.hasClass('ui-resizable-resizing'))
			return;

		t._hint({
			msg:'<input type="hidden" id="elem-width-max" value="' + val + '" />' +
				'<div class="mt5 ml20 fs11 i pale">' +
					'<b class="i fs11">Размер элемента</b> будет ' +
					'<br>' +
					'подстраиваться под <b class="i fs11">размер блока</b>,' +
					'<br>' +
					'в котором он находится.' +
				'</div>',
			width:240,
			pad:10,
			delayShow:700,
			show:1,
			func:function() {
				$('#elem-width-max')._check({
					title:'максимальная ширина',
					func:function(v) {
						save = 1;
						save_v = v ? 0 : EL.width_max - 10;
						EL.width = save_v;
						div.width(v ? 'auto' : EL.width_max - 10);
					}
				});
			},
			funcBeforeHide:function() {
				if(!save)
					return false;

				var send = {
						op:'block_elem_width_save',
						elem_id:BL.elem_id,
						width:save_v,
						busy_obj:block
					};
				_post(send);
			}
		});
	})
	.on('click', '.block-level-change', function() {//изменения уровня редактирования блоков
		var t = $(this),
			v = _num(t.html()),
			p = t.parent(),
			but = p.find('.block-grid-on'),
			obj_name = p.attr('val').split(':')[0];

		p.find('.block-level-change')
			.removeClass('orange')
			.addClass('cancel');

		t.removeClass('cancel').addClass('orange');

		_cookie('block_level_' + obj_name, v);

		but.removeClass('grey').trigger('click');
	})

	.on('click', '.block-choose-on', function() {//включение выбора блоков
		var t = $(this),
			on = t.hasClass('orange') ? 0 : 1,
			p = t.parent(),
			but = p.find('.block-grid-on');

		t._dn(on, 'grey');
		t._dn(!on, 'orange');
		t.addClass('_busy');

		but._dn(!on, 'vh');
		p.find('.elem-width-change')._dn(!on, 'vh');

		but.removeClass('grey').trigger('click');
		but.removeClass('_busy');
	})
	.on('mouseenter', '.block-choose-on', function() {//выплывающая подсказка для действий с выбранными блоками
		/*
			используются cookie:
				block_ids_motion - IDS блоков, которые копируются или переносятся
				block_is_move - флаг переноса блоков, иначе копирование
		*/
		var t = $(this),
			c = _num(t.find('b').html()),
			ids = t.attr('val'),
			p = t.parent(),
			GRID_ON = p.find('.block-grid-on'),
			bcoMsg = function() {
				if(_cookie('block_ids_motion'))
					return bcoMotion();

				return '<table class="bs5">' +
					'<tr><td class="line-b pb3">' +
							'<button class="vk small w90 fl mr3 bco-copy">копировать</button>'+
							'<div class="grey fs11"><b class="fs11 color-555">Продублировать</b> выделенные блоки в указанном месте.<div>' +

					'<tr><td class="pb3">' +
							'<button class="vk small w90 fl mr3 red bco-move">вырезать</button>'+
							'<div class="grey fs11">Выделенные блоки будут <b class="fs11 color-555">перенесены</b> в указанное место.<div>' +
				'</table>';
			},
			bcoMotion = function() {//сообщение когда блоки выбраны для копирования
				var isMove = _num(_cookie('block_is_move'));
				return '<div class="b color-555 mar10 center">' +
							'Выбран' + _end(c, ['', 'о']) + ' ' + c + ' блок' + _end(c, ['', 'а', 'ов']) +
							'<br>' +
							'для ' + (isMove ? '<b class="red">переноса</b>' : 'копирования') +
					   '</div>' +
					'<div class="_info ml10 mr10">' +
						'Укажите <b>пустой блок</b> для вставки.' +
						'<div class="mt10">Либо <button class="vk small bco-paste-0 mtm3">вставьте блоки</button><br>в начальный уровень.</div>' +
					'</div>' +
					'<div class="mar10 center"><button class="vk small cancel bco-cancel">отменить выбор блоков</button></div>';
			};

		if(!c)
			return;
		if(t.hasClass('grey'))
			return;

		t._hint({
			width:210,
			msg:bcoMsg(),
			side:'right',
			ugPos:40,
			show:1,
			delayHide:300,
			func:function(o) {
				$(document)
					//блоки выбраны для копирования
					.off('click', '.bco-copy')
					 .on('click', '.bco-copy', function() {
						_cookie('block_ids_motion', ids);
						_cookie('block_is_move', 0);
						o.html(bcoMotion());
						GRID_ON.removeClass('grey').trigger('click');
						GRID_ON.removeClass('_busy');
					})

					//блоки выбраны для переноса
					.off('click', '.bco-move')
					 .on('click', '.bco-move', function() {
						_cookie('block_ids_motion', ids);
						_cookie('block_is_move', 1);
						o.html(bcoMotion());
						GRID_ON.removeClass('grey').trigger('click');
						GRID_ON.removeClass('_busy');
					})

					//отмена выбранных блоков
					.off('click', '.bco-cancel')
					 .on('click', '.bco-cancel', function() {
						_cookie('block_ids_motion', '');
						o.html(bcoMsg());
						$('._hint').remove();
						GRID_ON.removeClass('grey').trigger('click');
						GRID_ON.removeClass('_busy');
					})

					//вставка на нулевой уровень
					.off('click', '.bco-paste-0')
					 .on('click', '.bco-paste-0', function() {
					 	var op = 'block_choose_paste_0_copy';
					 	if(_num(_cookie('block_is_move')))
					 		op = 'block_choose_paste_0_move';
					 	var tt = $(this),
							send = {
								op:op,
								obj_name:p.attr('val').split(':')[0],
								obj_id:p.attr('val').split(':')[1],
								ids:_cookie('block_ids_motion'),
								busy_obj:tt
							};
						_post(send, function(res) {
							GRID_ON.removeClass('grey').trigger('click');
							GRID_ON.removeClass('_busy');
							_cookie('block_ids_motion', '');
							for(var i in res.blk)
								BLKK[i] = res.blk[i];
							for(var i in res.elm)
								ELMM[i] = res.elm[i];
						});
					});
			}
		});
	})

	.on('mouseenter', '.block-unit', _blockUnitSetup)
	.on('click', '.block-unit', function() {//нажатие на блок для настройки

		//если производится процесс деления блока на части, действие не производится
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLKK[block_id];

		//если есть подблоки, действие не производится
		if(BL.child_count)
			return;

		if(BL.elem_id)
			return $('#elem-hint-' + BL.elem_id + ' .icon-edit').trigger('click');

		$('#elem-hint-add').trigger('click');
	})
	.on('click', '.block-click-page', function(e) {//нажатие на блок для перехода на страницу
		e.stopPropagation();

		var t = $(this),
			cls = t.attr('class').split(' ');
		_forN(cls, function(sp) {
			if(sp.substr(0, 3) != 'pg-')
				return;

			var ex = sp.split('-'),
				page_id = _num(ex[1]);
			if(!page_id)
				return false;

			var unit = t.parents('.sp-unit'),
				id = _num(unit.attr('val')),
				link = '&p=' + page_id;

			if(id)
				link += '&id=' + id;

			location.href = URL + link;
			return false;
		});
	});

$.fn._grid = function(o) {
	var t = $(this);

	o = $.extend({
		width:1000,
		parent_id:0,//родительский блок
		obj_name:'page',//имя объекта, где располагаются блоки
		obj_id:PAGE_ID  //id объекта
	}, o);

	t.gridstack({
		draggable:{grid:[10,1]},
		resizable:{grid:[10,10]},
		itemClass:'grid-item',
		handle:'.grid-content', //область, за которую можно перетаскивать
		animate:false,          //плавная пристыковка после отпускания при растягивании
		verticalMargin:1,       //отступ сверху
		cellHeight:10,          //минимальная высота блока
		float:false,            //если true - блок можно расположить в любом месте, иначе блок всегда тянется к верху
		width:o.width / 10      //количество элементов минимальной ширины может поместиться по всей длине
	});

	//включение перетаскивания линейки
	$('#grid-line').draggable({axis:'y',grid:[10,10]});

	var grid = t.data('gridstack'),
		num = 1;
	//добавление нового блока
	$('#grid-add').click(function() {
		grid.addWidget($('<div id="gn' + num++ + '">' +
	        '<div class="grid-info">' + o.width + '</div>' +
	        '<div class="grid-edge"></div>' +
	        '<div class="grid-edge er"></div>' +
			'<div class="grid-content"></div>' +
			'<div class="grid-del">x</div>' +
			'</div>'),
			0, 0, o.width, 3, true);
	});

	//сохранение данных
	$('#grid-save').click(function() {
		var t = $(this),
			arr = [];
		_forEq($('.grid-item'), function(eq) {
			arr.push(
				(eq.attr('id') ? _num(eq.attr('id').split('_')[1]) : 0) + ',' +
				eq.attr('data-gs-x') + ',' +
				eq.attr('data-gs-y') + ',' +
				eq.attr('data-gs-width') + ',' +
				eq.attr('data-gs-height')
			);
		});

		var send = {
			op:'block_grid_save',
			parent_id:o.parent_id,
			obj_name:o.obj_name,
			obj_id:o.obj_id,
			arr:arr,
			busy_obj:t
		};
		_post(send, function(res) {
			$('#block-level-' + o.obj_name).after(res.level).remove();
			$('.block-content-' + o.obj_name).html(res.html);
			for(var i in res.blk)
				BLKK[i] = res.blk[i];
			for(var i in res.elm)
				ELMM[i] = res.elm[i];
		});
	});
	$('#grid-cancel').click(function() {
		$('#block-level-' + o.obj_name)
			.find('.block-grid-on')
			.removeClass('grey')
			.trigger('click');
	});

	t.on('gsresizestop', function(event, elem) {//действие после остановки изменения размера блока
		var h = _num($(elem).attr('data-gs-height')),
			y = $(elem).attr('data-gs-y'),
			attr_id = $(elem).attr('id');
		_forEq($('.grid-item'), function(eq) {
			if(eq.attr('data-gs-y') != y)
				return;
			if(eq.attr('id') == attr_id)
				return;
			grid.resize(eq, null, h);
		});
	})
	 .on('drag resize', function(e) {//действие во время перетаскивания блока
		var item = $(e.target),
			offset = item.offset(),
			info = item.find('.grid-info'),
			WH = $(window).height(),//высота экрана видимой области
			scrollTop = $(window).scrollTop(),
			cr = 50,//отступ линии сверху и снизу
			сrt = scrollTop > cr ? cr : scrollTop;//корректировка при скролле
		info.html(item.width());
		item.find('.grid-edge').css({
			height:WH - 100 + сrt,
			top:-offset.top + scrollTop + cr - сrt
		});
	 })
	 .on('dragstop', function(event) {//действие после остановки перетаскивания блока
		 var elem = $(event.target),
			 h = _num(elem.attr('data-gs-height')),
			 h_new = 0,
			 y = -1,
			 attr_id = elem.attr('id');
		 _forEq($('.grid-item'), function(eq) {
			 if(!eq.attr('id')) {
				 y = eq.attr('data-gs-y');
				 return false;
			 }
		 });
		 if(y < 0)
			 return;
		 _forEq($('.grid-item'), function(eq) {
			 if(!eq.attr('id'))
				 return;
			 if(attr_id == eq.attr('id'))
				 return;
			 if(y != eq.attr('data-gs-y'))
				 return;
			 if(h == eq.attr('data-gs-height'))
				 return;
			 h_new = _num(eq.attr('data-gs-height'));
		 });
		 if(!h_new)
			 return;
		 grid.resize(elem, null, h_new);
	 });

	$(document)
		.off('click', '.grid-del')
		.on('click', '.grid-del', function() {
			var t = $(this),
				p = t.parent();
			grid.removeWidget(p);
		});
};

