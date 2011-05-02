<?php

require_once 'util/Util.php';
require_once 'search/SearchEngine.php';
/**
 * Search Engine for Sindice Semantic Web Search.
 * @author Christian Hippler (eof@gmx.net)
 */
class SindiceSearchEngine extends SearchEngine
{
	/**
	 * probe wether connection and data format are ok
	 * @see SearchEngine::probe()
	 */
	public function probe()
	{
		$this->url = "http://api.sindice.com/v2/search?q=" . urlencode($this->kw) . "&qt=term&page=1";	
		$this->loadURL();
		$out = $this->parse();
	}
	/**
	 * do the search
	 * @see SearchEngine::search()
	 */
	public function search()
	{
		$rows = array();
		$nresults = 0;
		for($i=1;$i<=10;$i++)
		{
			$this->url = "http://api.sindice.com/v2/search?q=" . urlencode($this->kw) . "&qt=term&page=$i";	
			$this->loadURL();
			$out = $this->parse();
			$m = count($out);
			
			if($m == 0) break; // no more results
			
			$nresults+= $m;
			$rows = array_merge($rows,$out); 
			if($nresults >= $this->limit) break; // enough results
		}
		
		$res = array('cols' => array('label','uri','link'), 'rows' => $rows);
		$this->util->debug(	print_r($res,true));
		return $res;
	}
	/**
	 * When the api changes this might need some adaption too.
	 */
	private function parse()
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadXML($this->page);
		$rows = array();
		
		// XXXPATH 
		foreach ($doc->documentElement->childNodes as $node)
			if(strcasecmp($node->nodeName, "Result") == 0) {	
				$row = array();
				
				foreach($node->childNodes as $xnode)
					if(strcasecmp($xnode->nodeName,"link") == 0)
						$row['uri'] =  $xnode->getAttribute("rdf:resource");
					else if(strcasecmp($xnode->nodeName, "dc:title") == 0)
						$row['label'] = $xnode->textContent;
				
				$rows []= $row;
			}
			
		return $rows;
	}
}