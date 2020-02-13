<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/perform_checks.php';
	require_once 'php_includes/status_common.php';
	require_once 'php_includes/friends_common.php';
	require_once 'php_includes/wrapText.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';

	// Make sure the user is logged in
  $u = checkU($_SESSION['username'], $conn);
	
  userExists($conn, $u); 

	$otype = "all";
  $limit = 'LIMIT 100';

  // Initialize vars
	$moMoFriends = "";
  $one = "1";
	$their_friends = array();
	$myf = array();

  if(isset($_GET["otype"])){
    $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
  }
 
  // Main logic is wrapped in a separate file; used elsewhere
  require_once 'friendsugg_fetch.php'; 	
?>
<!DOCTYPE html>
<html>
  <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" href="/style/style.css">
    <title><?php echo $u; ?> - Friend Suggestion</title>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/mbc.js"></script>
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />

    <script src="/js/specific/friendsugg.js"></script>
    <script src="/js/specific/anim.js"></script>
    <script src="/js/lload.js"></script>
    <script src="/js/specific/sort_dd.js"></script>
  </head>
  <body style="overflow-x: hidden;">
    <?php require_once 'template_pageTop.php'; ?>
      <div id="pageMiddle_2">
        <div id="data_holder">
          <div>
            <div>
              <span id="countFsug">
                <?php echo $countFs; ?>
              </span>
              friend suggestions
            </div>
          </div>
        </div>
        <button id="sort" class="main_btn_fill">Filter suggestions</button>
          <div id="sortTypes">
            <div class="gridDiv">
              <p class="mainHeading">Similar age</p>
              <div id="suggf_8">+/- 1-2 years</div>
              <div id="suggf_9">+/- 3-5 years</div>
              <div id="suggf_10">+/- 6-10 years</div>
              <div id="suggf_11">+/- 11-20 years</div>
            </div>

            <div class="gridDiv">
              <p class="mainHeading">Users nearby</p>
              <div id="suggf_0">0-5 km (0-3.1 miles) area</div>
              <div id="suggf_1">5-10 km (3.1-6.2 miles) area</div>
              <div id="suggf_2">10-50 km (6.2-31 miles) area</div>
              <div id="suggf_3">50-100 km (31-62.1 miles) area</div>
            </div>

            <div class="gridDiv">
              <p class="mainHeading">Close relationships</p>
              <div id="suggf_4">friends of friends</div>
            </div>
      
            <div class="gridDiv">
              <p class="mainHeading">Geolocation</p>
              <div id="suggf_5">from the same city</div>
              <div id="suggf_6">from the same province</div>
              <div id="suggf_7">from the same country</div>
            </div>

            <div class="clear"></div>
          </div>
          <div class="clear"></div>
          <hr class="dim">
          <div id="momofdif" class="flexibleSol">
            <?php if($moMoFriends != ""){
              echo $moMoFriends;
            }else{
              echo "
                <p style='color: #999; text-align: center;'>
                  Oops... we could not list you any friend suggestions
                </p>
              ";
            } ?>
          </div>
        <div class="clear"></div>
        <div id="paginationCtrls" style="width: 100px; height: 10px; margin: 0 auto;">
          <?php echo $paginationCtrls; ?>
        </div>
      </div>
      <?php require_once 'template_pageBottom.php'; ?>
      <script type="text/javascript">
          doAnim('#sort', '#sortTypes');

          applyListeners(12, "suggf_", "momofdif", "/more_friends.php?otype=", "momofdif");
          changeStyle("suggf_0", "suggf_", 12);
    </script>
  </body>
</html>
