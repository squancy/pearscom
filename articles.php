<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'safe_encrypt.php';
	require_once 'a_array.php';
	require_once 'headers.php';
	require_once 'ccov.php';

	// Initialize any variables that the page might echo
	$u = "";
	$p = "";
	$one = "1";
	$a = "a";
	$b = "b";
	$c = "c";
	$x = "";
	$pure_p = "";
	$profile_pic = "";
	$profile_pic_btn = "";
	$avatar_form = "";

	if(isset($_GET["u"])){
		$u = mysqli_real_escape_string($conn, $_GET["u"]);
	} else {
	    header('Location: /usernotexist');
	    exit();
	}

	if(isset($_GET["p"])){
		$x = $_GET['p'];
		$pure_p = $x;
		$p = base64url_decode($x,$hshkey);
	}else{
		header('Location: /index');
		exit();
	}

	// Get article id
	$id = "";
	$sql = "SELECT id FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$p);
	$stmt->execute();
	$stmt->bind_result($id);
	$stmt->fetch();
	$stmt->close();

	$_SESSION["id"] = $id;

	// Select the member from the users table
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	// Now make sure the user exists in the table
	if($numrows < 1){
		header('location: /usernotexist');
		exit();
	}

	$stmt->close();
	// Make sure this article exists in the database
	$sql = "SELECT * FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$p);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows_2 = $stmt->num_rows;
	if($numrows_2 < 1){
		header('location: /articlenotexist');
		exit();
	}
	$stmt->close();
	// Check to see if the viewer is the account owner
	$isOwner = "No";
	if($u == $log_username && $user_ok == true){
		$isOwner = "Yes";
	}
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	// Fetch the user row from the sql above
	while($row = $result->fetch_assoc()){
		$avatar = $row["avatar"];
	}
	$stmt->close();
	
	$profile_pic = '/user/'.$u.'/'.$avatar;

	if($avatar == NULL){
		$profile_pic = '/images/avdef.png';
	}
	
	$isBlock = false;
	if($user_ok == true){
		$block_check = "SELECT id FROM blockedusers WHERE blockee=? AND blocker=?";
		$stmt = $conn->prepare($block_check);
		$stmt->bind_param("ss",$log_username,$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
	if($numrows > 0){
	    $isBlock = true;
    }
    $stmt->close();
}
?>
<?php
	// SET UP REQUIRED VARS AND DATAS FOR THE EDIT FUNCTION
	$e_img1 = "";
	$e_img2 = "";
	$e_img3 = "";
	$e_img4 = "";
	$e_img5 = "";
	$e_img_count = 0;
	$sql = "SELECT img1, img2, img3, img4, img5 FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$p);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$e_img1 = $row["img1"];
		$e_img2 = $row["img2"];
		$e_img3 = $row["img3"];
		$e_img4 = $row["img4"];
		$e_img5 = $row["img5"];
	}

	if($e_img1 == ""){
		$e_img_count++;
	}
	if($e_img2 == ""){
		$e_img_count++;
	}
	if($e_img3 == ""){
		$e_img_count++;
	}
	if($e_img4 == ""){
		$e_img_count++;
	}
	if($e_img5 == ""){
		$e_img_count++;
	}
?>
<?php
	// Echo article
	$written_by_ma = "";
	$post_time_ma = "";
	$content_ma = "";
	$title_main_ma = "";
	$tags_ma = "";
	$tgsnew_ma = "";
	$tags_count_ma = "";
	$cover_ma = "";
	$cat_ma = "";
	$iimg1 = "";
	$iimg2 = "";
	$iimg3 = "";
	$iimg4 = "";
	$iimg5 = "";
	$nomsg = "";
	$att_count = 0;
	$rank = "";
	$sql = "SELECT * FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$p);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$written_by_ma = $row["written_by"];
		$content2 = stripslashes($row["content"]);
		$content_ma = str_replace( "\n", '<br>', $content2);
		$content_ma = str_replace('\'', '&#39;', $content_ma);
		$content_ma = str_replace('\'', '&#34;', $content_ma);
		$content_ma = urldecode($content_ma);
		$title_main_ma = $title_ma = stripslashes($row["title"]);
		$title_ma = str_replace('\'', '&#39;', $title_ma);
		$title_ma = str_replace('\'', '&#34;', $title_ma);
		$title_main_ma = str_replace('\'', '&#39;', $title_main_ma);
		$title_main_ma = str_replace('\'', '&#34;', $title_main_ma);
		$tmlong = $title_main_ma;
		if(strlen($title_main_ma) > 30){
		    $title_main_ma = mb_substr($title_main_ma, 0, 26, "utf-8");
		    $title_main_ma .= ' ...';
		}
		$post_time_ma = $row["post_time"];
		$post_time_am = strftime("%b %d, %Y", strtotime($post_time_ma));
		$tags_ma = $row["tags"];
		$cat_ma = $row["category"];

		if($row["img1"] != NULL){
			$img1 = $row["img1"];
			$pcurl = '/permUploads/'.$img1.'';
			$rank = "First";
			$iimg1 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img1.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
			$att_count++;
		}
		if($row["img2"] != NULL){
			$img2 = $row["img2"];
			$pcurl = '/permUploads/'.$img2.'';
			$rank = "Second";
			$iimg2 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img2.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
			$att_count++;
		}
		if($row["img3"] != NULL){
			$img3 = $row["img3"];
			$pcurl = '/permUploads/'.$img3.'';
			$rank = "Third";
			$iimg3 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img3.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
			$att_count++;
		}
		if($row["img4"] != NULL){
			$img4 = $row["img4"];
			$pcurl = '/permUploads/'.$img4.'';
			$rank = "Fourth";
			if($iimg1 != "" && $iimg2 != "" && $iimg3 != "" && $iimg5 != ""){
				$iimg4 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img4.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
				$att_count++;
			}else{
				$iimg4 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img4.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
				$att_count++;
			}

		}
		if($row["img5"] != NULL){
			$img5 = $row["img5"];
			$pcurl = '/permUploads/'.$img5.'';
			$rank = "Fifth";
			if($iimg1 != "" && $iimg2 != "" && $iimg3 != "" && $iimg4 != ""){
				$iimg5 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img5.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
			}else{
				$iimg5 = '<div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img5.'\',\''.$rank.'\')" class="pclyxbz lazy-bg"></div>';
			}
			$att_count++;
		}

		$cover = chooseCover($cat);
		$cover_ma = $cover;

		$tags_explode = explode(",", $tags_ma);
		$tags_count_ma = count($tags_explode);
	}
	$stmt->close();
