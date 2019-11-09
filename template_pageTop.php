<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'sec_session_start.php';
  require_once 'headers.php';
  require_once 'safe_encrypt.php';
  
  $friend_count_online = "";
  $myfeed = "";
  $my_title = "";
  $view_all_link = "";
  $one = "1";
  $a = "a";
  $c = "c";
  $feedlink = "";
  $cookieacc = "";
  sec_session_start();
      $idofman = hash('gost','<>#&@{}[]chashSECZRE99881');
      if(!isset($_COOKIE["cookieset_"])){
        $_COOKIE["cookieset_"] = "no_$idofman";
      }
        if($_COOKIE["cookieset_"] == "no_$idofman"){
          $cookieacc = '<div id="logacccooks">We use cookies to ensure users receive a consistent user experience while we conduct A/B testing on certain aspects on Pearscom in order to improve our product offerings. We also use cookies to improve the performance and reliability. In addition, we use cookies to help us discover usability issues in Pearscom so we can make performance improvements. By clicking on the bottom below you agree with every aspects of this statement.<br><button id="ugbtn" onclick="agreeCookie(\''.$idofman.'\')">I agree</button></div>';
        }
  $rawfff = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $rawfff = urldecode($rawfff);
  $warfff = $rawfff;
  $usstr = "";
  if($warfff == "https://www.pearscom.com/user/".$u."/"){
    $usstr = $u;
  }
  // Check for new feed
  $all_friends = array();
  $sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss",$log_username,$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    if ($row["user1"] != $log_username){array_push($all_friends, $row["user1"]);}
    if ($row["user2"] != $log_username){array_push($all_friends, $row["user2"]);}
  }
  $stmt->close();
  $curar = join("','",$all_friends);
    $sql = "SELECT following FROM follow WHERE follower = ? AND following NOT IN('$curar')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
    	if ($row["following"] != $u){array_push($all_friends, $row["following"]);}
    }
    $stmt->close();
  $friendsCSV = join("','", $all_friends);
  $allf = join("','",$all_friends);
  $bdfusres = "";
    $datef = date("Y-m-d");
    $sql = "SELECT * FROM users WHERE DATE_FORMAT(bday, '%m-%d') = DATE_FORMAT(?, '%m-%d') AND username IN ('$allf')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$datef);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $uname = $row["username"];
        $bdfusres .= "<div style='padding: 10px;'><p style='font-size: 14px; text-align: center;'>Today is ".$uname."&#39;s birthday! Wish him/her the best! <img src='/images/bdcake.png' width='12' height='12'></p><input type='text' id='hbtuta'><button class='bdsendtof main_btn_fill fixRed' style='float: right;' onclick='sendBdTo(\"".$uname."\")'>Send</button><div class='clear'></div><div id='bdstattos' style='text-align: center;'></div></div><hr class='dim'>";
    }
    $stmt->close();

  $sql = "SELECT feedcheck FROM users WHERE username = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
  $stmt->execute();
  $stmt->bind_result($feedcheck);
  $stmt->fetch();
  $stmt->close();  

  $sql = "SELECT COUNT(s.id) FROM status AS s LEFT JOIN users AS u ON u.username = s.author WHERE ? < s.postdate AND (s.type = ? OR s.type = ?) AND s.author IN ('$friendsCSV')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss",$feedcheck,$a,$c);
  $stmt->execute();
  $stmt->bind_result($feed_count);
  $stmt->fetch();
  $stmt->close();

  $trif = "";
  if("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" == "https://www.pearscom.com/"){
    
  }

  if($feed_count == 0){
    $feedlink = '<a href="/index"><img src="/images/feed.png" class="eightteen">'.$trif.'</a>';
  }else if($feed_count > 0 && $feed_count < 100){
    $feedlink = '<a href="/index"><img src="/images/feed.png" class="eightteen">'.$trif.'</a><sup class="supStyle">'.$feed_count.'</sup>';
  }else if($feed_count > 99){
    $feed_count = "99+";
    $feedlink = '<a href="/index"><img src="/images/feed.png" class="eightteen">'.$trif.'</a><sup class="supStyle">'.$feed_count.'</sup>';
  }

  // Check for new pm's
  if($rawfff == "https://www.pearscom.com/private_messages/".$log_username.""){
       
  }
  $pm_n = '';
  if($user_ok == true){
    $x = 'x';
    $one = '1';
    $zero = '0';
    $sql = "SELECT id FROM pm WHERE (receiver=? AND parent=? AND rdelete=? AND rread=?)
              OR (sender=? AND sdelete=? AND parent=? AND hasreplies=? AND sread=?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss",$log_username,$x,$zero,$zero,$log_username,$zero,$x,$one,$zero);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows > 0){
      $sql = "SELECT COUNT(id) FROM pm WHERE (receiver=? AND parent=? AND rdelete=? AND rread=?)
              OR (sender=? AND sdelete=? AND parent=? AND hasreplies=? AND sread=?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssssssss",$log_username,$x,$zero,$zero,$log_username,$zero,$x,$one,$zero);
      $stmt->execute();
      $stmt->bind_result($pmcount);
      $stmt->fetch();
      $stmt->close();
      if($pmcount > 99){
        $pmcount = "99+";
      }
      $pm_n = '<a href="/private_messages/'.$log_username.'" title="You have new messages"><img src="/images/msg_white.png" width="20" height="20" alt="Private Messages" title="This message is for logged in members" id="dpm2"><sup class="supStyle">'.$pmcount.'</sup></a>';
    }else{
      $pm_n = '<a href="/private_messages/'.$log_username.'" title="You do not have new messages"><img src="/images/msg_white.png" width="20" height="20" alt="Private Messages" id="dpm3"></a>';
    }
  }

  if("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" == "https://www.pearscom.com/notifications"){
    
  }

  $envelope = '&nbsp;&nbsp;<img src="/images/nnot.png" class="nineteen">';
  $loginLink = '<a href="/login" id="down" class="bcolor">Log In <img src="/images/liimg.png" width="16" height="16" class="palsbdg"></a><a href="/signup" class="bcolor">Sign Up <img src="/images/suimg.png" width="16" height="16" class="palsbdg"></a>';
  // ADDED THIS FOR FRIEND REQUEST COUNT AND DISPLAY
  $zero = '0';
  $sql = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$zero);
  $stmt->execute();
  $stmt->bind_result($requests_count);
  $stmt->fetch();
  $stmt->close();
  if($user_ok == true) {
    $sql = "SELECT notescheck FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($notescheck);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT id FROM notifications WHERE username=? AND date_time > ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$notescheck);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
      if ($numrows == 0) {
          if($requests_count == 0){
              $envelope = '<a href="/notifications" title="Your notifications and friend requests"><img src="/images/nnot.png" class="nineteen"></a>';
          }else{
              $envelope = '<a href="/notifications"><img src="/images/nnot.png" class="nineteen"><sup class="supStyle">'.$requests_count.'</sup></a>';
          }
      } else {
        $sql = "SELECT COUNT(id) FROM notifications WHERE username=? AND date_time > ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$notescheck);
        $stmt->execute();
        $stmt->bind_result($ncount);
        $stmt->fetch();
        $stmt->close();
        $lncount = $ncount;
        $ncount += $requests_count;
        $lncount += $requests_count;
        if($ncount > 99){
          $ncount = "99+";
        }
        $envelope = '<a href="/notifications" title="You have '.$lncount.' new notification(s)"><img src="/images/nnot.png" class="nineteen"><sup class="supStyle">'.$ncount.'</sup></a>';
      }
  }

  if($user_ok == true){
    $myfeed = "<a href='/feed' id='myfeed'>Friends Activity</a>";
  }
  $one = '1';
  $user_template_image = "";
  $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
      $avatar = $row["avatar"];
      $pcurl = "/user/".$log_username."/".$avatar;
      $pcurlk = "/images/avdef.png";
      if($avatar == NULL){
        $user_template_image = '<div data-src=\''.$pcurlk.'\' onclick="toggleCP()" id="user_template_img" class="lazy-bg"></div>';
      }else{
        $user_template_image = '<div data-src=\''.$pcurl.'\' id="user_template_img" onclick="toggleCP()" class="lazy-bg"></div>';
      }
  }
  $stmt->close();
  $supportLink = 'Help';
  $settingsLink = 'Profile Settings';
  $imageLink = 'My Photos';
