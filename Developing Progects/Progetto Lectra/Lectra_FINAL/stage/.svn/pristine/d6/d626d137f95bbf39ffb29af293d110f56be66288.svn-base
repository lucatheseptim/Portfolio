<?php
include_once ("/var/www/html/lectra/stage/Logger.php");
include_once ("/var/www/html/lectra/stage/Services/Services.php");
include_once ("/var/www/html/lectra/stage/Models.php");
include_once ("/var/www/html/lectra/stage/utils/ApiCaller.php");
include_once ("/var/www/html/lectra/stage/config.inc.php");
// include_once ("/var/www/html/lectra/stage/utils/Dbg.php");

// Dbg::$debug_active = 1;
// Dbg::$log_filename = "php://output";
$logger = new EchoLogger("cron_send_order");
$api_caller = new APICaller();

// Dbg::d("cron_send_order", "token: $token");
// $logger->log("token: $token");

$today = new DateTime();
$logger->log($today->format(Order::DATE_FORMAT));
$args = array(
    "deliveryDate < :delivery",
    "stato = :stato"
);
$params = array(":stato" => Order::APPROVED_STATE, ":delivery" => $today->format(Order::DATE_FORMAT));
$orders_not_sent = Order::whereRaw(implode(" AND ", $args), $params);
$logger->log("Ordini da inviare: ".sizeof($orders_not_sent));
$order_created = array();

$config = new Configurator("/var/www/html/lectra/stage/");
$as400 = new AS400Connector($config);
$order_service = new LectraOrderService($api_caller, $lectra_credentials, $as400);
/** @var Order $order */
foreach ($orders_not_sent as $order){
    $logger->log("perparing order ".$order->id);

    $res = $order_service->send($order);
    if($res["status"]){ // If OK save order to create batch
        $logger->log("Order Created");
        $order_created = array_merge($order_created, $res["data"]);
        $order->stato = Order::LECTRA_SENT_STATE;
        $order->save(); // Commento per test
    }else{
        $logger->log($res["error"]);
    }
}
// Create batch orders created
if(!empty($order_created)){
    $batch_reference = "BATCH_" . $today->format("Y_m_d_H_i_s");
    $res = $order_service->sendOrderBatch($batch_reference, array_keys($order_created));
    $logger->log("res batch", $res);
    if(!$res["status"]){
        $logger->log("Error batch $batch_reference: ".$res["error"], $res["data"]);
    }
}
$logger->log("END");
?>
