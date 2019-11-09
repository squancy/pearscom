<?php
	require_once '../php_includes/check_login_statues.php';
	require_once '../safe_encrypt.php';
	require_once '../tupl.php';
	$one = "1";
	$zero = "0";

	$u = $_SESSION['username'];

	if($user_ok != true || $log_username == "") {
		exit();
	}
	// Check group name
	if(isset($_POST["gnamecheck"])){
		$gname = mysqli_real_escape_string($conn, $_POST["gnamecheck"]);
		$sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$gname);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$gname_check = $stmt->num_rows;
		$ish = strpos($gname,"#");
		$ish2 = strpos($gname, "%23");
		$ish3 = strpos($gname, "&#35;");
	    // Begin error
		if (is_numeric($gname[0])) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Group name cannot begin with a number</span>';
		    exit();
	    } else if ($gname_check > 0) {
	    	// Group name is taken
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Group name is taken</span>';
		    exit();
	    } else if (strlen($gname) < 3 || strlen($gname) > 100) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Group name must be between 3 and 100 characters</span>';
		    exit();
	    } else if($ish != false || $ish2 != false || $ish3 != false){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Group name cannot contain a hashtag sign</span>';
		    exit();
		}
	    $stmt->close();
	}
?>
<?php
	// Check group category
	if(isset($_POST["catcheck"])){
		$cat = $_POST["catcheck"];
		if($cat == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose a category</span>';
			exit();
		}else if($cat != "1" && $cat != "2" && $cat != "3" && $cat != "4" && $cat != "5" && $cat != "6" && $cat != "7" && $cat != "8"){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose a valid category</span>';
			exit();
		}
	}
?>
<?php
	// Check group type
	if(isset($_POST["typecheck"])){
		$type = $_POST["typecheck"];
		if($type == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose a type</span>';
			exit();
		}else if($type != "0" && $type != "1"){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose a valid type</span>';
			exit();
		}
	}
?>
<?php
	// Create new group
	if(isset($_POST["action"]) && $_POST['action'] == "new_group"){
		
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$name = mysqli_real_escape_string($conn, $_POST["name"]);
		$ish = strpos($name,"#");
		$ish2 = strpos($name, "%23");
		$ish3 = strpos($name, "&#35;");
	    $inv = preg_replace('#[^0-9.]#', '', $_POST['inv']);
	    $cat = $_POST['cat'];
		// DUPLICATE DATA CHECKS FOR USERNAME AND EMAIL
		$sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$name);
	    $stmt->execute();
	    $stmt->store_result();
		$stmt->fetch();
		$n_check = $stmt->num_rows;
		// FORM DATA ERROR HANDLING
		if($name == "" || $inv == "" || $cat == ""){
			echo "The form submission is missing values.";
	        exit();
		} else if ($n_check > 0){ 
	        echo "The group name you entered is alreay taken";
	        exit();
		} else if (strlen($name) < 3 || strlen($name) > 100) {
	        echo "Group name must be between 3 and 100 characters";
	        exit(); 
	    } else if (is_numeric($name[0])) {
	        echo 'Group name cannot begin with a number';
	        exit();
	    } else if($cat != "1" && $cat != "2" && $cat != "3" && $cat != "4" && $cat != "5" && $cat != "6" && $cat != "7" && $cat != "8"){
	        echo 'Please give a valid category';
	        exit();
	    } else if ($ish != false || $ish2 != false || $ish3 != false){
	        echo 'Group name cannot contain a hashtag (#) sign';
	        exit();
	    } else {
	    	$stmt->close();
			// END FORM DATA ERROR HANDLING
		    // Begin Insertion of data into the database
			// Add group to database
			$gicon = "gdef.png";
			$sql = "INSERT INTO groups (name, creation, logo, invrule, cat, creator)       
			        VALUES(?,NOW(),?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sssss",$name,$gicon,$inv,$cat,$log_username);
			$stmt->execute();
			$stmt->close();
			// Add to group member to database
			$sql = "INSERT INTO gmembers (gname, mname, approved, admin)       
			        VALUES(?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$name,$log_username,$one,$one);
			$stmt->execute();
			$stmt->close();
			if (!file_exists("../groups")) {
				mkdir("../groups", 0755);
			}
			// Create directory(folder) to hold each user's files(pics, MP3s, etc.)
			if (!file_exists("../groups/$name")) {
				mkdir("../groups/$name", 0755);
			}
			$gLogo = '../images/gdef.png';
			$gLogo2 = "../groups/$name/gdef.png"; 
			if (!copy($gLogo, $gLogo2)) {
				echo "failed to create logo.";
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
				$app = "Recently Created Group <img src='/images/ngroup.png' class='notfimg'>";
				$note = $log_username.' created a new group: <br /><a href="/group/'.$name.'">Check it now</a>';
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
				$app = "Recently Created Group | your following, ".$log_username.", created a new group <img src='/images/ngroup.png' class='notfimg'>";
				$note = '<a href="/group/'.$name.'">Check it now</a>';
				// Insert into database
				$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
				$stmt->execute();
				$stmt->close();			
			}

			echo "group_created|$name";
			exit();
		}
		exit();
	}
