<?php

/* [32] Значение списка: порядковый номер */
function _element32_struct($el) {
	return array(
		'num_1' => _num($el['num_1']) /* формат вывода:
                                            0: по умолчанию
                                            1: идентификатор
                                            2: номер
									  */
	) + _elementStruct($el);
}
function _element32_title($el) {
	return $el['num_1'] == 1 ? 'ID' : 'NUM';
}
function _element32_print($el, $prm) {
	if(empty($prm['unit_get']))
		return _element('title', $el);

	$u = $prm['unit_get'];

	if(!$num = _num($u))
		if(is_array($u)) {
			switch($el['num_1']) {
				default:
				case 0:
				case 2:
					$num = empty($u['num']) ? $u['id'] : $u['num'];
					break;
				case 1:
					$num = $u['id'];
					break;
			}
		}


	if(!$num)
		return _element('title', $el);

	$num = _spisokColSearchBg($el, $num);

	return $num;
}
function _element32_template_docx($el, $u) {
	return empty($u['num']) ? $u['id'] : $u['num'];
}

