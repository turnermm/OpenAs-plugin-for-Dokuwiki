<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */

class action_plugin_openas extends DokuWiki_Action_Plugin {
    //store the namespaces for sorting
    var $sortEdit = array();
    var $helper = false;
    var $commit = false;

    /**
     * Constructor
     */
    function action_plugin_fckg_delete(){
    }

    function getInfo() {
        return array(
            'author' => 'Pierre Spring',
            'email'  => 'pierre.spring@liip.ch',
            'date'   => '2007-Mai-08',
            'name'   => 'fckg_delete',
            'desc'   => 'Action Plugin for the openas Plugin',
            'url'    => 'https://fosswiki.liip.ch/display/FCKW/Home');
    }

    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'openas_preprocess');
    }

    function openas_preprocess(&$event){

        if(isset($_REQUEST['openas']) && $_REQUEST['openas'] == 'delete') {
              $id = wikiFN($_REQUEST['id']); 
              if(file_exists($id)) {
                  $file = wikiFN($_REQUEST['saveas_orig']); 
                  if(file_exists($file)) @unlink($file);
              }
        }

    }


} //end of action class
?>
