<!DOCTYPE html>
<html>
<head>

    <?php
        $path = get_template_directory_uri();
    ?>

    <title>Live longer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon" href="<?=$path?>/images/logoSmall.png">
    <link rel="stylesheet" href="<?=$path?>/css/normalize.css">
    <link rel="stylesheet" href="<?=$path?>/css/bootstrap.css">

    <link rel="stylesheet" href="<?=$path?>/css/component.css">
    <link rel="stylesheet" href="<?=$path?>/css/style.css">


    <link rel="stylesheet" type="text/css" href="<?=$path?>/css/cs-select.css" />
    <link rel="stylesheet" type="text/css" href="<?=$path?>/css/cs-skin-rotate.css" />
    <link rel="stylesheet" type="text/css" href="<?=$path?>/css/snap.css" />
    <link rel="stylesheet" type="text/css" href="<?=$path?>/css/jquery.mCustomScrollbar.css" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <script src="<?=$path?>/js/script.js"></script>
    <script src="<?=$path?>/node_modules/jquery.cookie/jquery.cookie.js"></script>
    <script src="<?=$path?>/js/bootstrap.js"></script>
    <script src="<?=$path?>/js/hover.js"></script>
    <script src="<?=$path?>/js/snap.js"></script>
    <script src="<?=$path?>/js/modernizr.custom.animate.js"></script>
    <script src="<?=$path?>/js/jquery.mCustomScrollbar.js"></script>
    <script src="<?=$path?>/js/cart.js"></script>

    <script defer >

        $.cookie.json = true;

        $(document).ready( ()=>{

            $('.cbalink').remove();

        } );

    </script>

    <?php

        wp_head();
        $categories = get_terms( 'vtaminkataxonomy' , [
            'orderby'           => 'name',
            'order'             => 'ASC',
            'hide_empty'        => false,
            'exclude'           => array(),
            'exclude_tree'      => array(),
            'include'           => array(),
            'fields'            => 'all',
            'slug'              => '',
            'parent'            => null,
            'hierarchical'      => false,
            'childless'         => false,
            'get'               => '',
            'name__like'        => '',
            'description__like' => '',
            'pad_counts'        => false,
            'offset'            => '',
            'search'            => '',
            'cache_domain'      => 'core'
        ] );



        if( isset($_COOKIE['vCart']) ){

            $cart = json_decode(  stripcslashes($_COOKIE['vCart'] ));

            $amount = count( $cart );

        }//if
        else{
            $amount = 0;
        }//else

    ?>
    <script>

        let value = `<?= $_COOKIE['vCart']; ?>`;
        value = JSON.parse(value.replace('\\',''));

    </script>

</head>
<body>
<div class="snap-drawers">


    <div class="snap-drawer snap-drawer-left">
        <div class="side">
            <h2>Меню</h2>
            <ul>
                <li><a href="index.html" >Главная</a></li>
                <li><a href="products.html" >Товары</a></li>
                <li><a href="blog.html" >Блог</a></li>
                <li><a href="about_us.html" >О компании</a></li>
                <li><a href="contacts.html" >Контакты</a></li>
            </ul>

            <h2>Категории</h2>

            <ul>
                <?php foreach ($categories as $category) : ?>
                    <li><a href="index.html" ><?php echo $category->name ?></a></li>
                <?php endforeach; ?>
            </ul>
            <h2>Контакты</h2>
            <p>email@Vtaminka.com</p>

            <div class="socialSmall clearfix">
                <span class="social vk">E</span>
                <span class="social fb">B</span>
                <span class="social google">C</span>
                <span class="social tw">D</span>
            </div>
        </div>
    </div>

    <div id="content" class="snap-content" style="">

        <header class="headerTop ">
            <div class="wrapper clearfix">
                <div class="menuThin visible-sm-inline-block visible-xs-inline-block">
                    <a href="#" class="dropdown-toggle smallMenuA">T</a>
                </div>
                <div class="delimetersmall delimeter visible-sm-inline-block visible-xs-inline-block"></div>
                <div class="lang">
                    <select class="cs-select cs-skin-rotate">
                        <option value="1">Язык</option>
                        <option value="2">rus</option>
                        <option value="3">eng</option>
                    </select>
                </div>
                <div class="delimeter"></div>
                <div class="cart">
                    <a href="/cart">
                        <span class="cartIcon">n</span>
                        <span class="cartCount"><?= $amount ?></span>
                    </a>
                </div>
                <div class="menu hidden-sm hidden-xs">
                    <nav class="topMenu cl-effect-2">
                        <a href="index.html" >Главная</a>
                        <a href="products.html" >Товары</a>
                        <a href="blog.html" >Блог</a>
                        <a href="about_us.html" >О компании</a>
                        <a href="contacts.html" >Контакты</a>
                    </nav>
                </div>

            </div>
        </header>

        <header class="header ">
            <div class="wrapper">
                <div id='menuOpen' class="hidden menuThin visible-sm-inline-block visible-xs-inline-block">
                    <a href="#" class="dropdown-toggle smallMenuA" >T</a>
                </div>
                <div class="logo">
                    <a href="/"><img src="<?=$path?>/images/logo.png" alt=""></a>
                </div>
                <div class="cart">
                    <a href="/cart">
                        <span class="cartIcon">n</span>
                        <span class="cartCount"><?= $amount ?></span>
                    </a>
                </div>
                <div class="menu hidden-sm hidden-xs">
                    <nav class="mainMenu cl-effect-5">

                            <?php foreach ($categories as $category) : ?>
                                <?php $categoryLink = get_category_link($category->term_id) ?>
                                <a href="<?= $categoryLink ?>" >
                                    <span data-hover='<?php echo $category->name ?>' >
                                        <?php echo $category->name ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                    </nav>
                </div>

            </div>

        </header>

        <section>