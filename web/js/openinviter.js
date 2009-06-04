$(document).ready(function() {
    
    if($("#openInviter_email").val() == "Your Email/Username Here")
    {
      $("#openInviter_email").css("color","#CCCCCC");
    }
    
    $("#openInviter_email").click(function () { 
      if($(this).val() == "Your Email/Username Here")
          $(this).val(''); 
      $(this).css("color","#000000");
    });
    
    $("#openInviter_email").blur(function () { 
      if($(this).val() == "")
      {
        $(this).val("Your Email/Username Here"); 
        $(this).css("color","#CCCCCC");
      }
    });
});


function toggleAll(element) 
{
    var form = document.forms.openinviter, z = 0;
    for(z=0; z<form.length;z++)
      if(form[z].type == 'checkbox')
        form[z].checked = element.checked;
        
}