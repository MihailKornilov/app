<?php
require_once 'modul/global/global.php';

_face();
_sa();
_auth();

die(
	_header().
	_pageSetupMenu().
	_content().
	_debug().
	_footer()
);
