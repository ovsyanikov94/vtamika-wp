$(document).ready(function(){
    $('#content').scroll(function(){
        if($('#content').scrollTop() > 40){//window

            $('.header').addClass('headerFluid');
            $('#menuOpen').removeClass('hidden');
            
        }else{
            $('.header').removeClass('headerFluid');
            $('#menuOpen').addClass('hidden');
        }    
        
    });    
    
    var snapper = new Snap({
        element: document.getElementById('content'),
        disable: 'right'
    });
    
    
    if(document.documentElement.clientWidth<991){
        snapper.enable();
    }else{
        snapper.disable();
    }
    $('.smallMenuA').click(function(){
        
        var data = snapper.state();
        if(data.state == 'closed'){
            snapper.open('left');
        }else{
            snapper.close();
        }
    });
    
//    $(".tabsContent").mCustomScrollbar({
//        axis:"x"
//    });
});