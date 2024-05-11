class CamiciaOrdinata {
    constructor(vest, taglia, taglia_collo, materiale_id, id = null){ // costruttore 
        this.id = null; 
        this.taglia = taglia;
        this.taglia_collo = taglia_collo;
        this.materiale_primario_id = materiale_id;
        this.vestibilita = vest;
        this.qty = 0; // parte da 0 
        this.componenti = []; // componente è un array 
        this.alterazioni = []; // alterazioni è un array
        this.ricamo = {}; // è un oggetto
        this.bottoni_mp = 0; // Bottoni madre perla parte da 0
        // Descrizioni (solo x frontend)
        this.descrizioni = {
            davanti: "",
            dietro: "",
            polsi: "",
            tasche: "",
            collo: "",
            materiale_primario: ""
        }
    }
    /**
     * Aggiungi un nuovo componente
     * @param  {int} id id componente
     * @return {void}
     */
    aggiungiComponente(id){
        this.componenti.push({  // aggiungi all'array componenti l'id e il materiale primario 
            component_id: id,
            materiale: this.materiale_primario_id
        });
    }
    /**
     * Aggiungi alterazione
     * @param  {int} id     id alterazione
     * @param  {float} valore valore alterazione
     * @return {void}
     */
    aggiungiAlterazione(id, valore){ // aggiungi all'array alterazioni l'id dell'alterazione e il suo valore 
        this.alterazioni.push({
            alteration_id: id,
            value: valore
        });
    }
    /**
     * imposta il ricamo alla camicia (ogni volta lo sovrascrive)
     * @param {string} testo
     * @param {int} stile_id
     * @param {int} pos_testo_id
     * @param {int} col_id
     * @param {int} sim_id
     * @param {int} pos_sim_id
     * @param {int} ordine_sim_id
     * @param {int} col_sim_id
     */
    setRicamo(testo, stile_id, pos_testo_id, col_id, sim_id, pos_sim_id, ordine_sim_id, col_sim_id){
        if(testo != ""){
            this.ricamo.testo = testo;
        }
        if(stile_id >= 0){
            this.ricamo.stile = stile_id;
        }
        if(pos_testo_id >= 0){
            this.ricamo.pos_testo = pos_testo_id;
        }
        if(col_id >= 0){
            this.ricamo.colore = col_id;
        }
        if(sim_id >= 0){
            this.ricamo.simbolo = sim_id;
        }
        if(pos_sim_id >= 0){
            this.ricamo.pos_simbolo = pos_sim_id;
        }
        if(ordine_sim_id >= 0){
            this.ricamo.ordine_simbolo = ordine_sim_id;
        }
        if(col_sim_id >= 0){
            this.ricamo.colore_simbolo = col_sim_id;
        }
    }
    /**
     * Imposta la quantità
     * @param {int} qty
     */
    setQty(qty){
        this.qty = qty;
    }
    /**
     * Imposta il flag dei bottoni madre perla
     * @param {int} flag_btn_mp
     */
    setBottoniMadrePerla(flag_btn_mp){
        this.bottoni_mp = flag_btn_mp;
    }
}
class LectraOrdine {
    constructor(id){
        this.id = id;
        this.articoli = {}
    }
    /**
     * Aggiunge / Sovrascrive un articolo all'ordine
     * @param {int} id
     * @param {CamiciaOrdinata} articolo
     * @throws TypeError
     */
    replaceArticolo(id, articolo){
        if(! articolo instanceof CamiciaOrdinata){
            throw new TypeError("articolo deve essere di tipo CamiciaOrdinata");
        }
        this.articoli[id] = articolo;
    }
    /**
     * Rimuove l'articolo dall'ordine
     * @param  {int} id
     */
    rimuoviArticolo(id){
        delete this.articoli[id];
    }
    /**
     * Duplica l'articolo nell'ordine
     * @param  {int} id_da_duplicare id elemento da duplicare
     * @param  {int} new_id nuovo id dell'elemento duplicato
     */
    duplicaArticolo(id_da_duplicare, new_id){
        if(this.articoli[id_da_duplicare] != undefined){
            this.articoli[new_id] = Object.assign(
                Object.create(Object.getPrototypeOf(this.articoli[id_da_duplicare])),
                this.articoli[id_da_duplicare]
            ); // Clone
        }
    }
}