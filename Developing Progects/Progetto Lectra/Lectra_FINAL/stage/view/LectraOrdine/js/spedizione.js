/*spedizione js*/
$(document).ready(function () { // una volta che l' html è stato letto tutto
    $(".container-inserimento_spedizione").hide();//lo rendo invisibile
    //tasto indietro spedizione
    $("#indietro-spedizione").unbind();
    $("#indietro-spedizione").click(function () {
        $("#utente").show();
        $("#indietro-mail").hide();
        $(".container-inserimento_spedizione" ).hide();
        $("#indietro-create-account" ).show();
        $(".nuovo_cliente" ).hide();
        if($("#clienti_esistente_no").is(":checked")) { // se il cliente ha cliccato che non è esistente
            $(".container-inserimento_spedizione").hide(); //lo rendo invisibile
            $("#indietro-create-account").show(); // lo rendo visibile 
            $(".container-creazione_mail").show();
            $(".nuovo_cliente").show();
            //remove active from Progress Bar 
            var list = document.querySelectorAll("li");
            for(var i = 0; i<list.length ; i++){
                if(list[i].textContent == 'CRM' || list[i].textContent == 'ORDINE' || list[i].textContent == 'SPEDIZIONE'){
                    $("#crm").addClass('active');
                    $("#ordine").addClass('active');
                    $("#spedizione").removeClass('active');
                }
            }
        }else if($("#clienti_esistente_si").is(":checked")){ // se il cliente ha cliccato che già esiste
            $(".container-inserimento_spedizione").hide(); //lo rendo invisibile
            $("#indietro-mail").show();
            $(".container-creazione_mail").show();
            $("#cliente_container").show();
            //remove active from Progress Bar
            var list = document.querySelectorAll("li");
            for(var i = 0; i<list.length ; i++){
                if(list[i].textContent == 'CRM' || list[i].textContent == 'ORDINE' || list[i].textContent == 'SPEDIZIONE'){
                    $("#crm").addClass('active');
                    $("#ordine").addClass('active');
                    $("#spedizione").removeClass('active');
                }
            }
        }
    });
}); // chiuso doucument.ready 
