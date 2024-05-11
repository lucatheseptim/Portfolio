<?php
session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once ("$root/include/configurator.php");
require_once ("$root/include/connector.php");
require_once ("$root/utils/Field.php");
require_once ("$root/utils/Dbg.php");
require_once ("$root/utils/Req.php");
require_once ("$root/utils/ApiCaller.php");
require_once ("$root/utils/Scal.php");
require_once ("$root/utils/FieldManager.php");
require_once ("$root/utils/Field.php");
require_once ("$root/utils/Field.php");
// require_once ("$root/lib/TCPDF-main/tcpdf.php");
require_once ("$root/Lectra.php");
require_once ("Factory/FakerFactory.php");

use PHPUnit\Framework\TestCase;

class AppLectraTest extends TestCase{
    const STAGE_ENV = "stage";
    const API_AUTH_PATH = "https://api.mylectra.com/auth/my-credentials/client-credential";
    const API_PATH_PLAN = "https://gateway-cuttingroom.cloudservices.mylectra.com/public/api/";
    const API_PATH_PREPARE = "https://mass-custo-connector.cloudservices.mylectra.com/api/";
    const PDF_PATH = "/var/www/html/lectra/stage/pdf/test/";
    // const PDF_EXT_PATH = "https://185.53.150.222/su_misura/stage/pdf/";
    const PDF_EXT_PATH = "/var/www/html/lectra/stage/pdf/test/";
    /** @static AppLectra $app_lectra */
    public static $app_lectra;
    /** @static array $order_created_ids */
    public static $order_created_ids;
    /** @static MysqlConnector $db */
    public static $db;
    /** @static AS400Connector $as400 */
    public static $as400;
    /** @static IndaginiMysqlConnector $db_ind */
    public static $db_ind;

    public static function setUpBeforeClass(): void{
        $config = new Configurator(__DIR__);
        self::$db = new MysqlConnector($config);
        self::$as400 = new AS400Connector($config);
        self::$db_ind = new IndaginiMysqlConnector($config);
        self::$order_created_ids = array();
    }
    /**
     * @return string auth token
     */
    public function testDoAuth(): string{
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $lectra = $this->makeLectraObj($_REQUEST, $session);
        $res = $lectra->do_auth();
        $this->assertIsArray($res);
        $this->assertArrayHasKey("success", $res);
        $this->assertArrayHasKey("data", $res);
        $this->assertEquals(1, $res["success"]);
        $this->assertArrayHasKey("access_token", $res["data"]);
        $this->assertIsString($res["data"]["access_token"]);

        return $res["data"]["access_token"];
    }

