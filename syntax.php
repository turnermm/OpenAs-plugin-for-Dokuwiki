<?php
/**
 * Plugin OpenAs.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
//ini_set("display_errors","1");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_openas extends DokuWiki_Syntax_Plugin {

    function getInfo() {
        return array('author' => 'Myron Turner',
                     'email'  => 'turnermm02@shaw.ca',
                     'date'   => '2011-05-29',
                     'name'   => 'OpenAs Plugin',
                     'desc'   => 'File utility for moving and saving-as, and creating new pages from templates',
                     'url'    => 'http://www.dokuwiki.org/plugin:openas');
    }

    function getType() { return 'substition'; }
    function getSort() { return 80; }
    function getPType() { return 'block'; }
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~SaveAS>.*?~~',$mode,'plugin_openas');
        $this->Lexer->addSpecialPattern('~~OpenAS>.*?~~',$mode,'plugin_openas');
        $this->Lexer->addSpecialPattern('~~MoveTO>.*?~~',$mode,'plugin_openas');
    }

    function handle($match, $state, $pos, &$handler) {
         global $ID;
         $actions = array('SaveAS' => 'save', 'MoveTO' => 'delete');
         $file = wikiFN($ID);
         list($type,$name,$newpagevars) = explode('>',(trim($match,'~')));
           
               
         if($type == 'SaveAS' || $type == 'MoveTO') {
           $action = $actions[$type];
           $newfile = wikiFN($name);           
           $contents = file_get_contents($file);
           $contents = preg_replace('/~~' . $type .'.*?~~/',"",$contents,1);
           io_saveFile($newfile,$contents);   
           $wikilink = html_wikilink("$name?saveas_orig=$ID&openas=$action");     
           $match = "Click on this link to open your renamed page:<br /> $wikilink";  
         }
         else if($type == 'OpenAS') {
           list($id,$template) = explode('#',$name);
           $newpagevars = urlencode($newpagevars); 
           $match = 'Click on this link to open your page:<br />' .
             html_wikilink("$id?do=edit&rev=&newpagetemplate=:pagetemplates:$template&newpagevars=$newpagevars");     
         }
         
         return array($state,$match);
    }

    function render($mode, &$renderer, $data) {
        list($state,$match) = $data;
       
        if($mode == 'xhtml'){
            $renderer->doc .= '<div class="save_as">' . $match . '</div>';
            return true;
        }
        return false;
    }

  function write_debug($what) {   
     $handle = fopen("saveas.txt", "a");
     if(is_array($what)) $what = print_r($what,true);
     fwrite($handle,"$what\n");
     fclose($handle);
  }
}
