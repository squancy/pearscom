<?php
	require_once 'php_includes/check_login_statues.php';
	/*function findDistance($uAlatlon, $uBlatlon){
		$u1 = explode(',', $uAlatlon);
		$lat1 = $u1[0];
		$lon2 = $u1[1];

		$u2 = explode(',', $uBlatlon);
		$lat2 = $u2[0];
		$lon2 = $u2[1];

		$dist = acos(sin(deg2rad($lat1))
				* sin(deg2rad($lat2))
				+ cos(deg2rad($lat1))
				* cos(deg2rad($lat2))
				* cos(deg2rad($lon1 - $lon2)));

		$dist = rad2deg($dist);
		$miles = (float) $dist * 69;
		$km = (float) $miles * 1.61;

		$totalDist = sprintf("%0.2f", $miles).' miles';
		$totalDist .= ' ('.sprintf("%0.2f", $km).' kilometres)';
		
		return $totalDist;
	}*/

	// Initialize any variables that the page might echo
	$u = "";
	$sex = "Male";
	$userlevel = "";
	$country = "";
	$joindate = "";
	$lastsession = "";
	$profile_pic = "";
	$profile_pic_btn = "";
	$avatar_form = "";
	$background_form = "";
	$one = "1";

	// Make sure the _GET username is set and sanitize it
	if(isset($_GET["u"])){
		$u = preg_replace('#[^a-z0-9 .-]#i', '', $_GET['u']);
	}else{
		header('Location: index.php');
		exit();
	}

	// Select the member from the users table
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();

	// Now make sure the user exists in the table
	if($result->num_rows < 1){
		echo "<b style='text-align: center;'>That user does not exist or is not yet activated, press back</b>";
		exit();
	}

	// Check to see if the viewer is the account owner
	$isOwner = "No";
	if($u == $log_username && $user_ok == true){
		$isOwner = "Yes";
		$profile_pic_btn = '<a href="#" onclick="return false;" id="ca" onmousedown="toggleElement(\'avatar_form\')">Change Avatar</a>';
		$avatar_form  = '<form id="avatar_form" enctype="multipart/form-data" method="post" action="php_parsers/photo_system.php">';
		$avatar_form .=   '<h4>Change your avatar</h4>';
		$avatar_form .=   '<input type="file" name="avatar" id="file" class="inputfile" required>';
		$avatar_form .=   '<label for="file">Choose a file</label>';
		$avatar_form .=   '<p><input type="submit" value="Upload"></p>';
		$avatar_form .= '</form>';

		// Background form
		$background_form  = '<form id="background_form" enctype="multipart/form-data" method="post" action="php_parsers/photo_system.php">';
		$background_form .=   '<h4>Change your background</h4>';
		$background_form .=   '<input type="file" name="background" id="bfile" class="inputfile" required>';
		$background_form .=   '<label for="bfile">Choose a file</label>';
		$background_form .=   '<p><input type="submit" value="Upload Background"></p>';
		$background_form .= '</form>';
	}

	// Fetch the user row from the query above
	while($row = $result->fetch_assoc()){
		$profile_id = $row["id"];
		$gender = $row["gender"];
		$country = $row["country"];
		$userlevel = $row["userlevel"];
		$signup = $row["signup"];
		$avatar = $row["avatar"];
		$lastlogin = $row["lastlogin"];
		$joindate = strftime("%b %d, %Y", strtotime($signup));
		$lastsession = strftime("%b %d, %Y", strtotime($lastlogin));
		// Get the latlon as user A
		$uAlatlon = $row["latlon"];
		$bdate = substr($row["bday"], 5, 9);
		$birthday_ = $row["bday"];
		$birthday = strftime("%b %d, %Y", strtotime($birthday_));
		$birthday_year = substr($row["bday"], 0, 4);
	}
	$is_birthday = "no";
	$today_is = date('m-d');
	if($today_is == $bdate){
		$is_birthday = "yes";
	}
	$leap = date("L");
	if($leap == '0' && $today_is == "02-28" && $bdate == '02-29'){
		$is_birthday = "yes";
	}

	if($gender == "f"){
		$sex = "Female";
	}
	$profile_pic = '<img src="user/'.$u.'/'.$avatar.'" alt="'.$u.'">';

	if($avatar == NULL){
		$profile_pic = '<img src="images/avdef.png">';
	}

	$current_year = date("Y");
	$age = $current_year - $birthday_year;

	$stmt->close();

	// Get the latlon as user B
	$uBlatlon = "";
	if(isset($log_username)){
		$sql = "SELECT latlon FROM users WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$log_username);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$uBlatlon = $row["latlon"];
		}

		$stmt->close();
	}

	/*
	$uBlatlon = "";
	if(isset($log_username)){
		$result = mysqli_query($conn, "SELECT latlon FROM users WHERE username='$log_username' LIMIT 1");
		while($row = mysqli_fetch_row($result)){
			$uBlatlon = $row[0];
		}
	}
	*/

	$totalDist = "";
	if(($uAlatlon != "") && ($uBlatlon != "")){
		$totalDist = findDistance($uAlatlon,$uBlatlon);
	}

	if($userlevel == "a"){
		$userlevel = "Verified";
	}else if($userlevel == "b"){
		$userlevel = "Not Verified";
	}else{
		$userlevel = "Not verified";
	}
