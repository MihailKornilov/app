<?php

/* [37] Select: выбор колонки таблицы (SA) */
function _element37_struct($el) {
	return _elementStruct($el);
}
function _element37_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'width' => @$el['width'],
		'value' => _elem37v($el, $prm)
	));
}
function _element37_vvv($el, $prm) {
	if(!$block = _blockOne($prm['srce']['block_id']))
		return array();
	//список колонок может быть получен при условии, если элемент размещается в диалоге
	if($block['obj_name'] != 'dialog')
		return array();
	if(!$dlg = _dialogQuery($block['obj_id']))
		return array();

	//выбранная колонка, если редактирование записи
	$uCol = '';
	if($u = $prm['unit_edit'])
		$uCol = $u['col'];

	$field = _elemVvv37fieldDop($uCol);

	//если диалог родительский, получение колонок родителя
	if($parent_id = $dlg['dialog_id_parent']) {
		$field = _elemVvv37parent($parent_id, $field);
		$PAR = _dialogQuery($parent_id);
		//если таблицы одинаковые, отправка только родительских колонок
		if(!$dlg['table_1'] || $dlg['table_1'] == $PAR['table_1'])
			return $field;
	}

	$field = _elemVvv37field($dlg, $uCol, $field);

	return $field;
}
function _elem37v($el, $prm) {//получение значения колонки
	if($v = _elemPrintV($el, $prm))
		return $v;

	//если редактирование, колонка не подставляется
	if(!empty($prm['unit_edit']))
		return '';

	//--- Aвтоматическое подставление колонки ---
	if(!$DLG = _dialogQuery($prm['srce']['dialog_id']))
		return '';
	if(!$type_id = $DLG['element_type'])
		return '';

	$type = _elemColType($type_id);
	foreach(_element37_vvv($el, $prm) as $r) {
		if($r['busy'])
			continue;

		$ex = explode('_', $r['id']);
		if(empty($ex[0]))
			continue;

		if($type == $ex['0'])
			return $r['id'];
	}

	return '';
}
function _elemVvv37field($dlg, $uCol, $send=array()) {//колонки по каждой таблице
	if(!$dlg['table_1'])
		return $send;

	//получение используемых колонок
	$colUse = array();
	foreach($dlg['cmp'] as $r) {
		if(empty($r['col']))
			continue;
		$colUse[$r['col']] = !empty($r['name']) ? '<i class="color-555 ml10">('.$r['name'].')</i>' : '';
	}

	//колонки, которые не должны выбираться
	$fieldNo = array(
		'id' => 1,
		'id_old' => 1,
		'num' => 1,
		'app_id' => 1,
		'cnn_id' => 1,
		'parent_id' => 1,
		'user_id' => 1,
		'page_id' => 1,
		'block_id' => 1,
		'element_id' => 1,
		'dialog_id' => 1,
		'obj_id' => 1,
		'width' => 1,
		'color' => 1,
		'font' => 1,
		'size' => 1,
		'mar' => 1,
		'sort' => 1,
		'deleted' => 1,
		'user_id_add' => 1,
		'user_id_del' => 1,
//		'dtime_add' => 1,
		'dtime_del' => 1,
		'dtime_create' => 1,
		'app_id_last' => 1
	);

	foreach($dlg['field1'] as $col => $k) {
		if(isset($fieldNo[$col]))
			continue;

		$color = '';
		$busy = 0;//занята ли колонка
		$name = '';
		if(isset($colUse[$col])) {
			$color = $uCol == $col ? 'b color-pay' : 'b red';
			$busy = $uCol == $col ? 0 : 1;
			$name = $colUse[$col];
		}
		$u = array(
			'id' => $col,
			'title' => $col,
			'busy' => $busy,
			'content' =>
				'<div class="'.$color.'">'.
					'<span class="pale">'.$dlg['name'].'.</span>'.
					$col.
					$name.
				'</div>'

		);
		$send[] = $u;
	}

	return $send;
}
function _elemVvv37fieldDop($uCol) {//дополнительная колонка - из другого списка
	$send=array();

	if(!$col_id = _num($uCol))
		return $send;
	if(!$el = _elemOne($col_id))
		return $send;
	if(!$col = $el['col'])
		return $send;
	if(!$DLG = _dialogQuery($el['block']['obj_id']))
		return $send;

	$send[] = array(
		'id' => $col_id,
		'title' => $DLG['name'].': '.$el['name'],
		'content' => $DLG['name'].': '.$el['name'].' <b class="pale">'.$col.'</b>'
	);

	return $send;
}
function _elemVvv37parent($dlg_id, $send) {//колонки родительского диалога
	if(!$dlg = _dialogQuery($dlg_id))
		return $send;

	foreach($dlg['cmp'] as $id => $cmp) {
		if(empty($cmp['col']))
			continue;

		$send[] = array(
			'id' => $id,
			'title' => $dlg['name'].': '.$cmp['name'],
			'content' => $dlg['name'].': '.$cmp['name'].' <b class="pale">'.$cmp['col'].'</b>'
		);
	}

	return $send;
}
function _elem37changeCol($cmp_id, $unit_id) {//перенос данных, если было изменено имя колонки
	if(!defined('ELEM37_CHANAGE_COL'))
		return;
	if(!$cmp = _elemOne($cmp_id))
		return;
	if($cmp['dialog_id'] != 37)
		return;
	if(!$unit = _elemOne($unit_id))
		return;
	if(!$bl = _blockOne($unit['block_id']))
		return;
	if($bl['obj_name'] != 'dialog')
		return;
	//диалог, при помощи которого вносятся данные списка
	if(!$DLG = _dialogQuery($bl['obj_id']))
		return;

	$ex = explode('-', ELEM37_CHANAGE_COL);

	$sql = "UPDATE "._queryFrom($DLG)."
			SET `".$ex[1]."`=`".$ex[0]."`,
				`".$ex[0]."`=DEFAULT
			WHERE `dialog_id`=".$DLG['id'];
	query($sql);
}






