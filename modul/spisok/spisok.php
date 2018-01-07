<?php

function _spisokCond($pe) {//������������ ������ � ��������� ������
	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_1']);
	$field = $dialog['field'];

	$cond = "`id`";
	if(isset($field['deleted']))
		$cond = "!`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($field['dialog_id']))
		$cond .= " AND `dialog_id`=".$pe['num_1'];

	$cond .= _spisokFilterSearch($pe);

	return $cond;
}
function _spisokCountAll($pe) {//��������� ������ ���������� ����� ������
	$key = 'SPISOK_COUNT_ALL'.$pe['id'];

	if(defined($key))
		return constant($key);

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_1']);

	$sql = "SELECT COUNT(*)
			FROM `".$dialog['base_table']."`
			WHERE "._spisokCond($pe);
	$all = _num(query_value($sql));

	define($key, $all);

	return $all;
}
function _spisokElemCount($r) {//������������ �������� � ����������� ���������� ������ ��� ������ �� ��������
	if(!$pe_id = $r['num_1'])
		return '������ �� ������.';

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$pe_id;
	if(!$pe = query_assoc($sql))
		return '��������, ����������� ������, �� ����������.';

	//���� ��������� �������, ��������� ��������� �� ��������, ������� ��������� ������
	if(!$all = _spisokCountAll($pe))
		return $pe['txt_1'];

	return
	_end($all, $r['txt_1'], $r['txt_3'], $r['txt_5']).
	' '.
	$all.
	' '.
	_end($all, $r['txt_2'], $r['txt_4'], $r['txt_6']);
}
function _spisokInc($dialog, $spisok) {//��������� ������
	$send = array();

	//����� ���������� ������� � ��������� �������
	foreach($dialog['cmp'] as $cmp_id => $cmp) {
		//���� �� ������
		if($cmp['type_id'] != 2)
			continue;

		//���� �� �������� ��������� �������
		if($cmp['num_4'] != 3)
			continue;

		//������� ����� ������������� ������ �� ������ ������� ������
		if(!$ids = _idsGet($spisok, $cmp['col_name']))
			continue;

		//��������� ������ �� ���������� ������
		$incDialog = _dialogQuery($cmp['num_1']);
		$sql = "SELECT *
				FROM `".$incDialog['base_table']."`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$cmp['num_1']."
				  AND `id` IN (".$ids.")";
		$send[$cmp['num_1']] = query_arr($sql);
	}

	return $send;
}
function _spisokShow($el, $next=0) {//������, ��������� �� ��������
	/*
	$el:
		dialog_id = 14: ������
            num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
			num_2 - ����� (���������� �����, ��������� �� ���� ���)
			txt_1 - ��������� ������� �������






		--- ������ ---
		txt_2[child] - ��� [182]: ������������ ����������
		txt_5 - ��������� ������ ����������� �������
			-1: num ���������� �����
			-2: dtime_add
			-3: ������ ����������
			-4: ������������ �����
		num_1 - ������� ��� ������: [181] => ������� [182] => ������
		num_2 - �����, ���������� ����� ������, ������������� �� ���� ���
		num_3 - id �������, ����� ������� �������� ������ ������
		num_4[child] - id �������. ���� ������������� - ��.txt_5
		num_5 - ������� "���������� ����� �������"
		num_6 - ������� "������������ ������ ��� ���������"
		num_7 - ������� "����� ������"

		$next: ��������� ���� ������, ������������ $el['num_2']

		dialog_id = 23: �������

	*/
	if(!$dialog = _dialogQuery($el['dialog_id']))
		return '�������������� ������ id'.$el['dialog_id'];

	$dv = $dialog['v_ass'];

	$limit = PAS ? 3 : $dv[$el['num_2']];//�����

	//������, ����� ������� �������� ������ ������
	$dialog_id = $el['num_1'];
	$spDialog = _dialogQuery($dialog_id);

	//�������� ������
	$CMP = $spDialog['cmp'];
	$spTable = $spDialog['base_table'];

	$all = _spisokCountAll($el);

	//��������� ������ ������
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE "._spisokCond($el)."
			ORDER BY `dtime_add` DESC
			LIMIT ".($limit * $next).",".$limit;
	if(!$spisok = query_arr($sql))
		return '<div class="_empty">'._br($el['txt_1']).'</div>';

	//	$inc = _spisokInc($spDialog, $spisok);

	foreach($spisok as $id => $sp)
		if(empty($sp['num']))
			$spisok[$id]['num'] = $sp['id'];

	//����� �������� ����
	switch($el['dialog_id']) {
		case 23://�������
			if(empty($el['txt_5']))
				return '<div class="_empty"><span class="fs15 red">������� �� ���������.</span></div>';

			$colArr = explode(',', $el['txt_5']);

			$html = !$next ? '<table class="_stab'._dn(!$el['num_7'], 'small').'">' : '';
			//����������� �������� �������
			if($el['num_5'] && !$next) {
				$html .= '<tr>';
				foreach($colArr as $col) {
					$ex = explode('&', $col);
					$html .= '<th>'.$ex[1];
				}
			}

			foreach($spisok as $sp) {
				$html .= '<tr'.($el['num_6'] ? ' class="over1"' : '').'>';
				foreach($colArr as $col) {
					$ex = explode('&', $col);
					switch($ex[0]) {
						case -1://num
							$html .= '<td class="w15 grey r">'._spisokColLink($sp['num'], $el, $sp, @$ex[3]);
							break;
						case -2://����
							$tooltip = '">';
							if(isset($sp['viewer_id_add'])) {
								$u = _viewer($sp['viewer_id_add']);
								$msg = '��'.($u['sex'] == 2 ? '�� ' : '���� ').$u['first_name'].' '.$u['last_name'];
								$tooltip = _tooltip($msg, -40);
							}
							$html .= '<td class="w50 wsnw r grey fs12 curD'.$tooltip.FullData($sp['dtime_add'], 0, 1);
							break;
						case -3://������ ����������
							$html .= '<td class="pad0 w15 wsnw">'.
										_iconEdit(array(
											'class' => 'dialog-open ml3',
											'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$sp['id']
										)).
										_iconDel(array(
											'class' => 'dialog-open mr3',
											'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$sp['id'].',to_del:1'
										));
							break;
						default:
							$el = $CMP[$ex[0]];
							if($el['col_name'] == 'app_any_spisok')
								$v = $sp['app_id'] ? 0 : 1;
							else
								$v = $sp[$el['col_name']];

							$cls = array();
							//�������
							if($el['type_id'] == 1) {
								$cls[] = 'center';
								$v = $v ? '<div class="icon icon-ok curD"></div>' : '';
							}
							//������� ������� ������
							if($el['type_id'] == 2)
								if($el['num_4'] == 3) {
									$incDialog = _dialogQuery($el['num_1']);
									$col_name = $incDialog['component'][$el['num_2']]['col_name'];

									$unit_id = $v;
									$v = $inc[$el['num_1']][$v][$col_name];

									if($incDialog['action_id'] == 2)
										$v = '<a href="'.URL.'&p='.$incDialog['action_page_id'].'&id='.$unit_id.'">'.$v.'</a>';

							}
							$v = _spisokColSearchBg($v, $el, $el['id']);
							$html .= '<td class="'.implode(' ', $cls).'">'.
										_spisokColLink($v, $el, $sp, @$ex[3]);
					}
				}
			}

			if($limit * ($next + 1) < $all) {
				$count_next = $all - $limit * ($next + 1);
				if($count_next > $limit)
					$count_next = $limit;
				$html .=
					'<tr class="over1" onclick="_spisokNext($(this),'.$el['id'].','.($next + 1).')">'.
						'<td colspan="20">'.
							'<tt class="db center curP fs14 blue pad8">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>';
			}


			$html .= !$next ? '</table>' : '';
			return $html;
		case 14: return _spisokUnit182_template($el, $spisok, $all, $limit, $next);	break;
		default: return '����������� ������� ��� ������: '.$el['num_1'];
	}
}
function _spisokColLink($txt, $pe, $sp, $allow) {//������ �������� ������� � ������, ���� �����
	if(!$allow)
		return $txt;

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_3']);

	//�� ��������� ������� ��������
	$link = '&p='.$pe['page_id'];

	//���� ������� �������� ���������, �� ������ �������� �� ��������
	if($dialog['base_table'] == '_page')
		$link = '&p='.$sp['id'];

	//���� ������ �������������, �� ������� �� �������� ���������� ������������ �� ��� ����� todo ��������
	if($dialog['base_table'] == '_vkuser')
		$link = '&viewer_id='.$sp['viewer_id'];

	//���� ������� �������� �������� ����� �������� �������� ������
	if($dialog['action_id'] == 2)
		$link = '&p='.$dialog['action_page_id'].'&id='.$sp['id'];

	return '<a href="'.URL.$link.'" class="inhr">'.$txt.'</a>';
}
function _spisokColSearchBg($txt, $pe, $cmp_id) {//��������� �������� ������� ��� ��������� (�������) ������
	$val = _spisokFilterSearchVal($pe);
	if(!strlen($val))
		return $txt;

	//������������� ������ �������, �� ������� ������������ �����
	$colIds = _idsAss($pe['txt_3']);
	//���� �� ������ ������� ����� ��������, �� ��������� ������ ��������� �������
	if(!isset($colIds[$cmp_id]))
		return $txt;

	$val = utf8($val);
	$txt = utf8($txt);
	$txt = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $txt, 1);
	$txt = win1251($txt);

	return $txt;
}
function _spisokFilterSearchVal($pe) {//��������� ��������� �������� � ������ ������, �������������� �� ���� ������
	$key = 'ELEM_SEARCH_VAL'.$pe['id'];

	if(defined($key))
		return constant($key);

	$sql = "SELECT `v`
			FROM `_element`
			WHERE `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_3`=".$pe['id'];
	$v = query_value($sql);

	define($key, $v);

	return $v;
}
function _spisokFilterSearch($pe) {//��������� �������� �������-������ ��� ������
	//���� ����� �� ������������ �� �� ����� ��������, �� �����
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	$val = _spisokFilterSearchVal($pe);
	if(!strlen($val))
		return '';


	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		$arr[] = "`".$cmp[$cmp_id]['col_name']."` LIKE '%".addslashes($val)."%'";
	}

	if(empty($arr))
		return '';

	return " AND (".implode($arr, ' OR ').")";
}

