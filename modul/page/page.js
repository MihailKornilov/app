var _pageShow = function(pas) {//активация элементов после вывода страницы
		_elemActivate(BLOCK_ARR, {}, pas);

		//если включено управление страницей, активация прекращается для некоторых элементов
//		if(pas)
			return;

		//применение функций к _search
		_forEq($('._search'), function(sp) {
			sp.find('input')._search({
				func:function(v, attr_id) {
					var obj = $('#' + attr_id),
						send = {
							op:'spisok_search',
							elem_id:sp.parent().attr('id').split('_')[1],
							v:v
						};

					if(obj._search('is_process'))
						return;

					obj._search('process');
					_post(send, function(res) {
						obj._search('cancel');
						$(res.spisok_attr).html(res.spisok_html);
						$(res.count_attr).html(res.count_html);
					});
				}
			});
		});
	};