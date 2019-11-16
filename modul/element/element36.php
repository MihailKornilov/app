<?php

/* [36] Иконка */
function _element36_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id иконки из _element36type
		'num_2'   => _num($el['num_2']),//изменять яркость при наведении мышкой
		'num_3'   => _num($el['num_3']),//курсор рука при наведении, иначе стрелочка
		'txt_1' => $el['txt_1']         //подсказка
	) + _elementStruct($el);
}
function _element36_struct_title($el) {
	$el['title'] = 'ICON';
	return $el;
}
function _element36_print($el) {
	$type = _element36type($el['num_1']);
	$pl = _dn(!$el['num_2'], 'pl');
	$cur = $el['num_3'] ? ' curP' : ' curD';

	return '<div class="icon icon-'.$type.$pl.$cur._element36TT($el).'</div>';
}
function _element36_copy_field($el) {
	return array(
		'num_1'   => _num($el['num_1']),
		'num_2'   => _num($el['num_2']),
		'num_3'   => _num($el['num_3']),
		'txt_1' => $el['txt_1']
	);
}
function _element36TT($el) {
	if(empty($el['txt_1']))
		return '">';

	return _tooltip($el['txt_1'], -10, 'l');
}
function _element36type($id='all') {//доступные варианты иконок
	$icon = array(
		1 => 'hint',
		2 => 'print',
		3 => 'ok',
		4 => 'set',
		5 => 'set-b',
		6 => 'client',
		7 => 'worker',
		8 => 'vk',
		9 => 'rub',
		10 => 'usd',
		11 => 'stat',
		12 => 'set-dot',
		13 => 'info',
		14 => 'search',
		15 => 'star',
		16 => 'comment',
		17 => 'add',
		18 => 'edit',
		19 => 'del',
		20 => 'del-red',
		21 => 'doc',
		22 => 'order',
		23 => 'calendar',
		24 => 'eye',
		25 => 'clock',
		26 => 'cancel',
		27 => 'recover',
		28 => 'off',
		29 => 'offf',
		30 => 'out',
		31 => 'chain',
		32 => 'move',
		33 => 'move-x',
		34 => 'move-y',
		35 => 'sub',
		36 => 'join',
		37 => 'cube',
		38 => 'filter',
		39 => 'link',
		40 => 'hand',
		41 => 'manual',
		42 => 'copy',
		43 => 'exit',
		44 => 'admin',
		45 => 'video'
	);

	if($id == 'all')
		return $icon;

	return isset($icon[$id]) ? $icon[$id] : 'empty';
}
function PHP12_icon18_list($prm) {
	$sel = 0;
	if($col = $prm['el12']['col'])
		if($u = $prm['unit_edit'])
			$sel = $u[$col];

	$send = '';
	foreach(_element36type() as $id => $name) {
		$send .=
			'<div class="icu over1'._dn($id!=$sel, 'sel').'" val="'.$id.'">'.
				'<div class="icon icon-'.$name.' curP"></div>'.
			'</div>';
	}

	return
	'<div class="_icon-choose mt3">'.
		$send.
	'</div>';
}






