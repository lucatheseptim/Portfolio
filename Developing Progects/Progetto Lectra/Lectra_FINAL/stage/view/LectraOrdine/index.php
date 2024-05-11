<?php
require_once("login.php");
require_once ("auth.php");
require_once ("progress_bar.php");
require_once ("riepilogo_camicie.php");
//session_destroy();
require_once ("spedizione.php");
require_once ("form.php");
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{language}">
<head profile="http://gmpg.org/xfn/11">
    <title>Lectra</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/x-icon" href="cut-solid.svg">
<!--    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="css/progress_bar.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/spedizione.css">
    <link rel="stylesheet" href="css/riepilogo_camicie.css">
    <link rel="stylesheet" href="css/form.css">
    <script
    src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
    crossorigin="anonymous"></script> 
    <script src='https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js'></script>
    <script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="./js/ordine.js"></script>
    <script src="./js/spedizione.js"></script>
    <script src="./js/form.js"></script>
    <script src="./js/riepilogo_camicie.js"></script>
    <script src="./js/lectra.js"></script>
    <script src="./js/display.js"></script>
    <script>
        
        $(document).ready(function () { // una volta che ho finito di leggere l'html
            $(".container_login").hide(); // lo nascondo
            $("#logout_utente").show(); // rendo visibile il bottone di logout  

            toggleFullscreen(); 
   
        }); // chiudo qui il document ready     
    </script>
