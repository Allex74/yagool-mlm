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
**** Действие обновления значений полей в базе - вход в семёрку матрицы
***/
if ($_REQUEST['matrix_gatemoney_7'] == 1) {
    $matrix_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $matrix_moneygate = $_REQUEST['matrix_moneygate'];
    $matrix_userid = $_REQUEST['matrix_userid'];
    $matrix_programname = $_REQUEST['matrix_programname'];
    /******** Найдем нужную запись по Login ************/
    $matrix_balance = 0; // Инициализируем переменную
    //Выборка из базы
    $sqlQuery = "SELECT `f1470`,`f3721`,`f3850`,`f3830`,`f3860`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matrix_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значение ID переменной
        $matrix_name = $row['f3721'];
        $matrix_patronymic = $row['f3850'];
        $matrix_balance = $row['f3830'];
        $matrix_usname = $row['f1470'];
        //Задаём условие
        if ($matrix_balance == 0) {
            echo 'Пополните Ваш баланс. На данный момент он равен нулю.';
            exit;
        } elseif ($matrix_balance < $matrix_moneygate) {
            echo 'Недостаточно средств для входа в 7-ку.';
            exit;
        } else {
            $matrix_newbalance = $matrix_balance - $matrix_moneygate; // Вычитаем стоимость входа
        }
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $matrix_userid;
        $data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
        $data['f4100'] = 'Осуществлён вход в 7-ку программы ' . $matrix_programname . '. Списано $' . $matrix_moneygate . '. Баланс $' . $matrix_newbalance;
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data); //вставляем новую запись
        // Создаём массив части значений строки
