<?php

/**
 * Basic Classes
 *
 * This file contains some basic classes to include in all projects
 *
 * <code>require_once ('base.php');</code>
 *
 * @package Base
 */


require_once 'include/configurator.php';
require_once 'include/connector.php';
// require_once 'include/ez_sql/ez_sql_core.php';
// require_once 'include/ez_sql/ez_sql_mysql.php';


/**
 * @param array $marray
 * @param string $column
 * @return array
 */
function array_my_multisort (array $marray, $column) {
    foreach ($marray as $key => $value) {
        $sortarr[] = $value[$column];
    }
    array_multisort ($sortarr, SORT_DESC, $marray);
    return $marray;
}

/**
 * @param array $array
 * @param array $cols
 * @return array
 */
function array_msort (array $array, array $cols) {
    $colarr = array ();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array ();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower ($row[$col]); }
    }
    $params = array ();
    foreach ($cols as $col => $order) {
        $params[] =& $colarr[$col];
        $params = array_merge ($params, (array) $order);
    }
    call_user_func_array ('array_multisort', $params);
    $ret = array ();
    $keys = array ();
    $first = true;
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            if ($first) { $keys[$k] = substr ($k,1); }
            $k = $keys[$k];
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
        $first = false;
    }
    return $ret;
}

/**
 * This function is pretty straight-forward. Use it like this
 *
 * <code>$thumb_filename = makeThumb ($source_filename);</code>
 *
 * @param string $src
 * @return string
 */
function makeThumb ($src) {
	require_once ('phmagick/phmagick.php');
	$temp = explode("/",$src);
	//$thumb = "/var/www/rsync/".$temp[2]."/".substr ($temp[3], 0, -4) . '_thumb.jpg';
	$thumb = "/var/www/rsync/".$temp[2]."/".substr ($temp[5], 0, -4) . '_thumb.jpg';
	if (!is_file ($thumb)) {
		$phMagick = new phMagick ($src);
		$phMagick->setDestination ($thumb)->resize (180, 150, true);
	}
	return $thumb;
}

function makeThumbM ($src) {
	require_once ('phmagick/phmagick.php');
	$temp = explode("/",$src);
	$thumb = "/var/www/rsync/".$temp[2]."/".substr ($temp[5], 0, -4) . '_thumbm.jpg';
	if (!is_file ($thumb)) {
		$phMagick = new phMagick ($src);
		$phMagick->debug = true;
		$phMagick->setDestination ($thumb)->resize (380, 250, true);
	}
	return $thumb;
}

/**
 * Time your queries and write them into a table
 *
 * <code>
 * $config = new Configurator ();
 * $db = DbConnector::create ($config);
 * $ctrl = new SQLController ($db);
 * $ctrl->add ($sql);
 * $ctrl->save (get_class ($this));
 * </code>
 *
 * @package Base
 */
class Timer {

	/**
	 * @var $_start;
	 */
	protected $_start;

	public function __construct () {
		$this->_start = $this->now ();
	}

	/**
	 * @return float
	 */
	protected function now () {
		return microtime (true);
	}

	/**
	 * @return float
	 */
	public function get () {
		return $this->now () - $this->_start;
	}

}

/**
 * @package Base
 */
// class SQLController {
//
// 	protected $user;
// 	protected $db;
// 	protected $timer;
//
// 	protected $sql = array ();
//
// 	public function __construct (ezSQL_mysql $db) {
// 		global $sessionAuth;
// 		$this->user = $sessionAuth->user;
// 		$this->db = $db;
// 		$this->timer = new Timer ();
// 	}
//
// 	public function add ($sql) {
// 		$this->sql[] = array ($sql, $this->timer->get ());
// 		$this->timer = new Timer ();
// 	}
//
// 	public function save ($classname) {
// 		foreach ($this->sql as $query) {
// 			$sql = sprintf (
// 				"INSERT INTO sqlcontroller (user, query, executed, classname, duration) VALUES ('%s', '%s', NOW(), '%s', '%s')",
// 				$this->user,
// 				$this->db->escape ($query[0]),
// 				$classname,
// 				$query[1]
// 			);
// 			$this->db->query ($sql);
// 		}
// 	}
//
// 	public function get(){
// 		return $this->sql;
// 	}
// }

