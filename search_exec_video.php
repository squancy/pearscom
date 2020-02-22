<?php
  require_once 'timeelapsedstring.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/mtime.php';
  require_once 'safe_encrypt.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';
  require_once 'durc.php';

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

  // Get URL params
  if(isset($_GET['search']) && isset($_GET["uU"])){
    $u = mysqli_real_escape_string($conn, $_GET["search"]);  
    $uU = mysqli_real_escape_string($conn, $_GET["uU"]);  
    if ($u == "" || $uU == ""){
      header('Location: index.php');
      exit();
    }

    $u_search = "%$u%";

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM videos 
              WHERE user = ? AND video_name LIKE ? OR video_description LIKE ?";
    $url_n = "/search_videos/{$u}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'sss', $url_n, $uU, $u_search,
      $u_search);

    // Perform search query
    $sql = "SELECT * FROM videos 
            WHERE user = ? AND (video_description LIKE ? OR video_name LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$uU,$u_search,$u_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $id = $row["id"];
        $video_name = $row["video_name"];
        $video_description = $row["video_description"];
        $video_filename = $row["video_file"];
        $video_poster = $row["video_poster"];
        $video_upload = $row["video_upload"];
        $vu = strftime("%b %d, %Y", strtotime($video_upload));
        $agoform = time_elapsed_string($video_upload);
        $id = base64url_encode($id,$hshkey);
        $dur = convDur($row["dur"]);

        if($video_description == NULL){
          $video_description = "No description given";
        }

        $video_name = wrapText($video_name, 20);
        $video_description = wrapText($video_description, 20);
  
        if($video_name == NULL){
          $video_name = "Untitled";
        }

        if($video_poster == NULL){
          $pcurl = '/images/defaultimage.png';
        }else{
          $pcurl = '/user/'.$uU.'/videos/'.$video_poster.'';
        }

        $output .= '
          <a href="/video_zoom/'.$id.'">
            <div class="lazy-bg genBg sepDivs" data-src=\''.$pcurl.'\'
              style="width: 50px; height: 50px; border-radius: 50%; float: left;
              margin-right: 5px;"></div>
          </a>
          <div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;"
            id="sLong">
            <p>'.$video_name.'</p>
            <p>'.$video_description.'</p>
            <p>'.$dur.'</p>
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
  <title>Pearscom - Search for videos</title>
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
  <script src="/js/lload.js" async></script>
  <script src="/js/specific/searchns.js"></script>
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
    const uU = '<?php echo $uU; ?>';
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php' ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off"
          placeholder="Search for videos by their name or description">
          <div id="artSearchBtn" onclick="getMyFLArr('searchArt',
            '/search_videos/' + (encodeURI(_('searchArt').value)) + '&uU=' + uU)">
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
