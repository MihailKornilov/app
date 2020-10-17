<?php

/* [40] Фильтрование списка */
function _element40_struct($el) {
	/*
		Работает совместно с PHP12_spfl [41] - настройка значений
	*/
	return array(
		'num_1'   => _num($el['num_1']),//id элемента - путь к списку [13]
		'txt_1'   => $el['txt_1']       //текст нулевого значения
	) + _elementStruct($el);
}
function _element40_print($el, $prm) {
	$attr_id = _elemAttrId($el, $prm);
	$placeholder = ' placeholder="'.$el['txt_1'].'"';
	$disabled = $prm['blk_setup'] ? ' disabled' : '';

	$title = '';
	if($v = _elemPrintV($el, $prm)) {
		$vv = htmlspecialchars_decode($v);
		$arr = json_decode($vv, true);
		$c = count($arr);
		$title = $c.' услови'._end($c, 'е', 'я', 'й');
	}

	return
	'<input type="hidden" id="'.$attr_id.'" value="'.$v.'" />'.
	'<div class="_spfl dib w125 prel" id="'.$attr_id.'_spfl">'.
		'<div class="icon icon-filter pabs"></div>'.
		'<div class="icon icon-del pl pabs'._dn($v).'"></div>'.
		'<input type="text" readonly class="inp clr7 b pl25 curP w100p over3"'.$placeholder.$disabled.' value="'.$title.'" />'.
	'</div>';
}
function _element40_vvv($el, $prm) {//получение id диалога на основании исходного блока (если нет указания на диалог в настройке)
	if($el['num_1'])//указание есть. Диалог будет получен динамически из элемента
		return 0;

	$prm = _blockParam($prm);

	$dss = _elem40dss_14($prm);
	$dss = _elem40dss_23($prm, $dss);
	$dss = _elem40dss_88($prm, $dss);
	$dss = _elem40dss_43($prm, $dss);
	$dss = _elem40dss_44($prm, $dss);
	$dss = _elem40dss_page($prm, $dss);

	return $dss;
	if(!empty($prm['unit_edit']))
		$prm['srce']['element_id'] = $prm['unit_edit']['element_id'];

	//поиск элемента по ячейке таблицы
	if($ell_id = $prm['srce']['element_id']) {
		$sql = "SELECT *
				FROM `_element`
				WHERE `id`=".$ell_id;
		if($ell = query_assoc($sql))
			if($elp = _elemOne($ell['parent_id'])) {
				//таблица
				if($elp['dialog_id'] == 23)
					return $elp['num_1'];
				//таблица из нескольких списков
				if($elp['dialog_id'] == 88)
					if($json = json_decode($elp['txt_2'], true)) {
						$n = 0;
						foreach($json['col'] as $col)
							foreach($col['elm'] as $nn => $elm_id)
								if($elm_id == $ell_id)
									$n = $nn;
						foreach($json['spv'] as $nn => $spv)
							if($nn == $n)
								return $spv['dialog_id'];
					}
				//сборный текст
				if($elp['dialog_id'] == 44)
					foreach($elp['vvv'] as $vvv)
						if($vvv['id'] == $ell_id)
							if($vvv['dialog_id'] == 11) {
								$el11 = _elemOne(_idsFirst($vvv['txt_2']));
								$BL = _blockOne($el11['block_id']);
								if($BL['obj_name'] == 'dialog')
									return $BL['obj_id'];
								return 0;
							}
			}
	}

	if(!$block_id = $prm['srce']['block_id'])
		return 0;
	if(!$BL = _blockOne($block_id))
		return 0;

	if($EL = _elemOne($BL['elem_id']))
		if(_elemIsConnect($EL))//если является списком - отправка диалога списка
			return _num($EL['num_1']);

	if($BL['obj_name'] == 'page') {
		if(!$page = _page($BL['obj_id']))
			return 0;
		return $page['dialog_id_unit_get'];
	}

	return _blockDlgId($block_id);
}
function _elem40dss_bl($prm) {//получение данных блока
	if(!$bl = _blockOne($prm['srce']['block_id']))
		if($el = _elemOne($prm['srce']['element_id']))
			if(!$bl = _blockOne($el['block_id']))
				return $bl;

	if(empty($bl))
		return array();

	return $bl;
}
function _elem40dss_14($prm) {//получение id диалога из списка-шаблона
	if(!$bl = _elem40dss_bl($prm))
		return 0;
	if($bl['obj_name'] != 'spisok')
		return 0;
	if(!$el = _elemOne($bl['obj_id']))
		return 0;

	return _num($el['num_1']);
}
function _elem40dss_23($prm, $dss=0) {//получение id диалога из Списка-таблицы
	if($dss)
		return $dss;
	if(!$el = _elemOne($prm['srce']['element_id']))
		return 0;
	if(!$el = _elemOne($el['parent_id']))
		return 0;
	if($el['dialog_id'] != 23)
		return 0;

	return $el['num_1'];
}
function _elem40dss_88($prm, $dss=8) {//получение id диалога из ячейки элемента Несколько таблиц
	if($dss)
		return $dss;
	if($dss = _elem88dlgId($prm['srce']['element_id']))
		return $dss;
	if(!$u = $prm['unit_edit'])
		return 0;
	if(!$elem_id = _elemId($u['dialog_id'], $u['id']))
		return 0;
	if($dss = _elem88dlgId($elem_id))
		return $dss;

	return 0;
}
function _elem40dss_43($prm, $dss) {//получение id диалога из Шаблона записи
	if($dss)
		return $dss;
	if(!$bl = _elem40dss_bl($prm))
		return 0;
	if($bl['obj_name'] != 'tmp43')
		return 0;
	if(!$el = _elemOne($bl['obj_id']))
		return 0;
	if(!$bl = _blockOne($el['block_id']))
		return 0;
	if($bl['obj_name'] != 'dialog')
		return 0;

	return $bl['obj_id'];
}
function _elem40dss_44($prm, $dss) {//получение id диалога из Сборного текста
	if($dss)
		return $dss;
	if(!$el = _elemOne($prm['srce']['element_id']))
		return 0;
	if(!$el = _elemOne($el['parent_id']))
		return 0;
	if($el['dialog_id'] != 44)
		return 0;

	if(!$bl = _blockOne($el['block_id'])) {
		if(!$parent_id = $el['parent_id'])
			return 0;

		//сборный текст вставлен в ячейку [23] Список-таблица
		$prm['srce']['element_id'] = $el['id'];
		if($dss = _elem40dss_23($prm))
			return $dss;

		//сборный текст вставлен в ячейку [88] Таблица из нескольких списков
		if($dss = _elem40dss_88($prm))
			return $dss;

		return 0;
	}

	switch($bl['obj_name']) {
		case 'page':
			if(!$page = _page($bl['obj_id']))
				return 0;
			return _num($page['dialog_id_unit_get']);
	}

	return 0;
}
function _elem40dss_page($prm, $dss) {//получение id диалога из Страницы, принимающей данные записи
	if($dss)
		return $dss;
	if(!$bl = _elem40dss_bl($prm))
		return 0;
	if($bl['obj_name'] != 'page')
		return 0;
	if(!$page = _page($bl['obj_id']))
		return 0;

	return _num($page['dialog_id_unit_get']);
}
function _elem40res($filter, $u) {
	$send = true;
	foreach($filter as $ff) {
		$v = _elemUids($ff['elem_id'], $u);
		switch($ff['cond_id']) {
			//отсутствует
			case 1:
				if(!$v)
					break;
				$send = false;
				break;
			//присутствует
			case 2:
				if($v)
					break;
				$send = false;
				break;
			//равно
			case 3:
				$ff['unit_id'] = _40condVcopy($ff['unit_id']);
				$vv = $ff['unit_id'] ? $ff['unit_id'] : $ff['txt'];
				if($v == _40cond_dop($ff, $vv))
					break;
				$send = false;
				break;
			//не равно
			case 4:
				$ff['unit_id'] = _40condVcopy($ff['unit_id']);
				$vv = $ff['unit_id'] ? $ff['unit_id'] : $ff['txt'];

				//$v - значение, которое проверяется (которое пришло)
				//$vv - значение, установленное в фильтре

				$vv = _elemUidsChild($ff['elem_id'], $vv);

				if(!isset($vv[$v]))
					break;

				$send = false;
				break;
			//больше
			case 5:
				if($v*1 > _num($ff['txt']))
					break;
				$send = false;
				break;
			//больше или равно
			case 6:
				if($v >= _num($ff['txt']))
					break;
				$send = false;
				break;
			//меньше
			case 7:
				if($vv = _40cond_dop($ff, $ff['unit_id'])) {
					if(isDate($vv))
						if(isDate($v)) {
							$v = strtotime($v);
							$vv = strtotime($vv);
							if($v < $vv)
								break;
						}
				} else
					if($v < _num($ff['txt']))
						break;
				$send = false;
				break;
			//меньше или равно
			case 8:
				if($v <= _num($ff['txt']))
					break;
				$send = false;
				break;
			//содержит
			case 9:
				$send = false;
				break;
			//не содержит
			case 10:
				$send = false;
				break;
		}
	}

	return $send;
}