?>
<?php
	$isFriend = false;
	$ownerBlockViewer = false;
	$viewerBlockOwner = false;
	if($u != $log_username && $user_ok == true){
		$friend_check = "SELECT id FROM friends WHERE user1=? AND user2=? AND accepted=? OR user1=? AND user2=? AND accepted=? LIMIT 1";
		$stmt = $conn->prepare($friend_check);
		$stmt->bind_param("ssssss",$log_username,$u,$one,$u,$log_username,$one);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		if($numrows > 0){
	        $isFriend = true;
	    }
	    $stmt->close();

		$block_check1 = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
		$stmt = $conn->prepare($block_check1);
		$stmt->bind_param("ss",$u,$log_username);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows2 = $stmt->num_rows;
		if($numrows2 > 0){
	        $ownerBlockViewer = true;
	    }
	    $stmt->close();

		$block_check2 = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
		$stmt = $conn->prepare($block_check2);
		$stmt->bind_param("ss",$log_username,$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows3 = $stmt->num_rows;
		if($numrows3 > 0){
	        $viewerBlockOwner = true;
	    }
	    $stmt->close();
	}
?>
<?php 
	$friend_button = '<button style="opacity: 0.6; cursor: not-allowed;">Request As Friend</button>';
	$block_button = '<button style="opacity: 0.6; cursor: not-allowed;">Block User</button>';

	// LOGIC FOR FRIEND BUTTON
	if($isFriend == true){
		$friend_button = '<button onclick="friendToggle(\'unfriend\',\''.$u.'\',\'friendBtn\')">Unfriend </button>';
	} else if($user_ok == true && $u != $log_username && $ownerBlockViewer == false){
		$friend_button = '<button onclick="friendToggle(\'friend\',\''.$u.'\',\'friendBtn\')">Request As Friend </button>';
	}

	// LOGIC FOR BLOCK BUTTON
	if($viewerBlockOwner == true){
		$block_button = '<button onclick="blockToggle(\'unblock\',\''.$u.'\',\'blockBtn\')">Unblock User </button>';
	} else if($user_ok == true && $u != $log_username){
		$block_button = '<button onclick="blockToggle(\'block\',\''.$u.'\',\'blockBtn\')">Block User </button>';
	}
?>
<?php
	$isFollow = false;
	if($u != $log_username && $user_ok == true){
		$follow_check = "SELECT id FROM follow WHERE follower=? AND following=? LIMIT 1";
		$stmt = $conn->prepare($follow_check);
		$stmt->bind_param("ss",$log_username,$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
	if($numrows > 0){
	        $isFollow = true;
		}
		$stmt->close();
    }
?>
<?php
	$follow_button = "";
	$isFollowOrNot = "";
	$gs = "him";
	// Set $gender_sex if the user is male or female
	$sql = "SELECT * FROM users WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$gender_sex = $row["gender"];
	}
	$stmt->close();
	// LOGIC FOR FOLLOW BUTTON
	if($isFollow == true){
		$follow_button = '<button class="brown_color_btn" onclick="followToggle(\'unfollow\',\''.$u.'\',\'followBtn\')">Unfollow</button>';
		if($gender_sex == "f"){
			$gs = "her";
		}

		$isFollowOrNot = "You're following ".$gs;
	}else{
		$follow_button = '<button class="brown_color_btn" onclick="followToggle(\'follow\',\''.$u.'\',\'followBtn\')">Follow</button>';
		$isFollowOrNot = "You're not following ".$u;
	}
?>
<?php
	$friendsHTML = '';
	$friends_view_all_link = '';
	$sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssss",$u,$one,$u,$one);
	$stmt->execute();
	$stmt->bind_result($friend_count);
	$stmt->fetch();
	if($friend_count < 1){
		if($isOwner == "Yes"){
			$friendsHTML = '<b>You have no friends yet.</b>';
		}else{
			$friendsHTML = '<b>'.$u.' has no friends yet.</b>';
		}
	} else {
		$stmt->close();
		$max = 14;
		$all_friends = array();
		$sql = "SELECT user1 FROM friends WHERE (user2=? OR user1=?) AND accepted=? ORDER BY RAND() LIMIT $max";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$u,$u,$one);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			array_push($all_friends, $row["user1"]);
			array_push($all_friends, $row["user2"]);
		}
		$stmt->close();
		
		$friendArrayCount = count($all_friends);
		if($friendArrayCount > $max){
			array_splice($all_friends, $max);
		}
		if($friend_count > $max){
			$friends_view_all_link = '<a href="view_friends.php?u='.$u.'">View all</a>';
		}
		$orLogic = '';
		foreach($all_friends as $key => $user){
			$orLogic .= "username='$user' OR ";
		}
		$orLogic = chop($orLogic, "OR ");
		$sql = "SELECT username, avatar FROM users WHERE $orLogic";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$result3 = $stmt->get_result();
		while($row = $result3->fetch_assoc()) {
			$friend_username = $row["username"];
			$friend_avatar = $row["avatar"];
			if($friend_avatar != ""){
				$friend_pic = 'user/'.$friend_username.'/'.$friend_avatar.'';
			} else {
				$friend_pic = 'images/avdef.png';
			}
			$friendsHTML .= '<a href="user.php?u='.$friend_username.'"><img class="friendpics" src="'.$friend_pic.'" alt="'.$friend_username.'" title="'.$friend_username.'"><img class="friendpics_big" src="'.$friend_pic.'" alt="'.$friend_username.'" title="'.$friend_username.'"></a>';
		}
		$stmt->close();
	}

	// Followers count
	$followersHTML = "";
	$sql = "SELECT COUNT(id) FROM follow WHERE following=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$stmt->bind_result($follower_count);
	$stmt->fetch();
	if($follower_count < 1){
		$followersHTML = '<b>'.$u." has no followers yet.</b>";
	}

	$stmt->close();
	// Followers profile pic
	$following_div = "";
	$sql = "SELECT u.*, f.* 
			FROM users AS u
			LEFT JOIN follow AS f ON u.username = f.follower
			WHERE f.following = ? ORDER BY RAND() LIMIT 15";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$flw_pic = $row["avatar"];
		$fname = $row["username"];
		if($flw_pic == NULL){
			$pp = '<img src="images/avdef.png" width="70" height="70" title="'.$fname.'" style="border: 1px solid #336b87;" />';
		}else{
			$pp = '<img src="user/'.$fname.'/'.$flw_pic.'" width="70" height="70" title="'.$fname.'" style="border: 1px solid #336b87;" />';
		}
		$following_div .= '<a href="user.php?u='.$fname.'">'.$pp.'</a>';
	}

	$stmt->close();

	// Following profile pic
	$other_div = "";
	$sql = "SELECT u.*, f.* 
			FROM users AS u
			LEFT JOIN follow AS f ON u.username = f.following
			WHERE f.follower = ? ORDER BY RAND() LIMIT 15";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$flw_pic = $row["avatar"];
		$fname = $row["username"];
		if($flw_pic == NULL){
			$pp = '<img src="images/avdef.png" width="70" height="70" title="'.$fname.'" style="border: 1px solid #336b87;" />';
		}else{
			$pp = '<img src="user/'.$fname.'/'.$flw_pic.'" width="70" height="70" title="'.$fname.'" style="border: 1px solid #336b87;" />';
		}
		$other_div .= '<a href="user.php?u='.$fname.'">'.$pp.'</a>';
	}

	$stmt->close();

	$meFollow = "";
	$sql = "SELECT COUNT(id) FROM follow WHERE follower=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$stmt->bind_result($mecount);
	$stmt->fetch();
	if($mecount < 1 && $isOwner == "Yes"){
		$meFollow = '<b>You are not following anyone at the moment.</b>';
	}else if($mecount < 1 && $isOwner == "No"){
		$meFollow = '<b>'.$u.' is not following anyone at the moment</b>';
	}else if($mecount == 1 && $isOwner == "Yes"){
		$meFollow = '<b>You are following '.$mecount.' person at the moment</b>';
	}else if($mecount > 1 && $isOwner == "Yes"){
		$meFollow = '<b>You are following '.$mecount.' people at the moment</b>';
	}else if($mecount > 1 && $isOwner == "No"){
		$meFollow = '<b>'.$u.' is following '.$mecount.' people at the moment</b>';
	}else if($mecount == 1 && $isOwner == "No"){
		$meFollow = '<b>'.$u.' is following '.$mecount.' person at the moment</b>';
	}
	$stmt->close();

	// Create the photos button
	$photos_btn = "<button onclick='window.location = 'photos.php?u=<?php echo $u; ?>View Photos</button>";

	// Create the random photos
	$coverpic = "";
	$photo_title = "";
	$sql = "SELECT filename FROM photos WHERE user=? ORDER BY RAND() LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$filename = $row["filename"];
			$coverpic = '<img src="user/'.$u.'/'.$filename.'" alt="Photo">';
		}
	}
	$stmt->close();
