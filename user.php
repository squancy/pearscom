<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/user_common.php';
  require_once 'php_includes/friends_common.php';
  require_once 'php_includes/gr_common.php';
  require_once 'php_includes/art_common.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/gen_work.php';
  require_once 'php_includes/gen_month.php';
  require_once 'php_includes/photo_common.php';
  require_once 'template_day_list.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/isfriend.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'durc.php';
  require_once 'phpmobc.php';
  require_once 'headers.php';
  require_once 'ccov.php';
  require_once 'php_includes/dist.php';    
 
  $ismobile = mobc();

  // Initialize any variables that the page might echo
  $u = "";
  $sex = "Male";
  $profile_pic_btn = "";
  $background_form = "";
  $one = "1";
  $c = "c";
  $a = "a";
  $b = "b";
  $max = 14;

  // Make sure the $_GET username is set and sanitize it
  $u = checkU($_GET['u'], $conn);
 
  // Check if user wants to write an article (redirection)
  $wart = checkGETParams($_GET['wart'], $conn,
    '<script type="text/javascript">writeArticle();</script>', 'yes');
  
  // Check if pm form should be showed
  $pmw = checkGETParams($_GET['pm'], $conn, '<script>showForm();</script>', 'write');

  // Check if the user exists in db
  userExists($conn, $u);

  // Check if user has been blocked
  $isBlock = isBlocked($conn, $log_username, $u);

  // Check to see if the viewer is the account owner
  $isOwner = "No";
  if($u == $log_username && $user_ok == true){
    $isOwner = "Yes";
    $profile_pic_btn = '
      <span id="blackbb">
        <img src="/images/cac.png" onclick="return false;" id="ca"
          onmousedown="toggleElement(\'avatar_form\')" width="20" height="20">
      </span>
      <form id="avatar_form" enctype="multipart/form-data" method="post"
        action="/php_parsers/photo_system.php">
        <div id="add_marg_mob">
          <input type="file" name="avatar" id="file" class="inputfile ppChoose"
            required accept="image/*">
          <label for="file" style="font-size: 12px;">Choose a file</label>
            <p>
              <input type="submit" value="Upload" class="main_btn_fill fixRed"
                style="font-size: 12px;">
            </p>
        </div>
      </form>
    ';

    // Background form
    $background_form  = '
      <form id="background_form" style="text-align: center;"
        enctype="multipart/form-data" method="post"
        action="/php_parsers/photo_system.php">
        <input type="file" name="background" id="bfile" class="inputfile"
          onchange="showfile()" required accept="image/*">
        <label for="bfile" style="margin-right: 10px;">Choose a file</label>
        <input type="submit" class="main_btn_fill fixRed" value="Upload Background"
          id="fixFlow">
        <span id="sel_f"></span>
        <p style="color: #999; font-size: 14px;" class="txtc">
          <b>Note: </b>
          the allowed file extensions are: jpeg, jpg, png and gif and the maximum file
          size limit is 5MB
        </p>
      </form>
    ';
  }

  // Fetch user information 
  $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $u, $one);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $profile_id = $row["id"];
    $gender = $row["gender"];
    $country = $row["country"];
    $userlevel = $row["userlevel"];
    $signup = $row["signup"];
    $avatar = $row["avatar"];
    $lastlogin = $row["lastlogin"];
    $lastsession = strftime("%b %d, %Y", strtotime($lastlogin));

    // Get the latlon as user A
    $uBlatlon = $row["latlon"];
    $bdor = $row["bday"];
    $bdate = mb_substr($row["bday"], 5, 9, "utf-8");
    $birthday_ = $row["bday"];
    $birthday = strftime("%b %d, %Y", strtotime($birthday_));
    $birthday_year = mb_substr($row["bday"], 0, 4, "utf-8");
    $onlineornot = $row["online"];
    $joindate = strftime("%b %d, %Y", strtotime($signup));
    $memberfor = time_elapsed_string($signup);
  }

  $is_birthday = "no";
  $today_is = date('m-d');
  if($today_is == $bdate){
    $is_birthday = "yes";
  }

  /*
    If user was born in a leap year and this year is not a leap year celebreate their
    birthday on 02-28
  */

  $leap = date("L");
  if($leap == '0' && $today_is == "02-28" && $bdate == '02-29'){
    $is_birthday = "yes";
  }

  if($gender == "f"){
    $sex = "Female";
  }

  $profile_pic = '/user/'.$u.'/'.$avatar;

  if($avatar == NULL){
    $profile_pic = '/images/avdef.png';
  }
  
  // Get logged in user's geolocation
  list($lat, $lon) = getLatLon($conn, $log_username);
  
  // Get profile page user's geolocation
  list($blat, $blon) = getLatLon($conn, $u);
  
  // Calculate the distance between them
  $distBetween = vincentyGreatCircleDistance($lat, $lon, $blat, $blon);

  // Check if the current user is an adult or still underage
  list($agestring, $age) = genAgeString($bdor);

  // Check if user has verified themselves or not
  $userlevel = checkUserLevel($userlevel);
  
  $grbtn = "";
  if($isOwner == "Yes"){
    $grbtn = '
      <button onclick="hreftogr()" id="vupload">
        View Groups <img src="/images/vgr.png" class="notfimg"
          style="margin-bottom: -2px;">
      </button>
    ';
  }

  // Check if logged in user and profile user are friends
  $isFriend = isFriend($u, $log_username, $user_ok, $conn);
  if($u != $log_username && $user_ok == true){
    $ownerBlockViewer = checkBlockings($conn, $u, $log_username);
    $viewerBlockOwner = checkBlockings($conn, $log_username, $u);
  }

  $friend_button = '
    <button style="opacity: 0.6; cursor: not-allowed;" class="main_btn_fill fixRed">
      Request as friend
    </button>
  ';

  $block_button = '
    <button style="opacity: 0.6; cursor: not-allowed;" class="main_btn_fill fixRed">
      Block User
    </button>
  ';

  // Logic for displaying friend button
  if($isFriend){
    $fAction = 'unfriend';
    $fButtonText = 'Unfriend';
  } else if ($user_ok == true && $u != $log_username && !$ownerBlockViewer){
    $fAction = 'friend';
    $fButtonText = 'Request as friend';
  }

  if (!$ownerBlockViewer) {
    $friend_button = '
      <button onclick="friendToggle(\''.$fAction.'\',\''.$u.'\',\'friendBtn\')"
        class="main_btn_fill fixRed">'.$fButtonText.'</button>
    ';
  }

  $zeroone = isAccepted($conn, $log_username, $u);
  if($zeroone == "0"){
    $friend_button = '
      <p style="font-size: 14px; color: #999; margin: 0;">
        Friend request is waiting for approval
      </p>
    ';
  }

  // Logic for displaying block button
  if($viewerBlockOwner == true){
    $bAction = 'unblock';
    $bButtonText = 'Unblock';
  } else if($user_ok == true && $u != $log_username){
    $bAction = 'block';
    $bButtonText = 'Block';
  }

  $block_button = '
    <button onclick="blockToggle(\''.$bAction.'\',\''.$u.'\',\'blockBtn\')"
      class="main_btn_fill fixRed">'.$bButtonText.' user</button>
  ';

  $isFollow = isFollow($conn, $log_username, $u);

  $isFollowOrNot = "";
  $gs = "him";

  // Logic for displaying follow button
  if($isFollow){
    $fAction = 'unfollow';
    $fButtonText = 'Unfollow';

    if($gender == "f"){
      $gs = "her";
    }

    $isFollowOrNot = "<p style='color: #999;' id='isFol'>You're following ".$gs."</p>";
  }else{
    $fAction = 'follow';
    $fButtonText = 'Follow';
  }

  $follow_button = '
    <button class="main_btn_fill fixRed"
      onclick="followToggle(\''.$fAction.'\',\''.$u.'\',\'followBtn\', \'isFol\')">
      '.$fButtonText.'
    </button>
  ';

  $friendsHTML = '';
  $friends_view_all_link = '';
  $bdfusres = "";
  $all_friends = array();

  // Count num of friends
  $friend_count = numOfFriends($conn, $u);

  // Count online friends
  $yes = "yes";
  $sql = "SELECT COUNT(f.id) FROM friends AS f LEFT JOIN users AS u ON u.username = f.user1
    WHERE (f.user1=? AND f.accepted=?) OR (f.user2=? AND f.accepted=?) AND u.online = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sssss", $u, $one, $u, $one, $yes);
  $stmt->execute();
  $stmt->bind_result($online_count);
  $stmt->fetch();
  $stmt->close();

  if($friend_count < 1){
    if($isOwner == "Yes"){
      $friendsHTML = '<p style="color: #999;" class="txtc">You have no friends yet</p>';
    }else{
      $friendsHTML = '<p style="color: #999;" class="txtc">'.$u.' has no friends yet</p>';
    }
  } else {
    // Get user's all friends and display some of them
    $all_friends = getUsersFriends($conn, $u, $u);
    shuffle($all_friends);
    $fCSV = implode("','", $all_friends);

    $friendArrayCount = count($all_friends);
    if($friendArrayCount > $max){
      array_splice($all_friends, $max);
    }

    if($friend_count > $max){
      $friends_view_all_link = '<a href="/view_friends/'.$u.'">View all</a>';
    }

    $sql = "SELECT * FROM users WHERE username IN ('$fCSV')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result3 = $stmt->get_result();
    while($row = $result3->fetch_assoc()) {
      $friendsHTML .= genRightBox($row, $lat, $lon, $conn);
    }
    $stmt->close();
  }

  // Followers count
  $followersHTML = "";
  $follower_count = countFols($conn, $u, 'following');
  if($follower_count < 1){
    $followersHTML = '<b>'.$u." has no followers yet.</b>";
  }

  $following_count = countFols($conn, $u, 'follower');

  // Followers & followings profile pic
  $sqlParams = ['follower', 'following'];
  $varNames = ['following_div', 'other_div'];
  for ($i = 0; $i < 2; $i++) {
    $sql = "SELECT u.*, f.follower
            FROM users AS u
            LEFT JOIN follow AS f ON u.username = f.".$sqlParams[0]."
            WHERE f.".$sqlParams[1]." = ? ORDER BY RAND() LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      ${$varNames[$i]} .= genRightBox($row, $lat, $lon, $conn);
    }
  
    $stmt->close();

    // Swap values in array
    $tmp = $sqlParams[0];
    $sqlParams[0] = $sqlParams[1];
    $sqlParams[1] = $tmp;
  }
  
  // Create the photos button
  $photos_btn = "
    <button onclick='window.location = '/photos/<?php echo $u; ?>View Photos</button>
  ";
      
  // Gather more information about user
  $sql = "SELECT * FROM edit WHERE username=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $job = $row["job"];
      $about = $row["about"];
      $profession = $row["profession"];
      $state = $row["state"];
      $city = $row["city"];
      $mobile = $row["mobile"];
      $hometown = $row["hometown"];
      $fmusic = $row["fav_music"];
      $fmovie = $row["fav_movie"];
      $pstatus = $row["par_status"];
      $elemen = $row["elemen"];
      $high = $row["high"];
      $uni = $row["uni"];
      $politics = $row["politics"];
      $religion = $row["religion"];
      $nd_day = $row["nd_day"];
      $nd_month = $row["nd_month"];
      $ndtonum = strftime("%m", strtotime($nd_month));
      $ndtogether = "2018-".$ndtonum."-".$nd_day;
      $interest = $row["interest"];
      $notemail = $row["notemail"];
      $website = $row["website"];
      $language = $row["language"];
      $address = $row["address"];
      $degree = $row["degree"];
      $quotes = $row["quotes"];
      $cleanqu = $quotes;
      $cleanqu = str_replace("”",'',$cleanqu);
     
      // Prefix website link with http://
      if(!(substr($website, 0, 7) === "http://") && $website){
          $website = "http://".$website;
      }
      
      // Prefix for email
      $emailURL = $notemail;
      if(!(substr($notemail, 0, 7) === "mailto:")){
          $emailURL = "mailto:".$notemail;
      }
    }

    if($profession == "w"){
        $works = "Working";
    }else if($profession == "r"){
        $works = "Retired";
    }else if($profession == "u"){
        $works = "Unemployed";
    }else if($profession == "o"){
        $works = "Other";
    }else{
        $works = "Student";
    }

    $stmt->close();
  }

  // Add article button
  $article = "";
  if($log_username != "" && $user_ok && $isOwner == "Yes"){
    $article = '
      <button class="main_btn_fill fixRed" onclick="hgoArt()">Write article</button>
    ';
  }

  // Echo articles
  $echo_articles = "";
  $numnum = 0;
  $sql = "SELECT * FROM articles WHERE written_by=? ORDER BY RAND() LIMIT 6";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $echo_articles .= genFullBox($row);
    }
  }

  // Get background
  $attribute = "";
  $sql = "SELECT background FROM useroptions WHERE username=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $stmt->bind_result($bg);
  $stmt->fetch();
  $stmt->close();

  $attribute = '/user/'.$u.'/background/'.$bg;
  if($bg == NULL || $bg == "original"){
    $attribute = '/images/backgrounddefault.png';
  }

  // Get user photos
  $numnum = 0;
  $userallf = getUsersFriends($conn, $u, $log_username);
  $uallf = join("','",$userallf);
  $echo_photos = "";

  // Decide how many photos we should display depending on the user platform
  if(!$ismobile){
    $lmit = 10;
  }else{
    $lmit = 6;
  }

  $sql = "SELECT * FROM photos WHERE user=? ORDER BY uploaddate DESC LIMIT 12";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $filename_photo = $row["filename"];
    $gallery_photo = $row["gallery"];
    $description = $row["description"];
    $uploader = $row["user"];
    $udate = strftime("%b %d, %Y", strtotime($row["uploaddate"]));;
    $ud = time_elapsed_string($udate);
    $description = wrapText($description, 20);

    if($description == ""){
      $description = "No description ...";
    }

    // Select friends for photo
    $sql = "SELECT author FROM photos_status WHERE photo = ? AND author IN ('$uallf')
      LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filename_photo);
    $stmt->execute();
    $stmt->bind_result($fwhoc);
    $stmt->fetch();
    $stmt->close();

    if($fwhoc == ""){
      $fwhoc = "none of your friends has posted yet";
    }

    $uploader = wrapText($uploader, 35);
    $fwhoc = wrapText($fwhoc, 35);
    $numnum++;

    $pcurl = '/user/'.$u.'/'.$filename_photo;
    $openURL = '/photo_zoom/'.$u.'/'.$filename_photo;
    list($width,$height) = getimagesize('user/'.$u.'/'.$filename_photo);

    $echo_photos .= '
      <div class="pccanvas userPhots" onmouseover="appPho(\''.$numnum.'\')"
        onmouseleave="disPho(\''.$numnum.'\')" onclick="openURL(\''.$openURL.'\')">
        <div class="pcnpdiv lazy-bg" data-src=\''.$pcurl.'\'>
          <div id="photo_heading" style="width: auto !important; margin-top: 0px;
            position: static;">'.$width.' x '.$height.'
          </div>
        </div>
        <div class="infoimgdiv" id="phonum_'.$numnum.'" style="width: auto; height: auto;">
          <div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat;
            background-position: center; background-size: cover; width: 120px;
            height: 103px; float: left; border-radius: 10px;" class="lazy-bg">
          </div>
          <span>
            <img src="/images/picture.png" width="12" height="12">
            &nbsp;Gallery: '.$gallery_photo.'<br>
            <img src="/images/desc.png" width="12" height="12">
            &nbsp;Description: '.$description.'<br>
            <img src="/images/nddayico.png" width="12" height="12">
            &nbsp;Pusblished: '.$udate.' ('.$ud.' ago)<br>
            <img src="/images/puname.png" width="12" height="12">
            &nbsp;Uploader: '.$uploader.'<br>
            <img src="/images/fus.png" width="12" height="12">
            &nbsp;Friends who posted below the photo: '.$fwhoc.'
          </span>
        </div>
      </div>
    ';
  }

  // Get user's videos
  $videos = "";
  $sql = "SELECT * FROM videos WHERE user=? ORDER BY RAND() LIMIT 3";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$u);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $id = $row["id"];
    $vf = $row["video_file"];
    $description = $row["video_description"];
    $video_name = $row["video_name"];
    $video_upload = $row["video_upload"];
    $pr = $row["video_poster"];
    $dur = $row["dur"];
    $dur = convDur($dur);
    $video_upload_ = strftime("%b %d, %Y", strtotime($video_upload));
    if($video_name == ""){
      $video_name = "Untitled";
    }

    if($description == ""){
      $description = "No description";
    }

    if($pr == ""){
      $pr = "/images/uservid.png";
    }else{
      $pr = '/user/'.$u.'/videos/'.$pr;
    }

    $description = wrapText($description, 22);
    $video_name = wrapText($video_name, 22);

    $ec = base64url_encode($id, $hshkey);
    $videos .= "
      <a href='/video_zoom/" . $ec . "' style='height: 150px;'>
        <div class='nfrelv' style='width: 100%;'>
          <div data-src=\"".$pr."\" class='lazy-bg' style='height: 150px;' id='pcgetc'></div>
          <div class='pcjti'>" . $video_name . "</div>
          <div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px;
            position: absolute; bottom: 15px;'>" . $dur . "</div>
        </div>
      </a>
    ";
  }
  $stmt->close();

  if(!$videos){
    if($isOwner == "No"){
      $videos = "
        <p style='color: #999;' class='txtc'>
          It seems that ".$u." has not uploaded any videos yet
        </p>
      ";
    }else{
      $videos = "
        <p style='color: #999;' class='txtc'>
          It seems that you have not uploaded any videos yet
        </p>
      ";
    }
  }
  $stmt->close();

  $myf = getUsersFriends($conn, $u, $log_username);
  $theirf = getUsersFriends($conn, $log_username, $u);

  $incomm = array_intersect($myf, $theirf);
  $resincomm = count($incomm);

  // Get number of all photos
  $count_all = countUserPhots($conn, $u);

  // Get number of favs given for user's arts
  $count_favs = cntLikesNew($conn, $u, 'fav_art', 'art_uname');

  // Get num of videos
  $count_vids = cntLikesNew($conn, $u, 'videos', 'user');

  $sql = "SELECT COUNT(l.id) FROM video_likes AS l LEFT JOIN videos AS v ON v.id = l.video
    WHERE l.username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $stmt->bind_result($count_vid_likes);
  $stmt->fetch();
  $stmt->close();
  
  // Get num of likes given on user's arts
  $count_likes = cntLikesNew($conn, $u, 'heart_likes', 'art_uname');

  // Count user's arts
  $count_arts = cntLikesNew($conn, $u, 'articles', 'written_by');

  // Wish user a happy name day 
  $try = date("Y-m-d");
  $sql = "SELECT DATEDIFF(?,?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $ndtogether, $try);
  $stmt->execute();
  $stmt->bind_result($untilnd);
  $stmt->fetch();
  $stmt->close();

  if($untilnd < 0){
    $untilnd = $days + $untilnd;
  }

  $untilndday = $untilnd." days until name day";
  if($untilndday == 0){
    $untilndday = "happy name day!";
  }

  // Get groups
  $echo_groups = "";
  $sql = "SELECT gm.*, gp.*
      FROM gmembers AS gm
      LEFT JOIN groups AS gp ON gp.name = gm.gname
      WHERE gm.mname = ? ORDER BY gp.creation DESC LIMIT 7";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $echo_groups .= genGrBox($row);
  }

  $echo_groups .= '<div class="clear"></div>';
  $stmt->close();

  if($other_div == ""){
    if($isOwner == "Yes"){
      $other_div = "
        <p style='font-size: 14px; color: #999;' class='txtc'>
          It seems that you do not follow anyone right now
        </p>
      ";
    }else{
      $other_div = "
        <p style='font-size: 14px; color: #999;' class='txtc'>
          It seems that ".$u." do not follow anyone right now
        </p>
      ";
    }
  }

  // Count user as a member and group creator
  $member_count = cntLikesNew($conn, $u, 'gmembers', 'mname');
  $creator_count = cntLikesNew($conn, $u, 'groups', 'creator');
