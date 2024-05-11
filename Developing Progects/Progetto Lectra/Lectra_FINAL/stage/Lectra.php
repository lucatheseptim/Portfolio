<?php
require_once ("Controller/OrderController.php");
include_once ("Models.php");
include_once ("Requests.php");
include_once ("Services/Services.php");
include_once ("Services/BollaLavorazioneService.php");
require_once ("JsonReader.php");
require_once ("Controller/DhlController.php");


class ConfirmInvioMail extends InvioMail
{

    public function __construct($subject)
    {
        parent::__construct($subject);
    }

    public function send()
    {
        $this->mail->IsHTML(true);
        if (!$this->mail->Send()) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return 0;
        } else {
            error_log("Message " . $this->mail->Subject . " sent!");
            return 1;
        }
    }
}


class EndRes {

    public $success;
    public $error;
    public $data;

    public function __construct($success=0, $error="", $data=array()) {

        $end_res = array();
        $this->success=$success;
        $this->error=$error;
        $this->data=$data;
        return $end_res;

    }
}


class Logistica{

    protected $db;
    protected $as400;

    protected $req;
    protected $app_lectra;

    function __construct($db,$as400,$req,$app_lectra){
        $this->db= $db;
        $this->as400= $as400;
        $this->req=$req;
        $this->app_lectra = $app_lectra;
    }



    public function multidim_array_sum_array($ar1, $ar2){
        $end_res = array();
        $keys= array_keys($ar1);
        foreach($keys as $key) {

            if(!isset($ar1[$key]) || !isset($ar2[$key])){
                throw new Exception('Array key not exist');
            }

            $end_res[$key] = $ar1[$key]+$ar2[$key];
        }
        return $end_res;
    }



    public function calc_prezzo_pezzi($barcodes){



        $end_res = (array)new EndRes(0, 'No prezzo');
        $barcode_prezzi = array();
        $sql="SELECT
                TRIM(ALTMTB) AS BARCODE,
                COALESCE(VEN.LD0ML2, 0) AS PREZZO
                FROM AZGRPCAMI.MTBCPF
                LEFT OUTER JOIN AZGRPCAMI.MMA1PF ON (PARMTB = NUMMM1)
                LEFT OUTER JOIN AZGRPCAMI.MLI2PF AS VEN ON NUMMM1 = VEN.PARML2
                WHERE VEN.TREML2=''
                AND VEN.CODML2='01'
                AND VEN.CDIML2='EURO'
                AND VEN.TCLML2=''
                AND VEN.CLIML2=0
                AND VEN.DINML2=0
                AND ALTMTB IN ('".implode("','",$barcodes)."')";


        $res = $this->as400->query($sql);
        if($res){
            $end_res["success"]=1;
            $end_res["error"]="";

            foreach ($res as $row){
              $barcode_prezzi[$row->BARCODE] = $row->PREZZO;
            }


            $end_res["data"]= $barcode_prezzi;



        }


        return $end_res;

    }




    public function get_movimenti_by_ordini($ordini_id=NULL){

        $end_res = (array)new EndRes(0,"No data");

        if(!isset($ordini_id)){
            $params = $req_data = $this->req->get_request_data("val_",4,1);
            $ordini_id =  explode(",",$params["ordini_id"]);
            Dbg::d("params",$params,1);
        }





        Dbg::d("ordini_id",$ordini_id,1);



        $sql="";
        for($i=1;$i<=1;$i++){

            $sql.= "SELECT id, label, descrizione, barcode,  pezzi_ordini  FROM(
                        (SELECT SUM(p_".$i.") AS pezzi_ordini FROM movimenti WHERE id_ordine IN(".implode(",",$ordini_id).") ) b,
                        (SELECT  id, label, descrizione, barcode,  min_pezzi FROM pezzi WHERE id=".$i.") c
                    ) UNION ";

        }

        $sql.="SELECT
                    t_collo_id as id,
                    tele_collo.label as label,
                    tele_collo.descrizione as descrizione,
                    tele_collo.barcode as barcode,
                    (SELECT sum(t_collo_qty) FROM movimenti as mov_ord WHERE mov_ord.t_collo_id = mov_all.t_collo_id AND id_ordine IN(".implode(",",$ordini_id).") ) pezzi_ordini
                    FROM movimenti as mov_all, tele AS tele_collo WHERE t_collo_id = tele_collo.id GROUP BY t_collo_id UNION ";


        $sql.="SELECT
                    t_polso_id as id,
                    tele_polso.label as label,
                    tele_polso.descrizione as descrizione,
                    tele_polso.barcode as barcode,
                    (SELECT sum(t_polso_qty) FROM movimenti as mov_ord WHERE mov_ord.t_polso_id = mov_all.t_polso_id AND id_ordine IN(".implode(",",$ordini_id).") ) pezzi_ordini
                    FROM movimenti as mov_all, tele AS tele_polso WHERE t_polso_id = tele_polso.id GROUP BY t_polso_id;";



        Dbg::d("sql_movimenti",$sql,1);

        $data=array();
        $res= $this->db->query($sql);

        $barcodes= array();

        foreach ($res as $row){
            $data[]= array(
                        "id"=>$row->id,
                        "label"=>$row->label,
                         "barcode"=>$row->barcode,
                         "descrizione"=>$row->descrizione,
                         "pezzi_ordini"=>$row->pezzi_ordini,
                        );
            $barcodes[]=$row->barcode;
        }


        $barcode_prezzi = $this->calc_prezzo_pezzi($barcodes);
        $barcode_prezzi= $barcode_prezzi["data"];

        foreach ($data as $key_data=>$val_data){

            $data[$key_data]["prezzo"]= $barcode_prezzi[$val_data["barcode"]];
        }




        if(count($data)){

            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]=$data;

        }


        return $end_res;


    }



    public function save_movimenti_by_ordine(){

        Dbg::d("get movimenti by ordine", "-------------------",1);


        $params =  $req_data = $this->req->get_request_data("val_",4,1);
        $dati_ordine=  $this->app_lectra->get_dati_ordine($params["ordine_id"]);


        Dbg::d("dati_ordine", $dati_ordine,1);


        $pezzi_order_product_tot= array();

        $sql="INSERT INTO movimenti (id_ordine, id_order_product, p_1,p_2,p_3,p_4,p_5,p_6,p_7,p_8,p_9,p_10,p_11,p_12,p_13,p_14, p_15, t_collo_id, t_collo_qty, t_polso_id, t_polso_qty) VALUES";

        foreach ($dati_ordine["order_product"] as   $order_product){


            Dbg::d("order product","------------------------------",1);


            $pezzi_order_product =  $this->get_pezzi_by_order_product($order_product);
            $tele_order_product =  $this->get_tele_by_order_product($order_product);
            $tele_order_product = $tele_order_product["data"];



            Dbg::d("tele order product",$tele_order_product,1);

            $collo_id= isset($tele_order_product["collo_id"])?$tele_order_product["collo_id"]:NULL;
            $polso_id= isset($tele_order_product["polso_id"])?$tele_order_product["polso_id"]:NULL;

            Dbg::d("collo id", $collo_id,1);
            Dbg::d("polso_id", $polso_id,1);


            $sql.= "(

                      ".$params["ordine_id"].",
                      ".$order_product["id"].",
                      ".$pezzi_order_product[1].",
                      ".$pezzi_order_product[2].",
                      ".$pezzi_order_product[3].",
                      ".$pezzi_order_product[4].",
                      ".$pezzi_order_product[5].",
                      ".$pezzi_order_product[6].",
                      ".$pezzi_order_product[7].",
                      ".$pezzi_order_product[8].",
                      ".$pezzi_order_product[9].",
                      ".$pezzi_order_product[10].",
                      ".$pezzi_order_product[11].",
                      ".$pezzi_order_product[12].",
                      ".$pezzi_order_product[13].",
                      ".$pezzi_order_product[14].",
                      ".$pezzi_order_product[15].",
                      ".$collo_id.",
                      ".$tele_order_product["collo_qty"].",
                      ".$polso_id.",
                      ".$tele_order_product["polso_qty"]."
                      ),";


        }



        Dbg::d("pezzi_order_product_tot", $pezzi_order_product_tot,1);


        $sql= substr($sql,0,-1);
        $sql.=";";

        Dbg::d("sql",$sql,1);


        $this->db->query($sql);


    }



    function  get_tele_by_order_product ($order_product){


        $end_res= (array) new EndRes(0,"Errore tele");

        $args= array();
        if(isset($order_product["components"][1]['id'])){
            $args[] =  "davanti='".$order_product["components"][1]['id']."'";
        }

        if(isset($order_product["components"][2]['id'])){
            $args[] =  "dietro='".$order_product["components"][2]['id']."'";
        }

        if(isset($order_product["components"][3]['id'])){
            $args[] =  "manica_polsini='".$order_product["components"][3]['id']."'";
        }

        if(isset($order_product["components"][4]['id'])){
            $args[] =  "tasche='".$order_product["components"][4]['id']."'";
        }

        if(isset($order_product["components"][5]['id'])){
            $args[] =  "collo='".$order_product["components"][5]['id']."'";
        }

        if(isset($order_product["grading"])){
            $args[] =  "materiale='".$order_product["grading"]."'";
        }


        //tela collo
        $tela_collo_id=NULL;
        $tela_collo_qty=0;

        $tela_polso_id=NULL;
        $tela_polso_qty=0;

        $sql= "SELECT id_tela, qty
                    FROM tele_regole
                    WHERE 1=1 AND tipo='C'  AND ".implode(" AND ",$args).";";

        Dbg::d("sql tela collo",$sql,1);

        $res= $this->db->query($sql);

        if($res){


            $end_res["success"]=1;
            $end_res["error"]="";

            $tela_collo_id= $res[0]->id_tela;
            $tela_collo_qty= $res[0]->qty;

        }


        //tela polso
        if(isset($tela_collo_id)){
            $args[] =  "tela_collo='".$tela_collo_id."'";
        }

        $sql= "SELECT id_tela, qty
                    FROM tele_regole
                    WHERE 1=1 AND tipo='P' AND ".implode(" AND ",$args).";";

        $res= $this->db->query($sql);

        if($res){

            $tela_polso_id= $res[0]->id_tela;
            $tela_polso_qty= $res[0]->qty;
            $end_res["data"]["collo_id"] = $tela_collo_id;
            $end_res["data"]["collo_qty"] = $tela_collo_qty;
            $end_res["data"]["polso_id"] = $tela_polso_id;
            $end_res["data"]["polso_qty"] = $tela_polso_qty;

        }


        Dbg::d("sql tela polso",$sql,1);


        return $end_res;



    }


    function get_pezzi_by_order_product($order_product){


        $pezzi_order_product=array();
        $pezzi_order_product[1] = 1;
        $pezzi_order_product[2] = 1;
        $pezzi_order_product[3] = 1;
        $pezzi_order_product[4] = 1;
        $pezzi_order_product[5] = 1;
        $pezzi_order_product[6] = 1;
        $pezzi_order_product[7] = 1;
        $pezzi_order_product[8] = 1;
        $pezzi_order_product[9] = 1;
        $pezzi_order_product[10] = 1;
        $pezzi_order_product[11] = 1;
        $pezzi_order_product[12] = 1;
        $pezzi_order_product[13] = 1;
        $pezzi_order_product[14] = 1;
        $pezzi_order_product[15] = 1;



        $args= array();
        if(isset($order_product["components"][1]['id'])){
            $args[] =  "davanti='".$order_product["components"][1]['id']."'";
        }

        if(isset($order_product["components"][2]['id'])){
            $args[] =  "dietro='".$order_product["components"][2]['id']."'";
        }

        if(isset($order_product["components"][3]['id'])){
            $args[] =  "manica_polsini='".$order_product["components"][3]['id']."'";
        }

        if(isset($order_product["components"][4]['id'])){
            $args[] =  "tasche='".$order_product["components"][4]['id']."'";
        }

        if(isset($order_product["components"][5]['id'])){
            $args[] =  "collo='".$order_product["components"][5]['id']."'";
        }

        if(isset($order_product["grading"])){
            $args[] =  "materiale='".$order_product["grading"]."'";
        }


        $sql= "SELECT id_pezzo, qty
                    FROM pezzi_regole
                    WHERE 1=1 AND  ".implode(" AND ",$args).";";

        Dbg::d("sql_pezzi",$sql,1);


        $res = $this->db->query($sql);

        if($res){
            foreach ($res as $row){
                $pezzi_order_product[$row->id_pezzo]= $row->qty;
            }
        }




        Dbg::d("pezzi_prodotto", $pezzi_order_product,1);
        return $pezzi_order_product;


    }




    function add_movimentazione_from_as400(){


                $yesterday = new DateTime();
                $yesterday->sub(new DateInterval('P1D'));


                $end_res = (array)new EndRes(0,'Errore add movimentazione as400');

                Dbg::d("add_movimentazione_from_as400","----------",1);


                //mag via mecenate 034, NMAOR

                $sql="SELECT
                            TRIM(ALTMTB) AS BARCODE,
                            NUMOR1,
                            DATOR1,
                            TRIM(ARFMM1) || '' || TRIM(MOFMM1) || '' || TRIM(COFMM1) AS COD_ART,
                            EVAOR2,
                            ALTMTB,
                            CASE
                              WHEN( TRIM(CHAR(AZGRPCAMI.MOR2PF.EVAOR2))='1') THEN 1
                              WHEN( TRIM(CHAR(AZGRPCAMI.MOR2PF.EVAOR2))='2') THEN 1
                              WHEN( TRIM(CHAR(AZGRPCAMI.MOR2PF.EVAOR2))='0') THEN 1
                              WHEN( TRIM(CHAR(AZGRPCAMI.MOR2PF.EVAOR2))='') THEN 1
                            ELSE 0 END AS CONFIRMED,
                            Q01OR2 + Q02OR2 + Q03OR2 + Q04OR2 + Q05OR2 + Q06OR2 + Q07OR2 + Q08OR2 + Q09OR2 + Q10OR2 + Q11OR2 + Q12OR2 + Q13OR2 + Q14OR2 + Q15OR2 + Q16OR2 + Q17OR2 + Q18OR2 + Q19OR2 + Q20OR2 + Q21OR2 + Q22OR2 + Q23OR2 + Q24OR2 AS QTOT

                    FROM    AZGRPCAMI.MOR1PF,AZGRPCAMI.MOR2PF, AZGRPCAMI.CANAPF,AZ100.FTABPF,AZGRPCAMI.MMA1PF, AZGRPCAMI.MTBCPF
                    WHERE
                            NUMMM1 = PARMTB AND
                            CODCAN = CLFOR1 and
                            TOROR1 = TOROR2 and
                            CLFOR1 = CLFOR2 and
                            NUMOR1 = NUMOR2 and
                            NUMMM1 = PAROR2 AND
                            TTAMM1=ITAMTB AND
                            TRIM(SUBSTRING(KEYFTA, 4)) = TRIM(CL1MM1) and
                            KEYFTA LIKE 'E1%'
                            AND FLAOR1 = '1'
                        AND NMAOR1 IN(1,
                        2)
                        AND CTROR1 = 201
                        AND QTOOR1>0
                        AND TOROR1 = 'C'
                        AND TIPCAN IN('IT')
                        AND NPRMTB = 1
                        AND NUMOR1 NOT IN (
                            SELECT NORWO1
                        FROM
                            AZGRPCAMI.MWO1PF
                        WHERE
                            CLFWO1 = CLFOR1
                            AND TWOWO1 = 'C')
                        AND DATOR1 = 20201102
                        LIMIT 10";



                 //"AND DATOR1 = ".$yesterday->format("Ymd").";


                Dbg::d("sql as 400", $sql,1 );

                $articoli = array();
                $res = $this->as400->query($sql);

                if($res){

                foreach ($res as $row){

                    $articoli[$row->NUMOR1][$row->BARCODE] = array(
                                                    "cod_art"=>$row->COD_ART,
                                                    "qty"=>$row->QTOT
                                                );

                }

                Dbg::d("articoli 1", $articoli,1 );


                foreach ($articoli as $key_ord=>$val_ord) {


                    $sql = "SELECT id, barcode FROM pezzi WHERE barcode IN('".implode("','", array_keys($val_ord))."')";
                    $res= $this->db->query($sql);


                    foreach ($res as $row){
                         Dbg::d("keyord", $key_ord,1);
                         Dbg::d("rowww", $row,1);
                         $articoli[$key_ord][$row->barcode]["id_pezzo"] = $row->id;
                         $articoli[$key_ord][$row->barcode]["tipo"] = "PEZZO";
                     }



                    $sql = "SELECT id, tipo, barcode FROM tele WHERE barcode IN('".implode("','", array_keys($val_ord))."')";
                    $res= $this->db->query($sql);


                    foreach ($res as $row){
                        Dbg::d("keyord", $key_ord,1);
                        Dbg::d("rowww", $row,1);
                        $articoli[$key_ord][$row->barcode]["id_tela"] = $row->id;
                        $articoli[$key_ord][$row->barcode]["tipo"] = $row->tipo=="C"?"TELA_COLLO":"TELA_POLSO";
                    }


                    Dbg::d("articoli",$articoli,1);


                    $end_res_check_pezzi_ordini_as400= $this->check_pezzi_ordini_as400($articoli[$key_ord]);


                    Dbg::d("end_res_check_pezzi_ordini_as400", $end_res_check_pezzi_ordini_as400,1);


                    if($end_res_check_pezzi_ordini_as400["success"]==1){



                        $sql_all_insert="";

                        //pezzi
                        $sql_insert = "INSERT INTO movimenti (" ;
                        $sql_insert.="ordine_as400, ";

                        foreach ($articoli[$key_ord] as $key_art=>$val_art){

                            switch($val_art["tipo"]){
                                case "PEZZO":
                                  $sql_insert.="p_".$val_art["id_pezzo"].", ";
                                  break;

                            }

                        }

                        $sql_insert = substr($sql_insert,0, -2);
                        $sql_insert.=")  VALUES";

                        $sql_insert.="(";
                        $sql_insert.= $key_ord.", ";
                        foreach ($articoli[$key_ord] as $key_art=>$val_art){

                            switch($val_art["tipo"]){
                                case "PEZZO":
                                    $sql_insert.= $val_art["qty"].", ";
                                    break;
                            }

                        }

                        $sql_insert = substr($sql_insert,0, -2);
                        $sql_insert.=")";


                        $sql_all_insert.=$sql_insert.";";


                        Dbg::d("sql insert indagini", $sql_insert,1 );


                        foreach ($articoli[$key_ord] as $key_art=>$val_art){

                            switch($val_art["tipo"]){
                                case "TIPO_COLLI":
                                    $sql_all_insert.= "INSERT INTO movimenti (collo_id, collo_qty) VALUES (".$val_art["id_tela"].",".$val_art["qty"]."); ";
                                    break;
                                case "TIPO_POLSO":
                                    $sql_all_insert.= "INSERT INTO movimenti (collo_id, collo_qty) VALUES (".$val_art["id_tela"].",".$val_art["qty"]."); ";
                                    break;
                            }

                        }

                        $res= $this->db->query($sql_insert);
                        if($res=="none"){

                            $end_res["success"]=1;
                            $end_res["error"]="";
                        }

                    } else{

                        $end_res = $end_res_check_pezzi_ordini_as400;
                    }


                    if($end_res["success"]==1){
                        $subject = "Su misura, carico fasonista ordine ".$key_ord." OK";
                        $mail_body=$end_res["error"];

                    }else{
                        $subject = "Su misura, carico fasonista ordine ".$key_ord." KO";
                        $mail_body=$end_res["error"];
                    }


                    Dbg::d("end ord 1","----",1);

                    $this->app_lectra->sendMail($subject, $mail_body,  $this->app_lectra->email["logistica"]);
                    sleep(2);


                    Dbg::d("end ord 2","----",1);






                }









                    Dbg::d("articoli 2", $articoli,1 );




                    /*
                                    foreach ($articoli as $key_ord=>$val_ord){

                                        Dbg::d("val ord--", $val_ord,1);





                                        Dbg::d("articoli", $articoli,1);









                                        $sql_insert = "INSERT INTO pezzi_movimenti (" ;

                                        foreach ($val_ord as $key_art=>$val_art){
                                            $sql_insert.="p_".$val_art["id_pezzo"].", ";
                                        }

                                        $sql_insert.="qty)  VALUES";


                                        $sql_insert.="(";
                                        foreach ($val_ord as $key_art=>$val_art){
                                            $sql_insert.="q_".$val_art["qty"].", ";
                                        }

                                        $sql_insert.=")";


                                        Dbg::d("sql insert indagini", $sql_insert,1 );
                                        $end_res_check_pezzi_ordini_as400= $this->check_pezzi_ordini_as400();


                                        if($end_res_check_pezzi_ordini_as400["success"]==1){

                                            $res= $this->db->query($sql_insert);
                                            if($res=="none"){

                                                $end_res["success"]=1;
                                                $end_res["error"]="";

                                            }
                                        } else{

                                            $end_res = $end_res_check_pezzi_ordini_as400;
                                        }



                                        if($end_res["success"]==1){

                                            $subject = "Su misura, carico fasonista ordine ".$row->NUMOR1." OK";
                                            $mail_body=$end_res["error"];





                                        }else{
                                            $subject = "Su misura, carico fasonista ordine ".$row->NUMOR1." KO";
                                            $mail_body=$end_res["error"];
                                        }


                                        $this->app_lectra->sendMail($subject, $mail_body,  $this->app_lectra->email["logistica"]);
                                        sleep(2);



                                    }


                               }


                    */




            }



        return $end_res;
    }



    function check_pezzi_ordini_as400($articoli){


        $end_res = (array)new EndRes(1,'');
        foreach ($articoli as $key_art => $val_art){
                if(!isset($val_art["id_pezzo"]) || isset($val_art["id_tela"])){
                     $end_res["success"]=0;
                     $end_res["error"]="Pezzi as400 non corrispondenti";
                     break;
                }


        }

        return $end_res;
    }




    function add_movimenti(){




        $end_res = (array)new EndRes(0,'errore check pezzi');
        $mov=array();
        $mov[] = array("id"=>1,"qty"=>3);
        $mov[] = array("id"=>2,"qty"=>4);
        $sql = "INSERT INTO pezzi_movimenti (pezzo_id, qty) VALUES";

        foreach ($mov as $mov){

            $sql.="(".$mov["id"].",".$mov["qty"]."), ";
        }

        $sql =  substr($sql, 0, -2);
        $sql.=";";
        Dbg::d("sql",$sql,1);
        $res= $this->db->query($sql);

        if($res){
            $end_res["success"]=1;
            $end_res["error"]="";
        }
        return "";
    }



    public function get_pezzi_tele(){


       $sql="SELECT label, descrizione, barcode, min_pezzi FROM lectra_stage.pezzi
              UNION
             SELECT label, descrizione, barcode, min_pezzi FROM lectra_stage.tele";

    }




    function check_pezzi(){



        Dbg::d("checkpezzi","--------",1);

        $end_res = (array)new EndRes(0,'errore check pezzi');
        $params =  $req_data = $this->req->get_request_data("val_",4,1);

        $sql="";



        for($i=1;$i<16;$i++){

            $sql.= "SELECT id, label, descrizione, min_pezzi, pezzi_mag, pezzi_ordini, pezzi_mag-pezzi_ordini as pezzi_tot  FROM(
                        (SELECT SUM(p_".$i.") AS pezzi_mag FROM movimenti WHERE id_ordine IS NULL) a,
                        (SELECT SUM(p_".$i.") AS pezzi_ordini FROM movimenti WHERE id_ordine IS NOT NULL) b,
                        (SELECT  id, label, descrizione, min_pezzi FROM pezzi WHERE id=".$i.") c
                    ) WHERE pezzi_mag-pezzi_ordini<=min_pezzi UNION ";

        }


        $sql.="SELECT id, label, descrizione, min_pezzi, pezzi_mag, pezzi_ordini, pezzi_mag-pezzi_ordini as pezzi_tot FROM(
                SELECT
                    t_collo_id as id,
                    tele_collo.label as label,
                    tele_collo.descrizione as descrizione,
                    tele_collo.min_pezzi,
                    (SELECT sum(t_collo_qty) FROM movimenti as mov_mag WHERE mov_mag.t_collo_id = mov_all.t_collo_id AND id_ordine IS NULL)  pezzi_mag,
                    (SELECT sum(t_collo_qty) FROM movimenti as mov_ord  WHERE mov_ord.t_collo_id = mov_all.t_collo_id AND id_ordine IS NOT NULL ) pezzi_ordini
                FROM
                    movimenti as mov_all,
                    tele AS tele_collo
                WHERE
                    t_collo_id = tele_collo.id
                GROUP BY t_collo_id
                ) t_colli WHERE pezzi_mag-pezzi_ordini<=min_pezzi UNION ";

        $sql.="SELECT id, label, descrizione, min_pezzi, pezzi_mag, pezzi_ordini, pezzi_mag-pezzi_ordini as pezzi_tot FROM(
                SELECT
                    t_polso_id as id,
                    tele_polso.label as label,
                    tele_polso.descrizione as descrizione,
                    tele_polso.min_pezzi,
                    (SELECT sum(t_polso_qty) FROM movimenti as mov_mag WHERE mov_mag.t_polso_id = mov_all.t_polso_id AND id_ordine IS NULL)  pezzi_mag,
                    (SELECT sum(t_polso_qty) FROM movimenti as mov_ord  WHERE mov_ord.t_polso_id = mov_all.t_polso_id AND id_ordine IS NOT NULL ) pezzi_ordini
                FROM
                    movimenti as mov_all,
                    tele AS tele_polso
                WHERE
                    t_polso_id = tele_polso.id
                GROUP BY t_polso_id
                ) t_colli WHERE pezzi_mag-pezzi_ordini<=min_pezzi";


        //$sql = substr($sql, 0, -7);
        Dbg::d("sql__",$sql,1);

        $res= $this->db->query($sql);

        if($res){

            $mail_body="I seguenti pezzi hanno superato la quantita minima: <br/><br/><br/>";

            foreach ($res as $row){
                $mail_body.="id: ".$row->id."<br/>";
                $mail_body.="label: ".$row->label."<br/>";
                $mail_body.="descrizione: ".$row->descrizione."<br/>";
                $mail_body.="pezzi minimi: ".$row->min_pezzi."<br/>";
                $mail_body.="pezzi disponibili ".$row->pezzi_tot."<br/>";
                $mail_body.="----------------------- <br/>";
            }


            $this->app_lectra->sendMail("Su misura, quantita minima raggiunta", $mail_body,  $this->app_lectra->email["logistica"]);
        }

        Dbg::d("checkpezzi","--------",1);


        $end_res_crea_proforma = $this->create_proforma();
        if($end_res_crea_proforma["success"]=1){
            $end_res= $end_res_crea_proforma;
        }

        return $end_res;

    }


    /******************************
     * PROFORMA
     *******************************/

    public function print_proforma($ordini){


        Dbg::d("ordiniiii", $ordini,1);


        $ordini_id = array();
        foreach ($ordini as $key_ord=>$val_ord){

            Dbg::d("keyord", $key_ord,1);

            $ordini_id[] = $key_ord;
        }



        Dbg::d("ordini_id", $ordini_id,1);


        $movimenti_ordini = $this->get_movimenti_by_ordini($ordini_id);
        $movimenti_ordini=$movimenti_ordini["data"];


        Dbg::d("movimenti ordini", $movimenti_ordini,1);


        $html_body="<h1>Proforma.</h1>";


        foreach ($movimenti_ordini as $key_mov=>$val_mov){
            $html_body.="<p1>".$val_mov["id"]."\t".$val_mov["label"]."\t".$val_mov["barcode"]."\t".$val_mov["descrizione"]."\t".$val_mov["pezzi_ordini"]."\t".$val_mov["prezzo"]."</p1><br/>";
        }


        Dbg::d("html body",$html_body,1);

        $now = new DateTime();
        $title="Proforma a".$now->format("Y m d");
        $filename= "proforma_".$now->format("Ymd_His").".pdf";
        $end_res_pdf_storico = $this->app_lectra->create_pdf($title, $html_body, $filename);
        if($end_res_pdf_storico["success"]==1) {
            $title="Proforma b".$now->format("Y m d");
            $filename = "proforma_" .$now->format("Ymd").".pdf";
            $end_res_pdf_finale = $this->app_lectra->create_pdf($title, $html_body, $filename);

            Dbg::d("abbb","-----",1);

            if($end_res_pdf_finale["success"]==1){


                Dbg::d("acc","-----",1);

                Dbg::d("endres pdf finale",$end_res_pdf_finale,1 );

                $end_res =  $end_res_pdf_finale;
            }
        }



        return $end_res;
    }






    function create_proforma(){

        Dbg::d("create proforma","----------",1);
        $end_res= (array) new EndRes(0,'Errore creazione proforma');

        $sql="SELECT * FROM orders WHERE stato=5;";
        $res= $this->db->query($sql);
        $ids=array();

        if($res) {
            foreach ($res as $row){
                $ids[]=$row->id;
            }
        }

        Dbg::d("ids",$ids,1);

        $ordini = $this->app_lectra->get_dati_ordine($ids);
        $end_res_proforma = $this->print_proforma($ordini);
        if($end_res_proforma["success"]==1){

            $now = new DateTime();
            $mail_body="In allegato proforma ".$now->format("Y m d").":  <br/><br/><br/>";
            if($this->app_lectra->sendMail("Proforma ".$now->format("Y m d"), $mail_body,  $this->app_lectra->email["logistica"], array($end_res_proforma["data"]["filepathname"]))){

                $end_res = $end_res_proforma;
            };

        }

        return $end_res;

    }






    /******************************
     * FATTURA
     *******************************/


    function create_fattura(){

        Dbg::d("create proforma","----------",1);
        $end_res= (array) new EndRes(0,'Errore creazione proforma');

        $sql="SELECT * FROM orders WHERE stato=5;";
        $res= $this->db->query($sql);
        $ids=array();

        if($res) {
            foreach ($res as $row){
                $ids[]=$row->id;
            }
        }

        Dbg::d("ids",$ids,1);
        $ordini = $this->app_lectra->get_dati_ordine($ids);
        $end_res_proforma = $this->print_fattura($ordini);
        if($end_res_proforma["success"]==1){

            $now = new DateTime();
            $mail_body="In allegato proforma ".$now->format("Y m d").":  <br/><br/><br/>";
            if($this->app_lectra->sendMail("Proforma ".$now->format("Y m d"), $mail_body,  $this->app_lectra->email["logistica"], array($end_res_proforma["data"]["filepathname"]))){


                $end_res = $end_res_proforma;
            };

        }

        return $end_res;

    }



    public function print_fattura(){

        $sql= "SELECT * FROM pezzi LIMIT 2;";
        Dbg::d("sql",$sql,1);
        $res= $this->db->query($sql);
        $html_body="<h1>Istruzioni...</h1>";
        foreach ($res as $row){
            $html_body.="<p1>".$row->descrizione."</p1>";
        }

        Dbg::d("html body",$html_body,1);

        $now = new DateTime();
        $title="Fattura".$now->format("Y m d");
        $filename= "fattura_".$now->format("Ymd_His").".pdf";
        $end_res_pdf_storico = $this->app_lectra->create_pdf($title, $html_body, $filename);
        if($end_res_pdf_storico["success"]==1) {
            $title="Fattura".$now->format("Y m d");
            $filename = "fattura_" .$now->format("Ymd").".pdf";
            $end_res_pdf_finale = $this->app_lectra->create_pdf($title, $html_body, $filename);
            if($end_res_pdf_finale["success"]==1){
                $end_res =  $end_res_pdf_finale;
            }
        }

        return $end_res;
    }



}