?>
<?php
	$job = "";
	$about = "";
	$works = "";
	$profession = "";
	$city = "";
	$state = "";
	$mobile = "";
	$hometown = "";
	$fmovie = "";
	$fmusic = "";
	$pstatus = "";

	$elemen = "";
	$high = "";
	$uni = "";
	$politics = "";
	$religion = "";
	$nd_day = "";
	$nd_month = "";
	$interest = "";
	$notemail = "";
	$website = "";
	$language = "";
	// Gather more informations about user
	$sql = "SELECT * FROM edit WHERE username=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$job = $row["job"];
			$about = $row["about"];
			$profession = $row["profession"];
			$state = $row["state"];
			$city = $row["city"];
			$mobile = $row["mobile"];
			$hometown = $row["hometown"];
			$fmusic = $row["fav_music"];
			$fmovie = $row["fav_movie"];
			$pstatus = $row["par_status"];
			$elemen = $row["elemen"];
			$high = $row["high"];
			$uni = $row["uni"];
			$politics = $row["politics"];
			$religion = $row["religion"];
			$nd_day = $row["nd_day"];
			$nd_month = $row["nd_month"];
			$interest = $row["interest"];
			$notemail = $row["notemail"];
			$website = $row["website"];
			$language = $row["language"];
		}
		if($profession == "w"){
			$works = "Working";
		}else if($profession == "r"){
			$works = "Retired";
		}else if($profession == "u"){
			$works = "Unemployed";
		}else if($profession == "o"){
			$works = "Other";
		}else{
			$works = "Student";
		}
		if(strlen($job) > 50){
			$job = substr($job, 0, 46);
			$job .= " ...";
		}

		if(strlen($state) > 50){
			$state = substr($state, 0, 46);
			$state .= " ...";
		}

		if(strlen($city) > 50){
			$city = substr($city, 0, 46);
			$city .= " ...";
		}

		if(strlen($mobile) > 50){
			$mobile = substr($mobile, 0, 46);
			$mobile .= " ...";
		}

		if(strlen($hometown) > 50){
			$hometown = substr($hometown, 0, 46);
			$hometown .= " ...";
		}

		if(strlen($fmusic) > 50){
			$fmusic = substr($fmusic, 0, 46);
			$fmusic .= " ...";
		}

		if(strlen($fmovie) > 50){
			$fmovie = substr($fmovie, 0, 46);
			$fmovie .= " ...";
		}

		if(strlen($pstatus) > 50){
			$pstatus = substr($pstatus, 0, 46);
			$pstatus .= " ...";
		}

		if(strlen($elemen) > 50){
			$elemen = substr($elemen, 0, 46);
			$elemen .= " ...";
		}

		if(strlen($high) > 50){
			$high = substr($high, 0, 46);
			$high .= " ...";
		}

		if(strlen($uni) > 50){
			$uni = substr($uni, 0, 46);
			$uni .= " ...";
		}

		if(strlen($politics) > 50){
			$politics = substr($politics, 0, 46);
			$politics .= " ...";
		}

		if(strlen($religion) > 50){
			$religion = substr($religion, 0, 46);
			$religion .= " ...";
		}

		if(strlen($interest) > 50){
			$interest = substr($interest, 0, 46);
			$interest .= " ...";
		}

		if(strlen($notemail) > 50){
			$notemail = substr($notemail, 0, 46);
			$notemail .= " ...";
		}

		if(strlen($website) > 50){
			$website = substr($website, 0, 46);
			$website .= " ...";
		}

		if(strlen($language) > 50){
			$language = substr($language, 0, 46);
			$language .= " ...";
		}
		$stmt->close();
	}
?>
<?php
	// Add article button
	$article = "";
	if($log_username != "" && $user_ok == true && $isOwner == "Yes"){
		$article = '<button id="art_btn" style="background-color: #763626;" onclick="writeArticle()">Write Article</button>';
	}
