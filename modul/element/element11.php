<?php

/* [11] ������� �������� ������ */
function _element11_struct($el, $ELM=array()) {
	/*
		������� �������� ����� ������� PHP12_v_choose
	*/

	global $G_ELEM;
	if(empty($ELM))
		$ELM = $G_ELEM;

	$send = array(
		'parent_id' => _num($el['parent_id']),

		'txt_2'     => $el['txt_2'],    //id ��������, ���������� �� �������, ������� ������ ������ ������
								        //�������� �������� ��������� ����� �������: 256,1312,560
		'txt_7'     => $el['txt_7'],    //����� ����� (��� ������� ��������)
		'txt_8'     => $el['txt_8']     //����� ������ (��� ������� ��������)
	) + _elementStruct($el);

	if($last_id = _idsLast($el['txt_2']))
		if(isset($ELM[$last_id])) {
			$el11 = $ELM[$last_id];

			//��������� ��������� ������ (������� 11)
			if(_elemRule($el11['dialog_id'], 11)) {
				$send['stl'] = 1; //��� JS
				$send['color'] = $el['color'];
				$send['font']  = $el['font'];
				$send['size']  = $el['size'] ? _num($el['size']) : 13;
			}

			//��������� ��������� �������� �� �������� ��� �������� �������
			if(_elemRule($el11['dialog_id'], 16))
				$send['url_use'] = 1;

			//�������� ������������
			if($el11['dialog_id'] == 60) {
				$send['width'] = empty($el['width']) ? 30 : _num($el['width']);
				$send['num_7'] = _num($el['num_7']);
				$send['num_8'] = _num($el['num_8']);
				$send['immg'] = 1;
			}
	}

	return $send;
}
function _element11_struct_title($el, $ELM, $DLGS=array()) {
	$el['title'] = '';
	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!isset($ELM[$id]))
			return $el;

		$ell = $ELM[$id];

		//��� ����������� ���� �� �������
		if($ell['dialog_id'] == 60) {
			$el['title'] = _imageNo($el['width'], $el['num_8']);
			return $el;
		}

		//��������� ��������
		if(_elemIsConnect($ell)) {
			$dlg = $DLGS[$ell['num_1']];
			$el['title'] .= $dlg['name'].' � ';
			continue;
		}

		$el['title'] .= _element('title', $ell);
	}
	return $el;
}
function _element11_js($el) {
	$send = _elementJs($el);

	//�������������� �������� ��� �����������
	if($last = _idsLast($el['txt_2']))
		if($ell = _elemOne($last)) {
			if($ell['dialog_id'] == 60)
				$send += array(
					'num_7' => $el['num_7'],//[60] ����������� ������
					'num_8' => $el['num_8'] //[60] ����������� ����
				);
			//��������� ��������� ������� �����������
			if(_elemRule($ell['dialog_id'], 14))
				$send['eye'] = 1;
			}

	return $send;
}
function _element11_print($el, $prm) {
	if(!$u = @$prm['unit_get'])
		return $el['title'];
	if(empty($el['txt_2']))
		return _msgRed('[11] ��� ids ���������');

	foreach(_ids($el['txt_2'], 'arr') as $id) {
		if(!$ell = _elemOne($id))
			return _msgRed('-ell-yok-');

		if(_elemIsConnect($ell))
			return _element29_print11($el, $u);

		if(!_elemIsConnect($ell)) {
			$ell['elp'] = $el;//������� ������������� �������� (��� ��������� ��� ������� ������)
			return _element('print11', $ell, $u);
		}

		if(empty($ell['col']))
			return _msgRed('-cnn-col-yok-');

		$col = $ell['col'];
		$u = $u[$col];

		if(is_array($u))
			continue;

		if($ell['dialog_id'] == 29)
			return $ell['txt_1'];

		return _msgRed('�������� �����������');
	}

	return _msgRed('-11-yok-');
}

