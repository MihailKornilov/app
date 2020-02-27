<?php

/* [27] Cумма значений записи */
function _element27_struct($el) {
	/*
		настройка значений через PHP12_balans_setup
	*/
	return array(
		'num_3'   => _num($el['num_3'])//включение счётчика
	) + _elementStruct($el);
}
function _element27_print($el) {
	return $el['name'];
}
function _element27_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return @$u[$col];
}
function _element27_struct_vvv($el, $cl) {
	return array(
		'id' => _num($cl['id']),
		'znak' => _num($cl['num_8']),  /*   0 - сложение
											1 - вычитание
											2 - умножение
											3 - деление
										*/
		'title' => $cl['title']
	);
}
function _element27_template_docx($el, $u) {
	return _element27_print11($el, $u);
}

/* ---=== НАСТРОЙКА БАЛАНСА ===--- */
function PHP12_balans_setup($prm) {
	/*
		все действия через JS

		num_8: знак
	*/

	if(!$prm['unit_edit'])
		return _emptyMin('Настройка значений будет доступна<br>после вставки элемента в блок.');

	return '';
}
function PHP12_balans_setup_save($cmp, $val, $unit) {//сохранение содержания баланса
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений баланса
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает баланс
	*/

	if(!$parent_id = _num($unit['id']))
		return;

	$ids = '0';
	$update = array();

	if(!empty($val)) {
		if(!is_array($val))
			return;

		foreach($val as $r) {
			if(!$id = _num($r['id']))
				continue;
			$ids .= ','.$id;
			$update[] = array(
				'id' => $id,
				'znak' => _num($r['znak'])
			);
		}
	}

	//удаление значений, которые были удалены при настройке
	$sql = "DELETE FROM `_element`
			WHERE `parent_id`=".$parent_id."
			  AND `id` NOT IN (".$ids.")";
	query($sql);

	if(empty($update))
		return;

	foreach($update as $sort => $r) {
		$sql = "UPDATE `_element`
				SET `parent_id`=".$parent_id.",
					`num_8`=".$r['znak'].",
					`sort`=".$sort."
				WHERE `id`=".$r['id'];
		query($sql);
	}

	_BE('elem_clear');
}
function PHP12_balans_setup_vvv($prm) {
	if(!$u = $prm['unit_edit'])
		return array();
	if(!$el = _elemOne($u['id']))
		return array();

	return _element('vvv', $el);
}
