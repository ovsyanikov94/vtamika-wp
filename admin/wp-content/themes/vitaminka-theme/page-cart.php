<?php
    get_header() ;

    $cart = null;
    $products = null;

    if( isset($_COOKIE['vCart']) ){

        $cart = json_decode( stripslashes( $_COOKIE['vCart'] ) );

        $products = [];
        $total = 0;
        $totalAmount = 0;

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
    <h2>Корзина</h2>
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

    <div class="breadCrumbs">
        <a href="#">Главная</a>
        <div class="delimeter"></div>
        <a href="#">Корзина</a>
    </div>

    <div class="breadCrumbs">
        <a href="/make-order">Оформить заказ</a>
    </div>

    <div class="cart-wrapper" >

        <?php if($cart != null) : ?>

            <?php foreach ($products as $item) : ?>

                <div class="clearfix productCartRow" data-product-id="<?= $item->ID?>">

                    <?php

                    $content = substr($item->post_content , 0 , 150 );
                    $content .= '...';

                    ?>

                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12 productImg cartCol">
                        <img src="<?= $item->image ?>" alt="...">
                    </div>
                    <div class=" col-lg-6 col-md-6 col-sm-6 col-xs-12 productCartText cartCol">

                        <div class="productBlock">
                            <h3><?= $item->post_title ?></h3>
                            <p class="text-justify"><?= $content ?></p>
                        </div>

                    </div>
                    <div data-product-id="<?= $item->ID?>" data-start-price="<?= $item->price ?>" class="product-start-price text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                        $ <?= $item->price ?>
                    </div>
                    <div data-product-id="<?= $item->ID?>" class="text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                        <input class="input product-amount" type="text" value="<?= $item->amount ?>">
                    </div>
                    <div data-product-id="<?= $item->ID?>" class="product-total-price text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                        $ <?= $item->amount * $item->price ?>
                    </div>
                    <div data-product-id="<?= $item->ID?>" class="text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol cartDelete">
                        <a href="#">s</a>
                    </div>
                </div>

            <?php endforeach; ?>

            <div class="clearfix">
                <div class="clearfix productCartRow cartAllCount">

                    <div class=" col-lg-6 col-md-6 col-sm-6 col-xs-12 productCartText cartCol">

                        <h3 class="text-left" style="margin-bottom: 0px;">Итого</h3>

                    </div>
                    <div class="text-center empty col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                    </div>
                    <div class="cart-amount text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                        <?= $totalAmount ?>
                    </div>
                    <div data-cart-total="<?= $total ?>" class="cart-total text-center col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                        $ <?= $total ?>
                    </div>
                    <div class="text-center empty col-lg-1 col-md-1 col-sm-1 col-xs-12 productCartText cartCol">
                    </div>
                </div>
            </div>

        <?php
        else :
            echo "<h2>Корзина пуста</h2>";
        endif; ?>

    </div>


</div>

<?php get_footer() ; ?>