/**
 * @package Base
 */
class Template {

	/**
	 * @var string $content
	 */
    private $content;

	/**
	 * @var array $arr
	 */
    private $arr = array ();

	/**
	 * @param string $file
	 */
	public function __construct ($file) {
        if (!is_readable ($file)) {
			die ("Couldn't find " . $file . "!");
		}
		$this->content = file_get_contents ($file);
    }

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set ($key, $value) {
        $this->arr[$key] = $value;
    }

	/**
	 * @return string
	 */
    public function out () {
        foreach ($this->arr as $key => $value) {
            $this->content = str_replace ('{' . $key . '}', $value, $this->content);
        }
        return $this->content;
    }

}

/**
 * @package Base
 */
class RequestFilter {

	/**
	 * @param string $key
	 * @return mixed
	 */
	private function getVar ($key) {
		return isset ($_REQUEST[$key]) ? $_REQUEST[$key] : '';
	}

	/**
	 * @param string $key
	 * @param string $preset
	 * @return string
	 */
	public function getClient ($key, $preset = '') {
		$arr = array ('options' => array ('regexp' => '/^[A-Z0-9_]{8}$/'));
		$retval = filter_var ($this->getVar ($key), FILTER_VALIDATE_REGEXP, $arr);
		return (!empty ($retval) ? $retval : $preset);
    }

	/**
	 * @param string $key
	 * @param string $preset
	 * @return string
	 */
	public function getInt ($key, $preset = '') {
		$retval = filter_var ($this->getVar ($key), FILTER_VALIDATE_INT);
		return (!empty ($retval) ? $retval : $preset);
	}

	/**
	 * @param string $key
	 * @param string $preset
	 * @return string
	 */
	public function getEmail ($key, $preset = '') {
		$retval = filter_var ($this->getVar ($key), FILTER_VALIDATE_EMAIL);
		return (!empty ($retval) ? $retval : $preset);
	}

}

/**
 * @package Base
 */
class ServerFilter {

