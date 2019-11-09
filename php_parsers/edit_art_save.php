<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
	// Ajax calls this code to execute
	if(isset($_POST["u"]) && isset($_POST["p"]) && isset($_POST["texta"])){
		// Connect to the database
		require_once '../php_includes/conn.php';
		// Gather up the posted values
		$p = mysqli_real_escape_string($conn, $_POST['p']);
		$u = mysqli_real_escape_string($conn, $_POST['u']);
		$texta = mysqli_real_escape_string($conn, $_POST['texta']);
		$title = mysqli_real_escape_string($conn, $_POST['title']);
		$title = htmlentities($title);
		$img1 = "";
		$img2 = "";
		$img3 = "";
		$img4 = "";
		$img5 = "";
		if(!empty($_POST['img1'])){
			$img1 = mysqli_real_escape_string($conn, $_POST['img1']);
		}
		if(!empty($_POST['img2'])){
			$img2 = mysqli_real_escape_string($conn, $_POST['img2']);
		}
		if(!empty($_POST['img3'])){
			$img3 = mysqli_real_escape_string($conn, $_POST['img3']);
		}
		if(!empty($_POST['img4'])){
			$img4 = mysqli_real_escape_string($conn, $_POST['img4']);
		}
		if(!empty($_POST['img5'])){
			$img5 = mysqli_real_escape_string($conn, $_POST['img5']);
		}
		// Form data error handling
		$sql = "SELECT username FROM users WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows < 1){
			echo "This user does not exist";
			exit();
		}else if($p == ""){
			echo "This article does not exist";
			exit();
		}else if($texta == ""){
			echo "Please type in something first";
			exit();
		}else if(strlen($title) > 100){
		    echo "Maximum character limit for title is 100";
			exit();
		}else{
			$sql = "UPDATE articles SET content=?, title=? WHERE written_by=? AND post_time=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$texta,$title,$u,$p);
			$stmt->execute();
			$stmt->close();
			// INSERT IMAGES
			if($img1 != "" && $img1 != "no"){
				$kaboom = explode(".", $img1);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img1", "../permUploads/$img1");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img1";
				$resized_file = "../permUploads/$img1";
				$wmax = 400;
				$hmax = 500;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img1=? WHERE written_by=? AND post_time=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img1,$u,$p);
				$stmt->execute();
				$stmt->close();
			}
			if($img2 != "" && $img2 != "no"){
				$kaboom = explode(".", $img2);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img2", "../permUploads/$img2");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img2";
				$resized_file = "../permUploads/$img2";
				$wmax = 400;
				$hmax = 500;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img2=? WHERE written_by=? AND post_time=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img2,$u,$p);
				$stmt->execute();
				$stmt->close();
			}
			if($img3 != "" && $img3 != "no"){
				$kaboom = explode(".", $img3);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img3", "../permUploads/$img3");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img3";
				$resized_file = "../permUploads/$img3";
				$wmax = 400;
				$hmax = 500;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img3=? WHERE written_by=? AND post_time=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img3,$u,$p);
				$stmt->execute();
				$stmt->close();
			}
			if($img4 != "" && $img4 != "no"){
				$kaboom = explode(".", $img4);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img4", "../permUploads/$img4");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img4";
				$resized_file = "../permUploads/$img4";
				$wmax = 400;
				$hmax = 500;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img4=? WHERE written_by=? AND post_time=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img4,$u,$p);
				$stmt->execute();
				$stmt->close();
			}
			if($img5 != "" && $img5 != "no"){
				$kaboom = explode(".", $img5);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img5", "../permUploads/$img5");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img5";
				$resized_file = "../permUploads/$img5";
				$wmax = 400;
				$hmax = 500;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img5=? WHERE written_by=? AND post_time=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img5,$u,$p);
				$stmt->execute();
				$stmt->close();
			}
			// Get friends array
			$friends = array();
			// Insert into notifications
			$sql = "SELECT user1 FROM friends WHERE user2=? AND accepted=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$one);
			$stmt->execute();
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc()) { 
				array_push($friends, $row["user1"]); 
			}
			$stmt->close();
			$sql = "SELECT user2 FROM friends WHERE user1=? AND accepted=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$one);
			$stmt->execute();
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc()) { 
				array_push($friends, $row["user2"]); 
			}
			$stmt->close();
			for($i = 0; $i < count($friends); $i++){
				$friend = $friends[$i];
				$app = "Edited Article";
				$note = $log_username.' edited his/her article: <br /><a href="/articles/'.$p.'/'.$log_username.'">Check it now</a>';
				$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
				$stmt->execute();
				$stmt->close();
			}
			mysqli_close($conn);
			echo "save_success";
			exit();
		}
		exit();
	}
?>