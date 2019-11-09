<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
?>
<?php
	// Ajax calls this code to execute
	if(isset($_POST['type']) && isset($_POST['user'])){
		// Clean the $_POST['user'] var
		$user = mysqli_real_escape_string($conn, $_POST['user']);
		// Make sure the user exists in the database
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$user,$one);
		$stmt->execute();
		$stmt->bind_result($exist_count);
		$stmt->fetch();
		if($exist_count < 1){
			mysqli_close($conn);
			echo "$user does not exist.";
			exit();
		}
		$stmt->close();
		// Ajax calls this code to execute
		if($_POST['type'] == "follow"){
			// Check to see if the user is alerady following
			$sql = "SELECT COUNT(id) FROM follow WHERE follower=? AND following=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$user);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0){
				echo "You are already following $user";
				exit();
			}else{
				$stmt->close();
				// Insert into the database
				$sql = "INSERT INTO follow(follower, following, follow_time)
						VALUES (?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$user);
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
					$app = "New follower <img src='/images/nfol.png' class='notfimg'>";
					$note = $log_username.' started to follow '.$user.': <br /><a href="/user/'.$user.'/">Check it now</a>';
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
					$app = "New follower | your following, ".$log_username.", started to follow you <img src='/images/nfol.png' class='notfimg'>";
					$note = '<a href="/user/'.$user.'/">Check it now</a>';
					// Insert into database
					$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
					$stmt->execute();
					$stmt->close();			
				}

				mysqli_close($conn);
				echo "follow_success";
				exit();
			}
			// Ajax calls this code to execute
		}else if($_POST['type'] == "unfollow"){
			// Check to see the user is the following or the follower
			$sql = "SELECT COUNT(id) FROM follow WHERE following=? AND follower=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$user);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0 && $row_count2 == 0){
				// Delete
				$stmt->close();
		        $sql = "DELETE FROM follow WHERE following=? AND follower=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$user);
				$stmt->execute();
				$stmt->close();
				mysqli_close($conn);
		        echo "unfollow_success";
		        exit();
		    }
		    $stmt->close();

		    $sql = "SELECT COUNT(id) FROM follow WHERE following=? AND follower=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$user,$log_username);
			$stmt->execute();
			$stmt->bind_result($row_count2);
			$stmt->fetch();
		    if($row_count2 > 0 && $row_count1 == 0){
		    	// Delete
		    	$stmt->close();
		    	$sql = "DELETE FROM follow WHERE follower=? AND following=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$user);
				$stmt->execute();
				$stmt->close();
				mysqli_close($conn);
		        echo "unfollow_success";
		        exit();
		    }else if($row_count1 == 0 && $row_count2 == 0){
		    	mysqli_close($conn);
		        echo "You do not follow this user";
		        exit();
		    }
		}
	}
?>