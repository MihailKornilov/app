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

	if($i == 'page_js') {//�������� �������� � ������� JS �� ������� ������ �� ��� ����������
		$send = array();
		foreach($F['spisok'] as $id => $arr)
			foreach($arr as $elid => $el)
				$send[$id][$elid] = $el['v'];
		return $send;
	}

	return $F;
}

function _spisokIsSort($block_id) {//�����������, ����� �� ����������� ���������� ����� ������ (����� �������� 71)
	if(!$spisok_el = _block('spisok', $block_id, 'elem_arr'))
		return 0;

	foreach($spisok_el as $elem)
		if($elem['dialog_id'] == 71)
			return 1;

	return 0;
}

function _spisokCountAll($el) {//��������� ������ ���������� ����� ������
	$key = 'SPISOK_COUNT_ALL'.$el['id'];

	if(defined($key))
		return constant($key);

	//������, ����� ������� �������� ������ ������
	$dialog = _dialogQuery($el['num_1']);

	$sql = "SELECT COUNT(*)
			FROM `"._baseTable($dialog['table_1'])."` `t1`
			"._spisokJoin($dialog)."
			WHERE "._spisokCond($el);
	$all = _num(query_value($sql));

	define($key, $all);

	return $all;
}
function _spisokJoinField($dialog) {//����������� ������� ������� ������
	if(!$dialog['table_2'])
		return '';

	$send = '';
	foreach($dialog['cmp'] as $cmp) {
		if($cmp['table_num'] != 2)
			continue;
		if(empty($cmp['col']))
			continue;
		$send .= ',`t2`.`'.$cmp['col'].'`';
	}

	return $send;
}
function _spisokJoin($dialog) {//����������� ������� ������, ���� ���������
	if(!$dialog['table_2'])
		return '';

	return "INNER JOIN `"._baseTable($dialog['table_2'])."` `t2`
			ON `t1`.`id`=`t2`.`".$dialog['table_2_field']."`";
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
		if($cmp['dialog_id'] != 29 && $cmp['dialog_id'] != 59)
			continue;

		//������ ���� ��������� ��� �������
		if(!$col = $cmp['col'])
			continue;

		//������� ����� ������������� ������ �� ������ ������� ������
		if(!$ids = _idsGet($spisok, $col))
			continue;

		//��������� ������ �� ���������� ������
		$incDialog = _dialogQuery($cmp['num_1']);

		$cond = "`t1`.`id` IN (".$ids.")";
		if(isset($field['deleted']))
			$cond .= " AND !`t1`.`deleted`";
		if(isset($field['app_id']))
			$cond .= " AND `t1`.`app_id`=".APP_ID;
		if(isset($field['dialog_id']))
			$cond .= " AND `t1`.`dialog_id`=".$cmp['num_1'];

		$sql = "SELECT `t1`.*"._spisokJoinField($incDialog)."
				FROM `"._baseTable($incDialog['table_1'])."` `t1`
					  "._spisokJoin($incDialog)."
				WHERE ".$cond;
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
			foreach($arr as$r)
				$spisok[$r['obj_id']][$col] = _imageHtml($r, 80, 1);
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

	$all = _spisokCountAll($ELEM);

	$order = "`t1`.`id` DESC";
	if(_spisokIsSort($ELEM['block_id']))
		$order = "`sort`";

	//��������� ������ ������
	$sql = "SELECT `t1`.*"._spisokJoinField($spDialog)."
			FROM `"._baseTable($spDialog['table_1'])."` `t1`
			"._spisokJoin($spDialog)."
			WHERE "._spisokCond($ELEM)."
			ORDER BY ".$order."
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
								break;
							}

							$txt = _br($sp[$col]);
							$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
						break;
*/
						case 34: $cls[] = 'pad0'; //������ ����������
						default:
							$txt = _elemUnit($td, $sp);
							$txt = _spisokUnitUrl($td, $sp, $txt);
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

