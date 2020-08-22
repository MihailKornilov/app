<?php

/* [27] Cумма значений записи */
function _element27_struct($el) {
	/*
		настройка значений через PHP12_balans_setup
	*/
	return array(
		'txt_1'   => $el['txt_1'],      /* [12] содержание суммы: слагаемые
											znak:
												+ плюс
												- минус
												* умножить
												/ делить
											elem_id: элемент, по которому будет получено значение
										*/
		'num_3'   => _num($el['num_3']) // [1] включение счётчика
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
function _element27_vvv($el) {
	if(!$el['txt_1'])
		return array();
	if(!$send = json_decode($el['txt_1'], true))
		return array();

	foreach($send as $id => $r)
		$send[$id]['title'] = _elemIdsTitle($r['elem_id']);

	return _arrNum($send);
}
function _element27_template_docx($el, $u) {
	return _element27_print11($el, $u);
}

/* [27] Настройка */
function PHP12_balans_setup($prm) {
	/*
		все действия через JS
	*/
	return '';
}
function PHP12_balans_setup_save($cmp, $val, $unit) {//сохранение содержания баланса
	/*
		$cmp  - компонент-функция, размещающий в диалоге настройку значений баланса
		$val  - значения, полученные для сохранения
		$unit - элемент, который размещает баланс
	*/

	if(!$unit['id'])
		return;
	if(!$col = $cmp['col'])
		return;

	$save = array();

	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$elem_id = _num($r['elem_id']))
					continue;

				switch($r['znak']) {
					case '+':
					case '-':
					case '*':
					case '/': break;
				}
				if(!$r['znak'])
					continue;

				$save[] = array(
					'znak' => $r['znak'],
					'elem_id' => $elem_id
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	query($sql);

	_elemOne($unit['id'], true);
	_element27update($unit['id']);
}
function PHP12_balans_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();

	return _element('vvv', $el);
}


function _element27update($elem_id, $unit_ids=0) {//пересчёт сумм значений записи
	if(!$el = _elemOne($elem_id))
		return;
	if($el['dialog_id'] != 27)
		return;
	if(!$colSrc = _elemCol($el))
		return;

	//блок, в котором размещается "баланс"
	if(!$block_id = _num($el['block_id']))
		return;
	if(!$BL = _blockOne($block_id))
		return;
	if($BL['obj_name'] != 'dialog')
		return;
	//диалог, в котором размещаются значения (данные этого списка будут обновляться)
	if(!$DSrc = _dialogQuery($BL['obj_id']))
		return;

	//предварительное обнуление значений перед обновлением
	$sql = "UPDATE "._queryFrom($DSrc)."
			SET `".$colSrc."`=0
			WHERE "._queryWhere($DSrc).
($unit_ids ? " AND `t1`.`id` IN (".$unit_ids.")" : '');
	query($sql);

	//получение всех слагаемых баланса
	if(!$item = _element('vvv', $el))
		return;

	//составление суммы из слагаемых
	$upd = '';
	foreach($item as $n => $r) {
		if(!$col = _elemCol($r['elem_id']))
			continue;

		$znak = $r['znak'];
		if(!$n && $r['znak'] != '-')
			$znak = '';

		$upd .= $znak."`".$col."`";
	}

	if(!$upd)
		return;

	//процесс обновления
	$sql = "UPDATE "._queryFrom($DSrc)."
			SET `".$colSrc."`=".$upd."
			WHERE "._queryWhere($DSrc).
($unit_ids ? " AND `t1`.`id` IN (".$unit_ids.")" : '');
	query($sql);

	_element27accum($elem_id);
}
function _element27childChange($child_id, $unit_ids=0) {//если было изменёно какое-либо слагаемое баланса (или данные в записи), обновить этот баланс
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=27";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $elem_id => $el) {
		if(!$vvv = _element27_vvv($el))
			continue;
		foreach($vvv as $v)
			if($v['elem_id'] == $child_id) {
				_element27update($elem_id, $unit_ids);
				break;
			}
	}
}
function _element27counter($child_id, $uid, $dlg, $unit, $sum=0, $sumOld=0) {//внесение данных в счётчик, если по какому-либо слагаемому баланса было произведено изменение
	$sql = "SELECT *
			FROM `_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=27
			  AND `num_3`";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $elem_id => $el) {
		if(!$vvv = _element27_vvv($el))
			continue;

		foreach($vvv as $v) {
			if($v['elem_id'] != $child_id)
				continue;

			if(!$col = _elemCol($el))
				return;
			//блок, в котором расположен счётчик
			if(!$bl = _blockOne($el['block_id']))
				return;
			if($bl['obj_name'] != 'dialog')
				return;
			//диалог, в котором расположен счётчик
			if(!$dlg27 = _dialogQuery($bl['obj_id']))
				return;
			//запись, из которой будет взят значение баланса
			if(!$u27 = _spisokUnitQuery($dlg27, $uid))
				return;
			if(!isset($u27[$col]))
				return;

			$balans = round($u27[$col], 10);

			_counterV($el['id'], $uid, $dlg, $unit['id'], $balans, $sum, $sumOld);
			break;
		}
	}
}

function _element27inDialog($DLG, $unitNew, $unitOld) {//пересчёт баланса, если в диалоге изменилось значение, которое является слагаемым в этом балансе
	$DLG = _dialogParent($DLG);
	foreach($DLG['cmp'] as $el) {
		if(!$col = _elemCol($el))
			continue;
		if(!isset($unitNew[$col]))
			continue;
		if(!empty($unitOld))
			if(isset($unitNew[$col]) && isset($unitOld[$col]))
				if($unitNew[$col] == $unitOld[$col])
					continue;
		_element27childChange($el['id'], $unit_ids=0);
	}
}

function _element27accum($elem_id=0) {//сбор всех изменённых балансов для последующей проверки их участия в фильтрах других счётчиков
	global $_27ACCUM;

	if(!isset($_27ACCUM))
		$_27ACCUM = array();

	if(!$elem_id)
		return $_27ACCUM;

	$_27ACCUM[$elem_id] = $elem_id;

	return array();
}
function _element27inFilter() {//пересчёт значения счётчика, если баланс используется в его фильтре
	if(!$elm27 = _element27accum())
		return;

	//поиск элемента баланса во всех суммах приложения
	$sql = "SELECT *
			FROM `_element`
			WHERE `dialog_id`=55
			  AND `app_id`=".APP_ID."
			  AND LENGTH(`txt_1`)";
	if(!$arr = query_arr($sql))
		return;

	foreach($arr as $r) {
		if(!$F = _decode($r['txt_1']))
			continue;

		foreach($F as $v) {
			if(!$elem_id = _idsLast($v['elem_id']))
				continue;
			if(empty($elm27[$elem_id]))
				continue;

			//пересчёт суммы, использующую баланс в фильтре
			_element55update($r['id']);
		}
	}
}

