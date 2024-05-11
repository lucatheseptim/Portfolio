/*VARIABILI GLOBALI*/
let alterazioni = [];
let valore_camicia_scelta_davanti = ""; //oggetto vuoto maglietta_davanti
let valore_camicia_scelta_dietro = ""; //oggetto vuoto maglietta_dietro
let valore_camicia_scelta_manica_polsi = ""; //oggetto vuoto maglietta_polsi
let valore_camicia_scelta_tasche_pattine = ""; //oggetto vuoto tasche/pattine
let valore_camicia_scelta_collo = ""; //oggetto vuoto scelta/collo
var count_camicia = 1;
let scelta = "" ; // la mia scelta parte da vuota
let valori_totali_camicia = ""; // parte da vuoto
let descrizione_camicia = "";
let count_camicia_selezionata = 0;
let last_camicia_id = 0;    // Ultimo id valido per gestire le chiavi della camicia
let modifica = false;
let codice_camicia_id_davanti = "";
let codice_camicia_id_dietro = "";
let codice_camicia_id_manica_polsi = "";
let codice_camicia_id_scelta_tasche_pattine = "";
let codice_camicia_id_collo = "";
var n_tasche = null;
var no_tasche = false;
var view_tasche_image = false;
var una_tasca = false;
var due_tasche = false;
var scelta_no_tasche = false;
var ordine_id_global = -1; // Id ordine creato dalla save_order
var ordine_lectra = null;
let codice_maglietta = "" ; // parte da
let click_seleziona = false;

$(document).ready(function () {
    ordine_lectra = new LectraOrdine(0); // instanzio la classe Lectra Ordine
    camicia_index = 0;
    getVestibilità();
   
    function getVestibilità(){
        //get fits
        $.ajax({
            url: "/lectra/stage/init.php",
            method: "POST",
            dataType: "json",
            data: {
                action: "get_field_data",
                val_field: "product"
            }
        }).done((res) => {
            if(res.output.success === 1){
                options = res.output.data;
                $("#vestibilita").empty();
                $("#vestibilita").append("<option value='0'>Seleziona:</option>");
                for(row in options){
                    $("#vestibilita").append("<option value='" + options[row].ID + "'>" + options[row].DESCRIZIONE + "</option>");
                }
            }
            $(".container-bar").show();
        });
        $("#info_scelte").css('display','none');
        $("#conferma-fit").unbind();
        $("#conferma-fit").click(function () {
            if($("#vestibilita").find(":selected").val() != 0){ // si poteva fare $( "#versabilita option:selected" ).val();
                $(".container-selezione_vestibilita").hide(); // viene nascosto
                $(".container-selezione_collo").show(); // viene rese visibile
                getCollo($("#vestibilita").find(":selected").val());
                $(".vesti").remove();
                $("#info_scelte").append("<li class='vesti'><i class='fas fa-caret-right'></i>  Vestibilità:  "+ $("#vestibilita").find(":selected").text()+"</li>");
                $("#info_scelte").css('display','block');
            }else{
                swal("Attenzione!", "Selezionare la vestibilità per proseguire.", "warning");
            }
        });
    }
    //agg nuova camicia -> materiali
    $("#aggiungi-nuova-camicia").click(function(){
        $(".container-selezione_materiali").show();
        $(".container-repilogo-camicia").hide();
        $("#info_scelte").show();
    });
    //aggiunta camicia nuoco cliente -reset tutto cio che era selezionato
    $("#nuova-cliente-camicia").click(function(){
        $(".container-repilogo-camicia").hide();
        $(".container-all").show();
        $(".container-selezione_vestibilita").show();
        //reset funzione mod-alterazioni
        $("#modifica_alterazione_corpo .form-check-input").prop('checked' , false);
        $("#modifica_alterazione_corpo .corpo_davanti").prop('selectedIndex',0);
        $("#modifica_alterazione_corpo .corpo_dietro").prop('selectedIndex',0);
        $("#modifica_alterazione_corpo .corpo_entrambi").prop('selectedIndex',0);
        //reset spalle/torace/vita/bacino
        $("#modifica_alterazione_spalle #select_spalle").prop('selectedIndex',0);
        $("#modifica_alterazione_torace #select_torace").prop('selectedIndex',0);
        $("#modifica_alterazione_vita #select_vita").prop('selectedIndex',0);
        $("#modifica_alterazione_bacino #select_bacino").prop('selectedIndex',0);
        //reset funz agg-ricami
        $('.scelta_ricami :input').val('');
        $('.stile').removeClass("active_stile");
        $('.stile').removeClass("active_stile");
        $('#risultato_ricamo').empty();
        $('.btn_color_lettere').removeClass("active_color");
        $('.posizione').removeClass("active_posizione");
        $('.simbolo').removeClass("active_simbolo");
        $('.btn_color_simbolo').removeClass("active_color_simbolo");
        $('.posizione_simbolo').removeClass("active_posizione_simbolo");
        $('.ordine_simbolo #ordine_simbolo').prop('selectedIndex',0);
        //reset funz scegli-bottoni madreperla
        $(".form-check-input").prop('checked' , false);
        //reset select text-vestibilita-collo-taglie-mate
        $("#info_scelte li").empty();
        $("#info_scelte li").css('list-style','none');
        //reset agg ricamo-scegli bottoni
        $("#ricami").attr('style','border:5px solid #023047');
        $("#bottoni").attr('style','border:5px solid #023047');
        getVestibilità();
    });

    $(".container-repilogo-camicia").hide();

    $("#conferma-camicia").click(function(){
        //progress bar 
        $('#ordine').addClass('active');
        $("#crm").addClass('active');
        var empty_ricamo = $(".iniziale-1").val() != "" && $(".iniziale-2").val() != "" ? "" : "blocca";
        //controllo componenti pieni tranne il ricamo 
        if(empty_ricamo == "" && valore_camicia_scelta_davanti !="" && valore_camicia_scelta_dietro !="" && valore_camicia_scelta_manica_polsi !="" && valore_camicia_scelta_tasche_pattine !="" && valore_camicia_scelta_collo !=""){
            $(".container-repilogo-camicia").show();
            $(".container-information").show();
            $("#riepilogo-camicie").hide(); 
            $("#info_scelte").hide();
            $(".container-selezione_componenti").hide();
            //prendo tutti i dati
            var vestibilita =  $("#vestibilita").val();
            var collo = $("#collo").val();
            var taglia = $("#taglie").val();
            var materiali = $("#materiali").val();
            //instazio l'oggetto CamiciaOrdinata , // istanzio l0ogeeto camicia 
            var camicia = new CamiciaOrdinata(vestibilita,collo,taglia,materiali);
            // Descrizioni
            camicia.descrizioni.davanti = valore_camicia_scelta_davanti;
            camicia.descrizioni.dietro = valore_camicia_scelta_dietro;
            camicia.descrizioni.polsi = valore_camicia_scelta_manica_polsi;
            camicia.descrizioni.tasche = valore_camicia_scelta_tasche_pattine;
            camicia.descrizioni.collo = valore_camicia_scelta_collo;
            camicia.descrizioni.materiale_primario = $("#materiali").find(":selected").text();
            // Componenti
            camicia.aggiungiComponente(codice_camicia_id_davanti);
            camicia.aggiungiComponente(codice_camicia_id_dietro);
            camicia.aggiungiComponente(codice_camicia_id_manica_polsi);
            camicia.aggiungiComponente(codice_camicia_id_scelta_tasche_pattine);
            camicia.aggiungiComponente(codice_camicia_id_collo);
            // Alterazioni
            if($("#corpo_check").prop("checked")){
                if($("#select_corpo_entrambe").val() != undefined){
                    camicia.aggiungiAlterazione(3, $("#select_corpo_entrambe").val());
                    camicia.aggiungiAlterazione(4, $("#select_corpo_entrambe").val());
                }
            }else{
                if($("#select_corpo_davanti").val() != undefined){
                    camicia.aggiungiAlterazione(3, $("#select_corpo_davanti").val());
                }
                if($("#select_corpo_dietro").val() != undefined){
                    camicia.aggiungiAlterazione(4, $("#select_corpo_dietro").val());
                }
            }
            if($("#select_spalle").val() != undefined){
                camicia.aggiungiAlterazione(6, $("#select_spalle").val());
            }
            if($("#select_torace").val() != undefined){
                camicia.aggiungiAlterazione(7, $("#select_torace").val());
            }
            if($("#select_vita").val() != undefined){
                camicia.aggiungiAlterazione(8, $("#select_vita").val());
            }
            if($("#select_bacino").val() != undefined){
                camicia.aggiungiAlterazione(9, $("#select_bacino").val());
            }
            // Qty articolo
            camicia.setQty(1); // Da sistemare
            // Ricami
            ricamo_testo = $("div.container-selezione_ricami input.iniziale-1").val() + $("div.container-selezione_ricami input.iniziale-2").val() + $("div.container-selezione_ricami input.iniziale-3").val();
            simbolo = $("div.simbolo.active_simbolo").data();
            //save riepilogo
            if(!$("#no").is(":checked")){

                camicia.setRicamo(
                    ricamo_testo.trim(),
                    (ricamo_testo.trim() != "") ? $("div.stile.active_stile").data("id") : -1, // manca value
                    (ricamo_testo.trim() != "") ? $("div.posizione.active_posizione").data("id") : -1,
                    (ricamo_testo.trim() != "") ? $("div.btn_color_lettere.active_color").data("id") : -1,
                    (simbolo != undefined) ? simbolo : -1,
                    (simbolo != undefined) ? $("div.posizione_simbolo.active_posizione_simbolo").data("id") : -1,
                    (simbolo != undefined) ? $("#ordine_simbolo").data("id") : -1,
                    (simbolo != undefined) ? $("div.btn_color_simbolo.active_color_simbolo").data("id") : -1
                );
            }
           
            id_camicia = $("#id-camicia").val();
            if($("#id-camicia").val() != undefined && $("#id-camicia").val() != ""){
                id_camicia = $("#id-camicia").val();
            }else{
                id_camicia = last_camicia_id;
                last_camicia_id++;
            }
            ordine_lectra.replaceArticolo(id_camicia, camicia);
            creaRiepilogo(ordine_lectra.articoli);
        }else{
            $(".container-selezione_componenti").show();
            $(".container-information").hide();
            swal({
                title: "Attenzione!",
                text: "Selezionare tutti i campi obbligatori.",
                icon: "warning",
                closeOnEsc: false,
                }).then(function (closeOnEsc) {});
        }
    });
   //tasto riepilogo conferma ordine
    $("#riepilogo-conferma-ordine").click(function(){
        $(".container-repilogo-camicia").hide();
        $(".nuovo_cliente").hide();
        $("#indietro-create-account").hide();
        $(".container-creazione_mail").show();
        $("#indietro-mail").show();
        /*CREAZIONE NUOVO ORDINE */
        $.ajax({
            url: "/lectra/stage/init.php",
            dataType: "json",
            data:{
                action: "save_order", /*save order*/
                val_fields: {
                    ordine_id : null,
                    cliente_id : 1,
                    codneg : 404,
                    comment: ""
                }
            }
          }).done(function(res){
              if(res && res.output.success == 1){
                  ordine_id_global = res.output.data.id;
                  parseRequestSalvaProdotti(ordine_id_global, ordine_lectra.articoli);
              }else{
                  alert("errore generazione ordine");
              }
        });
    }); // chiuso click riepilogo-conferma-ordine
    //invio Spedizione
    $("#conferma-spedizione-ordine").click(function(){
        parseRequestApprovaOrdine(ordine_id_global);
    });
}); //fine document ready