?>
<?php
	// Echo articles
	$echo_articles = "";
	$post_time = "";
	$written_by = "";
	$sql = "SELECT * FROM articles WHERE written_by=? ORDER BY RAND() LIMIT 5";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$written_by = $row["written_by"];
			$title = $row["title"];
			$post_time_ = $row["post_time"];
			$post_time = strftime("%b %d, %Y", strtotime($post_time_));
			$title_new = $title;
			$written_by_original = $written_by;

			if(strlen($title) > 22){
				$title = substr($title, 0, 18);
				$title .= " ...";
			}

			if(strlen($written_by) > 14){
				$written_by = substr($written_by, 0, 11);
				$written_by .= ' ...';
			}

			$echo_articles .= '<div id="floright"><div id="article_header"><p id="art_title">'.$title.'</p></div><div id="article_content"><b>Written by</b>: <b style="font-weight: normal;">'.$written_by.'</b><br /><b>Posted: </b><b style="font-weight: normal;">'.$post_time.'</b><br /><br /><a href="articles.php?p='.$post_time_.'&u='.$written_by_original.'">Read More >>></a></div></div>';
		}
	}else{
		if($isOwner == "Yes"){
			$echo_articles = '<p>You have no articles</p>';
		}else{
			$echo_articles = '<p>'.$u.' has no articles</p>';
		}

		$stmt->close();
	}

	// Article view all link logic
	$article_link = "";
	$sql = "SELECT * FROM articles WHERE written_by=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows > 0){
		if($isOwner == "No"){
			$article_link = '<a href="articles_view_all.php?u='.$written_by.'">View All</a>';
		}
	}

	$stmt->close();

	// Count posts and replies
	$sql = "SELECT COUNT(id) FROM status WHERE author=? OR account_name=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$u);
	$stmt->execute();
	$stmt->bind_result($status_count);
	$stmt->fetch();
	$stmt->close();

	// Get background
	$attribute = "";
	$sql = "SELECT * FROM useroptions WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$bg = $row["background"];
	}

	$stmt->close();

	$attribute = 'user/'.$u.'/background/'.$bg;
	if($bg == NULL || $bg == "original"){
		$attribute = 'images/backgrounddefault.png';
	}

	// Get how many users online
	// Get friends arrays
	$u2 = array();
	$u1 = array();
	$sql = "SELECT user2 FROM friends WHERE user1=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$u2 = $row["user2"];
	}

	$stmt->close();

	$sql = "SELECT user1 FROM friends WHERE user2=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	while($row = $result->fetch_assoc()){
		$u1 = $row["user1"];
	}

	$stmt->close();

	// Get user photos
	$echo_photos = "";
	$sql = "SELECT * FROM photos WHERE user=? LIMIT 10";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$filename_photo = $row["filename"];
		$gallery_photo = $row["gallery"];
		$description = $row["description"];
		$echo_photos .= '<div id="user_photo"><div>'.$gallery_photo.'</div><a href="photos.php?u='.$u.'"><img src="user/'.$u.'/'.$filename_photo.'" title="'.$description.'" alt="Photo" width="100" height="100"></a></div>';
	}

	$stmt->close();

	// Get user's videos
	$videos = "";
	$sql = "SELECT * FROM videos WHERE user=? ORDER BY RAND() LIMIT 3";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$vf = $row["video_file"];
		$description = $row["video_description"];
		$video_name = $row["video_name"];
		$video_upload = $row["video_upload"];
		$pr = $row["video_poster"];
		$video_upload_ = strftime("%r, %b %d, %Y", strtotime($video_upload));
		if($video_name == ""){
			$video_name = "Untitled";
		}else if($description == ""){
			$description = "No description";
		}
		if($pr == ""){
			$pr = "images/uservid.png";
		}else{
			$pr = 'user/'.$u.'/videos/'.$pr.'';
		}
		if(strlen($description) > 35){
			$description = substr($description, 0, 31);
			$description .= " ...";
		}
		if(strlen($video_name) >  35){
			$video_name = substr($video_name, 0, 31);
			$video_name .= " ...";
		}
	    $videos .= '<div id="user_vid"><p><b>Name: </b> '.$video_name.'</p><p><b>Description: </b>'.$description.'</p><p><b>Upload date: </b>'.$video_upload_.'</p><video width="250" height="150" controls="true" poster="'.$pr.'" played><source src="user/'.$u.'/videos/'. $vf.'"></video></div>';
	}
	$stmt->close();
	$sql = "SELECT * FROM videos WHERE user=? ORDER BY RAND() LIMIT 3";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows < 1){
    	$videos = "".$u." has not uploaded any videos yet";
    }
    $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
	<title>User Profile - <?php echo $u; ?></title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="images/webicon.png">
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="jquery_in.js"></script>
	<script src="js/main.js"></script>
	<script src="js/ajax.js"></script>
	<script type="text/javascript">
		function friendToggle(e,n,t){_(t).innerHTML='<img src="images/rolling.gif" width="30" height="30">';var o=ajaxObj("POST","php_parsers/friend_system.php");o.onreadystatechange=function(){1==ajaxReturn(o)&&("friend_request_sent"==o.responseText?_(t).innerHTML="OK Friend Request Sent":"unfriend_ok"==o.responseText?_(t).innerHTML="<button onclick=\"friendToggle('friend','<?php echo $u; ?>','friendBtn')\">Request As Friend</button>":(alert(o.responseText),_(t).innerHTML="Try again later"))},o.send("type="+e+"&user="+n)}function blockToggle(e,n,t){(t=document.getElementById(t)).innerHTML='<img src="images/rolling.gif" width="30" height="30">';var o=ajaxObj("POST","php_parsers/block_system.php");o.onreadystatechange=function(){1==ajaxReturn(o)&&("blocked_ok"==o.responseText?t.innerHTML="<button onclick=\"blockToggle('unblock','<?php echo $u; ?>','blockBtn')\">Unblock User</button>":"unblocked_ok"==o.responseText?t.innerHTML="<button onclick=\"blockToggle('block','<?php echo $u; ?>','blockBtn')\">Block User</button>":(alert(o.responseText),t.innerHTML="Try again later!"))},o.send("type="+e+"&blockee="+n)}

		function openUserEdit(){
			if(_("editprofileform").style.display === "none"){
				_("editprofileform").style.display = "block";
				_("userEditBtn").style.backgroundColor = "#ad5038";
			}else{
				_("editprofileform").style.display = "none";
				_("userEditBtn").style.backgroundColor = "#763626";
			}
		}

		function statusMax(field, maxlimit) {
			if (field.value.length > maxlimit){
				alert(maxlimit+" maximum character limit reached");
				field.value = field.value.substring(0, maxlimit);
			}
		}

		function emptyElement(x){
			_(x).innerHTML = "";
		}

		function editChanges(){
			var status = _("status");
			var job = _("job").value;

			var elemen = _("elemen").value;
			var high = _("high").value;
			var uni = _("uni").value;
			var politics = _("politics").value;
			var religion = _("religion").value;
			var language = _("language").value;
			var nd_day = _("nd_day").value;
			var nd_month = _("nd_month").value;
			var interest = _("interest").value;
			var notemail = _("notemail").value;
			var website = _("website").value;

			var ta = _("ta").value;
			var pro = _("profession").value;
			var city = _("city").value;
			var state = _("state").value;
			var mobile = _("mobile").value;
			var hometown = _("hometown").value;
			var fmovie = _("movies").value;
			var fmusic = _("music").value;
			var pstatus = _("pstatus").value;

			if(job == "" == "" && ta == "" && pro == "" && city == "" && state == "" && mobile == "" && hometown == "" && fmovie == "" && pstatus == "" && fmusic == "" && elemen == "" && high == "" && uni == "" && politics == "" && religion == "" && language == "" && nd_day == "" && nd_month == "" && interest == "" && notemail == "" && website == ""){
				status.innerHTML = "Please fill in at least 1 field";
			}else{
				_("editbtn").style.display = "none";
				status.innerHTML = '<img src="images/rolling.gif" width="30" height="30">';
				var ajax = ajaxObj("POST", "php_parsers/edit_parser.php");
				ajax.onreadystatechange = function(){
					if(ajaxReturn(ajax) == true){
						if(ajax.responseText != "edit_success"){
							status.innerHTML = ajax.responseText;
							_("editbtn").style.display = "block";
						}else{
							_("editprofileform").innerHTML = "<p class='success_green'>Your changes has been saved successfully</p>";
						}
					}
				}
				ajax.send("job="+job+"&ta="+ta+"&pro="+pro+"&city="+city+"&state="+state+"&mobile="+mobile+"&hometown="+hometown+"&fmovie="+fmovie+"&fmusic="+fmusic+"&pstatus="+pstatus+"&elemen="+elemen+"&high="+high+"&uni="+uni+"&politics="+politics+"&religion="+religion+"&language="+language+"&nd_day="+nd_day+"&nd_month="+nd_month+"&interest="+interest+"&notemail="+notemail+"&website="+website);
			}
		}

		function writeArticle(){
			var ueb = _("userEditBtn");
			var as = _("article_show");
			var art = _("writearticle");
			var ab = _("art_btn");
			var hi = _("hide_it");
			if(as.style.display == 'block'){
				ueb.style.display = 'none';
				hi.style.display = 'none';
				as.style.display = 'none';
				art.style.display = 'block';
				ab.style.display = "block";
				ab.style.opacity = "0.9";
			}else{
				ueb.style.display = 'block';
				as.style.display = 'block';
				art.style.display = 'none';
				hi.style.display = 'block';
				ab.style.opacity = "1.0";
			}
		}

		function saveArticle(){
			var theForm = _("writearticle");
			var title = _("title").value;
			var status = _("status_art");
			var tags = _("keywords").value;
			theForm.elements["myTextArea"].value = window.frames['richTextField'].document.body.innerHTML;
			var area = theForm.elements["myTextArea"].value;
			if(title == "" || area == "" || tags == ""){
				status.innerHTML = '<p class="error_red">Please fill in all fields!</p>';
			}else{
				_("article_btn").style.display = 'none';
				status.innerHTML = '<img src="images/rolling.gif" width="30" height="30">';
				var ajax = ajaxObj("POST","php_parsers/article_parser.php");
				ajax.onreadystatechange = function(){
					if(ajaxReturn(ajax) == true){
						if(ajax.responseText != "article_success"){
							status.innerHTML = ajax.responseText;
							_("article_btn").style.display = "block";
						}else{
							_("writearticle").innerHTML = "<p class='success_green'>Your article has been saved successfully</p>";
						}
					}
				}
			}
			ajax.send("title="+title+"&area="+area+"&tags="+tags);
		}

		function openHelp(){
			var o = _("help_hide_div");
			if(o.style.display == 'none'){
				o.style.display = 'block';
			}else{
				o.style.display = 'none';
			}
		}

		function followToggle(type,user,elem){
			_(elem).innerHTML='<img src="images/rolling.gif" width="30" height="30">';
			var ajax = ajaxObj("POST","php_parsers/follow_system.php");
			ajax.onreadystatechange = function(){
				if(ajaxReturn(ajax) == true){
					if(ajax.responseText == "follow_success"){
						_(elem).innerHTML = '<button class="brown_color_btn" onclick="followToggle(\'unfollow\',\'<?php echo $u; ?>\',\'followBtn\')">Unfollow</button>';
					}else if(ajax.responseText == "unfollow_success"){
						_(elem).innerHTML = '<button class="brown_color_btn" onclick="followToggle(\'follow\',\'<?php echo $u; ?>\',\'followBtn\')">Follow</button>';
					}else{
						alert(ajax.responseText);
						_(elem).innerHTML = 'Try again later';
					}
				}
			}
			ajax.send("type="+type+"&user="+user);
		}

		function openEdu(){
			var x = _("edu");
			var y = _("plus_minus");
			if(x.style.display == "none"){
				x.style.display = 'block';
				y.innerHTML = '-';
			}else{
				x.style.display = 'none';
				y.innerHTML = '+';
			}
		}

		function openPro(){
			var x = _("pro_");
			var y = _("plus_minus_2");
			if(x.style.display == "none"){
				x.style.display = 'block';
				y.innerHTML = '-';
			}else{
				x.style.display = 'none';
				y.innerHTML = '+';
			}
		}

		function openCity(){
			var x = _("city_");
			var y = _("plus_minus_3");
			if(x.style.display == "none"){
				x.style.display = 'block';
				y.innerHTML = '-';
			}else{
				x.style.display = 'none';
				y.innerHTML = '+';
			}
		}

		function openMe(){
			var x = _("me");
			var y = _("plus_minus_4");
			if(x.style.display == "none"){
				x.style.display = 'block';
				y.innerHTML = '-';
			}else{
				x.style.display = 'none';
				y.innerHTML = '+';
			}
		}

		function openCon(){
			var x = _("con");
			var y = _("plus_minus_5");
			if(x.style.display == "none"){
				x.style.display = 'block';
				y.innerHTML = '-';
			}else{
				x.style.display = 'none';
				y.innerHTML = '+';
			}
		}

		var showingSourceCode = false;
		var isInEditMode = false;

		function enableEditMode(){
			richTextField.document.designMode = 'On';
		}

		function execCmd(command){
			richTextField.document.execCommand(command, false, null);
		}

		function execCmdWithArg(command, arg){
			richTextField.document.execCommand(command, false, arg);
		}

		function toggleSource(){
			if(showingSourceCode){
				// Show source code
				richTextField.document.getElementsByTagName('body')[0].innerHTML = richTextField.document.getElementsByTagName('body')[0].textContent;
				showingSourceCode = false;
			}else{
				richTextField.document.getElementsByTagName('body')[0].textContent = richTextField.document.getElementsByTagName('body')[0].innerHTML;
				showingSourceCode = true;
			}
		}

		function toggleEdit(){
			if(isInEditMode){
				richTextField.document.designMode = 'Off';
				isInEditMode = false;
			}else{
				richTextField.document.designMode = 'On';
				isInEditMode = true;
			}
		}

		function uploadBiBg(imgtype){
			var status = _("statusbig");
			var imgtype = imgtype;
			var ajax = ajaxObj("POST", "php_parsers/photo_system.php");
			ajax.onreadystatechange = function(){
				if(ajaxReturn(ajax) == true){
					if(ajax.responseText == "bibg_success"){
						status.innerHTML = '<p class="success_green">You have successfully changed your background to '+imgtype+'</p>';
						location.reload();
						window.scrollTo(0,0);

					}else{
						alert(ajax.responseText);
						status.innerHTML = '<p class="error_red">Try again later</p>';
					}
				}
			}
			ajax.send("imgtype="+imgtype);
		}

		function showBiBg(){
			var sb = _("statusbig");
			var inner = _("inner");
			if(sb.style.display == "none"){
				sb.style.display = "block";
				inner.innerHTML = "Hide";
			}else{
				sb.style.display = "none";
				inner.innerHTML = "Show";
			}
		}
	</script>
