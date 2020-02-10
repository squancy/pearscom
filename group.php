<?php
  /*
    Display a group + implement comment section
  */

  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/gr_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/wrapText.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';
  require_once 'ccovg.php';
  require_once 'elist.php';
  require_once 'php_includes/dist.php';
  
  list($lat, $lon) = getLatLon($conn, $log_username);
  $one = "1";
  $u = $_SESSION["username"];

  // Select the member from the users table
  if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
    userExists($conn, $u); 
  }

  // Initialize any variables that the page might echo
  $moderators = array();
  $approved = array();
  $pending = array();
  $all = array();
  $one = "1";
  $zero = "0";
  $mem_count = 0;

  // Make sure the $_GET group name is set, and sanitize it
  if(isset($_GET["g"])){
    $g = mysqli_real_escape_string($conn, $_GET["g"]);
  }else{
    header('Location: /index');
    exit();
  }
  
  $_SESSION["gname"] = $g;

  // Handle pagination
  $sql_p = "SELECT COUNT(id) FROM grouppost WHERE gname = ? AND type = ?";
  $url_n = "/group/{$g}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_p, 'ss', $url_n, $g, $zero);

  // Select the group from db; if not found redirect to index page
  $sql = "SELECT * FROM groups WHERE name=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $g);
  $stmt->execute();
  $result = $stmt->get_result();
  if($row = $result->fetch_assoc()){
    $gr_id = $row["id"];
    $gName = $row["name"];
    $gCreation = $row["creation"];
    $gLogo = $row["logo"];
    $invRule = $row["invrule"];
    $creator = $row["creator"];
    $gr_des = $row["des"];
    $gr_des_old = $row["des"];
    $gr_des_old = str_replace( '\n', '<br />', $gr_des_old ); 

    // Wrap description if too long
    if(strlen($gr_des) > 250){
      $gr_des = wrapText($gr_des, 250);
      $gr_des .= '
        &nbsp;
        <a id="toggle_gr_'.$gr_id.'" onclick="opentext_gr(\''.$gr_id.'\')">See More</a>';
      $gr_des_old = '
        <div id="lessmore_gr_'.$gr_id.'" class="lmml" style="font-size: 14px;">
          '.$gr_des_old.'&nbsp;
          <a id="toggle_gr_'.$gr_id.'" onclick="opentext_gr(\''.$gr_id.'\')">See Less</a>
        </div>';
    }else{
      $gr_des_old = "";
    }

    $gr_des = str_replace('\n', '<br />', $gr_des); 
    if (!$gr_des) {
      $gr_des = 'not yet given';
    }

    if($invRule == 0){
      $invRule = "Private group";
    }else{
      $invRule = "Public group";
    }

  } else {
    header('location: /index');
    exit();
  }

  $stmt->close();
  
  $pcurl = getUserAvatar($conn, $creator);
  
  $creator_echo = '
    <a href="/user/'.$creator.'/" style="float: left;">
      <div data-src=\''.$pcurl.'\' style="width: 50px; height: 50px; border-radius: 50%;"
        class="genBg lazy-bg grCreat">
      </div>
    </a>';

  // Select a group logo pic
  if($gLogo != NULL && $gLogo != "gdef.png"){
    $profile_pic = '/groups/'.$g.'/'.$gLogo.'';
  }else if($gLogo == NULL || $gLogo == "gdef.png"){
    $profile_pic = '/images/gdef.png';
  }

  // Set session for group
  $_SESSION['group'] = $gName;

  // Get members data
  $sql = 'SELECT DISTINCT g.mname, g.approved, g.admin, u.avatar
      FROM gmembers AS g
      LEFT JOIN users AS u ON u.username = g.mname
      WHERE g.gname = ?';
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $g);
  $stmt->execute();
  $result2 = $stmt->get_result();
  while($row2 = $result2->fetch_assoc()){
    $mName = $row2["mname"];
    $app = $row2["approved"];
    $admin = $row2["admin"];
    $avatar = $row2["avatar"];

    $member_pic = '/user/'.$mName.'/'.$avatar;
    if($avatar == NULL){
      $member_pic = '/images/avdef.png';
    }

    // Determine if approved
    isApproved($app, $pending, $approved, $mName);

    array_push($all, $mName);

    // Determine if admin
    isAdmin($admin, $mName, $moderators);

    // Get number of moderators and members
    $mod_count = count($moderators);
    $app_count = count($approved);
    $pend_count = count($pending);
    
    $mem_count = $app_count - $mod_count;

    $mod_slice = array_slice($moderators, 0, 6);
    $mod_string = join("','", $mod_slice);
    
    $app_array = array_diff($approved, $moderators);
    
    $app_slice = array_slice($app_array, 0, 2);
    $app_string = implode(", ", $app_slice);

    // Output
    if(in_array($mName, $app_array)){
      $gMembers .= '
        <a href="/user/'.$mName.'/">
          <div data-src=\''.$member_pic.'\' style="width: 50px; height: 50px;
            margin-right: 5px; float: left; border-radius: 50%;" class="genBg lazy-bg grMem">
          </div>
        </a>
      ';
    }
  }
  $stmt->close();

  // Join group button
  $joinBtn = genJoinBtn($all, $pending, $g);

  if(count($app_array) < 1){
    $gMembers = '
      <p style="color: #999; font-size: 14px;">
        There are no members in this group
      </p>
    ';
  }
  
  // Get the avatar of moderators
  $sqlMods = "SELECT avatar, username FROM users WHERE username IN('$mod_string')";
  $stmt = $conn->prepare($sqlMods);
  $stmt->execute();
  $resMods = $stmt->get_result();
  while($rowMods = $resMods->fetch_assoc()){
    $uLogo = $rowMods["avatar"];
    $uName = $rowMods["username"];
    $uLogo = avatarImg($uName, $uLogo);
    $moderatorsPics .= '
      <a href="/user/'.$uName.'/" style="float: left;">
        <div class="genBg lazy-bg grMod" data-src=\''.$uLogo.'\' style="width: 50px;
          height: 50px; border-radius: 50%; margin-right: 5px;">
        </div>
      </a>
    ';
  }
  $stmt->close();

  // Build posting mechanism
  if(in_array($_SESSION['username'], $approved)){
    $status_ui = '
      <textarea id="statustext" class="user_status" onfocus="showBtnDiv()"
        placeholder="What&#39;s in your mind?"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
        <span id="swithspan">
          <button id="statusBtn" onclick="postToStatus(false, false, false, \'statustext\',
            \''.$g.'\', \'/php_parsers/group_parser2.php\', \'listBlabs\')" class="btn_rply">
            Post</button>
        </span>
        <img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply"
          onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
          title="Upload a photo"/>
        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox(\'emojiBox_group\')">
        <div class="clear"></div>
    ';
    $status_ui .= generateEList("x", 'emojiBox_group', 'statustext');
    $status_ui .= '</div>';
    $status_ui .= '
      <div id="standardUpload" class="hiddenStuff">
        <form id="image_SP" enctype="multipart/form-data" method="post">
          <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
            accept="image/*"/>
        </form>
      </div>
      <div class="clear"></div>
    ';
  }

  // Get group status posts
  $sql = "SELECT gp.*, u.*, gp.id AS grouppost_id 
          FROM grouppost AS gp
          LEFT JOIN users AS u ON u.username = gp.author
          WHERE gp.gname = ? 
            AND gp.type = ? 
          ORDER BY gp.pdate DESC
          $limit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $g, $zero);
  $stmt->execute();
  $result_new = $stmt->get_result();
  if ($result_new->num_rows > 0){
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
      $funames = wrapText($fuco, 20);

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
      $shareButton = genShareBtn($log_username, $post_auth, $post_id);

      $isLike = isLiked($conn, $user_ok, $log_username, $post_id, $g, 'group_status_likes');
      
      // Add status like button
      list($likeButton, $likeText) = genStatLikeBtn($isLike, $post_id, true, ',\''.$g.'\'');

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
          
          // Generate reply img avatar + pop up info box
          $reply_image = genUserImage($reply_auth, $re_avatar_pic, $funames, $isonimg, $fucor,
            $dist, $numoffs, true, $cClass); 
          
          // Add delete btn
          $replyDeleteButton = genDelBtn($reply_auth, $log_username, $reply_auth,
            $statusreplyid, false);

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
            $statusreplyid, false, ',\''.$g.'\'');

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
              onclick="replyPost(\''.$post_id.'\',\''.$g.'\')">Reply</button>
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
  }else{
    $btfo = "<p style='color: #999;' class='txtc'>Be the first one who post something!</p>";
  }

  // Pending member section for admin
  if (in_array($_SESSION['username'],$moderators) && $invRule == 'Private group'){
    $addMembers = "
      <hr class='dim'><p style='font-size: 16px; margin-top: 5px;'>
        Pending members (".$pend_count.")
      </p>
      <div class='horizontalScroll'>
    ";

    if($pend_count == 0){
      $addMembers .= '
        <p style="color: #999; font-size: 14px;">
          There are no pending approvals at the moment
        </p>
      ';
    }

    for($x = 0; $x < $pend_count; $x++){
      $curuser = $pending[$x];
      $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $curuser);
      $stmt->execute();
      $result = $stmt->get_result();
      if($row = $result->fetch_assoc()){
        $avatar = $row["avatar"];
        $country = $row["country"];
        $isonline = $row["online"];
      }

      if($isonline == "yes"){
        $isonline = "border: 2px solid #00a1ff";
      }else{
        $isonline = "border: 2px solid grey";
      }

      $avatar = avatarImg($curuser, $avatar);
      $avatar = '
        <div data-src=\''.$avatar.'\' style="border-radius: 50%; width: 60px; height: 60px;
          float: left;" class="genBg lazy-bg"></div>';

      $country = wrapText($country, 20);

      $addMembers .= '
        <div class="wfaHolder">
          <a href="/user/'.$curuser.'/">'.$avatar.'</a>
          <div id="btn_align">
            <button id="appbtn" class="main_btn_fill fixRed"
              onclick="approveMember(\''.$curuser.'\',\''.$g.'\')"
              style="margin-bottom: 5px;">Approve</button>
            <br />
            <button id="appbtn_2" class="main_btn"
              onclick="declineMember(\''.$curuser.'\',\''.$g.'\')">Decline</button>
          </div>
          <div id="pending_data">
            <p style="padding: 0px; margin: 0px;">'.$curuser.'</p>
            <p style="padding: 0px; margin: 0px;">'.$country.'</p>
          </div>
        </div>
      ';
    }
    $addMembers .= '</div>';
  }else if(!in_array($_SESSION["username"], $moderators) && in_array($_SESSION["username"],
    $approved) && $invRule == 'Private group'){
    $addMembers = '
      <hr class="dim">
      <p style="font-size: 16px; margin: 0;">
        Pending members
      </p>
      <p style="color: #999; font-size: 14px;">
        Claim a promotion to be a moderator to see the group&#39;s pending approvals
      </p>
    ';
  }

  if(in_array($_SESSION['username'],$moderators)){
    $addAdmin = '
      <hr class="dim">
      <p style="font-size: 16px;">Add new admin to group</p>
      <input style="margin-top: 0;" type="text" class="ssel" name="new_admin"
        id="new_admin" placeholder="Username case sensitively">
      <button class="main_btn_fill fixRed" id="addAdm" onclick="addAdmin(\''.$g.'\')">
        Add admin
      </button>
    ';
  }

  // Change logo for group creator only
  if($_SESSION['username'] == $creator){
    $profile_pic_btn = '
      <span id="blackbb" class="bbbGr">
        <img src="/images/cac.png" onclick="return false;" id="ca"
          onmousedown="toggleElement(\'avatar_form\',\''.$g.'\')" width="20" height="20">
      </span>

      <form id="avatar_form" enctype="multipart/form-data" method="post"
        action="/php_parsers/group_parser2.php" class="grelem">
        <div id="godownal">
          <input type="file" name="avatar" id="file" class="inputfile ppChoose"
            style="font-size: 12px;" required accept="image/*">
          <label for="file" style="font-size: 12px; margin-bottom: 5px;">Choose a file</label>
          <br>
          <input type="submit" value="Upload" class="main_btn_fill fixRed">
        </div>
      </form>
    ';
  }

  // Check how many posts recorded
  $record_count = cntRecords($conn, $g);
  
  $post_c = cntTypes($conn, $g, $zero);
  $reply_c = cntTypes($conn, $g, $one);

  // Get related groups
  $rgroups = "";
  $all_friends = getUsersFriends($conn, $u, $log_username);

  $isrel = false;
  $allfmy = join("','", $all_friends);

  $myArray = array();
  $sql = "SELECT gname FROM gmembers WHERE mname = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($myArray, $row["gname"]);
  }
  $stmt->close();
  $myarr = join("','", $myArray);

  $sql = "SELECT DISTINCT gr.* FROM gmembers AS gm LEFT JOIN groups AS gr ON
    gr.name = gm.gname WHERE gm.mname IN ('$allfmy') AND gm.mname != ? AND gr.creator != ?
    AND gr.name NOT IN ('$myarr') ORDER BY RAND() LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $log_username, $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $rgroups .= genGrBox($row);
    }
    $stmt->close();
  }else{
    $sql = "SELECT gr.* FROM groups AS gr LEFT JOIN gmembers AS gm ON gr.name = gm.gname
      WHERE gm.mname != ? AND gr.creator != ? AND gr.name NOT IN ('$myarr') ORDER BY RAND()
      LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $rgroups .= genGrBox($row);
      }
    }else{
      $isrel = true;
      $rgroups = '
        <p style="color: #999; text-align: center;">
          Unfortunately there are no related groups at the moment
        </p>
      ';
    }
  }

  // Get my groups
  $myallgroups = "";
  $sql = "SELECT DISTINCT gr.* FROM gmembers AS gm LEFT JOIN groups AS gr
    ON gm.gname = gr.name WHERE gm.mname = ? AND gm.approved = ? ORDER BY RAND() LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $log_username, $one);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $myallgroups .= genGrBox($row);
  }
  
  $ismyis = false;
  if($myallgroups == ""){
    $myallgroups = '
      <p style="color: #999; text-align: center;">
        You are not in any groups at the moment.
        Create a new<br>one or join to an existing one.
      </p>';
    $ismyis = true;
  }

  $g_echo = wrapText($g, 70); 
  
  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
    $rgroups = '
      <p style="color: #999; font-size: 16px;" class="txtc">
        Please <a href="/login">log in</a> in order to see related groups
      </p>
    ';
    $myallgroups = '
      <p style="color: #999; font-size: 16px;" class="txtc">
        Please <a href="/login">log in</a> in order to see your groups
      </p>
    ';
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $g; ?></title>
  <meta charset="utf-8">
  <meta name="description" content="<?php echo $g; ?> group. Join and have a conversation
    with people with the same interests as you in a Pearscom group.">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />

  <script type="text/javascript">
    const GNAME = '<?php echo $g ?>';
    const UNAME = '<?php echo $log_username; ?>';
  </script>
  <script src='/js/specific/p_dialog.js' defer></script>
  <script src='/js/specific/error_dialog.js' defer></script>
  <script src='/js/specific/open_emoji.js' defer></script>
  <script src='/js/specific/group.js' defer></script>
  <script src='/js/specific/see_hide.js' defer></script>
  <script src='/js/specific/open_emoji.js' defer></script>
  <script src='/js/specific/insert_emoji.js' defer></script>
  <script src='/js/specific/upload_funcs.js' defer></script>
  <script src='/js/specific/btn_div.js' defer></script>
  <script src='/js/specific/post_reply.js' defer></script>
  <script src='/js/specific/delete_post.js' defer></script>
    <script type="text/javascript"> 
    function replyPost(name, options) {
      var query_string = "replytext_" + name;
      var c = _(query_string).value;
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
      _("swithidbr_" + name).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          var addedItemIDs = xhr.responseText.split("|");
          if ("reply_ok" == addedItemIDs[0]) {
            var id = addedItemIDs[1];
            _("status_" + id).innerHTML += '<div id="reply_' + id + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + id + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + id + "','reply_" + id + '\');" title="Delete Comment">X</button></span><br />' + line + "</div></div>";
            _(query_string).value = "";
            _("swithidbr_" + name).innerHTML = '<button id="replyBtn_' + id + '" class="btn_rply" onclick="replyPost(\''+name+'\', \''+options+'\')">Reply</button>';
            _("triggerBtn_SP_reply").style.display = "block";
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
      xhr.send("action=post_reply&sid=" + name + "&data=" + c + "&g=" + options + "&image=" + hasImage);
    }
    function deleteReply(siteId, data) {
      if (1 != confirm("Are you sure you want to delete this reply? We will not be able to recover it!")) {
        return false;
      }
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          if ("delete_ok" == xhr.responseText) {
            _(data).style.display = "none";
          } else {
            alert(xhr.responseText);
          }
        }
      };
      xhr.send("action=delete_reply&replyid=" + siteId);
    }
    
    var hasImage = "";
    window.onbeforeunload = function() {
      if ("" != hasImage) {
        return "You have not posted your image";
      }
    };
    
    function toggleLike(payload, data, opts, result) {
      var res = ajaxObj("POST", "/php_parsers/gr_like_system.php");
      res.onreadystatechange = function() {
        if (1 == ajaxReturn(res)) {
          if ("like_success" == res.responseText) {
            _(opts).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + data + "','likeBtn_" + data + '\', \''+result+'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanf_" + data).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanf_" + data).innerText = ++e + " likes";
          } else {
            if ("unlike_success" == res.responseText) {
              _(opts).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + data + "','likeBtn_" + data + '\', \''+result+'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
              e = (e = (e = _("ipanf_" + data).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
              e = Number(e);
              _("ipanf_" + data).innerText = --e + " likes";
            } else {
              _("overlay").style.display = "block";
              _("overlay").style.opacity = .5;
              _("dialogbox").style.display = "block";
              _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
              document.body.style.overflow = "hidden";
            }
          }
        }
      };
      res.send("type=" + payload + "&id=" + data + "&group=" + result);
    }
    function toggleLike_reply(isSlidingUp, current_notebook, k, command) {
      var request = ajaxObj("POST", "/php_parsers/gr_like_system_reply.php");
      request.onreadystatechange = function() {
        if (1 == ajaxReturn(request)) {
          if ("like_success_reply" == request.responseText) {
            _(k).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + current_notebook + "','likeBtn_reply_" + current_notebook + '\', \''+command+'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanr_" + current_notebook).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + current_notebook).innerText = ++e + " likes";
          } else {
            if ("unlike_success_reply" == request.responseText) {
              _(k).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + current_notebook + "','likeBtn_reply_" + current_notebook + '\', \''+command+'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
              e = (e = (e = _("ipanr_" + current_notebook).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
              e = Number(e);
              _("ipanr_" + current_notebook).innerText = --e + " likes";
            } else {
              _("overlay").style.display = "block";
              _("overlay").style.opacity = .5;
              _("dialogbox").style.display = "block";
              _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
              document.body.style.overflow = "hidden";
            }
          }
        }
      };
      request.send("type=" + isSlidingUp + "&id=" + current_notebook + "&group=" + command);
    }

    function statusMax(match, i) {
      if (match.value.length > i) {
        _("overlay").style.display = "block";
        _("overlay").style.opacity = .5;
        _("dialogbox").style.display = "block";
        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = "hidden";
        match.value = match.value.substring(0, i);
      }
    } 
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="overlay"></div>
  <div id="pageMiddle_2">
    <div id="dialogbox"></div>
    <div class="biggerHolder">
    <div id="gr_upper" class="genWhiteHolder">
      <div id="gr_icon_box" data-src='<?php echo $profile_pic; ?>' class="genBg lazy-bg"><?php echo $profile_pic_btn; ?><?php echo $avatar_form; ?></div>
      <?php echo $joinBtn; ?>
      <?php if(in_array($_SESSION['username'], $approved)){ ?>
        <button id="quitBtn" class="main_btn_fill fixRed btnUimg" onclick="quitGroup('<?php echo $g; ?>')">Quit group</button>
      <?php } ?>

      <p class="grHeading"><?php echo $g_echo; ?></p>
      <p><b>Established: </b><?php echo date('F d, Y', strtotime($gCreation)); ?></p>
      <p><b>Created by: </b><?php echo $creator; ?></p>
      <?php echo $creator_echo; ?>
      <div class="clear"></div>
      <p><b>Type: </b><?php echo $invRule; ?></p>

      <p style="font-size: 14px;">
        <b>Moderators: </b>
        <?php echo $mod_count; ?>
        <br>
      </p>
      <?php echo $moderatorsPics; ?>
      <div class="clear"></div>

      <div id="gborder">
        <p style="font-size: 14px;"><b>Members: </b><?php echo $mem_count; ?><br>
          <div id="groupmembers">
            <?php echo $gMembers; ?>
          </div>
        </p>
      </div>
      <div class="clear"></div>

      <?php echo $addAdmin; ?>

      <div id="left_side">
        <div id="pending_holder">
        <?php echo $addMembers; ?>
        </div>
        <hr class="dim">
      </div>
      <div class="clear"></div>

      <?php if(in_array($_SESSION["username"], $moderators) && $gr_des == NULL){ ?>
        <div id="grdes_holder">
          <p style="font-size: 16px;">New description</p>
          <textarea id="desgivegr" style="width: 100%; margin-top: 0;" class="ssel" placeholder="Give a description about the group" onkeyup="statusMax(this, 3000)"></textarea>
          <button id="des_save_btn" class="main_btn_fill fixRed" onclick="saveDesGr()">Save description</button>
        </div>
      <?php }else{ ?>
        <div id="grdes_holder">
          <b style="font-size: 14px;">Description: </b><span id="current_des"><p style="font-size: 14px; margin-top: 0;" id="hide_<?php echo $gr_id; ?>"><?php echo $gr_des; ?><?php echo $gr_des_old; ?></p></span>
          <?php if(in_array($_SESSION['username'], $moderators)){ ?><span id="hdit"><button class="main_btn_fill fixRed" onclick="changeDesGr()">Change description</button></span><?php } ?>
        </div>
      <?php } ?>
    </div>
    <?php echo "<p style='color: #999; text-align: center; id='ghere'>".$record_count." comments recorded</p>"; ?>
    <?php if(in_array($u, $app_array)){
      echo $btfo;
    } ?>
    <?php if(in_array($_SESSION['username'], $approved)){ ?>
      <?php echo $status_ui; ?>
    <?php } ?>
  <div id="listBlabs">
    <?php if(!in_array($_SESSION['username'], $approved)){ ?>
        <p style="color: #999;" class="txtc">Claim a membership from the group leader to see the comments</p>
    <?php } ?>
    <?php 
      if(in_array($_SESSION['username'], $approved)){
        echo $mainPosts;
      }
    ?>
  </div>
  </div>
  <div id="uptoea">
      <div class="compdiv genWhiteHolder">
        <b style="font-size: 16px;">Related groups</b>
        <div class="relgroups" id="relgs">
          <?php echo $rgroups ?>
          </div>
        </div>

      <div class="compdiv genWhiteHolder">
        <b style="font-size: 16px;">My groups</b>
        <div class="relgroups" id="mygs">
          <?php echo $myallgroups; ?>
          </div>
        </div>
    </div>
  <div class="clear"></div>
  <div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
	function getCookie(e) {
    for (var l = e + "=", s = decodeURIComponent(document.cookie).split(";"), o = 0; o < s.length; o++) {
        for (var r = s[o];
            " " == r.charAt(0);) r = r.substring(1);
        if (0 == r.indexOf(l)) return r.substring(l.length, r.length)
    }
    return ""
}

function setDark() {
    var e = "thisClassDoesNotExist";
    if (!document.getElementById(e)) {
        var l = document.getElementsByTagName("head")[0],
            s = document.createElement("link");
        s.id = e, s.rel = "stylesheet", s.type = "text/css", s.href = "/style/dark_style.css", s.media = "all", l.appendChild(s)
    }
}
var isdarkm = getCookie("isdark");
"yes" == isdarkm && setDark();
var us = "less";

function showReply(e, l) {
    "less" == us ? (_("showreply_" + e).innerText = "Hide replies (" + l + ")", _("allrply_" + e).style.display = "block", us = "more") : "more" == us && (_("showreply_" + e).innerText = "Show replies (" + l + ")", _("allrply_" + e).style.display = "none", us = "less")
}
  let cur = `<?php echo $gr_des; ?>`;
  let old = `<?php echo $gr_des_old; ?>`;

  if(old == "") old = cur;

  old = old.replace(/<br \/>/g, "\n");

  function changeDesGr(){
    _("grdes_holder").innerHTML = `<p style="font-size: 16px;">New description</p>
          <textarea id="desgivegr" style="width: 100%; margin-top: 0;" class="ssel" placeholder="Give a description about the group" onkeyup="statusMax(this, 3000)">${old}</textarea>
          <button id="des_save_btn" class="main_btn_fill fixRed" onclick="saveDesGr()">Save description</button>`;
  }

  </script>
</body>
</html>
