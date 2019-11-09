<?php
include_once("../php_includes/check_login_statues.php");
require_once '../safe_encrypt.php';
require_once '../tupl.php';
require_once '../timeelapsedstring.php';
$one = "1";

function change_orientation($path){
	$exif = exif_read_data($path);
	if(isset($exif['Orientation']) && $exif['Orientation'] != 1){
		$position = $exif['Orientation'];
		$degrees = "";
		if($position == "8"){
			$degrees = "90";
		}else if($position == "3"){
			$degrees = "180";
		}else if($position == "6"){
			$degrees = "-90";
		}

		if($degrees == "90" || $degrees == "180" || $degrees == "-90"){
			$source = imagecreatefromjpeg($path);
			$rotate = imagerotate($source, $degrees, 0);
			imagejpeg($rotate, realpath($path));
			imagedestroy($source);
			imagedestroy($rotate);
		}
	}
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
		header("Location: ../image_size_error");
        exit();	
	}
	$db_file_name = imgHash($log_username,$fileExt);
	if($fileSize > 5242880) {
		header("Location: ../image_bigger_error");
		exit();	
	} else if (!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName) ) {
		header("Location: ../image_type_error.php");
		exit();
	} else if ($fileErrorMsg == 1) {
		header("Location: ../image_unknown_error");
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
	$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/$db_file_name");
	if ($moveResult != true) {
		header("location: ../file_upload_error");
		exit();
	}
	include_once("../php_includes/image_resize.php");
	$target_file = "../user/$log_username/$db_file_name";
	$resized_file = "../user/$log_username/$db_file_name";
	$wmax = 650;
	$hmax = 650;
	img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	$sql = "UPDATE users SET avatar=? WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$db_file_name,$log_username);
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
		$app = "New Profile Picture <img src='/images/ppc.png' class='notfimg'>";
		$note = $log_username.' changed his/her profile picture: <br /><a href="/user/'.$log_username.'/">Check it now</a>';
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
		$app = "New Profile Picture | your following, ".$log_username.", changed his/her profile picture <img src='/images/ppc.png' class='notfimg'>";
		$note = '<a href="/user/'.$log_username.'/">Check it now</a>';
		// Insert into database
		$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
		$stmt->execute();
		$stmt->close();			
	}

	mysqli_close($conn);
	header("location: ../user/$log_username/");
	exit();
}
?>
<?php 
if (isset($_FILES["background"]["name"]) && $_FILES["background"]["tmp_name"] != ""){
	$fileName = $_FILES["background"]["name"];
    $fileTmpLoc = $_FILES["background"]["tmp_name"];
	$fileType = $_FILES["background"]["type"];
	$fileSize = $_FILES["background"]["size"];
	$fileErrorMsg = $_FILES["background"]["error"];
	$kaboom = explode(".", $fileName);
	$fileExt = end($kaboom);
	list($width, $height) = getimagesize($fileTmpLoc);
	if($width < 10 || $height < 10){
		header("Location: ../image_size_error");
        exit();	
	}
	$db_file_name = imgHash($log_username,$fileExt);
	if($fileSize > 5242880) {
		header("Location: ../image_bigger_error");
		exit();	
	} else if (!preg_match("/\.(jpg|png|jpeg)$/i", $fileName) ) {
		header("Location: ../image_type_error");
		exit();
	} else if ($fileErrorMsg == 1) {
		header("Location: ../image_unknown_error");
		exit();
	}
	if (!file_exists("../user/$log_username/background")) {
		mkdir("../user/$log_username/background", 0755);
	}

	$sql = "SELECT background FROM useroptions WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->bind_result($background);
	$stmt->fetch();
	if($background != ""){
		$picurl = "../user/$log_username/background/$background"; 
	    if (file_exists($picurl)) { unlink($picurl); }
	}
	$stmt->close();
	$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/background/$db_file_name");
	if ($moveResult != true) {
		header("location: ../file_upload_error");
		exit();
	}
	include_once("../php_includes/image_resize.php");
	$target_file = "../user/$log_username/background/$db_file_name";
	$resized_file = "../user/$log_username/background/$db_file_name";
	$wmax = 1200;
	$hmax = 350;
	img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	$sql = "UPDATE useroptions SET background=? WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$db_file_name,$log_username);
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
		$app = "New Background <img src='/images/bgc.png' class='notfimg'>";
		$note = $log_username.' changed his/her background: <br /><a href="/user/'.$log_username.'/">Check it now</a>';
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
		$app = "New Background | your following, ".$log_username.", changed his/her background <img src='/images/bgc.png' class='notfimg'>";
		$note = '<a href="/user/'.$log_username.'/">Check it now</a>';
		// Insert into database
		$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
		$stmt->execute();
		$stmt->close();			
	}

	mysqli_close($conn);
	header("location: ../user/$log_username/");
	exit();
}
?>
<?php
	include_once("../php_includes/check_login_statues.php");

	if(isset($_POST["imgtype"]) && $_POST["imgtype"] != ""){
		$imgtype = $_POST['imgtype'];
		$fileTmpLoc = "";
		$fileName = "";
	    if($imgtype == "universe"){
	    	$fileTmpLoc = "../images/universebi.jpg";
	    	$fileName = "universebi.jpg";
	    }else if($imgtype == "flowers"){
	    	$fileTmpLoc = "../images/flowersbi.jpeg";
	    	$fileName = "flowersbi.jpeg";
	    }else if($imgtype == "forest"){
	    	$fileTmpLoc = "../images/forestbi.jpg";
	    	$fileName = "forestbi.jpg";
	    }else if($imgtype == "bubbles"){
	    	$fileTmpLoc = "../images/bubblesbi.jpg";
	    	$fileName = "bubblesbi.jpg";
	    }else if($imgtype == "mountains"){
	    	$fileTmpLoc = "../images/mountainsbi.jpeg";
	    	$fileName = "mountainsbi.jpeg";
	    }else if($imgtype == "waves"){
	    	$fileTmpLoc = "../images/wavesbi.jpg";
	    	$fileName = "wavesbi.jpg";
	    }else if($imgtype == "stones"){
	    	$fileTmpLoc = "../images/stonesbi.jpg";
	    	$fileName = "stonesbi.jpg";
	    }else if($imgtype == "simple"){
	    	$fileTmpLoc = "../images/simplebi.jpg";
	    	$fileName = "simplebi.jpg";
	    }
	    $kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);

		$db_file_name = imgHash($log_username,$fileExt);
		if (!file_exists("../user/$log_username/background")) {
			mkdir("../user/$log_username/background", 0755);
		}

		$sql = "SELECT background FROM useroptions WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$log_username);
		$stmt->execute();
		$stmt->bind_result($background);
		$stmt->fetch();
		if($background != ""){
			$picurl = "../user/background/$background";
		    if (file_exists($picurl)) { unlink($picurl); }
		}
		$stmt->close();
		$newFileLoc = "../user/$log_username/background/$db_file_name";
		$copied = copy($fileTmpLoc, $newFileLoc);
		if((!$copied)){
			echo "Error: not copied";
		}
		
		$sql = "UPDATE useroptions SET background=? WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$db_file_name,$log_username);
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
			$app = "New Background <img src='/images/bgc.png' class='notfimg'>";
			$note = $log_username.' changed his/her background: <br /><a href="/user/'.$log_username.'/">Check it now</a>';
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
			$app = "New Background | your following, ".$log_username.", changed his/her background <img src='/images/bgc.png' class='notfimg'>";
			$note = '<a href="/user/'.$log_username.'/">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		
		mysqli_close($conn);
		echo "bibg_success";
		exit();
	}	
