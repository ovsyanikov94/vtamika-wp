$(document).ready( function (  ){

    $('#SendMessageToAdmin').click( async function (  ){

        let email = $('#email').val();
        let phone = $('#phone').val();
        let name = $('#name').val();
        let message = $('#message').val();

        try{

            let response = await $.ajax({
                'url': '/wp-admin/admin-ajax.php',
                'type': 'POST',
                'data': {
                    'email': email,
                    'phone': phone,
                    'name': name,
                    'message': message,
                    'action': 'sendMessageToAdmin'
                }
            });
            
            console.log('RESPONSE: ' , response);

        }//try
        catch( ex ){

            console.log('EX: ' , ex);

        }//catch

    }  );

}  );