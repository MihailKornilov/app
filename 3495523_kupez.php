<?php
/*
	Комтекс: 3495523 -> 4

	ОСОБЕННОСТИ ПЕРЕНОСА:
		1.
*/

function _elem129_kupez($DLG, $POST_CMP) {
	if($DLG['id'] != 129)
		return;
	if(APP_ID != 4)
		jsonError('Нужно находиться в приложении Купец');

	define('APP_ID_OLD', 3495523);

	$key = key($POST_CMP);

	switch($POST_CMP[$key]) {
		//полный перенос
		case 1:
			_kupezDataDel();

			_comtex_user();
			_comtex_user_cnn();

			_kupez_client();

			_kupez_rubric();
			_kupez_zayav_ob();
			break;
		//частичный
		case 2:
			_kupez_invoice();
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
					1594431,1594432 /* доп.параметры объявлений */
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


























