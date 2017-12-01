var _pageShow = function() {//выполнение после вывода страницы

		//применение функций к _search
		_forEq($('._search'), function(sp) {
			sp.find('input')._search({
				func:function(v, attr_id) {
					var send = {
						op:'spisok_search',
						element_id:_parent(sp, '.pe').attr('id').split('_')[1],
						v:v
					},
						obj = $('#' + attr_id);

					if(obj._search('is_process'))
						return;

					obj._search('process');
					_post(send, function(res) {
						obj._search('cancel');
						$(res.attr_id).html(res.spisok);
					});
				}
			});
		});
	};