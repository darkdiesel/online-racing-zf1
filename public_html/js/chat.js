$(document).ready(function(){
    jQuery(function($){
        $("#chat_messages_box .chat_message_box .chat_mesage_nickname .nick").click( function() {
            $("#chat #userChat #messageTextArea").val('[i]'+$(this).html()+'[/i], ');
            checkMes();
        });
     
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
        $("#chat #userChat").submit(function(){
            var message_text = $('#chat #messageTextArea').val();
            
            if (message_text.length==0) {
                alert("Нельзя отправлять пустые сообщения!");
            } else {
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
        });
        
        get_chat_messages();
        
        function get_chat_messages(){
            $.ajax(
            {
                url: '/chat/getmessages',
                type: 'POST',
                data:
                {
                    'ajax_action': 'get_chat_messages'
                    //'last_act': last_act
                },
                dataType: 'json',
                success: function (result)
                {
                    $('#chat #chat_messages_box').empty("");
                    $('#chat #chat_messages_box').html(result);
                    // добавляем в текстовое поле новые сообщения
                    //$('#chat_text_field').append(/*result.message_code*/);

                    // обновляем значение последнего сообщения
                    //$('#last_act').val(result.last_act);

                    // автопрокрутка текстового поля вниз
                    //$('#chat_text_field').scrollTop($('#chat_text_field').scrollTop()+100*$('.chat_post_my, .chat_post_other').size()); 

                    //$('#block').val('no');// убираем блокировку
                }
            });
        }
    });
});

/*$(document).ready(function () {

    // делаем фокус на поле ввода при загрузке страницы
    /*if ($("#chat_text_input").size()>0)
    {
        $("#chat_text_input").focus();
    }*/

    // функция отправки сообщения
    /*function send_message()
    {
        var message_text = $('#chat_text_input').val();
        if (message_text!="")
        {
            $.ajax(
            {
                url: 'functions/chat_scripts.php',
                type: 'POST',
                data:
                {
                    'action': 'add_message',
                    'message_text': message_text
                },
                dataType: 'json',
                success: function (result)
                {
                    $('#chat_text_input').val(''); // очищаем поле ввода

                    // сразу же подгружаем отправленное сообщение в чат
                    get_chat_messages();
                }
            });
        }
    }

    // функция подгрузки новых сообщений в чат
    function get_chat_messages()
    {
        // если не выставлена блокировка повторного выполнения данной функции, продолжаем
        if ($('#block').val() == 'no')
        {
            $('#block').val('yes'); // ставим блокировку

            var last_act = $('#last_act').val();
            $.ajax(
            {
                url: 'functions/chat_scripts.php',
                type: 'POST',
                data:
                {
                    'action': 'get_chat_message',
                    'last_act': last_act
                },
                dataType: 'json',
                success: function (result)
                {
                    // добавляем в текстовое поле новые сообщения
                    $('#chat_text_field').append(result.message_code);

                    // обновляем значение последнего сообщения
                    $('#last_act').val(result.last_act);

                    // автопрокрутка текстового поля вниз
                    $('#chat_text_field').scrollTop($('#chat_text_field').scrollTop()+100*$('.chat_post_my, .chat_post_other').size()); 

                    $('#block').val('no');// убираем блокировку
                }
            });
        }
    }

    // отправка сообщений при нажатии клавиши "Enter"
    $('#chat_text_input').keyup(function(event)
    {
        if (event.which == 13)
        {
            send_message();
        }
    });

    // отправка сообщений при нажатии кнопки "Ответить"
    $('#chat_button').click(function()
    {
        send_message();
    });

    // Действие для кнопки "Выход"
    $('#logout_button').click(function()
    {
        window.location.href = 'index.php?logout';
    });

    // проверяем наличие новых сообщений каждые 2 секунды
    setInterval(function()
    {
        get_chat_messages();
    }, 2000);

    // прокрутка текстового поля до последнего сообщения вниз
    $('#chat_text_field').scrollTop($('#chat_text_field').scrollTop()+100*$('.chat_post_my, .chat_post_other').size());

});*/