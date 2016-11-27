
jQuery(document).ready(function(){
   
    jQuery("div.save_as_tmpl a.wikilink2").click(function(){
       var jq = jQuery(this);
       var href = jq.attr('href');    
      var id = jQuery("#save_as_info input[name=save_as_page]").val() ;
      if(!id)   {
          alert("You have left the page id blank.")
          return false;
      }  
         else {
      href = href.replace(/SAVEAS_PAGE/i, id);
      jq.attr("href",href);  //   (jQuery("div.save_as_tmpl a.wikilink2").attr("href",href));
     }
    });
});