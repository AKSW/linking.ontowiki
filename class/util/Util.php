<?php
/**
 * Linking Extension Util.php file.
 * Error handling, logging, request parsing.
 * There are two clients: the module or the controller.
 * @author Christian Hippler (eof@gmx.net)
 */
class Util
{
	/** Module or Controller */
	private $_client;
	
	/**  Corresponding field from  Client. */
	private $_owApp;
	
	/**  Corresponding field from  Client. */
	private $_erfurt;
	
	/**  Corresponding field from  Client. */
	private $_privateConfig;
	
	/**  Corresponding field from  Client. */
	private $_request ;
	
	/**
	 * Resolve a prefix like rdf:type to its proper URI.
	 * Defined in the ini file.
	 * @see $this->parse_ini()
	 * @var array
	 */
	public $prefix = null;
	
	/**
	 * Array of arrays, each a possible combo box content. 
	 * Defined in the ini file.
	 * @see $this->parse_ini()
	 * @var array
	 */
	public $groups = array();
	
	/**
	 * Properties from which to extract default search keywords.
	 * Defined in the ini file.
	 * @see $this->parse_ini()
	 * @var array
	 */
	public $extract_properties = array();
	
	/**
	 * All possible contents of the combo box,
	 * one entry is choosen later.
	 * Defined in the ini file.
	 * @see $this->parse_ini()
	 * @var array
	 */
	public $import = array();
	
	/**
	 * Set in $this->choose_import_properties(),
	 * @var array
	 */
	public $import_properties = null;
	
	/**
	 * Extension base dir
	 * @var string
	 */
	public $context;
	
	/**
	 * Construct the Util instance.
	 */
	public function __construct(OntoWiki $OntoWiki,$Client,$root,Erfurt_App $Erfurt,$PrivConf,$Request)
	{
		$this->_owApp = $OntoWiki;
		$this->_client = $Client;
		$this->_privateConfig = $PrivConf;
		$this->context = $this->_owApp->config->urlBase . 'linking/';
		$this->_erfurt = $Erfurt;
		$this->_request = $Request;
		
		set_include_path(get_include_path() . PATH_SEPARATOR . $root . "/class");
		
		$this->_client->Resource = $OntoWiki->selectedResource;
		$this->_client->Model = $OntoWiki->selectedModel;
	
		$this->parse_ini();
	}
 	
 	/**
     * Parse the module's ini file.
	 * @throws Exception
	 */
	private function parse_ini()
	{
		$this->debug("PARSING THE INI FILE");
		
		$pc = $this->_privateConfig;
	
    	$this->prefix = $pc->prefix->toArray();
    	
    	// resolve extract properties
		$_ex =  $pc->extract_properties->toArray();
    	foreach ($_ex as $v) { 
    		$this->extract_properties []= $this->resolve_url($v);
    	}
    	
    	$this->debug("debug util->extract_properties:" . print_r($this->extract_properties,true));
    	
		// resolve groups
		$Groups = $pc->groups->toArray();
		foreach($Groups as $group => $memberlist) {	
			$this->groups[$group] = array();
			foreach ($memberlist as $member) {
				$this->groups[$group][$member]= $this->resolve_url($member);
			}
		}
		
		$this->debug("debug util->groups:" . print_r($this->groups,true));
		
    	//resolve import
		$I = $pc->import->toArray();
		foreach ($I as $_P => $_V) {
			
			if(strcasecmp('default', $_P) ==0 ) { 
				$this->import['default'] = $_V; 
			} else {
				$P = array();
				foreach($_V as $O => $G) {
					if(!isset($this->groups[$G])) 
						throw new Exception("ini parse error (not a group): $G");
					else
						$P[$this->resolve_url($O)] = $G;
				}
				$this->import[$this->resolve_url($_P)] = $P;
			}
		}
		
		$this->debug("debug util->import:".print_r($this->import,true));
		
		$this->debug("FINISHED PARSING INI");
	}
	
	/**
	 * Helps parsing the ini file.
	 * @param string $v (e.g. rdf:type)
	 * @throws Exception (e.g. parse_url($this->prefix['rdf'] . 'type') fails)
	 */
	private function resolve_url($v)
    {
    	list($p,$s) = explode(":", $v);
		$url = $this->prefix[$p] . $s ;
		if(parse_url($url) === FALSE ) {
			throw new Exception("ini parsing error (URL resolved from '$v' is not an URL):" . $url);
		}
		return $url;
    }
    
	/**
	 * Used in the clients.
	 * Extracts text from selectedResource.
	 */
	public function getKeywords()
	{
		$Mod = $this->_owApp->selectedModel;
		$Res = $this->_owApp->selectedResource;
		$ExProps = $this->extract_properties;
		$EfStore = $this->_erfurt->getStore();
		
		foreach ($ExProps as $ep) {
    		$Q = " SELECT ?epv  FROM <$Mod>  WHERE{ <$Res>  <$ep> ?epv }";
			$results = $EfStore->sparqlQuery($Q);
	    	
	    	if(count($results) > 0)
	    		return  $results[0]['epv'];
	    }
		return "";
    }
    
    /**
     * Called from Controller.
     * Check which import properties to use.
     * The combo contents for the link.
     * @see $this->parse_ini, ini file
     */
	public function choose_import_properties()
    {
    	$M = $this->_owApp->selectedModel;
    	$R = $this->_owApp->selectedResource;
    	$ip  = array();
    	
    	$results = $this->_erfurt->getStore()->sparqlQuery("SELECT ?p ?o WHERE { <$R> ?p ?o }");
    	$len = count($results);
    	for($i=0;$i<$len;$i++)
    	{
    		$p = $results[$i]['p'];
    		$o = $results[$i]['o'];
    		if(isset($this->import[$p]))
    			if(isset($this->import[$p][$o]))
    				$ip = array_merge($ip, $this->groups[$this->import[$p][$o]]);
    	}
    	
    	if(count($ip) == 0)
    		$ip = $this->groups[$this->import['default']];
    	
    	$this->import_properties = $ip;
    }
	
