<?php

/* [300] Привязка пользователя к странице ВК */
function _element300_struct($el) {
	return _elementStruct($el);
}
function _element300_print($el, $prm) {
	$vkRes = '';
	if($user_id = _elemPrintV($el, $prm, 0)) {
		$res = _vkapi('users.get', array(
			'user_ids' => $user_id,
			'fields' => 'photo,'.
						'sex,'.
						'country,'.
						'city'
		));

		if(empty($res['response']))
			$vkRes = '<div class="red">Данные из VK не получены';
		else
			$vkRes = _elem300Sel($res['response'][0]);
	}

	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'"'.$disabled.' value="'.$user_id.'" />'.
	'<div id="'._elemAttrId($el, $prm).'_vk300" class="_vk300"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-vk curD'._dn(!$user_id).'"></div>'.
		'<input type="text" class="w100p'._dn(!$user_id).'"'.$disabled.' />'.
		'<div class="vk-res">'.$vkRes.'</div>'.
	'</div>';
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
			'<td><div class="icon icon-del-red pl fr ml20 mtm2'._tooltip('Отменить', -31).'</div>'.
				'<a href="//vk.com/id'.$res['id'].'" target="_blank">'.
					$res['first_name'].' '.$res['last_name'].
				'</a>'.
				'<div class="grey mt3">'._elem300Place($res).'</div>'.
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


