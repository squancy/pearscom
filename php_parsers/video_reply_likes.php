<?php
	require_once "../php_includes/check_login_statues.php";
	require_once "../php_includes/conn.php";
	require_once '../safe_encrypt.php';

	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
	$zero = "0";
    
	$vi = "";
    if(isset($_POST["vi"]) && $_POST["vi"] != ""){
        $vi = mysqli_real_escape_string($conn, $_POST["vi"]);
    }else if(isset($_SESSION["id"]) && !empty($_SESSION["id"])){
        $vi = $_SESSION["id"];
	    $vi = base64url_decode($vi, $hshkey);
    }
?>
<?php
	if(isset($_POST['type']) && isset($_POST['id'])){
		$id = preg_replace('#[^0-9]#i', '', $_POST['id']);

    if ($vi == "") {
      // Fired from index.php like so get the photo file name from status id
      $sql = "SELECT vidid FROM video_status WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i",$id);
      $stmt->execute();
      $stmt->bind_result($vi);
      $stmt->fetch();
      $stmt->close();
    }

		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$log_username,$one);
		$stmt->execute();
		$stmt->bind_result($exist_count);
		$stmt->fetch();
		if($exist_count < 1){
			mysqli_close($conn);
			echo "$user does not exist.";
			exit();
		}
		$stmt->close();
		if($_POST['type'] == "like"){
			$sql = "SELECT COUNT(id) FROM video_reply_likes WHERE user=? AND video=? AND reply=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sii",$log_username,$vi,$id);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0){
				echo "You have already liked it";
				exit();
			}else{
				$stmt->close();
				$sql = "INSERT INTO video_reply_likes(user, video, reply, like_time)
						VALUES (?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sii",$log_username,$vi,$id);
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
				echo "like_reply_success";
				exit();
			}
		}else if($_POST['type'] == "unlike"){
			$sql = "SELECT COUNT(id) FROM video_reply_likes WHERE user=? AND video=? AND reply = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sii",$log_username,$vi,$id);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
			if($row_count1 > 0){
				$stmt->close();
		        $sql = "DELETE FROM video_reply_likes WHERE user=? AND video=? AND reply = ? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sii",$log_username,$vi,$id);
				$stmt->execute();
				mysqli_close($conn);
		        echo "unlike_reply_success";
		        exit();
		    }else{
		    	mysqli_close($conn);
		        echo "You do not like this post";
		        exit();
		    }
		}
	}
?>
