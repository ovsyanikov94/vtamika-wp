<?php
get_header() ;

$cart = null;
$products = null;

if( isset($_COOKIE['vCart']) ){

    $cart = json_decode( stripslashes( $_COOKIE['vCart'] ) );

    $products = [];
    $total = 0;
    $totalAmount = 0;
    $delivery = get_option('delivery');

    foreach ($cart as $item) {
        $totalAmount++;
        $post = get_post( $item->id );
        $post->price = get_post_meta( $item->id , 'price' , true);
        $thumb_id = get_post_thumbnail_id( $item->id);
        $image = wp_get_attachment_image_src($thumb_id,'full');//
        $post->image = $image[0];
        $post->amount = $item->amount;

        //$post->post_content = apply_filters( 'post_content', $post->post_content );
        $post->post_content = strip_tags( $post->post_content );

        $products[] = $post;
        $total += $post->price * $post->amount;


    }//foreach

}//if

?>

<div class="bannerSlim" style="background-image: url('images/banner.png'); background-position-y: 55%;">
    <div class="bannerWrap"></div>
    <h2>Оформить заказ</h2>
</div>
<div class="wrapper clearfix">
    <!-- SVG Sprite -->
    <div style="height: 0; width: 0; position: absolute; visibility: hidden;" aria-hidden="true">
        <svg version="1.1" xmlns="http://www.w2.org/2000/svg" xmlns:xlink="http://www.w2.org/1999/xlink" focusable="false">
            <symbol id="ripply-scott" viewBox="0 0 100 100">
                <circle id="ripple-shape" cx="1" cy="1" r="1" />
            </symbol>
        </svg>
    </div>

    <div class="clearfix">

        <div class="checkoutOrder col-lg-6 col-md-6 col-sm-12 col-xs-12 ">
            <h2>Персональные данные:</h2>
            <div class="clearfix">
                <div class="inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Ваше имя">
                    </label>
                </div>
                <div class="telInp inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Контактный email">
                    </label>
                </div>
                <div class=" inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Контактный телефон">
                    </label>
                </div>
                <div class=" inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Адресс доставки">
                    </label>
                </div>
                <div class="areaWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <textarea></textarea>
                </div>
            </div>

            <h2>Оплата:</h2>
            <div class="clearfix">
                <div class="inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Номер карты">
                    </label>
                </div>
                <div class="telInp inputWrap col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <select class="cs-select cs-skin-border">
                        <option value="" disabled selected>Выберите год</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
                <div class=" inputWrap col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <select class="cs-select cs-skin-border">
                        <option value="" disabled selected>Выберите год</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
                <div class=" inputWrap col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>

                        <input class=""  placeholder="CVV код">
                    </label>
                </div>
                <div class=" inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>

                        <input class=""  placeholder="Имя карты">
                    </label>
                </div>

            </div>

            <div class="areaWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <button id="js-ripple-btn" class="button styl-material">
                    Подтвердить заказ
                    <svg class="ripple-obj" id="js-ripple">
                        <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                    </svg>
                </button>
            </div>

        </div>
        <div class="checkout col-lg-6 col-md-6 col-sm-12 col-xs-12 ">
            <h2>Содержание заказа:</h2>

            <?php if($cart != null) : ?>

                <?php foreach ($products as $item) : ?>

                    <div class="clearfix productCartRow">

                        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12 productImg cartCol">
                            <img src="<?= $item->image ?>" alt="">
                        </div>
                        <div class=" col-lg-6 col-md-4 col-sm-4 col-xs-4 productCartText cartCol">

                            <div class="productBlock">
                                <h3> <?= $item->post_title ?> </h3>
                            </div>

                        </div>
                        <div class="text-center col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">
                            <?= $item->amount ?>
                        </div>
                        <div class="text-center col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">
                            $ <?= $item->amount * $item->price ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php
            else :
                echo "<h2>Корзина пуста</h2>";
            endif; ?>



            <div class="clearfix coupon productCartRow">
                <div class="clearfix buttonBlock">
                    <input class="input" type="text" value="" placeholder="Введите промокод">
                    <button id="js-ripple-btn" class="button styl-material">
                        ОК
                        <svg class="ripple-obj" id="js-ripple">
                            <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="text-left clearfix cartAllCount productCartRow">

                <div class="empty col-lg-2 col-md-2 col-sm-2 col-xs-12 productImg cartCol">

                </div>
                <div class="text-left col-lg-6 col-md-4 col-sm-4 col-xs-4 productCartText cartCol">

                    <div class="productBlock">
                        <h3>Доставка</h3>
                    </div>

                </div>

                <div class="text-center  col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">

                </div>
                <div class="text-center col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">
                    $  <?= $delivery ?>
                </div>


            </div>

            <div class="clearfix cartAllCount productCartRow">

                <div class="empty col-lg-2 col-md-2 col-sm-2 col-xs-12 productImg cartCol">

                </div>
                <div class=" col-lg-6 col-md-4 col-sm-4 col-xs-4 productCartText cartCol">

                    <div class="productBlock">
                        <h3>Итого</h3>
                    </div>

                </div>

                <div class="text-center col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">
                    <?= $totalAmount ?>
                </div>
                <div class="text-center col-lg-2 col-md-3 col-sm-3 col-xs-4 productCartText cartCol">
                    $ <?= $total + $delivery ?>
                </div>


            </div>

        </div>

    </div>
</div>

<?php get_footer() ; ?>
