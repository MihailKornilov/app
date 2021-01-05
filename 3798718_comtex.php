<?php
/*
	Комтекс: 3798718 -> 6

	ОСОБЕННОСТИ ПЕРЕНОСА:
	1. В клиентах убрано поле Факс. Всего содержится 14 записей. Перенесено в поле Телефон.
	2. Картриджи перенесены в Товары
	3. Удалены ознакомительные счета (всего было 3)
	4. Отображение начальной проблемы в заявках-оборудование
	5. Зарплата в деньгах отображается отдельно от общих расходов
	6. В деньгах убраны внесения и выводы
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
			jsonError('Перенос завершён');
			_comtexDataDel();

			_comtex_user_name_correct();

			_comtex_user();
			_comtex_user_cnn();

			_comtex_client();

			_comtex_tovar_category();
			_comtex_tovar();
			_comtex_tovar_avai();
			_comtex_tovar_fail();
			_comtex_tovar_cartridge();

			_comtex_zayav_place();
			_comtex_zayav_equip();
			_comtex_zayav_status();
			_comtex_zayav();
			_comtex_zayav_tovar();
			_comtex_zayav_cartridge();
			_comtex_zayav_vyzov();
			_comtex_zayav_expense_worker();
			_comtex_zayav_expense_other();
			_comtex_zayav_expense_tovar();
			_comtex_zayav_note();
			_comtex_zayav_note_comment();

			_comtex_accrual();
			_comtex_invoice();
			_comtex_invoice_transfer();
			_comtex_refund();

			_comtex_expense_category();
			_comtex_expense();
			_comtex_worker_zp();
			_comtex_salary_accrual();
			_comtex_salary_deduct();

			_comtex_remind_status();
			_comtex_remind_reason();
			_comtex_remind();
			_comtex_remind_action();

			_comtex_schet_pay();
			_comtex_cartridge_in_zayav();

			_comtex_income();
			_comtex_devices_lost();
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

function _comtexUserId($r, $i='viewer_id_add') {//получение id пользователя, который вносил запись
	global $USERVK;

	if(!isset($USERVK)) {
		$sql = "SELECT `vk_id`,`id`
				FROM `_user`
				WHERE `vk_id`";
		$USERVK = query_ass($sql);
	}

	if(!isset($USERVK[$r[$i]]))
		return 0;

	return _num($USERVK[$r[$i]]);
}
function _comtexErrMsg($dialog_id, $col,  $about) {
	$sql = "SELECT COUNT(*)
			FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`".$col."`";
	if($c = query_value($sql))
		echo $dialog_id.': '.$col.' - '.$about.' ('.$c.')'."\n\n";
}
function _comtexSpisokClear($dialog_id) {//очистка списка по конкретному диалогу
	$sql = "DELETE FROM `_spisok` WHERE `dialog_id`=".$dialog_id;
	query($sql);
	return $dialog_id;
}
function _comtexAss($dialog_id, $id, $return='id', $and='') {//получение нового id по старому
	global $COMTEX_ASS;

	$key = md5($dialog_id.$return.$and);

	if(!isset($COMTEX_ASS[$key])) {
		$sql = "SELECT `id_old`,`".$return."`
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `dialog_id`=".$dialog_id."
				  AND `id_old`
				".$and;
		$COMTEX_ASS[$key] = query_ass($sql);
	}

	if(!isset($COMTEX_ASS[$key][$id]))
		return 0;

	return $COMTEX_ASS[$key][$id];
}
function _comtexHistory($dialog_id) {//История по конкретному диалогу
	$sql = "DELETE FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `type_id`=1
			  AND `dialog_id`=".$dialog_id;
	query($sql);

	$sql = "SELECT *
			FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".APP_ID.",
				1,
				".$dialog_id.",
				".$id.",
				".$r['user_id_add'].",
				'".$r['dtime_add']."'
		)";
	}

	$sql = "INSERT INTO `_history` (
				app_id,
				type_id,
				dialog_id,
				unit_id,
				user_id_add,
				dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	//активирование истории
	$sql = "UPDATE `_history`
			SET `active`=1
			WHERE `dialog_id`=".$dialog_id;
	query($sql);
}
function _comtexDataDel() {// Удаление всех данных в приложении
	$sql = "DELETE FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `id` NOT IN (
					1352491,/* Данные организации */
					1352542,/* Реквизиты банка */
					1246258,1246259,1246260 /* Статусы напоминаний */
				) AND !`cnn_id`";
	query($sql);

	$sql = "DELETE FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `id` NOT IN (
			    51665,51666 /* Иконки для печати документов */
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