	/**
	 * require a csv list of Names
	 * @param unknown_type $varsname
	 */
	private function require_vars($varsname)
	{
		$vars = explode(',',$this->require_param($varsname));
		
		foreach($vars as $k => $v)
			if(!$this->isName($vars[$k] = trim($v)))
					$this->error_throw( $this->CONF['varname-error']);
		
		return $vars;
	}
	
	/**
	 * Throws if parse_url($_REQUEST[$n]) == false
	 * @param string $n
	 */
	public function require_url($n)
	{
		$v = $this->require_param($n);
		if(parse_url($v) == false) $this->error_throw("not an url: $n");
		return $v;
	}
	
	/**
	 * Throws if $f is not an uploaded file.
	 * @see $this->require_param
	 * @param string $f
	 */
	public function require_file($f)
	{
		$file = $_FILES[$f]['tmp_name'];
		if(is_file($file)) return $file;
		else $this->error_throw("parameter $f was expected to be a file");
	}
	
	/**
	 * True if p is set and not too long.
	 * @see $this->require_param
	 * @param unknown_type $p
	 * @param unknown_type $l
	 */
	public function require_param_limited($p,$l)
	{
		$v = $this->require_param($p);
		if(strlen($v) > $l) $this->error_throw("parameter $p was expected to be of length <= $l ");
		return $v;
	}
	
	/**
	 * $this->require_param(p) == v or throw.
	 * @see $this->require_param
	 * @param string $p
	 * @param string $v
	 */
	public function require_param_value($p,$v)
	{
		$n = $this->require_param($p);
		if($n != $v) $this->error_throw("parameter $p was expected to have value $v");
	}
	
	/**
	 * Throws if $this->isName($this->require_param($p)) is false.
	 * @param string $p
	 * @param string $e
	 */
	public function require_name($p, $e = '') 
	{
		if(!$this->isName($n = $this->require_param($p)))
			$this->error_throw( $e . ", parameter $p was expected to be a name ([a-z0-9'_']+) but ( $p = $n )");
		
		return $n;
	}
	
	/**
	 * if empty(optional_param($p,null)) throw.
	 * else return trim(optional_param($p)).
	 * @param string $p
	 * @param string $e
	 */
	public function require_param($p,$e=false)
	{
		$v = $this->optional_param($p,null);
		if(!empty($v)) return trim($v);
		$this->error_throw($e ? $e : "require_param $p failed , because $p is empty");
	}
	
	/**
	 *  $v = $this->_request->getParam($p);
	 *	if(null === $v)return $d;
	 *	else return $v;
	 * @param string $p
	 * @param string $d
	 */
	public function optional_param($p,$d='')
	{
		$v = $this->_request->getParam($p);
		if(null === $v)return $d;
		else return $v;
	}
	
	/**
	 * return null !== $this->_request->getParam($p);
	 * @param string $p
	 */
	public function isset_param($p)
	{
		return null !== $this->_request->getParam($p);
	}
	
	/**
	 * If param is set and onger than l throw.
	 * @param string  $p param
	 * @param string $l max length of $_REQUEST[$p]
	 * @param string $d return value if $p is not set in $_REQUEST
	 */
	public function optional_param_limited($p,$l,$d='')
	{
		$v = $this->optional_param($p,$d);
		if(strlen($v) < $l) return $v;
		$this->error_throw("parameter $p was expected to be of length <= $l");
	}
	
	/**
	 * returns true if  
	 * the first letter is alpha, remaining letters are alphanumerics or underscores
	 */
	public function isName($name)
	{
		if(!ctype_alpha($name[0])) return false;
	
		for($i = 0, $j = count($name); $i < $j; $i++)
		{
			$s = $name[$i];
			if(!(ctype_alnum($s) || $s == '_')) return false;
		}
		
		return true;
	}
	
	/**
	 *  return  round(microtime(true) * 1000 , 0); 
	 */
	public function millitime()
	{ 
		return  round(microtime(true) * 1000 , 0); 
	}
	
	/**
	 * log and die
	 * @param string $msg
	 */
	public static function error_die($msg)
	{
		$this->log("error_die:" . $msg);
		die("<p class='error'>" . $msg . "</p>");	
	}
	
	/**
	 * $this->log() an Exception.
	 * @param Exception $ex
	 */
	public function exception_log(Exception $ex)
	{
		$this->log($ex->getMessage());
		$this->log($ex->getTraceAsString());
	}
	
	/**
	 * log() then throw an exception
	 * @param string $msg
	 * @throws Exception by design
	 */
	public function error_throw($msg) 
	{
		$this->log($msg);
		throw new Exception($msg);
	}
	
	/**
	 * just calls $this->log()
	 * @param string $m
	 */
	public function debug($m)
	{
		$this->log($m);
	}
	
	/**
	 * uses error_log()
	 * @param string $m
	 */
	public function log($m)
	{
		$m = trim($m);
		if($m) error_log($m);
	}
	
	/**
	 * uses tidy
	 * @param xml string $xml
	 */
	public function log_xml($xml)
	{
		$config = array (
			'indent' => TRUE,
			'input-xml' => TRUE,
            'output-xml' => TRUE,
            'wrap' => 200
		);
		$tidy = tidy_parse_string($xml, $config, 'UTF8');
		$tidy->cleanRepair();
		$this->log($tidy);
	}
}