	/**
	 * @return mixed
	 */
	public function getRemoteAddress () {
		return filter_var ($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

}

/**
 * @package Base
 */
class Selector {

	/**
	 * @var string $id
	 */
	public $id;

	/**
	 * @var string $name
	 */
	public $name = 'undefined';

	/**
	 * @var string $klass
	 */
	public $klass = 'undefined';

	/**
	 * @var array $arr
	 */
	protected $arr = array ();

	/**
	 * @return array
	 */
	public function get () {
		return $this->arr;
	}

	public function first () {
		if (!empty ($this->arr)) {
			$temp = array_keys ($this->arr);
			return $temp[0];
		}
		return NULL;
	}

	public function out ($str = '', $options = '') {
		if (NULL === $this->id) $this->id = $this->name;
		foreach ($this->arr as $key => $value) {
			$options .= sprintf (
				'<option value="%s"%s>%s</option>',
				$key,
				($str == $key ? ' selected="selected"' : ''),
				$value
			);
		}
		return sprintf (
			'<select id="%s" name="%s" class="%s">%s</select>',
			$this->id, $this->name, $this->klass, $options
		);
	}

	public function setID ($id){
		$this->id = $id;
	}

}

/**
 * Generic class: May be you'll never have to use the class directly
 *
 * @package Base
 */
class AS400Table {

	/**
	 * @var FactoryCami $fct
	 */
	public $fct;

	/**
	 * @var array $arr
	 */
	protected $arr = array ();

	/**
	 * @param FactoryCami $fct
	 */
	public function __construct (FactoryCami $fct) {
		$this->fct = $fct;
	}

}

/**
 * Generic Record in a DB2
 *
 * May be you'll never use this class directly.
 *
 * @package Base
 */
class AS400Row {

	/**
	 * @param StdClass $obj
	 */
	public function __construct (StdClass $obj) {
		$this->set ($obj);
	}

	/**
	 * @param StdClass $obj
	 */
	public function set (StdClass $obj) {
		$cls_vars = array_keys (get_object_vars ($this));
		$row_var = get_object_vars ($obj);
		foreach ($row_var as $key => $value) {
			if ($key{0} != '_') {
				$key = strtolower ($key);
				if (in_array ($key, $cls_vars)) {
					$this->$key = $value;
				} else {
					$log = sprintf (
						"%s = %s is not expected in %s",
						$key,
						print_r ($value, TRUE),
						get_class ($this)
					);
					error_log ($log);
				}
			}
		}
	}

}

/**
 * @package Base
 */
// class MysqlStatement {
//
// 	protected $db;
// 	protected $arr = NULL;
//
// 	public function __construct (ezSQL_mysql $db) {
// 		$this->db = $db;
// 	}
//
// 	public function getArgs () { }
//
// }

/**
 * @package Base
 */
interface iHashUnique {

	/**
	 * @param ListShops $obj
	 */
	static function create (AS400Table $obj);

}

/**
 * @package Base
 */
class HashUnique {

	/**
	 * @var array $arr
	 */
	protected $arr = array ();

	/**
	 * @param array $arr
	 */
	public function __construct (Array $arr) {
		asort ($arr);
		$this->arr = array_filter ($arr);
	}

	/**
	 * @return array $arr
	 */
	public function get () {
		return $this->arr;
	}

}

/**
 * @package Base
 */
interface iSummary {

	/**
	 * @param array $arr
	 */
	public function addSummary (Array $arr);

}

/**
 * @package Base
 */
class Summary {

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function set ($key, $value) {
		$this->$key = $value;
	}

	/**
	 * @return array
	 */
	public function get () {
		return $this->arr;
	}

}

/**
 * @package Base
 */
class JsTableDescr {

	/**
	 * @var array $arr
	 */
	protected $arr;

	/**
	 * @return string
	 */
	public function get () {
		$arr = array ();
		foreach ($this->arr as $obj) {
			$arr[] = $obj->get ();
		}
		return "[ " . implode (", ", $arr) . " ]";
	}

}

/**
 * @package Base
 */
class JsTableItem {

	/**
	 * @return string
	 */
	public function get () {
		$arr = array ();
		foreach (get_object_vars ($this) as $key => $value) {
			if (!is_null ($value)) {
				$arr[] = $key . ": '" . $value . "'";
			}
		}
		return "{ " . implode (", ", $arr) . " }";
	}

}

class Decorator {

	/**
	 * @var bool $format
	 */
	protected $format;

	/**
	 * @param bool $format
	 */
	public function __construct ($format = FALSE) {
		$this->format = ($format ? TRUE : FALSE);
	}

}

/**
 * @package Base
 */
interface iStaticDescription {

	public function hash ();

}

/**
 * @package Base
 */
class StaticDescription extends Decorator {

	/**
	 * @param string $key
	 * @return string
	 */
	public function get ($key) {
		$arr = $this->hash ();
		return ($this->format && isset ($arr[$key]) ? $arr[$key] : $key);
	}

}

/**
 * @package Base
 */
interface iGenericDecorator {

	public function get ($num);

}

/**
 * @package Base
 */
class MoneyDecorator extends Decorator implements iGenericDecorator {

	public function get ($num) {
		$num = round ($num, 2);
		return ($this->format ? number_format ($num, 2, ',', '.') : $num);
	}

}

/**
 * @package Base
 */
class IntDecorator extends Decorator implements iGenericDecorator {

	public function get ($num) {
		return ($this->format ? number_format (round ($num), 0, ',', '.') : $num);
	}

}

class GenericDecorator {

	protected $obj;

	public function __construct ($obj) {
		$this->obj = $obj;
	}

	public function __call ($method, $args) {
		return call_user_func_array (array ($this->obj, $method), $args);
	}

	final protected function _int ($str, $postfix = '') {
		return round ($str) . (!empty ($postfix) ? ' ' . $postfix : '');
	}

	final protected function _float ($str, $postfix = '',$USAmoney = '') {
		if($USAmoney)
			return number_format (round ($str, 2), 2, '.', ',') . (!empty ($postfix) ? ' ' . $postfix : '');
		else
			return number_format (round ($str, 2), 2, ',', '.') . (!empty ($postfix) ? ' ' . $postfix : '');
	}

}

/**
 * @package Base
 */
interface iGChart {

	public function setData (array $arr);

}

/**
 * @package Base
 */
class GChart {

	const path = 'https://chart.googleapis.com/chart?cht=%s&chm=%s&chs=%sx%s&chf=%s&chd=%s&chds=%s&chxt=x,y&chbh=r,1,1&chxr=%s&chco=%s';
	const width = 632;
	const height = 474;

	protected $cht;
	protected $chm;
	protected $chf;
	protected $chd;
	protected $chds;
	protected $chxr;
	protected $chco;

	public function getPath () {
		return sprintf (
			self::path,
			$this->cht,
			$this->chm,
			self::width,
			self::height,
			$this->chf,
			$this->chd,
			$this->chds,
			$this->chxr,
			$this->chco
		);
	}
}
// class blockQuery extends MysqlStatement{
//
// 	protected $datiArr;
//
//
// 	public function create($id){
// 			$sql = sprintf("SELECT * FROM auth WHERE id = %d",$id);
// 			$result = $this->db->get_results ($sql);
// 			foreach($result[0] as $key => $value){
// 				$this->datiArr[$key] = $value;
// 			}
// 	}
//
// 	public function exist($vpass,$tpass = ''){
// 			$sql = sprintf("SELECT * FROM auth WHERE id = %d %s ",$this->datiArr['id'],
// 					"AND ".(empty($tpass) ? "pass" : "temp")." = PASSWORD('".$vpass."')");
// 			$result = $this->db->get_results ($sql);
// 			return $result;
// 	}
//
// 	public function modify($newpass){
// 			$sql = sprintf("UPDATE auth SET pass = PASSWORD('%s') , data = NOW(), temp = '' WHERE id = %d ",$newpass,$this->datiArr['id']);
// 			$result = $this->db->get_results ($sql);
// 	}
//
// 	public function temporary($tpass){
// 			$sql = sprintf("UPDATE auth SET temp = PASSWORD('%s') WHERE id = %d ",$tpass,$this->datiArr['id']);
// 			$result = $this->db->get_results ($sql);
// 	}
//
// 	public function getUser(){
// 		return $this->datiArr['user'];
// 	}
//
// 	public function getmail(){
// 		return $this->datiArr['email'];
// 	}
//
// 	public function getId(){
// 		return $this->datiArr['id'];
// 	}
//
// }
class noty{

	public function popup($text,$func,$time = 3000){
		$script = sprintf('noty({"text":"%s",
							  "layout":"center",
							  "type":"alert",
							  "animateOpen":{"height":"toggle"},
							  "animateClose":{"height":"toggle"},
							  "speed":500,
							  "timeout": %d,
							  "closeButton":false,
							  "closeOnSelfClick":true,
							  "closeOnSelfOver":false,
							  "modal":true,
							  "onClose" : function(){ %s; }
							  });',$text,$time,$func);
		return $script;
	}
	public function confirm($text,$func,$layout = 'bottom'){
		$script = sprintf('noty({
								"layout": "bottom",
								"text": "%s",
								"buttons": [{
											"type": "btn btn-primary", "text": "Ok", "click": function($noty){
												$noty.close(); noty({"force": true, "layout": "bottom", "text": "You clicked Ok button", "type": "success"});
												}
										  },
										  {
											"type": "btn btn-danger", "text": "Cancel", "click": function($noty){
												$noty.close(); noty({"force": true, "layout": "bottom", "text": "You clicked Cancel button", "type": "error"});
												}
										  }
										  ],
								"closable": false,
								"timeout": false
								});
								',$text);
		return $script;
	}
}
?>
