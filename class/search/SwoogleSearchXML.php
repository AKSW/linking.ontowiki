<?php

/**
 * This is not for reuse outside SwoogleSearchEngine.
 * Parser Object for Swoogle Search.
 * @see SwoogleSearchEngine
 */
class SwoogleSearchXML
{
	private $row;
	private $rows;
	
	public function __construct()
	{
		$this->rows = array();
		$this->interm = false;
		$this->inlabel = false;
	}
	
	public function start_element($parser,$tag,$att)
	{
		if(strtolower($tag) == 'wob:semanticwebterm' )
		{
			$this->row = array();
			$this->interm = true;
			$this->row['uri'] = trim($att['rdf:about']);
		}
		else if(strtolower($tag) == 'swoogle:haslocalname')
		{
			if($this->interm)
			{ 
				$this->inlabel = true;
				$this->row['label'] = '';
			}
		}
	}
	
	public function end_element($parser,$tag)
	{
		if(strtolower($tag) == 'wob:semanticwebterm') 
		{
			$this->rows []= $this->row;
			$this->interm = false;
		}
		else if(strtolower($tag) == 'swoogle:haslocalname')
		{
			if($this->interm) $this->inlabel = false;
		}
	}
	
	public function character_data($parser,$data)
	{
		if($this->interm && $this->inlabel) $this->row['label'] .= $data;
	}
	
	public function getResults()
	{
		return $this->rows;
	}
}
