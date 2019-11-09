<?php
	require_once"php_includes/check_login_statues.php";
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	// If the page requestor is not logged in header them away
	if($user_ok != true || $log_username == ""){
		header('Location: /index');
	    exit();
	}
	
	// Select the member from the users table
    $one = "1";
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if($numrows < 1){
    	header('location: /usernotexist');
    	exit();
    }
    $stmt->close();

	// This first query is just to get the total count of rows
	$sql = "SELECT COUNT(id) FROM notifications WHERE username LIKE BINARY ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->bind_result($rows);
	$stmt->fetch();
	$stmt->close();
	// Here we have the total row count
	// This is the number of results we want displayed per page
	$page_rows = 25;
	// This tells us the page number of our last page
	$last = ceil($rows/$page_rows);
	// This makes sure $last cannot be less than 1
	if($last < 1){
		$last = 1;
	}
	// Establish the $pagenum variable
	$pagenum = 1;
	// Get pagenum from URL vars if it is present, else it is = 1
	if(isset($_GET['pn'])){
		$pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
	}
	// This makes sure the page number isn't below 1, or more than our $last page
	if ($pagenum < 1) { 
	    $pagenum = 1; 
	} else if ($pagenum > $last) { 
	    $pagenum = $last; 
	}
	// This sets the range of rows to query for the chosen $pagenum
	$limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	// Establish the $paginationCtrls variable
	$paginationCtrls = '';
	// If there is more than 1 page worth of results
	if($last != 1){
		/* First we check if we are on page one. If we are then we don't need a link to 
		   the previous page or the first page so we do nothing. If we aren't then we
		   generate links to the first page, and to the previous page. */
		if ($pagenum > 1) {
	        $previous = $pagenum - 1;
			$paginationCtrls .= '<a href="/notifications?pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
			// Render clickable number links that should appear on the left of the target page number
			for($i = $pagenum-4; $i < $pagenum; $i++){
				if($i > 0){
			        $paginationCtrls .= '<a href="/notifications?pn='.$i.'">'.$i.'</a> &nbsp; ';
				}
		    }
	    }
		// Render the target page number, but without it being a link
		$paginationCtrls .= ''.$pagenum.' &nbsp; ';
		// Render clickable number links that should appear on the right of the target page number
		for($i = $pagenum+1; $i <= $last; $i++){
			$paginationCtrls .= '<a href="/notifications?pn='.$i.'">'.$i.'</a> &nbsp; ';
			if($i >= $pagenum+4){
				break;
			}
		}
		// This does the same as above, only checking if we are on the last page, and then generating the "Next"
	    if ($pagenum != $last) {
	        $next = $pagenum + 1;
	        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/notifications?pn='.$next.'">Next</a> ';
	    }
	}


	// This first query is just to get the total count of rows
	$sql = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$zero);
	$stmt->execute();
	$stmt->bind_result($rows_f);
	$stmt->fetch();
	$stmt->close();
	// Here we have the total row count
	// This is the number of results we want displayed per page
	$page_rows_f = 25;
	// This tells us the page number of our last page
	$last = ceil($rows_f/$page_rows_f);
	// This makes sure $last cannot be less than 1
	if($last_f < 1){
		$last_f = 1;
	}
	// Establish the $pagenum variable
	$pagenum_f = 1;
	// Get pagenum from URL vars if it is present, else it is = 1
	if(isset($_GET['pnf'])){
		$pagenum_f = preg_replace('#[^0-9]#', '', $_GET['pnf']);
	}
	// This makes sure the page number isn't below 1, or more than our $last page
	if ($pagenum_f < 1) { 
	    $pagenum_f = 1; 
	} else if ($pagenum_f > $last_f) { 
	    $pagenum_f = $last_f; 
	}
	// This sets the range of rows to query for the chosen $pagenum
	$limit_f = 'LIMIT ' .($pagenum_f - 1) * $page_rows_f .',' .$page_rows_f;
	// Establish the $paginationCtrls variable
	$paginationCtrls_f = '';
	// If there is more than 1 page worth of results
	if($last_f != 1){
		/* First we check if we are on page one. If we are then we don't need a link to 
		   the previous page or the first page so we do nothing. If we aren't then we
		   generate links to the first page, and to the previous page. */
		if ($pagenum_f > 1) {
	        $previous_f = $pagenum_f - 1;
			$paginationCtrls_f .= '<a href="notifications?pnf='.$previous_f.'">Previous</a> &nbsp; &nbsp; ';
			// Render clickable number links that should appear on the left of the target page number
			for($i_f = $pagenum_f-4; $i_f < $pagenum_f; $i_f++){
				if($i_f > 0){
			        $paginationCtrls_f .= '<a href="notifications?pnf='.$i_f.'">'.$i_f.'</a> &nbsp; ';
				}
		    }
	    }
		// Render the target page number, but without it being a link
		$paginationCtrls_f .= ''.$pagenum_f.' &nbsp; ';
		// Render clickable number links that should appear on the right of the target page number
		for($i_f = $pagenum_f+1; $i_f <= $last; $i_f++){
			$paginationCtrls_f .= '<a href="notifications?pnf='.$i_f.'">'.$i_f.'</a> &nbsp; ';
			if($i_f >= $pagenum_f+4){
				break;
			}
		}
		// This does the same as above, only checking if we are on the last page, and then generating the "Next"
	    if ($pagenum_f != $last_f) {
	        $next_f = $pagenum_f + 1;
	        $paginationCtrls_f .= ' &nbsp; &nbsp; <a href="notifications?pnf='.$next_f.'">Next</a> ';
	    }
	}

	$zero = "0";
	$notification_list = "";
	$sql = "SELECT * FROM notifications WHERE username LIKE BINARY ? ORDER BY date_time DESC $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1){
		$notification_list = "<p>You do not have any notifications at the moment</p>";
		$stmt->close();
	} else {
		while ($row = $result->fetch_assoc()) {
			$noteid = $row["id"];
			$initiator = $row["initiator"];
			$app = $row["app"];
			$note = $row["note"];
			$date_time = $row["date_time"];
			$username_ = $row["username"];
			$date_time = strftime("%b %d, %Y", strtotime($date_time));
			$sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$initiator);
			$stmt->execute();
			$stmt->bind_result($noti_avatar);
			$stmt->fetch();
			$pcurl = "";
			if($noti_avatar == NULL){
				$pcurl = '/images/avdef.png';
			}else{
				$pcurl = '/user/'.$initiator.'/'.$noti_avatar;
			}
			$notification_list .= "<div id='notifications'><a href='/user/".$initiator."/'><div data-src=\"".$pcurl."\" style='background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; float: left; margin-right: 5px; border-radius: 50%;' class='lazy-bg'></div></a><div style='width: calc(100% - 55px); box-sizing: border-box; float: left;'><b id='not_id'>".$app."<br />".$note."</b><b id='not_date'>".$date_time."</b></div></div><div class='clear'></div><hr class='dim'>";
			$stmt->close();
		}
	}
	$sql = "UPDATE users SET notescheck=NOW() WHERE username=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->close();
