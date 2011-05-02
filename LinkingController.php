<?php
/**
 * Search for URI at Sindice and DBpedia.
 * The controller part.
 * Answers some AJAX requests.
 * @author Christian Hippler (eof@gmx.net)
 */
class LinkingController extends OntoWiki_Controller_Component  
{
	/**
	 * Methods and Fields that are also use by Module.
	 * Like ini parsing.
	 * @var Util
	 */
	private $util;
	/**
	 * Initialize the Controller.
	 * @see OntoWiki_Controller_Component::init()
	 */
	public function init()
	{
		parent::init();
		require_once 'class/util/Util.php';
		$this->util = new Util(
			$this->_owApp,
			$this,
			str_replace("LinkingController.php","", __FILE__),
			$this->_erfurt,
			$this->_privateConfig,
			$this->_request			
		);
	}
	/**
	 * There are two Request types handled here:
	 * searching for link candidates and storing links to the model.
	 * The storing happens after user consent the UI for which 
	 * is in resources/linking.js.
	 */
	public function ajaxAction()
	{
		require_once("util/Util.php");
    	
    	$u = $this->util;
    	$cmd = $u->require_param("cmd");
    	
    	if($cmd == 'find') {
    		
    		$this->request_find($u);
    		
    	} else if ($cmd == 'store-selected-links') {
    		
    		$this->request_storeSelectedLinks();
    	}
	}
	/**
	 * Search at one of the endpoints.
	 * calls this->searchEndpoints,
	 * calls this->choose_import_properties,
	 * renders results,
	 * exits.
	 */
	private function request_find()
	{
		try {
    		$u = $this->util;
    		$u->debug("enter request_find");
    		
    		$results = $this->request_find2();
    		
    		$u->choose_import_properties();
    		
    		$this->view->util = $u;
			$this->view->import_properties = $u->import_properties;
    		$this->view->results = $results;
			$this->view->open = true;
			$this->sendBodyExit($this->view->render('linking/results.phtml'));
		
		} catch(Exception $e) {
			
    		$u->debug("search Endpoints failed");
    		$this->sendBodyExit("Error searching :" . $e->getMessage() );
    	}
    }
    /**
	 * Search at one of the endpoints.
	 */
    private function request_find2()
    {
    	$u = $this->util;
    	// sindice,dbpedia,all
    	$ep = $u->require_param('ep');
    	// max number of results
    	$limit = $u->require_param('limit');
    	
    	// key words for the search
    	if(strlen($kw = $u->optional_param('kw','')) == 0) { 
    		$kw = $u->getKeywords();
    	}
    	if(count(trim($kw))==0) {
    		throw new Exception("no keywords given or found");
    	}
    	require_once 'search/SearchEngine.php';
    	$engine = SearchEngine::getEngine($u, $ep, $kw, $limit);
    	try {
    		$engine->probe();
    	} catch(Exception $ex) {
    		throw new Exception("$ep failed to probe");
    	}
    	return $engine->search();
    }
	/**
	 * Send a response without the complete OntoWiki UI,
	 * i.e. caller is a script not the user agent.
	 * This function clears and sets the body then calls exit().
	 * @param string $body string to send
	 */
	private function sendBodyExit($body)
	{
		$this->_response->clearBody();
        $this->_response->setBody($body);
        $this->_response->sendResponse();
		exit;
	}
	/**
	 * Import the links selected by the user into the selectedModel
	 * @param Util $u
	 */
 	private function request_storeSelectedLinks()
    {
    	try 
    	{
    		$u = $this->util;
    		require_once("Erfurt/Store.php");
	    	$S = $this->_erfurt->getStore();
	    	$M = $u->require_url('model');
	    	$R = $u->require_url('resource');
	    	
	    	$links = trim($u->require_param('links'));
	    	if(1 != preg_match('/^[^,]+,[^,]+(,,[^,]+,[^,]+)*$/', $links)) {
	    		throw new Exception ('links parameter seems faulty');
	    	}
	    	$L = explode(",,", $links);
	    	$u->debug("links:" . $links);
	    	
	    	$len = count($L);
	    	for($i=0;$i<$len;$i++)
	    	{
	    		list($P , $O) = explode(",",$L[$i]);
	    		if(FALSE === parse_url($P)) throw new Exception("property is no url : $P");
	    		if(FALSE === parse_url($O)) throw new Exception("object is no url : $O");
	    		// it's important that the object is such array(..)
	    		$S->addStatement($M, $R, $P,  array( 'type' => 'uri', 'value' => $O ));
	    		$u->debug("added statement $R $P $O");
	    	}
	    	$this->sendBodyExit("ok");
    	}
    	catch(Exception $e)
    	{
    		$u->debug("storing selected links failed failed\n" . $e->getTrace() );
    		$this->sendBodyExit("Error searching :" . $e->getMessage() );
    	}
    }
}