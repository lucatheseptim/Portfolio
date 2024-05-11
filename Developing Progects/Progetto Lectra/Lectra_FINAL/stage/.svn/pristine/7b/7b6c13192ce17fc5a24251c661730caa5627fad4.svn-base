<?php


session_start();

//require_once('include/batchFunction.php');
require_once 'include/configurator.php';
require_once 'include/connector.php';
require_once('utils/Dbg.php');
require_once('utils/Req.php');
require_once('utils/ApiCaller.php');
require_once('utils/Scal.php');
require_once('utils/FieldManager.php');
require_once('utils/Field.php');
require_once('utils/WSSoapClient.php');
require_once('lib/TCPDF-main/tcpdf.php');
require_once('Lectra.php');


$backoffice_authorized_coms=array(7069,6735,5553,7242);


$email = array();
$email["logistica"] = array("psalemi@feniciaspa.it");
$email["ordine"] = array("psalemi@feniciaspa.it");
$email["fattura"] = array("psalemi@feniciaspa.it");
$email["proforma"] = array("psalemi@feniciaspa.it");


ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config_path= "/var/www/html/lectra/stage/";
$config = new Configurator($config_path);
$db = new MysqlConnector($config);
$as400 = new AS400Connector($config);
$db_ind = new IndaginiMysqlConnector($config);


//as400
Dbg::$debug_active = (isset($_REQUEST["debug"])&& $_REQUEST["debug"]==1)?1:0;
Dbg::$excluded_actions=array(
        "get_fields_data",
        "get_lista_ordini",
        "get_taglie",
        "get_componente",
        "get_alterations",
        "get_data_negozio",
        "get_contrasto",
        "get_manica_alterations",
        "get_polso_alterations",
        "check_ricami",
);
Dbg::$log_filename = "/var/www/html/lectra/stage/log/lectra.log";
$action=isset($_REQUEST["action"])?$_REQUEST["action"]:'';

$env = 'stage';
$pdf_path= "/var/www/html/lectra/".$env."/pdf/";
$pdf_ext_path = "https://185.53.150.222/su_misura/".$env."/pdf/";

Dbg::d("action",$action,1);

$logged_id='';
Dbg::log_action($logged_id,$action,$_REQUEST);

$api_auth_path = "https://api.mylectra.com/auth/my-credentials/client-credential";
$api_path_plan = "https://gateway-cuttingroom.cloudservices.mylectra.com/public/api/";
$api_path_prepare = "https://mass-custo-connector.cloudservices.mylectra.com/api/";



$session = isset($_SESSION)?$_SESSION:NULL;

$lectra =new AppLectra($env, $_REQUEST, $session,  $db, $as400, $db_ind, $api_auth_path, $api_path_plan, $api_path_prepare,$pdf_path,$pdf_ext_path,$backoffice_authorized_coms, $email);





?>