function _comtex_user_name_correct() {//восстановление имён у пользователей DELETED
	$sql = "SELECT `vk_id`,`id`
			FROM `_user`
			WHERE `i`='DELETED'
			  AND `vk_id`";
	$ass = query_ass($sql);

	_db2();
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `viewer_id` IN ("._idsGet($ass, 'key').")
			  AND `first_name` NOT IN ('DELETED', 'onpay')
			GROUP BY `viewer_id`";
	$vk = array();
	foreach(query_arr($sql) as $r)
		$vk[$r['viewer_id']] = $r;

	foreach($ass as $vk_id => $id) {
		if(!$u = @$vk[$vk_id])
			continue;
		$sql = "UPDATE `_user`
				SET `f`='".$u['last_name']."',
					`i`='".$u['first_name']."',
					`o`='".$u['middle_name']."'
				WHERE `id`=".$id;
		query($sql);
	}
}

function _comtex_user() {//пользователи приложения

	//колонки, по которым будут отобраны id пользователей
	$UF = array(
		'viewer_id_add' => 1,
		'viewer_id_del' => 1,
		'worker_id' => 1,
		'executer_id' => 1,
		'v1_viewer_id' => 1
	);

	//получение всех таблиц
	_db2();
	$sql = "SHOW TABLES";
	$arr = query_array($sql);

	$UIDS = array();
	foreach($arr as $r) {
		$key = key($r);
		$table = $r[$key];

		//получение таблиц с колонками app_id
		_db2();
		$sql = "DESCRIBE `".$table."`";
		$FLD = query_array($sql);
		foreach($FLD as $rr)
			if($rr['Field'] == 'app_id') {
				foreach($FLD as $F)
					if(isset($UF[$F['Field']])) {
						_db2();
						$sql = "SELECT DISTINCT(`".$F['Field']."`) `id`
								FROM `".$table."`
								WHERE `app_id`=".APP_ID_OLD."
								  AND `".$F['Field']."`";
						if($ids = query_ids($sql)) {
							$UIDS = array_merge($UIDS, _ids($ids, 'arr'));
							$UIDS = array_unique($UIDS);
						}
					}
				break;
			}
	}

	_db2();
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `worker`
			  AND `viewer_id`";
	if($ids = query_ids($sql)) {
		$UIDS = array_merge($UIDS, _ids($ids, 'arr'));
		$UIDS = array_unique($UIDS);
	}

	$UIDS = implode(',', $UIDS);

	//новые пользователи, существующие в базе
	$sql = "SELECT DISTINCT `vk_id` FROM `_user` WHERE `vk_id`";
	$uNew = query_ids($sql);

	_db2();
	$sql = "SELECT
	            `id`,
				`viewer_id`,
				IFNULL(last_name,'') `last_name`,
				IFNULL(first_name,'') `first_name`,
				IFNULL(middle_name,'') `middle_name`,
				`sex`,
				`dtime_add`,
				`last_seen`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `viewer_id` NOT IN (".$uNew.")
			  AND `viewer_id` IN (".$UIDS.")";
	if(!$users = query_arr($sql))
		return;

	$mass = array();
	foreach($users as $id => $r) {
		$mass[] = "(
				".$id.",
				".$r['viewer_id'].",
				'".$r['last_name']."',
				'".$r['first_name']."',
				'".$r['middle_name']."',
				".$r['sex'].",
				'".$r['dtime_add']."',
				'".$r['last_seen']."'
			)";
	}

	$sql = "INSERT INTO `_user` (
			  id_old,
			  vk_id,
			  f,
			  i,
			  o,
			  pol,
			  dtime_create,
			  dtime_last
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_user_cnn() {//привязка пользователей к приложению
	$sql = "DELETE FROM `_spisok`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=111";
	query($sql);

	$sql = "DELETE FROM `_user_access`
			WHERE `app_id`=".APP_ID;
	query($sql);

	_db2();
	$sql = "SELECT
				`viewer_id` `id`,
				`post`,
				`dtime_add`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `worker`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;


	//Отображение в списке зп сотрудников
	$showZP = array();
	_db2();
	$sql = "SELECT *
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `key`='RULE_SALARY_SHOW'
			  AND `value`";
	foreach(query_arr($sql) as $r)
		if($user_id = _comtexUserId($r, 'viewer_id'))
			$showZP[$user_id] = 1;

	$mass = array();
	$rule = array();
	foreach($arr as $id => $r) {
		if(!$cnn_id = _comtexUserId($r, 'id'))
			continue;
		$mass[] = "(
				".APP_ID.",
				".$id.",
				111,
				
				".$cnn_id.",
				'".$r['post']."',
				"._num(@$showZP[$cnn_id]).",

				1,
				'".$r['dtime_add']."'
			)";
		$rule[] = "(
				".APP_ID.",
				".$cnn_id."
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				app_id,
				num,
				dialog_id,
				
				cnn_id,     /* _user.id */
				txt_1,      /* post */
				num_2,
				
				user_id_add,
				dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	//Права доступа для пользователей
	$sql = "INSERT INTO `_user_access` (
			  app_id,
			  user_id
			) VALUES ".implode(',', $rule);
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

	_comtexHistory($dialog_id);
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
function _comtex_tovar_avai() {//наличие товара
	$dialog_id = _comtexSpisokClear(1425);

	_db2();
	$sql = "SELECT *
			FROM _tovar_move
			WHERE `app_id`=".APP_ID_OLD."
			  AND `type_id`=1
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
				
				"._comtexAss(1403, $r['tovar_id']).", /* товары */
				".$r['count'].",
				".$r['cena'].",
				'".$r['about']."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  sum_1,
				  sum_2,
				  txt_1,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexHistory($dialog_id);

//	_comtexErrMsg($dialog_id, 'num_1', 'товары');
	$sql = "DELETE FROM `_spisok`
			WHERE dialog_id=".$dialog_id."
			  AND !num_1";
	query($sql);

}
function _comtex_tovar_fail() {//списание товаров
	$dialog_id = _comtexSpisokClear(1427);

	_db2();
	$sql = "SELECT *
			FROM _tovar_move
			WHERE `app_id`=".APP_ID_OLD."
			  AND `type_id` IN (4,5,6)
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
				
				"._comtexAss(1403, $r['tovar_id']).", /* товары */
				".$r['count'].",
				'".$r['about']."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  sum_1,
				  txt_1,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexHistory($dialog_id);

	$sql = "DELETE FROM `_spisok`
			WHERE dialog_id=".$dialog_id."
			  AND !num_1";
	query($sql);

}
function _comtex_tovar_cartridge() {//картриджи
	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=1404
			  AND `txt_1`='Картриджи'
			LIMIT 1";
	if(!$parent_id = query_value($sql))
		jsonError('Картриджи: не получен id категории');

	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=1404
			  AND `txt_1`='Лазерные'
			LIMIT 1";
	if(!$lazerId = query_value($sql)) {
		$sql = "INSERT INTO _spisok (app_id,dialog_id,parent_id,txt_1,sort,user_id_add) VALUES (".APP_ID.",1404,".$parent_id.",'Лазерные',1000,1)";
		$lazerId = query_id($sql);
	}

	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=1404
			  AND `txt_1`='Струйные'
			LIMIT 1";
	if(!$struyId = query_value($sql)) {
		$sql = "INSERT INTO _spisok (app_id,dialog_id,parent_id,txt_1,sort,user_id_add) VALUES (".APP_ID.",1404,".$parent_id.",'Струйные',1001,1)";
		$struyId = query_id($sql);
	}


	$dialog_id = 1403;//товары

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND `num_1` IN (".$lazerId.",".$struyId.")";
	query($sql);

	_db2();
	$sql = "SELECT *
			FROM `_setup_cartridge`
			ORDER BY `name`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT MAX(`txt_3`)
			FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			LIMIT 1";
	$articul = _num(query_value($sql)) + 1;

	$mass = array();
	$sort = 0;
	foreach($arr as $id => $r) {
		$catId = $r['type_id'] == 1 ? $lazerId : $struyId;
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				".$catId.",
				'Картридж ".$r['name']."',
				'0".($articul++)."',
				".$r['cost_filling'].",
				".$r['cost_restore'].",
				".$r['cost_chip'].",

				".($sort++).",
				1
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  txt_1,
				  txt_3,
				  num_2,
				  num_3,
				  num_4,

				  `sort`,
				  `user_id_add`
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

	//установка статуса по умолчанию всем видам заявок
	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND `num_1`
			LIMIT 1";
	if($def = query_value($sql)) {
		$sql = "UPDATE `_element`
				SET `num_6`=".$def."
				WHERE `id` IN (15773,16732,16745)";
		query($sql);
	}
}
function _comtex_zayav() {//заявки-оборудование
	$dialog_id = _comtexSpisokClear(1402);

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=5
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

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
				
				"._comtexAss(1234, $r['client_id']).",
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

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
			"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
			"._comtexAss(1403, $r['tovar_id'])."  /* товары */
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
function _comtex_zayav_cartridge() {//заявки-картриджи
	$dialog_id = _comtexSpisokClear(1429);

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=6
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1408";
	$STATUS = query_ass($sql);

	$mass = array();
	foreach($arr as $zayav_id => $r) {
		$mass[] = "(
				".$zayav_id.",
				".APP_ID.",
				".$r['nomer'].",
				".$dialog_id.",
				
				"._comtexAss(1234, $r['client_id']).",
				".$r['count'].",
				".$r['pay_type'].",
				"._num(@$STATUS[$r['status_id']]).",

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
				  num_4,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'клиенты');
	_comtexErrMsg($dialog_id, 'num_4', 'статус');
}
function _comtex_zayav_vyzov() {//заявки-вызов специалиста
	$dialog_id = _comtexSpisokClear(1447);

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `app_id`=".APP_ID_OLD."
			  AND `service_id`=7
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1408";
	$STATUS = query_ass($sql);

	$mass = array();
	foreach($arr as $zayav_id => $r) {
		$mass[] = "(
				".$zayav_id.",
				".APP_ID.",
				".$r['nomer'].",
				".$dialog_id.",
				
				"._comtexAss(1234, $r['client_id']).",
				"._num(@$STATUS[$r['status_id']]).",
				'".addslashes($r['adres'])."',
				'".addslashes($r['about'])."',
				'".$r['srok']."',

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
				  date_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'клиенты');
	_comtexErrMsg($dialog_id, 'num_2', 'статус');
}
function _comtex_zayav_expense_worker() {//начисления зп сотрудникам в заявке
	$dialog_id = _comtexSpisokClear(1444);

	_db2();
	$sql = "SELECT *
			FROM _zayav_expense
			WHERE `app_id`=".APP_ID_OLD."
			  AND `category_id`=14
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mon = $r['year'].'-'.($r['mon'] < 10 ? '0' : '').$r['mon'];
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexUserId($r, 'worker_id').",
				"._comtexAss(1447, $r['zayav_id']).", /* заявки-вызов специалиста */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */
				".$r['sum'].",
				'".$mon."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
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
				  num_4,
				  sum_1,
				  txt_1,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

//	_comtexErrMsg($dialog_id, 'num_1', 'заявки');
	_comtexErrMsg($dialog_id, 'num_2', 'сотрудники');

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`num_1`
			  AND !`num_3`
			  AND !`num_4`";
	query($sql);
}
function _comtex_zayav_expense_other() {//расход по заявке: прочее
	$dialog_id = _comtexSpisokClear(1445);

	_db2();
	$sql = "SELECT *
			FROM _zayav_expense
			WHERE `app_id`=".APP_ID_OLD."
			  AND `category_id`=16
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

				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */
				'".addslashes($r['txt'])."',
				".$r['sum'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
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
				  sum_1,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

//	_comtexErrMsg($dialog_id, 'num_1', 'заявки');

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`num_1`
			  AND !`num_2`";
	query($sql);
}
function _comtex_zayav_expense_tovar() {//расход по заявке: запчасти
	$dialog_id = _comtexSpisokClear(1446);

	_db2();
	$sql = "SELECT *
			FROM _zayav_expense
			WHERE `app_id`=".APP_ID_OLD."
			  AND `category_id`=15
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

				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1403, $r['tovar_id']).", /* товары */
				".($r['tovar_count'] ? $r['tovar_count'] : 1).",
				".$r['sum'].",

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
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

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

//	_comtexErrMsg($dialog_id, 'num_1', 'заявки');
//	_comtexErrMsg($dialog_id, 'num_2', 'товары');

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND (!`num_1` OR !`num_2`)";
	query($sql);
}

function _comtex_accrual() {//начисления
	$dialog_id = _comtexSpisokClear(1409);

	_db2();
	$sql = "SELECT *
			FROM _money_accrual
			WHERE `app_id`=".APP_ID_OLD."
			  AND !`schet_id`
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

				".$r['sum'].",
				'".$r['about']."',
				"._comtexAss(1234, $r['client_id']).",
				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1447, $r['zayav_id']).", /* заявки-вызов специалиста */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */

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
				
				  sum_1,
				  txt_1,
				  num_1,
				  num_2,
				  num_3,
				  num_4,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`num_1`
			  AND !`num_2`
			  AND !`num_3`
			  AND !`num_4`";
	query($sql);
}
function _comtex_invoice() {//Расчётные счета
	$dialog_id = _comtexSpisokClear(1412);

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

	//Установка счёта по умолчанию при внесении платежа для Счёта на оплату
	$sql = "SELECT `id`
			FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND `txt_1`='Безналичный'
			LIMIT 1";
	if($def = query_value($sql)) {
		$sql = "UPDATE `_element`
				SET `num_6`=".$def."
				WHERE `id`=18274";
		query($sql);
	}
}
function _comtex_invoice_transfer() {//переводы между счетами
	$dialog_id = _comtexSpisokClear(1414);

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

				"._comtexAss(1412, $r['invoice_id_from']).", /* расчётные счета */
				"._comtexAss(1412, $r['invoice_id_to']).", /* расчётные счета */
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
function _comtex_refund() {//возвраты
	$dialog_id = _comtexSpisokClear(1418);

	_db2();
	$sql = "SELECT *
			FROM _money_refund
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

				".$r['sum'].",
				'".$r['about']."',
				"._comtexAss(1412, $r['invoice_id']).", /* расчётные счета */
				"._comtexAss(1234, $r['client_id']).",
				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */

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
				
				  sum_1,
				  txt_1,
				  num_1,
				  num_2,
				  num_3,
				  num_4,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'счета');
//	_comtexErrMsg($dialog_id, 'num_2', 'клиенты');

	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`num_2`";
	query($sql);
}

