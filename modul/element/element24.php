<?php

/* [24] Select: выбор списка приложения */
function _element24_struct($el) {
	return array(
		'txt_1'   => $el['txt_1'],      //текст, когда список не выбран
		'num_1'   => _num($el['num_1']),/* содержание селекта:
											0   - все списки приложения. Функция _dialogSpisokOn()
											960 - размещённые на текущем объекте
												  Списки размещаются диалогами 14(шаблон), 23(таблица), История действий
												  Идентификаторами результата являются id элементов (а не диалогов)
												  Функция _dialogSpisokOnPage()
											961 - привязанные к данному диалогу
												  Идентификаторами результата являются id элементов (а не диалогов)
												  Функция _dialogSpisokOnConnect()
									   */
		'num_2'   => _num($el['num_2']) //[1] возможность выбора нескольких списков
	) + _elementStruct($el);
}
function _element24_print($el, $prm) {
	return
	_select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, 0)
   ));
}
function _element24_vvv($el, $prm) {
	if(empty($prm['srce']))
		return array();

	$dialog_id = $prm['srce']['dialog_id'];
	$block_id = $prm['srce']['block_id'];
	switch($el['num_1']) {
		//диалоги, которые могут быть списками: spisok_on=1 и размещены на текущей странице
		case 960: return _dialogSpisokOnPage($block_id);
		//диалоги, которые привязаны к выбранному диалогу
		case 961: return _dialogSpisokOnConnect($block_id);
	}

	//все списки приложения
	return _dialogSpisokOn($dialog_id, $block_id, $el['id']);
}

