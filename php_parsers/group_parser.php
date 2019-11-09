<?php
	// Make sure both username and group sessions are set
	require_once '../tupl.php';
	require_once '../php_includes/check_login_statues.php';
	session_start();
	if(!isset($_SESSION["username"])){
	    exit();
	}
	// Save in into vars
	$uS = $_SESSION['username'];
	$gS = $_SESSION['group'];
	include_once("../php_includes/conn.php");
	$one = "1";
	$zero = "0";
	?>
	<?php
	// Check group name
	if(isset($_POST["gnamecheck"])){
		$gname = mysqli_real_escape_string($conn, $_POST['gnamecheck']);
		$sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$gname);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$gname_check = $stmt->num_rows;
	    // Length error
	    if (strlen($gname) < 3 || strlen($gname) > 50) {
		    echo '<strong class="error_red" style="font-weight: normal;">3 - 50 characters please</strong>';
		    exit();
	    }
	    // Begin error
		if (is_numeric($gname[0])) {
		    echo '<strong class="error_red" style="font-weight: normal;">Group names must begin with a letter</strong>';
		    exit();
	    }

	    // Group name is OK
	    if ($gname_check < 1) {
		    echo '<img src="/images/correct.png" width="21" height="21" margin-left: -20px;>';
		    exit();
	    } else {
	    	// Group name is taken
		    echo '<strong class="error_red" style="font-weight: normal;">' . $gname . ' is taken</strong>';
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
			echo '<strong class="error_red" style="font-weight: normal;">Please choose a category</strong>';
			exit();
		}else{
			echo '<strong class="success_green" style="font-weight: normal;">Group category is OK</strong>';
			exit();
		}
	}
?>
<?php
	// Check group type
	if(isset($_POST["typecheck"])){
		$type = $_POST["typecheck"];
		if($type == ""){
			echo '<strong class="error_red" style="font-weight: normal;">Please choose a category</strong>';
			exit();
		}else{
			echo '<strong class="success_green" style="font-weight: normal;">Group type is OK</strong>';
			exit();
		}
	}
?>
<?php
	// Create new group
	if(isset($_POST["action"]) && $_POST['action'] == "new_group"){
		
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$name = mysqli_real_escape_string($conn, $_POST["name"]);
	    $inv = preg_replace('#[^0-9.]#', '', $_POST['inv']);
	    $cat = $_POST['cat'];
		// DUPLICATE DATA CHECKS FOR USERNAME AND EMAIL
		if ($inv == "1"){
			$inv = "0";
		}
		if ($inv == "2"){
			$inv = "1";
		}
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
		} else if (strlen($name) < 3 || strlen($name) > 50) {
	        echo "Group name must be between 3 and 50 characters";
	        exit(); 
	    } else if (is_numeric($name[0])) {
	        echo 'Group name cannot begin with a number';
	        exit();
	    } else {
	    	$stmt->close();
			// END FORM DATA ERROR HANDLING
		    // Begin Insertion of data into the database
			// Add group to database
			$gicon = "group_icon.png";
			$sql = "INSERT INTO groups (name, creation, logo, invrule, cat, creator)       
			        VALUES(?,NOW(),?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sssss",$name,$gicon,$inv,$cat,$uS);
			$stmt->execute();
			$stmt->close();
			// Add to group member to database
			$sql = "INSERT INTO gmembers (gname, mname, approved, admin)       
			        VALUES(?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$name,$uS,$one,$one);
			$stmt->execute();
			$stmt->close();
			if (!file_exists("../groups")) {
				mkdir("../groups", 0755);
			}
			// Create directory(folder) to hold each user's files(pics, MP3s, etc.)
			if (!file_exists("../groups/$name")) {
				mkdir("../groups/$name", 0755);
			}
			$gLogo = '../images/group_icon.png';
			$gLogo2 = "../groups/$name/group_icon.png"; 
			if (!copy($gLogo, $gLogo2)) {
				echo "failed to create logo.";
			}
			echo "group_created|$name";
			exit();
		}
		exit();
	}
