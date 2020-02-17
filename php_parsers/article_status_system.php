<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	require_once '../safe_encrypt.php';
  require_once '../php_includes/ind.php';

	if($user_ok != true || $log_username == "") {
		exit();
	}

    $ar = "";
    if(isset($_POST["arid"]) && $_POST["arid"] != ""){
        $ar = $_POST["arid"];
    }else if(isset($_SESSION["id"]) && !empty($_SESSION["id"])){
        $ar = $_SESSION["id"];
    }
?>
<?php
	// Ajax calls this code to execute
	if (isset($_POST['action']) && $_POST['action'] == "status_post"){
		// Make sure post data and image is not empty
		if(strlen($_POST['data']) < 1 && $_POST['image'] == "na"){
			mysqli_close($conn);
		    echo "data_empty";
		    exit();
		}
		$image = $_POST['image'];
		// Move the image(s) to the permanent folder
		if($image != "na"){
			$kaboom = explode(".", $image);
			$fileExt = end($kaboom);
			rename("../tempUploads/$image", "../permUploads/$image");
			require_once '../php_includes/image_resize.php';
			$target_file = "../permUploads/$image";
			$resized_file = "../permUploads/$image";
			// Set the maximum width and height
			$wmax = 400;
			$hmax = 500;
			list($width, $height) = getimagesize($target_file);
			if($width > $wmax || $height > $hmax){
				// Resize image
				img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
			}
		}
		// Make sure type is either a or c
		// if($_POST['type'] != "a" || $_POST['type'] != "c"){
		if($_POST['type'] != ("a" || "c")){
			mysqli_close($conn);
		    echo "type_unknown";
		    exit();
		}

		// Clean all of the $_POST vars that will interact with the database
		$type = preg_replace('#[^a-z]#', '', $_POST['type']);
		$account_name = mysqli_real_escape_string($conn, $_POST["user"]);
		$data = htmlentities($_POST['data']);
		// The user only posted an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}

		// Make sure account name exists (the profile being posted on)
		$one = '1';
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$account_name,$one);
	    $stmt->execute();
	    $stmt->bind_result($row);
	    $stmt->fetch();
		if($row < 1){
			mysqli_close($conn);
			echo "$account_no_exist";
			exit();
		}
		$stmt->close();
		// Insert the status post into the database now
		$sql = "INSERT INTO article_status(account_name, author, type, data, artid, postdate) 
				VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssssi",$account_name,$log_username,$type,$data,$ar);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
		$sql = "UPDATE article_status SET osid=? WHERE id=? AND artid = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("iii",$id,$id,$ar);
		$stmt->execute();
		$stmt->close();
		// Count posts of type "a" for the person posting and evaluate the count
		$a = 'a';
		$sql = "SELECT COUNT(id) FROM article_status WHERE author=? AND type=? AND artid = ?";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ssi",$log_username,$a,$ar);
	    $stmt->execute();
	    $stmt->bind_result($row);
	    $stmt->fetch();
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
			$sql = "SELECT * FROM articles WHERE id = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$ar);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$ptime = $row["post_time"];
			}
			$ptime = base64url_encode($ptime,$hshkey);
			$stmt->close();
			$app = "Article Status Post <img src='/images/post.png' class='notfimg'>";
			$note = $log_username.' posted on: <br /><a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$id.'">Below an article</a>';
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
			$sql = "SELECT * FROM articles WHERE id = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$ar);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$ptime = $row["post_time"];
			}
			$ptime = base64url_encode($ptime,$hshkey);
			$stmt->close();
			$app = "Article Status Post | your following, ".$friend.", posted on an article <img src='/images/post.png' class='notfimg'>";
			$note = $log_username.' posted: <br /><a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$id.'">Below an article</a>';
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
?><?php 
	//action=status_reply&osid="+osid+"&user="+user+"&data="+data
	if (isset($_POST['action']) && $_POST['action'] == "status_reply"){
		// Make sure data is not empty
		if(strlen($_POST['data']) < 1 && $_POST['image'] == "na"){
			mysqli_close($conn);
		    echo "data_empty";
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

		// Clean the posted variables
		$osid = preg_replace('#[^0-9]#', '', $_POST['sid']);
		$account_name = mysqli_real_escape_string($conn, $_POST["user"]);
		$data = htmlentities($_POST['data']);

    if ($ar == "") {
      $ar = indexId($conn, $osid, "article_status", "artid");
    }

		// We just have an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}
		
		// Make sure account name exists (the profile being posted on)
		$one = "1";
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$account_name,$one);
		$stmt->execute();
		$stmt->bind_result($row);
		$stmt->fetch();
		if($row < 1){
			mysqli_close($conn);
			echo "$account_no_exist";
			exit();
		}
		// Insert the status reply post into the database now
		$stmt->close();

		$b = 'b';
		$sql = "INSERT INTO article_status(osid,account_name,author,type,data,artid,postdate) VALUES(?,?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issssi",$osid,$account_name,$log_username,$b,$data,$ar);
		$stmt->execute();
		$row = $stmt->num_rows;
		if($row < 1){
			$id = mysqli_insert_id($conn);
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
			$sql = "SELECT * FROM articles WHERE id = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$ar);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$ptime = $row["post_time"];
			}
			$ptime = base64url_encode($ptime,$hshkey);
			$stmt->close();
			$app = "Article Status Reply <img src='/images/reply.png' class='notfimg'>";
			$note = $log_username.' posted on: <br /><a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$osid.'">Below an article</a>';
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
			$sql = "SELECT * FROM articles WHERE id = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$ar);
			$stmt->execute();
			$res = $stmt->get_result();
			while($row = $res->fetch_assoc()){
				$ptime = $row["post_time"];
			}
			$ptime = base64url_encode($ptime,$hshkey);
			$stmt->close();
			$app = "Article Status Post | your follower, ".$friend.", posted on an article <img src='/images/reply.png' class='notfimg'>";
			$note = $log_username.' posted: <br /><a href="/articles/'.$ptime.'&u='.$log_username.'/#status_'.$osid.'">Below an article</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "reply_ok|$id";
		exit();
	}