/* [40] Применение условий */
function _40cond($EL, $cond, $prm=array()) {//изначальные условия отображения списка
/*
	значения, которые может принимать unit_id:
		 -1 => 'Совпадает с текущей страницей'
		 -2 => 'Совпадает с данными, приходящей на диалог'
		 -3 => 'Совпадает с данными, которые принимает блок'
		 -4 => 'По значению указанного элемента'
		-11 => 'число текущего дня',
		-12 => 'число текущей недели',
		-13 => 'число текущего месяца',
		-14 => 'число текущего года'
		-15 => 'сегодня'
		-21 => 'текущий пользователь'
		-31 => 'значение v1'
*/

	if(empty($cond))
		return '/* [40] условия отсутствуют */';

	if(!is_array($cond))
		if(!$cond = _decode($cond))
			return " AND !`t1`.`id` /* [40] не получен массив условий */";

	$send = '';
	foreach($cond as $r) {
		if(!$ids = _ids($r['elem_id']))
			return " AND !`t1`.`id` /* [40] элемент не получен */";
		if(_ids($r['elem_id'], 'count') > 2)
			return " AND !`t1`.`id` /* [40] уровень вложения > 2 не отработан */";

		$elem_id = _idsLast($r['elem_id']);

		if(!$ell = _elemOne($elem_id))
			return " AND !`t1`.`id` /* [40] элемента ".$elem_id." не существует */";
		if(!$col = _elemCol($ell))
			return " AND !`t1`.`id` /* [40] отсутствует имя колонки */";
		if(!$BL = _blockOne($ell['block_id']))
			return " AND !`t1`.`id` /* [40] элемент не содержится блок */";
		if($BL['obj_name'] != 'dialog')
			return " AND !`t1`.`id` /* [40] элемент не из диалога */";
		if(!$DLG = _dialogQuery($BL['obj_id']))
			return " AND !`t1`.`id` /* [40] диалога не существует */";

		$col = '`'._queryTN($DLG, $col).'`.`'.$col.'`';

		$val = _40cond_cnn($EL, $r, $ell, $r['txt'], $prm);
		$val = _40cond_17($r, $ell, $val);
		$val = _40cond_date($ell, $val);
		$val = _40cond_dop($r, $val);

		if($ell['dialog_id'] == 31)//Выбор нескольких значений галочками
			if($r['cond_id'] == 9 || $r['cond_id'] == 10)//содержит / не содержит
				$val = ','.$val.',';    //оборачивание в запятые для точности запроса

		if($err = _40cond_err($val))
			return $err;

		$condV = "\n"._40condV(
					$r['cond_id'],
					$col,
					$val
				 ).
			(DEBUG ? " /* [el:".$r['elem_id'].",cond:".$r['cond_id'].",txt:\"".$r['txt']."\",id:".$r['unit_id']."] */ " : '');



		//если вложение - формирование запроса по идентификаторам
		if(_ids($r['elem_id'], 'count') == 2) {
			if(!$elem_id = _idsFirst($r['elem_id']))
				return " AND !`t1`.`id` /* [40] не получен элемент-список */";
			if(!$elsp = _elemOne($elem_id))
				return " AND !`t1`.`id` /* [40] элемента-списка ".$elem_id." не существует */";
			if(!_elemIsConnect($elsp))
				return " AND !`t1`.`id` /* [40] элемент ".$elem_id." не является списком */";
			if(!$DLG = _dialogQuery($elsp['num_1']))
				return " AND !`t1`.`id` /* [40] диалога ".$elsp['num_1']." не существует */";
			if(!$col = $elsp['col'])
				return " AND !`t1`.`id` /* [40] не получено имя колонки */";
			$send .= " AND `t1`.`".$col."` IN (
						SELECT `id`
						FROM  "._queryFrom($DLG)."
						WHERE "._queryWhere($DLG).$condV."
					   )";
			continue;
		}

		$send .= $condV;
	}

	return $send;
}
function _40cond_cnn($EL, $r, $ell, $v, $prm) {//значение подключаемого списка
	if(!_elemIsConnect($r['elem_id']))
		return $v;
	if(!$DLG_ID_CONN = $ell['num_1'])
		return '[40] отсутствует id диалога, размещающего список';
	if($r['cond_id'] != 3 && $r['cond_id'] != 4)
		return $v;
	if(!$unit_id = _num($r['unit_id'], 1))
		return 0;
	//указан вариант, когда страница принимает данные записи
	if($unit_id == -1) {
		if(!$unit_id = _num(@$_GET['id']))
			return '[40] страница не принимает данные записи';

		if(empty($EL))
			return $unit_id;
		if(!$BL = _blockOne($EL['block_id']))
			return $unit_id;

		$dlg_id = $DLG_ID_CONN;

		//проверка, чтобы список был размещён на странице или в диалоге
		switch($BL['obj_name']) {
			case 'page':
				if(!$page = _page($BL['obj_id']))
					return '[40] страницы '.$BL['obj_id'].' не существует';
				//id диалога, данные единицы списка которого выводится на странице
				if(!$dlg_id = $page['dialog_id_unit_get'])
					return '[40] странице не присвоен диалог, который принимает данные записи';
				break;
			case 'dialog':
//							if(!$DLG = _dialogQuery($BL['obj_id']))
//								return ' AND !`t1`.`id` /* [40] диалога '.$dlg_id.' не существует */';
//							if(!$DLG['is_unit_get'])
//								return ' AND !`t1`.`id` /* [40] диалог не принимает данные записи */';
//							if(!$unit_id = _num(@$_GET['id']))
//								return ' AND !`t1`.`id` /* no dialog unit_id */';
				break;
			default: return '[40] !is_page && !is_dialog';
		}

		//выбранный привязанный список совпадает с принимаемым страницей
		if($DLG_ID_CONN != $dlg_id) {
			if(!$DLG = _dialogQuery($dlg_id))
				return '[40] no dialog='.$dlg_id;
			//получение данных записи, которую принимает страница
			if(!$unit = _spisokUnitQuery($DLG, $unit_id))
				return '[40] не получены данные записи';
			//поиск первого элемента, который содержит привязанный список выбранного значения для отображения
			$cmp = false;
			foreach($DLG['cmp'] as $c)
				if(_elemIsConnect($c))
					if($c['num_1'] == $DLG_ID_CONN) {
					$cmp = $c;
					break;
				}

		/*
			echo 'указатель на связку='.$DLG_ID_CONN.' ('._dialogParam($DLG_ID_CONN, 'name').') col='.$col.'<br>';
			echo 'страница принимает='.$dlg_id.' ('._dialogParam($dlg_id, 'name').') единицу списка '.$unit_id.'<br>';
			echo 'найденная колонка из связки '.$cmp['col'].'<br>';
			echo 'получен id от указателя '.$unit[$cmp['col']]['id'].'<br>';
			echo 'выводится список='.$el['num_1'].' ('._dialogParam($el['num_1'], 'name').')<br>';
			echo '<br>';
		*/

			if(!$cmp)
				return ' AND !`t1`.`id` /* [40] no cmp */';

			$unit_id = is_array($unit[$cmp['col']]) ? $unit[$cmp['col']]['id'] : $unit[$cmp['col']];
		}

		return $unit_id;
	}

	//указан вариант, когда диалоговое окно принимает данные записи
	if($unit_id == -2)
		return _num(@$prm['unit_get_id']);

	//указан вариант, когда блок принимает данные записи
	if($unit_id == -3)
		return _num(@$prm['unit_get_id']);

	//указан вариант, где по элементу в исходном диалоге будет получено значение
	if($unit_id == -4) {
		if(!$el = _elemOne($r['txt'])) {
			switch($r['txt']) {
				//текущий пользватлель
				case -21: return USER_ID;
				//текущий диалог
				case -22: return $prm['srce']['dialog_id'];
				//текущая запись
				case -23:
					if(!$uid = _num(@$prm['unit_edit']['id']))
						return 0;
					return $uid;
			}
			return 0;
		}
		if($el['dialog_id'] != 29)//пока только для элемента [29]
			return 0;

		//если создание записи
		if($unit = @$prm['unit_get']) {
			if($el['num_6'] != -1)
				return 'элемент не принимает данные записи';
			return $unit['id'];
		}

		//если редактирование записи
		if($unit = @$prm['unit_edit']) {
			if(!$col = _elemCol($el))
				return 'нет колонки';
			if(empty($unit[$col]))
				return 'значение отсутствует';
			if(!is_array($unit[$col]))
				return _num($unit[$col]);
			return $unit[$col]['id'];
		}

		return 'запись отсутствует';
	}

	$unit_id = _40condVcopy($unit_id);

	//проверяются дочерние значения
	$sql = "/* [40] проверка дочерних значений */
			SELECT `id`
			FROM `_spisok`
			WHERE `parent_id`=".$unit_id;
	if($ids = query_ids($sql))
		$unit_id .= ','.$ids;

	return $unit_id;
}
function _40cond_17($r, $ell, $val) {//значения _select [17]
	if(_40cond_err($val))
		return $val;
	if($ell['dialog_id'] != 17)
		return $val;

	return _num($r['unit_id']);
}
function _40cond_date($ell, $val) {//если элемент является датой, преобразование значения в дату, если это число.
	if(_40cond_err($val))
		return $val;
	if(!_elemIsDate($ell))
		return $val;
	if(!preg_match(REGEXP_INTEGER, $val))
		return '[40] некорректное значение даты';

	//число - это количество дней
	//нулевое значение = сегодня
	//положительное = дни в будущем
	//отрицательное = дни в прошлом
	$val = TODAY_UNIXTIME + $val * 86400;
	return strftime('%Y-%m-%d', $val);
}
function _40cond_dop($r, $val) {//дополнительные условия, когда unit_id < 0
	if(_40cond_err($val))
		return $val;
	if($r['cond_id'] < 3)
		return $val;

	switch($r['unit_id']) {
		case -11: return _num(strftime('%d'));
		case -12:
			if($week = date('w'))
				return $week;
			return 7;
		case -13: return _num(strftime('%m'));
		case -14: return _num(strftime('%Y'));
		case -15: return TODAY;

		case -21: return USER_ID;

		case -31:
			if(empty($_GET['v1']))
				return "---###$$ /* v1 не получен */";
			return _txt($_GET['v1']);
	}

	return $val;
}
function _40cond_err($val) {//определение, была ли ошибка (если в строке будет найден текст "[40] ... ")
	if(!preg_match('/^\[40\][*]?/', $val))
		return false;

	return " AND !`t1`.`id` /* ".$val." */";
}
function _40condV($act, $col, $val) {//значение запроса по конкретному условию
	if(!$col)
		return '';

	$val = addslashes($val);
	switch($act) {
		//отсутствует
		case 1: return " AND ".$col."=DEFAULT(".$col.")";

		//присутствует
		case 2: return " AND ".$col."!=DEFAULT(".$col.")";

		//равно
		case 3:
			if(!_num($val) && _ids($val))
				return " AND ".$col." IN (".$val.")";
			return " AND ".$col."='".$val."'";

		//не равно
		case 4:
			if(!_num($val) && _ids($val))
				return " AND ".$col." NOT IN (".$val.")";
			return " AND ".$col."!='".$val."'";

		//больше
		case 5: return " AND ".$col.">'".$val."'";

		//больше или равно
		case 6: return " AND ".$col.">='".$val."'";

		//меньше
		case 7: return " AND ".$col."<'".$val."'";

		//меньше или равно
		case 8: return " AND ".$col."<='".$val."'";

		//содержит
		case 9: return " AND ".$col." LIKE '%".$val."%'";

		//не содержит
		case 10:return " AND ".$col." NOT LIKE '%".$val."%'";
	}

	return " AND !`t1`.`id` /* _40condV: не найдено условие */";
}
function _40condVcopy($unit_id) {//подмена значения для копии из оригинала
//	if($unit_id <= 0)
		return $unit_id;

	//проверка приложения-копии
	$app = _app(APP_ID);
	if(!$pid = $app['pid'])
		return $unit_id;

	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `id_old`=".$unit_id."
			LIMIT 1";
	return _num(query_value($sql));
}


