/* ��� �������� ����������� �����������, ������������ � ���������� */
var VK_SCROLL = 0,
	ZINDEX = 0,
	BC = 0,

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

	_backfon = function(add) {//������ ��� ��� �������� ������������� ����
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

	_dialog = function(o) {//���������� ����
		o = $.extend({
			top:100,
			width:380,
			mb:0,       //margin-bottom: ������ ����� �� ������� (��� ��������� ��� ���������� �������)
			padding:10, //������ ��� content
			dialog_id:0,//ID �������, ������������ �� ����
			color:'',   //���� ������� - ��������� � ������
			id:'',      //������������� �������: ��� ����������� ��������� ������ ��, ����� �������������� ��������� ���
			head:'head: �������� ���������',
			load:0, // ����� �������� �������� �������� � ������ �������
			class:'',//�������������� ����� ��� content
			content:'content: ���������� ������������ ����',
			submit:function() {},
			cancel:function() {},
			butSubmit:'������',
			butCancel:'������'
		}, o);

		var frameNum = $('.dFrame').length;

		//�������� ������� � ��� �� ���������������
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
					'<div class="ms center red">� �������� �������� ��������� ������.</div>' +
				'</div>';

		var html =
			'<div class="_dialog"' + (o.id ? ' id="' + o.id + '_dialog"' : '') + '>' +
				'<div class="head ' + o.color + '">' +
					'<div class="close fr curP"><a class="icon icon-del wh"></a></div>' +
		            '<div class="edit fr curP dn"><a class="icon icon-edit wh"></a></div>' +
				'<div class="fs14 white">' + o.head + '</div>' +

				'</div>' +
				'<div class="dcntr">' +
					'<iframe class="dFrame" name="dFrame' + frameNum + '"></iframe>' +
					'<div class="content bg-fff' + (o.class ? ' ' + o.class + '_dialog' : '') + '"' + (o.padding ? ' style="padding:' + o.padding + 'px"' : '') + '>' +
						o.content +
					'</div>' +
				'</div>' +
				'<div class="btm">' +
					'<button class="vk submit mr10 ' + o.color + (o.butSubmit ? '' : ' dn') + '">' + o.butSubmit + '</button>' +
					'<button class="vk cancel' + (o.butCancel ? '' : ' dn') + '">' + o.butCancel + '</button>' +
				'</div>' +
			'</div>';

		// ���� ����������� ������ ������ �� ��������, ������������ ��������� ������������ ������ ��������
		if(frameNum == 0)
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
			w2 = Math.round(o.width / 2); // ������/2. ��� ����������� ��������� �� ������
		dialog.find('.close').click(dialogClose);
		butSubmit.click(submitFunc);
		butCancel.click(function(e) {
			e.stopPropagation();
			dialogClose();
			o.cancel();
		});

		//��� ���� input ��� ������� enter ����������� submit
		content.find('input').keyEnter(submitFunc);

		_backfon();

		dialog.css({
			width:o.width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:$(document).width() / 2 - w2 + 'px',
			'z-index':ZINDEX + 5
		});
		iconEdit.click(function() {
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
				color:'red',
				pad:10,
				show:1,
				remove:1
			});
		}
		function loadError(msg) {//������ �������� ������ ��� �������
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
			load:function(send, func) {//�������� �������� � ������� ��� ��������� � ���������� ����. ���� ������ - ����� ���������
				$.post(AJAX, send, function(res) {
					if(res.success) {
						content.html(res.html);
						if(typeof func == 'function')
							func(res);
					} else
						loadError(res.text);
				}, 'json');
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
						if(res.delem_id)
							$('#delem' + res.delem_id)._flash({color:'red'});
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
			},
			iconEdit:function(v) {//�����/������� ������ ��������������� �������
				if(v == 'hide')
					iconEdit.addClass('dn');
				if(v == 'show')
					iconEdit.removeClass('dn');
			}
		};
	},

	_dialogEdit = function(dialog_id) {//��������|�������������� ����������� ����
		dialog_id = _num(dialog_id);
		var dialog = _dialog({
				dialog_id:dialog_id,
				color:'orange',
				width:500,
				top:20,
				padding:0,
				head:'��������� ����������� ����',
				load:1,
				submit:submit,
				cancel:function() {
					_dialogOpen(dialog_id);
				}
			}),
			send = {
				op:'dialog_edit_load',
				dialog_id:dialog_id
			},
			DIALOG_WIDTH;

		window.LABEL_WIDTH = 125;

		dialog.load(send, loaded);

		function loaded(res) {
			dialog_id = res.dialog_id;
			dialog.width(res.width);
			dialog.content.html(res.html);
			dialog.butSubmit((dialog_id ? '���������' : '�������') + ' ���������� ����');

			window.CMP_NAME = res.cmp_name;
			window.DIALOG_ELEMENT = res.element;
			window.DIALOG_COMPONENT = res.component;
			window.COMPONENT_FUNC = res.func;
			window.SPISOK_ON = res.spisokOn;

			_dialogScript(res.component, 1);

			$('#spisok_on')._check({
				func:function(v) {
					$('#tr_spisok_name')[(v ? 'remove' : 'add') + 'Class']('dn');
				}
			});

			$('#app_any')._check();
			$('#sa')._check();
			sortable();
			elementFuncFunc();
			elementFuncEdit();
			elementFuncDel();

			$('#dialog-menu')._menu({
				type:4,
				spisok:res.menu,
				func:_dialogHeightCorrect
			});
			$('#action_id')._select({
				width:250,
				title0:'���',
				spisok:res.action,
				func:function(v) {
					$('.td-action-page')._dn(v == 2);
					$('#action_page_id')._select(0);
				}
			});
			$('#action_page_id')._select({
				width:250,
				title0:'�� �������',
				spisok:res.page_list
			});

			_dialogHeightCorrect();

			//��������� ����� ��� ��������� ������ �������
			DIALOG_WIDTH = res.width;
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

			//��������� ����� ��� ��������� ������ ����� � ����������
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

			//���������/���������� ���������� �������
			$('#dialog-cmp-block').click(function() {
				_dialogCmpBlock();
			});
		}
		function elementFuncFunc() {//�������� ���� ��������� ������� ����������
			$(document).off('click', '.cmp-set .icon-usd');
			$(document).on('click', '.cmp-set .icon-usd', function() {
				var p = _parent($(this), 'DD'),
					id = _num(p.attr('val'), 1),
					cmp = {};
				_forN(DIALOG_COMPONENT, function(sp, n) {
					cmp = DIALOG_COMPONENT[n];
					if(sp.id == id)
						return false;
				});
				_dialogCmpEditFunc(cmp);
			});
		}
		function elementFuncEdit() {//������� �������������� ����������
			$(document).off('click', '.cmp-set .icon-edit');
			$(document).on('click', '.cmp-set .icon-edit', function() {
				var p = _parent($(this), 'DD'),
					id = _num(p.attr('val'), 1),
					cmp = {};
				_forN(DIALOG_COMPONENT, function(sp, n) {
					cmp = DIALOG_COMPONENT[n];
					if(sp.id == id)
						return false;
				});
				_dialogCmpEdit(cmp);
			});
		}
		function elementFuncDel() {//������� �������� ����������
			$(document).off('click', '.cmp-set .icon-del-red');
			$(document).on('click', '.cmp-set .icon-del-red', function() {
				var p = _parent($(this), 'DD'),
					id = _num(p.attr('val'));
				p.remove();
				_forN(DIALOG_COMPONENT, function(sp, n) {
					if(sp.id != id)
						return;
					DIALOG_COMPONENT.splice(n, 1);
				});
				_dialogHeightCorrect();
			});
		}
		function submit() {
			send = {
				op:'dialog_' + (dialog_id ? 'edit' : 'add'),
				dialog_id:dialog_id,
				app_any:$('#app_any').val(),
				sa:$('#sa').val(),
				width:DIALOG_WIDTH,
				label_width:LABEL_WIDTH,
				head_insert:$('#head_insert').val(),
				button_insert_submit:$('#button_insert_submit').val(),
				button_insert_cancel:$('#button_insert_cancel').val(),
				head_edit:$('#head_edit').val(),
				button_edit_submit:$('#button_edit_submit').val(),
				button_edit_cancel:$('#button_edit_cancel').val(),
				base_table:$('#base_table').val(),
				component:DIALOG_COMPONENT,
				func:COMPONENT_FUNC,
				spisok_on:$('#spisok_on').val(),
				spisok_name:$('#spisok_name').val(),
				action_id:$('#action_id').val(),
				action_page_id:$('#action_page_id').val(),
				menu_edit_last:$('#dialog-menu').val()
			};
			dialog.post(send, function(res) {
				_dialogOpen(res.dialog_id);
			});
		}
	},
	_dialogHeightCorrect = function() {//��������� ������ ����� ��� ��������� ������ ������� � ������ ����� � ����������
		var h = $('#dialog-w-change').parent().height();
		$('#dialog-w-change').height(h);
		h = $('#dialog-base').height();
		$('#label-w-change').height(h);
	},
	_dialogCmpEdit = function(CMP) {//����������|�������������� ���������� �������
		CMP = $.extend({
			id:0,
			type_id:0,
			col_name:'',    //��� ������� (��� SA)
			label_name:'',  //�������� ����
			require:0,      //���� "��������� ������������ ����������"
			hint:'',        //����� ���������
			width:0,        //����� �������� (��� input, textarea, select)
			num_1:0,
			num_2:0,
			num_3:0,
			num_4:0,
			num_5:0,
			txt_1:'',
			txt_2:'',
			v:[]
		}, CMP);

		var TYPE_ID = CMP.type_id,//��������� �������
			dialog = _dialog({
				width:550,
				top:30,
				padding:0,
				color:'orange',
				head:'��������� ���������� �������',
				content:elContentHtml(),
				butSubmit:'',
				submit:submit
			}),
			EL_VAL_ASS = [];

		elSelScript();
		elEditScript();

		//����� ������ ��������� ��� ���������
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

		function elContentHtml() {//����� ������ ��������� ��� ������ ���������� �������
			if(TYPE_ID)
				return '';
			var html = '';
			for(var k in DIALOG_ELEMENT) {
				var sp = DIALOG_ELEMENT[k];
				html += '<div val="' + k + '" class="over1 line-b element ' + sp.css + '">' + sp.name + '</div>';
			}
			return html;
		}
		function elSelScript() {//����� ������ �������� �� ���� ������
			if(TYPE_ID)
				return;
			dialog.content.find('.element').click(function() {
				TYPE_ID = _num($(this).attr('val'));
				dialog.content.slideUp(200, function() {
					$(this).html(DIALOG_ELEMENT[TYPE_ID].html)
						   .slideDown(200, elScript);
					dialog.butSubmit('�������� ���������');
				});
			});
		}
		function elEditScript() {//����������� ���� �������������� ��������
			if(!TYPE_ID)
				return;

			dialog.content.html(DIALOG_ELEMENT[TYPE_ID].html);
			$('#col_name').val(CMP.col_name);
			$('#label_name').val(CMP.label_name);
			$('#label-require').val(CMP.require);
			$('#label-hint').val(CMP.hint);
			$('#txt_1').val(CMP.txt_1);

			elScript();
			dialog.butSubmit('��������� ���������');
		}
		function elScript() {//���������� �������� ��� ����������� ��������
			var labelPrevUpdate = function() {//���������� ���������������� ��������� label
					var txt = $.trim($('#label_name').val()),
						require = _bool($('#label-require').val()),
						hint = $.trim($('#label-hint').val());
					txt =
						(txt ? txt + ':' : '') +
						(require ? '<div class="dib red fs15 mtm2">*</div>' : '') +
						(hint ? ' <div class="icon icon-info pl dialog-hint-edit"></div>' : '');
					$('#label-prev').html(txt);
				},
				elPrevAction = function() {};//��������, ������� ����������� � ���������� �������� � ��������������� ���������

			$('#label_name').keyup(labelPrevUpdate).focus();

			$('#label-require')._check({
				title:'��������� ������������ ����������',
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
						tooltip:'������������ ������� ��������',
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
						but1 = em.find('button:first'),//������������ ��������
						but2 = em.find('button:eq(1)'),//��� ������
						but3 = em.find('button:eq(2)'),//����� �������� �������
						but4 = em.find('button:eq(3)');//������ �������� ��������
					$.fn.selCancel = function() {//������ ������
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
									'<div class="_info w400 dib">����� ���������� �������, ������� �������� ��������.</div>' +
								'<tr><td class="label r w150">����������:' +
									'<td class="i b w150">��� ������� �������' +
									'<td><div class="icon icon-del mbm5' + _tooltip('�������� �����', -52) + '</div>' +
								'<tr><td>' +
									'<td colspan="2"><input type="hidden" id="num_5" value="' + CMP.num_5 + '" />' +
							'</table>'
						);
						em.selCancel();
						$('#num_5')._check({
							light:1,
							title:'���������� ������ ������ � ������� ��������'
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
						em.after(elObjSelect(CMP, '����� �������� ����������� �������-������.' +
												  '<br />' +
												  '��� ��������� �������� ������, ����� �������, ' +
												  '�� ������� ����� ������������� ����������� ����������.',
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
									'<div class="_info w400 dib">����� �� �������, ������� ������� ����������� �� ��������.</div>' +
								'<tr><td class="label r w150">����������:' +
									'<td class="i b w200">������ �������� ��������' +
									'<td><div class="icon icon-del mbm5' + _tooltip('�������� �����', -52) + '</div>' +
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
				case 6: /* ��������� */ {
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
					var html = elObjSelect(CMP, '�������� �������� � �������');
					$('#connect-head').after(html);
					elObjSelect(CMP);
					break;
				}
				case 9: /* ��������� */ {
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
							{uid:1,title:'�� ����� ����'},
							{uid:2,title:'� ������ ��������������'}
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
		function elVal(func, obj, lastNoDel) {//��������, ������� �������� �������� Radio, Select
			var DL = obj.after('<dl class="mt10"></dl>').next(),
				NUM = 1,
				valAdd = function(v) {
					v = $.extend({
						id:0,
						uid:NUM,
						title:'��� �������� ' + NUM,
						def:0
					}, v);

					DL.append(
						'<dd class="curM over1 ml20 mr20" val="' + v.id + '">' +
							'<table class="bs5 w100p">' +
								'<tr><td class="label r w150">�������� ' + NUM + ':' +
									'<td><input type="text" class="w230 mr5" id="el-val-' + v.uid + '" val="' + v.uid + '" value="' + v.title + '" />' +
										'<input type="hidden" id="el-def-' + v.uid + '" val="' + v.uid + '" value="' + v.def + '" />' +
										'<div val="' + v.uid + '" class="icon icon-del ml20 prel top5' + _tooltip('������� ��������', -55) + '</div>' +
							'</table>' +
						'</dd>'
					);

					DL.sortable({
						axis:'y',
						update:function () {
							var dd = DL.find('dd');
							EL_VAL_ASS = [];
							for(var n =0; n < dd.length; n++) {
								var eq = dd.eq(n);
								EL_VAL_ASS.push({
									id:_num(eq.attr('val')),
									uid:_num(eq.find('.icon-del').attr('val')),
									title:eq.find('input[type="text"]').val(),
									def:_num(eq.find('input[type="hidden"]').val())
								});
							}
							func();
						}
					});

					$('#el-def-' + v.uid)._check({
						tooltip:'������������ �� ���������',
						func:function(v, attr_id) {
							var uid = _num($('#' + attr_id).attr('val'));
							for(var n = 0; n < EL_VAL_ASS.length; n++) {
								var sp = EL_VAL_ASS[n];
								sp.def = sp.uid == uid ? v : 0;
							}

							func();

							if(!v)
								return;

							//������ ������� � ��������� ��������
							var DEF = DL.find('input[type="hidden"]');
							for(var n = 0; n < DEF.length; n++) {
								var sp = DEF.eq(n);
								if(sp.attr('id') == attr_id)
									continue;
								sp._check(0);
							}
						}
					});

					$('#el-val-' + v.uid).keyup(function() {
						var t = $(this),
							uid = _num(t.attr('val'));
						_forN(EL_VAL_ASS, function(sp) {
							if(sp.uid != uid)
								return;
							sp.title = t.val();
							sp.content = t.val();
						});
						func();
					}).select();

					DL.find('.icon-del:last').click(function() {
						if(lastNoDel && EL_VAL_ASS.length < 2)
							return;

						var t = $(this),
							uid = _num(t.attr('val')),
							p = _parent(t, 'DD');
						_forN(EL_VAL_ASS, function(sp, n) {
							if(sp.uid != uid)
								return;
							EL_VAL_ASS.splice(n, 1);
						});
						p.remove();
						func();
						if(!EL_VAL_ASS.length) {
							DL.next().remove();
							DL.prev().removeClass('dn');
							DL.remove();
							CMP.v = [];
						}
					});
					EL_VAL_ASS.push({
						id:v.id,
						uid:v.uid,
						title:v.title,
						def:v.def
					});
					NUM++;
					func();
				};

			//������ ���������� ������ ��������
			DL.after(
				'<div class="center mt5">' +
					'<button class="vk cancel">�������� ��������</button>' +
				'</div>'
			).next().find('button').click(valAdd);

			if(CMP.id) {
				for(var n = 0; n < CMP.v.length; n++)
					valAdd(CMP.v[n]);
				if(!CMP.v.length)
					valAdd();
			} else
				valAdd();
		}
		function elObjSelect(CMP, html, del) {//����� ������� ��� ������ ��� ������ _select
			/*
				CMP - ������ ��������
				html - ���������� � ���� html, ���� ��������� ������. �������� ���������
			*/
			if(html)
				return  '<table class="bs5 w100p">' +
							'<tr><td colspan="3" class="center">' +
								'<div class="_info w400 dib">' + html + '</div>' +
							'<tr><td class="label r topi w175">������:' +
								'<td class="w230"><input type="hidden" id="num_1" value="' + CMP.num_1 + '" />' +
								'<td>' +
							 (del ? '<div class="icon icon-del mbm5' + _tooltip('�������� �����', -52) + '</div>' : '') +
							'<tr><td class="label r topi">��� �������:' +
								'<td colspan="2">' +
									'<input type="hidden" id="num_2" value="' + CMP.num_2 + '" />' +
						'</table>';

			//���������� ��������
			var menuColSet = function(v) {
				if(!v) {
					$('#num_2')
						._select('title0', '������� �������� ������')
						._select('empty');
					return;
				}

				$('#num_2')._select(0);
				var send = {
					op:'dialog_spisok_on_col_load',
					dialog_id:v
				};
				$('#num_2')._select('load', send, function() {
					$('#num_2')._select('title0', '�� �������')
				});
			};
			$('#num_1')._select({
				width:220,
				title0:'�� ������',
				spisok:SPISOK_ON,
				func:menuColSet
			});
			$('#num_2')._select({
				width:220,
				title0:'������� �������� ������'
			});
			menuColSet(CMP.num_1);
			$('#num_2')._select(CMP.num_2);

		}
		function submit() {
			var rand = CMP.id || Math.round(Math.random() * 10000),//��������� ����� ��� �������� ID ��������
				attr_id = 'elem' + rand,
				elem = {
					attr_id:'#' + attr_id,
					id:CMP.id || rand * -1,
					type_id:TYPE_ID,
					col_name:$.trim($('#col_name').val()),
					label_name:$.trim($('#label_name').val()),
					require:_bool($('#label-require').val()),
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

			//������������ ���������� � �������� �� ������
			switch(TYPE_ID) {
				case 1://check
					if(!elem.label_name && !elem.txt_1) {
						dialog.err('������� �������� ����,<br />���� ����� ��� �������');
						$('#label_name').focus();
						return;
					}
					break;
				case 2://select
					if(elem.num_3 && !elem.txt_1) {
						dialog.err('�� ������ ����� �������� ��������');
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
				case 6://���������
					break;
				case 7://info
					if(!elem.txt_1) {
						dialog.err('�������� ����� ����������');
						$('#txt_1').focus();
						return;
					}
					inp = '<div class="_info">' + _br(elem.txt_1, 1) + '</div>';
					break;
				case 8://connect
					inp = '<div class="grey i">��������� ���������</div>';
					break;
				case 9://���������
					if(!elem.txt_1) {
						dialog.err('�������� ����� ���������');
						$('#txt_1').focus();
						return;
					}
					inp = '<div class="hd' + elem.num_1 + '">' + elem.txt_1 + '</div>';
					break;
			}

			var DD =
					'<dd class="over1 curM prel" val="' + elem.id + '">' +
						'<div class="cmp-set">' +
							'<div class="icon icon-edit mr3' + _tooltip('��������� ���������', -66) + '</div>' +
							'<div class="icon icon-del-red' + _tooltip('������� ���������', -59) + '</div>' +
						'</div>' +
						'<table class="bs5 w100p">' +
							'<tr><td class="label label-width ' + (TYPE_7 ? '' : 'r') +' pr5" ' + (TYPE_7 ? 'colspan="2"' : 'style="width:' + LABEL_WIDTH + 'px"') + '>' +
									(elem.label_name ? elem.label_name + ':' : '') +
									(elem.require ? '<div class="dib red fs15 mtm2">*</div>' : '') +
									(elem.hint ? ' <div class="icon icon-info pl dialog-hint" val="' + _br(elem.hint, 1) + '"></div>' : '') +
				(!TYPE_7 ? '<td>' : '') +
								inp +
						'</table>' +
					'</dd>',
				DD_ED;//����� ��� ��������� ������� (��� _flash)



			//������� ����������
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
	_dialogCmpEditFunc = function(CMP) {//��������� ������� ����������
		var dialog = _dialog({
				width:500,
				top:30,
				padding:0,
				color:'orange',
				head:'������� ���������� �������',
				content:
					'<div class="hd1">��������� <b class="fs15">' + CMP_NAME[CMP.type_id] + '</b> <u>' + CMP.label_name + '</u></div>' +
					'<div id="cmp-func-add" class="center over1 mar20 pad10 curP b">����� �������</div>',
				butSubmit:'���������',
				submit:submit
			}),
			FC = COMPONENT_FUNC[CMP.id],
			NUM = 1,
			COND_SHOW = {//���������� ��� ��� ������� �� ��������� ��������
				1:1,
				2:1
			},
			CMP_SHOW = {//���������� ��� ��� ���������� �� ��������� ��������
				1:1,
				2:1,
				3:1,
				4:1
			};

		cmpFuncHtml();
		$('#cmp-func-add').click(cmpFuncUnit);

		function cmpFuncHtml() {//����� ������ ������� ����������
			if(!FC)
				return '';

			var html = '';
			_forN(FC, function(sp) {
				html += cmpFuncUnit(sp);
			});
			return html;
		}
		function cmpFuncUnit(v) {//������������ ����� �������
			v = $.extend({
				action_id:0,
				cond_id:0,
				ids:0
			}, v);
			var html =
				'<div class="cmp-func bor-e8 bg-gr2 mar20 pad10 pt1" val="' + NUM + '">' +
					'<div class="hd2 mt5">' +
						'������� ' + NUM + ':' +
						'<div class="icon icon-del fr" id="cmp-func-del' + NUM + '"></div>' +
					'</div>' +
					'<table class="bs5 mt10 w100p" id="act-tab' + NUM + '">' +
						'<tr><td class="label r w100">��������:' +
							'<td><input type="hidden" id="cmp-func-act' + NUM + '" value="' + v.action_id + '" />' +
					'</table>' +
					'<table class="bs5' + _dn(COND_SHOW[v.action_id]) + '" id="cond-tab' + NUM + '">' +
						'<tr><td class="label r w100 topi">�������:' +
							'<td><input type="hidden" id="cmp-func-cond' + NUM + '" value="' + v.cond_id + '" />' +
					'</table>' +
					'<table class="bs5 w100p' + _dn(CMP_SHOW[v.action_id]) + '" id="cmp-tab' + NUM + '">' +
						'<tr><td class="label r w100 topi">����������:' +
							'<td><input type="hidden" id="cmp-func-ids' + NUM + '" value="' + v.ids + '" />' +
					'</table>' +
				'</div>';
			$('#cmp-func-add').before(html);

			$('#cmp-func-del' + NUM)
				._tooltip('������� ������� ' + NUM)
				.click(function() {
					$(this).parent().parent().remove();
				});

			$('#cmp-func-act' + NUM)._select({
				width:250,
				title0:'�� �������',
				spisok:[
					{uid:1,title:'������'},
					{uid:2,title:'��������'},
					{uid:3,title:'������=0 / ��������=1',
						content:'������=0 / ��������=1' +
								'<div class="grey fs12">�������� ��� ������� ��������</div>' +
								'<div class="grey fs12">���������� ��� ���������</div>'
					},
					{uid:4,title:'������=1 / ��������=0',
						content:'������=1 / ��������=0' +
								'<div class="grey fs12">�������� ��� ��������� ��������</div>' +
								'<div class="grey fs12">���������� ��� �������</div>'
					},
					{uid:5,title:'����� ���������� ������� ������',
						content:'����� ���������� ������� ������' +
								'<div class="grey fs12">����������� ��� ������� � ���� �������, ����� �������� ��������� ��� ���.</div>'
					},
					{uid:6,title:'����� ����� �� ������� ������',
						content:'����� ����� �� ������� ������' +
								'<div class="grey fs12">����������� ��� ������� � ���� Radio, ����� ������� ������ ����.</div>'
					},
					{uid:7,title:'����������� ������� ������',
						content:'����������� ������� ������' +
								'<div class="grey fs12">����������� ����������� ������� �� ������.</div>'
					}
				],
				func:function(v, attr_id) {
					var num = attr_id.split('act')[1];
					$('#cond-tab' + num)._dn(COND_SHOW[v]);
					$('#cmp-tab' + num)._dn(CMP_SHOW[v]);
				}
			});

			$('#cmp-func-cond' + NUM)._radio({
				title0:'��� �������',
				light:1,
				spisok:[
					{uid:1,title:'��� ������� ��������'},
					{uid:2,title:'��� ��������� ��������'}
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
				title0:'�� �������',
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
					dialog.err('�� ������� �������� � ������� ' + num);
					return false;
				}
				if(CMP_SHOW[f.action_id] && f.ids == 0) {
					$('#cmp-tab' + num)._flash({color:'red'});
					dialog.err('�� ������� ���������� � ������� ' + num);
					return false;
				}

				arr.push(f);
			}))
				return;

			COMPONENT_FUNC[CMP.id] = arr;
			dialog.close();
		}
	},

	_dialogCmpBlock = function(t, block_id) {//���������/���������� ��������� ������ ��� �������
		var on = t.hasClass('grey'),
			send = {
				op:'spisok_tmp_block_' + (on ? 'on' : 'off'),
				block_id:block_id
			};

		if(t.hasClass('_busy'))
			return;

		t.addClass('_busy');

		_post(send, function(res) {
			butOn(on);
			$('#tmp-elem-list').html(res.html);
			if(!on) {
				BLOCK_ARR = res.block_arr;
				return;
			}
			$('#grid-stack')._grid({
				width:res.w,
				parent_id:block_id,
				is_spisok:block_id,
				funcAfterSave:function(res) {
					$('#spisok-unit-block-level').html(res.level);
					$('#tmp-elem-list').html(res.html);
					BLOCK_ARR = res.block_arr;
					butOn(0);
				},
				funcCancel:function() {
					t.trigger('click');
				}
			});
		}, function() {
			t.removeClass('_busy');
		});

		function butOn(v) {
			t.removeClass('_busy');
			var val = t.attr('val'),
				html = t.html();
			t._dn(v, 'grey');
			t._dn(!v, 'orange');
			t.html(val);
			t.attr('val', html);
		}
	},

	_dialogOpen = function(dialog_id, unit_id, unit_id_dub, funcAfterPost) {//�������� ����������� ����
		dialog_id = _num(dialog_id);
		unit_id = _num(unit_id);
		var dialog = _dialog({
				dialog_id:dialog_id,
				width:500,
				top:20,
				head:'������',
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
			dialog.content.html(res.html);
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
				func:{},//�������� �������, ���� ��������� ���������. ��������� ���� ��� �������� 5,6
				page_id:PAGE_ID,
				block_id:window.BLOCK_ID || 0
			};

			_forN(cmp, function(sp) {
				if(!sp.func_flag)
					return;

				send.elem[sp.id] = $(sp.attr_id).val();

				//���������� �������� �������
				funcVal(sp.id, send.func);
			});

			dialog.post(send, function(res) {
				if(funcAfterPost) {
					funcAfterPost(res);
					return;
				}
				switch(res.action_id) {
					case 1: location.reload(); break;
					case 2: location.href = URL + '&p=' + res.page_id + '&id=' + res.unit_id; break;
				}
			});
		}
		function funcVal(id, sf) {//��������� �������� �������
			var func = COMPONENT_FUNC,
				v = [], //���������� ��� ����� ��������
				join = 1;//����������� �� ����������� ��������� ����� �����

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
					if($('#colNameShow').length) {//181 �������
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
/*
					if($('#tmp-elem-list').length) {//182 ������
						_forEq($('#tmp-elem-list .pe'), function(eq) {
							var k = _num(eq.attr('val'));
							ELEM_ARR[k].pe = '';
							v.push(ELEM_ARR[k]);
						});
						join = 0;
					}
*/
				}
			});
			sf[id] = join ? v.join(',') : v;
		}
	},
	_dialogScript = function(component, isEdit) {//���������� �������� ����� �������� ������ �������

		//��������� �����������
		_forN(component, function(ch) {
			_dialogCmpScript(ch, isEdit);
		});

		//���������� �������, ����������� � �����������
		_forN(component, function(ch) {
			_dialogCmpFunc(ch.id, _num($(ch.attr_id).val()), isEdit, 1);
		})
	},
	_dialogCmpScript = function(ch, isEdit) {//���������� �������� ��� ����������� ���������� �������
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
					$(ch.attr_id)
						._select('disabled')
						.next().resizable({
							minWidth:80,
							maxWidth:350,
							grid:10,
							handles:'e',
							stop:function(event, ui) {
								var id = _num(ui.originalElement[0].id.split('elem')[1].split('_select')[0]);
								for(var n = 0; n < DIALOG_COMPONENT.length; n++) {
									var sp = DIALOG_COMPONENT[n];
									if(sp.id == id) {
										sp.width = ui.size.width;
										break;
									}
								}
							}
						});
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
			case 4: /* textarea */ {
				$(ch.attr_id)
					.parent().parent()
					.find('.label').addClass('topi');
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
				else
					$(ch.attr_id).autosize();
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
			case 6: /* ��������� */ {
				$(ch.attr_id)._calendar({
					lost:ch.num_3,
					tomorrow:ch.num_4
				});
				break;
			}
			case 7: /* ���������� */ {
				break;
			}
			case 8: /* ������ */ {
				break;
			}
			case 9: /* ��������� */ {
				break;
			}
		}
	},
	_dialogCmpFunc = function(cmp_id, v, isEdit, first) {//�������, ����������� � ����������� �������
		if(isEdit)
			return;

		var farr = COMPONENT_FUNC[cmp_id];
		if(!farr)
			return;

		//������ ��������������� �������� �������, �� �������
		var UP = first ? 'hide' : 'slideUp',
			DOWN = first ? 'show' : 'slideDown',
			speed = first ? 0 : 200;

		for(var n = 0; n < farr.length; n++) {
			var func = farr[n],
				hide = func.action_id == 1,
				show = func.action_id == 2,
				h0s1 = func.action_id == 3,//������=0 / ��������=1
				h1s0 = func.action_id == 4,//������=1 / ��������=0
				act = hide ? UP : DOWN,

				ifNo = func.cond_id == 1,   //���� ��� ����e���
				ifYes = func.cond_id == 2,  //���� ���� ��������

				ids = func.ids.split(',');

			if(func.action_id == 5 || func.action_id == 6) {//����� ��������� ������
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
						vid:v //���� dialog_id, ���� pe_id, ���� ����� ������� - ������ ������ � ������� ��������
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
	_dialogCmpFuncElemList = function(cmp_id, o, v) {//��������� �������� ������
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

			//��������� ������� ����� �������� �����
			if(res.after)
				return spc.html(res.after);

			spc.html(res.html);

			RES = res;

			switch(res.spisok_type) {
				default:
				case 181:/* ������� */ {
					DL = spc.find('dl');

					_forN(res.arr, itemAdd);

					DL.sortable({
						axis:'y',
						handle:'.icon-move-y'
					});

					spc.find('.item-add').click(itemAdd);

					break;
				}
				case 182:/* ������ */ {
					//��������� ����� ������� _blockSpisokUnitElAdd
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
								'<input type="text" class="tr w175 b" placeholder="��� �������" value="' + sp.tr + '" />' +
							'<td class="td-link-on">' +
								'<input type="hidden" class="elem-link" id="elem-link' + NUM + '" value="' + sp.link + '" />' +
							'<td class="topi r">' +
								'<div class="icon icon-del pl' + _tooltip('������� �������', -51) + '</div>' +
					'</table>'
			);

			var DD = DL.find('dd:last');

			colSel($('#elem-col' + NUM));
			$('#elem-link' + NUM)._check({
					tooltip:'�������� �������'
				});
			DD.find('.td-link-on')._dn(sp.link_on);
			DD.find('.icon-del').click(function() {
				DD.remove();
			});

			NUM++;
		}
		function colSel(sp) {
			sp._select({
				title0:'������� �� �������',
				spisok:RES.label_name_select,
				func:function(v, id, item) {
					_parent($('#' + id)).find('.td-link-on')._dn(item.link_on);
				}
			});
		}
	},

	_blockUnitSetup = function() {//��������� ������ �����
		if(!window.BLOCK_ARR)//�������� ��� �� �����������
			return;

		//���� ������������ ������� ������� ����� �� �����, ��������� ������ �� ���������
		if($('.block-unit-grid').length)
			return;

		var t = $(this),
			block_id = _num(t.attr('val')),
			BL = BLOCK_ARR[block_id],
			obj = $('#bl_' + BL.id);

		//��� ������ ����������
		if(BL.save || obj.hasClass('_busy'))
			return;

		t._hint({
			msg:'<div class="mar10">' +
					'<div class="hd2 mb10">��������� �����</div>' +
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
					tooltip:'������',
					func:function(v) {
						obj.css('border-top', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor1')._check({
					tooltip:'������',
					func:function(v) {
						obj.css('border-right', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor2')._check({
					tooltip:'�����',
					func:function(v) {
						obj.css('border-bottom', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#block-unit-bor3')._check({
					tooltip:'�����',
					func:function(v) {
						obj.css('border-left', v ? '#DEE3EF solid 1px' : '');
						BL.save = 1;
					}
				});
				$('#but-elem-add').click(function() {
					_blockUnitElAdd(BL);
				});
				if(BL.elem_id) {
					$('#elem-edit').click(function() {
						$('._hint').remove();
						if(BL.is_spisok)
							return _blockSpisokUnitElAdd(BL);
						_dialogOpen(BL.dialog_id, BL.elem_id);
					});
					$('#elem-del').click(function() {
						$('._hint').remove();
						var func = !BL.is_spisok ? false : _blockUnitGridOff;
						_dialogOpen(6, BL.elem_id, 0, func);
					});
					var tMar = {
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
	_blockUnitBg = function(BL) {//������� �����
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
			'<tr><td class="color-555 fs14 pr5">�������:' +
				'<td><div id="block-set-bg">' +
						'<div class="' + (BL.bg == 'bg-fff' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP     bg-fff" val="bg-fff"></div>' +
						'<div class="' + (BL.bg == 'bg-gr1' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr1" val="bg-gr1"></div>' +
						'<div class="' + (BL.bg == 'bg-gr3' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr3" val="bg-gr3"></div>' +
						'<div class="' + (BL.bg == 'bg-gr2' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-gr2" val="bg-gr2"></div>' +
						'<div class="' + (BL.bg == 'bg-ffe' ? 'sel' : '') + ' dib h25 w25 bor-e8 curP ml5 bg-ffe" val="bg-ffe"></div>' +
					'</div>' +
			'</table>';
	},
	_blockUnitBor = function(BL) {//������� �����
		var bor = BL.bor.split(' ');
		return '<table class="mt10">' +
				'<tr><td class="color-555 fs14 pr5">�������:' +
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
	_blockUnitBut = function(BL) {//������
		if(BL.elem_id)
			return '';

		return '<div class="mt20 center">' +
					'<button id="but-block-grid" class="vk small orange mb5" onclick="_blockUnitGrid(' + BL.id + ')">��������� ��������</button>' +
	   (!BL.child ? '<button id="but-elem-add" class="vk small green">�������� �������</button>' : '') +
				'</div>';
	},
	_blockUnitElAdd = function(BL) {//���������� ������ �������� � ����
		$('._hint').remove();

		if(BL.is_spisok)
			return _blockSpisokUnitElAdd(BL);

		var html =
				'<div class="center pad20">' +
					'<p><button class="vk" val="3">����</button>' +
					'<p class="mt10"><button class="vk" val="4">���������</button>' +
					'<p class="mt10"><button class="vk" val="7">�����</button>' +
					'<p class="mt10"><button class="vk grey" val="10">������������ �����</button>' +
					'<p class="mt10"><button class="vk" val="11">������ �������</button>' +
					'<p class="mt10"><button class="vk green" val="2">������</button>' +
					'<p class="mt10"><button class="vk" val="9">������</button>' +
					'<p class="mt10"><button class="vk" val="15">������: ���������� �����</button>' +
					'<p class="mt10"><button class="vk" val="14">������: ����������</button>' +
			  (SA ? '<p class="mt10"><button class="vk red" val="12">SA: �� �������</button>' : '') +
				'</div>',
			dialog = _dialog({
				width:450,
				top:20,
				head:'���������� ������ �������� � ���� ��������',
				content:html,
				butSubmit:'',
				butCancel:'�������'
			});

		dialog.content.find('button').click(function() {
			var v = $(this).attr('val');
			window.BLOCK_ID = BL.id;
			dialog.close();
			_dialogOpen(v);
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
					width:res.w,
					parent_id:block_id,
					obj_name:res.block.obj_name,
					obj_id:res.block.obj_id
				});
/*
				if(res.block.is_spisok) {
					obj.funcAfterSave = function(res) {
						$('#spisok-unit-block-level').html(res.level);
						$('#tmp-elem-list').html(res.html);
						BLOCK_ARR = res.block_arr;
					};
					obj.funcCancel = _blockUnitGridOff;
				}
*/
			}, function() {
				but.removeClass('_busy');
			});
	},
	_blockUnitGridOff = function() {//���������� ���������� �������, ����� ���������� ������ ������
		var but = $('#but-spisok-tmp-grid');
		if(but.hasClass('grey')) {
			var html = but.html(),
				val = but.attr('val');
			but.html(val);
			but.attr('val', html);
			but.removeClass('grey')
			   .addClass('orange');
		}
		but.trigger('click');
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

	_elemUnit = function(EL) {//��������� �������� � ����������� ����
		if(!EL.elem_id)
			return '';

		return '<div class="mar5 pad5 bor-e8 bg-gr1">' +
				'<div class="hd2 ">' +
					'��������� ��������' +
					'<div class="fr">' +
						'<div id="elem-edit" class="icon icon-edit mr3' + _tooltip('������������� ������', -69) + '</div>' +
						'<div id="elem-del" class="icon icon-del-red' + _tooltip('������� �������', -52) + '</div>' +
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
	_elemUnitPlace = function(EL) {//����� ��������: ��� �����, ������� ��������
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
			'<tr><td class="fs14 color-555 pb3 center">�������' +
			'<tr><td>' +
					'<div val="top" class="icon-wiki iw6 mr3' + _dn(EL.pos == 'top','on') + _tooltip('�����-�����', -37) + '</div>' +
					'<div val="top center" class="icon-wiki iw7 mr3' + _dn(EL.pos == 'top center','on') + _tooltip('�����-�����', -35) + '</div>' +
					'<div val="top r" class="icon-wiki iw8' + _dn(EL.pos == 'top r','on') + _tooltip('�����-������', -73, 'r') + '</div>' +
			'<tr><td>' +
					'<div val="" class="icon-wiki iw3 mr3' + _dn(!EL.pos,'on') + _tooltip('�����', -15) + '</div>' +
					'<div val="center" class="icon-wiki iw4 mr3' + _dn(EL.pos == 'center','on') + _tooltip('�� ������', -28) + '</div>' +
					'<div val="r" class="icon-wiki iw5' + _dn(EL.pos == 'r','on') + _tooltip('������', -34, 'r') + '</div>' +
			'<tr><td>' +
					'<div val="bottom" class="icon-wiki iw9 mr3' + _dn(EL.pos == 'bottom','on') + _tooltip('����-�����', -33) + '</div>' +
					'<div val="bottom center" class="icon-wiki iw10 mr3' + _dn(EL.pos == 'bottom center','on') + _tooltip('����-�����', -32) + '</div>' +
					'<div val="bottom r" class="icon-wiki iw11' + _dn(EL.pos == 'bottom r','on') + _tooltip('����-������', -65, 'r') + '</div>' +
		'</table>';
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
	_elemUnitColor = function(EL) {//����� ��������: ���� ������
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
	},

	_pageSetupAppPage = function() {//���������� ������� ���������� � ������ �������
		$('#page-sort').nestedSortable({
			forcePlaceholderSize: true,//��������� ������ �����, ������ ��� ���� �������
			placeholder:'placeholder',//�����, ����������� ��� ��������� �����, ������ ������ �������
			handle:'div',
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

			update:function() {
				var send = {
					op:'page_sort',
					arr:$(this).nestedSortable('toArray')
				};
				_post(send);
			},

			expandedClass:'pb10',//��������� ������
			errorClass:'bg-fcc' //������, ���� ������� ����������� ������� �� ����������� �������
		});
	};

$(document)
	.on('click', '._check', function() {//���������/������ �������, ���� ���� �������� ����� PHP
		var t = $(this);
		if(t.hasClass('noon'))//���� ������� �������� ����� JS, � �� ����� PHP, �� �������� ���
			return;

		var p = t.prev(),
			v = _num(p.val()) ? 0 : 1;

		p.val(v);
		t[(v ? 'add' : 'remove') + 'Class']('on');
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

	.on('click', '.block-grid-on', function() {//���������/���������� ���������� �������
		var t = $(this),
			p = t.parent(),
			v = t.hasClass('grey'),
			obj_name = t.attr('val').split(':')[0],
			obj_id = t.attr('val').split(':')[1],
			send = {
				op:'block_edit_' + (v ? 'on' : 'off'),
				obj_name:obj_name,
				obj_id:obj_id
			};

		t._dn(v, 'grey');
		t._dn(!v, 'orange');
		p.find('.block-level-change')._dn(!v);

		_post(send, function(res) {
			$('.block-content-' + obj_name).html(res.html);
			if(v)
				$('#grid-stack')._grid();
		});

	})
	.on('click', '.block-level-change', function() {//��������� ������ �������������� ������
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
	.on('click', '.spisok-unit-block-level-change', function() {//��������� ������ �������������� ������ ������� ������
		var t = $(this),
			v = _num(t.html());

		t.parent().find('.vk').addClass('cancel');
		t.removeClass('cancel');

		_cookie('block_level_spisok', v);

		_blockUnitGridOff();
	})
	.on('mouseenter', '.block-unit', _blockUnitSetup);

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

	function checkPrint() {//����� �������
		var nx = t.next();
		if(nx.hasClass('_check'))   //���� ������� ���� �������� ����� PHP - ���������� � ���������� �������
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
	t.funcGo = function() {//���������� �������
		o.func(_num(t.val()), attr_id);
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
		min:false,  //����������� ��������
		max:false,  //������������ ��������
		minus:0,    //����� ������� � �����
		step:1,     //���
		tooltip:'',
		func:function() {}
	}, o);

	if(o.min < 0)
		o.minus = 1;

	var val = _num(t.val());
	valCorrect();
	t.val(val)
	 .width(o.width - 31)
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
$.fn._select = function(o, o1, o2) {
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
				case 'is_process': return s.isProcess();
				case 'load'://�������� ������ ������
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
		case 'object':
			//���� ��� ������ ����, �� �������
			if(o.width || o.func)
				break;

			//������� ������ ����� ��������
			if('length' in o) {
				s = window[id + '_select'];
				s.spisok(o);
				return t;
			}
			if(!('spisok' in o))
				return t;
	}

	o = $.extend({
		width:180,			// ������
		disabled:0,
		block:false,       	// ������������ �������
		bottom:0,           // ������ �����
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
					(o.clear ? '<div class="icon icon-del mt5 fr' + _dn(val) + _tooltip('��������', -49, 'r') + '</div>' : '') +
	   (o.funcAdd ? '<td class="seladd">' : '') +
					'<td class="selug">' +
			'</table>' +
			'<div class="selres" style="width:' + o.width + 'px"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

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
		pad:0,      //���������� ������� ��������

		side:'auto',    //�������, � ������� ��������� ������������ ������������ �������� (auto, top, right, bottom, left)
		ugPos:'center', //������� ������ �� ���������: top, bottom (��� ���������), left, right (��� �����������). ���� ����� � �������� ����� ���� ������.
		objPos:'center',//������� �������, � ������� ����� ��������� ������.
						//top, bottom - ��� ���������
	                    //left, right - ��� �����������
						//mouse - ������������ ��������� ���� ��� ������ ������� �������
	                    //����� � �������� ����� ���� ������.

		show:0,	     //����� ���������� ���������

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

	o.func();

	var HINT = $('#hint' + HN),
		MSG = HINT.find('.hi-msg'),
		UG = HINT.find('.ug');

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

	if(t.hasClass('_tooltip'))
		return t;

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
	var t = $(this),
		id = t.attr('id'),
		val = t.val(),
		d = new Date();

	o = $.extend({
		year:d.getFullYear(),	// ���� ��� �� ������, �� ������� ���
		mon:d.getMonth() + 1,   // ���� ����� �� ������, �� ������� �����
		day:d.getDate(),		// �� �� � ���
		lost:0,                 // ���� �� 0, �� ����� ������� ��������� ���
		func:function () {},    // ����������� ������� ��� ������ ���
		place:'right',          // ������������ ��������� ������������ ������
		tomorrow:0              // ������ "������" ��� ������� ��������� ���������� ����
	}, o);

	// ���� input hidden ������� ����, ���������� �
	if(REGEXP_DATE.test(val) && val != '0000-00-00') {
		var r = val.split('-');
		o.year = r[0];
		o.mon = Math.abs(r[1]);
		o.day = Math.abs(r[2]);
	}

	//�������� ������ �� ��������� ��� ��������� ������
	t.next().remove('._calendar');

	t.after(
		'<div class="_calendar" id="' + id + '_calendar">' +
			'<div class="calinp">' + o.day + ' ' + MONTH_DAT[o.mon] + ' ' + o.year + '</div>' +
			'<div class="calabs"></div>' +
		'</div>'
	);

	var	curYear = o.year,//����,
		curMon = o.mon,  //�������������
		curDay = o.day,  //� input hidden
		inp = t.next().find('.calinp'),
		calabs = inp.next(),//����� ��� ���������
		calmon,             //����� ��� ������ � ����
		caldays;            //����� ��� ����

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

	t.val(dataForm());
	inp.click(calPrint);

	function calPrint(e) {
		if(!calabs.html()) {
			e.stopPropagation();

			// ���� ���� ������� ������ ���������, �� �����������, ����� ��������
			var cals = $('.calabs');
			for(var n = 0; n < cals.length; n++) {
				var sp = cals.eq(n);
				if(sp.parent().attr('id').split('_calendar')[0] == id)
					continue;
				sp.html('');
			}

			// �������� �������� ��������� ��� ������� �� ����� ����� ������
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
					'<table class="calweeks"><tr><td>��<td>��<td>��<td>��<td>��<td>��<td>��</table>' +
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
	function daysPrint() {//����� ������ ����
		var n,
			html = '<tr>',
			year = d.getFullYear(),
			mon = d.getMonth() + 1,
			today = d.getDate(),
			df = dayFirst(o.year, o.mon),
			cur = year == o.year && mon == o.mon,// ��������� �������� ���, ���� ������� ������� ��� � �����
			lost = o.lost == 0, // ���������� ��������� ����
			st = o.year == curYear && o.mon == curMon, // ��������� ���������� ���
			dc = dayCount(o.year, o.mon);

		//��������� ������ �����
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
	function dataForm() {//������������ ���� � ���� 2012-12-03
		return curYear +
			'-' + (curMon < 10 ? '0' : '') + curMon +
			'-' + (curDay < 10 ? '0' : '') + curDay;
	}
	function dayFirst(year, mon) {//����� ������ ������ � ������
		var first = new Date(year, mon - 1, 1).getDay();
		return first == 0 ? 7 : first;
	}
	function dayCount(year, mon) {//���������� ���� � ������
		mon--;
		if(mon == 0) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function back(e) {//������������� ��������� �����
		e.stopPropagation();
		o.mon--;
		if(o.mon == 0) {
			o.mon = 12;
			o.year--;
		}

		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
	function next(e) {//������������� ��������� �����
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
$.fn._search = function(o, v) {//��������� ������
	/*
		������������� input:text
		attr_id �� ����������
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
		focus:0,//����� ������������� �����
		hold:'', //�����-��������� placeholder
		func:function() {},
		enter:0,//��������� �������� ����� ������ ����� ������� �����
		v:''    //�������� ��������
	}, o);

	//�������� ����� �� ��������� ������, ������� ���� �������� ����� PHP. ���� ��� ����, �� ���������� �������.
	var p = t.parent();
	if(p.hasClass('_search')) {
		o.ex = 1;    //���� ������������ ��������� ������
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
	t.process = function() {//����� �������� �������� � ������ �������
		busy.removeClass('dn');
	};
	t.isProcess = function() {//�����������, � �������� �� �����
		return !busy.hasClass('dn');
	};
	t.cancel = function() {//������� �������� �������� � ������ �������
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
$.fn._menu = function(o) {//����
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
$.fn._dropdown = function(o) {//���������� ������ � ���� ������
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
		head:'',    // ���� �������, �� �������� � �������� ������, � ������ �� spisok
		headgrey:0,
		disabled:0,
		title0:'',
		spisok:[],
		func:function() {},
		nosel:0 // �� ��������� �������� ��� ������ ��������
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

	function assCreate() {//�������� �������������� �������
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
$.fn._grid = function(o) {
	var t = $(this);

	o = $.extend({
		width:100,  //���������� ��������� ����������� ������ ����� ����������� �� ���� �����
		parent_id:0,//������������ ����
		obj_name:'page',//��� �������, ��� ������������� �����
		obj_id:PAGE_ID, //id �������
		funcAfterSave:afterSave,//�������� ����� ����������
		funcCancel:cancel       //�������� ��� ������ ������
	}, o);

	t.gridstack({
		itemClass:'grid-item',
		handle:'.grid-content',  //�������, �� ������� ����� �������������
		animate:true,           //������� ����������� ����� ���������� ��� ������������
		verticalMargin:1,       //������ ������
		cellHeight:10,          //����������� ������ �����
		float:false,            //���� true - ���� ����� ����������� � ����� �����, ����� ���� ������ ������� � �����
		width:o.width
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

		t.addClass('_busy');
		var send = {
			op:'block_grid_save',
			parent_id:o.parent_id,
			obj_name:o.obj_name,
			obj_id:o.obj_id,
			arr:arr
		};
		_post(send, o.funcAfterSave, function() {
			t.removeClass('_busy');
		});
	});
	$('#grid-cancel').click(o.funcCancel);

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

	//���������� ������
	function afterSave(res) {
		$('#block-level-' + o.obj_name).after(res.level).remove();
		$('.block-content-' + o.obj_name).html(res.html);
	}
	//������ ��������������
	function cancel() {
		$('#block-level-' + o.obj_name)
			.find('.block-grid-on')
			.removeClass('grey')
			.trigger('click');
	}
};



