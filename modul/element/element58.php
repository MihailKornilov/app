<?php

/* [58] Условия удаления записи (пока не используется) */
function _element58_struct($el) {
	/*
		применяется при настройке диалога в удалении
	*/
	return array(
		'num_1'   => _num($el['num_1']),//id диалога
		'num_2'   => _num($el['num_2']),//запрещать удаление, если наступили новые сутки [1]
	) + _elementStruct($el);
}

