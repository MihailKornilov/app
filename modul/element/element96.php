<?php

/* [96] Количество значений связанного списка с учётом категорий */
function _element96_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//id элемента: привязанный список
		'txt_1'   => $el['txt_1'],      //id элемента (с учётом вложений): путь к категориям
		'txt_2'   => $el['txt_2']       //id элемента (с учётом вложений): путь к цветам
	) + _elementStruct($el);
}
function _element96_print($el, $prm) {
	if($prm['blk_setup'])
		return '<div class="el96-u bg-ffc mr3">8</div>'.
			   '<div class="el96-u bg-fcc">3</div>';

	if(!$u = $prm['unit_get'])
		return '';

	//ключ для конкретного элемента, по которому расположены данные в записи
	$key = 'el96_'.$el['id'];
	if(empty($u[$key]))
		return '';

	end($u[$key]);
	$end = key($u[$key]);

	$send = '';
	foreach($u[$key] as $id => $r) {
		$bg = $r['bg'] ? ' style="background-color:'.$r['bg'].'"' : '';
		$mr = $id != $end ? ' mr3' : '';
		$send .= '<div'.$bg.' class="el96-u'.$mr.' tool" data-tool="'.$r['name'].'">'.$r['count'].'</div>';
	}

	return $send;
}

