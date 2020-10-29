<?php

/* [300] Привязка пользователя к странице ВК */
function _element300_struct($el) {
	return array(
		'issp' => 301
	) + _elementStruct($el);
}
function _element300_print($el, $prm) {
	$user_id = _elemPrintV($el, $prm, 0);
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'"'.$disabled.' value="'.$user_id.'" />'.
	'<div id="'._elemAttrId($el, $prm).'_vk300" class="_vk300"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-vk curD'._dn(!$user_id).'"></div>'.
		'<input type="text" class="w100p'._dn(!$user_id).'"'.$disabled.' />'.
		'<div class="vk-res">'._elem300vkRes($user_id).'</div>'.
	'</div>';
}
function _element300_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$vk_id = _num(@$u[$col]))
		return '';
	if(!$res = _elem300vkRes($vk_id, true))
		return '';

	return '<a href="//vk.com/id'.$vk_id.'" target="_blank" class="inhr">'.$res['first_name'].' '.$res['last_name'].'</a>';
}
function _elem300p301($el, $u, $last_id) {//вывод значения пользователя из ВК
	/*
		16977 - ava
		16978 - Имя Фамилия
		16979 - Фамилия Имя
		16982 - ava + link (ссылка на страницу пользователя)
	*/

	if(!$col = _elemCol($el))
		return '';
	if(!$vk_id = _num(@$u[$col]))
		return '';
	if(!$res = _elem300vkRes($vk_id, true))
		return '';

	switch($last_id) {
		case 16977: return '<img src="'.$res['photo'].'" class="br1000" width="'._elemWidth($el['elp']).'">';
		case 16978: return $res['first_name'].' '.$res['last_name'];
		case 16979: return $res['last_name'].' '.$res['first_name'];
		case 16982: return '<a href="//vk.com/id'.$vk_id.'" target="_blank">'.
								'<img src="'.$res['photo'].'" class="br1000" width="'._elemWidth($el['elp']).'">'.
						   '</a>';
	}

	return '<a href="//vk.com/id'.$vk_id.'" target="_blank" class="inhr">'.$res['first_name'].' '.$res['last_name'].'</a>';
}
function _elem300vkRes($user_id, $isArr=false) {//данные пользователя из VK
	if(!$user_id)
		return $isArr ? array() : '';

	$res = _vkapi('users.get', array(
		'user_ids' => $user_id,
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response']))
		return $isArr ? array() : '<div class="clr5 fs11">Данные из VK не получены</div>';

	return $isArr ? $res['response'][0] : _elem300Sel($res['response'][0]);
}
function _elem300Place($res) {//страна и город пользователя ВК
	$place = array();
	if(!empty($res['country']))
		$place[] = $res['country']['title'];
	if(!empty($res['city']))
		$place[] = $res['city']['title'];

	return implode(', ', $place);
}
function _elem300Sel($res) {//выбранный пользователь ВК
	return
	'<table>'.
		'<tr><td class="pr5"><img src="'.$res['photo'].'" class="ava35">'.
			'<td><div class="icon icon-del-red pl fr ml20 mtm2 tool" data-tool="Отменить"></div>'.
				'<a href="//vk.com/id'.$res['id'].'" target="_blank">'.
					$res['first_name'].' '.$res['last_name'].
				'</a>'.
				'<div class="clr1 mt3">'._elem300Place($res).'</div>'.
	'</table>';
}
function _elem300VkIdTest($DLG, $v, $user_id) {//проверка, чтобы два одинаковый `vk_id` не попали в таблицу `_user`
	if(!$vk_id = _num($v))
		return false;

	//поиск таблицы `_user`
	$tab = $DLG['table_name_1'];

	if($parent_id = $DLG['dialog_id_parent']) {
		$PAR = _dialogQuery($parent_id);
		$tab = $PAR['table_name_1'];
	}

	if($tab == '_user') {
		$sql = "SELECT COUNT(*)
				FROM `_user`
				WHERE `vk_id`=".$vk_id.
	($user_id ? " AND `id`!=".$user_id : '');
		return query_value($sql);
	}

	return false;
}


