<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/gr_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/friends_common.php';
  require_once 'php_includes/art_common.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/index_common.php';
  require_once 'php_includes/conn.php';
  require_once 'timeelapsedstring.php';
  require_once 'phpmobc.php';
  require_once 'safe_encrypt.php';
  require_once 'durc.php';
  require_once 'headers.php';
  require_once 'elist.php';
  require_once 'ccov.php';
  require_once 'php_includes/dist.php';

  $isfeed = false;
  $ismobile = mobc();
  $htmlTitle = "Connect us, connect the world";
  if (isset($log_username) && $log_username != "") {
    $isfeed = true;
    $htmlTitle = "Home";
    $isIndex = true;
    $newsfeed = "";
    $limitStat = 'LIMIT 6';
    $u = $log_username;

    $ajaxArray = array();

    // Function for getting the vars posted by the AJAX req
    function checkLimits($conn, $limitStat) {
      if (isset($_POST['limit_min'])) {
        $limit_min = (string) mysqli_real_escape_string($conn, $_POST['limit_min']);
        $limitStat = 'LIMIT ' . $limit_min . ', 6';
        $isAJAX = true;
        return [$limitStat, $isAJAX];
      }
      return [$limitStat, false];
    }

    // Set feedcheck in users table
    updateFeedcheck($conn, $u);

    $one = "1";
    $zero = "0";
    $a = "a";
    $b = "b";
    $c = "c";
    
    // Select blocked users by the viewer
    $blocked_array = getBlockedUsers($conn, $log_username);
    $bUsers = join("','", $blocked_array);

    list($lat, $lon) = getLatLon($conn, $log_username);

    // Max diff is around 50km
    $lat_m2 = $lat - 0.1;
    $lat_p2 = $lat + 0.1;
    $lon_m2 = $lon - 0.1;
    $lon_p2 = $lon + 0.1;

    // Select the member from the users table
    userExists($conn, $u);
     
    $isFriend = true;

    // Start getting data for the news feed
    // Get friends
    $all_friends = getUsersFriends($conn, $u, $u);

    // Select followings
    $afSug = $all_friends;
    $curar = join("','", $all_friends);
    $all_friends = array_merge($all_friends, getFollowers($conn, $curar, $u));
    $friendsCSV = join("','", $all_friends);

    // Count feed elements
    $feedrcnt = feedCount($conn, $friendsCSV);

    // Check if there are users nearby
    $cnt_near = countNearbyUsers($conn, $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);

    // Select user's country
    $ucountry = getUsersCountry($conn, $log_username);

    $val = "";
    $lmit = "";
    $statuslist = "";

    /*
      Select posts from friends and followings + also include nearby users
    */

    // Also call this bunch of code when reached bottom of page; AJAX req
    if (isset($_POST['limit_min']) || $isIndex) {
      $isAJAX = false;
      list($limitStat, $isAJAX) = checkLimits($conn, $limitStat);

      if($friendsCSV != ""){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR ((u.lat BETWEEN ? AND ?) AND
                (u.lon BETWEEN ? AND ?)) AND s.author NOT IN ('$bUsers')
                AND (s.type=? OR s.type = ?) AND s.author != ?
                ORDER BY s.postdate DESC $limitStat";
      }else if($cnt_near > 0){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE (u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?) AND
                (s.type=? OR s.type = ?) AND s.author != ?
                AND s.author NOT IN ('$bUsers')
                ORDER BY s.postdate DESC $limitStat";
      }else{
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.country = ? AND (s.type=? OR s.type = ?) AND
                s.author != ?
                AND s.author NOT IN ('$bUsers')
                GROUP BY s.author ORDER BY s.postdate DESC $limitStat";
      }

      $stmt = $conn->prepare($sql);
      if($friendsCSV != ""){
        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
          $log_username);
      }else if($cnt_near > 0){
        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
          $log_username);
      }else{
        $stmt->bind_param("ssss", $ucountry, $a, $c, $log_username);
      }

      $stmt->execute();
      $result = $stmt->get_result();

      $statuslist = '';
      $sType = 'status';
      require_once 'template_fetch.php';

      if (!empty($statuslist)) {
        $statuslist .= "<hr class='dim'>";
      }

      $stmt->close();
      if ($isAJAX) {
        array_push($ajaxArray, $statuslist);
      }
    }

    // Get photos from friends & nearby users
    $gallery_list = "";
    if (!empty($all_friends)) {
      $sql = "SELECT * FROM photos WHERE user IN ('$friendsCSV')
        AND user NOT IN ('$bUsers')
        ORDER BY uploaddate
        LIMIT 15";
    } else if($cnt_near > 0) {
      $sql = "SELECT u.*, p.* FROM users AS u LEFT JOIN photos AS p ON
        u.username = p.user WHERE (u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?)
        AND p.user NOT IN ('$bUsers')
        AND p.user != ? ORDER BY RAND() LIMIT 15";
    }else{
      $sql = "SELECT p.*, u.country
              FROM photos AS p
              LEFT JOIN users AS u ON u.username = p.user
              AND p.user NOT IN ('$bUsers')
              WHERE u.country = ? ORDER BY p.uploaddate DESC LIMIT 15";
    }

    $stmt = $conn->prepare($sql);
    if ($cnt_near > 0) {
      $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    }else if($cnt_near < 1){
      $stmt->bind_param("s", $ucountry);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $uder = $row["user"];
      $fname = $row["filename"];
      $description = $row["description"];
      $timed = $row["uploaddate"];
      $udp = strftime("%R, %b %d, %Y", strtotime($timed));
      $uds = time_elapsed_string($timed);
      $description = wrapText($description, 16);
     
      $pcurl = '/user/' . $uder . '/' . $fname . '';
      list($width, $height) = getimagesize('user/' . $uder . '/' . $fname . '');
      $gallery_list .= "
        <a href='/photo_zoom/" . urlencode($uder) . "/" . $fname . "'>
          <div class='pccanvas'>
            <div class='lazy-bg' data-src=\"".$pcurl."\">
              <div id='photo_heading' style='width: auto !important; margin-top: 0px;
                position: static;'>" . $width . " x " . $height . "
              </div>
            </div>
          </div>
        </a>
      ";
    }
    $stmt->close();
  }

  // Get photo posts
  if (isset($_POST['limit_min']) || $isIndex) {
    $isAJAX = false;
    list($limitStat, $isAJAX) = checkLimits($conn, $limitStat);

    $sql = "SELECT COUNT(id) FROM photos_status
            WHERE author IN ('$friendsCSV')
            AND author NOT IN ('$bUsers')
            AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($photorcnt);
    $stmt->fetch();
    $stmt->close();
    $statphol = "";
    if($friendsCSV != ""){
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM photos_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon
              BETWEEN ? AND ?
              AND s.author NOT IN ('$bUsers')
              AND (s.type=? OR s.type = ?) AND s.author != ?
              ORDER BY s.postdate DESC $limitStat";
    }else if($cnt_near > 0){
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM photos_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND
              (s.type=? OR s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              ORDER BY s.postdate DESC $limitStat";
    }else{
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM photos_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.country = ? AND (s.type=? OR s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              GROUP BY s.author ORDER BY s.postdate DESC $limitStat";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
      $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
        $log_username);
    }else if($cnt_near > 0){
      $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
        $log_username);
    }else{
      $stmt->bind_param("ssss", $ucountry, $a, $c, $log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $statphol = '';
      $sType = 'photo';
      require_once 'photo_fetch.php';
    }

    if (!empty($statphol)) {
      $statphol .= '<hr class="dim">';
    }
    $stmt->close();

    if ($isAJAX) {
      array_push($ajaxArray, $statphol);
    }
   }

  $imgs = "";
  $inc = 0;

  // Get recent articles from friends
  $mx = 0;
  if ($ismobile == true) {
    $mx = 4;
  } else {
    $mx = 8;
  }
  $sugglist = "";
  if (!empty($all_friends)) {
    $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsCSV') AND written_by
      AND written_by NOT IN ('$bUsers')
      != ? ORDER BY post_time DESC LIMIT $mx";
  } else if ($cnt_near > 0) {
    $sql = "SELECT u.*, a.* FROM users AS u LEFT JOIN articles AS a
      ON u.username = a.written_by WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ?
      AND a.written_by NOT IN ('$bUsers')
      AND a.written_by != ? ORDER BY post_time DESC LIMIT $mx";
  }else{
    $sql = "SELECT a.*, u.country
            FROM articles AS a
            LEFT JOIN users AS u ON u.username = a.written_by
            WHERE u.country = ?
            AND a.written_by NOT IN ('$bUsers')
            GROUP BY a.written_by ORDER BY a.post_time DESC LIMIT $mx";
  }
  $stmt = $conn->prepare($sql);

  if(!empty($all_friends)){
    $stmt->bind_param("s", $u);
  } else if ($cnt_near > 0) {
    $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
  } else {
    $stmt->bind_param("s", $ucountry);
  }
  $stmt->execute();
  $result2 = $stmt->get_result();
  if($result2->num_rows > 0){
    while ($row = $result2->fetch_assoc()) {
      ++$inc;
      $wb = $row["written_by"];
      $tit = stripslashes(cleanStr($row["title"]));
      $content_ma = stripslashes(cleanStr($row["content"]));
      $ntc = 0;
      $tag = $row["tags"];
      $pt_ = $row["post_time"];
      $opt = $pt_;
      $pt = strftime("%b %d, %Y", strtotime($pt_));
      $pt_ = base64url_encode($pt_, $hshkey);
      $wb_ori = urlencode($wb);
      $cat = $row["category"];
      $num = 0;
      if ($ismobile != true) {
          $num = 16;
      } else {
          $num = 10;
      }

      $wb = wrapText($wb, 16);
      $tit = wrapText($tit, $num);
      $tah = wraptext($tag, 14);
      
      $cnt_fav = countFavs($conn, $opt, $log_username);
      $cnt_heart = countHearts($conn, $opt, $log_username);

      $cover = chooseCover($cat);

      $sugglist .= '
        <div class="newsfar">
          <div id="pcbk">
            <b>Title: </b>' . $tit . '
            <br>
            <b>Author: </b>
            <a href="/user/' . $wb . '/">' . $wb . '</a>
            <br>
            <b>Publsihed: </b>' . $pt . '
            <br>
            <b>Category: </b>' . $cat . '
            <br>
            <a href="/articles/' . $pt_ . '/' . $wb_ori . '">
              Read article >>>
            </a>
          </div>

          <div style="float: right;" class="pclti">
            ' . $cover . '
            <img src="/images/star.png" style="width: 18px !important; height: 18px
              !important;">
            <b>' . $cnt_fav . '</b>
            <br>
            <img src="/images/heart.png" style="width: 17px !important; height: 17px
              !important;">
            <b>' . $cnt_heart . '</b>
          </div>

          <div class="clear">
        </div>
        <hr class="dim">
        <div id="pcbkt">
          <div id="pcs_' . $inc . '" class="wrapCont">
            ' . $content_ma . '
          </div>
        </div>
      </div>
      ';
    }
  }
  $stmt->close();

  // Get article posts
  if (isset($_POST['limit_min']) || $isIndex) {
    $isAJAX = false;
    list($limitStat, $isAJAX) = checkLimits($conn, $limitStat);

    $sql = "SELECT COUNT(id)
            FROM article_status
            WHERE author IN ('$friendsCSV')
            AND author NOT IN ('$bUsers')
            AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($artrcnt);
    $stmt->fetch();
    $stmt->close();
    $statartl = "";
    if($friendsCSV != ""){
      $sql = "SELECT s.*, s.artid AS aid, u.avatar, u.online, u.country, u.lat, u.lon
              FROM article_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon
              BETWEEN ? AND ? 
              AND s.author NOT IN ('$bUsers')
              AND (s.type=? OR s.type = ?) AND s.author != ?
              ORDER BY s.postdate DESC $limitStat";
    }else if ($cnt_near > 0){
      $sql = "SELECT s.*, s.artid AS aid, u.avatar, u.online, u.country, u.lat, u.lon
              FROM article_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND
              s.author NOT IN ('$bUsers') AND
              (s.type=? OR s.type = ?) AND s.author != ?
              ORDER BY s.postdate DESC $limitStat";
    }else{
      $sql = "SELECT s.*, s.artid AS aid, u.avatar, u.online, u.country, u.lat, u.lon
              FROM article_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.country = ? AND (s.type=? OR s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              GROUP BY s.author ORDER BY s.postdate DESC $limitStat";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
      $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
        $log_username);
    }else if($cnt_near > 0){
      $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
        $log_username);
    }else{
      $stmt->bind_param("ssss", $ucountry, $a, $c, $log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $statartl = '';
      $sType = 'art';
      require_once 'art_fetch.php';
    }
    $stmt->close();

    if (!empty($statartl)) {
      $statartl .= '<hr class="dim">';
    }

    if ($isAJAX) {
      array_push($ajaxArray, $statartl);
    }
  }

  // Give friend suggestions
  $moMoFriends = "";
  $their_friends = array();
  $my_friends = array();
  $myf = array();
  $otype = 'all';
  $limit = 'LIMIT 3';
  
  require_once 'friendsugg_fetch.php';

  // Get videos for news feed
  $relvids = "";
  if($friendsCSV != ""){
    $sql = "SELECT * FROM videos WHERE user IN('$friendsCSV')
      AND user NOT IN ('$bUsers')
      ORDER BY RAND() LIMIT 12";
  }else if($cnt_near > 0){
    $sql = "SELECT v.* FROM videos AS v LEFT JOIN users AS u ON u.username = v.user WHERE 
      u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ?
      AND v.user NOT IN ('$bUsers')
      ORDER BY v.video_upload DESC LIMIT 12";
  }else{
    $sql = "SELECT v.*, u.country
            FROM videos AS v
            LEFT JOIN users AS u ON u.username = v.user
            WHERE u.country = ?
            AND v.user NOT IN ('$bUsers')
            GROUP BY v.user ORDER BY v.video_upload DESC LIMIT 6";
  }
  $stmt = $conn->prepare($sql);

  if($friendsCSV != ""){
      $stmt->bind_param("ss", $a, $c);
  }else if($cnt_near > 0){
      $stmt->bind_param("ssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2);
  }else{
      $stmt->bind_param("s", $ucountry);
  }

  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()) {
      $vid = $row["id"];
      $vid = base64url_encode($vid, $hshkey);
      $vuser = $row["user"];
      $vvname = $row["video_name"];
      $vdescription = $row["video_description"];
      $vposter = $row["video_poster"];
      $vfile = $row["video_file"];
      $vdate_ = $row["video_upload"];
      $vdate = strftime("%b %d, %Y", strtotime($vdate_));
      $dur = $row["dur"];
      $dur = convDur($dur);
      if ($vvname == NULL) {
        $vvname = "Untitiled";
      }

      $vvname = wrapText($vvname, 18);
      if ($vposter != NULL) {
        $pcurlo = '/user/' . $vuser . '/videos/' . $vposter . '';
      } else {
        $pcurlo = '/images/defaultimage.png';
      }

      $uds = time_elapsed_string($vdate_);
      $relvids .= "
        <a href='/video_zoom/" . $vid . "'>
          <div class='nfrelv'>
            <div data-src=\"".$pcurlo."\" class='lazy-bg' id='pcgetc'></div>
            <div class='pcjti'>" . $vvname . "</div>
            <div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px;
              position: absolute; bottom: 15px;'>" . $dur . "
            </div>
          </div>
        </a>
      ";
    }
  }
  $stmt->close();

  // Get video status
  if (isset($_POST['limit_min']) || $isIndex) {
    $isAJAX = false;
    list($limitStat, $isAJAX) = checkLimits($conn, $limitStat);

    if($friendsCSV != ""){
      $sql = "SELECT s.*, s.vidid AS video_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM video_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ?
              AND ?
              AND s.author NOT IN ('$bUsers')
              AND (s.type=? OR s.type = ?) AND s.author != ?
              ORDER BY s.postdate DESC $limitStat";
    }else if($cnt_near > 0){
      $sql = "SELECT s.*, s.vidid AS video_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM video_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=?
              OR s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              ORDER BY s.postdate DESC $limitStat";
    }else{
      $sql = "SELECT s.*, s.vidid AS video_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM video_status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.country = ? AND (s.type=? OR s.type=?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              GROUP BY s.author ORDER BY s.postdate DESC $limitStat";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
          $log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,
          $log_username);
    }else{
        $stmt->bind_param("sss", $ucountry, $a, $c, $log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      $statvidl = '';
      $sType = 'video';
      require_once 'video_fetch.php';
    }

    if (!empty($statvidl)) {
      $statvidl .= '<hr class="dim">';
    }
    $stmt->close();

    if ($isAJAX) {
      array_push($ajaxArray, $statvidl);
    }
  }
  
  // Get group posts for news feed
  if (isset($_POST['limit_min']) || $isIndex) {
    $isAJAX = false;
    list($limitStat, $isAJAX) = checkLimits($conn, $limitStat);

    if($friendsCSV != ""){
      $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM grouppost AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ?
              AND ? AND (s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              ORDER BY s.pdate DESC $limitStat";
    }else if($cnt_near > 0){
      $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM grouppost AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=?)
              AND s.author NOT IN ('$bUsers')
              AND s.author != ? ORDER BY s.pdate DESC $limitStat";
    }else{
      $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
              FROM grouppost AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.country = ? AND (s.type=?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              GROUP BY s.author ORDER BY s.pdate DESC $limitStat";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
      $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero,
        $log_username);
    }else if($cnt_near > 0){
      $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero,
        $log_username);
    }else{
      $stmt->bind_param("sss", $ucountry, $zero, $log_username);
    }

    $stmt->execute();
    $result_new = $stmt->get_result();
    if ($result_new->num_rows > 0){
      $mainPosts = '';  
      $sType = 'group';
      require_once 'group_fetch.php';
    }

    if (!empty($mainPosts)) {
      $mainPosts .= '<hr class="dim">';  
    }
    $stmt->close();

    if ($isAJAX) {
      array_push($ajaxArray, $mainPosts);
    }
  }

  // If an AJAX req is made to index.php output the posts in a random order
  if ($isAJAX) {
    shuffle($ajaxArray);
    foreach ($ajaxArray as $post) {
      echo $post;
    }
    unset($ajaxArray);
    $ajaxArray = array();
    exit();
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pearscom - <?php echo $htmlTitle; ?></title>
  <meta charset="utf-8">
  <meta lang="en">
  <meta name="robots" content="index, follow">
  <meta name="copyright" content="Pearscom">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="shortcut icon" href="/images/newfav.png" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Unveil your creativity and find new friends.">
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/jjs.js" defer></script>
    <script src="/js/main.js" defer></script>
  <script src="/js/lload.js" defer></script>
  <meta name="author" content="Pearscom">
  <meta name="keywords" content="Pearscom, pearscom, pear, pears, pearscom welcome,
    connect us, connect the world, pears community, pearscommunity, pearscomm">
  <script type="application/ld+json">
    {
      "@context" : "http://schema.org",
      "@type" : "Article",
      "name" : "Pearscom",
      "author" : {
                  "@type" : "Person",
                  "name" : "Pearscom, Mark Frankli"
                  },
      "image" : "https://www.pearscom.com/images/newfav.png",
      "articleSection" : "Keep contact with your friends",
      "articleBody" : `Write articles, upload photos & videos.`,
      "url" : "https://www.pearscom.com/",
      "publisher" : {
                      "@type" : "Organization",
                      "name" : "Pearscom"
                    }
    }
  </script>
  <style type="text/css">
    @media only screen and (max-width: 768px){
      #logacccooks{
        margin-top: 36px !important;
      }
    }
  </style>
  
  <script src="/js/mbc.js"></script>
  <script src="/js/ajax.js" defer></script>
  <script src='/js/specific/p_dialog.js' defer></script>
  <script src='/js/specific/file_dialog.js' defer></script>
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

  </script>