class LectraUser{

    public $data;
    public $db;
    public $as400;
    public $crm_api_path_set = "http://172.31.212.21:9001/api/extDataAccess/SetClientiDettaglio";
    public $crm_api_path_get = "http://172.31.212.21:9001/api/extDataAccess/GetClienteDettaglio";
    public $tad_token= "CLT-61F898F8-BC0B-4DE5-AE0D-84E97D104ED6";
    public $mandatory=array("CRMId", "Cognome", "Nome", "DataNascita", "Email", "Via", "Localita", "Provincia", "Nazione", "Cap", "Telefono");


    function __construct($data=NULL,$db, $as400, $api_caller){

        $this->api_caller = $api_caller;
        $this->db=$db;
        $this->as400=$as400;
        if(isset($data)){
               $this->data=$data;
               Dbg::d("data",$data,1);
        }

    }

    public function get_crm_id(){
        $end_res = (array)new EndRes(0,'Errore CRM id');
        $sql="DELETE FROM clienti_crm_id; INSERT INTO clienti_crm_id (id) VALUES(NULL);";
        $res= $this->db->query($sql);

        if($res=="none"){
            $sql="SELECT LAST_INSERT_ID() as last_insert_id;";
            $res= $this->db->query($sql);

            if($res){
                $last_id= $res[0]->last_insert_id;
                $end_res["success"]=1;
                $end_res["error"]="";
                $end_res["data"]["last_insert_id"]=$last_id;
            }
        }
        return $end_res;

    }

    function get_by_crm(){








        $end_res = (array)new EndRes(0,'Errore ricezione dati');
        $end_res_validate_email = $this->validate_email($this->data["Email"]);
        if($end_res_validate_email["success"]==1){


            /*
                        $sql="SELECT
                                NOMACD,
                                COGACD,
                                DNNACD,
                                SESACD,
                                INDACD,
                                CAIACD,
                                LOCACD,
                                PROACD,
                                TELACD,
                                AMAACD,
                                PIVACD,
                                CFIACD,
                                DNAACD
                                FROM
                                ANCDSPF
                                WHERE
                                EMAACD  = '".$this->data["Email"]."'";

                        $res = $this->as400->query($sql);



                        $data=array();
                        if($res){
                            $data["Nome"]=$res[0]["NOMACD"];
                            $data["Cognome"]=$res[0]["COGACD"];
                            $data["DataNascita"]=$res[0]["DNNACD"];
                            $data["Sesso"]=$res[0]["SESACD"];
                            $data["PartitaIva"]=$res[0]["PIVACD"];
                            $data["CodiceFiscale"]=$res[0]["PIVACD"];


                        }


            */




            $params = array();
            $params["Email"] = $this->data["Email"];
            $params=  json_encode($params,JSON_UNESCAPED_SLASHES);

            $headers = array();
            $headers[] = "TadAuthToken: " . $this->tad_token;
            $headers[] = "Content-Type: application/json";

            $this->api_caller->resetHeader();
            $this->api_caller->setHeader($headers);

            Dbg::d("headers__",$headers,1);
            Dbg::d("path__",$this->crm_api_path_get,1);
            Dbg::d("params__",$params,1);

            $res_call= $this->api_caller->callAPI("POST", $this->crm_api_path_get,$params);
            Dbg::d("api_caller ",$this->api_caller,1);
            $res_call = json_decode($res_call);
            Dbg::d("res_call",$res_call,1);
            if(count($res_call->Errors)>0){
                Dbg::d("errore--- ","-----",1);
                $end_res["error"]="";
                foreach ($res_call->Errors as   $errore  ){
                    $end_res["success"]=0;
                    $end_res["error"] = $errore->Error."<br/><br/>";
                }
            }else{
                if(!isset($res_call->Cliente)){
                    $end_res["success"]=1;
                    $end_res["error"]="Cliente non trovato";
                }else{
                    //scelta indirizzo nel frontend
                    $end_res["success"]=1;
                    $end_res["data"]= $res_call->Cliente;
                }
            }




        }else{
            $end_res = $end_res_validate_email;
        }
        return $end_res;
    }




    function save_on_crm($last_insert_id){

        $end_res = (array)new EndRes(0,'Errore salvataggio su crm');

        $CRMId="LCT|".$last_insert_id."";
        $this->data["CRMId"]=$CRMId;
        $end_res_validate = $this->validate($this->data);

        if($end_res_validate["success"]==1) {

            $data = $this->data;
            $body_data = array();
            $body_data["ID"] = $CRMId;
            $body_data["RagioneSociale"] = $data["Cognome"] . " " . $data["Nome"];
            $body_data["Cognome"] = $data["Cognome"];
            $body_data["Nome"] = $data["Nome"];
            $body_data["DataNascita"] = $data["DataNascita"];
            $body_data["Sesso"] = $data["Sesso"];
            $body_data["PartitaIva"] = $data["PartitaIva"];
            $body_data["Pec"] = "";
            $body_data["CodiceUfficioDestinatario"] = null;
            $body_data["CodiceFiscale"] = $data["CodiceFiscale"];
            $body_data["Email"] = $data["Email"];
            $body_data["Attivo"] = true;
            $body_data["Cancellato"] = false;
            $body_data["ConsensoMarketing"] = false;
            $body_data["ConsensoProfilazione"] = false;
            $body_data["ConsensoComunicazioneATerzi"] = false;
            $body_data["Note"] = "";
            $body_data["LinguaISO"] = "IT";
            $body_data["Store"] = "Demo";
            $body_data["Indirizzi"] = array();
            $body_data["Indirizzi"][] = array(
                "Intestatario"=>$data["Nome"] . " " . $data["Cognome"],
                "Via"=>$data["Via"],
                "Localita"=>$data["Localita"],
                "Provincia"=>$data["Provincia"],
                "Nazione"=>$data["Nazione"],
                "Cap"=>$data["Cap"],
                "Telefono"=>$data["Telefono"]
            );
            $body_data["ConsensoEmail"] = false;

            $params = array();
            $params["Clienti"] = array();
            $params["Clienti"][] = $body_data;
            $params = json_encode($params);


            $headers = array();
            $headers[] = "TadAuthToken: " . $this->tad_token;
            $headers[] = "Content-Type: application/json";

            $this->api_caller->resetHeader();
            $this->api_caller->setHeader($headers);

            Dbg::d("headers__",$headers,1);
            Dbg::d("path__",$this->crm_api_path_set,1);
            Dbg::d("params__",$params,1);

            $res_call= $this->api_caller->callAPI("POST", $this->crm_api_path_set,$params);
            Dbg::d("api_caller ",$this->api_caller,1);
            $res_call = json_decode($res_call);
            Dbg::d("res_call",$res_call,1);
            if(count($res_call->Errors)>0){

                Dbg::d("errore--- ","-----",1);
                $end_res["error"]="";
                foreach ($res_call->Errors as   $errore  ){
                    $end_res["success"]=0;
                    $end_res["error"] = $errore->Error."<br/><br/>";
                }
            }else{
                $end_res["success"]=1;
                $end_res["error"]="";
            }
            Dbg::d("res_call3",$end_res,1);

        }

        return $end_res;

    }



    function update_on_crm($CRMId){

        $end_res = (array)new EndRes(0,'Errore salvataggio su crm');

        //$CRMId="LECTRA|".$last_insert_id."";
        $this->data["CRMId"]=$CRMId;
        $end_res_validate = $this->validate($this->data);

        if($end_res_validate["success"]==1) {

            $data = $this->data;
            $body_data = array();
            $body_data["ID"] = $CRMId;
            $body_data["RagioneSociale"] = $data["Cognome"] . " " . $data["Nome"];
            $body_data["Cognome"] = $data["Cognome"];
            $body_data["Nome"] = $data["Nome"];
            $body_data["DataNascita"] = $data["DataNascita"];
            $body_data["Sesso"] = $data["Sesso"];
            $body_data["PartitaIva"] = $data["PartitaIva"];
            $body_data["Pec"] = "";
            $body_data["CodiceUfficioDestinatario"] = null;
            $body_data["CodiceFiscale"] = $data["CodiceFiscale"];
            $body_data["Email"] = $data["Email"];
            $body_data["Attivo"] = true;
            $body_data["Cancellato"] = false;
            $body_data["ConsensoMarketing"] = false;
            $body_data["ConsensoProfilazione"] = false;
            $body_data["ConsensoComunicazioneATerzi"] = false;
            $body_data["Note"] = "";
            $body_data["LinguaISO"] = "IT";
            $body_data["Store"] = "Demo";
            $body_data["Indirizzi"] = array();
            $body_data["Indirizzi"][] = array(
                "Intestatario"=>$data["Nome"] . " " . $data["Cognome"],
                "Via"=>$data["Via"],
                "Localita"=>$data["Localita"],
                "Provincia"=>$data["Provincia"],
                "Nazione"=>$data["Nazione"],
                "Cap"=>$data["Cap"],
                "Telefono"=>$data["Telefono"]
            );
            $body_data["ConsensoEmail"] = false;

            $params = array();
            $params["Clienti"] = array();
            $params["Clienti"][] = $body_data;
            $params = json_encode($params);


            $headers = array();
            $headers[] = "TadAuthToken: " . $this->tad_token;
            $headers[] = "Content-Type: application/json";

            $this->api_caller->resetHeader();
            $this->api_caller->setHeader($headers);

            Dbg::d("headers__",$headers,1);
            Dbg::d("path__",$this->crm_api_path_set,1);
            Dbg::d("params__",$params,1);

            $res_call= $this->api_caller->callAPI("POST", $this->crm_api_path_set,$params);
            Dbg::d("api_caller ",$this->api_caller,1);
            $res_call = json_decode($res_call);
            Dbg::d("res_call",$res_call,1);
            if(count($res_call->Errors)>0){

                Dbg::d("errore--- ","-----",1);
                $end_res["error"]="";
                foreach ($res_call->Errors as   $errore  ){
                    $end_res["success"]=0;
                    $end_res["error"] = $errore->Error."<br/><br/>";
                }
            }else{
                $end_res["success"]=1;
                $end_res["error"]="";
            }
            Dbg::d("res_call3",$end_res,1);

        }

        return $end_res;

    }



    function save($data_negozio=NULL, $crm_id=NULL){

        $data_int= array(
            "ConsensoMarketing",
            "ConsensoProfilazione",
            "ConsensoComunicazioneATerzi",
            "ConsensoEmail"
        );

        $end_res = (array)new EndRes(0,'Errore salvataggio');
        Dbg::d("saveeee","---", 1 );
        $end_res_validate = $this->validate($this->data);

        Dbg::d("endresvalidate",$end_res_validate,1);
        Dbg::d("endresvalidateee",$end_res_validate["success"],1);
        Dbg::d("data negozio", $data_negozio,1);

        if(isset($data_negozio)){

            Dbg::d("data negozio oooo1", $data_negozio,1);
            $this->data["NomeNeg"]=addslashes($data_negozio["NOME_NEGOZI0"]);
            $this->data["DescrizioneNeg"]=addslashes($data_negozio["DESCRIZIONE"]);
            $this->data["IndirizzoNeg"]=addslashes($data_negozio["INDIRIZZO"]);
            $this->data["IndirizzoEstensioneNeg"]=addslashes($data_negozio["INDIRIZZO_ESTENSIONE"]);
            $this->data["LocalitaNeg"]=addslashes($data_negozio["LOCALITA"]);
            $this->data["ProvinciaNeg"]=addslashes($data_negozio["PROVINCIA"]);
            $this->data["CAPNeg"]=addslashes($data_negozio["CAP"]);
            $this->data["TelefonoNeg"]=addslashes($data_negozio["TEL"]);
            $this->data["PIVANeg"]=addslashes($data_negozio["PIVA"]);
        }

        if($end_res_validate["success"]==1){
            $sql="INSERT INTO clienti (";
                foreach ($this->data as $key_item=>$val_item){
                    $sql.= $key_item.", ";
                }

            $sql =  substr($sql, 0, -2);
            $sql.=") VALUES (";
            foreach ($this->data as $key_item=>$val_item){
                if(in_array($key_item, $data_int) ){
                    $sql.=   "".$val_item.", ";
                }else{
                    $sql.=   "'".$val_item."', ";
                }
            }

            Dbg::d("sqllll",$sql,1);

            $sql =  substr($sql, 0, -2);
            $sql.=");";
            $res= $this->db->query($sql);
            if($res!="error_exec"){
                $sql="SELECT LAST_INSERT_ID() as last_insert_id";
                $res= $this->db->query($sql);
                if($res!="error_exec"){
                    $end_res["success"]=1;
                    $end_res["error"]="";
                    $end_res["data"]["last_insert_id"]=$res[0]->last_insert_id;
                }
            }

        }else{
            $end_res= $end_res_validate;
        }

        return $end_res;

    }


    function validate($data){

        $end_res = (array) new EndRes(1,"");
        foreach ($this->data as $key => $item){
             if(in_array($key,$this->mandatory) && $item==""){
                 $end_res["success"]=0;
                 $end_res["error"].= "Campo ".$key." obbligatorio.<br/>";

            }
        }

        foreach ($this->mandatory as  $item){
            if(!isset($this->data[$item])){
                $end_res["success"]=0;
                $end_res["error"].= "Campo ".$item." obbligatorio.<br/>";

            }
        }

        if($end_res["success"]==1){
            if(isset($this->data["Email"])){
                $end_res_validate_email  = $this->validate_email($this->data["Email"]);
                if($end_res_validate_email["success"]==0){
                    $end_res =  $end_res_validate_email;

                }
            }

        }

        return $end_res;
    }


    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    function validate_email($email){

        $end_res = (array) new EndRes(0,"Errore Validazione email");
        $email = $this->test_input($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $end_res["success"]=1;
            $end_res["error"]="";
        }
        return $end_res;
    }
}


class LectraField extends Field{

    public function __construct( $db, $db_type, $data_type=NULL, $label=NULL, $sql=NULL, $validation=NULL, $corr_field=NULL, $custom_props=NULL){

        $this->curr_db= $db;
        $this->db_type= $db_type;
        $this->data_type = $data_type;
        $this->label = $label;
        $this->validation= $validation;
        $this->sql = $sql;
        if(isset($this->sql)){
            if(strpos($this->sql,"WHERE")=== false){
                $this->sql.=" WHERE 1=1";
            }
        }
        $this->corr_field = $corr_field;
        $this->custom_props = $custom_props;
        if(!is_null($custom_props)){
            foreach ($custom_props as $key_prop => $val_prop){
                $this->{$key_prop} = $val_prop;
            }
        }
    }

    public function add_args($args){


        Dbg::d("add args",$args,1);

        if(count($args)>0){
            $this->sql.=" AND ".implode(" AND ",$args);
        }
        if($this->db_type=="ind"){
            $this->sql.=";";
        }
    }

    public function get_data(){


        Dbg::d("get data sql",$this->sql,1);

        $data = array();
        switch ($this->db_type){
            case "custom_values":
                $data = $this->custom_props["custom_values"];
                break;
            case "ind":
                $res= $this->curr_db->query($this->sql);
                if($res){
                    foreach ($res as $row){
                        Dbg::d("row",$row,1);
                        $data[$row->ID] = (array) $row;
                    }
                }
                break;
             case "as400":
                $res = $this->curr_db->query($this->sql);
                 if($res){
                     foreach ($res as $row){
                         Dbg::d("row",$row,1);
                         $row_ar = (array) $row;
                         foreach ($row_ar as $key_row=>$val_row){
                             $data[$row->ID][$key_row]=htmlentities($val_row);
                         }

                     }
                 }
                break;
        }
        return $data;
    }
}



class AppLectra
{

    protected $env;
    protected $as400;
    protected $db;
    protected $db_name;
    protected $request;
    protected $shop_id;
    protected $api_caller;
    protected $api_path_plan;
    protected $api_path_prepare;
    protected $api_path_plan_plan;
    protected $session;
    public $email;
    protected $action;
    protected $method;
    protected $access_token;
    protected $pdf_path;
    protected $pdf_ext_path;
    protected $backoffice_authorized_coms;
    protected $backoffice_authorized_actions = array(
            "rolls",
            "batches",
            "materials",
            "materials_sets",
            "get_lista_ordini",
            "save_prodotto_da_cucire",
            "save_prodotto_completato",
            "import_rolls"
    );


    protected $now;


    function __construct($env, $request,$session, $db, $as400, $db_ind, $api_auth_path, $api_path_plan,$api_path_prepare, $pdf_path, $pdf_ext_path, $backoffice_authorized_coms, $email)
    {

        error_log("request");
        error_log(json_encode($request));


        error_log(json_encode($this->req));

        $this->req = new Req($request, $this->db);


        $this->request= $this->req->clean();

        error_log(json_encode($this->request));


        Dbg::d("request",$request,1 );
        Dbg::d("session",$session,1 );
        $this->pdf_path= $pdf_path;
        $this->pdf_ext_path= $pdf_ext_path;
        $this->db=$db;
        $this->db_ind=$db_ind;
        $this->as400=$as400;
        $this->env=$env;
        $this->session= $session;
        $this->api_path_plan= $api_path_plan;
        $this->api_auth_path= $api_auth_path;
        $this->api_path_prepare= $api_path_prepare;
        $this->backoffice_authorized_coms = $backoffice_authorized_coms;
        $this->logistica = new Logistica($this->db, $this->as400, $this->req, $this);

        $this->actions= array();
        $this->api_caller= new APICaller();

        if($this->env="stage"){
            $sql= "USE lectra_stage;";
            $this->db->query($sql);
        }else if($this->env="prod"){
            $sql= "USE lectra_prod;";
            $this->db->query($sql);
        }

        $this->request = $request;
        if(isset($this->request["action"])){
            $this->action= $this->request["action"];
            $this->api_path_plan.=$this->action."/";
        }
        if(isset($this->request["method"])){
            $this->method= $this->request["method"];
        }

        $this->fields  = $this->add_fields();
        $this->fields_manager = new FieldManager($db, $as400, $this->fields);
        $this->email=$email;
        $this->start();
    }


