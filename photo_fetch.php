<?php
  while ($row = $result->fetch_assoc()) {
    $statusid = $row["id"];
    $type = $row["type"];
    if ($isIndex) {
      $p = $row["photo"];
    }
    $account_name = $row["account_name"];
    $author = $row["author"];
    $postdate_ = $row["postdate"];
    $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
    $avatar = $row["avatar"];
    $fuco = $row["country"];
    $ison = $row["online"];
    $flat = $row["lat"];
    $flon = $row["lon"];

    // Get distance between 2 users bases on lat and lon
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
    $isomimg = isOn($ison);

    // Get user avatar
    $friend_pic = avatarImg($author, $avatar);
    $funames = $author;

    $funames = wrapText($funames, 20);
    $fuco = wrapText($fuco, 20);

    $mgin = false;
    if($log_username == $u){
        $mgin = true;
    }

    $numoffs = numOfFriends($conn, $author);

    // On-hover user info box
    $user_image = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
    $numoffs, true); 

    $data = $row["data"];
    $data_old = sanitizeData($row["data"]);
    $pos = strpos($data_old,
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
          
    $isex = clearImg($data_old);

    // Wrap post text if longer than 1,000 chars
    list($data, $data_old) = seeHideWrap($data, $data_old, $statusid, $pos, $isex);

    $data = sanitizeData($data); 
    $agoform = time_elapsed_string($postdate_);

    // Get status delete button
    $statusDeleteButton = '';
    $statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid, true,
      true, '/php_parsers/photo_status_system.php');

    // Add share button
    $shareButton = genShareBtn($log_username, $author, $statusid,
      '/php_parsers/photo_status_system.php');

    // Check if user liked the post
    $isLike = userLiked($user_ok, $conn, $statusid, $log_username, true, 'photo_stat_likes',
      'status');

    // Add status like button
    list($likeButton, $likeText) = genStatLikeBtn($isLike, $statusid, true, false,
      '/php_parsers/like_photo_system.php');

    // Gather status replies
    $status_replies = "";
    $sql2 = "SELECT s.*, u.avatar, u.country, u.online, u.lat, u.lon 
        FROM photos_status AS s 
        LEFT JOIN users AS u ON u.username = s.author
        WHERE s.photo = ? 
        AND s.osid = ? 
        AND s.type = ? 
        ORDER BY s.postdate DESC";

    $stmt = $conn->prepare($sql2);
    $stmt->bind_param("sis", $p, $statusid, $b);
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

        // Get reply user avatar img
        $friend_pic = avatarImg($replyauthor, $avatar2);

        $flat = $row["lat"];
        $flon = $row["lon"];
        $ison = $row["online"];
        $fuco = $row["country"];

        // Get the distance between 2 users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = isOn($ison); 

        $funames = $replyauthor;
        
        $funames = wrapText($funames, 20);
        $fuco = wrapText($fuco, 20);

        $numoffs = numOfFriends($conn, $replyauthor);
        $user_image2 = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
          $numoffs, true);

        $replypostdate_ = $row2["postdate"];
        $replypostdate = strftime("%b %d, %Y", strtotime($replypostdate_));
        $agoformrply = time_elapsed_string($replypostdate_);
        $data_old_reply = sanitizeData($row2["data"]);
        $isex = clearImg($replydata); 
      
        // Wrap reply text if longer than 1,000 chars
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);

        $replydata = sanitizeData($replydata); 

        // Get reply delete button
        $replyDeleteButton = '';
        $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
          $statusreplyid, false, true, '/php_parsers/photo_status_system.php');

        // Check if user liked the reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false,
          'photo_reply_likes', 'reply');

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false, false, '/php_parsers/like_reply_photo_system.php/');

        // Count reply likes
        $rpycl = getAllLikes('photo_reply_likes', 'reply', $statusreplyid, $conn); 

        $replyLog = genLog($_SESSION['username'], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        // If file is used on index.php add 'status post' text
        $replyLog .= addIndexText($isIndex,
          '/photo_zoom/'.$account_name.'/'.$p.'/#reply_'.$statusid, 'Photo reply');


        // Build reply output
        $status_replies .= genStatusReplies($statusreplyid, $replyDeleteButton,
          $replypostdate, $agoformrply, $user_image2, $replydata, $data_old_reply,
          $replyLog, $rpycl); 
      } 
    }

    // Count status likes
    $cl = getAllLikes('photo_stat_likes', 'status', $statusid, $conn,
      'status = ? AND photo = ?', 'is', $statusid, $p); 

    // Count the replies
    $crply = countReplies($u, $statusid, $p, $conn, 'photos_status', 'photo'); 

    $showmore = genShowMore($crply, $statusid);        
    $statusLog = genLog($_SESSION['username'], $statusid, $likeButton, $likeText, true,
      $shareButton);

    // If file is used on index.php add 'status post' text
    if ($row["type"] != "b") {
      $statusLog .= addIndexText($isIndex,
        '/photo_zoom/'.$account_name.'/'.$p.'/#status_'.$statusid, 'Photo post');
    } else {
      $statusLog .= addIndexText($isIndex,
        '/photo_zoom/'.$account_name.'/'.$p.'/#reply_'.$statusid, 'Photo reply');
    }
    
    // Merge everything and send it to display
    $statphol .= genStatCommon($statusid, $statusDeleteButton, $postdate, $agoform,
      $user_image, $data, $data_old, $statusLog, $cl, $showmore, $status_replies);
        
    // Comment section
    $statphol .= genReplyInput($isFriend, $log_username, $u, $statusid,
      '/php_parsers/photo_status_system.php');
  }
?>
