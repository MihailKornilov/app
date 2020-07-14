<?php

/* [95] Быстрое формирование списка */

/* Структура элемента */
function _element95_struct($el) {
	return array(
	) + _elementStruct($el);
}

/* Вывод содержимого элемента на экран */
function _element95_print($el, $prm) {
	if(!empty($prm['blk_setup']))
		return _emptyMin('[95] Быстрое формирование списка');

	return '';
}





function PHP12_elem95_setup($prm) {//настройка колонок списка
	if(!$prm['unit_edit'])
		return _emptyMin10('Настройка колонок будет доступна после вставки элемента в блок.');
	return '';
}
