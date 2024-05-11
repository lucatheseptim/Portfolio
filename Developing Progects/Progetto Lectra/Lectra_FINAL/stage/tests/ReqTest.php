<?php
require_once ("/var/www/html/lectra/stage/Requests.php");

use PHPUnit\Framework\TestCase;

class ReqTest extends TestCase{
    public function testCheckRequiredDataValid(): void{
        $request = new SaveOrderReq();
        $data = array("ordine_id" => 1, "cliente_id" => 1);
        $res = $request->check_required_data($data);
        $this->assertEquals(1, $res["success"]);
        $this->assertEquals("", $res["error"]);
    }

    public function testCheckRequiredDataNotValid(): void{
        $request = new SaveOrderReq();
        $data = array();
        $res = $request->check_required_data($data);
        $this->assertEquals(0, $res["success"]);
        $this->assertEquals("Campo ordine_id obbligatorio", $res["error"]);
    }

    public function testCheckRequiredRecursiveDataValid(): void{
        $request = new SaveOrderProductReq();
        $data = [
            "ordine_id" => 1,
            "products" => [
                [
                    "product_id" => 1,
                    "qty" => 2,
                    "grading" => 39,
                    "scollo" => 39,
                    "components" => [
                        [
                            "component_id" => 1,
                            "materiale" => 2,
                        ]
                    ]
                ]
            ]
        ];
        $res = $request->check_required_data($data);
        $this->assertEquals(1, $res["success"]);
        $this->assertEquals("", $res["error"]);
    }

    public function testCheckRequiredRecursiveDataNotValid(): void{
        $request = new SaveOrderProductReq();
        $data = [
            "ordine_id" => 1,
            "products" => [
                [
                    "product_id" => 1,
                    "qty" => 2,
                    "grading" => 39,
                    "scollo" => 39,
                    "components" => [
                        [
                            "component_id" => 1,
                            "materiale" => 2,
                        ]
                    ]
                ],
                [
                    "product_id" => 2,
                    "grading" => 40,
                    "scollo" => 39,
                    "components" => [
                        [
                            "component_id" => 1,
                            "materiale" => 2,
                        ]
                    ]
                ]
            ]
        ];
        $res = $request->check_required_data($data);
        $this->assertEquals(0, $res["success"]);
        $this->assertEquals("Campo qty obbligatorio", $res["error"]);
    }
}

?>
