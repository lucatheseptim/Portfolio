<?php
/** File inutilizzato. Serve solo come prova per le chiamate API di Lectra */
require_once('utils/ApiCaller.php');
require_once('utils/Dbg.php');
require_once('utils/Req.php');

$api_caller = new APICaller();
$api_caller->setHeader(array("Content-Type: application/json"));
$auth_data = array("username" => "ced", "password" => "Camicissima12");
$ip_address = "backend-cdqiuqi-zgzvw2kr4mr5m.eu-5.magentosite.cloud";
$res = $api_caller->callAPI("POST", $ip_address."/rest/V1/integration/admin/token", json_encode($auth_data));
echo (string) json_decode($res);

unset($api_caller);

$headers = array("authorization: Basic UWdGVmxCT1RVRkoxQldKM250YmxBU1h3WkMxS2wyUlM6VVRaUHNjdXIyRDJOZjV1Tld0LUdMTW85TGpZeDN2eUsyQUlkb213eG5YSU5ETE1XNFZma1pndmxWaGpzUzlPaQ==","Content-Type: application/json");
$api_caller = new APICaller();
$api_caller->setHeader($headers);
$params= array();
$params["audience"]="https://plan-cuttingroom.api.mylectra.com";
$params = json_encode($params,JSON_UNESCAPED_SLASHES);
$res = $api_caller->callAPI("POST", "https://api.mylectra.com/auth/my-credentials/client-credential", $params);
echo(print_r(json_decode($res),1));
?>
