<?php

/* [97] Независимая кнопка */

function _element97_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//[24] список
		'num_2'   => _num($el['num_2'], 1),//позиция сверху
		'num_3'   => _num($el['num_3'], 1) //позиция слева
	) + _elementStruct($el);
}
function _element97_print($el) {
	$DLG = _dialogQuery($el['num_1']);
	return '<span class="fs11 pale">[Независимая кнопка: '.$DLG['name'].']</span>';
}
function _elem97print($page_id) {//вывод кнопки на экран
	if(!$ELM = _BE('elem_arr', 'page', $page_id))
		return '';

	$send = '';
	foreach($ELM as $el) {
		if($el['dialog_id'] != 97)
			continue;

		$css = 'margin-top:'.$el['num_2'].'px;'.
			   'margin-left:'.$el['num_3'].'px;';

		//при настройке кнопки передаётся ID элемента для сохранения после перетаскивания
		$val = PAS ? '' : ' val="dialog_id:'.$el['num_1'].'"';
		$cls = PAS ? 'curM' : 'curP dialog-open';
		$attr_id = PAS ? ' id="but97_'.$el['id'].'"' : '';
		$coord = PAS ? ' data-coord="'.$el['num_3'].':'.$el['num_2'].'"' : '';

		$send .=
		'<div'.$attr_id.' class="but97 '.$cls.'"'.$val.'" style="'.$css.'"'.$coord.'>'.
			'<div class="b97-ver mt15"></div>'.
			'<div class="b97-gor"></div>'.
			'<div class="b97-ver bottom"></div>'.
		'</div>'.
		(PAS ? '<script>_elem97move('.$el['id'].')</script>' : '');
	}

	return $send;
}

