<?php
require_once ("utils/Req.php");

class SaveOrderReq extends ReqMiddleware{
    protected $required = ["ordine_id" => "int", "cliente_id" => "int"];
}

class SaveOrderProductReq extends ReqMiddleware{
    protected $required = [
        "ordine_id" => "int",
        "products" => [
            "product_id" => "int",
            "qty" => "int",
            "grading" => "int",
            "scollo" => "int",
            "components" => [
                "component_id" => "int",
                "materiale" => "int"
            ]
        ]
    ];
}

class ChangeOrderStateReq extends ReqMiddleware{
    protected $required = [
        "ordine_id" => "int"
    ];
}

?>