    /**
     * @return int order_id
     */
    public function testSaveOrder(): int{
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $_SESSION["codneg"] = 101;
        $fake_request = array(
            "action" => "save_order",
            "val_fields" => array(
                "ordine_id" => null,
                "cliente_id" => 1,
                "comment" => ""
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);


        $echo_res = ob_get_contents();  // Prende i dati stampati nel output buffer (echo, print, ...)
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $this->assertArrayHasKey("input", $res);
        $this->assertArrayHasKey("output", $res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Ordine creato con successo", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // Check order created
        $order_data = $output["data"];
        $this->assertArrayHasKey("id", $order_data);
        self::$order_created_ids[] = $order_data["id"];
        $this->assertEquals($fake_request["val_fields"]["cliente_id"], $order_data["cliente_id"]);
        $this->assertEquals($_SESSION["codneg"], $order_data["salesPoint"]);
        $this->assertEquals($fake_request["val_fields"]["comment"], $order_data["comment"]);

        return $order_data["id"];
    }
    /**
     * @depends testSaveOrder
     */
    public function testSaveOrderProduct($order_id): void{
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $json = '{"ordine_id":'.$order_id.',"products":[{"id":null,"product_id":"1","qty":2,"grading":40,"scollo":40,"comment":"","gender":"m",'.
            '"assortment":"","specialGrading1":"","bodyReference":"","components":[{"component_id":"6","materiale":"1","contrasto":"2"}],'.
            '"alterations":[{"alteration_id":"2","value":10}]}]}';
        $fake_request = array(
            "action" => "save_order_product",
            "val_fields" => json_decode($json, true)
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $this->assertArrayHasKey("input", $res);
        $this->assertArrayHasKey("output", $res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Prodotti aggiunti con successo", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // Check products created
        $prod_data = $output["data"];
        $this->assertIsArray($prod_data);
        $this->assertEquals(sizeof($fake_request["val_fields"]["products"]), sizeof($prod_data));
    }
    /**
     * @depends testSaveOrder
     */
    public function testConfirmOrder($order_id): void{
        $this->markTestSkipped("check permessi TCPDF per eseguire questo test.");
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $fake_request = array(
            "action" => "confirm_order",
            "val_fields" => array(
                "ordine_id" => $order_id
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        $order = Order::find($order_id);
        $this->assertEquals(Order::PENDING_STATE, $order->stato);
    }

    /**
     * @depends testSaveOrder
     */
    public function testApproveOrder($order_id): void{
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $fake_request = array(
            "action" => "approve_order",
            "val_fields" => array(
                "ordine_id" => $order_id
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        $order = Order::find($order_id);
        $this->assertEquals(Order::APPROVED_STATE, $order->stato);
    }

    public function testSaveOrderWithRicami(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder();
        self::$order_created_ids[] = $order->id;
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $json = '{"ordine_id":'.$order->id.',"products":[{"id":null,"product_id":"1","qty":1,"grading":39,"scollo":40,'.
            '"components":[{"component_id":"6","materiale":"1"}],"alterations":[{"alteration_id":"2","value":10}],'.
            '"ricamo":{"testo":"ab ","stile":8,"pos_testo":6,"colore":1,"simbolo":13,"pos_simbolo":18,"ordine_simbolo":17,"colore_simbolo":1}'.
            '}]}';
        $fake_request = array();
        $fake_request["action"] =  "save_order_product";
        $fake_request["val_fields"] = json_decode($json, true);
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $this->assertArrayHasKey("input", $res);
        $this->assertArrayHasKey("output", $res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Prodotti aggiunti con successo", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // Check products created
        $prod_data = $output["data"];
        $this->assertIsArray($prod_data);
        $actual_prod = ProductOrder::where("order_id", $order->id);
        $this->assertEquals(1, sizeof($actual_prod));
        // Check ricami
        $actual_prod = $actual_prod[0]; // Ha solo 1 prodotto
        $actual_prod_ricami = ProductOrderDettaglio::where("order_product_id", $actual_prod->id);
        $this->assertNotEmpty($actual_prod_ricami);
        $this->assertEquals(2, sizeof($actual_prod_ricami));
        foreach ($actual_prod_ricami as $ricamo) {
            $this->assertEquals($actual_prod->id, $ricamo->order_product_id);
            $this->assertContains($ricamo->barcode, array("2000015782012", "2000016277012"));
            $this->assertContains($ricamo->tipo, array(ProductOrderDettaglio::TIPO_RIC_SIMBOLO, ProductOrderDettaglio::TIPO_RIC_TESTO));
        }
    }

    public function testSaveOrderWithBottoneMadreperla(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder();
        self::$order_created_ids[] = $order->id;
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $json = '{"ordine_id":'.$order->id.',"products":[{"id":null,"product_id":"1","qty":1,"grading":39,"scollo":40,'.
            '"components":[{"component_id":"6","materiale":"1"}],"alterations":[{"alteration_id":"2","value":10}],'.
            '"bottoni_mp":1}]}';
        $fake_request = array();
        $fake_request["action"] =  "save_order_product";
        $fake_request["val_fields"] = json_decode($json, true);
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $this->assertIsArray($res);
        $this->assertArrayHasKey("input", $res);
        $this->assertArrayHasKey("output", $res);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Prodotti aggiunti con successo", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // Check products created
        $prod_data = $output["data"];
        $this->assertIsArray($prod_data);
        $actual_prod = ProductOrder::where("order_id", $order->id);
        $this->assertEquals(1, sizeof($actual_prod));
        // Check bottone
        $actual_prod = $actual_prod[0]; // Ha solo 1 prodotto
        $actual_prod_ricami = ProductOrderDettaglio::where("order_product_id", $actual_prod->id);
        $this->assertNotEmpty($actual_prod_ricami);
        $this->assertEquals(1, sizeof($actual_prod_ricami));
        $ricamo = $actual_prod_ricami[0];
        $this->assertEquals($actual_prod->id, $ricamo->order_product_id);
        $bottone_mp = Dettaglio::where("tipo", ProductOrderDettaglio::TIPO_BOTTONI_MADRE_PERLA);
        $this->assertEquals($bottone_mp[0]->barcode, $ricamo->barcode);
        $this->assertEquals(ProductOrderDettaglio::TIPO_BOTTONI_MADRE_PERLA, $ricamo->tipo);
    }

    public function testDeleteOrderNotPermanent(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder();
        self::$order_created_ids[] = $order->id;
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $fake_request = array(
            "action" => "delete_order",
            "val_fields" => array(
                "ordine_id" => $order->id,
                "permanent" => 0
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Ordine cancellato correttamente", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // $this->assertEquals($order->id, $output["data"]);
        $order = Order::find($order->id);
        $this->assertNotNull($order);
        $this->assertEquals(Order::CANCELLED_STATE, $order->stato);
    }

    public function testDeleteOrderPermanent(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder(2);
        self::$order_created_ids[] = $order->id;
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $fake_request = array(
            "action" => "delete_order",
            "val_fields" => array(
                "ordine_id" => $order->id,
                "permanent" => 1
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Ordine cancellato correttamente", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // $this->assertEquals($order->id, $output["data"]);
        $this->assertNull(Order::find($order->id));
        $products = ProductOrder::where("order_id", $order->id);
        $this->assertEmpty($products);
    }

    public function testDeleteOrderProduct(): void{
        $order_factory = new FakerOrderFactory();
        $n_products = 2;
        $order = $order_factory->createOrder($n_products);
        self::$order_created_ids[] = $order->id;
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $order_products = ProductOrder::where("order_id", $order->id);
        $this->assertEquals($n_products, sizeof($order_products));
        $fake_request = array(
            "action" => "delete_order_product",
            "val_fields" => array(
                "product_id" => $order_products[0]->id
            )
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("Prodotto cancellato correttamente", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        // $this->assertEquals($order->id, $output["data"]);
        $this->assertNotNull(Order::find($order->id));
        $this->assertEquals($n_products - 1, sizeof(ProductOrder::where("order_id", $order->id)));
    }

    public function testGetOrderData(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder(2);
        self::$order_created_ids[] = $order->id;
        $order->session = session_id();
        $order->salesPoint = 101;
        $order->save();
        $session = isset($_SESSION) ? $_SESSION : NULL;
        $fake_request = array(
            "action" => "get_order_data"
        );
        ob_start();
        $lectra = $this->makeLectraObj($fake_request, $session);
        $echo_res = ob_get_contents();
        ob_end_clean();
        $res = json_decode($echo_res, true);
        $output = $res["output"];
        // Check result
        $this->assertArrayHasKey("success", $output);
        $this->assertEquals(1, $output["success"]);
        $this->assertArrayHasKey("error", $output);
        $this->assertEquals("", $output["error"]);
        $this->assertArrayHasKey("data", $output);
        $order_data = $output["data"];
        $this->assertEquals($order->id, $order_data["order_id"]);
    }

    public function testSaveProdottoCompletato(): void{
        $order_factory = new FakerOrderFactory();
        $order = $order_factory->createOrder(1);
        self::$order_created_ids[] = $order->id;
        // Set prodotti
        $products = ProductOrder::where("order_id", $order->id);
        $barcode_test = 1000000000000200;
        foreach ($products as $product){
            $product->inviato = 1;
            $product->da_cucire = 1;
            $product->barcode_univoco = $barcode_test;
            $prod_id = $product->save();
            if($prod_id < 0){   // Se non salva il prodotto correttamente manda in errore il test
                $this->fail("Errore salvataggio prodotto");
            }
            $barcode_test++;
        }
        // Requests
        foreach ($products as $p) {
            $session = isset($_SESSION) ? $_SESSION : NULL;
            $fake_request = array(
                "action" => "save_prodotto_completato",
                "val_barcode" => $p->barcode_univoco
            );
            ob_start();
            $lectra = $this->makeLectraObj($fake_request, $session);
            $echo_res = ob_get_contents();
            ob_end_clean();
            $res = json_decode($echo_res, true);
            $output = $res["output"];
            // Check result
            $this->assertArrayHasKey("success", $output);
            $this->assertEquals(1, $output["success"]);
            $this->assertArrayHasKey("error", $output);
            // $this->assertEquals("", $output["error"]);
            $this->assertArrayHasKey("data", $output);
        }
        // Check stato ordine
        $actual_order = Order::find($order->id);
        $this->assertEquals(Order::DHL_SENT_STATE, $actual_order->stato);
        $this->assertNotEquals("", $actual_order->dhl_confirmation_number);
        $this->assertNotNull($actual_order->dhl_confirmation_number);
        // Check aggiornamento prodotto
        $actual_products = ProductOrder::where("order_id", $order->id);
        foreach ($actual_products as $act_product){
            $this->assertEquals(1, $act_product->completato);
        }
        // Check DHL package
        $actual_package = DhlPackageOrder::where("order_id", $order->id);
        foreach ($actual_package as $act_pack){
            $this->assertNotEquals("", $act_pack->tracking_number);
            $this->assertNotNull($act_pack->tracking_number);
        }
        // Check lettera di vettura
        $pdf_filename = self::PDF_EXT_PATH."lettera_vettura_ordine_".$order->id."*.pdf";
        $res = glob($pdf_filename);
        $this->assertEquals(2, sizeof($res));
    }

    private function makeLectraObj($request, $session){
        $fake_codcom = 1;
        $fake_session = ["auth" => [], "codcom" => [$fake_codcom]]; // fake auth
        return new AppLectra(
            self::STAGE_ENV,
            $request,
            $fake_session,
            self::$db,
            self::$as400,
            self::$db_ind,
            self::API_AUTH_PATH,
            self::API_PATH_PLAN,
            self::API_PATH_PREPARE,
            self::PDF_PATH,
            self::PDF_EXT_PATH,
            array($fake_codcom),
            array(
                "logistica" => "test.email@test.com",
                "fattura" => "test.email@test.com",
                "proforma" => "test.email@test.com",
            )
        );
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
            $packages = DhlPackageOrder::where("order_id", $id);
            foreach ($packages as $pack){
                DhlPackageOrder::delete($pack->id);
            }
            Order::delete($id);
        }
        // Elimina i file generati
        $files = glob(self::PDF_EXT_PATH . "*");
        foreach($files as $file){
            if(is_file($file)) {
                unlink($file);
            }
        }
        // Fine sessione
        session_destroy();
    }
}
?>