?>
<?php
	// Join Group Request
	if(isset($_POST["action"]) && $_POST['action'] == "join_group"){
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$group = $_POST['g'];
		$name = $u;
		// Empty check
		if($name == "" || $group == ""){
	        exit();
		}
		// Make sure that group exists
		$sql = "SELECT id, invrule FROM groups WHERE name=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$group);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows < 1){
	    	exit();
		} else {
			while ($row = $result->fetch_assoc()) {
				$rule = $row["invrule"];
			}
		}
		$stmt->close();
		// Add request to database
		$sql = "INSERT INTO gmembers (gname, mname, approved)
			        VALUES(?,?,?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$group,$name,$rule);
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
			$app = "Join To New Group <img src='/images/joing.png' class='notfimg'>";
			$note = $log_username.' joined to a new group called '.$group.': <br /><a href="/group/'.$group.'">Check it now</a>';
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
			$app = "Join To New Group | your following, ".$log_username.", joined to a new group called ".$group." <img src='/images/joing.png' class='notfimg'>";
			$note = '<a href="/group/'.$group.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}
		
		if ($rule == 1){
			echo "refresh_now";
			exit();
		} else {
			echo "pending_approval";
			exit();		
		}
	}
?>
<?php
if(isset($_POST["action"]) && $_POST['action'] == "approve_member"){
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$g = $_POST['g'];
	$u = mysqli_real_escape_string($conn, $_POST["u"]);

	// Empty check
	if($g == "" || $u == ""){
        exit();
	}
	
	// Make sure request exists
	$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$g,$u,$zero);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
    	exit();
	}
	$stmt->close();
	// Add request to database
	$sql = "UPDATE gmembers SET approved=? WHERE mname=? AND gname=? LIMIT 1";;
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$one,$u,$g);
	$stmt->execute();
	$stmt->close();
	echo "member_approved";
	exit();
}
?><?php
	// Decline member
	if(isset($_POST["action"]) && $_POST['action'] == "decline_member"){
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$g = mysqli_real_escape_string($conn, $_POST['g']);
		$u = mysqli_real_escape_string($conn, $_POST["u"]);

		// Empty check
		if($g == "" || $u == ""){
		    echo "Username or group name does not exist";
	        exit();
		}
		
		// Make sure request exists
		$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$g,$u,$zero);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		if($numrows < 1){
		    echo "Request does not exist";
	    	exit();
		}
		$stmt->close();
		
		// Remove from database
		$sql = "DELETE FROM gmembers WHERE mname=? AND gname=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$u,$g);
		$stmt->execute();
		$stmt->close();
		echo "member_declined";
		exit();
}
?>
<?php 
	if(isset($_POST["action"]) && $_POST['action'] == "quit_group"){
		// Empty check
		$uS = $u;
		$gS = mysqli_real_escape_string($conn, $_POST['g']);
		if($gS == "" || $uS == ""){
		    echo "Group name or username does not exist";
			exit();
		}

		// Make sure already member
		$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$gS,$uS,$one);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		if($numrows < 1){
		    echo "You are not a member of this group";
			exit();
		}
		$stmt->close();

		// Check if he/she is the last member
		$sql = "SELECT COUNT(id) FROM gmembers WHERE gname = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$gS);
		$stmt->execute();
		$stmt->bind_result($numrows);
        $stmt->fetch();
        $stmt->close();
		if($numrows <= 1){
			$sqlRem = "DELETE FROM groups WHERE name = ?";
			$stmt = $conn->prepare($sqlRem);
			$stmt->bind_param("s",$gS);
			$stmt->execute();
			$stmt->close();
		}

        /*
		// Check if he/she is the only admin in the group
		$sql = "SELECT COUNT(id) FROM gmembers WHERE gname = ? AND approved = ? AND admin = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$gS,$one,$one);
		$stmt->execute();
		$stmt->bind_result($nrows2);
        $stmt->fetch();
        $stmt->close();
		if($nrows2 <= 1){
			$sqlRem2 = "UPDATE gmembers SET admin = ? WHERE approved = ? AND gname = ? LIMIT 1";
			$stmt = $conn->prepare($sqlRem2);
			$stmt->bind_param("sss",$one,$one,$gS);
			$stmt->execute();
			$stmt->close();
		}
        */
        
		// Remove from the database
		$sql = "DELETE FROM gmembers WHERE mname=? AND gname=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$uS,$gS);
		$stmt->execute();
		$stmt->close();

		echo "was_removed";
		exit();
	}
