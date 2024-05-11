<?php


/****************
FieldManager
 ***************/
class FieldManager {

    protected $db;
    protected $as400;
    protected $fields;

    public function __construct($db, $as400, $fields ){

        $this->db = $db;
        $this->as400 = $as400;
        $this->fields = $fields;

    }

    public function get_data($curr_fields=NULL)
    {
        if(!$curr_fields){
            $curr_fields = $this->fields;
        }
        $data= array();
        foreach ($curr_fields as $field){
            $data[$field->label] = $field->get_data();
        }
        return $data;
    }


    public function validate($curr_fields, $curr_values=NULL){

        $end_res= array();
        $end_res["success"]=0;
        $end_res["error"]="Errore validazione";

        if(!$curr_fields){
            $curr_fields = $this->fields;
        }

        if(count($curr_fields)==count($curr_values)){
            $validation_errors=0;
            foreach ($curr_fields as $field){

                Dbg::d("curr fieldd",$field->label);

                $end_res_validation = $field->validate($curr_values[$field->label]);
                if($end_res_validation["success"]==0){
                    $end_res["error"] = $end_res_validation["error"];
                    $validation_errors=1;
                    break;
                }
            }
            if(!$validation_errors){

                $end_res["success"]=1;
                $end_res["error"] ="";
            }
        }else{
            $end_res["success"]=0;
            $end_res["error"]="Differenza tra numero campi e valori";
        }
        return $end_res;
    }

}


?>