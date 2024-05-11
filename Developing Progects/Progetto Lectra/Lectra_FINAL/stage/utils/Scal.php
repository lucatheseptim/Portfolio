<?php

/****************
Scal
 ***************/
class Scal{

    protected $as400;
    public $size_us_15 = array("14½","15","15½","16","16½","17","17½","18","18½","19","19½","20","20½");
    public $size_8 = array("9","9½","10","10½","11","11½","12","12½","13","13½","14","14½","15","15½","16","16½","17","17½","18","18½","19","19½","20","20½");
    public $az;
    public $lib;


    public function __construct($as400, $lib=NULL)
    {
        $this->as400=$as400;
        $this->lib=$lib;

        if($lib=="m"){
            $this->az = "AZ101";
        }else if($lib=="c"){
            $this->az = "AZ116";
        }else if($lib=="n"){
            $this->az = "AZ401";
        }

    }

    public function get_sizes_by_scalarino($cod_scalarino){

        $end_res["success"]=1;
        $end_res["error"]=array();
        $end_res["data"]=array();

        $cod_scalarino= str_pad($cod_scalarino,2,0,  STR_PAD_LEFT);

        $sql="SELECT
              TRIM(SUBSTRING(KEYFTA, 4, 3)) AS TIPO,
              TRIM(SUBSTRING(FILTAB, 3, SUBSTRING(FILTAB, 1, 2)* 3)) AS TAGLIE
              FROM
              ".$this->az.".FTABPF
              WHERE
              KEYFTA LIKE 'X%' 
              AND TRIM(SUBSTRING(KEYFTA, 4, 3)) = '".$cod_scalarino."'";

        $res= $this->as400->query($sql);
        if($res){
            switch ($cod_scalarino) {
                case 15:
                    $sizes=$this->size_us_15;
                    break;
                case 8:
                    $sizes=$this->size_8;
                    break;
                default:
                    $sizes= str_split($res[0]->TAGLIE,3);
                    $sizes = array_map('trim', $sizes);
                    break;
            }
            $end_res["data"] = $sizes;
        }

        return $end_res;

    }
}


?>