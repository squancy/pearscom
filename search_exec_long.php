<?php
  require_once 'timeelapsedstring.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/pagination.php';
  require_once 'search_exec_common.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/mtime.php';
  require_once 'php_includes/wrapText.php';

  function genUserRow($row) {
    global $count;
    $uname = $row["username"];
    $country = $row["country"];
    $avatar = $row["avatar"];
    $isonline = $row["online"];
    $bday = $row["bday"];
    $signupdate = $row["signup"];
    $uname_original = $uname;
    $mfor = time_elapsed_string($signupdate);
    $age = floor((time() - strtotime($bday)) / 31556926);

    $uname = wrapText($uname, 36);

    if($isonline == "yes"){
      $isonline = "border: 2px solid rgb(0, 161, 255);";
    }else{
      $isonline = "border: 2px solid grey;";
    }

    $pcurl = avatarImg($uname, $avatar);

    return '
      <a href="/user/'.$uname.'/">
        <div class="lazy-bg genBg sepDivs" data-src=\''.$pcurl.'\'
          style="width: 50px; height: 50px; border-radius: 50%; float: left;
          margin-right: 5px; '.$isonline.'"></div>
      </a>
      <div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;"
        id="sLong">
        <p><a href="/user/'.$uname.'/">'.$uname.'</a></p>
        <p>'.$country.'</p>
        <p>'.$age.' years old</p>
        <p>Member for '.$mfor.'</p>
      </div>
      <div class="clear"></div>
      <hr class="dim">
    ';
    $count++;
  }

  $count = 0;

  // If user is not logged in no search is allowed
  if(!isset($_SESSION["username"]) || !$_SESSION["username"]){
    $output = '
      <p style="font-size: 14px; margin: 0px;">
        You are not logged in therefore you cannot search!
      </p>
    ';
    exit();
  }

  // AJAX calls this code
  if(isset($_GET['search'])){
    $u = mysqli_real_escape_string($conn, $_GET["search"]);  
    if (!$u){
      header('Location: index.php');
      exit();
    }

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM users 
              WHERE username LIKE ? AND activated = ?";
    $url_n = "search_exec_long.php?search={$u}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'ss', $url_n, $u_search, $one);

    // Perform search query
    $output = performSearch($conn, substr($limit, 6));
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Search for users</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/lload.js"></script>
</head>
<body>
  <?php require_once 'template_pageTop.php' ?>
  <div id="pageMiddle_2">
    <div id="long_search" class="genWhiteHolder">
      <?php 
        echo measureTime();
        echo $output;
      ?>
    </div>
    <div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
  </div>
  <?php require_once 'template_pageBottom.php' ?>
</body>
</html>
