<?php

//IMPORTO IL FILE INDAGINI:PHP
require_once ('indagini.php'); 

$localita = array(); //array associativo  

$request = RequesterClassificazioni::singleton ();
$factory = FactoryLibrary::create ($request->getLibrary ());
$list = new TADListVals ($factory); 

  
//localita
$selector = new SelectorCities ($list->getCities ());
$localita['city'] = $selector->get ();


//die(); 
echo json_encode(array("localita"=>$localita['city'])); 




 
?>
