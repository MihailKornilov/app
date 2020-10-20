<?php
require_once 'modul/global/global.php';

/*
echo 'COOKIE:';
echo '<br>';
echo _pr($_COOKIE);
echo '<br>';
echo '<br>';

echo 'CODE='.CODE;
echo '<br>';
*/
//_cookieDel('code');

//установка глобальных настроек - констант: SCRIPT, JS_CACHE, APP_ACCESS
_setting();

//определение, как загружена страница: iframe или сайт
//_face();

//проверка авторизации. Установка констант: USER_ID, APP_ID
_auth();

/*
echo 'USER_ID='.USER_ID;
echo '<br>';
echo 'APP_ID='.APP_ID;
echo '<br>';
echo 'APP_PARENT='.APP_PARENT;
echo '<br>';
echo 'APP_IS_PID='.APP_IS_PID;
echo '<br>';
echo 'USER_ACCESS='.USER_ACCESS;
echo '<br>';
*/


//выход, если требуется
_authLogout();

//установка флага SA
_sa();

//получение данных о пользователе
_user();

//установка флага PAS: управления страницей
_pasDefine();

//
_userInviteCookieSave();



//приложение было запущено через фрейм в ВК
_pageIframe();

//страница авторизации
_pageAuth();

//страница (19): тех-работы
_pageGlobalDeny();

//страница (105): о недоступности приложения для пользователя
_pageAppUserAccess();

//приложение в работе
_pageContent();

//список приложений пользователя
_page98();
