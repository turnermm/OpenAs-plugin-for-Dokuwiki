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
    var $labels = array();
    function getType() { return 'substition'; }
    function getSort() { return 60; }
    function getPType() { return 'block'; }
    function connectTo($mode) {
      
        $this->Lexer->addEntryPattern('~~OpenAsVarsStart~~', $mode, 'plugin_openas');
        $this->Lexer->addPattern('~~OpenAsVar>TAreaOpen~~', $mode, 'plugin_openas');
        $this->Lexer->addPattern('~~OpenAsVar>TAreaClose~~', $mode, 'plugin_openas');
        $this->Lexer->addPattern('~~OpenAsVAR>.*?~~','plugin_openas');
        $this->Lexer->addPattern('~~OpenAsNUM>.*?~~','plugin_openas');        
        $this->Lexer->addSpecialPattern('~~SaveAS>.*?~~',$mode,'plugin_openas');
        $this->Lexer->addSpecialPattern('~~OpenAS>.*?~~',$mode,'plugin_openas');
        $this->Lexer->addSpecialPattern('~~MoveTO>.*?~~',$mode,'plugin_openas');
    }

    function postConnect()
    {
      $this->Lexer->addExitPattern('~~OpenAsVarsClose~~', 'plugin_openas');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
         global $ID;
 
         $actions = array('SaveAS' => 'save', 'MoveTO' => 'delete');
         $which = array('SaveAS'=>'saved as', 'MoveTO' => 'renamed');
         $file = wikiFN($ID);
         list($type,$name,$newpagevars) = explode('>',(trim($match,'~')));
         $name=trim($name);  
         $labels = $this->getConf('labels');
         if($labels == 'none') {
             $this->labels['open'] = "";
             $this->labels['close'] = "";      
         }
          else {         
             $labels = trim($labels);
             $ltype = $labels[0];  
             $this->labels['open'] = "<$ltype>";
             $this->labels['close'] = "</$ltype>";
         }             
    
         switch ($state) {
              case DOKU_LEXER_ENTER : return array($state, '');
              case DOKU_LEXER_UNMATCHED :  return array($state, $match);
              case DOKU_LEXER_MATCHED :           
                  if($type == 'OpenAsNUM') {
                      return array($state,"$type:$name"); 
                 }             
                      return array($state, $name);
              case DOKU_LEXER_EXIT :       return array($state, '');
              case DOKU_LEXER_SPECIAL :
         if($name[0] != ':') $name = ":$name";

         if($type == 'SaveAS' || $type == 'MoveTO') {
           $action = $actions[$type];
           $newfile = wikiFN($name);           
           $contents = file_get_contents($file);
           $contents = preg_replace('/~~' . $type .'.*?~~/',"",$contents,1);
           io_saveFile($newfile,$contents);   
           $wikilink = html_wikilink("$name?saveas_orig=$ID&openas=$action");   
           $msg = "$ID " . $this->getLang('will_save') .   $which[$type] ."  $name.<br />";  
           $match = $msg  .  $this->getLang('open_wl') . "<br /> $wikilink";  
         }
         else if($type == 'OpenAS') {
           list($id,$template) = explode('#',$name);
           $newpagevars = urlencode($newpagevars); 
           $match = $this->getLang('open_page'). '<br />' .
            html_wikilink("$id?do=edit&rev=&newpagetemplate=$template&newpagevars=$newpagevars");     
         }
         return array($state,$match);
    }
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        list($state,$match) = $data;
       
        if($mode == 'xhtml'){
            switch ($state) {
            case DOKU_LEXER_ENTER : 
                    $renderer->doc .= '<div id="openasrepl" class="openasrepl"><form id="open_as_var">' ."\n";
                    break;
            case DOKU_LEXER_UNMATCHED :  
                   $text = $renderer->_xmlEntities($match);
                   $text = preg_replace('/\*\*(.*?)\*\*/ms', $this->labels['open'] ."$1" .$this->labels['close'] ,$text);
                   $text = preg_replace ("#\s*\\\\\s*#",'<br />',$text);              
                   $renderer->doc .=  $text; 
            break;
            case DOKU_LEXER_MATCHED :
               if(preg_match('/^TAreaOpen:(.*?)$/',$match,$matches)) { 
                   $renderer->doc .= "<textarea rows = '4' cols = '60' name = '$matches[1]' id = '$matches[1]' class='open_as_repl'>\n";
               }
               else if($match == 'TAreaClose') {
                   $renderer->doc .= "</textarea>\n";
               } 
               else {
                   $size = "24";                 
                   if(preg_match("/^OpenAsNUM:(.*?)$/",$match,$matches)) {
                       $match = $matches[1];  
                       $size = "10";
                   }                 
                   $renderer->doc .= "<input type='text' size='$size' name= '$match'  id ='$match' class='open_as_repl'>&nbsp;\n";
                }   
               break;
            case DOKU_LEXER_EXIT :  $renderer->doc .= '</form></div>' . "\n";
                   break;
            case DOKU_LEXER_SPECIAL :            
            $class= 'save_as'; 
                if(preg_match("/SAVEAS_PAGE/i",$match)) {
                     $id = $this->labels['open'] . $this->getLang('pageid')  .$this->labels['close'] ;
                 $renderer->doc .= '<div class="save_as_info">' . "\n";
                     $renderer->doc .= '<form id="save_as_info">' . $id . ' <input type="text" size="24" name="save_as_page" id ="save_as_page">' . "\n";
                 $renderer->doc .= "</form></div>\n";
                 $class ='save_as_tmpl';
            }
            $renderer->doc .= '<div class="'. $class .'">' . $match . '</div>';
            return true;
        }
    }
        return false;
    }

  function  getReplacementAttributes($name) {
      
  }
  function write_debug($what) {   
     $handle = fopen("saveas.txt", "a");
     if(is_array($what)) $what = print_r($what,true);
     fwrite($handle,"$what\n");
     fclose($handle);
  }
}