function _spisokList($dialog_id, $component_id, $v='', $unit_id=0) {//������ ������� (���� ������ ��� select)
	$dialog = _dialogQuery($dialog_id);

	if(!$colName = $dialog['component'][$component_id]['col_name'])
		$colName = 'id';

	$cond = "`app_id`=".APP_ID."
		 AND `dialog_id`=".$dialog_id;
	if($v)
		$cond .= " AND `".$colName."` LIKE '%".addslashes($v)."%'";
	if($unit_id)
		$cond .= " AND `id`<=".$unit_id;

	$sql = "SELECT `id`,`".$colName."`
			FROM `".$dialog['base_table']."`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT 50";
	return query_selArray($sql);
}

function _spisokUnit182_template($pe, $spisok, $all, $limit, $next) {//������������ ������ �� �������
	$dialog = _dialogQuery($pe['num_1']);
	$cmp = $dialog['cmp'];

	if(PAS)
		return '<div class="_empty">������ <b class="fs14">'.$dialog['spisok_name'].'</b></div>';

	if(!$arr = _blockArr('spisok', $pe['block_id'], 'arr'))
		return '<div class="_empty"><span class="fs15 red">������ ������� ������ �� ��������.</span></div>';

	$send = '';
	foreach($spisok as $sp) {
		$child = array();
		foreach($arr as $id => $r) {
			if($el = $r['elem']) {//���� ������� ���� � �����
				$txt = '';
				switch($el['num_1']) {
					case -1: $txt = $sp['num']; break;//���������� �����
					case -2: $txt = FullData($sp['dtime_add'], 0, 1); break; //���� ��������
					case -4: $txt = _br($el['txt_2']); break;//������������ �����
					default:
						switch($el['num_2']) {
							case 1: $txt = $cmp[$el['num_1']]['label_name']; break;//�������� �������
							case 2://�������� �������
								$txt = $sp[$cmp[$el['num_1']]['col_name']];
								$txt = _spisokColSearchBg($txt, $pe, $el['num_1']);
								$txt = _spisokColLink($txt, $pe, $sp, $el['num_7']);
								break;
						}				}
				$r['elem']['real_txt'] = $txt;
			}
			$child[$r['parent_id']][$id] = $r;
		}

		$block = _blockArrChild($child);
		$send .= _blockLevel($block, $pe['block']['width']);
	}

	if($limit * ($next + 1) < $all) {
		$count_next = $all - $limit * ($next + 1);
		if($count_next > $limit)
			$count_next = $limit;
		$send .=
			'<div class="mt5 over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
				'<tt class="db center curP fs14 blue pad10">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>'.
			'</div>';
	}

	return $send;
}







