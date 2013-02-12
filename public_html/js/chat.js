$(document).ready(function(){
    jQuery(function($){
        $("#chat #userChat #reset").click(function(){
            $("#chat #userChat #messageTextArea").empty();
            $("#chat #userChat #messageTextArea").val("");
            checkMes();
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
        
        // send message to server script
        $("#chat #userChat #submit").click(function(){
            set_chat_message();
        });
        
        function set_chat_message(){
            var message_text = $('#chat #messageTextArea').val();
            
            if (message_text.length==0) {
                alert("Нельзя отправлять пустые сообщения!");
            } else {
                $("#chat #chat_ajax_img").fadeIn(100);
                $.ajax(
                {
                    url: '/chat/addmessage',
                    type: 'POST',
                    data:
                    {
                        'ajax_action': 'add_message',
                        'message_text': message_text
                    },
                    dataType: 'json',
                    success: function (result)
                    {
                        $('#chat #messageTextArea').empty(); // очищаем поле ввода
                        $('#chat #messageTextArea').val("");
                        checkMes();
                        // сразу же подгружаем отправленное сообщение в чат
                        get_chat_messages();
                    }
                });
            }
            
            return false;
        }
        
        function get_chat_messages(){
            if ($('#chat #userChat #block_msg').val() == 'no') {
                $("#chat #chat_ajax_img").fadeIn(100);
                $('#chat #userChat #block_msg').val('yes');
                var last_act = $('#chat #userChat #last_load').val();
                $.ajax(
                {
                    url: '/chat/getmessages',
                    type: 'POST',
                    data:
                    {
                        'ajax_action': 'get_chat_messages',
                        'last_act': last_act
                    },
                    dataType: 'json',
                    success: function (result)
                    {
                        if (result.last_act != 'null') {
                            // добавляем в текстовое поле новые сообщения
                            $('#chat #chat_messages_box').prepend(result.message_html);
                            // обновляем значение последнего сообщения
                            $('#chat #userChat #last_load').val(result.last_act);
                        }
                        $('#chat #userChat #block_msg').val('no');// убираем блокировку
                    }
                
                });
                $("#chat #chat_ajax_img").fadeOut(300);
            }
        }
        // проверяем наличие новых сообщений каждые 2 секунды
        setInterval(function()
        {
            get_chat_messages();
        },30000);
    });
});