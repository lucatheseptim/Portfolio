<?php
require_once ("/var/www/html/lectra/stage/Services/Services.php");
require_once ("/var/www/html/lectra/stage/Models.php");
require_once ("/var/www/html/lectra/stage/utils/ApiCaller.php");
require_once ("Factory/FakerFactory.php");
include_once ("/var/www/html/lectra/stage/config.inc.php");

use PHPUnit\Framework\TestCase;

class LectraOrderServiceTest extends TestCase{
    /** @static array Array id model creati nei vari test. Vengono cancellati alla fine di tutti test*/
    public static $order_created_ids;
    /** @static FakerOrderFactory **/
    public static $order_factory;
    /** @static $credenziali_lectra **/
    private static $lectra_credentials;

    public static function setUpBeforeClass(): void{
        global $lectra_credentials;
        self::$order_created_ids = array();
        self::$order_factory = new FakerOrderFactory();
        self::$lectra_credentials = $lectra_credentials;
    }

    public function testPrepareLectraOrderData(): void{
        $config = new Configurator(__DIR__);
        $lectra_order = new LectraOrderService(
            new ApiCaller(),
            self::$lectra_credentials,
            new MysqlConnector($config)
        );
        $order = self::$order_factory->createOrder(2);
        self::$order_created_ids[] = $order->id;
        foreach (ProductOrder::where("order_id", $order->id) as $product){
            $res = $lectra_order->prepareLectraOrderData($order, $product);
            $this->assertIsArray($res);
            $this->assertIsString($res["reference"]);
            $this->assertArrayHasKey("products", $res);
            $this->assertIsArray($res["products"]);
            $this->assertEquals(1, sizeof($res["products"])); // Soluzione 1 prodotto x ordine
            foreach ($res["products"] as $order_product){
                $this->assertArrayHasKey("productReference", $order_product);
                $this->assertArrayHasKey("boc", $order_product);
                foreach ($order_product["boc"] as $boc){
                    $this->assertArrayHasKey("componentReference", $boc);
                }
                $this->assertArrayHasKey("bom", $order_product);
                foreach ($order_product["bom"] as $bom){
                    $this->assertArrayHasKey("materialPosition", $bom);
                    $this->assertContains($bom["materialPosition"], array(Materiale::TESS_PRINC, Materiale::TESS_SEC));
                    $this->assertArrayHasKey("materialReference", $bom);
                    // $this->assertNotEmpty(Materiale::where("reference", $bom["materialReference"])); // Controlla che esiste il materiale nel DB
                    $this->assertArrayHasKey("components", $bom);
                    $this->assertIsArray($bom["components"]);
                    foreach ($bom["components"] as $component){
                        $this->assertArrayHasKey("componentReference", $component);
                    }
                }
            }
        }
    }
    /**
     * Elimina l'ordine alla fine dei test.
     * Se va in errore di sinstassi il codice non lo elimina e va fatto a mano dal DB
     */
    public static function tearDownAfterClass(): void{
        foreach (array_unique(self::$order_created_ids) as $id){
            $prod_orders = ProductOrder::where("order_id", $id);
            foreach ($prod_orders as $product){
                ProductOrder::delete($product->id);
            }
            Order::delete($id);
        }
    }
}
