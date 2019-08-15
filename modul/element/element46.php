<?php

/* [46] Данные текущего пользователя */
function _element46_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//диалог пользователей, на основании которого будет выбираться значение [38]
		'txt_2'   => _num($el['txt_2']) //id значения [13]
	) + _element11_struct($el);
}
function _element46_js($el) {
	return _element11_js($el);
}
function _element46_print($el, $prm) {
	if(!APP_ID)
		return '';
	if(!$u = _user())
		return '';

	$u['dialog_id'] = 111;
	$spisok[USER_ID] = $u;
	$spisok = _spisokImage($spisok);

	$prm['unit_get'] = $spisok[USER_ID];

	return _element11_print($el, $prm);
}

