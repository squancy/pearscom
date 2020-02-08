<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/art_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'phpmobc.php';
  require_once 'ccov.php';
  require_once 'headers.php';

  $ismobile = mobc();
  $one = "1";
  if($ismobile != true){
      $max = 12;   
  }else{
      $max = 9;
  }
  $count_it = true;

  // Get username from URL
  $u = checkU($_GET['u'], $conn);
  
  // Check if user is the owner of the page
  $isOwner = isOwner($u, $log_username, $user_ok);

  if ($isOwner == 'Yes') {
    $whoseUname = 'My articles';
  } else {
    $whoseUname = $u . "'s articles";
  }
  $whoseArt = "
    <p style='font-size: 18px; padding-bottom: 0px; text-align: center;'>
      <a href='/all_articles/".$u."'>
        ".$whoseUname."
      </a>
      <img src='/images/myone.png' class='notfimg' style='margin-bottom: -2px;'>
    </p>
  ";

  // Check if user exists id db
  userExists($conn, $u);

  $profile_pic = getUserAvatar($conn, $u);
  
  // Echo articles user's articles
  $echo_articles = "";
  $j = 0;

  function genFullBox($row) {
    global $hshkey;
    $wb = $row["written_by"];
    $tit = stripslashes($row["title"]);
    $tit = str_replace('\'', '&#39;', $tit);
    $tit = str_replace('\'', '&#34;', $tit);
    $tag = $row["tags"];
    $pt_ = $row["post_time"];
    $opt = $pt_;
    $pt = strftime("%b %d, %Y", strtotime($pt_));
    $pt_ = base64url_encode($pt_, $hshkey);
    $wb_ori = urlencode($wb);
    $cat = $row["category"];
    $cover = chooseCover($cat);
  
    if(!function_exists('genArtBox')) {
      function genArtBox($post_time_, $written_by_original, $cover, $written_by, $title,
        $post_time, $tags, $cat) {
        return '
          <a href="/articles/'.$post_time_.'/'.$written_by_original.'">
            <div class="article_echo_2" style="width: 100%;">
              '.$cover.'
              <div>
                <p class="title_">
                  <b>Author: </b>'.$written_by.'
                </p>
                <p class="title_">
                  <b>Title: </b>'.$title.'
                </p>
                <p class="title_">
                  <b>Posted: </b>'.$post_time.'
                </p>
                <div id="tag_wrap">
                  <p class="title_">
                    <b>Tags: </b>'.$tags.'
                  </p>
                </div>
                <p class="title_">
                  <b>Category: </b>'.$cat.'
                </p>
              </div>
            </div>
          </a>
        '; 
      }
    }

    return genArtBox($pt_, $wb_ori, $cover, $wb, $tit, $pt, $tag, $cat);
  }

  $all_my_art = array();
  $sql = "SELECT * FROM articles WHERE written_by=? ORDER BY post_time DESC LIMIT $max";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $echo_articles .= genFullBox($row);
      array_push($all_my_art, $row["id"]);
    }
  }else{
    $count_it = false;
    if($isOwner == "Yes"){
      $echo_articles = "
        <p style='color: #999; text-align: center;'>
          It seems that you have not written any articles so far
        </p>";
    }else{
      $echo_articles = "
        <p style='color: #999; text-align: center;'>
          It seems that ".$u." has not written any articles so far
        </p>";
    }
  }
  $stmt->close();

  // Count the user's all articles and set a view all link
  function cntAllArts($conn, $u) {
    $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($my_all);
    $stmt->fetch();
    $stmt->close();
    return $my_all;
  }

  $my_all = (int) cntAllArts($conn, $u);

  $my_art_arr_count = count($all_my_art);
  if($my_art_arr_count > $max){
    array_splice($all_my_art, $max);
  }

  $showmore = "";
  if($my_all > $max){
    if($isOwner == "Yes"){
      $showmore = '/ <a href="/all_articles/'.$log_username.'">Show my all articles</a>';
    }else{
      $showmore = '/ <a href="/all_articles/'.$u.'">Show '.$u.'&#39;s all articles</a>';
    }
  }

  // Get the page viewer's gender
  class GetGender {
    public static function gender() {
      global $conn, $u;
      $sql = "SELECT gender FROM users WHERE username = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $u);
      $stmt->execute();
      $stmt->bind_result($gender_viewer);
      $stmt->fetch();
      $stmt->close();
      return $gender_viewer;
    }
  }

  $gender_viewer = GetGender::gender(); 

  // Get how many articles the user has written
  $count_art = getNumOfArts($conn, $u);

  // Decide who is viewing the page
  $count_text = "";
  if($count_art == 1){
    $count_text = "<span>".$count_art."</span> article";
  }else if($count_art > 1 || $count_art == 0){
    $count_text = "<span>".$count_art."</span> articles";
  }

  // Get how many likes he/she got
  function getAllAdded($conn, $u, $db) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE art_uname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    return $cnt;
  }

  $like_count = (int) getAllAdded($conn, $u, 'heart_likes');

  // Decide who is viewing the page
  $like_text = "";
  if($like_count == 1){
    $like_text = "<span>".$like_count."</span> like";
  }else if($like_count > 1 || $like_count == 0){
    $like_text = "<span>".$like_count."</span> likes";
  }

  // Get how many times did he/she liked other articles
  function cntOther($conn, $u, $db) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    return $cnt;
  }

  $my_likes = (int) cntOther($conn, $u, 'heart_likes');

  $my_text = "";
  if($my_likes == 1){
    $my_text = "<span>".$my_likes."</span> likes gien";
  }else if($my_likes > 1 || $my_likes == 0){
    $my_text = "<span>".$my_likes."</span> likes given";
  }

  // Get suggested articles
  // First get all friends
  $all_friends = getUsersFriends($conn, $u, $log_username);

  // Suggest articles from friends & exclude the user
  $k = 0;
  $dnsar = array();
  $sugglist = "";
  $friendsGR = join("','", $all_friends);
  $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsGR') AND written_by != ?
    ORDER BY RAND() LIMIT $max";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result2 = $stmt->get_result();
  while($row = $result2->fetch_assoc()){
    array_push($dnsar, $row["id"]);
    $sugglist .= genFullBox($row);
  }

  $stmt->close();
  $dnsars = join("','",$dnsar);

  /*
    If suggested articles < 11 suggest more arts from nearby users
    This time geolocation is used: latitude and longitude
  */

  $l = 0;
  if($k < 11){
    $lmit = 11 - $k;
    list($lat, $lon) = getLatLon($conn, $log_username);

    // 1.4 max difference is 185.3 km
    $lat_m2 = $lat-0.7;
    $lat_p2 = $lat+0.7;

    $lon_m2 = $lon-0.7;
    $lon_p2 = $lon+0.7;
    $sql = "
      SELECT u.lat,u.lon, a.* FROM articles AS a LEFT JOIN users AS u ON
      u.username = a.written_by WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND
      a.written_by != ? AND a.id NOT IN('$dnsars') ORDER BY RAND() LIMIT $lmit";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    $stmt->execute();
    $result2 = $stmt->get_result();
    while($row = $result2->fetch_assoc()){
      $sugglist .= genFullBox($row);
    }
  }
  
  if($sugglist == ""){
    $sugglist = "
      <p style='font-size: 16px; text-align: center; color: #999;'>
        You have no suggested articles at the moment.
        This may due to that you have no friends or they have not written any articles
        so far.
      </p>
    ";
  }

  // Get how many times has the user marked an article as favourite
  $other_fav = (int) cntOther($conn, $u, 'fav_art');

  $other_echo = "";
  if($other_fav == 1){
    $other_echo = "<span>".$other_fav."</span> favourite given";
  }else if($other_fav == 0 || $other_fav > 1){
    $other_echo = "<span>".$other_fav."</span> favourites given";
  }

  // Get how many favourite marks has the user got
  $my_fav = (int) getAllAdded($conn, $u, 'fav_art');

  $my_echo = "";
  if($my_fav == 1){
    $my_echo = "<span>".$my_fav."</span> favourite";
  }else if($my_fav > 1 || $my_fav == 0){
    $my_echo = "<span>".$my_fav."</span> favourites";
  }
  
  // Get today's most liked articles
  $best_arts = "";
  $at_array = array();
  $uname_array = array();
  $sql = "SELECT art_uname, art_time, COUNT(*) AS u 
          FROM heart_likes
          WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)
          GROUP BY art_time
          ORDER BY u DESC
          LIMIT $max";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row = $res->fetch_assoc()){
    $uname = $row["art_uname"];
    $at = $row["art_time"];
    array_push($uname_array, $uname);
    array_push($at_array, $at);
  }
  $stmt->close();

  $uname_string = join("','", $uname_array);
  $at_string = join("','", $at_array);

  // Select the best articles of the day
  $m = 0;
  $sql = "
    SELECT * FROM articles WHERE written_by IN ('$uname_string') AND post_time
    IN ('$at_string')";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row = $res->fetch_assoc()){
    $best_arts .= genFullBox($row);
  }
  $stmt->close();

  // If nothing, run query without time restriction
  if($uname_string == "" && $at_string == ""){
    $uname_array2 = array();
    $at_array2 = array();
    $sql = "SELECT art_uname, art_time, COUNT(*) AS u 
        FROM heart_likes
        GROUP BY art_time
        ORDER BY u DESC
        LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
      $uname = $row["art_uname"];
      $at = $row["art_time"];
      array_push($uname_array2, $uname);
      array_push($at_array2, $at);
    }
    $stmt->close();

    $uname_string2 = join("','", $uname_array2);
    $at_string2 = join("','", $at_array2);

    $n = 0;
    $sql = "
      SELECT * FROM articles WHERE written_by IN ('$uname_string2')
      AND post_time IN ('$at_string2')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
      $best_arts .= genFullBox($row);
    }
    $stmt->close();
  }
  
  // Get the best authors
  $bauthors = array();
  $sql = "SELECT art_uname, COUNT(*) AS u 
          FROM heart_likes
          GROUP BY art_uname
          ORDER BY u DESC
          LIMIT 11";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row = $res->fetch_assoc()){
      $uname = $row["art_uname"];
      array_push($bauthors, $uname);
  }
  $stmt->close();

  $bauthors = array_unique($bauthors);
  $bauthors = join("','",$bauthors);
  $echo_bas = "";
  $sql = "
    SELECT u.*, COUNT(*) AS b FROM users AS u LEFT JOIN fav_art AS f ON
    f.art_uname = u.username WHERE u.username IN('$bauthors') GROUP BY f.art_uname
    ORDER BY b DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row = $res->fetch_assoc()){
    $buname = $row["username"];
    $bavatar = $row["avatar"];
    $bonline = $row["online"];
    
    $cnt_hearts = (int) getAllAdded($conn, $buname, 'heart_likes');
    $cnt_favs = (int) getAllAdded($conn, $buname, 'fav_art');
    $cnt_wbs = (int) cntAllArts($conn, $buname);
    
    $sql = "SELECT category, COUNT(category) AS u 
            FROM articles
            WHERE written_by = ?
            GROUP BY category
            ORDER BY u DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $buname);
    $stmt->execute();
    $stmt->bind_result($favcat, $dnt);
    $stmt->fetch();
    $stmt->close();
    
    if($bavatar == NULL){
        $bavatar = '/images/avdef.png';
    }else{
        $bavatar = '/user/'.$buname.'/'.$bavatar;
    }

    // Count points from likes and favs: likes are 2x weighted
    $apoints = ($cnt_favs * 2 + $cnt_hearts) / $cnt_wbs;
    $apoints = round($apoints, 2);
    $uniid = base64url_encode($buname,$hshkey);
    $echo_bas .= '
      <div class="bauthors">
        <a href="/user/'.$buname.'/">
          <div style="background-image: url(\''.$bavatar.'\'); background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 50px; height: 50px;
            display: inline-block;" class="whee" onmouseover="showBas(\''.$uniid.'\')"
            onmouseleave="hideBas(\''.$uniid.'\')">
          </div>
        </a>

        <span style="margin-left: 5px;">
          <img src="/images/star.png" width="18" height="18">
          <b>'.$cnt_favs.'</b>
          <br>
          <img src="/images/heart.png" width="17" height="17" style="margin-left: 5px;">
            <b>'.$cnt_hearts.'</b>
        </span>

        <div class="infobadiv" id="pc_'.$uniid.'">
          <span style="float: left;">
            <b style="font-size: 12px;">Username: </b>'.$buname.'
            <br>
            <b style="font-size: 12px;">Articles written: </b>'.$cnt_wbs.'
            <br>
            <b style="font-size: 12px;">Likes got: </b>'.$cnt_hearts.'
            <br>
            <b style="font-size: 12px;">Favourites got: </b>'.$cnt_favs.'
            <br>
            <b style="font-size: 12px;">Points got: </b>'.$apoints.'
            <br>
            <b style="font-size: 12px;">Favourite category: </b>'.$favcat.'
          </span>

          <div style="background-image: url(\''.$bavatar.'\'); background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 85px; height: 85px;
            display: inline-block; float: right; border-radius: 50%;">
          </div>
        </div>
      </div>
    ';
  }
  $stmt->close();
