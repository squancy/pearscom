<?php
  require_once "php_includes/check_login_statues.php";
  require_once "php_includes/perform_checks.php";
  require_once "php_includes/photo_common.php";
  require_once "php_includes/pagination.php";
  require_once "php_includes/wrapText.php";
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'headers.php';
  require_once 'phpmobc.php';
  
  // Make sure the $_GET "u" is set, and sanitize it
  $u = checkU($_GET['u'], $conn);
  
  $one = "1";

  if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
    // If user is logged in make sure they exist in db
    userExists($conn, $u);
  }

  $isMob = mobc();
  $gallery_list = "";
  $photo_form = "";

  $otype = "sort_0";

  // Check to see if the viewer is the account owner
  $isOwner = isOwner($u, $log_username, $user_ok);
  if($isOwner == 'Yes'){
    $photo_form = '
      <form id="photo_form" class="styleform" style="width: 100%; margin-top: 20px;"
        enctype="multipart/form-data" method="post" class="pcpk">
        <p style="font-size: 18px; margin-top: 0px; text-align: center;">
          Upload a new photo
        </p>
        <select name="gallery" class="ssel" id="cgal" style="margin-top: 0;" required>
          <option value="" selected="true" disabled="true">Choose gallery</option>
          <option value="Myself">Myself</option>
          <option value="Family">Family</option>
          <option value="Pets">Pets</option>
          <option value="Friends">Friends</option>
          <option value="Games">Games</option>
          <option value="Freetime">Freetime</option>
          <option value="Sports">Sports</option>
          <option value="Knowledge">Knowledge</option>
          <option value="Hobbies">Hobbies</option>
          <option value="Working">Working</option>
          <option value="Relations">Relations</option>
          <option value="Other">Other</option>
        </select>
        &nbsp;
        <input type="file" id="file" class="inputfile" accept="image/*" required
          onchange="showfile()">
        <label for="file" id="choose_file">Choose a file</label>
        &nbsp;
        <span id="sel_f">No files selected</span><br />
        <textarea id="description" style="height: 60px;" name="description"
          placeholder="Describe your photo in a few words" onkeyup="statusMax(this,1000)"
          style="height: 40px;"></textarea>
        <p style="margin-bottom: 0;">
          <input type="button" style="display: block; margin: 0 auto;" value="Upload photo"
            class="fixRed main_btn_fill" id="vupload" onclick="uploadPhoto()">
          <p style="font-size: 14px; text-align: center;">
            The maximum file size limit is 5MB. Please make sure your image is below this
            number.
          </p>
        </p>
        <p style="font-size: 14px; margin-top: 0; text-align: center;" id="locht">
          <b style="font-size: 14px;">Tip: </b> upload up to 5 photos at the same time by
          dragging & dropping them into this field. <br>
          For further information please visit the <a href="/help">help &amp; support</a>
          page!
        </p>
        <div id="pbc">
          <div id="progressBar"></div>
          <div id="pbt">
        </div>
      </div>
      <div id="percentage"></div>
      <div id="p_status"></div>
    </form>
    ';
  }

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM photos WHERE user=?";
  $url_n = "/photos/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n, $u); 

  // Get number of all photos
  $count_all = countUserPhots($conn, $u);

  $belong = "";
  if($count_all > 0){
    $belong = '
      <p style="clear: left; text-align: center; color: #999;">
        These photos belong to <a href="/user/'.$u.'/">'.$u.'</a>
      </p>
    ';
  }
  $stmt->close();

  // Count how many comments
  $all_count = countComments($conn, 'photos_status', 'account_name');

  $out_likes = cntLikesNew($conn, $u, 'photo_stat_likes', 'username');

  $countRels = 0;
  $countMine = 0;

  // Get related photos
  $all_friends = getUsersFriends($conn, $u, $u);
  $allfmy = implode("','", $all_friends);
  $related_p = "";
  $sql = "SELECT * FROM photos WHERE user IN ('$allfmy') ORDER BY RAND() LIMIT 15";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $countRels++;
    $related_p .= genPhotoBox($row, true);
  }
  $stmt->close();

  // If there are no friends suggest photos randomly
  $nof = false;
  if($allfmy == ""){
    $nof = true;
    $sql = "SELECT * FROM photos WHERE user != ? ORDER BY RAND() LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $countRels++;
      $related_p .= genPhotoBox($row, true);
    }
    $stmt->close();
  }

  // Initialize gallery vars
  $egalsMyself = "Myself";
  $egalsFamily = "Family";
  $egalsPets = "Pets";
  $egalsFriends = "Friends";
  $egalsGames = "Games";
  $egalsFreetime = "Freetime";
  $egalsSports = "Sports";
  $egalsKnowledge = "Knowledge";
  $egalsHobbies = "Hobbies";
  $egalsWorking = "Working";
  $egalsRelations = "Relations";
  $egalsOther = "Other";
  $egalsDaD = "Drag & Drop";

  // Also call this bunch of code when category is changed
  if(isset($_GET["otype"]) || $otype != ""){
    $typeExists = false;
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
      $typeExists = true;
    }

    // Choose the appropriate sql query for the chosen category
    if($otype == "sort_0"){
      $sql = "SELECT * FROM photos WHERE user = ? ORDER BY uploaddate DESC $limit";
    }else if($otype == "sort_1"){
      $sql = "SELECT * FROM photos WHERE user = ? ORDER BY uploaddate ASC $limit";
    }else if($otype == "sort_15"){
      $sql = "SELECT * FROM photos WHERE user = ? AND description IS NOT NULL ORDER BY
        description $limit";
    }else if($otype == "sort_16"){
      $sql = "SELECT * FROM photos WHERE user = ? AND description IS NOT NULL ORDER BY
        description DESC $limit";
    }else if($otype != "sort_0" && $otype != "sort_1" && $otype != "sort_15" &&
      $otype != "sort_16"){
      $sql = "SELECT * FROM photos WHERE user = ? AND gallery = ? ORDER BY uploaddate
        DESC $limit";
    }

    $stmt = $conn->prepare($sql);

    // Select the proper parameters concerning the query
    if($otype == "sort_2"){
      $stmt->bind_param("ss", $u, $egalsMyself);
    }else if($otype == "sort_3"){
      $stmt->bind_param("ss", $u, $egalsFamily);
    }else if($otype == "sort_4"){
      $stmt->bind_param("ss", $u, $egalsPets);
    }else if($otype == "sort_5"){
      $stmt->bind_param("ss", $u, $egalsFriends);
    }else if($otype == "sort_6"){
      $stmt->bind_param("ss", $u, $egalsGames);
    }else if($otype == "sort_7"){
      $stmt->bind_param("ss", $u, $egalsFreetime);
    }else if($otype == "sort_8"){
      $stmt->bind_param("ss", $u, $egalsSports);
    }else if($otype == "sort_9"){
      $stmt->bind_param("ss", $u, $egalsKnowledge);
    }else if($otype == "sort_10"){
      $stmt->bind_param("ss", $u, $egalsHobbies);
    }else if($otype == "sort_11"){
      $stmt->bind_param("ss", $u, $egalsWorking);
    }else if($otype == "sort_12"){
      $stmt->bind_param("ss", $u, $egalsRelations);
    }else if($otype == "sort_13"){
      $stmt->bind_param("ss", $u, $egalsOther);
    }else if($otype == "sort_14"){
      $stmt->bind_param("ss", $u, $egalsDaD);
    }else{
      $stmt->bind_param("s", $u);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
      $countMine++;
      $gallery_list .= genPhotoBox($row, true);
    }
    $stmt->close();

    // Produce output
    if(isset($_GET["otype"])){
      if($gallery_list != ""){
        echo $gallery_list;
      }else{
        echo "
          <p style='color: #999; text-align: center;'>
            There are no such photos fitting this criteria!
          </p>
        ";
      }
      exit();
    }
  }
  $isP = true;

  // Check to see if user is logged in
  if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
    $related_p = "
      <p style='color: #999;' class='txtc'>
        You need to be <a href='/login'>logged in</a> in order to see related photos
      </p>
    ";
    $isP = false;
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta lang="en">
    <title><?php echo $u; ?>'s Photos</title>
    <meta name="description" content="Visit <?php echo $u; ?>&#39;s photos in his/her photo
      gallery. Click on the certain images in order to see it in a bigger view.">
    <meta name="keywords" content="<?php echo $u; ?> photos, photo gallery, all photo
      galleries, photos of <?php echo $u; ?>, pearscom photos">
    <meta name="author" content="Pearscom">
    <link rel="icon" href="/images/newfav.png" type="image/x-icon">
    <link rel="stylesheet" href="/style/style.css">
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />

    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/mbc.js"></script>
    <script type="text/javascript">
      var iso = "<?php echo $isOwner; ?>";
    </script>
    <script src="/js/specific/status_max.js"></script>
    <script src="/js/specific/file_dialog.js"></script>
    <script src="/js/specific/p_dialog.js"></script>
    <script src="/js/specific/longbtn.js"></script>
    <script src="/js/specific/photo.js" defer></script>
    <script src="/js/specific/filter.js"></script>
    <script src="/js/specific/dd.js"></script>
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
    </style>
    <script type="text/javascript">
      let UNAME = '<?php echo $u; ?>';
    </script>
