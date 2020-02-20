<?php
  require_once 'timeelapsedstring.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/mtime.php';
  require_once 'php_includes/pagination.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  $output = "";
  $u = "";
  $count = 0;

  // AJAX calls this code to process the request
  if(isset($_GET['search']) && isset($_GET["uU"])){
    // Escape vars
    $u = mysqli_real_escape_string($conn, $_GET["search"]);
    $uU = mysqli_real_escape_string($conn, $_GET["uU"]);
    if ($u == ""){
      header('Location: /index');
      exit();
    }
    $u_search = "%$u%";

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM photos 
              WHERE user = ? AND (gallery LIKE ? OR description LIKE ?)";
    $url_n = "/photo_search/{$u}&uU={$uU}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'sss', $url_n, $uU,
      $u_search, $u_search); 
    
    $sql = "SELECT * FROM photos 
            WHERE user = ? AND (gallery LIKE ? OR description LIKE ?) $limit";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $uU, $u_search, $u_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $filename = $row["filename"];
        $gallery = $row["gallery"];
        $description = wrapText($row["description"], 20);
        $uploaddate = $row["uploaddate"];
        $ud = strftime("%b %d, %Y", strtotime($uploaddate));  
        
        if($description == ""){
            $description = "No description given";
        }
        
        $uds = time_elapsed_string($uploaddate);
        $pcurl = '/user/'.$uU.'/'.$filename;
                
        $output .= '
          <a href="/photo_zoom/'.urlencode($uU).'/'.$filename.'">
            <div class="lazy-bg genBg sepDivs" data-src=\''.$pcurl.'\' style="width: 50px;
              height: 50px; border-radius: 50%; float: left; margin-right: 5px;"></div>
          </a>
          <div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;"
            id="sLong">
            <p>'.$gallery.'</p>
            <p>'.$description.'</p>
            <p>Published '.$uds.' ago</p>
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
  <title>Search for photos</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/lload.js" async></script>
  <script src="/js/specific/longbtn.js"></script>
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
</head>
<body>
  <?php require_once 'template_pageTop.php' ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off"
          placeholder="Search for photos by their gallery name or description">
        <div id="artSearchBtn" onclick="getLSearchArt('searchArt', 'artSearchResults',
          '/photo_search/' + (_('searchArt').value) + '&uU=<?php echo $uU; ?>')">
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
    <div class="clear"></div>
    <div id="pagination_controls" style="margin-top: 30px; margin-bottom: 30px;">
      <?php echo $paginationCtrls; ?>
    </div>
  </div>
  <?php require_once 'template_pageBottom.php' ?>
</body>
</html>
