<?php

/* [14] Список-шаблон */
function _element14_struct($el) {
	/*
		настройка шаблона через функцию PHP12_spisok14_setup
	*/
	return array(
		'num_1' => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2' => _num($el['num_2']),//длина (количество строк, выводимых за один раз)

		'txt_1' => $el['txt_1'],      //сообщение пустого запроса
		'txt_2' => $el['txt_2'],      //условия отображения, настраиваемые через [40]
		'num_3' => _num($el['num_3']),/* порядок:
											0 - автоматически
											2318 - по дате добавления
											2319 - сотрировка (на основании поля sort)
									  */
		'num_4' => _num($el['num_4']) //горизонтальное расположение списка
	) + _elementStruct($el);
}
function _element14_struct_title($el, $DLG) {
	if(!$dlg_id = $el['num_1'])
		return $el;
	if(empty($DLG[$dlg_id]))
		return $el;
	$el['title'] = $DLG[$dlg_id]['name'];
	return $el;
}
function _element14_js($el) {
	return array(
		'num_1' => _num($el['num_1'])
	) + _elementJs($el);
}
function _element14_print($el, $prm) {
	if(!$dialog_id = $el['num_1'])
		return _emptyRed('Не указан список для вывода данных.');
	if(!$DLG = _dialogQuery($dialog_id))
		return _emptyRed('Списка <b>'.$dialog_id.'</b> не существует.');
	if($prm['blk_setup'])
		return _emptyMin('Список-шаблон <b>'.$DLG['name'].'</b>');

	return _spisok14($el);
}
function _element14_copy_field($el) {
	return array(
//		'num_1' => _num($el['num_1']),
		'num_2' => _num($el['num_2']),

		'txt_1' => $el['txt_1'],
//		'txt_2' => $el['txt_2'],
		'num_3' => _num($el['num_3']),
		'num_4' => _num($el['num_4'])
	);
}
function _element14_copy_vvv($el, $obj_id) {
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='spisok'
			  AND `obj_id`=".$el['id']."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$BLK = query_arr($sql))
		return;

	foreach($BLK as $r) {
		$r['obj_id'] = $obj_id;
		_blockInsert($r);
	}

	_blockChildCountSet('spisok', $obj_id);
	_blockAppIdUpdate('spisok', $obj_id);
}
