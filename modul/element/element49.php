<?php

/* [49] Выбор блоков из диалога или страницы */
function _element49_struct($el) {
	/*
		работает в паре с [19] - окно выбора блоков
	*/
	return array(
		'txt_1'   => $el['txt_1']//текст для placeholder
	) + _elementStruct($el);
}
function _element49_print($el, $prm) {
	$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$v = _elemPrintV($el, $prm);
	$ids = _ids($v);
	$count = _ids($ids, 'count');
	$title = $count ? $count.' блок'._end($count, '', 'а', 'ов') : '';

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div class="_sebl dib prel bg0 over1" id="'._elemAttrId($el, $prm).'_sebl"'._elemStyleWidth($el).'>'.
		'<div class="icon icon-cube pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp curP w100p clr8"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}

/* ---=== ВЫБОР БЛОКОВ [19] ===--- */
function PHP12_block_choose($prm) {
	if(!$block_id = _num($prm['srce']['block_id']))
		if($elem_id = _num($prm['srce']['element_id'])) {
			if(!$EL = _elemOne($elem_id))
				return _emptyMin10('[19] Отсутствует исходный блок.');
			$block_id = $EL['block_id'];
		}
	if(!$BL = _blockOne($block_id))
		return _emptyMin10('Блока '.$block_id.' не существует.');

	$title = 'Страница';
	$obj_name = $BL['obj_name'];
	$obj_id = $BL['obj_id'];

	switch($obj_name) {
		case 'page':
			if(!$page = _page($obj_id))
				return _emptyMin10('Страницы '.$obj_id.' не существует.');
			$name = $page['name'];
			break;
		case 'dialog':
			$title = 'Диалог';
			if(!$dlg = _dialogQuery($obj_id))
				return _emptyMin10('Диалога '.$obj_id.' не существует.');
			$name = $dlg['name'];
			break;
		case 'spisok':
			if(!$el = _elemOne($obj_id))
				return _emptyMin10('Элемента '.$obj_id.' не существует.');
			if(!$bl = _blockOne($el['block_id']))
				return _emptyMin10('Блока '.$el['block_id'].' не существует.');

			$obj_name = $bl['obj_name'];
			$obj_id = $bl['obj_id'];
			if($obj_name == 'dialog') {
				$title = 'Диалог';
				if(!$dlg = _dialogQuery($el['num_1']))
					return _emptyMin10('Диалога-списка '.$el['num_1'].' не существует.');
				$name = $dlg['name'];
			} else {
				if(!$page = _page($obj_id))
					return _emptyMin10('Страницы '.$obj_id.' не существует.');
				$name = $page['name'];
			}
			break;
		default:
			return _emptyMin10('Выбор блоков возможен только на страницах и в диалоговых окнах.');
	}

	//доп.параметны, отправленные из JS
	$prm['dop'] += array(
		'level_deny' => 0,  //запрет изменения уровня блоков. Только верхний (первый) уровень
		'blk_deny' => 0,    //блоки, которые запрещено выбирать
		'sel' => 0          //выбранные блоки
	);

	$cond = array(
		'blk_choose' => 1,
		'blk_level' => $prm['dop']['level_deny'] ? 1 : _blockLevelDefine($obj_name),
		'blk_deny' => $prm['dop']['blk_deny'],
		'blk_sel' => $prm['dop']['sel']
	);

	return
	'<div class="fs14 pad10 pl15 bg-orange">'.$title.' <b class="fs14">'.$name.'</b>:</div>'.
	($prm['dop']['level_deny'] ? '' : PHP12_block_choose_but_level($obj_name, $obj_id)).
	'<div id="block-choose-div">'.
		_blockHtml($obj_name, $obj_id, $cond).
	'</div>';
}
function PHP12_block_choose_but_level($obj_name, $obj_id) {//кнопки уровня блоков
	$arr = _blockLevelButArr($obj_name, $obj_id);
	if(count($arr) < 2)
		return '';

	$html = '';
	foreach($arr as $n => $color)
		$html .= '<button class="block-choose-level-change vk small ml5 '.$color.'">'.$n.'</button>';

	return
	'<div class="bg-ffc">'.
		'<table class="bs5 ml10">'.
			'<tr><td class="clr13">Уровни блоков:'.
				'<td>'.$html.
				'<td class="w50 level-hold">'.
		'</table>'.
	'</div>';
}

