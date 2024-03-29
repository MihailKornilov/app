<?php

/* [57] Меню переключения блоков */
function _element57_struct($el) {
	/*
		для настройки блоков используется функция PHP12_menu_block_setup
	*/
	return array(
		'num_1'   => _num($el['num_1']),/* внешний вид меню:
											1158 - Маленькие синие кнопки
											1159 - С нижним подчёркиванием
                                            13534 - вертикальное
										*/
		'num_2'   => _num($el['num_2']),//запоминать выбранный пункт меню
		'txt_1'   => $el['txt_1']       //содержание в формате JS
	) + _elementStruct($el);
}
function _element57_print($el, $prm) {
	$vvv = _element('vvv', $el);
	$def = 0;
	foreach($vvv as $r)
		if($r['def']) {
			$def = $r['id'];
			break;
		}

	//последняя позиция пункта меню
	if($el['num_2']) {
		$EL_COO = '57_'.$el['id'];
		if($v = _num(@$_COOKIE[$EL_COO]))
			$def = $v;
	}

	$v = _elemPrintV($el, $prm, $def);

	$type = array(
		1158 => 2,
		1159 => 3,
		13534 => 4
	);

	$type_id = $type[$el['num_1']];

	$razdel = '';
	foreach($vvv as $n => $r) {
		$sel = _dn($v != $r['id'], 'sel');
		$curd = _dn(!$prm['blk_setup'], 'curD');
		$ml10 = $type_id == 2 && $n ? ' ml10' : '';
		$razdel .= '<a class="link'.$ml10.$sel.$curd.'">'.$r['title'].'</a>';
	}

	return '<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
		   '<div class="_menu'.$type_id.'">'.$razdel.'</div>';
}
function _element57_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';
	if(!$id = _num(@$u[$col]))
		return '';
	if(!$vvv = _decode($el['txt_1']))
		return '';

	foreach($vvv as $r)
		if($r['id'] == $id)
			return $r['title'];

	return '';
}
function _element57_vvv($el) {//пункты меню
	return _decode($el['txt_1']);
}
function _element57punkt($bl, $prm) {//скрытие блока, если он является пунктом меню и пока не отображается
	global $_57PUNKT;

	if(!isset($_57PUNKT)) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `app_id` IN (0,".APP_ID.") 
				  AND `dialog_id`=57";
		if(!$_57PUNKT = DB1::arr($sql))
			return $bl;
	}

	foreach($_57PUNKT as $r) {
		if(!$vvv = _decode($r['txt_1']))
			continue;

		$sel = 0;
		if($u = $prm['unit_edit'])
			if($col = $r['col'])
				if(!empty($u[$col]))
					$sel = $u[$col];

		foreach($vvv as $v) {
			if($sel) {
				if($sel == $v['id'])
					continue;
			} else
				if(!isset($_COOKIE['57_'.$r['id']])) {
					if($v['def'])
						continue;
				} else
					if($_COOKIE['57_'.$r['id']] == $v['id'])
						continue;

			$ass = _idsAss($v['blk']);
			if(!isset($ass[$bl['id']]))
				continue;
			$bl['hidden'] = true;
			return $bl;
		}
	}

	return $bl;
}
function _elem57inc($spisok) {
	foreach($spisok as $id => $sp) {
		if(!$dlg_id = _num(@$sp['dialog_id']))
			continue;
		if(!$DLG = _dialogQuery($dlg_id))
			continue;

		foreach($DLG['cmp'] as $el) {
			if($el['dialog_id'] != 57)
				continue;
			if(!$col = _elemCol($el))
				continue;

			$spisok[$id][$col.'_title'] = _element57_print11($el, $sp);
		}
	}

	return $spisok;
}

function PHP12_menu_block_setup() {//используется в диалоге [57]
	return '';
}
function PHP12_menu_block_setup_save($cmp, $val, $unit) {//сохранение данных о пунктах меню
	if(empty($unit['id']))
		return;
	if(!$col = $cmp['col'])
		return;

	$save = array();

	if(!empty($val))
		if(is_array($val))
			foreach($val as $r) {
				if(!$id = _num($r['id']))
					continue;
				if(!$title = _txt($r['title']))
					continue;
				$save[] = array(
					'id' => $id,
					'title' => $title,
					'blk' => _ids($r['blk']),
					'def' =>_num($r['def'])
				);
			}

	$save = json_encode($save);

	$sql = "UPDATE `_element`
			SET `".$col."`='".addslashes($save)."'
			WHERE `id`=".$unit['id'];
	DB1::query($sql);

	_elemOne($unit['id'], true);
}
function PHP12_menu_block_setup_vvv($prm) {
	if(empty($prm['unit_edit']))
		return array();
	if(!$el = _elemOne($prm['unit_edit']['id']))
		return array();
	if(!$arr = _element('vvv', $el))
		return array();

	foreach($arr as $id => $r) {
		$arr[$id]['id'] = _num($r['id']);
		$arr[$id]['def'] = _num($r['def']);
	}

	return $arr;
}
