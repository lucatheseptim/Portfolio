<?php
//IMPORTO LA LIBRERIA CHE CONTIENE LA CONNESSIONE AL DATABSE
require_once("base.php");
//IMPORTO IL FILE FACTORY LIB
require_once("factorylib.php");  

//verifico tutti i dati ricevuti dal Client
$libreria = $_POST["libreria"]; // ottengo la libreria
$data_da = $_POST["data_da"]; //ottengo la data da 
$data_a = $_POST["data_a"];  // ottengo la data a 
$codice_magazzino = $_POST["codice_magazzino_singolo_all"];
$progressivo_vendita = $_POST["numero_progressivo_vendita"];
$negozi = $_POST["negozi"]; //ottengo il negozio o i negozi 

error_log("il progressivo vendita è :"+$progressivo_vendita);


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



$factory = factoryLIbrary::create($libreria); 
$az = $factory->az;

$factory_azgrpcami = factoryLIbrary::create($libreria); 
$az_grpcami = $factory_azgrpcami->azgrp;



   
 
$args = array("SUBSTR(".$az.".FTABPF.FILTAB,120,2)='".$factory->rm2rks."'"); 

//ESEGUO I VARI CONTROLLI PER VEDERE E PRENDERE SOLO I NEGOZI DIRETTI 
if(trim($factory->rm2rks) != 'C' && trim($factory->rm2rks) != 'G') $args[] = " SUBSTR ( ".$az.".FTABPF.FILTAB , 1 , 1 ) <> '1'";
if(trim($factory->rm2rks) != 'N' && trim($factory->rm2rks) != 'M') $args[] = " TRIM(SUBSTR(".$az.".FTABPF.KEYFTA,4)) NOT LIKE '7%'";

//QUERY UNO PER ESTRAPOLAZIONE DI TUTTI I NEGOZI SINGOLI PER PUNTO VENDITA PER LIBRERIA

/*
$query_singoli_specifica = "SELECT NMAMT2 AS CODICE_MAGAZZINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA , NRIMT2 AS NUMERO_RIGA_SCONTRINO ,".
" SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , DSCMT2 AS DATA_SCONTRINO , NGGMTR AS GIORNI_TRASCORSI_RESO ". 
" FROM ".$az.".MDT2PF". 
" INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR".
    " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
	" AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR". 
" INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))".
" INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
" WHERE TRIMT2 ='R' AND DSVMTR BETWEEN '".$data_da."' AND '".$data_a."' AND  KEYFTA LIKE 'S%'".
" GROUP BY NMAMT2 , NUMMT2 , NRIMT2 , NGGMTR , SUBSTRING(FILTAB,4, 30) , DSCMT2".
" ORDER BY NMAMT2, NUMMT2 , NRIMT2 , NGGMTR , DSCMT2  , SUBSTRING(FILTAB , 4, 30)";
error_log( "la query prodotta è" .$query_singoli_specifica);  //STAMPO LATO BACKEND LA QUERY 
*/
 
/*TABELLA INTERMEDIA*/
if($codice_magazzino != "null" && $progressivo_vendita == "null" && $negozi == "null"){
    //QUERY UNO PER ESTRAPOLAZIONE DI TUTTI I NEGOZI SINGOLI  RECAP PER PUNTO VENDITA PER LIBRERIA
    $query_singoli_all = "SELECT NMAMT2 AS CODICE_MAGAZZINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , DSCMT2 AS DATA_SCONTRINO , NGGMTR AS GIORNI_TRASCORSI_RESO , DSVMTR as DATA_VENDITA_ORIGINE ,TSCMT1 AS TOTALE_SCONTRINO , CDIMT2 AS DIVISA ".  
    " FROM ".$az.".MDT2PF". 
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR".
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR".  
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))".
    " INNER JOIN ".$az.".MDT1PF ON ".$az.".MDT1PF.NUMMT1 = ".$az.".MDT2PF.NUMMT2 ".  
	" AND ".$az.".MDT1PF.NMAMT1 = ".$az.".MDT2PF.NMAMT2 ". 
	" AND ".$az.".MDT1PF.DSCMT1 = ".$az.".MDT2PF.DSCMT2 " .
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND  KEYFTA LIKE 'S%' AND NMAMT2 IN (".$codice_magazzino.")".
    " GROUP BY NMAMT2 , NUMMT2 , SUBSTRING(FILTAB,4, 30) , DSCMT2 , NGGMTR , DSVMTR , TSCMT1 , CDIMT2" .
    " ORDER BY NMAMT2, NUMMT2  , DSCMT2  , SUBSTRING(FILTAB , 4, 30) , NGGMTR , DSVMTR , TSCMT1 , CDIMT2 " ;
    error_log( "la query prodotta intermedia è" .$query_singoli_all);  //STAMPO LATO BACKEND LA QUERY 
  // inserire qui i negozi 

    


    $negozi_all_s = array();
    $count_all_s = 0; 

    //CONNESSIONE AL DATABASE AS400  
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400);

    foreach($as400->query($query_singoli_all) as $row){ 

        $negozi_all_s[$count_all_s] = $row;
        $count_all_s ++;  //incremento 
    }

    //RISPEDISCO AL CLIENT I DATI 
    echo json_encode(array("negozi_all_singoli_raggruppati" => $negozi_all_s ));  

  


