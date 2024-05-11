$(document).ready(function() {

    //CALENDAR PLUGIN

    //ISTANZIO LA DATA CORRENTE 
    
    var d = new Date();  
      
    var month = String(d.getMonth()+1).padStart(2, '0');    // PRENDO IL MESE
    var day = String(d.getDate()).padStart(2, '0');        // PRENDO IL GIORNO 
    var year = d.getFullYear();  // PRENDO L'ANNO  

    //IMPOSTO IL CALENDARIO CORRENTE 
    $("#datepicker_data_da").val(day+"-"+month+"-"+year).data('Zebra_DatePicker'); 

    //IMPOSTO IL CALENDARIO CORRENTE 
    $("#datepicker_data_a").val(day+"-"+month+"-"+year).data('Zebra_DatePicker'); 
    

    //IMPOSTO IL FORMATO DELLA DATA DA 
    $("#datepicker_data_da").Zebra_DatePicker({ // Zebra Date Picker imposto il formato della data 
        format: 'd-m-Y' , 
 
    });


    $('#datepicker_data_da-on-change').Zebra_DatePicker({
        onChange: function(view, elements) {
            if (view === 'days') {
                elements.each(function() {
                    if ($(this).data('date').match(/\-24$/))
                        $(this).css({
                            background: '#C40000',
                            color:      '#FFF'
                        });
                });
            }
        }
    });

    //IMPOSTO IL FORMATO DELLA DATA A 
    $("#datepicker_data_a").Zebra_DatePicker({
        format: 'd-m-Y',
    }); 


    $('#datepicker_data_a-on-change').Zebra_DatePicker({
        onChange: function(view, elements) {
            if (view === 'days') {
                elements.each(function() {
                    if ($(this).data('date').match(/\-24$/))
                        $(this).css({
                            background: '#C40000',
                            color:      '#FFF'
                        });
                });
            }
        }
    });

    

});