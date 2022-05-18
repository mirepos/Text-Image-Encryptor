<?php /* 135 Lines */

######################################################################################
# ™ Software                                                                         #
#              _   _   _              ____        ___ _                              #
#    __ _  ___| |_| | | |___   ___™  / ___|  ___ / __| |_  _     _  __ _ _ __  ___   #
#   / _` |/ __  | | | |  __ \ / _ \  \___ \ / _ \| _|| __/| | _ | |/ _` | '__|/ __\  #
#  | (_| | (__| | | |_| |__) | (_) |  ___) | (_) | | | |_ | || || | (_| | |  |  _|   #
#   \__,_|\___,_|_|___|_,___/ \___/  |____/ \___/|_|  \__| \_____/ \__,_|_|   \___/  #
#                                                                                    #
# Copyright © 2018 - all rights reserved                                             #
######################################################################################'

//////////////////////////////////////////////////////////////////////////////////////
////// BUT DO NOT EDIT ANYTHING IF YOU ARE NOT TOTALY SHURE WHAT YOU ARE DOING! //////
//////////////////////////////////////////////////////////////////////////////////////

class ImageConverter{

  protected $image_name;
  protected $format;
  protected $quality;
  protected $size;
  protected $output_folder;
  protected $quality_value;
  protected $size_value;
  protected $output_name;

  public function __construct($args = []){
    if(!is_dir($args['output_folder']) || !is_writable($args['output_folder']))
      mkdir($args['output_folder'], 0777);
    if($args['output_folder'][strlen($args['output_folder'])-1] != '/')
      $args['output_folder'] .= '/';
    $this->output_folder = $args['output_folder'];
    $this->image_name = $args['image_name'];
    $this->format = $args['format'];
    $this->quality = $args['quality'];
    $this->size = $args['size'];
  }

  public function convert(){
    $image_source_type = $this->getSourceImageType();
    $resource = $this->makeImageResource($image_source_type);
    $this->setQualityValue();
    $this->setSizeValue($resource);
    $make_new_image = $this->makeNewImage($resource);
    if ($make_new_image)
      return $this->output_name;
    else
      return false;
  }

  protected function getSourceImageType(){
    $image_name_parts = pathinfo($this->image_name);
    return strtolower($image_name_parts['extension']);
  }

  protected function setQualityValue(){
    if($this->quality == 'high')
      $this->quality_value = 100;
    if($this->quality == 'normal')
      $this->quality_value = 75;
  }

  protected function setSizeValue($resource){
    if ($this->size == 'original')
      $this->size_value = imagesx($resource);
    elseif($this->size == 'xtralarge')
      $this->size_value = 3000;
    elseif($this->size == 'large')
      $this->size_value = 1800;
    elseif($this->size == 'medium')
      $this->size_value = 900;
    elseif($this->size == 'small')
      $this->size_value = 600;
    elseif ($this->size == 'xsmall')
      $this->size_value = 300;
  }

  protected function makeImageResource($format){
    switch ($format){
      case 'jpg':
      case 'jpeg':
        $resource = imagecreatefromjpeg($this->image_name);
        return $resource;
        break;
      case 'png':
        $resource = imagecreatefrompng($this->image_name);
        return $resource;
        break;
      case 'webp':
        $resource = imagecreatefromwebp($this->image_name);
        return $resource;
        break;
      case 'gif':
        $resource = imagecreatefromgif($this->image_name);
        return $resource;
        break;
    }
  }

  protected function makeNewImage($resource){
    $image_name_array = explode('/', $this->image_name);
    $output_name = end($image_name_array);
    $image_name_parts = pathinfo($output_name);
    $output_name = $image_name_parts['filename'];
    $scaled = imagescale($resource, $this->size_value);
    if($this->format == 'jpg' || $this->format == 'jpeg'){
      $this->output_name = $this->output_folder.$output_name.'.jpg';
      imagejpeg($scaled, $this->output_name, $this->quality_value);
      imagedestroy($scaled);
      return true;
    }elseif ($this->format == 'png'){
      $this->output_name = $this->output_folder.$output_name.'.png';
      $pngQuality = ($this->quality_value - 100) / 11.111111;
      $pngQuality = round(abs($pngQuality));
      imagepng($scaled, $this->output_name, $pngQuality); // OLD VALUE 9
      imagedestroy($scaled);
      return true;
    }elseif ($this->format == 'webp'){
      $this->output_name = $this->output_folder.$output_name.'.webp';
      imagewebp($scaled, $this->output_name);
      imagedestroy($scaled);
      return true;
    }elseif ($this->format == 'gif'){
      $this->output_name = $this->output_folder.$output_name.'.gif';
      imagegif($scaled, $this->output_name);
      imagedestroy($scaled);
      return true;
    }
    imagedestroy($resource);
    return true;
  }
}