function getCollo(id_fit){
    //get collar
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        data: {
            action: "get_collo_alterations",
            val_product_id: id_fit
        }
    }).done((res) => {
        if(res.output.success === 1){
            options = res.output.data.VALORI_DA_VISUALIZZARE;
            $("#collo").empty();
            $("#collo").append("<option value='0'>Seleziona:</option>");
            for(row in options){
                $("#collo").append("<option value='" + parseInt(options[row]) + "'>" + parseInt(options[row]) + "</option>");
            }
        }
    });

    $("#indietro-collo").unbind();
    $("#indietro-collo").click(function () {
        $(".container-selezione_vestibilita").show();
        $(".container-selezione_collo").hide();
    });

    $("#conferma-collo").unbind();
    $("#conferma-collo").click(function () {
        if($("#collo").find(":selected").val() != 0){
            $(".container-selezione_collo").hide();
            $(".container-selezione_taglie").show();
            getTaglie($("#collo").find(":selected").val(), $("#vestibilita").find(":selected").val());
            $(".collo").remove();
            $("#info_scelte").append("<li class='collo'><i class='fas fa-caret-right'></i>  Collo:  "+ $("#collo").find(":selected").text()+"</li>");
        }else{
            swal("Attenzione!", "Selezionare il collo per proseguere.", "warning");
        }
    });
}

function getTaglie(id_collo, id_fit){
    //get collar
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        data: {
            action: "get_taglie",
            val_collo_size: id_collo,
            val_product_id: id_fit
        }
    }).done((res) => {
        if(res.output.success === 1){ // se il risultato è success
            options = res.output.data;
            $("#taglie").empty();
            $("#taglie").append("<option value='0'>Seleziona:</option>");
            for(row in options){
                $("#taglie").append("<option value='" + parseInt(options[row]) + "'>" + parseInt(options[row]) + "</option>");
            }
        }
    });

    $("#indietro-taglie").unbind();
    $("#indietro-taglie").click(function () {
        $(".container-selezione_collo").show();
        $(".container-selezione_taglie").hide();
    });

    $("#conferma-taglie").unbind();
    $("#conferma-taglie").click(function () {
        if($("#taglie").find(":selected").val() != 0){
            $(".container-selezione_taglie").hide();
            $(".container-selezione_materiali").show();
            getMateriali();
            $(".taglie").remove();
            $("#info_scelte").append("<li class='taglie'><i class='fas fa-caret-right'></i>  Taglie:  "+ $("#taglie").find(":selected").text()+"</li>");
        }else{
            swal("Attenzione!", "Selezionare la taglia della camicia per proseguere.", "warning");
        }
    });
}

function getMateriali(){
    //get collar
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        data: {
            action: "get_field_data",
            val_field: "materiale"
        }
    }).done((res) => {
        if(res.output.success === 1){
            $("#materiali").empty();
            $("#materiali").append("<option value='0'>Seleziona:</option>");
            options = res.output.data;
            for(row in options){
                $("#materiali").append("<option value='" + options[row]["ID"] + "'>" + options[row].DESCRIZIONE + "</option>");
            }
        }
    }); 

    $("#indietro-materiali").unbind();
    $("#indietro-materiali").click(function () {
        $(".container-selezione_taglie").show();
        $(".container-selezione_materiali").hide();
    });

    $("#conferma-materiali").unbind();
    $("#conferma-materiali").click(function () {
        //Controllo tessuti madreperla
        if($("#materiali").find(":selected").val() != 0){
            $("#info_scelte").show();
            $(".container-selezione_materiali").hide();
            $(".container-selezione_componenti").show();
            getComponenti();
            $(".materiali").remove();
            $("#info_scelte").append("<li class='materiali'><i class='fas fa-caret-right'></i>  Materiali:  "+ $("#materiali").find(":selected").text()+"</li>");
            $.ajax({
                url: "/lectra/stage/init.php",
                method: "POST",
                dataType: "json",
                data: {
                    action: "has_bottoni_madreperla",
                    val_materiali_id: $("#materiali").find(":selected").val()
                }
            }).done((res) => {
                if(res.output.success == 1){
                    flagBottoni = res.output.data.has_bottoni_madreperla;
                    if(flagBottoni == 1){
                        $("#bottoni").show();
                    }else{
                        $("#bottoni").hide();
                    }
                }
            });
        }else{
            swal("Attenzione!", "Selezionare il materiale per proseguere.", "warning");
        }
    });
}

