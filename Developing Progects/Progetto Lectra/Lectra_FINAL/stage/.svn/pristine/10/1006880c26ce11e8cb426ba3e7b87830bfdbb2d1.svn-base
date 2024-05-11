<?php
require_once ("/var/www/html/lectra/stage/include/connector.php");

abstract class Model {
    const DB_ENV_PATH = "/var/www/html/lectra/stage/";
    const DATE_FORMAT = "Y-m-d H:i:s";
    /** @static MysqlConnector connessione db */
    protected static $db;
    /** @var string $table Nome tabella dove risiede il model */
    protected $table;
    /** @var string $key Nome campo chiave primaria PK (per ora gestisce solo una chiave singola) */
    protected $key = "id";
    /** @var array $fields Array contenente i campi della tabella $table. NON INSERIRE il campo PK all'interno */
    protected $fields;
    /** Crea i campi come properties del model (public) a seconda del array $fields e del campo $key */
    public function __construct(){
        $config = new Configurator(self::DB_ENV_PATH);
        self::$db = new MysqlConnector($config); // ATTENZIONE. Forte dipendenza dal db!
        // Tutti i $fields e $key vengono inizializzati a null
        $this->{$this->key} = null;
        foreach ($this->fields as $f) {
            $this->{$f} = null;
        }
    }
    /**
     * Recupera il model dal db
     * @param  mixed $key
     * @return Model|null      Model. null if not found
     */
    public static function find($key){
        $model = new static;
        $pk = $model->getPK();

        $query = "SELECT * FROM ".$model->getTable()." WHERE $pk = :value";
        $res = self::$db->query($query, array(":value" => $key));
        if(!$res ||  !isset($res[0])){   // Error or not found
            return null;
        }
        $model = self::getModelFromResult($res[0]);
        $model->{$pk} = $key;

        return $model;
    }
    /**
     * Recupera tutti i model all'interno della tabella
     * @return Model[]       array di models
     */
    public static function all(){
        $models = array();
        $model = new static;
        $query = "SELECT * FROM ".$model->getTable();
        $res = self::$db->query($query);
        if($res && !empty($res)){
            foreach ($res as $row){
                $models[] = self::getModelFromResult($row);
            }
        }
        return $models;
    }
    /**
     * Recupera i model che rispettano le condizioni
     * @param  string $key   nome campo db condizione
     * @param  mixed $value  value campo
     * @param  string $eq    operatore per la condizione. = di defeault
     * @return Model[]       array di models
     */
    public static function where($key, $value, $eq = "="){
        return self::whereRaw("$key $eq :value", array(":value" => $value));
    }
    /**
     * [whereRaw description]
     * @param  string $where Stringa che indica la condizione where
     * @param  array $params Parametri sanificazione query
     * @return Model
     * @throws Exception Eccezione nel caso di query errata o problema nella query
     */
    public static function whereRaw($where, $params = array()){
        $models = array();
        $model = new static;
        $query = "SELECT * FROM ".$model->getTable()." WHERE $where";
        $res = self::$db->query($query, $params);
        if($res && is_array($res) && !empty($res)){
            foreach ($res as $row){
                $models[] = self::getModelFromResult($row);
            }
        }else if(is_string($res) && $res == OdbcStmt::PDO_ERROR_EXEC_MSG){
            $db_error = self::$db->getLastError();
            error_log($query);
            throw new Exception((isset($db_error[2])) ? $db_error[2] : "Errore generico");
        }
        return $models;
    }
    /**
     * Ritorna il model dal result di una query
     * @param  Object $elem row result
     * @return Model    Model rappresentante dei dati. Null nel caso non ci sia la chiave primaria
     */
    private static function getModelFromResult($elem){
        $model = new static;
        $pk = $model->getPK();
        if(!property_exists($elem, $pk)){ // Se non ha la chiave primaria ritorna null
            return null;
        }
        $model->{$pk} = $elem->{$pk};
        foreach ($model->getFields() as $f){
            if(property_exists($elem, $f)){
                $value = $elem->{$f};
                if(self::validateDate($elem->{$f}, self::DATE_FORMAT)){
                    $value = \DateTime::createFromFormat(self::DATE_FORMAT, $elem->{$f});
                }
                $model->{$f} = $value;
            }
        }
        return $model;
    }
    /**
     * Elimina il model dal db
     * @param  mixed $key
     * @return mixed $key in caso di successo. -1 in caso di errore.
     */
    public static function delete($key){
        $model = new static;
        $pk = $model->getPK();
        $query = "DELETE FROM ".$model->getTable()." WHERE $pk = :$pk";
        $res = self::$db->query($query, array(":$pk" => $key));
        return ($res != OdbcStmt::PDO_ERROR_EXEC_MSG) ? $key : -1;
    }
    /**
     * Controlla che la string sia una data valida
     * @param  string $date   stringa da controllare
     * @param  string $format formato della stringa da controllare
     * @return bool
     */
    private static function validateDate($date, $format){
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    /**
     * Salva il model. Viene gestita la sanificazione della query e gli escape dei caratteri speciali
     * @return int id model creato / aggiornato. -1 in caso di errore
     */
    public function save(): int{
        $params = array();
        if($this->exists()){
            // Update
            $sets = array();
            $params[":$this->key"] = $this->{$this->key};
            foreach ($this->fields as $f){
                $sets[] = "$f = :$f";
                $params[":$f"] = $this->getDbValue($this->{$f});
            }
            $query = "UPDATE $this->table SET ".implode(",", $sets)." WHERE $this->key = :$this->key";
            $res = self::$db->query($query, $params);
            $id = $this->{$this->key};
        }else{
            // Insert
            $values = array();
            foreach ($this->fields as $f){
                $values[] = ":$f";
                $params[":$f"] = $this->getDbValue($this->{$f});
            }
            $query = "INSERT INTO $this->table (".implode(",", $this->fields).") VALUES (".implode(",", $values).")";
            $res = self::$db->query($query, $params);
            $id = self::$db->lastInsertId();
        }
        if(is_array($res) || $res != OdbcStmt::PDO_ERROR_EXEC_MSG){
            $this->{$this->key} = $id;
            return $id;
        }

        return -1;
    }
    /**
     * Formatta il valore per il DB
     * @param  mixed $value
     * @return string        Valore formattato per essere inserito nel DB (MySQL)
     */
    private function getDbValue($value){
        if (is_null($value)){
            return null;
        }else if ($value instanceof \DateTime){
            return $value->format(self::DATE_FORMAT);
        }
        return $value;
    }
    /**
     * Restituisce true / false in base se il db ha il model già salvato o meno
     * @return bool [description]
     */
    protected function exists(): bool{
        if(is_null($this->{$this->key}))
            return false;
        $query = "SELECT $this->key FROM $this->table WHERE $this->key = :key";
        $res = self::$db->query($query, array(":key" => $this->{$this->key}));
        return ($res && !empty($res));
    }
    /**
     * @return string
     */
    public function getTable(){
        return $this->table;
    }
    /**
     * @return array
     */
    public function getFields(){
        return $this->fields;
    }
    /**
     * @return string
     */
    public function getPK(){
        return $this->key;
    }
    /**
     * @return array array with fields data
     */
    public function toArray(){
        $data = array();

        $data[$this->key] = $this->{$this->key};
        foreach ($this->fields as $f){
            $data[$f] = ($this->{$f} instanceof \DateTime) ? $this->{$f}->format(self::DATE_FORMAT) : $this->{$f};
        }

        return $data;
    }
    /**
     * Fuck you
     * @return string sbirulino
     */
    private function fuckyou(){
        return "uaaaaahhhhh";
    }
    /**
     * Free condoms
     * @return string oh yes!
     */
    protected function fuckyouwithcondoms(){
        return "OH YES PLS ON MY MOUNTH PLS";
    }
}
?>
