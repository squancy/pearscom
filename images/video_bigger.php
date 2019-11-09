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
            WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)
            GROUP BY video";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    $numrows = $stmt->num_rows();
    $stmt->close();
    $days = 0;
    $decc = "";
    if($numrows > 0){
        $days = 1;
        $decc = "day";
    }else{
        $days = 7;
        $decc = "week";
    }
	$bestvids = "";
	$sql = "SELECT video, COUNT(*) AS u 
            FROM video_likes
            WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL -$days DAY)
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
            $sql = "SELECT video_poster, user, dur, video_name FROM videos WHERE id=? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$id_o);
            $stmt->execute();
            $stmt->bind_result($vidp,$vidu,$dur,$vidn);
            $stmt->fetch();
            $stmt->close();
            $prs = '/user/'.$vidu.'/videos/'.$vidp.'';
            $dur = convDur($dur);
            if($vidn == ""){
    			$vidn = "Untitled";
    		}
    		if(strlen($vidn) > 17){
    			$vidn = mb_substr($vidn, 0, 14, "utf-8");
    			$vidn .= "...";
    		}
            $bestvids .= '<a href="/video_zoom/'.$id_hsh.'"><div id="vidtit" class="pcjti" style="margin-top: 0px !important; width: 156px;">'.$vidn.'</div><div class="bvidh" style="background-image: url(\''.$prs.'\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 160px; height: 90px; margin-bottom: 5px; margin-right: 5px;"></div><span id="text_'.$ec.'" class="pcvdurh" style="margin-top: -35px !important;">'.$dur.'</span></a>';
        }
        $stmt->close();
    }else{
        $bestvids = '<i style="font-size: 14px;">Unfortunately, there are no videos such that ...</i>';
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
			$dur = convDur($row["dur"]);
			if($poster == ""){
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
    			$like_check = "SELECT id FROM video_likes WHERE video = ? LIMIT 1";
    			$stmt = $conn->prepare($like_check);
    			$stmt->bind_param("i",$id_number);
    			$stmt->execute();
    			$stmt->store_result();
    			$stmt->fetch();
    			$numrows = $stmt->num_rows;
    		if($numrows > 0){
    			    $isLike = true;
    			}
    
    		}
    		$hshid = base64url_encode($id_number,$hshkey);
    		if($isLike == true){
    			$likeButton = '<span id="likeBtnv_'.$hshid.'" style="display: inline-block; margin-right: 5px;"><a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\',\'ion_'.$hshid.'\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike"></a></span>';
    			$isLikeOrNot = '<b style="font-size: 12px !important; font-weight: normal; margin-right: 5px; margin-left: 5px; margin-top: -3px;" id="ion_'.$hshid.'">&#9658; You liked this video ('.$vc.')</b>';
    		}else{
    			$likeButton = '<span id="likeBtnv_'.$hshid.'" style="display: inline-block; margin-right: 5px;"><a href="#" onclick="return false;" onmousedown="likeVideo(\'like\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\',\'ion_'.$hshid.'\')"><img src="/images/nf.png" width="18" height="18" title="Like"></a></span>';
    			$isLikeOrNot = '<b style="font-size: 12px !important; font-weight: normal; margin-right: 5px; margin-left: 5px; margin-top: -3px;" id="ion_'.$hshid.'">&#9658; You did not like this video yet ('.$vc.')</b>';
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

			<div class="vidHolderBig">
			<video width="100%" id="my_video_'.$ec.'" style="background-image: url(\''.$poster.'\');" class="genBg bigvidg" preload="metadata" id="my_video_'.$ec.'" onclick="startVidW(my_video_'.$ec.',\''.$ec.'\')">
				<source src="/user/'.$u.'/videos/'. $video_file.'">

              <p style="font-size: 14px;" class="txtc">Unfortunately, an error has occured during the video loading. Please refresh the page and if the video is still loaded, please visit our <a href="/help">help</a> page or send us a <a href="/help#report">problem report.</a> As for now, you can <a href="/user/'.$u.'/videos/'. $video_file.'" download>download</a> the video/auido file and play it on your computer or mobile device.</p>
              </video>
              <div id="video-controls" class="controls" data-state="hidden" style="display: none;">
               <button id="playpause" type="button" data-state="play"></button>
               <button id="stop" type="button" data-state="stop"></button>
               <div class="progress">
                  <progress id="progress" value="0" min="0">
                     <span id="progress-bar"></span>
                  </progress>
               </div>
               <button id="mute" type="button" data-state="mute"></button>
               <button id="volinc" type="button" data-state="volup"></button>
               <button id="voldec" type="button" data-state="voldown"></button>
               <button type="button" id="curdur"><span id="curt">00:00</span> / '.$dur.'</button>
               <div id="opdiv">
                    <span onclick="verySlow()">Very slow [0.25]</span> <img src="/images/vssnail.png" width="12" height="12"><br />
        			<span onclick="slow()">Slow [x0.50]</span> <img src="/images/sturt.png" width="12" height="12"><br />
        			<span onclick="normal()">Normal [x1.00]</span> <img src="/images/nnrom.png" width="12" height="12"><br />
        			<span onclick="fast()">Fast [x1.50]</span> <img src="/images/fghop.png" width="12" height="12"><br />
        			<span onclick="veryFast()">Very fast [x2]</span> <img src="/images/vfch.png" width="12" height="12">
               </div>
               <button type="button" id="options" onclick="changeSetts()"></button>
               <button id="fs" type="button" data-state="go-fullscreen"></button>
            </div>


            <div class="vControls">
            	<div class="orangeBar">
            		<div class="orangeJuice"></div>
            	</div>
            	<div class="vButtons">
            		<button id="playPauseBtn" status="play">
            			<img src="/images/playbtn.svg" width="20" height="20" id="tgl1">
            		</button>
            		<button id="muteBtn">
            			<img src="/images/mutebtn.svg" width="20" height="20">
            		</button>
            	</div>
            </div>

            </div>

            <div class="clear"></div>

			<div id="bholder"><p style="margin-top: 0px;">Most liked videos of the '.$decc.': </p>'.$bestvids.'</div>
			<div class="clear"></div>
			<p class="shtrp">'.$video_name.'<span class="flor">'.$isLikeOrNot.''.$likeButton.'<img src="/images/black_share.png" id="video_share" style="width: 18px; margin-top: 5px; height: 18px; margin: 0;" onclick="sharePhoto(\''.$id_number.'\')"></span></p><div class="clear"></div><a href="/user/'.$user.'/"><div style="background-image: url(\''.$avav.'\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 40px; height: 40px; float: left; border-radius: 3px;"></div></a>&nbsp;<b style="display: inline-block; margin-bottom: -10px; vertical-align: middle;"> - '.$user.'</b><br>&nbsp;<b style="display: inline-block; font-size: 12px !important; color: #585858; margin-bottom: -10px; vertical-align: middle;"> - Published: '.$upload.' ('.$agoform.' ago)</b><div class="clear"></div><p style="font-size: 14px; margin-bottom: 0px;">'.$description.'</p>
			<div id="bholder_mob"><hr class="dhr"><p style="margin-top: 0px;">Most liked videos of the '.$decc.': </p>'.$bestvids.'</div>
			<div class="clear"></div>
		</div>';
		}
	}else{
		header('location: /videonotexist');
		exit();
	}
	$stmt->close();
	/*<img src="/images/transp.jpg" onload="initializePlayer(\''.$ec.'\')" style="display: none;">*/
	// Old video controls
	/*<span id="settings_'.$ec.'" onclick="changeSetts()"><img src="/images/dg.png" width="18" height="18" id="longsetts"></span>
			                <span id="text_'.$ec.'" class="pcvdurh pcusgf" style="margin-top: 1px !important;">'.$dur.'</span>
			                
			                
			                <div id="video_controls_bar_bm" style="width: 35%; max-width: 700px;" class="biggerctrls">
					    		<span id="playpausebtn_'.$ec.'" onclick="playPause(\''.$ec.'\')" class="ms_visible"><img src="/images/pausebtn.png" width="15" height="15"></span>
					    		<input type="range" id="seekslider_'.$ec.'" min="0" max="100" value="0" step="1" class="sslider_long sslider">
					    		<span id="ms_align_big"><span id="curtimetext_'.$ec.'" class="vidtime">0:00</span> <span class="vidtime">/</span> <span id="durtimetext_'.$ec.'" class="vidtime">0:00</span></span>
					    		<span id="mutebtn_'.$ec.'" class="ms_visible"><img src="/images/mute.png" wdith="19" height="19" id="mutebigger"></span>
					    		<span id="vmhide"><input type="range" id="volumeslider_'.$ec.'" min="0" max="100" value="100" step="1" class="sslider_short vslider"></span>
					    		<span id="settings_'.$ec.'" onclick="changeSetts()" style="float: right;"><img src="/images/sett.png" width="18" height="18" id="shortsetts"></span>
					    		<div class="settings_menu newfitm" id="settings_menu_vb">
					    			<p style="font-size: 10px; color: #fff; margin: 0;">Change video speed</p>
					    			<span onclick="verySlow()">Very slow [0.25]</span> <img src="/images/vssnail.png" width="12" height="12"><br />
	                    			<span onclick="slow()">Slow [x0.50]</span> <img src="/images/sturt.png" width="12" height="12"><br />
	                    			<span onclick="normal()">Normal [x1.00]</span> <img src="/images/nnrom.png" width="12" height="12"><br />
	                    			<span onclick="fast()">Fast [x1.50]</span> <img src="/images/fghop.png" width="12" height="12"><br />
	                    			<span onclick="veryFast()">Very fast [x2]</span> <img src="/images/vfch.png" width="12" height="12">
					    			<hr class="dhr" style="color: #fff;">
					    			<p style="font-size: 10px; color: #fff; margin: 0;">Change to full screen</p>
					    			<span onclick="toggleFullScr()">Full screen</span> <img src="/images/fcbtn.png" width="12" height="12">
					    			<div id="vmvisi"><input type="range" id="volumeslider_sm_'.$ec.'" min="0" max="100" value="100" step="1" class="sslider_short vslider"></div>
					    		</div>
					    	</div>*/
	
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
		if(strlen($vdescription) > 12){
			$vdescription = mb_substr($vdescription, 0, 8, "utf-8");
			$vdescription .= " ...";
		}
		if(strlen($vvname) > 12){
			$vvname = mb_substr($vvname, 0, 8, "utf-8");
			$vvname .= " ...";
		}
		if(strlen($vuser) > 12){
			$vuser = mb_substr($vuser, 0, 8, "utf-8");
			$vuser .= " ...";
		}
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
		$related_vids .= "<div id='nev_rel_holder_' style='width: 200px;'><a href='/video_zoom/".$vid."'><div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat; background-position: center; background-size: cover; width: 56px; height: 56px; float: left; margin-right: 2px;'></div></a><div id='new_inner_div_'><p style='font-size: 12px !important; float: left; padding: 0px; margin-top: 10px;'><i>Uploaded by: </i>".$vuser."<br><i>Name: </i>".$vvname."<br><i>Description: </i>".$vdescription."<br><i>Published: </i>".$uds." ago</p></div></div>";
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
			if(strlen($vdescription) > 12){
				$vdescription = mb_substr($vdescription, 0, 8, "utf-8");
				$vdescription .= " ...";
			}
			if(strlen($vvname) > 12){
				$vvname = mb_substr($vvname, 0, 8, "utf-8");
				$vvname .= " ...";
			}
			if(strlen($vuser) > 12){
				$vuser = mb_substr($vuser, 0, 8, "utf-8");
				$vuser .= " ...";
			}
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
			$related_vids .= "<div id='nev_rel_holder_' style='width: 200px;'><a href='/video_zoom/".$vid."'><div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat; background-position: center; background-size: cover; width: 56px; height: 56px; float: left; margin-right: 2px;'></div></a><div id='new_inner_div_'><p style='font-size: 12px !important; float: left; padding: 0px; margin-top: 10px;'><i>Uploaded by: </i>".$vuser."<br><i>Name: </i>".$vvname."<br><i>Description: </i>".$vdescription."<br><i>Published: </i>".$uds." ago</p></div></div>";
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
		if(strlen($vdescription_) > 12){
			$vdescription_ = mb_substr($vdescription_, 0, 8, "utf-8");
			$vdescription_ .= " ...";
		}
		if(strlen($vvname_) > 12){
			$vvname_ = mb_substr($vvname_, 0, 8, "utf-8");
			$vvname_ .= " ...";
		}
		
		if(strlen($vuser_) > 12){
			$vuser_ = mb_substr($vuser_, 0, 8, "utf-8");
			$vuser_ .= " ...";
		}
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

		$myvids .= "<div id='nev_rel_holder_' style='width: 200px;'><a href='/video_zoom/".$vid_."'><div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat; background-position: center; background-size: cover; width: 56px; height: 56px; float: left; margin-right: 2px;'></div></a><div id='new_inner_div_'><p style='font-size: 12px !important; float: left; padding: 0px; margin-top: 10px;'><i>Uploaded by: </i>".$vuser_."<br><i>Name: </i>".$vvname_."<br><i>Description: </i>".$vdescription_."<br><i>Published: </i>".$uds." ago</p></div></div>";
	}
	$stmt->close();
	
	$ismyv = false;
	$isrel = false;
	
	if($myvids == ""){
	    $myvids = '<i style="font-size: 14px;">Unfortunately, we could not get any of your videos ...</i>';
	    $ismyv = true;
	}
	
	if($related_vids == ""){
	    $related_vids = '<i style="font-size: 14px;">Unfortunately, we could not list any related videos for you ...</i>';
	    $isrel = true;
	}

	if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
		$related_vids = "<p style='font-size: 14px;'>Unfortunately, we could not find any related videos for you because you are not <a href='/login'>logged in</a> or you do not own an <a href='/signup'>account</a>!</p>";
		$myvids = "<p style='font-size: 14px;'>Unfortunately, we could not not list your videos here because you are not <a href='/login'>logged in</a> or you do not own an <a href='/signup'>account</a>!</p>";
		$isrel = true;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Video - <?php echo $video_name; ?></title>
	<meta charset="utf-8">
	<meta lang="en">
	<link rel="icon" type="image/x-icon" href="/images/webicon.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Watch <?php echo $u; ?>&#39;s video! General information/description: <?php echo $description; ?> | title: <?php echo $video_name; ?> | published: <?php echo $upload; ?> (<?php echo $agoform; ?> ago)">
    <meta name="keywords" content="pearscom video, <?php echo $video_name; ?>, <?php echo $video_name; ?> video, <?php echo $video_name; ?> pearscom video, <?php echo $u; ?> video pearscom, video pearscom">
    <meta name="author" content="Pearscom">
	<script src="/js/jjs.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script type="text/javascript">
	var _0x9da3=["\x6D\x79\x5F\x76\x69\x64\x65\x6F\x5F","\x70\x6C\x61\x79\x70\x61\x75\x73\x65\x62\x74\x6E\x5F","\x73\x65\x65\x6B\x73\x6C\x69\x64\x65\x72\x5F","\x63\x75\x72\x74\x69\x6D\x65\x74\x65\x78\x74\x5F","\x64\x75\x72\x74\x69\x6D\x65\x74\x65\x78\x74\x5F","\x6D\x75\x74\x65\x62\x74\x6E\x5F","\x76\x6F\x6C\x75\x6D\x65\x73\x6C\x69\x64\x65\x72\x5F","\x66\x75\x6C\x6C\x73\x63\x72\x62\x74\x6E\x5F","\x73\x65\x74\x74\x69\x6E\x67\x73\x5F","\x76\x6F\x6C\x75\x6D\x65\x73\x6C\x69\x64\x65\x72\x5F\x73\x6D\x5F","\x63\x68\x61\x6E\x67\x65","\x61\x64\x64\x45\x76\x65\x6E\x74\x4C\x69\x73\x74\x65\x6E\x65\x72","\x74\x69\x6D\x65\x75\x70\x64\x61\x74\x65","\x63\x6C\x69\x63\x6B","\x72\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x73\x63\x72\x65\x65\x6E","\x77\x65\x62\x6B\x69\x74\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x53\x63\x72\x65\x65\x6E","\x6D\x6F\x7A\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x53\x63\x72\x65\x65\x6E","\x6D\x73\x52\x65\x71\x75\x65\x73\x74\x46\x75\x6C\x6C\x73\x63\x72\x65\x65\x6E","\x70\x61\x75\x73\x65\x64","\x70\x6C\x61\x79","\x64\x69\x73\x70\x6C\x61\x79","\x73\x74\x79\x6C\x65","\x74\x65\x78\x74\x5F","\x6E\x6F\x6E\x65","\x70\x61\x75\x73\x65","\x62\x6C\x6F\x63\x6B","\x70\x6C\x61\x79\x62\x61\x63\x6B\x52\x61\x74\x65","\x73\x65\x74\x74\x69\x6E\x67\x73\x5F\x6D\x65\x6E\x75\x5F\x76\x62","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x70\x61\x75\x73\x65\x62\x74\x6E\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x3E","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x70\x6C\x61\x79\x62\x74\x6E\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x3E","\x64\x75\x72\x61\x74\x69\x6F\x6E","\x76\x61\x6C\x75\x65","\x63\x75\x72\x72\x65\x6E\x74\x54\x69\x6D\x65","\x66\x6C\x6F\x6F\x72","\x30","\x3A","\x6D\x75\x74\x65\x64","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x6E\x6D\x75\x74\x65\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x35\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x35\x27\x20\x69\x64\x3D\x27\x6D\x75\x74\x65\x62\x69\x67\x67\x65\x72\x27\x3E","\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x27\x2F\x69\x6D\x61\x67\x65\x73\x2F\x6D\x75\x74\x65\x2E\x70\x6E\x67\x27\x20\x77\x69\x64\x74\x68\x3D\x27\x31\x39\x27\x20\x68\x65\x69\x67\x68\x74\x3D\x27\x31\x39\x27\x20\x69\x64\x3D\x27\x6D\x75\x74\x65\x62\x69\x67\x67\x65\x72\x27\x3E","\x76\x6F\x6C\x75\x6D\x65","\x50\x4F\x53\x54","\x2F\x70\x68\x70\x5F\x70\x61\x72\x73\x65\x72\x73\x2F\x73\x74\x61\x74\x75\x73\x5F\x73\x79\x73\x74\x65\x6D\x2E\x70\x68\x70","\x6F\x6E\x72\x65\x61\x64\x79\x73\x74\x61\x74\x65\x63\x68\x61\x6E\x67\x65","\x72\x65\x73\x70\x6F\x6E\x73\x65\x54\x65\x78\x74","\x73\x68\x61\x72\x65\x5F\x76\x69\x64\x65\x6F\x5F\x6F\x6B","\x6F\x76\x65\x72\x6C\x61\x79","\x6F\x70\x61\x63\x69\x74\x79","\x64\x69\x61\x6C\x6F\x67\x62\x6F\x78","\x3C\x70\x20\x73\x74\x79\x6C\x65\x3D\x22\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x38\x70\x78\x3B\x20\x6D\x61\x72\x67\x69\x6E\x3A\x20\x30\x70\x78\x3B\x22\x3E\x53\x68\x61\x72\x65\x20\x74\x68\x69\x73\x20\x76\x69\x64\x65\x6F\x3C\x2F\x70\x3E\x3C\x70\x3E\x59\x6F\x75\x20\x68\x61\x76\x65\x20\x73\x75\x63\x63\x65\x73\x73\x66\x75\x6C\x6C\x79\x20\x73\x68\x61\x72\x65\x64\x20\x74\x68\x69\x73\x20\x76\x69\x64\x65\x6F\x20\x77\x68\x69\x63\x68\x20\x77\x69\x6C\x6C\x20\x62\x65\x20\x76\x69\x73\x69\x62\x6C\x65\x20\x6F\x6E\x20\x79\x6F\x75\x72\x20\x6D\x61\x69\x6E\x20\x70\x72\x6F\x66\x69\x6C\x65\x20\x70\x61\x67\x65\x20\x69\x6E\x20\x74\x68\x65\x20\x63\x6F\x6D\x6D\x65\x6E\x74\x20\x73\x65\x63\x74\x69\x6F\x6E\x2E\x3C\x2F\x70\x3E\x3C\x62\x75\x74\x74\x6F\x6E\x20\x69\x64\x3D\x22\x76\x75\x70\x6C\x6F\x61\x64\x22\x20\x73\x74\x79\x6C\x65\x3D\x22\x70\x6F\x73\x69\x74\x69\x6F\x6E\x3A\x20\x61\x62\x73\x6F\x6C\x75\x74\x65\x3B\x20\x72\x69\x67\x68\x74\x3A\x20\x33\x70\x78\x3B\x20\x62\x6F\x74\x74\x6F\x6D\x3A\x20\x33\x70\x78\x3B\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x44\x69\x61\x6C\x6F\x67\x28\x29\x22\x3E\x43\x6C\x6F\x73\x65\x3C\x2F\x62\x75\x74\x74\x6F\x6E\x3E","\x6F\x76\x65\x72\x66\x6C\x6F\x77","\x62\x6F\x64\x79","\x68\x69\x64\x64\x65\x6E","\x3C\x70\x20\x73\x74\x79\x6C\x65\x3D\x22\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x38\x70\x78\x3B\x20\x6D\x61\x72\x67\x69\x6E\x3A\x20\x30\x70\x78\x3B\x22\x3E\x41\x6E\x20\x65\x72\x72\x6F\x72\x20\x68\x61\x73\x20\x6F\x63\x63\x75\x72\x65\x64\x3C\x2F\x70\x3E\x3C\x70\x3E\x55\x6E\x66\x6F\x72\x74\x75\x6E\x61\x74\x65\x6C\x79\x20\x74\x68\x65\x20\x76\x69\x64\x65\x6F\x20\x73\x68\x61\x72\x69\x6E\x67\x20\x68\x61\x73\x20\x66\x61\x69\x6C\x65\x64\x2E\x20\x50\x6C\x65\x61\x73\x65\x20\x74\x72\x79\x20\x61\x67\x61\x69\x6E\x20\x6C\x61\x74\x65\x72\x2E\x3C\x2F\x70\x3E\x3C\x62\x75\x74\x74\x6F\x6E\x20\x69\x64\x3D\x22\x76\x75\x70\x6C\x6F\x61\x64\x22\x20\x73\x74\x79\x6C\x65\x3D\x22\x70\x6F\x73\x69\x74\x69\x6F\x6E\x3A\x20\x61\x62\x73\x6F\x6C\x75\x74\x65\x3B\x20\x72\x69\x67\x68\x74\x3A\x20\x33\x70\x78\x3B\x20\x62\x6F\x74\x74\x6F\x6D\x3A\x20\x33\x70\x78\x3B\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x44\x69\x61\x6C\x6F\x67\x28\x29\x22\x3E\x43\x6C\x6F\x73\x65\x3C\x2F\x62\x75\x74\x74\x6F\x6E\x3E","\x6C\x6F\x67","\x61\x63\x74\x69\x6F\x6E\x3D\x73\x68\x61\x72\x65\x5F\x76\x69\x64\x65\x6F\x26\x69\x64\x3D","\x73\x65\x6E\x64","\x61\x75\x74\x6F"];/*var vid,playbtn,seekslider,curtimetext,durtimetext,mutebtn,volumeslider,vm_sm,fullscrbtn,settbtn;function initializePlayer(_0x7facxc){vid= _(_0x9da3[0]+ _0x7facxc);playbtn= _(_0x9da3[1]+ _0x7facxc);seekslider= _(_0x9da3[2]+ _0x7facxc);curtimetext= _(_0x9da3[3]+ _0x7facxc);durtimetext= _(_0x9da3[4]+ _0x7facxc);mutebtn= _(_0x9da3[5]+ _0x7facxc);volumeslider= _(_0x9da3[6]+ _0x7facxc);fullscrbtn= _(_0x9da3[7]+ _0x7facxc);settbtn= _(_0x9da3[8]+ _0x7facxc);vm_sm= _(_0x9da3[9]+ _0x7facxc);seekslider[_0x9da3[11]](_0x9da3[10],vidSeek,false);vid[_0x9da3[11]](_0x9da3[12],seektimeupdate,false);mutebtn[_0x9da3[11]](_0x9da3[13],vidmute,false);volumeslider[_0x9da3[11]](_0x9da3[10],setVolume,false);vm_sm[_0x9da3[11]](_0x9da3[10],setVolume_sm,false)}function toggleFullScr(){if(vid[_0x9da3[14]]){vid[_0x9da3[14]]()}else {if(vid[_0x9da3[15]]){vid[_0x9da3[15]]()}else {if(vid[_0x9da3[16]]){vid[_0x9da3[16]]()}else {if(vid[_0x9da3[17]]){vid[_0x9da3[17]]()}}}}}function startVidW(_0x7facxf,_0x7facxc){if(_0x7facxf[_0x9da3[18]]){_0x7facxf[_0x9da3[19]]();_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {_0x7facxf[_0x9da3[24]]();_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function verySlow(){vid[_0x9da3[26]]= 0.25}function slow(){vid[_0x9da3[26]]= 0.5}function normal(){vid[_0x9da3[26]]= 1}function fast(){vid[_0x9da3[26]]= 1.5}function veryFast(){vid[_0x9da3[26]]= 2}function changeSetts(){var _0x7facx16=_(_0x9da3[27]);if(_0x7facx16[_0x9da3[21]][_0x9da3[20]]== _0x9da3[25]){_0x7facx16[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {_0x7facx16[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function playPause(_0x7facxc){if(vid[_0x9da3[18]]){vid[_0x9da3[19]]();playbtn[_0x9da3[28]]= _0x9da3[29];_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23]}else {vid[_0x9da3[24]]();playbtn[_0x9da3[28]]= _0x9da3[30];_(_0x9da3[22]+ _0x7facxc)[_0x9da3[21]][_0x9da3[20]]= _0x9da3[25]}}function vidSeek(){var _0x7facx19=vid[_0x9da3[31]]* (seekslider[_0x9da3[32]]/ 100);vid[_0x9da3[33]]= _0x7facx19}function seektimeupdate(){var _0x7facx1b=vid[_0x9da3[33]]* (100/ vid[_0x9da3[31]]);seekslider[_0x9da3[32]]= _0x7facx1b;var _0x7facx1c=Math[_0x9da3[34]](vid[_0x9da3[33]]/ 60);var _0x7facx1d=Math[_0x9da3[34]](vid[_0x9da3[33]]- _0x7facx1c* 60);var _0x7facx1e=Math[_0x9da3[34]](vid[_0x9da3[31]]/ 60);var _0x7facx1f=Math[_0x9da3[34]](vid[_0x9da3[31]]- _0x7facx1e* 60);if(_0x7facx1d< 10){_0x7facx1d= _0x9da3[35]+ _0x7facx1d};if(_0x7facx1c< 10){_0x7facx1c= _0x9da3[35]+ _0x7facx1c};if(_0x7facx1f< 10){_0x7facx1f= _0x9da3[35]+ _0x7facx1f};if(_0x7facx1e< 10){_0x7facx1e= _0x9da3[35]+ _0x7facx1e};curtimetext[_0x9da3[28]]= _0x7facx1c+ _0x9da3[36]+ _0x7facx1d;durtimetext[_0x9da3[28]]= _0x7facx1e+ _0x9da3[36]+ _0x7facx1f}function vidmute(){if(vid[_0x9da3[37]]){vid[_0x9da3[37]]= false;mutebtn[_0x9da3[28]]= _0x9da3[38];volumeslider[_0x9da3[32]]= 100;vm_sm[_0x9da3[32]]= 100}else {vid[_0x9da3[37]]= true;mutebtn[_0x9da3[28]]= _0x9da3[39];volumeslider[_0x9da3[32]]= 0;vm_sm[_0x9da3[32]]= 0}}function setVolume(){vid[_0x9da3[40]]= volumeslider[_0x9da3[32]]/ 100}function setVolume_sm(){vid[_0x9da3[40]]= vm_sm[_0x9da3[32]]/ 100}function closeDialog(){_(_0x9da3[48])[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23];_(_0x9da3[46])[_0x9da3[21]][_0x9da3[20]]= _0x9da3[23];_(_0x9da3[46])[_0x9da3[21]][_0x9da3[47]]= 0;document[_0x9da3[51]][_0x9da3[21]][_0x9da3[50]]= _0x9da3[57]}*/
	function sharePhoto(o){var e=ajaxObj("POST","/php_parsers/status_system.php");e.onreadystatechange=function(){1==ajaxReturn(e)&&("share_video_ok"==e.responseText?(_("overlay").style.display="block",_("overlay").style.opacity=.5,_("dialogbox").style.display="block",_("dialogbox").innerHTML='<p style="font-size: 18px; margin: 0px;">Share this video</p><p>You have successfully shared this video which will be visible on your main profile page in the comment section.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>',document.body.style.overflow="hidden"):(_("overlay").style.display="block",_("overlay").style.opacity=.5,_("dialogbox").style.display="block",_("dialogbox").innerHTML='<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately the video sharing has failed. Please try again later.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>',document.body.style.overflow="hidden",console.log(e.responseText)))},e.send("action=share_video&id="+o)}function verySlow(){video.playbackRate=.25}function slow(){video.playbackRate=.5}function normal(){video.playbackRate=1}function fast(){video.playbackRate=1.5}function veryFast(){video.playbackRate=2}function changeSetts(){var o=_("opdiv");"inline-block"==o.style.display?o.style.display="none":o.style.display="inline-block"}
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
	    <?php echo $big_vid; ?>
	    <div class="compdiv" style="width: 95%;" id="getw"><b>My videos <img src="/images/myone.png" class="notfimg" style="margin-bottom: -2px;"></b>
	        <p style='color: #999; font-size: 12px; margin-top: 0px;'>My other videos randomly ordered</p>
	        <?php if($ismyv == false){ ?>
	        <div class="rightarrd" id="slider"><img src="/images/larr.png" width="28" height="28"></div>
	        <?php } ?>
	        <div id="myvids_holder">
	            <?php echo $myvids; ?>
	        </div>
	        <?php if($ismyv == false){ ?>
	        <div class="rightarrd" id="slidel" style="margin-top: -67px;"><img src="/images/rarr.png" width="28" height="28"></div>
	        <?php } ?>
	    </div>
	    <div class="compdiv" style="width: 95%;"><b>Related videos <img src="/images/related.png" class="notfimg" style="margin-bottom: -2px;"></b>
	        <p style='color: #999; font-size: 12px; margin-top: 0px;'>Some videos from your friends</p>
	        <?php if($isrel == false){ ?>
	        <div class="rightarrd" id="slide1"><img src="/images/larr.png" width="28" height="28"></div>
	        <?php } ?>
	        <div id="relvid_holder_big">
	            <?php echo $related_vids; ?>
	        </div>
	        <?php if($isrel == false){ ?>
	        <div class="rightarrd" id="slide2" style="margin-top: -67px;"><img src="/images/rarr.png" width="28" height="28"></div>
	        <?php } ?>
	    </div>
	    <div class="clear"></div>
	    <br />
	    <div class="newstatdiv">
	        <?php if($isBlock == false){ ?>
	        <?php require_once 'video_status.php'; ?>
	        <?php }else{ ?>
	        <p style="font-size: 14px; color: #ffd11a;"><img src="/images/alwar.png" width="14" height="14"> Alert: this user blocked you, therefore you cannot post below his/her video!</p>
	        <?php } ?>
	    </div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
    	/*var ec = "<?php echo $ec; ?>";
		var videoContainer = _("videoContainer");
		var video = _("my_video_" + ec);
		var videoControls = _("video-controls");
		var playpause = _("playpause");
		var stop = _("stop");
		var mute = _("mute");
		var volinc = _("volinc");
		var voldec = _("voldec");
		var progress = _("progress");
		var progressBar = _("progress-bar");
		var fullscreen = _("fs");
		var supportsVideo = !!document.createElement("video").canPlayType;
		function vidEnd(canCreateDiscussions) {
		  changeButtonState("ended");
		}
		supportsVideo ? videoControls.setAttribute("data-state", "visible") : video.controls = true, video.addEventListener("ended", vidEnd, false), video.addEventListener("play", function() {
		  changeButtonState("playpause");
		}, false), video.addEventListener("pause", function() {
		  changeButtonState("playpause");
		}, false), stop.addEventListener("click", function(canCreateDiscussions) {
		  video.pause();
		  video.currentTime = 0;
		  progress.value = 0;
		  changeButtonState("playpause");
		}), playpause.addEventListener("click", function(canCreateDiscussions) {
		  if (video.paused || video.ended) {
		    video.play();
		  } else {
		    video.pause();
		  }
		}), mute.addEventListener("click", function(canCreateDiscussions) {
		  video.muted = !video.muted;
		  changeButtonState("mute");
		}), volinc.addEventListener("click", function(canCreateDiscussions) {
		  alterVolume("+");
		}), voldec.addEventListener("click", function(canCreateDiscussions) {
		  alterVolume("-");
		});
		var alterVolume = function(ch) {
		  var numPolyTabs = Math.floor(10 * video.volume) / 10;
		  if ("+" === ch) {
		    if (numPolyTabs < 1) {
		      video.volume += .1;
		    }
		  } else {
		    if ("-" === ch && numPolyTabs > 0) {
		      video.volume -= .1;
		    }
		  }
		};
		var checkVolume = function(data) {
		  if (data) {
		    var numPolyTabs = Math.floor(10 * video.volume) / 10;
		    if ("+" === data) {
		      if (numPolyTabs < 1) {
		        video.volume += .1;
		      }
		    } else {
		      if ("-" === data && numPolyTabs > 0) {
		        video.volume -= .1;
		      }
		    }
		    video.muted = numPolyTabs <= 0;
		  }
		  changeButtonState("mute");
		};
		alterVolume = function(updated) {
		  checkVolume(updated);
		};
		video.addEventListener("volumechange", function() {
		  checkVolume();
		}, false), video.addEventListener("loadedmetadata", function() {
		  progress.setAttribute("max", video.duration);
		}), video.addEventListener("timeupdate", function() {
		  if (!progress.getAttribute("max")) {
		    progress.setAttribute("max", video.duration);
		  }
		  progress.value = video.currentTime;
		  progressBar.style.width = Math.floor(video.currentTime / video.duration * 100) + "%";
		  seektimeupdate();
		});
		var curtimetext = _("curt");
		function seektimeupdate() {
		  video.currentTime;
		  video.duration;
		  var minutes = Math.floor(video.currentTime / 60);
		  var seconds = Math.floor(video.currentTime - 60 * minutes);
		  var interval = Math.floor(video.duration / 60);
		  var ret = Math.floor(video.duration - 60 * interval);
		  if (seconds < 10) {
		    seconds = "0" + seconds;
		  }
		  if (minutes < 10) {
		    minutes = "0" + minutes;
		  }
		  if (ret < 10) {
		    ret = "0" + ret;
		  }
		  if (interval < 10) {
		    interval = "0" + interval;
		  }
		  curtimetext.innerHTML = minutes + ":" + seconds;
		}
		progress.addEventListener("click", function(event) {
		  var percent = (event.pageX - (this.offsetLeft + this.offsetParent.offsetLeft)) / this.offsetWidth;
		  video.currentTime = percent * video.duration;
		});
		var fullScreenEnabled = !!(document.fullscreenEnabled || document.mozFullScreenEnabled || document.msFullscreenEnabled || document.webkitSupportsFullscreen || document.webkitFullscreenEnabled || document.createElement("video").webkitRequestFullScreen);
		fullScreenEnabled || (fullscreen.style.display = "none"), fs.addEventListener("click", function(canCreateDiscussions) {
		  handleFullscreen();
		});
		var isFullScreen = function() {
		  return !!(document.fullScreen || document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement || document.fullscreenElement);
		};
		var handleFullscreen = function() {
		  if (isFullScreen()) {
		    if (document.exitFullscreen) {
		      document.exitFullscreen();
		    } else {
		      if (document.mozCancelFullScreen) {
		        document.mozCancelFullScreen();
		      } else {
		        if (document.webkitCancelFullScreen) {
		          document.webkitCancelFullScreen();
		        } else {
		          if (document.msExitFullscreen) {
		            document.msExitFullscreen();
		          }
		        }
		      }
		    }
		    setFullscreenData(false);
		    if (0 == mobilecheck) {
		      videoContainer.style.width = "60%";
		    } else {
		      videoControls.style.marginTop = "-36px";
		    }
		  } else {
		    if (videoContainer.requestFullscreen) {
		      videoContainer.requestFullscreen();
		    } else {
		      if (videoContainer.mozRequestFullScreen) {
		        videoContainer.mozRequestFullScreen();
		      } else {
		        if (videoContainer.webkitRequestFullScreen) {
		          videoContainer.webkitRequestFullScreen();
		        } else {
		          if (videoContainer.msRequestFullscreen) {
		            videoContainer.msRequestFullscreen();
		          }
		        }
		      }
		    }
		    setFullscreenData(true);
		    videoContainer.style.width = "100%";
		    videoControls.style.marginTop = "-4px";
		  }
		};
		var setFullscreenData = function(isIron) {
		  videoContainer.setAttribute("data-fullscreen", !!isIron);
		  if (0 == isIron) {
		    if (0 == mobilecheck) {
		      videoContainer.style.width = "60%";
		      videoControls.style.marginTop = "-4px";
		    }
		  } else {
		    videoContainer.style.width = "100%";
		    if (1 == mobilecheck) {
		      videoControls.style.marginTop = "-36px";
		    }
		  }
		};
		document.addEventListener("fullscreenchange", function(canCreateDiscussions) {
		  setFullscreenData(!(!document.fullScreen && !document.fullscreenElement));
		}), document.addEventListener("webkitfullscreenchange", function() {
		  setFullscreenData(!!document.webkitIsFullScreen);
		}), document.addEventListener("mozfullscreenchange", function() {
		  setFullscreenData(!!document.mozFullScreen);
		}), document.addEventListener("msfullscreenchange", function() {
		  setFullscreenData(!!document.msFullscreenElement);
		});
		var supportsProgress = void 0 !== document.createElement("progress").max;
		if (!supportsProgress) {
		  progress.setAttribute("data-state", "fake");
		}
		var changeButtonState = function(value) {
		  if ("playpause" == value) {
		    if (video.paused || video.ended) {
		      playpause.setAttribute("data-state", "play");
		    } else {
		      playpause.setAttribute("data-state", "pause");
		    }
		  } else {
		    if ("mute" == value) {
		      mute.setAttribute("data-state", video.muted ? "unmute" : "mute");
		    } else {
		      if ("ended" == value) {
		        playpause.setAttribute("data-state", "ended");
		      }
		    }
		  }
		};*/

		let ec = "<?php echo $ec; ?>";
		let video = _("my_video_" + ec);
		let ppbtn = _("playPauseBtn");
		let tgl1 = _("tgl1");
		let og = document.querySelector(".orangeJuice");
		let ob = document.querySelector(".orangeBar");

		ppbtn.addEventListener("click", function togglePP(){
			if(ppbtn.getAttribute("status") == "play"){
				video.play();
				ppbtn.setAttribute("status", "pause");
				tgl1.src = "/images/pausebtn.svg";
			}else{
				video.pause();
				ppbtn.setAttribute("status", "play");
				tgl1.src = "/images/playbtn.svg";
			}
		});

		video.addEventListener("timeupdate", function update(){
			let jPos = video.currentTime / video.duration;
			og.style.width = jPos * 100 + "%";
			if(video.ended){
				ppbtn.setAttribute("status", "play");
				tgl1.src = "/images/playbtn.svg";
			}
		});

		ob.addEventListener("click", function(event) {
		  let percent = (event.pageX + ob.offsetLeft) / this.offsetWidth;
		  console.log(event.clientX);
		  video.currentTime = percent * video.duration;
		});


		function likeVideo(midiOutObj, name, address, connectionPool) {
		  var request = ajaxObj("POST", "/php_parsers/video_parser.php");
		  request.onreadystatechange = function() {
		    if (1 == ajaxReturn(request)) {
		      if ("like_success" == request.responseText) {
		        _(address).innerHTML = '<span id="likeBtnv_' + name + '" style="display: inline-block; margin-right: 5px;"><a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\'' + name + "','likeBtnv_" + name + "','ion_" + name + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike"></a></span>';
		        _("ion_" + name).innerHTML = '<p style="font-size: 12px !important; float: left; margin-top: 0px; margin-bottom: 0px;" id="ion_' + name + '">&#9658; You liked this video</p>';
		      } else {
		        if ("unlike_success" == request.responseText) {
		          _(address).innerHTML = '<span id="likeBtnv_' + name + '" style="display: inline-block; margin-right: 5px;"><a href="#" onclick="return false;" onmousedown="likeVideo(\'like\',\'' + name + "','likeBtnv_" + name + "','ion_" + name + '\')"><img src="/images/nf.png" width="18" height="18" title="Like"></a></span>';
		          _("ion_" + name).innerHTML = '<p style="font-size: 12px !important; float: left; margin-top: 0px; margin-bottom: 0px;" id="ion_' + name + '">&#9658; You did not like this video, yet</p>';
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
		  request.send("type=" + midiOutObj + "&id=" + name);
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