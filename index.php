<?php
  /*
    TODO: return to index.php when all pages are done and use the functions defined in
    other files
  */

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
    $u = $log_username;

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

    if($friendsCSV != ""){
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE s.author IN ('$friendsCSV') OR ((u.lat BETWEEN ? AND ?) AND
              (u.lon BETWEEN ? AND ?)) AND s.author NOT IN ('$bUsers')
              AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
              ORDER BY s.postdate DESC LIMIT 6";
    }else if($cnt_near > 0){
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE (u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?) AND
              (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
              AND s.author NOT IN ('$bUsers')
              ORDER BY s.postdate DESC LIMIT 6";
    }else{
      $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
              FROM status AS s
              LEFT JOIN users AS u ON u.username = s.author
              WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND
              s.author != ?
              AND s.author NOT IN ('$bUsers')
              GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
    }

    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
      $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
        $log_username);
    }else if($cnt_near > 0){
      $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
        $log_username);
    }else{
      $stmt->bind_param("sssss", $ucountry, $a, $c, $b, $log_username);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $mainStatSQL = $sql;
    $mainStatSQLParams = $params;

    $statuslist = '--start status--';
    require_once 'template_fetch.php';
    $statuslist .= "--end status --<hr class='dim'>";

    $stmt->close();

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
            AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            ORDER BY s.postdate DESC LIMIT 6";
  }else if($cnt_near > 0){
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM photos_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND
            (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            ORDER BY s.postdate DESC LIMIT 6";
  }else{
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM photos_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
   }
  $stmt = $conn->prepare($sql);
  if($friendsCSV != ""){
    $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
      $log_username);
  }else if($cnt_near > 0){
    $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
      $log_username);
  }else{
    $stmt->bind_param("sssss", $ucountry, $a, $c, $b, $log_username);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $statphol = '--start photo stat--';
    require_once 'photo_fetch.php';
    $statphol .= '--end photo stat--';
  } else {
    $statphol = "
      <p>
        Recommended photo posts from your friends & followings
      </p>
      <p style='font-size: 14px;'>
        Your friends have not posted or replied anything recently.
        Check your <a href='/friend_suggestions'>friend suggestions</a> to get new
        friends or encourage them to post & reply more!
      </p>";
  }
  $statphol .= '<hr class="dim">';
  $stmt->close();

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
  }else{
    $sugglist = "
      <p>
        Recommended articles from your friends & followings
      </p>
      <p style='font-size: 14px;'>
        Your friends have not written any articles recently.
        Encourage them to write more, share their knowledge and entertain other people!
      </p>";
  }
  $stmt->close();

  // Get article posts
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
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM article_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon
            BETWEEN ? AND ? 
            AND s.author NOT IN ('$bUsers')
            AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            ORDER BY s.postdate DESC LIMIT 6";
  }else if ($cnt_near > 0){
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM article_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND
            s.author NOT IN ('$bUsers') AND
            (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            ORDER BY s.postdate DESC LIMIT 6";
  }else{
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM article_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
  }
  $stmt = $conn->prepare($sql);
  if($friendsCSV != ""){
    $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
      $log_username);
  }else if($cnt_near > 0){
    $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
      $log_username);
  }else{
    $stmt->bind_param("sssss", $ucountry, $a, $c, $b, $log_username);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $statartl = '--start art stat--';
    require_once 'art_fetch.php';
    $statartl .= '--end art stat--';
  } else {
    $statartl = "
      <p>
        Recommended article posts from your friends & followings
      </p>
      <p style='font-size: 14px;'>
        Your friends have not posted or replied anything recently.
        Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends
        or encourage them to post & reply more!
      </p>
    ";
  }
  $stmt->close();
  $statartl .= '<hr class="dim">';

  // Give friend suggestions
  $moMoFriends = "";
  $their_friends = array();
  $my_friends = array();
  $myf = array();
  $otype = 'all';
  $limit = 'LIMIT 10';
  
  require_once 'friendsugg_fetch.php';

  if($moMoFriends == ""){
    $moMoFriends = "
      <p>
        Friend suggestions & people who may like
      </p>
      <p style='font-size: 14px;'>
        Unfortunately, there are no available friend suggestions. Come back later.
      </p>
    ";
  }

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
  }else{
    $relvids = "
      <p>
        Recommended videos from ".$part."
      </p>
      <p style='font-size: 14px;'>
        Your friends have not uploaded any videos yet.
        Encourage them to upload videos & share their memories by sending them a private
        message!
      </p>
    ";
  }
  $stmt->close();

  // Get video status
  $statvidl.= "";
  if($friendsCSV != ""){
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM video_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ?
            AND ?
            AND s.author NOT IN ('$bUsers')
            AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            ORDER BY s.postdate DESC LIMIT 6";
  }else if($cnt_near > 0){
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM video_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=?
            OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            ORDER BY s.postdate DESC LIMIT 6";
  }else{
    $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
            FROM video_status AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
  }
  $stmt = $conn->prepare($sql);
  if($friendsCSV != ""){
      $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
        $log_username);
  }else if($cnt_near > 0){
      $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,
        $log_username);
  }else{
      $stmt->bind_param("ssss", $ucountry, $a, $c, $b, $log_username);
  }
  $stmt->execute();
  $result = $stmt->get_result();
	if($result->num_rows > 0){
    $statvidl = '---video stat start---';
    require_once 'video_fetch.php';
    $statvidl .= '---video stat end---';
  } else {
    $statvidl = "
      <p>
        Recommended video posts from your friends & followings
      </p>
      <p style='font-size: 14px;'>
        Your friends have not posted or replied anything recently.
        Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends
        or encourage them to post & reply more!
      </p>
    ";
  }
  $statvidl .= '<hr class="dim">';
  $stmt->close();
  
  // Get group posts for news feed
  $mainPosts = "";
  if($friendsCSV != ""){
    $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
            FROM grouppost AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ?
            AND ? AND (s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            ORDER BY s.pdate DESC LIMIT 6";
  }else if($cnt_near > 0){
    $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
            FROM grouppost AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR
            s.type = ?)
            AND s.author NOT IN ('$bUsers')
            AND s.author != ? ORDER BY s.pdate DESC LIMIT 6";
  }else{
    $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
            FROM grouppost AS s
            LEFT JOIN users AS u ON u.username = s.author
            WHERE u.country = ? AND (s.type=? OR s.type = ?) AND s.author != ?
            AND s.author NOT IN ('$bUsers')
            GROUP BY s.author ORDER BY s.pdate DESC LIMIT 6";
  }
  $stmt = $conn->prepare($sql);
  if($friendsCSV != ""){
    $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero, $one,
      $log_username);
  }else if($cnt_near > 0){
    $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero, $one,
      $log_username);
  }else{
    $stmt->bind_param("ssss", $ucountry, $zero, $one, $log_username);
  }

  $stmt->execute();
  $result_new = $stmt->get_result();
  if ($result_new->num_rows > 0){
    $mainPosts = '--start gr post--';  
    $g = $row['gname'];
    require_once 'group_fetch.php';
    $mainPosts .= '--end gr post--';  
  }else{
    $mainPosts = "
      <p>
        Recommended group posts from your friends & followings
      </p>
      <p style='font-size: 14px;'>
        Your friends have not posted or replied anything yet recently.
        Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends
        or encourage them to post & reply more!
      </p>
    ";
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
  <meta name="description" content="Welcome to Pearscom! Sign up or log in - if you already own an account - and upload your photos, videos, write articles, get new friends and message with them. Start exploring Pearscom now!">
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
  <meta name="keywords" content="Pearscom, pearscom, pear, pears, pearscom welcome, connect us, connect the world, pears community, pearscommunity, pearscomm">
  <script type="application/ld+json"> { "@context" : "http://schema.org", "@type" : "Article", "name" : "Pearscom", "author" : { "@type" : "Person", "name" : "Pearscom, Mark Frankli" }, "image" : "https://www.pearscom.com/images/newfav.png", "articleSection" : "Keep contact with your friends", "articleBody" : "Pearscom helps you to keep contant with your friends and in sharing your joys and sorrows with other people", "url" : "http://www.pearscom.com/", "publisher" : { "@type" : "Organization", "name" : "Pearscom" } } </script>
  <style type="text/css">
      @media only screen and (max-width: 768px){
        #logacccooks{
          margin-top: 36px !important;
      }
      }
  </style>

