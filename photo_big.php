<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'safe_encrypt.php';
	require_once 'phpmobc.php';
	require_once 'headers.php';
	// Sanitize type vars
	$a = "a";
	$b = "b";
	$c = "c";
	$one = "1";
	$vdelete_btn = "";
	$gallery_u = "";
	$description_u = "";
	$uploaddate_u = "";
	$uds = "";
	$p = "";
	$u = "";
	if(isset($_GET["p"]) && isset($_GET['u'])){
		$p = $_GET["p"];
		$u = $_GET['u'];
	}else{
		header('Location: /index');
	}

	$_SESSION["photo"] = $p;
	
	if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
	    // Select the member from the users table
        $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$one);
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
	}

	$countRels = 0;
	$countMine = 0;
	
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

    $all_friends = array();
	$sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$u,$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		if ($row["user1"] != $u && $row["user1"] != $log_username){array_push($all_friends, $row["user1"]);}
		if ($row["user2"] != $u && $row["user2"] != $log_username){array_push($all_friends, $row["user2"]);}
	}
	$stmt->close();
	$pcallf = join("','",$all_friends);
    $sql = "SELECT gallery FROM photos WHERE filename=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$p);
    $stmt->execute();
    $stmt->bind_result($galgal);
    $stmt->fetch();
    $stmt->close();
    //$galgal = htmlspecialchars($galgal);
    // Select photos from the same gallery
    $ismob = mobc();
    $o = 0;
    $imit = 0;
    if($ismob == false){
        $imit = 9;
        $lmitS = 9;
    }else{
        $imit = 6;
        $lmitS = 6;
    }
	$samegp = "<div class='samegpdiv'><p style='margin-top: 0;' class='txtc'>Suggested photos from friends</p><div class='flexibleSol' id='photSamegp'>";
	$sql = "SELECT * FROM photos WHERE gallery = ? AND user = ? AND filename != ? ORDER BY RAND() LIMIT $imit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$galgal,$u,$p);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		    $uder = $row["user"];
			$fname = $row["filename"];
			$description = $row["description"];
			$timed = $row["uploaddate"];
			$udp = strftime("%R, %b %d, %Y", strtotime($timed));
			$uds = time_elapsed_string($timed);
			if(strlen($description) > 12){
				$description = mb_substr($description, 0, 8, "utf-8");
				$description .= " ...";
			}
			$pcurl = '/user/'.$uder.'/'.$fname.'';
			list($width,$height) = getimagesize('user/'.$uder.'/'.$fname.'');
			$samegp .= "<a href='/photo_zoom/".urlencode($uder)."/".$fname."'><div class='pccanvas'><div data-src=\"".$pcurl."\" style='background-repeat: no-repeat; background-position: center; background-size: cover; height: 100px;' class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>".$width." x ".$height."</div></div></div></a>";
			$o++;
		}
	$stmt->close();
	}
	if($o < $imit){
	    $lmit = $imit - $o;
		$sql = "SELECT * FROM photos WHERE user IN('$pcallf') AND filename != ? ORDER BY RAND() LIMIT $lmitS";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$p);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
			    $uder = $row["user"];
				$fname = $row["filename"];
				$description = $row["description"];
				$timed = $row["uploaddate"];
				$udp = strftime("%R, %b %d, %Y", strtotime($timed));
				$uds = time_elapsed_string($timed);
				if(strlen($description) > 12){
					$description = mb_substr($description, 0, 8, "utf-8");
					$description .= " ...";
				}
				$pcurl = '/user/'.$uder.'/'.$fname.'';
				list($width,$height) = getimagesize('user/'.$uder.'/'.$fname.'');
				$samegp .= "<a href='/photo_zoom/".urlencode($uder)."/".$fname."'><div class='pccanvas'><div data-src=\"".$pcurl."\" style='background-repeat: no-repeat; background-position: center; background-size: cover; height: 100px;' class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>".$width." x ".$height."</div></div></div></a>";
				$o++;
			}
		}
	}
		if($o < $imit){
		    $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("s",$u);
    		$stmt->execute();
    		$stmt->bind_result($mylat,$mylon);
    		$stmt->fetch();
    		$stmt->close();
    		$lat_m2 = $mylat-0.7;
    		$lat_p2 = $mylat+0.7;
    
    		$lon_m2 = $mylon-0.7;
    		$lon_p2 = $mylon+0.7;
		    $lmit = 9 - $o;
    		$sql = "SELECT u.*, p.* FROM photos AS p LEFT JOIN users AS u ON u.username = p.user WHERE p.user NOT IN ('$all_friends') AND u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND p.user != ? $lmitS";
    		$stmt->bind_param("sssss",$lat_m2,$lat_p2,$lon_m2,$lon_p2,$log_username);
    		$stmt->execute();
    		$result = $stmt->get_result();
    		if($result->num_rows > 0){
    			while($row = $result->fetch_assoc()){
    			    $uder = $row["user"];
    				$fname = $row["filename"];
    				$description = $row["description"];
    				$timed = $row["uploaddate"];
    				$udp = strftime("%R, %b %d, %Y", strtotime($timed));
    				$uds = time_elapsed_string($timed);
    				if(strlen($description) > 12){
    					$description = mb_substr($description, 0, 8, "utf-8");
    					$description .= " ...";
    				}
    				$pcurl = '/user/'.$uder.'/'.$fname.'';
    				list($width,$height) = getimagesize('user/'.$uder.'/'.$fname.'');
    				$samegp .= "<a href='/photo_zoom/".urlencode($uder)."/".$fname."'><div class='pccanvas'><div data-src=\"".$pcurl."\" style='background-repeat: no-repeat; background-position: center; background-size: cover; height: 100px;' class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>".$width." x ".$height."</div></div></div></a>";
    				$o++;
    			}
    		}
		}

	$samegp .= "</div></div>";

	if($samegp == "<div class='samegpdiv'><p>Other photos from the same gallery, from friends & recently uploaded</p></div>"){
		$samegp = '<i style="font-size: 14px;">Unfortunately, we could not list any photos for you from the same gallery ...</i><br>';
	}

	// Get that certain photo
	$big_photo = "";
	$gallery = "";
	$sql = "SELECT * FROM photos WHERE filename=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$p);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$id = $row["id"];
			$uder = $row["user"];
			$gallery = $row["gallery"];
			$description = $row["description"];
			if($description == NULL){
				$description = "No description given";
			}
			
			$shareButton = '';
			if($_SESSION["username"] != "" && $u != $log_username){
			    $shareButton = '<span class="photoBigBottom">
						<img src="/images/black_share.png" style="width: 20px; height: 20px; vertical-align: middle;" onclick="sharePhoto(\''.$id.'\')">
						<span style="vertical-align: middle;">Share</span>
					</span>';
			}
			
			$uploaddate_ = $row["uploaddate"];
			$uploaddate = strftime("%b %d, %Y", strtotime($uploaddate_));
			$agoform = time_elapsed_string($uploaddate_);
			if($log_username == $uder){
				$vdelete_btn = '<span class="photoBigBottom">
					<a onclick="deletePhoto(\''.$id.'\')">
						<img src="/images/dildel.png" width="18" height="18" style="vertical-align: middle;">
					</a>
					<span style="vertical-align: middle;">Delete</span>
				</span>';
			}
			list($width,$height) = getimagesize('user/'.$u.'/'.$p.'');
			$big_photo .= '<div id="big_photo_holder" class="styleform" style="width: 100%; background-color: #fff;">
				<div class="innerImage">
					<img src="/user/'.$u.'/'.$p.'" onclick="openImgBig(\'/user/'.$u.'/'.$p.'\')"/>
				</div>
				'.$samegp.'<div class="clear"></div><br>

				<div class="collection" id="ccSu" style="border-top: 1px solid rgba(0, 0, 0, 0.1); text-align: center; margin-bottom: 20px;">
			      <p style="font-size: 16px; margin-left: 23px;" id="signup">Photo Properties</p>
			      <img src="/images/alldd.png">
			    </div>

				<div class="slideInfo" id="suDD" style="text-align: center;"><p><b>Published by: </b><a href="/user/'.$uder.'/">'.$uder.'</a></p><p style="word-break: break-all;"><b>Description: </b>'.$description.'</p><p><b>Upload date: </b>'.$uploaddate.' | '.$agoform.' ago</p><p><b>Gallery: </b>'.$gallery.'</p></div><div class="clear"></div>

				<div style="height: 35px;" class="flexibleSol">
					'.$shareButton.'

					<span class="photoBigBottom">
						<a href="/photos/'.$log_username.'">
							<img src="/images/pback.png" width="18" height="18" style="vertical-align: middle;">
						</a>
						<span style="vertical-align: middle;">Back</span>
					</span>

					'.$vdelete_btn.'
				</div>
				<div class="clear"></div>
				<span id="info_stat"></span>
			</div>';
		}
	}else{
		header('Location: /index');
	}

	$stmt->close();
	
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

	$isOwner = "no";
	if($u == $log_username && $user_ok == true){
		$isOwner = "yes";
	}

	// Count how many posts are there
	$sql = "SELECT COUNT(id) FROM photos_status WHERE photo = ? AND type = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$p,$a);
	$stmt->execute();
	$stmt->bind_result($post_count);
	$stmt->fetch();
	$stmt->close();

	// Count how many replies are there
	$sql = "SELECT COUNT(id) FROM photos_status WHERE photo = ? AND type = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$p,$b);
	$stmt->execute();
	$stmt->bind_result($reply_count);
	$stmt->fetch();
	$stmt->close();

	// Count how many status are there
	$sql = "SELECT COUNT(id) FROM photos_status WHERE photo = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$p);
	$stmt->execute();
	$stmt->bind_result($all_count);
	$stmt->fetch();
	$stmt->close();

	// Get related photos
	// FIRST GET FRIENDS ARRAY
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
	$allfmy = join("','", $all_friends);
	$related_p = "";
	$nof = false;
	if($allfmy == ""){
	    $nof = true;
	}
	$sql = "SELECT * FROM photos WHERE user IN ('$allfmy') ORDER BY RAND() LIMIT $lmitS";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$countRels++;
			$uploader = $row["user"];
		    $uploaderori = urlencode($uploader);
		    $upim = $uploader;
			$gallery_u = $row["gallery"];
			$filename_u = $row["filename"];
			$description_u = $row["description"];
			$uploaddate_u_ = $row["uploaddate"];
			$uploaddate_u = strftime("%R, %b %d, %Y", strtotime($uploaddate_u_));

			list($width, $height) = getimagesize('user/' . $upim . '/' . $filename_u . '');

			$pcurl = '/user/'.$upim.'/'.$filename_u.'';
			$uds = time_elapsed_string($uploaddate_u_);
			$related_p .= "<a href='/photo_zoom/" . $uploaderori . "/" . $filename_u . "'><div class='pccanvas' style='width: 100%;'><div  data-src=\"".$pcurl."\" class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
		}
	}else{
		$sql = "SELECT * FROM photos WHERE user != ? ORDER BY RAND() LIMIT $lmitS";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$u);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$countRels++;
			$uploader = $row["user"];
		    $uploaderori = urlencode($uploader);
		    $upim = $uploader;
			$gallery_u = $row["gallery"];
			$filename_u = $row["filename"];
			$description_u = $row["description"];
			$uploaddate_u_ = $row["uploaddate"];
			$uploaddate_u = strftime("%R, %b %d, %Y", strtotime($uploaddate_u_));
			list($width, $height) = getimagesize('user/' . $upim . '/' . $filename_u . '');

			$pcurl = '/user/'.$upim.'/'.$filename_u.'';
			$uds = time_elapsed_string($uploaddate_u_);
			$related_p .= "<a href='/photo_zoom/" . $uploaderori . "/" . $filename_u . "'><div class='pccanvas' style='width: 100%;'><div data-src=\"".$pcurl."\" class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
		}
	}
	$stmt->close();

	// Get users's other photos
	$minep = "";
	$sql = "SELECT * FROM photos WHERE filename != ? AND user = ? ORDER BY uploaddate DESC LIMIT $lmitS";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$p,$log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$countMine++;
		$uploader = $row["user"];
		$uploaderori = urlencode($uploader);
		$upmim = $uploader;
		$gallery_u = $row["gallery"];
		$filename_u = $row["filename"];
		$description_u = $row["description"];
		$uploaddate_u_ = $row["uploaddate"];
		$uploaddate_u = strftime("%R, %b %d, %Y", strtotime($uploaddate_u_));
		
		$uds = time_elapsed_string($uploaddate_u_);
		
		if($description_u == NULL){
		    $description_u = "No description";    
		}
		$pcurl = '/user/'.$upmim.'/'.$filename_u.'';

		list($width, $height) = getimagesize('user/' . $upmim . '/' . $filename_u . '');
		
		$minep .= "<a href='/photo_zoom/" . $uploaderori . "/" . $filename_u . "'><div class='pccanvas' style='width: 100%;'><div data-src=\"".$pcurl."\" class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
	}
	$stmt->close();

    $isRP = true;
	if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
		$related_p = '<p style="color: #999;" class="txtc">You need to be <a href="/login">logged in</a> in order to see related photos</p>';
		$minep = '<p style="color: #999;" class="txtc">You need to be <a href="/login">logged in</a> in order to see your photos</p>';
		$isRP = false;
	}

    $temp = false;
	if($minep == ""){
		$minep = '<i style="font-size: 14px;">Unfortunately, you have no other listable photos uploaded. Come back later and upload new photos to your gallery ...</i>';
		$temp = true;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta lang="en">
	<title><?php echo $u; ?> - <?php echo $gallery_u; ?></title>
	<link rel="icon" href="/images/newfav.png" type="image/x-icon">
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<link rel="stylesheet" href="/style/style.css">
	<meta name="description" content="Check <?php echo $u; ?>'s photo, comment & share your opinion and watch several other related photos as well, all on Pearscom.">
    <meta name="keywords" content="pearscom photos, pearscom gallery, pearscom galleries, pearscom photo, pearscom <?php echo $u; ?>, pearscom <?php  echo $gallery_u; ?>, photo big view pearscom, big, view, big view">
    <meta name="author" content="Pearscom">
	<script type="text/javascript">
		function deletePhoto(o) {
    if (1 != confirm("Are you sure you want to delete this photo? Be careful because we will not be able to bring back or reset it!")) return !1;
    var e = _("info_stat"),
        t = ajaxObj("POST", "/php_parsers/delete_photo.php");
    t.onreadystatechange = function() {
        1 == ajaxReturn(t) && ("delete_photo_success" != t.responseText ? e.innerHTML = t.responseText : e.innerHTML = "<p style='font-size: 14px !important;'>You have successfully deleted this photo!</p>")
    }, t.send("id=" + o)
}

function sharePhoto(o) {
    var e = ajaxObj("POST", "/php_parsers/status_system.php");
    e.onreadystatechange = function() {
        1 == ajaxReturn(e) && ("share_photo_ok" == e.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Share this photo</p><p>You have successfully shared this photo which will be visible on your main profile page in the comment section.</p><button id="vupload" style="float: right;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately the photo sharing has failed. Please try again later.</p><button id="vupload" style="float: right;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
    }, e.send("action=share_photo&id=" + o)
}

function openImgBig(o) {
    _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<img src="' + o + '"><button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
}

function closeDialog() {
    _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto"
}

function deletePhoto(o) {
    if (1 != confirm("Are you sure you want to delete this photo? Be careful because we will not be able to bring back or reset it!")) return !1;
    var e = _("info_stat"),
        t = ajaxObj("POST", "/php_parsers/delete_photo.php");
    t.onreadystatechange = function() {
        1 == ajaxReturn(t) && ("delete_photo_success" != t.responseText ? e.innerHTML = t.responseText : e.innerHTML = "<p style='font-size: 14px !important;'>You have successfully deleted this photo!</p>")
    }, t.send("id=" + o)
}

function sharePhoto(o) {
    var e = ajaxObj("POST", "/php_parsers/status_system.php");
    e.onreadystatechange = function() {
        1 == ajaxReturn(e) && ("share_photo_ok" == e.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Share this photo</p><p>You have successfully shared this photo which will be visible on your main profile page in the comment section.</p><button id="vupload" style="float: right;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately the photo sharing has failed. Please try again later.</p><button id="vupload" style="float: right;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
    }, e.send("action=share_photo&id=" + o)
}

function openImgBig(o) {
    _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<img src="' + o + '"><button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
}

function closeDialog() {
    _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto"
}
	</script>
</head>
<body style="overflow-x: hidden;">
	<?php require_once 'template_pageTop.php'; ?>
	<div id="overlay"></div>
	<div id="pageMiddle_2">
		<div id="dialogbox"></div>

		<div id="imagefloat">
			<?php echo $big_photo; ?>
		</div>
		<div id="data_holder">
            <div>
                <div><span><?php echo $countRels; ?></span> related photos</div>
            </div>
        </div>
        <div id="userFlexArts" class="flexibleSol mainPhotRel">
            <?php echo $related_p; ?>
        </div>
        <div class="clear"></div>
        <hr class="dim">
		<div id="data_holder">
            <div>
                <div><span><?php echo $countMine; ?></span> photos of mine</div>
            </div>
        </div>
        <div id="userFlexArts" class="flexibleSol mainPhotRel">
            <?php echo $minep; ?>
        </div>
        <div class="clear"></div>
        <hr class="dim">
		<?php if($isBlock != true){ ?><?php require_once 'photos_status.php'; ?><?php }else{ ?><p style="font-size: 14px; color: #ffd11a;"><p style="color: #006ad8;" class="txtc">Alert: this user blocked you, therefore you cannot post on his/her photos!</p><?php } ?>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
		        function getCookie(e) {
	        for (var o = e + "=", t = decodeURIComponent(document.cookie).split(";"), l = 0; l < t.length; l++) {
	            for (var i = t[l];
	                " " == i.charAt(0);) i = i.substring(1);
	            if (0 == i.indexOf(o)) return i.substring(o.length, i.length)
	        }
	        return ""
	    }

	    function setDark() {
	        var e = "thisClassDoesNotExist";
	        if (!document.getElementById(e)) {
	            var o = document.getElementsByTagName("head")[0],
	                t = document.createElement("link");
	            t.id = e, t.rel = "stylesheet", t.type = "text/css", t.href = "/style/dark_style.css", t.media = "all", o.appendChild(t)
	        }
	    }
	    var isdarkm = getCookie("isdark");
	    "yes" == isdarkm && setDark();	

	    function doDD(first, second){
		    $( "#" + first ).click(function() {
		      $( "#" + second ).slideToggle( "fast", function() {
		        
		      });
		    });
		  }

		  doDD("ccSu", "suDD");
	</script>
</body>
</html>
