<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/top_common.php';
  require_once 'sec_session_start.php';
  require_once 'headers.php';
  require_once 'safe_encrypt.php';
  
  $my_title = "";
  $view_all_link = "";
  $one = "1";
  $a = "a";
  $c = "c";
  $x = 'x';
  $zero = '0';

  sec_session_start();

  // Check for new feed
  $all_friends = getUsersFriends($conn, $log_username, $log_username);
  $allf = join("','",$all_friends);

  getFollowings($conn, $allf, $u, $all_friends);

  $friendsCSV = join("','", $all_friends);

  // Get users who have a birthday today
  $bdfusres = "";
  $datef = date("Y-m-d");
  $sql = "SELECT * FROM users WHERE DATE_FORMAT(bday, '%m-%d') = DATE_FORMAT(?, '%m-%d')
    AND username IN ('$allf')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $datef);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $uname = $row["username"];
    $bdfusres .= "
      <div style='padding: 10px;'>
        <p style='font-size: 14px; text-align: center;'>
          Today is ".$uname."&#39;s birthday! Wish them the best!
          <img src='/images/bdcake.png' width='12' height='12'>
        </p>
        <input type='text' id='hbtuta'>
        <button class='bdsendtof main_btn_fill fixRed' style='float: right;'
          onclick='sendBdTo(\"".$uname."\")'>Send</button>
        <div class='clear'></div>
        <div id='bdstattos' style='text-align: center;'></div>
      </div>
      <hr class='dim'>
    ";
  }
  $stmt->close();

  // Check for new private messages
  $pm_n = '';
  if($user_ok == true){
    $sql = "SELECT id FROM pm WHERE (receiver=? AND parent=? AND rdelete=? AND rread=?)
            OR (sender=? AND sdelete=? AND parent=? AND hasreplies=? AND sread=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $log_username, $x, $zero, $zero, $log_username, $zero,
      $x, $one, $zero);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows > 0){
      $pmcount = $numrows;
      if($pmcount > 99){
        $pmcount = "99+";
      }
      $pm_n = '
        <a href="/private_messages/'.$log_username.'" title="You have new messages">
          <img src="/images/msg_white.png" width="20" height="20" alt="Private Messages"
            id="dpm2">
          <sup class="supStyle">'.$pmcount.'</sup>
        </a>
      ';
    }else{
      $pm_n = '
        <a href="/private_messages/'.$log_username.'" title="You do not have new messages">
          <img src="/images/msg_white.png" width="20" height="20" alt="Private Messages"
          id="dpm3">
        </a>
      ';
    }
  }

  // Display notifications; add the num of friend requests + num of notifs
  $envelope = '&nbsp;&nbsp;<img src="/images/nnot.png" class="nineteen">';
  $requests_count = reqCount($conn, $log_username);
  if($user_ok == true) {
    $notescheck = getNotescheck($conn, $log_username);

    $sql = "SELECT id FROM notifications WHERE username=? AND date_time > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $notescheck);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $totalNotifs = $numrows + $requests_count;
    if ($totalNotifs > 0) {
      if ($totalNotifs > 99) {
        $totalNotifs = '99+';
      }

      $envelope = '
        <a href="/notifications">
          <img src="/images/nnot.png" class="nineteen">
          <sup class="supStyle">'.$totalNotifs.'</sup>
        </a>
      '; 
    } else {
      $envelope = '
        <a href="/notifications" title="Your notifications and friend requests">
          <img src="/images/nnot.png" class="nineteen">
        </a>
      ';      
    }
  }

  // Display user profile image at the right top corner
  $user_template_image = "";
  $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $log_username, $one);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $avatar = $row["avatar"];
    $pcurl = "/user/".$log_username."/".$avatar;
    $pcurlk = "/images/avdef.png";
    if($avatar == NULL){
      $user_template_image = '
        <div data-src=\''.$pcurlk.'\' onclick="toggleCP()" id="user_template_img"
          class="lazy-bg"></div>';
    }else{
      $user_template_image = '
        <div data-src=\''.$pcurl.'\' id="user_template_img" onclick="toggleCP()"
          class="lazy-bg"></div>';
    }
  }
  $stmt->close();

  $supportLink = 'Help';
  $settingsLink = 'Profile Settings';
  $imageLink = 'My Photos';

  $yes = 'yes';

  // User's recent photos
  $sql = "SELECT * FROM
          (SELECT * FROM photos WHERE user = ? ORDER BY id DESC LIMIT 9) sub
          ORDER BY id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $pgal = $row["gallery"];
    $fname = $row["filename"];
    $des = $row["description"];
    $udate_ = $row["uploaddate"];
    $udate = strftime("%b %d, %Y", strtotime($udate_));
    $agoform = time_elapsed_string($udate_);

    $des = wrapText($des, 20);

    if($des == "" || $des == NULL){
        $des = "No description";
    }
    $urlm = '/user/'.$log_username.'/'.$fname;

    $echo_recent_photos .= '
      <div id="recentdivs">
        <div data-src=\''.$urlm.'\' class="recbgs lazy-bg" style="background-size: cover;
          background-repeat: no-repeat; background-position: center;"
          onclick="location.href=\'/photo_zoom/'.$log_username.'/'.$fname.'\'">
        </div>
      </div>
    ';
  }

  // Fallback message when no recent photos are found
  if($result->num_rows == 0){
    $echo_recent_photos = "
      <div style='padding: 10px;'>
        <p style='font-size: 14px; color: #999;' class='txtc'>
          You have not uploaded any photos yet! Start
          <a href='/photos/".$log_username."'>uploading</a> now!
        </p>
      </div>
    ";
  }
  $stmt->close();

  // Get recent videos by user
  $echo_recent_videos = "";
  $sql = "SELECT * FROM
          (SELECT * FROM videos WHERE user = ? ORDER BY id DESC LIMIT 5) sub
          ORDER BY id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $vidid = $row["id"];
    $vname = $row["video_name"];
    $vdes = $row["video_description"];
    $vposter = $row["video_poster"];
    $vupload_ = $row["video_upload"];
    $vupload = strftime("%b %d, %Y", strtotime($vupload_));
    $agoform = time_elapsed_string($vupload_);

    $vname = wrapText($vname, 20);
    $vdes = wrapText($vdes, 20);

    if($vname == ""){
      $vname = "Untitled";
    }

    if($vdes == ""){
      $vdes = "No description";
    }

    if($vposter == NULL){
      $urlk = '/images/defaultimage.png';
    }else{
      $urlk = '/user/'.$log_username.'/videos/'.$vposter;
    }
    
    $hvidid = base64url_encode($vidid,$hshkey);
    
    $echo_recent_videos .= '
      <div id="recentdivs">
        <div class="recbgs lazy-bg" data-src=\''.$urlk.'\' style="background-size: cover;
          background-repeat: no-repeat; background-position: center; margin-right: 3px;"
          onclick="location.href=\'/video_zoom/'.$hvidid.'\'">
        </div>
      </div>
    ';
  }

  // Fallback message when no videos are found
  if($result->num_rows == 0){
    $echo_recent_videos = "
      <div style='padding: 10px;'>
        <p style='font-size: 14px; color: #999;' class='txtc'>
          You have not uploaded any videos or audio files yet!
          Start <a href='/videos/".$log_username."'>uploading</a> now!
        </p>
      </div>
    ";
  }
  $stmt->close();
