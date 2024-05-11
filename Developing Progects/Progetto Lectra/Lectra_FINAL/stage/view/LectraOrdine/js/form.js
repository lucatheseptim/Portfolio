$(document).ready(function(){
    $(".container-creazione_mail").hide();
    //componenti -> cliente esistente
    //indietro utente esistente -> componenti
    $("#indietro-mail").click(function() {
        $("#indietro-mail").hide();
        $(".container-creazione_mail").hide();
        $(".container-selezione_componenti").hide();
        $(".container-repilogo-camicia").show();  
    });
    /* ================================================= */
     //checkbox Si -Insert email
    $("#clienti_esistente_si").unbind();
    $("#clienti_esistente_si").click(function(){
          if($(this).is(":checked")) {
             $("#clienti_esistente_no").prop('checked' , false);
             $("#cliente_esistente").show();
             $("#conferma-email-esistente").show();
         } else {
             $(".container-creazione_mail").show();
             $("#cliente_esistente").hide();
         }
      });
       //conferma si email esistente
    $("#conferma-email-esistente").on( "click", function() {
        var emailVal=  $('#Email').val();
        $.ajax({
            url: "/lectra/stage/init.php",
            dataType: "json",
            type: "POST",
            data: {
                action: "get_user_by_crm",
                val_fields: { Email:emailVal }
            }
        }).done(function (ret) {

        });
        $(".error").hide();
        var hasError = false;
        //verifica emailValida
        var emailReg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if(emailVal.length == 0){
            hasError = true;
            swal("Attenzione!", "Campo obbligatorio", "warning");
        }else if(!emailReg.test(emailVal)){
            hasError = true;
            swal("Attenzione!","Selezionare una email valida.","warning");
        } else{
            $(".container-inserimento_spedizione").show();
            $("#indietro-mail").hide();
            $("#utente").hide();
        }
        if(hasError== true){return false;}
    });
     //checkNo -> create account
    $("#clienti_esistente_no").unbind();
    $("#clienti_esistente_no").click(function () {
        if($(this).is(":checked")){
            $("#clienti_esistente_si").prop('checked' , false);
            $(".nuovo_cliente").show();
            $("#indietro-create-account").show();
            $("#cliente_container").hide();
            $("#indietro-mail").hide();
        }else{
            $(".container-creazione_mail").show();
        }
        //click del No esegue l'ajax provincia
        $.ajax({
            url:"/lectra/stage/init.php",
            method:"POST",
            dataType:"json",
            data:{
                action:"get_field_data",
                val_field:"provincia"
            }
        }).done(function(res){
            if(res.output.success === 1){
                options = res.output.data;
                $("select.selectInfo").empty();
                $("select.selectInfo").append("<option value='0'>Seleziona:</option>");
                for(row in options){
                    $("select.selectInfo").append("<option value='" + options[row].ID + "'>" + options[row].DESCRIZIONE + "</option>");
                }
            }
        });
    }); 
     //conferma newUser -> spedizione 
    $("#create-account").unbind();  
    $("#create-account").click(function(){
         var saveEmail = $('#Emails').val();
         $.ajax({
             url: "/lectra/stage/init.php",
             dataType: "json",
             type: "POST",
             data: {
                 action: "save_user_on_crm",
                 val_fields:{
                    Cognome: $('#Cognome').val(),
                    Nome: $('#Nome').val(),
                    DataNascita: $('#DataNascita').val(),
                    Sesso: $('.genere').val(),
                    PartitaIva: $('#PartitaIva').val(),
                    //CodiceFiscale: $('#Cognome').val(),
                    Email: $('#Emails').val(),
                    Via: $('#Via').val(),
                    Localita: $('#Localita').val(),
                    Provincia: $('select.selectInfo').val(),
                    Nazione: $('#naz').val(),
                    Cap: $('#Cap').val(),
                    Telefono: $('#telefono').val(),
                    ConsensoMarketing:$('#ConsensoMarketing').prop("checked") ? 1 : 0,
                    ConsensoProfilazione:$('#ConsensoProfilazione').prop("checked") ? 1 : 0,
                    ConsensoComunicazioneATerzi:$('#ConsensoComunicazioneATerzi').prop("checked") ? 1 : 0,
                    ConsensoEmail:$('#ConsensoEmail').prop("checked") ? 1 : 0,
                 }
             }  
         }).done(function (ret) {

         });
         $(".container-creazione_mail").hide(); 
         $(".container-bar").show(); // rendo visibile la progress bar 
         $(".container-inserimento_spedizione").show(); // visualizzo il container spedizione
         $(".nuovo_cliente").hide();
         $('.container-bar li.active').removeClass('active'); // rimuovo agli elementi li la proprietà active
         var lis = document.querySelectorAll("li"); // prendo tutti gli elementi
         for (var i = 0; i < lis.length; i++) {  
             if(lis[i].textContent == 'CRM' || lis[i].textContent == 'ORDINE'  || lis[i].textContent == 'SPEDIZIONE' ){ // verifivco il testo dell' elemento li 
                 $("#crm").addClass('active');
                 $("#ordine").addClass('active');
                 $("#spedizione").addClass('active');
             }
         }
     });  
    //indietro createAccount -> email esistente
    $("#indietro-create-account").click(function() {
        $("#cliente_container").show();
        $("#indietro-mail").show();
        $("#indietro-create-account").hide();
        $(".nuovo_cliente").hide(); 
    });
    //select all checkbox consenso
    $("#selectAll").click(function(){
        $("input[type=checkbox]").prop("checked", $(this).prop("checked"));
    });
    $("input[type=checkbox]").click(function() {
        if (!$(this).prop("checked")) {
            $("#selectAll").prop("checked", false);
        }
    });
});