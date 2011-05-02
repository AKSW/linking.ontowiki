<?php
require_once 'search/SearchEngine.php';
/**
 * SearchEngine implementation  
 * for the Swoogle Web Service (http://swoogle.umbc.edu)
 * @author Christian Hippler (eof@gmx.net)
 */
class SwoogleSearchEngine extends SearchEngine
{
	/**
	 * Abstract in base class, gets URIs
	 * from Swoogle.
	 * @see SearchEngine::search()
	 */
	public function search($keywords, $limit)
	{
		if($limit < 10 ) $limit = 10;
		if($limit > 100) $limit = 100;
		
		$keywords = urlencode($keywords);
		
		$this->httpheader = array("Accept: application/rdf+xml");
		
		$rows = array();
		$nresults = 0;
		for($i=1;$i<$limit;$i+=10)
		{
			$base = "http://logos.cs.umbc.edu:8080/swoogle31/q?";
			$this->url = $base . "queryType=search_swt&searchStart=$i&searchString=$keywords&key=demo";   
			$page = $this->loadURL();
			$this->util->debug("page is :" . $this->page);
			$this->parse();
		}
	}
	
	/**
	 * result/xml parser,
	 * take a look at the raw xml (rdf) too,
	 * before reading this.
	 * @param xml $page
	 */
	private function parse()
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadXML($this->page);
		$rows = array();
		foreach ($doc->documentElement->childNodes as $node)
		{
			if(strcasecmp($node->nodeName, 'swoogle:QueryResponse') == 0 )
			{
				foreach($node->childNodes as $xnode)
				{
					if(strcasecmp($xnode->nodeName, 'swoogle:hasSearchTotalResults')==0)
					{
						$this->util->debug("total:" . $xnode->textContent );	
					}
					else if(strcasecmp($xnode->nodeName, 'swoogle:hasSearchStart') == 0)
					{
						$this->util->debug("start:" . $xnode->textContent );
					}
					else if(strcasecmp($xnode->nodeName, 'swoogle:hasResult')==0)
					{
						
					}
				}
			}
		}
	}
	
	
	
	
}