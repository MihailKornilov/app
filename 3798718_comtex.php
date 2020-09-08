<?php
/*
	Комтекс: 3798718 -> 6

	ОСОБЕННОСТИ ПЕРЕНОСА:
	1. В клиентах убрано поле Факс. Всего содержится 14 записей. Перенесено в поле Телефон.
*/

function _elem129_comtex($DLG, $POST_CMP) {
	if($DLG['id'] != 129)
		return;
	if(APP_ID != 6)
		jsonError('Нужно находиться в приложении Комтекс');

	define('APP_ID_OLD', 3798718);

	$key = key($POST_CMP);

	switch($POST_CMP[$key]) {
		//полный перенос
		case 1:
			_comtexDataDel();

			_comtex_client();

			_comtex_tovar_category();
			_comtex_tovar();
			_comtex_zayav_place();
			_comtex_zayav_equip();
			_comtex_zayav_status();

		//частичный
		case 2:
			_comtex_zayav();
			_comtex_zayav_tovar();
			break;

		default:
			jsonError('Выберите тип переноса');
	}

	jsonSuccess();
}

function _comtexUserId($r) {//получение id пользователя, который вносил запись
	global $USERVK;

	if(!isset($USERVK)) {
		$sql = "SELECT `vk_id`,`id`
				FROM `_user`
				WHERE `vk_id`";
		$USERVK = query_ass($sql);
	}

	if(!isset($USERVK[$r['viewer_id_add']]))
		return 0;

	return _num($USERVK[$r['viewer_id_add']]);
}
function _comtexSpisokClear($dialog_id) {//очистка списка по конкретному диалогу
	$sql = "DELETE FROM `_spisok` WHERE `dialog_id`=".$dialog_id;
	query($sql);
	return $dialog_id;
}
function _comtexDataDel() {// Удаление всех данных в приложении
	$sql = "DELETE FROM `_spisok` WHERE `app_id`=".APP_ID." AND `id` NOT IN (0) AND !`cnn_id`";/* todo переделать */
	query($sql);

	$sql = "DELETE FROM `_image` WHERE `app_id`=".APP_ID." AND `id` NOT IN (0)";
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

function _comtex_client() {//Клиенты
	$dialog_id = _comtexSpisokClear(1234);

	_db2();
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		if($r['fax'])
			$r['phone'] .= ', fax: '.$r['fax'];

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				".($r['category_id'] == 2 ? 1 : 0).",
				'".$r['name']."',
				'".$r['phone']."',

				'".$r['adres']."',
				'".$r['inn']."',
				'".$r['kpp']."',
				'".$r['email']."',

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
				
				  num_1,/* category_id 1:частное лицо, 2:организация */
				  txt_1,/* name */
				  txt_2,/* phone */
				
				  txt_3,/* adres */
				  txt_4,/* inn */
				  txt_5,/* kpp */
				  txt_6,/* email */
				
				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}

function _comtex_tovar_category() {//категории товаров
	$dialog_id = _comtexSpisokClear(1404);


	//категории
	_db2();
	$sql = "SELECT *
			FROM `_tovar_category`
			WHERE `app_id`=".APP_ID_OLD."
			  AND !`parent_id`
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




	//подкатегории
	_db2();
	$sql = "SELECT *
			FROM `_tovar_category`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `parent_id`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$sql = "SELECT `id`
				FROM `_spisok` 
				WHERE `id_old`=".$r['parent_id']."
				  AND `dialog_id`=".$dialog_id;
		$parent_id = query_value($sql);

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				".$parent_id.",
				
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
function _comtex_tovar() {//товары
	$dialog_id = _comtexSpisokClear(1403);

	_db2();
	$sql = "SELECT
			    t.id,
			
			    tb.category_id,
			    t.name,
			    t.about,
			    tb.articul,
			    IFNULL(tb.sum_buy,0) sum_buy,
			    IFNULL(tb.sum_sell,0) sum_sell,
			
			    viewer_id_add,
			    dtime_add
			  FROM _tovar t,
			       _tovar_bind tb
			  WHERE `t`.`id`=`tb`.`tovar_id`
			    AND `tb`.`app_id`=".APP_ID_OLD;
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$sql = "SELECT id
				from _spisok
				WHERE `dialog_id`=1404
				  AND `id_old`=".$r['category_id']."
				LIMIT 1";
		$category_id = query_value($sql);

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				".$category_id.",
				'".$r['name']."',
				'".$r['about']."',
				'".$r['articul']."',

				".$r['sum_buy'].",
				".$r['sum_sell'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  num,
				  dialog_id,
				
				  num_1,/* category_id */
				  txt_1,/* name */
				  txt_2,/* about */
				  txt_3,/* articul */

				  sum_1,
				  sum_2,
				
				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);
}

function _comtex_zayav_place() {//местонахождения устройств
	$dialog_id = _comtexSpisokClear(1406);

	_db2();
	$sql = "SELECT *
			FROM `_zayav_tovar_place`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	$mass[] = "(
			1,
			".APP_ID.",
			1,
			".$dialog_id.",
			'в сервисном центре',
			1
		)";
	$mass[] = "(
			2,
			".APP_ID.",
			2,
			".$dialog_id.",
			'у клиента',
			2
		)";
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				'".$r['place']."',

				".$r['id']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  txt_1,

				  `sort`
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_zayav_equip() {//комплектации оборудования
	$dialog_id = _comtexSpisokClear(1407);

	_db2();
	$sql = "SELECT
			  e.id,
			  e.name,
			  b.sort
		  FROM _tovar_equip e,
		       _tovar_equip_bind b
		  WHERE e.id=b.equip_id
		    AND b.app_id=".APP_ID_OLD."
		  GROUP BY e.id
		  ORDER BY e.id";
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
				
				  txt_1,
				  `sort`
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_zayav_status() {//статусы заявок-оборудования
	$dialog_id = _comtexSpisokClear(1408);

	_db2();
	$sql = "SELECT
			    id,
			
			    `name`,
			    `about`,
			    CONCAT('#',`color`) `color`,
			    `default`,
			
			    `sort`
			  FROM _zayav_status
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
				'".$r['color']."',
				".$r['default'].",

				".$r['sort']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  txt_1,
				  txt_2,
				  txt_3,
				  num_1,

				  `sort`
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_zayav() {//заявки-оборудование
	$dialog_id = _comtexSpisokClear(1402);

	_db2();
	$sql = "SELECT *
			FROM _global._zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=5
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1234";
	$CLIENT = query_ass($sql);


	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1408";
	$STATUS = query_ass($sql);


	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1407";
	$EQUIP = query_ass($sql);


	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1406";
	$PLACE = query_ass($sql);



	$mass = array();
	foreach($arr as $zayav_id => $r) {
		$eq = '';
		if($ids = _ids($r['tovar_equip_ids'], 'arr')) {
			$eqArr = array();
			foreach($ids as $id)
				if(isset($EQUIP[$id]))
					$eqArr[] = $EQUIP[$id];
			if($eqArr)
				$eq = '0,'.implode(',', $eqArr).',0';
		}
		$mass[] = "(
				".$zayav_id.",
				".APP_ID.",
				".$r['nomer'].",
				".$dialog_id.",
				
				"._num(@$CLIENT[$r['client_id']]).",
				"._num(@$STATUS[$r['status_id']]).",
				'".$eq."',
				'".$r['serial']."',
				"._num(@$PLACE[$r['tovar_place_id']]).",
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
				  num_5,
				  txt_3,
				  txt_1,
				  num_4,
				  num_2,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_zayav_tovar() {//прикрепление товаров к заявкам-оборудование
	$sql = "DELETE FROM `_spisok` WHERE !`dialog_id`";
	query($sql);

	//товары в заявках
	_db2();
	$sql = "SELECT *
			  FROM _zayav_tovar
			  WHERE `app_id`=".APP_ID_OLD."
			  ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;


	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1402";
	$ZAYAV = query_ass($sql);

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1403";
	$TOVAR = query_ass($sql);

	$mass = array();
	foreach($arr as $id => $r) {

		if(!isset($ZAYAV[$r['zayav_id']]))
			jsonError($r['zayav_id']);


		$mass[] = "(
			"._num(@$ZAYAV[$r['zayav_id']]).",
			"._num(@$TOVAR[$r['tovar_id']])."
		)";
	}

	$sql = "INSERT INTO _spisok (
			  id,
			  num_3
			) VALUES ".implode(',', $mass)."
			ON DUPLICATE KEY UPDATE
			  `num_3`=VALUES(`num_3`)";
	query($sql);



}


