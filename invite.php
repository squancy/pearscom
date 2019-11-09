<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	$one = "1";
	// Make sure the user is logged in
	if(isset($_SESSION['username'])){
		$u = $_SESSION['username'];
	} else {
		header("Location: /needlogged");
		exit();	
	}

	// Select the member from the users table
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();

	// Now make sure the user exists in the table
	$numrows = $stmt->num_rows;
	if($numrows < 1){
		header('location: /usernotexist');
		exit();
	}
	$stmt->close();
	// Ajax calls this code to execute
	if(isset($_POST["e"]) && isset($_POST["t"])){
		// Clean all the variables
		$e = mysqli_real_escape_string($conn, $_POST['e']);
		$t = htmlentities($_POST['t']);
		// Connect to the database
		require_once 'php_includes/conn.php';
		// Form data error handling
		if($e == "" || $t == ""){
			echo "Please fill out all the form data";
			exit();
		}else{
		    $sql = "SELECT * FROM users WHERE email = ?";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("s",$e);
		    $stmt->execute();
            $stmt->store_result();
	        $stmt->fetch();
	        $numrows = $stmt->num_rows;
	        if($numrows > 0){
	            echo "This email address is already in our system!";
	            exit();
	        }
	        $stmt->close();

			// Check if user has invited more than 5 people today
			$sql = "SELECT COUNT(id) FROM invite WHERE inviter = ? AND invite_time >= NOW() - INTERVAL 1 DAY";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->bind_result($chccnt);
			$stmt->fetch();
			$stmt->close();

			if($chccnt >= 5){
				echo "You have reached your daily invitation limit! Come back tomorrow.";
				mysqli_close($conn);
				exit();
			}

			// Validate email
			if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
				echo "Please give a valid email address!";
				mysqli_close($conn);
				exit();
			}

			$sql = "INSERT INTO invite(inviter, data, inviting_email, invite_time)
					VALUES (?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$log_username,$t,$e);
			$stmt->execute();
			$stmt->close();

			$postdate_ = date("Y-m-d");

			$postdate = strftime("%b %d, %Y", strtotime($postdate_));
			$to = "$e";
			// Send the email to the inviting friend
			$ndate = date("m-d-Y");
			$from = "Pearscom <auto_responder@pearscom.com>";
			$subject = 'Invitation to Pearscom';
			$message = '<!DOCTYPE html>
					       <html>
					       	<head>
					       		<title>Invitation to Pearscom</title>
					       	</head>
					       	<body style="font-family: Arial, sans-serif; background-color: #fff; width: auto; height: auto; margin: 0 auto; margin-top: 10px; max-width: 800px;">
					       		<div style="padding:10px; background-color: #282828; margin: 0 auto; border-top-left-radius: 20px; border-top-right-radius: 20px;">
					       			<a href="https://www.pearscom.com"><img src="https://www.pearscom.com/images/newfav.png" width="50" height="50" alt="pearscom.com" style="display: block; margin: 0 auto;"></a>
					       		</div>
					       			<div style="padding:24px; font-size:14px; border-left: 1px solid rgba(0, 0, 0, 0.1); border-bottom: 1px solid rgba(0, 0, 0, 0.1); border-right: 1px solid rgba(0, 0, 0, 0.1);"><b style="font-size: 20px;">Hello, </b><br /><br />Your friend, '.$log_username.' invited you to a website called Pearscom because '.$log_username.' wants to see you there to be part of an amazing community.<br><br>
					       			<a href="https://www.pearscom.com/user/'.$log_username.'/" style="width: calc(33.333% - 10px); box-sizing: border-box; padding: 5px; background-color: red; color: white; border-radius: 20px; display: block; float: left; margin-right: 10px; text-decoration: none; text-align: center;"> '.$log_username.'</a><a href="https://www.pearscom.com/signup" style="width: calc(33.333% - 10px); box-sizing: border-box; padding: 5px; background-color: red; color: white; border-radius: 20px; display: block; float: left; margin-right: 10px; text-decoration: none; text-align: center;">Sign up</a><a href="https://www.pearscom.com/login" style="width: calc(33.333% - 10px); box-sizing: border-box; padding: 5px; background-color: red; color: white; border-radius: 20px; display: block; float: left; text-decoration: none; text-align: center;">Log in</a><br><br><br>
					       			<b>For this occasion, '.$log_username.' wrote this invitating message to you:</b><br>
					       			<div style="margin-left: 20px; font-style: italic;">'.$t.'<br><br>- '.$log_username.' - '.$ndate.'</div><br>
					       		</div>
					       		<div style="background: #282828; padding: 2px; border-radius: 0px 0px 20px 20px; color: #c1c1c1; font-size: 14px;"><p style="text-align: center;">For further information consider visiting our <a href="https://www.pearscom.com/help" style="color: red; text-decoration: none;">help &amp; support</a> page<br><br>&copy; Pearscom <?php echo date("Y"); ?> <i>&#34;Connect us, connect the world&#34;</i></p></div>
					       	</body>
					       </html>';
			$headers = "From: $from\n";
	        $headers .= "MIME-Version: 1.0\n";
	        $headers .= "Content-type: text/html; charset=iso-8859-1\n";
			mail($to, $subject, $message, $headers);
			echo "invite_success";
			exit();
		}
	}

	// Select invited people by the user
	$allemails = "";
	$sql = "SELECT * FROM invite WHERE inviter = ? ORDER BY invite_time LIMIT 100";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$email = $row["inviting_email"];
		$time = $row["invite_time"];
		$time = strftime("%R, %b %d, %Y", strtotime($time));
		$allemails .= '<div class="inviteEms"><a href="mailto:'.$email.'">'.$email.'</a> - '.$time.'</div>';
	}
	if($result->num_rows < 1){
		$allemails = "<p style='text-align: center; color: #999;'>It seems that you have not sent any invitations to your friends yet ...</p>";
	}

	$stmt->close();

	$sql = "SELECT COUNT(id) FROM invite WHERE inviter = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($inv_count);
    $stmt->fetch();
    $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" href="/style/style.css">
	<title><?php echo $u; ?> - Invite Friends</title>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script type="text/javascript">
		function sendInv() {
		    var e = _("email_invite").value,
		        t = _("t").value,
		        i = _("status");
		    if ("" == e || "" == t) i.innerHTML = "Please fill all the form data";
		    else {
		        _("submit_btn").style.display = "none", i.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var s = ajaxObj("POST", "invite.php");
		        s.onreadystatechange = function() {
		            1 == ajaxReturn(s) && ("invite_success" != s.responseText ? (i.innerHTML = s.responseText, _("submit_btn").style.display = "block") : i.innerHTML = "<p style='font-size: 14px;'>You have successfully send your email to " + e + " <img src='/images/correct.png' width='21' height='21'></p>")
		        }, s.send("e=" + e + "&t=" + t)
		    }
		}

		function statusMax(e, t) {
		    e.value.length > t && (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", e.value = e.value.substring(0, t))
		}
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<div id="data_holder">
			<div>
				<div><span><?php echo $inv_count; ?></span> invited friends</div>
			</div>
		</div>
		<div style="width: 100%; box-sizing: border-box;">
			<p style="color: #999; text-align: center; margin-top: 0;">People you have invited to Pearscom so far</p>
			<?php echo $allemails; ?>
			<div class="clear"></div>
		</div>
		<div class="collection" id="ccSu" style="border-top: 1px solid rgba(0, 0, 0, 0.1);">
	      <p style="font-size: 18px;" id="signup">How can I invite my friends?</p>
	      <img src="/images/alldd.png">
	    </div>
	    <div class="slideInfo" id="suDD">
			<p style="font-size: 14px;">If you like Pearscom we would be very thankful to send and email to your friends to sign up this website. In the <i>Email</i> field add your friend&#39;s email address where you want to send the invitation. Please do not give a fake or not valid email address and do not give some else&#39;s one. Do not send hunreds or thousands of emails, because this can occur a bann!<br /><br>In the textarea write some lines why would you like to see him/her on Pearscom. This message will also appear in your email to ensure your friend this is not an automatically sent message. For some reasons we limited the number of letters to 1,000 that can be sent in a letter (do not write novels just the essence).</p>
	    </div>
	    <br>
		<form id="loginform" class="styleform" name="inviteform" onsubmit="return false;" style="width: 100%; padding: 30px;">
			<input id="email_invite" type="email" placeholder="Friend's email address">

			<textarea placeholder="Why do you want to invite your friend to Pearscom?" id="t" onkeyup="statusMax(this, 1000)"></textarea><br />
			<button id="submit_btn" class="pplongbtn main_btn_fill" onclick="sendInv()" style="width: 50%;">Send Invitation</button>
			<div id="status" style="text-align: center; margin-top: 10px;"></div>
		</form>
		<div class="clear"></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
		function getCookie(e) {
		    for (var t = e + "=", s = decodeURIComponent(document.cookie).split(";"), n = 0; n < s.length; n++) {
		        for (var r = s[n];
		            " " == r.charAt(0);) r = r.substring(1);
		        if (0 == r.indexOf(t)) return r.substring(t.length, r.length)
		    }
		    return ""
		}

		function setDark() {
		    var e = "thisClassDoesNotExist";
		    if (!document.getElementById(e)) {
		        var t = document.getElementsByTagName("head")[0],
		            s = document.createElement("link");
		        s.id = e, s.rel = "stylesheet", s.type = "text/css", s.href = "/style/dark_style.css", s.media = "all", t.appendChild(s)
		    }
		}
		var isdarkm = getCookie("isdark");
		"yes" == isdarkm && setDark();

		function doDD(first, second){
		    $( "#" + first ).click(function() {
		      $( "#" + second ).slideToggle( "fast", function() {
		        
		      });
		    });
		  }

		  doDD("ccSu", "suDD");
	</script>
</body>
</html>