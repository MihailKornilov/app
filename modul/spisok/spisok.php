<?php


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
	$page_id = $pe['page_id'];

	$dialog = _dialogQuery(14);
	$dv = $dialog['v_ass'];

	define('SPISOK_LIMIT', $dv[$pe['num_2']]);//�����

	//������, ����� ������� �������� ������ ������
	$dialog_id = $pe['num_3'];
	$spDialog = _dialogQuery($dialog_id);

	$CMP = $spDialog['component']; //�������� ������
	$spTable = $spDialog['base_table'];

	$cond = "`id`";
	if(isset($spDialog['field']['deleted']))
		$cond = "!`deleted`";
	if(isset($spDialog['field']['app_id']))
		$cond .= " AND `app_id` IN (0,".APP_ID.")";
	if(isset($spDialog['field']['dialog_id']))
		$cond .= " AND `dialog_id`=".$dialog_id;

	//��������� ������ ���������� �����
	$sql = "SELECT COUNT(*)
			FROM `".$spTable."`
			WHERE ".$cond."
			  "._spisokFilterSearch($pe, $spDialog);
	$all = query_value($sql);

	//��������� ������ ������
	$sql = "SELECT *
			FROM `".$spTable."`
			WHERE ".$cond."
			  "._spisokFilterSearch($pe, $spDialog)."
			ORDER BY `dtime_add` DESC
			LIMIT ".(SPISOK_LIMIT * $next).",".SPISOK_LIMIT;
	$spisok = query_arr($sql);



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
								$html .= '<td class="w15 grey r">'.$sp['num'];
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
								if($el['type_id'] == 1) {//�������
									$cls[] = 'center';
									$v = $v ? '<div class="icon icon-ok curD"></div>' : '';
								}
								if(@$ex[3]) {//������
									//�� ��������� ������� ��������
									$link = '&p='.$page_id;

									//���� ������� �������� ���������, �� ������ �������� �� ��������
									if($spTable == '_page')
										$link = '&p='.$sp['id'];

									//���� ������ �������������, �� ������� �� �������� ���������� ������������ �� ��� ����� todo ��������
									if($spTable == '_vkuser')
										$link = '&viewer_id='.$sp['viewer_id'];

									//���� ������� �������� �������� ����� �������� �������� ������
									if($spDialog['action_id'] == 2)
										$link = '&p='.$spDialog['action_page_id'].'&id='.$sp['id'];

									$v = '<a href="'.URL.$link.'"'._dn(!$pe['num_7'], 'class="fs12"').'>'.$v.'</a>';
								}
								$html .= '<td class="'.implode(' ', $cls).'">'.$v;
						}

	//						if(strlen($val) && $el['col_name'] == $CMP[$comp_id]['col_name'])
	//							$v = preg_replace(_regFilter($val), '<em class="fndd">\\1</em>', $v, 1);
					}
				}

				if(SPISOK_LIMIT * ($next + 1) < $all) {
					$count_next = $all - SPISOK_LIMIT * ($next + 1);
					if($count_next > SPISOK_LIMIT)
						$count_next = SPISOK_LIMIT;
					$html .=
						'<tr class="over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
							'<td colspan="20">'.
								'<tt class="db center curP fs14 blue pad8">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>';
				}


				$html .= !$next ? '</table>' : '';
				break;
			case 182://������
				//��������� ��������� �������
				$sql = "SELECT *
						FROM `_page_element`
						WHERE `parent_id`=".$pe['id']."
						ORDER BY `sort`";
				if(!$tmp = query_arr($sql)) {
					$html = '<div class="_empty"><span class="fs15 red">������ ������� ������ �� ��������.</span></div>';
					break;
				}

				$html = '';
				foreach($spisok as $sp) {
					$html .= '<div>';
					foreach($tmp as $r) {
						$txt = '';
						switch($r['num_4']) {
							case -1://���������� �����
								$txt = $sp['num'];
								break;
							case -2://���� ��������
								$txt = FullData($sp['dtime_add'], 0, 1);
								break;
							case -4://������������ �����
								$txt = _br($r['txt_2']);
								break;
							default:
								if($r['num_4'] <= 0)
									continue;
								switch($r['txt_2']) {
									case 1://��� �������
										$txt = $CMP[$r['num_4']]['label_name'];
										break;
									case 2://�������� �������
										$txt = $sp[$CMP[$r['num_4']]['col_name']];
										break;
									default: continue;
								}
						}
						$html .=
						'<div class="'.$r['type'].' '.$r['pos'].' '.$r['color'].' '.$r['font'].' '.$r['size'].'"'._pageElemStyle($r).'>'.
							$txt.
						'</div>';
					}
					$html .= '</div>';
				}

				if(SPISOK_LIMIT * ($next + 1) < $all) {
					$count_next = $all - SPISOK_LIMIT * ($next + 1);
					if($count_next > SPISOK_LIMIT)
						$count_next = SPISOK_LIMIT;
					$html .=
						'<div class="over1" onclick="_spisokNext($(this),'.$pe['id'].','.($next + 1).')">'.
							'<tt class="db center curP fs14 blue pad10">�������� ��� '.$count_next.' �����'._end($count_next, '�', '�', '��').'</tt>'.
						'</div>';
				}
				break;
			default:
				$html = '����������� ������� ��� ������: '.$pe['num_1'];
		}

	return $html;
}
function _spisokFilterSearch($pe, $spDialog) {//��������� �������� �������-������ ��� ������
	//���� ����� �� ������������ �� �� ����� ��������, �� �����
	if(!$colIds = _ids($pe['txt_3'], 1))
		return '';

	//��������� �������� �������� ������, ������������� �� ��������, ��� ��������� ������ �������������� �� ���� ������
	$sql = "SELECT *
			FROM `_page_element`
			WHERE `page_id`=".$pe['page_id']."
			  AND `dialog_id`=7
			  AND `num_3`=".$pe['id'];
	if(!$search = query_assoc($sql))
		return '';

	$arr = array();
	foreach($colIds as $cmp_id) {
		if(empty($spDialog['component'][$cmp_id]))
			continue;
		$arr[] = "`".$spDialog['component'][$cmp_id]['col_name']."` LIKE '%".addslashes($search['v'])."%'";
	}

	if(empty($arr))
		return '';

	return " AND (".implode($arr, ' OR ').")";
}


