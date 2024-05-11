<?php
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once ("$root/Models.php");

use PHPUnit\Framework\TestCase;

class OrderModelTest extends TestCase{
    /** @static array Array id model creati nei vari test. Vengono cancellati alla fine di tutti test*/
    public static $test_model_id;

    public static function setUpBeforeClass(): void{
        self::$test_model_id = array();
    }
    /**
     * @return Order order model created
     */
    public function testOrderModelInit(): Order{
        $order_model = new Order();
        $this->assertInstanceOf(Order::class, $order_model);

        $fields = self::getProtectedProperty($order_model, "fields");
        foreach ($fields as $f) {
            $this->assertObjectHasAttribute($f, $order_model);
        }

        return $order_model;
    }
    /** @depends testOrderModelInit */
    public function testOrderModelExists(Order $order_model): Order{
        $test_method = self::getMethod(Order::class, "exists");
        $this->assertFalse($test_method->invokeArgs($order_model, array())); // Non esiste ancora
        $order_model->cliente_id = 1;
        $order_model->comment = "test comment";
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;
        $this->assertTrue($test_method->invokeArgs($order_model, array()));
        return $order_model;
    }
    /** @depends testOrderModelExists */
    public function testUpdateOrderModelSave(Order $order_model): void{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->comment = "new comment";
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $this->assertEquals($order_model->id, $inserted_id);
        $order_model_actual = Order::find($inserted_id);
        $this->assertEquals($order_model->comment, $order_model_actual->comment);
    }

    public function testNewOrderModelSave(): void{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->comment = "test 123";
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $this->assertGreaterThan(0, $inserted_id);
        $this->assertEquals($order_model->id, $inserted_id);
    }

    public function testNewOrderModelSaveDateTimeValue(): Order{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->deliveryDate = new \DateTime();
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $this->assertGreaterThan(0, $inserted_id);
        $order_model_actual = Order::find($inserted_id);
        $this->AssertInstanceOf(\DateTime::class, $order_model_actual->deliveryDate);
        $this->assertEquals(
            $order_model->deliveryDate->format(Model::DATE_FORMAT),
            $order_model_actual->deliveryDate->format(Model::DATE_FORMAT)
        );

        return $order_model;
    }
    /** @depends testNewOrderModelSaveDateTimeValue */
    public function testNewOrderModelUpdateDateTimeValue(Order $order_model): void{
        $order_model->deliveryDate = new \DateTime();
        $order_model->cliente_id = 1;
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $this->assertGreaterThan(0, $inserted_id);
        $order_model_actual = Order::find($inserted_id);
        $this->AssertInstanceOf(\DateTime::class, $order_model_actual->deliveryDate);
        $this->assertEquals(
            $order_model->deliveryDate->format(Model::DATE_FORMAT),
            $order_model_actual->deliveryDate->format(Model::DATE_FORMAT)
        );
    }

    public function testOrderModelDelete(): void{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->comment = "test 123";
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $this->assertGreaterThan(0, $inserted_id);
        $deleted_id = Order::delete($inserted_id);
        $this->assertEquals($inserted_id, $deleted_id);
    }

    public function testOrderModelWhere(): void{
        $order_model1 = new Order();
        $order_model1->cliente_id = 1;
        $order_model1->comment = "test 123";
        $order_model1->priority = -1;
        $inserted_id1 = $order_model1->save();
        self::$test_model_id[] = $inserted_id1;

        $order_model2 = new Order();
        $order_model2->cliente_id = 1;
        $order_model2->comment = "test 234";
        $order_model2->priority = -1;
        $inserted_id2 = $order_model2->save();
        self::$test_model_id[] = $inserted_id2;
        // Not found
        $res = Order::where("priority", 4);
        $this->assertIsArray($res);
        // $this->assertEmpty($res);
        // Found 2
        $res = Order::where("priority", -1);
        $this->assertIsArray($res);
        $this->assertEquals(2, sizeof($res));
        foreach ($res as $model){
            $this->assertInstanceOf(Order::class, $model);
            $this->assertEquals($model->priority, -1);
            $this->assertContains($model->id, array($inserted_id1, $inserted_id2));
        }
        // Found 1
        $res = Order::where("comment", "test 234");
        $this->assertIsArray($res);
        $this->assertEquals(1, sizeof($res));
        foreach ($res as $model){
            $this->assertInstanceOf(Order::class, $model);
            $this->assertEquals($model->comment, "test 234");
            $this->assertContains($model->id, array($inserted_id1, $inserted_id2));
        }
    }

    public function testOrderModelAll(): void{
        $config = new Configurator(__DIR__);
        $db = new MysqlConnector($config);
        $orders = Order::all();
        $this->assertIsArray($orders);
        $query = "SELECT COUNT(*) AS NUM FROM ".(new Order)->getTable();
        $res = $db->query($query);
        $this->assertEquals($res[0]->NUM, sizeof($orders));
    }

    public function testOrderModelToArray(): void{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->comment = "test 123";
        $order_model->priority = 1;
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;
        $model_array = $order_model->toArray();
        $this->assertIsArray($model_array);
        foreach ($order_model->getFields() as $field){
            $this->assertArrayHasKey($field, $model_array);
        }
    }

    public function testFixExcapeStringOnFind(): void{
        $order_model = new Order();
        $order_model->cliente_id = 1;
        $order_model->comment = addslashes("test 123';;'");
        $order_model->priority = -1;
        $inserted_id = $order_model->save();
        self::$test_model_id[] = $inserted_id;

        $order = Order::find($inserted_id);
        $order->priority = 1;
        $actual_update_id = $order->save();
        $this->assertEquals($inserted_id, $actual_update_id);
        $this->assertEquals(addslashes("test 123';;'"), $order->comment);
    }

    public static function tearDownAfterClass(): void{
        foreach (array_unique(self::$test_model_id) as $id){
            Order::delete($id);
        }
    }

    /** Makes private / protected properties usable */
    public static function getProtectedProperty($obj, $property){
        $reflection = new \ReflectionClass($obj);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        return $reflection_property->getValue($obj);
    }
    /** Makes private / protected methods tesable */
    protected static function getMethod($obj_class, $name) {
        $class = new ReflectionClass($obj_class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
?>