?>  
<!DOCTYPE html>
<html>
<head>
  <title>Atricles - <?php echo $u; ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <script src="/js/jjs.js" async></script>
  <script src="/text_editor.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  
  <script type="text/javascript">
    const LOGNAME = '<?php echo $log_username; ?>';
  </script>
  <script src="/js/mbc.js" defer></script>
  <script src="/js/specific/main_search.js" defer></script>
  <style type="text/css">
    @media only screen and (max-width: 1000px){ 
      #searchArt{
        width: 90% !important;
      }

      #artSearchBtn{
        width: 10% !important;
      }

      @media only screen and (max-width: 500px){
        #searchArt {
          width: 85% !important;
        }

        #artSearchBtn {
           width: 15% !important;
        }
      }
    }
  </style>
</head>
<body>
  <?php include_once("template_pageTop.php"); ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off" onkeyup="getArt(this.value)"
          placeholder="Search for articles by their author, title, category or tags"
          class="lsearch">
        <div id="artSearchBtn" onclick="getLSearchArt()">
          <img src="/images/searchnav.png" width="17" height="17">
        </div>
      </div>
      <div class="clear"></div>
    </div>
    <div id="artSearchResults" class="longSearches"></div>
    <div id="data_holder">
      <div>
        <div><?php echo $count_text; ?></div>
        <div><?php echo $like_text; ?></div>
        <div><?php echo $my_text; ?></div>
        <div><?php echo $my_echo; ?></div>
        <div><?php echo $other_echo; ?></div>
      </div>
    </div>

    <button id="writeIt" class="main_btn_fill" onclick="getWA()">Write Article</button>
    <div class="clear"></div>
    <hr class='dim'>
    <div id="centetait">
      <?php echo $whoseArt; ?> 
      <div class="flexibleSol" id="userFlexArts">
        <?php echo $echo_articles; ?>
      </div>
    <div class="clear"></div>

    <?php if($count_it == true){ ?>
      <hr class='dim'>
    <?php } ?>

    <p style="font-size: 18px; text-align: center;">
      <a href='/article_suggestions'>Suggested</a> articles from friends &amp; nearby users
      <img src="/images/morea.png" class="notfimg" style="margin-bottom: -2px;">
    </p>

    <div class="flexibleSol" id="userFlexArts">
      <?php echo $sugglist; ?>
    </div>
    <div class="clear"></div>
    <hr class="dim">

    <div class="clear"></div>

    <p style="font-size: 18px; text-align: center;">
      Today&#39;s most liked &amp; favourite articles
      <img src="/images/likeb.png" class="notfimg" style="margin-bottom: -2px;">
    </p>

    <div class="flexibleSol" id="userFlexArts">
      <?php echo $best_arts; ?>
    </div>

    <?php if($best_arts == ""){ ?>
      <p style="color: #999; text-align: center;">
        It seems that there are no articles fitting the requirements
      </p>
    <?php } ?>
  </div>
  <div class="clear"></div>
  <hr class="dim">
  <p style="font-size: 18px; text-align: center;">
    Best authors of all time
    <img src="/images/bestas.png" class="notfimg" style="margin-bottom: -2px;">
  </p>
  <div class="flexibleSol" id="userFlexArts">
    <?php echo $echo_bas; ?>
  </div>
  <div class="clear"></div>
  <br><br>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  </body>
</html>
