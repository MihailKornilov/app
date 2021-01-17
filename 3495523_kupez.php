<?php
/*
	Комтекс: 3495523 -> 4

	ОСОБЕННОСТИ ПЕРЕНОСА:
		1. Внесения и выводы денег убраны (перенесены в переводы между счетами)
*/

function _elem129_kupez($DLG, $POST_CMP) {
	if($DLG['id'] != 129)
		return;
	if(APP_ID != 4)
		jsonError('Нужно находиться в приложении Купец');

	set_time_limit(300);

	define('APP_ID_OLD', 3495523);

	$key = key($POST_CMP);

	switch($POST_CMP[$key]) {
		//полный перенос
		case 1:
			_kupezDataDel();

			_comtex_user();
			_comtex_user_cnn();

			_kupez_client();

			_kupez_gazeta_nomer();

			_kupez_rubric();
			_kupez_zayav_ob();
			_kupez_zayav_rek();
			_kupez_zayav_rek_image();
			_kupez_zayav_poz();
			_kupez_zayav_poz_image();

			_kupez_zayav_gn();

			_kupez_invoice();
			_kupez_invoice_transfer();
			_kupez_invoice_in_out();
			_kupez_expense_category();
			_kupez_expense();

			break;
		//частичный
		case 2:
			_kupez_zayav_gn();
			break;

		default:
			jsonError('Выберите тип переноса');
	}

	//очищение истории от пустых значений
	$sql = "DELETE FROM _history
			WHERE app_id=".APP_ID."
			  AND unit_id NOT IN (
				SELECT `id` FROM _spisok WHERE app_id=".APP_ID."
			)";
	query($sql);


	global $SQL_QUERY;
	$SQL_QUERY = array();

	jsonSuccess();
}

function _kupezDataDel() {// Удаление всех данных в приложении
	$sql = "DELETE FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `id` NOT IN (
					1526000, /* настройка стоимости длины объявлений */
					1594431,1594432, /* доп.параметры объявлений */
					1599857, /* категория расходов: зарплата */
					1613229,1613230,1613231,1613232, /* настройка стоимости см2 полосы */
					0
				) AND !`cnn_id`";
	query($sql);

	$sql = "DELETE FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `id` NOT IN (
			    0
			  )";
	query($sql);

	$sql = "DELETE FROM `_attach` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_history` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_history_edited` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_counter_v` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_note` WHERE `app_id`=".APP_ID;
	query($sql);

//	$sql = "DELETE FROM `_user_access` WHERE `app_id`=".APP_ID;
//	query($sql);

	$sql = "DELETE FROM `_user_spisok_filter` WHERE `app_id`=".APP_ID;
	query($sql);
}

function _kupez_client() {//Клиенты
	$dialog_id = _comtexSpisokClear(1040);

	_db2();
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				".($r['category_id'] == 13 ? 1 : 0).",
				'".$r['name']."',
				'".$r['phone']."',

				'".$r['fax']."',
				'".$r['adres']."',
				'".$r['inn']."',
				'".$r['kpp']."',
				'".$r['email']."',
				".$r['skidka'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  num,
				  dialog_id,
				
				  num_1,/* category_id 12:частное лицо, 13:организация */
				  txt_1,/* name */
				  txt_2,/* phone */
				
				  txt_7,/* fax */
				  txt_3,/* adres */
				  txt_4,/* inn */
				  txt_5,/* kpp */
				  txt_6,/* email */
				  num_2,/* скидка */
				
				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexHistory($dialog_id);
}

function _kupez_gazeta_nomer() {//номера газет
	$dialog_id = _comtexSpisokClear(1489);

	_db2();
	$sql = "SELECT *
			FROM `_setup_gazeta_nomer`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `general_nomer`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				".$r['general_nomer'].",
				".$r['week_nomer'].",
				'".$r['day_print']."',
				'".$r['day_public']."',
				".$r['polosa_count']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				`id_old`,
				`app_id`,
				`num`,
				`dialog_id`,

				num_2,
				num_1,
				date_1,
				date_2,
				num_3
			) VALUES ".implode(',', $mass);
	query($sql);
}