?>
<?php
	$isHeart = false;
	if($user_ok == true){
		$heart_check = "SELECT id FROM heart_likes WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
		$stmt = $conn->prepare($heart_check);
		$stmt->bind_param("sss",$log_username,$p,$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
	if($numrows > 0){
		    $isHeart = true;
		}
	}
	$stmt->close();

	// Add like(heart) button
	$heartButton = "";
	$isHeartOrNot = "";

	if($isHeart == true){
		$heartButton = '<a href="#" onclick="return false;" onmousedown="toggleHeart(\'unheart\',\''.$p.'\', \''.$u.'\',\'heartBtn\')"><img src="/images/heart.png" width="18" height="18" title="Dislike" class="icon_hover_art"></a>';
		$isHeartOrNot = 'You liked this article';
	}else{
		$heartButton = '<a href="#" onclick="return false;" onmousedown="toggleHeart(\'heart\',\''.$p.'\', \''.$u.'\',\'heartBtn\')"><img src="/images/heart_b.png" width="18" height="18" title="Like" class="icon_hover_art"></a>';
		$isHeartOrNot = 'You did not like this article, yet';
	}

	// Heart count
	$sql = "SELECT COUNT(id) FROM heart_likes WHERE art_time=? AND art_uname=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$p,$u);
	$stmt->execute();
	$stmt->bind_result($heart_count);
	$stmt->fetch();
	$stmt->close();
	// Add favourite button
	$isFav = false;
	if($user_ok == true){
		$fav_check = "SELECT id FROM fav_art WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
		$stmt = $conn->prepare($fav_check);
		$stmt->bind_param("sss",$log_username,$p,$u);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
	if($numrows > 0){
		    $isFav = true;
		}
	}
	$stmt->close();

	// Add like(heart) button
	$favButton = "";
	$isFavOrNot = "";
	if($isFav == true){
		$favButton = '<a href="#" onclick="return false;" onmousedown="toggleFav(\'unfav\',\''.$p.'\', \''.$u.'\',\'favBtn\')"><img src="/images/star.png" width="20" height="20" title="Unfavourite" class="icon_hover_art"></a>';
		$isFavOrNot = "You added as favourite this article";
	}else{
		$favButton = '<a href="#" onclick="return false;" onmousedown="toggleFav(\'fav\',\''.$p.'\', \''.$u.'\',\'favBtn\')"><img src="/images/star_b.png" width="20" height="20" title="Favourite" class="icon_hover_art"></a>';
		$isFavOrNot = "You did not add as favourite this article, yet";
	}

	// Add delete button
	$deleteButton = "";
	if($log_username == $written_by_ma){
		$deleteButton = '<button onclick="deleteArt(\''.$p.'\',\''.$u.'\')" id="deleteBtn_art" class="main_btn_fill fixRed">Delete Article</button>';
	}

	// Add edit button
	$editButton = "";
	if($log_username == $written_by_ma){
		$editButton = '<button class="main_btn_fill fixRed" onclick="editArt()" id="edit_btn_art" class="main_btn_fill fixRed">Edit article</button>';
	}

	// Get related articles
	// First get user's friends
	$all_friends = array();
	$sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		array_push($all_friends, $row["user1"]);
	}
	$stmt->close();

	$sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		array_push($all_friends, $row["user2"]);
	}
	$stmt->close();
	// Implode all friends array into a strinh
	$allfmy = join("','", $all_friends);
	// Make sql
	$related = "";
	$all_related = array();
	$sql = "SELECT * FROM articles WHERE category = ? OR tags IN ('$tags') OR written_by IN ('$allfmy')";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$cat);
	$stmt->execute();
	$result3 = $stmt->get_result();
	while($row = $result3->fetch_assoc()){
		array_push($all_related, $row["id"]);
	}
	$stmt->close();
	// Get user's articles to count the difference
	$my_arts = array();
	$sql = "SELECT * FROM articles WHERE written_by = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		array_push($my_arts, $row["id"]);
	}
	$stmt->close();
	// Now count the difference
	$difference = array_diff($all_related, $my_arts);
	$diff = join(",",$difference);
	$sql = "SELECT * FROM articles WHERE id IN ('$diff') ORDER BY post_time DESC LIMIT 3";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$written_bylink = $row["written_by"];
		$wb = urlencode($written_bylink);
		$cat = $row["category"];
		$tags2 = $row["tags"];
		$linkp = $row["post_time"];
		$title = html_entity_decode($row["title"]);
		$dop_ = $row["post_time"];
		$dpp = base64url_encode($linkp,$hshkey);
		$dop = strftime("%b %d, %Y", strtotime($dop_));
		$agoform = time_elapsed_string($dop_);

		$cover = chooseCover($cat);

		$related .= '<a href="/articles/'.$dpp.'/'.$wb.'"><div class="article_echo_2 artRelGen">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_bylink.'</p>';
            $related .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $related .= '<p class="title_"><b>Posted: </b>'.$agoform.' ago</p>';
            $related .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
	}
	$stmt->close();

	// Do not let $related to be empty

	if($related == ""){
		$sql = "SELECT * FROM articles WHERE written_by != ? AND category = ? AND post_time = ? LIMIT 3";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$log_username,$cat,$post_time);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
		$written_bylink = $row["written_by"];
		$wb = urlencode($written_bylink);
		$cat = $row["category"];
		$tags2 = $row["tags"];
		$linkp = $row["post_time"];
		$title = html_entity_decode($row["title"]);
		$dop_ = $row["post_time"];
		$dpp = base64url_encode($linkp,$hshkey);
		$dop = strftime("%b %d, %Y", strtotime($dop_));
		$agoform = time_elapsed_string($dop_);

		$cover = chooseCover($cat);
		
		$related .= '<a href="/articles/'.$dpp.'/'.$wb.'"><div class="article_echo_2 artRelGen">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_bylink.'</p>';
            $related .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $related .= '<p class="title_"><b>Posted: </b>'.$agoform.' ago</p>';
            $related .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
		}
		$stmt->close();
	}

	if($related == ""){
		$sql = "SELECT * FROM articles WHERE written_by != ? AND category = ? OR post_time = ? OR title = ? LIMIT 3";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$log_username,$cat,$post_time,$title);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
		$written_bylink = $row["written_by"];
		$wb = urlencode($written_bylink);
		$cat = $row["category"];
		$tags2 = $row["tags"];
		$linkp = $row["post_time"];
		$title = html_entity_decode($row["title"]);
		$dop_ = $row["post_time"];
		$dpp = base64url_encode($linkp,$hshkey);
		$dop = strftime("%b %d, %Y", strtotime($dop_));
		$agoform = time_elapsed_string($dop_);

		$cover = chooseCover($cat);
		
		$related .= '<a href="/articles/'.$dpp.'/'.$wb.'"><div class="article_echo_2 artRelGen">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_bylink.'</p>';
            $related .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $related .= '<p class="title_"><b>Posted: </b>'.$agoform.' ago</p>';
            $related .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
		}
		$stmt->close();
	}

    $isrel = false;
	if($related == ""){
		$related = '<p style="font-size: 14px; color: #999;" class="txtc">We could not list any related article for you</p>';
		$isrel = true;
	}

	// Count the replies and posts
	// ALL
	$sql = "SELECT COUNT(id) FROM article_status WHERE artid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i",$id);
	$stmt->execute();
	$stmt->bind_result($scount);
	$stmt->fetch();
	$stmt->close();
	// POSTS
	$sql = "SELECT COUNT(id) FROM article_status WHERE artid = ? AND type = ? OR type = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("iss",$id,$a,$c);
	$stmt->execute();
	$stmt->bind_result($cposts);
	$stmt->fetch();
	$stmt->close();
	// REPLIES
	$sql = "SELECT COUNT(id) FROM article_status WHERE type = ? AND artid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("si",$b,$id);
	$stmt->execute();
	$stmt->bind_result($creplies);
	$stmt->fetch();
	$stmt->close();

	// Get users's other articles
	// FIRST CHECK IF THERE ARE AT LEAST 4 STATUS POSTS
	$usersarts = "";
	$sql = "SELECT COUNT(id) FROM article_status WHERE type = ? OR type = ? AND artid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssi",$a,$c,$id);
	$stmt->execute();
	$stmt->bind_result($countif);
	$stmt->fetch();
	$stmt->close();

		// START TO BUILD THE DIV
		$sql = "SELECT * FROM articles WHERE written_by = ? ORDER BY RAND() LIMIT 3";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$u);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
		$written_bylink = $row["written_by"];
		$wb = urlencode($written_bylink);
		$cat = $row["category"];
		$tags2 = $row["tags"];
		$linkp = $row["post_time"];
		$title = html_entity_decode($row["title"]);
		$dop_ = $row["post_time"];
		$dpp = base64url_encode($linkp,$hshkey);
		$dop = strftime("%b %d, %Y", strtotime($dop_));
		$agoform = time_elapsed_string($dop_);

		$cover = chooseCover($cat);
		
		$usersarts .= '<a href="/articles/'.$dpp.'/'.$wb.'"><div class="article_echo_2 artRelGen">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_bylink.'</p>';
            $usersarts .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $usersarts .= '<p class="title_"><b>Posted: </b>'.$agoform.' ago</p>';
            $usersarts .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
		}
	
	$stmt->close();
	
	if($usersarts == ""){
	    $usersarts = '<p style="font-size: 14px; color: #999;" class="txtc">You have no other articles except this one</p>';
	}

	// Get favourite articles
	$fav_arts = "";
	$sql = "SELECT f.*, a.* FROM fav_art AS f LEFT JOIN articles AS a ON f.username = a.written_by WHERE f.art_uname = ? AND f.art_time = a.post_time ORDER BY post_time DESC LIMIT 3";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$written_bylink = $row["written_by"];
		$wb = urlencode($written_bylink);
		$cat = $row["category"];
		$tags2 = $row["tags"];
		$linkp = $row["post_time"];
		$title = html_entity_decode($row["title"]);
		$dop_ = $row["post_time"];
		$dpp = base64url_encode($linkp,$hshkey);
		$dop = strftime("%b %d, %Y", strtotime($dop_));
		$agoform = time_elapsed_string($dop_);

		$cover = chooseCover($cat);
		
		$fav_arts .= '<a href="/articles/'.$dpp.'/'.$wb.'"><div class="article_echo_2 artRelGen">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_bylink.'</p>';
            $fav_arts .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $fav_arts .= '<p class="title_"><b>Posted: </b>'.$agoform.' ago</p>';
            $fav_arts .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
	}

	$stmt->close();
	
	$isfavis = false;
	if($fav_arts == ""){
		if($isOwner == "No"){
	    	$fav_arts = '<p style="font-size: 14px; color: #999;" class="txtc">It seems that '.$u.' has not added any articles as favourite yet</p>';
	    }else{
	    	$fav_arts = '<p style="font-size: 14px; color: #999;" class="txtc">It seems that you have not added any articles as favourite yet</p>';
	    }
	    $isfavis = true;
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $tmlong; ?></title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Read <?php echo $u; ?>'s article about '<?php echo $tmlong; ?>'">
	<script src="/js/jjs.js" async></script>
	<script src="/text_editor.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script src="/js/mbc.js"></script>
	<script src="/js/lload.js"></script>
	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script type="text/javascript">
		function toggleHeart(type,p,u,elem){
			var ajax = ajaxObj("POST","/php_parsers/heart_system.php");
			ajax.onreadystatechange = function(){
				if(ajaxReturn(ajax) == true){
					if(ajax.responseText == "heart_success"){
						_(elem).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleHeart(\'unheart\',\'<?php echo $p; ?>\', \'<?php echo $u; ?>\',\'heartBtn\')"><img src="/images/heart.png" width="18" height="18" title="Dislike" class="icon_hover_art"></a>';
						let cnt = _("cntHeart").innerText;
						cnt = Number(cnt);
						_("cntHeart").innerText = ++cnt;
					}else if(ajax.responseText == "unheart_success"){
						_(elem).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleHeart(\'heart\',\'<?php echo $p; ?>\', \'<?php echo $u; ?>\',\'heartBtn\')"><img src="/images/heart_b.png" width="18" height="18" title="Like" class="icon_hover_art"></a>';
						let cnt = _("cntHeart").innerText;
						cnt = Number(cnt);
						_("cntHeart").innerText = --cnt;
					}else{
						_("overlay").style.display = "block";
						_("overlay").style.opacity = 0.5;
						_("dialogbox").style.display = "block";
						_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your article like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
						document.body.style.overflow = "hidden";
						_(elem).innerHTML = 'Try again later';
					}
				}
			}
			ajax.send("type="+type+"&p="+p+"&u="+u);
		}

	function toggleFav(type,p,u,elem){
		var ajax = ajaxObj("POST","/php_parsers/fav_system.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "fav_success"){
					_(elem).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleFav(\'unfav\',\'<?php echo $p; ?>\', \'<?php echo $u; ?>\',\'favBtn\')"><img src="/images/star.png" width="20" height="20" title="Favourite" class="icon_hover_art"></a>';
				}else if(ajax.responseText == "unfav_success"){
					_(elem).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleFav(\'fav\',\'<?php echo $p; ?>\', \'<?php echo $u; ?>\',\'favBtn\')"><img src="/images/star_b.png" width="20" height="20" title="Unfavourite" class="icon_hover_art"></a>';
				}else{
					_("overlay").style.display = "block";
					_("overlay").style.opacity = 0.5;
					_("dialogbox").style.display = "block";
					_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your favourite article. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
					document.body.style.overflow = "hidden";
					_(elem).innerHTML = 'Try again later';
				}
			}
		}
		ajax.send("type="+type+"&p="+p+"&u="+u);
	}

	function deleteArt(p,u){
		var conf = confirm("Are you sure you want to delete this article?");
		if(conf != true){
			return false;
		}
		var x = _("deleteBtn_art");
		var y = _("big_view_article");
		x.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		var ajax = ajaxObj("POST","/php_parsers/art_del.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "delete_success"){
					y.innerHTML = '<p class="success_green">You have successfully deleted this article</p>';
				}else{
					_("overlay").style.display = "block";
					_("overlay").style.opacity = 0.5;
					_("dialogbox").style.display = "block";
					_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your article deletion. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
					document.body.style.overflow = "hidden";
					y.innerHTML = 'Try again later';
				}
			}
		}
		ajax.send("p="+p+"&u="+u);
	}

	function editArt(){
	    document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
		var x = _("edit_btn_art");
		var y = _("big_view_article");
		y.innerHTML = "<p style=\"text-align: center; margin-top: 0;\">You are now in editor mode</p><hr class=\"dim\"><textarea name=\"title\" id=\"title\" type=\"text\" maxlength=\"100\" style=\"width: 98%;\" placeholder=\"Article Title\"></textarea><div class=\"toolbar\"><a onclick=\"execCmd('bold')\"><i class=\"fa fa-bold\"></i></a><a onclick=\"execCmd('italic')\"><i class=\"fa fa-italic\"></i></a><a onclick=\"execCmd('underline')\"><i class=\"fa fa-underline\"></i></a><a onclick=\"execCmd('strikeThrough')\"><i class=\"fa fa-strikethrough\"></i></a><a onclick=\"execCmd('justifyLeft')\"><i class=\"fa fa-align-left\"></i></a><a onclick=\"execCmd('justifyCenter')\"><i class=\"fa fa-align-center\"></i></a><a onclick=\"execCmd('justifyRight')\"><i class=\"fa fa-align-right\"></i></a><a onclick=\"execCmd('justifyFull')\"><i class=\"fa fa-align-justify\"></i></a><a onclick=\"execCmd('cut')\"><i class=\"fa fa-cut\"></i></a><a onclick=\"execCmd('copy')\"><i class=\"fa fa-copy\"></i></a><a onclick=\"execCmd('indent')\"><i class=\"fa fa-indent\"></i></a><a onclick=\"execCmd('outdent')\"><i class=\"fas fa-outdent\"></i></a><a onclick=\"execCmd('subscript')\"><i class=\"fa fa-subscript\"></i></a><a onclick=\"execCmd('superscript')\"><i class=\"fa fa-superscript\"></i></a><a onclick=\"execCmd('undo')\"><i class=\"fa fa-undo\"></i></a><a onclick=\"execCmd('redo')\"><i class=\"fas fa-redo\"></i></a><a onclick=\"execCmd('insertUnorderedList')\"><i class=\"fa fa-list-ul\"></i></a><a onclick=\"execCmd('insertOrderedList')\"><i class=\"fa fa-list-ol\"></i></a><a onclick=\"execCmd('insertParagraph')\"><i class=\"fa fa-paragraph\"></i></a>&nbsp;<select class=\"ssel sselArt\" style=\"width: 85px; margin-top: 5px; margin-right: 5px; background-color: #fff;\" onchange=\"execCmdWithArg('formatBlock', this.value)\" class=\"font_all\"><option value=\"H1\">H1</option><option value=\"H2\">H2</option><option value=\"H3\">H3</option><option value=\"H4\">H4</option><option value=\"H5\">H5</option><option value=\"H6\">H6</option></select><a onclick=\"execCmd('insertHorizontalRule')\">HR</a><a onclick=\"execCmd('createLink', prompt('Enter URL', 'https'))\"><i class=\"fa fa-link\"></i></a><a onclick=\"execCmd('unlink')\"><i class=\"fa fa-unlink\"></i></a><a onclick=\"toggleSource()\"><i class=\"fa fa-code\"></i></a><a onclick=\"toggleEdit()\"><i class=\"fas fa-edit\"></i></a>&nbsp;<select onchange=\"execCmdWithArg('fontName', this.value)\" class=\"ssel sselArt\" style=\"width: 85px; margin-top: 5px; margin-right: 5px; background-color: #fff;\" id=\"font_name\"><option value=\"Arial\">Arial</option><option value=\"Comic Sans MS\">Comic Sans MS</option><option value=\"Courier\">Courier</option><option value=\"Georgia\">Georgia</option><option value=\"Helvetica\">Helvetica</option><option value=\"Thaoma\">Thaoma</option><option value=\"Palatino Linotype\">Palatino Linotype</option><option value=\"Arial Black\">Arial Black</option><option value=\"Lucida Sans Unicode\">Lucida Sans Unicode</option><option value=\"Trebuchet MS\">Times New Roman</option><option value=\"Lucida Console\">Lucida Console</option><option value=\"Courier New\">Courier New</option></select>&nbsp;<select class=\"ssel sselArt\" style=\"width: 85px; margin-top: 5px; margin-right: 5px; background-color: #fff;\" onchange=\"execCmdWithArg('formatSize', this.value)\" class=\"font_all\"><option value=\"1\">1</option><option value=\"2\">2</option><option value=\"3\">3</option><option value=\"4\">4</option><option value=\"5\">5</option><option value=\"6\">6</option><option value=\"7\">7</option></select><span>Fore Color: <input type=\"color\" style=\"vertical-align: middle; margin-top: -4px;\" onchange=\"execCmdWithArg('foreColor', this.value)\" id=\"fcolor\"/></span><span>Background Color: <input type=\"color\" style=\"vertical-align: middle; margin-top: -4px;\" onchange=\"execCmdWithArg('hiliteColor', this.value)\" id=\"bcolor\"/></span><a onclick=\"execCmd('selectAll')\"><i class=\"fa fa-reply-all\"></i></a></div><form id=\"editArtForm\"><textarea style=\"display:none;\" name=\"myTextArea\" id=\"myTextArea\" cols=\"100\" rows=\"14\"></textarea><iframe name=\"richTextField\" id=\"richTextField\" allowtransparency=\"true\" style=\"background: #fff;\"></iframe></form><br /><p style=\"font-size: 14px;\">Further help about editing &amp; writing a well-received, clear and formal article: <button class=\"main_btn_fill fixRed\" onclick=\"openAHelp()\">See help</button></p><hr class=\"dim\"><p style=\"font-size: 14px; margin-top: 0px;\">Attach images to your article in order to make visually better (number of attachable images is <?php echo $e_img_count; ?>)&nbsp;<button class=\"main_btn_fill fixRed\" onclick=\"openIHelp()\">Get informed</button></p>";
		
		var e_img1 = "<?php echo $e_img1; ?>";
		var e_img2 = "<?php echo $e_img2; ?>";
		var e_img3 = "<?php echo $e_img3; ?>";
		var e_img4 = "<?php echo $e_img4; ?>";
		var e_img5 = "<?php echo $e_img5; ?>";

		let wOne = [];

		if(e_img1 == "") wOne.push(1);
		
		if(e_img2 == "") wOne.push(2);
		
		if(e_img3 == "") wOne.push(3);
		
		if(e_img4 == "") wOne.push(4);
	
		if(e_img5 == "") wOne.push(5);

		for(let i = 0; i < wOne.length; i++){
			y.innerHTML += "<div id='au"+wOne[i]+"'><img src=\"/images/addimg.png\" onclick=\"triggerUpload(event, 'art_upload"+wOne[i]+"')\" class=\"triggerBtnreply mob_square\" /></div><span id='aimage"+wOne[i]+"'></span><input type=\"file\" name=\"file_array\" id='art_upload"+wOne[i]+"' onchange=\"doUploadGen('art_upload"+wOne[i]+"', 'au"+wOne[i]+"', '"+wOne[i]+"')\" accept=\"image/*\" style=\"display: none;\" />";
		}

		y.innerHTML += "<div class=\"clear\"></div><hr class=\"dim\"><button class=\"main_btn_fill fixRed\" onclick=\"saveEditArt('<?php echo $p; ?>','<?php echo $log_username; ?>')\">Save article</button><span id=\"astatus\"></span>";

		richTextField.document.designMode = "On";
		var text_to_edit = `<?php echo html_entity_decode($content_ma); ?>`;
		window.frames['richTextField'].document.body.innerHTML = text_to_edit;
		var doc = frames["richTextField"].document;
        //Trigger a page "load".
        /*$(function() {
            var $frame = $('#richTextField');
            $('body').html( $frame );
            setTimeout( function() {
                var doc = $frame[0].contentWindow.document;
                var $body = $('body',doc);
                $body.html('<h1>Test</h1>');
            }, 1 );
        });*/
        $("#richTextField").contents().find('html').html(text_to_edit);
        //Set innerHTML of the body tag
		_("title").innerHTML = '<?php echo $title_main_ma; ?>';
	}
	
	    var showingSourceCode = false;
		var isInEditMode = false;

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

		var hasImage1 = "";
		var hasImage2 = "";
		var hasImage3 = "";
		var hasImage4 = "";
		var hasImage5 = "";


		function doUploadGen(data, holder, num){
            var s = _(data).files[0];
              if ("" == s.name) {
                return false;
              }
              if ("image/jpeg" != s.type && "image/gif" != s.type && "image/png" != s.type && "image/jpg" != s.type) {
                return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
                false;
              }
              _(holder).innerHTML = '<img src="/images/whup.jpg" width="100" height="100" class="triggerBtnreply mob_square" style="margin-left: 0px;">';
              var formData = new FormData;
              formData.append("stPic", s);
              var xhr = new XMLHttpRequest;
              xhr.addEventListener("load", function load(event){
                completeHandlerGen(event, holder, num)
              }, false);
              xhr.addEventListener("error", function error(event){
                errorHandlerGen(event, holder, num)
              }, false);
              xhr.addEventListener("abort", function abort(event){
                abortHandlerGen(event, holder, num)
              }, false);
              xhr.open("POST", "/php_parsers/photo_system.php");
              xhr.send(formData);
        }

        function completeHandlerGen(event, holder, num) {
          var t = event.target.responseText.split("|");
          if ("upload_complete" == t[0]) {
            if(num == "1") hasImage1 = t[1];
            else if(num == "2") hasImage2 = t[1];
            else if(num == "3") hasImage3 = t[1];
            else if(num == "4") hasImage4 = t[1];
            else if(num == "5") hasImage5 = t[1];
            _(holder).innerHTML = '<img src="/tempUploads/' + t[1] + '" class="triggerBtnreply mob_square" style="border-radius: 20px;"/>';
          } else {
            _(holder).innerHTML = "Unfortunately an unknown error has occured";
          }
        }
        function errorHandlerGen(event, holder) {
          _(holder).innerHTML = "Upload Failed";
        }
        function abortHandlerGen(event, holder) {
          _(holder).innerHTML = "Upload Aborted";
        }

		function openAHelp(){
			_("overlay").style.display = "block";
			_("overlay").style.opacity = 0.5;
			_("dialogbox_art").style.display = "block";
			_("dialogbox_art").innerHTML = "<p style=\"font-size: 16px; font-style: italic;\">How to write a well-received, clean, entertaining formal article?</p><hr class=\"dim\"><br><p>In order to write a good article you have to keep in mind the following things and instructions:</p><br><p>1. Once you have choosed a topic do a research of that to get a clear picture and enough knowledge</p><p>2. Create a strong, unique title that will describe your article in a few words and will grab the readers&#39; attention</p><p>3. Divide your article into more (at least 3) paragraphs: <i>introducion</i>, <i>main part</i>, <i>conclusion</i></p><p>4. Write major points</p><p>5. Write your article first and edit it later</p><b><p>Structure of a well-written formal article</p></b><p>The <i>introducion:</i></p><p style=\"margin: 0px;\">it is one of the most essential part of the article - grab the attention of your readers, hook them in.</p><p style=\"font-size: 12px !important; margin-left: 20px;\">Use drama, emotion, quotations, rhetorical questions, descriptions, allusions, alliterations and methapors.</p><br><p> The <i>main part(s):</i></p><p>this part of the article needs to stick to the ideas or answer any questions raised in the intoducion</p><p style=\"font-size: 12px !important; margin-left: 20px;\">Try to maintain an \"atmosphere\" / tone / distinctive voice throughout the writing.</p><br><p>The <i>conclusion:</i></p><p>it is should be written to help the reader remember the article. Use a strong punch-line.</p><br><p style=\"font-size: 16px; font-style: italic;\">Images &amp; visualization in the topic for the better understanding</p><hr class=\"dim\"><p>Do research and a plan for your article (<a href=\"http://www.e-custompapers.com/blog/practical-tips-for-article-reviews.html\" target=\"_blank\">source</a>)</p><img src=\"/images/howtoart.jpg\"><p>The parts of a well-written article (<a href=\"https://apessay.com/order/?rid=ea55690ca8f7b080\" target=\"_blank\">source</a>)</p><img src=\"/images/partsa.jpg\"><button id=\"vupload\" style=\"float: right; margin: 3px;\" onclick=\"closeDialog_a()\">Close</button>";
			document.body.style.overflow = "hidden";
		}

		function openIHelp(){
			_("overlay").style.display = "block";
			_("overlay").style.opacity = 0.5;
			_("dialogbox_art").style.display = "block";
			_("dialogbox_art").innerHTML = "<b style=\"font-size: 16px;\">Attachable images:</b><hr class=\"dim\"><p>You can attach up to 5 images to your article in order to make it more visually, helpful and picturesque. It is an optional avalibility but it is highly recommended to attach at least one picture to your article. If you do not attach any images nothing will appear instead of this. Important: the rules are the same as with the standard image uploading. The maximum image size is 5MB and the allowed image extenstions are jpg, jpeg, gif and png. For more information please visit the help page.</p><button id=\"vupload\" style=\"float: right; margin: 3px;\" onclick=\"closeDialog_a()\">Close</button>";
			document.body.style.overflow = "hidden";
		}

	function topFunction() {
    	document.body.scrollTop = 0; // For Chrome, Safari and Opera 
    	document.documentElement.scrollTop = 0; // For IE and Firefox
	}

	function openHelp(){
		var o = _("help_hide_div");
		if(o.style.display == 'none'){
			o.style.display = 'block';
		}else{
			o.style.display = 'none';
		}
	}

	function saveEditArt(p,u){
		var title = _("title").value;
		var theForm = _("editArtForm");
		var status = _("astatus");
		theForm.elements["myTextArea"].value = window.frames['richTextField'].document.body.innerHTML;
		var texta = theForm.elements["myTextArea"].value;
		texta = encodeURIComponent(texta);
		if(title == "" || theForm == ""){
			status.innerHTML = '<p style="color: red;">Please fill in all fields!</p>';
		}
		status.innerHTML='<img src="/images/rolling.gif" width="30" height="30">';
		var ajax = ajaxObj("POST","/php_parsers/edit_art_save.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "save_success"){
				    status.innerHTML = '<p style="color: #999;" class="txtc">You have successfully saved your article</p><br><a class="txtc" href="/articles/<?php echo base64url_encode($p,$hshkey); ?>/<?php echo $log_username; ?>">Check out your new article</a>';
				}else{
					_("overlay").style.display = "block";
					_("overlay").style.opacity = 0.5;
					_("dialogbox").style.display = "block";
					_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured while saving your article. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
					document.body.style.overflow = "hidden";
					x.innerHTML = '<p style="color: #999;" class="txtc">Try again later</p>';
				}
			}
		}
		ajax.send("p="+p+"&u="+u+"&texta="+texta+"&title="+title+"&img1="+hasImage1+"&img2="+hasImage2+"&img3="+hasImage3+"&img4="+hasImage4+"&img5="+hasImage5);
	}

	// Edit
	/*
	document.documentElement.innerHTML = '<iframe id="richTextField"></iframe>';
					var frame = document.getElementById('richTextField').contentWindow.document;
					frame.open();
					frame.write('<?php echo $u; ?>');
					frame.close();
					*/

	function printContent(el){
		var restore = document.body.innerHTML;
		var print = _(el).innerHTML;
		document.body.innerHTML = print;
		window.print();
		document.body.innerHTML = restore;
	}

	function shareArticle(id){
		var ajax = ajaxObj("POST", "/php_parsers/status_system.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "share_art_ok"){
					_("overlay").style.display = "block";
					_("overlay").style.opacity = 0.5;
					_("dialogbox").style.display = "block";
					_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Share this article</p><p>You have successfully shared this article which will be visible on your main profile page in the comment section.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
					document.body.style.overflow = "hidden";
				}else{
					_("overlay").style.display = "block";
					_("overlay").style.opacity = 0.5;
					_("dialogbox").style.display = "block";
					_("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately the article sharing has failed. Please try again later.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
					document.body.style.overflow = "hidden";
				}
			}
		}
		ajax.send("action=share_art&id="+id);
	}

	function closeDialog(){
		_("dialogbox").style.display = "none";
		_("overlay").style.display = "none";
		_("overlay").style.opacity = 0;
		document.body.style.overflow = "auto";
	}

	function openIimgBig(img,count){
		_("dialogbox").style.display = "block";
		_("dialogbox").style.width = "auto";
		_("dialogbox").style.height = "auto";
		_("overlay").style.display = "block";
		_("overlay").style.opacity = 0.5;
		document.body.style.overflow = "hidden";
		_("dialogbox").innerHTML = ""+count+" attached image<img src='/permUploads/"+img+"' style='width: 100%; height: auto;'><br><br><br><button id='vupload' style='position: absolute; right: 3px; bottom: 3px;' onclick='closeDialog()'>Close</button>";
	}
	
	function closeDialog_a(){
		_("dialogbox_art").style.display = "none";
		_("overlay").style.display = "none";
		_("overlay").style.opacity = 0;
		document.body.style.overflow = "auto";
	}
	</script>
