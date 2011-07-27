/**
 * javaScript methods that build the UI for
 * the linking extension.
 * @author Christian Hippler (e o f ät g m x n e t)
 */
linking = function() {
	/*
	 * for reference use in anon functions 
	 */
	var _ = this;
	
	function log(x) { 
		try { 
			console.log(x);
		} catch(y){}
		return x;
	}
	
	function list(o) {
		log("meberlisting " + o);
		for(var x in o) log(x);
	}
	
	this.NETWORK_ERROR = "Network error.";
	this.STORE_LINKS_SUCCESS = "Stored the selected links.";
	this.STORE_ERROR = "Error storing links.";
	this.RENDER_ERROR = "Error rendering results";
	
	this.RESULT_SYNTAX_ERROR = "AJAX httpcode=200 but data was bad.";
	
	this.COL_LABEL = 'Ressource';
	this.COL_URI = 'URI';
	this.COL_LINK = 'Eigenschaft';
	
	this.TT_LABEL = "The label of the URI from the URI column";
	
	this.TT_URI = "The URI which was found to be similar to the selected resource("
		   	+ $('#linking_resource').val() + ").";
	
	this.TT_LINK = "Click on the row in this column to show a combo box."
			+ " Select a property from the list that should be used"
			+ " to link the URI from the URI column with the selected"
			+ " resource(" + $('#linking_resource').val() + ")";
	
	this.TT_ADDLINKS = "Select multiple rows, for each row a triple"
	      + "(" 
	      	+ $('#linking_resource').val() 
	      	+ "," + this.COL_LINK 
	      	+ "," + this.COL_URI 
	      + ")"
	      + " is written to the active model.";
	
	this.NOTHING_FOUND_MSG = "Nothing concerning the keywords '" + $("#linking-kw").val() + "'" 
			+ " was found at the Endpoint " + $("#linking-ep").val() +"!";
	
	/** How long to wait on network requests before declaring an error. */
	this.AJAX_TIMEOUT = 5000;
	
	/** No linking properties were choosen but store button clicked */
	this.STORE_NOTHING_SELECTED = "Nothing is selected";
	
	/** Calls loadResults useing jquery-ui and dataTables to show results. */
	this.dt_formfind = function() {
		try {	
			var l = function (d){ return $('#linking-'+d).val(); };
			this.loadResults(l('ep'),l('limit'),null,null,l('kw'),this.dt_render_result);
		} catch(e) {
			log(e);
			alert(log(_.RENDER_ERROR + " " + e));
		}
	};
	
	/** Shows the results within a jQuery dataTable() within a jQuery dialog() */
	this.dt_render_result = function (_d) {
			
			start = new Date().getTime();
			dp = _d.properties;
			dr = _d.results;
			
			// don't pick up DOM from first query on second
			// this is bad/leaky  , $(...).remove() should work
			dtid = "ldt_datatable-" + start;
			dlgid = "ldt_dialog-" + start;
			
			function l(a,b) { // strip attributs 
				if(!b)b = ' ';
				i = a.indexOf(b);
				if(0 > i) return a;
				return a.substr(0, i);
			}
			function w(a,b) { // wrap in tag
				return "<" + a + ">" + b + "</" + l(a) + ">";
			}
			function com(a, i) { // combo (html select)
				if(undefined == a) return com([], 0);
				if(i < dp.length) {	
					a.push(w('option value=' + dp[i][1], dp[i][0]));
					return com(a,++i);
				} else {
					a.push(w('option value="" selected',""));
					return w('select style="min-width:70px;width:auto;"',a.join(''));
				}
			}
			function href(a) {
				return w("a target=_new href='" + a + "'", a);
			}
			function t() { 	// the table
				
				// colgroup and head
				var tl=_.TT_LABEL,cl=_.COL_LABEL,tu=_.TT_URI,cu=_.COL_URI,tli=_.TT_LINK,cli=_.COL_LINK;
				var sl="", su="", sli="";
				var _th = function(a,b,c) { 
					return w('th title="' + a + '" style="' + c + '" ', b); 
				};
				var _c = function(a){ 
					return w('col width="'+a+'" ',''); 
				};
				var h = '' // w('colgroup',_c("30%") + _c("50%") + _c("15%"))  
					  + w('thead', w('tr', _th(tl, cl, sl) + _th(tu, cu, su) + _th(tli, cli, sli)));
				
				// body
				var i = 0, trs = [], sel = com();
				do {
					trs.push(w('tr',w('td',dr[i][0]) + w('td',href(dr[i][1])) + w('td', sel)));
				} while(++i < dr.length);
				
				var b = w('tbody', trs.join(''));
				
				return w('table cellspacing=0 cellpadding=0 border=0 id=' + dtid, h + b );
			}
			
			var odiv = 'ldt_outer';
			$('#' + odiv).remove();
			$(document.body).append(w('div id=' + odiv, ''));
			
			// store button
			var sbt  = "<div><button id='ldt_store_bt'>Store Selection to Model</button></div>";
			
			// outer div for dialog() window inner div for protecting dataTable from window
			$('#'+odiv).append(w('div id=' + dlgid, sbt + w('div id=ldt_armor', t(_d.results,_d.properties))));
			
			// jQuery ui dialog (window)
			$('#' + dlgid).dialog({
				width : Math.round( 0.888 * $(document.body).width()),
				modal : true
			});
			
			// title
			shtml = '<span class="ui-dialog-titlebar-mini">Select URIs and Properties for Import</span>';
			$('.ui-dialog-titlebar-close').parent().append(shtml);
		
			// jQuery dataTable
			$('#' + dtid).dataTable({
				bDestroy : true,
				"bLengthChange": true, // how many rows per page
				"bFilter": true, // search field
				"bInfo": false
			});
			$("#" + dtid ).css({'width':'100%'});
			
			// pokemon minify defaults somewhat
			$("#" + dlgid ).css('padding','0');
			$("#" + dtid + " td").css({'vertical-align': 'center','padding' : '1px 2px'});
			
			// store selction to model
			$('#ldt_store_bt').click(function(){ _.storeSelection(dtid); });
			
			log("leave dt_render_result " + (new Date().getTime())- start);
	};
	/**
	 * $.ajax() with the params, call f on success.
	 * @param f what to do when the request works out 
	 */
	this.loadResults = function(ep,limit,refined,sim,kw,f)
	{
		m = $('#linking_model').val();
		r = $('#linking_resource').val();
		c = $('#linking_context').val();
		
		url = c + "ajax?cmd=find"
		+ "&model=" + escape(m) 
		+ "&resource=" + escape(r)
		+ "&ep=" + ep
		+ "&limit=" + limit 
		+ "&refine=" + refined 
		+ "&sim=" + sim 
		+ "&kw=" + escape(kw);
		
		this.ajax(url,function (data) {
			eval("var _data = " + data);
			p = _data.properties;
			r = _data.results;
			if(r.length == 0) {
				alert(_.NOTHING_FOUND_MSG);
				throw _.log(_.NOTHING_FOUND_MSG);
			}
			f(_data);
		});
	};
	
	/**
	 * Send triples in selection to server (LinkingController's ajaxAction).
	 */
	this.storeSelection = function(dtid) {
		
		function esc(x) { return encodeURIComponent(x); }
		
		// collect rows that have !null valued selects
		// links parameter format: link1,uri1,,link2,uri2 ...
		l = [];
		$('#' + dtid + ' tr').each(function(){
			p = $(this).find('select').val();
			if(undefined != p) // first tr has only ths
			{
				u = $(this).find('a').text();
				if('' !== p ){	
					l.push(esc(p) + ',' + esc(u));  
				}
			}
		});
		
		if(0 == l.length) { 
			alert(_.STORE_NOTHING_SELECTED);
			return ;
		}
		
		url = $('#linking_context').val() 
		+ "ajax?cmd=store-selected-links"
		+ "&resource=" + esc( $('#linking_resource').val())
		+ "&model=" + esc($('#linking_model').val())
		+ "&links=" + l.join(',,');
		
		_.ajax(url, function(data) {
				if(data == "ok") alert(_.STORE_LINKS_SUCCESS);
				else alert(_.STORE_ERROR + " " + data );
		});
	};
	
	this.ajax = function(url,f) {
		$.ajax ({
			url: url,
			async : false,
			timeout : _.AJAX_TIMEOUT,
			success : f,
			error : function() { throw _.NETWORK_ERROR + " " + url; }
		});
	};
	
	this.__test_dt_render_result = function ()
	{
		data = { results: [ 
					[ 'Al-Ghazali' , 'http://dbpedia.org/resource/Ghazali' , 0 , '' ]  , 
					[ 'Giordano Bruno' , 'http://dbpedia.org/resource/Giordano_Bruno' , 0 , '' ]  , 
					[ 'Thomas Aquinas' , 'http://dbpedia.org/resource/Thomas_Aquinas' , 0 , '' ]  , 
					[ 'Averroes Search' , 'http://dbpedia.org/resource/Averroes%27_Search' , 0 , '' ]  , 
					[ 'Averroes' , 'http://dbpedia.org/resource/8318_Averroes' , 0 , '' ]  , 
					[ 'Cesare Cremonini' , 'http://dbpedia.org/resource/Cesare_Cremonini_%28philosopher%29' , 0 , '' ]  , 
					[ 'RenÃ© Descartes' , 'http://dbpedia.org/resource/Ren%C3%A9_Descartes' , 0 , '' ]  , 
					[ 'Alhazen' , 'http://dbpedia.org/resource/Alhazen' , 0 , '' ]  , 
					[ 'Muhammad Farooq Khan' , 'http://dbpedia.org/resource/Dr_Muhammad_Farooq_Khan' , 0 , '' ]  , 
					[ 'Averroess Search' , 'sSearch' , 0 , '' ] 
					],
					 properties : [ 
					['owl:sameAs','http://www.w3.org/2002/07/owl#sameAs'],
					['skos:closeMatch','http://www.w3.org/2004/02/skos/core#closeMatch'],
					['rdf:type','http://www.w3.org/1999/02/22-rdf-syntax-ns#type']
					]
		};
		
		this.dt_render_result(data);
	};
};