    function sendMail($subject, $body, $email, $files = NULL)
    {

        Dbg::d("send email subject",$subject,1);
        Dbg::d("send email body",$body,1);
        Dbg::d("send email email",$email,1);
        Dbg::d("files",$files,1);

        return 1;


        $mail = new ConfirmInvioMail($subject);
        $mail->setHost("fast.smtpok.com");
        $mail->setUserPassword("s9091_2", "EuifU?07dZ");
        $mail->setPort(25);
        $mail->setSMTPSecure("none");
        $mail->setBody($body);
        //$mail->A($email);
        if ($files) {
            if (is_array($files)) {
                foreach ($files as $file) {

                    Dbg::d("fileee",$file,1);

                    $mail->allegato($file);
                }
            } else {
                //$mail->allegato($file);
            }
        }
        $mail->CCN(array("psalemi@feniciaspa.it"));
        return $mail->send();
    }





    function login($pwd_encoded=NULL) {

        $end_res= (array) new EndRes(0,'Errore login');
        $params =  $this->req->get_request_data("val_",4,1);



        if( !isset($params["codcom"]) || !isset($params["password"]) ||  !isset($params["codneg"]) ){
            $end_res["success"]=0;
            $end_res["error"]="Inserire user e password e verificare codice negozio";
        } else{



            if(isset($pwd_encoded)&&$pwd_encoded==1){
                Dbg::d("pwd1",$params["password"],1);
                $params["password"] = base64_decode($params["password"]);
                Dbg::d("pwd2",$params["password"],1);
                $params["password"] = substr($params["password"],4);
                Dbg::d("pwd3",$params["password"],1);

                $query_params= array(
                    ":codcom"=>$params["codcom"],
                    ":password"=>$params["password"]
                );
                $sql = "SELECT codcom, descrizione, first_login
                    FROM attendance.user
                    WHERE codcom=:codcom
                    and pass = :password
                    and active = 1";

            }else{

                $query_params= array(
                    ":codcom"=>$params["codcom"],
                    ":password"=>$params["password"]
                );
                $sql = "SELECT codcom, descrizione, first_login
                    FROM attendance.user
                    WHERE codcom=:codcom
                    and pass = PASSWORD(:password)
                    and active = 1";

            }


            $res = $this->db_ind->query($sql,$query_params);


            Dbg::d("this->db",$this->db_ind,1);

            Dbg::d("res",$res,1);

            if(!$res){
                $end_res["success"]=0;
                $end_res["error"]="Verificare utenti e password";
             }elseif($res[0]->first_login){
                $end_res["success"]=0;
                $end_res["error"]="E' necessario cambiare la password utilizzando il sistema di timbrature";
            }else {
                $end_res["success"]=1;
                $end_res["error"]="";
            }

        }


        if($end_res["success"]==0){
            $login_result = "FAILED";

        }else{
            $login_result = "CORRECT";
        }

        $query_params= array(
            ":codcom"=>$params["codcom"],
            ":codneg"=>$params["codneg"],
            ":action"=>"LOGIN",
            ":result"=>$login_result,
        );

        $sql="INSERT INTO checklog (codcom, codneg ,action,result)
              VALUES (:codcom, :codneg, :action, :result)";


        $res = $this->db->query($sql,$query_params);

        if($login_result == "CORRECT"){
            $_SESSION['auth'] = 1;
            $_SESSION['codcom'] = $params['codcom'];
            $_SESSION['codneg'] = $params['codneg'];
            $_SESSION["logged"] = "LOGGED";

        }else{
            $end_res["success"]=0;
            $end_res["errore"]="errore log login";
        }

        return $end_res;
    }



    function logout() {


        $end_res= (array) new EndRes(0,'errore logout');

        $logout_result="FAILED";

        $sess_destroyed=0;

        if(session_destroy()){
            $logout_result="CORRECT";
            $sess_destroyed=1;
        };
        $query_params= array(
            ":codcom"=>$this->session["codcom"],
            ":codneg"=>$this->session["codneg"],
            ":action"=>"LOGOUT",
            ":result"=>$logout_result,
        );

        $sql="INSERT INTO checklog (codcom,codneg, action, result)
              VALUES (:codcom, :codneg, :action, :result)";

        $res = $this->db->query($sql,$query_params);

        if($res='none' && $sess_destroyed==1){
            $end_res["success"]=1;
            $end_res["error"]='';
        }


        return $end_res;

    }

    public function check_ricami(){

        $params =  $this->req->get_request_data("val_",4,1);
        $end_res= (array) new EndRes(0,'Errore ricami');
        $n_lettere = strlen($params["testo"]);
        if($n_lettere<>2 && $n_lettere<>3){
            $end_res["success"]=0;
            $end_res["error"]="Verificare il numero di lettere";

        }else{
            $end_res["success"]=1;
            $end_res["error"]='';
        }
        return $end_res;
    }
    /**
     * Crea la bolla di lavorazione dell'articolo dal suo barcode
     * @return array
     */
    public function create_bolla_lavorazione(){
        $params = $this->req->get_request_data("val_", 4, 1);
        $prod_order = ProductOrder::where("barcode_univoco", $params["barcode"]);
        if(!$prod_order || !isset($prod_order[0]))
            return (array) new EndRes(0, "Prodotto inesistente");

        $bolla_lav_service = new BollaLavorazioneService($prod_order[0]->id);
        $title = "Bolla Lavorazione - Fasonista FD3";
        $html_body = $bolla_lav_service->getHtmlBollaLavorazione();
        $filename = "bolla_lav_" . $prod_order[0]->barcode_univoco . ".pdf";
        $end_res_pdf_bolla = $this->create_pdf($title, $html_body, $filename);

        return ($end_res_pdf_bolla["success"] == 1)
            ? $end_res_pdf_bolla
            : (array) new EndRes(0, "Errore creazione pdf bolla lavorazione");
    }

    public function downloadFile($filepath){
        if (!file_exists($filepath)) {
            error_log("File inesistente in ".$filepath.". Exit");
            return;
        }
        error_log("Starting download excel...");
        header("Content-disposition: attachment; filename=bolla.pdf");
        // header("Content-disposition: attachment;");
        ob_clean();
        flush();
        readfile($filepath);
    }

    public function has_bottoni_madreperla(){

        $end_res = (array) new EndRes(1,'');
        $end_res["data"]["has_bottoni_madreperla"]=0;
        $params  =  $this->req->get_request_data("val_",4,1);


        $params["field"]="materiale";
        $params["check_has_bottoni_madreperla"]=1;
        $data = $this->get_field_data($params);
        $data = $data["data"];

        Dbg::d("dataaa3",$data,1);

        if(count($data)){

            $end_res["data"]["has_bottoni_madreperla"]=1;
        }

        Dbg::d("end ressss", $end_res,1);
        return $end_res;
    }




    public function calc_ricami($n_lettere, $stile, $simbolo){

         $end_res= (array) new EndRes(1,"");
         $data = array();
         if($n_lettere>0){
             $sql = "SELECT * FROM dettagli WHERE n_lettere=".$n_lettere."  and stile=".$stile.";";
             Dbg::d("sql",$sql,1);
             $res= $this->db->query($sql);
             Dbg::d("res",$res,1);
             if($res){
                 $data["iniziali"]["barcode"]= $res[0]->barcode;
                 $data["iniziali"]["prezzo"]= $res[0]->prezzo;
             }
         }
         //simbolo
         if($simbolo!=1){
             $sql = "SELECT * FROM dettagli WHERE simbolo=1;";
             Dbg::d("sql",$sql,1);
             $res= $this->db->query($sql);
             if($res){
                 $data["simbolo"]["barcode"]= $res[0]->barcode;
                 $data["simbolo"]["prezzo"]= $res[0]->prezzo;
             }
         }
         $end_res["data"]=$data;
         return $data;


    }


    public function add_fields(){

        $this->fields = array();

        $this->fields['product'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "product",
            "SELECT id AS ID, reference AS DESCRIZIONE, tipo AS TIPO FROM product",
            NULL,
            NULL,
            NULL
        );

        $this->fields['product_sizes'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "product",
            "SELECT id as ID,
                        taglia as DESCRIZIONE,
                        is_default as IS_DEFAULT
                        FROM product_sizes",
            NULL,
            NULL,
            NULL
        );

        $this->fields['materiale'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "materiale",
            "SELECT
                    materiali.id as ID,
                    reference as CODICE_MATERIALE,
                     CONCAT_WS(' ', materiali.nature, materiali.color) as DESCRIZIONE,
                    article_code as ARTICLE_CODE,
                    color as COLOR,
                    nature as NATURE,
                    composition as COMPOSITION,
                    motif_type as MOTYF_TYPE,
                    bottone_madreperla as BOTTONE_MADREPERLA,
                    bottone_madreperla_dettagli.label AS BOTTONE_MADREPERLA_LABEL,
                    bottone_poliestere as BOTTONE_POLIESTERE,
                    bottone_poliestere_dettagli.label AS BOTTONE_POLIESTERE_LABEL,
                    tele AS TELE,
                    tela_dettagli.label AS TELE_LABEL,
                    etichette_lavaggio AS ETICHETTE_LAVAGGI,
                    etichette_lavaggio.label AS ETICHETTE_LAVAGGI0_LABEL
                FROM
                    lectra_stage.materiali
                        LEFT JOIN
                    colori_dettagli AS bottone_poliestere_dettagli ON bottone_poliestere = bottone_poliestere_dettagli.id
                        LEFT JOIN
                    colori_dettagli AS bottone_madreperla_dettagli ON bottone_madreperla = bottone_madreperla_dettagli.id
                        LEFT JOIN
                    colori_dettagli AS tela_dettagli ON tele = tela_dettagli.id
                        LEFT JOIN
                    etichette_lavaggio AS etichette_lavaggio ON etichette_lavaggio = etichette_lavaggio.id",
            NULL,
            NULL,
            NULL
        );


        $this->fields['lista_componenti'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "lista_componenti",
            "SELECT id as ID, codice as CODICE, label as DESCRIZIONE FROM component_type",
            NULL,
            NULL,
            NULL
        );


        $this->fields['componente'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "davanti",
            "SELECT
                    component.id as ID,
                    CONCAT(component_type.codice, component.codice) as CODICE,
                    component.descrizione as DESCRIZIONE,
                    component.has_contrast as HAS_CONTRAST,
                    component.n_tasche as N_TASCHE,
                    component.has_polsi as HAS_POLSI,
                    component.carre_intero as HAS_CARRE_INTERO
                    FROM
                    component,component_type
                    WHERE
                    component_type.id = component.component_type_id",
            NULL,
            NULL,
            NULL
        );

        $this->fields["alteration"] = new LectraField(
            $this->db,
            "ind",
            "int",
            "alteration",
            "SELECT
                  id as ID,
                  label as DESCRIZIONE,
                  min_size AS MIN_SIZE,
                  max_size AS MAX_SIZE,
                  step AS STEP,
                  alteration_type AS TIPO_ALTERAZIONE,
                  valori_mm AS VALORI_MM
                  FROM alteration WHERE custom=0",
            NULL,
            NULL,
            NULL
        );

        $this->fields["negozi"] = new LectraField(
            $this->as400,
            "as400",
            "int",
            "negozi",
            "SELECT * FROM(
            SELECT
                TRIM(substr(KEYFTA, 4)) AS ID,
                trim(substr(KEYFTA, 4)) AS CODNEG,
                trim(substring(FILTAB,4,30)) AS NOME_NEGOZI0,
                substring(filtab, 118, 2) AS MAGAZZINO,
                TRIM(DESCAN) AS DESCRIZIONE,
                TRIM(INDCAN) AS INDIRIZZO,
                TRIM(INECAN) AS INDIRIZZO_ESTENSIONE,
                TRIM(LOCCAN) AS LOCALITA,
                TRIM(PROCAN) AS PROVINCIA,
                TRIM(CAICAN) AS CAP,
                TRIM(TELCAN) AS TEL,
                TRIM(PIVCAN) AS PIVA
            FROM AZ101.FTABPF, AZGRPCAMI.CANAPF, AZGRPCAMI.UTMAGPF, AZGRPCAMI.FANAPF
            WHERE
                KEYFTA like 'S%'
                AND substr(FILTAB, 1, 1) <> '1'
                AND trim(substr(FILTAB, 99, 6)) = CODCAN
                AND trim(substr(KEYFTA, 4)) not like '7%'
                AND trim(substr(KEYFTA, 4)) = NMAUTM
                AND AGECAN = CODFAN
                AND substr(FILTAB, 120, 2 ) <> 'E'
                AND DCHUTM = 0
                AND trim(substr(KEYFTA, 4)) NOT IN('275','281','282','283','323','324','325','326','328')
                ORDER BY trim(desfan),substring(FILTAB,4,30)
                ) WHERE 1=1",

            NULL,
            NULL,
            NULL
        );

        $this->fields["regione"] = new LectraField(
            $this->db,
            "ind",
            "int",
            "regione",
            "SELECT
                  codice as ID,
                  codice as CODICE,
                  label AS DESCRIZIONE
                  FROM regione",
            NULL,
            NULL,
            NULL
        );

        $this->fields["provincia"] = new LectraField(
            $this->db,
            "ind",
            "int",
            "provincia",
            "SELECT
                    codice as ID,
                    codice as CODICE,
                    label as DESCRIZIONE
                  FROM provincia",
            NULL,
            NULL,
            NULL
        );

        $this->fields['sesso'] = new LectraField(
            $this->db,
            "custom_values",
            "int",
            "sesso",
            "",
            NULL,
            NULL,
            array("custom_values"=>array(
                                    "f"=>"f",
                                    "m"=>"m")
            )
        );

        $this->fields['bottoni'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "bottoni",
            "SELECT * FROM bottoni;",
            NULL,
            NULL,
            NULL,
                NULL,
                NULL

        );

        $this->fields['ricami_colore'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "ricami_colore",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='CO';",
            NULL,
            NULL,
             NULL
        );

        $this->fields['ricami_posizione'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "ricami_posizione_simbolo",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='PC';",
            NULL,
            NULL,
            NULL
        );

        $this->fields['ricami_stile_testo'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "ricami_stile_testo",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='ST';",
            NULL,
            NULL,
            NULL
        );

        $this->fields['ricami_simbolo'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "ricami_simbolo",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='SI';",
            NULL,
            NULL,
           NULL
        );

        $this->fields['ricami_ordine_simbolo'] = new LectraField(
            $this->db,
            "ind",
            "int",
            "ricami_ordine_simbolo",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='OS'",
            NULL,
            NULL,
            array("custom_values"=>array(
                1=>"Iniziale",
                2=>"Dopo Prima Lettera",
                3=>"Finale"
            )
            )
        );

        $this->fields['ricami_posizione_simbolo'] = new LectraField(
            $this->db,
            "custom_values",
            "int",
            "ricami_posizione_simbolo",
            "SELECT id as ID, label as DESCRIZIONE FROM ricami_opzioni WHERE tipo='PS'",
            NULL,
            NULL,
            array("custom_values"=>array(
                1=>"3/4  bottone",
                2=>"In basso",
                3=>"Polsino"
            )
            )
        );
        return $this->fields;

    }


    public function get_componente(){
        $params = $this->req->get_request_data("val_",4,1);
        $params["field"]='componente';
        $end_res = $this->get_field_data($params);
        return $end_res;

    }


    public function da_a_step($da, $a, $step){

        $end_res = (array)new EndRes(1, '');
        $valori=array();
        $curr_val=number_format($da,2);
        $valori[]=$curr_val;

        do {
            $curr_val+=$step;
            $curr_val=number_format($curr_val,2);
            $valori[]=$curr_val;

        }  while ($curr_val < $a);

        if($curr_val!=$a){
            $end_res["success"]=0;
            $end_res["error"]='Errore valori alterazione';
        }else{
            $end_res["data"] =$valori;
        }

        return $end_res;
    }




    public function get_collo_alterations(){

        $end_res = (array)new EndRes(0, 'Errore recupero dati alterazione collo');
        $params = $this->req->get_request_data("val_",4,1);

        if(!isset($params["product_id"])){
            $end_res["error"]="Definire il tipo di prodotto";
        }


        $da=37;
        $a= 50;
        $step =1;

        if($params["product_id"]==2){

            $da=37;
            $a= 46;
            $step =1;
        }

         Dbg::d("da",$da,1);
         Dbg::d("a",$a,1);
         Dbg::d("step",$step,1);


        $end_res_valori_da_visualizzare = $this->da_a_step($da,$a,$step);
        if($end_res_valori_da_visualizzare["success"]==1){

            $valori_da_visualizzare= $end_res_valori_da_visualizzare["data"];
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]["VALORI_DA_VISUALIZZARE_DA"]=$da;
            $end_res["data"]["VALORI_DA_VISUALIZZARE_A"]=$a;
            $end_res["data"]["VALORI_DA_VISUALIZZARE_STEP"]=$step;
            $end_res["data"]["VALORI_DA_VISUALIZZARE"] =$valori_da_visualizzare;

        }

        return $end_res;

    }


    public function get_taglie(){


        $end_res = (array)new EndRes(0, 'Errore taglia');
        $params = $this->req->get_request_data("val_",4,1);

        if(!isset($params["product_id"])){
            $end_res["error"]="Definire il tipo di prodotto";
            return $end_res;
        }

        if(!isset($params["collo_size"])){
            $end_res["error"]="Definire la taglia del collo";
            return $end_res;
        }

        if($params["product_id"]==2 && $params["collo_size"]>46){
            $end_res["error"]="Valore collo non valido per quel prodotto";
            return $end_res;
        }



        $collo_size=$params["collo_size"];

        if($params["product_id"]==2){
            $max_product_taglia=46;
        }else{
            $max_product_taglia=50;
        }

        $max_taglia = min($collo_size+4, $max_product_taglia);
        $min_taglia = max($collo_size-4, 37);
        $data = $this->da_a_step($min_taglia,$max_taglia, 1 );

        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"] = $data["data"];

        return $end_res;



    }









    public function get_manica_alterations(){

        $end_res = (array)new EndRes(0, 'Errore recupero dati alterazione manica');


        $params = $this->req->get_request_data("val_",4,1);

        if(!isset($params["codcomp"])){
            $end_res["error"]="Definire codice componente";
        }



        $da=-7.50;
        $a= +7.50;
        $step =0.5;

        $maniche_corte= array("8001","8002","8003","8004");
        if(in_array($params["codcomp"],$maniche_corte)){
            $da=-3;
            $a= +6;
            $step =0.5;

        }


       $end_res_valori_da_visualizzare = $this->da_a_step($da,$a,$step);
       if($end_res_valori_da_visualizzare["success"]==1){

           $valori_da_visualizzare= $end_res_valori_da_visualizzare["data"];
           $end_res["data"]["VALORI_DA_VISUALIZZARE_DA"]=$da;
           $end_res["data"]["VALORI_DA_VISUALIZZARE_A"]=$a;
           $end_res["data"]["VALORI_DA_VISUALIZZARE_STEP"]=$step;
           $end_res["data"]["VALORI_DA_VISUALIZZARE"] =$valori_da_visualizzare;

           $da_mm = $da*10;
           $a_mm = $a*10;
           $step = $step*10;
           $end_res_valori_millimetri = $this->da_a_step($da_mm,$a_mm,$step);

           if($end_res_valori_millimetri["success"]==1){
               $end_res["success"]=1;
               $end_res["error"]="";
               $valori_millimetri = $end_res_valori_millimetri["data"];
               $end_res["data"]["VALORI_DA_INVIARE"] =$valori_millimetri;

           }
       }


        return $end_res;

    }

    public function get_polso_alterations(){

        $end_res = (array)new EndRes(0, 'Errore recupero dati alterazione polsi');

        $params = $this->req->get_request_data("val_",4,1);
        if(!isset($params["size"])){
            $end_res["error"]="Definire taglia";
        }

        if(!isset($params["product_id"])){
            $end_res["error"]="Definire prodotto";
        }

        $alterazioni_polsi= array();

        $alterazioni_polsi[1][37]["visualizzare"]=array(4);
        $alterazioni_polsi[1][37]["inviare"]=array(40);

        $alterazioni_polsi[1][38]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[1][38]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[1][39]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[1][39]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[1][40]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[1][40]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[1][41]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[1][41]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[1][42]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[1][42]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[1][43]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[1][43]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[1][44]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[1][44]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[1][45]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][45]["inviare"]=array(-40);

        $alterazioni_polsi[1][46]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][46]["inviare"]=array(-40);

        $alterazioni_polsi[1][47]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][47]["inviare"]=array(-40);

        $alterazioni_polsi[1][48]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][48]["inviare"]=array(-40);

        $alterazioni_polsi[1][49]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][49]["inviare"]=array(-40);

        $alterazioni_polsi[1][50]["visualizzare"]=array(-4);
        $alterazioni_polsi[1][50]["inviare"]=array(-40);


        $alterazioni_polsi[2][37]["visualizzare"]=array(4);
        $alterazioni_polsi[2][37]["inviare"]=array(40);

        $alterazioni_polsi[2][38]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[2][38]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[2][39]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[2][39]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[2][40]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[2][40]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[2][41]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[2][41]["inviare"]=($this->da_a_step(-2, +2, 1))["data"];

        $alterazioni_polsi[2][42]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[2][42]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[2][43]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[2][43]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[2][44]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[2][44]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[2][45]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[2][45]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[2][46]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[2][46]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[3][37]["visualizzare"]=array(4);
        $alterazioni_polsi[3][37]["inviare"]=array(40);

        $alterazioni_polsi[3][38]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[3][38]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[3][39]["visualizzare"]=($this->da_a_step(-1, +3, 1))["data"];
        $alterazioni_polsi[3][39]["inviare"]=($this->da_a_step(-10, +30, 10))["data"];

        $alterazioni_polsi[3][40]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[3][40]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[3][41]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[3][41]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[3][42]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[3][42]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[3][43]["visualizzare"]=($this->da_a_step(-2, +2, 1))["data"];
        $alterazioni_polsi[3][43]["inviare"]=($this->da_a_step(-20, +20, 10))["data"];

        $alterazioni_polsi[3][44]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[3][44]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[3][45]["visualizzare"]=($this->da_a_step(-3, +1, 1))["data"];
        $alterazioni_polsi[3][45]["inviare"]=($this->da_a_step(-30, +10, 10))["data"];

        $alterazioni_polsi[3][46]["visualizzare"]=array(-4);
        $alterazioni_polsi[3][46]["inviare"]=array(-40);

        $alterazioni_polsi[3][47]["visualizzare"]=array(-4);
        $alterazioni_polsi[3][47]["inviare"]=array(-40);

        $alterazioni_polsi[3][48]["visualizzare"]=array(-4);
        $alterazioni_polsi[3][48]["inviare"]=array(-40);

        $alterazioni_polsi[3][49]["visualizzare"]=array(-4);
        $alterazioni_polsi[3][49]["inviare"]=array(-40);

        $alterazioni_polsi[3][50]["visualizzare"]=array(-4);
        $alterazioni_polsi[3][50]["inviare"]=array(-40);

        $alterazione = $alterazioni_polsi[$params["product_id"]][$params["size"]];

        if(isset($alterazione)){

            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]=$alterazione;
        }

        return $end_res;


    }



    public function save_lettera_di_vettura($ordine=null, $pdf_string=null){
        $end_res = (array) new EndRes(0, "Errore salvataggio lettera di vettura");
        if($ordine== null || $pdf_string==null){
            $end_res["error"]="Parametri mancanti";
            return $end_res;
        }

        $pdf_string=base64_decode($pdf_string);
        $now = new DateTime();
        $filename= "lettera_vettura_ordine_".$ordine."_".$now->format("Ymd_His").".pdf";
        $filepathname=$this->pdf_path.$filename;
        Dbg::d("filepathname1".$filepathname,"-",1);
        if(file_put_contents($filepathname,$pdf_string)){
            $filename = "lettera_vettura_ordine_".$ordine.".pdf";
            $filepathname=$this->pdf_path.$filename;
            if(file_exists($filepathname)){
                if (!unlink($filepathname)) {
                    $end_res["success"]="Il file precedente non &egrave; stato rimosso";
                }
            }
            Dbg::d("filepathname2".$filepathname,"-",1);
            if(file_put_contents($filepathname,$pdf_string)){
                $end_res["success"]=1;
                $end_res["error"]="";
                $end_res["data"]["file"]=$this->pdf_ext_path.$filename;
            }
        }

        return $end_res;
    }



    public function get_barcode_by_ordine($ordine_id)
    {

        $ordine_id=1;
        $end_res = (array)new EndRes(1, '');
        $dati_ordine = $this->get_dati_ordine($ordine_id);

        $barcodes_separati= array();
        $barcodes_uniti = array();

        foreach ($dati_ordine["order_product"] as $key_ord_prod => $val_ord_prod){



            $barcodes_separati[$key_ord_prod]["barcode_prezzo"] = $val_ord_prod["barcode_prezzo"];
            $barcodes_uniti[] = $val_ord_prod["barcode_prezzo"];

            if(isset($val_ord_prod["ricami"]["RS"]["barcode"])){
                $barcodes_separati[$key_ord_prod]["barcode_ricamo_simbolo"]=$val_ord_prod["ricami"]["RS"]["barcode"];
                $barcodes_uniti[] = $val_ord_prod["ricami"]["RS"]["barcode"];
            }

            if(isset($val_ord_prod["ricami"]["RT"]["barcode"])){
                $barcodes_separati[$key_ord_prod]["barcode_ricamo_simbolo"]=$val_ord_prod["ricami"]["RT"]["barcode"];
                $barcodes_uniti[] = $val_ord_prod["ricami"]["RT"]["barcode"];
            }

            if(isset($val_ord_prod["bottoni"])){
                $barcodes_separati[$key_ord_prod]["barcode_bottoni"]=$val_ord_prod["bottoni"]["BM"]["barcode"];
            }

        }

        $end_res["data"]["barcodes"]["separati"] = $barcodes_separati;
        $end_res["data"]["barcodes"]["uniti"] = $barcodes_uniti;
        return $end_res;

    }



    public function calc_prezzo_by_ordine($ordine_id){

        $end_res = (array)new EndRes(0, 'No prezzo');

        $barcodes= $this->get_barcode_by_ordine($ordine_id);
        $barcodes = $barcodes["data"]["barcodes"]["uniti"];

        $sql="SELECT
                SUM(COALESCE(VEN.LD0ML2, 0)) AS SUM_PREZZO_VEN
                FROM AZGRPCAMI.MTBCPF
                LEFT OUTER JOIN AZGRPCAMI.MMA1PF ON (PARMTB = NUMMM1)
                LEFT OUTER JOIN AZGRPCAMI.MLI2PF AS VEN ON NUMMM1 = VEN.PARML2
                WHERE VEN.TREML2=''
                AND VEN.CODML2='01'
                AND VEN.CDIML2='EURO'
                AND VEN.TCLML2=''
                AND VEN.CLIML2=0
                AND VEN.DINML2=0
                AND ALTMTB IN ('".implode("','",$barcodes)."')";
            $res = $this->as400->query($sql);

            if($res){
                $prezzo= $res[0]->SUM_PREZZO_VEN;
                $end_res["success"]=1;
                $end_res["error"]="";
                $end_res["data"]["prezzo"]=$prezzo; ;
            }


        return $end_res;

    }


    public function check_alterations(){

        $alterazioni = array();
        $alterazioni[3]= 13;
        $alterazioni[4]= 0;

        Dbg::d("check_alterations","---",1);

        //davanti dietro
        $end_res = (array)new EndRes(0, 'Errore check alterazioni');
        if( abs($alterazioni[3] - $alterazioni[4]) <= 12  ){

            $end_res["success"]=1;
            $end_res["error"]="";

        }else{
            $end_res["success"]=0;
            $end_res["error"]="Verificare Differenza tra lunghezza davanti e dietro";

        }

        Dbg::d("check_alterations end res",$end_res,1);
        return  $end_res;

    }

    /**
     * Calcola il valore dello scollo nelle alterazioni.
     * @param  int $collo_size
     * @param  int $size
     * @return int             Valore in cm
     */
    public function calc_scollo_alterations($collo_size = NULL, $size = NULL){
        $end_res= (array)new EndRes(0,"Verificare i dati di input");
        if($collo_size == NULL || $size == NULL){
            return $end_res;
        }

        $scollo = $collo_size - $size;
        $end_res["success"] = 1;
        $end_res["error"] = "";
        $end_res["data"] = $scollo;
        $end_res["data"] = $scollo;

        return $end_res;
    }




    public function pre_save(){


        /*
        Dbg::d("pre save","----",1);
        $scollo_alterations= $this->calc_scollo_alterations(40, 37);


        Dbg::d("scollo_alterations",$scollo_alterations,1);

         $barcode_univoco = $this->calc_barcode_univoco();

         Dbg::d("calc_barcode_univoco",$barcode_univoco,1);

         Dbg::d("barcode",$this->calc_barcode_prezzo(),1);
        */

    }



    public function calc_barcode_prezzo(){

        $end_res = (array)new EndRes(0, 'Barcode non trovato');
        $sql="SELECT barcode FROM product_barcode LIMIT 1";
        $res= $this->db->query($sql);
        if($res){
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]["barcode"]=$res[0]->barcode;
        }
        return $end_res;
    }


    public function calc_barcode_univoco(){

        Dbg::d("calc_barcode_univoco", "init",1);
        $sql="DELETE from barcode_prodotti_univoco;
              INSERT INTO barcode_prodotti_univoco (id) VALUES (NULL);";
        $res= $this->db->query($sql);
        if($res=="none"){
            $sql="SELECT LAST_INSERT_ID() as last_insert_id;";
            $res= $this->db->query($sql);
            if($res){
                $last_id= $res[0]->last_insert_id;
                $end_res["success"]=1;
                $end_res["error"]="";
                $end_res["data"]["last_insert_id"]=$last_id;

            }
        }
        return $end_res;
    }
    /**
     * Imposta il prodotto dell'ordine nello stato da cucire, fornito il suo barcode univoco.
     * Il prodotto deve avere lo stato di inviato = 1
     * In questo stato il prodotto è stato ritagliato ed è pronto per essere spedito dal fasonista
     * @return array
     */
    public function save_prodotto_da_cucire(){
        $params = $this->req->get_request_data("val_",4,1);
        $end_res = (array)new EndRes(0, 'Salvataggio non effettuato');
        $sql="SELECT * FROM  order_products WHERE inviato=1 AND  barcode_univoco='".$params["barcode"]."';";
        $res= $this->db->query($sql);
        if($res){
            $sql= "UPDATE order_products SET da_cucire=1 WHERE barcode_univoco='".$params["barcode"]."';";
            $this->db->query($sql);
            if($this->db->getRowCount()>0){
                $end_res["success"]=1;
                $end_res["error"]="";
            }
        }else{
            $end_res["error"]="Il prodotto non &egrave stato terminato dalla macchina";
        }
        return $end_res;
    }
    /**
     * Imposta il prodotto dell'ordine nello stato di completato, ovvero è ritornato dal fasonista il prodotto cucito*
     * Il prodotto deve avere lo stato di inviato = 1 e da_cucire = 1
     * @return array
     */
    public function save_prodotto_completato(){
        $params = $this->req->get_request_data("val_",4,1);
        $end_res = (array)new EndRes(0, 'Salvataggio non effettuato');
        $sql="SELECT * FROM  order_products WHERE inviato=1 AND da_cucire=1 AND  barcode_univoco='".$params["barcode"]."';";
        $res=  $this->db->query($sql);
        if($res){
            $sql= "UPDATE order_products SET completato=1 WHERE barcode_univoco='".$params["barcode"]."';";
            $this->db->query($sql);
            if($this->db->getRowCount()>0){
                 $order_id = $this->get_ordine_by_barcode($params["barcode"]);
                 $order_id = $order_id["data"]["order_id"];
                 Dbg::d("a1","----",1);
                 $end_res_ordine_completato=$this->check_ordine_completato($order_id);
                 Dbg::d("end_res_ordine_completato",$end_res_ordine_completato,1);
                 if($end_res_ordine_completato["success"]==1){
                     $order_controller = new OrderController();
                     $order_controller->setOrderReadyForShipment($order_id);
                     $dhl_controller = new DhlController($order_id);
                     $status = $dhl_controller->createOrderPackages();
                     if(!$status){
                         return (array) new EndRes(0, "Errore creazione di un pacco dhl");
                     }
                     $end_res = $this->do_pickup_request($order_id);
                     if($end_res["success"] == 1){
                         Dbg::d("a2","----",1);
                        $end_res = $this->do_shipment_request($order_id);
                        if($end_res["success"] == 1){
                            Dbg::d("a3","----",1);
                            $order_controller->setOrderSent($order_id);
                        }else{
                            return $end_res;
                        }
                    }else{
                        return $end_res;
                    }
                }
                $end_res["success"]=1;
                $end_res["error"]="";
            }
        }else{
            $end_res["success"]=0;
            $end_res["error"]="Il prodotto non è stato segnato tra quelli cui il completamento &egrave; possibile";
        }

        return $end_res;
    }


    public function get_ordine_by_barcode($barcode){

        $end_res = (array)new EndRes(0, 'Ordine non trovato');
        $sql="SELECT order_id
                FROM order_products
                WHERE barcode_univoco = '".$barcode."'";

        $res= $this->db->query($sql);
        if($res){
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]["order_id"]= $res[0]->order_id;

        }
        return $end_res;



    }


    public function check_ordine_completato($order_id){


        $end_res = (array)new EndRes(1, '');


        $sql="SELECT * FROM order_products WHERE order_id IN (".$order_id.") AND completato=0 AND da_cucire=0";
        $res= $this->db->query($sql);

        if($res){
            $end_res["success"]=0;
            $end_res["error"]="Ordine non completato";


        }

        return $end_res;

    }


    public function get_barcode(){

        //Dbg::d("get barcode","barcode",1);

        //============================================================+
        // File name   : example_027.php
        // Begin       : 2008-03-04
        // Last Update : 2013-05-14
        //
        // Description : Example 027 for TCPDF class
        //               1D Barcodes
        //
        // Author: Nicola Asuni
        //
        // (c) Copyright:
        //               Nicola Asuni
        //               Tecnick.com LTD
        //               www.tecnick.com
        //               info@tecnick.com
        //============================================================+

        /**
         * Creates an example PDF TEST document using TCPDF
         * @package com.tecnick.tcpdf
         * @abstract TCPDF - Example: 1D Barcodes.
         * @author Nicola Asuni
         * @since 2008-03-04
         */

// Include the main TCPDF library (search for installation path).
// create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 027');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 027', PDF_HEADER_STRING);

// set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);


// set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
        /*if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }*/

// ---------------------------------------------------------

// set a barcode on the page footer
        $pdf->setBarcode(date('Y-m-d H:i:s'));

// set font
        $pdf->SetFont('helvetica', '', 11);

// add a page
        $pdf->AddPage();

// print a message
        $txt = "You can also export 1D barcodes in other formats (PNG, SVG, HTML). Check the examples inside the barcodes directory.\n";
        $pdf->MultiCell(70, 50, $txt, 0, 'J', false, 1, 125, 30, true, 0, false, true, 0, 'T', false);
        $pdf->SetY(30);

// -----------------------------------------------------------------------------

        $pdf->SetFont('helvetica', '', 10);

// define barcode style
        $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => true,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );

// PRINT VARIOUS 1D BARCODES



// EAN 13
        $pdf->Cell(0, 0, 'EAN 13', 0, 1);
        $pdf->write1DBarcode('1234567890128', 'EAN13', '', '', '', 18, 0.4, $style, 'N');

        $pdf->Ln();

// ---------------------------------------------------------

//Close and output PDF document
        $pdf->Output('example_027.pdf', 'I');


    }



    public function get_order_n_items($order_id){

        $end_res = (array)new EndRes(0, 'Ordine non trovato');

        $sql = "SELECT count(*) as counter FROM order_products WHERE order_id=".$order_id.";";
        $res=$this->db->query($sql);
        if($res){
            $res = $res[0]->counter;
            $end_res["success"]=1;
            $end_res["error"]="";
        }

        return $res;

    }



    public function get_dati_cliente_by_ordine($order_id){

        $end_res = (array)new EndRes(0, 'Cliente non trovato');

        $sql="SELECT
                    clienti.*,
                    orders.id as order_id
                FROM
                   clienti,
                   orders
                WHERE
                    orders.cliente_id = clienti.id AND
                    orders.id = ".$order_id.";";


        $res=$this->db->query($sql);
        if($res){
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"] = (array) $res[0];
        }

        return $end_res;

    }


    public function get_dati_order_products($order_id){

        $end_res = (array)new EndRes(0, 'Ordine non trovato');

        $sql = "SELECT * FROM order_products
                WHERE order_id=".$order_id."
                ORDER BY order_products.sequenceNumber;";

        $res=$this->db->query($sql);
        if($res){

            $data= array();
            foreach ($res as $row){

                $data[] = (array) $row;
            }

            Dbg::d("dataaaaaa", $data,1);

            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"] = $data;
        }

        return $end_res;

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


    function startsWith ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }





    public function import_update_materials()
    {

        $end_res = (array)new EndRes(0, "materiali non aggiornati");


        $pathfile = "/var/www/html/lectra/stage/import/csv/2021.04.27/materiali_details_2021.04.27.csv";
        $content = file_get_contents($pathfile);





        $lines = explode("\n", $content); // this is your array of words


        $sql = "INSERT INTO materiali (reference, article_code, bottone_poliestere, bottone_madreperla, tele, etichette_lavaggio ) VALUES";

        foreach ($lines as $line) {


            if ($this->startsWith($line, ",ARTICLE CODE")) {
                continue;
            }
            if ($this->startsWith($line, ",,")) {
                continue;
            }


            if ($line == "") {
                continue;
            }


            Dbg::d("line", $line, 1);

            $data = explode(",", trim($line));

            Dbg::d("data", $data, 1);



            $article_code = trim($data[1]);
            Dbg::d("article_cod", $article_code, 1);


            $reference = trim($data[2]);
            Dbg::d("reference", $reference, 1);



            $bottone_poliestere = $this->get_colore_dettaglio_id_by_string(trim($data[4]));
            Dbg::d("bottone_poliestere1 ", trim($data[4]), 1);
            Dbg::d("bottone_poliestere1 ", $bottone_poliestere, 1);



            $bottone_madreperla = $this->get_colore_dettaglio_id_by_string(trim($data[5]));
            Dbg::d("bottone_madreperla1 ", trim($data[5]), 1);
            Dbg::d("bottone_madreperla2 ", $bottone_madreperla, 1);


            $tele = $this->get_colore_dettaglio_id_by_string(trim($data[6]));
            Dbg::d("tele1", trim($data[6]), 1);
            Dbg::d("tele2", $tele, 1);


            $etichette_lavaggio = $this->get_etichetta_dettaglio_id_by_string(trim($data[7]));

            Dbg::d("etichette_lavaggio", $etichette_lavaggio, 1);

            $sql .= "('".$reference."', '".$article_code. "', ".$bottone_poliestere.", ".$bottone_madreperla.", ".$tele . ", ".$etichette_lavaggio."), ";


        }


        $sql = substr($sql, 0,-2);
        $sql.= " ON DUPLICATE KEY UPDATE article_code = VALUES(article_code), bottone_madreperla = VALUES(bottone_madreperla), bottone_poliestere = VALUES(bottone_poliestere) , tele = VALUES(tele) , etichette_lavaggio = VALUES(etichette_lavaggio);";


        Dbg::d("sql",$sql,1);


        /*

            //check

              SELECT
                materiali.id,
                article_code,
                reference,
                color,
                nature,
                composition,
                motif_type,
                bottone_madreperla,
                bottone_madreperla_dettagli.label AS bottone_madreperla_label,
                bottone_poliestere,
                bottone_poliestere_dettagli.label AS bottone_poliestere_label,
                tele,
                tela_dettagli.label AS tele_label,
                etichette_lavaggi.label AS etichette_lavaggio_label
            FROM
                lectra_stage.materiali
                    LEFT JOIN
                colori_dettagli AS bottone_poliestere_dettagli ON bottone_poliestere = bottone_poliestere_dettagli.id
                    LEFT JOIN
                colori_dettagli AS bottone_madreperla_dettagli ON bottone_madreperla = bottone_madreperla_dettagli.id
                    LEFT JOIN
                colori_dettagli AS tela_dettagli ON tele = tela_dettagli.id
                    LEFT JOIN
                etichette_lavaggi AS etichette_lavaggi ON etichette_lavaggio = etichette_lavaggi.id
            ORDER BY reference ASC


         */


        /*
        $res= $this->db->query($sql);
        if($this->db->getRowCount()>0) {
            $end_res["success"] = 1;
            $end_res["error"] = "";
        }

        */




        return $end_res;





    }


    function get_etichetta_dettaglio_id_by_string($string){

        $etichetta_id=NULL;

        switch (strval($string)){
            case "100% COTTON (CO)";
                $etichetta_id=1;
                break;
                break;
            case "100% LINEN (LI)":
                $etichetta_id=2;
                break;

        }

        return $etichetta_id;

    }

    function get_colore_dettaglio_id_by_string($string){

            $colore_id=NULL;

            switch (strval($string)){
                case "0";
                    $colore_id=1;
                    break;
                case "BIANCO":
                    $colore_id=2;
                    break;
                case "GRIGIO":
                    $colore_id=3;
                    break;
                case "NERO":
                    $colore_id=4;
                    break;
                case "BLU":
                    $colore_id=5;
            }


            return $colore_id;

    }
    /**
     * Importa i batch e roll dai file csv all'intero di una cartella
     * Struttura colonne del file:
     *    0 - codice camicia (ANDROMEDA, MAIA, ...)
     *    1 - sequence number. Univoco all'interno del tessuto
     *    2 - numerico articole code
     *    3 - colore
     *    4 - fornitore
     *    5 - supplier roll number (inutilizzato)
     *    6 - metri rotolo (in metri)
     *    7 - larghezza minima tagliabile (in cm)
     * Batch reference: col[2] + "_" + col[4]
     * Roll reference: col[0] + "_" + col[1] + "_" + col[2] + "_" + col[3]
     * @return void
     */
    public function import_rolls(){
        // return;
        $end_res = (array) new EndRes(0,"rotoli non importati");
        $path = "/var/www/html/lectra/stage/import/csv/2021.07.27";
        $cvs_files = $a = scandir($path);
        $delimiter = ",";
        Dbg::d("cvs_files",$cvs_files,1);
        $batches=array();
        $rolls = array();
        foreach ($cvs_files as $file){
            if($file == "." || $file == ".."){
                continue;
            }
            Dbg::d("file",$file,1);
            $content = file_get_contents($path."/".$file);
            Dbg::d("content",$content,1);
            $lines = explode("\n", $content); // this is your array of words
            foreach($lines as $line) {
                if($this->startsWith($line, "ARTICLE CODE")){
                    continue;
                }
                if($this->startsWith($line, $delimiter)){
                    continue;
                }
                if($this->startsWith($line, "TOTAL")){
                    continue;
                }
                if($line == ""){
                    continue;
                }
                Dbg::d("line",$line,1);
                $data =  explode($delimiter, trim($line));
                Dbg::d("data",$data,1);
                $batch = array(
                    "reference"=>str_replace(' ', '_', trim($data[2])."_".trim($data[4])),
                    "materials_reference"=>str_replace(' ', '_',trim($data[2])),
                    "supplier"=>str_replace(' ', '_',trim($data[4]))
                );
                Dbg::d("batch",$batch,1);
                if(!in_array($batch,$batches)){
                    $batches[]= $batch;
                }else{
                    Dbg::d("batch exist!!",$batch,1);
                }
                $roll = array(
                    "reference" => str_replace(' ', '_',trim($data[0])."_".str_pad(trim($data[1]), 2, "0", STR_PAD_LEFT)."_".trim($data[2])."_".trim($data[3])),
                    "batch_reference" => str_replace(' ', '_',trim($data[2])."_".trim($data[4])),
                    "sequence_number" => str_replace(' ', '_',trim($data[1])),
                    "length" => str_replace(' ', '_',str_replace(",",".",trim($data[6]))),
                    "width" => str_replace(' ', '_',str_replace(",",".",trim($data[7]/100)))
                );
                if(!in_array($roll,$rolls)){
                    $rolls[]= $roll;
                }
            }
            //break;
        }
        Dbg::d("batches", $batches,1);
        Dbg::d("rolls", $rolls,1);
        $sql = "INSERT INTO batch(reference,materials_reference,supplier) VALUES ";
        foreach ($batches as $batch){
            $sql .= "(";
            $sql .= "'".$batch["reference"]."',";
            $sql .= "'".$batch["materials_reference"]."',";
            $sql .= "'".$batch["supplier"]."'";
            $sql .= "), ";
        }
        $sql = substr($sql,0,-2);
        $sql .= " ON DUPLICATE KEY UPDATE materials_reference = VALUES(materials_reference), supplier = VALUES(supplier);";
        Dbg::d("sql insert batch", $sql,1);
        $res=$this->db->query($sql);
         if($res=="none"){
            $sql = "INSERT INTO roll (reference, batch_reference, sequence_number, length,  width) VALUES";
            foreach ($rolls as $roll){
                $sql .= "(";
                $sql .= "'".$roll["reference"]."', ";
                $sql .= "'".$roll["batch_reference"]."', ";
                $sql .= "".$roll["sequence_number"].", ";
                $sql .= "".$roll["length"].", ";
                $sql .= "".$roll["width"]."";
                $sql .= "), ";
            }
            Dbg::d("sql insert",$sql,1);
            $sql = substr($sql,0,-2);
            $sql .= " ON DUPLICATE KEY UPDATE batch_reference = VALUES(batch_reference), length = VALUES(length), width = VALUES(width);";
            $res=$this->db->query($sql);
            if($res == "none"){
                $end_res["success"]=1;
                $end_res["error"]="";
            }
            Dbg::d("sql insert rolls",$sql,1);
         }
         //Dbg::d("ress",$res,1);
         Dbg::d("thisdb",$this->db,1);
    }
    /**
     * Effettua una chiamata per prenotare un corriere di DHL sull'ordine specificato
     * @param  int $order_id
     * @return array
     */
    public function do_pickup_request($order_id){
        $dhl_controller = new DhlController($order_id);
        $order = Order::find($order_id);
        if(!is_null($dhl_controller) && !is_null($order)){
            try{
                $ritiro = new DateTime();
                $ritiro->setTimezone(new DateTimeZone("Europe/Rome"));
                $ritiro->add(new DateInterval("P1D"));
                $path = "https://wsbexpress.dhl.com/rest/sndpt/PickupRequest";
                $product_data = JSONReader::readContentData("req_json/PickupRequest.json");
                $product_data["PickUpRequest"]["PickUpShipment"]["PickupTimestamp"] = $ritiro->format(DhlController::TIMESTAMP_RITIRO_FORMAT);
                $product_data["PickUpRequest"]["PickUpShipment"]["InternationalDetail"]["Commodities"]["NumberOfPieces"] = sizeof(ProductOrder::where("order_id", $order_id));
                $product_data["PickUpRequest"]["PickUpShipment"]["Ship"]["Recipient"] = $dhl_controller->getContactDataFromCliente(Cliente::find($order->cliente_id));
                $product_data["PickUpRequest"]["PickUpShipment"]["Packages"] = $dhl_controller->getPackagesData();
                $headers = array();
                $headers[] = "Authorization: Basic ".base64_encode("fenciaspaIT:A$7tJ#5gA@2s");
                $headers[] = "Content-Type: application/json";
                $this->api_caller->resetHeader();
                $this->api_caller->setHeader($headers);
                $res_call = $this->api_caller->callAPIComplete("POST", $path, json_encode($product_data));
                if(isset($res_call["body"]) && isset($res_call["info"]["http_code"]) && $res_call["info"]["http_code"] == 200){
                    $res = json_decode($res_call["body"], true);
                    if(isset($res["PickUpResponse"]["DispatchConfirmationNumber"])){
                        $order->dhl_confirmation_number = $res["PickUpResponse"]["DispatchConfirmationNumber"];
                        $order_id = $order->save();
                        $end_res = ($order_id >= 0)
                            ? new EndRes(1, "Prenotazione ordine $order_id avvenuta con successo")
                            : new EndRes(0, "Prenotazione ordine $order_id avvenuta. Errore salvataggio confirmation number");
                    }else{
                        $end_res = new EndRes(0, "DispatchConfirmationNumber non presente nella risposta");
                    }
                }else{
                    $end_res = new EndRes(0, "Errore chiamata pickup_request. stato: ".$res_call["info"]["http_code"], json_decode($res_call["body"], true));
                }
            }catch(Exception $e){
                $end_res = new EndRes(0, $e->getMessage());
                throw $e;
            }
        }else{
            $end_res = new EndRes(0, "Ordine $order_id inesitente");
        }
        return (array) $end_res;
    }

    public function do_shipment_request($order_id=NULL){

        $end_res = new EndRes(0, "Errore shipment_request");
        Dbg::d("do_shipment_request","------------------",1);


        if($this->env=="stage" && !isset($order_id)){
            $order_id=1;
        }


        $n_items= $this->get_order_n_items($order_id);

        Dbg::d("nitems",$n_items,1);

        $dati_cliente= $this->get_dati_cliente_by_ordine($order_id);
        $dati_cliente = $dati_cliente["data"];

        Dbg::d("dati_cliente",$dati_cliente,1);

        $dati_order_products= $this->get_dati_order_products($order_id);
        $dati_order_products= $dati_order_products["data"];


        $end_res_prezzo = $this->calc_prezzo_by_ordine($order_id);
        if($end_res_prezzo["success"] != 1){
            return $end_res_prezzo;
        }
        $prezzo = $end_res_prezzo["data"]["prezzo"];

        Dbg::d("dati_order_products",$dati_order_products,1);

        $data= array();
        $data["ShipmentRequest"]= array();
        $data_spedizione = new DateTime();
        $data_spedizione->setTimezone(new DateTimeZone("Europe/Rome"));
        $data_spedizione->add(new DateInterval("P1D"));
        $data["ShipmentRequest"]["RequestedShipment"]["ShipTimestamp"] = $data_spedizione->format("Y-m-d\TH:i:s\G\M\TP");
        $data["ShipmentRequest"]["RequestedShipment"]["PaymentInfo"] = "DAP";

        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["DropOffType"] = "REGULAR_PICKUP";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["ServiceType"] = "N";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["Account"] = "106780707";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["Currency"] = "EUR";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["UnitOfMeasurement"] = "SI";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["LabelType"] = "PDF";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["LabelTemplate"] = "ECOM26_A6_002";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["PaperlessTradeEnabled"] = 0;

        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["Billing"]["ShipperAccountNumber"] = "106780707";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["Billing"]["ShippingPaymentType"] = "R";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["Billing"]["BillingAccountNumber"] = "106780707";
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["ShipmentReferences"]["ShipmentReference"] = array();
        $data["ShipmentRequest"]["RequestedShipment"]["ShipmentInfo"]["ShipmentReferences"]["ShipmentReference"][]  =  array(
                                                                                                                            "ShipmentReference"  => $order_id."_1",
                                                                                                                            "ShipmentReferenceType"  => "CU"
                                                                                                                        );

        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Contact"]["PersonName"] = "Mario Mina";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Contact"]["CompanyName"] = "Fenicia Spa";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Contact"]["PhoneNumber"] = "3489214245"; //prima di andare in prod mettere quello di mario mina
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Contact"]["EmailAddress"] =  "ced@feniciaspa.it";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Contact"]["MobilePhoneNumber"] = "3489214245"; //prima di andare in prod mettere quello di mario mina


        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Address"]["StreetLines"] =  "Via Mecenate 84 interno 23";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Address"]["City"] =  "Milano";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Address"]["PostalCode"] =  "20138";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Shipper"]["Address"]["CountryCode"] =  "IT";

        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Contact"]["PersonName"] =  $dati_cliente["Nome"]." ". $dati_cliente["Cognome"];
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Contact"]["CompanyName"] =  "-";
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Contact"]["PhoneNumber"] =  $dati_cliente["Telefono"];
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Contact"]["EmailAddress"] = $dati_cliente["Email"];
        $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Contact"]["MobilePhoneNumber"] =  $dati_cliente["Telefono"];


        if($dati_cliente["RitiroInNegozio"]!=1){

            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StreetLines"] =  $dati_cliente["Via"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["City"] = $dati_cliente["Localita"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StateOrProvinceCode"] =  $dati_cliente["Provincia"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["PostalCode"] =  $dati_cliente["CAP"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["CountryCode"] =  "IT";

        }else{

            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StreetLines"] = "c/o Camicissima: ".$dati_cliente["DescrizioneNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StreetLines2"] = $dati_cliente["IndirizzoEstensioneNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StreetLines3"] = $dati_cliente["IndirizzoNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["City"] = $dati_cliente["LocalitaNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["StateOrProvinceCode"] =  $dati_cliente["ProvinciaNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["PostalCode"] =  $dati_cliente["CAPNeg"];
            $data["ShipmentRequest"]["RequestedShipment"]["Ship"]["Recipient"]["Address"]["CountryCode"] =  "IT";

        }

        $dhl_controller = new DhlController($order_id);
        if($dhl_controller){
           $data["ShipmentRequest"]["RequestedShipment"]["Packages"] = $dhl_controller->getPackagesData();
        }

        $data["ShipmentRequest"]["RequestedShipment"]["InternationalDetail"]["Content"] = "DOCUMENTS";
        $data["ShipmentRequest"]["RequestedShipment"]["InternationalDetail"]["Commodities"]["CustomsValue"] = $prezzo;
        $data["ShipmentRequest"]["RequestedShipment"]["InternationalDetail"]["Commodities"]["Description"] = "order ".$order_id;

        $params = json_encode($data);
        $headers = array();
        $headers[] = "Authorization: Basic ".base64_encode("fenciaspaIT:A$7tJ#5gA@2s");
        $headers[] = "Content-Type: application/json";
        $path = "https://wsbexpress.dhl.com/rest/sndpt/ShipmentRequest";

            Dbg::d("headers",$headers,1);
        Dbg::d("path",$path,1);
        Dbg::d("params",$params,1);

        $this->api_caller->resetHeader();
        $this->api_caller->setHeader($headers);
        $res_call = $this->api_caller->callAPIComplete("POST", $path, $params);
        if(isset($res_call["body"]) && isset($res_call["info"]["http_code"]) && $res_call["info"]["http_code"] == 200){
            $res = json_decode($res_call["body"], true);
            if(isset($res["ShipmentResponse"]["PackagesResult"]["PackageResult"])){
                foreach ($res["ShipmentResponse"]["PackagesResult"]["PackageResult"] as $package_res){
                    if(isset($package_res["@number"]) && isset($package_res["TrackingNumber"])){
                        $args = array(
                            "sequence_number = ".$package_res["@number"],
                            "order_id = $order_id"
                        );
                        $dhl_packages = DhlPackageOrder::whereRaw(implode(" AND ", $args));
                        if(isset($dhl_packages[0])){
                            $dhl_pack = $dhl_packages[0];
                            $dhl_pack->tracking_number = $package_res["TrackingNumber"];
                            $dhl_pack->save();
                        }
                    }
                }
                if(isset($res["ShipmentResponse"]["LabelImage"]) && isset($res["ShipmentResponse"]["LabelImage"][0]) && isset($res["ShipmentResponse"]["LabelImage"][0]["GraphicImage"])){
                    $end_res_lettera_vettura = $this->save_lettera_di_vettura($order_id, $res["ShipmentResponse"]["LabelImage"][0]["GraphicImage"]);
                    $end_res = ($end_res_lettera_vettura["success"] == 1)
                        ? new EndRes(1, "Creazione spedizione ordine $order_id avvenuta con successo", $end_res_lettera_vettura["data"])
                        : new EndRes(0, "Errore creazione lettera di vettura. ".$end_res_lettera_vettura["error"]);
                }else{
                    $end_res = new EndRes(0, "Lettera di vettura non presente nella risposta", json_decode($res_call["body"], true));
                }

            }else{
                $end_res = new EndRes(0, "PackageResult non presente nella risposta", json_decode($res_call["body"], true));
            }
        }else{
            $end_res = new EndRes(0, "Errore chiamata shipment_request. stato: ".$res_call["info"]["http_code"], json_decode($res_call["body"], true));
        }
        // sleep(2);
        return (array) $end_res;
    }



    public function get_alterations(){

        $end_res = (array)new EndRes(0, 'Errore alterazioni');

        $params = $this->req->get_request_data("val_",4,1);
        $params["field"]="alteration";
        $end_res_alt = $this->get_field_data($params);

        if($end_res_alt["success"]==1){

            foreach ($end_res_alt["data"] as $key_alter=>$val_alter){


                $da = $val_alter["MIN_SIZE"];
                $a = $val_alter["MAX_SIZE"];
                $step=$val_alter["STEP"];
                $end_res_valori_da_visualizzare = $this->da_a_step($da,$a,$step);


                if($end_res_valori_da_visualizzare["success"]==1){

                    $valori_da_visualizzare= $end_res_valori_da_visualizzare["data"];
                    $end_res["data"][$key_alter]["ID_ALTERAZIONE"]=$key_alter;
                    $end_res["data"][$key_alter]["DESCRIZIONE"]=$val_alter["DESCRIZIONE"];
                    $end_res["data"][$key_alter]["VALORI_DA_VISUALIZZARE_DA"]=$da;
                    $end_res["data"][$key_alter]["VALORI_DA_VISUALIZZARE_A"]=$a;
                    $end_res["data"][$key_alter]["VALORI_DA_VISUALIZZARE_STEP"]=$step;
                    $end_res["data"][$key_alter]["VALORI_DA_VISUALIZZARE"] =$valori_da_visualizzare;

                    if($val_alter["VALORI_MM"]){
                        $da_mm = $da*10;
                        $a_mm = $a*10;
                        $step = $step*10;
                        $end_res_valori_millimetri = $this->da_a_step($da_mm,$a_mm,$step);

                        if($end_res_valori_millimetri["success"]==1){
                            $end_res["success"]=1;
                            $end_res["error"]="";
                            $valori_millimetri = $end_res_valori_millimetri["data"];
                            $end_res["data"][$key_alter]["VALORI_DA_INVIARE"] =$valori_millimetri;


                        }
                    }else{
                        $end_res["data"][$key_alter]["VALORI_DA_INVIARE"] =$valori_da_visualizzare;
                    }

                }



            }
        }

        return $end_res;

    }


    public function get_contrasto(){

        $end_res= (array)new EndRes(1,"");
        $params = $this->req->get_request_data("val_",4,1);
        if(!isset($params["component_id"])) {
            $end_res["success"]=0;
            $end_res["error"]="Parametro mancante";
            return $end_res;
        }


        $sql="SELECT * FROM component WHERE has_contrast=1 AND id=".$params["component_id"].";";

        Dbg::d("sql", $sql,1);

        $res=$this->db->query($sql);
        if(!$res){
            $end_res["success"]=0;
            $end_res["error"]="Contrasto non previsto per questo elemento";
            return $end_res;
        }


        if(!isset($params["component_id"])) {
            $end_res["success"]=0;
            $end_res["error"]="Parametro mancante";
        }


        $params["field"]="materiale";
        $data = $this->get_field_data($params);
        return $data;

    }


    public function get_data_negozio($cod_neg=NULL){
        $params["field"]='negozi';
        $params["id_neg"]= $cod_neg;
        $end_res = $this->get_field_data($params);
        return $end_res;

    }



    public function get_info_negozio($cod_neg){



        $end_res_data_neg = $this->get_data_negozio($cod_neg);

        Dbg::d("codneggg",$cod_neg,1);
        Dbg::d("end_res_data_negggggg 1",$end_res_data_neg,1);


        $data_neg_str="";
        if(isset($end_res_data_neg["data"][$cod_neg])){
            $data_neg= $end_res_data_neg["data"][$cod_neg];
            unset($data_neg["ID"]);

            if($end_res_data_neg["success"]==1){
                foreach ($data_neg as $key => $val){
                    if($val!=""){
                    //$data_neg_str.=  ucfirst(str_replace("_"," ",strtolower($key)))." ".$val.", ";
                        $data_neg_str.= $val.", ";
                    }
                }
                $data_neg_str = substr($data_neg_str, 0, -2);
            }
        }
        return $data_neg_str;
    }



    public function get_dati_ordine($id_ordine){




        $args=array();
        if(is_array($id_ordine)){
            $args[] = "orders.id IN('".implode("','",$id_ordine)."')";
        }else{
            $args[] = "orders.id=".$id_ordine."";
        }

        Dbg::d("args", $args,1);



        $sql="SELECT
                orders.id AS order_id,
                orders.stato,
                order_products.id AS order_products_id,
                order_product_components.component_id AS component_id,
                component_type.id as component_type_id,
                order_product_alterations.alteration_id AS alteration_id,
                order_products.product_id as product_id,
                order_products.barcode_prezzo as product_barcode_prezzo,
                order_products.barcode_univoco as product_barcode_univoco,

                bottoni.tipo as bottoni_tipo,
                bottoni.barcode  AS bottoni_barcode,

                etichette_lavaggio.id as etichette_lavaggio_id,
                etichette_lavaggio.label as etichette_lavaggio_label,

                ricami.id  AS ricami_id,

                ricami.tipo AS ricami_tipo,

                ricami.barcode  AS ricami_barcode,
                ricami.colore  AS ricami_colore_id,
                ricami_colore.label AS ricami_colore_label,

                ricami_posizione.id  AS ricami_posizione_id,
                ricami_posizione.label AS  ricami_posizione_label,

                ricami_stile_testo.id  AS ricami_stile_testo_id,
                ricami_stile_testo.label AS  ricami_stile_testo_label,

                ricami_simbolo.id  AS ricami_simbolo_id,
                ricami_simbolo.label AS  ricami_simbolo_label,

                ricami_ordine_simbolo.id  AS ricami_ordine_simbolo_id,
                ricami_ordine_simbolo.label AS  ricami_ordine_simbolo_label,

                ricami_posizione_simbolo.id  AS ricami_posizione_simbolo_id,
                ricami_posizione_simbolo.label AS  ricami_posizione_simbolo_label,

                ricami.testo  AS ricami_testo,

                clienti.CRMid AS cliente_CRMid,
                clienti.Nome AS nome_cliente,
                clienti.Cognome AS cognome_cliente,
                clienti.DataNascita AS data_nascita,
                clienti.Sesso AS sesso,
                clienti.PartitaIva AS p_iva,
                clienti.CodiceFiscale AS cf,
                clienti.Email AS email,
                clienti.Via AS via,
                clienti.Localita AS localita,
                clienti.Provincia AS provincia,
                clienti.CAP AS CAP,
                clienti.Nazione AS nazione,
                clienti.Telefono AS telefono,
                clienti.RitiroInNegozio AS ritiro_in_negozio,
                clienti.CodNeg AS cod_neg,
                clienti.NomeNeg AS nome_neg,
                clienti.DescrizioneNeg AS descrizione_neg,
                clienti.IndirizzoNeg AS indirizzo_neg,
                clienti.IndirizzoEstensioneNeg AS indirizzo_estensione_neg,
                clienti.LocalitaNeg AS localita_neg,
                clienti.ProvinciaNeg AS provincia_neg,
                clienti.CAPNeg AS CAP_neg,
                clienti.NazioneNeg AS nazione_neg,
                clienti.TelefonoNeg AS telefono_neg,
                clienti.PIVANeg AS piva_neg,
                factory,
                salesPoint,
                orderDate,
                deliveryDate,
                gender AS prod_gender,
                orders.comment AS order_comment,
                product.reference AS prod_reference,
                gender AS gender,
                order_products.comment AS prod_comment,
                grading AS prod_grading,
                quantity AS prod_quantity,
                component.qty AS comp_quantity,
                component_type.label AS comp_type_descrizione,
                component.descrizione AS comp_descrizione,
                alteration.label AS alteration_label,
                order_product_alterations.value AS alteration_value,
                materiali.id as materiale_id,
                CONCAT_WS(' ', materiali.nature, materiali.color) AS materiale_label,
                contrasto.id as contrasto_id,
                CONCAT_WS(' ', contrasto.nature, contrasto.color) AS contrasto_label,
                CONCAT(component_type.codice, component.codice) AS comp_reference
            FROM
                orders
                    LEFT JOIN
                clienti ON orders.cliente_id = clienti.id
                    LEFT JOIN
                order_products ON orders.id = order_products.order_id
                    LEFT JOIN
                order_product_components ON order_products.id = order_product_components.order_product_id
                    LEFT JOIN
                order_product_alterations ON order_products.id = order_product_alterations.order_product_id
                    LEFT JOIN
                    (SELECT * FROM order_product_dettagli WHERE tipo IN ('RS', 'RT')) as ricami ON order_products.id = ricami.order_product_id
                    LEFT JOIN
                    (SELECT * FROM order_product_dettagli WHERE tipo IN ('BM')) as bottoni ON order_products.id = bottoni.order_product_id
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('CO') ) as ricami_colore ON ricami.colore = ricami_colore.id AND ricami.colore IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('PC') ) as ricami_posizione ON ricami.posizione = ricami_posizione.id AND ricami.posizione IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('ST') ) as ricami_stile_testo ON ricami.stile_testo = ricami_stile_testo.id AND ricami.stile_testo IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('SI') ) as ricami_simbolo ON ricami.simbolo = ricami_simbolo.id AND ricami.simbolo IS NOT NULL
                     LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('OS') ) as ricami_ordine_simbolo ON ricami.ordine_simbolo = ricami_ordine_simbolo.id AND ricami.ordine_simbolo IS NOT NULL
                     LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('PS') ) as ricami_posizione_simbolo ON ricami.posizione = ricami_posizione_simbolo.id AND ricami.posizione IS NOT NULL
                    LEFT JOIN
                alteration ON order_product_alterations.alteration_id = alteration.id
                    LEFT JOIN
                product ON order_products.product_id = product.id
                    LEFT JOIN
                component ON order_product_components.component_id = component.id
                    LEFT JOIN
                component_type ON component.component_type_id = component_type.id
                    LEFT JOIN
                materiali ON order_product_components.material_id = materiali.id
                    LEFT JOIN
                materiali AS contrasto ON order_product_components.contrastReference = contrasto.id
                   LEFT JOIN
                etichette_lavaggio ON materiali.etichette_lavaggio=etichette_lavaggio.id
            WHERE ".implode(" AND ", $args)."
            ORDER BY order_products.sequenceNumber;";


        Dbg::d("sql",$sql,1);

        $data=array();

        $res=$this->db->query($sql);

        if($res){

            foreach ($res as $row){

                $data[$row->order_id]["order_id"]=$row->order_id;
                $data[$row->order_id]["stato"]=$row->stato;
                $data[$row->order_id]["order_products_id"]=$row->order_products_id;
                $data[$row->order_id]["factory"]=$row->factory;
                $data[$row->order_id]["sales_point"]=$row->salesPoint;
                $data[$row->order_id]["sales_point_info"]=$this->get_info_negozio($row->salesPoint);
                $data[$row->order_id]["order_date"]=$row->orderDate;
                $data[$row->order_id]["delivery_date"]=$row->deliveryDate;
                $data[$row->order_id]["order_comment"]=$row->order_comment;
                $data[$row->order_id]["cliente"]["CRMid"] = $row->cliente_CRMid;
                $data[$row->order_id]["cliente"]["nome"] = $row->nome_cliente;
                $data[$row->order_id]["cliente"]["cognome"] = $row->cognome_cliente;
                $data[$row->order_id]["cliente"]["data_nascita"] = $row->data_nascita;
                $data[$row->order_id]["cliente"]["sesso"] = $row->sesso;
                $data[$row->order_id]["cliente"]["p_iva"] = $row->p_iva;
                $data[$row->order_id]["cliente"]["cf"] = $row->cf;
                $data[$row->order_id]["cliente"]["email"] = $row->email;
                $data[$row->order_id]["cliente"]["via"] = $row->via;
                $data[$row->order_id]["cliente"]["localita"] = $row->localita;
                $data[$row->order_id]["cliente"]["provincia"] = $row->provincia;
                $data[$row->order_id]["cliente"]["CAP"] = $row->CAP;
                $data[$row->order_id]["cliente"]["nazione"] = $row->nazione;
                $data[$row->order_id]["cliente"]["telefono"] = $row->telefono_neg;
                $data[$row->order_id]["cliente"]["ritiro_in_negozio"] = $row->ritiro_in_negozio;

                if(  $data[$row->order_id]["cliente"]["ritiro_in_negozio"] ==1){
                    $data[$row->order_id]["cliente"]["cod_neg"] = $row->cod_neg;
                    $data[$row->order_id]["cliente"]["nome_neg"] = $row->nome_neg;
                    $data[$row->order_id]["cliente"]["indirizzo_neg"] = $row->indirizzo_neg;
                    $data[$row->order_id]["cliente"]["indirizzo_estensione_neg"] = $row->indirizzo_estensione_neg;
                    $data[$row->order_id]["cliente"]["localita_neg"] = $row->localita_neg;
                    $data[$row->order_id]["cliente"]["provincia_neg"] = $row->provincia_neg;
                    $data[$row->order_id]["cliente"]["CAP_neg"] = $row->CAP_neg;
                    $data[$row->order_id]["cliente"]["nazione_neg"] = $row->nazione_neg;
                    $data[$row->order_id]["cliente"]["telefono_neg"] = $row->telefono_neg;
                    $data[$row->order_id]["cliente"]["piva_neg"] = $row->piva_neg;
                }



                $data[$row->order_id]["order_product"][$row->order_products_id]["id"]=$row->order_products_id;
                $data[$row->order_id]["order_product"][$row->order_products_id]["barcode_prezzo"]=$row->product_barcode_prezzo;
                $data[$row->order_id]["order_product"][$row->order_products_id]["gender"]=$row->gender;
                $data[$row->order_id]["order_product"][$row->order_products_id]["quantity"]=$row->prod_quantity;
                $data[$row->order_id]["order_product"][$row->order_products_id]["grading"]=$row->prod_grading;
                $data[$row->order_id]["order_product"][$row->order_products_id]["reference"]=$row->prod_reference;

                if($row->component_id){
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["id"]=$row->component_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["reference"]=$row->comp_reference;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["quantity"]=$row->comp_quantity;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["comp_type"]["id"]=$row->component_type_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["comp_type"]["label"]=$row->comp_type_descrizione;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["comp_descrizione"]=$row->comp_descrizione;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["materiale"]["id"]=$row->materiale_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["materiale"]["label"]=$row->materiale_label;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["contrasto"]["id"]=$row->contrasto_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["components"][$row->component_type_id]["contrasto"]["label"]=$row->contrasto_label;


                    if(isset($row->bottoni_barcode)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["bottoni"][$row->bottoni_tipo]["tipo"] = $row->bottoni_tipo;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["bottoni"][$row->bottoni_tipo]["barcode"] = $row->bottoni_barcode;
                    }


                    if(isset( $row->ricami_tipo)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["tipo"]=$row->ricami_tipo;
                    }

                    if(isset( $row->ricami_barcode)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["barcode"]=$row->ricami_barcode;
                    }

                    if(isset($row->ricami_colore_id)){

                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["colore"]["id"] = $row->ricami_colore_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["colore"]["label"] = $row->ricami_colore_label;
                    }

                    if(isset($row->ricami_posizione_id)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione"]["id"] = $row->ricami_posizione_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione"]["label"] = $row->ricami_posizione_label;
                    }


                    if(isset($row->ricami_simbolo_id)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["simbolo"]["id"] = $row->ricami_simbolo_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["simbolo"]["label"] = $row->ricami_simbolo_label;
                    }


                    if(isset($row->ricami_testo)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["testo"] = $row->ricami_testo;
                    }

                    if(isset($row->ricami_stile_testo_id)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["stile_testo"]["id"] = $row->ricami_stile_testo_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["stile_testo"]["label"] = $row->ricami_stile_testo_label;
                    }

                    if(isset($row->ricami_ordine_simbolo_id)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["ordine_simbolo"]["id"] = $row->ricami_ordine_simbolo_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["ordine_simbolo"]["label"] = $row->ricami_ordine_simbolo_label;
                    }

                    if(isset($row->ricami_posizione_simbolo_id)){
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione_simbolo"]["id"] = $row->ricami_posizione_simbolo_id;
                        $data[$row->order_id]["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione_simbolo"]["label"] = $row->ricami_posizione_simbolo_label;
                    }

                }

                if($row->alteration_id){
                    $data[$row->order_id]["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["id"]=$row->alteration_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["label"]=$row->alteration_label;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["value"]=$row->alteration_value;

                }



                if(isset($row->etichette_lavaggio_label)){
                    $data[$row->order_id]["order_product"][$row->order_products_id]["materiali"]["etichette_lavaggio_label"]["id"] = $row->etichette_lavaggio_id;
                    $data[$row->order_id]["order_product"][$row->order_products_id]["materiali"]["etichette_lavaggio_label"]["label"] = $row->etichette_lavaggio_label;
                }


            }

        }



        if(!is_array($id_ordine)){
            $data =$data[$row->order_id];
        }


        return $data;
    }




    /*
    public function get_dati_ordine($id_ordine=NULL,$id_ordini=array()){

        Dbg::d("id_ordine",$id_ordine,1);
        $sql="SELECT
                orders.id AS order_id,
                order_products.id AS order_products_id,
                order_product_components.component_id AS component_id,
                component_type.id as component_type_id,
                order_product_alterations.alteration_id AS alteration_id,
                order_products.product_id as product_id,
                order_products.barcode_prezzo as product_barcode_prezzo,

                bottoni.tipo as bottoni_tipo,
                bottoni.barcode  AS bottoni_barcode,

                ricami.id  AS ricami_id,

                ricami.tipo AS ricami_tipo,

                ricami.barcode  AS ricami_barcode,

                ricami.colore  AS ricami_colore_id,
                ricami_colore.label AS ricami_colore_label,

                ricami_posizione.id  AS ricami_posizione_id,
                ricami_posizione.label AS  ricami_posizione_label,

                ricami_stile_testo.id  AS ricami_stile_testo_id,
                ricami_stile_testo.label AS  ricami_stile_testo_label,

                ricami_simbolo.id  AS ricami_simbolo_id,
                ricami_simbolo.label AS  ricami_simbolo_label,

                ricami_ordine_simbolo.id  AS ricami_ordine_simbolo_id,
                ricami_ordine_simbolo.label AS  ricami_ordine_simbolo_label,

                ricami_posizione_simbolo.id  AS ricami_posizione_simbolo_id,
                ricami_posizione_simbolo.label AS  ricami_posizione_simbolo_label,

                ricami.testo  AS ricami_testo,

                clienti.CRMid AS cliente_CRMid,
                clienti.Nome AS nome_cliente,
                clienti.Cognome AS cognome_cliente,
                clienti.DataNascita AS data_nascita,
                clienti.Sesso AS sesso,
                clienti.PartitaIva AS p_iva,
                clienti.CodiceFiscale AS cf,
                clienti.Email AS email,
                clienti.Via AS via,
                clienti.Localita AS localita,
                clienti.Provincia AS provincia,
                clienti.CAP AS CAP,
                clienti.Nazione AS nazione,
                clienti.Telefono AS telefono,
                clienti.RitiroInNegozio AS ritiro_in_negozio,
                clienti.CodNeg AS cod_neg,
                clienti.NomeNeg AS nome_neg,
                clienti.DescrizioneNeg AS descrizione_neg,
                clienti.IndirizzoNeg AS indirizzo_neg,
                clienti.IndirizzoEstensioneNeg AS indirizzo_estensione_neg,
                clienti.LocalitaNeg AS localita_neg,
                clienti.ProvinciaNeg AS provincia_neg,
                clienti.CAPNeg AS CAP_neg,
                clienti.NazioneNeg AS nazione_neg,
                clienti.TelefonoNeg AS telefono_neg,
                clienti.PIVANeg AS piva_neg,
                factory,
                salesPoint,
                orderDate,
                deliveryDate,
                gender AS prod_gender,
                orders.comment AS order_comment,
                product.reference AS prod_reference,
                gender AS gender,
                order_products.comment AS prod_comment,
                grading AS prod_grading,
                quantity AS prod_quantity,
                component.qty AS comp_quantity,
                component_type.label AS comp_type_descrizione,
                component.descrizione AS comp_descrizione,
                alteration.label AS alteration_label,
                order_product_alterations.value AS alteration_value,
                materiali.id as materiale_id,
                CONCAT_WS(' ', materiali.nature, materiali.color) AS materiale_label,
                contrasto.id as contrasto_id,
                CONCAT_WS(' ', contrasto.nature, contrasto.color) AS contrasto_label,
                CONCAT(component_type.codice, component.codice) AS comp_reference
            FROM
                orders
                    LEFT JOIN
                clienti ON orders.cliente_id = clienti.id
                    LEFT JOIN
                order_products ON orders.id = order_products.order_id
                    LEFT JOIN
                order_product_components ON order_products.id = order_product_components.order_product_id
                    LEFT JOIN
                order_product_alterations ON order_products.id = order_product_alterations.order_product_id
                    LEFT JOIN
                    (SELECT * FROM order_product_dettagli WHERE tipo IN ('RS', 'RT')) as ricami ON order_products.id = ricami.order_product_id
                    LEFT JOIN
                    (SELECT * FROM order_product_dettagli WHERE tipo IN ('BM')) as bottoni ON order_products.id = bottoni.order_product_id
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('CO') ) as ricami_colore ON ricami.colore = ricami_colore.id AND ricami.colore IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('PC') ) as ricami_posizione ON ricami.posizione = ricami_posizione.id AND ricami.posizione IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('ST') ) as ricami_stile_testo ON ricami.stile_testo = ricami_stile_testo.id AND ricami.stile_testo IS NOT NULL
                    LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('SI') ) as ricami_simbolo ON ricami.simbolo = ricami_simbolo.id AND ricami.simbolo IS NOT NULL
                     LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('OS') ) as ricami_ordine_simbolo ON ricami.ordine_simbolo = ricami_ordine_simbolo.id AND ricami.ordine_simbolo IS NOT NULL
                     LEFT JOIN
                    (SELECT * FROM ricami_opzioni WHERE tipo IN ('PS') ) as ricami_posizione_simbolo ON ricami.posizione = ricami_posizione_simbolo.id AND ricami.posizione IS NOT NULL
                    LEFT JOIN
                alteration ON order_product_alterations.alteration_id = alteration.id
                    LEFT JOIN
                product ON order_products.product_id = product.id
                    LEFT JOIN
                component ON order_product_components.component_id = component.id
                    LEFT JOIN
                component_type ON component.component_type_id = component_type.id
                    LEFT JOIN
                materiali ON order_product_components.material_id = materiali.id
                    LEFT JOIN
                materiali AS contrasto ON order_product_components.contrastReference = contrasto.id
            WHERE orders.id=".$id_ordine."
            ORDER BY order_products.sequenceNumber;";


        Dbg::d("sql",$sql,1);

        $data=array();

        $res=$this->db->query($sql);

        if($res){

            foreach ($res as $row){

                $data["order_id"]=$row->order_id;
                $data["order_products_id"]=$row->order_products_id;
                $data["factory"]=$row->factory;
                $data["sales_point"]=$row->salesPoint;
                $data["sales_point_info"]=$this->get_info_negozio($row->salesPoint);
                $data["order_date"]=$row->orderDate;
                $data["delivery_date"]=$row->deliveryDate;
                $data["order_comment"]=$row->order_comment;
                $data["cliente"]["CRMid"] = $row->cliente_CRMid;
                $data["cliente"]["nome"] = $row->nome_cliente;
                $data["cliente"]["cognome"] = $row->cognome_cliente;
                $data["cliente"]["data_nascita"] = $row->data_nascita;
                $data["cliente"]["sesso"] = $row->sesso;
                $data["cliente"]["p_iva"] = $row->p_iva;
                $data["cliente"]["cf"] = $row->cf;
                $data["cliente"]["email"] = $row->email;
                $data["cliente"]["via"] = $row->via;
                $data["cliente"]["localita"] = $row->localita;
                $data["cliente"]["provincia"] = $row->provincia;
                $data["cliente"]["CAP"] = $row->CAP;
                $data["cliente"]["nazione"] = $row->nazione;
                $data["cliente"]["telefono"] = $row->telefono_neg;
                $data["cliente"]["ritiro_in_negozio"] = $row->ritiro_in_negozio;

                if(  $data["cliente"]["ritiro_in_negozio"] ==1){
                    $data["cliente"]["cod_neg"] = $row->cod_neg;
                    $data["cliente"]["nome_neg"] = $row->nome_neg;
                    $data["cliente"]["indirizzo_neg"] = $row->indirizzo_neg;
                    $data["cliente"]["indirizzo_estensione_neg"] = $row->indirizzo_estensione_neg;
                    $data["cliente"]["localita_neg"] = $row->localita_neg;
                    $data["cliente"]["provincia_neg"] = $row->provincia_neg;
                    $data["cliente"]["CAP_neg"] = $row->CAP_neg;
                    $data["cliente"]["nazione_neg"] = $row->nazione_neg;
                    $data["cliente"]["telefono_neg"] = $row->telefono_neg;
                    $data["cliente"]["piva_neg"] = $row->piva_neg;
                }

                $data["order_product"][$row->order_products_id]["id"]=$row->order_products_id;
                $data["order_product"][$row->order_products_id]["barcode_prezzo"]=$row->product_barcode_prezzo;
                $data["order_product"][$row->order_products_id]["gender"]=$row->gender;
                $data["order_product"][$row->order_products_id]["quantity"]=$row->prod_quantity;
                $data["order_product"][$row->order_products_id]["grading"]=$row->prod_grading;
                $data["order_product"][$row->order_products_id]["reference"]=$row->prod_reference;

                if($row->component_id){
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["id"]=$row->component_id;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["reference"]=$row->comp_reference;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["quantity"]=$row->comp_quantity;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["comp_type"]["id"]=$row->component_type_id;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["comp_type"]["label"]=$row->comp_type_descrizione;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["comp_descrizione"]=$row->comp_descrizione;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["materiale"]["id"]=$row->materiale_id;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["materiale"]["label"]=$row->materiale_label;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["contrasto"]["id"]=$row->contrasto_id;
                    $data["order_product"][$row->order_products_id]["components"][$row->component_id]["contrasto"]["label"]=$row->contrasto_label;


                    if(isset($row->bottoni_barcode)){
                        $data["order_product"][$row->order_products_id]["bottoni"][$row->bottoni_tipo]["tipo"] = $row->bottoni_tipo;
                        $data["order_product"][$row->order_products_id]["bottoni"][$row->bottoni_tipo]["barcode"] = $row->bottoni_barcode;
                    }


                    if(isset( $row->ricami_tipo)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["tipo"]=$row->ricami_tipo;
                    }

                    if(isset( $row->ricami_barcode)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["barcode"]=$row->ricami_barcode;
                    }

                    if(isset($row->ricami_colore_id)){

                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["colore"]["id"] = $row->ricami_colore_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["colore"]["label"] = $row->ricami_colore_label;
                    }

                    if(isset($row->ricami_posizione_id)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione"]["id"] = $row->ricami_posizione_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione"]["label"] = $row->ricami_posizione_label;
                    }


                    if(isset($row->ricami_simbolo_id)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["simbolo"]["id"] = $row->ricami_simbolo_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["simbolo"]["label"] = $row->ricami_simbolo_label;
                    }


                    if(isset($row->ricami_testo)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["testo"] = $row->ricami_testo;
                    }

                    if(isset($row->ricami_stile_testo_id)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["stile_testo"]["id"] = $row->ricami_stile_testo_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["stile_testo"]["label"] = $row->ricami_stile_testo_label;
                    }



                    if(isset($row->ricami_ordine_simbolo_id)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["ordine_simbolo"]["id"] = $row->ricami_ordine_simbolo_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["ordine_simbolo"]["label"] = $row->ricami_ordine_simbolo_label;
                    }

                    if(isset($row->ricami_posizione_simbolo_id)){
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione_simbolo"]["id"] = $row->ricami_posizione_simbolo_id;
                        $data["order_product"][$row->order_products_id]["ricami"][$row->ricami_tipo]["posizione_simbolo"]["label"] = $row->ricami_posizione_simbolo_label;
                    }

                }

                if($row->alteration_id){
                    $data["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["id"]=$row->alteration_id;
                    $data["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["label"]=$row->alteration_label;
                    $data["order_product"][$row->order_products_id]["alterations"][$row->alteration_id]["value"]=$row->alteration_value;

                }

            }

        }

        return $data;
    }

    */





    public function get_field_data($params=NULL){

        $end_res= (array)new EndRes(1,"");

        if(!isset($params)){
            $params = $this->req->get_request_data("val_",4,1);
        }

        Dbg::d("paramsss",$params,1);

        $params_ok=0;
        $field= $params["field"];
        $args=array();
        switch ($field){

             case "product_sizes":
                 if(isset($params["product_id"])) {
                     $params_ok = 1;
                     $args[] = "product_id='" . $params["product_id"] . "'";
                 }
             break;
            case "dettaglio":
                if(isset($params["component_type_id"])) {
                    $params_ok = 1;
                    $args[] = "component_type.id=". $params["component_type_id"];
                }
                break;
            case "componente":
                if(isset($params["component_type_id"])) {
                    $params_ok = 1;
                    $args[] = "component_type.id=". $params["component_type_id"];
                }
                break;
            case "alteration":
                $params_ok = 1;
                if(isset($params["id"])) {
                    $args[] = "id=". $params["id"];
                }
                break;
            case "negozi":
                $params_ok = 1;
                if(isset($params["id_neg"])) {
                    $args[] = " ID = ". $params["id_neg"];
                }
                break;
            case "materiale":
                $params_ok = 1;

                Dbg::d("materiale","---------------",1);

                Dbg::d("paraqms",$params,1);

                if(isset($params["materiale_id"]) && isset($params["check_has_bottoni_madreperla"]) && $params["check_has_bottoni_madreperla"]==1) {
                    $args[] = " materiali.id = ". $params["materiale_id"];
                    $args[] = " bottone_madreperla_dettagli.label <> '0'";
                }

                Dbg::d("args",$args,1);

                break;
            default:
                $params_ok=1;
                break;
         }


         if($params_ok==0){
             $end_res["success"]=0;
             $end_res["error"]="Errore, Paramentri mancanti";
         }else{


             Dbg::d("getdata222",$this->fields[$field],1);
             Dbg::d("args2222",$args,1);

             if(count($args)){
                 $this->fields[$field]->add_args($args);
             }
             $data = $this->fields[$field]->get_data();
             $end_res["data"]=$data;
         }
         ;

        return $end_res;

    }


    public function get_fields_data(){

       Dbg::d("get field data2","get field data2",1);
       $fields_data = $this->fields_manager->get_data();
       Dbg::d("fields_manager",$this->fields_manager,1);
       Dbg::d("field data",$fields_data,1);

       return $fields_data;

    }


    public function check_login(){



        $end_res= (array)new EndRes(0,"Effettuare il login");

        //if(1){
        if($this->env=="prod") {
            if (isset($this->session["auth"])) {
                $end_res["success"] = 1;
                $end_res["error"] = "";
            }
        }else{
            $end_res["success"] = 1;
            $end_res["error"] = "";
        }

        return $end_res;

    }


    public function check_perm(){

        if($this->env=="prod"){

            $end_res= (array)new EndRes(0,"Non hai il permesso per accedere al backoffice");
            if(isset($this->session["auth"])){
                if(in_array($this->action,$this->backoffice_authorized_actions)){
                    if(in_array($_SESSION["codcom"],$this->backoffice_authorized_coms)){
                            $end_res["success"]=1;
                            $end_res["error"]="";
                    }
                }else{
                    $end_res["success"]=1;
                    $end_res["error"]="";
                }
            }
        }else{
            $end_res["success"] = 1;
            $end_res["error"] = "";
        }


        return $end_res;

    }

    /**
     * Gestisce le varie request in arrivo in base al campo action
     * @return void
     */
    public function start(){
        error_log("start");
        if($this->action=='get_dati_pagina_cancella_ordini'){
            $output= $this->login(1);
            if($output["success"]==1){
                $output= $this->get_dati_pagina_cancella_ordini();
            }
            echo json_encode(array('input' => $this->request, 'output' => $output));
            return;
        }else if($this->action=='delete_order'){
            $output= $this->login(1);
            if($output["success"]==1){
                $output= $this->delete_order();
            }
            echo json_encode(array('input' => $this->request, 'output' => $output));
            return;
        }else if($this->action=='login'){
            error_log("action login init");
            $output= $this->login();
            error_log("action login end");
        }else{
            error_log("check login init");
            $end_res_check_login=$this->check_login();
            error_log("check login end");
            if($end_res_check_login["success"]==0){
                $output = $end_res_check_login;
                echo json_encode(array('input' => $this->request, 'output' => $output));
                return;
            }

            $end_res_check_perm=$this->check_perm();
            if($end_res_check_perm["success"]==0){
               $output = $end_res_check_perm;
               echo json_encode(array('input' => $this->request, 'output' => $output));
               return;
            }

           Dbg::d("start action", $this->action,1);
           switch ($this->action){
               case "rolls":
                   switch ($this->method){
                       case "post":
                           $this->rolls_post();
                           break;
                       case "put":
                           $this->rolls_put();
                           break;
                       case "delete":
                           $this->rolls_delete();
                           break;
                   }
                   break;
               case "batches":
                   switch ($this->method){
                       case "post":
                           $this->batches_post();
                           break;
                       case "delete":
                           $this->batches_delete();
                           break;
                      /* case "put":
                           $this->batches_put();
                           break;*/
                   }
                   break;
                case "materials":
                    switch ($this->method){
                        case "get":
                            $this->materials_get();
                            break;
                        case "post":
                            $this->materials_post();
                            break;
                        case "put":
                            $this->materials_put();
                            break;
                     }
                    break;

               case "materials_sets":
                   switch ($this->method){
                       case "post":
                           $this->materials_sets_post();
                           break;
                   }
                   break;

               case "import_update_materials":
                   $output =  $this->import_update_materials();
                   break;
               case "get_fields_data":
                   Dbg::d("get_fields_data", "get_fields_data" ,1);
                   $output =  $this->get_fields_data();
                   break;
               case "get_field_data":
                   Dbg::d("get_fields_data", "get_fields_data" ,1);
                   $output =  $this->get_field_data();
                   break;
               case "do_auth":
                   $this->do_auth();
                   break;
               case "logout":
                   $output = $this->logout();
                   break;
               case "get_lista_ordini":
                   $output = $this->get_lista_ordini();
                   break;
               case "get_taglie":
                   $output = $this->get_taglie();
                   break;
               case "save":
                   $this->save();
                   break;
               case "get_componente":
                   $output = $this->get_componente();
                   break;
               case "get_alterations":
                   $output = $this->get_alterations();
                   break;
               case "get_collo_alterations":
                   $output = $this->get_collo_alterations();
                   break;
               case "get_data_negozio":
                   $output = $this->get_data_negozio();
                   break;
               case "get_contrasto":
                   $output = $this->get_contrasto();
                   break;
               case "get_user_by_crm":
                   $output = $this->get_user_by_crm();
                   break;
               case "save_user_on_crm":
                   $output = $this->save_user_on_crm();
                   break;
               case "update_user_on_crm":
                   $output = $this->update_user_on_crm();
                   break;
               case "save_user":
                   $output = $this->save_user();
                   break;
               case "print_order":
                   $output = $this->print_order();
                   break;
               case "get_barcode":
                   $output = $this->get_barcode();
                   break;
               case "send_confirmed_orders":
                   $output = $this->send_confirmed_orders();
                   break;
               case "get_manica_alterations":
                   $output = $this->get_manica_alterations();
                   break;
               case "get_polso_alterations":
                   $output = $this->get_polso_alterations();
                   break;
               case "print_instructions":
                   $output = $this->print_instructions();
                   break;
               case "check_ricami":
                   $output = $this->check_ricami();
                   break;
               case "get_orders":
                   $output = $this->get_orders();
                   break;
               case "get_order_data":
                   $output = $this->get_new_order_by_session();
                   break;
               case "save_order":
                   $output = $this->save_order();
                   break;
               case "save_order_product":
                   $output = $this->save_order_product();
                   break;
               case "delete_order":
                   $output = $this->delete_order();
                   break;
               case "delete_order_product":
                   $output = $this->delete_order_product();
                   break;
               case "confirm_order":
                   $output = $this->confirm_order();
                   if($output["success"]==1){
                       $output = $this->print_order();
                       if($output["success"]==1){
                           $output = $this->print_instructions();
                       }
                   }
                   break;
               case "approve_order":
                   $output = $this->confirm_order(true);
                   break;
               /*case "pre_save":
                   $output= $this->pre_save();
                   break;
               case "post_save":
                   $output=  $this->post_save();
                   break;*/
               case "save_prodotto_da_cucire":
                   $output=  $this->save_prodotto_da_cucire();
                   break;
               case "save_prodotto_completato":
                   $output=  $this->save_prodotto_completato();
                   break;
               case "do_shipment_request":
                   $output=  $this->do_shipment_request();
                   break;
               case "import_rolls":
                   $output=  $this->import_rolls();
                   break;
               case "add_movimentazione_from_as400":
                   $output=  $this->logistica->add_movimentazione_from_as400();
                   break;
               case "add_movimenti":
                   $output=  $this->logistica->add_movimenti();
                   break;
               case "check_pezzi":
                    $this->logistica->check_pezzi();
                   break;
               case "save_lettera_di_vettura":
                   $output= $this->save_lettera_di_vettura();
                   break;
               case "get_barcode_by_ordine":
                   $output= $this->get_barcode_by_ordine();
                   break;
               case "calc_prezzo_by_ordine":
                   $output= $this->calc_prezzo_by_ordine();
                   break;
               case "print_proforma":
                   $output= $this->logistica->print_proforma();
                   break;
               case "create_proforma":
                   $output= $this->logistica->create_proforma();
                   break;
               case "create_fattura":
                   $output= $this->logistica->create_fattura();
                   break;
                case "create_bolla_lavorazione":
                    $output = $this->create_bolla_lavorazione();
                    break;
                case "download_bolla_lav":
                    $params = $this->req->get_request_data("val_", 4, 1);
                    error_log(print_r($params, true));
                    $output = (array) new EndRes(0, "Errore download file", array());
                    if(isset($params["filename"])){
                        $this->downloadFile($params["filename"]);
                        return;
                    }else{
                        $output = (array) new EndRes(0, "Nessun file specificato", array());
                    }
               case "save_movimenti_by_ordine":
                   $output= $this->logistica->save_movimenti_by_ordine();
                   break;
               case "get_movimenti_by_ordini":
                   $output= $this->logistica->get_movimenti_by_ordini();
                   break;
               case "has_bottoni_madreperla":
                   $output= $this->has_bottoni_madreperla();
                   break;
               case "delete_order_remote":
                   $output= $this->delete_order_remote();
                   break;
            }
        }
        Dbg::d("session2",$this->session,1);
        if(isset($output)){
            echo json_encode(array('input' => $this->request, 'output' => $output),JSON_UNESCAPED_SLASHES);
        }
    }
    /**
     * Recupera i dati di tutti gli ordini
     * @return
     */
    public function get_orders(){
        $data = array();
        $controller = new OrderController();
        $fields = $this->req->get_request_data("val_", 4, 1);
        if(isset($fields["fields"]) && isset($fields["fields"]["stato"])){
            $orders = $controller->getOrders($fields["fields"]["stato"]);
        }else{
            $orders = $controller->getOrders();
        }
        foreach ($orders as $ord){
            $data[$ord->id] = $ord->toArray();
            $data[$ord->id]["products"] = array();
            $products = ProductOrder::where("order_id", $ord->id);
            foreach ($products as $prod){
                $data[$ord->id]["products"][$prod->id] = $prod->toArray();
            }
        }
        return new EndRes(1, "", $data);
    }
    /**
     * Recupera i dati dell'ordine nello stato "new" legato alla sessione
     * @return array
     */
    public function get_new_order_by_session(){
        $msg = "";
        $data = array();
        $controller = new OrderController();
        $orders = $controller->getOrderBySession(Order::NEW_STATE);
        if(!empty($orders)){
            $data = $this->get_dati_ordine($orders[0]->id);
            if(sizeof($orders) > 2){
                $msg = "Anomalia. Ci sono più ordini legati a questa session";
            }
        }
        return new EndRes(1, $msg, $data);
    }
    /**
     * Salva l'ordine nel DB
     * @return array
     */
    public function save_order(){
        $end_res = new EndRes();
        $controller = new OrderController();
        $fields = $this->req->get_request_data("val_",4,1);
        if(isset($fields["fields"])){
            $res = (new SaveOrderReq())->check_required_data($fields["fields"]);
            if($res["success"] === 0){
                return new EndRes($res["success"], $res["error"]);
            }
            try{
                $order = $controller->saveOrder($fields["fields"]);
                $end_res = ($order)
                    ? new EndRes(1, "Ordine creato con successo", $order->toArray())
                    : new EndRes(0, "Errore creazione ordine");
            }catch(Exception $e){
                $end_res = new EndRes(0, $e->getMessage());
            }
        }else{
            $end_res = new EndRes(0, "Errore passaggio parametri");
        }
        return $end_res;
    }
    /**
     * Salva un prodotto nel'ordine
     * @return array
     */
     public function save_order_product(){
         $end_res = new EndRes();
         $controller = new OrderController();
         $fields = $this->req->get_request_data("val_",4,1);
         if(isset($fields["fields"])){
             $res = (new SaveOrderProductReq())->check_required_data($fields["fields"]);
             if($res["success"] === 0){
                 $end_res = new EndRes($res["success"], $res["error"]);
             }else{
                 try{
                     // Add products info
                     foreach($fields["fields"]["products"] as &$pre_save_prod){ // & modifica direttamente l'array
                         // Calcolo scollo
                         $res = $this->calc_scollo_alterations($pre_save_prod["scollo"], $pre_save_prod["grading"]);
                         // if($res["success"] != 1){
                         //     throw new Exception("errore calcolo alterazione scollo");
                         // }
                         $scollo = $res["data"];
                         if(!isset($pre_save_prod["alterations"])){
                             $pre_save_prod["alterations"] = array();
                         }
                         $pre_save_prod["alterations"][] = array(
                             "alteration_id" => (Alteration::where("label", "3_SCOLLO")[0])->id,
                             "value" => $scollo * 10
                         );
                         // Creazione barcode articolo
                         $barcode_uni = $this->calc_barcode_univoco();
                         if($barcode_uni["success"] != 1 || !isset($barcode_uni["data"]["last_insert_id"])){
                             throw new Exception("errore creazione barcode articolo");
                         }
                         $pre_save_prod["barcode"] = $barcode_uni["data"]["last_insert_id"];
                         $barcode_prz = $this->calc_barcode_prezzo();
                         if($barcode_prz["success"] != 1 || !isset($barcode_prz["data"]["barcode"])){
                             throw new Exception("errore creazione barcode prezzo");
                         }
                         $pre_save_prod["barcode_prezzo"] = $barcode_prz["data"]["barcode"];
                         // Calcolo barcode ricami
                         if(isset($pre_save_prod["ricamo"])){
                             $ricamo = $pre_save_prod["ricamo"];
                             $barcodes = $this->calc_ricami(
                                 (isset($ricamo["testo"]) && in_array(strlen(trim($ricamo["testo"])), [2,3])) ? strlen(trim($ricamo["testo"])) : 0,
                                 (isset($ricamo["stile"])) ? $ricamo["stile"] : 0,
                                 (isset($ricamo["simbolo"])) ? $ricamo["simbolo"] : 0
                             );
                             if(!empty($barcodes)){
                                 $pre_save_prod["ricamo"]["barcodes"] = $barcodes;
                             }
                         }
                     }
                     $sequence_number = $this->getLastSequenceNumber($fields["fields"]["ordine_id"]);
                     /** @var ProductOrder $products */
                     $products = $controller->saveOrderProducts(
                         $fields["fields"]["ordine_id"],
                         $fields["fields"],
                         ($sequence_number >= 0) ? $sequence_number+1 : 0
                     );
                     if(!empty($products)){
                         foreach($products as $prod){
                             $order_products[] = $prod->toArray();
                         }
                         $end_res = new EndRes(1, "Prodotti aggiunti con successo", $order_products);
                     }else{
                         $end_res = new EndRes(0, "Errore creazione prodotti nell'ordine");
                     }
                 }catch(Exception $e){
                     $end_res = new EndRes(0, $e->getMessage());
                 }
             }
         }else{
             $end_res = new EndRes(0, "Errore passaggio parametri");
         }
         return $end_res;
     }

     private function getLastSequenceNumber($order_id){
         $sql = "SELECT MAX(p.sequenceNumber) as seq FROM orders AS o ".
             "INNER JOIN order_products as p ON (p.order_id = o.id) WHERE order_id = :order_id";
         $res = $this->db->query($sql, array(":order_id" => $order_id));
         return ($res && is_array($res)) ? $res[0]->seq : -1;
     }
    /**
     * Cancella ordine
     * @return array
     */





    public function delete_order_remote(){






        $params = $this->req->get_request_data("val_",4,1);


        Dbg::d("params",$params,1);


        $controller = new OrderController();


        Dbg::d("params",count($params),1);


        if(count($params)>=0){
            if(isset($params["ordine_id"])){ // Check required fields here
                try{
                    $status = $controller->deleteOrder(
                        $params["ordine_id"],
                        0
                    );
                    $end_res = ($status)
                        ? new EndRes(1, "Ordine cancellato correttamente")
                        : new EndRes(0, "Errore cancellazioen ordine");
                }catch(Exception $e){
                    $end_res = new EndRes(0, $e->getMessage());
                }
            }else{
                $end_res = new EndRes(0, "Campo ordine_id è obbligatorio");
            }
        }else{



            $end_res = new EndRes(0, "Errore passaggio parametri");
        }
        return $end_res;
    }



    public function delete_order(){
        $end_res = new EndRes();
        $controller = new OrderController();
        $fields = $this->req->get_request_data("val_",4,1);
        if(isset($fields["fields"])){
            if(isset($fields["fields"]["ordine_id"])){ // Check required fields here
                try{
                    $status = $controller->deleteOrder(
                        $fields["fields"]["ordine_id"],
                        (isset($fields["fields"]["permanent"])) ? $fields["fields"]["permanent"] : 0
                    );
                    $end_res = ($status)
                        ? new EndRes(1, "Ordine cancellato correttamente")
                        : new EndRes(0, "Errore cancellazioen ordine");
                }catch(Exception $e){
                    $end_res = new EndRes(0, $e->getMessage());
                }
            }else{
                $end_res = new EndRes(0, "Campo ordine_id è obbligatorio");
            }
        }else{
            $end_res = new EndRes(0, "Errore passaggio parametri");
        }
        return $end_res;
    }
    /**
     * Cancella prodotto dall'ordine
     * @return array
     */
    public function delete_order_product(){
        $end_res = new EndRes();
        $controller = new OrderController();
        $fields = $this->req->get_request_data("val_",4,1);
        if(isset($fields["fields"])){
            if(isset($fields["fields"]["product_id"])){ // Check required fields here
                try{
                    $status = $controller->deleteOrderProduct($fields["fields"]["product_id"]);
                    $end_res = ($status)
                        ? new EndRes(1, "Prodotto cancellato correttamente")
                        : new EndRes(0, "Errore cancellazione prodotto");
                }catch(Exception $e){
                    $end_res = new EndRes(0, $e->getMessage());
                }
            }else{
                $end_res = new EndRes(0, "Campo product_id è obbligatorio");
            }
        }else{
            $end_res = new EndRes(0, "Errore passaggio parametri");
        }
        return $end_res;
    }
    /**
     * Approvazione dell'ordine (cliente firma il foglio)
     * @param bool $approved Se true l'ordine è anche approvato, altrimenti è solo confermato.
     * @return array
     */
    public function confirm_order($approved = false){
        $end_res = new EndRes();
        $controller = new OrderController();
        // Save order
        $fields = $this->req->get_request_data("val_",4,1);
        if(isset($fields["fields"])){
            if(isset($fields["fields"]["ordine_id"])){
                try{
                    $res = ($approved)
                        ? $controller->approveOrder($fields["fields"]["ordine_id"])
                        : $controller->confirmOrder($fields["fields"]["ordine_id"]);
                    $end_res = ($res)
                        ? new EndRes(1, "")
                        : new EndRes(0, "Errore cambio stato");
                }catch(Exception $e){
                    $end_res = new EndRes(0, $e->getMessage());
                }
            }else{
                $end_res = new EndRes(0, "Il campo ordine_id è obbligatorio");
            }
        }else{
            $end_res = new EndRes(0, "Errore passaggio parametri");
        }
        return (array)$end_res;
    }


    public function send_confirmed_orders($order_id=NULL){



        /*echo("aaaa");
        $end_res= (array)new EndRes();
        $req_val =  $this->req->get_request_data("order_id_",4,1);
        Dbg::d("req val", $req_val,1);
        */

        $end_res= (array)new EndRes();
        $this->get_ordini();

        return $end_res;

    }


    /**
     * @return mixed
     */


    public function create_pdf($title, $html_body, $filename){
        $end_res = (array) new EndRes(0, "pdf non generato");
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
       // $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle($title);
        //$pdf->SetSubject('TCPDF Tutorial');
        //$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        /*
         // set some language-dependent strings (optional)
         if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
             require_once(dirname(__FILE__).'/lang/eng.php');
             $pdf->setLanguageArray($l);
         }
         */
        // ---------------------------------------------------------
        // set default font subsetting mode
        $pdf->setFontSubsetting(true);
        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $pdf->SetFont('dejavusans', '', 14, '', true);
        // Add a page
        // This method has several options, check the source code documentation for more information.
        $pdf->AddPage();
        // set text shadow effect
        $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
        // $html = <<<EOD
        //         $html_body
        //     EOD;
        $html = $html_body;
        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        // ---------------------------------------------------------
        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        $filepathname=$this->pdf_path.$filename;
        Dbg::d("filepathname init",$filepathname,1);
        if(file_exists($filepathname)){
            if (!unlink($filepathname)) {
                $end_res["success"]="Il file precedente non &egrave; stato scaricato";
            }
        }
        $pdf->Output($filepathname, 'F');
        if(file_exists($filepathname)){
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]["file"]=$this->pdf_ext_path.$filename;
            $end_res["data"]["filepathname"]=$filepathname;
        }
        return $end_res;
    }







    public function print_instructions($params=NULL){

        $end_res = (array) new EndRes(0,"Errore Stampa Istruzioni" );

        $req_val =  $this->req->get_request_data("val_",4,1);
        Dbg::d("reqval__",$req_val,1);
        $data= $this->get_dati_ordine($req_val["fields"]["ordine_id"]);



        foreach ($data["order_product"] as $key_order=> $val_order){


            Dbg::d("keyorder", $key_order,1);
            Dbg::d("val_order", $val_order,1);

            $walk_txt="";
            array_walk_recursive( $data, function($value, $key) use (&$walk_txt) {
                $walk_txt.=$key.": ".$value."<br/>";
            }, $walk_txt);


            $now = new DateTime();
            $title="Instructions ".$data["order_id"]." ".$val_order["product_barcode_univoco"];
            $filename= "instructions_".$data["order_id"]."_".$val_order["product_barcode_univoco"]."_".$now->format("Ymd_His").".pdf";
            $end_res_pdf_storico= $this->create_pdf($title, $walk_txt, $filename);
            if($end_res_pdf_storico["success"]==1){
                $filename= "instructions_".$data["order_id"]."_".$val_order["product_barcode_univoco"].".pdf";
                $end_res_pdf_finale= $this->create_pdf($title, $walk_txt, $filename);
                if($end_res_pdf_finale["success"]==1){
                    $end_res =  $end_res_pdf_finale;
                }else{
                    break;
                }
            } else{
                break;
            }
        }

        return $end_res;

    }


    public function get_lista_ordini(){

        $end_res= (array)new EndRes(0,"Errore lista ordini");

        $sql="SELECT
                    orders.id as order_id,
                    order_products.product_id as order_product_id,
                    order_products.barcode_univoco,
                    order_products.inviato,
                    orders.comment,
                    orders.stato,
                    orders.orderDate,
                    gender,
                    quantity,
                    grading,
                    salesPoint,
                    da_cucire,
                    completato
                    FROM
                orders, order_products
                WHERE
                order_products.order_id= orders.id
                ORDER BY orders.orderDate DESC";


        Dbg::d("sql",$sql,1);
        $res=$this->db->query($sql);

        Dbg::d("thisdb",$this->db,1);
        Dbg::d("res",$res,1);

        if($res){
            foreach ($res as $row){


                $pdf_ordine_filename= "ordine_".$row->order_id.".pdf";
                $pdf_lettera_vettura_filename= "lettera_vettura_ordine_".$row->order_id.".pdf";

                $pdf_istruzioni_filename=  "istruzioni_prodotto_".$row->barcode_univoco.".pdf";

                Dbg::d("pdf ordine filename",$pdf_ordine_filename,1);
                Dbg::d("pdf istruzioni_filename",$pdf_istruzioni_filename,1);

                Dbg::d("existse",file_exists($this->pdf_path.$pdf_istruzioni_filename));

                $data["id_ordine"]=$row->order_id;
                $data[$row->order_id]["pdf_ordine"] = file_exists($this->pdf_path.$pdf_ordine_filename)? $this->pdf_ext_path.$pdf_ordine_filename: "FILE_NON_ESISTENTE";
                $data[$row->order_id]["pdf_lettera_vettura"] = file_exists($this->pdf_path.$pdf_lettera_vettura_filename)? $this->pdf_ext_path.$pdf_lettera_vettura_filename: "FILE_NON_ESISTENTE";

                $data[$row->order_id]["ordini"][$row->order_product_id]["id"]=$row->order_product_id;
                $data[$row->order_id]["ordini"][$row->order_product_id]["comment"]=$row->comment;
                $data[$row->order_id]["ordini"][$row->order_product_id]["barcode_univoco"]=$row->barcode_univoco;
                $data[$row->order_id]["ordini"][$row->order_product_id]["stato"]=$row->stato;
                $data[$row->order_id]["ordini"][$row->order_product_id]["order_date"]=$row->orderDate;
                $data[$row->order_id]["ordini"][$row->order_product_id]["gender"]=$row->gender;
                $data[$row->order_id]["ordini"][$row->order_product_id]["quantity"]=$row->quantity;
                $data[$row->order_id]["ordini"][$row->order_product_id]["grading"]=$row->grading;
                $data[$row->order_id]["ordini"][$row->order_product_id]["sales_point"]=$row->salesPoint;
                $data[$row->order_id]["ordini"][$row->order_product_id]["inviato"]=$row->inviato;
                $data[$row->order_id]["ordini"][$row->order_product_id]["da_cucire"]=$row->da_cucire;
                $data[$row->order_id]["ordini"][$row->order_product_id]["completato"]=$row->completato;
                $data[$row->order_id]["ordini"][$row->order_product_id]["pdf_istruzioni"]= file_exists($this->pdf_path.$pdf_istruzioni_filename)? $this->pdf_ext_path.$pdf_istruzioni_filename: "FILE_NON_ESISTENTE";

            }
            $end_res["success"]=1;
            $end_res["error"]="";
            $end_res["data"]=$data;
        }

        return $end_res;


    }



    public function print_order()
    {


            $end_res = (array) new EndRes(0,"Errore Stampa ordine" );

            $req_val =  $this->req->get_request_data("val_",4,1);
            Dbg::d("reqval__",$req_val,1);
            $data= $this->get_dati_ordine($req_val["fields"]["ordine_id"]);

            Dbg::d("data__",$data,1);


            //ordine
            $walk_txt="";
            array_walk_recursive( $data, function($value, $key) use (&$walk_txt) {
                $walk_txt.=$key.": ".$value."<br/>";
            }, $walk_txt);


            $now = new DateTime();
            $title="ordine_".$data["order_id"];
            $filename= "ordine_".$data["order_id"]."_".$now->format("Ymd_His").".pdf";
            $end_res_pdf_storico= $this->create_pdf($title, $walk_txt, $filename);

            $filename_cliente = "ordine_".$data["order_id"]."_cliente.pdf";
            $end_res_pdf_cliente = $this->create_pdf($title, $walk_txt, $filename_cliente);

            $filename_negozio = "ordine_".$data["order_id"]."_negozio.pdf";
            $end_res_pdf_negozio = $this->create_pdf($title, $walk_txt, $filename_negozio);

            $filename_barcode = "ordine_".$data["order_id"]."_barcode.pdf";
            $end_res_pdf_barcode = $this->create_pdf($title, $walk_txt, $filename_barcode);


            if( $end_res_pdf_storico["success"]==1 &&
                $end_res_pdf_cliente["success"]==1 &&
                $end_res_pdf_negozio["success"]==1 &&
                $end_res_pdf_barcode["success"]==1){


                Dbg::d("aaa___","----",1);

                $mail_subject = "Camicissima: ordine: ".$data["order_id"]." ".
                $mail_body=  $data["order_id"];
                $mail_logistica = $this->email["ordine"];
                if($this->sendMail($mail_subject, $mail_body, $mail_logistica)){
                    $end_res["success"]=1;
                    $end_res["error"]='';

                    $end_res["data"]["file_cliente"]=$end_res_pdf_cliente["data"]["file"];
                    $end_res["data"]["filepathname_cliente"]=$end_res_pdf_cliente["data"]["filepathname"];

                    $end_res["data"]["file_negozio"]=$end_res_pdf_negozio["data"]["file"];
                    $end_res["data"]["filepathname_negozio"]=$end_res_pdf_negozio["data"]["filepathname"];

                    $end_res["data"]["filename_barcode"]=$end_res_pdf_barcode["data"]["file"];
                    $end_res["data"]["filepathname_barcode"]=$end_res_pdf_barcode["data"]["filepathname"];


                }else{
                    $end_res["success"]=0;
                    $end_res["error"]='mail non inviata';
                }



            }


            return $end_res;





/*


            // create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetSubject('TCPDF Tutorial');
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

            // set default header data
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
            $pdf->setFooterData(array(0,64,0), array(0,64,128));

            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);



            // ---------------------------------------------------------

            // set default font subsetting mode
            $pdf->setFontSubsetting(true);

            // Set font
            // dejavusans is a UTF-8 Unicode font, if you only need to
            // print standard ASCII chars, you can use core fonts like
            // helvetica or times to reduce file size.
            $pdf->SetFont('dejavusans', '', 14, '', true);

            // Add a page
            // This method has several options, check the source code documentation for more information.
            $pdf->AddPage();

            // set text shadow effect
            $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));


            $style_barcode = array(
                'position' => '',
                'align' => 'C',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => true,
                'hpadding' => 'auto',
                'vpadding' => 'auto',
                'fgcolor' => array(0,0,0),
                'bgcolor' => false,
                'text' => true,
                'font' => 'helvetica',
                'fontsize' => 8,
                'stretchtext' => 4
            );


            // EAN 13
            $pdf->Cell(0, 0, 'EAN 13', 0, 1);
            $pdf->write1DBarcode('1234567890128', 'EAN13', '', '', '', 18, 0.4, $style_barcode, 'N');



        Dbg::d("data",$data,1);



        $html_body= " <h1>ID ordine: ".$data["id_ordine"]."</h1>";
        $html_body.=" <p>Punto vendita: ".$data["sales_point"]."</p>";
        $html_body.=" <p>Punto vendita info: ".$data["sales_point_info"]."</p>";
        $html_body.=" <p>Data Ordine: ".substr($data["order_date"],0, 10)."</p>";
        $html_body.=" <p>Ora Ordine: ".substr($data["order_date"],11)."</p>";
        $html_body.=" <p>Data Consegna: ".substr($data["delivery_date"],0, 10)."</p>";
        $html_body.=" <p>Ora Consegna: ".substr($data["delivery_date"],11)."</p>";
        $html_body.=" <p>Commento: ".$data["order_comment"]."</p>";

        $html_body.= " <h1>Cliente: </h1>";
        foreach ($data["cliente"] as $key_cliente => $val_cliente){

            $html_body.= "<p>".ucfirst(str_replace("_"," ",$key_cliente)).": ".$val_cliente."</p>";

        }


        foreach ($data["products"] as $key_prod => $val_prod){


            $html_body.= " <h1>Prodotto: </h1>";
           Dbg::d("val_prodddd",$val_prod,1);
            $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Genere: ".$val_prod["gender"]."</p>";
            $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Quantit&agrave;: ".$val_prod["quantity"]."</p>";
            $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Modello: ".$val_prod["reference"]."</p>";
            $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;Taglia: ".$val_prod["grading"]."</p>";



            if(count($val_prod["components"])>0){

            $html_body.= " <h1>Componenti: </h1>";


               foreach ($val_prod["components"] as $key_comp=>$val_comp){
                   Dbg::d("val_comp",$val_comp,1);
                   $html_body.= "<h2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo componente: ".$val_comp["comp_type_descrizione"]."</h2>";
                   $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Codice Riferimenti: ".$val_comp["reference"]."</p>";
                   $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Quantit&agrave;: ".$val_comp["quantity"]."</p>";
                   $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Descrizione: ".$val_comp["comp_descrizione"]."</p>";
                   $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Materiale: ".$val_comp["materiale"]."</p>";
                   $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Contrasto: ".$val_comp["contrasto"]."</p>";
               }

            }


            if(count($val_prod["alterations"])>0){

                $html_body.= " <h1>Alterazioni: </h1>";
                foreach ($val_prod["alterations"] as $key_alt=>$val_alt){
                    Dbg::d("val_comp",$val_alt,1);
                    $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nome: ".$val_alt["label"]."</p>";
                    $html_body.= "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Valore: ".$val_alt["value"]."</p>";

                }

            }


        }

        $html =
<<<EOD
           $html_body
EOD;


    Dbg::d("html body",$html_body,1);




            // Print text using writeHTMLCell()
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

            // ---------------------------------------------------------

            // Close and output PDF document
            // This method has several options, check the source code documentation for more information.
            //);

            Dbg::d("id ordine",$data["id_ordine"],1);
            //$pdf->Output('example_001.pdf', 'I');
            $pdf->Output($this->pdf_path."ordine_".$data["id_ordine"], 'F');

            Dbg::d("print order end","print orde end",1);

            //============================================================+
            // END OF FILE
            //============================================================+

            return '';

*/

    }


    public function get_user_by_crm(){

        $req_val =  $this->req->get_request_data("val_",4,1);

        Dbg::d("get_user_by_crm---","---",1);

        $user= new LectraUser($req_val["fields"],$this->db, $this->as400, $this->api_caller);
        return $user->get_by_crm();
    }





    public function save_user_on_crm(){

        $end_res = (array) new EndRes(0,"Errore Salvataggio su crm");
        $req_val =  $this->req->get_request_data("val_",4,1);

        $user= new LectraUser($req_val["fields"],$this->db,$this->as400, $this->api_caller);

        $end_res_check_existing_user = $user->get_by_crm();

        Dbg::d("check existing user", $end_res_check_existing_user,1);

        //se utente non gia registrato
        if(true){
        //if($end_res_check_existing_user["success"]==0){
            $end_res_get_crm_id = $user->get_crm_id();
            Dbg::d("get crm id", $end_res_get_crm_id,1);
            if($end_res_get_crm_id["success"]==1){
                $last_insert_id= $end_res_get_crm_id["data"]["last_insert_id"];
                Dbg::d("last_insert_id", $last_insert_id ,1);
                if(is_numeric($last_insert_id)){

                    Dbg::d("last_insert_id 2", $last_insert_id ,1);
                    $end_res_save_crm= $user->save_on_crm($last_insert_id);
                    if($end_res_save_crm["success"]==1){
                        $end_res["success"]=1;
                        $end_res["error"]="";
                        $end_res["data"]["CRMId"]=$last_insert_id;
                    }

                } else {
                    $end_res["success"]=1;
                    $end_res["error"]="Errore id utente";
                }

            }else{
                $end_res["success"]=0;
                $end_res["error"]="Utente non é stato salvato";
            }

        }else{
            $end_res["success"]=0;
            $end_res["error"]="Utente già esistente";
        }

        return $end_res;

    }


    public function save_user(){

        $req_val =  $this->req->get_request_data("val_",4,1);
        $data_negozio=NULL;


        if(isset($req_val["fields"]["RitiroInNegozio"]) &&  isset($req_val["fields"]["CodNeg"]) &&  $req_val["fields"]["RitiroInNegozio"]==1 && is_numeric($req_val["fields"]["CodNeg"])){

            $cod_neg= $req_val["fields"]["CodNeg"];
            $data_negozio = $this->get_data_negozio($cod_neg);
            $data_negozio = $data_negozio["data"][$cod_neg];
            Dbg::d("data negozio", $data_negozio,1);

        }

        $user= new LectraUser($req_val["fields"],$this->db, $this->as400, $this->api_caller);
        return $user->save($data_negozio);

    }
    /**
     * Esegue autenticazione sul sistema di Lectra
     * @return array
     */
    public function do_auth(){

       //  $end_res=array();
       //  $end_res["success"]=0;
       //  $end_res["error"]="";
       //  $end_res["data"]=array();
       //
       //
       //  Dbg::d("do_auth start", "--------",1);
       // // $headers=array("authorization: Basic UWdGVmxCT1RVRkoxQldKM250YmxBU1h3WkMxS2wyUlM6VVRaUHNjdXIyRDJOZjV1Tld0LUdMTW85TGpZeDN2eUsyQUlkb213eG5YSU5ETE1XNFZma1pndmxWaGpzUzlPaQ==","Content-Type: application/json");
       //
       //  $headers[]="Authorization: Basic UWdGVmxCT1RVRkoxQldKM250YmxBU1h3WkMxS2wyUlM6VVRaUHNjdXIyRDJOZjV1Tld0LUdMTW85TGpZeDN2eUsyQUlkb213eG5YSU5ETE1XNFZma1pndmxWaGpzUzlPaQ==";
       //  $headers[]="Content-Type: application/json";
       //
       //  Dbg::d("headers",$headers,1);
       //  $params= array();
       //  //plan
       //  //$params["audience"]="https://plan-cuttingroom.api.mylectra.com";
       //
       //  //prepare
       //  $params["audience"]="https://custom-offer-definition-connector.mylectra.com";
       //
       //
       //  $params=  json_encode($params,JSON_UNESCAPED_SLASHES);
       //  $this->api_caller->setHeader($headers);
       //
       //  $res_call= $this->api_caller->callAPI("POST", $this->api_auth_path, $params);
       //  $res_call = json_decode($res_call);
       //
       //  if(isset($res_call->access_token)){
       //      $end_res["success"]=1;
       //      $end_res["data"]["access_token"]=$res_call->access_token;
       //
       //  }
       //
       //  Dbg::d("res call", $res_call,1);
       //  /*Dbg::d("do_auth end", "--------",1);
       //  Dbg::d("end_res", $end_res,1);*/
       //  return $end_res;
       //
       $end_res = array();
       $end_res["success"] = 0;
       $end_res["error"] = "";
       $end_res["data"] = array();

       Dbg::d("do_auth start", "--------",1);
       $credentials = "UWdGVmxCT1RVRkoxQldKM250YmxBU1h3WkMxS2wyUlM6VVRaUHNjdXIyRDJOZjV1Tld0LUdMTW85TGpZeDN2eUsyQUlkb213eG5YSU5ETE1XNFZma1pndmxWaGpzUzlPaQ";
       // plan
       // $plan_audience = "https://plan-cuttingroom.api.mylectra.com";
       // $resource_key = "RES_PLAN";

       // prepare
       $prepare_audience = "https://custom-offer-definition-connector.mylectra.com";
       $resource_key = "RES_PREPARE";

       $token = LectraAuthService::getToken($credentials, $prepare_audience, $resource_key);

       if($token != ""){
           $end_res["success"] = 1;
           $end_res["data"]["access_token"] = $token;
       }

       return $end_res;
    }


    public function materials_get(){

        //return;

        $end_res=array();
        $end_res["success"]=0;
        $end_res["error"]="";
        $end_res["data"]=array();

        $end_res_auth = $this->do_auth();
        if($end_res_auth["success"]==1){

            Dbg::d("end_res_auth",$end_res_auth,1);
            $this->access_token=$end_res_auth["data"]["access_token"];
            $headers=array();
            $headers[]="Authorization: Bearer ".$this->access_token;
            $headers[]="x-api-version: 4.0";
            $headers[]="Content-Type: application/json";
            $this->api_caller->resetHeader();
            $this->api_caller->setHeader($headers);
            Dbg::d("headers",$headers,1);
            Dbg::d("path",$this->api_path_plan,1);
            $res_call= $this->api_caller->callAPI("GET", $this->api_path_plan);
            Dbg::d("api_caller ",$this->api_caller,1);
            Dbg::d("res_call ",$res_call,1);

           // echo($res_call);

            $res_call = json_decode($res_call);
            //$params["audience"]="https://plan-cuttingroom.api.mylectra.com";
            return $end_res;

        }
        return $end_res;
        Dbg::d("materials get","materials get",1);
    }
    /**
     * Aggiorna i rotoli sul sistema di lectra
     * @return
     */
    public function rolls_put(){
        // return;
        Dbg::d("rolls put","rolls put",1);
        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();
        /*$sql="SELECT * FROM roll LIMIT 1";*/
        $sql="SELECT roll.*,
                    materiali.motif_type
                FROM
                    batch,
                    roll,
                    materiali
                WHERE
                    roll.batch_reference = batch.reference
                    AND materiali.reference=batch.materials_reference AND roll.id;";

        $res = $this->db->query($sql);
        Dbg::d("RES",$res,1);
        Dbg::d("RES",count($res),1);
        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();
        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){
                    /*
                       {
                        "reference": "K-001-C",
                        "batchReference": "Batch reference 1",
                        "length": 15.67,
                        "sequenceNumber": 3,
                        "shrinkageX": 1,
                        "shrinkageY": 0,
                        "visibleSide": "Undefined",
                        "materialDirection": "Normal",
                        "plainMaterialInfo": {
                          "width": 1.4
                        },
                        "weftStripeInfo": {
                          "width": 1.4,
                          "weftOffset": 0.07,
                          "weftStep": 0.1
                        },
                        "warpStripeInfoDefiningStepNumber": {
                          "width": 1.38,
                          "stepsNumber": 20,
                          "warpOffset": 0.015,
                          "remaining": 0.007,
                          "weftStep": 0.02,
                          "weftOffset": 0.015
                        },
                        "warpStripeInfoDefiningWidth": {
                          "width": 1.35,
                          "weftStep": 0,
                          "weftOffset": 0
                        },
                        "warpStripeInfoDefiningStep": {
                          "width": 1.4,
                          "warpStep": 0.2,
                          "warpOffset": 0.015,
                          "weftStep": 0,
                          "weftOffset": 0
                        },
                        "status": "Available"
                      }

                    */
                    $data = array();
                    $data["reference"] = $row->reference;
                    $data["batchReference"] = $row->batch_reference;
                    $data["length"] = $row->length;
                    $data["sequenceNumber"] = $row->sequence_number;
                    $data["shrinkageX"] = NULL;
                    $data["shrinkageY"] = NULL;
                    $data["visibleSide"] = NULL;
                    $data["materialDirection"] = NULL;
                    if($row->motif_type=='None'){
                        $data["plainMaterialInfo"]["width"] = $row->width;
                    }

                    $data["status"]=NULL;
                    /*
                    $data["weftStripeInfo"]["width"] = NULL;
                    $data["weftStripeInfo"]["weftOffset"] =NULL;
                    $data["weftStripeInfo"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["width"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["stepsNumber"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["warpOffset"] =0;
                    $data["warpStripeInfoDefiningStepNumber"]["remaining"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["weftStep"] =0;
                    $data["warpStripeInfoDefiningStepNumber"]["weftOffset"] =NULL;
                    $data["warpStripeInfoDefiningWidth"]["width"] =0;
                    $data["warpStripeInfoDefiningWidth"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningWidth"]["weftOffset"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["width"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["warpStep"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["warpOffset"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["weftOffset"] =NULL;
                    */
                    //$params= json_encode($data);
                    error_log("start", $data["reference"],1 );
                    //$params=  json_encode($data,JSON_UNESCAPED_SLASHES);
                    $params = json_encode($data,JSON_NUMERIC_CHECK);
                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);
                    //$path=$this->api_path_plan."rolls";
                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    Dbg::d("params",$params,1);
                    $res_call = $this->api_caller->callAPI("PUT", $this->api_path_plan . $row->reference, $params);
                    Dbg::d("api_caller ",$this->api_caller,1);
                    $res_call = json_decode($res_call);
                    Dbg::d("res_call ",$res_call,1);
                    Dbg::d("res_call_ref ",isset($res_call->reference),1);
                    if(!isset($res_call->reference)){
                        Dbg::d("res_call2 ",$res_call,1);
                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;
                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }
                    sleep(2);
                    error_log("end", $data["reference"],1 );
                }
            }
        }
        Dbg::d("end res end",$end_res,1);
        return $end_res;
    }






    public function batches_delete(){

        return;




        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();


        $sql="SELECT * FROM batch";


        $res = $this->db->query($sql);

        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();


        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){

                    $data = array();

                    $data["reference"]  = $row->reference;

                    $params=  json_encode($data,JSON_NUMERIC_CHECK);

                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);

                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    Dbg::d("params",$params,1);


                    $res_call = $this->api_caller->callAPI("DELETE", $this->api_path_plan . $row->reference, $params);

                    Dbg::d("api_caller ",$this->api_caller,1);

                    $res_call = json_decode($res_call);


                    Dbg::d("res_call ",$res_call,1);

                    Dbg::d("res_call_ref ",isset($res_call->reference),1);

                    if(!isset($res_call->reference)){

                        Dbg::d("res_call2 ",$res_call,1);

                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;

                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }

                    sleep(2);
                    error_log("end", $data["reference"],1 );

                }
            }
        }





        return;


    }



    public function rolls_delete(){


        return;

        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();


        $sql="SELECT * FROM roll WHERE id >= 66 AND id < 318;";


        $res = $this->db->query($sql);

        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();


        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){

                    $data = array();

                    $data["reference"]  = $row->reference;

                    $params=  json_encode($data,JSON_NUMERIC_CHECK);

                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);

                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    Dbg::d("params",$params,1);

                    //return;
                    $res_call = $this->api_caller->callAPI("DELETE", $this->api_path_plan .str_replace(' ', '%20', $row->reference), $params);

                    Dbg::d("api_caller ",$this->api_caller,1);

                    $res_call = json_decode($res_call);


                    Dbg::d("res_call ",$res_call,1);

                    Dbg::d("res_call_ref ",isset($res_call->reference),1);

                    if(!isset($res_call->reference)){

                        Dbg::d("res_call2 ",$res_call,1);

                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;

                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }

                    sleep(2);
                    error_log($row->id);
                    error_log($row->reference);

                }
            }
        }


             return;



    }
    /**
     * Aggiunge i nuovi rotoli al sistema di Lectra
     * Per ora modificare l'id (roll.id) per recuperare solo i nuovi rotoli da aggiungere
     * @return array
     */
    public function rolls_post(){
        // return;
        Dbg::d("rolls post","rolls post",1);
        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();
        /*$sql="SELECT * FROM roll LIMIT 1";*/
        $sql="SELECT roll.*,
                    materiali.motif_type
                FROM
                    batch,
                    roll,
                    materiali
                WHERE
                    roll.batch_reference = batch.reference
                    AND materiali.reference=batch.materials_reference AND batch_reference IN ( '25025_LUTHAI', '25026_LUTHAI', '25027_LUTHAI' )";

        $res = $this->db->query($sql);
        Dbg::d("RES",$res,1);
        Dbg::d("RES",count($res),1);
        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();
        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){
                  /*
                     {
                      "reference": "K-001-C",
                      "batchReference": "Batch reference 1",
                      "length": 15.67,
                      "sequenceNumber": 3,
                      "shrinkageX": 1,
                      "shrinkageY": 0,
                      "visibleSide": "Undefined",
                      "materialDirection": "Normal",
                      "plainMaterialInfo": {
                        "width": 1.4
                      },
                      "weftStripeInfo": {
                        "width": 1.4,
                        "weftOffset": 0.07,
                        "weftStep": 0.1
                      },
                      "warpStripeInfoDefiningStepNumber": {
                        "width": 1.38,
                        "stepsNumber": 20,
                        "warpOffset": 0.015,
                        "remaining": 0.007,
                        "weftStep": 0.02,
                        "weftOffset": 0.015
                      },
                      "warpStripeInfoDefiningWidth": {
                        "width": 1.35,
                        "weftStep": 0,
                        "weftOffset": 0
                      },
                      "warpStripeInfoDefiningStep": {
                        "width": 1.4,
                        "warpStep": 0.2,
                        "warpOffset": 0.015,
                        "weftStep": 0,
                        "weftOffset": 0
                      },
                      "status": "Available"
                    }

                             *  */
                    $data = array();
                    $data["reference"]  = $row->reference;
                    $data["batchReference"]  = $row->batch_reference;
                    $data["length"]  =  $row->length;
                    $data["sequenceNumber"]  =  $row->sequence_number;
                    $data["shrinkageX"]  =  NULL;
                    $data["shrinkageY"]  =  NULL;
                    $data["visibleSide"]  =  NULL;
                    $data["materialDirection"]  =  NULL;
                    if($row->motif_type=='None'){
                        $data["plainMaterialInfo"]["width"] = $row->width;
                    }
                    $data["status"]=NULL;
                    /*
                    $data["weftStripeInfo"]["width"] = NULL;
                    $data["weftStripeInfo"]["weftOffset"] =NULL;
                    $data["weftStripeInfo"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["width"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["stepsNumber"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["warpOffset"] =0;
                    $data["warpStripeInfoDefiningStepNumber"]["remaining"] =NULL;
                    $data["warpStripeInfoDefiningStepNumber"]["weftStep"] =0;
                    $data["warpStripeInfoDefiningStepNumber"]["weftOffset"] =NULL;

                    $data["warpStripeInfoDefiningWidth"]["width"] =0;
                    $data["warpStripeInfoDefiningWidth"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningWidth"]["weftOffset"] =NULL;

                    $data["warpStripeInfoDefiningStep"]["width"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["warpStep"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["warpOffset"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["weftStep"] =NULL;
                    $data["warpStripeInfoDefiningStep"]["weftOffset"] =NULL;
                    */
                    //$params= json_encode($data);
                    error_log("start", $data["reference"],1 );
                    //$params=  json_encode($data,JSON_UNESCAPED_SLASHES);
                    $params=  json_encode($data,JSON_NUMERIC_CHECK);
                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);
                    //$path=$this->api_path_plan."rolls";
                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    Dbg::d("params",$params,1);
                    $res_call= $this->api_caller->callAPI("POST", $this->api_path_plan,$params);
                    Dbg::d("api_caller ",$this->api_caller,1);
                    $res_call = json_decode($res_call);
                    Dbg::d("res_call ",$res_call,1);
                    Dbg::d("res_call_ref ",isset($res_call->reference),1);
                    if(!isset($res_call->reference)){
                        Dbg::d("res_call2 ",$res_call,1);
                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;
                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }
                    error_log($row->id);
                    error_log($row->reference);
                    sleep(2);
                }
            }
        }
        Dbg::d("end res end",$end_res,1);
        return $end_res;
        Dbg::d("materials post","materials post end",1);
        return;
    }






    public function batches_post(){


        return;


        Dbg::d("rolls post","rolls post",1);

        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();

        $sql="SELECT * FROM batch WHERE id>=63;";
        $res = $this->db->query($sql);

        Dbg::d("RES",$res,1);
        Dbg::d("RES",count($res),1);



        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();

        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){
                    Dbg::d("aaa","bbb",1);
                    Dbg::d("row",$row,1);


/*
                    {
                       "reference": "K-001-C",
                      "materialReference": "K-001",
                      "supplier": "AZIM LTD",
                      "bath": "F",
                      "presentation": "SinglePly",
                      "customerReservation": "TheCustomer",
                      "visibleSide": "Undefined",
                      "materialDirection": "Undefined",
                      "shrinkageX": 1.3,
                      "shrinkageY": -2.1,
                      "width": 1.35,
                      "length": 33,
                      "weight": 12.34
                    }
*/

                    $data = array();

                    $data["reference"]  = $row->reference;
                    $data["materialReference"]  = $row->materials_reference;
                    $data["supplier"]  =  $row->supplier;
                    $data["bath"]  =  "";
                    $data["presentation"]  =  "SinglePly";
                    $data["customerReservation"]  =  "";
                    $data["visibleSide"]  =  "Undefined";
                    $data["materialDirection"]  =  "Undefined";
                    $data["shrinkageX"]  =  0;
                    $data["shrinkageY"]  =  0;
                    $data["width"]  =  NULL;
                    $data["length"]  =  NULL;
                    $data["weight"]  =  NULL;


                    $params= json_encode($data);

                    error_log("start", $data["reference"],1 );


                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);


                    //$path=$this->api_path_plan."batches";

                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    Dbg::d("params",$params,1);



                    $res_call= $this->api_caller->callAPI("POST", $this->api_path_plan,$params);



                    Dbg::d("api_caller ",$this->api_caller,1);

                    $res_call = json_decode($res_call);


                    Dbg::d("res_call ",$res_call,1);

                    Dbg::d("res_call_ref ",isset($res_call->reference),1);

                    if(!isset($res_call->reference)){

                        Dbg::d("res_call2 ",$res_call,1);

                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;

                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }

                    sleep(2);
                    error_log("end", $data["reference"],1 );

                }

            }

        }


        Dbg::d("end res end",$end_res,1);
        return $end_res;
        Dbg::d("materials post","materials post end",1);
        return;
    }



    public function materials_sets_post(){



        Dbg::d("materials_sets_post1","__",1);

        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();

        $sql="SELECT * FROM materiali;";
        $res = $this->db->query($sql);

        Dbg::d("RES",$res,1);

        Dbg::d("RES",count($res),1);
        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();


        Dbg::d("end_res_auth1",$end_res_auth,1);


        $count=0;

        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){
                    Dbg::d("aaa","bbb",1);

                    Dbg::d("row",$row,1);

                    $data = array();
                    $data["reference"]  = $row->reference;
                    $params= json_encode($data);

                    Dbg::d("end_res_auth",$end_res_auth,1);
                    Dbg::d("data",$data,1);


                    /*$count++;
                    if($count>1) {
                        break;
                    }*/


                    $mat_set_path=$this->api_path_prepare.$row->reference."/material-dependencies";


                    Dbg::d("mat_set_pa",$mat_set_path,1);
                    Dbg::d("params",$params,1);


                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);
                    Dbg::d("headers",$headers,1);
                    $res_call= $this->api_caller->callAPI("POST", $this->api_path_prepare,$params);
                    Dbg::d("api_caller ",$this->api_caller,1);
                    $res_call = json_decode($res_call);
                    Dbg::d("res_call ",$res_call,1);
                    Dbg::d("res_call_ref ",isset($res_call->reference),1);

                    if(!isset($res_call->reference)){
                        Dbg::d("res_call2 ",$res_call,1);
                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;
                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }


                    exit;

                    sleep(2);
                    error_log("end", $data["reference"],1 );

                }
            }

        }

        return;
    }



    public function materials_put()
    {

        return;
        $end_res = array();
        $end_res["success"] = 1;
        $end_res["error"] = "";
        $end_res["data"] = array();

        $sql = "SELECT * FROM materiali";
        $res = $this->db->query($sql);

        Dbg::d("RES", $res, 1);

        Dbg::d("RES", count($res), 1);
        $this->api_caller = new APICaller();
        $end_res_auth = $this->do_auth();

        if ($end_res_auth["success"] == 1) {
            if ($res) {
                foreach ($res as $row) {

                    Dbg::d("aaa", "bbb", 1);
                    Dbg::d("row", $row, 1);

                    $data = array();
                    $data = array();
                    $data["reference"] = $row->reference;
                    $data["name"] = $row->nature . " " . $row->color;
                    $data["color"] = $row->color;
                    $data["comment"] = $row->composition;
                    $data["shrinkageX"] = 0;
                    $data["shrinkageY"] = 0;
                    $data["areaDensity"] = null;
                    $data["thickness"] = null;
                    $data["defaultPresentation"] = "SinglePly";
                    $data["defaultWidth"] = null;
                    $data["nestingFamilyReference"] = "NOROT";
                    $data["cuttingFamilyReference"] = "Standard";
                    $data["qualityZonesFamilyReference"] = null;
                    $data["motifType"] = $row->motyf_type;
                    $data["motifCategory"] = null;
                    $data["hasVisibleBow"] = false;

                    $params = json_encode($data);

                    error_log("start", $data["reference"], 1);

                    Dbg::d("end_res_auth", $end_res_auth, 1);
                    $this->access_token = $end_res_auth["data"]["access_token"];
                    $headers = array();
                    $headers[] = "Authorization: Bearer " . $this->access_token;
                    $headers[] = "x-api-version: 4.0";
                    $headers[] = "Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);


                    Dbg::d("headers", $headers, 1);
                    Dbg::d("path", $this->api_path_plan, 1);
                    Dbg::d("params", $params, 1);

                   // continue;
                    //$res_call= $this->api_caller->callAPI("PUT", $this->api_path_plan,$params);
                    $res_call = $this->api_caller->callAPI("PUT", $this->api_path_plan . $row->reference, $params);

                    Dbg::d("api_caller ", $this->api_caller, 1);


                    $res_call = json_decode($res_call);

                    Dbg::d("res_call ", $res_call, 1);
                    Dbg::d("res_call_ref ", isset($res_call->reference), 1);

                    if (!isset($res_call->reference)) {

                        Dbg::d("res_call2 ", $res_call, 1);

                        foreach ($res_call as $item) {
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"] = 0;
                            $end_res["error"] .= "errore item" . $data["reference"] . " - error code" . $item->code . " - " . $item->message;

                            Dbg::d("end res", $end_res, 1);
                            return $end_res;
                            //   }
                        }
                    }


                    sleep(2);
                    error_log("end", $data["reference"], 1);

                }

            }





            Dbg::d("end res end", $end_res, 1);
            return $end_res;
            Dbg::d("materials post", "materials post end", 1);
        }


    }


    public function materials_post(){

        return;

        /* OK

        $end_res=array();
        $end_res["success"]=1;
        $end_res["error"]="";
        $end_res["data"]=array();

        $sql="SELECT * FROM material_import;";
        $res = $this->db->query($sql);

        Dbg::d("RES",$res,1);

        Dbg::d("RES",count($res),1);
        $this->api_caller= new APICaller();
        $end_res_auth = $this->do_auth();

        if($end_res_auth["success"]==1){
            if($res){
                foreach ($res as $row){
                    Dbg::d("aaa","bbb",1);

                    Dbg::d("row",$row,1);

                    $data = array();
                    $data["reference"]  = $row->reference;
                    $data["name"]  = $row->name;
                    $data["color"]  =  $row->color;
                    //$data["comment"]  =  "composition:".$row->composition." hscode:".$row->hs_code." finishing:".$row->finishing." made in:".$row->made_in." article code:".$row->article_code;
                    $data["comment"]  =  "aaaa";
                    $data["shrinkageX"]  = round($row->shrinkageX);
                    $data["shrinkageY"]  = round($row->shrinkageY);
                    $data["areaDensity"]  = null;
                    $data["thickness"]  = null;
                    $data["defaultPresentation"]  = "SinglePly";
                    $data["defaultWidth"]  = null;
                    $data["nestingFamilyReference"]  = null;
                    $data["cuttingFamilyReference"]  = null;
                    $data["qualityZonesFamilyReference"]  = null;
                    $data["motifType"]  =  null;
                    $data["motifCategory"]  =  null;
                    $data["hasVisibleBow"]  =  false;


                    $params= json_encode($data);

                    error_log("start", $data["reference"],1 );


                    Dbg::d("end_res_auth",$end_res_auth,1);
                    $this->access_token=$end_res_auth["data"]["access_token"];
                    $headers=array();
                    $headers[]="Authorization: Bearer ".$this->access_token;
                    $headers[]="x-api-version: 4.0";
                    $headers[]="Content-Type: application/json";
                    $this->api_caller->resetHeader();
                    $this->api_caller->setHeader($headers);
                    Dbg::d("headers",$headers,1);
                    Dbg::d("path",$this->api_path_plan,1);
                    $res_call= $this->api_caller->callAPI("POST", $this->api_path_plan,$params);
                    Dbg::d("api_caller ",$this->api_caller,1);

                    $res_call = json_decode($res_call);


                    Dbg::d("res_call ",$res_call,1);

                    Dbg::d("res_call_ref ",isset($res_call->reference),1);

                    if(!isset($res_call->reference)){

                        Dbg::d("res_call2 ",$res_call,1);

                        foreach ($res_call as $item ){
                            // if($item->code!=200){
                            // if($item->code!=200){
                            $end_res["success"]=0;
                            $end_res["error"].="errore item".$data["reference"]." - error code". $item->code." - ". $item->message;

                            Dbg::d("end res",$end_res,1);
                            return $end_res;
                            //   }
                        }
                    }



                    sleep(2);
                    error_log("end", $data["reference"],1 );

                }

            }

        }




        Dbg::d("end res end",$end_res,1);
        return $end_res;
        Dbg::d("materials post","materials post end",1);
         */
    }


    function get_dati_pagina_cancella_ordini(){

        $capo_area= $_SESSION["codcom"];
        //$capo_area= 4350;
        $end_res = (array)new EndRes(1,'');

        $sql="SELECT
                TRIM(INT(TEXFAN)) AS CODMEC,
                TRIM(substr(KEYFTA, 4)) AS CODNEG,
                TRIM(desfan) AS NOMI,
                AGECAN AS CODHEAD,
                TRIM(substring(FILTAB,4,30)) AS NEGOZI,
                TRIM(substring(filtab, 120, 2)) AS MAGAZZINI
            FROM
                AZ101.FTABPF, AZGRPCAMI.CANAPF, AZGRPCAMI.UTMAGPF, AZGRPCAMI.FANAPF
            WHERE
                KEYFTA like 'S%'
                AND substr(FILTAB, 1, 1) <> '1'
                AND TRIM(substr(FILTAB, 99, 6)) = CODCAN
                AND TRIM(substr(KEYFTA, 4)) not like '7%'
                AND TRIM(substr(KEYFTA, 4)) = NMAUTM
                AND AGECAN = CODFAN
                AND DCHUTM = 0
                AND substr(FILTAB, 120, 2 ) <> 'E'
                AND TRIM(L '0' FROM TEXFAN) = '".$capo_area."'";

        $res= $this->as400->query($sql);


        Dbg::d("sql",$sql,1);


        Dbg::d("res",$res,1);

        $shops = array();

        if($res){
            foreach ($res as $row){
                $shops[$row->CODNEG]["nome_negozio"] = $row->NEGOZI;
            }
        }


        //Dbg::d("shops",$shops,1);
        $sql="SELECT
                    codcom, nome, cognome, sede_lavoro
                FROM
                    attendance.user
                WHERE
                    sede_lavoro IN('".implode("','",array_keys($shops))."')";

        Dbg::d("sql shops",$sql,1 );

        $res = $this->db_ind->query($sql);

        Dbg::d("rese",$res,1 );

        $commessi= array();
        if($res){
            foreach ($res as $row){

                $shops[$row->sede_lavoro]["commessi"][$row->codcom] = $row->nome." ".$row->cognome;

                if(!in_array($row->codcom,$commessi)){
                    $commessi[]= $row->codcom;
                }
            }
        }



        $order_ids = array();
        $sql="SELECT
                id
            FROM
                orders
            WHERE
                codcom
                IN ('".implode("','",$commessi)."')";

        $res = $this->db->query($sql);
        foreach ($res as $row){
            $order_ids[] = $row->id;
        }


        Dbg::d("orders id",$order_ids,1);

        $dati_ordini = $this->get_dati_ordine($order_ids);



        foreach ($dati_ordini as $key_ord =>$val_ord){

            Dbg::d("val order", $val_ord,1);

            Dbg::d("val order sales point", $val_ord["sales_point"],1);
            Dbg::d("order id", $val_ord["order_id"],1);

            $pdf_ordine_filename= "ordine_".$key_ord.".pdf";
            $shops[$val_ord["sales_point"]]["ordini"][$val_ord["order_id"]]["data"] = $val_ord;
            $shops[$val_ord["sales_point"]]["ordini"][$val_ord["order_id"]]["pdf"] = file_exists($this->pdf_path.$pdf_ordine_filename)? $this->pdf_ext_path.$pdf_ordine_filename: "FILE_NON_ESISTENTE";



        }



        Dbg::d("sql4",$sql,1);
        Dbg::d("res",$res,1 );

        Dbg::d("commessi", $commessi,1);
        Dbg::d("shops3",$shops,1);

        $end_res["data"] = $shops;

        return $end_res;


    }

}







?>