</head>
<body style="overflow-x: hidden;">
  <?php include_once("template_pageTop.php"); ?>
  <div id="pageMiddle_2">
  <div id="artSearch">
    <div id="artSearchInput">
      <input id="searchArt" type="text" autocomplete="off" onkeyup="getPhotos(this.value)"
        placeholder="Search among photos by gallery or description">
      <div id="artSearchBtn" onclick="getLSearchArt('searchArt', 'phoSearchResults',
        '/photo_search/' + encodeURI(_('searchArt').value) + '&uU=<?php echo $u; ?>')">
        <img src="/images/searchnav.png" width="17" height="17">
      </div>
    </div>
    <div class="clear"></div>
  </div>
  <div id="phoSearchResults" class="longSearches"></div>
  <?php if($isOwner == "Yes"){ ?>
  <div id="photo_form">
    <?php echo $photo_form; ?>
      <div class="clear"></div>
        <?php if($isMob){ ?>
          <hr class="dim">
        <?php } ?>
      </div>
    <?php } ?>
    <?php if($isOwner == "No"){ ?>
      <?php if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){ ?>
        <p style="font-size: 16px; color: #999;" class="txtc">
          In order to upload photos please <a href="/login">log in</a>.
          Haven&#39;t got an account? <a href="/signup">Sign up</a>
        </p>
      <?php } ?>
      <div id="data_holder">
        <div>
          <div><span><?php echo $all_count; ?></span> comments got</div>
          <div><span><?php echo $out_likes; ?></span> likes given</div>
        </div>
      </div>
      <?php if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){ ?>
        <a href="/photos/<?php echo $log_username; ?>" style="text-align: center;
          display: block;">Upload photos to my galleries</a>
        <div class="clear"></div>
      <?php } ?>
    <?php } ?>
    <div id="data_holder">
      <div>
        <div><span><?php echo $count_all; ?></span> photos</div>
      </div>
    </div>

    <button id="sort" class="main_btn_fill">Filter Photos</button>
    <div id="sortTypes">
      <div class="gridDiv">
        <p class="mainHeading">Publish date</p>
        <div id="sort_0">Newest to oldest</div>
        <div id="sort_1">Oldest to newest</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Gallery (1)</p>
        <div id="sort_2">Myself</div>
        <div id="sort_3">Family</div>
        <div id="sort_4">Pets</div>
        <div id="sort_5">Friends</div>
        <div id="sort_6">Games</div>
        <div id="sort_7">Freetime</div>
        <div id="sort_8">Sports</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Description</p>
        <div id="sort_15">Alphabetical order</div>
        <div id="sort_16">Reverse alphabetical order</div>
      </div>
      <div class="gridDiv">
        <p class="mainHeading">Gallery (2)</p>
        <div id="sort_9">Knowledge</div>
        <div id="sort_10">Hobbies</div>
        <div id="sort_11">Working</div>
        <div id="sort_12">Relations</div>
        <div id="sort_13">Other</div>
        <div id="sort_14">Drag &amp; Drop</div>
      </div>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>

    <hr class="dim">

    <div class="flexibleSol mainPhotRel" id="userFlexArts"><?php echo $gallery_list; ?></div>
    <div class="clear"></div>
    <div id="paginationCtrls" style="text-align: center; margin: 30px;">
      <?php echo $paginationCtrls; ?>
    </div>
    <hr class="dim">

    <div id="data_holder">
      <div>
        <div><span><?php echo $countRels; ?></span> related photos</div>
      </div>
    </div>

    <div class="flexibleSol mainPhotRel" id="userFlexArts">
      <?php echo $related_p; ?>
    </div>
    <div class="clear"></div>
    <?php if($count_all == 0 && $isOwner == "Yes"){ ?>
      <i style="font-size: 14px;">You have not uploaded any videos yet ...</i>
    <?php }else if($count_all == 0 && $isOwner == "No"){ ?>
      <i style="font-size: 14px;">
        Unfortunately, <?php echo $u; ?> has not uploaded any videos yet ...
      </i>
    <?php } ?>
                
    <div class="clear"></div>
    
    <?php echo $belong; ?>
    <br />
  </div>
  <?php include_once("template_pageBottom.php"); ?>
  <script type="text/javascript">
    let beforeInner;
    if (iso == "Yes"){
      beforeInner = _("photo_form").innerHTML;
    }

    doDD('sort', 'sortTypes');

    const SERVER = "/photos?u=<?php echo $u; ?>&otype=";

    function successHandler(req) {
      _("userFlexArts").innerHTML = req.responseText;
      startLazy(true);
    }

    const BOXES = [];
    for(let i = 0; i < 17; i++){
      BOXES.push("sort_" + i);
    }

    for (let box of BOXES) {
      addListener(box, box, 'userFlexArts', SERVER, successHandler);
    }

    changeStyle("sort_0", BOXES);
    </script>
</body>
</html>
