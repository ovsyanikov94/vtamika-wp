<?php
/*
Plugin Name: my-blog-plugin
Author: IT-STEP
*/

include("includes/class.newPostType.php");
include("includes/class.newPostStatus.php");
include("includes/class.formGenerator.php");


include("includes/ajaxApi.php");
require_once "includes/Entity.php";
include 'includes/avgEntity.php';

//include_once 'includes/taxonomy-term-image.php';

add_action( 'init', 'registerPostTypes', 0 );



$entity = new avgEntity();

$entity->addEntityFields(

    array(
        'price' => ''
    )
);

//ajaxApi::initializeEntity($entity);
//ajaxApi::registerApiAction('getPostWithParams');
//ajaxApi::registerApiAction('makeOrder');
//ajaxApi::registerApiAction('savePhoto');
//ajaxApi::registerApiAction('getSocials');
//ajaxApi::registerApiAction('getOptions');
//ajaxApi::registerApiAction('authorize');
//ajaxApi::registerApiAction('getOrders');
//ajaxApi::registerApiAction('getAdminOrders');
//ajaxApi::registerApiAction('adminAuthorize');
//ajaxApi::registerApiAction('usePromoCode');
//ajaxApi::registerApiAction('getRoboResponse');
//ajaxApi::registerApiAction('sendMail');
//ajaxApi::registerApiAction('deleteOrder');

ajaxApi::registerApiAction('sendMessageToAdmin');

add_action('admin_menu', 'CreateMyPluginMenu');

function CreateMyPluginMenu(){

    //Проверим существует ли функция add_options_page
    if (function_exists('add_options_page'))
    {
        //Добавляем пункт меню в Параметры
        add_options_page('Страница настроек', 'Общаяя информация', 'manage_options', 'MypluginUniqIdentifictor', 'MyPluginPageOptions');
    }
}

function MyPluginPageOptions(){
    ?>
    <div class="wrap">
        <h3>Укажите дополнительные настройки:</h3>

        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>

            <table class="form-table">

                <tr valign="top">
                    <th scope="row">Доставка</th>
                    <td><input type="text" style="width: 100%;" name="delivery" value="<?php echo get_option('delivery'); ?>" /></td>
                </tr>

            </table>

            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="delivery" />

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>

        </form>
    </div>
    <?

}

function wpdocs_register_meta_boxes() {
    add_meta_box(
        'meta-box-id',
        __( 'Корзина заказа', 'textdomain' ),
        'wpdocs_my_display_callback',
        'myorder'
    );
}
add_action( 'add_meta_boxes', 'wpdocs_register_meta_boxes' );

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function wpdocs_my_display_callback( $post ) {
    echo ajaxApi::getOrderCartHtml($post->ID);
}


function registerPostTypes()
{


    add_theme_support('post-thumbnails');
    add_option( 'my_option', '255' );

//    add_image_size( 'small-size', 84, 84, true );
//    add_image_size( 'big-size', 288, 142 , true);
//    add_image_size( 'instagram-size', 213, 213 , true);



    $taxonomies = array(
        'vtaminkaTaxonomy'
    );

    $fields = array();
    $metaBox = array();

    $fields[] = array(
        'type' => 'input-text',
        'name' => 'price',
        'placeholder' => 'Цена',
        'label' => "Введите цену"
    );

    $fields[] = array(
        'type' => 'text-area',
        'name' => 'ingredients',
        'placeholder' => 'Состав',
        'label' => "Введите состав",
        'rows' => 8
    );

    $metaBox[] = array('name' => 'Характеристики товара', 'fields' => $fields);


    $Goods = new newPostType(
        'Goods',
        'Товары',
        array(
            'title',
            'editor',
            'thumbnail'
        ), $taxonomies, $metaBox);

    $metaBox = [];
    $fields = [];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'email',
        'placeholder' => 'Email',
        'label' => "Email"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'phone',
        'placeholder' => 'Телефон',
        'label' => "Телефон пользователя"
    ];

    $metaBox [] =  array('name' => 'Контактная информация', 'fields' => $fields );

    $Feedback = new newPostType(
        'feedback',
        'Обратная связь',
        array(
            'title',
            'editor'
        ), [], $metaBox);

    $fields = [];
    $metaBox = [];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'email',
        'placeholder' => 'Email',
        'label' => "Email"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'phone',
        'placeholder' => 'Телефон',
        'label' => "Телефон пользователя"
    ];


    $fields[] = [
        'type' => 'input-text',
        'name' => 'address',
        'placeholder' => 'Адрес',
        'label' => "Адрес доставки"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'card',
        'placeholder' => 'Карта',
        'label' => "Карта пользователя"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'cvv',
        'placeholder' => 'CVV',
        'label' => "CVV"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'month',
        'placeholder' => 'Месяц',
        'label' => "Месяц"
    ];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'year',
        'placeholder' => 'Год',
        'label' => "Год"
    ];


    $metaBox [] =  array('name' => 'Детальная информация о заказе', 'fields' => $fields );

    new newPostType(
        'orders',
        'Заказы',
        array(
            'title',
            'editor'
        ), [], $metaBox);

    $fields = [];
    $metaBox = [];

    $fields[] = [
        'type' => 'input-text',
        'name' => 'discount',
        'placeholder' => 'Процент скидки',
        'label' => "Процент скидки"
    ];

    $metaBox [] =  array('name' => 'Информация', 'fields' => $fields );

    new newPostType(
        'promocodes',
        'Промокоды',
        array(
            'title',
        ), [], $metaBox);

}//



