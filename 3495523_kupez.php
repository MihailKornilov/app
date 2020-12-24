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

			break;
		//частичный
		case 2:
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





























