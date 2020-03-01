<?php 
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/gr_common.php';
  require_once 'php_includes/pagination.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  // Check if user is logged in
  isLoggedIn($user_ok, $log_username);

  $u = $_SESSION['username'];

  $otype = "grs_0";

  // Handle pagination
  $sql_s = "SELECT COUNT(gp.id)
            FROM gmembers AS gm
            LEFT JOIN groups AS gp ON gp.name = gm.gname
            LEFT JOIN users AS u ON gm.mname = u.username 
            WHERE gm.mname = ?";
  $url_n = "view_all_groups.php?u={$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n, $u); 

  if(isset($_GET["otype"]) || $otype == "grs_0"){
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }

    $my_all_list = "";
    $cond = "";
    $grCat = "";
    $grType = "";

    // If sort type exists in URL order groups in regard of that
    if($otype == "grs_0"){
      $cond = "ORDER BY gp.creation DESC";
    }else if($otype == "grs_1"){
      $cond = "ORDER BY gp.creation ASC";
    }else if($otype == "grs_2"){
      $cond = "ORDER BY gp.creator";
    }else if($otype == "grs_3"){
      $cond = "ORDER BY gp.creator DESC";
    }else if($otype == "grs_4"){
      $grCat = "1";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_5"){
      $grCat = "2";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_6"){
      $grCat = "3";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_7"){
      $grCat = "4";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_8"){
      $grCat = "5";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_9"){
      $grCat = "6";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_10"){
      $grCat = "7";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_11"){
      $grCat = "8";
      $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_12"){
      $grType = "1";
      $cond = "AND gp.invrule = ? ORDER BY gp.name DESC";
    }else if($otype == "grs_13"){
      $grType = "0";
      $cond = "AND gp.invrule = ? ORDER BY gp.name DESC";
    }

    $sql = "SELECT gm.*, gp.*, u.*
            FROM gmembers AS gm
            LEFT JOIN groups AS gp ON gp.name = gm.gname
            LEFT JOIN users AS u ON gm.mname = u.username 
            WHERE gm.mname = ? $cond $limit";
    $stmt = $conn->prepare($sql);
    
    if($otype == "grs_0" || $otype == "grs_1" || $otype == 'grs_2' || $otype == 'grs_3'){
        $stmt->bind_param("s", $u);
    }else if($otype != "grs_0" && $otype != "grs_1" && $otype != "grs_12" &&
      $otype != "grs_13"){
      $stmt->bind_param("ss", $u, $grCat);
    }else{
      $stmt->bind_param("ss", $u, $grType);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0){
      while ($row = $result->fetch_assoc()) {
        $my_all_list .= genGrBox($row);
      }
    }else{
      $my_all_list = "
        <p style='text-align: center; color: #999;'>
          Unfortunately, there are no such groups fitting the criteria
        <p>
      ";
    }
    
    // Produce output
    if(isset($_GET["otype"])){
      echo $my_all_list;
      exit();
    }
  }

  $cnt_gr = cntLikesNew($conn, $u, 'gmembers', 'mname');

  if($cnt_gr < 1){
    $my_all_list = "
      <p style='text-align: center; color: #999;'>
        You are not in any groups at the moment.
        <a href='/groups'>Create</a> your own one or <a href='/groups'>join</a> to an
        existing one!
      <p>
    ";
  }
  $stmt->close();

  $my_cnt = cntLikesNew($conn, $log_username, 'gmembers', 'mname');
  $cre_cnt = cntLikesNew($conn, $log_username, 'groups', 'creator');
?>
<!DOCTYPE html>
<html>
<head>
  <title>View My All Groups</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <script src="/js/main.js"></script>
  <script src="/js/lload.js"></script>
  <script src="/js/specific/filter.js"></script>
  <script src="/js/specific/dd.js"></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/jjs.js" async></script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <div id="data_holder">
      <div>
        <div><span><?php echo $my_cnt; ?></span> groups as member</div>
        <div><span><?php echo $cre_cnt; ?></span> created groups</div>
      </div>
    </div>
    <button id="sort" class="main_btn_fill">Filter Groups</button>
    <div id="sortTypes">
      <div class="gridDiv">
        <p class="mainHeading">Establishment date</p>
        <div id="grs_0">Newest to oldest</div>
        <div id="grs_1">Oldest to newest</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Category</p>
        <div id="grs_4">Animals</div>
        <div id="grs_5">Relationships</div>
        <div id="grs_6">Friends &amp; Family</div>
        <div id="grs_7">Freetime</div>
        <div id="grs_8">Sports</div>
        <div id="grs_9">Games</div>
        <div id="grs_10">Knowledge</div>
        <div id="grs_11">Others</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Creator</p>
        <div id="grs_2">Alphabetical order</div>
        <div id="grs_3">Reverse alphabetical order</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Type</p>
        <div id="grs_12">Public groups</div>
        <div id="grs_13">Private groups</div>
      </div>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
    <hr class="dim">
    <hr id="sepHr">
    <div id="userFlexArts" class="flexibleSol"><?php echo $my_all_list; ?></div>
    <div class="clear"></div>
    <div id="paginationCtrls" style="text-align: center;"><?php echo $paginationCtrls; ?></div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    doDD('sort', 'sortTypes');

    function successHandler(req) {
      _("userFlexArts").innerHTML = req.responseText;
      startLazy(true);
    }

    const SERVER = '/view_all_groups.php?otype=';
    const BOXES = [];

    for (let i = 0; i < 14; i++){
      BOXES.push("grs_" + i);
    }

    for (let box of BOXES) {
      addListener(box, box, 'userFlexArts', SERVER, successHandler);
    }

    changeStyle("grs_0");
  </script>
</body>
</html>