//------------------------ Вычисление переходов участников ------------------------------------
        $data3 = Array();
        if ($matrix_programname == 'БТ-1') { //Вычисление для программы БТ-1
            $pn = 1; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4320`,`f5010`,`f5020` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4320'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4320`,`f5010`,`f5020` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4320'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4320`,`f5010`,`f5020` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4320'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4320`,`f5010`,`f5020` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4320'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5020'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4730" => $matsev['f5350'], "f4930" => "", "f5020" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                        $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        //отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №1';
                                            $smarty_calc = '$168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-1 $100
                                            $sum = $m7['f3830'] + 100;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "`id`=", $m7['id']); 
                                        } 
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5020'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $descm['id']), "id='", $matrix_userid,"'");
                                        //отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $descm['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №1';
                                            $smarty_calc = '$168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-1 $100
                                            $sum = $m7['f3830'] + 100;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "`id`=", $m7['id']); 
                                        } 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4320'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5020'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4730" => $matsev['f5350'], "f4930" => "", "f5020" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                    $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    //отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                    $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                    $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                    while ($m7 = sql_fetch_assoc($m7_ass)) {
                                        $smarty_name = $m7['f3721'];
                                        $smarty_patr = $m7['f3850'];
                                        $smarty_lnames = 'Благотворительная №1';
                                        $smarty_calc = '$168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                        $smarty->assign("smarty_name", $smarty_name);
                                        $smarty->assign("smarty_patr", $smarty_patr);
                                        $smarty->assign("smarty_lnames", $smarty_lnames);
                                        $smarty->assign("smarty_calc", $smarty_calc);
                                        send_template(340, "`id`=" . $m7['id']);
                                        //занесение в хронику событий о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                        $data = Array();
                                        $data['f4080'] = $m7['id'];
                                        $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                        $data['f4100'] = 'Реинвест. ИТОГО: $168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                        data_insert(280, EVENTS_ENABLE, $data);
                                        //зачисление на счёт реинвестированного в БТ-1 $100
                                        $sum = $m7['f3830'] + 100;
                                        data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "`id`=", $m7['id']); 
                                    } 
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5020'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4320'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5020'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4730" => $matsev['f5350'], "f4930" => "", "f5020" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Благотворительная №1';
                                    $smarty_calc = '$168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в БТ-1 $100
                                    $sum = $m7['f3830'] + 100;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "`id`=", $m7['id']); 
                                } 
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5020'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4320'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5020'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4730" => $matsev['f5350'], "f4930" => "", "f5020" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            //отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                            $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                            $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                            while ($m7 = sql_fetch_assoc($m7_ass)) {
                                $smarty_name = $m7['f3721'];
                                $smarty_patr = $m7['f3850'];
                                $smarty_lnames = 'Благотворительная №1';
                                $smarty_calc = '$168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                $smarty->assign("smarty_name", $smarty_name);
                                $smarty->assign("smarty_patr", $smarty_patr);
                                $smarty->assign("smarty_lnames", $smarty_lnames);
                                $smarty->assign("smarty_calc", $smarty_calc);
                                send_template(340, "`id`=" . $m7['id']);
                                //занесение в хронику событий о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
                                $data = Array();
                                $data['f4080'] = $m7['id'];
                                $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                $data['f4100'] = 'Реинвест. ИТОГО: $168 начислено - $42 реинвест - $26 благо = $100 зачислено';
                                data_insert(280, EVENTS_ENABLE, $data);
                                //зачисление на счёт реинвестированного в БТ-1 $100
                                $sum = $m7['f3830'] + 100;
                                data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "`id`=", $m7['id']); 
                            } 
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5020'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Благотворительная №1';
            $data3['f4320'] = 2;
        } elseif ($matrix_programname == 'БТ-2') {
            $pn = 2; //присваиваем значение переменной
           //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4330`,`f5010`,`f5030` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4330'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4330`,`f5010`,`f5030` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4330'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4330`,`f5010`,`f5030` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4330'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4330`,`f5010`,`f5030` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4330'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5030'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4740" => $matsev['f5350'], "f4940" => "", "f5030" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                        $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        //отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №2';
                                            $smarty_calc = '$800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-2 $500
                                            $sum = $m7['f3830'] + 500;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                        } 
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5030'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        //отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $descm['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №2';
                                            $smarty_calc = '$800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-2 $500
                                            $sum = $m7['f3830'] + 500;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                                        } 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4330'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5030'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4740" => $matsev['f5350'], "f4940" => "", "f5030" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                    $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                   //отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                    $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                    $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                    while ($m7 = sql_fetch_assoc($m7_ass)) {
                                        $smarty_name = $m7['f3721'];
                                        $smarty_patr = $m7['f3850'];
                                        $smarty_lnames = 'Благотворительная №2';
                                        $smarty_calc = '$800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                        $smarty->assign("smarty_name", $smarty_name);
                                        $smarty->assign("smarty_patr", $smarty_patr);
                                        $smarty->assign("smarty_lnames", $smarty_lnames);
                                        $smarty->assign("smarty_calc", $smarty_calc);
                                        send_template(340, "`id`=" . $m7['id']);
                                        //занесение в хронику событий о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                        $data = Array();
                                        $data['f4080'] = $m7['id'];
                                        $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                        $data['f4100'] = 'Реинвест. ИТОГО: $800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                        data_insert(280, EVENTS_ENABLE, $data);
                                        //зачисление на счёт реинвестированного в БТ-2 $500
                                        $sum = $m7['f3830'] + 500;
                                        data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                    } 
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5030'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4330'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5030'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4740" => $matsev['f5350'], "f4940" => "", "f5030" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Благотворительная №2';
                                    $smarty_calc = '$800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в БТ-2 $500
                                    $sum = $m7['f3830'] + 500;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                } 
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5030'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4330'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5030'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4740" => $matsev['f5350'], "f4940" => "", "f5030" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            //отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                            $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                            $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                            while ($m7 = sql_fetch_assoc($m7_ass)) {
                                $smarty_name = $m7['f3721'];
                                $smarty_patr = $m7['f3850'];
                                $smarty_lnames = 'Благотворительная №2';
                                $smarty_calc = '$800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                $smarty->assign("smarty_name", $smarty_name);
                                $smarty->assign("smarty_patr", $smarty_patr);
                                $smarty->assign("smarty_lnames", $smarty_lnames);
                                $smarty->assign("smarty_calc", $smarty_calc);
                                send_template(340, "`id`=" . $m7['id']);
                                //занесение в хронику событий о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
                                $data = Array();
                                $data['f4080'] = $m7['id'];
                                $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                $data['f4100'] = 'Реинвест. ИТОГО: $800 начислено - $200 реинвест - $100 благо = $500 зачислено';
                                data_insert(280, EVENTS_ENABLE, $data);
                                //зачисление на счёт реинвестированного в БТ-2 $500
                                $sum = $m7['f3830'] + 500;
                                data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                            } 
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5030'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Благотворительная №2';
            $data3['f4330'] = 2;
        } elseif ($matrix_programname == 'БТ-3') {
            $pn = 3; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4340`,`f5010`,`f5040` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4340'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4340`,`f5010`,`f5040` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4340'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4340`,`f5010`,`f5040` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4340'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4340`,`f5010`,`f5040` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4340'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5040'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4750" => $matsev['f5350'], "f4950" => "", "f5040" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                        $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        //отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №3';
                                            $smarty_calc = '$4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-3 $2500
                                            $sum = $m7['f3830'] + 2500;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                        } 
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5040'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        //отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $descm['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Благотворительная №3';
                                            $smarty_calc = '$4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в БТ-3 $2500
                                            $sum = $m7['f3830'] + 2500;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                        } 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4340'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5040'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4750" => $matsev['f5350'], "f4950" => "", "f5040" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                    $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    //отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                    $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                    $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                    while ($m7 = sql_fetch_assoc($m7_ass)) {
                                        $smarty_name = $m7['f3721'];
                                        $smarty_patr = $m7['f3850'];
                                        $smarty_lnames = 'Благотворительная №3';
                                        $smarty_calc = '$4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                        $smarty->assign("smarty_name", $smarty_name);
                                        $smarty->assign("smarty_patr", $smarty_patr);
                                        $smarty->assign("smarty_lnames", $smarty_lnames);
                                        $smarty->assign("smarty_calc", $smarty_calc);
                                        send_template(340, "`id`=" . $m7['id']);
                                        //занесение в хронику событий о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                        $data = Array();
                                        $data['f4080'] = $m7['id'];
                                        $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                        $data['f4100'] = 'Реинвест. ИТОГО: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                        data_insert(280, EVENTS_ENABLE, $data);
                                        //зачисление на счёт реинвестированного в БТ-3 $2500
                                        $sum = $m7['f3830'] + 2500;
                                        data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                    } 
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5040'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4340'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5040'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4750" => $matsev['f5350'], "f4950" => "", "f5040" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Благотворительная №3';
                                    $smarty_calc = '$4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в БТ-3 $2500
                                    $sum = $m7['f3830'] + 2500;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                } 
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5040'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4340'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5040'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4750" => $matsev['f5350'], "f4950" => "", "f5040" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            //отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                            $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                            $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                            while ($m7 = sql_fetch_assoc($m7_ass)) {
                                $smarty_name = $m7['f3721'];
                                $smarty_patr = $m7['f3850'];
                                $smarty_lnames = 'Благотворительная №3';
                                $smarty_calc = '$4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                $smarty->assign("smarty_name", $smarty_name);
                                $smarty->assign("smarty_patr", $smarty_patr);
                                $smarty->assign("smarty_lnames", $smarty_lnames);
                                $smarty->assign("smarty_calc", $smarty_calc);
                                send_template(340, "`id`=" . $m7['id']);
                                //занесение в хронику событий о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
                                $data = Array();
                                $data['f4080'] = $m7['id'];
                                $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                $data['f4100'] = 'Реинвест. ИТОГО: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено';
                                data_insert(280, EVENTS_ENABLE, $data);
                                //зачисление на счёт реинвестированного в БТ-3 $2500
                                $sum = $m7['f3830'] + 2500;
                                data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                            }
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5040'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Благотворительная №3';
            $data3['f4340'] = 2;
        } elseif ($matrix_programname == 'АБТ') {
            $pn = 4; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4350`,`f5010`,`f5050` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4350'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4350`,`f5010`,`f5050` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4350'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4350`,`f5010`,`f5050` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4350'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4350`,`f5010`,`f5050` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4350'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5050'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4760" => $matsev['f5350'], "f4960" => "", "f5050" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                        $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        //отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Автомобильно-Благотворительная';
                                            $smarty_calc = '$20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в АБТ $14000
                                            $sum = $m7['f3830'] + 14000;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                        }
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5050'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $descm['id']), "id='", $matrix_userid,"'");
                                        //отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $descm['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Автомобильно-Благотворительная';
                                            $smarty_calc = '$20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в АБТ $14000
                                            $sum = $m7['f3830'] + 14000;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                        }
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4350'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5050'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4760" => $matsev['f5350'], "f4960" => "", "f5050" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                    $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    //отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                    $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                    $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                    while ($m7 = sql_fetch_assoc($m7_ass)) {
                                        $smarty_name = $m7['f3721'];
                                        $smarty_patr = $m7['f3850'];
                                        $smarty_lnames = 'Автомобильно-Благотворительная';
                                        $smarty_calc = '$20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                        $smarty->assign("smarty_name", $smarty_name);
                                        $smarty->assign("smarty_patr", $smarty_patr);
                                        $smarty->assign("smarty_lnames", $smarty_lnames);
                                        $smarty->assign("smarty_calc", $smarty_calc);
                                        send_template(340, "`id`=" . $m7['id']);
                                        //занесение в хронику событий о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                        $data = Array();
                                        $data['f4080'] = $m7['id'];
                                        $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                        $data['f4100'] = 'Реинвест. ИТОГО: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                        data_insert(280, EVENTS_ENABLE, $data);
                                        //зачисление на счёт реинвестированного в АБТ $14000
                                        $sum = $m7['f3830'] + 14000;
                                        data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                    }
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5050'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4350'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5050'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4760" => $matsev['f5350'], "f4960" => "", "f5050" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                               $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Автомобильно-Благотворительная';
                                    $smarty_calc = '$20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в АБТ $14000
                                    $sum = $m7['f3830'] + 14000;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                }
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5050'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4350'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5050'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4760" => $matsev['f5350'], "f4960" => "", "f5050" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Автомобильно-Благотворительная';
                                    $smarty_calc = '$20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в АБТ $14000
                                    $sum = $m7['f3830'] + 14000;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']); 
                                }
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5050'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Автомобильно-Благотворительная';
            $data3['f4350'] = 2;
        } elseif ($matrix_programname == 'ЖБТ') {
            $pn = 5; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4360`,`f5010`,`f5060` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4360'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4360`,`f5010`,`f5060` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4360'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4360`,`f5010`,`f5060` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4360'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4360`,`f5010`,`f5060` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4360'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5060'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4770" => $matsev['f5350'], "f4970" => "", "f5060" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                           $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        //отправка сообщения реинвестированному о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Жилищно-Благотворительная';
                                            $smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в ЖБТ $77000
                                            $sum = $m7['f3830'] + 77000;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                                        }
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5060'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $descm['id']), "id='", $matrix_userid,"'");
                                        //отправка сообщения реинвестированному о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                        $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $descm['f4550'] . "' AND `status`=0 LIMIT 1";
                                        $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                        while ($m7 = sql_fetch_assoc($m7_ass)) {
                                            $smarty_name = $m7['f3721'];
                                            $smarty_patr = $m7['f3850'];
                                            $smarty_lnames = 'Жилищно-Благотворительная';
                                            $smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                            $smarty->assign("smarty_name", $smarty_name);
                                            $smarty->assign("smarty_patr", $smarty_patr);
                                            $smarty->assign("smarty_lnames", $smarty_lnames);
                                            $smarty->assign("smarty_calc", $smarty_calc);
                                            send_template(340, "`id`=" . $m7['id']);
                                            //занесение в хронику событий о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                            $data = Array();
                                            $data['f4080'] = $m7['id'];
                                            $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                            $data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                            data_insert(280, EVENTS_ENABLE, $data);
                                            //зачисление на счёт реинвестированного в ЖБТ $77000
                                            $sum = $m7['f3830'] + 77000;
                                            data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                                        } 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4360'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5060'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4770" => $matsev['f5350'], "f4970" => "", "f5060" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                       $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    //отправка сообщения реинвестированному о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                    $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                    $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                    while ($m7 = sql_fetch_assoc($m7_ass)) {
                                        $smarty_name = $m7['f3721'];
                                        $smarty_patr = $m7['f3850'];
                                        $smarty_lnames = 'Жилищно-Благотворительная';
                                        $smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                        $smarty->assign("smarty_name", $smarty_name);
                                        $smarty->assign("smarty_patr", $smarty_patr);
                                        $smarty->assign("smarty_lnames", $smarty_lnames);
                                        $smarty->assign("smarty_calc", $smarty_calc);
                                        send_template(340, "`id`=" . $m7['id']);
                                        //занесение в хронику событий о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                        $data = Array();
                                        $data['f4080'] = $m7['id'];
                                        $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                        $data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                        data_insert(280, EVENTS_ENABLE, $data);
                                        //зачисление на счёт реинвестированного в ЖБТ $77000
                                        $sum = $m7['f3830'] + 77000;
                                        data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                                    }
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5060'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4360'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5060'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4770" => $matsev['f5350'], "f4970" => "", "f5060" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                   $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                //отправка сообщения реинвестированному о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                                $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                                while ($m7 = sql_fetch_assoc($m7_ass)) {
                                    $smarty_name = $m7['f3721'];
                                    $smarty_patr = $m7['f3850'];
                                    $smarty_lnames = 'Жилищно-Благотворительная';
                                    $smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                    $smarty->assign("smarty_name", $smarty_name);
                                    $smarty->assign("smarty_patr", $smarty_patr);
                                    $smarty->assign("smarty_lnames", $smarty_lnames);
                                    $smarty->assign("smarty_calc", $smarty_calc);
                                    send_template(340, "`id`=" . $m7['id']);
                                    //занесение в хронику событий о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                    $data = Array();
                                    $data['f4080'] = $m7['id'];
                                    $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                    $data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                    data_insert(280, EVENTS_ENABLE, $data);
                                    //зачисление на счёт реинвестированного в ЖБТ $77000
                                    $sum = $m7['f3830'] + 77000;
                                    data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                                }
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5060'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4360'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350`,`f4550` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5060'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4770" => $matsev['f5350'], "f4970" => "", "f5060" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            //отправка сообщения реинвестированному о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                            $m7_arr = "SELECT `id`,`f3721`,`f3830`,`f3850` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matsev['f4550'] . "' AND `status`=0 LIMIT 1";
                            $m7_ass = sql_query($m7_arr) or user_error(mysql_error() . "<br>" . $m7_arr . "<br>", E_USER_ERROR);
                            while ($m7 = sql_fetch_assoc($m7_ass)) {
                                $smarty_name = $m7['f3721'];
                                $smarty_patr = $m7['f3850'];
                                $smarty_lnames = 'Жилищно-Благотворительная';
                                $smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                $smarty->assign("smarty_name", $smarty_name);
                                $smarty->assign("smarty_patr", $smarty_patr);
                                $smarty->assign("smarty_lnames", $smarty_lnames);
                                $smarty->assign("smarty_calc", $smarty_calc);
                                send_template(340, "`id`=" . $m7['id']);
                                //занесение в хронику событий о начислении и вычитам: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено
                                $data = Array();
                                $data['f4080'] = $m7['id'];
                                $data['f4090'] = 'Реинвест в ' . $matrix_programname;
                                $data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
                                data_insert(280, EVENTS_ENABLE, $data);
                                //зачисление на счёт реинвестированного в ЖБТ $77000
                                $sum = $m7['f3830'] + 77000;
                                data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
                            }
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5060'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Жилищно-Благотворительная';
            $data3['f4360'] = 2;
        } elseif ($matrix_programname == 'ДБТ') {
            $pn = 6; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4370`,`f5010`,`f5070` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4370'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4370`,`f5010`,`f5070` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4370'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4370`,`f5010`,`f5070` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        //если в семёрке спонсора спонсора спонсора нет...
                        if ($sp_vtroyke['f4370'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4370`,`f5010`,`f5070` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            if ($sp_vtroyke2['f4370'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5070'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4780" => $matsev['f5350'], "f5070" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                           $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5070'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4370" => 2, "f4780" => 4, "f5070" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4370" => 2, "f4780" => 5, "f5070" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4370" => 2, "f4780" => 6, "f5070" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4370" => 2, "f4780" => 7, "f5070" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4370'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5070'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4780" => $matsev['f5350'], "f5070" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                       $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5070'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4370'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5070'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4780" => $matsev['f5350'], "f5070" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                   $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5070'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4370'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5070'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4780" => $matsev['f5350'], "f5070" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5070'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Доверительно-Благотворительная';
            $data3['f4370'] = 2;
        } elseif ($matrix_programname == 'МБТ') {
            $pn = 7; //присваиваем значение переменной
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
            $sponsor = "SELECT `f4380`,`f5010`,`f5080` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $row['f5010'] . "' AND `status`=0 LIMIT 1";
            $res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
                /******** смотрим в какой матрице стоит спонсор ************/
                if ($rowsp['f4380'] == 1) {
                    //если в семёрке спонсора нет, тогда смотрим где стоит спонсор спонсора
                    //для этого узнаём его ID...
                    $namespons = "SELECT `f4380`,`f5010`,`f5080` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['f5010'] . "' AND `status`=0 LIMIT 1";
                    $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                    $nmsp = sql_fetch_assoc($namesp);
                    //если в семёрке спонсора спонсора нет...
                    if ($nmsp['f4380'] == 1) {
                        //тогда смотрим, где стоит спонсор спонсора спонсора
                        //для этого узнаём его ID...
                        $spvtroyke = "SELECT `f4380`,`f5010`,`f5080` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $nmsp['f5010'] . "' AND `status`=0 LIMIT 1";
                        $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                        $sp_vtroyke = sql_fetch_assoc($vtroyke);
                        if ($sp_vtroyke['f4380'] == 1) {
                            //тогда смотрим, где стоит спонсор спонсора спонсора
                            //для этого узнаём его ID...
                            $spvtroyke2 = "SELECT `f4380`,`f5010`,`f5080` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $sp_vtroyke['f5010'] . "' AND `status`=0 LIMIT 1";
                            $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" .    $spvtroyke2 . "<br>", E_USER_ERROR);
                            $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
                            //если в семёрке спонсора спонсора спонсора нет...
                            if ($sp_vtroyke2['f4380'] == 2) {
                                //если стоит в семёрке, узнаём её ID и делаем запрос
                                $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke2['f5080'] . "' AND `status`=0 LIMIT 1";
                                $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                                while ($matsev = sql_fetch_assoc($vsemerke)) {
                                    data_update(42, EVENTS_ENABLE, array("f4790" => $matsev['f5350'], "f5080" => $matsev['id']), "id='", $matrix_userid,"'");
                                    //пишем в таблицу Семёрки логин текущего Участника
                                    $data2 = Array();
                                    if ($matsev['f5350'] == 4) {
                                        $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 5) {
                                        $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 6) {
                                           $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                    } elseif ($matsev['f5350'] == 7) {
                                        $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                    }
                                    data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke2['f5080'], "'"); //обновляем данные
                                }
                            } else {
                                //Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
                                $matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
                                $mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
                                while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
                                    if($descm['f5350'] == 4) {
                                        data_update(42, EVENTS_ENABLE, array("f4380" => 2, "f4790" => 4, "f5080" => $descm['id']), "id='", $matrix_userid,"'");
                                        data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 5) {
                                        data_update(42, EVENTS_ENABLE, array("f4380" => 2, "f4790" => 5, "f5080" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 6) {
                                        data_update(42, EVENTS_ENABLE, array("f4380" => 2, "f4790" => 6, "f5080" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_usname), "`id`=", $descm['id']);
                                    } elseif($descm['f5350'] == 7) {
                                        data_update(42, EVENTS_ENABLE, array("f4380" => 2, "f4790" => 7, "f5080" => $descm['id']), "id='", $matrix_userid,"'"); 
                                        data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_usname), "`id`=", $descm['id']);
                                    }
                                }
                            }
                        } elseif ($sp_vtroyke['f4380'] == 2) { //или уже всё таки в семёрке
                             //если стоит в семёрке, узнаём её ID и делаем запрос
                            $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $sp_vtroyke['f5080'] . "' AND `status`=0 LIMIT 1";
                            $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                            while ($matsev = sql_fetch_assoc($vsemerke)) {
                                data_update(42, EVENTS_ENABLE, array("f4790" => $matsev['f5350'], "f5080" => $matsev['id']), "id='", $matrix_userid,"'");
                                //пишем в таблицу Семёрки логин текущего Участника
                                $data2 = Array();
                                if ($matsev['f5350'] == 4) {
                                    $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 5) {
                                    $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 6) {
                                       $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                                } elseif ($matsev['f5350'] == 7) {
                                    $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                                }
                                data_update(320, EVENTS_ENABLE, $data2, "`id`='", $sp_vtroyke['f5080'], "'"); //обновляем данные
                            } 
                        }
                    } elseif ($nmsp['f4380'] == 2) { //или уже всё таки в семёрке
                        //если стоит в семёрке, узнаём её ID и делаем запрос
                        $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $nmsp['f5080'] . "' AND `status`=0 LIMIT 1";
                        $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                        while ($matsev = sql_fetch_assoc($vsemerke)) {
                            data_update(42, EVENTS_ENABLE, array("f4790" => $matsev['f5350'], "f5080" => $matsev['id']), "id='", $matrix_userid,"'");
                            //пишем в таблицу Семёрки логин текущего Участника
                            $data2 = Array();
                            if ($matsev['f5350'] == 4) {
                                $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 5) {
                                $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 6) {
                                   $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                            } elseif ($matsev['f5350'] == 7) {
                                $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                            }
                            data_update(320, EVENTS_ENABLE, $data2, "`id`='", $nmsp['f5080'], "'"); //обновляем данные
                        }
                    }
                } elseif ($rowsp['f4380'] == 2) {
                    //если стоит в семёрке, узнаём её ID и делаем запрос
                    $spvsemerke = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `id`='" . $rowsp['f5080'] . "' AND `status`=0 LIMIT 1";
                    $vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
                    while ($matsev = sql_fetch_assoc($vsemerke)) {
                        data_update(42, EVENTS_ENABLE, array("f4790" => $matsev['f5350'], "f5080" => $matsev['id']), "id='", $matrix_userid,"'");
                        //пишем в таблицу Семёрки логин текущего Участника
                        $data2 = Array();
                        if ($matsev['f5350'] == 4) {
                            $data2['f4620'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 5) {
                            $data2['f4630'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 6) {
                            $data2['f4640'] = $matrix_usname; //пишем логин текущего Участника
                        } elseif ($matsev['f5350'] == 7) {
                            $data2['f4650'] = $matrix_usname; //пишем логин текущего Участника
                        }
                        data_update(320, EVENTS_ENABLE, $data2, "`id`='", $rowsp['f5080'], "'"); //обновляем данные
                    }
                }
            }
            $matrix_longnames = 'Меценатская Благотворительная';
            $data3['f4380'] = 2;
        }
//---------------------- Вычисление переходов участников - КОНЕЦ ---------------------------
        $data3['f3830'] = $matrix_balance - $matrix_moneygate;
        $data3['u'] = 1;
        // Обновляем поля строки значениями из массива
        data_update(42, EVENTS_ENABLE, $data3, "`id`='", $matrix_userid, "'"); //обновляем данные
        // Присваиваем значения переменным из массива части значений строки
        $smarty_name = $row['f3721'];
        $smarty_patronymic = $row['f3850'];
        $smarty_longnames = $matrix_longnames;
        $smarty_moneygate = $matrix_moneygate;
        $smarty_newbalance = $matrix_newbalance;
        // Передаём значения Smarty в шаблон письма
        $smarty->assign("smarty_name", $smarty_name);
        $smarty->assign("smarty_patronymic", $smarty_patronymic);
        $smarty->assign("smarty_longnames", $smarty_longnames);
        $smarty->assign("smarty_moneygate", $smarty_moneygate);
        $smarty->assign("smarty_newbalance", $smarty_newbalance);
        // Отправляем шаблон письма
        send_template(320, "`id`=" . $matrix_userid);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
}
?>