<?php
  require_once 'timeelapsedstring.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/mtime.php';
  require_once 'php_includes/wrapText.php';
  require_once 'safe_encrypt.php';
  require_once 'headers.php';
  require_once 'ccov.php';

  $output = "";
  $u = "";
  $count = 0;

  // If user is not logged in no search is allowed
  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
    $output = '
      <p style="font-size: 14px; margin: 0px;">
        You are not logged in therefore you cannot search!
      </p>
    ';
    exit();
  }

  // Get paramaters in the URL and perform search
  if(isset($_GET['search'])){
    $u = mysqli_real_escape_string($conn, $_GET["search"]);  
    if (!$u){
      header('Location: index.php');
      exit();
    }
    $u_search = "$u%";
  
    // If user searches in their articles do not search by author
    $clause = "written_by LIKE ? OR title LIKE ? OR tags LIKE ? OR category LIKE ?";
    $inputText = "Search for articles by their author, title, category or tags";
    if($_GET["inmy"] == "yes" || $_GET['user'] != NULL){
      $clause = "(written_by = ?) AND (title LIKE ? OR tags LIKE ? OR category LIKE ?)";
      if (!isset($_GET['user'])) {
        $inputText = "Search in your articles by their title, category or tags";
      }
    }

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM articles 
              WHERE $clause";
    $url_n = "/search_articles/{$u}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'ssss', $url_n, $u_search,
      $u_search, $u_search, $u_search);

    if (isset($_GET['user'])) {
      $sqlUname = mysqli_real_escape_string($conn, $_GET['user']);
    } else {
      $sqlUname = $log_username;
    }

    // Perform search query
    $sql = "SELECT * FROM articles WHERE $clause $limit";
    $stmt = $conn->prepare($sql);
    if((isset($_GET["inmy"]) && $_GET["inmy"] == "yes") || isset($_GET['user'])){
      $stmt->bind_param("ssss", $sqlUname, $u_search, $u_search, $u_search);
    }else{
      $stmt->bind_param("ssss", $u_search, $u_search, $u_search, $u_search);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $written_by = $row["written_by"];
        $wbori = urlencode($written_by);
        $title = $row["title"];
        $tags = $row["tags"];
        $cat = $row["category"];
        $post_time = $row["post_time"];
        $pt = strftime("%b %d, %Y", strtotime($post_time));
        $agoform = time_elapsed_string($post_time);

        $tags = wrapText($tags, 20);
        $title = wrapText($title, 20);
        
        $post_time = base64url_encode($post_time, $hshkey);
        $cover = chooseCover($cat);

        $cover = preg_replace('/<img src="\/images\/\w+\/(\w+)\.jpg"\s+class="cover_art">/',
          "/images/art_cover/$1.jpg", $cover);

        $output .= '
          <a href="/articles/'.$post_time.'/'.$wbori.'">
            <div class="lazy-bg genBg sepDivs" data-src=\''.$cover.'\' style="width: 50px;
              height: 50px; border-radius: 50%; float: left; margin-right: 5px;"></div>
          </a>
          <div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;"
            id="sLong">
            <p><a href="/user/'.$wbori.'/">'.$written_by.'</a></p>
            <p>'.$title.'</p>
            <p>'.$tags.'</p>
            <p>'.$cat.'</p>
            <p>Published '.$agoform.' ago</p>
          </div>
          <div class="clear"></div>
          <hr class="dim">
        ';
        $count++;
      }
    } else {
      // No results from search
      $output = "<p class='txtc' style='color: #999;'>Unfortunately, no results were found</p>";
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pearscom - Search for articles</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js" async></script>
  <script src="/js/main.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/ajax.js" async></script>
  <script src="/js/create_down.js" async></script>
  <script src="/js/specific/searchns.js" async></script>
  <style type="text/css">
    @media only screen and (max-width: 1000px){ 
      #searchArt{
        width: 90% !important;
      }

      #artSearchBtn{
        width: 10% !important;
      }
    }

    @media only screen and (max-width: 500px){
      #searchArt {
        width: 85% !important;
      }

      #artSearchBtn {
        width: 15% !important;
      }
    }
  </style>
  <script type="text/javascript">
    let inmy = "<?php echo $_GET["inmy"]; ?>";
    let user = "<?php echo $_GET['user'] ?>";
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php' ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off"
          placeholder="<?php echo $inputText; ?>">
        <div id="artSearchBtn" onclick="getMyFLArr('searchArt',
          '/search_articles/' + (encodeURI(_('searchArt').value)) + '&user=' + user,
          '/search_articles/' + (encodeURI(_('searchArt').value)) + '&inmy=yes',
          inmy == 'yes')">
          <img src="/images/searchnav.png" width="17" height="17">
        </div>
      </div>
      <div class="clear"></div>
    </div>
    <br>
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
