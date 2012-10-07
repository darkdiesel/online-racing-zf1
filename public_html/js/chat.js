$(document).ready(function(){
    jQuery(function($){
        $("#chat_messages_box .chat_message_box .chat_mesage_nickname .nick").click( function() {
            $("#chat #userChat #messageTextArea").append('[i]'+$(this).html()+'[/i], ');
        });
     
        $("#chat #userChat #reset").click(function(){
            $("#chat #userChat #messageTextArea").empty();
        });
        
        $("#chat #userChat #messageTextArea").focus(function(){
            checkMes();
        });
        
        $("#chat #userChat #messageTextArea").keyup(function(){
            checkMes();
        });
        
        $("#chat #userChat #messageTextArea").keydown(function(){
            checkMes();
        });
        
        function checkMes()
        {
            $("#chat #chat_textarea_count").html(500 - $("#chat #messageTextArea").val().length);
        }
    });
});