<script src="/js/ajax.js" defer></script>
<script type="text/javascript">
    var ifed = "<?php echo $isfeed; ?>";
    var hasImage = "";

    
    function doUpload_reply(e, o, w) {
        var t = _(e).files[0];
        if ("" == t.name) return !1;
        if ("image/jpeg" != t.type && "image/gif" != t.type && "image/png" != t.type && "image/jpg" != t.type) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        _(w).style.display = "none";
        var l = new FormData;
        l.append("stPic_reply", t);
        var i = new XMLHttpRequest;
        i.upload.addEventListener("progress", progressHandler_reply, !1), i.addEventListener("load", completeHandler_reply, !1), i.addEventListener("error", errorHandler_reply, !1), i.addEventListener("abort", abortHandler_reply, !1), i.open("POST", "/php_parsers/photo_system.php"), i.send(l)
    }

    function progressHandler_reply(e) {
        var o = e.loaded / e.total * 100,
            t = "<p>" + Math.round(o) + "% uploaded please wait ...</p>";
        _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = "<b>Your uploading image status</b><p>" + t + "</p>", document.body.style.overflow = "hidden"
    }
    
    if('serviceWorker' in navigator){
        navigator.serviceWorker.register('/sw.js')
            .then(function(){
                console.log('Worker Registered');
            });
    }

    function completeHandler_reply(o) {
        var t = o.target.responseText.split("|");
        "upload_complete_reply" == t[0] ? (hasImage = t[1], _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="tempUploads/' + t[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("uploadDisplay_SP_reply_" + e).innerHTML = t[0])
    }

    function errorHandler_reply(e) {
        _('overlay').style.opacity = 0.5;
        _('dialogbox').style.display = 'block';
        _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">An unknown error has occured</p><p>Unfortunately an unknown error has occured meanwhile your uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }

    function abortHandler_reply(e) {
        _('overlay').style.opacity = 0.5;
        _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Your image has been aborted</p><p>Unfortunately your image has been aborted meanwhile uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }

    function replyToStatus(e, o, t, l) {
        var i = _(t).value;
        if ("" == i && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var r = "";
        "" != i && (r = i.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == r && "" != hasImage ? (i = "||na||", r = '<img src="permUploads/' + hasImage + '" />') : "" != r && "" != hasImage ? r += '<br /><img src="permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_feed_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var s = ajaxObj("POST", "/php_parsers/status_system.php");
        s.onreadystatechange = function() {
            if (1 == ajaxReturn(s)) {
                var o = s.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    i = i.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_' + l + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_" + l + "','feed', 'feed');\" title=\"Delete Comment\">X</button></span><br />" + r + "</div></div>", _("swithidbr_feed_" + e).innerHTML = '<button id="replyBtn_feed_' + e + '" class="btn_rply" onclick="replyToStatus(\'' + e + "','<?php echo $u; ?>','replytext_feed_" + e + "',this)\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_feed_").style.display = "block", _("btns_SP_reply_feed_" + e).style.display = "none", _("uploadDisplay_SP_reply_feed_" + e).innerHTML = "", _("replytext_feed_" + e).style.height = "40px", _("fu_SP_reply_feed_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, s.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + i + "&image=" + hasImage)
    }

    function deleteReply(e, o, t, l) {
        if (1 != confirm("Are you sure you want to delete this reply?")) return !1;
        var i = t;
        "phot" == t ? t = "photo_status_system" : "art" == t ? (i = "arid", t = "article_status_system") : t = "vid" == t ? "video_status_parser" : "feed" == t ? "status_system" : "group_parser2";
        var r = ajaxObj("POST", "/php_parsers/" + t + ".php");
        r.onreadystatechange = function() {
            1 == ajaxReturn(r) && ("delete_ok" == r.responseText ? _(o).style.display = "none" : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status post deletion. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, r.send("action=delete_reply&replyid=" + e + "&" + i + "=" + l)
    }

    function toggleLike(e, o, t) {
        var l = ajaxObj("POST", "/php_parsers/like_system.php");
        l.onreadystatechange = function() {
            if (1 == ajaxReturn(l))
                if ("like_success" == l.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + o + "','likeBtn_feed_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanf_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == l.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + o + "','likeBtn_feed_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanf_" + o).innerText = --e + " likes"
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, l.send("type=" + e + "&id=" + o)
    }

    function toggleLike_reply(e, o, t) {
        var l = ajaxObj("POST", "/php_parsers/like_reply_system.php");
        l.onreadystatechange = function() {
            if (1 == ajaxReturn(l))
                if ("like_reply_success" == l.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + o + "','likeBtn_reply_feed_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == l.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + o + "','likeBtn_reply_feed_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", _(t).innerHTML = "Try again later"
        }, l.send("type=" + e + "&id=" + o)
    }
    window.onbeforeunload = function() {
        if ("" != hasImage) return "You have not posted your image"
    };
    var us = "less";

    function showReply(e, o, t) {
        "less" == us ? (_("showreply_" + t + "_" + e).innerText = "Hide replies (" + o + ")", _("allrply_" + t + "_" + e).style.display = "block", us = "more") : "more" == us && (_("showreply_" + t + "_" + e).innerText = "Show replies (" + o + ")", _("allrply_" + t + "_" + e).style.display = "none", us = "less")
    }

    function closeDialog() {
        _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto"
    }

    function shareStatus(e) {
        var o = ajaxObj("POST", "/php_parsers/status_system.php");
        o.onreadystatechange = function() {
            1 == ajaxReturn(o) && ("share_ok" == o.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, o.send("action=share&id=" + e)
    }

    function shareStatus_gr(e, o) {
        var t = ajaxObj("POST", "/php_parsers/group_parser2.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share_status&id=" + e + "&group=" + o)
    }
    hasImage = "";

    function openEmojiBox_reply(e, id) {
        var o = _("emojiBox_reply_" + id + "_" + e);
        "block" == o.style.display ? o.style.display = "none" : o.style.display = "block"
    }

    function insertEmoji(e, o) {
        var t = document.getElementById(e);
        if (t) {
            var l = t.scrollTop,
                i = 0,
                r = t.selectionStart || "0" == t.selectionStart ? "ff" : !!document.selection && "ie";
            if ("ie" == r) {
                t.focus();
                var s = document.selection.createRange();
                s.moveStart("character", -t.value.length), i = s.text.length
            } else "ff" == r && (i = t.selectionStart);
            var n = t.value.substring(0, i),
                a = t.value.substring(i, t.value.length);
            if (t.value = n + o + a, i += o.length, "ie" == r) {
                t.focus();
                var p = document.selection.createRange();
                p.moveStart("character", -t.value.length), p.moveStart("character", i), p.moveEnd("character", 0), p.select()
            } else "ff" == r && (t.selectionStart = i, t.selectionEnd = i, t.focus());
            t.scrollTop = l
        }
    }

    function triggerUpload_reply(e, o) {
        e.preventDefault(), _(o).click()
    }

    function replyToStatus_art(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_art_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/article_status_system.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_art_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_art_" + l + "', 'art', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div>", _("swithidbr_art_" + e).innerHTML = '<button id="replyBtn_art_' + e + '" class="btn_rply" onclick="replyToStatus_art(\'' + e + "','<?php echo $u; ?>','replytext_art_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_art_").style.display = "block", _("btns_SP_reply_art_" + e).style.display = "none", _("uploadDisplay_SP_reply_art_" + e).innerHTML = "", _("replytext_art_" + e).style.height = "40px", _("fu_SP_reply_art_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&arid=" + i)
    }

    function replyToStatus_phot(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_phot_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/photo_status_system.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_phot_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_phot_" + l + "', 'phot', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div>", _("swithidbr_phot_" + e).innerHTML = '<button id="replyBtn_phot_' + e + '" class="btn_rply" onclick="replyToStatus_phot(\'' + e + "','<?php echo $u; ?>','replytext_phot_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_phot_").style.display = "block", _("btns_SP_reply_phot_" + e).style.display = "none", _("uploadDisplay_SP_reply_phot_" + e).innerHTML = "", _("replytext_phot_" + e).style.height = "40px", _("fu_SP_reply_phot_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&phot=" + i)
    }

    function replyPost(e, o) {
        var t = "replytext_gr_" + e,
            l = _(t).value;
        if ("" == l && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var i = "";
        "" != l && (i = l.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == i && "" != hasImage ? (l = "||na||", i = '<img src="/permUploads/' + hasImage + '" />') : "" != i && "" != hasImage ? i += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_gr_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var r = ajaxObj("POST", "/php_parsers/group_parser2.php");
        r.onreadystatechange = function() {
            if (1 == ajaxReturn(r)) {
                var l = r.responseText.split("|");
                if ("reply_ok" == l[0]) {
                    var s = l[1];
                    _("status_" + s).innerHTML += '<div id="reply_gr_' + s + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + s + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + s + "','reply_gr_" + s + "','group','" + o + '\');" title="Delete Comment">X</button></span><br />' + i + "</div></div><br /><br />", _("swithidbr_gr_" + e).innerHTML = '<button id="replyBtn_gr_' + e + '" class="btn_rply" onclick="replyPost(\'' + e + "','" + o + "')\">Reply</button>", _(t).value = "", _("replyBtn_gr_" + e).disabled = !1, _(t).value = "", _("triggerBtn_SP_reply_gr_").style.display = "block", _("btns_SP_reply_gr_" + e).style.display = "none", _("replytext_gr_" + e).style.height = "40px", _("fu_SP_reply_gr_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, r.send("action=post_reply&sid=" + e + "&data=" + l + "&g=" + o + "&image=" + hasImage)
    }

    function replyToStatus_vid(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_vid_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/video_status_parser.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_vid_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_vid_" + l + "', 'vid', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div><br /><br /><br />", _("swithidbr_vid_" + e).innerHTML = '<button id="replyBtn_vid_' + e + '" class="btn_rply" onclick="replyToStatus_vid(\'' + e + "','<?php echo $u; ?>','replytext_vid_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_vid_").style.display = "block", _("btns_SP_reply_vid_" + e).style.display = "none", _("uploadDisplay_SP_reply_vid_" + e).innerHTML = "", _("replytext_vid_" + e).style.height = "40px", _("fu_SP_reply_vid_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&vid=" + i)
    }

    function shareStatus_art(e) {
        var o = ajaxObj("POST", "/php_parsers/article_status_system.php");
        o.onreadystatechange = function() {
            1 == ajaxReturn(o) && ("share_ok" == o.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, o.send("action=share&id=" + e)
    }

    function shareStatus_phot(e, o) {
        var t = ajaxObj("POST", "/php_parsers/photo_status_system.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share&id=" + e + "&phot=" + o)
    }

    function shareStatus_vid(e, o) {
        var t = ajaxObj("POST", "/php_parsers/video_status_parser.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share&id=" + e + "&vid=" + o)
    }

    function toggleLike_art(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_art.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'unlike\',\'' + o + "','likeBtn_art" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'like\',\'' + o + "','likeBtn_art" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&arid=" + l)
    }

    function toggleLike_gr(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/gr_like_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'unlike\',\'' + o + "','likeBtn_gr_" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_gr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_gr_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'like\',\'' + o + "','likeBtn_gr_" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_gr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                _("ipan_gr_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&group=" + l)
    }

    function toggleLike_reply_gr(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/gr_like_system_reply.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success_reply" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'unlike\',\'' + o + "','likeBtn_reply_gr_" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_gr_reply_" + o).innerText.replace("(", "")).replace(")", "").replace("likes").replace(" ", "");
                    e = Number(e);
                    _("ipan_gr_reply_" + o).innerText = ++e + " likes";
                } else if ("unlike_success_reply" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'like\',\'' + o + "','likeBtn_reply_gr_" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_gr_reply_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                _("ipan_gr_reply_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&group=" + l)
    }

    function toggleLike_phot(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_photo_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'unlike\',\'' + o + "','likeBtn_phot" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_phot_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_phot_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'like\',\'' + o + "','likeBtn_phot" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_phot_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_phot_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&phot=" + l)
    }

    function toggleLike_vid(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_video.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'unlike\',\'' + o + "','likeBtn_vid" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_vid_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_vid_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'like\',\'' + o + "','likeBtn_vid" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_vid_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_vid_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&vid=" + l)
    }

    function toggleLike_reply_vid(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_video.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'unlike\',\'' + o + "','likeBtn_reply_vid" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_vid_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace("");
                    e = Number(e);
                    _("ipanr_vid_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'like\',\'' + o + "','likeBtn_reply_vid" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_vid_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_vid_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&vid=" + l)
    }

    function toggleLike_reply_art(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_reply_system_art.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_reply_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'unlike\',\'' + o + "','likeBtn_reply_art" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_art_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_art_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'like\',\'' + o + "','likeBtn_reply_art" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>'
                e = (e = (e = _("ipanr_art_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_art_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&arid=" + l)
    }

    function toggleLike_reply_phot(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_reply_photo_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_reply_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'unlike\',\'' + o + "','likeBtn_reply_phot" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_phot_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_phot_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'like\',\'' + o + "','likeBtn_reply_phot" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_phot_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_phot_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&phot=" + l)
    }

    function showBtnDiv_reply(e, o) {
        0 == mobilecheck && (_("replytext_" + o + "_" + e).style.height = "130px"), _("btns_SP_reply_" + o + "_" + e).style.display = "block"
    }
    window.onbeforeunload = function() {
        if ("" != hasImage) return "You have not posted your image"
    };
    var stat = "less";

    function opentext(e, o) {
        "less" == stat ? (_("lessmore_" + o + "_" + e).style.display = "block", _("toggle_" + o + "_" + e).innerText = "See Less", _("hide_" + o + "_" + e).style.display = "none", stat = "more") : "more" == stat && (_("lessmore_" + o + "_" + e).style.display = "none", _("toggle_" + o + "_" + e).innerText = "See More", _("hide_" + o + "_" + e).style.display = "block", stat = "less")
    }
  </script>
</head>
<body style="background-color: #fafafa;">
  <?php require_once 'template_pageTop.php'; ?>
  <?php if($isfeed == false){ ?>
      <div id="pearHolder" class="seekhide"></div>
        <section id="startContent">
            <div>
                <p>Connect us, connect the world</p><br>
                <p>Join to Pearscom now and get a pear.</p><br>
                <button class="main_btn" onclick="location.href='/login'">Log In</button>
                <button class="main_btn main_btn_fill" onclick="location.href='/signup'">Sign Up</button>
                <p class="centerBox">By signing up you agree our <a href="/policies" class="rlink">Privacy and Policy</a>, how we collect and use your data and accept the use of <a href="policies" class="rlink">cookies</a> on the site.</p>
            </div>
        </section>
        <div id="pearHolder" class="hideseek"></div>
        <div id="changingWords"><div class="wordsStyle"><span class="wordsBg">Share your ideas</span></div></div>
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
                <div id="mp"><?php echo $mainPosts; ?></div><hr class="dim">
                <p style="display: none;" id="mr"></p>
            </div>
            <div id="pcload"></div>
        </div>
    <?php } ?>
  <?php if(!$isfeed){ require_once 'template_pageBottom.php'; } ?>

  <script type="text/javascript">
var isf = "<?php echo $isfeed; ?>";

function getCookie(e) {
    for (var t = e + "=", n = decodeURIComponent(document.cookie).split(";"), i = 0; i < n.length; i++) {
        for (var o = n[i];
            " " == o.charAt(0);) o = o.substring(1);
        if (0 == o.indexOf(t)) return o.substring(t.length, o.length)
    }
    return "";
}

function setDark() {
    var e = "thisClassDoesNotExist";
    if (!document.getElementById(e)) {
        var t = document.getElementsByTagName("head")[0],
            n = document.createElement("link");
        n.id = e, n.rel = "stylesheet", n.type = "text/css", n.href = "/style/dark_style.css", n.media = "all", t.appendChild(n)
    }
}
var isdarkm = getCookie("isdark");

if(isf){
    var CheckIfScrollBottom = debouncer(function() {
        if(getDocHeight() < (getScrollXY()[1] + window.innerHeight + 100)) {
           1 == isn && (_("pcload").innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style=\'display: block; margin: 0 auto; margin-top: 5px; margin-bottom: 5px;\'>');
    
                var e = 0;
                if (0 != num) e = 12 * num + 1;
                var t = e + 12,
                    n = new ajaxObj("POST", "test_box.php");
                n.onreadystatechange = function() {
                    1 == ajaxReturn(n) && ("" != n.responseText ? (1 == num && (_("mr").style.display = "block"), _("pcload").innerHTML = "", _("newsfeed").innerHTML += n.responseText) : (_("pcload").innerHTML = '<p style="color: #999; text-align: center;">This is the end of your news feed. Come back later ...</p>', isn = !1))
                }, n.send("limit_min=" + e + "&limit_max=" + t + "&num=" + num), inc++, num++
        }
    },500);
    
    document.addEventListener('scroll',CheckIfScrollBottom);
}

function debouncer(a,b,c){var d;return function(){var e=this,f=arguments,g=function(){d=null,c||a.apply(e,f)},h=c&&!d;clearTimeout(d),d=setTimeout(g,b),h&&a.apply(e,f)}}
function getScrollXY(){var a=0,b=0;return"number"==typeof window.pageYOffset?(b=window.pageYOffset,a=window.pageXOffset):document.body&&(document.body.scrollLeft||document.body.scrollTop)?(b=document.body.scrollTop,a=document.body.scrollLeft):document.documentElement&&(document.documentElement.scrollLeft||document.documentElement.scrollTop)&&(b=document.documentElement.scrollTop,a=document.documentElement.scrollLeft),[a,b]}
function getDocHeight(){var a=document;return Math.max(a.body.scrollHeight,a.documentElement.scrollHeight,a.body.offsetHeight,a.documentElement.offsetHeight,a.body.clientHeight,a.documentElement.clientHeight)}



if (1 == isf) {
    function randtext() {
        for (var e = "", t = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", n = 0; n < 5; n++) e += t.charAt(Math.floor(Math.random() * t.length));
        return e
    }
    
    var inc = 0,
        num = 0,
        isn = !0;
    1 == isn && $(window).scroll(function() {

    }), window.innerWidth > 808 && (_("cp").style.width = "300px", _("cp").style.right = "0px"), 0 == mobilecheck && (_("cp").addEventListener("mouseover", function() {
        _("cp").style.overflowY = "auto", document.body.style.overflowY = "auto"
    }), _("cp").addEventListener("mouseout", function() {
        _("cp").style.overflowY = "hidden", document.body.style.overflowY = "auto"
    }));
    var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName("body")[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight || g.clientHeight;
    _("pageMiddle_index").style.overflow = "auto";
    for (var cut = 0, is = "<?php echo $imgs; ?>", isa = is.split("|"), j = (inc = 0, 0); j < isa.length - 1; j++) {
        ++inc, cut = "" != isa[j] ? 90 : 400;
        var t = _("pcs_" + inc).innerText;
        if (t.length > 90) {
            var xt = t.substr(0, cut);
            _("pcs_" + inc).innerText = xt + " ..."
        }
    }
}

    if(!isf){
        let keepLoop = 0;
        let testArr = ["Upload videos and photos", "Create unique content", "Keep contact with your friends", "Chat with other people", "Write and read articles", "Talk in groups", "Search for people nearby", "Share your ideas"];
        let contDiv = document.getElementById("changingWords");

        setInterval(getWord, 2000);
        function getWord(){
            if(keepLoop >= testArr.length) keepLoop = 0;
            let newDiv = document.createElement("div");
            let newSpan = document.createElement("span");
            newDiv.appendChild(newSpan);
            newSpan.className = "wordsBg";
            newSpan.innerHTML = testArr[keepLoop];
            newDiv.className = "wordsStyle";
            newSpan.id = 'fadeSpan';
            newSpan.style.display = "none";
            contDiv.replaceChild(newDiv, contDiv.childNodes[0]);
            $("#fadeSpan").fadeIn(500)
            keepLoop++;
        }
    }

var mainStatusSQL = `<?php echo $mainStatSQL; ?>`;
    var paramsSQL = `<?php echo $mainStatSQLParams; ?>`;

    function fetchStatus() {
      var s = ajaxObj("POST", "/template_status.php");
        s.onreadystatechange = function() {
            if (ajaxReturn(s)) {
              console.log(s.responseText);
            }
        }
        s.send('main_status=' + mainStatusSQL + '&params=' + paramsSQL);
    }

    fetchStatus();

  </script>
</body>
</html>
