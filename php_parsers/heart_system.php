<?php
	include_once("../php_includes/check_login_statues.php");
	if($user_ok != true || $log_username == "") {
		exit();
	}

	$one = "1";
	$zero = "0";
?>
<?php
	if(isset($_POST['type']) && isset($_POST['p']) && isset($_POST['u'])){
		$p = $_POST['p'];
		$u = mysqli_real_escape_string($conn, $_POST["u"]);
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
		if($_POST['type'] == "heart"){
			$sql = "SELECT COUNT(id) FROM heart_likes WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$p,$u);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0){
				echo "You have already liked it";
				exit();
			}else{
				$stmt->close();
				$sql = "INSERT INTO heart_likes(username, art_time, art_uname, like_time)
						VALUES (?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$log_username,$p,$u);
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
				for($i = 0; $i < count($friends); $i++){
					$friend = $friends[$i];
					$app = "Liked Article <img src='/images/likeb.png' class='notfimg'>";
					$note = $log_username.' liked an article: <br /><a href="/articles/'.$p.'/'.$log_username.'">Check it now</a>';
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
					$app = "Liked Article | your following, ".$log_username.", liked an article <img src='/images/likeb.png' class='notfimg'>";
					$note = '<a href="/articles/'.$p.'/'.$log_username.'">Check it now</a>';
					// Insert into database
					$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
					$stmt->execute();
					$stmt->close();			
				}
				mysqli_close($conn);
				echo "heart_success";
				exit();
			}
		}else if($_POST['type'] == "unheart"){
			$sql = "SELECT COUNT(id) FROM heart_likes WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$p,$u);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
			if($row_count1 > 0){
				$stmt->close();
		        $sql = "DELETE FROM heart_likes WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$log_username,$p,$u);
				$stmt->execute();
				mysqli_close($conn);
		        echo "unheart_success";
		        exit();
		    }else{
		    	mysqli_close($conn);
		        echo "You do not like this post";
		        exit();
		    }
		}
	}
?>