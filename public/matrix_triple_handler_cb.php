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
**** Действие обновления значений полей в базе - вход в тройку матрицы
***/
if ($_REQUEST['matrix_gatemoney_3'] == 1) {
    $matrix_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $matrix_moneygate = $_REQUEST['matrix_moneygate'];
	$matrix_userid = $_REQUEST['matrix_userid'];
	$matrix_programname = $_REQUEST['matrix_programname'];
    /******** Найдем нужную запись по Login ************/
    $matrix_balance = 0; // Инициализируем переменную
    //Выборка из базы
    $sqlQuery = "SELECT `f1470`,`f3721`,`f3850`,`f3830`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $matrix_userid . "' AND `status`=0 LIMIT 1";
    $result = sql_query($sqlQuery) or user_error(mysql_error() . "<br>" . $sqlQuery . "<br>", E_USER_ERROR);
    while ($row = sql_fetch_assoc($result)) {
        // Присваиваем значение ID переменной
        $matrix_name = $row['f3721'];
		$matrix_patronymic = $row['f3850'];
		$matrix_balance = $row['f3830'];
		$matrix_spname = $row['f3860'];
		$matrix_usname = $row['f1470'];
		//Задаём условие
        if ($matrix_balance == 0) {
            echo 'Пополните Ваш баланс. На данный момент он равен нулю.';
            exit;
		} elseif ($matrix_balance < $matrix_moneygate) {
            echo 'Недостаточно средств для входа в 3-ку.';
            exit;
        } else {
		    $matrix_newbalance = $matrix_balance - $matrix_moneygate; // Вычитаем стоимость входа
		}
        // Создаём массив части значений строки
        $data = Array();
        $data['f4080'] = $matrix_userid;
        $data['f4090'] = 'Вход в 3-ку ' . $matrix_programname;
        $data['f4100'] = 'Осуществлён вход в 3-ку программы ' . $matrix_programname . '. Списано $' . $matrix_moneygate . '. Баланс $' . $matrix_newbalance;
        // Добавляем в таблицу новую запись значениями из массива
        data_insert(280, EVENTS_ENABLE, $data);
        // Создаём массив части значений строки
        $data = Array();
//------------------------ Вычисление переходов участников ------------------------------------
		if ($matrix_programname == 'БТ-1') { //Вычисление для программы БТ-1
		    $pn = 1; //присваиваем значение переменной
            $position = 0; // Инициализируем переменную
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
			$sponsor = "SELECT `id`,`f1470`,`f3860`,`f4730`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $matrix_spname . "' AND `f4320`=1 AND `status`=0 LIMIT 1";
			$res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
			    $position = $rowsp['f4730'];
				/******** смотрим в каком месте матрицы стоит спонсор - если спонсор стоит на вершине, тогда... ************/
                if($position == 1) {
		            //...делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4260` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    //смотрим занято ли второе место, если не занято...
					    if($fb['f4260'] == "") {
							$data['f4320'] = 1; //в поле БТ-1 пишем статус отображения Тройки
                            $data['f4730'] = 2; //то встаём во второе
							$data['f4930'] = $fb['id']; //пишем в таблицу Участники в поле IDТ-1 ID связанной матрицы-тройки
							$data['f5020'] = ""; //сбрасываем поле IDС-1 - связь с матрицей-семёркой
							//пишем в таблицу Тройки в поле М-2 свой ID
							$data2 = Array();
							$data2['f4260'] = $matrix_usname;
							data_update(62, EVENTS_ENABLE, $data2, "`id`='", $fb['id'], "'");
						}
						//если занято второе - встаём в третье и матрица делится: создаются две новые
						//верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
					    elseif($fb['f4260'] !== "") {//по этому условию создаём две новые матрицы, а старую удаляем
						    //подбираем Спонсору матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                    //для этого узнаём логин его спонсора...
				            $namespons = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['id'] . "' AND `status`=0 LIMIT 1";
			                $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                            $nmsp = sql_fetch_assoc($namesp);
							//определяем, стоит ли спонсор ещё в тройке... 
							$spvtroyke = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                            $sp_vtroyke = sql_fetch_assoc($vtroyke);
							if($sp_vtroyke['f4930'] !== "") {
							    //запрос в базу...
							    $next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
                                $sp_next = sql_fetch_assoc($nextsp);
							    //определяем, стоит ли спонсор спонсора ещё в тройке... 
							    $spvtroyke2 = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
                                $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
								if($sp_vtroyke2['f4930'] !== "") {
							        //запрос в базу...
							        $next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
                                    $sp_next2 = sql_fetch_assoc($nextsp2);
							        //определяем, стоит ли спонсор спонсора спонсора ещё в тройке... 
							        $spvtroyke3 = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
                                    $sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								    if($sp_vtroyke3['f4930'] !== "") {
										//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
										$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
										$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
										while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
											if($descm['f5350'] == 4) {
												data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $descm['id']), "id='", $rowsp['id'],"'");
												data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 5) {
												data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 6) {
												data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 7) {
												data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_spname), "`id`=", $descm['id']);
											}
										}
								    } else { //или уже всё таки в семёрке
							            $approv_sp = $sp_next2['f3860'];
							        }
								} else { //или уже всё таки в семёрке
							        $approv_sp = $sp_next['f3860'];
							    }
							} else { //или уже всё таки в семёрке
							    $approv_sp = $nmsp['f3860'];
							}
							if($approv_sp) {
								//в таблице Семёрки ищем его по логину, т.к. перейти спонсор может только в ту семёрку, где стоит его спонсор
								$spvsemerke = "SELECT `id`,`f4570`,`f4580`,`f4590`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=1 AND `status`=0 LIMIT 1";
								$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
								while ($matsev = sql_fetch_assoc($vsemerke)) {
									//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4620'] == "") {
										$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
										$data['f4730'] = 4; //пишем в таблицу Спонсора в поле М-1 4-е место, куда поставили
										$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
										$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-4 логин Спонсора
										$data2 = Array();
										$data2['f4620'] = $rowsp['f1470']; //в поле М-4 пишем логин Спонсора
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если четвёртое место занято...
									elseif($matsev['f4620'] !== "") {
										//смотрим пятое место, если не занято - ставим Спонсора сюда
										if($matsev['f4630'] == "") {
											$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
											$data['f4730'] = 5; //пишем в таблицу Участники в поле М-1 5-е место, куда поставили
											$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
											$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-5 логин Спонсора
											$data2 = Array();
											$data2['f4630'] = $rowsp['f1470']; //в поле М-5 пишем логин Спонсора
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//если пятое место тоже занято...
										elseif($matsev['f4630'] !== "") {
											//смотрим шестое место, если не занято - ставим Спонсора сюда
											if($matsev['f4640'] == "") {
												$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
												$data['f4730'] = 6; //пишем в таблицу Участники в поле М-1 6-е место, куда поставили
												$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
												$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4640'] = $rowsp['f1470']; //в поле М-6 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
											//но если и шестое место занято - ставим Спонсора в седьмое и запускаем деление матрицы
											elseif($matsev['f4640'] !== "") {
												$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
												$data['f4730'] = 7; //пишем в таблицу Участники в поле М-1 7-е место, куда поставили
												$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
												$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4650'] = $rowsp['f1470']; //в поле М-7 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
										}
									}
								}
							}
							//создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
							$data = Array();
							$data['f4710'] = 1; //присваиваем ID записи таблицы Программы - БТ-1
							$data['f4250'] = $fb['f4260'];
							data_insert(62, EVENTS_ENABLE, $data);
							//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
							$data2 = Array();
							$data2['f4710'] = 1; //присваиваем ID записи таблицы Программы - БТ-1
							$data2['f4250'] = $matrix_usname;
							data_insert(62, EVENTS_ENABLE, $data2);
							//узнаём ID первой новой матрицы
				            $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $fb['f4260'] . "' AND `f4710`=1 AND `status`=0 LIMIT 1";
			                $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                            while ($matrix1 = sql_fetch_assoc($newmat1)) {
							    //переписываем поля М-1 и IDТ-1 Участника
								$data = Array();
								$data['f4320'] = 1; //в поле БТ-1 пишем статус отображения Тройки
                                $data['f4730'] = 1; //указываем место на вершине матрицы
							    $data['f4930'] = $matrix1['id']; //в поле IDТ-1 пишем ID его новой матрицы-тройки
								$data['f5020'] = ""; //сбрасываем поле IDС-1 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $fb['f4260'], "'");
							}
							//узнаём ID второй новой матрицы
				            $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=1 AND `status`=0 LIMIT 1";
			                $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                            while ($matrix2 = sql_fetch_assoc($newmat2)) {
							    //переписываем поля М-1 и IDТ-1 Участника
								$data = Array();
								$data['f4320'] = 1; //в поле БТ-1 пишем статус отображения Тройки
                                $data['f4730'] = 1; //указываем место на вершине матрицы
							    $data['f4930'] = $matrix2['id']; //в поле IDТ-1 пишем ID его новой матрицы-тройки
								$data['f5020'] = ""; //сбрасываем поле IDС-1 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
							}
							//удаляем поделившуюся матрицу
							data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
						}
					}
				}
				/******** а если спонсор стоит во втором месте - встаём в третье и матрица делится: создаются две новые ************/
				elseif($position == 2) {
				    //верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
		            //делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4250` FROM `" . DATA_TABLE . "62` WHERE `f4260`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    $finthree = $fb['f4250']; //назначаем переменную - логин верхнего в тройке
						//подбираем верхнему Участнику матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                //для этого узнаём логин его спонсора...
				        $namespons = "SELECT `id`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $finthree . "' AND `status`=0 LIMIT 1";
			            $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                        $nmsp = sql_fetch_assoc($namesp);
						//определяем, стоит ли спонсор ещё в тройке... 
						$spvtroyke = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
						$vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
						$sp_vtroyke = sql_fetch_assoc($vtroyke);
						if($sp_vtroyke['f4930'] !== "") {
							//запрос в базу...
							$next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
							$nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
							$sp_next = sql_fetch_assoc($nextsp);
							//определяем, стоит ли спонсор спонсора ещё в тройке... 
							$spvtroyke2 = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
							$vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
							$sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
							if($sp_vtroyke2['f4930'] !== "") {
								//запрос в базу...
								$next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
								$nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
								$sp_next2 = sql_fetch_assoc($nextsp2);
								//определяем, стоит ли спонсор спонсора ещё в тройке... 
								$spvtroyke3 = "SELECT `f4930` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
								$vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
								$sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								if($sp_vtroyke3['f4930'] !== "") {
									//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
									$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
									$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
									while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
										if($descm['f5350'] == 4) {
											data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $descm['id']), "id='", $nmsp['id'],"'");
											data_update(320, EVENTS_ENABLE, array("f4620" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 5) {
											data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4630" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 6) {
											data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4640" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 7) {
											data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4650" => $finthree), "`id`=", $descm['id']);
										}
									}
								} else { //или уже всё таки в семёрке
									$approv_sp = $sp_next2['f3860'];
								}
							} else { //или уже всё таки в семёрке
								$approv_sp = $sp_next['f3860'];
							}
						} else { //или уже всё таки в семёрке
							$approv_sp = $nmsp['f3860'];
						}
						if($approv_sp) {
							//в таблице Семёрки ищем его по логину, т.к. перейти Участник может только в ту семёрку, где стоит его спонсор
							$spvsemerke = "SELECT `id`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=1 AND `status`=0 LIMIT 1";
							$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
							while ($matsev = sql_fetch_assoc($vsemerke)) {
								//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
								if($matsev['f4620'] == "") {
									$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
									$data['f4730'] = 4; //пишем в таблицу Участники в поле М-1 4-е место, куда поставили
									$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
									$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
									data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
									//пишем в таблицу Семёрки в поле М-4 логин верхнего Участника
									$data2 = Array();
									$data2['f4620'] = $finthree; //в поле М-4 пишем логин верхнего Участника
									data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
								}
								//если четвёртое место занято...
								elseif($matsev['f4620'] !== "") {
									//смотрим пятое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4630'] == "") {
										$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
										$data['f4730'] = 5; //пишем в таблицу Участники в поле М-1 5-е место, куда поставили
										$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
										$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-5 логин верхнего Участника
										$data2 = Array();
										$data2['f4630'] = $finthree; //в поле М-5 пишем логин верхнего Участника
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если пятое место тоже занято...
									elseif($matsev['f4630'] !== "") {
										//смотрим шестое место, если не занято - ставим верхнего Участника сюда
										if($matsev['f4640'] == "") {
											$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
											$data['f4730'] = 6; //пишем в таблицу Участники в поле М-1 6-е место, куда поставили
											$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
											$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-6 логин верхнего Участника
											$data2 = Array();
											$data2['f4640'] = $finthree; //в поле М-6 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//но если и шестое место занято, ставим верхнего Участника в 7-е - матрица делится с реинвестом верхнего Участника
										elseif($matsev['f4640'] !== "") {
											$data['f4320'] = 2; //в поле БТ-1 пишем статус отображения Семёрки
											$data['f4730'] = 7; //пишем в таблицу Участники в поле М-1 7-е место, куда поставили
											$data['f4930'] = ""; //сбрасываем поле IDТ-1 - связь с матрицей-тройкой
											$data['f5020'] = $matsev['id']; //в поле IDС-1 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-7 логин верхнего Участника
											$data2 = Array();
											$data2['f4650'] = $finthree; //в поле М-7 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
									}
								}
							}
						}
					    //создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
						$data = Array();
						$data['f4710'] = 1; //присваиваем ID записи таблицы Программы - БТ-1
						$data['f4250'] = $rowsp['f1470'];
						data_insert(62, EVENTS_ENABLE, $data);
						//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
						$data2 = Array();
						$data2['f4710'] = 1; //присваиваем ID записи таблицы Программы - БТ-1
						$data2['f4250'] = $matrix_usname;
						data_insert(62, EVENTS_ENABLE, $data2);
						//узнаём ID первой новой матрицы
				        $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `f4710`=1 AND `status`=0 LIMIT 1";
			            $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                        while ($matrix1 = sql_fetch_assoc($newmat1)) {
							//переписываем поля М-1 и IDТ-1 левого Участника
						    $data = Array();
							$data['f4320'] = 1; //в поле БТ-1 пишем статус отображения Тройки
                            $data['f4730'] = 1; //указываем место на вершине матрицы
							$data['f4930'] = $matrix1['id']; //в поле IDТ-1 пишем ID его новой матрицы-тройки
							$data['f5020'] = ""; //сбрасываем поле IDС-1 - связь с матрицей-семёркой
						    data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'");
						}
						//узнаём ID второй новой матрицы
				        $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=1 AND `status`=0 LIMIT 1";
			            $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                        while ($matrix2 = sql_fetch_assoc($newmat2)) {
							//переписываем поля М-1 и IDТ-1 правого Участника
							$data = Array();
							$data['f4320'] = 1; //в поле БТ-1 пишем статус отображения Тройки
                            $data['f4730'] = 1; //указываем место на вершине матрицы
							$data['f4930'] = $matrix2['id']; //в поле IDТ-1 пишем ID его новой матрицы-тройки
							$data['f5020'] = ""; //сбрасываем поле IDС-1 - связь с матрицей-семёркой
							data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
						}
						//удаляем поделившуюся матрицу
						data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
					}
                }
			    $matrix_longnames = 'Благотворительная №1';
			}
			if($position == "") {
				$matrix_longnames = 'Благотворительная №1';
				$data['f4320'] = 3; //присваиваем значение полю БТ-1 таблицы Участники 
                echo 'Ваш спонсор отсутствует в тройке!';
            }
		} elseif ($matrix_programname == 'БТ-2') {
		    $pn = 2; //присваиваем значение переменной
            $position = 0; // Инициализируем переменную
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
			$sponsor = "SELECT `id`,`f1470`,`f3860`,`f4740`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $matrix_spname . "' AND `f4330`=1 AND `status`=0 LIMIT 1";
			$res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
			    $position = $rowsp['f4740'];
				/******** смотрим в каком месте матрицы стоит спонсор - если спонсор стоит на вершине, тогда... ************/
                if($position == 1) {
		            //...делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4260` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    //смотрим занято ли второе место, если не занято...
					    if($fb['f4260'] == "") {
							$data['f4330'] = 1; //в поле БТ-2 пишем статус отображения Тройки
                            $data['f4740'] = 2; //то встаём во второе
							$data['f4940'] = $fb['id']; //пишем в таблицу Участники в поле IDТ-2 ID связанной матрицы-тройки
							$data['f5030'] = ""; //сбрасываем поле IDС-2 - связь с матрицей-семёркой
							//пишем в таблицу Тройки в поле М-2 свой ID
							$data2 = Array();
							$data2['f4260'] = $matrix_usname;
							data_update(62, EVENTS_ENABLE, $data2, "`id`='", $fb['id'], "'");
						}
						//если занято второе - встаём в третье и матрица делится: создаются две новые
						//верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
					    elseif($fb['f4260'] !== "") {//по этому условию создаём две новые матрицы, а старую удаляем
						    //подбираем Спонсору матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                    //для этого узнаём логин его спонсора...
				            $namespons = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['id'] . "' AND `status`=0 LIMIT 1";
			                $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                            $nmsp = sql_fetch_assoc($namesp);
							//определяем, стоит ли спонсор ещё в тройке... 
							$spvtroyke = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                            $sp_vtroyke = sql_fetch_assoc($vtroyke);
							if($sp_vtroyke['f4940'] !== "") {
							    //запрос в базу...
							    $next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
                                $sp_next = sql_fetch_assoc($nextsp);
							    //определяем, стоит ли спонсор спонсора ещё в тройке... 
							    $spvtroyke2 = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
                                $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
								if($sp_vtroyke2['f4940'] !== "") {
							        //запрос в базу...
							        $next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
                                    $sp_next2 = sql_fetch_assoc($nextsp2);
							        //определяем, стоит ли спонсор спонсора спонсора ещё в тройке... 
							        $spvtroyke3 = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
                                    $sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								    if($sp_vtroyke3['f4940'] !== "") {
										//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
										$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
										$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
										while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
											if($descm['f5350'] == 4) {
												data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $descm['id']), "id='", $rowsp['id'],"'");
												data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 5) {
												data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 6) {
												data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 7) {
												data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_spname), "`id`=", $descm['id']);
											}
										}
								    } else { //или уже всё таки в семёрке
							            $approv_sp = $sp_next2['f3860'];
							        }
								} else { //или уже всё таки в семёрке
							        $approv_sp = $sp_next['f3860'];
							    }
							} else { //или уже всё таки в семёрке
							    $approv_sp = $nmsp['f3860'];
							}
							if($approv_sp) {
								//в таблице Семёрки ищем его по логину, т.к. перейти спонсор может только в ту семёрку, где стоит его спонсор
								$spvsemerke = "SELECT `id`,`f4570`,`f4580`,`f4590`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=2 AND `status`=0 LIMIT 1";
								$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
								while ($matsev = sql_fetch_assoc($vsemerke)) {
									//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4620'] == "") {
										$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
										$data['f4740'] = 4; //пишем в таблицу Спонсора  4-е место, куда поставили
										$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
										$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-4 логин Спонсора
										$data2 = Array();
										$data2['f4620'] = $rowsp['f1470']; //в поле М-4 пишем логин Спонсора
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если четвёртое место занято...
									elseif($matsev['f4620'] !== "") {
										//смотрим пятое место, если не занято - ставим Спонсора сюда
										if($matsev['f4630'] == "") {
											$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
											$data['f4740'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
											$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
											$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-5 логин Спонсора
											$data2 = Array();
											$data2['f4630'] = $rowsp['f1470']; //в поле М-5 пишем логин Спонсора
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//если пятое место тоже занято...
										elseif($matsev['f4630'] !== "") {
											//смотрим шестое место, если не занято - ставим Спонсора сюда
											if($matsev['f4640'] == "") {
												$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
												$data['f4740'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
												$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
												$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4640'] = $rowsp['f1470']; //в поле М-6 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
											//но если и шестое место занято - ставим Спонсора в седьмое и запускаем деление матрицы
											elseif($matsev['f4640'] !== "") {
												$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
												$data['f4740'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
												$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
												$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4650'] = $rowsp['f1470']; //в поле М-7 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
										}
									}
								}
							}
							//создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
							$data = Array();
							$data['f4710'] = 2; //присваиваем ID записи таблицы Программы - БТ-2
							$data['f4250'] = $fb['f4260'];
							data_insert(62, EVENTS_ENABLE, $data);
							//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
							$data2 = Array();
							$data2['f4710'] = 2; //присваиваем ID записи таблицы Программы - БТ-2
							$data2['f4250'] = $matrix_usname;
							data_insert(62, EVENTS_ENABLE, $data2);
							//узнаём ID первой новой матрицы
				            $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $fb['f4260'] . "' AND `f4710`=2 AND `status`=0 LIMIT 1";
			                $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                            while ($matrix1 = sql_fetch_assoc($newmat1)) {
							    //переписываем поля М-1 и IDТ-2 Участника
								$data = Array();
								$data['f4330'] = 1; //в поле БТ-2 пишем статус отображения Тройки
                                $data['f4740'] = 1; //указываем место на вершине матрицы
							    $data['f4940'] = $matrix1['id']; //в поле IDТ-2 пишем ID его новой матрицы-тройки
								$data['f5030'] = ""; //сбрасываем поле IDС-2 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $fb['f4260'], "'");
							}
							//узнаём ID второй новой матрицы
				            $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=2 AND `status`=0 LIMIT 1";
			                $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                            while ($matrix2 = sql_fetch_assoc($newmat2)) {
							    //переписываем поля М-1 и IDТ-2 Участника
								$data = Array();
								$data['f4330'] = 1; //в поле БТ-2 пишем статус отображения Тройки
                                $data['f4740'] = 1; //указываем место на вершине матрицы
							    $data['f4940'] = $matrix2['id']; //в поле IDТ-2 пишем ID его новой матрицы-тройки
								$data['f5030'] = ""; //сбрасываем поле IDС-2 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
							}
							//удаляем поделившуюся матрицу
							data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
						}
					}
				}
				/******** а если спонсор стоит во втором месте - встаём в третье и матрица делится: создаются две новые ************/
				elseif($position == 2) {
				    //верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
		            //делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4250` FROM `" . DATA_TABLE . "62` WHERE `f4260`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    $finthree = $fb['f4250']; //назначаем переменную - логин верхнего в тройке
						//подбираем верхнему Участнику матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                //для этого узнаём логин его спонсора...
				        $namespons = "SELECT `id`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $finthree . "' AND `status`=0 LIMIT 1";
			            $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                        $nmsp = sql_fetch_assoc($namesp);
						//определяем, стоит ли спонсор ещё в тройке... 
						$spvtroyke = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
						$vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
						$sp_vtroyke = sql_fetch_assoc($vtroyke);
						if($sp_vtroyke['f4940'] !== "") {
							//запрос в базу...
							$next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
							$nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
							$sp_next = sql_fetch_assoc($nextsp);
							//определяем, стоит ли спонсор спонсора ещё в тройке... 
							$spvtroyke2 = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
							$vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
							$sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
							if($sp_vtroyke2['f4940'] !== "") {
								//запрос в базу...
								$next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
								$nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
								$sp_next2 = sql_fetch_assoc($nextsp2);
								//определяем, стоит ли спонсор спонсора ещё в тройке... 
								$spvtroyke3 = "SELECT `f4940` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
								$vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
								$sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								if($sp_vtroyke3['f4940'] !== "") {
									//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
									$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
									$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
									while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
										if($descm['f5350'] == 4) {
											data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $descm['id']), "id='", $nmsp['id'],"'");
											data_update(320, EVENTS_ENABLE, array("f4620" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 5) {
											data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4630" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 6) {
											data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4640" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 7) {
											data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4650" => $finthree), "`id`=", $descm['id']);
										}
									}
								} else { //или уже всё таки в семёрке
									$approv_sp = $sp_next2['f3860'];
								}
							} else { //или уже всё таки в семёрке
								$approv_sp = $sp_next['f3860'];
							}
						} else { //или уже всё таки в семёрке
							$approv_sp = $nmsp['f3860'];
						}
						if($approv_sp) {
							//в таблице Семёрки ищем его по логину, т.к. перейти Участник может только в ту семёрку, где стоит его спонсор
							$spvsemerke = "SELECT `id`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=2 AND `status`=0 LIMIT 1";
							$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
							while ($matsev = sql_fetch_assoc($vsemerke)) {
								//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
								if($matsev['f4620'] == "") {
									$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
									$data['f4740'] = 4; //пишем в таблицу Участники  4-е место, куда поставили
									$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
									$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
									data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
									//пишем в таблицу Семёрки в поле М-4 логин верхнего Участника
									$data2 = Array();
									$data2['f4620'] = $finthree; //в поле М-4 пишем логин верхнего Участника
									data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
								}
								//если четвёртое место занято...
								elseif($matsev['f4620'] !== "") {
									//смотрим пятое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4630'] == "") {
										$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
										$data['f4740'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
										$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
										$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-5 логин верхнего Участника
										$data2 = Array();
										$data2['f4630'] = $finthree; //в поле М-5 пишем логин верхнего Участника
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если пятое место тоже занято...
									elseif($matsev['f4630'] !== "") {
										//смотрим шестое место, если не занято - ставим верхнего Участника сюда
										if($matsev['f4640'] == "") {
											$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
											$data['f4740'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
											$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
											$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-6 логин верхнего Участника
											$data2 = Array();
											$data2['f4640'] = $finthree; //в поле М-6 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//но если и шестое место занято, ставим верхнего Участника в 7-е - матрица делится с реинвестом верхнего Участника
										elseif($matsev['f4640'] !== "") {
											$data['f4330'] = 2; //в поле БТ-2 пишем статус отображения Семёрки
											$data['f4740'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
											$data['f4940'] = ""; //сбрасываем поле IDТ-2 - связь с матрицей-тройкой
											$data['f5030'] = $matsev['id']; //в поле IDС-2 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-7 логин верхнего Участника
											$data2 = Array();
											$data2['f4650'] = $finthree; //в поле М-7 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
									}
								}
							}
                        }
					    //создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
						$data = Array();
						$data['f4710'] = 2; //присваиваем ID записи таблицы Программы - БТ-2
						$data['f4250'] = $rowsp['f1470'];
						data_insert(62, EVENTS_ENABLE, $data);
						//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
						$data2 = Array();
						$data2['f4710'] = 2; //присваиваем ID записи таблицы Программы - БТ-2
						$data2['f4250'] = $matrix_usname;
						data_insert(62, EVENTS_ENABLE, $data2);
						//узнаём ID первой новой матрицы
				        $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `f4710`=2 AND `status`=0 LIMIT 1";
			            $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                        while ($matrix1 = sql_fetch_assoc($newmat1)) {
							//переписываем поля М-1 и IDТ-2 левого Участника
						    $data = Array();
							$data['f4330'] = 1; //в поле БТ-2 пишем статус отображения Тройки
                            $data['f4740'] = 1; //указываем место на вершине матрицы
							$data['f4940'] = $matrix1['id']; //в поле IDТ-2 пишем ID его новой матрицы-тройки
							$data['f5030'] = ""; //сбрасываем поле IDС-2 - связь с матрицей-семёркой
						    data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'");
						}
						//узнаём ID второй новой матрицы
				        $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=2 AND `status`=0 LIMIT 1";
			            $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                        while ($matrix2 = sql_fetch_assoc($newmat2)) {
							//переписываем поля М-1 и IDТ-2 правого Участника
							$data = Array();
							$data['f4330'] = 1; //в поле БТ-2 пишем статус отображения Тройки
                            $data['f4740'] = 1; //указываем место на вершине матрицы
							$data['f4940'] = $matrix2['id']; //в поле IDТ-2 пишем ID его новой матрицы-тройки
							$data['f5030'] = ""; //сбрасываем поле IDС-2 - связь с матрицей-семёркой
							data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
						}
						//удаляем поделившуюся матрицу
						data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
					}
                }
			    $matrix_longnames = 'Благотворительная №2';
			}
			if($position == "") {
				$matrix_longnames = 'Благотворительная №2';
				$data['f4330'] = 3; //присваиваем значение полю БТ-2 таблицы Участники 
                echo 'Ваш спонсор отсутствует в тройке!';
            }
		} elseif ($matrix_programname == 'БТ-3') {
		    $pn = 3; //присваиваем значение переменной
            $position = 0; // Инициализируем переменную
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
			$sponsor = "SELECT `id`,`f1470`,`f3860`,`f4750`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $matrix_spname . "' AND `f4340`=1 AND `status`=0 LIMIT 1";
			$res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
			    $position = $rowsp['f4750'];
				/******** смотрим в каком месте матрицы стоит спонсор - если спонсор стоит на вершине, тогда... ************/
                if($position == 1) {
		            //...делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4260` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    //смотрим занято ли второе место, если не занято...
					    if($fb['f4260'] == "") {
							$data['f4340'] = 1; //в поле БТ-3 пишем статус отображения Тройки
                            $data['f4750'] = 2; //то встаём во второе
							$data['f4950'] = $fb['id']; //пишем в таблицу Участники в поле IDТ-3 ID связанной матрицы-тройки
							$data['f5040'] = ""; //сбрасываем поле IDС-3 - связь с матрицей-семёркой
							//пишем в таблицу Тройки в поле М-2 свой ID
							$data2 = Array();
							$data2['f4260'] = $matrix_usname;
							data_update(62, EVENTS_ENABLE, $data2, "`id`='", $fb['id'], "'");
						}
						//если занято второе - встаём в третье и матрица делится: создаются две новые
						//верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
					    elseif($fb['f4260'] !== "") {//по этому условию создаём две новые матрицы, а старую удаляем
						    //подбираем Спонсору матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                    //для этого узнаём логин его спонсора...
				            $namespons = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['id'] . "' AND `status`=0 LIMIT 1";
			                $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                            $nmsp = sql_fetch_assoc($namesp);
							//определяем, стоит ли спонсор ещё в тройке... 
							$spvtroyke = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                            $sp_vtroyke = sql_fetch_assoc($vtroyke);
							if($sp_vtroyke['f4950'] !== "") {
							    //запрос в базу...
							    $next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
                                $sp_next = sql_fetch_assoc($nextsp);
							    //определяем, стоит ли спонсор спонсора ещё в тройке... 
							    $spvtroyke2 = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
                                $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
								if($sp_vtroyke2['f4950'] !== "") {
							        //запрос в базу...
							        $next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
                                    $sp_next2 = sql_fetch_assoc($nextsp2);
							        //определяем, стоит ли спонсор спонсора спонсора ещё в тройке... 
							        $spvtroyke3 = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
                                    $sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								    if($sp_vtroyke3['f4950'] !== "") {
										//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
										$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
										$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
										while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
											if($descm['f5350'] == 4) {
												data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $descm['id']), "id='", $rowsp['id'],"'");
												data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 5) {
												data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 6) {
												data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 7) {
												data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_spname), "`id`=", $descm['id']);
											}
										}
								    } else { //или уже всё таки в семёрке
							            $approv_sp = $sp_next2['f3860'];
							        }
								} else { //или уже всё таки в семёрке
							        $approv_sp = $sp_next['f3860'];
							    }
							} else { //или уже всё таки в семёрке
							    $approv_sp = $nmsp['f3860'];
							}
							if($approv_sp) {
								//в таблице Семёрки ищем его по логину, т.к. перейти спонсор может только в ту семёрку, где стоит его спонсор
								$spvsemerke = "SELECT `id`,`f4570`,`f4580`,`f4590`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=3 AND `status`=0 LIMIT 1";
								$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
								while ($matsev = sql_fetch_assoc($vsemerke)) {
									//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4620'] == "") {
										$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
										$data['f4750'] = 4; //пишем в таблицу Спонсора  4-е место, куда поставили
										$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
										$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-4 логин Спонсора
										$data2 = Array();
										$data2['f4620'] = $rowsp['f1470']; //в поле М-4 пишем логин Спонсора
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если четвёртое место занято...
									elseif($matsev['f4620'] !== "") {
										//смотрим пятое место, если не занято - ставим Спонсора сюда
										if($matsev['f4630'] == "") {
											$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
											$data['f4750'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
											$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
											$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-5 логин Спонсора
											$data2 = Array();
											$data2['f4630'] = $rowsp['f1470']; //в поле М-5 пишем логин Спонсора
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//если пятое место тоже занято...
										elseif($matsev['f4630'] !== "") {
											//смотрим шестое место, если не занято - ставим Спонсора сюда
											if($matsev['f4640'] == "") {
												$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
												$data['f4750'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
												$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
												$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4640'] = $rowsp['f1470']; //в поле М-6 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
											//но если и шестое место занято - ставим Спонсора в седьмое и запускаем деление матрицы
											elseif($matsev['f4640'] !== "") {
												$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
												$data['f4750'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
												$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
												$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4650'] = $rowsp['f1470']; //в поле М-7 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
										}
									}
								}
                            }
							//создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
							$data = Array();
							$data['f4710'] = 3; //присваиваем ID записи таблицы Программы - БТ-3
							$data['f4250'] = $fb['f4260'];
							data_insert(62, EVENTS_ENABLE, $data);
							//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
							$data2 = Array();
							$data2['f4710'] = 3; //присваиваем ID записи таблицы Программы - БТ-3
							$data2['f4250'] = $matrix_usname;
							data_insert(62, EVENTS_ENABLE, $data2);
							//узнаём ID первой новой матрицы
				            $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $fb['f4260'] . "' AND `f4710`=3 AND `status`=0 LIMIT 1";
			                $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                            while ($matrix1 = sql_fetch_assoc($newmat1)) {
							    //переписываем поля М-1 и IDТ-3 Участника
								$data = Array();
								$data['f4340'] = 1; //в поле БТ-3 пишем статус отображения Тройки
                                $data['f4750'] = 1; //указываем место на вершине матрицы
							    $data['f4950'] = $matrix1['id']; //в поле IDТ-3 пишем ID его новой матрицы-тройки
								$data['f5040'] = ""; //сбрасываем поле IDС-3 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $fb['f4260'], "'");
							}
							//узнаём ID второй новой матрицы
				            $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=3 AND `status`=0 LIMIT 1";
			                $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                            while ($matrix2 = sql_fetch_assoc($newmat2)) {
							    //переписываем поля М-1 и IDТ-3 Участника
								$data = Array();
								$data['f4340'] = 1; //в поле БТ-3 пишем статус отображения Тройки
                                $data['f4750'] = 1; //указываем место на вершине матрицы
							    $data['f4950'] = $matrix2['id']; //в поле IDТ-3 пишем ID его новой матрицы-тройки
								$data['f5040'] = ""; //сбрасываем поле IDС-3 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
							}
							//удаляем поделившуюся матрицу
							data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
						}
					}
				}
				/******** а если спонсор стоит во втором месте - встаём в третье и матрица делится: создаются две новые ************/
				elseif($position == 2) {
				    //верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
		            //делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4250` FROM `" . DATA_TABLE . "62` WHERE `f4260`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    $finthree = $fb['f4250']; //назначаем переменную - логин верхнего в тройке

						//подбираем верхнему Участнику матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                //для этого узнаём логин его спонсора...
				        $namespons = "SELECT `id`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $finthree . "' AND `status`=0 LIMIT 1";
			            $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                        $nmsp = sql_fetch_assoc($namesp);
						//определяем, стоит ли спонсор ещё в тройке... 
						$spvtroyke = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
						$vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
						$sp_vtroyke = sql_fetch_assoc($vtroyke);
						if($sp_vtroyke['f4950'] !== "") {
							//запрос в базу...
							$next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
							$nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
							$sp_next = sql_fetch_assoc($nextsp);
							//определяем, стоит ли спонсор спонсора ещё в тройке... 
							$spvtroyke2 = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
							$vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
							$sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
							if($sp_vtroyke2['f4950'] !== "") {
								//запрос в базу...
								$next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
								$nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
								$sp_next2 = sql_fetch_assoc($nextsp2);
								//определяем, стоит ли спонсор спонсора ещё в тройке... 
								$spvtroyke3 = "SELECT `f4950` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
								$vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
								$sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								if($sp_vtroyke3['f4950'] !== "") {
									//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
									$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
									$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
									while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
										if($descm['f5350'] == 4) {
											data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $descm['id']), "id='", $nmsp['id'],"'");
											data_update(320, EVENTS_ENABLE, array("f4620" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 5) {
											data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4630" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 6) {
											data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4640" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 7) {
											data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4650" => $finthree), "`id`=", $descm['id']);
										}
									}
								} else { //или уже всё таки в семёрке
									$approv_sp = $sp_next2['f3860'];
								}
							} else { //или уже всё таки в семёрке
								$approv_sp = $sp_next['f3860'];
							}
						} else { //или уже всё таки в семёрке
							$approv_sp = $nmsp['f3860'];
						}
						if($approv_sp) {
							//в таблице Семёрки ищем его по логину, т.к. перейти Участник может только в ту семёрку, где стоит его спонсор
							$spvsemerke = "SELECT `id`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=3 AND `status`=0 LIMIT 1";
							$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
							while ($matsev = sql_fetch_assoc($vsemerke)) {
								
								//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
								if($matsev['f4620'] == "") {
									$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
									$data['f4750'] = 4; //пишем в таблицу Участники  4-е место, куда поставили
									$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
									$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
									data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
									//пишем в таблицу Семёрки в поле М-4 логин верхнего Участника
									$data2 = Array();
									$data2['f4620'] = $finthree; //в поле М-4 пишем логин верхнего Участника
									data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
								}
								//если четвёртое место занято...
								elseif($matsev['f4620'] !== "") {
									//смотрим пятое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4630'] == "") {
										$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
										$data['f4750'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
										$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
										$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-5 логин верхнего Участника
										$data2 = Array();
										$data2['f4630'] = $finthree; //в поле М-5 пишем логин верхнего Участника
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если пятое место тоже занято...
									elseif($matsev['f4630'] !== "") {
										//смотрим шестое место, если не занято - ставим верхнего Участника сюда
										if($matsev['f4640'] == "") {
											$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
											$data['f4750'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
											$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
											$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-6 логин верхнего Участника
											$data2 = Array();
											$data2['f4640'] = $finthree; //в поле М-6 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//но если и шестое место занято, ставим верхнего Участника в 7-е - матрица делится с реинвестом верхнего Участника
										elseif($matsev['f4640'] !== "") {
											$data['f4340'] = 2; //в поле БТ-3 пишем статус отображения Семёрки
											$data['f4750'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
											$data['f4950'] = ""; //сбрасываем поле IDТ-3 - связь с матрицей-тройкой
											$data['f5040'] = $matsev['id']; //в поле IDС-3 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-7 логин верхнего Участника
											$data2 = Array();
											$data2['f4650'] = $finthree; //в поле М-7 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
									}
								}
							}
						}
					    //создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
						$data = Array();
						$data['f4710'] = 3; //присваиваем ID записи таблицы Программы - БТ-3
						$data['f4250'] = $rowsp['f1470'];
						data_insert(62, EVENTS_ENABLE, $data);
						//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
						$data2 = Array();
						$data2['f4710'] = 3; //присваиваем ID записи таблицы Программы - БТ-3
						$data2['f4250'] = $matrix_usname;
						data_insert(62, EVENTS_ENABLE, $data2);
						//узнаём ID первой новой матрицы
				        $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `f4710`=3 AND `status`=0 LIMIT 1";
			            $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                        while ($matrix1 = sql_fetch_assoc($newmat1)) {
							//переписываем поля М-1 и IDТ-3 левого Участника
						    $data = Array();
							$data['f4340'] = 1; //в поле БТ-3 пишем статус отображения Тройки
                            $data['f4750'] = 1; //указываем место на вершине матрицы
							$data['f4950'] = $matrix1['id']; //в поле IDТ-3 пишем ID его новой матрицы-тройки
							$data['f5040'] = ""; //сбрасываем поле IDС-3 - связь с матрицей-семёркой
						    data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'");
						}
						//узнаём ID второй новой матрицы
				        $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=3 AND `status`=0 LIMIT 1";
			            $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                        while ($matrix2 = sql_fetch_assoc($newmat2)) {
							//переписываем поля М-1 и IDТ-3 правого Участника
							$data = Array();
							$data['f4340'] = 1; //в поле БТ-3 пишем статус отображения Тройки
                            $data['f4750'] = 1; //указываем место на вершине матрицы
							$data['f4950'] = $matrix2['id']; //в поле IDТ-3 пишем ID его новой матрицы-тройки
							$data['f5040'] = ""; //сбрасываем поле IDС-3 - связь с матрицей-семёркой
							data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
						}
						//удаляем поделившуюся матрицу
						data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
					}
                }
			    $matrix_longnames = 'Благотворительная №3';
			}
			if($position == "") {
				$matrix_longnames = 'Благотворительная №3';
				$data['f4340'] = 3; //присваиваем значение полю БТ-3 таблицы Участники 
                echo 'Ваш спонсор отсутствует в тройке!';
            }
		} elseif ($matrix_programname == 'АБТ') {
		    $pn = 4; //присваиваем значение переменной
            $position = 0; // Инициализируем переменную
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
			$sponsor = "SELECT `id`,`f1470`,`f3860`,`f4760`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $matrix_spname . "' AND `f4350`=1 AND `status`=0 LIMIT 1";
			$res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
			    $position = $rowsp['f4760'];
				/******** смотрим в каком месте матрицы стоит спонсор - если спонсор стоит на вершине, тогда... ************/
                if($position == 1) {
		            //...делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4260` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    //смотрим занято ли второе место, если не занято...
					    if($fb['f4260'] == "") {
							$data['f4350'] = 1; //в поле АБТ пишем статус отображения Тройки
                            $data['f4760'] = 2; //то встаём во второе
							$data['f4960'] = $fb['id']; //пишем в таблицу Участники в поле IDТ-4 ID связанной матрицы-тройки
							$data['f5050'] = ""; //сбрасываем поле IDС-4 - связь с матрицей-семёркой
							//пишем в таблицу Тройки в поле М-2 свой ID
							$data2 = Array();
							$data2['f4260'] = $matrix_usname;
							data_update(62, EVENTS_ENABLE, $data2, "`id`='", $fb['id'], "'");
						}
						//если занято второе - встаём в третье и матрица делится: создаются две новые
						//верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
					    elseif($fb['f4260'] !== "") {//по этому условию создаём две новые матрицы, а старую удаляем
						    //подбираем Спонсору матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                    //для этого узнаём логин его спонсора...
				            $namespons = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['id'] . "' AND `status`=0 LIMIT 1";
			                $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                            $nmsp = sql_fetch_assoc($namesp);
							//определяем, стоит ли спонсор ещё в тройке... 
							$spvtroyke = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                            $sp_vtroyke = sql_fetch_assoc($vtroyke);
							if($sp_vtroyke['f4960'] !== "") {
							    //запрос в базу...
							    $next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
                                $sp_next = sql_fetch_assoc($nextsp);
							    //определяем, стоит ли спонсор спонсора ещё в тройке... 
							    $spvtroyke2 = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
                                $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
								if($sp_vtroyke2['f4960'] !== "") {
							        //запрос в базу...
							        $next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
                                    $sp_next2 = sql_fetch_assoc($nextsp2);
							        //определяем, стоит ли спонсор спонсора спонсора ещё в тройке... 
							        $spvtroyke3 = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
                                    $sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								    if($sp_vtroyke3['f4960'] !== "") {
										//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
										$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
										$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
										while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
											if($descm['f5350'] == 4) {
												data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $descm['id']), "id='", $rowsp['id'],"'");
												data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 5) {
												data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 6) {
												data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 7) {
												data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_spname), "`id`=", $descm['id']);
											}
										}
								    } else { //или уже всё таки в семёрке
							            $approv_sp = $sp_next2['f3860'];
							        }
								} else { //или уже всё таки в семёрке
							        $approv_sp = $sp_next['f3860'];
							    }
							} else { //или уже всё таки в семёрке
							    $approv_sp = $nmsp['f3860'];
							}
							if($approv_sp) {
								//в таблице Семёрки ищем его по логину, т.к. перейти спонсор может только в ту семёрку, где стоит его спонсор
								$spvsemerke = "SELECT `id`,`f4570`,`f4580`,`f4590`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=4 AND `status`=0 LIMIT 1";
								$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
								while ($matsev = sql_fetch_assoc($vsemerke)) {
									//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4620'] == "") {
										$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
										$data['f4760'] = 4; //пишем в таблицу Спонсора  4-е место, куда поставили
										$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
										$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-4 логин Спонсора
										$data2 = Array();
										$data2['f4620'] = $rowsp['f1470']; //в поле М-4 пишем логин Спонсора
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если четвёртое место занято...
									elseif($matsev['f4620'] !== "") {
										//смотрим пятое место, если не занято - ставим Спонсора сюда
										if($matsev['f4630'] == "") {
											$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
											$data['f4760'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
											$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
											$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-5 логин Спонсора
											$data2 = Array();
											$data2['f4630'] = $rowsp['f1470']; //в поле М-5 пишем логин Спонсора
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//если пятое место тоже занято...
										elseif($matsev['f4630'] !== "") {
											//смотрим шестое место, если не занято - ставим Спонсора сюда
											if($matsev['f4640'] == "") {
												$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
												$data['f4760'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
												$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
												$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4640'] = $rowsp['f1470']; //в поле М-6 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
											//но если и шестое место занято - ставим Спонсора в седьмое и запускаем деление матрицы
											elseif($matsev['f4640'] !== "") {
												$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
												$data['f4760'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
												$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
												$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4650'] = $rowsp['f1470']; //в поле М-7 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
										}
									}
								}
						    }
							//создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
							$data = Array();
							$data['f4710'] = 4; //присваиваем ID записи таблицы Программы - АБТ
							$data['f4250'] = $fb['f4260'];
							data_insert(62, EVENTS_ENABLE, $data);
							//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
							$data2 = Array();
							$data2['f4710'] = 4; //присваиваем ID записи таблицы Программы - АБТ
							$data2['f4250'] = $matrix_usname;
							data_insert(62, EVENTS_ENABLE, $data2);
							//узнаём ID первой новой матрицы
				            $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $fb['f4260'] . "' AND `f4710`=4 AND `status`=0 LIMIT 1";
			                $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                            while ($matrix1 = sql_fetch_assoc($newmat1)) {
							    //переписываем поля М-1 и IDТ-4 Участника
								$data = Array();
								$data['f4350'] = 1; //в поле АБТ пишем статус отображения Тройки
                                $data['f4760'] = 1; //указываем место на вершине матрицы
							    $data['f4960'] = $matrix1['id']; //в поле IDТ-4 пишем ID его новой матрицы-тройки
								$data['f5050'] = ""; //сбрасываем поле IDС-4 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $fb['f4260'], "'");
							}
							//узнаём ID второй новой матрицы
				            $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=4 AND `status`=0 LIMIT 1";
			                $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                            while ($matrix2 = sql_fetch_assoc($newmat2)) {
							    //переписываем поля М-1 и IDТ-4 Участника
								$data = Array();
								$data['f4350'] = 1; //в поле АБТ пишем статус отображения Тройки
                                $data['f4760'] = 1; //указываем место на вершине матрицы
							    $data['f4960'] = $matrix2['id']; //в поле IDТ-4 пишем ID его новой матрицы-тройки
								$data['f5050'] = ""; //сбрасываем поле IDС-4 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
							}
							//удаляем поделившуюся матрицу
							data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
						}
					}
				}
				/******** а если спонсор стоит во втором месте - встаём в третье и матрица делится: создаются две новые ************/
				elseif($position == 2) {
				    //верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
		            //делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4250` FROM `" . DATA_TABLE . "62` WHERE `f4260`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    $finthree = $fb['f4250']; //назначаем переменную - логин верхнего в тройке

						//подбираем верхнему Участнику матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                //для этого узнаём логин его спонсора...
				        $namespons = "SELECT `id`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $finthree . "' AND `status`=0 LIMIT 1";
			            $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                        $nmsp = sql_fetch_assoc($namesp);
						//определяем, стоит ли спонсор ещё в тройке... 
						$spvtroyke = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
						$vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
						$sp_vtroyke = sql_fetch_assoc($vtroyke);
						if($sp_vtroyke['f4960'] !== "") {
							//запрос в базу...
							$next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
							$nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
							$sp_next = sql_fetch_assoc($nextsp);
							//определяем, стоит ли спонсор спонсора ещё в тройке... 
							$spvtroyke2 = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
							$vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
							$sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
							if($sp_vtroyke2['f4960'] !== "") {
								//запрос в базу...
								$next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
								$nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
								$sp_next2 = sql_fetch_assoc($nextsp2);
								//определяем, стоит ли спонсор спонсора ещё в тройке... 
								$spvtroyke3 = "SELECT `f4960` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
								$vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
								$sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								if($sp_vtroyke3['f4960'] !== "") {
									//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
									$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
									$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
									while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
										if($descm['f5350'] == 4) {
											data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $descm['id']), "id='", $nmsp['id'],"'");
											data_update(320, EVENTS_ENABLE, array("f4620" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 5) {
											data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4630" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 6) {
											data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4640" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 7) {
											data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4650" => $finthree), "`id`=", $descm['id']);
										}
									}
								} else { //или уже всё таки в семёрке
									$approv_sp = $sp_next2['f3860'];
								}
							} else { //или уже всё таки в семёрке
								$approv_sp = $sp_next['f3860'];
							}
						} else { //или уже всё таки в семёрке
							$approv_sp = $nmsp['f3860'];
						}
						if($approv_sp) {
							//в таблице Семёрки ищем его по логину, т.к. перейти Участник может только в ту семёрку, где стоит его спонсор
							$spvsemerke = "SELECT `id`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=4 AND `status`=0 LIMIT 1";
							$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
							while ($matsev = sql_fetch_assoc($vsemerke)) {
								
								//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
								if($matsev['f4620'] == "") {
									$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
									$data['f4760'] = 4; //пишем в таблицу Участники  4-е место, куда поставили
									$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
									$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
									data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
									//пишем в таблицу Семёрки в поле М-4 логин верхнего Участника
									$data2 = Array();
									$data2['f4620'] = $finthree; //в поле М-4 пишем логин верхнего Участника
									data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
								}
								//если четвёртое место занято...
								elseif($matsev['f4620'] !== "") {
									//смотрим пятое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4630'] == "") {
										$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
										$data['f4760'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
										$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
										$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-5 логин верхнего Участника
										$data2 = Array();
										$data2['f4630'] = $finthree; //в поле М-5 пишем логин верхнего Участника
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если пятое место тоже занято...
									elseif($matsev['f4630'] !== "") {
										//смотрим шестое место, если не занято - ставим верхнего Участника сюда
										if($matsev['f4640'] == "") {
											$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
											$data['f4760'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
											$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
											$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-6 логин верхнего Участника
											$data2 = Array();
											$data2['f4640'] = $finthree; //в поле М-6 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//но если и шестое место занято, ставим верхнего Участника в 7-е - матрица делится с реинвестом верхнего Участника
										elseif($matsev['f4640'] !== "") {
											$data['f4350'] = 2; //в поле АБТ пишем статус отображения Семёрки
											$data['f4760'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
											$data['f4960'] = ""; //сбрасываем поле IDТ-4 - связь с матрицей-тройкой
											$data['f5050'] = $matsev['id']; //в поле IDС-4 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-7 логин верхнего Участника
											$data2 = Array();
											$data2['f4650'] = $finthree; //в поле М-7 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
									}
								}
							}
						}
					    //создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
						$data = Array();
						$data['f4710'] = 4; //присваиваем ID записи таблицы Программы - АБТ
						$data['f4250'] = $rowsp['f1470'];
						data_insert(62, EVENTS_ENABLE, $data);
						//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
						$data2 = Array();
						$data2['f4710'] = 4; //присваиваем ID записи таблицы Программы - АБТ
						$data2['f4250'] = $matrix_usname;
						data_insert(62, EVENTS_ENABLE, $data2);
						//узнаём ID первой новой матрицы
				        $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `f4710`=4 AND `status`=0 LIMIT 1";
			            $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                        while ($matrix1 = sql_fetch_assoc($newmat1)) {
							//переписываем поля М-1 и IDТ-4 левого Участника
						    $data = Array();
							$data['f4350'] = 1; //в поле АБТ пишем статус отображения Тройки
                            $data['f4760'] = 1; //указываем место на вершине матрицы
							$data['f4960'] = $matrix1['id']; //в поле IDТ-4 пишем ID его новой матрицы-тройки
							$data['f5050'] = ""; //сбрасываем поле IDС-4 - связь с матрицей-семёркой
						    data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'");
						}
						//узнаём ID второй новой матрицы
				        $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=4 AND `status`=0 LIMIT 1";
			            $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                        while ($matrix2 = sql_fetch_assoc($newmat2)) {
							//переписываем поля М-1 и IDТ-4 правого Участника
							$data = Array();
							$data['f4350'] = 1; //в поле АБТ пишем статус отображения Тройки
                            $data['f4760'] = 1; //указываем место на вершине матрицы
							$data['f4960'] = $matrix2['id']; //в поле IDТ-4 пишем ID его новой матрицы-тройки
							$data['f5050'] = ""; //сбрасываем поле IDС-4 - связь с матрицей-семёркой
							data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
						}
						//удаляем поделившуюся матрицу
						data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
					}
                }
			    $matrix_longnames = 'Автомобильно-Благотворительная';
			}
			if($position == "") {
				$matrix_longnames = 'Автомобильно-Благотворительная';
				$data['f4350'] = 3; //присваиваем значение полю АБТ таблицы Участники 
                echo 'Ваш спонсор отсутствует в тройке!';
            }
		} elseif ($matrix_programname == 'ЖБТ') {
		    $pn = 5; //присваиваем значение переменной
            $position = 0; // Инициализируем переменную
            //Проверяем, где стоит спонсор
            //Выбираем в таблице массив полей записи спонсора
			$sponsor = "SELECT `id`,`f1470`,`f3860`,`f4770`,`f5010` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $matrix_spname . "' AND `f4360`=1 AND `status`=0 LIMIT 1";
			$res = sql_query($sponsor) or user_error(mysql_error() . "<br>" . $sponsor . "<br>", E_USER_ERROR);
            while ($rowsp = sql_fetch_assoc($res)) { //ищем спонсора и копируем его данные в массив
			    $position = $rowsp['f4770'];
				/******** смотрим в каком месте матрицы стоит спонсор - если спонсор стоит на вершине, тогда... ************/
                if($position == 1) {
		            //...делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4260` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    //смотрим занято ли второе место, если не занято...
					    if($fb['f4260'] == "") {
							$data['f4360'] = 1; //в поле ЖБТ пишем статус отображения Тройки
                            $data['f4770'] = 2; //то встаём во второе
							$data['f4970'] = $fb['id']; //пишем в таблицу Участники в поле IDТ-5 ID связанной матрицы-тройки
							$data['f5060'] = ""; //сбрасываем поле IDС-5 - связь с матрицей-семёркой
							//пишем в таблицу Тройки в поле М-2 свой ID
							$data2 = Array();
							$data2['f4260'] = $matrix_usname;
							data_update(62, EVENTS_ENABLE, $data2, "`id`='", $fb['id'], "'");
						}
						//если занято второе - встаём в третье и матрица делится: создаются две новые
						//верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
					    elseif($fb['f4260'] !== "") {//по этому условию создаём две новые матрицы, а старую удаляем
						    //подбираем Спонсору матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                    //для этого узнаём логин его спонсора...
				            $namespons = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `id`='" . $rowsp['id'] . "' AND `status`=0 LIMIT 1";
			                $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                            $nmsp = sql_fetch_assoc($namesp);
							//определяем, стоит ли спонсор ещё в тройке... 
							$spvtroyke = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                $vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
                            $sp_vtroyke = sql_fetch_assoc($vtroyke);
							if($sp_vtroyke['f4970'] !== "") {
							    //запрос в базу...
							    $next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
                                $sp_next = sql_fetch_assoc($nextsp);
							    //определяем, стоит ли спонсор спонсора ещё в тройке... 
							    $spvtroyke2 = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                    $vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
                                $sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
								if($sp_vtroyke2['f4970'] !== "") {
							        //запрос в базу...
							        $next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
                                    $sp_next2 = sql_fetch_assoc($nextsp2);
							        //определяем, стоит ли спонсор спонсора спонсора ещё в тройке... 
							        $spvtroyke3 = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
			                        $vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
                                    $sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								    if($sp_vtroyke3['f4970'] !== "") {
										//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
										$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
										$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
										while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
											if($descm['f5350'] == 4) {
												data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $descm['id']), "id='", $rowsp['id'],"'");
												data_update(320, EVENTS_ENABLE, array("f4620" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 5) {
												data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4630" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 6) {
												data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4640" => $matrix_spname), "`id`=", $descm['id']);
											} elseif($descm['f5350'] == 7) {
												data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $descm['id']), "id='", $rowsp['id'],"'"); 
												data_update(320, EVENTS_ENABLE, array("f4650" => $matrix_spname), "`id`=", $descm['id']);
											}
										}
								    } else { //или уже всё таки в семёрке
							            $approv_sp = $sp_next2['f3860'];
							        }
								} else { //или уже всё таки в семёрке
							        $approv_sp = $sp_next['f3860'];
							    }
							} else { //или уже всё таки в семёрке
							    $approv_sp = $nmsp['f3860'];
							}
							if($approv_sp) {
								//в таблице Семёрки ищем его по логину, т.к. перейти спонсор может только в ту семёрку, где стоит его спонсор
								$spvsemerke = "SELECT `id`,`f4570`,`f4580`,`f4590`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=5 AND `status`=0 LIMIT 1";
								$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
								while ($matsev = sql_fetch_assoc($vsemerke)) {
									//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4620'] == "") {
										$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
										$data['f4770'] = 4; //пишем в таблицу Спонсора  4-е место, куда поставили
										$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
										$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-4 логин Спонсора
										$data2 = Array();
										$data2['f4620'] = $rowsp['f1470']; //в поле М-4 пишем логин Спонсора
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если четвёртое место занято...
									elseif($matsev['f4620'] !== "") {
										//смотрим пятое место, если не занято - ставим Спонсора сюда
										if($matsev['f4630'] == "") {
											$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
											$data['f4770'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
											$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
											$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-5 логин Спонсора
											$data2 = Array();
											$data2['f4630'] = $rowsp['f1470']; //в поле М-5 пишем логин Спонсора
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//если пятое место тоже занято...
										elseif($matsev['f4630'] !== "") {
											//смотрим шестое место, если не занято - ставим Спонсора сюда
											if($matsev['f4640'] == "") {
												$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
												$data['f4770'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
												$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
												$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4640'] = $rowsp['f1470']; //в поле М-6 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
											//но если и шестое место занято - ставим Спонсора в седьмое и запускаем деление матрицы
											elseif($matsev['f4640'] !== "") {
												$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
												$data['f4770'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
												$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
												$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
												data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'"); //обновляем данные
												//пишем в таблицу Семёрки в поле М-6 логин Спонсора
												$data2 = Array();
												$data2['f4650'] = $rowsp['f1470']; //в поле М-7 пишем логин Спонсора
												data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
											}
										}
									}
								}
							}
							//создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
							$data = Array();
							$data['f4710'] = 5; //присваиваем ID записи таблицы Программы - ЖБТ
							$data['f4250'] = $fb['f4260'];
							data_insert(62, EVENTS_ENABLE, $data);
							//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
							$data2 = Array();
							$data2['f4710'] = 5; //присваиваем ID записи таблицы Программы - ЖБТ
							$data2['f4250'] = $matrix_usname;
							data_insert(62, EVENTS_ENABLE, $data2);
							//узнаём ID первой новой матрицы
				            $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $fb['f4260'] . "' AND `f4710`=5 AND `status`=0 LIMIT 1";
			                $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                            while ($matrix1 = sql_fetch_assoc($newmat1)) {
							    //переписываем поля М-5 и IDТ-5 Участника
								$data = Array();
								$data['f4360'] = 1; //в поле ЖБТ пишем статус отображения Тройки
                                $data['f4770'] = 1; //указываем место на вершине матрицы
							    $data['f4970'] = $matrix1['id']; //в поле IDТ-5 пишем ID его новой матрицы-тройки
								$data['f5060'] = ""; //сбрасываем поле IDС-5 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $fb['f4260'], "'");
							}
							//узнаём ID второй новой матрицы
				            $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=5 AND `status`=0 LIMIT 1";
			                $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                            while ($matrix2 = sql_fetch_assoc($newmat2)) {
							    //переписываем поля М-5 и IDТ-5 Участника
								$data = Array();
								$data['f4360'] = 1; //в поле ЖБТ пишем статус отображения Тройки
                                $data['f4770'] = 1; //указываем место на вершине матрицы
							    $data['f4970'] = $matrix2['id']; //в поле IDТ-5 пишем ID его новой матрицы-тройки
								$data['f5060'] = ""; //сбрасываем поле IDС-5 - связь с матрицей-семёркой
								data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
							}
							//удаляем поделившуюся матрицу
							data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
						}
					}
				}
				/******** а если спонсор стоит во втором месте - встаём в третье и матрица делится: создаются две новые ************/
				elseif($position == 2) {
				    //верхний уходит в семёрку, а участники в плече переходят в вершины, левый в первую - правый во вторую...
		            //делаем запрос к базе о местах матрицы
				    $freebusy = "SELECT `id`,`f4250` FROM `" . DATA_TABLE . "62` WHERE `f4260`='" . $rowsp['f1470'] . "' AND `status`=0 LIMIT 1";
			        $fob = sql_query($freebusy) or user_error(mysql_error() . "<br>" . $freebusy . "<br>", E_USER_ERROR);
                    while ($fb = sql_fetch_assoc($fob)) { //ищем спонсора и копируем его данные в массив
					    $finthree = $fb['f4250']; //назначаем переменную - логин верхнего в тройке

						//подбираем верхнему Участнику матрицу-семёрку, ставим его туда и корректируем его поля в таблице Участники
		                //для этого узнаём логин его спонсора...
				        $namespons = "SELECT `id`,`f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $finthree . "' AND `status`=0 LIMIT 1";
			            $namesp = sql_query($namespons) or user_error(mysql_error() . "<br>" . $namespons . "<br>", E_USER_ERROR);
                        $nmsp = sql_fetch_assoc($namesp);
						//определяем, стоит ли спонсор ещё в тройке... 
						$spvtroyke = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
						$vtroyke = sql_query($spvtroyke) or user_error(mysql_error() . "<br>" . $spvtroyke . "<br>", E_USER_ERROR);
						$sp_vtroyke = sql_fetch_assoc($vtroyke);
						if($sp_vtroyke['f4970'] !== "") {
							//запрос в базу...
							$next_sp = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $nmsp['f3860'] . "' AND `status`=0 LIMIT 1";
							$nextsp = sql_query($next_sp) or user_error(mysql_error() . "<br>" . $next_sp . "<br>", E_USER_ERROR);
							$sp_next = sql_fetch_assoc($nextsp);
							//определяем, стоит ли спонсор спонсора ещё в тройке... 
							$spvtroyke2 = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
							$vtroyke2 = sql_query($spvtroyke2) or user_error(mysql_error() . "<br>" . $spvtroyke2 . "<br>", E_USER_ERROR);
							$sp_vtroyke2 = sql_fetch_assoc($vtroyke2);
							if($sp_vtroyke2['f4970'] !== "") {
								//запрос в базу...
								$next_sp2 = "SELECT `f3860` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next['f3860'] . "' AND `status`=0 LIMIT 1";
								$nextsp2 = sql_query($next_sp2) or user_error(mysql_error() . "<br>" . $next_sp2 . "<br>", E_USER_ERROR);
								$sp_next2 = sql_fetch_assoc($nextsp2);
								//определяем, стоит ли спонсор спонсора ещё в тройке... 
								$spvtroyke3 = "SELECT `f4970` FROM `" . DATA_TABLE . "42` WHERE `f1470`='" . $sp_next2['f3860'] . "' AND `status`=0 LIMIT 1";
								$vtroyke3 = sql_query($spvtroyke3) or user_error(mysql_error() . "<br>" . $spvtroyke3 . "<br>", E_USER_ERROR);
								$sp_vtroyke3 = sql_fetch_assoc($vtroyke3);
								if($sp_vtroyke3['f4970'] !== "") {
									//Если спонсор так и не найден, ищем последнюю созданную матрицу своей программы и встаём туда...
									$matdesc = "SELECT `id`,`f5350` FROM `" . DATA_TABLE . "320` WHERE `f4720`='" . $pn . "' AND `status`=0 ORDER BY `id` DESC LIMIT 1";
									$mdesc = sql_query($matdesc) or user_error(mysql_error() . "<br>" . $matdesc . "<br>", E_USER_ERROR);
									while ($descm = sql_fetch_assoc($mdesc)) { //узнаём последнюю матрицу в таблице
										if($descm['f5350'] == 4) {
											data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $descm['id']), "id='", $nmsp['id'],"'");
											data_update(320, EVENTS_ENABLE, array("f4620" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 5) {
											data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4630" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 6) {
											data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4640" => $finthree), "`id`=", $descm['id']);
										} elseif($descm['f5350'] == 7) {
											data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $descm['id']), "id='", $nmsp['id'],"'"); 
											data_update(320, EVENTS_ENABLE, array("f4650" => $finthree), "`id`=", $descm['id']);
										}
									}
								} else { //или уже всё таки в семёрке
									$approv_sp = $sp_next2['f3860'];
								}
							} else { //или уже всё таки в семёрке
								$approv_sp = $sp_next['f3860'];
							}
						} else { //или уже всё таки в семёрке
							$approv_sp = $nmsp['f3860'];
						}
						if($approv_sp) {
							//в таблице Семёрки ищем его по логину, т.к. перейти Участник может только в ту семёрку, где стоит его спонсор
							$spvsemerke = "SELECT `id`,`f4620`,`f4630`,`f4640` FROM `" . DATA_TABLE . "320` WHERE (`f4570`='" . $approv_sp . "' OR `f4580`='" . $approv_sp . "' OR `f4590`='" . $approv_sp . "' OR `f4620`='" . $approv_sp . "' OR `f4630`='" . $approv_sp . "' OR `f4640`='" . $approv_sp . "') AND `f4720`=5 AND `status`=0 LIMIT 1";
							$vsemerke = sql_query($spvsemerke) or user_error(mysql_error() . "<br>" . $spvsemerke . "<br>", E_USER_ERROR);
							while ($matsev = sql_fetch_assoc($vsemerke)) {
								//смотрим занято ли четвёртое место, если не занято - ставим верхнего Участника сюда
								if($matsev['f4620'] == "") {
									$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
									$data['f4770'] = 4; //пишем в таблицу Участники  4-е место, куда поставили
									$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
									$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
									data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
									//пишем в таблицу Семёрки в поле М-4 логин верхнего Участника
									$data2 = Array();
									$data2['f4620'] = $finthree; //в поле М-4 пишем логин верхнего Участника
									data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
								}
								//если четвёртое место занято...
								elseif($matsev['f4620'] !== "") {
									//смотрим пятое место, если не занято - ставим верхнего Участника сюда
									if($matsev['f4630'] == "") {
										$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
										$data['f4770'] = 5; //пишем в таблицу Участники  5-е место, куда поставили
										$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
										$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
										data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
										//пишем в таблицу Семёрки в поле М-5 логин верхнего Участника
										$data2 = Array();
										$data2['f4630'] = $finthree; //в поле М-5 пишем логин верхнего Участника
										data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
									}
									//если пятое место тоже занято...
									elseif($matsev['f4630'] !== "") {
										//смотрим шестое место, если не занято - ставим верхнего Участника сюда
										if($matsev['f4640'] == "") {
											$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
											$data['f4770'] = 6; //пишем в таблицу Участники  6-е место, куда поставили
											$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
											$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-6 логин верхнего Участника
											$data2 = Array();
											$data2['f4640'] = $finthree; //в поле М-6 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
										//но если и шестое место занято, ставим верхнего Участника в 7-е - матрица делится с реинвестом верхнего Участника
										elseif($matsev['f4640'] !== "") {
											$data['f4360'] = 2; //в поле ЖБТ пишем статус отображения Семёрки
											$data['f4770'] = 7; //пишем в таблицу Участники  7-е место, куда поставили
											$data['f4970'] = ""; //сбрасываем поле IDТ-5 - связь с матрицей-тройкой
											$data['f5060'] = $matsev['id']; //в поле IDС-5 пишем ID связанной матрицы-семёрки
											data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $finthree, "'"); //обновляем данные
											//пишем в таблицу Семёрки в поле М-7 логин верхнего Участника
											$data2 = Array();
											$data2['f4650'] = $finthree; //в поле М-7 пишем логин верхнего Участника
											data_update(320, EVENTS_ENABLE, $data2, "`id`='", $matsev['id'], "'");
										}
									}
								}
							}
						}	
					    //создаём первую новую матрицу и пишем в неё данные левого Участника из плеча
						$data = Array();
						$data['f4710'] = 5; //присваиваем ID записи таблицы Программы - ЖБТ
						$data['f4250'] = $rowsp['f1470'];
						data_insert(62, EVENTS_ENABLE, $data);
						//создаём вторую новую матрицу и пишем в неё данные правого Участника из плеча
						$data2 = Array();
						$data2['f4710'] = 5; //присваиваем ID записи таблицы Программы - ЖБТ
						$data2['f4250'] = $matrix_usname;
						data_insert(62, EVENTS_ENABLE, $data2);
						//узнаём ID первой новой матрицы
				        $newmatrix1 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $rowsp['f1470'] . "' AND `f4710`=5 AND `status`=0 LIMIT 1";
			            $newmat1 = sql_query($newmatrix1) or user_error(mysql_error() . "<br>" . $newmatrix1 . "<br>", E_USER_ERROR);
                        while ($matrix1 = sql_fetch_assoc($newmat1)) {
							//переписываем поля М-5 и IDТ-5 левого Участника
						    $data = Array();
							$data['f4360'] = 1; //в поле ЖБТ пишем статус отображения Тройки
                            $data['f4770'] = 1; //указываем место на вершине матрицы
							$data['f4970'] = $matrix1['id']; //в поле IDТ-5 пишем ID его новой матрицы-тройки
							$data['f5060'] = ""; //сбрасываем поле IDС-5 - связь с матрицей-семёркой
						    data_update(42, EVENTS_ENABLE, $data, "`id`='", $rowsp['id'], "'");
						}
						//узнаём ID второй новой матрицы
				        $newmatrix2 = "SELECT `id` FROM `" . DATA_TABLE . "62` WHERE `f4250`='" . $matrix_usname . "' AND `f4710`=5 AND `status`=0 LIMIT 1";
			            $newmat2 = sql_query($newmatrix2) or user_error(mysql_error() . "<br>" . $newmatrix2 . "<br>", E_USER_ERROR);
                        while ($matrix2 = sql_fetch_assoc($newmat2)) {
							//переписываем поля М-5 и IDТ-5 правого Участника
							$data = Array();
							$data['f4360'] = 1; //в поле ЖБТ пишем статус отображения Тройки
                            $data['f4770'] = 1; //указываем место на вершине матрицы
							$data['f4970'] = $matrix2['id']; //в поле IDТ-5 пишем ID его новой матрицы-тройки
							$data['f5060'] = ""; //сбрасываем поле IDС-5 - связь с матрицей-семёркой
							data_update(42, EVENTS_ENABLE, $data, "`f1470`='", $matrix_usname, "'");
						}
						//удаляем поделившуюся матрицу
						data_delete(62, EVENTS_ENABLE, "`id`='", $fb['id'], "'");
					}
                }
			    $matrix_longnames = 'Жилищно-Благотворительная';
			}
			if($position == "") {
				$matrix_longnames = 'Жилищно-Благотворительная';
				$data['f4360'] = 3; //присваиваем значение полю ЖБТ таблицы Участники 
                echo 'Ваш спонсор отсутствует в тройке!';
            }
		}
//---------------------- Вычисление переходов участников - КОНЕЦ ---------------------------
        $data['f3830'] = $matrix_balance - $matrix_moneygate; // снова пересчитываем балланс - вычитаем стоимость входа
        $data['u'] = 1;
        // Обновляем поля строки значениями из массива
        data_update(42, EVENTS_ENABLE, $data, "`id`='", $matrix_userid, "'");
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
        send_template(310, "`id`=" . $matrix_userid);
    }
    echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
}
?>