<?php

//IMPORTO IL FILE INDAGINI:PHP
require_once ('indagini.php'); 

$negozi = array(); 

$request = RequesterClassificazioni::singleton ();
$factory = FactoryLibrary::create ($request->getLibrary ());
$list = new TADListVals ($factory); 

 
$selector = new SelectorShops ($list->getShops ());
$negozi['negozi'] = $selector->get ();


//die(); 
echo json_encode(array("negozi"=>$negozi['negozi'])); 




 
?>