</head>
<div class = "container-all"> 

    <div class="container_info">

        <ul id="info_scelte"></ul>
    </div>

    <div class="container-selezione_vestibilita container-selezione_all">

        <div class="indietro" id="indietro-fit" style="background-color: white;">
            INDIETRO
        </div>

        <h2 class="titolo">Selezione Vestibilità:</h2>

        <select name="vestibilità" id="vestibilita">
            <option value="0">Seleziona:</option>
        </select>

        <div class="cont-but">
            <div class="conferma" id="conferma-fit">
                CONFERMA
            </div>
        </div>

    </div>

    <div class="container-selezione_collo container-selezione_all">

        <div class="indietro" id="indietro-collo">
            INDIETRO
        </div>

        <h2 class="titolo">Selezione Collo:</h2>

        <select name="collo" id="collo">
            <option value="0">Seleziona:</option>
        </select>

        <div class="cont-but">
            <div class="conferma" id="conferma-collo">
                CONFERMA
            </div>
        </div>

    </div>

    <div class="container-selezione_taglie container-selezione_all">

        <div class="indietro" id="indietro-taglie">
            INDIETRO
        </div>

        <h2 class="titolo">Selezione Taglia Camicia:</h2>

        <select name="taglie" id="taglie">
            <option value="0">Seleziona:</option>
        </select>

        <div class="cont-but">
            <div class="conferma" id="conferma-taglie">
                CONFERMA
            </div>
        </div>

    </div>

    <div class="container-selezione_materiali container-selezione_all">

        <div class="indietro" id="indietro-materiali">
            INDIETRO
        </div>

        <h2 class="titolo">Selezione Materiale Principale:</h2>

        <select name="materiali" id="materiali">
            <option value="0">Seleziona:</option>
        </select>

        <div class="cont-but">
            <div class="conferma" id="conferma-materiali">
                CONFERMA
            </div>
        </div>
    </div>

    <div class="container-selezione_componenti container-selezione_all">

        <input type="hidden" id="id-camicia" name="id_camicia" value="">
        <div class="indietro" id="indietro-componenti">
            INDIETRO
        </div>

        <div id="componenti"></div>
        <div class="funzioni">
            <div id="alterazioni" class="orange-btn">MODIFICA <br>ALTERAZIONI</div>
            <div id="ricami" class="orange-btn">AGGIUNGI RICAMO  +6€/9€</div>
            <div id="bottoni" class="orange-btn">SCEGLI BOTTONI  <br>+5€</div>
        </div>

        <div class="cont-but">
            <div class="conferma-camicia" id="conferma-camicia">
                CONFERMA CAMICIA
            </div>
        </div>

    </div>

    <div class="container-selezione_bottoni container-selezione_all">

        <div class="indietro" id="indietro-bottoni">
            INDIETRO
        </div>

        <div class="scelta">
            <h2>Bottoni In Madre Perla: 5€</h2>
            <div class="checkbox-button form-check form-switch">
                <input id="bottoni_check" class="form-check-input" type="checkbox">
            </div>
        </div>

        <img class="img_bottoni" src="imgs/bottoni_madrev2.png" alt="Bottini Madre Perla">

    </div>

    <div class="container-selezione_alterazioni container-selezione_all">

        <div class="indietro" id="indietro-alterazioni">
            INDIETRO
        </div>

        <div id="scelta_alterazioni"></div>

    </div>

    <div class="container-scelta_alterazioni_corpo container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_corpo">
            INDIETRO
        </div>

        <div id="modifica_alterazione_corpo"></div>

    </div>

    <div class="container-scelta_alterazioni_spalle container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_spalle">
            INDIETRO
        </div>

        <div id="modifica_alterazione_spalle"></div>

    </div>

    <div class="container-scelta_alterazioni_maniche container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_maniche">
            INDIETRO
        </div>

        <div id="modifica_alterazione_maniche"></div>

    </div>

    <div class="container-scelta_alterazioni_torace container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_torace">
            INDIETRO
        </div>

        <div id="modifica_alterazione_torace"></div>

    </div>

    <div class="container-scelta_alterazioni_vita container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_vita">
            INDIETRO
        </div>

        <div id="modifica_alterazione_vita"></div>

    </div>

    <div class="container-scelta_alterazioni_bacino container-selezione_all">

        <div class="indietro" id="indietro-scelta_alterazioni_bacino">
            INDIETRO
        </div>

        <div id="modifica_alterazione_bacino"></div>

    </div>

    <div class="container-selezione_ricami container-selezione_all">

        <div class="indietro" id="indietro-ricami" style="margin-top: -52px;">
            INDIETRO
        </div>

        <div class="scelta_ricami">
            <div class="container_btn_ricamo">
               <!--  <div class="scelte_checkbox_attiva" id="si_scelta_ricamo_ckeck">
                    <input type="checkbox" id="si_ricamo_check"/>
                    <label class="check_text" id="siricamo" for="si_ricamo_check"><b>ATTIVA RICAMO</b></label>
                </div>

                <div class="scelte_checkbox_disattiva" id="no_scelta_ricamo_ckeck">
                    <input type="checkbox" id="no_ricamo_check"/>
                    <label class="check_text" id="noricamo" for="no_ricamo_check"><b>DISATTIVA RICAMO</b></label>
                </div> -->
                <!-- <div class="knobs"></div> -->

                <!-- <div class="checkbox-button_ricami form-check form-switch" id="button-1">
                <input id="bottoni_check_ricami" class="form-check-input" type="checkbox">
                 -->
                    <!-- mode toggle switcher -->
                    <div class="toggle-radio">
                    <input type="radio" name="rdo" id="yes" checked>
                    <input type="radio" name="rdo" id="no">
                    <div class="switch_ricamo">
                        <label for="no">DISATTIVA</label>
                        <label for="yes">ATTIVA</label>
                        <span></span>
                    </div>
                    </div>
                <!-- finish mode toggle switcher -->
                
            </div>
            </div>
            <h2 class="titolo" style="margin-bottom: 5px;margin-top: -36px;">Scelta del ricamo: 6€/9€</h2>
            <div class="row_ric">
                <h3>Iniziali*:</h3>
                <input maxlength="1" type="text" class="iniziali iniziale-1">
                <input maxlength="1" type="text" class="iniziali iniziale-2" disabled>
                <input maxlength="1" type="text" class="iniziali iniziale-3" disabled>
                <h3 style="margin-left: 205px">Stile:</h3>
                <div class="stile" name="corsivo" id="corsivo" data-id="8"><img src="imgs/imgRicami/corsivo.png" alt="corsivo"></div>
                <div class="stile" name="regular" id="stampatello" data-id="9"><img src="imgs/imgRicami/stampatello.png" alt="stampatello"></div>
            </div>
            <div class="row_ric">
                <h3>Risultato:</h3>
                <div class="risultato" id="risultato_ricamo"></div>
            </div>
            <div class="row_ric">
                <h3>Colore lettere:</h3>
                <div class="btn_color_lettere bianco" id="1" data-id="1"><span class="black">bianco</span></div>
                <div class="btn_color_lettere grigio" id="5" data-id=""><span class="white">grigio</span></div>
                <div class="btn_color_lettere blu" id="2" data-id="2"><span class="white">blu</span></div>
                <div class="btn_color_lettere rosso" id="3" data-id="3"><span class="white">rosso</span></div>
                <div class="btn_color_lettere verde" id="4" data-id="4"><span class="white">verde</span></div>
            </div>
            <div class="row_ric">
                <h3>Posizione:</h3>
                <div class="posizione" id="pos_1" data-id="18"><img src="imgs/imgRicami/img_posizione_1.png" alt="no_simbolo"></div>
                <div class="posizione" id="pos_2" data-id="19"><img src="imgs/imgRicami/img_posizione_2.png" alt="no_simbolo"></div>
                <div class="posizione" id="pos_3" data-id="20"><img src="imgs/imgRicami/img_posizione_3.png" alt="no_simbolo"></div>
            </div>
            <div class="row_ric">
                <h3>Simbolo:</h3>
                <div class="simbolo active_simbolo" id="no_simbolo" data-id=""><img src="imgs/imgRicami/img_nessun_simbolo.png" alt="no_simbolo"></div>
                <div class="simbolo" id="simbolo_1" data-id="11"><img src="imgs/imgRicami/img_simboliportaforuna_01.png" alt="simbolo_1"></div>
                <div class="simbolo" id="simbolo_2" data-id="13"><img src="imgs/imgRicami/img_simboliportaforuna_02.png" alt="simbolo_2"></div>
                <div class="simbolo" id="simbolo_3" data-id="12"><img src="imgs/imgRicami/img_simboliportaforuna_03.png" alt="simbolo_3"></div>
                <div class="simbolo" id="simbolo_4" data-id="14"><img src="imgs/imgRicami/img_simboliportaforuna_04.png" alt="simbolo_4"></div>
                <div class="ordine_simbolo">
                    <h3 style="margin-left: 10px">Ordine simbolo:</h3>
                    <select name="ordine" id="ordine_simbolo">
                        <option value="0" id="default_ordine" data-id="">Seleziona ordine:</option>
                        <option value="1" data-id="15">Iniziale</option>
                        <option value="2" data-id="16">Dopo prima lettera:</option>
                        <option value="3" data-id="17">Fine</option>
                    </select>
                </div>
            </div>
            <div class="row_ric">
                <h3>Colore simbolo:</h3>
                <div class="btn_color_simbolo bianco" id="1" data-id="1"><span class="black">bianco</span></div>
                <div class="btn_color_simbolo grigio" id="5" data-id=""><span class="white">grigio</span></div>
                <div class="btn_color_simbolo blu" id="2" data-id="2"><span class="white">blu</span></div>
                <div class="btn_color_simbolo rosso" id="3" data-id="3"><span class="white">rosso</span></div>
                <div class="btn_color_simbolo verde" id="4" data-id="4"><span class="white">verde</span></div>
            </div>
            <div class="row_ric">
                <h3>Posizione simbolo:</h3>
                <div class="posizione_simbolo" id="possimbolo_1" data-id="18"><img src="imgs/imgRicami/img_posizione_1.png" alt="1"></div>
                <div class="posizione_simbolo" id="possimbolo_2" data-id="19"><img src="imgs/imgRicami/img_posizione_2.png" alt="2"></div>
                <div class="posizione_simbolo" id="possimbolo_3" data-id="20"><img src="imgs/imgRicami/img_posizione_3.png" alt="3"></div>
            </div>
            <div class="row_ric">
                <p>
                    *Inserire almeno due lettere; il simbolo è facoltativo e può non essere aggiunto. Tutte le personalizzazioni saranno ricamate sul lato sinistro delle camicie.
                </p>
            </div>   
              
            
        </div>
    </div>
    <!--carosello camicie (DAVANTI)-->
    <div class="container-selezione_davanti container-selezione_all">
        <!--tasto indietro davntoi della camicia-->
        <div class="indietro" id="indietro-scelta-camicia" style="margin-top : -52px;">
            INDIETRO
        </div>
