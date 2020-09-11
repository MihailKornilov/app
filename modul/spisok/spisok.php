<?php
function _filterCache() {//кеширование фильтров списка
	if($send = _cache_get(FILTER_KEY))
		return $send;

	$send = array(
		'spisok' => array(),//все списки с фильтрами
		'filter' => array() //ассоциативный список элемент-фильтр -> значение
	);

	$sql = "SELECT *
			FROM `_user_spisok_filter`
			WHERE `app_id` IN (0,".APP_ID.")
			  and `user_id`=".USER_ID;
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
				'elem' => _element('struct', $elFilter[$filter_id]),
				'v' => $r['v'],
				'def' => $r['def']
			);
			$send['spisok'][$spisok_id][$filter_id] = $v;
			$send['filter'][$filter_id] = $v;
		}
	}

	return _cache_set(FILTER_KEY, $send);
}
function _filter($i='all', $v=0, $vv='') {//получение значений фильтров списка
	if(!defined('FILTER_KEY'))
		define('FILTER_KEY', 'FILTER_user'.USER_ID);

	if($i == 'cache_clear')
		return _cache_clear(FILTER_KEY);

	$F = _filterCache();

	//значение конкретного элемента-фильтра
	if($i == 'v') {
		if(!$v)
			return false;
		if(!isset($F['filter'][$v]))
			return false;
		return $F['filter'][$v]['v'];
	}

	//значение конкретного элемента-фильтра
	if($i == 'vv') {
		$el = $v;
		if(empty($el))
			return $vv;
		if(!is_array($el))
			return $vv;
		if(!$elem_id = _num($el['id']))
			return $vv;
		if(!isset($F['filter'][$elem_id]))
			return _filterInsert($el['num_1'], $el['id'], $vv);
		return $F['filter'][$elem_id]['v'];
	}

	//список элементов-фильтров для конкретного списка
	if($i == 'spisok') {
		if(!$v)
			return array();
		if(!isset($F['spisok'][$v]))
			return array();
		return $F['spisok'][$v];
	}

	//значения фильтров в формате JS по каждому списку во всём приложении
	if($i == 'page_js') {
		$send = array();
		foreach($F['spisok'] as $id => $arr)
			foreach($arr as $elid => $el)
				$send[$id][$elid] = $el['v'];
		return $send;
	}

	//внесение значение фильтра, если отсутствует
	if($i == 'insert') {
		if(!is_array($v))
			return '';
		if(empty($v))
			return '';
		if(!$spisok = _num(@$v['spisok']))
			return '';
		if(!$filter = _num(@$v['filter']))
			return '';
		$v = @$v['v'];

		_filterInsert($spisok, $filter, $v);
	}

	//обновление значения по умолчанию
	if($i == 'def_update') {
		if(!$filter_id = _num($v))
			return false;

		$def = $vv;

		$sql = "UPDATE `_user_spisok_filter`
				SET `def`='".addslashes($def)."'
				WHERE `element_id_filter`=".$filter_id;
		query($sql);

		_filter('cache_clear');
	}

	//определение отличия значений от условий по умолчанию
	if($i == 'diff') {
		if(!$v)
			return 0;
		if(empty($F['spisok'][$v]))
			return 0;
		foreach($F['spisok'][$v] as $r)
			if($r['v'] != $r['def'])
				return 1;
		return 0;
	}

	return $F;
}
function _filterInsert($spisok, $filter, $v) {//внесение нового значения фильтра
	if(!$spisok = _num($spisok))
		return $v;
	if(!$filter = _num($filter))
		return $v;
	if(!$SP = _elemOne($spisok))
		return $v;

	$sql = "SELECT *
			FROM `_user_spisok_filter`
			WHERE `app_id`=".APP_ID."
			  AND `user_id`=".USER_ID."
			  AND `element_id_spisok`=".$spisok."
			  AND `element_id_filter`=".$filter;
	$id = _num(query_value($sql));

	$sql = "INSERT INTO `_user_spisok_filter` (
				`id`,
				`app_id`,
				`user_id`,
				`element_id_spisok`,
				`element_id_filter`,
				`v`,
				`def`
			) VALUES (
				".$id.",
				".APP_ID.",
				".USER_ID.",
				".$spisok.",
				".$filter.",
				'".addslashes(_txt($v))."',
				'".addslashes(_txt($v))."'
			) ON DUPLICATE KEY UPDATE
				`v`=VALUES(`v`)";
	query($sql);

	_filter('cache_clear');

	return $v;
}
function _filterHtml($send, $spisok_id) {//получение данных списка после применения фильтра (через upd)
	if(!$el = _elemOne($spisok_id))
		return $send;

//	$spFunc = '_spisok'.$el['dialog_id'];
	$spFunc = '_element'.$el['dialog_id'].'_print';

	$send['upd'][] = array(
		'id' => $spisok_id,
		'html' => $spFunc($el)
	);

	return $send;
}
function _filterIgnore($el) {//определение игнорируется ли данный фильтр
	if($elem_id = _num($el))
		if(!$el = _elemOne($el))
			return true;
	if(!$bl = _blockOne($el['block_id']))
		return true;
	if(!$elm = _BE('elem_arr', $bl['obj_name'], $bl['obj_id']))
		return false;

	//поиск фильтров, которые требуют игнорирования
	foreach($elm as $id => $r)
		switch($r['dialog_id']) {
			//быстрый поиск
			case 7:
				if(!_filter('v', $id))
					return false;
				if(!$r['num_2'])
					return false;
				if(!$ass = _idsAss($r['txt_3']))
					return false;
				if(!isset($ass[$el['id']]))
					return false;
				return true;
		}

	return false;
}