function _kupez_rubric() {//рубрики объявлений
	$dialog_id = _comtexSpisokClear(1478);

	//категории
	_db2();
	$sql = "SELECT *
			FROM `_setup_rubric`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				'".$r['name']."',

				".$r['sort']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  txt_1,/* name */

				  `sort`
			) VALUES ".implode(',', $mass);
	query($sql);


	$sql = "SELECT `id_old`,`id`
			FROM `_spisok` 
			WHERE `dialog_id`=".$dialog_id;
	$PAR = query_ass($sql);


	//подрубрики
	_db2();
	$sql = "SELECT *
			FROM `_setup_rubric_sub`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				"._num(@$PAR[$r['rubric_id']]).",
				
				'".$r['name']."',

				".$r['sort']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				  `parent_id`,
				
				  txt_1,/* name */

				  `sort`
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_zayav_ob() {//заявки-объявления
	$dialog_id = _comtexSpisokClear(1477);

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1478
			  AND !`parent_id`";
	$RUB = query_ass($sql);

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1478
			  AND `parent_id`";
	$RUBSUB = query_ass($sql);

	$x = 1000;

	for($n = 0; $n < $x; $n++) {
		_db2();
		$sql = "SELECT *
				FROM _zayav
				WHERE `app_id`=".APP_ID_OLD."
				  AND `service_id`=8
				ORDER BY `id`
				LIMIT ".($n*$x).",".$x;
		if(!$arr = query_arr($sql))
			return;

		$mass = array();
		foreach($arr as $zayav_id => $r) {
			if($sub_id = $r['rubric_id_sub'])
				$rubric = $RUBSUB[$sub_id];
			else
				$rubric = $RUB[$r['rubric_id']];

			$mass[] = "(
					".$zayav_id.",
					".APP_ID.",
					".$r['nomer'].",
					".$dialog_id.",
					
					"._comtexAss(1040, $r['client_id']).",/* клиент */
					".$rubric.",
					'".addslashes($r['about'])."',
					'".addslashes($r['phone'])."',
					'".addslashes($r['adres'])."',
					".$r['sum_manual'].",
					".$r['sum_cost'].",
	
					"._comtexUserId($r).",
					'".$r['dtime_add']."',
					".$r['deleted']."
				)";
		}

		$sql = "INSERT INTO `_spisok` (
					  `id_old`,
					  `app_id`,
					  `num`,
					  `dialog_id`,
					
					  num_1,
					  num_2,
					  txt_1,
					  txt_2,
					  txt_3,
					  num_4,
					  sum_1,
	
					  user_id_add,
					  dtime_add,
					  deleted
				) VALUES ".implode(',', $mass);
		query($sql);
	}
}
function _kupez_zayav_rek() {//заявки-реклама
	$dialog_id = _comtexSpisokClear(1486);

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=9
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $zayav_id => $r) {
		$mass[] = "(
				".$zayav_id.",
				".APP_ID.",
				".$r['nomer'].",
				".$dialog_id.",
				
				"._comtexAss(1040, $r['client_id']).",/* клиент */

				".$r['size_x'].",
				".$r['size_y'].",
				".round($r['size_x']*$r['size_y']).",
				".$r['skidka'].",

				".$r['sum_manual'].",
				".$r['sum_cost'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,

				  sum_2,
				  sum_3,
				  sum_4,
				  num_2,

				  num_3,
				  sum_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'Клиенты');
}
function _kupez_zayav_rek_image() {//изображения к рекламе
	$sql = "DELETE FROM `_image` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "UPDATE `_spisok`
			SET `txt_1`=''
			WHERE `dialog_id`=1486";
	query($sql);

	_db2();
	$sql = "SELECT *
			FROM `_image`
			WHERE `app_id`=".APP_ID_OLD."
			  AND !`deleted`
			  AND `unit_name`='zayav'
			  AND `unit_id` IN (
			    SELECT `id` FROM `_zayav` WHERE `service_id`=9
			  )
			ORDER BY `unit_id`,`sort`";
	if(!$arr = query_arr($sql))
		return;

	$ASS = array();
	foreach($arr as $r) {
		if(!$zayav_id = _comtexAss(1486, $r['unit_id']))
			continue;
		if(!isset($ASS[$zayav_id]))
			$ASS[$zayav_id] = array();

		$sql = "INSERT INTO `_image` (
					`app_id`,
	
					server_id,
					max_name,
					max_x,
					max_y,
					max_size,
					80_name,
					80_x,
					80_y,
					80_size,
	
					sort,
					user_id_add,
					dtime_add
				) VALUES (
					".APP_ID.",

					"._imageServer($r['path']).",
				    '".$r['big_name']."',
				    ".$r['big_x'].",
				    ".$r['big_y'].",
				    ".$r['big_size'].",
				    '".$r['small_name']."',
				    ".$r['small_x'].",
				    ".$r['small_y'].",
				    ".$r['small_size'].",

					".$r['sort'].",
					"._comtexUserId($r).",
					'".$r['dtime_add']."'
				)";
		$ASS[$zayav_id][] = query_id($sql);
	}

	$mass = array();
	foreach($ASS as $zayav_id => $ids)
		$mass[] = "(".$zayav_id.",'".implode(',', $ids)."')";

	$sql = "INSERT INTO `_spisok` (
				`id`,
				`txt_1`
			) VALUES ".implode(',', $mass)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`)";
	query($sql);
}
function _kupez_zayav_poz() {//заявки-поздравления
	$dialog_id = _comtexSpisokClear(1487);

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=10
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $zayav_id => $r) {
		$mass[] = "(
				".$zayav_id.",
				".APP_ID.",
				".$r['nomer'].",
				".$dialog_id.",
				
				"._comtexAss(1040, $r['client_id']).",/* клиент */

				".$r['sum_cost'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,

				  sum_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_zayav_poz_image() {//изображения к рекламе
	$sql = "UPDATE `_spisok`
			SET `txt_1`=''
			WHERE `dialog_id`=1487";
	query($sql);

	_db2();
	$sql = "SELECT *
			FROM `_image`
			WHERE `app_id`=".APP_ID_OLD."
			  AND !`deleted`
			  AND `unit_name`='zayav'
			  AND `unit_id` IN (
			    SELECT `id` FROM `_zayav` WHERE `service_id`=10
			  )
			ORDER BY `unit_id`,`sort`";
	if(!$arr = query_arr($sql))
		return;

	$ASS = array();
	foreach($arr as $r) {
		if(!$zayav_id = _comtexAss(1487, $r['unit_id']))
			continue;
		if(!isset($ASS[$zayav_id]))
			$ASS[$zayav_id] = array();

		$sql = "INSERT INTO `_image` (
					`app_id`,
	
					server_id,
					max_name,
					max_x,
					max_y,
					max_size,
					80_name,
					80_x,
					80_y,
					80_size,
	
					sort,
					user_id_add,
					dtime_add
				) VALUES (
					".APP_ID.",

					"._imageServer($r['path']).",
				    '".$r['big_name']."',
				    ".$r['big_x'].",
				    ".$r['big_y'].",
				    ".$r['big_size'].",
				    '".$r['small_name']."',
				    ".$r['small_x'].",
				    ".$r['small_y'].",
				    ".$r['small_size'].",

					".$r['sort'].",
					"._comtexUserId($r).",
					'".$r['dtime_add']."'
				)";
		$ASS[$zayav_id][] = query_id($sql);
	}

	$mass = array();
	foreach($ASS as $zayav_id => $ids)
		$mass[] = "(".$zayav_id.",'".implode(',', $ids)."')";

	$sql = "INSERT INTO `_spisok` (
				`id`,
				`txt_1`
			) VALUES ".implode(',', $mass)."
			ON DUPLICATE KEY UPDATE
				`txt_1`=VALUES(`txt_1`)";
	query($sql);
}

