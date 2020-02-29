<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/video_common.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/status_common.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'durc.php';
  require_once 'phpmobc.php';
  require_once 'headers.php';

  // Make sure the $_GET "v" is set, and sanitize it
  $one = "1";
  $id = checkU($_GET['id'], $conn);
  
  if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
    // Check if user exists in db
    $tUname = $_SESSION['username'];
    userExists($conn, $tUname);
  }else{
    header('locations: /needlogged');
  }
  
  $u = "";
  $rcs = "";
  $description = "";
  $upload = "";
  $agofrom = "";

  $_SESSION["id"] = $id;
  $ec = $id;
  $id = base64url_decode($id, $hshkey);
  $id = preg_replace('/\D/', '', $id);

  // Count the likes on this video
  $countvl = cntLikesNew($conn, $id, 'video_likes', 'video');
  
  $a = "a";
  $b = "b";
  $c = "c";
  
  // Get today's most liked videos
  $ismob = mobc();
  if($ismob == false){
    $max = 6;
  }else{
    $max = 4;
  }

  // Query for selecting the best videos over an x time period
  $sql = "SELECT video, COUNT(*) AS u 
          FROM video_likes
          WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL - 1 DAY)
          GROUP BY video";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $stmt->store_result();
  $numrows = $stmt->num_rows();
  $stmt->close();
  $days = 0;
  $bHolder = "";
  if($numrows > 0){
    $days = 1;
    $bHolder = "Most liked videos of the day";
  }else{
    $days = 7;
    $bHolder = "Most liked videos of the week";
  }

  $bestvids = "";
  $sql = "SELECT video, COUNT(*) AS u 
          FROM video_likes
          WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL - $days DAY)
          GROUP BY video
          ORDER BY u DESC
          LIMIT $max";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $res = $stmt->get_result();
  if($res->num_rows > 0){
    while($row = $res->fetch_assoc()){
      $bestvids .= genLVidBox($conn, $row);
    }
    $stmt->close();
  }else{
    $bHolder = "Trending videos of all time";
    $sql = "SELECT video, COUNT(*) AS u 
            FROM video_likes
            GROUP BY video
            ORDER BY u DESC
            LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
      while($row = $res->fetch_assoc()){
        $bestvids .= genLVidBox($conn, $row);
      }
    }else{
      $bestvids = '
        <p style="color: #999;" class="txtc">
          It seems that there are no videos fitting the criteria
        </p>
      ';
    }
  }

  // Get that certain video
  $big_vid = "";
  $poster = "";
  $video_name = "";
  $sql = "SELECT * FROM videos WHERE id = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $rcs = $row["id"];
      $id_number = $rcs;
      $user = $row["user"];
      $video_file = $row["video_file"];
      $video_name = $row["video_name"];
      $description = $row["video_description"];
      $poster = $row["video_poster"];
      $upload_ = $row["video_upload"];
      $upload = strftime("%b %d, %Y", strtotime($upload_));
      $u = $row["user"];
      $pDur = $row["dur"];
      $dur = convDur($row["dur"]);
      $poster = thumbnailImg($u, $poster);

      if($description == ""){
        $description = "Untitled";
      }

      if($video_name == ""){
        $video_name = "Untitled";
      }

      // Get number of likes
      $like_count = cntLikesNew($conn, $id_number, 'video_likes', 'video');
      $isLike = userLiked($user_ok, $conn, $id_number, $log_username, true, 'video_likes',
        'video');
      $hshid = base64url_encode($id_number, $hshkey);

      // Generate like btn and text
      if($isLike){
        $lType = 'unlike';
        $lImg = 'fillthumb';
        $lText = 'Dislike';
      }else{
        $lType = 'like';
        $lImg = 'nf';
        $lText = 'Like';
      }

      $likeButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleLike(\''.$lType.'\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\',
          false, \'/php_parsers/video_parser.php\', \'\')">
          <img src="/images/'.$lImg.'.png" width="18" height="18" class="like_unlike"
          style="vertical-align: middle;">
        </a>
      ';
      $likeText = '<span style="vertical-align: middle;">'.$lText.'</span>';

      $shareButton = '
        <img src="/images/black_share.png" width="18" height="18" onclick="return false;"
          onmousedown="shareVideo(\'' . $id_number . '\');" id="shareBlink"
          style="vertical-align: middle;">
      ';

      // Wrap description if longer than 200 chars
      $dold = $description;
      if(strlen($description) > 200){
        $description = substr($description, 0, 200);
        $description .= "...";
        $description .= '
          <a onclick="showDes(\''.$dold.'\', \''.$description.'\')">Show more</a>';
      }
      
      $dstr = "";
      if($_SESSION["username"] != ""){
        $dstr = '
          <span id="likeBtnv_'.$hshid.'" class="likeBtn">
            '.$likeButton.'
            <span style="vertical-align: middle;">'.$likeText.'</span>
          </span>
          <div class="shareDiv">
            ' . $shareButton . '
            <span style="vertical-align: middle;">Share</span>
          </div>';
      }

      $stmt->close();

      $avav = getUserAvatar($conn, $user);
      $agoform = time_elapsed_string($upload_);

      $big_vid = '
        <div id="big_holder" class="genWhiteHolder">
          <div class="vidHolderBig" id="videoContainer">
            <video width="100%" id="my_video_'.$ec.'" class="bigvidg" 
              id="my_video_'.$ec.'" poster="'.$poster.'" preload="metadata">
              <source src="/user/'.$u.'/videos/'. $video_file.'">

              <p style="font-size: 14px;" class="txtc">
                Unfortunately, an error has occured during the video loading.
                Please refresh the page or visit our <a href="/help">help</a> page.
                As for now, you can
                <a href="/user/'.$u.'/videos/'. $video_file.'" download>download</a>
                the video/auido file and play it on your computer or mobile device.
              </p>
            </video>
            <div class="lds-spinner" id="testl">
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
              <div></div>
            </div>
            <div class="vControls">
              <div class="tooltipVid" id="timeInd">
            </div>

            <div class="orangeBar" id="ob">
              <div class="orangeJuice" id="oj"></div>
              <div class="orangeJuicy"></div>
              <div class="orangeGrey"></div>
            </div>

            <div class="vButtons" id="pcControls">
              <button id="playPauseBtn" status="play">
                <img src="/images/playbtn.svg" id="tgl1">
                <span class="tooltipVidText" id="ppToggle">Play (p)</span>
              </button>

              <span id="volCont" style="display: flex;">
                <button id="muteBtn" status="sound">
                  <img src="/images/mutebtn.svg" id="tgl2">
                  <span class="tooltipVidText" id="muteToggle">Mute (m)</span>
                </button>

                <div class="volSlider" id="volSlider">
                  <input type="range" id="vChange" style="display: none;" min="0" max="100">
                </div>

                <div id="timeData" class="timeData">
                  <div id="curtime">00:00 /</div>&nbsp;
                  <div id="duration">'.$dur.'</div>
                </div>
              </span>

              <div class="vRight">
                <button id="optionsGears">
                  <img src="/images/gearsbtn.svg">
                  <span class="tooltipVidText" id="optionsToggle">Options (o)</span>
                </button>
                <button id="fullScreen">
                  <img src="/images/fullsrc.svg">
                  <span class="tooltipVidText" id="fsToggle">Fullscreen (f)</span>
                </button>
              </div>

              <div id="optionsMenu">
                <div>Speed</div>
                <div onclick="changeSpeed(0.25)">0.25</div>
                <div onclick="changeSpeed(0.5)">0.5</div>
                <div onclick="changeSpeed(0.75)">0.75</div>
                <div onclick="changeSpeed(1)">Normal</div>
                <div onclick="changeSpeed(1.25)">1.25</div>
                <div onclick="changeSpeed(1.5)">1.5</div>
                <div onclick="changeSpeed(2.0)">2</div>
              </div>
            </div>
          </div>
        </div>
        <div class="clear"></div>
        <div class="clear"></div>
        <div>
          <div>
            <p class="shtrp">'.$video_name.'</p>
            <div style="float: left;">
              <p class="greyP" id="ipanf_' . $hshid . '">'.$like_count.' likes</p>
            </div>
            <div style="float: right; margin-top: 10px;">
              '.$dstr.'
            </div>
          </div>
        </div>
        <div class="clear"></div>
        <hr class="dim">
        <a href="/user/'.$user.'/">
          <div style="background-image: url(\''.$avav.'\'); width: 40px; height: 40px;
            float: left; border-radius: 50%;" class="genBg"></div>
        </a>
        &nbsp;&nbsp;&nbsp;
        <b style="display: inline-block; margin-bottom: -10px; vertical-align: middle;">
          '.$user.'
        </b>
        <br>
        &nbsp;&nbsp;&nbsp;
        <b style="display: inline-block; font-size: 12px; color: #999; margin-bottom: -10px;
          vertical-align: middle;">Published on '.$upload.' ('.$agoform.' ago)
        </b>
        <div class="clear"></div>
        <p style="font-size: 14px; margin-bottom: 0px;" id="shDes">'.$description.'</p>
        <div class="clear"></div>
      </div>
      <hr class="dim shHr">';
    }
  }else{
    header('location: /videonotexist');
    exit();
  }
  $stmt->close();

  // Check if user is blocked
  $isBlock = isBlocked($conn, $log_username, $u);
  
  // Get related videos
  $all_friends = getUsersFriends($conn, $u, $log_username);
  $allfmy = join("','", $all_friends);
  $related_vids = "";
  $sql = "SELECT * FROM videos WHERE user IN ('$allfmy') LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $related_vids .= genLVidBox($conn, $row, false);
  }
  $stmt->close();
  
  if(empty($all_friends)){
    $sql = "SELECT * FROM videos LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $related_vids .= genLVidBox($conn, $row, false);
    }
    $stmt->close();
  }

  // Get users's videos
  $myvids = "";
  $sql = "SELECT * FROM videos WHERE user = ? LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $myvids .= genLVidBox($conn, $row, false);
  }
  $stmt->close();
  
  $ismyv = false;
  $isrel = false;
  
  if(!$myvids){
    $myvids = '
      <p style="color: #999;" class="txtc">
        It seems that you have not uploaded any videos so far
      </p>
    ';
    $ismyv = true;
  }
  
  if(!$related_vids){
    $related_vids = '
      <p style="color: #999;" class="txtc">
        It seems that there are no listable related videos
      </p>
    ';
    $isrel = true;
  }

  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
    $myvids = $related_vids = "
      <p style='color: #999;' class='txtc'>
        Please <a href='/login'>log in</a> in order to see videos
      </p>
    ";
    $isrel = true;
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $video_name; ?></title>
  <meta charset="utf-8">
  <meta lang="en">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Watch <?php echo $video_name; ?>">
  <meta name="keywords" content="pearscom video, <?php echo $video_name; ?>,
    <?php echo $video_name; ?> video, <?php echo $video_name; ?> pearscom video,
    <?php echo $u; ?> video pearscom, video pearscom">
  <meta name="author" content="Pearscom">
  <script src="/js/jjs.js"></script>
  <script src="/js/main.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/mbc.js"></script>
  <script src="/js/specific/p_dialog.js"></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/lload.js"></script>
  <script src="/js/specific/video.js" defer></script>
  <script src="/js/specific/like_status.js"></script>
  <script src="/js/specific/share_status.js"></script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <?php echo $big_vid; ?>
    <div class="genWhiteHolder vidRedPad" style="margin-top: 10px;">
      <b class="vBigText vidIncMar">My videos</b>
      <div id="myvids_holder" class="flexibleSol">
        <?php echo $myvids; ?>
      </div>
    </div>

    <div class="genWhiteHolder vidRedPad" style="margin-top: 10px;">
      <b class="vBigText vidIncMar">Related videos</b>
      <div id="relvid_holder_big" class="flexibleSol">
        <?php echo $related_vids; ?>
      </div>
    </div>

    <div class="genWhiteHolder vidRedPad" style="margin-top: 10px;">
      <b class="vBigText vidIncMar"><?php echo $bHolder; ?></b>
      <div id="relvid_holder_big" class="flexibleSol">
        <?php echo $bestvids; ?>
      </div>
    </div>

    <div class="clear"></div>

    <div class="newstatdiv">
      <?php if($isBlock != true){ ?>
        <?php require_once 'video_status.php'; ?>
      <?php }else{ ?>
      <p style="color: #006ad8;" class="txtc">
        Alert: this user blocked you, therefore you cannot post on his/her profile!
      </p>
      <?php } ?>
    </div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    let ec = "<?php echo $ec; ?>";
    let vDur = "<?php echo $pDur ?>";
    let video = _("my_video_" + ec);
    let ppbtn = _("playPauseBtn");
    let tgl1 = _("tgl1");
    let tgl2 = _("tgl2");
    let og = document.querySelector(".orangeJuice");
    let oj = document.querySelector(".orangeJuicy");
    let ob = document.querySelector(".orangeBar");
    let ogrey = document.querySelector(".orangeGrey");
    let mutebtn = _("muteBtn");
    let fs = _("fullScreen");
    let controls = document.querySelector(".vControls");
    let isDragging = false;
  </script>
</body>
</html>
