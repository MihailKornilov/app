var _blockUnitSetup = function() {//настройка стилей блока в выплывающем окне
		if(!window.BLOCK_ARR)//страница ещё не догрузилась
			return;

		//если производится процесс деления блока на части, настройка стилей не выводится
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLOCK_ARR[block_id],
			obj = $('#bl_' + BL.id);

		//идёт процес сохранения
		if(BL.save || obj.hasClass('_busy'))
			return;

		t._hint({
			msg:'<div class="mar10">' +
					'<div class="hd2 mb10">Настройки блока</div>' +
					_blockUnitBg(BL) +
					_blockUnitBor(BL) +
					_blockUnitBut(BL) +
				'</div>' +
				_elemUnit(BL),
			width:240,
			objPos:'mouse',
			show:1,
			delayHide:300,
			func:function() {
				$('#block-unit-bor0')._check({
					tooltip:'сверху',
					func:function(v) {
						obj.css('border-top', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor1')._check({
					tooltip:'справа',
					func:function(v) {
						obj.css('border-right', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor2')._check({
					tooltip:'снизу',
					func:function(v) {
						obj.css('border-bottom', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor3')._check({
					tooltip:'слева',
					func:function(v) {
						obj.css('border-left', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#but-elem-add').click(function() {
					_blockUnitElAdd(BL);
				});
				if(BL.elem_id) {
/*
					$('#elem-edit').click(function() {
						$(this).addClass('spin');
						return;
						$('._hint').remove();
						if(BL.obj_name == 'spisok')
							return _blockSpisokUnitElAdd(BL);
						_dialogOpen(BL.dialog_id, BL.elem_id);
					});
*/
					$('#elem-del').click(function() {
						$('._hint').remove();
						var func = BL.obj_name != 'spisok' ? false : function() {
							$('#block-level-spisok').find('.block-grid-on').removeClass('grey').trigger('click');
						};
						_dialogOpen(6, BL.elem_id, 0, func);
					});
					var tMar = {
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
								$('#pe_' + BL.elem_id)
									.css({margin:
										top + (top ? 'px' : '') + ' ' +
										right + (right ? 'px' : '') + ' ' +
										bottom + (bottom ? 'px' : '') + ' ' +
										left + (left ? 'px' : '')
									});

								BL.mar = top + ' ' + right + ' ' + bottom + ' ' + left;
								BL.save = 1;
							}
						});
					$('#elem-size')._count({
						min:10,
						max:18,
						func:function(v) {
							$('#pe_' + BL.elem_id)
								.removeClass('fs' + BL.size)
								.addClass('fs' + v);
							BL.size = v;
							BL.save = 1;
						}
					});

				}
			},
			funcBeforeHide:function() {
				_blockUnitSave(BL, obj);
			}
		});
	},
	_blockUnitBg = function(BL) {//заливка блока
		$(document)
			.off('click', '#block-set-bg div')
			.on('click', '#block-set-bg div', function() {
				var unit = $(this),
					bg = unit.attr('val'),
					sel = unit.hasClass('sel');

				unit.parent().find('.sel').removeClass('sel');
				$('#bl_' + BL.id).removeClass('bg-fff bg-gr1 bg-gr2 bg-gr3 bg-ffe');

				if(!sel) {
					unit.addClass('sel');
					$('#bl_' + BL.id).addClass(bg);
				}

				BL.bg = sel ? '' : bg;
				BL.save = 1;
			});

		return '<table>' +
			'<tr><td class="color-555 fs14 pr5">Заливка:' +
				'<td><div id="block-set-bg">' +
						'<div class="' + (BL.bg == 'bg-fff' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP     bg-fff" val="bg-fff"></div>' +
						'<div class="' + (BL.bg == 'bg-gr1' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr1" val="bg-gr1"></div>' +
						'<div class="' + (BL.bg == 'bg-gr3' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr3" val="bg-gr3"></div>' +
						'<div class="' + (BL.bg == 'bg-gr2' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr2" val="bg-gr2"></div>' +
						'<div class="' + (BL.bg == 'bg-ffe' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-ffe" val="bg-ffe"></div>' +
					'</div>' +
			'</table>';
	},
	_blockUnitBor = function(BL) {//границы блока
		var bor = BL.bor.split(' ');
		return '<table class="mt10">' +
				'<tr><td class="color-555 fs14 pr5">Границы:' +
				'<td>' +
					'<div class="ml20 pl5"><input type="hidden" id="block-unit-bor0" value="' + bor[0] + '"></div>' +
					'<table class="bs5">' +
						'<tr>' +
							'<td><input type="hidden" id="block-unit-bor3" value="' + bor[3] + '">' +
							'<td class="pl20"><input type="hidden" id="block-unit-bor1" value="' + bor[1] + '">' +
					'</table>' +
					'<div class="ml20 pl5"><input type="hidden" id="block-unit-bor2" value="' + bor[2] + '"></div>' +
			'</table>';
	},
	_blockUnitBut = function(BL) {//кнопки
		if(BL.elem_id)
			return '';

		return '<div class="mt20 center">' +
					'<button id="but-block-grid" class="vk small orange mb5" onclick="_blockUnitGrid(' + BL.id + ')">Настроить подблоки</button>' +
	   (!BL.child ? '<button id="but-elem-add" class="vk small green">Вставить элемент</button>' : '') +
				'</div>';
	},
	_blockUnitElAdd = function(BL) {//добавление нового элемента в блок
		$('._hint').remove();

		if(BL.obj_name == 'spisok')
			return _blockSpisokUnitElAdd(BL);

		var html =
				'<div class="center pad20">' +
					'<p><button class="vk" val="3">Меню</button>' +
					'<p class="mt10"><button class="vk" val="4">Заголовок</button>' +
					'<p class="mt10"><button class="vk" val="7">Поиск</button>' +
					'<p class="mt10"><button class="vk grey dialog-button" val="10">Произвольный текст</button>' +
					'<p class="mt10"><button class="vk" val="11">Данные объекта</button>' +
					'<p class="mt10"><button class="vk green" val="2">Кнопка</button>' +
					'<p class="mt10"><button class="vk" val="9">Ссылка</button>' +
					'<p class="mt10"><button class="vk" val="15">Список: количество строк</button>' +
					'<p class="mt10"><button class="vk" val="14">Список: содержание</button>' +
			  (SA ? '<p class="mt10"><button class="vk red" val="12">SA: из функции</button>' : '') +
				'</div>',
			dialog = _dialog({
				width:450,
				top:20,
				head:'Вставка элемента в блок',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		dialog.content.find('button').click(function() {
			var v = $(this).attr('val');
			window.BLOCK_ID = BL.id;
			dialog.close();
		});
	},
	_blockUnitGrid = function(block_id) {
		var send = {
				op:'block_unit_gird',
				id:block_id
			},
			but = $('#but-block-grid');

		but.addClass('_busy');

		_post(send, function(res) {
				$('._hint').remove();
				$('.block-content-' + res.block.obj_name).html(res.html);
				$('#grid-stack')._grid({
					width:res.block.width,
					parent_id:block_id,
					obj_name:res.block.obj_name,
					obj_id:res.block.obj_id
				});
			}, function() {
				but.removeClass('_busy');
			});
	},
	_blockUnitSave = function(BL, obj) {
		if(!BL.save)
			return;

		BL.bor = $('#block-unit-bor0').val() + ' ' +
				 $('#block-unit-bor1').val() + ' ' +
				 $('#block-unit-bor2').val() + ' ' +
				 $('#block-unit-bor3').val();

		BL.op = 'block_unit_style_save';
		obj.addClass('_busy');
		_post(BL, function() {
			obj.removeClass('_busy');
			BL.save = 0;
		});
	},

	_elemUnit = function(EL) {//настройки элемента в выплывающем окне
		if(!EL.elem_id)
			return '';

		return '<div class="mar5 pad5 bor-e8 bg-gr1">' +
				'<div class="hd2 ">' +
					'Настройки элемента' +
					'<div class="fr">' +
						'<div val="' + EL.dialog_id + '" class="icon icon-edit dialog-icon mr3' + _tooltip('Редактировать элемент', -134, 'r') + '</div>' +//id="elem-edit"
						'<div id="elem-del" class="icon icon-del-red' + _tooltip('Удалить элемент', -95, 'r') + '</div>' +
					'</div>' +
				'</div>' +

				'<table class="w100p mt5">' +
					'<tr><td>' + _elemUnitMar(EL) +
						'<td>' + _elemUnitPlace(EL) +
				'</table>' +

			(EL.fontAllow ?
				'<table class="w100p mt10">' +
					'<tr><td>' + _elemUnitFont(EL) +
						'<td>' + _elemUnitColor(EL) +
						'<td class="r w75">' +
							'<input id="elem-size" class="w15" value="' + EL.size + '" />' +
				'</table>'
			: '') +
		'</div>';
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
	_elemUnitPlace = function(EL) {//стили элемента: тип блока, позиция элемента
		$(document)
			.off('click', '#elem-pos div')
			.on('click', '#elem-pos div', function() {
				var unit = $(this),
					v = unit.attr('val'),
					bl = $('#bl_' + EL.id);

				_parent(unit, 'TABLE').find('.on').removeClass('on');
				unit.addClass('on');

				bl.removeClass('top r center bottom');
				if(v)
					bl.addClass(v);

				EL.pos = v;
				EL.save = 1;
			});
		return  '<table id="elem-pos">' +
			'<tr><td class="fs14 color-555 pb3 center">Позиция' +
			'<tr><td>' +
					'<div val="top" class="icon-wiki iw6 mr3' + _dn(EL.pos == 'top','on') + _tooltip('Вверх-влево', -37) + '</div>' +
					'<div val="top center" class="icon-wiki iw7 mr3' + _dn(EL.pos == 'top center','on') + _tooltip('Вверх-центр', -35) + '</div>' +
					'<div val="top r" class="icon-wiki iw8' + _dn(EL.pos == 'top r','on') + _tooltip('Вверх-вправо', -73, 'r') + '</div>' +
			'<tr><td>' +
					'<div val="" class="icon-wiki iw3 mr3' + _dn(!EL.pos,'on') + _tooltip('Влево', -15) + '</div>' +
					'<div val="center" class="icon-wiki iw4 mr3' + _dn(EL.pos == 'center','on') + _tooltip('По центру', -28) + '</div>' +
					'<div val="r" class="icon-wiki iw5' + _dn(EL.pos == 'r','on') + _tooltip('Вправо', -34, 'r') + '</div>' +
			'<tr><td>' +
					'<div val="bottom" class="icon-wiki iw9 mr3' + _dn(EL.pos == 'bottom','on') + _tooltip('Вниз-влево', -33) + '</div>' +
					'<div val="bottom center" class="icon-wiki iw10 mr3' + _dn(EL.pos == 'bottom center','on') + _tooltip('Вниз-центр', -32) + '</div>' +
					'<div val="bottom r" class="icon-wiki iw11' + _dn(EL.pos == 'bottom r','on') + _tooltip('Вниз-вправо', -65, 'r') + '</div>' +
		'</table>';
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

				$('#pe_' + EL.elem_id)._dn(cls, v);

				_forEq($('#elem-font .on'), function(eq) {
					font.push(eq.attr('val'));
				});
				EL.font = font.join(' ');
				EL.save = 1;
			});


		return '<div id="elem-font" class="dib">' +
				'<div class="icon-wiki mr3' + font.b + '" val="b"></div>' +
				'<div class="icon-wiki iw1 mr3' + font.i + '" val="i"></div>' +
				'<div class="icon-wiki iw2 mr5' + font.u + '" val="u"></div>' +
		'</div>';
	},
	_elemUnitColor = function(EL) {//стили элемента: цвет текста
		$(document)
			.off('mouseenter', '#elem-color td')
			.on('mouseenter', '#elem-color td', function() {
				var td = $(this),
					v = td.attr('val');
				td._tooltip(ELEM_COLOR[v][1]);
			})
			.off('click', '#elem-color td')
			.on('click', '#elem-color td', function() {
				var td = $(this),
					v = td.attr('val');

				$('#elem-color td').css('color', 'transparent');
				td.css('color', '#fff');

				$('#pe_' + EL.elem_id)
					.removeClass(EL.color)
					.addClass(v);

				$('#elem-color').css('background-color', ELEM_COLOR[v][0]);

				EL.color = v;
				EL.save = 1;
			});

		var td = '',
			n = 0;
		for(var i in ELEM_COLOR) {
			var bg = ELEM_COLOR[i][0],
				sel = i == EL.color ? '#fff' : 'transparent';
			if(!n || n == 7)
				td += '<tr>';
			td += '<td class="pad5 center" style="background-color:' + bg + ';color:' + sel + '" val="' + i + '">&#10004;';
			n++;
		}

		return '<div id="elem-color" class="bor-e8 prel mtm2"  style="background-color:' + ELEM_COLOR[EL.color][0] + '">' +
			 '<table class="w200 bg-eee curP pabs">' + td + '</table>' +
			'</div>';
	};

$(document)
	.on('click', '.block-grid-on', function() {//включение/выключение управления блоками
		var t = $(this),
			p = t.parent(),
			v = t.hasClass('grey'),
			spl = t.attr('val').split(':'),
			send = {
				op:'block_grid_' + (v ? 'on' : 'off'),
				obj_name:spl[0],
				obj_id:spl[1],
				width:spl[2]
			};

		t._dn(v, 'grey');
		t._dn(!v, 'orange');
		p.find('.block-level-change')._dn(!v);

		_post(send, function(res) {
			$('.block-content-' + spl[0]).html(res.html);
			if(v)
				$('#grid-stack')._grid({
					obj_name:spl[0],
					obj_id:spl[1],
					width:spl[2]
				});
		});

	})
	.on('click', '.block-level-change', function() {//изменения уровня редактирования блоков
		var t = $(this),
			v = _num(t.html()),
			p = t.parent(),
			but = p.find('.block-grid-on'),
			obj_name = but.attr('val').split(':')[0];

		p.find('.block-level-change')
			.removeClass('orange')
			.addClass('cancel');

		t.removeClass('cancel').addClass('orange');

		_cookie('block_level_' + obj_name, v);

		but.removeClass('grey').trigger('click');
	})
	.on('mouseenter', '.block-unit', _blockUnitSetup);

$.fn._grid = function(o) {
	var t = $(this);

	o = $.extend({
		width:1000,
		parent_id:0,//родительский блок
		obj_name:'page',//имя объекта, где располагаются блоки
		obj_id:PAGE_ID  //id объекта
	}, o);

	t.gridstack({
		itemClass:'grid-item',
		handle:'.grid-content',  //область, за которую можно перетаскивать
		animate:true,           //плавная пристыковка после отпускания при растягивании
		verticalMargin:1,       //отступ сверху
		cellHeight:10,          //минимальная высота блока
		float:false,            //если true - блок можно расположить в любом месте, иначе блок всегда тянется к верху
		width:o.width / 10      //количество элементов минимальной ширины может поместиться по всей длине
	});

	var grid = t.data('gridstack'),
		num = 1;
	//добавление нового блока
	$('#grid-add').click(function() {
		grid.addWidget($('<div id="gn' + num++ + '">' +
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

		t.addClass('_busy');
		var send = {
			op:'block_grid_save',
			parent_id:o.parent_id,
			obj_name:o.obj_name,
			obj_id:o.obj_id,
			width:o.width,
			arr:arr
		};
		_post(send, afterSave, function() {
			t.removeClass('_busy');
		});
	});
	$('#grid-cancel').click(cancel);

	t.on('gsresizestop', function(event, elem) {
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
	 .on('dragstop', function(event) {
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

	//сохранение блоков
	function afterSave(res) {
		$('#block-level-' + o.obj_name).after(res.level).remove();
		$('.block-content-' + o.obj_name).html(res.html);
		for(var k in res.block_arr)
			BLOCK_ARR[k] = res.block_arr[k];
	}
	//отмена редактирования
	function cancel() {
		$('#block-level-' + o.obj_name)
			.find('.block-grid-on')
			.removeClass('grey')
			.trigger('click');
	}
};