function _spisokIsSort($elem_id) {//определение, нужно ли производить сортировку этого списка (поиск элемента 71)
	if(!$spisok_el = _BE('elem_arr', 'spisok', $elem_id))
		return 0;

	foreach($spisok_el as $elem)
		if($elem['dialog_id'] == 71)
			return 1;

	return 0;
}

function _spisokCountAll($el, $prm, $next=0) {//получение общего количества строк списка
	$key = 'SPISOK_COUNT_ALL'.$el['id'];

	if(defined($key))
		return constant($key);

	//диалог, через который вносятся данные списка
	if(!$dialog = _dialogQuery($el['num_1']))
		return 0;
	if(!$dialog['table_1'])
		return 0;

	$sql = "SELECT COUNT(*)
			FROM  "._queryFrom($dialog)."
			WHERE "._spisokWhere($el, $prm);
	$all = _num(query_value($sql));

	//проверка, есть ли единица списка, которую нашли по номеру (num)
	if(!$next && (_elem7num14($el) || _elem7num23($el)))
		$all++;

	define($key, $all);

	return $all;
}

function _spisokInclude($spisok) {//вложенные списки
	global $_SI;

	if(empty($spisok))
		return array();

	//проверка наличия колонки dialog_id в содержании списка
	$spkey = key($spisok);
	$sp0 = $spisok[$spkey];
	if(!isset($sp0['dialog_id']))
		return $spisok;
	
	//сбор ID диалогов
	$DLG_IDS = array();
	foreach($spisok as $r)
		if($dialog_id = _num(@$r['dialog_id']))
			$DLG_IDS[$dialog_id] = 1;
	
	if(empty($DLG_IDS))
		return $spisok;

	foreach($DLG_IDS as $dlg_id => $i) {
		$dlg = _dialogQuery($dlg_id);
		$CMP = $dlg['cmp'];
		foreach($CMP as $cmp) {//поиск компонента диалога с вложенным списком
			//должен является вложенным списком
			if(!_elemIsConnect($cmp))
				continue;

			//должно быть присвоено имя колонки
			if(empty($cmp['col']))
				continue;

			$col = $cmp['col'];

			//выборка будет производиться только по нужным строкам списка
			if(!$ids = _idsGet($spisok, $col))
				continue;

			//получение данных из вложенного списка
			$incDialog = _dialogQuery($cmp['num_1']);
			$incDialog = _dialogParent($incDialog);

			$sql = "SELECT "._queryCol($incDialog)."
					FROM   "._queryFrom($incDialog)."
					WHERE "._queryCol_id($incDialog)." IN (".$ids.")
					  AND "._queryWhere($incDialog, 1);
			$key = md5($sql);
			if(!isset($_SI[$key])) {
				if($arr = query_arr($sql)) {
					//вложения во вложенных списках
					$arr = _spisokInclude($arr);
					$arr = _spisokImage($arr);

				}
				$_SI[$key] = $arr;
			} else
				$arr = $_SI[$key];

			if(empty($arr))
				continue;

			//идентификаторы будут заменены на массив с данными единицы списка
			foreach($spisok as $id => $r)
				if($dlg_id == $r['dialog_id']) {
					$connect_id = $r[$col];
					if(is_array($connect_id))
						continue;
					if(empty($arr[$connect_id]))
						continue;
					$spisok[$id][$col] = $arr[$connect_id];
				}
		}
	}

	return $spisok;
}
function _spisokImage($spisok) {//вставка картинок
	if(empty($spisok))
		return array();

	//проверка наличия колонки dialog_id в содержании списка
	$key = key($spisok);
	$sp0 = $spisok[$key];
	if(empty($sp0['dialog_id']))
		return $spisok;

	$DLG = _dialogQuery($sp0['dialog_id']);

	foreach($DLG['cmp'] as $cmp) {//поиск компонента диалога с изображениями
		//должен является компонентом "загрузка изображений"
		if($cmp['dialog_id'] != 60)
			continue;

		//должно быть присвоено имя колонки
		if(!$col = $cmp['col'])
			continue;

		//подготовка массива для вставки изображения
		$image_ids = array();
		foreach($spisok as $id => $r) {
			$ids = $r[$col];
			if($iid = _idsFirst($ids))
				$image_ids[$id] = $iid;
			$spisok[$id][$col] = array('ids'=>$ids);
		}

		if($image_ids) {
			$sql = "SELECT *
					FROM `_image`
					WHERE `id` IN (".implode(',', $image_ids).")";
			if($img = query_arr($sql))
				foreach($spisok as $id => $r)
					if($image_id = _num(@$image_ids[$id]))
						if(!empty($img[$image_id]))
							$spisok[$id][$col] += $img[$image_id];
		}
	}

	return $spisok;
}
function _spisok96inc($EL, $spisok) {//получение значений, если в блоке присутствует элемент [96]
	foreach(_BE('elem_arr', 'spisok', $EL['id']) as $cmp_id => $cmp) {
		if($cmp['dialog_id'] != 96)
			continue;

		$key = 'el96_'.$cmp_id;

		//вставка пустых значений
		foreach($spisok as $id => $sp)
			$spisok[$id][$key] = array();

		//элемент в привязанном диалоге, отвечающий за размещение
		if(!$el = _elemOne($cmp['num_1']))
			continue;
		//колонка, по которой будет производиться выборка
		if(!$col = $el['col'])
			continue;
		if(!$bl = _blockOne($el['block_id']))
			continue;
		if($bl['obj_name'] != 'dialog')
			continue;
		if(!$dlg = _dialogQuery($bl['obj_id']))
			continue;

		//получение данных привязанного списка
		$sql = "SELECT "._queryCol($dlg)."
				FROM   "._queryFrom($dlg)."
				WHERE  "._queryWhere($dlg)."
				  AND `".$col."` IN ("._idsGet($spisok).")";
		if(!$inc = query_arr($sql))
			continue;
		$inc = _spisokInclude($inc);

		//колонки для получения названия
		$txt = array();
		foreach(_ids($cmp['txt_1'], 'arr') as $elem_id) {
			$ell = _elemOne($elem_id);
			$txt[] = $ell['col'];
		}

		//колонки для получения цвета
		$color = array();
		foreach(_ids($cmp['txt_2'], 'arr') as $elem_id) {
			$ell = _elemOne($elem_id);
			$color[] = $ell['col'];
		}

		foreach($inc as $sp) {
			//id записи, которая дополняется
			$spisok_id = $sp[$col]['id'];

			//id записи, которая подсчитывается - получение названия
			$txt_id = 0;
			$txt_name = '';
			if(!empty($txt)) {
				$txt_id = $sp[$txt[0]]['id'];
				$txt_name = $sp[$txt[0]][$txt[1]];
			}

			//получение цвета
			$color_name = '';
			if(!empty($color))
				//название и цвет должны быть получены из одного диалога
				if($txt_id == $sp[$color[0]]['id'])
					$color_name = $sp[$color[0]][$color[1]];

			if(empty($spisok[$spisok_id][$key][$txt_id])) {
				$spisok[$spisok_id][$key][$txt_id] = array(
					'count' => 1,
					'name' => $txt_name,
					'bg' => $color_name
				);
				continue;
			}

			$spisok[$spisok_id][$key][$txt_id]['count']++;
		}
	}

	return $spisok;
}

