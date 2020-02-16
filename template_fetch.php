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

    $numoffs = numOfFriends($conn, $author);

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
    
        $numoffs = numOfFriends($conn, $replyauthor);

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
        
        $replyLog = genLog($_SESSION['username'], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        // If file is used on index.php add 'reply post' text
        $replyLog .= addIndexText($isIndex, '/user/'.$account_name.'/#reply_'.$statusreplyid,
          'Status reply');
          
        $status_replies .= genStatusReplies($statusreplyid, $replyDeleteButton, $replypostdate,
          $agoformrply, $user_image2, $replydata, $data_old_reply, $replyLog, $rpycl);
      }
    }

    // Count likes
    $cl = getAllLikes('status_likes', 'status', $statusid, $conn);

    // Count the replies
    $crply = countReplies_stat($conn, $b, $u, $statusid);

    $showmore = genShowMore($crply, $statusid);
    $statusLog = genLog($_SESSION['username'], $statusid, $likeButton, $likeText, true,
      $shareButton);
    
    // If file is used on index.php add 'status post' text
    if ($row["type"] != "b") {
      $statusLog .= addIndexText($isIndex, '/user/'.$account_name.'/#status_'.$statusid,
        'Status post');
    } else {
      $statusLog .= addIndexText($isIndex, '/user/'.$account_name.'/#reply_'.$statusid,
        'Status reply');
    }
    
    $statuslist .= genStatCommon($statusid, $statusDeleteButton, $postdate, $agoform,
      $user_image, $data, $data_old, $statusLog, $cl, $showmore, $status_replies);

    // Build potential reply section
    $statuslist .= genReplyInput($isFriend, $log_username, $u, $statusid,
      '/php_parsers/status_system.php');
  }
?>