</head>
<body style="background-color: #fafafa;">
  <?php require_once 'template_pageTop.php'; ?>
  <?php if(!$isfeed){ ?>
    <div id="pearHolder" class="seekhide"></div>
      <section id="startContent">
        <div>
          <p>Connect us, connect the world</p><br>
          <p>Join to Pearscom now and get a pear.</p><br>
          <button class="main_btn" onclick="location.href='/login'">Log In</button>
          <button class="main_btn main_btn_fill" onclick="location.href='/signup'">
            Sign Up
          </button>
          <p class="centerBox">
            By signing up you agree our
            <a href="/policies" class="rlink">Privacy and Policy</a>,
            how we collect and use your data and accept the use of
            <a href="policies" class="rlink">cookies</a> on the site.
          </p>
        </div>
      </section>
      <div id="pearHolder" class="hideseek"></div>
      <div id="changingWords"><div class="wordsStyle"><span class="wordsBg">Share your ideas</span></div>
      </div>
      <div class="clear"></div>
    <?php }else{ ?>
      <div id="dialogbox"></div>
      <div id="overlay"></div>
      <div id="pageMiddle_index" style="background-color: transparent;">
        <div id="newsfeed">
          <div id="sli"><?php echo $statuslist; ?></div>
          <div id="gal" class="ppForm"><?php echo $gallery_list; ?></div>
          <div class="clear"></div><hr class="dim">
          <div id="sphl"><?php echo $statphol; ?></div>
          <div id="sgl" class="ppForm"><?php echo $sugglist; ?></div>
          <div class="clear"></div><hr class="dim">
          <div id="astat"><?php echo $statartl; ?></div>
          <div id="sug"><div class="nfmo ppForm"><?php echo $moMoFriends; ?></div></div>
          <span><div class="clear"></div></span><hr class="dim">
          <div id="rel" class="ppForm"><?php echo $relvids; ?></div>
          <div class="clear"></div><hr class="dim">
          <div id="svid"><?php echo $statvidl; ?></div>
          <div id="mp"><?php echo $mainPosts; ?></div>
          <p style="display: none;" id="mr"></p>
        </div>
        <div id="pcload"></div>
      </div>
    <?php } ?>
    <?php if(!$isfeed){
      require_once 'template_pageBottom.php';
    } ?>

  <script type="text/javascript">
    var isf = "<?php echo $isfeed; ?>";
  </script>
  <script src="/js/specific/index.js"></script>
</body>
</html>
