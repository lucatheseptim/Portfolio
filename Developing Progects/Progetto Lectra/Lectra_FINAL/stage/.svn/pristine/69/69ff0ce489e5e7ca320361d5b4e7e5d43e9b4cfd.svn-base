<?php
interface ILogger {
    public function log($message);
}

class EchoLogger implements ILogger{
    private $name;

    function __construct($name){
        $this->name = $name;
    }

    public function log($message, $data = null){
        $now = new Datetime();
        $data_str = "";
        if($data && is_array($data)){
            $data_str = print_r($data, 1);
        }
        echo("[".$now->format('Y-m-d H:i:s')."] EchoLogger $this->name: $message, [$data_str]\n");
    }
}
