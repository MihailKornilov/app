<?php
switch(@$_POST['op']) {
	case 'app_enter'://вход в приложение из списка приложений
		if(!SITE)
			jsonError('Вход может осуществляться только на сайт');
		if(!$app_id = _num($_POST['app_id']))
			jsonError('Некорректный ID приложения');
		if(!_userAppAccessGet(USER_ID, $app_id))
			jsonError('Отсутствует регистрация в этом приложении');

		$sql = "UPDATE `_user_auth`
				SET `app_id`=".$app_id."
				WHERE `code`='".CODE."'";
		query($sql);

		$sql = "UPDATE `_user`
				SET `app_id_last`=".$app_id."
				WHERE `id`=".USER_ID;
		query($sql);

		_cache_clear('AUTH_'.CODE, 1);
		_cache_clear('page');

		_auth();

		jsonSuccess();
		break;
	case 'sort'://сортировка значений по элементу $.fn._sort
		if(!$elem_id = _num($_POST['elem_id']))
			jsonError('Не получен id элемента');
		if(!$el = _elemOne($elem_id))
			jsonError('Элемента '.$elem_id.' не существует');
		if(!$sortIds = _ids($_POST['ids'], 'arr'))
			jsonError('Отсутствуют элементы для сортировки');

		$dialog_id = 0;
		switch($el['dialog_id']) {
			case 14: $dialog_id = $el['num_1']; break;
			case 12:
				switch($el['txt_1']) {
					case 'PHP12_dialog_app':
					case 'PHP12_elem_choose':
						foreach($sortIds as $n => $id) {
							$sql = "UPDATE `_dialog` SET `sort`=".$n." WHERE `id`=".$id;
							query($sql);
						}
						jsonSuccess();
						break;
					case 'PHP12_app_list':
						foreach($sortIds as $n => $id) {
							$sql = "UPDATE `_user_access` SET `uasort`=".$n." WHERE `id`=".$id;
							query($sql);
						}
						jsonSuccess();
						break;
				}
				jsonError('Не найдена функция [12]');
				break;
			default: jsonError('Не найден диалог');
		}

		if(!$DLG = _dialogQuery($dialog_id))
			jsonError('Диалога '.$dialog_id.' не существует');
		if(!$DLG['table_1'])
			jsonError('Диалогу не присвоена таблица');
		if(!$sortCol = _queryColReq($DLG, 'sort'))
			jsonError('Отсутствует колонка сортировки');

		foreach($sortIds as $n => $id) {
			$sql = "UPDATE "._queryFrom($DLG)."
					SET ".$sortCol."=".$n."
					WHERE "._queryWhere($DLG)."
					  AND `t1`.`id`=".$id;
			query($sql);
		}

		jsonSuccess();
		break;
}


