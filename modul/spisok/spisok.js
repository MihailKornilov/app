var _spisokUpdate = function(elem_spisok, func) {
		var send = {
			op:'spisok_filter_update',
			elem_spisok:elem_spisok,         //id элемента-списка
			elem_v:FILTER[elem_spisok],      //значения фильтра по каждому элементу
			busy_obj:_attr_el(elem_spisok),  //id элемента, размещающего список
			busy_cls:'spisok-busy'
		};
		_post(send, function(res) {
			//показ/скрытие кнопки очистки фильтра [80]
			if(res.clear_id)
				_attr_cmp(res.clear_id)._dn(res.clear_diff);

			//обновление количества [15], если есть
			if(res.count_id)
				_attr_el(res.count_id).html(res.count_html);

			//обновление группировки [79], если есть
			if(res.group_id)
				_attr_el(res.group_id).html(res.group_html);

			_attr_el(res.spisok_id).html(res.spisok_html);
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
			var obj = _attr_el(elem_id);
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




