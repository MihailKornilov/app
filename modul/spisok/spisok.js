var _spisokNext = function(t, elem_id, next) {
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
	};