/* [41] НАСТРОЙКА УСЛОВИЙ */
function PHP12_spfl($prm) {
	if(!PHP12_spfl_dss($prm))
		return _emptyMin('Отсутствует id исходного диалога');

	return '';
}
function PHP12_spfl_save($DLG) {
	if($DLG['id'] != 41)
		return;

	//поиск id элемента, который является подключаемой PHP функцией
	$vvv_id = 0;

	foreach($DLG['cmp'] as $cmp)
		if($cmp['dialog_id'] == 12)
			if($cmp['txt_1'] == 'PHP12_spfl')
				$vvv_id = $cmp['id'];

	if(!$vvv_id)
		jsonError('Не найдена подключаемая функция');

	$send['v'] = '';
	$send['title'] = '';

	if(!empty($_POST['vvv']))
		if($arr = $_POST['vvv'][$vvv_id])
			if(is_array($arr))
				if(!empty($arr)) {
					$v = array();
					foreach($arr as $r) {
						if(!$r['elem_id'] = _ids($r['elem_id']))
							continue;
						if(!$r['cond_id'] = _num($r['cond_id']))
							continue;
						$r['unit_id'] = _num($r['unit_id'], 1);
						$v[] = $r;
					}
					$send['v'] = json_encode($v);
					$c = count($v);
					$send['c'] = $c;
					$send['title'] = $c.' услови'._end($c, 'е', 'я', 'й');
				}

	jsonSuccess($send);
}
function PHP12_spfl_vvv($prm) {//получение настроек для редактирования

	$send = array(
		'dss' => PHP12_spfl_dss($prm),
		'vvv' => array(),
		'drop' => PHP12_spfl_drop()//стандартные значения выпадающего списка
	);

	//получение id диалога по элементу, через который был выбор
	if($elem_id = $prm['srce']['element_id'])
		$send['dss'] = _dialogSel24($elem_id, $prm['srce']['dss']);

	if(!$arr = $prm['srce']['dop'])
		return $send;

	$arr = htmlspecialchars_decode($arr);
	if(!$arr = json_decode($arr, true))
		return $send;

	foreach($arr as $n => $r) {
		$arr[$n]['elem_title'] = _elemIdsTitle($r['elem_id']);
		$arr[$n]['spisok'] = array();
		$arr[$n]['unit4title'] = '';
		if($arr[$n]['elem_issp'] = _elemIsConnect($r['elem_id'])) {
			$spisok = _29cnn($r['elem_id']);
			$arr[$n]['spisok'] = PHP12_spfl_vvv_unshift($spisok);
			if($r['unit_id'] == -4)
				$arr[$n]['unit4title'] = _elemIdsTitle($r['txt']);
		} else {
			$last = _idsLast($r['elem_id']);
			if($el = _elemOne($last))
				if($el['dialog_id'] == 17) {
					$arr[$n]['elem_issp'] = 1;
					$arr[$n]['spisok'] = _element('vvv', $el);
				}
		}
	}

	$send['vvv'] = $arr;

	return $send;
}
function PHP12_spfl_dss($prm) {//получение id исходного диалога
	if($dss = $prm['srce']['dss'])
		return $dss;

	if($block_id = $prm['srce']['block_id'])
		if($BL = _blockOne($block_id))
			if($BL['obj_name'] == 'dialog') {
				if($DLG = _dialogQuery($BL['obj_id']))
					if($DLG['is_unit_get']) {
						$PAR = _dialogParent($DLG);
						return $PAR['id'];
					}
				return $BL['obj_id'];
			}

	return 0;
}
function PHP12_spfl_drop() {
	return array(
		-11 => 'число текущего дня',
		-12 => 'число текущей недели',
		-13 => 'число текущего месяца',
		-14 => 'число текущего года',
		-15 => 'сегодня',

		-21 => 'текущий пользователь',

		-31 => 'значение v1'
	);
}
function PHP12_spfl_vvv_unshift($spisok) {//общие дополнительные значения
	array_unshift(
		$spisok,
		array(
			'id' => -4,
			'title' => 'Указанное значение',
			'content' => '<div class="b clr11">Указанное значение</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Указать элемент, значение которого будет использовано</div>'
		)
	);
	array_unshift(
		$spisok,
		array(
			'id' => -3,
			'title' => 'Совпадает с данными, которые принимает блок',
			'content' => '<div class="b clr11">Совпадает с данными, которые принимает блок</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет выбрана запись, которую принимает блок</div>'
		)
	);
	array_unshift(
		$spisok,
		array(
			'id' => -2,
			'title' => 'Совпадает с данными, которые принимает диалоговое окно',
			'content' => '<div class="b clr11">Совпадает с данными, которые принимает диалоговое окно</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет выбрана запись, которую принимает диалоговое окно</div>'
		)
	);
	array_unshift(
		$spisok,
		array(
			'id' => -1,
			'title' => 'Совпадает с текущей страницей',
			'content' => '<div class="b clr11">Совпадает с текущей страницей</div>'.
						 '<div class="fs12 clr1 ml10 mt3 i">Будет выбрана запись, которую принимает текущая страница</div>'
		)
	);

	return $spisok;
}
