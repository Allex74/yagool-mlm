<?php session_start();
/********** Подключение функций КБ ****************/
if (!defined('__DIR__')) define('__DIR__', dirname(__FILE__));
$modules_dir = 'modules';
$dir = explode($modules_dir, __DIR__);
$sess_id = substr($dir[0], 0, -1); // Обрежем последний слэш
define('CB_ROOT', $dir[0]);
$config['script_noauth'] = 1; // Авторизация нам не нужна
/*********** Подключаем все функции КБ *****************/
require_once (CB_ROOT . 'include/config.php');
// if(empty($config['site_root'])) $config['site_root'] = $sess_id;
// В разных версиях КБ могут отличаться вызовы common.php. В 2.0.3 отсюда обычно подключается хорошо
require_once (CB_ROOT . "common.php");
// Сбросим редирект на стр. авторизацию, если таковой будет после подключения common.php
if (isset($_SESSION[$ses_id]['login_redirect'])) unset($_SESSION[$ses_id]['login_redirect']);
// Подключим языковой файл, если вдруг он не подключен
// require_once $config['site_root']."/lang/".$config["lang"].".php";
// $u_id = 1; // Это ID пользователя, от имени которого Вы хотите добавлять запись. Я обычно использую admin

/***
**** Действие отправки письма с учётными данными
***/
if ($_REQUEST['vote_restor'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_email = $_REQUEST['vote_email'];
    if ($vote_email == '') {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по email ************/
    $record_id = 0; // Инициализируем переменную
    //Выборка из базы
    $sqlQuery = "SELECT `id`,`f3721`,`f3850`,`f3860`,`f1470`,`f3870` FROM `" . DATA_TABLE . "42` WHERE `f442`='" . $vote_email . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным из массива части значений строки
        $record_id = $row['id'];
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_sponsname = $row['f3860'];
        $smarty_login = $row['f1470'];
        $smarty_password = $row['f3870'];
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_sponsname", $smarty_sponsname);
        $smarty->assign("smarty_login", $smarty_login);
        $smarty->assign("smarty_password", $smarty_password);
        // Отправляем шаблон письма
        send_template(190, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Напоминание пароля';
        $data['f4100'] = 'На Ваш e-mail отправлены учётные данные';
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    if ($record_id == 0) {
        echo 'Нет такого E-mail в базе';
        exit;
    }
}
/***
**** Действие обновления значений полей в базе
***/
if ($_REQUEST['vote_save'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_city = $_REQUEST['vote_city'];
    $vote_name = $_REQUEST['vote_name'];
    $vote_patronymic = $_REQUEST['vote_patronymic'];
    $vote_lastname = $_REQUEST['vote_lastname'];
    $vote_dateofbirth = $_REQUEST['vote_dateofbirth'];
    $login = $_REQUEST['login'];
    if (($vote_city == '') || ($vote_name == '') || ($vote_patronymic == '') || ($vote_lastname == '') || ($vote_dateofbirth == '') || ($login == '')) {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по Login ************/
    $record_id = 0; // Инициализируем переменную
    //Выборка из базы
    $sqlQuery = "SELECT `id` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $login . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значение ID переменной
        $record_id = $row['id'];
        // Создаём массив части значений строки
        $data = Array();
        $data['f1047'] = $vote_city;
        $data['f3721'] = $vote_name;
        $data['f3850'] = $vote_patronymic;
        $data['f3840'] = $vote_lastname;
        $data['f3731'] = $vote_dateofbirth;
        $data['u'] = 1;
        // Обновляем поля строки значениями из массива
        data_update(42, EVENTS_ENABLE, $data, "`id`='", $record_id, "'");
    }
    if ($record_id == 0) {
        echo 'Нет такого пользователя';
        exit;
    }
    // Выборка из базы
    $sqlQuery2 = "SELECT `f1047`,`f3721`,`f3850`,`f3840`,`f3731` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $record_id . "' AND `status`=0 LIMIT 1";
    $result2 = sql_query($sqlQuery2) or user_error(mysql_error() . "<br>" . $sqlQuery2 . "<br>", E_USER_ERROR);
    while ($row2 = sql_fetch_assoc($result2)) {
        // Присваиваем значения переменным из массива части значений строки
        $smarty_sity = $row2['f1047'];
        $smarty_name = $row2['f3721'];
        $smarty_patronymic = $row2['f3850'];
        $smarty_lastname = $row2['f3840'];
        $smarty_dateofbirth = $row2['f3731'];
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_sity", $smarty_sity);
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_lastname", $smarty_lastname);
        $smarty->assign("smarty_dateofbirth", $smarty_dateofbirth);
        // Отправляем шаблон письма
        send_template(200, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Изменение данных';
        $data['f4100'] = 'Обновлено: ' . $row2['f1047'] . ', ' . $row2['f3721'] . ' ' . $row2['f3850'] . ' ' . $row2['f3840'] . ', ' . $row2['f3731'];
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    
}
/***
**** Действие отправки письма с инструкцией пополнения счёта
***/
if ($_REQUEST['vote_countadd'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_addcount = $_REQUEST['vote_addcount'];
    $vote_userid = $_REQUEST['vote_userid'];
    if ($vote_addcount == '') {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по ID ************/
    $record_id = 0; // Инициализируем переменную
    // Выборка из базы
    $sqlQuery = "SELECT `f3721`,`f3850`,`f3830` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $vote_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным из массива части значений строки
        $record_id = $vote_userid;
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_count = $row['f3830'];
        $smarty_addcount = $vote_addcount;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_count", $smarty_count);
        $smarty->assign("smarty_addcount", $smarty_addcount);
        // Отправляем шаблон письма
        send_template(220, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Пополнение баланса';
        $data['f4100'] = 'На Ваш e-mail отправлена инструкция пополнения баланса';
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    if ($record_id == 0) {
        echo 'Ошибка обмена данными!';
        exit;
    }
}
/***
**** Действие отправки письма с инструкцией вывода средств
***/
if ($_REQUEST['vote_countsubtract'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_subtractcount = $_REQUEST['vote_subtractcount'];
    $vote_userid = $_REQUEST['vote_userid'];
    if ($vote_subtractcount == '') {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по ID ************/
    $record_id = 0; // Инициализируем переменную
    // Выборка из базы
    $sqlQuery = "SELECT `f3721`,`f3850`,`f3830` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $vote_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным из массива части значений строки
        $record_id = $vote_userid;
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_count = $row['f3830'];
        $smarty_subtractcount = $vote_subtractcount;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_count", $smarty_count);
        $smarty->assign("smarty_subtractcount", $smarty_subtractcount);
        // Отправляем шаблон письма
        send_template(230, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Вывод средств';
        $data['f4100'] = 'На Ваш e-mail отправлена инструкция на вывод средств';
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    if ($record_id == 0) {
        echo 'Ошибка обмена данными!';
        exit;
    }
}
/***
**** Действие перевода средств со счёта на счёт участников
***/
if ($_REQUEST['vote_counttransfer'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_username = $_REQUEST['vote_username'];
    $vote_balance = $_REQUEST['vote_balance'];
    $vote_checkusername = $_REQUEST['vote_checkusername'];
    $vote_transfercount = $_REQUEST['vote_transfercount'];
    $vote_userid = $_REQUEST['vote_userid'];
    if (($vote_balance < $vote_transfercount) || ($vote_checkusername == '') || ($vote_transfercount == '')) {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по Login ************/
    $record_id = 0; // Инициализируем переменную
    //Выборка из базы ID, имя, отчества и текущего баланса получателя
    $sqlQuery = "SELECT `id`,`f3721`,`f3850`,`f3830` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $vote_checkusername . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным
        $record_id = $row['id'];
        $new_count = $row['f3830'] + $vote_transfercount; // Прибавляем к балансу получателя
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Перевод средств';
        $data['f4100'] = 'C баланса участника ' . $vote_username . ' переведено на Ваш баланс $' . $vote_transfercount;
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
        // Создаём массив части значений строки
        $data = Array();
        $data['f3830'] = $new_count;
        $data['u'] = 1;
        // Обновляем баланс получателя новым значением
        data_update(42, EVENTS_ENABLE, $data, "`id`='", $record_id, "'");
        // Присваиваем значения переменным из массива части значений строки
        $smarty_recipname = $row['f3721'];
        $smarty_recippat = $row['f3850'];
        $smarty_recipcount = $row['f3830'];
        $smarty_recipnewcount = $new_count;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_recipname", $smarty_recipname);
        $smarty->assign("smarty_recippat", $smarty_recippat);
        $smarty->assign("smarty_recipcount", $smarty_recipcount);
        $smarty->assign("smarty_recipnewcount", $smarty_recipnewcount);
    }
    if ($record_id == 0) {
        echo 'Нет такого пользователя';
        exit;
    }
    /******** Найдем нужную запись по ID ************/
    $record_id2 = 0; // Инициализируем переменную
    $sqlQuery2 = "SELECT `f1470`,`f3721`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $vote_userid . "' AND `status`=0 LIMIT 1"; // Выборка
    $result2 = sql_query($sqlQuery2) or user_error(mysql_error() . "<br>" . $sqlQuery2 . "<br>", E_USER_ERROR);
    while ($row2 = sql_fetch_assoc($result2)) {
        $record_id2 = $vote_userid;
        $new_count2 = $vote_balance - $vote_transfercount; // Вычитаем из баланса отправителя
        // Создаём массив части значений строки
        $data2 = Array();
        $data2['f4080'] = $record_id2;
        $data2['f4090'] = 'Перевод средств';
        $data2['f4100'] = 'С Вашего баланса переведено $' . $vote_transfercount . ' на баланс участника ' . $vote_checkusername;
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data2);
        // Создаём массив части значений строки
        $data2 = Array();
        $data2['f3830'] = $new_count2;
        $data2['u'] = 1;
        // Обновляем баланс получателя новым значением
        data_update(42, EVENTS_ENABLE, $data2, "`id`='", $record_id2, "'");
        // Присваиваем значения переменным из массива части значений строки
        $smarty_username = $row2['f1470'];
        $smarty_name = $row2['f3721'];
        $smarty_patronymic = $row2['f3850'];
        $smarty_count = $vote_balance;
        $smarty_checkusername = $vote_checkusername;
        $smarty_transfercount = $vote_transfercount;
        $smarty_newcount = $new_count2;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_username", $smarty_username);
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_count", $smarty_count);
        $smarty->assign("smarty_checkusername", $smarty_checkusername);
        $smarty->assign("smarty_transfercount", $smarty_transfercount);
        $smarty->assign("smarty_newcount", $smarty_newcount);
    }
    if (($record_id == 0) || ($record_id2 == 0)) {
        echo 'Ошибка';
        exit;
    }
    // Отправляем шаблоны писем участникам системы
    send_template(290, "`id`=" . $record_id);
    // Отправляем шаблоны писем участникам системы
    send_template(240, "`id`=" . $record_id2);
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    
}
/***
**** Действие смены пароля участника с отправкой ему на почту
***/
if ($_REQUEST['vote_passwordnew'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_newpassword = $_REQUEST['vote_newpassword'];
    $vote_userid = $_REQUEST['vote_userid'];
    if ($vote_newpassword == '') {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по ID ************/
    $record_id = 0; // Инициализируем переменную
    // Выборка из базы
    $sqlQuery = "SELECT `f3721`,`f3850`,`f3870` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $vote_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным из массива части значений строки
        $record_id = $vote_userid;
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_oldpassword = $row2['f3870'];
        $smarty_newpassword = $vote_newpassword;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("record_id", $record_id);
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_oldpassword", $smarty_oldpassword);
        $smarty->assign("smarty_newpassword", $smarty_newpassword);
        // Создаём массив части значений строки
        $data = Array();
        $data['f3870'] = $vote_newpassword;
        // Обновляем поля строки значениями из массива
        data_update(42, EVENTS_ENABLE, $data, "`id`='", $record_id, "'");
        // Отправляем шаблон письма
        send_template(260, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Изменение пароля';
        $data['f4100'] = 'Ваш пароль ' . $row2['f3870'] . ' был изменён на новый ******';
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    if ($record_id == 0) {
        echo 'Ошибка обмена данными!';
        exit;
    }
}
/***
**** Действие отправки сообщения от участника - администратору проекта
***/
if ($_REQUEST['vote_adminmessage'] == 1) {
    $vote_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $vote_messageadmin = $_REQUEST['vote_messageadmin'];
    $vote_userid = $_REQUEST['vote_userid'];
    if ($vote_messageadmin == '') {
        echo 'ERROR'; // Нет данных на форме
        exit;
    }
    /******** Найдем нужную запись по ID ************/
    $record_id = 0; // Инициализируем переменную
    // Выборка из базы
    $sqlQuery = "SELECT `f3721`,`f3850`,`f3840` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $vote_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значения переменным из массива части значений строки
        $record_id = $vote_userid;
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_lastname = $row2['f3840'];
        $smarty_messageadmin = $vote_messageadmin;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("record_id", $record_id);
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_lastname", $smarty_lastname);
        $smarty->assign("smarty_messageadmin", $smarty_messageadmin);
        // Отправляем шаблон письма
        send_template(250, "`id`=" . $record_id);
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $record_id;
        $data['f4090'] = 'Сообщение админу';
        $data['f4100'] = 'Произведена отправка сообщения администратору';
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
    if ($record_id == 0) {
        echo 'Ошибка обмена данными!';
        exit;
    }
}
?>