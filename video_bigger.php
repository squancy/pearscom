<?php
	include_once("php_includes/check_login_statues.php");
	require_once 'timeelapsedstring.php';
	require_once 'safe_encrypt.php';
	require_once 'durc.php';
	require_once 'phpmobc.php';
	require_once 'headers.php';

	// Make sure the _GET "v" is set, and sanitize it
	$id = "";
	$one = "1";
	if(isset($_GET['id']) && $_GET['id'] != ""){
		$id = $_GET['id'];
	}else{
		header('Location: /index');
	}
	
	if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
	    $fff = $_SESSION["username"];
	    $sql = "SELECT id FROM users WHERE username = ? AND activated = ? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss",$fff,$one);
	    $stmt->execute();
	    $stmt->bind_result($isit);
	    $stmt->execute();
	    $stmt->fetch();
	    $stmt->close();
	    if($isit == "" || $isit == NULL){
	        header('location: /usernotexist');
	    }
	}else{
	    header('locations: /needlogged');
	}
	
	$u = "";
	$rcs = "";
	$description = "";
	$upload = "";
	$agofrom = "";

	$_SESSION["id"] = $id;
	$ec = $id;
	$id = base64url_decode($id,$hshkey);
	$id = preg_replace('/\D/', '', $id);
	
	$sql = "SELECT COUNT(id) FROM video_likes WHERE video = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$id);
	$stmt->execute();
	$stmt->bind_result($countvl);
	$stmt->fetch();
	$stmt->close();
	
	if($countvl == NULL || $countvl == ""){
	    $countvl = 0;
	}
	
	$a = "a";
	$b = "b";
	$c = "c";
	$sql = "SELECT COUNT(id) FROM video_status WHERE vidid = ? AND (type = ? OR type = ?)";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("iss",$id,$a,$c);
	$stmt->execute();
	$stmt->bind_result($countstat);
	$stmt->fetch();
	$stmt->close();
	
	if($countstat == NULL || $countstat == ""){
	    $countstat = 0;
	}
	
	$sql = "SELECT COUNT(id) FROM video_status WHERE vidid = ? AND type = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("is",$id,$b);
	$stmt->execute();
	$stmt->bind_result($countrply);
	$stmt->fetch();
	$stmt->close();
	
	if($countrply == NULL || $countrply == ""){
	    $countrply = 0;
	}
	
	$allp = $countstat+$countrply;
	
	// Get today's most liked videos
	$ismob = mobc();
	if($ismob == false){
	    $max = 6;
	}else{
	    $max = 4;
	}
	$sql = "SELECT video, COUNT(*) AS u 
            FROM video_likes
            WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL - 1 DAY)
            GROUP BY video";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    $numrows = $stmt->num_rows();
    $stmt->close();
    $days = 0;
    $bHolder = "";
    if($numrows > 0){
        $days = 1;
        $bHolder = "Most liked videos of the day";
    }else{
        $days = 7;
        $bHolder = "Most liked videos of the week";
    }
	$bestvids = "";
	$sql = "SELECT video, COUNT(*) AS u 
            FROM video_likes
            WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL - $days DAY)
            GROUP BY video
            ORDER BY u DESC
            LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        while($row = $res->fetch_assoc()){
            $id_o = $row["video"];
            $id_hsh = base64url_encode($id_o,$hshkey);
            $vdate_ = $row["video_upload"];
            $sql = "SELECT video_poster, user, dur, video_name FROM videos WHERE id=? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$id_o);
            $stmt->execute();
            $stmt->bind_result($vidp,$vidu,$dur,$vidn);
            $stmt->fetch();
            $stmt->close();
            $prs = "";
            if($vidp != NULL){
            	$prs = '/user/'.$vidu.'/videos/'.$vidp.'';
            }else{
            	$prs = '/user/defaultimage.png';
            }
            $dur = convDur($dur);
            if($vidn == ""){
    			$vidn = "Untitled";
    		}

            $bestvids .= "<a href='/video_zoom/" . $id_hsh . "'><div class='nfrelv vBigDown' style='white-space: nowrap;'><div id='pcgetc' data-src=\"".$prs."\" class='mainVids lazy-bg'></div><div class='pcjti'>" . $vidn . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
        }
        $stmt->close();
    }else{
    	$bHolder = "Trending videos of all time";
    	$sql = "SELECT video, COUNT(*) AS u 
            FROM video_likes
            GROUP BY video
            ORDER BY u DESC
            LIMIT $max";
	    $stmt = $conn->prepare($sql);
	    $stmt->execute();
	    $res = $stmt->get_result();
	    if($res->num_rows > 0){
	        while($row = $res->fetch_assoc()){
	        	$id_o = $row["video"];
	        	$vdate_ = $row["video_upload"];
	            $id_hsh = base64url_encode($id_o,$hshkey);
	            $sql = "SELECT video_poster, user, dur, video_name FROM videos WHERE id=? LIMIT 1";
	            $stmt = $conn->prepare($sql);
	            $stmt->bind_param("s",$id_o);
	            $stmt->execute();
	            $stmt->bind_result($vidp,$vidu,$dur,$vidn);
	            $stmt->fetch();
	            $stmt->close();
	            $prs = "";
	            if($vidp != NULL){
	            	$prs = '/user/'.$vidu.'/videos/'.$vidp.'';
	            }else{
	            	$prs = '/user/defaultimage.png';
	            }
	            $dur = convDur($dur);
	            if($vidn == NULL){
	    			$vidn = "Untitled";
	    		}

	            $bestvids .= "<a href='/video_zoom/" . $id_hsh . "'><div class='nfrelv vBigDown' style='white-space: nowrap;'><div id='pcgetc' data-src=\"".$prs."\" class='mainVids lazy-bg'></div><div class='pcjti'>" . $vidn . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
	        }
	    }else{
	    	$bestvids = '<p style="color: #999;" class="txtc">It seems that there are no videos fitting the criteria</p>';
	    }
    }

	// Get that certain video
	$big_vid = "";
	$poster = "";
	$video_name = "";
	$sql = "SELECT * FROM videos WHERE id = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i",$id);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$rcs = $row["id"];
			$id_number = $rcs;
			$user = $row["user"];
			$video_file = $row["video_file"];
			$video_name = $row["video_name"];
			$description = $row["video_description"];
			$poster = $row["video_poster"];
			$upload_ = $row["video_upload"];
			$upload = strftime("%b %d, %Y", strtotime($upload_));
			$u = $row["user"];
			$pDur = $row["dur"];
			$dur = convDur($row["dur"]);
			if($poster == "" || $poster == NULL){
				$poster = "/images/defaultimage.png";
			}else{
				$poster = '/user/'.$u.'/videos/'.$poster.'';
			}
			if($description == ""){
				$description = "Untitled";
			}
			if($video_name == ""){
				$video_name = "Untitled";
			}
			// Get number of likes
    		$sql = "SELECT COUNT(id) FROM video_likes WHERE video=?";
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("i",$id_number);
    		$stmt->execute();
    		$stmt->bind_result($like_count);
    		$stmt->fetch();
    		$stmt->close();
    		$vc = "".$like_count;
			$isLike = false;
    		if($user_ok == true){
    			$like_check = "SELECT id FROM video_likes WHERE video = ? AND username = ? LIMIT 1";
    			$stmt = $conn->prepare($like_check);
    			$stmt->bind_param("is",$id_number, $log_username);
    			$stmt->execute();
    			$stmt->store_result();
    			$stmt->fetch();
    			$numrows = $stmt->num_rows;
    		if($numrows > 0){
    			    $isLike = true;
    			}
    
    		}
    		$hshid = base64url_encode($id_number,$hshkey);
    		$likeText = "";
    		if($isLike == true){
    			$likeButton = '<a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
    			$likeText = '<span style="vertical-align: middle;">Dislike</span>';
    		}else{
    			$likeButton = '<a href="#" onclick="return false;" onmousedown="likeVideo(\'like\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
    			$likeText = '<span style="vertical-align: middle;">Like</span>';
    		}

    		$shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="sharePhoto(\'' . $id_number . '\');" id="shareBlink" style="vertical-align: middle;">';

    		$dold = $description;
    		if(strlen($description) > 200){
    			$description = substr($description, 0, 200);
    			$description .= "...";
    			$description .= '<a onclick="showDes(\''.$dold.'\', \''.$description.'\')">Show more</a>';
    		}
    		
    		$dstr = "";
    		if($_SESSION["username"] != ""){
    		    $dstr = '<span id="likeBtnv_'.$hshid.'" class="likeBtn">
							'.$likeButton.'
							<span style="vertical-align: middle;">'.$likeText.'</span>
						</span>
						<div class="shareDiv">
			                ' . $shareButton . '
			                <span style="vertical-align: middle;">Share</span>
			            </div>';
    		}

    		$stmt->close();
			$sql = "SELECT avatar FROM users WHERE username = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$user);
			$stmt->execute();
			$stmt->bind_result($av);
			$stmt->fetch();
			$stmt->close();
			$avav = '/user/'.$user.'/'.$av.'';
			$agoform = time_elapsed_string($upload_);
			$big_vid = '<div id="big_holder" class="genWhiteHolder">

			<div class="vidHolderBig" id="videoContainer">
			<video width="100%" id="my_video_'.$ec.'" class="bigvidg" preload="metadata" id="my_video_'.$ec.'" poster="'.$poster.'">
				<source src="/user/'.$u.'/videos/'. $video_file.'">

              <p style="font-size: 14px;" class="txtc">Unfortunately, an error has occured during the video loading. Please refresh the page and if the video is still loaded, please visit our <a href="/help">help</a> page or send us a <a href="/help#report">problem report.</a> As for now, you can <a href="/user/'.$u.'/videos/'. $video_file.'" download>download</a> the video/auido file and play it on your computer or mobile device.</p>
              </video>
              <div class="lds-spinner" id="testl"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
            <div class="vControls">
            	<div class="tooltipVid" id="timeInd">
            	</div>
            	<div class="orangeBar" id="ob">
            		<div class="orangeJuice" id="oj"></div>
            		<div class="orangeJuicy"></div>
            		<div class="orangeGrey"></div>
            	</div>
            	<div class="vButtons" id="pcControls">
            		<button id="playPauseBtn" status="pause">
            			<img src="/images/pausebtn.svg" id="tgl1">
            			<span class="tooltipVidText" id="ppToggle">Play (p)</span>
            		</button>
            		<span id="volCont" style="display: flex;">
	            		<button id="muteBtn" status="sound">
	            			<img src="/images/mutebtn.svg" id="tgl2">
	            			<span class="tooltipVidText" id="muteToggle">Mute (m)</span>
	            		</button>
	            		<div class="volSlider" id="volSlider">
	            			<input type="range" id="vChange" style="display: none;" min="0" max="100">
	            		</div>
	            		<div id="timeData" class="timeData">
	            			<div id="curtime">00:00 /</div>&nbsp;
	            			<div id="duration">'.$dur.'</div>
	            		</div>
	            	</span>

	            	<div class="vRight">
	            		<button id="optionsGears">
	            			<img src="/images/gearsbtn.svg">
	            			<span class="tooltipVidText" id="optionsToggle">Options (o)</span>
	            		</button>
	            		<button id="fullScreen">
	            			<img src="/images/fullsrc.svg">
	            			<span class="tooltipVidText" id="fsToggle">Fullscreen (f)</span>
	            		</button>
	            	</div>

	            	<div id="optionsMenu">
	            		<div>Speed</div>
	            		<div onclick="changeSpeed(0.25)">0.25</div>
	            		<div onclick="changeSpeed(0.5)">0.5</div>
	            		<div onclick="changeSpeed(0.75)">0.75</div>
	            		<div onclick="changeSpeed(1)">Normal</div>
	            		<div onclick="changeSpeed(1.25)">1.25</div>
	            		<div onclick="changeSpeed(1.5)">1.5</div>
	            		<div onclick="changeSpeed(1.75)">1.75</div>
	            	</div>
            	</div>
            </div>

            </div>

            <div class="clear"></div>

			<div class="clear"></div>
			<div>
				<div>
					<p class="shtrp">'.$video_name.'</p>
					<div style="float: left;">
						<p class="greyP" id="ipanr_' . $hshid . '">'.$like_count.' likes</p>
					</div>
					<div style="float: right; margin-top: 10px;">
						'.$dstr.'
			        </div>
				</div>
	        </div>
			<div class="clear"></div>
			<hr class="dim">
			<a href="/user/'.$user.'/">
				<div style="background-image: url(\''.$avav.'\'); width: 40px; height: 40px; float: left; border-radius: 50%;" class="genBg"></div>
			</a>
			&nbsp;&nbsp;&nbsp;
			<b style="display: inline-block; margin-bottom: -10px; vertical-align: middle;">'.$user.'</b>
			<br>
			&nbsp;&nbsp;&nbsp;
			<b style="display: inline-block; font-size: 12px; color: #999; margin-bottom: -10px; vertical-align: middle;">Published on '.$upload.' ('.$agoform.' ago)
			</b>
			<div class="clear"></div>
			<p style="font-size: 14px; margin-bottom: 0px;" id="shDes">'.$description.'</p>
			<div class="clear"></div>
		</div><hr class="dim shHr">';
		}
	}else{
		header('location: /videonotexist');
		exit();
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
	
	if($_SESSION["username"] != "" && isset($_SESSION["username"])){
	    // Select the member from the users table
        $one = "1";
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
	}

	// Get related videos
	// FIRST GET USERS'S FRIENDS
	$all_friends = array();
	$sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$one);
	$stmt->execute();
	$result2 = $stmt->get_result();
	while ($row = $result2->fetch_assoc()) {
		array_push($all_friends, $row["user1"]);
	}
	$stmt->close();

	$sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$one);
	$stmt->execute();
	$result3 = $stmt->get_result();
	while ($row = $result3->fetch_assoc()) {
		array_push($all_friends, $row["user2"]);
	}
	$stmt->close();
	// Implode all friends array into a string
	$allfmy = join("','", $all_friends);
	$related_vids = "";
	$sql = "SELECT * FROM videos WHERE user IN ('$allfmy') LIMIT 30";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$vid = $row["id"];
		$vid = base64url_encode($vid,$hshkey);
		$vuser = $row["user"];
		$vvname = $row["video_name"];
		$vdescription = $row["video_description"];
		$vposter = $row["video_poster"];
		$vfile = $row["video_file"];
		$vdate_ = $row["video_upload"];
		$vdate = strftime("%b %d, %Y", strtotime($vdate_));

		$curdate = date("Y-m-d");
    	$ud = mb_substr($vdate_, 0,10, "utf-8");

		$pcurl = "";
		if($vposter == NULL){
			$pcurl = '/images/defaultimage.png';
		}else{
			$pcurl = '/user/'.$vuser.'/videos/'.$vposter.'';
		}
		$uds = time_elapsed_string($vdate_);
		if($vvname == NULL){
		    $vvname = "Untitled";
		}
		if($vdescription == NULL){
		    $vdescription = "No description";
		}
		$related_vids .= "<a href='/video_zoom/" . $vid . "'><div class='nfrelv vBigDown' style='white-space: nowrap;'><div id='pcgetc' data-src=\"".$pcurl."\" class='mainVids lazy-bg'></div><div class='pcjti'>" . $vvname . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
	}
	$stmt->close();
	
	if(empty($all_friends)){
		$sql = "SELECT * FROM videos LIMIT 30";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$vid = $row["id"];
			$vid = base64url_encode($vid,$hshkey);
			$vuser = $row["user"];
			$vvname = $row["video_name"];
			$vdescription = $row["video_description"];
			$vposter = $row["video_poster"];
			$vfile = $row["video_file"];
			$vdate_ = $row["video_upload"];
			$vdate = strftime("%b %d, %Y", strtotime($vdate_));
			$dur = convDur($row["dur"]);

			$curdate = date("Y-m-d");
        	$ud = mb_substr($vdate_, 0,10, "utf-8");

			$pcurl = "";
			if($vposter == NULL){
				$pcurl = '/images/defaultimage.png';
			}else{
				$pcurl = '/user/'.$vuser.'/videos/'.$vposter.'';
			}
			$uds = time_elapsed_string($vdate_);
			if($vvname == NULL){
			    $vvname = "Untitled";
			}
			if($vdescription == NULL){
			    $vdescription = "No description";
			}
			$related_vids .= "<a href='/video_zoom/" . $vid . "'><div class='nfrelv vBigDown' style='white-space: nowrap;'><div id='pcgetc' data-src=\"" . $pcurl . "\" class='mainVids lazy-bg'></div><div class='pcjti'>" . $vvname . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
		}
		$stmt->close();
	}

	// Get users's videos
	$myvids = "";
	$sql = "SELECT * FROM videos WHERE user = ? LIMIT 30";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$vid_ = $row["id"];
		$vid_ = base64url_encode($vid_,$hshkey);
		$vuser_ = $row["user"];
		$vvname_ = $row["video_name"];
		$vdescription_ = $row["video_description"];
		$vposter_ = $row["video_poster"];
		$vfile_ = $row["video_file"];
		$vdate__ = $row["video_upload"];
		$vdate_ = strftime("%b %d, %Y", strtotime($vdate__));
		$dur = convDur($row["dur"]);

		$curdate = date("Y-m-d");
        $ud = mb_substr($vdate__, 0,10, "utf-8");

		$pcurl = "";
		if($vposter_ == NULL){
			$pcurl = '/images/defaultimage.png';
		}else{
			$pcurl = '/user/'.$log_username.'/videos/'.$vposter_.'';
		}
		
		if($vvname_ == NULL){
		    $vvname_ = "Untitled";
		}
		if($vdescription_ == NULL){
		    $vdescription_ = "No description";
		}
		
		$uds = time_elapsed_string($vdate_);

		$myvids .= "<a href='/video_zoom/" . $vid_ . "'><div class='nfrelv vBigDown' style='white-space: nowrap;'><div id='pcgetc' data-src=\"".$pcurl."\" class='mainVids lazy-bg'></div><div class='pcjti'>" . $vvname_ . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
	}
	$stmt->close();
	
	$ismyv = false;
	$isrel = false;
	
	if($myvids == ""){
	    $myvids = '<p style="color: #999;" class="txtc">It seems that you have not uploaded any videos so far</p>';
	    $ismyv = true;
	}
	
	if($related_vids == ""){
	    $related_vids = '<p style="color: #999;" class="txtc">It seems that there are no listable related videos</p>';
	    $isrel = true;
	}

	if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
		$related_vids = "<p style='color: #999;' class='txtc'>Please <a href='/login'>log in</a> in order to see related videos</p>";
		$myvids = "<p style='color: #999;' class='txtc'>Please <a href='/login'>log in</a> in order to see your videos</p>";
		$isrel = true;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $video_name; ?></title>
	<meta charset="utf-8">
	<meta lang="en">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Watch <?php echo $video_name; ?>">
    <meta name="keywords" content="pearscom video, <?php echo $video_name; ?>, <?php echo $video_name; ?> video, <?php echo $video_name; ?> pearscom video, <?php echo $u; ?> video pearscom, video pearscom">
    <meta name="author" content="Pearscom">
	<script src="/js/jjs.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script src="/js/mbc.js"></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script src="/js/lload.js"></script>
	<script type="text/javascript">
	var _0x9da3=["\x6D\x79\x5F\x76\x69\x64\x65\x6F\x5F","\x70\x6C\x61\x79\x70\x61\x75\x73\x65\x62\x74\x6E\x5F","\x73\x65\x65\x6B\x73\x6C\x69\x64\x65\x72\x5F","\x63\x75\x72\x74\x69\x6D\x65\x74\x65\x78\x74\x5F","\x64\x75\x72\x74\x69\x6D\x65\x74\x65\x78\x74\x5F","\x6D\x75\x74\x65\x62\x74\x6E\x5F","\x76\x6F\x6C\x75\x6D\x65\x73\x6C\x69\x64\x65\x72\x5F","\x66\x75\x6C\x6C\x73\x63\x72\x62\x74\x6E\x5F","\x73\x65\x74\x74\x69\x6E\x67\x73\x5F","\x76\x6F\x6C\x75\x6D\x65\x73\x6C\x69\x64\x65\x72\x5F\x73\x6D\x5F","\x63\x68\x61\x6E\x67\x65","\x61\x64\x64\x45\x76\x65\x6E\x74\x4C\x69\x73\x74\x65\x6E\x65\x72","\x74\x69\x6D\x65\x75\x70\x64\x61\x74\x65","\x63\x6C\x69\x63\x6B","\x72\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x73\x63\x72\x65\x65\x6E","\x77\x65\x62\x6B\x69\x74\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x53\x63\x72\x65\x65\x6E","\x6D\x6F\x7A\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x53\x63\x72\x65\x65\x6E","\x6D\x73\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x73\x63\x72\x65\x65\x6E","\x70\x61\x75\x73\x65\x64","\x70\x6C\x61\x79","\x64\x69\x73\x70\x6C\x61\x79","\x73\x74\x79\x6C\x65","\x74\x65\x78\x74\x5F","\x6E\x6F\x6E\x65","\x70\x61\x75\x73\x65","\x62\x6C\x6F\x63\x6B","\x70\x6C\x61\x79\x62\x61\x63\x6B\x52\x61\x74\x65","\x73\x65\x74\x74\x69\x6E\x67\x73\x5F\x6D\x65\x6E\x75\x5F\x76\x62","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x70\x61\x75\x73\x65\x62\x74\x6E\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x3E","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x70\x6C\x61\x79\x62\x74\x6E\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x3E","\x64\x75\x72\x61\x74\x69\x6F\x6E","\x76\x61\x6C\x75\x65","\x63\x75\x72\x72\x65\x6E\x74\x54\x69\x6D\x65","\x66\x6C\x6F\x6F\x72","\x30","\x3A","\x6D\x75\x74\x65\x64","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x6E\x6D\x75\x74\x65\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x20\x69\x64\x3D\x27\x6D\x75\x74\x65\x62\x69\x67\x67\x65\x72\x27\x3E","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x6D\x75\x74\x65\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x39\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x39\x27\x20\x69\x64\x3D\x27\x6D\x75\x74\x65\x62\x69\x67\x67\x65\x72\x27\x3E","\x76\x6F\x6C\x75\x6D\x65","\x50\x4F\x53\x54","\x2F\x70\x68\x70\x5F\x70\x61\x72\x73\x65\x72\x73\x2F\x73\x74\x61\x74\x75\x73\x5F\x73\x79\x73\x74\x65\x6D\x2E\x70\x68\x70","\x6F\x6E\x72\x65\x61\x64\x79\x73\x74\x61\x74\x65\x63\x68\x61\x6E\x67\x65","\x72\x65\x73\x70\x6F\x6E\x73\x65\x54\x65\x78\x74","\x73\x68\x61\x72\x65\x5F\x76\x69\x64\x65\x6F\x5F\x6F\x6B","\x6F\x76\x65\x72\x6C\x61\x79","\x6F\x70\x61\x63\x69\x74\x79","\x64\x69\x61\x6C\x6F\x67\x62\x6F\x78","\x3C\x70\x20\x73\x74\x79\x6C\x65\x3D\x22\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x38\x70\x78\x3B\x20\x6D\x61\x72\x67\x69\x6E\x3A\x20\x30\x70\x78\x3B\x22\x3E\x53\x68\x61\x72\x65\x20\x74\x68\x69\x73\x20\x76\x69\x64\x65\x6F\x3C\x2F\x70\x3E\x3C\x70\x3E\x59\x6F\x75\x20\x68\x61\x76\x65\x20\x73\x75\x63\x63\x65\x73\x73\x66\x75\x6C\x6C\x79\x20\x73\x68\x61\x72\x65\x64\x20\x74\x68\x69\x73\x20\x76\x69\x64\x65\x6F\x20\x77\x68\x69\x63\x68\x20\x77\x69\x6C\x6C\x20\x62\x65\x20\x76\x69\x73\x69\x62\x6C\x65\x20\x6F\x6E\x20\x79\x6F\x75\x72\x20\x6D\x61\x69\x6E\x20\x70\x72\x6F\x66\x69\x6C\x65\x20\x70\x61\x67\x65\x20\x69\x6E\x20\x74\x68\x65\x20\x63\x6F\x6D\x6D\x65\x6E\x74\x20\x73\x65\x63\x74\x69\x6F\x6E\x2E\x3C\x2F\x70\x3E\x3C\x62\x75\x74\x74\x6F\x6E\x20\x69\x64\x3D\x22\x76\x75\x70\x6C\x6F\x61\x64\x22\x20\x73\x74\x79\x6C\x65\x3D\x22\x70\x6F\x73\x69\x74\x69\x6F\x6E\x3A\x20\x61\x62\x73\x6F\x6C\x75\x74\x65\x3B\x20\x72\x69\x67\x68\x74\x3A\x20\x33\x70\x78\x3B\x20\x62\x6F\x74\x74\x6F\x6D\x3A\x20\x33\x70\x78\x3B\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x44\x69\x61\x6C\x6F\x67\x28\x29\x22\x3E\x43\x6C\x6F\x73\x65\x3C\x2F\x62\x75\x74\x74\x6F\x6E\x3E","\x6F\x76\x65\x72\x66\x6C\x6F\x77","\x62\x6F\x64\x79","\x68\x69\x64\x64\x65\x6E","\x3C\x70\x20\x73\x74\x79\x6C\x65\x3D\x22\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x38\x70\x78\x3B\x20\x6D\x61\x72\x67\x69\x6E\x3A\x20\x30\x70\x78\x3B\x22\x3E\x41\x6E\x20\x65\x72\x72\x6F\x72\x20\x68\x61\x73\x20\x6F\x63\x63\x75\x72\x65\x64\x3C\x2F\x70\x3E\x3C\x70\x3E\x55\x6E\x66\x6F\x72\x74\x75\x6E\x61\x74\x65\x6C\x79\x20\x74\x68\x65\x20\x76\x69\x64\x65\x6F\x20\x73\x68\x61\x72\x69\x6E\x67\x20\x68\x61\x73\x20\x66\x61\x69\x6C\x65\x64\x2E\x20\x50\x6C\x65\x61\x73\x65\x20\x74\x72\x79\x20\x61\x67\x61\x69\x6E\x20\x6C\x61\x74\x65\x72\x2E\x3C\x2F\x70\x3E\x3C\x62\x75\x74\x74\x6F\x6E\x20\x69\x64\x3D\x22\x76\x75\x70\x6C\x6F\x61\x64\x22\x20\x73\x74\x79\x6C\x65\x3D\x22\x70\x6F\x73\x69\x74\x69\x6F\x6E\x3A\x20\x61\x62\x73\x6F\x6C\x75\x74\x65\x3B\x20\x72\x69\x67\x68\x74\x3A\x20\x33\x70\x78\x3B\x20\x62\x6F\x74\x74\x6F\x6D\x3A\x20\x33\x70\x78\x3B\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x44\x69\x61\x6C\x6F\x67\x28\x29\x22\x3E\x43\x6C\x6F\x73\x65\x3C\x2F\x62\x75\x74\x74\x6F\x6E\x3E","\x6C\x6F\x67","\x61\x63\x74\x69\x6F\x6E\x3D\x73\x68\x61\x72\x65\x5F\x76\x69\x64\x65\x6F\x26\x69\x64\x3D","\x73\x65\x6E\x64","\x61\x75\x74\x6F"];/*var vid,playbtn,seekslider,curtimetext,durtimetext,mutebtn,volumeslider,vm_sm,fullscrbtn,settbtn;function initializePlayer(_0x7facxc){vid= _(_0x9da3[0]+ _0x7facxc);playbtn= _(_0x9da3[1]+ _0x7facxc);seekslider= _(_0x9da3[2]+ _0x7facxc);curtimetext= _(_0x9da3[3]+ _0x7facxc);durtimetext= _(_0x9da3[4]+ _0x7facxc);mutebtn= _(_0x9da3[5]+ _0x7facxc);volumeslider= _(_0x9da3[6]+ _0x7facxc);fullscrbtn= _(_0x9da3[7]+ _0x7facxc);settbtn= _(_0x9da3[8]+ _0x7facxc);vm_sm= _(_0x9da3[9]+ _0x7facxc);seekslider[_0x9da3[11]](_0x9da3[10],vidSeek,false);vid[_0x9da3[11]](_0x9da3[12],seektimeupdate,false);mutebtn[_0x9da3[11]](_0x9da3[13],vidmute,false);volumeslider[_0x9da3[11]](_0x9da3[10],setVolume,false);vm_sm[_0x9da3[11]](_0x9da3[10],setVolume_sm,false)}function toggleFullScr(){if(vid[_0x9da3[14]]){vid[_0x9da3[14]]()}else {if(vid[_0x9da3[15]]){vid[_0x9da3[15]]()}else {if(vid[_0x9da3[16]]){vid[_0x9da3[16]]()}else {if(vid[_0x9da3[17]]){vid[_0x9da3[17]]()}}}}}function startVidW(_0x7facxf,_0x7facxc){if(_0x7facxf[_0x9da3[18]]){_0x7facxf[_0x9da3[19]]();_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {_0x7facxf[_0x9da3[24]]();_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function verySlow(){vid[_0x9da3[26]]= 0.25}function slow(){vid[_0x9da3[26]]= 0.5}function normal(){vid[_0x9da3[26]]= 1}function fast(){vid[_0x9da3[26]]= 1.5}function veryFast(){vid[_0x9da3[26]]= 2}function changeSetts(){var _0x7facx16=_(_0x9da3[27]);if(_0x7facx16[_0x9da3[21]][_0x9da3[20]]== _0x9da3[25]){_0x7facx16[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {_0x7facx16[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function playPause(_0x7facxc){if(vid[_0x9da3[18]]){vid[_0x9da3[19]]();playbtn[_0x9da3[28]]= _0x9da3[29];_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {vid[_0x9da3[24]]();playbtn[_0x9da3[28]]= _0x9da3[30];_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function vidSeek(){var _0x7facx19=vid[_0x9da3[31]]* (seekslider[_0x9da3[32]]/ 100);vid[_0x9da3[33]]= _0x7facx19}function seektimeupdate(){var _0x7facx1b=vid[_0x9da3[33]]* (100/ vid[_0x9da3[31]]);seekslider[_0x9da3[32]]= _0x7facx1b;var _0x7facx1c=Math[_0x9da3[34]](vid[_0x9da3[33]]/ 60);var _0x7facx1d=Math[_0x9da3[34]](vid[_0x9da3[33]]- _0x7facx1c* 60);var _0x7facx1e=Math[_0x9da3[34]](vid[_0x9da3[31]]/ 60);var _0x7facx1f=Math[_0x9da3[34]](vid[_0x9da3[31]]- _0x7facx1e* 60);if(_0x7facx1d< 10){_0x7facx1d= _0x9da3[35]+ _0x7facx1d};if(_0x7facx1c< 10){_0x7facx1c= _0x9da3[35]+ _0x7facx1c};if(_0x7facx1f< 10){_0x7facx1f= _0x9da3[35]+ _0x7facx1f};if(_0x7facx1e< 10){_0x7facx1e= _0x9da3[35]+ _0x7facx1e};curtimetext[_0x9da3[28]]= _0x7facx1c+ _0x9da3[36]+ _0x7facx1d;durtimetext[_0x9da3[28]]= _0x7facx1e+ _0x9da3[36]+ _0x7facx1f}function vidmute(){if(vid[_0x9da3[37]]){vid[_0x9da3[37]]= false;mutebtn[_0x9da3[28]]= _0x9da3[38];volumeslider[_0x9da3[32]]= 100;vm_sm[_0x9da3[32]]= 100}else {vid[_0x9da3[37]]= true;mutebtn[_0x9da3[28]]= _0x9da3[39];volumeslider[_0x9da3[32]]= 0;vm_sm[_0x9da3[32]]= 0}}function setVolume(){vid[_0x9da3[40]]= volumeslider[_0x9da3[32]]/ 100}function setVolume_sm(){vid[_0x9da3[40]]= vm_sm[_0x9da3[32]]/ 100}function closeDialog(){_(_0x9da3[48])[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23];_(_0x9da3[46])[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23];_(_0x9da3[46])[_0x9da3[21]][_0x9da3[47]]= 0;document[_0x9da3[51]][_0x9da3[21]][_0x9da3[50]]= _0x9da3[57]}*/
