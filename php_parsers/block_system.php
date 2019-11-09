<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
?><?php
	// Ajax calls this code to execute
	if (isset($_POST['type']) && isset($_POST['blockee'])){
		// Clean the blockee
		$blockee = mysqli_real_escape_string($conn, $_POST['blockee']);
		// Make sure the blockee is exists in the database 
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$blockee,$one);
		$stmt->execute();
		$stmt->bind_result($exist_count);
		$stmt->fetch();
		if($exist_count < 1){
			mysqli_close($conn);
			echo "$blockee does not exist.";
			exit();
		}
		$stmt->close();
		// Check to see if the user has already blocked the blockee
		$sql = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$log_username,$blockee);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		if($_POST['type'] == "block"){
		    if ($numrows > 0) {
				mysqli_close($conn);
		        echo "You already have this member blocked.";
		        exit();
		    } else {
		    	// Insert into database
		    	$stmt->close();
				$sql = "INSERT INTO blockedusers(blocker, blockee, blockdate) VALUES(?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$blockee);
				$stmt->execute();
				$stmt->close();
				// Check to see if the blockee is the user's friend
				$sql = "SELECT COUNT(id) FROM friends WHERE (user1=? AND user2=? AND accepted=?)
						OR (user1=? AND user2=? AND accepted=?) LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssssss",$log_username,$blockee,$one,$blockee,$log_username,$one);
				$stmt->execute();
				$stmt->bind_result($rowcount);
				$stmt->fetch();
				// If it is, first delete from friends
				if($rowcount > 0){
					$stmt->close();
					$sql = "DELETE FROM friends WHERE (user1=? AND user2=? AND accepted=?)
						OR (user1=? AND user2=? AND accepted=?) LIMIT 1";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("ssssss",$log_username,$blockee,$one,$blockee,$log_username,$one);
					$stmt->execute();
					$stmt->close();
				}
				mysqli_close($conn);
		        echo "blocked_ok";
		        exit();
			}
			// Ajax calls this code to execute
		} else if($_POST['type'] == "unblock"){
			// Check to see if the user is not blocked before
		    if ($numrows == 0) {
			    mysqli_close($conn);
		        echo "You do not have this user blocked, therefore we cannot unblock them.";
		        exit();
		    } else {
		    	// Delete from database
				$sql = "DELETE FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$blockee);
				$stmt->execute();
				$stmt->close();
				mysqli_close($conn);
		        echo "unblocked_ok";
		        exit();
			}
		}
	}
?>