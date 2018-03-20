/* ��� �������� ����������� �����������, ������������ � ���������� */
var DIALOG = {},//������ ���������� ���� ��� ���������� ������� ����������

	MONTH_DEF = {
		1:'������',
		2:'�������',
		3:'����',
		4:'������',
		5:'���',
		6:'����',
		7:'����',
		8:'������',
		9:'��������',
		10:'�������',
		11:'������',
		12:'�������'
	},
	MONTH_DAT = {
		1:'������',
		2:'�������',
		3:'�����',
		4:'������',
		5:'���',
		6:'����',
		7:'����',
		8:'�������',
		9:'��������',
		10:'�������',
		11:'������',
		12:'�������'
	},
	WEEK_NAME = {
		0:'��',
		1:'��',
		2:'��',
		3:'��',
		4:'��',
		5:'��',
		6:'��',
		7:'��'
	},

	_color = function(v, func) {
		$(document)
			.off('mouseenter', '._color td')
			.on('mouseenter', '._color td', function() {//����� ������ ��� ���������
				var td = $(this),
					v = td.attr('val');
				td._tooltip(ELEM_COLOR[v][1]);
			})
			.off('click', '._color td')
			.on('click', '._color td', function() {//��������� ����� ��� ������
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

	_dialog = function(o) {//���������� ����
		o = $.extend({
			top:100,
			width:0,    //������ �������. ���� 0 = �������������
			mb:0,       //margin-bottom: ������ ����� �� ������� (��� ��������� ��� ���������� �������)
			pad:0,      //������ ��� content

			dialog_id:0,//id �������, ������������ �� ����
			unit_id:0,  //id ������� ������, ������� �������� ��� ������ ������� ������� (������ ��� �������� ��� �������������� �������)
			block_id:0, //id �����, � ������� ����������� ������� (������ ��� �������� ��� �������������� �������)

			edit_access:0,//����� ������ �������������� �������

			color:'',   //���� ������� - ��������� � ������
			head:'head: �������� ���������',
			content:'<div class="pad30 pale">content: ���������� ������������ ����</div>',

			butSubmit:'������',
			butCancel:'������',
			submit:function() {},
			cancel:dialogClose
		}, o);

		var DIALOG_NUM = $('._dialog').length,
			html =
			'<div class="_dialog-back"></div>' +
			'<div class="_dialog">' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh pl"></a></div>' +
		            '<div class="edit fr curP' + _dn(!DIALOG_NUM && o.edit_access) + '"><a class="icon icon-edit wh pl"></a></div>' +
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
			w2 = Math.round(width / 2); // ������/2. ��� ����������� ��������� �� ������

		dialog.find('.close').click(dialogClose);
//		content.find('input')._enter(submitFunc);//��� ���� input ��� ������� enter ����������� submit
		butSubmit.click(submitFunc);
		butCancel.click(function() {
//			e.stopPropagation();
//			dialogClose();
			if(butCancel.hasClass('_busy'))
				return;
			o.cancel();
		});
		iconEdit.click(function() {//������� �� ������ ��������������
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
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		ZINDEX += 10;

		function dialogClose() {
			DBACK.remove();
			dialog.remove();
			ZINDEX -= 10;
			if(o.dialog_id)
				delete DIALOG[o.dialog_id];
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
			D:function(attr) {//��������� �������� �� ���������� ��������� �� ����� �������
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
			butSubmit:function(name) {//��������� ������ ������ ����������
				butSubmit[(name ? 'remove' : 'add') + 'Class']('dn');
				butSubmit.html(name);
			},
			butCancel:function(name) {//��������� ������ ������ ������
				butCancel[(name ? 'remove' : 'add') + 'Class']('dn');
				butCancel.html(name);
			},
			submit:function(func) {
				o.submit = func;
			},
			post:function(send, success) {//�������� �����
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
			head:function(v) {//��������� ������ ���������
				dialog.find('.head .white').html(v);
			},
			width:function(v) {//��������� ������ ����
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
	_dialogEdit = function(o) {//��������|�������������� ����������� ����
		var dialog = _dialog({
				dialog_id:o.dialog_id,
				top:20,
				color:'orange',
				width:o.width,
				head:'��������� ����������� ����',
				content:o.html,
				butSubmit:'��������� ���������� ����',
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
			DLG = function(attr) {//��������� �������� ������� ������� �� �������� (��� ���������� ��������� � ������� ���������)
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
			title:'�������������� ������'
		});

		_forN(['insert', 'edit', 'del'], function(act, n) {
			DLG('#' + act + '_action_id')._select({
				width:270,
				title0:'�������� ���, ������� ����',
				spisok:o.action,
				func:function(v) {
					DLG('.td-' + act + '-action-page')._dn(v == 2);
					DLG('#' + act + '_action_page_id')._select(0);
				}
			});
			DLG('#' + act + '_action_page_id')._select({
				width:270,
				title0:'�� �������',
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
		});

		DLG('#table_1')._select({
			width:170,
			title0:'�� �������',
			spisok:o.tables
		});
		DLG('#table_2')._select({
			width:170,
			title0:'�� �������',
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
			title0:'������� ��� ������',
			spisok:o.tablesFields[DLG('#table_2').val()]
		});
		DLG('#element_group_id')._select({
			title0:'���',
			width:230,
			spisok:o.group
		});
		DLG('#element_width')._count({width:60,step:10});
		DLG('#element_width_min')._count({width:60,step:10});
		DLG('#element_dialog_func')._select({
			width:280,
			title0:'�� ������',
			spisok:o.dialog_spisok
		});

		_dialogHeightCorrect(DLG);

		//��������� ����� ��� ��������� ������ �������
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

				insert_head:DLG('#insert_head').val(),
				insert_button_submit:DLG('#insert_button_submit').val(),
				insert_button_cancel:DLG('#insert_button_cancel').val(),
				insert_action_id:DLG('#insert_action_id').val(),
				insert_action_page_id:DLG('#insert_action_page_id').val(),

				edit_head:DLG('#edit_head').val(),
				edit_button_submit:DLG('#edit_button_submit').val(),
				edit_button_cancel:DLG('#edit_button_cancel').val(),
				edit_action_id:DLG('#edit_action_id').val(),
				edit_action_page_id:DLG('#edit_action_page_id').val(),

				del_head:DLG('#del_head').val(),
				del_button_submit:DLG('#del_button_submit').val(),
				del_button_cancel:DLG('#del_button_cancel').val(),
				del_action_id:DLG('#del_action_id').val(),
				del_action_page_id:DLG('#del_action_page_id').val(),

				spisok_on:DLG('#spisok_on').val(),

				table_1:DLG('#table_1').val(),
				table_2:DLG('#table_2').val(),
				table_2_field:DLG('#table_2_field')._select('inp'),
				app_any:DLG('#app_any').val(),
				sa:DLG('#sa').val(),

				element_group_id:DLG('#element_group_id').val(),
				element_width:DLG('#element_width').val(),
				element_width_min:DLG('#element_width_min').val(),
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
	_dialogHeightCorrect = function(DLG) {//��������� ������ ����� ��� ��������� ������ ������� � ������ ����� � ����������
		var h = DLG('#dialog-w-change').parent().height();
		DLG('#dialog-w-change').height(h);
	},

	_dialogOpen = function(o) {//�������� ����������� ����
		var dialog = _dialog({
			dialog_id:o.dialog_id,
			block_id:o.block_id,  //��� �������� ��������, ���� ����� ����������� �������������� �������
			unit_id:o.unit_id,    //id ����� ��� ��������

			top:20,
			width:o.width,
			edit_access:o.edit_access,

			head:o.head,
			content:o.html,
			butSubmit:o.button_submit,
			butCancel:o.button_cancel,
			submit:submit
		});

		//���� �������� ������� ������, �� ������ �������
		if(o.act == 'del')
			dialog.bottom.find('.submit').addClass('red');
		else {
			window.DIALOG_OPEN = dialog;
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
				dialog_source:o.dialog_source,//id ��������� ����������� ����
				cmp:{},
				cmpv:{}
			};

			if(o.unit_id) {
				send.op = 'spisok_save';
				if(o.act == 'del')
					send.op = 'spisok_del';
			}

			//��������� �������� �����������
			if(o.act != 'del')
				_forIn(o.cmp, function(sp, id) {
					switch(sp.dialog_id) {
						case 12://������������ �������
							if(!window[sp.txt_1])
								return;
							send.cmpv[id] = window[sp.txt_1](sp, 'get');
							return;
						case 19://���������� ��� ��������� �����������
							send.cmpv[id] = _cmpV19(sp, 1);
							return;
						case 30://��������� ���������� ���������� ������
							send.cmpv[id] = _cmpV30(sp, 'get');
							break;
						case 37://SA: Select - ����� ����� �������
							send.cmp[id] = $(sp.attr_cmp)._select('inp');
							return;
						case 49://��������� ���������� �������� ������
							send.cmpv[id] = _cmpV49(sp, 'get');
							break;
						case 56://��������� ����� �������� ������� ������
							send.cmpv[id] = _cmpV56(sp, 'get');
							break;
						case 58://���������� ��� ��������� �����������
							send.cmpv[id] = _cmpV58(sp, 1);
							return;
					}
					send.cmp[id] = $(sp.attr_cmp).val();
				});

			dialog.post(send, function(res) {
				//�������� ������� 50 - ����� ��������, ���� ����� ��� �� ����
				if(o.d50close)
					o.d50close();

				//���� ������������ �������, ����������� ���
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
					case 3://���������� ����������� ������
						var bln = '#block-level-' + res.block_obj_name;
						$(bln).after(res.level).remove();
						$(bln)
							.find('.block-grid-on')
							.removeClass('grey')
							.trigger('click');
						break;
					case 4://���������� ��������� �������
						var id = _num(o.dialog_source);
						if(!id)
							break;
						if(!DIALOG[id])
							break;
						DIALOG[id].close();
						if(!res.dialog_source)
							break;
						console.log(res);
						_dialogOpen(res.dialog_source);
						break;
				}
			});
		}
	},
	_elemActivate = function(elem, unit) {//������������� ���������
		var attr_focus = false;//�������, �� ������� ����� ��������� �����

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
						msg:el.hint_msg,
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
				case 1://�������
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
				//select - ����� ��������
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
				//�������
				case 12:
					if(!window[el.txt_1])
						return;
					window[el.txt_1](el, unit);
					return;
				//����� �������� �� ������� ��� ��������
				case 13:
					var P = $(el.attr_cmp).next(),
						obj_name = P.find('.obj_name').val(),
						obj_id = P.find('.obj_id').val(),
						inp = P.find('.inp'),
						del = P.find('.icon-del'),
						choosed = function(bec) {
							_forEq(bec, function(sp) {
								if(sp.attr('val') == $(el.attr_cmp).val()) {
									sp.addClass('sel');
									return false;
								}
							});
						};
					P.click(function() {
						switch(obj_name) {
							case 'page':
								var send = {
										op:'elem_choose_page',
										page_id:obj_id,
										busy_obj:inp,
										busy_cls:'hold'
									};
								_post(send, function(res) {
									$('#_content').html(res.html);
									$('.block-grid-on,.block-level-change,.elem-width-change').hide();
									DIALOG_OPEN.hide();
									DIALOG[28].hide();
									var bec = $('#_content').find('.choose');
									choosed(bec);
									bec.click(function() {
										var t = $(this),
											id = t.attr('val');
										$(el.attr_cmp).val(id);
										inp.val(ELM[id].title);
										del._dn(1);
										P._flash();
										DIALOG[28].show();
										DIALOG_OPEN.show();
										$('.block-grid-on,.block-level-change,.elem-width-change').show();
										$('#block-level-page')
											.find('.block-grid-on')
											.removeClass('grey')
											.trigger('click');
									});
								});
								return;
							case 'dialog':
								var func_open = function(res, dlg) {
									var bec = dlg.D('.choose');
									choosed(bec);
									bec.click(function() {
										var t = $(this),
											id = t.attr('val');
										$(el.attr_cmp).val(id);
										inp.val(ELM74[id]);
										del._dn(1);
										dlg.close();
										P._flash();
									});
								};
								_dialogLoad({
									dialog_id:74,
									dialog_source:obj_id,
									busy_obj:inp,
									busy_cls:'hold',
									func_open:func_open
								});
								return;
						}
						P._hint({
							msg:'����������� �������� ������',
							color:'red',
							pad:10,
							show:1
						});
					});
					del.click(function(e) {
						e.stopPropagation();
						$(el.attr_cmp).val(0);
						inp.val('');
						del._dn();
					});
					return;
				//select - ������������ ��������
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
				//���������� ��� ��������� �����������
				case 19: _cmpV19(el); return;
				//��������������� �������: ������ ��������, ����������� � ��������
				case 22:
					$(el.attr_el).find('DL')._sort({table:'_element_func'});
					return;
				//select - ����� ������ (��� ������ ����������)
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
				//��������� ������� ������� ������
				case 25:
					$('#block-level-spisok')
						.find('.block-grid-on')
						.removeClass('grey')
						.trigger('click');
					return;
				//��������������� �������: ���������� ������� ��� ������ ��������
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

						//����� ����������� �� ���������� ������
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
				//select - ����� ������� �� ������� ������ (��� ������)
				case 29:
					var o = {
						width:el.width,
						title0:el.txt_1,
						write:el.num_1 && el.num_3,
						msg_empty:'�� �������',
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
						o.msg_empty = '������ ���� �� ��������';
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
				//��������������� �������: ��������� ���������� ���������� ������
				case 30: _cmpV30(el, unit); return;
				//����� �������� ��� ���������� Select
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
				//count - ����������
				case 35:
					$(el.attr_cmp)._count({
						width:el.width,
						min:el.num_1,
						max:el.num_2,
						step:el.num_3,
						minus:el.num_4
					});
					return;
				//SA: Select - ����� ����� �������
				case 37:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:'�� �������',
						msg_empty:'������� ���',
						spisok:el.vvv
					});
					_forN(el.vvv, function(u) {
						if(u.title == unit.col) {
							$(el.attr_cmp)._select(u.id);
							return false;
						}
					});
					return;
				//SA: Select - ����� ����������� ����
				case 38:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						msg_empty:'������� ��� �� ���� �������',
						spisok:el.vvv
					});
					return;
				//SA: Select - ������������
				case 41:
					$(el.attr_cmp)._select({
						width:el.width,
						title0:el.txt_1,
						spisok:el.vvv
					});
					return;
				//������ ������: ����������� ���������
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
				//��������������� �������: ���������� ��� ��������� �����������
				case 49: _cmpV49(el, unit); return;
				//���������
				case 51:
					$(el.attr_cmp)._calendar({
						lost:el.num_1,
						time:el.num_2
					});
					return;
				//��������������� �������: ��������� ����� �������� ������� ������ (��� [27])
				case 56: _cmpV56(el, unit); return;
				//���� ������������ ������
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
				//��������������� �������: ��������� ������� ���� ������������ ������ (��� [57])
				case 58: _cmpV58(el); return;
				//������ ������ ��� ������ ������
				case 59:
					var but = $(el.attr_cmp + el.afics),
						div = but.next(),
						unitSel = function(id) {//�������� ����� ������ ��������
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
					//������ ������
					div.find('.icon').click(function() {
						but._dn(1);
						div._dn();
						$(el.attr_cmp).val(0);
					});
					return;
				//�������� �����������
				case 60:
					var AEL = $(el.attr_el),
						load = AEL.find('._image-load'),
						prc = AEL.find('._image-prc'), //div ��� ����������� ���������
						ids_upd = function() {//���������� id ����������� �����������
							var ids = [];
							_forEq(AEL.find('dd.curM'), function(sp) {
								ids.push(sp.attr('val'));
							});
							$(el.attr_cmp).val(ids.join(','));

							//��������� �������� ��� �������� �����������
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
						xhr_upload = function(file) {//�������� ���������� ����� ��� ������ �� ������
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

					//�������� ����������� �� �����
					ids_upd();
					AEL.find('dl').sortable({
						items:'.curM',
						placeholder:'ui-hold',
						update:ids_upd
					});
					AEL.find('.tab-load td').mouseenter(function() {
						var t = $(this),
							msg = '������� �������� �� ������.' +
								  '<br>' +
								  '������ �� ����� 100�100 ����.' +
								  '<br>' +
								  '������ ����� �� ����� 15 ��.';
						if(t.hasClass('ii2'))
							msg = '������� ������ �� �����������';
						if(t.hasClass('ii3'))
							msg = '������� ���� � ���������';
						if(t.hasClass('ii4')) {
							if(t.hasClass('empty'))
								msg = '�������� ����������� ���';
							else {
								var c = _num(t.attr('val'));
								msg = '������� ' + c + ' ����������' + _end(c, ['�', '�', '�']) + '.' +
									  '<br>' +
									  '������� ��� ���������.';
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

					//�������� ����������� �� ������
					var linkDiv = AEL.find('._image-link'), //���� � ������� �� �����������
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

					//�������� ���������
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

					//����������� � ���-������
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

					//�������� �����������
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
				//������-�������
				case 62:
					$(el.attr_cmp)._check({
						func:function(v) {
							_elemFunc(el, v);
							FILTER[el.num_2][el.id] = v;
							_spisokUpdate(el.num_2);
						}
					});
					return;
				//����� ����� ������
				case 66:
					var func = function(v) {
							$(el.attr_cmp).val(v);
						},
						html = _color($(el.attr_cmp).val(), func);
					$(el.attr_cmp).next().remove('._color');
					$(el.attr_cmp).after(html);
					return;
				//����� ����� ����
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
				//������-���������
				case 77:
					var CAL = $(el.attr_el).find('._filter-calendar');
					CAL.find('.laquo').click(function() {
						var send = {
							op:'filter_calendar_mon_change',
							mon:CAL.find('.mon-cur').val(),
							side:$(this).attr('val'),
							busy_cls:'busy',
							busy_obj:CAL
						};
						_post(send, function(res) {
							CAL.find('.mon-cur').val(res.mon);
							CAL.find('.td-mon').html(res.td_mon);
							CAL.find('.fc-cnt').html(res.cnt);

						});
					});
					return;
			}
		});

		if(attr_focus)
			$(attr_focus).focus();
	},
	_cmpV19 = function(o, get) {//���������� ��� ��������� �����������. dialog_id=19
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� ��������</div>',
			DL = el.html(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,
				title:'��� �������� ' + NUM,
				content:'',
				def:0,
				use:0
			}, v);

			DL.append(
				'<dd class="over1" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w90 grey r topi">�������� ' + NUM + ':' +
							'<td><input type="text" class="title w100p b" value="' + v.title + '" />' +
								'<textarea class="w100p min mtm1' + _dn(o.num_1) + '" placeholder="�������� ��������">' + v.content + '</textarea>' +
							'<td class="w15 topi">' +
								'<input type="hidden" class="def" id="el-def-' + NUM + '" value="' + v.def + '" />' +
							'<td class="w50 r top pt5">' +
					   (v.use ? '<div class="dib fs11 color-ccc mr3 curD' + _tooltip('�������������', -53) + v.use + '</div>'
								:
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('������� ��������', -55) + '</div>'
					   ) +
					'</table>' +
				'</dd>'
			);

			DL.sortable({axis:'y',handle:'.icon-move-y'});
			var DD = DL.find('dd:last');
			DD.find('textarea').autosize();
			DD.find('.def')._check({
				tooltip:'�� ���������',
				func:function(v, ch) {
					if(!v)
						return;
					//������ ������� � ��������� ��������
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
	_cmpV30 = function(o, unit) {//��������� ���������� ���������� ������. dialog_id=30
		if(unit == 'get') {//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� �������</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		$('#cmp_531')._check({//�����-������� ��������� ����������
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
				id:0,       //id ��������
				dialog_id:50,//id �������, ����� ������� ��� �������� ���� �������
				attr_el:'#inp_' + NUM,
				attr_bl:'#inp_' + NUM,
				attr_tr:'#tr_' + NUM,
				width:150,  //������ �������
				tr:'',      //��� ������� txt_7
				title:'',   //��� ��������
				font:'',
				color:'',
				pos:'',      //txt_8
				url_access:1,//������� ��������� ���� �������
				url:0        //������� �������� �������
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center top pt5"><div class="icon icon-move-y pl curM"></div>' +
							'<td class="w80 grey r topi">������� ' + NUM + ':' +
							'<td><div style="width:' + v.width + 'px">' +
									'<div class="div-inp-tr' + _dn(unit.num_5) + '">' +
										'<input type="text"' +
											  ' id="tr_' + NUM + '"' +
											  ' class="inp-tr bg-gr2 w100p center fs14 blue mb1"' +
											  ' placeholder="��� �������"' +
											  ' value="' + v.tr + '"' +
										' />' +
									'</div>' +
									'<input type="text"' +
										  ' id="inp_' + NUM + '"' +
										  ' class="inp w100p curP ' + v.font + ' ' + v.color + ' ' + v.pos + '"' +
										  ' readonly' +
										  ' placeholder="�������� �� �������"' +
										  ' value="' + v.title + '"' +
									' />' +
								'</div>' +
							'<td class="w50 r top pt5">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('������� �������', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var INP = $(v.attr_el),
				DD = DL.find('dd:last');
			valueResize(v);
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:unit.source.block_id,  //����, � ������� ��������� �������
					unit_id:v.id || -112,           //id ���������� �������� (��� ��������������)
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
		function valueResize(v) {//��������� ��������� ������, ���� ���� ��������
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
		function cmpUpdate() {//���������� �������� ����������
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
	_cmpV49 = function(o, unit) {//��������� ���������� �������� ������ [44]
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� �������</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1;

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id ��������
				dialog_id:50,
				title:'',
				spc:1     //������ ������
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w25 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="������� �� ������"' +
									  ' value="' + _br(v.title) + '"' +
								' />' +
							'<td class="w25">' +
								'<input type="hidden" class="spc" value="' + v.spc + '" />' +
							'<td class="w50 r">' +
								'<div val="' + NUM + '" class="icon icon-del pl' + _tooltip('������� �������', -52) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:v.dialog_id,
					block_id:unit.source.block_id,
					unit_id:v.id || -111,           //id ���������� �������� (��� ��������������)
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
			DD.find('.spc')._check({tooltip:'������ ������'});
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
		function cmpUpdate() {//���������� �������� ����������
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
	_cmpV56 = function(o, unit) {//��������� ����� �������� ������� ������
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� ��������</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last');

		BUT_ADD.click(valueAdd);

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,       //id ��������
				minus:0,    //��������� ��������
				title:''
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 pl5">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w25 r">' +
								'<button class="vk short ' + (v.minus ? 'red' : 'green') + ' w35">' + (v.minus ? '�' : '+') + '</button>' +
							'<td><input type="text"' +
									  ' class="inp w100p curP"' +
									  ' readonly' +
									  ' placeholder="�������� �� �������"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w50 r">' +
								'<div class="icon icon-del pl' + _tooltip('������� ��������', -54) + '</div>' +
					'</table>' +
				'</dd>'
			);

			var DD = DL.find('dd:last'),
				INP = DD.find('.inp');
			INP.click(function() {
				_dialogLoad({
					dialog_id:11,
					block_id:unit.source.block_id,
					unit_id:v.id || -113,      //id ���������� �������� (��� ��������������)
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
				._tooltip('�����������', -33)
				.click(function() {
					var t = $(this),
						plus = t.hasClass('green');
					t.html(plus ? '�' : '+');
					t._dn(plus, 'green');
					t._dn(!plus, 'red');
					t._tooltip(plus ? '���������' : '�����������', plus ? -26 : -33);
				});
			DD.find('.icon-del').click(function() {
				var t = $(this),
					p = _parent(t, 'DD');
				p.remove();
				cmpUpdate();
				v.id = 0;
			});
		}
		function cmpUpdate() {//���������� �������� ����������
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
	_cmpV58 = function(o, get) {//��������� ������� ���� ������������ ������
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">����� ����� ����</div>',
			DL = el.append(html).find('dl'),
			BUT_ADD = el.find('div:last'),
			NUM = 1,
			BCS = $('.block-choose-submit');//������ ���������� ������ ������

		//������ ������ ������ �� ��������
		$('.block-choose-cancel').click(dlgShow);

		BUT_ADD.click(valueAdd);

		if(!o.vvv.length)
			valueAdd();

		for(var i in o.vvv)
			valueAdd(o.vvv[i])

		function valueAdd(v) {
			v = $.extend({
				id:0,                  //id ��������
				num:NUM,
				title:'��� ������ ' + NUM++, //��� ������ ����
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
									  ' placeholder="��� �� �������"' +
									  ' value="' + v.title + '"' +
								' />' +
							'<td class="w125">' +
								'<input type="text"' +
									  ' class="pk-block w100p curP color-ref over1"' +
									  ' readonly' +
									  ' placeholder="������� �����"' +
									  ' value="' + v.blk_title + '"' +
									  ' val="' + v.blk + '"' +
								' />' +
							'<td class="w35 r">' +
								'<div class="icon icon-del pl' + _tooltip('������� �����', -44) + '</div>' +
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
						BLOCK.val(ids.length ? ids.length + ' ����' + _end(ids.length, ['', '�', '��']) : '');
						dlgShow();
					});
				});
			});
			DD.find('.def')._check({
				tooltip:'�� ���������',
				func:function(v, ch) {
					if(!v)
						return;
					//������ ������� � ��������� ��������
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
	_elemFunc = function(el, v, is_open) {//���������� �������, ����������� � ���������
		/*
			is_open - ���� ���������, �������� ���, ������ ���������� �������
		*/

		if(!el.func)
			return;

		_forN(el.func, function(sp) {
			switch(sp.dialog_id) {
				//�����/������� ������
				case 36://�������[1]:
				case 40://���������� ����[17]:
					var is_show = 0;//�������� ��� ���������� �����. �� ��������� ��������.

					//��������
					switch(sp.action_id) {
						//������
						case 709:
						case 726:
						default: break;
						//��������
						case 710:
						case 727:
							is_show = 1;
							break;
					}

					//�������
					switch(sp.cond_id) {
						case 703://�������� �� �������
						case 730://������� �����
							if(v && sp.action_reverse) {
								is_show = is_show ? 0 : 1;
								break;
							}
							if(v)
								return;
							break;
						case 704://�������� �������
						case 731://������� �����������
							if(!v && sp.action_reverse) {
								is_show = is_show ? 0 : 1;
								break;
							}
							if(!v)
								return;
							break;
						case 705://���������� ��������
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

					//�������
					_forN(_elemFuncBlockObj(sp.target), function(oo) {
						if(!oo.obj.length)
							return;

						switch(sp.effect_id) {
							//������������/���������
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
							//������������/��������������
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
				//���������/������ ��������
				case 73://������-�������[62]
					var is_set = 0;//�� ���������: �������� ��������

					//��������
					switch(sp.action_id) {
						//�������� ��������
						case 1718:
						default: break;
						//���������� ��������
						case 1719:
							is_set = 1;
							break;
					}

					//�������
					switch(sp.cond_id) {
						case 1715://������� �����
							if(v && sp.action_reverse) {
								is_set = is_set ? 0 : 1;
								break;
							}
							if(v)
								return;
							break;
						case 1716://������� �����������
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
						//���� ������� �������� �� ������ �������
						switch(EL.dialog_id) {
							case 1: //�������
							case 62://������-�������
								$(EL.attr_cmp)._check(is_set);
								FILTER[el.num_2][EL.id] = is_set;
								break;
						}
					});
			}
		});
	},
	_elemFuncBlockObj = function(blk_ass) {//��������� $(obj) ������
		var arr = [],
			TRG = _copyObj(blk_ass);

		_forIn(TRG, function(n, block_id) {
			if(!n)
				return;
			var BL = BLK[block_id];

			if(BL.xx == 1) {//���� ���� � ���� ����, ������� ���������� �� ���� �������
				arr.push({
					obj:_parent($(BL.attr_bl), '.bl-div'),
					slide:1
				});
				return;
			}

			//��������, ���������� �� �� ������� �� ��������� ����� � ��� �� ����
			var all = 1;
			_forIn(BL.xx_ids, function(i, id) {
				if(!TRG[id]) {//�����, ���� �� �� ����
					all = 0;
					return false;
				}
			});

			if(all) {
				_forIn(BL.xx_ids, function(i, id) {
					TRG[id] = 0;//����� � ��� �� ���� ����������, ����� � ��� ������� �� �����������
				});
				arr.push({
					obj:_parent($(BL.attr_bl), '.bl-div'),
					slide:1
				});
				return;
			}

			//������� ����� ��������� � ����������� �����
			arr.push({
				obj:$(BL.attr_bl),
				slide:0
			});
		});

		return arr;
	},

	_elemGroup = function(v, dlg) {//�������, ������� ����������� ����� �������� ���� ������ ��������
		var D = dlg.D;
		D('.el-group-head').click(function() {//������������ ����
			var t = $(this),
				id = t.attr('val');
			D('.el-group-head').removeClass('sel');
			t.addClass('sel');

			D('#elem-group .cnt')._dn(0);
			D('#cnt_' + id)._dn(1);
		});
		D('.elem-unit').click(function() {//�������� �������
			var t = $(this);
			v.dialog_id = t.attr('val');
			_dialogLoad(v);
		});

		if(!SA)
			return;
		D('#elem-group .icon-edit').click(function(e) {//�������������� �������
			e.stopPropagation();
			var t = $(this),
				send = {
					op:'dialog_edit_load',
					dialog_id:t.parent().parent().attr('val'),
					busy_obj:t,
					busy_cls:'spin'
				};
			_post(send, _dialogEdit);
		});
		_forEq(D('#elem-group .cnt'), function(sp) {
			sp._sort({table:'_dialog'});
		});
	},
	_dialogLoad = function(o) {//�������� �������
		/*
			o.func_open - �������, ����������� ����� �������� �������
			o.func_save - �������, ����������� ����� ��������� ���������� ������� (����� ������� ������ submit)
		*/
		var send = {
			op:'dialog_open_load',
			page_id:PAGE_ID,

			dialog_id:_num(o.dialog_id),        //������, ������� ������ �������
			dialog_source:_num(o.dialog_source),//�������� ������, ���� �������������
			block_id:_num(o.block_id, 1),       //���� (��� ������������� id: �������-�����������), � ������� ����������� �������
			unit_id:_num(o.unit_id, 1),         //id ������� ������ (������� ��� �������)

			del:_num(o.del),                    //�������� ��������

			busy_obj:o.busy_obj,
			busy_cls:o.busy_cls
		};

		_post(send, function(res) {
			if(res.dialog_id == 44) {
				if(o.d50close)
					o.d50close();
			} else
				res.d50close = o.d50close;
			//�������, ����������� ����� �������� ������� ��������
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

	_pageSetupAppPage = function() {//���������� ������� ���������� � ������ �������
		$('#page-sort').nestedSortable({
			forcePlaceholderSize: true,//��������� ������ �����, ������ ��� ���� �������
			placeholder:'placeholder',//�����, ����������� ��� ��������� �����, ������ ������ �������
			handle:'.icon-move',
//			helper:	'clone',
			listType:'ol',
			items:'li',
//			tolerance:'pointer',
			toleranceElement:'> div',
			isTree:true,
			maxLevels:3,
//			startCollapsed: false,
			tabSize:30,//����������, �� ������� ���� �������� �������, ����� �� ������� �� ������ �������
//			expandOnHover:700,
//			opacity:1,
			revert:200, //������� ����������� (����) �������� �� ��� �����. ����� - �������� � �������������.

			update:function(e, t) {
				//����������� ������ ������������ ��������. ���� ��������, �� ��������� ������
				var pos = t.item.parent().attr('id');
				t.item.find('a')._dn(!pos, 'b fs14');

				var send = {
					op:'page_sort',
					arr:$(this).nestedSortable('toArray')
				};
				_post(send);
			},

			expandedClass:'pb10',//��������� ������
			errorClass:'bg-fcc' //������, ���� ������� ����������� ������� �� ����������� �������
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
	_pageUserAccessAll = function(el, i) {//��������� ����� ��� ���� �����������
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
	_imageShow = function() {//�������� �����������. ������������ �������� [12]
		var IMS = $('#_image-show'),
			IU = IMS.find('.iu'),
			IMAIN = $('#_image-main'),
			imNext = function(next_id) {//��������� ���������� �����������
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
		IMAIN.click(function() {//������� �� �������� �����������
			if(!IMG_IDS.length) {//���� ����������� ����� ����, �� �������� �������
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
		IU.click(function() {//������� �� �������������� �����������
			var t = $(this),
				id = _num(t.attr('val'));
			imNext(id);
		});
	},
	_filterCheckSetup = function(o, i) {//��������� ������� ������� ��� ������� (����������� ����� [12])
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� �������</div>',
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
				id:0,       //id �������� �� �������, �� �������� ����� ����������� ������� �������
				title:'',   //��� ��������
				num_8:0,  //id ������� �� ����������� ������ [num_8]
				txt_8:''  //������� �������                  [txt_8]
			}, v);

			DL.append(
				'<dd class="over3">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w50 r color-sal">����:' +
							'<td><input type="text"' +
									  ' readonly' +
									  ' class="title w150 curP over4"' +
									  ' placeholder="�������� ��������..."' +
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
								'<div class="icon icon-del pl' + _tooltip('������� �������', -52) + '</div>' +
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
					unit_id:v.id || -114,           //id ���������� �������� (��� ��������������)
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
			COND_ID._select({//�������
				width:150,
				disabled:!v.id,
				spisok:[
					{id:1,title:'�����������'},
					{id:2,title:'������������'},
					{id:3,title:'�����'},
					{id:4,title:'�� �����'},
					{id:5,title:'������'},
					{id:6,title:'������ ��� �����'},
					{id:7,title:'������'},
					{id:8,title:'������ ��� �����'},
					{id:9,title:'��������'},
					{id:10,title:'�� ��������'}
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
	_historySetup = function(o, i) {//��������� ������� ������� �������� (����������� ����� [12])
		var el = $(o.attr_el);

		//��������� ������ ��� ����������
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
				   '<div class="fs15 color-555 pad10 center over1 curP">�������� ������</div>',
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
				id:0,     //id ��������-������
				dialog_id:50,  //id �������, ���������� ��������-��������
				num_1:0,  //id ��������-��������
				title:'', //��� ��������-��������
				txt_7:'', //����� �����
				txt_8:''  //����� ������
			}, v);

			DL.append(
				'<dd class="over3" val="' + v.id + '">' +
					'<table class="bs5 w100p">' +
						'<tr><td class="w35 center">' +
								'<div class="icon icon-move-y pl curM"></div>' +
							'<td class="w200">' +
								'<input type="text"' +
									  ' class="txt_7 w100p"' +
									  ' placeholder="����� �����"' +
									  ' value="' + v.txt_7 + '"' +
								' />' +
							'<td class="w150">' +
								'<input type="text"' +
									  ' readonly' +
									  ' class="title w100p curP over4"' +
									  ' placeholder="�������� �� �������"' +
									  ' value="' + v.title + '"' +
									  ' val="' + v.num_1 + '"' +
								' />' +
							'<td class="w200">' +
								'<input type="text"' +
									  ' class="txt_8 w100p"' +
									  ' placeholder="����� ������"' +
									  ' value="' + v.txt_8 + '"' +
								' />' +
							'<td class="r">' +
								'<div class="icon icon-del pl' + _tooltip('������� ������', -48) + '</div>' +
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
	.on('click', '._check', function() {//���������/������ �������, ���� ���� �������� ����� PHP
		var t = $(this);
		if(t.hasClass('noon'))//���� ������� �������� ����� JS, � �� ����� PHP, �� �������� ���
			return;
		if(t.hasClass('disabled'))
			return;

		var p = t.prev(),
			v = _num(p.val()) ? 0 : 1;

		p.val(v);
		t._dn(!v, 'on');
	})
	.on('click', '._radio div', function() {//����� �������� radio, ���� ��� ������� ����� PHP
		var t = $(this),
			p = t.parent();
		if(!p.hasClass('php'))//���� ������� ��� ������� ����� JS, � �� ����� PHP, �� �������� ���
			return;
		if(p.hasClass('disabled'))
			return;
		var v = _num(t.attr('val'));

		p.prev().val(v);
		p.find('.on').removeClass('on');
		t.addClass('on');
	})

	.on('click', '.dialog-open', function() {//������� �� ������, ������ ��� �������� �������
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
	.on('click', '.image-open', function() {//�������� ����������� ��� ������� �� ���������
		var t = $(this),
			id = t.attr('val');
		_dialogLoad({
			dialog_id:65,
			unit_id:id,
			busy_obj:t.parent()
		});
	})

	.on('mouseenter', '.dialog-hint', function() {//����������� ��������� ��� ��������� �� ������ � �������
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
	})
	.ready(function() {
	});

$.fn._check = function(o) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id');
	if(!attr_id) {
		attr_id = 'check' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var div_id = attr_id + '_check',
		win = attr_id + '_check_win',
		S = window[win];

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
		o.func(v, t);
	});

	if(o.tooltip)
		CHECK._tooltip(o.tooltip);

	function checkPrint() {//����� �������
		var nx = t.next(),
			cls = '';//�������������� �����, ������� ���� ��������� ����� PHP
		if(nx.hasClass('_check')) {  //���� ������� ���� �������� ����� PHP - ���������� � ���������� �������
			o = $.extend({
				title:nx.html() == '&nbsp;' ? '' : nx.html(),
				disabled:nx.hasClass('disabled'),
				light:nx.hasClass('light'),
				block:nx.hasClass('block')
			}, o);
			nx.removeClass('_check title light block disabled on');
			cls = ' ' + nx.attr('class');
			nx.remove();
		}

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
				'<div id="' + div_id + '" class="_check noon' + on + title + light + block + dis + cls + '">' +
					(o.title ? o.title : '&nbsp;') +
				'</div>';

		t.val(val).after(html);
	}
	function setVal(v) {
		CHECK[(v ? 'add' : 'remove') + 'Class']('on');
		t.val(v);
	}

	t.value = setVal;
	t.funcGo = function() {//���������� �������
		o.func(_num(t.val()), t);
	};
	t.dis = function() {//������� ������� � ���������� ���������
		CHECK.addClass('disabled');
	};
	t.enab = function() {//������� ������� � �������� ���������
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
		interval:7, //�������� ����� ����������
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
	t.funcGo = function() {//���������� �������
		o.func(_num(t.val()), attr_id);
	};
	t.dis = function() {//������� ������� � ���������� ���������
		RADIO.addClass('disabled');
	};
	t.enab = function() {//������� ������� � �������� ���������
		RADIO.removeClass('disabled');
	};
	window[win] = t;
	return t;
};
$.fn._count = function(o) {//input � �����������
	var t = $(this),
		attr_id = t.attr('id'),
		S;

	if(!attr_id) {
		attr_id = 'count' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_count';

	o = $.extend({
		width:50,   //���� 0 = 100%
		bold:0,
		min:false,  //����������� ��������
		max:false,  //������������ ��������
		minus:0,    //����� ������� � �����
		step:1,     //���. ���� ������ ���������: [1,5,10,20]
		again:0,    //���� ������������ ������� �� �������� ��������, ����������� � ������
		time:0,     //�������� �������� �������� (���������� ���� �������, ���� ������ 10)
		tooltip:'',
		disabled:0,
		func:function() {}
	}, o);

	if(o.min < 0)
		o.minus = 1;

	var val = _num(t.val());
	val = valCorrect();
	t.val((o.time && val < 10 ? '0' : '') + val)
	 .attr('type', 'text')
	 .attr('readonly', true);

	var width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		dis = o.disabled ? ' disabled' : '',
		STEP_COUNT = o.step.length || 0,//���������� ��������, ���� ���-������
		STEP_N = 0;//����� ����, ���� ���-������

	if(STEP_COUNT) {
		o.min = o.step[0];
		o.max = o.step[STEP_COUNT - 1];
		_forN(o.step, function(sp, n) {
			if(sp == val) {
				STEP_N = n;
				return false;
			}
		});
	}

	if(t.parent().hasClass('_count')) {
		t.parent()
			._dn(dis == '', 'disabled')
			.attr('id', win)
			.width(o.width || '100%')
			.find('.but').remove();
	} else {
		t.wrap('<div class="_count' + dis + '" id="' + win + '" style="' + width + '">');
	}

	var el = $('#' + win);
	el._dn(val || o.time, 'nol');
	el.append(
		'<div class="but"></div>' +
		'<div class="but but-b"></div>'
	);

	if(o.bold)
		t.addClass('b');

	if(o.tooltip)
		el._tooltip(o.tooltip, -15);

	el.find('.but').click(function() {
		if(dis)
			return;
		var znak = $(this).hasClass('but-b') ? -1 : 1;

		if(STEP_COUNT) {
			STEP_N += znak;
			if(znak > 0 && STEP_N > STEP_COUNT - 1)
				STEP_N = STEP_COUNT - 1;
			if(znak < 0 && STEP_N < 0)
				STEP_N = 0;
			val = o.step[STEP_N];
		} else
			val += o.step * znak;

		val = valCorrect();
		el._dn(val || o.time, 'nol');
		t.val((o.time && val < 10 ? '0' : '') + val);
		o.func(val, attr_id);
	});
	function valCorrect() {
		if(o.min !== false && val < o.min && o.again && o.max !== false)
			return o.max;

		if(o.max !== false && val > o.max && o.again && o.min !== false)
			return o.min;

		if(!o.minus && val < 0)
			return 0;

		if(o.min !== false && val < o.min)
			return o.min;

		if(o.max !== false && val > o.max)
			return o.max;

		return val;
	}

	window[win] = t;
	return t;
};
$.fn._select = function(o, o1) {//���������� ������ �� 03.01.2018
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id'),
		VALUE = t.val();

	if(!attr_id) {
		attr_id = 'select' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_select',
		s = window[win];

	switch(typeof o) {
		default:
		case 'undefined': break;
		case 'object': break;
		case 'number':
		case 'string': return action();
	}

	o = $.extend({
		width:150,			// ������. ���� 0 = 100%
		disabled:0,         // ������ ��������, ����� �����
		blocked:0,          // �������������. ������ ��������, �� �������� ����
		block:0,       	    // ������������ �������
		title0:'',			// ���� � ������� ���������
		spisok:[],			// ���������� � ������� json
		write:0,            // ����������� ������� ��������
		write_save:0,       // ��������� �����, ���� ���� �� ������ �������
		msg_empty:'������ ����',
		multi:0,            // ����������� �������� ��������� ��������. �������������� ������������� ����� �������
		func:function() {},	// �������, ����������� ��� ������ ��������
		funcWrite:funcWrite,// �������, ����������� ��� ����� � INPUT � �������. ����� ��� ������ ������ �� ���, ��������, Ajax-�������, ���� �� vk api.
		funcAdd:null	    // ���������� ����� �������. ���� �������, ���������� ������.
	}, o);

	var dis = o.disabled ? ' disabled' : '',
		blocked = o.blocked ? ' blocked' : '',
		dib = o.block ? '' : ' dib',
		width = 'width:' + (o.width ? o.width + 'px' : '100%'),
		readonly = o.write ? '' : ' readonly',
		placeholder = o.title0 ? ' placeholder="' + o.title0 + '"' : '',
		iconAddFlag = o.funcAdd && !dis && !blocked,
		html =
		'<div class="_select' + dis + blocked + dib + '" id="' + win + '" style="' + width + '">' +
			'<table class="w100p">' +
				'<tr><td>' +
			 (o.multi ? '<dl>' : '') +
							'<input type="text" class="select-inp w50' + _dn(!o.multi, 'w100p') + '"' + placeholder + readonly + ' />' +
			 (o.multi ? '</dl>' : '') +
					'<td class="w15' + _dn(o.write) + '"><div class="icon icon-del clear pl dn"></div>' +
					'<td class="w25 r' + _dn(iconAddFlag) + '"><div class="icon icon-add pl"></div>'+
					'<td class="arrow">' +
			'</table>' +
			'<div class="select-res"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	if(blocked)
		dis = 1;

	var SEL = t.next(),
		DL = SEL.find('dl'),
		DLW = o.multi ? Math.round(DL.width()) : 0,
		INP = SEL.find('.select-inp'),
		RES = SEL.find('.select-res'),
		ICON_DEL = SEL.find('.clear'),
		ICON_ADD = SEL.find('.icon-add'),
		MASS_ASS,//������������� ������ � ���� {1:'text'}
		MASS_SEL,//������ � ���� [{id:1,title:'text1'},{id:2,title:'text2'}]
		MASS_SEL_SAVE,//������������ MASS_SEL
		TAG = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // ����� ���� �����
		BG_ASS;  //������������� ������ ������ ����

	massCreate();
	o.multi ? multiPrint() : spisokPrint();
	valueSet(o.multi ? '' : VALUE);

	INP.keydown(function() {
		setTimeout(function() {
			VALUE = 0;
			t.val(0);
			var v = INP.val();
			ICON_DEL._dn(v && !o.multi);
			o.funcWrite(v, t);
		}, 0);
	});
	SEL.click(function(e) {
		if(dis)
			return;

		var rs = SEL.hasClass('rs'),
			tagret = $(e.target);

		if(tagret.hasClass('select-unit info'))
			return;
		if(tagret.hasClass('select-unit')) {
			valueSet(tagret.attr('val'));
			o.func(VALUE);
		} else {
			var p = _parent(tagret, '.select-unit');
			if(p.hasClass('select-unit')) {
				valueSet(p.attr('val'));
				o.func(VALUE);
			}
		}

		if(rs && o.write && tagret.hasClass('select-inp'))
			return;
		if(tagret.hasClass('icon-add'))
			return;
		if(tagret.hasClass('icon-del'))
			return;
		if(tagret.hasClass('empty'))
			return;

		SEL._dn(rs, 'rs');
		RES._dn(RES.height() < 250, 'h250');

		//��������� ���������� ��������
		if(!rs)
			_forEq(RES.find('.select-unit'), function(sp) {
				if(VALUE == sp.attr('val')) {
					RES.find('.select-unit').removeClass('ov');
					if(sp.hasClass('info'))
						return false;
					sp.addClass('ov');
					//��������� ���������� �������� � ������� ���������
					sp = sp[0];
					var showTop = Math.round((250 - sp.offsetHeight) / 2);
					RES[0].scrollTop = sp.offsetTop - showTop;
					return false;
				}
			});
	});
	ICON_DEL.click(function() {
		valueSet(0);
		o.funcWrite('', t);
	});
	if(iconAddFlag)
		SEL.find('.icon-add').click(function() {
			o.funcAdd(t);
		});

	if(o.multi)
		DL.sortable({update:multiValueSet});

	$(document)
		.off('click._select')
		 .on('click._select', function(e) {
			var cur = $(e.target).parents('._select'),
				attr = '';

			//�������� ��������, ����� ������� ���� � �������
			if(cur.hasClass('_select'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._select' + attr).removeClass('rs');
		});

	function massCreate() {//�������� ������� ��� ����������� ������ ������
		var unit;

		MASS_ASS = {};
		MASS_SEL = [];
		MASS_SEL_SAVE = [];
		BG_ASS = {};

		if(o.title0)
			MASS_ASS[0] = '';

		//�������� ������ �������� ������������� ��������
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
				MASS_SEL_SAVE.push(_copyObj(unit));
			});
			return;
		}

		//�������� ������ �������� ���������������� ��������
		_forN(o.spisok, function(sp, n) {
			var id,
				title,
				content;

			//�������� �� ���������� ���������������� ������
			if(typeof sp == 'number' || typeof sp == 'string') {
				id = n + 1;
				title = sp;
			} else {
				id = sp.uid;
				if(id === undefined)
					id = sp.id;
				if(sp.info)
					id = -1;
				if(id === undefined)
					return;
				id = _num(id, 1);
				if(!id)
					return;
				title = sp.title;
				if(title === undefined)
					return;
				content = sp.content;
			}

			MASS_ASS[id] = title || ' ';
			title = title || '&nbsp;';
			if(!content)
				content = title;
			unit = {
				id:id,
				title:title,
				content:content,
				info:_num(sp.info),//���� ��������������� ��������. �������� ������ �������.
				bg:sp.bg
			};
			MASS_SEL.push(unit);
			MASS_SEL_SAVE.push(_copyObj(unit));
			BG_ASS[id] = sp.bg;
		});
	}
	function spisokPrint() {//������� ������ � select
		RES.removeClass('h250');
		if(!MASS_SEL.length) {
			RES.html('<div class="empty">' + o.msg_empty + '</div>');
			return;
		}

		var html = '',
			is_sel = o.multi ? _idsAss(t.val()) : {}; //��������� �������� (������ ������� �������� ��� multi)
		if(o.title0 && !o.write && !o.multi)
			html += '<div class="select-unit title0" val="0">' + o.title0 + '</div>';

		_forN(MASS_SEL, function(sp) {
			if(is_sel[sp.id])
				return;
			var info = sp.info ? ' info' : '',
				val = info ? '' : ' val="' + sp.id + '"',
				bg = sp.bg ? ' style="background-color:' + sp.bg + '"' : '';
			html += '<div class="select-unit' + info + '"' + bg + val + '>' + sp.content + '</div>';
		});

		RES.html(html);

		var h = RES.height();
		RES._dn(h < 250, 'h250');

		RES.find('.select-unit').mouseenter(function() {
			var sp = $(this);
			RES.find('.ov').removeClass('ov');
			if(sp.hasClass('info'))
				return;
			sp.addClass('ov');
		});
	}
	function funcWrite() {//��������� �������� ��� ������
		var v = $.trim(INP.val()),
			find = [],
			reg = new RegExp(v, 'i'); // ��� ������ ���������� ��������
		_forN(MASS_SEL_SAVE, function(sp) {
			var un = _copyObj(sp),
				arr = un.content.split(TAG); // �������� �� ������ �������� �����
			_forN(arr, function(r, k) {
				if(!r.length)    //���� ������ ������
					return;
				if(TAG.test(r))  //���� ��� ���
					return;
				if(!reg.test(r)) //���� ��� ����������
					return;

				arr[k] = r.replace(reg, '<em class="fndd">$&</em>'); // ������������ ������
				un.content = arr.join('');
				find.push(un);
				return false; // � ����� ����� �� �������
			});
		});
		MASS_SEL = find;
		spisokPrint();
	}
	function valueSet(v) {//��������� ��������
		if(o.multi)
			return multiValueSet(v);
		v = _num(v);
		VALUE = v;
		t.val(v);
		INP.val(MASS_ASS[v] ? MASS_ASS[v].replace(/&quot;/g,'"') : '');
		ICON_DEL._dn(v && o.write);
		if(BG_ASS[v]) {
			SEL.css('background-color', BG_ASS[v]);
			INP.css('background-color', BG_ASS[v]);
		}
	}
	function multiValueSet(v) {//���������� ������� � ������ ������ ����� ������� ��������, ���� ������-�����
		v = _num(v);
		multiBefore(v);

		var dd = DL.find('dd:last'),
			w = DLW - 10,
			vv = [];
		if(dd.length) {
			var ol = dd[0].offsetLeft,
				ow = dd[0].offsetWidth,
				inpW = DLW - ol - ow - 10;
			w = inpW < 30 ? w : inpW;
			_forEq(DL.find('dd'), function(sp) {
				vv.push(sp.attr('val'));
			});
		}
		INP.width(w);
		INP.attr('placeholder', dd.length ? '' : o.title0);
		t.val(vv.join(','));
		spisokPrint();
	}
	function multiPrint() {//����� ��������� �������� ��� ������-������
		_forIn(_idsAss(t.val()), function(i, id) {
			multiBefore(id);
		});
	}
	function multiBefore(v) {//������� ��������, ���� ������-�����
		if(!v)
			return;
		if(!MASS_ASS[v])
			return;
		INP.before(
			'<dd class="multi" val="' + v + '">' +
				MASS_ASS[v] +
				'<div class="icon icon-del pl"></div>' +
			'</dd>'
		);
		INP.val('');
		DL.find('.icon:last').click(function() {
			$(this).parent().remove();
			multiValueSet();
		});
	}
	function action() {//���������� �������� � ������������ �������
		if(s === undefined)
			return t;

		if(typeof o == 'number') {
			s.value(o);
			return s;
		}

		switch(o) {
			case 'disable': s.disable(); break;
			case 'enable': s.enable(); break;
			case 'inp': return s.inp();
			case 'spisok': s.spisok(o1); break;
		}

		return s;
	}

	t.value = valueSet;
	t.icon_del = ICON_DEL;
	t.icon_add = ICON_ADD;
	t.inp = function() {//��������� ��������� ��������
		return INP.val();
	};
	t.disable = function() {//������� ����������
		SEL.addClass('disabled')
		   .removeClass('rs');
		INP.attr('readonly', true);
		SEL.find('.td-add')._dn();
		dis = true;
	};
	t.enable = function() {//������� ��������
		SEL.removeClass('disabled');
		dis = false;
	};
	t.process = function() {//����� �������� ��������
		ICON_DEL.addClass('spin');
	};
	t.isProcess = function() {//��������� ����� �������� ��������
		return ICON_DEL.hasClass('spin');
	};
	t.cancel = function() {//������ �������� ��������
		ICON_DEL.removeClass('spin');
	};
	t.spisok = function(spisok) {//������� ������ ������
		t.cancel();
		o.spisok = spisok;
		massCreate();
		spisokPrint();
	};
	t.unitUnshift = function(unit) {//������� ������� � ������ ������������� ������
		o.spisok.unshift(unit);
		massCreate();
		spisokPrint();
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
				case 'load'://�������� ������ ������
					s.process();
					_post1(o1, function(res) {
						if(res.success) {
							s.spisok(res.spisok);
							if(o2)
								o2(res);
						} else
							s.cancel();
					});
					break;
				case 'cancel': s.cancel(); break;
				case 'clear': s.clear(); break;//�������� inp, ��������� val=0
				case 'empty': s.empty(); break;//�������� ������, ��������� val=0
				case 'title0'://��������� ��� ��������� title0
					if(o1) {
						s.title0(o1);
						return s;
					}
					return s.title0();
				case 'title': return s.title();
				case 'inp': return s.inp();
				case 'focus': s.focus(); break;
				case 'first': s.first(); break;//��������� ������� �������� � ������
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
/*			//���� ��� ������ ����, �� �������
			s = window[id + '_select'];
			if(!s)
				break;

			//������� ������ ����� ��������
			if('length' in o) {
				s.spisok(o);
				return t;
			}
			if(!('spisok' in o))
				return t;
*/
	}

	o = $.extend({
		width:180,			// ������
		disabled:0,
		block:0,       	    // ������������ �������
		title0:'',			// ���� � ������� ���������
		spisok:[],			// ���������� � ������� json
		limit:0,
		write:0,            // ����������� ������� ��������
		write_save:0,       // ��������� �����, ���� ���� �� ������ �������
		nofind:'������ ����',
		multiselect:0,      // ����������� �������� ��������� ��������. �������������� ������������� ����� �������
		func:function() {},	// �������, ����������� ��� ������ ��������
		funcAdd:null,		// ������� ���������� ������ ��������. ���� �� ������, �� ��������� ������. ������� ������� ������ ���� ���������, ����� ����� ���� �������� �����
		funcKeyup:funcKeyup	// �������, ����������� ��� ����� � INPUT � �������. ����� ��� ������ ������ �� ���, ��������, Ajax-�������, ���� �� vk api.
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
					(o.clear ? '<div class="icon icon-del mt5 fr' + _dn(val) + _tooltip('��������', -49, 'r') + '</div>' : '') +
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
		resH, //������ ������ �� ���������
		title0bg = select.find('.title0bg'), //������� title ��� background
		ass,            //������������� ������ � ����������
		save = [],      //���������� ��������� ������
		assHide = {},   //������������� ������ � ������������ � ������
		multiCount = 0, //���������� ��������� ������-��������
		tag = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // ����� ���� �����
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

	var keyVal = inp.val();//�������� �������� �� inp

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
			case 38: //�����
				if(n == len)
					n = 1;
				if(n > 0) {
					if(len > 1) // ���� � ������ ������ ����� ��������
						u.eq(n).removeClass('ov');
					ov = u.eq(n - 1);
				} else
					ov = u.eq(0);
				ov.addClass('ov');
				ov = ov[0];
				if(res0.scrollTop > ov.offsetTop)// ���� ������� ���� ����� ���� ���������, �������� � ����� ����
					res0.scrollTop = ov.offsetTop;
				if(ov.offsetTop - 250 - res0.scrollTop + ov.offsetHeight > 0) // ���� ����, �� ����
					res0.scrollTop = ov.offsetTop - 250 + ov.offsetHeight;
				break;
			case 40: //����
				if(n == len) {
					u.eq(0).addClass('ov');
					res0.scrollTop = 0;
				}
				if(n < len - 1) {
					u.eq(n).removeClass('ov');
					ov = u.eq(n+1);
					ov.addClass('ov');
					ov = ov[0];
					if(ov.offsetTop + ov.offsetHeight - res0.scrollTop > 250) // ���� ������� ���� ���������, �������� � ������ �������
						res0.scrollTop = ov.offsetTop + ov.offsetHeight - 250;
					if(ov.offsetTop < res0.scrollTop) // ���� ����, �� � �������
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
	function unitSelShow() {//��������� ���������� ���� � ����������� ��� � ���� ���������
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
	function assCreate() {//�������� �������������� �������
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
	function multiCorrect(v, ch) {//������������ �������� ������ multi
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
				reg = new RegExp(v, 'i'); // ��� ������ ���������� ��������
			for(n = 0; n < o.spisok.length; n++) {
				var sp = o.spisok[n],
					arr = sp.content.split(tag); // �������� �� ������ �������� �����
				for(var k = 0; k < arr.length; k++) {
					var r = arr[k];
					if(r.length) // ���� ������ �� ������
						if(!tag.test(r)) // ���� ��� �� ���
							if(reg.test(r)) { // ���� ���� ����������
								arr[k] = r.replace(reg, '<em>$&</em>'); // ������������ ������
								sp.content = arr.join('');
								find.push(sp);
								break; // � ����� ����� �� �������
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

			//���������� ������� ���������, ���� ������ ������ ���� ������
			var st = select.offset().top;//��������� ������� ������ ��������
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
	t.process = function() {//����� �������� �������� � selinp
		inp.addClass('_busy');
	};
	t.isProcess = function() {//�������� ������� �������� �������� � selinp
		return inp.hasClass('_busy');
	};
	t.cancel = function() {//������ �������� �������� � selinp
		inp.removeClass('_busy');
	};
	t.clear = function() {//�������� inp, ��������� val=0
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
	t.empty = function() {//�������� ������, ��������� val=0
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
	t.title0 = function(v) {//���������|��������� �������� ��������
		if(v) {
			title0bg.html(v);
			select.find('.title0').html(v);
			return;
		}
		return title0bg.html();
	};
	t.title = function() {//��������� ����������� �������������� ��������
		return ass[t.val()];
	};
	t.inp = function() {//��������� ����������� ��������� ��������
		return inp.val();
	};
	t.focus = function() {//��������� ������ �� input
		inp.focus();
	};
	t.disabled = function() {//��������� ������������� �������
		select.addClass('disabled');
		res.remove();
		inp.remove();
	};
	t.first = function() {//��������� ������� �������� � ������
		if(!o.spisok.length)
			setVal(0);
		setVal(o.spisok[0].uid);
	};

	window[id + '_select'] = t;
	return t;
};
$.fn._hint = function(o) {//����������� ���������
	var t = $(this);

	//������� ���������. ��� �������� ������ ��� ���������, ������� ���� ���������
	if(!window.HINT_NUM)
		HINT_NUM = 1;

	//���� � ���� ��������� ���� ������������ �� ������, �� ������ �� ����������
	if(o.show && t.hasClass('hnt' + HINT_NUM))
		return t;

	o = $.extend({
		msg:'�����',//��������� ���������
		color:'',   //���� ������ (�����)
		width:0,    //������������� ������. ���� 0 - �������������
		pad:1,      //���������� ������� ��������

		side:'auto',    //�������, � ������� ��������� ������������ ������������ �������� (auto, top, right, bottom, left)
		ugPos:'center', //������� ������ �� ���������: top, bottom (��� ���������), left, right (��� �����������). ���� ����� � �������� ����� ���� ������.
		objPos:'center',//������� �������, � ������� ����� ��������� ������.
						//top, bottom - ��� ���������
	                    //left, right - ��� �����������
						//mouse - ������������ ��������� ���� ��� ������ ������� �������
	                    //����� � �������� ����� ���� ������.

		show:0,	     //����� ���������� ���������. ����� ������ ���������.

		event:'mouseenter', // �������, ��� ������� ���������� �������� ���������
		speed:200,//�������� ���������, �������
		delayShow:0, // �������� ����� ���������
		delayHide:0, // �������� ����� ��������

		func:function() {},         //�������, ������� ����������� ����� ������� ��������
		funcBeforeHide:function() {}//�������, ������� ����������� ����� ������� ������� ���������
	}, o);

	//������������� ����������� ������ � ������ ��������
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

	var HINT = $('#hint' + HN),
		MSG = HINT.find('.hi-msg'),
		UG = HINT.find('.ug');

	o.func(MSG);

	HINT.css('z-index', ZINDEX + 6);

	//�������������� ������ ������, ���� ������ ������� �������
	if(!o.width) {
		var msgW = Math.ceil(MSG.width()),
			msgH = Math.ceil(MSG.height()),
			k = 23;//����������� �� ������ ������, ������� ���������� ����������� ������, �� ������� ����� �������� ���������
		if(msgW > msgH * k) {
			var del = msgW / (msgH * k);
			msgW = Math.ceil(msgW / del);
			MSG.width(msgW);
		}
	}

	var W = Math.ceil(HINT.css('width').split('px')[0]),      //������ ������ ��������� � ������ �����
		H = Math.ceil(HINT.css('height').split('px')[0]),     //������ ������ ��������� � ������ �����

		TBS = t.css('box-sizing'),
		objW = hintObjW(),//������ �������
		objH = hintObjH(),//������ �������

		slide = 20, //����������, �� ������� ���������� ��������� ��� ���������
		SIDE = o.side,       //�������, � ������� ����� ��������� ���������
		topStart,
		topEnd,
		leftStart,
		leftEnd,

		// �������� �������� ���������:
		// - wait_to_showing - ������� ������ (���� ���� ��������)
		// - showing - ���������
		// - showed - ��������
		// - wait_to_hidding - ������� ������� (���� ���� ��������)
		// - hidding - ����������
		// - hidden - ������
		process = 'hidden',
		timer = 0;

	//�������������� ����������� ������ ��� ����, ����� ��������� ���������� ������� ������� � �� ����������, ���� ��������� � ������ ����� ������
	if(!o.width)
		HINT.width(W - 2);

	t.on(o.event + '.hint' + HN, hintShow);
	t.on('mouseleave.hint' + HN, hintHide);
	HINT.on('mouseenter.hint' + HN, hintShow)
		.on('mouseleave.hint' + HN, hintHide);

	// �������������� ����� ���������, ���� �����
	if(o.show) {
		t.addClass('hnt' + HN);
		t.addClass('hint-show');
		if(o.objPos != 'mouse')
			hintShow();
		t.on('mousemove.hint' + HN, hintShow);
	}

	function hintObjW() {//��������� ������ �������
		var w = Math.round(t.css('width').split('px')[0]);//���������� ������
		w += Math.round(t.css('border-left-width').split('px')[0]);//����� ������
		w += Math.round(t.css('border-right-width').split('px')[0]);//����� �����
		if(TBS != 'border-box') {
			w += Math.round(t.css('padding-left').split('px')[0]);//������ �����
			w += Math.round(t.css('padding-right').split('px')[0]);//������ ������
		}
		return w;
	}
	function hintObjH() {//��������� ������ �������
		var h = Math.round(t.css('height').split('px')[0]);//���������� ������
		h += Math.round(t.css('border-top-width').split('px')[0]);//����� ������
		h += Math.round(t.css('border-bottom-width').split('px')[0]);//����� �����
		if(TBS != 'border-box') {
			h += Math.round(t.css('padding-top').split('px')[0]);//������ ������
			h += Math.round(t.css('padding-bottom').split('px')[0]);//������ �����
		}
		return h;
	}
	function hintSideAuto() {//�������������� ����������� ������� ��������� ���������
		if(o.side != 'auto')
			return o.side;

		var offset = t.offset(),
			screenW = $(window).width(), //������ ������ ������� �������
			screenH = $(window).height(),//������ ������ ������� �������
			scrollTop = $(window).scrollTop(),//���������� ������
			scrollLeft = $(window).scrollLeft(),//���������� �����
			diff = {//��������� ������������ ��� ����������� ��������� � ������� ����������
				top:offset.top - scrollTop - H - 6,
				bottom:screenH + scrollTop - offset.top - objH - H - 6 - slide,
				left:offset.left - scrollLeft - W - 6,
				right:screenW + scrollLeft - offset.left - objW - W - 6
			},
			minMinus = -9999,//����������� �����, ���� �� � ����� ������� ��� ���������� ������������
			minMinusSide;    //������� ������������ ������

		//����� �� ���������� ���� ���������
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
	function hintPosition(e) {//���������������� ��������� ����� �������
		var offset = t.offset();

		SIDE = hintSideAuto();

		UG.removeClass('ugb ugl ugt ugr');
		//���������������� ������
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

		//����������� ������� �������, �� ������� ����� ������������ ������
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
	function hintShow(e) {//������� ������ ���������
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
		//�������� �������� ���������
		function action() {
			process = 'showing';
			hintPosition(e);
			HINT.css({top:topStart, left:leftStart, opacity:0})
				.animate({top:topEnd, left:leftEnd, opacity:1}, o.speed, function() { process = 'showed' });
		}
	}
	function hintHide() {//������� ������� ���������
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
				//���������, ������� ������������� ������������, ���������
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

	t.find('.ttdiv').remove();
	t.addClass('_tooltip');
	t.append(
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>'
	);
	//�������������������� ���������
	if(!left) {
		var ttdiv = t.find('.ttdiv');
		left = Math.ceil(ttdiv.width() / 2) - 9;
		ttdiv.css('left', '-' + left + 'px');
	}

	return t;
};
$.fn._calendar = function(o) {
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id'),
		VALUE = t.val();

	if(!attr_id) {
		attr_id = 'calendar' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_calendar',
		S = window[win],
		n;

	o = $.extend({
		lost:1,                //���� �� 0, �� ����� ������� ��������� ���
		time:0,                //���������� �����
		tomorrow:0,            //������ "������" ��� ������� ��������� ���������� ����
		func:function () {}    //����������� ������� ��� ������ ���
	}, o);


	//�������� ������ �� ��������� ��� ��������� ������
	t.next().remove('._calendar');
	t.after(
		'<div class="_calendar" id="' + win + '">' +
			'<div class="icon icon-calendar"></div>' +
			'<input type="text" class="cal-inp" readonly />' +

		(o.time ?
			'<div class="dib ml8">' +
				'<input type="hidden" id="' + attr_id + '_hour"/>' +
			'</div>' +
			'<div class="dib b ml3 mr3">:</div>' +
			'<input type="hidden" id="' + attr_id + '_min"/>'
		: '') +

			'<div class="cal-abs dn">' +
				'<table class="cal-head">'+
					'<tr><td class="cal-back">' +
						'<td class="cal-mon">' +
						'<td class="cal-next">' +
				'</table>' +
				'<table class="cal-week"><tr><td>��<td>��<td>��<td>��<td>��<td>��<td>��</table>' +
				'<table class="cal-day"></table>' +
			'</div>' +
		'</div>'
	);

	if(o.time) {
		$('#' + attr_id + '_hour')._count({
			min:0,
			max:23,
			again:1,
			time:1
		});
		$('#' + attr_id + '_min')._count({
			step:10,
			min:0,
			max:50,
			again:1,
			time:1
		});
	}

/*
	if(o.tomorrow) {
		inp
			.after('<a class="dib ml10 grey">������</a>')
			.next().click(function() {
				var tmr = new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
				o.year = tmr.getFullYear();
				o.mon = tmr.getMonth() + 1;
				daySel(tmr.getDate());
			});
	}
*/

	var D = new Date(),
		CUR_YEAR = D.getFullYear(), //������� ���
		CUR_MON =  D.getMonth() + 1,//������� �����
		CUR_DAY =  D.getDate(),     //������� ����

		TAB_YEAR = CUR_YEAR,        //���, ������������ � ���������
		TAB_MON =  CUR_MON,         //�����, ������������ � ���������

		VAL_YEAR = CUR_YEAR,    //��������� ���
		VAL_MON =  CUR_MON,     //��������� �����
		VAL_DAY =  CUR_DAY,     //��������� ����

		CAL = t.next(),
		INP = CAL.find('.cal-inp'),        //��������� ����������� ���������� ���
		CAL_ABS = CAL.find('.cal-abs'), //���������� ���������
		TD_MON = CAL.find('.cal-mon'),  //������ td � ������� � �����
		TD_WEEK = CAL.find('.cal-week'),//������� � ���������� ������
		TAB_DAY = CAL.find('.cal-day'); //������� � �����

	valTest();
	tdMonUpd();
	dayPrint();

	INP.click(function() {
		if(CAL.hasClass('disabled'))
			return;
		var on = CAL_ABS.hasClass('dn');
		TAB_YEAR = VAL_YEAR;
		TAB_MON = VAL_MON;
		tdMonUpd();
		dayPrint();
		TD_WEEK._dn(1);
		TAB_DAY._dn(1, 'mon');
		CAL_ABS._dn(on);
	});
	CAL.find('.cal-back').click(back);
	CAL.find('.cal-next').click(next);
	TD_MON.click(function() {
		TD_WEEK._dn();
		TAB_DAY._dn(0, 'mon');
		TD_MON.html(TAB_YEAR);
		monPrint();
	});


	$(document)
		.off('click._calendar')
		.on('click._calendar', function(e) {
			var cur = $(e.target).closest('._calendar,.cal-tb'),//������� ���������
				attr = '';  //id �������� ���������

			//�������� ����������, ����� ������� ���� � �������
			//����� ��������, ���� ������� ���� �� ��
			if(cur.hasClass('_calendar'))
				attr = ':not(#' + cur.attr('id') + ')';

			if(cur.hasClass('cal-tb'))
				attr = ':not(#' + cur.attr('val') + ')';

			$('._calendar' + attr + ' .cal-abs')._dn();
		});

	function valTest() {//�������� �������� ��������, ���������, ���� ������������
		if(!VALUE.length)
			return valUpd();
		if(!REGEXP_DATE.test(VALUE))
			return valUpd();

		var ex = VALUE.split('-');
		if(!_num(ex[0]) || !_num(ex[1]) || !_num(ex[2]))
			return valUpd();

		VAL_YEAR = _num(ex[0]);
		VAL_MON =  _num(ex[1]);
		VAL_DAY =  _num(ex[2]);
		TAB_YEAR = VAL_YEAR;
		TAB_MON = VAL_MON;
		valUpd();
	}
	function valUpd() {//���������� ��������
		VALUE = TAB_YEAR + '-' + (TAB_MON < 10 ? '0' : '') + TAB_MON + '-' + (VAL_DAY < 10 ? '0' : '') + VAL_DAY;
		t.val(VALUE);
		INP.val(VAL_DAY + ' ' + MONTH_DAT[VAL_MON] + ' ' + VAL_YEAR);
	}
	function tdMonUpd() {
		TD_MON.html(MONTH_DEF[TAB_MON] + ' ' + TAB_YEAR);
	}
	function dayPrint() {//����� ������ ����
		var html = '<tr>',
			df = dayFirst(),
			dc = dayCount(TAB_YEAR, TAB_MON),
			cur = CUR_YEAR == TAB_YEAR && CUR_MON == TAB_MON,// ��������� �������� ���, ���� ������� ������� ��� � �����
			st =  VAL_YEAR == TAB_YEAR && VAL_MON == TAB_MON;// ��������� ���������� ���, ���� ������� ��� � ����� ���������� ���

		//��������� ������ �����
		if(df > 1)
			for(n = 0; n < df - 1; n++)
				html += '<td>';

		for(n = 1; n <= dc; n++) {
			var l = '';
			if(TAB_YEAR < CUR_YEAR) l = ' lost';
			else if(TAB_YEAR == CUR_YEAR && TAB_MON < CUR_MON) l = ' lost';
			else if(TAB_YEAR == CUR_YEAR && TAB_MON == CUR_MON && n < CUR_DAY) l = ' lost';
			var b = cur && n == CUR_DAY ? ' b' : '',
				set = st && n == VAL_DAY ? ' set' : '',
				sel = !l || l && o.lost ? ' sel' : '';
			html += '<td class="' + sel + set + b +	l + '">' + n;
			if(++df > 7 && n != dc) {
				html += "<tr>";
				df = 1;
			}
		}
		TAB_DAY
			.html('<tbody class="cal-tb" val="' + win + '">' + html + '</tbody>')
			.find('.sel').click(daySel);
	}
	function dayFirst() {//����� ������ ������ � ������
		var first = new Date(TAB_YEAR, TAB_MON - 1, 1).getDay();
		return first || 7;
	}
	function dayCount(year, mon) {//���������� ���� � ������
		mon--;
		if(!mon) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function daySel() {
		VAL_YEAR = TAB_YEAR;
		VAL_MON = TAB_MON;
		VAL_DAY = _num($(this).html());
		CAL_ABS._dn();
		valUpd();
		dayPrint();
	}
	function monPrint() {//����������� �������, ����� ������������� �� ����
		var html = '',
			cur = CUR_YEAR == TAB_YEAR,//��������� �������� ������, ���� ������� ������� ���
			st =  VAL_YEAR == TAB_YEAR,//��������� ���������� ������, ���� ������� ��� ��������� ������
			monn = {
				1:'���',
				2:'���',
				3:'���',
				4:'���',
				5:'���',
				6:'���',
				7:'���',
				8:'���',
				9:'���',
				10:'���',
				11:'���',
				12:'���'
			},
			tr = 3;

		for(n = 1; n <= 12; n++) {
			if(++tr > 3) {
				html += '<tr>';
				tr = 0;
			}
			var b = cur && n == CUR_MON ? ' b' : '',
				set = st && n == VAL_MON ? ' set' : '';
			html += '<td class="sel' + b + set + '" val="' + n + '">' + monn[n];
		}
		TAB_DAY
			.html('<tbody class="cal-tb" val="' + win + '">' + html + '</tbody>')
			.find('.sel').click(function() {
				TAB_MON = _num($(this).attr('val'));
				TAB_DAY._dn(1, 'mon');
				TD_WEEK._dn(1);
				tdMonUpd();
				dayPrint();
			});
	}
	function back() {//������������� ��������� �����
		if(TD_WEEK.hasClass('dn')) {
			TAB_YEAR--;
			TD_MON.html(TAB_YEAR);
			monPrint();
			return;
		}
		if(!--TAB_MON) {
			TAB_MON = 12;
			TAB_YEAR--;
		}
		tdMonUpd();
		dayPrint();
	}
	function next() {//������������� ��������� �����
		if(TD_WEEK.hasClass('dn')) {
			TAB_YEAR++;
			TD_MON.html(TAB_YEAR);
			monPrint();
			return;
		}
		if(++TAB_MON > 12) {
			TAB_MON = 1;
			TAB_YEAR++;
		}
		tdMonUpd();
		dayPrint();
	}


};
$.fn._search = function(o, v) {//��������� ������
	/*
		������������� input:text
		attr_id �� ����������
	*/
	var t = $(this),
		attr_id = t.attr('id'),
		VALUE = $.trim(t.val());

	if(!attr_id) {
		attr_id = 'search' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_search',
		S = window[win];

	switch(typeof o) {
		case 'number':
		case 'string':
			if(!S)
				break;
			if(o == 'val') {
				if(v) {
					S.inp(v);
					return S;
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
			return S;
	}

//	if(S && S.inp)
//		return S;

	o = $.extend({
		width:150,      //������. ���� 0 = 100%
		placeholder:'', //�����-���������
		focus:0,        //����� ������������� �����
		enter:0,        //��������� �������� ����� ������ ����� ������� �����
		func:function() {}
	}, o);

	//����� ������, ���� �� ��� �������� ����� PHP. ����� ������ ���������� �������
	if(!_parent(t, '._search').hasClass('_search')) {
		var width = ' style="width:' + (o.width ? o.width + 'px' : '100%') + '"',
			placeholder = o.placeholder ? ' placeholder="' + o.placeholder + '"' : '',
			html = '<div class="_search" id="' + attr_id + '_search"' + width + '>' +
				'<table class="w100p">' +
					'<tr><td class="w15 pl5">' +
							'<div class="icon icon-search curD"></div>' +
						'<td><input type="text" id="' + attr_id + '"' + placeholder + ' value="' + VALUE + '" />' +
						'<td class="w25 center">' +
							'<div class="icon icon-del pl' + _dn(VALUE) + '"></div>' +
				'</table>' +
			'</div>';

		t.after(html).remove();
	}

	var SEARCH = $('#' + win),
		INP = $('#' + attr_id),
		DEL = SEARCH.find('.icon-del');

	t = INP;

	if(o.focus)
		t.focus();

	INP.keydown(function(e) {
		setTimeout(function() {
			VALUE = $.trim(INP.val());
			DEL._dn(VALUE);
			if(o.enter && e.which != 13)
				return;
			o.func(VALUE, t);
		}, 0);
	});
	DEL.click(function() {
		if(DEL.hasClass('spin'))
			return;
		t.clear();
		o.func('', t);
		t.focus();
	});

	t.inp = function(v) {
		if(!v)
			return VALUE;
		VALUE = $.trim(v);
		INP.val(VALUE);
		DEL.removeClass('dn spin');
	};
	t.icon_del = DEL;
	t.clear = function() {//�������� �����������
		INP.val('');
		DEL.addClass('dn');
	};
	t.process = function() {//����� �������� ��������
		DEL.addClass('spin');
	};
	t.isProcess = function() {//�����������, � �������� �� �����
		return DEL.hasClass('spin');
	};
	t.cancel = function() {//������ �������� ��������
		DEL.removeClass('spin');
	};

	window[win] = t;

	return t;
};
$.fn._menu = function(o) {//����
	var tMain = $(this),
		attr_id = tMain.attr('id'),
		val = _num(tMain.val()),
		win = attr_id + '_menu',
		n,
		S;

	if(!attr_id)
		return;

	S = window[win];

	switch(typeof o){
		case 'number':
		case 'string':
		case 'boolean':
			var v = _num(o);
			S.value(v);
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

	var mainDiv = tMain.next(),
		link = mainDiv.find('.link');

	link.click(_click);

	function _init() {
		var html = '<div class="_menu' + o.type + '" id="' + attr_id + '_menu">';

		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n],
				sel = val == sp.id ? ' sel' : '';
			html +=
				'<a class="link' + sel + '" val="' + sp.id + '">' +
					sp.title +
				'</a>';
		}

		html += '</div>';

		tMain.next().remove('._menu' + o.type);
		tMain.after(html);
	}
	function _click() {
		var t = $(this),
			v = _num(t.attr('val'));
		link.removeClass('sel');
		t.addClass('sel');
		tMain.val(v);
		_pageCange(v);
		o.func(v, S);
	}
	function _pageCange(v) {
		_forN(o.spisok, function(sp) {
			$('.' + attr_id + '-' + sp.id)._dn(v == sp.id);
		});
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
$.fn._dropdown = function(o) {//���������� ������ � ���� ������
	var t = $(this);

	if(!t.length)
		return;

	var attr_id = t.attr('id');
	if(!attr_id) {
		attr_id = 'dropdown' + Math.round(Math.random() * 100000);
		t.attr('id', attr_id);
	}

	var win = attr_id + '_dropdown',
		S = window[win],
		VALUE = _num(t.val());

	o = $.extend({
		head:'',    //���� �������, �� �������� � �������� ������, � ������ �� spisok
		nosel:0,    //�� ��������� �������� ��� ������ ��������
		title0:'',
		grey:0,     //���������� �����, ���� �������� �� �������
		disabled:0,
		spisok:[],
		func:function() {}
	}, o);

	if(o.title0)
		o.head = o.title0;

	t.next().remove('._dropdown');

	var dis = o.disabled ? ' disabled' : '',
		html =  '<div class="_dropdown' + dis + '" id="' + win + '">' +
					'<a class="dd-head">' + o.head + '</a>' +
					'<div class="dd-list"></div>' +
				'</div>';
	t.after(html);

	var DDN = t.next(),
		HEAD = DDN.find('.dd-head'),
		LIST = DDN.find('.dd-list'),
		timer = 0,
		MASS = [],
		MASS_ASS = {};

	massCreate();
	spisokPrint();
	valueSet(VALUE);

	var DDU = DDN.find('.ddu');

	$(document)
		.off('click._dropdown')
		 .on('click._dropdown', function(e) {//�������� ���� ������� ��� ������� �� ����� ����� �� ������
			var cur = $(e.target).parents('._dropdown'),
				attr = '';

			//�������� ��������, ����� ������� ���� � �������
			if(cur.hasClass('_dropdown'))
				attr = ':not(#' + cur.attr('id') + ')';

			$('._dropdown' + attr + ' .dd-list').hide();
		});

	HEAD.on('click mouseenter', function() {
		timerClear();
		_forEq(DDU, function(sp) {
			if(VALUE == sp.attr('val')) {
				sp.addClass('on');
				return false;
			}
		});
		LIST.show();
	});
	DDU.click(function() {
		timerClear();
		LIST.hide();
		var tt = $(this),
			v = _num(tt.attr('val'));
		valueSet(v);
	});
	DDU.mouseenter(function() {
		DDU.removeClass('on');
	});
	LIST.on({
		mouseleave:function () {
			timer = setTimeout(function() {
				LIST.fadeOut(200);
			}, 500);
		},
		mouseenter:timerClear
	});

	function massCreate() {//�������� ������� ��� ����������� ������ ������
		var unit;

		if(o.title0)
			MASS_ASS[0] = o.title0;

		//�������� ������ �������� ������������� ��������
		if(!o.spisok.length) {
			_forIn(o.spisok, function(sp, id) {
				id = _num(id);
				if(!id)
					return;
				MASS_ASS[id] = sp;
				unit = {
					id:id,
					title:sp
				};
				MASS.push(unit);
			});
			return;
		}

		//�������� ������ �������� ���������������� ��������
		_forN(o.spisok, function(sp, n) {
			var id,
				title;

			//�������� �� ���������� ���������������� ������
			if(typeof sp == 'number' || typeof sp == 'string') {
				id = n + 1;
				title = sp;
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
			}

			MASS_ASS[id] = title || ' ';
			title = title || '&nbsp;';
			unit = {
				id:id,
				title:title
			};
			MASS.push(unit);
		});
	}
	function spisokPrint() {//����� ������
		html = '<div class="dd-sel">' + o.head + '</div>';

		if(o.title0)
			html += '<div class="ddu title0" val="0">' + o.title0 + '</div>';

		_forN(MASS, function(sp) {
			var on = VALUE == sp.id ? ' on' : '';
			html += '<div class="ddu' + on + '" val="' + sp.id + '">' +
						sp.title +
					'</div>';
		});

		LIST.html(html);
	}
	function valueSet(v) {
		HEAD.html(MASS_ASS[v])._dn(v, 'grey');
		DDN.find('.dd-sel').html(MASS_ASS[v]);
		VALUE = v;
		t.val(v);
	}
	function timerClear() {
		if(!timer)
			return;
		clearTimeout(timer);
		timer = 0;
	}

	window[win] = t;
	return t;
};