/*TABELLA SPECIFICA DI INIZIALE CON MEDIA*/    
}else if($codice_magazzino == "null" && $progressivo_vendita == "null"  && $negozi!="null"){


    //QUERY PER ESTRAPOLAZIONE DI TUTTI I NEGOZI RAGGRUPPATI PER PUNTO VENDITA PER LIBRERIA (MEDIA)
    $query_raggruppati = "SELECT NMAMTR AS CODICE_MAGAZZINO ,SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO  , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI,".
    " AVG(s.MAX_RESO) AS MEDIA_RESI_SCONTRINO  , SUM(s.NGGMTR) AS SOMMA_GIORNI_RESI_NEGOZIO ".
    " FROM ( SELECT NMAMT2 ,NMAMTR, NUMMTR, MAX(NGGMTR) AS MAX_RESO , ".
    " NGGMTR" . 
    "  FROM  ".$az.".MDTRPF AS sr INNER JOIN ".$az.".MDT2PF AS s2 ON ( NMAMT2 = NMAMTR AND  NUMMT2 = NUMMTR AND  NRIMT2 = NRIMTR )  WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' ".
        " GROUP BY NMAMT2,NMAMTR, NUMMTR , NGGMTR " . 
    ") AS s ".  
    " INNER JOIN ".$az.".FTABPF ON INT(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = s.NMAMT2 " . 
    " WHERE KEYFTA LIKE 'S%' AND " .implode("AND" ,$args)." AND NMAMT2 IN (".implode(",",$negozi).") " . 
    " GROUP BY ".
    "    NMAMTR , SUBSTRING(FILTAB,4, 30) ".
    " ORDER BY NMAMTR , SUBSTRING(FILTAB,4, 30)";

    /*
    $query_raggruppati = "SELECT NMAMT2 AS CODICE_MAGAZZINO,".
    " SUBSTRING(FILTAB,4, 30) AS NOME_NEGOZIO , COUNT(NUMMTR) AS NUMERO_SCONTRINI_RILASCIATI, AVG(NGGMTR) AS MEDIA_RESI_SCONTRINO".
    " FROM ".$az.".MDT2PF".  
    " INNER JOIN ".$az.".MDTRPF ON ".$az.".MDT2PF.NMAMT2 = ".$az.".MDTRPF.NMAMTR". 
        " AND ".$az.".MDT2PF.NUMMT2 = ".$az.".MDTRPF.NUMMTR".  
        " AND ".$az.".MDT2PF.NRIMT2 = ".$az.".MDTRPF.NRIMTR".
    " INNER JOIN ".$az.".FTABPF ON ".$az.".MDT2PF.NMAMT2 = TRIM(SUBSTRING(KEYFTA,4,3))". 
    " INNER JOIN ".$az_grpcami.".CANAPF ON ".$az_grpcami.".CANAPF.CODCAN = SUBSTRING(".$az.".FTABPF.FILTAB,99,6)".
    " WHERE TRIMT2 ='R' AND DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'S%' AND " .implode("AND" ,$args)."".
    " GROUP BY NMAMT2 , SUBSTRING(FILTAB,4, 30)";

    */

    error_log($query_raggruppati);  //STAMPO LA QUERY LATO SERVER 

    $count_all_r = 0; 
    $negozi_all_r = array(); 
 

    //CONNESSIONE AL DATABASE AS400  
    $config_as_400 = new Configurator (); 
    $as400 = new OdbcStmt ($config_as_400);

    foreach($as400->query($query_raggruppati) as $row){

        $negozi_all_r[$count_all_r] = $row;  
        $count_all_r ++; //incremento
    }

    //RISPEDISCO AL CLIENT I DATI 
    echo json_encode(array("negozi_all_raggruppati" => $negozi_all_r ));  
 

/*TABELLA SPECIFICA DI RECAP FINALE*/
}else if($codice_magazzino != "null" && $progressivo_vendita != "null"  && $negozi =="null"){
  
    
    $query_specifica_progressivo_vendita = "SELECT ARFMM1 AS CODICE_ARTICOLO_FORNITORE ,(trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)) AS SKU ," .
    " XPRMT2 AS PREZZO_LISTINO , NUMMT2 AS NUMERO_PROGRESSIVO_VENDITA ,NMAMT2 AS CODICE_MAGAZZINO , ".
    " DSCMT2 AS DATA_SCONTRINO , CL1MM1 AS COD_ARTICOLO ,trim(SUBSTRING(".$az.".FTABPF.FILTAB,0,30)) AS TIPOLOGIA_ARTICOLO , TRIMT2 AS VENDITA , CDIMT2 AS DIVISA , NETMT2 AS PREZZO_NETTO ".
    " FROM ".$az.".MDT2PF ".
    " INNER JOIN ".$az_grpcami.".MMA1PF ON ".$az_grpcami.".MMA1PF.NUMMM1 = ".$az.".MDT2PF.PARMT2 ". 
    " INNER JOIN ".$az.".FTABPF ON trim(SUBSTRING(".$az.".FTABPF.KEYFTA,4,3)) = ".$az_grpcami.".MMA1PF.CL1MM1 ".
    " WHERE DSCMT2 BETWEEN '".$data_da."' AND '".$data_a."' AND KEYFTA LIKE 'E1%' AND NUMMT2 = ".$progressivo_vendita." AND NMAMT2 IN (".$codice_magazzino.")".  
    " GROUP BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2  , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2 , CDIMT2 , NETMT2".
    " ORDER BY ARFMM1 , (trim(".$az_grpcami.".MMA1PF.ARFMM1) || trim(".$az_grpcami.".MMA1PF.MOFMM1) || trim(".$az_grpcami.".MMA1PF.COFMM1)),XPRMT2 , NUMMT2 , NMAMT2 , DSCMT2 , CL1MM1 , trim(SUBSTRING(".$az.".FTABPF.FILTAB, 0, 30))  , TRIMT2, CDIMT2 , NETMT2";
    
    
    

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