function _comtex_expense_category() {//категории расходов
	$dialog_id = _comtexSpisokClear(1415);

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

				".$r['sort']."
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  num,
				  dialog_id,

				  txt_1,/* name */

				  sort
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_expense() {//расходы
	$dialog_id = _comtexSpisokClear(1416);

	_db2();
	$sql = "SELECT *
			FROM _money_expense
			WHERE `app_id`=".APP_ID_OLD."
			  AND !`worker_id`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1415";
	$CAT = query_ass($sql);

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._num(@$CAT[$r['category_id']]).",
				"._comtexAss(1412, $r['invoice_id']).", /* расчётные счета */
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

	_comtexErrMsg($dialog_id, 'num_2', 'счета');
}
function _comtex_worker_zp() {//выдача зарплата сотрудников
	$dialog_id = _comtexSpisokClear(1417);

	_db2();
	$sql = "SELECT *
			FROM _money_expense
			WHERE `app_id`=".APP_ID_OLD."
			  AND `worker_id`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mon = $r['year'].'-'.($r['mon'] < 10 ? '0' : '').$r['mon'];
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexUserId($r, 'worker_id').",
				"._comtexAss(1412, $r['invoice_id']).", /* расчётные счета */
				".$r['sum'].",
				'".$r['about']."',
				'".$mon."',

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
				  txt_2,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

//	_comtexErrMsg($dialog_id, 'num_1', 'сотрудники');
	_comtexErrMsg($dialog_id, 'num_2', 'счета');
}
function _comtex_salary_accrual() {//произвольные начисления зп сотрудникам
	$dialog_id = _comtexSpisokClear(1441);

	_db2();
	$sql = "SELECT *
			FROM _salary_accrual
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mon = $r['year'].'-'.($r['mon'] < 10 ? '0' : '').$r['mon'];
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexUserId($r, 'worker_id').",
				".$r['sum'].",
				'".$r['about']."',
				'".$mon."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  sum_1,
				  txt_1,
				  txt_2,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'сотрудники');
}
function _comtex_salary_deduct() {//вычеты зп сотрудникам
	$dialog_id = _comtexSpisokClear(1442);

	_db2();
	$sql = "SELECT *
			FROM _salary_deduct
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mon = $r['year'].'-'.($r['mon'] < 10 ? '0' : '').$r['mon'];
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexUserId($r, 'worker_id').",
				".$r['sum'].",
				'".$r['about']."',
				'".$mon."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  sum_1,
				  txt_1,
				  txt_2,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'сотрудники');
}

