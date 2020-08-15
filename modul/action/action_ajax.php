<?php

switch(@$_POST['op']) {
	case 'act219_block_upd'://обновление содержимого блока
		if(!$action_id = _num($_POST['action_id']))
			jsonError('Не получен id действия');
		if(!$src_id = _num($_POST['src_id']))
			jsonError('Не получен исходный блок');
		if(!$SRC = _blockOne($src_id))
			jsonError('Исходного блока id'.$src_id.' не существует');
		if(!$block_id = _num($_POST['ids']))
			jsonError('Функция работает пока только для одного блока');
		if(!$unit_id = _num($_POST['unit_id']))
			jsonError('Не получен id записи');
		if(!$bl = _blockOne($block_id))
			jsonError('Блока id'.$block_id.' не существует');
		if(!$action =  _BE('block_one_action', $src_id))
			jsonError('Исходному блоку не назначены действия');

		//заливка, в которую будет окрашен выбранный (исходный) блок
		$send['bg'] = '';
		foreach($action as $r)
			if($r['id'] == $action_id)
				$send['bg'] = $r['v1'];

		$BLK = _BE('block_obj', $bl['obj_name'], $bl['obj_id']);

		$bll[$block_id] = _blockChild($BLK, $block_id);

		$prm = _blockParam(array(), $bl['obj_name']);
		$prm['unit_get_id'] = $unit_id;

		$send['blk'][$block_id] = _blockLevel($bll, $prm, 0, 2, $bl['width']);

		jsonSuccess($send);
		break;
}