</head>
<body onload="enableEditMode()">
	<?php include_once("template_pageTop.php"); ?>
	<div id="pageMiddle_2">
		<div class="row">
		<div id="name_holder"><?php echo $u; ?></div>
		<div style="width: 100%; height: 350px; background-image: url('<?php echo $attribute; ?>'); background-size: 100%; background-repeat: no-repeat;">
	  <div id="profile_pic_box">
		  <?php echo $profile_pic_btn; ?>
		  <?php echo $avatar_form; ?>
		  <?php echo $profile_pic; ?>
	  </div>

	   <div id="photo_showcase" onclick="window.location = 'photos.php?u=<?php echo $u; ?>';" title="View <?php echo $u; ?>&#39;s photo galleries">
    		<?php echo $coverpic; ?>
       </div>
       </div>

	  <p>Is the viewer the page owner logged in and verified? <b><?php echo $isOwner; ?></b></p>
	  <div id="hide_it">
	  <div id="min_height">
	  	<table>
	  	 <tr>
	  	  <th><p style="font-size: 20px;">General Informations</p></th>
	  	 </tr>
	  	 <tr>
		  <td><b>Gender: </b><?php echo $sex; ?></td>
		 </tr>
		 <tr>
		  <td><b>Country: </b><?php echo $country; ?><?php require_once 'template_country_flags.php'; ?></td>
		 </tr>
		 <tr>
		  <?php if($state != ""){ ?>
	      	<td><b>State/Province: </b><?php echo $state; ?></td>
		  <?php } ?>
		 </tr>
		 <tr>
		  <?php if($city != ""){ ?>
	      	<td><b>City/Town: </b><?php echo $city; ?></td>
		  <?php } ?>
		 </tr>
		 <tr>
		  <td><b>User Security: </b> <?php echo $userlevel; ?></td>
		 </tr>
		 <tr>
	      <td><b>Member Since: </b> <?php echo $joindate; ?></td>
	     </tr>
	     <tr>
	      <td><b>Last Log in: </b> <?php echo $lastsession; ?></td>
	     </tr>
	     <tr>
	      <td><b>Birthday: </b> <?php echo $birthday; ?></td>
	     </tr>
	     <tr>
	      <?php if($nd_day != "" && $nd_month != ""){ ?>
	      	<td><b>Name day: </b><?php echo $nd_day.", ".$nd_month; ?></td>
		  <?php } ?>
		 </tr>
		 <tr>
	      <td><b>Age: </b><?php echo $age; ?></td>
	     </tr>
      </table>

     	<table id="personal_table">
          <?php if($hometown != "" || $fmovie != "" || $fmusic != "" || $politics != "" || $religion != "" || $interest != "" || $language != ""){ ?>
          <tr>
	     	 <th><p style="font-size: 20px;">Personal Informations</p></th>
	      </tr>
		  <tr>
			  <?php if($hometown != ""){ ?>
		      	<td><b>Hometown: </b><?php echo $hometown; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($fmovie != ""){ ?>
		      	<td><b>Favourite Movies: </b><?php echo $fmovie; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($fmusic != ""){ ?>
		      	<td><b>Favourite Songs/Music: </b><?php echo $fmusic; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($pstatus != ""){ ?>
		      	<td><b>Partnership Status: </b><?php echo $pstatus; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($politics != ""){ ?>
		      	<td><b>Political Views: </b><?php echo $politics; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($religion != ""){ ?>
		      	<td><b>Religious views: </b><?php echo $religion; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($interest != ""){ ?>
		      	<td><b>I'm interested in: </b><?php echo $interest; ?></td>
			  <?php } ?>
		  </tr>
		  <tr>
			  <?php if($language != ""){ ?>
		      	<td><b>Language: </b><?php echo $language; ?></td>
			  <?php } ?>
			  <?php } ?>
		  </tr>
		  </table>
		  <table id="contact_table">
		  	  <tr>
				  <?php if($mobile != "" || $notemail != "" || $website != ""){ ?>
				  	<th><p style="font-size: 20px;">Contact Informations</p></th>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($mobile != ""){ ?>
			      	<td><b>Mobile: </b><?php echo $mobile; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($notemail != ""){ ?>
			      	<td><b>Email: </b><?php echo $notemail; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($website != ""){ ?>
			      	<td><b>Website: </b><?php echo $website; ?></td>
				  <?php } ?>
			  <tr>
		  </table>
		  <table  id="edu_table">
		  	  <tr>
				  <?php if($elemen != "" || $high != "" || $uni != "" || $profession != "" || $job != ""){ ?>
				  	<th><p style="font-size: 20px">Education/Profession</p></th>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($elemen != ""){ ?>
			      	<td><b>Elementary School: </b><?php echo $elemen; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($high != ""){ ?>
			      	<td><b>High School: </b><?php echo $high; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($uni != ""){ ?>
			      	<td><b>University: </b><?php echo $uni; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($profession != ""){ ?>
			      	<td><b>Profession: </b><?php echo $works; ?></td>
				  <?php } ?>
			  </tr>
			  <tr>
				  <?php if($job != ""){ ?>
			      	<td><b>Job: </b><?php echo $job; ?></td>
				  <?php } ?>
			  </tr>
		  </table>
		  </div>
		</div>
		<?php if($about != ""){ ?>
		<div id="aboutheader"><?php if($isOwner == "Yes"){echo "<p style='font-size: 20px;'>About Me</p>";}else{echo "<p style='font-size: 20px;'>About ".$u."</p>";} ?></div>
		<div id="newabout">
			<b>About me: </b><?php echo $about; ?>
		</div>
		<br />
		<?php } ?>
	  <!--<p><b>This user lives </b><?php echo $totalDist; ?> from you</p>-->
	  <?php if($log_username == $u && $user_ok == true){ ?>
	  	<fieldset>
	  	<legend>Change profile settings</legend>
	  	<button onclick="openUserEdit()" id="userEditBtn" style="margin-top: 10px;">Edit Profile</button>
	  	<p>Give some informations about yourself. If you fill more fields your friends and family members will find you a lot more easily.<br />You can add your education, job, your favourite music, film and a lot of other things, too.<br /><br />Why is it good to give information about yourself?<ol><li>You can find your friends, family and acquaintances and they will also find you a lot more easier</li><li>You will be able to make new friends who lives close to you, went to the same school as you or your jobs are the same</li></ol></p>
	  <?php } ?>
	  <form name="editprofileform" id="editprofileform" onsubmit="return false;">

	  	<!-- EDUCATION -->
	  	<a href="#" onclick="return false;" onmousedown="openEdu()" class="openEdit" id="edu_link"><b id="plus_minus">+</b> Education</a><br /><br />
	  	<div id="edu">
		  	<div class="editformtitle">Elementary School:</div>
		  	<input id="elemen" type="text" placeholder="e.g Greenwood Elementary School" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">Secondary School/High School:</div>
		  	<input id="high" type="text" placeholder="e.g. Thomas Jefferson High School" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">University:</div>
		  	<input id="uni" type="text" placeholder="e.g. University of Florida" onfocus="emptyElement('status')" maxlength="150"><br /><br />
	  	</div>

		<!-- PROFESSION -->
		<a href="#" onclick="return false;" onmousedown="openPro()" class="openEdit" id="pro_link"><b id="plus_minus_2">+</b> Profession</a><br /><br />
		<div id="pro_">
			<div class="editformtitle">Job:</div>
			<input id="job" type="text" placeholder="e.g. Engineer at IBM" onfocus="emptyElement('status')" maxlength="150">
			<div id="editformradio">What do you do?</div>
		  	<select id="profession" onfocus="emptyElement('status')">
		  		<option value=""></option>
			  	<option value="s">Student</option>
			  	<option value="w">Working</option>
			  	<option value="r">Retired</option>
			  	<option value="u">Unemployed</option>
			  	<option value="o">Other</option>
		  	</select><br /><br />
	  	</div>

	  	<!-- CITIES -->
	  	<a href="#" onclick="return false;" onmousedown="openCity()" class="openEdit" id="city_link"><b id="plus_minus_3">+</b> Cities</a><br /><br />
	  	<div id="city_">
		  	<div class="editformtitle">State/Province:</div>
		  	<input id="state" type="text" placeholder="e.g. California" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">City/Town:</div>
		  	<input id="city" type="text" placeholder="e.g. Los Angeles" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">Hometown:</div>
		  	<input id="hometown" type="text" placeholder="e.g. New York City" onfocus="emptyElement('status')" maxlength="150"><br /><br />
	  	</div>

	  	<!-- ABOUT ME -->
	  	<a href="#" onclick="return false;" onmousedown="openMe()" id="me_link"><b id="plus_minus_4">+</b> About me</a><br /><br />
	  	<div id="me">
		  	<div class="editformtitle">About me:</div>
		  	<textarea id="ta" onkeyup="statusMax(this,1000)" placeholder="e.g. I like watching TV, swimming ..." onfocus="emptyElement('status')"></textarea>
		  	<div class="editformtitle">Favourite Movies (you can write more):</div>
		  	<textarea id="movies" class="movie_music" placeholder="e.g. Pirates of the Caribbean, Titanic ..." onkeyup="statusMax(this,400)"></textarea>
		  	<div class="editformtitle">Favourite Music (you can write more):</div>
		  	<textarea id="music" class="movie_music" placeholder="e.g. Pop, Rock, Country, Heavy Metal ..." onkeyup="statusMax(this,400)"></textarea>
		  	<div class="editformtitle">Partnership status:</div>
		  	<input id="pstatus" type="text" placeholder="e.g. Married, Looking for partner ...">
		  	<div class="editformtitle">Political views:</div>
		  	<input id="politics" type="text" placeholder="e.g. Very Liberal ...">
		  	<div class="editformtitle">Religious views:</div>
		  	<input id="religion" type="text" placeholder="e.g. Christian ...">
		  	<div class="editformtitle">Language:</div>
		  	<input id="language" type="text" placeholder="e.g. English, Russian ...">
		  	<div class="editformtitle">Name day:</div>
		  	<select id="nd_day" onfocus="emptyElement('status')">
		  		<?php require_once 'template_day_list.php'; ?>
		  	</select>
		  	<select id="nd_month" onfocus="emptyElement('status')">
			  		<option value=""></option>
			  		<option value="January">January</option>
			  		<option value="February">February</option>
			  		<option value="March">March</option>
			  		<option value="April">April</option>
			  		<option value="May">May</option>
			  		<option value="July">July</option>
			  		<option value="June">June</option>
			  		<option value="August">August</option>
			  		<option value="September">September</option>
			  		<option value="October">October</option>
			  		<option value="November">November</option>
			  		<option value="December">December</option>
		  	</select>

		  	<div class="editformtitle">Who you're interseted in?</div>
		  	<input id="interest" type="text" placeholder="e.g. Woman ..."><br /><br />
	  	</div>

	  	<!-- CONTACT -->
	  	<a href="#" onclick="return false;" onmousedown="openCon()" id="con_link"><b id="plus_minus_5">+</b> Contact</a><br /><br />
	  	<div id="con">
		  	<div class="editformtitle">Mobile:</div>
		  	<input id="mobile" type="text" placeholder="248-230-5096" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">Email (not your log in email):</div>
		  	<input id="notemail" type="email" placeholder="example@example.com" onfocus="emptyElement('status')" maxlength="150">
		  	<div class="editformtitle">Website:</div>
		  	<input id="website" type="text" placeholder="www.example.com" onfocus="emptyElement('status')" maxlength="150"><br /><br />
		</div>
	  	<button id="editbtn" onclick="editChanges()">Submit</button>
	  	<div id="status"></div>
	  </form>
	  </fieldset>
			<form id="writearticle" name="writearticle" onsubmit="return false;">
			<p style="text-align: center; font-size: 22px;">Create Article</p>
			<textarea name="title" id="title" type="text" maxlength="100" placeholder="Article Title"></textarea>
			<div class="toolbar">
			  <a onclick="execCmd('bold')"><i class='fa fa-bold'></i></a>
			  <a onclick="execCmd('italic')"><i class='fa fa-italic'></i></a>
			  <a onclick="execCmd('underline')"><i class='fa fa-underline'></i></a>
			  <a onclick="execCmd('strikeThrough')"><i class='fa fa-strikethrough'></i></a>
			  <a onclick="execCmd('justifyLeft')"><i class='fa fa-align-left'></i></a>
			  <a onclick="execCmd('justifyCenter')"><i class='fa fa-align-center'></i></a>
			  <a onclick="execCmd('justifyRight')"><i class='fa fa-align-right'></i></a>
			  <a onclick="execCmd('justifyFull')"><i class='fa fa-align-justify'></i></a>
			  <a onclick="execCmd('cut')"><i class='fa fa-cut'></i></a>
			  <a onclick="execCmd('copy')"><i class='fa fa-copy'></i></a>
			  <a onclick="execCmd('indent')"><i class='fa fa-indent'></i></a>
			  <a onclick="execCmd('outdent')"><i class='fa fa-dedent'></i></a>
			  <a onclick="execCmd('subscript')"><i class='fa fa-subscript'></i></a>
			  <a onclick="execCmd('superscript')"><i class='fa fa-superscript'></i></a>
			  <a onclick="execCmd('undo')"><i class='fa fa-undo'></i></a>
			  <a onclick="execCmd('redo')"><i class='fa fa-repeat'></i></a>
			  <a onclick="execCmd('insertUnorderedList')"><i class='fa fa-list-ul'></i></a>
			  <a onclick="execCmd('insertOrderedList')"><i class='fa fa-list-ol'></i></a>
			  <a onclick="execCmd('insertParagraph')"><i class='fa fa-paragraph'></i></a>
			  <select onchange="execCmdWithArg('formatBlock', this.value)" class="font_all">
			  	<option value="H1">H1</option>
			  	<option value="H2">H2</option>
			  	<option value="H3">H3</option>
			  	<option value="H4">H4</option>
			  	<option value="H5">H5</option>
			  	<option value="H6">H6</option>
			  </select>
			  <a onclick="execCmd('insertHorizontalRule')">HR</a>
			  <a onclick="execCmd('createLink', prompt('Enter URL', 'https://'))"><i class='fa fa-link'></i></a>
			  <a onclick="execCmd('unlink')"><i class='fa fa-unlink'></i></a>
			  <a onclick="toggleSource()"><i class='fa fa-code'></i></a>
			  <a onclick="toggleEdit()"><i class="fa fa-pencil-square-o"></i></a>
			  <select onchange="execCmdWithArg('fontName', this.value)" id="font_name">
			  	<option value="Arial">Arial</option>
			  	<option value="Comic Sans MS">Comic Sans MS</option>
			  	<option value="Courier">Courier</option>
			  	<option value="Georgia">Georgia</option>
			  	<option value="Helvetica">Helvetica</option>
			  	<option value="Thaoma">Thaoma</option>
			  	<option value="Palatino Linotype">Palatino Linotype</option>
			  	<option value="Arial Black">Arial Black</option>
			  	<option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
			  	<option value="Trebuchet MS">Times New Roman</option>
			  	<option value="Lucida Console">Times New Roman</option>
			  	<option value="Courier New">Times New Roman</option>
			  </select>
			  <select onchange="execCmdWithArg('formatSize', this.value)" class="font_all">
			  	<option value="1">1</option>
			  	<option value="2">2</option>
			  	<option value="3">3</option>
			  	<option value="4">4</option>
			  	<option value="5">5</option>
			  	<option value="6">6</option>
			  	<option value="7">7</option>
			  </select>
			  Fore Color: <input type="color" onchange="execCmdWithArg('foreColor', this.value)"/>
			  Background Color: <input type="color" onchange="execCmdWithArg('hiliteColor', this.value)"/>
			  <a onclick="execCmdWithArg('insertImage', prompt('Enter image location: '))"><i class='fa fa-file-image-o'></i></a>
			  <a onclick="execCmd('selectAll')"><i class="fa fa-reply-all"></i></a>
			</div>
			<!-- Hide(but keep) normal textarea and place in the iFrame replacement for it -->
			<textarea style="display:none;" name="myTextArea" id="myTextArea" cols="100" rows="14"></textarea>
			<iframe name="richTextField" id="richTextField"></iframe>
			<!-- End replacing normal textarea -->
			<p>Tags:</p>
			<input type="text" id="keywords" maxlenght="150" placeholder="Give tags for your article e.g. freetime, food, sport, holdiday etc. separated by a comma"><br />
			<a onclick="openHelp()" id="info_article"><i class="fa fa-info-circle"></i></a>
			<div id="help_hide_div">
				<p style="font-size: 18px;">Help for using font size</p>
				<font size="1">This text is font 1</font><br />
				<font size="2">This text is font 2</font><br />
				<font size="3">This text is font 3</font><br />
				<font size="4">This text is font 4</font><br />
				<font size="5">This text is font 5</font><br />
				<font size="6">This text is font 6</font><br />
				<font size="7">This text is font 7</font>
			</div>
			<br /><br /><button id="article_btn" onclick="saveArticle()">Create Article</button><br /><br /><br />
			<span id="status_art"></span>
			</form>
			<br />
	  	<div id="article_show">
	  		<fieldset>
	  		<legend>Friends &amp; Blocks</legend>
	  		<p>Add <?php echo $u; ?> as a friend/unfriend: <span id="friendBtn"><?php echo $friend_button; ?></span>
	  		<?php 
	  			if($isOwner == "Yes" && $friend_count == 1){ 
	  				echo '<b> You have '.$friend_count.' friend</b>'; 
	  			}else if($isOwner == "Yes" && $friend_count > 1){
	  				echo '<b> You have '.$friend_count.' friends</b>';
	  			}else if($isOwner == "No" && $friend_count == 1){
	  				echo '<b> '.$u.' has '.$friend_count.' friend</b>';
	  			}else if($isOwner == "No" && $friend_count > 1){
	  				echo '<b> '.$u.' has '.$friend_count.' friends</b>';
	  			}
	  		?> 
	  		<?php echo $friends_view_all_link; ?></p>
	  		<p>Block/unblock <?php echo $u; ?>: <span id="blockBtn"><?php echo $block_button; ?></span></p>
	  		<?php if($u == $log_username && $user_ok == true){ ?>
	  			<p><a href="more_friends.php">Find More Friends</a></p>
	  		<?php } ?>
	  		<h3><?php 
	  	  	if($isOwner == "Yes"){
	  	  		echo '<p>My friends ('.$friend_count.')</p>';
	  	  	}else{
	  	  		echo '<p>'.$u.'&#39s friends ('.$friend_count.')</p>';
	  	  	}
	  	  ?></h3>
	  	  <p><?php echo $friendsHTML; ?></p>
	  	  </fieldset>
	  	  <br />
	  	  <fieldset>
	  	  <legend><?php if($isOwner == "Yes"){echo "My Photos";}else{echo $u."&#39;s Photos";} ?></legend>
	  	  <p>Photo Gallery: <button onclick="window.location = 'photos.php?u=<?php echo $u; ?>'">View Photos</button></p>
	  	  <?php 
	  	  	if($isOwner == "Yes"){
	  	  		echo '<p>Find all your photos in one place, at the photo galley. You can also upload new ones share your existing ones or view your friends & family&#39;s photos. <br /><br />What can I do with my photos?<ol><li>You can choose from over 10 galleries</li><li>You can give a description about your image with a few words which is not necessary, but recommended</li><li>You can leave comments below your or your friends photos</li><li>You can upload png, jpg, jpeg or even gif typed images</li></ol></p>';
	  	  	}else{
	  	  		echo '<p>Visit '.$u.'&#39s photo gallery</p>';
	  	  	}
	  	  ?>
	  	  <p style="font-size: 18px;"><?php if($isOwner == "Yes"){ echo '<p>My Photos</p>';}else{ echo '<p>'.$u.'&#39s Photos</p>';} ?></p>
	  	  <?php echo $echo_photos; ?>
	  	  </fieldset>
	  	  <div class="clear"></div>
	  	  <?php if($is_birthday == "yes" && $u == $log_username && $user_ok == true){ 
	  	  		echo '<img src="images/bd.gif" id="hb_img">';
	  	  	}
	  	  ?>
	  	  <br />
	  	  <fieldset>
	  	  <legend><?php if($isOwner == "Yes"){echo "My Articles";}else{echo $u."&#39;s Articles";} ?></legend>
	  	  <div id="article_box">
	  	  	<br />
		  	  <?php echo $article; ?>
		  	  <?php if($isOwner == "Yes"){
		  	  	echo "<p>Have you ever wanted to share your grandmother's best browne recipe or tell your opinion about a certain topic? Now, you can easily do it by writing your own article that you can share with your friends or you can even read your friends articles.<br /><br />What can I do with my Articles?<ol><li>You can name your article but try to be specific</li><li>You can edit your article like in text editors (<b>Bold text</b>, <i>Italic text</i>, <u>Underlined text</u>, import images, change font style etc.)</li><li>You can comment to articles, tell your opinion or share your ideas</li><li>You can like your friends articles or add as a favourite</li><li>You can also edit your existing articles</li></ol></p>";
		  	  	}
		  	  ?>
	  	  	  <?php if($isOwner == "Yes"){echo "Existing Articles:";}else{echo $u."&#39;s Existing Articles:";} ?>
	  	  </div>
	  	  <br />
	  	  <?php echo $echo_articles; ?>
	  	  </fieldset>
	  	  <br />
	  	  <fieldset>
	  	  	<legend>Videos &amp; Audios</legend>
	  	  	<?php if($isOwner == "Yes"){echo "<p>Upload new videos, auidos and MP3s and share with your friends.<br /><br />What can I do with my videos? <ol><li>You can give a short name where you can specify the topic of the video</li><li>You can also give a description where you can add more information about the video, put links etc.</li><li>You can set a poster for your video that will appear as a cover image</li></ol></p><hr>";}else{echo $u."&#39;s videos<br />";
	  	  	} ?>
	  	  	<?php echo $videos; ?>
	  	  	<?php if($isOwner == "No"){echo "<br /><a href='videos.php?u=".$u."'>View ".$u."&#39s Videos</a>";} ?>
	  	  </fieldset>

	  	  <br />
	  	  <fieldset>
	  	  	<legend>Followers &amp; Followings</legend>
	  	  <p><?php if($isOwner == "Yes"){
	  	  		echo "<p>My followers</p>";
	  	  		echo $meFollow;
	  	  	}else{
	  	  		echo $u."'s followers";
	  	  	} ?>
	  	  </p>
	  	  <div id="follow_count">
		  	<?php
		  		if($isOwner == "Yes" && $follower_count == 0){
		  			echo '<b> You have '.$follower_count.' followers</b>';
		  		}else if($isOwner == "Yes" && $follower_count > 1){
		  			echo '<b> You have '.$follower_count.' followers</b>';
		  		}else if($isOwner == "No" && $follower_count == 0){
		  			echo '<b> '.$u.' has '.$follower_count.' follower</b>';
		  		}else if($isOwner == "No" && $follower_count > 1){
		  			echo '<b> '.$u.' has '.$follower_count.' followers</b>';
		  		}else if($isOwner == "No" && $follower_count == 1){
		  			echo '<b> '.$u.' has '.$follower_count.' follower</b>';
		  		}else if($isOwner == "Yes" && $follower_count == 1){
		  			echo '<b> You have '.$follower_count.' follower</b>';
		  		}
		  	?><br /><br />
		  	<?php if($log_username != $u){ echo $isFollowOrNot; }?>
		  	<?php if($isOwner == "Yes"){ ?>
		  		<p>My Followers</p>
		  	<?php }else{ ?>
		  		<p><?php echo $u; ?>&#34;s followers</p>
		  	<?php } ?>
		  	<?php echo $following_div; ?>
		  	<?php if($isOwner == "Yes"){ ?>
		  		<p>I follow these people</p>
		  	<?php }else{ ?>
		  		<p><?php echo $u; ?> follows these people</p>
		  	<?php } ?>
		  	<?php echo $other_div; ?>
		  </div>
		  <?php if($isOwner == "No" && $log_username != ""){ ?>
		    <br /><span id="followBtn"><?php echo $follow_button; ?></span>
		  <?php } ?>
		  </fieldset>
		  <br />
		  <?php if($user_ok == true && $isOwner == "Yes"){ ?>
		  <fieldset>
		  	<legend>Background</legend>
		  <p>Set an image as background from your computer or choose one from the built-in backgrounds</p>
		  <p><b>Note: </b>the optimal image size for the background is 1200 x 350 pixels otherwise, it will be automaticly resized</p>
		  <?php echo $background_form; ?>
		  <b>Built-in backgrounds: </b><p onclick="showBiBg()" id="outer"><b id="inner" style="font-weight: normal;">Show</b> >>></p>
		  <div id="statusbig" style="display: none;">
			  <div class="bibg">
			  	<img src="images/universebi.jpg" width="100" height="100" alt="Universe" title="Universe Background" onclick="uploadBiBg('universe')">
			  </div>

			  <div class="bibg">
			  	<img src="images/flowersbi.jpg" width="100" height="100" alt="Flowers" title="Flowers Background" onclick="uploadBiBg('flowers')">
			  </div>

			  <div class="bibg">
			  	<img src="images/forestbi.jpg" width="100" height="100" alt="Forest" title="Forest Background" onclick="uploadBiBg('forest')">
			  </div>

			  <div class="bibg">
			  	<img src="images/bubblesbi.jpg" width="100" height="100" alt="Bubbles" title="Bubbles Background" onclick="uploadBiBg('bubbles')">
			  </div>

			  <div class="bibg">
			  	<img src="images/mountainsbi.jpg" width="100" height="100" alt="Mountains" title="Mountains Background" onclick="uploadBiBg('mountains')">
			  </div>

			  <div class="bibg">
			  	<img src="images/wavesbi.jpg" width="100" height="100" alt="Waves" title="Waves Background" onclick="uploadBiBg('waves')">
			  </div>

			  <div class="bibg">
			  	<img src="images/stonesbi.jpg" width="100" height="100" alt="Stones" title="Stones Background" onclick="uploadBiBg('stones')">
			  </div>

			  <div class="bibg">
			  	<img src="images/simplebi.jpg" width="100" height="100" alt="Blue" title="Blue Background" onclick="uploadBiBg('simple')">
			  </div>
		  </div>
		  <?php } ?>
		  </fieldset>
		  <div class="clear"></div>
	  	  <?php if($u == $log_username && $user_ok == true){ ?>
	  	  <div id="groupModule"></div>
	  	  <?php } ?>
	  	  <?php require_once 'template_pm.php'; ?>
	  	  <p><?php echo '<strong><p style="font-size: 17px;" id="posts">'.$status_count.' posts recorded</p></strong>'; ?></p>
	  	  <?php require_once 'template_status.php'; ?>
	   </div>
	   </div>
	</div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
</body>
</html>