function getComponenti(){
    //get collar
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        data: {
            action: "get_field_data",
            val_field: "lista_componenti"
        }
    }).done((res) => {
        if(res.output.success === 1){
            options = res.output.data;
            $("#componenti").empty();
            $("#info_scelte").show();
            for(row in options){
                $("#componenti").append("<div class='blu-btn componenti' name="+options[row]["ID"]+"  id='comp_" + options[row].ID + "-" + options[row].CODICE + "'>" + options[row].DESCRIZIONE + "</div>");
            }
            //verifico tra tutti i click se si veriifica un click di collo,davanti,manica/polsi,tasche/pattine,collo
            $(".componenti").click(function(){ // se è avvenuto un click
                var id_completo_comp;
                var id;
                id_completo_comp = $(this).attr("id");
                id = $(this).attr("name");
                $("#davanti").hide();
                $("#dietro").hide();
                $("#manica_polsi").hide();
                $("#tasche_pattine").hide();
                $("#collo_scelta_camicia").hide();
                $("#no_scelta_tasche_ckeck").hide();
                $("#una_tasca").hide();
                $("#due_tasche").hide();
                $("#notgl").hide();
                $("#scelta_tasche").hide();
                $("#scelta_manica").hide();
                $("#scelta_manica_corta").hide();
                $("#scelta_manica_lunga").hide();
                $("#scelta_dietro").hide();
                $("#scelta_dietro_manica").hide();
                $("#scelta_dietro_mezza_manica").hide();
                $("#info_scelte").hide();
                if(id == 1){
                    $("#davanti").show();
                    $("#scelta_tasche").show();
                    $("#no_scelta_tasche_ckeck").show();
                    $("#una_tasca").show();
                    $("#due_tasche").show();
                    scelta = "davanti"; // imposto la scelta davanti
                    //faccio vedere la scelta
                    $("#no_tasche_check").prop('checked',false);
                    $("#una_tasca_check").prop('checked',false);
                    $("#due_tasche_check").prop('checked',false);
                    parseRequestGetComponente(1, null, null, null, codice_camicia_id_davanti);

                    $("#una_tasca_check").change(function() {
                        if($("#una_tasca_check").is(":checked")){ // se ritorna true
                            $("#due_tasche_check").prop("checked", false);
                            $("#no_tasche_check").prop("checked", false);
                            una_tasca = true;
                            due_tasche = false;
                            no_tasche = true;
                            n_tasche = 1;
                            $("#comp_4-9").show(); //lo rendo disponibile
                            parseRequestGetComponente(1, null, 1 , null , codice_camicia_id_davanti );
                        }
                    });
                    $("#due_tasche_check").change(function() {
                        if($("#due_tasche_check").is(":checked")){ // se ritorna true
                            $("#una_tasca_check").prop("checked", false);
                            $("#no_tasche_check").prop("checked", false);
                            due_tasche = true;
                            una_tasca = false;
                            no_tasche = true;
                            n_tasche = 2;
                            $("#comp_4-9").show(); // lo rendo disponibile
                            parseRequestGetComponente(1, null, 2 , null, codice_camicia_id_davanti);
                            }
                    }); // chiudo il click
                    $("#no_tasche_check").change(function() {
                        if($("#no_tasche_check").is(":checked")){
                            $("#una_tasca_check").prop("checked", false);
                            $("#due_tasche_check").prop("checked", false);
                            scelta_no_tasche = true;
                            n_tasche = 0;
                            //rendo visibile il div
                            $("#comp_4-9").show();
                            $("#comp_4-9").hide();
                            parseRequestGetComponente(1, null, 0 , null, codice_camicia_id_davanti);
                        }
                    });

                }else if(id == 2){
                    $("#dietro").show();
                    $("#scelta_dietro").show();
                    $("#scelta_dietro_manica").show(); // lo rendo disponibile
                    $("#scelta_dietro_mezza_manica").show(); // lo rendo disponibile
                    scelta = "dietro";
                    $('#camicia_dietro_intero').prop('checked',false);
                    $('#camicia_dietro_mezzo_intero').prop('checked',false);
                    parseRequestGetComponente(2, null, null, null, codice_camicia_id_dietro);

                    $("#camicia_dietro_intero").change(function(){
                        if($("#camicia_dietro_intero").is(':checked')){
                            $('#camicia_dietro_mezzo_intero').prop('checked',false); //lo metto a false
                            parseRequestGetComponente(2,null,null,true,codice_camicia_id_dietro);
                        }
                    });
                    $("#camicia_dietro_mezzo_intero").change(function(){
                        if($("#camicia_dietro_mezzo_intero").is(':checked')){
                            $('#camicia_dietro_intero').prop('checked',false); // lo metto a false
                            parseRequestGetComponente(2,null,null,false,codice_camicia_id_dietro);
                        }
                    });
                }else if(id == 3){
                    $("#manica_polsi").show();
                    $("#scelta_manica").show();
                    $("#scelta_manica_corta").show();
                    $("#scelta_manica_lunga").show();
                    scelta = "manica_polsi";
                    //faccio vedere la scelta
                    $("#manica_corta").prop('checked',false);
                    $("#manica_lunga").prop('checked',false);
                    parseRequestGetComponente(3, null, null, null, codice_camicia_id_manica_polsi);

                    $("#manica_corta").change(function() {
                        if($("#manica_corta").is(":checked")) // se ritorna true
                        {
                            $("#manica_lunga").prop('checked',false);
                            parseRequestGetComponente(3, false, null , null, codice_camicia_id_manica_polsi);
                        }
                    });
                    $("#manica_lunga").change(function() {
                        if($("#manica_lunga").is(":checked")) // se ritorna true
                        {
                            $("#manica_corta").prop('checked',false);
                            parseRequestGetComponente(3, true, null , null, codice_camicia_id_manica_polsi);
                        }
                    }); // chiudo il click
                }else if(id == 4){
                    // parseRequestGetComponente(id, null, n_tasche);
                    $("#tasche_pattine").show();
                    scelta = "tasche_pattine";
                    parseRequestGetComponente(4, null, n_tasche, null, codice_camicia_id_scelta_tasche_pattine);
                }else if(id == 5){
                    // parseRequestGetComponente(id, null, null);
                    $("#collo_scelta_camicia").show();
                    scelta = "collo_scelta_camicia";
                    parseRequestGetComponente(5, null, null, null, codice_camicia_id_collo);
                }

                
                // parseRequestGetComponente(id, null, null, null);

                

                //click del tasto indietro
                $("#indietro-scelta-camicia").click(function(){
                    $("#info_scelte").show();
                    $(".container-selezione_davanti").hide();
                    $(".container-selezione_componenti").show();
                    $(".comp_davanti").empty();
                    $(".carousel-inner").empty(); // cancello tutti i figli e il loro contenuto  all'interno della classe carosello

                });
                //click tasto Seleziona avanti camicia
                $(".seleziona-davanti-camicia").unbind();
                $(".seleziona-davanti-camicia").click(function(){ 

                    click_seleziona = true; /// lo imposto a true 

                    codice_maglietta = $(".carousel-item.active input.codice_camicia").val(); // prendo l'id della camicia
                    console.log(codice_maglietta); // il codice della maglietta 

                    $("#info_scelte").show(); 
                    //valori scelta maglietta
                    if($(".comp_all").length == 0){
                        $("#info_scelte").append("<details><summary class='recap_info'>COMPONENTI</summary><ul class='comp_all'></ul></details>");
                    }
                    if(scelta == "davanti"){ //if se la scelta è davanti
                        valore_camicia_scelta_davanti = $(".carousel-item.active input.desc_camicia").val(); // prendo lòa sua descrizione
                        //controllo se è stata selezionata una camicia
                        if(valore_camicia_scelta_davanti !=""){
                            $("#comp_1-6").attr('style','background-color:#347C2C;');   // diventa verde
                            //$("#info_scelte").append("<li class='comp_davanti'>Davanti:"+$(".carousel-item.active input.desc_camicia").val()+"</li>");
                            $(".comp_davanti").remove();
                            $(".comp_all").append("<li class='comp_davanti'>Davanti:"+ valore_camicia_scelta_davanti+"</li>").slideDown;
                        }
                        codice_camicia_id_davanti = $(".carousel-item.active input.codice_camicia_id").val();
                        valore_camicia_scelta_davanti = "<b> Davanti : </b> " + valore_camicia_scelta_davanti;
                    }else if (scelta == "dietro"){
                        valore_camicia_scelta_dietro = $(".carousel-item.active input.desc_camicia").val();
                        codice_camicia_id_dietro = $(".carousel-item.active input.codice_camicia_id").val();
                        if( valore_camicia_scelta_dietro != ""){
                            $("#comp_2-7").attr('style','background-color:#347C2C;');   // diventa verde
                            //$("#info_scelte").append("<li class='comp_dietro'>Dietro:"+$(".carousel-item.active input.codice_camicia_id").val()+"</li>");
                            $(".comp_dietro").remove();
                            $(".comp_all").append("<li class='comp_dietro'>Dietro:"+$(".carousel-item.active input.desc_camicia").val()+"</li>").slideDown;
                        }
                        valore_camicia_scelta_dietro = "<b> Dietro: </b>" + valore_camicia_scelta_dietro;
                    }else if(scelta == "manica_polsi"){
                        valore_camicia_scelta_manica_polsi = $(".carousel-item.active input.desc_camicia").val();
                        codice_camicia_id_manica_polsi = $(".carousel-item.active input.codice_camicia_id").val();
                        if(valore_camicia_scelta_manica_polsi !=""){
                            $("#comp_3-8").attr('style','background-color:#347C2C;');   // diventa verde
                            //$("#info_scelte").append("<li class='comp_manica'>Manica/Polsi:"+$(".carousel-item.active input.desc_camicia").val()+"</li>");
                            $(".comp_manica").remove();
                            $(".comp_all").append("<li class='comp_manica'>Manica/Polsi:"+ valore_camicia_scelta_manica_polsi+"</li>").slideDown;
                        }
                        valore_camicia_scelta_manica_polsi = "<b>Manica/Polsi:</b>" + valore_camicia_scelta_manica_polsi;
                    }else if(scelta == "tasche_pattine"){
                        if(una_tasca == true  && due_tasche  == true){
                            valore_camicia_scelta_tasche_pattine = $(".carousel-item.active input.desc_camicia").val();
                            if( valore_camicia_scelta_tasche_pattine !=""){
                                $("#comp_4-9").attr('style','background-color:#347C2C;');   // diventa verde
                            }
                            codice_camicia_id_scelta_tasche_pattine = $(".carousel-item.active input.codice_camicia_id").val();
                            valore_camicia_scelta_tasche_pattine = "<b>Tasche/Pattine:</b>" + valore_camicia_scelta_tasche_pattine;
                        }else{
                            valore_camicia_scelta_tasche_pattine = $(".carousel-item.active input.desc_camicia").val();
                            if( valore_camicia_scelta_tasche_pattine !=""){
                                $("#comp_4-9").attr('style','background-color:#347C2C;');   // diventa verde
                                //$("#info_scelte").append("<li class='comp_tasche'>Tasche:"+ $(".carousel-item.active input.desc_camicia").val()+"</li>");
                                $(".comp_tasche").remove();
                                $(".comp_all").append("<li class='comp_tasche'>Tasche:"+ valore_camicia_scelta_tasche_pattine+"</li>").slideDown;
                            }
                            codice_camicia_id_scelta_tasche_pattine = $(".carousel-item.active input.codice_camicia_id").val();
                            valore_camicia_scelta_tasche_pattine = "<b>Tasche/Pattine:</b>" + valore_camicia_scelta_tasche_pattine;
                        }
                    }else if(scelta  == "collo_scelta_camicia"){
                        valore_camicia_scelta_collo = $(".carousel-item.active input.desc_camicia").val();
                        codice_camicia_id_collo= $(".carousel-item.active input.codice_camicia_id").val();
                        if(valore_camicia_scelta_collo !=""){
                            $("#comp_5-").attr('style','background-color:#347C2C;');   // diventa verde
                            //$("#info_scelte").append("<li class='comp_collo'>Collo:"+$(".carousel-item.active input.desc_camicia").val()+"</li>");
                            $(".comp_collo").remove();
                            $(".comp_all").append("<li class='comp_collo'>Collo:"+valore_camicia_scelta_collo+"</li>").slideDown;
                        }
                        valore_camicia_scelta_collo = "<b>Misura Collo:</b>" + valore_camicia_scelta_collo;
                    }
                    $(".container-selezione_davanti").hide(); //carosello
                    $(".container-selezione_componenti").show();
                });
            });

            $("#indietro-componenti").unbind();

            $("#indietro-componenti").click(function () {
                $(".container-selezione_materiali").show();
                $(".container-selezione_componenti").hide();
                $("#info_scelte").show();
            });

            $("#bottoni").click(function(){
                $(".container-selezione_componenti").hide();
                $(".container-selezione_bottoni").show();
                $("#info_scelte").hide();
                $("#indietro-bottoni").click(function () {
                    $("#info_scelte").show();
                    $(".container-selezione_componenti").show();
                    $(".container-selezione_bottoni").hide();
                    if( $("#bottoni_check").is(':checked')){
                        $("#bottoni").attr('style','border:5px solid #347C2C;');
                        $(".bottoni").remove();
                        $("#info_scelte").append("<li class='bottoni'><i class='fas fa-caret-right'></i>  Bottoni:  "+($("#bottoni_check").is(':checked') == true ? "SI" : "NO") +"</li>");
                    }else{
                        $("#bottoni").attr('style','border:5px solid #023047;');
                        $(".bottoni").remove();
                        $("#info_scelte").append("<li class='bottoni'><i class='fas fa-caret-right'></i>  Bottoni:  "+($("#bottoni_check").is(':checked') == true ? "SI" : "NO") +"</li>");
                    }
                });
            });

            $("#alterazioni").click(function(){
                $(".container-selezione_componenti").hide();
                $(".container-selezione_alterazioni").show();
                //$("#info_scelte").hide();
                getAlterazioni();
                //$("#select_corpo_dietro").find(":selected").val()!="" || $("#select_corpo_davanti").find(":selected").val()!="" || $("#select_corpo_entrambe").find(":selected").val()!="")
                if($("#select_corpo_entrambe").find(":selected").val()!= undefined && $("#select_corpo_entrambe").find(":selected").val()!="" ||$("#select_corpo_davanti").find(":selected").val()!= undefined && $("#select_corpo_davanti").find(":selected").val()!="" || $("#select_corpo_dietro").find(":selected").val()!= undefined && $("#select_corpo_dietro").find(":selected").val()!="" ){
                    $("#alt_CORPO").attr('style','background-color:#347C2C;');
                }else{
                    $("#alt_CORPO").attr('style','background-color:#023047;');
                }
                if($("#select_spalle").find(":selected").val()!= undefined && $("#select_spalle").find(":selected").val()!=""){
                    $("#alt_SPALLE").attr('style','background-color:#347C2C;');
                    $("#alt_SPALLE").attr('style','background-color:#023047;');
                }
                if($("#select_torace").find(":selected").val()!= undefined && $("#select_torace").find(":selected").val()!=""){
                    $("#alt_TORACE").attr('style','background-color:#347C2C;');
                }else{
                    $("#alt_TORACE").attr('style','background-color:#023047;');
                }
                if($("#select_vita").find(":selected").val()!= undefined && $("#select_vita").find(":selected").val()!=""){
                    $("#alt_VITA").attr('style','background-color:#347C2C;');
                }else{
                    $("#alt_VITA").attr('style','background-color:#023047;');
                }
                if($("#select_bacino").find(":selected").val()!= undefined && $("#select_bacino").find(":selected").val()!=""){
                    $("#alt_BACINO").attr('style','background-color:#347C2C;');
                }else{
                    $("#alt_BACINO").attr('style','background-color:#023047;');
                }
                $("#indietro-alterazioni").click(function () {
                    $("#info_scelte").show();
                    $(".container-selezione_componenti").show();
                    $(".container-selezione_alterazioni").hide();
                });
            });

            $("#ricami").click(function(){
                $(".container-selezione_componenti").hide();
                $(".container-selezione_ricami").show();
                $("#info_scelte").hide();
                getRicami();
                $("#indietro-ricami").click(function () {
                    $("#info_scelte").show();
                    $(".container-selezione_componenti").show(); 
                    $(".container-selezione_ricami").hide();
                    if($(".iniziale-1").val()!="" && $(".iniziale-2").val()!="" || $(".iniziale-3").val()!=""){
                        $("#ricami").attr('style','border:5px solid #347C2C;');
                        //remove element if exist
                        $("#info_scelte .ricami").remove();
                        //remove ricamo disattiva
                        
                        if($("#ordine_simbolo").val() == "1"){ // hai selezionato la lettera iniziale
                            $("#info_scelte").append("<li class='ricami'><i class='fas fa-caret-right'></i>  Ricami:  "+" <img src='"+ $(".simbolo.active_simbolo").find("img").attr("src")+"'width='30px'>"+ $(".iniziale-1").val().toUpperCase() +"."+ $(".iniziale-2").val().toUpperCase()+" "+ $(".iniziale-3").val().toUpperCase()+".</li>");
                        }else if($("#ordine_simbolo").val() == "2"){ //dopo la prima lettera
                            $("#info_scelte").append("<li class='ricami'><i class='fas fa-caret-right'></i>  Ricami:  "+ $(".iniziale-1").val().toUpperCase() +"."+" <img src='"+ $(".simbolo.active_simbolo").find("img").attr("src")+"'width='30px'>"+$(".iniziale-2").val().toUpperCase()+$(".iniziale-3").val().toUpperCase()+"</li>");
                        }else if($("#ordine_simbolo").val() == "3"){ //seleziona la lettera alla fine
                            $("#info_scelte").append("<li class='ricami'><i class='fas fa-caret-right'></i>  Ricami:  "+ $(".iniziale-1").val().toUpperCase() +"."+ $(".iniziale-2").val().toUpperCase()+"."+ $(".iniziale-3").val().toUpperCase() +"."+" <img src='"+ $(".simbolo.active_simbolo").find("img").attr("src")+"'width='30px'></li>");
                        }else{ // in tutti gli altri casi
                            $("#info_scelte").append("<li class='ricami'><i class='fas fa-caret-right'></i>  Ricami: "+ $(".iniziale-1").val().toUpperCase() +"."+ $(".iniziale-2").val().toUpperCase()+"."+$(".iniziale-3").val().toUpperCase()+($(".iniziale-3").val() ? ".": "" )+"</li>"); 
                        }
                    }else{
                        $("#ricami").attr('style','border: 5px solid #023047;');
                        if($("#info_scelte .ricami")){
                            $("#info_scelte .ricami").remove();
                        }
                    }
                    if($("#no").is(":checked")){
                        $(".ricami").remove();

                  /*       $(".ricami").attr('style','background-color:#023047;');
                        $(".ricami").attr('style','background-color:#023047;'); */
                    }
                });
                //disabled ricamo
                $("#no").click(function(){
                   /*$( "input.iniziali" ).prop( "disabled", true );
                    $(".scelta_ricami").addClass("disabled"); */
                    $(".row_ric").hide();
                    $(".ricami").attr('style','background-color:#023047;');
                });
                //enable ricamo
                $("#yes").click(function(){
                    /* $( "input.iniziali" ).prop( "disabled", false);
                    $(".scelta_ricami").removeClass("disabled"); */
                    $(".row_ric").show();
                });
               /*  $(".toggle-radio").click(function(){
                    if($(".toggle-radio").is(":checked")){
                        $( "input.iniziali" ).prop( "disabled", true );
                        $(".scelta_ricami").addClass("disabled");
                    }else{
                        $( "input.iniziali" ).attr( "disabled", false);
                        $(".scelta_ricami").removeClass("disabled");
                    }
                }); */
            });
        }
    });
}
//aggiungi ricamo
function getRicami(){
    var ultima_immagine = 1;
    $('.stile').unbind();
    $(".stile").click(function(){
        $(".stile").removeClass("active_stile");
        $(this).addClass("active_stile");
        let iniziale_1 = $(".iniziale-1").val();
        let iniziale_2 = $(".iniziale-2").val();
        let iniziale_3 = $(".iniziale-3").val();
        let stile = $(".stile.active_stile").attr("name");
        if(stile != undefined && iniziale_1 != ""){
            var ricamo = "";
            ultima_immagine = 1;
            //non fa ripetere l'img ricamo
            $("#risultato_ricamo").empty();
            ricamo += "<img id='img_1' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_1.toUpperCase()] + ".png' alt='corsivo'>";
            ricamo += iniziale_2 != "" ? "<img id='img_2' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_2.toUpperCase()] + ".png' alt='corsivo'>" : "";
            ricamo += iniziale_3 != "" ? "<img id='img_3' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_3.toUpperCase()] + ".png' alt='corsivo'>" : "";
            $("#risultato_ricamo").append(ricamo);
            iniziale_2 != "" ? ultima_immagine = 2 : "";
            iniziale_3 != "" ? ultima_immagine = 3 : "";
        }
    });

    $(".iniziale-1").unbind();
    $(".iniziale-1").change(function(){
        if($(this).val() != ""){
            $(".iniziale-2").removeAttr("disabled");
        }else{
            $(".iniziale-2").val("");
            $(".iniziale-2").attr("disabled", "disabled");
            $(".iniziale-3").val("");
            $(".iniziale-3").attr("disabled", "disabled");
        }
    });

    $(".iniziale-2").unbind();
    $(".iniziale-2").change(function(){
        if($(this).val() != ""){
            $(".iniziale-3").removeAttr("disabled");
        }else{
            $(".iniziale-3").val("");
            $(".iniziale-3").attr("disabled", "disabled");
        }
    });

    let mappatura_lettere = {
        "A" : "01",
        "B" : "02",
        "C" : "03",
        "D" : "04",
        "E" : "05",
        "F" : "06",
        "G" : "07",
        "H" : "08",
        "I" : "09",
        "J" : "10",
        "K" : "11",
        "L" : "12",
        "M" : "13",
        "N" : "14",
        "O" : "15",
        "P" : "16",
        "Q" : "17",
        "R" : "18",
        "S" : "19",
        "T" : "20",
        "U" : "21",
        "V" : "22",
        "W" : "23",
        "X" : "24",
        "Y" : "25",
        "Z" : "26"
    }

    $('.iniziali').bind('keyup blur',function(){
        var node = $(this);
        node.val(node.val().replace(/[^a-zA-Z]/g,'') );
    });

    $(".iniziali").change(function(){
        let iniziale_1 = $(".iniziale-1").val();
        let iniziale_2 = $(".iniziale-2").val();
        let iniziale_3 = $(".iniziale-3").val();
        let stile = $(".stile.active_stile").attr("name");
        if(stile != undefined && iniziale_1 != ""){
            var ricamo = "";
            ultima_immagine = 1;
            $("#risultato_ricamo").empty();
            ricamo += "<img id='img_1' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_1.toUpperCase()] + ".png' alt='corsivo'>";
            ricamo += iniziale_2 != "" ? "<img id='img_2' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_2.toUpperCase()] + ".png' alt='corsivo'>" : "";
            ricamo += iniziale_3 != "" ? "<img id='img_3' src='imgs/imgRicami/lettere-" + stile + "_" + mappatura_lettere[iniziale_3.toUpperCase()] + ".png' alt='corsivo'>" : "";
            $("#risultato_ricamo").append(ricamo);
            iniziale_2 != "" ? ultima_immagine = 2 : "";
            iniziale_3 != "" ? ultima_immagine = 3 : "";
        }
    });

    $('.btn_color_lettere').unbind();
    $(".btn_color_lettere").click(function(){
       $(".btn_color_lettere").removeClass("active_color");
       $(this).addClass("active_color");
    });

    $('.btn_color_simbolo').unbind();
    $(".btn_color_simbolo").click(function(){
        $(".btn_color_simbolo").removeClass("active_color_simbolo");
        $(this).addClass("active_color_simbolo");
    });

    $('.simbolo').unbind();
    $(".simbolo").click(function(){
        $(".simbolo").removeClass("active_simbolo");
        $(this).addClass("active_simbolo");
        $("#default_ordine").prop("selected", "selected");
        $("#immagine_risultante").remove();
        checkMostraOrdineSimbolo();
    });

    $('.posizione').unbind();
    $(".posizione").click(function(){
        $(".posizione").removeClass("active_posizione");
        $(this).addClass("active_posizione");
        checkMostraOrdineSimbolo();
    });

    $('.posizione_simbolo').unbind();
    $(".posizione_simbolo").click(function(){
        $(".posizione_simbolo").removeClass("active_posizione_simbolo");
        $(this).addClass("active_posizione_simbolo");
        checkMostraOrdineSimbolo();
    });

    $("#ordine_simbolo").unbind();
    $("#ordine_simbolo").change(function(){
        var scelta = $(this).val();
        $("#immagine_risultante").remove();
        if(scelta != 0){
            $(".simbolo_risultato").remove();
            var simbolo_selezionato = $(".simbolo.active_simbolo").find("img").attr("src");
            if(scelta == 1){
                $("#img_1").before("<img id='immagine_risultante' src='" + simbolo_selezionato + "' alt='simbolo_1'>");
            }else if(scelta == 2){
                $("#img_1").after("<img id='immagine_risultante' src='" + simbolo_selezionato + "' alt='simbolo_1'>");                
            }else if(scelta == 3){
                $("#img_"+ultima_immagine).after("<img id='immagine_risultante' src='" + simbolo_selezionato + "' alt='simbolo_1'>");
            }
        }
    });
}

