<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/art_common.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'a_array.php';
  require_once 'headers.php';
  require_once 'ccov.php';

  $one = "1";
  $a = "a";
  $b = "b";
  $c = "c";

  $u = checkU($_GET["u"], $conn);
  userExists($conn, $u);

  // Decode url param identifier
  if(isset($_GET["p"])){
    $x = $_GET['p'];
    $pure_p = $x;
    $p = base64url_decode($x,$hshkey);
  }else{
    header('Location: /index');
    exit();
  }
  
  function getArtId($conn, $u, $p) {
    $sql = "SELECT id FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    return $id;
  }

  function articleExists($conn, $u, $p) {
    $sql = "SELECT * FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows < 1){
      header('location: /articlenotexist');
      exit();
    }
    $stmt->close();
  }

  // Get unique article id
  $id = getArtId($conn, $u, $p);
  
  $_SESSION["id"] = $id;

  // Make sure this article exists in the database
  articleExists($conn, $u, $p);
  
  // Check to see if the viewer is the account owner
  $isOwner = isOwner($u, $log_username, $user_ok);  
  
  $profile_pic = getUserAvatar($conn, $u);
  $isBlock = isBlocked($conn, $log_username, $u);

  /*
    TODO: Instead of hardcoding the 5 attachable images create a more clever and dynamic
    solution.
  */

  $sql = "SELECT img1, img2, img3, img4, img5 FROM articles WHERE written_by = ? AND
    post_time = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$p);
  $stmt->execute();
  $stmt->bind_result($e_img1, $e_img2, $e_img3, $e_img4, $e_img5);
  $stmt->fetch();
  $stmt->close();

  $arr_of_imgs = array($e_img1, $e_img2, $e_img3, $e_img4, $e_img5);

  // Count empty imgs
  $e_img_count = count($arr_of_imgs) - count(array_filter($arr_of_imgs));
  
  function mapImgURL($img) {
    if($img) {
      $pcurl = '/permUploads/'.$img;
      return '
        <div data-src=\''.$pcurl.'\' onclick="openIimgBig(\''.$img.'\')"
          class="pclyxbz lazy-bg"></div>';
    }
  }

  $arr_of_imgs = array_map('mapImgURL', $arr_of_imgs);

  $sql = "SELECT * FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $u, $p);
  $stmt->execute();
  $result = $stmt->get_result();
  if($row = $result->fetch_assoc()){
    $written_by_ma = $row["written_by"];
    $originalTitle = $row['title'];
    $content2 = $row["content"];
    $content_ma = stripslashes(cleanStr($content2));
    $title_main_ma  = wrapText(cleanStr(stripslashes($row["title"])), 30);
    $title_ma  = cleanStr(stripslashes($row["title"]));
    $tmlong = $title_main_ma;
      
    $post_time_ma = $row["post_time"];
    $post_time_am = strftime("%b %d, %Y", strtotime($post_time_ma));
    $tags_ma = $row["tags"];
    $cat_ma = $row["category"];

    $cover = chooseCover($cat);
    $cover_ma = $cover;

    $tags_explode = explode(",", $tags_ma);
    $tags_count_ma = count($tags_explode);
  }
  $stmt->close();

  // Check if user has liked the article
  $isHeart = isAdded($conn, $log_username, $p, $u, $user_ok, 'heart_likes');

  // Add like(heart) button
  list($heartButton, $isHeartOrNot) = genHeartBtn($isHeart, $p, $u);

  // Heart count
  $heart_count = countHearts($conn, $p, $u);

  // Check if user added article as fav 
  $isFav = isAdded($conn, $log_username, $p, $u, $user_ok, 'fav_art');

  // Add fav button 
  list($favButton, $isFavOrNot) = genFavBtn($isFav, $p, $u);

  // Add delete & edit button
  list($deleteButton, $editButton) = genButtons($log_username, $written_by_ma, $p, $u);

  // Get related articles
  // First get user's friends
  $all_friends = getUsersFriends($conn, $u, $log_username);
  
  $allfmy = join("','", $all_friends);

  $related = "";
  $all_related = array();
  $sql = "SELECT * FROM articles WHERE (category = ? OR tags IN ('$tags') OR written_by IN
    ('$allfmy')) AND written_by != ? ORDER BY post_time";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $cat, $u);
  $stmt->execute();
  $result3 = $stmt->get_result();
  while($row = $result3->fetch_assoc()){
    array_push($all_related, $row["id"]);
  }
  $stmt->close();
  shuffle($all_related);

  // Choose 3 random articles from the suggested ones
  $rel = join(",", $all_related);
  $sql = "SELECT * FROM articles WHERE id IN ('$rel') ORDER BY post_time DESC LIMIT 3";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $related .= genFullBox($row);
  }
  $stmt->close();

  // Do not let $related to be empty: suggest articles from the same category
  if($related == ""){
    $sql = "SELECT * FROM articles WHERE written_by != ? AND category = ? AND post_time = ?
      LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $log_username, $cat, $post_time);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $related .= genFullBox($row);
    }
    $stmt->close();
  }

  /*
    If related articles are still none suggest articles created at the same time or
    titled the same
  */

  if($related == ""){
    $sql = "SELECT * FROM articles WHERE written_by != ? AND category = ? OR post_time = ?
      OR title = ? LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $log_username, $cat, $post_time, $title);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $related .= genFullBox($row);
    }
    $stmt->close();
  }

  $isrel = false;
  // If related articles is still none display not found msg
  if($related == ""){
    $related = '
      <p style="font-size: 14px; color: #999;" class="txtc">
        We could not list any related article for you
      </p>';
    $isrel = true;
  }

  // Count the replies and posts
  $sql = "SELECT COUNT(id) FROM article_status WHERE artid = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($scount);
  $stmt->fetch();
  $stmt->close();

  $sql = "SELECT COUNT(id) FROM article_status WHERE artid = ? AND type = ? OR type = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iss", $id, $a, $c);
  $stmt->execute();
  $stmt->bind_result($cposts);
  $stmt->fetch();
  $stmt->close();

  $sql = "SELECT COUNT(id) FROM article_status WHERE type = ? AND artid = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si",$b,$id);
  $stmt->execute();
  $stmt->bind_result($creplies);
  $stmt->fetch();
  $stmt->close();

  // Get users's other articles
  $usersarts = "";
  $sql = "SELECT * FROM articles WHERE written_by = ? AND post_time != ? ORDER BY RAND()
    LIMIT 3";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $u, $p);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $usersarts .= genFullBox($row);
  }
  
  $stmt->close();
  
  // User has not articles except this one
  if($usersarts == ""){
      $usersarts = '
        <p style="font-size: 14px; color: #999;" class="txtc">
          You have no other articles except this one
        </p>';
  }

  // Get favourite articles
  $fav_arts = "";
  $sql = "
    SELECT f.*, a.* FROM fav_art AS f LEFT JOIN articles AS a ON f.username = a.written_by
      WHERE f.art_uname = ? AND f.art_time = a.post_time ORDER BY post_time DESC LIMIT 3";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $fav_arts .= genFullBox($row);
  }
  $stmt->close();
  
  $isfavis = false;
  if($fav_arts == ""){
    if($isOwner == "No"){
        $fav_arts = '
          <p style="font-size: 14px; color: #999;" class="txtc">
            It seems that '.$u.' has not added any articles as favourite yet
          </p>';
      }else{
        $fav_arts = '
        <p style="font-size: 14px; color: #999;" class="txtc">
          It seems that you have not added any articles as favourite yet
        </p>';
      }
      $isfavis = true;
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo $tmlong; ?></title>
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Read <?php echo $u; ?>'s article about
    '<?php echo $tmlong; ?>'">

  <script src="/js/jjs.js" async></script>
  <script src="/text_editor.js" async></script>
  <script src="/js/main.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/mbc.js"></script>
  <script src="/js/lload.js"></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script type="text/javascript">
    // Define some constants that are used on the client side
    const ID = '<?php echo $p ?>';
    const IMG_ARR = ['<?php echo $e_img1; ?>', '<?php echo $e_img2; ?>',
      '<?php echo $e_img3; ?>', '<?php echo $e_img4; ?>', '<?php echo $e_img5; ?>'];
    const CONTENT = `<?php echo html_entity_decode($content_ma); ?>`;
    const TITLE =  `<?php echo $originalTitle; ?>`;
    const IMG_COUNT = '<?php echo $e_img_count; ?>';
    const URL = '<?php echo base64url_encode($p,$hshkey); ?>';
    const LOGNAME = '<?php echo $log_username; ?>';
  </script>
  <script src='/js/specific/p_dialog.js' defer></script>
  <script src='/js/specific/dialog_btn.js' defer></script>
  <script src='/js/specific/file_dialog.js' defer></script>
  <script src='/js/specific/main_art.js' defer></script>
  <script src='/js/specific/art_helps.js' defer></script>
  <script type="text/javascript">
    var showingSourceCode = false;
    var isInEditMode = false;

    var hasImageGen1 = "";
    var hasImageGen2 = "";
    var hasImageGen3 = "";
    var hasImageGen4 = "";
    var hasImageGen5 = "";  
  </script>
</head>
<body>
  <?php include_once("template_pageTop.php"); ?>
  <div id="overlay"></div>
  <div id="pageMiddle_2">
    <div id="dialogbox_art"></div>
    <div id="dialogbox"></div>

    <div class="biggerHolder">
      <div id="big_view_article" class="genWhiteHolder">
      <?php if($_SESSION["username"] != ""){ ?>
        <div id="heart_btn">
          <span id="cntHeart" style="float: left;">
            <?php echo $heart_count; ?>
          </span>

          &nbsp;&nbsp;

          <span id="heartBtn">
            <?php echo $heartButton; ?>
          </span>
        </div>

        <div id="fav_btn">
          <span id="favBtn" style="margin-right: 7px;">
            <?php echo $favButton; ?>
          </span>
        </div>

        <img src="/images/black_share.png" id="art_share" style="width: 20px; height: 20px;"
          onclick="shareArticle('<?php echo $id; ?>')">
      <?php } ?>

      <div id="arti_pp" class="lazy-bg genBg" data-src="<?php echo $profile_pic; ?>"
        onclick="window.location = '/user/<?php echo $u; ?>/'"></div>
      <div id="forpcontent" style="font-size: 14px;">
        <div id="artkeppal">
          <p style="font-size: 14px; margin: 0px;">
            <strong>Author: </strong>
            <b class="art_font">
              <a href="/user/<?php echo $u; ?>/">
                <?php echo $u; ?>
              </a>
            </b>
          </p>

          <p style="font-size: 14px; margin: 0px;">
            <strong>Title: </strong>
            <b class="art_font">
              <?php echo $tmlong; ?>
            </b>
          </p>

          <p style="font-size: 14px; margin: 0px;">
            <strong>Category: </strong>
            <b class="art_font">
              <?php echo $cat_ma; ?>
            </b>
          </p>

          <p style="font-size: 14px; margin: 0px;">
            <strong>Posted: </strong>
            <b class="art_font">
              <?php echo $post_time_am; ?>
            </b>
          </p>
          <a href="#pcontent">Go to comments</a>
        </div>

        <br />
        <hr class="dim">
        <?php echo $content_ma; ?></p>
        <hr class="dim">
        <div id="attached_photos" class="flexibleSol">
          <?php
            foreach($arr_of_imgs as $img) {
              echo $img;
            }
          ?>
        </div>
        <div class="clear"></div>
        <br>
      </div>

      <div id="pcontent">
        <button onclick="topFunction()" id="back_top" class="main_btn_fill fixRed">
          Back to top
        </button>
        <?php echo $deleteButton; ?>
        <?php echo $editButton; ?>
        <button class="main_btn_fill fixRed" onclick="printContent('forpcontent')">
          Print article
        </button>
      </div>
    </div>

    <p style="color: #999;" class="txtc">
      <?php echo $scount; ?> comments recorded
    </p>
    <hr class="dim">
    <?php if($isBlock != true){ ?>
      <?php require_once 'article_status.php'; ?>
    <?php }else{ ?>
      <p style="color: #006ad8;" class="txtc">
        Alert: this user blocked you, therefore you cannot post on his/her articles!
      </p>
    <?php } ?>
  </div>
  <div id="uptoea">
    <div id="yellow_box_art" class="genWhiteHolder">
      <b style="font-size: 16px;">Information about the article</b>
      <br /><br />
      <div id="art_mob_wrap">
        &bull; Tags(<?php echo $tags_count_ma; ?>) <?php echo $tags_ma; ?><br />
        &bull; <?php echo $isHeartOrNot; ?><br />
        &bull; <?php echo $isFavOrNot; ?><br />
        &bull; This article belongs to <?php echo $u; ?><br />
        <?php echo "&bull; This article has the &#34;".$cat_ma."&#34; category"; ?>
      </div>
      <div style="float: right; margin-top: -85px; margin-right: 2px;">
        <?php echo $cover_ma; ?>
      </div>
    </div>
    <div class="compdiv genWhiteHolder">
      <b style="font-size: 16px;">Related articles</b>
        <div id="related_arts">
          <?php echo $related; ?>
        </div>
    </div>

    <div class="compdiv genWhiteHolder">
      <?php if($isOwner == "Yes"){
        echo "<b style='font-size: 16px;'>My articles</b>";
      }else{
        echo "<b style='font-size: 16px;'>".$u."&#39;s articles</b>";
      } ?>

      <div id="artsminemy">
        <?php echo $usersarts; ?>
      </div>
    </div>

       <div class="compdiv genWhiteHolder">
        <?php if($isOwner == "Yes"){
          echo "<b style='font-size: 16px;'>My favourite articles</b>";
        }else{
          echo "<b style='font-size: 16px;'>".$u."&#39;s favourite articles</b>";
        } ?>

        <div id="addfavarts">
          <?php echo $fav_arts; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="clear"></div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    window.onbeforeunload = function(){
      if(_("title").innerHTML != "" ||
        window.frames['richTextField'].document.body.innerHTML != ""){
          return "You have unsaved work left. Are you sure you want to leave the page?";
      }
    }
 </script>
</body>
</body>
</html>
