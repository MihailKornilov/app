<?php
function _pageCache() {//��������� ������� ������� �� ����
	if($arr = _cache())
		return $arr;

	$sql = "SELECT
				*,
				0 `block_count`,
				0 `elem_count`,
				1 `del_access`,
				0 `access`
			FROM `_page`
			WHERE `app_id` IN (0,".APP_ID.")
			  AND `sa` IN (0,".SA.")
			ORDER BY `sort`";
	if(!$page = query_arr($sql))
		return array();

	//��������� ���������� ������ �� ������ ��������
	$sql = "SELECT
				`obj_id`,
				COUNT(*) `c`
			FROM `_block`
			WHERE `obj_name`='page'
			  AND `obj_id` IN ("._idsGet($page).")
			GROUP BY `obj_id`";
	$block = query_ass($sql);

	foreach($page as $id => $r) {
		$block_count = _num(@$block[$id]);
		$page[$id]['del_access'] = $block_count || $r['common_id'] ? 0 : 1;
	}

	$sql = "SELECT *
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".USER_ID;
	foreach(query_arr($sql) as $r)
		if(!empty($page[$r['page_id']]))
			$page[$r['page_id']]['access'] = 1;

	return _cache($page);
}
function _page($i='all', $i1=0) {//��������� ������ ��������
	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//�������� ����������
	if($i == 'app') {
		$send = array();
		foreach($page as $id => $r) {
			if(!$r['app_id'])
				continue;
			if($r['sa'])
				continue;
			$send[$id] = $r;
		}
		return $send;
	}

	//id ������� ��������
	if($i == 'cur') {
		if($page_id = _num(@$_GET['p'])) {
			if(!isset($page[$page_id]))
				return 0;
			if($page[$page_id]['common_id'])
				return $page[$page_id]['common_id'];
			return $page_id;
		}
		$i = 'def';
	}

	//id �������� �� ���������
	if($i == 'def') {
		//������ ����������, ���� ������������ �� ����� � ����������
		if(!APP_ID)
			return 98;

		//������� ����� �������� ����������
		foreach($page as $p)
			if(!$p['sa'] && $p['def'] && $p['access'])
				return $p['id'];

		//����� ������ ��������� ��������
		foreach($page as $p)
			if(!$p['sa'] && $p['access'])
				return $p['id'];

		//����� �������� SA
		if(SA)
			foreach($page as $p)
				if($p['sa'] && $p['def'])
					return $p['id'];

		//����� �� ������ �������
		return 12;
	}

	//�������� �� �������� ������������ ������������ �������
	if($i == 'is_cur_parent') {
		if(!$page_id = _num($i1))
			return false;
		$cur = _page('cur');

		//����������� �������� ��������� � �������
		if($page_id == $cur)
			return true;

		//������� �������� ���� �������� �������
		if(!$cur_parent = _num($page[$cur]['parent_id']))
			return false;

		//����������� �������� �������� ��������� �������
		if($page_id == $cur_parent)
			return true;

		//����������� �������� �������� ���-��������� �������
		if($page_id == $page[$cur_parent]['parent_id'])
			return true;

		return false;
	}

	//������ ������� ��� select
	if($i == 'for_select') {
		$child = array();
		foreach($page as $id => $r) {
			if(!$r['parent_id'])
				continue;

			if(empty($child[$r['parent_id']]))
				$child[$r['parent_id']] = array();

			$child[$r['parent_id']][] = $r;
			unset($page[$id]);
		}
		$send = _pageChildArr($page, $child);
		if(SA) {
			$send[] = array(
				'title' => utf8('�������� SA'),
				'info' => 1
			);
			foreach(_pageSaForSelect($page, $child) as $r)
				$send[] = $r;
		}

		if($i1 == 'js')
			return json_encode($send);

		return $send;
	}

	//��������� ��������, ������� ��������� �������� ������
	//  $i1 - id �������, ������� ������ ������ ����� ������
	if($i == 'spisok_id') {
		if(!$dialog_id = _num($i1))
			return 0;
		foreach($page as $id => $r) {
			if($r['spisok_id'] == $dialog_id)
				return $id;
		}
		return 0;
	}

	//������ �������� ������� ������������ ������������
	if($i == 'child') {
		if(!$parent_id = _num($i1))
			return array();
		$send = array();
		foreach($page as $id => $r) {
			if($r['parent_id'] == $parent_id)
				$send[$id] = $r;
		}
		return $send;
	}

	//������ ���������� ��������
	if($page_id = _num($i)) {
		if(!isset($page[$page_id]))
			return false;
		return $page[$page_id];
	}

	//�������� ������� ��������
	if($page_id = _page('cur')) {
		if(!isset($page[$page_id]))
			return false;
		if(!isset($page[$page_id][$i]))
			return false;
		return $page[$page_id][$i];
	}

	return false;
}
function _pageChildArr($arr, $child, $level=0) {//������������ �������� ������� ��� select
	$send = array();
	foreach($arr as $r) {
		if($r['sa'])
			continue;
		if(!$r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))),
			'content' => '<div class="fs'.(14-$level).' '.($level ? 'ml'.($level*20) : 'b').'">'.utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))).'</div>'
		);
		if(!empty($child[$r['id']]))
			foreach(_pageChildArr($child[$r['id']], $child, $level+1) as $sub)
				$send[] = $sub;
	}

	return $send;
}
function _pageSaForSelect($arr, $child) {//�������� SA ��� select
	$send = array();
	foreach($arr as $r) {
		if(!$r['sa'] && $r['app_id'])
			continue;
		$send[] = array(
			'id' => _num($r['id']),
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))),
			'content' => '<div class="'.($r['sa'] ? 'color-ref' : 'color-pay').'">'.utf8(addslashes(htmlspecialchars_decode(trim($r['name'])))).'</div>'
		);
		if(!empty($child[$r['id']]))
			foreach(_pageSaForSelect($child[$r['id']], $child) as $sub)
				$send[] = $sub;
	}

	return $send;

}

function _pasDefine() {//��������� ����� ��������� ���������� ��������� PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//�������� ����������
		if($page = _page($page_id))//������ �������� ��������
			if(!($page['sa'] && !SA))
				if(!(!$page['app_id'] && !SA))
					$pas = _bool(@$_COOKIE['page_setup']);

	define('PAS', APP_ID && $pas);
}
/*
function _pageSetupAppPage() {//���������� ���������� ����������
	$arr = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		$arr[$id] = $r;
	}

	if(empty($arr))
		return
		'<div class="_empty">'.
			'��� �� ������� �� ����� ��������.'.
			'<div class="mt10 fs15 black">�������� ������!</div>'.
		'</div>';

	$sort = array();
	foreach($arr as $id => $r)
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		}

	return
	'<style>'.
//		'ol{max-width:700px;padding-left:40px}'.
	'</style>'.
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>';
}
function _pageSetupAppPageSpisok($arr, $sort) {//������ ������� ����������
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1'.(!$r['parent_id'] ? ' pb10' : '').'" id="item_'.$r['id'].'">'.
			'<div>'.
				'<table class="_stab w100p bg-fff">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok fr curD'._tooltip('�������� �� ���������', -76).'</div>' : '').
						'<td class="w35 wsnw">'.
							'<div class="icon icon-move pl"></div>'.
							'<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].'" class="icon icon-edit pl dialog-open'._tooltip('�������� ��������', -58).'</div>'.
		($r['del_access'] ? '<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',del:1" class="icon icon-del-red dialog-open'._tooltip('�������� ������, �������', -79).'</div>'
						  : '<div class="icon icon-empty"></div>'
		).
				'</table>'.
			'</div>';
		if(!empty($sort[$r['id']]))
			$send .= '<ol>'._pageSetupAppPageSpisok($sort[$r['id']], $sort).'</ol>';
	}

	return $send;
}
*/
function _pasMenu() {//������ ���� ���������� ���������
	if(!PAS)
		return '';

	return
	'<div id="pas">'.
		'<div class="w1000 mara pad5">'.
			'<div class="dib fs16 b">'._page('name').	'</div>'.
		'</div>'.
		'<div class="w1000 mara pad5">'.
			_blockLevelChange('page', _page('cur')).
		'</div>'.
	'</div>';
}