function _spisokUnitQuery($dialog, $unit_id) {//��������� ������ ������� ������
	if(!$dialog['table_1'])
		return array();

	$cond = "`t1`.`id`=".$unit_id;
	if(isset($dialog['field1']['app_id']) || isset($dialog['field2']['app_id']))
		$cond .= " AND `app_id`=".APP_ID;
	if(_baseTable($dialog['table_1']) != '_element' && isset($dialog['field1']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog['id'];
	$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
			FROM `"._baseTable($dialog['table_1'])."` `t1`
			"._spisokJoin($dialog)."
			WHERE ".$cond;
	return query_assoc($sql);
}
function _spisokUnitNum($el, $u) {//���������� ����� - �������� ������� ������
	if(empty($u))
		return _elemTitle($el['id']);
	if(empty($u['num']))
		return $u['id'];
	return $u['num'];
}
function _spisokUnitData($el, $unit) {//���� � ����� - �������� ������� ������ [33]
	if(empty($unit) || empty($unit['dtime_add']))
		return _elemTitle($el['id']);

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
function _spisokUnitUser($el, $u) {//�������� ������� ������ - ��� ������������
	if(empty($u))
		return $el['name'];

	if(empty($u['user_id_add']))
		return 'no user';

	return _user($u['user_id_add'], 'name');
}
function _spisokUnitIconEdit($el, $unit_id) {//������ ���������� - �������� ������� ������ [34]
	if(empty($unit_id))//����������� id ������� ������
		return '-no-unit';

	$dialog_id = 0;

	if($el['block_id'] < 0)
		if($el = _elemQuery(abs($el['block_id'])))
			if($el['dialog_id'] == 23)
				$dialog_id = $el['num_1'];

	if(!$dialog_id && empty($el['block']))//�� �������� � ��������� ������ �����
		return '-no-block';

	if(!$dialog_id)
		switch($el['block']['obj_name']) {
			case 'spisok':
				$key = 'ICON_EDIT_'.$el['id'];
				if(defined($key)) {
					$dialog_id = constant($key);
					break;
				}
				if(!$BL = _blockQuery($el['block']['obj_id']))//����� �� ����������
					return '-no-bl-spisok';
				if(empty($BL['elem']))//��� ��������, ������������ ������
					return '-no-el-spisok';

				$dialog_id = _num($BL['elem']['num_1']);
				define($key, $dialog_id);
				break;
			case 'page':
				if(!$page = _page($el['block']['obj_id']))
					return '-no-page';
				$dialog_id = $page['spisok_id'];
				break;
			default: return '-no-spisok';
		}

	if(!$dialog_id)
		return '-no-dialog-id';

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

function _spisokUnitUrl($el, $unit, $txt) {//������ �������� ������� � ������
	if(!$el['url'])//���� ����������� �� �����
		return $txt;
	if(empty($unit['id']))//����������� ������� ������
		return $txt;

	$dialog_id = 0;

	if($el['block_id'] < 0)
		if($el = _elemQuery(abs($el['block_id'])))
			if($el['dialog_id'] == 23)
				$dialog_id = $el['num_1'];

	if(!$dialog_id && empty($el['block']))//�� �������� � ��������� ������ �����
		return $txt;

	if(!$dialog_id)
		switch($el['block']['obj_name']) {
			case 'spisok':
				$key = 'ELEM_LINK_'.$el['id'];
				if(defined($key)) {
					$dialog_id = constant($key);
					break;
				}
				if($el['dialog_id'] == 11) {
					if(!$ids = _ids($el['txt_2'], 1))
						return $txt;
					if(!$c = count($ids))//������ ��������� �������
						return $txt;
					if(empty($ids[$c - 1]))
						return $txt;
					if(!$EL = _elemQuery($ids[$c - 1]))
						return $txt;
					if(!$EL['block']['obj_name'] == 'dialog')
						return $txt;

					$dialog_id = $EL['block']['obj_id'];
					define($key, $dialog_id);
					break;
				}
				if(!$BL = _blockQuery($el['block']['obj_id']))//����� �� ����������
					return $txt;
				if(empty($BL['elem']))//��� ��������, ������������ ������
					return $txt;

				$dialog_id = _num($BL['elem']['num_1']);
				define($key, $dialog_id);
				break;
			case 'page':
				if($el['dialog_id'] == 11) {
					if(!$ids = _ids($el['txt_2'], 1))
						return $txt;
					if(!$c = count($ids))//������ ��������� �������
						return $txt;
					if(empty($ids[$c - 1]))
						return $txt;
					if(!$EL = _elemQuery($ids[$c - 1]))
						return $txt;
					if(!$EL['block']['obj_name'] == 'dialog')
						return $txt;

					$dialog_id = $EL['block']['obj_id'];
					break;
				}
				if(!$page = _page($el['block']['obj_id']))
					return $txt;
				$dialog_id = $page['spisok_id'];
				break;
			default: return $txt;
		}

	if(!$dialog_id)
		return $txt;

	if(!$dlg = _dialogQuery($dialog_id))
		return $txt;

	//������ �� ��������, ���� ��� ������ �������
	if(_baseTable($dlg['table_1']) == '_page')
		return '<a href="'.URL.'&p='.$unit['id'].'" class="inhr">'.$txt.'</a>';

	if(!$page_id = _page('spisok_id', $dialog_id))
		return $txt;

	return '<a href="'.URL.'&p='.$page_id.'&id='.$unit['id'].'" class="inhr">'.$txt.'</a>';
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
	$field = $dialog['field1'];

	$cond = "`t1`.`id`";
	if(isset($field['deleted']))
		$cond = "!`t1`.`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `t1`.`app_id` IN (0,".APP_ID.")";
	if(isset($field['dialog_id']))
		$cond .= " AND `t1`.`dialog_id`=".$el['num_1'];

	$cond .= _spisokCondSearch($el);
	$cond .= _spisokCond52($el);
	$cond .= _spisokCond62($el);
	$cond .= _spisokCond77($el);

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
		$arr[] = "`t1`.`".$cmp[$cmp_id]['col']."` LIKE '%".addslashes($v)."%'";
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
		return ' AND !`t1`.`id`';

	//��������, �� ������� �������� ������
	if(!$page = _page($el['block']['obj_id']))
		return ' AND !`t1`.`id`';

	//id �������, ������� ������ �������� ����������� �� ��������
	if(!$spisok_id = $page['spisok_id'])
		return ' AND !`t1`.`id`';

	$cmp = false;
	foreach(_dialogParam($el['num_1'], 'cmp') as $r) {
		if($r['dialog_id'] != 29)
			continue;
		if($r['num_1'] != $spisok_id)
			continue;
		$cmp = $r;
	}

	if(!$cmp)
		return ' AND !`t1`.`id`';

	if(!$unit_id = _num(@$_GET['id']))
		return ' AND !`t1`.`id`';

	return " AND `t1`.`".$cmp['col']."`=".$unit_id;
}
function _spisokCond62($el) {//������-�������
	$send = '';

	//����� ��������-�������-�������
	foreach(_spisokFilter('spisok', $el['id']) as $F) {
		$filter = $F['elem'];

		if($filter['dialog_id'] != 62)
			continue;

		$v = $F['v'];

		//������� �����������, ���� 1439: �����������, 1440 - �� �����������
		if($filter['num_1'] == 1439 && !$v)
			continue;
		if($filter['num_1'] == 1440 && $v)
			continue;

		//�������, ����������� ������
		$sql = "SELECT *
				FROM `_element`
				WHERE `block_id`=-".$filter['id'];
		if(!$cond = query_arr($sql))
			continue;

		//�������, �� ������� ����� ������������� ������
		$sql = "SELECT `id`,`col`
				FROM `_element`
				WHERE `id` IN ("._idsGet($cond, 'txt_2').")";
		if(!$elCol = query_ass($sql))
			continue;

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

		foreach($cond as $r) {
			if(!$col = $elCol[$r['txt_2']])
				continue;
			$val = addslashes($r['txt_8']);
			switch($r['num_8']) {
				case 1: $send.= " AND !`t1`.`".$col."`"; break;
				case 2: $send.= " AND `t1`.`".$col."`"; break;
				case 3: $send.= " AND `t1`.`".$col."`='".$val."'"; break;
				case 4: $send.= " AND `t1`.`".$col."`!='".$val."'"; break;
				case 5: $send.= " AND `t1`.`".$col."`>'".$val."'"; break;
				case 6: $send.= " AND `t1`.`".$col."`>='".$val."'"; break;
				case 7: $send.= " AND `t1`.`".$col."`<'".$val."'"; break;
				case 8: $send.= " AND `t1`.`".$col."`<='".$val."'"; break;
				case 9: $send.= " AND `t1`.`".$col."` LIKE '%".$val."%'"; break;
				case 10:$send.= " AND `t1`.`".$col."` NOT LIKE '%".$val."%'"; break;
			}
		}
	}

	return $send;
}
function _spisokCond77($el) {//������-���������
	$filter = false;
	$v = '';

	//����� ��������-�������-���������
	foreach(_spisokFilter('spisok', $el['id']) as $r)
		if($r['elem']['dialog_id'] == 77) {
			$filter = true;
			$v = $r['v'];
			break;
		}

	if(!$filter)
		return '';
	if(!$v)
		return ' AND !`id`';

	$ex = explode(':', $v);

	if(empty($ex[1]))
		return " AND `dtime_add` LIKE '".$v."%'";

	return " AND `dtime_add`>='".$ex[0]." 00:00:00' AND `dtime_add`<='".$ex[1]." 23:59:59'";
}
function _spisok29connect($cmp_id, $v='', $sel_id=0) {//��������� ������ ������ ��� ������ (dialog_id:29)
	if(!$cmp_id)
		return array();
	if(!$cmp = _elemQuery($cmp_id))
		return array();
	if(!$cmp['num_1'])
		return array();
	if(!$dialog = _dialogQuery($cmp['num_1']))
		return array();
	if(!$elem_ids = _ids($cmp['txt_2']))//��������, ���������� id ���������, ������������� ���������� select
		return array();

	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN (".$elem_ids.")";
	if(!$elem_arr = query_arr($sql))
		return array();

	$S = array();//������ � ������������ ��� ���������� select
	foreach($elem_arr as $el)
		$S[] = _spisok29connectGet($el, $v);

	$cond = array();
	foreach($S as $n => $r)
		$cond[] = $r['cond'];
	$cond = array_diff($cond, array(''));
	$cond = $cond ? " AND (".implode(' OR ', $cond).")" : '';

	$field = $dialog['field1'];

	$cond = "`t1`.`id`".$cond;
	if(isset($field['deleted']))
		$cond .= " AND !`t1`.`deleted`";
	if(isset($field['app_id']))
		$cond .= " AND `t1`.`app_id`=".APP_ID;
	if(isset($field['dialog_id']))
		$cond .= " AND `t1`.`dialog_id`=".$cmp['num_1'];

	$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
			FROM `"._baseTable($dialog['table_1'])."` `t1`
				  "._spisokJoin($dialog)."
			WHERE ".$cond."
			ORDER BY ".(isset($field['sort']) ? "`sort`," : '')."`id` DESC
			LIMIT 50";
	if(!$spisok = query_arr($sql))
		return array();

	//���������� ������� ������, ������� ���� ������� �����
	if($sel_id && empty($arr[$sel_id])) {
		$sql = "SELECT `t1`.*"._spisokJoinField($dialog)."
				FROM `"._baseTable($dialog['table_1'])."` `t1`
					  "._spisokJoin($dialog)."
				WHERE `t1`.`id`=".$sel_id;
		if($unit = query_assoc($sql))
			$spisok[$sel_id] = $unit;
	}

	foreach($S as $n => $r)
		if($r['cnn']) {
			$sql = "SELECT `id`,`".$r['col1']."`
					FROM `_spisok`
					WHERE `id` IN ("._idsGet($spisok, $r['col0']).")";
			$S[$n]['cnnAss'] = query_ass($sql);
		}

	$send = array();
	foreach($spisok as $r) {
		$title = '�������� �� ���������';
		if($S[0]['col0']) {
			$title = $r[$S[0]['col0']];
			if($S[0]['cnn'])
				$title = $title ? $S[0]['cnnAss'][$title] : '';
		}

		$content = '';
		if(isset($S[1]) && $S[1]['col0']) {
			$content = $r[$S[1]['col0']];
			if($S[1]['cnn'])
				$content = $content ? $S[1]['cnnAss'][$content] : '';
		}
		if($content)
			$content = '<div class="fs11 grey">'.$content.'</div>';

		$u = array(
			'id' => _num($r['id']),
			'title' => $title,
			'content' => $title.$content
		);
		if($v) {
			$txt = utf8($u['content']);
			$txt = preg_replace(_regFilter(utf8($v)), '<em class="fndd">\\1</em>', $txt, 1);
			$u['content'] = win1251($txt);
		}

		$send[] = $u;
	}

	return $send;
}
function _spisok29connectGet($el, $v) {
	$send = array(
		'col0' => '',       //��� ������� �������� ������
		'col1' => '',       //��� ������� ������������ ������
		'cnn' => 0,         //��� �� ����������� ������
		'cnnAss' => array(),//������������� ������ ������������ ������
		'cond' => ''
	);

	$ids = $el['txt_2'];

	$sql = "SELECT *
			FROM `_element`
			WHERE `id` IN (".$ids.")";
	if(!$arr = query_arr($sql))
		return $send;

	$ids = _ids($ids, 1);
	$id0 = $ids[0];
	$send['col0'] = $arr[$id0]['col'];
	if(count($ids) == 1 && $v)
		$send['cond'] = "`".$send['col0']."` LIKE '%".addslashes($v)."%'";
	if(count($ids) == 2) {
		$send['cnn'] = 1;
		$id1 = $ids[1];
		$dlg_id = _num($arr[$id0]['num_1']);
		$send['col1'] = $arr[$id1]['col'];

		if($v) {
			$sql = "SELECT `id`,`".$send['col1']."`
					FROM `_spisok`
					WHERE `dialog_id`=".$dlg_id."
					  AND `".$send['col1']."` LIKE '%".addslashes($v)."%'
					LIMIT 1000";
			if($cnnAss = query_ass($sql)) {
				$send['cond'] = "`".$send['col0']."` IN ("._idsGet($cnnAss, 'key').")";
				return $send;
			}
			$send['cond'] = "!`id`";
		}
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
			FROM `"._baseTable($dlg['table_1'])."`
			WHERE `id`=".$unit_id;
	if(!$un = query_assoc($sql))
		return '';

	return _blockHtml('spisok', $el['block_id'], 350, 0, $un);
}

function _spisokCmpConnectIdGet($el) {//��������� id ������������ ������, ���� ����� ����� ������������ ������ (��� ��������, ����������� �������� ������)
	if($el['dialog_id'] != 29)//������ ��� ������
		return 0;
	if(!$get_id = _num(@$_GET['id']))
		return 0;
	if(!$page_id = _page('cur'))
		return 0;
	if(!$page = _page($page_id))
		return 0;
	if(!$page['spisok_id'])//�������� �� �������� ��������
		return 0;
	if($page['spisok_id'] == $el['num_1'])//���� ������ �������� ���������, ����������� ��������, ������� $_GET['id']
		return $get_id;

	if(!$dlg = _dialogQuery($page['spisok_id']))
		return 0;

	foreach($dlg['cmp'] as $cmp)
		if($cmp['dialog_id'] == 29 && $cmp['num_1'] == $el['num_1']) {
			$sql = "SELECT *
					FROM `_spisok`
					WHERE `id`=".$get_id;
			if($unit = query_assoc($sql))
				return $unit[$cmp['col']];
		}

	return 0;
}








