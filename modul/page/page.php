<?php
function _pageCache() {//��������� ������� ������� �� ����
	if($arr = _cache())
		return $arr;

	$sql = "SELECT
				*,
				0 `block_count`,
				0 `elem_count`,
				1 `del_access`
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

	//��������� ���������� ��������� �� ������ ��������
	$sql = "SELECT
				`page_id`,
				COUNT(*) `c`
			FROM `_element`
			WHERE `block_id` IN ("._idsGet($block).")
			GROUP BY `page_id`";
	$elem = query_ass($sql);

	foreach($page as $id => $r) {
		$block_count = _num(@$block[$id]);
		$elem_count = _num(@$elem[$id]);
		$page[$id]['block_count'] = $block_count;
		$page[$id]['elem_count'] = $elem_count;
		$page[$id]['del_access'] = $block_count || $elem_count ? 0 : 1;
	}

	return _cache($page);
}
function _page($i='all', $i1=0) {//��������� ������ ��������
	if(!defined('APP_ID'))
		return 0;

	if(!$i)
		return 0;

	$page = _pageCache();

	if($i === 'all')
		return $page;

	//id �������� �� ���������, ���� �� $_GET
	if($i == 'cur') {
		if($page_id = _num(@$_GET['p'])) {
			if(!isset($page[$page_id]))
				return 0;
			return $page_id;
		}

		foreach($page as $p) {
			if(!$p['def'])
				continue;

			if(!SA && $p['sa'])
				continue;

			return $p['id'];
		}

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

function _pageSetupDefine() {//��������� ����� ��������� ���������� ��������� PAS: page_setup
	$pas = 0;

	if($page_id = _page('cur'))//�������� ����������
		if($page = _page($page_id))//������ �������� ��������
			if(!($page['sa'] && !SA))
				if(!(!$page['app_id'] && !SA))
					$pas = _bool(@$_COOKIE['page_setup']);

	define('PAS', $pas);
}
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
		'.placeholder{outline:1px dashed #4183C4;margin-top:1px}'.
		'ol{list-style-type:none;max-width:700px;padding-left:40px}'.
	'</style>'.
	'<ol id="page-sort">'._pageSetupAppPageSpisok($arr, $sort).'</ol>'.
	'<script>_pageSetupAppPage()</script>';
}
function _pageSetupAppPageSpisok($arr, $sort) {//������ ������� ����������
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r) {
		$send .= '<li class="mt1" id="item_'.$r['id'].'">'.
			'<div class="curM">'.
				'<table class="_stab w100p bor-e8 bg-fff over1">'.
					'<tr><td>'.
							'<a href="'.URL.'&p='.$r['id'].'" class="'.(!$r['parent_id'] ? 'b fs14' : '').'">'.$r['name'].'</a>'.
								($r['def'] ? '<div class="icon icon-ok fr curD'._tooltip('�������� �� ���������', -76).'</div>' : '').
						'<td class="w35 wsnw">'.
							'<div val="dialog_id:20,unit_id:'.$r['id'].'" class="icon icon-edit dialog-open'._tooltip('�������� ��������', -58).'</div>'.
	   (!$r['del_access'] ? '<div class="icon icon-off'._tooltip('��������', -29).'</div>' : '').
		($r['del_access'] ? '<div onclick="_dialogOpen(6,'.$r['id'].')" class="icon icon-del-red'._tooltip('�������� ������, �������', -79).'</div>' : '').
				'</table>'.
			'</div>';
		if(!empty($sort[$r['id']]))
			$send .= '<ol>'._pageSetupAppPageSpisok($sort[$r['id']], $sort).'</ol>';
	}

	return $send;
}
function _pageSetupMenu() {//������ ���� ���������� ���������
	if(!APP_ID)
		return '';
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

function _pageShow($page_id) {
	if(!$page = _page($page_id))
		return _contentMsg();

	if(!SA && $page['sa'])
		return _contentMsg();

	return
	_blockHtml('page', $page_id).
//	_page_div().
	'<script>'.
		'var PAGE_LIST='._page('for_select', 'js').','.
			'BLOCK_ARR='._blockJS('page', $page_id).','.
			'ELEM_COLOR={'._elemColor().'};'.
	'</script>'.
	'<script>_pageShow('.PAS.')</script>';
}
function _elemDiv($el, $unit=array()) {//������������ div ��������
	if(!$el)
		return '';

	$tmp = !empty($el['tmp']);//������� ������ �������
	$attr_id = $tmp ? '' : ' id="pe_'.$el['id'].'"';

	$cls = array();
	$cls[] = $el['color'];
	$cls[] = $el['font'];
	$cls[] = $el['size'] ? 'fs'.$el['size'] : '';
	$cls = array_diff($cls, array(''));
	$cls = implode(' ', $cls);
	$cls = $cls ? ' class="'.$cls.'"' : '';

	return
	'<div'.$attr_id.$cls._elemStyle($el).'>'.
		_elemUnit($el, $unit).
	'</div>';

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
function _elemColor() {//������ ������ ��� ������ � ������� JS, ��������� ���������
	return
		'"":["#000","׸����"],'.
		'"color-555":["#555","Ҹ���-�����"],'.
		'"grey":["#888","�����"],'.
		'"pale":["#aaa","�������"],'.
		'"color-ccc":["#ccc","������ �������"],'.
		'"blue":["#2B587A","Ҹ���-�����"],'.
		'"color-acc":["#07a","�����"],'.
		'"color-sal":["#770","���������"],'.
		'"color-pay":["#090","������"],'.
		'"color-aea":["#aea","����-������"],'.
		'"color-ref":["#800","Ҹ���-�������"],'.
		'"red":["#e22","�������"],'.
		'"color-del":["#a66","Ҹ���-��������"],'.
		'"color-vin":["#c88","��������"]';
}
function _elemUnit($el, $unit=array()) {//������������ �������� ��������
	$UNIT_ISSET = isset($unit['id']);
	if(!$US = @$unit['source'])
		$US = array();

	//�������� �� ������
	$v = $UNIT_ISSET && $el['col'] ? $unit[$el['col']]: '';
	$attr_id = 'cmp_'.$el['id'];
	$disabled = BLOCK_EDIT || ELEM_WIDTH_CHANGE || !empty($unit['choose']) ? ' disabled' : '';

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
			return '<input type="hidden" id="'.$attr_id.'" value="'._num($v).'" />';

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

		//Radio
		case 16:
			/*
				txt_1 - ����� �������� ��������
				num_1 - �������������� ���������
				�������� �� _element ����� dialog_id:19
			*/
			$sql = "SELECT `id`,`txt_1`
					FROM `_element`
					WHERE `dialog_id`=19
					  AND `block_id`=-".$el['id']."
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

		//Select - ����� ������, ������� ���� � ����������
		case 24:
			/*
                txt_1 - �����, ����� ������ �� ������
				������� _dialogSpisokOn()
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

		//Select - ����� ������ �� ����������� ������� ��������
		case 27:
			/*
				������ ����������� ��������� 14(������) � 23(�������)
				���������������� ���������� �������� id ��������� (� �� ��������)

                txt_1 - �����, ����� ������ �� ������
				������� _dialogSpisokOnPage()
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
				num_2 - ����������� ���������� ����� ��������
				num_3 - ����� �������� �������
			*/
			return _select(array(
						'attr_id' => $attr_id,
						'placeholder' => $el['txt_1'],
						'width' => $el['width'],
						'value' => _num($v)
				   ));

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
			/*
                num_1 - ���������� ��� ������� ����� �������
			*/
			if($el['num_1'] && !empty($US)) {
				if($block = _blockQuery($US['block_id']))
					if($block['obj_name'] == 'dialog') //����� ����� ������� ����� �������������, ������ ���� ������� ����������� � �������
						return
							'<table>'.
								'<tr><td class="pr3 b color-555">'._dialogParam($block['obj_id'], 'base_table').'.'.
									'<td>'._select(array(
												'attr_id' => $attr_id,
												'width' => $el['width']
										   )).
							'</table>';
			}

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




		//---=== �������� ��� ����������� ===---
		//button
		case 2:
			/*
				txt_1 - ����� ������
				num_1 - ����
				num_2 - ��������� ������
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
			$dialog_source = $el['block']['obj_name'] == 'dialog' ? ',dialog_source:'.$el['block']['obj_id'] : '';
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

		//�����
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
						'v' => $el['v'],
						'disabled' => $disabled
					));

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
				num_1 - id ��������, ���������� �� �������, ������� ������ ������ ������ (����� dialog_id=26)
				num_2 - �������� �������
			*/

			if(isset($el['txt_real']))
				return $el['txt_real'];

			$sql = "SELECT *
					FROM `_element`
					WHERE `id`=".$el['num_1'];
			if(!$elem = query_assoc($sql))
				return '������� �����������</div>';

			switch($elem['dialog_id']) {
				case 8: return '��������� ��������';
				case 10: return $elem['txt_1'];
			}

			return '�������� �� ��������';

		//SA: ������� PHP
		case 12:
			/*
                txt_1 - ��� �������
			*/
			if(!$el['txt_1'])
				return '������ �������� �������';
			if(!function_exists($el['txt_1']))
				return '������� �� ����������';
			return $el['txt_1']();

		//���������� ������� ������ - ������
		case 14:
			/*
                num_1 - id �������, ������� ������ ������ ������ (������ �������� ����� �������������)
				num_2 - ����� (���������� �����, ��������� �� ���� ���)
				txt_1 - ��������� ������� �������

				��������� ������� ����� ��������������� �������: dialig_id=25
			*/
			if(PAS) {
				$dialog = _dialogQuery($el['num_1']);
				return '<div class="_empty">������ <b class="fs14">'.$dialog['spisok_name'].'</b></div>';
			}

			return _spisokShow($el);

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

			$BL = _blockQuery($bs_id);

			if($BL['obj_name'] != 'dialog')
				return _emptyMin('�������� ����� ���������<br>������ ����������� � ���������� �����.');

			$sql = "SELECT *
					FROM `_element_func`
					WHERE `block_id`=".$bs_id."
					ORDER BY `sort`";
			if(!$arr = query_arr($sql))
				return _emptyMin('�������� �� ���������.');

			$spisok = '';
			foreach($arr as $r) {
				$c = count(_ids($r['target'], 1));
				$spisok .=
					'<dd val="'.$r['id'].'">'.
					'<table class="bs5 ml10 bor1 bg-gr2 over2 mb5 curD">'.
						'<tr>'.
							'<td class="w35 top">'.
								'<div class="icon icon-move-y pl"></div>'.
							'<td class="w230">'.
								'<div class="fs15">�����-������� ������</div>'.
								'<div class="fs12 ml20 mt3">1 - ��������, 0 - ������</div>'.
								'<div class="fs12 ml20 pale">������� ���</div>'.
							'<td class="w100 b color-ref top center pt3">'.
								$c.' ����'._end($c, '', '�', '��').
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
				txt_2 - ids ��������� ����� �������. ���� �������� �������� � ������� _element

				��������� ������� ����� ��������������� �������: dialig_id=30
			*/
			if(PAS) {
				$dialog = _dialogQuery($el['num_1']);
				return '<div class="_empty">������-������� <b class="fs14">'.$dialog['spisok_name'].'</b></div>';
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
				num_2 - ��� ��������:
							40: ����� ��������
							41: ��������, ������� ������ ������
							42: ��������, �� ������� ����� ����������� �����
							43: �����
				num_3 - ����� ���������� ��������
			*/

			if(!$bs_id = _num(@$US['block_id']))
				return _emptyMin('����������� ID ��������� �����.');

			$BL = _blockQuery($bs_id);

			//������� �������� � ������� ������
			if($BL['obj_name'] == 'spisok')
				$BL = _blockQuery($BL['obj_id']);

			if(!$EL = $BL['elem'])
				return _emptyMin('���������� ������� ����� ��������<br>����� ������� �������� � ����.');

			if($el['num_2'] == 43 && $BL['obj_name'] != 'dialog')
				return _emptyMin('����� ������ �������� ������ ��� ��������.');

			//����� id �������, ������� ������� ��������
			$dialog_id = 0;
			switch($EL['dialog_id']) {
				case 7://�����
					if(!$EL['num_1'])
						return _emptyMin('���������� ������� ����� �������� ����� ������ ������,<br>�� �������� ����� ������������� �����.');
					$sp = _elemQuery($EL['num_1']);
					$dialog_id = $sp['num_1'];
					break;
				case 14://������-������
				case 23://������-�������
					if(!$dialog_id = $EL['num_1'])
						return _emptyMin('���������� ������� ����� �������� ����� ������ ������.');
					break;
			}

			if($el['num_2'] == 43)
				$dialog_id = $BL['obj_id'];

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
			$dialogCur = _dialogQuery($el['block']['obj_id']);
			if($dialogCur['base_table'] == '_element_func') {
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


			$send = array(
				'choose' => 1,
				'choose_access' => $choose_access,
				'choose_sel' => _idsAss($v),       //ids ����� ��������� ��������� ��� ������
				'choose_deny' => $choose_deny      //ids ��������� ��� ������, ������� �������� ������ (���� ��� ���� ������� ������ ������� ���� �� ��������)
			);

			return
			'<div class="fs14 pad10 pl15 bg-gr2 line-b">���������� ���� <b class="fs14">'.$dialog['spisok_name'].'</b>:</div>'.
			'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
			_blockHtml('dialog', $dialog_id, $dialog['width'], 0, $send);

		//������ �������� ��� �������
		case 28: return 28;

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

		//����� �������� ��� ������� (��������� ���� ��� ������)
		case 31:
			/*
				num_1 - id ��������, ���������� �� �������, ������� ������ ������ ������ (����� dialog_id=26)
			*/
			return '31';

		//�������� ������: ���������� �����
		case 32: return '���������� ����� ������������ ������ � �������';

		//�������� ������: ����
		case 33:
			/*
				num_1 - ������:
					35: 5 ������� 2017
					36: 5 ��� 2017
					37: 05/08/2017
				num_2 - �� ���������� ������� ���
				num_3 - ����� � ��������� ����:
					���������
					�����
					�������
					������
					�����������
			*/
			return '���� ������������ ������ � �������';

		//���������� �������� ��� �������: �������/����� ������
		case 36:
			/*
				������� _element_func
					action_id - ��������
					effect_id - �������
						44 - ������������/���������
						45 - ������������/��������������
					target - id ������, �� ������� ������������ �������
			*/
			return 36;
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
		if($unit['num_1'] != $r['parent_id'])
			continue;
		$menu[$id] = $r;
	}

	if(!$menu)
		return '�������� ���.';

	$razdel = '';
	foreach($menu as $r) {
		$sel = _page('is_cur_parent', $r['id']) ? ' sel' : '';
		$razdel .=
			'<a class="link'.$sel.'" href="'.URL.'&p='.$r['id'].'">'.
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
		'<div class="icon icon-hint"></div>'.
		' �������� �����'.
	'</div>'.

	'<button class="vk mar20" id="bbb">������ ��� ����������</button>'.

	'<br>'.
	'<br>'.
	'<br>'.
	'<div class="w200">'.
		'<input type="hidden" class="aaa" />'.
	'</div>'.

	'<div class="bg-ddf w500 mt10">'.
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

	'<div class="mar20 bg-ccd">'.
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







