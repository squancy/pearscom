<?php
	include_once("../php_includes/check_login_statues.php");
	require_once "../safe_encrypt.php";
	require_once '../ccov.php';
	
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
	$a = "a";
	$b = "b";
?>
<?php
	if (isset($_POST['action']) && $_POST['action'] == "status_post"){
		// Make sure post data and image is not empty
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
		// We just have an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}
		
		// Make sure account name exists (the profile being posted on)
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
		$sql = "INSERT INTO status(account_name, author, type, data, postdate) 
				VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$account_name,$log_username,$type,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
		$sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$id,$id);
		$stmt->execute();
		$stmt->close();
		// Count posts of type "a" for the person posting and evaluate the count
		$sql = "SELECT COUNT(id) FROM status WHERE author=? AND type=?";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$log_username,$a);
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
			$app = "Status Post <img src='/images/post.png' class='notfimg'>";
			$note = $log_username.' posted on '.$account_name.'&#39;s profile: <br /><a href="/user/'.$account_name.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Status Post | your following, ".$log_username.", posted on ".$account_name."&#39;s profile: <img src='/images/post.png' class='notfimg'>";
			$note = '<a href="/user/'.$account_name.'/#status_'.$id.'">Check it now</a>';
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
	if (isset($_POST['action']) && $_POST['action'] == "bd_wish"){
		// Make sure post data and image is not empty
		if(strlen($_POST['data']) == "" || $_POST['bduser'] == ""){
			mysqli_close($conn);
		    echo "data_empty";
		    exit();
		}

		if($_POST['type'] != "bd_wish"){
			mysqli_close($conn);
		    echo "type_unknown";
		    exit();
		}

		// Clean all of the $_POST vars that will interact with the database
		$type = mysqli_real_escape_string($conn, $_POST['type']);
		$account_name = mysqli_real_escape_string($conn, $_POST["bduser"]);
		$data = htmlentities($_POST['data']);
		
		$data = '<i>'.$log_username.' wished a happy birthday to you and wrote this message: <img src="/images/bdcake.png" width="14" height="14"></i><br>'.$data;
		
		// Make sure account name exists (the profile being posted on)
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
		$sql = "INSERT INTO status(account_name, author, type, data, postdate) 
				VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$account_name,$log_username,$type,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
		$sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii",$id,$id);
		$stmt->execute();
		$stmt->close();
		// Count posts of type "a" for the person posting and evaluate the count
		$sql = "SELECT COUNT(id) FROM status WHERE author=? AND type=?";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$log_username,$a);
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
			$app = "Birthday Wish <img src='/images/post.png' class='notfimg'>";
			$note = $log_username.' posted on '.$account_name.'&#39;s birthday: <br /><a href="/user/'.$account_name.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Birthday Wish | your following, ".$log_username.", posted on ".$account_name."&#39;s birthday: <img src='/images/post.png' class='notfimg'>";
			$note = '<a href="/user/'.$account_name.'/#status_'.$id.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();
		}
		mysqli_close($conn);
		echo "bdsent_ok";
		exit();
	}
