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

  class ManageImage {
    public function __construct($name, $log_username) {
      $this->fileName = $_FILES[$name]["name"];
      $this->fileTmpLoc = $_FILES[$name]["tmp_name"];
      $this->fileType = $_FILES[$name]["type"];
      $this->fileSize = $_FILES[$name]["size"];
      $this->fileErrorMsg = $_FILES[$name]["error"];

      $kaboom = explode(".", $this->fileName);
      $this->fileExt = end($kaboom);
      list($this->width, $this->height) = getimagesize($this->fileTmpLoc);
      $this->db_file_name = imgHash($log_username, $this->fileExt);
    }

    public function checkErrors() {
      if ($this->width < 10 || $this->height < 10) {
        header("location: ../image_size_error");
        exit();  
      } else if($this->fileSize > 3145728) {
        header("location: ../image_bigger_error");
        exit();  
      } else if (!preg_match("/\.(gif|jpg|png|jfif|jpeg)$/i", $this->fileName)) {
        header("location: ../image_type_error");
        exit();
      } else if ($this->fileErrorMsg) {
        header("location: ../file_upload_error");
        exit();
      }
    }
    
    public function moveRes($loc) {
      $moveResult = move_uploaded_file($this->fileTmpLoc, $loc);
      if (!$moveResult) {
        header("location: ../file_upload_error");
        exit();
      }
    }

    public function resizeImg($wmax, $hmax, $target_file, $resized_file) {
      require_once '../php_includes/image_resize.php';
      img_resize($target_file, $resized_file, $wmax, $hmax, $this->fileExt);
    }
  }
?>
