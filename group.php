<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'timeelapsedstring.php';
    require_once 'headers.php';
    require_once 'ccovg.php';
    require_once 'elist.php';
	require_once 'php_includes/dist.php';
    
    // Select user's lat and lon
    $sql = "SELECT lat, lon FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($lat,$lon);
    $stmt->fetch();
    $stmt->close();
    $one = "1";
    $u = $_SESSION["username"];
    // Select the member from the users table
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
      $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$u,$one);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      // Now make sure the user exists in the table
      if($numrows < 1){
        header('location: /usernotexist');
        exit();
      }
    }
  // Initialize any variables that the page might echo
  $g = "";
    $gr_id = "";
    $post_id = "";
    $statusreplyid = "";
    $gName = "";
    $gCreation = "";
    $gLogo = "";
    $invRule = "";
    $privRule = "";
    $creator = "";
    $gMembers = "";
    $moderators = array();
    $approved = array();
    $pending = array();
    $all = array();
    $joinBtn = "";
    $addMembers = "";
    $addAdmin = "";
    $profile_pic_btn = "";
    $avatar_form = "";
    $mainPosts = "";
    $one = "1";
    $zero = "0";
    $gr_des = "";
    $mod_string = "";
    $mem_count = 0;
    $app_string = "";
    $moderatorsPics = "";

    // Make sure the $_GET group name is set, and sanitize it
    if(isset($_GET["g"])){
      $g = mysqli_real_escape_string($conn, $_GET["g"]);
    }else{
      header('Location: /index');
      exit();
    }
    
    $_SESSION["gname"] = $g;

  // This first query is just to get the total count of rows
  $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ? AND type = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$g,$zero);
  $stmt->execute();
  $stmt->bind_result($rows);
  $stmt->fetch();
  $stmt->close();
  // Here we have the total row count
  // This is the number of results we want displayed per page
  $page_rows = 10;
  // This tells us the page number of our last page
  $last = ceil($rows/$page_rows);
  // This makes sure $last cannot be less than 1
  if($last < 1){
    $last = 1;
  }
  // Establish the $pagenum variable
  $pagenum = 1;
  // Get pagenum from URL vars if it is present, else it is = 1
  if(isset($_GET['pn'])){
    $pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
  }
  // This makes sure the page number isn't below 1, or more than our $last page
  if ($pagenum < 1) { 
      $pagenum = 1; 
  } else if ($pagenum > $last) { 
      $pagenum = $last; 
  }
  // This sets the range of rows to query for the chosen $pagenum
  $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
  // Establish the $paginationCtrls variable
  $paginationCtrls = '';
  // If there is more than 1 page worth of results
  if($last != 1){
    /* First we check if we are on page one. If we are then we don't need a link to 
       the previous page or the first page so we do nothing. If we aren't then we
       generate links to the first page, and to the previous page. */
    if ($pagenum > 1) {
          $previous = $pagenum - 1;
      $paginationCtrls .= '<a href="/group/'.$g.'&pn='.$previous.'#ghere">Previous</a> &nbsp; &nbsp; ';
      // Render clickable number links that should appear on the left of the target page number
      for($i = $pagenum-4; $i < $pagenum; $i++){
        if($i > 0){
              $paginationCtrls .= '<a href="/group/'.$g.'&pn='.$i.'#ghere">'.$i.'</a> &nbsp; ';
        }
        }
      }
    // Render the target page number, but without it being a link
    $paginationCtrls .= ''.$pagenum.' &nbsp; ';
    // Render clickable number links that should appear on the right of the target page number
    for($i = $pagenum+1; $i <= $last; $i++){
      $paginationCtrls .= '<a href="/group/'.$g.'&pn='.$i.'#ghere">'.$i.'</a> &nbsp; ';
      if($i >= $pagenum+4){
        break;
      }
    }
    // This does the same as above, only checking if we are on the last page, and then generating the "Next"
      if ($pagenum != $last) {
          $next = $pagenum + 1;
          $paginationCtrls .= ' &nbsp; &nbsp; <a href="/group/'.$g.'&pn='.$next.'#ghere">Next</a> ';
      }
  }

    // Select the group from the users table
  $sql = "SELECT * FROM groups WHERE name=? LIMIT 1";
  // Make sure that group exists and get group data
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$g);
  $stmt->execute();
  $stmt->store_result();
  $stmt->fetch();
  $numrows = $stmt->num_rows;
  if($numrows < 1){
    header('Location: /index');
    exit();
  }else{
    $stmt->close();
    // Get data about group
    $sql_ = "SELECT * FROM groups WHERE name=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$g);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $gr_id = $row["id"];
      $gName = $row["name"];
      $gCreation = $row["creation"];
      $gLogo = $row["logo"];
      $invRule = $row["invrule"];
      $creator = $row["creator"];
      $gr_des = $row["des"];
      $gr_des_old = $row["des"];
      $gr_des_old = str_replace( '\n', '<br />', $gr_des_old ); 
      if(strlen($gr_des) > 250){
        $gr_des = mb_substr($gr_des, 0,250, "utf-8");
        $gr_des .= " ...";
        $gr_des .= '&nbsp;<a id="toggle_gr_'.$gr_id.'" onclick="opentext_gr(\''.$gr_id.'\')">See More</a>';
        $gr_des_old = '<div id="lessmore_gr_'.$gr_id.'" class="lmml" style="font-size: 14px;">'.$gr_des_old.'&nbsp;<a id="toggle_gr_'.$gr_id.'" onclick="opentext_gr(\''.$gr_id.'\')">See Less</a></div>';
      }else{
        $gr_des_old = "";
      }
      $gr_des = str_replace( '\n', '<br />', $gr_des ); 

      if($invRule == 0){
        $invRule = "Private group";
      }else{
        $invRule = "Public group";
      }
    }
    $stmt->close();
  }

  $sql = "SELECT avatar FROM users WHERE username = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$creator);
  $stmt->execute();
  $stmt->bind_result($cre_pp);
  $stmt->fetch();
  $stmt->close();

  if($cre_pp == NULL){
      $pcurl = "/images/avdef.png";
  }else{
      $pcurl = "/user/".$creator."/".$cre_pp;
  }
  $creator_echo = '<a href="/user/'.$creator.'/" style="float: left;"><div data-src=\''.$pcurl.'\' style="width: 50px; height: 50px; border-radius: 50%;" class="genBg lazy-bg grCreat"></div></a>';

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
  $stmt->bind_param("s",$g);
  $stmt->execute();
  $result2 = $stmt->get_result();
  while($row2 = $result2->fetch_assoc()){
    $mName = $row2["mname"];
    $app = $row2["approved"];
    $admin = $row2["admin"];
    $avatar = $row2["avatar"];
    // Set user image
    $member_pic = '/user/'.$mName.'/'.$avatar;
    if($avatar == NULL){
      $member_pic = '/images/avdef.png';
    }

    // Determine if approved
    switch($app){
      case 0:
        array_push($pending, $mName);
      break;

      case 1:
        array_push($approved, $mName);
      break;
    }

    array_push($all, $mName);

    // Determine if admin
    if($admin == 1){
      if(!in_array($mName, $moderators)){
        array_push($moderators, $mName);
      }
    }

    // Get all counts
    $mod_count = count($moderators);
    $app_count = count($approved);
    $pend_count = count($pending);
    
    $mem_count = $app_count - $mod_count;

    $mod_slice = array_slice($moderators, 0, 6);
    $mod_string = join("','",$mod_slice);

    //echo $mod_string;
    
    $app_array = array_diff($approved, $moderators);
    
    $app_slice = array_slice($app_array, 0, 2);
    $app_string = implode(", ",$app_slice);

    // Output
    if(in_array($mName, $app_array)){
      $gMembers .= '<a href="/user/'.$mName.'/"><div data-src=\''.$member_pic.'\' style="width: 50px; height: 50px; margin-right: 5px; float: left; border-radius: 50%;" class="genBg lazy-bg grMem"></div></a>';
    }
  }
  // Join group button
  if ((isset($_SESSION['username'])) && (!in_array($_SESSION['username'],$all))){
    $joinBtn = '<button id="joinBtn" class="main_btn_fill fixRed btnUimg" onclick="joinGroup(\''.$g.'\')">Join group</button>';
  }else if(in_array($_SESSION['username'], $pending)){
      $joinBtn = '<p style="font-size: 14px; color: #999;" class="btnUimg wfa">Waiting for approval</p>';
  }

  $stmt->close();

  if(count($app_array) < 1){
    $gMembers = '<p style="color: #999; font-size: 14px;">There are no members in this group</p>';
  }

  $sqlMods = "SELECT avatar, username FROM users WHERE username IN('$mod_string')";
    $stmt = $conn->prepare($sqlMods);
    $stmt->execute();
    $resMods = $stmt->get_result();
    while($rowMods = $resMods->fetch_assoc()){
      $uLogo = $rowMods["avatar"];
      $uName = $rowMods["username"];
      if($uLogo == NULL){
        $uLogo = '/images/avdef.png';
      }else{
        $uLogo = '/user/'.$uName.'/'.$uLogo;
      }
      $moderatorsPics .= '<a href="/user/'.$uName.'/" style="float: left;"><div class="genBg lazy-bg grMod" data-src=\''.$uLogo.'\' style="width: 50px; height: 50px; border-radius: 50%; margin-right: 5px;"></div></a>';
    }
    $stmt->close();

  // Build posting mechanism
  // Get all thread starting post
  if(in_array($_SESSION['username'], $approved)){
    $status_ui = '<textarea id="statustext" class="user_status" onfocus="showBtnDiv()" placeholder="What&#39;s in your mind?"></textarea>';
      $status_ui .= '<div id="uploadDisplay_SP"></div>';
      $status_ui .= '<div id="pbc">
                <div id="progressBar"></div>
                <div id="pbt"></div>
                  </div>';
      $status_ui .= '<div id="btns_SP" class="hiddenStuff" style="width: 90%;">';
        $status_ui .= '<span id="swithspan"><button id="statusBtn" onclick="newPost(\''.$g.'\')" class="btn_rply">Post</button></span>';
        $status_ui .= '<img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply" onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22" title="Upload A Photo"/>';
        $status_ui .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox()">';
        $status_ui .= '<div class="clear"></div>';
        $status_ui .= generateEList("x", 'emojiBox_group', 'statustext');
      $status_ui .= '</div>';
      $status_ui .= '<div id="standardUpload" class="hiddenStuff">';
        $status_ui .= '<form id="image_SP" enctype="multipart/form-data" method="post">';
        $status_ui .= '<input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')" accept="image/*"/>';
        $status_ui .= '</form>';
      $status_ui .= '</div>';
      $status_ui .= '<div class="clear"></div>';
    }
  $sql = "SELECT gp.*, u.*, gp.id AS grouppost_id 
          FROM grouppost AS gp
          LEFT JOIN users AS u ON u.username = gp.author
          WHERE gp.gname = ? 
            AND gp.type = ? 
          ORDER BY gp.pdate DESC
          $limit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$g,$zero);
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
  $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
  $isonimg = '';
  if($ison == "yes"){
      $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
  }else{
      $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
  }
  if($avatar != ""){
    $friend_pic = '/user/'.$post_auth.'/'.$avatar.'';
  } else {
    $friend_pic = '/images/avdef.png';
  }
  $funames = $post_auth;
  if(strlen($funames) > 20){
      $funames = mb_substr($funames, 0, 16, "utf-8");
      $funames .= " ...";
  }
  if(strlen($fuco) > 20){
      $fuco = mb_substr($fuco, 0, 16, "utf-8");
      $fuco .= " ...";
  }
  $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss",$post_auth,$post_auth,$one);
  $stmt->execute();
  $stmt->bind_result($numoffs);
  $stmt->fetch();
  $stmt->close();
    if($post_avatar != NULL){
      $avatar_pic = '/user/'.$post_auth.'/'.$post_avatar;
    }else{
      $avatar_pic = '/images/avdef.png';
    }
    $user_image = "";
    $agoform = time_elapsed_string($post_date_);
    if($post_auth == $log_username){
      $class = "round";
    }else{
        $class = "margin-bottom: 7px;";
    }

    $style = "";
    if($post_auth == $log_username){
      $style = "margin-left: -11px;";
    }
    
    $cClass = "";
    if($post_auth == $creator){
        $cClass = "grCreat";
    }else if(in_array($post_auth, $moderators)){
        $cClass = "grMem";
    }else{
        $cClass = "grMem";
    }
        
    $user_image = '<a href="/user/'.$post_auth.'"><div data-src=\''.$avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; '.$style.' background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob lazy-bg '.$cClass.'"></div><div class="infostdiv"><div data-src=\''.$avatar_pic.'\' style="background-repeat: no-repeat; float: left; background-size: cover; border-radius: 50%; background-position: center; width: 60px; height: 60px; display: inline-block;" class="lazy-bg '.$cClass.'"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';

      $statusDeleteButton = '';
      if($post_auth == $log_username){
        $statusDeleteButton = '<span id="sdb_'.$post_id.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" onclick="return false;" onmousedown="deleteStatus(\''.$post_id.'\',\'status_'.$post_id.'\');" title="Delete Post And Its Replies">X</button></span> &nbsp; &nbsp;';
      }

      // Add share button
      $shareButton = "";
      if($log_username != "" && $post_auth != $log_username){
        $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus(\'' . $post_id . '\');" id="shareBlink" style="vertical-align: middle;">';
      }

      $isLike = false;
      if($user_ok == true){
        $like_check = "SELECT id FROM group_status_likes WHERE username=? AND gpost=? AND gname = ? LIMIT 1";
        $stmt = $conn->prepare($like_check);
        $stmt->bind_param("sis",$log_username,$post_id,$g);
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
        $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\''.$post_id.'\',\'likeBtn_'.$post_id.'\',\''.$g.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
        $$likeText = '<span style="vertical-align: middle;">Dislike</span>';
      }else{
        $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\''.$post_id.'\',\'likeBtn_'.$post_id.'\',\''.$g.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
        $likeText = '<span style="vertical-align: middle;">Like</span>';
      }

      $post_data_old = $row["data"];
      $post_data_old = nl2br($post_data_old);
	    $post_data_old = str_replace("&amp;","&",$post_data_old);
	    $post_data_old = stripslashes($post_data_old);
      $pos = strpos($data_old,'<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    		    
    $isex = false;
	$sec_data = "";
	$first_data = "";
	if(strpos($post_data_old,'<img src="/permUploads/') !== false){
	    $split = explode('<img src="/permUploads/',$post_data_old);
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
	if(strlen($post_data) > 1000){
	    if($pos === false && $isex == false){
		    $post_data = mb_substr($post_data, 0,1000, "utf-8");
			$post_data .= " ...";
			$post_data .= '&nbsp;<a id="toggle_'.$post_id.'" onclick="opentext(\''.$post_id.'\')">See More</a>';
			$post_data_old = '<div id="lessmore_'.$post_id.'" class="lmml"><p id="status_text">'.$post_data_old.'&nbsp;<a id="toggle_'.$post_id.'" onclick="opentext(\''.$post_id.'\')">See Less</a></p></div>';
	    }else{
	        $post_data_old = "";
	    }
	}else{
		$post_data_old = "";
	}
        $post_data = nl2br($post_data);
		$post_data = str_replace("&amp;","&",$post_data);
		$post_data = stripslashes($post_data);
      // <b class="ispan">('.$cl.')</b> <span id="likeBtn">'.$likeButton.'</span> <div id="isornot_div">'.$isLikeOrNot.'</div>
      // '.$showmore.'<span id="allrply_'.$post_id.'" class="hiderply">'.$status_replies.'</span>
      
      // Get replies and user images using inner loop
      $status_replies = "";
      $sql_b = 'SELECT g.*, u.*
           FROM grouppost AS g
           LEFT JOIN users AS u ON u.username = g.author
          WHERE g.pid = ? AND g.type = ? ORDER BY g.pdate DESC';
      $stmt = $conn->prepare($sql_b);
      $stmt->bind_param("is",$post_id,$one);
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
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';
        if($ison == "yes"){
            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
        }else{
            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
        }
        if($avatar2 != ""){
          $friend_pic = '/user/'.$reply_auth.'/'.$avatar2.'';
        } else {
          $friend_pic = '/images/avdef.png';
        }
        $funames = $reply_auth;
        if(strlen($funames) > 20){
            $funames = mb_substr($funames, 0, 16, "utf-8");
            $funames .= " ...";
        }
        if(strlen($fucor) > 20){
            $fucor = mb_substr($fucor, 0, 16, "utf-8");
            $fucor .= " ...";
        }
        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",$reply_auth,$reply_auth,$one);
        $stmt->execute();
        $stmt->bind_result($numoffs);
        $stmt->fetch();
        $stmt->close();
        if($reply_avatar != NULL){
          $re_avatar_pic = '/user/'.$reply_auth.'/'.$reply_avatar;
        }else{
            $re_avatar_pic = '/images/avdef.png';
        }
        
        $cClass = "";
        if($reply_auth == $creator){
            $cClass = "grCreat";
        }else if(in_array($reply_auth, $moderators)){
            $cClass = "grMem";
        }else{
            $cClass = "grMem";
        }
        
        $reply_image = '<a href="/user/'.$reply_auth.'/"><div data-src=\''.$re_avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg '.$cClass.'"></div><div class="infotsrdiv"><div data-src=\''.$re_avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; float: left; border-radius: 50%; display: inline-block;" class="tshov lazy-bg '.$cClass.'"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fucor.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';

          $replyDeleteButton = '';
          if($reply_auth == $log_username){
            $replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_'.$statusreplyid.'\');" title="Delete Comment">X</button ></span>';
          }
          $agoformrply = time_elapsed_string($reply_date_);
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
		if(strlen($reply_data) > 1000){
		    if($isex == false){
			    $reply_data = mb_substr($reply_data, 0,1000, "utf-8");
				$reply_data .= " ...";
				$reply_data .= '&nbsp;<a id="toggle_reply_'.$statusreplyid.'" onclick="opentext_reply(\''.$statusreplyid.'\')">See More</a>';
				$data_old_reply = '<div id="lessmore_reply_'.$statusreplyid.'" class="lmml"><p id="status_text">'.$data_old_reply.'&nbsp;<a id="toggle_reply_'.$statusreplyid.'" onclick="opentext_reply(\''.$statusreplyid.'\')">See Less</a></p></div>';
		    }else{
		        $data_old_reply = "";
		    }
		}else{
			$data_old_reply = "";
		}
        $reply_data = nl2br($reply_data);
		$reply_data = str_replace("&amp;","&",$reply_data);
		$reply_data = stripslashes($reply_data);
          $isLike_reply = false;
          if($user_ok == true){
            $like_check_reply = "SELECT id FROM group_reply_likes WHERE username=? AND gpost=? AND gname=? LIMIT 1";
            $stmt = $conn->prepare($like_check_reply);
            $stmt->bind_param("sis",$log_username,$statusreplyid,$g);
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
            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\''.$statusreplyid.'\',\'likeBtn_reply_'.$statusreplyid.'\',\''.$g.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
            $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
          }else{
            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\''.$statusreplyid.'\',\'likeBtn_reply_'.$statusreplyid.'\',\''.$g.'\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
            $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
          }

            // Count reply likes
            $sql = "SELECT COUNT(id) FROM group_reply_likes WHERE gpost = ? AND gname = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is",$statusreplyid,$g);
            $stmt->execute();
            $stmt->bind_result($rpycount);
            $stmt->fetch();
            $stmt->close();
            $rpycl = ''.$rpycount;

            $try_reply = "";
            if($reply_auth == $creator){
              $try_reply = '<b style="font-size: 12px !important; font-weight: normal; color: #FFDA44;">'.$reply_auth.'&nbsp;<img src="/images/crown.png" width="12" height="12" title="Post by group leader"></b>';
            }else if(in_array($reply_auth, $moderators)){
              $try_reply = '<b style="font-size: 12px !important; font-weight: normal; color: #006DF0;" title="Post by a moderator">'.$reply_auth.'</b>';
            }else{
              $try_reply = '<b style="font-size: 12px !important; font-weight: normal; color: #999;" title="Post by a member">'.$reply_auth.'</b>';
            }

          // Build replies
          $status_replies .= '
          <div id="reply_'.$statusreplyid.'" class="reply_boxes">
            <div>'.$replyDeleteButton.'
            <p id="float">
              <b class="sreply">Reply: </b>
              <b class="rdate">
                <span class="tooLong">'.$reply_date.'</span> ('.$agoformrply.' ago)
              </b>
            </p>'.$reply_image.'
            <p id="reply_text">
              <b class="sdata" id="hide_reply_'.$statusreplyid.'">'.$reply_data.''.$data_old_reply.'
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
          </div>';
        }
      }

      // Count likes
      $sql = "SELECT COUNT(id) FROM group_status_likes WHERE gname = ? AND gpost = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si",$g,$post_id);
      $stmt->execute();
      $stmt->bind_result($count);
      $stmt->fetch();
      $stmt->close();
      $cl = ''.$count;

      // Count the replies
      $sql = "SELECT COUNT(id) FROM grouppost WHERE type = ? AND gname = ? AND pid = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssi",$one,$g,$post_id);
      $stmt->execute();
      $stmt->bind_result($countrply);
      $stmt->fetch();
      $stmt->close();

      $crply = ''.$countrply;

      $showmore = "";
      if($countrply > 0){
        $showmore = '<div class="showrply"><a id="showreply_'.$post_id.'" onclick="showReply('.$post_id.','.$crply.')">Show replies ('.$crply.')</a></div>';
      }

      $try = "";
      if($post_auth == $creator){
        $try = '<b style="font-size: 12px !important; font-weight: normal; color: #FFDA44;">'.$post_auth.'&nbsp;<img src="/images/crown.png" width="12" height="12" title="Post by group leader"></b>';
      }else if(in_array($post_auth, $moderators)){
        $try = '<b style="font-size: 12px !important; font-weight: normal; color: #006DF0;" title="Post by a moderator">'.$post_auth.'&nbsp;</b>';
      }else{
        $try = '<b style="font-size: 12px !important; font-weight: normal; color: #999;" title="Post by a member">'.$post_auth.'&nbsp;</b>';
      }
      
      if(strlen($post_auth) > 12){
        $post_auth = mb_substr($post_auth, 0, 8, "utf-8");
        $post_auth .= ' ...';
      }

      // Build threads
      $mainPosts .= '<div id="status_'.$post_id.'" class="status_boxes">
            <div>'.$statusDeleteButton.'
              <p id="status_date">
                <b class="status_title">Post: </b>
                <b class="pdate">
                  <span class="tooLong">'.$post_date.'</span> ('.$agoform.' ago)
                </b>
              </p>'.$user_image.'
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
        </div>'.$showmore.'<span id="allrply_'.$post_id.'" class="hiderply">'.$status_replies.'</span>
        </div>';
      $mainPosts .= '</div><div class="clear">';

      // Time to build the Reply To section
      $mainPosts .= '<textarea id="replytext_'.$post_id.'" class="replytext" placeholder="Write a comment" onfocus="showBtnDiv_reply(\''.$post_id.'\')"></textarea>';
      $mainPosts .= '<div id="uploadDisplay_SP_reply_'.$post_id.'"></div>';
      $mainPosts .= '<div id="btns_SP_reply_'.$post_id.'" class="hiddenStuff rply_joiner">';
        $mainPosts .= '<span id="swithidbr_'.$post_id.'"><button id="replyBtn_'.$post_id.'" class="btn_rply" onclick="replyPost(\''.$post_id.'\',\''.$g.'\')">Reply</button></span>';
        $mainPosts .= '<img src="/images/camera.png" id="triggerBtn_SP_reply" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22" title="Upload A Photo" />';
        $mainPosts .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$post_id.')">';
        $mainPosts .= '<div class="clear"></div>';
        $mainPosts .= generateEList($post_id, 'emojiBox_reply_' . $post_id . '', 'replytext_'.$post_id.'');
      $mainPosts .= '</div>';
      $mainPosts .= '<div id="standardUpload_reply" class="hiddenStuff">';
        $mainPosts .= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
        $mainPosts .= '<input type="file" name="FileUpload" id="fu_SP_reply" onchange="doUpload_reply(\'fu_SP_reply\', \''.$post_id.'\')" accept="image/*"/>';
        $mainPosts .= '</form>';
      $mainPosts .= '</div>';
    }
  }else{
    $btfo = "<p style='color: #999;' class='txtc'>Be the first one who post something!</p>";
  }
?>
<?php
  // Pending member section for admin
  if (in_array($_SESSION['username'],$moderators)){
    $addMembers = "<hr class='dim'><p style='font-size: 16px; margin-top: 5px;'>Pending members (".$pend_count.")</p><div class='horizontalScroll'>";
    if($pend_count == 0){
      $addMembers .= '<p style="color: #999; font-size: 14px;">There are no pending approvals at the moment</p>';
    }
    for($x=0;$x<$pend_count;$x++){
      $curuser = $pending[$x];
      $avatar = "";
      $country = "";
      $isonline = "";
      $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$curuser);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()){
        $avatar = $row["avatar"];
        $country = $row["country"];
        $isonline = $row["online"];
      }
      if($isonline == "yes"){
        $isonline = "border: 2px solid #00a1ff";
      }else{
        $isonline = "border: 2px solid grey";
      }
      
      if($avatar == NULL || $avatar == ""){
          $avatar = '/images/avdef.png';
      }else if($avatar != NULL && $avatar != ""){
        $avatar = '/user/'.$curuser.'/'.$avatar;
      }

      $avatar = '<div data-src=\''.$avatar.'\' style="border-radius: 50%; width: 60px; height: 60px; float: left;" class="genBg lazy-bg"></div>';

      if(strlen($country) > 20){
        $country = mb_substr($country, 0, 16, "utf-8");
        $country .= ' ...';
      }

      $addMembers .= '<div class="wfaHolder"><a href="/user/'.$curuser.'/">'.$avatar.'</a>';
      $addMembers .= '<div id="btn_align">';
        $addMembers .= '<button id="appbtn" class="main_btn_fill fixRed" onclick="approveMember(\''.$curuser.'\',\''.$g.'\')" style="margin-bottom: 5px;">Approve</button><br />';
        $addMembers .= '<button id="appbtn_2" class="main_btn" onclick="declineMember(\''.$curuser.'\',\''.$g.'\')">Decline</button>';
      $addMembers .= '</div>';
      $addMembers .= '<div id="pending_data">';
        $addMembers .= '<p style="padding: 0px; margin: 0px;">'.$curuser.'</p>';
        $addMembers .= '<p style="padding: 0px; margin: 0px;">'.$country.'</p>';
      $addMembers .= '</div></div>';
    
    }
    $addMembers .= "</div>";
  }else if(!in_array($_SESSION["username"], $moderators) && in_array($_SESSION["username"], $approved)){
    $addMembers = '<hr class="dim"><p style="font-size: 16px; margin: 0;">Pending members</p>';
    $addMembers .= '<p style="color: #999; font-size: 14px;">Claim a promotion to be a moderator to see the group&#39;s pending approvals</p>';
  }
?>
<?php
  if(in_array($_SESSION['username'],$moderators)){
    $addAdmin = '<hr class="dim"><p style="font-size: 16px;">Add new admin to group</p>';
    $addAdmin .= '<input style="margin-top: 0;" type="text" class="ssel" name="new_admin" id="new_admin" placeholder="Username case sensitively">';
    $addAdmin .= '<button class="main_btn_fill fixRed" id="addAdm" onclick="addAdmin(\''.$g.'\')">Add admin</button>';
  }
?>
<?php
  // Change logo for group creator only
  if($_SESSION['username'] == $creator){
    $profile_pic_btn = '<span id="blackbb" class="bbbGr"><img src="/images/cac.png" onclick="return false;" id="ca" onmousedown="toggleElement(\'avatar_form\',\''.$g.'\')" width="20" height="20"></span>';
    $avatar_form  = '<form id="avatar_form" enctype="multipart/form-data" method="post" action="/php_parsers/group_parser2.php" class="grelem">';
    $avatar_form .=   '<div id="godownal">';
    $avatar_form .=   '<input type="file" name="avatar" id="file" class="inputfile ppChoose" style="font-size: 12px;" required accept="image/*">';
    $avatar_form .=   '<label for="file" style="font-size: 12px; margin-bottom: 5px;">Choose a file</label>';
    $avatar_form .=   '<br><input type="submit" value="Upload" class="main_btn_fill fixRed">';
     $avatar_form .=   '</div>';
    $avatar_form .= '</form>';
  }
?>
<?php
  // Check how many posts recorded
  $record_count = "";
  $post_c = "";
  $reply_c = "";
  $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$g);
  $stmt->execute();
  $stmt->bind_result($record_count);
  $stmt->fetch();
  $stmt->close();
  
  $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ? AND type = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$g,$zero);
  $stmt->execute();
  $stmt->bind_result($post_c);
  $stmt->fetch();
  $stmt->close();
  
  $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ? AND type = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$g,$one);
  $stmt->execute();
  $stmt->bind_result($reply_c);
  $stmt->fetch();
  $stmt->close();

  // Get related groups
  // FIRST GET FRIENDS
  $rgroups = "";
  $all_friends = array();
  $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($all_friends, $row["user1"]);
  }
  $stmt->close();

  $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($all_friends, $row["user2"]);
  }
  $stmt->close();
  $isrel = false;
  // Implode all friends array into a string
  $allfmy = join("','", $all_friends);

  $myArray = array();
  $sql = "SELECT gname FROM gmembers WHERE mname = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($myArray, $row["gname"]);
  }
  $stmt->close();
  $myarr = join("','", $myArray);

  $sql = "SELECT DISTINCT gr.* FROM gmembers AS gm LEFT JOIN groups AS gr ON gr.name = gm.gname WHERE gm.mname IN ('$allfmy') AND gm.mname != ? AND gr.creator != ? AND gr.name NOT IN ('$myarr') ORDER BY RAND() LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $logo = $row["logo"];
      $groupname = $row["name"];
      $gnameori = $groupname;
      $gnameori = urlencode($gnameori);
      $gnameim = $groupname;
      $est = $row["creation"];
      $creatorMy = $row["creator"];
      $est_ = strftime("%R, %b %d, %Y", strtotime($est));
      $agoform = time_elapsed_string($est);
      $des = $row["des"];

      if($logo != "gdef.png"){
        $logo = '/groups/'.$gnameim.'/'.$logo.'';
      }else{
        $logo = '/images/gdef.png';
      }

      if($des == NULL || $des == ""){
        $des = "not given";
      }

      $cat = $row["cat"];
      $cat = chooseCat($cat);

      $rgroups .= '<a href="/group/'.$gnameori.'"><div class="article_echo_2 artRelGen" style="height: auto; width: 100%;"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$groupname.'</p>';
      $rgroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
      $rgroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
      $rgroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
      $rgroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
    }
    $stmt->close();
  }else{
    $sql = "SELECT gr.* FROM groups AS gr LEFT JOIN gmembers AS gm ON gr.name = gm.gname WHERE gm.mname != ? AND gr.creator != ? AND gr.name NOT IN ('$myarr') ORDER BY RAND() LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
          $logo = $row["logo"];
          $groupname = $row["name"];
          $gnameori = urlencode($groupname);
          $gnameim = $groupname;
          $est = $row["creation"];
          $creatorMy = $row["creator"];
          $est_ = strftime("%R, %b %d, %Y", strtotime($est));
          $agoform = time_elapsed_string($est);
          $des = $row["des"];
    
          if($logo != "gdef.png"){
            $logo = '/groups/'.$gnameim.'/'.$logo.'';
          }else{
            $logo = '/images/gdef.png';
          }
    
          if($des == NULL || $des == ""){
            $des = "not given";
          }
    
          $cat = $row["cat"];
          $cat = chooseCat($cat);
    
          $rgroups .= '<a href=\'/group/'.$gnameori.'\'><div class="article_echo_2 artRelGen" style="height: auto; width: 100%;"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$groupname.'</p>';
          $rgroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
          $rgroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
          $rgroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
          $rgroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
        }
    }else{
        $isrel = true;
      $rgroups = '<p style="color: #999; text-align: center;">Unfortunately there are no related groups at the moment ...</p>';
    }
  }

  // Get my groups
  $myallgroups = "";
  $sql = "SELECT DISTINCT gr.* FROM gmembers AS gm LEFT JOIN groups AS gr ON gm.gname = gr.name WHERE gm.mname = ? AND gm.approved = ? ORDER BY RAND() LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $logo = $row["logo"];
    $groupname = $row["name"];
    $gnameim = $row["name"];
    $gnameori = urlencode($groupname);
    $crdate = $row["creation"];
    $crd = strftime("%b %d, %Y", strtotime($crdate));
    $creatorMy = $row["creator"];
    $des = $row["des"];

    $agoform = time_elapsed_string($crdate);
    $date = date('F d, Y', strtotime($crdate));
    if($logo == "gdef.png"){
      $logo = '/images/gdef.png';
    }else{
      $logo = '/groups/'.$gnameim.'/'.$logo.'';
    }

    $cat = $row["cat"];
    $cat = chooseCat($cat);

    $myallgroups .= '<a href=\'/group/'.$gnameori.'\'><div class="article_echo_2 artRelGen" style="height: auto; width: 100%;"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$groupname.'</p>';
    $myallgroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
    $myallgroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
    $myallgroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
    $myallgroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
  }
  
  $ismyis = false;
  if($myallgroups == ""){
      $myallgroups = '<i style="font-size: 14px;">You are not in any groups at the moment. Create a new<br>one or join to an existing one.</i>';
      $ismyis = true;
  }

  $g_echo = $g;
  if(strlen($g) > 70){
    $g_echo = mb_substr($g_echo, 0, 66, "utf-8");
    $g_echo .= ' ...';
  }
  
  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
      $rgroups = '<p style="color: #999; font-size: 16px;" class="txtc">Please <a href="/login">log in</a> in order to see related groups</p>';
      $myallgroups = '<p style="color: #999; font-size: 16px;" class="txtc">Please <a href="/login">log in</a> in order to see your groups</p>';
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $g; ?></title>
  <meta charset="utf-8">
  <meta name="description" content="<?php echo $g; ?> group. Join and have a conversation with people with the same interests as you in a Pearscom group.">
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
        function joinGroup(address) {
      var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
      request.onreadystatechange = function() {
        if (1 == ajaxReturn(request)) {
          var doctypeContent = request.responseText;
          if ("pending_approval" == doctypeContent) {
            _("joinBtn").style.display = "none";
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Awaiting approval</p><p>We have successfully sent your request to the group. Now you have to wait until a moderator approves your request to join this group.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
          if ("refresh_now" == doctypeContent) {
            _("joinBtn").style.display = "none";
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Success</p><p>You have successfully joined to this group. Refresh the page to actually join in.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }
      };
      request.send("action=join_group&g=" + address);
    }
    function approveMember(pollProfileId, userId) {
      var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
      request.onreadystatechange = function() {
        if (1 == ajaxReturn(request)) {
          if ("member_approved" == request.responseText) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Member approved</p><p>You have successfully approved ' + pollProfileId + '.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          } else {
            console.log(request.responseText);
          }
        }
      };
      request.send("action=approve_member&u=" + pollProfileId + "&g=" + userId);
    }
    function openEmojiBox_reply(name) {
      var cancel = _("emojiBox_reply_" + name);
      if ("block" == cancel.style.display) {
        cancel.style.display = "none";
      } else {
        cancel.style.display = "block";
      }
    }
    function declineMember(pollProfileId, userId) {
      var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
      request.onreadystatechange = function() {
        if (1 == ajaxReturn(request)) {
          if ("member_declined" == request.responseText) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Member declined</p><p>You declined ' + pollProfileId + '.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }else{
          _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occurred</p><p>' + request.responseText + '</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
        }
      };
      request.send("action=decline_member&u=" + pollProfileId + "&g=" + userId);
    }
    function addAdmin(i) {
      var vulnData = _("new_admin").value;
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          if ("admin_added" == xhr.responseText) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Moderator added</p><p>You have successfully added ' + vulnData + ' as a moderator.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }else{
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>'+xhr.responseText+'</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }
      };
      xhr.send("action=add_admin&n=" + vulnData + "&g=" + i);
    }
    function newPost(callback) {
      var c = _("statustext").value;
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
      _("swithspan").innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          var g_footnotes = xhr.responseText.split("|");
          if ("post_ok" == g_footnotes[0]) {
            var o = g_footnotes[1];
            var newHTML = _("listBlabs").innerHTML;
            _("listBlabs").innerHTML = '<div id="status_' + o + '" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_' + o + '"><button onclick="return false;" class="delete_s" onmousedown="deleteStatus(\'' + o + "','status_" + o + '\');" title="Delete Status And Its Replies">X</button></span><br />' + line + "</div></div>" + newHTML;
            _("swithspan").innerHTML = "<button id=\"statusBtn\" onclick=\"newPost('<?php echo $g; ?>')\" class='btn_rply'>Post</button>";
            _("statustext").value = "";
            _("triggerBtn_SP").style.display = "block";
            _("btns_SP").style.display = "none";
            _("uploadDisplay_SP").innerHTML = "";
            hasImage = "";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status post. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }
      };
      xhr.send("action=new_post&data=" + c + "&g=" + callback + "&image=" + hasImage);
    }
    function deleteStatus(id, status) {
      if (1 != confirm("By clicking on the OK button you delete this post and its all replies. Please note that if once you deleted it we cannot reset it!")) {
        return false;
      }
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          if ("delete_ok" == xhr.responseText) {
            _(status).style.display = "none";
            _("replytext_" + id).style.display = "none";
            _("replyBtn_" + id).style.display = "none";
          } else {
            alert(xhr.responseText);
          }
        }
      };
      xhr.send("action=delete_status&statusid=" + id);
    }
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
    var us = "less";
    function showReply(name, index) {
      if ("less" == us) {
        _("showreply_" + name).innerText = "Hide replies (" + index + ")";
        _("allrply_" + name).style.display = "block";
        us = "more";
      } else {
        if ("more" == us) {
          _("showreply_" + name).innerText = "Show replies (" + index + ")";
          _("allrply_" + name).style.display = "none";
          us = "less";
        }
      }
    }
    function openEmojiBox() {
      var cancel = _("emojiBox_group");
      if ("block" == cancel.style.display) {
        cancel.style.display = "none";
      } else {
        cancel.style.display = "block";
      }
    }
    function insertEmoji(type, value) {
      var node = document.getElementById(type);
      if (node) {
        var newTop = node.scrollTop;
        var pos = 0;
        var undefined = node.selectionStart || "0" == node.selectionStart ? "ff" : !!document.selection && "ie";
        if ("ie" == undefined) {
          node.focus();
          var oSel = document.selection.createRange();
          oSel.moveStart("character", -node.value.length);
          pos = oSel.text.length;
        } else {
          if ("ff" == undefined) {
            pos = node.selectionStart;
          }
        }
        var left = node.value.substring(0, pos);
        var right = node.value.substring(pos, node.value.length);
        if (node.value = left + value + right, pos = pos + value.length, "ie" == undefined) {
          node.focus();
          var range = document.selection.createRange();
          range.moveStart("character", -node.value.length);
          range.moveStart("character", pos);
          range.moveEnd("character", 0);
          range.select();
        } else {
          if ("ff" == undefined) {
            node.selectionStart = pos;
            node.selectionEnd = pos;
            node.focus();
          }
        }
        node.scrollTop = newTop;
      }
    }
    var hasImage = "";
    function showBtnDiv() {
      _("btns_SP").style.display = "block";
    }
    function showBtnDiv_reply(name) {
      _("btns_SP_reply_" + name).style.display = "block";
    }
    function doUpload(data) {
      var opts = _(data).files[0];
      if ("" == opts.name) {
        return false;
      }
      if ("image/jpeg" != opts.type && "image/png" != opts.type && "image/gif" != opts.type && "image/jpg" != opts.type) {
        return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
        document.body.style.overflow = "hidden", false;
      }
      _("triggerBtn_SP").style.display = "none";
      _("pbc").style.display = "block";
      var fd = new FormData;
      fd.append("stPic", opts);
      var request = new XMLHttpRequest;
      request.upload.addEventListener("progress", progressHandler, false);
      request.addEventListener("load", completeHandler, false);
      request.addEventListener("error", errorHandler, false);
      request.addEventListener("abort", abortHandler, false);
      request.open("POST", "/php_parsers/photo_system.php");
      request.send(fd);
    }
    function progressHandler(event) {
      var inDays = event.loaded / event.total * 100;
      var percent_progress = Math.round(inDays);
      _("progressBar").style.width = percent_progress + "%";
      _("pbt").innerHTML = percent_progress + "%";
    }
    function completeHandler(event) {
      var formattedDirections = event.target.responseText.split("|");
      _("progressBar").style.width = "0%";
      _("pbc").style.display = "none";
      if ("upload_complete" == formattedDirections[0]) {
        hasImage = formattedDirections[1];
        _("uploadDisplay_SP").innerHTML = '<img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage" />';
      } else {
        _("uploadDisplay_SP").innerHTML = formattedDirections[0];
        _("triggerBtn_SP").style.display = "block";
      }
    }
    function errorHandler(callback) {
      _("uploadDisplay_SP").innerHTML = "Upload Failed";
      _("triggerBtn_SP").style.display = "block";
    }
    function abortHandler(canCreateDiscussions) {
      _("uploadDisplay_SP").innerHTML = "Upload Aborted";
      _("triggerBtn_SP").style.display = "block";
    }
    function doUpload_reply(body, sharpCos) {
      var opts = _(body).files[0];
      if ("" == opts.name) {
        return false;
      }
      if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type && "image/jpg" != opts.type) {
        return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
        document.body.style.overflow = "hidden", false;
      }
      _("triggerBtn_SP_reply").style.display = "none";
      var fd = new FormData;
      fd.append("stPic_reply", opts);
      var xhr = new XMLHttpRequest;
      xhr.upload.addEventListener("progress", progressHandler_reply, false);
      xhr.addEventListener("load", completeHandler_reply, false);
      xhr.addEventListener("error", errorHandler_reply, false);
      xhr.addEventListener("abort", abortHandler_reply, false);
      xhr.open("POST", "/php_parsers/photo_system.php");
      xhr.send(fd);
    }
    function progressHandler_reply(event) {
      var inDays = event.loaded / event.total * 100;
      var t = "<p>" + Math.round(inDays) + "% uploaded please wait ...</p>";
      _("overlay").style.display = "block";
      _("overlay").style.opacity = .5;
      _("dialogbox").style.display = "block";
      _("dialogbox").innerHTML = "<b>Your uploading photo status</b><p>" + t + "</p>";
      document.body.style.overflow = "hidden";
    }
    function completeHandler_reply(event) {
      var formattedDirections = event.target.responseText.split("|");
      if ("upload_complete_reply" == formattedDirections[0]) {
        hasImage = formattedDirections[1];
        _("overlay").style.display = "block";
        _("overlay").style.opacity = .5;
        _("dialogbox").style.display = "block";
        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = "hidden";
      } else {
        _("uploadDisplay_SP_reply_" + e).innerHTML = formattedDirections[0];
        _("triggerBtn_SP_reply").style.display = "block";
      }
    }
    function errorHandler_reply(canCreateDiscussions) {
      _("uploadDisplay_SP_reply_").innerHTML = "Upload Failed";
      _("triggerBtn_SP_reply").style.display = "block";
    }
    function abortHandler_reply(canCreateDiscussions) {
      _("uploadDisplay_SP_reply").innerHTML = "Upload Aborted";
      _("triggerBtn_SP_reply").style.display = "block";
    }
    function triggerUpload(event, file) {
      event.preventDefault();
      _(file).click();
    }
    function triggerUpload_reply(event, numberofclassifiers) {
      event.preventDefault();
      _(numberofclassifiers).click();
    }
    window.onbeforeunload = function() {
      if ("" != hasImage) {
        return "You have not posted your image";
      }
    };
    var stat = "less";
    function opentext(name) {
      if ("less" == stat) {
        _("lessmore_" + name).style.display = "block";
        _("toggle_" + name).innerText = "See Less";
        _("hide_" + name).style.display = "none";
        stat = "more";
      } else {
        if ("more" == stat) {
          _("lessmore_" + name).style.display = "none";
          _("toggle_" + name).innerText = "See More";
          _("hide_" + name).style.display = "block";
          stat = "less";
        }
      }
    }
    var statreply = "less";
    function opentext_reply(name) {
      if ("less" == statreply) {
        _("lessmore_reply_" + name).style.display = "block";
        _("toggle_reply_" + name).innerText = "See Less";
        _("hide_reply_" + name).style.display = "none";
        statreply = "more";
      } else {
        if ("more" == statreply) {
          _("lessmore_reply_" + name).style.display = "none";
          _("toggle_reply_" + name).innerText = "See More";
          _("hide_reply_" + name).style.display = "block";
          statreply = "less";
        }
      }
    }
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
    function closeDialog() {
      _("dialogbox").style.display = "none";
      _("overlay").style.display = "none";
      _("overlay").style.opacity = 0;
      document.body.style.overflow = "auto";
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
    var stat_gr = "less";
    function opentext_gr(name) {
      if ("less" == stat_gr) {
        _("lessmore_gr_" + name).style.display = "block";
        _("toggle_gr_" + name).innerText = "See Less";
        _("hide_" + name).style.display = "none";
        stat_gr = "more";
      } else {
        if ("more" == stat_gr) {
          _("lessmore_gr_" + name).style.display = "none";
          _("toggle_gr_" + name).innerText = "See More";
          _("hide_" + name).style.display = "block";
          stat_gr = "less";
        }
      }
    }
    function quitGroup(id) {
      if (1 != confirm("Are you sure you want to quit from this group?")) {
        return false;
      }
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr) && "was_removed" == xhr.responseText) {
          window.Location.replace("user.php?u=<?php echo $log_username; ?>");
        }
      };
      xhr.send("action=quit_group&g=" + id);
    }
    function saveDesGr() {
      var vulnData = _("desgivegr").value;
      var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
      _("grdes_holder").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          var e = xhr.responseText.split("|");
          var linkedsceneitem = (e[1]).toString();

          linkedsceneitem = linkedsceneitem.replace(/\\n/g, "<br>");
          if ("des_save_success" == e[0]) {
            _("grdes_holder").innerHTML = '<p style="font-size: 14px;">' + linkedsceneitem + "</p>";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured while saving the group description. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
            _("grdes_holder").innerHTML = "Try again later";
          }
        }
      };
      xhr.send("text=" + vulnData + "&gr=<?php echo $g; ?>");
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
