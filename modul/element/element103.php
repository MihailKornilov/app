<?php

/* [103] Настройка доступа к страницам */

function _element103_print($el, $prm) {
	if(!$u = $prm['unit_get'])
		$u = $prm['unit_edit'];

	//доступные страницы
	$pageIds = array();

	if($u) {
		if(_sa($u['id']))
			return _empty('SA: Доступны все страницы.');
		if($u['access_admin'])
			return _empty('Администратор приложения: доступны все страницы.');
		$pageIds = _idsAss(_user($u['id'], 'access_pages'));
	}

	$arr = _page('app');
	$sort = array();
	foreach($arr as $id => $r) {
		if($r['parent_id']) {
			if(empty($sort[$r['parent_id']]))
				$sort[$r['parent_id']] = array();
			$r['access'] = _num(@$pageIds[$id]);
			$sort[$r['parent_id']][] = $r;
			unset($arr[$id]);
		} else
			$arr[$id]['access'] = _num(@$pageIds[$id]);
	}

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" />'.
	'<dl>'._element103spisok($arr, $sort).'</dl>';
}
function _element103spisok($arr, $sort) {//список страниц для настройки доступа
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
			$send .= '<dl class="ml40'._dn($r['access']).'">'._element103spisok($sort[$r['id']], $sort).'</dl>';
	}

	return $send;
}
function _elem103save($cmp_id, $user_id, $val) {//сохранение доступа к страницам для конкретного пользователя
	if(!$cmp = _elemOne($cmp_id))
		return false;
	if($cmp['dialog_id'] != 103)
		return false;
	if(_sa($user_id))
		return false;
	//создатель приложения
	if($user_id == _app(APP_ID, 'user_id_add'))
		return false;

	$upd = array();
	$ass = _idsAss($val);
	$page = _page();

	if($ids = _ids($val, 'arr'))
		foreach($ids as $page_id) {
			if(empty($page[$page_id]))
				continue;

			$p = $page[$page_id];
			//если родительская страница недоступна, дочерняя пропускается
			if($parent_id = $p['parent_id']) {
				if(empty($ass[$parent_id]))
					continue;

				//то же самое для третьего уровня
				$p = $page[$parent_id];
				if($parent_id = $p['parent_id'])
					if(empty($ass[$parent_id]))
						continue;
			}

			$upd[] = $page_id;
		}

	$sql = "UPDATE `_user_access`
			SET `access_pages`='".implode(',', $upd)."'
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".$user_id;
	DB1::query($sql);

	_cache_clear('AUTH_'.CODE, 1);
	_cache_clear('page');
	_cache_clear('user'.$user_id);

	return true;
}
