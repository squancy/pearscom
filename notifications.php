<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/perform_checks.php';
	require_once 'php_includes/wrapText.php';
	require_once 'php_includes/status_common.php';
	require_once 'php_includes/pagination.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';

	// If the page requestor is not logged in header them away
  isLoggedIn($user_ok, $log_username);

  $u = $_SESSION['username'];

  // Check if user exists in db
  userExists($conn, $u);

  // Handle notifications pagination
  $sql_s = "SELECT COUNT(id) FROM notifications WHERE username LIKE BINARY ?";
  $url_n = "/notifications";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 's', $url_n, $log_username); 

  // Handle friend requests pagination
	$zero = "0";
  $sql_s = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
  $url_n = "/notifications";
  list($paginationCtrls_f, $limit_f) = pagination($conn, $sql_s, 'si', $url_n, $log_username,
    $zero); 

  // Generate notif boxes
  function genNotifBox($row, $conn) {
    $noteid = $row["id"];
    $initiator = $row["initiator"];
    $app = $row["app"];
    $note = $row["note"];
    $date_time = $row["date_time"];
    $username_ = $row["username"];
    $date_time = strftime("%b %d, %Y", strtotime($date_time));
    $sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $initiator);
    $stmt->execute();
    $stmt->bind_result($noti_avatar);
    $stmt->fetch();

    $pcurl = avatarImg($initiator, $noti_avatar);
    return "
      <div id='notifications'>
        <a href='/user/".$initiator."/'>
          <div data-src=\"".$pcurl."\" style='background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 50px; height: 50px;
            display: inline-block; float: left; margin-right: 5px; border-radius: 50%;'
            class='lazy-bg'>
          </div>
        </a>
        <div style='width: calc(100% - 55px); box-sizing: border-box; float: left;'>
          <b id='not_id'>".$app."<br />".$note."</b>
          <b id='not_date'>".$date_time."</b>
        </div>
      </div>
      <div class='clear'>
    </div>
    <hr class='dim'>";
    $stmt->close();
  }

  // Generate friend reqs
  function genReqBox($row, $conn) {
    $reqID = $row["id"];
    $user1 = $row["user1"];
    $datemade = $row["datemade"];
    $datemade = strftime("%B %d", strtotime($datemade));
    $sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user1);
    $stmt->execute();
    $stmt->bind_result($user1avatar);
    $stmt->fetch();
    $pcurll = avatarImg($user1, $user1avatar); 
    $stmt->close();
    $user1_original = urlencode($user1);

    $user1 = wrapText($user1, 46);

    return '
      <div id="friendreq_'.$reqID.'" class="friendrequests">
        <a href="/user/'.$user1_original.'/">
          <div data-src=\''.$pcurll.'\' style="background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 50px; height: 50px;
            display: inline-block; float: left; margin-right: 5px; border-radius: 50%;"
            class="lazy-bg">
          </div>
        </a>
        <div class="user_info" style="width: calc(100% - 55px);" id="user_info_'.$reqID.'">
          On '.$datemade.' <a href="/user/'.$user1_original.'/">'.$user1.'</a><br />
          Requested Friendship&nbsp;&nbsp;&nbsp;
          <button class="main_btn_fill" style="border: 0; border-radius: 10px; padding: 7px;"
            onclick="friendReqHandler(\'accept\',\''.$reqID.'\',\''.$user1_original.'\',
            \'user_info_'.$reqID.'\')">Accept</button> or 
          <button class="main_btn" onclick="friendReqHandler(\'reject\',\''.$reqID.'\',
            \''.$user1_original.'\',\'user_info_'.$reqID.'\')">Reject</button>
        </div>
      </div>
      <hr class="dim">
    ';
  }

  function countNotifs($log_username, $conn) {
    $sql = "SELECT COUNT(id) FROM notifications WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($count_nots);
    $stmt->fetch();
    $stmt->close();
    return $count_nots;
  }

  function countReqs($log_username, $zero, $conn) {
    $sql = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $zero);
    $stmt->execute();
    $stmt->bind_result($count_reqs);
    $stmt->fetch();
    $stmt->close();
    return $count_reps;
  }

	$notification_list = "";
	$sql = "SELECT * FROM notifications WHERE username LIKE BINARY ? ORDER BY date_time DESC
    $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1){
		$notification_list = "<p>You do not have any notifications at the moment</p>";
		$stmt->close();
	} else {
		while ($row = $result->fetch_assoc()) {
      $notification_list .= genNotifBox($row, $conn);
		}
	}
	$sql = "UPDATE users SET notescheck=NOW() WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->close();

  // Handle friend requests
	$friend_requests = "";
	$sql = "SELECT * FROM friends WHERE user2=? AND accepted=? ORDER BY datemade ASC $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$zero);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1){
		$friend_requests = '
      <p style="font-size: 14px;">
        You have no friend requests at the moment
      </p>
    ';
	} else {
		while ($row = $result->fetch_assoc()) {
	    $friend_requests .= genReqBox($row, $conn);	
		}
	}

  $count_nots = countNotifs($log_username, $conn);
  $count_reps = countReqs($log_username, $zero, $conn);	
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $log_username; ?> - Notifications and Friend Requests</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="icon" type="icon/x-icon" href="/images/newfav.png">
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/specific/notif.js"></script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<div id="data_holder">
			<div>
				<div><span><?php echo $count_nots; ?></span> notifications</div>
				<div><span><?php echo $count_reqs; ?></span> friend requests</div>
			</div>
		</div>
		<div id="notesBox" class="notsBoxes" style="margin-right: 10px;">
			<p style="font-size: 20px; margin-top: 0px;">Notifications</p>
			<p><?php echo $notification_list; ?></p>
			<div id="paginationCtrls"><?php echo $paginationCtrls; ?></div>
		</div>

		<div id="friendReqBox" class="notsBoxes">
			<p style="font-size: 20px; margin-top: 0px;">Friend Requests</p>
			<p><?php echo $friend_requests; ?></p>
			<div id="paginationCtrls_f"><?php echo $paginationCtrls_f; ?></div>
		</div>
		<div class="clear"></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
</body>
</html>