?>
<?php
	$friend_requests = "";
	$sql = "SELECT * FROM friends WHERE user2=? AND accepted=? ORDER BY datemade ASC $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$zero);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows < 1){
		$friend_requests = '<p style="font-size: 14px;">You have no friend requests at the moment</p>';
	} else {
		while ($row = $result->fetch_assoc()) {
			$reqID = $row["id"];
			$user1 = $row["user1"];
			$datemade = $row["datemade"];
			$datemade = strftime("%B %d", strtotime($datemade));
			$sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$user1);
			$stmt->execute();
			$stmt->bind_result($user1avatar);
			$stmt->fetch();
			$pcurll = '/user/'.$user1.'/'.$user1avatar;
			if($user1avatar == NULL){
				$pcurll = '/images/avdef.png';
			}
			$stmt->close();
			$user1_original = urlencode($user1);
			if(strlen($user1) > 46){
				$user1 = mb_substr($user1, 0, 43, "utf-8");
				$user1 .= ' ...';
			}
			$friend_requests .= '<div id="friendreq_'.$reqID.'" class="friendrequests">';
			$friend_requests .= '<a href="/user/'.$user1_original.'/"><div data-src=\''.$pcurll.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; float: left; margin-right: 5px; border-radius: 50%;" class="lazy-bg"></div></a>';
			$friend_requests .= '<div class="user_info" style="width: calc(100% - 55px);" id="user_info_'.$reqID.'">On '.$datemade.' <a href="/user/'.$user1_original.'/">'.$user1.'</a><br /> Requested Friendship&nbsp;&nbsp;&nbsp;';
			$friend_requests .= '<button class="main_btn_fill" style="border: 0; border-radius: 10px; padding: 7px;" onclick="friendReqHandler(\'accept\',\''.$reqID.'\',\''.$user1_original.'\',\'user_info_'.$reqID.'\')">Accept</button> or ';
			$friend_requests .= '<button class="main_btn" onclick="friendReqHandler(\'reject\',\''.$reqID.'\',\''.$user1_original.'\',\'user_info_'.$reqID.'\')">Reject</button>';
			$friend_requests .= '</div>';
			$friend_requests .= '</div><hr class="dim">';
	
		}
	}
	$sql = "SELECT COUNT(id) FROM notifications WHERE username = ?";
	$stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($count_nots);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$zero);
    $stmt->execute();
    $stmt->bind_result($count_reqs);
    $stmt->fetch();
    $stmt->close();
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
	<script type="text/javascript">
		function friendReqHandler(e,r,n,s){_(s).innerHTML='<img src="/images/rolling.gif" width="30" height="30">';var t=ajaxObj("POST","php_parsers/friend_system.php");t.onreadystatechange=function(){1==ajaxReturn(t)&&("accept_ok"==t.responseText?_(s).innerHTML="<b>Request Accepted!</b><br />Your are now friends":"reject_ok"==t.responseText?_(s).innerHTML="<b>Request Rejected</b><br />You chose to reject friendship with this user":_(s).innerHTML=t.responseText)},t.send("action="+e+"&reqid="+r+"&user1="+n)}
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<!-- Start page content -->
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
		<!-- End page content -->
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),n=0;n<s.length;n++){for(var r=s[n];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();
	</script>
</body>
</html>