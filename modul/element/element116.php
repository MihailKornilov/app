<?php

/* [116] Глобальные счётчики */
function _counterGlobal($dialog_id, $dlgAct) {
	if(!$DLG = _dialogQuery($dialog_id))
		return;

	if($parent_id = $DLG['dialog_id_parent'])
		$dialog_id = $parent_id;

	$sql = "SELECT *
			FROM `_counter`
			WHERE `spisok_id`=".$dialog_id;
	if(!$arr = DB1::arr($sql))
		return;

	foreach($arr as $counter_id => $r)
		switch($r['type_id']) {
			//количество
			case 3851:
				$sql = "SELECT COUNT(*)
						FROM  "._queryFrom($DLG)."
						WHERE "._queryWhere($DLG).
							_40cond(array(), $r['filter']);
				$count = _num(DB1::value($sql));

				if(!_counterGlobalInsertAccess($counter_id, $count))
					break;

				_counterGlobalInsert($counter_id, $count, $dlgAct);
				break;
			//сумма
			case 3852:
				//элемент, по которому будет расчитываться сумма
				if(!$el = _elemOne($r['sum_elem_id']))
					break;
				if(!$col = $el['col'])
					break;

				$sql = "SELECT SUM(`".$col."`)
						FROM  "._queryFrom($DLG)."
						WHERE "._queryWhere($DLG).
							_40cond(array(), $r['filter']);
				$sum = DB1::value($sql);

				if(!_counterGlobalInsertAccess($counter_id, $sum))
					break;

				_counterGlobalInsert($counter_id, $sum, $dlgAct);
				break;
			default: _debugLog('Неизвестный тип счётчика: '.$r['type_id']);
		}
}
function _counterGlobalInsertAccess($counter_id, $v) {//разрешение на внесение записи о балансе
	$sql = "SELECT *
			FROM `_counter_v`
			WHERE `counter_id`=".$counter_id."
			ORDER BY `id` DESC
			LIMIT 1";
	if(!$cv = DB1::assoc($sql))
		return true;

	if($v != $cv['balans'])
		return true;

	return false;
}
function _counterGlobalInsert($counter_id, $balans, $dlgAct) {//внесение записи счётчика
	$sql = "INSERT INTO `_counter_v` (
				`app_id`,
				`counter_id`,
				`action_type_id`,
				`action_dialog_id`,
				`balans`,
				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$counter_id.",
				".$dlgAct['act'].",
				".$dlgAct['id'].",
				".$balans.",
				".USER_ID."
			)";
	DB1::query($sql);
}

function _counterV($elem_id, $owner_id,  $dlg, $unit_id, $balans, $sum=0, $sumOld=0) {//внесение данных по конкретному счётчику
	//получение последней записи
	$sql = "SELECT *
			FROM `_counter_v`
			WHERE `element_id`=".$elem_id."
			  AND `owner_id`=".$owner_id."
			ORDER BY `id` DESC
			LIMIT 1";
	if($cv = DB1::assoc($sql))
		//если значение совпадает, запись не вносится
		if($balans == $cv['balans'])
			return;

	$sql = "INSERT INTO `_counter_v` (
				`app_id`,
				`element_id`,
				`owner_id`,

				`action_type_id`,
				`action_dialog_id`,
				`action_unit_id`,

				`sum_old`,
				`sum`,
				`balans`,

				`user_id_add`
			) VALUES (
				".APP_ID.",
				".$elem_id.",
				".$owner_id.",

				".$dlg['act'].",
				".$dlg['id'].",
				".$unit_id.",

				".$sumOld.",
				".$sum.",
				".$balans.",

				".USER_ID."
			)";
	DB1::query($sql);
}

function PHP12_counter_v($prm) {
	if(!$u = $prm['unit_get'])
		return _empty('Отсутствуют данные записи');
	if(!$unit_id = _num($u['id']))
		return _empty('Не получен ID записи');


	$act = array(
		1 => 'Внесение',
		2 => 'Изменение',
		3 => 'Удаление'
	);

	$actColor = array(
		1 => 'bg11',
		2 => 'bg-ffc',
		3 => 'bg-fcc'
	);

	$sql = "SELECT *
			FROM `_counter_v`
			WHERE `app_id`=".APP_ID."
			  AND `owner_id`=".$unit_id."
			ORDER BY `id` DESC
			LIMIT 300";
	if(!$spisok = DB1::arr($sql))
		return _empty('Данных нет');


	$send = '<table class="_stab small">'.
				'<tr><th>Действие'.
					'<th>Диалог'.
					'<th>Сумма'.
					'<th>Остаток'.
					'<th>Дата внесения'.
					'<th>Менеджер'.
				'';

	foreach($spisok as $r) {
		$DLG = _dialogQuery($r['action_dialog_id']);
		$send .= '<tr>'.
					'<td class="'.$actColor[$r['action_type_id']].'">'.$act[$r['action_type_id']].
					'<td>'.$DLG['name'].
					'<td class="r">'.$r['sum'].
					'<td class="r">'.$r['balans'].
					'<td>'.$r['dtime_add'].
					'<td>'._user($r['user_id_add'], 'name').
					'';
	}

	$send .= '</table>';


	return $send;
}
