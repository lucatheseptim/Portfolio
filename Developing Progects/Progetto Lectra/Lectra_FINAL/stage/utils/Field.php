<?php


/****************
Field
 ***************/
class Field {

    public $curr_db;
    public $db_type;
    public $data_type;
    public $label;
    public $validation = array();
    public $sql;
    public $corr_field;
    public $curr_value;
    public $custom_props;


    protected $validation_allowed_values = array("max_length","min_length","not_empty","type","nut_null");
    protected $validation_allowed_types = array("int", "float", "char","numeric");


    public function __construct(
            $db,
            $db_type,
            $data_type=NULL,
            $label=NULL,
            $sql=NULL,
            $validation=NULL,
            $corr_field=NULL,
            $custom_props=NULL
        ){

        $this->curr_db= $db;
        $this->db_type= $db_type;
        $this->data_type = $data_type;
        $this->label = $label;
        $this->validation= $validation;
        $this->sql = $sql;
        $this->corr_field = $corr_field;
        $this->custom_props = $custom_props;
        if(!is_null($custom_props)){
            foreach ($custom_props as $key_prop => $val_prop){
                $this->{$key_prop} = $val_prop;
            }
        }
    }


    public function validate($curr_value=NULL){

        if($curr_value==NULL){
            $curr_value=$this->curr_value;
        }

        $end_res= array();
        $end_res["success"]=0;
        $end_res["error"]= "";

        Dbg::d("validate", $this->validation,1);
        Dbg::d("validate allowed values", $this->validation_allowed_values,1);

        $diff = array_diff(array_keys($this->validation),$this->validation_allowed_values);
        if(count($diff)>0){
            $end_res["error"] = "Campo: ".$this->label. ", Verificare i tipi di validazione";
            return $end_res;
        }
        if(!in_array($this->data_type, $this->validation_allowed_types)){
            $end_res["error"] = "Campo: ".$this->label. ", Verificare il tipo";
            return $end_res;
        }

        if(is_null($curr_value)){
            $end_res["error"] = "Campo: ".$this->label. ", Inserire valore";
        }

        foreach ($this->validation as $key=>$val){
            switch ($key) {

                case "max_length":
                    if(strlen($curr_value)>$val){
                        $end_res["error"] =  "Campo: ".$this->label. ", Lunghezza massima superata";
                    }
                    break;

                case "min_length":
                    if(strlen($curr_value)<$val){
                        $end_res["error"] =  "Campo: ".$this->label. ", Lunghezza minima non raggiunta";
                    }
                    break;

                case "not_empty":
                    if(strlen($curr_value)==0){
                        $end_res["error"] =  "Campo: ".$this->label. ", non può essere vuoto";
                    }
                    break;

                case "not_null":
                    if(strlen($curr_value)==0){
                        $end_res["error"] =  "Campo: ".$this->label. " nullo";
                    }
                    break;
                //type
                case "type":
                    switch ($this->data_type){
                        case "char":
                            if(!is_string($curr_value)){
                                $end_res["error"] = "Campo: ".$this->label. " errore tipo";
                            }
                            break;
                        case "numeric":
                            if(!is_numeric($curr_value)){
                                $end_res["error"] = "Campo: ".$this->label. " errore tipo";
                            }
                            break;
                        case "float":
                            if(!is_float($curr_value)){
                                $end_res["error"] = "Campo: ".$this->label. " errore tipo";
                            }
                            break;
                        case "int":
                            if(!is_int($curr_value)){
                                $end_res["error"] = "Campo: ".$this->label. " errore tipo";
                            }
                            break;
                    }
                    break;
                    //end type
                    break;
            }
        }



        if($end_res["error"]==""){
            $end_res["success"]=1;
        }

        Dbg::d("end_res_validation 1",$end_res,1);
        return $end_res;
    }



    public function get_data(){

        Dbg::d("getdataaaaa","-------------",1);

        $data = array();
        switch ($this->db_type){
            case "custom_values":
                $data = $this->custom_props["custom_values"];
                break;
            case "ind":
                Dbg::d("ind sql", $this->sql,1);
                $res= $this->curr_db->query($this->sql);
                if($res){
                    foreach ($res as $row){
                        $data[$row->CODICE] = $row->DESCRIZIONE;
                    }
                }
                break;
            case "as400":
                Dbg::d("as400 sql", $this->sql,1);
                $res = $this->curr_db->query($this->sql);
                if($res){
                    foreach ($res as $row){
                        $data[$row->CODICE] = $row->DESCRIZIONE;
                    }
                }
                break;
        }


        return $data;

    }


}

?>