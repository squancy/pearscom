<?php
	// Check to see if the user is not logged in
	require_once '../php_includes/check_login_statues.php';
	if($user_ok != true || $log_username == ""){
		exit();
	}
	$one = "1";
	$zero = "0";
?>
<?php
	// Ajax calls this code to execute
	if(isset($_POST['type']) && isset($_POST['user'])){
		// Clean the $_POST['user'] variable
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
		// Ajax calls this code to execute
		$stmt->close();
		if($_POST['type'] == "friend"){
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=? AND accepted=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$user,$one,$user,$one);
			$stmt->execute();
			$stmt->bind_result($friend_count);
			$stmt->fetch();
			$stmt->close();
			
			// Error handling
			// Check to see the user is not blocking me
			$sql = "SELECT COUNT(id) FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$user,$log_username);
			$stmt->execute();
			$stmt->bind_result($blockcount1);
			$stmt->fetch();
			if ($blockcount1 > 0 && $blockcount2 == 0){
	            mysqli_close($conn);
		        echo "$user has you blocked, we cannot proceed.";
		        exit();
	        }
	        $stmt->close();
	        // Check to see I am not blocking the user
			$sql = "SELECT COUNT(id) FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$user);
			$stmt->execute();
			$stmt->bind_result($blockcount2);
			$stmt->fetch();
	        if($blockcount2 > 0 && $blockcount1 == 0){
	            mysqli_close($conn);
		        echo "You must first unblock $user in order to friend with them.";
		        exit();
	        }
	        $stmt->close();
	        // Check to see we're alerady friends
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$user,$one);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
	        if ($row_count1 > 0 && $blockcount2 == 0 && $blockcount1 == 0) {
			    mysqli_close($conn);
		        echo "You are already friends with $user.";
		        exit();
		    }
		    $stmt->close();
	        // Check to see we're already friends
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$user,$log_username,$one);
			$stmt->execute();
			$stmt->bind_result($row_count2);
			$stmt->fetch();
	        if ($row_count2 > 0 && $blockcount2 == 0 && $row_count1 == 0 && $blockcount1 == 0) {
			    mysqli_close($conn);
		        echo "You are already friends with $user.";
		        exit();
		    }
		    $stmt->close();
		    // Check to see there is a pending friend request
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$user,$zero);
			$stmt->execute();
			$stmt->bind_result($row_count3);
			$stmt->fetch();
		    if ($row_count3 > 0 && $blockcount2 == 0 && $row_count1 == 0 && $row_count2 == 0 && $blockcount1 == 0) {
			    mysqli_close($conn);
		        echo "You have a pending friend request already sent to $user.";
		        exit();
		    }
		    $stmt->close();
		    // Check to see there is a pending friend request
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$user,$log_username,$zero);
			$stmt->execute();
			$stmt->bind_result($row_count4);
			$stmt->fetch();
		    if ($row_count4 > 0 && $blockcount2 == 0 && $row_count1 == 0 && $row_count2 == 0 && $row_count3 == 0 && $blockcount1 == 0) {
			    mysqli_close($conn);
		        echo "$user has requested to friend with you first. Check your friend requests.";
		        exit();
		    }
		    $stmt->close();
		    if($row_count4 == 0 && $blockcount2 == 0 && $row_count1 == 0 && $row_count2 == 0 && $row_count3 == 0 && $blockcount1 == 0){
		    	// Insert into the database
		        $sql = "INSERT INTO friends(user1, user2, datemade) VALUES(?,?,NOW())";
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
					$app = "New Friend <img src='/images/nfri.png' class='notfimg'>";
					$note = ''.$log_username.' and '.$user.' are now friends<br /><a href="/user/'.$user.'/">Check '.$user.'&#39;s profile now</a>';
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
					$app = "New Friend <img src='/images/nfri.png' class='notfimg'>";
					$note = ''.$log_username.' and '.$user.' are now friends<br /><a href="/user/'.$user.'/">Check '.$user.'&#39;s profile now</a>';
					// Insert into database
					$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
					$stmt->execute();
					$stmt->close();			
				}

				mysqli_close($conn);
		        echo "friend_request_sent";
		        exit();
			}
			// Ajax calls this code to execute
	} else if($_POST['type'] == "unfriend"){
		// Investigate both incident
		$user = mysqli_real_escape_string($conn, $_POST["user"]);
		$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$log_username,$user,$one);
		$stmt->execute();
		$stmt->bind_result($row_count1);
		$stmt->fetch();
		$stmt->close();
		
	    if ($row_count1 > 0) {
	    	// Delete if $row_count1 is true
	        $sql = "DELETE FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$user,$one);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
	        echo "unfriend_ok";
	        exit();
	    }
	    $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$user,$log_username,$one);
		$stmt->execute();
		$stmt->bind_result($row_count2);
		$stmt->fetch();
		$stmt->close();
		
	    if ($row_count2 > 0 && $row_count1 == 0) {
	    	// Delete if $row_count2 is true
			$sql = "DELETE FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$user,$log_username,$one);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
	        echo "unfriend_ok";
	        exit();
	    } else if($row_count1 == 0 && $row_count2 == 0){
	    	// Error
			mysqli_close($conn);
	        echo "No friendship could be found between your account and $user, therefore we cannot unfriend you.";
	        exit();
		}
		exit();
	}
}
?>
<?php
	// action variable's value can be accept or reject
	if (isset($_POST['action']) && isset($_POST['reqid']) && isset($_POST['user1'])){
		// Clean the posted variables
		$reqid = preg_replace('#[^0-9]#', '', $_POST['reqid']);
		$user = mysqli_real_escape_string($conn, $_POST['user1']);
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
		if($_POST['action'] == "accept"){
			// Make sure they're not already friends
			$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$user,$one);
			$stmt->execute();
			$stmt->bind_result($row_count1);
			$stmt->fetch();
		    if ($row_count1 > 0 && $row_count2 == 0) {
			    mysqli_close($conn);
		        echo "You are already friends with $user.";
		        exit();
		    }
			$stmt->close();
		    $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$user,$log_username,$one);
			$stmt->execute();
			$stmt->bind_result($row_count2);
			$stmt->fetch();
		    if ($row_count2 > 0 && $row_count1 == 0) {	
			    mysqli_close($conn);
		        echo "You are already friends with $user.";
		        exit();
		    }
		    $stmt->close();
		    if($row_count2 == 0 && $row_count1 == 0) {
		    	// Accept ok
				$sql = "UPDATE friends SET accepted=? WHERE id=? AND user1=? AND user2=? LIMIT 1";
				$stmt =$conn->prepare($sql);
				$stmt->bind_param("ssss",$one,$reqid,$user,$log_username);
				$stmt->execute();
				$stmt->close();
				mysqli_close($conn);
		        echo "accept_ok";
		        exit();
			}
			// Reject ok
		} else if($_POST['action'] == "reject"){
			$sql = "DELETE FROM friends WHERE id=? AND user1=? AND user2=? AND accepted=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$reqid,$user,$log_username,$zero);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
			echo "reject_ok";
			exit();
		}
	}
?>