?>
<!DOCTYPE html>
<html>
<head>
  <title>
    <?php
      if (!$wart){
        echo $u;
      } else {
        echo 'Write an article';
      }
    ?>
  </title>
  <meta charset="utf-8">
  <meta lang="en">
  <meta name="description" content="Check <?php echo $u; ?>'s articles, photos,
    videos and friends, send them a message and post on their profile!">
  <meta name="keywords" content="<?php echo $u; ?>, <?php echo $u; ?> pearscom,
    <?php echo $u; ?> profile, user profile <?php echo $u; ?>, user <?php echo $u; ?>">
  <meta name="author" content="Pearscom">
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js"></script>
  <script src="/js/main.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/mbc.js"></script>
  <script src="/js/specific/p_dialog.js"></script>
  <script src="/js/specific/error_dialog.js"></script>
  <script src="/js/specific/user.js" defer></script>
  <script src="/js/specific/main_art.js"></script>
  <script src="/js/specific/friendsugg.js"></script>
  <script src="/js/specific/status_max.js"></script>
  <script src="/text_editor.js"></script>
  <script src="/js/fadeEffects.js" async></script>

  <style type="text/css">
    @media only screen and (max-width: 747px){
      #video_controls_bar{
        width: 50% !important;
      }
    }
  </style>
  <script type="text/javascript">
    const PUNAME = '<?php echo $u; ?>';
    var luname = "<?php echo $log_username; ?>";
    var hasImageGen1 = "";
    var hasImageGen2 = "";
    var hasImageGen3 = "";
    var hasImageGen4 = "";
    var hasImageGen5 = "";

    function writeArticle() {
      var cancel = _("article_show");
      var header = _("writearticle");
      var input = _("art_btn");
      var tmp = _("hide_it");
      var code = _("userNavbar");
      var t = _("slide1");
      var line = _("slide2");
      if ("block" == cancel.style.display) {
        tmp.style.display = "none";
        cancel.style.display = "block";
        header.style.display = "block";
        input.style.display = "block";
        input.style.opacity = "0.9";
        code.style.display = "block";
        _("menuVer").style.display = "flex";
      } else {
        cancel.style.display = "none";
        header.style.display = "block";
        tmp.style.display = "none";
        code.style.display = "none";
        t.style.display = "none";
        line.style.display = "none";
        _("menuVer").style.display = "none";
        window.scrollTo(0, 0);
      }
    }
  </script>
