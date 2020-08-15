<?php

/* [45] Выбор нескольких значений привязанного списка */
function _element45_struct($el) {
	return array(
		'req'     => _num($el['req']),
		'req_msg' => $el['req_msg'],

		'num_1'   => _num($el['num_1']),//список (из которого будут выбираться значения)
		'txt_1'   => $el['txt_1'],      //имя кнопки
		'num_2'   => _num($el['num_2']),//вспомогательный диалог
		'txt_3'   => $el['txt_3'],      //элемент-название [13]
		'txt_4'   => $el['txt_4'],      //элемент-категория [13]
		'txt_2'   => $el['txt_2'],      //элемент-изображение [13]
		'num_3'   => _num($el['num_3']),//указывать количество выбранных значений
		'num_4'   => _num($el['num_4']),//элемент-цена [13]
		'num_5'   => _num($el['num_5']) //элемент-итог [13]
	) + _elementStruct($el);
}
function _element45_js($el) {
	return array(
		'num_2' => _num($el['num_2']),
		'num_4' => _num($el['num_4']),
		'num_5' => _num($el['num_5'])
	) + _elementJs($el);
}
function _element45_print($el, $prm) {
	$v = _elemPrintV($el, $prm);

	return
	'<input type="hidden" id="'._elemAttrId($el, $prm).'" value="'.$v.'" />'.
	'<div>'._element45Uns($el, $v).'</div>'.
	'<table class="w100p">'.
		'<tr><td>'.
				_button(array(
					'attr_id' => _elemAttrId($el, $prm).$el['afics'],
					'name' => $el['txt_1'],
					'color' => 'grey',
					'width' => $el['width'],
					'small' => 1,
					'class' => _dn(!$prm['blk_setup'], 'curD')
				)).
			'<td class="uns-itog r fs14 pr40">'.
	'</table>';
}
function _element45_print11($el, $u) {
	if(!$col = _elemCol($el))
		return '';

	return _element45Uns($el, @$u[$col], true);
}
function _element45Uns($el, $v, $is_show=false) {//выбранные значения при редактировании
	if(empty($v))
		return '';

	$UNS = array();
	foreach(explode(',', $v) as $ex) {
		$exx = explode(':', $ex);
		$UNS[] = array(
			'id' => $exx[0],
			'count' => $exx[1],
			'cena' => isset($exx[2]) ? round($exx[2], 2) : 0
		);
	}

	if(empty($UNS))
		return '';
	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE `t1`.`id` IN ("._idsGet($UNS).")
			  AND "._queryWhere($DLG, 1);
	if(!$arr = query_arr($sql))
		return '';

	//вставка значений из вложенных списков
	$arr = _spisokInclude($arr);

	//вставка картинок
	$arr = _spisokImage($arr);

	//элемент-название
	if(!$colName = _elemCol($el['txt_3']))
		$colName = _elemCol($DLG['spisok_elem_id']);


	//элемент-изображение
	$colImg = _elemCol($el['txt_2']);

	//элемент-цена
	$colCena = _elemCol($el['num_4']);

	$send = '';
	$n = 1;
	foreach($UNS as $r) {
		if(!isset($arr[$r['id']]))
			continue;

		$name = _msgRed('Отсутствует колонка для отображения названия');

		$u = $arr[$r['id']];
		if($colName)
			if(isset($u[$colName]))
				$name = $u[$colName];

		$cena = $r['cena'];
		if($colCena && $r['cena'] < 0)
			if(isset($u[$colCena]))
				$cena = $u[$colCena];

		//вариант вывода значений для редактирования
		if(!$is_show) {
			$send .=
			'<tr><td class="w35">'.
					'<div class="fs14 grey r">'.$n++.'</div>'.
	 ($colImg ? '<td class="pad0 w35 center">'._imageHtml($u[$colImg], 30, 30) : '').
				'<td>'.
	($el['txt_4'] ? '<div class="fs11 grey mb2">'._elemUids($el['txt_4'], $u).'</div>' : '').
					'<div class="fs15">'.$name.'</div>'.
				'<td class="w70 bg-ffd'._dn($el['num_3']).'">'.
					'<input type="text" class="uinp w100p r b" val="'.$r['id'].'" value="'.$r['count'].'">'.

			($colCena ?
				'<td class="w100 r">'.
					'<b class="ucena">'.$cena.'</b> руб.'
			: '').

				'<td class="pad0 w35 center">'.
					'<div class="icon icon-del tool-l" data-tool="Отменить выбор"></div>';
			continue;
		}

		//вариант вывода значений для просмотра
		$imgW = $el['txt_4'] ? 30 : 20;
		$send .=
		'<tr><td class="w25 grey r">'.$n++.
 ($colImg ? '<td class="pad0 '.($el['txt_4'] ? 'w35' : 'w25').' center">'._imageHtml($u[$colImg], $imgW, $imgW) : '').
			'<td>'.
($el['txt_4'] ? '<div class="fs11 grey mb2">'._elemUids($el['txt_4'], $u).'</div>' : '').
				$name;
		if($el['num_3'])
			$send .= '<td class="w50 r b">'.$r['count'];

		if($colCena)
			$send .= '<td class="w70 r fs12">'.
						'<b class="ucena fs12">'.$cena.'</b> руб.';
	}

	return '<table class="_stab w100p small'._dn($is_show, 'mb5').'">'.$send.'</table>';
}
function _element45_template_docx($el, $u) {
	if(!$col = $el['col'])
		return '';
	if(empty($u[$col]))
		return '';

	if(!$BL = _blockOne($el['block_id']))
		return '';
	if($BL['obj_name'] != 'dialog')
		return '';

	$UNS = array();
	foreach(explode(',', $u[$col]) as $r) {
		$ex = explode(':', $r);
		$UNS[] = array(
			'id' => _num($ex[0]),
			'count' => round($ex[1], 2),
			'cena' => isset($ex[2]) ? round($ex[2], 2) : 0

		);
	}

	if(empty($UNS))
		return '';

	if(!$DLG = _dialogQuery($el['num_1']))
		return '';

	$sql = "SELECT "._queryCol($DLG)."
			FROM   "._queryFrom($DLG)."
			WHERE `t1`.`id` IN ("._idsGet($UNS).")
			  AND "._queryWhere($DLG, 1);
	if(!$arr = query_arr($sql))
		return '';

	$col = _elemCol($DLG['spisok_elem_id']);
	$n = 1;
	$TR = '';
	foreach($UNS as $r) {
		$u = $arr[$r['id']];

		$name = '';
		if($col)
			if(isset($u[$col]))
				$name = $u[$col];

		$TR .=
		'<w:tr w:rsidR="003F55DD" w:rsidTr="003F55DD"><w:trPr><w:trHeight w:val="375"/></w:trPr><w:tc><w:tcPr><w:tcW w:w="447" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="00054B3F"><w:pPr><w:jc w:val="right"/><w:rPr><w:color w:val="7F7F7F" w:themeColor="text1" w:themeTint="80"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr></w:pPr><w:r><w:rPr><w:color w:val="7F7F7F" w:themeColor="text1" w:themeTint="80"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'.
			'<w:t>'.($n++).'</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="4253" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="003F55DD" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="00054B3F"><w:pPr><w:rPr><w:lang w:val="en-US"/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:lang w:val="en-US"/></w:rPr>'.
			'<w:t>'.$name.'</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1302" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="003F55DD" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="00054B3F"><w:pPr><w:jc w:val="center"/></w:pPr><w:r w:rsidRPr="003F55DD">'.
			'<w:t>'.$r['count'].'</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1534" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="003F55DD" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="00054B3F"><w:pPr><w:jc w:val="right"/></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:lang w:val="en-US"/></w:rPr>'.
			'<w:t xml:space="preserve">'._sumSpace($r['cena'], true).' </w:t></w:r><w:r w:rsidRPr="003F55DD">'.
			'<w:t>руб.</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1950" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="003F55DD" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="00054B3F"><w:pPr><w:jc w:val="right"/></w:pPr><w:r w:rsidRPr="003F55DD">'.
			'<w:t>'._sumSpace($r['count']*$r['cena'], true).' руб.</w:t></w:r></w:p></w:tc>'.
		'</w:tr>';
	}

	return
	'<w:tbl><w:tblPr><w:tblStyle w:val="a3"/><w:tblW w:w="0" w:type="auto"/><w:tblInd w:w="108" w:type="dxa"/><w:tblLook w:val="04A0"/></w:tblPr><w:tblGrid><w:gridCol w:w="447"/><w:gridCol w:w="4253"/><w:gridCol w:w="1302"/><w:gridCol w:w="1534"/><w:gridCol w:w="1950"/></w:tblGrid>'.

		'<w:tr w:rsidR="009B4ACE" w:rsidTr="003F55DD"><w:tc><w:tcPr><w:tcW w:w="447" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:shd w:val="clear" w:color="auto" w:fill="D9D9D9" w:themeFill="background1" w:themeFillShade="D9"/><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="009B4ACE" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="003F55DD"><w:pPr><w:jc w:val="center"/><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:b/></w:rPr><w:t>№</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="4253" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:shd w:val="clear" w:color="auto" w:fill="D9D9D9" w:themeFill="background1" w:themeFillShade="D9"/><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="009B4ACE" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="003F55DD"><w:pPr><w:jc w:val="center"/><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:b/></w:rPr><w:t>Название</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1302" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:shd w:val="clear" w:color="auto" w:fill="D9D9D9" w:themeFill="background1" w:themeFillShade="D9"/><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="009B4ACE" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="003F55DD"><w:pPr><w:jc w:val="center"/><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:b/></w:rPr><w:t>Кол-во</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1534" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:shd w:val="clear" w:color="auto" w:fill="D9D9D9" w:themeFill="background1" w:themeFillShade="D9"/><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="009B4ACE" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="003F55DD"><w:pPr><w:jc w:val="center"/><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:b/></w:rPr><w:t>Цена</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1950" w:type="dxa"/><w:tcBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:left w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="000000" w:themeColor="text1"/></w:tcBorders><w:shd w:val="clear" w:color="auto" w:fill="D9D9D9" w:themeFill="background1" w:themeFillShade="D9"/><w:vAlign w:val="center"/><w:hideMark/></w:tcPr><w:p w:rsidR="009B4ACE" w:rsidRPr="003F55DD" w:rsidRDefault="003F55DD" w:rsidP="003F55DD"><w:pPr><w:jc w:val="center"/><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="003F55DD"><w:rPr><w:b/></w:rPr><w:t>Сумма</w:t></w:r></w:p></w:tc></w:tr>'.

		$TR.

	'</w:tbl>';
}
