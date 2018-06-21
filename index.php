<?php
require_once 'modul/global/global.php';

_setting();
_face();
_auth();
_authLogout();
_saDefine();
_user();
_pasDefine();

die(_html());