function checkMostraOrdineSimbolo(){
    var simbolo_scelto = $(".simbolo.active_simbolo").attr("id");
    var posizione_simbolo = $(".posizione_simbolo.active_posizione_simbolo").attr("id");
    var posizione_lettere = $(".posizione.active_posizione").attr("id");
    var i_posizione_simbolo = posizione_simbolo.substring(posizione_simbolo.indexOf("_") + 1, posizione_simbolo.length);
    var i_posizione_lettere = posizione_lettere.substring(posizione_lettere.indexOf("_") + 1, posizione_lettere.length);
    if(i_posizione_simbolo == i_posizione_lettere){
        if($(".active_simbolo").attr("id") == "no_simbolo"){
            $(".ordine_simbolo").css("display", "none");
        }else{
            $(".ordine_simbolo").css("display", "flex");
        }
    }else{
        $(".ordine_simbolo").css("display", "none");
    }
}

function getAlterazioni(){
    $("#info_scelte").show();
    $("#scelta_alterazioni").empty();
    $("#scelta_alterazioni").append("<div class='blu-btn click_alterazioni' id='alt_CORPO' name='corpo'>Lunghezza Corpo</div>");
    $("#scelta_alterazioni").append("<div class='blu-btn click_alterazioni' id='alt_SPALLE' name='spalle'>Larghezza Spalle</div>");
    $("#scelta_alterazioni").append("<div class='blu-btn click_alterazioni' id='alt_TORACE' name='torace'>Larghezza Torace</div>");
    $("#scelta_alterazioni").append("<div class='blu-btn click_alterazioni' id='alt_VITA' name='vita'>Larghezza Vita</div>");
    $("#scelta_alterazioni").append("<div class='blu-btn click_alterazioni' id='alt_BACINO' name='bacino'>Larghezza Bacino</div>");
    clickAlterazioni();
}

