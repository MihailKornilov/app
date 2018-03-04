<?php
function _spisokFilterCache() {//����������� �������� ������
	if($send = _cache())
		return $send;

	$send = array(
		'spisok' => array(),//��� ������ � ���������
		'filter' => array() //������������� ������ �������-������ -> ��������
	);

	$sql = "SELECT *
			FROM `_user_spisok_filter`
			WHERE `user_id`=".USER_ID;
	if($arr = query_arr($sql)) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id` IN ("._idsGet($arr,'element_id_filter').")";
		$elFilter = query_arr($sql);
		foreach($arr as $r) {
			$filter_id = $r['element_id_filter'];
			$spisok_id = $r['element_id_spisok'];
			if(empty($elFilter[$filter_id]))
				continue;
			$v = array(
				'elem' => $elFilter[$filter_id],
				'v' => $r['v']
			);
			$send['spisok'][$spisok_id][$filter_id] = $v;
			$send['filter'][$filter_id] = $v;
		}
	}

	return _cache($send);
}
function _spisokFilter($i='all', $elem_id=0) {//��������� �������� �������� ������
	if($i == 'cache_clear') {
		_cache('clear', '_spisokFilterCache');
		return true;
	}

	$F = _spisokFilterCache();

	//�������� ����������� ��������-�������
	if($i == 'v') {
		if(!$elem_id)
			return '';
		if(!isset($F['filter'][$elem_id]))
			return '';
		return $F['filter'][$elem_id]['v'];
	}

	//������ ���������-�������� ��� ����������� ������
	if($i == 'spisok') {
		if(!$elem_id)
			return array();
		if(!isset($F['spisok'][$elem_id]))
			return array();
		return $F['spisok'][$elem_id];
	}


	return $F;
}

function _spisokCountAll($el) {//��������� ������ ���������� ����� ������
	$key = 'SPISOK_COUNT_ALL'.$el['id'];

	if(defined($key))
		return constant($key);

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($el['num_1']);

	$sql = "SELECT COUNT(*)
			FROM `".$dialog['base_table']."`
			WHERE "._spisokCond($el);
	$all = _num(query_value($sql));

	define($key, $all);

	return $all;
}
function _spisokElemCount($r) {//������������ �������� � ����������� ���������� ������ ��� ������ �� ��������
	if(!$elem_id = $r['num_1'])
		return '������ �� ������.';
	if(!$elem = _elemQuery($elem_id))
		return '��������, ����������� ������, �� ����������.';

	//���� ��������� �������, ��������� ��������� �� ��������, ������� ��������� ������
	if(!$all = _spisokCountAll($elem))
		return $elem['txt_1'];

	return
	_end($all, $r['txt_1'], $r['txt_3'], $r['txt_5']).
	' '.
	$all.
	' '.
	_end($all, $r['txt_2'], $r['txt_4'], $r['txt_6']);
}
function _spisokInclude($spisok, $CMP) {//��������� ������
	foreach($CMP as $cmp_id => $cmp) {//����� ���������� ������� � ��������� �������
		//������ �������� ��������� �������
		if($cmp['dialog_id'] != 29)
			continue;

		//������ ���� ��������� ��� �������
		if(!$col = $cmp['col'])
			continue;

		//������� ����� ������������� ������ �� ������ ������� ������
		if(!$ids = _idsGet($spisok, $col))
			continue;

		//��������� ������ �� ���������� ������
		$incDialog = _dialogQuery($cmp['num_1']);
		$sql = "SELECT *
				FROM `".$incDialog['base_table']."`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$cmp['num_1']."
				  AND `id` IN (".$ids.")";
		if(!$arr = query_arr($sql))
			continue;

		//�������������� ����� �������� �� ������ � ������� ������� ������
		foreach($spisok as $id => $r) {
			$connect_id = $r[$col];
			if(empty($arr[$connect_id]))
				continue;
			$spisok[$id][$col] = $arr[$connect_id];
		}
	}

	return $spisok;
}
function _spisokImage($spisok, $CMP) {//������� ��������
	foreach($CMP as $cmp_id => $cmp) {//����� ���������� ������� � �������������
		//������ �������� ����������� "�������� �����������"
		if($cmp['dialog_id'] != 60)
			continue;

		//������ ���� ��������� ��� �������
		if(!$col = $cmp['col'])
			continue;

		foreach($spisok as $id => $r)
			$spisok[$id][$col] = 'no img';

		$sql = "SELECT *
				FROM `_image`
				WHERE `obj_name`='_spisok'
				  AND `obj_id` IN ("._idsGet($spisok).")
				  AND !`sort`";
		if($arr = query_arr($sql))
			foreach($arr as$r) {
				$spisok[$r['obj_id']][$col] = _imageHtml($r);
			}
	}

	return $spisok;
}
function _spisokShow($ELEM, $next=0) {//������, ��������� �� ��������
	/*
	$ELEM:
		dialog_id = 14: ������
            num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
			num_2 - ����� (�����, ���������� �����, ��������� �� ���� ���)
			txt_1 - ��������� ������� �������

		dialog_id = 23: �������
            num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
			num_2 - ����� (�����, ���������� �����, ��������� �� ���� ���)
			txt_1 - ��������� ������� �������
			num_3 - ����� ������ �������
			num_4 - ������������ ������ ��� ��������� ����
			num_5 - ���������� ����� �������
			txt_2 - ids ��������� ����� �������. ���� �������� �������� � ������� _element
		�������� ����������� �������� 31.
		��������� ��������:
			num_1 - id ��������-�������� �� �������
			txt_1 - ��� ��������� TR
			width - ������ �������
			font
			color
			txt_6 - pos (�������)
			num_2 - �������� �������
			sort
	*/
	if(!$dialog = _dialogQuery($ELEM['dialog_id']))
		return '�������������� ������ id'.$ELEM['dialog_id'];

	$limit = PAS ? 3 : $ELEM['num_2'];//�����

	//������, ����� ������� �������� ������ ������
	$dialog_id = $ELEM['num_1'];
	$spDialog = _dialogQuery($dialog_id);

	//�������� ������
	$CMP = $spDialog['cmp'];
	$spTable = $spDialog['base_table'];

	$all = _spisokCountAll($ELEM);

	//��������� ������ ������
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE "._spisokCond($ELEM)."
			ORDER BY `id` DESC
			LIMIT ".($limit * $next).",".$limit;
	if(!$spisok = query_arr($sql))
		return '<div class="_empty">'._br($ELEM['txt_1']).'</div>';

	//������� �������� �� ��������� �������
	$spisok = _spisokInclude($spisok, $CMP);
	//������� ��������
	$spisok = _spisokImage($spisok, $CMP);

	//����� �������� ����
	switch($ELEM['dialog_id']) {
		//�������
		case 23://�������
			if(empty($ELEM['txt_2']))
				return '<div class="_empty"><span class="fs15 red">������� �� ���������.</span></div>';
			if(!$ELEM['num_1'])
				return '<div class="_empty"><span class="fs15 red">�� ������ ������ ��� ������ ������.</span></div>';
			if(!$tabDialog = _dialogQuery($ELEM['num_1']))
				return '<div class="_empty"><span class="fs15 red">������ <b>'.$ELEM['num_1'].'</b> �� ����������.</span></div>';

			//��������� �������� ������� �������
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$ELEM['txt_2'].")
					  AND `block_id`=-".$ELEM['id']."
					ORDER BY `sort`";
			$tabCol = query_arr($sql);

			//��������� ������������ �������� ������
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN ("._idsGet($tabCol, 'txt_2').")";
			$tabElemUse = query_arr($sql);

			$html = !$next ? '<table class="_stab'._dn(!$ELEM['num_3'], 'small').'">' : '';

			//����������� �������� �������
			if(!$next && $ELEM['num_5']) {
				$html .= '<tr>';
				foreach($tabCol as $tr)
					$html .= '<th>'.$tr['txt_7'];
			}

			foreach($spisok as $sp) {
				$html .= '<tr'.($ELEM['num_4'] ? ' class="over1"' : '').'>';
				foreach($tabCol as $td) {
//					$txt = '';
					$cls = array();
					switch($td['dialog_id']) {
/*
						case 1111://�� �������
							$elemUse = $tabElemUse[$td['txt_2']];
							$el = $CMP[$elemUse['id']];

							//�������� �� ��������� �������
							if(!$col = $el['col'])
								break;

							//� ������ �� ���������� ����� �������
							if(!isset($sp[$col]))
								break;

							//�������� �� ������� ������
							if($el['dialog_id'] == 29) {
								$txt = $sp[$col]['txt_1'];
								$txt = _spisokUnitUrl($txt, $sp[$col], $td['url']);
								break;
							}

							$txt = _br($sp[$col]);
							$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
							$txt = _spisokUnitUrl($txt, $sp, $td['url']);
						break;
*/
						case 34: $cls[] = 'pad0'; //������ ����������
						default:
							$txt = _elemUnit($td, $sp);
							$txt = _spisokUnitUrl($txt, $sp, $td['url']);
							break;
					}
					$cls[] = $td['font'];
					$cls[] = $td['color'];
					$cls[] = $td['txt_8'];//pos - �������
					$cls = array_diff($cls, array(''));
					$cls = implode(' ', $cls);
					$cls = $cls ? ' class="'.$cls.'"' : '';
					$html .= '<td'.$cls.' style="width:'.$td['width'].'px">'.$txt;
				}
			}

			if($limit * ($next + 1) < $all) {
				$count_next = $all - $limit * ($next + 1);
				if($count_next > $limit)
					$count_next = $limit;
				$html .=
					'<tr class="over5 curP center blue" onclick="_spisokNext($(this),'.$ELEM['id'].','.($next + 1).')">'.
						'<td colspan="20">'.
							'<tt class="db '.($ELEM['num_3'] ? 'fs13 pt3 pb3' : 'fs14 pad5').'">'.
								'�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').
							'</tt>';
			}

			$html .= !$next ? '</table>' : '';
			return $html;

		//������
		case 14:
			if(!$BLK = _block('spisok', $ELEM['block_id'], 'block_arr'))
				return '<div class="_empty"><span class="fs15 red">������ ������� ������ �� ��������.</span></div>';

			//��������� ���������, ������������� ����������� � ������
			$ELM = _block('spisok', $ELEM['block_id'], 'elem_arr');

			//������ ������� ������ � ������ ��������
			$ex = explode(' ', $ELEM['mar']);
			$width = floor(($ELEM['block']['width'] - $ex[1] - $ex[3]) / 10) * 10;

			$send = '';
			foreach($spisok as $sp) {
				$child = array();
				foreach($BLK as $id => $r) {
/*
					$r['elem'] = array();
					if($elem_id = $r['elem_id']) {//���� ������� ���� � �����
						$txt = '';
						$el = $ELM[$elem_id];
						switch($el['num_1']) {
							default:
								$tmp = $ELM_TMP[$el['num_1']];
								switch($tmp['dialog_id']) {
									case 10: $txt = $tmp['txt_1']; break;//������������ �����
									default://�������� �������
										if($col = $tmp['col'])
											$txt = $sp[$col];
										$txt = _spisokColSearchBg($txt, $ELEM, $el['num_1']);
								}
						}

						$el['tmp'] = 1;
						$el['block'] = $r;
						$el['txt_real'] = $txt;
						$r['elem'] = $el;
					}
*/
					if($r['elem_id']) {
						$elem = $ELM[$r['elem_id']];
						$elem['block'] = $r;
						$r['elem'] = $elem;
					} else
						$r['elem'] = array();

					$child[$r['parent_id']][$id] = $r;
				}

				$block = _blockArrChild($child);
				$send .=
					'<div class="sp-unit" val="'.$sp['id'].'">'.
						_blockLevel($block, $width, 0, 0, 1, $sp).
					'</div>';
			}

			if($limit * ($next + 1) < $all) {
				$count_next = $all - $limit * ($next + 1);
				if($count_next > $limit)
					$count_next = $limit;
				$send .=
					'<div class="over5" onclick="_spisokNext($(this),'.$ELEM['id'].','.($next + 1).')">'.
						'<tt class="db center curP fs14 blue pad10">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>'.
					'</div>';
			}

			return $send;
	}

	return '����������� ������� ��� ������: '.$ELEM['num_1'];
}

function _spisokUnitNum($u) {//���������� ����� - �������� ������� ������
	if(empty($u))
		return '���������� �����';
	if(empty($u['num']))
		return $u['id'];
	return $u['num'];
}
function _spisokUnitData($unit, $el) {//���� � ����� - �������� ������� ������ [33]
	if(empty($unit) || empty($unit['dtime_add']))
		return '���� � �����';

	$dtime = $unit['dtime_add'];

	if(!preg_match(REGEXP_DATE, $dtime))
		return '������������ ������ ����';

	$ex = explode(' ', $dtime);
	$d = explode('-', $ex[0]);

	//�����
	$hh = '';
	if($el['num_4'] && !empty($ex[1])) {
		$h = explode(':', $ex[1]);
		$hh .= ' '.$h[0].':'.$h[1];
	}

	if($el['num_1'] == 31)
		return $d[2].'/'.$d[1].'/'.$d[0].$hh;

	$hh = $hh ? ' �'.$hh : '';

	if($el['num_3']) {
		$dCount = floor((strtotime($ex[0]) - TODAY_UNIXTIME) / 3600 / 24);
		switch($dCount) {
			case -1: return '�����'.$hh;
			case 0: return '�������'.$hh;
			case 1: return '������'.$hh;
		}
	}

	return
		_num($d[2]).                                                     //����
		' '.($el['num_1'] == 29 ? _monthFull($d[1]) : _monthCut($d[1])). //�����
		($el['num_2'] && $d[0] == YEAR_CUR ? '' : ' '.$d[0]).            //���
		$hh;                                                             //�����
}
function _spisokUnitIconEdit($dialog_id, $unit_id) {//������ ���������� - �������� ������� ������ [34]
	return
		_iconEdit(array(
			'class' => 'dialog-open pl',
			'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$unit_id
		)).
		_iconDel(array(
			'class' => 'dialog-open pl',
			'val' => 'dialog_id:'.$dialog_id.',unit_id:'.$unit_id.',del:1'
		));
}

function _spisokUnitUrl($txt, $sp, $is_url) {//������ �������� ������� � ������
	if(!$is_url)//���� ����������� �� �����
		return $txt;

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($sp['dialog_id']);

	//�� ��������� ������� ��������
	$link = '&p='._page('cur');

	//���� ������� �������� ���������, �� ������ �������� �� ��������
	if($dialog['base_table'] == '_page')
		$link = '&p='.$sp['id'];

	//���� ������ �������������, �� ������� �� �������� ���������� ������������ �� ��� ����� todo ��������
	if($dialog['base_table'] == '_vkuser')
		$link = '&viewer_id='.$sp['user_id'];

	//���� ���� ��������, ������� ��������� �������� ������
	if($page_id = _page('spisok_id', $dialog['id']))
		$link = '&p='.$page_id.'&id='.$sp['id'];

	return '<a href="'.URL.$link.'" class="inhr">'.$txt.'</a>';
}
function _spisokColSearchBg($txt, $el, $cmp_id) {//��������� �������� ������� ��� ��������� (�������) ������
	$search = false;
	$v = '';

	//����� ��������-�������-������
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
		}

	if(!$search)
		return $txt;
	if(!$v)
		return $txt;

	//������������� ������ �������, �� ������� ������������ �����
	$colIds = _idsAss($search['txt_2']);
	//���� �� ������ ������� ����� ��������, �� ��������� ������ ��������� �������
	if(!isset($colIds[$cmp_id]))
		return $txt;

	$v = utf8($v);
	$txt = utf8($txt);
	$txt = preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $txt, 1);
	$txt = win1251($txt);

	return $txt;
}

function _spisokCond($el) {//������������ ������ � ��������� ������
	//$el - �������, ������� ��������� ������. 14 ��� 23.
	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($el['num_1']);
	$field = $dialog['field'];

	$cond = "`id`";
	if(isset($field['deleted']))
		$cond = "!`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($field['dialog_id']))
		$cond .= " AND `dialog_id`=".$el['num_1'];

	$cond .= _spisokCondSearch($el);
	$cond .= _spisokCond52($el);
	$cond .= _spisokCond62($el);

	return $cond;
}
function _spisokCondSearch($el) {//�������� �������-������ ��� ������
	$search = false;
	$v = '';

	//����� ��������-�������-������
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
			break;
		}

	if(!$search)
		return '';
	if(!$v)
		return '';

	//���� ����� �� ������������ �� �� ����� ��������, �� �����
	if(!$colIds = _ids($search['txt_2'], 1))
		return '';

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($el['num_1']);
	$cmp = $dialog['cmp'];

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($cmp[$cmp_id]))
			continue;
		$arr[] = "`".$cmp[$cmp_id]['col']."` LIKE '%".addslashes($v)."%'";
	}

	if(!$arr)
		return '';

	return " AND (".implode($arr, ' OR ').")";
}
function _spisokCond52($el) {//������ �� ������ �������
	//����� ��������, ������� �������� ������� ������ ������ ��� ����� ������
	$sql = "SELECT COUNT(*)
			FROM `_element`
			WHERE `dialog_id`=52
			  AND `num_1`=".$el['id']."
			LIMIT 1";
	if(!query_value($sql))
		return '';

	//��������, ����� ������ ��� �������� ������ �� ��������
	if($el['block']['obj_name'] != 'page')
		return ' AND !`id`';

	//��������, �� ������� �������� ������
	if(!$page = _page($el['block']['obj_id']))
		return ' AND !`id`';

	//id �������, ������� ������ �������� ����������� �� ��������
	if(!$spisok_id = $page['spisok_id'])
		return ' AND !`id`';

	$cmp = false;
	foreach(_dialogParam($el['num_1'], 'cmp') as $r) {
		if($r['dialog_id'] != 29)
			continue;
		if($r['num_1'] != $spisok_id)
			continue;
		$cmp = $r;
	}

	if(!$cmp)
		return ' AND !`id`';

	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`id`';

	return " AND `".$cmp['col']."`=".$unit_id;
}
function _spisokCond62($el) {//������-�������
	$filter = false;
	$v = 0;

	//����� ��������-�������-�������
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 62) {
			$filter = $r['elem'];
			$v = $r['v'];
			break;
		}

	if(!$filter)
		return '';

	//������� �����������, ���� 1439: �����������, 1440 - �� �����������
	if($filter['num_1'] == 1439 && !$v)
		return '';
	if($filter['num_1'] == 1440 && $v)
		return '';

	//�������, ����������� ������
	$sql = "SELECT *
			FROM `_element`
			WHERE `block_id`=-".$filter['id'];
	if(!$cond = query_arr($sql))
		return '';

	//�������, �� ������� ����� ������������� ������
	$sql = "SELECT `id`,`col`
			FROM `_element`
			WHERE `id` IN ("._idsGet($cond, 'num_1').")";
	if(!$elCol = query_ass($sql))
		return '';

	/*
		 1: �����������
		 2: ������������
		 3: �����
		 4: �� �����
		 5: ������
		 6: ������ ��� �����
		 7: ������
		 8: ������ ��� �����
		 9: ��������
		10: �� ��������
	*/

	$send = '';
	foreach($cond as $r) {
		if(!$col = $elCol[$r['num_1']])
			continue;
		$val = addslashes($r['txt_8']);
		switch($r['num_8']) {
			case 1: $send.= " AND !`".$col."`"; break;
			case 2: $send.= " AND `".$col."`"; break;
			case 3: $send.= " AND `".$col."`='".$val."'"; break;
			case 4: $send.= " AND `".$col."`!='".$val."'"; break;
			case 5: $send.= " AND `".$col."`>'".$val."'"; break;
			case 6: $send.= " AND `".$col."`>='".$val."'"; break;
			case 7: $send.= " AND `".$col."`<'".$val."'"; break;
			case 8: $send.= " AND `".$col."`<='".$val."'"; break;
			case 9: $send.= " AND `".$col."` LIKE '%".$val."%'"; break;
			case 10:$send.= " AND `".$col."` NOT LIKE '%".$val."%'"; break;
		}
	}

	return $send;
}
function _spisokConnect($cmp_id, $v='', $sel_id=0) {//��������� ������ ������ ��� ������ (dialog_id:29)
	if(!$cmp_id)
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `id`=".$cmp_id;
	if(!$cmp = query_assoc($sql))
		return array();

	if(!$cmp['num_1'])
		return array();

	if(!$dialog = _dialogQuery($cmp['num_1']))
		return array();

	//��������� ��� ������� ��� ����������� ���������� Select
	$ex = explode(',', $cmp['txt_2']);
	$col0 = _num($ex[0]);
	$col1 = _num(@$ex[1]);
	if($cmp['txt_2']) {
		$sql = "SELECT `id`,`txt_2`
				FROM `_element`
				WHERE `id` IN ("._ids($cmp['txt_2']).")";
		if($ass = query_ass($sql)) {
			$sql = "SELECT `id`,`col`
					FROM `_element`
					WHERE `id` IN (".implode(',', $ass).")";
			if($cols = query_ass($sql)) {
				$col0 = $cols[$ass[$col0]];
				$col1 = @$cols[$ass[$col1]];
			}
		}
	}
	//�������� ������� ������� � �������
	$col0 = isset($dialog['field'][$col0]) ? $col0 : '';
	$col1 = isset($dialog['field'][$col1]) ? $col1 : '';

	$cond = "`dialog_id`=".$cmp['num_1'];
	if($v) {
		$cols = array();
		if($col0)
			$cols[] = "`".$col0."` LIKE '%".$v."%'";
		if($col1)
			$cols[] = "`".$col1."` LIKE '%".$v."%'";
		$cond .= $cols ? " AND (".implode(' OR ', $cols).")" : " AND !`id`";
	}
	$sql = "SELECT *
			FROM `_spisok`
			WHERE ".$cond."
			  AND !`deleted`
			ORDER BY `id` DESC
			LIMIT 50";
	if(!$arr = query_arr($sql))
		return array();

	//���������� ������� ������, ������� ���� ������� �����
	if($sel_id && empty($arr[$sel_id])) {
		$sql = "SELECT *
				FROM `_spisok`
				WHERE `dialog_id`=".$cmp['num_1']."
				  AND `id`=".$sel_id;
		if($unit = query_assoc($sql))
			$arr[$sel_id] = $unit;
	}

	$send = array();
	foreach($arr as $r) {
		$u = array(
			'id' => _num($r['id']),
			'title' => utf8($col0 ? $r[$col0] : '�������� �� ���������'),
			'content' => utf8($col0 ? $r[$col0] : '<div class="red">�������� �� ���������</div>')
		);
		if($col1 && $r[$col1])
			$u['content'] = utf8($r[$col0].'<div class="fs11 grey">'.$r[$col1].'</div>');
		if($v)
			$u['content'] = preg_replace(_regFilter(utf8($v)), '<em class="fndd">\\1</em>', $u['content'], 1);

		$send[] = $u;
	}

	return $send;
}
function _spisok59unit($cmp_id, $unit_id) {//��������� �������� ��� ������ ������� ����� ������ [59]
	if(!$unit_id)
		return '';
	if(!$el = _elemQuery($cmp_id))
		return '';
	if(!$dialog_id = _num($el['num_1']))
		return '';
	if(!$dlg = _dialogQuery($dialog_id))
		return '';

	$sql = "SELECT *
			FROM `".$dlg['base_table']."`
			WHERE `id`=".$unit_id;
	if(!$un = query_assoc($sql))
		return '';

	return _blockHtml('spisok', $el['block_id'], 350, 0, $un);
}









