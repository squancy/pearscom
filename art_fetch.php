<?php
  while ($row = $result->fetch_assoc()) {
    if ($isIndex) {
      $_SESSION['id'] = '';
      $ar = $row['aid'];

      // Select post time of art
      $sql = "SELECT post_time FROM articles WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('s', $ar);
      $stmt->execute();
      $stmt->bind_result($pdate);
      $stmt->fetch();
      $stmt->close();

      $arLink = base64url_encode($pdate, $hshkey);
    }
    $statusid = $row["id"];
    $account_name = $row["account_name"];
    $author = $row["author"];
    $postdate_ = $row["postdate"];
    $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
    $agoform = time_elapsed_string($postdate_);
    $avatar = $row["avatar"];
    $fuco = $row["country"];
    $ison = $row["online"];
    $flat = $row["lat"];
    $flon = $row["lon"];

    // Get the distance between 2 users based on their lat and lon coords
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);

    // Check if user is online
    $isonimg = isOn($ison);

    // Avatar pic
    $friend_pic = avatarImg($author, $avatar);

    $funames = $author;
    $fuco = wrapText($fuco, 20);  
    
    $numoffs = numOfFriends($conn, $author);

    // On-hover user info box
    $user_image = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
      $numoffs, true);
    $data = $row["data"];
    $data_old = $row["data"];
    $data_old = sanitizeData($data_old);
    $pos = strpos($data_old, 
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');

    /*
      TODO: instead of saving uploaded images in the same context as other data & text
      save the img in a separate db field -> no need for regex & efficient
    */

    $isex = clearImg($data_old);    
    
    // Wrap post if longer than 1000 char
    list($data, $data_old) = seeHideWrap($data, $data_old, $statusid, $pos, $isex);

    $data = sanitizeData($data);
    $statusDeleteButton = '';

    // Status delete button
    if (!$isIndex) {
      $statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid); 
    } else {
      // ugly button to position user avatar
      $statusDeleteButton = '
        <button style="visibility: hidden; margin-left: -5px;"></button>
      ';
    }

    // Add share button
    $shareButton = genShareBtn($log_username, $author, $statusid);  

    // Check if user liked the post or not
    $isLike = userLiked($user_ok, $conn, $statusid, $log_username);    

    // Add status like button
    list($likeButton, $likeText) = genStatLikeBtn($isLike, $statusid);  

    // Gather status replies 
    $status_replies = "";
    $sql2 = "SELECT s.*, u.avatar
        FROM article_status AS s
        LEFT JOIN users AS u ON u.username = s.author
        WHERE s.artid = ? AND s.osid = ?
        AND s.type = ?
        ORDER BY postdate DESC";

    $stmt = $conn->prepare($sql2);
    $stmt->bind_param("iis", $ar, $statusid, $b);
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

        // Get distance between users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';

        // Is online or not
        $isonimg = isOn($ison);
        $friend_pic = avatarImg($replyauthor, $avatar2);
        $funames = wrapText($replyauthor, 20);
        $fuco = wrapText($fuco, 20);

        $numoffs = numOfFriends($conn, $replyauthor);

        // On-hover reply user info box
        $user_image2 = genUserImage($replyauthor, $friend_pic, $funames, $isonimg, $fuco,
          $dist, $numoffs, true);
        $replypostdate_ = $row2["postdate"];
        $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
        $agoform_reply = time_elapsed_string($replypostdate_);

        // Reply del btn
        if (!$isIndex) {
          $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
            $statusreplyid, false);
        } else {
          // ugly button to position user avatar
          $replyDeleteButton = '
            <button style="visibility: hidden; margin-left: -5px"></button>
          ';
        }


        $data_old_reply = $row2["data"];
        $data_old_reply = sanitizeData($data_old_reply);
        $isex = clearImg($data_old_reply); 
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);
        
        $replydata = sanitizeData($replydata);  
        
        // Check if user liked the current reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false);

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false);

        $rpycl = getAllLikes('art_reply_likes', 'reply', $statusreplyid, $conn);
        
        $replyLog = genLog($_SESSION["username"], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);
        $replyLog .= addIndexText($isIndex,
          '/articles/'.$arLink.'/'.$account_name.'/#reply_'.$statusreplyid,
          'Article reply');

        $status_replies .= genStatusReplies($statusreplyid, $replyDeleteButton, $replypostdate,
          $agoform_reply, $user_image2, $replydata, $data_old_reply, $replyLog, $rpycl);
      }
    }

    // Count the replies
    if (!$isIndex) {
      $crply = countReplies($u, $statusid, $ar, $conn);
    } else {
      $crply = countReplies($u, $statusid, $ar, $conn, 'article_status', 'artid', true);
    }
    $showmore = genShowMore($crply, $statusid);

    // Count likes
    $cl = getAllLikes('art_stat_likes', 'status', $statusid, $conn);
      
    $statusLog = genLog($_SESSION["username"], $statusid, $likeButton, $likeText,
      true, $shareButton);

    // If file is used on index.php add 'status post' text
    if ($row["type"] != "b") {
      $statusLog .= addIndexText($isIndex,
        '/articles/'.$arLink.'/'.$account_name.'/#status_'.$statusid, 'Article post');
    } else {
      $statusLog .= addIndexText($isIndex,
        '/articles/'.$arLink.'/'.$account_name.'/#reply_'.$statusreplyid,
        'Article reply');
    }

    $statartl .= genStatCommon($statusid, $statusDeleteButton, $postdate, $agoform,
      $user_image, $data, $data_old, $statusLog, $cl, $showmore, $status_replies);

    if ($isFriend || $log_username == $u || $author == $log_username) {
      $statartl .= genReplyInput($isFriend, $log_username, $u, $statusid,
        '/php_parsers/article_status_system.php'); 
    }
  }
?>
