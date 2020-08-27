<?php
/* ОСОБЕННОСТИ ПЕРЕНОСА:
	1. В клиентах убрано поле Факс. Всего содержится 14 записей. Перенесено в поле Телефон.
*/

function _elem129_comtex($DLG, $POST_CMP) {
	if($DLG['id'] != 129)
		return;
	if(APP_ID != 6)
		jsonError('Нужно находиться в приложении Комтекс');

	foreach($POST_CMP as $i)
		if(!$i)
			jsonError('Нужно поставить все три галочки');


	define('APP_ID_OLD', 3798718);

	_comtex_data_del();
	_comtex_client();
	_comtex_tovar_category();

	jsonSuccess();
}

function _comtex_data_del() {// Удаление всех данных в приложении
	$sql = "DELETE FROM `_global_n`.`_spisok` WHERE `app_id`=".APP_ID." AND `id` NOT IN (0) AND !`cnn_id`";/* todo переделать */
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_image` WHERE `app_id`=".APP_ID." AND `id` NOT IN (0)";
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_attach` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_history` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_history_edited` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_counter_v` WHERE `app_id`=".APP_ID;
	query($sql);

	$sql = "DELETE FROM `_global_n`.`_note` WHERE `app_id`=".APP_ID;
	query($sql);

//	$sql = "DELETE FROM `_global_n`.`_user_access` WHERE `app_id`=".APP_ID;
//	query($sql);

	$sql = "DELETE FROM `_global_n`.`_user_spisok_filter` WHERE `app_id`=".APP_ID;
	query($sql);
}
function _comtex_client() {
	$dialog_id = 1234;

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

		$sql = "SELECT `id` FROM `_user` WHERE vk_id=".$r['viewer_id_add']." LIMIT 1";
		$user_id = query_value($sql);

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

				".$user_id.",
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
	$dialog_id = 1404;

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
