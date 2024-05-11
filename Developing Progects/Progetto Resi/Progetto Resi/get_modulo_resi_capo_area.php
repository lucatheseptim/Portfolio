<?php
 
//IMPORTO IL FILE INDAGINI:PH
require_once ('indagini.php'); 

$capo_area = array(); 

$request = RequesterClassificazioni::singleton ();
$factory = FactoryLibrary::create ($request->getLibrary ());
$list = new TADListVals ($factory); 

 
//capo area
$selector = new SelectorHeadArea ($list->TADgetHeadArea ());
$capo_area['headarea'] = $selector->get ();


//die(); 
echo json_encode(array("capoarea"=>$capo_area['headarea'])); 




 
?>
