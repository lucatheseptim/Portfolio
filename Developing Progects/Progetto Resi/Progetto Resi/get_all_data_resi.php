<?php

//IMPORTO LA LIBRERIA CHE CONTIENE LA CONNESSIONE AL DATABSE 
require_once("base.php");
//IMPORTO IL FILE FACTORY LIB
require_once("factorylib.php"); 

//verifico tutti i dati ricevuti dal Client
$libreria = $_POST["libreria"]; // ottengo la libreria
$data_da = $_POST["data_da"]; //ottengo la data da 
$data_a = $_POST["data_a"];  // ottengo la data a 
$negozi = $_POST["negozi"]; //ottengo il negozio o i negozi 
$capoarea = $_POST["capoarea"];  //ottengo il capo area 
$localita = $_POST["localita"];  //ottengo la località
$progressivo_vendita = $_POST["numero_progressivo_vendita"]; 
$codice_magazzino_filtrato = $_POST["codice_magazzino_singolo_filter"];
 
if(empty($capoarea)){
 
    //non c'è il capo area 
    $capoarea_check = false;   

}else{ 

    //c'è il capo area 
    $capoarea_check = true; 
}


 
error_log(print_r($negozi, 1)); // stampo sul server tutte le informazioni 


//PULIZIA DATE
$data_da_p = str_replace("-","",$data_da);  // stampo la data_da
$data_a_p = str_replace("-","",$data_a);   // stampo la data_a  

error_log($data_a_p);
error_log($data_da_p);


//INVERTO LE DATE PER IL DATABASE 
$giorno_da = substr($data_da_p,0,2); 
$mese_da = substr($data_da_p,2,2); 
$anno_da = substr($data_da_p,4,6); 


error_log($giorno_da); 
error_log($mese_da);  
error_log($anno_da); 


$data_da = $anno_da.$mese_da.$giorno_da;


$giorno_a = substr($data_a_p,0,2); 
$mese_a = substr($data_a_p,2,2); 
$anno_a = substr($data_a_p,4,6); 


error_log($giorno_a); 
error_log($mese_a);  
error_log($anno_a); 


$data_a = $anno_a.$mese_a.$giorno_a;


error_log($data_da); 
error_log($data_a); 
 

//PULIZIA LOCALITA
$localita = trim($localita); // tolgo eventuali spazi 


$factory = factoryLIbrary::create($libreria); 
$az = $factory->az;  

$factory_azgrpcami = factoryLIbrary::create($libreria); 
$az_grpcami = $factory_azgrpcami->azgrp; 

//error_log("il valore della libreria è : ".$az);  

$negozi_all = array(); 
$count_negozio = 0;  

//creo un array associativo per ogni negozio 

foreach($negozi as $negozio){

    //error_log($negozio); 
    $negozi_all[$count_negozio] = $negozio; 
    $count_negozio ++;  
     
} 

 
 