?>
<?php
  // Check for new notifications
  $not_icon = '<a href="#">
                <img src="/images/notf.png" alt="notifications" border="0" width="18" height="18" title="Notifications" onclick="return false" onmousedown="showNot()" id="notf">
              </a>'; // White
  if($user_ok == true){
    $sql = "SELECT notescheck FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($notescheck);
    $stmt->fetch();
    $stmt->close();
    $sql = "SELECT id FROM notifications WHERE username=? AND date_time > ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$notescheck);
    $stmt->execute();
    $numrows = $stmt->num_rows;
    if ($numrows == 0) {
      $not_icon = '<a href="#" id="n_right">
                    <img src="/images/notf.png" alt="notifications" border="0" width="18" height="18" title="Notifications" onclick="return false" onmousedown="showNot()" id="notf">
                  </a>';
    } else {
      $not_icon = '<a href="#" id="n_right">
                    <img src="/images/flashing_not.gif" alt="notifications" border="0" width="18" height="18" title="Notifications" onclick="return false" onmousedown="showNot()" id="notf">
                  </a>';
    }
    $stmt->close();
  }
?>
<?php
  // Set post time for dropdown
  $written_by = "";
  $sql = "SELECT * FROM articles WHERE written_by=? ORDER BY RAND() LIMIT 4";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
  $stmt->execute();
  $numrows = $stmt->num_rows;
  if($numrows > 0){
    while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
      $written_by = $row["written_by"];
    }
  }
  $stmt->close();
  $yes = 'yes';
  // Get online friends
  $friends_array = array();
  $sql = "SELECT user1 FROM friends WHERE user2=? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) { 
    array_push($friends_array, $row["user1"]); 
  }

  $sql = "SELECT user2 FROM friends WHERE user1=? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) { 
    array_push($friends_array, $row["user2"]); 
  }

  for($i=0; $i<count($friends_array); $i++){
    $frienda = $friends_array[$i];
    $sql = "SELECT COUNT(id) FROM users WHERE username=? AND online=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$frienda,$yes);
    $stmt->execute();
    $stmt->bind_result($friend_count_online);
    $stmt->fetch();
    $stmt->close();
  }
  if(empty($friends_array)){
    $friend_count_online = "0";
  }

  // User's recent activities
  $echo_recent_photos = "";
  $sql = "SELECT * FROM (
          SELECT * FROM photos WHERE user = ? ORDER BY id DESC LIMIT 9
      ) sub
      ORDER BY id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $pgal = $row["gallery"];
    $fname = $row["filename"];
    $des = $row["description"];
    $udate_ = $row["uploaddate"];
    $udate = strftime("%b %d, %Y", strtotime($udate_));
    $agoform = time_elapsed_string($udate_);
    if(strlen($des) > 20){
      $des = mb_substr($des, 0, 20, "utf-8");
      $des .= ' ...';
    }
    if($des == "" || $des == NULL){
        $des = "No description";
    }
    $urlm = '/user/'.$log_username.'/'.$fname.'';
    $echo_recent_photos .= '<div id="recentdivs"><div data-src=\''.$urlm.'\' class="recbgs lazy-bg" style="background-size: cover; background-repeat: no-repeat; background-position: center;" onclick="location.href=\'/photo_zoom/'.$log_username.'/'.$fname.'\'"></div></div>';
  }

  if($result->num_rows == 0){
    $echo_recent_photos = "<div style='padding: 10px;'><p style='font-size: 14px; color: #999;' class='txtc'>You have not uploaded any photos yet! Start <a href='/photos/".$log_username."'>uploading</a> now!</p></div>";
  }
  $stmt->close();

  $echo_recent_videos = "";
  $sql = "SELECT * FROM (
          SELECT * FROM videos WHERE user = ? ORDER BY id DESC LIMIT 5
      ) sub
      ORDER BY id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
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
    if(strlen($vname) > 20){
      $vname = mb_substr($vname, 0, 20, "utf-8");
      $vname .= ' ...';
    }
    if(strlen($vdes) > 20){
      $vdes = mb_substr($vdes, 0, 20, "utf-8");
      $vdes .= ' ...';
    }
    if($vname == ""){
      $vname = "Untitled";
    }
    if($vdes == ""){
      $vdes = "No description";
    }
    if($vposter == NULL){
      $urlk = '/images/defaultimage.png';
    }else{
      $urlk = '/user/'.$log_username.'/videos/'.$vposter.'';
    }
    
    $hvidid = base64url_encode($vidid,$hshkey);
    
    $echo_recent_videos .= '<div id="recentdivs"><div class="recbgs lazy-bg" data-src=\''.$urlk.'\' style="background-size: cover; background-repeat: no-repeat; background-position: center; margin-right: 3px;" onclick="location.href=\'/video_zoom/'.$hvidid.'\'"></div></div>';
  }

  if($result->num_rows == 0){
    $echo_recent_videos = "<div style='padding: 10px;'><p style='font-size: 14px; color: #999;' class='txtc'>You have not uploaded any videos or audio files yet! Start <a href='/videos/".$log_username."'>uploading</a> now!</p></div>";
  }
  $stmt->close();
