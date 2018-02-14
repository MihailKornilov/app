<?php
require_once 'modul/global/global.php';

_face();
_saSet();
_auth();
_pasDefine();

die(
	_header().
	_pageSetupMenu().
	_content().
	_debug().
	_footer()
);
