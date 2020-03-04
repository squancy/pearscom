<?php
  require_once '../php_includes/conn.php';
  require_once '../php_includes/check_login_statues.php';
  require_once '../tupl.php';

  $gal = "Drag & Drop";
  $str = "";
  $i = 0;

  class HandleImage {
    public function __construct($log_username, $fileExt) {
      $this->db_file_name = imgHash($log_username, $fileExt);
    }

    public function errorCheck($width, $height, $fileSize, $fileName, $fileErrorMsg) {
      if($width < 10 || $height < 10){
        echo "Image is too small|fail";
        exit();
      }else if($fileSize > 5242880){
        echo "Your image was larger than 5mb|fail";
        exit();
      }else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
        echo "Your image file was not png, jpg, gif or jpeg type|fail";
        exit();
      }else if($fileErrorMsg){
        echo "An unknown error occured|fail";
        exit();
      }
    }

    public function insertToDb($conn, $log_username, $gal) {
      $sql = "INSERT INTO photos (user,gallery,filename,uploaddate) VALUES (?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $gal, $this->db_file_name);
      $stmt->execute();
      $stmt->close();
    }

    public function resizeImg($width, $height, $log_username, $fileExt) {
      require_once "../php_includes/image_resize.php";
      $wmax = 800;
      $hmax = 600;
      if($width > $wmax || $height > $hmax){
        $target_file = "../user/$log_username/$this->db_file_name";
        $resized_file = "../user/$log_username/$this->db_file_name";
        img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
      }
    }
  }

  // Loop through every image that gets uploaded
  if(!empty($_FILES['file']['name'][0]) && $_FILES['file']['tmp_name'] != ""){
    foreach($_FILES['file']['name'] as $pos => $name){
      $fileName = $_FILES["file"]["name"][$pos];
      $fileTmpLoc = $_FILES["file"]["tmp_name"][$pos];
      $fileType = $_FILES["file"]["type"][$pos];
      $fileSize = $_FILES["file"]["size"][$pos];
      $fileErrorMsg = $_FILES["file"]["error"][$pos];

      $kaboom = explode(".", $fileName);
      $fileExt = end($kaboom);
      list($width, $height) = getimagesize($fileTmpLoc);
      $imgObj = new HandleImage($log_username, $fileExt);
      $imgObj->errorCheck($width, $height, $fileSize, $fileName, $fileErrorMsg);

      // Move image to its permanent location
      $movres = move_uploaded_file($fileTmpLoc, "../user/$log_username/$imgObj->db_file_name");
      if(!$movres){
        exit();
      }

      // Resize img
      $imgObj->resizeImg($width, $height, $log_username, $fileExt);

      $imgObj->insertToDb($conn, $log_username, $gal);
      
      $str .= $imgObj->db_file_name."|";
      if(++$i == 5){
          break;
      }
    }
    echo "success|".$str;
    exit();
  }
?>