//error_log(print_r($negozi_all,1));  
if(($localita == "all_city" || $localita != "all_city") && $progressivo_vendita == 'null' && $capoarea_check == true ){ 

    error_log("ENTRA NEL CASO IN CUI C'E' LA CITTA' O TUTTE ' , IL CAPOAREA , GLI ALTRI FILTRI E PROGRESSIVO VENDITA => NULL ");

    //QUERY UNO PER ESTRAPOLAZIONE NEGOZI SINGOLI PER PUNTO VENDITA PER LIBRERIA CON CAPO AREA, LOCALITA' E NEGOZI
    /*TABELLA INTERMEDIA */
    
    
    $query_s = "SELECT NMAMT2 AS CODICE_MAGAZZINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , DSCMT2 as DATA_SCONTRINO , NGGMTR AS GIORNI_TRASCORSI_RESO  , DSVMTR as DATA_VENDITA_ORIGINE ,TSCMT1 AS TOTALE_SCONTRINO , CDIMT2 AS DIVISA". 
    " FROM ".$az.".MDT2PF".
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR".
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR". 
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))".
    " INNER JOIN ".$az.".MDT1PF ON ".$az.".MDT1PF.NUMMT1 = ".$az.".MDT2PF.NUMMT2 ".  
	" AND ".$az.".MDT1PF.NMAMT1 = ".$az.".MDT2PF.NMAMT2 ". 
	" AND ".$az.".MDT1PF.DSCMT1 = ".$az.".MDT2PF.DSCMT2 " .
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN ".$data_da." AND ".$data_a." AND NMAMT2 IN (".implode(",",$negozi).")".
    " AND  KEYFTA LIKE 'S%' AND AGECAN = ".$capoarea."". 
    " GROUP BY NMAMT2 , NUMMT2 , NGGMTR , DSCMT2 , SUBSTRING(FILTAB,4, 30) , DSVMTR , TSCMT1 , CDIMT2".
    " ORDER BY NMAMT2, NUMMT2 , NGGMTR , DSCMT2 , SUBSTRING(FILTAB , 4, 30) , DSVMTR , TSCMT1 , CDIMT2";

    error_log( "la query prodotta è" .$query_s);  //STAMPO LATO BACKEND LA QUERY 


    //QUERY PER ESTRAPOLAZIONE NEGOZI RAGGRUPPATI PER PUNTO VENDITA PER LIBRERIA (MEDIA) CON CAPO AREA , LOCALITA' E NEGOZI 
    /*TABELLA INIZIALE */


    $query_r =  "SELECT NMAMTR AS CODICE_MAGAZZINO ,SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO  , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI,".
    " AVG(s.NGGMTR) AS MEDIA_RESI_SCONTRINO , SUM(s.NGGMTR) AS SOMMA_GIORNI_RESI_NEGOZIO" .
    " FROM ( SELECT NMAMT2 ,NMAMTR, NUMMTR, NGGMTR" . 
    "  FROM  ".$az.".MDTRPF AS sr INNER JOIN ".$az.".MDT2PF AS s2 ON ( NMAMT2 = NMAMTR AND  NUMMT2 = NUMMTR AND  NRIMT2 = NRIMTR )".  
    "  WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND TRIMT2 ='R' ".
        " GROUP BY NMAMT2,NMAMTR, NUMMTR , NGGMTR " . 
    ") AS s ".  
    " INNER JOIN ".$az.".FTABPF ON INT(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = s.NMAMT2 " . 
    " INNER JOIN ".$az_grpcami.".CANAPF ON AZGRPCAMI.CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB, 99, 6)".
    " WHERE KEYFTA LIKE 'S%' AND AGECAN = ".$capoarea." AND NMAMT2 IN (".implode(",",$negozi).")". 
    " GROUP BY ".
    "    NMAMTR , SUBSTRING(FILTAB,4, 30) ".
    " ORDER BY NMAMTR , SUBSTRING(FILTAB,4, 30)";

    /*
    
    $query_r = "SELECT NMAMT2 AS CODICE_MAGAZZINO,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI, AVG(NGGMTR) AS MEDIA_RESI_SCONTRINO".
    " FROM ".$az.".MDT2PF".  
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR". 
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR".
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))". 
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND NMAMT2 IN (".implode(",",$negozi).") ".
    " AND KEYFTA LIKE 'S%' AND AGECAN = ".$capoarea."".
    "GROUP BY NMAMT2 , SUBSTRING(FILTAB,4, 30)";

    */

    error_log($query_r);  //STAMPO LA QUERY LATO SERVER 



    //CONNESSIONE AL DATABASE AS400  
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400); 

    $count_query_s = 0;  
    $count_query_r = 0; 
    $negozi_singoli = array(); //ARRAY ASSOCIATIVO 
    $negozi_raggruppati = array(); //ARRAY ASSOCIATIVO 



    foreach($as400->query($query_r) as $row){


        //PRENDO LA MEDIA DEGLI SCONTRINI DA DATABASE 
        //$media_scontrini_full = $row->MEDIA_RESI_SCONTRINO; 
        //$media_scontrini = number_format($media_scontrini_full , 2,".",""); // oppure si poteva fare cosi 
        //$media_scontrini = round($media_scontrini_full, 2 , PHP_ROUND_HALF_UP);// mi calcola in automatico la media per difetto o eccesso 
        //error_log("la media del negozio è : ".$media_scontrini); // media degli scontrini  
        
        $negozi_raggruppati[$count_query_r] = $row; 

        error_log(print_r($negozi_raggruppati,1)); 
        $count_query_r = $count_query_r +1;  
    }
    

 
    //dichiaro una variabile di negozi singoli  ,
    //un array associativo
    foreach($as400->query($query_s) as $row){ 

        //error_log(print_r($row,1)); 
        $negozi_singoli[$count_query_s] = $row;  // mi stampo la riga contenente tutti i dati
        $count_query_s = $count_query_s +1; 
    }

    

    //RISPEDISCO AL CLIENT I DATI 
    echo json_encode(array("negozi_raggruppati"=> $negozi_raggruppati,"negozi_singoli"=> $negozi_singoli)); 

 
 

}else if(($localita == "all_city" || $localita != "all_city") && $progressivo_vendita == 'null' && !$capoarea_check) { //se ho selezionato una città 

    error_log("ENTRA NEL CASO IN CUI C'E' la citta O TUTTE  , TUTTI I CAPI AREA, E I TUTTI I NEGOZI DELLA LOCALITA , E PROGRESSIVO VENDITA => NULL ");
    
    //QUERY UNO PER ESTRAPOLAZIONE NEGOZI SINGOLI PER PUNTO VENDITA PER LIBRERIA CON CAPO AREA, LOCALITA' E NEGOZI
    /*TABELLA INTERMEDIA */
    
    $query_s = "SELECT NMAMT2 AS CODICE_MAGAZZINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , DSCMT2 as DATA_SCONTRINO , NGGMTR AS GIORNI_TRASCORSI_RESO  , DSVMTR as DATA_VENDITA_ORIGINE ,TSCMT1 AS TOTALE_SCONTRINO ,CDIMT2 AS DIVISA ". 
    " FROM ".$az.".MDT2PF".
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR".
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR".  
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))".
    " INNER JOIN ".$az.".MDT1PF ON ".$az.".MDT1PF.NUMMT1 = ".$az.".MDT2PF.NUMMT2 ".  
	" AND ".$az.".MDT1PF.NMAMT1 = ".$az.".MDT2PF.NMAMT2 ". 
	" AND ".$az.".MDT1PF.DSCMT1 = ".$az.".MDT2PF.DSCMT2 " .
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN ".$data_da." AND ".$data_a." AND NMAMT2 IN (".implode(",",$negozi).")".
    " AND  KEYFTA LIKE 'S%' /*AND SUBSTRING(FILTAB,64,30) = '".$localita."' */ ". 
    " GROUP BY NMAMT2 , NUMMT2 , NGGMTR , DSCMT2 , SUBSTRING(FILTAB,4, 30) , DSVMTR , TSCMT1 , CDIMT2 ".
    " ORDER BY NMAMT2, NUMMT2 , NGGMTR , DSCMT2 , SUBSTRING(FILTAB , 4, 30) , DSVMTR , TSCMT1 , CDIMT2 "; 

  
 
  
    error_log( "la query prodotta è" .$query_s);  //STAMPO LATO BACKEND LA QUERY 


    //QUERY PER ESTRAPOLAZIONE NEGOZI RAGGRUPPATI PER PUNTO VENDITA PER LIBRERIA (MEDIA) CON CAPO AREA , LOCALITA' E NEGOZI 
    /*TABELLA INIZIALE*/

    $query_r =  "SELECT NMAMTR AS CODICE_MAGAZZINO ,SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO  , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI,".
    " AVG(s.NGGMTR) AS MEDIA_RESI_SCONTRINO , SUM(s.NGGMTR) AS SOMMA_GIORNI_RESI_NEGOZIO ".
    " FROM ( SELECT NMAMT2 ,NMAMTR, NUMMTR, NGGMTR " . 
    "  FROM  ".$az.".MDTRPF AS sr INNER JOIN ".$az.".MDT2PF AS s2 ON ( NMAMT2 = NMAMTR AND  NUMMT2 = NUMMTR AND  NRIMT2 = NRIMTR )".  
    "  WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND TRIMT2 ='R' ".
        " GROUP BY NMAMT2,NMAMTR, NUMMTR , NGGMTR " . 
    ") AS s ".  
    " INNER JOIN ".$az.".FTABPF ON INT(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = s.NMAMT2 " . 
    " WHERE KEYFTA LIKE 'S%' AND SUBSTRING(FILTAB,64,30)= '".$localita."' AND NMAMT2 IN (".implode(",",$negozi).")". 
    " GROUP BY ".
    "    NMAMTR , SUBSTRING(FILTAB,4, 30) ".
    " ORDER BY NMAMTR , SUBSTRING(FILTAB,4, 30)";


    /*
    $query_r = "SELECT NMAMT2 AS CODICE_MAGAZZINO,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI, AVG(NGGMTR) AS MEDIA_RESI_SCONTRINO".
    " FROM ".$az.".MDT2PF".  
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR". 
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR".
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))". 
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND NMAMT2 IN (".implode(",",$negozi).") ".
    " AND KEYFTA LIKE 'S%' AND SUBSTRING(FILTAB,64,30)= '".$localita."' ".
    "GROUP BY NMAMT2 , SUBSTRING(FILTAB,4, 30)";

    */


    error_log($query_r);  //STAMPO LA QUERY LATO SERVER 


    //CONNESSIONE AL DATABASE AS400  
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400); 

    $count_query_s = 0;  
    $count_query_r = 0; 
    $negozi_singoli = array(); //ARRAY ASSOCIATIVO 
    $negozi_raggruppati = array(); //ARRAY ASSOCIATIVO 



    foreach($as400->query($query_r) as $row){
        
        $negozi_raggruppati[$count_query_r] = $row; 

        error_log(print_r($negozi_raggruppati,1)); 
        $count_query_r = $count_query_r +1;  
    }
    


    //dichiaro una variabile di negozi singoli  ,
    //un array associuativo
    foreach($as400->query($query_s) as $row){ 

        //error_log(print_r($row,1)); 
        $negozi_singoli[$count_query_s] = $row;  // mi stampo la riga contenente tutti i dati
        $count_query_s = $count_query_s +1; 
    }

    

    //RISPEDISCO AL CLIENT I DA5TI 
    echo json_encode(array("negozi_raggruppati"=> $negozi_raggruppati,"negozi_singoli"=> $negozi_singoli)); 
 


  