?>
  <head>
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/fontawesome-all.min.js" async></script>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/jjs.js"></script>
    <script src="/js/mbc.js"></script>
    <script src="/js/lload.js"></script>
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
    function sendBdTo(e) {
        var o = _("hbtuta").value,
            t = "bd_wish";
        if ("" == o) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>In order to successfully post the birthday wishes to ' + e + ' you have to type in something first.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = ajaxObj("POST", "/php_parsers/status_system.php");
        s.onreadystatechange = function () {
            1 == ajaxReturn(s) && ("bdsent_ok" == s.responseText ? (_("bdstattos").innerHTML = "<p style='font-size: 12px; color: #999; margin-bottom: 0;'>Birthday message sent!</p>", document.getElementsByClassName("bdsendtof").value = "") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your birthday post. Please try again later and check everything is proper.' + s.responseText + '</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
            _("hbtuta").value = ""
        }, s.send("action=" + t + "&data=" + o + "&bduser=" + e + "&type=" + t)
    }
    function agreeCookie(e) {
        var o = new ajaxObj("POST", "/php_parsers/cookp.php");
        o.onreadystatechange = function () {
            if (1 == ajaxReturn(o) && "agree" == o.responseText) var e = _("logacccooks"),
                t = setInterval(function () {
                    e.style.opacity || (e.style.opacity = 1), e.style.opacity < .1 ? clearInterval(t) : e.style.opacity -= .1
                }, 20)
        }, o.send("ctype=" + e)
    }
    function closeDialog() {
        return _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto", !1
    }

  function getNames(e) {
      if (e == "") return _("memSearchResults").style.display = "none", !1;
      var x = encodeURI(e),
          t = new XMLHttpRequest;
      t.open("POST", "/search_exec.php", !0), t.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), t.onreadystatechange = function () {
          if (4 == t.readyState && 200 == t.status) {
              var e = t.responseText;
              e != "" && (_("memSearchResults").style.display = "block", _("memSearchResults").innerHTML = e)
          }
      }, t.send("u=" + x)
  }
  function getResults() {
      var e = _("searchUsername").value;
      if (e == "") return _("memSearchResults").style.display = "none", !1;
      var x = encodeURI(e);
      window.location = "/search_members/" + x
  }
  function getMLNames() {
      var e = _("searchUsername_mobile").value;
      if (e == "") return _("memSearchResults_mobile").style.display = "none", !1;
      window.location = "/search_members/" + e
  }
  function getNames_mobile(e) {
      var x = new RegExp;
      if (x = /[^a-z0-9 .-_]/gi, e.search(x) >= 0 && (e = e.replace(x, ""), _("searchUsername").value = e), e == "") return _("memSearchResults").style.display = "none", !1;
      var t = encodeURI(e),
          r = new XMLHttpRequest;
      r.open("POST", "/search_exec.php", !0), r.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), r.onreadystatechange = function () {
          if (4 == r.readyState && 200 == r.status) {
              var e = r.responseText;
              e != "" && (_("memSearchResults_mobile").style.display = "block", _("memSearchResults_mobile").innerHTML = e)
          }
      }, r.send("u=" + t)
  }
  function friendReqHandler(e, x, t, r) {
      _(r).innerHTML = "<img src=\"/images/rolling.gif\" width=\"30\" height=\"30\">";
      var n = ajaxObj("POST", "/php_parsers/friend_system.php");
      n.onreadystatechange = function () {
          1 == ajaxReturn(n) && ("accept_ok" == n.responseText ? _(r).innerHTML = "<b>Request Accepted!</b><br />Your are now friends" : "reject_ok" == n.responseText ? _(r).innerHTML = "<b>Request Rejected</b><br />You chose to reject friendship with this user" : _(r).innerHTML = n.responseText)
      }, n.send("action=" + e + "&reqid=" + x + "&user1=" + t)
  }
    </script>
  </head>
  <body>
    <header>
    <div id="dialogbox"></div>
    <div id="overlay"></div>
    <!--<?php echo $cookieacc; ?>-->
    <div id="navbar">
      <div id="innerNav">
        <a href="/index">
          <img src="/images/newfav.png" alt="Pearscom" width="47" height="47" id="newfav">
        </a>
        <div id="icons_align" <?php if($log_username == ""){ ?> style="display: none;" <?php } ?>>
          <div>
              <span id="sback"><img src="/images/sback.png" width="20" height="20"></span>
              <a id="sico"><img src="/images/sico.png" width="20" height="20"></a>
              <div class="tdd" style="margin-top: 5px;">
                <?php if($log_username != "" && $user_ok == true){ ?><a id="menu1btn" onclick="toggleDD()"><img src="/images/ddi.png" height="24" width="24" style="margin-top: -3px;"></a><?php } ?>
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
              <?php if($log_username != "" && $user_ok == true){ ?><?php echo $envelope; ?><?php } ?>
              <?php if($log_username != "" && $user_ok == true){ ?><?php echo $pm_n; ?><?php } ?>
              <?php if($log_username != "" && $user_ok == true){ ?>
                <?php echo $user_template_image; ?>
              <?php } ?>
            </div>
          </div>
        <div id="search_align">
              <div id="memSearch">
                <div id="memSearchInput">
                    <input id="searchUsername" type="text" onkeyup="getNames(this.value)" placeholder="Search for friends" value="">
                  </div>
                <div id="memSearchResults"></div>
              </div>
              <div class="clear"></div>
          </div>
          <div onclick="getResults()" id="s_dont">
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
              <?php echo $bdfusres; ?>
              <?php $decc = count($friends_array); ?>
              <?php if($decc < 1){ ?><p style="color: #999; font-size: 14px; padding: 10px;" class="txtc">It seems that you have no friends yet. <a href="/friend_suggestions">Search</a> in friend suggestions and find yours now!</p><?php } ?>
              <?php
              for($i=0; $i<count($friends_array); $i++){
                $withone = $friends_array[$i];
                $sql = "SELECT COUNT(id) FROM users WHERE username=? AND online=? ORDER BY lastlogin DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss",$withone,$yes);
                $stmt->execute();
                $stmt->bind_result($friend_count_online);
                $stmt->fetch();
                $stmt->close();
                if($friend_count_online == 0){
                  $isonline = "<img src='/images/wgrey.png' width='15' height='15' id='wicon' title='Offline'>";
                }else{
                  $isonline = "<img src='/images/wgreen.png' width='15' height='15' id='wicon' title='Online'>";
                }
                $sql = "SELECT * FROM users WHERE username=? ORDER BY lastlogin DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s",$withone);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()){
                  $friendav = $row["avatar"];
                  $useronline = $row["online"];
                  $llin = $row["lastlogin"];
                  $jdate = $row["signup"];
                  $pcurl = "/user/".$withone."/".$friendav;
                  if($friendav != NULL){
                    $userpic = '<a href="/user/'.$withone.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 35px; height: 35px; float: left; border-radius: 50%;" class="lazy-bg"></div></a>';
                  }else{
                    $userpic = '<a href="/user/'.$withone.'/"><div data-src="/images/avdef.png" style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 35px; height: 35px; float: left; border-radius: 50%;" class="lazy-bg"></div></a>';
                  }
                  $lfor = "";
                  if($useronline == "yes"){
                    $lfor = "online";
                  }else{
                    $lfor = time_elapsed_string($llin);
                    $lfor .= ' ago';
                  }
                  $woor = $withone;
                  // Check for username length
                  if(strlen($withone) > 24){
                    $withone = mb_substr($withone, 0, 20, "utf-8");
                    $withone .= " ...";
                  }
                  echo "<div id='slideout'>".$userpic."<b class='witho'>".$withone."</b>".$isonline."<br /><b class='witho' style='color: #999; font-size: 12px !important;'>".$lfor."</b><div class='clear'></div></div>";
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
              <!--<p style="font-size: 12px; color: #999; text-align: center; padding: 0px;">Have a good day, <?php echo $log_username; ?></p>-->
              <p style="font-size: 14px; color: #999; text-align: center;">&copy; Pearscom <?php echo date("Y"); ?></p>
           <?php } ?> 
          </div>
          </div>
  </header>
  </body>
