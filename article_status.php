<?php
  /*
    Comment section of an article. Implement status posts, replies, likes etc.
  */

  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/isfriend.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'php_includes/pagination.php';
  require_once 'headers.php';
  require_once 'elist.php';
  require_once 'php_includes/dist.php';

  // Select user's lat and lon
  list($lat, $lon) = getLatLon($conn, $log_username);

  $status_ui = "";
  $statuslist = "";
  $statusid = "";
  $one = "1";
  $zero = "0";
  $a = "a";
  $b = "b";
  $c = "c";
  $ar = $_SESSION["id"];
  $p_en = base64url_encode($p, $hshkey);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM article_status WHERE account_name=? AND artid=?";
  $url_n = "/articles/{$p_en}/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'si', $url_n, $u, $ar); 
 
  // Check if users are friends
  $isFriend = isFriend($u, $log_username, $user_ok, $conn);
  
  $txtMsg = "";
  if($isOwner == "No"){
    $txtMsg = "What is your opinion about this article?";
  }else if($isOwner == "Yes"){
    $txtMsg = "Say something about your article";
  }

  // If user is logged in allow them to comment 
  if($_SESSION["username"] != ""){
    $status_ui = '
      <textarea id="statustext_" onfocus="showBtnDiv()" placeholder="'.$txtMsg.'" 
      class="user_status"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
      <span id="swithspan">
        <button id="statusBtn" class="btn_rply"
          onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext_\')">Post</button
      </span>
      <img src="/images/camera.png" id="triggerBtn_SP_" 
        onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
        title="Upload A Photo" class="triggerBtnreply" />
      <img src="/images/emoji.png" width="22" class="triggerBtn" height="22"
        title="Send emoticons" id="emoji" onclick="openEmojiBox()">
      <div class="clear"></div>
      ';
    $status_ui .= generateEList($statusid, 'emojiBox_art', 'statustext_');
    $status_ui .= '
      </div>
      <div id="standardUpload" class="hiddenStuff">
      <form id="image_SP" enctype="multipart/form-data" method="post">
        <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
          accept="image/*" />
      </form>
      </div>
      <div class="clear"></div>
      ';
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>Please <a href='/login'>log in</a>
        in order to leave a comment</p>";    
  }

  // Get status posts & data about the authors
  $sql = "SELECT s.*, u.avatar, u.lat, u.lon, u.online, u.country
    FROM article_status AS s
    LEFT JOIN users AS u ON u.username = s.author
    WHERE s.artid = ? AND (s.account_name=? AND s.type=?)
    OR (s.account_name=? AND s.type=?)
    ORDER BY s.postdate DESC $limit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("issss", $ar, $u, $a, $u, $c);
  $stmt->execute();
  $result = $stmt->get_result();
  require_once 'art_fetch.php';
?>
<script type='text/javascript'>
  const UNAME = '<?php echo $u; ?>';
</script>
<script src='/js/specific/p_dialog.js' defer></script>
<script src='/js/specific/file_dialog.js' defer></script>
<script src='/js/specific/see_hide.js' defer></script>
<script src='/js/specific/open_emoji.js' defer></script>
<script src='/js/specific/delete_post.js' defer></script>
<script src='/js/specific/insert_emoji.js' defer></script>
<script src='/js/specific/upload_funcs.js' defer></script>
<script src='/js/specific/btn_div.js' defer></script>
<script src='/js/specific/post_reply.js' defer></script>
<script src='/js/specific/share_status.js' defer></script>
<script src='/js/specific/like_status.js' defer></script>
<script type="text/javascript">
  var hasImage = "";
  window.onbeforeunload = function() {
    if ("" != hasImage) {
      return "You have not posted your image";
    }
  } 
</script>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>

<div id="statusarea">
  <?php echo $statartl; ?>
</div>

<div style="text-align: center; padding: 20px;">
  <?php echo $paginationCtrls; ?>
</div>