?>
<?php
if(isset($_POST["action"]) && $_POST['action'] == "add_admin"){
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$n = mysqli_real_escape_string($conn, $_POST["n"]);
	$gS = mysqli_real_escape_string($conn, $_POST['g']);
	$uS = $u;

	// Empty check
	if($gS == "" || $uS == "" || $n == ""){
        exit();
	}
	
	// Make sure already member
	$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$gS,$n,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
    	echo "This user is not the member of this group!";
    	exit();
	}
	$stmt->close();
	
	// Check if user is not already an admin
	$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? AND admin = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssss",$gS,$n,$one,$zero);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
    	echo "This user is already a moderator";
    	exit();
	}

	// Set as admin
	$sql = "UPDATE gmembers SET admin=? WHERE gname=? AND mname=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$one,$gS,$n);
	$stmt->execute();
	$stmt->close();
	echo "admin_added";
	exit();
}
?>
<?php 
	if (isset($_FILES["avatar"]["name"]) && $_FILES["avatar"]["tmp_name"] != ""){
		$uS = $u;
		$gS =  $_SESSION["gname"];

		$fileName = $_FILES["avatar"]["name"];
	    $fileTmpLoc = $_FILES["avatar"]["tmp_name"];
		$fileType = $_FILES["avatar"]["type"];
		$fileSize = $_FILES["avatar"]["size"];
		$fileErrorMsg = $_FILES["avatar"]["error"];
		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);
		list($width, $height) = getimagesize($fileTmpLoc);
		if($width < 10 || $height < 10){
			header("location: ../image_size_error");
	        exit();	
		}
		$db_file_name = imgHash($log_username,$fileExt);
		if($fileSize > 3145728) {
			header("location: ../image_bigger_error");
			exit();	
		} else if (!preg_match("/\.(gif|jpg|png|jfif|jpeg)$/i", $fileName) ) {
			header("location: ../image_type_error");
			exit();
		} else if ($fileErrorMsg == 1) {
			header("location: ../file_upload_error");
			exit();
		}
		$moveResult = move_uploaded_file($fileTmpLoc, "../groups/$gS/$db_file_name");
		if ($moveResult != true) {
			header("location: ../file_upload_error");
			exit();
		}
		include_once("../php_includes/image_resize.php");
		$target_file = "../user/$log_username/$db_file_name";
		$resized_file = "../user/$log_username/$db_file_name";
		$wmax = 200;
		$hmax = 300;
		img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
		$sql = "UPDATE groups SET logo=? WHERE name=? AND creator=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$db_file_name,$gS,$uS);
		$stmt->execute();
		$stmt->close();
		mysqli_close($conn);
		header("location: ../group/$gS");
		exit();
	}