<!--        <div class="tasche_container">-->
        <!--tasti-->
            <h3 id="scelta_tasche">DAVANTI CON TASCHE?</h3>
            <div class="container_scelte"  style="margin-top: -50px;">
                <div class="scelte_checkbox_tasche" id="no_scelta_tasche_ckeck">
                    <input type="checkbox" id="no_tasche_check" style="width:20px;height:20px"/>
                    <label class="check_text" id="notasche" for="no_tasche_check"><b>NO</b></label>
                </div>
                <div class="scelte_checkbox_tasche" id="una_tasca">
                    <input type="checkbox" id="una_tasca_check" style="width:20px;height:20px"/>
                    <label class="check_text" for="una_tasca_check"><b>UNA</b></label>
                </div>
                <div class="scelte_checkbox_tasche" id="due_tasche">
                    <input type="checkbox" id="due_tasche_check" style="width:20px;height:20px"/>
                    <label class="check_text" for="due_tasche_check"><b>DUE</b></label>
                </div>
            </div>
<!--        </div>-->
<!--        <div class="maniche_container">-->
            <h2 id="scelta_manica">MANICA LUNGA O CORTA?</h2>
            <div class="container_scelte">
                <div class="scelte_checkbox_maniche" id="scelta_manica_corta">
                    <input type="checkbox" id="manica_corta" style="width:20px;height:20px"/>
                    <label class="check_text" for="manica_corta"><b>MANICA CORTA</b></label>
                </div>
                <div class="scelte_checkbox_maniche" id="scelta_manica_lunga">
                    <input type="checkbox" id="manica_lunga" style="width:20px;height:20px"/>
                    <label class="check_text" for="manica_lunga"><b>MANICA LUNGA</b></label>
                </div>
            </div>
