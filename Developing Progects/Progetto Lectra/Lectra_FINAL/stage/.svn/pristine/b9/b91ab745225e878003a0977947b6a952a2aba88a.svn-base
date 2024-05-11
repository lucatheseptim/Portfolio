<?php
/**
 * Created by PhpStorm.
 * User: psalemi
 * Date: 25/03/21
 * Time: 15.23
 */


class APICaller{

    private $header_params;

    public function __construct(){
        $this->header_params = array();
    }

    public function callAPI($method, $url, $data = false){
        $curl = curl_init();
        $this->addCallHeader($curl);



        switch ($method){
            case "POST":
                echo("postttt");

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

                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        /*curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "username:password");*/
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        echo("<pre>---------");
        print_r($result);
        echo("</pre>");

        $info = curl_getinfo($curl);

        echo("<pre>---------");
        print_r($info);
        echo("</pre>");




        curl_close($curl);

        return $result;
    }

    public function setHeader($params){
        $this->header_params = array_merge($this->header_params, $params);
    }

    public function resetHeader(){
        $this->header_params = array();
    }

    private function addCallHeader($curl){
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header_params);
    }
}




$headers = array();
$headers[] = "TadAuthToken: CLT-61F898F8-BC0B-4DE5-AE0D-84E97D104ED6";
$headers[] = "Content-Type: application/json";
$path="http://172.31.212.21:9001/api/extDataAccess/SetClientiDettaglio";

$params='{"Clienti":[{"ID":"LECTRAL|20"}]}';

$params=  json_encode($params,'JSON_UNESCAPED_SLASHES');


$params= array();
$params["Clienti"][]= array("ID"=>"LECTRAL|20");
$params=  json_encode($params,JSON_UNESCAPED_SLASHES);
//$params=  json_encode($params);

$api_caller= new APICaller();
$api_caller->resetHeader();
$api_caller->setHeader($headers);


echo("<pre>header---------");
print_r($headers);
echo("</pre>");

echo("<pre>path---------");
print_r($path);
echo("</pre>");

echo("<pre>params---------");
print_r($params);
echo("</pre>");

/*
Dbg::d("headers",$headers,1);
Dbg::d("path",$path,1);
Dbg::d("params",$params,1);
*/




$res_call= $api_caller->callAPI("POST", $path, $params);
$res_call = json_decode($res_call);




echo("<pre>api caller---------");
print_r($api_caller);
echo("</pre>");

echo("<pre>res call---------");
print_r($res_call);
echo("</pre>");



