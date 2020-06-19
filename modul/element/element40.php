<?php

/* [40] Фильтрование списка */
function _element40_struct($el) {
	/*
		Работает совместно с PHP12_spfl [41] - настройка значений
	*/
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'num_1'   => _num($el['num_1']),//id элемента - путь к списку [13]
		'txt_1'   => $el['txt_1']       //текст нулевого значения
	) + _elementStruct($el);
}
function _element40_print($el, $prm) {
	$attr_id = _elemAttrId($el, $prm);
	$placeholder = ' placeholder="'.$el['txt_1'].'"';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$title = '';
	if($v = _elemPrintV($el, $prm)) {
		$vv = htmlspecialchars_decode($v);
		$arr = json_decode($vv, true);
		$c = count($arr);
		$title = $c.' услови'._end($c, 'е', 'я', 'й');
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
	'<div class="_spfl dib w125 prel" id="'.$attr_id.'_spfl">'.
		'<div class="icon icon-filter pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp color-del b pl25 curP w100p over3"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}
function _element40_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element40_vvv($el, $prm) {//получение id диалога на основании исходного блока (если нет указания на диалог в настройке)
	if($el['num_1'])//указание есть
		return 0;

	if($u = $prm['unit_edit'])
		$prm['srce']['element_id'] = $u['element_id'];

	//поиск элемента по ячейке таблицы
	if($ell_id = $prm['srce']['element_id']) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$ell_id;
		if($ell = query_assoc($sql))
			if($elp = _elemOne($ell['parent_id'])) {
				//таблица
				if($elp['dialog_id'] == 23)
					return $elp['num_1'];
				//таблица из нескольких списков
				if($elp['dialog_id'] == 88)
					if($json = json_decode($elp['txt_2'], true)) {
						$n = 0;
						foreach($json['col'] as $col)
							foreach($col['elm'] as $nn => $elm_id)
								if($elm_id == $ell_id)
									$n = $nn;
						foreach($json['spv'] as $nn => $spv)
							if($nn == $n)
								return $spv['dialog_id'];
					}
				//сборный текст
				if($elp['dialog_id'] == 44)
					foreach($elp['vvv'] as $vvv)
						if($vvv['id'] == $ell_id)
							if($vvv['dialog_id'] == 11) {
								$el11 = _elemOne(_idsFirst($vvv['txt_2']));
								$BL = _blockOne($el11['block_id']);
								if($BL['obj_name'] == 'dialog')
									return $BL['obj_id'];
								return 0;
							}
			}
	}

	if(!$block_id = $prm['srce']['block_id'])
		return 0;
	if(!$BL = _blockOne($block_id))
		return 0;

	if($EL = $BL['elem'])
		if(_elemIsConnect($EL))//если является списком - отправка диалога списка
			return _num($EL['num_1']);

	if($BL['obj_name'] == 'page') {
		if(!$page = _page($BL['obj_id']))
			return 0;
		return $page['dialog_id_unit_get'];
	}

	return 0;
}
function _elem40json($cond) {//перевод данных фильтра из JSON в array
	if(empty($cond))
		return array();

	$arr = htmlspecialchars_decode($cond);
	return json_decode($arr, true);
}