<script type="text/javascript">
   var mobilecheck = mobilecheck();
      var now = new Date,
          hrs = now.getHours(),
          loguname = "<?php echo $log_username; ?>";
      if ("" != loguname) {
          /*var msg = "";
          hrs > 4 && (msg = "<div>Mornin' Sunshine!</div><img src='/images/emor.png' width='35' height='35'>"), hrs > 6 && (msg = "<div>Good morning!</div><img src='/images/mor.png' width='35' height='35'>"), hrs > 12 && (msg = "<div>Good afternoon!</div><img src='/images/aft.png' width='35' height='35'>"), hrs > 17 && (msg = "<div>Good evening!</div><img src='/images/emor.png' width='35' height='35'>"), (hrs > 22 || 0 == hrs) && (msg = "<div>Good night!</div><img src='/images/night.png' width='35' height='35'>"), _("greetings").innerHTML = msg, */("https://www.pearscom.com/#" && "https://www.pearscom.com/") != (window.location.href || document.URL) && (window.addEventListener("mouseup", function (e) {
              
          }), 0 == mobilecheck && window.addEventListener("mouseup", function (e) {
              var t = _("cp"),
                  o = _("user_template_img");
              e.target != t && e.target.parentNode != t && e.target != o && e.target.parentNode != o && (t.style.width = "0", t.style.right = "-30px", document.body.style.overflowY = "auto")
          }));
          var w = window,
              d = document,
              e = d.documentElement,
              g = d.getElementsByTagName("body")[0],
              x = w.innerWidth || e.clientWidth || g.clientWidth,
              y = w.innerHeight || e.clientHeight || g.clientHeight,
              bool = !0;
          "https://www.pearscom.com/" == (window.location.href || document.URL) && x >= 808 && (bool = !1)
      }

      function toggleCP() {
          if (1 == bool) {
              cp.offsetWidth;
              if (300 == cp.offsetWidth || "100%" == cp.style.width){
                  cp.style.width = "0", cp.style.right = "-10px", document.body.style.overflowY = "auto";
              } else {
                  cp.style.width = 0 == mobilecheck ? "300px" : "100%";
                  cp.offsetWidth;
                  cp.style.right = "0", document.body.style.overflowY = "hidden"
              }
              if(apiPres){
                  cp.style.top = '103px';
              }
          }
      }
      function toggleMenu(e) {
          "block" == (e = _(e)).style.display ? (e.style.display = "none", 1 == mobilecheck ? document.body.style.overflow = "hidden" : "") : (e.style.display = "block", 1 == mobilecheck ? document.body.style.overflow = "auto" : "")
      }

      function toggleDD(){
        if(document.getElementsByClassName("tddc")[0].style.display == "block"){
          document.getElementsByClassName("tddc")[0].style.display = "none";
        }else{
          document.getElementsByClassName("tddc")[0].style.display = "block";
        }
      }

      _("sico").addEventListener("click", function showSearch(e){
        document.getElementsByClassName("nineteen")[0].style.display = "none";
        _("dpm3").style.display = "none";
        _("user_template_img").style.display = "none";
        _("search_align").style.display = "block";
        if(window.innerWidth >= 328){
          _("search_align").style.width = "60%";
        }else{
          _("search_align").style.width = "50%";
        }
        _("s_dont").style.display = "block";
        _("icons_align").style.width = "40px";
        _("sico").style.display = "none";
        _("sback").style.display = "block";
        let x = document.getElementsByClassName("supStyle");
        for(let c of Array.from(x)){
            c.style.display = "none";
        }
      });
      
      let deferredPrompt;
      let apiPres = false;
      window.addEventListener('beforeinstallprompt', function(e){
          if(localStorage.getItem('expire') <= new Date().getTime()){
            deferredPrompt = e;
            apiPres = true;
            let refNode = _('cp');
            let newNode = document.createElement('div');
            let parentNode = document.getElementsByTagName('header')[0];
            let cp = _('cp');
            newNode.id = 'installProg';
            newNode.innerHTML = '<button class="main_btn main_btn_fill" id="installBtn" onclick="installAPI()">Install Pearscom</button><div><img src="/images/cins.png" onclick="closeAPI()"></div>';
            parentNode.insertBefore(newNode, refNode);
            if(_('cp') != null){
                if(parseInt(_('cp').offsetWidth, 10) > 0){
                    _('cp').style.top = '103px';
                }
            }
            if(window.location.pathname == '/'){
                if(_('newsfeed') != null) _('newsfeed').style.marginTop = '50px';
                if(_('pearHolder') != null) _('pearHolder').style.marginTop = '100px';
                if(_('startContent') != null) _('startContent').style.marginTop = '120px';
                if(_('changingWords') != null) _('changingWords').style.height = 'calc(50% - 20px)';
            }else if(window.location.pathname == '/login' || window.location.pathname == '/signup' || window.location.pathname == '/forgot_password'){
                _('pageMiddle_2').style.marginTop = '120px';
            }else{
                _('pageMiddle_2').style.marginTop = '100px';
            }
          }
      });
      
      function closeAPI(e){
          apiPres = false;
          _('installProg').style.display = 'none';
          let time = new Date().getTime() + 86400 * 1000;
          localStorage.setItem('expire', time);
          if(window.location.pathname == '/'){
            _('newsfeed').style.marginTop = '0px';
          }else{
            _('pageMiddle_2').style.marginTop = '60px';
          }
          _('cp').style.top = '51px';
          console.log(_('cp'));
      }
      
      function installAPI(e){
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice
            .then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                newNode.innerHTML = '<p>Congratulations! Pearscom has been successfully installed.</p>';
                closeAPI();
            }
          deferredPrompt = null;
        });
      }

      _("sback").addEventListener("click", function back(e){
        document.getElementsByClassName("nineteen")[0].style.display = "inline-block";
        _("dpm3").style.display = "block";
        _("user_template_img").style.display = "block";
        _("search_align").style.display = "none";
        _("s_dont").style.display = "none";
        _("icons_align").style.width = "80%";
        _("sico").style.display = "block";
        _("sback").style.display = "none";
        let x = document.getElementsByClassName("supStyle");
        for(let c of Array.from(x)){
            c.style.display = "inline-block";
        }
      });
</script>