?>
<?php 
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
		// We just have an image
		if($data == "||na||" && $image != "na"){
			$data = '<img src="/permUploads/'.$image.'" /><br>';
		// We have an image and text
		}else if($data != "||na||" && $image != "na"){
			$data = $data.'<br /><img src="/permUploads/'.$image.'" /><br>';
		}
		
		// Make sure account name exists (the profile being posted on)
		$sql = "SELECT COUNT(id) FROM users WHERE username=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$log_username,$one);
		$stmt->execute();
		$stmt->bind_result($row);
		$stmt->fetch();
		if($row < 1){
			mysqli_close($conn);
			echo "$account_no_exist";
			exit();
		}
		$stmt->close();
		// Insert the status reply post into the database now
		$sql = "INSERT INTO status(osid, account_name, author, type, data, postdate)
		        VALUES(?,?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issss",$osid,$account_name,$log_username,$b,$data);
		$stmt->execute();
		$row = $stmt->num_rows;
		if($row < 1){
			$id = mysqli_insert_id($conn);
		}
		$id = mysqli_insert_id($conn);
		// Insert the status post into the database now
		$sql = "INSERT INTO status(account_name, author, type, data, postdate) 
				VALUES(?,?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$account_name,$log_username,$type,$data);
		$stmt->execute();
		$stmt->close();
		$id = mysqli_insert_id($conn);
		// Count posts of type "a" for the person posting and evaluate the count
		$sql = "SELECT COUNT(id) FROM status WHERE author=? AND type=?";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$log_username,$a);
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
			$app = "Status Reply <img src='/images/reply.png' class='notfimg'>";
			$note = $log_username.' commented on '.$account_name.'&#39;s profile: <br /><a href="/user/'.$account_name.'/#reply_'.$id.'">Check it now</a>';
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
			$app = "Status Reply | your following, ".$log_username.", commented on ".$account_name."&#39;s profile: <img src='/images/reply.png' class='notfimg'>";
			$note = '<a href="/user/'.$account_name.'/#reply_'.$id.'">Check it now</a>';
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
	if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
		if(!isset($_POST['statusid']) || $_POST['statusid'] == ""){
			mysqli_close($conn);
			echo "status id is missing";
			exit();
		}
		$statusid = preg_replace('#[^0-9]#', '', $_POST['statusid']);
		// Check to make sure this logged in user actually owns that comment
		$sql = "SELECT account_name, author, data FROM status WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$statusid);
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
			$sql = "DELETE FROM status WHERE osid=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$statusid);
			$stmt->execute();
			$stmt->close();
			mysqli_close($conn);
		    echo "delete_ok";
			exit();
		}
	}
?><?php 
	if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
		if(!isset($_POST['replyid']) || $_POST['replyid'] == ""){
			mysqli_close($conn);
			exit();
		}
		$replyid = preg_replace('#[^0-9]#', '', $_POST['replyid']);
		// Check to make sure the person deleting this reply is either the account owner or the person who wrote it
		$sql = "SELECT osid, account_name, author FROM status WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$replyid);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			$osid = $row["osid"];
			$account_name = $row["account_name"];
			$author = $row["author"];
		}
		$stmt->close();
	    if ($author == $log_username || $account_name == $log_username) {
			$sql = "DELETE FROM status WHERE id=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i",$replyid);
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
		$sql = "SELECT author, data FROM status WHERE id=? LIMIT 1";
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
		$sql = "SELECT * FROM status WHERE id=? LIMIT 1";
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
<?php
	if(isset($_POST['action']) && $_POST['action'] == "share_art"){
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
		$sql = "SELECT id FROM articles WHERE id=? LIMIT 1";
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
		$sql = "SELECT * FROM articles WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$id);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$written_by = $row["written_by"];
			$wb_original = $written_by;
			$title = $row["title"];
			$tags = $row["tags"];
			$post_time_ = $row["post_time"];
			$pt = base64url_encode($post_time_,$hshkey);
			$posttime = strftime("%b %d, %Y", strtotime($post_time_));
			$cat = $row["category"];
			$cover = chooseCover($cat);
			
			$cover = preg_replace('/<img src="\/images\/\w+\/(\w+)\.jpg"\s+class="cover_art">/', "/images/art_cover/$1.jpg", $cover);

			$data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared photo via <a href="/user/'.$wb_original.'/">'.$written_by.'</a></p></div><hr class="dim">';
			$data .= '<a href="/articles/'.$pt.'/'.$wb_original.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src=\''.$cover.'\'></div></a><br />';
			$data .= '<div class="txtc"><b style="font-size: 14px;">Title: </b>'.$title.'<br />';
			$data .= '<b style="font-size: 14px;">Published: </b>'.$posttime.'<br />';
			$data .= '<b style="font-size: 14px;">Category: </b>'.$cat.'</div>';

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
			$app = "Shared Article <img src='/images/black_share.png' class='notfimg'>";
			$note = $log_username.' shared an article.<br /><a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Shared Article | your following, ".$log_username.", shared an article <img src='/images/black_share.png' class='notfimg'>";
			$note = '<a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "share_art_ok";
		exit();
	}
