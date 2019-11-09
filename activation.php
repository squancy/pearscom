<?php
	require_once 'headers.php';
	
 	$one = "1";
	$error = "";
	if (isset($_GET['id']) && isset($_GET['u']) && isset($_GET['e']) && isset($_GET['p'])) {
		// Connect to database and sanitize incoming $_GET variables
	    include_once("php_includes/conn.php");
	    $id = preg_replace('#[^0-9]#i', '', $_GET['id']); 
		$u = mysqli_real_escape_string($conn, $_GET["u"]);
		$e = mysqli_real_escape_string($conn, $_GET['e']);
		$p = mysqli_real_escape_string($conn, $_GET['p']);
		if(strpos($p,"__slash__")){
		    $p = str_replace("__slash__","/",$p);
		}
		if(strpos($p,"__dollar__")){
		    $p = str_replace("__dollar__","$",$p);
		}
		if(strpos($p,"__dot__")){
		    $p = str_replace("__dot__",".",$p);
		}
		// Evaluate the lengths of the incoming $_GET variable
		if($id == "" || strlen($u) < 3 || strlen($e) < 5 || $p == ""){
			// Log this issue into a text file and email details to yourself
			$error = "Activation string length issues";
		}
		// Check their credentials against the database
		$sql = "SELECT * FROM users WHERE id=? AND username=? AND email=? AND password=? LIMIT 1";
	   	$stmt = $conn->prepare($sql);
	   	$stmt->bind_param("isss",$id,$u,$e,$p);
	   	$stmt->execute();
	   	$stmt->store_result();
	   	$stmt->fetch();
	   	$numrows = $stmt->num_rows;
		// Evaluate for a match in the system (0 = no match, 1 = match)
		if($numrows == 0){
			// Log this potential hack attempt to text file and email details to yourself
			$error = "Your credentials are not matching anything in our system";
		}
		$stmt->close();
		// Match was found, you can activate them
		$sql = "UPDATE users SET activated=? WHERE id=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si",$one,$id);
		$stmt->execute();
	    $stmt->close();
		// Optional double check to see if activated in fact now = 1
		$sql = "SELECT * FROM users WHERE id=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("is",$id,$one);
	   	$stmt->execute();
	   	$stmt->store_result();
	   	$stmt->fetch();
	   	$numrows = $stmt->num_rows;
		// Evaluate the double check
	    if($numrows == 0){
			// Log this issue of no switch of activation field to 1
	        $error = "Activation failure";
	    } else if($numrows == 1) {
			// Great everything went fine with activation!
	        $error = "Activation success";
	    } else {
	    	$error = "Unknown error occurred";
	    }
	} else {
		// Log this issue of missing initial $_GET variables
		$error = "Missing URL parameters";
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Pearscom - Activate Account</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=devide-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
	<script src="/js/jjs.js" async></script>
	<script src="/js/smooth.js" async></script>
	<script src="/js/uijs.js" async></script>
	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
</head>
<body style="background: #fafafa;">
	<div id="pageMiddle_2">
		<?php if($error != "Activation success"){ ?>
			<p style="text-align: center; font-size: 24px; color: #999; margin-top: 0;">Oops, something went wrong ...</p>
		<?php }else{ ?>
			<p style="text-align: center; font-size: 24px; color: #999; margin-top: 0;">Congratulations! You have successfully activated your account!</p>
		<?php } ?>
		<div id="divf1">
		    <?php if($error == "Activation success"){ ?>
			    <img src="/images/checked.png">
			<?php }else{ ?>
			    <img src="/images/error.png">
			<?php } ?>
		</div>
		<div id="divf2">
			<p><?php echo $error; ?></p>
			<?php if($error == "Activation success"){ ?>
				<p>Great! You have successfully verified your email and activated your account so now you are ready to <a href="/login">log in</a> to your account.<br><br>If you wish to ask any questions feel free to <a href="/help">do it.</a><br><br>We hope you will enjoy being part of an amazing community!</p>
			<?php }else{ ?>
				<p>We are really sorry ... Unfortunately an error has occured during your signing up and returned: <?php echo $error; ?></p>
				<p>Don&#39;t worry! We might help you to solve this problem if you send us a <a href="/help">problem report.</a></p>
			<?php } ?>
		</div>
		<div class="clear"></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
</body>
</html>
