<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/pagination.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/isfriend.php';
  require_once 'php_includes/dist.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';
  require_once 'elist.php';

  // Get lat and lon coordinates of the user
  list($lat, $lon) = getLatLon($conn, $log_username);
    
  $status_ui = "";
  $statuslist = "";
  $a = "a";
  $b = "b";
  $c = "c";

  $p = $_SESSION["photo"];

  // Check if viewer is the owner of the image
  $isOwner = isOwner($u, $log_username, $user_ok);

  // Check if users are friends
  $isFriend = isFriend($u, $log_username, $user_ok, $conn);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM photos_status WHERE account_name=? AND type = ? AND
    photo = ?";
  $url_n = "/photo_zoom/{$p}/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'sss', $url_n, $u, $a, $p);

  $toDis = numOfPosts($conn, 'photos_status', 'photo', $p);  

  // Create user input field
  if($_SESSION["username"] != ""){
    $status_ui = '
      '.$toDis.'
      <textarea id="statustext" class="user_status" onfocus="showBtnDiv()"
        placeholder="What do you think about this photo?"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="txt_holder"></div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
        <span id="swithspan">
          <button id="statusBtn" class="btn_rply"
            onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\',false,\'/php_parsers/photo_status_system.php\')">
              Post
          </button>
        </span>
        <img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply"
          onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
          title="Upload A Photo" />
        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox(\'emojiBox\')">
        <div class="clear"></div>
    ';
    $status_ui.= generateEList($statusid, 'emojiBox', 'statustext');
    $status_ui .= '</div>';
    $status_ui .= '
      <div id="standardUpload" class="hiddenStuff">
        <form id="image_SP" enctype="multipart/form-data" method="post">
          <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
            accept="image/*"/>
        </form>
      </div>
      <div class="clear"></div>
    ';
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>
        Please <a href='/login'>log in</a> in order to leave a comment
      </p>";    
  }

  // Query to fetch posts & replies
  $sql = "SELECT s.*,  u.avatar, u.country, u.online, u.lat, u.lon
    FROM photos_status AS s
    LEFT JOIN users AS u ON u.username = s.author
    WHERE s.photo = ? AND (s.account_name=? AND s.type=?)
    OR (s.account_name=? AND s.type=?)
    ORDER BY s.postdate DESC $limit";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sssss", $p, $u, $a, $u, $c);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    require_once 'photo_fetch.php';
  }else{
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
      echo "
        <p style='text-align: center; color: #999;'>
          Be the first one who post something!
        </p>
      ";
    }
  }
?>
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
    if (hasImage != "") {
      return "You have not posted your image";
    }
  }
</script>

<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statphol; ?>
</div>
<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
