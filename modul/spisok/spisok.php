<?php

function _spisokCond($pe) {//������������ ������ � ��������� ������
	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_3']);
	$field = $dialog['field'];

	$cond = "`id`";
	if(isset($field['deleted']))
		$cond = "!`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($field['dialog_id']))
		$cond .= " AND `dialog_id`=".$pe['num_3'];

	$cond .= _spisokFilterSearch($pe);

	return $cond;
}
function _spisokCountAll($pe) {//��������� ������ ���������� ����� ������
	$key = 'SPISOK_COUNT_ALL'.$pe['id'];

	if(defined($key))
		return constant($key);

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($pe['num_3']);

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
			FROM `_page_element`
			WHERE `id`=".$pe_id;
	if(!$pe = query_assoc($sql))
		return '��������, ����������� ������, �� ����������.';

	//���� ��������� �������, ��������� ��������� �� ��������, ������� ��������� ������
	if(!$all = _spisokCountAll($pe))
		return $pe['txt_1'];

	return
		$r['txt_1'].' '.
		$all.' '.
		_end($all, $r['txt_2'], $r['txt_3'], $r['txt_4']);
}
function _spisokInc($dialog, $spisok) {//��������� ������
	$send = array();

	//����� ���������� ������� � ��������� �������
	foreach($dialog['component'] as $cmp_id => $cmp) {
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
function _spisokShow($pe, $next=0) {//������, ��������� �� ��������
	/*
	$pe:
		dialog_id = 14
		txt_1 - ��������� ������� �������
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

	$next: ��������� ���� ������, ������������ $pe['num_2']
	*/
	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	$limit = PAS ? 3 : $dv[$pe['num_2']];//�����

	//������, ����� ������� �������� ������ ������
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);

	$CMP = $spDialog['component']; //�������� ������
	$spTable = $spDialog['base_table'];


	$all = _spisokCountAll($pe);

	//��������� ������ ������
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE "._spisokCond($pe)."
			ORDER BY `dtime_add` DESC
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	$inc = _spisokInc($spDialog, $spisok);

	$html = '<div class="_empty">'._br($pe['txt_1']).'</div>';

	foreach($spisok as $id => $sp)
		if(empty($sp['num']))
			$spisok[$id]['num'] = $sp['id'];

	//����� �������� ����
	if($spisok)
		switch($pe['num_1']) {
			case 181://�������
				if(empty($pe['txt_5'])) {
					$html = '<div class="_empty"><span class="fs15 red">������� �� ���������.</span></div>';
					break;
				}

				$colArr = explode(',', $pe['txt_5']);

				$html = !$next ? '<table class="_stab'._dn(!$pe['num_7'], 'small').'">' : '';
				//����������� �������� �������
				if($pe['num_5'] && !$next) {
					$html .= '<tr>';
					foreach($colArr as $col) {
						$ex = explode('&', $col);
						$html .= '<th>'.$ex[1];
					}
				}

				foreach($spisok as $sp) {
					$html .= '<tr'.($pe['num_6'] ? ' class="over1"' : '').'>';
					foreach($colArr as $col) {
						$ex = explode('&', $col);
						switch($ex[0]) {
							case -1://num
								$html .= '<td class="w15 grey r">'._spisokColLink($sp['num'], $pe, $sp, $col);
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
													'onclick'=>'_dialogOpen('.$dialog_id.','.$sp['id'].')',
													'class' => 'ml5 mr5'
												));
								//._iconDel();
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
								$v = _spisokColSearchBg($v, $pe, $el['id']);
								$html .= '<td class="'.implode(' ', $cls).'">'.
											_spisokColLink($v, $pe, $sp, $col);
						}
					}
				}

				if($limit * ($next + 1) < $all) {
					$count_next = $all - $limit * ($next + 1);
					if($count_next > $limit)
						$count_next = $limit;
					$html .=
						'<tr class="over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
							'<td colspan="20">'.
								'<tt class="db center curP fs14 blue pad8">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>';
				}


				$html .= !$next ? '</table>' : '';
				break;
			case 182: $html = _spisokUnit182_template($pe, $spisok, $all, $limit, $next);	break;
			default:
				$html = '����������� ������� ��� ������: '.$pe['num_1'];
		}

	return $html;
}
function _spisokColLink($txt, $pe, $sp, $col) {//������ �������� ������� � ������, ���� �����
	$ex = explode('&', $col);

	if(!@$ex[3])
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

	return '<a href="'.URL.$link.'"'._dn(!$pe['num_7'], 'class="fs12"').'>'.$txt.'</a>';
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
	$txt = preg_replace(_regFilter($val), '<em class="fndd'._dn(!$pe['num_7'], 'fs12').'">\\1</em>', $txt, 1);
	$txt = win1251($txt);

	return $txt;
}
function _spisokFilterSearchVal($pe) {//��������� ��������� �������� � ������ ������, �������������� �� ���� ������
	$key = 'ELEM_SEARCH_VAL'.$pe['id'];

	if(defined($key))
		return constant($key);

	$sql = "SELECT `v`
			FROM `_page_element`
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
	$dialog = _dialogQuery($pe['num_3']);
	$cmp = $dialog['component'];

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

	//����������� ������ ������� ����������� �������
	if($dialog['base_table'] == '_page')
		return _dialogPageList();

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

function _spisokUnitSetup($block_id, $width, $grid_id=0) {//������ ��� ��������� ������� ������
	$sql = "SELECT
				*,
				'' `elem`
			FROM `_block`
			WHERE `is_spisok`=".$block_id."
			ORDER BY `y`,`x`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty min">'.
				'������ ����.'.
				'<div class="mt10 pale">������� � ��������� ������.</div>'.
			   '</div>';

	//����������� ��������� � �����
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	foreach(query_arr($sql) as $r) {
		unset($arr[$r['block_id']]['elem']);
		$r['block'] = $arr[$r['block_id']];
		$arr[$r['block_id']]['elem'] = $r;
	}

	foreach($arr as $id => $r)
		$arr[$id]['child'] = array();

	$child = array();
	foreach($arr as $id => $r)
		$child[$r['parent_id']][$id] = $r;

	$block = _blockChildArr($child, $block_id);

	define('GRID_ID', $grid_id);

	return _blockLevel($block, $width);
}
function _spisokUnit182_template($pe, $spisok, $all, $limit, $next) {//������������ ������ �� �������
	$dialog = _dialogQuery($pe['num_3']);
	$cmp = $dialog['component'];

	if(PAS)
		return '<div class="_empty">������ <b class="fs14">'.$dialog['spisok_name'].'</b></div>';

	//��������� ������ �������
	$sql = "SELECT
				*,
				'' `elem`				
			FROM `_block`
			WHERE `is_spisok`=".$pe['block_id']."
			ORDER BY `y`,`x`";
	if(!$arr = query_arr($sql))
		return '<div class="_empty"><span class="fs15 red">������ ������� ������ �� ��������.</span></div>';

	//����������� ��������� � �����
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `block_id` IN ("._idsGet($arr).")";
	if(!$elem = query_arr($sql))
		return '<div class="_empty"><span class="fs15 red">������ ������� ������ �� ��������.</span></div>';

	foreach($elem as $r) {
		unset($arr[$r['block_id']]['elem']);
		$r['block'] = $arr[$r['block_id']];
		$r['real_txt'] = '';
		$arr[$r['block_id']]['elem'] = $r;
	}

	foreach($arr as $id => $r)
		$arr[$id]['child'] = array();


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
							case 2: $txt = $sp[$cmp[$el['num_1']]['col_name']]; break;//�������� �������
						}				}
				$r['elem']['real_txt'] = $txt;
			}
			$child[$r['parent_id']][$id] = $r;
		}

		$block = _blockChildArr($child, $pe['block_id']);
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







