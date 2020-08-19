<?php

/* [52] Заметки */
function _element52_struct($el) {
	return array(
		'num_1'   => _num($el['num_1']),//разрешать комментарии к заметкам
		'num_2'   => _num($el['num_2']) /* управление клавишами:
											9645 - внесение заметки при нажатии CTRL+ENTER, перенос строки ENTER
											9644 - внесение заметки при нажатии ENTER, перенос строки CTRL+ENTER
                                        */
	) + _elementStruct($el);
}
function _element52_print($el, $prm) {
	if($prm['blk_setup'])
		return _emptyMin('Заметки');
	if(!$width = _noteWidth($el))
		return _emptyRed('Не получена шинина для заметок');

	$page_id = _page('cur');
	$obj_id = _num(@$_GET['id']);

	return
	'<div class="_note" val="'.$page_id.':'.$obj_id.'">'.
		'<div class="prel">'.
			'<div class="note-ok"></div>'.
			'<div class="icon icon-ok spin"></div>'.
			'<div class="_note-txt">'.
				'<textarea placeholder="напишите заметку..." /></textarea>'.
			'</div>'.
		'</div>'.
		'<div class="_note-list">'._noteList($page_id, $obj_id, $el).'</div>'.
	'</div>';
}

function _noteWidth($el) {//получение ширины заметки
	return _elemWidth($el) - 20;
}
function _noteList($page_id, $obj_id, $el) {
	$sql = "SELECT *
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND !`parent_id`
			  AND !`deleted`
			  AND `page_id`=".$page_id."
			  AND `obj_id`=".$obj_id."
			ORDER BY `dtime_add` DESC";
	if(!$arr = query_arr($sql))
		return '';

	foreach($arr as $id => $r)
		$arr[$id]['comment'] = array();

	$arr = _noteImgArr($arr);

	$sql = "SELECT *
			FROM `_note`
			WHERE `parent_id` IN ("._idsGet($arr).")
			  AND !`deleted`
			ORDER BY `dtime_add`";
	if($comm = query_arr($sql)) {
		$comm = _noteImgArr($comm);
		foreach($comm as $r)
			$arr[$r['parent_id']]['comment'][] = $r;
	}

	$send = '';
	$n = 0;
	foreach($arr as $r) {
		$send .=
			'<div class="_note-u'._dn(!$n, 'line-t').'" val="'.$r['id'].'">'.
				'<div class="_note-is-show">'.
					'<table class="bs10 w100p">'.
						'<tr><td class="w35">'.
								'<img class="ava40" src="'._user($r['user_id_add'], 'src').'">'.
							'<td>'.
								'<div class="note-del icon icon-del pl fr tool-l" data-tool="Удалить заметку"></div>'.
								'<div val="dialog_id:81,edit_id:'.$r['id'].'" class="dialog-open icon icon-edit pl fr tool-l" data-tool="Изменить заметку"></div>'.
								'<a class="b">'._user($r['user_id_add'], 'name').'</a>'.
								'<div class="pale mt3">'.FullDataTime($r['dtime_add'], 1).'</div>'.
						'<tr>'.
							'<td colspan="2">'.
								'<div style="word-wrap:break-word;width:'._noteWidth($el).'px" class="fs14">'.
									_noteLink($r['txt'], 1).
								'</div>'.
								_noteImg($r).
					'</table>'.
					_noteComment($el, $r, $n).
				'</div>'.
				'<div class="_note-is-del">'.
					'Заметка удалена.'.
					'<a class="note-rest ml10">Восстановить</a>'.
				'</div>'.
			'</div>';
		$n++;
	}

	return $send;
}
function _noteComment($el, $r, $n) {//отображение комментариев к заметке
	if(!$el['num_1'])
		return '';

	$cmnt = $r['comment'] ? 'Комментарии '.count($r['comment']) : 'Комментировать';
	$comment = '';
	foreach($r['comment'] as $c)
		$comment .= _noteCommentUnit($el, $c);

	return
	'<div class="_note-to-cmnt dib b over1'._dn($n).'">'.
		'<div class="icon icon-comment"></div>'.
		$cmnt.
	'</div>'.
	'<div class="_note-comment'._dn(!$n).'">'.
		$comment.
		'<table class="w100p">'.
			'<tr><td><div class="_comment-txt">'.
						'<textarea placeholder="комментировать.." /></textarea>'.
					'</div>'.
				'<td class="w35 bottom">'.
					'<div class="icon icon-empty spin ml5 mb5"></div>'.
					'<div class="comment-ok"></div>'.
		'</table>'.
	'</div>';
}
function _noteCommentUnit($el, $c) {//html одного комментария
	return
	'<div class="_comment-u">'.
		'<table class="_comment-is-show bs5 w100p">'.
			'<tr><td class="w35">'.
					'<img class="ava30" src="'._user($c['user_id_add'], 'src').'">'.
				'<td>'.
					'<div class="_note-icon fr mr5">'.
						'<div val="dialog_id:82,edit_id:'.$c['id'].'" class="dialog-open icon icon-edit pl"></div>'.
						'<div class="comment-del icon icon-del pl" onclick="_noteCDel(this,'.$c['id'].')"></div>'.
					'</div>'.
					'<a class="fs12">'._user($c['user_id_add'], 'name').'</a>'.
					'<div class="fs12 pale mt2">'.FullDataTime($c['dtime_add'], 1).'</div>'.
			'<tr>'.
				'<td colspan="2">'.
					'<div style="word-wrap:break-word;width:'.(_noteWidth($el)-50).'px;">'.
						_noteLink($c['txt']).
					'</div>'.
					_noteImg($c).
		'</table>'.
		'<div class="_comment-is-del">'.
			'Комментарий удалён.'.
			'<a class="comment-rest ml10" onclick="_noteCRest(this,'.$c['id'].')">Восстановить</a>'.
		'</div>'.
	'</div>';
}
function _noteLink($txt, $fs14=false) {//поиск в тексте ссылок и обёртка
	$fs14 = $fs14 ? ' class="fs14"' : '';
	$preg_autolinks = array(
	    'pattern' => array(
	        "'[\w\+]+://[A-z0-9\.\?\+\-/_=&%#:;,]+[\w/=]+'si",
	        "'([^/])(www\.[A-z0-9\.\?\+\-/_=&%#:;,]+[\w/=]+)'si",
	    ),
	    'replacement' => array(
	        '<a href="$0" target="_blank" class="inhr" rel="nofollow">$0</a>',
	        '$1<a href="http://$2" target="_blank" rel="nofollow"'.$fs14.'>$2</a>',
	    ));
	$search = $preg_autolinks['pattern'];
	$replace = $preg_autolinks['replacement'];

	$txt = preg_replace($search, $replace, $txt);
	return _br($txt);

}
function _noteImgArr($arr) {//подмена id изображений на данные
	$imgIds = array();
	foreach($arr as $id => $r) {
		$imgIds[] = _ids($r['image_ids']);
		$arr[$id]['img'] = array();
	}

	if(empty($imgIds))
		return $arr;

	$sql = "SELECT *
			FROM `_image`
			WHERE `id` IN (".implode(',', $imgIds).")";
	if(!$img = query_arr($sql))
		return $arr;

	foreach($arr as $note_id => $r) {
		if(!$ids = _ids($r['image_ids'], 'arr'))
			continue;

		foreach($ids as $img_id)
			if(!empty($img[$img_id]))
				$arr[$note_id]['img'][] = $img[$img_id];
	}

	return $arr;
}
function _noteImg($r) {//вставка изображений
	if(empty($r['img']))
		return '';

	$send = '';
	foreach($r['img'] as $img)
		$send .= '<div class="dib mt10 mr10">'._imageHtml($img, 200, 200).'</div>';

	return '<div>'.$send.'</div>';
}


