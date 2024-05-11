<?php
/**
 * Lettore JSON
 */
class JSONReader {
    /**
     * @param  string $path
     * @return array
     * @throws Exception
     */
    public static function readContentData($path){
        $json_string = file_get_contents($path);
        if ($json_string === false){
            throw new Exception("Errore lettura file $path");
        }
        $data = json_decode($json_string, true);
        if ($data === null){
            throw new Exception("Errore parsing JSON");
        }
        return $data;
    }
}
?>
