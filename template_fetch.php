<?php
  while ($row = $result->fetch_assoc()) {
		$statusid = $row["id"];
		$account_name = $row["account_name"];
		$author = $row["author"];
		$postdate_ = $row["postdate"];
		$postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
		$avatar = $row["avatar"];
		$class = "";
		if($author == $log_username){
		    $class = "round";
		}else{
		    $class = "round_2";
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

		if ($avatar == NULL) {
            $friend_pic = '/images/avdef.png';
        } else {
            $friend_pic = '/user/' . $author . '/' . $avatar;
        }

		$sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$author,$author,$one);
		$stmt->execute();
		$stmt->bind_result($numoffs);
		$stmt->fetch();
		$stmt->close();

		$user_image = '<a href="/user/'.$author.'/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';

		$agoform = time_elapsed_string($postdate_);
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
		$statusDeleteButton = '';
		if($author == $log_username || $account_name == $log_username ){
			$statusDeleteButton = '<span id="sdb_'.$statusid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" onclick="return false;" onmousedown="deleteStatus(\''.$statusid.'\',\'status_'.$statusid.'\');" title="Delete Post And Its Replies">X</button></span>';
		}
		// Add share button
		$shareButton = "";
		if($log_username != "" && $author != $log_username && $account_name != $log_username){
			$shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus(\'' . $statusid . '\');" id="shareBlink" style="vertical-align: middle;">';
		}

		$isLike = false;
		if($user_ok == true){
			$like_check = "SELECT id FROM status_likes WHERE username=? AND status=? LIMIT 1";
			$stmt = $conn->prepare($like_check);
			$stmt->bind_param("si",$log_username,$statusid);
			$stmt->execute();
			$stmt->store_result();
			$stmt->fetch();
			$numrows = $stmt->num_rows;
		if($numrows > 0){
		        $isLike = true;
			}
			$stmt->close();
	    }
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
		$sql2 = "SELECT s.*, u.avatar, u.lat, u.lon, u.country, u.online
				FROM status AS s
				LEFT JOIN users AS u ON u.username = s.author
				WHERE s.osid = ? 
				AND s.type = ? 
				ORDER BY s.postdate ASC";
		$stmt = $conn->prepare($sql2);
		$stmt->bind_param("is",$statusid,$b);
		$stmt->execute();
		$result2 = $stmt->get_result();
	    if($result2->num_rows > 0){
	        while ($row2 = $result2->fetch_assoc()) {
				$statusreplyid = $row2["id"];
				$replyauthor = $row2["author"];
				$replydata = $row2["data"];
				$avatar2 = $row2["avatar"];
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
        		if($avatar2 != ""){
        			$friend_pic = '/user/'.$replyauthor.'/'.$avatar2.'';
        		} else {
        			$friend_pic = '/images/avdef.png';
        		}
        		$funames = $replyauthor;
        		if(strlen($funames) > 20){
        		    $funames = mb_substr($funames, 0, 16, "utf-8");
        		    $funames .= " ...";
        		}
        		if(strlen($fuco) > 20){
        		    $fuco = mb_substr($fuco, 0, 16, "utf-8");
        		    $fuco .= " ...";
        		}

        		$friend_pic = "";
                if ($avatar2 == NULL) {
                    $friend_pic = '/images/avdef.png';
                } else {
                    $friend_pic = '/user/' . $replyauthor . '/' . $avatar2;
                }

        		$sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
        		$stmt = $conn->prepare($sql);
        		$stmt->bind_param("sss",$replyauthor,$replyauthor,$one);
        		$stmt->execute();
        		$stmt->bind_result($numoffs);
        		$stmt->fetch();
        		$stmt->close();
				$user_image2 = '<a href="/user/'.$replyauthor.'/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';

				$replypostdate_ = $row2["postdate"];
				$replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
				$replyDeleteButton = '';
				if($replyauthor == $log_username || $account_name == $log_username ){
					$replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_'.$statusreplyid.'\');" title="Delete Comment">X</button ></span>';
				}
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

				$isLike_reply = false;
				if($user_ok == true){
					$like_check_reply = "SELECT id FROM reply_likes WHERE username=? AND reply=? LIMIT 1";
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

				// Count reply likes
				$sql = "SELECT COUNT(id) FROM reply_likes WHERE reply = ?";
			    $stmt = $conn->prepare($sql);
			    $stmt->bind_param("i",$statusreplyid);
			    $stmt->execute();
			    $stmt->bind_result($rpycount);
			    $stmt->fetch();
			    $stmt->close();
			    $rpycl = ''.$rpycount;
			    
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

	    // Count likes
	    $sql = "SELECT COUNT(id) FROM status_likes WHERE status = ?";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("i",$statusid);
	    $stmt->execute();
	    $stmt->bind_result($count);
	    $stmt->fetch();
	    $stmt->close();
	    $cl = ''.$count;

	    // Count the replies
	    $sql = "SELECT COUNT(id) FROM status WHERE type = ? AND account_name = ? AND osid = ?";
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

			$statuslist .= '<div id="status_'.$statusid.'" class="status_boxes">
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
		    $statuslist .= '<textarea id="replytext_'.$statusid.'" class="replytext" onfocus="showBtnDiv_reply(\''.$statusid.'\')" placeholder="Write a comment"></textarea>';
			$statuslist .= '<div id="uploadDisplay_SP_reply_'.$statusid.'"></div>';
			$statuslist .= '<div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff rply_joiner">';
				$statuslist .= '<span id="swithidbr_'.$statusid.'"><button id="replyBtn_'.$statusid.'" class="btn_rply" onclick="replyToStatus(\''.$statusid.'\',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button></span>';
				$statuslist .= '<img src="/images/camera.png" id="triggerBtn_SP_reply" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22" title="Upload A Photo" />';
				$statuslist .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$statusid.')">';
				$statuslist .= '<div class="clear"></div>';
				$statuslist.= generateEList($statusid, 'emojiBox_reply_' . $statusid . '', 'replytext_'.$statusid.'');
			$statuslist .= '</div>';
			$statuslist .= '<div id="standardUpload_reply" class="hiddenStuff">';
				$statuslist .= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
				$statuslist .= '<input type="file" name="FileUpload" id="fu_SP_reply" onchange="doUpload_reply(\'fu_SP_reply\', \''.$statusid.'\')" accept="image/*"/>';
				$statuslist .= '</form>';
			$statuslist .= '</div>';
		}
	}
?>
