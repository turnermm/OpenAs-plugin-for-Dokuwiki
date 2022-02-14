jQuery (function() {   
    jQuery("div.save_as_tmpl a.wikilink2").click(function(){
       var jq = jQuery(this);
       var href = jq.attr('href');    
      var id = jQuery("#save_as_info input[name=save_as_page]").val() ;
      if(!id)   {
          alert("You have left the page id blank.");
          return false;
      }  
    else {
      href = href.replace(/SAVEAS_PAGE/i, id);
      jq.attr("href",href);  //   (jQuery("div.save_as_tmpl a.wikilink2").attr("href",href));
     }
     var repl_str = "";
    jQuery( "input.open_as_repl, textarea.open_as_repl" ).each( function( index, element ){
          if(this.value) {              
              repl_str += '@' + this.id + '@,' + this.value + ';'
            }
    });
     
        if((href.match(/newpagevars=%40/) || href.match(/newpagevars=\s*$/)) && repl_str) {            
            href += '%3B' + encodeURIComponent(repl_str);
            href = href.replace(/%20/g,'+');           
            jq.attr("href",href);              
        }
            
    });
    
});
