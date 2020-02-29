<?php
	include_once("../php_includes/check_login_statues.php");
	require_once '../safe_encrypt.php';
	require_once '../tupl.php';

	if(isset($_FILES["stPic_video"]["name"]) && $_FILES["stPic_video"]["tmp_name"] != ""){
		$fileName = $_FILES["stPic_video"]["name"];
	    $fileTmpLoc = $_FILES["stPic_video"]["tmp_name"];
		$fileType = $_FILES["stPic_video"]["type"];
		$fileSize = $_FILES["stPic_video"]["size"];
		$fileErrorMsg = $_FILES["stPic_video"]["error"];
		$dur = $_POST["stVideo_dur"];
		if($dur == NULL || $dur == ""){
		    echo "Could not get video duration";
		    exit();
		}
		$description = "";
		$videoname = "";
		if($_POST["stVideo_des"] != ""){
			$description = mysqli_real_escape_string($conn, $_POST["stVideo_des"]);
		}
		if($_POST["stVideo_name"] != ""){
			$videoname = mysqli_real_escape_string($conn, $_POST["stVideo_name"]);
		}
		
		// Set the poster vars
		$posterName = "";
	    $posterTmp = "";
		$posterType = "";
		$posterSize = "";
		$posterErr = "";
		$kaboom_poster = "";
		$fileExt_poster = "";
		$poster_file = "";

		if(isset($_FILES["stPic_poster"]["name"]) && $_FILES["stPic_poster"]["tmp_name"] != ""){
			$posterName = $_FILES["stPic_poster"]["name"];
      $posterTmp = $_FILES["stPic_poster"]["tmp_name"];
			$posterType = $_FILES["stPic_poster"]["type"];
			$posterSize = $_FILES["stPic_poster"]["size"];
			$posterErr = $_FILES["stPic_poster"]["error"];

			$kaboom_poster = explode(".", $posterName);
			$fileExt_poster = end($kaboom_poster);
		}

		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);

		$db_file_name = imgHash($log_username,$fileExt);
		if($posterTmp != "" && $posterName != ""){
			$poster_file = imgHash($log_username,$fileExt_poster);
		}

		if(isset($_FILES["stPic_poster"]["name"]) && $_FILES["stPic_poster"]["tmp_name"] != ""){

			if($fileSize > 15728640) {
				echo "Video size is bigger than 15MB.";
				exit();	
			} else if (!preg_match("/\.(mp3|mp4|webm|mkv|avi|amv)$/i", $fileName) ) {
				echo "Video file type is not supported.";
				exit();
			} else if ($fileErrorMsg == 1) {
				echo "An unknown error occured.";
				exit();
			}else if($posterSize > 5242880){
				echo "Poster size is bigger than 5MB.";
				exit();	
			}else if(!preg_match("/\.(jpg|jpeg|png)$/i", $posterName) ){
				echo "Poster file type is not supported.";
				exit();
			}else if($posterErr == 1){
				echo "An unknown error occured.";
				exit();
			}
		}

		if (!file_exists("../user/$log_username/videos")) {
			mkdir("../user/$log_username/videos", 0755);
		}

		$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/videos/$db_file_name");
		if ($moveResult != true) {
			echo "An error occured during file uploading.";
			exit();
		}

		if($posterName != "" && $posterTmp != "" && $poster_file != ""){
			$moveResult_ = move_uploaded_file($posterTmp, "../user/$log_username/videos/$poster_file");
			if ($moveResult_ != true) {
				echo "An error occured during file uploading.";
				exit();
			}
		}
    
		if($description == "" && $videoname == "" && $poster_file == ""){
			$sql = "INSERT INTO videos(user, video_file, video_upload, dur) VALUES (?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description == "" && $videoname == "" && $poster_file != ""){
			$sql = "INSERT INTO videos(user, video_poster, video_file, video_upload, dur) VALUES
      (?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$log_username,$poster_file,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description == "" && $videoname != "" && $poster_file == ""){
			$sql = "INSERT INTO videos(user, video_name, video_file, video_upload, dur) VALUES (?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$log_username,$videoname,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description == "" && $videoname != "" && $poster_file != ""){
			$sql = "INSERT INTO videos(user, video_name, video_poster, video_file, video_upload, dur) VALUES (?,?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sssss",$log_username,$videoname,$poster_file,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description != "" && $videoname == "" && $poster_file == ""){
			$sql = "INSERT INTO videos(user, video_description, video_file, video_upload, dur) VALUES (?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$log_username,$description,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description != "" && $videoname == "" && $poster_file != ""){
			$sql = "INSERT INTO videos(user, video_description, video_poster, video_file, video_upload, dur) VALUES (?,?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sssss",$log_username,$description,$poster_file,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else if($description != "" && $videoname != "" && $poster_file != ""){
			$sql = "INSERT INTO videos(user, video_name, video_description, video_poster, video_file, video_upload, dur) VALUES (?,?,?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssssss",$log_username,$videoname,$description,$poster_file,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}else{
			$sql = "INSERT INTO videos(user, video_name, video_description, video_file, video_upload, dur) VALUES (?,?,?,?,NOW(),?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sssss",$log_username,$videoname,$description,$db_file_name,$dur);
			$stmt->execute();
			$stmt->close();
		}

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
			$sql = "SELECT * FROM videos WHERE video_file = ? AND user = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$db_file_name,$log_username);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$vidid = $row["id"];
			}
			$vidid = base64url_encode($vidid,$hshkey);
			$stmt->close();
			$app = "New Video Uploaded <img src='/images/nvideo.png' class='notfimg'>";
			$note = $log_username.' uploaded a new video: <br /><a href="/video_zoom/'.$vidid.'">Check it now</a>';
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
			$sql = "SELECT * FROM videos WHERE video_file = ? AND user = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$db_file_name,$log_username);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$vidid = $row["id"];
			}
			$vidid = base64url_encode($vidid,$hshkey);
			$stmt->close();
			$app = "New Video Uploaded | your following, ".$log_username.", uploaded a new video <img src='/images/nvideo.png' class='notfimg'>";
			$note = '<a href="/video_zoom/'.$vidid.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "upload_complete|";
		exit();
	}
