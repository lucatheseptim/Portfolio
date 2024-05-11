<?php



/****************
Debug
 ***************/
class Dbg {

    public static $log_filename=NULL;
    public static $debug_active=0;
    public static $excluded_actions=array();

    public static function d($key, $value, $debug=1, $function_name='' ) {
        if(self::$debug_active){
            if($debug){
                echo $function_name.' '.$key . " = ";
                switch (gettype($value)) {
                    case 'integer' :
                    case 'double' :
                    case 'boolean' :
                    case 'string' :
                        echo $value;
                        echo "<br/>";
                        break;
                    case 'array' :
                    case 'object' :
                    default :
                        echo '<pre>';
                        print_r($value);
                        echo '</pre>';
                        break;
                }
            }
        }
    }


    public static function print_mem()
    {
        /* Currently used memory */
        $mem_usage = memory_get_usage();
        /* Peak memory usage */
        $mem_peak = memory_get_peak_usage();
        Dbg::d("The script is now using: ", "<strong>".round($mem_usage / 1024)."KB</strong> of memory<br>" , 1);
        Dbg::d("Peak usage: ", "<strong>".round($mem_peak / 1024) ."KB</strong> of memory<br>" , 1);
    }

    public static function microtime($label="",$debug){
        if(1){
            // if(self::$debug_active){
            if($debug) {
                $t = microtime(true);
                $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
                $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
                error_log( "<br>microtime - ".$label.": ".$d->format("Y-m-d H:i:s.u")."<br/>");
            }
        }
    }

    public static function log_action($logged_id,$action,$request_no_pwd){
        if(!in_array($action,self::$excluded_actions)){
            self::log("user_id: ".$logged_id.", request: ".json_encode($request_no_pwd));
        }
    }

    public static function log($txt) {

        if(self::$log_filename){
            $d = new DateTime();


            $fp = fopen(self::$log_filename, 'a');//opens file in append mode.
            fwrite($fp, $d->format('Y-m-d H:i:s')." ".$txt.PHP_EOL);
            fclose($fp);
        } else {
            self::d("Dbg log","filename not configured");
        }

    }
}


?>