function _comtex_remind_status() {//статусы напоминаний
	return;
	$dialog_id = _comtexSpisokClear(1420);

	$sql = "INSERT INTO _spisok (app_id,dialog_id,id_old,txt_1,txt_2,sort,user_id_add) VALUES (".APP_ID.",".$dialog_id.",1,'Активные','#D7EBFF',0,1)";
	$status_id = query_id($sql);

	$sql = "INSERT INTO _spisok (app_id,dialog_id,id_old,txt_1,txt_2,sort,user_id_add) VALUES (".APP_ID.",".$dialog_id.",2,'Выполненные','#cfc',1,1)";
	query($sql);

	$sql = "INSERT INTO _spisok (app_id,dialog_id,id_old,txt_1,txt_2,sort,user_id_add) VALUES (".APP_ID.",".$dialog_id.",3,'Отменены','#ededed',2,1)";
	query($sql);

	$sql = "UPDATE `_element`
			SET `num_6`=".$status_id."
			WHERE `id`=16166";
	query($sql);
}
function _comtex_remind_reason() {//причины переноса напоминаний
	$dialog_id = _comtexSpisokClear(1421);

	//категории
	_db2();
	$sql = "SELECT *
			FROM `_remind_reason`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `count` DESC";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",
				
				'".$r['txt']."'
			)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  txt_1
			) VALUES ".implode(',', $mass);
	query($sql);
}
function _comtex_remind() {//напоминания
	$dialog_id = _comtexSpisokClear(1419);

	_db2();
	$sql = "SELECT *
			FROM _remind
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

				'".$r['txt']."',
				'".$r['about']."',
				'".$r['day']."',
				"._comtexAss(1420, $r['status']).", /* статусы напоминаний */
				"._comtexAss(1234, $r['client_id']).",
				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1447, $r['zayav_id']).", /* заявки-вызов специалиста */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  txt_1,
				  txt_2,
				  date_1,
				  num_1,
				  num_2,
				  num_3,
				  num_4,
				  num_5,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	$sql = "DELETE FROM `_spisok`
			WHERE dialog_id=".$dialog_id."
			  AND !num_3
			  AND !num_4
			  AND !num_5";
	query($sql);

	_comtexHistory($dialog_id);
}
function _comtex_remind_action() {//действие с напоминаниями
	$dialog_id = _comtexSpisokClear(1424);

	_db2();
	$sql = "SELECT *
			FROM `_remind_history`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `viewer_id_add`
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT DISTINCT `txt_1`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1421";
	$REASON = query_ass($sql);

	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexAss(1419, $r['remind_id']).", /* напоминания */
				"._comtexAss(1420, $r['status']).", /* статусы напоминаний */
				'".$r['day']."',
				"._num(@$REASON[$r['txt']]).",

				"._comtexUserId($r).",
				'".$r['dtime_add']."'
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_1,
				  num_2,
				  date_1,
				  num_3,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	$sql = "SELECT `id`
			FROM _spisok
			WHERE `id_old`=1
			  AND dialog_id=1420
			LIMIT 1";
	$status_id = query_value($sql);

	$sql = "DELETE FROM `_spisok`
			WHERE dialog_id=".$dialog_id."
			  AND num_2=".$status_id."
			  AND !num_3";
	query($sql);

	$sql = "DELETE FROM `_spisok`
			WHERE dialog_id=".$dialog_id."
			  AND !num_1";
	query($sql);

	_comtexHistory($dialog_id);
}

