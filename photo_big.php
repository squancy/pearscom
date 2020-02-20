<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/photo_common.php';
  require_once 'php_includes/wrapText.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'phpmobc.php';
  require_once 'headers.php';

  // Initialize some vars
  $a = "a";
  $b = "b";
  $c = "c";
  $one = "1";
  $vdelete_btn = "";
  $gallery_u = "";
  $description_u = "";
  $uploaddate_u = "";
  $u = '';
  $uds = "";
  $hasSugg = false;

  // Get the required parameters from the URL; otherwise redirect user
  if(isset($_GET["p"]) && isset($_GET['u'])){
    $p = $_GET["p"];
    $u = $_GET['u'];
  }else{
    header('Location: /index');
  }

  $_SESSION["photo"] = $p;
  
  // If user is logged in check if they are in db
  if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
    userExists($conn, $u);
  }

  $countRels = 0;
  $countMine = 0;

  // Get user's friends 
  $all_friends = getUsersFriends($conn, $u, $log_username);
  $pcallf = join("','",$all_friends);

  // Select the photo given in the URL as a parameter
  $sql = "SELECT gallery FROM photos WHERE filename=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $p);
  $stmt->execute();
  $stmt->bind_result($galgal);
  $stmt->fetch();
  $stmt->close();

  // Select photos from the same gallery
  $ismob = mobc();
  $o = 0;
  $imit = 0;
  if($ismob == false){
    $imit = 9;
    $lmitS = 9;
  }else{
    $imit = 6;
    $lmitS = 6;
  }

  $samegp = "
    <div class='samegpdiv'>
      <p style='margin-top: 0;' class='txtc'>
        Suggested photos from friends
      </p>
      <div class='flexibleSol' id='photSamegp'>
  ";

  $sql = "SELECT * FROM photos WHERE gallery = ? AND user = ? AND filename != ?
    ORDER BY RAND() LIMIT $imit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss", $galgal, $u, $p);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $samegp .= genPhotoBox($row);      
      $o++;
      $hasSugg = true;
    }
  $stmt->close();
  }

  // If the num of suggested photos is less than limit suggest more
  if($o < $imit){
    $lmit = $imit - $o;
    $sql = "SELECT * FROM photos WHERE user IN('$pcallf') AND filename != ? ORDER BY RAND()
      LIMIT $lmitS";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $p);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $samegp .= genPhotoBox($row);
        $hasSugg = true;
        $o++;
      }
    }
  }

  // If we can still suggest more photos list images from users nearby
  if($o < $imit){
    // First get user's lat and lon coords
    list($mylat, $mylon) = getLatLon($conn, $u);

    // Make a max. acceptable correction
    $lat_m2 = $mylat-0.2;
    $lat_p2 = $mylat+0.2;

    $lon_m2 = $mylon-0.2;
    $lon_p2 = $mylon+0.2;
    $lmit = 9 - $o;

    $sql = "SELECT u.*, p.* FROM photos AS p LEFT JOIN users AS u ON u.username = p.user
      WHERE p.user NOT IN ('$all_friends') AND u.lat BETWEEN ? AND ? AND u.lon BETWEEN ?
      AND ? AND p.user != ? $lmitS";
    $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $samegp .= genPhotoBox($row);
        $hasSugg = true;
        $o++;
      }
    }
  }

  $samegp .= "</div></div>";

  if(!$hasSugg){
    $samegp = '
      <p style="font-size: 14px; color: #999; text-align: center;">
        Unfortunately, we could not list any photos for you from the same gallery
      </p>
      <br>
    ';
  }

  // Get that certain photo
  $big_photo = "";
  $gallery = "";
  $sql = "SELECT * FROM photos WHERE filename=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $p);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $id = $row["id"];
      $uder = $row["user"];
      $gallery = $row["gallery"];
      $description = $row["description"];
      if($description == NULL){
        $description = "No description given";
      }
      
      $shareButton = '';

      // Add a share button if user is logged in and the photo is not theirs
      if($_SESSION["username"] != "" && $u != $log_username){
        $shareButton = '
          <span class="photoBigBottom">
            <img src="/images/black_share.png" style="width: 20px; height: 20px;
              vertical-align: middle;" onclick="sharePhoto(\''.$id.'\')">
            <span style="vertical-align: middle;">Share</span>
          </span>
        ';
      }
      
      $uploaddate_ = $row["uploaddate"];
      $uploaddate = strftime("%b %d, %Y", strtotime($uploaddate_));
      $agoform = time_elapsed_string($uploaddate_);

      // If user is the owner of the photo add del btn
      if($log_username == $uder){
        $vdelete_btn = '
          <span class="photoBigBottom">
            <a onclick="deletePhoto(\''.$id.'\')">
              <img src="/images/dildel.png" width="18" height="18"
              style="vertical-align: middle;">
            </a>
            <span style="vertical-align: middle;">Delete</span>
          </span>
        ';
      }

      list($width,$height) = getimagesize('user/'.$u.'/'.$p);

      $big_photo .= '
        <div id="big_photo_holder" class="styleform" style="width: 100%;
          background-color: #fff;">
          <div class="innerImage">
            <img src="/user/'.$u.'/'.$p.'" onclick="openImgBig(\'/user/'.$u.'/'.$p.'\')"/>
          </div>

          '.$samegp.'
          <div class="clear"></div>
          <br>

          <div class="collection" id="ccSu" style="border-top: 1px solid rgba(0, 0, 0, 0.1);
            text-align: center;">
            <p style="font-size: 16px; margin-left: 23px;" id="signup">Photo Properties</p>
            <img src="/images/alldd.png">
          </div>

          <div class="slideInfo" id="suDD" style="text-align: center;">
            <p><b>Published by: </b><a href="/user/'.$uder.'/">'.$uder.'</a></p>
            <p style="word-break: break-all;"><b>Description: </b>'.$description.'</p>
            <p><b>Upload date: </b>'.$uploaddate.' | '.$agoform.' ago</p>
            <p><b>Gallery: </b>'.$gallery.'</p>
          </div>
          <div class="clear"></div>

          <div style="height: 35px; margin-top: 20px;" class="flexibleSol">
            '.$shareButton.'

            <span class="photoBigBottom">
              <a href="/photos/'.$log_username.'">
                <img src="/images/pback.png" width="18" height="18"
                style="vertical-align: middle;">
              </a>
              <span style="vertical-align: middle;">Back</span>
            </span>

            '.$vdelete_btn.'
        </div>
        <div class="clear"></div>
        <span id="info_stat"></span>
      </div>
      ';
    }
  }else{
    header('Location: /index');
  }

  $stmt->close();

  // Check to see if the user is blocked by the photo owner
  $isBlock = isBlocked($conn, $log_username, $u);
 
  // Check to see if the viewer is the owner of the page
  $isOwner = isOwner($u, $log_username, $user_ok);

  // Count how many comments there are
  $all_count = countComments($conn, 'photos_status', 'photo');

  // Get related photos
  $related_p = "";
  $nof = false;
  if($allfmy == ""){
      $nof = true;
  }
  $sql = "SELECT * FROM photos WHERE user IN ('$pcallf') ORDER BY RAND() LIMIT $lmitS";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $countRels++;
      $related_p .= genPhotoBox($row, true);
    }
  }else{
    $sql = "SELECT * FROM photos WHERE user != ? ORDER BY RAND() LIMIT $lmitS";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $countRels++;
      $related_p .= genPhotoBox($row, true);
    }
  }
  $stmt->close();

  // Get users's other photos
  $minep = "";
  $sql = "SELECT * FROM photos WHERE filename != ? AND user = ? ORDER BY uploaddate
    DESC LIMIT $lmitS";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $p, $log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $countMine++;
    $minep .= genPhotoBox($row, true);
  }
  $stmt->close();

  $isRP = true;
  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
    $related_p = '
      <p style="color: #999;" class="txtc">
        You need to be <a href="/login">logged in</a> in order to see related photos
      </p>
    ';
    $minep = '
      <p style="color: #999;" class="txtc">
        You need to be <a href="/login">logged in</a> in order to see your photos
      </p>
    ';
    $isRP = false;
  }

  $temp = false;
  if($minep == ""){
    $minep = '
      <p style="font-size: 14px; color: #999; text-align: center;">
        Unfortunately, you have no other listable photos uploaded. Come back later and upload
        new photos to your gallery.
      </p>
    ';
    $temp = true;
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta lang="en">
  <title><?php echo $u; ?> - <?php echo $gallery_u; ?></title>
  <link rel="icon" href="/images/newfav.png" type="image/x-icon">
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <link rel="stylesheet" href="/style/style.css">
  <meta name="description" content="Check <?php echo $u; ?>'s photo, comment & share your
    opinion and watch several other related photos as well, all on Pearscom.">
  <meta name="keywords" content="pearscom photos, pearscom gallery, pearscom galleries,
    pearscom photo, pearscom <?php echo $u; ?>, pearscom <?php  echo $gallery_u; ?>,
    photo big view pearscom, big, view, big view">
  <meta name="author" content="Pearscom">
  <script src="/js/specific/p_dialog.js"></script>
  <script src="/js/specific/dd.js"></script>
  <script src="/js/specific/error_dialog.js"></script>
  <script src="/js/specific/photo.js"></script>
</head>
<body style="overflow-x: hidden;">
  <?php require_once 'template_pageTop.php'; ?>
  <div id="overlay"></div>
  <div id="pageMiddle_2">
  <div id="dialogbox"></div>
  <div id="imagefloat">
    <?php echo $big_photo; ?>
  </div>
  <div id="data_holder">
    <div>
      <div><span><?php echo $countRels; ?></span> related photos</div>
    </div>
  </div>
  <div id="userFlexArts" class="flexibleSol mainPhotRel">
    <?php echo $related_p; ?>
  </div>
  <div class="clear"></div>
  <hr class="dim">
  <div id="data_holder">
    <div>
      <div><span><?php echo $countMine; ?></span> photos of mine</div>
    </div>
  </div>
  <div id="userFlexArts" class="flexibleSol mainPhotRel">
      <?php echo $minep; ?>
  </div>
  <div class="clear"></div>
  <hr class="dim">
  <?php if($isBlock != true){ ?>
    <?php require_once 'photos_status.php'; ?>
  <?php }else{ ?>
    <p style="font-size: 14px; color: #ffd11a;">
      <p style="color: #006ad8;" class="txtc">
        Alert: this user blocked you, therefore you cannot post on his/her photos!
      </p>
  <?php } ?>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    doDD("ccSu", "suDD");
  </script>
</body>
</html>
