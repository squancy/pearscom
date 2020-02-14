<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/status_common.php';
	require_once 'php_includes/pagination.php';
	require_once 'php_includes/perform_checks.php';
	require_once 'php_includes/wrapText.php';
	//require_once 'user.php';
  require_once 'headers.php';
  require_once 'elist.php';
  require_once 'php_includes/dist.php';

	$status_ui = "";
	$statuslist = "";
	$a = "a";
	$b = "b";
	$c = "c";
	$one = "1";
	$zero = "0";

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM status WHERE account_name=? AND type = ?";
  $url_n = "/user/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'ss', $url_n, $u, $a);

  // Display the number of status posts recorded
	$sql = "SELECT COUNT(id) FROM status WHERE account_name = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$u);
	$stmt->execute();
	$stmt->bind_result($countRs);
	$stmt->fetch();
	$stmt->close();
	$toDis = "";
	if($countRs > 0){
		$toDis = '<p style="color: #999; text-align: center;">'.$countRs.' comments recorded</p>';
	}

	$wmes = "What&#39;s in your mind?";

	if($isOwner == "Yes"){
		$wmes = 'What&#39;s new with you '.$u.'?';
	}

  // Build user input
  if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
    $status_ui = ''.$toDis.'
			<textarea id="statustext" onfocus="showBtnDiv()" placeholder="'.$wmes.'"
        class="user_status"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
			  <div id="progressBar"></div>
				<div id="pbt"></div>
			</div>
      <div id="txt_holder"></div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
      <span id="swithspan">
        <button id="statusBtn" class="btn_rply"
          onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\',false,\'/php_parsers/status_system.php\')">Post</button>
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
            accept="image/*"/>
        </form>
      </div>
      <div class="clear"></div>
    ';
  }else{
    $status_ui = "
      <p style='color: #999;' class='txtc'>
        To leave a comment please <a href='/login'>log in</a>
      </p>
    ";
  }

  // Query to fetch posts & replies
  $sql = "SELECT s.*, u.avatar, u.country, u.online, u.lat, u.lon
		FROM status AS s 
		LEFT JOIN users AS u ON u.username = s.author
		WHERE (s.account_name=? AND s.type=?)
		OR (s.account_name=? AND s.type=?)
		OR (s.account_name=? AND s.type=?)
		ORDER BY s.postdate DESC $limit";
	$bd_wish = "bd_wish";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssssss", $u, $a, $u, $c, $u, $bd_wish);
	$stmt->execute();
	$result = $stmt->get_result();

  // Logic in separate file
  require_once 'template_fetch.php';

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
    if ("" != hasImage) {
      return "You have not posted your image";
    }
  }
</script>

<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statuslist; ?>
</div>
<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
