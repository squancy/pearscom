<?php
  /*
    Comment section of an article. Implement status posts, replies, likes etc.
  */

  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/isfriend.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'php_includes/pagination.php';
  require_once 'headers.php';
  require_once 'elist.php';
  require_once 'php_includes/dist.php';

  // Select user's lat and lon
  function getUserLatLon($conn, $log_username) {
    $sql = "SELECT lat, lon FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($lat, $lon);
    $stmt->fetch();
    $stmt->close();
    return [$lat, $lon];
  }

  list($lat, $lon) = getUserLatLon($conn, $log_username);

  $status_ui = "";
  $statuslist = "";
  $statusid = "";
  $one = "1";
  $zero = "0";
  $a = "a";
  $b = "b";
  $c = "c";
  $ar = $_SESSION["id"];
  $p_en = base64url_encode($p, $hshkey);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM article_status WHERE account_name=? AND artid=?";
  $url_n = "/articles/{$p_en}/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'si', $url_n, $u, $ar); 
 
  // Check if users are friends
  $isFriend = isFriend($u, $log_username, $user_ok, $conn);
  
  $txtMsg = "";
  if($isOwner == "No"){
    $txtMsg = "What is your opinion about this article?";
  }else if($isOwner == "Yes"){
    $txtMsg = "Say something about your article";
  }

  // If user is logged in allow them to comment 
  if($_SESSION["username"] != ""){
    $status_ui = '
      <textarea id="statustext_" onfocus="showBtnDiv()" placeholder="'.$txtMsg.'" 
      class="user_status"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
      <span id="swithspan">
        <button id="statusBtn" class="btn_rply"
          onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext_\')">Post</button
      </span>
      <img src="/images/camera.png" id="triggerBtn_SP_" 
        onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
        title="Upload A Photo" class="triggerBtnreply" />
      <img src="/images/emoji.png" width="22" class="triggerBtn" height="22"
        title="Send emoticons" id="emoji" onclick="openEmojiBox()">
      <div class="clear"></div>
      ';
    $status_ui .= generateEList($statusid, 'emojiBox_art', 'statustext_');
    $status_ui .= '
      </div>
      <div id="standardUpload" class="hiddenStuff">
      <form id="image_SP" enctype="multipart/form-data" method="post">
        <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
          accept="image/*" />
      </form>
      </div>
      <div class="clear"></div>
      ';
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>Please <a href='/login'>log in</a>
        in order to leave a comment</p>";    
  }

  // Get status posts & data about the authors
  $sql = "SELECT s.*, u.avatar, u.lat, u.lon, u.online, u.country
    FROM article_status AS s
    LEFT JOIN users AS u ON u.username = s.author
    WHERE s.artid = ? AND (s.account_name=? AND s.type=?)
    OR (s.account_name=? AND s.type=?)
    ORDER BY s.postdate DESC $limit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("issss", $ar, $u, $a, $u, $c);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
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
    $sec_data = "";
    $first_data = "";

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
    $statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid); 

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
        $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
          $statusreplyid, false);

        $data_old_reply = $row2["data"];
        $data_old_reply = sanitizeData($data_old_reply);
        $sec_data = "";
        $first_data = "";
        $isex = clearImg($data_old_img); 
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);
        
        $replydata = sanitizeData($replydata);  
        
        // Check if user liked the current reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false);

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false);

        $rpycl = getAllLikes('art_reply_likes', 'reply', $statusreplyid, $conn);
        
        $replyLog = "";
        $statusLog = "";

        $replyLog = genLog($_SESSION["username"], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        $status_replies .= '
          <div id="reply_'.$statusreplyid.'" class="reply_boxes">
            <div>
              '.$replyDeleteButton.'
              <p id="float">
                <b class="sreply">Reply: </b>
                <b class="rdate">
                  <span class="tooLong">'.$replypostdate.'</span>
                  ('.$agoform_reply.' ago)
                </b>
              </p>

              '.$user_image2.'
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

      // Count the replies
      $crply = countReplies($u, $statusid, $ar, $conn);

      $showmore = genShowMore($crply, $statusid);

      // Count likes
      $cl = getAllLikes('art_stat_likes', 'status', $statusid, $conn);
        
      $statusLog = genLog($_SESSION["username"], $statusid, $likeButton, $likeText,
        true, $shareButton);

      $statuslist .= '
        <div id="status_'.$statusid.'" class="status_boxes">
          <div>
            '.$statusDeleteButton.'
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
          <span id="allrply_'.$statusid.'" class="hiderply">
            '.$status_replies.'
          </span>
        </div>';

    if($isFriend == true || $log_username == $u){
      $statuslist .= '
        <textarea id="replytext_'.$statusid.'" class="replytext"
          onfocus="showBtnDiv_reply('.$statusid.')"
          placeholder="Write a comment..."></textarea>
        <div id="uploadDisplay_SP_reply_'.$statusid.'"></div>
        <div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff rply_joiner">
          <span id="swithidbr_'.$statusid.'">
            <button id="replyBtn_'.$statusid.'" class="btn_rply"
              onclick="replyToStatus('.$statusid.',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button>
            </span>
            <img src="/images/camera.png" id="triggerBtn_SP_reply_" class="triggerBtnreply"
              onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22"
              title="Upload a photo" />
            <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
              title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$statusid.')">
            <div class="clear"></div>
        ';
      $statuslist .= generateEList($statusid, 'emojiBox_reply_' . $statusid,
          'replytext_'.$statusid);
      $statuslist .= '</div>';
      $statuslist .= '
        <div id="standardUpload_reply" class="hiddenStuff">
          <form id="image_SP_reply" enctype="multipart/form-data" method="post">
            <input type="file" name="FileUpload" id="fu_SP_reply"
              onchange="doUpload_reply(\'fu_SP_reply\', '.$statusid.')" accept="image/*"/>
          </form>
        </div>
        <div class="clear"></div>
      ';
    }
  }
?>
<script type='text/javascript'>
  const UNAME = '<?php echo $u; ?>';
</script>
<script src='/js/specific/p_dialog.js' defer></script>
<script src='/js/specific/see_hide.js' defer></script>
<script src='/js/specific/open_emoji.js' defer></script>
<script src='/js/specific/delete_post.js' defer></script>
<script src='/js/specific/insert_emoji.js' defer></script>
<script src='/js/specific/upload_funcs.js' defer></script>
<script src='/js/specific/btn_div.js' defer></script>
<script src='/js/specific/post_reply.js' defer></script>
<script src='/js/specific/share_status.js' defer></script>
<script src='/js/specific/like_status.js' defer></script>
<script type="text/javascript">
  var hasImage = "";
  window.onbeforeunload = function() {
    if ("" != hasImage) {
      return "You have not posted your image";
    }
  } 
</script>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>

<div id="statusarea">
  <?php echo $statuslist; ?>
</div>

<div style="text-align: center; padding: 20px;">
  <?php echo $paginationCtrls; ?>
</div>
