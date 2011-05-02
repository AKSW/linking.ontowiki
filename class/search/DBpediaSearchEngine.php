<?php 
require_once 'util/Util.php';
require_once 'search/SearchEngine.php';
/**
 * SearchEingine for DBpedia Web Service.
 * It uses some exotic SPARQL to retrive results.
 * @author Christian Hippler (eof@gmx.net)
 */
class DBpediaSearchEngine extends SearchEngine
{
	/**
	 * probe wether connection and data format are ok
	 * @see SearchEngine::probe()
	 */
	public function probe()
	{
		$this->search();
	}
	/**
	 * Interface contract member function.
	 * @see SearchEngine::search()
	 */
	public function search()
	{
		$u = $this->util;
		$endpoint = 'http://dbpedia.org/sparql?';
		$S = $this->getDBpediaSelect($this->kw,$this->limit);
		
		$base = $endpoint;
		$query = $S;
		$url = 	 $base  . "query="  . urlencode($query) ;
		
		$url .=  "&format=" . urlencode('text/xml').
			     "&default-graph-uri=" . urlencode('http://dbpedia.org') . 
			     "&debug=on&timeout=10000";
		
		$this->url = $url;
		$this->httpheader =  array('Accept: application/sparql-results+xml');
		$this->loadURL();
		
		$res = $this->parse();
		
		return array ( 'cols' => array('label', 'uri'), 'rows' => $res['rows'] );
	}
	
	/**
	 * Exotic SPARQL to use the fulltext-index of
	 * DBpedia powered by Openlink Virtuoso. 
	 * @param string $keyword
	 * @param integer $limit
	 */
	private function getDBpediaSelect($keyword,$limit)
	{
		$keyword = str_replace("\"", "", $keyword);
		$keyword = str_replace("'","",$keyword);
		
		if( $limit < 10 ) $limit = 10;
		if( $limit > 100) $limit = 100;
		
		return  
		" select(<sql:s_sum_page>( <sql:vector_agg> (<bif:vector> (?c1, ?sm)), <bif:vector> ('$keyword')))  as ?res "
		." where" 
		." {{" 
		.   " select (<SHORT_OR_LONG::>(?s1)) as ?c1, (<sql:S_SUM> ( <SHORT_OR_LONG::IRI_RANK> (?s1),<SHORT_OR_LONG::>(?s1textp),<SHORT_OR_LONG::>(?o1), ?sc ) ) as ?sm"
		.	" where { ?s1 ?s1textp ?o1 . ?o1 bif:contains  '\"$keyword\"'  option (score ?sc)  . }" 
		.	" order by desc (<sql:sum_rank> ((<sql:S_SUM>(<SHORT_OR_LONG::IRI_RANK> (?s1),<SHORT_OR_LONG::>(?s1textp),<SHORT_OR_LONG::>(?o1), ?sc ))))"
		.	" limit $limit  offset 0" 
		."}}";
	}
	
	/**
	 * XML parsing, Called from serach[2] with that xml.
	 * @param unknown_type $xml
	 */
	private function parse()
	{
		$xml = $this->page;
		require_once('search/DBpediaSearchXML.php');
		$parser = xml_parser_create();
		$callback = new DBpediaSearchXML();
		xml_set_object($parser,$callback);
		xml_set_element_handler($parser,'start_element','end_element');
		xml_set_character_data_handler($parser,'character_data');
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,false);
		
		// $xml is the whole document ,true says 'last chunk' ( because there is just one here )
		xml_parse($parser,$xml,true);
		
		return $callback->getResults();
	}
}