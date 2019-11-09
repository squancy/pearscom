<?php
    require_once '../php_includes/conn.php';
    require_once '../php_includes/check_login_statues.php';
    require_once '../tupl.php';
    $gal = "Drag & Drop";
    $str = "";
    $i = 0;
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
    		if($width < 10 || $height < 10){
    			echo "Image is too small|fail";
    			exit();
    		}
    		$db_file_name = imgHash($log_username,$fileExt);
    		if($fileSize > 5242880){
    			echo "Your image was larger than 5mb|fail";
    			exit();
    		}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
    			echo "Your image file was not png, jpg, gif or jpeg type|fail";
    			exit();
    		}else if($fileErrorMsg == 1){
    			echo "An unknown error occured|fail";
    			exit();
    		}
    		$sql = "INSERT INTO photos (user,gallery,filename,uploaddate) VALUES (?,?,?,NOW())";
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("sss",$log_username,$gal,$db_file_name);
    		$stmt->execute();
    		$stmt->close();
    		$movres = move_uploaded_file($fileTmpLoc, "../user/$log_username/$db_file_name");
    		if($movres != true){
    		    exit();
    		}
        	include_once("../php_includes/image_resize.php");
        	$wmax = 800;
        	$hmax = 600;
        	if($width > $wmax || $height > $hmax){
        		$target_file = "../user/$log_username/$db_file_name";
        	    $resized_file = "../user/$log_username/$db_file_name";
        		img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
        	}
        	$str .= $db_file_name."|";
        	if(++$i == 5){
        	    break;
        	}
        }
        echo "success|".$str;
        exit();
    }
?>