?>
<?php
	// Add new post
	if(isset($_POST['action']) && $_POST['action'] == "new_post"){
		// Make sure post data is not empty
		if(strlen($_POST['data']) < 1){
			exit();
		}

		$gS = $_POST['g'];
		$uS = $u;

		// Clean all of the $_POST vars that will interact with the database
		$data = htmlentities($_POST['data']);

		$image = mysqli_real_escape_string($conn, $_POST["image"]);
		// Move the image(s) to the permanent folder
		if($image != "na"){
			$kaboom = explode(".", $image);
			$fileExt = end($kaboom);
			rename("../tempUploads/$image", "../permUploads/$image");
			require_once '../php_includes/image_resize.php';
			$target_file = "../permUploads/$image";
			$resized_file = "../permUploads/$image";
			$wmax = 400;
			$hmax = 500;
			list($width, $height) = getimagesize($target_file);
			if($width > $wmax || $height > $hmax){
				img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
			}
		}

		// We just have an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}

		// Insert the status post into the database now
		$sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
				VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssss",$zero,$gS,$uS,$zero,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
		$sql = "UPDATE grouppost SET pid=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$id,$id);
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
			$app = "Group Status Post <img src='/images/post.png' class='notfimg'>";
			$note = $log_username.' posted on '.$gS.' group: <br /><a href="/group/'.$gS.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Group Status Post | your following, ".$log_username.", posted on a group called ".$gS." <img src='/images/post.png' class='notfimg'>";
			$note = '<a href="/group/'.$gS.'/#status_'.$id.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "post_ok|$id";
		exit();
	}
?>
<?php
	// Reply to post
	if(isset($_POST['action']) && $_POST['action'] == "post_reply"){
		// Make sure post data is not empty
		$sid = preg_replace('#[^0-9]#i', '', $_POST['sid']);
		if(strlen($_POST['data']) < 1){
			exit();
		}

		$gS = $_POST['g'];
		$uS = $u;

		// Clean all of the $_POST variables that will interact with the database
		$data = htmlentities($_POST['data']);

		// Empty check
		if($sid == ""){
			exit();
		}

		$image = mysqli_real_escape_string($conn, $_POST["image"]);
		// Move the image(s) to the permanent folder
		if($image != "na"){
			$kaboom = explode(".", $image);
			$fileExt = end($kaboom);
			rename("../tempUploads/$image", "../permUploads/$image");
			require_once '../php_includes/image_resize.php';
			$target_file = "../permUploads/$image";
			$resized_file = "../permUploads/$image";
			$wmax = 400;
			$hmax = 500;
			list($width, $height) = getimagesize($target_file);
			if($width > $wmax || $height > $hmax){
				img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
			}
		}

		// We just have an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}

		// Insert the status into the database now
		$sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
				VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issss",$sid,$gS,$uS,$one,$data);
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
			$app = "Group Status Reply <img src='/images/reply.png' class='notfimg'>";
			$note = $log_username.' commented on '.$gS.' group: <br /><a href="/group/'.$gS.'/#status_'.$sid.'">Check it now</a>';
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
			$app = "Group Status Reply | your following, ".$log_username.", commented on ".$gS." group <img src='/images/reply.png' class='notfimg'>";
			$note = '<a href="/group/'.$gS.'/#status_'.$sid.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "reply_ok|$sid";
		exit();

	}
