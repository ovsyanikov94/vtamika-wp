<?php

include 'Interface.api.php';
include("class.mmOrders.php");

class ajaxApi implements interfaceApi{
    
    public static function echoDataWithHeader($data){
        
        header("Content-Type: application/json");
       
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: application/json');
        
        if(isset($data['fields'])){
            echo json_encode($data['fields']);
        }//if
        else{
            echo json_encode(array());
        }//else

        exit();
        
    }//echoDataWithHeader
    
    public static $entity;
    
    public static function initializeEntity(Entity $entity){
        
        self::$entity = $entity;
        
    }

    public static function sendMessageToAdmin(  ){

        $name = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST,'phone',FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST,'email',FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST,'message',FILTER_SANITIZE_STRING);

        $postID = wp_insert_post([
            'post_title' => $name,
            'post_content' => $message,
            'post_type' => 'feedback',
            'post_status'   => 'publish',
            'post_author'   => 1,
        ]);

        if( is_wp_error($postID) || $postID == 0 ){

            self::echoDataWithHeader([
                'fields' => [
                    'message' => 'Создание не удалось',
                    'error' => $postID,
                    'code' => 500,
                    'data' => [ $name , $message ]
                ]
            ]);

        }//if


        update_post_meta( $postID , 'email' , $email );
        update_post_meta( $postID , 'phone' , $phone );

