<?php
require_once 'search/SearchEngine.php';
/**
 * SearchEngine implementation  
 * for the Swoogle Web Service (http://swoogle.umbc.edu)
 * @author Christian Hippler (eof@gmx.net)
 */
class SwoogleSearchEngine extends SearchEngine
{
	protected $URL_PREFIX = "http://logos.cs.umbc.edu:8080/swoogle31/q?";
	// only 25 results with demo key
	protected $SWOOGLE_KEY = "demo";
	protected $LOOP_START = 1; 	// i =
	protected $LOOP_LIMIT = 21; // i <=
	protected $LOOP_INC = 10; 	// i +=
	
	public function probe()
	{
		$this->url = $this->URL_PREFIX . 
		"queryType=search_swt&searchStart=1&searchString=" . $this->kw_enc .
		"&key=" . $this->SWOOGLE_KEY;   
		$page = $this->loadURL();
		$this->parse();
	}
	
	/**
	 * Abstract in base class, gets URIs
	 * from Swoogle.
	 * @see SearchEngine::search()
	 */
	public function search()
	{
		$u = $this->util;
		$rows = array();
		$nresults = 0;
		for($i=$this->LOOP_START; $i<=$this->LOOP_LIMIT; $i+=$this->LOOP_INC)
		{
			$u->debug("search run $i");
			$this->url = $this->URL_PREFIX . 
			"queryType=search_swt&searchStart=$i" .
			"&searchString=" . $this->kw_enc . "&key=" . $this->SWOOGLE_KEY;   
			$page = $this->loadURL();
			if(($m = count($out = $this->parse())) == 0) break; // no more results
			$nresults += $m;
			$rows = array_merge($rows, $out); 
			if($nresults >= $this->limit) break; // enough results
		}
		$res = array('cols' => array('label', 'uri', 'link'), 'rows' => $rows);
		$u->debug(print_r($res,true));
		return $res;
	}
	
	/**
	 * result/xml parser,
	 * take a look at the raw xml (rdf) too,
	 * before reading this.
	 * @param xml $page
	 */
	private function parse()
	{
		$xml = $this->page;
		require_once('search/SwoogleSearchXML.php');
		$parser = xml_parser_create();
		$callback = new SwoogleSearchXML();
		xml_set_object($parser,$callback);
		xml_set_element_handler($parser,'start_element','end_element');
		xml_set_character_data_handler($parser,'character_data');
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,false);
		
		// $xml is the whole document ,true says 'last chunk' ( because there is just one here )
		xml_parse($parser,$xml,true);
		
		return $callback->getResults();
	}
	
	
	
	
}