function clickAlterazioni(){
    $(".click_alterazioni").unbind();
    $(".click_alterazioni").click(function(){
        let name = $(this).attr("name");
        //get collar
        $.ajax({
            url: "/lectra/stage/init.php",
            method: "POST",
            dataType: "json",
            data: {
                action: "get_alterations"
            }
        }).done((res) => {
            //$("#modifica_alterazione").empty();
            if(res.output.success === 1) {
                if (name == "corpo") {
                    if(alterazioni[name] == undefined || alterazioni[name] != 1){
                        let option = "<option value=''>Seleziona il valore:</option>";
                        var valori_corpo = res.output.data["3"].VALORI_DA_VISUALIZZARE;
                        var valori_invio_corpo = res.output.data["3"].VALORI_DA_INVIARE;
                        for(i in valori_corpo){
                            option += "<option value='" + valori_invio_corpo[i] + "'>" + valori_corpo[i] + "</option>";
                        }
                        let checkbox = "<h2>Davanti diverso da dietro:</h2>" +
                            "<div class='checkbox-corpo form-check form-switch'>" +
                            "<input id='corpo_check' class='form-check-input' type='checkbox'>" +
                            "</div>";
                        $("#modifica_alterazione_corpo").append(checkbox);
                        $("#modifica_alterazione_corpo").append("<h2 class='corpo_entrambi'>Modifica davanti e dietro:</h2>");
                        $("#modifica_alterazione_corpo").append("<select class='corpo_entrambi' id='select_" + name + "_entrambe'>" + option + "</select>");
                        $("#modifica_alterazione_corpo").append("<h2 class='corpo_davanti'>Modifica lunghezza davanti:</h2>");
                        $("#modifica_alterazione_corpo").append("<select class='corpo_davanti' id='select_" + name + "_davanti'>" + option + "</select>");
                        $("#modifica_alterazione_corpo").append("<h2 class='corpo_dietro'>Modifica lunghezza dietro:</h2>");
                        $("#modifica_alterazione_corpo").append("<select class='corpo_dietro' id='select_" + name + "_dietro'>" + option + "</select>");
                        alterazioni[name] = 1;
                    }
                    $(".container-selezione_alterazioni").hide();
                    $(".container-scelta_alterazioni_corpo").show();
                    $("#corpo_check").unbind();
                    $("#corpo_check").change(function () {
                        if($(this).is(":checked")){ // se è selezionata
                            $(".corpo_entrambi").hide();
                            $(".corpo_davanti").show();
                            $(".corpo_dietro").show();
                        }else{
                            $(".corpo_entrambi").show();
                            $(".corpo_davanti").hide();
                            $(".corpo_dietro").hide();
                        }
                    });
                    //click dell'input svuota la select e resetta il summary
                    $(".form-check-input").click(function(){
                        $("#modifica_alterazione_corpo .corpo_entrambi").prop('selectedIndex',0);
                        $(".alt_all").empty();
                    })
                    $("#indietro-scelta_alterazioni_corpo").unbind();
                    $("#indietro-scelta_alterazioni_corpo").click(function () {
                        $(".container-selezione_alterazioni").show();
                        $(".container-scelta_alterazioni_corpo").hide();

                        //summury lunghezza corpor
                        if($(".alt_all").length == 0){
                            $("#info_scelte").append("<details><summary class='recap_info'>ALTERAZIONI</summary><ul class='alt_all'></ul></details>");
                        }

                        if($("#select_corpo_dietro").find(":selected").val()!="" || $("#select_corpo_davanti").find(":selected").val()!="" || $("#select_corpo_entrambe").find(":selected").val()!=""){
                            $("#alt_CORPO").attr('style','background-color:#347C2C;');
                            if($("#select_corpo_entrambe").find(":selected").val() ==""){
                                $(".alt_all").append("<li class='corpo-davanti'>Corpo Davanti:"+ $("#select_corpo_davanti").find(":selected").val()+"</li>");
                                $(".alt_all").append("<li class='corpo-dietro'>Corpo Dietro:"+ $("#select_corpo_dietro").find(":selected").val()+"</li>");
                            }else{
                                $(".corpo-entrambe").empty();
                                $(".alt_all").append("<li class='corpo-entrambe'>Corpo Davanti e dietro:"+ $("#select_corpo_entrambe").find(":selected").val()+"</li>");
                            }
                        }else{
                            $("#alt_CORPO").attr('style','background-color:#023047;');
                            $(".corpo-entrambe").remove();
                            $(".corpo-davanti").remove();
                            $(".corpo-dietro").remove();
                        }
                    });
                }else if(name == "spalle"){
                    if(alterazioni[name] == undefined || alterazioni[name] != 1){
                        let option = "<option value=''>Seleziona il valore:</option>";
                        var valori_spalle = res.output.data["6"].VALORI_DA_VISUALIZZARE;
                        var valori_invio_spalle = res.output.data["6"].VALORI_DA_INVIARE;
                        for(i in valori_spalle){
                            option += "<option value='" + valori_invio_spalle[i] + "'>" + valori_spalle[i] + "</option>";
                        }
                        $("#modifica_alterazione_spalle").append("<h2>Modifica larghezza spalle:</h2>");
                        $("#modifica_alterazione_spalle").append("<select id='select_" + name + "'>" + option + "</select>");
                        alterazioni[name] = 1;
                    }
                    $(".container-selezione_alterazioni").hide();
                    $(".container-scelta_alterazioni_spalle").show();
                    $("#indietro-scelta_alterazioni_spalle").unbind();
                    $("#indietro-scelta_alterazioni_spalle").click(function () {
                        $(".container-selezione_alterazioni").show();
                        $(".container-scelta_alterazioni_spalle").hide();
                        if($("#select_spalle").find(":selected").val()!=""){
                            $("#alt_SPALLE").attr('style','background-color:#347C2C;');
                            $(".alt_all").append("<li class='corpo-spalle'>Larghezza Spalle:"+ $("#select_spalle").find(":selected").val()+"</li>");
                        }else{
                            $("#alt_SPALLE").attr('style','background-color:#023047;');
                            $(".corpo-spalle").remove();
                        }
                    });
                }else if(name == "torace"){
                    if(alterazioni[name] == undefined || alterazioni[name] != 1){
                        let option = "<option value=''>Seleziona il valore:</option>";
                        var valori_torace = res.output.data["7"].VALORI_DA_VISUALIZZARE;
                        var valori_invio_torace = res.output.data["7"].VALORI_DA_INVIARE;
                        for(i in valori_torace){
                            option += "<option value='" + valori_invio_torace[i] + "'>" + valori_torace[i] + "</option>";
                        }
                        $("#modifica_alterazione_torace").append("<h2>Modifica larghezza torace:</h2>");
                        $("#modifica_alterazione_torace").append("<select id='select_" + name + "'>" + option + "</select>");
                        alterazioni[name] = 1;
                    }
                    $(".container-selezione_alterazioni").hide();
                    $(".container-scelta_alterazioni_torace").show();
                    $("#indietro-scelta_alterazioni_torace").unbind();
                    $("#indietro-scelta_alterazioni_torace").click(function () {
                        $(".container-selezione_alterazioni").show();
                        $(".container-scelta_alterazioni_torace").hide();
                        if($("#select_torace").find(":selected").val()!=""){
                            $("#alt_TORACE").attr('style','background-color:#347C2C;');
                            $(".alt_all").append("<li class='corpo-torace'>Larghezza Torace:"+ $("#select_torace").find(":selected").val()+"</li>");
                        }else{
                            $("#alt_TORACE").attr('style','background-color:#023047;');
                            $(".corpo-torace").remove();
                        }
                    });
                }else if(name == "vita"){
                    if(alterazioni[name] == undefined || alterazioni[name] != 1){
                        let option = "<option value=''>Seleziona il valore:</option>";
                        var valori_vita = res.output.data["8"].VALORI_DA_VISUALIZZARE;
                        var valori_invio_vita = res.output.data["8"].VALORI_DA_INVIARE;
                        for(i in valori_vita){
                            option += "<option value='" + valori_invio_vita[i] + "'>" + valori_vita[i] + "</option>";
                        }
                        $("#modifica_alterazione_vita").append("<h2>Modifica larghezza vita:</h2>");
                        $("#modifica_alterazione_vita").append("<select id='select_" + name + "'>" + option + "</select>");
                        alterazioni[name] = 1;
                    }
                    $(".container-selezione_alterazioni").hide();
                    $(".container-scelta_alterazioni_vita").show();
                    $("#indietro-scelta_alterazioni_vita").unbind();
                    $("#indietro-scelta_alterazioni_vita").click(function () {
                        $(".container-selezione_alterazioni").show();
                        $(".container-scelta_alterazioni_vita").hide();
                        if($("#select_vita").find(":selected").val()!=""){
                            $("#alt_VITA").attr('style','background-color:#347C2C;');
                            $(".alt_all").append("<li class='corpo-vita'>Larghezza Vita:"+ $("#select_vita").find(":selected").val()+"</li>");
                        }else{
                            $("#alt_VITA").attr('style','background-color:#023047;');
                            $(".corpo-vita").remove();
                        }
                    });
                }else if(name == "bacino"){
                    if(alterazioni[name] == undefined || alterazioni[name] != 1){
                        let option = "<option value=''>Seleziona il valore:</option>";
                        var valori_bacino = res.output.data["9"].VALORI_DA_VISUALIZZARE;
                        var valori_invio_bacino = res.output.data["9"].VALORI_DA_INVIARE;
                        for(i in valori_bacino){
                            option += "<option value='" + valori_invio_bacino[i] + "'>" + valori_bacino[i] + "</option>";
                        }
                        $("#modifica_alterazione_bacino").append("<h2>Modifica larghezza bacino:</h2>");
                        $("#modifica_alterazione_bacino").append("<select id='select_" + name + "'>" + option + "</select>");
                        alterazioni[name] = 1;
                    }
                    $(".container-selezione_alterazioni").hide();
                    $(".container-scelta_alterazioni_bacino").show();
                    $("#indietro-scelta_alterazioni_bacino").unbind();
                    $("#indietro-scelta_alterazioni_bacino").click(function () {
                        $(".container-selezione_alterazioni").show();
                        $(".container-scelta_alterazioni_bacino").hide();
                        if($("#select_bacino").find(":selected").val()!=""){
                            $("#alt_BACINO").attr('style','background-color:#347C2C;');
                            $(".alt_all").append("<li class='corpo-bacino'>Larghezza Bacino:"+ $("#select_bacino").find(":selected").val()+"</li>");
                        }else{
                            $("#alt_BACINO").attr('style','background-color:#023047;');
                            $(".corpo-bacino").remove();
                        }
                    });
                }
            }
        });
    });
}  