/*TERZO CASO TABELLA FINALE SPECIFICA-> SKU CON FILTRI  con capoarea, con una localita e i negozi  */
}else if( $localita != "all_city" && $progressivo_vendita !='null') {

    error_log("entra qui TERZO CASO TABELLA FINALE SPECIFICA-> SKU CON FILTRI con tutte le citta e i negozi "); 

  
    $query_specifica_progressivo_vendita = "SELECT ARFMM1 AS CODICE_ARTICOLO_FORNITORE ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)) AS SKU ," .
    " XPRMT2 AS PREZZO_LISTINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,NMAMT2 AS CODICE_MAGAZZINO , ".
    " DSCMT2 AS DATA_SCONTRINO , CL1MM1 AS COD_ARTICOLO ,trim(SUBSTRING(".$az.".FTABPF.FILTAB,0,30)) AS TIPOLOGIA_ARTICOLO , TRIMT2 AS VENDITA , CDIMT2 AS DIVISA , NETMT2 AS PREZZO_NETTO ".
    " FROM ".$az.".MDT2PF ". 
    " INNER JOIN ".$az_grpcami.".MMA1PF ON ".$az_grpcami.".MMA1PF.NUMMM1 = ".$az.".MDT2PF.PARMT2 ". 
    " INNER JOIN ".$az.".FTABPF ON trim(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = ".$az_grpcami.".MMA1PF.CL1MM1 ".
    " WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'E1%' AND NUMMT2 = ".$progressivo_vendita."".
    " AND NMAMT2 IN (".$codice_magazzino_filtrato.")".  //IN (".implode(",",$negozi).")*/".  
    " GROUP BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2  , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2 , CDIMT2 , NETMT2".
    " ORDER BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2, CDIMT2 , NETMT2";
    
  
     
    /*
    
    $query_specifica_progressivo_vendita= "SELECT NRIMT2 AS NUMERO_RIGA_SCONTRINO,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)) AS SKU ".
    " ,XPRMT2 AS PREZZO_LISTINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,NMAMT2 AS CODICE_MAGAZZINO ,". 
    " NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA, SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO,DSCMT2 AS DATA_SCONTRINO ".  
    " FROM ".$az.".MDT2PF ".
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR ". 
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR ".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR ".
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = INT(SUBSTRING(KEYFTA,4,3))" .
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " INNER JOIN ".$az_grpcami.".MMA1PF ON ".$az_grpcami.".MMA1PF.NUMMM1 = ".$az.".MDT2PF.NMAMT2 ".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'S%' AND NUMMT2 = ".$progressivo_vendita."".
    " AND AGECAN = ".$capoarea." AND SUBSTRING(FILTAB,64,30)= '".$localita."' AND NMAMT2 IN (".implode(",",$negozi).")". 
    " GROUP BY NRIMT2 ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , NRIMT2 , SUBSTRING(FILTAB,4,30)".
    " ORDER BY NRIMT2 ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , NRIMT2 , SUBSTRING(FILTAB,4,30)"; 
 
    */

    error_log($query_specifica_progressivo_vendita);

    $count_all_spec_progress = 0; 
    $all_specifico_progressivo = array(); 
 

    //CONNESSIONE AL DATABASE AS400   
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400);

    foreach($as400->query($query_specifica_progressivo_vendita) as $row){

        $all_specifico_progressivo[$count_all_spec_progress] = $row;  
        $count_all_spec_progress ++; //incremento
    }

    echo json_encode(array("negozi_all_specifica_progress_vendita"=>$all_specifico_progressivo)); 

  
    