?>
<?php
	if(isset($_POST['action']) && $_POST['action'] == "share_photo"){
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
		$sql = "SELECT id FROM photos WHERE id=? LIMIT 1";
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
		$sql = "SELECT * FROM photos WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$id);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$user = $row["user"];
			$user_ori = $user;
			$gallery = $row["gallery"];
			$filename = $row["filename"];
			$des = $row["description"];
			if(strlen($des) > 60){
			    $des = substr($des, 0, 57);
			    $des .= "...";
			}
			$uploaddate_ = $row["uploaddate"];
			$ud = strftime("%b %d, %Y", strtotime($uploaddate_));
			$data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared photo via <a href="/user/'.$user_ori.'/">'.$user.'</a></p></div><hr class="dim">';
			$data .= '<a href="/photo_zoom/'.$user_ori.'/'.$filename.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src="/user/'.$user_ori.'/'.$filename.'"></div></a><br>';
			$data .= '<div style="text-align: center;"><b style="font-size: 14px;">Gallery: </b>'.$gallery.'<br />';
			$data .= '<b style="font-size: 14px;">Published: </b>'.$ud.'<br />';
			$data .= '<b style="font-size: 14px;">Description: </b>'.$des.'</div>';
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
			$app = "Shared Photo <img src='/images/black_share.png' class='notfimg'>";
			$note = $log_username.' shared a photo.<br /><a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Shared Photo | your following, ".$log_username.", shared a photo <img src='/images/black_share.png' class='notfimg'>";
			$note = '<a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "share_photo_ok";
		exit();
	}
?>
<?php
	if(isset($_POST['action']) && $_POST['action'] == "share_video"){
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
		$sql = "SELECT id FROM videos WHERE id=? LIMIT 1";
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
		$sql = "SELECT * FROM videos WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i",$id);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$user = $row["user"];
			$user_ori = $user;
			$video_name = $row["video_name"];
			$video_description = $row["video_description"];
			$video_poster = $row["video_poster"];
			$video_file = $row["video_file"];
			$uploaddate = $row["video_upload"];
			$vup = strftime("%b %d, %Y", strtotime($uploaddate));
			if($video_name == ""){
				$video_name = "Untitled";
			}

			if($video_description == ""){
				$video_description = "not given";
			}
			
			if(strlen($video_description) > 60){
			    $video_description = substr($video_description, 0, 57);
			    $video_description .= "...";
			}

			if($video_poster == NULL){
				$video_poster = 'images/defaultimage.png';
			}else{
				$video_poster = '/user/'.$user_ori.'/videos/'.$video_poster;
			}
            $id = base64url_encode($id,$hshkey);
			$data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared video via <a href="/user/'.$user_ori.'/">'.$user.'</a></p></div><hr class="dim">';
			$data .= '<a href="/video_zoom/'.$id.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src=\''.$video_poster.'\'></div></a><br />';
			$data .= '<div class="txtc"><b style="font-size: 14px;">Title: </b>'.$video_name.'<br />';
			$data .= '<b style="font-size: 14px;">Description: </b>'.$video_description.'<br />';
			$data .= '<b style="font-size: 14px;">Published: </b>'.$vup.'</div>';

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
			$app = "Shared Video <img src='/images/black_share.png' class='notfimg'>";
			$note = $log_username.' shared a video.<br /><a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
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
			$app = "Shared Photo | your following, ".$log_username.", shared a video <img src='/images/black_share.png' class='notfimg'>";
			$note = '<a href="/user/'.$log_username.'/#status_'.$id.'">Check it now</a>';
			// Insert into database
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
			$stmt->execute();
			$stmt->close();			
		}

		mysqli_close($conn);
		echo "share_video_ok";
		exit();
	}
?>