function parseRequestSalvaProdotti(ordine_id, articoli){
    // json format
    data_products = [];
    for (let id_art of Object.keys(articoli)){
        art = articoli[id_art];
        data_art = {};
        data_art.id = "";
        data_art.product_id = art.vestibilita;
        data_art.qty = art.qty;
        data_art.grading = art.taglia;
        data_art.scollo = art.taglia_collo;
        data_art.comment = "";
        data_art.gender = "";
        data_art.assortment = "";
        data_art.specialGrading1 = "";
        data_art.bodyReference = "";
        data_art.components = art.componenti;
        data_art.alterations = art.alterazioni;
        if(art.ricamo != {}){
            data_art.ricamo = art.ricamo;
        }
        data_art.bottoni_mp = art.bottoni_mp;
        data_products.push(data_art);
    }
    $.ajax({
        url: "/lectra/stage/init.php",
        dataType: "json",
        data: {
            action: "save_order_product",
            val_fields: {
                ordine_id : ordine_id,
                products : data_products
            }
        }
      }).done(function(res){
          if(res && res.output.success == 1){

          }else{
              alert("errore aggiunta articoli");
          }
    });
}

function parseRequestApprovaOrdine(ordine_id){
    $.ajax({
        url: "/lectra/stage/init.php",
        dataType: "json",
        data: {
            action: "approve_order",
            val_fields: {
                ordine_id : ordine_id
            }
        }
      }).done(function(res){
          if(res && res.output.success == 1){
              swal("Ordine Spedizione Inviato...","","success").then((value) => {
                  location.reload();
              });
          }else{
              alert("errore aggiunta articoli");
          }
    });
}

