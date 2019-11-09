<?php
// Protect this script from direct url access
include_once("../php_includes/check_login_statues.php");
if($user_ok != true || $log_username == "") {
	exit();
}
$one = "1";
$zero = "0";
?><?php
// New PM
if (isset($_POST['action']) && $_POST['action'] == "new_pm"){
	// Make sure post data is not empty
	if(strlen($_POST['data']) < 1){
		mysqli_close($conn);
	    echo "data_empty";
	    exit();
	}
	// Make sure post data is not empty
	if(strlen($_POST['data2']) < 1){
		mysqli_close($conn);
	    echo "data_empty";
	    exit();
	}	
	
	// Clean all of the $_POST vars that will interact with the database
	$fuser = mysqli_real_escape_string($conn, $_POST["fuser"]);
	$tuser = mysqli_real_escape_string($conn, $_POST["tuser"]);
	$data = htmlentities($_POST['data']);
	$data2 = htmlentities($_POST['data2']);

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

	// Clean all of the $_POST vars that will interact with the database
	// We just have an image
	if($data == "||na||" && $image != "na"){
		$data = '<img src="/permUploads/'.$image.'" />';
	// We have an image and text
	}else if($data != "||na||" && $image != "na"){
		$data = $data.'<br /><img src="/permUploads/'.$image.'" />';
	}
	
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$tuser,$one);
	$stmt->execute();
	$stmt->bind_result($row);
	$stmt->fetch();
	if($row < 1){
		mysqli_close($conn);
		echo "$account_no_exist";
		exit();
	}
	$stmt->close();
	//No message to yourself
	if ($log_username == $tuser){
		echo "cannot_message_self";
		exit();
	}
	// Insert the status post into the database now
	$defaultP = "x";
	$sql = "INSERT INTO pm(receiver, sender, senttime, subject, message, parent) 
			VALUES(?,?,NOW(),?,?,?)";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sssss",$tuser,$fuser,$data2,$data,$defaultP);
	$stmt->execute();
	$stmt->close();
	mysqli_close($conn);
	echo "pm_sent";
	exit();
}
?><?php
// Reply To PM
if (isset($_POST['action']) && $_POST['action'] == "pm_reply"){
	// Make sure data is not empty
	if(strlen($_POST['data']) < 1){
		mysqli_close($conn);
	    echo "data_empty";
	    exit();
	}
	// Clean the posted variables
	$osid = preg_replace('#[^0-9]#', '', $_POST['pmid']);
	$account_name = mysqli_real_escape_string($conn, $_POST["user"]);
	$osender = mysqli_real_escape_string($conn, $_POST["osender"]);
	$data = $_POST['data'];
	$image = mysqli_real_escape_string($conn, $_POST["image"]);
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$account_name,$one);
	$stmt->execute();
	$stmt->bind_result($row);
	$stmt->fetch();
	if($row < 1){
		mysqli_close($conn);
		echo "account_no_exist";
		exit();
	}
	$stmt->close();
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
	// Clean all of the $_POST vars that will interact with the database
	// We just have an image
	if($data == "||na||" && $image != "na"){
		$data = '<img src="/permUploads/'.$image.'" />';
	// We have an image and text
	}else if($data != "||na||" && $image != "na"){
		$data = $data.'<br /><img src="/permUploads/'.$image.'" />';
	}
	// Insert the pm reply post into the database now
	$x = "x";
	$sql = "INSERT INTO pm(receiver, sender, senttime, subject, message, parent)
	        VALUES(?,?,NOW(),?,?,?)";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssssi",$x,$account_name,$x,$data,$osid);
	$stmt->execute();
	$stmt->close();
	$id = mysqli_insert_id($conn);
	
	if ($log_username != $osender){
		$sql = "UPDATE pm SET hasreplies=?, rread=?, sread=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssi",$one,$one,$zero,$osid);
		$stmt->execute();
		$stmt->close();
	} else {
		$sql = "UPDATE pm SET hasreplies=?, rread=?, sread=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssi",$one,$zero,$one,$osid);
		$stmt->execute();
		$stmt->close();
	}
	mysqli_close($conn);
	echo "reply_ok|$id";
	exit();
}
?><?php
// Delete PM
if (isset($_POST['action']) && $_POST['action'] == "delete_pm"){
	if(!isset($_POST['pmid']) || $_POST['pmid'] == ""){
		mysqli_close($conn);
		echo "id_missing";
		exit();
	}
	$pmid = preg_replace('#[^0-9]#', '', $_POST['pmid']);
	if(!isset($_POST['originator']) || $_POST['originator'] == ""){
		mysqli_close($conn);
		echo "originator_missing";
		exit();
	}
	$originator = mysqli_real_escape_string($conn, $_POST['originator']);
	// see who is deleting
	if ($originator == $log_username) {
		$sql = "UPDATE pm SET sdelete=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si",$one,$pmid);
		$stmt->execute();
		$stmt->close();
		}
	if ($originator != $log_username) {
		$sql = "UPDATE pm SET sdelete=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si",$one,$pmid);
		$stmt->execute();
		$stmt->close();
		}
	mysqli_close($conn);
	echo "delete_ok";
	exit();
}
?><?php
// Mark As Read
if (isset($_POST['action']) && $_POST['action'] == "mark_as_read"){
	if(!isset($_POST['pmid']) || $_POST['pmid'] == ""){
		mysqli_close($conn);
		echo "id_missing";
		exit();
	}
	$pmid = preg_replace('#[^0-9]#', '', $_POST['pmid']);
	if(!isset($_POST['originator']) || $_POST['originator'] == ""){
		mysqli_close($conn);
		echo "originator_missing";
		exit();
	}
	$originator = mysqli_real_escape_string($conn, $_POST['originator']);
	// see who is marking as read
	if ($originator == $log_username) {
		$sql = "UPDATE pm SET mread=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si",$one,$pmid);
		$stmt->execute();
		$stmt->close();
		}
	if ($originator != $log_username) {
		$sql = "UPDATE pm SET mread=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si",$one,$pmid);
		$stmt->execute();
		$stmt->close();
		}
	mysqli_close($conn);
	echo "read_ok";
	exit();
}

if (isset($_POST['action']) && $_POST['action'] == "deletemessage"){
	$pmid = preg_replace('#[^0-9]#', '', $_POST['pmid']);
	$uname = mysqli_real_escape_string($conn, $_POST['uname']);
	$stime = $_POST['stime'];
	$sql = "SELECT id FROM users WHERE username = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
		echo "This user does not exist";
		exit();
	}
	$stmt->close();
	$x = "x";
	$sql = "DELETE FROM pm WHERE sender = ? AND senttime = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$uname,$stime);
	$stmt->execute();
	$stmt->close();
	echo "deletemessage_ok";
	exit();
}
?>