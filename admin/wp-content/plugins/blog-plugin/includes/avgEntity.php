<?php

class avgEntity extends Entity{
    
    public $image;
    public $date;
    public $count;

    public $vkImage;
    public $fbPhoto;
    public $instagramPhoto;


    public $OtherFields;
    
    public function getEntityData($entityParams = array()){ 
       
        $news = get_post($entityParams->ID);

        $this->title = apply_filters('the_title', $news->post_title);
        $this->count = 1;
        $this->id = $news->ID;

        $thumb_id = get_post_thumbnail_id($this->id);

        $this->description = $news->post_content;//html_entity_decode(strip_tags( ));//strip_tags(strip_shortcodes ( $news->post_content ));

        $this->image = wp_get_attachment_image_src($thumb_id,'full');//

        $this->image = $this->image[0];

        $vkPhoto = wp_get_attachment_image_src($thumb_id,array(87, 83));//


        $this->vkImage = $vkPhoto[0];

        $fbPhoto = wp_get_attachment_image_src($thumb_id, array(87, 83));//

        $this->fbPhoto = $fbPhoto[0];

        $instPhoto =  wp_get_attachment_image_src($thumb_id, array(213, 213));//

        $this->instagramPhoto = $instPhoto[0];

        $this->secondImage = MultiPostThumbnails::get_post_thumbnail_url('goods', 'secondary-image',$this->id,'small');

        foreach ($this->OtherFields as $key => $value) {
                $this->OtherFields[$key] = get_post_meta($this->id,$key,true);//;
        }//foreach

        return $this;

    }//getNews

}