function _comtex_zayav_note() {//заметки в заявках
	$sql = "DELETE FROM `_note` WHERE `app_id`=".APP_ID;
	query($sql);

	_db2();
	$sql = "SELECT *
			FROM `_note`
			WHERE `app_id`=".APP_ID_OLD."
			  AND `page_name`=45
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$mass = array();
	foreach($arr as $id => $r) {
		$page_id = 0;
		if($obj_id = _comtexAss(1402, $r['page_id'])) // заявки-оборудование
			$page_id = 473;
		elseif($obj_id = _comtexAss(1447, $r['page_id']))//заявки-вызов специалиста
			$page_id = 492;
		elseif($obj_id = _comtexAss(1429, $r['page_id']))//заявки-картриджи
			$page_id = 481;

		if(!$page_id)
			continue;

		$mass[] = "(
				".$id.",
				".APP_ID.",

				".$page_id.",
				".$obj_id.",
				'".addslashes($r['txt'])."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted'].",
				"._comtexUserId($r, 'viewer_id_del').",
				'".$r['dtime_del']."'
		)";
	}

	$sql = "INSERT INTO `_note` (
				id_old,
				app_id,
				
				page_id,
				obj_id,
				txt,
				
				user_id_add,
				dtime_add,
				deleted,
				user_id_del,
				dtime_del
			) VALUES ".implode(',', $mass);
	query($sql);

	// Вставка первого комментария в описание неисправности в заявке
	$sql = "UPDATE `_spisok` `t`
			SET `txt_2`=IFNULL((
							SELECT `txt`
							FROM `_note`
							WHERE `obj_id`=`t`.`id`
							  AND `app_id`=".APP_ID."
							ORDER BY `id`
							LIMIT 1
						),'')
			WHERE dialog_id=1402";
	query($sql);
}
function _comtex_zayav_note_comment() {//комментарии к заметкам в заявках
	$sql = "DELETE FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND `parent_id`";
	query($sql);

	_db2();
	$sql = "SELECT *
			FROM `_note_comment`
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`";
	$NOTE = query_ass($sql);

	$mass = array();
	foreach($arr as $id => $r) {
		if(!$parent_id = _num(@$NOTE[$r['note_id']]))
			continue;

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$parent_id.",

				'".addslashes($r['txt'])."',

				"._comtexUserId($r).",
				'".$r['dtime_add']."',
				".$r['deleted'].",
				"._comtexUserId($r, 'viewer_id_del').",
				'".$r['dtime_del']."'
		)";
	}

	$sql = "INSERT INTO `_note` (
				id_old,
				app_id,
				parent_id,

				txt,
				
				user_id_add,
				dtime_add,
				deleted,
				user_id_del,
				dtime_del
			) VALUES ".implode(',', $mass);
	query($sql);
}

function _comtex_schet_pay() {//счета на оплату
	$dialog_id = _comtexSpisokClear(1431);

	_db2();
	$sql = "SELECT *
			FROM _schet_pay
			WHERE `app_id`=".APP_ID_OLD."
			  AND type_id=1
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	//формирование содержания для счетов
	_db2();
	$SC = array();
	$sql = "SELECT *
			FROM _schet_pay_content
			WHERE `app_id`=".APP_ID_OLD."
			ORDER BY `id`";
	foreach(query_arr($sql) as $r) {
		$schet_id = _num($r['schet_id']);

		if(!isset($SC[$schet_id]))
			$SC[$schet_id] = array();

		$SC[$schet_id][] = array(
			'id' => 10,
			'txt' => $r['name'],
			'count' => $r['count'],
			'cena' => $r['cena'],
			'sum' => $r['count']*$r['cena']
		);
	}


	$mass = array();
	foreach($arr as $id => $r) {
		$txt_2 = isset($SC[$id]) ? json_encode($SC[$id]) : '';

		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				1352491, /* Организация-получатель */
				1352542, /* Банк */
				'".$r['prefix'].$r['nomer']."',
				'".$r['date_create']."',
				"._comtexAss(1234, $r['client_id']).",
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */
				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1447, $r['zayav_id']).", /* заявки-вызов специалиста */
				'".addslashes($txt_2)."',
				".$r['pass'].",
				'".$r['pass_day']."',
				".$r['sum'].",

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
				  num_4,
				  txt_1,
				  date_1,
				  num_2,
				  num_3,
				  num_7,
				  num_8,
				  txt_2,
				  num_5,
				  date_2,
				  sum_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

//	_comtexErrMsg($dialog_id, 'num_2', 'клиенты');
//	_comtexErrMsg($dialog_id, 'num_3', 'заявки');
}
function _comtex_cartridge_in_zayav() {//картриджи в заявках
	$dialog_id = _comtexSpisokClear(1432);

	_db2();
	$sql = "SELECT *
			FROM _zayav_cartridge
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return;

	$sql = "SELECT `id_old`,`id`
			FROM `_spisok`
			WHERE `dialog_id`=1403
			  AND `num_1` IN (1457227,1457228)
			  AND `id_old`";
	$CART = query_ass($sql);


	$mass = array();
	foreach($arr as $id => $r) {
		$mass[] = "(
				".$id.",
				".APP_ID.",
				".$id.",
				".$dialog_id.",

				"._comtexAss(1431, $r['schet_id']).", /* счета на оплату */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */
				"._num(@$CART[$r['cartridge_id']]).",
				
				".$r['filling'].",
				".$r['restore'].",
				".$r['chip'].",
				".$r['cost'].",

				'".addslashes($r['prim'])."',

				'"._comtexAss(1429, $r['zayav_id'], 'user_id_add')."', /* заявки-картриджи: кто внёс */
				'"._comtexAss(1429, $r['zayav_id'], 'dtime_add')."'    /* заявки-картриджи: дата внесения */
		)";
	}

	$sql = "INSERT INTO `_spisok` (
				  `id_old`,
				  `app_id`,
				  `num`,
				  `dialog_id`,
				
				  num_8,
				  num_1,
				  num_2,

				  num_3,
				  num_4,
				  num_5,
				  num_6,

				  txt_1,

				  user_id_add,
				  dtime_add
			) VALUES ".implode(',', $mass);
	query($sql);

	_comtexErrMsg($dialog_id, 'num_1', 'заявки');
//	_comtexErrMsg($dialog_id, 'num_2', 'картриджи');
}

function _comtex_income() {//платежи
	$dialog_id = _comtexSpisokClear(1413);

	_db2();
	$sql = "SELECT *
			FROM _money_income
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

				".$r['sum'].",
				'".$r['about']."',
				"._comtexAss(1412, $r['invoice_id']).", /* расчётные счета */
				"._comtexAss(1234, $r['client_id']).",
				"._comtexAss(1402, $r['zayav_id']).", /* заявки-оборудование */
				"._comtexAss(1447, $r['zayav_id']).", /* заявки-вызов специалиста */
				"._comtexAss(1429, $r['zayav_id']).", /* заявки-картриджи */
				"._comtexAss(1431, $r['schet_id']).", /* счета на оплату */
				'".$r['schet_paid_day']."',

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
				
				  sum_1,
				  txt_1,
				  num_1,
				  num_2,
				  num_3,
				  num_4,
				  num_5,
				  num_6,
				  date_1,

				  user_id_add,
				  dtime_add,
				  deleted
			) VALUES ".implode(',', $mass);
	query($sql);

/*
	$sql = "DELETE FROM `_spisok`
			WHERE `dialog_id`=".$dialog_id."
			  AND !`num_3`
			  AND !`num_4`
			  AND !`num_5`";
	query($sql);
*/
}

function _comtex_devices_lost() {//восстановление потерянных устройств в заявках
	$sql = "SELECT id_old,id
			FROM `_spisok`
			WHERE `dialog_id`=1402
			  AND !`num_3`";
	if(!$ASS = query_ass($sql))
		return;

	_db2();
	$sql = "SELECT *
			FROM _zayav
			WHERE `id` IN ("._idsGet($ASS, 'key').")";
	if(!$zayav = query_arr($sql))
		return;

	foreach($zayav as $r) {
		$sql = "SELECT `id`
				FROM `_spisok`
				WHERE `dialog_id`=1403
				  AND `txt_1`='".$r['name']."'
				LIMIT 1";
		if(!$tovar_id = query_value($sql)) {
			$sql = "INSERT INTO `_spisok` (
						`app_id`,
						`dialog_id`,
						`num_1`,
						`txt_1`,
						`user_id_add`
					) VALUES (
						".APP_ID.",
						1403,
						1451283,
						'".$r['name']."',
						1
					)";
			$tovar_id = query_id($sql);
		}
		$sql = "UPDATE `_spisok`
				SET `num_3`=".$tovar_id."
				WHERE `id`=".$ASS[$r['id']];
		query($sql);
	}


}






























