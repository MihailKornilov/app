<?php
require_once 'modul/global/global.php';

_auth();

die(
	_header().
	_content().
	_debug().
	_footer()
);