?>
<?php
	// Video delete
	if(isset($_POST["id"]) && $_POST["id"] != "" && $_POST["type"] == "delete"){
		// Check to see if the video exists in the database
		$id = $_POST["id"];
		$id = base64url_decode($id,$hshkey);
		$sql = "SELECT * FROM videos WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows < 1){
			echo "Video does not exist";
			exit();
		}else{
			$sql = "DELETE FROM videos WHERE id=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$id);
			$stmt->execute();
			$stmt->close();
		}
		echo "delete_success";
		exit();
	}
?>
<?php
	if(isset($_POST['type']) && isset($_POST['id'])){
		$one = "1";
		$zero = "0";
		$id = $_POST['id'];
		$id = base64url_decode($id, $hshkey);
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$log_username,$one);
		$stmt->execute();
		$stmt->bind_result($exist_count);
		$stmt->fetch();
		if($exist_count < 1){
			mysqli_close($conn);
			echo "User does not exist.";
			exit();
		}
		$stmt->close();
		$sql = "SELECT COUNT(id) FROM videos WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$id);
		$stmt->execute();
		$stmt->bind_result($vidc);
		$stmt->fetch();
		if($vidc < 1){
			mysqli_close($conn);
			echo "Video does not exist.";
			exit();
		}
		$stmt->close();
		if($_POST['type'] == "like"){
			$sql = "SELECT COUNT(id) FROM video_likes WHERE username=? AND video=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$id);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0){
				echo "You have already liked it";
				exit();
			}else{
				$stmt->close();
				$sql = "INSERT INTO video_likes(username, video, like_time)
						VALUES (?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$id);
				$stmt->execute();
				$stmt->close();
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
				$id = base64url_encode($id,$hshkey);
				for($i = 0; $i < count($friends); $i++){
					$friend = $friends[$i];
					$app = "Video Like <img src='/images/likeb.png' class='notfimg'>";
					$note = $log_username.' liked a video: <br /><a href="/video_zoom/'.$id.'">Check it now</a>';
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
                $id = base64url_encode($id,$hshkey);
				for($i = 0; $i < count($diffarr); $i++){
					$friend = $diffarr[$i];
					$app = "Video Like | your following, ".$log_username.", liked a video <img src='/images/likeb.png' class='notfimg'>";
					$note = '<a href="/video_zoom/'.$id.'">Check it now</a>';
					// Insert into database
					$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
					$stmt->execute();
					$stmt->close();			
				}
				mysqli_close($conn);
				echo "like_success";
				exit();
			}
		}else if($_POST['type'] == "unlike"){
			$sql = "SELECT COUNT(id) FROM video_likes WHERE username=? AND video=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$id);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
			if($row_count1 > 0){
				$stmt->close();
		        $sql = "DELETE FROM video_likes WHERE username=? AND video=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$id);
				$stmt->execute();
				mysqli_close($conn);
		        echo "unlike_success";
		        exit();
		    }else{
		    	mysqli_close($conn);
		        echo "You do not like this post";
		        exit();
		    }
		}
	}
?>
