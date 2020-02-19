<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/art_common.php';
  require_once 'php_includes/pagination.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'ccov.php';
  require_once 'headers.php';

  // Check if the user is logged in
  $u = checkU($_SESSION['username'], $conn);

  $one = "1";

  // Check if the user exists in the database
  userExists($conn, $u);

  $otype = "aff";

  // Normal sugg. by the users's friends without limit
  // Get all friends
  $all_friends = getUsersFriends($conn, $u, $log_username);
  $sugglist = "";
  $friendstags = array();
  $friendsGR = join("','", $all_friends);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM articles WHERE written_by IN ('$friendsGR')";
  $url_n = "/article_suggestions";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n); 

  // Get user's lat and lon coords
  list($lat, $lon) = getLatLon($conn, $log_username);

  // Set maximum acceptable distance
  $lat_m2 = $lat-0.2;
  $lat_p2 = $lat+0.2;

  $lon_m2 = $lon-0.2;
  $lon_p2 = $lon+0.2;

  $countSugg = 0;

  // Generate article boxes on demand
  if(isset($_GET["otype"]) || $otype == "aff"){
    $countSugg = 0;
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }

    $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsGR') ORDER BY RAND() $limit";

    // Custom queries for each filter
    if($otype == "afn"){
      $sql = "SELECT a.*,u.* FROM users AS u LEFT JOIN articles AS a ON a.written_by =
        u.username WHERE a.written_by NOT IN ('$friendsGR') AND lat BETWEEN ? AND ? AND lon
        BETWEEN ? AND ? AND a.written_by != ? ORDER BY RAND() $limit";
    }else if($otype == "afr"){
      $sql = "SELECT a.*,u.* FROM users AS u LEFT JOIN articles AS a ON a.written_by =
        u.username WHERE a.written_by NOT IN ('$friendsGR') AND lat NOT BETWEEN ? AND ? AND lon
        NOT BETWEEN ? AND ? AND a.written_by != ? ORDER BY RAND() $limit";
    }

    $stmt = $conn->prepare($sql);
    if($otype == "afn" || $otype == "afr"){
      $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    }

    $stmt->execute();
    $result2 = $stmt->get_result();
    while($row = $result2->fetch_assoc()){
      $sugglist .= genFullBox($row);
      $countSugg++;
    }
  }

  if($result2->num_rows < 1){
    $sugglist = "
      <p style='text-align: center; color: #999;'>
        Unfortunately, there are no articles fitting the criteria
      </p>
    ";
  }

  if (isset($_GET["otype"])){
    echo $sugglist."!|||!".$countSugg;
    exit();
  }
  $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $u; ?> - More Suggestions</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <script src="/jquery_in.js" async></script>
  <script src="/text_editor.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/specific/filter.js"></script>
  <script src="/js/specific/dd.js"></script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <div id="data_holder">
      <div>
        <div>
          <span id="countArtsS"><?php echo $countSugg; ?></span>
          suggested articles
        </div>
      </div>
    </div>

    <button id="sort" class="main_btn_fill">Filter Suggestions</button>
    <div id="sortTypes">
      <div class="gridDivS">
        <p class="mainHeading">Related</p>
        <div id="aff">Articles from friends</div>
      </div>
      <div class="gridDivS">
        <p class="mainHeading">Geolocation</p>
        <div id="afn">Articles from nearby users</div>
      </div>
      <div class="gridDivS">
        <p class="mainHeading">Random</p>
        <div id="afr">Articles from random users</div>
      </div>
    </div>
    <div class="clear"></div>
    <hr class="dim">
    <div id="userFlexArts" class="flexibleSol">
      <?php echo $sugglist; ?>
    </div>
    <div class="clear"></div>
    <div id="paginationCtrls"><?php echo $paginationCtrls; ?></div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    doDD("sort", "sortTypes");

    function successHandler(req) {
      let data = req.responseText.split("!|||!");
      _("userFlexArts").innerHTML = data[0];
      _("countArtsS").innerHTML = data[1];
    }

    const SERVER = "/more_sugg_art.php?otype=";

    const BOXES = ['aff', 'afn', 'afr'];
    for (let box of BOXES) {
      addListener(box, box, 'userFlexArts', SERVER, successHandler);
    }

    changeStyle("aff", BOXES);
  </script>
</body>
</html>