?><?php
	// Join Group Request
	if(isset($_POST["action"]) && $_POST['action'] == "join_group"){
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$name = $uS;
		$group = $gS;
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
		
		if ($rule == 0){
			echo "pending_approval";
			exit();	
		} else {
			echo "refresh_now";
			exit();		
		}
	}
?>
<?php
if(isset($_POST["action"]) && $_POST['action'] == "approve_member"){
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$g = $gS;
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
		$g = $gS;
		$u = mysqli_real_escape_string($conn, $_POST['u']);

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
		// Remove from database
			$sql = "DELETE FROM gmembers WHERE mname=? AND gname=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$g,$u);
			$stmt->execute();
			$stmt->close();
			echo "member_declined";
			exit();
}
?>
<?php 
	if(isset($_POST["action"]) && $_POST['action'] == "quit_group"){
		// Empty check
		if($gS == "" || $uS == ""){
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
			exit();
		}
		$stmt->close();

		// Remove from the database
		$sql = "DELETE FROM gmembers WHERE mname=? AND gname=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$uS,$gS);
		$stmt->execute();
		$stmt->close();

		// If the group is empty remove from the database
		$junk = array();
		$sql = "SELECT * FROM gmembers WHERE approved=? AND admin=?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$zero,$zero);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			array_push($junk, $row["gname"]);
		}
		$stmt->close();

		for($i=0; $i<count($junk); $i++){
			// Delete from gmembers
			$groupa = $junk[$i];
			$sql = "DELETE FROM groups WHERE name=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$groupa);
			$stmt->execute();
			$stmt->close();
		}

		echo "was_removed";
		exit();
	}
?>
<?php
if(isset($_POST["action"]) && $_POST['action'] == "add_admin"){
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$n = mysqli_real_escape_string($conn, $_POST['n']);

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
    	exit();
	}
	$stmt->close();
	// Verify admin status
	$sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND admin=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$gS,$uS,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
    	exit();
	}

	// Set as admin
	$sql = "UPDATE gmembers SET admin=? WHERE gname=? AND mname=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$one,$gS,$n);
	$stmt->execute();
	$stmt->close();
	echo "admin_added";
	exit;
}
?>
<?php 
	if (isset($_FILES["avatar"]["name"]) && $_FILES["avatar"]["tmp_name"] != ""){
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
		if($fileSize > 1048576) {
			header("location: ../image_bigger_error");
			exit();	
		} else if (!preg_match("/\.(gif|jpg|png|jfif|jpeg)$/i", $fileName) ) {
			header("location: ../image_type_error");
			exit();
		} else if ($fileErrorMsg == 1) {
			header("location: ../file_upload_error");
			exit();
		}
		$sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$log_username);
		$stmt->execute();
		$stmt->bind_result($avatar);
		$stmt->fetch();
		if($avatar != ""){
			$picurl = "../user/$log_username/$avatar"; 
		    if (file_exists($picurl)) { unlink($picurl); }
		}
		$stmt->close();
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

		// Clean all of the $_POST vars that will interact with the database
		$data = htmlentities($_POST['data']);
		$data = mysqli_real_escape_string($conn, $data);

		// Insert the status post into the database now
		$sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
				VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssss",$zero,$gS,$uS,$zero,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);

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

		// Clean all of the $_POST variables that will interact with the database
		$data = htmlentities($_POST['data']);
		$data = mysqli_real_escape_string($conn, $data);

		// Empty check
		if($sid == ""){
			exit();
		}

		// Insert the status into the database now
		$sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
				VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issss",$sid,$gS,$uS,$one,$data);
		$stmt->execute();
		$stmt->close();
		mysqli_close($conn);
		echo "reply_ok|$sid";
		exit();

	}
?>