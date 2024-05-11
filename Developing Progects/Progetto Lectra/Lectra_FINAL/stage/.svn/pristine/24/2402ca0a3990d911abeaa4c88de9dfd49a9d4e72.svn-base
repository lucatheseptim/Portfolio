$(document).ready(function () { // una volta che l' html è stato letto tutto
        //tasto indietro Riepilogo-Camicie
        $("#riepilogo-camicie").click(function(){
            $(".container-selezione_componenti ").show(); // lo rendo visibile
            $(".container-information").hide(); // lo rendo invisibile
            $("#riepilogo-camicie").hide(); // lo rendo invibile
            $(".container-repilogo-camicia").hide(); // lo rendo invibile
            //remove active dalla progress bar
            var list = document.querySelectorAll("li");
            for( var i = 0; i<list.length ; i++){
                if(list[i].textContent == 'CRM' || list[i].textContent == 'ORDINE' || list[i].textContent == 'SPEDIZIONE'){
                    $("#ordine").addClass('active');
                    $("#crm").removeClass('active');
                    $("#spedizione").removeClass('active');
                }
            }
        }); // chiuso Riepilogo-Camicie
}); // chiudo document.ready

function refreshBtnAggiornaCamicia(){
    // Modifica
    $(".modificaBtn").unbind();
    $(".modificaBtn").on('click' , function () {
        $(".container-repilogo-camicia").hide();
        $(".container-all").show();
        $(".container-selezione_vestibilita").show();
        //dati salvati
        //qui cancello la riga della camicia precedente
        let id_camicia = $(this).data("id-camicia");
        ordine_lectra.rimuoviArticolo(id_camicia);
        $('.row_camicia-selezionata').remove();
    });
   // Duplica
    $(".duplicaBtn").unbind();
    $(".duplicaBtn").on('click', function (){
        let id_camicia = $(this).data("id-camicia");
        ordine_lectra.duplicaArticolo(id_camicia, last_camicia_id);
        creaRiepilogo(ordine_lectra.articoli);
        last_camicia_id++;
        refreshBtnAggiornaCamicia();
        eventSalvaMateriale();
    });
    // Delete
    $(".deleteBtn").unbind();
    $(".table_dark").on('click', '.deleteBtn', function() {
        let id_camicia = $(this).data("id-camicia");
        var row = $(this);
        swal({
          title: "SEI SICURO?",
          text: "Sei sicuro di voler rimuovere questo elemento dal carrello?",
          icon: "warning",
          buttons: true,
          dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                ordine_lectra.rimuoviArticolo(id_camicia);
                row.closest('.row_camicia-selezionata').remove(); // cancello la riga contenentente le caratteristiche della camicia
                swal("Il tuo elemento è stato rimosso", {
                    icon: "success",
                });
            } else {
                swal("Nessun elemento rimosso!");
            }
        });
    });
}