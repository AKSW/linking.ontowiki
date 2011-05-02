<?php
/**
 * Search for URI at Sindice and DBpedia.
 * The results are displayed and can be linked
 * to the selectedResource. This is the Module Part.
 * It has an phtml in templates. It issues a script
 * (resources/linking.js) that talks to the controllers 
 * ajaxAction.
 *
 * @author Christian Hippler (eof@gmx.net)
 */
class LinkingModule extends OntoWiki_Module
{
	/**
	 * Helper class to catch common stuff.
	 * @var Util
	 */
	private $util;
	
	/**
	 * Initialize the Module
	 * @see OntoWiki_Module::init()
	 */
	public function init()
	{
		// setup the extension
		parent::init();
		require_once 'class/util/Util.php';
		$this->util = $u = new Util(
			$this->_owApp, 
			$this, 
			str_replace("LinkingModule.php", "", __FILE__),
			$this->_erfurt,
			$this->_privateConfig,
			$this->_request
		);
		
		// set variables the view needs
		$v = $this->view;
		$mu = $v->moduleUrl;
		
		if(isset($this->_privateConfig->develop)) $v->develop = $this->_privateConfig->develop;
		
		$v->context = $u->context;
		$v->Model = $this->Model;
		$v->Resource = $this->Resource;
		$v->default_keywords = $u->getKeywords();
		$v->headScript()->appendFile($mu  . 'resources/jquery.dataTables.min.js');
		$v->headLink()->appendStylesheet($mu . 'resources/jquery.dataTables.css');
		$v->headScript()->appendFile($mu . 'resources/linking.js');
	}
	
	/**
	 * Returns this modules title.
	 * @see OntoWiki_Module::getTitle()
	 */
	public function getTitle()
	{
		return $this->_privateConfig->module_title;
	}

	/**
	 * Renders to templates/linking/searchForm.phtml.
	 * @see OntoWiki_Module::getContents()
	 */
	public function getContents()
	{
		return $this->render("linking/searchForm");
	}
}