function _spisokUnitQuery($dialog, $unit_id, $nosuq=false) {//получение данных записи
	global $SUQ;

	if(!$unit_id)
		return array();

	$key = $dialog['id'].'_'.$unit_id;

	if(!$nosuq && isset($SUQ[$key]))
		return $SUQ[$key];

	$dialog = _dialogParent($dialog);

	if(!$dialog['table_1'])
		return array();

	$sql = "SELECT "._queryCol($dialog)."
			FROM   "._queryFrom($dialog)."
			WHERE "._queryCol_id($dialog)."=".$unit_id."
			  AND "._queryWhere($dialog);
	if(!$spisok[$unit_id] = query_assoc($sql))
		return array();

	$spisok = _spisokInclude($spisok);
	$spisok = _spisokImage($spisok);

	$SUQ[$key] = $spisok[$unit_id];//пока без _arrNum
	
	return $SUQ[$key];
}
function _spisokUnitUrl($el, $prm, $txt) {//обёртка значения в ссылку
	if(!empty($prm['blk_setup']))
		return $txt;
	if(!$action = _BE('elem_one_action', $el['id']))
		return $txt;

	//данные записи
	if(!$u = $prm['unit_get'])
		$u['id'] = 0;
	if(!empty($u['deleted']))
		return $txt;

	if($prm['blk_setup'])
		return '<a class="inhr">'.$txt.'</a>';

	foreach($action as $func)
		switch($func['dialog_id']) {
			//переход на страницу
			case 221:
				$isFilter = false;//наличие фильтра
				$fAccept = false; //фильтр подошёл
				if($F = _decode($func['filter'])) {
					$isFilter = true;
					$F = $F[0];
					switch($F['cond_id']) {
						case 3: //равно
							$v = _elemUids($F['elem_id'], $u);
							if($v == $F['txt'])
								$fAccept = true;
							break;
					}
				}

				if($isFilter && !$fAccept)
					break;

				$page_id = $func['target_ids'];
				$id = _spisokUnitUrlPage($el, $page_id, $u);
				return '<a href="'.URL.'&p='.$page_id.'&pfrom='._page('cur').($id ? '&id='.$id : '').'" class="inhr">'.$txt.'</a>';

			//открытие диалога
			case 205:
				if($func['initial_id'] != -2)
					break;
			case 222:
				//открытие по ID из списка диалогов (сам себя)
				if($func['target_ids'] == -2)
					$val = 'dialog_id:'.$u['id'];
				else
					$val = 'dialog_id:'.$func['target_ids'];

				//элемент передаёт id записи для отображения
				if($func['apply_id'])
					$val .= ',get_id:'._unitUrlId($u, _num($func['target_ids']));

				//блок передаёт id записи для редактирования
				if($func['effect_id'])
					$val .= ',edit_id:'._unitUrlId($u, $func['target_ids']);

				//блок передаёт id записи для удаления
				if($func['revers'])
					$val .= ',del_id:'._unitUrlId($u, $func['target_ids']);

//				if(preg_match('/class="/', $txt))
//					return preg_replace('/class="/', 'val="'.$val.'" class="dialog-open curP ', $txt, 1);

				return '<a val="'.$val.'" class="dialog-open inhr">'.$txt.'</a>';

			//внешняя ссылка
			case 224:
				$link = $func['target_ids'] ? $func['target_ids'] : $txt;
				$link = preg_match('/^http/', $link) ? $link : '//'.$link;
				$link = str_replace('{APP_ID}', APP_ID, $link);
				return
				'<a href="'.$link.'" class="inhr" target="_blank">'.
					_elemAction245($el, $txt).
				'</a>';

			//открытие документа
			case 227:
				$doc_id = $func['target_ids'];
				return '<a class="inhr" href="'.URL.'&p=9&doc_id='.$doc_id.'&id='.$u['id'].'">'.$txt.'</a>';
		}

	return $txt;
}
function _spisokUnitUrlPage($el, $page_id, $u) {//получение id записи согласно странице
	if(empty($u))
		return 0;
	if(!$page = _page($page_id))
		return $u['id'];
	if(empty($page['dialog_id_unit_get']))
		return $u['id'];
//	if(!empty($u['dialog_id_use']) && $u['dialog_id_use'] == $page['dialog_id_unit_get'])
//		return $u['id'];

	switch($el['dialog_id']) {
		case 11:
			if(!$elem_id = _idsFirst($el['txt_2']))
				return $u['id'];
			if(!$col = _elemCol($elem_id))
				return $u['id'];
			return is_array($u[$col]) ? $u[$col]['id'] : $u['id'];
		case 69://имя пользователя
			return $u['user_id_add'];
	}

	return $u['id'];
}
function _unitUrlId($u, $dlg_id) {//получение id из записи для ссылки
	if(empty($u))
		return 0;
	if(!is_array($u))
		return 0;
	if(!isset($u['dialog_id']))
		return $u['id'];
	if(!$DLG = _dialogQuery($dlg_id))
		return $u['id'];
	if($DLG['is_unit_get']) {
		$PAR = _dialogParent($DLG);
		$dlg_id = $PAR['id'];
	}
	if($u['dialog_id'] == $dlg_id)
		return $u['id'];
	foreach($u as $i => $v)
		if(is_array($v))
			foreach($v as $ii => $vv)
				if($ii == 'dialog_id')
					if($vv == $dlg_id)
						return $v['id'];
	return $u['id'];
}

