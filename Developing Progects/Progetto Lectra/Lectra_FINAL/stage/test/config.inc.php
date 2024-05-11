<?php

$path=dirname(__FILE__);





$env="stage";


/*******************
 * AS 400
 ******************/
$as400_dsn = 'AS400';
$as400_user = 'WAI';
$as400_password = 'S83DCPIX';
$as400_host = '172.31.212.240';



/*******************
 * DB SERVER BI
 ******************/

/* macchina 40 */
/*$db_user = 'ced';
$db_password = 'f3n1c14p';
$db_name = 'bi_stage';
$db_host = '127.0.0.1';*/


$db_user = 'ced';
$db_password = 'fr78iS-Ojk3....';
$db_name = 'bi_stage';
$db_host = '127.0.0.1';



/*******************
 * DB INDAGINI
 ******************/

/* SVILUPPO
$db_indagini_user= 'root';
$db_indagini_password = 's83dcpix!';
$db_indagini_name = 'fenicia';
$db_indagini_host = '192.168.91.16';
*/


/* PROD */
$db_indagini_user= 'root';
$db_indagini_password = 's83dcpix!';
$db_indagini_name = 'fenicia';
$db_indagini_host = '172.31.212.11';




/*******************
 * DB TIMBRATURE
 ******************/


/* SVILUPPO
$db_indagini_user= 'root';
$db_indagini_password = 's83dcpix!';
$db_indagini_name = 'fenicia';
$db_indagini_host = '192.168.91.16';
*/


/* PROD */
$db_timb_user = 'ced';
$db_timb_password = 'thUBUJH-OUjiN3....';
$db_timb_name = 'attendance';
$db_timb_host = '172.31.212.30';



/*  INDAGINI SVILUPPO */


$email=array("psalemi@feniciaspa.it");






?>
