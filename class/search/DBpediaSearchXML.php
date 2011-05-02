<?php

/**
 * This is not for reuse outside DBpediaSearchEngine.
 * 
 * Parser Object for DBpedia Search.
 * This parses the xml produced by the exotic
 * DBpedia Query in the DBpediaSearchEngine class.
 * The format is a series of something like these rows:
 * 
 *<row>
 *	<col>trank</col>
 *	<col>erank</col>
 *	<col short=shorturl>longurl</col>
 *	<col>Label data here</col>
 *	<col>tagged html label</col>
 *</row>
 *
 * Columns 3 and 4 are used.
 * 
 * @see DBpediaSearchEngine
 */
class DBpediaSearchXML
{
	private $row;
	private $rows;
	
	public function __construct()
	{
		$this->rows = array();
		$this->incol = false;
	}
	
	public function start_element($parser,$tag,$att)
	{
		if(strtolower($tag) == 'column' )
		{
			$this->colno++;
			$this->incol = true;
			if($this->colno == 3) $this->row['shorturl'] = trim($att['shortform']);
		}
		else if(strtolower($tag) == 'row')
		{
			$this->colno = 0;
			$this->row = array();
			$this->row['label'] = "";
		}
	}
	
	public function end_element($parser,$tag)
	{
		if(strtolower($tag) == 'row') $this->rows []= $this->row;
		else if(strtolower($tag)=='column') $this->incol = false;
	}
	
	public function character_data($parser,$data)
	{
		if($this->incol)
		{
			if($this->colno == 3 ) $this->row['uri'] = trim($data);
			else if($this->colno == 4 ) $this->row['label'] .= $data;
		}
	}
	
	public function getResults()
	{
		return array('rows' => $this->rows ,'cols' => array('shorturl','url'));
	}
}