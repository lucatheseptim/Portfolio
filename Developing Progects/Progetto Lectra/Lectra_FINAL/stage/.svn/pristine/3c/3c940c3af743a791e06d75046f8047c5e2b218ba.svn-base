<?php
include_once ("/var/www/html/lectra/stage/utils/Dbg.php");

class InvioMail{

	protected $mail;

	public function __construct ($subject){
		require_once '/var/www/html/lectra/stage/lib/PHPMailer/PHPMailerAutoload.php';
		$this->mail  = new PHPMailer();
		$this->mail->IsSMTP();
		$this->mail->SMTPAuth   = true;
		$this->mail->SMTPSecure = "tls";
		$this->mail->Host       = "smtp.gmail.com";
		$this->mail->Port       = 587;
		$this->mail->Username   = "servizio@feniciaspa.it";
		$this->mail->Password   = "800ay0up05n!";
		$this->mail->From       = "servizio@feniciaspa.it";
		$this->mail->FromName   = "Indagini 2.0";
		$this->mail->Subject    = $subject;
	}

	public function setBody($html){
		$this->mail->MsgHTML($html);
	}

	public function setFrom($text){
		$this->mail->From = $text;
	}

	public function setFromName($text){
		$this->mail->FromName = $text;
	}

	public function setHost($text){
		$this->mail->Host = $text;
	}

	public function setPort($int){
		$this->mail->Port = $int;
	}

	public function setSMTPSecure($text){
		$this->mail->SMTPSecure = $text;
	}

	public function setUserPassword($user,$password){
		$this->mail->Username = $user;
		$this->mail->Password = $password;
	}

	public function A(array $address){
		foreach ($address as $mail) {
			$this->mail->AddAddress($mail);
		}
	}

	public function CC(array $address){
		foreach ($address as $mail) {
			$this->mail->AddCC($mail);
		}
	}

	public function CCN(array $address){
		foreach ($address as $mail) {
			$this->mail->AddBcc($mail);
		}
	}

	public function send(){
		$this->mail->IsHTML(true);
		if(!$this->mail->Send()) {
			error_log( "Mailer Error: " . $this->mail->ErrorInfo );
		} else  error_log( "Message ".$this->mail->Subject." sent!" );
	}

	public function allegato($path){
		$this->mail->AddAttachment($path);
	}
}


/*
class ConfiguratorStage extends Configurator{
	 	protected $conf = 'config.inc_stage.php';
}
*/

class Configurator {

	/**
	 * @var string $conf
	 */


	protected $conf = 'config.inc.php';

	/**
	 * @var array $arr
	 */
	protected $arr = array ();

	/**
	 * @param string $path
	 */
	public function __construct () {

		$path= dirname(__FILE__)."/".$this->conf;
		if (!is_readable ($path)) {
			die ($path . " does not exists or just isn't readable!");
		}
		include ($path);
		foreach (get_defined_vars () as $key => $value) {
			$this->__set ($key, $value);
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set ($key, $value) {
		$this->arr[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key) {
		return (isset ($this->arr[$key]) ? $this->arr[$key] : NULL);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key) {
		return (isset ($this->arr[$key]));
	}

}

/**
 * @package Connector
 */
class OdbcStmt {
	const PDO_NO_ERROR_MSG = "none";
	const PDO_ERROR_EXEC_MSG = "error_exec";
	/**
	 * @var int $db
	 */
	protected $db;
	/**
	 * @var PDOException $last_error
	 */
	protected $last_error;
	protected $row_count;
	/**
	 * @param Configurator $config
	 */
	public function __construct (Configurator $config) {
		$this->db = $this->create ($config);
		$this->last_error = null;
		$this->row_count = null;
	}
	/**
	 * @param string $sql
	 * @return array
	 */
	public function query ($sql, $params = array()) {
		$this->row_count = null;
		$tmp = array();
		$stmt = null;
		try{
			$status = false;
			$stmt = $this->db->prepare($sql);
			$status = $stmt->execute($params);
			while( $row = $stmt->fetchObject() ) {
				$tmp[] = $row;
			}
			Dbg::d("stmt", $stmt,1);
			Dbg::d("status", $status,1);
		}catch(PDOException $e){
			if($stmt)
				$this->row_count = $stmt->rowCount();
			if($status)
				return self::PDO_NO_ERROR_MSG;
			$this->last_error = $e;
			return self::PDO_ERROR_EXEC_MSG;
		}

		return $tmp;
	}

	/**
	 * @param string $sql
	 */
	public function set ($sql) {
		$this->db->exec($sql);
	}

	/**
	 * @return int last inserted id
	 */
	public function lastInsertId(): int{
		return $this->db->lastInsertId();
	}
	/**
	 * @return array [SQLSTATE Code, Driver error code, Driver error msg]. Vuoto nel caso non si sia verificato alcun errore.
	 */
	public function getLastError(): array{
		return (!$this->last_error) ? array() : $this->last_error->errorInfo;
	}


	public function getRowCount() {
		return $this->row_count;
	}

}

class AS400Connector extends OdbcStmt{

	/**
	 * @param Configurator $config
	 * @return int
	 */
	public static function create ($config) {
		$host = $config->as400_host;
		$user = $config->as400_user;
		$password = $config->as400_password;
		$dbh = new PDO("odbc:DRIVER={iSeries Access ODBC Driver};SYSTEM=$host;PROTOCOL=TCPIP", $user, $password);
		$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		return $dbh;
	}

}

class MysqlConnector extends OdbcStmt {

	public static function create (Configurator $config) {
		$dbhost=$config->db_host;
		$dbuser=$config->db_user;
		$dbpass=$config->db_password;
		$dbname=$config->db_name;
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

}

class IndaginiMysqlConnector extends OdbcStmt {

	public static function create (Configurator $config) {
		$dbhost=$config->db_indagini_host;
		$dbuser=$config->db_indagini_user;
		$dbpass=$config->db_indagini_password;
		$dbname=$config->db_indagini_name;
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

}

class TimbMysqlConnector extends OdbcStmt {

	public static function create (Configurator $config) {
		$dbhost=$config->db_timb_host;
		$dbuser=$config->db_timb_user;
		$dbpass=$config->db_timb_password;
		$dbname=$config->db_timb_name;
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

}


?>
