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
	  private $locks_set=false;
	  private $locked_fn;
    /**
     * Constructor
     */
    function __construct() {
	    
	     $this->locked_fn = $this->metaFilePath('locks','ser',false);
	}
	
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'openas_preprocess');
		$controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this, 'update_locked_pages');
    }

	
	/**
	    $_REQUEST['saveas_orig']: id of the original page which will be moved or copied to a new name
	    $_REQUEST['id'] or $INFO['id']: name of the new page
	*/
    function openas_preprocess(Doku_Event $event){
	   global $INFO;
         if(!$this->check_url()) return;
 
         if(isset($_REQUEST['openas'])) {
            $new_file = wikiFN($INFO['id']); 
            if(file_exists($new_file) && isset($_REQUEST['saveas_orig'])) {	
                 // handle relative links for both save and move		  
                  $this->update_relative_links($_REQUEST['id'],$_REQUEST['saveas_orig']) ;			  
                if($_REQUEST['openas'] == 'delete') {   
			      $this->get_backs($_REQUEST['id'],$_REQUEST['saveas_orig']) ;
                  $file = wikiFN($_REQUEST['saveas_orig']); 
                  if(file_exists($file)&&!$this->locks_set) @unlink($file);
              }
        }
        }


    }

	function  get_backs($id,$orig) {		     
		$backlinks = ft_backlinks($orig);
		
		 foreach($backlinks as $backlink) {	
			if(!checklock($backlink)) {
			lock($backlink);   
			}		 
		}
		
		$locked_array=$this->get_locked_array() ;
         $locks_found = false;  
		 foreach($backlinks as $link) {	
			if(checklock($link)) {
			   $this->locks_set = true;  
			   $locked_array[$link] = array($id,$orig);			   
			   continue;
			}		 			
			$this->resolve_ids($id,$orig,$link);
		    unlock($link);
		}
		if($this->locks_set) {
		    io_saveFile($this->locked_fn,serialize($locked_array));
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
		$metafn=$this->metaFilePath($orig_id,'orig');
	
		io_saveFile($metafn,$data);	

	    $data = preg_replace_callback('/\[\[(.*?)\]\]/', 
		     create_function(
			   '$matches',
			   'global $openas_debug,$orig_namespace;		              			   
			   $link_array = explode("|",$matches[1]);		   
			   $c_link = $link_array[0];						   
			   $link_text = isset($link_array[1]) ? $link_array[1] : "";  
			   resolve_pageid($orig_namespace,$c_link,$c_exists);
			   if($c_exists) {
				   return "[[:$c_link|$link_text]]";
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
		
		io_saveFile($metafn,$data);	
		
	    $data = preg_replace_callback('/\[\[(.*?)\]\]/', "openas_check_pageid",$data);
        
		$dir = dirname($current_wikifn);		
	    $fname = basename($current_wikifn, '.txt');				
		$path = "$dir/$fname" . $this->ext;	   	
		io_saveFile($path,$data);	
	}
  
  function metaFilePath($current_id, $ext='mvd', $numbered=true) {
    $namespace = 'openas:' . $current_id;	
	
	if($numbered) {
	    for($i=1; ; $i++) {		     
		    $metafnn = metaFN($namespace,  '.' . "$i.$ext");			
			if(!@file_exists($metafnn)) {
			    return $metafnn;
			}		
        }
	}
	
      $metafn = metaFN($namespace,  '.' . $ext);	
	   return $metafn;
  }

  /**
   		 $orig_id: deleted page 
		 $new_id: page to which $orig_id is being moved
		 $curid: the page which is being currently being updated as determined
		            from the backlinks array 

 */  
  function save_locked($new_id,$orig_id,$back_link) {
      $this->locked_array[$back_link] = array($new_id,$orig_id);
  }
  
  function get_locked_array() {
	 if(@file_exists($this->locked_fn)) {	         
		  return unserialize(io_readFile($this->locked_fn,false));
	 }
	 
	 return array();
  }
  
  function update_locked_pages(Doku_Event $event) {
     global $ID;
	 $locked_array=$this->get_locked_array() ;
	
	 if(isset($locked_array[$ID])) {
	    //  $this->ext = '.locked2.txt'; 
		  $new_id = $locked_array[$ID][0];
		  $orig_id = $locked_array[$ID][1];
		  $this->resolve_ids($new_id, $orig_id, $ID);
	 }
	 unset($locked_array[$ID]);
	 io_saveFile($this->locked_fn,serialize($locked_array));
	 if(empty($locked_array)) {
         $file = wikiFN($orig_id); 
         if(file_exists($file)) @unlink($file);
	  }
	 
  }
  
  function check_url() {
      global $INPUT,$USERINFO;
      
      if(empty($USERINFO)) {    
          $action = $INPUT->get->str('openas');
          if($action) {
              $db = DOKU_BASE;     
              $default =  "${db}lib/plugins/openas/images/404.jpg";
              $fourOhfour = $this->getConf("404");
              if(!$fourOhfour) $fourOhfour = $default;
              header("Location: $fourOhfour"); /* Redirect browser */          
              return false;
          }
      } 
      //id=tower2&saveas_orig=tower&openas=delete
      $newid = $INPUT->get->str('id');
      $saveas_orig = $INPUT->get->str('saveas_orig');      
      $action = $INPUT->get->str('openas'); 
      if(!$action) return false;
    
      resolve_pageid(getNS($newid), $newid, $exists);
      $newid = cleanID($newid);
      $auth_newid = auth_quickaclcheck($newid); 
      
      resolve_pageid(getNS($saveas_orig), $saveas_orig, $exists); 
      cleanID($saveas_orig);       
      $auth_origid = auth_quickaclcheck($saveas_orig); 
      
     // msg("$action original page:  $saveas_orig // " . $auth_origid);
     // msg("new page: $newid //". $auth_newid );

     switch($action)
     {
        case 'delete':                               
           if($auth_origid < AUTH_EDIT) {
               $nodel = $this->getLang('nodelete');
               msg("1. $nodel $saveas_orig");
               return false;
           }
            if($auth_newid < AUTH_CREATE) {
                $nocreate = $this->getLang('nocreate');
                msg("2. $nocreate $id");
                return false;
            }                                  
           return true;
           
        case 'save':
           if($auth_newid < AUTH_CREATE) {
               $nocreate = $this->getLang('nocreate');
               msg("3. $nocreate $newid");
               return false;
          }
          if($auth_origid < AUTH_EDIT) {
             $nocopy = $this->getLang('nocopy');                 
              msg("4. $nocopy $saveas_orig");
              return false;   
          }            
           return true;
           
        default:
           return false;
     }
     
     return false;
  } 
    
  function write_debug($what,$pre=false) {  
     if(is_array($what)) $what = print_r($what,true);
     if($pre) {
        msg('<pre>' . $what . '</pre>');
     return;  
     }   
     $handle = fopen("openas.txt", "a");
     fwrite($handle,"$what\n");
     fclose($handle);
  }

} //end of action class
?>
