<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	require_once 'elist.php';

 	function vincentyGreatCircleDistance(
      $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 3959)
    {
      // convert from degrees to radians
      $latFrom = deg2rad($latitudeFrom);
      $lonFrom = deg2rad($longitudeFrom);
      $latTo = deg2rad($latitudeTo);
      $lonTo = deg2rad($longitudeTo);
    
      $lonDelta = $lonTo - $lonFrom;
      $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
      $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
    
      $angle = atan2(sqrt($a), $b);
     
      $number = $angle * $earthRadius;
      $number = round($number);
      return $number;
    }
    // Select user's lat and lon
    $sql = "SELECT lat, lon FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($lat,$lon);
    $stmt->fetch();
    $stmt->close();
    
	$status_ui = "";
	$statuslist = "";
	$statusid = "";
	$a = "a";
	$b = "b";
	$c = "c";
	// Get the length of each posts
	$p = $_SESSION["photo"];

	$isOwner = "No";
	if($u == $log_username && $user_ok == true){
		$isOwner = "Yes";
	}

	$isFriend = false;
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
}

	// This first query is just to get the total count of rows
	$sql = "SELECT COUNT(id) FROM photos_status WHERE account_name=? AND type = ? AND photo = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$u,$a,$p);
	$stmt->execute();
	$stmt->bind_result($rows);
	$stmt->fetch();
	$stmt->close();
	// Here we have the total row count
	// This is the number of results we want displayed per page
	$page_rows = 10;
	// This tells us the page number of our last page
	$last = ceil($rows/$page_rows);
	// This makes sure $last cannot be less than 1
	if($last < 1){
		$last = 1;
	}
	// Establish the $pagenum variable
	$pagenum = 1;
	// Get pagenum from URL vars if it is present, else it is = 1
	if(isset($_GET['pn'])){
		$pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
	}
	// This makes sure the page number isn't below 1, or more than our $last page
	if ($pagenum < 1) { 
	    $pagenum = 1; 
	} else if ($pagenum > $last) { 
	    $pagenum = $last;
	}
	// This sets the range of rows to query for the chosen $pagenum
	$limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	// Establish the $paginationCtrls variable
	$paginationCtrls = '';
	// If there is more than 1 page worth of results
	if($last != 1){
		/* First we check if we are on page one. If we are then we don't need a link to 
		   the previous page or the first page so we do nothing. If we aren't then we
		   generate links to the first page, and to the previous page. */
		if ($pagenum > 1) {
	        $previous = $pagenum - 1;
			$paginationCtrls .= '<a href="/photo_zoom/'.$p.'/'.$u.'&pn='.$previous.'#pposts">Previous</a> &nbsp; &nbsp; ';
			// Render clickable number links that should appear on the left of the target page number
			for($i = $pagenum-4; $i < $pagenum; $i++){
				if($i > 0){
			        $paginationCtrls .= '<a href="/photo_zoom/'.$p.'&u='.$u.'&pn='.$i.'#pposts">'.$i.'</a> &nbsp; ';
				}
		    }
	    }
		// Render the target page number, but without it being a link
		$paginationCtrls .= ''.$pagenum.' &nbsp; ';
		// Render clickable number links that should appear on the right of the target page number
		for($i = $pagenum+1; $i <= $last; $i++){
			$paginationCtrls .= '<a href="/photo_zoom/'.$p.'&u='.$u.'&pn='.$i.'#pposts">'.$i.'</a> &nbsp; ';
			if($i >= $pagenum+4){
				break;
			}
		}
		// This does the same as above, only checking if we are on the last page, and then generating the "Next"
	    if ($pagenum != $last) {
	        $next = $pagenum + 1;
	        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/photo_zoom/'.$p.'&u='.$u.'&pn='.$next.'#pposts">Next</a> ';
	    }
	}

	$sql = "SELECT COUNT(id) FROM photos_status WHERE photo = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$p);
	$stmt->execute();
	$stmt->bind_result($countRs);
	$stmt->fetch();
	$stmt->close();
	$toDis = "";
	if($countRs > 0){
		$toDis = '<p style="color: #999; text-align: center;">'.$countRs.' comments recorded</p>';
	}
	
	if($_SESSION["username"] != ""){
	$status_ui = '
	'.$toDis.'
	<textarea id="statustext" class="user_status" onfocus="showBtnDiv()" placeholder="What do you think about this photo?"></textarea>';
	$status_ui .= '<div id="uploadDisplay_SP"></div>';
	$status_ui .= '<div id="pbc">
					<div id="progressBar"></div>
					<div id="pbt"></div>
				   </div>';
	$status_ui .= '<div id="txt_holder"></div>';
	$status_ui .= '<div id="btns_SP" class="hiddenStuff" style="width: 90%;">';
		$status_ui .= '<span id="swithspan"><button id="statusBtn" class="btn_rply" onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')">Post</button></span>';
		$status_ui .= '<img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply" onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22" title="Upload A Photo" />';
		$status_ui .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox()">';
		$status_ui .= '<div class="clear"></div>';
		$status_ui.= generateEList($statusid, 'emojiBox', 'statustext');
	$status_ui .= '</div>';
	$status_ui .= '<div id="standardUpload" class="hiddenStuff">';
		$status_ui .= '<form id="image_SP" enctype="multipart/form-data" method="post">';
		$status_ui .= '<input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')" accept="image/*"/>';
		$status_ui .= '</form>';
	$status_ui .= '</div>';
	$status_ui .= '<div class="clear"></div>';
	}else{
	    $status_ui = "<p class='txtc' style='color: #999;'>Please <a href='/login'>log in</a> in order to leave a comment</p>";    
	}
	
	?>
	<?php
		$sql = "SELECT s.*,  u.avatar, u.country, u.online, u.lat, u.lon
		FROM photos_status AS s
		LEFT JOIN users AS u ON u.username = s.author
		WHERE s.photo = ? AND (s.account_name=? AND s.type=?)
		OR (s.account_name=? AND s.type=?)
		ORDER BY s.postdate DESC $limit";
	?>
	<?php 
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sssss",$p,$u,$a,$u,$c);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		while ($row = $result->fetch_assoc()) {
			//$stmt->close();
			$statusid = $row["id"];
			$type = $row["type"];
			$account_name = $row["account_name"];
			$author = $row["author"];
			$postdate_ = $row["postdate"];
			$postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
			$avatar = $row["avatar"];
			$class = "";
			if($author == $log_username){
			    $class = "class='round ptshov'";
			}else{
			    $class = 'id="round_2" class="ptshov"';
			}
			$fuco = $row["country"];
    		$ison = $row["online"];
    		$flat = $row["lat"];
    		$flon = $row["lon"];
    		$dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
    		$isonimg = '';
    		if($ison == "yes"){
    		    $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
    		}else{
    		    $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
    		}
    		if($avatar != ""){
    			$friend_pic = '/user/'.$author.'/'.$avatar.'';
    		} else {
    			$friend_pic = '/images/avdef.png';
    		}
    		$funames = $author;
    		if(strlen($funames) > 20){
    		    $funames = mb_substr($funames, 0, 16, "utf-8");
    		    $funames .= " ...";
    		}
    		if(strlen($fuco) > 20){
    		    $fuco = mb_substr($fuco, 0, 16, "utf-8");
    		    $fuco .= " ...";
    		}
    		$mgin = "";
    		if($log_username == $u){
    		    $mgin = "margin-left: -11px;";
    		}

    		if ($avatar == NULL) {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $author . '/' . $avatar;
            }

    		$sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("sss",$author,$author,$one);
    		$stmt->execute();
    		$stmt->bind_result($numoffs);
    		$stmt->fetch();
    		$stmt->close();
			$user_image = '<a href="/user/' . $author . '/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; border-radius: 50%;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';

			$data = $row["data"];
			$data_old = $row["data"];
			$data_old = nl2br($data_old);
		    $data_old = str_replace("&amp;","&",$data_old);
		    $data_old = stripslashes($data_old);
			$pos = strpos($data_old,'<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    		    
    		    $isex = false;
            	$sec_data = "";
            	$first_data = "";
            	if(strpos($data_old,'<img src="/permUploads/') !== false){
            	    $split = explode('<img src="/permUploads/',$data_old);
            	    clearstatcache();
                    $sec_data = '<img src="/permUploads/'.$split[1];
                    $first_data = $split[0];
                    $img = str_replace('"','',$split[1]); // remove double quotes
                    $img = str_replace('/>','',$img); // remove img end tag
                    $img = str_replace(' ','',$img); // remove spaces
                    $img = str_replace('<br>','',$img); // remove spaces
                    $img = trim($img);
                    $fn = "permUploads/".$img; // file name with dynamic variable in it
                    if(file_exists($fn)){
                        $isex = true;
                    }
            	}
    			if(strlen($data) > 1000){
    			    if($pos === false && $isex == false){
    				    $data = mb_substr($data, 0,1000, "utf-8");
        				$data .= " ...";
        				$data .= '&nbsp;<a id="toggle_'.$statusid.'" onclick="opentext(\''.$statusid.'\')">See More</a>';
        				$data_old = '<div id="lessmore_'.$statusid.'" class="lmml"><p id="status_text">'.$data_old.'&nbsp;<a id="toggle_'.$statusid.'" onclick="opentext(\''.$statusid.'\')">See Less</a></p></div>';
    			    }else{
    			        $data_old = "";
    			    }
    			}else{
    				$data_old = "";
    			}
			$data = nl2br($data);
		    $data = str_replace("&amp;","&",$data);
		    $data = stripslashes($data);
			$agoform = time_elapsed_string($postdate_);
			$statusDeleteButton = '';
			if($author == $log_username || $account_name == $log_username ){
				$statusDeleteButton = '<span id="sdb_'.$statusid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" onclick="return false;" onmousedown="deleteStatus(\''.$statusid.'\',\'status_'.$statusid.'\');" title="Delete Post And Its Replies">X</button></span> &nbsp; &nbsp;';
			}
			// Add share button
			$shareButton = "";
			if($log_username != "" && $author != $log_username && $account_name != $log_username){
				$shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus(\'' . $statusid . '\');" id="shareBlink" style="vertical-align: middle;">';
			}

			$isLike = false;
			if($user_ok == true){
				$like_check = "SELECT id FROM photo_stat_likes WHERE username=? AND status=? LIMIT 1";
				$stmt = $conn->prepare($like_check);
				$stmt->bind_param("si",$log_username,$statusid);
				$stmt->execute();
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
			if($numrows > 0){
			        $isLike = true;
				}
		    }

		    $stmt->close();
			// Add status like button
			$likeButton = "";
			$likeText = "";
			if($isLike == true){
				$likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\''.$statusid.'\',\'likeBtn_'.$statusid.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
				$likeText = '<span style="vertical-align: middle;">Dislike</span>';
			}else{
				$likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\''.$statusid.'\',\'likeBtn_'.$statusid.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
				$likeText = '<span style="vertical-align: middle;">Like</span>';
			}
			
			// GATHER UP ANY STATUS REPLIES
			$status_replies = "";
			$sql2 = "SELECT s.*, u.avatar, u.country, u.online, u.lat, u.lon 
					FROM photos_status AS s 
					LEFT JOIN users AS u ON u.username = s.author
					WHERE s.photo = ? 
					AND s.osid = ? 
					AND s.type = ? 
					ORDER BY s.postdate DESC";

			$stmt = $conn->prepare($sql2);
			$stmt->bind_param("sis",$p,$statusid,$b);
			$stmt->execute();
			$result2 = $stmt->get_result();
		    if($result2->num_rows > 0){
		        while ($row2 = $result2->fetch_assoc()) {
					$statusreplyid = $row2["id"];
                        $replyauthor = $row2["author"];
                        $replydata = $row2["data"];
                        $replydata = nl2br($replydata);
                        $replypostdate_ = $row2["postdate"];
                        $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
                        $avatar2 = $row2["avatar"];
                        $replydata = str_replace("&amp;", "&", $replydata);
                        $replydata = stripslashes($replydata);
                        $friend_pic = "";
                        if ($avatar2 == NULL) {
                            $friend_pic = '/images/avdef.png';
                        } else {
                            $friend_pic = '/user/' . $replyauthor . '/' . $avatar2;
                        }
                        $flat = $row["lat"];
                        $flon = $row["lon"];
                        $ison = $row["online"];
                        $fuco = $row["country"];
                        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                        $isonimg = '';
                        if ($ison == "yes") {
                            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                        } else {
                            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                        }
                        $funames = $replyauthor;
                        if (strlen($funames) > 20) {
                            $funames = mb_substr($funames, 0, 16, "utf-8");
                            $funames.= " ...";
                        }
                        if (strlen($fuco) > 20) {
                            $fuco = mb_substr($fuco, 0, 16, "utf-8");
                            $fuco.= " ...";
                        }
                        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sss", $replyauthor, $replyauthor, $one);
                        $stmt->execute();
                        $stmt->bind_result($numoffs);
                        $stmt->fetch();
                        $stmt->close();
					$user_image2 = '<a href="/user/'.urlencode($replyauthor).'/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tsrhov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';

					$replypostdate_ = $row2["postdate"];
					$replypostdate = strftime("%b %d, %Y", strtotime($replypostdate_));
					$agoformrply = time_elapsed_string($replypostdate_);
					$data_old_reply = $row2["data"];
					$data_old_reply = nl2br($data_old_reply);
				    $data_old_reply = str_replace("&amp;","&",$data_old_reply);
				    $data_old_reply = stripslashes($data_old_reply);
    				$isex = false;
                    	$sec_data = "";
                    	$first_data = "";
                    	if(strpos($data_old_reply,'<img src="/permUploads/') !== false){
                    	    $split = explode('<img src="/permUploads/',$data_old_reply);
                    	    clearstatcache();
                            $sec_data = '<img src="/permUploads/'.$split[1];
                            $first_data = $split[0];
                            $img = str_replace('"','',$split[1]); // remove double quotes
                            $img = str_replace('/>','',$img); // remove img end tag
                            $img = str_replace(' ','',$img); // remove spaces
                            $img = str_replace('<br>','',$img); // remove spaces
                            $img = trim($img);
                            $fn = "permUploads/".$img; // file name with dynamic variable in it
                            if(file_exists($fn)){
                                $isex = true;
                            }
                    	}
            			if(strlen($replydata) > 1000){
            			    if($isex == false){
            				    $replydata = mb_substr($replydata, 0,1000, "utf-8");
                				$replydata .= " ...";
                				$replydata .= '&nbsp;<a id="toggle_'.$statusreplyid.'" onclick="opentext(\''.$statusreplyid.'\')">See More</a>';
                				$data_old_reply = '<div id="lessmore_'.$statusreplyid.'" class="lmml"><p id="status_text">'.$data_old_reply.'&nbsp;<a id="toggle_'.$statusreplyid.'" onclick="opentext(\''.$statusreplyid.'\')">See Less</a></p></div>';
            			    }else{
            			        $data_old_reply = "";
            			    }
            			}else{
            				$data_old_reply = "";
            			}
    				$replydata = nl2br($replydata);
				    $replydata = str_replace("&amp;","&",$replydata);
				    $replydata = stripslashes($replydata);
					$replyDeleteButton = '';
					if($replyauthor == $log_username || $account_name == $log_username ){
						$replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_'.$statusreplyid.'\');" title="Delete Comment">X</button ></span>';
					}

					$isLike_reply = false;
					if($user_ok == true){
						$like_check_reply = "SELECT id FROM photo_reply_likes WHERE username=? AND reply=? LIMIT 1";
						$stmt = $conn->prepare($like_check_reply);
						$stmt->bind_param("si",$log_username,$statusreplyid);
						$stmt->execute();
						$stmt->store_result();
						$stmt->fetch();
						$numrows = $stmt->num_rows;
					if($numrows > 0){
					        $isLike_reply = true;
						}
				    }

				    $stmt->close();

					// Add reply like button
					$likeButton_reply = "";
					$likeText_reply = "";
					if($isLike_reply == true){
						$likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\''.$statusreplyid.'\',\'likeBtn_reply_'.$statusreplyid.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
						$likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
					}else{
						$likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\''.$statusreplyid.'\',\'likeBtn_reply_'.$statusreplyid.'\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
						$likeText_reply = '<span style="vertical-align: middle;">Like</span>';
					}
					$sql = "SELECT COUNT(id) FROM photo_reply_likes WHERE reply = ? AND photo = ?";
				    $stmt = $conn->prepare($sql);
				    $stmt->bind_param("is",$statusreplyid,$p);
				    $stmt->execute();
				    $stmt->bind_result($rpycount);
				    $stmt->fetch();
				    $stmt->close();
				    $rpycl = ''.$rpycount;
				    
				    $replyLog = "";
			    $statusLog = "";
			    if($_SESSION["username"] != ""){
			        $replyLog = '<span id="likeBtn_reply_'.$statusreplyid.'" class="likeBtn">'
							.$likeButton_reply.'
							<span style="vertical-align: middle;">'.$likeText_reply.'</span>
						</span>';
			    }

					$status_replies .= '
					<div id="reply_'.$statusreplyid.'" class="reply_boxes">
						<div>'.$replyDeleteButton.'
						<p id="float">
							<b class="sreply">Reply: </b>
							<b class="rdate">
								<span class="tooLong">'.$replypostdate.'</span> ('.$agoformrply.' ago)
							</b>
						</p>'.$user_image2.'
						<p id="reply_text">
							<b class="sdata" id="hide_reply_'.$statusreplyid.'">'.$replydata.''.$data_old_reply.'
							</b>
						</p>

						<hr class="dim">
                        '.$replyLog.'
						<div style="float: left; padding: 0px 10px 0px 10px;">
                            <b class="ispan" id="ipanr_' . $statusreplyid . '">' . $rpycl . ' likes</b>
                        </div>
                        <div class="clear"></div>
						</div>
					</div>';
		        }
		    }

		    // Count status likes
		    $sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE status = ? AND photo = ?";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("is",$statusid,$p);
		    $stmt->execute();
		    $stmt->bind_result($count);
		    $stmt->fetch();
		    $stmt->close();
		    $cl = ''.$count;

		    // Count the replies
		    $sql = "SELECT COUNT(id) FROM photos_status WHERE type = ? AND account_name = ? AND osid = ?";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("ssi",$b,$u,$statusid);
		    $stmt->execute();
		    $stmt->bind_result($countrply);
		    $stmt->fetch();
		    $stmt->close();

		    $crply = ''.$countrply;

		    $showmore = "";
		    if($countrply > 0){
		    	$showmore = '<div class="showrply"><a id="showreply_'.$statusid.'" onclick="showReply('.$statusid.','.$crply.')">Show replies ('.$crply.')</a></div>';
		    }
		    
		    if($_SESSION["username"] != ""){
	        $statusLog = '<span id="likeBtn_'.$statusid.'" class="likeBtn">
							'.$likeButton.'
							<span style="vertical-align: middle;">'.$likeText.'</span>
						</span>

						<div class="shareDiv">
                            ' . $shareButton . '
                            <span style="vertical-align: middle;">Share</span>
                        </div>';
	    }
				$statuslist .= '
					<div id="status_'.$statusid.'" class="status_boxes">
						<div>'.$statusDeleteButton.'
							<p id="status_date">
								<b class="status_title">Post: </b>
								<b class="pdate">
									<span class="tooLong">'.$postdate.'</span> ('.$agoform.' ago)
								</b>
							</p>'.$user_image.'
						<div id="sdata_'.$statusid.'">
							<p id="status_text">
								<b class="sdata" id="hide_'.$statusid.'">
									'.$data.''.$data_old.'
								</b>
							</p>
						</div>

						<hr class="dim">

					    '.$statusLog.'

                        <div style="float: left; padding: 0px 10px 0px 10px;">
                            <b class="ispan" id="ipanf_' . $statusid . '">
                                ' . $cl . ' likes
                            </b>
                        </div>
                        <div class="clear"></div>
				</div>'.$showmore.'<span id="allrply_'.$statusid.'" class="hiderply">'.$status_replies.'</span>
				</div>';
			if($isFriend == true || $log_username == $u){
			    $statuslist .= '<textarea id="replytext_'.$statusid.'" class="replytext" onfocus="showBtnDiv_reply('.$statusid.')" placeholder="Write a comment"></textarea>';
				$statuslist .= '<div id="uploadDisplay_SP_reply_'.$statusid.'"></div>';
				$statuslist .= '<div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff rply_joiner">';
					$statuslist .= '<span id="swithidbr_'.$statusid.'"><button id="replyBtn_'.$statusid.'" class="btn_rply" onclick="replyToStatus('.$statusid.',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button></span>';
					$statuslist .= '<img src="/images/camera.png" id="triggerBtn_SP_reply" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22" title="Upload A Photo" />';
					$statuslist .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$statusid.')">';
					$statuslist .= '<div class="clear"></div>';
					$statuslist.= generateEList($statusid, 'emojiBox_reply_' . $statusid . '', 'replytext_'.$statusid.'');
		
				$statuslist .= '</div>';
				$statuslist .= '<div id="standardUpload_reply" class="hiddenStuff">';
					$statuslist .= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
					$statuslist .= '<input type="file" name="FileUpload" id="fu_SP_reply" onchange="doUpload_reply(\'fu_SP_reply\', '.$statusid.')" accept="image/*"/>';
					$statuslist .= '</form>';
				$statuslist .= '</div>';	
			}
		}
	}else{
		if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
			echo "<p style='text-align: center; color: #999;'>Be the first one who post something!</p>";
		}
	}
?>
<script type="text/javascript">
	var us = "less";
	function showReply(name, index) {
	  if ("less" == us) {
	    _("showreply_" + name).innerText = "Hide replies (" + index + ")";
	    _("allrply_" + name).style.display = "block";
	    us = "more";
	  } else {
	    if ("more" == us) {
	      _("showreply_" + name).innerText = "Show replies (" + index + ")";
	      _("allrply_" + name).style.display = "none";
	      us = "less";
	    }
	  }
	}
	var statreply = "less";
	function opentext_reply(name) {
	  if ("less" == stat) {
	    _("lessmore_reply_" + name).style.display = "block";
	    _("toggle_reply_" + name).innerText = "See Less";
	    _("hide_reply_" + name).style.display = "none";
	    stat = "more";
	  } else {
	    if ("more" == stat) {
	      _("lessmore_reply_" + name).style.display = "none";
	      _("toggle_reply_" + name).innerText = "See More";
	      _("hide_reply_" + name).style.display = "block";
	      stat = "less";
	    }
	  }
	}
	function openEmojiBox_reply(name) {
	  var cancel = _("emojiBox_reply_" + name);
	  if ("block" == cancel.style.display) {
	    cancel.style.display = "none";
	  } else {
	    cancel.style.display = "block";
	  }
	}
	function openEmojiBox() {
	  var cancel = _("emojiBox");
	  if ("block" == cancel.style.display) {
	    cancel.style.display = "none";
	  } else {
	    cancel.style.display = "block";
	  }
	}
	function insertEmoji(type, value) {
	  var node = document.getElementById(type);
	  if (node) {
	    var newTop = node.scrollTop;
	    var pos = 0;
	    var undefined = node.selectionStart || "0" == node.selectionStart ? "ff" : !!document.selection && "ie";
	    if ("ie" == undefined) {
	      node.focus();
	      var oSel = document.selection.createRange();
	      oSel.moveStart("character", -node.value.length);
	      pos = oSel.text.length;
	    } else {
	      if ("ff" == undefined) {
	        pos = node.selectionStart;
	      }
	    }
	    var left = node.value.substring(0, pos);
	    var right = node.value.substring(pos, node.value.length);
	    if (node.value = left + value + right, pos = pos + value.length, "ie" == undefined) {
	      node.focus();
	      var range = document.selection.createRange();
	      range.moveStart("character", -node.value.length);
	      range.moveStart("character", pos);
	      range.moveEnd("character", 0);
	      range.select();
	    } else {
	      if ("ff" == undefined) {
	        node.selectionStart = pos;
	        node.selectionEnd = pos;
	        node.focus();
	      }
	    }
	    node.scrollTop = newTop;
	  }
	}
	function deleteStatus(id, status) {
	  if (1 != confirm("Press OK to confirm deletion of this status and its replies")) {
	    return false;
	  }
	  var xhr = ajaxObj("POST", "/php_parsers/photo_status_system.php");
	  xhr.onreadystatechange = function() {
	    if (1 == ajaxReturn(xhr)) {
	      if ("delete_ok" == xhr.responseText) {
	        _(status).style.display = "none";
	        _("replytext_" + id).style.display = "none";
	        _("replyBtn_" + id).style.display = "none";
	      } else {
	        alert(xhr.responseText);
	      }
	    }
	  };
	  xhr.send("action=delete_status&statusid=" + id);
	}
	function deleteReply(result, data) {
	  if (1 != confirm("Press OK to confirm deletion of this reply")) {
	    return false;
	  }
	  var res = ajaxObj("POST", "/php_parsers/photo_status_system.php");
	  res.onreadystatechange = function() {
	    if (1 == ajaxReturn(res)) {
	      if ("delete_ok" == res.responseText) {
	        _(data).style.display = "none";
	      } else {
	        alert(res.responseText);
	      }
	    }
	  };
	  res.send("action=delete_reply&replyid=" + result);
	}
	var hasImage = "";
	function showBtnDiv() {
	  _("btns_SP").style.display = "block";
	}
	function showBtnDiv_reply(name) {
	  _("btns_SP_reply_" + name).style.display = "block";
	}
	function doUpload(data) {
	  var opts = _(data).files[0];
	  if ("" == opts.name) {
	    return false;
	  }
	  if ("image/jpeg" != opts.type && "image/png" != opts.type && "image/gif" != opts.type && "image/jpg" != opts.type) {
	    return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
	    document.body.style.overflow = "hidden", false;
	  }
	  _("triggerBtn_SP").style.display = "none";
	  _("pbc").style.display = "block";
	  var fd = new FormData;
	  fd.append("stPic", opts);
	  var request = new XMLHttpRequest;
	  request.upload.addEventListener("progress", progressHandler, false);
	  request.addEventListener("load", completeHandler, false);
	  request.addEventListener("error", errorHandler, false);
	  request.addEventListener("abort", abortHandler, false);
	  request.open("POST", "/php_parsers/photo_system.php");
	  request.send(fd);
	}

	function progressHandler(event) {
	  var inDays = event.loaded / event.total * 100;
	  var percent_progress = Math.round(inDays);
	  _("progressBar").style.width = percent_progress + "%";
	  _("pbt").innerHTML = percent_progress + "%";
	}
	function completeHandler(event) {
	  var formattedDirections = event.target.responseText.split("|");
	  _("progressBar").style.width = "0%";
	  _("pbc").style.display = "none";
	  if ("upload_complete" == formattedDirections[0]) {
	    hasImage = formattedDirections[1];
	    _("uploadDisplay_SP").innerHTML = '<img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage" />';
	  } else {
	    _("uploadDisplay_SP").innerHTML = formattedDirections[0];
	    _("triggerBtn_SP").style.display = "block";
	  }
	}
	function errorHandler(callback) {
	  _("uploadDisplay_SP").innerHTML = "Upload Failed";
	  _("triggerBtn_SP").style.display = "block";
	}
	function abortHandler(canCreateDiscussions) {
	  _("uploadDisplay_SP").innerHTML = "Upload Aborted";
	  _("triggerBtn_SP").style.display = "block";
	}
	function doUpload_reply(body, sharpCos) {
	  var opts = _(body).files[0];
	  if ("" == opts.name) {
	    return false;
	  }
	  if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type && "image/jpg" != opts.type) {
	    return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
	    document.body.style.overflow = "hidden", false;
	  }
	  _("triggerBtn_SP_reply").style.display = "none";
	  var fd = new FormData;
	  fd.append("stPic_reply", opts);
	  var xhr = new XMLHttpRequest;
	  xhr.upload.addEventListener("progress", progressHandler_reply, false);
	  xhr.addEventListener("load", completeHandler_reply, false);
	  xhr.addEventListener("error", errorHandler_reply, false);
	  xhr.addEventListener("abort", abortHandler_reply, false);
	  xhr.open("POST", "/php_parsers/photo_system.php");
	  xhr.send(fd);
	}
	function progressHandler_reply(event) {
	  var inDays = event.loaded / event.total * 100;
	  var o = "<p>" + Math.round(inDays) + "% uploaded please wait ...</p>";
	  _("overlay").style.display = "block";
	  _("overlay").style.opacity = .5;
	  _("dialogbox").style.display = "block";
	  _("dialogbox").innerHTML = "<b>Your uploading image status</b><p>" + o + "</p>";
	}
	function completeHandler_reply(event) {
	  var t = event.target.responseText.split("|");
	  if ("upload_complete_reply" == t[0]) {
	    hasImage = t[1];
	    _("overlay").style.display = "block";
	    _("overlay").style.opacity = .5;
	    _("dialogbox").style.display = "block";
	    _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="/tempUploads/' + t[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	    document.body.style.overflow = "hidden";
	  } else {
	    _("triggerBtn_SP_reply").style.display = "block";
	  }
	}
	function errorHandler_reply(canCreateDiscussions) {
	  _("uploadDisplay_SP_reply_").innerHTML = "Upload Failed";
	  _("triggerBtn_SP_reply").style.display = "block";
	}
	function abortHandler_reply(canCreateDiscussions) {
	  _("uploadDisplay_SP_reply").innerHTML = "Upload Aborted";
	  _("triggerBtn_SP_reply").style.display = "block";
	}
	function triggerUpload(event, file) {
	  event.preventDefault();
	  _(file).click();
	}
	function triggerUpload_reply(event, t) {
	  event.preventDefault();
	  _(t).click();
	}
	function shareStatus(type) {
	  var request = ajaxObj("POST", "/php_parsers/photo_status_system.php");
	  request.onreadystatechange = function() {
	    if (1 == ajaxReturn(request)) {
	      if ("share_ok" == request.responseText) {
	        _("overlay").style.display = "block";
	        _("overlay").style.opacity = .5;
	        _("dialogbox").style.display = "block";
	        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	        document.body.style.overflow = "hidden";
	      } else {
	        _("overlay").style.display = "block";
	        _("overlay").style.opacity = .5;
	        _("dialogbox").style.display = "block";
	        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	        document.body.style.overflow = "hidden";
	      }
	    }
	  };
	  request.send("action=share&id=" + type);
	}
	function toggleLike(e, o, t) {
	  var result = ajaxObj("POST", "/php_parsers/like_photo_system.php");
	  result.onreadystatechange = function() {
	    if (1 == ajaxReturn(result)) {
	      if ("like_success" == result.responseText) {
	        _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + o + "','likeBtn_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanf_" + o).innerText = ++e + " likes";
	      } else {
	        if ("unlike_success" == result.responseText) {
	         _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + o + "','likeBtn_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
            e = (e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanf_" + o).innerText = --e + " likes";
	        } else {
	          _("overlay").style.display = "block";
	          _("overlay").style.opacity = .5;
	          _("dialogbox").style.display = "block";
	          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	          document.body.style.overflow = "hidden";
	          _(t).innerHTML = "Try again later";
	        }
	      }
	    }
	  };
	  result.send("type=" + e + "&id=" + o);
	}
	function toggleLike_reply(e, o, t) {
	  var result = ajaxObj("POST", "/php_parsers/like_reply_photo_system.php");
	  result.onreadystatechange = function() {
	    if (1 == ajaxReturn(result)) {
	      if ("like_reply_success" == result.responseText) {
	        _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + o + "','likeBtn_reply_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + o).innerText = ++e + " likes";
	      } else {
	        if ("unlike_reply_success" == result.responseText) {
	          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + o + "','likeBtn_reply_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
            e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + o).innerText = --e + " likes";
	        } else {
	          _("overlay").style.display = "block";
	          _("overlay").style.opacity = .5;
	          _("dialogbox").style.display = "block";
	          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	          document.body.style.overflow = "hidden";
	          _(t).innerHTML = "Try again later";
	        }
	      }
	    }
	  };
	  result.send("type=" + e + "&id=" + o);
	}
	window.onbeforeunload = function() {
	  if ("" != hasImage) {
	    return "You have not posted your image";
	  }
	};
	var stat = "less";
	function opentext(name) {
	  if ("less" == stat) {
	    _("lessmore_" + name).style.display = "block";
	    _("toggle_" + name).innerText = "See Less";
	    _("hide_" + name).style.display = "none";
	    stat = "more";
	  } else {
	    if ("more" == stat) {
	      _("lessmore_" + name).style.display = "none";
	      _("toggle_" + name).innerText = "See More";
	      _("hide_" + name).style.display = "block";
	      stat = "less";
	    }
	  }
	}
	function closeDialog() {
	  _("dialogbox").style.display = "none";
	  _("overlay").style.display = "none";
	  _("overlay").style.opacity = 0;
	  document.body.style.overflow = "auto";
	}
	function postToStatus(cond, thencommands, pollProfileId, userId) {
	  var c = _(userId).value;
	  if ("" == c && "" == hasImage) {
	    return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
	  }
	  var line = "";
	  if ("" != c) {
	    line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
	  }
	  if ("" == line && "" != hasImage) {
	    c = "||na||";
	    line = '<img src="/permUploads/' + hasImage + '" />';
	  } else {
	    if ("" != line && "" != hasImage) {
	      line = line + ('<br /><img src="/permUploads/' + hasImage + '" />');
	    } else {
	      hasImage = "na";
	    }
	  }
	  _("swithspan").innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
	  var xhr = ajaxObj("POST", "/php_parsers/photo_status_system.php");
	  xhr.onreadystatechange = function() {
	    if (1 == ajaxReturn(xhr)) {
	      var tilesToCheck = xhr.responseText.split("|");
	      if ("post_ok" == tilesToCheck[0]) {
	        var t = tilesToCheck[1];
	        var newHTML = _("statusarea").innerHTML;
	        _("statusarea").innerHTML = '<div id="status_' + t + '" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_' + t + '"><button onclick="return false;" class="delete_s" onmousedown="deleteStatus(\'' + t + "','status_" + t + '\');" title="Delete Status And Its Replies">X</button></span><br />' + line + "</div></div>" + newHTML;
	        _("swithspan").innerHTML = "<button id=\"statusBtn\" onclick=\"postToStatus('status_post','a','<?php echo $u; ?>','statustext')\">Post</button>";
	        _(userId).value = "";
	        _("triggerBtn_SP").style.display = "block";
	        _("btns_SP").style.display = "none";
	        _("uploadDisplay_SP").innerHTML = "";
	        _("fu_SP").value = "";
	        _("txt_holder").innerHTML = "";
	        hasImage = "";
	      } else {
	        _("overlay").style.display = "block";
	        _("overlay").style.opacity = .5;
	        _("dialogbox").style.display = "block";
	        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status post. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	        document.body.style.overflow = "hidden";
	      }
	    }
	  };
	  xhr.send("action=" + cond + "&type=" + thencommands + "&user=" + pollProfileId + "&data=" + c + "&image=" + hasImage);
	}
	function replyToStatus(id, supr, o, dizhi) {
	  var c = _(o).value;
	  if ("" == c && "" == hasImage) {
	    return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
	  }
	  var line = "";
	  if ("" != c) {
	    line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
	  }
	  if ("" == line && "" != hasImage) {
	    c = "||na||";
	    line = '<img src="/permUploads/' + hasImage + '" />';
	  } else {
	    if ("" != line && "" != hasImage) {
	      line = line + ('<br /><img src="/permUploads/' + hasImage + '" />');
	    } else {
	      hasImage = "na";
	    }
	  }
	  _("swithidbr_" + id).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
	  var xhr = ajaxObj("POST", "/php_parsers/photo_status_system.php");
	  xhr.onreadystatechange = function() {
	    if (1 == ajaxReturn(xhr)) {
	      var actionsLengthsArray = xhr.responseText.split("|");
	      if ("reply_ok" == actionsLengthsArray[0]) {
	        var l = actionsLengthsArray[1];
	        c = c.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
	        _("status_" + id).innerHTML += '<div id="reply_' + l + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_" + l + '\');" title="Delete Comment">X</button></span><br />' + line + "</div></div><br /><br />";
	        _("swithidbr_" + id).innerHTML = '<button id="replyBtn_' + id + '" class="btn_rply" onclick="replyToStatus(\'' + id + "','<?php echo $u; ?>','replytext_" + id + "',this)\">Reply</button>";
	        _(o).value = "";
	        _("triggerBtn_SP_reply").style.display = "block";
	        _("btns_SP_reply_" + id).style.display = "none";
	        _("uploadDisplay_SP_reply_" + id).innerHTML = "";
	        _("fu_SP_reply").value = "";
	        hasImage = "";
	      } else {
	        _("overlay").style.display = "block";
	        _("overlay").style.opacity = .5;
	        _("dialogbox").style.display = "block";
	        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
	        document.body.style.overflow = "hidden";
	      }
	    }
	  };
	  xhr.send("action=status_reply&sid=" + id + "&user=" + supr + "&data=" + c + "&image=" + hasImage);
	}
</script>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statuslist; ?>
</div>
<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>