<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	require_once '../safe_encrypt.php';
	require_once '../a_array.php';
	if($user_ok != true || $log_username == "") {
	    header('../index');
		exit();
	}
	// Ajax calls this code to execute
	if(isset($_POST["title"]) && isset($_POST["area"]) && isset($_POST["tags"]) && isset($_POST["cat"]) && isset($_POST["img1"]) && isset($_POST["img2"]) && isset($_POST["img3"]) && isset($_POST["img4"]) && isset($_POST["img5"])){
		// Connect to the database
		require_once '../php_includes/conn.php';
		// Gather up the posted values
		$title = mysqli_real_escape_string($conn, $_POST['title']);
		$myta = mysqli_real_escape_string($conn, $_POST['area']);
		$tags = mysqli_real_escape_string($conn, $_POST['tags']);
		$cat = mysqli_real_escape_string($conn, $_POST['cat']);
		$img1 = mysqli_real_escape_string($conn, $_POST['img1']);
		$img2 = mysqli_real_escape_string($conn, $_POST['img2']);
		$img3 = mysqli_real_escape_string($conn, $_POST['img3']);
		$img4 = mysqli_real_escape_string($conn, $_POST['img4']);
		$img5 = mysqli_real_escape_string($conn, $_POST['img5']);
		$now = date("Y-m-d H:i:s");

		// Form data error handling
		if($title == "" || $myta == "" || $tags == "" || $cat == ""){
			echo "Please fill out all the form data";
			exit();
		}else if(!in_array($cat, $a_cats)){
		    echo "Please give a valid category";
			exit();
		}else if(strlen($title) > 100){
		    echo "Maximum character limit for title is 100";
		    exit();
		}else if(strlen($tags) > 100){
		    echo "Maximum character limit for tags is 100";
		    exit();
		}else{
			// Insert into the database
			$sql = "INSERT INTO articles(written_by, title, content, tags, category, post_time)
					VALUES (?,?,?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssssss",$log_username,$title,$myta,$tags,$cat,$now);
			$stmt->execute();
			$stmt->close();

			$sql = "SELECT id FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$now);
			$stmt->execute();
			$stmt->bind_result($artid);
			$stmt->fetch();
			$stmt->close();

			// Insert the images into the database
			// Move the image(s) to the permanent folder
			if($img1 != "no" && $img1 != ""){
				$kaboom = explode(".", $img1);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img1", "../permUploads/$img1");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img1";
				$resized_file = "../permUploads/$img1";
				$wmax = 800;
				$hmax = 600;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img1 = ? WHERE post_time = ? AND written_by = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img1,$now,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($img2 != "no" && $img2 != ""){
				$kaboom = explode(".", $img2);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img2", "../permUploads/$img2");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img2";
				$resized_file = "../permUploads/$img2";
				$wmax = 800;
				$hmax = 600;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img2 = ? WHERE post_time = ? AND written_by = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img2,$now,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($img3 != "no" && $img3 != ""){
				$kaboom = explode(".", $img3);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img3", "../permUploads/$img3");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img3";
				$resized_file = "../permUploads/$img3";
				$wmax = 800;
				$hmax = 600;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img3 = ? WHERE post_time = ? AND written_by = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img3,$now,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($img4 != "no" && $img4 != ""){
				$kaboom = explode(".", $img4);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img4", "../permUploads/$img4");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img4";
				$resized_file = "../permUploads/$img4";
				$wmax = 800;
				$hmax = 600;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img4 = ? WHERE post_time = ? AND written_by = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img4,$now,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($img5 != "no" && $img5 != ""){
				$kaboom = explode(".", $img5);
				$fileExt = end($kaboom);
				rename("../tempUploads/$img5", "../permUploads/$img5");
				require_once '../php_includes/image_resize.php';
				$target_file = "../permUploads/$img5";
				$resized_file = "../permUploads/$img5";
				$wmax = 800;
				$hmax = 600;
				list($width, $height) = getimagesize($target_file);
				if($width > $wmax || $height > $hmax){
					img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
				}
				$sql = "UPDATE articles SET img5 = ? WHERE post_time = ? AND written_by = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$img5,$now,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			$nown = $now;
			$nown = base64url_encode($nown,$hshkey);
			// Insert notifications to all friends of the post author
			$friends = array();
			$one = "1";
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
				$app = "Recently Created Article <img src='/images/atrim.png' class='notfimg'>";
				$note = $log_username.' created a new article: <br /><a href="/articles/'.$nown.'/'.$log_username.'">Check it now</a>';
				// Insert into database
				$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
				$stmt->execute();
				$stmt->close();
			}

			// Send it to followers
			$followers = array();
			$sql = "SELECT * FROM follow WHERE following = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$result = $stmt->get_result();
			while($row = $result->fetch_assoc()){
				$fwer = $row["follower"];
				array_push($followers, $fwer);
			}
			$stmt->close();

			$diffarr = array_diff($followers, $friends);

			for($i = 0; $i < count($diffarr); $i++){
				$friend = $diffarr[$i];
				$app = "Recently Created Article | your following, ".$log_username.", created a new article <img src='/images/atrim.png' class='notfimg'>";
				$note = $log_username.' created a new article: <br /><a href="/articles/'.$nown.'/'.$log_username.'">Check it now</a>';
				// Insert into database
				$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
				$stmt->execute();
				$stmt->close();			
			}
			
			$sql = "SELECT avatar FROM users WHERE username = ? AND activated = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$one);
			$stmt->execute();
			$stmt->bind_result($avatar);
			$stmt->fetch();
			$stmt->close();
			
			$now = base64url_encode($now,$hshkey);
			
			mysqli_close($conn);
			echo "article_success|$avatar|$log_username|$now";
			exit();
		}
		exit();
	}
?>