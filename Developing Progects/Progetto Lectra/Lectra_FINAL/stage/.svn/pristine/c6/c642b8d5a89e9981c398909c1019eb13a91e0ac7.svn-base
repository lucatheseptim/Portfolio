<?php

Class Campaign{

    public $curr_db;

    public function __construct($db){
        $this->db = $db;
    }

    public function check(){

        $end_res=array();
        $end_res["success"]=0;
        $end_res["error"]="Errore numero active campaign";

        $sql= "SELECT count(*) as count
                FROM  schede_prodotto.sales_campaign_campagne
                WHERE active=1";
        $res= $this->db->get_results($sql);

        if($res[0]->count==1){
            $end_res["success"]=1;
            $end_res["error"]="";
        }
        return $end_res;

    }

    public function get_active(){

        $active_campaign=NULL;
        $sql="SELECT id,label
              FROM  schede_prodotto.sales_campaign_campagne 
              WHERE active=1";
        $this->db->get_results($sql);
        $res=$this->db;
        if($res->num_rows==1){
            $active_campaign= array();
            $active_campaign["id"]=$res->last_result[0]->id;
            $active_campaign["label"]=$res->last_result[0]->label;
        }
        return $active_campaign;

    }


}

?>