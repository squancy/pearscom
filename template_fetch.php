<?php
  function countReplies_stat($conn, $b, $u, $statusid) {
    $sql = "SELECT COUNT(id) FROM status WHERE type = ? AND account_name = ? AND osid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $b, $u, $statusid);
    $stmt->execute();
    $stmt->bind_result($countrply);
    $stmt->fetch();
    $stmt->close();
    return $countrply;
  }

  while ($row = $result->fetch_assoc()) {
		$statusid = $row["id"];
		$account_name = $row["account_name"];
		$author = $row["author"];
		$postdate_ = $row["postdate"];
		$postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
		$avatar = $row["avatar"];
	  $fuco = $row["country"];
		$ison = $row["online"];
		$flat = $row["lat"];
		$flon = $row["lon"];

    // Distance between 2 users based on their lat and lon coords
		$dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
		$isonimg = isOn($ison);

    // Avatar pic of user
    $friend_pic = avatarImg($author, $avatar);

		$funames = $author;

    $funames = wrapText($funames, 20);
    $fuco = wrapText($fuco, 20);

    $numoffs = getUsersFriends($conn, $u, $log_username);

    // Avatar pic of user + popup on desktop
		$user_image = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
      $numoffs, true); 

		$agoform = time_elapsed_string($postdate_);
		$data = $row["data"];
		$data_old = sanitizeData($row["data"]);
		$pos = strpos($data_old,
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    		    
	  $isex = clearImg($data_old);
   
    // Wrap post text if longer than 1,000 chars
    list($data, $data_old) = seeHideWrap($data, $data_old, $statusid, $pos, $isex);
		
		$data = sanitizeData($data); 
    $statusDeleteButton = '';

    // Status delete button
		$statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid, true,
      true, '/php_parsers/status_system.php');

    // Add share button
		$shareButton = genShareBtn($log_username, $author, $statusid,
      '/php_parsers/status_system.php'); 

    // Check if user liked the post
		$isLike = userLiked($user_ok, $conn, $statusid, $log_username, true,
      'status_likes', 'status');

		// Add status like button
	  list($likeButton, $likeText) = genStatLikeBtn($isLike, $statusid, true, false,
      '/php_parsers/like_system.php');	

		// Gather status replies
		$status_replies = "";
		$sql2 = "SELECT s.*, u.avatar, u.lat, u.lon, u.country, u.online
				FROM status AS s
				LEFT JOIN users AS u ON u.username = s.author
				WHERE s.osid = ? 
				AND s.type = ? 
				ORDER BY s.postdate ASC";
		$stmt = $conn->prepare($sql2);
		$stmt->bind_param("is", $statusid, $b);
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

        // Get distance between 2 users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = isOn($ison);
       
        // Get reply author's avatar
        $friend_pic = avatarImg($replyauthor, $avatar2);
        $funames = $replyauthor;
        
        $funames = wrapText($funames, 20);
        $fuco = wrapText($fuco, 20);
    
        $numoffs = getUsersFriends($conn, $replyauthor, $log_username);

				$user_image2 = genUserImage($replyauthor, $friend_pic, $funames, $isonimg, $fuco,
          $dist, $numoffs, true);

				$replypostdate_ = $row2["postdate"];
				$replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));

        // Reply delete button
				$replyDeleteButton = '';
        $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
          $statusreplyid, false, true, '/php_parsers/status_system.php');

				$agoformrply = time_elapsed_string($replypostdate_);
				$data_old_reply = sanitizeData($row2["data"]);
				$isex = clearImg($data_old_reply);
        
        // Wrap reply text if longer than 1,000 chars
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);

        $replydata = sanitizeData($replydata); 

        // Check if user liked the reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false,
          'reply_likes', 'reply');

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false, false, '/php_parsers/like_reply_system.php');

        // Count reply likes
        $rpycl = getAllLikes('reply_likes', 'reply', $statusreplyid, $conn);

        $replyLog = "";
        $statusLog = "";
        
        $replyLog = genLog($_SESSION['username'], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);
			    
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
							<b class="sdata" id="hide_reply_'.$statusreplyid.'">
                '.$replydata.''.$data_old_reply.'
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
	    $cl = getAllLikes('status_likes', 'status', $statusid, $conn);

	    // Count the replies
	    $crply = countReplies_stat($conn, $b, $u, $statusid);

      $showmore = genShowMore($crply, $statusid);
      $statusLog = genLog($_SESSION['username'], $statusid, $likeButton, $likeText, true,
        $shareButton);
	    
        $statuslist .= '
        <div id="status_'.$statusid.'" class="status_boxes">
          <div>'.$statusDeleteButton.'
            <p id="status_date">
              <b class="status_title">Post: </b>
              <b class="pdate">
                <span class="tooLong">'.$postdate.'</span> ('.$agoform.' ago)
              </b>
            </p>

          '.$user_image.'

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
      </div>
      '.$showmore.'
      <span id="allrply_'.$statusid.'" class="hiderply">'.$status_replies.'</span>
    </div>';

		if($isFriend == true || $log_username == $u){
	    $statuslist .= '
        <textarea id="replytext_'.$statusid.'" class="replytext"
          onfocus="showBtnDiv_reply(\''.$statusid.'\')"
          placeholder="Write a comment"></textarea>
        <div id="uploadDisplay_SP_reply_'.$statusid.'"></div>
        <div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff rply_joiner">
        <span id="swithidbr_'.$statusid.'">
          <button id="replyBtn_'.$statusid.'" class="btn_rply"
            onclick="replyToStatus(\''.$statusid.'\',\''.$u.'\',\'replytext_'.$statusid.'\',this,false,\'/php_parsers/status_system.php\')">Reply</button>
        </span>
        <img src="/images/camera.png" id="triggerBtn_SP_reply" class="triggerBtnreply"
          onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22"
          title="Upload A Photo" />
        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$statusid.')">
        <div class="clear"></div>
      ';
		  $statuslist .= generateEList($statusid, 'emojiBox_reply_' . $statusid . '',
        'replytext_'.$statusid.'');
			$statuslist .= '</div>';
			$statuslist .= '
        <div id="standardUpload_reply" class="hiddenStuff">
          <form id="image_SP_reply" enctype="multipart/form-data" method="post">
            <input type="file" name="FileUpload" id="fu_SP_reply"
              onchange="doUpload_reply(\'fu_SP_reply\', \''.$statusid.'\')" accept="image/*"/>
          </form>
        </div>
      ';
		}
	}
?>