function creaRiepilogo(articoli){
    let caretteristiche_magliette_ordine = {};
    caretteristiche_magliette_ordine.davanti = valore_camicia_scelta_davanti ;
    caretteristiche_magliette_ordine.dietro = valore_camicia_scelta_dietro ;
    caretteristiche_magliette_ordine.polsi = valore_camicia_scelta_manica_polsi;
    caretteristiche_magliette_ordine.tasche = valore_camicia_scelta_tasche_pattine ;
    caretteristiche_magliette_ordine.collo = valore_camicia_scelta_collo ;
    caretteristiche_magliette_ordine.materiali = $("#materiali").find(":selected").text();
    var str_materiali = "";
    //get collar
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        async: false,
        data: {
            action: "get_field_data",
            val_field: "materiale"
        }
    }).done((res) => {
        if(res.output.success === 1){
            options = res.output.data;
            for(row in options){
                if(options[row].DESCRIZIONE == caretteristiche_magliette_ordine.materiali) {
                    var select = "selected";
                }else{
                    var select = "";
                }
                str_materiali += "<option "+select+" value='" + options[row]["ID"] + "'>" + options[row].DESCRIZIONE + "</option>"
            }
            caretteristiche_magliette_ordine.selected = str_materiali;
        }
    });
    //OPPURE
    caretteristiche_magliette_ordine_totali = {
        totale : valore_camicia_scelta_davanti+valore_camicia_scelta_dietro+valore_camicia_scelta_manica_polsi+valore_camicia_scelta_tasche_pattine+valore_camicia_scelta_collo
    };
    $(".container_shirt").empty();
    let i = 1;
    for (var id_art of Object.keys(articoli)){
        articolo = articoli[id_art];
        $(".container_shirt").append( // appendo tutto il contenuto
            "<tr id='row_camicia_"+id_art+"' class='row_camicia-selezionata' style='border:0.6px solid #fff'>"+
                "<td>"+
                "<b><h6>Camicia n°:"+i+"</h6></b>"+
                "<ul>"+
                    "<li>"+articolo.descrizioni.davanti+"</li>"+
                    "<li>"+articolo.descrizioni.dietro+"</li>"+
                    "<li>"+articolo.descrizioni.polsi+"</li>"+
                    "<li>"+articolo.descrizioni.tasche+"</li>"+
                    "<li>"+articolo.descrizioni.collo+"</li>"+
                    "<li id='desc_materiale_"+id_art+"'><b>Materiale<b>: "+articolo.descrizioni.materiale_primario+"</li>"+
                "</ul>"+
                "</td>"+
                "<td>"+
                    // "<i id='modifica_"+id_art+"' class='modificaBtn action_btn fas fa-pencil-alt' data-id-camicia='"+id_art+"'></i>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<i id='elimina_"+id_art+"' class='deleteBtn action_btn fas fa-trash-alt' data-id-camicia='"+id_art+"'></i>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<i id='duplica_"+id_art+"' class='duplicaBtn action_btn fas fa-copy' data-id-camicia='"+id_art+"'></i>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<br>"+
                    "<div> "+
                    "<select class='mate_select' id='materialiduplicati_"+id_art+"' >"+
                        caretteristiche_magliette_ordine.selected+
                    "</select>"+
                    "<div class='save_materiale' id='salvaMateriale_"+id_art+"' data-id-camicia='"+id_art+"'><span class='text_save'>SALVA MATERIALE<span></div> </div>"+
                "</td>"+
            "</tr>");
        i++;
    }
    //salva materiali
    eventSalvaMateriale();
    valore_camicia_scelta_davanti = "";
    valore_camicia_scelta_dietro = "";
    valore_camicia_scelta_manica_polsi = "";
    valore_camicia_scelta_tasche_pattine = "";
    valore_camicia_scelta_collo = "";
    refreshBtnAggiornaCamicia();
}

function eventSalvaMateriale() {
    $(".save_materiale").unbind();
    $(".save_materiale").click(function() {
        let key = $(this).data("id-camicia");
        var id = $(this).attr("id");
        var id_camicia = id.substring(id.indexOf("_")+1, id.length);
        var description_materiale = $("#materialiduplicati_"+id_camicia).find(":selected").text();
        var id_materiale = $("materialiduplicati_"+id_camicia).val();
        $("#desc_materiale_"+id_camicia).text("Materiale: "+description_materiale);
        ordine_lectra.articoli[key].materiale_primario_id = $("#materialiduplicati_"+id_camicia).val();
        ordine_lectra.articoli[key].descrizioni.materiale_primario = description_materiale;
    })
}

