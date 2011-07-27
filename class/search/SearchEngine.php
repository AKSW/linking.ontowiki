<?php
/**
 * Base class for Search Engines.
 * These are wrappers arround endpoints.
 * The search method must return an array usable by the UI rendering code.
 * @author Christian Hippler (eof@gmx.net)
 */
abstract class SearchEngine
{
	protected $util;
	protected $url;
	protected $page;
	protected $ch;
	protected $httpheader;
	protected $kw;
	protected $kw_enc;
	protected $limit;
	
	/**
	 * curl_init();
	 */
	public function __construct(Util $u,$kw,$limit)
	{
		$this->ch  = curl_init();
		$this->util = $u;
		$this->kw = $kw;
		$this->kw_enc = urlencode($kw);
		if($limit > 100) $limit = 100;
		if($limit < 10) $limit = 10;
		$this->limit = $limit;
		$this->httpheader = array("Accept: application/rdf+xml");
	}
	
	/**
	 * curl_close();
	 */
	public function __destruct()
	{
		curl_close($this->ch);
	}
	/**
	 * return one of the endpoints possible
	 * @param Util $u 
	 * @param string $engine one of dbpedia,sindice,swoogle
	 * @param string $kw space separated keywords
	 * @param integer $limit max results
	 * @throws Exception
	 */
	public static function getEngine($u, $engine, $kw, $limit)
	{
		if($limit < 10 ) $limit = 10;
    	if($limit > 100) $limit = 100;
    	
		if($engine == 'sindice' ) {
			require_once 'search/SindiceSearchEngine.php';
			return new SindiceSearchEngine($u, $kw, $limit);
		} else if ($engine == 'dbpedia') {
			require_once 'search/DBpediaSearchEngine.php';
			return new DBpediaSearchEngine($u, $kw, $limit);
		} else if($engine == 'swoogle') {
			require_once 'search/SwoogleSearchEngine.php';
			return new SwoogleSearchEngine($u, $kw, $limit);
		} else {
			throw new Exception('no such engine ' . $engine);
		}
	}
	
	/**
	 * Interface function.
	 * Do the search must return 
	 * array('cols' => array('label','uri','link'), 'rows' => $rows);
	 */
	public abstract function search();
	
	/**
	 * Interface function.
	 * Check if endpoint online
	 */
	public abstract function probe();
	
	/**
	 * For use in subclasses , fetches an url with curl.
	 * @throws Exception HTTP_CODE != 200
	 */
	protected function loadURL()
	{
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->httpheader);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		$this->page = curl_exec($this->ch);
		$this->util->debug($this->page);
		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		
		if($code != 200) {
			$this->util->log($this->page);
			throw new Exception("HTTP_CODE $code when loading " . $this->url);
		}
	}
}