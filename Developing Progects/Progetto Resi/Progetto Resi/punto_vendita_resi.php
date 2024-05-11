<?php

require_once ('indagini.php'); 
require_once ('menu.php'); 
//IMPORTO IL FILE CHE CONTIENE I DATI PER LA CONNESSIONE AL DB  
require_once ('configurator.php');
        

$config = new ConfiguratorDbTimb('/var/www/wai/indagini/'); 

$mo = $sessionAuth->getMO(); 

$page = new Template ('templates/general.html');  
$page->language = $sessionAuth->language; //SETTO IL LINGUAGGIO ALLA PAGINA GENERAL.HTML


$menu = new IndaginiMenu (); //ISTANZIO CLASSE IndaginiMenu
$page->menu = $menu->get (); //CHIAMO IL METODO GET   
$page->headline = $menu->getH1 (); //PASSO NELLA SEZIONE HEADLINE DELLA PAGINA general.html
//IL GET DEL METODO getH1; 
 

$page->title = "Punto Vendita Resi";  //PASSO NELLA SEZIONE TITLE DELLA PAGINA general.html
//IL GET DEL METODO getTitle 

    $style = "<style> .fake-link{color:blue;text-decoration:underline;cursor:pointer} .sticky-head{position:sticky; top:0px} ".
	".row-striped{background-color: rgba(170, 170, 170, 0.34);}".
	"#dati_cli th,td{padding: 5px 10px;} ". 
	".dettaglio_vend{margin-top: 5px;} ".
	"</style>"; 
 
$page->style = '<link rel="stylesheet" href="templates/style.css" type="text/css" />' .
	'<link rel="stylesheet" href="templates/buttons.css" type="text/css" media="screen" />'.
	'<link rel="stylesheet" href="templates/menu.css" type="text/css" media="screen" />'.
	'<link rel="stylesheet" href="templates/W3Modal.css" type="text/css" media="screen" />'.
	'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" type="text/css"/>'.
	'<link rel="stylesheet" href="templates/utilities/utilities.css?v=0.1" type="text/css" media="screen" />'.
	'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">'.
	'<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" />'.
    //CSS CONFIRM DIALOG 
    '<link href="https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">'.  
    //ZEBRA DATE PICKER CALENDAR CSS PLUGIN FILE
    '<link rel="stylesheet" href="plugin_calendario/dist/css/default/zebra_datepicker.min.css" type="text/css">'.
    //MULTISELCT CCS SUMO PLUGIN FILE
    '<link rel="stylesheet" href="multiselect_plugin/sumoselect.min.css">'.
    //STYLE RESI 
    '<link rel="stylesheet" href="style_resi.css">'
    .$style; 

$hide_cli_script = "";
$hide_cli_filters = ($sessionAuth->__get("crm_dsa") > 0) ? true : false;
if(!$hide_cli_filters){
	$hide_cli_script = "<script>$(window).ready(function(){ console.log('hide clients'); $('#filter_cli').hide(); });</script>";
}


$page->script  = "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>".
                //SWEET ALERT 
                "<script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>".
                "<script src='https://cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shCore.min.js'></script>".
                "<script src='https://cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushJScript.min.js'></script>".
                "<script src='https://cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushXml.min.js'></script>".
                "<script src='https://cdnjs.cloudflare.com/ajax/libs/SyntaxHighlighter/3.0.83/scripts/shBrushCss.min.js'></script>".
                "<script src='https://code.jquery.com/jquery-3.5.0.min.js'></script>".
                "<script src='https://cdn.jsdelivr.net/npm/zebra_pin@2.0.0/dist/zebra_pin.min.js'></script>".
                "<script src='plugin_calendario/examples/examples.js'></script>". 
                "<script src='https://code.jquery.com/ui/1.13.0/jquery-ui.js'></script>".
                // JQUERY CONFIRM DIALOG 
                "<script src = 'https://code.jquery.com/jquery-1.10.2.js'></script>".
                "<script src = 'https://code.jquery.com/ui/1.10.4/jquery-ui.js'></script>".
                "<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js'></script>".
                //JQUERY ZEBRA DATE PICKER PLUGIN JS 
                "<script src='plugin_calendario/dist/zebra_datepicker.min.js'></script>".
                //file js logica punti vendita
                "<script src='../js/punto_vendita_resi.js'></script>".  
                "<script src='templates/utilities/utilities.js'></script>".
                // MULTISLECT SUMO PLUGIN  JS 
                "<script src='multiselect_plugin/jquery.sumoselect.min.js'></script>"
                .$hide_cli_script; 
        
$form = new Template ('templates/form_template_resi.html'); 

$form->Library = $mo->translate ('Libreria');
$sellibs = new SelectorLibraries ();
$form->LibraryVal = $sellibs->out ();


$form->HeadArea = $mo->translate ('Capo Area');

$form->City = $mo->translate ('Località');

$form->Shop = $mo->translate ('Negozio');

$form->GoButton = $mo->translate ('CERCA RESI'); 

$page->body = $form->out ();

$page->footer = "" ; 

echo $page->out ();

?>