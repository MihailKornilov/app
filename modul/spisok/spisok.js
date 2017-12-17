var _spisokNext = function(t, pe_id, next) {
		if(t.hasClass('_busy'))
			return;

		t.removeClass('over1')
		 .addClass('_busy');

		var send = {
			op:'spisok_next',
			pe_id:pe_id,
			next:next
		};
		_post(send, function(res) {
			var obj = res.type == 181 ? _parent(t, 'TABLE') : $('#pe_' + pe_id);
			obj.append(res.spisok);
			t.remove();
		},function() {
			t.addClass('over1')
			 .removeClass('_busy');
		});
	},
	_spisokTmpBlock = function(t, block_id) {//���������/���������� ��������� ������ ������� ������
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
			if(!on)
				return;
			$('#grid-stack')._grid({
				width:res.w,
				parent_id:block_id,
				is_spisok:block_id,
				funcAfterSave:function(res) {
					$('#tmp-elem-list').html(res.html);
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
	_blockSpisokUnitElAdd = function(BL) {//������� �������� � ���� � ������� ������
		/*
			num_1 - ������� ������
				-1 - ���������� ����� num
				-2 - ���� ��������
				-3 - ������ ���������� (��� �������)
				-4 - ������������ ����� �� txt_2
			num_2 - ��� ���������� �������
				1 - ��������
				2 - ��������
			num_3 - ������, ����� ������� �������� ������� ������
			txt_2 - ������������ ����� ��� num_1 = -4
		*/
		var el = $.extend({
				elem_id:0,
				num_1:0,
				num_2:0,
				txt_2:''
			}, BL),
			html = '<table class="bs10">' +
						'<tr><td class="label r w125">�������:' +
							'<td><input type="hidden" id="elem_type" value="' + el.num_1 + '" />' +
						'<tr class="tr-col-type' + _dn(el.num_1 > 0) + '">' +
							'<td class="label r">��� ����������:' +
							'<td><input type="hidden" id="col_type" value="' + el.num_2 + '" />' +
						'<tr class="tr-txt-2' + _dn(el.num_1 == -4) + '">' +
							'<td class="label r topi">�����:' +
							'<td><textarea id="tmp_elem_txt_2" class="min w250">' + _br(el.txt_2) + '</textarea>' +
					'</table>',
			dialog = _dialog({
				width:470,
				head:el.elem_id ? '�������������� ��������' : '������� �������� � ���� ������',
				content:html,
				butSubmit:el.elem_id ? '���������' : '�������� �������',
				butCancel:'�������',
				submit:submit
			});

			$('#elem_type')._select({
				block:1,
				width:220,
				title0:'�� �������',
				spisok:RES.label_name_select,
				func:function(v) {
					$('.tr-col-type')._dn(v > 0);
					$('#col_type')._select('clear');

					$('.tr-txt-2')._dn(v == -4);
					$('#tmp_elem_txt_2').val('');
					if(v == -4)
						$('#tmp_elem_txt_2').focus();
				}
			});
			$('#col_type')._select({
				block:1,
				width:220,
				title0:'�� ������',
				spisok:[
					{uid:1,title:'��� �������'},
					{uid:2,title:'�������� �������'}
				]
			});
			$('#tmp_elem_txt_2').autosize();
			function submit() {
				var send = {
					op:'spisok_tmp_elem_to_block',
					elem_id:el.elem_id,
					block_id:BL.id,
					num_1:$('#elem_type').val(),
					num_2:$('#col_type').val(),
					txt_2:$('#tmp_elem_txt_2').val()
				};
				dialog.post(send, function() {

				});
			}

	};