?>
<?php 
	if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
		if(!isset($_POST['statusid']) || $_POST['statusid'] == ""){
			mysqli_close($conn);
			echo "status id is missing";
			exit();
		}
		$statusid = preg_replace('#[^0-9]#', '', $_POST['statusid']);
		// Check to make sure this logged in user actually owns that comment
		$sql = "SELECT * FROM grouppost WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$statusid);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			$author = $row["author"];
			$data = $row["data"];
		}
		$stmt->close();
	    if ($author == $log_username) {
	    	// Check for images
	    	if(preg_match('/<img.+src=[\'"](?P<src>.+)[\'"].*>/i', $data, $has_image)){
				$source = '../'.$has_image['src'];
				if (file_exists($source)) {
	        		unlink($source);
	    		}
			}
			$sql = "DELETE FROM grouppost WHERE pid=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$statusid);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
		    echo "delete_ok";
			exit();
		}
	}
?>
<?php 
	if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
		if(!isset($_POST['replyid']) || $_POST['replyid'] == ""){
		    echo "Invalid input: missing id.";
			mysqli_close($conn);
			exit();
		}
		if(!isset($_POST['group']) || $_POST['group'] == ""){
		    echo "Invalid input: missing group name.";
			mysqli_close($conn);
			exit();
		}
		$replyid = preg_replace('#[^0-9]#', '', $_POST['replyid']);
		$group = mysqli_real_escape_string($conn, $_POST["group"]);
		// Check to make sure the person deleting this reply is either the account owner or the person who wrote it
		$sql = "SELECT author FROM grouppost WHERE id=? AND gname = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("is",$replyid,$group);
		$stmt->execute();
		$stmt->bind_result($author);
		$stmt->fetch();
		$stmt->close();
		echo "Repid: ".$replyid."|";
		echo "Group: ".$group."|";
		echo "Auth: ".$author."|";
	    if ($author == $log_username) {
			$sql = "DELETE FROM grouppost WHERE id=? AND gname = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("is",$replyid,$group);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
		    echo "delete_ok";
			exit();
		}
	}
?>
<?php 
	if (isset($_POST['text']) && isset($_POST["gr"])){
		$gr = $_POST["gr"];
		$text = mysqli_real_escape_string($conn, $_POST["text"]);
		$text = htmlentities($text);

		$sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$gr);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows < 1){
			echo "This group does not exist.";
			exit();
		}
		$stmt->close();
	    $sql = "UPDATE groups SET des = ? WHERE name = ? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$text,$gr);
	    $stmt->execute();
	    $stmt->close();

	    echo "des_save_success|$text";
	    exit();
	}
?>
<?php
    // Share group status
    if(isset($_POST["action"]) && $_POST["action"] == "share_status"){
        // Error handling: make sure id is set
        if(!isset($_POST["id"]) || $_POST["id"] == ""){
            echo "Invalid input: id is missing.";
            mysqli_close($conn);
            exit();
        }else{
            $id = preg_replace('/\D/', '', $_POST["id"]);
        }
        // Error handling: make sure group is set
        if(!isset($_POST["group"]) || $_POST["group"] == ""){
            echo "Invalid input: group is missing.";
            mysqli_close($conn);
            exit();
        }else{
            $group = mysqli_real_escape_string($conn, $_POST["group"]);
        }
        // Select the certain group post from the database
        $sql = "SELECT data, author FROM grouppost WHERE id = ? AND gname = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is",$id,$group);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows < 1){
            echo "Invalid input: post does not exist";
            mysqli_close($conn);
            exit();
        }
        $stmt->close();
        // Start to implement the group status share
        $sql = "SELECT data, author FROM grouppost WHERE id = ? AND gname = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is",$id,$group);
        $stmt->execute();
        $stmt->bind_result($dat,$auth);
        $stmt->fetch();
        $stmt->close();
        $a = "a";
        $data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared via <a href="/user/'.$auth.'/">'.$auth.'</a></p></div><hr class="dim">';
		$data .= '<div id="share_data">'.$dat.'</div>';
		$sql = "INSERT INTO status(account_name, author, type, data, postdate) VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$log_username,$log_username,$a,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
        // Update the osID of inserted column
 		$sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
 		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$id,$id);
		$stmt->execute();
		$stmt->close();
        // Share was implemented without any errors, echo success message and exit
		mysqli_close($conn);
		echo "share_ok";
		exit();
    }
?>