$(document).ready(function() { // This script need be improved
 $(window).load(function () {
  my_protocol = location.protocol;
  my_pathname=location.pathname;
  work_path = my_pathname.substr(0,my_pathname.indexOf('/courses/'));
  $.ajax({
    contentType: "application/x-www-form-urlencoded",
    beforeSend: function(content_object) {},
    type: "POST",
    url: my_protocol+"//"+location.host+work_path+"/main/glossary/glossary_ajax_request.php",
    data: "glossary_data=true",
    success: function(datas) {
      if (datas.length==0) {
        return false;
      }
      data_terms=datas.split("[|.|_|.|-|.|]");
      for(i=0;i<data_terms.length;i++) {
        specific_terms=data_terms[i].split("__|__|");
        var real_term = specific_terms[1];
        var real_code = specific_terms[0];
        $('.quiz_content_actions, .media_scroll, .quiztitle').highlight(real_term, false, real_code);
      }
    //mouse over event
    $(".quiz_content_actions .glossary-ajax, .quiztitle .glossary-ajax, .media_scroll .glossary-ajax").mouseover(function(){
      var div_content_id="div_content_id";
      if ($("#"+div_content_id).length > 0) {
            $("#"+div_content_id).remove();
      }

      var text_box = $(this).text();
      $("<div style='display:none;' title='"+ text_box +"' id="+div_content_id+">&nbsp;</div>").insertAfter(this);
	  
      notebook_id = $(this).attr("name");
      data_notebook = notebook_id.split("link");
      my_glossary_id=data_notebook[1];
      $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(content_object) {
          //$("div#"+div_content_id).html("<img src="+my_protocol+"//"+location.host+work_path+"/main/inc/lib/javascript/indicator.gif />");
        },
        type: "POST",
        url: my_protocol+"//"+location.host+work_path+"/main/glossary/glossary_ajax_request.php",
        data: "glossary_id="+my_glossary_id,
        success: function(datas) {
          $("div#"+div_content_id).show();
          $("div#"+div_content_id).css({
                'width':'500px', 
                'border':'6px solid #525252', 
                'min-height':'120px',
                'padding':'0px 10px',
                '-moz-border-radius': '5px',
                '-webkit-border-radius': '5px',
                'border-radius': '5px',
                'z-index':'100000',
                'font-size':'12px',
                'font-family':'Verdana',
                'color':'#000',
                'overflow':'auto',
                'font-weight':'normal'
          });               
          $("div#"+div_content_id).center();          
          var btn_close = '<div class="btn-glossary-close" style="text-align:right;background-color: #E8E8E8;margin:-5px -10px;padding:0px;"><table width="100%"><tr><td align="left" style="font-weight:bold;">'+text_box+'</td><td align="right" width="50px;"><a href="javascript:void(0)" onclick="closeGlossaryPopup(\'div_content_id\');" style="padding-right:0px !important;"><img src="../img/close.gif" border="0" /></a></td></tr></table></div>';
          $("div#"+div_content_id).html(btn_close+datas);          
        }
      });
    });
   }
  });
 });
});

function closeGlossaryPopup(obj) {
    $("div#"+obj).hide("slow");    
    $("div#"+obj).remove();
}

jQuery.fn.center = function () {
    this.css("position","fixed");
    this.css("_position","absolute");
    this.css("top", "30%");
    this.css("left", "35%");
   
    return this;
}