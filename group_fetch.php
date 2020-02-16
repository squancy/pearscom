<?php
  while ($row = $result_new->fetch_assoc()) {
    if ($isIndex) {
      $g = $row["gname"];
      $cClass = "";
    }
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
    
    if (!$isIndex) {
      $cClass = chooseClass($moderators, $post_auth, $creator);
    }
    
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
        
        if (!$isIndex) {
          $cClass = chooseClass($moderators, $reply_auth, $creator); 
        }

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

        $replyLog = genLog($_SESSION['username'], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        $replyLog .= addIndexText($isIndex, '/group/'.$g.'/#reply_'.$statusreplyid,
          'Group reply');

        // Build replies
        $status_replies .= genStatusReplies($statusreplyid, $replyDeleteButton, $reply_date,
          $agoformrply, $reply_image, $reply_data, $data_old_reply, $replyLog, $rpycl);
      }
    }

    // Count likes
    $cl = cntLikes($conn, $post_id, $g, 'group_status_likes');

    // Count the replies
    $crply = cntReplies($conn, $g, $post_id);

    $showmore = genShowMore($crply, $post_id);    
    $post_auth = wrapText($post_auth, 12);
    $statusLog = genLog($_SESSION['username'], $post_id, $likeButton, $likeText, true,
      $shareButton);

    // If file is used on index.php add custom text
    if ($post_type != $one) {
      $statusLog .= addIndexText($isIndex, '/group/'.$g.'/#status_'.$post_id,
        'Group post');
    } else {
      $statusLog .= addIndexText($isIndex, '/group/'.$g.'/#reply_'.$post_id,
        'Group reply');
    }

    // Build threads
    $mainPosts .= genStatCommon($post_id, $statusDeleteButton, $post_date, $agoform,
      $user_image, $post_data, $post_data_old, $statusLog, $cl, $showmore, $status_replies);

    // Time to build the Reply To section
    $mainPosts .= genReplyInput($isFriend, $log_username, $u, $post_id,
      '/php_parsers/group_parser2.php', $g);
  }
?>
