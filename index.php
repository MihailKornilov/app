<?php
require_once 'modul/global/global.php';

_face();
_saSet();
_auth();
_authLogout();
_user();
_pasDefine();

die(_html());
