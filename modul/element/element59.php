<?php

/* [59] Связка списка при помощи кнопки */
function _element59_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],        //текст кнопки
		'txt_5'   => $el['txt_5'],        //НЕ ЗАНИМАТЬ (используется под фильтр в [29])
		'num_1'   => _num($el['num_1']),  //id диалога, через который вносятся данные выбираемого списка
		'num_3'   => _num($el['num_3']),  //блокировать выбор
		'num_4'   => _num($el['num_4']),  //id диалога, которое открывается при нажатии на кнопку
		'num_6'   => _num($el['num_6'], 1),//по умолчанию [85]

		'issp' => _num($el['num_1'])
	) + _elementStruct($el);
}
function _element59_title_get($el, $unit_id) {
	if(!$dialog_id = _num($el['num_1']))
		return '';
	if(!$dlg = _dialogQuery($dialog_id))
		return '';
	if(!$dlg['spisok_elem_id'])
		return _msgRed('<a class="dialog-setup inhr" val="dialog_id:'.$dlg['id'].',menu:4">Диалог '.$dlg['id'].'</a> колонка по умолчанию не настроено');
	if(!$u = _spisokUnitQuery($dlg, $unit_id))
		return '';
	if(!$col = _elemCol($dlg['spisok_elem_id']))
		return '';
	if(!isset($u[$col]))
		return '';

	return $u[$col];
}
function _element59_print($el, $prm) {
	$v = _elemPrintV($el, $prm, $el['num_6']);
	$v = _elem29PageSel($el['num_1'], $v);
	$v = _elem29DialogSel($el, $prm, $v);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	_button(array(
		'attr_id' => _elemAttrId($el, $prm).$el['afics'],
		'name' => $el['txt_1'],
		'color' => 'grey',
		'width' => $el['width'],
		'small' => 1,
		'class' => _dn(!$v)._dn(!$prm['blk_setup'] && !$el['num_3'], 'curD')
	)).
	'<div class="prel'._dn($v).'">'.
	(!$el['num_3'] ?
		'<div style="position:absolute;top:2px;right:3px;z-index:100" class="icon icon-del-red pl tool" data-tool="Отменить выбор"></div>'
	: '').
		'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
	'</div>';
}
function _element59_history($el, $u) {
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';
	if(!$elem_id = $DLG['spisok_elem_id'])
		return '';
	if(!$ell = _elemOne($elem_id))
		return '';
	if(empty($ell['col']))
		return '';

	$col = $ell['col'];

	if(empty($u[$col]))
		return '';
	if(is_array($u[$col]))
		return '';

	return $u[$col];
}
function _spisok59unit($elem_id, $unit_id, $return='html') {//выбранное значение при связке списков через кнопку [59]
	switch($return) {
		case 'html': $send = ''; break;
		case 'assoc':
		default:
			$send = array();
	}

	if(!$unit_id)
		return $send;
	if(!$el = _elemOne($elem_id))
		return $send;
	if(!$dialog_id = _num($el['num_1']))
		return $send;
	if(!$dlg = _dialogQuery($dialog_id))
		return $send;
	if(!$prm['unit_get'] = _spisokUnitQuery($dlg, $unit_id))
		return $send;

	if($return == 'html')
		return _blockHtml('spisok', $elem_id, $prm);

	return _arrNum($prm['unit_get']);
}

