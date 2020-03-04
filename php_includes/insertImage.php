<?php
  class InImage {
    public function doInsert($img) {
      $kaboom = explode(".", $img);
      $fileExt = end($kaboom);
      rename("../tempUploads/$img", "../permUploads/$img");
      require_once '../php_includes/image_resize.php';
      $target_file = "../permUploads/$img";
      $resized_file = "../permUploads/$img";
      $wmax = 800;
      $hmax = 600;
      list($width, $height) = getimagesize($target_file);
      if($width > $wmax || $height > $hmax){
        img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
      }
    } 
  }
?>