function _kupez_zayav_gn() {//номера выпусков в заявках
	$dialog_id = _comtexSpisokClear(1491);

	_kupez_zayav_gnService($dialog_id, 8, 'num_2', 1477);//объявления
	_kupez_zayav_gnService($dialog_id, 9, 'num_3', 1486);//реклама
	_kupez_zayav_gnService($dialog_id, 10, 'num_4', 1487);//поздравления

	_comtexErrMsg($dialog_id, 'num_5', 'номер газеты');
}
function _kupez_zayav_gnService($dialog_id, $service_id, $col, $zayav_dlg_id) {//номера выпусков для конкретного типа заявок
	$x = 1000;

	//ассоциации доп.параметров
	$DOP = array();
	switch($service_id) {
		case 8:
			$DOP = array(
				1 => 1594431,
				2 => 1594432
			);
			break;
		case 9:
		case 10:
		case 11:
			$DOP = array(
				1 => 1613229,
				2 => 1613232,
				3 => 1613230,
				4 => 1613231
			);
			break;
	}

	for($n = 0; $n < $x; $n++) {
		_db2();
		$sql = "SELECT *
				FROM `_zayav_gazeta_nomer`
				WHERE `app_id`=".APP_ID_OLD."
				  AND `zayav_id` IN (
							SELECT `id`
							FROM `_zayav`
							WHERE `service_id`=".$service_id."
							ORDER BY `id`
						)
				ORDER BY `id`
				LIMIT ".($n*$x).",".$x;
		if(!$arr = query_arr($sql))
			break;

		$mass = array();
		foreach($arr as $id => $r) {
			$mass[] = "(
					".$id.",
					".APP_ID.",
					".$id.",
					".$dialog_id.",
					
					"._comtexAss(1040, $r['client_id']).",/* клиент */
					"._comtexAss($zayav_dlg_id, $r['zayav_id']).",/* заявка */
					"._comtexAss(1489, $r['gazeta_nomer_id']).",/* номер выхода */
					"._num(@$DOP[$r['dop']]).",
					".$r['polosa'].",
					".$r['cena'].",
					".$r['skidka'].",
					".$r['skidka_sum'].",				

					"._comtexAss($zayav_dlg_id, $r['zayav_id'], 'user_id_add').",
					'"._comtexAss($zayav_dlg_id, $r['zayav_id'], 'dtime_add')."'
				)";
		}

		$sql = "INSERT INTO `_spisok` (
					`id_old`,
					`app_id`,
					`num`,
					`dialog_id`,
	
					`num_1`,    /* клиент */
					`".$col."`, /* заявка */
					`num_5`,    /* номер выхода */
					`num_6`,    /* доп.параметр */
					`num_7`,    /* номер полосы */
					`sum_16`,   /* стоимость */
					`num_8`,    /* скидка */
					`sum_17`,   /* сумма скидки */
					
					`user_id_add`,
				    `dtime_add`					
				) VALUES ".implode(',', $mass);
		query($sql);
	}
}

