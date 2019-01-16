var _spisokUpdate = function(elem_spisok, func) {
		var send = {
			op:'spisok_filter_update',
			elem_spisok:elem_spisok,            //id элемента-списка
			elem_v:FILTER[elem_spisok],         //значения фильтра по каждому элементу
			busy_obj:$('#el_' + elem_spisok),   //id элемента, размещающего список
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
	_spisok14Next = function(t, elem_id, next) {
		var send = {
			op:'spisok_14_next',
			elem_id:elem_id,
			next:next,
			busy_obj:t
		};
		_post(send, function(res) {
			var obj = $('#el_' + elem_id);
			obj.append(res.spisok);
			t.remove();
		});
	},
	_spisok23next = function(t, elem_id, next) {//догрузка списка-таблицы
		var send = {
			op:'spisok_23_next',
			elem_id:elem_id,
			next:next,
			busy_obj:t
		};
		_post(send, function(res) {
			t.closest('._stab').append(res.spisok);
			t.remove();
		});
	},
	_spisokSort = function(elem_id) {
		_attr_el(elem_id)._sort({
			items:'.sp-unit',
			elem_id:elem_id
		});
	};




