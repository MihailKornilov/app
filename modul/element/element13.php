<?php

/* [13] Выбор элемента из диалога или страницы */
function _element13_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст для placeholder
		'num_1'   => _num($el['num_1']),//элемент в исходном диалоге [13] (если num_3=8059)
		'num_2'   => _num($el['num_2']),//разрешать выбирать только некоторые типы элементов (иначе любые)
		'txt_2'   => $el['txt_2'],      //ids диалогов разрешённых элементов [12]
		'num_3'   => _num($el['num_3']),/* где выбирать элементы:
											8058: в исходном диалоге
											8059: по элементу в исходном диалоге
											8060: на текущей странице
											8061: из диалога, данные которого получает страница
                                        */
		'num_5'   => _num($el['num_5']),//выбор значений во вложенных списках
		'num_6'   => _num($el['num_6']) //выбор нескольких значений
	) + _elementStruct($el);
}
function _element13_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	//в самом себе выбор элемента невозможен
	if($block_id = $prm['srce']['block_id'])//должен быть блок 2214
		if($BL = _blockOne($block_id))
			if($BL['obj_name'] == 'dialog' && $BL['obj_id'] == 13)
				$disabled = ' disabled';

	$v = _elemPrintV($el, $prm, !$el['num_5'] && !$el['num_6'] ? 0 : '');

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_selem dib prel bg0 over1" id="'._elemAttrId($el, $prm).'_selem"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-star pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp curP w100p clr11"'.$placeholder.$disabled.' value="'._elemIdsTitle($v).'" />'.
	'</div>';
}
function PHP12_elem_rule7($prm) {/* ---=== ЭЛЕМЕНТЫ, КОТОРЫЕ МОЖНО ВЫБИРАТЬ В НАСТРОЙКЕ ДИАЛОГА [13] ===--- */
	//элементы, используемые в правиле 7
	$sql = "SELECT `dialog_id`
			FROM `_element_rule_use`
			WHERE `rule_id`=7";
	if(!$ids = DB1::ids($sql))
		return _emptyMin('Нет элементов для выбора');

	//получение разрешённых элементов
	$sql = "SELECT *
			FROM `_dialog`
			WHERE `id` IN (".$ids.")
			ORDER BY `sort`,`id`";
	if(!$elem = DB1::arr($sql))
		return _empty('Нет элементов для отображения.');

	$sql = "SELECT *
			FROM `_element_group`
			WHERE `id` IN ("._idsGet($elem, 'element_group_id').")
			ORDER BY `sort`";
	if(!$group = DB1::arr($sql))
		return _emptyMin('Отсутствуют группы элементов.');

	foreach($group as $id => $r)
		$group[$id]['elem'] = array();

	//расстановка элементов в группы
	foreach($elem as $id => $r)
		$group[$r['element_group_id']]['elem'][] = $r;

	$ass = _idsAss(_elemPrintV($prm['el12'], $prm));

	$send = '';
	foreach($group as $r) {
		$send .= '<div class="fs15 mt15 mb5 clr9">'.$r['name'].':</div>';
		foreach($r['elem'] as $el) {
			$send .=
			'<div class="ml15 mt3">'.
				_check(array(
					'attr_id' => 'rule7-el'.$el['id'],
					'title' => $el['name'],
					'value' => _num(@$ass[$el['id']]) ? 1 : 0
				)).
			'</div>';
		}
	}

	return $send;
}

