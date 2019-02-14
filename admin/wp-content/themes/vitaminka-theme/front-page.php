<?php get_header(); ?>

<?php
    $path = get_template_directory_uri();
?>

<div class="banner" style="background-image: url(<?=$path?>/images/banner.png);">
</div>
<div class="wrapper clearfix">

    <script defer src="<?= $path ?>/js/feedback.js" ></script>

    <!-- SVG Sprite -->
    <div style="height: 0; width: 0; position: absolute; visibility: hidden;" aria-hidden="true">
        <svg version="1.1" xmlns="http://www.w2.org/2000/svg" xmlns:xlink="http://www.w2.org/1999/xlink" focusable="false">
            <symbol id="ripply-scott" viewBox="0 0 100 100">
                <circle id="ripple-shape" cx="1" cy="1" r="1" />
            </symbol>
        </svg>
    </div>

    <h2>Каталог</h2>

    <div class="products clearfix">

        <ul class="effect-2 grid cs-style-2 clearfix" id="grid">

            <?php
                $products = get_posts([
                    'numberposts' => 10,
                    'post_type' => 'goods'
                ]);

                if( isset($_COOKIE['vCart']) ){
                    $cart = json_decode(  stripcslashes($_COOKIE['vCart'] ));
                }//if
                else{
                    $cart = null;
                }//else


            ?>

            <?php foreach ($products as $product) : ?>

                <?php
                    $price = get_post_meta($product->ID, 'price', true );
                    $id = $product->ID;

                    $thumb_id = get_post_thumbnail_id($product->ID);
                    $image = wp_get_attachment_image_src($thumb_id,'full');//
                    $image = $image[0];

                    $link = get_permalink($product->ID);

                    if($cart){

                        $check =  array_filter( $cart , function( $product ) use ($id)  {
                            return $product->id == $id;
                        });

                    }//if
                    else{
                        $check = false;
                    }//else

                ?>

                <li class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                    <figure>
                        <div class="figurWrap">
                            <h3><a href="<?= $link?>" > <?= $product->post_title ?> &mdash; <span class="price">$ <?= $price ?></span></a></h3>
                            <img src="<?= $image ?>">
                        </div>
                        <figcaption>

                            <?php if( !$check ):  ?>

                            <div class="clearfix buttonBlock">
                                <select class="cs-select cs-skin-border">
                                    <option value="1" disabled selected>1</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                </select>

                                <button data-product-id="<?= $product->ID ?>" id="js-ripple-btn" class="button styl-material add-to-cart">
                                    В корзину
                                    <svg class="ripple-obj" id="js-ripple">
                                        <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                                    </svg>
                                </button>
                            </div>

                            <?php else: ?>
                                 <div class="clearfix buttonBlock">
                                    <div class="inCart">Уже в корзине</div>
                                 </div>
                            <?php endif; ?>

                        </figcaption>
                    </figure>
                </li>

            <?php endforeach; ?>


        </ul>
        <div class="more">
            <button id="js-ripple-btn" class="button styl-material">
                Еще
                <svg class="ripple-obj" id="js-ripple">
                    <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                </svg>
            </button>
        </div>
    </div>

    <h2>Новости</h2>

    <div class="indexNews clearfix">
        <div class="news col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <img src="images/year.jpg" alt="">
        </div>
        <div class="news col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <img src="images/year.jpg" alt="">
        </div>
        <div class="news col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <img src="images/year.jpg" alt="">
        </div>
        <div class="news col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <img src="images/year.jpg" alt="">
        </div>
    </div>

    <h2>Свяжитесь с нами!</h2>

    <div class="indexForm contactsForm col-lg-6 col-md-8 col-sm-10 col-xs-12 clearfix">

        <div class="clearfix">
            <div class="inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <label>

                    <input class="" id="name" placeholder="Ваше имя">
                </label>
            </div>
            <div class="telInp inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <label>

                    <input class=""  id="email" placeholder="Контактный email">
                </label>
            </div>
            <div class=" inputWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <label>

                    <input class="" id="phone" placeholder="Контактный телефон">
                </label>
            </div>

        </div>
        <div class="areaWrap col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <textarea id="message"></textarea>
        </div>
        <!--                        <input type="button" class="button" value="ОТПРАВИТЬ">-->
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <button id="SendMessageToAdmin" class="button styl-material">
                ОТПРАВИТЬ
                <svg class="ripple-obj" id="js-ripple">
                    <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                </svg>
            </button>
        </div>

    </div>
</div>

<?php get_footer(); ?>
