<?php

require_once ('auth.php'); /*verifico che l'utente sia loggato*/

?>

<div class="container-repilogo-camicia container-selezione_all">
    
    <div class= "indietro" id="riepilogo-camicie">
        INDIETRO 
    </div>  

        <div class="container-information">
            <div class="container-riepilogo">
                <!--bottone agginuta nuova camicia-->
                <div class="flex">
                        <div id="aggiungi-nuova-camicia">
                                AGGIUNGI NUOVA <br> CAMICIA<i class="icon_plus fas fa-plus"></i>
                        </div>
                        
                        <div id="nuova-cliente-camicia">
                            AGGIUNGI CAMICIA NUOVO CLIENTE<i class="icon_plus fas fa-plus"></i>
                        </div>
                </div>
                <div class="table-position">
                <table class="table_dark">
                        <thead>
                            <tr>
                                <th>RIEPILOGO SCELTE : </th>
                                <th colspan="2">AZIONI:</th> 
                            </tr>
                        </thead>  
                        <tbody class="container_shirt"> 
                        </tbody>
                        <tfoot>
                        </tfoot> 
                    </table> 
                    
                    <br>
                    <div id="riepilogo-conferma-ordine">
                        CONFERMA ORDINE
                    </div>
                </div>
            </div>
        </div>
</div>

 