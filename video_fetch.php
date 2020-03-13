<?php
  while ($row = $result->fetch_assoc()) {
    if ($isIndex) {
      $_SESSION['id'] = '';
      $viHash = base64url_encode($row["video_id"], $hshkey);
      $vi = $row["video_id"];
    }
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

    // Get distance between 2 users
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
    $isonimg = isOn($ison);

    $friend_pic = avatarImg($author, $avatar);
    $funames = $author;

    $funames = wrapText($funames, 20);
    $fuco = wrapText($fuco, 20);
    
    $mgin = false;
    if(isset($_SESSION["username"])){
      $mgin = true;
    }
    
    $numoffs = numOfFriends($conn, $author);

    // User image + popup on desktop
    $user_image = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
      $numoffs, true);
    $agoform = time_elapsed_string($postdate_);
    $data = $row["data"];
    $data_old = sanitizeData($row["data"]);
    $pos = strpos($data_old,
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    $isex = clearImg($data_old);

    // Wrap status text if longer than 1,000 chars
    list($data, $data_old) = seeHideWrap($data, $data_old, $statusid, $pos, $isex);

    $data = sanitizeData($data);

    // Status delete button
    $statusDeleteButton = '';

    if(!$isIndex) {
      $statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid, true,
        true, '/php_parsers/video_status_parser.php');
    } else {
      // ugly button to position user avatar
      $statusDeleteButton = '
        <button style="visibility: hidden; margin-left: -5px;"></button>
      ';
    }

    // Add share button
    $shareButton = genShareBtn($log_username, $author, $statusid,
      '/php_parsers/video_status_parser.php');

    // Check if user liked the post
    $isLike = userLiked($user_ok, $conn, $statusid, $log_username, true, 'video_status_likes',
      'video');

    // Add status like button
    list($likeButton, $likeText) = genStatLikeBtn($isLike, $statusid, true, false,
      '/php_parsers/like_system_video.php'); 

    // Gather status replies
    $status_replies = "";
    $sql2 = "SELECT s.*, u.avatar, u.online, u.lat, u.lon, u.country
        FROM video_status AS s
        LEFT JOIN users AS u ON u.username = s.author
        WHERE s.vidid = ? AND  s.osid = ? 
        AND s.type = ? 
        ORDER BY postdate DESC";

    $stmt = $conn->prepare($sql2);
    $stmt->bind_param("iis", $vi, $statusid, $b);
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

        // Distance between users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = isOn($ison);

        $friend_pic = avatarImg($replyauthor, $avatar2);
        $funames = $replyauthor;

        $funames = wrapText($funames, 20);
        $fuco = wrapText($fuco, 20);

        $numoffs = numOfFriends($conn, $replyauthor);
        $user_image2 = genUserImage($replyauthor, $friend_pic, $funames, $isonimg, $fuco,
          $dist, $numoffs, true);

        $replypostdate_ = $row2["postdate"];
        $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
        $replydata = sanitizeData($replydata);

        // Create reply delete button
        $replyDeleteButton = '';

        if (!$isIndex) {
          $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
            $statusreplyid, false, true, '/php_parsers/video_status_parser.php'); 
        } else {
          // ugly button to position user avatar
          $replyDeleteButton = '
            <button style="visibility: hidden; margin-left: -5px;"></button>
          ';
        }

        $agoformrply = time_elapsed_string($replypostdate_);
        $data_old_reply = sanitizeData($row2["data"]);
        $isex = clearImg($data_old_reply);

        // Wrap reply text if longer than 1,000 chars
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);

        $replydata = sanitizeData($replydata);

        // Check if user liked the reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false,
          'video_reply_likes', 'reply');

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false, false, '/php_parsers/video_reply_likes.php');

        // Count reply likes
        $rpycl = getAllLikes('video_reply_likes', 'reply', $statusreplyid, $conn);

        $replyLog = genLog($_SESSION['username'], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        $replyLog .= addIndexText($isIndex, '/video_zoom/'.$viHash.'/#reply_'.$statusreplyid,
          'Video reply');

        $status_replies .= genStatusReplies($statusreplyid, $replyDeleteButton, $replypostdate,
          $agoformrply, $user_image2, $replydata, $data_old_reply, $replyLog, $rpycl);
      }
    }

    // Count likes
    $cl = getAllLikes('video_status_likes', 'status', $statusid, $conn);

    // Count the replies
    if (!$isIndex) {
      $crply = countReplies($u, $statusid, $vi, $conn, 'video_status', 'vidid');
    } else {
      $crply = countReplies($u, $statusid, $vi, $conn, 'video_status', 'vidid', true);
    }

    $showmore = genShowMore($crply, $statusid);    
    $statusLog = genLog($_SESSION['username'], $statusid, $likeButton, $likeText, true,
      $shareButton);

    // If file is used on index.php add 'status post' text
    $statusLog .= addIndexText($isIndex,
      '/video_zoom/'.$viHash.'/#status_'.$statusid, 'Video post');

    $statvidl .= genStatCommon($statusid, $statusDeleteButton, $postdate, $agoform,
      $user_image, $data, $data_old, $statusLog, $cl, $showmore, $status_replies);
    
    if ($isFriend || $log_username == $u || $author == $log_username) {
      $statvidl .= genReplyInput($isFriend, $log_username, $u, $statusid,
        '/php_parsers/video_status_parser.php');
    }
  }
?>
