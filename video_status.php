<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/status_common.php';
	require_once 'php_includes/pagination.php';
	require_once 'php_includes/isfriend.php';
	require_once 'php_includes/perform_checks.php';
	require_once 'php_includes/wrapText.php';
	require_once 'safe_encrypt.php';
	require_once 'headers.php';
	require_once 'elist.php';
	require_once 'php_includes/dist.php';

  list($lat, $lon) = getLatLon($conn, $log_username);

	$status_ui = "";
	$statuslist = "";
	$a = "a";
	$b = "b";
	$c = "c";

	$vi = $_SESSION["id"];
  $sType = "video";

	$isOwner = isOwner($u, $log_username, $user_ok); 
	
	$vi = base64url_decode($vi, $hshkey);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM video_status WHERE account_name=? AND vidid = ?";
  $url_n = "/video_zoom/{$vi_en}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'si', $url_n, $u, $vi);

	$isFriend = isFriend($u, $log_username, $user_ok, $conn); 

	if($isOwner == "Yes"){
		$wtext = "Post something about your video!";
	} else {
		$wtext = "What do you think about this video?";
	}

  // Count num of posts
	$sql = "SELECT COUNT(id) FROM video_status WHERE account_name = ? AND vidid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss", $u, $vi);
	$stmt->execute();
	$stmt->bind_result($countRs);
	$stmt->fetch();
	$stmt->close();
	$toDis = "";
	if($countRs > 0){
		$toDis = '<p style="color: #999; text-align: center;">'.$countRs.' comments recorded</p>';
	}

  // Build user input
  if($_SESSION["username"] != ""){
    $status_ui = $toDis.'
      <textarea id="statustext" class="user_status" onfocus="showBtnDiv()"
        placeholder="'.$wtext.'"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
        <span id="swithspan">
          <button id="statusBtn"
            onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\',false,\'/php_parsers/video_status_parser.php\')"
            class="btn_rply">Post</button>
        </span>
        <img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply"
          onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
          title="Upload A Photo" />
        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox(\'emojiBox\')">
        <div class="clear"></div>
    ';
    $status_ui .= generateEList($statusid, 'emojiBox', 'statustext');
    $status_ui .= '</div>';
    $status_ui .= '
      <div id="standardUpload" class="hiddenStuff">
        <form id="image_SP" enctype="multipart/form-data" method="post">
          <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
            accept="image/*">
        </form>
      </div>
      <div class="clear"></div>
    ';  
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>
        Please <a href='/login'>log in</a> in order to leave a comment
      </p>
    ";
  }

  $sql = "SELECT s.*, u.avatar, u.lat, u.lon, u.country, u.online
		FROM video_status AS s 
		LEFT JOIN users AS u ON u.username = s.author
		WHERE s.vidid = ? AND (s.account_name=? AND s.type=?) 
		OR (s.account_name=? AND s.type=?) 
		ORDER BY s.postdate DESC $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("issss", $vi, $u, $a, $u, $c);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
	  require_once 'video_fetch.php';	
	}else{
		echo "<p style='color: #999;' class='txtc'>Be the first one commeting on this video!</p>";
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
<div id="overlay"></div>
<div id="dialogbox"></div>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statvidl; ?>
</div>
<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
