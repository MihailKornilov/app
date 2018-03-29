var _spisokUpdate = function(elem_spisok, func) {
		var send = {
			op:'spisok_filter_update',
			elem_spisok:elem_spisok,            //id ��������-������
			elem_v:FILTER[elem_spisok],         //�������� ������� �� ������� ��������
			busy_obj:$('#el_' + elem_spisok),   //id ��������, ������������ ������
			busy_cls:'spisok-busy'
		};
		_post(send, function(res) {
			$(res.clear_attr)._dn(res.clear_diff);
			$(res.count_attr).html(res.count_html);
			$(res.spisok_attr).html(res.spisok_html);
			if(func)
				func(res);
		});
	},
	_spisokNext = function(t, elem_id, next) {
		var send = {
			op:'spisok_next',
			elem_id:elem_id,
			next:next,
			busy_obj:t
		};
		_post(send, function(res) {
			var obj = res.is_table ? _parent(t, 'TABLE') : $('#el_' + elem_id);
			obj.append(res.spisok);
			t.remove();
		});
	},
	_spisokSort = function(attr_el) {
		$(attr_el)._sort({
			items:'.sp-unit',
			handle:'.icon-move',
			table:'_spisok'
		});
	};