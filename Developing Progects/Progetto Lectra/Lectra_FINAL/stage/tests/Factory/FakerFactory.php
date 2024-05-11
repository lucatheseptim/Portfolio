<?php
require_once ("/var/www/html/lectra/stage/Models.php");
/**
 * Generatore casuale
 */
class Faker {
    const DIGITS = "0123456789";
    const LOW_ALPHABET = "abcdefghijklmnopqrstuvwxyz";
    const UP_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const SPECIAL_CHAR = "\"'?\\|;:,.";
    const SPACE_CHAR = " ";
    /**
     * Genera un int random
     * @param  int    $min range basso
     * @param  int    $max range alto (incluso)
     * @return int
     */
    public function generateInt(int $min , int $max){
        return rand($min, $max);
    }
    /**
     * Genera una stringa casuale
     * @param  int $length    lunghezza strina output
     * @param  string $char_pool  (Opzionale) pool dalla quale pescare i caratteri. se vuota usa tutti i caratteri
     * @return string
     */
    public function generateString(string $length, string $char_pool = ""){
        $characters = ($char_pool == "")
            ? self::DIGITS.self::LOW_ALPHABET.self::UP_ALPHABET.self::SPECIAL_CHAR.self::SPACE_CHAR
            : $char_pool;
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        return $random_string;
    }
    /**
     * Extracts random element from array
     * @param  array  $array
     * @return mixed|null
     */
    public function extractRandomElementFromArray(array $array){
        return (!empty($array)) ? $array[$this->generateInt(0, sizeof($array) - 1)] : null;
    }
}
/**
 * Crea un ordine finto nel db (x test)
 */
class FakerOrderFactory extends Faker {

    function __construct(){}
    /**
     * Crea un ordine finto nel db
     * @param int $n_prod alla quale associare il prodotto
     * @return Order|null  model creato. null in caso di errore
     */
    public function createOrder(int $n_prod = 0){
        $customer_model = $this->extractRandomElementFromArray(Cliente::all());
        $order = new Order();
        $order->cliente_id = $customer_model->id;
        $order->codcom = $this->generateInt(1000, 9999);
        $order->comment = addslashes($this->generateString(200));
        $order->factory = "Mecenate";
        $order->salesPoint = $this->generateInt(100, 999);
        $order->priority = $this->generateInt(1, 10);
        $order->deliveryDate = new DateTime();
        $order->orderDate = new DateTime();
        $order->measuresUnit = "cm";
        $order->stato = Order::NEW_STATE;
        $order_id = $order->save();
        $order->sequenceNumber = $order->id;
        $order_id = $order->save();
        if($order_id < 0){
            return null;
        }
        // Add products
        if($n_prod > 0){
            for ($i=0; $i < $n_prod; $i++) {
                $this->createProductOrder($order_id, $i);
            }
        }

        return $order;
    }
    /**
     * Crea un prodotto all'interno dell'ordine
     * @param int $order_id          id ordine alla quale associare il prodotto
     * @param int $sequence_number   numero sequenziale all'interno dell'ordine
     * @return ProductOrder|null  model creato. null in caso di errore
     */
    public function createProductOrder(int $order_id, int $sequence_number){
        $product_model = $this->extractRandomElementFromArray(Product::all());
        $product_order = new ProductOrder();
        $product_order->order_id = $order_id;
        $product_order->product_id = $product_model->id;
        $product_order->gender = $this->generateString(1, "mf");
        $product_order->comment = addslashes($this->generateString(200));
        $product_order->quantity = $this->generateInt(1,3);
        $product_order->assortment = $this->generateString(10, self::LOW_ALPHABET);
        $product_order->grading = $this->generateInt(37,50);
        $product_order->specialGrading1 = $this->generateInt(37,50);
        $product_order->bodyReference = $order_id;
        $product_order->inviato = 0;
        $product_order->da_cucire = 0;
        $product_order->completato = 0;
        $product_order->sequenceNumber = $sequence_number + 1;
        $product_order_id = $product_order->save();
        if($product_order_id < 0){
            return null;
        }
        foreach (TipologyComponent::all() as $tipologia_componente){
            $component = $this->extractRandomElementFromArray(Component::where("component_type_id", $tipologia_componente->id));
            if($component){
                $this->createProductComponentOrder($product_order_id, $component->id);
            }
        }
        foreach (Alteration::all() as $alteration){
            $value = $this->extractRandomElementFromArray(range($alteration->min_size, $alteration->max_size, $alteration->step));
            $this->createProductAlterationOrder($product_order_id, $alteration->id, $value);
        }

        return $product_order;
    }
    /**
     * Crea un componente all'interno del prodotto
     * @param  int    $product_id    id prodotto da associare
     * @param  int    $component_id  (Opzionale) id componente da associare
     * @return ProductOrderComponent|null  model creato. null in caso di errore
     */
    public function createProductComponentOrder(int $product_id, int $component_id = -1){
        if($component_id == -1){
            $component = $this->extractRandomElementFromArray(Component::all());
            $component_id = $component->id;
        }
        $material = $this->extractRandomElementFromArray(Materiale::all());
        $product_component = new ProductOrderComponent();
        $product_component->order_product_id = $product_id;
        $product_component->component_id = $component_id;
        $product_component->material_id = $material->id;
        $product_component->comment = addslashes($this->generateString(200));
        $product_component->contrastReference = null;
        $id = $product_component->save();

        return ($id > 0) ? $product_component : null;
    }
    /**
     * Crea un'alterazione all'interno del prodotto
     * @param  int    $product_id    id prodotto da associare
     * @param  int    $alteration_id id alterazione
     * @param float  $value         valore alterazione
     * @return ProductOrderAlteration|null
     */
    public function createProductAlterationOrder(int $product_id, int $alteration_id, $value){
        $product_alteration = new ProductOrderAlteration();
        $product_alteration->order_product_id = $product_id;
        $product_alteration->alteration_id = $alteration_id;
        $product_alteration->value = $value;
        $id = $product_alteration->save();

        return ($id > 0) ? $product_alteration : null;

    }

}
?>
