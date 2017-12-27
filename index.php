<?php
require_once 'modul/global/global.php';

_face();
_saSet();
_auth();
_pageSetupDefine();

die(
	_header().
	_pageSetupMenu().
	_content().
	_debug().
	_footer()
);
