var _blockUpd = function(blk) {//���������� ���������� ����������, ���������� �����
		for(var k in blk)
			BLK[k] = blk[k];
	},
	_blockUnitSetup = function() {//��������� ������ ����� � ����������� ����
		if(!window.BLK)//�������� ��� �� �����������
			return;

		//���� ������������ ������� ������� ����� �� �����, ��������� ������ �� ���������
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLK[block_id],
			obj = $(BL.attr_bl),
			borSave = function() {//������� �� ������� ��� ���������/������ �������
				BL.bor = $('#block-unit-bor0').val() + ' ' +
						 $('#block-unit-bor1').val() + ' ' +
						 $('#block-unit-bor2').val() + ' ' +
						 $('#block-unit-bor3').val();
				BL.save = 1;
			};

		//��� ������ ����������
		if(BL.save || obj.hasClass('_busy'))
			return;

		t._hint({
			msg:'<div class="pad5">' +
					'<div class="hd2">����</div>' +
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
					tooltip:'������',
					func:function(v) {
						obj.css('border-top', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor1')._check({
					tooltip:'������',
					func:function(v) {
						obj.css('border-right', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor2')._check({
					tooltip:'�����',
					func:function(v) {
						obj.css('border-bottom', v ? '#DEE3EF solid 1px' : '');
						borSave();
					}
				});
				$('#block-unit-bor3')._check({
					tooltip:'�����',
					func:function(v) {
						obj.css('border-left', v ? '#DEE3EF solid 1px' : '');
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

				if(BL.elem_id) {
					var EL = ELM[BL.elem_id],
						tMar = {
							0:'������',
							1:'������',
							2:'�����',
							3:'�����'
						};
					for(var n = 0; n < 4; n++)
						$('#el-mar' + n)._count({
							step:5,
							max:50,
							tooltip:'������ ' + tMar[n],
							func:function(v, id) {
								var pos = _num(id.split('el-mar')[1]),
									top =    pos == 0 ? v : _num($('#el-mar0').val()),
									right =  pos == 1 ? v : _num($('#el-mar1').val()),
									bottom = pos == 2 ? v : _num($('#el-mar2').val()),
									left =   pos == 3 ? v : _num($('#el-mar3').val());
								$(EL.attr_el)
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
							$(EL.attr_el)
								.removeClass('fs' + EL.size)
								.addClass('fs' + v);
							EL.size = v;
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
	_blockUnitBg = function(BL) {//������� �����
		var BGS = 'bg-fff bg-gr3 bg-ffe bg-efe bg-gr2 bg-fee',
			div = '';

		$(document)
			.off('click', '#block-set-bg div')
			.on('click', '#block-set-bg div', function() {
				var unit = $(this),
					bg = unit.attr('val'),
					sel = unit.hasClass('sel');

				unit.parent().find('.sel').removeClass('sel');
				$(BL.attr_bl).removeClass(BGS);

				if(!sel) {
					unit.addClass('sel');
					$(BL.attr_bl).addClass(bg);
				}

				BL.bg = sel ? '' : bg;
				BL.save = 1;
			});

		_forN(BGS.split(' '), function(sp, n) {
			var sel = BL.bg == sp ? ' sel' : '',
				ml3 = n ? ' ml3' : '';
			div += '<div class="dib center h25 w25 bor-e8 curP fs17 grey ' + sp + ml3 + sel + '" val="' + sp + '">&#10004;</div>';
		});

		return '<div class="color-555 fs14 mt5">�������:</div>' +
			   '<div id="block-set-bg" class="mt3">' + div + '</div>';
	},
	_blockUnitBor = function(BL) {//������� �����
		var bor = BL.bor.split(' ');
		return '<table class="mt10">' +
				'<tr><td class="color-555 fs14">�������:' +
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
	_blockUnitSa = function(BL) {//��������� ����� ��� SA
		if(!SA)
			return '';

		return '<td class="bg-ffc bor-f0 pl5 pr3">' +
			'<input type="hidden" id="block-width-auto" value="' + BL.width_auto + '" />' +
			'<div class="mt8">' +
				'<input type="hidden" id="block-sa-view" value="' + BL.sa + '" />' +
			'</div>';
	},
	_blockUnitBut = function(BL) {//������
		if(BL.elem_id)
			return '';

		return '<div class="mt20 center">' +
					'<button class="vk small orange mb5" onclick="_blockUnitGrid($(this),' + BL.id + ')">��������� ��������</button>' +
 (!BL.child_count ? '<button class="dialog-open vk small green" id="elem-hint-add" val="dialog_id:50,block_id:' + BL.id + '">�������� �������</button>' : '') +
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
			$('.block-content-' + res.block.obj_name).html(res.html);
			$('#grid-stack')._grid({
				width:res.block.width,
				parent_id:block_id,
				obj_name:res.block.obj_name,
				obj_id:res.block.obj_id
			});
		});
	},
	_blockUnitSave = function(BL, obj) {
		if(!BL.save)
			return;

		BL.op = 'block_unit_style_save';
		BL.elem = ELM[BL.elem_id];
		BL.busy_obj = obj;
		_post(BL, function() {
			BL.save = 0;
		});
	},

	_elemUpd = function(elm) {//���������� ���������� ����������, ����������
		for(var k in elm)
			ELM[k] = elm[k];
	},
	_elemUnit = function(BL) {//��������� �������� � ����������� ����
		if(!BL.elem_id)
			return '';

		var EL = ELM[BL.elem_id];

		return '<div class="mar5 pad5 bor-e8 bg-gr1" id="elem-hint-' + EL.id + '">' +
				'<div class="fs15 blue line-b">' +
					'�������' +
					'<div class="fr mtm3">' +
						'<div val="dialog_id:64,unit_id:' + EL.id + '" class="icon icon-eye ml3 dialog-open pl' + _tooltip('������� �����������', -67) + '</div>' +
						'<div val="dialog_id:' + EL.dialog_func + ',block_id:' + BL.id + '" class="icon icon-usd ml3 dialog-open' + _dn(EL.dialog_func) + _dn(!EL.is_func, 'pl') + _tooltip('��������� ��������', -62) + '</div>' +
						'<div val="dialog_id:43,unit_id:' + EL.id + '" class="icon icon-hint ml3 curP dialog-open' + _dn(!EL.hint_on, 'pl') + _dn(EL.hint_access) + _tooltip('��������� ���������', -65) + '</div>' +
						'<div val="dialog_id:' + EL.dialog_id + ',unit_id:' + EL.id + '" class="icon icon-edit dialog-open ml3' + _tooltip('������������� �������', -134, 'r') + '</div>' +
						'<div val="dialog_id:' + EL.dialog_id + ',unit_id:' + EL.id + ',del:1" class="icon icon-del-red dialog-open ml3' + _tooltip('������� �������', -94, 'r') + '</div>' +
					'</div>' +
				'</div>' +

				'<table class="w100p mt5">' +
					'<tr><td>' + _elemUnitMar(EL) +
						'<td>' + _elemUnitPlace(BL) +
				'</table>' +

			(EL.style_access ?
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
	_elemUnitPlace = function(BL) {//������� ��������
		return  '<table id="elem-pos">' +
			'<tr><td class="fs14 color-555 pb3 center">�������' +
			'<tr><td><div val="top" class="icon-wiki iw6 mr3' + _dn(BL.pos == 'top','on') + _tooltip('�����-�����', -37) + '</div>' +
					'<div val="top center" class="icon-wiki iw7 mr3' + _dn(BL.pos == 'top center','on') + _tooltip('�����-�����', -35) + '</div>' +
					'<div val="top r" class="icon-wiki iw8' + _dn(BL.pos == 'top r','on') + _tooltip('�����-������', -73, 'r') + '</div>' +
			'<tr><td>' + _elemUnitPlaceMiddle(BL) +
			'<tr><td><div val="bottom" class="icon-wiki iw9 mr3' + _dn(BL.pos == 'bottom','on') + _tooltip('����-�����', -33) + '</div>' +
					'<div val="bottom center" class="icon-wiki iw10 mr3' + _dn(BL.pos == 'bottom center','on') + _tooltip('����-�����', -32) + '</div>' +
					'<div val="bottom r" class="icon-wiki iw11' + _dn(BL.pos == 'bottom r','on') + _tooltip('����-������', -65, 'r') + '</div>' +
		'</table>';
	},
	_elemUnitPlaceMiddle = function(BL) {//����������� ����� �������
		$(document)
			.off('click', '#elem-pos div')
			.on('click', '#elem-pos div', function() {
				var unit = $(this),
					v = unit.attr('val');

				_parent(unit, 'TABLE').find('.on').removeClass('on');
				unit.addClass('on');

				$(BL.attr_bl).removeClass('top r center bottom');
				if(v)
					$(BL.attr_bl).addClass(v);

				BL.pos = v;
				BL.save = 1;
			});
		return  '<div val="" class="icon-wiki iw3 mr3' + _dn(!BL.pos,'on') + _tooltip('�����', -15) + '</div>' +
				'<div val="center" class="icon-wiki iw4 mr3' + _dn(BL.pos == 'center','on') + _tooltip('�� ������', -28) + '</div>' +
				'<div val="r" class="icon-wiki iw5' + _dn(BL.pos == 'r','on') + _tooltip('������', -34, 'r') + '</div>';
	},
	_elemUnitFont = function(EL) {//����� ��������: ��������, ������, �������������
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

				if(EL.attr_tr)//��������� ��� �������, ��� ������
					return;

				BLK[EL.block_id].save = 1;
			})
			.off('click', '.elem-url')
			.on('click', '.elem-url', function() {
				var t = $(this),
					v = t.hasClass('on') ? 0 : 1;
				t._dn(!v, 'on');
				EL.url = v;

				if(EL.attr_tr)//��������� ��� �������, ��� ������
					return;

				BLK[EL.block_id].save = 1;
			});
		return '<div id="elem-font" class="dib">' +
			'<div val="b" class="icon-wiki ml3' + font.b + _tooltip('������', -23) + '</div>' +
			'<div val="i" class="icon-wiki iw1 ml3' + font.i + _tooltip('���������', -31) + '</div>' +
			'<div val="u" class="icon-wiki iw2 ml3' + font.u + _tooltip('�����������', -39) + '</div>' +
		'</div>' +
		(EL.url_access ?
			'<div class="elem-url icon-wiki iw12 ml3' + _dn(EL.url, 'on') + _tooltip('������', -20) + '</div>'
		: '');
	},
	_elemUnitColor = function(EL) {//����� ��������: ���� ������
		var func = function(v) {
			$(EL.attr_el)
				.removeClass(EL.color)
				.addClass(v);

			EL.color = v;

			if(EL.attr_tr)//��������� ��� �������, ��� ������
				return;

			BLK[EL.block_id].save = 1;
		};

		return _color(EL.color, func);
	};

$(document)
	.on('click', '.block-grid-on', function() {//���������/���������� ���������� �������
		var t = $(this),
			p = t.parent(),
			v = t.hasClass('grey'),
			spl = p.attr('val').split(':'),
			send = {
				op:'block_grid_' + (v ? 'on' : 'off'),
				obj_name:spl[0],
				obj_id:spl[1],
				width:spl[2],
				busy_obj:t
			};
		_post(send, function(res) {
			t._dn(v, 'grey');
			t._dn(!v, 'orange');
			p.find('.block-level-change')._dn(!v);
			p.find('.elem-width-change')._dn(!v);

			$('.block-content-' + spl[0]).html(res.html);

			if(!v) {
//				_elemActivate(res.elm, {}, 1);
				_blockUpd(res.blk);
				_elemUpd(res.elm);
			}
			if(v) {
				$('._hint').remove();
				$('#grid-stack')._grid({
					obj_name:spl[0],
					obj_id:spl[1],
					width:spl[2]
				});
			}
		});
	})
	.on('click', '.elem-width-change', function() {//���������/���������� ��������� ������ ���������
		var t = $(this),
			p = t.parent(),
			on = t.hasClass('grey') ? 1 : 0,
			spl = p.attr('val').split(':'),
			send = {
				op:'block_elem_width_change',
				obj_name:spl[0],
				obj_id:spl[1],
				width:spl[2],
				on:on,
				busy_obj:t
			};

		_post(send, function(res) {
			$('._hint').remove();
			t._dn(on, 'grey');
			t._dn(!on, 'orange');
			p.find('.block-grid-on')._dn(!on, 'vh');
			p.find('.block-level-change')._dn(!on, 'vh');
			$('.block-content-' + spl[0]).html(res.html);
			_forIn(res.elm, function(sp, k) {
				if(!on || !sp.width_min)
					return;
				$(sp.attr_cmp + '_edit' + sp.afics).css('width', '100%');
				$(sp.attr_el)
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
								ELM[k].width = ui.size.width;
							});
						}
					});
			});
		});
	})
	.on('mouseenter', '.ewc .ui-resizable-e', function() {//��������� � ������������ ���������� ������ 100% ��� ��������
		var t = $(this),
			div = t.parent(),
			block = div.parent(),
			block_id = _num(block.attr('id').split('_')[1]),
			BL = BLK[block_id],
			EL = ELM[BL.elem_id],
			val = EL.width ? 0 : 1,
			save = 0,
			save_v;

		if(div.hasClass('ui-resizable-resizing'))
			return;

		t._hint({
			msg:'<input type="hidden" id="elem-width-max" value="' + val + '" />' +
				'<div class="mt5 ml20 fs11 i pale">' +
					'<b class="i fs11">������ ��������</b> ����� ' +
					'<br>' +
					'�������������� ��� <b class="i fs11">������ �����</b>,' +
					'<br>' +
					'� ������� �� ���������.' +
				'</div>',
			width:240,
			pad:10,
			delayShow:700,
			show:1,
			func:function() {
				$('#elem-width-max')._check({
					title:'������������ ������',
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
	.on('click', '.block-level-change', function() {//��������� ������ �������������� ������
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
	.on('mouseenter', '.block-unit', _blockUnitSetup)
	.on('click', '.block-unit', function() {//������� �� ���� ��� ���������
		if(!window.BLK)//�������� ��� �� �����������
			return;

		//���� ������������ ������� ������� ����� �� �����, �������� �� ������������
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLK[block_id];

		//���� ���� ��������, �������� �� ������������
		if(BL.child_count)
			return;

		if(BL.elem_id)
			return $('#elem-hint-' + BL.elem_id + ' .icon-edit').trigger('click');

		$('#elem-hint-add').trigger('click');
	});

$.fn._grid = function(o) {
	var t = $(this);

	o = $.extend({
		width:1000,
		parent_id:0,//������������ ����
		obj_name:'page',//��� �������, ��� ������������� �����
		obj_id:PAGE_ID  //id �������
	}, o);

	t.gridstack({
		itemClass:'grid-item',
		handle:'.grid-content',  //�������, �� ������� ����� �������������
		animate:false,           //������� ����������� ����� ���������� ��� ������������
		verticalMargin:1,       //������ ������
		cellHeight:10,          //����������� ������ �����
		float:false,            //���� true - ���� ����� ����������� � ����� �����, ����� ���� ������ ������� � �����
		width:o.width / 10      //���������� ��������� ����������� ������ ����� ����������� �� ���� �����
	});

	var grid = t.data('gridstack'),
		num = 1;
	//���������� ������ �����
	$('#grid-add').click(function() {
		grid.addWidget($('<div id="gn' + num++ + '">' +
			'<div class="grid-content"></div>' +
			'<div class="grid-del">x</div>' +
			'</div>'),
			0, 0, o.width, 3, true);
	});

	//���������� ������
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
			width:o.width,
			arr:arr,
			busy_obj:t
		};
		_post(send, function(res) {
			$('#block-level-' + o.obj_name).after(res.level).remove();
			$('.block-content-' + o.obj_name).html(res.html);
//			_elemActivate(res.elm, {}, 1);
			_blockUpd(res.blk);
		});
	});
	$('#grid-cancel').click(function() {
		$('#block-level-' + o.obj_name)
			.find('.block-grid-on')
			.removeClass('grey')
			.trigger('click');
	});

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
};