function parseRequestGetComponente(id_componente, manica_lunga, n_tasche , check_carre , id_camicia_selezionata){
    $("#carouselExampleControls .carousel-inner").empty();
    $(".carousel-bottoni").empty();
    //AJAX
    $.ajax({
        url: "/lectra/stage/init.php",
        method: "POST",
        dataType: "json",
        data: {  // i dati che invio al server
            action: "get_componente",
            val_component_type_id: id_componente  /*gli passo l'id*/
        }
    }).done(function(res){
        $("#carouselExampleControls").show();
        $(".container-selezione_davanti").show(); // rendo visibile il carosello
        $(".container-selezione_componenti").hide(); // lo rendo invisibile
        $("#indietro-scelta-camicia").show(); //rendo visibile il tasto indietro della camicia
        $(".carousel-inner").empty(); // svuoto il carosello
        if(res.output.success == 1){
            var json_camicie = res.output.data;
            var codice_camicia = " ";
            var has_polsi = "";
            var count = 1;
            var count_maglietta_selezionata = 1;
            var cls_active_not_selected = "active"; 
            let cls_active = "active";
            var maglietta_selezionata = false;
            for(let id of Object.keys(json_camicie)){
                codice_camicia = json_camicie[id]["CODICE"]; //codice
                descrizione_camicia = json_camicie[id]["DESCRIZIONE"]; // descrizione
                has_contrasto = json_camicie[id]["HAS_CONTRAST"];
                n_tasche_camicia = json_camicie[id]["N_TASCHE"]; // numero tasche
                has_polsi = json_camicie[id]["HAS_POLSI"];
                has_carre = json_camicie[id]["HAS_CARRE_INTERO"] ; // stampo il carre delle camicie 1 o 0
                if(check_carre !=null){ //se è diverso da null
                    if(check_carre){  // se la scelta dell'utente del carre è true
                        if(has_carre !=1){  // controllo nell'ajax se è diverso da 1 , se è cosi continua.
                            continue;
                        }
                    }else{ // se la scelta dell'utente del carre è false
                        if(has_carre !=0){ // controllo nell'ajax se è diverso da 0 , se è cosi continua.
                            continue;
                        }
                    }
                }
                if(n_tasche_camicia != null){
                    if(n_tasche == 0){ // Selezione utente (checkbox)
                        if(n_tasche_camicia != 0){
                            continue; //appena trova questa istruzione si ritorna all'inizio del ciclo
                             // Se l'utente ha selezionato 1 tasca ma l'elemento non ha lo stesso numero di tasche salta la sua creazione
                        }
                    }
                    if(n_tasche == 1){ // Selezione utente (checkbox)
                        if(n_tasche_camicia != 1){

                            continue; // Se l'utente ha selezionato 1 tasca ma l'elemento non ha lo stesso numero di tasche salta la sua creazione
                        }
                    }
                    if(n_tasche == 2){ // Selezione utente (checkbox)
                        if(n_tasche_camicia != 2){
                            continue; // Se l'utente ha selezionato 1 tasca ma l'elemento non ha lo stesso numero di tasche salta la sua creazione
                        }
                    }
                }
                if(manica_lunga != null){ // Selezione utente (checkbox)
                    if(has_polsi == 0 && manica_lunga){ // Visualizza solo Manica corta
                        continue;
                    }else if(has_polsi == 1 && !manica_lunga){ // Visualizza solo Manica lunga
                        continue;
                    }
                }
                if(has_contrasto == 0){
             
                         
                        if(click_seleziona){


                            //SE AL PRIMO GIRO DEL FOR HO TROVATO LA CAMICIA SELEZIONA ENTRO 
                            //AL SECONDO GIRO DEL FOR ENTRA CON maglietta_selezionata = TRUE 
                            //E LE ALTRE CAMICIE DALLA SECONDA IN POI LE METTO NON ATTIVE
                            if(id_camicia_selezionata == json_camicie[id]["ID"] || maglietta_selezionata === true ){ // controllo se il codice della camicia è quello che è stato selezionato

                                console.log("codice camicia selezionata"+codice_maglietta); 
                                console.log("codice camicia json"+codice_camicia); 
                                console.log(" i codici della maglietta  sono uguali"); // i codici dell amaglietta sono uguali  
                                
                                
                                $("#carouselExampleControls .carousel-inner").append("<div class='carousel-item "+cls_active+"' id='carosello_id_"+count_maglietta_selezionata+"' >");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<div class='desc_shirt'><p class='text_carousel' style='font-size:18px' ><b>"+codice_camicia+"<br>"+descrizione_camicia+"</br></b></p></div>");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<div class='cut_img'><img src='imgs/image_componenti/"+codice_camicia+".jpg' class='d-block w-50' alt='...'></div>");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='codice_camicia' type=\"hidden\" value="+codice_camicia+">");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='codice_camicia_id' type=\"hidden\" value="+json_camicie[id]["ID"]+">");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='desc_camicia' type=\"hidden\" value='"+descrizione_camicia+"'>");
                                $("#carouselExampleControls .carousel-inner").append("</div>");   
                                cls_active = "";
                                count_maglietta_selezionata++;
                                maglietta_selezionata = true ;
                                codice_maglietta = "";  // lo svuto 
                              
                            }else{ // SE AL PRIMO GIRO DEL FOR NON TROVO LA CAMICIA SELEZIONATA CHE HO SCELTO IO,
                                   // LE CAMICIE ANTECEDENTI ALLA MIA CHE HO SCELTO LE METTO NON ACTIVE 

                                
                                $("#carouselExampleControls .carousel-inner").append("<div class='carousel-item' id='carosello_id_"+count_maglietta_selezionata+"' >");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<div class='desc_shirt'><p class='text_carousel' style='font-size:18px' ><b>"+codice_camicia+"<br>"+descrizione_camicia+"</br></b></p></div>");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<div class='cut_img'><img src='imgs/image_componenti/"+codice_camicia+".jpg' class='d-block w-50' alt='...'></div>");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='codice_camicia' type=\"hidden\" value="+codice_camicia+">");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='codice_camicia_id' type=\"hidden\" value="+json_camicie[id]["ID"]+">");
                                $("#carouselExampleControls .carousel-inner #carosello_id_"+count_maglietta_selezionata).append("<input class='desc_camicia' type=\"hidden\" value='"+descrizione_camicia+"'>");
                                $("#carouselExampleControls .carousel-inner").append("</div>");
                                count_maglietta_selezionata++;  
                        
                            }

                        }else{  //CASO DI DEFAULT QUANDO NON HO SELEZIONATO UNA CAMICIA , VISUALIZZO IL CAROSELLO 
                                //IN BASE ALLA SEZIONE CHE HO SCELTO(DAVANTI O DIETRO O MANICHE O TASCHE O COLLO)
                            
                            $("#carouselExampleControls .carousel-inner").append("<div class='carousel-item "+cls_active_not_selected+"' id='carosello_id_"+count+"' >");
                            $("#carouselExampleControls .carousel-inner #carosello_id_"+count).append("<div class='desc_shirt'><p class='text_carousel' style='font-size:18px' ><b>"+codice_camicia+"<br>"+descrizione_camicia+"</br></b></p></div>");
                            $("#carouselExampleControls .carousel-inner #carosello_id_"+count).append("<div class='cut_img'><img src='imgs/image_componenti/"+codice_camicia+".jpg' class='d-block w-50' alt='...'></div>");
                            $("#carouselExampleControls .carousel-inner #carosello_id_"+count).append("<input class='codice_camicia' type=\"hidden\" value="+codice_camicia+">");
                            $("#carouselExampleControls .carousel-inner #carosello_id_"+count).append("<input class='codice_camicia_id' type=\"hidden\" value="+json_camicie[id]["ID"]+">");
                            $("#carouselExampleControls .carousel-inner #carosello_id_"+count).append("<input class='desc_camicia' type=\"hidden\" value='"+descrizione_camicia+"'>");
                            $("#carouselExampleControls .carousel-inner").append("</div>");
                            cls_active_not_selected = "";
                            count++;
                        }

                               
                    
                }           
                

            }

            click_seleziona = false;

            $(".carousel-bottoni").append(
                "<button class='carousel-control-prev' type='button' data-bs-target='#carouselExampleControls' data-bs-slide='prev'>"+
                "<span style='background-color: #023047' class='carousel-control-prev-icon' aria-hidden='true'></span>"+
                "<span style='background-color: #023047' class='visually-hidden'>Previous</span>"+
                "</button>"+
                "<button class='carousel-control-next' type='button' data-bs-target='#carouselExampleControls' data-bs-slide='next'>"+
                    "<span style='background-color: #023047' class='carousel-control-next-icon' aria-hidden='true'></span>"+
                    "<span style='background-color: #023047' class='visually-hidden'>Next</span>"+
                "</button>");
        }else{
            swal("Errore chiamata Ajax","Errore" + res.output.error,"warning");
        }
    });
}