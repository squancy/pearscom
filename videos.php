<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'php_includes/video_common.php';
    require_once 'php_includes/perform_checks.php';
    require_once 'php_includes/pagination.php';
    require_once 'timeelapsedstring.php';
    require_once 'safe_encrypt.php';
    require_once 'durc.php';
    require_once 'phpmobc.php';
    require_once 'headers.php';

    // Make sure the _GET "u" is set, and sanitize it
    $u = checkU($_GET['u'], $conn);

    $one = "1";
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
      // Check if user exists in db
      userExists($conn, $u);
    }
    $otype = "def";
    $ismobile = mobc();
    
    $id = "";
    $video_form = "";
    $echo_videos_short = "";
    $id_number = "";
    // Check to see if the viewer is the account owner
    $isOwner = "no";
    if($u == $log_username && $user_ok){
      $isOwner = "yes";
      $video_form  = '
        <form id="video_form_div" style="width: 100%;" enctype="multipart/form-data"
          method="post" class="styleform">
          <p style="font-size: 18px; margin-top: 0px;" class="txtc">Upload a new video</p>
            <input type="text" name="videoname"
              style="margin-left: 0px; display: inline-block;" id="videoname"
              placeholder="Title or name of your video" onkeyup="statusMax(this,150)">
            <input type="file" name="video" id="file" class="inputfile" required
              onchange="showfile(\'file\', \'sel_f\')">
            <label for="file" id="choose_file" class="ltmarg">Choose video</label>
            <span id="sel_f">&nbsp; No files selected</span>
            <textarea id="description" style="margin-left: 0px;" name="description"
              class="longerv" placeholder="Description of the video in a few words"
              onkeyup="statusMax(this,1000)"></textarea>
            <input type="file" name="poster" id="asd" class="inputfile" accept="image/*"
              onchange="showfile(\'asd\', \'sel_f2\')">
            <label for="asd" id="as">Choose poster</label>
            <span id="sel_f2">&nbsp;&nbsp;No files selected</span>
            <br />
            <p>
              <input type="button" value="Upload Video" onclick="uploadVideo()" id="vupload"
                class="main_btn_fill fixRed" style="display: block; margin: 0 auto;">
              <div id="txt_holder"></div>
              <div class="collection vInfo" style="border-top: 1px solid rgba(0, 0, 0, 0.1);"
                id="ccSu">
                <p style="font-size: 18px;" id="signup">How can I upload my video?</p>
                <img src="/images/alldd.png">
              </div>
              <div class="slideInfo vInfoRev" id="suDD">
                <p style="font-size: 14px;" class="txtc">
                  Make sure the video size is below 10MB and the poster image size is below 5MB.
                  The allowed file extensions for posters are jpg, jpeg, png and gif, for videos
                  it is MP4, WebM and Ogg (may vary between browsers).
                </p>
              </p>
              <p style="font-size: 14px;" class="txtc">
                A poster will function as a showcase image for the video until that is started.
              </p>
              <p style="font-size: 14px;" class="txtc">
                For further information may consider visiting the <a href="/help">help &amp;
                support</a> page.
              </p>
            </div>
            <div id="rolling"></div>
            <div id="pbc">
              <div id="progressBar"></div>
              <div id="pbt"></div>
            </div>
          </form>
      ';
    }

    // Handle pagination
    $sql_s = "SELECT COUNT(id) FROM videos WHERE user=?";
    $url_n = "/videos/{$u}";
    list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n, $u); 

    // Check if the user has uploaded any videos
    $echo_videos = "";
    $sql = "SELECT * FROM videos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $cvids = $numrows;
    if($numrows < 1){
      if($isOwner == "yes"){
        $echo_videos = "<p style='font-size: 14px;'>You have not uploaded any videos yet</p>";
      }else{
        $echo_videos = "
          <p style='text-align: center; font-size: 14px;'>
            ".$u." has not uploaded any videos yet
          </p>
        ";
      }
    }
    $stmt->close();

    if(isset($_GET["otype"]) || $otype != ""){
      $clause = "";
      $typeExists = false;
      if(isset($_GET["otype"])){
        $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
        $typeExists = true;
      }

      if($otype == "sort_0"){
        $clause = "ORDER BY video_upload DESC";
      }else if($otype == "sort_1"){
        $clause = "ORDER BY video_upload ASC";
      }else if($otype == "sort_4"){
        $clause = "ORDER BY video_name";
      }else if($otype == "sort_5"){
        $clause = "ORDER BY video_name DESC";
      }else if($otype == "sort_2"){
        $clause = "ORDER BY video_description";
      }else if($otype == "sort_3"){
        $clause = "ORDER BY video_description DESC";
      }else if($otype == "sort_6"){
        $clause = "ORDER BY dur DESC";
      }else if($otype == "sort_7"){
        $clause = "ORDER BY dur";
      }else{
        $clause = "ORDER BY video_upload DESC";
      }

      // Get users videos
      $items = "";
      $url = array();
      $sql = "SELECT * FROM videos WHERE user=? $clause $limit";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$u);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()){
        $echo_videos .= genLVidBox($conn, $row, false); 
      }

      $echo_videos .= '
        <div class="clear"></div>
        <div id="pagination_controls">'.$paginationCtrls.'</div>
      ';

      if(isset($_GET["otype"])){
        echo $echo_videos;
        exit();
      }
    }

    $cntRels = 0;

    // Get related videos
    $all_friends = getUsersFriends($conn, $u, $u);
    $nof = false;
    $allfmy = join("','", $all_friends);
    $related_vids = "";
    $sql = "SELECT * FROM videos WHERE user IN ('$allfmy') ORDER BY RAND() LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $cntRels++;
      $related_vids .= genLVidBox($conn, $row, false);
    }
    $stmt->close();

    if(!$allfmy){
      $nof = true;
      $sql = "SELECT * FROM videos ORDER BY RAND() LIMIT 30";
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()){
        $cntRels++;
        $related_vids .= genLVidBox($conn, $row, false);
      }
      $stmt->close();
    }
    
    $isrel = false;
    if($related_vids == ""){
      $related_vids = '
        <p style="color: #999;" class="txtc">
          It seems that we could not list any related videos for you
        </p>
      ';
      $isrel = true;
    }

    $cntMyvids = cntLikesNew($conn, $u, 'videos', 'user');

    $sql = "SELECT COUNT(l.id) FROM video_likes AS l LEFT JOIN videos AS v ON v.id = l.video
      WHERE l.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($cntLikesGot);
    $stmt->fetch();
    $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $u; ?>&#39;s Videos</title>
  <meta charset="utf-8">
  <meta lang="en">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="All of <?php echo $u; ?>&#39;s videos is available and you
    can watch them. Click on the icon in order to see in in a bigger view.">
  <meta name="keywords" content="pearscom videos <?php echo $u; ?>, <?php echo $u; ?> videos,
    <?php echo $u; ?> all videos, videos of <?php echo $u; ?>, <?php echo $u; ?> videos page">
  <meta name="author" content="Pearscom">
  <script src="/js/jjs.js"></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/mbc.js"></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/lload.js"></script>
  <script src="/js/specific/status_max.js"></script>
  <script src="/js/specific/p_dialog.js"></script>
  <script src="/js/specific/file_dialog.js"></script>
  <script src="/js/specific/error_dialog.js"></script>
  <script src="/js/specific/vgen.js"></script>
  <script src="/js/specific/dd.js"></script>
  <script src="/js/specific/filter.js"></script>

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
<body style="overflow-x: hidden;">
  <?php require_once 'template_pageTop.php'; ?>
  <div id="overlay"></div>
  <div id="dialogbox"></div>
  <div id="pageMiddle_2">
    <div id="artSearch">
      <div id="artSearchInput">
        <input id="searchArt" type="text" autocomplete="off"
           onkeyup="getVideos(this.value)"
           placeholder="Search among videos by their name or description">
        <div id="artSearchBtn" onclick="getLVideos()">
          <img src="/images/searchnav.png" width="17" height="17">
        </div>
      </div>
      <div class="clear"></div>
    </div>
    <div id="vidSearchResults" class="longSearches"></div>
    <br />
    <?php if($isOwner == "yes"){ ?>
      <?php echo $video_form; ?>
    <?php } ?>
    <div id="data_holder">
      <div>
        <div><span><?php echo $cntMyvids; ?></span> videos</div>
        <div><span><?php echo $cntLikesGot; ?></span> likes got</div>
      </div>
    </div>

    <button id="sort" class="main_btn_fill">Filter videos</button>
    <div id="sortTypes">
      <div class="gridDiv">
        <p class="mainHeading">Publish date</p>
        <div id="sort_0">Newest to oldest</div>
        <div id="sort_1">Oldest to newest</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Description</p>
        <div id="sort_2">Alphabetical order</div>
        <div id="sort_3">Reverse alphabetical order</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Title</p>
        <div id="sort_4">Alphabetical order</div>
        <div id="sort_5">Reverse alphabetical order</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Duration</p>
        <div id="sort_6">Longest to shortest</div>
        <div id="sort_7">Shortest to longest</div>
      </div>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
    <hr class="dim">

    <?php echo $info_vid_user; ?>
      
    <div id="holdit" class="ppForm mvidHolder"><?php echo $echo_videos; ?></div>
    <div class="clear"></div>
    <hr class="dim">
    <div id="data_holder">
      <div>
        <div><span><?php echo $cntRels; ?></span> related videos</div>
      </div>
    </div>
    <div class="vRelHolder ppForm" id="vRelHolder">
      <?php echo $related_vids; ?>
    </div>
    <div class="clear"></div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    const VUNAME = "<?php echo $u; ?>";

    doDD("sort", "sortTypes");
    doDD("ccSu", "suDD");

    const SERVER = "/videos.php?u=<?php echo $u; ?>&otype=";

    function successHandler(req) {
      _("holdit").innerHTML = req.responseText;
      startLazy(true);
    }

    const BOXES = [];
    for(let i = 0; i < 8; i++){
      BOXES.push("sort_" + i);
    }

    for (let box of BOXES) {
      addListener(box, box, 'holdit', SERVER, successHandler);
    }

    changeStyle("sort_0", BOXES);
  </script>
</body>
</html>