?>
<?php
if (isset($_FILES["stPic_photo"]["name"]) && $_FILES["stPic_photo"]["tmp_name"] != "" && isset($_POST["cgal"])){
	$gallery = preg_replace('#[^a-z0-9 .-_]#i', '', $_POST["cgal"]);
	$description = $_POST["des"];
	if(strlen($description) > 1000){
	    echo "You overstepped the maximum 1000 characters limit!";
	    exit();
	}
	$fileName = $_FILES["stPic_photo"]["name"];
    $fileTmpLoc = $_FILES["stPic_photo"]["tmp_name"];
	$fileType = $_FILES["stPic_photo"]["type"];
	$fileSize = $_FILES["stPic_photo"]["size"];
	$fileErrorMsg = $_FILES["stPic_photo"]["error"];
	$kaboom = explode(".", $fileName);
	$fileExt = end($kaboom);
	$db_file_name = imgHash($log_username,$fileExt);
	list($width, $height) = getimagesize($fileTmpLoc);
	if($width < 10 || $height < 10){
		header("Location: ../image_size_error");
        exit();
	}
	if($fileSize > 5242880){
		echo "Your image was larger than 5mb|fail";
		exit();
	}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
		echo "Your image file was not png, jpg, gif or jpeg type|fail";
		exit();
	}else if($fileErrorMsg == 1){
		echo "An unknown error occured|fail";
		exit();
	}else if($gallery != "Myself" && $gallery != "Family" && $gallery != "Pets" && $gallery != "Friends" && $gallery != "Games" && $gallery != "Freetime" && $gallery != "Games" && $gallery != "Sports" && $gallery != "Knowledge" && $gallery != "Hobbies" && $gallery != "Working" && $gallery != "Relations" && $gallery != "Other"){
	    echo "Please give a valid category|fail";
		exit();
	}
	$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/$db_file_name");
	if ($moveResult != true) {
		exit();
	}

	//$spinPic = change_orientation("../user/$log_username/$db_file_name");

	include_once("../php_includes/image_resize.php");
	$wmax = 800;
	$hmax = 600;
	if($width > $wmax || $height > $hmax){
		$target_file = "../user/$log_username/$db_file_name";
	    $resized_file = "../user/$log_username/$db_file_name";
		img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	}
	if($description == ""){
		$sql = "INSERT INTO photos(user, gallery, filename, uploaddate) VALUES (?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$log_username,$gallery,$db_file_name);
		$stmt->execute();
		$stmt->close();
	}else{
		$sql = "INSERT INTO photos(user, gallery, filename, description, uploaddate) VALUES (?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$log_username,$gallery,$db_file_name,$description);
		$stmt->execute();
		$stmt->close();
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
		$app = "Newly Uploaded Photo <img src='/images/picture.png' class='notfimg'>";
		$note = $log_username.' changed uploaded a new photo into one of his/her galleries: <br /><a href="/user/'.$log_username.'/">Check it now</a>';
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
		$app = "Newly Uploaded Photo | your following, ".$log_username.", uploaded a new photo into one of his/her galleries <img src='/images/picture.png' class='notfimg'>";
		$note = '<a href="/user/'.$log_username.'/">Check it now</a>';
		// Insert into database
		$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
		$stmt->execute();
		$stmt->close();			
	}
	
	mysqli_close($conn);
	echo "upload_complete|";
	exit();
}
?><?php 
if (isset($_POST["delete"]) && $_POST["id"] != ""){
	$id = preg_replace('#[^0-9]#', '', $_POST["id"]);
	$sql = "SELECT user, filename FROM photos WHERE id=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i",$id);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$user = $row["user"];
		$filename = $row["filename"];
	}
	if($user == $log_username){
		$picurl = "../user/$log_username/$filename"; 
	    if (file_exists($picurl)) {
			unlink($picurl);
			$stmt->close();
			$sql = "DELETE FROM photos WHERE id=? LIMIT 1";
	        $stmt = $conn->prepare($sql);
	        $stmt->bind_param("i",$id);
	        $stmt->execute();
	        $stmt->close();
		}
	}
	mysqli_close($conn);
	echo "deleted_ok";
	exit();
}
?>
<?php
	if(isset($_FILES["stPic"]["name"]) && $_FILES["stPic"]["tmp_name"] != ""){
		$fileName = $_FILES["stPic"]["name"];
		$fileTmpLoc = $_FILES["stPic"]["tmp_name"];
		$fileType = $_FILES["stPic"]["type"];
		$fileSize = $_FILES["stPic"]["size"];
		$fileErrorMsg = $_FILES["stPic"]["error"];
		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);
		list($width, $height) = getimagesize($fileTmpLoc);
		if($width < 10 || $height < 10){
			echo "Image is too small|fail";
			exit();
		}
		$db_file_name = imgHash($log_username,$fileExt);
		if($fileSize > 5242880){
			echo "Your image was larger than 5mb|fail";
			exit();
		}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
			echo "Your image file was not png, jpg, gif or jpeg type|fail";
			exit();
		}else if($fileErrorMsg == 1){
			echo "An unknown error occured|fail";
			exit();
		}
		if(move_uploaded_file($fileTmpLoc, "../tempUploads/$db_file_name")){
			echo "upload_complete|$db_file_name";
		}else{
			echo "move_uploaded_file function failed|fail";
		}
	}
