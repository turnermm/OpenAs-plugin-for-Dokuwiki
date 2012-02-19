<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */

class action_plugin_openas extends DokuWiki_Action_Plugin {
         
      private $ext = '.txt';
    /**
     * Constructor
     */
    function __construct() {
	  $this->ext = '.dbg.txt';    	
	}
	
    function getInfo() {
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-05-28',
            'name'   => 'openas',
            'desc'   => 'Action Plugin for the openas Plugin',
            'url'    => 'http://www.dokuwiki.org/plugin:openas');
    }

    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'openas_preprocess');
    }

	
	/**
	    $_REQUEST['saveas_orig']: id of the original page which is being moved to a new name
	    $_REQUEST['id']: name of the new page
	*/
    function openas_preprocess(&$event){
	   global $ID;
	 
        if(isset($_REQUEST['openas']) && $_REQUEST['openas'] == 'delete') {
              $new_file = wikiFN($_REQUEST['id']); 			
              if(file_exists($new_file)) {			  
                  $this->update_relative_links($_REQUEST['id'],$_REQUEST['saveas_orig']) ;			  
			      $this->get_backs($_REQUEST['id'],$_REQUEST['saveas_orig']) ;
                  $file = wikiFN($_REQUEST['saveas_orig']); 
                  if(file_exists($file)) @unlink($file);
              }
        }


    }

	function  get_backs($id,$orig) {		     
		$backlinks = ft_backlinks($orig);
		 foreach($backlinks as $link) {		    
			$this->resolve_ids($id,$orig,$link);
		}
	}		
	
	/**
	  Determines the absolute link for any relative links in the original page
	  Then replaces relative links in moved page with the absolute links
	  $c_link in the  create_function will hold the absolute link for an id based on the 
	  resolved link from the original page

      @params
 	  $new_id: page to which original is being moved
	  $orig_id: page which is being moved and deleted 
	*/
	function update_relative_links($new_id, $orig_id) {
	    global $orig_namespace, $openas_debug;
		$orig_namespace = rtrim(getNS($orig_id),':') . ':';			
		$openas_debug = $this;
        $current_wikifn=wikiFN($new_id);
		$data = io_readFile($current_wikifn);
		$metafn=$this->metaFilePath($orig_id);
		 $this->write_debug($metafn);
		io_saveFile($metafn,$data);	

	    $data = preg_replace_callback('/\[\[(\..*?)\]\]/', 
		     create_function(
			   '$matches',
			   'global $openas_debug,$orig_namespace;		              			   
			   $link_array = explode("|",$matches[1]);		   
			   $c_link = $link_array[0];						   
			   $link_text = isset($link_array[1]) ? $link_array[1] : "";  
			   resolve_pageid($orig_namespace,$c_link,$c_exists);
			   if($c_exists) {
				   return "[[$c_link|$link_text]]";
			   }
			   return $matches[0];'
		     )	,
		$data
		);

		$dir = dirname($current_wikifn);		
	    $fname = basename($current_wikifn, '.txt');	
		$path = "$dir/$fname" . $this->ext;
		io_saveFile($path,$data);	
	    	
	}
	/**
	    This function updates all links in the backlink page which reference the
		page being deleted.  The updated links will now refer to the new page.
		The updated links will be absolute links.
	     @params:
		 $orig_id: deleted page 
		 $new_id: page to which $orig_id is being moved
		 $curid: the page which is being currently being updated as determined
		            from the backlinks array 
	*/
	function resolve_ids($new_id, $orig_id, $curid) {
        $current_wikifn=wikiFN($curid);
        if(!$current_wikifn) return;

		global  $current_ns;
		global $openas_debug;
		global $old_id;
		global $new_page_id;
		$old_id = $orig_id;
		ltrim($new_id,':');
		$new_page_id = ':' . $new_id;
		
		$current_ns = rtrim(getNS($curid),':') . ':';			
		
		if(!function_exists("openas_check_pageid")) {
		
		    function openas_check_pageid($matches) {		 
			    global $openas_debug,$old_id,$current_ns,$new_page_id;		  			   
			    $link_array = explode('|',$matches[1]);		   
			    $c_link = $link_array[0];
			
			    resolve_pageid($current_ns,$c_link,$c_exists);
			
			     if($c_exists) {   // found a link in the namespace of current page that relates to namespace of page being deleted (original page)		   	     
					  if(preg_match("/$old_id/",$c_link)) {  //is this link id found in the current page
						   list($name,$hash) = explode('#',$link_array[0]);
						   if(isset($hash)) {
							 $new_link = $new_page_id . "#" .$hash;
						   }
						   else $new_link = $new_page_id ;			        
						
						   return '[[' . $new_link . ']]';
					}

			    }
			
			   return '[[' . $matches[1] . ']]';
			}
		
		}
		$data = io_readFile($current_wikifn);
        $metafn = $this->metaFilePath($curid);
		 $this->write_debug($metafn);
		io_saveFile($metafn,$data);	
		
	    $data = preg_replace_callback('/\[\[(.*?)\]\]/', "openas_check_pageid",$data);
        
		$dir = dirname($current_wikifn);		
	    $fname = basename($current_wikifn, '.txt');				
		$path = "$dir/$fname" . $this->ext;	   	
		io_saveFile($path,$data);	
	}
  
  function metaFilePath($current_id) {
       $namespace = 'openas:' . $current_id;	
	   $metafn = metaFN($namespace,'.mvd');
	   return $metafn;
  }
  
  function write_debug($what) {   
     $handle = fopen("openas.txt", "a");
     if(is_array($what)) $what = print_r($what,true);
     fwrite($handle,"$what\n");
     fclose($handle);
  }

} //end of action class
?>
