<?php
require_once ("/var/www/html/lectra/stage/Models.php");
/**
 * Classi relative ai controller. Gestiscono le varie REQUEST. I dati passati in questa classe sono già controllati
 */
class OrderController {
    function __construct(){}
    /**
     * Salva ordine
     * @param  array $data
     * @return Order|null  Model ordine creato. Null in caso di errore
     * @throws Exception Eccezzione possibili durante il save()
     */
    public function saveOrder($data){
        $order = (isset($data["ordine_id"]) && $data["ordine_id"] != "") ? Order::find($data["ordine_id"]) : new Order(); // se esiste modifica l'ordine
        if(is_null($order))
            return null;
        $order->cliente_id = $data["cliente_id"];
        $order->codcom = (isset($_SESSION["codcom"])) ? $_SESSION["codcom"] : null;
        $order->session = (session_id()) ? session_id() : "";
        $order->comment = isset($data["comment"]) ? $data["comment"] : "";
        $order->factory = Order::ORDER_FCT;
        $order->salesPoint = (isset($_SESSION["codneg"])) ? $_SESSION["codneg"] : "";
        $order->priority = 1;
        $order->deliveryDate = new DateTime();
        $order->orderDate = null;
        $order->measuresUnit = "cm";
        $order->stato = Order::NEW_STATE;
        $order_id = $order->save();
        $order->sequenceNumber = $order->id;
        $order_id = $order->save();

        return ($order_id >= 0) ? $order : null;
    }
    /**
     * Aggiunge dei prodotti all'ordine. Se un prodotto va in errore durante la creazione effettua il rollback dei prodotti
     * @param  int $order_id
     * @param  array $data
     * @param  int $index   Indice di partenza del sequenceNumber del prodotto. Max(sequenceNumer) + 1
     * @return ProductOrder[]  Model creati
     * @throws Exception Eccezzione possibili durante il save(). Se si verifica un'eccezione di un prodotto effettua il rollback degli altri
     */
    public function saveOrderProducts($order_id, $data, $index = 0){
        $products = array();
        $order = Order::find($order_id);
        if($order && isset($data["products"])){
            $i = 0;
            foreach ($data["products"] as $data_product){
                $product_order = (isset($data_product["id"]) && $data_product["id"] != "") ? ProductOrder::find($data_product["id"]) : new ProductOrder();
                $product_order->order_id = $order_id;
                $product_order->product_id = $data_product["product_id"];
                $product_order->quantity = $data_product["qty"];
                $product_order->grading = $data_product["grading"];
                $product_order->comment = "Prodotto ".($index + $i);
                $product_order->barcode_univoco = $data_product["barcode"];
                $product_order->barcode_prezzo = $data_product["barcode_prezzo"];
                $product_order->inviato = 0;
                $product_order->da_cucire = 0;
                $product_order->completato = 0;
                // Opzionali
                $product_order->gender = (isset($data_product["gender"])) ? addslashes($data_product["gender"]) : ProductOrder::GENDER_M;
                $product_order->assortment = (isset($data_product["assortment"])) ? addslashes($data_product["assortment"]) : null;
                $product_order->specialGrading1 = (isset($data_product["specialGrading1"])) ? addslashes($data_product["specialGrading1"]) : null;
                $product_order->bodyReference = (isset($data_product["bodyReference"])) ? addslashes($data_product["bodyReference"]) : null;
                $product_order->sequenceNumber = $index + $i;
                try{
                    $prod_ord_id = $product_order->save();
                    $products[] = $product_order;
                    if($prod_ord_id < 0){
                        throw new Exception("Errore durante la creazione del prodotto");
                    }
                    // Components
                    $this->rollback(ProductOrderComponent::class, ProductOrderComponent::where("order_product_id", $prod_ord_id));
                    foreach ($data_product["components"] as $component){
                        $this->saveOrderProductComponent($prod_ord_id, $component);
                    }
                    // Alterations
                    if(isset($data_product["alterations"])){
                        $this->rollback(ProductOrderAlteration::class, ProductOrderAlteration::where("order_product_id", $prod_ord_id));
                        foreach ($data_product["alterations"] as $alteration){
                            $this->saveOrderProductAlteration($prod_ord_id, $alteration);
                        }
                    }
                    // Ricami
                    if(isset($data_product["ricamo"])){
                        $dettagli_prod = ProductOrderDettaglio::whereRaw(
                            "order_product_id = :order_prod AND tipo IN (:tipo1, :tipo2)",
                            array(":order_prod" => $prod_ord_id, ":tipo1" => ProductOrderDettaglio::TIPO_RIC_TESTO, ":tipo2" => ProductOrderDettaglio::TIPO_RIC_SIMBOLO)
                        );
                        $this->rollback(ProductOrderDettaglio::class, $dettagli_prod);
                        $this->saveOrderProductRicami($prod_ord_id, $data_product["ricamo"]);
                    }
                    // Bottoni mp
                    if(isset($data_product["bottoni_mp"])){
                        // $this->rollback(ProductOrderDettaglio::class, ProductOrderDettaglio::where("order_product_id", $prod_ord_id));
                        $dettagli_prod = ProductOrderDettaglio::whereRaw(
                            "order_product_id = :order_prod AND tipo = :tipo",
                            array(":order_prod" => $prod_ord_id, ":tipo" => ProductOrderDettaglio::TIPO_BOTTONI_MADRE_PERLA)
                        );
                        $this->rollback(ProductOrderDettaglio::class, $dettagli_prod);
                        $bottone_mp = Dettaglio::where("tipo", ProductOrderDettaglio::TIPO_BOTTONI_MADRE_PERLA);
                        if(!empty($bottone_mp)){
                            $this->saveOrderProductDettaglio($prod_ord_id, $bottone_mp[0]);
                        }
                    }
                }catch(Exception $e){
                    // Se un prodotto va in errore cancello tutti gli altri prodotti creati prima
                    $this->rollback(ProductOrder::class, $products);
                    throw $e;
                }
                $i++;
                unset($product_order);
            }
        }

        return $products;
    }
    /**
     * Aggiunge il componente al prodotto
     * @param  int $prod_ord_id    id order_product
     * @param  array $component_data dati componente
     * @return ProductOrderComponent!null
     */
    public function saveOrderProductComponent($prod_ord_id, $component_data){
        $component = new ProductOrderComponent();
        $component->order_product_id = $prod_ord_id;
        $component->component_id = $component_data["component_id"];
        $component->material_id = $component_data["materiale"];
        // (Opzionali)
        $component->comment = (isset($data["comment"])) ? addslashes($data["comment"]) : "";
        $component->contrastReference = (isset($data["contrasto"])) ? $data["contrasto"] : null;

        $component_id = $component->save();

        return ($component_id >= 0) ? $component : null;
    }
    /**
     * Aggiunge l'alterazione al prodotto
     * @param  int $prod_ord_id    id order_product
     * @param  array $alteration_data dati alterazione
     * @return ProductOrderComponent!null
     */
    public function saveOrderProductAlteration($prod_ord_id, $alteration_data){
        $alteration = new ProductOrderAlteration();
        $alteration->order_product_id = $prod_ord_id;
        $alteration->alteration_id = $alteration_data["alteration_id"];
        $alteration->value = $alteration_data["value"];

        $alteration_id = $alteration->save();

        return ($alteration_id >= 0) ? $alteration : null;
    }
    /**
     * Salva un ricamo collegato al prodotto nell'ordine
     * @param  int $prod_ord_id        id ProductOrder
     * @param  array $ricamo_data      Dati ricamo
     * @return ProductOrderDettaglio[]
     */
    public function saveOrderProductRicami($prod_ord_id, $ricamo_data){
        $ricami = array();
        // Ricamo testo
        if(isset($ricamo_data["barcodes"][Dettaglio::RIC_LETTERE])){
            $prod_ricamo = new ProductOrderDettaglio();
            $prod_ricamo->order_product_id = $prod_ord_id;
            $prod_ricamo->barcode = $ricamo_data["barcodes"][Dettaglio::RIC_LETTERE]["barcode"];
            $prod_ricamo->posizione = $ricamo_data["pos_testo"];
            $prod_ricamo->colore = $ricamo_data["colore"];
            $prod_ricamo->stile_testo = $ricamo_data["stile"];
            $prod_ricamo->testo = trim($ricamo_data["testo"]);
            $prod_ricamo->prezzo = $ricamo_data["barcodes"][Dettaglio::RIC_LETTERE]["prezzo"];
            $prod_ricamo->tipo = ProductOrderDettaglio::TIPO_RIC_TESTO;
            $ricamo_id = $prod_ricamo->save();
            if($ricamo_id >= 0){
                $ricami[$ricamo_id] = $prod_ricamo;
            }
        }
        // Ricamo simbolo
        if(isset($ricamo_data["barcodes"][Dettaglio::RIC_SIMBOLO])){
            $prod_ricamo = new ProductOrderDettaglio();
            $prod_ricamo->order_product_id = $prod_ord_id;
            $prod_ricamo->barcode = $ricamo_data["barcodes"][Dettaglio::RIC_SIMBOLO]["barcode"];
            $prod_ricamo->posizione = $ricamo_data["pos_simbolo"];
            $prod_ricamo->colore = $ricamo_data["colore_simbolo"];
            $prod_ricamo->simbolo = $ricamo_data["simbolo"];
            $prod_ricamo->ordine_simbolo = $ricamo_data["ordine_simbolo"];
            $prod_ricamo->prezzo = $ricamo_data["barcodes"][Dettaglio::RIC_SIMBOLO]["prezzo"];
            $prod_ricamo->prezzo = $ricamo_data["barcodes"][Dettaglio::RIC_SIMBOLO]["prezzo"];
            $prod_ricamo->tipo = ProductOrderDettaglio::TIPO_RIC_SIMBOLO;
            $ricamo_id = $prod_ricamo->save();
            if($ricamo_id >= 0){
                $ricami[$ricamo_id] = $prod_ricamo;
            }
        }

        return $ricami;
    }
    /**
     * Salva un'opzione al prodotto ordinato (ricamo, barcode, ...)
     * @param  int $prod_ord_id         id ProductOrder
     * @param  Dettaglio $dettaglio      dettaglio
     * @return ProductOrderDettaglio|null
     */
    public function saveOrderProductDettaglio($prod_ord_id, $dettaglio){
        $prod_bottone = new ProductOrderDettaglio();

        $prod_bottone->order_product_id = $prod_ord_id;
        $prod_bottone->barcode = $dettaglio->barcode;
        $prod_bottone->prezzo = $dettaglio->prezzo;
        $prod_bottone->tipo = $dettaglio->tipo;
        $prod_bottone_id = $prod_bottone->save();

        return ($prod_bottone_id >= 0) ? $prod_bottone_id : null;
    }
    /**
     * Effettua il rollback dei model inseriti
     * @param  string $class  Classe dalla quale sono composti i model
     * @param  array $models array contenente i model da fare il rollback
     */
    private function rollback($class, $models){
        if(!empty($models)){
            foreach ($models as $m){
                $class::delete($m->{$m->getPK()});
            }
        }
    }
    /**
     * Conferma l'ordine (prima firma del cliente)
     * @param  int $order_id
     * @return bool      true = update effettuato
     */
    public function confirmOrder($order_id){
        $order = Order::find($order_id);
        if(!$order)
            return false;
        return $this->changeOrderStatus($order, Order::PENDING_STATE);
    }
    /**
     * Approva l'ordine (firma del cliente)
     * @param  int $order_id
     * @return bool      true = update effettuato
     */
    public function approveOrder($order_id){
        $order = Order::find($order_id);
        if(!$order)
            return false;
        return $this->changeOrderStatus($order, Order::APPROVED_STATE);
    }
    /**
     * Completa l'ordine. Tutti i prodotti cuciti sono ritornati al magazzino
     * @param  int $order_id
     * @return bool      true = update effettuato
     */
    public function setOrderReadyForShipment($order_id){
        $order = Order::find($order_id);
        if(!$order)
            return false;
        return $this->changeOrderStatus($order, Order::DHL_READY_TO_SHIP_STATE);
    }
    /**
     * Imposta l'ordine come spedito
     * @param  int $order_id
     * @return bool      true = update effettuato
     */
    public function setOrderSent($order_id){
        $order = Order::find($order_id);
        if(!$order)
            return false;
        return $this->changeOrderStatus($order, Order::DHL_SENT_STATE);
    }
    /**
     * Elimina l'ordine (e i prodotti al suo interno)
     * @param  int $order_id
     * @param  int $permanent  0 Cancellazione logia, 1 Cancellazione fisica
     * @return bool      true = delete effettuato
     */
    public function deleteOrder($order_id, $permanent = 0){
        $status = false;
        if($permanent == 1){
            $res = Order::delete($order_id);
            $prod_orders = ProductOrder::where("order_id", $order_id);
            foreach ($prod_orders as $product){
                ProductOrder::delete($product->id);
            }
            $status = ($res != -1);
        }else if($permanent == 0){
            $order = Order::find($order_id);
            if(!$order)
                return false;
            $status = $this->changeOrderStatus($order, Order::CANCELLED_STATE);
        }
        return $status;
    }
    /**
     * Cambia lo stato dell'ordine
     * @param Order $order
     * @param int $stato nuovo stato
     * @return bool
     */
    private function changeOrderStatus($order, $stato){
        $order->stato = $stato;
        $order_id = $order->save();
        return ($order_id >= 0);
    }
    /**
     * Elimina il prodotto al'interno dell'ordine
     * @param  int $order_product_id
     * @return bool      true = delete effettuato
     */
    public function deleteOrderProduct($order_product_id){
        return ProductOrder::delete($order_product_id);
    }
    /**
     * Recupera gli ordini legati alla sessione corrente
     * @param  string $stato stato ordine da filtrare
     * @return Order[]  array ordini che rispettano i filtri. Se non è settata la sessione ritorna un array vuoto
     */
    public function getOrderBySession($stato = ""){
        if(!session_id()) // No session is set
            return array();
        $args = array("session = '".session_id()."'");
        $params = array();
        if($stato !== ""){
            $args[] = "stato = :stato";
            $params[":stato"] = $stato;
        }
        $orders = Order::whereRaw(implode(" AND ", $args), $params);

        return $orders;
    }
    /**
     * Recupera tutti gli ordini
     * @param  string $stato stato ordine da filtrare
     * @return Order[]
     */
    public function getOrders($stato = ""){
        $orders = ($stato !== "")
            ? Order::where("stato", $stato)
            : Order::all();
        return $orders;
    }
}
?>
