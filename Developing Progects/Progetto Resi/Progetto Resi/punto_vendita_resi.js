/*VARIABILI GLOBALI*/
var MySelects_values = "" ; //parte da vuoto 
var valore_selezionato_library = ""; 
var data_da ="";
var data_a =""; 
var capoarea = "";   
var citta =""; 
var nome_negozi_all_singoli_raggruppati = ""; 
var nome_negozi_singoli_raggruppati = ""; 
var nome_negozio_singolo = ""; 
var codice_magazzino ="";
var prog_vend = "";
var progressivo_vendita_global_filter = ""; 
var codice_magazzino_filtrato = "";
var no_data_negozi_all_raggruppati = false; 
var no_data_negozi_raggruppati = false; 

/*function myfunction(){

    console.log("la variabile library ha valore : "+valore_selezionato_library)
}*/

$(document).ready(function(){ //QUANDO HO FINITO DI LEGGER TUTTO L'HTML


    // istanzio la mia classe Sorter ;
    var sorter = new Sorter(); 


    function get_negozi(){
        $.ajax({

            url:"../indagini/get_modulo_resi_negozio.php",
            type:"POST",
            dataType:"json",
            data:{

                library : valore_selezionato_library, 
                startdate : data_da,
                enddate : data_a, 
                city: "" ,
                clsValue: "",
                headarea: "", 
                shop: "",  
                tipologia:"",
                tp:""
            }
 
        }).done(function(res){
            
            console.log(res); 

            $("#ajaxloader_minus").css("display" , "none"); 


            //CONTROLLO SE LA CLASSE ESISTE 
            if($("#shop_negozi").length){

                //console.log("entra qui.........");

                //console.log("entra qui ed esiste la classe"); 

                //CANCELLO LA SUMO SELECT E TUTTO IL SUO CONTENUTO
                //NON LA RICREO PERCHÈ ME LA RICREA IN AUTOMATICO IL PLUGIN 
                $("#shop_negozi").remove();
                $(".sumo_shops").remove(); 
                $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
                
                //SVUOTO LA VARIABILE GLOBALE CHE CONTIENE I NEGOZI 
                MySelects_values = "" ; 


            }
             
            //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DEI NEGOZI
            var option_select_negozi ="";
            for(let valore in res["negozi"] ){

                var negozio = res["negozi"][valore]; 
                //console.log(negozio);  

               //var nome_negozio = res[valore]["NOME_NEGOZIO"]; 
               //var codice_negozio = res[valore]["CODNEG"]; 
                
               option_select_negozi = option_select_negozi + "<option  class='negozio_select_multiple' value="+valore+">"+negozio+"</option>"; 
            }



       
            //console.log(option_select_header); 

            $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
           
            //APPENDO ALLA SELECT LE OPTION CREATE
            $("#shop_negozi").append(option_select_negozi).show();

            //INSERISCO UNA SELECT CON SCIRTTO TUTTO 
            //AGIUNGO L'ATTRIBUTO MULTIPLE ALLA SELECT SHOP
            // <select id='shop' multiple='multiple' name='shops' class='med'>  
            //</select> 
            $("#shop_negozi").attr("multiple","multiple"); 
 

            //CHIAMO IL PLUGIN PER ABILITARE LA SUMO SELECT
            $("#shop_negozi").SumoSelect({

                selectAll: true, 
            
                csvDispCount: 3,

                search : true    // se voglio cercare uno specifico 

            });

            $('.negozio_select_multiple')[0].sumo.selectAll(); // seleziono  tutti i negozi 

            //disabilto FALLATI - CENTERGROSS ECOMM
            /*
            var element = $("label:contains('FALLATI - CENTERGROSS ECOMM')");  


            if (element !=""){

                console.log("entra qui "); 

                
                $('.negozio_select_multiple')[0].sumo.disableItem(61);
            }
            */

            //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
            $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 

                MySelects_values = $("#shop_negozi").val(); /* prendo i valori */
               
            });
            
        
            //PRENDO I VALORI SELEZIONATI DALLA SELECT NEGOZIO 
            $("#shop_negozi").change(function(){ 

                console.log("hai cliccato qui dentro la select dei negozi ");

                //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
                $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 
                    
                    MySelects_values = $("#shop_negozi").val(); /* prendo il valore del MySelects_values */
                    //alert("hai selezionato "+$(this).val()) ; //alert 
    
                });

                //LEGGO I VALORI 
                console.log("i valori nell'array sono: ") //console.log a video 
                for(let valore in MySelects_values){
                    console.log(MySelects_values[valore]); 
                }

            });

        }); 

    } //chiuso function negozi 


    function get_localita(){


        $.ajax({

            url:"../indagini/get_modulo_resi_localita.php",
            type:"POST",
            dataType:"json",
            data:{

                library : valore_selezionato_library, 
                startdate : data_da,
                enddate : data_a, 
                city: "",
                clsValue: "",
                headarea: "", 
                shop: "", 
                tipologia:"",
                tp:"", 
            }
 
        }).done(function(res){ // mi ritorna la risposta del server 


            //console.log(res); 
            
            //CANCELLO LO SPAN DELLA CITTA CHE CONTIENE LA SELECT DELLE LOCALITA
            if($("#city").length){

                //CANCELLO LE OPTION E LE RICREO
                $(".option_resi_localita").remove(); 

            } 

            
            //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DELLA LOCALITÀ
            var output_localita ="";
            for(let valore in res["localita"] ){ // per ogni località

                var localita = res["localita"][valore];  //prendo la località che l'utente clicca nella select 

                
                //console.log(localita);
                
              
                output_localita = output_localita + "<option  class='option_resi_localita' value="+valore+">"+localita+"</output>";

            }


            var option_tutto ="";
            option_tutto = "<option class='option_resi_localita' id='select_tutto_localita' >--Tutto--</option>"; 

            $("#city").append(option_tutto);  // aggiungo una option

            //APPENDO ALLA SELECT LA OPTION CREATA 
            $("#city").append(output_localita); 
                   
        }); 

    }

    function get_capo_area(){

        $.ajax({   
            url:"../indagini/get_modulo_resi_capo_area.php", 
            type:"POST",
            dataType: "json",
            data:{

                library : valore_selezionato_library, 
                startdate : $("#datepicker_data_da").val() ,
                enddate : $("#datepicker_data_a").val() , 
                city: "",
                clsValue: "",
                headarea: "", 
                shop: "", 
                tipologia:"",
                tp:"", 


            }
        }).done(function(res){ 

            var values_order  = sorter.sortArrayMap(res["capoarea"]); 

            console.log(values_order); 


            //CANCELLO LO SPAN DELLA LOCALITÀ CHE CONTIENE LA SELECT DEI CAPI AREA
            if($("#headarea").length){ // verifico la lunghezza della  

                //CANCELLO LE OPTION E LE RICREO 
                $(".option_resi_capo_area").remove();
            }  
        
        
            //qui costruisco la select di capo area 
            //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DEL CAPO AREA 
            var output_capoarea = ""; 

            for(let valore in values_order ){ 

                var cap_area = values_order[valore].val;  
                
                //console.log(capoarea);
        
                output_capoarea = output_capoarea + "<option class='option_resi_capo_area' value="+values_order[valore].cod+">"+cap_area+"</output>";

            }
         
            //APPENDO IL CONTENUTO 
            var option_tutto = "";  //svuoto la variabile
            option_tutto = "<option class='option_resi_capo_area' id='select_tutto_capoarea' >--Tutto--</option>"; 
            $("#headarea").append(option_tutto); 
 
            //APPENDO IL CONTENUTO  
            $("#headarea").append(output_capoarea); 
         

        }); 

    } // fine function get_capo_area
    

    $(".gif_caricamento").css("display", "none"); 
    $("#ajaxloader_minus").css("display" , "none"); 

    //DIALOG INFORMATIVA
    $('.info-icon').click(function(){

        $("#informazioni_progetto").prop("display" , "block"); 
        
        $("#informazioni_progetto").dialog({ 
                            

            autoOpen: false,     
            
            title: "INFORMAZIONI PROGETTO RESI ",

            position: {
                    my: 'center',
                    at: 'center',
                    of: window
            },

            width: 450,

            height:100
 
        });
     
        $("#informazioni_progetto").dialog( "open" );
     
 
    });


    //AGGIUNGO UNA OPTION SELECT LIBRERIA E LA SELEZIONO COME PRIMA DELLA LISTA
    $('#library').children(':first').before("<option value='title'>Scegli la Libreria</option>");
    $("#library").val("title").trigger("change"); 

 
    //ACTION CHANGE LIBRERIA 
    $("#library").change(function(){ 

        $("#ajaxloader_minus").css("display" , "block"); 

        //CONTROLLO SE LA TABELLA DEI RESI RAGGRUPPATI E' STATA CREATA E LA TABELLA DEI SINGOLI E' STATA CREATA 
        //CANCELLO LA TABELLA DEI RESI [FILTRATA] E LA TABELLA DEI RESI SINGOLI[FILTRATA]
        if($("#table_date_resi_singoli").length && $("#table_date_resi_raggruppati").length){ //CONTROLLO SE ESITE LA CLASSE 

            $("#table_date_resi_singoli").remove(); 
            $("#table_date_resi_raggruppati").remove();  
            $("#title_resi").hide();

        } 

        //CONTROLLO SE LA TABELLA DEI RESI RAGGRUPPATI E' STATA CREATA E LA TABELLA DEI SINGOLI E' STATA CREATA 
        //CANCELLO LA TABELLA [ALL] DEI RESI E LA TABELLA [ALL] DEI RESI SINGOLI
        if($("#table_date_all_resi_raggruppati").length && $("#table_date_all_resi_singoli").length){ //CONTROLLO SE ESITE LA CLASSE 

            $("#table_date_all_resi_raggruppati").remove(); 
            $("#table_date_all_resi_singoli").remove(); 
            $("#tot_scontrini_raggruppati_all").remove();
            $("#title_resi").hide();

        } 

        valore_selezionato_library = $(this).find(":selected").attr("value"); // prendo il valore della select della libreria 
        //console.log(valore_selezionato_library); 

    
         //COSTRUISCO LA SELECT DEI CAPI AREA 
        //FUNZIONE AJAX
        get_capo_area();


        //COSTRUISCO LA SELECT DELLA LOCALITA'
        //FUNZIONE AJAX  
        get_localita(); 
      
        //COSTRUISCO LA SELECT DEI NEGOZI
        //FUNZIONE AJAX 
        get_negozi(); 

        
 
    }); //FINE ACTION CHANGE LIBRERIA 
 

    //COSTRUISCO LA SELECT DEI CAPI AREA PLUS NEGOZI 
    //FUNZIONE AJAX   
   // get_capo_area_plus_city_plus_negozi(); 

    //function get_capo_area_plus_city_plus_negozi(){

        $("#headarea").change(function(){

            $("#ajaxloader_minus").css("display" , "block");
     
            //console.log("entra nel change della select del capo area");   
    
            //PRENDO IL CAPOAREA 
            capoarea = $(this).find(":selected").attr("value"); 
    
           
            //console.log(capoarea); 
        
    
            //FUNZIONE AJAX 
            //COSTRUISCO LA SELECT DELLA LOCALITÀ   
            $.ajax({
    
                url:"../indagini/get_modulo_resi_localita.php",
                type:"POST",
                dataType:"json",
                data:{
    
                    library : valore_selezionato_library, 
                    startdate : data_da,
                    enddate : data_a, 
                    city: "",
                    clsValue: "",
                    headarea: capoarea, 
                    shop: "", 
                    tipologia:"",
                    tp:"", 
                }
     
            }).done(function(res){ // mi ritorna la risposta del server 
    
    
                $("#ajaxloader_minus").css("display" , "none"); 
    
                //console.log(res); 
                
                //CANCELLO LO SPAN DELLA CITTA CHE CONTIENE LA SELECT DELLE LOCALITA
                if($("#city").length){
    
                    //CANCELLO LE OPTION E LE RICREO
                    $(".option_resi_localita").remove(); 
    
                } 
    
                
                //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DELLA LOCALITÀ
                var output_localita ="";
                for(let valore in res["localita"] ){ // per ogni località
    
                    var localita = res["localita"][valore];  //prendo la località che l'utente clicca nella select        
                  
                    output_localita = output_localita + "<option  class='option_resi_localita' value="+valore+">"+localita+"</output>";
    
                }
    
    
                var option_tutto =""; 
                option_tutto = "<option class='option_resi_localita' id='select_tutto_localita' >--Tutto--</option>"; 
    
                $("#city").append(option_tutto);  // aggiungo una option
    
                //APPENDO ALLA SELECT LA OPTION CREATA 
                $("#city").append(output_localita); 
    
        

                //citta = "" ;  
    
                //COSTRUISCO LA SELECT DEI NEGOZI 
                //FUNZIONE AJAX 
                $.ajax({ 
    
                    url:"../indagini/get_modulo_resi_negozio.php",
                    type:"POST",
                    dataType:"json",
                    data:{
    
                        library : valore_selezionato_library, 
                        startdate : data_da,
                        enddate : data_a, 
                        city: "",
                        clsValue: "",
                        headarea: capoarea, 
                        shop: "",  
                        tipologia:"",
                        tp:""
                    }
        
                }).done(function(res){
                    
                    console.log(res); 
    
                    $("#ajaxloader_minus").css("display" , "none"); 
    
    
                    //CONTROLLO SE LA CLASSE ESISTE 
                    if($("#shop_negozi").length){
    
                        //console.log("entra qui ed esiste la classe"); 
    
                        //CANCELLO LA SUMO SELECT E TUTTO IL SUO CONTENUTO
                        //NON LA RICREO PERCHÈ ME LA RICREA IN AUTOMATICO IL PLUGIN 
                        $("#shop_negozi").remove();
                        $(".sumo_shops").remove(); 
                        $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
                        
                        //SVUOTO LA VARIABILE GLOBALE CHE CONTIENE I NEGOZI 
                        MySelects_values = "" ; 
    
    
                    }
                    
                    //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DEI NEGOZI
                    var option_select_negozi ="";
                    for(let valore in res["negozi"] ){
    
                        var negozio = res["negozi"][valore]; 
                            //console.log(negozio);  
        
                        //var nome_negozio = res[valore]["NOME_NEGOZIO"]; 
                        //var codice_negozio = res[valore]["CODNEG"]; 
                            
                        option_select_negozi = option_select_negozi + "<option  class='negozio_select_multiple' value="+valore+">"+negozio+"</option>"; 
                    }
    
             
                    //console.log(option_select_header); 
    
                    $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
                
                    //APPENDO ALLA SELECT LE OPTION CREATE
                    $("#shop_negozi").append(option_select_negozi).show();
    
                    //INSERISCO UNA SELECT CON SCIRTTO TUTTO 
                    //AGIUNGO L'ATTRIBUTO MULTIPLE ALLA SELECT SHOP
                    // <select id='shop' multiple='multiple' name='shops' class='med'>  
                    //</select> 
                    $("#shop_negozi").attr("multiple","multiple"); 
    
     
                    //CHIAMO IL PLUGIN PER ABILITARE LA SUMO SELECT
                    $("#shop_negozi").SumoSelect({
    
                        selectAll: true, 
                    
                        csvDispCount: 3,
    
                        search : true    // se voglio cercare uno specifico 
    
                    });
    
                    $('.negozio_select_multiple')[0].sumo.selectAll(); //faccio partire tutti i negozi 
    
                    //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
                    $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 
    
                        MySelects_values = $("#shop_negozi").val(); /* prendo i valori */
                        
                    });
    
    
                    //PRENDO I VALORI SELEZIONATI DALLA SELECT NEGOZIO 
                    $("#shop_negozi").change(function(){ 
    
                        console.log("hai cliccato qui dentro la select dei negozi ");
    
                        //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
                        $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 
                            
                            MySelects_values = $("#shop_negozi").val(); /* prendo il valore del MySelects_values */
                            //alert("hai selezionato "+$(this).val()) ; //alert 
                        });
    
                        //LEGGO I VALORI  
                        console.log("i valori nell'array sono: ") //console.log a video 
                        for(let valore in MySelects_values){
                            console.log(MySelects_values[valore]); 
                        }
    
                    });
    
                }); // chiuso Ajax
                
    
            }); 
    
    
    
        });     

    //} //fine function get_capo_area_plus_city_plus_negozi
 

    //FUNZIONE AJAX 
    //COSTRUISCO LA SELECT DEI NEGOZI  
    $("#city").change(function(){


        $("#ajaxloader_minus").css("display" , "block");

        citta = $(this).find(":selected").text(); //prendo la citta
        //console.log(citta);  //visualizzo la citta

 
        if(citta =="--Tutto--"){
            citta = ""; 
        }

        get_negozi_change(); //richiamo qui i negozi





 
    }); // chiuso change_city 


    function get_negozi_change(){
        $.ajax({

            url:"../indagini/get_modulo_resi_negozio.php",
            type:"POST",
            dataType:"json",
            data:{

                library : valore_selezionato_library, 
                startdate : data_da,
                enddate : data_a, 
                city: citta ,
                clsValue: "",
                headarea: capoarea, 
                shop: "",  
                tipologia:"",
                tp:""
            }
 
        }).done(function(res){
            
            console.log(res); 

            $("#ajaxloader_minus").css("display" , "none"); 


            //CONTROLLO SE LA CLASSE ESISTE 
            if($("#shop_negozi").length){

                //console.log("entra qui.........");

                //console.log("entra qui ed esiste la classe"); 

                //CANCELLO LA SUMO SELECT E TUTTO IL SUO CONTENUTO
                //NON LA RICREO PERCHÈ ME LA RICREA IN AUTOMATICO IL PLUGIN 
                $("#shop_negozi").remove();
                $(".sumo_shops").remove(); 
                $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
                
                //SVUOTO LA VARIABILE GLOBALE CHE CONTIENE I NEGOZI 
                MySelects_values = "" ; 


            }
             
            //COSTRUISCO LA SELECT DINAMICA CON I DATI RICEVUTI DAL SERVER DEI NEGOZI
            var option_select_negozi ="";
            for(let valore in res["negozi"] ){

                var negozio = res["negozi"][valore]; 
                //console.log(negozio);  

               //var nome_negozio = res[valore]["NOME_NEGOZIO"]; 
               //var codice_negozio = res[valore]["CODNEG"]; 
                
               option_select_negozi = option_select_negozi + "<option  class='negozio_select_multiple' value="+valore+">"+negozio+"</option>"; 
            }



       
            //console.log(option_select_header); 

            $("#shopspan").append("<select  id='shop_negozi' name='shops' class='negozio_select_multiple' hidden></select>"); //APPENDO IL CONTENUTO AL DIV SHOPSPAN 
           
            //APPENDO ALLA SELECT LE OPTION CREATE
            $("#shop_negozi").append(option_select_negozi).show();

            //INSERISCO UNA SELECT CON SCIRTTO TUTTO 
            //AGIUNGO L'ATTRIBUTO MULTIPLE ALLA SELECT SHOP
            // <select id='shop' multiple='multiple' name='shops' class='med'>  
            //</select> 
            $("#shop_negozi").attr("multiple","multiple"); 
 

            //CHIAMO IL PLUGIN PER ABILITARE LA SUMO SELECT
            $("#shop_negozi").SumoSelect({

                selectAll: true, 
            
                csvDispCount: 3,

                search : true    // se voglio cercare uno specifico 

            });

            $('.negozio_select_multiple')[0].sumo.selectAll(); // seleziono  tutti i negozi 

            //disabilto FALLATI - CENTERGROSS ECOMM
            /*
            var element = $("label:contains('FALLATI - CENTERGROSS ECOMM')");  


            if (element !=""){

                console.log("entra qui "); 

                
                $('.negozio_select_multiple')[0].sumo.disableItem(61);
            }
            */

            //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
            $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 

                MySelects_values = $("#shop_negozi").val(); /* prendo i valori */
               
            });
            
        
            //PRENDO I VALORI SELEZIONATI DALLA SELECT NEGOZIO 
            $("#shop_negozi").change(function(){ 

                console.log("hai cliccato qui dentro la select dei negozi ");

                //VERIFICO SE CI SONO NEGOZI SELEZIONATI(IL PRIMO È SEMPRE SELEZIONATO DI DEFAULT) 
                $('.negozio_select_multiple option:selected').each(function(){ //PER OGNI ELEMENTO SELEZIONATO 
                    
                    MySelects_values = $("#shop_negozi").val(); /* prendo il valore del MySelects_values */
                    //alert("hai selezionato "+$(this).val()) ; //alert 
    
                });

                //LEGGO I VALORI 
                console.log("i valori nell'array sono: ") //console.log a video 
                for(let valore in MySelects_values){
                    console.log(MySelects_values[valore]); 
                }

            });

        }); 

    } //chiuso function negozi 
 

   //TO DO ACTION CLICK BOTTONE CERCA RESI 
   $("#gobutton").click(function(){ 

       
        var check_libreria = $("#library").find(":selected").text(); 

        
        //prendo la citta
        var citta_tutto = $("#city").find(":selected").text();


        if(citta_tutto == "--Tutto--"){
            citta = ""; 
        }

        //prendo il capo area 
        var capo_area_tutto = $("#headarea").find(":selected").text(); 
    
    
        if(capo_area_tutto == "--Tutto--" ){

            capoarea = ""; 
        }
 
        

        //controllo se è stata selezionata la libreria
        if( check_libreria === "Scegli la Libreria" ){
            swal("Attenzione!", "Attenzione selezionare la libreria", "warning"); 
        }else{


            //GIF DI CARICAMENTO
            $(".gif_caricamento").css("display", "block"); 


            //PRENDO LE DATE 
            var data_da = $("#datepicker_data_da").val(); 
            console.log(data_da);
            var data_a = $("#datepicker_data_a").val();
            console.log(data_a);

            //CONTROLLO SE LA TABELLA DEI RESI RAGGRUPPATI E' STATA CREATA E LA TABELLA DEI SINGOLI E' STATA CREATA 
            //CANCELLO LA TABELLA DEI RESI [FILTRATA] E LA TABELLA DEI RESI SINGOLI[FILTRATA]
            if($("#table_date_resi_singoli").length && $("#table_date_resi_raggruppati").length){ //CONTROLLO SE ESITE LA CLASSE 

                $("#table_date_resi_singoli").remove(); 
                $("#table_date_resi_raggruppati").remove(); 
                $("#title_resi").hide();

            } 

            //CONTROLLO SE LA TABELLA DEI RESI RAGGRUPPATI E' STATA CREATA E LA TABELLA DEI SINGOLI E' STATA CREATA 
            //CANCELLO LA TABELLA [ALL] DEI RESI E LA TABELLA [ALL] DEI RESI SINGOLI
            if($("#table_date_all_resi_raggruppati").length && $("#table_date_all_resi_singoli").length  && $("#table_date_resi_singoli_spec").length) { //CONTROLLO SE ESITE LA CLASSE 

                $("#table_date_all_resi_raggruppati").remove(); 
                $("#table_date_all_resi_singoli").remove(); 
                $("#table_date_resi_singoli_spec").remove(); 
                $("#tot_scontrini_raggruppati_all").remove();
                $("#title_resi").hide();

            }
            
            //CANCELLO SE PRIMA E' AVVENUTA UN ESTAZIONE GLOBALE
            if($(".table_date_resi_raggruppati").length || $("#table_date_resi_raggruppati")){

                $(".table_date_resi_raggruppati").remove();
                $("#table_date_resi_raggruppati").remove();  
            }



            //CONTROLLO SE È GIA STATA CREATE IN PRECEDENZA UNA TABELLA 
            if($("#table_date_resi_raggruppati_filter").length){
                $("#table_date_resi_raggruppati_filter").remove(); 
            }

                

           

            console.log("il capoarea è :"+capoarea);
            console.log("la citta  è"+citta);  
 
            //CASO 1 
            if(capoarea == "" && citta == "") {
 
                // console.log("entra qui"); 

                /*
                if(data_da.valueOf() > data_a.valueOf()){ 
                    swal("Attenzione!", "hai selezionato la data di partenza maggiore di quella futura", "warning"); 
                    console.log("data di partenza: "+ data_da.valueOf());  
                    console.log("data a :" +data_a.valu eOf());  
                } 
                */
                
                $.ajax({

                    url:"../indagini/get_global_data_resi.php",  
                    type:"POST",
                    dataType:"json",
                    data:{
                        libreria : valore_selezionato_library ,/* gli passo la libreria*/ 
                        data_da : data_da ,
                        data_a : data_a ,
                        codice_magazzino_singolo_all : "null" ,
                        numero_progressivo_vendita : "null" , 
                        negozi : MySelects_values 
                    
                    }

                }).done(function(res){

                    console.log(res); 

                    if(res.negozi_all_raggruppati.length == 0 ){

                        console.log("entra qui non ci sono resi ");

                        no_data_negozi_all_raggruppati = true ; 


                    }




                    $(".gif_caricamento").css("display", "none"); 


                    


                    //CREO LA TABELLA DEI RESI RAGGRUPPATI E DEI RESI SINGOLI 
                    $("#body_output").append(
                        "<table class='table_date_resi_raggruppati'  id='table_date_all_resi_raggruppati' style='width:1200px; height:110px;' hidden >"+
                            "<div class='title_resi' id='title_resi' hidden>"+ 
                            "<h1>Dati Resi</h1>"+  
                            "</div>"+ 
                            "<tr>"+ 
                                "<th style='color: white;'>NEGOZIO</th>"+
                                "<th style='color: rgb(233, 225, 225);'>MEDIA GIORNI RESI SCONTRINO</th>"+
                                "<th style='color:white;'>NUMERO SCONTRINI CONTENENTE CAMBI</th>"+
                            "</tr>"+
                        "</table>"+
                        "<table class='table_date_resi_singoli' id='table_date_all_resi_singoli' hidden >"+
                            "<tr>"+
                                "<th style='color: white;'>PROGRESSIVO VENDITA</th>"+ 
                                "<th style='color: white;'>DATA_SCONTRINO</th>"+  
                                "<th style='color: white;'>DATA_VENDITA_ORIGINARIA</th>"+ 
                                "<th style='color: white;'>TOTALE SCONTRINO</th>"+
                                "<th style='color: white;'>GIORNI RESI SCONTRINO</th>"+ 
                            "</tr>"+
                        "</table>"     
                    ); 
     

                    somma_giorni_resi_per_negozio = 0;  //parte da zero 
                    somma_scontrini_rilasciati_per_negozio = 0; //parte da zero

                    for(let raggruppati in res.negozi_all_raggruppati){  

                       

                       
                    
                        // GET THE DATA FROM THE SERVER 
                        var codice = res.negozi_all_raggruppati[raggruppati]["CODICE_MAGAZZINO"];  // il codice del magazzino
                        var media = res.negozi_all_raggruppati[raggruppati]["MEDIA_RESI_SCONTRINO"];
                        somma_giorni_resi_per_negozio = somma_giorni_resi_per_negozio +  Number(res.negozi_all_raggruppati [raggruppati]["SOMMA_GIORNI_RESI_NEGOZIO"]); 
                        
                        var numero_giorni_scontrini = res.negozi_all_raggruppati[raggruppati]["NUMERO_SCONTRINI_RILASCIATI"]; 
                        somma_scontrini_rilasciati_per_negozio =  somma_scontrini_rilasciati_per_negozio + Number(res.negozi_all_raggruppati[raggruppati]["NUMERO_SCONTRINI_RILASCIATI"]);
                        
                        //var media_row = (Math.round( media * 100 )/100 ).toString();
                        //var media_row_clean = parseFloat(media).toPrecision(3);
                        var media_row_clean = Math.round(parseFloat(media));
                        var nome_negozio = res.negozi_all_raggruppati[raggruppati]["NOME_NEGOZIO"];  
                        console.log(nome_negozio); // stampo il nome del negozio 
                        console.log(codice);   //stampo il codice del negozio 
                        console.log("la somma dei giorni resi per ogni negozio è " +somma_giorni_resi_per_negozio); 
                        //console.log(media);
         
                        $("#table_date_all_resi_raggruppati").append( 
                            "<tr>"+
                                "<td>"+nome_negozio+" <div style='padding-left:235px; margin-top:-13px;'><i  data-codice="+codice+"  data-nome-negozio="+nome_negozio+"  id="+codice+"  name='icon' class='fas fa-plus-square singoli_all'></i></td></div>"+  
                                "<td>"+media_row_clean+"</td>"+ 
                                "<td>"+numero_giorni_scontrini+"</td>"+ 
                            "</tr>"
                        ); 
    
                        
                    }// CHIUSO CICLO FOR  

 
                    
                    var media_tot_rows = somma_giorni_resi_per_negozio / somma_scontrini_rilasciati_per_negozio   ; 
                    media_tot_fixxata =  parseFloat(media_tot_rows).toPrecision(3);

                    $("#table_date_all_resi_raggruppati").last().append("<tr id='tot_scontrini_raggruppati_all'>" 
                    +"<td class='tot_scontrini_raggruppati_all'></td>"+
                    "<td class='tot_scontrini_raggruppati_all'> MEDIA TOTALE:"  +media_tot_fixxata+"</td>"+
                    "<td class='tot_scontrini_raggruppati_all'>SOMMA SCONTRINI:"  +somma_scontrini_rilasciati_per_negozio+"</td>"+
                    "</tr>"); 
                     
                        
                        

                    //FACCIO VEDERE LA TABELLA
                    if(no_data_negozi_all_raggruppati){ 


                        $(".tot_scontrini_raggruppati_all").remove(); //cancello eventuali scritte 

                        $("#title_resi").show();
                        $("#table_date_all_resi_raggruppati").append("<tr><td colspan='3' class='no_data_resi'>Spiacente per queste date non ci sono Resi</td></tr>");
                        $("#table_date_all_resi_raggruppati").show(); 
                       
                        console.log("entra qui non ci sono resi "); 

                        no_data_negozi_all_raggruppati =  false; //resetto la variabile globale a false  


  
                    }else{

                        $("#table_date_all_resi_raggruppati").show(); 
                        $("#title_resi").show();

                    }
                    
                    
                    


                    //CLICK 
                    $(".singoli_all").click(function(){
                        


                        //cancello la tabella 
                        $(".table_date_resi_singoli").remove();

                        $("#body_output").append(
                        "<table class='table_date_resi_singoli' id='table_date_all_resi_singoli' hidden >"+
                            "<tr>"+
                                "<th style='color: white;'>PROGRESSIVO VENDITA</th>"+ 
                                "<th style='color: white;'>DATA_SCONTRINO</th>"+  
                                "<th style='color: white;'>DATA_VENDITA_ORIGINARIA</th>"+
                                "<th style='color: white;'>TOTALE SCONTRINO</th>"+
                                "<th style='color: white;'>GIORNI RESI SCONTRINO</th>"+ 
                            "</tr>"+
                        "</table>"); 

                        var nome_negozio_singolo  = $(this).data("nome-negozio"); 

                        console.log("il nome del negozio  è :"+nome_negozio_singolo);
                    
  

                        codice_magazzino = $(this).data("codice");

                        console.log("il codice magazzino  è :"+codice_magazzino);



                        $.ajax({

                            url:"../indagini/get_global_data_resi.php",  
                            type:"POST",
                            dataType:"json",
                            data:{
                                libreria : valore_selezionato_library ,/* gli passo la libreria*/ 
                                data_da : data_da ,
                                data_a : data_a , 
                                codice_magazzino_singolo_all : codice_magazzino, 
                                numero_progressivo_vendita : "null"  , 
                                negozi : "null" 
                                 
                            } 
        
                        }).done(function(res){

            
                            console.log("entra in singoli");
                            
    
                            var totale_giorni_resi = 0;  //partono da zero 
                            var scontrini_emessi = 0;  // parte da zero 
                            
                            //TABELLA SINGOLI ALL 
                            for(let singoli_all in res.negozi_all_singoli_raggruppati){
                                
                                var codice_magazzino_take = res.negozi_all_singoli_raggruppati[singoli_all]["CODICE_MAGAZZINO"]; 
    
                                if(codice_magazzino_take == codice_magazzino){
                                    
                                    //console.log("entra qui..............");
    
                                    nome_negozio_singolo  = res.negozi_all_singoli_raggruppati[singoli_all]["NOME_NEGOZIO"]; 
                                    var data_scontrino = res.negozi_all_singoli_raggruppati[singoli_all]["DATA_SCONTRINO"];  
                                    var data_vendita_originaria = res.negozi_all_singoli_raggruppati[singoli_all]["DATA_VENDITA_ORIGINE"];
                                    console.log("la data di origine è : " + data_vendita_originaria); 
                                    var numero_progressivo_vendita  = res.negozi_all_singoli_raggruppati[singoli_all]["NUMERO_PROGRESSIVO_VENDITA"]; 
                                    var totale_scontrino =  res.negozi_all_singoli_raggruppati[singoli_all]["TOTALE_SCONTRINO"]; 
                                    var divisa = res.negozi_all_singoli_raggruppati[singoli_all]["DIVISA"]; 
                                    var giorni_scontrino = res.negozi_all_singoli_raggruppati[singoli_all]["GIORNI_TRASCORSI_RESO"]; 
                                    


                                    var data_giorni = data_scontrino.substring(6,8);
                                    var data_anni = data_scontrino.substring(0,4); 
                                    var data_mesi = data_scontrino.substring(4,6);

                                    //data vendita origine 
                                    var data_origine_giorni = data_vendita_originaria.substring(6,8); 
                                    var data_origine_mesi = data_vendita_originaria.substring(4,6);
                                    var data_origine_anni = data_vendita_originaria.substring(0,4); 

                                    //incremento variabile 
                                    totale_giorni_resi =  totale_giorni_resi + Number(giorni_scontrino); 

                                    //incremneto variabile
 
                                    scontrini_emessi ++; 
    
                                    //APPENDO IL CONTENUTO ALLA TABELLA
                                    $("#table_date_all_resi_singoli").append(    
    
                                        "<tr class='riga_all_resi_singoli'>"+ 
                                            "<td id='prog_vendita'>"+numero_progressivo_vendita+"<i  data-progressivo-vendita_plus_all_="+numero_progressivo_vendita+" id='progress_plus_all_"+numero_progressivo_vendita+"'  name='icon' class='fas fa-plus-square singoli_specifica '> </a></i>         <i  data-progressivo-vendita_minus_all_="+numero_progressivo_vendita+" id='progress_minus_all_"+numero_progressivo_vendita+"'  name='icon' class='fas fa-minus-square singoli_specifica_minus' style='display:none' ></i></td>"+ 
                                            "<td>"+data_giorni+"-"+data_mesi+"-"+data_anni+"</td>"+
                                            "<td>"+data_origine_giorni+"-"+data_origine_mesi+"-"+data_origine_anni+"</td>"+
                                            "<td>"+totale_scontrino+" "+divisa+"</td>"+
                                            "<td>"+giorni_scontrino+"</td>"+
                                        "</tr>"+
                                        "<tr id='id_prog_"+numero_progressivo_vendita+"'  class='spec_riga_all_resi_singoli'  style='display:none'> </tr>"
                                    ); 
                                     
                                }


                                $("#table_date_all_resi_singoli").show(); 
    
                                $("#table_date_all_resi_singoli").dialog({
                                
    
                                    autoOpen: false,     
                                    
                                    title: "DATI RESI SINGOLI  -----  "+nome_negozio_singolo,
    
                                    position: {
                                            my: 'center',
                                            at: 'center',
                                            of: window
                                    },
    
                                });
    
                                $("#table_date_all_resi_singoli").dialog( "open" );
                                
                                  
    
                            }// chiuso for

                            var media_totale_negozio_specifico = totale_giorni_resi / scontrini_emessi; 

                            var media_totale_negozio_specifico_fixxata = parseFloat(media_totale_negozio_specifico).toPrecision(3);
                            

 

                            $("#table_date_all_resi_singoli").last().append("<tr id='tot_scontrini_spec_all'>"+ 
                           "<td>MEDIA TOTALE NEGOZIO: "+media_totale_negozio_specifico_fixxata+"</td>"+
                            "<td></td>"+
                            "<td></td>"+
                            "<td></td>"+ 
                            "<td>TOTALE GIORNI RESI:"+totale_giorni_resi+"</td>"+
                            "</tr>"); 

                            //TO DO CLICK SPECIFICO SKU SE È CLICCATO IL PLUS(vedo le caratteristiche del prodotto)  
                            $(".singoli_specifica").click(function(){


                            
                                prog_vend = $(this).data("progressivo-vendita_plus_all_");

                                //visualizzo la riga 
                                $("#id_prog_"+prog_vend).show();
                               
                                console.log(prog_vend);


                                //icone
                                $("#progress_plus_all_"+prog_vend).css("display" , "none");
                                $("#progress_minus_all_"+prog_vend).css("display", "inline-block"); 

                                //cancello la riga precedente creata 
                                $("#row_container_id_prog_"+prog_vend).remove();  

 
                               

                                $.ajax({

                                    url: "../indagini/get_global_data_resi.php",
                                    type: "POST",
                                    dataType:"json",
                                    data:{

                                        libreria : valore_selezionato_library ,/* gli passo la libreria*/ 
                                        data_da : data_da ,
                                        data_a : data_a , 
                                        codice_magazzino_singolo_all : codice_magazzino,
                                        numero_progressivo_vendita : prog_vend ,
                                        negozi: "null"  
                                        
                                    } 
                                }).done(function(res){

                                    //console.log("arriva qui..."); 

                                    $("#id_prog_"+prog_vend).append(
                                        "<td class='riga_row_in_table_spec'  id='row_container_id_prog_"+prog_vend+"' colspan='5'>"+
                                            "<table class='result_prodotti_specifica' id='result_prodotti_specifica_"+prog_vend+"'>"+
                                                "<tr>"+
                                                //"<th>COD_FORNITORE</th>"+//
                                                "<th>SKU</th>"+
                                                "<th>PROGRESSIVO VENDITA</th>"+
                                                "<th>PREZZO_LISTINO</th>"+
                                                "<th>PREZZO_NETTO</th>"+
                                                //"<th>COD_MAGAZZINO</th>"+
                                                //"<th>DATA_SCONTRINO</th>"+
                                                //"<th>COD_ARTICOLO</th>"+
                                                "<th>TIPOLOGIA_ARTICOLO</th>"+
                                                "<th>VENDITA</th>"+
                                                "</tr>"+
                                            "</table>"+
                                        "</td>");
  
 
                                    console.log(res); // stampo i risultati 

                                    console.log(prog_vend);

                        

                                    for(let progressivo in res.negozi_all_specifica_progress_vendita){

                                        var progressivo_vendita_take = res.negozi_all_specifica_progress_vendita[progressivo]["NUMERO_PROGRESSIVO_VENDITA"] ;
                                        
                                        console.log(progressivo_vendita_take);
                                        

                                        // sei l progressivo è uguale 
                                        if(prog_vend == progressivo_vendita_take){

                                            //console.log("entra qui progress specifica ......");
 

                                            var codice_articolo_fornitore = res.negozi_all_specifica_progress_vendita[progressivo]["CODICE_ARTICOLO_FORNITORE"];
                                            console.log(codice_articolo_fornitore);

                                            var sku = res.negozi_all_specifica_progress_vendita[progressivo]["SKU"] ;
                                            console.log(sku);

                                            var progressivo_vendita =  res.negozi_all_specifica_progress_vendita[progressivo]["NUMERO_PROGRESSIVO_VENDITA"] ;

                                            var prezzo_listino = res.negozi_all_specifica_progress_vendita[progressivo]["PREZZO_LISTINO"] ;

                                            var prezzo_netto = res.negozi_all_specifica_progress_vendita[progressivo]["PREZZO_NETTO"] ;

                                            var codice_magazzino  = res.negozi_all_specifica_progress_vendita[progressivo]["CODICE_MAGAZZINO"] ;

                                            var data_scontrino = res.negozi_all_specifica_progress_vendita[progressivo]["DATA_SCONTRINO"] ;
                                            var data_giorni = data_scontrino.substring(6,8);
                                            var data_anni = data_scontrino.substring(0,4);
                                            var data_mesi = data_scontrino.substring(4,6);
                                            var tipologia_articolo = res.negozi_all_specifica_progress_vendita[progressivo]["COD_ARTICOLO"] ;
                                            var descrizione_prodotto =  res.negozi_all_specifica_progress_vendita[progressivo]["TIPOLOGIA_ARTICOLO"] ;
                                            var vendita =  res.negozi_all_specifica_progress_vendita[progressivo]["VENDITA"] ;
                                            var divisa =  res.negozi_all_specifica_progress_vendita[progressivo]["DIVISA"] ;
                                             

                                            if(vendita == "R"){

                                                prezzo_listino = "-"+prezzo_listino; 

                                                prezzo_netto = "-"+prezzo_netto;
                                                
                                                
                                            }
                                  
                                            
                                            //var riga_scontrino =  res.negozi_all_specifica_progress_vendita[progressivo]["NUMERO_RIGA_SCONTRINO"] ;                                        
                                            //var nome_negozio = res.negozi_all_specifica_progress_vendita[progressivo]["NOME_NEGOZIO"] ;
                                            //var data_scontrino = res.negozi_all_specifica_progress_vendita[progressivo]["DATA_SCONTRINO"] ;
                                            //console.log(data_scontrino);
                                           
                                     

                                            
 
                                            $("#result_prodotti_specifica_"+prog_vend).append( 
                                                        "<tr>"+                                              
                                                            //"<td>"+codice_articolo_fornitore+"</td>"+
                                                            "<td>"+sku+"</td>"+
                                                            "<td>"+progressivo_vendita+"</td>"+
                                                     
                                                  
                                                            "<td>"+prezzo_listino+" "+divisa+"</td>"+
                                                            "<td>"+prezzo_netto+" "+divisa+"</td>"+
                                                            //"<td>"+codice_magazzino+"</td>"+
                                                            //"<td>"+data_giorni+"-"+data_mesi+"-"+data_anni+"</td>"+
                                                            //"<td>"+tipologia_articolo+"</td>"+
                                                            "<td>"+descrizione_prodotto+"</td>"+
                                                            "<td>"+vendita+"</td>"+
                                                        "</tr>"          
                                            ); 
                                        
                                        }
                                        

                                            
                                    }// chiuso for       
                                        

                                }); //chiuso done 

                            });

                            $(".singoli_specifica_minus").click(function(){

                                //nascondere la riga 

                                prog_vend = $(this).data("progressivo-vendita_minus_all_");

                                $("#id_prog_"+prog_vend).hide();
                               
                                console.log(prog_vend);


                                var codice_progressivo_vendita = $(this).data('progressivo-vendita_minus_all_'); 
                                $("#progress_plus_all_"+codice_progressivo_vendita).css("display" , "inline-block");
                                $("#progress_minus_all_"+codice_progressivo_vendita).css("display", "none"); 

                                console.log("codice_progressivo_vendita"+codice_progressivo_vendita);


                            });

                        });    

                    }); // chiuso singoli all_click             

                });

            }else{  //SE L'UTENTE HA SELEZIONATO LE DATE PIU' GLI ALTRI FILTRI(NEGOZI,LOCALITA',CAPOAREA ) -> CASO DUE 
                

                //vedo che citta ho selezionato
                var citta = $("#city").find(":selected").text(); 
 
                if(citta == "--Tutto--"){

                    citta = "all_city";
          
                }
                /*

                if(data_da.valueOf() > data_a.valueOf()){ 

                    swal("Attenzione!", "hai selezionato la data di partenza maggiore di quella futura", "warning"); 
                    console.log("data di partenza: "+ data_da.valueOf());  
                    console.log("data a :" +data_a.valueOf());  
                }
            
                */
                console.log(valore_selezionato_library);
                console.log(MySelects_values); 

                $.ajax({           
                    url:"../indagini/get_all_data_resi.php",
                    type:"POST",
                    dataType:"json", 
                    data:{
                        
                        libreria: valore_selezionato_library, 
                        data_da: data_da, /*gli passo la data*/
                        data_a: data_a,   /*gli passo la data_a */ 
                        negozi: MySelects_values, 
                        localita : citta , 
                        capoarea : capoarea ,
                        numero_progressivo_vendita :"null" ,
                        codice_magazzino_singolo_filter : "null"
   

                    }

                }).done(function(res){   /* mi ritorna il la risposta del server */ 


                    if(res.negozi_raggruppati.length == 0){

                        console.log("entra qui non ci sono resi ");

                        no_data_negozi_raggruppati = true; 
                
                    }

                   
                   
                   
                    $(".gif_caricamento").css("display", "none"); 


                    console.log("cancella tabella"); 

                    //CONTROLLO SE È GIA STATA CREATE IN PRECEDENZA UNA TABELLA 
                    if($("#table_date_resi_raggruppati_filter").length){
                        $("#table_date_resi_raggruppati_filter").remove(); 

                    }

                    //CONTROLLO SE E' STATA CREATA PRECEDENTEMENTE UNA TABELLA CREATA 
                    if($("#table_da.lee_resi_singoli").length){

                        $("#table_date_resi_singoli").remove();
                        $(".tot_scontrini_raggruppati_all_filter").remove(); 
                    } 

                    //console.log(res.negozi_raggruppati);  //SCENDO FINO AL LIVELLO DEI NEGOZI RAGGRUPPATI 

                    $(".gif_caricamento").css("display", "none"); 

                    //CREO LA TABELLA DEI RESI RAGGRUPPATI E DEI RESI SINGOLI 
                    $("#body_output").append(
                        "<table class='table_date_resi_raggruppati_filter'  id='table_date_resi_raggruppati_filter' style='width:1200px; height:110px;' hidden >"+
                            "<div class='title_resi' id='title_resi' hidden>"+ 
                            "<h1>Dati Resi</h1>"+  
                            "</div>"+ 
                            "<tr>"+
                                "<th style='color: white;'>NEGOZIO</th>"+
                                "<th style='color: rgb(233, 225, 225);'>MEDIA GIORNI RESI SCONTRINO</th>"+
                                "<th style='color:white;'>NUMERO SCONTRINI RILASCIATI</th>"+
                            "</tr>"+
                        "</table>"+
                        "<table class='table_date_resi_singoli' id='table_date_resi_singoli' hidden >"+
                            "<tr>"+
                                "<th style='color: white;'>PROGRESSIVO VENDITA</th>"+
                                "<th style='color: white;'>DATA_SCONTRINO</th>"+  
                                "<th style='color: white;'>DATA_VENDITA_ORIGINARIA</th>"+  
                                "<th style='color: white;'>TOTALE SCONTRINO</th>"+
                                "<th style='color: white;'>GIORNI RESI SCONTRINO</th>"+
                            "</tr>"+
                        "</table>"
                    ); 
 

                    var somma_giorni_resi_per_negozio = 0;  //parte da zero 
                    //var somma_scontrini_rilasciati_per_negozio = 0; //parte da zero
                    
                    
                    //var count_righe = 0;
                    //var media_tot = 0;  
                    var numero_scontrini_tot = 0; 
 
                    for(let raggruppati in res.negozi_raggruppati){ 


                       

                        // GET THE DATA FROM THE SERVER   
                        var codice = res.negozi_raggruppati[raggruppati]["CODICE_MAGAZZINO"];  // il codice del magazzino
                        var media = res.negozi_raggruppati[raggruppati]["MEDIA_RESI_SCONTRINO"];
                        somma_giorni_resi_per_negozio = somma_giorni_resi_per_negozio +  Number(res.negozi_raggruppati[raggruppati]["SOMMA_GIORNI_RESI_NEGOZIO"]); 

                        //var media_fixxata = (Math.round( media * 100 )/100 ).toString(); 
                        var media_row_clean = parseFloat(media).toPrecision(3);
                        var media_row_clean = Math.round(parseFloat(media));
                        //media_tot = media_tot + Number(media_row_clean); 
                        var nome_negozio = res.negozi_raggruppati[raggruppati]["NOME_NEGOZIO"];
                        var numero_giorni_scontrini = res.negozi_raggruppati[raggruppati]["NUMERO_SCONTRINI_RILASCIATI"]; 
                        numero_scontrini_tot = numero_scontrini_tot + Number(numero_giorni_scontrini) ;  
                        
                        
                        console.log(nome_negozio);
                        console.log(codice);   
                        //console.log(media);
        
                        $("#table_date_resi_raggruppati_filter").append( 
                            "<tr>"+
                                "<td>"+nome_negozio+"<div style='padding-left:235px; margin-top:-13px;'><i  data-codice="+codice+" id="+codice+"  name='icon' class='fas fa-plus-square filter_no_filter'></i></td></div>"+
                                "<td>"+media_row_clean +"</td>"+  
                                "<td>"+numero_giorni_scontrini+"</td>"+
                            "</tr>"
                        );
                        
                        //count_righe++; 
                        
                    }// CHIUSO CICLO FOR  
 
 
                    var media_tot_rows = somma_giorni_resi_per_negozio / numero_scontrini_tot  ; 
                    media_tot_fixxata =  parseFloat(media_tot_rows).toPrecision(3);

                    /*
                    var media_tot_rows = media_tot / count_righe ;
                    var media_tot_rows_fixxata = (Math.round( media_tot_rows * 100 )/100 ).toString();
                    var media_tot_rows_fixxata = parseFloat(media_tot_rows).toPrecision(3);
                    */

                    $("#table_date_resi_raggruppati_filter").last().append("<tr id='tot_scontrini_raggruppati_all_filter'>" 
                    +"<td class='tot_scontrini_raggruppati_all_filter'></td>"+
                    "<td class='tot_scontrini_raggruppati_all_filter'> MEDIA TOTALE:"+media_tot_fixxata+"</td>"+
                    "<td class='tot_scontrini_raggruppati_all_filter'>SOMMA SCONTRINI:"+numero_scontrini_tot+"</td>"+
                    "</tr>");

                 
                    //FACCIO VEDERE LA TABELLA
                    if(no_data_negozi_raggruppati){

                        $(".tot_scontrini_raggruppati_all_filter").remove(); 


                        $("#title_resi").show();   
                        $("#table_date_resi_raggruppati_filter").append("<tr><td colspan='3' class='no_data_resi'>Spiacente per queste date non ci sono Resi</td></tr>");
                        $("#table_date_resi_raggruppati_filter").show();

                        no_data_negozi_raggruppati = false; //resetto la variabile globale a false 
                        
                        console.log("entra qui non ci sono resi ");  
                       
  
                    }else{

                        $("#table_date_resi_raggruppati_filter").show(); 
                        $("#title_resi").show();    
                    }


                    $(".filter_no_filter").click(function(){ 

                        console.log("hai cliccato il piu' con filtri"); 


                       
                        //cancello la tabella 
                        $(".table_date_resi_singoli").remove();

                        //ricreo la tabella 
                        $(".table_date_resi_singoli").remove();

                        $("#body_output").append(
                        "<table class='table_date_resi_singoli' id='table_date_resi_singoli' hidden >"+
                            "<tr>"+
                                "<th style='color: white;'>PROGRESSIVO VENDITA</th>"+
                                "<th style='color: white;'>DATA_SCONTRINO</th>"+  
                                "<th style='color: white;'>DATA_VENDITA_ORIGINARIA</th>"+  
                                "<th style='color: white;'>TOTALE SCONTRINO</th>"+
                                "<th style='color: white;'>GIORNI RESI SCONTRINO</th>"+ 
                            "</tr>"+
                        "</table>"); 
                         
                        codice_magazzino_filtrato = $(this).data("codice");  //il codice del magazzino 
                        var tot_somma_giorni_resi = 0;
                        count_scontrini_emessi = 0;  // partono da zero 

                        //var codice_magazzino = $(this).attr("id"); 
                        console.log("hai selezionato il codice"+codice_magazzino_filtrato); 
                
                            //var totale_giorni_resi_scontrino = 0; 

                            for(let singoli in res.negozi_singoli ){

                                //console.log(codice_magazzino); 

                                    var codice_magazzino_singolo = res.negozi_singoli[singoli]["CODICE_MAGAZZINO"];
                                    if(codice_magazzino_filtrato == codice_magazzino_singolo){

                                        var numero_progressivo_vendita = res.negozi_singoli[singoli]["NUMERO_PROGRESSIVO_VENDITA"];
                                        var giorni_resi_scontrino  = res.negozi_singoli[singoli]["GIORNI_TRASCORSI_RESO"];
                                        nome_negozi_singoli_raggruppati = res.negozi_singoli[singoli]["NOME_NEGOZIO"]; 
                                        //var riga_scontrino = res.negozi_singoli[singoli]["NUMERO_RIGA_SCONTRINO"]; 
                                        var totale_scontrino = res.negozi_singoli[singoli]["TOTALE_SCONTRINO"]; 
                                        var divisa = res.negozi_singoli[singoli]["DIVISA"];
                                        var data_scontrino = res.negozi_singoli[singoli]["DATA_SCONTRINO"]; 
                                        var data_vendita_originaria = res.negozi_singoli[singoli]["DATA_VENDITA_ORIGINE"]; 
                                        console.log("la data di origine è : " + data_vendita_originaria); 
                                        var data_giorni = data_scontrino.substring(6,8);
                                        var data_anni = data_scontrino.substring(0,4); 
                                        var data_mesi = data_scontrino.substring(4,6); // data_data l'utente  la data dello scontrino
                                        //data originaria RESI
                                        var data_giorni_origine_giorni = data_vendita_originaria.substring(6,8);
                                        var data_giorni_origine_anni = data_vendita_originaria.substring(0,4); 
                                        var data_giorni_origine_mesi = data_vendita_originaria.substring(4,6); 
 



                                        tot_somma_giorni_resi = tot_somma_giorni_resi + Number(giorni_resi_scontrino) ; 

                                        //APPENDO IL CONTENUTO ALLA TABELLA
                                        $("#table_date_resi_singoli").append(  
 
                                        "<tr class='riga_resi_singoli'>"+ 
                                            "<td>"+numero_progressivo_vendita+"<i  data-codice-progressivo-vendita-filtri="+numero_progressivo_vendita+" id='progress_plus_"+numero_progressivo_vendita+"'  name='icon' class='fas fa-plus-square filter_spec_filtri'></i><i  data-progressivo-vendita_filtri_minus="+numero_progressivo_vendita+" id='progress_minus_"+numero_progressivo_vendita+"'  name='icon' class='fas fa-minus-square singoli_specifica_minus_filtri' style='display: none' ></i></td>"+ // numero giorni vendita scontrino 
                                            "<td>"+data_giorni+"-"+data_mesi+"-"+data_anni+"</td>"+
                                            "<td>"+data_giorni_origine_giorni+"-"+data_giorni_origine_mesi+"-"+data_giorni_origine_anni+"</td>"+  
                                            "<td>"+totale_scontrino+" "+divisa+"</td>"+
                                            "<td>"+giorni_resi_scontrino+"</td>"+
                                            "<tr id='id_prog_with_filtri_"+numero_progressivo_vendita+"'  class='spec_riga_all_resi_singoli'  style='display:none'> </tr>"+
                                            //"<td class='somma_scontrini' data-scontrini="+giorni_resi_scontrino+">"+giorni_resi_scontrino+"</td>"+
                                        "</tr>"  
                                        );
                                        
                                        count_scontrini_emessi = count_scontrini_emessi +1 ;  //incremento 
                                    }  
                            }// chiuso for

                            
                            var media_totale_negozio_specifico = tot_somma_giorni_resi / count_scontrini_emessi; 

                            var media_totale_negozio_specifico_fixxata = parseFloat(media_totale_negozio_specifico).toPrecision(3);
                            
 
                            //console.log(tot_somma_giorni_resi);

                            
                            $("#table_date_resi_singoli").last().append("<tr id='tot_scontrini_filtri'>"+ 
                            "<td>MEDIA TOTALE NEGOZIO: "+media_totale_negozio_specifico_fixxata+"</td>"+
                            "<td></td>"+
                            "<td></td>"+ 
                            "<td></td>"+
                            "<td>TOTALE GIORNI RESI:"+tot_somma_giorni_resi+"</td>"+
                            "</tr>"); 
 
                            

                            $("#table_date_resi_singoli").show(); 

                            
                    
                            $("#table_date_resi_singoli").dialog({

 
                                autoOpen: false, 


                                position: {
                                    my: 'center',
                                    at: 'center',
                                    of: window
                                },

                                width: 700 , 
            
                
                                title: "DATI RESI SINGOLI  -----  "+nome_negozi_singoli_raggruppati,
                                
                        
                
                            });
 


                            $("#table_date_resi_singoli").dialog( "open" );


                            //CLICK SKU DEL PRODOTTO 
                            $(".filter_spec_filtri").click(function(){

                              

                                console.log(" entra qui nel filter spec con filtri!.."); 

                                
                                var codice_progressivo_vendita = $(this).data('codice-progressivo-vendita-filtri'); 
                                $("#progress_plus_"+codice_progressivo_vendita).css("display" , "none");
                                $("#progress_minus_"+codice_progressivo_vendita).css("display", "inline-block"); 

                                console.log("codice_progressivo_vendita"+codice_progressivo_vendita);


                                //mostro la riga 
                                progressivo_vendita_global_filter  = $(this).data("codice-progressivo-vendita-filtri"); 
                                
                                console.log(" il progressivo vendita è :"+progressivo_vendita_global_filter); 

                                $("#id_prog_with_filtri_"+progressivo_vendita_global_filter).show();

                                
                                //CANCELLO LA RIGA PRECEDENTEMENTE CREATA
                                $("#row_container_id_prog_"+progressivo_vendita_global_filter).remove();
                                      
                                //console.log(prog_vend);


                                $.ajax({

                                    url:"../indagini/get_all_data_resi.php",
                                    type:"POST",
                                    dataType:"json", 
                                    data:{
                                        
                                        libreria: valore_selezionato_library, 
                                        data_da: data_da, /*gli passo la data*/
                                        data_a: data_a,   /*gli passo la data_a */  
                                        negozi: MySelects_values, 
                                        localita : citta , 
                                        capoarea : capoarea ,
                                        numero_progressivo_vendita : progressivo_vendita_global_filter ,
                                        codice_magazzino_singolo_filter : codice_magazzino_filtrato

                                         
                                    }

                                    
                                }).done(function(res){

                                


                                    $("#id_prog_with_filtri_"+progressivo_vendita_global_filter).append(
                                        "<td class='riga_row_in_table_spec_filter'  id='row_container_id_prog_"+progressivo_vendita_global_filter+"' colspan='5'>"+
                                            "<table class='result_prodotti_specifica' id='result_prodotti_specifica_filter_"+progressivo_vendita_global_filter+"'>"+
                                                "<tr>"+
                                                "<tr>"+
                                                //"<th>COD_FORNITORE</th>"+
                                                "<th>SKU</th>"+
                                                "<th>PROGRESSIVO VENDITA</th>"+
                                                "<th>PREZZO_LISTINO</th>"+
                                                "<th>PREZZO_NETTO</th>"+
                                                //"<th>COD_MAGAZZINO</th>"+
                                                //"<th>DATA_SCONTRINO</th>"+
                                                //"<th>COD_ARTICOLO</th>"+
                                                "<th>TIPOLOGIA_ARTICOLO</th>"+
                                                "<th>VENDITA</th>"+
                                                "</tr>"+
                                                "</tr>"+
                                            "</table>"+
                                        "</td>");
  


                                    for(let progressivo in res.negozi_all_specifica_progress_vendita){

                                        var progressivo_vendita_take = res.negozi_all_specifica_progress_vendita[progressivo]["NUMERO_PROGRESSIVO_VENDITA"] ;
                                        
                                        console.log(progressivo_vendita_take);
                                    

                                        // sei l progressivo è uguale 
                                        if(progressivo_vendita_global_filter == progressivo_vendita_take){
                                             
                                            var codice_articolo_fornitore = res.negozi_all_specifica_progress_vendita[progressivo]["CODICE_ARTICOLO_FORNITORE"];
                                            console.log(codice_articolo_fornitore);

                                            var sku = res.negozi_all_specifica_progress_vendita[progressivo]["SKU"] ;
                                            console.log(sku);

                                            var progressivo_vendita =  res.negozi_all_specifica_progress_vendita[progressivo]["NUMERO_PROGRESSIVO_VENDITA"] ;

                                            var prezzo_listino = res.negozi_all_specifica_progress_vendita[progressivo]["PREZZO_LISTINO"] ;

                                            var prezzo_netto = res.negozi_all_specifica_progress_vendita[progressivo]["PREZZO_NETTO"] ;

                                            var codice_magazzino  = res.negozi_all_specifica_progress_vendita[progressivo]["CODICE_MAGAZZINO"] ;

                                            var data_scontrino = res.negozi_all_specifica_progress_vendita[progressivo]["DATA_SCONTRINO"] ;
                                            var data_giorni = data_scontrino.substring(6,8);
                                            var data_anni = data_scontrino.substring(0,4);
                                            var data_mesi = data_scontrino.substring(4,6);
                                            var tipologia_articolo = res.negozi_all_specifica_progress_vendita[progressivo]["COD_ARTICOLO"] ;
                                            var descrizione_prodotto =  res.negozi_all_specifica_progress_vendita[progressivo]["TIPOLOGIA_ARTICOLO"] ;
                                            var vendita =  res.negozi_all_specifica_progress_vendita[progressivo]["VENDITA"] ;
                                            var divisa =  res.negozi_all_specifica_progress_vendita[progressivo]["DIVISA"] ;
                                            

                                            if(vendita == "R"){

                                                prezzo_listino = "-"+prezzo_listino;

                                                prezzo_netto = "-"+prezzo_netto;

                                            }




                                            $("#result_prodotti_specifica_filter_"+progressivo_vendita).append( 
                                                        "<tr>"+                                              
                                                            //"<td>"+codice_articolo_fornitore+"</td>"+
                                                            "<td>"+sku+"</td>"+
                                                            "<td>"+progressivo_vendita+"</td>"+
                                                            "<td>"+prezzo_listino+" "+divisa+"</td>"+
                                                            "<td>"+prezzo_netto+" "+divisa+"</td>"+
                                                            //"<td>"+codice_magazzino+"</td>"+
                                                            //"<td>"+data_giorni+"-"+data_mesi+"-"+data_anni+"</td>"+
                                                            //"<td>"+tipologia_articolo+"</td>"+
                                                            "<td>"+descrizione_prodotto+"</td>"+
                                                            "<td>"+vendita+"</td>"+
                                                        "</tr>"  
                                            ); 
                                         
                                        }
                                        

                                             
                                    }// chiuso for    
                                        

                                }); //chiuso done 

                            });

                            $(".singoli_specifica_minus_filtri").click(function(){

                            

                                prog_vend_filtri = $(this).data("progressivo-vendita_filtri_minus");

                                $("#id_prog_with_filtri_"+prog_vend_filtri).hide();
                               

                                console.log(prog_vend_filtri);



                                var codice_progressivo_vendita = $(this).data('progressivo-vendita_filtri_minus'); 
                                $("#progress_plus_"+codice_progressivo_vendita).css("display" , "inline-block");
                                $("#progress_minus_"+codice_progressivo_vendita).css("display", "none"); 

                                console.log("codice_progressivo_vendita"+codice_progressivo_vendita);

                            });                    
                            
                    }); // chiuso click raggruppati all 
                        
        
                });    
            }

        }//chiuso else    

    });    

});




