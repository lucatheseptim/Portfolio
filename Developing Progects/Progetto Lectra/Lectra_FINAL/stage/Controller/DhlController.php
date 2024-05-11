<?php
require_once ("/var/www/html/lectra/stage/Models.php");

class DhlController{
    /** @const int MAX_QTY_CAMI max numero camicie dentro la scatola bianca dell'ordine*/
    const MAX_QTY_CAMI = 7;
    const MAX_WEIGHT_KG_CAMI_PACKAGE = 3;
    const TIMESTAMP_FORMAT = "Y-m-d\TH:i:s\G\M\TP";
    const TIMESTAMP_RITIRO_FORMAT = "Y-m-d\T12:00:01\G\M\TP";
    /** @var array $package_dimensions Dimensioni standard scatola bianca ( scatola degli ordini ). lenght x width x height */
    private $package_dimensions = array("Length" => 39, "Width" => 30, "Height" => 27);
    /** @var Order $order **/
    private $order;
    /**
     * @param int $order_id
     * @throws Exception Throw exception on order not found
     */
    public function __construct($order_id){
        $order = Order::find($order_id);
        if(!$order){
            return null;
        }
        $this->order = Order::find($order_id);
    }
    /**
     * Ritorna i dati del contatto nel formato per le API di DHL da un cliente
     * @param  Cliente $person  (Opzionale) Se null prende il cliente associato all'ordine
     * @return array empty in caso di errore
     */
    public function getContactDataFromCliente($person = null){
        $dhl_person = array();
        $dhl_address = array();
        $person = ($person) ? $person : Cliente::find($this->order->cliente_id);
        if(!$person){   // Se è ancora null vuol dire che la find non ha trovato il cliente.
            return array();
        }
        // Contact
        $dhl_person["PersonName"] = $person->Cognome . " " . $person->Nome;
        $dhl_person["CompanyName"] = "-";
        $dhl_person["PhoneNumber"] = $person->Telefono;
        $dhl_person["EmailAddress"] = $person->Email;
        // Address
        if($person->RitiroInNegozio != 1){
            $dhl_address["StreetLines"] = $person->Via;
            $dhl_address["City"] = $person->Localita;
            $dhl_address["PostalCode"] = $person->CAP;
        }else{
            $dhl_address["StreetLines"] = $person->IndirizzoNeg;
            $dhl_address["City"] = $person->LocalitaNeg;
            $dhl_address["PostalCode"] = $person->CAPNeg;
        }
        $dhl_address["CountryCode"] = "IT";

        return array("Contact" => $dhl_person, "Address" => $dhl_address);
    }
    /**
     * Crea i pacchi relativi all'ordine di DHL suddividendo i prodotti. Si ferma in caso di errore
     * @return bool true nel caso tutti i pacchi sono stati creati con successo. False altrimenti
     */
    public function createOrderPackages(){
        $status = true;
        $products = ProductOrder::where("order_id", $this->order->id);
        $n = sizeof($products);
        $seq_numb = 1;

        while($n > 0){
            $status = $this->createOrderPackage($seq_numb, ($n < self::MAX_QTY_CAMI) ? $n : self::MAX_QTY_CAMI);
            if(!$status)
                return $status;
            $n -= self::MAX_QTY_CAMI;
            $seq_numb++;
        }

        return $status;
    }
    /**
     * Crea il pacco per la spedizione di DHL
     * @param  string $sequence_number numero sequenzionale all'interno dell'ordine
     * @param  int    $n_prod          numero prodotti all'interno del pacco
     * @return bool     true = success, false = failure
     */
    private function createOrderPackage(string $sequence_number, int $n_prod){
        $package = new DhlPackageOrder();

        if($n_prod < 0 || $n_prod > self::MAX_QTY_CAMI){
            return false;
        }
        $package->order_id = $this->order->id;
        $package->reference = $this->order->id . "_" . $sequence_number;
        $package->n_products = $n_prod;
        $package->sequence_number = $sequence_number;
        $package_id = $package->save();

        return ($package_id >= 0);
    }
    /**
     * Ritorna i dati relativi ai pacchi per la spedizione di DHL
     * @param  double $weight (Opzionale). se = null inserisce il peso stimato di un ordine con sole camicie + scatola
     * @return array
     */
    public function getPackagesData(double $weight = null){
        $packages_data = array();
        $packages = DhlPackageOrder::where("order_id", $this->order->id);
        $weight = ($weight) ? $weight : self::MAX_WEIGHT_KG_CAMI_PACKAGE;
        foreach($packages as $pack){
            $packages_data[] = $this->prepareDhlPackageData($pack, $weight);
        }

        return array("RequestedPackages" => $packages_data);
    }
    /**
     * Prepara i dati relativi ad un pacco (scatola bianca)
     * @param  DhlPackageOrder $package         Dati pacco
     * @param  int|double          $weight          Peso complessivo (lordo) della scatola
     * @return array
     */
    public function prepareDhlPackageData(DhlPackageOrder $package, $weight){
        $package_data = array();

        $package_data["@number"] = $package->sequence_number; // deve essere > 0 per DHL
        $package_data["Weight"] = $weight;
        $package_data["CustomerReferences"] = $package->order_id . "_" . $package->sequence_number;
        $package_data["Dimensions"] = $this->package_dimensions;

        return $package_data;
    }
}
?>