        self::echoDataWithHeader([
            'fields' => [
                'message' => 'Создание удалось!',
                'code' => 200,
                '$postID' => $postID
            ]
        ]);

    }//sendMessageToAdmin

    public static function registerApiAction($action){
          
        add_action( "wp_ajax_$action", array('ajaxApi', $action));
        add_action( "wp_ajax_nopriv_$action", array('ajaxApi', $action));


    }
    
    public static function getPosts($params = array()){
        
        $postParams = array(
            'numberposts'     => isset($params['numberposts'])?$params['numberposts']:-1,
            'offset'          => isset($params['offset'])?$params['offset']:0,
            'orderby'         => isset($params['orderby'])?$params['orderby']:'post_date',
            'order'           => isset($params['order'])?$params['order']:'DESC',
            'include'         => isset($params['ID'])?$params['ID']:'',
            'exclude'         => '',
            'meta_key'        => isset($params['meta_key']) ? $params['meta_key']: '',
            'meta_value'      => isset($params['meta_value']) ? $params['meta_value']: '',
            'post_type'       => isset($params['type'])?$params['type']: array('production','News','Slider','Partners','portfolio'),
            'post_mime_type'  => '',
            'post_parent'     => '',
            'name' => isset($params['slug'])?$params['slug']:'',
            'post_status'     => array('new','buy','delivered','publish')
        );
        
        if(isset($params['author'])){
            $postParams['author'] = $params['author'];
        }//if
        
        if(isset($params['category'])){
            $postParams['tax_query'] = array(	
                array(
			'taxonomy' => $params['taxonomy'],
			'field'    => 'slug',
			'terms'    => $params['category'],
		), 	
            );
        }

        if(isset($params['meta_query_set'])){

            $postParams['meta_query'] = array();

            foreach($params['meta_query_values'] as $value){
                $postParams['meta_query'][]['key'] = $value['key'];
                $postParams['meta_query'][]['value'] = $value['value'];
                $postParams['meta_query'][]['relation'] = 'AND';

            }

        }

        $posts = get_posts($postParams);
        
        return $posts;
        
    }
    
    public static function getProductKey(){
        
        return 'pKey';
        
    }

    public static function getPostWithParams($params = array()){
        
        $resultProducts = array();
        $myArray = array();
        $isEcho = true;
        
        //Если в параметре пустой массив 
        if(empty($params)){
            
            parse_str($_REQUEST[self::getProductKey()],$myArray );
            $params = $myArray;
            
        }//if
        else{
            
            $isEcho = false;
            
        }
        
        foreach (self::getPosts($params) as $postObj){
            
            if(isset($params['class'])){
                $entityData = new $params['class']($postObj);
            }//if
            else{
                $entityData = clone self::$entity->getEntityData($postObj);
            }//else
            
            $resultProducts[] = $entityData;
        }//foreach
        
        if($isEcho){
            
            self::echoDataWithHeader(
                array(
                    'header' => 'json',
                    'fields' => $resultProducts
                    )//array
            );

        }
        else{
            
            return $resultProducts;
            
        }//else
        
        
        
    }
    
    public static function addFeedback(){
        
        $userName = $_REQUEST['name'];
        $userEmail = $_REQUEST['email'];
        $userMessage = $_REQUEST['message'];
        $userPhone = $_REQUEST['phone'];
        
        
        
        $orderPostParams = array(
                'post_status' => 'publish',
                'post_type' => 'feedback',
                'post_title' => "feedback-$userName",
                'post_content' => $userMessage
        );
        
        $feedId = wp_insert_post($orderPostParams,true);
        
        update_post_meta($feedId, 'name', $userName);
        update_post_meta($feedId, 'mail', $userEmail);
        update_post_meta($feedId, 'phone', $userPhone);
        
        self::echoDataWithHeader(
                    array(
                        'header' => 'json',
                        'fields' => $feedId
                        )
            );
        
    }

    public static function getCategoriesR($parent){

        $args = array(
            'orderby'           => 'name',
            'order'             => 'ASC',
            'hide_empty'        => false,
            'exclude'           => array(),
            'exclude_tree'      => array(),
            'include'           => isset($_REQUEST['ID'])?$_REQUEST['ID']:array(),
            'fields'            => 'all',
            'slug'              => '',
            'parent'            => $parent == null ? 0 : $parent,
            'hierarchical'      => false,
            'childless'         => false,
            'get'               => '',
            'name__like'        => '',
            'description__like' => '',
            'pad_counts'        => false,
            'offset'            => '',
            'search'            => '',
            'cache_domain'      => 'core'
        );

        $categories = get_terms('productstax',$args);

        if(count($categories) == 0){

            return;

        }

        $resultCategories = array();


        foreach ($categories as $category) {

            $categoryObject = new stdClass();
            $categoryObject->name = html_entity_decode($category->name);
            $categoryObject->term_id = $category->term_id;
            $categoryObject->slug = $category->slug;
            $categoryObject->parent = $category->parent;
            $categoryObject->image = get_option( 'productstax_term_images');

            foreach(get_option( 'productstax_term_images') as $catId=>$imageId){
                if($catId == $categoryObject->term_id){
                    $imageResult = wp_get_attachment_image_src( $imageId, 'thumbnail' );
                    $categoryObject->image = $imageResult[0];
                }//if
            }//foreach

            $categoryObject->subCats = self::getCategoriesR($category->term_id);
            $resultCategories[] = $categoryObject;

        }//foreach

        return $resultCategories;

    }

    public static function getSingleCategory(){
        $slug = $_REQUEST['slug'];

        $args = array(
            'orderby'           => 'name',
            'order'             => 'ASC',
            'hide_empty'        => false,
            'exclude'           => array(),
            'exclude_tree'      => array(),
            'include'           => isset($_REQUEST['ID'])?$_REQUEST['ID']:array(),
            'fields'            => 'all',
            'slug'              => $slug,
            'parent'            => '',
            'hierarchical'      => false,
            'childless'         => false,
            'get'               => '',
            'name__like'        => '',
            'description__like' => '',
            'pad_counts'        => false,
            'offset'            => '',
            'search'            => '',
            'cache_domain'      => 'core'
        );

        $categories = get_terms('productstax',$args);

        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $categories[0]
            )
        );

    }//getSingleCategory

    public static function getCategories($parent=null){


        $categories = self::getCategoriesR(null);
        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $categories
            )
        );
        
    }

    public static function getSubCategories(){
        $parent = $_REQUEST['parent'];

        $args = array(
            'orderby'           => 'name',
            'order'             => 'ASC',
            'hide_empty'        => false,
            'exclude'           => array(),
            'exclude_tree'      => array(),
            'include'           => isset($_REQUEST['ID'])?$_REQUEST['ID']:array(),
            'fields'            => 'all',
            'slug'              => '',
            'parent'            => $parent,
            'hierarchical'      => false,
            'childless'         => false,
            'get'               => '',
            'name__like'        => '',
            'description__like' => '',
            'pad_counts'        => false,
            'offset'            => '',
            'search'            => '',
            'cache_domain'      => 'core'
        );

        $categories = get_terms('productstax',$args);

        $resultCategories = array();

        foreach ($categories as $category) {

            $categoryObject = new stdClass();
            $categoryObject->name = html_entity_decode($category->name);
            $categoryObject->term_id = $category->term_id;
            $categoryObject->slug = $category->slug;
            $categoryObject->parent = $category->parent;
            $categoryObject->image = get_option( 'productstax_term_images');

            foreach(get_option( 'productstax_term_images') as $catId=>$imageId){
                if($catId == $categoryObject->term_id){
                    $imageResult = wp_get_attachment_image_src( $imageId, 'thumbnail' );
                    $categoryObject->image = $imageResult[0];
                }//if
            }//foreach

            $resultCategories[] = $categoryObject;

        }//foreach

        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $resultCategories
            )
        );

    }//getSubCategories

    public static function getComments(){

        $productId = filter_input(INPUT_POST,'id',FILTER_SANITIZE_STRING);
        $numberposts = filter_input(INPUT_POST,'number',FILTER_SANITIZE_STRING);
        $offset = filter_input(INPUT_POST,'offset',FILTER_SANITIZE_STRING);

        $comments = get_comments( array(
            'post_id' => $productId,
             'number' => $numberposts,
             'offset' => $offset

            )
        );

        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $comments
            )
        );


    }

    public static function addComment(){

        $name = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST,'message',FILTER_SANITIZE_STRING);
        $productId = filter_input(INPUT_POST,'id',FILTER_SANITIZE_STRING);

        $commentdata = array(
            'comment_post_ID'      => $productId,
            'comment_author'       => $name,
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_content'      => $message,
            'comment_type'         => '',
            'comment_parent'       => 0,
            'user_ID'              => 0,
        );

        // добавляем данные в Базу Данных
        $commentId = wp_new_comment( $commentdata );

        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $commentId
            )
        );
    }

    public static function updateUser(){

        $userId = intval(filter_input(INPUT_POST,'id',FILTER_SANITIZE_STRING));
        $userName = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
        $userAddress = filter_input(INPUT_POST,'address',FILTER_SANITIZE_STRING);
        $userPhone = filter_input(INPUT_POST,'phone',FILTER_SANITIZE_STRING);

        update_user_meta($userId,'name',$userName);
        update_user_meta($userId,'address',$userAddress);
        update_user_meta($userId,'phone',$userPhone);

    }//updateUser

    public static function sendMail($orderID){

        require_once "SendMailSmtpClass.php";
        $mailSMTP = new SendMailSmtpClass('info@face-to-face.ru', '12qwaszx', 'mail.face-to-face.ru', 'face-to-face');
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n"; // кодировка письма
        $headers .= "From: Sweetconfession <info@face-to-face.ru>\r\n"; // от кого письмо

        $order = new mmOrders(array('orderID' => intval($orderID)));

        $subject = "Заказ № {$order->orderID}";
        $message = "Номер вашего заказа - {$order->orderID}<br>Состав заказа:<br>";
        $message .= self::getOrderCartHtml(intval($order->orderID));
        $message .= "<br>";
        $message .= "Спасибо за покупку, наш менеджер свяжется с вами в ближайшее время!";

        $result =  $mailSMTP->send($order->senderEmail, $subject, $message, $headers); // отправляем письмо

        return $result;

    }
    
    public static function getOptions(){
            
        $options = array('address','officeNumber');
        $optionValue = array();
        
        foreach($options as $singleOption){
            $option = get_option($singleOption);
            
            $optionValue[$singleOption] = $option;
            
        }//foreach

        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $optionValue
            )
        );
        

    }//getOptions

    public static function makeOrder($params = array()){
        
        $myArray = array();
         
        
        //Если в параметре пустой массив 
        if(empty($params)){
            
            parse_str ($_REQUEST[self::getProductKey()],$myArray );
            $params = $myArray;
            
        }//if
       
        $order = new mmOrders([
            //.... $_POST
        ]);
        //$form = self::getRoboUrl($order);


        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => array( 'order' => $order, 'form' => $form)
                )//array
        );
            
    }//makeOrder

    public static function getClient($phone){

        global $wpdb;
        $query = " SELECT p.ID from {$wpdb->posts} p WHERE p.post_type = 'clients' and p.post_status = 'publish' and p.ID in ( SELECT pm.post_id from {$wpdb->postmeta} pm WHERE pm.meta_key = 'clientPhone' and pm.meta_value = '$phone' )";

        $clients = $wpdb->get_results($query);

        return $clients;

    }

    public static function usePromoCode($promoName = null){

        $result = array();

        if(!empty($promoName)){//function params
            
        }//if
        else{//Request param
            $promoCodeName = $_REQUEST['promoName'];
            
            $promoObj = get_page_by_title( $promoCodeName,OBJECT , 'PromotionalCodes' );
            $promoId = $promoObj->ID;

            $isUsed = get_post_meta($promoId,'isUsed',true);

            if($isUsed == 'no'){

                $discount = get_post_meta($promoId,'disCount',true);
                $result['status'] = 'success';
                $result['promo'] = $promoCodeName;
                $result['discount'] = $discount;

            }//if
            else{
                $result['status'] = 'error';
            }//else
            
            self::echoDataWithHeader(array(
                'header' => 'json',
                'fields' => $result
            ));
             
        }//else
        
    }//usePromoCode
    
    public static function getOrderCartHtml($orderId=-1){
        if($orderId==-1){
            $orderId = intval($_REQUEST['post']);
        }
        
        $exportOrder = new mmOrders(array('orderID' => $orderId));

        $totalAmount = 0.0;
        $productHTML = '<table style="display:block;  border-collapse: collapse; width:100%; font-size:10pt;" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td style=" border:1px solid #000; padding:10px;width:500px; padding: 5px;;">Наименование</td>
                                    <td style=" border:1px solid #000; padding:10px; width:150px;">Количество</td>
                                    <td style=" border:1px solid #000; padding:10px; width:150px;">Цена</td>
                                </tr>';

        if($exportOrder->cart != NULL && count($exportOrder->cart) != 0){
            foreach($exportOrder->cart as $cartItem){

                if($cartItem['isInCart'] == 'true'){

                    $productHTML.=  '<tr>
                                <td style=" border:1px solid #000;padding:10px;">'.$cartItem['title'].'</td>
                                <td style=" border:1px solid #000;padding:10px;">'.$cartItem['count'].'</td>
                                <td style=" border:1px solid #000;padding:10px;">'.$cartItem['OtherFields']['price'].' руб.</td></tr><tr style="background:#ccc">
                            </tr>';
                }//if



            }//foreach
        }//if


        
        $totalPrice = floatval($exportOrder->totalPrice);
        
        if(isset($exportOrder->promo) && !empty($exportOrder->promo)){
//            var_dump($exportOrder);

            $promoObj = get_page_by_title( $exportOrder->promo ,OBJECT , 'PromotionalCodes' );
            $promoId = $promoObj->ID;

            $isUsed = get_post_meta($promoId,'isUsed',true);

            if($isUsed == 'no'){

                $discount = get_post_meta($promoId,'disCount',true);

                $resultSum = $exportOrder->totalPrice - ($exportOrder->totalPrice * $discount/100);

            }//if
            
            $productHTML .= '<tr>
                   <td colspan="2" align="center" style="padding:10px; border:1px solid #000;">Без промо: '. $exportOrder->totalPrice .' руб</td>
                    <td colspan="2" align="center" style="padding:10px; border:1px solid #000;">'.$resultSum.' руб.</td>
                    </tr>';
            
            $productHTML .= '<tr>
                <td colspan="2" align="center" style="padding:10px; border:1px solid #000;">Промокод : '.$exportOrder->promo.'</td>
                <td colspan="2" align="center" style="padding:10px; border:1px solid #000;">Скидка: '.$discount.'% </td>
            </tr>';
            
        }//if
        
        $productHTML .= '<tr>
                                <td colspan="3" align="right" style="padding:10px; border:1px solid #000;">Итого : '.$totalPrice.' руб.</td>
                            </tr>
                        </tbody>
                    </table>';
        return $productHTML;

    }
    
    public static function register(){
        
        $user_name = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);
        $user_email = filter_input(INPUT_POST, 'email',FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password',FILTER_SANITIZE_STRING);
        
        $result = array();
        
        $user_id = username_exists( $user_name );
        
        if ( !$user_id and email_exists($user_email) == false ) {
                $user_id = wp_create_user( $user_name, $password, $user_email );
                
                if(! is_wp_error( $user_id )){
                    
                    $result['message'] = 'register!';
                    $result['type'] = 'success';
                    $result['userId'] = $user_id;
                    $result['params'] = array($user_name,$password,$user_email);
                    
                }//if
                else{
                    $result['message'] = 'user not created';
                    $result['type'] = 'error';
                    $result['errorMessage'] = $user_id->get_error_message();
                    
                }//else
                
        } else {
            $result['message'] = 'login already exist!';
            $result['type'] = 'error';
        }//else
        
        self::echoDataWithHeader(array(
                'header' => 'json',
                'fields' => $result
        ));
    }//register
    
    public static function authorize(){
        
        $login = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password',FILTER_SANITIZE_STRING);



        $User = self::getPostWithParams(array(

            'type' => 'cityworkers',
            'meta_query_set' => '',
            'meta_query_values' => array(

                array(
                    'key' => 'adminLogin',
                    'value' => $login,
                ),
                array(
                    'key' => 'adminPassword',
                    'value' => $password,
                )
            )

        ));

        $result = array();

        if(count($User) > 0){

            $result['status'] = 'success';
            $result['user'] = array(
                'login' =>  $User[0]->OtherFields['adminLogin'],
                'name' => $User[0]->OtherFields['adminName'],
                'lastName' => $User[0]->OtherFields['adminLastName'],
                'password' => $User[0]->OtherFields['adminPassword']
            );
            $result['params'] = array($login,$password);
        }//if
        else{

            $User = self::getPostWithParams(array(

                'type' => 'cinemaworkers',
                'meta_query_set' => '',
                'meta_query_values' => array(

                    array(
                        'key' => 'adminLogin',
                        'value' => $login,
                    ),
                    array(
                        'key' => 'adminPassword',
                        'value' => $password,
                    )
                )

            ));
            if(count($User) > 0){

                $result['status'] = 'success';
                $result['user'] = array(
                    'login' =>  $User[0]->OtherFields['adminLogin'],
                    'name' => $User[0]->OtherFields['adminName'],
                    'lastName' => $User[0]->OtherFields['adminLastName'],
                    'password' => $User[0]->OtherFields['adminPassword']
                );
                $result['params'] = array($login,$password);
            }//if
            else{
                $result['status'] = 'error';
                $result['message'] = 'Пользователь не найден';
            }//else

        }//else

        $user = wp_signon([
            'email' => '',
            'pass' => ''
        ] , true);

        self::echoDataWithHeader(array(
            'header' => 'json',
            'fields' => $result,
        ));
        
    }//authorize

    public static function adminAuthorize(){

        $login = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password',FILTER_SANITIZE_STRING);
        $result = array();

        $user = wp_signon(
            array(
                'user_login' => $login,
                'user_password' => $password
            ));

        if ( is_wp_error($user) ){
            $result['status']='error';
            $result['message']='auth error';
        }
        else{
            $result['status']='success';
        }

        self::echoDataWithHeader(array(
            'header' => 'json',
            'fields' => $result
        ));

    }

    public static function savePhoto(){

        $postID = intval($_REQUEST['post_id']);
        $thumb_id = get_post_thumbnail_id($postID);

        $url = wp_get_attachment_image_src($thumb_id,'full');//
        $ob_url =$url[0];
        $file = basename($ob_url);
        $uplDir = wp_upload_dir();

        $fullPath = $uplDir['basedir'] . "/userconfessions/$file" ;

        if (file_exists($fullPath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$file.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit();
        }//if
        else{

            echo 'file not found';
            echo $fullPath;
            exit();

        }//else

    }

    public static function getSocials(){

        $socials = self::getPostWithParams(array(

            'type' => 'socials',
            'meta_key' => 'isShowOnSite',
            'meta_value' => 'yes'

        ));

        $resultArray = array();

        foreach($socials as $sSocial){

            $users = self::getPostWithParams(array(

                'type' => 'userphoto',
                'meta_query_set' => '',
                'meta_query_values' => array(

                    array(
                        'key' => 'isShowOnSite',
                        'value' => 'yes',
                    ),
                    array(
                        'key' => 'socilas',
                        'value' => $sSocial->title,
                    )
                )

            ));

            $toSocialAdd = array();

            foreach($users as $user){

                $toSocialAdd[] = array(
                    'vk' => $user->vkImage,
                    'fb' =>$user->fbPhoto,
                    'inst' => $user->instagramPhoto,
                    'img' => $user->image
                );

            }//foreach

            $videoID = intval($sSocial->OtherFields['groupVideo']);

            $videoLink = get_post_meta($videoID,'videoLink',true);
            $socialID = intval($sSocial->id);

            $resultArray[] = array(

                'image' => $sSocial->image,
                'id' => $sSocial->id,
                'title' =>  $sSocial->title,
                'groupLink' => $sSocial->OtherFields['groupLink'],
                'groupConfession' =>  get_post_meta($socialID,'groupConfession',true),
                'publishingHref' => get_post_meta($socialID,'publishingHref',true),
                'video'     => $videoLink,
                'photos'    => $toSocialAdd,
                'style'     => $sSocial->OtherFields['style'],
                'isActive'     => $sSocial->OtherFields['isActive']
            );

        }//foreah
//        echo '<pre>';
//        echo var_dump($resultArray);
//        echo '</pre>';
        self::echoDataWithHeader(
            array(
                'header' => 'json',
                'fields' => $resultArray
            )
        );


    }//getSocials

    public static function getOrders()
    {

        $login = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);

        $User = self::getPostWithParams(array(

            'type' => 'cinemaworkers',
            'meta_key' => 'adminLogin',
            'meta_value' => $login

        ));

        $result = array();

        if( count($User) != 0){

            //$result['user'] = $User;
            $cinemaPostID = intval($User[0]->OtherFields['cinemas']);
            $orders = self::getPostWithParams(array(

                'type' => 'myorder',
                'class' => 'mmOrders',
                'meta_key' => 'city',
                'meta_value' => $cinemaPostID

            ));
            $result['orders'] = $orders;
            $result['status'] = 'success';

        }//if
        else{

            $User = self::getPostWithParams(array(

                'type' => 'cityworkers',
                'meta_key' => 'adminLogin',
                'meta_value' => $login

            ));

            if( count($User) != 0){

                //$result['user'] = $User;
                $cityPostID = intval($User[0]->OtherFields['city']);

                global $wpdb;

                $cityID = $wpdb->get_results("select post_id from {$wpdb->postmeta} where meta_key = 'cityNumber' and meta_value = '$cityPostID' ");
                $cityID = $cityID[0];

                $orders = self::getPostWithParams(array(

                    'type' => 'myorder',
                    'class' => 'mmOrders',
                    'meta_key' => 'city',
                    'meta_value' => $cityID->post_id

                ));
                $result['orders'] = $orders;
                $result['status'] = 'success';

            }//if
            else{
                $result['status'] = 'error';
            }//else

        }//else


        $result['statuses'] = array(

            array('key' => 'payed','value' => 'Оплачен'),
            array('key'=> 'notPayed', 'value' => 'Не оплачен'),
            array('key'=> 'complited', 'value' => 'Завершен'),
            array('key'=> 'canceled', 'value' => 'Отменен')


        );

        self::echoDataWithHeader(array(
            'header' => 'json',
            'fields' => $result
        ));
    }//getOrders

    public static function getAdminOrders(){

        $login = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password',FILTER_SANITIZE_STRING);
        $result = array();

        $user = wp_signon(
            array(
                'user_login' => $login,
                'user_password' => $password
        ));

        if ( is_wp_error($user) ){
            $result['status']='error';
            $result['message']='auth error';
        }
        else{

            $orders = self::getPostWithParams(array(

                'type' => 'myorder',
                'class' => 'mmOrders'

            ));
            $result['status'] = 'success';
            $result['orders'] = $orders;
            $result['statuses'] = array(

                array('key' => 'payed','value' => 'Оплачен'),
                array('key'=> 'notPayed', 'value' => 'Не оплачен'),
                array('key'=> 'complited', 'value' => 'Завершен'),
                array('key'=> 'canceled', 'value' => 'Отменен')


            );

            $workers = self::getPostWithParams(array(

                'type' => 'cinemaworkers'

            ));

            $workArray = array();

            foreach($workers as $worker){

                $workArray[] = $worker->OtherFields;

            }//foreach

            $workers = self::getPostWithParams(array(

                'type' => 'cityworkers'

            ));

            foreach($workers as $worker){

                $workArray[] = $worker->OtherFields;
            }//foreach

            $result['workers'] =$workArray;

        }



        self::echoDataWithHeader(array(
            'header' => 'json',
            'fields' => $result
        ));

    }

    public static function getRoboUrl(mmOrders $Order){

        $orderID = intval($Order->orderID);

        $mrh_login = "SWEETCONFESSION";
        $mrh_pass1 ="ckfdfcdznjq3" ;//"khF6kBi3H5CI3rVAa8rl"

        // номер заказа
        // number of order
        $inv_id = $orderID;

        // описание заказа
        // order description

        $inv_desc = "Оплата заказа № $inv_id";

        // сумма заказа
        // sum of order
         $out_summ = intval($Order->totalPrice);

        if($Order->promo){
            $promoObj = get_page_by_title( $Order->promo ,OBJECT , 'PromotionalCodes' );
            $promoId = $promoObj->ID;

            $isUsed = get_post_meta($promoId,'isUsed',true);

            if($isUsed == 'no'){

                $discount = get_post_meta($promoId,'disCount',true);

                $out_summ -= ($out_summ * $discount/100);

            }//if


        }

        // язык
        // language
        $culture = "ru";

        // кодировка
        // encoding
        $encoding = "utf-8";

        // формирование подписи
        // generate signature
        $crc  = md5("$mrh_login:$out_summ:$inv_id:$mrh_pass1");

       $result = array(
           "MrchLogin" => $mrh_login,
           "OutSum"    => $out_summ,
           "InvId"     => $inv_id,
           "Desc"      => $inv_desc,
           "SignatureValue" => $crc,
           "Shp_item"       => '',
           "Culture"        => $culture
       );

        return $result;

    }

    public static function getRoboResponse(){

        $orderID = intval($_REQUEST["InvId"]);

        $mrh_login = "SWEETCONFESSION";
        $mrh_pass1 ="ckfdfcdznjq3" ;//"khF6kBi3H5CI3rVAa8rl"

        // номер заказа
        // number of order
        $inv_id = $orderID;


        $Order = new mmOrders(array('orderID' => $orderID));

        // сумма заказа
        // sum of order

        $out_summ = intval($Order->totalPrice);

        if(isset($Order->promo) && ! empty($Order->promo)){

            echo 'promo!<br>';

            $promoObj = get_page_by_title( $Order->promo ,OBJECT , 'PromotionalCodes' );
            $promoId = $promoObj->ID;

            $isUsed = get_post_meta($promoId,'isUsed',true);

            if($isUsed == 'no'){

                $discount = get_post_meta($promoId,'disCount',true);

                $out_summ -= ($out_summ * $discount/100);

            }//if


        }

        $crc  = md5("$out_summ:$inv_id:$mrh_pass1");

        $REQUEST_SUMM = intval($_POST["OutSum"]);

        $result = array();

        if($REQUEST_SUMM == $out_summ && $crc ==  $_POST["SignatureValue"]){

            $result['type'] = 'success';

            update_post_meta($orderID,'userOrderStatus','payed');
            if(isset($Order->promo) && ! empty($Order->promo)){

                $promoObj = get_page_by_title( $Order->promo ,OBJECT , 'PromotionalCodes' );
                $promoId = $promoObj->ID;

                $isUsed = get_post_meta($promoId,'isUsed',true);

                if($isUsed == 'no'){

                    $discount = update_post_meta($promoId,'isUsed','yes');

                }//if

            }//if

        }//if
        else{

            $result['type'] = 'error';

            $result['fields'] = array(
                'SignatureValue' => $_POST["SignatureValue"],
                '$crc' => $crc
            );

        }//else

        if($result['type'] == 'success'){

            header("location:http://sweetconfession.ru/successOrder/{$Order->orderID}");

        }
        else{

            header("location:http://sweetconfession.ru/error");
        }

    }

    public static function deleteOrder(){

        $login = filter_input(INPUT_POST, 'login',FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password',FILTER_SANITIZE_STRING);
        $result = array();
        $isAuthorized = false;

        $user = wp_signon(
            array(
                'user_login' => $login,
                'user_password' => $password
            ));

        if(is_wp_error($user)){//Не главный админ

            $user = self::getPostWithParams(array(

                'type' => 'cityworkers',
                'meta_query_set' => '',
                'meta_query_values' => array(

                    array(
                        'key' => 'adminLogin',
                        'value' => $login,
                    ),
                    array(
                        'key' => 'adminPassword',
                        'value' => $password,
                    )
                )

            ));

            if(count($user) == 0){

                $user = self::getPostWithParams(array(

                    'type' => 'cinemaworkers',
                    'meta_query_set' => '',
                    'meta_query_values' => array(

                        array(
                            'key' => 'adminLogin',
                            'value' => $login,
                        ),
                        array(
                            'key' => 'adminPassword',
                            'value' => $password,
                        )
                    )

                ));

                if(count($user) == 0){

                    $isAuthorized = false;

                }//if
                else{
                    $isAuthorized = true;
                }//else

            }//if
            else{

                $isAuthorized = true;

            }//else

        }//if

        if ($isAuthorized){
            $orderID = intval($_REQUEST['id']);

            $resultDelete = wp_delete_post($orderID);

            if($resultDelete){
                //delete_post_meta()

                delete_post_meta($orderID, 'recipientName');
                delete_post_meta($orderID, 'recipientMail');

                delete_post_meta($orderID, 'recipientPhone');
                delete_post_meta($orderID, 'cart');

                delete_post_meta($orderID, 'city');
                delete_post_meta($orderID, 'cityTitle');

                delete_post_meta($orderID, 'senderName');
                delete_post_meta($orderID, 'senderMail');
                delete_post_meta($orderID, 'senderPhone');

                delete_post_meta($orderID, 'totalPrice');

                delete_post_meta($orderID, 'deliveryAddress');
                delete_post_meta($orderID, 'isOnWall');

                delete_post_meta($orderID, 'orderDate');
                delete_post_meta($orderID, 'orderTime');

                delete_post_meta($orderID, 'userOrderStatus');

                delete_post_meta($orderID, 'templateNumber');
                delete_post_meta($orderID, 'templateText');

                $result['status'] = 'success';

            }//if
            else{

                $result['status'] = 'error';
                $result['status'] = 'Ошибка при удалении';

            }//else

        }//if
        else{

            $result['status'] = 'error';
            $result['status'] = 'Ошибка авторизации';

        }//else

        self::echoDataWithHeader(array(
            'header' => 'json',
            'fields' => $result,
        ));

    }//deleteOrder


    public static function importClients(){
        set_time_limit(3600);

        $orders = array(
            array('id' => '39','senderPhone' => '+79150983328','receiverPhone' => '+79269843678','address' => 'Малая Тульска 5/2 кв 53','promoId' => '0','status' => '5','senderName' => 'Olga','receiverName' => 'olga','senderEmail' => 'korsakova.o@gmail.com','receiverEmail' => 'korsakova.o@gmail.com','confession' => 'love','sendId' => '44566543432323456','fileName' => '54ec510659628.jpg'),
            array('id' => '40','senderPhone' => '+9kljdlskjf','receiverPhone' => '+9dfkfjkds','address' => 'dkjflkdfjlksdjf','promoId' => '0','status' => '0','senderName' => 'ksldkf;sfk','receiverName' => 'ksdjflkjflkj','senderEmail' => 'korsakova.o@gmail.com','receiverEmail' => 'korsakova.o@gmail.com','confession' => 'dklkdsjflksdjf','sendId' => '','fileName' => ''),
            array('id' => '41','senderPhone' => '+796600044323','receiverPhone' => '+796600044323','address' => 'вапрорккеппукегнрвап кореглгшл','promoId' => '9','status' => '0','senderName' => 'ывапро','receiverName' => 'аапро','senderEmail' => 'ваап@dfg','receiverEmail' => 'ваап@dfg','confession' => 'ваппрооол','sendId' => '','fileName' => ''),
            array('id' => '42','senderPhone' => 'rcr','receiverPhone' => 'rf','address' => 'rf','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '43','senderPhone' => '+7 916 153 86 95','receiverPhone' => '+7 910 454 11 94','address' => 'Ул. Верхние Поля, дом 4, кв. 32, 4 этаж','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '44','senderPhone' => '+79161538695','receiverPhone' => '+79104541194','address' => 'улица Верхние Поля, дом 4, квартира 32.  08 марта 2015 года утром с 08-00 до 11-00. ','promoId' => '0','status' => '3','senderName' => 'Валерий','receiverName' => 'Ольга','senderEmail' => 'vkrukov2001@mail.ru','receiverEmail' => 'oslepuhina@mail.ru','confession' => 'Нет теплей твоих рук,
Нет светлей твоих глаз!
Пусть не будет разлук
И печалей у нас!
Пусть веселье и радость
Будут рядом всегда.
Чтобы горе и старость
Не пришли никогда.
Пусть все дни, как заря,
Будут вечно ясны,
И пусть в сердце живет
Состояние весны!
','sendId' => '','fileName' => ''),
            array('id' => '45','senderPhone' => '+3569884568','receiverPhone' => '+3569884568','address' => 'Донецк
ул. Малая Брянская, 65','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '46','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '13','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '47','senderPhone' => '89166875510','receiverPhone' => '9168489554','address' => '2-я Новоостанкинская улица, дом 12, кв. 44','promoId' => '0','status' => '0','senderName' => 'от жены','receiverName' => 'любимому мужу','senderEmail' => '','receiverEmail' => '','confession' => 'чем дольше вместе, тем любимец и ценнее :) люблю, целую','sendId' => '','fileName' => ''),
            array('id' => '48','senderPhone' => '89150983328','receiverPhone' => '8915476595','address' => 'Малая тульская 4','promoId' => '0','status' => '1','senderName' => 'Ольги','receiverName' => 'Максиму','senderEmail' => 'korsakova.o@gmail.com','receiverEmail' => 'confessionwall@gmail.com','confession' => 'Люблю тебя','sendId' => '','fileName' => '5519b935afb74.jpg'),
            array('id' => '49','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '14','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '50','senderPhone' => '+79644473571','receiverPhone' => '+79037431176, +79264848033','address' => 'Завод ЗИЛ. Лаборатория дизайна Fashion Factory ZIL','promoId' => '15','status' => '0','senderName' => 'Федорова Ивана','receiverName' => 'Людмиле Норсоян и Марии Челяденковой (Матвеевой)','senderEmail' => '','receiverEmail' => '','confession' => 'Дорогая, Людмила, спасибо Вам за труд в индустрии моды, за Свет освещающий дорогу молодым! Спасибо за сотрудничество!
Мария, спасибо за проявленное терпение в столь долгих разговорах о работе! Желаю Вам понимающих партнеров в индустрии.

Пускай этот маленький сладкий сюрприз порадует вас за чашечкой чая или кофе. Обнимаю.

P.S. "В сознании так сложилось, что вас трое - Людмила, Мария, Стас. Потому третье пирожное для Стаса, но он теперь наверное редко с Вами..."

Привет заводу ЗИЛ и Fashion Factory из Владивостока.','sendId' => '','fileName' => ''),
            array('id' => '51','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '16','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '52','senderPhone' => '','receiverPhone' => '','address' => 'LiyhDJ  <a href="http://ajrlxvcmncoo.com/">ajrlxvcmncoo</a>, [url=http://gsiereqdneim.com/]gsiereqdneim[/url], [link=http://wrryspkedcwe.com/]wrryspkedcwe[/link], http://trptopgdmvby.com/','promoId' => '0','status' => '0','senderName' => 'rngrhueuobj','receiverName' => 'rngrhueuobj','senderEmail' => 'iqhpzi@pjjhhu.com','receiverEmail' => 'iqhpzi@pjjhhu.com','confession' => 'LiyhDJ  <a href="http://ajrlxvcmncoo.com/">ajrlxvcmncoo</a>, [url=http://gsiereqdneim.com/]gsiereqdneim[/url], [link=http://wrryspkedcwe.com/]wrryspkedcwe[/link], http://trptopgdmvby.com/','sendId' => '','fileName' => ''),
            array('id' => '53','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '17','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '54','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '18','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '55','senderPhone' => '89797','receiverPhone' => '0908','address' => 'олдодлод','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '56','senderPhone' => '434','receiverPhone' => '342','address' => 'апв','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '57','senderPhone' => '353452332','receiverPhone' => '2353253252','address' => '23 235  ssd gfgd gsd','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '58','senderPhone' => '353452332','receiverPhone' => '2353253252','address' => '23 235  ssd gfgd gsd','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '59','senderPhone' => '353452332','receiverPhone' => '2353253252','address' => '23 235  ssd gfgd gsd','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '60','senderPhone' => '353452332','receiverPhone' => '2353253252','address' => '23 235  ssd gfgd gsd','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '61','senderPhone' => '234','receiverPhone' => '234','address' => '234','promoId' => '0','status' => '0','senderName' => '234','receiverName' => '234','senderEmail' => '234@qwe','receiverEmail' => '234@qwe','confession' => '234','sendId' => '','fileName' => ''),
            array('id' => '62','senderPhone' => 'asd','receiverPhone' => 'qwr','address' => 'asf','promoId' => '0','status' => '0','senderName' => 'as','receiverName' => 'asd','senderEmail' => 'asd@asd','receiverEmail' => 'asd@asd','confession' => 'asd','sendId' => '','fileName' => ''),
            array('id' => '63','senderPhone' => 'dfg','receiverPhone' => 'dfg','address' => 'dfg','promoId' => '0','status' => '0','senderName' => 'dfg','receiverName' => 'dfg','senderEmail' => 'dfgdfg','receiverEmail' => 'dfgdfg','confession' => 'dfg','sendId' => '','fileName' => ''),
            array('id' => '64','senderPhone' => '+79267647195','receiverPhone' => '+79267533783','address' => 'Севастопольский проспект 77, корпус 2, подъезд 4, этаж 5, кв. 197. Код домофона: 197К0535','promoId' => '0','status' => '3','senderName' => 'Motograter','receiverName' => 'Kokiko','senderEmail' => 'anomonopia@gmail.com','receiverEmail' => 'anomonopia@gmail.com','confession' => '"Я очень консервативный". "Я тоже". "Она добилась своего!". Люблю тебя и сына ;)','sendId' => '','fileName' => ''),
            array('id' => '65','senderPhone' => 'WcFE2BPf','receiverPhone' => 'maQIDsTqJs','address' => 'No more s***. All posts of this qutaily from now on','promoId' => '0','status' => '0','senderName' => 'Sabrina','receiverName' => 'Sabrina','senderEmail' => 'ml34bzus2c@outlook.com','receiverEmail' => 'h7vvv8w5@yahoo.com','confession' => 'No more s***. All posts of this qutaily from now on','sendId' => '','fileName' => ''),
            array('id' => '66','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '19','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '67','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '20','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '68','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '21','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '69','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '22','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '70','senderPhone' => '87898797987','receiverPhone' => '77987987979','address' => 'jkhkhk','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '71','senderPhone' => '+79267637374','receiverPhone' => '+79267637374','address' => 'Большая Академическая дом 5А
Время доставки: 11:00-12:00','promoId' => '0','status' => '0','senderName' => 'ОТ PR- отдела.','receiverName' => 'Гениальному директору Голубевой Юлии.','senderEmail' => '','receiverEmail' => '','confession' => 'ЮЛЯ!
МЫ ТЕБЯ ЛЮБИМ!
ТВОЙ PR
','sendId' => '','fileName' => ''),
            array('id' => '72','senderPhone' => '8 9267637374','receiverPhone' => '8 926 763 73 74','address' => 'полд','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '73','senderPhone' => '+79030163417','receiverPhone' => '+79160551056','address' => 'самовывоз по адресу: 1-й Тверской-ямской переулок, д. 18, EmporioCafe','promoId' => '505','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '74','senderPhone' => '374585895','receiverPhone' => '1252525','address' => 'йепнейер','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '75','senderPhone' => '89660531010','receiverPhone' => '89030010838','address' => 'г. Москва Спасопесковский переулок дом 7/1, стр.1, после 18.00 позвонить','promoId' => '383','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '76','senderPhone' => '+79030010838','receiverPhone' => '+79650531010','address' => 'вауауауауаау','promoId' => '0','status' => '0','senderName' => 'Сергей','receiverName' => 'Ксении','senderEmail' => 'serjo90@bk.ru','receiverEmail' => 'srborisov@yandex.ru','confession' => 'Я тебя очень сильно люблю!','sendId' => '','fileName' => ''),
            array('id' => '77','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '23','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '78','senderPhone' => '+79645871118','receiverPhone' => '+79645871118','address' => 'Здравсвуйте! Подарочный сертификат был выигран на Love Radio. Расскажите, как получить торт :)','promoId' => '134','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '79','senderPhone' => '+79257725912','receiverPhone' => '+79264137618','address' => 'Москва, ул. Юных Ленинцев 85-2-59','promoId' => '0','status' => '0','senderName' => 'Галина','receiverName' => 'Павлу','senderEmail' => '','receiverEmail' => '','confession' => 'Спасибо! ','sendId' => '','fileName' => ''),
            array('id' => '80','senderPhone' => '+79646499427','receiverPhone' => '+79646499427','address' => 'Мичуринский проспект 7-366','promoId' => '613','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'От сладких девочек супер мальчику.','sendId' => '','fileName' => ''),
            array('id' => '81','senderPhone' => '+79646499427','receiverPhone' => '+79646499427','address' => 'Мичуринский проспект 7-366','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'От сладких девочек супер мальчику.','sendId' => '','fileName' => ''),
            array('id' => '82','senderPhone' => '+79646499427','receiverPhone' => '+79646499427','address' => 'Мичуринский проспект 7-366','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Сладкому мальчику от супер девочек.','sendId' => '','fileName' => ''),
            array('id' => '83','senderPhone' => '89670274759','receiverPhone' => '84959693933','address' => 'Красно студенческий проезд д 2 кВ 146, сертификат выигран на love radio','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '84','senderPhone' => '84959693933','receiverPhone' => '89670274759','address' => 'Красно студенческий проезд д2 кВ 146 , сертификат с love radio, доставка требуется на 15.10','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '85','senderPhone' => '+393335208917','receiverPhone' => '+393335208917','address' => 'hkjhkjhkhj','promoId' => '292','status' => '0','senderName' => '','receiverName' => '','senderEmail' => 'galjasv2@gmail.com','receiverEmail' => 'galjasv2@gmail.com','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '86','senderPhone' => '89670274759','receiverPhone' => '89670274759','address' => 'Красностуденческий пр. д2 , кв146 , доставка на 15.10, подарочный сертификат от love radio','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '87','senderPhone' => '89670274759','receiverPhone' => '89670274759','address' => 'Красностуденческий пр. д2 , кв146 , доставка на 15.10, подарочный сертификат от love radio','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '88','senderPhone' => '89670274759','receiverPhone' => '89670274759','address' => 'Красностуденческий пр. д2 , кв146 , доставка на 15.10, подарочный сертификат от love radio','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '89','senderPhone' => '89670274759','receiverPhone' => '89670274759','address' => 'Красностуденческий пр. д2 , кв146 , доставка на 15.10, подарочный сертификат от love radio','promoId' => '292','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '5 лет счастливы вместе','sendId' => '','fileName' => ''),
            array('id' => '90','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д. 7, стр.2 КБ "СИСТЕМА" при подходе позвонить по указанному номеру. 20-ого октября 2015 г. в первой половине дня.','promoId' => '367','status' => '3','senderName' => 'Екатерина ','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => 'С Днем Рождения, Катенька!','sendId' => '','fileName' => ''),
            array('id' => '91','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2, КБ "СИСТЕМА" при подходе позвонить по указанному номеру 20 октября 2015 г. в первой половине дня','promoId' => '93','status' => '3','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => 'Мне сегодня 30 лет :)','sendId' => '','fileName' => ''),
            array('id' => '92','senderPhone' => '+79645871118','receiverPhone' => '+79067128319','address' => 'Самовывоз. Ангарская улица, д. 45, к.2','promoId' => '0','status' => '0','senderName' => 'Дмитрий','receiverName' => 'Инна','senderEmail' => 'terexov@my.com','receiverEmail' => 'inna742008@rambler.ru','confession' => 'Любимой маме :)','sendId' => '','fileName' => ''),
            array('id' => '93','senderPhone' => '+79645871118','receiverPhone' => '+79067128319','address' => 'Самовывоз','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '94','senderPhone' => '+79645871118','receiverPhone' => '+79645871118','address' => 'Самовывоз','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '95','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '96','senderPhone' => '+7985414817','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '233','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '97','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '98','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '99','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '100','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '101','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект д.47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '102','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '103','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '104','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '105','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '106','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '107','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '108','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '109','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '110','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект, дом 47.Доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '111','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '112','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47, доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '113','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '114','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '115','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '116','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '117','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '118','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '119','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '120','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '121','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '122','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '123','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '124','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '125','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '126','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '127','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '128','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '129','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '130','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '131','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '132','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Вика Берникова.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '133','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Вика Берникова.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '134','senderPhone' => '+7904555215','receiverPhone' => '+7904555215','address' => '+7904555215','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '135','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47,','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '136','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '137','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '138','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '139','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => '+7904555215','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '140','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'ВОЛ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '141','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'ОТДЕЛЕНИЕМ УФМС РОССИИ ПО РЕСПУБЛИКЕ БАШКОРТОСТАН В АЛЬШЕЕВСКОМ РАЙОНЕ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '142','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'ОТДЕЛЕНИЕМ УФМС РОССИИ ПО РЕСПУБЛИКЕ БАШКОРТОСТАН В АЛЬШЕЕВСКОМ РАЙОНЕ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '143','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'ОТДЕЛЕНИЕМ УФМС РОССИИ ПО РЕСПУБЛИКЕ БАШКОРТОСТАН В АЛЬШЕЕВСКОМ РАЙОНЕ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '144','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'ОТДЕЛЕНИЕМ УФМС РОССИИ ПО РЕСПУБЛИКЕ БАШКОРТОСТАН В АЛЬШЕЕВСКОМ РАЙОНЕ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '145','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Dddd','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '146','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Dfgg','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '147','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом.47, доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '148','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом.47, доставка с 11-00 до 18-00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '149','senderPhone' => '+79166460786','receiverPhone' => '+79166460786','address' => 'Лубянский проезд, д. 19, стр. 1','promoId' => '283','status' => '0','senderName' => 'От Оли','receiverName' => 'Андрею','senderEmail' => '','receiverEmail' => '','confession' => 'С возвращением! я скучала.','sendId' => '','fileName' => ''),
            array('id' => '150','senderPhone' => '+79166460786','receiverPhone' => '+79166460786','address' => 'Лубянский проезд, д. 19, стр.1','promoId' => '0','status' => '0','senderName' => 'Оля','receiverName' => 'Андрей','senderEmail' => '','receiverEmail' => '','confession' => 'С возвращением! Я скучала)','sendId' => '','fileName' => ''),
            array('id' => '151','senderPhone' => '+79165147469','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 корп. 6 кв. 143. После 12 часов дня, предварительно позвонить.','promoId' => '109','status' => '0','senderName' => 'Кота','receiverName' => 'Кролику','senderEmail' => 'dag143@mail.ru','receiverEmail' => '','confession' => 'Кроля, я тебя люблю! ','sendId' => '','fileName' => ''),
            array('id' => '152','senderPhone' => '+79165147469','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 корп. 6 кв. 143. На 1 ноября после 18 часов, предварительно позвонить.','promoId' => '0','status' => '0','senderName' => 'Кота','receiverName' => 'Кролику','senderEmail' => 'dag143@mail.ru','receiverEmail' => '','confession' => 'Кроля, я тебя люблю! ','sendId' => '','fileName' => ''),
            array('id' => '153','senderPhone' => '+79165147469','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 корп. 6 кв. 143. На 1 ноября после 18 часов, предварительно позвонить.','promoId' => '0','status' => '0','senderName' => 'Кота','receiverName' => 'Кролику','senderEmail' => 'dag143@mail.ru','receiverEmail' => '','confession' => 'Кроля, я тебя люблю! ','sendId' => '','fileName' => ''),
            array('id' => '154','senderPhone' => '+79165147469','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 корп. 6 кв. 143. На 1 ноября после 18 часов, предварительно позвонить.','promoId' => '0','status' => '0','senderName' => 'Кота','receiverName' => 'Кролику','senderEmail' => 'dag143@mail.ru','receiverEmail' => '','confession' => 'Кроля, я тебя люблю!','sendId' => '','fileName' => ''),
            array('id' => '155','senderPhone' => '+79165147469','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 корп. 6 кв. 143. На 1 ноября после 18 часов, предварительно позвонить.','promoId' => '109','status' => '3','senderName' => 'Кота','receiverName' => 'Кролику','senderEmail' => 'dag143@mail.ru','receiverEmail' => '','confession' => 'Кроля, я тебя люблю!','sendId' => '','fileName' => ''),
            array('id' => '156','senderPhone' => '89037476007','receiverPhone' => '89037476007','address' => ' Хорошевское ш., д.35, корп.1. в понедельник 02.11 с 11-00 до 18-00','promoId' => '34','status' => '1','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '157','senderPhone' => '89037476007','receiverPhone' => '89037476007','address' => ' Хорошевское ш., д.35, корп.1. в понедельник 02.11 с 11-00 до 18-00','promoId' => '34','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '158','senderPhone' => '+79263306426','receiverPhone' => '+79263306426','address' => 'Осенний бульвар, дом 16, корпус 2, квартира 870, 13 подъезд, 15 этаж. Доставка 07.11.2015 с 10.00 до 16.00','promoId' => '838','status' => '3','senderName' => 'от всех','receiverName' => 'Викке','senderEmail' => 'korsakova.o@gmail.com','receiverEmail' => 'korsakova.o@gmail.com','confession' => 'Викусе с днем рождения!','sendId' => '','fileName' => ''),
            array('id' => '159','senderPhone' => '+79265546767','receiverPhone' => '+79265546767','address' => 'г. Москва, ул. Академика Анохина, д. 2, к. 2, кв. 94-95
15/11/2015 чт. 13.00 ','promoId' => '183','status' => '3','senderName' => 'Бусловский','receiverName' => 'Бусловскому ','senderEmail' => '6225138@gmail.com','receiverEmail' => 'korsakova.o@gmail.com','confession' => 'Искренне Вас благодарим!','sendId' => '','fileName' => ''),
            array('id' => '160','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2, КБ "система" 13 ноября 2015 с 10.00 до 16.00 При подходе позвонить по указанному номеру','promoId' => '333','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '161','senderPhone' => '+79164217200','receiverPhone' => '+79164217200','address' => 'ул. Палехская д. 13 кв. 286
Позвонить за час. Доставка утро, день 9.11.15
По сертификату!','promoId' => '788','status' => '3','senderName' => 'От родных!','receiverName' => 'Дедушке!','senderEmail' => '','receiverEmail' => '','confession' => 'Любимому Дедушке в день Рождения!','sendId' => '','fileName' => ''),
            array('id' => '162','senderPhone' => '+79266332222','receiverPhone' => '+79266332222','address' => 'Самовывоз в понедельник 9 ноября около 16-00','promoId' => '938','status' => '0','senderName' => '...','receiverName' => '..','senderEmail' => 'Nondoletel@yahoo.com','receiverEmail' => 'Nndoletel@yahoo.com','confession' => 'Идеальная парочка <3 ','sendId' => '','fileName' => '563f2179dadc5.jpeg'),
            array('id' => '163','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2. (КБ СИСТЕМА) Доставка 13.11.2015 с 10.00 до 16.00 При подходе позвонить по указанному номеру','promoId' => '0','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '164','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2. (КБ СИСТЕМА) Доставка 13.11.2015 с 10.00 до 16.00 При подходе позвонить по указанному номеру','promoId' => '0','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '165','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2. (КБ СИСТЕМА) Доставка 13.11.2015 с 10.00 до 16.00 При подходе позвонить по указанному номеру','promoId' => '0','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '166','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр.2 (КБ "СИСТЕМА") Доставка 13.11.2015 с 10.00 до 16.00 При подходе позвонить по указанному номеру','promoId' => '0','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '167','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'Волгоградский проспект дом 47','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '168','senderPhone' => '+79166204545','receiverPhone' => '+79166204545','address' => 'Ул. бестужевых, дом 27 А.
3 подъезд.','promoId' => '0','status' => '0','senderName' => 'Эдуарда','receiverName' => 'Лейле','senderEmail' => '','receiverEmail' => 'Laila61@bk.ru','confession' => 'Любимая жена, для тебя все самое вкусное, нежное, воздушное.
Столько лет мы уже вместе, еще столько же бы прожить рядом с тобой.
Не просто Люблю  -  обожаю!!!
','sendId' => '','fileName' => ''),
            array('id' => '169','senderPhone' => '89153165859','receiverPhone' => '89153165859','address' => 'Готова подъехать на м. Белорусская 26.11.2015 с 13:00 до 16:00','promoId' => '0','status' => '0','senderName' => 'От племянницы Александры','receiverName' => 'Лене и Мишане','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '170','senderPhone' => '89153165859','receiverPhone' => '89153165859','address' => 'Готова подъехать на м. Белорусская 26.11.2015 с 13:00 до 16:00
','promoId' => '963','status' => '3','senderName' => 'От племянницы Александры','receiverName' => 'Ленуше и Мишане','senderEmail' => 'Gostevskaya.a@mail.ru','receiverEmail' => 'Gostevskaya.a@mail.ru','confession' => 'Поздравляю Вас с днем рождения! Желаю друзей, которым можно позвонить в любое время и услышать в ответ: «Сейчас приеду». Большой любви, от которой сносит крышу и хочется петь. Здоровья, которое позволяет гулять ночь напролет и утром с улыбкой выйти на работу. Счет в банке, который позволит осуществлять заветные желания. Гармонии в душе и ощущения, что каждый прожитый день является самым счастливым!
Я Вас очень люблю❤️ Будьте счастливы и любимы!','sendId' => '','fileName' => '564f23d545e26.jpg'),
            array('id' => '171','senderPhone' => '+79164013970','receiverPhone' => '+79164013970','address' => 'Смогу забрать его 21.11.15 на м. Белорусская в с 14:00 до 17:00','promoId' => '159','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '172','senderPhone' => '+79164601556','receiverPhone' => '+79150566991','address' => 'ул.Братеевская д.10 к.1 под. 4 этаж 10 кв 225
домофон 200ключ1511, привезти после 7 вечера,желательно за час позвонить','promoId' => '514','status' => '3','senderName' => 'Папы и Мамы','receiverName' => 'Катюше','senderEmail' => '','receiverEmail' => '','confession' => 'На 6 месяцев','sendId' => '','fileName' => ''),
            array('id' => '173','senderPhone' => '89651622870','receiverPhone' => '89651622870','address' => 'Готов встретить на белоруской кольцевой 26 ноября 2015 года в 15:00','promoId' => '308','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '174','senderPhone' => '89651622870','receiverPhone' => '89651622870','address' => 'метро белорусская кольцевая, готов встретиться у метро 26 ноября 2015 года, в 15:00 часов.','promoId' => '308','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '175','senderPhone' => '+79168441336','receiverPhone' => '+79168441336','address' => 'A99638L удобно было забрать 29 ноября в 20.00','promoId' => '988','status' => '3','senderName' => 'бььбт','receiverName' => 'ьбьт','senderEmail' => 'korsakova.o@gmail.com','receiverEmail' => 'korsakova.o@gmail.com','confession' => 'бюью','sendId' => '','fileName' => ''),
            array('id' => '176','senderPhone' => '+79161687552','receiverPhone' => '+79167951019','address' => '3-я Мытищинская улица, д. 16, стр. 60','promoId' => '217','status' => '0','senderName' => 'Мамусик','receiverName' => 'Лесе','senderEmail' => '','receiverEmail' => '','confession' => 'Милому Лисенку!','sendId' => '','fileName' => ''),
            array('id' => '177','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '24','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '178','senderPhone' => '+79161687552','receiverPhone' => '+79167951019','address' => '3-я Мытищинская улица, д. 16, стр. 60','promoId' => '0','status' => '0','senderName' => 'Мамусик','receiverName' => 'Лесе','senderEmail' => '','receiverEmail' => '','confession' => 'Милому Лисенку!','sendId' => '','fileName' => ''),
            array('id' => '179','senderPhone' => '+79161687552','receiverPhone' => '+79167951019','address' => '3-я Мытищинская улица, д. 16, стр. 60','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '180','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '181','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '25','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '182','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '183','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '217','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '184','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '26','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '185','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '186','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '217','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '187','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '27','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '188','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => '3-я Мытищинская улица, д. 16, стр. 60. пятница, 04 декабря 2015 года, с 11-00 до 17-00.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '189','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'готова быть на Белорусской, с 11-00 до 15-00.','promoId' => '216','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '190','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '28','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '191','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'готова быть на Белорусской, с 11-00 до 15-00.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '192','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'готова быть на Белорусской с 11-00 до 15-00 04.12.2015','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '193','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '29','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '194','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'готова быть на Белорусской с 11-00 до 15-00 04.12.2015','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '195','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'Белорусская, 04.12.2015, с 11-00 до 15-00 ','promoId' => '217','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '196','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '30','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '197','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'Белорусская, 04.12.2015, с 11-00 до 15-00 ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '198','senderPhone' => '89854608764','receiverPhone' => '89854608764','address' => 'Метро Красносельская , ул Верхняя Красносельская д 3 стр 1 с 9-18.00','promoId' => '439','status' => '3','senderName' => 'Анастасия Королевич ','receiverName' => 'Владимиру Смагину ','senderEmail' => '','receiverEmail' => '','confession' => 'Спасибо тебе за все , я тебя очень люблю...
','sendId' => '','fileName' => '565719450afac.jpg'),
            array('id' => '199','senderPhone' => '+79266332222','receiverPhone' => '+79100038739','address' => 'Ул. Большая Черкизовская,  дом 6, корпус 8, квартира 70. На 29 ноября. ','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => 'nondoletel@yahoo.com','receiverEmail' => '','confession' => '','sendId' => '','fileName' => '565751beb840a.jpg'),
            array('id' => '200','senderPhone' => '+79100038739','receiverPhone' => '+79100038739','address' => 'На воскресенье 29 ноября','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '201','senderPhone' => '+79100038739','receiverPhone' => '+79100038739','address' => '+79100038739','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '202','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'заберу на Белорусской! :-) 04 декабря 2015 года примерно с 11 до 15. Спасибо!','promoId' => '217','status' => '0','senderName' => 'Мамусик','receiverName' => 'Лисенку','senderEmail' => '','receiverEmail' => '','confession' => 'Ты потрясающая девчонка!','sendId' => '','fileName' => ''),
            array('id' => '203','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'заберу на Белорусской! :-) 04 декабря 2015 года примерно с 11 до 15. Спасибо!','promoId' => '0','status' => '0','senderName' => 'Мамусик','receiverName' => 'Лисенку','senderEmail' => '','receiverEmail' => '','confession' => 'Ты потрясающая девчонка!','sendId' => '','fileName' => ''),
            array('id' => '204','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'заберу на Белорусской! :-) 04 декабря 2015 года примерно с 11 до 15. Спасибо!','promoId' => '0','status' => '0','senderName' => 'Мамусик','receiverName' => 'Лисенку','senderEmail' => '','receiverEmail' => '','confession' => 'Ты потрясающая девчонка!','sendId' => '','fileName' => ''),
            array('id' => '205','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'Белорусская, 04.12.2015, с 11 до 15','promoId' => '217','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '206','senderPhone' => '+79266332222','receiverPhone' => '+79100038739','address' => 'Улица Большая Черкизовская 6, корпус 8','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => 'nondoletel@yahoo.com','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '207','senderPhone' => '+79266332222','receiverPhone' => '+79100038739','address' => 'Улица Большая Черкизовская 6, корпус 8, квартира 70 ','promoId' => '938','status' => '3','senderName' => '','receiverName' => '','senderEmail' => 'nondoletel@yahoo.com','receiverEmail' => '','confession' => 'Если есть такая возможность, тортик хотели бы 2 декабря в среду :)) Спасибо заранее !!','sendId' => '','fileName' => '565c7557a7e69.jpg'),
            array('id' => '208','senderPhone' => '+79253506938','receiverPhone' => '+79253506938','address' => 'м. Арбатская, Нижний Кисловский пер., д.7, стр. КБ "СИСТЕМА" 31.12.2015 с 09.00 до 12.00 При подходе позвонить по указанному телефону','promoId' => '0','status' => '0','senderName' => 'Екатерина','receiverName' => 'Екатериа','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '209','senderPhone' => '+79253506938','receiverPhone' => '+9253506938','address' => 'м. Арбатская, Нижний Кисловский пер. д. 7, стр.2 КБ СИСТЕМА 31.12.2015 с 10.00 до 12.00 ','promoId' => '333','status' => '3','senderName' => 'Екатерина','receiverName' => 'Екатерина','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '210','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '31','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '211','senderPhone' => '+79854148171','receiverPhone' => '+79854148171','address' => 'г.Москва, Волгоградский проспект , д.47','promoId' => '233','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '212','senderPhone' => '89167311307','receiverPhone' => '89167311307','address' => 'Самовывоз','promoId' => '208','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '213','senderPhone' => '+79154481380','receiverPhone' => '+79154481380','address' => 'Новокосинская 14 к6 кв. 143. На 12 декабря в 12:00','promoId' => '638','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '214','senderPhone' => '+79160599699','receiverPhone' => '+79169260346','address' => '141281, Московская обл., г. Ивантеевка,  ул. Железнодорожная, д.1. Доставка в течение рабочего дня (с 10:00 до 16:00). Просьба предварительно позвонить.','promoId' => '0','status' => '0','senderName' => '"Малакут" Терочкина Наталья','receiverName' => 'Людмила Цуканова','senderEmail' => '','receiverEmail' => '','confession' => 'Мила, поздравляем с Днем Рожденья!
Желаем с улыбкой вставать на работу,
К труду приступать вдохновенно, с охотой
И быть у начальника вечно в почете,
И дни завершать все на радостной ноте.

Всегда быть красивой, веселой, задорной,
С любыми делами справляться проворно.
Добиться всех целей, поставленных планов,
Успешной быть, мудрой, шикарной, желанной!

А дома купаться в любви безграничной,
Всегда пребывать в настроении отличном.
Пусть ангел-хранитель Вам дарит опеку!
Чудес неземных и добра Вам, коллега!','sendId' => '','fileName' => ''),
            array('id' => '215','senderPhone' => '','receiverPhone' => '','address' => 'wFffdO  <a href="http://wepvjhfbwglx.com/">wepvjhfbwglx</a>, [url=http://efoqhcoiyyds.com/]efoqhcoiyyds[/url], [link=http://gztbasrexhij.com/]gztbasrexhij[/link], http://bmsbtissefgx.com/','promoId' => '0','status' => '0','senderName' => 'isbqnvff','receiverName' => 'isbqnvff','senderEmail' => 'npxsgf@uvopuz.com','receiverEmail' => 'npxsgf@uvopuz.com','confession' => 'wFffdO  <a href="http://wepvjhfbwglx.com/">wepvjhfbwglx</a>, [url=http://efoqhcoiyyds.com/]efoqhcoiyyds[/url], [link=http://gztbasrexhij.com/]gztbasrexhij[/link], http://bmsbtissefgx.com/','sendId' => '','fileName' => ''),
            array('id' => '216','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '32','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '217','senderPhone' => '','receiverPhone' => '','address' => 'cPWOX6  <a href="http://qklqgnsxoewa.com/">qklqgnsxoewa</a>, [url=http://blvteiovhzlq.com/]blvteiovhzlq[/url], [link=http://xqhmppjwubiw.com/]xqhmppjwubiw[/link], http://hhdondalugne.com/','promoId' => '0','status' => '0','senderName' => 'nmnpjruo','receiverName' => 'nmnpjruo','senderEmail' => 'srorzu@zulgbg.com','receiverEmail' => 'srorzu@zulgbg.com','confession' => 'cPWOX6  <a href="http://qklqgnsxoewa.com/">qklqgnsxoewa</a>, [url=http://blvteiovhzlq.com/]blvteiovhzlq[/url], [link=http://xqhmppjwubiw.com/]xqhmppjwubiw[/link], http://hhdondalugne.com/','sendId' => '','fileName' => ''),
            array('id' => '218','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '33','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '219','senderPhone' => '+79161687552','receiverPhone' => '+79161687552','address' => 'Москва, ул. 3-я Мытищинская, д. 16, стр. 60
строго до 15 часов. Спасибо за понимание!','promoId' => '738','status' => '3','senderName' => 'От Юлии и Олеси','receiverName' => 'Мамочке и бабушке!','senderEmail' => '','receiverEmail' => '','confession' => 'Мамулечка - Бабулечка! Живи долго и радостно! Здоровья тебе на долгие годы! Счастья и беззаботности! Любви во всем!','sendId' => '','fileName' => ''),
            array('id' => '220','senderPhone' => '','receiverPhone' => '','address' => 'Hi4sms  <a href="http://fnnpelgllzaz.com/">fnnpelgllzaz</a>, [url=http://lnitucaunxxm.com/]lnitucaunxxm[/url], [link=http://jyobwyeekfdx.com/]jyobwyeekfdx[/link], http://ccyafygqaekk.com/','promoId' => '0','status' => '0','senderName' => 'dtjfxa','receiverName' => 'dtjfxa','senderEmail' => 'bwabsd@syytrw.com','receiverEmail' => 'bwabsd@syytrw.com','confession' => 'Hi4sms  <a href="http://fnnpelgllzaz.com/">fnnpelgllzaz</a>, [url=http://lnitucaunxxm.com/]lnitucaunxxm[/url], [link=http://jyobwyeekfdx.com/]jyobwyeekfdx[/link], http://ccyafygqaekk.com/','sendId' => '','fileName' => ''),
            array('id' => '221','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '35','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '222','senderPhone' => '','receiverPhone' => '','address' => 'ef1AIF  <a href="http://qpwefzetskqv.com/">qpwefzetskqv</a>, [url=http://vfxuqwrewjow.com/]vfxuqwrewjow[/url], [link=http://nfdtzseruzjv.com/]nfdtzseruzjv[/link], http://kkdzkaglkfsa.com/','promoId' => '0','status' => '0','senderName' => 'bjbrld','receiverName' => 'bjbrld','senderEmail' => 'bckyei@qnsgvv.com','receiverEmail' => 'bckyei@qnsgvv.com','confession' => 'ef1AIF  <a href="http://qpwefzetskqv.com/">qpwefzetskqv</a>, [url=http://vfxuqwrewjow.com/]vfxuqwrewjow[/url], [link=http://nfdtzseruzjv.com/]nfdtzseruzjv[/link], http://kkdzkaglkfsa.com/','sendId' => '','fileName' => ''),
            array('id' => '223','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '36','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '224','senderPhone' => '89164217200','receiverPhone' => '89164217200','address' => 'ул. Палехская д. 13 кв. 286','promoId' => '358','status' => '3','senderName' => 'От меня себе!','receiverName' => 'Очаровательной Татьяне!','senderEmail' => '','receiverEmail' => 'bridzhit062006@mail.ru','confession' => 'Для себя любимой! С наилучшими пожеланиями! С Днем Рождения!
29.12.2015 г.','sendId' => '','fileName' => ''),
            array('id' => '225','senderPhone' => '89164217200','receiverPhone' => '89164217200','address' => 'ул. Палехская д. 13 кв. 286
Привезти торт 28.12.15 г. Днем!','promoId' => '0','status' => '0','senderName' => 'От меня себе!','receiverName' => 'Очаровательной Татьяне!','senderEmail' => '','receiverEmail' => 'bridzhit062006@mail.ru','confession' => 'Для себя любимой! С наилучшими пожеланиями! С Днем Рождения!
29.12.2015 г.','sendId' => '','fileName' => ''),
            array('id' => '226','senderPhone' => '','receiverPhone' => '','address' => 'gBcX4r  <a href="http://ybohbiceamkl.com/">ybohbiceamkl</a>, [url=http://mdpbageyazpm.com/]mdpbageyazpm[/url], [link=http://anqklcyvgloy.com/]anqklcyvgloy[/link], http://tbfeskphrhkc.com/','promoId' => '0','status' => '0','senderName' => 'sbvuulqm','receiverName' => 'sbvuulqm','senderEmail' => 'owjqim@nzagqw.com','receiverEmail' => 'owjqim@nzagqw.com','confession' => 'gBcX4r  <a href="http://ybohbiceamkl.com/">ybohbiceamkl</a>, [url=http://mdpbageyazpm.com/]mdpbageyazpm[/url], [link=http://anqklcyvgloy.com/]anqklcyvgloy[/link], http://tbfeskphrhkc.com/','sendId' => '','fileName' => ''),
            array('id' => '227','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '37','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '228','senderPhone' => '89857515603','receiverPhone' => '89857515603','address' => '30.12.15 в 17:00','promoId' => '65','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '229','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'м.Беллорусская,удобно забрать вечером с17ч до 18ч','promoId' => '763','status' => '3','senderName' => '','receiverName' => 'любимой семье','senderEmail' => '','receiverEmail' => '','confession' => 'я хочу что бы  Новый год мы прожили с любовью.','sendId' => '','fileName' => ''),
            array('id' => '230','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'Белорусская ,могу забрать 30.12.15 с 17ч-18ч-19ч','promoId' => '763','status' => '1','senderName' => '','receiverName' => 'любимой семье','senderEmail' => '','receiverEmail' => '','confession' => 'Пусть Новый год будет полон любовью!','sendId' => '','fileName' => ''),
            array('id' => '231','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'м.белорусская готова забрать 30.12.15. с 17ч-18ч-19ч','promoId' => '59','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Пусть Новый год принесет много любви и радости!','sendId' => '','fileName' => ''),
            array('id' => '232','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'м.белорусская готова забрать 30.12.15 с 17ч-18ч-19ч','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Пусть Новый год будет наполнен счастьем и любовью!','sendId' => '','fileName' => ''),
            array('id' => '233','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'м.белорусская готова забрать 30.12.15 с 17ч-18ч-19ч','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Пусть Новый год будет наполнен счастьем и любовью!','sendId' => '','fileName' => ''),
            array('id' => '234','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'м.белорусская готова забрать 30.12.15 с 17ч-18ч-19ч','promoId' => '604','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Пусть Новый год будет наполнен счастьем и любовью!','sendId' => '','fileName' => ''),
            array('id' => '235','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'метро белорусская готова забрать 30.12.15 в 17ч-19ч','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '236','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'самовывоз','promoId' => '941','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '237','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'Верхоянская 10 кв6','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'С Новым годом:)','sendId' => '','fileName' => ''),
            array('id' => '238','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'Верхоянская 10 кв6','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'С Новым годом:)','sendId' => '','fileName' => ''),
            array('id' => '239','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'Верхоянская 10 кв6','promoId' => '392','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'С Новым годом:)','sendId' => '','fileName' => ''),
            array('id' => '240','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'Самовывоз, хочу забрать торт 29 декабря , вечером.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '241','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'Самовывоз, хочу забрать торт 29 декабря , вечером.','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '242','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'самовывоз 28 декабря ,после 19.00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '243','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'самовывоз 28 декабря ,после 19.00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '244','senderPhone' => '89031573287','receiverPhone' => '89031573287','address' => 'самовывоз 28 декабря ,после 19.00','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '245','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'У. Верхоянская д10 кв6','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '246','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'Метро белорусская','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '247','senderPhone' => '89163164407','receiverPhone' => '89163164407','address' => 'М.Белорусская','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '248','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'метро белорусская 30.12.15 с 17ч-19ч','promoId' => '433','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '249','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'метро белорусская 30.12.15 с 17ч-19ч','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '250','senderPhone' => '+79629622702','receiverPhone' => '+79629622702','address' => 'самовывоз','promoId' => '913','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '251','senderPhone' => '89636256465','receiverPhone' => '89636256465','address' => 'Метро беллорусская с 17-18ч 30.12.15','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '252','senderPhone' => '+79163135278','receiverPhone' => '+79163135278','address' => 'ул.2-ая Пугачевская дом 8, корп 5, квартира 112
Привезти 01.01.2016','promoId' => '118','status' => '3','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '253','senderPhone' => '9152293149','receiverPhone' => '9152293149','address' => 'Большая Черкизовская, дом 1, корпус 2, квартира 3','promoId' => '0','status' => '0','senderName' => 'Малой','receiverName' => 'Коту','senderEmail' => '','receiverEmail' => '','confession' => 'Мяу, люблю тебя ','sendId' => '','fileName' => ''),
            array('id' => '254','senderPhone' => '+79037462567','receiverPhone' => '+79037462567','address' => 'Ленинский прт 43, 161. Александру Семенову. Признаний не надо. Форма оплаты оговорена с О. Корсаковой. Нужно срочно завтра в течение дня','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '255','senderPhone' => '+7 903 746 2567 ','receiverPhone' => '+7 903 746 2567 ','address' => 'Ленинский 43, 161','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '256','senderPhone' => '+79150983328','receiverPhone' => '+79150983328','address' => 'f,mnvg,mdfnv','promoId' => '14','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '257','senderPhone' => '+79150983328','receiverPhone' => '+79150983328','address' => 'f,mnvg,mdfnv','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '258','senderPhone' => '+79150983328','receiverPhone' => '+79150983328','address' => 'f,mnvg,mdfnv','promoId' => '14','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '259','senderPhone' => '+79037462567','receiverPhone' => '+79037462567','address' => 'о месте встрече договоримся','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => 'semenov.venture@mail.ru','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '260','senderPhone' => '','receiverPhone' => '','address' => '3fgaDi  <a href="http://ywehaggnwpxg.com/">ywehaggnwpxg</a>, [url=http://ijsakgqwkuld.com/]ijsakgqwkuld[/url], [link=http://xtffhqlcgqcv.com/]xtffhqlcgqcv[/link], http://ddkhfktzamgr.com/','promoId' => '0','status' => '0','senderName' => 'ulasyj','receiverName' => 'ulasyj','senderEmail' => 'blmwtu@hjwojm.com','receiverEmail' => 'blmwtu@hjwojm.com','confession' => '3fgaDi  <a href="http://ywehaggnwpxg.com/">ywehaggnwpxg</a>, [url=http://ijsakgqwkuld.com/]ijsakgqwkuld[/url], [link=http://xtffhqlcgqcv.com/]xtffhqlcgqcv[/link], http://ddkhfktzamgr.com/','sendId' => '','fileName' => ''),
            array('id' => '261','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '13','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '262','senderPhone' => '','receiverPhone' => '','address' => 'NKRPDh http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'NKRPDh http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '263','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '38','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '264','senderPhone' => '','receiverPhone' => '','address' => 'wlmji6 http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'wlmji6 http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '265','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '39','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '266','senderPhone' => '','receiverPhone' => '','address' => '93nrR2 http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => '93nrR2 http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '267','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '40','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '268','senderPhone' => '','receiverPhone' => '','address' => 'USyVuW http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'USyVuW http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '269','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '41','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '270','senderPhone' => '+77779716790','receiverPhone' => '+79687619220','address' => 'Москва, Пролетарский проспект, дом 16 корпус 3 кв.95','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => 'Леркин, люблю тебя!))','sendId' => '','fileName' => ''),
            array('id' => '271','senderPhone' => '','receiverPhone' => '','address' => 'JqjEdO http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'JqjEdO http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '272','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '42','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '273','senderPhone' => '','receiverPhone' => '','address' => 'zLq7PO http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'zLq7PO http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '274','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '43','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '275','senderPhone' => '','receiverPhone' => '','address' => 'i7hopX http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'i7hopX http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '276','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '44','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '277','senderPhone' => '','receiverPhone' => '','address' => 'gBg9AV http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'gBg9AV http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '278','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '45','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '279','senderPhone' => '','receiverPhone' => '','address' => 'RCXPHw http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'RCXPHw http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '280','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '46','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '281','senderPhone' => '','receiverPhone' => '','address' => 'oKZfLM http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'oKZfLM http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '282','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '47','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '283','senderPhone' => '','receiverPhone' => '','address' => 'jefgVV http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','promoId' => '0','status' => '0','senderName' => 'Mark','receiverName' => 'Mark','senderEmail' => 'mark357177@hotmail.com','receiverEmail' => 'mark357177@hotmail.com','confession' => 'jefgVV http://www.FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG.com','sendId' => '','fileName' => ''),
            array('id' => '284','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '48','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '285','senderPhone' => '65765765','receiverPhone' => '656546546','address' => 'jhjfjgfjhgjhgj','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '286','senderPhone' => '7657657','receiverPhone' => '76868','address' => 'ллррлор','promoId' => '0','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '287','senderPhone' => '','receiverPhone' => '','address' => '','promoId' => '49','status' => '0','senderName' => '','receiverName' => '','senderEmail' => '','receiverEmail' => '','confession' => '','sendId' => '','fileName' => ''),
            array('id' => '288','senderPhone' => '89150983328','receiverPhone' => '89150983328','address' => '17 проезд Марьиной рощи','promoId' => '0','status' => '0','senderName' => 'Оля','receiverName' => 'Костч','senderEmail' => 'Kojvb@hggh.ru','receiverEmail' => 'Ghhic@hgj.ru','confession' => 'Рооит','sendId' => '','fileName' => ''),
            array('id' => '289','senderPhone' => '+79688749520','receiverPhone' => '+79653027750','address' => 'Очень хотелось бы сделать доставку по адресу МО, Красногорский р-н. п. Отрадное, д. 15, кв. 3
это 4 км. от м. Пятницкое шоссе (р-н Митино). от Мкад 8 км.
в качестве исключения.','promoId' => '0','status' => '0','senderName' => 'От любимого мужа ','receiverName' => 'любимой жене ','senderEmail' => 'strsrg@yandex.ru','receiverEmail' => '','confession' => 'ты самая лучшая ','sendId' => '','fileName' => ''),
            array('id' => '290','senderPhone' => '+7957865454','receiverPhone' => '+9078967865','address' => 'дшрдрамопмлпилпло','promoId' => '0','status' => '0','senderName' => 'оориор','receiverName' => 'мрпь','senderEmail' => 'рекагнпал','receiverEmail' => '','confession' => 'рплпдгл','sendId' => '','fileName' => ''),
            array('id' => '291','senderPhone' => '+789 1011845','receiverPhone' => '+7891011845','address' => 'TEST','promoId' => '0','status' => '0','senderName' => 'fff','receiverName' => 'fff','senderEmail' => 'fff','receiverEmail' => '','confession' => 'fffff','sendId' => '','fileName' => ''),
            array('id' => '292','senderPhone' => '324234','receiverPhone' => '23432','address' => 'df gdsfg dfgdfs','promoId' => '0','status' => '0','senderName' => 'sfasef','receiverName' => 'sdfsd','senderEmail' => 'werw@ster.ru','receiverEmail' => 'jshfdx@sjfo.ru','confession' => 'sdf sdf sdf','sendId' => '','fileName' => ''),
            array('id' => '293','senderPhone' => '324234','receiverPhone' => '23432','address' => 'df gdsfg dfgdfs','promoId' => '0','status' => '0','senderName' => 'sfasef','receiverName' => 'sdfsd','senderEmail' => 'werw@ster.ru','receiverEmail' => 'jshfdx@sjfo.ru','confession' => 'sdf sdf sdf','sendId' => '','fileName' => '')
        );

        foreach($orders as $order){

            $clients = self::getClient($order['senderPhone']);

            if(count($clients) == 0){

                $clientFiedls = array(

                    'post_title'    => wp_strip_all_tags( $order['senderName'] ),
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type' => 'clients'

                );

                $post_id = wp_insert_post( $clientFiedls );

                update_post_meta($post_id,'city', $order['address'] );
                update_post_meta($post_id,'clientName',$order['senderName'] );
                update_post_meta($post_id,'clientLastName', $order['senderName'] );
                update_post_meta($post_id,'clientEmail', $order['senderEmail'] );
                update_post_meta($post_id,'clientPhone', $order['senderPhone'] );
                update_post_meta($post_id,'clientStatus', 'sender' );

            }

            $clients = self::getClient($order['receiverPhone']);

            if(count($clients) == 0){

                $clientFiedls = array(

                    'post_title'    => wp_strip_all_tags( $order['receiverName'] ),
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type' => 'clients'

                );

                $post_id = wp_insert_post( $clientFiedls );

                update_post_meta($post_id,'city', $order['address'] );
                update_post_meta($post_id,'clientName',$order['receiverName'] );
                update_post_meta($post_id,'clientLastName', $order['receiverName'] );
                update_post_meta($post_id,'clientEmail', $order['receiverEmail'] );
                update_post_meta($post_id,'clientPhone', $order['receiverPhone'] );
                update_post_meta($post_id,'clientStatus', 'recipient' );

            }

        }//foreach

    }//importClients

}