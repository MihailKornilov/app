<?php
function _button($v=array()) {//������ �� ��������
	$name = empty($v['name']) ? '������' : $v['name'];
	$click = empty($v['click']) ? '' : ' onclick="'.$v['click'].'"';
	$color = empty($v['color']) ? '' : ' '.$v['color'];

	return
	'<button class="vk'.$color.'"'.$click.'>'.
		$name.
	'</button>';
}








