<?php
    get_header();

    $id = get_the_ID();

    $thumb_id = get_post_thumbnail_id($id);
    $image = wp_get_attachment_image_src($thumb_id,'full');//
    $image = $image[0];

    $price = get_post_meta($id  , 'price' , true );

    $post = get_post( $id );

?>


<div class="bannerSlim" style="background-image: url('images/banner.png');
        background-position-y: 55%;">
    <div class="bannerWrap"></div>
    <h2><?php the_title() ?></h2>
<!--

    SELECT * ...
    WP_POST = { ... }
    ...
    the_title() => echo WP_POST->post_title
-->

</div>
<div class="wrapper clearfix">

    <div class="clearfix product">

        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 productImg">
            <img src="<?= $image ?>" alt="not found!">
        </div>
        <div class=" col-lg-6 col-md-6 col-sm-6 col-xs-12 productText">


            <div class="productBlock">
                <h3 class="productPrice">$ <?= $price ?></h3>
                <div class="productToCart">
                    <div class="clearfix buttonBlock">
                        <input class="input" type="text" value="1">
                        <button id="js-ripple-btn" class="button styl-material">
                            В корзину
                            <svg class="ripple-obj" id="js-ripple">
                                <use height="100" width="100" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#ripply-scott" class="js-ripple"></use>
                            </svg>
                        </button>
                    </div>
                </div>
                <h3>Описание</h3>
                <div>
                    <?php //the_content(); ?>
                    <?= $post->post_content; ?>
                </div>

            </div>

        </div>

    </div>
    <div class="clearfix product">
        <div class=" col-lg-12 col-md-12 col-sm-12 col-xs-12 productText">

            <div>
                <div class="tabsContent">
                    <ul class="nav nav-tabs" id="myTabs" role="tablist">
                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Описание</a></li>
                        <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Состав</a></li>
                        <li role="presentation"><a href="#info" aria-controls="info" role="tab" data-toggle="tab">Информация о продукте</a></li>
                        <li role="presentation" class=""><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Описание</a></li>
                        <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Состав</a></li>
                        <li role="presentation"><a href="#info" aria-controls="info" role="tab" data-toggle="tab">Информация о продукте</a></li>
                        <li role="presentation" class=""><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Описание</a></li>
                        <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Состав</a></li>
                        <li role="presentation"><a href="#info" aria-controls="info" role="tab" data-toggle="tab">Информация о продукте</a></li>

                    </ul>
                </div>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active productBlock" id="home">

                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque sagittis erat sit amet enim auctor feugiat.  Aenean nec ultricies enim. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis.  Aenean nec ultricies enim. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis. </p>
                        <p> Aenean nec ultricies enim.  Aenean nec ultricies enim. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis.  Aenean nec ultricies enim. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis. Duis vitae mauris erat.</p>

                    </div>
                    <div role="tabpanel" class="tab-pane productBlock" id="profile">

                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque sagittis erat sit amet enim auctor feugiat. </p>
                        <p> Aenean nec ultricies enim. Vestibulum ut viverra orci. Maecenas suscipit odio quis felis varius mollis. Duis vitae mauris erat.</p>
                    </div>
                    <div role="tabpanel" class="tab-pane productBlock" id="info">

                        <p><span class="bold">Дозировка на год: </span> 1000г</p>
                        <p><span class="bold">Дозировка на 6 месяцев:</span> 500г</p>
                        <p><span class="bold">Противопоказания: </span> нет</p>
                        <p><span class="bold">Дополнительные факты: </span> нет</p>
                        <p><span class="bold">Количество в упаковке: </span> 100</p>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>


<?php
    get_footer();
?>

