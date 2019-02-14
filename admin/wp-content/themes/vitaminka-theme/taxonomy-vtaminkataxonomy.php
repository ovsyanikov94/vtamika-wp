<?php

get_header() ;

$category = get_queried_object();

?>

<div class="bannerSlim" style="background-image: url('http://hdwallpapers.cat/wallpaper/strawberry_fruit_red_brown_green_wood_hd-wallpaper-1725166.jpg');">
    <div class="bannerWrap"></div>
    <h2><?= $category->name ?></h2>
</div>
<div class="products clearfix">


    <ul class="effect-2 grid cs-style-2 clearfix" id="grid">


<?php

if ( have_posts() ) {

    while ( have_posts() ) {
        the_post();

        $id = get_the_ID();
        $title = get_the_title();
        $price = get_post_meta( $id , 'price' , true);
        $link = get_the_permalink();

        $thumb_id = get_post_thumbnail_id($id);
        $image = wp_get_attachment_image_src($thumb_id,'full');//
        $image = $image[0];


    ?>

        <li class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
            <figure>
                <div class="figurWrap">
                    <h3><a href="<?= $link ?>"><?= $title ?> &mdash; <span class="price">$ <?= $price ?></span></a></h3>
                    <img src="<?= $image ?>">
                </div>
                <figcaption>
                    <div class="clearfix buttonBlock">
                        <!--                                        <input class="input" type="text" value="1">-->
                        <select class="cs-select cs-skin-border">
                            <option value="1" disabled selected>1</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>

                        <button id="js-ripple-btn" class="button styl-material">
                            В корзину
                            <svg class="ripple-obj" id="js-ripple">
                                <use height="100" width="100" xlink:href="#ripply-scott" class="js-ripple"></use>
                            </svg>
                        </button>
                    </div>
                </figcaption>
            </figure>
        </li>

        <?php }//while


} else {

   echo "<h2>Products not found!</h2>";
}
?>

<?php get_footer() ?>


