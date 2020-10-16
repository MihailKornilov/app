<?php

/* [14] Список-шаблон */
function _element14_struct($el) {
	/*
		настройка шаблона через функцию PHP12_tmp_setup
	*/
	return array(
		'num_1' => _num($el['num_1']),//id диалога, который вносит данные списка (шаблон которого будет настраиваться)
		'num_2' => _num($el['num_2']),//длина (количество строк, выводимых за один раз)

		'txt_1' => $el['txt_1'],      //сообщение пустого запроса
		'txt_2' => $el['txt_2'],      //условия отображения, настраиваемые через [40]
		'num_3' => _num($el['num_3']),/* порядок:
											0 - автоматически
											2318 - по дате добавления
											2320 - значение из диалога
											2319 - сотрировка (на основании поля sort)
									  */
		'num_4' => _num($el['num_4']),//горизонтальное расположение списка
		'num_5' => _num($el['num_5']),//значение из диалога (для num_3=2320)
		'num_6' => _num($el['num_6']) //обратный порядок
	) + _elementStruct($el);
}
function _element14_title() {
	return 'Список ШАБЛОН';
}
function _element14_print($ELEM, $prm=array(), $next=0) {
	if(!$DLG = _dialogQuery($ELEM['num_1']))
		return _emptyRed('Диалога '.$ELEM['num_1'].' не существует.');
	if(!empty($prm['blk_setup']))
		return _emptyMin('Список-шаблон <b>'.$DLG['name'].'</b>');
	if(!_BE('block_arr', 'spisok', $ELEM['id']))
		return _emptyRed('Шаблон <b>'.$DLG['name'].'</b> не настроен.');

	$limit = $ELEM['num_2'];
	$SC = $ELEM['num_6'] ? 'DESC' : 'ASC';

	if(!$all = _spisokCountAll($ELEM, array(), $next))
		return $ELEM['txt_1'] ? _emptyMin(_br($ELEM['txt_1'])) : '';

	$IS_SORT = _spisokIsSort($ELEM['id']);

	$order = _queryCol_id($DLG);
	switch($ELEM['num_3']) {
		//по дате добавления
		case 2318:
			if($tab = _queryTN($DLG, 'dtime_add'))
				$order = "`".$tab."`.`dtime_add`";
			break;
		//ручная сортировка
		case 2319:
			if(!_queryTN($DLG, 'sort'))
				break;
			if(!$IS_SORT)
				break;
			$order = "`sort`";
			$SC = 'ASC';
			break;
		//значение из диалога
		case 2320:
			if(!$col = _elemCol($ELEM['num_5']))
				break;
			$order = "`".$col."`";
			break;
	}

	//получение данных списка
	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE  "._spisokWhere($ELEM)."
			ORDER BY ".$order." ".$SC."
			LIMIT ".($limit * $next).",".$limit;
	$spisok = query_arr($sql);

	//добавление записи, если был быстрый поиск по номеру
	if(!$next)
		$spisok = _elem7num14($ELEM, $spisok);

	//вставка значений из вложенных списков
	$spisok = _spisokInclude($spisok);

	//вставка картинок
	$spisok = _spisokImage($spisok);

	//вставка значений для элемента [96]
	$spisok = _spisok96inc($ELEM, $spisok);

	$send = '';
	foreach($spisok as $id => $sp) {
		$block = _BE('block_obj', 'spisok', $ELEM['id']);
		$prm = array(
			'unit_get' => $sp,
			'td_no_end' => $ELEM['num_4']
		);
		$send .= '<div class="sp-unit'._dn(!$ELEM['num_4'], 'dib').'" val="'.$id.'">'.
					_blockLevel($block, $prm).
				 '</div>';
	}

	if($limit * ($next + 1) < $all) {
		$count_next = $all - $limit * ($next + 1);
		if($count_next > $limit)
			$count_next = $limit;
		$send .=
			'<div class="over5" onclick="_spisok14Next($(this),'.$ELEM['id'].','.($next + 1).')">'.
				'<tt class="db center curP fs14 clr15 pad10">Показать ещё '.$count_next.' запис'._end($count_next, 'ь', 'и', 'ей').'</tt>'.
			'</div>';
	}

	if($IS_SORT)
		$send .= '<script>_spisokSort("'.$ELEM['id'].'")</script>';

	return $send;
}
function _element14_copy_vvv($el, $obj_id) {
	$sql = "SELECT *
			FROM `_block`
			WHERE `obj_name`='spisok'
			  AND `obj_id`=".$el['id']."
			ORDER BY `parent_id`,`y`,`x`";
	if(!$BLK = query_arr($sql))
		return;

	foreach($BLK as $r) {
		$r['obj_id'] = $obj_id;
		_blockInsert($r);
	}

	_blockChildCountSet('spisok', $obj_id);
	_blockAppIdUpdate('spisok', $obj_id);
}