?>
<?php
	if(isset($_FILES["stPic_reply"]["name"]) && $_FILES["stPic_reply"]["tmp_name"] != ""){
		$fileName = $_FILES["stPic_reply"]["name"];
		$fileTmpLoc = $_FILES["stPic_reply"]["tmp_name"];
		$fileType = $_FILES["stPic_reply"]["type"];
		$fileSize = $_FILES["stPic_reply"]["size"];
		$fileErrorMsg = $_FILES["stPic_reply"]["error"];
		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);
		list($width, $height) = getimagesize($fileTmpLoc);
		if($width < 10 || $height < 10){
			echo "Image is too small|fail";
			exit();
		}
		$db_file_name = imgHash($log_username,$fileExt);
		if($fileSize > 5242880){
			echo "Your image was larger than 5mb|fail";
			exit();
		}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
			echo "Your image file was not png, jpg, gif or jpeg type|fail";
			exit();
		}else if($fileErrorMsg == 1){
			echo "An unknown error occured|fail";
			exit();
		}
		if(move_uploaded_file($fileTmpLoc, "../tempUploads/$db_file_name")){
			echo "upload_complete_reply|$db_file_name";
		}else{
			echo "move_uploaded_file function failed|fail";
		}
	}
?>
<?php
	if(isset($_FILES["stPic_pm"]["name"]) && $_FILES["stPic_pm"]["tmp_name"] != ""){
		$fileName = $_FILES["stPic_pm"]["name"];
		$fileTmpLoc = $_FILES["stPic_pm"]["tmp_name"];
		$fileType = $_FILES["stPic_pm"]["type"];
		$fileSize = $_FILES["stPic_pm"]["size"];
		$fileErrorMsg = $_FILES["stPic_pm"]["error"];
		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);
		list($width, $height) = getimagesize($fileTmpLoc);
		if($width < 10 || $height < 10){
			echo "Image is too small|fail";
			exit();
		}
		$db_file_name = imgHash($log_username,$fileExt);
		if($fileSize > 5242880){
			echo "Your image was larger than 5mb|fail";
			exit();
		}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
			echo "Your image file was not png, jpg, gif or jpeg type|fail";
			exit();
		}else if($fileErrorMsg == 1){
			echo "An unknown error occured|fail";
			exit();
		}
		if(move_uploaded_file($fileTmpLoc, "../tempUploads/$db_file_name")){
			echo "upload_complete_pm|$db_file_name";
		}else{
			echo "move_uploaded_file function failed|fail";
		}
	}
