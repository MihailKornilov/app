<?php

/* [46] Данные текущего пользователя */
function _element46_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),// [38] диалог пользователей, на основании которого будет выбираться значение
		'txt_2'   => _num($el['txt_2']),// [13] id значения

		'num_7' => _num($el['num_7']),//ограничение высоты (настройка стилей для [60] изображения)
		'num_8' => _num($el['num_8']) //закруглённые углы (настройка стилей для [60] изображения)
	) + _elementStruct($el);
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