</head>
<body>
	<?php include_once("template_pageTop.php"); ?>
	<div id="overlay"></div>
	<div id="pageMiddle_2">
	    <div id="dialogbox_art"></div>
		<div id="dialogbox"></div>

		<div class="biggerHolder">
	  <div id="big_view_article" class="genWhiteHolder">
	      <?php if($_SESSION["username"] != ""){ ?>
	    <div id="heart_btn"><span id="cntHeart" style="float: left;"><?php echo $heart_count; ?></span>&nbsp;&nbsp;<span id="heartBtn"><?php echo $heartButton; ?></span></div>
	  	<div id="fav_btn"><span id="favBtn" style="margin-right: 7px;"><?php echo $favButton; ?></span></div>
	  	<img src="/images/black_share.png" id="art_share" style="width: 20px; height: 20px;" onclick="shareArticle('<?php echo $id; ?>')">
	  	<?php } ?>
	  	<div id="arti_pp" class="lazy-bg genBg" data-src="<?php echo $profile_pic; ?>" onclick="window.location = '/user/<?php echo $u; ?>/'"></div>
	  	<div id="forpcontent" style="font-size: 14px;">
	  	    <div id="artkeppal">
    		  	<p style="font-size: 14px; margin: 0px;"><strong>Author: </strong><b class="art_font"><a href="/user/<?php echo $u; ?>/"><?php echo $u; ?></a></b></p>
    		 	<p style="font-size: 14px; margin: 0px;"><strong>Title: </strong><b class="art_font"><?php echo $tmlong; ?></b></p>
    		 	<p style="font-size: 14px; margin: 0px;"><strong>Category: </strong><b class="art_font"><?php echo $cat_ma; ?></b></p>
    		 	<p style="font-size: 14px; margin: 0px;"><strong>Posted: </strong><b class="art_font"><?php echo $post_time_am; ?></b></p>
    		 	<a href="#pcontent">Go to comments</a>
		 	</div>
		 	<br /><hr class="dim">
		 	<?php echo $content_ma; ?></p>
		 	<hr class="dim">
		 	<div id="attached_photos" class="flexibleSol">
			 	<?php echo $iimg1; ?>
			 	<?php echo $iimg2; ?>
			 	<?php echo $iimg3; ?>
			 	<?php echo $iimg4; ?>
			 	<?php echo $iimg5; ?>
		 	</div>
		 	<div class="clear"></div>
		 	<br>
		</div>
	 	<div id="pcontent">
		 	<button onclick="topFunction()" id="back_top" class="main_btn_fill fixRed">Back to top</button>
		 	<?php echo $deleteButton; ?>
		 	<?php echo $editButton; ?>
		 	<button class="main_btn_fill fixRed" onclick="printContent('forpcontent')">Print article</button>
	 	</div>
	 </div>
	 <p style="color: #999;" class="txtc"><?php echo $scount; ?> comments recorded</p>
	  	<hr class="dim">
	  <?php if($isBlock != true){ ?><?php require_once 'article_status.php'; ?><?php }else{ ?><p style="color: #006ad8;" class="txtc">Alert: this user blocked you, therefore you cannot post on his/her articles!</p><?php } ?>
	</div>
	  <div id="uptoea">
		  <div id="yellow_box_art" class="genWhiteHolder">
		  	<b style="font-size: 16px;">Information about the article</b><br /><br />
		  	<div id="art_mob_wrap">
			  	&bull; Tags(<?php echo $tags_count_ma; ?>) <?php echo $tags_ma; ?><br />
			  	&bull; <?php echo $isHeartOrNot; ?><br />
			  	&bull; <?php echo $isFavOrNot; ?><br />
			  	&bull; This article belongs to <?php echo $u; ?><br />
			  	<?php echo "&bull; This article has the &#34;".$cat_ma."&#34; category"; ?>
			  </div>
		  	<div style="float: right; margin-top: -85px; margin-right: 2px;"><?php echo $cover_ma; ?></div>
		  </div>

		    <div class="compdiv genWhiteHolder">
		  	<b style="font-size: 16px;">Related articles</b>
			  	<div id="related_arts">
			  		<?php echo $related; ?>
			  	</div>
			</div>

		    <div class="compdiv genWhiteHolder">
		  	<?php if($isOwner == "Yes"){echo "<b style='font-size: 16px;'>My articles</b>";}else{echo "<b style='font-size: 16px;'>".$u."&#39;s articles</b>";} ?>

		  	<div id="artsminemy">
		  		<?php echo $usersarts; ?>
		  </div>
		 </div>
		   <div class="compdiv genWhiteHolder">
		  	<?php if($isOwner == "Yes"){echo "<b style='font-size: 16px;'>My favourite articles</b>";}else{echo "<b style='font-size: 16px;'>".$u."&#39;s favourite articles</b>";} ?>

		  	<div id="addfavarts">
		  		<?php echo $fav_arts; ?>
		  </div>
		</div>
		</div>
		
	 </div>
	 <div class="clear"></div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
	    var _0x6016=["\x73\x6C\x69\x64\x65\x32","\x6F\x6E\x6D\x6F\x75\x73\x65\x64\x6F\x77\x6E","\x61\x72\x74\x73\x6D\x69\x6E\x65\x6D\x79","\x72\x69\x67\x68\x74","\x73\x6C\x69\x64\x65\x31","\x6C\x65\x66\x74","\x73\x6C\x69\x64\x65\x6C","\x72\x65\x6C\x61\x74\x65\x64\x5F\x61\x72\x74\x73","\x73\x6C\x69\x64\x65\x72","\x73\x6C\x69\x64\x65\x6C\x6C","\x61\x64\x64\x66\x61\x76\x61\x72\x74\x73","\x73\x6C\x69\x64\x65\x72\x72","\x73\x63\x72\x6F\x6C\x6C\x4C\x65\x66\x74","\x63\x6C\x65\x61\x72\x49\x6E\x74\x65\x72\x76\x61\x6C"];if(_(_0x6016[0])!= undefined){var forward=_(_0x6016[0]);forward[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[2]);sideScroll(_0xa11ax2,_0x6016[3],15,250,20)}};if(_(_0x6016[4])!= undefined){var back=_(_0x6016[4]);back[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[2]);sideScroll(_0xa11ax2,_0x6016[5],15,250,20)}};if(_(_0x6016[6])!= undefined){var f2=_(_0x6016[6]);f2[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[7]);sideScroll(_0xa11ax2,_0x6016[3],15,250,20)}};if(_(_0x6016[8])!= undefined){var b2=_(_0x6016[8]);b2[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[7]);sideScroll(_0xa11ax2,_0x6016[5],15,250,20)}};if(_(_0x6016[9])!= undefined){var f3=_(_0x6016[9]);f3[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[10]);sideScroll(_0xa11ax2,_0x6016[3],15,250,20)}};if(_(_0x6016[11])!= undefined){var b3=_(_0x6016[11]);b3[_0x6016[1]]= function(){var _0xa11ax2=_(_0x6016[10]);sideScroll(_0xa11ax2,_0x6016[5],15,250,20)}};function sideScroll(_0xa11ax9,_0xa11axa,_0xa11axb,_0xa11axc,_0xa11axd){scrollAmount= 0;var _0xa11axe=setInterval(function(){if(_0xa11axa== _0x6016[5]){_0xa11ax9[_0x6016[12]]-= _0xa11axd}else {_0xa11ax9[_0x6016[12]]+= _0xa11axd};scrollAmount+= _0xa11axd;if(scrollAmount>= _0xa11axc){window[_0x6016[13]](_0xa11axe)}},_0xa11axb)}
    	    window.onbeforeunload = function(){
        	    if(_("title").innerHTML != "" || window.frames['richTextField'].document.body.innerHTML != ""){
        	        return "You have unsaved work left. Are you sure you want to leave the page?";
        	    }
        	}
        	
        	function getCookie(cname) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for(var i = 0; i <ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
	 	function setDark(){
            var cssId = 'thisClassDoesNotExist';
            if (!document.getElementById(cssId)){
                var head  = document.getElementsByTagName('head')[0];
                var link  = document.createElement('link');
                link.id   = cssId;
                link.rel  = 'stylesheet';
                link.type = 'text/css';
                link.href = '/style/dark_style.css';
                link.media = 'all';
                head.appendChild(link);
            }
        }
        var isdarkm = getCookie("isdark");
        if(isdarkm == "yes"){
            setDark();
        }
	</script>
</body>
</body>
</html>
