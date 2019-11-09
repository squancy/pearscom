<?php
	include_once("../php_includes/check_login_statues.php");
	require_once '../safe_encrypt.php';
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
	$zero = "0";
	$ph = "";
    if(isset($_POST["phot"]) && $_POST["phot"] != ""){
        $ph = mysqli_real_escape_string($conn, $_POST["phot"]);
    }else{
        $ph = $_SESSION["photo"];   
    }
?>
<?php
	if(isset($_POST['type']) && isset($_POST['id'])){
		$id = preg_replace('#[^0-9]#i', '', $_POST['id']);
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
			$sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE username=? AND status=? AND photo=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sis",$log_username,$id,$ph);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
			if($row_count1 > 0){
				echo "You have already liked it";
				exit();
			}else{
				$stmt->close();
				$sql = "INSERT INTO photo_stat_likes(username, status, photo, like_time)
						VALUES (?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sis",$log_username,$id,$ph);
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
				$phh = base64url_encode($ph,$hshkey);
				for($i = 0; $i < count($friends); $i++){
					$friend = $friends[$i];
					$app = "Liked Status Photo <img src='/images/likeb.png' class='notfimg'>";
					$note = $log_username.' liked a status: <br /><a href="/photo_zoom/'.$phh.'/'.$log_username.'">Below a photo</a>';
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
					$app = "Liked Status Photo | your following, ".$log_username.", liked status below a photo <img src='/images/likeb.png' class='notfimg'>";
					$note = '<a href="/photo_zoom/'.$phh.'/'.$log_username.'">Check it now</a>';
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
			$sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE username=? AND status=? AND photo=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sis",$log_username,$id,$ph);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
			if($row_count1 > 0){
				$stmt->close();
		        $sql = "DELETE FROM photo_stat_likes WHERE username=? AND status=? AND photo=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sis",$log_username,$id,$ph);
				$stmt->execute();
				$stmt->close();
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