?>
<?php
	if(isset($_FILES["stPic_msg"]["name"]) && $_FILES["stPic_msg"]["tmp_name"] != ""){
		$fileName = $_FILES["stPic_msg"]["name"];
		$fileTmpLoc = $_FILES["stPic_msg"]["tmp_name"];
		$fileType = $_FILES["stPic_msg"]["type"];
		$fileSize = $_FILES["stPic_msg"]["size"];
		$fileErrorMsg = $_FILES["stPic_msg"]["error"];
		$sid = $_POST['sid'];
		$kaboom = explode(".", $fileName);
		$fileExt = end($kaboom);
		list($width, $height) = getimagesize($fileTmpLoc);
		if($width < 10 || $height < 10){
			echo "Image is too small|fail";
			//exit();
		}
		$db_file_name = imgHash($log_username,$fileExt);
		if($fileSize > 5242880){
			echo "Your image was larger than 5mb|fail";
			//exit();
		}else if(!preg_match("/\.(gif|jpg|png|jpeg)$/i", $fileName)){
			echo "Your image file was not png, jpg, gif or jpeg type|fail";
			//exit();
		}else if($fileErrorMsg == 1){
			echo "An unknown error occured|fail";
			//exit();
		}
		if(move_uploaded_file($fileTmpLoc, "../tempUploads/$db_file_name")){
			echo "upload_complete_msg|$db_file_name|$sid";
		}else{
			echo "move_uploaded_file function failed|fail";
		}
	}
?>
<?php
	if(isset($_POST["gal"]) && isset($_POST["u"])){
		$gal_all = "";
		$gal_temp = preg_replace('#[^a-z 0-9,]#i', '', $_POST["gal"]);
		$u = mysqli_real_escape_string($conn, $_POST["u"]);
		$sql = "SELECT * FROM photos WHERE gallery = ? AND user = ? ORDER BY uploaddate DESC";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$gal_temp,$u);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$file = $row["filename"];
			$gallery = $row["gallery"];
			$gal_all .= "$file|$gallery|$u|||";
		}
		$stmt->close();
		mysqli_close($conn);
		$gal_all = trim($gal_all, "|||");
		echo $gal_all;
		exit();
	}
?>
<?php
	if(isset($_POST["gal_less"]) && isset($_POST["u_less"])){
		$gall_less = "";
		$gal_temp_ = preg_replace('#[^a-z 0-9,]#i', '', $_POST["gal_less"]);
		$u_ = mysqli_real_escape_string($conn, $_POST["u_less"]);
		$sql = "SELECT * FROM photos WHERE gallery = ? AND user = ? ORDER BY uploaddate DESC LIMIT 4";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$gal_temp_,$u_);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$file = $row["filename"];
			$gallery = $row["gallery"];
			$gall_less .= "$file|$gallery|$u_|||";
		}
		$stmt->close();
		mysqli_close($conn);
		$gall_less = trim($gall_less, "|||");
		echo $gall_less;
		exit();
	}
?>
<?php
    if(!empty($_FILES['file']['name'][0])){
        foreach($_FILES['file']['name'] as $pos => $name){
            echo $name."|";
        }
        echo "asd";
        exit();
    }
    
?>