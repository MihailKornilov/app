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
			_parent(t, res.spisok_type == 181 ? 'TABLE' : '.pe').append(res.spisok);
			t.remove();
		},function() {
			t.addClass('over1')
			 .removeClass('_busy');
		});
	};