function _unitGet($prm) {//получение данных записи
	if(empty($prm['unit_get']))
		return array();
	return $prm['unit_get'];
}

function _spisokColSearchBg($el, $txt) {//подсветка значения колонки при текстовом (быстром) поиске
	$element_id_spisok = 0;

	//список-шаблон
	if($bl = _blockOne($el['block_id']))
		if($bl['obj_name'] == 'spisok')
			$element_id_spisok = $bl['obj_id'];

	//список-таблица
	if(!empty($el['parent_id'])) {
		if(!$ell = _elemOne($el['parent_id']))
			return $txt;
		if($ell['dialog_id'] == 23)
			$element_id_spisok = $el['parent_id'];
		elseif($ell['dialog_id'] == 44)
				if($bl = _blockOne($ell['block_id']))
					if($bl['obj_name'] == 'spisok')
						$element_id_spisok = $bl['obj_id'];
	}

	if(!$element_id_spisok)
		return $txt;

	$search = false;
	$v = '';

	//поиск элемента-фильтра-поиска
	foreach(_filter('spisok', $element_id_spisok) as $r)
		if($r['elem']['dialog_id'] == 7) {
			$search = $r['elem'];
			$v = $r['v'];
		}

	if(!$search)
		return $txt;
	if(!$v)
		return $txt;

	//совпадение с номером единицы списка
	if($v == $txt && strlen($v) == strlen($txt))
		return '<em class="fndd">'.$txt.'</em>';

	//выделение найденного значения возможно только если элемент был вставлен через [11]
	if($el['dialog_id'] != 11)
		return $txt;
	if(!$ids = _ids($el['txt_2'], 'arr'))
		return $txt;

	//ассоциативный массив колонок, по которым производится поиск
	$colIds = _idsAss($search['txt_2']);

	//если по данной колонке поиск разрешён, то выделение цветом найденные символы
	if(!isset($colIds[$ids[0]]))
		return $txt;

	return preg_replace(_regFilter($v), '<em class="fndd">\\1</em>', $txt, 1);
}

function _spisokWhere($el, $prm=array()) {//формирование строки с условиями поиска
	//$el - элемент, который размещает список 14 или 23.

	//диалог, через который вносятся данные списка
	$dlg = _dialogQuery($el['num_1']);

	if($el['dialog_id'] != 14 && $el['dialog_id'] != 23)
		return "!"._queryCol_id($dlg);

	$cond = _queryWhere($dlg);
	$cond .= _40cond($el, $el['txt_2'], $prm);
	$cond .= _elem7filter($el);
	$cond .= _elem62filter($el);
	$cond .= _elem72filter($el);
	$cond .= _elem74filter($el);
	$cond .= _elem75filter($el);
	$cond .= _elem77filter($el);
	$cond .= _elem78filter($el);
	$cond .= _elem83filter($el);
	$cond .= _elem102filter($el);

	return $cond;
}

