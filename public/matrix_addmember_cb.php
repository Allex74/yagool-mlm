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
if ($_REQUEST['matrix_addmember'] == 1) {
    $matrix_referer = $_SERVER['HTTP_REFERER']; // ПО этому признаку можно смотреть откуда пришел запрос, чтобы исключить постинг с других ресурсов.
    $add_member = $_REQUEST['add_member'];
	$matrix_userid = $_REQUEST['matrix_userid'];
	$matrix_programname = $_REQUEST['matrix_programname'];
	if($add_member) {
		//Смотрим, в какой матрице стоит спонсор
		$msp = data_select_array(42, "`f1470`='",$add_member,"' AND `status`=0 LIMIT 1");
		//Берём массив полей участника...
		$yn = data_select_array(42, "`id`=",$matrix_userid," AND `status`=0 LIMIT 1");
		if(!$msp) { //Если запрос в базу вернул FALSE
			echo 'Указанный Вами спонсор отсутствует в базе.';
			exit;
		//------------------------ Вычисление переходов участников ------------------------------------
		} elseif (($matrix_programname == 'БТ-1') && ($msp['f4930'] !== "")) { //Вычисление для программы БТ-1
			//Смотрим, в какой матрице стоит спонсор и определяем первое свободне место
			$wm = data_select_array(62, "`id`=",$msp['f4930']," AND `status`=0 LIMIT 1");
			if($wm['f5360'] == 2) {//если свободно второе, то встаём...
				data_update(62, EVENTS_ENABLE, array("f4260" => $yn['f1470']), "`id`=", $msp['f4930']);
				data_update(42, EVENTS_ENABLE, array("f4320" => 1, "f4730" => 2, "f4930" => $msp['f4930'], "f5020" => ""), "`id`=", $matrix_userid);
			} elseif($wm['f5360'] == 3) {//если свободно третье, то встаём в третье и матрица делится...
			    data_update(62, EVENTS_ENABLE, array("f4270" => $yn['f1470']), "`id`=", $msp['f4930']);
				data_update(42, EVENTS_ENABLE, array("f4320" => 1, "f4730" => 3, "f4930" => $msp['f4930'], "f5020" => ""), "`id`=", $matrix_userid);
				//создаются две новые матрицы...
				$m = data_select_array(62, "`id`=",$msp['f4930']," AND `status`=0 LIMIT 1");
				//создаём первую новую матрицу: пишем обновляем данные левого Участника из плеча
				$m1 = data_insert(62, EVENTS_ENABLE, array("f4710" => 1, "f4250" => $m['f4260']));
				data_update(42, EVENTS_ENABLE, array("f4320" => 1, "f4730" => 1, "f4930" => $m1, "f5020" => ""), "`f1470`='", $m['f4260'], "'");
				//создаём вторую новую матрицу: пишем обновляем данные правого Участника из плеча
                $m2 = data_insert(62, EVENTS_ENABLE, array("f4710" => 1, "f4250" => $m['f4270']));
				data_update(42, EVENTS_ENABLE, array("f4320" => 1, "f4730" => 1, "f4930" => $m2, "f5020" => ""), "`f1470`='", $m['f4270'], "'");
				//верхний переходит в семёрку за своим спонсором. Узнаём спонсоров на три колена вглубь...
				$sp1 = data_select_array(42, "`f1470`='",$wm['f4250'],"' AND `status`=0");
				$sp2 = data_select_array(42, "`f1470`='",$sp1['f3860'],"' AND `status`=0");
				$sp3 = data_select_array(42, "`f1470`='",$sp2['f3860'],"' AND `status`=0");
				//проверяем по порядку - кто из них стоит в семёрке
				if(($sp1['f4930'] == "") and ($sp1['f5020'] !== "")) {
					//если спонсор первого колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp1['f5020']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp2['f4930'] == "") and ($sp2['f5020'] !== "")) {
					//если спонсор второго колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp2['f5020']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp3['f4930'] == "") and ($sp3['f5020'] !== "")) {
					//если спонсор третьего колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp3['f5020']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $sm['id']), "`id`=", $m['f5000']);
					}
				} else {
					//Если ни один из спонсоров не стоит в семёрке, то ставим перешедшегов в первую новую семёрку текущей программы...
					$descm = data_select_array(320, "`f4720`=1 AND `status`=0 ORDER BY `id` DESC LIMIT 1");
					if($descm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 4, "f4930" => "", "f5020" => $descm['id']), "id=", $m['f5000']);
					} elseif($descm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 5, "f4930" => "", "f5020" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 6, "f4930" => "", "f5020" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $168 начислено - $42 реинвест - $26 благо = $100 зачислено
						$m7 = data_select_array(42, "`id`=",$descm['f4550']," AND `status`=0");
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
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4320" => 2, "f4730" => 7, "f4930" => "", "f5020" => $descm['id']), "id=", $m['f5000']); 
					}
				}
				//отправка сообщения перешедшему в семёрку о начислении и вычитам: $42 (начислено) - $42 (вход в 7-ку) = $0 (зачислено)
				$mv = data_select_array(42, "`id`=",$m['f5000']," AND `status`=0");
				$smarty_name = $mv['f3721'];
				$smarty_patr = $mv['f3850'];
				$smarty_lnames = 'Благотворительная №1';
				$smarty_calc = '$42 начислено - $42 вход в 7-ку = $0 зачислено';
				$smarty->assign("smarty_name", $smarty_name);
				$smarty->assign("smarty_patr", $smarty_patr);
				$smarty->assign("smarty_lnames", $smarty_lnames);
				$smarty->assign("smarty_calc", $smarty_calc);
				send_template(330, "`id`=" . $mv['id']);
				//занесение в хронику событий перешедшему в семёрку о начислении и вычитам: $42 (начислено) - $42 (вход в 7-ку) = $0 (итого)
				$data = Array();
				$data['f4080'] = $mv['id'];
				$data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
				$data['f4100'] = 'Из 3-ки в 7-ку. ИТОГО: $42 начислено - $42 вход = $0 зачислено';
				data_insert(280, EVENTS_ENABLE, $data);
				//удаляем поделившуюся матрицу
				data_delete(62, EVENTS_ENABLE, "`id`=", $msp['f4930']);
			}
		} elseif (($matrix_programname == 'БТ-2') && ($msp['f4940'] !== "")) { //Вычисление для программы БТ-2
			//Смотрим, в какой матрице стоит спонсор и определяем первое свободне место
			$wm = data_select_array(62, "`id`=",$msp['f4940']," AND `status`=0 LIMIT 1");
			if($wm['f5360'] == 2) {//если свободно второе, то встаём...
				data_update(62, EVENTS_ENABLE, array("f4260" => $yn['f1470']), "`id`=", $msp['f4940']);
				data_update(42, EVENTS_ENABLE, array("f4330" => 1, "f4740" => 2, "f4940" => $msp['f4940'], "f5030" => ""), "`id`=", $matrix_userid);
			} elseif($wm['f5360'] == 3) {//если свободно третье, то встаём в третье и матрица делится...
			    data_update(62, EVENTS_ENABLE, array("f4270" => $yn['f1470']), "`id`=", $msp['f4940']);
				data_update(42, EVENTS_ENABLE, array("f4330" => 1, "f4740" => 3, "f4940" => $msp['f4940'], "f5030" => ""), "`id`=", $matrix_userid);
				//создаются две новые матрицы...
				$m = data_select_array(62, "`id`=",$msp['f4940']," AND `status`=0 LIMIT 1");
				//создаём первую новую матрицу: пишем обновляем данные левого Участника из плеча
				$m1 = data_insert(62, EVENTS_ENABLE, array("f4710" => 2, "f4250" => $m['f4260']));
				data_update(42, EVENTS_ENABLE, array("f4330" => 1, "f4740" => 1, "f4940" => $m1, "f5030" => ""), "`f1470`='", $m['f4260'], "'");
				//создаём вторую новую матрицу: пишем обновляем данные правого Участника из плеча
                $m2 = data_insert(62, EVENTS_ENABLE, array("f4710" => 2, "f4250" => $m['f4270']));
				data_update(42, EVENTS_ENABLE, array("f4330" => 1, "f4740" => 1, "f4940" => $m2, "f5030" => ""), "`f1470`='", $m['f4270'], "'");
				//верхний переходит в семёрку за своим спонсором. Узнаём спонсоров на три колена вглубь...
				$sp1 = data_select_array(42, "`f1470`='",$wm['f4250'],"' AND `status`=0");
				$sp2 = data_select_array(42, "`f1470`='",$sp1['f3860'],"' AND `status`=0");
				$sp3 = data_select_array(42, "`f1470`='",$sp2['f3860'],"' AND `status`=0");
				//проверяем по порядку - кто из них стоит в семёрке
				if(($sp1['f4940'] == "") and ($sp1['f5030'] !== "")) {
					//если спонсор первого колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp1['f5030']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp2['f4940'] == "") and ($sp2['f5030'] !== "")) {
					//если спонсор второго колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp2['f5030']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp3['f4940'] == "") and ($sp3['f5030'] !== "")) {
					//если спонсор третьего колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp3['f5030']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $sm['id']), "`id`=", $m['f5000']);
					}
				} else {
					//Если ни один из спонсоров не стоит в семёрке, то ставим перешедшегов в первую новую семёрку текущей программы...
					$descm = data_select_array(320, "`f4720`=2 AND `status`=0 ORDER BY `id` DESC LIMIT 1");
					if($descm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 4, "f4940" => "", "f5030" => $descm['id']), "id=", $m['f5000']);
					} elseif($descm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 5, "f4940" => "", "f5030" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 6, "f4940" => "", "f5030" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $800 начислено - $200 реинвест - $100 благо = $500 зачислено
						$m7 = data_select_array(42, "`id`=",$descm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4330" => 2, "f4740" => 7, "f4940" => "", "f5030" => $descm['id']), "id=", $m['f5000']); 
					}
				}
				//отправка сообщения перешедшему в семёрку о начислении и вычитам: $200 (начислено) - $200 (вход в 7-ку) = $0 (зачислено)
				$mv = data_select_array(42, "`id`=",$m['f5000']," AND `status`=0");
				$smarty_name = $mv['f3721'];
				$smarty_patr = $mv['f3850'];
				$smarty_lnames = 'Благотворительная №2';
				$smarty_calc = '$200 начислено - $200 вход в 7-ку = $0 зачислено';
				$smarty->assign("smarty_name", $smarty_name);
				$smarty->assign("smarty_patr", $smarty_patr);
				$smarty->assign("smarty_lnames", $smarty_lnames);
				$smarty->assign("smarty_calc", $smarty_calc);
				send_template(330, "`id`=" . $mv['id']);
				//занесение в хронику событий перешедшему в семёрку о начислении и вычитам: $200 (начислено) - $200 (вход в 7-ку) = $0 (итого)
				$data = Array();
				$data['f4080'] = $mv['id'];
				$data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
				$data['f4100'] = 'Из 3-ки в 7-ку. ИТОГО: $200 начислено - $200 вход = $0 зачислено';
				data_insert(280, EVENTS_ENABLE, $data);
				//удаляем поделившуюся матрицу
				data_delete(62, EVENTS_ENABLE, "`id`=", $msp['f4940']);
			}
		} elseif (($matrix_programname == 'БТ-3') && ($msp['f4950'] !== "")) { //Вычисление для программы БТ-3
			//Смотрим, в какой матрице стоит спонсор и определяем первое свободне место
			$wm = data_select_array(62, "`id`=",$msp['f4950']," AND `status`=0 LIMIT 1");
			if($wm['f5360'] == 2) {//если свободно второе, то встаём...
				data_update(62, EVENTS_ENABLE, array("f4260" => $yn['f1470']), "`id`=", $msp['f4950']);
				data_update(42, EVENTS_ENABLE, array("f4340" => 1, "f4750" => 2, "f4950" => $msp['f4950'], "f5040" => ""), "`id`=", $matrix_userid);
			} elseif($wm['f5360'] == 3) {//если свободно третье, то встаём в третье и матрица делится...
			    data_update(62, EVENTS_ENABLE, array("f4270" => $yn['f1470']), "`id`=", $msp['f4950']);
				data_update(42, EVENTS_ENABLE, array("f4340" => 1, "f4750" => 3, "f4950" => $msp['f4950'], "f5040" => ""), "`id`=", $matrix_userid);
				//создаются две новые матрицы...
				$m = data_select_array(62, "`id`=",$msp['f4950']," AND `status`=0 LIMIT 1");
				//создаём первую новую матрицу: пишем обновляем данные левого Участника из плеча
				$m1 = data_insert(62, EVENTS_ENABLE, array("f4710" => 3, "f4250" => $m['f4260']));
				data_update(42, EVENTS_ENABLE, array("f4340" => 1, "f4750" => 1, "f4950" => $m1, "f5040" => ""), "`f1470`='", $m['f4260'], "'");
				//создаём вторую новую матрицу: пишем обновляем данные правого Участника из плеча
                $m2 = data_insert(62, EVENTS_ENABLE, array("f4710" => 3, "f4250" => $m['f4270']));
				data_update(42, EVENTS_ENABLE, array("f4340" => 1, "f4750" => 1, "f4950" => $m2, "f5040" => ""), "`f1470`='", $m['f4270'], "'");
				//верхний переходит в семёрку за своим спонсором. Узнаём спонсоров на три колена вглубь...
				$sp1 = data_select_array(42, "`f1470`='",$wm['f4250'],"' AND `status`=0");
				$sp2 = data_select_array(42, "`f1470`='",$sp1['f3860'],"' AND `status`=0");
				$sp3 = data_select_array(42, "`f1470`='",$sp2['f3860'],"' AND `status`=0");
				//проверяем по порядку - кто из них стоит в семёрке
				if(($sp1['f4950'] == "") and ($sp1['f5040'] !== "")) {
					//если спонсор первого колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp1['f5040']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp2['f4950'] == "") and ($sp2['f5040'] !== "")) {
					//если спонсор второго колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp2['f5040']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp3['f4950'] == "") and ($sp3['f5040'] !== "")) {
					//если спонсор третьего колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp3['f5040']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $sm['id']), "`id`=", $m['f5000']);
					}
				} else {
					//Если ни один из спонсоров не стоит в семёрке, то ставим перешедшегов в первую новую семёрку текущей программы...
					$descm = data_select_array(320, "`f4720`=3 AND `status`=0 ORDER BY `id` DESC LIMIT 1");
					if($descm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 4, "f4950" => "", "f5040" => $descm['id']), "id=", $m['f5000']);
					} elseif($descm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 5, "f4950" => "", "f5040" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 6, "f4950" => "", "f5040" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $4000 начислено - $1000 реинвест - $500 благо = $2500 зачислено
						$m7 = data_select_array(42, "`id`=",$descm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4340" => 2, "f4750" => 7, "f4950" => "", "f5040" => $descm['id']), "id=", $m['f5000']); 
					}
				}
				//отправка сообщения перешедшему в семёрку о начислении и вычитам: $1000 (начислено) - $1000 (вход в 7-ку) = $0 (зачислено)
				$mv = data_select_array(42, "`id`=",$m['f5000']," AND `status`=0");
				$smarty_name = $mv['f3721'];
				$smarty_patr = $mv['f3850'];
				$smarty_lnames = 'Благотворительная №3';
				$smarty_calc = '$1000 начислено - $1000 вход в 7-ку = $0 зачислено';
				$smarty->assign("smarty_name", $smarty_name);
				$smarty->assign("smarty_patr", $smarty_patr);
				$smarty->assign("smarty_lnames", $smarty_lnames);
				$smarty->assign("smarty_calc", $smarty_calc);
				send_template(330, "`id`=" . $mv['id']);
				//занесение в хронику событий перешедшему в семёрку о начислении и вычитам: $1000 (начислено) - $1000 (вход в 7-ку) = $0 (итого)
				$data = Array();
				$data['f4080'] = $mv['id'];
				$data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
				$data['f4100'] = 'Из 3-ки в 7-ку. ИТОГО: $1000 начислено - $1000 вход = $0 зачислено';
				data_insert(280, EVENTS_ENABLE, $data);
				//удаляем поделившуюся матрицу
				data_delete(62, EVENTS_ENABLE, "`id`=", $msp['f4950']);
			}
		} elseif (($matrix_programname == 'АБТ') && ($msp['f4960'] !== "")) { //Вычисление для программы АБТ
			//Смотрим, в какой матрице стоит спонсор и определяем первое свободне место
			$wm = data_select_array(62, "`id`=",$msp['f4960']," AND `status`=0 LIMIT 1");
			if($wm['f5360'] == 2) {//если свободно второе, то встаём...
				data_update(62, EVENTS_ENABLE, array("f4260" => $yn['f1470']), "`id`=", $msp['f4960']);
				data_update(42, EVENTS_ENABLE, array("f4350" => 1, "f4760" => 2, "f4960" => $msp['f4960'], "f5050" => ""), "`id`=", $matrix_userid);
			} elseif($wm['f5360'] == 3) {//если свободно третье, то встаём в третье и матрица делится...
			    data_update(62, EVENTS_ENABLE, array("f4270" => $yn['f1470']), "`id`=", $msp['f4960']);
				data_update(42, EVENTS_ENABLE, array("f4350" => 1, "f4760" => 3, "f4960" => $msp['f4960'], "f5050" => ""), "`id`=", $matrix_userid);
				//создаются две новые матрицы...
				$m = data_select_array(62, "`id`=",$msp['f4960']," AND `status`=0 LIMIT 1");
				//создаём первую новую матрицу: пишем обновляем данные левого Участника из плеча
				$m1 = data_insert(62, EVENTS_ENABLE, array("f4710" => 4, "f4250" => $m['f4260']));
				data_update(42, EVENTS_ENABLE, array("f4350" => 1, "f4760" => 1, "f4960" => $m1, "f5050" => ""), "`f1470`='", $m['f4260'], "'");
				//создаём вторую новую матрицу: пишем обновляем данные правого Участника из плеча
                $m2 = data_insert(62, EVENTS_ENABLE, array("f4710" => 4, "f4250" => $m['f4270']));
				data_update(42, EVENTS_ENABLE, array("f4350" => 1, "f4760" => 1, "f4960" => $m2, "f5050" => ""), "`f1470`='", $m['f4270'], "'");
				//верхний переходит в семёрку за своим спонсором. Узнаём спонсоров на три колена вглубь...
				$sp1 = data_select_array(42, "`f1470`='",$wm['f4250'],"' AND `status`=0");
				$sp2 = data_select_array(42, "`f1470`='",$sp1['f3860'],"' AND `status`=0");
				$sp3 = data_select_array(42, "`f1470`='",$sp2['f3860'],"' AND `status`=0");
				//проверяем по порядку - кто из них стоит в семёрке
				if(($sp1['f4960'] == "") and ($sp1['f5050'] !== "")) {
					//если спонсор первого колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp1['f5050']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp2['f4960'] == "") and ($sp2['f5050'] !== "")) {
					//если спонсор второго колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp2['f5050']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp3['f4960'] == "") and ($sp3['f5050'] !== "")) {
					//если спонсор третьего колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp3['f5050']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $sm['id']), "`id`=", $m['f5000']);
					}
				} else {
					//Если ни один из спонсоров не стоит в семёрке, то ставим перешедшегов в первую новую семёрку текущей программы...
					$descm = data_select_array(320, "`f4720`=4 AND `status`=0 ORDER BY `id` DESC LIMIT 1");
					if($descm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 4, "f4960" => "", "f5050" => $descm['id']), "id=", $m['f5000']);
					} elseif($descm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 5, "f4960" => "", "f5050" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 6, "f4960" => "", "f5050" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $20000 начислено - $5000 реинвест - $1000 благо = $14000 зачислено
						$m7 = data_select_array(42, "`id`=",$descm['f4550']," AND `status`=0");
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
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4350" => 2, "f4760" => 7, "f4960" => "", "f5050" => $descm['id']), "id=", $m['f5000']); 
					}
				}
				//отправка сообщения перешедшему в семёрку о начислении и вычитам: $5000 (начислено) - $5000 (вход в 7-ку) = $0 (зачислено)
				$mv = data_select_array(42, "`id`=",$m['f5000']," AND `status`=0");
				$smarty_name = $mv['f3721'];
				$smarty_patr = $mv['f3850'];
				$smarty_lnames = 'Автомобильно-Благотворительная';
				$smarty_calc = '$5000 начислено - $5000 вход в 7-ку = $0 зачислено';
				$smarty->assign("smarty_name", $smarty_name);
				$smarty->assign("smarty_patr", $smarty_patr);
				$smarty->assign("smarty_lnames", $smarty_lnames);
				$smarty->assign("smarty_calc", $smarty_calc);
				send_template(330, "`id`=" . $mv['id']);
				//занесение в хронику событий перешедшему в семёрку о начислении и вычитам: $5000 (начислено) - $5000 (вход в 7-ку) = $0 (итого)
				$data = Array();
				$data['f4080'] = $mv['id'];
				$data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
				$data['f4100'] = 'Из 3-ки в 7-ку. ИТОГО: $5000 начислено - $5000 вход = $0 зачислено';
				data_insert(280, EVENTS_ENABLE, $data);
				//удаляем поделившуюся матрицу
				data_delete(62, EVENTS_ENABLE, "`id`=", $msp['f4960']);
			}
		} elseif (($matrix_programname == 'ЖБТ') && ($msp['f4970'] !== "")) { //Вычисление для программы ЖБТ
			//Смотрим, в какой матрице стоит спонсор и определяем первое свободне место
			$wm = data_select_array(62, "`id`=",$msp['f4970']," AND `status`=0 LIMIT 1");
			if($wm['f5360'] == 2) {//если свободно второе, то встаём...
				data_update(62, EVENTS_ENABLE, array("f4260" => $yn['f1470']), "`id`=", $msp['f4970']);
				data_update(42, EVENTS_ENABLE, array("f4360" => 1, "f4770" => 2, "f4970" => $msp['f4970'], "f5060" => ""), "`id`=", $matrix_userid);
			} elseif($wm['f5360'] == 3) {//если свободно третье, то встаём в третье и матрица делится...
			    data_update(62, EVENTS_ENABLE, array("f4270" => $yn['f1470']), "`id`=", $msp['f4970']);
				data_update(42, EVENTS_ENABLE, array("f4360" => 1, "f4770" => 3, "f4970" => $msp['f4970'], "f5060" => ""), "`id`=", $matrix_userid);
				//создаются две новые матрицы...
				$m = data_select_array(62, "`id`=",$msp['f4970']," AND `status`=0 LIMIT 1");
				//создаём первую новую матрицу: пишем обновляем данные левого Участника из плеча
				$m1 = data_insert(62, EVENTS_ENABLE, array("f4710" => 5, "f4250" => $m['f4260']));
				data_update(42, EVENTS_ENABLE, array("f4360" => 1, "f4770" => 1, "f4970" => $m1, "f5060" => ""), "`f1470`='", $m['f4260'], "'");
				//создаём вторую новую матрицу: пишем обновляем данные правого Участника из плеча
                $m2 = data_insert(62, EVENTS_ENABLE, array("f4710" => 5, "f4250" => $m['f4270']));
				data_update(42, EVENTS_ENABLE, array("f4360" => 1, "f4770" => 1, "f4970" => $m2, "f5060" => ""), "`f1470`='", $m['f4270'], "'");
				//верхний переходит в семёрку за своим спонсором. Узнаём спонсоров на три колена вглубь...
				$sp1 = data_select_array(42, "`f1470`='",$wm['f4250'],"' AND `status`=0");
				$sp2 = data_select_array(42, "`f1470`='",$sp1['f3860'],"' AND `status`=0");
				$sp3 = data_select_array(42, "`f1470`='",$sp2['f3860'],"' AND `status`=0");
				//проверяем по порядку - кто из них стоит в семёрке
				if(($sp1['f4970'] == "") and ($sp1['f5060'] !== "")) {
					//если спонсор первого колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp1['f5060']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
						$smarty_name = $m7['f3721'];
						$smarty_patr = $m7['f3850'];
						$smarty_lnames = 'Жилищно-Благотворительная';
						$smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						$smarty->assign("smarty_name", $smarty_name);
						$smarty->assign("smarty_patr", $smarty_patr);
						$smarty->assign("smarty_lnames", $smarty_lnames);
						$smarty->assign("smarty_calc", $smarty_calc);
						send_template(340, "`id`=" . $m7['id']);
						//занесение в хронику событий о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$data = Array();
						$data['f4080'] = $m7['id'];
						$data['f4090'] = 'Реинвест в ' . $matrix_programname;
						$data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						data_insert(280, EVENTS_ENABLE, $data);
				        //зачисление на счёт реинвестированного в ЖБТ $77000
						$sum = $m7['f3830'] + 77000;
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp2['f4970'] == "") and ($sp2['f5060'] !== "")) {
					//если спонсор второго колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp2['f5060']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
						$smarty_name = $m7['f3721'];
						$smarty_patr = $m7['f3850'];
						$smarty_lnames = 'Жилищно-Благотворительная';
						$smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						$smarty->assign("smarty_name", $smarty_name);
						$smarty->assign("smarty_patr", $smarty_patr);
						$smarty->assign("smarty_lnames", $smarty_lnames);
						$smarty->assign("smarty_calc", $smarty_calc);
						send_template(340, "`id`=" . $m7['id']);
						//занесение в хронику событий о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$data = Array();
						$data['f4080'] = $m7['id'];
						$data['f4090'] = 'Реинвест в ' . $matrix_programname;
						$data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						data_insert(280, EVENTS_ENABLE, $data);
				        //зачисление на счёт реинвестированного в ЖБТ $77000
						$sum = $m7['f3830'] + 77000;
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					}
				} elseif(($sp3['f4970'] == "") and ($sp3['f5060'] !== "")) {
					//если спонсор третьего колена стоит в семёрке, то ищем свободное место...
					$sm = data_select_array(320, "`id`=",$sp3['f5060']," AND `status`=0");
					if($sm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					} elseif($sm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$m7 = data_select_array(42, "`id`=",$sm['f4550']," AND `status`=0");
						$smarty_name = $m7['f3721'];
						$smarty_patr = $m7['f3850'];
						$smarty_lnames = 'Жилищно-Благотворительная';
						$smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						$smarty->assign("smarty_name", $smarty_name);
						$smarty->assign("smarty_patr", $smarty_patr);
						$smarty->assign("smarty_lnames", $smarty_lnames);
						$smarty->assign("smarty_calc", $smarty_calc);
						send_template(340, "`id`=" . $m7['id']);
						//занесение в хронику событий о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$data = Array();
						$data['f4080'] = $m7['id'];
						$data['f4090'] = 'Реинвест в ' . $matrix_programname;
						$data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						data_insert(280, EVENTS_ENABLE, $data);
				        //зачисление на счёт реинвестированного в ЖБТ $77000
						$sum = $m7['f3830'] + 77000;
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $sm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $sm['id']), "`id`=", $m['f5000']);
					}
				} else {
					//Если ни один из спонсоров не стоит в семёрке, то ставим перешедшегов в первую новую семёрку текущей программы...
					$descm = data_select_array(320, "`f4720`=5 AND `status`=0 ORDER BY `id` DESC LIMIT 1");
					if($descm['f5350'] == 4) {
						data_update(320, EVENTS_ENABLE, array("f4620" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 4, "f4970" => "", "f5060" => $descm['id']), "id=", $m['f5000']);
					} elseif($descm['f5350'] == 5) {
						data_update(320, EVENTS_ENABLE, array("f4630" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 5, "f4970" => "", "f5060" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 6) {
						data_update(320, EVENTS_ENABLE, array("f4640" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 6, "f4970" => "", "f5060" => $descm['id']), "id=", $m['f5000']); 
					} elseif($descm['f5350'] == 7) {
						//отправка сообщения реинвестированному о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$m7 = data_select_array(42, "`id`=",$descm['f4550']," AND `status`=0");
						$smarty_name = $m7['f3721'];
						$smarty_patr = $m7['f3850'];
						$smarty_lnames = 'Жилищно-Благотворительная';
						$smarty_calc = '$112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						$smarty->assign("smarty_name", $smarty_name);
						$smarty->assign("smarty_patr", $smarty_patr);
						$smarty->assign("smarty_lnames", $smarty_lnames);
						$smarty->assign("smarty_calc", $smarty_calc);
						send_template(340, "`id`=" . $m7['id']);
						//занесение в хронику событий о начислении и вычитам: $112000 (начислено) - $28000 (реинвест) - $7000 (благо) = $77000 (итого)
						$data = Array();
						$data['f4080'] = $m7['id'];
						$data['f4090'] = 'Реинвест в ' . $matrix_programname;
						$data['f4100'] = 'Реинвест. ИТОГО: $112000 начислено - $28000 реинвест - $7000 благо = $77000 зачислено';
						data_insert(280, EVENTS_ENABLE, $data);
				        //зачисление на счёт реинвестированного в ЖБТ $77000
						$sum = $m7['f3830'] + 77000;
						data_update(42, EVENTS_ENABLE, array("f3830" => $sum), "id=", $m7['id']);
						data_update(320, EVENTS_ENABLE, array("f4650" => $m['f4250']), "`id`=", $descm['id']);
						data_update(42, EVENTS_ENABLE, array("f4360" => 2, "f4770" => 7, "f4970" => "", "f5060" => $descm['id']), "id=", $m['f5000']);
					}
				}
				//отправка сообщения перешедшему в семёрку о начислении и вычитам: $28000 (начислено) - $28000 (вход в 7-ку) = $0 (зачислено)
				$mv = data_select_array(42, "`id`=",$m['f5000']," AND `status`=0");
				$smarty_name = $mv['f3721'];
				$smarty_patr = $mv['f3850'];
				$smarty_lnames = 'Жилищно-Благотворительная';
				$smarty_calc = '$28000 начислено - $28000 вход в 7-ку = $0 зачислено';
				$smarty->assign("smarty_name", $smarty_name);
				$smarty->assign("smarty_patr", $smarty_patr);
				$smarty->assign("smarty_lnames", $smarty_lnames);
				$smarty->assign("smarty_calc", $smarty_calc);
				send_template(330, "`id`=" . $mv['id']);
				//занесение в хронику событий перешедшему в семёрку о начислении и вычитам: $28000 (начислено) - $28000 (вход в 7-ку) = $0 (итого)
				$data = Array();
				$data['f4080'] = $mv['id'];
				$data['f4090'] = 'Вход в 7-ку ' . $matrix_programname;
				$data['f4100'] = 'Из 3-ки в 7-ку. ИТОГО: $28000 начислено - $28000 вход = $0 зачислено';
				data_insert(280, EVENTS_ENABLE, $data);
				//удаляем поделившуюся матрицу
				data_delete(62, EVENTS_ENABLE, "`id`=", $msp['f4970']);
			}
		} else {
			echo 'Указанного Вами спонсора нет в Тройках.';
			exit;
		}
		//---------------------- Вычисление переходов участников - КОНЕЦ ---------------------------
		echo 'DONE'; // Возвращаем сайту сообщение, что все в порядке
	} elseif(!$add_member) {
		echo 'Введите имя спонсора...';
		exit;
	}
}
?>