/*TERZO CASO TABELLA FINALE SPECIFICA-> SKU CON FILTRI */
}else if( $localita == "all_city" && $progressivo_vendita !="null" && $codice_magazzino_filtrato!="null" ){
 

    error_log("entra qui TERZO CASO TABELLA FINALE SPECIFICA-> SKU CON FILTRI con tutte le citta e i negozi "); 
  

    $query_specifica_progressivo_vendita = "SELECT ARFMM1 AS CODICE_ARTICOLO_FORNITORE ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)) AS SKU ," .
    " XPRMT2 AS PREZZO_LISTINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,NMAMT2 AS CODICE_MAGAZZINO , ".
    " DSCMT2 AS DATA_SCONTRINO , CL1MM1 AS COD_ARTICOLO ,trim(SUBSTRING(".$az.".FTABPF.FILTAB,0,30)) AS TIPOLOGIA_ARTICOLO , TRIMT2 AS VENDITA , CDIMT2 AS DIVISA , NETMT2 AS PREZZO_NETTO ".
    " FROM ".$az.".MDT2PF ".
    " INNER JOIN ".$az_grpcami.".MMA1PF ON ".$az_grpcami.".MMA1PF.NUMMM1 = ".$az.".MDT2PF.PARMT2 ". 
    " INNER JOIN ".$az.".FTABPF ON trim(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = ".$az_grpcami.".MMA1PF.CL1MM1 ".
    " WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'E1%' AND NUMMT2 = ".$progressivo_vendita."".
    " AND NMAMT2 IN (".$codice_magazzino_filtrato.")".  
    " GROUP BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2  , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2 , CDIMT2 , NETMT2".
    " ORDER BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2, CDIMT2 , NETMT2";
    
  
     
    /*
    $query_specifica_progressivo_vendita = "SELECT NRIMT2 AS NUMERO_RIGA_SCONTRINO,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)) AS SKU ".
    " ,XPRMT2 AS PREZZO_LISTINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,NMAMT2 AS CODICE_MAGAZZINO ,". 
    " NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA, SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO,DSCMT2 AS DATA_SCONTRINO ".  
    " FROM ".$az.".MDT2PF ".
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR ". 
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR ".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR ".
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = INT(SUBSTRING(KEYFTA,4,3))" .
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " INNER JOIN ".$az_grpcami.".MMA1PF ON ".$az_grpcami.".MMA1PF.NUMMM1 = ".$az.".MDT2PF.NMAMT2 ".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'S%' AND NUMMT2 = ".$progressivo_vendita."".
    " AND NMAMT2 IN (".$codice_magazzino_filtrato.") AND AGECAN = ".$capoarea." ". 
    " GROUP BY NRIMT2 ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , NRIMT2 , SUBSTRING(FILTAB,4,30)".
    " ORDER BY NRIMT2 ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , NRIMT2 , SUBSTRING(FILTAB,4,30)"; 
    */ 

    error_log($query_specifica_progressivo_vendita);

    $count_all_spec_progress = 0; 
    $all_specifico_progressivo = array(); 
  

    //CONNESSIONE AL DATABASE AS400   
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400);

    foreach($as400->query($query_specifica_progressivo_vendita) as $row){

        $all_specifico_progressivo[$count_all_spec_progress] = $row;  
        $count_all_spec_progress ++; //incremento
    }

    echo json_encode(array("negozi_all_specifica_progress_vendita"=>$all_specifico_progressivo)); 
}


?> 