function _kupez_invoice() {//Расчётные счета
	$dialog_id = _comtexSpisokClear(1483);

	_db2();
	$sql = "SELECT *
			FROM `_money_invoice`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				'".$r['name']."',
				'".$r['about']."',
				".$r['start'].",

				".$r['sort'].",
				".$r['deleted']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  num,
				  dialog_id,

				  txt_1,/* name */
				  txt_2,/* about */
				  sum_1,/* start */

				  sort,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_invoice_transfer() {//переводы между счетами
	$dialog_id = _comtexSpisokClear(1488);

	_db2();
	$sql = "SELECT *
			FROM _money_invoice_transfer
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexAss(1483, $r['invoice_id_from']).", /* расчётные счета */
				"._comtexAss(1483, $r['invoice_id_to']).", /* расчётные счета */
				".$r['sum'].",
				'".$r['about']."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  num_2,
				  sum_1,
				  txt_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_invoice_in_out() {//внесения и выводы (перенос в переводы)
	$dialog_id = 1488;

	_db2();
	$sql = "SELECT *
			FROM _money_invoice_out
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	//получение id счёта "На телефон Юре"
	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=1483
			  AND `txt_1`='На телефон Юре'";
	if(!$inTo = query_value($sql))
		echo 'Не получен id счёта "На телефон Юре"';

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexAss(1483, $r['invoice_id']).", /* c расчётного счета */
				".$inTo.", /* на расчётный счёт */
				".$r['sum'].",
				'".$r['about']."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  num_2,
				  sum_1,
				  txt_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_expense_category() {//категории расходов
	$dialog_id = _comtexSpisokClear(1482);

	//категории
	_db2();
	$sql = "SELECT *
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				'".$r['name']."',
				'".$r['about']."',

				".$r['sort']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  num,
				  dialog_id,

				  txt_1,/* name */
				  txt_2,/* about */

				  sort
			) VALUES ".implode(',', $mass);
	query($sql);


	//подкатегории
	_db2();
	$sql = "SELECT *
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$sql = "SELECT `id`
				FROM `_spisok`
				WHERE `dialog_id`=".$dialog_id."
				  AND `id_old`=".$r['category_id']."
				LIMIT 1";
		$cat_id = query_value($sql);

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$cat_id.",
				".$id.",
				".$dialog_id.",
				
				'".$r['name']."'
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `parent_id`,
				  num,
				  dialog_id,

				  txt_1/* name */
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _kupez_expense() {//расходы
	$dialog_id = _comtexSpisokClear(1484);

	_db2();
	$sql = "SELECT *
			FROM _money_expense
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		if($r['worker_id']) {
			$cat = 1599857;
		} else
			if($r['category_sub_id'])
				$cat = _comtexAss(1482, $r['category_sub_id'], 'id', 'AND `parent_id`');
			else
				$cat = _comtexAss(1482, $r['category_id'], 'id', 'AND !`parent_id`');


		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				".$cat.",
				"._comtexAss(1483, $r['invoice_id']).", /* расчётные счета */
				"._comtexUserId($r, 'worker_id').",
				".$r['sum'].",
				'".$r['about']."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted']."
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  num_2,
				  num_3,
				  sum_1,
				  txt_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_2', 'счета');
}


























