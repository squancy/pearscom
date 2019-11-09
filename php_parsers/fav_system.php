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
	if(isset($_POST['type']) && isset($_POST['p']) && isset($_POST['u'])){
		$p = $_POST['p'];
		$u = mysqli_real_escape_string($conn, $_POST['u']);
		// Make sure the user exists in the database
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

		// Ajax calls this code to execute
		if($_POST['type'] == "fav"){
			$sql = "SELECT COUNT(id) FROM fav_art WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$p,$u);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			// Check to see the user has already liked it
			if($row_count1 > 0){
				echo "You have already liked it";
				exit();
			}else{
				// Insert into database
				$stmt->close();
				$sql = "INSERT INTO fav_art(username, art_time, art_uname, fav_time)
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
					$sql = "SELECT * FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ss",$u,$p);
					$stmt->execute();
					$res = $stmt->get_result();
					$ptime = "";
					while($row = $res->fetch_assoc()){
						$ptime = $row["post_time"];
					}
					$stmt->close();
					$app = "Favourite Article <img src='/images/lace.png' class='notfimg'>";
					$note = $log_username.' added an article as a favourite: <br /><a href="/articles/'.$ptime.'/'.$u.'">Check it now</a>';
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
					$ptime = "";
					$sql = "SELECT * FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ss",$u,$p);
					$stmt->execute();
					$res = $stmt->get_result();
					while($row = $res->fetch_assoc()){
						$ptime = $row["post_time"];
					}
					$stmt->close();
					$app = "Favourite Article | your following, ".$log_username." added an article as a favoutite <img src='/images/lace.png' class='notfimg'>";
					$note = '<a href="/articles/'.$ptime.'/'.$u.'">Check it now</a>';
					// Insert into database
					$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
					$stmt->execute();
					$stmt->close();			
				}
				mysqli_close($conn);
				echo "fav_success";
				exit();
			}
			// Ajax calls this code to execute
		}else if($_POST['type'] == "unfav"){
			// Make sure the article is exists
			$sql = "SELECT COUNT(id) FROM fav_art WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$p,$u);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();

			if($row_count1 > 0){
				$stmt->close();
		        $sql = "DELETE FROM fav_art WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$log_username,$p,$u);
				$stmt->execute();
				$stmt->close();
				mysqli_close($conn);
		        echo "unfav_success";
		        exit();
		    }else{
		    	// Error
		    	mysqli_close($conn);
		        echo "You do not favourite this post";
		        exit();
		    }
		}
	}
?>