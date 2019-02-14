$( document ).ready(

    function (  ){

        function MakeCartTotal(  ){

            let startPricesElements = $('.product-start-price');
            let productAmountsElements = $('.product-amount');
            let total = 0;

            $.each( startPricesElements , function ( index , element ){

                let price = +$(element).data('start-price');
                let amount = +$(productAmountsElements[index]).val();

                total += price * amount;

            } );

            $('.cart-amount').text(`${startPricesElements.length}`);

            if( total === 0 ){

                $(`.cart-wrapper`).children().remove();
                $(`.cart-wrapper`).append('<h2>Корзина пуста!</h2>');

            }//if

            $('.cart-total').text(`$ ${total}`);
            $('.cartCount').text( startPricesElements.length );

        }

        $('body').on('click' , '.add-to-cart' , function (  ){

            let productID = $( this ).data('product-id');

            let cart = $.cookie('vCart');

            if( !cart ){

                cart = [
                    {
                        id: productID,
                        amount: 1
                    }
                ];

            }
            else{

                let check = cart.find( p => +p.id === +productID);

                if( !check ){

                    cart.push( {
                        id: productID,
                        amount: 1
                    });

                }//if

            }//else

            $.cookie('vCart' , cart , {
                path: '/',
                expires: 7
            });

            let parent = $( this ).parent(  );
            parent.children().remove();

            parent.append(`<div class="inCart">Уже в корзине</div>`);

            $('.cartCount').text( cart.length );

        } );
        
        $('body').on('input' , '.product-amount' , function (  ){
            
            let currentAmount = +$(this).val();
            let productID = +$(this).parent().data('product-id');
            let productStartPrice = +$(`.product-start-price[data-product-id=${productID}]`).data('start-price');

            let productTotalPrice = $(`.product-total-price[data-product-id=${productID}]`);

            if( isNaN( currentAmount ) || currentAmount === 0){

                $(this).val(1);
                productTotalPrice.text( `$ ${productStartPrice}` );

            }//if
            else{

                productTotalPrice.text( `$ ${productStartPrice * currentAmount}` );

            }//else

            MakeCartTotal();

        } );


        $('.cartDelete').click(function (  ){

            let productID = +$(this).data('product-id');
            $(`.productCartRow[data-product-id=${productID}]`).remove();
            MakeCartTotal();


            let cart = $.cookie('vCart');

            let check = cart.find( p => +p.id ===  productID );

            let index = cart.indexOf( check );
            cart.splice( index , 1 );

            $.cookie('vCart' , cart , {
                path: '/',
                expires: 7
            });

        } );


    }

);