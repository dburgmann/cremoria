$(document).ready(function() {
   $("a.fbInline").fancybox();
});

function expand(id){
    var e = $("#"+id);   
    
    if($(e).hasClass('hidden')){
        $(e).removeClass('hidden');
    }
    else{
        $(e).addClass('hidden');
    }
    $.fancybox.resize();
    
}