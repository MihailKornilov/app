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

			if(func)
				func(res);

			_filterAfter(res);
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
			_hintPaste(res);
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
			_hintPaste(res);
		});
	},
	_spisokSort = function(elem_id) {
		_attr_el(elem_id)._sort({
			items:'.sp-unit',
			elem_id:elem_id
		});
	},
	_filterAfter = function(res) {//действия после выполнения фильтров
		_hintPaste(res);

		//обновление дополнительных значений
		for(var i in res.upd) {
			var u = res.upd[i];
			_attr_el(u.id).html(u.html);
		}

		//скрытие/показ блоков
		_forIn(res.blk_hidden_upd, function(v, block_id) {
			_blockObj(block_id)[0].obj._dn(v)
		});

		//игнорирование фильтров
		_forIn(res.ignore, function(el) {
			switch(el.dialog_id) {
				//фильтр-галочка
				case 62: _attr_cmp(el.id)._check('ignore', el.ignore); break;
				//фильтр-радио
				case 74: _attr_cmp(el.id)._radio('ignore', el.ignore); break;
			}

		});
	};