</head>
<body onload="enableEditMode()" style="overflow-x: hidden;">
  <?php require_once "template_pageTop.php"; ?>
  
  <div id="overlay"></div>
  <div id="dialogbox"></div>
  <div id="dialogbox_art"></div>
  <div id="pageMiddle_2">
    <div class="row">
      <div id="name_holder">
        <?php
          echo $u;
          if($nd_day != "" && $nd_month != ""){
            if($untilndday == "happy name day!"){
        ?>
              <img src="/images/nddayico.png" width="20" height="20"
                style="margin-bottom: -2px;">
        <?php
            }
          }
        ?>
        <?php
          if ($is_birthday == "yes" && $isOwner == "No"){
            echo '<img src="/images/bdcake.png" height="25" width="25" style="margin-left: 5px;
            margin-top: -3px; vertical-align: middle;">';
          }else if ($is_birthday == "yes" && $isOwner == "Yes"){
            echo '<img src="/images/bdcake.png" height="25" width="25"
              style="margin-left: 5px; margin-top: -3px; vertical-align: middle;"
              title="Happy birthday '.$log_username.'!">';
          }
        ?>
      </div>

      <div data-src="<?php echo $attribute; ?>" class="lazy-bg" id="bg_holder_user">
        <div id="profile_pic_box" class="genBg lazy-bg" data-src="<?php echo $profile_pic; ?>"
          onclick="openPP('<?php echo $avatar; ?>','<?php echo $u; ?>')">
          <?php echo $profile_pic_btn; ?>
        </div>
      </div>
      <div class="clear"></div>

      <div class="infoHolder" style="margin-top: 20px; padding: 5px; display: flex;"
        id="menuVer">
        <img src="/images/usrarr2.png" width="20" height="20"
          style="margin-top: 16px; margin-right: 5px; float: left; cursor: pointer;"
          id="slide1">
          <div id="userNavbar">
            <div id="userInfo">Information</div>
              <?php if($u == $log_username){ ?>
                <div id="userEdit">Edit information</div>
              <?php } ?>
              <?php if($log_username != $u && $_SESSION["username"] != "" && !$isBlock){ ?>
                <div id="userPm" onclick="showForm();">Messages</div>
              <?php } ?>
              <div id="userFriends">Friends</div>
              <div id="userPhotos">Photos</div>
              <div id="userArticles">Articles</div>
              <div id="userFollowers">Followers</div>
              <div id="userVideos">Videos</div>
              <?php if($u == $log_username){ ?>
                <div id="userBackground">Background</div>
              <?php } ?>
              <div id="userGroups">Groups</div>
            </div>
            <img src="/images/usrarr.png" width="20" height="20"
              style="margin-top: 16px; margin-left: 5px; float: left; cursor: pointer;"
              id="slide2">
          </div>
          <div class="clear"></div>
          <div id="hide_it">
            <div id="min_height">
              <div id="aboutInfo">
                <div class="infoHolder">
                  <div class="overviewInner">
                    <div id="genI">General Information</div>
                    <div id="perI">Personal Information</div>
                    <div id="conI">Contact Information</div>
                    <div id="eduI">Education &amp; Jobs</div>
                    <div id="aboI">About Me</div>
                  </div>
                  <div class="contentInner" id="genIDiv">
                    <div><span>Gender: </span><?php echo $sex; ?></div>
                    <div><span>Country: </span><?php echo $country; ?></div>
                    <div><span>User Security: </span> <?php echo $userlevel; ?></div>
                    <div><span>Member For: </span> <?php echo $memberfor; ?></div>
                    <div><span>Last Seen: </span> <?php echo $lastsession; ?></div>
                    <div><span>Birthday: </span> <?php echo $birthday; ?></div>
                    <div><span>Age: </span><?php echo $age; ?><?php echo $agestring; ?></div>
                  <?php if($state != ""){ ?>
                    <div><span>State/Province: </span><?php echo $state; ?></div>
                  <?php } ?>
                     
                  <?php if($city != ""){ ?>
                    <div><span>City/Town: </span><?php echo $city; ?></div>
                  <?php } ?>
                 
                  <?php if($nd_day != "" && $nd_month != ""){ ?>
                    <div><span>Name day: </span><?php echo $nd_day.", ".$nd_month; ?></div>
                  <?php } ?>

                  <?php if($quotes != ""){ ?>
                    <div><span>Favourite quotes: </span><?php echo $quotes; ?></div>
                  <?php } ?>

                  <?php if(!$sey && !$country && !$userlevel && !$memberfor &&
                    !$lastsession && !$birthday && !$agestring && !$state && !$city
                    && !$nd_day && !$quotes){
                  ?>
                    <p style="text-align: center; color: #999;">
                      This user has not given any information yet
                    </p>
                  <?php } ?>
                </div>
                <div class="clear"></div>

                <div class="contentInner" id="perIDiv">
                  <?php if($hometown != ""){ ?>
                    <div><span>Hometown: </span><?php echo $hometown; ?></div>
                  <?php } ?>
              
                  <?php if($fmovie != ""){ ?>
                    <div><span>Favourite Movies: </span><?php echo $fmovie; ?></div>
                  <?php } ?>
              
                  <?php if($fmusic != ""){ ?>
                    <div><span>Favourite Songs/Music: </span><?php echo $fmusic; ?></div>
                  <?php } ?>
              
                  <?php if($pstatus != ""){ ?>
                   <div><span>Partnership Status: </span><?php echo $pstatus; ?></div>
                  <?php } ?>
              
                  <?php if($politics != ""){ ?>
                    <div><span>Political Views: </span><?php echo $politics; ?></div>
                  <?php } ?>
              
                  <?php if($religion != ""){ ?>
                    <div><span>Religious views: </span><?php echo $religion; ?></div>
                  <?php } ?>
              
                  <?php if($interest != ""){ ?>
                    <div><span>I'm interested in: </span><?php echo $interest; ?></div>
                  <?php } ?>
              
                  <?php if($language != ""){ ?>
                    <div><span>Language: </span><?php echo $language; ?></div>
                  <?php } ?>

                  <?php if(!$hometown && !$fmovie && !$pstatus && !$politics && !$religion
                    && !$interests && !$language){ ?>
                    <p style="text-align: center; color: #999;">
                      This user has not given any information yet
                    </p>
                  <?php } ?>
                </div>
                <div class="clear"></div>

                <div class="contentInner" id="conIDiv">
                  <?php if($mobile != ""){ ?>
                    <div><span>Mobile: </span><?php echo $mobile; ?> </div>
                  <?php } ?>
                
                  <?php if($notemail != ""){ ?>
                    <div>
                      <span>Email: </span><a href="<?php echo $emailURL; ?>">
                      <?php echo $notemail; ?></a>
                    </div>
                  <?php } ?>
                
                  <?php if($website != ""){ ?>
                    <div>
                      <span>Website: </span><a href="<?php echo $website; ?>">
                      <?php echo $website; ?></a>
                    </div>
                  <?php } ?>
                
                  <?php if($address != ""){ ?>
                    <div><span>Address: </span><?php echo $address; ?></div>
                  <?php } ?>

                  <?php if(!$mobile && !$notemail && !$website && !$address){ ?>
                    <p style="text-align: center; color: #999;">
                      This user has not given any information yet
                    </p>
                  <?php }?>
                </div>
                <div class="clear"></div>

                <div class="contentInner" id="eduIDiv">
                  <?php if($elemen != ""){ ?>
                      <div><span>Elementary School: </span><?php echo $elemen; ?></div>
                  <?php } ?>
                
                  <?php if($high != ""){ ?>
                    <div><span>High School: </span><?php echo $high; ?> </div>
                  <?php } ?>
              
                  <?php if($uni != ""){ ?>
                    <div><span>University: </span><?php echo $uni; ?> </div>
                  <?php } ?>
              
                  <?php if($profession != ""){ ?>
                    <div><span>Profession: </span><?php echo $works; ?></div>
                  <?php } ?>
              
                  <?php if($job != ""){ ?>
                    <div><span>Job: </span><?php echo $job; ?></div>
                  <?php } ?>
              
                  <?php if($degree != ""){ ?>
                    <div><span>Degree, certificate: </span><?php echo $degree; ?></div>
                  <?php } ?>

                  <?php if(!$elemen && !$high && !$uni && !$profession && !$job){ ?>
                    <p style="text-align: center; color: #999;">
                      This user has not given any information yet
                    </p>
                  <?php }?>
                </div>
                <div class="clear"></div>

                <div class="contentInner" id="aboIDiv">
                  <?php if($about != ""){ ?>
                    <div><?php echo $about; ?></div>
                  <?php } ?>

                  <?php if($about == ""){ ?>
                    <p style="text-align: center; color: #999;">
                      This user has not written anything interesting about them yet
                    </p>
                  <?php }?>
                </div>
                <div class="clear"></div>
              </div>
            </div>
          </div>

          <?php if($log_username == $u && $user_ok == true){ ?>
            <div id="editAbout" class="infoHolder">
              <p class="txtc">
                Give information about yourself in 5 topics - education, profession, city,
                about me &amp; personal information, contact - to make your profile more 
                recognizable for your friends and to make sure you are not a fake and unvalid
                user!
                <div class="pplongbtn profDDs" id="infodd">
                  Give information about yourself
                  <img src="/images/down-arrow.png" width="16" height="16"
                    style="float: right;">
                </div>
                <div style="display: none" id="artdd_div">
                  &bull; You can easily edit your information by clicking on the
                  <i>Edit Profile</i> button. There you can choose from 5 separated topics -
                  click on the right ones to make the menu go down - and from over 20 smaller
                  topics. It is highly recommended to fill in as many gaps as you can because
                  we (and other users also) prefer those users who has more information about
                  them. The more information you give the more people will trust you. However
                  do NOT give any private or confidental information like your password, log in
                  email address, credit card number etc. We cannot take any resposibilities for
                  you if you release these infos in public.<br />&bull; If you cannot fill in a
                  gap - for instance you have not graduated yet from a university - just leave
                  it as blank (this time there will be nothing displayed) or you can write
                  something like <i>not graduated yet</i> or <i>none</i> (this time the
                  information will be displayed with your given value).
                </div>
              </p>
          
              <form name="editprofileform" id="editprofileform" class="ppForm"
                onsubmit="return false;">
                <button class="main_btn_fill fixRed" id="education"
                  onclick="openDD('edu')">Education</button>
                <button class="main_btn_fill fixRed" id="profession"
                  onclick="openDD('pro_')">Profession</button>
                <button class="main_btn_fill fixRed" id="citydiff"
                  onclick="openDD('city_')">City</button>
                <button class="main_btn_fill fixRed" id="aboutmepi"
                  onclick="openDD('me')">About me &amp; personal information</button>
                <button class="main_btn_fill fixRed" id="contactf"
                  onclick="openDD('con')">Contact</button>
                <button class="main_btn_fill fixRed"
                  onclick="openDD('geoLoc')">Geolocation</button>
              </form>
              <hr class="dim" id="showHr" style="display: none;">

              <div class="ppddHolder">
                <div id="edu" style="display: none;">
                  <input id="elemen" type="text" placeholder="Elementary School"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $elemen; ?>">
                    <input id="high" type="text" placeholder="High School"
                      onfocus="emptyElement('status')" maxlength="150"
                      value="<?php echo $high; ?>">
                    <input id="uni" type="text" placeholder="University"
                      onfocus="emptyElement('status')" maxlength="150"
                      value="<?php echo $uni; ?>">
                    <input id="degree" type="text" placeholder="Degree"
                      onfocus="emptyElement('status')" maxlength="150"
                      value="<?php echo $degree; ?>">
                </div>

                <div id="pro_" style="display: none;">
                  <input id="job" type="text" placeholder="Job"
                    onfocus="emptyElement('status')" maxlength="150" 
                    value="<?php echo $job; ?>">
                  <select id="profession_sel" onfocus="emptyElement('status')">
                    <option value="" selected="true" disabled="true">Choose profession</option>
                    <?php
                      echo genWorkTypes(['Working', 'Retired', 'Unemployed', 'Student',
                        'Other'], $works);
                    ?>
                  </select>
                </div>

                <div id="city_" style="display: none;">
                  <input id="state" type="text" placeholder="State/Province"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $state; ?>">
                  <input id="city" type="text" placeholder="City"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $city; ?>">
                  <input id="hometown" type="text" placeholder="Hometown"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $hometown; ?>">
                </div>

                <div id="me" style="display: none;">
                  <textarea id="ta" onkeyup="statusMax(this,1000)" placeholder="About me"
                    onfocus="emptyElement('status')"><?php echo $about; ?></textarea>
                  <textarea id="movies" class="movie_music" placeholder="Favourite film"
                    onkeyup="statusMax(this,400)"><?php echo $fmovie; ?></textarea>
                  <textarea id="music" class="movie_music" placeholder="Favourite music"
                    onkeyup="statusMax(this,400)"><?php echo $fmusic; ?></textarea>
                  <textarea id="quotes" class="movie_music" placeholder="Favourite quotes"
                    onkeyup="statusMax(this,400)"><?php echo $cleanqu; ?></textarea>
                  <input id="pstatus" type="text" placeholder="Partnership status"
                    value="<?php echo $pstatus; ?>">
                  <input id="politics" type="text" placeholder="Political views"
                    value="<?php echo $politics; ?>">
                  <input id="religion" type="text" placeholder="Religion"
                    value="<?php echo $religion; ?>">
                  <input id="language" type="text" placeholder="Languages"
                    value="<?php echo $language; ?>">
                  <select id="nd_day" onfocus="emptyElement('status')">
                    <option value="" selected="true", disabled="true">Nameday day</option>
                    <?php
                      echo genDayVals($nd_day);
                    ?>
                  </select>
                  <select id="nd_month" onfocus="emptyElement('status')">
                    <option value="" disabled="true" selected>Nameday month</option>
                    <?php
                      echo genMonthVals($nd_month);
                    ?>
                  </select>
                  <input id="interest" type="text" placeholder="Interested in..."
                    value="<?php echo $interest; ?>">
                </div>

                <div id="con" style="display: none;">
                  <input id="mobile" type="text" placeholder="Mobile number"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $mobile; ?>">
                  <input id="notemail" type="email" placeholder="Email address"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $notemail; ?>">
                  <input id="website" type="text" placeholder="Website URL"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $website; ?>">
                  <input id="address" type="text" placeholder="Address"
                    onfocus="emptyElement('status')" maxlength="150"
                    value="<?php echo $address; ?>">
                </div>

                <div id="geoLoc" style="display: none;">
                  <button class="main_btn_fill fixRed" onclick="getLocation()">
                    Locate me
                  </button>
                  <span style="margin: 10px;">
                    Longitude: <span id="lon_update">not set</span>
                  </span>
                  <span style="margin: 10px;">
                    Latitude: <span id="lat_update">not set</span>
                  </span>
                </div>
              </div>

              <div class="clear"></div>
                <div id="status"></div>
                  <span id="after_status"></span>

                  <div id="appendLoc">
                    <span id="mapholder_update" style="margin-top: 7px;"></span>
                    <span id="update_coords" style="margin-top: 7px;"></span>
                  </div>

                  <button id="editbtn" class="main_btn"
                    style="display: none; margin: 0 auto; margin-top: 10px; padding: 7px;"
                    onclick="editChanges()">Save changes</button>
                  <button id="geolocBtn" class="main_btn"
                    style="display: none; margin: 0 auto; margin-top: 10px; padding: 7px;"
                    onclick="saveNewGeoLoc()">Save changes</button>
                </div>
              </div>
            <?php } ?>
            <form id="writearticle" name="writearticle" onsubmit="return false;">
              <p style="font-size: 22px; color: #999;" class="txtc">Create an article</p>
              <p style="color: #999; text-align: center; font-size: 14px;">
                Before writing an article please make sure you read the 'How to write a proper
                and well-recieved article?' section
              </p>
              <textarea name="title" id="title" type="text" maxlength="100"
                placeholder="Article Title"></textarea>
              <div class="toolbar">
                <a onclick="execCmd('bold')"><i class='fa fa-bold'></i></a>
                <a onclick="execCmd('italic')"><i class='fa fa-italic'></i></a>
                <a onclick="execCmd('underline')"><i class='fa fa-underline'></i></a>
                <a onclick="execCmd('strikeThrough')"><i class='fa fa-strikethrough'></i></a>
                <a onclick="execCmd('justifyLeft')"><i class='fa fa-align-left'></i></a>
                <a onclick="execCmd('justifyCenter')"><i class='fa fa-align-center'></i></a>
                <a onclick="execCmd('justifyRight')"><i class='fa fa-align-right'></i></a>
                <a onclick="execCmd('justifyFull')"><i class='fa fa-align-justify'></i></a>
                <a onclick="execCmd('cut')"><i class='fa fa-cut'></i></a>
                <a onclick="execCmd('copy')"><i class='fa fa-copy'></i></a>
                <a onclick="execCmd('indent')"><i class='fa fa-indent'></i></a>
                <a onclick="execCmd('outdent')"><i class='fa fa-outdent'></i></a>
                <a onclick="execCmd('subscript')"><i class='fa fa-subscript'></i></a>
                <a onclick="execCmd('superscript')"><i class='fa fa-superscript'></i></a>
                <a onclick="execCmd('undo')"><i class='fa fa-undo'></i></a>
                <a onclick="execCmd('redo')"><i class='fa fa-repeat'></i></a>
                <a onclick="execCmd('insertUnorderedList')"><i class='fa fa-list-ul'></i></a>
                <a onclick="execCmd('insertOrderedList')"><i class='fa fa-list-ol'></i></a>
                <a onclick="execCmd('insertParagraph')"><i class='fa fa-paragraph'></i></a>
                <select class="ssel sselArt"
                  style="width: 85px; margin-top: 5px; background-color: #fff;"
                  onchange="execCmdWithArg('formatBlock', this.value)" class="font_all">
                  <option value="" selected="true" disabled="true">Heading</option>
                  <option value="H1">H1</option>
                  <option value="H2">H2</option>
                  <option value="H3">H3</option>
                  <option value="H4">H4</option>
                  <option value="H5">H5</option>
                  <option value="H6">H6</option>
                </select>
                <a onclick="execCmd('insertHorizontalRule')">HR</a>
                <a onclick="execCmd('createLink', prompt('Enter URL', 'https://'))">
                  <i class='fa fa-link'></i>
                </a>
                <a onclick="execCmd('unlink')"><i class='fa fa-unlink'></i></a>
                <a onclick="toggleSource()"><i class='fa fa-code'></i></a>
                <a onclick="toggleEdit()"><i class="fas fa-edit"></i></a>
                <select class="ssel sselArt"
                  style="width: 85px; margin-top: 5px; background-color: #fff;"
                  onchange="execCmdWithArg('fontName', this.value)" id="font_name">
                  <option value="" selected="true" disabled="true">Font style</option>
                  <option value="Arial">Arial</option>
                  <option value="Comic Sans MS">Comic Sans MS</option>
                  <option value="Courier">Courier</option>
                  <option value="Georgia">Georgia</option>
                  <option value="Helvetica">Helvetica</option>
                  <option value="Thaoma">Thaoma</option>
                  <option value="Palatino Linotype">Palatino Linotype</option>
                  <option value="Arial Black">Arial Black</option>
                  <option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
                  <option value="Trebuchet MS">Trebuchet MS</option>
                  <option value="Courier New">Courier New</option>
                  <option value="Lucida Console">Lucida Console</option>
                  <option value="Times New Roman">Times New Roman</option>
                </select>
                <select class="ssel sselArt"
                  style="width: 85px; margin-top: 5px; background-color: #fff;"
                  onchange="execCmdWithArg('formatSize', this.value)" class="font_all">
                  <option value="" selected="true" disabled="true">Font size</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  <option value="6">6</option>
                  <option value="7">7</option>
                </select>
                <span>
                  Fore Color:
                  <input type="color" onchange="execCmdWithArg('foreColor', this.value)"
                    style="vertical-align: middle; margin-top: -4px;">
                </span>
                <span>
                  Background Color:
                  <input type="color" onchange="execCmdWithArg('hiliteColor', this.value)"
                    style="vertical-align: middle; margin-top: -4px;">
                </span>
                <a onclick="execCmd('selectAll')"><i class="fa fa-reply-all"></i></a>
              </div>
                <textarea style="display:none;" name="myTextArea" id="myTextArea"
                  cols="100" rows="14"></textarea>
                <iframe name="richTextField" id="richTextField"></iframe>
                <div id="art_sup_holder">
                <div id="ifmobalign">
                  <input type="text" id="keywords" maxlenght="150"
                    placeholder="Give comma separated tags for your article"
                    style="width: 100%; background-color: #fff;" class="pmInput">
                    <select id="art_cat" class="ssel"
                      style="width: 100%; background-color: #fff;">
                      <option value="" selected="true" disabled="true">Choose category</option>
                      <option value="School">School</option>
                      <option value="Business">Business</option>
                      <option value="Learning">Learning</option>
                      <option value="My Dreams">My Dreams</option>
                      <option value="Money">Money</option>
                      <option value="Sports">Sports</option>
                      <option value="Technology">Technology</option>
                      <option value="Video Games">Video Games</option>
                      <option value="TV programmes">TV programmes</option>
                      <option value="Hobbies">Hobbies</option>
                      <option value="Music">Music</option>
                      <option value="Freetime">Freetime</option>
                      <option value="Travelling">Travelling</option>
                      <option value="Books">Books</option>
                      <option value="Politics">Politics</option>
                      <option value="Movies">Movies</option>
                      <option value="Lifestyle">Lifestyle</option>
                      <option value="Food">Food</option>
                      <option value="Knowledge">Knowledge</option>
                      <option value="Language">Language</option>
                      <option value="Experiences">Experiences</option>
                      <option value="Love">Love</option>
                      <option value="Recipes">Recipes</option>
                      <option value="Personal Stories">Personal Stories</option>
                      <option value="Product Review">Product Review</option>
                      <option value="History">History</option>
                      <option value="Religion">Religion</option>
                      <option value="Entertainment">Entertainment</option>
                      <option value="News">News</option>
                      <option value="Animals">Animals</option>
                      <option value="Environment">Environment</option>
                      <option value="Issues">Issues</option>
                      <option value="The Future">The Future</option>
                    </select>
                    <br /><br />
                    <div class="noMarg">
                      Pick up to 5 images that will appear in your article (optional):
                    </div>

                    <div id="au1" style="border-radius: 20px;">
                      <img src="/images/addimg.png"
                        onclick="triggerUpload(event, 'art_upload1')"
                        class="triggerBtnreply mob_square" />
                    </div>
                    <span id="aimage1"></span>
                    <input type="file" name="file_array" id="art_upload1"
                      onchange="doUploadGen('art_upload1', 'au1', '1')"
                      accept="image/*" style="display: none;" />
                    <div id="au2" style="border-radius: 20px;">
                      <img src="/images/addimg.png"
                        onclick="triggerUpload(event, 'art_upload2')"
                        class="triggerBtnreply mob_square" />
                    </div>
                    <span id="aimage2"></span>
                    <input type="file" name="file_array" id="art_upload2"
                      onchange="doUploadGen('art_upload2', 'au2', '2')" accept="image/*"
                      style="display: none;" />
                    <div id="au3" style="border-radius: 20px;">
                      <img src="/images/addimg.png"
                        onclick="triggerUpload(event, 'art_upload3')"
                        class="triggerBtnreply mob_square" />
                    </div>
                    <span id="aimage3"></span>
                    <input type="file" name="file_array" id="art_upload3"
                      onchange="doUploadGen('art_upload3', 'au3', '3')" accept="image/*"
                      style="display: none;" />
                    <div id="au4" style="border-radius: 20px;">
                      <img src="/images/addimg.png"
                        onclick="triggerUpload(event, 'art_upload4')"
                        class="triggerBtnreply mob_square" />
                    </div>
                    <span id="aimage4"></span>
                    <input type="file" name="file_array" id="art_upload4"
                      onchange="doUploadGen('art_upload4', 'au4', '4')" accept="image/*"
                      style="display: none;" />
                    <div id="au5" style="border-radius: 20px;">
                      <img src="/images/addimg.png"
                        onclick="triggerUpload(event, 'art_upload5')"
                        class="triggerBtnreply mob_square" />
                    </div>
                    <span id="aimage5"></span>
                    <input type="file" name="file_array" id="art_upload5"
                      onchange="doUploadGen('art_upload5', 'au5', '5')" accept="image/*"
                      style="display: none;" />
                    <div class="clear"></div>
                    <br>
                    <div class="art_yel_help">
                      <b id="guideArt">
                        <p style="margin-top: 0px;" class="noMarg">
                          How to write a proper and well-received article?
                        </p>
                      </b>
                      <div class="fhArt" id="guideArtDD">
                        <p>
                          In order to write a good article you have to keep in mind the
                          following things and instructions:
                        </p>
                        <br>
                        <p>
                          1. Once you have choosed a topic do a research of that to get a clear
                          picture and enough knowledge
                        </p>
                        <p>
                          2. Create a strong, unique title that will describe your article in a
                          few words and will grab the readers&#39; attention
                        </p>
                        <p>
                          3. Divide your article into more (at least 3) paragraphs:
                          <i>introducion</i>, <i>main part</i>, <i>conclusion</i>
                        </p>
                        <p>4. Write major points</p>
                        <p>5. Write your article first and edit it later</p>
                        <b><p>Structure of a well-written formal article</p></b>
                        <p>The <i>introducion:</i></p>
                        <p style="margin: 0px;">
                          it is one of the most essential part of the article - grab the
                          attention of your readers, hook them in.
                        </p>
                        <p style="font-size: 12px !important; margin-left: 20px;">
                          Use drama, emotion, quotations, rhetorical questions, descriptions,
                          allusions, alliterations and methapors.
                        </p>
                        <br>
                        <p>The <i>main part(s):</i></p>
                        <p>
                          this part of the article needs to stick to the ideas or answer any
                          questions raised in the intoducion
                        </p>
                        <p style="font-size: 12px !important; margin-left: 20px;">
                          Try to maintain an "atmosphere" / tone / distinctive voice throughout
                          the writing.
                        </p>
                        <br>
                        <p>The <i>conclusion:</i></p>
                        <p>
                          it is should be written to help the reader remember the article. Use
                          a strong punch-line.
                        </p>
                      </div>
                    </div>
                    <div class="art_yel_help">
                      <b id="whatAre">
                        <p style="margin-top: 0px;" class="noMarg">
                          What are tags, categories and attachable images?
                        </p>
                      </b>
                      <div class="fhArt" id="wharAreDD">
                        <p>
                          In the interest of creating a unique and "colorful" article you need
                          to give tags and choose a category for it.
                        </p>
                        <br>
                        <b><p style="margin-top: 0px;">Tags:</p></b>
                        <p>
                          Tags are short words that describes your article in a fast way.
                          People just read them through and they will immediately know what is
                          it about. For instance if you have an article about computers your
                          tags can be <i>technology, computers, #nerd, motherboard</i> etc.
                        </p>
                        <br>
                        <b><p style="margin-top: 0px;">Category:</p></b>
                        <p>
                          The category is just a simple classification that your article has.
                          It tells the readers what is your article about and it will also
                          appear in a picture.
                        </p>
                        <br>
                        <b><p style="margin-top: 0px;">Attachable images:</p></b>
                        <p>
                          You can attach up to 5 images to your article in order to make it
                          more visually, helpful and picturesque. It is an optional avalibility
                          but it is highly recommended to attach at least one picture to your
                          article. If you do not attach any images nothing will appear instead
                          of this. <br><b>Important: </b>the rules are the same as with the
                          standard image uploading. The maximum image size is 5MB and the
                          allowed image extenstions are jpg, jpeg, gif and png. For more
                          information please visit the <a href="/help">help</a> page.
                        </p>
                      </div>
                    </div>
                    <p style="color: #999;" class="admitP">
                      I admit that my article will be public, everyone can read it in order to
                      get new information or for entertainment purposes
                    </p>
                    <button id="article_btn" class="main_btn_fill fixRed"
                      onclick="saveArticle()" style="margin-bottom: 10px;">
                      Create Article
                    </button>
                    <span id="status_art"></span>
                    <hr class="dim hideForm">
                  </div>
                <?php if($wart != ""){ ?>
                  </div>
                <?php } ?>
                <div id="img_holder_a">
                  <br>
                  <div>
                    <div style="margin-bottom: 10px;" class="artImgs">
                      <p>
                        Do research and a plan for your article
                        (<a href="http://www.e-custompapers.com/blog/practical-tips-for-
                          article-reviews.html" target="_blank">source</a>)
                      </p>
                      <img src="/images/howtoart.jpg" onclick="articleGuide('howtoart.jpg')"
                        style="border-radius: 20px; box-sizing: border-box;">
                    </div>
                    <div class="artImgs">
                      <p>
                        The parts of a well-written article
                        (<a href="https://apessay.com/order/?rid=ea55690ca8f7b080"
                        target="_blank">source</a>)
                      </p>
                      <img src="/images/partsa.jpg" onclick="articleGuide('partsa.jpg')"
                        style="border-radius: 20px; box-sizing: border-box;">
                    </div>
                  </div>
                </div>
              </div>
              <div class="clear"></div>
            </form>
            <div id="article_show">
              <div id="friendsAbout" class="infoHolder">

              <div id="data_holder">
                <div style="padding-top: 0;">
                  <div><span><?php echo $friend_count; ?></span> friends</div>
                  <div><span><?php echo $online_count; ?></span> online</div>
                </div>
              </div>

              <div class="contactHolder">
                <?php if($u != $log_username){ ?>
                  <span id="friendBtn"><?php echo $friend_button; ?></span>
                  <span id="blockBtn"><?php echo $block_button; ?></span>
                <?php } ?>

                <?php if($u == $log_username && $user_ok == true){ ?>
                  <button class="main_btn_fill fixRed"
                    onclick="location.href = '/friend_suggestions'">More friends</button>
                  <button class="main_btn_fill fixRed" onclick="location.href = '/invite'">
                    Invite friends
                  </button>
                <?php } ?>
                    
                <button class="main_btn_fill fixRed"
                  onclick="location.href = '/view_friends/<?php echo $u; ?>'">
                  View all friends
                </button>
              </div>
              <hr class="dim">
              <?php if($isFriend == true && $log_username != $u){ ?>
                <p style="color: #999;" class="txtc">You are friends with <?php echo $u; ?></p>
              <?php } ?>
              <?php if($log_username != $u){ ?>
                <p style="color: #999;" class="txtc">
                  You have <?php echo $resincomm; ?> friend(s) in common with
                  <?php echo $u; ?>
                </p>
              <?php } ?>
              <?php echo $friends_view_all_link; ?>
            
              <?php
                if($isOwner == "Yes"){
                  echo '<p style="color: #999;" class="txtc">My friends</p>';
                }else{
                  echo '<p style="color: #999;" class="txtc">'.$u.'&#39s friends</p>';
                }
              ?>
              <div class="flexibleSol">
                <?php echo $friendsHTML; ?>
              </div>
            </div>
            <div id="photosAbout" class="infoHolder">

            <div id="data_holder">
              <div style="padding-top: 0;">
                <div><span><?php echo $count_all; ?></span> photos</div>
              </div>
            </div>

            <div class="contactHolder">
              <button class="main_btn_fill fixRed"
                onclick="window.location = '/photos/<?php echo $u; ?>'">
                View Photos
              </button>
            </div>
            <br>
            <?php 
              if($isOwner == "Yes"){
                echo '
                  <div class="pplongbtn profDDs" id="imgdd">
                    Information about uploading photos
                    <img src="/images/down-arrow.png" width="16" height="16"
                      style="float: right;">
                  </div>
                  <div style="display: none;" id="artdd_div">
                    &bull; You can upload photos by clicking on the <i>See My Photos</i>
                    button in the dropdown menu. Keep in mind that a photo maximum can be 5MB
                    and the website only supports jpg, jpeg, png and gif extensions. You can
                    also give a short description up to 1,000 characters where you can write
                    some important, exciting and/or essential information about the certain
                    photo. Once if you uploaded your photo you can check it that it was
                    uploaded to the right gallery - if not, reupload it to the right one.
                    <br />
                    &bull; If you click on a photo you can see that in a <i>bigger view</i>
                    with more detailed information about it - like description, upload date
                    etc. There you can also check your related videos wich is based on your
                    friends suggestions, random photos and on those ones that has any
                    connections with you and/or your photos. Down below you can write a post,
                    comment, send emojis and attach images, too. We want you to behave as a
                    civilized person and please do not post any harmful or spam messages.
                    <br />
                    &bull; If you want to use someone else&#39;s photo for anything you have
                    to get an agreement from the owner of the photo. Without it you might
                    break some laws and harm someone&#39;s photo privacy!
                  </div>
                  <br>
                ';
              }
            ?>

            <div class="flexibleSol"><?php echo $echo_photos; ?></div>
              <?php if($echo_photos == ""){ ?>
                <p style="color: #999;" class="txtc">
                  It seems that there are no uploaded photos found
                </p>
              <?php } ?>
              <div class="clear"></div>
            </div>
            <div class="clear"></div>

            <div id="articlesAbout" class="infoHolder">
              <div id="data_holder">
                <div style="padding-top: 0;">
                  <div><span><?php echo $count_arts; ?></span> articles</div>
                  <div><span><?php echo $count_likes; ?></span> likes</div>
                  <div><span><?php echo $count_favs; ?></span> favourites</div>
                </div>
              </div>

              <div class="contactHolder">
                <?php
                  if($isOwner == "Yes"){
                    echo $article;
                  }
                ?>
                <button class="main_btn_fill fixRed"
                  onclick="location.href = '/all_articles/<?php echo $u; ?>'">
                  View articles
                </button>
              </div>
              <br>
              <?php if($isOwner == "Yes"){
                echo "
                  <div class='pplongbtn profDDs' id='artdd'>
                    How can I write an article?
                    <img src='/images/down-arrow.png' width='16' height='16'
                      style='float: right;'></div>
                      <div style='display: none;' id='artdd_div'>
                        &bull; When you give a title for your article try to be specific and
                        clean.
                        <br />
                        &bull; If you write an article you are able to edit it as in a text
                        editor where you can attach images, give custom font and text style
                        etc. Despite of the fact that is quite good try NOT to use too much
                        of these features, because this might make your article unreadable!
                        (Examples for text editing: <b>Bold text</b>, <i>Italic text</i>,
                        <u>Underlined text</u>, attach images, change font style etc.)
                        <br />
                        &bull; You completely have the freedom to tell your own opinion,
                        share your ideas and debate with others in a <b>civilized</b> way.
                        We want you to not send harmful messages and/or spam!<br />&bull;
                        You are also able to like articles with the <i>heart</i> icon or add
                        an article as your <i>favourite</i>. By liking or add an article as a
                        favourite you agree that we can send notifications for your friends to
                        keep up to date with you and to show them your interests.<br />&bull;
                        When you edit your own articles - because you can edit your owns -
                        you have a full control over it, therefore you can rewrite some part of
                        the article it that is not actual any more , attach new images and give
                        a new title or just correct the existing one. Nonetheless, you cannot
                        edit the tags and the category or delete the existing images. If you
                        want to change tons of things on your article do NOT do it! Write a new
                        one instead!<br>&bull; Be careful by deleting your articles. Once if
                        you deleted it&#39;s gone and we will be able to bring back it
                        again!<br />&bull; You can also print your articles by clicking on the
                        <i>Print article</i> button at the bottom. These printed articles can
                        freely used to read or learn from it but selling of these can may be
                        illegal without the author&#39;s agreement! If you use these anywhere
                        please link the source and the author&#39;s name.<br />&bull; The
                        <i>Related articles</i> based on your friends recently written or on
                        those articles that has a connection with yours - it can be the same
                        tags, title, similar writing style or the topic you wrote
                        about.
                      </div>
                    <br>
                  ";
                }
              ?>
              <div class="flexibleSol" id="userFlexArts">
                <?php echo $echo_articles; ?>
              </div>
              <?php if($echo_articles == ""){ ?>
                <p class="txtc" style="color: #999;">
                  It seems that there are no articles written
                </p>
              <?php } ?>
              <div class="clear"></div>
            </div>
            <div id="videosAbout" class="infoHolder">
              <div id="data_holder">
                <div style="padding-top: 0;">
                  <div><span><?php echo $count_vids; ?></span> videos</div>
                  <div><span><?php echo $count_vid_likes; ?></span> likes</div>
                </div>
              </div>

            <div class="contactHolder">
              <button class="main_btn_fill fixRed" onclick="location.href = '/videos/<?php echo $u; ?>'">View videos</button>
            </div>
            <br>
            <?php if($isOwner == "Yes"){
              echo "
                <div class='pplongbtn profDDs' id='vhelp'>
                  Information for uploading videos
                  <img src='/images/down-arrow.png' width='16' height='16'
                    style='float: right;'>
                </div>
                <div style='display: none;' id='artdd_div'>
                  &bull; You can upload a video by clicking on the <i>See My Videos</i>
                  link in the dropdown menu. Before you upload a video you can give a name,
                  a description and a poster that will be the background for your video (if you
                  upload an auido file like MP3 this image will be seeable in all the video).
                  That was mentioned here - name, description, poster - is not a requirement,
                  it&#39;s optional.<br />&bull; The maxmimum file size that you can upload as
                  a video is 50MB, the file extensions that are supported: mp3, mp4, webm and
                  ogg. For the poster it is the same as for the photos: maximum file size: 5MB,
                  and the supported types are jpg, jpeg, png, and gif. The maximum length of
                  video description is 1,000 characters and 150 for the name.<br />&bull; We
                  also collected <i>Related videos</i> for you that is based on your
                  friends&#39; videos or if you do not have any we display videos that somehow
                  can be connected to you.<br />&bull; You can also comment, post share images
                  and send emojis below your friends&#39; videos in the comment section. Please
                  be faithful to others and do not spam anything there.<br />&bull; If you need
                  any help have a look at our <a href='/help'>help</a> page or ask a question.
                </div>
                <br />
              ";
            } ?>

            <div class="flexibleSol" id="userFlexArts"><?php echo $videos; ?></div>
            <div class="clear"></div>
          </div>
          <div id="flsAbout" class="infoHolder">
          <div id="data_holder">
            <div style="padding-top: 0;">
              <div><span><?php echo $follower_count; ?></span> followers</div>
              <div><span><?php echo $following_count; ?></span> followings</div>
            </div>
          </div>

          <div class="contactHolder">
            <?php if($log_username != $u){ echo $isFollowOrNot; }?>
              <?php if($isOwner == "No" && $log_username != ""){ ?>
                <span id="followBtn"><?php echo $follow_button; ?></span>
            <?php } ?>
          </div>

          <div id="follow_count">
            <p style="color: #999;" class="txtc">Followers</p>
            <div class="flexibleSol"><?php echo $following_div; ?></div>
            <?php if($following_div == "" && $isOwner == "Yes"){ ?>
              <p style="color: #999; font-size: 14px;" class="txtc">
                It seems that you have no followers at the moment
              </p>
            <?php }else if($following_div == "" && $isOwner == "No"){ ?>
              <p style="color: #999; font-size: 14px;" class="txtc">
                It seems that <?php echo $u; ?> has no followers at the moment
              </p>
            <?php } ?>
            <hr class="dim">
            <p style="color: #999;" class="txtc">Followings</p>
            <div class="flexibleSol"><?php echo $other_div; ?></div>
          </div>
        </div>
        <?php if($user_ok == true && $isOwner == "Yes"){ ?>
          <div id="bcgAbout" class="infoHolder">
            <div class="pplongbtn profDDs" id="bgdd">
              How to change background?
              <img src="/images/down-arrow.png" width="16" height="16" style="float: right;">
            </div>
            <div style="display: none;" id="artdd_div">
              <p style="font-size: 14px; margin: 0px;">
                &bull; Your background will function like a cover image on your profile that
                everyone can see when they go to your profile. The maximum file size that you
                can upload is 5Mb. If your image is larger than this and if it has a png format
                please try to convert it into jpg or jpeg which reserves less space and memory
                (in order to avoid any misunderstands you can still upload png formats it is
                only our request)<br />&bull; The optimal image size for the background is 1200
                x 300 pixels otherwise, it will be automaticly resized. The 1200 x 350 image
                resolution is quite wide and narrow - it is not the typical image format - but
                we try to resize your image and bring out of the best from it. There also can
                be problems width the pixel size of the image. If it&#39;s too small - to fill
                out the 1200 x 300 resolution - we overstate your image which may occur that
                the pixels will be more visible and it might make the image&#39;s quality
                worse. On the other hand, if your image is too small we will try to reduce the
                size - which means that we crop out or try to reduce the size in proportion -
                and it can also make worse the resolution. If you feel that your image
                doesn&#39;t look nice and great you can choose from the 9 built-in background
                which are completlety different from each other and perfectly sized to the
                background image box.<br />&bull; After these if you couldn&#39;t upload your
                background feel free to visit our <a href="/help">help</a> page or ask a
                question.
              </p>
            </div>
            <br>
            <div class="contactHolder">
              <?php echo $background_form; ?>
            </div>
            <hr class="dim">
            <p style="color: #999;" class="txtc" id="builtInBg" onclick="showBiBg()">
              Built-in backgrounds
            </p>
            <div id="statusbig" style="display: none;">
              <div class="bibg genBg lazy-bg" data-src="/images/universebi.jpg"
                onclick="uploadBiBg('universe')">
                <p>Universe</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/flowersbi.jpg"
                onclick="uploadBiBg('flowers')">
                <p>Flowers</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/forestbi.jpg"
                onclick="uploadBiBg('forest')">
                <p>Forest</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/bubblesbi.jpg"
                onclick="uploadBiBg('bubbles')">
                <p>Bubbles</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/mountainsbi.jpg"
                onclick="uploadBiBg('mountains')">
                <p>Mountains</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/wavesbi.jpg"
                onclick="uploadBiBg('waves')">
                <p>Beach</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/stonesbi.jpg"
                onclick="uploadBiBg('stones')">
                <p>Stones</p>
              </div>
              <div class="bibg genBg lazy-bg" data-src="/images/simplebi.jpg"
                onclick="uploadBiBg('simple')">
                <p>Simple Blue</p>
              </div>
            </div>
          <?php } ?>
          <div class="clear"></div>
        </div>
        <div id="grsAbout" class="infoHolder">
          <div id="data_holder">
            <div style="padding-top: 0;">
              <div><span><?php echo $member_count; ?></span> as member</div>
              <div><span><?php echo $creator_count; ?></span> groups created</div>
            </div>
          </div>
    
          <?php if($log_username == $u){ ?>
            <div class="contactHolder">
              <button class="main_btn_fill fixRed"
                onclick="location.href = '/view_all_groups'">View groups</button>
            </div>
          <?php } ?>
          <br>
          <div id="userFlexArts" class="flexibleSol">
            <?php echo $echo_groups; ?>
          </div>
          <?php if($echo_groups == '<div class="clear"></div>'){ ?>
            <p class="txtc" style="color: #999;">
              It seems that no groups can be displayed here
            </p>
          <?php } ?>
        </div>
        <div class="clear"></div>
        <?php if($u == $log_username && $user_ok == true){ ?>
          <div id="groupModule"></div>
        <?php } ?>
        <?php if($log_username != "" && $isBlock != true){ ?>
          <?php require_once 'template_pm.php'; ?>
        <?php } ?>
        <hr class="dim">
        <?php if(!$isBlock){ ?>
          <?php require_once 'template_status.php'; ?>
        <?php }else{ ?>
          <p style="color: #006ad8;" class="txtc">
            Alert: this user blocked you, therefore you cannot post on his/her profile!
          </p>
        <?php } ?>
       </div>
     </div>
    </div>
  </div>
  <?php echo $npm; ?>
  <?php echo $wart; ?>
  
  <?php require_once 'template_pageBottom.php'; ?>
</body>
<?php echo $pmw; ?>
</html>
