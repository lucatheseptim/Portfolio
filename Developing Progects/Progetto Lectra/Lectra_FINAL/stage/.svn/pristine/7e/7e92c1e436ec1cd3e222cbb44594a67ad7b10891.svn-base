<?php
include_once ("/var/www/html/lectra/stage/utils/Dbg.php");

class APICaller{
    /** @var array */
    private $header_params;

    public function __construct(){
        $this->header_params = array();
    }
    /**
     * Esegue la chiamata API
     * @param  string  $method API method
     * @param  string  $url    Endpoint
     * @param  boolean $data   (Optional) Api data. Dipende dal metodo selezionato
     * @return string          Body della risposta API. false in caso di errore
     */
    public function callAPI($method, $url, $data = false){
        $result = $this->callAPIComplete($method, $url, $data);
        return $result["body"];
    }
    /**
     * Esegue la chiamata API ritornando anche le informazioni sull'API.
     * @param  string  $method API method
     * @param  string  $url    Endpoint
     * @param  boolean $data   (Optional) Api data. Dipende dal metodo selezionato
     * @return array          "body" contiene la risposta, "info" contiene le informazioni sulla risposta
     */
    public function callAPIComplete($method, $url, $data = false){
        $curl = curl_init();
        $this->addCallHeader($curl);

        Dbg::d("method",$method,1);
        switch ($method){
            case "POST":
                Dbg::d("posttt",$method,1);

                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data){
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                // curl_setopt($curl, CURLOPT_PUT, 1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "GET-BODY":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                Dbg::d("default",$method,1);
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // Optional Authentication:
        /*curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "username:password");*/
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        Dbg::d("resulttt",$result,1);
        $info = curl_getinfo($curl);
        Dbg::d("info",$info,1);
        //echo print_r($info,1);
        curl_close($curl);

        return array("body" => $result, "info" => $info);
    }
    /**
     * Aggiunge gli header all'oggetto curl
     * @param CurlHandle $curl cURL handle
     */
    private function addCallHeader($curl){
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header_params);
    }
    /**
     * Aggiunge gli header alla chiamata. Gli header già presenti non vengono cancellati
     * @param string[] $params
     */
    public function setHeader($params){
        $this->header_params = array_merge($this->header_params, $params);
    }
    /**
     * Pulisce gli header della chiamata
     */
    public function resetHeader(){
        $this->header_params = array();
    }
}

?>