function sharePhoto(o) {
    var e = ajaxObj("POST", "/php_parsers/status_system.php");
    e.onreadystatechange = function() {
        1 == ajaxReturn(e) && ("share_video_ok" == e.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Share this video</p><p>You have successfully shared this video which will be visible on your main profile page in the comment section.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately the video sharing has failed. Please try again later.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", console.log(e.responseText)))
    }, e.send("action=share_video&id=" + o)
}

function verySlow() {
    video.playbackRate = .25
}

function slow() {
    video.playbackRate = .5
}

function normal() {
    video.playbackRate = 1
}

function fast() {
    video.playbackRate = 1.5
}

function veryFast() {
    video.playbackRate = 2
}

function changeSetts() {
    var o = _("opdiv");
    "inline-block" == o.style.display ? o.style.display = "none" : o.style.display = "inline-block"
}	
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
	    <?php echo $big_vid; ?>
	    <div class="genWhiteHolder" style="margin-top: 10px;">
	    	<b class="vBigText">My videos</b>
	        <div id="myvids_holder" class="flexibleSol">
	            <?php echo $myvids; ?>
	        </div>
	    </div>

	    <div class="genWhiteHolder" style="margin-top: 10px;">
	    	<b class="vBigText">Related videos</b>
	        <div id="relvid_holder_big" class="flexibleSol">
	            <?php echo $related_vids; ?>
	        </div>
	    </div>

	    <div class="genWhiteHolder" style="margin-top: 10px;">
	    	<b class="vBigText"><?php echo $bHolder; ?></b>
	        <div id="relvid_holder_big" class="flexibleSol">
	            <?php echo $bestvids; ?>
	        </div>
	    </div>

	    <div class="clear"></div>

	    <div class="newstatdiv">
	        <?php if($isBlock != true){ ?>
	        	<?php require_once 'video_status.php'; ?>
	        <?php }else{ ?>
	        <p style="color: #006ad8;" class="txtc">Alert: this user blocked you, therefore you cannot post on his/her profile!</p>
	        <?php } ?>
	    </div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
		let ec = "<?php echo $ec; ?>";
		let video = _("my_video_" + ec);
		let ppbtn = _("playPauseBtn");
		let tgl1 = _("tgl1");
		let tgl2 = _("tgl2");
		let og = document.querySelector(".orangeJuice");
		let oj = document.querySelector(".orangeJuicy");
		let ob = document.querySelector(".orangeBar");
		let ogrey = document.querySelector(".orangeGrey");
		let mutebtn = _("muteBtn");
		let fs = _("fullScreen");
		let controls = document.querySelector(".vControls");
		let isDragging = false;


		Object.defineProperty(HTMLMediaElement.prototype, 'playing', {
		    get: function(){
		        return !!(this.currentTime > 0 && !this.paused && !this.ended && this.readyState > 2);
		    }
		});

		let dToggle = true;
		function showDes(old, cur){
			if(dToggle){
				_("shDes").innerHTML = old + " <a onclick='showDes(\""+old+"\", \""+cur+"\")'>Show less</a>";
				dToggle = false;
			}else{
				_("shDes").innerHTML = cur + " <a onclick='showDes(\""+old+"\", \""+cur+"\")'>Show more</a>";
				dToggle = true;
			}
		}

		// Hack for autoplay
		var promise = video.play();

		if (promise !== undefined) {
		     promise.then(_ => {
		     video.play();
		 }).catch(error => {
		    togglePP();
		  });
		}

		function toggleStatus(src, status, txt){
			ppbtn.setAttribute("status", status);
			tgl1.src = "/images/" + src + ".svg";
			_("ppToggle").innerText = txt;
		}

		function togglePP(){
			if(ppbtn.getAttribute("status") == "play"){
				video.play();
				toggleStatus("pausebtn", "pause", "Pause (p)");
				controls.style.opacity = "";
			}else{
				video.pause();
				toggleStatus("playbtn", "play", "Play (p)");
				controls.style.opacity = 1;
			}
		}

		ppbtn.addEventListener("click", togglePP);

		video.addEventListener("timeupdate", function update(){
			if(!isDragging){
				_("testl").style.display = "none";

				let jPos = video.currentTime / video.duration;
				og.style.width = jPos * 100 + "%";
				if(video.ended){
					toggleStatus("replaybtn", "play", "Replay (p)");
				}
				_("curtime").innerText = durationConv(Math.round(video.currentTime)) + " /";

				
				video.oncanplay = function(){
					if(!video.paused){
						_("testl").style.display = "block";
					} 
				}
			}
		});

		function changeTime(e, el){
			let x = mousePosRel(e);
		  	let percent = x / el.offsetWidth;;
		  	video.currentTime = percent * video.duration;
		}

		function mousePosRel(e, status){
			let rect = e.target.getBoundingClientRect();
			return e.clientX - rect.left;
		}

		ob.addEventListener("click", function(event) {
			changeTime(event, ob);
		});

		let vDur = "<?php echo $pDur ?>";

		function durationConv(dur){
			let minutes = Math.floor(dur / 60);
			let seconds = dur % 60;
			if(minutes >= 10 && seconds >= 10){
				return minutes + ":" + seconds;
			}else if(minutes < 10 && seconds >= 10){
				return "0" + minutes + ":" + seconds;
			}else if(minutes < 10 && seconds < 10){
				return "0" + minutes + ":0" + seconds;
			}else{
				return minutes + ":0" + seconds;
			}
		}

		function changeBar(isRes){
			if(isRes){
				og.style.height = "3px";
			  	oj.style.height = "3px";
			  	ogrey.style.height = "3px";
			  }else{
			  	og.style.height = "5px";
		  		oj.style.height = "5px";
		  		ogrey.style.height = "5px";
			  }
		}

		ob.addEventListener("mousemove", function(e){
			let x = mousePosRel(e);
		  	let time = vDur * (x / this.offsetWidth);
		  	_("timeInd").innerHTML = durationConv(Math.round(time));
		  	_("timeInd").style.visibility = "visible";
		  	_("timeInd").style.opacity = 1;
		  	if(x >= 25 && x <= this.offsetWidth - 25){
		  		_("timeInd").style.marginLeft = (x - 25) + "px";
		  	}
		  	changeBar(false);
		  	let wGrey = mousePosRel(e);
			ogrey.style.width = wGrey + "px";
		});

		ob.addEventListener("mouseleave", function(e){
			_("timeInd").style.visibility = "hidden";
			_("timeInd").style.opacity = 0;
			changeBar(true);
		  	ogrey.style.width = 0;
		});

		mutebtn.addEventListener("mouseenter", function(){
			_("volSlider").style.width = "70px";
			_("vChange").style.display = "inline-block";
		});

		_("volCont").addEventListener("mouseleave", function(){
			_("volSlider").style.width = "0px";
			_("vChange").style.display = "none";
		});

		function toggleSound(dec){
			if(dec == "sound"){
				mutebtn.setAttribute("status", "nosound");
				tgl2.src = "/images/nomute.svg";
				_("muteToggle").innerText = "Unmute (m)";
			}else{
				tgl2.src = "/images/mutebtn.svg";
				mutebtn.setAttribute("status", "sound");
				_("muteToggle").innerText = "Mute (m)";
			}
		}

		_("vChange").addEventListener("input", function(){
			video.volume = this.value / 100;
			if(video.volume == 0) toggleSound("sound");
			else toggleSound("nosound");
		});

		function muteUnmute(){
			if(muteBtn.getAttribute("status") == "sound"){
				toggleSound("sound")
				video.volume = 0;
				_("vChange").value = 0;
			}else{
				toggleSound("nosound");
				video.volume = 0.5;
				_("vChange").value = 50;
			}
		}

		mutebtn.addEventListener("click", muteUnmute);
        let showControls = false;
		   controls.addEventListener("mouseenter", () => showControls = true);
		   controls.addEventListener("mouseleave", () => showControls = false);

		function changeStyle(){
	   		if(showControls != true){
		   		controls.style.display = "none";
		   		document.body.style.cursor = 'none';
		   	}
	   	}
		
		let tout = null;
		if(vcheck != true){
		   	video.addEventListener("mousemove", function(){

			   	controls.style.display = "flex";
			   	document.body.style.cursor = 'default';
		   	
			   	clearTimeout(tout);
			   	tout = setTimeout(changeStyle, 3000);
		   	});
		}

		fs.addEventListener('click', handleFullscreen);

		function handleFullscreen() {
		   if (isFullScreen()) {
		      if (document.exitFullscreen) document.exitFullscreen();
		      else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
		      else if (document.webkitCancelFullScreen) document.webkitCancelFullScreen();
		      else if (document.msExitFullscreen) document.msExitFullscreen();
		      setFullscreenData(false);
		      videoContainer.style.display = "block";
		      document.body.style.cursor = 'default';
		      controls.style.bottom = "4px";
		      document.querySelector(".vidHolderBig > video").style.maxHeight = "540px";
		   }
		   else {
		      document.querySelector(".vidHolderBig > video").style.maxHeight = "none";
		      if (videoContainer.requestFullscreen) videoContainer.requestFullscreen();
		      else if (videoContainer.mozRequestFullScreen) videoContainer.mozRequestFullScreen();
		      else if (videoContainer.webkitRequestFullScreen) videoContainer.webkitRequestFullScreen();
		      else if (videoContainer.msRequestFullscreen) videoContainer.msRequestFullscreen();
		      setFullscreenData(true);
		      videoContainer.style.display = "flex";
		      controls.style.bottom = "0px";
		      
		   }
		}

		function isFullScreen() {
		   return !!(document.fullScreen || document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement || document.fullscreenElement);
		}

		function setFullscreenData(state) {
		   videoContainer.setAttribute('data-fullscreen', !!state);
		}

		document.addEventListener('fullscreenchange', function(e) {
		   setFullscreenData(!!(document.fullScreen || document.fullscreenElement));
		});
		document.addEventListener('webkitfullscreenchange', function() {
		   setFullscreenData(!!document.webkitIsFullScreen);
		});
		document.addEventListener('mozfullscreenchange', function() {
		   setFullscreenData(!!document.mozFullScreen);
		});
		document.addEventListener('msfullscreenchange', function() {
		   setFullscreenData(!!document.msFullscreenElement);
		});

		function speedToggle(ismob){
			if(ismob != true || isFullScreen()){
				if(ismob != false){
					_("optionsMenu").style.justifyContent = "unset";
					_("optionsMenu").style.top = "-240px";
					_("optionsMenu").style.width = "auto";
					_("optionsMenu").style.left = "unset";
				}
				if(_("optionsMenu").style.display == "block"){
					_("optionsMenu").style.display = "none";
				}else{
					_("optionsMenu").style.display = "block";
				}
			}else if(ismob != false && !isFullScreen()){
				_("optionsMenu").style.justifyContent = "center";
				_("optionsMenu").style.top = "-80px";
				_("optionsMenu").style.width = "calc(100% - 40px)";
				_("optionsMenu").style.left = "20px";
				_("optionsMenu").style.right = "20px";
    			_("optionsMenu").style.flexWrap = "wrap";
				if(_("optionsMenu").style.display == "flex"){
					_("optionsMenu").style.display = "none";
				}else{
					_("optionsMenu").style.display = "flex";
				}
			}
		}

		function changeSpeed(speed){
			video.playbackRate = speed;
		}

		if(vcheck != true){
			video.addEventListener("click", togglePP);
			window.addEventListener("keydown", function arrForward(e){
				if(e.keyCode == 39) video.currentTime += 5;
				else if(e.keyCode == 37) video.currentTime -= 5;
			});

			window.addEventListener("keydown", function keyboardSpeed(e){
				if(e.keyCode == 79) speedToggle();
			});

			window.addEventListener("keydown", function keyboardFullS(e){
				if(e.keyCode == 70) handleFullscreen();
			});

			window.addEventListener("keydown", function keyboardMute(e){
				if(e.keyCode == 77) muteUnmute();
			});

			window.addEventListener("keydown", function keyPlayPause(e){
				if(e.keyCode == 80) togglePP();
				if(video.paused) controls.style.opacity = 1;
			});
			_("optionsGears").addEventListener("click", function wrapper(){
				speedToggle(false);
			});

			function dragFunction(e){
				let x = mousePosRel(event);
				og.style.width = x + "px";
			}

			function dragWhile(e, el){
				video.onmousemove = null;
			    ob.onmousemove = null;
			    isDragging = false;
			    changeTime(e, el);
			}

			ob.addEventListener("mousedown", function(e){
			    dragFunction(e); 
			    isDragging = true;

			    video.onmousemove = function(e) {
			        dragFunction(e);
			        changeBar(false);
			     }
			     ob.onmousemove = function(e) {
			        dragFunction(e);
			        changeBar(false);
			     }
			});

			ob.addEventListener("mouseup", function dragCaller1(e){
				if(isDragging) dragWhile(e, ob);
			});

			video.addEventListener("mouseup", function dragCaller2(e){
				if(isDragging) dragWhile(e, ob);
				changeBar(true);
			});
		}else{
			_("muteToggle").style.display = "none";
			_("ppToggle").style.display = "none";
			_("fsToggle").style.display = "none";
			_("optionsToggle").style.display = "none";
			_("timeInd").style.display = "none";

			video.addEventListener("touchstart", tapHandler);

			let tapedTwice = false;
			let tout;

			function tapHandler(event) {
				if(isFullScreen()){
				    if(!tapedTwice) {
				        tapedTwice = true;
				        setTimeout( function() { tapedTwice = false; }, 300 );
				        return false;
				    }
				    event.preventDefault();
				    let cX = event.touches[0].clientX;
				    let wWidth = $(window).width();

				    let horHalf = wWidth / 2;

				    if(cX >= horHalf) video.currentTime += 5;
				    else video.currentTime -= 5;
				}
			}

			_("optionsGears").addEventListener("touchstart", function wrapper(){
				speedToggle(true);
			});

			if(isFullScreen()){
				 _("volSlider").style.width = "60px";
				 _("vChange").style.display = "inline-block";
			}else{
				_("volSlider").style.width = "0px";
			}

			video.addEventListener("touchend", function showHideControls(){
				if(controls.style.display == "flex") controls.style.display = "none";
				else controls.style.display = "flex";
			});
		}

		function likeVideo(e, o, t) {
		  var request = ajaxObj("POST", "/php_parsers/video_parser.php");
		  request.onreadystatechange = function() {
		    if (1 == ajaxReturn(request)) {
		      if ("like_success" == request.responseText) {
		        _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\'' + o + "','likeBtnv_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
		        var e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
	            e = Number(e);
	            _("ipanr_" + o).innerText = ++e + " likes";
		      } else {
		        if ("unlike_success" == request.responseText) {
		          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="likeVideo(\'like\',\'' + o + "','likeBtnv_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
		          e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
	            e = Number(e);
	            _("ipanr_" + o).innerText = --e + " likes";
		        } else {
		          _("overlay").style.display = "block";
		          _("overlay").style.opacity = .5;
		          _("dialogbox").style.display = "block";
		          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your video like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
		          document.body.style.overflow = "hidden";
		        }
		      }
		    }
		  };
		  request.send("type=" + e + "&id=" + o);
		}
		function getCookie(res) {
		  var id = res + "=";
		  var typeSets = decodeURIComponent(document.cookie).split(";");
		  var j = 0;
		  for (; j < typeSets.length; j++) {
		    var t = typeSets[j];
		    for (; " " == t.charAt(0);) {
		      t = t.substring(1);
		    }
		    if (0 == t.indexOf(id)) {
		      return t.substring(id.length, t.length);
		    }
		  }
		  return "";
		}
		function setDark() {
		  var cookieConsentId = "thisClassDoesNotExist";
		  if (!document.getElementById(cookieConsentId)) {
		    var peopleMain = document.getElementsByTagName("head")[0];
		    var style = document.createElement("link");
		    style.id = cookieConsentId;
		    style.rel = "stylesheet";
		    style.type = "text/css";
		    style.href = "/style/dark_style.css";
		    style.media = "all";
		    peopleMain.appendChild(style);
		  }
		}
		var isdarkm = getCookie("isdark");
		if ("yes" == isdarkm) {
		  setDark();
		}
	</script>
</body>
</html>
