<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/status_common.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  // Initialize any variables that the page might use
  $one = "1";
  $c = "c";
  $a = "a";
  $b = "b";
  $one = "1";
  $max = 14;

  // Make sure the $_GET username is set and sanitize it
  $u = checkU($_GET['u'], $conn);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM friends WHERE user1 = ? OR user2 = ? AND accepted = ?";
  $url_n = "/view_friends/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'sss', $url_n, $u, $u, $one); 
  
  userExists($conn, $u);
  
  // Check to see if the viewer is the account owner
  $isOwner = isOwner($u, $log_username, $user_ok);

  $otype = "sort_4";
  $friendsHTML = '';

  // Count the num of friends the user has
  $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=?
    AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssss", $u, $one, $u, $one);
  $stmt->execute();
  $stmt->bind_result($friend_count);
  $stmt->fetch();
  $stmt->close();
  if($friend_count < 1){
    if($isOwner == "Yes"){
      $friendsHTML = '
        <p style="color: #999;" class="txtc">
          It seems that you have no friends currently.
          Check your <a href="/friend_suggestions">friend suggestions</a> in order to get
          new ones.
        </p>
      ';
    }else{
      $friendsHTML = '<p style="color: #999;" class="txtc">'.$u.' has no friends yet</p>';
    }
  } else {
    if(isset($_GET["otype"]) || $otype != ""){
      if(isset($_GET["otype"])){
        $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
      }

      $all_friends = getUsersFriends($conn, $u, $u);
      $impActive = array_reverse($all_friends);
      $imp = $impActive = join("','",$all_friends);

      $isOnLine = "";

      // Select the proper SQL query in regard of the URL sort param
      if($otype == "sort_0"){
        $isOnLine = "yes";
        $sql = "SELECT * FROM users WHERE username IN('$imp') AND online = ? ORDER BY
          username $limit";
      }else if($otype == "sort_1"){
        $isOnLine = "no";
        $sql = "SELECT * FROM users WHERE username IN('$imp') AND online = ?
          ORDER BY username $limit";
      }else if($otype == "sort_2"){
        $sql = "SELECT DISTINCT u.* FROM users AS u LEFT JOIN friends AS f
          ON u.username = f.user1 WHERE u.username IN('$imp') ORDER BY f.datemade DESC $limit";
      }else if($otype == "sort_3"){
        $sql = "SELECT DISTINCT u.* FROM users AS u LEFT JOIN friends AS f
          ON u.username = f.user1 WHERE u.username IN('$imp') ORDER BY f.datemade ASC $limit";
      }else if($otype == "sort_4"){
        $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY username $limit";
      }else if($otype == "sort_5"){
        $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY username DESC $limit";
      }else if($otype == "sort_6"){
        $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY country $limit";
      }else if($otype == "sort_7"){
        $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY country DESC $limit";
      }

      $stmt = $conn->prepare($sql);
      if($otype == "sort_0" || $otype == "sort_1"){
        $stmt->bind_param("s",$isOnLine);
      }

      $stmt->execute();
      $result3 = $stmt->get_result();
      while($row = $result3->fetch_assoc()) {
        $friend_username = $row["username"];
        $friend_avatar = $row["avatar"];
        $friend_online = $row["online"];
        $friend_country = $row["country"];
        $bday = $row["bday"];
        $color = "";
        
        $friend_pic = avatarImg($friend_username, $friend_avatar);
        
        if($friend_online == "yes"){
          $color = 'color: green;';
        }else{
          $color = 'color: #999;';
        }
        
        $echo_online = '
          <b style="font-weight: normal; '.$color.'">
            online <img src="/images/wgreen.png" class="notfimg" style="margin-bottom: -2px;">
          </b>
        ';
        
        $style = 'background-repeat: no-repeat; background-size: cover;
          background-position: center; display: inline-block; float: left; width: 60px;
          height: 60px; border-radius: 50px; margin-bottom: 0;';

        $age = floor((time() - strtotime($bday)) / 31556926);

        $friendsHTML .= '
          <div>
            <a href="/user/'.$friend_username.'/">
              <div data-src=\''.$friend_pic.'\' class="lazy-bg friendpics"
                style=\''.$style.'\'>
              </div>
            </a>
            <div id="contviewf" style="width: calc(100% - 80px); margin-left: 10px;">
              <p>
                <span>
                  '.$friend_username.'<br />
                </span>
                <span>
                  '.$friend_country.'<br />
                </span>
                '.$age.' years old
              </p>
            </div>
          </div>
        ';
      }
      $stmt->close();

      if(isset($_GET["otype"])){
        echo $friendsHTML;
        exit();
      }
    }
  }
  
  $sql = "SELECT COUNT(id) FROM users WHERE username IN('$imp')";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $stmt->bind_result($countFriends);
  $stmt->fetch();
  $stmt->close();
  
  $yes = "yes";
  $sql = "SELECT COUNT(id) FROM users WHERE username IN('$imp') AND online = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$yes);
  $stmt->execute();
  $stmt->bind_result($countOFriends);
  $stmt->fetch();
  $stmt->close();
  
  $toggle = "no";
  if($u == $log_username){
    $toggle = "yes";
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $u; ?>'s all friends</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <meta name="description" content="Check <?php echo $u; ?>'s all friends.">
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/dialog.js" async></script>
    <script src="/js/specific/filter.js"></script>
    <script src="/js/specific/dd.js"></script>
    <script src="/js/specific/viewf.js"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
    <style type="text/css">
      @media only screen and (max-width: 1000px){ 
        #searchArt{
          width: 90% !important;
        }

        #artSearchBtn{
          width: 10% !important;
        }

        .longSearches{
          width: calc(90% - 15px) !important;
        }

        @media only screen and (max-width: 500px){
          #searchArt {
            width: 85% !important;
          }

          #artSearchBtn {
            width: 15% !important;
          }

          .longSearches{
            width: calc(100% - 30px) !important;
          }
        }
      }
    </style>
    <script type="text/javascript">
      let origin = "<?php echo $toggle; ?>"; 
      const VUNAME = "<?php echo $u; ?>";
      const IMP = "<?php echo $imp; ?>";
    </script>
  </head>
  <body>
    <?php include_once("template_pageTop.php"); ?>
    <div id="pageMiddle_2">
      <div id="artSearch">
        <div id="artSearchInput">
          <input id="searchArt" type="text" autocomplete="off"
            onkeyup="getFriends(this.value)" placeholder="Search among friends">
          <div id="artSearchBtn" onclick="getMyFLArr()">
            <img src="/images/searchnav.png" width="17" height="17">
          </div>
        </div>
        <div class="clear"></div>
      </div>
      <div id="frSearchResult" class="longSearches"></div>
      <div id="data_holder">
        <div>
          <div><span><?php echo $countFriends; ?></span> friends</div>
          <div><span><?php echo $countOFriends; ?></span> online</div>
        </div>
      </div>
      <button id="sort" class="main_btn_fill">Filter Friends</button>
      <div id="sortTypes">
        <div class="gridDiv">
          <p class="mainHeading">Activity</p>
          <div id="sort_0">Online</div>
          <div id="sort_1">Offline</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Relation length</p>
          <div id="sort_2">new friends to oldest</div>
          <div id="sort_3">old friends to newest</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Username</p>
          <div id="sort_4">Alphabetical order</div>
          <div id="sort_5">Reverese alphabetical order</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Country</p>
          <div id="sort_6">Alphabetical order</div>
          <div id="sort_7">Reverse alphabetical order</div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
      <hr class="dim">
      <div id="momofdif" class="flexibleSol">
        <?php echo $friendsHTML; ?>
      </div>
      <div class="clear"></div>
    </div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
      doDD('sort', 'sortTypes');

      const SERVER = "/view_friends.php?u=<?php echo $u; ?>&otype=";

      function successHandler(req) {
        _("momofdif").innerHTML = req.responseText;
        startLazy(true);
      }

      const BOXES = [];
      for(let i = 0; i < 8; i++){
        BOXES.push("sort_" + i);
      }

      for (let box of BOXES) {
        addListener(box, box, 'momofdif', SERVER, successHandler);
      }
        
      changeStyle("sort_4", BOXES);
    </script>
  </body>
</html>
