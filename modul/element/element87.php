<?php

/* [87] Циферка в меню страниц */
function _element87_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id диалога: список
		'txt_1'   => $el['txt_1']       //условия [40]
	) + _elementStruct($el);
}
function _element87_print($el, $prm) {
	if(!$prm['blk_setup'])
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return _msgRed('Не получены данные диалога '.$el['num_1']);

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($DLG)."
			WHERE "._queryWhere($DLG).
				_40cond(array(), $el['txt_1']);
	$count = DB1::value($sql);

	return 'Кол-во "'.$DLG['name'].'" '.($count ? '+'.$count : '0');
}

