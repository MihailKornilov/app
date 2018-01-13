var _spisokNext = function(t, pe_id, next) {
		var send = {
			op:'spisok_next',
			pe_id:pe_id,
			next:next,
			busy_obj:t
		};
		_post(send, function(res) {
			var obj = res.is_table ? _parent(t, 'TABLE') : $('#pe_' + pe_id);
			obj.append(res.spisok);
			t.remove();
		});
	};