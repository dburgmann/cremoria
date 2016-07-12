$(document).ready(function() {
    $("a.msgOpen").fancybox({
        //Openening of Messagewindow in fancybox
       'hideOnContentClick': false,
       'padding': 0,
       'onComplete': function(){

           //Ajax function for sending messages
           $("#msgSend").click(function(){
                $.fancybox.showActivity();
                var receiver = $("input#receiver").val();
                var title = $("input#title").val();
                var text = $("textarea#text").val();
                var dataString = 'receiver='+ receiver + '&title=' + title + '&text=' + text;
                $.ajax({
                    type: "POST",
                    url: "http://daniel-burgmann.studiert.net/index.php/messages/send",
                    data: dataString,
                    success: function() {
                        $("input#receiver").val('');
                        $("input#title").val('');
                        $("textarea#text").val('');
                        $("div#msgInfo").html('Nachricht verschickt!');
                        $("div#msgInfo").removeClass('hidden');
                        $.fancybox.resize();
                        $.fancybox.hideActivity();                        
                    } 
                });                
                return false;
            })

           //Ajax function for deleting messages
           $(".msgDelete").click(function(){
                var id = $(this).attr('href');
                var dataString = 'id='+ id;
                $.ajax({
                    type: "GET",
                    url: "http://daniel-burgmann.studiert.net/index.php/messages/delete",
                    data: dataString,
                    success: function(result) {
                    } //TODO: feedback das nachricht gesendet! in result ist die ausgabe vom php script
                });
                $(this).parent().parent().fadeOut('fast');
                $("#msg"+id).fadeOut('fast');
                return false;
            })
        }
   });
});
