<?php
/* Функции, которые требуются обязательно в обоих вариантах: site или iframe */
if(!function_exists('_auth')) {
	function _auth() {
		die('no func: _auth');
	}
}