?><?php
	// Ajax calls this code to execute
	if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
		if(!isset($_POST['statusid']) || $_POST['statusid'] == ""){
			mysqli_close($conn);
			echo "status id is missing";
			exit();
		}
		$statusid = preg_replace('#[^0-9]#', '', $_POST['statusid']);
		// Check to make sure this logged in user actually owns that comment
		$sql = "SELECT account_name, author, data FROM article_status WHERE id=? AND artid=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$statusid,$ar);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			$account_name = $row["account_name"]; 
			$author = $row["author"];
			$data = $row["data"];
		}
		$stmt->close();
	    if ($author == $log_username || $account_name == $log_username) {
	    	// Check for images
	    	if(preg_match('/<img.+src=[\'"](?P<src>.+)[\'"].*>/i', $data, $has_image)){
				$source = '../'.$has_image['src'];
				if (file_exists($source)) {
	        		unlink($source);
	    		}
			}
			$sql = "DELETE FROM article_status WHERE osid=? AND artid = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ii",$statusid,$ar);
			$stmt->execute();
			$stmt->close();

			mysqli_close($conn);
		    echo "delete_ok";
			exit();
		}
	}
?><?php
	// Ajax calls this code to execute
	if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
		// Make sure the $_POST['replyid'] is set and not empty
		if(!isset($_POST['replyid']) || $_POST['replyid'] == ""){
			mysqli_close($conn);
			exit();
		}
		// Clean $_POST['replyid']
		$replyid = preg_replace('#[^0-9]#', '', $_POST['replyid']);
		// Check to make sure the person deleting this reply is either the account owner or the person who wrote it
		$sql = "SELECT osid, account_name, author FROM article_status WHERE id=? AND artid = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$replyid,$ar);
		$stmt->execute();
		$stmt->bind_result($osid,$account_name,$author);
		$stmt->fetch();
		$stmt->close();
		// Delete status
	    if ($author == $log_username || $account_name == $log_username) {
			$sql = "DELETE FROM article_status WHERE id=? AND artid = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ii",$replyid,$ar);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
		    echo "delete_ok";
			exit();
		}
	}
?>
<?php
	if(isset($_POST['action']) && $_POST['action'] == "share"){
	    $a = "a";
		if(!isset($_POST['id'])){
			mysql_close($conn);
			echo "fail";
			exit();
		}
		$id = preg_replace('#[^0-9]#', '', $_POST['id']);
		if($id == ""){
			mysqli_close($conn);
			echo "fail";
			exit();
		}

    if ($ar == "") {
      $ar = indexId($conn, $id, "article_status", "artid");
    }

		$sql = "SELECT author, data FROM article_status WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$id);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		if($numrows < 1){
			mysqli_close($conn);
			echo "fail";
			exit();
		}
		$stmt->close();
		$sql = "SELECT * FROM article_status WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$id);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared via <a href="/user/'.$row["author"].'/">'.$row["author"].'</a></p></div><hr class="dim">';
			$data .= '<div id="share_data">'.$row["data"].'</div>';
			$stmt->close();
			$sql = "INSERT INTO status(account_name, author, type, data, postdate) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$log_username,$log_username,$a,$data);
			$stmt->execute();
			$stmt->close();
			$id = mysqli_insert_id($conn);
		}
		$sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
 		$stmt = $conn->prepare($sql);
 		$stmt->bind_param("ii",$id,$id);
 		$stmt->execute();
 		$stmt->close();
		mysqli_close($conn);
		echo "share_ok";
		exit();
	}
?>
