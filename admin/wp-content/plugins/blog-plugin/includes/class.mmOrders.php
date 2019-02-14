<?php

class mmOrders
{

    public $orderID;   
    
    public $name;
    public $email;
    public $phone;

    public $card;

    public $address = null;

    public $promo = null;
    
    public $cart;
    public $cartHtml = null;

    public $orderStatus;
    public $orderStatusLabel;

    public $totalPrice;

    public $discount = null;

    public function __construct($params = 0){

        if(!is_object($params) && isset($params['orderID']) && $params['orderID']!= 0){
            
            $this->orderID = $params['orderID'];
            $this->getOrder();
            
        }else if(is_object($params)){
            
            $this->orderID = $params->ID;
            $this->getOrder();
            
        }else{
            
            $orderPostParams = array(
                'post_status' => 'publish',
                'post_type' => 'order',
                'post_title' => $params[ 'name' ]
            );
            
            if(isset($params['author'])){
                $orderPostParams['post_author'] = intval($params['author']);
            }
            
            $this->orderID = wp_insert_post($orderPostParams,true);
            
            $this->recipientName = $params['recipientName'];

            $this->card = $params['card'];
            $this->email = $params['email'];

            if( isset( $params['promo'] ) ){

                $promocode = get_page_by_title( $params['promo'], OBJECT , ['promocodes'] );
                $this->discount = 0;

                if( $promocode ){

                    $discount = intval( get_post_meta( $promocode->ID , 'discount' , true));

                    $this->discount = $discount;

                }//if

            }//if

            $this->card = $params['card'];

            if(isset($params['address'])){

                $this->address = $params['address'];

            }


            if(isset($params['promo'])){
                
                $this->promo = $params['promo'];
                
            }//if

            $this->cart = $params['productList'];

            $this->totalPrice = $params['totalPrice'];

            $this->saveOrder();
            
        }//else

    }//if
    
    public function generateOrderLabel(){
        if($this->orderStatus == 'payed'){$this->orderStatusLabel = 'Оплачен';}
        else if($this->orderStatus == 'complited'){$this->orderStatusLabel = 'Завершен';}
        else if($this->orderStatus == 'canceled'){$this->orderStatusLabel = 'Отменен';}
        else {$this->orderStatus = 'notPayed'; $this->orderStatusLabel = 'Не оплачен';}
    }
    
    public function getOrder(){
        
        $post = get_post($this->orderID); 
        $this->orderName = $post->post_title;

        $this->cart = get_post_meta($this->orderID, 'cart', true);

        $this->totalPrice = get_post_meta($this->orderID, 'totalPrice', true);

        $this->recipientName  =  get_post_meta($this->orderID, 'recipientName', true);
        $this->recipientEmail =  get_post_meta($this->orderID, 'recipientMail', true);

        $this->date  =  get_post_meta($this->orderID, 'orderDate', true);
        $this->time =  get_post_meta($this->orderID, 'orderTime', true);

        $this->recipientPhone =  get_post_meta($this->orderID, 'recipientPhone', true);

        $this->senderName = get_post_meta($this->orderID, 'senderName', true);
        $this->senderEmail = get_post_meta($this->orderID, 'senderMail', true);
        $this->senderPhone =  get_post_meta($this->orderID, 'senderPhone', true);
        $this->confession =  intval(get_post_meta($this->orderID, 'templateNumber', true));
        $this->promo =  get_post_meta($this->orderID, 'promo', true);

        if($this->promo != '' && $this->promo != null){
            $promoObj = get_page_by_title( $this->promo ,OBJECT , 'PromotionalCodes' );
            $promoId = $promoObj->ID;
            $this->discount = intval(get_post_meta($promoId,'disCount',true));
        }



        if(!$this->confession){
            $this->templateText = get_post_meta($this->orderID, 'templateTextNotStandart', true);
        }
        else{
            $this->confessionText = get_post_meta($this->orderID, 'templateText', true);
        }
        $this->filePath = intval(get_post_meta($this->orderID, 'filePath', true));

        $this->image = wp_get_attachment_image_src($this->filePath,'full');//

        $this->image = $this->image[0];

        $this->city = intval(get_post_meta($this->orderID, 'city', true));
        $post = get_post($this->city);
        $cityNumber = intval(get_post_meta($post->ID, 'cityNumber', true));

        if($post->post_type != 'cinemas'){
            $this->cityTitle = get_post_meta($this->orderID, 'cityTitle', true);
        }


        $this->isOnWall = get_post_meta($this->orderID, 'isOnWall', true);

        if($post->post_type == 'cities'){
            $this->cityTitle = $post->post_title;

            $head = ajaxApi::getPostWithParams(array(
                'type'=>'cityworkers',
                'meta_key' => 'city',
                'meta_value' => $cityNumber
            ));


            $this->head = $head[0]->OtherFields;

        }//if
        else{
            $this->cinemaTitle = $post->post_title;

            $head = ajaxApi::getPostWithParams(array(
                'type'=>'cinemaworkers',
                'meta_key' => 'cinemas',
                'meta_value' => $this->city
            ));

            $this->head = $head[0]->OtherFields;


        }//else


        $this->orderStatus = get_post_meta($this->orderID, 'userOrderStatus', true);

        $this->generateOrderLabel();
    }

    public function setStatus($orderStatus){

        $this->orderStatus = $orderStatus;
        $this->generateOrderLabel();
        
    }

    public function generateName(){

        $this->orderName = "{$this->orderID} - {$this->senderName} - {$this->recipientName}";


    }

    public function saveOrder(){

        $orderPostParams = array(
            'ID' => $this->orderID,
            'post_status' => 'publish',
            'post_type' => 'orders',
            'post_title' => $this->name
        );

        $orderID = wp_update_post($orderPostParams,true);


        if(!empty($this->promo)){
            update_post_meta($orderID, 'promo', $this->promo);
        }//if

        update_post_meta($this->orderID, 'email', $this->email);
        update_post_meta($this->orderID, 'phone', $this->phone);
        update_post_meta($this->orderID, 'address', $this->address);
        update_post_meta($this->orderID, 'cart', $this->cart );
        update_post_meta($this->orderID, 'discount', $this->discount );
        update_post_meta($this->orderID, 'card', $this->card );


    }



}