function _pageUserAccess($el, $unit) {//��������� ������� � ��������� ��� ������������ (����������� ����� [12])
	if(empty($unit['id']))
		return _emptyMin('����������� id ������������.');

	//��������� ��������
	$sql = "SELECT `page_id`
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$unit['id'];
	$ids = _idsAss(query_ids($sql));

	$arr = _page('app');
	$sort = array();
	foreach($arr as $id => $r) {
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$r['access'] = _num(@$ids[$id]);
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		} else
			$arr[$id]['access'] = _num(@$ids[$id]);
	}


	return
	'<input type="hidden" id="access-user-id" value="'.$unit['id'].'" />'.
	'<dl>'._pageUserAccessSpisok($arr, $sort).'</dl>';
}
function _pageUserAccessSpisok($arr, $sort) {//������ ������� ��� ��������� �������
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<dd class="'._dn($r['parent_id'], ' pb10').'">'.
				'<table class="bs3">'.
					'<tr><td>'._check(array(
									'attr_id' => 'pageAccess_'.$r['id'],
									'title' => $r['name'],
									'class' => !$r['parent_id'] ? 'b fs14' : '',
									'value' => $r['access']
								)).
				'</table>';
		if(!empty($sort[$r['id']]))
			$send .= '<dl class="ml40'._dn($r['access']).'">'._pageUserAccessSpisok($sort[$r['id']], $sort).'</dl>';
	}

	return $send;
}
function _pageUserShow($el, $unit) {//����������� �������, ��������� ������������ (����������� ����� [12])
	if(empty($unit['id']))
		return _emptyMin('����������� id ������������.');

	//������ � ���������
	$sql = "SELECT `access`
			FROM `_user_app`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$unit['id']."
			LIMIT 1";
	if(!query_value($sql))
		return '<div class="_empty min mar10 red">���� � ���������� ��������.</div>';

	//��������� ��������
	$sql = "SELECT `page_id`
			FROM `_user_page_access`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$unit['id'];
	$ids = _idsAss(query_ids($sql));

	$page = _page('app');
	foreach($page as $id => $r)
		$page[$id]['access'] = _num(@$ids[$id]);

	$child = array();
	foreach($page as $id => $r) {
		if(empty($child[$r['parent_id']]))
			$child[$r['parent_id']] = array();
		$child[$r['parent_id']][] = $r;
	}

	if(!$send = _pageUserShowSpisok($child))
		return _emptyMin('��� ��������� �������.');

	return $send;
}
function _pageUserShowSpisok($arr, $parent_id=0) {//������ ������� ��� ��������� �������
	if(empty($arr[$parent_id]))
		return '';

	$send = '';
	foreach($arr[$parent_id] as $id => $r) {
		if(!$r['access'])
			continue;
		$send .= '<dd class="'._dn($r['parent_id'], ' pb10').'">'.
				'<table class="bs3">'.
					'<tr><td class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].
				'</table>';
		if(!empty($arr[$r['id']]))
			$send .= '<dl class="ml30">'._pageUserShowSpisok($arr, $r['id']).'</dl>';
	}

	return $send;
}
function _pageUserAccessAll() {//��������� ����� � ���������� ���� ������������� (����������� ����� [12])
	$sql = "SELECT
				`u`.*,
				`ua`.`access`
			FROM
				`_user` `u`,
				`_user_app` `ua`
			WHERE `ua`.`app_id`=".APP_ID."
			  AND `u`.`id`=`ua`.`user_id`
			ORDER BY `ua`.`dtime_add`";
	if(!$user = query_arr($sql))
		return _emptyMin('����������� ���.');

	$send = '<table class="">';
	foreach($user as $r)
		$send .=
			'<tr class="over1">'.
				'<td class="w200 pad5 pl20 curD">'.
					$r['f'].' '.$r['i'].
				'<td class="w35">'.
					_check(array(
						'attr_id' => 'allAcc_'.$r['id'],
						'value' => $r['access']
					));

	$send .= '</table>';

	return $send;
}



function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	if(!SA && !$page['access'])
		return _contentMsg();

	if(APP_ID && !APP_ACCESS)
		$page_id = 105;

	if($page_id == 105 && APP_ID && APP_ACCESS)
		$page_id = _page('def');

	return
//	'USER_ID='.USER_ID.' APP_ID='.APP_ID.' APP_ACCESS='.APP_ACCESS.
//	_block('page', $page_id, 'block_js').
//	_pr(_block('page', $page_id, 'elem_arr')).
	_blockHtml('page', $page_id, 1000, 0, _pageSpisokUnit($page_id)).
