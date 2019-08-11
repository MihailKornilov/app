<?php

/* [26] Select: выбор документа (SA) */
function _element26_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'txt_1'   => $el['txt_1']  //нулевое значение
	) + _elementStruct($el);
}
function _element26_js($el) {
	return array(
		'txt_1' => $el['txt_1']
	) + _elementJs($el);
}
function _element26_print($el, $prm) {
	return _select(array(
		'attr_id' => _elemAttrId($el, $prm),
		'placeholder' => $el['txt_1'],
		'width' => @$el['width'],
		'value' => _elemPrintV($el, $prm, 0)
	));
}
function _element26_vvv() {
	$sql = "SELECT `id`,`name`
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id` DESC";
	return query_ass($sql);
}

