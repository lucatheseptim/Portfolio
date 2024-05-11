<?php


/****************
Req
 ***************/
class Req{

    protected $request;
    protected $cleaned_data;
    protected $db;

    public function __construct($request,$db){

        $this->request= $request;
        $this->db=$db;
    }

    function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }

    function is_JSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }


  /*  function clean( ){

        $data= array();

        if(count($this->request)>0){
            foreach ($this->request as $key_item=>$val_item){
                $val_item=$this->object_to_array(json_decode($val_item));

                    $this->iterate_ar($val_item);

            }
        }
    }

  */




 function clean(){
        Dbg::d("cr init",$this->request,1);
        $data= array();
        if(count($this->request)>0){
            foreach ($this->request as $key_item=>$val_item){

                Dbg::d("valitem",$val_item);
                Dbg::d("isjson",$this->is_JSON($val_item));

                if($this->is_JSON($val_item)){
                    $val_item=$this->object_to_array(json_decode($val_item));
                    Dbg::d("valitem2",$val_item);
                }

                if(is_array($val_item)){

                    Dbg::d("valitem3",$val_item);
                    foreach ($val_item as $key_item_i=>$val_item_i){
                        if(is_array($val_item_i)){
                            Dbg::d("valitem_i",$val_item_i);
                            foreach ($val_item_i as $key_item_ii=>$val_item_ii){
                                if(is_array($val_item_ii)){
                                    foreach ($val_item_ii as $key_item_iii=>$val_item_iii){
                                        //$data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = $this->db->escape($val_item_iii);
                                        // $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = addslashes($val_item_iii);


                                        //loop 4
                                        if(is_array($val_item_iii)){
                                            foreach ($val_item_iii as $key_item_iiii=>$val_item_iiii){
                                                //$data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = $this->db->escape($val_item_iii);
                                                // $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii][$key_item_iiii] = addslashes($val_item_iiii);

                                                //loop 5
                                                if(is_array($val_item_iiii)){
                                                    foreach ($val_item_iiii as $key_item_iiiii=>$val_item_iiiii){
                                                        //$data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = $this->db->escape($val_item_iii);
                                                        // $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii][$key_item_iiii][$key_item_iiiii] = addslashes($val_item_iiiii);

                                                        //loop 6
                                                        if(is_array($val_item_iiiii)){
                                                            foreach ($val_item_iiiii as $key_item_iiiiii=>$val_item_iiiiii){
                                                                //$data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = $this->db->escape($val_item_iii);
                                                                $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii][$key_item_iiii][$key_item_iiiii][$key_item_iiiiii] = addslashes($val_item_iiiiii);
                                                                //loop 6
                                                            }
                                                        }else{
                                                            // $data[$key_item][$key_item_i][$key_item_ii] = $this->db->escape($val_item_ii);
                                                            $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii][$key_item_iiii][$key_item_iiiii] = addslashes($val_item_iiiii);
                                                        }

                                                    }
                                                }else{
                                                    // $data[$key_item][$key_item_i][$key_item_ii] = $this->db->escape($val_item_ii);
                                                    $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii][$key_item_iiii] = addslashes($val_item_iiii);
                                                }


                                            }
                                        }else{
                                            // $data[$key_item][$key_item_i][$key_item_ii] = $this->db->escape($val_item_ii);
                                            $data[$key_item][$key_item_i][$key_item_ii][$key_item_iii] = addslashes($val_item_iii);
                                        }




                                    }
                                }else{
                                   // $data[$key_item][$key_item_i][$key_item_ii] = $this->db->escape($val_item_ii);
                                    $data[$key_item][$key_item_i][$key_item_ii] = addslashes($val_item_ii);
                                }

                            }
                            Dbg::d("data3",$data);
                        }else{
                            //$data[$key_item][$key_item_i] = $this->db->escape($val_item_i);
                            $data[$key_item][$key_item_i] =addslashes($val_item_i);
                        }

                    }
                }else{
                    //$data[$key_item] = $this->db->escape($val_item);
                    $data[$key_item] = addslashes($val_item);
                }
            }
        }

        $this->cleaned_data = $data;
        Dbg::d("dataaaaaa",$data,1);
        return $data;
    }




    public function get_request_data( $init_string=NULL, $init_string_length=0, $in_ar=0){

        Dbg::d("request 1 ---",$this->cleaned_data,1);
        Dbg::d("params",func_get_args (),1);
        Dbg::d("request 2 ---",$this->cleaned_data,1);
        $input=$this->cleaned_data;
        Dbg::d("input",$input,1);
        $data=array();
        if(!$init_string){
            $data=$input;
        }else{
            foreach ($input as $key_item  => $val_item){
                Dbg::d("key_item",substr( $key_item, 0, $init_string_length ),1);
                if(substr( $key_item, 0, $init_string_length ) === $init_string){
                    Dbg::d("aaa",substr( $key_item, 0, $init_string_length),1);
                    $data[substr($key_item,$init_string_length)] = $val_item;
                }
            }
        }
        Dbg::d("dataaa",$data,1);
        return $data;
    }
}

class ReqMiddleware {
    /** @var array $required */
    protected $required;
    /**
     * Controlla che esistano i campi obbligatori nel campo $data
     * @param  array $data
     * @param  array $required Campi required
     * @return array    array("success" => 0 = errore / 1 = successo, "error" => messaggio errore );
     */
    public function check_required_data($data, $required = array()){
        $required = (empty($required)) ? $this->required : $required;
        foreach($required as $req_field => $val){
            if(!isset($data[$req_field])){
                return array("success" => 0, "error" => "Campo $req_field obbligatorio");
            }

            if(is_array($val)){
                foreach($data[$req_field] as $data_arr){
                    $res = $this->check_required_data($data_arr, $required[$req_field]);
                    if($res["success"] == 0){
                        return $res;
                    }
                }
            }
        }
        return array("success" => 1, "error" => "");
    }
}
?>