//	_page_div().
	'<script>'.
		'var BLK='._block('page', $page_id, 'block_js').','.
			'ELM='._block('page', $page_id, 'elem_js').','.
			'PAGE_LIST='._page('for_select', 'js').','.
			'ELEM_COLOR='._colorJS().','.
			'FILTER='._json(_spisokFilter('page_js')).';'.
		'_pageAct('.PAS.');'.
	'</script>';
}
function _pageSpisokUnit($page_id, $obj_name='page') {//������ ������� ������, ������� ����������� �� ��������. ��������� �� $_GET['id']
	if($obj_name != 'page')
		return array();

	$page = _page($page_id);
	if(!$dialog_id = $page['spisok_id'])
		return array();

	$pageDef = '<br><br><a href="'.URL.'&p='._page('def').'">������� �� �������� �� ���������</a>';
	if(!$id = _num(@$_GET['id']))
		return _contentMsg('������������ ������������� ������� ������.'.$pageDef);

	if(!$dialog = _dialogQuery($dialog_id))
		return _contentMsg('����������� ������, ������� ������ ������.'.$pageDef);

	if(!$unit = _spisokUnitQuery($dialog, $id))
		return _contentMsg('������� ������ id'.$id.' �� ����������.'.$pageDef);

	if(isset($dialog['field1']['deleted']) && $unit['deleted'])
		return _contentMsg('������� ������ id'.$id.' ���� �������.'.$pageDef);

	return $unit;
}
function _elemDiv($el, $unit=array()) {//������������ div ��������
	if(!$el)
		return '';

	$txt = _elemUnit($el, $unit);

	//���� ������� ������ �������, attr_id �� ��������
	$attr_id = !BLOCK_EDIT && $el['block']['obj_name'] == 'spisok' ? '' : ' id="el_'.$el['id'].'"';

	$cls = array();
	$cls[] = _elemFormatColor($txt, $el, $el['color']);
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = $cls ? ' class="'.implode(' ', $cls).'"' : '';

	$txt = _elemFormat($txt, $el);

	return '<div'.$attr_id.$cls._elemStyle($el).'>'.$txt.'</div>';
}
function _elemFormat($txt, $el) {//�������������� �������������� ��� �����
	if($el['format_hide'] && empty($txt))
		return '';
	if(!preg_match(REGEXP_CENA_MINUS, $txt))
		return $txt;
	if($el['format_hide'] && !_cena($txt, 1))
		return '';

	if($el['format_space'])
		$txt = _sumSpace($txt, $el['format_fract_0_show'], $el['format_fract_char']);
	else {
		if(!$el['format_fract_0_show'])
			$txt = round($txt, 2);
		$txt = str_replace('.', $el['format_fract_char'], $txt);
	}

	return $txt;
}
function _elemFormatColor($txt, $el, $color) {//������� ����� ��� �������������� �������������� ��� �����
	if(!preg_match(REGEXP_CENA_MINUS, $txt))
		return $color;

	switch($el['format_color_cond']) {
		case 1457:
			if($txt == 0)
				return $el['format_color'];
			break;
		case 1458:
			if($txt < 0)
				return $el['format_color'];
			break;
		case 1459:
			if($txt > 0)
				return $el['format_color'];
			break;
	}

	return $color;
}
function _elemStyle($el) {//����� css ��� ��������
	$send = array();

	//�������
	$ex = explode(' ', $el['mar']);
	foreach($ex as $px)
		if($px) {
			$send[] = 'margin:'.
				$ex[0].($ex[0] ? 'px' : '').' '.
				$ex[1].($ex[1] ? 'px' : '').' '.
				$ex[2].($ex[2] ? 'px' : '').' '.
				$ex[3].($ex[3] ? 'px' : '');
			break;
		}

	//����� �������� ��������� ������ ���������,
	//�� ��������, ������� ����� �������������, ��������, ��������� ����������
	if(ELEM_WIDTH_CHANGE && !_dialogParam($el['dialog_id'], 'element_width'))
		$send[] = 'visibility:hidden';

	if(!$send)
		return '';

	return ' style="'.implode(';', $send).'"';
}
function _elemUnit($el, $unit=array()) {//������������ �������� ��������
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//�������� �� ������
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']] : '';
	$is_edit = @BLOCK_EDIT || ELEM_WIDTH_CHANGE || !empty($unit['choose']);
	$attr_id = 'cmp_'.$el['id'].($is_edit ? '_edit' : '');
	$disabled = $is_edit ? ' disabled' : '';

	switch($el['width']) {
		case 0: $width = ' style="width:100%"'; break;
//		case -1: $width = ' style="width:100%"'; break;
		default: $width = ' style="width:'.$el['width'].'px"';
	}

	switch($el['dialog_id']) {
		//---=== ���������� ��� �������� ������ ===--- (������������ $unit)
		//�������
		case 1:
			/*
				txt_1 - ����� ��� �������
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
				'value' => _num($v)
			));

		//textarea (������������� ��������� ����)
		case 5:
			/*
				txt_1 - ����� ��� placeholder
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			return
			'<textarea id="'.$attr_id.'"'.$width.$placeholder.$disabled.'>'.
				$v.
			'</textarea>';

		//Select - ����� ��������
		case 6:
			/*
                txt_1 - �����, ����� �������� �� �������
				������� _page('for_select', 'js')
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//input:text (������������ ��������� ����)
		case 8:
			/*
				txt_1 - ����� ��� placeholder
				txt_2 - ����� �� ���������
				num_1 - ������:
					38 - ����� �����
					39 - ����� � �����
				num_2 - ���������� ������ ����� �������
				num_3 - ��������� ������������� ��������
				num_4 - ��������� ������� 0
			*/
			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$v = empty($v) ? $el['txt_2'] : $v;
			return '<input type="text" id="'.$attr_id.'"'.$width.$placeholder.$disabled.' value="'.$v.'" />';

		//����� �������� �� ������� ��� ��������
		case 13:
			/*
				������������ ��������� ����� ������������ �������� ��� ������, �� �������� ����� ���������� �������

				txt_1 - ����� ��� placeholder
			*/

			$obj_name = '';
			$obj_id = 0;

			if(!empty($unit['source']['block_id'])) {
				$block_id = _num($unit['source']['block_id']);
				if($BL = _blockQuery($block_id)) {
					if($BL['obj_name'] == 'page' || $BL['obj_name'] == 'dialog') {
						$obj_name = $BL['obj_name'];
						$obj_id = $BL['obj_id'];
					}
				}
			}

			$placeholder = $el['txt_1'] ? ' placeholder="'.$el['txt_1'].'"' : '';
			$v = _num($v);

			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			'<div class="_selem dib prel bg-fff over1" id="'.$attr_id.'_selem"'.$width.'>'.
				'<input type="hidden" class="obj_name" value="'.$obj_name.'" />'.
				'<input type="hidden" class="obj_id" value="'.$obj_id.'" />'.
				'<div class="icon icon-star pabs"></div>'.
				'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
				'<input type="text" readonly class="inp curP w100p"'.$placeholder.$disabled.' value="'._elemTitle($v).'" />'.
			'</div>';

		//Radio
		case 16:
			/*
				txt_1 - ����� �������� ��������
				num_1 - �������������� ���������
				�������� �� _element ����� dialog_id:19
			*/
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `block_id`=-".$el['id']."
					ORDER BY `sort`";
			$spisok = query_ass($sql);

			return _radio(array(
				'attr_id' => $attr_id,
				'light' => 1,
				'block' => !$el['num_1'],
				'interval' => 5,
				'value' => _num($v) ? _num($v) : $el['def'],
				'title0' => $el['txt_1'],
				'spisok' => $spisok,
				'disabled' => $disabled
			));

		//Select - ������������ ��������
		case 17:
			/*
                txt_1 - ����� �������� ��������
				�������� �� _element ����� dialog_id:19
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $el['def']
				   ));

		//Dropdown
		case 18:
			/*
                txt_1 - ����� �������� ��������
				�������� �� _element ����� dialog_id:19
			*/
			return _dropdown(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $el['def']
				   ));

		//��������������� �������: ���������� ��� ��������� �����������: radio, select, dropdown
		case 19:
			/*
				��� �������� ����� JS.
				������ �������� � _element. � block_id ������� ������������� id �������� ��������.

				num_1 - ������������ �������� ��������

				��������:
					id
					txt_1 - title
					txt_2 - content
					def
					sort
			*/

			return '<div class="_empty min">���������� ����������</div>';

		//Select - ����� ������ ����������
		case 24:
			/*
                txt_1 - �����, ����� ������ �� ������
				num_1 - ���������� �������:
						0   - ��� ������ ����������. ������� _dialogSpisokOn()
						960 - ����������� �� ������� �������
							  ������ ����������� ��������� 14(������), 23(�������), ������� ��������
							  ���������������� ���������� �������� id ��������� (� �� ��������)
							  ������� _dialogSpisokOnPage()
						961 - ����������� � ������� �������
							  ���������������� ���������� �������� id ��������� (� �� ��������)
							  ������� _dialogSpisokOnConnect()
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Select - ����� ������� �� ������� ������
		case 29:
			/*
				��� ������ ������ ������ � ������
				������ ������ ��������� ������ � �����

                num_1 - id �������, ����� ������� �������� ������ ����������� ������
                txt_1 - �����, ����� ������� �� �������
                txt_2 - ��� id ��������, ������������ ���������� Select
				num_2 - ����������� ���������� ����� ��������
				num_3 - ����� �������� �������
				num_4 - ����������� �����
				num_5 - ��������� ������
			*/

			if(!$UNIT_ISSET)
				$v = _spisokCmpConnectIdGet($el);

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//����� �������� ��� ���������� Select
		case 31:
			/*
				num_1 - id ��������, ����������� Select, ��� �������� ���������� ��������
				txt_1 - ��� ������� ��������
				num_2 - ������������ �� ������ ��������
				txt_2 - ��� ������� ��������
			*/
			$ex = explode(',', $v);
			$v0 = _num(@$ex[0]) ? _elemTitle($ex[0]) : '';
			$v1 = _num(@$ex[1]) ? _elemTitle($ex[1]) : '';
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<input type="text" id="'.$attr_id.'_sv" class="sv w125 curP over1 color-pay" placeholder="'.$el['txt_1'].'" val="0" readonly'.$disabled.' value="'.$v0.'" />'.
			($el['num_2'] ?
				'<input type="text" class="sv w150 curP over1 color-pay ml5" placeholder="'.$el['txt_2'].'" val="1" readonly'.$disabled.' value="'.$v1.'" />'
			: '');

		//Count - ����������
		case 35:
			/*
                num_1 - ����������� ��������
                num_2 - ������������ ��������
                num_3 - ���
                num_4 - ����� ���� ������������� (�������)
			*/
			return _count(array(
						'attr_id' => $attr_id,
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - ����� ������� �������
		case 37:
			return _select(array(
						'attr_id' => $attr_id,
						'width' => $el['width']
				   ));

		//SA: Select - ����� ����������� ����
		case 38:
			/*
                txt_1 - ������� ��������
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//SA: Select - �������� �� ������������� �������
		case 41:
			/*

			*/

			if(!$bs_id = _num(@$US['block_id']))
				return '<div class="red">����������� ID ��������� �����.</div>';

			$BL = _blockQuery($bs_id);
			if($BL['obj_name'] != 'dialog')
				return '<div class="red">�������� ���� �� �������� ������ �� �������.</div>';

			if(!$EL = $BL['elem'])
				return '<div class="red">����������� �������� �������.</div>';

			if($EL['dialog_id'] != 17)
				return '<div class="red">�������� ������� �� �������� ���������� �����.</div>';

			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $EL['txt_1'],
						'width' => $el['width'],
						'value' => _num($v) ? _num($v) : $EL['def']
				   ));

		//���������
		case 51:
			/*
				num_1 - ��������� ����� ��������� ����
				num_2 - ���������� �����
			*/
			return _calendar(array(
				'attr_id' => $attr_id,
				'value' => $v
			));

		//������ ������ ��� ������ ������
		case 59:
			/*
				txt_1 - ����� ������
                num_1 - id �������, ����� ������� �������� ������ ����������� ������
				num_4 - id �������, ������� ����������� ��� ������� �� ������
			*/

			$v = _num($v);
			return
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_button(array(
				'attr_id' => $attr_id.$el['afics'],
				'name' => $el['txt_1'],
				'color' => 'grey',
				'width' => $el['width'],
				'small' => 1,
				'class' => _dn(!$v)
			)).
			'<div class="'._dn($v).'">'.
				'<div class="icon icon-del-red pl fr'._tooltip('�������� �����', -53).'</div>'.
				'<div class="un-html">'._spisok59unit($el['id'], $v).'</div>'.
			'</div>';

		//����� ����� ������
		case 66:
			/*
			*/
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color" style="background-color:#000"></div>';

		//����� ����� ����
		case 70:
			$v = empty($v) ? '#fff' : $v;
			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<div class="_color-bg" style="background-color:'.$v.'"></div>';




		//---=== �������� ����������� ===---
		//button
		case 2:
			/*
				txt_1 - ����� ������
				num_1 - ����
				num_2 - ��������� ������
				num_3 - ��������� �������� ������, ������� ��������� ��������
				num_4 - dialog_id, ������� �������� �� ��� ������
			*/
			$color = array(
				0 => '',      //����� - �� ���������
				1 => '',      //�����
				2 => 'green', //������
				3 => 'red',   //�������
				4 => 'grey',  //�����
				5 => 'cancel',//����������
				6 => 'pink',  //�������
				7 => 'orange' //���������
			);

			//������� ��������� ����� ��� �������� ��� �������������� ��������, ���� ������ ����������� � �������
			$block = _num(@$US['block_id']) ? ',block_id:'.$US['block_id'] : '';
			//���� ������ ����������� � ���������� ����, �� ����������� id ����� ���� ��� ��������
			$dialog_source = !empty($el['block']) && $el['block']['obj_name'] == 'dialog' ? ',dialog_source:'.$el['block']['obj_id'] : '';

			//������ ��������� �������� ������, ������� ��������� ��������
			if($el['num_3'] && $UNIT_ISSET)
				$block = ',unit_id:'.$unit['id'];

			//���� ����� ������, ����� ����������� ����� ������ ��� ��
			if(!$el['num_4'])
				$block = ',block_id:'.$el['block_id'];

			return _button(array(
						'attr_id' => $attr_id,
						'name' => _br($el['txt_1']),
						'color' => $color[$el['num_1']],
						'width' => $el['width'],
						'small' => $el['num_2'],
						'class' => 'dialog-open',
						'val' => 'dialog_id:'.$el['num_4'].$block.$dialog_source
					));

		//���� �������
		case 3:
			/*
				num_1 - ������ (��������-��������). � ���� ����� �������� ��������
				num_2 - ������� ���:
						10 - �������� ��� - �������������� ����
						11 - � �������������� (�����.)
						12 - ����� ��������� ������ (�����.)
						13 - ������� ������������ ����
			*/
			return _pageElemMenu($el);

		//���������
		case 4:
			/*
                txt_1 - ����� ���������
			*/
			return '<div class="hd2">'.$el['txt_1'].'</div>';

		//������ �� ��������
		case 9:
			/*
                txt_1 - ����� ������
				num_1 - id ��������
			*/
			if(!$txt = $el['txt_1']) {
				$page = _page($el['num_1']);
				$txt = $page['name'];
			}
			return '<a class="inhr" href="'.URL.'&p='.$el['num_1'].'">'.
						$txt.
				   '</a>';

		//������������ �����
		case 10:
			/*
                txt_1 - �����
			*/
			return _br($el['txt_1']);

		//����� �������� ��� ������� (��������� ���� ��� ������)
		case 11:
			/*
				txt_2 - id ��������, ���������� �� �������, ������� ������ ������ ������ (����� dialog_id=26)
						�������� �������� ��������� ����� ������� 256,1312,560
			*/

			if(!$UNIT_ISSET)
				return _elemTitle($el['id']);

			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN ("._ids($el['txt_2']).")";
			if(!$elemArr = query_arr($sql))
				return '������� �����������';

			$send = '';

			$ids = _ids($el['txt_2'], 1);
			foreach($ids as $n => $elem_id) {
				$elem = $elemArr[$elem_id];
				switch($elem['dialog_id']) {
					//������������� ����
					case 5:
					//������������ ����
					case 8:
						if(empty($unit))
							return '';
						if(empty($unit[$elem['col']]))
							return '';
						$txt = $unit[$elem['col']];
						if($n) {
							$el0 = $elemArr[$ids[0]];
							if($el0['dialog_id'] == 29)
								if($el0['num_5']) {//����� �������� �� �������


									if($parent_id = $unit['parent_id'])
										while($parent_id) {
											$sql = "SELECT *
													FROM `_spisok`
													WHERE `id`=".$parent_id;
											if(!$u = query_assoc($sql))
												break;
											$txt = $u[$elem['col']].' � '.$txt;
											$parent_id = $u['parent_id'];
										}


								}
						}
//						$txt = _spisokColSearchBg($txt, $ELEM, $elemUse['id']);
						$txt = _spisokUnitUrl($el, $unit, $txt);
						$send .= _br($txt);
						break;
					//������������ �����
					case 10: $send .= $elem['txt_1']; break;
					//������
					case 29:
					case 59:
						if(!$sp = $unit[$elem['col']])
							break;
						if(!is_array($sp)) {
							$dialog = _dialogQuery($unit['dialog_id']);
							$sql = "SELECT *
									FROM `"._baseTable($dialog['table_1'])."`
									WHERE `id`=".$sp;
							$unit = query_assoc($sql);
							break;
						}
						$unit = $sp;
						break;
					//����� �������� ������� ������
					case 27:
					//���������� ���������� ������
					case 54:
					//����� ���������� ������
					case 55: $send .= $unit[$elem['col']]; break;
					//�����������
					case 60:
						if(!$col = $elem['col']) {
							$send .= '';
							break;
						}
						if(empty($unit)) {
							$send .= _imageNo($el['width']);
							break;
						}

	//					if(empty($unit[$elem['col']]))//id �������� �������� � �������
	//						$send .= '';
	//					if(!$img_id = _num($unit[$elem['col']]))//��������� id ��������, ���� ����� �, ���� ��� ������������
	//						$send .= $unit[$elem['col']];

						$sql = "SELECT *
								FROM `_image`
								WHERE `obj_name`='elem_".$elem['id']."'
								  AND `obj_id`=".$unit['id']."
								  AND !`deleted`
								  AND !`sort`
								LIMIT 1";
						if(!$r = query_assoc($sql)) {
							$send .= _imageNo($el['width']);
							break;
						}
						$send .= _imageHtml($r, $el['width'], $el['num_7']);
						break;
				}
			}
			return $send;

		//SA: ������� PHP
		case 12:
			/*
				����� ���������� ������ PHP-������� ����� ����������� JS-������� � ����� �� ������, ���� ����������.

                txt_1 - ��� �������
			*/

			if(!$el['txt_1'])
				return '<div class="_empty min">����������� ��� �������.</div>';
			if(!function_exists($el['txt_1']))
				return '<div class="_empty min">������� <u>'.$el['txt_1'].'</u> �� ����������.</div>';
			if($is_edit)
				return '<div class="_empty min">������� '.$el['txt_1'].'</div>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				$el['txt_1']($el, $unit);

		//���������� ������� ������ - ������
		case 14:
			/*
                num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
				num_2 - ����� (���������� �����, ��������� �� ���� ���)
				txt_1 - ��������� ������� �������

				��������� ������� ����� ��������������� �������: dialig_id=25
			*/
			if($is_edit)
				return '<div class="_empty">������ <b class="fs14">'._dialogParam($el['num_1'],'name').'</b></div>';

			return
				_spisokShow($el).
				(_spisokIsSort($el['block_id']) ?
					'<script>_spisokSort("'.$el['attr_el'].'")</script>'
				: '');

		//���������� ����� ������
		case 15:
			/*
                num_1 - id ��������, ����������� ������, ���������� ����� �������� ����� ��������
				txt_1 "1" txt_2 - �������� "1" ������
				txt_3 "2" txt_4 - �������� "2" ������
				txt_5 "5" txt_6 - �������� "5" �������
			*/
			return _spisokElemCount($el);

		//�������������� ����
		case 21:
			/*
                txt_1 - ����������
			*/
			return '<div class="_info">'._br($el['txt_1']).'</div>';

		//��������������� �������: ������ ��������, ����������� � ��������
		case 22:
			if(!$bs_id = _num(@$US['block_id']))
				return _emptyMin('����������� ID ��������� �����.');

			if(!$BL = _blockQuery($bs_id))
				return _emptyMin('��������� ����� id'.$bs_id.' �� ����������.');

			if($BL['obj_name'] != 'page' && $BL['obj_name'] != 'dialog')
				return _emptyMin('�������� ����� ���������<br>������ ����������� �� ��������� � ���������� �����.');

			$sql = "SELECT *
					FROM `_element_func`
					WHERE `block_id`=".$bs_id."
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				return _emptyMin('�������� �� ���������.');

			//�������� ��������
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'action_id').")";
			$act = query_ass($sql);

			//�������� �������
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'cond_id').")";
			$cond = query_ass($sql);

			//���������� ��������
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'value_specific').")";
			$vs = query_ass($sql);

			//�������� ��������
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `id` IN ("._idsGet($arr, 'effect_id').")";
			$effect = query_ass($sql);
			$effect[0] = '���';

			$spisok = '';
			foreach($arr as $r) {
				$c = count(_ids($r['target'], 1));
				$targetName = '����'._end($c, '', '�', '��');
				$targetColor = 'color-ref';
				if($r['dialog_id'] == 73) {
					$targetName = '����.';
					$targetColor = 'color-pay';
				}
				$spisok .=
					'<dd val="'.$r['id'].'">'.
					'<table class="bs5 bor1 bg-gr2 over2 mb5 curD">'.
						'<tr>'.
							'<td class="w25 top">'.
								'<div class="icon icon-move-y pl"></div>'.
							'<td class="w300">'.
								'<div class="fs15">'._dialogParam($r['dialog_id'], 'name').'</div>'.
								'<table class="bs3">'.
									'<tr><td class="fs12 grey top">��������:'.
										'<td class="fs12">'.
											'<b class="fs12">'.$act[$r['action_id']].'</b>, ���� '.
				   (!$r['value_specific'] ? '<b class="fs12">'.$cond[$r['cond_id']].'</b>' : '').
					($r['value_specific'] ? '�������: <b>'.$vs[$r['value_specific']].'</b>' : '').
					($r['action_reverse'] ? '<div class="fs11 color-555">(����������� �������� ��������)</div>' : '').
					 ($r['effect_id'] ? '<tr><td class="fs12 grey r">������:<td class="fs12 color-pay">'.$effect[$r['effect_id']] : '').
								'</table>'.
							'<td class="w70 b '.$targetColor.' top center pt3">'.
								$c.' '.$targetName.
							'<td class="w50 r top">'.
								'<div val="dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',dialog_source:'.$el['block']['obj_id'].'" class="icon icon-edit pl dialog-open'._tooltip('��������� ��������', -60).'</div>'.
								_iconDel(array(
									'class' => 'pl ml5 dialog-open',
									'val' => 'dialog_id:'.$r['dialog_id'].',unit_id:'.$r['id'].',del:1,dialog_source:'.$el['block']['obj_id']
								)).
					'</table>'.
					'</dd>';
			}

			return '<dl class="mar10">'.$spisok.'</dl>';

		//���������� ������� ������ - �������
		case 23:
			/*
                num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
				num_2 - ����� (���������� �����, ��������� �� ���� ���)
				txt_1 - ��������� ������� �������
				num_3 - ����� ������ �������
				num_4 - ������������ ������ ��� ��������� ����
				num_5 - ���������� ����� �������
				num_6 - ����������� ���������� ����� ������� (���� �����������, ����� ������ ���������� 200)
				num_7 - ������ ���������� (1,2,3)
				txt_2 - ids ��������� ����� �������. ���� �������� �������� � ������� _element

				��������� ������� ����� ��������������� �������: dialig_id=30
			*/
			if(PAS) {
				$dialog = _dialogQuery($el['num_1']);
				return '<div class="_empty">������-������� <b class="fs14">'.$dialog['name'].'</b></div>';
			}

			return _spisokShow($el);

		//��������������� �������: ��������� ������� ������� ������
		case 25:
			/*
				��� �������: spisok
				 id �������: block_id, � ������� ����������� ������
			*/
			if(!$UNIT_ISSET)
				return
				'<div class="bg-ffe pad10">'.
					'<div class="_empty min">'.
						'��������� ������� ����� �������� ����� ������� ������ � ����.'.
					'</div>'.
				'</div>';

			//����������� ������ �������
			$sql = "SELECT *
					FROM `_block`
					WHERE `id`=".$unit['block_id'];
			if(!$block = query_assoc($sql))
				return '�����, � ������� ��������� ������, �� ����������.';

			setcookie('block_level_spisok', 1, time() + 2592000, '/');
			$_COOKIE['block_level_spisok'] = 1;

			//������������� ������ � ������ ��������
			$ex = explode(' ', $unit['mar']);
			$width = floor(($block['width'] - $ex[1] - $ex[3]) / 10) * 10;
			$line_r = $width < 980 ? ' line-r' : '';

			return
				'<div class="bg-ffc pad10 line-b">'.
					_blockLevelChange('spisok', $unit['block_id'], $width).
				'</div>'.
				'<div class="block-content-spisok'.$line_r.'" style="width:'.$width.'px">'._blockHtml('spisok', $unit['block_id'], $width).'</div>';

		//��������������� �������: ���������� ������� ��� ������ ��������
		case 26:
			/*
				������������ � ��������: 7,11,36,40

				num_2 - ��� ��������:
							40: ����� ��������
							41: ��������, ������� ������ ������
							42: ��������, �� ������� ����� ����������� �����
							43: �����
				num_3 - ����� ���������� ��������
			*/
			if($el['block']['obj_name'] != 'dialog')
				return _emptyMin('������� ����� ������������� ������ � ����� �������');

			$dialog_id = _num(@$US['dialog_source']);

			//�������� ����
			if($bls_id = _num(@$US['block_id'], 1)) {
				//���� �������� ���������
				if($bls_id < 0) {
					if(!$EL = _elemQuery(abs($bls_id)))
						return _emptyMin('��������� �������� id'.$bls_id.' �� ����������.');
					$bls_id = $EL['block_id'];//���������� ��������� �����
				}

				//$history = $el['dialog_id']

				if(!$BLS = _blockQuery($bls_id))
					return _emptyMin('�������� ���� id'.$bls_id.' �����������.');

				if($el['num_2'] == 43 && $BLS['obj_name'] != 'dialog')
					return _emptyMin('����� ������ �������� ������ ��� ��������.');

				//id �������, � ������� ������������� �����
				switch($el['block']['obj_id']) {
					case 7://�����
						if(!$EL = $BLS['elem'])
							return _emptyMin('���������� ������� ����� ��������<br>����� ������� �������� ������ � ����.');
						if(!$EL['num_1'])
							return _emptyMin('���������� ������� ����� �������� ����� ������ ������,<br>�� �������� ����� ������������� �����.');
						if(!$sp = _elemQuery($EL['num_1']))
							return _emptyMin('����������� �������, ����������� ������.');
						$dialog_id = $sp['num_1'];
						break;
					case 11://������� ��������...
						if($BLS['obj_name'] == 'spisok') {//...� ���� ������� [14]
							$bl = _blockQuery($BLS['obj_id']);
							if(!$bl['elem'])
								return _emptyMin('���������� ������� ����� ��������<br>����� ������� �������� � ����.');
							if(!$dialog_id = $bl['elem']['num_1'])
								return _emptyMin('���������� ������� ����� �������� ����� ������ ������.');
							break;
						}
						if($BLS['obj_name'] == 'page') {
							if($BLS['elem'] && ($BLS['elem']['dialog_id'] == 14 || $BLS['elem']['dialog_id'] == 23)) {//������ [14,23]
								$dialog_id = $BLS['elem']['num_1'];
								break;
							}
							if(!$page = _page($BLS['obj_id']))
								return _emptyMin('������ �������� '.$BLS['obj_id'].' �� ��������.');
							if(!$dialog_id = $page['spisok_id'])
								return _emptyMin('�������� �� ��������� �������� ������� ������');
						}
						if($BLS['obj_name'] == 'dialog') {
							if($dialog_id = $US['dialog_source']) {
								//����������� ������� ���������� ��� ��������, ������� �������� �������� ��� ������
								//��������� ���������, ��� ������ id �������
								if($BLS['elem']['dialog_id'] == 31) {
									if(!$el31_id = _num($BLS['elem']['num_1']))
										return _emptyMin('����������� id ��������, ������������ select');
									if(!$el31 = _elemQuery($el31_id))
										return _emptyMin('����������� �������, ����������� select');
									if($el31['num_1']) {//$dialog_id - �������� ���������, ����������� ���������� ������-������ [29]
										if(!$ell = _elemQuery($dialog_id))
											return _emptyMin('...');
										$dialog_id = _num($ell['block']['obj_id']);
									}
								}
								break;
							}

							$dialog_id = $BLS['obj_id'];
							break;
						}
						break;
					case 31://����� �������� ��� ����������� ����
						if($BLS['obj_name'] != 'dialog')
							return _emptyMin('����� �������� ������ ��� ��������');
						$dialog_id = $BLS['obj_id'];
						break;
					case 36://�����-������� ������ ��� �������
					case 40://�����-������� ������ ��� ����������� ����
					default:
						if($el['num_2'] == 43) {
							$dialog_id = $BLS['obj_id'];
							break;
						}
						return _emptyMin('������������� ������ '.$el['block']['obj_id']);
				}
			}

			if(!$dialog_id)
				return _emptyMin('�� ������� ID �������, ������� ������ ������ ������.');

			if(!$dialog = _dialogQuery($dialog_id))
				return _emptyMin('������� �� ����������, ������� ������ ������ ������.');

			//����, ������� ����� ������������
			$choose_access = array();
			switch($el['num_2']) {
				case 40://����� ��������
					$choose_access = array('all'=>1);
					break;
				case 41: //��������, ������� ������ ������
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_is_insert`";
						$choose_access = query_ass($sql);
					break;
				case 42: //��������, �� ������� ����� ����������� �����
						$sql = "SELECT `id`,1
								FROM `_dialog`
								WHERE !`app_id`
								  AND `element_search_access`";
						$choose_access = query_ass($sql);
					break;
				case 43: //�����
					$choose_access = array('block'=>1);
					break;
			}

			//��������� ��� ��������� �����, ����� ������ ���� �� ������� (��� �������)
			$choose_deny = array();
/*
			$dialogCur = _dialogQuery($el['block']['obj_id']);
			if($dialogCur['base_tabl'] == '_element_func') {
				$id = $UNIT_ISSET ? _num($unit['id']) : 0;
				$sql = "SELECT *
						FROM `_element_func`
						WHERE `block_id`=".$bs_id."
						  AND `id`!=".$id;
				if($arr = query_arr($sql))
					foreach($arr as $r)
						foreach(_ids($r['target'], 1) as $t)
							$choose_deny[$t] = 1;
			}
*/

			$send = array(
				'choose' => 1,
				'choose_access' => $choose_access,
				'choose_sel' => _idsAss($v),       //ids ����� ��������� ��������� ��� ������
				'choose_deny' => $choose_deny      //ids ��������� ��� ������, ������� �������� ������ (���� ��� ���� ������� ������ ������� ���� �� ��������)
			);

			return
			'<div class="fs14 pad10 pl15 bg-gr2 line-b">���������� ���� <b class="fs14">'.$dialog['name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send).
			'<input type="hidden" class="dlg26" value="'.$dialog_id.'" />'.
			'<script>ELM'.$dialog_id.'='._block('dialog', $dialog_id, 'elem_js').';</script>';

		//��������������� �������: ��������� ���������� ���������� ������
		case 30:
			/*
				��� �������: spisok
				 id �������: block_id, � ������� ����������� ������
			*/
			if(!$UNIT_ISSET)
				return '<div class="_empty min">��������� ������� ����� �������� ����� ������� ������ � ����.</div>';

			//��� �������� ����� JS
			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';

		//�������� ������: ���������� �����
		case 32: return _spisokUnitNum($el, $unit);

		//�������� ������: ����
		case 33:
			/*
				num_1 - ������:
					29: 5 ������� 2017
					30: 5 ��� 2017
					31: 05/08/2017
				num_2 - �� ���������� ������� ���
				num_3 - ����� � ��������� ����:
					�����
					�������
					������
				num_4 - ���������� ����� � ������� 12:45
			*/

			return _spisokUnitData($el, $unit);

		//�������� ������: ������ ����������
		case 34:
			if(!$UNIT_ISSET)
				return 'edit';

			return _spisokUnitIconEdit($el, $unit['id']);

		//������ ������: ����������� ���������
		case 42:
			/*
				txt_1 - ����� ���������
				num_1 - ������� ��������
					741 - ������
					742 - �����
					743 - �����
					744 - ������
			*/
			return '<div class="icon icon-hint pl" id="'.$attr_id.'"></div>';

		//��������������� �������: ��������� ����� �������� ������� ������ (��� [27])
		case 56:
			/*
				��� �������� ����� JS.
				cmp_id �������� ids ������������ ��������� � ����������� �������
			*/
			if($is_edit)
				return '<div class="_empty min">��������� ����� �������� ������� ������</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';

		//���� ������������ ������
		case 57:
			/*
				num_1 - ������� ��� ����:
						1158 - ��������� ����� ������
						1159 - � ������ ��������������
			*/

			if(empty($el['vvv']))
				return '';

			$type = array(
				1158 => 2,
				1159 => 1
			);

			$razdel = '';
			foreach($el['vvv'] as $r)
				$razdel .= '<a class="link'._dn($el['def'] != $r['id'], 'sel').'">'.$r['title'].'</a>';

			return
				'<input type="hidden" id="'.$attr_id.'" value="'.$el['def'].'" />'.
				'<div class="_menu'.$type[$el['num_1']].'">'.$razdel.'</div>';

		//��������������� �������: ��������� ������� ���� ������������ ������ (��� [57])
		case 58:
			/*
				��� �������� ����� JS.
			*/
			if($is_edit)
				return '<div class="_empty min">��������� ������� ���� ������������ ������</div>';

			return '';

		//�������� �����������
		case 60:
			/*
				num_7 - ����������� ������ (��������� ������)

				num_1 - ������������ ���������� �����������, ������� ��������� ���������
			*/
			if($is_edit)
				return '<div class="_empty min">�����������</div>';

			$v = _num($v);

			//������� ����������� ����������� ��� ��������������, ������� ���� �� ��������� � ���������� ���
			$sql = "UPDATE `_image`
					SET `obj_name`='elem_".$el['id']."',
						`deleted`=1,
						`user_id_del`=".USER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE `obj_name`='elem_".$el['id']."_".USER_ID."'";
			query($sql);

			$html = '';
			$del_count = 0;
			if($unit_id = _num(@$unit['id'])) {
				$sql = "SELECT *
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND !`deleted`
						ORDER BY `sort`";
				if($spisok = query_arr($sql))
					foreach($spisok as $r)
						$html .= _imageDD($r);

				$sql = "SELECT COUNT(*)
						FROM `_image`
						WHERE `obj_name`='elem_".$el['id']."'
						  AND `obj_id`=".$unit_id."
						  AND `deleted`";
				$del_count = query_value($sql);
			}
			return
			'<div class="_image">'.
				'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
				'<dl>'.
					$html.
					'<dd class="dib">'.
						'<table class="_image-load">'.
							'<tr><td>'.
									'<div class="_image-add icon-image"></div>'.
									'<div class="icon-image spin"></div>'.
									'<div class="_image-prc"></div>'.
									'<div class="_image-dis"></div>'.
									'<table class="tab-load">'.
										'<tr><td class="icon-image ii1">'.//������� �� ������
												'<form>'.
													'<input type="file" accept="image/jpeg,image/png,image/gif,image/tiff" />'.
												'</form>'.
											'<td class="icon-image ii2">'.      //������� ������ �� �����������
										'<tr><td class="icon-image ii3">'.      //���� � ���������
											'<td class="icon-image ii4'._dn($del_count, 'empty').'" val="'.$del_count.'">'.//������� �� �������
									'</table>'.

						'</table>'.
					'</dd>'.
				'</dl>'.
				'<div class="_image-link dn mt5">'.
					'<table class="w100p">'.
						'<tr><td>'.
								'<input type="text" class="w100p" placeholder="�������� ������ ��� �������� � ������� Enter" />'.
							'<td class="w50 center">'.
								'<div class="icon icon-ok"></div>'.
								'<div class="icon icon-del pl ml5"></div>'.
					'</table>'.
				'</div>'.
			'</div>';

		//������ ������� ��������
		case 68:
			if($is_edit)
				return '<div class="_empty min">������� ��������.</div>';

			return _historySpisok($el);

		//�������� ������: ��� ������������
		case 69: return _spisokUnitUser($el, $unit);

		//�������� ������: ������ ����������
		case 71: return '<div class="icon icon-move pl"></div>';




		//---=== �������� � ��������� (�������) ===---
		//������ �������� ��� ������� [1]
		case 28: return 28;

		//���������� �������� ��� ������� [1]: �������/����� ������
		case 36:
			/*
				������� _element_func
					action_id - �������� ��� ������
						726 - ������
						727 - ��������
					cond_id - ������� ��������
						730 - ������� �����
						731 - ������� �����������
					action_reverse - ��������� �������� ��������
					effect_id - �������
						44 - ������������/���������
						45 - ������������/��������������
					target - id ������, �� ������� ������������ �������
			*/
			return 36;

		//������ �������� ��� ����������� ���� [17]
		case 39: return 39;

		//���������� �������� ��� ����������� ���� [17]: �������/����� ������
		case 40:
			/*
				������� _element_func
					action_id - �������� ��� ������
						709 - ������
						710 - ��������
					cond_id - ������� ��������
						703 - �������� �� �������
						704 - �������� �������
						705 - ���������� ��������
					action_reverse - ��������� �������� �������� (��� �������/�� �������)
					value_specific - ����������� �������� (��� ������� 705)
					effect_id - �������
						715 - ������������/���������
						716 - ������������/��������������
					target - id ������, �� ������� ������������ �������
			*/
			return 40;

		//������� �����
		case 44:
			/*
				txt_1 - ids ���������, ����������� ����������
			*/

			if(!$el['txt_1'])
				return '';

			$txt = '';
			$sql = "SELECT *
					FROM `_element`
					WHERE `id` IN (".$el['txt_1'].")
					ORDER BY `sort`";
			foreach(query_arr($sql) as $r) {
				$txt .= _elemUnit($r, $unit);
				$txt .= $r['num_8'] ? ' ' : ''; //���������� ������� ������, ���� �����
			}

			$txt = _spisokUnitUrl($el, $unit, $txt);

			return $txt;

		//��������������� �������: ��������� ���������� �������� ������
		case 49:
			/*
				��� �������� ����� JS.
				cmp_id �������� ids ������������ ��������� � ����������� �������
			*/
			if($is_edit)
				return '<div class="_empty min">���������� �������� ������</div>';

			return '<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />';




		//---=== ������� ===---
		//������� ����� - ������
		case 7:
			/*
                txt_1 - ����� ������
				num_1 - id ��������, ����������� ������, �� �������� ���������� �����
				txt_2 - �� ����� ����� ����������� ����� (id ��������� ����� ������� ������� ������)
			*/
			return _search(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'v' => _spisokFilter('v', $el['id']),
						'disabled' => $disabled
					));

		//����������� ������ ��� ������� ���������� ������
		case 52:
			/*
				�������� ������ �� ��������, ������� ��������� ������� ������

				num_1 - id ��������, ������� ��������� ������
			*/
			if($is_edit)
				return '������-������';

			return '';

		//������� - �� ��������
		case 53:
			/*
			*/
			return '�������';

		//������� - ������
		case 62:
			/*
				txt_1 - ����� ��� �������
				num_1 - ������� �����������:
						1439 - ������� �����������
						1440 - ������� �� �����������
				num_2 - id ��������, ������������ ������
			*/

			return _check(array(
				'attr_id' => $attr_id,
				'title' => $el['txt_1'],
				'disabled' => $disabled,
				'value' => _num(_spisokFilter('v', $el['id']))
			));

		//���������
		case 77:
			/*
				num_1 - id ��������, ������������ ������
			*/
			return _filterCalendar($el);



		//---=== ������ ===---
		//��������� ����� �������� ������� ������
		case 27:
			/*
				txt_2 - ids �������� ��� ��������
			*/
			return $el['name'];

		//���������� �������� ������������ ������
		case 54:
			/*
				num_1 - ����������� ������
			*/
			return $el['name'];

		//����� �������� ������������ ������
		case 55:
			/*
				��� �������� ���� ������������ ������� sum_1, sum_2, ...

				num_1 - ����������� ������
				num_2 - id �������� �������� (�������) ������������ ������
			*/
			return $el['name'];
	}

	return'����������� �������='.$el['dialog_id'];
}

function _pageElemMenu($unit) {//������� dialog_id=3: ���� �������
	$menu = array();
	foreach(_page() as $id => $r) {
		if(!$r['app_id'])
			continue;
		if($r['sa'])
			continue;
		if(!$r['access'])
			continue;
		if($unit['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return '�������� ���.';

	$razdel = '';
	foreach($menu as $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';
		$page_id = $r['id'];
		if($r['common_id']) {//���� �������� �������� ������� �� ������ ��������, ��� ���� ��� ����������, ����� ������ ��������� ���������
			$page_id = $r['common_id'];
			$p = _page($page_id);
			if(!$p['access']) {
				$page_id = 0;
				foreach(_page('child', $r['id']) as $p)
					if($p['access']) {
						$page_id = $p['id'];
						break;
					}
			}
		}

		if(!$page_id)
			continue;

		$razdel .=
			'<a class="link'.$sel.'" href="'.URL.'&p='.$page_id.'">'.
				$r['name'].
			'</a>';
	}

	//������� ��� ����
	$type = array(
		0 => 0,
		10 => 0,//�������� ��� - �������������� ����
		11 => 1,//� �������������� (�����.)
		12 => 2,//����� ��������� ������ (�����.)
		13 => 3 //������� ������������ ����
	);

	return '<div class="_menu'.$type[$unit['num_2']].'">'.$razdel.'</div>';
}
















function _page_div() {//todo ����
	return
	'<div class="mar20 bor-e8 pad20" id="for-hint">'.
		'�������� ����� '.
		'<div class="icon icon-edit"></div>'.
		'<div class="icon icon-hint"></div>'.
		'<div class="icon spin pl wh"></div>'.
		'<div class="icon icon-del"></div>'.
		'<div class="icon icon-del-red"></div>'.
		'<div class="icon icon-add"></div>'.
		'<div class="icon icon-ok"></div>'.
		'<div class="icon icon-set"></div>'.
		'<div class="icon icon-set-b"></div>'.
		'<div class="icon icon-off"></div>'.
		'<div class="icon icon-offf"></div>'.
		'<div class="icon icon-doc-add"></div>'.
		'<div class="icon icon-order"></div>'.
		'<div class="icon icon-client"></div>'.
		'<div class="icon icon-worker"></div>'.
		'<div class="icon icon-vk"></div>'.
		'<div class="icon icon-rub"></div>'.
		'<div class="icon icon-usd"></div>'.
		'<div class="icon icon-stat"></div>'.
		'<div class="icon icon-print"></div>'.
		'<div class="icon icon-out"></div>'.
		'<div class="icon icon-chain"></div>'.
		'<div class="icon icon-set-dot"></div>'.
		'<div class="icon icon-move"></div>'.
		'<div class="icon icon-move-x"></div>'.
		'<div class="icon icon-move-y"></div>'.
		'<div class="icon icon-sub"></div>'.
		'<div class="icon icon-join"></div>'.
		'<div class="icon icon-info"></div>'.
		'<div class="icon icon-search"></div>'.
		' �������� �����'.
	'</div>'.

	'<button class="vk mar20" id="bbb">������ ��� ����������</button>'.

	'<br>'.
	'<br>'.
	'<br>'.
	'<div class="w200 pad20">'.
		'<input type="hidden" id="aaa" value="2" />'.
	'</div>'.

	'<div class="w500 mt10">'.
		'<div class="_select dib bg-ffc w200 prel">'.
			'<table class="w100p">'.
				'<tr><td>456'.
			'</table>'.
		'</div>'.
		'<div class="_select  bg-ffc w200 prel">'.
			'<table>'.
				'<tr><td>456'.
			'</table>'.
		'</div>'.
	'</div>'.
//	'<div><input type="text" /></div>'.

	'<div class="mar20">'.
		'<div class="icon icon-edit wh"></div>'.
		'<div class="icon icon-del wh"></div>'.
	'</div>';
}
function gridStackStyleGen() {//������������� ������ ��� gridstack
	$step = 50;    //��� ����� �� �����������
	$send = '';
	$w = round(100 / $step, 10);//������ ���� � ���������
	$next = $w;
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-stack-item[data-gs-width='".$n."'] {width:".$next."%}<br>".
			".grid-stack-item[data-gs-x='".$n."']     {left:".$next."%}<br>".
			".grid-stack-item[data-gs-min-width='".$n."'] {min-width:".$next."%}<br>".
			".grid-stack-item[data-gs-max-width='".$n."'] {max-width:".$next."%}<br>".
			"<br>";
		$next = round($next + $w, 10);
	}

	return $send;
}
function gridStackStylePx() {//������������� ������ ��� grid-child
	$step = 100;    //��� ����� �� �����������
	$send = '';
	for($n = 1; $n <= $step; $n++) {
		$send .=
			".grid-child-item[data-gs-width='".$n."']{width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-x='".$n."']{left:".($n*10)."px}<br>".
			".grid-child-item[data-gs-min-width='".$n."']{min-width:".($n*10)."px}<br>".
			".grid-child-item[data-gs-max-width='".$n."']{max-width:".($n*10)."px}<br>".
			"<br>";
	}

	return $send;
}



function userImageMove() {//������� �������� ������������� � �����������
	_cache('clear', '_imageServerCache');

	$sql = "SELECT *
			FROM `_user`
			WHERE LENGTH(`ava`)";
	foreach(query_arr($sql) as $r) {
		$ex = explode('/', $r['ava']);
		$c = count($ex) - 1;
		$server = '';
		foreach($ex as $n => $v) {
			if($n == $c)
				continue;
			$server .= $v.'/';
		}
		$name = $ex[$c];

		$sql = "INSERT INTO `_image` (
					`server_id`,
	
					`max_name`,
					`max_x`,
					`max_y`,
	
					`80_name`,
					`80_x`,
					`80_y`,
	
					`obj_name`,
					`obj_id`,
	
					`user_id_add`
				) VALUES (
					"._imageServer($server).",
	
					'".$name."',
					50,
					50,
	
					'".$name."',
					50,
					50,
	
					'elem_1778',
					".$r['id'].",
	
					".USER_ID."
			)";
		query($sql);

		$image_id = query_insert_id('_image');
		$sql = "UPDATE `_user`
				SET `ava`='',
					`image_ids`=".$image_id."
				WHERE `id`=".$r['id'];
		query($sql);
	}
}





