<?php

/* [69] Значение записи: имя пользователя */
function _element69_struct($el) {
	return array(
		'num_1'   => _num($el['num_1'])/* формат:
									        8170: Имя Фамилия
									        8171: Фамилия Имя
									        8172: Фамилия Имя Отчество
									        8173: Фамилия И.О.
                                        */
	) + _elementStruct($el);
}
function _element69_print($el, $prm) {
	if(!$u = $prm['unit_get'])
		return $el['title'];
	if(empty($u['user_id_add']))
		return 'no user';
	if(!$us = _user($u['user_id_add']))
		return '';

	switch($el['num_1']) {
		default:
		case 8170: break;
		case 8171: return $us['f'].' '.$us['i'];
		case 8172: return $us['f'].' '.$us['i'].' '.$us['o'];
		case 8173:
			$send = $us['f'];
			if($us['i']) {
				$send .= ' '.mb_substr($us['i'], 0, 1).'.';
				if($us['o'])
					$send .= mb_substr($us['o'], 0, 1).'.';
			}
			return $send;
	}

	return $us['i'].' '.$us['f'];
}
function _element69_template_docx($el, $u) {
	$prm = _blockParam();
	$prm['unit_get'] = $u;
	return _element69_print($el, $prm);
}

