<?php
  require_once 'timeelapsedstring.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/mtime.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

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

  // AJAX calls this code
  if(isset($_GET['search'])){
    $u = mysqli_real_escape_string($conn, $_GET["search"]);  
    if ($u == ""){
      header('Location: /index');
      exit();
    }
    $u_search = "%$u%";

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM groups 
              WHERE name LIKE ?";
    $url_n = "/search_groups/{$u}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n, $u_search);

    $sql = "SELECT * FROM groups 
            WHERE name LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $id = $row["id"];
        $gname = $row["name"];
        $gnameori = urlencode($gname);
        $gnameim = $gname;
        $gcreation = $row["creation"];
        $logo = $row["logo"];
        $invrule = $row["invrule"];
        $cat = $row["cat"];
        $gdes = $row["des"];
        $creator = $row["creator"];
        $creatorori = urlencode($creator);
        $gc = strftime("%b %d, %Y", strtotime($gcreation));

        $creator = wrapText($creator, 20);
        $gname = wrapText($gname, 20);
        $gdes = wrapText($gdes, 20);

        if($invrule == 0){
          $invrule = "Private";
        }else{
          $invrule = "Public";
        }
        
        if($gdes == NULL){
          $gdes = "No description given";
        }
        
        $agoform = time_elapsed_string($gcreation);

        if($logo == NULL || $logo == "gdef.png"){
          $logo = '/images/gdef.png';
        }else{
          $logo = '/groups/'.$gnameim.'/'.$logo;
        }

        $gdes = str_replace("\\n", " ", $gdes);

        $output .= '
          <a href="/group/'.$gnameori.'">
            <div class="lazy-bg genBg sepDivs" data-src=\''.$logo.'\' style="width: 50px;
              height: 50px; border-radius: 50%; float: left; margin-right: 5px;"></div>
          </a>
          <div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;"
            id="sLong">
            <p><a href="/group/'.$gnameori.'/">'.$gname.'</a></p>
            <p>'.$invrule.' group</p>
            <p>Created by <a href="/user/'.$creatorori.'/">'.$creator.'</a></p>
            <p>'.$gdes.'</p>
            <p>Established '.$agoform.' ago</p>
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
  <title>Pearscom - Search for groups</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/jquery_in.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/lload.js"></script>
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
</head>
<body>
  <?php require_once 'template_pageTop.php' ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off"
          placeholder="Search for videos by their name or description">
          <div id="artSearchBtn" onclick="getMyFLArr('searchArt',
            '/search_groups/' + (encodeURI(_('searchArt').value)))">
            <img src="/images/searchnav.png" width="17" height="17"></div>
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