?>
<!DOCTYPE html>
<head>
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/main.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/jjs.js"></script>
  <script src="/js/mbc.js" defer></script>
  <script src="/js/lload.js"></script>
  <script src="/js/specific/p_dialog.js"></script>
  <script src="/js/specific/error_dialog.js"></script>
  <script src="/js/specific/longbtn.js"></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />

  <?php if($log_username != ""){ ?>
    <style type="text/css">
      @media only screen and (max-width: 500px){
        #s_dont, #search_align, .tdd{
          display: none;
        }
      }

      @media only screen and (max-width: 768px){
        #search_align{
          width: 39%;
        }
      }
    </style>
  <?php } ?>
  <script type="text/javascript">
    const UNAME = '<?php echo $log_username; ?>';
  </script>
  <script src="/js/specific/top.js" defer></script>
</head>
<body>
  <header>
    <div id="dialogbox"></div>
    <div id="overlay"></div>
    <div id="navbar">
      <div id="innerNav">
        <a href="/index">
          <img src="/images/newfav.png" alt="Pearscom" width="47" height="47" id="newfav">
        </a>
        <div id="icons_align"
          <?php if($log_username == ""){ ?>
            style="display: none;"
          <?php } ?>
        >
          <div>
            <span id="sback"><img src="/images/sback.png" width="20" height="20"></span>
            <a id="sico"><img src="/images/sico.png" width="20" height="20"></a>
            <div class="tdd" style="margin-top: 5px;">
            <?php if($log_username != "" && $user_ok == true){ ?>
              <a id="menu1btn" onclick="toggleDD()">
                <img src="/images/ddi.png" height="24" width="24" style="margin-top: -3px;">
              </a>
            <?php } ?>
            <div class="tddc" id="tddcCont">
              <a href="/user_articles/<?php echo $log_username; ?>">See My Articles </a>
              <a href="/all_articles/<?php echo $log_username; ?>">My All Articles </a>
              <a href="/friend_suggestions">Friend Suggestions </a>
              <a href="/groups">Groups </a>
              <a href="/view_all_groups/">See All Groups </a>
              <a href="/settings">Profile Settings </a>
              <a href="/invite">Invite Friends </a>
              <a href="/help">Help &amp; Support </a>
              <a href="/logout">Log Out </a>
            </div>
          </div>
          <?php
            if($log_username != "" && $user_ok == true){
              echo $envelope;
              echo $pm_n;
              echo $user_template_image;
            } ?>
        </div>
      </div>
      <div id="search_align">
        <div id="memSearch">
          <div id="memSearchInput">
            <input id="searchUsername" type="text" onkeyup="getNames(this.value)"
              placeholder="Search for friends" value="" autocomplete="off">
          </div>
          <div id="memSearchResults"></div>
        </div>
        <div class="clear"></div>
      </div>
      <div onclick="getLSearchArt('searchUsername', 'memSearchResults',
        '/search_members/' + (encodeURI(_('searchUsername').value)))" id="s_dont">
        <img src="/images/searchnav.png" width="18" height="18" alt="Search icon">
      </div>
    </div>
  </div>
  <?php if($log_username != "" && $user_ok == true){ ?>
    <div id="cp">
      <div class="innerView">
        <div class="relevantInfo">
          <div onclick="location.href = '/user/<?php echo $log_username; ?>/'">
            <span>
              <div data-src='/images/cpprof.png' class='lazy-bg noRound'></div>
              <span>My Profile</span>
            </span>
          </div>
          <div onclick="location.href = '/index'">
            <span>
              <div data-src='/images/nfeed.png' class='lazy-bg noRound'></div>
              <span>News Feed</span>
            </span>
          </div>
          <div onclick="location.href = '/view_friends/<?php echo $log_username; ?>/'">
            <span>
              <div data-src='/images/cpfriends.png' class='lazy-bg noRound'></div>
              <span>Friends</span>
            </span>
          </div>
        </div>  
        <?php
          echo $bdfusres;
          $decc = count($all_friends);
        ?>
        <?php if($decc < 1){ ?>
          <p style="color: #999; font-size: 14px; padding: 10px;" class="txtc">
            It seems that you have no friends yet. <a href="/friend_suggestions">Search</a>
            in friend suggestions and find yours now!
          </p>
        <?php } ?>
        <?php
          // Display user's friends
          for ($i = 0; $i < count($all_friends); $i++){
            $withone = $all_friends[$i];
            $sql = "SELECT COUNT(id) FROM users WHERE username=? AND online=? ORDER BY
              lastlogin DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $withone, $yes);
            $stmt->execute();
            $stmt->bind_result($friend_count_online);
            $stmt->fetch();
            $stmt->close();

            if($friend_count_online == 0){
              $isonline = "<img src='/images/wgrey.png' width='15' height='15' id='wicon'>";
            }else{
              $isonline = "<img src='/images/wgreen.png' width='15' height='15' id='wicon'>";
            }

            $sql = "SELECT * FROM users WHERE username=? ORDER BY lastlogin DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $withone);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()){
              $friendav = $row["avatar"];
              $useronline = $row["online"];
              $llin = $row["lastlogin"];
              $jdate = $row["signup"];
              $pcurl = "/user/".$withone."/".$friendav;

              if($friendav == NULL){
                $pcurl = '/images/avdef.png';
              }

              $userpic = '
                <a href="/user/'.$withone.'/">
                  <div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat;
                    background-size: cover; background-position: center; width: 35px;
                    height: 35px; float: left; border-radius: 50%;" class="lazy-bg">
                  </div>
                </a>
              ';

              if($useronline == "yes"){
                $lfor = "online";
              }else{
                $lfor = time_elapsed_string($llin);
                $lfor .= ' ago';
              }

              $woor = $withone;
              $withone = wrapText($withone, 24);

              echo "
                <div id='slideout'>
                  ".$userpic."
                  <b class='witho'>".$withone."</b>".$isonline."<br />
                  <b class='witho' style='color: #999; font-size: 12px !important;'>
                    ".$lfor."
                  </b>
                  <div class='clear'></div>
                </div>
              ";
            }
            $stmt->close();
          } ?>
          <hr class="dim">
          <div class="relevantInfo">
            <div onclick="location.href = '/photos/<?php echo $log_username; ?>'">
                <span>
                  <div data-src='/images/cpcamera.png' class='lazy-bg noRound'></div>
                  <span>Photos</span>
                </span>
            </div>
          </div>
          <div style="padding: 10px;">
            <?php echo $echo_recent_photos; ?>
          </div>

          <div class="clear"></div>
          <hr class="dim">  
          <div class="relevantInfo">
            <div onclick="location.href = '/videos/<?php echo $log_username; ?>'">
              <span>
                <div data-src='/images/cpvideos.png' class='lazy-bg noRound'></div>
                <span>Videos</span>
              </span>
            </div>
          </div>
              
          <div style="padding: 10px;"><?php echo $echo_recent_videos; ?></div>
          <div class="clear"></div>
          <hr class="dim">

          <div class="relevantInfo" id="redNav">
            <div onclick="location.href = '/user_articles/<?php echo $log_username; ?>'">
              <span>
                <div data-src='/images/cparts.png' class='lazy-bg noRound'></div>
                <span>See My Articles</span>
              </span>
            </div>

            <div onclick="location.href = '/friend_suggestions/'">
              <span>
                <div data-src='/images/cpsugg.png' class='lazy-bg noRound'></div>
                <span>Friend Suggestions</span>
              </span>
            </div>

            <div onclick="location.href = '/groups'">
              <span>
                <div data-src='/images/cpgroup.png' class='lazy-bg noRound'></div>
                <span>Groups</span>
              </span>
            </div>

            <div onclick="location.href = '/settings'">
              <span>
                <div data-src='/images/cpsettings.png' class='lazy-bg noRound'></div>
                <span>Profile Settings</span>
              </span>
            </div>

            <div onclick="location.href = '/invite/<?php echo $log_username; ?>'">
              <span>
                <div data-src='/images/cpinv.png' class='lazy-bg noRound'></div>
                <span>Invite Friends</span>
              </span>
            </div>

            <div onclick="location.href = '/help'">
              <span>
                <div data-src='/images/cphelp.png' class='lazy-bg noRound'></div>
                <span>Help &amp; Support</span>
              </span>
            </div>

            <div onclick="location.href = '/logout'">
              <span>
                <div data-src='/images/cplogout.png' class='lazy-bg noRound'></div>
                <span>Log Out</span>
              </span>
            </div>
          </div>
          <hr class="dim afterHr">
          <p style="font-size: 14px; color: #999; text-align: center;">
            &copy; Pearscom <?php echo date("Y"); ?>
          </p>
       <?php } ?> 
      </div>
    </div>
  </header>
</body>
