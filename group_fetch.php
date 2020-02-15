<?php
  while ($row = $result_new->fetch_assoc()) {
    $post_id = $row["grouppost_id"];
    $post_auth = $row["author"];
    $post_type = $row["type"];
    $post_data = $row["data"];
    $post_date_ = $row["pdate"];
    $post_date = strftime("%R, %b %d, %Y", strtotime($post_date_));
    $post_avatar = $row["avatar"];
    $fuco = $row["country"];
    $ison = $row["online"];
    $flat = $row["lat"];
    $flon = $row["lon"];

    // Get distance between users
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
    $isonimg = isOn($ison);

    $friend_pic = avatarImg($post_auth, $avatar);
    $funames = $post_auth;
    
    $funames = wrapText($funames, 20);
    $fuco = wrapText($fuco, 20);

    $numoffs = numOfFriends($conn, $post_auth);

    $avatar_pic = avatarImg($post_auth, $post_avatar);
    $user_image = "";
    $agoform = time_elapsed_string($post_date_);

    if($post_auth == $log_username){
      $class = "round";
    }else{
      $class = "margin-bottom: 7px;";
    }

    $style = false;
    if($post_auth == $log_username){
      $style = true;
    }
    
    $cClass = chooseClass($moderators, $post_auth, $creator);
    
    $user_image = genUserImage($post_auth, $avatar_pic, $funames, $isonimg, $fuco, $dist,
      $numoffs, $style, $cClass);

    // Add delete button
    $statusDeleteButton = genDelBtn($post_auth, $log_username, $post_auth, $post_id, true,
      false, '/php_parsers/group_parser2.php');

    // Add share button
    $shareButton = genShareBtn($log_username, $post_auth, $post_id,
    '/php_parsers/group_parser2.php', $g, 'group');

    $isLike = isLiked($conn, $user_ok, $log_username, $post_id, $g, 'group_status_likes');
    
    // Add status like button
    list($likeButton, $likeText) = genStatLikeBtn($isLike, $post_id, true, $g,
      '/php_parsers/gr_like_system.php');

    $post_data_old = sanitizeData($row["data"]);
    $pos = strpos($data_old,
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    
    // See TODO in article_status.php
    $isex = clearImg($post_data_old);

    // Wrap post if longer than 1000 char
    list($post_data, $post_data_old) = seeHideWrap($post_data, $post_data_old, $post_id,
      $pos, $isex);
    $post_data = sanitizeData($post_data);

    // Get replies and user images using inner loop
    $status_replies = "";
    $sql_b = 'SELECT g.*, u.avatar, u.country, u.online, u.lat, u.lon
         FROM grouppost AS g
         LEFT JOIN users AS u ON u.username = g.author
         WHERE g.pid = ? AND g.type = ? ORDER BY g.pdate DESC';
    $stmt = $conn->prepare($sql_b);
    $stmt->bind_param("is", $post_id, $one);
    $stmt->execute();
    $result_old = $stmt->get_result();
    if($result_old->num_rows > 0){
      while ($row2 = $result_old->fetch_assoc()) {
        $statusreplyid = $row2["id"];
        $reply_auth = $row2["author"];
        $reply_data = $row2["data"];
        $reply_date_ = $row2["pdate"];
        $reply_date = strftime("%R, %b %d, %Y", strtotime($reply_date_));
        $reply_avatar = $row2["avatar"];
        $fucor = $row2["country"];
        $ison = $row2["online"];
        $flat = $row2["lat"];
        $flon = $row2["lon"];

        // Dist between users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);

        $isonimg = isOn($ison);
        
        $friend_pic = avatarImg($reply_auth, $avatar2);
        $funames = $reply_auth;
        
        $funames = wrapText($funames, 20);
        $fucor = wrapText($fucor, 20);

        $numoffs = numOfFriends($conn, $reply_auth); 
        $re_avatar_pic = avatarImg($reply_auth, $reply_avatar);
        
        $cClass = chooseClass($moderators, $reply_auth, $creator); 

        $mgin = true;
        if ($reply_auth != $log_username) {
          $mgin = false;
        }

        // Generate reply img avatar + pop up info box
        $reply_image = genUserImage($reply_auth, $re_avatar_pic, $funames, $isonimg, $fucor,
          $dist, $numoffs, $mgin, $cClass); 
        
        // Add delete btn
        $replyDeleteButton = genDelBtn($reply_auth, $log_username, $reply_auth,
          $statusreplyid, false, false, '/php_parsers/group_parser2.php');

        $agoformrply = time_elapsed_string($reply_date_);
        $data_old_reply = sanitizeData($row2["data"]);

        $isex = clearImg($data_old_reply);

        // Wrap reply if longer than 1,000 char
        list($reply_data, $data_old_reply) = seeHideWrap($reply_data, $data_old_reply,
          $statusreplyid, false, false, false);
        $reply_data = sanitizeData($reply_data);

        $isLike_reply = isLiked($conn, $user_ok, $log_username, $statusreplyid, $g, 
          'group_reply_likes');
            
        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false, $g, '/php_parsers/gr_like_system_reply.php');

        // Count reply likes
        $rpycl = cntLikes($conn, $statusreplyid, $g, 'group_reply_likes');

        // Build replies
        $status_replies .= '
          <div id="reply_'.$statusreplyid.'" class="reply_boxes">
            <div>
              '.$replyDeleteButton.'
              <p id="float">
                <b class="sreply">Reply: </b>
                <b class="rdate">
                  <span class="tooLong">'.$reply_date.'</span> ('.$agoformrply.' ago)
                </b>
              </p>

              '.$reply_image.'

              <p id="reply_text">
                <b class="sdata" id="hide_reply_'.$statusreplyid.'">
                  '.$reply_data.''.$data_old_reply.'
                </b>
              </p>

              <hr class="dim">

              <span id="likeBtn_reply_'.$statusreplyid.'" class="likeBtn">'
                .$likeButton_reply.'
                <span style="vertical-align: middle;">'.$likeText_reply.'</span>
              </span>

              <div style="float: left; padding: 0px 10px 0px 10px;">
                <b class="ispan" id="ipanr_' . $statusreplyid . '">' . $rpycl . ' likes</b>
              </div>

              <div class="clear"></div>
            </div>
          </div>
        ';
      }
    }

    // Count likes
    $cl = cntLikes($conn, $post_id, $g, 'group_status_likes');

    // Count the replies
    $crply = cntReplies($conn, $g, $post_id);

    $showmore = "";
    if($crply > 0){
      $showmore = '
        <div class="showrply">
          <a id="showreply_'.$post_id.'" onclick="showReply('.$post_id.','.$crply.')">
            Show replies ('.$crply.')
          </a>
        </div>
      ';
    }
    
    $post_auth = wrapText($post_auth, 12);

    // Build threads
    $mainPosts .= '
      <div id="status_'.$post_id.'" class="status_boxes">
        <div>
          '.$statusDeleteButton.'
          <p id="status_date">
            <b class="status_title">Post: </b>
            <b class="pdate">
              <span class="tooLong">'.$post_date.'</span> ('.$agoform.' ago)
            </b>
          </p>

          '.$user_image.'

          <div id="sdata_'.$post_id.'">
            <p id="status_text">
              <b class="sdata" id="hide_'.$post_id.'">
                '.$post_data.''.$post_data_old.'
              </b>
            </p>
          </div>

          <hr class="dim">

          <span id="likeBtn_'.$post_id.'" class="likeBtn">
            '.$likeButton.'
            <span style="vertical-align: middle;">'.$likeText.'</span>
          </span>

          <div class="shareDiv">
            ' . $shareButton . '
            <span style="vertical-align: middle;">Share</span>
          </div>

          <div style="float: left; padding: 0px 10px 0px 10px;">
            <b class="ispan" id="ipanf_' . $post_id . '">
              ' . $cl . ' likes
            </b>
          </div>

          <div class="clear"></div>
        </div>
        '.$showmore.'
        <span id="allrply_'.$post_id.'" class="hiderply">'.$status_replies.'</span>
      </div>
    ';
    $mainPosts .= '</div><div class="clear">';

    // Time to build the Reply To section
    $mainPosts .= '
      <textarea id="replytext_'.$post_id.'" class="replytext" placeholder="Write a comment"
        onfocus="showBtnDiv_reply(\''.$post_id.'\')"></textarea>
      <div id="uploadDisplay_SP_reply_'.$post_id.'"></div>
      <div id="btns_SP_reply_'.$post_id.'" class="hiddenStuff rply_joiner">
        <span id="swithidbr_'.$post_id.'">
          <button id="replyBtn_'.$post_id.'" class="btn_rply"
            onclick="replyToStatus(\''.$post_id.'\', false, \'replytext_'.$post_id.'\', false, \''.$g.'\', \'/php_parsers/group_parser2.php\')">Reply</button>
        </span>

        <img src="/images/camera.png" id="triggerBtn_SP_reply" class="triggerBtnreply"
          onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22"
          title="Upload A Photo" />

        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$post_id.')">

        <div class="clear"></div>
    ';
    $mainPosts .= generateEList($post_id, 'emojiBox_reply_' . $post_id,
        'replytext_'.$post_id);
    $mainPosts .= '</div>';
    $mainPosts .= '
      <div id="standardUpload_reply" class="hiddenStuff">
        <form id="image_SP_reply" enctype="multipart/form-data" method="post">
          <input type="file" name="FileUpload" id="fu_SP_reply"
            onchange="doUpload_reply(\'fu_SP_reply\', \''.$post_id.'\')" accept="image/*"/>
        </form>
      </div>
    ';
  }
?>