<!--        </div>-->
<!--        <div class="dietro_container">-->
            <h2 id="scelta_dietro">DIETRO CON CARRÈ INTERO O MEZZO?</h2>
            <div class="container_scelte">
                <div class="scelte_checkbox_dietro" id="scelta_dietro_manica">
                    <input type="checkbox" id="camicia_dietro_intero" style="width:20px;height:20px"/>
                    <label class="check_text" for="camicia_dietro_intero"><b>CARRÈ INTERO</b><label>
                </div>
                <div class="scelte_checkbox_dietro" id="scelta_dietro_mezza_manica">
                    <input type="checkbox" id="camicia_dietro_mezzo_intero" style="width:20px;height:20px"/>
                    <label class="check_text" for="camicia_dietro_mezzo_intero"><b>MEZZO CARRÈ</b><label>
                </div>
            </div>
<!--        </div>-->
        <h3 id="notgl">Non hai selezionato nessuna taglia</h3>
<!--        <h3 id="davanti">DAVANTI:</h3>-->
<!--        <h3 id="dietro">DIETRO</h3>-->
<!--        <h3 id="manica_polsi">MANICA/POLSI</h3>-->
        <h3 id="tasche_pattine">TASCHE</h3>
        <h3 id="collo_scelta_camicia">COLLO</h3>
        <div id="carouselExampleControls" data-bs-interval="false" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">             
            </div>
            <div class="carousel-bottoni">
            </div>
        </div>

        <div class="seleziona-davanti-camicia" id="seleziona-scelta-camicia">
            SELEZIONA
        </div>

    </div>
</html>