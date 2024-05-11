<?php
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once ("$root/Models.php");
require_once ("$root/Controller/DhlController.php");
require_once ("Factory/FakerFactory.php");

use PHPUnit\Framework\TestCase;

class DhlControllerTest extends TestCase{
    /** @static array Array id model creati nei vari test. Vengono cancellati alla fine di tutti test*/
    public static $test_order_ids;
    /** @static FakerOrderFactory **/
    public static $order_factory;

    public static function setUpBeforeClass(): void{
        self::$test_order_ids = array();
        self::$order_factory = new FakerOrderFactory();
    }

    public function testGetContactDataFromCliente(): void{
        $order = self::$order_factory->createOrder(1);
        self::$test_order_ids[] = $order->id;
        $dhl_controller = new DhlController($order->id);
        $client = Cliente::find($order->cliente_id);

        $client_data = $dhl_controller->getContactDataFromCliente();

        $this->assertIsArray($client_data);
        $this->assertNotEmpty($client_data);
        $this->assertArrayHasKey("Contact", $client_data);
        $exp_client_data = $client_data["Contact"];
        $this->assertEquals($client->Cognome." " .$client->Nome, $exp_client_data["PersonName"]);
        $this->assertEquals($client->Telefono, $exp_client_data["PhoneNumber"]);
        $this->assertEquals($client->Email, $exp_client_data["EmailAddress"]);
        $this->assertArrayHasKey("Address", $client_data);
        $exp_address_data = $client_data["Address"];
        $this->assertArrayHasKey("StreetLines", $exp_address_data);
        $this->assertArrayHasKey("City", $exp_address_data);
        $this->assertArrayHasKey("PostalCode", $exp_address_data);
        $this->assertEquals("IT", $exp_address_data["CountryCode"]);
    }
    /**
     * Creazione ordine con singolo pacco da spedire
     * NON VANNO I DATA PROVIDER CON I DEPENDS!!!
     * @return Order
     */
    public function testCreateOrderPackagesSinglePackage(): Order{
        $n_products = 2;
        $order = self::$order_factory->createOrder($n_products);
        self::$test_order_ids[] = $order->id;
        $dhl_controller = new DhlController($order->id);

        $status = $dhl_controller->createOrderPackages();
        $this->assertTrue($status);
        $packages = DhlPackageOrder::where("order_id", $order->id);
        $expected_number_packages = intdiv($n_products, DhlController::MAX_QTY_CAMI) + 1;
        $this->assertEquals($expected_number_packages, sizeof($packages));
        $i = 1;
        $n = $n_products;
        foreach ($packages as $p){
            $this->assertEquals($order->id, $p->order_id);
            $this->assertEquals($order->id."_".$i, $p->reference);
            $expected_n_products_in_package = ($n < DhlController::MAX_QTY_CAMI) ? $n : DhlController::MAX_QTY_CAMI;
            $this->assertEquals($expected_n_products_in_package, $p->n_products);
            $n -= DhlController::MAX_QTY_CAMI;
            $this->assertEquals($i, $p->sequence_number);
            $i++;
        }

        return $order;
    }
    /**
     * @depends  testCreateOrderPackagesSinglePackage
     * @param Order $order
     */
    public function testGetPackagesDataSinglePackageWithoutWeight(Order $order): void{
        $dhl_controller = new DhlController($order->id);

        $dhl_packages = $dhl_controller->getPackagesData();
        $this->assertArrayHasKey("RequestedPackages", $dhl_packages);
        $packages = $dhl_packages["RequestedPackages"];
        $this->assertEquals(sizeof(DhlPackageOrder::where("order_id", $order->id)), sizeof($packages));
        $i = 1;
        foreach ($packages as $p){
            $this->assertEquals($i, $p["@number"]);
            $this->assertEquals(DhlController::MAX_WEIGHT_KG_CAMI_PACKAGE, $p["Weight"]);
            $this->assertEquals($order->id."_".$i, $p["CustomerReferences"]);
            $this->assertIsArray($p["Dimensions"]);
            $this->assertArrayHasKey("Length",  $p["Dimensions"]);
            $this->assertArrayHasKey("Width",  $p["Dimensions"]);
            $this->assertArrayHasKey("Height",  $p["Dimensions"]);
            $i++;
        }
    }
    /**
     * Creazione ordine con più pacchi da spedire
     * NON VANNO I DATA PROVIDER CON I DEPENDS!!!
     * @return Order
     */
    public function testCreateOrderPackagesMultiplePackage(): Order{
        $n_products = 8;
        $order = self::$order_factory->createOrder($n_products);
        self::$test_order_ids[] = $order->id;
        $dhl_controller = new DhlController($order->id);

        $status = $dhl_controller->createOrderPackages();
        $this->assertTrue($status);
        $packages = DhlPackageOrder::where("order_id", $order->id);
        $expected_number_packages = intdiv($n_products, DhlController::MAX_QTY_CAMI) + 1;
        $this->assertEquals($expected_number_packages, sizeof($packages));
        $i = 1;
        $n = $n_products;
        foreach ($packages as $p){
            $this->assertEquals($order->id, $p->order_id);
            $this->assertEquals($order->id."_".$i, $p->reference);
            $expected_n_products_in_package = ($n < DhlController::MAX_QTY_CAMI) ? $n : DhlController::MAX_QTY_CAMI;
            $this->assertEquals($expected_n_products_in_package, $p->n_products);
            $n -= DhlController::MAX_QTY_CAMI;
            $this->assertEquals($i, $p->sequence_number);
            $i++;
        }

        return $order;
    }
    /**
     * @depends  testCreateOrderPackagesMultiplePackage
     * @param Order $order
     */
    public function testGetPackagesDataMultiplePackageWithoutWeight(Order $order): void{
        $dhl_controller = new DhlController($order->id);

        $dhl_packages = $dhl_controller->getPackagesData();
        $this->assertArrayHasKey("RequestedPackages", $dhl_packages);
        $packages = $dhl_packages["RequestedPackages"];
        $this->assertEquals(sizeof(DhlPackageOrder::where("order_id", $order->id)), sizeof($packages));
        $i = 1;
        foreach ($packages as $p){
            $this->assertEquals($i, $p["@number"]);
            $this->assertEquals(DhlController::MAX_WEIGHT_KG_CAMI_PACKAGE, $p["Weight"]);
            $this->assertEquals($order->id."_".$i, $p["CustomerReferences"]);
            $this->assertIsArray($p["Dimensions"]);
            $this->assertArrayHasKey("Length",  $p["Dimensions"]);
            $this->assertArrayHasKey("Width",  $p["Dimensions"]);
            $this->assertArrayHasKey("Height",  $p["Dimensions"]);
            $i++;
        }
    }

    public static function tearDownAfterClass(): void{
        foreach (array_unique(self::$test_order_ids) as $id){
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
    }
    // Providers
    public function numProductsProvider(): array{
        return array(
            array(2),
            array(8)
        );
    }
}
